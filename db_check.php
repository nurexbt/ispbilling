<?php
require_once 'includes/db.php';

$query = "SHOW TABLES";
$result = $conn->query($query);
while($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}
?>
