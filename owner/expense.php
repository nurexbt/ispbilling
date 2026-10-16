<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;
$active_menu = 'expense';
$page_title = 'Expense Approvals';
$page_subtitle = 'Review and approve company expenses';
include '../includes/header.php';
include 'includes/sidebar.php';

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';

// Fetch expenses from DB
if ($filter_status == 'All') {
    $expenses_query = "
        SELECT e.*, emp.full_name as added_by_name 
        FROM expense_tbl e
        LEFT JOIN portal_user_tbl pu ON e.created_by = pu.id
        LEFT JOIN employee_tbl emp ON pu.employee_id = emp.id
        ORDER BY e.id DESC";
} else {
    $expenses_query = "
        SELECT e.*, emp.full_name as added_by_name 
        FROM expense_tbl e
        LEFT JOIN portal_user_tbl pu ON e.created_by = pu.id
        LEFT JOIN employee_tbl emp ON pu.employee_id = emp.id
        WHERE e.status = '" . $conn->real_escape_string($filter_status) . "' 
        ORDER BY e.id DESC";
}
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
                <select class="form-select form-select-sm border-slate-200 shadow-none" style="width: 150px; font-size: 13px;" onchange="window.location.href='expense.php?status='+this.value">
                    <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $filter_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $filter_status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="All" <?= $filter_status == 'All' ? 'selected' : '' ?>>All Expenses</option>
                </select>
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
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
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
                                            <a href="#" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none doc-preview-btn"
                                               data-url="<?= $base_path ?><?= htmlspecialchars($doc_path) ?>"
                                               data-name="Doc <?= $idx + 1 ?>">
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
                            <td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <?php if ($row['status'] == 'Pending'): ?>
                                    <button class="btn-action btn-action-green btn-approve-expense" style="width: 24px; height: 24px;" title="Approve" data-id="<?= $row['id'] ?>"><i class="bi bi-check-lg"></i></button>
                                    <button class="btn-action btn-action-red btn-reject-expense" style="width: 24px; height: 24px;" title="Reject" data-id="<?= $row['id'] ?>"><i class="bi bi-x-lg"></i></button>
                                    <?php else: ?>
                                    <span class="text-slate-400" style="font-size:11px;">Processed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<!-- Reject Expense Modal -->
<div class="modal fade" id="rejectExpenseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 16px; font-weight: 600;">
            <div class="bg-danger text-white rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-x-circle"></i></div> Reject Expense
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" style="font-size: 12px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="rejectExpenseForm">
            <input type="hidden" name="id" id="reject_expense_id">
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Rejection Note *</label>
                <textarea name="reject_note" class="form-control border-slate-200 shadow-none py-2" rows="3" placeholder="Explain why this expense is rejected..." style="font-size: 13px;" required></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn text-slate-600 border border-slate-200 bg-light shadow-none" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; padding: 8px 16px;">Cancel</button>
                <button type="submit" class="btn btn-danger shadow-none" style="font-size: 13px; font-weight: 500; border-radius: 8px; padding: 8px 16px;">Reject Expense</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/doc_viewer_modal.php'; ?>
<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#expenseTable').DataTable({
        // Uses global configuration from main.js
    });

    $('#searchExpense').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Approve
    $('.btn-approve-expense').on('click', function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Approve Expense?',
            text: "Are you sure you want to approve this expense?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'compact-swal-popup',
                title: 'compact-swal-title',
                htmlContainer: 'compact-swal-text',
                actions: 'compact-swal-actions',
                confirmButton: 'btn btn-sm btn-success px-4 text-white',
                cancelButton: 'btn btn-sm btn-secondary px-3 text-white'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);
                $.post('../ajax/approve_expense.php', { id: id }, function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: 'The expense has been approved.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        btn.prop('disabled', false);
                    }
                });
            }
        });
    });

    // Reject
    $('.btn-reject-expense').on('click', function() {
        var id = $(this).data('id');
        $('#reject_expense_id').val(id);
        $('#rejectExpenseModal').modal('show');
    });

    $('#rejectExpenseForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Rejecting...');
        
        $.post('../ajax/reject_expense.php', $(this).serialize(), function(res) {
            if (res.success) {
                $('#rejectExpenseModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Rejected!',
                    text: 'The expense has been rejected.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.prop('disabled', false).text('Reject Expense');
            }
        });
    });
});
</script>

