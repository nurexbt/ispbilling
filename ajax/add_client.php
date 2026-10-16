<?php
require_once '../includes/db.php';
require_once '../includes/routeros_api.class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $router_id = $_POST['router_id'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $pppoe_status = $_POST['pppoe_status'] ?? 'enable';
    
    // Check if required fields are provided
    if (empty($request_id) || empty($router_id) || empty($package_id) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    // Always attempt to create PPPoE user on router
    // Fetch host info
    $stmt = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ?");
    $stmt->bind_param("i", $router_id);
    $stmt->execute();
    $host_res = $stmt->get_result();
    
    if ($host_res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Selected router not found.']);
        exit;
    }
    $host = $host_res->fetch_assoc();
    $stmt->close();
    
    // Fetch package profile name
    $stmt = $conn->prepare("SELECT original_profile FROM package_tbl WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $pkg_res = $stmt->get_result();
    
    if ($pkg_res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Selected package not found.']);
        exit;
    }
    $pkg = $pkg_res->fetch_assoc();
    $stmt->close();
    
    // Connect to RouterOS
    $API = new RouterosAPI();
    $API->debug = false;
    $API->port = (int)$host['api_port'];
    
    if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
        // Add PPPoE User
        $API->write('/ppp/secret/add', false);
        $API->write('=name=' . $username, false);
        $API->write('=password=' . $password, false);
        $API->write('=service=pppoe', false);
        $API->write('=profile=' . $pkg['original_profile'], false);
        
        if ($pppoe_status === 'disable') {
            $API->write('=disabled=yes', false);
        }
        
        $API->write('=comment=' . $name . '-' . $phone . '-' . $address);
        
        $READ = $API->read(false);
        $ARRAY = $API->parseResponse($READ);
        $API->disconnect();
        
        // Check if there's an error from the router (e.g., user already exists)
        if (isset($ARRAY['!trap'])) {
            $error_message = $ARRAY['!trap'][0]['message'] ?? 'Failed to create PPPoE user on router.';
            echo json_encode(['status' => 'error', 'message' => 'Router API Error: ' . $error_message]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to connect to the Router API. Check connection.']);
        exit;
    }

    // Insert into customer_tbl
    $client_note = $_POST['client_note'] ?? '';
    $email = $_POST['email'] ?? '';
    $area_id = !empty($_POST['area_id']) ? $_POST['area_id'] : null;
    $subarea_id = !empty($_POST['subarea_id']) ? $_POST['subarea_id'] : null;
    $package_price = $_POST['package_price'] ?? 0;
    $connection_date = $_POST['connection_date'] ?? date('Y-m-d');
    $billing_date = $_POST['billing_date'] ?? date('Y-m-d');
    $payment_cycle_date = $_POST['payment_cycle_date'] ?? date('Y-m-d');
    $connection_fee = $_POST['connection_fee'] ?? 0;
    $deposit = $_POST['deposit'] ?? 0;
    $advance_payment = $_POST['advance_payment'] ?? 0;
    $onu_sn = $_POST['onu_sn'] ?? '';
    $discount = $_POST['discount'] ?? 0;
    $is_existing_customer = $_POST['is_existing_customer'] ?? 0;
    $previous_due = (float)($_POST['previous_due'] ?? 0.00);
    $nid_no = $_POST['nid_no'] ?? '';
    
    // Create initial balance
    $total_due_to_save = $previous_due + (float)$connection_fee + (float)$deposit;

    $onu_submitted_by = null;
    if (!empty($request_id)) {
        $req_res = $conn->query("SELECT onu_submitted_by FROM connection_request_tbl WHERE id = " . intval($request_id));
        if ($req_res && $req_res->num_rows > 0) {
            $req_row = $req_res->fetch_assoc();
            $onu_submitted_by = $req_row['onu_submitted_by'];
        }
    }

    $stmt = $conn->prepare("INSERT INTO customer_tbl (name, nid_no, phone, email, area_id, subarea_id, address, router_id, package_id, username, password, package_price, discount, connection_date, billing_date, payment_cycle_date, connection_fee, deposit, advance_payment, pppoe_status, note, onu_sn, is_existing_customer, previous_due, onu_submitted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssiisiissddsssdddsssiis", $name, $nid_no, $phone, $email, $area_id, $subarea_id, $address, $router_id, $package_id, $username, $password, $package_price, $discount, $connection_date, $billing_date, $payment_cycle_date, $connection_fee, $deposit, $advance_payment, $pppoe_status, $client_note, $onu_sn, $is_existing_customer, $total_due_to_save, $onu_submitted_by);
        $stmt->execute();
        $customer_id = $stmt->insert_id;
        $stmt->close();
        
        $processed_by = $_SESSION['username'] ?? 'System';
        
        if ((float)$connection_fee > 0) {
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Connection Fee', 'Unpaid', ?)");
            $bm = date('Y-m');
            $stmt->bind_param("idss", $customer_id, $connection_fee, $bm, $processed_by);
            $stmt->execute();
            $stmt->close();
        }
        
        if ((float)$deposit > 0) {
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Deposit', 'Unpaid', ?)");
            $bm = date('Y-m');
            $stmt->bind_param("idss", $customer_id, $deposit, $bm, $processed_by);
            $stmt->execute();
            $stmt->close();
        }
        
        if ((float)$previous_due > 0) {
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, description, status, processed_by) VALUES (?, ?, 0, ?, 'Previous Due', 'Unpaid', ?)");
            $bm = date('Y-m');
            $stmt->bind_param("idss", $customer_id, $previous_due, $bm, $processed_by);
            $stmt->execute();
            $stmt->close();
        }
        
        $bill_amount = (float)$package_price - (float)$discount;
        if ($bill_amount > 0) {
            // Generate the first month's bill
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, status, processed_by) VALUES (?, ?, 0, ?, 'Unpaid', ?)");
            $bm = date('Y-m', strtotime($payment_cycle_date));
            $stmt->bind_param("idss", $customer_id, $bill_amount, $bm, $processed_by);
            $stmt->execute();
            $stmt->close();
            
            // Advance payment_cycle_date by 1 month and update due
            $stmt = $conn->prepare("UPDATE customer_tbl SET payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH), previous_due = previous_due + ? WHERE id = ?");
            $stmt->bind_param("di", $bill_amount, $customer_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Update connection request status and save pppoe username
    $status = 'Service created';
    $stmt = $conn->prepare("UPDATE connection_request_tbl SET status = ?, pppoe_username = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $username, $request_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['status' => 'success', 'message' => 'Client added successfully and status updated!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error while updating status.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
