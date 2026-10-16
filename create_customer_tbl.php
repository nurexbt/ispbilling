<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS customer_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    area_id INT,
    subarea_id INT,
    address TEXT,
    router_id INT,
    package_id INT,
    username VARCHAR(100),
    password VARCHAR(100),
    package_price DECIMAL(10,2) DEFAULT 0.00,
    connection_date DATE,
    billing_date DATE,
    connection_fee DECIMAL(10,2) DEFAULT 0.00,
    deposit DECIMAL(10,2) DEFAULT 0.00,
    advance_payment DECIMAL(10,2) DEFAULT 0.00,
    pppoe_status ENUM('enable', 'disable') DEFAULT 'enable',
    note TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES area_tbl(id) ON DELETE SET NULL,
    FOREIGN KEY (subarea_id) REFERENCES subarea_tbl(id) ON DELETE SET NULL,
    FOREIGN KEY (router_id) REFERENCES host_tbl(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES package_tbl(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table customer_tbl created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
