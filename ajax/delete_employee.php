<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('employee_delete')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['employee_id'] ?? 0);
    
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required.']);
        exit;
    }

    // Get old file paths to delete them (optional but good practice)
    $sql_get = "SELECT profile_image, nid_image FROM employee_tbl WHERE id = $id";
    $result = $conn->query($sql_get);
    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['profile_image']) && file_exists('../' . $row['profile_image'])) {
            unlink('../' . $row['profile_image']);
        }
        if (!empty($row['nid_image']) && file_exists('../' . $row['nid_image'])) {
            unlink('../' . $row['nid_image']);
        }
    }

    $sql = "DELETE FROM employee_tbl WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Employee deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
