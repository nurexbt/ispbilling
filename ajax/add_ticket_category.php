<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('support_ticket_add') && !has_permission('txbill_full_access')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$category_name = trim($_POST['category_name'] ?? '');

if (empty($category_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO ticket_category_tbl (name) VALUES (?)");
$stmt->bind_param("s", $category_name);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Category added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
?>
