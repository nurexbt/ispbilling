<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = $_GET['router_id'] ?? 0;
$usernames = $_GET['usernames'] ?? []; // Array of usernames to check

if (!$router_id || empty($usernames)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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

$active_users = [];

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    // Construct optimized query to fetch only specific users
    $API->write('/ppp/active/print', false);
    
    $count = count($usernames);
    foreach ($usernames as $i => $user) {
        // RouterOS API query syntax
        $API->write("?name=" . trim($user), false);
        if ($i > 0) {
            $API->write("?#|", false); // OR operator with previous
        }
    }
    
    // Fetch only 'name' property to reduce bandwidth, and end the command
    $API->write("=.proplist=name", true);
    
    // Manual read loop to handle word-by-word response
    // This is more robust against parsing issues or missing !re tags
    $safety_limit = 10000; // Prevent infinite loop
    $count = 0;
    
    while ($count < $safety_limit) {
        $response = $API->read(false);
        $word = $response[0] ?? '';
        
        if ($word === '!done') {
            break;
        }
        
        if ($word === '!fatal' || $word === '!trap') {
            // Error occurred
            break;
        }
        
        if (strpos($word, '=name=') === 0) {
            $name = substr($word, 6); // Remove =name=
            $active_users[] = trim($name);
        }
        
        $count++;
    }
    
    $API->disconnect();

    // Return the list of active users found
    echo json_encode(['success' => true, 'active_users' => array_values(array_unique($active_users))]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to connect to Mikrotik router']);
}

$conn->close();
?>
