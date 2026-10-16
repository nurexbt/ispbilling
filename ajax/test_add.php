<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['request_id'=>1, 'router_id'=>1, 'package_id'=>1, 'username'=>'test', 'password'=>'test', 'name'=>'test', 'nid_no'=>'1', 'phone'=>'1', 'email'=>'t@t.com', 'is_existing_customer'=>0];
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'add_client.php';
?>
