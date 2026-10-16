<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('portal_user_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);

    if (empty($employee_id) || empty($username) || empty($password) || empty($role_id)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if username already exists
    $check_query = "SELECT id FROM portal_user_tbl WHERE username = '$username'";
    $check_result = $conn->query($check_query);
    if ($check_result && $check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO portal_user_tbl (employee_id, username, password, role_id) VALUES ($employee_id, '$username', '$hashed_password', $role_id)";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Portal user created successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create user: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
