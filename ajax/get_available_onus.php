<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch available ONUs from store_tbl
// 1. Joined with item_tbl where item_name contains 'ONU'
// 2. return_reason is NULL or empty
// 3. used_at is NULL
// 4. Barcode is not currently assigned to another active customer
$query = "
    SELECT s.id, s.barcode_id, i.item_name, s.created_at
    FROM store_tbl s
    JOIN item_tbl i ON s.item_id = i.id
    WHERE (LOWER(i.item_name) LIKE '%onu%')
      AND (s.return_reason IS NULL OR s.return_reason = '')
      AND s.used_at IS NULL
      AND s.barcode_id NOT IN (
          SELECT onu_sn FROM customer_tbl 
          WHERE onu_sn IS NOT NULL AND onu_sn != ''
      )
    ORDER BY s.barcode_id ASC
";

$res = $conn->query($query);
$items = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'barcode_id' => $row['barcode_id'],
            'item_name' => $row['item_name'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'count' => count($items),
    'data' => $items
]);

