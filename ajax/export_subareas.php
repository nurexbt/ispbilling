<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subareas_' . date('Y-m-d_H-i-s') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Sub Area Name', 'Parent Area Name'));

$query = "SELECT s.*, a.area_name FROM subarea_tbl s JOIN area_tbl a ON s.area_id = a.id ORDER BY s.id DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['subarea_name'],
            $row['area_name']
        ));
    }
}
fclose($output);
exit();
?>
