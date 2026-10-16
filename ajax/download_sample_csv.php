<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sample_customers.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, array(
    'ID', 'Name', 'NID', 'Phone', 'Email', 
    'Area', 'Subarea', 'Address', 
    'Router', 'Package', 'PPPoE Username', 'PPPoE Password', 'ONU MAC/SN',
    'Package Price', 'Discount', 'Connection Fee', 'Deposit', 'Advance Payment', 'Previous Due',
    'Connection Date', 'Billing Date', 'Billing Cycle Date', 'Paid Unpaid Status', 'Mikrotik Status', 'Status'
));
fputcsv($output, array(
    '', 'John Doe', '123456789', '01711000000', 'john@example.com', 
    'Mirpur', 'Mirpur 10', 'Road 1, House 2', 
    'Mikrotik-Main', 'Standard-5MB', 'john_doe', 'pass123', 'MAC-12345',
    '500', '0', '1000', '500', '0', '0',
    date('Y-m-d'), date('Y-m-d'), date('Y-m-d'), 'Paid', 'enable', 'Active'
));
fclose($output);
?>
