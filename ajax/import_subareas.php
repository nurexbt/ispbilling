<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!has_permission('area_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }

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

                $idx_subarea_name = $getIndex('Sub Area Name', 1);
                $idx_parent_area = $getIndex('Parent Area Name', 2);

                $action = $_POST['action'] ?? 'import';

                $conn->begin_transaction();
                try {
                    $checkArea = $conn->prepare("SELECT id FROM area_tbl WHERE area_name = ? LIMIT 1");
                    $checkDuplicate = $conn->prepare("SELECT id FROM subarea_tbl WHERE subarea_name = ? AND area_id = ? LIMIT 1");
                    $insertSubArea = $conn->prepare("INSERT INTO subarea_tbl (subarea_name, area_id) VALUES (?, ?)");

                    $successCount = 0;
                    $ready = [];
                    $skipped = [];

                    while (($row = fgetcsv($file, 10000, ",")) !== FALSE) {
                        $subarea_name = $row[$idx_subarea_name] ?? '';
                        if (empty($subarea_name)) continue;

                        $parent_area = $row[$idx_parent_area] ?? '';
                        
                        $area_id = NULL;
                        if (!empty($parent_area)) {
                            $checkArea->bind_param("s", $parent_area);
                            $checkArea->execute();
                            $res = $checkArea->get_result();
                            if ($res->num_rows > 0) {
                                $area_id = $res->fetch_assoc()['id'];
                            }
                        }
                        
                        if (!$area_id) {
                            $skipped[] = ['name' => $subarea_name, 'reason' => "Parent area '$parent_area' not found"];
                            continue;
                        }

                        $isDuplicate = false;
                        $skipReason = '';
                        $checkDuplicate->bind_param("si", $subarea_name, $area_id);
                        $checkDuplicate->execute();
                        if ($checkDuplicate->get_result()->num_rows > 0) {
                            $isDuplicate = true;
                            $skipReason = 'Sub area already exists under this parent.';
                        }

                        if ($isDuplicate) {
                            $skipped[] = ['name' => $subarea_name, 'reason' => $skipReason];
                            continue;
                        }

                        if ($action === 'preview') {
                            $ready[] = ['name' => $subarea_name, 'username' => $parent_area];
                            continue;
                        }

                        $insertSubArea->bind_param("si", $subarea_name, $area_id);
                        if($insertSubArea->execute()) {
                            $successCount++;
                        }
                    }

                    if ($action === 'preview') {
                        $conn->rollback();
                        echo json_encode(['success' => true, 'ready' => $ready, 'skipped' => $skipped]);
                    } else {
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => "Successfully imported $successCount sub areas!"]);
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
