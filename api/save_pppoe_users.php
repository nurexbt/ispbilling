<?php
header('Content-Type: application/json');
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check for JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $router_id = $input['router_id'] ?? 0;
    $users = $input['users'] ?? [];
} else {
    $router_id = $_POST['router_id'] ?? 0;
    $users = $_POST['users'] ?? [];
}

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router ID is missing']);
    exit;
}

if (empty($users)) {
    echo json_encode(['success' => false, 'message' => 'No users data received']);
    exit;
}

// Fetch packages for mapping and validation
$packages = [];
$p_stmt = $conn->prepare("SELECT original_name, given_name FROM packages WHERE router_id = ?");
$p_stmt->bind_param("i", $router_id);
$p_stmt->execute();
$p_result = $p_stmt->get_result();
while ($row = $p_result->fetch_assoc()) {
    $packages[$row['original_name']] = $row['given_name'];
}
$p_stmt->close();

$count = 0;
$errors = [];
// Note: given_profile is initially set to original_profile, and billing_date defaults to 1
$stmt = $conn->prepare("INSERT INTO pppoe_users (router_id, username, password, original_profile, given_profile, comment, billing_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
$check_stmt = $conn->prepare("SELECT id FROM pppoe_users WHERE router_id = ? AND username = ?");

foreach ($users as $user) {
    if (isset($user['selected']) && $user['selected'] == 1) {
        $username = $user['username'];
        $password = $user['password'];
        $profile = $user['profile'];
        $comment = $user['comment'];
        $today = new DateTime();
        $day = (int)$today->format('d');
        if ($day <= 10) {
            $billing_date = $today->format('Y-m-10');
        } else {
            $today->modify('first day of next month');
            $billing_date = $today->format('Y-m-10');
        }

        // Validate Profile
        if (!isset($packages[$profile])) {
            $errors[] = "User '$username': Profile '$profile' not found in packages.";
            continue;
        }
        $given_profile = $packages[$profile];

        // Check if user already exists for this router
        $check_stmt->bind_param("is", $router_id, $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $stmt->bind_param("issssss", $router_id, $username, $password, $profile, $given_profile, $comment, $billing_date);
            if ($stmt->execute()) {
                $count++;
            }
        }
    }
}

$check_stmt->close();
$stmt->close();
$conn->close();

$response = ['success' => true];
$message = "";

if ($count > 0) {
    $message .= "$count users added successfully. ";
} else {
    $message .= "No new users were added. ";
}

if (!empty($errors)) {
    $response['success'] = false; // Or partial success? Let's keep it mostly false if there are errors to alert user.
    if ($count > 0) $response['success'] = true; // If we saved some, it's a partial success.
    
    $message .= count($errors) . " users failed due to missing profiles.";
    $response['errors'] = $errors;
}

$response['message'] = trim($message);
echo json_encode($response);
?>
