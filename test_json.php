<?php
$str = '{"api_token": "$2y$12$XNtXdQgifD9//JpPjLFEu.WjMgny9BNlJgR0fGkeNxRAZUHap3Alq", "sid": "TeamzeroNONMASKING", "csms_id": "USRPBUNPHID94FPOQMR1"}';
$decoded = json_decode($str, true);
var_dump($decoded);
echo json_last_error_msg();
?>
