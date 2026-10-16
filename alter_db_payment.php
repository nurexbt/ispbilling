<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS payment_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_tbl(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "payment_tbl created successfully.";
} else {
    echo "Error creating payment_tbl: " . $conn->error;
}
?>
