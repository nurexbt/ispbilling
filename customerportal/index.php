<?php
$page_title = 'Dashboard';
include 'includes/header.php';

$query = "SELECT c.*, 
                 a.area_name, 
                 s.subarea_name, 
                 p.given_name as package_name
          FROM customer_tbl c
          LEFT JOIN area_tbl a ON c.area_id = a.id
          LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
          LEFT JOIN package_tbl p ON c.package_id = p.id
          WHERE c.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$mac_address = '---';
$ip_address = '---';
$uptime = '---';
$is_online = false;

if (!empty($customer['router_id']) && !empty($customer['username'])) {
    $stmt2 = $conn->prepare("SELECT ip_address, api_port, api_username, api_password FROM host_tbl WHERE id = ?");
    $stmt2->bind_param("i", $customer['router_id']);
    $stmt2->execute();
    $host_res = $stmt2->get_result();
    
    if ($host_res->num_rows > 0) {
        $host = $host_res->fetch_assoc();
        require_once '../includes/routeros_api.class.php';
        $API = new RouterosAPI();
        $API->debug = false;
        $API->port = (int)$host['api_port'];
        
        if ($API->connect($host['ip_address'], $host['api_username'], $host['api_password'])) {
            $API->write('/ppp/active/print', false);
            $API->write('?name=' . $customer['username'], true);
            $READ = $API->read(false);
            $ARRAY = $API->parseResponse($READ);
            
            $is_online = false;
            if (isset($ARRAY['!trap'])) {
                // Leave as '---' when permission denied or error
            } elseif (!empty($ARRAY) && isset($ARRAY[0])) {
                $is_online = true;
                $mac_address = $ARRAY[0]['caller-id'] ?? '---';
                $ip_address = $ARRAY[0]['address'] ?? '---';
                $uptime = $ARRAY[0]['uptime'] ?? '---';
            }
            $API->disconnect();
        }
    }
    $stmt2->close();
}
?>

<div class="row g-4 mb-4">
    <!-- Personal Information Card -->
    <div class="col-md-6 col-lg-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                    <h6 class="mb-0 fw-bolder text-slate-700 text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">PERSONAL INFORMATION</h6>
                </div>
                <hr class="border-slate-200 mb-0">
            </div>
            <div class="card-body p-4">
                <div class="row g-4" style="font-size: 0.9rem;">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['name']) ? htmlspecialchars($customer['name']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-telephone text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Phone</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-envelope text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Email</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-card-heading text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">NID No</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['nid_no']) ? htmlspecialchars($customer['nid_no']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-geo-alt text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Address</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['address']) ? htmlspecialchars($customer['address']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-map text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Area & Subarea</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['area_name']) ? htmlspecialchars($customer['area_name']) : 'N/A' ?> - <?= !empty($customer['subarea_name']) ? htmlspecialchars($customer['subarea_name']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Network & Connection Card -->
    <div class="col-md-6 col-lg-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-router fs-5"></i>
                    </div>
                    <h6 class="mb-0 fw-bolder text-slate-700 text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">NETWORK & CONNECTION</h6>
                </div>
                <hr class="border-slate-200 mb-0">
            </div>
            <div class="card-body p-4">
                <div class="row g-4" style="font-size: 0.9rem;">
                    <!-- Row 1: Username & Password -->
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-badge text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">PPPoE Username</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['username']) ? htmlspecialchars($customer['username']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-key text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">PPPoE Password</span>
                                <span class="fw-medium text-slate-800 font-monospace text-break"><?= !empty($customer['password']) ? htmlspecialchars($customer['password']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Package & ONU -->
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-box text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Package Name</span>
                                <span class="fw-medium text-slate-800 text-break"><?= !empty($customer['package_name']) ? htmlspecialchars($customer['package_name']) : (!empty($customer['package_id']) ? htmlspecialchars($customer['package_id']) : 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cpu text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">ONU SN</span>
                                <span class="fw-medium text-slate-800 font-monospace text-break"><?= !empty($customer['onu_sn']) ? htmlspecialchars($customer['onu_sn']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: MAC Address & PPPoE Status -->
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-diagram-3 text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">MAC Address</span>
                                <span class="fw-medium text-slate-800 font-monospace text-break"><?= htmlspecialchars($mac_address) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-activity text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">PPPoE Status</span>
                                <?php if($is_online): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Online</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Disconnected</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: IP Address & Uptime -->
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-globe text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">IP Address</span>
                                <span class="fw-medium text-slate-800 font-monospace text-break"><?= htmlspecialchars($ip_address) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock-history text-slate-400 me-3 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-slate-500 d-block text-truncate" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Uptime</span>
                                <span class="fw-medium text-slate-800 text-break"><?= htmlspecialchars($uptime) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Information Card -->
    <div class="col-md-6 col-lg-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-wallet2 fs-5"></i>
                    </div>
                    <h6 class="mb-0 fw-bolder text-slate-700 text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">FINANCIAL INFORMATION</h6>
                </div>
                <hr class="border-slate-200 mb-0">
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-tag text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Package Price</span>
                            <span class="fw-bold text-slate-800"><?= number_format($customer['package_price'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-percent text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Discount</span>
                            <span class="fw-medium text-slate-800"><?= number_format($customer['discount'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-circle text-danger me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Previous Due</span>
                            <span class="fw-bold text-danger"><?= number_format($customer['previous_due'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-cash-stack text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Connection Fee</span>
                            <span class="fw-medium text-slate-800"><?= number_format($customer['connection_fee'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-safe text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Deposit</span>
                            <span class="fw-medium text-slate-800"><?= number_format($customer['deposit'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-0">
                        <i class="bi bi-arrow-up-circle text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Advance Payment</span>
                            <span class="fw-medium text-slate-800"><?= number_format($customer['advance_payment'] ?? 0, 0) ?></span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Dates & Status Card -->
    <div class="col-md-6 col-lg-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-calendar3 fs-5"></i>
                    </div>
                    <h6 class="mb-0 fw-bolder text-slate-700 text-uppercase" style="letter-spacing: 1px; font-size: 0.9rem;">DATES & STATUS</h6>
                </div>
                <hr class="border-slate-200 mb-0">
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-shield-check <?= ($customer['status'] == 'Active') ? 'text-success' : 'text-danger' ?> me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Account Status</span>
                            <?php if(($customer['status'] ?? '') == 'Active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= htmlspecialchars($customer['status'] ?? 'Inactive') ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-calendar-check text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Connection Date</span>
                            <span class="fw-medium text-slate-800"><?= htmlspecialchars($customer['connection_date'] ?? 'N/A') ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-calendar-event text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Billing Date</span>
                            <span class="fw-medium text-slate-800"><?= htmlspecialchars($customer['billing_date'] ?? 'N/A') ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-arrow-repeat text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Payment Cycle Date</span>
                            <span class="fw-medium text-slate-800"><?= htmlspecialchars($customer['payment_cycle_date'] ?? 'N/A') ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-clock-history text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Promise Date</span>
                            <span class="fw-medium text-slate-800"><?= htmlspecialchars($customer['promise_date'] ?? 'N/A') ?></span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-0">
                        <i class="bi bi-star text-slate-400 me-3 fs-5"></i>
                        <div class="flex-grow-1">
                            <span class="text-slate-500 d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Special User</span>
                            <?php if(($customer['is_special_user'] ?? 0) == 1): ?>
                                <span class="badge bg-warning text-dark">Yes</span>
                            <?php else: ?>
                                <span class="fw-medium text-slate-800">No</span>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>