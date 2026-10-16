<?php
require 'includes/db.php';
if ($conn->query("ALTER TABLE sms_history_tbl ADD COLUMN bulk_numbers TEXT NULL DEFAULT NULL")) {
    echo "Success";
} else {
    echo "Error: " . $conn->error;
}
?>
