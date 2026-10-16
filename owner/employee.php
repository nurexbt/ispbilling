<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;
$active_menu = 'employee';
$page_title = 'Employee Management';
$page_subtitle = 'Manage staff members, salaries, and HR records';
include '../includes/header.php';
include 'includes/sidebar.php';

// Fetch Employees
$emp_query = "SELECT * FROM employee_tbl ORDER BY id DESC";
$emp_result = $conn->query($emp_query);
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchEmployee" class="toolbar-input" placeholder="Search employees...">
            </div>
            <div>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" style="font-weight: 600;">
                    <i class="bi bi-person-plus-fill"></i> Add Employee
                </button>
            </div>
        </div>

        <!-- Table -->
        <table id="employeeTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Employee</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Contact</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Role / Date</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Salary</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody style="border-top: 1px solid var(--border-slate-200);">
                <?php if ($emp_result && $emp_result->num_rows > 0): ?>
                    <?php $i = 1; while ($row = $emp_result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                        <td style="padding: 12px 16px;">
                            <div class="d-flex align-items-center gap-3">
                                <?php if(!empty($row['profile_image']) && file_exists($row['profile_image'])): ?>
                                    <img src="<?= htmlspecialchars($row['profile_image']) ?>" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm border" style="width: 36px; height: 36px;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center shadow-sm border border-primary-subtle" style="width: 36px; height: 36px; font-weight: 600; font-size: 14px;">
                                        <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex flex-column">
                                    <span class="text-slate-900" style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($row['full_name']) ?></span>
                                    <?php if(!empty($row['nid_image'])): ?>
                                        <a href="<?= htmlspecialchars($row['nid_image']) ?>" target="_blank" class="text-primary text-decoration-none" style="font-size: 10px;"><i class="bi bi-person-vcard"></i> View NID</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div class="d-flex flex-column gap-1">
                                <span class="text-slate-700 d-flex align-items-center gap-1" style="font-size: 11.5px;"><i class="bi bi-telephone-fill text-slate-400"></i> <?= htmlspecialchars($row['phone']) ?></span>
                                <?php if(!empty($row['email'])): ?>
                                    <span class="text-slate-500 d-flex align-items-center gap-1" style="font-size: 11px;"><i class="bi bi-envelope-fill text-slate-400"></i> <?= htmlspecialchars($row['email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div class="d-flex flex-column gap-1">
                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-1" style="width: fit-content; font-size: 10px; font-weight: 600;"><?= htmlspecialchars($row['designation']) ?></span>
                                <span class="text-slate-500 d-flex align-items-center gap-1 mt-1" style="font-size: 11px;"><i class="bi bi-calendar-event"></i> Joined <?= date('M d, Y', strtotime($row['joining_date'])) ?></span>
                            </div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="text-success fw-bold" style="font-size: 13px;">৳<?= number_format($row['salary'], 2) ?></span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <div class="d-flex gap-1">
                                <button class="btn-action btn-action-green btn-edit-emp" style="width: 24px; height: 24px;" title="Edit"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['full_name']) ?>"
                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                        data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                        data-designation="<?= htmlspecialchars($row['designation']) ?>"
                                        data-salary="<?= htmlspecialchars($row['salary']) ?>"
                                        data-joined="<?= htmlspecialchars($row['joining_date']) ?>"
                                        data-present="<?= htmlspecialchars($row['present_address']) ?>"
                                        data-permanent="<?= htmlspecialchars($row['permanent_address']) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn-action btn-action-red btn-delete-emp" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>">
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
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-person-badge-fill"></i></div> Add New Employee
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addEmployeeForm" enctype="multipart/form-data">
            
            <div class="row g-3">
                <!-- Column 1 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Full Name *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-person-fill"></i></span>
                            <input type="text" name="full_name" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Email Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" name="email" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Phone Number *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-info px-3"><i class="bi bi-telephone-fill"></i></span>
                            <input type="text" name="phone" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Designation</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-warning px-3"><i class="bi bi-briefcase-fill"></i></span>
                            <input type="text" name="designation" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Present Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-geo-alt-fill"></i></span>
                            <input type="text" name="present_address" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                </div>

                <!-- Column 2 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Salary *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" step="0.01" name="salary" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Joining Date *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-calendar-date-fill"></i></span>
                            <input type="date" name="joining_date" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Permanent Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-geo-fill"></i></span>
                            <input type="text" name="permanent_address" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Profile Image</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-image-fill"></i></span>
                            <input type="file" name="profile_image" class="form-control border-slate-200 shadow-none py-2" accept="image/*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">NID Image</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-purple px-3"><i class="bi bi-person-vcard-fill"></i></span>
                            <input type="file" name="nid_image" class="form-control border-slate-200 shadow-none py-2" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-4 gap-1 d-flex align-items-center" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Employee</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-pencil-square"></i></div> Edit Employee
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editEmployeeForm" enctype="multipart/form-data">
            <input type="hidden" name="employee_id" id="edit_emp_id">
            
            <div class="row g-3">
                <!-- Column 1 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Full Name *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-person-fill"></i></span>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Email Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" name="email" id="edit_email" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Phone Number *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-info px-3"><i class="bi bi-telephone-fill"></i></span>
                            <input type="text" name="phone" id="edit_phone" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Designation</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-warning px-3"><i class="bi bi-briefcase-fill"></i></span>
                            <input type="text" name="designation" id="edit_designation" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Present Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-geo-alt-fill"></i></span>
                            <input type="text" name="present_address" id="edit_present_address" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                </div>

                <!-- Column 2 -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Salary *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" step="0.01" name="salary" id="edit_salary" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Joining Date *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-calendar-date-fill"></i></span>
                            <input type="date" name="joining_date" id="edit_joining_date" class="form-control border-slate-200 shadow-none py-2" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Permanent Address</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-geo-fill"></i></span>
                            <input type="text" name="permanent_address" id="edit_permanent_address" class="form-control border-slate-200 shadow-none py-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Update Profile Image <small class="text-slate-400">(optional)</small></label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-image-fill"></i></span>
                            <input type="file" name="profile_image" class="form-control border-slate-200 shadow-none py-2" accept="image/*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Update NID Image <small class="text-slate-400">(optional)</small></label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-purple px-3"><i class="bi bi-person-vcard-fill"></i></span>
                            <input type="file" name="nid_image" class="form-control border-slate-200 shadow-none py-2" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-4 gap-1 d-flex align-items-center" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Update Employee</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#employeeTable').DataTable();
    
    // Custom Toolbar Search Binding
    $('#searchEmployee').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Function to handle AJAX form submissions with FormData (for files)
    function handleFormSubmit(formSelector, url, modalSelector) {
        $(formSelector).on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            var originalText = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Processing...');
            $btn.prop('disabled', true);
            
            var formData = new FormData(this);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
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
    handleFormSubmit('#addEmployeeForm', '../ajax/add_employee.php', '#addEmployeeModal');
    handleFormSubmit('#editEmployeeForm', '../ajax/edit_employee.php', '#editEmployeeModal');

    // Populate Edit Modal
    $(document).on('click', '.btn-edit-emp', function() {
        $('#edit_emp_id').val($(this).data('id'));
        $('#edit_full_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_phone').val($(this).data('phone'));
        $('#edit_designation').val($(this).data('designation'));
        $('#edit_salary').val($(this).data('salary'));
        $('#edit_joining_date').val($(this).data('joined'));
        $('#edit_present_address').val($(this).data('present'));
        $('#edit_permanent_address').val($(this).data('permanent'));
        $('#editEmployeeModal').modal('show');
    });

    // Delete Employee Handler
    $(document).on('click', '.btn-delete-emp', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Employee?',
            text: "You won't be able to revert this! This will remove their record and uploaded files.",
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
                    url: '../ajax/delete_employee.php',
                    type: 'POST',
                    data: { employee_id: id },
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
