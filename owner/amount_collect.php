<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;
$active_menu = 'amount_collect';
$page_title = 'Amount Collect';
$page_subtitle = 'Owner collects bill from manager and oversees system collections';
include '../includes/header.php';

if (!has_permission('amount_collect_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view this page.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'includes/sidebar.php';

// Fetch filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// --- 1. MANAGERS QUERY & CALCULATIONS ---
// Managers are users with manager role / permissions or who receive collector handovers
$mgr_query = "SELECT 
    pu.id as user_id, 
    pu.username, 
    pu.employee_id, 
    COALESCE(e.full_name, pu.username) as full_name, 
    r.role_name
FROM portal_user_tbl pu
LEFT JOIN role_tbl r ON pu.role_id = r.id
LEFT JOIN employee_tbl e ON pu.employee_id = e.id
WHERE LOWER(r.role_name) LIKE '%manager%' 
   OR r.permissions LIKE '%\"manager_full_access\":1%' 
   OR r.permissions LIKE '%\"manager_full_access\":\"1\"%'
   OR pu.username IN (
       SELECT DISTINCT received_by 
       FROM collector_handover_tbl 
       WHERE received_by IS NOT NULL 
         AND received_by != '' 
         AND LOWER(received_by) NOT IN ('nur', 'owner', 'admin', 'sysadmin')
   )";
$mgr_res = $conn->query($mgr_query);

$managers = [];
$total_manager_collected = 0;
$total_manager_from_collectors = 0;
$total_manager_direct = 0;
$total_manager_handed_over = 0;
$total_manager_due = 0;

while ($mgr = $mgr_res->fetch_assoc()) {
    $u = $mgr['username'];
    
    // a) Amount received by this manager from collectors
    $st1_sql = "SELECT COALESCE(SUM(amount), 0) FROM collector_handover_tbl WHERE received_by = ?";
    $st1_params = [$u];
    $st1_types = "s";
    if (!empty($filter_date_from)) {
        $st1_sql .= " AND handover_date >= ?";
        $st1_params[] = $filter_date_from . " 00:00:00";
        $st1_types .= "s";
    }
    if (!empty($filter_date_to)) {
        $st1_sql .= " AND handover_date <= ?";
        $st1_params[] = $filter_date_to . " 23:59:59";
        $st1_types .= "s";
    }
    $st1 = $conn->prepare($st1_sql);
    $st1->bind_param($st1_types, ...$st1_params);
    $st1->execute();
    $st1->bind_result($from_cols);
    $st1->fetch();
    $st1->close();
    
    // b) Direct collections by manager from customers
    $st2_sql = "SELECT COALESCE(SUM(amount), 0) FROM payment_tbl WHERE processed_by = ?";
    $st2_params = [$u];
    $st2_types = "s";
    if (!empty($filter_date_from)) {
        $st2_sql .= " AND payment_date >= ?";
        $st2_params[] = $filter_date_from;
        $st2_types .= "s";
    }
    if (!empty($filter_date_to)) {
        $st2_sql .= " AND payment_date <= ?";
        $st2_params[] = $filter_date_to;
        $st2_types .= "s";
    }
    $st2 = $conn->prepare($st2_sql);
    $st2->bind_param($st2_types, ...$st2_params);
    $st2->execute();
    $st2->bind_result($direct_cols);
    $st2->fetch();
    $st2->close();
    
    // Total collected by this manager
    $mgr_collected = $from_cols + $direct_cols;
    
    // c) Amount handed over by manager to owner
    $st3_sql = "SELECT COALESCE(SUM(amount), 0) FROM collector_handover_tbl WHERE processor_name = ?";
    $st3_params = [$u];
    $st3_types = "s";
    if (!empty($filter_date_from)) {
        $st3_sql .= " AND handover_date >= ?";
        $st3_params[] = $filter_date_from . " 00:00:00";
        $st3_types .= "s";
    }
    if (!empty($filter_date_to)) {
        $st3_sql .= " AND handover_date <= ?";
        $st3_params[] = $filter_date_to . " 23:59:59";
        $st3_types .= "s";
    }
    $st3 = $conn->prepare($st3_sql);
    $st3->bind_param($st3_types, ...$st3_params);
    $st3->execute();
    $st3->bind_result($mgr_handed);
    $st3->fetch();
    $st3->close();
    
    $mgr_due = max(0, $mgr_collected - $mgr_handed);
    
    $mgr['from_collectors'] = $from_cols;
    $mgr['direct_collected'] = $direct_cols;
    $mgr['total_collected'] = $mgr_collected;
    $mgr['handed_over'] = $mgr_handed;
    $mgr['due'] = $mgr_due;
    
    $managers[] = $mgr;
    $total_manager_from_collectors += $from_cols;
    $total_manager_direct += $direct_cols;
    $total_manager_collected += $mgr_collected;
    $total_manager_handed_over += $mgr_handed;
    $total_manager_due += $mgr_due;
}

// --- 2. GLOBAL SYSTEM-WIDE METRICS & ALL PROCESSORS ---
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_date_from)) {
    $where_clauses[] = "payment_date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "payment_date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}
$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT 
    processed_by, 
    SUM(amount) AS total_collected 
  FROM payment_tbl 
  WHERE $where_sql 
  GROUP BY processed_by 
  ORDER BY total_collected DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch handovers for all processors
$ho_where_clauses = ["1=1"];
$ho_params = [];
$ho_types = "";

if (!empty($filter_date_from)) {
    $ho_where_clauses[] = "handover_date >= ?";
    $ho_params[] = $filter_date_from . " 00:00:00";
    $ho_types .= "s";
}
if (!empty($filter_date_to)) {
    $ho_where_clauses[] = "handover_date <= ?";
    $ho_params[] = $filter_date_to . " 23:59:59";
    $ho_types .= "s";
}
$ho_where_sql = implode(" AND ", $ho_where_clauses);

$ho_query = "SELECT processor_name, SUM(amount) as total FROM collector_handover_tbl WHERE $ho_where_sql GROUP BY processor_name";
$ho_stmt = $conn->prepare($ho_query);
if (!empty($ho_params)) {
    $ho_stmt->bind_param($ho_types, ...$ho_params);
}
$ho_stmt->execute();
$ho_res = $ho_stmt->get_result();
$handovers = [];
$total_handover_all = 0;
while ($ho = $ho_res->fetch_assoc()) {
    $handovers[$ho['processor_name']] = $ho['total'];
    $total_handover_all += $ho['total'];
}
$ho_stmt->close();

$collectors = [];
$grand_total = 0;
while ($row = $result->fetch_assoc()) {
    $collectors[] = $row;
    $grand_total += $row['total_collected'];
}
$stmt->close();
?>

<div class="container-fluid pt-2">

    <!-- EXTRA 3 CARDS: MANAGER COLLECTION & HANDOVER SUMMARY -->
    <div class="d-flex align-items-center justify-content-between mb-2 mt-1">
        <div class="d-flex align-items-center gap-2">
            <span class="badge px-2 py-1" style="background: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 700; border-radius: 6px;">
                <i class="bi bi-shield-check me-1"></i> OWNER COLLECTION
            </span>
            <span class="text-slate-700 fw-bold" style="font-size: 13px;">Manager Collection & Handover Overview</span>
        </div>
        <span class="text-muted d-none d-sm-inline" style="font-size: 11px;">
            <i class="bi bi-info-circle me-1"></i>Owner collects directly from Manager
        </span>
    </div>

    <!-- The 3 Extra Cards Required -->
    <div class="row g-3 mb-3">
        <!-- Card 1: Manager Total Collected -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 border-0 h-100" style="border-left: 4px solid #6366f1 !important; border-radius: 12px; background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%); box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="text-slate-700 fw-bold" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Manager Total Collected</div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: #e0e7ff;">
                        <i class="bi bi-wallet2" style="color: #6366f1; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fw-bold mb-1" style="font-size: 24px; color: #4338ca; letter-spacing: -0.5px;">৳<?= number_format($total_manager_collected, 2) ?></div>
                <div class="text-slate-500" style="font-size: 11px;">
                    <i class="bi bi-info-circle me-1"></i>(which amount collected manager)
                </div>
                <div class="d-flex gap-2 mt-2 pt-2 border-top border-indigo-100" style="font-size: 11px; color: #4f46e5;">
                    <span>From Collectors: <strong>৳<?= number_format($total_manager_from_collectors, 2) ?></strong></span>
                    <span>•</span>
                    <span>Direct: <strong>৳<?= number_format($total_manager_direct, 2) ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Card 2: Manager Handed Over -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 border-0 h-100" style="border-left: 4px solid #10b981 !important; border-radius: 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="text-slate-700 fw-bold" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Manager Handed Over</div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: #bbf7d0;">
                        <i class="bi bi-box-arrow-right" style="color: #059669; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fw-bold mb-1" style="font-size: 24px; color: #047857; letter-spacing: -0.5px;">৳<?= number_format($total_manager_handed_over, 2) ?></div>
                <div class="text-slate-500" style="font-size: 11px;">
                    <i class="bi bi-check2-circle me-1"></i>(Manager to Owner)
                </div>
                <div class="mt-2 pt-2 border-top border-success-subtle text-success fw-semibold" style="font-size: 11px;">
                    Collected by Owner from Manager
                </div>
            </div>
        </div>

        <!-- Card 3: Manager Total Due -->
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 border-0 h-100" style="border-left: 4px solid #f43f5e !important; border-radius: 12px; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); box-shadow: 0 2px 8px rgba(244, 63, 94, 0.08);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="text-slate-700 fw-bold" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Manager Total Due</div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: #fecdd3;">
                        <i class="bi bi-hourglass-split" style="color: #e11d48; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fw-bold mb-1" style="font-size: 24px; color: #be123c; letter-spacing: -0.5px;">৳<?= number_format($total_manager_due, 2) ?></div>
                <div class="text-slate-500" style="font-size: 11px;">
                    <i class="bi bi-exclamation-circle me-1"></i>(Manager Total Collected - Manager Handed Over)
                </div>
                <div class="mt-2 pt-2 border-top border-danger-subtle text-danger fw-semibold" style="font-size: 11px;">
                    Pending for Owner to collect
                </div>
            </div>
        </div>
    </div>

    <!-- SYSTEM-WIDE SUMMARY ROW -->
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-4">
            <div class="p-2 px-3 border-0 rounded-3 d-flex align-items-center justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                <div>
                    <span class="text-slate-500" style="font-size: 10px; font-weight: 600; text-transform: uppercase;">System Grand Collected:</span>
                    <span class="ms-1 fw-bold text-slate-700" style="font-size: 13px;">৳<?= number_format($grand_total, 2) ?></span>
                </div>
                <i class="bi bi-database text-slate-400"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-2 px-3 border-0 rounded-3 d-flex align-items-center justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                <div>
                    <span class="text-slate-500" style="font-size: 10px; font-weight: 600; text-transform: uppercase;">All Processors Handed:</span>
                    <span class="ms-1 fw-bold text-slate-700" style="font-size: 13px;">৳<?= number_format($total_handover_all, 2) ?></span>
                </div>
                <i class="bi bi-arrow-left-right text-slate-400"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-2 px-3 border-0 rounded-3 d-flex align-items-center justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                <div>
                    <span class="text-slate-500" style="font-size: 10px; font-weight: 600; text-transform: uppercase;">Total System Due:</span>
                    <span class="ms-1 fw-bold text-slate-700" style="font-size: 13px;">৳<?= number_format(max(0, $grand_total - $total_handover_all), 2) ?></span>
                </div>
                <i class="bi bi-clock-history text-slate-400"></i>
            </div>
        </div>
    </div>

    <!-- MAIN CARD WITH TABS -->
    <div class="dashboard-card p-0 border-0" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden;">

        <!-- Filter & Search Toolbar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2 bg-white">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center" id="filterForm">
                    <input type="date" name="date_from" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_from) ?>" style="width: 135px; font-size: 12px;" placeholder="From Date" title="From Date">
                    <input type="date" name="date_to" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_to) ?>" style="width: 135px; font-size: 12px;" placeholder="To Date" title="To Date">
                    <button type="submit" class="btn btn-sm btn-primary shadow-none px-3" style="font-size: 12px;"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="amount_collect.php" class="btn btn-sm btn-light border-slate-200 shadow-none px-3" style="font-size: 12px;"><i class="bi bi-x-circle me-1"></i>Clear</a>
                </form>
            </div>
            <div>
                <input type="text" id="searchTableInput" class="toolbar-input form-control form-control-sm border-slate-200 shadow-none" placeholder="Search by name..." style="width: 210px; font-size: 12px;">
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom border-slate-200" id="collectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-dark px-3 py-2" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managersTabPane" type="button" role="tab" aria-controls="managersTabPane" aria-selected="true" style="font-size: 12px; border-radius: 8px 8px 0 0;">
                    <i class="bi bi-person-badge-fill text-indigo me-1" style="color: #6366f1;"></i> Managers (Owner Bill Collection)
                    <span class="badge bg-indigo ms-1 px-2" style="background-color: #6366f1; font-size: 10px;"><?= count($managers) ?></span>
                    <?php if ($total_manager_due > 0): ?>
                    <span class="badge bg-danger ms-1 px-2" style="font-size: 10px;">Due: ৳<?= number_format($total_manager_due, 2) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-slate-600 px-3 py-2" id="collectors-tab" data-bs-toggle="tab" data-bs-target="#collectorsTabPane" type="button" role="tab" aria-controls="collectorsTabPane" aria-selected="false" style="font-size: 12px; border-radius: 8px 8px 0 0;">
                    <i class="bi bi-people me-1"></i> Field Collectors Breakdown (View Only)
                    <span class="badge bg-secondary ms-1 px-2" style="font-size: 10px;"><?= count($collectors) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="collectTabsContent">
            
            <!-- TAB 1: MANAGERS (OWNER BILL COLLECTION) -->
            <div class="tab-pane fade show active" id="managersTabPane" role="tabpanel" aria-labelledby="managers-tab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="managersTable" style="border-collapse: collapse; min-width: 800px;">
                        <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 50px;">#</th>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Manager</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">From Collectors</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Direct Collection</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Manager Total Collected</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Handed Over to Owner</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Manager Total Due</th>
                                <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 12px 16px; width: 220px;">Action</th>
                            </tr>
                        </thead>
                        <tbody style="border-top: 1px solid var(--border-slate-200);">
                            <?php if (count($managers) > 0): ?>
                                <?php $i = 1; foreach ($managers as $mgr): ?>
                                <tr>
                                    <td class="text-slate-400" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                    <td style="padding: 12px 16px;">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-white fw-bold shadow-sm" style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); font-size: 12px;">
                                                <?= strtoupper(substr($mgr['username'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="text-slate-800 fw-bold" style="font-size: 13px;"><?= htmlspecialchars($mgr['full_name']) ?></div>
                                                <div class="text-slate-500" style="font-size: 11px;">@<?= htmlspecialchars($mgr['username']) ?> <span class="badge bg-light text-primary border" style="font-size: 9px;"><?= htmlspecialchars($mgr['role_name'] ?? 'Manager') ?></span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end" style="padding: 12px 16px;">
                                        <div class="text-slate-700" style="font-size: 13px; font-weight: 500;">৳<?= number_format($mgr['from_collectors'], 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 12px 16px;">
                                        <div class="text-slate-700" style="font-size: 13px; font-weight: 500;">৳<?= number_format($mgr['direct_collected'], 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 12px 16px;">
                                        <div class="fw-bold" style="font-size: 13px; color: #4338ca;">৳<?= number_format($mgr['total_collected'], 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 12px 16px;">
                                        <div class="text-success fw-bold" style="font-size: 13px;">৳<?= number_format($mgr['handed_over'], 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 12px 16px;">
                                        <div class="text-danger fw-bold" style="font-size: 14px;">৳<?= number_format($mgr['due'], 2) ?></div>
                                    </td>
                                    <td class="text-center" style="padding: 12px 16px; white-space: nowrap;">
                                        <?php if (has_permission('amount_collect_handover')): ?>
                                        <button class="btn btn-sm btn-success fw-semibold trigger-manager-collect-btn shadow-sm px-2 py-1 me-1" style="font-size: 11px; border-radius: 6px;" 
                                                data-manager="<?= htmlspecialchars($mgr['username']) ?>" 
                                                data-name="<?= htmlspecialchars($mgr['full_name']) ?>" 
                                                data-due="<?= $mgr['due'] ?>" 
                                                <?= $mgr['due'] <= 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-cash-stack me-1"></i> Collect Bill
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (has_permission('amount_collect_ledger_menu')): ?>
                                        <button class="btn btn-sm btn-outline-primary border-slate-200 fw-medium view-ledger-btn shadow-sm rounded-pill px-2 py-1" style="font-size: 11px;" 
                                                data-processor="<?= htmlspecialchars($mgr['username']) ?>" 
                                                data-is-manager="1">
                                            <i class="bi bi-wallet2 me-1"></i> Ledger
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-slate-500 py-4" style="font-size: 12px;">No managers found in the system.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: FIELD COLLECTORS (VIEW ONLY) -->
            <div class="tab-pane fade" id="collectorsTabPane" role="tabpanel" aria-labelledby="collectors-tab">
                <div class="p-3 bg-light border-bottom border-slate-100 d-flex align-items-center">
                    <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                    <div style="font-size: 12px; color: #475569;">
                        <strong>Field Collectors Breakdown:</strong> Field collectors hand over their collected amounts to the Manager. As Owner, you collect the accumulated cash directly from the Manager in the <strong>Managers tab</strong>.
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="collectorsTable" style="border-collapse: collapse; min-width: 650px;">
                        <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 60px;">#</th>
                                <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Processor / Collector</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Total Collected</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Handed Over</th>
                                <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Due Amount</th>
                                <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 12px 16px; width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody style="border-top: 1px solid var(--border-slate-200);">
                            <?php if (count($collectors) > 0): ?>
                                <?php $j = 1; foreach ($collectors as $col): ?>
                                <?php 
                                    $col_handed = $handovers[$col['processed_by']] ?? 0;
                                    $col_due = max(0, $col['total_collected'] - $col_handed);
                                ?>
                                <tr>
                                    <td class="text-slate-400" style="font-size: 11px; padding: 11px 16px;"><?= $j++ ?></td>
                                    <td style="padding: 11px 16px;">
                                        <div class="text-slate-800 fw-semibold" style="font-size: 13px;"><?= htmlspecialchars($col['processed_by'] ?? 'System') ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 11px 16px;">
                                        <div class="text-slate-800 fw-bold" style="font-size: 13px;">৳<?= number_format($col['total_collected'], 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 11px 16px;">
                                        <div class="text-success fw-bold" style="font-size: 13px;">৳<?= number_format($col_handed, 2) ?></div>
                                    </td>
                                    <td class="text-end" style="padding: 11px 16px;">
                                        <div class="text-danger fw-bold" style="font-size: 13px;">৳<?= number_format($col_due, 2) ?></div>
                                    </td>
                                    <td class="text-center" style="padding: 11px 16px;">
                                        <?php if (has_permission('amount_collect_ledger_menu')): ?>
                                        <button class="btn btn-sm btn-outline-primary border-slate-200 fw-medium view-ledger-btn shadow-sm rounded-pill px-3 py-1" style="font-size: 11px;" 
                                                data-processor="<?= htmlspecialchars($col['processed_by'] ?? 'System') ?>" 
                                                data-is-manager="0">
                                            <i class="bi bi-wallet2 me-1"></i> Ledger
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-slate-500 py-4" style="font-size: 12px;">No collection records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Ledger Modal -->
<div class="modal fade" id="ledgerModal" tabindex="-1" aria-labelledby="ledgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 1px solid #cbd5e1 !important;">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-wallet2 text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-slate-800 mb-1" id="ledgerModalLabel" style="font-size: 18px;">Processor / Manager Ledger</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-slate-500 me-2" style="font-size: 12px;">Overview for:</span>
                            <span class="badge bg-primary px-3 py-1 fw-semibold shadow-sm" style="font-size: 12px; border-radius: 6px;"><i class="bi bi-person-fill me-1"></i> <span id="ledgerProcessorName"></span></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 10px;"></button>
                </div>
            </div>
            <div class="modal-body pt-4 px-4 pb-4 bg-white">
                <div id="ledgerContent" class="position-relative" style="min-height: 200px;">
                    <!-- Ledger content will be loaded here via AJAX -->
                    <div class="d-flex justify-content-center align-items-center h-100 position-absolute w-100 top-0 start-0 bg-white" id="ledgerLoader" style="display: none !important;">
                        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collect Amount Modal (Owner collects from Manager) -->
<div class="modal fade" id="collectModal" tabindex="-1" aria-labelledby="collectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid #bbf7d0 !important;">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-cash-stack text-success fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-slate-800 mb-0" id="collectModalLabel" style="font-size: 18px;">Collect Bill from Manager</h5>
                        <p class="text-slate-500 mb-0" style="font-size: 12px;">Receive cash handover from manager to owner</p>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 10px;"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-white">
                <form id="collectForm">
                    <input type="hidden" name="processor_name" id="collectProcessorName">
                    <input type="hidden" name="for_date" id="collectForDate">
                    <input type="hidden" name="is_manager" value="1">
                    <input type="hidden" name="is_owner" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Manager Name</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-slate-400"><i class="bi bi-person-badge"></i></span>
                            <input type="text" id="collectProcessorDisplay" class="form-control bg-light border-start-0 ps-0 text-slate-700 fw-bold" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Manager Due</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-slate-400">৳</span>
                                <input type="text" id="collectDueAmount" class="form-control bg-light border-start-0 ps-0 text-danger fw-bold" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Amount to Collect <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm shadow-sm rounded">
                                <span class="input-group-text bg-white border-success border-end-0 text-success fw-bold">৳</span>
                                <input type="number" name="amount" id="collectInputAmount" class="form-control border-success border-start-0 ps-0 fw-bold text-success" style="font-size: 14px;" step="0.01" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div id="collectSuccessBox" class="alert alert-success d-none mb-3 py-2 px-3" style="font-size: 12px;">
                        <div class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i> Amount collected successfully!</div>
                        <div class="d-flex gap-2 mt-2">
                            <a id="btnPosReceipt" href="#" target="_blank" class="btn btn-sm btn-dark px-2 py-1" style="font-size: 11px;"><i class="bi bi-receipt me-1"></i> POS Receipt</a>
                            <a id="btnNormalReceipt" href="#" target="_blank" class="btn btn-sm btn-outline-dark px-2 py-1" style="font-size: 11px;"><i class="bi bi-printer me-1"></i> Normal Print</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm py-2" id="collectSubmitBtn" style="border-radius: 8px;">
                        <i class="bi bi-check2-circle me-1"></i> Confirm Collection
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Payments Modal -->
<div class="modal fade" id="viewPaymentsModal" tabindex="-1" aria-labelledby="viewPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="viewPaymentsModalLabel">Records for <span id="vpDateDisplay" class="text-primary"></span></h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="viewPaymentsContent" class="position-relative" style="min-height: 200px;">
                    <!-- Content loaded via AJAX -->
                    <div class="d-flex justify-content-center align-items-center h-100 position-absolute w-100 top-0 start-0 bg-white" id="vpLoader" style="display: none !important;">
                        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Search filter across visible table rows
        $('#searchTableInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#managersTable tbody tr, #collectorsTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });

        // Trigger Collection from Manager Table
        $('.trigger-manager-collect-btn').on('click', function() {
            var manager = $(this).data('manager');
            var name = $(this).data('name') || manager;
            var due = $(this).data('due');

            $('#collectProcessorName').val(manager);
            $('#collectProcessorDisplay').val(name + ' (@' + manager + ')');
            $('#collectForDate').val('');
            $('#collectDueAmount').val(due);
            $('#collectInputAmount').val(due);
            $('#collectInputAmount').attr('max', due);
            $('#collectSuccessBox').addClass('d-none');
            $('#collectSubmitBtn').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Confirm Collection');

            var collectModal = new bootstrap.Modal(document.getElementById('collectModal'));
            collectModal.show();
        });

        // Trigger Ledger Modal
        $(document).on('click', '.view-ledger-btn', function() {
            var processor = $(this).data('processor');
            var isManager = $(this).data('is-manager') || 0;
            
            $('#ledgerProcessorName').text(processor);
            $('#ledgerContent').children().not('#ledgerLoader').remove();
            $('#ledgerLoader').attr('style', 'display: flex !important;');
            
            var modal = new bootstrap.Modal(document.getElementById('ledgerModal'));
            modal.show();

            $.ajax({
                url: '../ajax/get_processor_ledger.php',
                type: 'POST',
                data: { processed_by: processor, is_manager: 1, is_owner: 1, is_manager_ledger: isManager },
                success: function(response) {
                    $('#ledgerLoader').attr('style', 'display: none !important;');
                    $('#ledgerContent').append(response);
                },
                error: function() {
                    $('#ledgerLoader').attr('style', 'display: none !important;');
                    $('#ledgerContent').append('<div class="alert alert-danger">An error occurred while loading the ledger.</div>');
                }
            });
        });

        // Trigger Collect Modal from within the Ledger Modal
        $(document).on('click', '.trigger-handover-btn', function() {
            var processor = $(this).data('processor');
            var due = $(this).data('due');
            
            var ledgerModalEl = document.getElementById('ledgerModal');
            var ledgerModal = bootstrap.Modal.getInstance(ledgerModalEl);
            if (ledgerModal) {
                ledgerModal.hide();
            }
            
            $('#collectProcessorName').val(processor);
            $('#collectProcessorDisplay').val(processor);
            $('#collectForDate').val('');
            $('#collectDueAmount').val(due);
            $('#collectInputAmount').val(due);
            $('#collectInputAmount').attr('max', due);
            $('#collectSuccessBox').addClass('d-none');
            $('#collectSubmitBtn').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Confirm Collection');
            
            var collectModal = new bootstrap.Modal(document.getElementById('collectModal'));
            collectModal.show();
        });

        // Trigger Collect Modal for a specific date
        $(document).on('click', '.trigger-handover-date-btn', function() {
            var processor = $(this).data('processor');
            var date = $(this).data('date');
            var due = $(this).data('due');
            
            var ledgerModalEl = document.getElementById('ledgerModal');
            var ledgerModal = bootstrap.Modal.getInstance(ledgerModalEl);
            if (ledgerModal) {
                ledgerModal.hide();
            }
            
            $('#collectProcessorName').val(processor);
            $('#collectProcessorDisplay').val(processor + ' (For Date: ' + date + ')');
            $('#collectForDate').val(date);
            $('#collectDueAmount').val(due);
            $('#collectInputAmount').val(due);
            $('#collectInputAmount').attr('max', due);
            $('#collectSuccessBox').addClass('d-none');
            $('#collectSubmitBtn').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Confirm Collection');
            
            var collectModal = new bootstrap.Modal(document.getElementById('collectModal'));
            collectModal.show();
        });

        // Handle Collection Form Submission
        $('#collectForm').on('submit', function(e) {
            e.preventDefault();
            var submitBtn = $('#collectSubmitBtn');
            submitBtn.prop('disabled', true).text('Processing Collection...');
            
            $.ajax({
                url: '../ajax/collect_from_processor.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        if (typeof showToast === 'function') {
                            showToast('success', res.message);
                        }
                        if (res.handover_id) {
                            $('#btnPosReceipt').attr('href', '../print_handover_receipt.php?id=' + res.handover_id + '&type=pos');
                            $('#btnNormalReceipt').attr('href', '../print_handover_receipt.php?id=' + res.handover_id);
                            $('#collectSuccessBox').removeClass('d-none');
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            setTimeout(function() { location.reload(); }, 1200);
                        }
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('error', res.message || 'Error collecting amount.');
                        } else {
                            alert(res.message || 'Error collecting amount.');
                        }
                        submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Confirm Collection');
                    }
                },
                error: function() {
                    if (typeof showToast === 'function') {
                        showToast('error', 'An error occurred during submission.');
                    } else {
                        alert('An error occurred during submission.');
                    }
                    submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Confirm Collection');
                }
            });
        });

        // Trigger View Payments / Collections Modal for Date
        $(document).on('click', '.trigger-view-payments-btn', function() {
            var processor = $(this).data('processor');
            var date = $(this).data('date');
            var isManager = $(this).data('is-manager') || 0;
            
            $('#vpDateDisplay').text(date);
            $('#viewPaymentsContent').children().not('#vpLoader').remove();
            $('#vpLoader').attr('style', 'display: flex !important;');
            
            var modal = new bootstrap.Modal(document.getElementById('viewPaymentsModal'));
            modal.show();

            $.ajax({
                url: '../ajax/get_date_payments.php',
                type: 'POST',
                data: { processed_by: processor, date: date, is_manager_ledger: isManager },
                success: function(response) {
                    $('#vpLoader').attr('style', 'display: none !important;');
                    $('#viewPaymentsContent').append(response);
                },
                error: function() {
                    $('#vpLoader').attr('style', 'display: none !important;');
                    $('#viewPaymentsContent').append('<div class="alert alert-danger">Error loading payments.</div>');
                }
            });
        });
    });
</script>
<?php include '../includes/footer.php'; ?>
