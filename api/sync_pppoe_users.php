<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = $_GET['router_id'] ?? 0;

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router ID is missing']);
    exit;
}

// Get router details
$stmt = $conn->prepare("SELECT ip_address, username, password, api_port FROM routers WHERE id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$result = $stmt->get_result();
$router = $result->fetch_assoc();

if (!$router) {
    echo json_encode(['success' => false, 'message' => 'Router not found']);
    exit;
}

$API = new RouterosAPI();
$API->port = (int)$router['api_port'];
$API->timeout = 5;

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    $secrets = $API->comm('/ppp/secret/print');
    $API->disconnect();

    $data = [];
    foreach ($secrets as $secret) {
        $data[] = [
            'name' => $secret['name'] ?? '',
            'password' => $secret['password'] ?? '',
            'profile' => $secret['profile'] ?? '',
            'comment' => $secret['comment'] ?? '',
            'service' => $secret['service'] ?? 'any'
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to Mikrotik Router']);
}
?>