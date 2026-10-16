<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE customer_tbl ADD COLUMN nid_no VARCHAR(100) NULL AFTER name";

if ($conn->query($sql) === TRUE) {
    echo "Column nid_no added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
