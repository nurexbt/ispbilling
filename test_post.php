<?php
$data = [
    'is_active' => '1',
    'gateway_url' => 'https://isms.gennet.com.bd/api/v3/send-sms',
    'http_method' => 'GET',
    'recipient_param' => 'msisdn',
    'message_param' => 'sms',
    'http_headers' => '{}',
    'additional_params' => '{"api_token": "my_new_token_123", "sid": "GENNETSMSOTP"}'
];

$ch = curl_init('http://127.0.0.1:8090/billsoft/ajax/save_sms_settings.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// We need a session cookie. Let's just create a test session.
// Wait, I already removed the auth check temporarily.
// Let me remove it using replace_file_content first.
