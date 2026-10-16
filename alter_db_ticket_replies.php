<?php
require_once 'includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS ticket_reply_tbl (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    note TEXT NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_ticket_tbl(id) ON DELETE CASCADE
);
";

if ($conn->query($sql)) {
    echo "ticket_reply_tbl created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
