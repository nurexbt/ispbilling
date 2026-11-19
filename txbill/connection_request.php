<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$base_path = '../';
$is_transmission = true;
$active_menu = 'connection_request';
$page_title = 'Connection Requests';
$page_subtitle = 'Your assigned connection tasks';
include '../includes/header.php';
include 'sidebar.php';

$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 0;

// Fetch assigned requests from DB
$requests_query = "SELECT c.*, a.area_name, s.subarea_name, e.full_name AS assigned_employee
                   FROM connection_request_tbl c
                   LEFT JOIN area_tbl a ON c.area_id = a.id
                   LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
                   LEFT JOIN employee_tbl e ON c.assigned_to = e.id
                   WHERE c.assigned_to = '$employee_id'
                   ORDER BY c.id DESC";
$requests_result = $conn->query($requests_query);

// Fetch on-hand equipments for this employee
$on_hand_query = "SELECT s.barcode_id, i.item_name FROM store_tbl s JOIN item_tbl i ON s.item_id = i.id WHERE s.delivered_to = '$employee_id' AND s.delivery_status = 'accepted' ORDER BY i.item_name ASC";
$on_hand_result = $conn->query($on_hand_query);
$on_hand_items = [];
if ($on_hand_result && $on_hand_result->num_rows > 0) {
    while($row = $on_hand_result->fetch_assoc()) {
        $on_hand_items[] = $row;
    }
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search tasks...">
            </div>
            <div>
                <!-- Actions could go here -->
            </div>
        </div>

        <!-- Table -->
        <table id="requestTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
            <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Applicant</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Contact</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Location</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Note</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">PPPoE</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if ($requests_result && $requests_result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $requests_result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-900 mb-1 d-flex align-items-center gap-1.5 flex-wrap" style="font-size: 13px; font-weight: 500;">
                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                    <?php if (($row['request_type'] ?? '') === 'onu_change'): ?>
                                        <span class="badge bg-warning text-dark border border-warning-subtle" style="font-size: 9.5px; font-weight: 600;"><i class="bi bi-arrow-repeat me-1"></i>ONU Change</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-slate-500" style="font-size: 11px;"><?= htmlspecialchars($row['created_at']) ?></div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-600 mb-1" style="font-size: 12px;"><i class="bi bi-telephone text-slate-400 me-1"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                <?php if (!empty($row['email'])): ?>
                                <div class="text-slate-500" style="font-size: 11px;"><i class="bi bi-envelope text-slate-400 me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div class="text-slate-700" style="font-size: 12px; font-weight: 500;">
                                    <?= htmlspecialchars($row['area_name']) ?> 
                                    <?= !empty($row['subarea_name']) ? ' <i class="bi bi-chevron-right text-slate-300" style="font-size:10px;"></i> ' . htmlspecialchars($row['subarea_name']) : '' ?>
                                </div>
                                <?php if (!empty($row['address'])): ?>
                                    <div class="text-slate-500 mt-1" style="font-size: 11px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['address']) ?>"><?= htmlspecialchars($row['address']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;">
                                <div style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['note']) ?>">
                                    <?= htmlspecialchars($row['note']) ?: '<span class="text-slate-300">-</span>' ?>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if (!empty($row['pppoe_username'])): ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi bi-person-badge-fill text-primary" style="font-size: 11px;"></i>
                                        <span class="text-slate-700" style="font-size: 12px; font-weight: 500; font-family: monospace;"><?= htmlspecialchars($row['pppoe_username']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-300" style="font-size: 11px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php 
                                    $statusClass = 'bg-secondary text-white';
                                    if ($row['status'] == 'Created') $statusClass = 'bg-info text-dark';
                                    if ($row['status'] == 'Assigned') $statusClass = 'bg-primary text-white';
                                    if ($row['status'] == 'TX Complete') $statusClass = 'bg-warning text-dark';
                                    if ($row['status'] == 'Service created') $statusClass = 'bg-success text-white';
                                ?>
                                <div class="badge <?= $statusClass ?> px-2 py-1" style="font-size: 11px; border-radius: 4px; font-weight: 500;">
                                    <?= htmlspecialchars($row['status'] ?: 'Pending') ?>
                                </div>
                                <?php if (!empty($row['onu_sn'])): ?>
                                    <div class="text-slate-500 mt-1" style="font-size: 10px;">ONU: <?= htmlspecialchars($row['onu_sn']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="padding: 12px 16px;">
                                <?php if ($row['status'] == 'Assigned'): ?>
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn-action btn-action-green btn-submit-onu" 
                                                style="width: 26px; height: 26px; border-radius: 4px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" 
                                                title="Submit ONU" 
                                                data-id="<?= $row['id'] ?>"
                                                data-request-type="<?= htmlspecialchars($row['request_type'] ?? 'new_connection') ?>"
                                                data-customer-id="<?= (int)($row['customer_id'] ?? 0) ?>"
                                                data-applicant="<?= htmlspecialchars($row['name'] ?? '') ?>">
                                            <i class="bi bi-check2-circle" style="font-size: 14px;"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<!-- Submit ONU SN Modal -->
<div class="modal fade" id="submitOnuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-modem-fill"></i></div> Submit ONU
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="submitOnuForm">
            <input type="hidden" name="request_id" id="onu_request_id">
            <input type="hidden" name="onu_sn" id="final_onu_sn">

            <!-- ONU Source Selector (Radio Toggle) -->
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1 d-block" style="font-size: 12px; font-weight: 600;">ONU Type / Source</label>
                <div class="btn-group w-100 p-1 bg-light rounded-3 border border-slate-200" role="group">
                    <input type="radio" class="btn-check" name="onu_source" id="onu_source_store" value="store" checked autocomplete="off">
                    <label class="btn btn-sm btn-outline-primary border-0 rounded-2 py-1.5" for="onu_source_store" style="font-size: 12px; font-weight: 500;">
                        <i class="bi bi-box-seam me-1"></i> From Store
                    </label>

                    <input type="radio" class="btn-check" name="onu_source" id="onu_source_customer" value="customer_own" autocomplete="off">
                    <label class="btn btn-sm btn-outline-primary border-0 rounded-2 py-1.5" for="onu_source_customer" style="font-size: 12px; font-weight: 500;">
                        <i class="bi bi-person-check me-1"></i> Customer Own ONU
                    </label>
                </div>
            </div>

            <!-- Option 1: Select from Store Equipments -->
            <div id="store_onu_group" class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Select ONU / Equipment <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-upc-scan"></i></span>
                    <select name="onu_sn_select" id="onu_sn_select" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="" disabled selected>Select from on-hand equipments...</option>
                        <option value="__customer_own__" style="color: #2563eb; font-weight: 600;">★ Customer Own ONU (Input last 4 digits)</option>
                        <?php foreach($on_hand_items as $eq): ?>
                            <option value="<?= htmlspecialchars($eq['barcode_id']) ?>"><?= htmlspecialchars($eq['item_name']) ?> - <?= htmlspecialchars($eq['barcode_id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($on_hand_items)): ?>
                <div class="form-text text-muted mt-1" style="font-size: 11px;">
                    <i class="bi bi-info-circle me-1"></i>No on-hand equipment available. If customer has their own ONU, switch to <strong>Customer Own ONU</strong>.
                </div>
                <?php endif; ?>
            </div>

            <!-- Option 2: Customer Own ONU (Manual Last 4 Digits) -->
            <div id="customer_onu_group" class="mb-3" style="display: none;">
                <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Customer ONU (Last 4 Digits) <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3 fw-bold font-monospace" style="font-size: 12px;">Customer-</span>
                    <input type="text" 
                           name="customer_onu_last4" 
                           id="customer_onu_last4" 
                           class="form-control border-slate-200 shadow-none py-2 font-monospace" 
                           placeholder="e.g. 4A8F or 1234" 
                           maxlength="4" 
                           autocomplete="off"
                           style="text-transform: uppercase; font-weight: 600; letter-spacing: 2px; font-size: 13px;">
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1.5 px-1">
                    <span class="text-slate-400" style="font-size: 11px;">Input customer ONU last 4 digit</span>
                    <span class="text-slate-500 font-monospace" style="font-size: 11px;">Record: <strong id="customer_onu_preview" class="text-primary fw-bold">Customer-____</strong></span>
                </div>
            </div>

            <!-- Option 3: Return Old ONU to Store (Shown on ONU Change tasks) -->
            <div id="onu_return_container" class="p-3 mb-2 rounded-3 border" style="background-color: #f8fafc; border-color: #e2e8f0; display: none;">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-slate-800 fw-semibold d-flex align-items-center gap-1.5" style="font-size: 12px;">
                        <i class="bi bi-arrow-return-left text-warning"></i> Old ONU Return to Store
                    </span>
                    <span class="badge bg-warning text-dark border border-warning-subtle" style="font-size: 9.5px; font-weight: 600;">Needs Manager Approval</span>
                </div>
                
                <div class="form-check form-switch mt-2 mb-2">
                    <input class="form-check-input" type="checkbox" name="return_old_onu" id="return_old_onu" value="1" checked style="cursor: pointer;">
                    <label class="form-check-label text-slate-700 fw-medium" for="return_old_onu" style="font-size: 12px; cursor: pointer;">
                        Return old ONU to Store
                    </label>
                </div>
                
                <div id="return_condition_box">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 11.5px; font-weight: 500;">Returned ONU Condition</label>
                    <select name="return_condition" id="return_condition" class="form-select form-select-sm border-slate-200 shadow-none text-slate-700" style="font-size: 12px;">
                        <option value="Replace" selected>Working / Replaced (For Store Inspection)</option>
                        <option value="Damage">Damage / Defective</option>
                        <option value="Warranty">Warranty Claim</option>
                    </select>
                    <div class="text-slate-400 mt-1" style="font-size: 10.5px;">
                        <i class="bi bi-shield-check me-1"></i>Before this ONU is added back to store inventory, it must be approved by the <strong>Manager</strong>.
                    </div>
                </div>
                <div id="return_not_returned_hint" class="text-muted mt-1" style="font-size: 11px; display: none;">
                    <i class="bi bi-info-circle me-1"></i>Old ONU will not be returned to store (kept by customer or not retrieved).
                </div>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Submit</button>
      </div>
        </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- DataTables & jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#requestTable')) {
        $('#requestTable').DataTable().destroy();
    }
    
    var table = $('#requestTable').DataTable();

    // Custom search integration
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Open Submit Modal
    $(document).on('click', '.btn-submit-onu', function() {
        var id = $(this).data('id');
        var reqType = $(this).data('request-type') || '';
        $('#onu_request_id').val(id);
        $('#submitOnuForm')[0].reset();
        
        // Reset to default Store mode
        setOnuMode('store');

        // Toggle Old ONU Return section based on request type
        if (reqType === 'onu_change') {
            $('#onu_return_container').show();
            $('#return_old_onu').prop('checked', true);
            $('#return_condition_box').show();
            $('#return_not_returned_hint').hide();
        } else {
            $('#onu_return_container').hide();
            $('#return_old_onu').prop('checked', false);
        }
        
        $('#submitOnuModal').modal('show');
    });

    $('#return_old_onu').on('change', function() {
        if ($(this).is(':checked')) {
            $('#return_condition_box').slideDown(150);
            $('#return_not_returned_hint').hide();
        } else {
            $('#return_condition_box').slideUp(150);
            $('#return_not_returned_hint').show();
        }
    });

    // Toggle between Store ONU and Customer Own ONU
    function setOnuMode(mode) {
        if (mode === 'customer_own') {
            $('#onu_source_customer').prop('checked', true);
            $('#store_onu_group').hide();
            $('#onu_sn_select').prop('required', false);
            $('#customer_onu_group').show();
            $('#customer_onu_last4').prop('required', true).val('').focus();
            $('#customer_onu_preview').text('Customer-____');
        } else {
            $('#onu_source_store').prop('checked', true);
            $('#store_onu_group').show();
            $('#onu_sn_select').prop('required', true);
            if ($('#onu_sn_select').val() === '__customer_own__') {
                $('#onu_sn_select').val('');
            }
            $('#customer_onu_group').hide();
            $('#customer_onu_last4').prop('required', false).val('');
        }
    }

    $('input[name="onu_source"]').on('change', function() {
        setOnuMode($(this).val());
    });

    // Also trigger Customer Own ONU if selected from dropdown
    $('#onu_sn_select').on('change', function() {
        if ($(this).val() === '__customer_own__') {
            setOnuMode('customer_own');
        }
    });

    // Live uppercase formatting and preview for customer ONU last 4 digits
    $('#customer_onu_last4').on('input keyup', function() {
        var val = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        $(this).val(val);
        $('#customer_onu_preview').text('Customer-' + (val ? val : '____'));
    });

    // Handle Submit ONU Form
    $('#submitOnuForm').on('submit', function(e) {
        e.preventDefault();
        
        var source = $('input[name="onu_source"]:checked').val();
        var finalOnuSn = '';

        if (source === 'customer_own') {
            var last4 = $('#customer_onu_last4').val().trim().toUpperCase();
            if (!last4) {
                showToast('error', 'Please enter customer ONU last 4 digits.');
                $('#customer_onu_last4').focus();
                return;
            }
            if (last4.length < 4) {
                showToast('error', 'Customer ONU must be exactly 4 digits / characters.');
                $('#customer_onu_last4').focus();
                return;
            }
            finalOnuSn = 'Customer-' + last4;
        } else {
            var selected = $('#onu_sn_select').val();
            if (!selected || selected === '__customer_own__') {
                showToast('error', 'Please select an ONU from on-hand equipments or switch to Customer Own ONU.');
                $('#onu_sn_select').focus();
                return;
            }
            finalOnuSn = selected;
        }

        $('#final_onu_sn').val(finalOnuSn);

        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/submit_onu_sn.php',
            type: 'POST',
            data: {
                request_id: $('#onu_request_id').val(),
                onu_sn: finalOnuSn,
                onu_source: source,
                customer_onu_last4: (source === 'customer_own' ? $('#customer_onu_last4').val().trim().toUpperCase() : ''),
                return_old_onu: $('#return_old_onu').is(':checked') ? 1 : 0,
                return_condition: $('#return_condition').val() || 'Replace'
            },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#submitOnuModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 800); 
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while submitting.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
