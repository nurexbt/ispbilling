<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action_status = trim($_POST['status'] ?? ''); // 'Active' or 'Inactive'

if (!$customer_id || !in_array($action_status, ['Active', 'Inactive'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID or status value.']);
    exit;
}

// 1. Fetch customer and associated router details
$stmt = $conn->prepare("
    SELECT c.id, c.name, c.status, c.username, c.router_id, c.pppoe_status,
           h.ip_address, h.api_port, h.api_username, h.api_password 
    FROM customer_tbl c
    LEFT JOIN host_tbl h ON c.router_id = h.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Customer not found.']);
    exit;
}
$cust = $res->fetch_assoc();
$stmt->close();

$mikrotik_action_taken = false;
$mikrotik_msg = '';

// 2. When set to Inactive, disable PPPoE secret in MikroTik router and drop active sessions
if ($action_status === 'Inactive') {
    if (!empty($cust['username']) && !empty($cust['router_id']) && !empty($cust['ip_address'])) {
        require_once '../includes/routeros_api.class.php';
        
        $API = new RouterosAPI();
        $API->debug = false;
        $API->port = !empty($cust['api_port']) ? (int)$cust['api_port'] : 8728;
        $API->timeout = 3;
        $API->attempts = 1;

        if ($API->connect($cust['ip_address'], $cust['api_username'], $cust['api_password'])) {
            // Find the PPPoE secret by username
            $API->write('/ppp/secret/print', false);
            $API->write('?name=' . $cust['username'], true);
            $READ = $API->read(false);
            $ARRAY = $API->parseResponse($READ);

            if (!empty($ARRAY)) {
                $secret_id = $ARRAY[0]['.id'];

                // Update secret to disabled=yes
                $API->write('/ppp/secret/set', false);
                $API->write('=.id=' . $secret_id, false);
                $API->write('=disabled=yes', true);
                $READ = $API->read(false);
                $response = $API->parseResponse($READ);

                if (isset($response['!trap'])) {
                    $mikrotik_msg = "MikroTik error: " . ($response['!trap'][0]['message'] ?? 'Unknown error');
                } else {
                    $mikrotik_action_taken = true;
                    $mikrotik_msg = "PPPoE secret disabled in MikroTik router.";

                    // Remove active PPPoE session so customer is disconnected immediately
                    $API->write('/ppp/active/print', false);
                    $API->write('?name=' . $cust['username'], true);
                    $READ_ACT = $API->read(false);
                    $ARRAY_ACT = $API->parseResponse($READ_ACT);
                    if (!empty($ARRAY_ACT)) {
                        foreach ($ARRAY_ACT as $act) {
                            if (isset($act['.id'])) {
                                $API->write('/ppp/active/remove', false);
                                $API->write('=.id=' . $act['.id'], true);
                                $API->read();
                            }
                        }
                    }
                }
            } else {
                $mikrotik_msg = "PPPoE secret '{$cust['username']}' not found on router.";
            }

            $API->disconnect();
        } else {
            $mikrotik_msg = "Could not connect to MikroTik router ({$cust['ip_address']}:{$API->port}).";
        }
    } else {
        $mikrotik_msg = "No router or username assigned to customer.";
    }
}

// 3. Update customer_tbl status (and pppoe_status to 'disable' if Inactive)
if ($action_status === 'Inactive') {
    $up = $conn->prepare("UPDATE customer_tbl SET status = ?, pppoe_status = 'disable' WHERE id = ?");
} else {
    $up = $conn->prepare("UPDATE customer_tbl SET status = ? WHERE id = ?");
}
$up->bind_param("si", $action_status, $customer_id);

if ($up->execute()) {
    $up->close();
    
    $message = "Customer '{$cust['name']}' status updated to {$action_status}.";
    if ($action_status === 'Inactive') {
        if ($mikrotik_action_taken) {
            $message .= " PPPoE secret disabled in MikroTik router.";
        } elseif (!empty($mikrotik_msg)) {
            $message .= " Note: " . $mikrotik_msg;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'customer_id' => $customer_id,
        'customer_status' => $action_status,
        'pppoe_status' => ($action_status === 'Inactive') ? 'disable' : ($cust['pppoe_status'] ?? 'enable'),
        'mikrotik_action' => $mikrotik_action_taken,
        'message' => $message
    ]);
} else {
    $err = $up->error;
    $up->close();
    echo json_encode(['status' => 'error', 'message' => 'Database error while updating status: ' . $err]);
}
?>

