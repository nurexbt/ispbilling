<?php
require 'includes/db.php';

echo "-- sms_settings_tbl Schema --\n";
$res = $conn->query("SHOW CREATE TABLE sms_settings_tbl");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'] . ";\n\n";
} else {
    echo "Table sms_settings_tbl not found.\n\n";
}

echo "-- sms_history_tbl Schema --\n";
$res = $conn->query("SHOW CREATE TABLE sms_history_tbl");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'] . ";\n";
} else {
    echo "Table sms_history_tbl not found.\n";
}
?>
