<?php
require_once '../includes/db.php'; 
require_once '../includes/routeros_api.class.php'; 

$API = new RouterosAPI(); 
$API->debug = true; 
$host = $conn->query('SELECT ip_address, api_port, api_username, api_password FROM host_tbl LIMIT 1')->fetch_assoc(); 
$API->port = (int)$host['api_port']; 

if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) { 
    $API->write('/ppp/active/print', false);
    $API->write('?name=khan3972', true);
    $READ = $API->read(false); 
    $ARRAY = $API->parseResponse($READ); 
    print_r($ARRAY); 
    $API->disconnect(); 
} else {
    echo "Connection failed";
}
