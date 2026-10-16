<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('portal_user_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $id = intval($_POST['user_id']);

    // Get current status
    $check_query = "SELECT is_active FROM portal_user_tbl WHERE id = $id";
    $result = $conn->query($check_query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_status = $row['is_active'] == 1 ? 0 : 1;
        
        $update_query = "UPDATE portal_user_tbl SET is_active = $new_status WHERE id = $id";
        if ($conn->query($update_query)) {
            $status_text = $new_status == 1 ? 'activated' : 'disabled';
            echo json_encode(['status' => 'success', 'message' => "Portal user $status_text successfully.", 'new_status' => $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
