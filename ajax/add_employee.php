<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('employee_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $designation = $conn->real_escape_string(trim($_POST['designation'] ?? ''));
    $present_address = $conn->real_escape_string(trim($_POST['present_address'] ?? ''));
    $permanent_address = $conn->real_escape_string(trim($_POST['permanent_address'] ?? ''));
    $salary = (float)($_POST['salary'] ?? 0);
    $joining_date = $conn->real_escape_string(trim($_POST['joining_date'] ?? ''));

    if (empty($full_name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Full Name and Phone are required.']);
        exit;
    }

    // Handle File Uploads
    $profile_path = '';
    $nid_path = '';
    $upload_dir = '../uploads/';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'emp_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . 'employees/' . $filename)) {
            $profile_path = 'uploads/employees/' . $filename;
        }
    }

    if (isset($_FILES['nid_image']) && $_FILES['nid_image']['error'] == 0) {
        $ext = pathinfo($_FILES['nid_image']['name'], PATHINFO_EXTENSION);
        $filename = 'nid_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['nid_image']['tmp_name'], $upload_dir . 'nids/' . $filename)) {
            $nid_path = 'uploads/nids/' . $filename;
        }
    }

    $sql = "INSERT INTO employee_tbl (full_name, email, phone, designation, present_address, permanent_address, salary, joining_date, profile_image, nid_image) 
            VALUES ('$full_name', '$email', '$phone', '$designation', '$present_address', '$permanent_address', $salary, '$joining_date', '$profile_path', '$nid_path')";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Employee added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
