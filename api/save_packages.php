<?php
header('Content-Type: application/json');
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$router_id = $_POST['router_id'] ?? 0;
$profiles = $_POST['profiles'] ?? [];

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router ID is missing']);
    exit;
}

if (empty($profiles)) {
    echo json_encode(['success' => false, 'message' => 'No profiles data received']);
    exit;
}

$count = 0;
$stmt = $conn->prepare("INSERT INTO packages (router_id, original_name, given_name, price) VALUES (?, ?, ?, ?)");

foreach ($profiles as $profile) {
    if (isset($profile['selected']) && $profile['selected'] == 1) {
        $original_name = $profile['original_name'];
        $given_name = $profile['given_name'];
        $price = floatval($profile['price']);

        // Check if package already exists for this router
        $check = $conn->query("SELECT id FROM packages WHERE router_id = $router_id AND original_name = '$original_name'");
        if ($check->num_rows == 0) {
            $stmt->bind_param("issd", $router_id, $original_name, $given_name, $price);
            if ($stmt->execute()) {
                $count++;
            }
        }
    }
}

$stmt->close();
$conn->close();

if ($count > 0) {
    echo json_encode(['success' => true, 'message' => "$count packages synced successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => "No new packages were saved (duplicates may exist)"]);
}
?>