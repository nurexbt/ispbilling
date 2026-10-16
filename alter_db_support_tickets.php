<?php
require_once 'includes/db.php';

echo "Starting Support Tickets DB Migration...\n";

// Table 1: ticket_category_tbl
$sql1 = "CREATE TABLE IF NOT EXISTS ticket_category_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql1)) {
    echo "Table ticket_category_tbl created/exists successfully.\n";
} else {
    echo "Error creating ticket_category_tbl: " . $conn->error . "\n";
}

// Table 2: support_ticket_tbl
$sql2 = "CREATE TABLE IF NOT EXISTS support_ticket_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    status ENUM('Created', 'On progress', 'Pending', 'Resolved') DEFAULT 'Created',
    progress_note TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES ticket_category_tbl(id) ON DELETE SET NULL
)";

if ($conn->query($sql2)) {
    echo "Table support_ticket_tbl created/exists successfully.\n";
} else {
    echo "Error creating support_ticket_tbl: " . $conn->error . "\n";
}

// Insert some default categories if none exist
$result = $conn->query("SELECT id FROM ticket_category_tbl LIMIT 1");
if ($result->num_rows == 0) {
    $defaults = ['Billing Issue', 'Technical Support', 'Feature Request', 'General Inquiry'];
    foreach ($defaults as $cat) {
        $stmt = $conn->prepare("INSERT INTO ticket_category_tbl (name) VALUES (?)");
        $stmt->bind_param("s", $cat);
        $stmt->execute();
    }
    echo "Default categories inserted.\n";
}

echo "Migration complete.\n";
?>
