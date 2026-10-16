<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('expense_add') && !has_permission('txbill_full_access')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    
    if (empty($amount) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Amount and category are required']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        $created_by = $_SESSION['user_id'] ?? null;
        $stmt = $conn->prepare("INSERT INTO expense_tbl (amount, category, note, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("dssi", $amount, $category, $note, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving expense: " . $stmt->error);
        }
        
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // Handle File Uploads
        if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
            $upload_dir = '../assets/uploads/expenses/';
            
            // Create directory if not exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_count = count($_FILES['documents']['name']);
            
            $doc_stmt = $conn->prepare("INSERT INTO expense_documents_tbl (expense_id, file_path) VALUES (?, ?)");

            for ($i = 0; $i < $file_count; $i++) {
                $file_name = $_FILES['documents']['name'][$i];
                $file_tmp = $_FILES['documents']['tmp_name'][$i];
                
                // Get extension
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Generate unique filename
                $new_file_name = 'exp_' . $expense_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                
                // The URL path to store in DB
                $db_path = 'assets/uploads/expenses/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $doc_stmt->bind_param("is", $expense_id, $db_path);
                    if (!$doc_stmt->execute()) {
                        throw new Exception("Error saving document record: " . $doc_stmt->error);
                    }
                }
            }
            $doc_stmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Expense added successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
