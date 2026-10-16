<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS invoice_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    billing_month VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_tbl(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "invoice_tbl created successfully.";
} else {
    echo "Error creating invoice_tbl: " . $conn->error;
}
?>
