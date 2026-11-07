<?php
/**
 * ajax/delete_month_bills.php
 * Bulk deletes all generated monthly bills for a given month and reverts balances.
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

if (!has_permission('customer_delete') && !has_permission('customer_edit') && !has_permission('owner_full_access')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied. You do not have permission to delete bills.']);
    exit;
}

$month = trim($_POST['month'] ?? '');
if (empty($month)) {
    echo json_encode(['status' => 'error', 'message' => 'Billing month is required.']);
    exit;
}

$alt_month = (preg_match('/^\d{4}-\d{2}$/', $month)) ? date('F-Y', strtotime($month . '-01')) : $month;
$alt_month2 = (preg_match('/^\d{4}-\d{2}$/', $month)) ? date('F Y', strtotime($month . '-01')) : $month;

$conn->begin_transaction();
try {
    // Select all generated monthly bills for this month (excluding one-time fees)
    $stmt = $conn->prepare("SELECT id, customer_id, amount, paid_amount, discount, billing_month, status 
                            FROM invoice_tbl 
                            WHERE (billing_month = ? OR billing_month = ?)
                              AND (description IS NULL OR description = '' OR description = 'Monthly Bill' OR description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
                            FOR UPDATE");
    $stmt->bind_param("ss", $month, $alt_month);
    $stmt->execute();
    $res = $stmt->get_result();

    $deleted_count = 0;
    $total_deducted_due = 0.0;

    while ($invoice = $res->fetch_assoc()) {
        $inv_id = (int)$invoice['id'];
        $c_id = (int)$invoice['customer_id'];
        $bill_amt = (float)$invoice['amount'];
        $paid_amt = (float)$invoice['paid_amount'];
        $disc_amt = (float)$invoice['discount'];
        $status = $invoice['status'];

        $unpaid_part = max(0.0, $bill_amt - $paid_amt - $disc_amt);
        $total_deducted_due += $unpaid_part;

        // Fetch customer
        $c_res = $conn->query("SELECT previous_due, advance_payment FROM customer_tbl WHERE id = $c_id FOR UPDATE");
        if ($c_res && $c_row = $c_res->fetch_assoc()) {
            $c_due = (float)$c_row['previous_due'];
            $c_adv = (float)$c_row['advance_payment'];

            $new_c_due = max(0.0, $c_due - $unpaid_part);
            $new_c_adv = $c_adv;

            // Delete any Adjust/Balance payment record
            $adj_res = $conn->query("SELECT id, amount FROM payment_tbl WHERE customer_id = $c_id AND (for_month = '$month' OR for_month = '$alt_month' OR for_month = '$alt_month2') AND payment_method IN ('Adjust', 'Balance')");
            $total_adj = 0.0;
            $adj_ids = [];
            if ($adj_res) {
                while ($adj_row = $adj_res->fetch_assoc()) {
                    $adj_ids[] = (int)$adj_row['id'];
                    $total_adj += (float)$adj_row['amount'];
                }
            }
            if (!empty($adj_ids)) {
                $new_c_adv += $total_adj;
                $conn->query("DELETE FROM payment_tbl WHERE id IN (" . implode(',', $adj_ids) . ")");
            } elseif ($paid_amt > 0) {
                $new_c_adv += $paid_amt;
            }

            // Step cycle date back
            $date_sql = "UPDATE customer_tbl 
                         SET previous_due = $new_c_due, 
                             advance_payment = $new_c_adv, 
                             payment_cycle_date = DATE_SUB(payment_cycle_date, INTERVAL 1 MONTH)" . 
                         ($status === 'Paid' ? ", billing_date = DATE_SUB(billing_date, INTERVAL 1 MONTH)" : "") . 
                         " WHERE id = $c_id";
            $conn->query($date_sql);
        }

        // Delete invoice
        $conn->query("DELETE FROM invoice_tbl WHERE id = $inv_id");
        $deleted_count++;
    }
    $stmt->close();

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully deleted $deleted_count generated bill(s) for $month. Total ৳" . number_format($total_deducted_due, 2) . " deducted from customer dues.",
        'deleted_count' => $deleted_count,
        'total_deducted_due' => $total_deducted_due
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
