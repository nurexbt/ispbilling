<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS khanerp";
if ($conn->query($sql) === TRUE) {
    // Database created or exists
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db('khanerp');

// Create table
$sql = "CREATE TABLE IF NOT EXISTS routers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    ip_address VARCHAR(50) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    api_port INT(5) NOT NULL DEFAULT 8728,
    status ENUM('enabled', 'disabled') DEFAULT 'enabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

$conn->close();
echo "Database and table setup completed successfully.";
?>