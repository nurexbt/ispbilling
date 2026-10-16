<?php
require_once '../includes/db.php';
$base_path = '../';
$is_manager = true;
$active_menu = 'host';
$page_title = 'Devices';
$page_subtitle = 'Connected syslog sources';
include '../includes/header.php';


include 'includes/sidebar.php';

// Fetch hosts from DB
$hosts_query = "SELECT * FROM host_tbl ORDER BY id DESC";
$hosts_result = $conn->query($hosts_query);
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search devices...">
            </div>
            <div>
                <?php if (has_permission('host_add')): ?>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addDeviceModal" style="font-weight: 600;">
                    <i class="bi bi-plus-lg"></i> Add Device
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <table id="hostTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Device Name</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">IP Address</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">API Port</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Username</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Password</th>
                        <?php if (has_permission('host_edit') || has_permission('host_delete')): ?>
<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
<?php endif; ?>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($hosts_result && $hosts_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $hosts_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900 mb-1" style="font-size: 12px; font-weight: 500;"><?= htmlspecialchars($row['device_name']) ?></div>
                                <?php if (isset($row['is_disabled']) && $row['is_disabled'] == 1): ?>
                                    <div class="status-pill offline device-status" data-id="<?= $row['id'] ?>">Disabled</div>
                                <?php else: ?>
                                    <div class="status-pill pending device-status" data-id="<?= $row['id'] ?>">Checking...</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-600" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['ip_address']) ?></td>
                            <td class="text-slate-600" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['api_port']) ?></td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['api_username']) ?></td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;">
                                <span class="password-mask">........</span>
                                <span class="password-real d-none"><?= htmlspecialchars($row['api_password']) ?></span>
                                <i class="bi bi-eye toggle-password ms-1" style="cursor: pointer;" title="Show Password"></i>
                            </td>
                            <?php if (has_permission('host_edit') || has_permission('host_delete')): ?>
<td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <?php if (has_permission('host_edit')): ?>
                                    <button class="btn-action btn-action-green btn-edit-host" style="width: 24px; height: 24px;" title="Edit"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['device_name']) ?>"
                                            data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                            data-port="<?= htmlspecialchars($row['api_port']) ?>"
                                            data-username="<?= htmlspecialchars($row['api_username']) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (has_permission('host_delete')): ?>
                                    <button class="btn-action btn-action-red btn-delete-host" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                    
                                    <?php if (has_permission('host_edit')): ?>
                                        <?php if (isset($row['is_disabled']) && $row['is_disabled'] == 1): ?>
                                            <button class="btn-action btn-action-green btn-toggle-power" style="width: 24px; height: 24px;" title="Enable" data-id="<?= $row['id'] ?>"><i class="bi bi-power"></i></button>
                                        <?php else: ?>
                                            <button class="btn-action btn-action-orange btn-toggle-power" style="width: 24px; height: 24px;" title="Disable" data-id="<?= $row['id'] ?>"><i class="bi bi-power"></i></button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <button class="btn-action btn-action-blue btn-sync-host" style="width: 24px; height: 24px;" title="Check Connection" data-id="<?= $row['id'] ?>"><i class="bi bi-arrow-repeat"></i></button>
                                </div>
                            </td>
<?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-slate-500 py-4">No devices found. Add your first device!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" id="addDeviceModalLabel" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-router"></i></div> Add Device
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addDeviceForm">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Device Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" name="device_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. Core Router" required>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">IP Address</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-hdd-network-fill"></i></span>
                        <input type="text" name="ip_address" class="form-control border-slate-200 shadow-none py-2" placeholder="160.187.175.26" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Port</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-warning px-3"><i class="bi bi-plug-fill"></i></span>
                        <input type="text" name="api_port" class="form-control border-slate-200 shadow-none py-2" placeholder="8728" value="8728" required>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Username</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="api_username" class="form-control border-slate-200 shadow-none py-2" placeholder="admin" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Password</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-shield-lock-fill"></i></span>
                        <input type="password" name="api_password" class="form-control border-slate-200 shadow-none py-2" placeholder="Password" required>
                    </div>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Device</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" id="editDeviceModalLabel" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Device
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editDeviceForm">
            <input type="hidden" name="host_id" id="edit_host_id">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Device Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" name="device_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. Core Router" required>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">IP Address</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-hdd-network-fill"></i></span>
                        <input type="text" name="ip_address" class="form-control border-slate-200 shadow-none py-2" placeholder="160.187.175.26" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Port</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-warning px-3"><i class="bi bi-plug-fill"></i></span>
                        <input type="text" name="api_port" class="form-control border-slate-200 shadow-none py-2" placeholder="8728" value="8728" required>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Username</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="api_username" class="form-control border-slate-200 shadow-none py-2" placeholder="admin" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">API Password</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-shield-lock-fill"></i></span>
                        <input type="password" name="api_password" class="form-control border-slate-200 shadow-none py-2" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Device</button>
      </div>
        </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#hostTable')) {
        $('#hostTable').DataTable().destroy();
    }
    
    var table = $('#hostTable').DataTable();
    
    // Bind custom search
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Handle Add Device Form Submission
    $('#addDeviceForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_host.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#addDeviceModal').modal('hide');
                    $('#addDeviceForm')[0].reset();
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
    $('.btn-edit-host').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var ip = $(this).data('ip');
        var port = $(this).data('port');
        var username = $(this).data('username');

        $('#edit_host_id').val(id);
        $('#editDeviceForm input[name="device_name"]').val(name);
        $('#editDeviceForm input[name="ip_address"]').val(ip);
        $('#editDeviceForm input[name="api_port"]').val(port);
        $('#editDeviceForm input[name="api_username"]').val(username);
        $('#editDeviceForm input[name="api_password"]').val(''); // Leave blank

        $('#editDeviceModal').modal('show');
    });

    // Handle Edit Device Form Submission
    $('#editDeviceForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/edit_host.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#editDeviceModal').modal('hide');
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

    // Toggle password visibility in table
    $(document).on('click', '.toggle-password', function() {
        var $td = $(this).closest('td');
        var $mask = $td.find('.password-mask');
        var $real = $td.find('.password-real');
        
        if ($mask.hasClass('d-none')) {
            // Currently showing real, switch to mask
            $mask.removeClass('d-none');
            $real.addClass('d-none');
            $(this).removeClass('bi-eye-slash text-primary').addClass('bi-eye');
            $(this).attr('title', 'Show Password');
        } else {
            // Currently showing mask, switch to real
            $mask.addClass('d-none');
            $real.removeClass('d-none');
            $(this).removeClass('bi-eye').addClass('bi-eye-slash text-primary');
            $(this).attr('title', 'Hide Password');
        }
    });

    // Delete Host
    $(document).on('click', '.btn-delete-host', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Device?',
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
                    url: '../ajax/delete_host.php',
                    type: 'POST',
                    data: { host_id: id },
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

    // Toggle Power Status
    $(document).on('click', '.btn-toggle-power', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        var $icon = $btn.find('i');
        
        $btn.prop('disabled', true);
        $icon.removeClass('bi-power').addClass('spinner-border spinner-border-sm');
        
        $.ajax({
            url: '../ajax/toggle_host_status.php',
            type: 'POST',
            data: { host_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    showToast('error', response.message);
                    $btn.prop('disabled', false);
                    $icon.removeClass('spinner-border spinner-border-sm').addClass('bi-power');
                }
            },
            error: function() {
                showToast('error', 'An error occurred.');
                $btn.prop('disabled', false);
                $icon.removeClass('spinner-border spinner-border-sm').addClass('bi-power');
            }
        });
    });

    // Sync Host (Check Connection)
    $(document).on('click', '.btn-sync-host', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        var $icon = $btn.find('i');
        var $statusPill = $('.device-status[data-id="'+id+'"]');
        
        $btn.prop('disabled', true);
        $icon.removeClass('bi-arrow-repeat').addClass('spinner-border spinner-border-sm');
        
        $.ajax({
            url: '../ajax/sync_host.php',
            type: 'POST',
            data: { host_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $statusPill.removeClass('offline').addClass('online').text('Online');
                    showToast('success', response.message);
                } else {
                    $statusPill.removeClass('online').addClass('offline').text(response.message === 'Device is disabled.' ? 'Disabled' : 'Offline');
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while checking connection.');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $icon.removeClass('spinner-border spinner-border-sm').addClass('bi-arrow-repeat');
            }
        });
    });

    // Auto-check connection for active devices on page load
    $('.device-status.pending').each(function() {
        var $statusPill = $(this);
        var id = $statusPill.data('id');
        
        $.ajax({
            url: '../ajax/sync_host.php',
            type: 'POST',
            data: { host_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $statusPill.removeClass('pending').addClass('online').text('Online');
                } else {
                    $statusPill.removeClass('pending').addClass('offline').text('Offline');
                }
            },
            error: function() {
                $statusPill.removeClass('pending').addClass('offline').text('Error');
            }
        });
    });
});
</script>
