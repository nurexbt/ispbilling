<?php
$lines = file('manager/customer.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'filterOffcanvas') !== false) {
        echo "Line " . ($i + 1) . ": " . htmlspecialchars($line) . "\n";
    }
}
?>
