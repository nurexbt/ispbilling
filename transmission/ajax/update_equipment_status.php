<?php
require_once '../../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';
    $emp_id = $_SESSION['employee_id'] ?? 0;

    if ($id <= 0 || !in_array($status, ['accepted', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
        exit;
    }

    if ($emp_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit;
    }

    // Verify ownership
    $check_query = "SELECT id FROM store_tbl WHERE id = $id AND delivered_to = '$emp_id' AND delivery_status = 'pending'";
    $check_result = $conn->query($check_query);
    if (!$check_result || $check_result->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Equipment not found or already processed.']);
        exit;
    }

    // Update status
    $conn->begin_transaction();
    try {
        if ($status === 'rejected') {
            $update_query = "UPDATE store_tbl SET delivery_status = 'pending', delivered_to = NULL WHERE id = $id";
        } else {
            $update_query = "UPDATE store_tbl SET delivery_status = '$status', approved_at = NOW() WHERE id = $id";
        }
        
        if (!$conn->query($update_query)) {
            throw new Exception("Update failed: " . $conn->error);
        }

        // Fetch employee name
        $employee_name = "technician";
        $emp_stmt = $conn->prepare("SELECT full_name FROM employee_tbl WHERE id = ?");
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $emp_id);
            $emp_stmt->execute();
            $res = $emp_stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $employee_name = $row['full_name'];
            }
            $emp_stmt->close();
        }

        // Log history
        $action = ($status === 'accepted') ? 'Approved' : 'Returned';
        $desc = ($status === 'accepted') ? "Approved by technician " . $employee_name : "Delivery rejected by technician " . $employee_name;
        
        $hist_stmt = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)");
        if ($hist_stmt) {
            $hist_stmt->bind_param("isss", $id, $action, $desc, $employee_name);
            $hist_stmt->execute();
            $hist_stmt->close();
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Equipment status updated to ' . $status . '.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
