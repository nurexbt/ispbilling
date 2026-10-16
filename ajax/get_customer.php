<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$customer_id = $_GET['id'] ?? 0;

if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}

$customer = $res->fetch_assoc();
$stmt->close();

echo json_encode([
    'status' => 'success',
    'data' => $customer
]);
?>
