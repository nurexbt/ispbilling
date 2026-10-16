<?php
require_once '../includes/db.php';
$base_path = '../';
$active_menu = 'dashboard';
include '../includes/header.php';
include 'sidebar.php';

if (!has_permission('billing_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view the billing portal.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

$current_month = date('m');
$current_year = date('Y');

function getStatCount($conn, $table, $current_month, $current_year, $is_monthly = false)
{
    $sql = "SELECT COUNT(*) as total FROM $table";
    if ($is_monthly) {
        $sql .= " WHERE MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'";
    }
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

$total_customers = getStatCount($conn, 'customer_tbl', $current_month, $current_year);
$monthly_customers = getStatCount($conn, 'customer_tbl', $current_month, $current_year, true);

$total_employees = getStatCount($conn, 'employee_tbl', $current_month, $current_year);
$monthly_employees = getStatCount($conn, 'employee_tbl', $current_month, $current_year, true);

$sql_paid_cust = "SELECT COUNT(*) as total FROM customer_tbl WHERE previous_due <= 0";
$res_paid_cust = $conn->query($sql_paid_cust);
$paid_customers = ($res_paid_cust && $r = $res_paid_cust->fetch_assoc()) ? $r['total'] : 0;

$sql_unpaid_cust = "SELECT COUNT(*) as total FROM customer_tbl WHERE previous_due > 0";
$res_unpaid_cust = $conn->query($sql_unpaid_cust);
$unpaid_customers = ($res_unpaid_cust && $r = $res_unpaid_cust->fetch_assoc()) ? $r['total'] : 0;

function getTicketStatusCount($conn, $status)
{
    $sql = "SELECT COUNT(*) as total FROM support_ticket_tbl WHERE status = '$status'";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

$total_tickets = getStatCount($conn, 'support_ticket_tbl', $current_month, $current_year);
$pending_tickets = getTicketStatusCount($conn, 'Pending');
$inprogress_tickets = getTicketStatusCount($conn, 'In Progress');
$review_tickets = getTicketStatusCount($conn, 'Review');
$cancelled_tickets = getTicketStatusCount($conn, 'Cancelled');
$completed_tickets = getTicketStatusCount($conn, 'Completed');

$billing_month_format = date('Y-m');
$sql_bill = "SELECT SUM(i.amount) as total_bill, SUM(i.paid_amount) as total_paid, SUM(c.discount) as total_discount 
             FROM invoice_tbl i 
             LEFT JOIN customer_tbl c ON i.customer_id = c.id 
             WHERE i.billing_month = '$billing_month_format'";
$res_bill = $conn->query($sql_bill);
$total_generated_bill = 0;
$total_paid_bill = 0;
$total_unpaid_bill = 0;
$total_discount_bill = 0;
if ($res_bill && $row_bill = $res_bill->fetch_assoc()) {
    $total_generated_bill = $row_bill['total_bill'] ?? 0;
    $total_paid_bill = $row_bill['total_paid'] ?? 0;
    $total_discount_bill = $row_bill['total_discount'] ?? 0;
    $total_unpaid_bill = $total_generated_bill - $total_paid_bill;
}


$today_date = date('Y-m-d');
$current_username = $conn->real_escape_string($_SESSION['username'] ?? '');

$sql_today_coll = "SELECT SUM(amount) as total FROM payment_tbl WHERE DATE(payment_date) = '$today_date' AND processed_by = '$current_username'";
$res_today_coll = $conn->query($sql_today_coll);
$today_collected = 0;
if ($res_today_coll && $row = $res_today_coll->fetch_assoc())
    $today_collected = $row['total'] ?? 0;

$sql_today_ho = "SELECT SUM(amount) as total FROM collector_handover_tbl WHERE DATE(handover_date) = '$today_date' AND processor_name = '$current_username'";
$res_today_ho = $conn->query($sql_today_ho);
$today_handover = 0;
if ($res_today_ho && $row = $res_today_ho->fetch_assoc())
    $today_handover = $row['total'] ?? 0;

$sql_all_coll = "SELECT SUM(amount) as total FROM payment_tbl WHERE processed_by = '$current_username'";
$res_all_coll = $conn->query($sql_all_coll);
$all_collected = 0;
if ($res_all_coll && $row = $res_all_coll->fetch_assoc())
    $all_collected = $row['total'] ?? 0;

$sql_all_ho = "SELECT SUM(amount) as total FROM collector_handover_tbl WHERE processor_name = '$current_username'";
$res_all_ho = $conn->query($sql_all_ho);
$all_handover = 0;
if ($res_all_ho && $row = $res_all_ho->fetch_assoc())
    $all_handover = $row['total'] ?? 0;

$not_handover = $all_collected - $all_handover;
if ($not_handover < 0)
    $not_handover = 0;

?>
<div class="container-fluid">

    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div
                class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Generated Bill This
                    Month</div>
                <div class="mb-3 d-flex align-items-center justify-content-center"
                    style="width: 70px; height: 70px; background-color: rgba(25, 118, 210, 0.1); color: #1976D2; border-radius: 50%; font-size: 2rem;">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div class="fs-2 fw-bold text-primary mb-1"><?= number_format($total_generated_bill, 2) ?></div>
                <div class="text-success" style="font-size: 0.75rem;"><i class="bi bi-calendar-check"></i> For
                    <?= date('F Y') ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div
                class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Unpaid This Month
                </div>
                <div class="mb-3 d-flex align-items-center justify-content-center"
                    style="width: 70px; height: 70px; background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 50%; font-size: 2rem;">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div class="fs-2 fw-bold text-danger mb-1"><?= number_format($total_unpaid_bill, 2) ?></div>
                <div class="text-danger" style="font-size: 0.75rem; opacity: 0.8;"><i class="bi bi-clock-history"></i>
                    Pending for <?= date('F Y') ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div
                class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Paid This Month</div>
                <div class="mb-3 d-flex align-items-center justify-content-center"
                    style="width: 70px; height: 70px; background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 50%; font-size: 2rem;">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="fs-2 fw-bold text-success mb-1"><?= number_format($total_paid_bill, 2) ?></div>
                <div class="text-success" style="font-size: 0.75rem; opacity: 0.8;"><i class="bi bi-graph-up-arrow"></i>
                    Collections for <?= date('F Y') ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div
                class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Discount This Month
                </div>
                <div class="mb-3 d-flex align-items-center justify-content-center"
                    style="width: 70px; height: 70px; background-color: rgba(245, 158, 11, 0.1); color: #F59E0B; border-radius: 50%; font-size: 2rem;">
                    <i class="bi bi-tags"></i>
                </div>
                <div class="fs-2 fw-bold text-warning mb-1"><?= number_format($total_discount_bill, 2) ?></div>
                <div class="text-warning" style="font-size: 0.75rem; opacity: 0.8;"><i class="bi bi-percent"></i>
                    Savings for <?= date('F Y') ?></div>
            </div>
        </div>
    </div>


    <!-- Today's Collection & Handover Row -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4"
                style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-left: 4px solid #38bdf8;">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Today's Grand Total
                    Collected</div>
                <div class="mb-3 d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm"
                    style="width: 70px; height: 70px; color: #0ea5e9; font-size: 2rem;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="fs-2 fw-bold mb-1" style="color: #0284c7;">৳ <?= number_format($today_collected, 2) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4"
                style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #4ade80;">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Today's Handover</div>
                <div class="mb-3 d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm"
                    style="width: 70px; height: 70px; color: #22c55e; font-size: 2rem;">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
                <div class="fs-2 fw-bold mb-1" style="color: #16a34a;">৳ <?= number_format($today_handover, 2) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4"
                style="background-color: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #fb7185;">
                <div class="fw-semibold text-slate-500 mb-3"
                    style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Not Handover Amount
                </div>
                <div class="mb-3 d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm"
                    style="width: 70px; height: 70px; color: #f43f5e; font-size: 2rem;">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
                <div class="fs-2 fw-bold mb-1" style="color: #e11d48;">৳ <?= number_format($not_handover, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Top Stat Cards -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-3 g-4 mb-4">
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Customer
                        </div>
                        <div class="fs-4 fw-bold text-info mb-1"><?= number_format($total_customers) ?></div>
                        <div class="text-success" style="font-size: 0.7rem;"><i class="bi bi-arrow-up"></i>
                            <?= number_format($monthly_customers) ?> this month</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(13, 202, 240, 0.1); color: #0DCAF0; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Paid Customer
                        </div>
                        <div class="fs-4 fw-bold text-success mb-1"><?= number_format($paid_customers) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Unpaid
                            Customer</div>
                        <div class="fs-4 fw-bold text-danger mb-1"><?= number_format($unpaid_customers) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-person-dash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Row -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-4 mb-4">
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Tickets
                        </div>
                        <div class="fs-4 fw-bold text-primary mb-1"><?= number_format($total_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(25, 118, 210, 0.1); color: #1976D2; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-ticket-detailed"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Pending</div>
                        <div class="fs-4 fw-bold text-warning mb-1"><?= number_format($pending_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.1); color: #F59E0B; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">In Progress
                        </div>
                        <div class="fs-4 fw-bold text-info mb-1"><?= number_format($inprogress_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(13, 202, 240, 0.1); color: #0DCAF0; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Review</div>
                        <div class="fs-4 fw-bold text-primary mb-1"><?= number_format($review_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(25, 118, 210, 0.1); color: #1976D2; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Completed
                        </div>
                        <div class="fs-4 fw-bold text-success mb-1"><?= number_format($completed_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold text-slate-500 mb-2"
                            style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Cancelled
                        </div>
                        <div class="fs-4 fw-bold text-danger mb-1"><?= number_format($cancelled_tickets) ?></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>



</div>

<?php include '../includes/footer.php'; ?>
