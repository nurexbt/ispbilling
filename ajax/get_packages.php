<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('package_view')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host_id = $_POST['host_id'] ?? '';
    
    if (empty($host_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Host ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, given_name, price FROM package_tbl WHERE host_id = ? ORDER BY given_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $host_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $packages = [];
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $packages]);
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
