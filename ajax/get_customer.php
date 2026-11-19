<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$customer_id = intval($_GET['id'] ?? 0);

if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
}

// 1. Fetch customer details safely
try {
    $stmt = $conn->prepare("SELECT * FROM customer_tbl WHERE id = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        exit;
    }

    $customer = $res->fetch_assoc();
    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch customer: ' . $e->getMessage()]);
    exit;
}

// 2. Auto-migrate connection_request_tbl columns if missing (e.g. fresh aaPanel deploy)
try {
    $chk_cr = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'customer_id'");
    if ($chk_cr && $chk_cr->num_rows === 0) {
        @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
    }
    $chk_rt = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'request_type'");
    if ($chk_rt && $chk_rt->num_rows === 0) {
        @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
    }
} catch (Throwable $e) {
    // Suppress to ensure customer data is always returned
}

// 3. Check for active pending ONU change task safely
$customer['pending_onu_change'] = null;
try {
    $pending_req_stmt = $conn->prepare("SELECT cr.id, cr.assigned_to, e.full_name as technician_name, cr.status, cr.created_at 
                                         FROM connection_request_tbl cr 
                                         LEFT JOIN employee_tbl e ON cr.assigned_to = e.id 
                                         WHERE cr.customer_id = ? AND cr.request_type = 'onu_change' AND cr.status = 'Assigned' LIMIT 1");
    if ($pending_req_stmt) {
        $pending_req_stmt->bind_param("i", $customer_id);
        $pending_req_stmt->execute();
        $pending_res = $pending_req_stmt->get_result();
        if ($pending_res && $pending_res->num_rows > 0) {
            $customer['pending_onu_change'] = $pending_res->fetch_assoc();
        }
        $pending_req_stmt->close();
    }
} catch (Throwable $e) {
    $customer['pending_onu_change'] = null;
}

echo json_encode([
    'status' => 'success',
    'data' => $customer
]);
exit;
