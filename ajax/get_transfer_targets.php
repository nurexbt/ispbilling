<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$exclude_id = (int)($_GET['exclude_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = "SELECT id, name, username, phone, previous_due, advance_payment 
        FROM customer_tbl 
        WHERE 1=1";
$params = [];
$types = "";

if ($exclude_id > 0) {
    $sql .= " AND id != ?";
    $params[] = $exclude_id;
    $types .= "i";
}

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY name ASC LIMIT 300";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$customers = [];
while ($row = $res->fetch_assoc()) {
    $customers[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'username' => $row['username'],
        'phone' => $row['phone'],
        'due' => (float)$row['previous_due'],
        'advance' => (float)$row['advance_payment']
    ];
}
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $customers]);
?>
