<?php
$src = 'c:\xampp\htdocs\billsoft\defaultportal\expense.php';
$dest = 'c:\xampp\htdocs\billsoft\billingportal\expense.php';

$content = file_get_contents($src);

// Fix paths for billingportal
$content = str_replace("include '../includes/sidebar.php';", "include 'sidebar.php';", $content);
$content = str_replace("require_once '../includes/db.php';", "require_once '../includes/db.php';\n\$base_path = '../';", $content);

// Filter query for billingportal
$content = preg_replace('/\$expenses_query\s*=\s*".*?ORDER BY e?\.?id DESC.*?";/s', "\$user_id = \$_SESSION['user_id'] ?? 0;\n\$expenses_query = \"\n    SELECT e.*, emp.full_name as added_by_name \n    FROM expense_tbl e\n    LEFT JOIN portal_user_tbl pu ON e.created_by = pu.id\n    LEFT JOIN employee_tbl emp ON pu.employee_id = emp.id\n    WHERE e.created_by = \$user_id \n    ORDER BY e.id DESC\n\";", $content);

// Add the Added By header
$headerSearch = '<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status Progress</th>';
$headerReplace = "<th class=\"border-0 text-slate-500\" style=\"font-size: 11px; padding: 12px 16px;\">Added By</th>\n                        " . $headerSearch;
$content = str_replace($headerSearch, $headerReplace, $content);

// Add the Added By TD exactly before the Status Progress TD
$tdSearch = '                            <td style="padding: 12px 16px;">
                                <div class="d-flex flex-column gap-2" style="font-size: 10px;">
                                    <div class="d-flex align-items-center gap-2">';
$tdReplace = "                            <td class=\"text-slate-600\" style=\"font-size: 12px; padding: 12px 16px;\">\n" .
             "                                <div class=\"d-flex align-items-center gap-2\">\n" .
             "                                    <div class=\"rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center\" style=\"width:24px;height:24px;font-weight:600;font-size:10px;\">\n" .
             "                                        <?= strtoupper(substr(\$row['added_by_name'] ?? '?', 0, 1)) ?>\n" .
             "                                    </div>\n" .
             "                                    <span><?= htmlspecialchars(\$row['added_by_name'] ?? 'Unknown') ?></span>\n" .
             "                                </div>\n" .
             "                            </td>\n" . $tdSearch;

// Try str_replace
$content = str_replace($tdSearch, $tdReplace, $content);
// If it fails because of \r\n, do it via \r\n
$tdSearchRN = str_replace("\n", "\r\n", $tdSearch);
$tdReplaceRN = str_replace("\n", "\r\n", $tdReplace);
$content = str_replace($tdSearchRN, $tdReplaceRN, $content);

$modalHtml = <<<HTML
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
HTML;

$jsHtml = <<<JS
// Fetch and show edit modal
    $(document).on('click', '.btn-edit-expense', function() {
        var id = $(this).data('id');
        var \$btn = $(this);
        var originalHtml = \$btn.html();
        \$btn.html('<span class="spinner-border spinner-border-sm" style="width: 14px; height: 14px; border-width: 2px;"></span>').prop('disabled', true);
        
        \$.ajax({
            url: '../ajax/get_expense.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                \$btn.html(originalHtml).prop('disabled', false);
                if(response.success) {
                    \$('#edit_expense_id').val(response.expense.id);
                    \$('#edit_expense_amount').val(response.expense.amount);
                    \$('#edit_expense_category').val(response.expense.category);
                    \$('#edit_expense_note').val(response.expense.note);
                    \$('#editExpenseModal').modal('show');
                } else {
                    showToast('error', response.message || 'Failed to fetch expense details.');
                }
            },
            error: function() {
                \$btn.html(originalHtml).prop('disabled', false);
                showToast('error', 'Server error occurred.');
            }
        });
    });

    // Handle Edit Submit
    \$('#editExpenseForm').on('submit', function(e) {
        e.preventDefault();
        var \$btn = $(this).find('button[type="submit"]');
        var originalText = \$btn.html();
        \$btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);
        
        var formData = new FormData(this);

        \$.ajax({
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
                    \$('#editExpenseModal').modal('hide');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', res.message);
                    \$btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showToast('error', 'Server error occurred while updating.');
                \$btn.html(originalText).prop('disabled', false);
            }
        });
    });
JS;

// Insert Edit Modal HTML
$footerSearch = "<?php include '../includes/footer.php'; ?>";
$content = str_replace($footerSearch, $modalHtml . "\n" . $footerSearch, $content);

// Insert JS
$jsSearch = "// Add Expense";
$content = str_replace($jsSearch, $jsHtml . "\n\n    " . $jsSearch, $content);

file_put_contents($dest, $content);
echo "Rebuilt successfully!";
