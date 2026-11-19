<?php
require_once 'includes/db.php';
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS pppoe_username VARCHAR(100) DEFAULT NULL AFTER onu_sn");
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
echo $conn->error ? 'Error: ' . $conn->error : '<b style="color:green">Done: migrations applied successfully</b>';
$conn->close();
