<?php
require_once 'includes/db.php';
$active_menu = 'amount_collect';
$page_title = 'Amount Collect';
$page_subtitle = 'Total collected amount by processors';
include 'includes/header.php';

if (!has_permission('amount_collect_view')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied. You do not have permission to view this page.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'includes/sidebar.php';

// Fetch filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_date_from)) {
    $where_clauses[] = "payment_date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "payment_date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT 
    processed_by, 
    SUM(amount) AS total_collected 
  FROM payment_tbl 
  WHERE $where_sql 
  GROUP BY processed_by 
  ORDER BY total_collected DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch handovers
$ho_where_clauses = ["1=1"];
$ho_params = [];
$ho_types = "";

if (!empty($filter_date_from)) {
    $ho_where_clauses[] = "handover_date >= ?";
    $ho_params[] = $filter_date_from . " 00:00:00";
    $ho_types .= "s";
}
if (!empty($filter_date_to)) {
    $ho_where_clauses[] = "handover_date <= ?";
    $ho_params[] = $filter_date_to . " 23:59:59";
    $ho_types .= "s";
}
$ho_where_sql = implode(" AND ", $ho_where_clauses);

$ho_query = "SELECT processor_name, SUM(amount) as total FROM collector_handover_tbl WHERE $ho_where_sql GROUP BY processor_name";
$ho_stmt = $conn->prepare($ho_query);
if (!empty($ho_params)) {
    $ho_stmt->bind_param($ho_types, ...$ho_params);
}
$ho_stmt->execute();
$ho_res = $ho_stmt->get_result();
$handovers = [];
$total_handover_all = 0;
while ($ho = $ho_res->fetch_assoc()) {
    $handovers[$ho['processor_name']] = $ho['total'];
    $total_handover_all += $ho['total'];
}
$ho_stmt->close();

$collectors = [];
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    $collectors[] = $row;
    $grand_total += $row['total_collected'];
}
$stmt->close();
?>

<div class="container-fluid pt-2">

    <!-- Summary Cards -->
    <div class="row g-3 mt-1 mb-3">
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #0ea5e9 !important; border-radius: 10px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Grand Total Collected</div>
                <div class="fw-bold" style="font-size: 20px; color: #0ea5e9;">৳<?= number_format($grand_total, 2) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #22c55e !important; border-radius: 10px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Handed Over</div>
                <div class="fw-bold" style="font-size: 20px; color: #22c55e;">৳<?= number_format($total_handover_all, 2) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #f43f5e !important; border-radius: 10px; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Due</div>
                <div class="fw-bold" style="font-size: 20px; color: #f43f5e;">৳<?= number_format(max(0, $grand_total - $total_handover_all), 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="dashboard-card p-0 border-0" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden;">

        <!-- Toolbar / Filters -->
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom border-slate-100 gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center" id="filterForm">
                    <input type="date" name="date_from" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_from) ?>" style="width: 135px; font-size: 12px;" placeholder="From Date" title="From Date">
                    <input type="date" name="date_to" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= htmlspecialchars($filter_date_to) ?>" style="width: 135px; font-size: 12px;" placeholder="To Date" title="To Date">
                    <button type="submit" class="btn btn-sm btn-primary shadow-none px-3" style="font-size: 12px;"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="amount_collect.php" class="btn btn-sm btn-light border-slate-200 shadow-none px-3" style="font-size: 12px;"><i class="bi bi-x-circle me-1"></i>Clear</a>
                </form>
            </div>
            <div>
                <input type="text" id="searchCollector" class="toolbar-input form-control form-control-sm border-slate-200 shadow-none" placeholder="Search name..." style="width: 200px; font-size: 12px;">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover align-middle mb-0" style="border-collapse: collapse; min-width: 600px;">
                <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; width: 60px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Name</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Total Amount Collected</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Handed Over</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px;">Due Amount</th>
                        <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 12px 16px; width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if (count($collectors) > 0): ?>
                        <?php $i = 1; foreach ($collectors as $col): ?>
                        <?php 
                            $handed = $handovers[$col['processed_by']] ?? 0;
                            $due = max(0, $col['total_collected'] - $handed);
                        ?>
                        <tr>
                            <td class="text-slate-400" style="font-size: 11px; padding: 11px 16px;"><?= $i++ ?></td>
                            <td style="padding: 11px 16px;">
                                <div class="text-slate-800" style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($col['processed_by'] ?? 'System') ?></div>
                            </td>
                            <td class="text-end" style="padding: 11px 16px;">
                                <div class="text-slate-800" style="font-size: 13px; font-weight: 600;">৳<?= number_format($col['total_collected'], 2) ?></div>
                            </td>
                            <td class="text-end" style="padding: 11px 16px;">
                                <div class="text-success" style="font-size: 13px; font-weight: 600;">৳<?= number_format($handed, 2) ?></div>
                            </td>
                            <td class="text-end" style="padding: 11px 16px;">
                                <div class="text-danger" style="font-size: 13px; font-weight: 600;">৳<?= number_format($due, 2) ?></div>
                            </td>
                            <td class="text-center" style="padding: 11px 16px;">
                                <?php if (has_permission('amount_collect_ledger_menu')): ?>
                                <button class="btn btn-sm btn-outline-primary border-slate-200 fw-medium view-ledger-btn shadow-sm rounded-pill px-3 py-1" style="font-size: 11px;" data-processor="<?= htmlspecialchars($col['processed_by'] ?? 'System') ?>">
                                    <i class="bi bi-wallet2 me-1"></i> Ledger
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-4" style="font-size: 12px;">No collection records found matching your filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Container -->
        <div class="p-3 border-top border-slate-100 d-flex justify-content-between align-items-center bg-white custom-paging-container" id="custom-paging-container">
        </div>
    </div>
</div>

<!-- Ledger Modal -->
<div class="modal fade" id="ledgerModal" tabindex="-1" aria-labelledby="ledgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-bottom: 1px solid #cbd5e1 !important;">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-wallet2 text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-slate-800 mb-1" id="ledgerModalLabel" style="font-size: 20px;">Processor Ledger</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-slate-500 me-2" style="font-size: 13px;">Overview for:</span>
                            <span class="badge bg-primary px-3 py-1 fw-semibold shadow-sm" style="font-size: 12px; border-radius: 6px;"><i class="bi bi-person-fill me-1"></i> <span id="ledgerProcessorName"></span></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 10px;"></button>
                </div>
            </div>
            <div class="modal-body pt-4 px-4 pb-4 bg-white">
                <div id="ledgerContent" class="position-relative" style="min-height: 200px;">
                    <!-- Ledger content will be loaded here via AJAX -->
                    <div class="d-flex justify-content-center align-items-center h-100 position-absolute w-100 top-0 start-0 bg-white" id="ledgerLoader" style="display: none !important;">
                        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collect Amount Modal -->
<div class="modal fade" id="collectModal" tabindex="-1" aria-labelledby="collectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-3 pt-4 px-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid #bbf7d0 !important;">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-cash-stack text-success fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-slate-800 mb-0" id="collectModalLabel" style="font-size: 18px;">Process Handover</h5>
                        <p class="text-slate-500 mb-0" style="font-size: 12px;">Receive cash from processor</p>
                    </div>
                    <button type="button" class="btn-close shadow-none ms-auto mb-auto" data-bs-dismiss="modal" aria-label="Close" style="background-color: white; border-radius: 50%; padding: 10px;"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-white">
                <form id="collectForm">
                    <input type="hidden" name="processor_name" id="collectProcessorName">
                    <input type="hidden" name="for_date" id="collectForDate">
                    
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Processor & Date</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-slate-400"><i class="bi bi-person"></i></span>
                            <input type="text" id="collectProcessorDisplay" class="form-control bg-light border-start-0 ps-0 text-slate-700 fw-medium" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Due Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-slate-400">৳</span>
                                <input type="text" id="collectDueAmount" class="form-control bg-light border-start-0 ps-0 text-danger fw-bold" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-slate-600 fw-semibold" style="font-size: 12px;">Amount to Collect <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm shadow-sm rounded">
                                <span class="input-group-text bg-white border-success border-end-0 text-success fw-bold">৳</span>
                                <input type="number" name="amount" id="collectInputAmount" class="form-control border-success border-start-0 ps-0 fw-bold text-success" style="font-size: 14px;" step="0.01" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm py-2" id="collectSubmitBtn" style="border-radius: 8px;">
                        <i class="bi bi-check2-circle me-1"></i> Confirm Collection
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Payments Modal -->
<div class="modal fade" id="viewPaymentsModal" tabindex="-1" aria-labelledby="viewPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="viewPaymentsModalLabel">Payment Records for <span id="vpDateDisplay" class="text-primary"></span></h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div id="viewPaymentsContent" class="position-relative" style="min-height: 200px;">
                    <!-- Content loaded via AJAX -->
                    <div class="d-flex justify-content-center align-items-center h-100 position-absolute w-100 top-0 start-0 bg-white" id="vpLoader" style="display: none !important;">
                        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Trigger Ledger Modal
        $('.view-ledger-btn').on('click', function() {
            var processor = $(this).data('processor');
            $('#ledgerProcessorName').text(processor);
            $('#ledgerContent').children().not('#ledgerLoader').remove();
            $('#ledgerLoader').attr('style', 'display: flex !important;');
            
            var modal = new bootstrap.Modal(document.getElementById('ledgerModal'));
            modal.show();

            $.ajax({
                url: 'ajax/get_processor_ledger.php',
                type: 'POST',
                data: { processed_by: processor },
                success: function(response) {
                    $('#ledgerLoader').attr('style', 'display: none !important;');
                    $('#ledgerContent').append(response);
                },
                error: function() {
                    $('#ledgerLoader').attr('style', 'display: none !important;');
                    $('#ledgerContent').append('<div class="alert alert-danger">An error occurred while loading the ledger.</div>');
                }
            });
        });

        // Trigger Collect Modal from within the Ledger Modal
        $(document).on('click', '.trigger-handover-btn', function() {
            var processor = $(this).data('processor');
            var due = $(this).data('due');
            
            // Hide ledger modal, show collect modal
            var ledgerModalEl = document.getElementById('ledgerModal');
            var ledgerModal = bootstrap.Modal.getInstance(ledgerModalEl);
            if (ledgerModal) {
                ledgerModal.hide();
            }
            
            $('#collectProcessorName').val(processor);
            $('#collectProcessorDisplay').val(processor);
            $('#collectForDate').val(''); // Empty for general handover
            $('#collectDueAmount').val(due);
            $('#collectInputAmount').val(due);
            $('#collectInputAmount').attr('max', due);
            
            var collectModal = new bootstrap.Modal(document.getElementById('collectModal'));
            collectModal.show();
        });

        // Trigger Collect Modal for a specific date
        $(document).on('click', '.trigger-handover-date-btn', function() {
            var processor = $(this).data('processor');
            var date = $(this).data('date');
            var due = $(this).data('due');
            
            // Hide ledger modal, show collect modal
            var ledgerModalEl = document.getElementById('ledgerModal');
            var ledgerModal = bootstrap.Modal.getInstance(ledgerModalEl);
            if (ledgerModal) {
                ledgerModal.hide();
            }
            
            $('#collectProcessorName').val(processor);
            $('#collectProcessorDisplay').val(processor + ' (For ' + date + ')');
            $('#collectForDate').val(date);
            $('#collectDueAmount').val(due);
            $('#collectInputAmount').val(due);
            $('#collectInputAmount').attr('max', due);
            
            var collectModal = new bootstrap.Modal(document.getElementById('collectModal'));
            collectModal.show();
        });

        $('#collectForm').on('submit', function(e) {
            e.preventDefault();
            var submitBtn = $('#collectSubmitBtn');
            submitBtn.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: 'ajax/collect_from_processor.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert(res.message || 'Error collecting amount.');
                        submitBtn.prop('disabled', false).text('Confirm Collection');
                    }
                },
                error: function() {
                    alert('An error occurred during submission.');
                    submitBtn.prop('disabled', false).text('Confirm Collection');
                }
            });
        });

        // Trigger View Payments Modal
        $(document).on('click', '.trigger-view-payments-btn', function() {
            var processor = $(this).data('processor');
            var date = $(this).data('date');
            
            $('#vpDateDisplay').text(date);
            $('#viewPaymentsContent').children().not('#vpLoader').remove();
            $('#vpLoader').attr('style', 'display: flex !important;');
            
            var modal = new bootstrap.Modal(document.getElementById('viewPaymentsModal'));
            modal.show();

            $.ajax({
                url: 'ajax/get_date_payments.php',
                type: 'POST',
                data: { processed_by: processor, date: date },
                success: function(response) {
                    $('#vpLoader').attr('style', 'display: none !important;');
                    $('#viewPaymentsContent').append(response);
                },
                error: function() {
                    $('#vpLoader').attr('style', 'display: none !important;');
                    $('#viewPaymentsContent').append('<div class="alert alert-danger">Error loading payments.</div>');
                }
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
