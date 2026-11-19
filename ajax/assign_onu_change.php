<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('customer_edit') && !has_permission('connection_request_assign') && !has_permission('connection_request_add')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: You do not have permission to assign technicians.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = intval($_POST['customer_id'] ?? 0);
$technician_id = intval($_POST['technician_id'] ?? 0);
$reason = trim($_POST['note'] ?? '');

if ($customer_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Valid Customer ID is required.']);
    exit;
}

if ($technician_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a technician to assign.']);
    exit;
}

// 1. Fetch Customer
$cust_stmt = $conn->prepare("SELECT id, name, phone, email, area_id, subarea_id, address, username, onu_sn, note FROM customer_tbl WHERE id = ? LIMIT 1");
$cust_stmt->bind_param("i", $customer_id);
$cust_stmt->execute();
$cust_res = $cust_stmt->get_result();
if (!$cust_res || $cust_res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found.']);
    exit;
}
$customer = $cust_res->fetch_assoc();
$cust_stmt->close();

// 2. Fetch Technician
$tech_stmt = $conn->prepare("SELECT id, full_name FROM employee_tbl WHERE id = ? LIMIT 1");
$tech_stmt->bind_param("i", $technician_id);
$tech_stmt->execute();
$tech_res = $tech_stmt->get_result();
if (!$tech_res || $tech_res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Technician not found.']);
    exit;
}
$technician = $tech_res->fetch_assoc();
$tech_stmt->close();

$tech_name = $technician['full_name'];
$cust_name = $customer['name'];
$cust_phone = $customer['phone'];
$cust_email = $customer['email'];
$cust_area = !empty($customer['area_id']) ? (int)$customer['area_id'] : null;
$cust_subarea = !empty($customer['subarea_id']) ? (int)$customer['subarea_id'] : null;
$cust_addr = $customer['address'];
$cust_onu = $customer['onu_sn'] ?: '';
$cust_user = $customer['username'] ?: '';

// Auto-migrate connection_request_tbl columns if missing
try {
    $chk_cr = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'customer_id'");
    if ($chk_cr && $chk_cr->num_rows === 0) {
        @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
    }
    $chk_rt = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'request_type'");
    if ($chk_rt && $chk_rt->num_rows === 0) {
        @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
    }
} catch (Throwable $e) {}

// 3. Check if there is an existing pending assignment for this customer
$chk_stmt = $conn->prepare("SELECT id, assigned_to FROM connection_request_tbl WHERE customer_id = ? AND request_type = 'onu_change' AND status = 'Assigned' LIMIT 1");
$chk_stmt->bind_param("i", $customer_id);
$chk_stmt->execute();
$chk_res = $chk_stmt->get_result();

$task_note = "ONU Change Request - Current ONU: " . ($cust_onu ?: 'None') . ($reason ? " (Reason: " . $reason . ")" : "");

if ($chk_res && $chk_res->num_rows > 0) {
    $existing = $chk_res->fetch_assoc();
    $existing_id = (int)$existing['id'];
    $chk_stmt->close();

    // Reassign existing task to selected technician
    $up_task = $conn->prepare("UPDATE connection_request_tbl SET assigned_to = ?, note = ? WHERE id = ?");
    $up_task->bind_param("isi", $technician_id, $task_note, $existing_id);
    $up_task->execute();
    $up_task->close();
    $request_id = $existing_id;
    $action_msg = "ONU change task reassigned to technician $tech_name.";
} else {
    if ($chk_stmt) $chk_stmt->close();
    // Insert new ONU Change Request
    $ins = $conn->prepare("INSERT INTO connection_request_tbl (name, email, phone, area_id, subarea_id, address, note, pppoe_username, customer_id, request_type, assigned_to, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'onu_change', ?, 'Assigned')");
    $ins->bind_param("sssiisssii", $cust_name, $cust_email, $cust_phone, $cust_area, $cust_subarea, $cust_addr, $task_note, $cust_user, $customer_id, $technician_id);
    if (!$ins->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create task: ' . $ins->error]);
        exit;
    }
    $request_id = $ins->insert_id;
    $ins->close();
    $action_msg = "ONU change task assigned to technician $tech_name successfully.";
}

// 4. Log in customer note
$assigned_by = function_exists('get_current_user_name') ? get_current_user_name($conn) : ($_SESSION['username'] ?? 'Admin');
$date_str = date('d M Y, h:ia');
$log_note = "[$date_str] Assigned to Technician $tech_name to change ONU (Current: " . ($cust_onu ?: 'None') . ($reason ? ", Reason: $reason" : "") . ") by $assigned_by";
$new_customer_note = !empty($customer['note']) ? $customer['note'] . "\n" . $log_note : $log_note;

$up_cust = $conn->prepare("UPDATE customer_tbl SET note = ? WHERE id = ?");
$up_cust->bind_param("si", $new_customer_note, $customer_id);
$up_cust->execute();
$up_cust->close();

echo json_encode([
    'status' => 'success',
    'message' => $action_msg,
    'request_id' => $request_id,
    'technician_id' => $technician_id,
    'technician_name' => $tech_name
]);
