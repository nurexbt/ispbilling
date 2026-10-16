<?php
require 'includes/db.php';

$queries = [
    "ALTER TABLE payment_tbl ADD COLUMN processed_by VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE invoice_tbl ADD COLUMN processed_by VARCHAR(255) DEFAULT NULL"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . $conn->error . " ($q)\n";
    }
}
?>
