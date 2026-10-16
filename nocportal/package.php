<?php
require_once '../includes/db.php';
$base_path = '../';
$is_noc = true;
$active_menu = 'package';
$page_title = 'Package Management';
$page_subtitle = 'Sync PPP profiles and manage billing packages';
include '../includes/header.php';

if (!has_permission('package_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view packages.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Fetch Packages
$pkg_query = "SELECT p.*, h.device_name FROM package_tbl p JOIN host_tbl h ON p.host_id = h.id ORDER BY p.id DESC";
$pkg_result = $conn->query($pkg_query);

// Fetch Active Hosts for Dropdown
$hosts_query = "SELECT id, device_name FROM host_tbl WHERE is_disabled = 0";
$hosts_result = $conn->query($hosts_query);
$hosts_list = [];
if($hosts_result) {
    while($row = $hosts_result->fetch_assoc()){
        $hosts_list[] = $row;
    }
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchPackage" class="toolbar-input" placeholder="Search packages...">
            </div>
            <div>
                <?php if (has_permission('package_add')): ?>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addPackageModal" style="font-weight: 600;">
                    <i class="bi bi-cloud-arrow-down-fill"></i> Add & Sync Package
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <table id="packageTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Given Name</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Original PPP Profile</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Router Host</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Price</th>
                    <?php if (has_permission('package_edit') || has_permission('package_delete')): ?>
<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 100px;">Action</th>
<?php endif; ?>
                </tr>
            </thead>
            <tbody style="border-top: 1px solid var(--border-slate-200);">
                <?php if ($pkg_result && $pkg_result->num_rows > 0): ?>
                    <?php $i = 1; while ($row = $pkg_result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                        <td style="padding: 12px 16px;">
                            <div class="text-slate-900" style="font-size: 13px; font-weight: 600;"><i class="bi bi-box-fill text-primary me-2"></i><?= htmlspecialchars($row['given_name']) ?></div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="badge bg-light text-slate-600 border border-slate-200 rounded-pill px-3 py-1" style="font-weight: 500; font-family: monospace; font-size: 11px;"><?= htmlspecialchars($row['original_profile']) ?></span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="badge bg-light text-secondary border border-secondary-subtle rounded-pill px-3 py-1" style="font-weight: 500; font-size: 11px;"><i class="bi bi-router me-1"></i><?= htmlspecialchars($row['device_name']) ?></span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="text-success fw-bold" style="font-size: 13px;">৳<?= number_format($row['price'], 2) ?></span>
                        </td>
                        <?php if (has_permission('package_edit') || has_permission('package_delete')): ?>
<td style="padding: 12px 16px;">
                            <div class="d-flex gap-1">
                                <?php if (has_permission('package_edit')): ?>
                                <button class="btn-action btn-action-green btn-edit-pkg" style="width: 24px; height: 24px;" title="Edit"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['given_name']) ?>"
                                        data-price="<?= htmlspecialchars($row['price']) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (has_permission('package_delete')): ?>
                                <button class="btn-action btn-action-red btn-delete-pkg" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
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

<!-- ================= MODALS ================= -->

<!-- Add & Sync Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-cloud-arrow-down-fill"></i></div> Sync & Add Packages
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4" style="background-color: #f8fafc;">
        
        <!-- Premium Header Area -->
        <div class="mb-4 p-4 rounded-4 position-relative" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd;">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div class="flex-grow-1" style="max-width: 500px;">
                    <label class="form-label text-primary mb-2 d-flex align-items-center gap-2" style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="bi bi-hdd-network"></i> Target Router Connection
                    </label>
                    <div class="d-flex shadow-sm rounded-pill overflow-hidden" style="background-color: #fff; border: 2px solid #fff; box-shadow: 0 4px 6px -1px rgba(56, 189, 248, 0.1), 0 2px 4px -1px rgba(56, 189, 248, 0.06) !important;">
                        <div class="px-3 d-flex align-items-center bg-white text-slate-400">
                            <i class="bi bi-router-fill fs-5"></i>
                        </div>
                        <select id="sync_host_id" name="host_id" class="form-select border-0 shadow-none bg-white py-3 ps-1 pe-4" style="font-weight: 600; font-size: 14px; color: #334155; cursor: pointer;" required>
                            <option value="" disabled selected>Select a router...</option>
                            <?php foreach($hosts_list as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['device_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="btnSyncMikrotik" class="btn border-0 text-white px-4 d-flex align-items-center gap-2" style="background: linear-gradient(to right, #0ea5e9, #2563eb); font-weight: 700; border-radius: 50rem; margin: 4px; transition: all 0.3s; white-space: nowrap;">
                            <i class="bi bi-lightning-charge-fill"></i> Execute Sync
                        </button>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-primary border border-primary-subtle px-3 py-2 rounded-pill shadow-sm" style="font-weight: 600; font-size: 12px;">
                        <i class="bi bi-shield-check me-1"></i> Secure API
                    </span>
                </div>
            </div>
        </div>

        <!-- Dynamic Table Container -->
        <div id="sync_results_container" class="d-none">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-bottom border-slate-100 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="text-slate-800 mb-0 d-flex align-items-center gap-2" style="font-weight: 600; font-size: 14px;">
                        <i class="bi bi-table text-primary"></i> Discovered PPP Profiles
                    </h6>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3" style="font-weight: 600;" id="profile_count">0 Profiles</span>
                </div>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table align-middle mb-0" style="border-collapse: separate; border-spacing: 0;">
                        <thead style="background-color: #f1f5f9; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="border-bottom border-slate-200 text-slate-600 text-center" style="font-size: 11px; padding: 12px 16px; width: 40px;">
                                    <input class="form-check-input" type="checkbox" id="selectAllProfiles" style="cursor: pointer;">
                                </th>
                                <th class="border-bottom border-slate-200 text-slate-600 text-uppercase" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 12px 16px;">Router Profile</th>
                                <th class="border-bottom border-slate-200 text-slate-600 text-uppercase" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 12px 16px;">Software Mapping Name</th>
                                <th class="border-bottom border-slate-200 text-slate-600 text-uppercase" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 12px 16px; width: 220px;">Retail Price</th>
                            </tr>
                        </thead>
                        <tbody id="sync_table_body" class="bg-white">
                            <!-- Rows will be injected here via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-white" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-white border-slate-200 shadow-sm px-4 py-2" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 600;" onclick="location.reload();">Close</button>
        <button type="button" id="btnSaveSelectedProfiles" class="btn btn-primary shadow-sm px-4 py-2 d-none" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
            <i class="bi bi-save me-1"></i> Save Selected
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-pencil-square"></i></div> Edit Package Details
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editPackageForm">
            <input type="hidden" name="package_id" id="edit_pkg_id">
            
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Given Name *</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" name="given_name" id="edit_given_name" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Price *</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3">৳</span>
                    <input type="number" step="0.01" name="price" id="edit_price" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Package</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#packageTable').DataTable();
    $('#searchPackage').on('keyup', function() { table.search(this.value).draw(); });

    // Sync Mikrotik Profiles Logic
    $('#btnSyncMikrotik').on('click', function() {
        var hostId = $('#sync_host_id').val();
        if(!hostId) {
            showToast('error', 'Please select a Router Host first.');
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Syncing...');
        $btn.prop('disabled', true);
        
        $('#sync_results_container').addClass('d-none');
        $('#sync_table_body').empty();

        $.ajax({
            url: '../ajax/get_mikrotik_profiles.php',
            type: 'POST',
            data: { host_id: hostId },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', 'Profiles synced successfully!');
                    var html = '';
                    response.data.forEach(function(profile, index) {
                        html += `
                        <tr>
                            <td class="border-bottom border-slate-100 text-center" style="padding: 8px 12px;">
                                <input class="form-check-input profile-checkbox" type="checkbox" value="${index}" style="cursor: pointer; width: 16px; height: 16px;">
                                <input type="hidden" id="orig_${index}" value="${profile}">
                            </td>
                            <td class="border-bottom border-slate-100" style="padding: 8px 12px;">
                                <div class="d-inline-flex align-items-center gap-1 bg-light border border-slate-200 rounded px-2 py-1 shadow-sm" style="font-family: 'Consolas', monospace; font-size: 12px; font-weight: 600; color: #1e293b;">
                                    <i class="bi bi-hdd-stack text-primary me-1"></i> ${profile}
                                </div>
                            </td>
                            <td class="border-bottom border-slate-100" style="padding: 8px 12px;">
                                <div class="input-group input-group-sm input-group-rounded">
                                    <span class="input-group-text bg-light border-slate-200 text-primary px-2"><i class="bi bi-tag-fill"></i></span>
                                    <input type="text" class="form-control border-slate-200 shadow-none pkg-name" id="name_${index}" placeholder="Portal Name" style="font-weight: 500;">
                                </div>
                            </td>
                            <td class="border-bottom border-slate-100" style="padding: 8px 12px;">
                                <div class="input-group input-group-sm input-group-rounded">
                                    <span class="input-group-text bg-light border-slate-200 text-success px-2 fw-bold">৳</span>
                                    <input type="number" step="0.01" class="form-control border-slate-200 shadow-none pkg-price text-success" id="price_${index}" placeholder="0.00" style="font-weight: 600;">
                                </div>
                            </td>
                        </tr>`;
                    });
                    $('#sync_table_body').html(html);
                    $('#profile_count').text(response.data.length + " Profiles");
                    $('#sync_results_container').removeClass('d-none');
                    $('#btnSaveSelectedProfiles').removeClass('d-none');
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'Server error occurred during sync.');
            },
            complete: function() {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Handle Select All Checkbox
    $(document).on('change', '#selectAllProfiles', function() {
        $('.profile-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Handle Bulk Save
    $('#btnSaveSelectedProfiles').on('click', function() {
        var hostId = $('#sync_host_id').val();
        var selected = [];
        
        $('.profile-checkbox:checked').each(function() {
            var index = $(this).val();
            var originalProfile = $('#orig_' + index).val();
            var givenName = $('#name_' + index).val().trim();
            var price = $('#price_' + index).val().trim();
            
            if(givenName && price) {
                selected.push({
                    original_profile: originalProfile,
                    given_name: givenName,
                    price: price
                });
            }
        });
        
        if(selected.length === 0) {
            showToast('error', 'Please select at least one profile and ensure Given Name and Price are filled.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_package_bulk.php',
            type: 'POST',
            data: {
                host_id: hostId,
                packages: JSON.stringify(selected)
            },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
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

    // Reset form on close
    $('#addPackageModal').on('hidden.bs.modal', function () {
        $('#sync_results_container').addClass('d-none');
        $('#btnSaveSelectedProfiles').addClass('d-none');
        $('#sync_table_body').empty();
        $('#sync_host_id').val('');
    });

    // Handle standard AJAX form submission
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


    handleFormSubmit('#editPackageForm', '../ajax/edit_package.php', '#editPackageModal');

    // Populate Edit Modal
    $(document).on('click', '.btn-edit-pkg', function() {
        $('#edit_pkg_id').val($(this).data('id'));
        $('#edit_given_name').val($(this).data('name'));
        $('#edit_price').val($(this).data('price'));
        $('#editPackageModal').modal('show');
    });

    // Delete Package
    $(document).on('click', '.btn-delete-pkg', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete Package?',
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
                    url: '../ajax/delete_package.php',
                    type: 'POST',
                    data: { package_id: id },
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

});
</script>
