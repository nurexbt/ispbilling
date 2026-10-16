<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('area_view')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['area_id'])) {
    $area_id = intval($_POST['area_id']);
    $query = "SELECT id, subarea_name FROM subarea_tbl WHERE area_id = $area_id ORDER BY subarea_name ASC";
    $result = $conn->query($query);
    
    $subareas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subareas[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $subareas]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
