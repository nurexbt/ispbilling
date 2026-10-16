<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;



$active_menu = 'support_tickets';
$page_title = 'Support Tickets';
$page_subtitle = 'Manage support tickets and track progress';

// Fetch current database time for accurate duration calculations
$db_time_res = $conn->query("SELECT NOW() as current_db_time");
$current_db_time = $db_time_res->fetch_assoc()['current_db_time'];
$current_timestamp = strtotime($current_db_time);

// Helper function for time duration
function get_time_duration($timestamp, $current_timestamp) {
    $created_time = strtotime($timestamp);
    $diff = $current_timestamp - $created_time;
    
    if ($diff < 0) $diff = 0;
    
    if ($diff < 60) return "Just now";
    
    $days = floor($diff / (60 * 60 * 24));
    $hours = floor(($diff - $days * 60 * 60 * 24) / (60 * 60));
    $minutes = floor(($diff - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = $days . 'd';
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    
    return implode(' ', $parts);
}

require_once '../includes/auth.php';

// Detect if user is a transmission portal user or manager and set flag for sidebar
if (has_permission('transmission_full_access') && !has_permission('dashboard_view')) {
    $is_transmission = true;
} elseif (has_permission('manager_full_access') && !has_permission('dashboard_view')) {
    $is_owner = true;
$is_manager = true;
}

include '../includes/header.php';
include 'includes/sidebar.php';


// Fetch summary metrics
$total_tickets = 0;
$open_tickets = 0;
$resolved_tickets = 0;

$res_summary = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN status = 'Review' THEN 1 ELSE 0 END) as review_count,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
FROM support_ticket_tbl");
if ($res_summary) {
    $summary = $res_summary->fetch_assoc();
    $total_tickets = $summary['total'] ?? 0;
    $pending_tickets = $summary['pending_count'] ?? 0;
    $in_progress_tickets = $summary['in_progress_count'] ?? 0;
    $review_tickets = $summary['review_count'] ?? 0;
    $completed_tickets = $summary['completed_count'] ?? 0;
    $cancelled_tickets = $summary['cancelled_count'] ?? 0;
}

// Fetch Categories for dropdown
$categories = [];
$res_cat = $conn->query("SELECT * FROM ticket_category_tbl ORDER BY name ASC");
while ($row = $res_cat->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch Customers for dropdown
$customers = [];
$res_cust = $conn->query("SELECT id, username, name FROM customer_tbl ORDER BY name ASC");
if ($res_cust) {
    while ($row = $res_cust->fetch_assoc()) {
        $customers[] = $row;
    }
}

$view = $_GET['view'] ?? 'active';
$status_filter = ($view == 'completed') ? "WHERE t.status = 'Completed'" : "WHERE t.status != 'Completed'";

// Fetch Tickets
$tickets = [];
$query = "SELECT t.*, c.name as category_name, cust.name as customer_name, cust.username as customer_username 
          FROM support_ticket_tbl t 
          LEFT JOIN ticket_category_tbl c ON t.category_id = c.id 
          LEFT JOIN customer_tbl cust ON t.customer_id = cust.id
          $status_filter
          ORDER BY t.created_at DESC";
$res_tickets = $conn->query($query);
while ($row = $res_tickets->fetch_assoc()) {
    $tickets[] = $row;
}

// Fetch Attachments
$attachments = [];
$res_att = $conn->query("SELECT * FROM ticket_attachment_tbl");
if ($res_att) {
    while ($row = $res_att->fetch_assoc()) {
        $attachments[$row['ticket_id']][] = $row;
    }
}
?>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
/* Select2 Custom Styles */
.select2-container--bootstrap-5 .select2-selection {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    min-height: 38px;
    padding: 0.375rem 0.75rem;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    font-size: 14px;
    color: #334155;
    padding: 0;
}
</style>

<div class="container-fluid pt-2">

    <!-- Summary Cards -->
    <div class="row g-3 mt-1 mb-3">
        <!-- Total Tickets -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #6366f1 !important; border-radius: 12px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-ticket-detailed fs-5" style="color: #6366f1;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Tickets</div>
                    <div class="fw-bold" style="font-size: 20px; color: #4f46e5; line-height: 1;"><?= $total_tickets ?></div>
                </div>
            </div>
        </div>
        <!-- Pending -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #64748b !important; border-radius: 12px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-clock-history fs-5" style="color: #64748b;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Pending</div>
                    <div class="fw-bold" style="font-size: 20px; color: #475569; line-height: 1;"><?= $pending_tickets ?></div>
                </div>
            </div>
        </div>
        <!-- In Progress -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #0284c7 !important; border-radius: 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-gear-fill fs-5" style="color: #0284c7;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">In Progress</div>
                    <div class="fw-bold" style="font-size: 20px; color: #0369a1; line-height: 1;"><?= $in_progress_tickets ?></div>
                </div>
            </div>
        </div>
        <!-- Review -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #d97706 !important; border-radius: 12px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-eye-fill fs-5" style="color: #d97706;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Review</div>
                    <div class="fw-bold" style="font-size: 20px; color: #b45309; line-height: 1;"><?= $review_tickets ?></div>
                </div>
            </div>
        </div>
        <!-- Completed -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #16a34a !important; border-radius: 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-check-circle-fill fs-5" style="color: #16a34a;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Completed</div>
                    <div class="fw-bold" style="font-size: 20px; color: #15803d; line-height: 1;"><?= $completed_tickets ?></div>
                </div>
            </div>
        </div>
        <!-- Cancelled -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm" style="border-left: 4px solid #dc2626 !important; border-radius: 12px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-x-circle-fill fs-5" style="color: #dc2626;"></i>
                </div>
                <div>
                    <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Cancelled</div>
                    <div class="fw-bold" style="font-size: 20px; color: #b91c1c; line-height: 1;"><?= $cancelled_tickets ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="dashboard-card p-0 border-0" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden;">

        <!-- Toolbar / Filters -->
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2">
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('support_ticket_add')): ?>
                <button class="btn btn-sm btn-primary shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#addTicketModal" style="font-size: 12px; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> New Ticket
                </button>
                <button class="btn btn-sm btn-light border-slate-200 shadow-sm px-3 text-slate-700" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal" style="font-size: 12px; border-radius: 6px;">
                    <i class="bi bi-tags me-1"></i> Manage Categories
                </button>
                <?php endif; ?>
                <?php if ($view == 'completed'): ?>
                <a href="support_tickets.php" class="btn btn-sm btn-light border-slate-200 shadow-sm px-3 text-slate-700" style="font-size: 12px; border-radius: 6px;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Active
                </a>
                <?php else: ?>
                <a href="support_tickets.php?view=completed" class="btn btn-sm btn-success shadow-sm px-3 text-white" style="font-size: 12px; border-radius: 6px;">
                    <i class="bi bi-check2-all me-1"></i> Completed Tickets
                </a>
                <?php endif; ?>
            </div>
            <div>
                <input type="text" id="searchTicket" class="toolbar-input form-control form-control-sm border-slate-200 shadow-none" placeholder="Search tickets..." style="width: 200px; font-size: 12px;">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover align-middle mb-0" style="border-collapse: collapse; min-width: 800px;">
                <thead style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Category</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Title</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Client</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Status/Priority</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Attachments</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 16px;">Created Time</th>
                        <?php if (has_permission('support_ticket_update')): ?>
<th class="border-0 text-slate-600 fw-bold text-center" style="font-size: 11px; padding: 12px 16px; width: 100px;">Actions</th>
<?php endif; ?>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid #e2e8f0;">
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach ($tickets as $ticket): 
                            $status_bg = 'bg-secondary';
                            if ($ticket['status'] == 'Pending') $status_bg = 'bg-secondary';
                            if ($ticket['status'] == 'In Progress') $status_bg = 'bg-primary';
                            if ($ticket['status'] == 'Review') $status_bg = 'bg-warning text-dark';
                            if ($ticket['status'] == 'Completed') $status_bg = 'bg-success';
                            if ($ticket['status'] == 'Cancelled') $status_bg = 'bg-danger';

                            $priority_color = 'text-success';
                            if ($ticket['priority'] == 'Medium') $priority_color = 'text-warning';
                            if ($ticket['priority'] == 'High') $priority_color = 'text-danger';
                            if ($ticket['priority'] == 'Urgent') $priority_color = 'text-danger fw-bold';
                        ?>
                        <tr>
                            <!-- Category -->
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <span class="badge bg-light text-slate-600 border border-slate-200 shadow-sm" style="font-size: 11px; border-radius: 6px;">
                                    <i class="bi bi-tag-fill text-slate-400 me-1"></i> <?= htmlspecialchars($ticket['category_name'] ?? 'Uncategorized') ?>
                                </span>
                            </td>

                            <!-- Title -->
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <div class="text-slate-800 fw-bold mb-1" style="font-size: 13px;">#<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($ticket['title']) ?></div>
                                <div class="text-slate-500 text-truncate" style="font-size: 11px; max-width: 200px;" title="<?= htmlspecialchars(strip_tags($ticket['description'])) ?>">
                                    <?= strip_tags($ticket['description']) ?>
                                </div>
                            </td>

                            <!-- Client -->
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <?php if (!empty($ticket['customer_name'])): ?>
                                <div class="text-primary fw-semibold" style="font-size: 12px;">
                                    <i class="bi bi-person-badge"></i> <?= htmlspecialchars($ticket['customer_username']) ?>-<?= htmlspecialchars($ticket['customer_name']) ?>
                                </div>
                                <?php else: ?>
                                <span class="text-slate-400" style="font-size: 11px;">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status / Priority -->
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <span class="badge <?= $status_bg ?> fw-semibold mb-1" style="font-size: 11px;">
                                    <?= htmlspecialchars($ticket['status']) ?>
                                </span><br>
                                <div class="<?= $priority_color ?> mt-1" style="font-size: 11px;">
                                    <i class="bi bi-flag-fill"></i> <?= htmlspecialchars($ticket['priority']) ?>
                                </div>
                            </td>

                            <!-- Attachments -->
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <?php 
                                $att_count = isset($attachments[$ticket['id']]) ? count($attachments[$ticket['id']]) : 0;
                                if ($att_count > 0): 
                                ?>
                                <button class="btn btn-sm btn-light border-slate-200 text-primary shadow-sm rounded-pill px-3 view-attachments-btn"
                                    data-attachments='<?= htmlspecialchars(json_encode($attachments[$ticket['id']]), ENT_QUOTES, 'UTF-8') ?>'
                                    style="font-size: 11px; font-weight: 500;">
                                    <i class="bi bi-paperclip"></i> <?= $att_count ?> File<?= $att_count > 1 ? 's' : '' ?>
                                </button>
                                <?php else: ?>
                                <span class="text-slate-400" style="font-size: 11px;">No files</span>
                                <?php endif; ?>
                            </td>

                            <!-- Created Time -->
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px; vertical-align: top;">
                                <div class="fw-semibold text-slate-700"><?= date('d M Y', strtotime($ticket['created_at'])) ?></div>
                                <div><?= date('h:i A', strtotime($ticket['created_at'])) ?></div>
                                <div class="text-primary mt-1" style="font-weight: 600; font-size: 10px; background: #e0e7ff; display: inline-block; padding: 2px 6px; border-radius: 4px;">
                                    <i class="bi bi-clock-history"></i> <?= get_time_duration($ticket['created_at'], $current_timestamp) ?>
                                </div>
                            </td>

                            <!-- Actions -->
                            <?php if (has_permission('support_ticket_update')): ?>
<td class="text-center" style="padding: 12px 16px; vertical-align: top;">
                                <?php if (has_permission('support_ticket_update')): ?>
                                <?php if ($ticket['status'] == 'Completed'): ?>
                                <button class="btn btn-sm btn-light border-slate-200 text-primary shadow-sm rounded px-2 update-ticket-btn w-100" 
                                    data-id="<?= $ticket['id'] ?>"
                                    data-title="<?= htmlspecialchars($ticket['title']) ?>"
                                    data-customer-username="<?= htmlspecialchars($ticket['customer_username'] ?? '') ?>"
                                    data-category="<?= htmlspecialchars($ticket['category_name'] ?? 'Uncategorized') ?>"
                                    data-status="<?= htmlspecialchars($ticket['status']) ?>"
                                    data-priority="<?= htmlspecialchars($ticket['priority']) ?>"
                                    data-is-completed="true"
                                    style="font-size: 11px;">
                                    <i class="bi bi-eye"></i> View Updates
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-light border-slate-200 text-primary shadow-sm rounded px-2 update-ticket-btn w-100" 
                                    data-id="<?= $ticket['id'] ?>"
                                    data-title="<?= htmlspecialchars($ticket['title']) ?>"
                                    data-customer-username="<?= htmlspecialchars($ticket['customer_username'] ?? '') ?>"
                                    data-category="<?= htmlspecialchars($ticket['category_name'] ?? 'Uncategorized') ?>"
                                    data-status="<?= htmlspecialchars($ticket['status']) ?>"
                                    data-priority="<?= htmlspecialchars($ticket['priority']) ?>"
                                    data-is-completed="false"
                                    style="font-size: 11px;">
                                    <i class="bi bi-pencil-square"></i> Update
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
<?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Container -->
        <div class="p-3 border-top border-slate-100 d-flex justify-content-between align-items-center bg-white custom-paging-container" id="custom-paging-container">
        </div>
    </div>
</div>

<!-- Add Ticket Modal -->
<div class="modal fade" id="addTicketModal" tabindex="-1" aria-labelledby="addTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 52px; height: 52px; backdrop-filter: blur(4px);">
                        <i class="bi bi-ticket-plus text-white fs-3"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="addTicketModalLabel" style="font-size: 20px;">New Support Ticket</h5>
                        <p class="text-white text-opacity-75 mb-0" style="font-size: 13px;">Create a new issue or request</p>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-white">
                <form id="addTicketForm">
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Ticket Title <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm rounded-3 shadow-sm border border-slate-200 overflow-hidden">
                                <span class="input-group-text bg-light border-0 text-slate-500 px-3"><i class="bi bi-fonts"></i></span>
                                <input type="text" name="title" class="form-control border-0 shadow-none py-2" required placeholder="E.g. Router not connecting" style="font-size: 14px;">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center bg-light p-2 rounded-3 border border-slate-100" style="min-height: 42px;">
                                <div class="form-check form-switch ms-2 mb-0">
                                    <input class="form-check-input" type="checkbox" id="isClientComplain" style="cursor: pointer;">
                                    <label class="form-check-label text-slate-700 fw-semibold ms-2" for="isClientComplain" style="font-size: 12px; cursor: pointer; margin-top: 2px;">Client Complaint?</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5" id="clientSelectWrapper" style="display: none;">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Select Client</label>
                            <select name="customer_id" id="clientSelect" class="form-select" data-placeholder="Search and select client...">
                                <option value=""></option>
                                <?php foreach ($customers as $cust): ?>
                                <option value="<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['username']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="categoryWrapper">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Category</label>
                            <div class="input-group input-group-sm rounded-3 shadow-sm border border-slate-200 overflow-hidden">
                                <span class="input-group-text bg-light border-0 text-slate-500 px-3"><i class="bi bi-tags"></i></span>
                                <select name="category_id" class="form-select border-0 shadow-none py-2" style="font-size: 14px;">
                                    <option value="">Select Category...</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Status</label>
                            <div class="input-group input-group-sm rounded-3 shadow-sm border border-slate-200 overflow-hidden">
                                <span class="input-group-text bg-light border-0 text-slate-500 px-3"><i class="bi bi-arrow-repeat"></i></span>
                                <select name="status" id="statusSelect" class="form-select border-0 shadow-none py-2" style="font-size: 14px;">
                                    <option value="Pending" data-color="#64748b" data-bg="#f1f5f9">Pending</option>
                                    <option value="In Progress" data-color="#0284c7" data-bg="#e0f2fe">In Progress</option>
                                    <option value="Review" data-color="#d97706" data-bg="#fef3c7">Review</option>
                                    <option value="Completed" data-color="#16a34a" data-bg="#dcfce7">Completed</option>
                                    <option value="Cancelled" data-color="#dc2626" data-bg="#fee2e2">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Priority</label>
                            <div class="input-group input-group-sm rounded-3 shadow-sm border border-slate-200 overflow-hidden">
                                <span class="input-group-text bg-light border-0 text-slate-500 px-3"><i class="bi bi-flag"></i></span>
                                <select name="priority" id="prioritySelect" class="form-select border-0 shadow-none py-2" style="font-size: 14px;">
                                    <option value="Low" data-color="#16a34a" data-bg="#dcfce7">Low</option>
                                    <option value="Medium" selected data-color="#d97706" data-bg="#fef3c7">Medium</option>
                                    <option value="High" data-color="#ea580c" data-bg="#ffedd5">High</option>
                                    <option value="Urgent" data-color="#dc2626" data-bg="#fee2e2">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Attachments</label>
                        <div class="input-group input-group-sm rounded-3 shadow-sm border border-slate-200 overflow-hidden">
                            <span class="input-group-text bg-light border-0 text-slate-500 px-3"><i class="bi bi-paperclip"></i></span>
                            <input type="file" name="attachments[]" id="ticketAttachments" class="form-control border-0 shadow-none py-2" style="font-size: 14px;" multiple>
                        </div>
                        <div class="form-text text-slate-500" style="font-size: 11px;"><i class="bi bi-info-circle me-1"></i>Max 10 MB per file.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Description <span class="text-danger">*</span></label>
                        <div class="shadow-sm rounded-3 overflow-hidden border border-slate-200">
                            <textarea name="description" id="ticketDescription" class="form-control border-0" rows="4" required placeholder="Describe the issue..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow py-3" style="border-radius: 10px; font-size: 15px; background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); border: none;" id="addTicketSubmitBtn">
                        <i class="bi bi-send-plus-fill me-2"></i> Submit Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Ticket Modal -->
<div class="modal fade" id="updateTicketModal" tabindex="-1" aria-labelledby="updateTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                <div class="d-flex align-items-center w-100 position-relative">
                    <div class="d-flex align-items-center" style="z-index: 2;">
                        <div class="bg-white bg-opacity-25 rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 52px; height: 52px; backdrop-filter: blur(4px);">
                            <i class="bi bi-journal-text text-white fs-3"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="updateTicketDisplayTitle" style="font-size: 18px;">Update Ticket</h5>
                            <p class="text-white text-opacity-75 mb-0" id="updateTicketDisplayCategory" style="font-size: 12px;">Category</p>
                        </div>
                    </div>
                    <div class="position-absolute top-50 start-50 translate-middle text-center w-100" style="pointer-events: none; z-index: 1;">
                        <span id="updateTicketCustomerUsername" class="badge bg-white text-primary fw-bold px-3 py-2 shadow-sm" style="font-size: 14px; display: none;"></span>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 2;"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light">
                <div class="p-4 custom-scrollbar" style="max-height: 350px; overflow-y: auto;" id="ticketRepliesContainer">
                    <div class="text-center text-slate-400 py-3"><div class="spinner-border spinner-border-sm"></div> Loading history...</div>
                </div>
                <div class="p-4 bg-white border-top border-slate-200" id="updateTicketFormArea">
                    <form id="updateTicketForm">
                        <input type="hidden" name="ticket_id" id="updateTicketId">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Change Status</label>
                                <select name="status" id="updateTicketStatus" class="form-select border-slate-300 shadow-sm" style="font-size: 14px;">
                                    <option value="Pending" data-color="#64748b" data-bg="#f1f5f9">Pending</option>
                                    <option value="In Progress" data-color="#0284c7" data-bg="#e0f2fe">In Progress</option>
                                    <option value="Review" data-color="#d97706" data-bg="#fef3c7">Review</option>
                                    <option value="Completed" data-color="#16a34a" data-bg="#dcfce7">Completed</option>
                                    <option value="Cancelled" data-color="#dc2626" data-bg="#fee2e2">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Change Priority</label>
                                <select name="priority" id="updateTicketPriority" class="form-select border-slate-300 shadow-sm" style="font-size: 14px;">
                                    <option value="Low" data-color="#16a34a" data-bg="#dcfce7">Low</option>
                                    <option value="Medium" data-color="#d97706" data-bg="#fef3c7">Medium</option>
                                    <option value="High" data-color="#ea580c" data-bg="#ffedd5">High</option>
                                    <option value="Urgent" data-color="#dc2626" data-bg="#fee2e2">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-slate-700 fw-semibold" style="font-size: 13px;">Add New Progress Note</label>
                            <div class="shadow-sm rounded-3 overflow-hidden border border-slate-200">
                                <textarea name="progress_note" id="updateTicketNote" class="form-control border-0" rows="3" placeholder="Describe the work done..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow py-3" style="border-radius: 10px; font-size: 15px; background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); border: none;" id="updateTicketSubmitBtn">
                            <i class="bi bi-check2-circle me-2"></i> Save Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-labelledby="manageCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 52px; height: 52px; backdrop-filter: blur(4px);">
                        <i class="bi bi-tags text-white fs-3"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="manageCategoriesModalLabel" style="font-size: 18px;">Ticket Categories</h5>
                        <p class="text-white text-opacity-75 mb-0" style="font-size: 12px;">Add or manage categories</p>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addCategoryForm" class="mb-4">
                    <div class="shadow-sm rounded-3 overflow-hidden d-flex bg-white border border-slate-200 p-1">
                        <span class="d-flex align-items-center px-3 text-slate-400">
                            <i class="bi bi-tag-fill"></i>
                        </span>
                        <input type="text" name="category_name" class="form-control border-0 shadow-none px-0" placeholder="New Category Name" required style="font-size: 14px;">
                        <button class="btn btn-primary fw-bold px-4" type="submit" id="addCategoryBtn" style="border-radius: 8px; font-size: 13px; background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); border: none;">
                            <i class="bi bi-plus-circle me-1"></i> Add
                        </button>
                    </div>
                </form>
                
                <h6 class="text-slate-700 fw-bold mb-3" style="font-size: 13px;">Existing Categories</h6>
                <div class="custom-scrollbar pe-2" style="max-height: 250px; overflow-y: auto;">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $cat): ?>
                            <div class="bg-white border border-slate-200 rounded-pill shadow-sm d-flex align-items-center py-2 px-3 transition-all hover-shadow" style="font-size: 13px; transition: all 0.2s;">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 24px; height: 24px;">
                                    <i class="bi bi-hash text-primary" style="font-size: 12px;"></i>
                                </div>
                                <span class="text-slate-700 fw-medium"><?= htmlspecialchars($cat['name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="w-100 text-slate-500 text-center py-4 bg-white rounded-3 border border-slate-200" style="font-size: 13px;">
                                <i class="bi bi-inbox fs-4 d-block mb-2 text-slate-300"></i> No categories found
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Attachments Modal -->
<div class="modal fade" id="viewAttachmentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 44px; height: 44px; backdrop-filter: blur(4px);">
                        <i class="bi bi-paperclip text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" style="font-size: 16px;">Attachments</h5>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(255,255,255,0.8); border-radius: 50%; padding: 10px;"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="attachmentsModalList" class="d-flex flex-column gap-2">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview File Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-header border-0 pb-2 justify-content-end">
                <button type="button" class="btn-close btn-close-white shadow-none bg-white opacity-100 rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0 position-relative">
                <img id="previewImage" src="" style="max-width: 100%; max-height: 85vh; border-radius: 8px; display: none; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                <iframe id="previewIframe" src="" style="width: 100%; height: 85vh; border: none; background: white; border-radius: 8px; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.5);"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Initialize Select2
    $('#clientSelect').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#addTicketModal'),
        width: '100%'
    });

    // Custom template for colorful dropdown options
    function formatStateColor(state) {
        if (!state.id) { return state.text; }
        var color = $(state.element).data('color');
        var bg = $(state.element).data('bg');
        return $(
            '<span class="badge fw-semibold rounded-pill px-2" style="background-color: ' + bg + '; color: ' + color + '; font-size: 13px;">' + state.text + '</span>'
        );
    }

    $('#statusSelect, #prioritySelect').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#addTicketModal'),
        width: '100%',
        minimumResultsForSearch: Infinity,
        templateResult: formatStateColor,
        templateSelection: formatStateColor
    });

    $('#updateTicketStatus, #updateTicketPriority').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#updateTicketModal'),
        width: '100%',
        minimumResultsForSearch: Infinity,
        templateResult: formatStateColor,
        templateSelection: formatStateColor
    });

    // Initialize Summernote
    $('#ticketDescription').summernote({
        placeholder: 'Describe the issue...',
        tabsize: 2,
        height: 150,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'italic', 'underline', 'clear']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['link']],
          ['view', ['fullscreen', 'codeview']]
        ]
    });
    
    $('#updateTicketNote').summernote({
        placeholder: 'Add a note about the progress...',
        tabsize: 2,
        height: 100,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'italic', 'underline', 'clear']],
          ['para', ['ul', 'ol']],
        ]
    });
    
    // Toggle Client Select
    $('#isClientComplain').on('change', function() {
        if ($(this).is(':checked')) {
            $('#clientSelectWrapper').slideDown(200);
            $('select[name="customer_id"]').prop('required', true);
        } else {
            $('#clientSelectWrapper').slideUp(200);
            $('select[name="customer_id"]').prop('required', false).val('');
        }
    });

    // Add Ticket
    $('#addTicketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#addTicketSubmitBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creating...');
        
        var formData = new FormData(this);

        $.ajax({
            url: '../ajax/add_ticket.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Create Ticket');
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Create Ticket');
            }
        });
    });

    // Populate Update Modal
    $('.update-ticket-btn').on('click', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        var customerUsername = $(this).data('customer-username');
        var category = $(this).data('category');
        var status = $(this).data('status');
        var priority = $(this).data('priority');
        var isCompleted = $(this).data('is-completed');
        
        if (customerUsername) {
            $('#updateTicketCustomerUsername').text('@' + customerUsername).show();
        } else {
            $('#updateTicketCustomerUsername').hide();
        }
        
        $('#updateTicketDisplayCategory').text(category);
        
        if (isCompleted) {
            $('#updateTicketDisplayTitle').text('#' + String(id).padStart(4, '0') + ' - ' + title + ' (Completed)');
            $('#updateTicketFormArea').hide();
        } else {
            $('#updateTicketDisplayTitle').text('#' + String(id).padStart(4, '0') + ' - ' + title);
            $('#updateTicketFormArea').show();
        }
        
        $('#updateTicketId').val(id);
        
        // Use trigger('change') so Select2 updates the UI correctly
        $('#updateTicketStatus').val(status).trigger('change');
        $('#updateTicketPriority').val(priority).trigger('change');
        
        // Clear old note in Summernote
        $('#updateTicketNote').summernote('code', '');
        
        $('#ticketRepliesContainer').html('<div class="text-center text-slate-400 py-3"><div class="spinner-border spinner-border-sm"></div> Loading history...</div>');
        
        var modal = new bootstrap.Modal(document.getElementById('updateTicketModal'));
        modal.show();

        // Fetch history
        $.ajax({
            url: '../ajax/get_ticket_replies.php',
            type: 'GET',
            data: { ticket_id: id },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#ticketRepliesContainer').html('<div class="text-center text-slate-400 py-4" style="font-size: 13px;"><i class="bi bi-chat-square-dots fs-3 d-block mb-2 text-slate-300"></i> No progress history yet.</div>');
                    } else {
                        var html = '<div class="timeline position-relative ms-4 ps-4 mt-2">';
                        res.data.forEach(function(reply, index) {
                            var isLast = index === res.data.length - 1;
                            html += `
                                <div class="position-relative mb-4">
                                    ${!isLast ? '<div class="position-absolute bg-primary" style="width: 2px; top: 28px; bottom: -24px; left: -25px; z-index: 0;"></div>' : ''}
                                    <div class="position-absolute bg-white border border-2 border-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 28px; height: 28px; left: -40px; top: 0; z-index: 1; box-shadow: 0 0 0 4px #f8fafc;">
                                        <i class="bi bi-chat-text-fill text-primary" style="font-size: 12px;"></i>
                                    </div>
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="fw-bold text-primary" style="font-size: 12px;"><i class="bi bi-person-circle me-1"></i> ${reply.created_by}</div>
                                                <div class="text-slate-400" style="font-size: 11px;">${reply.created_at}</div>
                                            </div>
                                            <div class="text-slate-700" style="font-size: 13px;">${reply.note}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#ticketRepliesContainer').html(html);
                        // Scroll to bottom of history
                        $('#ticketRepliesContainer').scrollTop($('#ticketRepliesContainer')[0].scrollHeight);
                    }
                } else {
                    $('#ticketRepliesContainer').html('<div class="text-center text-danger py-3">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#ticketRepliesContainer').html('<div class="text-center text-danger py-3">Failed to load history.</div>');
            }
        });
    });

    // Update Ticket
    $('#updateTicketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#updateTicketSubmitBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        $.ajax({
            url: '../ajax/update_ticket.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-2"></i> Save Update');
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i> Save Changes');
            }
        });
    });

    // Add Category
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#addCategoryBtn');
        btn.prop('disabled', true).text('...');
        
        $.ajax({
            url: '../ajax/add_ticket_category.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).text('Add');
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).text('Add');
            }
        });
    });

    // Handle Attachments Modal
    $('.view-attachments-btn').on('click', function() {
        var attachments = $(this).data('attachments');
        var html = '';
        
        if (attachments && attachments.length > 0) {
            attachments.forEach(function(att) {
                html += `
                <a href="${att.file_path}" class="card border border-slate-200 shadow-sm text-decoration-none transition-all hover-shadow view-file-btn" style="border-radius: 8px;">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex justify-content-center align-items-center me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="text-slate-700 fw-medium text-truncate" style="font-size: 12px; max-width: 150px;">
                            ${att.file_name}
                        </div>
                        <i class="bi bi-eye ms-auto text-primary" style="font-size: 12px;"></i>
                    </div>
                </a>
                `;
            });
        }
        
        $('#attachmentsModalList').html(html);
        var modal = new bootstrap.Modal(document.getElementById('viewAttachmentsModal'));
        modal.show();
    });

    // Handle File Preview
    $(document).on('click', '.view-file-btn', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var isImage = url.match(/\.(jpeg|jpg|gif|png|webp)$/i) != null;
        
        if (isImage) {
            $('#previewIframe').hide().attr('src', '');
            $('#previewImage').attr('src', url).show();
        } else {
            $('#previewImage').hide().attr('src', '');
            $('#previewIframe').attr('src', url).show();
        }
        
        // Hide the attachments list modal using the Bootstrap API
        var attachmentsModal = bootstrap.Modal.getInstance(document.getElementById('viewAttachmentsModal'));
        if (attachmentsModal) {
            attachmentsModal.hide();
        }
        
        // Show preview modal
        var previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    });

});
</script>
</body>
</html>
