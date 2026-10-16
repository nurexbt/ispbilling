<?php
$content = file_get_contents('manager/customer.php');
$start = strpos($content, 'id="filterOffcanvas"');
if ($start !== false) {
    $end = strpos($content, '</form>', $start);
    echo substr($content, $start, $end - $start + 7);
}
?>
