<?php
require_once '../includes/db.php';
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS pppoe_username VARCHAR(100) DEFAULT NULL AFTER onu_sn");
echo $conn->error ? 'Error: ' . $conn->error : 'Column added or already exists!';
