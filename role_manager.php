<?php
require_once 'includes/db.php';
$active_menu = 'role_manager';
$page_title = 'Role Manager';
$page_subtitle = 'Manage user roles and permissions';
include 'includes/header.php';

if (!has_permission('role_manager_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to manage roles.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'includes/sidebar.php';

// Fetch roles from DB
$roles_query = "SELECT * FROM role_tbl ORDER BY id DESC";
$roles_result = $conn->query($roles_query);

$modules = [
    'Dashboard' => ['view' => 'View'],
    'Host' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Package' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Area' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Company' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Customer' => ['view' => 'View', 'import' => 'Import', 'export' => 'Export', 'ledger' => 'Ledger'],
    'Connection Request' => ['view' => 'View', 'add' => 'Add', 'assign' => 'Assign', 'add_client' => 'Add Client', 'delete' => 'Delete'],
    'Payment Record' => ['view' => 'View'],
    'Amount Collect' => ['view' => 'View', 'ledger_menu' => 'Ledger Menu', 'ledger_view' => 'Ledger View (View Only)', 'amount_handover' => 'Amount Handover'],
    'Store Record' => ['view' => 'View', 'add_item_category' => 'Add Item Category', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Store Inventory' => ['view' => 'View', 'add' => 'Add', 'deliver' => 'Deliver', 'return' => 'Return', 'delete' => 'Delete'],
    'Employee' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Expense' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Salary' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Portal User' => ['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'],
    'Role Manager' => ['full_access' => 'Full Access'],
    'Support Ticket' => ['view' => 'View', 'add' => 'Add', 'update' => 'Update'],
    'User Activity' => ['view' => 'View', 'delete' => 'Delete']
];
?>

<style>
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}
.permission-card {
    background: #fff;
    border: 1px solid var(--border-slate-200);
    border-radius: 8px;
    padding: 12px;
}
.permission-card h6 {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-slate-800);
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-slate-100);
}
.form-check-label {
    font-size: 12px;
    color: var(--text-slate-600);
}
</style>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search roles...">
            </div>
            <div>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addRoleModal" style="font-weight: 600;">
                    <i class="bi bi-shield-plus"></i> Add Role
                </button>
            </div>
        </div>

        <!-- Table -->
        <table id="roleTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Role Name</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Created At</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($roles_result && $roles_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $roles_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900" style="font-size: 13px; font-weight: 600;">
                                    <i class="bi bi-shield-check text-primary me-2"></i><?= htmlspecialchars($row['role_name']) ?>
                                </div>
                            </td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['created_at']) ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <button class="btn-action btn-action-green btn-edit-role" style="width: 24px; height: 24px;" title="Edit"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['role_name']) ?>"
                                            data-permissions="<?= htmlspecialchars($row['permissions']) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn-action btn-action-red btn-delete-role" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-shield-plus"></i></div> Add New Role
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4" style="max-height: 60vh; overflow-y: auto;">
        <form id="addRoleForm">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Role Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="role_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. Area Manager" required>
                </div>
            </div>
            
            <h6 class="text-slate-700 fw-bold mb-3" style="font-size: 14px;">Permissions</h6>
            
            <div class="permission-grid">
                <?php foreach($modules as $module_name => $perms): ?>
                <?php $mod_key = strtolower(str_replace(' ', '_', $module_name)); ?>
                <div class="permission-card">
                    <h6><?= htmlspecialchars($module_name) ?></h6>
                    <?php foreach($perms as $perm_key => $perm_label): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="permissions[<?= $mod_key ?>_<?= $perm_key ?>]" value="1" id="add_<?= $mod_key ?>_<?= $perm_key ?>">
                        <label class="form-check-label" for="add_<?= $mod_key ?>_<?= $perm_key ?>"><?= htmlspecialchars($perm_label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Transmission Portal Full Access -->
                <div class="permission-card" style="border-color: #0dcaf0;">
                    <h6 class="text-info" style="color: #0dcaf0 !important;"><i class="bi bi-broadcast"></i> Transmission Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[transmission_full_access]" value="1" id="add_transmission_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_transmission_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Transmission Portal.</small>
                </div>
                
                <!-- Owner Portal Full Access -->
                <div class="permission-card" style="border-color: #ef4444;">
                    <h6 class="text-danger" style="color: #ef4444 !important;"><i class="bi bi-person-workspace"></i> Owner Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[owner_full_access]" value="1" id="add_owner_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_owner_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Owner Portal.</small>
                </div>
                
                <!-- Manager Portal Full Access -->
                <div class="permission-card" style="border-color: #f59e0b;">
                    <h6 class="text-warning" style="color: #f59e0b !important;"><i class="bi bi-briefcase"></i> Manager Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[manager_full_access]" value="1" id="add_manager_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_manager_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Manager Portal.</small>
                </div>
                
                <!-- Billing Portal Full Access -->
                <div class="permission-card" style="border-color: #10b981;">
                    <h6 class="text-success" style="color: #10b981 !important;"><i class="bi bi-receipt"></i> Billing Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[billing_full_access]" value="1" id="add_billing_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_billing_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Billing Portal.</small>
                </div>
                
                <!-- TxBill Portal Full Access -->
                <div class="permission-card" style="border-color: #8b5cf6;">
                    <h6 style="color: #8b5cf6 !important;"><i class="bi bi-intersect"></i> TxBill Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[txbill_full_access]" value="1" id="add_txbill_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_txbill_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate TxBill Portal.</small>
                </div>

                <!-- NOC Portal Full Access -->
                <div class="permission-card" style="border-color: #6366f1;">
                    <h6 style="color: #6366f1 !important;"><i class="bi bi-hdd-network"></i> NOC Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input" type="checkbox" name="permissions[noc_full_access]" value="1" id="add_noc_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="add_noc_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the NOC Portal (Host, Package, Area, Customer, Connections, Payments, Store, Expense, Support).</small>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Role</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Role
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4" style="max-height: 60vh; overflow-y: auto;">
        <form id="editRoleForm">
            <input type="hidden" name="role_id" id="edit_role_id">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Role Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="role_name" id="edit_role_name" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
            
            <h6 class="text-slate-700 fw-bold mb-3" style="font-size: 14px;">Permissions</h6>
            
            <div class="permission-grid">
                <?php foreach($modules as $module_name => $perms): ?>
                <?php $mod_key = strtolower(str_replace(' ', '_', $module_name)); ?>
                <div class="permission-card">
                    <h6><?= htmlspecialchars($module_name) ?></h6>
                    <?php foreach($perms as $perm_key => $perm_label): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[<?= $mod_key ?>_<?= $perm_key ?>]" value="1" id="edit_<?= $mod_key ?>_<?= $perm_key ?>" data-key="<?= $mod_key ?>_<?= $perm_key ?>">
                        <label class="form-check-label" for="edit_<?= $mod_key ?>_<?= $perm_key ?>"><?= htmlspecialchars($perm_label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Transmission Portal Full Access -->
                <div class="permission-card" style="border-color: #0dcaf0;">
                    <h6 class="text-info" style="color: #0dcaf0 !important;"><i class="bi bi-broadcast"></i> Transmission Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[transmission_full_access]" value="1" id="edit_transmission_full_access" data-key="transmission_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_transmission_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Transmission Portal.</small>
                </div>

                <!-- Owner Portal Full Access -->
                <div class="permission-card" style="border-color: #ef4444;">
                    <h6 class="text-danger" style="color: #ef4444 !important;"><i class="bi bi-person-workspace"></i> Owner Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[owner_full_access]" value="1" id="edit_owner_full_access" data-key="owner_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_owner_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Owner Portal.</small>
                </div>

                <!-- Manager Portal Full Access -->
                <div class="permission-card" style="border-color: #f59e0b;">
                    <h6 class="text-warning" style="color: #f59e0b !important;"><i class="bi bi-briefcase"></i> Manager Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[manager_full_access]" value="1" id="edit_manager_full_access" data-key="manager_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_manager_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Manager Portal.</small>
                </div>
                
                <!-- Billing Portal Full Access -->
                <div class="permission-card" style="border-color: #10b981;">
                    <h6 class="text-success" style="color: #10b981 !important;"><i class="bi bi-receipt"></i> Billing Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[billing_full_access]" value="1" id="edit_billing_full_access" data-key="billing_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_billing_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate Billing Portal.</small>
                </div>
                
                <!-- TxBill Portal Full Access -->
                <div class="permission-card" style="border-color: #8b5cf6;">
                    <h6 style="color: #8b5cf6 !important;"><i class="bi bi-intersect"></i> TxBill Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[txbill_full_access]" value="1" id="edit_txbill_full_access" data-key="txbill_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_txbill_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the separate TxBill Portal.</small>
                </div>

                <!-- NOC Portal Full Access -->
                <div class="permission-card" style="border-color: #6366f1;">
                    <h6 style="color: #6366f1 !important;"><i class="bi bi-hdd-network"></i> NOC Portal</h6>
                    <div class="form-check mb-0 mt-3">
                        <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[noc_full_access]" value="1" id="edit_noc_full_access" data-key="noc_full_access">
                        <label class="form-check-label fw-bold text-slate-700" for="edit_noc_full_access">Full Access</label>
                    </div>
                    <small class="text-slate-400 d-block mt-2" style="font-size: 10px; line-height: 1.2;">Grants complete access to the NOC Portal (Host, Package, Area, Customer, Connections, Payments, Store, Expense, Support).</small>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Role</button>
      </div>
        </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#roleTable')) {
        $('#roleTable').DataTable().destroy();
    }
    
    var table = $('#roleTable').DataTable({
        "order": [] 
    });
    
    // Bind custom search
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Handle Add Role Form Submission
    $('#addRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'ajax/add_role.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#addRoleModal').modal('hide');
                    $('#addRoleForm')[0].reset();
                    setTimeout(function(){ location.reload(); }, 1000); 
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while communicating with the server.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });

    // Handle Edit Button Click
    $('.btn-edit-role').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var permissions = $(this).data('permissions');

        $('#edit_role_id').val(id);
        $('#edit_role_name').val(name);
        
        // Reset all checkboxes first
        $('.edit-perm-checkbox').prop('checked', false);

        // Parse JSON permissions and check boxes
        if (permissions) {
            if (typeof permissions === 'string') {
                try {
                    permissions = JSON.parse(permissions);
                } catch (e) {
                    permissions = {};
                }
            }
            
            $('.edit-perm-checkbox').each(function() {
                var key = $(this).data('key');
                if (permissions[key] == 1 || permissions[key] == "1" || permissions[key] === true) {
                    $(this).prop('checked', true);
                }
            });
        }

        $('#editRoleModal').modal('show');
    });

    // Handle Edit Role Form Submission
    $('#editRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'ajax/edit_role.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#editRoleModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1000); 
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while communicating with the server.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete Role
    $(document).on('click', '.btn-delete-role', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Role?',
            text: "You won't be able to revert this! Users with this role might lose access.",
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
                    url: 'ajax/delete_role.php',
                    type: 'POST',
                    data: { role_id: id },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            showToast('success', response.message);
                            setTimeout(function(){ location.reload(); }, 800);
                        } else {
                            showToast('error', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'An error occurred while deleting.');
                    }
                });
            }
        });
    });
});
</script>
