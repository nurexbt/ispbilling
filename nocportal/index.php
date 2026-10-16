<?php
require_once '../includes/db.php';
$base_path = '../';
$is_noc = true;
$active_menu = 'dashboard';
$page_title = 'NOC Dashboard';
$page_subtitle = 'Network Operations Center overview';
include '../includes/header.php';

if (!has_permission('noc_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to access the NOC portal.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Stats
$current_month = date('m');
$current_year  = date('Y');

function noc_count($conn, $table, $where = '') {
    $sql = "SELECT COUNT(*) as t FROM $table" . ($where ? " WHERE $where" : '');
    $r = $conn->query($sql);
    return ($r && $row = $r->fetch_assoc()) ? $row['t'] : 0;
}

$total_customers   = noc_count($conn, 'customer_tbl');
$total_hosts       = noc_count($conn, 'host_tbl');
$total_packages    = noc_count($conn, 'package_tbl');
$total_areas       = noc_count($conn, 'area_tbl');
$pending_conn      = noc_count($conn, 'connection_request_tbl', "status = 'Pending'");
$open_tickets      = noc_count($conn, 'support_ticket_tbl', "status = 'Open'");
$monthly_payments  = 0;
$pay_r = $conn->query("SELECT IFNULL(SUM(amount),0) as total FROM payment_tbl WHERE MONTH(created_at)='$current_month' AND YEAR(created_at)='$current_year'");
if ($pay_r && $pay_row = $pay_r->fetch_assoc()) $monthly_payments = $pay_row['total'];
$monthly_expenses  = 0;
$exp_r = $conn->query("SELECT IFNULL(SUM(amount),0) as total FROM expense_tbl WHERE MONTH(created_at)='$current_month' AND YEAR(created_at)='$current_year'");
if ($exp_r && $exp_row = $exp_r->fetch_assoc()) $monthly_expenses = $exp_row['total'];
?>

<div class="container-fluid pt-2">

    <!-- Page Title -->
    <div class="d-flex align-items-center gap-3 mb-4 mt-3">
        <div>
            <h5 class="mb-0 text-slate-900 fw-bold">NOC Dashboard</h5>
            <p class="text-slate-400 mb-0" style="font-size:12px;">Network Operations Center — real-time overview</p>
        </div>
    </div>

    <!-- Stats Row 1 -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(59,130,246,0.1);">
                        <i class="bi bi-people text-primary" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:10px;">Total</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($total_customers) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Customers</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(16,185,129,0.1);">
                        <i class="bi bi-hdd-network text-success" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:10px;">Active</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($total_hosts) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Hosts / Routers</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(245,158,11,0.1);">
                        <i class="bi bi-box-seam text-warning" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-warning rounded-pill" style="font-size:10px;">Plans</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($total_packages) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Packages</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(139,92,246,0.1);">
                        <i class="bi bi-geo-alt text-purple" style="font-size:18px;color:#8b5cf6;"></i>
                    </div>
                    <span class="badge rounded-pill" style="font-size:10px;background:rgba(139,92,246,0.1);color:#8b5cf6;">Zones</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($total_areas) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Areas</div>
            </div>
        </div>
    </div>

    <!-- Stats Row 2 -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(239,68,68,0.1);">
                        <i class="bi bi-diagram-3 text-danger" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size:10px;">Pending</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($pending_conn) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Connection Requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(239,68,68,0.1);">
                        <i class="bi bi-headset text-danger" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size:10px;">Open</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;"><?= number_format($open_tickets) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Support Tickets</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(16,185,129,0.1);">
                        <i class="bi bi-credit-card text-success" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:10px;">This Month</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;">৳<?= number_format($monthly_payments, 0) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Payments Collected</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 border-0 h-100" style="border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(239,68,68,0.1);">
                        <i class="bi bi-wallet2 text-danger" style="font-size:18px;"></i>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size:10px;">This Month</span>
                </div>
                <div class="fw-bold text-slate-900" style="font-size:24px;">৳<?= number_format($monthly_expenses, 0) ?></div>
                <div class="text-slate-400 mt-1" style="font-size:12px;">Expenses</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-3">
        <div class="col-12">
            <div class="dashboard-card p-3 border-0" style="border-radius:12px;">
                <div class="fw-semibold text-slate-700 mb-3" style="font-size:13px;">Quick Access</div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="host.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-hdd-network text-primary"></i> Host</a>
                    <a href="package.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-box-seam text-warning"></i> Package</a>
                    <a href="area.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-geo-alt" style="color:#8b5cf6;"></i> Area</a>
                    <a href="customer.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-people text-primary"></i> Customer</a>
                    <a href="connection_request.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-diagram-3 text-danger"></i> Connections</a>
                    <a href="payment_record.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-credit-card text-success"></i> Payments</a>
                    <a href="store_record.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-archive text-secondary"></i> Store</a>
                    <a href="expense.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-wallet2 text-danger"></i> Expense</a>
                    <a href="support_tickets.php" class="btn btn-sm btn-light border border-slate-200 d-flex align-items-center gap-2" style="font-size:12px;border-radius:8px;"><i class="bi bi-headset text-danger"></i> Support</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
