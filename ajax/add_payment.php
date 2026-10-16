<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = $_POST['customer_id'] ?? 0;
$amount = (float)($_POST['amount'] ?? 0);
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');
$payment_method = $_POST['payment_method'] ?? 'Cash';
$for_month = !empty($_POST['for_month']) ? $_POST['for_month'] : null;
$invoice_id = !empty($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
$pay_from_balance = isset($_POST['pay_from_balance']) ? (int)$_POST['pay_from_balance'] : 0;
$discount = (float)($_POST['discount'] ?? 0);

if ($pay_from_balance) {
    $payment_method = 'Adjust';
}

if (!$customer_id || ($amount <= 0 && $discount <= 0) || $amount < 0 || $discount < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Valid amount or discount and customer ID are required.']);
    exit;
}

$conn->begin_transaction();

try {
    $processed_by = $_SESSION['username'] ?? 'System';

    // 1. Insert payment record
    $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, discount, payment_date, payment_method, for_month, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iddssss", $customer_id, $amount, $discount, $payment_date, $payment_method, $for_month, $processed_by);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert payment record.");
    }
    $stmt->close();

    $advance_billing_date = false;

    // 1b. Update invoice status and paid_amount if invoice_id is provided
    if ($invoice_id) {
        $stmt = $conn->prepare("SELECT amount, paid_amount, discount, description FROM invoice_tbl WHERE customer_id = ? AND id = ? FOR UPDATE");
        $stmt->bind_param("ii", $customer_id, $invoice_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $inv = $res->fetch_assoc();
            $new_paid = (float)$inv['paid_amount'] + $amount;
            $new_discount = (float)$inv['discount'] + $discount;
            $new_status = (($new_paid + $new_discount) >= (float)$inv['amount']) ? 'Paid' : 'Partial';
            
            if ($new_status === 'Paid' && (empty($inv['description']) || $inv['description'] === 'Monthly Bill')) {
                $advance_billing_date = true;
            }
            
            $stmt2 = $conn->prepare("UPDATE invoice_tbl SET paid_amount = ?, discount = ?, status = ? WHERE customer_id = ? AND id = ?");
            $stmt2->bind_param("ddsii", $new_paid, $new_discount, $new_status, $customer_id, $invoice_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
    }

    // 2. Fetch customer current balance
    $stmt = $conn->prepare("SELECT previous_due, advance_payment FROM customer_tbl WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception("Customer not found.");
    }
    $customer = $res->fetch_assoc();
    $stmt->close();

    $previous_due = (float)$customer['previous_due'];
    $advance_payment = (float)$customer['advance_payment'];

    // 3. Update balance based on payment
    
    // First, apply discount against previous_due
    if ($discount > 0) {
        if ($previous_due >= $discount) {
            $previous_due -= $discount;
        } else {
            $previous_due = 0; // Discount can't be added to advance balance
        }
    }

    if ($pay_from_balance) {
        if ($advance_payment < $amount) {
            throw new Exception("Insufficient advance payment balance to cover this amount.");
        }
        $advance_payment -= $amount;
        
        if ($previous_due >= $amount) {
            $previous_due -= $amount;
        } else {
            $previous_due = 0;
        }
    } else {
        if ($invoice_id) {
            // Paying a specific invoice
            $remaining_payment = $amount;
            if ($previous_due > 0) {
                if ($remaining_payment >= $previous_due) {
                    $remaining_payment -= $previous_due;
                    $previous_due = 0;
                } else {
                    $previous_due -= $remaining_payment;
                    $remaining_payment = 0;
                }
            }
            if ($remaining_payment > 0) {
                $advance_payment += $remaining_payment;
            }
        } else {
            // Advance Payment - DO NOT deduct from due, just add to balance
            $advance_payment += $amount;
        }
    }

    // 4. Update customer record
    if ($advance_billing_date) {
        $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ?, billing_date = DATE_ADD(billing_date, INTERVAL 1 MONTH) WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ? WHERE id = ?");
    }
    $stmt->bind_param("ddi", $previous_due, $advance_payment, $customer_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update customer balance.");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
