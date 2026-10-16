<?php
require_once '../includes/db.php';
require_once '../includes/routeros_api.class.php';

header('Content-Type: application/json');

$prefix = 'khan';
$startNum = 5000;
$maxNum = $startNum - 1;

// 1. Check max in database
$query = "SELECT username FROM customer_tbl WHERE username LIKE 'khan%'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $username = $row['username'];
        $numPart = substr($username, strlen($prefix));
        
        if (is_numeric($numPart)) {
            $num = (int)$numPart;
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
}

// 2. Check max in Mikrotik router (if router_id is provided)
$router_id = $_GET['router_id'] ?? '';
if (!empty($router_id)) {
    $stmt = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $router_id);
        $stmt->execute();
        $host_res = $stmt->get_result();
        
        if ($host_res->num_rows > 0) {
            $host = $host_res->fetch_assoc();
            
            $API = new RouterosAPI();
            $API->debug = false;
            $API->port = (int)$host['api_port'];
            
            if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
                $API->write('/ppp/secret/print');
                $secrets = $API->read();
                foreach ($secrets as $secret) {
                    if (isset($secret['name'])) {
                        $username = $secret['name'];
                        // If it starts with the prefix
                        if (strpos($username, $prefix) === 0) {
                            $numPart = substr($username, strlen($prefix));
                            if (is_numeric($numPart)) {
                                $num = (int)$numPart;
                                if ($num > $maxNum) {
                                    $maxNum = $num;
                                }
                            }
                        }
                    }
                }
                $API->disconnect();
            }
        }
        $stmt->close();
    }
}

$nextNum = $maxNum + 1;
$nextUsername = $prefix . $nextNum;

echo json_encode([
    'status' => 'success',
    'username' => $nextUsername,
    'password' => $nextUsername
]);
?>
