<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('portal_user_delete')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $id = intval($_POST['user_id']);

    $query = "DELETE FROM portal_user_tbl WHERE id = $id";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Portal user deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
