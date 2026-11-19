<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check authentication & manager/owner/admin permissions
$role = strtolower($_SESSION['role_name'] ?? '');
$is_mgr = has_permission('manager_full_access') || has_permission('owner_full_access') || in_array($role, ['manager', 'owner', 'sysadmin']) || has_permission('store_inventory_edit');

if (!$is_mgr) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: Only Manager or Owner can approve returned equipment to store.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$store_id = intval($_POST['store_id'] ?? 0);
$action_type = trim($_POST['action_type'] ?? 'approve_to_stock'); // 'approve_to_stock', 'mark_damaged', 'mark_warranty'
$note = trim($_POST['note'] ?? '');
$manager_name = function_exists('get_current_user_name') ? get_current_user_name($conn) : ($_SESSION['username'] ?? 'Manager');

if ($store_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid store item ID.']);
    exit;
}

// Fetch item from store_tbl
$stmt = $conn->prepare("SELECT s.*, i.item_name FROM store_tbl s JOIN item_tbl i ON s.item_id = i.id WHERE s.id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $store_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Store item not found.']);
    exit;
}

$item = $res->fetch_assoc();
$stmt->close();

$barcode = $item['barcode_id'];

$conn->begin_transaction();
try {
    if ($action_type === 'approve_to_stock') {
        // Approve back into Store as In-Stock (available for use/delivery)
        $up = $conn->prepare("UPDATE store_tbl SET 
                                delivery_status = 'pending', 
                                delivered_to = NULL, 
                                return_reason = NULL, 
                                replace_new_id = NULL, 
                                delivered_at = NULL, 
                                approved_at = NOW(), 
                                used_at = NULL, 
                                returned_at = NULL 
                              WHERE id = ?");
        $up->bind_param("i", $store_id);
        if (!$up->execute()) {
            throw new Exception("Failed to update store item: " . $up->error);
        }
        $up->close();

        $action_name = 'Approved to Store';
        $desc = "Approved by Manager " . $manager_name . " and added back to Store Stock (In Stock)." . ($note ? " Note: " . $note : "");
        $msg = "ONU $barcode successfully approved and added to Store Stock (In Stock).";
    } elseif ($action_type === 'mark_damaged') {
        $up = $conn->prepare("UPDATE store_tbl SET return_reason = 'Damage', approved_at = NOW() WHERE id = ?");
        $up->bind_param("i", $store_id);
        if (!$up->execute()) {
            throw new Exception("Failed to update store item: " . $up->error);
        }
        $up->close();

        $action_name = 'Damaged';
        $desc = "Marked as Damaged by Manager " . $manager_name . "." . ($note ? " Note: " . $note : "");
        $msg = "ONU $barcode marked as Damaged.";
    } elseif ($action_type === 'mark_warranty') {
        $up = $conn->prepare("UPDATE store_tbl SET return_reason = 'Warranty', approved_at = NOW() WHERE id = ?");
        $up->bind_param("i", $store_id);
        if (!$up->execute()) {
            throw new Exception("Failed to update store item: " . $up->error);
        }
        $up->close();

        $action_name = 'Warranty';
        $desc = "Sent to Warranty by Manager " . $manager_name . "." . ($note ? " Note: " . $note : "");
        $msg = "ONU $barcode marked for Warranty.";
    } else {
        throw new Exception("Invalid action type.");
    }

    // Log in history
    $hist = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)");
    if ($hist) {
        $hist->bind_param("isss", $store_id, $action_name, $desc, $manager_name);
        $hist->execute();
        $hist->close();
    }

    $conn->commit();
    echo json_encode([
        'status' => 'success',
        'message' => $msg
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
