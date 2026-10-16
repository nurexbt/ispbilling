<?php
require 'includes/db.php';
$res = $conn->query("SELECT * FROM sms_history_tbl ORDER BY sent_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
