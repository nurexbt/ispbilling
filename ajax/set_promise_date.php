<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$customer_id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'set' or 'clear'
$date = $_POST['date'] ?? '';

if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
}

if ($action === 'set') {
    // Validate: must be a future date
    if (empty($date) || strtotime($date) <= strtotime('today')) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a future date.']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE customer_tbl SET promise_date = ? WHERE id = ?");
    $stmt->bind_param("si", $date, $customer_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'success', 'message' => 'Promise date set to ' . date('d M Y', strtotime($date))]);
} elseif ($action === 'clear') {
    $stmt = $conn->prepare("UPDATE customer_tbl SET promise_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'success', 'message' => 'Promise date cleared.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
