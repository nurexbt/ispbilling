<?php
require_once 'includes/db.php';

$sql1 = "ALTER TABLE customer_tbl ADD COLUMN is_existing_customer TINYINT(1) DEFAULT 0 AFTER onu_sn";
$sql2 = "ALTER TABLE customer_tbl ADD COLUMN previous_due DECIMAL(10,2) DEFAULT 0.00 AFTER is_existing_customer";

if ($conn->query($sql1) === TRUE) {
    echo "Column is_existing_customer added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

if ($conn->query($sql2) === TRUE) {
    echo "Column previous_due added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
