<?php
/**
 * ajax/get_customers_dt.php
 * DataTables server-side processing for the customer table.
 * Supports: search, sort, pagination, billing/router/nid/special/date filters.
 */
require_once '../includes/db.php';
header('Content-Type: application/json');

// ── Auth check ────────────────────────────────────────────────────────────────
if (!has_permission('customer_view')) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// ── DataTables params ─────────────────────────────────────────────────────────
$draw   = intval($_POST['draw']   ?? 1);
$start  = intval($_POST['start']  ?? 0);
$length = intval($_POST['length'] ?? 25);
$search = trim($_POST['search']['value'] ?? '');

// Sort column
$orderColIdx = intval($_POST['order'][0]['column'] ?? 0);
$orderDir    = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderableColumns = [
    0 => 'c.id',
    1 => 'c.name',
    2 => 'c.phone',
    3 => 'a.area_name',
    4 => 'p.given_name',
    5 => 'c.billing_date',
    6 => '(c.advance_payment - c.previous_due)',
    7 => '(c.advance_payment - c.previous_due)',
];
$orderBy = $orderableColumns[$orderColIdx] ?? 'c.id';

// ── Custom filter params ──────────────────────────────────────────────────────
$filterBilling = trim($_POST['filter_billing'] ?? '');   // 'Paid' | 'Unpaid' | ''
$filterRouter  = trim($_POST['filter_router']  ?? '');   // 'Enabled' | 'Disabled' | ''
$filterNid     = trim($_POST['filter_nid']     ?? '');   // 'Yes'(missing) | 'No' | ''
$filterSpecial = trim($_POST['filter_special'] ?? '');   // 'Yes' | 'No' | ''
$filterDateFrom = trim($_POST['filter_date_from'] ?? '');
$filterDateTo   = trim($_POST['filter_date_to']   ?? '');

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(c.name LIKE ? OR c.username LIKE ? OR c.phone LIKE ? OR c.onu_sn LIKE ? OR a.area_name LIKE ? OR p.given_name LIKE ?)';
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
    $types .= 'ssssss';
}

if ($filterBilling === 'Paid') {
    $where[] = '(c.advance_payment - c.previous_due) >= 0';
} elseif ($filterBilling === 'Unpaid') {
    $where[] = '(c.advance_payment - c.previous_due) < 0';
}

if ($filterRouter === 'Enabled') {
    $where[] = "c.pppoe_status != 'disable'";
} elseif ($filterRouter === 'Disabled') {
    $where[] = "c.pppoe_status = 'disable'";
}

if ($filterNid === 'Yes') {    // NID Missing = Yes → nid_no is empty
    $where[] = "(c.nid_no IS NULL OR c.nid_no = '')";
} elseif ($filterNid === 'No') {
    $where[] = "(c.nid_no IS NOT NULL AND c.nid_no != '')";
}

if ($filterSpecial === 'Yes') {
    $where[] = 'c.is_special = 1';
} elseif ($filterSpecial === 'No') {
    $where[] = '(c.is_special IS NULL OR c.is_special = 0)';
}

if ($filterDateFrom !== '') {
    $where[] = 'c.billing_date >= ?';
    $params[] = $filterDateFrom;
    $types .= 's';
}
if ($filterDateTo !== '') {
    $where[] = 'c.billing_date <= ?';
    $params[] = $filterDateTo;
    $types .= 's';
}

$whereStr = implode(' AND ', $where);

// ── Base query ────────────────────────────────────────────────────────────────
$baseJoin = "FROM customer_tbl c
    LEFT JOIN area_tbl a    ON c.area_id    = a.id
    LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
    LEFT JOIN host_tbl h    ON c.router_id  = h.id
    LEFT JOIN package_tbl p ON c.package_id = p.id";

// Total records (no filter)
$totalRes = $conn->query("SELECT COUNT(*) AS cnt FROM customer_tbl c $baseJoin");
// Wait – the joins don't need c prefix in FROM; re-do:
$totalRes = $conn->query("SELECT COUNT(*) AS cnt FROM customer_tbl c
    LEFT JOIN area_tbl a    ON c.area_id    = a.id
    LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
    LEFT JOIN host_tbl h    ON c.router_id  = h.id
    LEFT JOIN package_tbl p ON c.package_id = p.id");
$totalRecords = $totalRes ? intval($totalRes->fetch_assoc()['cnt']) : 0;

// Filtered count
$countSql = "SELECT COUNT(*) AS cnt $baseJoin WHERE $whereStr";
if ($params) {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $filteredRecords = intval($stmt->get_result()->fetch_assoc()['cnt']);
    $stmt->close();
} else {
    $filteredRecords = intval($conn->query($countSql)->fetch_assoc()['cnt']);
}

// Data query
$dataSql = "SELECT c.*, a.area_name, s.subarea_name, h.device_name AS router_name, p.given_name AS package_name
    $baseJoin
    WHERE $whereStr
    ORDER BY $orderBy $orderDir
    LIMIT ?, ?";
$dataParams = array_merge($params, [$start, $length]);
$dataTypes  = $types . 'ii';

$stmt = $conn->prepare($dataSql);
$stmt->bind_param($dataTypes, ...$dataParams);
$stmt->execute();
$result = $stmt->get_result();

// ── Build rows ────────────────────────────────────────────────────────────────
$data = [];
$i = $start + 1;

while ($row = $result->fetch_assoc()) {
    $balance      = $row['advance_payment'] - $row['previous_due'];
    $isPaid       = $balance >= 0;
    $statusText   = $isPaid ? 'Paid' : 'Unpaid';
    $statusClass  = $isPaid ? 'bg-success' : 'bg-danger';
    $balanceClass = $balance > 0 ? 'text-success fw-bold' : ($balance < 0 ? 'text-danger fw-bold' : 'text-slate-500');
    $balanceFmt   = ($balance >= 0 ? '+' : '') . '৳' . number_format($balance, 2);
    $pppoeDisabled = ($row['pppoe_status'] ?? '') === 'disable';

    // Col 0: #
    $col0 = '<span class="text-slate-500" style="font-size:11px;">' . $i++ . '</span>';

    // Col 1: Client Details
    $specialStar = !empty($row['is_special'])
        ? ' <i class="bi bi-star-fill text-warning ms-1" title="Special User" style="font-size:11px;"></i>' : '';
    $onuHtml = !empty($row['onu_sn'])
        ? '<div class="text-slate-500 mt-1" style="font-size:10px;"><span class="fw-semibold">ONU:</span> ' . htmlspecialchars($row['onu_sn']) . '</div>' : '';
    $col1 = '<div class="text-slate-900" style="font-size:13px;font-weight:500;">' . htmlspecialchars($row['name']) . $specialStar . '</div>'
          . '<div class="text-slate-500 mt-1" style="font-size:11px;"><span class="fw-semibold">User:</span> ' . htmlspecialchars($row['username']) . ', <span class="fw-semibold">Pass:</span> ' . htmlspecialchars($row['password']) . '</div>'
          . $onuHtml;

    // Col 2: Contact
    $emailHtml = !empty($row['email'])
        ? '<div class="text-slate-500" style="font-size:11px;"><i class="bi bi-envelope text-slate-400 me-1"></i>' . htmlspecialchars($row['email']) . '</div>' : '';
    $nidHtml = !empty($row['nid_no'])
        ? '<div class="text-slate-500 mt-1" style="font-size:11px;"><i class="bi bi-person-badge text-slate-400 me-1"></i>NID: ' . htmlspecialchars($row['nid_no']) . '</div>' : '';
    $col2 = '<div class="text-slate-700" style="font-size:12px;"><i class="bi bi-telephone text-slate-400 me-1"></i>' . htmlspecialchars($row['phone']) . '</div>'
          . $emailHtml . $nidHtml;

    // Col 3: Location
    $subareaHtml = !empty($row['subarea_name'])
        ? ' <i class="bi bi-chevron-right text-slate-300" style="font-size:10px;"></i> ' . htmlspecialchars($row['subarea_name']) : '';
    $col3 = '<div class="text-slate-700" style="font-size:12px;font-weight:500;">' . htmlspecialchars($row['area_name'] ?? '') . $subareaHtml . '</div>'
          . '<div class="text-slate-500 mt-1" style="font-size:11px;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' . htmlspecialchars($row['address'] ?? '') . '">' . htmlspecialchars($row['address'] ?? '') . '</div>';

    // Col 4: Service Details
    $discountHtml = ($row['discount'] > 0)
        ? '<div class="text-success mt-1" style="font-size:10px;font-weight:500;"><i class="bi bi-tag-fill me-1"></i>Discount: ৳' . htmlspecialchars($row['discount']) . '</div>' : '';
    $col4 = '<div class="text-slate-700" style="font-size:12px;"><span class="fw-semibold">Router:</span> ' . htmlspecialchars($row['router_name'] ?? 'N/A') . '</div>'
          . '<div class="text-slate-500" style="font-size:11px;"><span class="fw-semibold">Package:</span> ' . htmlspecialchars($row['package_name'] ?? 'N/A') . ' - ৳' . htmlspecialchars($row['package_price']) . '</div>'
          . $discountHtml;

    // Col 5: Dates
    $col5 = '<div class="text-slate-700" style="font-size:11px;"><span class="fw-semibold">Conn:</span> ' . htmlspecialchars($row['connection_date']) . '</div>'
          . '<div class="text-slate-700 mt-1" style="font-size:11px;"><span class="fw-semibold">Bill:</span> ' . htmlspecialchars($row['billing_date']) . '</div>'
          . '<div class="text-slate-700 mt-1" style="font-size:11px;"><span class="fw-semibold">Cycle:</span> ' . htmlspecialchars($row['payment_cycle_date']) . '</div>';

    // Col 6: Balance
    $col6 = '<div class="' . $balanceClass . '" style="font-size:12px;">' . $balanceFmt . '</div>';

    // Col 7: Status
    $col7 = '<span class="d-none">status-' . strtolower($statusText) . ' ' . ($pppoeDisabled ? 'pppoe-disabled' : 'pppoe-enabled') . '</span>'
          . '<div class="badge ' . $statusClass . ' text-white px-2 py-1" style="font-size:11px;border-radius:4px;font-weight:500;">' . $statusText . '</div>'
          . '<div class="router-status-badge mt-1" data-username="' . htmlspecialchars($row['username']) . '">'
          . '<span class="badge bg-light text-slate-400 border border-slate-200" style="font-size:9px;"><span class="spinner-border spinner-border-sm" style="width:8px;height:8px;border-width:1px;"></span> Router</span>'
          . '</div>';

    // Col 8: Action
    $canEdit    = has_permission('customer_edit');
    $canDelete  = has_permission('customer_delete');
    $canLedger  = has_permission('customer_ledger');
    $canSupport = has_permission('support_ticket_view');
    $col8 = '';
    if ($canEdit || $canDelete || $canLedger || $canSupport) {
        $ledgerItem  = $canLedger  ? '<li><a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 view-ledger" data-id="' . $row['id'] . '"><i class="bi bi-wallet2 text-primary" style="width:18px;font-size:14px;text-align:center;"></i><span>Customer Ledger</span></a></li>' : '';
        $editItem    = $canEdit    ? '<li><a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 edit-customer" data-id="' . $row['id'] . '"><i class="bi bi-pencil-square text-success" style="width:18px;font-size:14px;text-align:center;"></i><span>Edit Customer</span></a></li>' : '';
        $ticketItem  = '<li><a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 view-tickets" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '" data-username="' . htmlspecialchars($row['username']) . '"><i class="bi bi-ticket-detailed text-warning" style="width:18px;font-size:14px;text-align:center;"></i><span>Ticket</span></a></li>';
        $col8 = '<div class="dropdown d-inline-block">'
              . '<button type="button" class="btn btn-sm customer-action-btn shadow-sm" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Customer Actions"><i class="bi bi-three-dots-vertical"></i></button>'
              . '<ul class="dropdown-menu dropdown-menu-end customer-action-dropdown shadow-lg border-0">'
              . $ledgerItem . $editItem . $ticketItem
              . '</ul></div>';
    }

    $data[] = [
        'DT_RowId'    => 'row_' . $row['id'],
        'DT_RowAttr'  => [],
        0 => $col0,
        1 => $col1,
        2 => $col2,
        3 => $col3,
        4 => $col4,
        5 => $col5,
        6 => $col6,
        7 => $col7,
        8 => $col8,
    ];
}
$stmt->close();

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data'            => $data,
]);
