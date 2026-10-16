<?php
require 'includes/db.php';

$sql1 = "ALTER TABLE invoice_tbl ADD COLUMN discount decimal(10,2) DEFAULT 0.00 AFTER description";
$sql2 = "ALTER TABLE payment_tbl ADD COLUMN discount decimal(10,2) DEFAULT 0.00 AFTER amount";

if ($conn->query($sql1) === TRUE) {
    echo "Column discount added to invoice_tbl successfully\n";
} else {
    echo "Error adding column to invoice_tbl: " . $conn->error . "\n";
}

if ($conn->query($sql2) === TRUE) {
    echo "Column discount added to payment_tbl successfully\n";
} else {
    echo "Error adding column to payment_tbl: " . $conn->error . "\n";
}

$conn->close();
?>
