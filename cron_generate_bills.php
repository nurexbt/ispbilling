<?php
require_once __DIR__ . '/includes/db.php';

// This script is run via a cron job on the 1st of every month (or manually/scheduled).
// Rules:
// 1. In running month, it will NEVER generate next month's bill (e.g. in November, will NOT generate December bill).
// 2. If a customer already has a bill created for the running month (or future month), it will avoid/skip them.
// 3. Only generates bill for the running month if the customer does not have one and their cycle is due.

header('Content-Type: application/json');

$conn->begin_transaction();
try {
    $current_month = date('Y-m'); // e.g. '2026-11'
    $current_month_text = date('F-Y'); // e.g. 'November-2026'
    $next_month = date('Y-m', strtotime('first day of next month')); // e.g. '2026-12'
    $next_month_text = date('F-Y', strtotime('first day of next month')); // e.g. 'December-2026'
    
    // Select all active customers
    $query = "SELECT id, package_price, discount, previous_due, advance_payment, billing_date, payment_cycle_date 
              FROM customer_tbl 
              WHERE status = 'Active' FOR UPDATE";
    $result = $conn->query($query);
    
    $generated = 0;
    $skipped = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($customer = $result->fetch_assoc()) {
            $customer_id = (int)$customer['id'];
            
            // -------------------------------------------------------------
            // Condition 1: In running month, do NOT generate next month bill.
            // If customer's payment cycle date is already in next month or future,
            // they are already billed/scheduled for next month; skip them.
            // -------------------------------------------------------------
            if (!empty($customer['payment_cycle_date'])) {
                $cycle_month = date('Y-m', strtotime($customer['payment_cycle_date']));
                if ($cycle_month > $current_month) {
                    $skipped++;
                    continue; // Future month cycle; do NOT generate next month bill
                }
            }
            
            // If customer was newly added with billing date starting in next month or later
            if (!empty($customer['billing_date']) && empty($customer['payment_cycle_date'])) {
                $billing_month_val = date('Y-m', strtotime($customer['billing_date']));
                if ($billing_month_val > $current_month) {
                    $skipped++;
                    continue;
                }
            }
            
            // -------------------------------------------------------------
            // Condition 2: Avoid if running month bill (or future bill) already exists.
            // Check both 'YYYY-MM' and 'Month-YYYY' formats.
            // Exclude one-time fees ('Connection Fee', 'Deposit', 'Previous Due').
            // -------------------------------------------------------------
            $check = $conn->prepare("SELECT id FROM invoice_tbl 
                                     WHERE customer_id = ? 
                                       AND (
                                           billing_month = ? 
                                           OR billing_month = ? 
                                           OR billing_month = ? 
                                           OR billing_month = ? 
                                           OR billing_month >= ?
                                       ) 
                                       AND (description IS NULL OR description = '' OR description = 'Monthly Bill' OR description NOT IN ('Connection Fee', 'Deposit', 'Previous Due'))");
            $check->bind_param("isssss", $customer_id, $current_month, $current_month_text, $next_month, $next_month_text, $next_month);
            $check->execute();
            $check_res = $check->get_result();
            if ($check_res->num_rows > 0) {
                $check->close();
                $skipped++;
                continue; // Already generated running month (or future) bill; avoid it!
            }
            $check->close();
            
            $bill_amount = (float)$customer['package_price'] - (float)$customer['discount'];
            if ($bill_amount <= 0) {
                // If package is free or fully discounted, just advance payment cycle to next month
                $stmt = $conn->prepare("UPDATE customer_tbl SET payment_cycle_date = DATE_ADD(payment_cycle_date, INTERVAL 1 MONTH) WHERE id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $stmt->close();
                continue;
            }
            
            // -------------------------------------------------------------
            // Condition 3: Strictly enforce that the generated bill month is running month!
            // -------------------------------------------------------------
            $bill_month = $current_month;
            if ($bill_month > date('Y-m')) {
                $skipped++;
                continue; // Safety: Never create future month bill in running month
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
            
            // 1. Create Invoice Record for Running Month
            $stmt = $conn->prepare("INSERT INTO invoice_tbl (customer_id, amount, paid_amount, billing_month, status, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iddsss", $customer_id, $bill_amount, $paid_amount, $bill_month, $invoice_status, $processed_by);
            $stmt->execute();
            $stmt->close();
            
            // 1.5 Create Payment Record if Auto-Paid from Balance
            if ($paid_amount > 0) {
                $payment_date = date('Y-m-d');
                $payment_method = 'Adjust';
                $stmt = $conn->prepare("INSERT INTO payment_tbl (customer_id, amount, payment_date, payment_method, for_month, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idssss", $customer_id, $paid_amount, $payment_date, $payment_method, $bill_month, $processed_by);
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
            
            // 3. Update Customer Record (Advance payment_cycle_date to next month)
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
        'running_month' => $current_month,
        'generated' => $generated,
        'skipped' => $skipped
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
