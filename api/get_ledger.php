<?php
header('Content-Type: application/json');
include '../includes/db.php';

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit;
}

// Fetch User Basic Info & Package Name
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.billing_date, u.status, u.given_profile, p.price as package_price
    FROM pppoe_users u
    LEFT JOIN packages p ON (u.given_profile = p.given_name OR u.given_profile = p.original_name) AND u.router_id = p.router_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_res = $stmt->get_result();
$user = $user_res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// --- SYNC BILLING LOGIC ---
// Ensure all past months have an invoice generated.
$bill_amount = isset($user['package_price']) ? (float)$user['package_price'] : 0;
if ($bill_amount > 0 && !empty($user['billing_date'])) {
    $billing_date = strtotime($user['billing_date']);
    $today = time();
    
    // If billing date is in the past, they owe us.
    if ($billing_date < $today) {
        $current_iter_date = $billing_date;
        
        // Loop from billing_date to today
        while ($current_iter_date < $today) {
            $month_str = date('Y-m-01', $current_iter_date);
            
            // Check if ANY bill exists for this month (paid or unpaid)
            // We use a loose check: if there is a bill with bill_month matching this month
            $check = $conn->query("SELECT id FROM pppoe_billings WHERE user_id = {$user['id']} AND bill_month = '$month_str'");
            if ($check->num_rows == 0) {
                // No bill found for this month, generate UNPAID bill
                $inv_no = 'INV-' . date('Ymd', $current_iter_date) . '-' . $user['id'];
                $due_date = date('Y-m-10', $current_iter_date); // 10th of that month
                
                // If the month is already passed, it's effectively "Overdue" but we store as "Unpaid"
                // The billing_date is the START of the unpaid period.
                // Example: billing_date = 2025-10-10. Today = 2026-01-12.
                // Iteration 1: 2025-10-10. Month: 2025-10-01. Generate Bill Oct.
                // Iteration 2: 2025-11-10. Month: 2025-11-01. Generate Bill Nov.
                // ...
                
                $stmt = $conn->prepare("INSERT INTO pppoe_billings (user_id, invoice_number, bill_month, bill_date, due_date, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'unpaid')");
                $bill_date_str = date('Y-m-d H:i:s', $current_iter_date);
                $stmt->bind_param("issssd", $user['id'], $inv_no, $month_str, $bill_date_str, $due_date, $bill_amount);
                $stmt->execute();
                $stmt->close();
            }
            
            // Move to next month
            $current_iter_date = strtotime("+1 month", $current_iter_date);
        }
    }
}
// --- END SYNC LOGIC ---

// Fetch Invoices/Billings (Now includes newly generated ones)
$sql = "SELECT * FROM pppoe_billings WHERE user_id = ? ORDER BY bill_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$invoices = [];
$total_paid = 0;
$current_due = 0;

while ($row = $result->fetch_assoc()) {
    $row['service_type'] = $user['given_profile']; 
    $invoices[] = $row;
    
    if ($row['status'] == 'paid') {
        $total_paid += $row['amount'];
    } else {
        $current_due += $row['amount'];
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'user' => $user,
    'invoices' => $invoices,
    'summary' => [
        'total_paid' => $total_paid,
        'current_due' => $current_due,
        'monthly_bill' => $user['package_price']
    ]
]);
?>