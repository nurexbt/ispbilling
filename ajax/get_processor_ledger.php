<?php
if ((isset($_POST['is_manager']) && $_POST['is_manager'] == 1) || (isset($_POST['is_owner']) && $_POST['is_owner'] == 1)) {
    $is_manager = true;
    $is_owner = true;
}
require_once '../includes/db.php';
require_once '../includes/auth.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

$processed_by = $_POST['processed_by'] ?? '';

if (empty($processed_by)) {
    echo "Processor name is required.";
    exit;
}

$is_manager_ledger = (isset($_POST['is_manager_ledger']) && $_POST['is_manager_ledger'] == 1);
if (!$is_manager_ledger) {
    // Check if processed_by has manager role
    $chk_stmt = $conn->prepare("SELECT pu.id FROM portal_user_tbl pu LEFT JOIN role_tbl r ON pu.role_id = r.id WHERE pu.username = ? AND (LOWER(r.role_name) LIKE '%manager%' OR r.permissions LIKE '%\"manager_full_access\":1%' OR r.permissions LIKE '%\"manager_full_access\":\"1\"%')");
    $chk_stmt->bind_param("s", $processed_by);
    $chk_stmt->execute();
    if ($chk_stmt->get_result()->num_rows > 0) {
        $is_manager_ledger = true;
    }
    $chk_stmt->close();
}

$date_records = [];
$total_collected = 0;
$total_from_collectors = 0;
$total_direct = 0;

if ($is_manager_ledger) {
    // 1. Collections received by manager from collectors
    $stmt1 = $conn->prepare("SELECT DATE(handover_date) as c_date, SUM(amount) as total_amount FROM collector_handover_tbl WHERE received_by = ? GROUP BY DATE(handover_date)");
    $stmt1->bind_param("s", $processed_by);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    while ($r1 = $res1->fetch_assoc()) {
        $d = $r1['c_date'];
        if (!isset($date_records[$d])) {
            $date_records[$d] = [
                'date' => $d,
                'collected' => 0,
                'handover' => 0,
                'due' => 0,
                'from_collectors' => 0,
                'direct' => 0
            ];
        }
        $date_records[$d]['from_collectors'] += $r1['total_amount'];
        $date_records[$d]['collected'] += $r1['total_amount'];
        $date_records[$d]['due'] += $r1['total_amount'];
        $total_collected += $r1['total_amount'];
        $total_from_collectors += $r1['total_amount'];
    }
    $stmt1->close();

    // 2. Direct customer collections by manager
    $stmt2 = $conn->prepare("SELECT DATE(payment_date) as c_date, SUM(amount) as total_amount FROM payment_tbl WHERE processed_by = ? GROUP BY DATE(payment_date)");
    $stmt2->bind_param("s", $processed_by);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r2 = $res2->fetch_assoc()) {
        $d = $r2['c_date'];
        if (!isset($date_records[$d])) {
            $date_records[$d] = [
                'date' => $d,
                'collected' => 0,
                'handover' => 0,
                'due' => 0,
                'from_collectors' => 0,
                'direct' => 0
            ];
        }
        $date_records[$d]['direct'] += $r2['total_amount'];
        $date_records[$d]['collected'] += $r2['total_amount'];
        $date_records[$d]['due'] += $r2['total_amount'];
        $total_collected += $r2['total_amount'];
        $total_direct += $r2['total_amount'];
    }
    $stmt2->close();
} else {
    // Standard collector query
    $col_query = "SELECT 
        DATE(payment_date) as c_date,
        SUM(amount) as total_amount
      FROM payment_tbl
      WHERE processed_by = ?
      GROUP BY DATE(payment_date)
      ORDER BY c_date DESC";

    $col_stmt = $conn->prepare($col_query);
    $col_stmt->bind_param("s", $processed_by);
    $col_stmt->execute();
    $col_res = $col_stmt->get_result();

    while($row = $col_res->fetch_assoc()){
        $d = $row['c_date'];
        $date_records[$d] = [
            'date' => $d,
            'collected' => $row['total_amount'],
            'handover' => 0,
            'due' => $row['total_amount'],
            'from_collectors' => 0,
            'direct' => $row['total_amount']
        ];
        $total_collected += $row['total_amount'];
    }
    $col_stmt->close();
}

// Fetch Handovers
$ho_query = "SELECT 
    id,
    for_date,
    handover_date,
    amount,
    received_by
  FROM collector_handover_tbl
  WHERE processor_name = ?
  ORDER BY handover_date DESC";

$ho_stmt = $conn->prepare($ho_query);
$ho_stmt->bind_param("s", $processed_by);
$ho_stmt->execute();
$ho_res = $ho_stmt->get_result();

$handovers = [];
$total_handover = 0;
while($row = $ho_res->fetch_assoc()){
    $handovers[] = $row;
    $total_handover += $row['amount'];
    
    // Deduct from date_records if for_date exists
    if (!empty($row['for_date']) && isset($date_records[$row['for_date']])) {
        $date_records[$row['for_date']]['handover'] += $row['amount'];
        $date_records[$row['for_date']]['due'] -= $row['amount'];
    }
}
$ho_stmt->close();

// Sort date records descending
krsort($date_records);

$due = max(0, $total_collected - $total_handover);
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="p-3 rounded shadow-sm d-flex align-items-center" style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-left: 4px solid #38bdf8;">
            <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 44px; height: 44px;">
                <i class="bi bi-cash-coin fs-5" style="color: #0ea5e9;"></i>
            </div>
            <div>
                <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Collected Amount</div>
                <div class="fw-bold" style="font-size: 18px; color: #0284c7; line-height: 1;">৳<?= number_format($total_collected, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="p-3 rounded shadow-sm d-flex align-items-center" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #4ade80;">
            <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 44px; height: 44px;">
                <i class="bi bi-box-arrow-in-down fs-5" style="color: #22c55e;"></i>
            </div>
            <div>
                <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Handover Amount</div>
                <div class="fw-bold" style="font-size: 18px; color: #16a34a; line-height: 1;">৳<?= number_format($total_handover, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="p-3 rounded shadow-sm d-flex align-items-center" style="background-color: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #fb7185;">
            <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm me-3" style="width: 44px; height: 44px;">
                <i class="bi bi-exclamation-circle fs-5" style="color: #f43f5e;"></i>
            </div>
            <div>
                <div class="text-slate-500 mb-1" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Due</div>
                <div class="fw-bold" style="font-size: 18px; color: #e11d48; line-height: 1;">৳<?= number_format($due, 2) ?></div>
            </div>
        </div>
    </div>
</div>


<div class="row g-3">
    <!-- Collections Table -->
    <div class="col-md-6">
        <h6 class="fw-bold text-slate-700 mb-2" style="font-size: 13px;"><i class="bi bi-arrow-down-circle text-primary me-1"></i> Collection Records</h6>
        <div class="table-responsive">
            <table id="ledgerCollectionTable" class="table table-sm align-middle table-hover mb-0" style="border-collapse: collapse; border: 1px solid #e2e8f0; width:100%;">
                <thead style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); position: sticky; top: 0; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Date</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Collected</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Handover</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Due</th>
                        <th class="border-0 text-slate-600 fw-bold text-center" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($date_records) > 0): ?>
                        <?php foreach($date_records as $rec): ?>
                        <tr>
                            <td style="font-size: 11px; padding: 8px;"><?= date('d M, y', strtotime($rec['date'])) ?></td>
                            <td class="text-success fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($rec['collected'], 2) ?></td>
                            <td class="text-danger fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($rec['handover'], 2) ?></td>
                            <td class="text-info fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($rec['due'], 2) ?></td>
                            <td class="text-center" style="padding: 8px;">
                                <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 me-1 trigger-view-payments-btn" style="font-size: 10px;" data-processor="<?= htmlspecialchars($processed_by) ?>" data-date="<?= $rec['date'] ?>" data-is-manager="<?= $is_manager_ledger ? '1' : '0' ?>" title="View Payments">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if($rec['due'] > 0 && has_permission('amount_collect_handover')): ?>
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-1 trigger-handover-date-btn" style="font-size: 10px;" data-processor="<?= htmlspecialchars($processed_by) ?>" data-date="<?= $rec['date'] ?>" data-due="<?= $rec['due'] ?>" title="Handover">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Handovers Table -->
    <div class="col-md-6">
        <h6 class="fw-bold text-slate-700 mb-2" style="font-size: 13px;"><i class="bi bi-arrow-up-circle text-danger me-1"></i> Handover Records</h6>
        <div class="table-responsive">
            <table id="ledgerHandoverTable" class="table table-sm align-middle table-hover mb-0" style="border-collapse: collapse; border: 1px solid #e2e8f0; width:100%;">
                <thead style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); position: sticky; top: 0; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Date</th>
                        <th class="border-0 text-slate-600 fw-bold" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Received By</th>
                        <th class="border-0 text-slate-600 fw-bold text-end" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Amount</th>
                        <th class="border-0 text-slate-600 fw-bold text-center" style="font-size: 11px; padding: 12px 10px; letter-spacing: 0.3px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($handovers) > 0): ?>
                        <?php foreach($handovers as $ho): ?>
                        <tr>
                            <td style="font-size: 11px; padding: 8px;"><?= date('d M, y h:i A', strtotime($ho['handover_date'])) ?></td>
                            <td style="font-size: 11px; padding: 8px;"><?= htmlspecialchars($ho['received_by']) ?></td>
                            <td class="text-end text-danger fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($ho['amount'], 2) ?></td>
                            <td class="text-center" style="padding: 8px; white-space: nowrap;">
                                <?php $print_base = isset($is_manager) && $is_manager ? '../' : ''; ?>
                                <a href="<?= $print_base ?>print_handover_receipt.php?id=<?= $ho['id'] ?>&type=pos" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-1 me-1" style="font-size: 10px;" title="POS Print (Bluetooth)">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a href="<?= $print_base ?>print_handover_receipt.php?id=<?= $ho['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size: 10px;" title="Normal Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    if ($.fn.DataTable) {
        $('#ledgerCollectionTable, #ledgerHandoverTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "pageLength": 10,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search records..."
            },
            "dom": '<"d-flex justify-content-end mb-2"f>rt<"d-flex justify-content-between align-items-center mt-2"i<"d-flex align-items-center"<"d-none"p><"custom-paging-container d-flex align-items-center gap-2">>>',
            "destroy": true
        });
        
        // Add custom styling to datatables search inputs inside the modal
        $('.dataTables_filter input').addClass('form-control form-control-sm shadow-none').css({'font-size':'11px', 'border-color':'#e2e8f0', 'border-radius':'4px'});
        $('.dataTables_info').css('font-size', '10px');
    }
</script>
