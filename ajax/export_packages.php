<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="packages_' . date('Y-m-d_H-i-s') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, array('ID', 'Given Name', 'Original PPP Profile', 'Router Name', 'Price'));

$query = "SELECT p.*, h.device_name 
          FROM package_tbl p 
          LEFT JOIN host_tbl h ON p.host_id = h.id 
          ORDER BY p.id DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['given_name'],
            $row['original_profile'],
            $row['device_name'],
            $row['price']
        ));
    }
}

fclose($output);
exit();
?>
