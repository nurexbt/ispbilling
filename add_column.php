<?php
require 'includes/db.php';
$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN IF NOT EXISTS onu_submitted_by VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE customer_tbl ADD COLUMN IF NOT EXISTS onu_submitted_by VARCHAR(100) DEFAULT NULL");
echo 'Done';
?>
