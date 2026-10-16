<?php
$c = file_get_contents('nocportal/customer.php');
$s = strpos($c, '<!-- Toolbar -->');
$e = strpos($c, '<table id="customerTable"', $s);
echo substr($c, $s, $e - $s);
?>
