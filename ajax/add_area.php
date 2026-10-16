<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('area_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $area_name = $conn->real_escape_string(trim($_POST['area_name'] ?? ''));
    
    if (empty($area_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Area name is required.']);
        exit;
    }

    $sql = "INSERT INTO area_tbl (area_name) VALUES ('$area_name')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Area added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
