<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$bill_type = trim($_POST['bill_type'] ?? '');
$billing_month = trim($_POST['billing_month'] ?? date('Y-m'));
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
$adjust_from_balance = isset($_POST['adjust_from_balance']) && $_POST['adjust_from_balance'] == '1';

if ($customer_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID.']);
    exit;
}

if (!in_array($bill_type, ['monthly', 'connection_fee', 'deposit'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid bill type selected.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}$/', $billing_month)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid billing month format (expected YYYY-MM).']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Bill amount must be greater than 0.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Lock and fetch customer record
    $stmt = $conn->prepare("SELECT id, name, status, previous_due, advance_payment, billing_date, payment_cycle_date FROM customer_tbl WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $stmt->close();
        throw new Exception("Customer not found.");
    }
    $customer = $res->fetch_assoc();
    $stmt->close();

    // Inactive customer check: Cannot generate bills for inactive customer
    if (isset($customer['status']) && strcasecmp($customer['status'], 'Inactive') === 0) {
        throw new Exception("Cannot generate bill! Customer '{$customer['name']}' is marked as Inactive (restricted).");
    }

    // 2. Determine invoice description based on bill_type
    $description = null;
    $billing_month_text = date('F-Y', strtotime($billing_month . '-01'));
    $billing_month_space = date('F Y', strtotime($billing_month . '-01'));
    $bill_label = 'Monthly Bill (' . $billing_month_text . ')';
    if ($bill_type === 'connection_fee') {
        $description = 'Connection Fee';
        $bill_label = 'Connection Fee Bill';
    } elseif ($bill_type === 'deposit') {
        $description = 'Deposit';
        $bill_label = 'Deposit Bill';
    }

    // 2.5 Duplicate Check: Do not create bill if already exists for this customer
    if ($bill_type === 'monthly') {
        $dup_stmt = $conn->prepare("SELECT id FROM invoice_tbl 
                                    WHERE customer_id = ? 
                                      AND (billing_month = ? OR billing_month = ? OR billing_month = ?)
                                      AND (description IS NULL OR description = '' OR description = 'Monthly Bill' OR description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
                                    LIMIT 1");
        $dup_stmt->bind_param("isss", $customer_id, $billing_month, $billing_month_text, $billing_month_space);
        $dup_stmt->execute();
        $dup_res = $dup_stmt->get_result();
        if ($dup_res->num_rows > 0) {
            $dup_stmt->close();
            throw new Exception("Duplicate bill! A Monthly Bill for {$billing_month_text} already exists for this customer.");
        }
        $dup_stmt->close();
    } else {
        $dup_stmt = $conn->prepare("SELECT id FROM invoice_tbl WHERE customer_id = ? AND description = ? LIMIT 1");
        $dup_stmt->bind_param("is", $customer_id, $description);
        $dup_stmt->execute();
        $dup_res = $dup_stmt->get_result();
        if ($dup_res->num_rows > 0) {
            $dup_stmt->close();
            throw new Exception("Duplicate bill! A {$description} bill already exists for this customer.");
        }
        $dup_stmt->close();
    }

    // 3. Calculate advance payment auto-adjustment & customer balances
    $advance = (float)$customer['advance_payment'];
    $due = (float)$customer['previous_due'];

    $invoice_status = 'Unpaid';
    $paid_amount = 0.0;
    $remaining_bill = $amount;

    if ($adjust_from_balance && $advance > 0) {
        if ($advance >= $remaining_bill) {
            $invoice_status = 'Paid';
            $paid_amount = $remaining_bill;
            $advance -= $remaining_bill;
            $remaining_bill = 0.0;
        } else {
            $invoice_status = 'Partial';
            $paid_amount = $advance;
            $remaining_bill -= $advance;
            $advance = 0.0;
        }
    }

    if ($remaining_bill > 0) {
        $due += $remaining_bill;
    }

    $processed_by = $_SESSION['username'] ?? 'System';

    // 4. Insert Invoice Record
    $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iddssss", $customer_id, $amount, $paid_amount, $billing_month, $description, $invoice_status, $processed_by);
    if (!$stmt->execute()) {
        throw new Exception("Failed to generate invoice: " . $stmt->error);
    }
    $invoice_id = $stmt->insert_id;
    $stmt->close();

    // 5. Insert Auto-Adjust Payment Record if paid from advance balance
    if ($paid_amount > 0) {
        $payment_date = date('Y-m-d');
        $payment_method = 'Adjust';
        $for_month_val = ($bill_type === 'monthly') ? $billing_month : $description;
        $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, payment_date, payment_method, for_month, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idssss", $customer_id, $paid_amount, $payment_date, $payment_method, $for_month_val, $processed_by);
        if (!$stmt->execute()) {
            throw new Exception("Failed to record balance adjustment payment: " . $stmt->error);
        }
        $stmt->close();
    }

    // 6. Update Customer Balances (and cycle/billing dates if monthly bill matches/exceeds cycle)
    $advance_cycle = false;
    $advance_billing = false;

    if ($bill_type === 'monthly') {
        if (!empty($customer['payment_cycle_date'])) {
            $cycle_ym = date('Y-m', strtotime($customer['payment_cycle_date']));
            if ($cycle_ym <= $billing_month) {
                $advance_cycle = true;
            }
        }
        if ($invoice_status === 'Paid' && !empty($customer['billing_date'])) {
            $billing_ym = date('Y-m', strtotime($customer['billing_date']));
            if ($billing_ym <= $billing_month) {
                $advance_billing = true;
            }
        }
    }

    $update_sql = "UPDATE customer_tbl SET previous_due = ?, advance_payment = ?";
    if ($advance_cycle) {
        $update_sql .= ", payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH)";
    }
    if ($advance_billing) {
        $update_sql .= ", billing_date = DATE_ADD(billing_date, INTERVAL 1 MONTH)";
    }
    $update_sql .= " WHERE id = ?";

    $up_stmt = $conn->prepare($update_sql);
    $up_stmt->bind_param("ddi", $due, $advance, $customer_id);
    if (!$up_stmt->execute()) {
        throw new Exception("Failed to update customer balance: " . $up_stmt->error);
    }
    $up_stmt->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "$bill_label of ৳" . number_format($amount, 2) . " generated successfully" . ($paid_amount > 0 ? " (৳" . number_format($paid_amount, 2) . " adjusted from balance)." : "."),
        'invoice_id' => $invoice_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

