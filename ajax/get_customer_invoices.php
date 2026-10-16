<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$customer_id = (int)($_GET['customer_id'] ?? 0);
if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
}

// Fetch customer
$stmt = $conn->prepare("SELECT id, name, username, previous_due, advance_payment FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cust_res = $stmt->get_result();
if ($cust_res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}
$customer = $cust_res->fetch_assoc();
$stmt->close();

// Fetch invoices
$stmt = $conn->prepare("SELECT id, amount, paid_amount, discount, billing_month, description, status, DATE(created_at) as created_date FROM invoice_tbl WHERE customer_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$inv_res = $stmt->get_result();
$invoices = [];
while ($row = $inv_res->fetch_assoc()) {
    $formatted_month = date('F-Y', strtotime($row['billing_month'] . '-01'));
    $row['month_display'] = !empty($row['description']) ? $row['description'] : $formatted_month;
    $row['due_amount'] = max(0, (float)$row['amount'] - (float)$row['paid_amount'] - (float)$row['discount']);
    $invoices[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'customer' => [
        'id' => (int)$customer['id'],
        'name' => $customer['name'],
        'username' => $customer['username'],
        'due' => (float)$customer['previous_due'],
        'advance' => (float)$customer['advance_payment']
    ],
    'invoices' => $invoices
]);
?>
