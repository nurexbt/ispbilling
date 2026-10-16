<?php
require_once '../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $salary_month = isset($_POST['salary_month']) ? $conn->real_escape_string(trim($_POST['salary_month'])) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($employee_id <= 0 || empty($salary_month) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and amount must be greater than 0.']);
        exit;
    }

    // Check if salary is already generated for this month for this employee
    $check_query = "SELECT id FROM salary_tbl WHERE employee_id = $employee_id AND salary_month = '$salary_month'";
    $check_result = $conn->query($check_query);
    if ($check_result && $check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Salary already generated for this month.']);
        exit;
    }

    // Insert salary record
    $query = "INSERT INTO salary_tbl (employee_id, salary_month, amount) VALUES ($employee_id, '$salary_month', $amount)";
    
    if ($conn->query($query) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Salary generated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
