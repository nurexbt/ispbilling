<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('connection_request_delete')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $id = intval($_POST['request_id']);

    $query = "DELETE FROM connection_request_tbl WHERE id = $id";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Request deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete request: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
