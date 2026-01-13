<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = $_GET['router_id'] ?? 0;
$usernames = $_GET['usernames'] ?? [];

if (!$router_id || empty($usernames) || !is_array($usernames)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Fetch router credentials
$stmt = $conn->prepare("SELECT ip_address, username, password, api_port FROM routers WHERE id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Router not found']);
    exit;
}
$router = $res->fetch_assoc();
$stmt->close();

$API = new RouterosAPI();
$API->port = (int)$router['api_port'];
$API->timeout = 5;

$statuses = []; // username => enabled|disabled

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    // Fetch minimal fields for all secrets, then filter locally
    $secrets = $API->comm('/ppp/secret/print', [
        '.proplist' => 'name,disabled'
    ]);
    $API->disconnect();

    // Index by name for quick lookup
    $index = [];
    foreach ($secrets as $s) {
        if (!isset($s['name'])) continue;
        $val = isset($s['disabled']) ? strtolower(trim((string)$s['disabled'])) : 'false';
        $is_disabled = in_array($val, ['true','yes','on','1'], true);
        $index[$s['name']] = $is_disabled;
    }

    foreach ($usernames as $u) {
        if (isset($index[$u])) {
            $statuses[$u] = $index[$u] ? 'disabled' : 'enabled';
        } else {
            // If not found in secrets, treat as disabled (no secret)
            $statuses[$u] = 'disabled';
        }
    }

    echo json_encode(['success' => true, 'statuses' => $statuses]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to router']);
}

$conn->close();
?>
