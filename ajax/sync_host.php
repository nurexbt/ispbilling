<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/routeros_api.class.php';

header('Content-Type: application/json');

if (!has_permission('host_edit') && !has_permission('host_view')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['host_id'];

    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid device ID.']);
        exit;
    }

    $sql = "SELECT * FROM host_tbl WHERE id = $id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $host = $result->fetch_assoc();

        if ($host['is_disabled'] == 1) {
            echo json_encode(['status' => 'error', 'message' => 'Device is disabled.']);
            exit;
        }

        $API = new RouterosAPI();
        $API->debug = false;
        $API->port = (int)$host['api_port'];

        // Attempt connection
        if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
            // Disconnect immediately after successful check
            $API->disconnect();
            echo json_encode(['status' => 'success', 'message' => 'Connected successfully! Device is Online.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Connection failed. Device is Offline.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Device not found.']);
    }
}
?>
