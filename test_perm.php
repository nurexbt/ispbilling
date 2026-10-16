<?php
require_once 'c:/xampp/htdocs/billsoft/includes/db.php';
$res = $conn->query('SELECT * FROM role_tbl');
while($row = $res->fetch_assoc()){
    echo $row['role_name'] . ': ' . $row['permissions'] . "\n";
}
