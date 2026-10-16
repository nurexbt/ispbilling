<?php
require_once '../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['employee_id'])) {
    $employee_id = intval($_GET['employee_id']);
    
    $query = "SELECT id, salary_month, amount, created_at, status, document_path FROM salary_tbl WHERE employee_id = $employee_id ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $history = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $history]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
