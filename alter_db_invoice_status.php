<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE invoice_tbl ADD COLUMN status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid' AFTER billing_month";

if ($conn->query($sql)) {
    echo "Column status added successfully to invoice_tbl.";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
