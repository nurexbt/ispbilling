<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('connection_request_assign')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['employee_id'])) {
    $request_id = intval($_POST['request_id']);
    $employee_id = intval($_POST['employee_id']);

    if (empty($employee_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select an employee.']);
        exit;
    }

    $update_query = "UPDATE connection_request_tbl SET assigned_to = $employee_id, status = 'Assigned' WHERE id = $request_id";

    if ($conn->query($update_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Request assigned successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign request.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
