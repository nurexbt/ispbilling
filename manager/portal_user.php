<?php
require_once '../includes/db.php';
$base_path = '../';
$is_manager = true;
$active_menu = 'portal_user';
$page_title = 'Portal Users';
$page_subtitle = 'Manage system access and authentication';
include '../includes/header.php';


include 'includes/sidebar.php';

// Fetch Portal Users from DB
$users_query = "SELECT pu.*, e.full_name, e.designation, r.role_name 
                FROM portal_user_tbl pu 
                LEFT JOIN employee_tbl e ON pu.employee_id = e.id 
                LEFT JOIN role_tbl r ON pu.role_id = r.id 
                ORDER BY pu.id DESC";
$users_result = $conn->query($users_query);

// Fetch Employees for dropdown (excluding those who already have a portal account)
$employees_list = [];
$employees_query = "SELECT id, full_name, designation FROM employee_tbl WHERE id NOT IN (SELECT employee_id FROM portal_user_tbl WHERE employee_id IS NOT NULL) ORDER BY full_name ASC";
$employees_result = $conn->query($employees_query);
if ($employees_result && $employees_result->num_rows > 0) {
    while($row = $employees_result->fetch_assoc()) {
        $employees_list[] = $row;
    }
}

// Fetch Roles for dropdown
$roles_list = [];
$roles_query = "SELECT id, role_name FROM role_tbl ORDER BY role_name ASC";
$roles_result = $conn->query($roles_query);
if ($roles_result && $roles_result->num_rows > 0) {
    while($row = $roles_result->fetch_assoc()) {
        $roles_list[] = $row;
    }
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search portal users...">
            </div>
            <div>
                <?php if (has_permission('portal_user_add')): ?>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal" style="font-weight: 600;">
                    <i class="bi bi-person-plus-fill"></i> Add Portal User
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <table id="userTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Employee</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Username</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Role</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Created At</th>
                        <?php if (has_permission('portal_user_edit') || has_permission('portal_user_delete')): ?>
<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
<?php endif; ?>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900 mb-1" style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($row['full_name']) ?: '<span class="text-slate-400">Unlinked User</span>' ?></div>
                                <div class="text-slate-500" style="font-size: 11px;"><?= htmlspecialchars($row['designation']) ?></div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-slate-700 fw-bold" style="font-size: 12px;"><?= htmlspecialchars($row['username']) ?></div>
                                    <?php if ($row['is_active'] == 1): ?>
                                        <div class="status-pill online" style="padding: 2px 6px; font-size: 9px;">Active</div>
                                    <?php else: ?>
                                        <div class="status-pill offline" style="padding: 2px 6px; font-size: 9px;">Disabled</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-3 py-1" style="font-weight: 500;"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($row['role_name']) ?: 'No Role' ?></span>
                            </td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['created_at']) ?></td>
                            <?php if (has_permission('portal_user_edit') || has_permission('portal_user_delete')): ?>
<td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <?php if (has_permission('portal_user_edit')): ?>
                                    <button class="btn-action btn-action-green btn-edit-user" style="width: 24px; height: 24px;" title="Edit"
                                            data-id="<?= $row['id'] ?>"
                                            data-employee="<?= htmlspecialchars($row['full_name']) ?>"
                                            data-username="<?= htmlspecialchars($row['username']) ?>"
                                            data-role="<?= $row['role_id'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <?php if ($row['is_active'] == 1): ?>
                                        <button class="btn-action btn-action-orange btn-toggle-status" style="width: 24px; height: 24px;" title="Disable Login" data-id="<?= $row['id'] ?>"><i class="bi bi-person-x"></i></button>
                                    <?php else: ?>
                                        <button class="btn-action btn-action-green btn-toggle-status" style="width: 24px; height: 24px;" title="Enable Login" data-id="<?= $row['id'] ?>"><i class="bi bi-person-check"></i></button>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (has_permission('portal_user_delete')): ?>
                                    <button class="btn-action btn-action-red btn-delete-user" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-person-plus-fill"></i></div> Add Portal User
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addUserForm">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Employee</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-person-badge"></i></span>
                    <select name="employee_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Choose Employee...</option>
                        <?php foreach($employees_list as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['designation']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Username</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-at"></i></span>
                    <input type="text" name="username" class="form-control border-slate-200 shadow-none py-2" placeholder="login_name" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Password</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" class="form-control border-slate-200 shadow-none py-2" placeholder="Secure password" required>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Assign Role</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-shield-check"></i></span>
                    <select name="role_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Select a Role...</option>
                        <?php foreach($roles_list as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Create Account</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Portal User
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editUserForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Employee</label>
                <input type="text" id="edit_employee_name" class="form-control form-control-sm border-slate-200 shadow-none py-2 bg-light" disabled>
                <small class="text-slate-400" style="font-size: 11px;">Employee mapping cannot be changed. Delete and recreate if needed.</small>
            </div>

            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Username</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-at"></i></span>
                    <input type="text" name="username" id="edit_username" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Reset Password</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" class="form-control border-slate-200 shadow-none py-2" placeholder="Leave blank to keep current password">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Assign Role</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-shield-check"></i></span>
                    <select name="role_id" id="edit_role_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Select a Role...</option>
                        <?php foreach($roles_list as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Account</button>
      </div>
        </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#userTable')) {
        $('#userTable').DataTable().destroy();
    }
    
    var table = $('#userTable').DataTable({
        "order": [] 
    });
    
    // Bind custom search
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Handle Add Form Submission
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_portal_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#addUserModal').modal('hide');
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
    $('.btn-edit-user').on('click', function() {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_employee_name').val($(this).data('employee'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_role_id').val($(this).data('role'));
        $('#editUserForm input[name="password"]').val(''); // Clear password field
        $('#editUserModal').modal('show');
    });

    // Handle Edit Form Submission
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/edit_portal_user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#editUserModal').modal('hide');
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

    // Toggle Login Status
    $(document).on('click', '.btn-toggle-status', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        var $icon = $btn.find('i');
        
        $btn.prop('disabled', true);
        var origIconClass = $icon.attr('class');
        $icon.attr('class', 'spinner-border spinner-border-sm');
        
        $.ajax({
            url: '../ajax/toggle_portal_user_status.php',
            type: 'POST',
            data: { user_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    showToast('error', response.message);
                    $btn.prop('disabled', false);
                    $icon.attr('class', origIconClass);
                }
            },
            error: function() {
                showToast('error', 'An error occurred.');
                $btn.prop('disabled', false);
                $icon.attr('class', origIconClass);
            }
        });
    });

    // Delete User
    $(document).on('click', '.btn-delete-user', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Portal User?',
            text: "You won't be able to revert this! The employee record will remain intact.",
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
                    url: '../ajax/delete_portal_user.php',
                    type: 'POST',
                    data: { user_id: id },
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
