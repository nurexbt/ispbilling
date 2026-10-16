<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('host_add')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_name = $conn->real_escape_string($_POST['device_name']);
    $ip_address = $conn->real_escape_string($_POST['ip_address']);
    $api_port = (int)$_POST['api_port'];
    $api_username = $conn->real_escape_string($_POST['api_username']);
    $api_password = $conn->real_escape_string($_POST['api_password']);

    // Basic validation
    if (empty($device_name) || empty($ip_address) || empty($api_username)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }

    $sql = "INSERT INTO host_tbl (device_name, ip_address, api_port, api_username, api_password) 
            VALUES ('$device_name', '$ip_address', $api_port, '$api_username', '$api_password')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Device added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
}
?>
