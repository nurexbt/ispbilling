<?php
/**
 * ajax/clean_duplicate_bills.php
 * Cleans redundant duplicate unpaid bills for a given month.
 * Can be run for a specific customer or for all duplicate customers in that month.
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
    echo json_encode(['status' => 'error', 'message' => 'Access Denied. You do not have permission to delete duplicate bills.']);
    exit;
}

$month = trim($_POST['month'] ?? '');
if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode(['status' => 'error', 'message' => 'A valid billing month (YYYY-MM) is required.']);
    exit;
}

$target_customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

$alt_month = date('F-Y', strtotime($month . '-01'));
$alt_month2 = date('F Y', strtotime($month . '-01'));

$conn->begin_transaction();
try {
    // 1. Identify which customer(s) have duplicates
    $cust_condition = $target_customer_id > 0 ? "AND i.customer_id = $target_customer_id" : "";
    
    $dup_query = "SELECT i.customer_id, COUNT(*) as bill_count
                  FROM invoice_tbl i
                  WHERE (i.billing_month = ? OR i.billing_month = ? OR i.billing_month = ?)
                    $cust_condition
                    AND (i.description IS NULL OR i.description = '' OR i.description = 'Monthly Bill' OR i.description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
                  GROUP BY i.customer_id
                  HAVING bill_count > 1";

    $stmt = $conn->prepare($dup_query);
    $stmt->bind_param("sss", $month, $alt_month, $alt_month2);
    $stmt->execute();
    $dup_res = $stmt->get_result();

    $customer_ids = [];
    while ($r = $dup_res->fetch_assoc()) {
        $customer_ids[] = (int)$r['customer_id'];
    }
    $stmt->close();

    if (empty($customer_ids)) {
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'No duplicate bills found to clean for ' . date('F Y', strtotime($month . '-01')) . '.',
            'deleted_count' => 0,
            'total_deducted_due' => 0.0
        ]);
        exit;
    }

    $total_deleted_count = 0;
    $total_deducted_due = 0.0;
    $affected_customers = 0;

    foreach ($customer_ids as $c_id) {
        // Fetch all bills for this customer in this month FOR UPDATE
        $b_stmt = $conn->prepare("SELECT id, customer_id, amount, paid_amount, discount, billing_month, status 
                                  FROM invoice_tbl 
                                  WHERE customer_id = ? 
                                    AND (billing_month = ? OR billing_month = ? OR billing_month = ?)
                                    AND (description IS NULL OR description = '' OR description = 'Monthly Bill' OR description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
                                  ORDER BY id ASC FOR UPDATE");
        $b_stmt->bind_param("isss", $c_id, $month, $alt_month, $alt_month2);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();

        $bills = [];
        while ($b_row = $b_res->fetch_assoc()) {
            $bills[] = $b_row;
        }
        $b_stmt->close();

        if (count($bills) <= 1) {
            continue;
        }

        // Sort bills:
        // 1. Paid comes first
        // 2. Partial comes second
        // 3. Lowest id (earliest created) comes first
        usort($bills, function($a, $b) {
            $scoreA = ($a['status'] === 'Paid' ? 1 : ($a['status'] === 'Partial' ? 2 : 3));
            $scoreB = ($b['status'] === 'Paid' ? 1 : ($b['status'] === 'Partial' ? 2 : 3));
            if ($scoreA !== $scoreB) {
                return $scoreA - $scoreB;
            }
            return (int)$a['id'] - (int)$b['id'];
        });

        $kept_bill = $bills[0];
        $remaining_bills = array_slice($bills, 1);

        $customer_unpaid_deduction = 0.0;
        $deleted_for_this_customer = 0;

        foreach ($remaining_bills as $dup) {
            // Only delete UNPAID duplicate bills as requested
            if ($dup['status'] === 'Unpaid') {
                $dup_id = (int)$dup['id'];
                $bill_amt = (float)$dup['amount'];
                $paid_amt = (float)$dup['paid_amount'];
                $disc_amt = (float)$dup['discount'];

                $unpaid_part = max(0.0, $bill_amt - $paid_amt - $disc_amt);
                $customer_unpaid_deduction += $unpaid_part;

                // Delete the duplicate invoice record
                $del_stmt = $conn->prepare("DELETE FROM invoice_tbl WHERE id = ?");
                $del_stmt->bind_param("i", $dup_id);
                $del_stmt->execute();
                $del_stmt->close();

                $deleted_for_this_customer++;
                $total_deleted_count++;
            }
        }

        if ($deleted_for_this_customer > 0) {
            // Deduct the duplicate unpaid portion from customer's previous_due
            // And step back payment_cycle_date by the number of deleted duplicates
            $up_cust = $conn->prepare("UPDATE customer_tbl 
                                       SET previous_due = GREATEST(0, previous_due - ?),
                                           payment_cycle_date = DATE_SUB(payment_cycle_date, INTERVAL ? MONTH)
                                       WHERE id = ?");
            $up_cust->bind_param("dii", $customer_unpaid_deduction, $deleted_for_this_customer, $c_id);
            $up_cust->execute();
            $up_cust->close();

            $total_deducted_due += $customer_unpaid_deduction;
            $affected_customers++;
        }
    }

    $conn->commit();

    $month_label = date('F Y', strtotime($month . '-01'));
    $msg = "Cleaned $total_deleted_count duplicate unpaid bill(s) across $affected_customers customer(s) for $month_label. Total ৳" . number_format($total_deducted_due, 2) . " deducted from customer dues.";

    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'deleted_count' => $total_deleted_count,
        'affected_customers' => $affected_customers,
        'total_deducted_due' => $total_deducted_due
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

