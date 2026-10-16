<?php
$content = file_get_contents('nocportal/customer.php');
$start = strpos($content, 'id="filterOffcanvas"');
echo substr($content, $start - 100, 2500);
?>
