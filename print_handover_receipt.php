<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Invalid Handover ID.";
    exit;
}

$query = "SELECT * FROM collector_handover_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "Handover record not found.";
    exit;
}

$handover = $res->fetch_assoc();

// Fetch company
$company = [
    'company_name' => 'Cyber Link Communication',
    'phone' => '',
    'email' => '',
    'address' => '',
    'website_url' => '',
    'logo_path' => ''
];
$res_company = $conn->query("SELECT * FROM company_tbl LIMIT 1");
if ($res_company && $res_company->num_rows > 0) {
    $company = $res_company->fetch_assoc();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'normal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Receipt #<?= $handover['id'] ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php if ($type === 'pos'): ?>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            color: #000;
            background-color: #fff;
            width: 58mm;
            font-size: 12px;
        }
        .receipt-container {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
            box-shadow: none;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .receipt-header h2 {
            margin: 0 0 5px;
            font-size: 16px;
            font-weight: bold;
        }
        .receipt-header p {
            margin: 0;
            font-size: 10px;
        }
        .receipt-details {
            margin-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .detail-label, .detail-value {
            font-size: 11px;
        }
        .amount-row {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .amount-label {
            font-weight: bold;
            font-size: 12px;
        }
        .amount-value {
            font-weight: bold;
            font-size: 14px;
        }
        .receipt-footer {
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
        }
        .print-btn {
            display: block;
            width: 100%;
            padding: 8px;
            background-color: #000;
            color: #fff;
            border: none;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
            font-family: 'Courier New', Courier, monospace;
        }
        @media print {
            .print-btn { display: none; }
            body { width: 58mm; margin: 0; padding: 0; }
        }
        <?php else: ?>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8fafc;
            color: #334155;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 24px;
            font-weight: 700;
        }
        .receipt-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
        }
        .detail-value {
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }
        .amount-row {
            background-color: #f0fdf4;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .amount-label {
            color: #166534;
            font-weight: 600;
            font-size: 16px;
        }
        .amount-value {
            color: #15803d;
            font-weight: 700;
            font-size: 20px;
        }
        .receipt-footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 30px;
        }
        .print-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.2s;
        }
        .print-btn:hover {
            background-color: #2563eb;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }
            .print-btn {
                display: none;
            }
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?= $type === 'pos' ? 'pos-receipt' : '' ?>">

<div class="receipt-container">
    <div class="receipt-header">
        <?php if(!empty($company['logo_path']) && file_exists($company['logo_path'])): ?>
            <img src="<?= htmlspecialchars($company['logo_path']) ?>" alt="Logo" style="max-height: 50px; margin-bottom: 5px;">
        <?php endif; ?>
        <h1 style="margin: 0; font-size: <?= $type === 'pos' ? '18px' : '20px' ?>; font-weight: bold;"><?= htmlspecialchars($company['company_name']) ?></h1>
        <?php if(!empty($company['address'])): ?>
            <p style="margin: 2px 0; font-size: <?= $type === 'pos' ? '10px' : '12px' ?>;"><?= htmlspecialchars($company['address']) ?></p>
        <?php endif; ?>
        <?php if(!empty($company['phone'])): ?>
            <p style="margin: 2px 0 10px 0; font-size: <?= $type === 'pos' ? '10px' : '12px' ?>;">Phone: <?= htmlspecialchars($company['phone']) ?></p>
        <?php endif; ?>
        <h2 style="border-top: 1px dashed <?= $type === 'pos' ? '#000' : '#cbd5e1' ?>; padding-top: <?= $type === 'pos' ? '5px' : '10px' ?>; margin-top: <?= $type === 'pos' ? '5px' : '10px' ?>;">Cash Handover Receipt</h2>
        <p>Receipt ID: #<?= str_pad($handover['id'], 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <div class="receipt-details">
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value"><?= date('d M, Y h:i A', strtotime($handover['handover_date'])) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Processor</span>
            <span class="detail-value"><?= htmlspecialchars($handover['processor_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Received By</span>
            <span class="detail-value"><?= htmlspecialchars($handover['received_by']) ?></span>
        </div>
        <?php if(!empty($handover['for_date'])): ?>
        <div class="detail-row">
            <span class="detail-label">Collection Date</span>
            <span class="detail-value"><?= date('d M, Y', strtotime($handover['for_date'])) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="amount-row">
        <span class="amount-label">Total Amount</span>
        <span class="amount-value">৳<?= number_format($handover['amount'], 2) ?></span>
    </div>

    <button class="print-btn" onclick="window.print()">Print Receipt</button>

    <div class="receipt-footer">
        Generated by BillSoft at <?= date('d M, Y h:i A') ?>
    </div>
</div>

</body>
</html>
