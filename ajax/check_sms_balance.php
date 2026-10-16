<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch settings
$res = $conn->query("SELECT * FROM sms_settings_tbl LIMIT 1");
if (!$res || $res->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'SMS Gateway is not configured.']);
    exit;
}
$settings = $res->fetch_assoc();

if (empty($settings['gateway_url']) || empty($settings['api_key'])) {
    echo json_encode(['status' => 'error', 'message' => 'Gateway URL or API Key is missing in settings.']);
    exit;
}

$gateway_url = rtrim($settings['gateway_url'], '/');
// Strip /send-sms or any other endpoint from the gateway URL if user entered it
if (strpos($gateway_url, '/api/v3') !== false) {
    $base_url = substr($gateway_url, 0, strpos($gateway_url, '/api/v3') + 7);
} else {
    $base_url = $gateway_url . '/api/v3';
}

$balance_url = $base_url . '/balance';

// Prepare query parameters
$query_data = [
    'api_token' => $settings['api_key']
];

$url = $balance_url . '?' . http_build_query($query_data);

// Execute request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPGET, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to SMS Gateway: ' . $error]);
    exit;
}

$resp_data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid response from gateway.', 'response' => $response]);
    exit;
}

if (isset($resp_data['status']) && $resp_data['status'] === 'SUCCESS') {
    $balance = 0;
    if (isset($resp_data['smsinfo']['balance_info'][0]['sms_balance'])) {
        $balance = $resp_data['smsinfo']['balance_info'][0]['sms_balance'];
    }
    echo json_encode([
        'status' => 'success', 
        'balance' => $balance,
        'message' => "Available SMS Balance: " . $balance
    ]);
} else {
    $error_msg = $resp_data['error_message'] ?? 'Failed to retrieve balance.';
    echo json_encode(['status' => 'error', 'message' => $error_msg]);
}
?>
