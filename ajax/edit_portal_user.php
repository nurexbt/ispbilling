<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('portal_user_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);

    if (empty($username) || empty($role_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and role are required.']);
        exit;
    }

    // Check if username already exists for a different user
    $check_query = "SELECT id FROM portal_user_tbl WHERE username = '$username' AND id != $user_id";
    $check_result = $conn->query($check_query);
    if ($check_result && $check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
        exit;
    }

    $update_query = "UPDATE portal_user_tbl SET username = '$username', role_id = $role_id";

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_query .= ", password = '$hashed_password'";
    }

    $update_query .= " WHERE id = $user_id";

    if ($conn->query($update_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Portal user updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
