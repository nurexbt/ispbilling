<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['onu_sn'])) {
    $request_id = intval($_POST['request_id']);
    $onu_sn = $conn->real_escape_string($_POST['onu_sn']);

    if (empty($onu_sn)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter the ONU Serial Number.']);
        exit;
    }

    $submitted_by = $conn->real_escape_string(get_current_user_name($conn));

    $update_query = "UPDATE connection_request_tbl SET onu_sn = '$onu_sn', onu_submitted_by = '$submitted_by', status = 'TX Complete' WHERE id = $request_id";

    if ($conn->query($update_query)) {
        // Also update the store item to mark it as used
        $conn->query("UPDATE store_tbl SET used_at = NOW(), delivery_status = 'used' WHERE barcode_id = '$onu_sn'");
        echo json_encode(['status' => 'success', 'message' => 'ONU SN submitted. Status changed to TX Complete.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit ONU SN.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
