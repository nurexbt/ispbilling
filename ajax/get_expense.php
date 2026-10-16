<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('expense_edit') && !has_permission('txbill_full_access')) {
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Expense ID is missing']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM expense_tbl WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $expense = $result->fetch_assoc();
    echo json_encode(['success' => true, 'expense' => $expense]);
} else {
    echo json_encode(['success' => false, 'message' => 'Expense not found']);
}

$stmt->close();
?>
