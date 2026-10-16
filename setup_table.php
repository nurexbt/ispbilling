<?php
require_once 'includes/db.php';
$sql = "CREATE TABLE IF NOT EXISTS collector_handover_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processor_name VARCHAR(100),
    amount DECIMAL(10,2),
    handover_date DATETIME,
    received_by VARCHAR(100)
);";
if($conn->query($sql)){
    echo "Table created successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
