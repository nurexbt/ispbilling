<?php
require_once 'includes/db.php';

echo "Adding customer_id to support_ticket_tbl...\n";

$sql = "ALTER TABLE support_ticket_tbl ADD COLUMN customer_id INT NULL DEFAULT NULL AFTER id";

if ($conn->query($sql)) {
    echo "Successfully added customer_id column.\n";
} else {
    echo "Error or already exists: " . $conn->error . "\n";
}

// Add foreign key if we want to be strict, but we can just leave it as INT for now
$sql_fk = "ALTER TABLE support_ticket_tbl ADD CONSTRAINT fk_ticket_customer FOREIGN KEY (customer_id) REFERENCES customer_tbl(id) ON DELETE SET NULL";
if ($conn->query($sql_fk)) {
    echo "Foreign key constraint added.\n";
} else {
    echo "FK Error or already exists: " . $conn->error . "\n";
}

echo "Migration complete.\n";
?>
