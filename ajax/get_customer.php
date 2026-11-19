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

// Check if customer has an active pending ONU change task
$pending_req_stmt = $conn->prepare("SELECT cr.id, cr.assigned_to, e.full_name as technician_name, cr.status, cr.created_at 
                                     FROM connection_request_tbl cr 
                                     LEFT JOIN employee_tbl e ON cr.assigned_to = e.id 
                                     WHERE cr.customer_id = ? AND cr.request_type = 'onu_change' AND cr.status = 'Assigned' LIMIT 1");
if ($pending_req_stmt) {
    $pending_req_stmt->bind_param("i", $customer_id);
    $pending_req_stmt->execute();
    $pending_res = $pending_req_stmt->get_result();
    $customer['pending_onu_change'] = ($pending_res && $pending_res->num_rows > 0) ? $pending_res->fetch_assoc() : null;
    $pending_req_stmt->close();
} else {
    $customer['pending_onu_change'] = null;
}

echo json_encode([
    'status' => 'success',
    'data' => $customer
]);
?>
