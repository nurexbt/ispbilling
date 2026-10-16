<?php
require_once '../includes/db.php';
require_once '../includes/routeros_api.class.php';

header('Content-Type: application/json');

// Only allow GET requests for reading status
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$routers = [];
$res = $conn->query("SELECT id, ip_address, api_username, api_password, api_port FROM host_tbl WHERE is_disabled = 0");
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $routers[] = $r;
    }
}

$status_map = [];

foreach ($routers as $router) {
    $API = new RouterosAPI();
    $API->debug = false;
    
    // Connect to Mikrotik
    $API->port = (int)$router['api_port'];
    if ($API->connect($router['ip_address'], $router['api_username'], $router['api_password'])) {
        // Fetch all secrets
        $secrets = $API->comm("/ppp/secret/print");
        
        if (is_array($secrets)) {
            foreach ($secrets as $secret) {
                if (isset($secret['name'])) {
                    $disabled = isset($secret['disabled']) && $secret['disabled'] == 'true';
                    $status_map[$secret['name']] = $disabled ? 'Disabled' : 'Enabled';
                }
            }
        }
        $API->disconnect();
    }
}

echo json_encode(['status' => 'success', 'data' => $status_map]);
?>
