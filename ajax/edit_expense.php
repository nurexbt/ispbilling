<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('expense_edit') && !has_permission('txbill_full_access')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    
    if (empty($id) || empty($amount) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Amount and category are required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE expense_tbl SET amount = ?, category = ?, note = ? WHERE id = ?");
        $stmt->bind_param("dssi", $amount, $category, $note, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating expense: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
