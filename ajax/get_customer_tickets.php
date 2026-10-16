<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$customer_id = intval($_GET['customer_id'] ?? 0);
if ($customer_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit();
}

// Fetch customer info
$stmt_cust = $conn->prepare("SELECT id, name, username, phone FROM customer_tbl WHERE id = ?");
$stmt_cust->bind_param("i", $customer_id);
$stmt_cust->execute();
$cust_res = $stmt_cust->get_result();
$customer = $cust_res->fetch_assoc();
$stmt_cust->close();

if (!$customer) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit();
}

// Fetch categories for ticket creation / edit
$categories = [];
$cat_res = $conn->query("SELECT id, name FROM ticket_category_tbl ORDER BY name ASC");
if ($cat_res) {
    while ($cat = $cat_res->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Fetch tickets for this customer
$stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.category_id, t.priority, t.status, t.created_by, t.created_at,
           c.name as category_name,
           (SELECT COUNT(*) FROM ticket_reply_tbl r WHERE r.ticket_id = t.id) as reply_count
    FROM support_ticket_tbl t
    LEFT JOIN ticket_category_tbl c ON t.category_id = c.id
    WHERE t.customer_id = ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$tickets_res = $stmt->get_result();
$tickets = [];
while ($t = $tickets_res->fetch_assoc()) {
    $t['created_at_formatted'] = date('d M Y, h:i A', strtotime($t['created_at']));
    $tickets[] = $t;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'customer' => $customer,
    'categories' => $categories,
    'tickets' => $tickets
]);
