<?php
/**
 * ajax/delete_generated_bill.php
 * Deletes a single generated invoice record and reverts all balance / date calculations.
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Permission check
if (!has_permission('customer_delete') && !has_permission('customer_edit') && !has_permission('payment_record_view') && !has_permission('owner_full_access')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied. You do not have permission to delete bills.']);
    exit;
}

$invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
if ($invoice_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid invoice ID.']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Fetch invoice
    $stmt = $conn->prepare("SELECT id, customer_id, amount, paid_amount, discount, billing_month, description, status 
                            FROM invoice_tbl 
                            WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $inv_res = $stmt->get_result();
    if ($inv_res->num_rows === 0) {
        $stmt->close();
        throw new Exception("Invoice #$invoice_id not found.");
    }
    $invoice = $inv_res->fetch_assoc();
    $stmt->close();

    $customer_id = (int)$invoice['customer_id'];
    $bill_amount = (float)$invoice['amount'];
    $paid_amount = (float)$invoice['paid_amount'];
    $discount_amount = (float)$invoice['discount'];
    $billing_month = $invoice['billing_month'];
    $status = $invoice['status'];

    // Unpaid portion of this bill that was added to customer's due
    $unpaid_portion = max(0.0, $bill_amount - $paid_amount - $discount_amount);

    // 2. Fetch customer
    $c_stmt = $conn->prepare("SELECT id, previous_due, advance_payment, billing_date, payment_cycle_date 
                              FROM customer_tbl 
                              WHERE id = ? FOR UPDATE");
    $c_stmt->bind_param("i", $customer_id);
    $c_stmt->execute();
    $cust_res = $c_stmt->get_result();
    if ($cust_res->num_rows === 0) {
        $c_stmt->close();
        throw new Exception("Customer #$customer_id not found.");
    }
    $customer = $cust_res->fetch_assoc();
    $c_stmt->close();

    $current_due = (float)$customer['previous_due'];
    $current_advance = (float)$customer['advance_payment'];

    // Deduct unpaid portion from customer's previous_due
    $new_due = max(0.0, $current_due - $unpaid_portion);

    // 3. Revert auto-adjusted payments or restore customer balance
    $alt_bm = (preg_match('/^\d{4}-\d{2}$/', $billing_month)) ? date('F-Y', strtotime($billing_month . '-01')) : $billing_month;
    $alt_bm2 = (preg_match('/^\d{4}-\d{2}$/', $billing_month)) ? date('F Y', strtotime($billing_month . '-01')) : $billing_month;

    $adj_stmt = $conn->prepare("SELECT id, amount FROM payment_tbl 
                                WHERE customer_id = ? 
                                  AND (for_month = ? OR for_month = ? OR for_month = ?) 
                                  AND payment_method IN ('Adjust', 'Balance')");
    $adj_stmt->bind_param("isss", $customer_id, $billing_month, $alt_bm, $alt_bm2);
    $adj_stmt->execute();
    $adj_res = $adj_stmt->get_result();
    
    $total_adjust_amount = 0.0;
    $adjust_payment_ids = [];
    while ($adj_row = $adj_res->fetch_assoc()) {
        $adjust_payment_ids[] = (int)$adj_row['id'];
        $total_adjust_amount += (float)$adj_row['amount'];
    }
    $adj_stmt->close();

    // If an auto-adjust payment existed, restore the advance balance and delete the payment record
    if (!empty($adjust_payment_ids)) {
        $new_advance = $current_advance + $total_adjust_amount;
        $id_list = implode(',', $adjust_payment_ids);
        $conn->query("DELETE FROM payment_tbl WHERE id IN ($id_list)");
    } else {
        // If paid with manual payment, restore the paid amount as customer advance credit
        if ($paid_amount > 0) {
            $new_advance = $current_advance + $paid_amount;
        } else {
            $new_advance = $current_advance;
        }
    }

    // 4. Step cycle date back by 1 month so it can be generated again if desired
    $date_update_sql = "UPDATE customer_tbl 
                        SET previous_due = ?, 
                            advance_payment = ?, 
                            payment_cycle_date = DATE_SUB(payment_cycle_date, INTERVAL 1 MONTH)" . 
                        ($status === 'Paid' ? ", billing_date = DATE_SUB(billing_date, INTERVAL 1 MONTH)" : "") . 
                        " WHERE id = ?";
    $up_cust = $conn->prepare($date_update_sql);
    $up_cust->bind_param("ddi", $new_due, $new_advance, $customer_id);
    $up_cust->execute();
    $up_cust->close();

    // 5. Delete invoice
    $del_inv = $conn->prepare("DELETE FROM invoice_tbl WHERE id = ?");
    $del_inv->bind_param("i", $invoice_id);
    $del_inv->execute();
    $del_inv->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => "Invoice #$invoice_id deleted successfully. ৳" . number_format($unpaid_portion, 2) . " deducted from customer's due balance.",
        'deducted_due' => $unpaid_portion,
        'customer_id' => $customer_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
