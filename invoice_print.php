<?php
include 'includes/db.php';

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$invoice_id) {
    die("Invalid Invoice ID");
}

// Fetch Invoice Data with User Details
$sql = "SELECT b.*, u.username, u.full_name, u.address, u.phone, u.given_profile, u.original_profile, 
               pay.payment_date 
        FROM pppoe_billings b 
        JOIN pppoe_users u ON b.user_id = u.id 
        LEFT JOIN pppoe_payments pay ON b.payment_id = pay.id 
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die("Invoice not found");
}

// Company Details (Hardcoded for now, or fetch from settings if available)
$company_name = "Khan Internet Service";
$company_address = "123 Main Street, City, Country";
$company_phone = "+880 1234 567890";
$company_email = "support@khanisp.com";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .invoice-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        .invoice-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-logo {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-details-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .table-invoice th {
            background-color: #f8f9fa;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media print {
            body {
                background-color: white;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="invoice-container">
        <!-- Actions -->
        <div class="mb-4 text-end no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Invoice</button>
            <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times"></i> Close</button>
        </div>

        <!-- Header -->
        <div class="row invoice-header align-items-center">
            <div class="col-6">
                <div class="invoice-logo"><?php echo $company_name; ?></div>
                <div class="text-muted small mt-2">
                    <?php echo $company_address; ?><br>
                    <?php echo $company_phone; ?><br>
                    <?php echo $company_email; ?>
                </div>
            </div>
            <div class="col-6 invoice-title">
                <h2 class="fw-bold text-uppercase text-secondary">Invoice</h2>
                <div class="text-muted">#<?php echo $invoice['invoice_number']; ?></div>
                <div class="badge <?php echo $invoice['status'] == 'paid' ? 'bg-success' : 'bg-danger'; ?> fs-6 mt-2">
                    <?php echo strtoupper($invoice['status']); ?>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="text-muted text-uppercase small fw-bold">Bill To</h6>
                <h5 class="fw-bold mb-1"><?php echo $invoice['full_name'] ?: $invoice['username']; ?></h5>
                <div class="text-muted">
                    <?php if($invoice['address']): ?><?php echo $invoice['address']; ?><br><?php endif; ?>
                    <?php if($invoice['phone']): ?>Phone: <?php echo $invoice['phone']; ?><br><?php endif; ?>
                    User ID: <?php echo $invoice['username']; ?>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="invoice-details-box text-start d-inline-block" style="min-width: 250px;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Invoice Date:</span>
                        <span class="fw-bold"><?php echo date('d M, Y', strtotime($invoice['bill_date'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Due Date:</span>
                        <span class="fw-bold"><?php echo date('d M, Y', strtotime($invoice['due_date'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Billing Month:</span>
                        <span class="fw-bold"><?php echo date('F Y', strtotime($invoice['bill_month'])); ?></span>
                    </div>
                    <?php if($invoice['status'] == 'paid' && !empty($invoice['payment_date'])): ?>
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <span class="text-success fw-bold">Paid On:</span>
                        <span class="fw-bold"><?php echo date('d M, Y h:i A', strtotime($invoice['payment_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Table -->
        <table class="table table-invoice mb-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-bold">Broadband Internet Service</div>
                        <div class="text-muted small">Package: <?php echo $invoice['given_profile'] ?: $invoice['original_profile']; ?></div>
                    </td>
                    <td class="text-end fw-bold"><?php echo number_format($invoice['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="total-section">
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Subtotal:</td>
                            <td class="text-end fw-bold"><?php echo number_format($invoice['amount'], 2); ?></td>
                        </tr>
                        <tr class="border-top">
                            <td class="fs-5 fw-bold">Total:</td>
                            <td class="text-end fs-5 fw-bold text-primary"><?php echo number_format($invoice['amount'], 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p class="small">This is a computer-generated invoice and does not require a signature.</p>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
