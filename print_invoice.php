<?php
require_once 'includes/db.php';

$id = $_GET['id'] ?? 0;
$format = $_GET['format'] ?? 'general';

if (!$id) {
    die("Invalid ID");
}

// Fetch invoice
$stmt = $conn->prepare("SELECT i.*, c.name, c.phone, c.address AS c_address, c.username, e.full_name AS processed_by_name 
                        FROM invoice_tbl i 
                        JOIN customer_tbl c ON i.customer_id = c.id 
                        LEFT JOIN portal_user_tbl pu ON i.processed_by = pu.username
                        LEFT JOIN employee_tbl e ON pu.employee_id = e.id
                        WHERE i.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Invoice not found.");
}
$invoice = $res->fetch_assoc();
$stmt->close();

// Fetch company
$company = [
    'company_name' => 'Cyber Link Communication',
    'phone' => '',
    'email' => '',
    'address' => '',
    'website_url' => '',
    'logo_path' => ''
];
$res = $conn->query("SELECT * FROM company_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $company = $res->fetch_assoc();
}

$billing_month_formatted = date('F Y', strtotime($invoice['billing_month'] . '-01'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - #<?php echo $invoice['id']; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container-general {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .container-pos {
            width: 58mm; /* Standard 58mm narrow POS */
            max-width: 58mm;
            margin: 0 auto;
            padding: 5px;
            font-size: 11px;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .border-bottom { border-bottom: 1px dashed #ccc; }
        .header-logo { max-width: 100px; max-height: 80px; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; }
        .general-table th { background-color: #f8f9fa; border-bottom: 2px solid #ddd; }
        .general-table td { border-bottom: 1px solid #eee; }
        
        .pos-table th, .pos-table td { padding: 4px 0; font-size: 12px; }
        
        @media print {
            body { -webkit-print-color-adjust: exact; margin: 0; padding: 0; }
            @page { margin: 0; }
            .no-print { display: none; }
            .container-pos { padding: 2mm; width: 54mm; margin: 0 auto; }
        }
    </style>
</head>
<body onload="window.print()">

<?php if ($format === 'general'): ?>
<!-- General A4 Format -->
<div class="container-general">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;" class="border-bottom mb-4 pb-2">
        <div>
            <?php if (!empty($company['logo_path'])): ?>
            <img src="<?php echo $company['logo_path']; ?>" class="header-logo" alt="Logo">
            <?php endif; ?>
            <h2 style="margin: 0; color: #1e293b;"><?php echo htmlspecialchars($company['company_name']); ?></h2>
            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">
                <?php echo htmlspecialchars($company['address']); ?><br>
                Phone: <?php echo htmlspecialchars($company['phone']); ?><br>
                Email: <?php echo htmlspecialchars($company['email']); ?>
            </p>
        </div>
        <div class="text-right">
            <h1 style="margin: 0; color: #0ea5e9; text-transform: uppercase;">Invoice</h1>
            <p style="margin: 5px 0; font-size: 14px; color: #475569;">
                <strong>Invoice #:</strong> INV-<?php echo str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?><br>
                <strong>Date:</strong> <?php echo date('d M Y', strtotime($invoice['created_at'])); ?><br>
                <strong>Status:</strong> <?php echo $invoice['status']; ?>
            </p>
        </div>
    </div>

    <div class="mb-4">
        <h4 style="margin: 0 0 10px 0; color: #334155;">Billed To:</h4>
        <p style="margin: 0; font-size: 15px;">
            <strong><?php echo htmlspecialchars($invoice['name']); ?></strong> (<?php echo htmlspecialchars($invoice['username']); ?>)<br>
            Phone: <?php echo htmlspecialchars($invoice['phone']); ?><br>
            Address: <?php echo htmlspecialchars($invoice['c_address']); ?>
        </p>
    </div>

    <table class="general-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 12px 16px; color: #475569; font-size: 13px;">
                    <?php 
                        if (!empty($invoice['description'])) {
                            echo htmlspecialchars($invoice['description']);
                        } else {
                            echo 'Bill for: <strong>' . date('F Y', strtotime($invoice['billing_month'] . '-01')) . '</strong>';
                        }
                    ?>
                </td>
                <td style="padding: 12px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">&#2547;<?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <table style="width: 300px;">
                <tr>
                    <th class="text-right">Total Billed:</th>
                    <td class="text-right font-bold">৳<?php echo number_format($invoice['amount'], 2); ?></td>
                </tr>
                <?php if ($invoice['discount'] > 0): ?>
                <tr>
                    <th class="text-right text-muted">Discount:</th>
                    <td class="text-right text-muted">৳<?php echo number_format($invoice['discount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th class="text-right">Paid Amount:</th>
                    <td class="text-right">৳<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                </tr>
                <tr>
                    <th class="text-right" style="color: #ef4444;">Due Amount:</th>
                    <td class="text-right font-bold" style="color: #ef4444;">৳<?php echo number_format(max(0, $invoice['amount'] - $invoice['paid_amount'] - $invoice['discount']), 2); ?></td>
                </tr>
            </table>
        </div>

    <div style="margin-top: 50px; font-size: 12px; color: #94a3b8; text-align: center;">
        <p>Thank you for your business!</p>
        <?php $display_name = !empty($invoice['processed_by_name']) ? $invoice['processed_by_name'] : $invoice['processed_by']; ?>
        <p>Processed By: <strong><?php echo htmlspecialchars($display_name ?? 'System'); ?></strong></p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</div>

<?php else: ?>
<!-- POS Format -->
<div class="container-pos">
    <div class="text-center mb-2">
        <?php if (!empty($company['logo_path'])): ?>
        <img src="<?php echo $company['logo_path']; ?>" style="max-width: 50px; max-height: 50px;" alt="Logo">
        <?php endif; ?>
        <div class="font-bold" style="font-size: 14px;"><?php echo htmlspecialchars($company['company_name']); ?></div>
        <div style="font-size: 11px;"><?php echo htmlspecialchars($company['email']); ?>, <?php echo htmlspecialchars($company['phone']); ?></div>
        <div style="font-size: 11px; margin-top: 2px;"><?php echo htmlspecialchars($company['address']); ?></div>
    </div>
    
    <div class="border-bottom mb-2 text-center" style="padding-bottom: 5px;">
        <div class="font-bold">INVOICE</div>
        INV-<?php echo str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?>
    </div>

    <div class="mb-2 border-bottom" style="font-size: 11px; padding-bottom: 5px;">
        <div style="margin-bottom: 3px;"><strong>Date:</strong> <?php echo date('d M Y', strtotime($invoice['created_at'])); ?></div>
        <div style="display: flex; justify-content: space-between;">
            <span><?php echo htmlspecialchars($invoice['name']); ?></span>
            <span><?php echo htmlspecialchars($invoice['username']); ?></span>
        </div>
    </div>

    <table class="pos-table border-bottom mb-2">
        <tr>
            <td style="padding: 5px 0;">
                <?php 
                    if (!empty($invoice['description'])) {
                        echo htmlspecialchars($invoice['description']);
                    } else {
                        echo 'Bill for: ' . date('M-y', strtotime($invoice['billing_month'] . '-01'));
                    }
                ?>
            </td>
            <td class="text-right" style="padding: 5px 0;">&#2547;<?php echo number_format($invoice['amount'], 2); ?></td>
        </tr>
    </table>

        <table class="pos-table mb-2">
            <tr>
                <td>Total:</td>
                <td class="text-right font-bold">৳<?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
            <?php if ($invoice['discount'] > 0): ?>
            <tr>
                <td>Discount:</td>
                <td class="text-right text-muted">৳<?php echo number_format($invoice['discount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Paid:</td>
                <td class="text-right">৳<?php echo number_format($invoice['paid_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>Due:</td>
                <td class="text-right font-bold">৳<?php echo number_format(max(0, $invoice['amount'] - $invoice['paid_amount'] - $invoice['discount']), 2); ?></td>
            </tr>
        </table>
    
    <div class="text-center" style="font-size: 11px; margin-top: 15px;">
        *** Thank You ***<br>
        <?php $display_name_pos = !empty($invoice['processed_by_name']) ? $invoice['processed_by_name'] : $invoice['processed_by']; ?>
        <span style="font-size: 10px; color: #666;">Processed By: <?php echo htmlspecialchars($display_name_pos ?? 'System'); ?></span>
    </div>
</div>
<?php endif; ?>

</body>
</html>
