<?php
require_once 'includes/db.php';
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS pppoe_username VARCHAR(100) DEFAULT NULL AFTER onu_sn");
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS onu_submitted_by VARCHAR(100) DEFAULT NULL AFTER onu_sn");

$conn->query("ALTER TABLE customer_tbl ADD COLUMN IF NOT EXISTS onu_submitted_by VARCHAR(100) DEFAULT NULL AFTER onu_sn");

$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS return_reason VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS replace_new_id VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS used_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl ADD COLUMN IF NOT EXISTS returned_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE store_tbl MODIFY COLUMN delivery_status ENUM('pending','accepted','rejected','used') DEFAULT 'pending'");

echo $conn->error ? 'Error: ' . $conn->error : '<b style="color:green">Done: all migrations applied successfully</b>';
$conn->close();
?>