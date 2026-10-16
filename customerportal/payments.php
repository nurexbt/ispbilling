<?php
$page_title = 'Payment Records';
include 'includes/header.php';

// Fetch filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build query for invoices
$where_clauses = ["customer_id = ?"];
$params = [$customer_id];
$types = "i";

if (!empty($filter_date_from)) {
    $where_clauses[] = "created_at >= ?";
    $params[] = $filter_date_from . ' 00:00:00';
    $types .= "s";
}
if (!empty($filter_date_to)) {
    $where_clauses[] = "created_at <= ?";
    $params[] = $filter_date_to . ' 23:59:59';
    $types .= "s";
}
if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT id, created_at as date_val, description, amount, paid_amount, (amount - paid_amount) as due_amount, status FROM invoice_tbl WHERE $where_sql ORDER BY created_at DESC, id DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$invoices = [];
while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt->close();

// Fetch additional stats
$stmt = $conn->prepare("SELECT SUM(amount) as grand_total FROM payment_tbl WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$payment_stats = $res->fetch_assoc();
$grand_total = $payment_stats['grand_total'] ?? 0;
$stmt->close();
$stmt = $conn->prepare("SELECT previous_due FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$customer_data = $res->fetch_assoc();
$total_due = $customer_data['previous_due'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid_bill,
    SUM(CASE WHEN status != 'paid' THEN (amount - paid_amount) ELSE 0 END) as total_unpaid_bill
    FROM invoice_tbl WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$inv_stats = $res->fetch_assoc();
$total_paid_bill = $inv_stats['total_paid_bill'] ?? 0;
$total_unpaid_bill = $inv_stats['total_unpaid_bill'] ?? 0;
$stmt->close();
?>

<div class="container-fluid pt-2">
    <!-- Summary Cards -->
    <div class="row g-3 mt-1 mb-3">
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #22c55e !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Paid Bill</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_paid_bill, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #f59e0b !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Unpaid Bill</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($total_unpaid_bill, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #3b82f6 !important; border-radius: 10px;">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Paid Amount</div>
                <div class="fw-bold text-slate-900" style="font-size: 16px;">৳<?= number_format($grand_total, 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dashboard-card p-3 text-center border-0" style="border-left: 3px solid #ef4444 !important; border-radius: 10px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
                <div class="text-slate-500 mb-1" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Due</div>
                <div class="fw-bold" style="font-size: 16px; color: #ef4444;">৳<?= number_format($total_due, 2) ?></div>
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
                    <select name="status" class="form-select form-select-sm border-slate-200 shadow-none" style="width: 130px; font-size: 12px;">
                        <option value="">All Statuses</option>
                        <option value="paid" <?= $filter_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="partial" <?= $filter_status === 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="unpaid" <?= $filter_status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary shadow-none px-3" style="font-size: 12px;"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="payments.php" class="btn btn-sm btn-light border-slate-200 shadow-none px-3" style="font-size: 12px;"><i class="bi bi-x-circle me-1"></i>Clear</a>
                </form>
            </div>
            <div>
                <input type="text" id="searchPaymentRecord" class="toolbar-input form-control form-control-sm border-slate-200 shadow-none" placeholder="Search bills..." style="width: 200px; font-size: 13px;">
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="paymentRecordTable" class="table align-middle mb-0" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">#</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Description</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Total Amount</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Paid Amount</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Due Amount</th>
                        <th class="border-0 text-slate-500 text-center" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                        <th class="border-0 text-slate-500 text-end" style="font-size: 11px; padding: 12px 16px; text-transform: uppercase; letter-spacing: 0.5px;">Action</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-slate-200);">
                    <?php if (count($invoices) > 0): ?>
                        <?php $i = 1; foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="text-slate-400" style="font-size: 11px; padding: 11px 16px;"><?= $i++ ?></td>
                            <td style="padding: 11px 16px;">
                                <div class="text-slate-700" style="font-size: 12px; font-weight: 500;"><?= date('d M Y, h:i A', strtotime($inv['date_val'])) ?></div>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="text-slate-600" style="font-size: 12px;"><?= htmlspecialchars($inv['description']) ?></span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="text-slate-800" style="font-size: 13px;">৳<?= number_format($inv['amount'], 2) ?></span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="text-success" style="font-size: 13px;">৳<?= number_format($inv['paid_amount'], 2) ?></span>
                            </td>
                            <td style="padding: 11px 16px;">
                                <span class="text-danger fw-bold" style="font-size: 13px;">৳<?= number_format($inv['due_amount'], 2) ?></span>
                            </td>
                            <td class="text-center" style="padding: 11px 16px;">
                                <?php if ($inv['status'] === 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">Paid</span>
                                <?php elseif ($inv['status'] === 'partial'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size: 11px;">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="padding: 11px 16px;">
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if ($inv['due_amount'] > 0): ?>
                                        <button class="btn btn-sm btn-primary shadow-none py-1 px-2" style="font-size: 12px; font-weight: 500;">
                                            <i class="bi bi-credit-card me-1"></i> Pay Bill
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-light border-slate-200 shadow-none text-slate-600 py-1 px-2 view-invoice-btn" style="font-size: 12px; font-weight: 500;" title="View Invoice" data-url="../print_invoice.php?id=<?= $inv['id'] ?>&format=pos">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom border-slate-100 py-3">
                <h6 class="modal-title fw-bold text-slate-800"><i class="bi bi-receipt me-2"></i>Invoice Details</h6>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="invoiceIframe" src="" style="width: 100%; height: 600px; border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<style>
    .bg-purple { background-color: #a855f7 !important; }

    /* Clean Pagination Design */
    div.dt-container .pagination {
        gap: 2px;
    }
    div.dt-container .page-item .page-link {
        border: none !important;
        background: transparent !important;
        color: #64748b;
        padding: 4px 10px;
        font-size: 16px;
        border-radius: 6px !important;
    }
    div.dt-container .page-item:not(.disabled) .page-link:hover {
        background-color: #f1f5f9 !important;
        color: #0ea5e9;
    }
    div.dt-container .page-item.active .page-link {
        background-color: #e0f2fe !important;
        color: #0ea5e9;
        font-weight: bold;
    }
    div.dt-container .page-item.disabled .page-link {
        background: transparent !important;
        color: #cbd5e1;
    }
</style>
<script>
$(document).ready(function() {
    var dt = $('#paymentRecordTable').DataTable({
        "pageLength": 25,
        "lengthChange": false,
        "layout": {
            topStart: null,
            topEnd: null
        },
        "order": [],
        "language": {
            "emptyTable": "<div class='text-center text-slate-400 py-4'><i class='bi bi-receipt d-block mb-2' style='font-size: 2rem; opacity: 0.3;'></i>No payment records found.</div>"
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });

    $('#searchPaymentRecord').on('keyup', function() {
        dt.search(this.value).draw();
    });

    $(document).on('click', '.view-invoice-btn', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        $('#invoiceIframe').attr('src', url);
        var myModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
        myModal.show();
    });
});
</script>