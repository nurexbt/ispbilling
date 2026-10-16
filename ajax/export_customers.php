<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


// Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array(
    'ID', 'Name', 'NID', 'Phone', 'Email', 
    'Area', 'Subarea', 'Address', 
    'Router', 'Package', 'PPPoE Username', 'PPPoE Password', 'ONU MAC/SN',
    'Package Price', 'Discount', 'Connection Fee', 'Deposit', 'Advance Payment', 'Previous Due',
    'Connection Date', 'Billing Date', 'Billing Cycle Date', 'Paid Unpaid Status', 'Mikrotik Status', 'Status'
));

// Fetch all customers with their relations
$query = "SELECT c.*, a.area_name, s.subarea_name, h.device_name as router_name, p.given_name as package_name 
          FROM customer_tbl c
          LEFT JOIN area_tbl a ON c.area_id = a.id
          LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
          LEFT JOIN host_tbl h ON c.router_id = h.id
          LEFT JOIN package_tbl p ON c.package_id = p.id
          ORDER BY c.id DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['name'],
            $row['nid_no'],
            $row['phone'],
            $row['email'],
            $row['area_name'],
            $row['subarea_name'],
            $row['address'],
            $row['router_name'],
            $row['package_name'],
            $row['username'],
            $row['password'],
            $row['onu_sn'],
            $row['package_price'],
            $row['discount'],
            $row['connection_fee'],
            $row['deposit'],
            $row['advance_payment'],
            $row['previous_due'],
            !empty($row['connection_date']) ? date('Y-m-d', strtotime($row['connection_date'])) : '',
            !empty($row['billing_date']) ? date('Y-m-d', strtotime($row['billing_date'])) : '',
            !empty($row['payment_cycle_date']) ? date('Y-m-d', strtotime($row['payment_cycle_date'])) : '',
            ((float)$row['previous_due'] > 0 ? 'Unpaid' : 'Paid'),
            $row['pppoe_status'] ? $row['pppoe_status'] : 'enable',
            $row['status']
        ));
    }
}

fclose($output);
exit();
?>
