<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('area_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['area_id'] ?? 0);
    $area_name = $conn->real_escape_string(trim($_POST['area_name'] ?? ''));
    
    if (empty($id) || empty($area_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Area ID and Name are required.']);
        exit;
    }

    $sql = "UPDATE area_tbl SET area_name = '$area_name' WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Area updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
