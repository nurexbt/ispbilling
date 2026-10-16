<?php
require_once '../includes/db.php';
$base_path = '../';
$is_noc = true;
$active_menu = 'area'; // Assuming area is added to sidebar, or we just set it
$page_title = 'Area Management';
$page_subtitle = 'Manage service areas and sub-areas';
include '../includes/header.php';

if (!has_permission('area_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view areas.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Fetch Area Data
$areas_query = "SELECT * FROM area_tbl ORDER BY id DESC";
$areas_result = $conn->query($areas_query);

$show_area_action = has_permission('area_edit') || has_permission('area_delete');

// Fetch Areas for the dropdowns
$areas_list = [];
if ($areas_result && $areas_result->num_rows > 0) {
    while($row = $areas_result->fetch_assoc()) {
        $areas_list[] = $row;
    }
}
// reset pointer for the table loop
if ($areas_result) {
    $areas_result->data_seek(0);
}

// Fetch Sub Areas
$subareas_query = "SELECT s.*, a.area_name FROM subarea_tbl s JOIN area_tbl a ON s.area_id = a.id ORDER BY s.id DESC";
$subareas_result = $conn->query($subareas_query);
?>

<div class="container-fluid pt-2">
    <!-- Area/SubArea Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar & Tabs -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <ul class="nav custom-segmented-control border-0" id="areaTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="area-tab" data-bs-toggle="pill" data-bs-target="#area" type="button" role="tab">Areas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="subarea-tab" data-bs-toggle="pill" data-bs-target="#subarea" type="button" role="tab">Sub Areas</button>
                </li>
            </ul>
            
            <div>
                <?php if (has_permission('area_add')): ?>
                <!-- Add Area Button (visible when Area tab is active) -->
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" id="btnAddArea" data-bs-toggle="modal" data-bs-target="#addAreaModal" style="font-weight: 600;">
                    <i class="bi bi-plus-lg"></i> Add Area
                </button>
                <!-- Add SubArea Button (visible when SubArea tab is active) -->
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2 d-none" id="btnAddSubArea" data-bs-toggle="modal" data-bs-target="#addSubAreaModal" style="font-weight: 600;">
                    <i class="bi bi-plus-lg"></i> Add Sub Area
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="areaTabContent">
            <!-- AREA TAB -->
            <div class="tab-pane fade show active" id="area" role="tabpanel">
                <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                    <input type="text" id="searchArea" class="toolbar-input" placeholder="Search areas...">
                </div>
                <table id="areaTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Area Name</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Created At</th>
                            <?php if ($show_area_action): ?>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if ($areas_result && $areas_result->num_rows > 0): ?>
                            <?php $i = 1; while ($row = $areas_result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="text-slate-900" style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($row['area_name']) ?></div>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['created_at']) ?></td>
                                <?php if ($show_area_action): ?>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex gap-1">
                                        <?php if (has_permission('area_edit')): ?>
                                        <button class="btn-action btn-action-green btn-edit-area" style="width: 24px; height: 24px;" title="Edit"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['area_name']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (has_permission('area_delete')): ?>
                                        <button class="btn-action btn-action-red btn-delete-area" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>">
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

            <!-- SUB AREA TAB -->
            <div class="tab-pane fade" id="subarea" role="tabpanel">
                <div class="p-3 border-bottom border-slate-100 d-flex justify-content-between align-items-center">
                    <input type="text" id="searchSubArea" class="toolbar-input" placeholder="Search sub areas...">
                </div>
                <table id="subAreaTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Sub Area Name</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Parent Area</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Created At</th>
                            <?php if ($show_area_action): ?>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if ($subareas_result && $subareas_result->num_rows > 0): ?>
                            <?php $i = 1; while ($row = $subareas_result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="text-slate-900" style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($row['subarea_name']) ?></div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-3" style="font-weight: 500;"><?= htmlspecialchars($row['area_name']) ?></span>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($row['created_at']) ?></td>
                                <?php if ($show_area_action): ?>
                                <td style="padding: 12px 16px;">
                                    <div class="d-flex gap-1">
                                        <?php if (has_permission('area_edit')): ?>
                                        <button class="btn-action btn-action-green btn-edit-subarea" style="width: 24px; height: 24px;" title="Edit"
                                                data-id="<?= $row['id'] ?>"
                                                data-area-id="<?= $row['area_id'] ?>"
                                                data-name="<?= htmlspecialchars($row['subarea_name']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (has_permission('area_delete')): ?>
                                        <button class="btn-action btn-action-red btn-delete-subarea" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>">
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
    </div>
</div>

<!-- Add Area Modal -->
<div class="modal fade" id="addAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pin-map-fill"></i></div> Add Area
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addAreaForm">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Area Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="area_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. North District" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Area</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Area Modal -->
<div class="modal fade" id="editAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Area
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editAreaForm">
            <input type="hidden" name="area_id" id="edit_area_id">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Area Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="area_name" id="edit_area_name" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Area</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Sub Area Modal -->
<div class="modal fade" id="addSubAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-geo-alt-fill"></i></div> Add Sub Area
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addSubAreaForm">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Parent Area</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-pin-map-fill"></i></span>
                    <select name="area_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Select an Area...</option>
                        <?php foreach($areas_list as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Sub Area Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="subarea_name" class="form-control border-slate-200 shadow-none py-2" placeholder="e.g. Block A" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Sub Area</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Sub Area Modal -->
<div class="modal fade" id="editSubAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-pencil-square"></i></div> Edit Sub Area
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editSubAreaForm">
            <input type="hidden" name="subarea_id" id="edit_subarea_id">
            <div class="mb-4">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Parent Area</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-pin-map-fill"></i></span>
                    <select name="area_id" id="edit_subarea_area_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Select an Area...</option>
                        <?php foreach($areas_list as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Sub Area Name</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-fonts"></i></span>
                    <input type="text" name="subarea_name" id="edit_subarea_name" class="form-control border-slate-200 shadow-none py-2" required>
                </div>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Sub Area</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTables
    var areaTable = $('#areaTable').DataTable();
    var subAreaTable = $('#subAreaTable').DataTable();
    
    // Custom Toolbar Search Binding
    $('#searchArea').on('keyup', function() {
        areaTable.search(this.value).draw();
    });
    $('#searchSubArea').on('keyup', function() {
        subAreaTable.search(this.value).draw();
    });

    // Tab Toggle Logic (Switch buttons)
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("data-bs-target"); // #area or #subarea
        if (target === '#area') {
            $('#btnAddArea').removeClass('d-none');
            $('#btnAddSubArea').addClass('d-none');
            areaTable.columns.adjust().draw();
        } else {
            $('#btnAddArea').addClass('d-none');
            $('#btnAddSubArea').removeClass('d-none');
            subAreaTable.columns.adjust().draw();
        }
    });

    // Function to handle AJAX form submissions
    function handleFormSubmit(formSelector, url, modalSelector) {
        $(formSelector).on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            var originalText = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Processing...');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showToast('success', response.message);
                        $(modalSelector).modal('hide');
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
    }

    // Bind Forms
    handleFormSubmit('#addAreaForm', '../ajax/add_area.php', '#addAreaModal');
    handleFormSubmit('#editAreaForm', '../ajax/edit_area.php', '#editAreaModal');
    handleFormSubmit('#addSubAreaForm', '../ajax/add_subarea.php', '#addSubAreaModal');
    handleFormSubmit('#editSubAreaForm', '../ajax/edit_subarea.php', '#editSubAreaModal');

    // Populate Edit Area Modal
    $(document).on('click', '.btn-edit-area', function() {
        $('#edit_area_id').val($(this).data('id'));
        $('#edit_area_name').val($(this).data('name'));
        $('#editAreaModal').modal('show');
    });

    // Populate Edit Sub Area Modal
    $(document).on('click', '.btn-edit-subarea', function() {
        $('#edit_subarea_id').val($(this).data('id'));
        $('#edit_subarea_area_id').val($(this).data('area-id'));
        $('#edit_subarea_name').val($(this).data('name'));
        $('#editSubAreaModal').modal('show');
    });

    // Delete Area / Sub Area Generic Handler
    function handleDelete(selector, url) {
        $(document).on('click', selector, function() {
            var id = $(this).data('id');
            var isArea = selector === '.btn-delete-area';
            
            Swal.fire({
                title: 'Delete ' + (isArea ? 'Area' : 'Sub Area') + '?',
                text: "You won't be able to revert this!" + (isArea ? " WARNING: This will also delete all sub-areas inside it!" : ""),
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
                        url: url,
                        type: 'POST',
                        data: isArea ? { area_id: id } : { subarea_id: id },
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
    }

    handleDelete('.btn-delete-area', '../ajax/delete_area.php');
    handleDelete('.btn-delete-subarea', '../ajax/delete_subarea.php');
});
</script>
