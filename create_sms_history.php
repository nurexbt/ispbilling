<?php
require 'includes/db.php';
$sql = "
CREATE TABLE IF NOT EXISTS sms_history_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_number VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    api_response TEXT,
    sent_by INT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($conn->query($sql) === TRUE) {
    echo "SUCCESS\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
?>
