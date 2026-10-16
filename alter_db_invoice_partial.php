<?php
require_once 'includes/db.php';

$sql1 = "ALTER TABLE invoice_tbl MODIFY COLUMN status ENUM('Unpaid', 'Paid', 'Partial') DEFAULT 'Unpaid'";
if ($conn->query($sql1)) {
    echo "Column status modified successfully.\n";
} else {
    echo "Error modifying status: " . $conn->error . "\n";
}

// Check if paid_amount exists before adding
$check = $conn->query("SHOW COLUMNS FROM invoice_tbl LIKE 'paid_amount'");
if ($check->num_rows == 0) {
    $sql2 = "ALTER TABLE invoice_tbl ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00 AFTER amount";
    if ($conn->query($sql2)) {
        echo "Column paid_amount added successfully.\n";
    } else {
        echo "Error adding paid_amount: " . $conn->error . "\n";
    }
} else {
    echo "Column paid_amount already exists.\n";
}
?>
