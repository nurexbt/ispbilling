<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('host_edit')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['host_id'];
    $device_name = $conn->real_escape_string($_POST['device_name']);
    $ip_address = $conn->real_escape_string($_POST['ip_address']);
    $api_port = (int)$_POST['api_port'];
    $api_username = $conn->real_escape_string($_POST['api_username']);
    $api_password = $conn->real_escape_string($_POST['api_password']);

    if (empty($id) || empty($device_name) || empty($ip_address) || empty($api_username)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }

    $sql = "UPDATE host_tbl SET 
            device_name = '$device_name', 
            ip_address = '$ip_address', 
            api_port = $api_port, 
            api_username = '$api_username'";

    // Only update password if a new one is provided
    if (!empty($api_password)) {
        $sql .= ", api_password = '$api_password'";
    }

    $sql .= " WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Device updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
}
?>
