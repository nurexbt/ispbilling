<?php
require_once '../includes/db.php';
require_once '../includes/routeros_api.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? '';
    if (empty($customer_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Customer ID is required.']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $onu_sn = $_POST['onu_sn'] ?? '';
    $area_id = $_POST['area_id'] ?? null;
    $subarea_id = $_POST['subarea_id'] ?? null;
    $address = $_POST['address'] ?? '';
    $nid_no = $_POST['nid_no'] ?? '';
    $router_id = $_POST['router_id'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $discount = $_POST['discount'] ?? 0;
    $package_price = $_POST['package_price'] ?? 0;
    $connection_date = $_POST['connection_date'] ?? date('Y-m-d');
    $billing_date = $_POST['billing_date'] ?? date('Y-m-d');
    $payment_cycle_date = $_POST['payment_cycle_date'] ?? date('Y-m-d');
    $client_note = $_POST['client_note'] ?? '';

    $is_special = isset($_POST['is_special']) ? 1 : 0;

    // Fetch existing customer to see if Mikrotik update is needed
    $stmt = $conn->prepare("SELECT username, password, router_id, package_id FROM customer_tbl WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $old_cust_res = $stmt->get_result();
    $old_cust = $old_cust_res->fetch_assoc();
    $stmt->close();

    $mikrotik_error = '';

    // If Mikrotik fields changed, update router
    if ($old_cust && ($old_cust['username'] !== $username || $old_cust['password'] !== $password || $old_cust['package_id'] != $package_id)) {
        // Fetch host info
        $stmt = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ?");
        $stmt->bind_param("i", $router_id);
        $stmt->execute();
        $host_res = $stmt->get_result();
        $host = $host_res->fetch_assoc();
        $stmt->close();

        // Fetch package profile name
        $stmt = $conn->prepare("SELECT original_profile FROM package_tbl WHERE id = ?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $pkg_res = $stmt->get_result();
        $pkg = $pkg_res->fetch_assoc();
        $stmt->close();

        if ($host && $pkg) {
            $API = new RouterosAPI();
            $API->debug = false;
            $API->port = (int)$host['api_port'];
            
            if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
                // Find existing secret by old username
                $API->write('/ppp/secret/print', false);
                $API->write('?name=' . $old_cust['username'], true);
                $READ = $API->read(false);
                $ARRAY = $API->parseResponse($READ);

                if (!empty($ARRAY)) {
                    $secret_id = $ARRAY[0]['.id'];
                    $API->write('/ppp/secret/set', false);
                    $API->write('=.id=' . $secret_id, false);
                    $API->write('=name=' . $username, false);
                    $API->write('=password=' . $password, false);
                    $API->write('=profile=' . $pkg['original_profile'], false);
                    $API->write('=comment=' . $name . '-' . $phone . '-' . $address);
                    
                    $res = $API->read(false);
                    $err = $API->parseResponse($res);
                    if (isset($err['!trap'])) {
                        $mikrotik_error = ' (Mikrotik Update Error: ' . ($err['!trap'][0]['message'] ?? 'Unknown error') . ')';
                    }
                } else {
                    $mikrotik_error = " (Mikrotik Note: Old PPPoE user '{$old_cust['username']}' not found, skipping update.)";
                }
                $API->disconnect();
            } else {
                $mikrotik_error = " (Mikrotik Warning: Failed to connect to Router. Router fields not updated there.)";
            }
        }
    }

    // Update database
    $stmt = $conn->prepare("UPDATE customer_tbl SET name=?, phone=?, email=?, onu_sn=?, area_id=?, subarea_id=?, address=?, nid_no=?, router_id=?, package_id=?, username=?, password=?, discount=?, package_price=?, is_special=?, connection_date=?, billing_date=?, payment_cycle_date=?, note=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("ssssiissiissssissssi", $name, $phone, $email, $onu_sn, $area_id, $subarea_id, $address, $nid_no, $router_id, $package_id, $username, $password, $discount, $package_price, $is_special, $connection_date, $billing_date, $payment_cycle_date, $client_note, $customer_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully!' . $mikrotik_error]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error while updating customer.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database prepare statement error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
