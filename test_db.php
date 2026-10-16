<?php
require_once 'includes/db.php';

$expenses_query = "
    SELECT e.id, e.created_by, pu.employee_id, emp.full_name as added_by_name 
    FROM expense_tbl e
    LEFT JOIN portal_user_tbl pu ON e.created_by = pu.id
    LEFT JOIN employee_tbl emp ON pu.employee_id = emp.id
";
$result = $conn->query($expenses_query);
while ($row = $result->fetch_assoc()) {
    print_r($row);
}