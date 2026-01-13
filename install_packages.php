<?php
include 'includes/db.php';

// Create packages table
$sql = "CREATE TABLE IF NOT EXISTS packages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    router_id INT(6) UNSIGNED,
    original_name VARCHAR(255) NOT NULL,
    given_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'enabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'packages' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>