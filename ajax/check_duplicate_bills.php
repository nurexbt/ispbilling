<?php
/**
 * ajax/check_duplicate_bills.php
 * Identifies duplicate generated monthly bills for a given month and classifies kept vs deletable bills.
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!has_permission('customer_view') && !has_permission('payment_record_view') && !has_permission('owner_full_access')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied.']);
    exit;
}

$month = trim($_REQUEST['month'] ?? date('Y-m'));
if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$alt_month = date('F-Y', strtotime($month . '-01'));
$alt_month2 = date('F Y', strtotime($month . '-01'));

// Find all customers with 2 or more monthly bills for this month
$dup_query = "SELECT i.customer_id, COUNT(*) as bill_count
              FROM invoice_tbl i
              WHERE (i.billing_month = ? OR i.billing_month = ? OR i.billing_month = ?)
                AND (i.description IS NULL OR i.description = '' OR i.description = 'Monthly Bill' OR i.description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
              GROUP BY i.customer_id
              HAVING bill_count > 1";

$stmt = $conn->prepare($dup_query);
$stmt->bind_param("sss", $month, $alt_month, $alt_month2);
$stmt->execute();
$res = $stmt->get_result();

$duplicate_customer_ids = [];
while ($row = $res->fetch_assoc()) {
    $duplicate_customer_ids[] = (int)$row['customer_id'];
}
$stmt->close();

if (empty($duplicate_customer_ids)) {
    echo json_encode([
        'status' => 'success',
        'month' => $month,
        'month_label' => date('F Y', strtotime($month . '-01')),
        'total_duplicate_customers' => 0,
        'total_deletable_bills' => 0,
        'total_deletable_amount' => 0.0,
        'duplicate_groups' => []
    ]);
    exit;
}

$cust_in_str = implode(',', $duplicate_customer_ids);

// Fetch all bills for these duplicate customers
$bills_query = "SELECT 
    i.id,
    i.customer_id,
    i.amount,
    i.paid_amount,
    i.discount,
    i.billing_month,
    i.status,
    i.description,
    i.created_at,
    i.processed_by,
    c.name as customer_name,
    c.username as customer_username,
    c.phone as customer_phone,
    c.previous_due as customer_current_due,
    c.advance_payment as customer_current_advance,
    a.area_name,
    COALESCE(p.given_name, p.original_profile) as package_name
FROM invoice_tbl i
JOIN customer_tbl c ON i.customer_id = c.id
LEFT JOIN area_tbl a ON c.area_id = a.id
LEFT JOIN package_tbl p ON c.package_id = p.id
WHERE i.customer_id IN ($cust_in_str)
  AND (i.billing_month = ? OR i.billing_month = ? OR i.billing_month = ?)
  AND (i.description IS NULL OR i.description = '' OR i.description = 'Monthly Bill' OR i.description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))
ORDER BY i.customer_id ASC, i.id ASC";

$stmt = $conn->prepare($bills_query);
$stmt->bind_param("sss", $month, $alt_month, $alt_month2);
$stmt->execute();
$b_res = $stmt->get_result();

$customers_map = [];
while ($bill = $b_res->fetch_assoc()) {
    $c_id = (int)$bill['customer_id'];
    if (!isset($customers_map[$c_id])) {
        $customers_map[$c_id] = [
            'customer_id' => $c_id,
            'customer_name' => $bill['customer_name'],
            'customer_username' => $bill['customer_username'],
            'customer_phone' => $bill['customer_phone'],
            'customer_current_due' => (float)$bill['customer_current_due'],
            'customer_current_advance' => (float)$bill['customer_current_advance'],
            'area_name' => $bill['area_name'] ?? '',
            'package_name' => $bill['package_name'] ?? '',
            'bills' => []
        ];
    }
    $customers_map[$c_id]['bills'][] = [
        'id' => (int)$bill['id'],
        'amount' => (float)$bill['amount'],
        'paid_amount' => (float)$bill['paid_amount'],
        'discount' => (float)$bill['discount'],
        'due_amount' => max(0.0, (float)$bill['amount'] - (float)$bill['paid_amount'] - (float)$bill['discount']),
        'status' => $bill['status'],
        'billing_month' => $bill['billing_month'],
        'created_at' => $bill['created_at'],
        'processed_by' => $bill['processed_by'] ?? 'System'
    ];
}
$stmt->close();

$duplicate_groups = [];
$grand_total_deletable_bills = 0;
$grand_total_deletable_amount = 0.0;

foreach ($customers_map as $c_id => $c_data) {
    $bills = $c_data['bills'];
    
    // Sort bills:
    // Priority: Paid (1) > Partial (2) > Unpaid (3)
    // Secondary: Earliest id (lowest id) first
    usort($bills, function($a, $b) {
        $scoreA = ($a['status'] === 'Paid' ? 1 : ($a['status'] === 'Partial' ? 2 : 3));
        $scoreB = ($b['status'] === 'Paid' ? 1 : ($b['status'] === 'Partial' ? 2 : 3));
        if ($scoreA !== $scoreB) {
            return $scoreA - $scoreB;
        }
        return $a['id'] - $b['id'];
    });

    $kept_invoice = $bills[0];
    $remaining_bills = array_slice($bills, 1);

    $deletable_invoices = [];
    $other_duplicates = [];
    $customer_deletable_amount = 0.0;

    foreach ($remaining_bills as $rem) {
        if ($rem['status'] === 'Unpaid') {
            $deletable_invoices[] = $rem;
            $customer_deletable_amount += $rem['due_amount'];
            $grand_total_deletable_bills++;
            $grand_total_deletable_amount += $rem['due_amount'];
        } else {
            $other_duplicates[] = $rem;
        }
    }

    $duplicate_groups[] = [
        'customer_id' => $c_id,
        'customer_name' => $c_data['customer_name'],
        'customer_username' => $c_data['customer_username'],
        'customer_phone' => $c_data['customer_phone'],
        'customer_current_due' => $c_data['customer_current_due'],
        'area_name' => $c_data['area_name'],
        'package_name' => $c_data['package_name'],
        'total_bills' => count($bills),
        'kept_invoice' => $kept_invoice,
        'deletable_invoices' => $deletable_invoices,
        'other_duplicates' => $other_duplicates,
        'customer_deletable_amount' => $customer_deletable_amount
    ];
}

echo json_encode([
    'status' => 'success',
    'month' => $month,
    'month_label' => date('F Y', strtotime($month . '-01')),
    'total_duplicate_customers' => count($duplicate_groups),
    'total_deletable_bills' => $grand_total_deletable_bills,
    'total_deletable_amount' => $grand_total_deletable_amount,
    'duplicate_groups' => $duplicate_groups
]);

