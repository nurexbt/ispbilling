<?php
require_once 'includes/db.php';
$res = $conn->query("DESCRIBE sms_history_tbl");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
