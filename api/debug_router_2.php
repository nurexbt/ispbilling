<?php
header('Content-Type: text/plain');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$router_id = 2; // KCTN_Access

$stmt = $conn->prepare("SELECT ip_address, username, password, api_port FROM routers WHERE id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$result = $stmt->get_result();
$router = $result->fetch_assoc();

echo "Connecting to " . $router['ip_address'] . "...\n";

$API = new RouterosAPI();
$API->port = (int)$router['api_port'];
$API->timeout = 10;
$API->debug = true; // Enable debug output

if ($API->connect($router['ip_address'], $router['username'], $router['password'])) {
    echo "Connected.\n";
    echo "Fetching active connections...\n";
    
    $API->write('/ppp/active/print');
    $read = $API->read(false);
    $active_connections = $API->parseResponse($read); // Using parseResponse if available, wait, the class I read earlier didn't have parseResponse method in the tool output? 
    // Wait, I need to check the class again. I might have misread or the previous SearchReplace modified check_active_status.php but I should verify the CLASS itself.
    
    // Actually, in the previous turn I saw `check_active_status.php` using `$API->comm('/ppp/active/print')`.
    // Let's use `comm` which handles write+read.
    
    $active_connections = $API->comm('/ppp/active/print');
    
    echo "Found " . count($active_connections) . " active connections.\n";
    
    $found = false;
    foreach ($active_connections as $conn) {
        if (isset($conn['name'])) {
            // echo "Active: " . $conn['name'] . "\n";
            if ($conn['name'] === 'khan006') {
                echo "MATCH FOUND: khan006 is active!\n";
                $found = true;
                print_r($conn);
            }
        }
    }
    
    if (!$found) {
        echo "khan006 NOT found in active list.\n";
        echo "First 5 active users:\n";
        for($i=0; $i<min(5, count($active_connections)); $i++) {
            print_r($active_connections[$i]);
        }
    }
    
    $API->disconnect();
} else {
    echo "Failed to connect.\n";
}
?>