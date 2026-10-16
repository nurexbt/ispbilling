<?php
$c = file_get_contents('manager/host.php');
$s = strpos($c, '<table');
$e = strpos($c, '</table>', $s);
echo substr($c, $s, $e - $s + 8);
?>
