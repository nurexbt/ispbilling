<?php
require_once '../includes/db.php';
$base_path = '../';
$is_noc = true;
$active_menu = 'store_record';
$page_title = 'Store Record';
$page_subtitle = 'Manage items and track physical inventory';
include '../includes/header.php';

if (!has_permission('noc_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Fetch Items
$items_query = "SELECT * FROM item_tbl ORDER BY id DESC";
$items_result = $conn->query($items_query);

// Pre-fetch items for the Store Add dropdown
$items_list = [];
if ($items_result && $items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $items_list[] = $row;
    }
    // reset pointer for table
    $items_result->data_seek(0);
}

// Fetch Store Inventory
$store_query = "SELECT s.*, i.item_name, e.full_name as employee_name, cr.id as connection_request_id 
                FROM store_tbl s 
                JOIN item_tbl i ON s.item_id = i.id 
                LEFT JOIN employee_tbl e ON s.delivered_to = e.id 
                LEFT JOIN connection_request_tbl cr ON s.barcode_id = cr.onu_sn 
                ORDER BY s.id DESC";
$store_result = $conn->query($store_query);


// Calculate Status Summaries
$category_stats = [];
if ($store_result && $store_result->num_rows > 0) {
    while ($row = $store_result->fetch_assoc()) {
        $item_name = $row['item_name'];
        if (!isset($category_stats[$item_name])) {
            $category_stats[$item_name] = [
                'In Stock' => 0,
                'Used' => 0,
                'Damage' => 0,
                'Replace' => 0,
                'Warranty' => 0,
                'Not approve yet' => 0,
                'Delivered' => 0,
                'Total' => 0
            ];
        }
        
        $is_in_stock = empty($row['connection_request_id']) && empty($row['return_reason']) && empty($row['employee_name']);
        $current_status = '';
        if ($is_in_stock) {
            $current_status = 'In Stock';
        } elseif (!empty($row['return_reason'])) {
            $current_status = $row['return_reason']; // Damaged, Replace, Warranty
            if ($current_status == 'Damaged') $current_status = 'Damage';
        } elseif (!empty($row['connection_request_id'])) {
            $current_status = 'Used';
        } elseif (!empty($row['employee_name'])) {
            if ($row['delivery_status'] == 'pending') {
                $current_status = 'Not approve yet';
            } else {
                $current_status = 'Delivered';
            }
        }
        
        if (isset($category_stats[$item_name][$current_status])) {
            $category_stats[$item_name][$current_status]++;
        } else {
            $category_stats[$item_name][$current_status] = 1;
        }
        $category_stats[$item_name]['Total']++;
    }
    $store_result->data_seek(0);
}

// Fetch Employees for Delivery

$employees_query = "SELECT id, full_name FROM employee_tbl ORDER BY full_name ASC";
$employees_result = $conn->query($employees_query);
$employees_list = [];
if ($employees_result && $employees_result->num_rows > 0) {
    while ($row = $employees_result->fetch_assoc()) {
        $employees_list[] = $row;
    }
}
?>

<div class="container-fluid pt-2">

<style>
.stepper-wrapper { display: flex; align-items: flex-start; justify-content: space-between; position: relative; width: 100%; min-width: 250px; padding-top: 5px; }
.step-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 2; }
.step-circle { width: 14px; height: 14px; border-radius: 50%; background-color: #fff; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; position: relative; z-index: 3; }
.step-circle::after { content: ''; width: 6px; height: 6px; border-radius: 50%; background-color: transparent; }
.step-item.completed .step-circle { border-color: #10b981; }
.step-item.completed .step-circle::after { background-color: #10b981; }
.step-item.error .step-circle { border-color: #ef4444; }
.step-item.error .step-circle::after { background-color: #ef4444; }
.step-line { position: absolute; top: 6px; left: 50%; width: 100%; height: 2px; background-color: #e2e8f0; z-index: 1; }
.step-item.completed .step-line { background-color: #10b981; }
.step-label { font-size: 9px; color: #64748b; margin-top: 4px; font-weight: 500; text-align: center; }
.step-item.completed .step-label { color: #10b981; font-weight: 600; }
.step-item.error .step-label { color: #ef4444; font-weight: 600; }
.step-time { font-size: 8px; color: #94a3b8; margin-top: 2px; text-align: center; line-height: 1.1; }
</style>

    <!-- Main Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar & Tabs -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <ul class="nav custom-segmented-control border-0" id="storeTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="item-tab" data-bs-toggle="pill" data-bs-target="#item_pane" type="button" role="tab">Item Categories</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventory-tab" data-bs-toggle="pill" data-bs-target="#inventory_pane" type="button" role="tab">Store Inventory</button>
                </li>
            </ul>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Add Item Button -->
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2 btn-add-toolbar" id="btnAddItem" data-bs-toggle="modal" data-bs-target="#addItemModal" style="font-weight: 600;">
                    <i class="bi bi-box-seam"></i> Add Item
                </button>
                <!-- Add Inventory Button (Hidden by default) -->
                <button class="btn-action bg-success text-white gap-2 px-3 py-2 btn-add-toolbar d-none" id="btnAddStore" data-bs-toggle="modal" data-bs-target="#addStoreModal" style="font-weight: 600;">
                    <i class="bi bi-upc-scan"></i> Add to Store
                </button>
            </div>
        </div>

        <div class="tab-content" id="storeTabContent">
            <!-- ITEMS TAB -->
            <div class="tab-pane fade show active" id="item_pane" role="tabpanel">
                <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center bg-light">
                    <input type="text" id="searchItem" class="toolbar-input" placeholder="Search item categories...">
                </div>
                <table id="itemTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 50px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 150px;">Item Name</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Statistics</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 120px;">Created At</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if ($items_result && $items_result->num_rows > 0): ?>
                            <?php $i = 1; while ($row = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="text-slate-900" style="font-size: 13px; font-weight: 600;"><i class="bi bi-box me-2 text-slate-400"></i><?= htmlspecialchars($row['item_name']) ?></div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php 
                                    $st = $category_stats[$row['item_name']] ?? [];
                                    ?>
                                                                        <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <?php 
                                        $color_map = [
                                            'In Stock' => ['color' => '#15803d', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
                                            'Used' => ['color' => '#1d4ed8', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                                            'Damage' => ['color' => '#b91c1c', 'bg' => '#fee2e2', 'border' => '#fecaca'],
                                            'Damaged' => ['color' => '#b91c1c', 'bg' => '#fee2e2', 'border' => '#fecaca'],
                                            'damaged' => ['color' => '#b91c1c', 'bg' => '#fee2e2', 'border' => '#fecaca'],
                                            'Replace' => ['color' => '#b45309', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                                            'Warranty' => ['color' => '#6d28d9', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
                                            'warrante' => ['color' => '#6d28d9', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
                                            'Delivered' => ['color' => '#0369a1', 'bg' => '#e0f2fe', 'border' => '#bae6fd'],
                                            'Not approve yet' => ['color' => '#334155', 'bg' => '#f1f5f9', 'border' => '#e2e8f0']
                                        ];
                                        foreach($st as $status_name => $count) {
                                            if($status_name === 'Total' || $count <= 0) continue;
                                            
                                            // Use a nicer display name
                                            $display_name = ucfirst($status_name); 
                                            if ($status_name == 'Not approve yet') $display_name = 'Pending';

                                            $colors = $color_map[$status_name] ?? ['color' => '#475569', 'bg' => '#f1f5f9', 'border' => '#cbd5e1'];
                                            
                                            echo '<span class="badge rounded-pill px-2 py-1 btn-filter-status" data-category="'.htmlspecialchars($row['item_name']).'" data-status="'.htmlspecialchars($status_name).'" style="cursor: pointer; color: '.$colors['color'].'; background-color: '.$colors['bg'].'; border: 1px solid '.$colors['border'].'; font-weight: 600; transition: transform 0.1s;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">' . htmlspecialchars($display_name) . ': ' . $count . '</span>';
                                        }
                                        ?>
                                        <span class="badge rounded-pill px-2 py-1 btn-filter-status" data-category="<?= htmlspecialchars($row['item_name']) ?>" data-status="Total" style="cursor: pointer; color: #0f172a; background-color: #f8fafc; border: 1px solid #e2e8f0; font-weight: 600; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">Total: <?= $st['Total'] ?? 0 ?></span>
                                    </div>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['created_at']) ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex gap-1">
                                        <button class="btn-action btn-action-green btn-edit-item" style="width: 24px; height: 24px;" title="Edit"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['item_name']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-action btn-action-red btn-delete-item" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <!-- STORE INVENTORY TAB -->
            <div class="tab-pane fade" id="inventory_pane" role="tabpanel">
            
                <div class="p-3 border-bottom border-slate-100 bg-white">

                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="searchStore" class="toolbar-input" placeholder="Search by barcode or item name..." style="width: 250px;">
                        <select id="filterStatus" class="toolbar-input form-select" style="min-width: 150px; width: auto; background-position: right 0.5rem center; padding-right: 2rem; color: #64748b;">
                            <option value="">All Statuses</option>
                            <option value="In Stock">In Stock</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Not approve yet">Not approve yet</option>
                            <option value="Used">Used</option>
                            <option value="Damage">Damage</option>
                            <option value="Replace">Replace</option>
                            <option value="Warranty">Warranty</option>
                        </select>
                    </div>
                </div>
                <table id="storeTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Item Category</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Barcode / Scan ID</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if ($store_result && $store_result->num_rows > 0): ?>
                            <?php $i = 1; while ($row = $store_result->fetch_assoc()): 
                                $is_in_stock = empty($row['connection_request_id']) && empty($row['return_reason']) && empty($row['employee_name']);
                                $current_status = '';
                                if ($is_in_stock) {
            $current_status = 'In Stock';
        } elseif (!empty($row['return_reason'])) {
                                    $current_status = $row['return_reason']; // Damaged, Replace, Warranty
                                } elseif (!empty($row['connection_request_id'])) {
            $current_status = 'Used';
        } elseif (!empty($row['employee_name'])) {
                                    if ($row['delivery_status'] == 'pending') {
                                        $current_status = 'Not approve yet';
                                    } else {
                                        $current_status = 'Delivered';
                                    }
                                }
                            ?>
                            <tr style="<?= $is_in_stock ? 'background-color: #f0fdf4;' : '' ?>">
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-3 py-1" style="font-weight: 600; font-size: 11px;"><?= htmlspecialchars($row['item_name']) ?></span>
                                        <?php if ($is_in_stock): ?>
                                            <span class="badge bg-success text-white" style="font-size: 9px;"><i class="bi bi-check-circle me-1"></i>In Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-upc text-slate-400 fs-6"></i>
                                        <span class="text-slate-900 font-monospace" style="font-size: 13px; font-weight: 500; letter-spacing: 0.5px;"><?= htmlspecialchars($row['barcode_id']) ?></span>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;" data-search="<?= htmlspecialchars($current_status) ?>">
                                    <?php
                                    $time_format = 'd M, h:ia';
                                    
                                    $s_added = 'completed'; 
                                    $s_delivered = !empty($row['delivered_at']) ? 'completed' : '';
                                    $s_approved = !empty($row['approved_at']) ? 'completed' : '';
                                    $s_used = !empty($row['used_at']) ? 'completed' : '';
                                    $s_returned = !empty($row['returned_at']) ? 'error' : '';

                                    // if current state is last one without next, we could mark it active, but completed is fine.
                                    
                                    $t_added = date($time_format, strtotime($row['created_at']));
                                    $t_delivered = !empty($row['delivered_at']) ? date($time_format, strtotime($row['delivered_at'])) : '';
                                    $t_approved = !empty($row['approved_at']) ? date($time_format, strtotime($row['approved_at'])) : '';
                                    $t_used = !empty($row['used_at']) ? date($time_format, strtotime($row['used_at'])) : '';
                                    $t_returned = !empty($row['returned_at']) ? date($time_format, strtotime($row['returned_at'])) : '';
                                    $return_label = !empty($row['return_reason']) ? htmlspecialchars($row['return_reason']) : 'Return';
                                    ?>
                                    
                                    <div class="stepper-wrapper">
                                        <div class="step-item <?= $s_added ?>">
                                            <div class="step-circle"></div>
                                            <div class="step-line"></div>
                                            <div class="step-label">Added</div>
                                            <div class="step-time"><?= $t_added ?></div>
                                        </div>
                                        <div class="step-item <?= $s_delivered ?>">
                                            <div class="step-circle"></div>
                                            <div class="step-line"></div>
                                            <div class="step-label">Delivered</div>
                                            <div class="step-time"><?= $t_delivered ?></div>
                                        </div>
                                        <div class="step-item <?= $s_approved ?>">
                                            <div class="step-circle"></div>
                                            <div class="step-line"></div>
                                            <div class="step-label">Approved</div>
                                            <div class="step-time"><?= $t_approved ?></div>
                                        </div>
                                        <div class="step-item <?= $s_used ?>">
                                            <div class="step-circle"></div>
                                            <div class="step-line"></div>
                                            <div class="step-label">Used</div>
                                            <div class="step-time"><?= $t_used ?></div>
                                        </div>
                                        <div class="step-item <?= $s_returned ?>">
                                            <div class="step-circle"></div>
                                            <div class="step-label"><?= $return_label ?></div>
                                            <div class="step-time"><?= $t_returned ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="btn-action bg-info text-white border-0 btn-history-store d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" title="History" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-clock-history" style="font-size: 12px;"></i>
                                        </button>
                                        <?php if (!empty($row['employee_name'])): ?>
                                            <button class="btn-action bg-warning text-dark border-0 btn-return-store d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" title="Return from <?= htmlspecialchars($row['employee_name']) ?>" data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-arrow-return-left" style="font-size: 12px;"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action bg-primary text-white border-0 btn-deliver-store d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" title="Deliver to" data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-person-check" style="font-size: 12px;"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (isset($row['return_reason']) && $row['return_reason'] === 'Warranty'): ?>
                                            <button class="btn-action bg-success text-white border-0 btn-readd-store d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" title="Re-Add to Store" data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-arrow-repeat" style="font-size: 12px;"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-action btn-action-red btn-delete-store" style="width: 24px; height: 24px; border-radius: 4px;" title="Delete" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-trash" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Return Equipment Modal -->
<div class="modal fade" id="returnStoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-warning text-dark rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-arrow-return-left"></i></div> Return Equipment
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="returnStoreForm">
            <input type="hidden" name="store_id" id="return_store_id">
            
            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Return Reason</label>
                <select name="return_reason" id="return_reason_select" class="form-select border-slate-200 shadow-none py-2 text-slate-700" style="font-size: 13px;" required>
                    <option value="" disabled selected>Select Reason...</option>
                    <option value="Damage">Damage</option>
                    <option value="Warranty">Warranty</option>
                    <option value="Replace">Replace</option>
                </select>
            </div>
            
            <div class="mb-2 d-none" id="replace_id_container">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Replacement Item ID/Barcode</label>
                <input type="text" name="replace_new_id" id="replace_new_id" class="form-control border-slate-200 shadow-none py-2 text-slate-700" style="font-size: 13px;" placeholder="Enter new item barcode...">
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-warning text-dark shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 600;"><i class="bi bi-check2-circle"></i> Submit</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyStoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-info text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-clock-history"></i></div> Item History
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body p-0">
          <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" id="historyTable">
                  <thead class="bg-slate-50" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                      <tr>
                          <th style="padding: 12px 16px; width: 180px;">Time</th>
                          <th style="padding: 12px 16px; width: 120px;">Action</th>
                          <th style="padding: 12px 16px;">Description</th>
                          <th style="padding: 12px 16px; width: 150px;">By</th>
                      </tr>
                  </thead>
                  <tbody style="font-size: 13px;" id="historyTableBody">
                      <!-- Loaded via AJAX -->
                  </tbody>
              </table>
          </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-box-seam"></i></div> Add Item Category
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addItemForm">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Item Name *</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" name="item_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. ONU Router, 8-Port Switch" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Item</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-pencil-square"></i></div> Edit Item Category
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editItemForm">
            <input type="hidden" name="item_id" id="edit_item_id">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Item Name *</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" name="item_name" id="edit_item_name" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Item</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Store (Rapid Barcode Scan) Modal -->
<div class="modal fade" id="addStoreModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-upc-scan"></i></div> Add to Store
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <p class="text-slate-500 mb-4" style="font-size: 12px;">Select an item category, then scan barcodes. Scanned items will be listed below before submitting.</p>
        
        <div class="mb-3">
            <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Item *</label>
            <div class="input-group input-group-sm input-group-rounded">
                <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-box-fill"></i></span>
                <select id="scan_item_id" class="form-select border-slate-200 shadow-none py-2" required>
                    <option value="" disabled selected>Select an item...</option>
                    <?php foreach($items_list as $itm): ?>
                        <option value="<?= $itm['id'] ?>"><?= htmlspecialchars($itm['item_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="mb-2">
            <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Scan Barcode ID</label>
            <div class="input-group input-group-sm input-group-rounded" style="box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2); border-radius: 8px;">
                <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-upc-scan"></i></span>
                <input type="text" id="scanner_input" class="form-control border-slate-200 shadow-none py-2" placeholder="Scan or type ID and press Enter..." autocomplete="off">
            </div>
        </div>
        
        <!-- Scanned Items List UI -->
        <div class="mt-3 p-3 bg-light rounded border border-slate-200" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-slate-600 fw-bold" style="font-size: 12px;">Scanned Items (<span id="scan_count">0</span>)</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="clear_scans" style="font-size: 11px; text-decoration: none;">Clear All</button>
            </div>
            <div id="scanned_badges_container" class="d-flex flex-wrap gap-2">
                <!-- Badges go here -->
            </div>
        </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="button" id="submit_scans_btn" class="btn btn-sm bg-success text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;" disabled><i class="bi bi-save-fill"></i> Save to Database</button>
      </div>
    </div>
  </div>
</div>

<!-- Deliver Item Modal -->
<div class="modal fade" id="deliverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-person-check"></i></div> Deliver Item
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="deliverForm">
            <input type="hidden" name="store_id" id="deliver_store_id">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Employee *</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-person-fill"></i></span>
                    <select name="employee_id" class="form-select border-slate-200 shadow-none py-2" required>
                        <option value="" disabled selected>Select an employee...</option>
                        <?php foreach($employees_list as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Submit Delivery</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    var itemTable = $('#itemTable').DataTable();
    var storeTable = $('#storeTable').DataTable();
    
    // Tab switching logic for buttons
    $('#storeTab button').on('shown.bs.tab', function (event) {
        var target = $(event.target).attr("data-bs-target");
        $('.btn-add-toolbar').addClass('d-none');
        if(target === '#item_pane') {
            $('#btnAddItem').removeClass('d-none');
        } else if(target === '#inventory_pane') {
            $('#btnAddStore').removeClass('d-none');
        }
    });

    // Custom Toolbar Search Binding
    $('#searchItem').on('keyup', function() { itemTable.search(this.value).draw(); });
    $('#searchStore').on('keyup', function() { storeTable.search(this.value).draw(); });
    $('#filterStatus').on('change', function() {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        storeTable.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // AJAX Form Handling
    function handleFormSubmit(formSelector, url, modalSelector) {
        $(formSelector).on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            var originalText = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showToast('success', response.message);
                        setTimeout(function(){ location.reload(); }, 800); 
                    } else {
                        showToast('error', response.message);
                        $btn.html(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    showToast('error', 'Server error occurred.');
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
    }

    handleFormSubmit('#addItemForm', '../ajax/add_item.php', '#addItemModal');
    handleFormSubmit('#editItemForm', '../ajax/edit_item.php', '#editItemModal');
    handleFormSubmit('#deliverForm', '../ajax/deliver_item.php', '#deliverModal');

    // Item Edit Binding
    $(document).on('click', '.btn-edit-item', function() {
        $('#edit_item_id').val($(this).data('id'));
        $('#edit_item_name').val($(this).data('name'));
        $('#editItemModal').modal('show');
    });
    
    // Deliver Store Item Binding
    $(document).on('click', '.btn-deliver-store', function() {
        $('#deliver_store_id').val($(this).data('id'));
        $('#deliverModal').modal('show');
    });

    // Deletion Logic (Generic)
    function bindDelete(btnClass, url, title) {
        $(document).on('click', btnClass, function() {
            var id = $(this).data('id');
            var paramName = url.includes('item') ? 'item_id' : 'store_id';
            var dataPayload = {};
            dataPayload[paramName] = id;
            
            Swal.fire({
                title: title,
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'compact-swal-popup',
                    title: 'compact-swal-title',
                    htmlContainer: 'compact-swal-text',
                    actions: 'compact-swal-actions',
                    confirmButton: 'btn btn-sm btn-danger px-4',
                    cancelButton: 'btn btn-sm btn-secondary px-3 text-white'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url, type: 'POST', data: dataPayload, dataType: 'json',
                        success: function(response) {
                            if(response.status === 'success') {
                                showToast('success', response.message);
                                setTimeout(function(){ location.reload(); }, 800);
                            } else {
                                showToast('error', response.message);
                            }
                        }
                    });
                }
            });
        });
    }

    bindDelete('.btn-delete-item', '../ajax/delete_item.php', 'Delete Item Category?');
    bindDelete('.btn-delete-store', '../ajax/delete_store.php', 'Delete from Store?');

    // Return Equipment
    $(document).on('click', '.btn-return-store', function() {
        var id = $(this).data('id');
        $('#return_store_id').val(id);
        $('#returnStoreForm')[0].reset();
        $('#replace_id_container').addClass('d-none');
        $('#replace_new_id').prop('required', false);
        $('#returnStoreModal').modal('show');
    });
    
    $('#return_reason_select').on('change', function() {
        if ($(this).val() === 'Replace') {
            $('#replace_id_container').removeClass('d-none');
            $('#replace_new_id').prop('required', true);
        } else {
            $('#replace_id_container').addClass('d-none');
            $('#replace_new_id').prop('required', false);
        }
    });

    // History Modal Logic
    $(document).on('click', '.btn-history-store', function() {
        var store_id = $(this).data('id');
        $('#historyTableBody').html('<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm text-info"></span> Loading history...</td></tr>');
        $('#historyStoreModal').modal('show');
        
        $.ajax({
            url: '../ajax/get_store_history.php',
            type: 'GET',
            data: { store_id: store_id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (response.data.length === 0) {
                        $('#historyTableBody').html('<tr><td colspan="4" class="text-center py-4 text-slate-500">No history found for this item.</td></tr>');
                    } else {
                        var html = '';
                        response.data.forEach(function(row) {
                            var actionClass = 'text-primary';
                            if (row.action === 'Added' || row.action === 'Re-Added') actionClass = 'text-success';
                            if (row.action === 'Returned') actionClass = 'text-danger';
                            if (row.action === 'Used') actionClass = 'text-warning';
                            
                            html += '<tr>';
                            html += '  <td class="text-slate-500"><i class="bi bi-clock me-1"></i> ' + row.created_at + '</td>';
                            html += '  <td class="font-weight-600 ' + actionClass + '">' + row.action + '</td>';
                            html += '  <td class="text-slate-700">' + row.description + '</td>';
                            html += '  <td class="text-slate-600"><i class="bi bi-person me-1"></i> ' + row.action_by + '</td>';
                            html += '</tr>';
                        });
                        $('#historyTableBody').html(html);
                    }
                } else {
                    $('#historyTableBody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Error: ' + response.message + '</td></tr>');
                }
            },
            error: function() {
                $('#historyTableBody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Failed to load history.</td></tr>');
            }
        });
    });

    // Re-Add to Store Logic
    $(document).on('click', '.btn-readd-store', function() {
        var store_id = $(this).data('id');
        
        Swal.fire({
            title: 'Re-Add to Store?',
            text: "This will make the item available in stock again.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Re-Add',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'compact-swal-popup',
                title: 'compact-swal-title',
                htmlContainer: 'compact-swal-text',
                actions: 'compact-swal-actions',
                confirmButton: 'btn btn-sm btn-success px-4',
                cancelButton: 'btn btn-sm btn-secondary px-3 text-white'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/readd_store_item.php',
                    type: 'POST',
                    data: { store_id: store_id },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            showToast('success', response.message);
                            setTimeout(function(){ location.reload(); }, 800);
                        } else {
                            showToast('error', response.message);
                        }
                    }
                });
            }
        });
    });

    $('#returnStoreForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/return_equipment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#returnStoreModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 800); 
                } else {
                    showToast('error', response.message);
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while returning equipment.');
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });


    // ==========================================
    // RAPID BARCODE SCANNING WORKFLOW
    // ==========================================
    let scannedBarcodes = [];

    // Focus input automatically when modal opens
    $('#addStoreModal').on('shown.bs.modal', function () {
        $('#scanner_input').focus();
    });

    // Intercept Enter key on scanner input
    $('#scanner_input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault(); // Stop modal form submission if any
            var code = $(this).val().trim();
            var $input = $(this);
            
            if (code.length > 0) {
                if (!scannedBarcodes.includes(code)) {
                    $input.prop('disabled', true);
                    $.ajax({
                        url: '../ajax/check_barcode.php',
                        type: 'POST',
                        data: { barcode: code },
                        dataType: 'json',
                        success: function(res) {
                            if (res.exists) {
                                showToast('error', 'Barcode ' + code + ' already exists in Store!');
                            } else {
                                scannedBarcodes.push(code);
                                renderScannedBadges();
                            }
                        },
                        complete: function() {
                            $input.prop('disabled', false).val('').focus();
                        }
                    });
                } else {
                    showToast('error', 'Barcode already scanned in this batch!');
                    $input.val('').focus();
                }
            }
        }
    });

    // Remove single barcode from list
    $(document).on('click', '.remove-barcode', function() {
        var codeToRemove = String($(this).data('code'));
        scannedBarcodes = scannedBarcodes.filter(c => c !== codeToRemove);
        renderScannedBadges();
        $('#scanner_input').focus();
    });

    // Clear all scans
    $('#clear_scans').on('click', function() {
        scannedBarcodes = [];
        renderScannedBadges();
        $('#scanner_input').focus();
    });

    function renderScannedBadges() {
        var $container = $('#scanned_badges_container');
        $container.empty();
        
        scannedBarcodes.forEach(function(code) {
            $container.append(`
                <div class="bg-white border border-slate-300 rounded px-3 py-2 d-flex align-items-center gap-2 shadow-sm" style="font-family: monospace; font-size: 14px; font-weight: 600; color: #1e293b;">
                    ${code}
                    <i class="bi bi-x-circle-fill text-danger remove-barcode fs-6" data-code="${code}" style="cursor: pointer; margin-left: 4px;"></i>
                </div>
            `);
        });
        
        $('#scan_count').text(scannedBarcodes.length);
        
        if (scannedBarcodes.length > 0 && $('#scan_item_id').val()) {
            $('#submit_scans_btn').prop('disabled', false);
        } else {
            $('#submit_scans_btn').prop('disabled', true);
        }
    }
    
    // Also re-check button status if item dropdown changes
    $('#scan_item_id').on('change', function() {
        renderScannedBadges(); 
        $('#scanner_input').focus();
    });

    // Final Submission of array
    $('#submit_scans_btn').on('click', function() {
        var itemId = $('#scan_item_id').val();
        if (!itemId) {
            showToast('error', 'Please select an item category first.');
            return;
        }
        if (scannedBarcodes.length === 0) return;
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_store.php',
            type: 'POST',
            data: {
                item_id: itemId,
                barcodes: scannedBarcodes
            },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    setTimeout(function(){ location.reload(); }, 1200); 
                } else {
                    showToast('error', response.message);
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showToast('error', 'Server error occurred.');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });


    // Clickable badges filtering
    $(document).on('click', '.btn-filter-status', function() {
        var category = $(this).data('category');
        var status = String($(this).data('status'));

        // Switch to Store Inventory tab
        var inventoryTab = new bootstrap.Tab(document.querySelector('#inventory-tab'));
        inventoryTab.show();

        // Set search values
        $('#searchStore').val(category);
        storeTable.search(category);

        if (status === 'Total') {
            $('#filterStatus').val('');
            storeTable.column(3).search('', true, false);
        } else {
            var $option = $('#filterStatus option').filter(function() { return $(this).val() == status; });
            if ($option.length) {
                $('#filterStatus').val(status);
            } else {
                $('#filterStatus').val(''); 
            }
            // "Not approve yet" maps to internal status "Not approve yet", which is what $status_name holds.
            var val = $.fn.dataTable.util.escapeRegex(status);
            storeTable.column(3).search('^' + val + '$', true, false);
        }
        
        storeTable.draw();
    });

});
</script>
