<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = $_GET['router_id'] ?? 0;

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router ID required']);
    exit;
}

// 1. Get DB Count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pppoe_users WHERE router_id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$db_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 2. Connect to Router
$router_query = $conn->query("SELECT * FROM routers WHERE id = $router_id");
if ($router_query->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Router not found']);
    exit;
}
$router = $router_query->fetch_assoc();

$API = new RouterosAPI();
$API->port = (int)$router['api_port'];
$API->timeout = 5;

$router_count = 0;
$missing_count = 0;

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    // Fetch only names
    $secrets = $API->comm('/ppp/secret/print', array('.proplist' => 'name'));
    $API->disconnect();
    
    $router_count = count($secrets);
    
    // Create map of router secrets
    $router_secret_names = [];
    foreach ($secrets as $secret) {
        if (isset($secret['name'])) {
            $router_secret_names[$secret['name']] = true;
        }
    }
    
    // Check missing
    $users_query = $conn->query("SELECT username FROM pppoe_users WHERE router_id = $router_id");
    while($u = $users_query->fetch_assoc()) {
        if (!isset($router_secret_names[$u['username']])) {
            $missing_count++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'db_count' => $db_count,
        'router_count' => $router_count,
        'missing_count' => $missing_count
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to connect to router',
        'db_count' => $db_count, // Still return DB count
        'router_count' => '-',
        'missing_count' => '-'
    ]);
}
?>