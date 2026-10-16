<?php
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
        exit;
    }

    $query = "SELECT pu.*, r.role_name, r.permissions 
              FROM portal_user_tbl pu 
              LEFT JOIN role_tbl r ON pu.role_id = r.id 
              WHERE pu.username = '$username'";
              
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_active'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Account is disabled. Contact administrator.']);
            exit;
        }

        if (password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['permissions'] = $user['permissions'];
            $permissions_arr = json_decode($user['permissions'], true) ?? [];
            
            $redirect_url = 'index.php';
            if (strtolower($user['role_name']) === 'sysadmin' || isset($permissions_arr['global_full_access'])) {
                $redirect_url = 'index.php';
            } elseif (isset($permissions_arr['owner_full_access']) || strtolower($user['role_name']) === 'owner') {
                $redirect_url = 'owner/index.php';
            } elseif (isset($permissions_arr['noc_full_access'])) {
                $redirect_url = 'nocportal/index.php';
            } elseif (isset($permissions_arr['transmission_full_access'])) {
                $redirect_url = 'transmission/index.php';
            } elseif (isset($permissions_arr['manager_full_access'])) {
                $redirect_url = 'manager/index.php';
            } elseif (isset($permissions_arr['billing_full_access'])) {
                $redirect_url = 'billingportal/index.php';
            } elseif (isset($permissions_arr['txbill_full_access'])) {
                $redirect_url = 'txbill/index.php';
            }
            
            echo json_encode(['status' => 'success', 'redirect' => $redirect_url]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
