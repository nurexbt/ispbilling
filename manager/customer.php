<?php
require_once '../includes/db.php';
$base_path = '../';
$is_manager = true;
$active_menu = 'customer';
$page_title = 'Customers';
$page_subtitle = 'Manage registered clients';
include '../includes/header.php';


include 'includes/sidebar.php';

// Fetch Customers
$customers_query = "SELECT c.*, a.area_name, s.subarea_name, h.device_name as router_name, p.given_name as package_name 
                    FROM customer_tbl c
                    LEFT JOIN area_tbl a ON c.area_id = a.id
                    LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
                    LEFT JOIN host_tbl h ON c.router_id = h.id
                    LEFT JOIN package_tbl p ON c.package_id = p.id
                    ORDER BY c.id DESC";
$customers_result = $conn->query($customers_query);

// Fetch Areas for Edit form
$areas_list = [];
$ar_res = $conn->query("SELECT id, area_name FROM area_tbl");
if ($ar_res) { while($r = $ar_res->fetch_assoc()){ $areas_list[] = $r; } }

// Fetch Hosts for Edit form
$hosts_list = [];
$host_res = $conn->query("SELECT id, device_name, ip_address FROM host_tbl WHERE is_disabled = 0");
if ($host_res) { while($r = $host_res->fetch_assoc()){ $hosts_list[] = $r; } }

// Fetch Customers Summary Stats
$stats_query = "SELECT 
                    COUNT(*) as total_customers, 
                    SUM(CASE WHEN (advance_payment - previous_due) >= 0 THEN 1 ELSE 0 END) as paid_customers, 
                    SUM(CASE WHEN (advance_payment - previous_due) < 0 THEN 1 ELSE 0 END) as unpaid_customers, 
                    SUM(CASE WHEN pppoe_status = 'disable' THEN 1 ELSE 0 END) as disabled_customers 
                FROM customer_tbl";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];
$total_clients = intval($stats['total_customers'] ?? 0);
$paid_clients = intval($stats['paid_customers'] ?? 0);
$unpaid_clients = intval($stats['unpaid_customers'] ?? 0);
$disabled_clients = intval($stats['disabled_customers'] ?? 0);
?>

<div class="container-fluid pt-2">
    <!-- Small Information Cards -->
    <div class="row g-3 mb-2">
        <!-- Total Client -->
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm customer-stat-card" data-filter="" title="Click to view all clients" style="border-left: 4px solid #2563eb !important; border-radius: 12px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); cursor: pointer;">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="bi bi-people-fill fs-5" style="color: #2563eb;"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-slate-500 mb-1 text-truncate" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Client</div>
                    <div class="fw-bold text-truncate" style="font-size: 22px; color: #1e40af; line-height: 1;"><?= number_format($total_clients) ?></div>
                </div>
            </div>
        </div>

        <!-- Paid Client -->
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm customer-stat-card" data-filter="status-paid" title="Click to filter Paid clients" style="border-left: 4px solid #16a34a !important; border-radius: 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); cursor: pointer;">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="bi bi-check-circle-fill fs-5" style="color: #16a34a;"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-slate-500 mb-1 text-truncate" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Paid Client</div>
                    <div class="fw-bold text-truncate" style="font-size: 22px; color: #15803d; line-height: 1;"><?= number_format($paid_clients) ?></div>
                </div>
            </div>
        </div>

        <!-- Unpaid Client -->
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm customer-stat-card" data-filter="status-unpaid" title="Click to filter Unpaid clients" style="border-left: 4px solid #dc2626 !important; border-radius: 12px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); cursor: pointer;">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="bi bi-exclamation-circle-fill fs-5" style="color: #dc2626;"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-slate-500 mb-1 text-truncate" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Unpaid Client</div>
                    <div class="fw-bold text-truncate" style="font-size: 22px; color: #b91c1c; line-height: 1;"><?= number_format($unpaid_clients) ?></div>
                </div>
            </div>
        </div>

        <!-- Disabled Customer (pppoe deactivated) -->
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 d-flex align-items-center border-0 shadow-sm customer-stat-card" data-filter="pppoe-disabled" title="Click to filter Disabled (PPPoE Deactivated) clients" style="border-left: 4px solid #d97706 !important; border-radius: 12px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); cursor: pointer;">
                <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="bi bi-person-x-fill fs-5" style="color: #d97706;"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-slate-500 mb-1 text-truncate" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Disabled Customer</div>
                    <div class="d-flex align-items-baseline gap-1.5 flex-wrap">
                        <span class="fw-bold text-truncate" style="font-size: 22px; color: #b45309; line-height: 1;"><?= number_format($disabled_clients) ?></span>
                        <span style="font-size: 10px; color: #92400e; font-weight: 500;">(PPPoE Deactivated)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchCustomer" class="toolbar-input" placeholder="Search customers...">
            </div>
            <div class="d-flex gap-2">
                <a href="../ajax/export_customers.php" class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" style="font-size: 12px; font-weight: 500; color: #475569;"><i class="bi bi-download"></i> Export CSV</a>
                <?php if (has_permission('customer_add')): ?>
                <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importClientModal" style="font-size: 12px; font-weight: 500; color: #475569;"><i class="bi bi-upload"></i> Import CSV</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" style="font-size: 12px; font-weight: 500; color: #475569;">
                    <i class="bi bi-funnel"></i> Filter
                </button>

            </div>
        </div>

        <div class="p-0">
            <table id="customerTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Client Details</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Contact</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Location</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Service details</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Dates</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Balance</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status</th>
                        <?php if (has_permission('customer_edit') || has_permission('customer_delete') || has_permission('customer_ledger')): ?>
<th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 12px 16px; width: 70px;">Action</th>
<?php endif; ?>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $customers_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900" style="font-size: 13px; font-weight: 500;">
                                    <?= htmlspecialchars($row['name']) ?>
                                    <?php if (!empty($row['is_special'])): ?>
                                    <i class="bi bi-star-fill text-warning ms-1" title="Special User" style="font-size: 11px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="text-slate-500 mt-1" style="font-size: 11px;"><span class="fw-semibold">User:</span> <?= htmlspecialchars($row['username']) ?>, <span class="fw-semibold">Pass:</span> <?= htmlspecialchars($row['password']) ?></div>
                                <?php if (!empty($row['onu_sn'])): ?>
                                <div class="text-slate-500 mt-1" style="font-size: 10px;"><span class="fw-semibold">ONU:</span> <?= htmlspecialchars($row['onu_sn']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-700" style="font-size: 12px;"><i class="bi bi-telephone text-slate-400 me-1"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                <?php if (!empty($row['email'])): ?>
                                <div class="text-slate-500" style="font-size: 11px;"><i class="bi bi-envelope text-slate-400 me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['nid_no'])): ?>
                                <div class="text-slate-500 mt-1" style="font-size: 11px;"><i class="bi bi-person-badge text-slate-400 me-1"></i> NID: <?= htmlspecialchars($row['nid_no']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-700" style="font-size: 12px; font-weight: 500;">
                                    <?= htmlspecialchars($row['area_name']) ?> 
                                    <?= !empty($row['subarea_name']) ? ' <i class="bi bi-chevron-right text-slate-300" style="font-size:10px;"></i> ' . htmlspecialchars($row['subarea_name']) : '' ?>
                                </div>
                                <div class="text-slate-500 mt-1" style="font-size: 11px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['address']) ?>"><?= htmlspecialchars($row['address']) ?></div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-700" style="font-size: 12px;"><span class="fw-semibold">Router:</span> <?= htmlspecialchars($row['router_name'] ?? 'N/A') ?></div>
                                <div class="text-slate-500" style="font-size: 11px;"><span class="fw-semibold">Package:</span> <?= htmlspecialchars($row['package_name'] ?? 'N/A') ?> - ৳<?= htmlspecialchars($row['package_price']) ?></div>
                                <?php if ($row['discount'] > 0): ?>
                                <div class="text-success mt-1" style="font-size: 10px; font-weight: 500;"><i class="bi bi-tag-fill me-1"></i>Discount: ৳<?= htmlspecialchars($row['discount']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-700" style="font-size: 11px;"><span class="fw-semibold">Conn:</span> <?= htmlspecialchars($row['connection_date']) ?></div>
                                <div class="text-slate-700 mt-1" style="font-size: 11px;"><span class="fw-semibold">Bill:</span> <?= htmlspecialchars($row['billing_date']) ?></div>
                                <div class="text-slate-700 mt-1" style="font-size: 11px;"><span class="fw-semibold">Cycle:</span> <?= htmlspecialchars($row['payment_cycle_date']) ?></div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php 
                                    $balance = $row['advance_payment'] - $row['previous_due'];
                                    $balanceClass = 'text-slate-500';
                                    if ($balance > 0) $balanceClass = 'text-success fw-bold';
                                    if ($balance < 0) $balanceClass = 'text-danger fw-bold';
                                ?>
                                <div class="<?= $balanceClass ?>" style="font-size: 12px;">
                                    <?= $balance >= 0 ? '+' : '' ?>৳<?= number_format($balance, 2) ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php 
                                    $isPaid = $balance >= 0;
                                    $statusClass = $isPaid ? 'bg-success' : 'bg-danger';
                                    $statusText = $isPaid ? 'Paid' : 'Unpaid';
                                    $pppoeDisabled = ($row['pppoe_status'] ?? '') === 'disable';
                                ?>
                                <span class="d-none">status-<?= strtolower($statusText) ?> <?= $pppoeDisabled ? 'pppoe-disabled' : 'pppoe-enabled' ?></span>
                                <div class="badge <?= $statusClass ?> text-white px-2 py-1" style="font-size: 11px; border-radius: 4px; font-weight: 500;">
                                    <?= $statusText ?>
                                </div>
                                <div class="router-status-badge mt-1" data-username="<?= htmlspecialchars($row['username']) ?>">
                                    <span class="badge bg-light text-slate-400 border border-slate-200" style="font-size: 9px;"><span class="spinner-border spinner-border-sm" style="width: 8px; height: 8px; border-width: 1px;"></span> Router</span>
                                </div>
                            </td>
                            <?php if (has_permission('customer_edit') || has_permission('customer_delete') || has_permission('customer_ledger') || has_permission('support_ticket_view')): ?>
                            <td class="text-center" style="padding: 12px 16px;">
                                <div class="dropdown d-inline-block">
                                    <button type="button" 
                                            class="btn btn-sm customer-action-btn shadow-sm" 
                                            data-bs-toggle="dropdown" 
                                            data-bs-display="static"
                                            aria-expanded="false" 
                                            title="Customer Actions">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end customer-action-dropdown shadow-lg border-0">
                                        <?php if (has_permission('customer_ledger')): ?>
                                        <li>
                                            <a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 view-ledger" data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-wallet2 text-primary" style="width: 18px; font-size: 14px; text-align: center;"></i>
                                                <span>Customer Ledger</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (has_permission('customer_edit')): ?>
                                        <li>
                                            <a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 edit-customer" data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-pencil-square text-success" style="width: 18px; font-size: 14px; text-align: center;"></i>
                                                <span>Edit Customer</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 view-tickets" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>" data-username="<?= htmlspecialchars($row['username']) ?>">
                                                <i class="bi bi-ticket-detailed text-warning" style="width: 18px; font-size: 14px; text-align: center;"></i>
                                                <span>Ticket</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Client Modal -->
<div class="modal fade" id="importClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <div class="modal-header border-bottom border-slate-100 bg-light py-3 px-4" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title text-slate-800" style="font-size: 15px; font-weight: 600;"><i class="bi bi-upload text-primary me-2"></i>Import Customers from CSV</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
      </div>
        <form id="importClientForm" enctype="multipart/form-data">
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Select CSV File <span class="text-danger">*</span></label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control form-control-sm border-slate-200 shadow-none" accept=".csv" required>
                      <div class="form-text mt-2 d-flex justify-content-between align-items-center" style="font-size: 11px;">
                          <span>Make sure the CSV matches the export format exactly.</span>
                          <a href="../ajax/download_sample_csv.php" class="text-decoration-none text-primary fw-medium"><i class="bi bi-download me-1"></i>Download Sample CSV</a>
                      </div>
                </div>
                <div class="mb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="add_to_mikrotik" id="add_to_mikrotik" value="1">
                        <label class="form-check-label text-slate-700" for="add_to_mikrotik" style="font-size: 13px; font-weight: 500;">
                            Add these users on Mikrotik router also?
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
                <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-search"></i> Preview Import</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Import Preview Modal -->
<div class="modal fade" id="previewImportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <div class="modal-header border-bottom border-slate-100 bg-light py-3 px-4" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title text-slate-800" style="font-size: 15px; font-weight: 600;"><i class="bi bi-eye text-primary me-2"></i>Import Preview</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body p-0">
          <div class="p-3 border-bottom border-slate-100">
              <h6 class="text-success mb-2" style="font-size: 13px; font-weight: 600;">Ready to Import (<span id="readyCount">0</span>)</h6>
              <div style="max-height: 150px; overflow-y: auto;" id="readyList" class="small text-slate-600"></div>
          </div>
          <div class="p-3">
              <h6 class="text-danger mb-2" style="font-size: 13px; font-weight: 600;">Skipped / Duplicates (<span id="skippedCount">0</span>)</h6>
              <div style="max-height: 150px; overflow-y: auto;" id="skippedList" class="small text-slate-600"></div>
          </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
          <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#importClientModal" style="font-size: 12px; font-weight: 500; color: #475569;">Back</button>
          <button type="button" id="confirmImportBtn" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Confirm Import</button>
      </div>
    </div>
  </div>
</div>

<style>
#ledgerModal .dataTables_filter {
    display: none !important;
}
/* Promise Date Pill - same as admin */
.promise-date-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fbbf24;
    border-radius: 50px;
    padding: 5px 14px 5px 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(251,191,36,0.18);
    min-width: 170px;
}
.promise-date-pill:hover { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); box-shadow: 0 3px 10px rgba(251,191,36,0.32); transform: translateY(-1px); }
.promise-date-pill .pd-icon { background: #f59e0b; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.promise-date-pill .pd-label { font-size: 10px; color: #92400e; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1; }
.promise-date-pill .pd-value { font-size: 12px; color: #78350f; font-weight: 600; line-height: 1.2; }
.promise-date-pill .pd-clear { margin-left: 4px; color: #d97706; background: none; border: none; padding: 0; line-height: 1; font-size: 14px; cursor: pointer; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.promise-date-pill .pd-clear:hover { background: rgba(251,191,36,0.35); color: #b45309; }
.promise-date-pill.has-date { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-color: #34d399; box-shadow: 0 2px 8px rgba(52,211,153,0.22); }
.promise-date-pill.has-date:hover { background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%); box-shadow: 0 4px 12px rgba(52,211,153,0.35); }
.promise-date-pill.has-date .pd-icon { background: #10b981; }
.promise-date-pill.has-date .pd-label { color: #065f46; }
.promise-date-pill.has-date .pd-value { color: #064e3b; }
.promise-date-pill.has-date .pd-clear { color: #059669; }
.flatpickr-calendar { border-radius: 16px !important; box-shadow: 0 20px 40px rgba(0,0,0,0.18) !important; border: none !important; font-family: 'Inter', sans-serif !important; overflow: hidden; }
.flatpickr-months { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; border-radius: 16px 16px 0 0; padding: 6px 0; }
.flatpickr-month, .flatpickr-current-month, .flatpickr-next-month, .flatpickr-prev-month { color: #fff !important; fill: #fff !important; }
.flatpickr-current-month input.cur-year { color: #fff !important; font-weight: 700 !important; }
.flatpickr-current-month .flatpickr-monthDropdown-months { color: #fff !important; font-weight: 700 !important; background: transparent !important; }
.flatpickr-weekdays { background: #fef3c7 !important; }
.flatpickr-weekday { color: #92400e !important; font-weight: 700 !important; font-size: 11px; }
.flatpickr-day.today { border-color: #f59e0b !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; border-color: #d97706 !important; font-weight: 700 !important; border-radius: 10px !important; }
.flatpickr-day:hover { background: #fef3c7 !important; border-color: #fbbf24 !important; border-radius: 10px !important; }
.flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover { color: #d1d5db !important; background: transparent !important; }
.flatpickr-day { border-radius: 10px !important; font-size: 13px !important; font-weight: 500; }
/* Stylish Iconic Action Button & Dropdown Modal System */
.customer-action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    color: #475569;
    font-size: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.customer-action-btn:hover,
.customer-action-btn:focus,
.customer-action-btn[aria-expanded="true"] {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #2563eb;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15) !important;
}
.customer-action-btn:active {
    transform: scale(0.95);
}

.customer-action-dropdown {
    min-width: 170px !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
    overflow: hidden;
    z-index: 1060 !important;
    padding: 6px !important;
    margin-top: 6px !important;
    position: absolute !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
    animation: dropdownFadeIn 0.12s ease-out;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.customer-action-dropdown .dropdown-item {
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.15s ease;
}
.customer-action-dropdown .dropdown-item:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.customer-action-dropdown .dropdown-item:active {
    background-color: #e2e8f0;
}

/* Small Information Card Styles */
.customer-stat-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
}
.customer-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04) !important;
}
.customer-stat-card.active-filter {
    box-shadow: 0 0 0 2px #0f172a, 0 10px 15px -3px rgba(0, 0, 0, 0.12) !important;
    transform: translateY(-2px);
}
</style>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Customer
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editCustomerForm">
            <input type="hidden" name="customer_id" id="edit_customer_id">
            
            <h6 class="mb-3 text-slate-700" style="font-size: 14px; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Applicant Details</h6>
            
            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Email Address</label>
                    <input type="email" name="email" id="edit_email" class="form-control form-control-sm border-slate-200 shadow-none">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">ONU MAC/SN</label>
                    <input type="text" name="onu_sn" id="edit_onu" class="form-control form-control-sm border-slate-200 shadow-none">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Area</label>
                    <select name="area_id" id="edit_area" class="form-select form-select-sm border-slate-200 shadow-none" style="font-size: 13px;" required>
                        <option value="">Select an Area...</option>
                        <?php foreach($areas_list as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Sub Area</label>
                    <select name="subarea_id" id="edit_subarea" class="form-select form-select-sm border-slate-200 shadow-none" style="font-size: 13px;">
                        <option value="">Select Sub Area...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Address</label>
                    <input type="text" name="address" id="edit_address" class="form-control form-control-sm border-slate-200 shadow-none">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">NID No <span class="text-danger">*</span></label>
                    <input type="text" name="nid_no" id="edit_nid" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
            </div>

            <h6 class="mb-3 mt-4 text-slate-700" style="font-size: 14px; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Service Details</h6>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Router</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-router"></i></span>
                        <select name="router_id" id="edit_router" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                            <option value="">Select Router...</option>
                            <?php foreach($hosts_list as $host): ?>
                                <option value="<?= $host['id'] ?>"><?= htmlspecialchars($host['device_name']) ?> (<?= htmlspecialchars($host['ip_address']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Package</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-info px-3"><i class="bi bi-box"></i></span>
                        <select name="package_id" id="edit_package" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                            <option value="">Select Package...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Username (PPPoE)</label>
                    <input type="text" name="username" id="edit_username" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Password</label>
                    <input type="text" name="password" id="edit_password" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Discount (৳)</label>
                    <input type="number" step="0.01" name="discount" id="edit_discount" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Final Price</label>
                    <input type="number" step="0.01" name="package_price" id="edit_package_price" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                    <input type="hidden" id="edit_original_price">
                </div>
            </div>

            <div class="row mb-3">
                  <div class="col-md-4 mb-2 mb-md-0">
                      <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Connection Date</label>
                      <input type="date" name="connection_date" id="edit_connection_date" class="form-control form-control-sm border-slate-200 shadow-none" required>
                  </div>
                  <div class="col-md-4 mb-2 mb-md-0">
                      <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Billing Date</label>
                      <input type="date" name="billing_date" id="edit_billing_date" class="form-control form-control-sm border-slate-200 shadow-none" required>
                  </div>
                  <div class="col-md-4">
                      <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Payment Cycle Date</label>
                      <input type="date" name="payment_cycle_date" id="edit_payment_cycle_date" class="form-control form-control-sm border-slate-200 shadow-none" required>
                  </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Note / Remark</label>
                <textarea name="client_note" id="edit_client_note" class="form-control border-slate-200 shadow-none" rows="2" placeholder="Any additional information..." style="border-radius: 10px; font-size: 13px; padding: 10px;"></textarea>
            </div>

            <div class="form-check form-switch mt-3 mb-2">
                <input class="form-check-input shadow-none" type="checkbox" role="switch" name="is_special" id="edit_is_special" value="1">
                <label class="form-check-label text-slate-600 fw-semibold" for="edit_is_special" style="font-size: 13px;">Special User ?</label>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" id="saveEditCustomerBtn" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-save"></i> Save Changes</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Ledger Modal -->
<div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
      <div class="modal-header border-bottom border-slate-100 bg-light py-3 px-4 d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title text-slate-800 m-0" style="font-size: 15px; font-weight: 600;"><i class="bi bi-wallet2 text-info me-2"></i>Payment Ledger: <span id="ledgerClientName" class="text-primary"></span></h5>
        <div class="d-flex align-items-center gap-3">
            <div id="promiseDatePill" class="promise-date-pill" title="Click to set promise date">
                <span class="pd-icon"><i class="bi bi-calendar-event"></i></span>
                <span class="d-flex flex-column">
                    <span class="pd-label">Promise Date</span>
                    <span class="pd-value" id="pdValueDisplay">Click to set</span>
                </span>
                <button type="button" class="pd-clear" id="ledgerPromiseDateClearBtn" style="display:none;" title="Clear">&times;</button>
            </div>
            <input type="text" id="ledgerPromiseDateInput" style="display:none;">
            <div class="form-check form-switch m-0" style="font-size: 13px;">
                <input class="form-check-input" type="checkbox" role="switch" id="ledgerMikrotikSwitch">
                <label class="form-check-label text-slate-600 fw-semibold" for="ledgerMikrotikSwitch">Mikrotik Status</label>
            </div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
        </div>
      </div>
      <div class="modal-body p-3 bg-slate-50">
          
          <!-- Customer Information Section -->
          <div class="card border-0 shadow-sm rounded-3 mb-3" style="background: linear-gradient(145deg, #ffffff, #f8fafc);">
              <div class="card-body p-3">
                  <h6 class="text-slate-700 mb-3" style="font-size: 13px; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;"><i class="bi bi-person-badge text-primary me-2"></i>Customer Information</h6>
                  <div class="row text-slate-600" style="font-size: 12px; line-height: 1.8;">
                      <!-- Column 1 -->
                      <div class="col-md-4 mb-2 mb-md-0">
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-person-fill text-info me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">Name:</span> <span id="ledgerCustName" class="fw-semibold text-slate-800"></span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-telephone-fill text-success me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">Phone:</span> <span id="ledgerCustPhone" class="fw-semibold"></span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-person-vcard-fill text-warning me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">NID:</span> <span id="ledgerCustNid" class="fw-semibold"></span>
                          </div>
                          <div class="d-flex align-items-start">
                              <i class="bi bi-geo-alt-fill text-danger me-2 mt-1" style="font-size: 14px; width: 16px;"></i>
                              <div>
                                  <span class="text-slate-500 me-1">Address:</span> 
                                  <span id="ledgerCustAddress" class="fw-semibold" style="line-height: 1.3;" title="Address"></span>
                              </div>
                          </div>
                      </div>
                      
                      <!-- Column 2 -->
                      <div class="col-md-4 mb-2 mb-md-0">
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-globe2 text-primary me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">PPPoE User:</span> <span id="ledgerCustUsername" class="fw-semibold text-primary font-monospace"></span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-key-fill text-secondary me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">PPPoE Pass:</span> <span id="ledgerCustPassword" class="fw-semibold font-monospace text-slate-700"></span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-router-fill text-dark me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">ONU SN:</span> <span id="ledgerCustOnu" class="fw-semibold"></span>
                          </div>
                          <div class="d-flex align-items-center">
                              <i class="bi bi-box-seam-fill text-purple me-2" style="font-size: 14px; width: 16px; color: #8b5cf6;"></i>
                              <span class="text-slate-500 me-1">Package:</span> <span id="ledgerCustPackage" class="fw-semibold" style="color: #8b5cf6;"></span>
                          </div>
                      </div>
                      
                      <!-- Column 3 -->
                      <div class="col-md-4">
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-currency-dollar text-success me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">Price:</span> <span class="fw-bold text-success">৳<span id="ledgerCustPrice"></span></span> 
                              <span class="text-danger ms-1" style="font-size: 11px; font-weight: 500;">(Disc: ৳<span id="ledgerCustDiscount"></span>)</span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-plug-fill text-info me-2" style="font-size: 14px; width: 16px;"></i>
                              <span class="text-slate-500 me-1">Conn Date:</span> <span id="ledgerCustConnDate" class="fw-semibold"></span>
                          </div>
                          <div class="d-flex align-items-center mb-1">
                              <i class="bi bi-calendar-event-fill text-orange me-2" style="font-size: 14px; width: 16px; color: #f97316;"></i>
                              <span class="text-slate-500 me-1">Bill Date:</span> <span id="ledgerCustBillDate" class="fw-semibold"></span>
                          </div>
                          <div class="d-flex align-items-center">
                              <i class="bi bi-arrow-repeat text-teal me-2" style="font-size: 14px; width: 16px; color: #14b8a6;"></i>
                              <span class="text-slate-500 me-1">Cycle Date:</span> <span id="ledgerCustCycleDate" class="fw-semibold"></span>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div class="row mb-3">
              <div class="col-md-4">
                  <div class="card border-0 shadow-sm rounded-3">
                      <div class="card-body p-3 text-center">
                          <h6 class="text-slate-500 mb-1" style="font-size: 11px; text-transform: uppercase;">Total Due</h6>
                          <h4 class="mb-0 text-danger fw-bold" id="ledgerTotalBilled" style="font-size: 20px;">৳0.00</h4>
                      </div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card border-0 shadow-sm rounded-3">
                      <div class="card-body p-3 text-center">
                          <h6 class="text-slate-500 mb-1" style="font-size: 11px; text-transform: uppercase;">Total Paid</h6>
                          <h4 class="mb-0 text-success fw-bold" id="ledgerTotalPaid" style="font-size: 20px;">৳0.00</h4>
                      </div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card border-0 shadow-sm rounded-3">
                      <div class="card-body p-3 text-center">
                          <h6 class="text-slate-500 mb-1" style="font-size: 11px; text-transform: uppercase;">Current Balance</h6>
                          <h4 class="mb-0 fw-bold" id="ledgerBalance" style="font-size: 20px;">৳0.00</h4>
                      </div>
                  </div>
              </div>
          </div>

          <div class="card border-0 shadow-sm rounded-3 mb-3">
              <div class="card-header bg-white border-bottom border-slate-100 py-3">
                  <h6 class="mb-0 text-slate-700" style="font-size: 13px; font-weight: 600;">Add New Payment</h6>
              </div>
              <div class="card-body p-3">
                  <form id="addPaymentForm">
                      <input type="hidden" name="customer_id" id="ledgerCustomerId">
                      <div class="row align-items-center g-3 mb-3">
                          <div class="col-md-3">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px;">Payment For</label>
                              <select name="invoice_id" id="addPaymentMonth" class="form-select form-select-sm shadow-none">
                                  <option value="">Advance Payment</option>
                              </select>
                              <input type="hidden" name="for_month" id="paymentForMonthStr">
                          </div>
                          <div class="col-md-3">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px;">Discount (৳)</label>
                              <input type="number" step="0.01" class="form-control form-control-sm border-slate-200 shadow-none" name="discount" id="addPaymentDiscount">
                          </div>
                          <div class="col-md-3">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px;">Amount (৳)</label>
                              <input type="number" step="0.01" class="form-control form-control-sm border-slate-200 shadow-none" name="amount" id="addPaymentAmount" required>
                          </div>
                          <div class="col-md-3">
                              <div class="form-check mt-3 pt-2">
                                  <input class="form-check-input shadow-none" type="checkbox" name="pay_from_balance" id="payFromBalance" value="1" disabled>
                                  <label class="form-check-label text-slate-600" for="payFromBalance" style="font-size: 11px; font-weight: 500;">
                                      Pay from balance
                                  </label>
                              </div>
                          </div>
                      </div>
                      <div class="row align-items-end g-3">
                          <div class="col-md-4">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px;">Payment Date</label>
                              <input type="date" name="payment_date" class="form-control form-control-sm shadow-none" value="<?= date('Y-m-d') ?>" required>
                          </div>
                          <div class="col-md-4">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px;">Method</label>
                              <select name="payment_method" class="form-select form-select-sm shadow-none">
                                  <option value="Cash">Cash</option>
                                  <option value="Bkash">Bkash</option>
                                  <option value="Bank Transfer">Bank Transfer</option>
                                  <option value="Other">Other</option>
                              </select>
                          </div>
                          <div class="col-md-4">
                              <button type="submit" class="btn btn-sm btn-primary w-100 shadow-sm" style="font-size: 12px; height: 31px;"><i class="bi bi-plus-circle me-1"></i>Add Payment</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>

          <div class="row">
              <!-- Invoices Table (Left) -->
              <div class="col-md-6">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="text-slate-700 mb-0" style="font-size: 13px; font-weight: 600;">Generated Bills</h6>
                      <input type="text" id="searchInvoices" class="form-control form-control-sm w-50 shadow-none" placeholder="Search bills..." style="font-size: 11px;">
                  </div>
                  <div class="table-responsive bg-white rounded-3 shadow-sm border border-slate-200">
                      <table id="ledgerInvoicesTable" class="table table-hover align-middle mb-0" style="font-size: 12px; width:100%;">
                          <thead class="bg-slate-50">
                              <tr>
                                  <th class="border-bottom-0 text-slate-500 py-2">Date</th>
                                  <th class="border-bottom-0 text-slate-500 py-2">Month</th>
                                  <th class="border-bottom-0 text-slate-500 py-2">Amount</th>
                                  <th class="border-bottom-0 text-slate-500 py-2 text-end">Action</th>
                              </tr>
                          </thead>
                          <tbody id="ledgerInvoicesBody">
                              <!-- Rendered via JS -->
                          </tbody>
                      </table>
                  </div>
              </div>
              
              <!-- Payments Table (Right) -->
              <div class="col-md-6">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="text-slate-700 mb-0" style="font-size: 13px; font-weight: 600;">Payments Record</h6>
                      <input type="text" id="searchPayments" class="form-control form-control-sm w-50 shadow-none" placeholder="Search payments..." style="font-size: 11px;">
                  </div>
                  <div class="table-responsive bg-white rounded-3 shadow-sm border border-slate-200">
                      <table id="ledgerPaymentsTable" class="table table-hover align-middle mb-0" style="font-size: 12px; width:100%;">
                          <thead class="bg-slate-50">
                              <tr>
                                  <th class="border-bottom-0 text-slate-500 py-2">Date</th>
                                  <th class="border-bottom-0 text-slate-500 py-2">Method</th>
                                  <th class="border-bottom-0 text-slate-500 py-2">Amount</th>
                                  <th class="border-bottom-0 text-slate-500 py-2 text-end">Action</th>
                              </tr>
                          </thead>
                          <tbody id="ledgerPaymentsBody">
                              <!-- Rendered via JS -->
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<!-- Refund Payment Modal -->
<div class="modal fade" id="refundPaymentModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4 bg-light">
        <h5 class="modal-title text-slate-800" style="font-size: 15px; font-weight: 600;">
          <i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Refund Payment
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
      </div>
      <form id="refundPaymentForm">
        <input type="hidden" name="payment_id" id="refundPaymentId">
        <input type="hidden" name="customer_id" id="refundCustomerId">
        <div class="modal-body p-4">
          <div class="p-3 mb-3 rounded-3" style="background: #fef2f2; border: 1px solid #fee2e2;">
            <div class="d-flex justify-content-between mb-1" style="font-size: 12px;">
              <span class="text-slate-600">Customer:</span>
              <span class="fw-bold text-slate-800" id="refundCustNameDisplay"></span>
            </div>
            <div class="d-flex justify-content-between mb-1" style="font-size: 12px;">
              <span class="text-slate-600">Payment For:</span>
              <span class="fw-semibold text-slate-700" id="refundForMonthDisplay"></span>
            </div>
            <div class="d-flex justify-content-between" style="font-size: 12px;">
              <span class="text-slate-600">Available Payment:</span>
              <span class="fw-bold text-danger" id="refundAvailableAmtDisplay"></span>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Refund Amount (৳) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control form-control-sm border-slate-200 shadow-none" name="refund_amount" id="refundAmountInput" required>
            <div class="form-text text-slate-400" style="font-size: 11px;">Amount will be deducted from this payment and customer balance. History remains with note.</div>
          </div>

          <div class="mb-3">
            <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Refund Method</label>
            <select name="refund_method" class="form-select form-select-sm shadow-none">
              <option value="Cash">Cash</option>
              <option value="Bkash">Bkash</option>
              <option value="Nagad">Nagad</option>
              <option value="Rocket">Rocket</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Refund Note / Reason <span class="text-danger">*</span></label>
            <textarea name="refund_reason" id="refundReasonInput" class="form-control form-control-sm border-slate-200 shadow-none" rows="2" placeholder="e.g. Overpayment refund, client disconnected, billing adjustment..." required></textarea>
            <div class="form-text text-slate-400" style="font-size: 11px;">This note will stay permanently recorded in the payment history.</div>
          </div>
        </div>
        <div class="modal-footer border-top border-slate-100 py-2 px-4 bg-light">
          <button type="button" class="btn btn-sm btn-white border-slate-200" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger shadow-sm px-3" id="confirmRefundBtn">
            <i class="bi bi-check2-circle me-1"></i>Confirm Refund
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Transfer Payment Modal -->
<div class="modal fade" id="transferPaymentModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4 bg-light">
        <h5 class="modal-title text-slate-800" style="font-size: 15px; font-weight: 600;">
          <i class="bi bi-arrow-left-right text-primary me-2"></i>Transfer Payment to Another Customer
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
      </div>
      <form id="transferPaymentForm">
        <input type="hidden" name="source_payment_id" id="transferSourcePaymentId">
        <input type="hidden" name="source_customer_id" id="transferSourceCustomerId">
        <div class="modal-body p-4">
          <!-- Source info alert -->
          <div class="p-3 mb-3 rounded-3" style="background: #eff6ff; border: 1px solid #dbeafe;">
            <div class="row g-2 text-slate-700" style="font-size: 12px;">
              <div class="col-md-6">
                <span class="text-slate-500">From Customer:</span> <span class="fw-bold text-primary" id="transferSourceCustDisplay"></span>
              </div>
              <div class="col-md-6 text-md-end">
                <span class="text-slate-500">Available Payment:</span> <span class="fw-bold text-success" id="transferAvailableAmtDisplay"></span>
              </div>
              <div class="col-12">
                <span class="text-slate-500">Original Payment For:</span> <span class="fw-semibold text-slate-700" id="transferForMonthDisplay"></span>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Transfer Amount (৳) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control form-control-sm border-slate-200 shadow-none" name="transfer_amount" id="transferAmountInput" required>
              <div class="form-text text-slate-400" style="font-size: 11px;">Will be deducted from this client and credited to target client.</div>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Select Target Customer <span class="text-danger">*</span></label>
              <select name="target_customer_id" id="transferTargetCustomerSelect" class="form-select form-select-sm shadow-none" required>
                <option value="">-- Choose Target Customer --</option>
              </select>
            </div>
          </div>

          <!-- Target Customer Bills & Info Section (Revealed when customer chosen) -->
          <div id="targetCustomerDetailsSection" class="p-3 mb-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; display: none;">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-slate-200">
              <span class="fw-semibold text-slate-800" style="font-size: 12px;"><i class="bi bi-person-check text-success me-1"></i>Target Customer Info</span>
              <span class="badge bg-light text-slate-600 border" id="targetCustBalanceBadge" style="font-size: 11px;"></span>
            </div>
            
            <div class="mb-2">
              <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">
                Select Generated Bill to Pay
              </label>
              <select name="target_invoice_id" id="transferTargetInvoiceSelect" class="form-select form-select-sm shadow-none">
                <option value="">Advance Payment (No specific bill)</option>
              </select>
              <div class="form-text text-slate-400" style="font-size: 11px;">Select a specific generated bill to mark as paid/partial, or leave as Advance Payment.</div>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Transfer Note / Reason <span class="text-danger">*</span></label>
            <textarea name="transfer_reason" id="transferReasonInput" class="form-control form-control-sm border-slate-200 shadow-none" rows="2" placeholder="e.g. Transferred balance to family member, bill clearance, account adjustment..." required></textarea>
            <div class="form-text text-slate-400" style="font-size: 11px;">Recorded in both customers' payment history.</div>
          </div>
        </div>
        <div class="modal-footer border-top border-slate-100 py-2 px-4 bg-light">
          <button type="button" class="btn btn-sm btn-white border-slate-200" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary shadow-sm px-3" id="confirmTransferBtn">
            <i class="bi bi-check2-circle me-1"></i>Confirm Transfer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Customer Ticket & Notes Modal -->
<div class="modal fade" id="customerTicketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" style="max-width: 850px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff;">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; font-size: 15px;">
                <i class="bi bi-ticket-detailed"></i>
            </div>
            <div>
                <h5 class="modal-title fw-bold text-white m-0" style="font-size: 15px; line-height: 1.2;">Customer Tickets & Notes</h5>
                <div class="text-slate-300 small" style="font-size: 11px;">
                    <span id="ctmCustomerName" class="fw-semibold text-warning"></span> 
                    <span id="ctmCustomerUsername" class="badge bg-white/20 text-white ms-1 px-2 py-0.5" style="background: rgba(255,255,255,0.15);"></span>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-warning fw-semibold shadow-sm px-3 d-flex align-items-center gap-1" id="ctmNewTicketToggleBtn" style="font-size: 12px; border-radius: 6px;">
                <i class="bi bi-plus-lg"></i> <span>New Ticket</span>
            </button>
            <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
        </div>
      </div>
      
      <div class="modal-body p-3 bg-slate-50" style="min-height: 420px; max-height: calc(85vh - 120px); overflow-y: auto;">
          
          <!-- Add / Edit Ticket Form Card (Hidden by default, shown on toggle or edit) -->
          <div class="card border-0 shadow-sm mb-3" id="ctmTicketFormCard" style="display: none; border-radius: 10px; border-left: 4px solid #f59e0b !important;">
              <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom border-slate-100">
                  <h6 class="m-0 fw-bold text-slate-800" id="ctmFormHeading" style="font-size: 13px;">
                      <i class="bi bi-plus-circle text-warning me-1"></i> Create Support Ticket
                  </h6>
                  <button type="button" class="btn-close shadow-none" id="ctmCancelFormBtn" aria-label="Close" style="font-size: 10px;"></button>
              </div>
              <div class="card-body p-3 bg-white">
                  <form id="ctmTicketForm">
                      <input type="hidden" name="ticket_id" id="ctmTicketId" value="0">
                      <input type="hidden" name="customer_id" id="ctmCustomerId" value="0">
                      
                      <div class="row g-2 mb-2">
                          <div class="col-md-7">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px; font-weight: 600;">Title / Subject <span class="text-danger">*</span></label>
                              <input type="text" name="title" id="ctmTicketTitle" class="form-control form-control-sm border-slate-200" placeholder="e.g. Optical wire issue / Low speed / Router config" required style="font-size: 12px;">
                          </div>
                          <div class="col-md-5">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px; font-weight: 600;">Category</label>
                              <select name="category_id" id="ctmTicketCategory" class="form-select form-select-sm border-slate-200" style="font-size: 12px;">
                                  <option value="">General Issue</option>
                              </select>
                          </div>
                      </div>
                      
                      <div class="row g-2 mb-2">
                          <div class="col-md-6">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px; font-weight: 600;">Priority</label>
                              <select name="priority" id="ctmTicketPriority" class="form-select form-select-sm border-slate-200" style="font-size: 12px;">
                                  <option value="Low">Low</option>
                                  <option value="Medium" selected>Medium</option>
                                  <option value="High">High</option>
                                  <option value="Urgent">Urgent</option>
                              </select>
                          </div>
                          <div class="col-md-6">
                              <label class="form-label text-slate-600 mb-1" style="font-size: 11px; font-weight: 600;">Status</label>
                              <select name="status" id="ctmTicketStatus" class="form-select form-select-sm border-slate-200" style="font-size: 12px;">
                                  <option value="Pending" selected>Pending</option>
                                  <option value="In Progress">In Progress</option>
                                  <option value="Review">Review</option>
                                  <option value="Completed">Completed</option>
                                  <option value="Cancelled">Cancelled</option>
                              </select>
                          </div>
                      </div>
                      
                      <div class="mb-2">
                          <label class="form-label text-slate-600 mb-1" style="font-size: 11px; font-weight: 600;">Description / Notes <span class="text-danger">*</span></label>
                          <textarea name="description" id="ctmTicketDescription" rows="3" class="form-control form-control-sm border-slate-200" placeholder="Enter problem details, client complaint, or visit notes..." required style="font-size: 12px;"></textarea>
                      </div>

                      <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top border-slate-100">
                          <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-600 px-3" id="ctmFormCancelBtn" style="font-size: 12px;">Cancel</button>
                          <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm d-flex align-items-center gap-1" id="ctmSubmitBtn" style="font-size: 12px;">
                              <i class="bi bi-check2-circle"></i> <span id="ctmSubmitBtnText">Save Ticket</span>
                          </button>
                      </div>
                  </form>
              </div>
          </div>

          <!-- Tickets List Container -->
          <div id="ctmTicketsListContainer">
              <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                  <span class="text-slate-600 fw-semibold" style="font-size: 12px;"><i class="bi bi-card-checklist text-primary me-1"></i> Customer Ticket History</span>
                  <span class="badge bg-white text-slate-600 border border-slate-200 px-2 py-1" id="ctmTicketCountBadge" style="font-size: 11px;">0 Tickets</span>
              </div>
              <div id="ctmTicketsLoading" class="text-center py-5">
                  <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                  <div class="text-slate-400 small mt-2" style="font-size: 12px;">Loading tickets & notes...</div>
              </div>
              <div id="ctmTicketsEmpty" class="text-center py-5 bg-white rounded-3 border border-slate-200 p-4" style="display: none;">
                  <div class="text-slate-300 mb-2"><i class="bi bi-ticket-detailed" style="font-size: 38px;"></i></div>
                  <h6 class="text-slate-600 fw-semibold mb-1" style="font-size: 13px;">No Support Tickets Found</h6>
                  <p class="text-slate-400 mb-3" style="font-size: 11px;">This customer doesn't have any tickets or notes yet.</p>
                  <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm" id="ctmEmptyNewBtn" style="font-size: 12px;">
                      <i class="bi bi-plus-lg me-1"></i> Create First Ticket
                  </button>
              </div>
              <div id="ctmTicketsCards" class="d-flex flex-column gap-2">
                  <!-- Rendered dynamically via JS -->
              </div>
          </div>

      </div>
    </div>
  </div>
</div>

<!-- Hidden Iframe for Printing -->
<iframe name="print_frame" id="print_frame" style="display:none;"></iframe>

<!-- Filter Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
  <div class="offcanvas-header border-bottom border-slate-100 py-3 px-4">
    <h5 class="offcanvas-title text-slate-800" id="filterOffcanvasLabel" style="font-size: 15px; font-weight: 600;"><i class="bi bi-funnel text-primary me-2"></i> Filter Customers</h5>
    <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close" style="font-size: 12px;"></button>
  </div>
  <div class="offcanvas-body px-4 py-3">
    <form id="customerFilterForm">
<style>
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    transition: all 0.3s ease;
    height: 100%;
}
.filter-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transform: translateY(-1px);
}
.filter-icon-box {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
.icon-billing { background: #e0f2fe; color: #0284c7; }
.icon-router { background: #f3e8ff; color: #9333ea; }
.icon-nid { background: #ffedd5; color: #ea580c; }
.icon-date { background: #dcfce7; color: #16a34a; }

.simple-switch-checkbox { display: none; }
.simple-switch-label {
    display: inline-block;
    width: 32px;
    height: 18px;
    background: #e2e8f0;
    border-radius: 18px;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 4px;
    flex-shrink: 0;
}
.simple-switch-knob {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    background: #ffffff;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.simple-switch-checkbox:checked ~ .simple-switch-label {
    background: #3b82f6;
}
.simple-switch-checkbox:checked ~ .simple-switch-label .simple-switch-knob {
    left: calc(100% - 16px);
}
.simple-switch-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}
.simple-switch-text {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    transition: 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex: 1;
}
.text-left { text-align: right; }
.text-right { text-align: left; }

.simple-switch-checkbox:not(:checked) ~ .text-left {
    color: #0f172a;
}
.simple-switch-checkbox:checked ~ .text-right {
    color: #0f172a;
}
</style>
        <div class="row g-2 mb-3">
            <!-- Billing filter -->
            <div class="col-6">
                <div class="filter-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="filter-icon-box icon-billing"><i class="bi bi-wallet2"></i></div>
                        <h6 class="m-0 text-slate-700" style="font-size: 11px; font-weight: 700;">Billing</h6>
                    </div>
                    <div class="simple-switch-container">
                        <input type="checkbox" id="billingNeuSwitch" class="simple-switch-checkbox filter-switch-checkbox" data-filtered="false">
                        <span class="simple-switch-text text-left">Unpaid</span>
                        <label for="billingNeuSwitch" class="simple-switch-label"><div class="simple-switch-knob"></div></label>
                        <span class="simple-switch-text text-right">Paid</span>
                    </div>
                </div>
            </div>
            
            <!-- Router status -->
            <div class="col-6">
                <div class="filter-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="filter-icon-box icon-router"><i class="bi bi-router"></i></div>
                        <h6 class="m-0 text-slate-700" style="font-size: 11px; font-weight: 700;">Router</h6>
                    </div>
                    <div class="simple-switch-container">
                        <input type="checkbox" id="routerNeuSwitch" class="simple-switch-checkbox filter-switch-checkbox" data-filtered="false">
                        <span class="simple-switch-text text-left" style="letter-spacing: 0px;">Disable</span>
                        <label for="routerNeuSwitch" class="simple-switch-label"><div class="simple-switch-knob"></div></label>
                        <span class="simple-switch-text text-right" style="letter-spacing: 0px;">Enable</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <!-- NID Missing -->
            <!-- NID Missing -->
            <div class="col-6">
                <div class="filter-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="filter-icon-box icon-nid"><i class="bi bi-person-vcard"></i></div>
                        <h6 class="m-0 text-slate-700" style="font-size: 11px; font-weight: 700;">NID Missing?</h6>
                    </div>
                    <div class="simple-switch-container">
                        <input type="checkbox" id="nidNeuSwitch" class="simple-switch-checkbox filter-switch-checkbox" data-filtered="false">
                        <span class="simple-switch-text text-left" style="letter-spacing: 0px;">No</span>
                        <label for="nidNeuSwitch" class="simple-switch-label"><div class="simple-switch-knob"></div></label>
                        <span class="simple-switch-text text-right" style="letter-spacing: 0px;">Yes</span>
                    </div>
                </div>
            </div>
            
            <!-- Special User -->
            <div class="col-6">
                <div class="filter-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="filter-icon-box" style="background: #fef3c7; color: #d97706;"><i class="bi bi-star-fill"></i></div>
                        <h6 class="m-0 text-slate-700" style="font-size: 11px; font-weight: 700;">Special User</h6>
                    </div>
                    <div class="simple-switch-container">
                        <input type="checkbox" id="specialNeuSwitch" class="simple-switch-checkbox filter-switch-checkbox" data-filtered="false">
                        <span class="simple-switch-text text-left" style="letter-spacing: 0px;">No</span>
                        <label for="specialNeuSwitch" class="simple-switch-label"><div class="simple-switch-knob"></div></label>
                        <span class="simple-switch-text text-right" style="letter-spacing: 0px;">Yes</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expired Date Search -->
        <div class="row g-2 mb-4">
            <div class="col-12">
                <div class="filter-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="filter-icon-box icon-date"><i class="bi bi-calendar-event"></i></div>
                        <h6 class="m-0 text-slate-700" style="font-size: 11px; font-weight: 700;">Billing Expired Date</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="position-relative flex-grow-1">
                            <label class="position-absolute top-0 start-0 ms-2 bg-white px-1 text-slate-400" style="font-size: 10px; transform: translateY(-50%); z-index: 1;">From</label>
                            <input type="date" class="form-control form-control-sm border-slate-200 shadow-none py-2 px-2" id="filterDateFrom" style="font-size: 12px; border-radius: 8px;">
                        </div>
                        <div class="text-slate-300"><i class="bi bi-arrow-right"></i></div>
                        <div class="position-relative flex-grow-1">
                            <label class="position-absolute top-0 start-0 ms-2 bg-white px-1 text-slate-400" style="font-size: 10px; transform: translateY(-50%); z-index: 1;">To</label>
                            <input type="date" class="form-control form-control-sm border-slate-200 shadow-none py-2 px-2" id="filterDateTo" style="font-size: 12px; border-radius: 8px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
  </div>
  <div class="offcanvas-footer border-top border-slate-100 py-3 px-4 bg-light d-flex gap-2">
      <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm w-50" id="resetFilterBtn" style="font-size: 12px; font-weight: 500; color: #475569;">Reset</button>
      <button type="button" class="btn btn-sm bg-primary text-white shadow-sm w-50" id="applyFilterBtn" style="font-size: 12px; font-weight: 500;">Apply Filters</button>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Run automated billing engine in the background silently
    $.get('../ajax/auto_generate_bills.php', function(res) {
        if (res.status === 'success' && res.generated > 0) {
            console.log('Automated Billing Engine: Generated ' + res.generated + ' new bills.');
            // Optionally reload to update balances if bills were generated
            // location.reload();
        }
    });

    var table = $('#customerTable').DataTable({
        "order": [] // Disable initial sorting
    });
    
    // Custom Toolbar Search
    $('#searchCustomer').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Filter Table by Customer Stat Cards
    $(document).on('click', '.customer-stat-card', function() {
        var filterVal = $(this).data('filter');
        if ($(this).hasClass('active-filter') || !filterVal) {
            $('.customer-stat-card').removeClass('active-filter');
            table.column(7).search('').draw();
        } else {
            $('.customer-stat-card').removeClass('active-filter');
            $(this).addClass('active-filter');
            table.column(7).search(filterVal).draw();
        }
    });

    // Track switch interaction for filtering
    $('.filter-switch-checkbox').on('change', function() {
        $(this).attr('data-filtered', 'true');
    });

    // Custom Filters
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (settings.nTable.id !== 'customerTable') return true;

        var isBillingFiltered = $('#billingNeuSwitch').attr('data-filtered') === 'true';
        var filterBilling = isBillingFiltered ? ($('#billingNeuSwitch').is(':checked') ? 'Paid' : 'Unpaid') : '';
        
        var isRouterFiltered = $('#routerNeuSwitch').attr('data-filtered') === 'true';
        var filterRouter = isRouterFiltered ? ($('#routerNeuSwitch').is(':checked') ? 'Enabled' : 'Disabled') : '';
        
        var isNidFiltered = $('#nidNeuSwitch').attr('data-filtered') === 'true';
        var filterNid = isNidFiltered ? ($('#nidNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';
        
        var isSpecialFiltered = $('#specialNeuSwitch').attr('data-filtered') === 'true';
        var filterSpecial = isSpecialFiltered ? ($('#specialNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';

        var filterDateFrom = $('#filterDateFrom').val();
        var filterDateTo = $('#filterDateTo').val();

        var clientNodeHtml = settings.aoData[dataIndex].anCells[1].innerHTML || '';
        var contactNodeText = settings.aoData[dataIndex].anCells[2].innerText || '';
        var statusNodeText = settings.aoData[dataIndex].anCells[7].innerText || '';
        var datesNodeText = settings.aoData[dataIndex].anCells[5].innerText || '';

        // NID Missing Filter
        if (filterNid) {
            var hasNid = contactNodeText.indexOf('NID:') !== -1;
            if (filterNid === 'Yes' && hasNid) {
                return false; // User selected "Yes" (Missing), but NID exists
            }
            if (filterNid === 'No' && !hasNid) {
                return false; // User selected "No" (Not missing), but NID doesn't exist
            }
        }

        // Special User Filter
        if (filterSpecial) {
            var hasSpecial = clientNodeHtml.indexOf('Special User') !== -1;
            if (filterSpecial === 'Yes' && !hasSpecial) {
                return false;
            }
            if (filterSpecial === 'No' && hasSpecial) {
                return false;
            }
        }

        // Billing Status Filter
        if (filterBilling) {
            if (statusNodeText.indexOf(filterBilling) === -1) {
                return false;
            }
        }

        // Router Status Filter
        if (filterRouter) {
            if (statusNodeText.indexOf(filterRouter) === -1) {
                return false;
            }
        }

        // Date Expired Filter (Parse Billing Date)
        if (filterDateFrom || filterDateTo) {
            var cycleMatch = datesNodeText.match(/Bill:\s*(\d{4}-\d{2}-\d{2})/);
            if (cycleMatch && cycleMatch[1]) {
                var cycleDate = new Date(cycleMatch[1]);
                if (filterDateFrom) {
                    if (cycleDate < new Date(filterDateFrom)) return false;
                }
                if (filterDateTo) {
                    if (cycleDate > new Date(filterDateTo)) return false;
                }
            } else {
                return false; // No billing date found but filter active
            }
        }

        return true;
    });

    $('#applyFilterBtn').on('click', function() {
        table.draw();
        var offcanvasElement = document.getElementById('filterOffcanvas');
        var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (offcanvas) offcanvas.hide();
    });

    $('#resetFilterBtn').on('click', function() {
        $('#customerFilterForm')[0].reset();
        $('.filter-switch-checkbox').attr('data-filtered', 'false');
        table.draw();
    });

    // Router Status Async Check
    // Track switch interaction for filtering
    $('.filter-switch-checkbox').on('change', function() {
        $(this).attr('data-filtered', 'true');
    });

    // Custom Filters
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (settings.nTable.id !== 'customerTable') return true;

        var isBillingFiltered = $('#billingNeuSwitch').attr('data-filtered') === 'true';
        var filterBilling = isBillingFiltered ? ($('#billingNeuSwitch').is(':checked') ? 'Paid' : 'Unpaid') : '';
        
        var isRouterFiltered = $('#routerNeuSwitch').attr('data-filtered') === 'true';
        var filterRouter = isRouterFiltered ? ($('#routerNeuSwitch').is(':checked') ? 'Enabled' : 'Disabled') : '';
        
        var isNidFiltered = $('#nidNeuSwitch').attr('data-filtered') === 'true';
        var filterNid = isNidFiltered ? ($('#nidNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';
        
        var isSpecialFiltered = $('#specialNeuSwitch').attr('data-filtered') === 'true';
        var filterSpecial = isSpecialFiltered ? ($('#specialNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';

        var filterDateFrom = $('#filterDateFrom').val();
        var filterDateTo = $('#filterDateTo').val();

        var clientNodeHtml = settings.aoData[dataIndex].anCells[1].innerHTML || '';
        var contactNodeText = settings.aoData[dataIndex].anCells[2].innerText || '';
        var statusNodeText = settings.aoData[dataIndex].anCells[7].innerText || '';
        var datesNodeText = settings.aoData[dataIndex].anCells[5].innerText || '';

        // NID Missing Filter
        if (filterNid) {
            var hasNid = contactNodeText.indexOf('NID:') !== -1;
            if (filterNid === 'Yes' && hasNid) {
                return false; // User selected "Yes" (Missing), but NID exists
            }
            if (filterNid === 'No' && !hasNid) {
                return false; // User selected "No" (Not missing), but NID doesn't exist
            }
        }

        // Special User Filter
        if (filterSpecial) {
            var hasSpecial = clientNodeHtml.indexOf('Special User') !== -1;
            if (filterSpecial === 'Yes' && !hasSpecial) {
                return false;
            }
            if (filterSpecial === 'No' && hasSpecial) {
                return false;
            }
        }

        // Billing Status Filter
        if (filterBilling) {
            if (statusNodeText.indexOf(filterBilling) === -1) {
                return false;
            }
        }

        // Router Status Filter
        if (filterRouter) {
            if (statusNodeText.indexOf(filterRouter) === -1) {
                return false;
            }
        }

        // Date Expired Filter (Parse Billing Date)
        if (filterDateFrom || filterDateTo) {
            var cycleMatch = datesNodeText.match(/Bill:\s*(\d{4}-\d{2}-\d{2})/);
            if (cycleMatch && cycleMatch[1]) {
                var cycleDate = new Date(cycleMatch[1]);
                if (filterDateFrom) {
                    if (cycleDate < new Date(filterDateFrom)) return false;
                }
                if (filterDateTo) {
                    if (cycleDate > new Date(filterDateTo)) return false;
                }
            } else {
                return false; // No billing date found but filter active
            }
        }

        return true;
    });

    $('#applyFilterBtn').on('click', function() {
        table.draw();
        var offcanvasElement = document.getElementById('filterOffcanvas');
        var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (offcanvas) offcanvas.hide();
    });

    $('#resetFilterBtn').on('click', function() {
        $('#customerFilterForm')[0].reset();
        $('.filter-switch-checkbox').attr('data-filtered', 'false');
        table.draw();
    });

    // Router Status Async Check
    // Router Status Async Check
    var routerStatuses = {};
    
    function updateRouterBadges() {
        $('.router-status-badge').each(function() {
            var uname = $(this).data('username');
            if (routerStatuses[uname] !== undefined) {
                var rstatus = routerStatuses[uname];
                if (rstatus === 'Enabled') {
                    $(this).html('<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10px;"><i class="bi bi-router"></i> Enabled</span>');
                } else {
                    $(this).html('<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px;"><i class="bi bi-router"></i> Disabled</span>');
                }
            } else {
                $(this).html('<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 10px;" title="Not found on router"><i class="bi bi-router"></i> Not Found</span>');
            }
        });
    }

    $.get('../ajax/get_ppp_status.php', function(res) {
        if (res.status === 'success') {
            routerStatuses = res.data;
            updateRouterBadges();
        }
    });

    table.on('draw', function() {
        if (Object.keys(routerStatuses).length > 0) {
            updateRouterBadges();
        }
    });

    // Handle CSV Preview
    $('#importClientForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        formData.append('action', 'preview');
        
        $.ajax({
            url: '../ajax/import_customers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                let res = typeof response === 'object' ? response : JSON.parse(response);
                if(res.success) {
                    $('#importClientModal').modal('hide');
                    $('#previewImportModal').modal('show');
                    
                    $('#readyCount').text(res.ready.length);
                    $('#skippedCount').text(res.skipped.length);
                    
                    let readyHtml = res.ready.map(r => `<div><i class="bi bi-check text-success"></i> ${r.name} (${r.username})</div>`).join('');
                    if(!readyHtml) readyHtml = '<div class="text-muted">None</div>';
                    $('#readyList').html(readyHtml);
                    
                    let skippedHtml = res.skipped.map(s => `<div><i class="bi bi-x text-danger"></i> ${s.name} (${s.username}) - ${s.reason}</div>`).join('');
                    if(!skippedHtml) skippedHtml = '<div class="text-muted">None</div>';
                    $('#skippedList').html(skippedHtml);
                    
                    if(res.ready.length === 0) {
                        $('#confirmImportBtn').prop('disabled', true);
                    } else {
                        $('#confirmImportBtn').prop('disabled', false);
                    }
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function() {
                alert('An error occurred during preview.');
            }
        });
    });

    // Handle Confirm Import
    $('#confirmImportBtn').on('click', function() {
        let formData = new FormData($('#importClientForm')[0]);
        formData.append('action', 'import');
        
        $(this).html('<i class="bi bi-hourglass-split"></i> Importing...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/import_customers.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                let res = typeof response === 'object' ? response : JSON.parse(response);
                if(res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    $('#confirmImportBtn').html('<i class="bi bi-check2-circle"></i> Confirm Import').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred during import.');
                $('#confirmImportBtn').html('<i class="bi bi-check2-circle"></i> Confirm Import').prop('disabled', false);
            }
        });
    });
    // View Ledger Modal — use delegation so it works on all DataTable pages
    $(document).on('click', '.view-ledger', function() {
        var customerId = $(this).data('id');
        $('#ledgerCustomerId').val(customerId);
        loadLedger(customerId);
        $('#ledgerModal').modal('show');
    });

    function loadLedger(customerId) {
        $.ajax({
            url: '../ajax/get_ledger.php',
            type: 'GET',
            data: { id: customerId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#ledgerClientName').text(response.data.name);
                    $('#ledgerTotalBilled').text('৳' + response.data.total_billed);
                    $('#ledgerTotalPaid').text('৳' + response.data.total_paid);
                    
                    var bal = parseFloat(response.data.balance);
                    var $balEl = $('#ledgerBalance');
                    $balEl.text((bal >= 0 ? '+' : '') + '৳' + bal.toFixed(2));
                    $balEl.removeClass('text-success text-danger').addClass(bal > 0 ? 'text-success' : (bal < 0 ? 'text-danger' : 'text-slate-700'));
                    
                    // Destroy existing DataTables if any
                    if ($.fn.DataTable.isDataTable('#ledgerInvoicesTable')) {
                        $('#ledgerInvoicesTable').DataTable().destroy();
                    }
                    if ($.fn.DataTable.isDataTable('#ledgerPaymentsTable')) {
                        $('#ledgerPaymentsTable').DataTable().destroy();
                    }
                    
                    // Set Mikrotik Switch
                    $('#ledgerMikrotikSwitch').prop('checked', response.data.pppoe_status === 'enable');

                    // Populate Customer Info
                    $('#ledgerCustName').text(response.data.name || '-');
                    $('#ledgerCustPhone').text(response.data.phone || '-');
                    var fullAddress = response.data.address || '';
                    if (response.data.subarea_name) {
                        fullAddress += (fullAddress ? ', ' : '') + response.data.subarea_name;
                    }
                    if (response.data.area_name) {
                        fullAddress += (fullAddress ? ', ' : '') + response.data.area_name;
                    }
                    $('#ledgerCustAddress').text(fullAddress || '-');
                    $('#ledgerCustUsername').text(response.data.username || '-');
                    $('#ledgerCustPassword').text(response.data.password || '-');
                    var onuDisplay = response.data.onu_sn || '-';
                    if (response.data.onu_sn && response.data.onu_submitted_by) {
                        onuDisplay += ' <span class="badge bg-light text-slate-500 border border-slate-200 ms-1 fw-normal" style="font-size: 9px;" title="Submitted by: ' + response.data.onu_submitted_by + '"><i class="bi bi-person me-1"></i>' + response.data.onu_submitted_by + '</span>';
                    }
                    $('#ledgerCustOnu').html(onuDisplay);
                    $('#ledgerCustPackage').text(response.data.package_name || '-');
                    $('#ledgerCustPrice').text(response.data.package_price || '0.00');
                    $('#ledgerCustDiscount').text(response.data.discount || '0.00');
                    $('#ledgerCustConnDate').text(response.data.connection_date || '-');
                    $('#ledgerCustBillDate').text(response.data.billing_date || '-');
                    $('#ledgerCustCycleDate').text(response.data.payment_cycle_date || '-');

                    // Set Promise Date Pill
                    var today = new Date().toISOString().split('T')[0];
                    var pDate = response.data.promise_date || '';
                    if (pDate && pDate <= today) {
                        pDate = '';
                        $.post('../ajax/set_promise_date.php', { id: customerId, action: 'clear' });
                    }
                    updatePromiseDatePill(pDate);
                    if (window._promisePicker) {
                        window._promisePicker.setDate(pDate ? pDate : null, false);
                    }

                    // Populate Invoices Table and Dropdown
                    var invoicesHtml = '';
                    var dropdownHtml = '<option value="">Advance Payment</option>';
                    if (response.data.invoices && response.data.invoices.length > 0) {
                        $.each(response.data.invoices, function(i, item) {
                            var statusBadge = '';
                            if (item.status === 'Paid') {
                                statusBadge = ' <span class="badge bg-success ms-1" style="font-size: 10px;">Paid</span>';
                            } else if (item.status === 'Partial') {
                                statusBadge = ' <span class="badge ms-1" style="background-color: #fd7e14; font-size: 10px;">Partial</span>';
                            }
                            
                            var amountDisplay = '-৳' + parseFloat(item.amount).toFixed(2);
                            if (item.status === 'Partial') {
                                amountDisplay += ' <span class="text-danger d-block mt-1" style="font-size: 10px; font-weight: 600;">(Due: ৳' + parseFloat(item.due_amount).toFixed(2) + ')</span>';
                            }

                            invoicesHtml += '<tr>';
                            invoicesHtml += '<td class="text-slate-700 py-1">' + item.date_val + '</td>';
                            invoicesHtml += '<td class="text-slate-600 py-1"><span class="badge bg-danger text-white" style="font-weight: 500;">' + item.description + '</span>' + statusBadge + '</td>';
                            invoicesHtml += '<td class="text-danger fw-bold py-1" style="line-height: 1.2;">' + amountDisplay + '</td>';
                            invoicesHtml += '<td class="text-end py-1">';
                            invoicesHtml += '<div class="btn-group">';
                            invoicesHtml += '<a href="../print_invoice.php?id=' + item.id + '&format=pos" target="_blank" class="btn btn-sm btn-light border-slate-200 text-slate-600" title="Print POS Receipt" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-receipt"></i></a>';
                            invoicesHtml += '<a href="../print_invoice.php?id=' + item.id + '&format=general" target="_blank" class="btn btn-sm btn-light border-slate-200 text-slate-600" title="Print A4 Invoice" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-printer"></i></a>';
                            invoicesHtml += '</div>';
                            invoicesHtml += '</td>';
                            invoicesHtml += '</tr>';
                            
                            if (item.status === 'Unpaid' || item.status === 'Partial') {
                                var amountToPay = item.status === 'Partial' ? item.due_amount : item.amount;
                                dropdownHtml += '<option value="' + item.id + '" data-amount="' + amountToPay + '" data-desc="' + item.description + '">' + item.description + '</option>';
                            }
                        });
                    }
                    $('#ledgerInvoicesBody').html(invoicesHtml);
                    $('#addPaymentMonth').html(dropdownHtml);

                    // Populate Payments Table
                    var paymentsHtml = '';
                    if (response.data.payments && response.data.payments.length > 0) {
                        $.each(response.data.payments, function(i, item) {
                            var monthBadge = item.for_month ? ' <small class="text-muted">(' + item.for_month + ')</small>' : '';
                            var methodBadgeClass = 'bg-secondary';
                            if (item.description === 'Transfer') methodBadgeClass = 'bg-info text-dark';
                            else if (item.description === 'Adjust') methodBadgeClass = 'bg-warning text-dark';
                            else if (item.description === 'Bkash') methodBadgeClass = 'bg-danger text-white';

                            var noteHtml = '';
                            if (item.note) {
                                noteHtml = '<div class="text-slate-500 mt-1" style="font-size: 10px; line-height: 1.3;"><i class="bi bi-info-circle me-1 text-primary"></i>' + $('<div>').text(item.note).html() + '</div>';
                            }

                            var isFullyRefunded = parseFloat(item.amount) <= 0;
                            var hasRefundOrTransfer = item.note && (item.note.indexOf('Refunded') !== -1 || item.note.indexOf('Transferred') !== -1);
                            var statusBadge = '';
                            if (isFullyRefunded) {
                                statusBadge = ' <span class="badge bg-danger ms-1" style="font-size: 9px;"><i class="bi bi-arrow-counterclockwise me-1"></i>Refunded</span>';
                            } else if (hasRefundOrTransfer) {
                                statusBadge = ' <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;"><i class="bi bi-clock-history me-1"></i>Adjusted</span>';
                            }

                            var amtDisplay = (isFullyRefunded ? '৳' : '+৳') + parseFloat(item.amount).toFixed(2);
                            if (parseFloat(item.discount) > 0) {
                                amtDisplay += '<br><small class="text-danger">Disc: ৳' + parseFloat(item.discount).toFixed(2) + '</small>';
                            }

                            var amtClass = isFullyRefunded ? 'text-muted text-decoration-line-through' : 'text-success fw-bold';

                            paymentsHtml += '<tr>';
                            paymentsHtml += '<td class="text-slate-700 py-1" style="white-space: nowrap;">' + item.date_val + '</td>';
                            paymentsHtml += '<td class="text-slate-600 py-1"><span class="badge ' + methodBadgeClass + ' text-white" style="font-weight: 500;">' + item.description + '</span>' + monthBadge + statusBadge + noteHtml + '</td>';
                            paymentsHtml += '<td class="' + amtClass + ' py-1">' + amtDisplay + '</td>';
                            
                            paymentsHtml += '<td class="text-end py-1">';
                            paymentsHtml += '<div class="btn-group">';
                            paymentsHtml += '<a href="../print_receipt.php?id=' + item.id + '&format=pos" target="_blank" class="btn btn-sm btn-light border-slate-200 text-slate-600" title="Print POS Receipt" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-receipt"></i></a>';
                            paymentsHtml += '<a href="../print_receipt.php?id=' + item.id + '&format=general" target="_blank" class="btn btn-sm btn-light border-slate-200 text-slate-600" title="Print A4 Receipt" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-printer"></i></a>';
                            if (!isFullyRefunded && parseFloat(item.amount) > 0) {
                                paymentsHtml += '<button type="button" class="btn btn-sm btn-outline-danger btn-refund-payment" data-id="' + item.id + '" data-amount="' + item.amount + '" data-month="' + (item.for_month || '') + '" title="Refund Payment" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-arrow-counterclockwise"></i></button>';
                                paymentsHtml += '<button type="button" class="btn btn-sm btn-outline-primary btn-transfer-payment" data-id="' + item.id + '" data-amount="' + item.amount + '" data-month="' + (item.for_month || '') + '" title="Transfer to Another Customer" style="font-size: 11px; padding: 2px 6px;"><i class="bi bi-arrow-left-right"></i></button>';
                            }
                            paymentsHtml += '</div>';
                            paymentsHtml += '</td>';
                            paymentsHtml += '</tr>';
                        });
                    }
                    $('#ledgerPaymentsBody').html(paymentsHtml);

                    // Initialize DataTables
                    var dtOptions = {
                        "pageLength": 5,
                        "lengthChange": false,
                        "order": []
                    };
                    var dtInvoices = $('#ledgerInvoicesTable').DataTable(dtOptions);
                    var dtPayments = $('#ledgerPaymentsTable').DataTable(dtOptions);
                    
                    // Custom Search
                    $('#searchInvoices').off('keyup').on('keyup', function() {
                        dtInvoices.search(this.value).draw();
                    });
                    $('#searchPayments').off('keyup').on('keyup', function() {
                        dtPayments.search(this.value).draw();
                    });
                } else {
                    showToast('error', response.message);
                }
            }
        });
    }

    // Auto-fill amount based on selected billing month
    $('#addPaymentMonth').on('change', function() {
        var amt = $(this).find('option:selected').data('amount');
        var desc = $(this).find('option:selected').data('desc');
        var $payFromBalance = $('#payFromBalance');
        
        $('#addPaymentDiscount').val(''); // Reset discount
        $('#addPaymentMonth').data('original-amount', amt);

        if (amt) {
            $('#addPaymentAmount').val(amt);
            $('#paymentForMonthStr').val(desc);
            $payFromBalance.prop('disabled', false);
        } else {
            $('#addPaymentAmount').val('');
            $('#paymentForMonthStr').val('');
            $payFromBalance.prop('checked', false).prop('disabled', true);
        }
    });

    $('#addPaymentDiscount').on('input', function() {
        var disc = parseFloat($(this).val()) || 0;
        var originalAmt = parseFloat($('#addPaymentMonth').data('original-amount'));
        if (!isNaN(originalAmt)) {
            var newAmt = originalAmt - disc;
            $('#addPaymentAmount').val(newAmt > 0 ? newAmt : 0);
        }
    });

    // Add Payment Form
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.html('<i class="bi bi-plus-circle me-1"></i>Add Payment').prop('disabled', false);
                if (response.status === 'success') {
                    showToast('success', response.message);
                    $('#addPaymentForm')[0].reset();
                    $('#addPaymentForm input[name="payment_date"]').val(new Date().toISOString().split('T')[0]);
                    loadLedger($('#ledgerCustomerId').val());
                    // We also need to reload page to update the main datatable balance if it's shown there, but ledger is fine for now
                } else {
                    showToast('error', response.message);
                }
            }
        });
    });

    // ==========================================
    // PAYMENT REFUND & TRANSFER LOGIC
    // ==========================================

    // Ensure scrolling works in ledger modal when sub-modals close
    $('#refundPaymentModal, #transferPaymentModal').on('hidden.bs.modal', function() {
        if ($('#ledgerModal').is(':visible')) {
            $('body').addClass('modal-open');
        }
    });

    // 1. Open Refund Modal
    $(document).on('click', '.btn-refund-payment', function() {
        var paymentId = $(this).data('id');
        var amount = parseFloat($(this).data('amount')) || 0;
        var month = $(this).data('month') || 'Advance Payment';
        var custName = $('#ledgerCustName').text() || $('#ledgerClientName').text();
        var custId = $('#ledgerCustomerId').val();

        $('#refundPaymentId').val(paymentId);
        $('#refundCustomerId').val(custId);
        $('#refundCustNameDisplay').text(custName);
        $('#refundForMonthDisplay').text(month);
        $('#refundAvailableAmtDisplay').text('৳' + amount.toFixed(2));

        $('#refundAmountInput').val(amount.toFixed(2)).attr('max', amount);
        $('#refundReasonInput').val('');

        $('#refundPaymentModal').modal('show');
    });

    // 2. Submit Refund Form
    $('#refundPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#confirmRefundBtn');
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Refunding...').prop('disabled', true);

        $.ajax({
            url: '../ajax/refund_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.html(originalHtml).prop('disabled', false);
                if (res.status === 'success') {
                    showToast('success', res.message);
                    $('#refundPaymentModal').modal('hide');
                    loadLedger($('#ledgerCustomerId').val());
                } else {
                    showToast('error', res.message || 'Refund failed');
                }
            },
            error: function() {
                $btn.html(originalHtml).prop('disabled', false);
                showToast('error', 'Network error while processing refund.');
            }
        });
    });

    // 3. Open Transfer Modal
    $(document).on('click', '.btn-transfer-payment', function() {
        var paymentId = $(this).data('id');
        var amount = parseFloat($(this).data('amount')) || 0;
        var month = $(this).data('month') || 'Advance Payment';
        var custName = $('#ledgerCustName').text() || $('#ledgerClientName').text();
        var custId = $('#ledgerCustomerId').val();

        $('#transferSourcePaymentId').val(paymentId);
        $('#transferSourceCustomerId').val(custId);
        $('#transferSourceCustDisplay').text(custName);
        $('#transferForMonthDisplay').text(month);
        $('#transferAvailableAmtDisplay').text('৳' + amount.toFixed(2));

        $('#transferAmountInput').val(amount.toFixed(2)).attr('max', amount);
        $('#transferReasonInput').val('');
        $('#targetCustomerDetailsSection').hide();
        $('#transferTargetInvoiceSelect').html('<option value="">Advance Payment (No specific bill)</option>');

        // Load customers into dropdown
        var $custSelect = $('#transferTargetCustomerSelect');
        $custSelect.html('<option value="">Loading customers...</option>').prop('disabled', true);

        $.ajax({
            url: '../ajax/get_transfer_targets.php',
            type: 'GET',
            data: { exclude_id: custId },
            dataType: 'json',
            success: function(res) {
                $custSelect.prop('disabled', false);
                if (res.status === 'success' && res.data) {
                    var opts = '<option value="">-- Choose Target Customer --</option>';
                    $.each(res.data, function(idx, c) {
                        var dueText = c.due > 0 ? (' | Due: ৳' + c.due.toFixed(2)) : '';
                        opts += '<option value="' + c.id + '">' + c.name + ' (' + c.username + dueText + ')</option>';
                    });
                    $custSelect.html(opts);
                } else {
                    $custSelect.html('<option value="">Failed to load customers</option>');
                }
            },
            error: function() {
                $custSelect.prop('disabled', false).html('<option value="">Error loading customers</option>');
            }
        });

        $('#transferPaymentModal').modal('show');
    });

    // 4. On Target Customer Change in Transfer Modal
    $('#transferTargetCustomerSelect').on('change', function() {
        var targetId = $(this).val();
        if (!targetId) {
            $('#targetCustomerDetailsSection').slideUp(180);
            return;
        }

        var $invSelect = $('#transferTargetInvoiceSelect');
        $invSelect.html('<option value="">Loading generated bills...</option>').prop('disabled', true);
        $('#targetCustomerDetailsSection').slideDown(180);

        $.ajax({
            url: '../ajax/get_customer_invoices.php',
            type: 'GET',
            data: { customer_id: targetId },
            dataType: 'json',
            success: function(res) {
                $invSelect.prop('disabled', false);
                if (res.status === 'success') {
                    var c = res.customer;
                    var badgeText = 'Current Due: ৳' + c.due.toFixed(2) + ' | Advance: ৳' + c.advance.toFixed(2);
                    $('#targetCustBalanceBadge').text(badgeText);

                    var opts = '<option value="" data-due="0">Advance Payment (Credit to Balance)</option>';
                    if (res.invoices && res.invoices.length > 0) {
                        var unpaidCount = 0;
                        $.each(res.invoices, function(idx, inv) {
                            if (inv.status === 'Unpaid' || inv.status === 'Partial') {
                                unpaidCount++;
                                opts += '<option value="' + inv.id + '" data-due="' + inv.due_amount + '">' + inv.month_display + ' (Bill: ৳' + parseFloat(inv.amount).toFixed(2) + ' | Due: ৳' + inv.due_amount.toFixed(2) + ') [' + inv.status + ']</option>';
                            }
                        });
                        if (unpaidCount === 0) {
                            opts += '<option disabled>-- No Unpaid Bills Found (Will credit as Advance) --</option>';
                        }
                    } else {
                        opts += '<option disabled>-- No Bills Generated Yet (Will credit as Advance) --</option>';
                    }
                    $invSelect.html(opts);
                } else {
                    $invSelect.html('<option value="">Advance Payment (Credit to Balance)</option>');
                }
            },
            error: function() {
                $invSelect.prop('disabled', false).html('<option value="">Advance Payment (Credit to Balance)</option>');
            }
        });
    });

    // 5. Submit Transfer Form
    $('#transferPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#confirmTransferBtn');
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Transferring...').prop('disabled', true);

        $.ajax({
            url: '../ajax/transfer_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.html(originalHtml).prop('disabled', false);
                if (res.status === 'success') {
                    showToast('success', res.message);
                    $('#transferPaymentModal').modal('hide');
                    loadLedger($('#ledgerCustomerId').val());
                } else {
                    showToast('error', res.message || 'Transfer failed');
                }
            },
            error: function() {
                $btn.html(originalHtml).prop('disabled', false);
                showToast('error', 'Network error while processing transfer.');
            }
        });
    });

    // Mikrotik Switch Toggle
    $('#ledgerMikrotikSwitch').on('change', function() {
        var customerId = $('#ledgerCustomerId').val();
        var isEnabled = $(this).is(':checked');
        var action = isEnabled ? 'enable' : 'disable';
        var $switch = $(this);
        
        $switch.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/toggle_mikrotik_status.php',
            type: 'POST',
            data: { id: customerId, action: action },
            dataType: 'json',
            success: function(response) {
                $switch.prop('disabled', false);
                if (response.status === 'success') {
                    showToast('success', response.message);
                } else {
                    showToast('error', response.message);
                    $switch.prop('checked', !isEnabled); // Revert UI
                }
            },
            error: function() {
                $switch.prop('disabled', false);
                $switch.prop('checked', !isEnabled); // Revert UI
                showToast('error', 'Network error while updating Mikrotik status.');
            }
        });
    });

    // ---- Promise Date Flatpickr ----
    function updatePromiseDatePill(dateStr) {
        var $pill = $('#promiseDatePill');
        var $val = $('#pdValueDisplay');
        var $clear = $('#ledgerPromiseDateClearBtn');
        if (dateStr) {
            var d = new Date(dateStr + 'T00:00:00');
            var formatted = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            $val.text(formatted);
            $pill.addClass('has-date');
            $pill.find('.pd-icon i').removeClass('bi-calendar-event').addClass('bi-calendar-check');
            $clear.show();
        } else {
            $val.text('Click to set');
            $pill.removeClass('has-date');
            $pill.find('.pd-icon i').removeClass('bi-calendar-check').addClass('bi-calendar-event');
            $clear.hide();
        }
    }
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    window._promisePicker = flatpickr('#ledgerPromiseDateInput', {
        minDate: tomorrow,
        disableMobile: true,
        dateFormat: 'Y-m-d',
        onChange: function(selectedDates, dateStr) {
            if (!dateStr) return;
            var customerId = $('#ledgerCustomerId').val();
            $.ajax({
                url: '../ajax/set_promise_date.php',
                type: 'POST',
                data: { id: customerId, action: 'set', date: dateStr },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        showToast('success', res.message);
                        updatePromiseDatePill(dateStr);
                    } else {
                        showToast('error', res.message);
                        window._promisePicker.clear();
                    }
                },
                error: function() { showToast('error', 'Network error.'); window._promisePicker.clear(); }
            });
        }
    });
    $(document).on('click', '#promiseDatePill', function(e) {
        if ($(e.target).closest('#ledgerPromiseDateClearBtn').length) return;
        window._promisePicker.open();
    });
    $(document).on('click', '#ledgerPromiseDateClearBtn', function(e) {
        e.stopPropagation();
        var customerId = $('#ledgerCustomerId').val();
        $.ajax({
            url: '../ajax/set_promise_date.php',
            type: 'POST',
            data: { id: customerId, action: 'clear' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    window._promisePicker.clear();
                    updatePromiseDatePill('');
                } else { showToast('error', res.message); }
            },
            error: function() { showToast('error', 'Network error.'); }
        });
    });

    // Edit Customer Modal Logic
    function calculateEditFinalPrice() {
        let original = parseFloat($('#edit_original_price').val()) || 0;
        let discount = parseFloat($('#edit_discount').val()) || 0;
        let final = original - discount;
        $('#edit_package_price').val(final > 0 ? final.toFixed(2) : '0.00');
    }
    $('#edit_discount').on('input', calculateEditFinalPrice);

    $('#edit_package').on('change', function() {
        var price = $(this).find(':selected').data('price');
        if(price) {
            $('#edit_original_price').val(price);
            calculateEditFinalPrice();
        }
    });

    $('#edit_area').on('change', function() {
        var area_id = $(this).val();
        if(area_id) {
            $.post('../ajax/get_subareas.php', { area_id: area_id }, function(res) {
                var html = '<option value="">Select Sub Area...</option>';
                if(res.status === 'success') {
                    $.each(res.data, function(i, v) {
                        html += '<option value="' + v.id + '">' + v.subarea_name + '</option>';
                    });
                }
                $('#edit_subarea').html(html);
                if (window.pendingEditSubarea) {
                    $('#edit_subarea').val(window.pendingEditSubarea);
                    window.pendingEditSubarea = null;
                }
            }, 'json');
        } else {
            $('#edit_subarea').html('<option value="">Select Sub Area...</option>');
        }
    });

    $('#edit_router').on('change', function() {
        var router_id = $(this).val();
        if(router_id) {
            $.post('../ajax/get_packages.php', { host_id: router_id }, function(res) {
                var html = '<option value="">Select Package...</option>';
                if(res.status === 'success') {
                    $.each(res.data, function(i, v) {
                        html += '<option value="' + v.id + '" data-price="' + v.price + '">' + v.given_name + ' (৳' + v.price + ')</option>';
                    });
                }
                $('#edit_package').html(html);
                if (window.pendingEditPackage) {
                    $('#edit_package').val(window.pendingEditPackage);
                    var price = $('#edit_package').find(':selected').data('price');
                    $('#edit_original_price').val(price || window.pendingEditPackagePrice);
                    window.pendingEditPackage = null;
                    window.pendingEditPackagePrice = null;
                } else {
                    $('#edit_package_price').val('');
                    $('#edit_original_price').val('');
                }
                calculateEditFinalPrice();
            }, 'json');
        } else {
            $('#edit_package').html('<option value="">Select Package...</option>');
            $('#edit_package_price').val('');
            $('#edit_original_price').val('');
        }
    });

    function fetchAndOpenEditModal(customerId) {
        $.get('../ajax/get_customer.php?id=' + customerId, function(res) {
            if (res.status === 'success') {
                var data = res.data;
                $('#edit_customer_id').val(data.id);
                $('#edit_name').val(data.name);
                $('#edit_phone').val(data.phone);
                $('#edit_email').val(data.email);
                $('#edit_onu').val(data.onu_sn);
                $('#edit_area').val(data.area_id);
                
                // Load subareas
                if(data.area_id) {
                    window.pendingEditSubarea = data.subarea_id;
                    $('#edit_area').trigger('change');
                } else {
                    $('#edit_subarea').html('<option value="">Select Sub Area...</option>');
                }
                
                $('#edit_address').val(data.address);
                $('#edit_nid').val(data.nid_no);
                $('#edit_router').val(data.router_id);
                
                // Load packages
                if(data.router_id) {
                    window.pendingEditPackage = data.package_id;
                    window.pendingEditPackagePrice = data.package_price;
                    $('#edit_router').trigger('change');
                } else {
                    $('#edit_package').html('<option value="">Select Package...</option>');
                }
                
                $('#edit_username').val(data.username);
                $('#edit_password').val(data.password);
                $('#edit_discount').val(data.discount);
                $('#edit_package_price').val(data.package_price);
                $('#edit_connection_date').val(data.connection_date);
                $('#edit_billing_date').val(data.billing_date);
                $('#edit_payment_cycle_date').val(data.payment_cycle_date);
                $('#edit_client_note').val(data.note);
                $('#edit_is_special').prop('checked', data.is_special == 1);
                
                $('#editCustomerModal').modal('show');
            } else {
                showToast('error', res.message);
            }
        });
    }

    $(document).on('click', '.edit-customer', function() {
        var customerId = $(this).data('id');
        fetchAndOpenEditModal(customerId);
    });


    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#saveEditCustomerBtn');
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Saving...');
        $.ajax({
            url: '../ajax/edit_customer.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save Changes');
                if(res.status === 'success') {
                    $('#editCustomerModal').modal('hide');
                    showToast('success', res.message);
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    showToast('error', res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save Changes');
                showToast('error', 'Network error.');
            }
        });
    });

    // ================= CUSTOMER TICKETS & NOTES MODAL SYSTEM =================
    const TICKET_AJAX_PATH = '../ajax/';
    let currentTicketCustomerId = 0;
    let customerTicketCategories = [];

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }

    function escapeAttr(str) {
        if (!str) return '';
        return String(str).replace(/"/g, '&quot;');
    }

    function getStatusBadge(status) {
        let cls = 'bg-secondary text-white';
        if (status === 'Pending') cls = 'bg-secondary text-white';
        else if (status === 'In Progress') cls = 'bg-primary text-white';
        else if (status === 'Review') cls = 'bg-warning text-dark';
        else if (status === 'Completed') cls = 'bg-success text-white';
        else if (status === 'Cancelled') cls = 'bg-danger text-white';
        return `<span class="badge ${cls} fw-semibold" style="font-size: 11px;">${escapeHtml(status)}</span>`;
    }

    function getPriorityBadge(priority) {
        let cls = 'bg-success-subtle text-success border border-success-subtle';
        let icon = 'bi-flag';
        if (priority === 'Medium') {
            cls = 'bg-warning-subtle text-warning border border-warning-subtle';
            icon = 'bi-flag-fill';
        } else if (priority === 'High') {
            cls = 'bg-danger-subtle text-danger border border-danger-subtle';
            icon = 'bi-flag-fill';
        } else if (priority === 'Urgent') {
            cls = 'bg-danger text-white';
            icon = 'bi-exclamation-triangle-fill';
        }
        return `<span class="badge ${cls} fw-semibold" style="font-size: 11px;"><i class="bi ${icon} me-1"></i>${escapeHtml(priority)}</span>`;
    }

    function getStatusBorderColor(status) {
        if (status === 'In Progress') return '#0284c7';
        if (status === 'Review') return '#f59e0b';
        if (status === 'Completed') return '#10b981';
        if (status === 'Cancelled') return '#ef4444';
        return '#64748b';
    }

    function loadCustomerTickets(customerId) {
        $('#ctmTicketsLoading').show();
        $('#ctmTicketsEmpty').hide();
        $('#ctmTicketsCards').empty();

        $.ajax({
            url: TICKET_AJAX_PATH + 'get_customer_tickets.php',
            type: 'GET',
            data: { customer_id: customerId },
            dataType: 'json',
            success: function(res) {
                $('#ctmTicketsLoading').hide();
                if (res.status === 'success') {
                    if (res.customer) {
                        $('#ctmCustomerName').text(res.customer.name || 'Customer');
                        $('#ctmCustomerUsername').text(res.customer.username ? '@' + res.customer.username : '');
                    }

                    customerTicketCategories = res.categories || [];
                    let catSelect = $('#ctmTicketCategory');
                    catSelect.empty().append('<option value="">General Issue</option>');
                    customerTicketCategories.forEach(function(c) {
                        catSelect.append(`<option value="${c.id}">${escapeHtml(c.name)}</option>`);
                    });

                    const tickets = res.tickets || [];
                    $('#ctmTicketCountBadge').text(tickets.length + (tickets.length === 1 ? ' Ticket' : ' Tickets'));

                    if (tickets.length === 0) {
                        $('#ctmTicketsEmpty').show();
                    } else {
                        tickets.forEach(function(t) {
                            const borderCol = getStatusBorderColor(t.status);
                            const statusBadge = getStatusBadge(t.status);
                            const priorityBadge = getPriorityBadge(t.priority);
                            const categoryBadge = t.category_name 
                                ? `<span class="badge bg-light text-slate-700 border border-slate-200" style="font-size: 10px;"><i class="bi bi-tag-fill text-slate-400 me-1"></i>${escapeHtml(t.category_name)}</span>` 
                                : '';
                            const replyCount = parseInt(t.reply_count || 0);

                            const cardHtml = `
                                <div class="card border-0 shadow-sm rounded-3 ticket-card-item mb-2.5" data-ticket-id="${t.id}" style="border-left: 4px solid ${borderCol} !important; background: #ffffff;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <span class="badge fw-bold font-monospace" style="font-size: 11px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">#${String(t.id).padStart(4, '0')}</span>
                                                    <span class="fw-bold text-slate-800" style="font-size: 13px;">${escapeHtml(t.title)}</span>
                                                    ${categoryBadge}
                                                </div>
                                                <div class="text-slate-400 mt-1" style="font-size: 11px;">
                                                    <i class="bi bi-clock me-1"></i>${escapeHtml(t.created_at_formatted || t.created_at)} 
                                                    ${t.created_by ? `<span class="ms-1">&bull; by <span class="text-slate-600 fw-medium">${escapeHtml(t.created_by)}</span></span>` : ''}
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                ${statusBadge}
                                                ${priorityBadge}
                                            </div>
                                        </div>

                                        <div class="text-slate-600 bg-slate-50 p-2.5 rounded-2 border border-slate-100 mb-2" style="font-size: 12px; line-height: 1.5; white-space: pre-line;">${escapeHtml(t.description || '').replace(/\\n/g, '<br>')}</div>

                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100 mt-2">
                                            <button type="button" class="btn btn-sm btn-light border-slate-200 text-slate-600 px-2.5 py-1 ctm-toggle-replies-btn" data-ticket-id="${t.id}" style="font-size: 11px;">
                                                <i class="bi bi-chat-left-dots text-primary me-1"></i>
                                                <span class="reply-count-text">${replyCount} Note${replyCount === 1 ? '' : 's'}</span>
                                                <i class="bi bi-chevron-down ms-1" style="font-size: 9px;"></i>
                                            </button>
                                            <div class="d-flex align-items-center gap-1.5">
                                                <button type="button" class="btn btn-sm btn-outline-primary px-2.5 py-1 ctm-edit-ticket-btn" 
                                                    data-id="${t.id}" 
                                                    data-title="${escapeAttr(t.title)}" 
                                                    data-category-id="${t.category_id || ''}" 
                                                    data-priority="${t.priority}" 
                                                    data-status="${t.status}" 
                                                    data-description="${escapeAttr(t.description)}" 
                                                    style="font-size: 11px;">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit Ticket
                                                </button>
                                            </div>
                                        </div>

                                        <div class="ctm-replies-section mt-3 pt-2 border-top border-slate-200" id="ctmRepliesSection-${t.id}" style="display: none;">
                                            <div class="ctm-replies-content mb-3" id="ctmRepliesContent-${t.id}">
                                                <div class="text-center py-2 text-slate-400 small"><span class="spinner-border spinner-border-sm"></span> Loading history...</div>
                                            </div>
                                            <div class="bg-light p-2.5 rounded-2 border border-slate-200">
                                                <label class="form-label text-slate-700 fw-semibold mb-1" style="font-size: 11px;"><i class="bi bi-chat-dots-fill text-primary me-1"></i> Add Progress Note / Quick Update</label>
                                                <div class="d-flex gap-2">
                                                    <input type="text" class="form-control form-control-sm border-slate-200 ctm-quick-note-input" placeholder="Type a note or progress update..." style="font-size: 12px;">
                                                    <button type="button" class="btn btn-sm btn-primary px-3 flex-shrink-0 ctm-send-note-btn" data-ticket-id="${t.id}" data-status="${t.status}" data-priority="${t.priority}" style="font-size: 12px;">
                                                        <i class="bi bi-send-fill me-1"></i> Send
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            $('#ctmTicketsCards').append(cardHtml);
                        });
                    }
                } else {
                    showToast('error', res.message || 'Failed to load tickets.');
                }
            },
            error: function() {
                $('#ctmTicketsLoading').hide();
                showToast('error', 'Network error while loading tickets.');
            }
        });
    }

    // Open Customer Ticket Modal on Action click
    $(document).on('click', '.view-tickets', function() {
        currentTicketCustomerId = $(this).data('id');
        const custName = $(this).data('name') || '';
        const custUsername = $(this).data('username') || '';

        $('#ctmCustomerId').val(currentTicketCustomerId);
        $('#ctmCustomerName').text(custName);
        $('#ctmCustomerUsername').text(custUsername ? '@' + custUsername : '');

        resetTicketForm();
        $('#ctmTicketFormCard').hide();
        $('#ctmNewTicketToggleBtn').html('<i class="bi bi-plus-lg"></i> <span>New Ticket</span>');

        $('#customerTicketModal').modal('show');
        loadCustomerTickets(currentTicketCustomerId);
    });

    function resetTicketForm() {
        $('#ctmTicketId').val('0');
        $('#ctmTicketTitle').val('');
        $('#ctmTicketCategory').val('');
        $('#ctmTicketPriority').val('Medium');
        $('#ctmTicketStatus').val('Pending');
        $('#ctmTicketDescription').val('');
        $('#ctmFormHeading').html('<i class="bi bi-plus-circle text-warning me-1"></i> Create Support Ticket');
        $('#ctmSubmitBtnText').text('Save Ticket');
    }

    // Toggle Form with "+ New Ticket"
    $('#ctmNewTicketToggleBtn, #ctmEmptyNewBtn').on('click', function() {
        if ($('#ctmTicketFormCard').is(':visible') && $('#ctmTicketId').val() === '0') {
            $('#ctmTicketFormCard').slideUp(200);
            $('#ctmNewTicketToggleBtn').html('<i class="bi bi-plus-lg"></i> <span>New Ticket</span>');
        } else {
            resetTicketForm();
            $('#ctmTicketFormCard').slideDown(200);
            $('#ctmNewTicketToggleBtn').html('<i class="bi bi-x-lg"></i> <span>Close Form</span>');
            $('#ctmTicketTitle').focus();
        }
    });

    // Cancel Form
    $('#ctmCancelFormBtn, #ctmFormCancelBtn').on('click', function() {
        $('#ctmTicketFormCard').slideUp(200);
        $('#ctmNewTicketToggleBtn').html('<i class="bi bi-plus-lg"></i> <span>New Ticket</span>');
        resetTicketForm();
    });

    // Click Edit Ticket on Card
    $(document).on('click', '.ctm-edit-ticket-btn', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const categoryId = $(this).data('category-id');
        const priority = $(this).data('priority');
        const status = $(this).data('status');
        const description = $(this).data('description');

        $('#ctmTicketId').val(id);
        $('#ctmTicketTitle').val(title);
        $('#ctmTicketCategory').val(categoryId);
        $('#ctmTicketPriority').val(priority);
        $('#ctmTicketStatus').val(status);
        $('#ctmTicketDescription').val(description);

        $('#ctmFormHeading').html(`<i class="bi bi-pencil-square text-primary me-1"></i> Edit Ticket #${String(id).padStart(4, '0')}`);
        $('#ctmSubmitBtnText').text('Update Ticket');

        $('#ctmTicketFormCard').slideDown(200);
        $('#ctmNewTicketToggleBtn').html('<i class="bi bi-x-lg"></i> <span>Close Form</span>');

        $('#customerTicketModal .modal-body').animate({ scrollTop: 0 }, 250);
        $('#ctmTicketTitle').focus();
    });

    // Submit Ticket Form (Add or Edit)
    $('#ctmTicketForm').on('submit', function(e) {
        e.preventDefault();
        const ticketId = parseInt($('#ctmTicketId').val() || 0);
        const submitBtn = $('#ctmSubmitBtn');
        const originalText = $('#ctmSubmitBtnText').text();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        const endpoint = ticketId > 0 ? (TICKET_AJAX_PATH + 'update_ticket.php') : (TICKET_AJAX_PATH + 'add_ticket.php');

        $.ajax({
            url: endpoint,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> <span id="ctmSubmitBtnText">' + originalText + '</span>');
                if (res.status === 'success') {
                    showToast('success', res.message || (ticketId > 0 ? 'Ticket updated successfully' : 'Ticket created successfully'));
                    $('#ctmTicketFormCard').slideUp(200);
                    $('#ctmNewTicketToggleBtn').html('<i class="bi bi-plus-lg"></i> <span>New Ticket</span>');
                    resetTicketForm();
                    loadCustomerTickets(currentTicketCustomerId);
                } else {
                    showToast('error', res.message || 'Operation failed');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> <span id="ctmSubmitBtnText">' + originalText + '</span>');
                showToast('error', 'Network error while saving ticket.');
            }
        });
    });

    // Toggle Replies & Timeline
    $(document).on('click', '.ctm-toggle-replies-btn', function() {
        const ticketId = $(this).data('ticket-id');
        const section = $(`#ctmRepliesSection-${ticketId}`);
        const chevron = $(this).find('.bi-chevron-down, .bi-chevron-up');

        if (section.is(':visible')) {
            section.slideUp(180);
            chevron.removeClass('bi-chevron-up').addClass('bi-chevron-down');
        } else {
            section.slideDown(180);
            chevron.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            loadTicketReplies(ticketId);
        }
    });

    function loadTicketReplies(ticketId) {
        const container = $(`#ctmRepliesContent-${ticketId}`);
        container.html('<div class="text-center py-2 text-slate-400 small"><span class="spinner-border spinner-border-sm"></span> Loading notes...</div>');

        $.ajax({
            url: TICKET_AJAX_PATH + 'get_ticket_replies.php',
            type: 'GET',
            data: { ticket_id: ticketId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const replies = res.data || [];
                    if (replies.length === 0) {
                        container.html('<div class="text-slate-400 small py-1 text-center"><i class="bi bi-info-circle me-1"></i> No notes yet.</div>');
                    } else {
                        let html = '<div class="d-flex flex-column gap-2">';
                        replies.forEach(function(r) {
                            html += `
                                <div class="bg-white p-2.5 rounded border border-slate-200 shadow-2xs">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold text-primary" style="font-size: 11px;"><i class="bi bi-person-fill me-1"></i>${escapeHtml(r.created_by)}</span>
                                        <span class="text-slate-400" style="font-size: 10px;">${escapeHtml(r.created_at)}</span>
                                    </div>
                                    <div class="text-slate-700" style="font-size: 12px; line-height: 1.4;">${r.note}</div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        container.html(html);
                    }
                } else {
                    container.html('<div class="text-danger small py-1 text-center">Failed to load notes.</div>');
                }
            },
            error: function() {
                container.html('<div class="text-danger small py-1 text-center">Network error loading notes.</div>');
            }
        });
    }

    // Submit Quick Progress Note
    $(document).on('click', '.ctm-send-note-btn', function() {
        const ticketId = $(this).data('ticket-id');
        const status = $(this).data('status');
        const priority = $(this).data('priority');
        const input = $(this).closest('.d-flex').find('.ctm-quick-note-input');
        const note = $.trim(input.val());
        const btn = $(this);

        if (!note) {
            input.focus();
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: TICKET_AJAX_PATH + 'update_ticket.php',
            type: 'POST',
            data: {
                ticket_id: ticketId,
                status: status,
                priority: priority,
                progress_note: note
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send');
                if (res.status === 'success') {
                    input.val('');
                    showToast('success', 'Note added successfully');
                    loadTicketReplies(ticketId);
                    const card = $(`#ctmRepliesSection-${ticketId}`).closest('.ticket-card-item');
                    const countEl = card.find('.reply-count-text');
                    const curCount = parseInt(countEl.text()) || 0;
                    const newCount = curCount + 1;
                    countEl.text(newCount + (newCount === 1 ? ' Note' : ' Notes'));
                } else {
                    showToast('error', res.message || 'Failed to add note');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send');
                showToast('error', 'Network error adding note.');
            }
        });
    });

});
</script>
