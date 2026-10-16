<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('store_inventory_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $barcodes = isset($_POST['barcodes']) ? $_POST['barcodes'] : [];
    $employee_name = get_current_user_name($conn);

    if (empty($item_id) || empty($barcodes) || !is_array($barcodes)) {
        echo json_encode(['status' => 'error', 'message' => 'Item ID and at least one barcode are required.']);
        exit;
    }

    $success_count = 0;
    $errors = [];

    // Begin transaction for bulk insert
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO store_tbl (item_id, barcode_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $hist_stmt = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, 'Added', 'Added to store', ?)");

        foreach ($barcodes as $barcode) {
            $bc = trim($barcode);
            if(empty($bc)) continue;
            
            // Check if exists first to provide better error than raw SQL duplicate key
            $check_stmt = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ?");
            $check_stmt->bind_param("s", $bc);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $errors[] = "Barcode '$bc' already exists in store.";
                continue;
            }
            $check_stmt->close();

            $stmt->bind_param("is", $item_id, $bc);
            if ($stmt->execute()) {
                $new_store_id = $stmt->insert_id;
                $hist_stmt->bind_param("is", $new_store_id, $employee_name);
                $hist_stmt->execute();
                $success_count++;
            } else {
                $errors[] = "Failed to insert '$bc'.";
            }
        }
        
        $stmt->close();
        
        if ($success_count > 0) {
            $conn->commit();
            $msg = "$success_count items added to store successfully.";
            if (count($errors) > 0) {
                $msg .= " Some errors occurred: " . implode(" ", $errors);
            }
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to add any items. ' . implode(" ", $errors)]);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
