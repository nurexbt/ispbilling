<?php
if ((isset($_POST['is_manager']) && $_POST['is_manager'] == 1) || (isset($_POST['is_owner']) && $_POST['is_owner'] == 1)) {
    $is_manager = true;
    $is_owner = true;
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!has_permission('amount_collect_handover')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$processor_name = $_POST['processor_name'] ?? '';
$amount = (float)($_POST['amount'] ?? 0);
$for_date = !empty($_POST['for_date']) ? $_POST['for_date'] : null;
$received_by = $_SESSION['username'] ?? 'Admin';
$handover_date = date('Y-m-d H:i:s');

if (empty($processor_name) || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Processor name and valid amount are required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO collector_handover_tbl (processor_name, amount, for_date, handover_date, received_by) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sdsss", $processor_name, $amount, $for_date, $handover_date, $received_by);

if ($stmt->execute()) {
    $inserted_id = $stmt->insert_id;
    echo json_encode(['status' => 'success', 'message' => 'Amount collected successfully.', 'handover_id' => $inserted_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to collect amount.']);
}

$stmt->close();
?>
