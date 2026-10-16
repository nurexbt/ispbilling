<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!has_permission('package_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host_id = (int)($_POST['host_id'] ?? 0);
    $packages_json = $_POST['packages'] ?? '';
    $packages = json_decode($packages_json, true);

    if (empty($host_id) || empty($packages) || !is_array($packages)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
        exit;
    }

    $successCount = 0;
    $errors = [];

    $stmt = $conn->prepare("INSERT INTO package_tbl (host_id, original_profile, given_name, price) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database preparation error.']);
        exit;
    }

    foreach ($packages as $pkg) {
        $orig = trim($pkg['original_profile']);
        $given = trim($pkg['given_name']);
        $price = (float)$pkg['price'];

        // Check if package already exists for this host
        $check_stmt = $conn->prepare("SELECT id FROM package_tbl WHERE host_id = ? AND original_profile = ?");
        $check_stmt->bind_param("is", $host_id, $orig);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $errors[] = "$orig already exists.";
            $check_stmt->close();
            continue;
        }
        $check_stmt->close();

        $stmt->bind_param("issd", $host_id, $orig, $given, $price);
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "Failed to add $orig.";
        }
    }
    $stmt->close();

    if ($successCount > 0) {
        $msg = "Successfully added $successCount package(s).";
        if (count($errors) > 0) {
            $msg .= " Some skipped: " . implode(", ", $errors);
        }
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No packages were added. ' . implode(", ", $errors)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
