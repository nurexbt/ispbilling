<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['host_id'];

    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid device ID.']);
        exit;
    }

    // Get current status
    $sql = "SELECT is_disabled FROM host_tbl WHERE id = $id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $current_status = (int)$row['is_disabled'];
        $new_status = $current_status === 1 ? 0 : 1;

        // Update status
        $update_sql = "UPDATE host_tbl SET is_disabled = $new_status WHERE id = $id";
        if ($conn->query($update_sql) === TRUE) {
            $msg = $new_status === 1 ? 'Device disabled successfully.' : 'Device enabled successfully.';
            echo json_encode(['status' => 'success', 'message' => $msg, 'is_disabled' => $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Device not found.']);
    }
}
?>
