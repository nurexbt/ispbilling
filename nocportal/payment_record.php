<?php
require_once '../includes/db.php';
$base_path = '../';
$is_noc = true;
$active_menu = 'payment_record';
$page_title = 'Payment Records';
$page_subtitle = 'All customer payment transactions';
include '../includes/header.php';

if (!has_permission('payment_record_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view payment records.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Fetch filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_method = $_GET['method'] ?? '';
$filter_search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_date_from)) {
    $where_clauses[] = "p.payment_date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "p.payment_date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}
if (!empty($filter_method)) {
    $where_clauses[] = "p.payment_method = ?";
    $params[] = $filter_method;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT 
    p.id,
    p.payment_date,
    p.amount,
    p.payment_method,
    p.for_month,
    p.processed_by,
    c.name AS customer_name,
    c.username AS pppoe_username
  FROM payment_tbl p
  JOIN customer_tbl c ON p.customer_id = c.id
  WHERE $where_sql
  ORDER BY p.payment_date DESC, p.id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$total_cash = 0;
$total_bkash = 0;
$total_bank = 0;
$total_adjust = 0;
$total_other = 0;

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    switch ($row['payment_method']) {
        case 'Cash': $total_cash += $row['amount']; break;
        case 'Bkash': $total_bkash += $row['amount']; break;
        case 'Bank Transfer': $total_bank += $row['amount']; break;
        case 'Adjust': $total_adjust += $row['amount']; break;
        default: $total_other += $row['amount']; break;
    }
}
$stmt->close();
$grand_total = $total_cash + $total_bkash + $total_bank + $total_other;
?>

<div class="container-fluid pt-2">

    <!-- Summary Cards -->
    <div class="row g-3 mt-1 mb-3">
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #22c55e !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Cash</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_cash, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #f59e0b !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Bkash</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_bkash, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #3b82f6 !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Bank Transfer</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_bank, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #a855f7 !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Adjust</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_adjust, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #64748b !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Other</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_other, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #0ea5e9 !important; border-radius: 10px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Collected</div>
                <div class="fw-bold" style="font-size: 16px; color: #0ea5e9;">৳<?= number_format($grand_total, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="dashboard-card p-0 border-0" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden;">

        <!-- Toolbar / Filters -->
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center" id="filterForm">
                    <input type="date" name="date_from" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_from) ?>" style="width: 135px; font-size: 12px;" placeholder="From Date" title="From Date">
                    <input type="date" name="date_to" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_to) ?>" style="width: 135px; font-size: 12px;" placeholder="To Date" title="To Date">
                    <select name="method" class="form-select form-select-sm border-slate-200 shadow-none" style="width: 130px; font-size: 12px;">
                        <option value="">All Methods</option>
                        <option value="Cash" <?= $filter_method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Bkash" <?= $filter_method === 'Bkash' ? 'selected' : '' ?>>Bkash</option>
                        <option value="Bank Transfer" <?= $filter_method === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        <option value="Adjust" <?= $filter_method === 'Adjust' ? 'selected' : '' ?>>Adjust</option>
                        <option value="Other" <?= $filter_method === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary shadow-none px-3" style="font-size: 12px;"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="payment_record.php" class="btn btn-sm btn-light border-slate-200 shadow-none px-3" style="font-size: 12px;"><i class="bi bi-x-circle me-1"></i>Clear</a>
                </form>
            </div>
            <div>
                <input type="text" id="searchPaymentRecord" class="toolbar-input" placeholder="Search records..." style="width: 200px;">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="paymentRecordTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Date</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Customer</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">PPPoE</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Bill / For</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Payment Type</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Amount</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Processed By</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if (count($payments) > 0): ?>
                        <?php $i = 1; foreach ($payments as $pay): ?>
                        <?php
                            // Determine badge color for payment method
                            $methodClass = 'bg-secondary';
                            $methodIcon = 'bi-cash';
                            if ($pay['payment_method'] === 'Cash') { $methodClass = 'bg-success'; $methodIcon = 'bi-cash'; }
                            elseif ($pay['payment_method'] === 'Bkash') { $methodClass = 'bg-warning text-dark'; $methodIcon = 'bi-phone'; }
                            elseif ($pay['payment_method'] === 'Bank Transfer') { $methodClass = 'bg-primary'; $methodIcon = 'bi-bank'; }
                            elseif ($pay['payment_method'] === 'Adjust') { $methodClass = 'bg-purple text-white'; $methodIcon = 'bi-arrow-left-right'; }

                            // Determine bill/for label
                            $billLabel = '<span class="badge bg-info text-white" style="font-size: 10px; font-weight: 500;">Advance Payment</span>';
                            if (!empty($pay['for_month'])) {
                                if (preg_match('/^\d{4}-\d{2}$/', $pay['for_month'])) {
                                    $billLabel = date('F Y', strtotime($pay['for_month'].'-01'));
                                } else {
                                    $billLabel = htmlspecialchars($pay['for_month']);
                                }
                            }
                        ?>
                        <tr>
                            <td class="text-slate-400" style="font-size: 11px; padding: 11px 16px;"><?= $i++ ?></td>
                            <td style="padding: 11px 16px;">
                                <div class="text-slate-700" style="font-size: 12px; font-weight: 500;"><?= date('d M Y', strtotime($pay['payment_date'])) ?></div>
                                <div class="text-slate-400" style="font-size: 10px;"><?= date('D', strtotime($pay['payment_date'])) ?></div>
                            </td>
                            <td style="padding: 11px 16px;">
                                <div class="text-slate-800" style="font-size: 12px; font-weight: 500;"><?= htmlspecialchars($pay['customer_name']) ?></div>
                            </td>
                            <td style="padding: 11px 16px;">
                                <?php if (!empty($pay['pppoe_username'])): ?>
                                    <span class="text-slate-700" style="font-size: 12px; font-weight: 500; font-family: monospace; background: #f1f5f9; padding: 2px 7px; border-radius: 4px;"><?= htmlspecialchars($pay['pppoe_username']) ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300" style="font-size: 11px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="text-slate-600" style="font-size: 12px;"><?= $billLabel ?></span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="badge <?= $methodClass ?> px-2 py-1" style="font-size: 10px; border-radius: 5px; font-weight: 500;">
                                    <i class="bi <?= $methodIcon ?> me-1"></i><?= htmlspecialchars($pay['payment_method']) ?>
                                </span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="fw-bold text-success" style="font-size: 13px;">+৳<?= number_format($pay['amount'], 2) ?></span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 9px; font-weight: 700; flex-shrink: 0;">
                                        <?= strtoupper(substr($pay['processed_by'] ?? 'S', 0, 1)) ?>
                                    </div>
                                    <span class="text-slate-600" style="font-size: 11px;"><?= htmlspecialchars($pay['processed_by'] ?? 'System') ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/main.js"></script>
<style>
    .bg-purple { background-color: #a855f7 !important; }
</style>
<script>
$(document).ready(function() {
    var dt = $('#paymentRecordTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [],
        "language": {
            "emptyTable": "<div class='text-center text-slate-400 py-4'><i class='bi bi-receipt d-block mb-2' style='font-size: 2rem; opacity: 0.3;'></i>No payment records found.</div>"
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });

    // Live search from toolbar input
    $('#searchPaymentRecord').on('keyup', function() {
        dt.search(this.value).draw();
    });
});
</script>
