<?php
require_once '../includes/db.php';
$base_path = '../';
$is_manager = true;
$active_menu = 'salary';
$page_title = 'Salary Management';
$page_subtitle = 'Generate month-wise salaries for employees';
include '../includes/header.php';
include 'includes/sidebar.php';

// Fetch employees
$emp_query = "SELECT * FROM employee_tbl ORDER BY id DESC";
$emp_result = $conn->query($emp_query);
?>



<!-- Employee Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="salaryTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Employee Name</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Designation</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Joining Date</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Default Salary</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($emp_result && $emp_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $emp_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($row['profile_image'])): ?>
                                        <img src="<?= $base_path . htmlspecialchars($row['profile_image']) ?>" alt="Profile" class="rounded-circle object-fit-cover" style="width: 32px; height: 32px; border: 2px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 12px; border: 2px solid #e2e8f0;">
                                            <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-slate-700" style="font-size: 13px;"><?= htmlspecialchars($row['full_name']) ?></div>
                                        <div class="text-slate-400" style="font-size: 11px;"><?= htmlspecialchars($row['phone']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600" style="font-size: 12px; padding: 12px 16px;">
                                <span class="badge bg-light text-slate-600 border border-slate-200 rounded-pill px-2 py-1" style="font-size: 10px; font-weight: 500;">
                                    <?= htmlspecialchars($row['designation'] ?: 'N/A') ?>
                                </span>
                            </td>
                            <td class="text-slate-500" style="font-size: 12px; padding: 12px 16px;"><?= $row['joining_date'] ? date('d M, Y', strtotime($row['joining_date'])) : 'N/A' ?></td>
                            <td class="text-slate-700 fw-bold" style="font-size: 13px; padding: 12px 16px;">
                                ৳<?= number_format($row['salary'], 2) ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <button class="btn btn-sm bg-primary text-white shadow-sm px-3 py-1 btn-salary-ledger" style="font-size: 11px; font-weight: 500; border-radius: 6px;" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-name="<?= htmlspecialchars($row['full_name']) ?>" 
                                    data-salary="<?= $row['salary'] ?>"
                                    data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                    data-email="<?= htmlspecialchars($row['email'] ?? 'N/A') ?>"
                                    data-address="<?= htmlspecialchars($row['present_address'] ?? 'N/A') ?>"
                                    data-designation="<?= htmlspecialchars($row['designation'] ?? 'N/A') ?>"
                                    data-joining="<?= $row['joining_date'] ? date('d M, Y', strtotime($row['joining_date'])) : 'N/A' ?>"
                                    data-image="<?= !empty($row['profile_image']) ? $base_path . htmlspecialchars($row['profile_image']) : '' ?>"
                                    >
                                    <i class="bi bi-journal-text me-1"></i> Salary Ledger
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Salary Ledger Modal -->
<div class="modal fade" id="salaryLedgerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-0 py-4 px-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border-radius: 12px 12px 0 0;">
        <h5 class="modal-title d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; color: #1e293b;"><i class="bi bi-wallet2" style="font-size: 18px;"></i></div> Salary Ledger
        </h5>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <form id="salaryLedgerForm">
          <div class="modal-body px-4 py-4" style="background-color: #f8fafc;">
              <input type="hidden" name="employee_id" id="ledger_employee_id">
              
              <!-- Employee Profile Card -->
              <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: white;">
                  <div class="card-body p-4">
                      <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
                          <div id="ledger_employee_avatar_wrapper" class="flex-shrink-0">
                              <img src="" id="ledger_employee_image" class="rounded-circle object-fit-cover shadow-sm d-none" style="width: 80px; height: 80px; border: 3px solid #e2e8f0;">
                              <div id="ledger_employee_initials" class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm d-none" style="width: 80px; height: 80px; font-size: 28px; border: 3px solid #e2e8f0;">
                              </div>
                          </div>
                          <div class="flex-grow-1 text-center text-md-start w-100">
                              <h5 id="ledger_employee_name" class="fw-bold text-slate-800 mb-1" style="font-size: 18px;"></h5>
                              <div id="ledger_employee_designation" class="text-primary mb-3" style="font-size: 13px; font-weight: 500;"></div>
                              
                              <div class="row g-3">
                                  <div class="col-sm-6">
                                      <div class="d-flex align-items-center gap-2 text-slate-600" style="font-size: 12px;">
                                          <i class="bi bi-telephone-fill text-slate-400"></i>
                                          <span id="ledger_employee_phone"></span>
                                      </div>
                                  </div>
                                  <div class="col-sm-6">
                                      <div class="d-flex align-items-center gap-2 text-slate-600" style="font-size: 12px;">
                                          <i class="bi bi-envelope-fill text-slate-400"></i>
                                          <span id="ledger_employee_email"></span>
                                      </div>
                                  </div>
                                  <div class="col-sm-6">
                                      <div class="d-flex align-items-center gap-2 text-slate-600" style="font-size: 12px;">
                                          <i class="bi bi-calendar-event-fill text-slate-400"></i>
                                          <span>Joined: <span id="ledger_employee_joining" class="fw-medium"></span></span>
                                      </div>
                                  </div>
                                  <div class="col-sm-6">
                                      <div class="d-flex align-items-center gap-2 text-slate-600" style="font-size: 12px;">
                                          <i class="bi bi-geo-alt-fill text-slate-400"></i>
                                          <span id="ledger_employee_address" class="text-truncate" style="max-width: 150px;"></span>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <div class="row g-3">
                  <div class="col-md-6">
                      <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Salary Month *</label>
                      <input type="month" name="salary_month" class="form-control border-slate-200 shadow-none py-2" style="font-size: 13px;" required value="<?= date('Y-m') ?>">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Amount (৳) *</label>
                      <input type="number" name="amount" id="ledger_amount" class="form-control border-slate-200 shadow-none py-2" style="font-size: 13px;" required step="0.01" min="0">
                  </div>
              </div>

              <hr class="my-4 border-slate-200">
              <h6 class="text-slate-700 fw-bold mb-3" style="font-size: 14px;">Salary History</h6>
              <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                  <table class="table table-sm align-middle mb-0" id="salaryHistoryTable">
                      <thead style="background-color: #F8FAFC; position: sticky; top: 0; z-index: 1;">
                          <tr>
                              <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 12px;">Month</th>
                              <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 12px;">Amount</th>
                              <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 12px;">Generated On</th>
                              <th class="border-0 text-slate-500" style="font-size: 11px; padding: 10px 12px;">Status</th>
                              <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 10px 12px;">Action</th>
                          </tr>
                      </thead>
                      <tbody id="salaryHistoryBody" style="border-top: 1px solid var(--border-slate-200);">
                          <tr><td colspan="5" class="text-center text-slate-400 py-3" style="font-size:12px;">Loading history...</td></tr>
                      </tbody>
                  </table>
              </div>

          </div>
          <div class="modal-footer border-top border-slate-100 px-4 py-3 bg-light" style="border-radius: 0 0 12px 12px;">
              <button type="button" class="btn text-slate-600 border border-slate-200 bg-white shadow-sm" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; padding: 8px 16px;">Cancel</button>
              <button type="submit" class="btn btn-primary shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500; border-radius: 8px;"><i class="bi bi-check2-circle"></i> Generate</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Pay Salary Modal -->
<div class="modal fade" id="paySalaryModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 fw-bold" style="font-size: 16px;">Pay Salary</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <form id="paySalaryForm" enctype="multipart/form-data">
          <div class="modal-body px-4 py-4">
              <input type="hidden" name="salary_id" id="pay_salary_id">
              <div class="mb-3">
                  <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Upload Document/Receipt</label>
                  <input type="file" name="document" class="form-control border-slate-200 shadow-none py-2" style="font-size: 13px;" accept=".jpg,.jpeg,.png,.pdf">
                  <div class="form-text" style="font-size: 11px;">Optional: Upload a receipt or transaction screenshot (JPG, PNG, PDF)</div>
              </div>
          </div>
          <div class="modal-footer border-top border-slate-100 px-4 py-3 bg-light" style="border-radius: 0 0 12px 12px;">
              <button type="button" class="btn text-slate-600 border border-slate-200 bg-white shadow-sm" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; padding: 8px 16px;">Cancel</button>
              <button type="submit" class="btn btn-success shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500; border-radius: 8px;">Submit Payment</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#salaryTable').DataTable({
        // Uses global configuration from main.js
    });

    // Open Salary Ledger Modal
    $('.btn-salary-ledger').on('click', function() {
        var empId = $(this).data('id');
        var empName = $(this).data('name');
        var empSalary = $(this).data('salary');
        var empPhone = $(this).data('phone');
        var empEmail = $(this).data('email');
        var empAddress = $(this).data('address');
        var empDesignation = $(this).data('designation');
        var empJoining = $(this).data('joining');
        var empImage = $(this).data('image');

        $('#ledger_employee_id').val(empId);
        $('#ledger_employee_name').text(empName);
        $('#ledger_amount').val(empSalary);
        
        $('#ledger_employee_phone').text(empPhone);
        $('#ledger_employee_email').text(empEmail);
        $('#ledger_employee_address').text(empAddress);
        $('#ledger_employee_designation').text(empDesignation);
        $('#ledger_employee_joining').text(empJoining);
        
        // Handle avatar
        if (empImage) {
            $('#ledger_employee_image').attr('src', empImage).removeClass('d-none');
            $('#ledger_employee_initials').addClass('d-none');
        } else {
            var initial = empName ? empName.charAt(0).toUpperCase() : 'U';
            $('#ledger_employee_image').addClass('d-none');
            $('#ledger_employee_initials').text(initial).removeClass('d-none');
        }
        
        // Load History
        $('#salaryHistoryBody').html('<tr><td colspan="5" class="text-center text-slate-400 py-3" style="font-size:12px;"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>');
        
        $.get('../ajax/get_salary_history.php', { employee_id: empId }, function(res) {
            var html = '';
            if (res.success && res.data.length > 0) {
                res.data.forEach(function(item) {
                    var d = new Date(item.created_at);
                    var dateStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    html += '<tr>';
                    html += '<td class="text-slate-600 fw-bold" style="font-size:12px; padding: 10px 12px;">' + item.salary_month + '</td>';
                    html += '<td class="text-success fw-bold" style="font-size:12px; padding: 10px 12px;">৳' + parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>';
                    html += '<td class="text-slate-400" style="font-size:11px; padding: 10px 12px;">' + dateStr + '</td>';
                    
                    var statusBadge = item.status === 'Paid' 
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 10px;">Paid</span>'
                        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1" style="font-size: 10px;">Unpaid</span>';
                    html += '<td style="padding: 10px 12px;">' + statusBadge + '</td>';
                    
                    html += '<td class="text-end" style="padding: 10px 12px;">';
                    if (item.status === 'Paid') {
                        if (item.document_path) {
                            html += '<a href="../' + item.document_path + '" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 11px; padding: 4px 8px;"><i class="bi bi-eye"></i> View Doc</a>';
                        }
                    } else {
                        html += '<button type="button" class="btn btn-sm btn-success btn-pay-salary" data-id="' + item.id + '" style="font-size: 11px; padding: 4px 8px;"><i class="bi bi-cash"></i> Pay</button>';
                    }
                    html += '</td>';
                    
                    html += '</tr>';
                });
            } else {
                html = '<tr><td colspan="5" class="text-center text-slate-400 py-4" style="font-size:12px;">No salary history found for this employee.</td></tr>';
            }
            $('#salaryHistoryBody').html(html);
        }, 'json').fail(function() {
            $('#salaryHistoryBody').html('<tr><td colspan="5" class="text-center text-danger py-4" style="font-size:12px;">Failed to load history.</td></tr>');
        });

        $('#salaryLedgerModal').modal('show');
    });

    // Submit Salary Form
    $('#salaryLedgerForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/generate_salary.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#salaryLedgerModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Server connection failed', 'error');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Pay Salary Button Click
    $(document).on('click', '.btn-pay-salary', function() {
        var salaryId = $(this).data('id');
        $('#pay_salary_id').val(salaryId);
        $('#paySalaryForm')[0].reset();
        $('#paySalaryModal').modal('show');
    });

    // Submit Pay Salary Form
    $('#paySalaryForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...').prop('disabled', true);
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '../ajax/pay_salary.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#paySalaryModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload history for this employee
                        var empId = $('#ledger_employee_id').val();
                        if (empId) {
                            $('.btn-salary-ledger[data-id="'+empId+'"]').trigger('click');
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Something went wrong', 'error');
                }
                $btn.html(originalText).prop('disabled', false);
            },
            error: function() {
                Swal.fire('Error', 'Server connection failed', 'error');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
