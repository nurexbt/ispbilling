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
                                <div class="text-slate-900 mb-1" style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($row['name']) ?></div>
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
                                        <button class="btn-action btn-action-green btn-submit-onu" style="width: 26px; height: 26px; border-radius: 4px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Submit ONU" data-id="<?= $row['id'] ?>"><i class="bi bi-check2-circle" style="font-size: 14px;"></i></button>
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
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
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
            
            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select ONU / Equipment</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-upc-scan"></i></span>
                    <select name="onu_sn" class="form-select border-slate-200 shadow-none py-2" required>
                        <option value="" disabled selected>Select from on-hand equipments...</option>
                        <?php foreach($on_hand_items as $eq): ?>
                            <option value="<?= htmlspecialchars($eq['barcode_id']) ?>"><?= htmlspecialchars($eq['item_name']) ?> - <?= htmlspecialchars($eq['barcode_id']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
        $('#onu_request_id').val(id);
        $('#submitOnuForm')[0].reset();
        $('#submitOnuModal').modal('show');
    });

    // Handle Submit ONU Form
    $('#submitOnuForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/submit_onu_sn.php',
            type: 'POST',
            data: $(this).serialize(),
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
