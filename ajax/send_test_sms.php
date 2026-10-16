<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sms_type = $_POST['sms_type'] ?? 'single';
    $message = trim($_POST['message'] ?? '');
    
    // Get gateway configuration
    $res = $conn->query("SELECT * FROM sms_settings_tbl LIMIT 1");
    if (!$res || $res->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'SMS gateway is not configured.']);
        exit;
    }
    
    $settings = $res->fetch_assoc();
    if ($settings['is_active'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'SMS Gateway is currently disabled in settings.']);
        exit;
    }

    if (empty($settings['gateway_url']) || empty($settings['api_key']) || empty($settings['sender_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Gateway configuration is incomplete.']);
        exit;
    }
    
    $gateway_url = rtrim($settings['gateway_url'], '/');
    if (strpos($gateway_url, '/api/v3') !== false) {
        $base_url = substr($gateway_url, 0, strpos($gateway_url, '/api/v3') + 7);
    } else {
        $base_url = $gateway_url . '/api/v3';
    }

    $api_token = $settings['api_key'];
    $sid = $settings['sender_id'];
    
    // Generate a 20 character alphanumeric string for CSMS ID
    $unique_id = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 5)), 0, 20);

    $payload = [
        "api_token" => $api_token,
        "sid" => $sid,
        "sms" => $message
    ];

    $endpoint = $base_url . '/send-sms';
    
    // Function to clean Bangladeshi MSISDN
    function clean_msisdn($number) {
        // Strip everything except digits
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        return $cleaned;
    }
    
    if ($sms_type === 'single') {
        $phone_number = trim($_POST['phone_number'] ?? '');
        if (empty($phone_number) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Phone number and message are required.']);
            exit;
        }
        $cleaned_number = clean_msisdn($phone_number);
        $payload['msisdn'] = $cleaned_number;
        $payload['csms_id'] = $unique_id;
        $recipient_log = $phone_number;
    } else {
        // Bulk
        $endpoint = $base_url . '/send-sms/bulk';
        $bulk_numbers = trim($_POST['bulk_numbers'] ?? '');
        if (empty($bulk_numbers) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Recipient numbers and message are required.']);
            exit;
        }
        // Split by comma, newline, or space and clean up
        $numbers = preg_split('/[\s,]+/', $bulk_numbers, -1, PREG_SPLIT_NO_EMPTY);
        if (count($numbers) > 100) {
            echo json_encode(['status' => 'error', 'message' => 'Max 100 numbers allowed per bulk request.']);
            exit;
        }
        
        $clean_numbers = [];
        foreach ($numbers as $num) {
            $cleaned = clean_msisdn($num);
            if (!empty($cleaned)) {
                $clean_numbers[] = $cleaned;
            }
        }
        
        if (empty($clean_numbers)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid recipient numbers found.']);
            exit;
        }
        
        $payload['msisdn'] = $clean_numbers;
        $payload['batch_csms_id'] = $unique_id;
        $payload['csms_id'] = $unique_id; // Adding this in case the API documentation was wrong and it expects csms_id
        $recipient_log = "Bulk (" . count($clean_numbers) . " numbers)";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        $status_type = 'error';
        $res_message = 'cURL Error: ' . $err;
        $api_response = $err;
    } else {
        $resp_data = json_decode($response, true);
        if ($resp_data && isset($resp_data['status']) && $resp_data['status'] === 'SUCCESS') {
            $status_type = 'success';
            $res_message = 'SMS Request Successful.';
        } else {
            $status_type = 'failed';
            $res_message = $resp_data['error_message'] ?? 'SMS Request Failed.';
        }
        $api_response = $response;
    }

    // Log to history
    $user_id = $_SESSION['user_id'];
    $bulk_numbers_json = ($sms_type === 'bulk' && !empty($clean_numbers)) ? json_encode($clean_numbers) : null;
    
    $stmt = $conn->prepare("INSERT INTO sms_history_tbl (recipient_number, message, status, api_response, sent_by, bulk_numbers) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssis", $recipient_log, $message, $status_type, $api_response, $user_id, $bulk_numbers_json);
        $stmt->execute();
        $stmt->close();
    }

    if ($status_type === 'error') {
        echo json_encode(['status' => 'error', 'message' => $res_message]);
    } else {
        echo json_encode([
            'status' => $status_type, 
            'message' => $res_message, 
            'api_response' => $api_response
        ]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
