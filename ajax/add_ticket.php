<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('support_ticket_add') && !has_permission('txbill_full_access') && !has_permission('customer_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$title = trim($_POST['title'] ?? '');
// Allow some HTML from Summernote for description, but not script tags
$description = strip_tags($_POST['description'] ?? '', '<p><br><b><strong><i><em><u><ul><ol><li><a>'); 
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
$customer_id = isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? intval($_POST['customer_id']) : null;
$priority = $_POST['priority'] ?? 'Medium';
$status = $_POST['status'] ?? 'Pending';
$created_by = $_SESSION['username'] ?? 'System';

if (empty($title) || empty(trim(strip_tags($description)))) {
    echo json_encode(['status' => 'error', 'message' => 'Title and description are required.']);
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO support_ticket_tbl (title, description, category_id, customer_id, priority, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisss", $title, $description, $category_id, $customer_id, $priority, $status, $created_by);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error inserting ticket: " . $stmt->error);
    }
    
    $ticket_id = $stmt->insert_id;
    $stmt->close();
    
    // Process Attachments
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $upload_dir = '../assets/uploads/tickets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $total_files = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $file_name = $_FILES['attachments']['name'][$i];
            $file_tmp = $_FILES['attachments']['tmp_name'][$i];
            $file_size = $_FILES['attachments']['size'][$i];
            $file_error = $_FILES['attachments']['error'][$i];
            
            if ($file_error === UPLOAD_ERR_OK) {
                // Check size (10MB limit)
                if ($file_size > 10 * 1024 * 1024) {
                    throw new Exception("File {$file_name} exceeds 10MB limit.");
                }
                
                // Sanitize filename and create unique path
                $clean_filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
                $unique_filename = time() . '_' . $i . '_' . $clean_filename;
                $destination = $upload_dir . $unique_filename;
                $db_path = 'assets/uploads/tickets/' . $unique_filename;
                
                if (move_uploaded_file($file_tmp, $destination)) {
                    $stmt_att = $conn->prepare("INSERT INTO ticket_attachment_tbl (ticket_id, file_name, file_path) VALUES (?, ?, ?)");
                    $stmt_att->bind_param("iss", $ticket_id, $clean_filename, $db_path);
                    $stmt_att->execute();
                    $stmt_att->close();
                } else {
                    throw new Exception("Failed to upload file {$file_name}.");
                }
            }
        }
    }
    
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Ticket created successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
