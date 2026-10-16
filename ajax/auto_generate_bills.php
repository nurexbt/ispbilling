<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$conn->begin_transaction();
try {
    // Select all customers where payment_cycle_date <= today and status is Active
    $query = "SELECT id, package_price, discount, previous_due, advance_payment, billing_date, payment_cycle_date 
              FROM customer_tbl 
              WHERE payment_cycle_date <= CURDATE() AND status = 'Active' FOR UPDATE";
    $result = $conn->query($query);
    
    $generated = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($customer = $result->fetch_assoc()) {
            $customer_id = $customer['id'];
            $bill_amount = (float)$customer['package_price'] - (float)$customer['discount'];
            
            if ($bill_amount <= 0) {
                // If package is free or fully discounted, just advance the dates
                $stmt = $conn->prepare("UPDATE customer_tbl SET billing_date = DATE_ADD(billing_date, INTERVAL 1 MONTH), payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH) WHERE id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $stmt->close();
                continue;
            }
            
            $billing_month = date('Y-m', strtotime($customer['payment_cycle_date']));
            
            $advance = (float)$customer['advance_payment'];
            
            $invoice_status = 'Unpaid';
            $paid_amount = 0;
            
            if ($advance >= $bill_amount) {
                $invoice_status = 'Paid';
                $paid_amount = $bill_amount;
            } elseif ($advance > 0) {
                $invoice_status = 'Partial';
                $paid_amount = $advance;
            }
            
            $processed_by = $_SESSION['username'] ?? 'System';
            
            // 1. Create Invoice Record
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, status, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iddsss", $customer_id, $bill_amount, $paid_amount, $billing_month, $invoice_status, $processed_by);
            $stmt->execute();
            $invoice_id = $stmt->insert_id;
            $stmt->close();
            
            // 1.5 Create Payment Record if Auto-Paid from Balance
            if ($paid_amount > 0) {
                $payment_date = date('Y-m-d');
                $payment_method = 'Adjust';
                $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, payment_date, payment_method, for_month, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idssss", $customer_id, $paid_amount, $payment_date, $payment_method, $billing_month, $processed_by);
                $stmt->execute();
                $stmt->close();
            }
            
            // 2. Adjust Balance
            $due = (float)$customer['previous_due'];
            $remaining_bill = $bill_amount;
            
            if ($advance > 0) {
                if ($advance >= $remaining_bill) {
                    $advance -= $remaining_bill;
                    $remaining_bill = 0;
                } else {
                    $remaining_bill -= $advance;
                    $advance = 0;
                }
            }
            
            if ($remaining_bill > 0) {
                $due += $remaining_bill;
            }
            
            // 3. Update Customer Record
            if ($invoice_status === 'Paid') {
                $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ?, billing_date = DATE_ADD(billing_date, INTERVAL 1 MONTH), payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH) WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE customer_tbl SET previous_due = ?, advance_payment = ?, payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH) WHERE id = ?");
            }
            $stmt->bind_param("ddi", $due, $advance, $customer_id);
            $stmt->execute();
            $stmt->close();
            
            $generated++;
        }
    }
    
    $conn->commit();
    echo json_encode(['status' => 'success', 'generated' => $generated]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
