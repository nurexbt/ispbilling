<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE customer_tbl ADD COLUMN is_special_user TINYINT(1) DEFAULT 0 AFTER status";

if ($conn->query($sql) === TRUE) {
    echo "Column is_special_user added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
