<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['store_id'])) {
    $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
    $employee_name = get_current_user_name($conn);

    // Verify it is in Warranty
    $check_query = "SELECT return_reason FROM store_tbl WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['return_reason'] !== 'Warranty') {
            echo json_encode(['status' => 'error', 'message' => 'Only items returned for Warranty can be re-added directly.']);
            exit;
        }
        
        $conn->begin_transaction();
        
        try {
            // Update the store item back to available
            $update_query = "UPDATE store_tbl SET 
                                delivery_status = 'pending', 
                                delivered_to = NULL, 
                                return_reason = NULL, 
                                delivered_at = NULL, 
                                approved_at = NULL, 
                                used_at = NULL, 
                                returned_at = NULL 
                             WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $store_id);
            if (!$update_stmt->execute()) {
                throw new Exception('Failed to update store item state.');
            }
            
            // Insert history log
            $action = 'Re-Added';
            $description = 'Re-Added to available store from Warranty state.';
            $history_query = "INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)";
            $hist_stmt = $conn->prepare($history_query);
            $hist_stmt->bind_param("isss", $store_id, $action, $description, $employee_name);
            if (!$hist_stmt->execute()) {
                throw new Exception('Failed to log history.');
            }
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Item successfully re-added to store.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
