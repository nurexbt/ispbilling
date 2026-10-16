<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('area_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $area_id = (int)($_POST['area_id'] ?? 0);
    $subarea_name = $conn->real_escape_string(trim($_POST['subarea_name'] ?? ''));
    
    if (empty($area_id) || empty($subarea_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Area ID and Sub Area Name are required.']);
        exit;
    }

    $sql = "INSERT INTO subarea_tbl (area_id, subarea_name) VALUES ($area_id, '$subarea_name')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Sub Area added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
