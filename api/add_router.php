<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$name = $_POST['name'] ?? '';
$ip = $_POST['ip'] ?? '';
$port = $_POST['port'] ?? 8728;
$user = $_POST['user'] ?? '';
$pass = $_POST['pass'] ?? '';

if (empty($name) || empty($ip) || empty($user)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$API = new RouterosAPI();
$API->port = (int)$port;

// Attempt Connection
// We use a try-catch block if the class throws exceptions, but this class uses booleans mostly.
// However, fsockopen might emit warnings. We can suppress them or handle them.
if ($API->connect($ip, $user, $pass)) {
    
    // Get System Identity
    $identityData = $API->comm('/system/identity/print');
    
    $routerIdentity = '';
    // Structure depends on ROS version but usually key 'name' exists in the first element.
    if (isset($identityData[0]['name'])) {
        $routerIdentity = $identityData[0]['name'];
    }

    $API->disconnect();

    // Check Identity Match
    // We compare case-insensitively or strictly? User said "match". I'll use strict for safety or case-insensitive?
    // "system identity match" -> usually exact match.
    if (trim($routerIdentity) === trim($name)) {
        
        // Prepare DB Insert
        $stmt = $conn->prepare("INSERT INTO routers (name, ip_address, username, password, api_port, status) VALUES (?, ?, ?, ?, ?, 'enabled')");
        if ($stmt) {
            $stmt->bind_param("ssssi", $name, $ip, $user, $pass, $port);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Router connected and saved successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database preparation error: ' . $conn->error]);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "Identity Mismatch! Your Input: '$name', Router Identity: '$routerIdentity'"
        ]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Connection Failed! Check IP, User, Password, and ensure API service is enabled on port ' . $port]);
}

$conn->close();
?>