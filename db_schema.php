<?php
require 'includes/db.php';
$res=$conn->query("SHOW COLUMNS FROM employee_tbl");
while($r=$res->fetch_assoc()){ echo $r['Field']." "; }
echo "\n";
$res=$conn->query("SHOW COLUMNS FROM portal_user_tbl");
while($r=$res->fetch_assoc()){ echo $r['Field']." "; }
?>
