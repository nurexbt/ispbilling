<?php
require_once '../includes/db.php';
require_once '../includes/routeros_api.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = $_POST['id'] ?? 0;
$action = $_POST['action'] ?? ''; // 'enable' or 'disable'

if (!$customer_id || !in_array($action, ['enable', 'disable'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

// 1. Fetch Customer info
$stmt = $conn->prepare("SELECT username, router_id, pppoe_status FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found.']);
    exit;
}
$customer = $res->fetch_assoc();
$stmt->close();

$username = $customer['username'];
$router_id = $customer['router_id'];

if (empty($username) || empty($router_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Customer missing username or router assignment.']);
    exit;
}

// 2. Fetch Router info
$stmt = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ?");
$stmt->bind_param("i", $router_id);
$stmt->execute();
$host_res = $stmt->get_result();

if ($host_res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Router not found.']);
    exit;
}
$host = $host_res->fetch_assoc();
$stmt->close();

// 3. Connect to Mikrotik
$API = new RouterosAPI();
$API->debug = false;
$API->port = (int)$host['api_port'];

if (!$API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to the Router API. Check connection.']);
    exit;
}

// 4. Find the PPPoE secret by username
$API->write('/ppp/secret/print', false);
$API->write('?name=' . $username, true);
$READ = $API->read(false);
$ARRAY = $API->parseResponse($READ);

if (empty($ARRAY)) {
    $API->disconnect();
    echo json_encode(['status' => 'error', 'message' => "PPPoE Secret for user '{$username}' not found on router."]);
    exit;
}

$secret_id = $ARRAY[0]['.id'];
$disabled_status = ($action === 'disable') ? 'yes' : 'no';

// 5. Update the PPPoE secret status
$API->write('/ppp/secret/set', false);
$API->write('=.id=' . $secret_id, false);
$API->write('=disabled=' . $disabled_status, true);

$READ = $API->read(false);
$response = $API->parseResponse($READ);
$API->disconnect();

if (isset($response['!trap'])) {
    $error_message = $response['!trap'][0]['message'] ?? 'Unknown error from router.';
    echo json_encode(['status' => 'error', 'message' => 'Router API Error: ' . $error_message]);
    exit;
}

// 6. Update database status
$stmt = $conn->prepare("UPDATE customer_tbl SET pppoe_status = ? WHERE id = ?");
$stmt->bind_param("si", $action, $customer_id);
if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Mikrotik user ' . ucfirst($action) . 'd successfully.',
        'new_status' => $action
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Status updated on router, but failed to update database.']);
}
$stmt->close();
?>
