<?php
require_once '../includes/db.php';
$base_path = '../';
$active_menu = 'collect_handover';
$page_title = 'Collect and Handover';
$page_subtitle = 'Date-wise collection and handover records';
include '../includes/header.php';

if (!has_permission('txbill_full_access') && !has_permission('amount_collect_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view this page.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

$current_username = $_SESSION['username'] ?? '';

// Determine Month / Date Range (Default: Current Month)
$selected_month = $_GET['month'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if (!empty($date_from) && !empty($date_to)) {
    $start_date = $date_from;
    $end_date = $date_to;
    $display_month = date('Y-m', strtotime($start_date));
    $is_custom_range = true;
} elseif (!empty($selected_month)) {
    $start_date = $selected_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $display_month = $selected_month;
    $is_custom_range = false;
} else {
    // DEFAULT: CURRENT MONTH
    $display_month = date('Y-m');
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $is_custom_range = false;
}

// Navigation helpers
$prev_month = date('Y-m', strtotime($start_date . ' -1 month'));
$next_month = date('Y-m', strtotime($start_date . ' +1 month'));
$current_month_str = date('Y-m');

// Selected Processor (Default: Logged-in user)
$selected_processor = isset($_GET['processor']) ? trim($_GET['processor']) : $current_username;

// Fetch list of all processors for dropdown filter
$all_processors = [];
$p_res1 = $conn->query("SELECT DISTINCT processed_by as uname FROM payment_tbl WHERE processed_by IS NOT NULL AND processed_by != ''");
if ($p_res1) {
    while ($r = $p_res1->fetch_assoc()) {
        $all_processors[$r['uname']] = true;
    }
}
$p_res2 = $conn->query("SELECT DISTINCT processor_name as uname FROM collector_handover_tbl WHERE processor_name IS NOT NULL AND processor_name != ''");
if ($p_res2) {
    while ($r = $p_res2->fetch_assoc()) {
        $all_processors[$r['uname']] = true;
    }
}
if (!empty($current_username)) {
    $all_processors[$current_username] = true;
}
ksort($all_processors);

// --- 1. FETCH DATE-WISE COLLECTIONS ---
$col_where = ["DATE(p.payment_date) >= ? AND DATE(p.payment_date) <= ?"];
$col_params = [$start_date, $end_date];
$col_types = "ss";

if ($selected_processor !== 'all' && !empty($selected_processor)) {
    $col_where[] = "p.processed_by = ?";
    $col_params[] = $selected_processor;
    $col_types .= "s";
}

$group_by_clause = ($selected_processor === 'all') ? "DATE(p.payment_date), p.processed_by" : "DATE(p.payment_date)";

$col_sql = "SELECT 
    DATE(p.payment_date) as c_date,
    p.processed_by,
    COUNT(p.id) as payment_count,
    SUM(p.amount) as total_amount
FROM payment_tbl p
WHERE " . implode(" AND ", $col_where) . "
GROUP BY $group_by_clause
ORDER BY c_date DESC";

$col_stmt = $conn->prepare($col_sql);
if (!empty($col_params)) {
    $col_stmt->bind_param($col_types, ...$col_params);
}
$col_stmt->execute();
$col_res = $col_stmt->get_result();

$date_records = [];
$month_total_collected = 0;
$month_total_txns = 0;

while ($row = $col_res->fetch_assoc()) {
    $key = ($selected_processor === 'all') ? ($row['c_date'] . '_' . $row['processed_by']) : $row['c_date'];
    $proc_val = !empty($row['processed_by']) ? $row['processed_by'] : ($selected_processor !== 'all' ? $selected_processor : 'System');
    $date_records[$key] = [
        'date' => $row['c_date'],
        'processor' => $proc_val,
        'payment_count' => (int)$row['payment_count'],
        'collected' => (float)$row['total_amount'],
        'handover' => 0.0,
        'due' => (float)$row['total_amount']
    ];
    $month_total_collected += (float)$row['total_amount'];
    $month_total_txns += (int)$row['payment_count'];
}
$col_stmt->close();

// --- 2. FETCH HANDOVERS ---
$ho_where = ["DATE(handover_date) >= ? AND DATE(handover_date) <= ?"];
$ho_params = [$start_date, $end_date];
$ho_types = "ss";

if ($selected_processor !== 'all' && !empty($selected_processor)) {
    $ho_where[] = "processor_name = ?";
    $ho_params[] = $selected_processor;
    $ho_types .= "s";
}

$ho_sql = "SELECT 
    id,
    processor_name,
    amount,
    handover_date,
    for_date,
    received_by
FROM collector_handover_tbl
WHERE " . implode(" AND ", $ho_where) . "
ORDER BY handover_date DESC, id DESC";

$ho_stmt = $conn->prepare($ho_sql);
if (!empty($ho_params)) {
    $ho_stmt->bind_param($ho_types, ...$ho_params);
}
$ho_stmt->execute();
$ho_res = $ho_stmt->get_result();

$handover_records = [];
$month_total_handover = 0;

while ($row = $ho_res->fetch_assoc()) {
    $handover_records[] = $row;
    $month_total_handover += (float)$row['amount'];
    
    // Allocate to date_records if applicable
    $target_date = !empty($row['for_date']) ? $row['for_date'] : date('Y-m-d', strtotime($row['handover_date']));
    $key = ($selected_processor === 'all') ? ($target_date . '_' . $row['processor_name']) : $target_date;
    
    if (isset($date_records[$key])) {
        $date_records[$key]['handover'] += (float)$row['amount'];
        $date_records[$key]['due'] = max(0, $date_records[$key]['collected'] - $date_records[$key]['handover']);
    }
}
$ho_stmt->close();

// --- 3. ALL-TIME STATS (FOR OVERALL DUE CALCULATION) ---
$all_time_collected = 0;
$all_time_handover = 0;

if ($selected_processor === 'all') {
    $atc_res = $conn->query("SELECT SUM(amount) as total FROM payment_tbl");
    if ($atc_res && $r = $atc_res->fetch_assoc()) $all_time_collected = (float)($r['total'] ?? 0);
    $ath_res = $conn->query("SELECT SUM(amount) as total FROM collector_handover_tbl");
    if ($ath_res && $r = $ath_res->fetch_assoc()) $all_time_handover = (float)($r['total'] ?? 0);
} else {
    $stmt_atc = $conn->prepare("SELECT SUM(amount) as total FROM payment_tbl WHERE processed_by = ?");
    $stmt_atc->bind_param("s", $selected_processor);
    $stmt_atc->execute();
    $r = $stmt_atc->get_result()->fetch_assoc();
    $all_time_collected = (float)($r['total'] ?? 0);
    $stmt_atc->close();

    $stmt_ath = $conn->prepare("SELECT SUM(amount) as total FROM collector_handover_tbl WHERE processor_name = ?");
    $stmt_ath->bind_param("s", $selected_processor);
    $stmt_ath->execute();
    $r = $stmt_ath->get_result()->fetch_assoc();
    $all_time_handover = (float)($r['total'] ?? 0);
    $stmt_ath->close();
}

$all_time_due = max(0, $all_time_collected - $all_time_handover);
$month_balance = max(0, $month_total_collected - $month_total_handover);
?>

<div class="container-fluid pt-2 pb-5">

    <!-- Top Filter & Month Switcher Bar -->
    <div class="dashboard-card p-3 mb-3 border-0" style="border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
        <form method="GET" id="filterForm" class="row g-2 align-items-center">
            
            <!-- Quick Month Switcher -->
            <div class="col-12 col-md-auto d-flex align-items-center gap-1">
                <a href="?month=<?= $prev_month ?>&processor=<?= urlencode($selected_processor) ?>" class="btn btn-sm btn-light border-slate-200 text-slate-700 shadow-none px-2" title="Previous Month">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <input type="month" name="month" class="form-control form-control-sm border-slate-200 fw-semibold text-slate-800 shadow-none text-center" value="<?= htmlspecialchars($display_month) ?>" style="width: 145px; font-size: 13px;" onchange="this.form.submit()" title="Select Month">
                <a href="?month=<?= $next_month ?>&processor=<?= urlencode($selected_processor) ?>" class="btn btn-sm btn-light border-slate-200 text-slate-700 shadow-none px-2" title="Next Month">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <?php if ($display_month !== $current_month_str || $is_custom_range): ?>
                <a href="?month=<?= $current_month_str ?>&processor=<?= urlencode($selected_processor) ?>" class="btn btn-sm btn-outline-primary border-slate-200 shadow-none px-2 ms-1" style="font-size: 11px;">
                    This Month
                </a>
                <?php endif; ?>
            </div>

            <!-- Processor / Collector Filter -->
            <div class="col-12 col-sm-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-slate-200 text-slate-500"><i class="bi bi-person"></i></span>
                    <select name="processor" class="form-select form-select-sm border-slate-200 shadow-none fw-medium text-slate-800" style="min-width: 170px;" onchange="this.form.submit()">
                        <?php if (!empty($current_username)): ?>
                        <option value="<?= htmlspecialchars($current_username) ?>" <?= ($selected_processor === $current_username) ? 'selected' : '' ?>>
                            My Records (<?= htmlspecialchars($current_username) ?>)
                        </option>
                        <?php endif; ?>
                        <option value="all" <?= ($selected_processor === 'all') ? 'selected' : '' ?>>All Processors</option>
                        <?php foreach (array_keys($all_processors) as $proc): ?>
                            <?php if ($proc !== $current_username): ?>
                            <option value="<?= htmlspecialchars($proc) ?>" <?= ($selected_processor === $proc) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proc) ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Custom Date Range Collapse Toggle -->
            <div class="col-auto">
                <button class="btn btn-sm btn-light border-slate-200 text-slate-600 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#customRangeCollapse" aria-expanded="<?= $is_custom_range ? 'true' : 'false' ?>" aria-controls="customRangeCollapse" style="font-size: 12px;">
                    <i class="bi bi-calendar-range me-1"></i> Custom Range
                </button>
            </div>

            <div class="col-auto ms-auto d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1" style="font-size: 11px; border-radius: 6px;">
                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M, Y', strtotime($start_date)) ?> &ndash; <?= date('d M, Y', strtotime($end_date)) ?>
                </span>
                <?php if ($is_custom_range || $display_month !== $current_month_str || $selected_processor !== $current_username): ?>
                <a href="collect_handover.php" class="btn btn-sm btn-link text-slate-400 text-decoration-none p-0" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise fs-6"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- Collapsible Custom Date Pickers -->
            <div class="collapse <?= $is_custom_range ? 'show' : '' ?> w-100 mt-2" id="customRangeCollapse">
                <div class="p-2 rounded bg-light border border-slate-200 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-slate-600 fw-medium" style="font-size: 12px;">Date Range:</span>
                    <input type="date" name="date_from" class="form-control form-control-sm border-slate-200 shadow-none bg-white" value="<?= htmlspecialchars($start_date) ?>" style="width: 140px; font-size: 12px;">
                    <span class="text-slate-400">&ndash;</span>
                    <input type="date" name="date_to" class="form-control form-control-sm border-slate-200 shadow-none bg-white" value="<?= htmlspecialchars($end_date) ?>" style="width: 140px; font-size: 12px;">
                    <button type="submit" class="btn btn-sm btn-primary shadow-none px-3" style="font-size: 12px;"><i class="bi bi-funnel me-1"></i> Apply Range</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Monthly Collected -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-card p-3 h-100 border-0" style="border-left: 4px solid #0ea5e9 !important; border-radius: 10px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Month Collected</div>
                        <div class="fw-bold" style="font-size: 22px; color: #0284c7; line-height: 1.2;">৳<?= number_format($month_total_collected, 2) ?></div>
                        <small class="text-slate-500" style="font-size: 11px;"><?= number_format($month_total_txns) ?> payments in <?= date('M Y', strtotime($start_date)) ?></small>
                    </div>
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 42px; height: 42px; color: #0ea5e9;">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Handover -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-card p-3 h-100 border-0" style="border-left: 4px solid #22c55e !important; border-radius: 10px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Month Handover</div>
                        <div class="fw-bold" style="font-size: 22px; color: #16a34a; line-height: 1.2;">৳<?= number_format($month_total_handover, 2) ?></div>
                        <small class="text-slate-500" style="font-size: 11px;"><?= count($handover_records) ?> handovers in <?= date('M Y', strtotime($start_date)) ?></small>
                    </div>
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 42px; height: 42px; color: #22c55e;">
                        <i class="bi bi-box-arrow-in-down fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Net Balance -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-card p-3 h-100 border-0" style="border-left: 4px solid #f59e0b !important; border-radius: 10px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Month Net Due</div>
                        <div class="fw-bold" style="font-size: 22px; color: #d97706; line-height: 1.2;">৳<?= number_format($month_balance, 2) ?></div>
                        <small class="text-slate-500" style="font-size: 11px;">Collected &minus; Handed Over</small>
                    </div>
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 42px; height: 42px; color: #f59e0b;">
                        <i class="bi bi-wallet2 fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- All-Time Cash in Hand / Due -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-card p-3 h-100 border-0" style="border-left: 4px solid #f43f5e !important; border-radius: 10px; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Cash In Hand</div>
                        <div class="fw-bold" style="font-size: 22px; color: #e11d48; line-height: 1.2;">৳<?= number_format($all_time_due, 2) ?></div>
                        <small class="text-slate-500" style="font-size: 11px;">All-time pending handover</small>
                    </div>
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm flex-shrink-0" style="width: 42px; height: 42px; color: #f43f5e;">
                        <i class="bi bi-exclamation-octagon fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Mode Switcher (Side-by-Side vs Single Tab) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex gap-2 bg-white p-1 rounded-3 shadow-sm border border-slate-100" style="width: fit-content;">
            <button type="button" class="btn btn-sm px-3 fw-medium view-mode-btn active" data-view="both" style="border-radius: 6px; font-size: 12px;">
                <i class="bi bi-columns-gap me-1"></i> Side-by-Side
            </button>
            <button type="button" class="btn btn-sm px-3 fw-medium view-mode-btn text-slate-500 bg-transparent" data-view="collections" style="border-radius: 6px; font-size: 12px;">
                <i class="bi bi-arrow-down-circle text-primary me-1"></i> Date-wise Collections <span class="badge bg-primary ms-1"><?= count($date_records) ?></span>
            </button>
            <button type="button" class="btn btn-sm px-3 fw-medium view-mode-btn text-slate-500 bg-transparent" data-view="handovers" style="border-radius: 6px; font-size: 12px;">
                <i class="bi bi-arrow-up-circle text-success me-1"></i> Handover Records <span class="badge bg-success ms-1"><?= count($handover_records) ?></span>
            </button>
        </div>
        <div class="text-slate-400" style="font-size: 12px;">
            <i class="bi bi-info-circle me-1"></i> Click on <span class="text-primary fw-semibold"><i class="bi bi-eye"></i> View</span> to see customer payment breakdown
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-4" id="tablesContainer">
        
        <!-- TABLE 1: DATE-WISE COLLECTION RECORDS -->
        <div class="col-12 col-xl-6" id="collectionCol">
            <div class="dashboard-card p-0 border-0 h-100" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden; background: #fff;">
                
                <!-- Table Header / Toolbar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2 bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-arrow-down-circle fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-slate-800 mb-0" style="font-size: 13px;">Date-Wise Collection Records</h6>
                            <small class="text-slate-400" style="font-size: 11px;"><?= count($date_records) ?> dates with collections</small>
                        </div>
                    </div>
                    <div>
                        <input type="text" id="searchCollectionTable" class="form-control form-control-sm border-slate-200 shadow-none" placeholder="Search date..." style="width: 150px; font-size: 11px;">
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="collectionDataTable" style="border-collapse: collapse; width: 100%;">
                        <thead style="background-color: #f8fafc; border-bottom: 1px solid var(--border-slate-200);">
                            <tr>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px; width: 45px;">#</th>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">Date</th>
                                <?php if ($selected_processor === 'all'): ?>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">Collector</th>
                                <?php endif; ?>
                                <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 10px 14px;">Payments</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 10px 14px;">Collected</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 10px 14px;">Handover</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 10px 14px;">Due</th>
                                <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 10px 14px; width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($date_records) > 0): ?>
                                <?php $i = 1; foreach ($date_records as $rec): ?>
                                <tr>
                                    <td class="text-slate-400" style="font-size: 11px; padding: 10px 14px;"><?= $i++ ?></td>
                                    <td style="padding: 10px 14px;">
                                        <div class="fw-semibold text-slate-800" style="font-size: 12px;"><?= date('d M, Y', strtotime($rec['date'])) ?></div>
                                        <small class="text-slate-400" style="font-size: 10px;"><?= date('l', strtotime($rec['date'])) ?></small>
                                    </td>
                                    <?php if ($selected_processor === 'all'): ?>
                                    <td style="padding: 10px 14px;">
                                        <span class="badge bg-light text-slate-700 border border-slate-200 fw-medium" style="font-size: 11px;"><?= htmlspecialchars($rec['processor']) ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-center" style="padding: 10px 14px;">
                                        <span class="badge bg-slate-100 text-slate-700 fw-semibold" style="font-size: 11px;"><?= $rec['payment_count'] ?></span>
                                    </td>
                                    <td class="text-end" style="padding: 10px 14px;">
                                        <span class="fw-bold text-slate-800" style="font-size: 12px;">৳<?= number_format($rec['collected'], 2) ?></span>
                                    </td>
                                    <td class="text-end" style="padding: 10px 14px;">
                                        <span class="fw-semibold text-success" style="font-size: 12px;">৳<?= number_format($rec['handover'], 2) ?></span>
                                    </td>
                                    <td class="text-end" style="padding: 10px 14px;">
                                        <?php if ($rec['due'] > 0): ?>
                                        <span class="fw-bold text-danger" style="font-size: 12px;">৳<?= number_format($rec['due'], 2) ?></span>
                                        <?php else: ?>
                                        <span class="text-slate-400 fw-medium" style="font-size: 11px;"><i class="bi bi-check2 text-success"></i> 0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="padding: 10px 14px;">
                                        <button type="button" class="btn btn-sm btn-outline-primary border-slate-200 py-0 px-2 shadow-none trigger-view-payments-btn" style="font-size: 11px; border-radius: 6px;" data-date="<?= $rec['date'] ?>" data-processor="<?= htmlspecialchars($rec['processor']) ?>" title="View Payments Breakdown">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= ($selected_processor === 'all') ? 8 : 7 ?>" class="text-center text-slate-400 py-5" style="font-size: 12px;">
                                        <i class="bi bi-inbox fs-3 d-block text-slate-300 mb-2"></i>
                                        No collection records found for this period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (count($date_records) > 0): ?>
                        <tfoot style="background-color: #f8fafc; border-top: 2px solid var(--border-slate-200);">
                            <tr>
                                <th colspan="<?= ($selected_processor === 'all') ? 4 : 3 ?>" class="text-slate-600 fw-bold" style="font-size: 11px; padding: 10px 14px;">Total</th>
                                <th class="text-end text-slate-900 fw-bold" style="font-size: 12px; padding: 10px 14px;">৳<?= number_format($month_total_collected, 2) ?></th>
                                <th class="text-end text-success fw-bold" style="font-size: 12px; padding: 10px 14px;">৳<?= number_format($month_total_handover, 2) ?></th>
                                <th class="text-end text-danger fw-bold" style="font-size: 12px; padding: 10px 14px;">৳<?= number_format($month_balance, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLE 2: HANDOVER RECORDS -->
        <div class="col-12 col-xl-6" id="handoverCol">
            <div class="dashboard-card p-0 border-0 h-100" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden; background: #fff;">
                
                <!-- Table Header / Toolbar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2 bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-arrow-up-circle fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-slate-800 mb-0" style="font-size: 13px;">Handover Records</h6>
                            <small class="text-slate-400" style="font-size: 11px;"><?= count($handover_records) ?> handover transactions</small>
                        </div>
                    </div>
                    <div>
                        <input type="text" id="searchHandoverTable" class="form-control form-control-sm border-slate-200 shadow-none" placeholder="Search handover..." style="width: 150px; font-size: 11px;">
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="handoverDataTable" style="border-collapse: collapse; width: 100%;">
                        <thead style="background-color: #f8fafc; border-bottom: 1px solid var(--border-slate-200);">
                            <tr>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px; width: 45px;">#</th>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">Handover Date</th>
                                <?php if ($selected_processor === 'all'): ?>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">Collector</th>
                                <?php endif; ?>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">Received By</th>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 14px;">For Date</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 10px 14px;">Amount</th>
                                <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 10px 14px; width: 95px;">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($handover_records) > 0): ?>
                                <?php $j = 1; foreach ($handover_records as $ho): ?>
                                <tr>
                                    <td class="text-slate-400" style="font-size: 11px; padding: 10px 14px;"><?= $j++ ?></td>
                                    <td style="padding: 10px 14px;">
                                        <div class="fw-semibold text-slate-800" style="font-size: 12px;"><?= date('d M, Y', strtotime($ho['handover_date'])) ?></div>
                                        <small class="text-slate-400" style="font-size: 10px;"><?= date('h:i A', strtotime($ho['handover_date'])) ?></small>
                                    </td>
                                    <?php if ($selected_processor === 'all'): ?>
                                    <td style="padding: 10px 14px;">
                                        <span class="badge bg-light text-slate-700 border border-slate-200 fw-medium" style="font-size: 11px;"><?= htmlspecialchars($ho['processor_name']) ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <td style="padding: 10px 14px;">
                                        <span class="text-slate-700 fw-medium" style="font-size: 12px;"><?= htmlspecialchars($ho['received_by'] ?? 'Admin') ?></span>
                                    </td>
                                    <td style="padding: 10px 14px;">
                                        <?php if (!empty($ho['for_date'])): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-medium" style="font-size: 10px;"><?= date('d M, y', strtotime($ho['for_date'])) ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-slate-100 text-slate-500 fw-normal" style="font-size: 10px;">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end" style="padding: 10px 14px;">
                                        <span class="fw-bold text-success" style="font-size: 12px;">৳<?= number_format($ho['amount'], 2) ?></span>
                                    </td>
                                    <td class="text-center" style="padding: 10px 14px; white-space: nowrap;">
                                        <a href="../print_handover_receipt.php?id=<?= $ho['id'] ?>&type=pos" target="_blank" class="btn btn-sm btn-outline-dark border-slate-200 py-0 px-2 me-1 shadow-none" style="font-size: 11px; border-radius: 6px;" title="POS Thermal Print">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <a href="../print_handover_receipt.php?id=<?= $ho['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary border-slate-200 py-0 px-2 shadow-none" style="font-size: 11px; border-radius: 6px;" title="Standard Print">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= ($selected_processor === 'all') ? 7 : 6 ?>" class="text-center text-slate-400 py-5" style="font-size: 12px;">
                                        <i class="bi bi-inbox fs-3 d-block text-slate-300 mb-2"></i>
                                        No handover records found for this period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (count($handover_records) > 0): ?>
                        <tfoot style="background-color: #f8fafc; border-top: 2px solid var(--border-slate-200);">
                            <tr>
                                <th colspan="<?= ($selected_processor === 'all') ? 5 : 4 ?>" class="text-slate-600 fw-bold" style="font-size: 11px; padding: 10px 14px;">Total Handed Over</th>
                                <th class="text-end text-success fw-bold" style="font-size: 12px; padding: 10px 14px;">৳<?= number_format($month_total_handover, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: View Date Payments Breakdown -->
<div class="modal fade" id="viewPaymentsModal" tabindex="-1" aria-labelledby="viewPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 1px solid #cbd5e1 !important;">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 44px; height: 44px;">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-slate-800 mb-0" id="viewPaymentsModalLabel" style="font-size: 17px;">Payment Records</h5>
                        <p class="text-slate-500 mb-0" style="font-size: 12px;">Breakdown for: <span id="vpDateDisplay" class="fw-bold text-primary"></span></p>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 8px;"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-white">
                <div id="viewPaymentsContent" class="position-relative" style="min-height: 160px;">
                    <!-- Content loaded via AJAX -->
                    <div class="d-flex justify-content-center align-items-center h-100 position-absolute w-100 top-0 start-0 bg-white" id="vpLoader" style="display: none !important;">
                        <div class="spinner-border text-primary" role="status" style="width: 1.75rem; height: 1.75rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    
    // View Payments Modal Trigger
    $(document).on('click', '.trigger-view-payments-btn', function() {
        var date = $(this).data('date');
        var processor = $(this).data('processor');
        
        $('#vpDateDisplay').text(date + (processor ? ' (' + processor + ')' : ''));
        $('#viewPaymentsContent').children().not('#vpLoader').remove();
        $('#vpLoader').attr('style', 'display: flex !important;');
        
        var modalEl = document.getElementById('viewPaymentsModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();

        $.ajax({
            url: '../ajax/get_date_payments.php',
            type: 'POST',
            data: { 
                processed_by: processor, 
                date: date 
            },
            success: function(response) {
                $('#vpLoader').attr('style', 'display: none !important;');
                $('#viewPaymentsContent').append(response);
            },
            error: function() {
                $('#vpLoader').attr('style', 'display: none !important;');
                $('#viewPaymentsContent').append('<div class="alert alert-danger py-2" style="font-size: 12px;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Failed to load payment records.</div>');
            }
        });
    });

    // View Mode Switcher (Both / Collections Only / Handovers Only)
    $('.view-mode-btn').on('click', function() {
        $('.view-mode-btn').removeClass('active text-white').addClass('text-slate-500 bg-transparent');
        $(this).addClass('active text-white').removeClass('text-slate-500 bg-transparent');
        
        var mode = $(this).data('view');
        if (mode === 'collections') {
            $('#collectionCol').removeClass('col-xl-6 d-none').addClass('col-12');
            $('#handoverCol').addClass('d-none');
        } else if (mode === 'handovers') {
            $('#handoverCol').removeClass('col-xl-6 d-none').addClass('col-12');
            $('#collectionCol').addClass('d-none');
        } else {
            // Both (side by side)
            $('#collectionCol').removeClass('col-12 d-none').addClass('col-12 col-xl-6');
            $('#handoverCol').removeClass('col-12 d-none').addClass('col-12 col-xl-6');
        }
    });

    // Quick Search: Collection Table
    $('#searchCollectionTable').on('keyup', function() {
        var val = $(this).val().toLowerCase();
        $('#collectionDataTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    // Quick Search: Handover Table
    $('#searchHandoverTable').on('keyup', function() {
        var val = $(this).val().toLowerCase();
        $('#handoverDataTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

});
</script>

<?php include '../includes/footer.php'; ?>

