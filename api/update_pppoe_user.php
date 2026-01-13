<?php
header('Content-Type: application/json');
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Get other fields
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$nid = $_POST['nid'] ?? '';
$address = $_POST['address'] ?? '';
$onu_sn = $_POST['onu_sn'] ?? '';
$connection_date = $_POST['connection_date'] ?? null;
$billing_date = $_POST['billing_date'] ?? null;
$comment = $_POST['comment'] ?? '';
// username, profile, router are typically not editable here or require Mikrotik sync? 
// User asked to add Image, Name, Phone, NID, Address.
// Assuming we just update local DB. If we need to update Mikrotik, that's a separate step (Update Profile/Secret).
// For now, let's update local DB.

// Handle Image Upload - REMOVED

// Build Query
$sql = "UPDATE pppoe_users SET full_name = ?, phone = ?, nid = ?, address = ?, onu_sn = ?, connection_date = ?, billing_date = ?, comment = ?";
$types = "ssssssss";
$params = [$full_name, $phone, $nid, $address, $onu_sn, $connection_date, $billing_date, $comment];

$sql .= " WHERE id = ?";
$types .= "i";
$params[] = $id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
