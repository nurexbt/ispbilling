<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('role_manager_full_access')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
    $role_id = intval($_POST['role_id']);
    $role_name = $conn->real_escape_string($_POST['role_name']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (empty($role_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Role name is required.']);
        exit;
    }

    $permissions_json = $conn->real_escape_string(json_encode($permissions));

    $query = "UPDATE role_tbl SET role_name = '$role_name', permissions = '$permissions_json' WHERE id = $role_id";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Role updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update role: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
