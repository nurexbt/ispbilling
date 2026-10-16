<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!has_permission('package_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

        if (!empty($_FILES['csv_file']['name']) && in_array($_FILES['csv_file']['type'], $csvMimes)) {
            if (is_uploaded_file($tmpName)) {
                $file = fopen($tmpName, 'r');
                $headers = fgetcsv($file);
                if (!$headers) {
                    echo json_encode(['success' => false, 'message' => 'Empty CSV file.']);
                    exit;
                }
                $headerMap = array_flip(array_map('strtolower', array_map('trim', $headers)));
                
                $getIndex = function($headerName, $defaultIndex) use ($headerMap) {
                    $key = strtolower(trim($headerName));
                    return isset($headerMap[$key]) ? $headerMap[$key] : $defaultIndex;
                };

                $idx_given_name = $getIndex('Given Name', 1);
                $idx_original_profile = $getIndex('Original PPP Profile', 2);
                $idx_router_name = $getIndex('Router Name', 3);
                $idx_price = $getIndex('Price', 4);

                $action = $_POST['action'] ?? 'import';

                $conn->begin_transaction();
                try {
                    $checkRouter = $conn->prepare("SELECT id FROM host_tbl WHERE device_name = ? LIMIT 1");
                    $checkDuplicate = $conn->prepare("SELECT id FROM package_tbl WHERE given_name = ? AND host_id = ? LIMIT 1");
                    $insertPackage = $conn->prepare("INSERT INTO package_tbl (given_name, original_profile, host_id, price) VALUES (?, ?, ?, ?)");

                    $successCount = 0;
                    $ready = [];
                    $skipped = [];

                    while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
                        $given_name = $row[$idx_given_name] ?? '';
                        if (empty($given_name)) continue;

                        $original_profile = $row[$idx_original_profile] ?? '';
                        $router_name = $row[$idx_router_name] ?? '';
                        $price = $row[$idx_price] ?? 0;
                        
                        $router_id = NULL;
                        if (!empty($router_name)) {
                            $checkRouter->bind_param("s", $router_name);
                            $checkRouter->execute();
                            $res = $checkRouter->get_result();
                            if ($res->num_rows > 0) {
                                $router_id = $res->fetch_assoc()['id'];
                            }
                        }
                        
                        if (!$router_id) {
                            $skipped[] = ['name' => $given_name, 'reason' => 'Router not found'];
                            continue;
                        }

                        $isDuplicate = false;
                        $skipReason = '';
                        $checkDuplicate->bind_param("si", $given_name, $router_id);
                        $checkDuplicate->execute();
                        if ($checkDuplicate->get_result()->num_rows > 0) {
                            $isDuplicate = true;
                            $skipReason = 'Package already exists on this router.';
                        }

                        if ($isDuplicate) {
                            $skipped[] = ['name' => $given_name, 'reason' => $skipReason];
                            continue;
                        }

                        if ($action === 'preview') {
                            $ready[] = ['name' => $given_name, 'username' => $original_profile];
                            continue;
                        }

                        $insertPackage->bind_param("ssid", $given_name, $original_profile, $router_id, $price);
                        if($insertPackage->execute()) {
                            $successCount++;
                        }
                    }

                    if ($action === 'preview') {
                        $conn->rollback();
                        echo json_encode(['success' => true, 'ready' => $ready, 'skipped' => $skipped]);
                    } else {
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => "Successfully imported $successCount packages!"]);
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
