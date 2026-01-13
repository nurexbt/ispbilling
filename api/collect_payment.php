<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$data = $_POST;
if (empty($data)) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
}

$user_id = $data['user_id'] ?? 0;
$invoice_id = $data['invoice_id'] ?? 0;
$amount = $data['amount'] ?? 0;
$notes = $data['notes'] ?? '';
$collected_by = 'System'; // In a real app, this would be from session

// DEBUG LOGGING
$log_file = '../payment_debug.txt';
$log_msg = "\n[" . date('Y-m-d H:i:s') . "] Request received. User ID: $user_id, Invoice ID: $invoice_id, Amount: $amount\n";
file_put_contents($log_file, $log_msg, FILE_APPEND);

if (!$user_id || $amount <= 0) {
    file_put_contents($log_file, "Invalid input\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// 1. Fetch User & Router Info
$sql = "SELECT p.*, r.ip_address, r.username as r_user, r.password as r_pass, r.api_port 
        FROM pppoe_users p 
        LEFT JOIN routers r ON p.router_id = r.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    file_put_contents($log_file, "User not found\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

file_put_contents($log_file, "User found: " . $user['username'] . ". Current Status: " . $user['status'] . "\n", FILE_APPEND);

// 2. Insert Payment Record
$stmt = $conn->prepare("INSERT INTO pppoe_payments (pppoe_user_id, amount, payment_date, added_by, notes) VALUES (?, ?, NOW(), ?, ?)");
$stmt->bind_param("idss", $user_id, $amount, $collected_by, $notes);
if (!$stmt->execute()) {
    file_put_contents($log_file, "DB Error: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database Error']);
    exit;
}
$payment_id = $stmt->insert_id;
$stmt->close();

// 3. Update Invoice Status (Link to Payment)
if ($invoice_id) {
    // Pay specific invoice
    $stmt = $conn->prepare("UPDATE pppoe_billings SET status = 'paid', payment_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $payment_id, $invoice_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Auto-match oldest unpaid invoice
    // Pay as many unpaid invoices as the amount covers?
    // For now, sticking to the existing logic of paying the oldest one or spreading?
    // The user just wants "if due is clear". 
    // Let's try to pay off invoices with the amount.
    $stmt = $conn->prepare("SELECT id, amount FROM pppoe_billings WHERE user_id = ? AND status = 'unpaid' ORDER BY bill_date ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $remaining_payment = $amount;
    
    while ($row = $res->fetch_assoc()) {
        if ($remaining_payment <= 0) break;
        
        $inv_id = $row['id'];
        $inv_amount = $row['amount'];
        
        // If payment covers this invoice fully (or we just mark it paid? Usually partial payment logic is complex)
        // For simple ISP billing, we usually mark 'paid' if they pay the bill amount.
        // If they pay partial, it's tricky. 
        // Let's assume for this request: If they pay, we try to mark invoices as paid.
        
        // Improved logic: Mark as paid if remaining payment >= invoice amount
        if ($remaining_payment >= $inv_amount) {
            $upd = $conn->prepare("UPDATE pppoe_billings SET status = 'paid', payment_id = ? WHERE id = ?");
            $upd->bind_param("ii", $payment_id, $inv_id);
            $upd->execute();
            $upd->close();
            $remaining_payment -= $inv_amount;
        } else {
            // Partial payment on an invoice? 
            // The system seems to lack partial invoice support (status is just 'paid'/'unpaid').
            // Let's leave it as unpaid if not fully covered, OR mark paid?
            // User requirement: "if due is clear". This implies strict due checking.
            // So we shouldn't mark as paid if not fully paid.
        }
    }
    $stmt->close();
}

// 4. Check Remaining Due
$stmt = $conn->prepare("SELECT SUM(amount) as total_due FROM pppoe_billings WHERE user_id = ? AND status = 'unpaid'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$due_result = $stmt->get_result();
$due_row = $due_result->fetch_assoc();
$total_due = $due_row['total_due'] ?? 0;
$stmt->close();

$router_updated = false;
$new_billing_date = $user['billing_date']; // Default to old date

// ONLY Enable/Extend if Due is Clear (<= 0)
// Note: floating point comparison safety
if ($total_due <= 0.01) { 
    // 5. Calculate New Billing Date
    $current_billing_date = $user['billing_date'];
    
    $today = new DateTime();
    try {
        $b_date = (!empty($current_billing_date) && $current_billing_date != '0000-00-00') 
                  ? new DateTime($current_billing_date) 
                  : new DateTime('-1 day'); // Treat as expired
    } catch (Exception $e) {
        $b_date = new DateTime('-1 day');
    }

    if ($today > $b_date) {
        // Expired: Extend from TODAY to next month (standard 10th cycle logic)
        $day = (int)$today->format('d');
        if ($day <= 10) {
            $new_billing_date = $today->format('Y-m-10');
        } else {
            $today->modify('first day of next month');
            $new_billing_date = $today->format('Y-m-10');
        }
        // Ensure we give at least ~1 month if paying today
        if ($new_billing_date <= date('Y-m-d')) {
             $d = new DateTime($new_billing_date);
             $d->modify('+1 month');
             $new_billing_date = $d->format('Y-m-d');
        }
    } else {
        // Not Expired: Extend by 1 month
        $b_date->modify('+1 month');
        $new_billing_date = $b_date->format('Y-m-d');
    }

    file_put_contents($log_file, "Due Cleared. New Billing Date: $new_billing_date\n", FILE_APPEND);

    // 6. Update DB Status
    $update_sql = "UPDATE pppoe_users SET status = 'enabled', billing_date = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_billing_date, $user_id);
    if (!$stmt->execute()) {
        file_put_contents($log_file, "Update User Error: " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();

    // 7. Enable on Router
    $API = new RouterosAPI();
    $API->timeout = 10; // Increased timeout to ensure connection
    if (!empty($user['api_port'])) {
        $API->port = $user['api_port'];
    }
    //$API->debug = true; // Disable debug for JSON output

    if ($API->connect($user['ip_address'], $user['r_user'], $user['r_pass'])) {
        $username = $user['username'];
        // Find Secret
        $secrets = $API->comm('/ppp/secret/print', ['?name' => $username]);
        if (!empty($secrets)) {
            $id = $secrets[0]['.id'];
            // Enable
            $API->comm('/ppp/secret/enable', ['.id' => $id]);
            
            // Optional: Reset active connection to enforce new limits/status immediately?
            // Usually enabling is enough.
            
            $router_updated = true;
            file_put_contents($log_file, "Router enabled user $username\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, "Router secret not found for $username\n", FILE_APPEND);
        }
        $API->disconnect();
    } else {
        file_put_contents($log_file, "Router connection failed\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, "Payment collected but Due not cleared. Total Due: $total_due. Skipping activation.\n", FILE_APPEND);
}

echo json_encode([
    'success' => true, 
    'message' => ($total_due <= 0.01) ? 'Payment Collected & User Activated' : 'Payment Collected (Due Remaining)',
    'new_date' => $new_billing_date,
    'router_updated' => $router_updated,
    'remaining_due' => $total_due
]);
?>