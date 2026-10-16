<?php
require 'includes/db.php';

echo "customer_tbl:\n";
$res = $conn->query("DESCRIBE customer_tbl");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";

echo "\nconnection_request_tbl:\n";
$res = $conn->query("DESCRIBE connection_request_tbl");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
?>
