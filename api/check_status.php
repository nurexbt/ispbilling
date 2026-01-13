<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Fetch router details from DB
$stmt = $conn->prepare("SELECT ip_address, username, password, api_port FROM routers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Router not found']);
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

$API = new RouterosAPI();
$API->port = (int)$row['api_port'];
$API->timeout = 2; // Short timeout for status check

if ($API->connect($row['ip_address'], $row['username'], $row['password'])) {
    $API->disconnect();
    echo json_encode(['status' => 'online']);
} else {
    echo json_encode(['status' => 'offline']);
}

$conn->close();
?>