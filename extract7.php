<?php
$lines = file('manager/customer.php');
for($i=640; $i<690; $i++) echo str_pad($i+1, 4, ' ', STR_PAD_LEFT) . ': ' . $lines[$i];
?>
