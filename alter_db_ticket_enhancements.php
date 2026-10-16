<?php
require_once 'includes/db.php';

echo "Starting Support Tickets Enhancements DB Migration...\n";

// 1. Alter support_ticket_tbl: Modify status ENUM and add priority
// Since ENUMs can be tricky to alter if data exists that doesn't match the new ENUM,
// we first change to VARCHAR, update data, then change back to ENUM. Or just alter directly.
// The user said: Pending, In Progress, Review, Completed, Cancelled.

// Alter status to new ENUM
$sql1 = "ALTER TABLE support_ticket_tbl MODIFY status ENUM('Pending', 'In Progress', 'Review', 'Completed', 'Cancelled') DEFAULT 'Pending'";
if ($conn->query($sql1)) {
    echo "Status ENUM updated successfully.\n";
} else {
    echo "Error updating status ENUM: " . $conn->error . "\n";
}

// Add priority
$sql2 = "ALTER TABLE support_ticket_tbl ADD COLUMN priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium' AFTER category_id";
if ($conn->query($sql2)) {
    echo "Priority column added successfully.\n";
} else {
    echo "Error adding priority (or already exists): " . $conn->error . "\n";
}

// 2. Create ticket_attachment_tbl
$sql3 = "CREATE TABLE IF NOT EXISTS ticket_attachment_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_ticket_tbl(id) ON DELETE CASCADE
)";

if ($conn->query($sql3)) {
    echo "Table ticket_attachment_tbl created/exists successfully.\n";
} else {
    echo "Error creating ticket_attachment_tbl: " . $conn->error . "\n";
}

echo "Migration complete.\n";
?>
