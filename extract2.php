<?php
$content = file_get_contents('nocportal/customer.php');
$start = strpos($content, '$.fn.dataTable.ext.search.push');
if ($start !== false) {
    $script_start = strrpos(substr($content, 0, $start), '<script');
    $script_end = strpos($content, '</script>', $start);
    echo substr($content, $script_start, $script_end - $script_start + 9);
} else {
    echo "Not found.";
}
?>
