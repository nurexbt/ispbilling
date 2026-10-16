<?php
require_once '../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salary_id'])) {
    $salary_id = intval($_POST['salary_id']);
    
    // Check if file was uploaded
    $document_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/salary_docs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['document']['name']);
        $ext = strtolower($file_info['extension']);
        // Allow images and pdf
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($ext, $allowed_ext)) {
            $new_filename = 'salary_' . $salary_id . '_' . time() . '.' . $ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                $document_path = 'uploads/salary_docs/' . $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: jpg, jpeg, png, pdf.']);
            exit;
        }
    }
    
    if ($document_path) {
        $stmt = $conn->prepare("UPDATE salary_tbl SET status = 'Paid', document_path = ? WHERE id = ?");
        $stmt->bind_param("si", $document_path, $salary_id);
    } else {
        $stmt = $conn->prepare("UPDATE salary_tbl SET status = 'Paid' WHERE id = ?");
        $stmt->bind_param("i", $salary_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Salary paid successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
