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

    // Fetch existing request details
    $req_stmt = $conn->prepare("SELECT customer_id, request_type, name, onu_sn FROM connection_request_tbl WHERE id = ? LIMIT 1");
    $req_stmt->bind_param("i", $request_id);
    $req_stmt->execute();
    $req_res = $req_stmt->get_result();
    $req_data = ($req_res && $req_res->num_rows > 0) ? $req_res->fetch_assoc() : null;
    $req_stmt->close();

    $customer_id = !empty($req_data['customer_id']) ? intval($req_data['customer_id']) : 0;
    $request_type = $req_data['request_type'] ?? 'new_connection';

    $update_query = "UPDATE connection_request_tbl SET onu_sn = '$onu_sn_escaped', onu_submitted_by = '$submitted_by', status = 'TX Complete' WHERE id = $request_id";

    if ($conn->query($update_query)) {
        // 1. Update New ONU in store_tbl (if from store)
        if (!$is_customer_own) {
            $conn->query("UPDATE store_tbl SET used_at = NOW(), delivery_status = 'used' WHERE barcode_id = '$onu_sn_escaped'");
        }

        // 2. Handle ONU Change for Existing Customer
        if ($request_type === 'onu_change' && $customer_id > 0) {
            $cust_stmt = $conn->prepare("SELECT id, name, username, onu_sn, note FROM customer_tbl WHERE id = ? LIMIT 1");
            $cust_stmt->bind_param("i", $customer_id);
            $cust_stmt->execute();
            $cust_res = $cust_stmt->get_result();
            $customer = ($cust_res && $cust_res->num_rows > 0) ? $cust_res->fetch_assoc() : null;
            $cust_stmt->close();

            if ($customer) {
                $old_onu_sn = trim($customer['onu_sn'] ?? '');
                $old_onu_display = !empty($old_onu_sn) ? $old_onu_sn : 'None';
                $cust_name = $customer['name'] ?? 'Customer';
                $cust_user = $customer['username'] ?? '';

                // A. Record New ONU in store history
                if (!$is_customer_own) {
                    $s_stmt = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ? LIMIT 1");
                    if ($s_stmt) {
                        $s_stmt->bind_param("s", $onu_sn);
                        $s_stmt->execute();
                        $s_res = $s_stmt->get_result();
                        if ($s_row = $s_res->fetch_assoc()) {
                            $new_store_id = (int)$s_row['id'];
                            $desc_new = "Used for customer " . $cust_name . " (" . $cust_user . ") - Replaced old ONU " . $old_onu_display;
                            $hist = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, 'Used', ?, ?)");
                            if ($hist) {
                                $hist->bind_param("iss", $new_store_id, $desc_new, $submitted_by);
                                $hist->execute();
                                $hist->close();
                            }
                        }
                        $s_stmt->close();
                    }
                }

                // B. Update Old ONU in store_tbl (if old ONU exists and is from store)
                if (!empty($old_onu_sn) && stripos($old_onu_sn, 'Customer-') !== 0) {
                    $old_stmt = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ? LIMIT 1");
                    if ($old_stmt) {
                        $old_stmt->bind_param("s", $old_onu_sn);
                        $old_stmt->execute();
                        $old_res = $old_stmt->get_result();
                        if ($old_row = $old_res->fetch_assoc()) {
                            $old_store_id = (int)$old_row['id'];
                            $conn->query("UPDATE store_tbl SET delivered_to = NULL, delivery_status = 'pending', return_reason = 'Replace', replace_new_id = '$onu_sn_escaped', returned_at = NOW() WHERE id = $old_store_id");

                            $desc_old = "Returned - Reason: Replace (Replaced with new ONU " . $onu_sn . " by technician " . $submitted_by . " for customer " . $cust_name . ")";
                            $hist_old = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, 'Returned', ?, ?)");
                            if ($hist_old) {
                                $hist_old->bind_param("iss", $old_store_id, $desc_old, $submitted_by);
                                $hist_old->execute();
                                $hist_old->close();
                            }
                        }
                        $old_stmt->close();
                    }
                }

                // C. Update Customer ONU & Append to Customer Note
                $date_str = date('d M Y, h:ia');
                $change_note = "[$date_str] ONU Changed by Technician $submitted_by: Old ONU: $old_onu_display, New ONU: $onu_sn";
                $updated_note = !empty($customer['note']) ? $customer['note'] . "\n" . $change_note : $change_note;

                $up_cust = $conn->prepare("UPDATE customer_tbl SET onu_sn = ?, onu_submitted_by = ?, note = ? WHERE id = ?");
                if ($up_cust) {
                    $up_cust->bind_param("sssi", $onu_sn, $submitted_by, $updated_note, $customer_id);
                    $up_cust->execute();
                    $up_cust->close();
                }

                // D. Create a Support Ticket record for auditing
                $ticket_title = "ONU Replaced - Old: " . $old_onu_display . ", New: " . $onu_sn;
                $ticket_desc = "ONU replaced by technician " . $submitted_by . " for customer " . $cust_name . " (" . $cust_user . ")";
                $category_id = 2; // Technical Support
                $priority = 'Medium';
                $ticket_status = 'Completed';

                $stmt_ticket = $conn->prepare("INSERT INTO support_ticket_tbl (title, description, category_id, customer_id, priority, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_ticket) {
                    $stmt_ticket->bind_param("ssiisss", $ticket_title, $ticket_desc, $category_id, $customer_id, $priority, $ticket_status, $submitted_by);
                    $stmt_ticket->execute();
                    $stmt_ticket->close();
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'ONU changed successfully to ' . $onu_sn . ' for customer ' . $cust_name . '.',
                    'onu_sn' => $onu_sn,
                    'is_customer_own' => $is_customer_own,
                    'is_onu_change' => true
                ]);
                exit;
            }
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
