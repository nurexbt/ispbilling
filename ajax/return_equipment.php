<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('store_inventory_return')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;

    $return_reason = isset($_POST['return_reason']) ? $conn->real_escape_string($_POST['return_reason']) : '';
    $replace_new_id = isset($_POST['replace_new_id']) ? $conn->real_escape_string($_POST['replace_new_id']) : NULL;
    $employee_name = get_current_user_name($conn);

    if (empty($store_id) || empty($return_reason)) {
        echo json_encode(['status' => 'error', 'message' => 'Store ID and Return Reason are required.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE store_tbl SET delivered_to = NULL, delivery_status = 'pending', return_reason = ?, replace_new_id = ?, returned_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $return_reason, $replace_new_id, $store_id);

        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        $action = 'Returned';
        $description = 'Returned - Reason: ' . $return_reason;
        if ($return_reason === 'Replace' && !empty($replace_new_id)) {
            $description .= ' (Replacement ID: ' . $replace_new_id . ')';
        }
        
        $history_query = "INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)";
        $hist_stmt = $conn->prepare($history_query);
        $hist_stmt->bind_param("isss", $store_id, $action, $description, $employee_name);
        
        if (!$hist_stmt->execute()) {
            throw new Exception('Failed to log history.');
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Equipment returned successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
