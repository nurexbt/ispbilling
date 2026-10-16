<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$base_path = '../';
$is_manager = true;
$active_menu = 'sms_portal';
$page_title = 'SMS Portal';
$page_subtitle = 'Configure API Gateway and test messaging';

include '../includes/header.php';
include 'includes/sidebar.php';

// Fetch current settings
$settings = [
    'is_active' => 1,
    'gateway_url' => '',
    'http_method' => 'GET',
    'recipient_param' => 'msisdn',
    'message_param' => 'sms',
    'http_headers' => '{}',
    'additional_params' => '{}'
];
$res = $conn->query("SELECT * FROM sms_settings_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $settings = array_merge($settings, $res->fetch_assoc());
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search history...">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light border-slate-200 text-slate-700 d-flex align-items-center gap-2 px-3 py-2 shadow-sm btn-check-balance" style="font-weight: 600; font-size: 13px;">
                    <i class="bi bi-wallet2 text-info"></i> Check Balance
                </button>
                <button class="btn btn-light border-slate-200 text-slate-700 d-flex align-items-center gap-2 px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#configModal" style="font-weight: 600; font-size: 13px;">
                    <i class="bi bi-gear-fill text-primary"></i> Configure Gateway
                </button>
                <button class="btn btn-success text-white d-flex align-items-center gap-2 px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#testSmsModal" style="font-weight: 600; font-size: 13px;">
                    <i class="bi bi-send-fill"></i> Send SMS
                </button>
            </div>
        </div>

        <table class="table align-middle mb-0" id="smsHistoryTable" style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Date & Time</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Recipient</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Message</th>
                    <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status</th>
                </tr>
            </thead>
            <tbody style="border-top: 1px solid var(--border-slate-200);">
                <?php
                $hist_res = $conn->query("SELECT * FROM sms_history_tbl ORDER BY sent_at DESC LIMIT 500");
                if ($hist_res && $hist_res->num_rows > 0) {
                    while ($row = $hist_res->fetch_assoc()) {
                        $is_success = ($row['status'] === 'success');
                        ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?php echo date('d M Y, h:i A', strtotime($row['sent_at'])); ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900 d-flex align-items-center gap-2" style="font-size: 12px; font-weight: 500;">
                                    <?php echo htmlspecialchars($row['recipient_number']); ?>
                                    <?php if (!empty($row['bulk_numbers'])): ?>
                                        <button class="btn btn-sm btn-light border-slate-200 py-0 px-2 view-bulk-numbers-btn shadow-sm" data-numbers='<?php echo htmlspecialchars($row['bulk_numbers'], ENT_QUOTES, 'UTF-8'); ?>' style="font-size: 10px; font-weight: 600;">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    <?php elseif (strpos($row['recipient_number'], 'Bulk') === 0): ?>
                                        <button class="btn btn-sm btn-light border-slate-200 py-0 px-2 shadow-sm" style="font-size: 10px; font-weight: 600; opacity: 0.6; cursor: not-allowed;" title="Numbers not saved for older records" disabled>
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-slate-600" style="font-size: 11px; padding: 12px 16px;">
                                <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['message']); ?>">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ($is_success): ?>
                                    <div class="status-pill online device-status" style="background-color: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 9999px; font-size: 10px; display: inline-block; font-weight: 600;"><i class="bi bi-check-circle-fill me-1"></i> Success</div>
                                <?php else: ?>
                                    <div class="status-pill offline device-status" style="background-color: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 9999px; font-size: 10px; display: inline-block; font-weight: 600;"><i class="bi bi-x-circle-fill me-1"></i> Failed</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Configure Gateway Modal (Gennet API v3) -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div class="modal-header border-bottom border-slate-100 py-3 px-4">
                <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" id="configModalLabel" style="font-size: 15px; font-weight: 600;">
                    <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-gear"></i></div> SMS Gateway Settings
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="smsConfigForm">
                    <div class="mb-4 d-flex align-items-center justify-content-between p-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                        <div>
                            <h6 class="mb-0 text-slate-800 fw-bold" style="font-size: 13px;">Enable SMS Gateway</h6>
                            <small class="text-slate-500">Toggle to activate or deactivate automated SMS sending</small>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input shadow-none cursor-pointer" type="checkbox" name="is_active" id="isActive" <?php echo ($settings['is_active'] == 1) ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold" style="font-size: 13px;">API Base Domain <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm shadow-none border-slate-200" name="gateway_url" placeholder="https://isms.gennet.com.bd" value="<?php echo htmlspecialchars($settings['gateway_url'] ?? ''); ?>" required>
                        <small class="text-muted" style="font-size: 11px;">Example: https://isms.gennet.com.bd</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold" style="font-size: 13px;">API Token <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm shadow-none border-slate-200" name="api_key" placeholder="Enter your API token" value="<?php echo htmlspecialchars($settings['api_key'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold" style="font-size: 13px;">Sender ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm shadow-none border-slate-200" name="sender_id" placeholder="e.g. TeamzeroNONMASKING" value="<?php echo htmlspecialchars($settings['sender_id'] ?? ''); ?>" required>
                    </div>
            </div>
            <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
                <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Save Configuration</button>
            </div>
                </form>
        </div>
    </div>
</div>

<!-- Test SMS Modal -->
<div class="modal fade" id="testSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-slate-800"><i class="bi bi-send-fill me-2 text-success"></i> Send Test SMS</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="testSmsForm" class="d-flex flex-column">
                    <ul class="nav nav-pills mb-3 nav-justified bg-slate-50 p-1 rounded-3" id="smsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-3 py-2 fw-semibold" style="font-size: 13px;" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-sms" type="button" role="tab">Single SMS</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 py-2 fw-semibold" style="font-size: 13px;" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk-sms" type="button" role="tab">Bulk SMS</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="smsTabsContent">
                        <!-- Single SMS Tab -->
                        <div class="tab-pane fade show active" id="single-sms" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Recipient Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-slate-50 border-slate-200 text-slate-500"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone_number" class="form-control shadow-none border-slate-200" placeholder="e.g. 01XXXXXXXXX" id="singlePhoneInput">
                                </div>
                            </div>
                        </div>

                        <!-- Bulk SMS Tab -->
                        <div class="tab-pane fade" id="bulk-sms" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Recipient Numbers</label>
                                <textarea name="bulk_numbers" id="bulkNumbersInput" class="form-control shadow-none border-slate-200" rows="3" placeholder="017XXXXXXXX, 018XXXXXXXX, 019XXXXXXXX..."></textarea>
                                <small class="text-muted" style="font-size: 11px;">Enter multiple numbers separated by commas (Max 100).</small>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="sms_type" id="smsTypeInput" value="single">

                    <div class="mb-3">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Message Template</label>
                        <select class="form-select shadow-none border-slate-200" id="smsTemplateSelect">
                            <option value="">-- Custom Message --</option>
                            <option value="Dear customer, your connection has been successfully established. Thank you for choosing us!">Welcome Message</option>
                            <option value="Reminder: Your monthly internet bill is due soon. Please pay to avoid disconnection.">Bill Reminder</option>
                            <option value="Your payment has been received successfully. Thank you!">Payment Confirmation</option>
                            <option value="This is a test message from your BillSoft ERP System to verify SMS Gateway connectivity.">System Test</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 flex-grow-1 d-flex flex-column">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Message</label>
                        <textarea name="message" id="testMessageContent" class="form-control shadow-none border-slate-200 flex-grow-1" rows="4" placeholder="Type your message here..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 rounded-3 py-2 fw-semibold shadow-sm d-flex justify-content-center align-items-center">
                        <i class="bi bi-paperclip me-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- API Response Modal -->
<div class="modal fade" id="apiResponseModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-slate-800"><i class="bi bi-terminal me-2 text-slate-500"></i>API Response Log</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-dark rounded-3 p-3 overflow-auto custom-scrollbar" style="max-height: 300px;">
                    <pre class="text-light mb-0" id="apiResponseContent" style="font-size: 0.8rem; font-family: monospace; white-space: pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Numbers Modal -->
<div class="modal fade" id="bulkNumbersModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-slate-800"><i class="bi bi-people-fill me-2 text-primary"></i>Bulk Recipients</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="bg-slate-50 border border-slate-200 rounded-3 p-3 overflow-auto custom-scrollbar" style="max-height: 250px;">
                    <div id="bulkNumbersContent" style="font-size: 13px; font-family: monospace; color: #475569; word-break: break-all;"></div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border-slate-200 rounded-3 px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    
    if ($.fn.DataTable.isDataTable('#smsHistoryTable')) {
        $('#smsHistoryTable').DataTable().destroy();
    }
    
    var table = $('#smsHistoryTable').DataTable({
        "order": [],
        "pageLength": 10
    });

    // Bind custom search
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // View Response Button
    $('.view-response-btn').on('click', function() {
        var response = $(this).data('response');
        $('#apiResponseContent').text(response || 'No response recorded.');
        $('#apiResponseModal').modal('show');
    });

    // View Bulk Numbers Button
    $('.view-bulk-numbers-btn').on('click', function() {
        var numbersJson = $(this).data('numbers');
        var numbersList = '';
        if (numbersJson && Array.isArray(numbersJson)) {
            numbersList = numbersJson.join(', ');
        } else {
            numbersList = 'No numbers found.';
        }
        
        $('#bulkNumbersContent').text(numbersList);
        $('#bulkNumbersModal').modal('show');
    });

    // Template Selection Logic
    $('#smsTemplateSelect').on('change', function() {
        var selectedText = $(this).val();
        $('#testMessageContent').val(selectedText);
    });

    // Handle SMS Type switching
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("data-bs-target"); // newly activated tab
        if (target === '#single-sms') {
            $('#smsTypeInput').val('single');
            $('#singlePhoneInput').prop('required', true);
            $('#bulkNumbersInput').prop('required', false);
        } else {
            $('#smsTypeInput').val('bulk');
            $('#singlePhoneInput').prop('required', false);
            $('#bulkNumbersInput').prop('required', true);
        }
    });

    // Check Balance logic
    $('.btn-check-balance').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalBtnHtml = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Checking...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/check_sms_balance.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $btn.html(originalBtnHtml).prop('disabled', false);
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'SMS Balance',
                        text: response.message,
                        icon: 'info',
                        customClass: {
                            popup: 'compact-swal-popup',
                            title: 'compact-swal-title',
                            confirmButton: 'btn btn-primary px-4'
                        },
                        buttonsStyling: false
                    });
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                $btn.html(originalBtnHtml).prop('disabled', false);
                showToast('error', 'Network error while checking balance.');
            }
        });
    });

    // Save Configuration
    $('#smsConfigForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalBtnHtml = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/save_sms_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.html(originalBtnHtml).prop('disabled', false);
                if (response.status === 'success') {
                    showToast('success', response.message);
                    $('#configModal').modal('hide');
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                $btn.html(originalBtnHtml).prop('disabled', false);
                showToast('error', 'Network error while saving settings.');
            }
        });
    });

    // Send Test SMS
    $('#testSmsForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalBtnHtml = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Sending...').prop('disabled', true);
        
        $.ajax({
            url: '../ajax/send_test_sms.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.html(originalBtnHtml).prop('disabled', false);
                if (response.status === 'success') {
                    showToast('success', response.message);
                    $('#testSmsModal').modal('hide');
                    
                    // Reload page after a short delay to see new history
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                $btn.html(originalBtnHtml).prop('disabled', false);
                showToast('error', 'Network error while sending test SMS.');
            }
        });
    });
});
</script>
