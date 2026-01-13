<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$input = json_decode(file_get_contents('php://input'), true);

$user_id = $input['user_id'] ?? 0;
$extend_date = $input['extend_date'] ?? '';

if (!$user_id || empty($extend_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Input']);
    exit;
}

// Update DB
$stmt = $conn->prepare("UPDATE pppoe_users SET extend_date = ?, status = 'enabled' WHERE id = ?");
$stmt->bind_param("si", $extend_date, $user_id);

if ($stmt->execute()) {
    
    // Also re-enable in Router if it was disabled
    $u_stmt = $conn->prepare("SELECT u.*, r.ip_address, r.username as r_user, r.password as r_pass, r.api_port 
                            FROM pppoe_users u 
                            LEFT JOIN routers r ON u.router_id = r.id 
                            WHERE u.id = ?");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $user = $u_stmt->get_result()->fetch_assoc();
    $u_stmt->close();

    if ($user && $user['ip_address']) {
        $API = new RouterosAPI();
        $API->port = (int)$user['api_port'];
        if ($API->connect($user['ip_address'], $user['r_user'], $user['r_pass'])) {
            $secrets = $API->comm('/ppp/secret/print', ['?name' => $user['username']]);
            if (!empty($secrets)) {
                $API->comm('/ppp/secret/enable', ['.id' => $secrets[0]['.id']]);
            }
            $API->disconnect();
        }
    }

    echo json_encode(['success' => true, 'message' => 'User extended successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database Error']);
}

$stmt->close();
$conn->close();
?>