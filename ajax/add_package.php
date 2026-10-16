<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!has_permission('package_add')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host_id = (int)($_POST['host_id'] ?? 0);
    $original_profile = $conn->real_escape_string(trim($_POST['original_profile'] ?? ''));
    $given_name = $conn->real_escape_string(trim($_POST['given_name'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);

    if (empty($host_id) || empty($original_profile) || empty($given_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Host, Original Profile, and Given Name are required.']);
        exit;
    }

    $sql = "INSERT INTO package_tbl (host_id, original_profile, given_name, price) VALUES ($host_id, '$original_profile', '$given_name', $price)";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Package mapped successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
