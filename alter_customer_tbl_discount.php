<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE customer_tbl ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00 AFTER package_price";
if ($conn->query($sql) === TRUE) {
    echo "Column discount added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
