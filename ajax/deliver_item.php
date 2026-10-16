<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('store_inventory_deliver')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_id = intval($_POST['store_id']);
    $employee_id = intval($_POST['employee_id']);
    $employee_name = get_current_user_name($conn);

    if (empty($store_id) || empty($employee_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Store ID and Employee ID are required.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE store_tbl SET delivered_to = ?, delivery_status = 'pending', delivered_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $employee_id, $store_id);

        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        // Fetch target employee name for log
        $target_name = "technician";
        $emp_stmt = $conn->prepare("SELECT full_name FROM employee_tbl WHERE id = ?");
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $employee_id);
            $emp_stmt->execute();
            $res = $emp_stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $target_name = $row['full_name'];
            }
        }
        
        $action = 'Delivered';
        $description = 'Delivered to technician ' . $target_name;
        $history_query = "INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)";
        $hist_stmt = $conn->prepare($history_query);
        $hist_stmt->bind_param("isss", $store_id, $action, $description, $employee_name);
        
        if (!$hist_stmt->execute()) {
            throw new Exception('Failed to log history.');
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Item successfully delivered to employee.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
