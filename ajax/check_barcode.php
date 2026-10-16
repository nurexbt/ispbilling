<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barcode = $conn->real_escape_string(trim($_POST['barcode'] ?? ''));

    if (empty($barcode)) {
        echo json_encode(['exists' => false]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
}
?>
