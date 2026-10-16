<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$customer_id = $_GET['id'] ?? 0;
if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
}

// Get customer details
$stmt = $conn->prepare("
    SELECT c.name, c.previous_due, c.advance_payment, c.pppoe_status, c.username, c.password, c.phone, c.address, c.nid_no, c.onu_sn, c.onu_submitted_by, c.package_price, c.discount, c.connection_date, c.billing_date, c.payment_cycle_date, c.router_id, c.promise_date, p.given_name as package_name, a.area_name, s.subarea_name
    FROM customer_tbl c
    LEFT JOIN package_tbl p ON c.package_id = p.id
    LEFT JOIN area_tbl a ON c.area_id = a.id
    LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}

$customer = $res->fetch_assoc();
$stmt->close();

$payments = [];
$invoices = [];
$total_paid = 0;
$total_billed = 0;

// Get payments
$stmt = $conn->prepare("SELECT id, amount, discount, payment_date AS date_val, payment_method AS description, for_month, note, processed_by FROM payment_tbl WHERE customer_id = ? ORDER BY payment_date DESC, id DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (!empty($row['for_month'])) {
        if (preg_match('/^\d{4}-\d{2}$/', $row['for_month'])) {
            $row['for_month'] = date('F-Y', strtotime($row['for_month'] . '-01'));
        }
    } else {
        $row['for_month'] = 'Advance Payment';
    }
    $payments[] = $row;
    if ($row['description'] !== 'Adjust') {
        $total_paid += $row['amount'];
    }
}
$stmt->close();

// Get generated invoices (bills)
$stmt = $conn->prepare("SELECT id, amount, paid_amount, DATE(created_at) AS date_val, billing_month, description, status FROM invoice_tbl WHERE customer_id = ? ORDER BY created_at DESC, id DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $formatted_month = date('F-Y', strtotime($row['billing_month'] . '-01'));
    $row['description'] = !empty($row['description']) ? $row['description'] : $formatted_month;
    $row['due_amount'] = (float)$row['amount'] - (float)$row['paid_amount'];
    $invoices[] = $row;
}
$stmt->close();

$balance = $customer['advance_payment'];
$total_due = $customer['previous_due'];

echo json_encode([
    'status' => 'success',
    'data' => [
        'name' => $customer['name'],
        'pppoe_status' => $customer['pppoe_status'],
        'promise_date' => $customer['promise_date'],
        'phone' => $customer['phone'],
        'address' => $customer['address'],
        'area_name' => $customer['area_name'],
        'subarea_name' => $customer['subarea_name'],
        'nid_no' => $customer['nid_no'],
        'onu_sn' => $customer['onu_sn'],
        'onu_submitted_by' => $customer['onu_submitted_by'],
        'username' => $customer['username'],
        'password' => $customer['password'],
        'package_name' => $customer['package_name'],
        'package_price' => $customer['package_price'],
        'discount' => $customer['discount'],
        'connection_date' => $customer['connection_date'],
        'billing_date' => $customer['billing_date'],
        'payment_cycle_date' => $customer['payment_cycle_date'],
        'total_billed' => number_format($total_due, 2),
        'total_paid' => number_format($total_paid, 2),
        'balance' => $balance,
        'payments' => $payments,
        'invoices' => $invoices
    ]
]);
?>
