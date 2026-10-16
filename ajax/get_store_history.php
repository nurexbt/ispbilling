<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['store_id'])) {
    $store_id = (int)$_GET['store_id'];

    $query = "SELECT action, description, action_by, created_at FROM store_item_history_tbl WHERE store_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                'action' => $row['action'],
                'description' => $row['description'],
                'action_by' => $row['action_by'] ?: 'System',
                'created_at' => date('d M Y, h:i A', strtotime($row['created_at']))
            ];
        }
        
        echo json_encode(['status' => 'success', 'data' => $history]);
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
