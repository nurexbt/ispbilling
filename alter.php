<?php
require 'includes/db.php';
$conn->query('ALTER TABLE invoice_tbl ADD COLUMN description VARCHAR(255) NULL AFTER amount');
echo "Done";
?>
