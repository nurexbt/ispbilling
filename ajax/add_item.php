<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('store_record_add_item_category')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $conn->real_escape_string(trim($_POST['item_name'] ?? ''));

    if (empty($item_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Item Name is required.']);
        exit;
    }

    $sql = "INSERT INTO item_tbl (item_name) VALUES ('$item_name')";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Item added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
