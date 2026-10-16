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

$source_payment_id = (int)($_POST['source_payment_id'] ?? 0);
$target_customer_id = (int)($_POST['target_customer_id'] ?? 0);
$target_invoice_id = (int)($_POST['target_invoice_id'] ?? 0);
$transfer_amount = (float)($_POST['transfer_amount'] ?? 0);
$transfer_reason = trim($_POST['transfer_reason'] ?? '');

if (!$source_payment_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid source payment ID.']);
    exit;
}

if (!$target_customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a target customer.']);
    exit;
}

if ($transfer_amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Transfer amount must be greater than zero.']);
    exit;
}

if ($transfer_reason === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a transfer note / reason.']);
    exit;
}

$conn->begin_transaction();

try {
    $processed_by = $_SESSION['username'] ?? 'System';

    // 1. Fetch source payment with lock
    $stmt = $conn->prepare("SELECT p.*, c.name AS source_name, c.username AS source_username, c.previous_due, c.advance_payment 
                            FROM payment_tbl p 
                            JOIN customer_tbl c ON p.customer_id = c.id 
                            WHERE p.id = ? FOR UPDATE");
    $stmt->bind_param("i", $source_payment_id);
    $stmt->execute();
    $p_res = $stmt->get_result();
    if ($p_res->num_rows === 0) {
        throw new Exception("Source payment record not found.");
    }
    $source_payment = $p_res->fetch_assoc();
    $stmt->close();

    $source_customer_id = (int)$source_payment['customer_id'];
    if ($source_customer_id === $target_customer_id) {
        throw new Exception("Source customer and target customer cannot be the same.");
    }

    $current_payment_amt = (float)$source_payment['amount'];
    if ($transfer_amount > $current_payment_amt) {
        throw new Exception("Transfer amount (৳" . number_format($transfer_amount, 2) . ") cannot exceed available payment amount (৳" . number_format($current_payment_amt, 2) . ").");
    }

    // 2. Fetch target customer with lock
    $stmt = $conn->prepare("SELECT id, name, username, previous_due, advance_payment, billing_date 
                            FROM customer_tbl 
                            WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $target_customer_id);
    $stmt->execute();
    $t_res = $stmt->get_result();
    if ($t_res->num_rows === 0) {
        throw new Exception("Target customer not found.");
    }
    $target_customer = $t_res->fetch_assoc();
    $stmt->close();

    // 3. Deduct from Source Payment and record note
    $new_source_amt = $current_payment_amt - $transfer_amount;
    $transfer_source_entry = "Transferred ৳" . number_format($transfer_amount, 2) . " to " . $target_customer['name'] . " (" . $target_customer['username'] . ") on " . date('Y-m-d H:i') . " by " . $processed_by . " - Note: " . $transfer_reason;
    $new_source_note = !empty($source_payment['note']) ? ($source_payment['note'] . " | " . $transfer_source_entry) : $transfer_source_entry;

    $stmt = $conn->prepare("UPDATE payment_tbl SET amount = ?, note = ? WHERE id = ?");
    $stmt->bind_param("dsi", $new_source_amt, $new_source_note, $source_payment_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update source payment record.");
    }
    $stmt->close();

    // 4. Reverse Source Invoice if applicable
    if (!empty($source_payment['for_month']) && $source_payment['for_month'] !== 'Advance Payment') {
        $for_month_val = $source_payment['for_month'];
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
        $stmt->bind_param("issss", $source_customer_id, $for_month_val, $alt_month, $for_month_val, $alt_month);
        $stmt->execute();
        $inv_res = $stmt->get_result();
        if ($inv_res->num_rows > 0) {
            $inv = $inv_res->fetch_assoc();
            $inv_id = (int)$inv['id'];
            $new_inv_paid = max(0, (float)$inv['paid_amount'] - $transfer_amount);
            $total_covered = $new_inv_paid + (float)$inv['discount'];
            $new_inv_status = ($total_covered >= (float)$inv['amount']) ? 'Paid' : (($new_inv_paid > 0) ? 'Partial' : 'Unpaid');

            $stmt2 = $conn->prepare("UPDATE invoice_tbl SET paid_amount = ?, status = ? WHERE id = ?");
            $stmt2->bind_param("dsi", $new_inv_paid, $new_inv_status, $inv_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
    }

    // 5. Adjust Source Customer balance
    $src_adv = (float)$source_payment['advance_payment'];
    $src_due = (float)$source_payment['previous_due'];
    if ($src_adv >= $transfer_amount) {
        $src_adv -= $transfer_amount;
    } else {
        $rem = $transfer_amount - $src_adv;
        $src_adv = 0;
        $src_due += $rem;
    }

    $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ? WHERE id = ?");
    $stmt->bind_param("ddi", $src_due, $src_adv, $source_customer_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update source customer balance.");
    }
    $stmt->close();

    // 6. Credit Target Customer
    $target_month_val = null;
    $target_advance_billing = false;

    if ($target_invoice_id > 0) {
        $stmt = $conn->prepare("SELECT id, amount, paid_amount, discount, billing_month, description, status 
                                FROM invoice_tbl 
                                WHERE customer_id = ? AND id = ? FOR UPDATE");
        $stmt->bind_param("ii", $target_customer_id, $target_invoice_id);
        $stmt->execute();
        $t_inv_res = $stmt->get_result();
        if ($t_inv_res->num_rows > 0) {
            $t_inv = $t_inv_res->fetch_assoc();
            $target_month_val = $t_inv['billing_month'];

            $new_t_paid = (float)$t_inv['paid_amount'] + $transfer_amount;
            $total_t_covered = $new_t_paid + (float)$t_inv['discount'];
            $new_t_status = ($total_t_covered >= (float)$t_inv['amount']) ? 'Paid' : 'Partial';

            if ($new_t_status === 'Paid' && (empty($t_inv['description']) || $t_inv['description'] === 'Monthly Bill')) {
                $target_advance_billing = true;
            }

            $stmt2 = $conn->prepare("UPDATE invoice_tbl SET paid_amount = ?, status = ? WHERE id = ?");
            $stmt2->bind_param("dsi", $new_t_paid, $new_t_status, $target_invoice_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
    }

    // Insert payment record for Target Customer
    $target_payment_note = "Transferred from " . $source_payment['source_name'] . " (" . $source_payment['source_username'] . ") Payment #" . $source_payment_id . " - Note: " . $transfer_reason;
    $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, discount, payment_date, payment_method, for_month, note, processed_by) VALUES (?, ?, 0, CURDATE(), 'Transfer', ?, ?, ?)");
    $stmt->bind_param("iddsss", $target_customer_id, $transfer_amount, $target_month_val, $target_payment_note, $processed_by);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert target payment record.");
    }
    $stmt->close();

    // 7. Adjust Target Customer balance
    $tgt_due = (float)$target_customer['previous_due'];
    $tgt_adv = (float)$target_customer['advance_payment'];

    if ($target_invoice_id > 0) {
        $rem_pay = $transfer_amount;
        if ($tgt_due > 0) {
            if ($rem_pay >= $tgt_due) {
                $rem_pay -= $tgt_due;
                $tgt_due = 0;
            } else {
                $tgt_due -= $rem_pay;
                $rem_pay = 0;
            }
        }
        if ($rem_pay > 0) {
            $tgt_adv += $rem_pay;
        }
    } else {
        // Advance transfer
        if ($tgt_due > 0) {
            if ($transfer_amount >= $tgt_due) {
                $rem = $transfer_amount - $tgt_due;
                $tgt_due = 0;
                $tgt_adv += $rem;
            } else {
                $tgt_due -= $transfer_amount;
            }
        } else {
            $tgt_adv += $transfer_amount;
        }
    }

    if ($target_advance_billing) {
        $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ?, billing_date = DATE_ADD(billing_date, INTERVAL 1 MONTH) WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ? WHERE id = ?");
    }
    $stmt->bind_param("ddi", $tgt_due, $tgt_adv, $target_customer_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update target customer balance.");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => '৳' . number_format($transfer_amount, 2) . ' transferred successfully to ' . $target_customer['name'] . '.'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
