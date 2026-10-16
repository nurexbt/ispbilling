<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/routeros_api.class.php';

header('Content-Type: application/json');

$today = date('Y-m-d');

$query = "SELECT c.id, c.username, c.router_id, c.billing_date, c.is_special_user, c.previous_due, c.promise_date, h.ip_address, h.api_port, h.api_username, h.api_password 
          FROM customer_tbl c
          JOIN host_tbl h ON c.router_id = h.id
          WHERE c.status = 'Active' AND c.pppoe_status = 'enable'";

$result = $conn->query($query);

$processed = 0;
$disabled_count = 0;
$skipped_count = 0;
$errors = [];

if ($result && $result->num_rows > 0) {
    while ($customer = $result->fetch_assoc()) {
        $processed++;
        $should_disable = false;
        
        // 1. check - Bill date expired?
        if (!empty($customer['billing_date']) && $today > $customer['billing_date']) {
            
            // 2. check Special User?
            if ($customer['is_special_user'] == 1) {
                $skipped_count++;
                continue; // don't disable
            }
            
            // 3. check Status? Paid/Unpaid (Unpaid means previous_due > 0)
            if ($customer['previous_due'] > 0) {
                
                // 4. check promise date have?
                if (!empty($customer['promise_date'])) {
                    
                    // 5. check promise date expired?
                    if ($today > $customer['promise_date']) {
                        $should_disable = true;
                    } else {
                        $skipped_count++;
                        continue; // don't disable
                    }
                } else {
                    // No promise date, so disable
                    $should_disable = true;
                }
            } else {
                $skipped_count++;
                continue; // Paid, don't disable
            }
        } else {
            $skipped_count++;
            continue; // Bill date not expired
        }
        
        if ($should_disable) {
            // Disable PPPoE secret in Mikrotik
            $API = new RouterosAPI();
            $API->debug = false;
            $API->port = (int)$customer['api_port'];

            if ($API->connect($customer['ip_address'], $customer['api_username'], $customer['api_password'])) {
                // Find the PPPoE secret by username
                $API->write('/ppp/secret/print', false);
                $API->write('?name=' . $customer['username'], true);
                $READ = $API->read(false);
                $ARRAY = $API->parseResponse($READ);

                if (!empty($ARRAY)) {
                    $secret_id = $ARRAY[0]['.id'];

                    // Update the PPPoE secret status
                    $API->write('/ppp/secret/set', false);
                    $API->write('=.id=' . $secret_id, false);
                    $API->write('=disabled=yes', true);

                    $READ = $API->read(false);
                    $response = $API->parseResponse($READ);
                    
                    if (isset($response['!trap'])) {
                        $errors[] = "Router API Error for {$customer['username']}: " . ($response['!trap'][0]['message'] ?? 'Unknown');
                    } else {
                        // Update database status
                        $stmt = $conn->prepare("UPDATE customer_tbl SET pppoe_status = 'disable', status = 'Inactive' WHERE id = ?");
                        $stmt->bind_param("i", $customer['id']);
                        $stmt->execute();
                        $stmt->close();
                        
                        $disabled_count++;
                    }
                } else {
                    $errors[] = "Secret for {$customer['username']} not found on router.";
                }
                
                $API->disconnect();
            } else {
                $errors[] = "Failed to connect to router for {$customer['username']}.";
            }
        }
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Expiry cron execution completed.',
    'processed' => $processed,
    'disabled' => $disabled_count,
    'skipped' => $skipped_count,
    'errors' => $errors
]);
?>
