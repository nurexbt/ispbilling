<?php
require 'includes/db.php';
// Update existing connection_request_tbl onu_submitted_by if it's null using assigned_to
$conn->query("UPDATE connection_request_tbl cr LEFT JOIN employee_tbl e ON cr.assigned_to = e.id SET cr.onu_submitted_by = e.full_name WHERE cr.onu_submitted_by IS NULL AND cr.assigned_to IS NOT NULL AND cr.assigned_to > 0");
// Update customer_tbl using connection_request_tbl data matching ONU SN
$res = $conn->query("UPDATE customer_tbl c JOIN connection_request_tbl cr ON c.onu_sn = cr.onu_sn SET c.onu_submitted_by = cr.onu_submitted_by WHERE c.onu_submitted_by IS NULL AND c.onu_sn != '' AND c.onu_sn IS NOT NULL");
echo "Updated " . $conn->affected_rows . " existing customers based on ONU SN matches.\n";
?>
