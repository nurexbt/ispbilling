<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('connection_request_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $area_id = intval($_POST['area_id']);
    $subarea_id = intval($_POST['subarea_id']);
    $address = $conn->real_escape_string($_POST['address']);
    $note = $conn->real_escape_string($_POST['note']);

    if (empty($name) || empty($phone) || empty($area_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill required fields (Name, Phone, Area).']);
        exit;
    }

    $subarea_val = $subarea_id > 0 ? $subarea_id : "NULL";

    $query = "INSERT INTO connection_request_tbl (name, email, phone, area_id, subarea_id, address, note, status) 
              VALUES ('$name', '$email', '$phone', $area_id, $subarea_val, '$address', '$note', 'Created')";

    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Connection request added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add connection request: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
