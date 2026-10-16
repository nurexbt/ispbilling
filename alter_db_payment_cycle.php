<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE customer_tbl ADD COLUMN payment_cycle_date DATE AFTER billing_date";

if ($conn->query($sql) === TRUE) {
    echo "Column payment_cycle_date added successfully\n";
    
    // Set payment_cycle_date equal to billing_date for existing customers
    $update_sql = "UPDATE customer_tbl SET payment_cycle_date = billing_date";
    if ($conn->query($update_sql) === TRUE) {
        echo "Existing customers updated successfully\n";
    } else {
        echo "Error updating existing customers: " . $conn->error . "\n";
    }
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
