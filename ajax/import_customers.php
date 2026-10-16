<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

require_once '../includes/routeros_api.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!has_permission('customer_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

        if (!empty($_FILES['csv_file']['name']) && in_array($_FILES['csv_file']['type'], $csvMimes)) {
            if (is_uploaded_file($tmpName)) {
                $file = fopen($tmpName, 'r');
                // Read headers and build a map
                $headers = fgetcsv($file);
                if (!$headers) {
                    echo json_encode(['success' => false, 'message' => 'Empty CSV file.']);
                    exit;
                }
                $headerMap = array_flip(array_map('strtolower', array_map('trim', $headers)));
                
                // Helper to get index by header name, fallback to default index if header missing
                $getIndex = function($headerName, $defaultIndex) use ($headerMap) {
                    $key = strtolower(trim($headerName));
                    return isset($headerMap[$key]) ? $headerMap[$key] : $defaultIndex;
                };

                $idx_name = $getIndex('Name', 1);
                $idx_nid = $getIndex('NID', 2);
                $idx_phone = $getIndex('Phone', 3);
                $idx_email = $getIndex('Email', 4);
                $idx_area = $getIndex('Area', 5);
                $idx_subarea = $getIndex('Subarea', 6);
                $idx_address = $getIndex('Address', 7);
                $idx_router = $getIndex('Router', 8);
                $idx_package = $getIndex('Package', 9);
                $idx_username = $getIndex('PPPoE Username', 10);
                $idx_password = $getIndex('PPPoE Password', 11);
                $idx_onu = $getIndex('ONU MAC/SN', 12);
                $idx_pkg_price = $getIndex('Package Price', 13);
                $idx_discount = $getIndex('Discount', 14);
                $idx_conn_fee = $getIndex('Connection Fee', 15);
                $idx_deposit = $getIndex('Deposit', 16);
                $idx_advance = $getIndex('Advance Payment', 17);
                $idx_prev_due = $getIndex('Previous Due', 18);
                $idx_conn_date = $getIndex('Connection Date', 19);
                $idx_bill_date = $getIndex('Billing Date', 20);
                $idx_cycle_date = $getIndex('Billing Cycle Date', 21);
                $idx_paid_status = $getIndex('Paid Unpaid Status', 22);
                $idx_mikrotik_status = $getIndex('Mikrotik Status', 23);
                $idx_status = $getIndex('Status', 24);

                $add_to_mikrotik = isset($_POST['add_to_mikrotik']) && $_POST['add_to_mikrotik'] == '1';
                $generate_bill = !isset($_POST['generate_bill']) || $_POST['generate_bill'] === 'yes';
                $action = $_POST['action'] ?? 'import';

                $conn->begin_transaction();
                try {
                    // Prepared statements
                    $checkArea = $conn->prepare("SELECT id FROM area_tbl WHERE area_name = ? LIMIT 1");
                    $checkSubarea = $conn->prepare("SELECT id FROM subarea_tbl WHERE subarea_name = ? LIMIT 1");
                    $checkRouter = $conn->prepare("SELECT id, ip_address, api_port, api_username, api_password FROM host_tbl WHERE device_name = ? LIMIT 1");
                    $checkPackage = $conn->prepare("SELECT id, original_profile FROM package_tbl WHERE given_name = ? LIMIT 1");
                    $checkDuplicate = $conn->prepare("SELECT id FROM customer_tbl WHERE username = ? LIMIT 1");
                    
                    $insertCustomer = $conn->prepare("INSERT INTO customer_tbl (name, nid_no, phone, email, area_id, subarea_id, address, router_id, package_id, username, password, onu_sn, package_price, discount, connection_fee, deposit, advance_payment, previous_due, connection_date, billing_date, payment_cycle_date, pppoe_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $successCount = 0;
                    $ready = [];
                    $skipped = [];
                    $mikrotikCache = []; // Cache to store existing users per router

                    while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
                        $name = $row[$idx_name] ?? '';
                        if (empty($name)) continue;

                        $nid = $row[$idx_nid] ?? '';
                        $phone = $row[$idx_phone] ?? '';
                        $email = $row[$idx_email] ?? '';
                        $username = $row[$idx_username] ?? '';
                        $password = $row[$idx_password] ?? '';

                        // 1. Check local DB duplicate
                        $isDuplicate = false;
                        $skipReason = '';
                        if (!empty($username)) {
                            $checkDuplicate->bind_param("s", $username);
                            $checkDuplicate->execute();
                            if ($checkDuplicate->get_result()->num_rows > 0) {
                                $isDuplicate = true;
                                $skipReason = 'Username already exists in database.';
                            }
                        }

                        // Resolve Router ID and fetch router details
                        $router_name = $row[$idx_router] ?? '';
                        $router_id = NULL;
                        $router_details = NULL;
                        if (!empty($router_name)) {
                            $checkRouter->bind_param("s", $router_name);
                            $checkRouter->execute();
                            $res = $checkRouter->get_result();
                            if ($res->num_rows > 0) {
                                $router_details = $res->fetch_assoc();
                                $router_id = $router_details['id'];
                            }
                        }

                        // 2. Check Mikrotik duplicate if needed
                        if (!$isDuplicate && $add_to_mikrotik && $router_id && !empty($username)) {
                            // If we haven't fetched users for this router yet, fetch and cache them
                            if (!isset($mikrotikCache[$router_id])) {
                                $mikrotikCache[$router_id] = []; // Default empty
                                $API = new RouterosAPI();
                                $API->debug = false;
                                $API->port = (int)$router_details['api_port'];
                                
                                if ($API->connect($router_details['ip_address'], $router_details['api_username'], $router_details['api_password'])) {
                                    $API->write('/ppp/secret/print');
                                    $secrets = $API->read();
                                    foreach ($secrets as $secret) {
                                        if (isset($secret['name'])) {
                                            $mikrotikCache[$router_id][] = $secret['name'];
                                        }
                                    }
                                    $API->disconnect();
                                } else {
                                    $skipReason = "Could not connect to Mikrotik router '{$router_name}'.";
                                    $isDuplicate = true;
                                }
                            }

                            // Check if username is in the cached Mikrotik users
                            if (!$isDuplicate && in_array($username, $mikrotikCache[$router_id])) {
                                $isDuplicate = true;
                                $skipReason = "Username already exists on Mikrotik router '{$router_name}'.";
                            }
                        }

                        if ($isDuplicate) {
                            $skipped[] = ['name' => $name, 'username' => $username, 'reason' => $skipReason];
                            continue;
                        }

                        // If it's just a preview, mark as ready and move to next
                        if ($action === 'preview') {
                            $ready[] = ['name' => $name, 'username' => $username];
                            continue;
                        }

                        // --- IMPORT LOGIC ---
                        
                        // Resolve Area ID
                        $area_name = $row[$idx_area] ?? '';
                        $area_id = NULL;
                        if (!empty($area_name)) {
                            $checkArea->bind_param("s", $area_name);
                            $checkArea->execute();
                            $res = $checkArea->get_result();
                            if ($res->num_rows > 0) $area_id = $res->fetch_assoc()['id'];
                        }

                        // Resolve Subarea ID
                        $subarea_name = $row[$idx_subarea] ?? '';
                        $subarea_id = NULL;
                        if (!empty($subarea_name)) {
                            $checkSubarea->bind_param("s", $subarea_name);
                            $checkSubarea->execute();
                            $res = $checkSubarea->get_result();
                            if ($res->num_rows > 0) $subarea_id = $res->fetch_assoc()['id'];
                        }

                        $address = $row[$idx_address] ?? '';

                        // Resolve Package ID and fetch original profile
                        $package_name = $row[$idx_package] ?? '';
                        $package_id = NULL;
                        $package_profile = NULL;
                        if (!empty($package_name)) {
                            $checkPackage->bind_param("s", $package_name);
                            $checkPackage->execute();
                            $res = $checkPackage->get_result();
                            if ($res->num_rows > 0) {
                                $pkg_details = $res->fetch_assoc();
                                $package_id = $pkg_details['id'];
                                $package_profile = $pkg_details['original_profile'];
                            }
                        }

                        $onu = $row[$idx_onu] ?? '';
                        $pkg_price = $row[$idx_pkg_price] ?? 0;
                        $discount = $row[$idx_discount] ?? 0;
                        $conn_fee = $row[$idx_conn_fee] ?? 0;
                        $deposit = $row[$idx_deposit] ?? 0;
                        $advance = $row[$idx_advance] ?? 0;
                        $prev_due = $row[$idx_prev_due] ?? 0;
                        $conn_date = !empty($row[$idx_conn_date]) ? date('Y-m-d', strtotime($row[$idx_conn_date])) : date('Y-m-d');
                        $bill_date = !empty($row[$idx_bill_date]) ? date('Y-m-d', strtotime($row[$idx_bill_date])) : date('Y-m-d');
                        $cycle_date = !empty($row[$idx_cycle_date]) ? date('Y-m-d', strtotime($row[$idx_cycle_date])) : $bill_date;
                        $mikrotik_status = !empty($row[$idx_mikrotik_status]) ? strtolower($row[$idx_mikrotik_status]) : 'enable';
                        $status = $row[$idx_status] ?? 'Active';
                        
                        $total_due_to_save = (float)$prev_due + (float)$conn_fee + (float)$deposit;

                        // Insert into database
                        $insertCustomer->bind_param("ssssiisiisssddddddsssss", $name, $nid, $phone, $email, $area_id, $subarea_id, $address, $router_id, $package_id, $username, $password, $onu, $pkg_price, $discount, $conn_fee, $deposit, $advance, $total_due_to_save, $conn_date, $bill_date, $cycle_date, $mikrotik_status, $status);
                        if($insertCustomer->execute()) {
                            $customer_id = $insertCustomer->insert_id;
                            $successCount++;
                            
                            $processed_by = $_SESSION['username'] ?? 'System';
                            $bm = date('Y-m');
                            
                            if ((float)$conn_fee > 0) {
                                $stmtInv = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Connection Fee', 'Unpaid', ?)");
                                $stmtInv->bind_param("idss", $customer_id, $conn_fee, $bm, $processed_by);
                                $stmtInv->execute();
                                $stmtInv->close();
                            }
                            
                            if ((float)$deposit > 0) {
                                $stmtInv = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Deposit', 'Unpaid', ?)");
                                $stmtInv->bind_param("idss", $customer_id, $deposit, $bm, $processed_by);
                                $stmtInv->execute();
                                $stmtInv->close();
                            }
                            
                            if ((float)$prev_due > 0) {
                                $stmtInv = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Previous Due', 'Unpaid', ?)");
                                $stmtInv->bind_param("idss", $customer_id, $prev_due, $bm, $processed_by);
                                $stmtInv->execute();
                                $stmtInv->close();
                            }

                            if ($generate_bill) {
                                $bill_amount = (float)$pkg_price - (float)$discount;
                                if ($bill_amount > 0) {
                                    $stmtInv = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, status, processed_by) VALUES (?, ?, 0, ?, 'Unpaid', ?)");
                                    $bm_cycle = date('Y-m', strtotime($bill_date));
                                    $stmtInv->bind_param("idss", $customer_id, $bill_amount, $bm_cycle, $processed_by);
                                    $stmtInv->execute();
                                    $stmtInv->close();
                                    
                                    $stmtUpd = $conn->prepare("UPDATE customer_tbl SET payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH), previous_due = previous_due + ? WHERE id = ?");
                                    $stmtUpd->bind_param("di", $bill_amount, $customer_id);
                                    $stmtUpd->execute();
                                    $stmtUpd->close();
                                }
                            } else {
                                $stmtUpd = $conn->prepare("UPDATE customer_tbl SET payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH) WHERE id = ?");
                                $stmtUpd->bind_param("i", $customer_id);
                                $stmtUpd->execute();
                                $stmtUpd->close();
                            }

                            // Add PPPoE user to Mikrotik if requested and Router/Package are valid
                            if ($add_to_mikrotik && $router_id && $package_id && !empty($username) && !empty($password)) {
                                $API = new RouterosAPI();
                                $API->debug = false;
                                $API->port = (int)$router_details['api_port'];
                                
                                if ($API->connect($router_details['ip_address'], $router_details['api_username'], $router_details['api_password'])) {
                                    $API->write('/ppp/secret/add', false);
                                    $API->write('=name=' . $username, false);
                                    $API->write('=password=' . $password, false);
                                    $API->write('=service=pppoe', false);
                                    $API->write('=profile=' . $package_profile, false);
                                    $API->write('=comment=' . $name . '-' . $phone . '-' . $address);
                                    $API->read(false);
                                    $API->disconnect();
                                }
                            }
                        }
                    }

                    if ($action === 'preview') {
                        $conn->rollback(); // No changes to save during preview
                        echo json_encode(['success' => true, 'ready' => $ready, 'skipped' => $skipped]);
                    } else {
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => "Successfully imported $successCount customers!"]);
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                fclose($file);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload a CSV file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please select a file to upload.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
