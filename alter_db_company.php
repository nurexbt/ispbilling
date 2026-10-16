<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS company_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    website_url VARCHAR(255),
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Table company_tbl created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Check if a row exists, if not, insert a default one
$result = $conn->query("SELECT id FROM company_tbl LIMIT 1");
if ($result->num_rows == 0) {
    $insert = "INSERT INTO company_tbl (company_name) VALUES ('My Company')";
    $conn->query($insert);
    echo "Default company record inserted.\n";
}
?>
