<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE payment_tbl ADD COLUMN for_month VARCHAR(20) NULL DEFAULT NULL AFTER payment_method";

if ($conn->query($sql)) {
    echo "Column for_month added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
