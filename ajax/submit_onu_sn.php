<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $onu_source = trim($_POST['onu_source'] ?? 'store');
    $onu_sn = trim($_POST['onu_sn'] ?? '');

    // Check if Customer Own ONU
    if ($onu_source === 'customer_own' || $onu_sn === '__customer_own__' || stripos($onu_sn, 'Customer-') === 0) {
        $last4 = strtoupper(trim($_POST['customer_onu_last4'] ?? ''));
        if (empty($last4) && preg_match('/^Customer-(.+)$/i', $onu_sn, $matches)) {
            $last4 = strtoupper(trim($matches[1]));
        }

        if (empty($last4)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter the last 4 digits of customer ONU.']);
            exit;
        }

        $onu_sn = 'Customer-' . $last4;
        $is_customer_own = true;
    } else {
        if (empty($onu_sn)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select the ONU from on-hand equipments.']);
            exit;
        }
        $is_customer_own = false;
    }

    $onu_sn_escaped = $conn->real_escape_string($onu_sn);
    $submitted_by = $conn->real_escape_string(get_current_user_name($conn));

    $update_query = "UPDATE connection_request_tbl SET onu_sn = '$onu_sn_escaped', onu_submitted_by = '$submitted_by', status = 'TX Complete' WHERE id = $request_id";

    if ($conn->query($update_query)) {
        // Only update store item to used if it was taken from company/store equipment
        if (!$is_customer_own) {
            $conn->query("UPDATE store_tbl SET used_at = NOW(), delivery_status = 'used' WHERE barcode_id = '$onu_sn_escaped'");
        }
        echo json_encode([
            'status' => 'success', 
            'message' => 'ONU SN submitted (' . $onu_sn . '). Status changed to TX Complete.',
            'onu_sn' => $onu_sn,
            'is_customer_own' => $is_customer_own
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit ONU SN: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
