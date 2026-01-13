<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = $_GET['router_id'] ?? 0;

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router ID is required']);
    exit;
}

// Fetch router details
$stmt = $conn->prepare("SELECT ip_address, username, password, api_port FROM routers WHERE id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Router not found']);
    exit;
}

$router = $result->fetch_assoc();
$stmt->close();

$API = new RouterosAPI();
$API->port = (int)$router['api_port'];
$API->timeout = 5;

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    $profiles = $API->comm('/ppp/profile/print');
    $API->disconnect();

    $data = [];
    foreach ($profiles as $profile) {
        $data[] = [
            'name' => $profile['name'],
            'local_address' => $profile['local-address'] ?? '',
            'remote_address' => $profile['remote-address'] ?? '',
            'rate_limit' => $profile['rate-limit'] ?? ''
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to Mikrotik router']);
}

$conn->close();
?>