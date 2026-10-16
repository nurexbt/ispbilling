import requests
import urllib.parse

data = {
    'is_active': '1',
    'gateway_url': 'https://isms.gennet.com.bd/api/v3/send-sms',
    'http_method': 'POST',
    'recipient_param': 'phone',
    'message_param': 'msg',
    'http_headers': '{"Test": "123"}',
    'additional_params': '{"token": "abcd"}'
}
headers = {'Content-Type': 'application/x-www-form-urlencoded'}
data_encoded = urllib.parse.urlencode(data)

with open('test_save_sms.php', 'w', encoding='utf-8') as f:
    f.write('''<?php
$_POST = array(
    'is_active' => '1',
    'gateway_url' => 'https://isms.gennet.com.bd/api/v3/send-sms',
    'http_method' => 'POST',
    'recipient_param' => 'phone',
    'message_param' => 'msg',
    'http_headers' => '{"Test": "123"}',
    'additional_params' => '{"token": "abcd"}'
);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION = ['user_id' => 1];

ob_start();
require 'ajax/save_sms_settings.php';
$out = ob_get_clean();
echo "OUTPUT:\\n" . $out;
?>''')
