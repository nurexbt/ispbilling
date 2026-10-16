<?php
require_once __DIR__ . '/includes/db.php';

// This script is meant to be run via a cron job on the 1st of every month.
// It will generate the "Monthly Bill" for the current month for all active customers,
// ensuring no duplicates are created.

header('Content-Type: application/json');

$conn->begin_transaction();
try {
    $current_month = date('Y-m');
    
    // Select all active customers
    $query = "SELECT id, package_price, discount, previous_due, advance_payment 
              FROM customer_tbl 
              WHERE status = 'Active' FOR UPDATE";
    $result = $conn->query($query);
    
    $generated = 0;
    $skipped = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($customer = $result->fetch_assoc()) {
            $customer_id = $customer['id'];
            
            // Check if monthly bill already generated for this month
            $check = $conn->prepare("SELECT id FROM invoice_tbl WHERE customer_id = ? AND billing_month = ? AND (description IS NULL OR description = '')");
            $check->bind_param("is", $customer_id, $current_month);
            $check->execute();
            $check_res = $check->get_result();
            if ($check_res->num_rows > 0) {
                $check->close();
                $skipped++;
                continue; // Already generated
            }
            $check->close();
            
            $bill_amount = (float)$customer['package_price'] - (float)$customer['discount'];
            if ($bill_amount <= 0) {
                continue; // No bill to generate
            }
            
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
            
            $processed_by = 'Cron System';
            
            // 1. Create Invoice Record
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, status, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iddsss", $customer_id, $bill_amount, $paid_amount, $current_month, $invoice_status, $processed_by);
            $stmt->execute();
            $stmt->close();
            
            // 1.5 Create Payment Record if Auto-Paid from Balance
            if ($paid_amount > 0) {
                $payment_date = date('Y-m-d');
                $payment_method = 'Adjust';
                $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, payment_date, payment_method, for_month, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idssss", $customer_id, $paid_amount, $payment_date, $payment_method, $current_month, $processed_by);
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
    echo json_encode([
        'status' => 'success', 
        'message' => "Cron execution completed.",
        'generated' => $generated,
        'skipped' => $skipped
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
