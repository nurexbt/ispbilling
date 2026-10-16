<?php
require_once '../includes/db.php';
$base_path = '../';
$active_menu = 'expense';
$page_title = 'Expense';
$page_subtitle = 'Manage company expenses';
include '../includes/header.php';

if (!has_permission('expense_view') && !has_permission('txbill_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view expenses.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';

// Fetch expenses from DB
$user_id = $_SESSION['user_id'] ?? 0;
$expenses_query = "
    SELECT e.*, emp.full_name as added_by_name 
    FROM expense_tbl e
    LEFT JOIN portal_user_tbl pu ON e.created_by = pu.id
    LEFT JOIN employee_tbl emp ON pu.employee_id = emp.id
    WHERE e.created_by = $user_id 
    ORDER BY e.id DESC
";
$expenses_result = $conn->query($expenses_query);

// Fetch documents grouped by expense_id
$docs_query = "SELECT * FROM expense_documents_tbl";
$docs_result = $conn->query($docs_query);
$documents_map = [];
if ($docs_result) {
    while ($doc = $docs_result->fetch_assoc()) {
        $documents_map[$doc['expense_id']][] = $doc['file_path'];
    }
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchExpense" class="toolbar-input" placeholder="Search expenses...">
            </div>
            <div>
                <?php if (has_permission('expense_add') || has_permission('txbill_full_access')): ?>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addExpenseModal" style="font-weight: 600;">
                    <i class="bi bi-plus-lg"></i> Add Expense
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <table id="expenseTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Date</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Category</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Amount</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Note</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Documents</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Added By</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status Progress</th>
                        <?php if (has_permission('expense_edit') || has_permission('expense_delete') || has_permission('txbill_full_access')): ?>
<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
<?php endif; ?>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($expenses_result && $expenses_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $expenses_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td class="text-slate-600" style="font-size: 12px; padding: 12px 16px;"><?= date('d M, y h:ia', strtotime($row['created_at'])) ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-1" style="width: fit-content; font-size: 10px; font-weight: 600;"><?= htmlspecialchars($row['category']) ?></span>
                                </div>
                            </td>
                            <td class="text-success fw-bold" style="font-size: 13px; padding: 12px 16px;">৳<?= number_format($row['amount'], 2) ?></td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px; max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars($row['note']) ?>">
                                <?= htmlspecialchars($row['note']) ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if (!empty($documents_map[$row['id']])): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($documents_map[$row['id']] as $idx => $doc_path): ?>
                                            <a href="<?= htmlspecialchars($doc_path) ?>" target="_blank" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none">
                                                <i class="bi bi-file-earmark-image me-1"></i>Doc <?= $idx + 1 ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400" style="font-size: 11px;">No docs</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-600" style="font-size: 12px; padding: 12px 16px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-weight:600;font-size:10px;">
                                        <?= strtoupper(substr($row['added_by_name'] ?? '?', 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($row['added_by_name'] ?? 'Unknown') ?></span>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="d-flex flex-column gap-2" style="font-size: 10px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:16px;height:16px;"><i class="bi bi-check" style="font-size: 12px;"></i></div>
                                        <div>
                                            <div class="fw-bold text-slate-700">Added</div>
                                            <div class="text-slate-400" style="font-size: 9px;"><?= date('d M, y h:ia', strtotime($row['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <div style="border-left: 2px solid #e2e8f0; height: 10px; margin-left: 7px; margin-top: -8px; margin-bottom: -8px;"></div>
                                    <?php if ($row['status'] == 'Pending'): ?>
                                    <div class="d-flex align-items-center gap-2 text-slate-400">
                                        <div class="rounded-circle border border-slate-300 bg-white" style="width:16px;height:16px;"></div>
                                        <div>
                                            <div class="fw-bold">Pending Approval</div>
                                        </div>
                                    </div>
                                    <?php elseif ($row['status'] == 'Approved'): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:16px;height:16px;"><i class="bi bi-check-all" style="font-size: 12px;"></i></div>
                                        <div>
                                            <div class="fw-bold text-success">Approved</div>
                                            <div class="text-slate-400" style="font-size: 9px;"><?= date('d M, y h:ia', strtotime($row['updated_at'])) ?></div>
                                        </div>
                                    </div>
                                    <?php elseif ($row['status'] == 'Rejected'): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width:16px;height:16px;"><i class="bi bi-x" style="font-size: 12px;"></i></div>
                                        <div>
                                            <div class="fw-bold text-danger">Rejected</div>
                                            <div class="text-slate-400" style="font-size: 9px;"><?= date('d M, y h:ia', strtotime($row['updated_at'])) ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($row['reject_note'])): ?>
                                        <div class="text-danger mt-1 p-1 bg-danger-subtle rounded border border-danger-subtle" style="font-size: 9px;">
                                            Note: <?= htmlspecialchars($row['reject_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php if (has_permission('expense_edit') || has_permission('expense_delete') || has_permission('txbill_full_access')): ?>
<td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <?php if (has_permission('expense_edit') || has_permission('txbill_full_access')): ?>
                                    <button class="btn-action btn-action-green btn-edit-expense" style="width: 24px; height: 24px;" title="Edit" data-id="<?= $row['id'] ?>"><i class="bi bi-pencil-square"></i></button>
                                    <?php endif; ?>
                                    <?php if (has_permission('expense_delete') || has_permission('txbill_full_access')): ?>
                                    <button class="btn-action btn-action-red btn-delete-expense" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
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

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-wallet2"></i></div> Add New Expense
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addExpenseForm" enctype="multipart/form-data">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Amount (৳) *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" step="0.01" name="amount" class="form-control border-slate-200 shadow-none py-2" placeholder="0.00" style="font-size: 13px;" required>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Category *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-tags-fill"></i></span>
                            <select name="category" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                                <option value="" disabled selected>Select category...</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Travel & Transport">Travel & Transport</option>
                                <option value="Meals & Entertainment">Meals & Entertainment</option>
                                <option value="Maintenance & Repair">Maintenance & Repair</option>
                                <option value="Salary & Wages">Salary & Wages</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Note</label>
                        <textarea name="note" class="form-control border-slate-200 shadow-none py-2" rows="3" placeholder="Enter expense details..." style="font-size: 13px;"></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Documents (Optional)</label>
                        <input type="file" name="documents[]" class="form-control border-slate-200 shadow-none py-2" multiple accept="image/*,.pdf,.doc,.docx" style="font-size: 13px;">
                        <small class="text-slate-400 d-block mt-1" style="font-size: 11px;">You can select multiple images or documents.</small>
                    </div>
                </div>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Expense</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-pencil-square"></i></div> Edit Expense
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="editExpenseForm">
            <input type="hidden" name="expense_id" id="edit_expense_id">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Amount (৳) *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" step="0.01" name="amount" id="edit_expense_amount" class="form-control border-slate-200 shadow-none py-2" placeholder="0.00" style="font-size: 13px;" required>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Category *</label>
                        <div class="input-group input-group-sm input-group-rounded">
                            <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-tags-fill"></i></span>
                            <select name="category" id="edit_expense_category" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                                <option value="" disabled selected>Select category...</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Travel & Transport">Travel & Transport</option>
                                <option value="Meals & Entertainment">Meals & Entertainment</option>
                                <option value="Maintenance & Repair">Maintenance & Repair</option>
                                <option value="Salary & Wages">Salary & Wages</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Note</label>
                        <textarea name="note" id="edit_expense_note" class="form-control border-slate-200 shadow-none py-2" rows="3" placeholder="Enter expense details..." style="font-size: 13px;"></textarea>
                    </div>
                </div>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-4 d-flex align-items-center gap-1" style="font-size: 13px; font-weight: 500;"><i class="bi bi-save"></i> Save Changes</button>
      </div>
      </form>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#expenseTable').DataTable({
        // Uses global configuration from main.js
    });

    $('#searchExpense').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Fetch and show edit modal
    $(document).on('click', '.btn-edit-expense', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" style="width: 14px; height: 14px; border-width: 2px;"></span>').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/get_expense.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                $btn.html(originalHtml).prop('disabled', false);
                if(response.success) {
                    $('#edit_expense_id').val(response.expense.id);
                    $('#edit_expense_amount').val(response.expense.amount);
                    $('#edit_expense_category').val(response.expense.category);
                    $('#edit_expense_note').val(response.expense.note);
                    $('#editExpenseModal').modal('show');
                } else {
                    showToast('error', response.message || 'Failed to fetch expense details.');
                }
            },
            error: function() {
                $btn.html(originalHtml).prop('disabled', false);
                showToast('error', 'Server error occurred.');
            }
        });
    });

    // Handle Edit Submit
    $('#editExpenseForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);
        
        var formData = new FormData(this);

        $.ajax({
            url: '../ajax/edit_expense.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch(e){}
                }
                if (res.success) {
                    showToast('success', res.message);
                    $('#editExpenseModal').modal('hide');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', res.message);
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showToast('error', 'Server error occurred while updating.');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Add Expense
    $('#addExpenseForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);
        
        var formData = new FormData(this);

        $.ajax({
            url: '../ajax/add_expense.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
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

    // Delete Expense
    $('.btn-delete-expense').on('click', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the expense and its documents permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Not Implemented', 'Delete feature coming soon!', 'info');
            }
        });
    });
});
</script>
