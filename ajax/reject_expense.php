<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Need manager or owner permission to reject
if (!has_permission('manager_full_access') && !has_permission('owner_full_access')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $reject_note = isset($_POST['reject_note']) ? trim($_POST['reject_note']) : '';
    
    if ($expense_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID.']);
        exit;
    }
    
    if (empty($reject_note)) {
        echo json_encode(['success' => false, 'message' => 'A rejection note is required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE expense_tbl SET status = 'Rejected', reject_note = ? WHERE id = ?");
    $stmt->bind_param("si", $reject_note, $expense_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject expense: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
