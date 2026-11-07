<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/routeros_api.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? '';
    if (empty($customer_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer ID is required.']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $onu_sn = $_POST['onu_sn'] ?? '';
    $area_id = $_POST['area_id'] ?? null;
    $subarea_id = $_POST['subarea_id'] ?? null;
    $address = $_POST['address'] ?? '';
    $nid_no = $_POST['nid_no'] ?? '';
    $router_id = $_POST['router_id'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $discount = $_POST['discount'] ?? 0;
    $package_price = $_POST['package_price'] ?? 0;
    $connection_date = $_POST['connection_date'] ?? date('Y-m-d');
    $billing_date = $_POST['billing_date'] ?? date('Y-m-d');
    $payment_cycle_date = $_POST['payment_cycle_date'] ?? date('Y-m-d');
    $client_note = $_POST['client_note'] ?? '';

    $is_special = isset($_POST['is_special']) ? 1 : 0;

    // ONU Replacement fields
    $is_onu_replaced = isset($_POST['is_onu_replaced']) && $_POST['is_onu_replaced'] == '1';
    $old_onu_sn = trim($_POST['old_onu_sn'] ?? '');
    $new_onu_sn = trim($_POST['new_onu_sn'] ?? '');

    if ($is_onu_replaced) {
        if (empty($new_onu_sn)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a new ONU from store to replace.']);
            exit;
        }
        $onu_sn = $new_onu_sn;
    }

    // Fetch existing customer to see if Mikrotik update is needed and get existing ONU/name
    $stmt = $conn->prepare("SELECT name, username, password, router_id, package_id, onu_sn FROM customer_tbl WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $old_cust_res = $stmt->get_result();
    $old_cust = $old_cust_res->fetch_assoc();
    $stmt->close();

    if ($is_onu_replaced && empty($old_onu_sn) && !empty($old_cust['onu_sn'])) {
        $old_onu_sn = trim($old_cust['onu_sn']);
    }

    $mikrotik_error = '';

    // If Mikrotik fields changed, update router
    if ($old_cust && ($old_cust['username'] !== $username || $old_cust['password'] !== $password || $old_cust['package_id'] != $package_id)) {
        // Fetch host info
        $stmt = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ?");
        $stmt->bind_param("i", $router_id);
        $stmt->execute();
        $host_res = $stmt->get_result();
        $host = $host_res->fetch_assoc();
        $stmt->close();

        // Fetch package profile name
        $stmt = $conn->prepare("SELECT original_profile FROM package_tbl WHERE id = ?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $pkg_res = $stmt->get_result();
        $pkg = $pkg_res->fetch_assoc();
        $stmt->close();

        if ($host && $pkg) {
            $API = new RouterosAPI();
            $API->debug = false;
            $API->port = (int)$host['api_port'];
            
            if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
                // Find existing secret by old username
                $API->write('/ppp/secret/print', false);
                $API->write('?name=' . $old_cust['username'], true);
                $READ = $API->read(false);
                $ARRAY = $API->parseResponse($READ);

                if (!empty($ARRAY)) {
                    $secret_id = $ARRAY[0]['.id'];
                    $API->write('/ppp/secret/set', false);
                    $API->write('=.id=' . $secret_id, false);
                    $API->write('=name=' . $username, false);
                    $API->write('=password=' . $password, false);
                    $API->write('=profile=' . $pkg['original_profile'], false);
                    $API->write('=comment=' . $name . '-' . $phone . '-' . $address);
                    
                    $res = $API->read(false);
                    $err = $API->parseResponse($res);
                    if (isset($err['!trap'])) {
                        $mikrotik_error = ' (Mikrotik Update Error: ' . ($err['!trap'][0]['message'] ?? 'Unknown error') . ')';
                    }
                } else {
                    $mikrotik_error = " (Mikrotik Note: Old PPPoE user '{$old_cust['username']}' not found, skipping update.)";
                }
                $API->disconnect();
            } else {
                $mikrotik_error = " (Mikrotik Warning: Failed to connect to Router. Router fields not updated there.)";
            }
        }
    }

    $action_by = get_current_user_name($conn);
    $emp_id = isset($_SESSION['employee_id']) && $_SESSION['employee_id'] > 0 ? (int)$_SESSION['employee_id'] : null;

    $conn->begin_transaction();

    try {
        // 1. Update customer_tbl
        if ($is_onu_replaced) {
            $stmt = $conn->prepare("UPDATE customer_tbl SET name=?, phone=?, email=?, onu_sn=?, area_id=?, subarea_id=?, address=?, nid_no=?, router_id=?, package_id=?, username=?, password=?, discount=?, package_price=?, is_special=?, connection_date=?, billing_date=?, payment_cycle_date=?, note=?, onu_submitted_by=? WHERE id=?");
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            $stmt->bind_param("ssssiissiissssisssssi", $name, $phone, $email, $onu_sn, $area_id, $subarea_id, $address, $nid_no, $router_id, $package_id, $username, $password, $discount, $package_price, $is_special, $connection_date, $billing_date, $payment_cycle_date, $client_note, $action_by, $customer_id);
        } else {
            $stmt = $conn->prepare("UPDATE customer_tbl SET name=?, phone=?, email=?, onu_sn=?, area_id=?, subarea_id=?, address=?, nid_no=?, router_id=?, package_id=?, username=?, password=?, discount=?, package_price=?, is_special=?, connection_date=?, billing_date=?, payment_cycle_date=?, note=? WHERE id=?");
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            $stmt->bind_param("ssssiissiissssissssi", $name, $phone, $email, $onu_sn, $area_id, $subarea_id, $address, $nid_no, $router_id, $package_id, $username, $password, $discount, $package_price, $is_special, $connection_date, $billing_date, $payment_cycle_date, $client_note, $customer_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Database error while updating customer: " . $stmt->error);
        }
        $stmt->close();

        // 2. Process ONU Replacement
        if ($is_onu_replaced) {
            $customer_name = !empty($name) ? $name : ($old_cust['name'] ?? 'Customer');
            $customer_username = !empty($username) ? $username : ($old_cust['username'] ?? '');
            $old_sn_display = !empty($old_onu_sn) ? $old_onu_sn : 'None';

            // A. Update Old ONU in store_tbl (if old_onu_sn was present and found in store_tbl)
            if (!empty($old_onu_sn)) {
                $stmt_old = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ? LIMIT 1");
                if ($stmt_old) {
                    $stmt_old->bind_param("s", $old_onu_sn);
                    $stmt_old->execute();
                    $res_old = $stmt_old->get_result();
                    if ($old_row = $res_old->fetch_assoc()) {
                        $old_store_id = (int)$old_row['id'];

                        $up_old = $conn->prepare("UPDATE store_tbl SET delivered_to = NULL, delivery_status = 'pending', return_reason = 'Replace', replace_new_id = ?, returned_at = NOW() WHERE id = ?");
                        if ($up_old) {
                            $up_old->bind_param("si", $new_onu_sn, $old_store_id);
                            $up_old->execute();
                            $up_old->close();
                        }

                        $old_desc = "Returned - Reason: Replace (Replaced with new ONU " . $new_onu_sn . " for customer " . $customer_name . ")";
                        $hist_old = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)");
                        if ($hist_old) {
                            $action_returned = 'Returned';
                            $hist_old->bind_param("isss", $old_store_id, $action_returned, $old_desc, $action_by);
                            $hist_old->execute();
                            $hist_old->close();
                        }
                    }
                    $stmt_old->close();
                }
            }

            // B. Update New ONU in store_tbl
            $stmt_new = $conn->prepare("SELECT id FROM store_tbl WHERE barcode_id = ? LIMIT 1");
            if ($stmt_new) {
                $stmt_new->bind_param("s", $new_onu_sn);
                $stmt_new->execute();
                $res_new = $stmt_new->get_result();
                if ($new_row = $res_new->fetch_assoc()) {
                    $new_store_id = (int)$new_row['id'];

                    if ($emp_id && $emp_id > 0) {
                        $up_new = $conn->prepare("UPDATE store_tbl SET delivered_to = ?, delivery_status = 'accepted', delivered_at = NOW(), used_at = NOW(), return_reason = NULL, replace_new_id = NULL WHERE id = ?");
                        if ($up_new) {
                            $up_new->bind_param("ii", $emp_id, $new_store_id);
                            $up_new->execute();
                            $up_new->close();
                        }
                    } else {
                        $up_new = $conn->prepare("UPDATE store_tbl SET delivery_status = 'accepted', delivered_at = NOW(), used_at = NOW(), return_reason = NULL, replace_new_id = NULL WHERE id = ?");
                        if ($up_new) {
                            $up_new->bind_param("i", $new_store_id);
                            $up_new->execute();
                            $up_new->close();
                        }
                    }

                    $new_desc = "Delivered to customer " . $customer_name . " (" . $customer_username . ") - Replaced old ONU " . $old_sn_display;
                    $hist_new = $conn->prepare("INSERT INTO store_item_history_tbl (store_id, action, description, action_by) VALUES (?, ?, ?, ?)");
                    if ($hist_new) {
                        $action_delivered = 'Delivered';
                        $hist_new->bind_param("isss", $new_store_id, $action_delivered, $new_desc, $action_by);
                        $hist_new->execute();
                        $hist_new->close();
                    }
                }
                $stmt_new->close();
            }

            // C. Create Ticket in support_ticket_tbl
            // User requirement:
            // and this record will set on this customer ticket- 
            // Onu replace- old onu sn $sn number, new onu $sn number
            $ticket_title = "ONU Replaced - Old: " . $old_sn_display . ", New: " . $new_onu_sn;
            $ticket_desc = "Onu replace- old onu sn " . $old_sn_display . ", new onu " . $new_onu_sn;
            $category_id = 2; // Technical Support
            $priority = 'Medium';
            $ticket_status = 'Completed';

            $stmt_ticket = $conn->prepare("INSERT INTO support_ticket_tbl (title, description, category_id, customer_id, priority, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_ticket) {
                $stmt_ticket->bind_param("ssiisss", $ticket_title, $ticket_desc, $category_id, $customer_id, $priority, $ticket_status, $action_by);
                $stmt_ticket->execute();
                $stmt_ticket->close();
            }
        }

        $conn->commit();
        $msg = 'Customer updated successfully!';
        if ($is_onu_replaced) {
            $msg = 'Customer updated and ONU replaced successfully!';
        }
        echo json_encode(['status' => 'success', 'message' => $msg . $mikrotik_error]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
