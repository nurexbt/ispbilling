<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('area_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['subarea_id'] ?? 0);
    $area_id = (int)($_POST['area_id'] ?? 0);
    $subarea_name = $conn->real_escape_string(trim($_POST['subarea_name'] ?? ''));
    
    if (empty($id) || empty($area_id) || empty($subarea_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Sub Area ID, Area ID, and Name are required.']);
        exit;
    }

    $sql = "UPDATE subarea_tbl SET area_id = $area_id, subarea_name = '$subarea_name' WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Sub Area updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
