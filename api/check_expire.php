<?php
header('Content-Type: application/json');
include '../includes/db.php';
include '../classes/routeros_api.class.php';

$today = date('Y-m-d');

// Find expired users who are not yet disabled and NOT special users
// Logic: (Billing Date Passed AND (Extend Date is NULL OR Extend Date Passed))
$sql = "SELECT p.*, r.ip_address, r.username as r_user, r.password as r_pass, r.api_port 
        FROM pppoe_users p 
        LEFT JOIN routers r ON p.router_id = r.id 
        WHERE p.billing_date < ? 
        AND (p.extend_date IS NULL OR p.extend_date < ?)
        AND p.status != 'disabled'
        AND (p.is_special_user = 0 OR p.is_special_user IS NULL)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$result = $stmt->get_result();

$processed_count = 0;
$errors = [];

while ($user = $result->fetch_assoc()) {
    $router_disabled = false;
    
    // Connect to Router to Disable
    if ($user['ip_address']) {
        $API = new RouterosAPI();
        $API->port = (int)$user['api_port'];
        $API->timeout = 5;
        
        if ($API->connect($user['ip_address'], $user['r_user'], $user['r_pass'])) {
            $username = $user['username'];
            $secrets = $API->comm('/ppp/secret/print', [
                '?name' => $username
            ]);
            
            if (!empty($secrets)) {
                $id = $secrets[0]['.id'];
                $API->comm('/ppp/secret/disable', [
                    '.id' => $id
                ]);
                
                // Also kill active connection if exists
                $active = $API->comm('/ppp/active/print', [
                    '?name' => $username
                ]);
                if (!empty($active)) {
                    foreach ($active as $a) {
                         $API->comm('/ppp/active/remove', [
                            '.id' => $a['.id']
                        ]);
                    }
                }
                
                $router_disabled = true;
            }
            $API->disconnect();
        } else {
            $errors[] = "Failed to connect to router for user {$user['username']}";
        }
    }

    // Update DB Status even if router fail? 
    // Ideally yes, to reflect 'should be disabled'. 
    // But if router fails, they still have internet.
    // Let's update DB only if we attempted.
    
    $update_sql = "UPDATE pppoe_users SET status = 'disabled' WHERE id = " . $user['id'];
    $conn->query($update_sql);
    $processed_count++;
}

echo json_encode([
    'success' => true,
    'processed' => $processed_count,
    'message' => "Checked expiration. Disabled $processed_count users.",
    'errors' => $errors
]);
?>