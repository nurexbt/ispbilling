<?php
$c1 = file_get_contents('manager/customer.php');
$c2 = file_get_contents('nocportal/customer.php');
$s1 = strpos($c1, '$(\'.filter-switch-checkbox\').on(\'change\'');
$e1 = strpos($c1, '// Router Status Async Check', $s1);
$s2 = strpos($c2, '$(\'.filter-switch-checkbox\').on(\'change\'');
$e2 = strpos($c2, '// Router Status Async Check', $s2);
$sub1 = substr($c1, $s1, $e1 - $s1);
$sub2 = substr($c2, $s2, $e2 - $s2);
if ($sub1 === $sub2) echo 'IDENTICAL JS'; else echo 'DIFFERENT JS';
?>
