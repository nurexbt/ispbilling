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

$payment_id = (int)($_POST['payment_id'] ?? 0);
$refund_amount = (float)($_POST['refund_amount'] ?? 0);
$refund_reason = trim($_POST['refund_reason'] ?? '');
$refund_method = trim($_POST['refund_method'] ?? 'Cash');

if (!$payment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment ID.']);
    exit;
}

if ($refund_amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Refund amount must be greater than zero.']);
    exit;
}

if ($refund_reason === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a reason / note for the refund.']);
    exit;
}

$conn->begin_transaction();

try {
    $processed_by = $_SESSION['username'] ?? 'System';

    // 1. Fetch payment with lock
    $stmt = $conn->prepare("SELECT p.*, c.id AS cust_id, c.name AS cust_name, c.username AS cust_username, c.previous_due, c.advance_payment 
                            FROM payment_tbl p 
                            JOIN customer_tbl c ON p.customer_id = c.id 
                            WHERE p.id = ? FOR UPDATE");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $p_res = $stmt->get_result();
    if ($p_res->num_rows === 0) {
        throw new Exception("Payment record not found.");
    }
    $payment = $p_res->fetch_assoc();
    $stmt->close();

    $current_payment_amt = (float)$payment['amount'];
    if ($refund_amount > $current_payment_amt) {
        throw new Exception("Refund amount (৳" . number_format($refund_amount, 2) . ") cannot exceed current payment amount (৳" . number_format($current_payment_amt, 2) . ").");
    }

    $customer_id = (int)$payment['customer_id'];
    $new_payment_amt = $current_payment_amt - $refund_amount;

    // 2. Format refund note
    $refund_entry = "Refunded ৳" . number_format($refund_amount, 2) . " (" . $refund_method . ") on " . date('Y-m-d H:i') . " by " . $processed_by . " - Reason: " . $refund_reason;
    $new_note = !empty($payment['note']) ? ($payment['note'] . " | " . $refund_entry) : $refund_entry;

    // 3. Update payment record (amount deducted, history stays with refund note)
    $stmt = $conn->prepare("UPDATE payment_tbl SET amount = ?, note = ? WHERE id = ?");
    $stmt->bind_param("dsi", $new_payment_amt, $new_note, $payment_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update payment record.");
    }
    $stmt->close();

    // 4. Reverse invoice paid amount if payment was for a specific invoice / month
    if (!empty($payment['for_month']) && $payment['for_month'] !== 'Advance Payment') {
        // Try to match invoice by billing_month or description
        $for_month_val = $payment['for_month'];
        // Check if for_month is YYYY-MM or Month-YYYY
        $alt_month = '';
        if (preg_match('/^\d{4}-\d{2}$/', $for_month_val)) {
            $alt_month = date('F-Y', strtotime($for_month_val . '-01'));
        } elseif (preg_match('/^[A-Za-z]+-\d{4}$/', $for_month_val)) {
            $alt_month = date('Y-m', strtotime('01-' . $for_month_val));
        }

        $stmt = $conn->prepare("SELECT id, amount, paid_amount, discount, status 
                                FROM invoice_tbl 
                                WHERE customer_id = ? AND (billing_month = ? OR billing_month = ? OR description = ? OR description = ?)
                                ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->bind_param("issss", $customer_id, $for_month_val, $alt_month, $for_month_val, $alt_month);
        $stmt->execute();
        $inv_res = $stmt->get_result();
        if ($inv_res->num_rows > 0) {
            $inv = $inv_res->fetch_assoc();
            $inv_id = (int)$inv['id'];
            $new_inv_paid = max(0, (float)$inv['paid_amount'] - $refund_amount);
            $total_covered = $new_inv_paid + (float)$inv['discount'];
            $new_inv_status = ($total_covered >= (float)$inv['amount']) ? 'Paid' : (($new_inv_paid > 0) ? 'Partial' : 'Unpaid');

            $stmt2 = $conn->prepare("UPDATE invoice_tbl SET paid_amount = ?, status = ? WHERE id = ?");
            $stmt2->bind_param("dsi", $new_inv_paid, $new_inv_status, $inv_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
    }

    // 5. Adjust customer balance
    // Refunding money means the customer has paid less, so their advance is reduced or their due increases
    $advance_payment = (float)$payment['advance_payment'];
    $previous_due = (float)$payment['previous_due'];

    if ($advance_payment >= $refund_amount) {
        $advance_payment -= $refund_amount;
    } else {
        $remainder = $refund_amount - $advance_payment;
        $advance_payment = 0;
        $previous_due += $remainder;
    }

    $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ? WHERE id = ?");
    $stmt->bind_param("ddi", $previous_due, $advance_payment, $customer_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update customer balance.");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => '৳' . number_format($refund_amount, 2) . ' refunded successfully. History updated with refund note.'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
