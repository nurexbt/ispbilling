<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$id = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$ip = $_POST['ip'] ?? '';
$port = $_POST['port'] ?? 8728;
$user = $_POST['user'] ?? '';
$pass = $_POST['pass'] ?? '';

if (!$id || empty($name) || empty($ip) || empty($user)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Fetch current details to get password if not provided
$stmt = $conn->prepare("SELECT password FROM routers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Router not found.']);
    exit;
}
$currentRouter = $res->fetch_assoc();
$stmt->close();

// If password is empty, use the current one
$passwordToUse = empty($pass) ? $currentRouter['password'] : $pass;

// Validate Connection with new/current details
$API = new RouterosAPI();
$API->port = (int)$port;

if ($API->connect($ip, $user, $passwordToUse)) {
    
    // Check Identity
    $identityData = $API->comm('/system/identity/print');
    $routerIdentity = '';
    if (isset($identityData[0]['name'])) {
        $routerIdentity = $identityData[0]['name'];
    }
    $API->disconnect();

    if (trim($routerIdentity) === trim($name)) {
        // Update DB
        $sql = "UPDATE routers SET name=?, ip_address=?, username=?, api_port=?";
        $types = "sssi";
        $params = [$name, $ip, $user, $port];

        if (!empty($pass)) {
            $sql .= ", password=?";
            $types .= "s";
            $params[] = $pass;
        }

        $sql .= " WHERE id=?";
        $types .= "i";
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Router updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();

    } else {
        echo json_encode([
            'success' => false, 
            'message' => "Identity Mismatch! Your Input: '$name', Router Identity: '$routerIdentity'"
        ]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Connection Failed! Check details.']);
}

$conn->close();
?>