<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('package_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['package_id'] ?? 0);
    $given_name = $conn->real_escape_string(trim($_POST['given_name'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);

    if (empty($id) || empty($given_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Package ID and Given Name are required.']);
        exit;
    }

    $sql = "UPDATE package_tbl SET given_name = '$given_name', price = $price WHERE id = $id";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Package updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
