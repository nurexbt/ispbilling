<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('store_record_add_item_category')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['item_id'] ?? 0);
    $item_name = $conn->real_escape_string(trim($_POST['item_name'] ?? ''));

    if (empty($id) || empty($item_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Item ID and Name are required.']);
        exit;
    }

    $sql = "UPDATE item_tbl SET item_name = '$item_name' WHERE id = $id";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Item updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
