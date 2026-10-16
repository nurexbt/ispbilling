import os

path = 'c:\\xampp\\htdocs\\billsoft\\billingportal\\customer.php'
with open(path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
start_idx = 0
for i, line in enumerate(lines):
    if line.startswith('                    <tr>'):
        start_idx = i
        break

top_part = """<?php
require_once '../includes/db.php';
$base_path = '../';
$active_menu = 'customer';
$page_title = 'Customers';
$page_subtitle = 'Manage registered clients';
include '../includes/header.php';

if (!has_permission('customer_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view customers.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Define filter
$expiry_filter = $_GET['filter'] ?? '';

// Base Query
$customers_query_base = "FROM customer_tbl c
                    LEFT JOIN area_tbl a ON c.area_id = a.id
                    LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
                    LEFT JOIN host_tbl h ON c.router_id = h.id
                    LEFT JOIN package_tbl p ON c.package_id = p.id";
                    
$customers_query = "SELECT c.*, a.area_name, s.subarea_name, h.device_name as router_name, p.given_name as package_name " . $customers_query_base;

// Filters
$where_clauses = [];
if ($expiry_filter === 'today') {
    $where_clauses[] = "c.billing_date = CURDATE()";
} else if ($expiry_filter === 'tomorrow') {
    $where_clauses[] = "c.billing_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
} else if ($expiry_filter === 'next7days') {
    $where_clauses[] = "c.billing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
}

if (!empty($where_clauses)) {
    $customers_query .= " WHERE " . implode(' AND ', $where_clauses);
}

$customers_query .= " ORDER BY c.id DESC";
$customers_result = $conn->query($customers_query);

// Get counts for cards
$today_query = "SELECT COUNT(id) as count FROM customer_tbl WHERE billing_date = CURDATE()";
$today_count = $conn->query($today_query)->fetch_assoc()['count'] ?? 0;

$tomorrow_query = "SELECT COUNT(id) as count FROM customer_tbl WHERE billing_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
$tomorrow_count = $conn->query($tomorrow_query)->fetch_assoc()['count'] ?? 0;

$next7_query = "SELECT COUNT(id) as count FROM customer_tbl WHERE billing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$next7_count = $conn->query($next7_query)->fetch_assoc()['count'] ?? 0;

// Fetch Areas for Edit form
$areas_list = [];
$ar_res = $conn->query("SELECT id, area_name FROM area_tbl");
if ($ar_res) { while($r = $ar_res->fetch_assoc()){ $areas_list[] = $r; } }

// Fetch Hosts for Edit form
$hosts_list = [];
$host_res = $conn->query("SELECT id, device_name, ip_address FROM host_tbl WHERE is_disabled = 0");
if ($host_res) { while($r = $host_res->fetch_assoc()){ $hosts_list[] = $r; } }
?>

<div class="container-fluid pt-2">
    <!-- Expiry Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <a href="customer.php?filter=today" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 <?= $expiry_filter === 'today' ? 'bg-primary text-white' : 'bg-white' ?>" style="transition: all 0.2s;">
                    <div class="card-body p-3 text-center">
                        <h6 class="mb-1 <?= $expiry_filter === 'today' ? 'text-white-50' : 'text-slate-500' ?>" style="font-size: 12px; text-transform: uppercase;">Today Expired</h6>
                        <h3 class="mb-0 fw-bold <?= $expiry_filter === 'today' ? 'text-white' : 'text-danger' ?>"><?= number_format($today_count) ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="customer.php?filter=tomorrow" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 <?= $expiry_filter === 'tomorrow' ? 'bg-primary text-white' : 'bg-white' ?>" style="transition: all 0.2s;">
                    <div class="card-body p-3 text-center">
                        <h6 class="mb-1 <?= $expiry_filter === 'tomorrow' ? 'text-white-50' : 'text-slate-500' ?>" style="font-size: 12px; text-transform: uppercase;">Tomorrow Expired</h6>
                        <h3 class="mb-0 fw-bold <?= $expiry_filter === 'tomorrow' ? 'text-white' : 'text-warning' ?>"><?= number_format($tomorrow_count) ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="customer.php?filter=next7days" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 <?= $expiry_filter === 'next7days' ? 'bg-primary text-white' : 'bg-white' ?>" style="transition: all 0.2s;">
                    <div class="card-body p-3 text-center">
                        <h6 class="mb-1 <?= $expiry_filter === 'next7days' ? 'text-white-50' : 'text-slate-500' ?>" style="font-size: 12px; text-transform: uppercase;">Next 7 Days Expired</h6>
                        <h3 class="mb-0 fw-bold <?= $expiry_filter === 'next7days' ? 'text-white' : 'text-info' ?>"><?= number_format($next7_count) ?></h3>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <?php if ($expiry_filter !== ''): ?>
    <div class="mb-3">
        <a href="customer.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear Filter</a>
    </div>
    <?php endif; ?>

    <!-- Customers Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchCustomer" class="toolbar-input" placeholder="Search customers...">
            </div>
            <div class="d-flex gap-2">
                <!-- <a href="../ajax/export_customers.php" class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" style="font-size: 12px; font-weight: 500; color: #475569;"><i class="bi bi-download"></i> Export CSV</a>
                <?php if (has_permission('customer_add')): ?>
                <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importClientModal" style="font-size: 12px; font-weight: 500; color: #475569;"><i class="bi bi-upload"></i> Import CSV</button> -->
                <?php endif; ?>
            </div>
        </div>

        <div class="p-0">
            <table id="customerTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #F8FAFC;">
"""

with open(path, 'w', encoding='utf-8') as f:
    f.write(top_part + "".join(lines[start_idx:]))
