<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$base_path = '../';
$is_transmission = true;
$active_menu = 'dashboard';
include '../includes/header.php';
include 'sidebar.php';

$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 0;

// Fetch Connection Requests Count
$req_res = $conn->query("SELECT COUNT(id) as count FROM connection_request_tbl WHERE assigned_to = '$employee_id' AND status = 'Assigned'");
$conn_requests = $req_res ? $req_res->fetch_assoc()['count'] : 0;

$req_service_res = $conn->query("SELECT COUNT(id) as count FROM connection_request_tbl WHERE assigned_to = '$employee_id' AND status = 'Service created'");
$service_created_requests = $req_service_res ? $req_service_res->fetch_assoc()['count'] : 0;

// Fetch Expenses Count & Amount
$exp_pending_count = 0;
$exp_pending_amount = 0;
$exp_approved_count = 0;
$exp_approved_amount = 0;
$exp_res = $conn->query("SELECT status, COUNT(id) as count, SUM(amount) as total_amount FROM expense_tbl WHERE created_by = '$employee_id' AND status IN ('Pending', 'Approved') GROUP BY status");
if ($exp_res) {
    while($row = $exp_res->fetch_assoc()) {
        if ($row['status'] === 'Pending') {
            $exp_pending_count = $row['count'];
            $exp_pending_amount = $row['total_amount'] ?: 0;
        } elseif ($row['status'] === 'Approved') {
            $exp_approved_count = $row['count'];
            $exp_approved_amount = $row['total_amount'] ?: 0;
        }
    }
}

// Fetch Support Tickets Count (Active tickets: Pending, In Progress, Review)
$ticket_res = $conn->query("SELECT COUNT(id) as count FROM support_ticket_tbl WHERE status NOT IN ('Completed', 'Cancelled')");
$active_tickets = $ticket_res ? $ticket_res->fetch_assoc()['count'] : 0;

// Fetch Equipments
// Initialize all items with 0 count
$items_res = $conn->query("SELECT item_name FROM item_tbl ORDER BY item_name ASC");
$equipments = [];
if ($items_res) {
    while($row = $items_res->fetch_assoc()) {
        $equipments[$row['item_name']] = ['pending' => 0, 'on_hand' => 0];
    }
}

$equip_query = "SELECT i.item_name, s.delivery_status, cr.id as used_cr 
                FROM store_tbl s 
                JOIN item_tbl i ON s.item_id = i.id 
                LEFT JOIN connection_request_tbl cr ON s.barcode_id = cr.onu_sn 
                WHERE s.delivered_to = '$employee_id' AND (s.return_reason IS NULL OR s.return_reason = '')";
$equip_result = $conn->query($equip_query);
if ($equip_result) {
    while($row = $equip_result->fetch_assoc()) {
        $name = $row['item_name'];
        if (!isset($equipments[$name])) {
            $equipments[$name] = ['pending' => 0, 'on_hand' => 0];
        }
        if (empty($row['used_cr'])) {
            if ($row['delivery_status'] === 'pending') {
                $equipments[$name]['pending']++;
            } elseif ($row['delivery_status'] === 'accepted') {
                $equipments[$name]['on_hand']++;
            }
        }
    }
}
?>

<div class="container-fluid pt-2">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <h4 class="mb-0 text-slate-900 fw-bold">Transmission Dashboard</h4>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Connection Requests Card -->
        <div class="col-12 col-md-6 col-lg-3">
            <a href="connection_request.php" class="text-decoration-none">
                <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4" style="transition: all 0.2s;">
                    <div class="fw-semibold text-slate-500 mb-3" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Assigned Connection Requests
                    </div>
                    <div class="mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 50%; font-size: 2rem;">
                        <i class="bi bi-plug"></i>
                    </div>
                    <div class="fs-2 fw-bold text-success mb-1"><?= number_format($conn_requests) ?></div>
                    <div class="text-success" style="font-size: 0.75rem;"><i class="bi bi-person-fill"></i> Assigned to you</div>
                </div>
            </a>
        </div>

        <!-- Service Created Card -->
        <div class="col-12 col-md-6 col-lg-3">
            <a href="connection_request.php" class="text-decoration-none">
                <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4" style="transition: all 0.2s;">
                    <div class="fw-semibold text-slate-500 mb-3" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Service Created
                    </div>
                    <div class="mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(59, 130, 246, 0.1); color: #3B82F6; border-radius: 50%; font-size: 2rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="fs-2 fw-bold text-primary mb-1"><?= number_format($service_created_requests) ?></div>
                    <div class="text-primary" style="font-size: 0.75rem;"><i class="bi bi-person-check-fill"></i> Completed by you</div>
                </div>
            </a>
        </div>

        <!-- Support Tickets Card -->
        <div class="col-12 col-md-6 col-lg-3">
            <a href="support_tickets.php" class="text-decoration-none">
                <div class="dashboard-card h-100 d-flex flex-column justify-content-center align-items-center text-center py-4" style="transition: all 0.2s;">
                    <div class="fw-semibold text-slate-500 mb-3" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Active Support Tickets
                    </div>
                <div class="mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 50%; font-size: 2rem;">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="fs-2 fw-bold text-danger mb-1"><?= number_format($active_tickets) ?></div>
                <div class="text-danger" style="font-size: 0.75rem;"><i class="bi bi-clock-history"></i> Needs Attention</div>
            </div>
            </a>
        </div>

        <!-- Expenses Card -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="dashboard-card h-100 p-3 d-flex flex-column" style="transition: all 0.2s;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-semibold text-slate-500" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        My Expenses
                    </div>
                    <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.1); color: #F59E0B; border-radius: 50%; font-size: 1.2rem;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3 mt-auto">
                    <div class="d-flex justify-content-between align-items-center border-bottom border-slate-100 pb-2">
                        <div>
                            <div class="text-slate-400" style="font-size: 11px; text-transform: uppercase;">Pending (<span class="fw-bold"><?= $exp_pending_count ?></span>)</div>
                            <div class="fw-bold text-warning" style="font-size: 16px;">৳<?= number_format($exp_pending_amount, 2) ?></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-slate-400" style="font-size: 11px; text-transform: uppercase;">Approved (<span class="fw-bold"><?= $exp_approved_count ?></span>)</div>
                            <div class="fw-bold text-success" style="font-size: 16px;">৳<?= number_format($exp_approved_amount, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3 text-slate-800 fw-bold">My Equipments</h5>
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-4 g-4 mb-4">
        <?php if(empty($equipments)): ?>
            <div class="col-12 text-slate-500">No equipments assigned to you currently.</div>
        <?php else: ?>
            <?php foreach($equipments as $item_name => $counts): ?>
                <?php 
                    $total = $counts['pending'] + $counts['on_hand']; 
                ?>
                <div class="col">
                    <div class="dashboard-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-semibold text-slate-500 mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?= htmlspecialchars($item_name) ?>
                                </div>
                                <div class="fs-4 fw-bold text-primary"><?= number_format($total) ?> <span style="font-size:0.8rem; font-weight:normal;" class="text-slate-400">Total</span></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(25, 118, 210, 0.1); color: #1976D2; border-radius: 50%; font-size: 1.2rem;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top border-slate-100 pt-2" style="font-size: 0.8rem;">
                            <span class="text-warning"><i class="bi bi-hourglass-split"></i> Pending: <strong><?= number_format($counts['pending']) ?></strong></span>
                            <span class="text-success"><i class="bi bi-check2-circle"></i> On Hand: <strong><?= number_format($counts['on_hand']) ?></strong></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
