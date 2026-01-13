<?php
header('Content-Type: application/json');
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No IDs provided']);
    exit;
}

// Convert IDs to integers for safety
$ids = array_map('intval', $ids);
$ids_string = implode(',', $ids);

$sql = "DELETE FROM pppoe_users WHERE id IN ($ids_string)";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => count($ids) . ' users deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete users']);
}

$conn->close();
?>