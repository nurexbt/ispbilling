<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

$processed_by = $_POST['processed_by'] ?? '';
$date = $_POST['date'] ?? '';

if (empty($processed_by) || empty($date)) {
    echo "Required parameters missing.";
    exit;
}

$is_manager = (isset($_POST['is_manager']) && $_POST['is_manager'] == 1) || (isset($_POST['is_manager_ledger']) && $_POST['is_manager_ledger'] == 1);
if (!$is_manager) {
    // Check if user is a manager
    $chk_stmt = $conn->prepare("SELECT pu.id FROM portal_user_tbl pu LEFT JOIN role_tbl r ON pu.role_id = r.id WHERE pu.username = ? AND (LOWER(r.role_name) LIKE '%manager%' OR r.permissions LIKE '%\"manager_full_access\":1%' OR r.permissions LIKE '%\"manager_full_access\":\"1\"%')");
    $chk_stmt->bind_param("s", $processed_by);
    $chk_stmt->execute();
    if ($chk_stmt->get_result()->num_rows > 0) {
        $is_manager = true;
    }
    $chk_stmt->close();
}

$collector_handovers = [];
$total_from_collectors = 0;
if ($is_manager) {
    $ho_stmt = $conn->prepare("SELECT id, handover_date, amount, processor_name, for_date FROM collector_handover_tbl WHERE received_by = ? AND DATE(handover_date) = ? ORDER BY handover_date DESC");
    $ho_stmt->bind_param("ss", $processed_by, $date);
    $ho_stmt->execute();
    $ho_res = $ho_stmt->get_result();
    while ($row = $ho_res->fetch_assoc()) {
        $collector_handovers[] = $row;
        $total_from_collectors += $row['amount'];
    }
    $ho_stmt->close();
}

$query = "SELECT 
    p.payment_date,
    p.amount,
    p.payment_method,
    p.for_month,
    c.name AS customer_name
  FROM payment_tbl p
  LEFT JOIN customer_tbl c ON p.customer_id = c.id
  WHERE p.processed_by = ? AND DATE(p.payment_date) = ?
  ORDER BY p.payment_date DESC, p.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $processed_by, $date);
$stmt->execute();
$res = $stmt->get_result();

$direct_payments = [];
$total_direct = 0;
while ($row = $res->fetch_assoc()) {
    $direct_payments[] = $row;
    $total_direct += $row['amount'];
}
$stmt->close();

$grand_date_total = $total_from_collectors + $total_direct;
?>

<?php if ($is_manager && count($collector_handovers) > 0): ?>
    <div class="mb-3">
        <h6 class="fw-bold text-slate-700 mb-2" style="font-size: 12px;"><i class="bi bi-people text-primary me-1"></i> Received From Field Collectors (<?= count($collector_handovers) ?>)</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover mb-0" style="border-collapse: collapse; border: 1px solid #e2e8f0; width:100%;">
                <thead style="background-color: #f1f5f9;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Time</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Collector</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">For Date</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 8px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($collector_handovers as $ho): ?>
                    <tr>
                        <td style="font-size: 11px; padding: 8px;"><?= date('h:i A', strtotime($ho['handover_date'])) ?></td>
                        <td style="font-size: 11px; padding: 8px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($ho['processor_name']) ?></td>
                        <td style="font-size: 11px; padding: 8px; color: #64748b;"><?= !empty($ho['for_date']) ? date('d M, Y', strtotime($ho['for_date'])) : 'General Handover' ?></td>
                        <td class="text-end text-success fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($ho['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f8fafc;">
                        <td colspan="3" class="text-end fw-semibold text-slate-600" style="font-size: 11px; padding: 8px;">Subtotal from Collectors:</td>
                        <td class="text-end text-success fw-bold" style="font-size: 12px; padding: 8px;">৳<?= number_format($total_from_collectors, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (count($direct_payments) > 0 || (!$is_manager || count($collector_handovers) === 0)): ?>
    <div class="mb-3">
        <?php if ($is_manager): ?>
        <h6 class="fw-bold text-slate-700 mb-2" style="font-size: 12px;"><i class="bi bi-wallet2 text-success me-1"></i> Direct Customer Collections (<?= count($direct_payments) ?>)</h6>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover mb-0" style="border-collapse: collapse; border: 1px solid #e2e8f0; width:100%;">
                <thead style="background-color: #f1f5f9; position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Time</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Customer</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Bill Month</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 8px;">Method</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 8px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($direct_payments as $row): ?>
                    <tr>
                        <td style="font-size: 11px; padding: 8px;"><?= date('h:i A', strtotime($row['payment_date'])) ?></td>
                        <td style="font-size: 11px; padding: 8px;"><?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?></td>
                        <td style="font-size: 11px; padding: 8px;"><?= htmlspecialchars($row['for_month']) ?></td>
                        <td style="font-size: 11px; padding: 8px;"><?= htmlspecialchars($row['payment_method']) ?></td>
                        <td class="text-end text-success fw-bold" style="font-size: 11px; padding: 8px;">৳<?= number_format($row['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($direct_payments) === 0): ?>
                    <tr><td colspan="5" class="text-center text-slate-400 py-3" style="font-size: 11px;">No direct customer payments on this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
    <span class="text-slate-700 fw-bold" style="font-size: 12px;">Total Collected for <?= date('d M, Y', strtotime($date)) ?>:</span>
    <span class="fw-bold" style="font-size: 15px; color: #0ea5e9;">৳<?= number_format($grand_date_total, 2) ?></span>
</div>
