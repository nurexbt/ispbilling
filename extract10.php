<?php
$c = file_get_contents('manager/customer.php');
$s = strpos($c, '<thead');
$e = strpos($c, '</thead>', $s);
echo substr($c, $s, $e - $s);
?>
