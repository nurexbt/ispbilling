<?php
require 'includes/db.php';
$json = '{"api_token": "$2y$12$XNtXdQgifD9//JpPjLFEu.WjMgny9BNlJgR0fGkeNxRAZUHap3Alq", "sid": "TeamzeroNONMASKING", "csms_id": "USRPBUNPHID94FPOQMR1"}';
$additional_params = $conn->real_escape_string($json);
$sql = "UPDATE sms_settings_tbl SET additional_params='$additional_params' WHERE id=1";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
