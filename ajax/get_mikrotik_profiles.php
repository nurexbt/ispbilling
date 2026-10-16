<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

require_once '../includes/routeros_api.class.php';
header('Content-Type: application/json');

if (!has_permission('package_add')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host_id = (int)($_POST['host_id'] ?? 0);

    if (empty($host_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a Router Host first.']);
        exit;
    }

    $sql = "SELECT * FROM host_tbl WHERE id = $host_id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $host = $result->fetch_assoc();

        if ($host['is_disabled'] == 1) {
            echo json_encode(['status' => 'error', 'message' => 'This device is currently disabled in your settings.']);
            exit;
        }

        $API = new RouterosAPI();
        $API->debug = false;
        $API->port = (int)$host['api_port'];

        if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
            $profiles = $API->comm('/ppp/profile/print');
            $API->disconnect();
            
            // Debugging
            file_put_contents('debug.txt', print_r($profiles, true));

            // Check for API errors (trap)
            if (isset($profiles['!trap'])) {
                $errorMsg = $profiles['!trap'][0]['message'] ?? 'Unknown Mikrotik API error';
                echo json_encode(['status' => 'error', 'message' => 'Mikrotik Error: ' . $errorMsg]);
                exit;
            }

            $profile_names = [];
            if (is_array($profiles)) {
                foreach ($profiles as $p) {
                    if(isset($p['name'])) {
                        $profile_names[] = $p['name'];
                    }
                }
            }
            
            if(count($profile_names) > 0) {
                echo json_encode(['status' => 'success', 'data' => $profile_names]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No PPP profiles found on this router.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Connection failed. Ensure the router is Online and API credentials are correct.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Router Host not found in database.']);
    }
}
?>
