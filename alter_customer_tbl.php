<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE customer_tbl ADD COLUMN onu_sn VARCHAR(100) AFTER email";
if ($conn->query($sql) === TRUE) {
    echo "Column onu_sn added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
