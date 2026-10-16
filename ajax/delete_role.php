<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('role_manager_full_access')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
    $id = intval($_POST['role_id']);

    $query = "DELETE FROM role_tbl WHERE id = $id";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Role deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete role: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
