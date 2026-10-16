<?php
require_once '../includes/db.php';
$base_path = '../';
$is_transmission = true;
$active_menu = 'equipments';
include '../includes/header.php';

$emp_id = $_SESSION['employee_id'] ?? 0;

// Fetch equipment assigned to this employee
$equipment_query = "SELECT s.*, i.item_name FROM store_tbl s JOIN item_tbl i ON s.item_id = i.id WHERE s.delivered_to = '$emp_id' ORDER BY s.id DESC";
$equipment_result = $conn->query($equipment_query);

$pending_items = [];
$on_hand_items = [];

if ($equipment_result && $equipment_result->num_rows > 0) {
    while ($row = $equipment_result->fetch_assoc()) {
        if ($row['delivery_status'] === 'pending') {
            $pending_items[] = $row;
        } elseif ($row['delivery_status'] === 'accepted') {
            // Only show accepted items that have NOT been used/submitted
            $on_hand_items[] = $row;
        }
    }
}
?>
<?php include 'sidebar.php'; ?>

<div class="container-fluid pt-2">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 text-slate-900 fw-bold" style="font-size: 1.25rem;">My Equipment</h4>
            <p class="text-slate-500 mb-0" style="font-size: 0.85rem;">Manage items assigned to you</p>
        </div>
    </div>

    <!-- Tabs/Switch Button -->
    <div class="d-flex gap-2 mb-3 bg-white p-2 rounded-3 shadow-sm border border-slate-100" style="width: fit-content;">
        <button class="btn btn-sm px-4 fw-medium btn-tab active" data-target="#pending-tab" style="border-radius: 8px;">Pending Items <span class="badge bg-warning text-dark ms-1"><?= count($pending_items) ?></span></button>
        <button class="btn btn-sm px-4 fw-medium btn-tab text-slate-500 bg-transparent" data-target="#onhand-tab" style="border-radius: 8px;">On Hand <span class="badge bg-success text-white ms-1"><?= count($on_hand_items) ?></span></button>
    </div>

    <!-- Tab Content -->
    <div class="dashboard-card p-0 border-0 shadow-sm">
        <!-- Pending Tab -->
        <div id="pending-tab" class="tab-pane active">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">ITEM CATEGORY</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">BARCODE / SCAN ID</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">ASSIGNED AT</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-align:right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if (count($pending_items) > 0): ?>
                            <?php $i = 1; foreach ($pending_items as $item): ?>
                            <tr>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1" style="font-size: 11px; border-radius: 12px;">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                    </div>
                                </td>
                                <td class="text-slate-700 fw-medium" style="font-size: 12px; padding: 12px 16px;">
                                    <i class="bi bi-upc text-slate-400 me-1"></i> <?= htmlspecialchars($item['barcode_id']) ?>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($item['created_at']) ?></td>
                                <td style="padding: 12px 16px; text-align:right;">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn-action btn-action-green btn-update-status" style="width: 24px; height: 24px; border-radius: 4px;" title="Accept" data-id="<?= $item['id'] ?>" data-status="accepted">
                                            <i class="bi bi-check-lg" style="font-size: 14px;"></i>
                                        </button>
                                        <button class="btn-action btn-action-red btn-update-status" style="width: 24px; height: 24px; border-radius: 4px;" title="Reject" data-id="<?= $item['id'] ?>" data-status="rejected">
                                            <i class="bi bi-x-lg" style="font-size: 14px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-slate-400 py-4" style="font-size: 13px;">No pending equipment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- On Hand Tab -->
        <div id="onhand-tab" class="tab-pane d-none">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                    <thead style="background-color: #F8FAFC;">
                        <tr>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">#</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">ITEM CATEGORY</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">BARCODE / SCAN ID</th>
                            <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">ASSIGNED AT</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 1px solid var(--border-slate-200);">
                        <?php if (count($on_hand_items) > 0): ?>
                            <?php $i = 1; foreach ($on_hand_items as $item): ?>
                            <tr>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= $i++ ?></td>
                                <td style="padding: 12px 16px;">
                                    <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1" style="font-size: 11px; border-radius: 12px;">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                    </div>
                                </td>
                                <td class="text-slate-700 fw-medium" style="font-size: 12px; padding: 12px 16px;">
                                    <i class="bi bi-upc text-slate-400 me-1"></i> <?= htmlspecialchars($item['barcode_id']) ?>
                                </td>
                                <td class="text-slate-500" style="font-size: 11px; padding: 12px 16px;"><?= htmlspecialchars($item['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-slate-400 py-4" style="font-size: 13px;">No equipment on hand.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Tab switching
    $('.btn-tab').click(function() {
        $('.btn-tab').removeClass('active text-primary bg-primary bg-opacity-10 border-primary').addClass('text-slate-500 bg-transparent').css('border', 'none');
        $(this).removeClass('text-slate-500 bg-transparent').addClass('active text-primary bg-primary bg-opacity-10').css('border', '1px solid rgba(13,110,253,0.2)');
        
        var target = $(this).data('target');
        $('.tab-pane').addClass('d-none');
        $(target).removeClass('d-none');
    });

    // Initialize first tab styles
    $('.btn-tab.active').removeClass('text-slate-500 bg-transparent').addClass('text-primary bg-primary bg-opacity-10').css('border', '1px solid rgba(13,110,253,0.2)');

    // Accept / Reject
    $('.btn-update-status').click(function() {
        var id = $(this).data('id');
        var status = $(this).data('status');
        var text = status === 'accepted' ? 'Accept this item?' : 'Reject this item?';
        
        Swal.fire({
            title: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'compact-swal-popup',
                title: 'compact-swal-title',
                confirmButton: 'btn btn-sm btn-primary px-4',
                cancelButton: 'btn btn-sm btn-secondary px-3 text-white'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/update_equipment_status.php',
                    type: 'POST',
                    data: { id: id, status: status },
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
                        showToast('error', 'Server error');
                    }
                });
            }
        });
    });
});
</script>
