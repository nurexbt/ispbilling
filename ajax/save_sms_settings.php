<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $gateway_url = rtrim($conn->real_escape_string($_POST['gateway_url'] ?? ''), '/');
    $api_key = $conn->real_escape_string($_POST['api_key'] ?? '');
    $sender_id = $conn->real_escape_string($_POST['sender_id'] ?? '');
    
    // Check if row exists
    $res = $conn->query("SELECT id FROM sms_settings_tbl LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $id = $row['id'];
        $sql = "UPDATE sms_settings_tbl SET 
                gateway_url='$gateway_url', 
                api_key='$api_key', 
                sender_id='$sender_id', 
                is_active=$is_active 
                WHERE id=$id";
    } else {
        $sql = "INSERT INTO sms_settings_tbl (gateway_url, api_key, sender_id, is_active) 
                VALUES ('$gateway_url', '$api_key', '$sender_id', $is_active)";
    }
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'SMS gateway settings saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
