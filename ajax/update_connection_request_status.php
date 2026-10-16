<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('connection_request_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $id = intval($_POST['request_id']);
    $status = $conn->real_escape_string($_POST['status']);

    $valid_statuses = ['Pending', 'Approved', 'Rejected'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }

    $query = "UPDATE connection_request_tbl SET status = '$status' WHERE id = $id";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
