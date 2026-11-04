<?php
/**
 * ajax/sync_mikrotik_status.php
 * Synchronizes PPPoE secret statuses from Mikrotik routers into customer_tbl.pppoe_status
 */

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/routeros_api.class.php';

// Auth check for web requests
if (!$is_cli) {
    require_once __DIR__ . '/../includes/auth.php';
    if (!has_permission('customer_view') && !has_permission('customer_edit')) {
        echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
        exit;
    }
}

// Optional router_id filter
$filter_router_id = isset($_REQUEST['router_id']) ? intval($_REQUEST['router_id']) : 0;

// Fetch active routers
$router_query = "SELECT id, device_name, ip_address, api_username, api_password, api_port FROM host_tbl WHERE is_disabled = 0";
if ($filter_router_id > 0) {
    $router_query .= " AND id = " . $filter_router_id;
}
$router_res = $conn->query($router_query);

if (!$router_res || $router_res->num_rows === 0) {
    $resp = [
        'status' => 'error',
        'message' => 'No active Mikrotik routers found to sync.'
    ];
    if ($is_cli) {
        echo json_encode($resp, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo json_encode($resp);
    }
    exit;
}

$routers = [];
while ($r = $router_res->fetch_assoc()) {
    $routers[] = $r;
}

// Fetch all customer usernames from DB for fast in-memory lookup
$cust_res = $conn->query("SELECT id, username, router_id, pppoe_status FROM customer_tbl WHERE username IS NOT NULL AND username != ''");
$db_customers = [];
if ($cust_res) {
    while ($c = $cust_res->fetch_assoc()) {
        $db_customers[$c['username']] = [
            'id' => (int)$c['id'],
            'status' => $c['pppoe_status'] ?? 'enable',
            'router_id' => (int)$c['router_id']
        ];
    }
}

$update_stmt = $conn->prepare("UPDATE customer_tbl SET pppoe_status = ? WHERE id = ?");

$total_checked = 0;
$updated_count = 0;
$connected_routers = 0;
$failed_routers = [];
$status_map = [];

foreach ($routers as $router) {
    $API = new RouterosAPI();
    $API->debug = false;
    $API->port = (int)$router['api_port'];
    $API->timeout = 5; // 5 seconds connection timeout

    $device_label = (!empty($router['device_name']) ? $router['device_name'] : $router['ip_address']);

    if (!$API->connect($router['ip_address'], $router['api_username'], $router['api_password'])) {
        $failed_routers[] = $device_label;
        continue;
    }

    $connected_routers++;

    // Fetch secrets from router
    $secrets = $API->comm("/ppp/secret/print");

    if (is_array($secrets)) {
        foreach ($secrets as $secret) {
            if (!isset($secret['name'])) continue;

            $uname = $secret['name'];
            $is_disabled = isset($secret['disabled']) && ($secret['disabled'] === 'true' || $secret['disabled'] === true || $secret['disabled'] == 1);
            $router_status = $is_disabled ? 'disable' : 'enable';
            $status_map[$uname] = $is_disabled ? 'Disabled' : 'Enabled';

            // Check if user exists in database
            if (isset($db_customers[$uname])) {
                $total_checked++;
                $current_db_status = $db_customers[$uname]['status'];

                // If DB status differs from router status, sync to DB!
                if ($current_db_status !== $router_status) {
                    $cust_id = $db_customers[$uname]['id'];
                    $update_stmt->bind_param("si", $router_status, $cust_id);
                    if ($update_stmt->execute()) {
                        $db_customers[$uname]['status'] = $router_status;
                        $updated_count++;
                    }
                }
            }
        }
    }

    $API->disconnect();
}

$update_stmt->close();

$message = "Synced {$connected_routers} router(s). {$updated_count} customer status(es) updated in database.";
if (!empty($failed_routers)) {
    $message .= " Could not connect to: " . implode(', ', $failed_routers) . ".";
}

$response = [
    'status' => 'success',
    'message' => $message,
    'connected_routers' => $connected_routers,
    'failed_routers' => $failed_routers,
    'updated_count' => $updated_count,
    'total_checked' => $total_checked,
    'router_statuses' => $status_map
];

if ($is_cli) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . " (Checked: {$total_checked}, Updated: {$updated_count})\n";
} else {
    echo json_encode($response);
}

