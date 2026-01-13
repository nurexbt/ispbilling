<?php
header('Content-Type: application/json');
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? 0;
$given_name = $_POST['given_name'] ?? '';
$price = $_POST['price'] ?? 0;

if (!$id || empty($given_name)) {
    echo json_encode(['success' => false, 'message' => 'ID and Given Name are required']);
    exit;
}

$stmt = $conn->prepare("UPDATE packages SET given_name = ?, price = ? WHERE id = ?");
$stmt->bind_param("sdi", $given_name, $price, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update package']);
}

$stmt->close();
$conn->close();
?>