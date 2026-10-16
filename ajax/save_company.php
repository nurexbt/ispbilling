<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('company_edit')) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$company_name = $_POST['company_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$website_url = $_POST['website_url'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($company_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Company Name is required']);
    exit;
}

// Ensure upload directory exists
$upload_dir = '../uploads/company/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Get current logo path
$current_logo = '';
$res = $conn->query("SELECT logo_path FROM company_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $current_logo = $res->fetch_assoc()['logo_path'];
}

$logo_path = $current_logo;

// Handle File Upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['logo'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (in_array(strtolower($ext), $allowed_ext)) {
        $filename = 'logo_' . time() . '.' . $ext;
        $dest = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $logo_path = 'uploads/company/' . $filename;
            
            // Delete old logo if it exists and isn't a default path outside uploads/company/
            if (!empty($current_logo) && strpos($current_logo, 'uploads/company/') === 0 && file_exists('../' . $current_logo)) {
                unlink('../' . $current_logo);
            }
        }
    }
}

// Check if a row exists
$check = $conn->query("SELECT id FROM company_tbl LIMIT 1");
if ($check && $check->num_rows > 0) {
    // Update existing row
    $stmt = $conn->prepare("UPDATE company_tbl SET company_name = ?, phone = ?, email = ?, address = ?, website_url = ?, logo_path = ?");
    $stmt->bind_param("ssssss", $company_name, $phone, $email, $address, $website_url, $logo_path);
} else {
    // Insert new row
    $stmt = $conn->prepare("INSERT INTO company_tbl (company_name, phone, email, address, website_url, logo_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $company_name, $phone, $email, $address, $website_url, $logo_path);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Company settings saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save settings: ' . $conn->error]);
}

$stmt->close();
?>
