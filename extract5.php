<?php
$content = file_get_contents('nocportal/customer.php');
$start = strpos($content, 'id="filterOffcanvas"');
if ($start !== false) {
    $end = strpos($content, '</form>', $start);
    echo substr($content, $start, $end - $start + 7);
}
?>
