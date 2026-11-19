<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$query = "SELECT e.id, e.full_name, r.role_name, e.designation 
          FROM employee_tbl e 
          LEFT JOIN portal_user_tbl pu ON pu.employee_id = e.id 
          LEFT JOIN role_tbl r ON pu.role_id = r.id 
          WHERE pu.is_active = 1 OR pu.id IS NOT NULL 
          ORDER BY 
            CASE WHEN r.role_name LIKE '%Technician%' OR r.role_name LIKE '%Tx%' OR e.designation LIKE '%Technician%' THEN 0 ELSE 1 END, 
            e.full_name ASC";
$result = $conn->query($query);
$technicians = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $display = $row['full_name'];
        if (!empty($row['role_name'])) {
            $display .= ' (' . $row['role_name'] . ')';
        } elseif (!empty($row['designation'])) {
            $display .= ' (' . $row['designation'] . ')';
        }
        $technicians[] = [
            'id' => (int)$row['id'],
            'name' => $row['full_name'],
            'display' => $display,
            'role' => $row['role_name'] ?? '',
            'designation' => $row['designation'] ?? ''
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $technicians
]);
