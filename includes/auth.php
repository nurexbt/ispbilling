<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = isset($base_path) ? $base_path : '';

// Function to get the current user's full name
function get_current_user_name($conn) {
    if (isset($_SESSION['name'])) {
        return $_SESSION['name'];
    }
    
    if (isset($_SESSION['employee_id']) && $_SESSION['employee_id'] > 0) {
        $emp_id = (int)$_SESSION['employee_id'];
        $res = $conn->query("SELECT full_name FROM employee_tbl WHERE id = $emp_id");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $_SESSION['name'] = $row['full_name'];
            return $row['full_name'];
        }
    }
    
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    
    return 'System';
}

// Function to check if user has a specific permission
function has_permission($permission_key) {
    if (isset($_SESSION['role_name']) && (strtolower($_SESSION['role_name']) === 'sysadmin' || strtolower($_SESSION['role_name']) === 'owner')) {
        return true;
    }
    
    global $is_manager, $is_owner;
    if (!empty($is_manager) && isset($_SESSION['role_name']) && strtolower($_SESSION['role_name']) === 'manager') {
        return true;
    }
    if (!empty($is_owner) && isset($_SESSION['role_name']) && strtolower($_SESSION['role_name']) === 'owner') {
        return true;
    }
    
    if (!isset($_SESSION['permissions'])) {
        return false;
    }
    
    $permissions = $_SESSION['permissions'];
    if (is_string($permissions)) {
        $permissions = json_decode($permissions, true);
    }
    
    if (isset($permissions['owner_full_access']) && ($permissions['owner_full_access'] == 1 || $permissions['owner_full_access'] === true)) {
        return true;
    }
    
    if (isset($permissions['billing_full_access']) && ($permissions['billing_full_access'] == 1 || $permissions['billing_full_access'] === true)) {
        $billing_perms = [
            'billing_full_access',
            'dashboard_view',
            'customer_view', 'customer_add', 'customer_edit', 'customer_delete', 'customer_ledger',
            'amount_collect_view',
            'expense_view', 'expense_add', 'expense_edit', 'expense_delete',
            'support_ticket_view', 'support_ticket_add', 'support_ticket_update'
        ];
        if (in_array($permission_key, $billing_perms)) {
            return true;
        }
    }

    if (isset($permissions['transmission_full_access']) && ($permissions['transmission_full_access'] == 1 || $permissions['transmission_full_access'] === true)) {
        $transmission_perms = [
            'transmission_full_access',
            'dashboard_view',
            'customer_view', 'customer_add', 'customer_edit', 'customer_delete', 'customer_ledger',
            'amount_collect_view',
            'expense_view', 'expense_add', 'expense_edit', 'expense_delete',
            'support_ticket_view', 'support_ticket_add', 'support_ticket_update'
        ];
        if (in_array($permission_key, $transmission_perms)) {
            return true;
        }
    }

    if (isset($permissions['txbill_full_access']) && ($permissions['txbill_full_access'] == 1 || $permissions['txbill_full_access'] === true)) {
        $txbill_perms = [
            'txbill_full_access',
            'dashboard_view',
            'customer_view', 'customer_add', 'customer_edit', 'customer_delete', 'customer_ledger',
            'amount_collect_view',
            'expense_view', 'expense_add', 'expense_edit', 'expense_delete',
            'support_ticket_view', 'support_ticket_add', 'support_ticket_update'
        ];
        if (in_array($permission_key, $txbill_perms)) {
            return true;
        }
    }


    if (isset($permissions['noc_full_access']) && ($permissions['noc_full_access'] == 1 || $permissions['noc_full_access'] === true)) {
        $noc_perms = [
            'noc_full_access',
            'dashboard_view',
            'customer_view', 'customer_add', 'customer_edit', 'customer_delete', 'customer_ledger',
            'amount_collect_view',
            'expense_view', 'expense_add', 'expense_edit', 'expense_delete',
            'support_ticket_view', 'support_ticket_add', 'support_ticket_update',
            'host_view', 'host_add', 'host_edit', 'host_delete',
            'package_view', 'package_add', 'package_edit', 'package_delete',
            'area_view', 'area_add', 'area_edit', 'area_delete',
            'payment_view', 'payment_record_view', 'store_view',
            'store_record_add_item_category', 'store_inventory_add', 'store_inventory_delete', 'store_inventory_deliver', 'store_inventory_return',
            'connection_request_view', 'connection_request_add', 'connection_request_edit', 'connection_request_delete', 'connection_request_assign', 'connection_request_add_client'
        ];
        if (in_array($permission_key, $noc_perms)) {
            return true;
        }
    }
    
    return isset($permissions[$permission_key]) && ($permissions[$permission_key] == 1 || $permissions[$permission_key] === true);
}

// Redirect to login if not logged in
$current_script = basename($_SERVER['SCRIPT_NAME']);
$allowed_pages = ['login.php', 'login_action.php'];

if ($current_script == 'login.php' && isset($_SESSION['user_id'])) {
    if (has_permission('owner_full_access') || (isset($_SESSION['role_name']) && strtolower($_SESSION['role_name']) === 'owner')) {
        header("Location: " . $base_path . "owner/index.php");
    } elseif (has_permission('noc_full_access')) {
        header("Location: " . $base_path . "nocportal/index.php");
    } elseif (has_permission('transmission_full_access')) {
        header("Location: " . $base_path . "transmission/index.php");
    } elseif (has_permission('manager_full_access')) {
        header("Location: " . $base_path . "manager/index.php");
    } elseif (has_permission('billing_full_access')) {
        header("Location: " . $base_path . "billingportal/index.php");
    } else {
        header("Location: " . $base_path . "index.php");
    }
    exit;
}

if (!in_array($current_script, $allowed_pages)) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $base_path . "login.php");
        exit;
    }
}

if (strpos($_SERVER['SCRIPT_NAME'], '/owner/') !== false) {
    if (!has_permission('owner_full_access') && !(isset($_SESSION['role_name']) && in_array(strtolower($_SESSION['role_name']), ['owner', 'sysadmin']))) {
        header("Location: " . $base_path . "index.php");
        exit;
    }
}

if ($current_script == 'index.php') {
    if (strpos($_SERVER['SCRIPT_NAME'], '/owner/') === false && strpos($_SERVER['SCRIPT_NAME'], '/manager/') === false && strpos($_SERVER['SCRIPT_NAME'], '/transmission/') === false && strpos($_SERVER['SCRIPT_NAME'], '/billingportal/') === false && strpos($_SERVER['SCRIPT_NAME'], '/defaultportal/') === false && strpos($_SERVER['SCRIPT_NAME'], '/nocportal/') === false) {
        if ((has_permission('owner_full_access') || (isset($_SESSION['role_name']) && strtolower($_SESSION['role_name']) === 'owner')) && !has_permission('dashboard_view')) {
            header("Location: " . $base_path . "owner/index.php");
            exit;
        } elseif (has_permission('noc_full_access') && !has_permission('dashboard_view')) {
            header("Location: " . $base_path . "nocportal/index.php");
            exit;
        } elseif (has_permission('transmission_full_access') && !has_permission('dashboard_view')) {
            header("Location: " . $base_path . "transmission/index.php");
            exit;
        } elseif (has_permission('manager_full_access') && !has_permission('dashboard_view')) {
            header("Location: " . $base_path . "manager/index.php");
            exit;
        } elseif (has_permission('billing_full_access') && !has_permission('dashboard_view')) {
            header("Location: " . $base_path . "billingportal/index.php");
            exit;
        }
    }
}

// Auto-check and ensure all required schema columns exist across tables (zero downtime on server deploy)
if (isset($conn) && $conn instanceof mysqli && empty($_SESSION['__schema_verified_v2'])) {
    try {
        // connection_request_tbl
        $chk_cr = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'customer_id'");
        if ($chk_cr && $chk_cr->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
        }
        $chk_rt = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'request_type'");
        if ($chk_rt && $chk_rt->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
        }
        $chk_os = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'onu_submitted_by'");
        if ($chk_os && $chk_os->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN onu_submitted_by VARCHAR(100) DEFAULT NULL AFTER onu_sn");
        }
        $chk_pu = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'pppoe_username'");
        if ($chk_pu && $chk_pu->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN pppoe_username VARCHAR(100) DEFAULT NULL AFTER onu_sn");
        }

        // customer_tbl
        $chk_cos = $conn->query("SHOW COLUMNS FROM customer_tbl LIKE 'onu_submitted_by'");
        if ($chk_cos && $chk_cos->num_rows === 0) {
            @$conn->query("ALTER TABLE customer_tbl ADD COLUMN onu_submitted_by VARCHAR(100) DEFAULT NULL AFTER onu_sn");
        }

        // store_tbl
        $chk_rr = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'return_reason'");
        if ($chk_rr && $chk_rr->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN return_reason VARCHAR(255) DEFAULT NULL");
        }
        $chk_rn = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'replace_new_id'");
        if ($chk_rn && $chk_rn->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN replace_new_id VARCHAR(100) DEFAULT NULL");
        }
        $chk_da = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'delivered_at'");
        if ($chk_da && $chk_da->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN delivered_at TIMESTAMP NULL DEFAULT NULL");
        }
        $chk_aa = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'approved_at'");
        if ($chk_aa && $chk_aa->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL");
        }
        $chk_ua = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'used_at'");
        if ($chk_ua && $chk_ua->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN used_at TIMESTAMP NULL DEFAULT NULL");
        }
        $chk_ra = $conn->query("SHOW COLUMNS FROM store_tbl LIKE 'returned_at'");
        if ($chk_ra && $chk_ra->num_rows === 0) {
            @$conn->query("ALTER TABLE store_tbl ADD COLUMN returned_at TIMESTAMP NULL DEFAULT NULL");
        }
        @$conn->query("ALTER TABLE store_tbl MODIFY COLUMN delivery_status ENUM('pending','accepted','rejected','used') DEFAULT 'pending'");

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['__schema_verified_v2'] = true;
        }
    } catch (Throwable $e) {
        // Suppress to ensure requests continue smoothly
    }
}
?>
