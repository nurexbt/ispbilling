<?php
$content = file_get_contents('manager/customer.php');
$start = strpos($content, 'id="filterOffcanvas"');
if ($start !== false) {
    echo substr($content, $start - 100, 3000);
} else {
    echo "Not found.";
}
?>
