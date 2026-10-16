<?php
require 'includes/db.php';
$res = $conn->query("DESCRIBE expense_tbl");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
