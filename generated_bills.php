<?php
require_once 'includes/db.php';
$active_menu = 'generated_bills';
$page_title = 'Generated Bills';
$page_subtitle = 'Month-wise generated customer invoices & billing records';
include 'includes/header.php';

if (!has_permission('customer_view') && !has_permission('payment_record_view') && !has_permission('owner_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view generated bills.</div></div>';
    include 'includes/footer.php';
    exit;
}

include 'includes/sidebar.php';

// Determine available billing months in the database
$month_counts = [];
$m_res = $conn->query("SELECT billing_month, COUNT(*) as cnt FROM invoice_tbl GROUP BY billing_month ORDER BY billing_month DESC");
if ($m_res) {
    while ($m_row = $m_res->fetch_assoc()) {
        $bm = trim($m_row['billing_month'] ?? '');
        if (!empty($bm)) {
            $month_counts[$bm] = (int)$m_row['cnt'];
        }
    }
}

// Current running month
$current_running_month = date('Y-m');
if (!isset($month_counts[$current_running_month])) {
    $month_counts[$current_running_month] = 0;
}
krsort($month_counts);

// Selected month from GET, default to current running month (or first month with data)
$selected_month = $_GET['month'] ?? '';
if (empty($selected_month) || !preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    $selected_month = $current_running_month;
}

// Additional filters
$filter_status = trim($_GET['status'] ?? '');
$filter_area = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? intval($_GET['area_id']) : '';

// Calculate prev and next month for quick arrows
$prev_month_ts = strtotime($selected_month . '-01 -1 month');
$next_month_ts = strtotime($selected_month . '-01 +1 month');
$prev_month_val = date('Y-m', $prev_month_ts);
$next_month_val = date('Y-m', $next_month_ts);

// Formatted month text
$selected_month_label = date('F Y', strtotime($selected_month . '-01'));
$alt_selected_month = date('F-Y', strtotime($selected_month . '-01'));

// Fetch Areas for filter
$areas = [];
$a_res = $conn->query("SELECT id, area_name FROM area_tbl ORDER BY area_name ASC");
if ($a_res) {
    while ($a = $a_res->fetch_assoc()) {
        $areas[] = $a;
    }
}

// -------------------------------------------------------------
// Calculate Summary KPIs for the selected month
// -------------------------------------------------------------
$summary_stmt = $conn->prepare("SELECT 
    COUNT(*) as total_count,
    COALESCE(SUM(amount), 0) as total_amount,
    COALESCE(SUM(paid_amount), 0) as total_paid,
    COALESCE(SUM(discount), 0) as total_discount,
    SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
    SUM(CASE WHEN status = 'Partial' THEN 1 ELSE 0 END) as partial_count
FROM invoice_tbl 
WHERE (billing_month = ? OR billing_month = ?)");
$summary_stmt->bind_param("ss", $selected_month, $alt_selected_month);
$summary_stmt->execute();
$kpi = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$total_invoices = (int)($kpi['total_count'] ?? 0);
$total_amount = (float)($kpi['total_amount'] ?? 0);
$total_paid = (float)($kpi['total_paid'] ?? 0);
$total_discount = (float)($kpi['total_discount'] ?? 0);
$total_due = max(0.0, $total_amount - $total_paid - $total_discount);
$paid_count = (int)($kpi['paid_count'] ?? 0);
$unpaid_count = (int)($kpi['unpaid_count'] ?? 0);
$partial_count = (int)($kpi['partial_count'] ?? 0);

// -------------------------------------------------------------
// Fetch Invoices List
// -------------------------------------------------------------
$where_clauses = ["(i.billing_month = ? OR i.billing_month = ?)"];
$params = [$selected_month, $alt_selected_month];
$types = "ss";

if (!empty($filter_status)) {
    $where_clauses[] = "i.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_area !== '') {
    $where_clauses[] = "c.area_id = ?";
    $params[] = $filter_area;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

$list_query = "SELECT 
    i.id,
    i.customer_id,
    i.amount,
    i.paid_amount,
    i.discount,
    i.billing_month,
    i.status,
    i.description,
    i.created_at,
    i.processed_by,
    c.name AS customer_name,
    c.username AS customer_username,
    c.phone AS customer_phone,
    c.previous_due AS customer_due,
    c.advance_payment AS customer_advance,
    a.area_name,
    COALESCE(p.given_name, p.original_profile) AS package_name
FROM invoice_tbl i
LEFT JOIN customer_tbl c ON i.customer_id = c.id
LEFT JOIN area_tbl a ON c.area_id = a.id
LEFT JOIN package_tbl p ON c.package_id = p.id
WHERE $where_sql
ORDER BY i.id DESC";

$stmt = $conn->prepare($list_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$invoices_res = $stmt->get_result();
$invoices = [];
while ($row = $invoices_res->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt->close();
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header & Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 11px;">
                    <i class="bi bi-file-earmark-ruled me-1"></i> Client & Billing
                </span>
                <span class="text-slate-400">/</span>
                <span class="text-slate-600 fw-semibold" style="font-size: 13px;">Generated Bills</span>
            </div>
            <h1 class="h3 fw-bold text-slate-800 mb-0">Generated Bills</h1>
            <p class="text-slate-500 mb-0" style="font-size: 13px;">Month-wise generated customer invoices, balances, and billing management</p>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-2" id="btnRunGenerateBills">
                <i class="bi bi-magic"></i>
                <span>Generate Bills</span>
            </button>
            <?php if ($total_invoices > 0 && (has_permission('customer_delete') || has_permission('owner_full_access'))): ?>
            <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-flex align-items-center gap-2" id="btnDeleteMonthBills" data-month="<?= htmlspecialchars($selected_month) ?>" data-count="<?= $total_invoices ?>" data-due="<?= number_format($total_due, 2, '.', '') ?>" data-label="<?= htmlspecialchars($selected_month_label) ?>">
                <i class="bi bi-trash3"></i>
                <span>Delete All for <?= htmlspecialchars($selected_month_label) ?></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Month Navigation & Controls Bar -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="generated_bills.php" id="filterForm" class="row g-2 align-items-center">
                <!-- Month Switcher with Quick Prev/Next -->
                <div class="col-12 col-md-auto d-flex align-items-center gap-1">
                    <a href="generated_bills.php?month=<?= $prev_month_val ?><?= !empty($filter_status) ? '&status='.urlencode($filter_status) : '' ?><?= $filter_area !== '' ? '&area_id='.urlencode($filter_area) : '' ?>" class="btn btn-sm btn-light border-slate-200 text-slate-600 px-2" title="Previous Month (<?= date('F Y', $prev_month_ts) ?>)">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    
                    <div class="input-group input-group-sm" style="min-width: 220px;">
                        <span class="input-group-text bg-white border-slate-200 text-slate-500">
                            <i class="bi bi-calendar-month"></i>
                        </span>
                        <select name="month" id="monthSelector" class="form-select form-select-sm border-slate-200 text-slate-800 fw-semibold" onchange="document.getElementById('filterForm').submit();">
                            <?php foreach ($month_counts as $m_val => $m_count): 
                                $m_name = date('F Y', strtotime($m_val . '-01'));
                                $is_curr = ($m_val === $current_running_month) ? ' (Current)' : '';
                            ?>
                                <option value="<?= htmlspecialchars($m_val) ?>" <?= ($m_val === $selected_month) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m_name . $is_curr . ' — ' . number_format($m_count) . ' bills') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <a href="generated_bills.php?month=<?= $next_month_val ?><?= !empty($filter_status) ? '&status='.urlencode($filter_status) : '' ?><?= $filter_area !== '' ? '&area_id='.urlencode($filter_area) : '' ?>" class="btn btn-sm btn-light border-slate-200 text-slate-600 px-2" title="Next Month (<?= date('F Y', $next_month_ts) ?>)">
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <?php if ($selected_month !== $current_running_month): ?>
                    <a href="generated_bills.php?month=<?= $current_running_month ?>" class="btn btn-sm btn-light border-slate-200 text-primary ms-1" style="font-size: 11px; font-weight: 500;">
                        This Month
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-auto">
                    <select name="status" class="form-select form-select-sm border-slate-200 text-slate-700" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Status</option>
                        <option value="Paid" <?= ($filter_status === 'Paid') ? 'selected' : '' ?>>Paid (<?= number_format($paid_count) ?>)</option>
                        <option value="Unpaid" <?= ($filter_status === 'Unpaid') ? 'selected' : '' ?>>Unpaid (<?= number_format($unpaid_count) ?>)</option>
                        <option value="Partial" <?= ($filter_status === 'Partial') ? 'selected' : '' ?>>Partial (<?= number_format($partial_count) ?>)</option>
                    </select>
                </div>

                <!-- Area Filter -->
                <div class="col-6 col-md-auto">
                    <select name="area_id" class="form-select form-select-sm border-slate-200 text-slate-700" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Areas</option>
                        <?php foreach ($areas as $ar): ?>
                            <option value="<?= $ar['id'] ?>" <?= ($filter_area === (int)$ar['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ar['area_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search Input inside table -->
                <div class="col-12 col-md ms-md-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-slate-200 text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="customSearch" class="form-control form-control-sm border-slate-200" placeholder="Search customer, username, phone...">
                        <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-500" id="clearSearch" title="Clear Search">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Invoices -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px; flex-shrink: 0;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-slate-500 fw-medium" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Bills</div>
                        <div class="h4 fw-bold text-slate-800 mb-0" id="kpiTotalCount"><?= number_format($total_invoices) ?></div>
                        <div class="text-slate-500 mt-1" style="font-size: 11px;">
                            <span class="text-success fw-semibold"><?= number_format($paid_count) ?> Paid</span> &bull; 
                            <span class="text-danger fw-semibold"><?= number_format($unpaid_count) ?> Unpaid</span>
                            <?php if ($partial_count > 0): ?>
                            &bull; <span class="text-warning fw-semibold"><?= number_format($partial_count) ?> Part</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Billed Amount -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px; flex-shrink: 0;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-slate-500 fw-medium" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Billed</div>
                        <div class="h4 fw-bold text-slate-800 mb-0" id="kpiTotalAmount">৳<?= number_format($total_amount, 2) ?></div>
                        <div class="text-slate-500 mt-1" style="font-size: 11px;">
                            For <?= htmlspecialchars($selected_month_label) ?>
                            <?php if ($total_discount > 0): ?>
                            (Disc: ৳<?= number_format($total_discount, 2) ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Collected / Paid -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px; flex-shrink: 0;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-slate-500 fw-medium" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Collected</div>
                        <div class="h4 fw-bold text-success mb-0" id="kpiTotalPaid">৳<?= number_format($total_paid, 2) ?></div>
                        <div class="text-slate-500 mt-1" style="font-size: 11px;">
                            <?php $collection_pct = ($total_amount > 0) ? round(($total_paid / $total_amount) * 100, 1) : 0; ?>
                            <span><?= $collection_pct ?>% of total billing collected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Due / Unpaid -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px; flex-shrink: 0;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-slate-500 fw-medium" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Due / Unpaid</div>
                        <div class="h4 fw-bold text-danger mb-0" id="kpiTotalDue">৳<?= number_format($total_due, 2) ?></div>
                        <div class="text-slate-500 mt-1" style="font-size: 11px;">
                            <span>Added to customer balances</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Data Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-bottom border-slate-200 py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list-columns-reverse text-primary fs-5"></i>
                <h5 class="card-title fw-bold text-slate-800 mb-0">Bills for <?= htmlspecialchars($selected_month_label) ?></h5>
                <span class="badge border border-slate-200 ms-2" id="tableRowCountBadge" style="font-size: 11px; font-weight: 600; color: #475569 !important; background-color: #f1f5f9 !important;">
                    <?= number_format(count($invoices)) ?> records
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="generatedBillsTable" style="width: 100%;">
                    <thead class="bg-light text-slate-600 border-bottom border-slate-200" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4" style="width: 45px;">#</th>
                            <th style="width: 110px;">Invoice #</th>
                            <th>Customer</th>
                            <th>Area & Package</th>
                            <th>Month</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th class="text-center" style="width: 90px;">Status</th>
                            <th>Generated At</th>
                            <th>Processed By</th>
                            <th class="text-end pe-4" style="width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="12" class="text-center text-slate-400 py-5">
                                <i class="bi bi-file-earmark-ruled d-block mb-2" style="font-size: 2.5rem; opacity: 0.3;"></i>
                                No generated bills found for <strong><?= htmlspecialchars($selected_month_label) ?></strong>.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            $sl = 1;
                            foreach ($invoices as $inv): 
                                $inv_id = (int)$inv['id'];
                                $c_id = (int)$inv['customer_id'];
                                $amount = (float)$inv['amount'];
                                $paid = (float)$inv['paid_amount'];
                                $disc = (float)$inv['discount'];
                                $due = max(0.0, $amount - $paid - $disc);

                                $status_badge = 'bg-secondary';
                                if ($inv['status'] === 'Paid') {
                                    $status_badge = 'bg-success';
                                } elseif ($inv['status'] === 'Unpaid') {
                                    $status_badge = 'bg-danger';
                                } elseif ($inv['status'] === 'Partial') {
                                    $status_badge = 'bg-warning text-dark';
                                }

                                $m_disp = date('F Y', strtotime($inv['billing_month'] . '-01'));
                                $created_disp = !empty($inv['created_at']) ? date('d M Y, h:i A', strtotime($inv['created_at'])) : '-';
                            ?>
                            <tr id="inv-row-<?= $inv_id ?>">
                                <td class="ps-4 text-slate-400" style="font-size: 11px;"><?= $sl++ ?></td>
                                <td>
                                    <span class="fw-bold text-slate-800" style="font-family: monospace;">#INV-<?= str_pad($inv_id, 6, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-slate-800"><?= htmlspecialchars($inv['customer_name'] ?? 'Unknown') ?></div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <?php if (!empty($inv['customer_username'])): ?>
                                        <span class="badge border border-slate-200" style="font-size: 11px; font-family: monospace; font-weight: 600; color: #1e293b !important; background-color: #f1f5f9 !important;">
                                            <i class="bi bi-person me-1 text-primary"></i><?= htmlspecialchars($inv['customer_username']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($inv['customer_phone'])): ?>
                                        <span class="text-slate-500" style="font-size: 11px;">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($inv['customer_phone']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($inv['package_name'])): ?>
                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="font-size: 10px; font-weight: 500;">
                                        <i class="bi bi-box me-1"></i><?= htmlspecialchars($inv['package_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($inv['area_name'])): ?>
                                    <div class="text-slate-500 mt-1" style="font-size: 11px;">
                                        <i class="bi bi-geo-alt me-1 text-slate-400"></i><?= htmlspecialchars($inv['area_name']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge border border-slate-200" style="font-size: 11px; font-weight: 600; color: #334155 !important; background-color: #f8fafc !important;">
                                        <i class="bi bi-calendar3 me-1 text-slate-400"></i><?= htmlspecialchars($m_disp) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-slate-800">
                                    ৳<?= number_format($amount, 2) ?>
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    ৳<?= number_format($paid, 2) ?>
                                </td>
                                <td class="text-end fw-bold <?= $due > 0 ? 'text-danger' : 'text-slate-400' ?>">
                                    ৳<?= number_format($due, 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $status_badge ?> px-2 py-1" style="font-size: 10px; border-radius: 4px;">
                                        <?= htmlspecialchars($inv['status']) ?>
                                    </span>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px;">
                                    <?= $created_disp ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 9px; font-weight: 700; flex-shrink: 0;">
                                            <?= strtoupper(substr($inv['processed_by'] ?? 'S', 0, 1)) ?>
                                        </div>
                                        <span class="text-slate-600" style="font-size: 11px;"><?= htmlspecialchars($inv['processed_by'] ?? 'System') ?></span>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="print_invoice.php?id=<?= $inv_id ?>&format=pos" target="_blank" class="btn btn-light border-slate-200 text-slate-600" title="Print POS Receipt" style="font-size: 11px; padding: 2px 7px;">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <a href="print_invoice.php?id=<?= $inv_id ?>&format=general" target="_blank" class="btn btn-light border-slate-200 text-slate-600" title="Print A4 Invoice" style="font-size: 11px; padding: 2px 7px;">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if (has_permission('customer_delete') || has_permission('customer_edit') || has_permission('owner_full_access')): ?>
                                        <button type="button" class="btn btn-outline-danger btn-delete-single-bill" 
                                                data-id="<?= $inv_id ?>" 
                                                data-customer="<?= htmlspecialchars($inv['customer_name'] ?? 'Customer') ?>" 
                                                data-amount="<?= number_format($amount, 2, '.', '') ?>" 
                                                data-due="<?= number_format($due, 2, '.', '') ?>"
                                                data-paid="<?= number_format($paid, 2, '.', '') ?>"
                                                title="Delete Bill & Deduct Balance" 
                                                style="font-size: 11px; padding: 2px 7px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
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
</div>

<?php include 'includes/footer.php'; ?>

<!-- DataTables Scripts & Styling -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

<style>
    .bg-purple-subtle { background-color: #f3e8ff !important; }
    .text-purple { color: #9333ea !important; }
    .border-purple-subtle { border-color: #e9d5ff !important; }
    #generatedBillsTable_wrapper .row:first-child { display: none !important; }

    /* Fix slate colors and badge text visibility */
    .text-slate-900 { color: #0f172a !important; }
    .text-slate-800 { color: #1e293b !important; }
    .text-slate-700 { color: #334155 !important; }
    .text-slate-600 { color: #475569 !important; }
    .text-slate-500 { color: #64748b !important; }
    .text-slate-400 { color: #94a3b8 !important; }
    .bg-slate-100 { background-color: #f1f5f9 !important; }
    .border-slate-200 { border-color: #e2e8f0 !important; }

    /* Ensure badges with light or slate backgrounds have dark, crisp, visible text */
    .badge.bg-light,
    .badge.bg-slate-100 {
        color: #334155 !important;
        background-color: #f1f5f9 !important;
    }
</style>

<script>
$(document).ready(function() {
    var dt = $('#generatedBillsTable').DataTable({
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100, 250],
        order: [[0, 'asc']],
        language: {
            emptyTable: "<div class='text-center text-slate-400 py-4'><i class='bi bi-file-earmark-ruled d-block mb-2' style='font-size: 2rem; opacity: 0.3;'></i>No generated bills match your filter.</div>"
        },
        columnDefs: [
            { orderable: false, targets: [11] }
        ]
    });

    // Custom live search toolbar
    $('#customSearch').on('keyup', function() {
        dt.search(this.value).draw();
    });

    $('#clearSearch').on('click', function() {
        $('#customSearch').val('');
        dt.search('').draw();
    });

    // ---------------------------------------------------------
    // Delete Single Generated Bill
    // ---------------------------------------------------------
    $(document).on('click', '.btn-delete-single-bill', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var invId = $btn.data('id');
        var custName = $btn.data('customer');
        var amount = parseFloat($btn.data('amount')) || 0;
        var due = parseFloat($btn.data('due')) || 0;
        var paid = parseFloat($btn.data('paid')) || 0;

        Swal.fire({
            title: 'Delete this Generated Bill?',
            html: '<div class="text-start" style="font-size: 13px;">' +
                  '<div class="mb-2"><strong>Invoice:</strong> #INV-' + String(invId).padStart(6, '0') + '</div>' +
                  '<div class="mb-2"><strong>Customer:</strong> ' + $('<div>').text(custName).html() + '</div>' +
                  '<div class="mb-3"><strong>Bill Amount:</strong> ৳' + amount.toFixed(2) + ' (Unpaid Due: ৳' + due.toFixed(2) + ')</div>' +
                  '<div class="alert alert-warning py-2 px-3 mb-0" style="font-size: 12px; line-height: 1.4;">' +
                  '<i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> ' +
                  'Deleting this bill will <strong>deduct ৳' + due.toFixed(2) + '</strong> from customer’s due balance, revert auto-adjusted payments, and step back their billing cycle date.' +
                  '</div></div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Yes, Delete Bill',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: 'ajax/delete_generated_bill.php',
                    type: 'POST',
                    data: { invoice_id: invId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Toast.fire({
                                icon: 'success',
                                title: res.message || 'Bill deleted and balance updated successfully.'
                            });

                            // Animate remove row from DataTable
                            var $row = $('#inv-row-' + invId);
                            dt.row($row).remove().draw(false);

                            // Dynamically update KPI summary cards
                            updateKpiDeltas(amount, paid, due);
                        } else {
                            $btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Delete',
                                text: res.message || 'An error occurred while deleting the bill.'
                            });
                        }
                    },
                    error: function(xhr, status, err) {
                        $btn.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                        Swal.fire({
                            icon: 'error',
                            title: 'Network / Server Error',
                            text: 'Failed to connect to the server. Please try again.'
                        });
                    }
                });
            }
        });
    });

    function updateKpiDeltas(amount, paid, due) {
        var $countEl = $('#kpiTotalCount');
        var curCount = parseInt($countEl.text().replace(/,/g, '')) || 0;
        if (curCount > 0) {
            $countEl.text((curCount - 1).toLocaleString());
            $('#tableRowCountBadge').text((curCount - 1).toLocaleString() + ' records');
        }

        var $amtEl = $('#kpiTotalAmount');
        var curAmt = parseFloat($amtEl.text().replace(/[^\d.-]/g, '')) || 0;
        $amtEl.text('৳' + Math.max(0, curAmt - amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        var $paidEl = $('#kpiTotalPaid');
        var curPaid = parseFloat($paidEl.text().replace(/[^\d.-]/g, '')) || 0;
        $paidEl.text('৳' + Math.max(0, curPaid - paid).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        var $dueEl = $('#kpiTotalDue');
        var curDue = parseFloat($dueEl.text().replace(/[^\d.-]/g, '')) || 0;
        $dueEl.text('৳' + Math.max(0, curDue - due).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    // ---------------------------------------------------------
    // Delete All Bills for the Month (Bulk)
    // ---------------------------------------------------------
    $('#btnDeleteMonthBills').on('click', function() {
        var month = $(this).data('month');
        var monthLabel = $(this).data('label');
        var count = $(this).data('count');
        var due = $(this).data('due');

        Swal.fire({
            title: 'Delete ALL Bills for ' + monthLabel + '?',
            html: '<div class="text-start" style="font-size: 13px;">' +
                  '<p>You are about to permanently delete all <strong>' + count + '</strong> generated bills for <strong>' + monthLabel + '</strong>.</p>' +
                  '<div class="alert alert-danger py-2 px-3 mb-3" style="font-size: 12px; line-height: 1.4;">' +
                  '<i class="bi bi-exclamation-octagon-fill me-1"></i> ' +
                  'All unpaid bill amounts (Total: <strong>৳' + parseFloat(due).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</strong>) will be <strong>deducted from customer dues</strong>, auto-adjusted payments will be restored, and customer billing cycle dates will be rolled back by 1 month.' +
                  '</div>' +
                  '<p class="text-danger fw-bold mb-0">Are you absolutely sure you want to proceed?</p>' +
                  '</div>',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Yes, Delete All for ' + monthLabel,
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting Bills...',
                    text: 'Reverting balances and customer billing cycles. Please wait.',
                    allowOutsideClick: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax/delete_month_bills.php',
                    type: 'POST',
                    data: { month: month },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Bulk Deletion Complete',
                                html: res.message || 'All monthly bills deleted and balances reverted successfully.',
                                confirmButtonColor: '#2563eb'
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Bulk Deletion Failed',
                                text: res.message || 'An error occurred during bulk deletion.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Network / Server Error',
                            text: 'Failed to complete bulk deletion. Please check server logs.'
                        });
                    }
                });
            }
        });
    });

    // ---------------------------------------------------------
    // Trigger Auto-Generate Bills
    // ---------------------------------------------------------
    $('#btnRunGenerateBills').on('click', function() {
        Swal.fire({
            title: 'Generate Running Month Bills?',
            text: 'This will generate bills for all eligible active customers whose billing cycle date matches or has arrived for the running month (<?= date("F Y") ?>). Next month bills will NOT be created.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-magic me-1"></i> Generate Bills Now',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Generating Bills...',
                    text: 'Please wait while monthly bills are processed.',
                    allowOutsideClick: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'ajax/auto_generate_bills.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Bill Generation Complete',
                                html: '<b>Generated:</b> ' + res.generated + ' bill(s)<br><b>Skipped:</b> ' + res.skipped + ' customer(s) (already billed or not due)',
                                confirmButtonColor: '#2563eb'
                            }).then(function() {
                                window.location.href = 'generated_bills.php?month=<?= date("Y-m") ?>';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Generation Error',
                                text: res.message || 'An error occurred while generating bills.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Failed to communicate with bill generator.'
                        });
                    }
                });
            }
        });
    });
});
</script>

