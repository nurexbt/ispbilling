<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('role_manager_full_access')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = $conn->real_escape_string($_POST['role_name']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (empty($role_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Role name is required.']);
        exit;
    }

    $permissions_json = $conn->real_escape_string(json_encode($permissions));

    $query = "INSERT INTO role_tbl (role_name, permissions) VALUES ('$role_name', '$permissions_json')";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Role created successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create role: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
