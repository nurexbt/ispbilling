<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;
$active_menu = 'connection_request';
$page_title = 'Connection Requests';
$page_subtitle = 'Manage new connection inquiries';
include '../includes/header.php';


include 'includes/sidebar.php';

// Fetch requests from DB
$requests_query = "SELECT c.*, a.area_name, s.subarea_name, e.full_name AS assigned_employee
                   FROM connection_request_tbl c
                   LEFT JOIN area_tbl a ON c.area_id = a.id
                   LEFT JOIN subarea_tbl s ON c.subarea_id = s.id
                   LEFT JOIN employee_tbl e ON c.assigned_to = e.id
                   ORDER BY c.id DESC";
$requests_result = $conn->query($requests_query);

// Fetch Employees who are Portal Users
$portal_employees = [];
$portal_emp_query = "SELECT pu.employee_id, e.full_name, r.role_name 
                     FROM portal_user_tbl pu 
                     JOIN employee_tbl e ON pu.employee_id = e.id 
                     LEFT JOIN role_tbl r ON pu.role_id = r.id 
                     WHERE pu.is_active = 1";
$portal_emp_result = $conn->query($portal_emp_query);
if ($portal_emp_result && $portal_emp_result->num_rows > 0) {
    while($row = $portal_emp_result->fetch_assoc()) {
        $portal_employees[] = $row;
    }
}

// Fetch Areas for the dropdowns
$areas_list = [];
$areas_query = "SELECT * FROM area_tbl ORDER BY area_name ASC";
$areas_result = $conn->query($areas_query);
if ($areas_result && $areas_result->num_rows > 0) {
    while($row = $areas_result->fetch_assoc()) {
        $areas_list[] = $row;
    }
}

// Fetch Hosts for the router dropdown
$hosts_list = [];
$hosts_query = "SELECT * FROM host_tbl WHERE is_disabled = 0 ORDER BY device_name ASC";
$hosts_result = $conn->query($hosts_query);
if ($hosts_result && $hosts_result->num_rows > 0) {
    while($row = $hosts_result->fetch_assoc()) {
        $hosts_list[] = $row;
    }
}
?>

<div class="container-fluid pt-2">
    <!-- Table Card -->
    <div class="dashboard-card p-0 mt-3 border-0" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-slate-100">
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="toolbar-input" placeholder="Search requests...">
            </div>
            <div>
                <?php if (has_permission('connection_request_add')): ?>
                <button class="btn-action bg-primary text-white gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addRequestModal" style="font-weight: 600;">
                    <i class="bi bi-plus-lg"></i> Add Request
                </button>
                <?php endif; ?>
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
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Assigned To</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">PPPoE</th>
                        <th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Status</th>
                        <?php if (has_permission('connection_request_assign') || has_permission('connection_request_add_client') || has_permission('connection_request_delete')): ?>
<th class="border-0 text-slate-500" style="font-size: 11px; padding: 12px 16px;">Action</th>
<?php endif; ?>
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
                            <td class="text-slate-500" style="font-size: 12px; padding: 12px 16px;">
                                <?= !empty($row['assigned_employee']) ? '<div class="fw-bold text-slate-700"><i class="bi bi-person-fill text-primary me-1"></i>' . htmlspecialchars($row['assigned_employee']) . '</div>' : '<span class="badge bg-light text-slate-400 border border-slate-200">Unassigned</span>' ?>
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
                            <?php if (has_permission('connection_request_assign') || has_permission('connection_request_add_client') || has_permission('connection_request_delete')): ?>
<td style="padding: 12px 16px;">
                                <div class="d-flex gap-1">
                                    <?php if (has_permission('connection_request_assign')): ?>
                                    <button class="btn-action btn-action-blue btn-assign-request" style="width: 24px; height: 24px;" title="Assign To" data-id="<?= $row['id'] ?>"><i class="bi bi-person-lines-fill"></i></button>
                                    <?php endif; ?>
                                    <?php if (has_permission('connection_request_add_client') && $row['status'] !== 'Service created'): ?>
                                    <button class="btn-action btn-action-green btn-add-client" style="width: 24px; height: 24px; background-color: #10B981; color: white;" title="Add Client" 
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                        data-email="<?= htmlspecialchars($row['email']) ?>"
                                        data-area="<?= htmlspecialchars($row['area_id']) ?>"
                                        data-subarea="<?= htmlspecialchars($row['subarea_id']) ?>"
                                        data-address="<?= htmlspecialchars($row['address']) ?>"
                                        data-note="<?= htmlspecialchars($row['note']) ?>"
                                        data-onu="<?= htmlspecialchars($row['onu_sn'] ?? '') ?>"
                                    ><i class="bi bi-person-plus-fill"></i></button>
                                    <?php endif; ?>
                                    <?php if (has_permission('connection_request_delete')): ?>
                                    <button class="btn-action btn-action-red btn-delete-request" style="width: 24px; height: 24px;" title="Delete" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
<?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
        </table>
    </div>
</div>

<!-- Add Request Modal -->
<div class="modal fade" id="addRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 650px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-plug"></i></div> Add Connection Request
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addRequestForm">
            
            <div class="row mb-3">
                <div class="col-md-12 mb-3 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Full Name</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="name" class="form-control border-slate-200 shadow-none py-2" placeholder="John Doe" required>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Phone Number</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-success px-3"><i class="bi bi-telephone-fill"></i></span>
                        <input type="text" name="phone" class="form-control border-slate-200 shadow-none py-2" placeholder="01XXXXXXXXX" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Email Address</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-warning px-3"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="email" class="form-control border-slate-200 shadow-none py-2" placeholder="john@example.com">
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Area</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-secondary px-3"><i class="bi bi-geo-alt-fill"></i></span>
                        <select name="area_id" id="areaSelect" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                            <option value="">Select an Area...</option>
                            <?php foreach($areas_list as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Sub Area</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-danger px-3"><i class="bi bi-pin-map-fill"></i></span>
                        <select name="subarea_id" id="subareaSelect" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;">
                            <option value="">Select Sub Area...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Detailed Address</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-house-door-fill"></i></span>
                    <input type="text" name="address" class="form-control border-slate-200 shadow-none py-2" placeholder="House/Road No.">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Note / Remark</label>
                <textarea name="note" class="form-control border-slate-200 shadow-none" rows="3" placeholder="Any additional information..." style="border-radius: 10px; font-size: 13px; padding: 10px;"></textarea>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-primary text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Submit Request</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Assign Employee Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-info text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-person-lines-fill"></i></div> Assign Request
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="assignForm">
            <input type="hidden" name="request_id" id="assign_request_id">
            
            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Employee</label>
                <div class="input-group input-group-sm input-group-rounded">
                    <span class="input-group-text bg-light border-slate-200 text-info px-3"><i class="bi bi-person-badge"></i></span>
                    <select name="employee_id" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                        <option value="">Choose Employee...</option>
                        <?php foreach($portal_employees as $emp): ?>
                            <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['role_name'] ?: 'No Role') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <small class="text-slate-400 mt-2 d-block" style="font-size: 11px;">Only active employees mapped in the Portal User system are shown here.</small>
            </div>
      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-info text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-check2-circle"></i> Confirm Assignment</button>
      </div>
        </form>
    </div>
  </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
    <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header border-bottom border-slate-100 py-3 px-4">
        <h5 class="modal-title text-slate-800 d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600;">
            <div class="bg-success text-white rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;"><i class="bi bi-person-plus-fill"></i></div> Add Client
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
      </div>
      <div class="modal-body px-4 py-4">
        <form id="addClientForm">
            <input type="hidden" name="request_id" id="client_request_id">
            
            <h6 class="mb-3 text-slate-700" style="font-size: 14px; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Applicant Details</h6>
            
            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Full Name</label>
                    <input type="text" name="name" id="client_name" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Phone Number</label>
                    <input type="text" name="phone" id="client_phone" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Email Address</label>
                    <input type="email" name="email" id="client_email" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">ONU MAC/SN</label>
                    <input type="text" name="onu_sn" id="client_onu" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Area</label>
                    <select name="area_id" id="client_area" class="form-select form-select-sm border-slate-200 shadow-none" style="font-size: 13px;" required>
                        <option value="">Select an Area...</option>
                        <?php foreach($areas_list as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Sub Area</label>
                    <select name="subarea_id" id="client_subarea" class="form-select form-select-sm border-slate-200 shadow-none" style="font-size: 13px;">
                        <option value="">Select Sub Area...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Address</label>
                    <input type="text" name="address" id="client_address" class="form-control form-control-sm border-slate-200 shadow-none">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">NID No <span class="text-danger">*</span></label>
                    <input type="text" name="nid_no" id="client_nid" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
            </div>

            <h6 class="mb-3 mt-4 text-slate-700" style="font-size: 14px; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Service Details</h6>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Router</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-primary px-3"><i class="bi bi-router"></i></span>
                        <select name="router_id" id="client_router" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                            <option value="">Select Router...</option>
                            <?php foreach($hosts_list as $host): ?>
                                <option value="<?= $host['id'] ?>"><?= htmlspecialchars($host['device_name']) ?> (<?= htmlspecialchars($host['ip_address']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Select Package</label>
                    <div class="input-group input-group-sm input-group-rounded">
                        <span class="input-group-text bg-light border-slate-200 text-info px-3"><i class="bi bi-box"></i></span>
                        <select name="package_id" id="client_package" class="form-select border-slate-200 shadow-none py-2" style="font-size: 13px;" required>
                            <option value="">Select Package...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Username (PPPoE)</label>
                    <input type="text" name="username" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Password</label>
                    <input type="text" name="password" class="form-control form-control-sm border-slate-200 shadow-none" required>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Discount (৳)</label>
                    <input type="number" step="0.01" name="discount" id="client_discount" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Final Price</label>
                    <input type="number" step="0.01" name="package_price" id="client_package_price" class="form-control form-control-sm border-slate-200 shadow-none bg-light" readonly>
                    <input type="hidden" id="client_original_price">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Connection Date</label>
                    <input type="date" name="connection_date" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Billing Date</label>
                    <input type="date" name="billing_date" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Payment Cycle Date</label>
                    <input type="date" name="payment_cycle_date" class="form-control form-control-sm border-slate-200 shadow-none" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Connection Fee</label>
                    <input type="number" step="0.01" name="connection_fee" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Deposit</label>
                    <input type="number" step="0.01" name="deposit" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
                <div class="col-md-4 d-none">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Advance Payment</label>
                    <input type="number" step="0.01" name="advance_payment" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-slate-600 mb-2" style="font-size: 13px; font-weight: 500;">PPPoE Create Status</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="pppoe_status" id="pppoeEnable" value="enable" checked>
                        <label class="form-check-label text-slate-600" for="pppoeEnable" style="font-size: 12px;">Enable</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="pppoe_status" id="pppoeDisable" value="disable">
                        <label class="form-check-label text-slate-600" for="pppoeDisable" style="font-size: 12px;">Disable</label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-slate-600 mb-2" style="font-size: 13px; font-weight: 500;">Existing Customer?</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_existing_customer" id="existingYes" value="1">
                            <label class="form-check-label text-slate-600" for="existingYes" style="font-size: 12px;">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_existing_customer" id="existingNo" value="0" checked>
                            <label class="form-check-label text-slate-600" for="existingNo" style="font-size: 12px;">No</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="previousDueContainer" style="display: none;">
                    <label class="form-label text-slate-600 mb-1" style="font-size: 12px; font-weight: 500;">Previous Due (৳)</label>
                    <input type="number" step="0.01" name="previous_due" id="client_previous_due" class="form-control form-control-sm border-slate-200 shadow-none" value="0.00">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-slate-600 mb-1" style="font-size: 13px; font-weight: 500;">Note / Remark</label>
                <textarea name="client_note" id="client_note" class="form-control border-slate-200 shadow-none" rows="2" placeholder="Any additional information..." style="border-radius: 10px; font-size: 13px; padding: 10px;"></textarea>
            </div>

      </div>
      <div class="modal-footer border-top border-slate-100 py-3 px-4 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-sm btn-white border-slate-200 shadow-sm px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 500; color: #475569;">Cancel</button>
        <button type="submit" class="btn btn-sm bg-success text-white shadow-sm px-3 gap-1 d-flex align-items-center" style="font-size: 12px; font-weight: 500;"><i class="bi bi-person-plus-fill"></i> Add Client</button>
      </div>
        </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#requestTable')) {
        $('#requestTable').DataTable().destroy();
    }
    
    var table = $('#requestTable').DataTable({
        "order": [] // Disable initial sorting
    });
    
    // Bind custom search
    $('.toolbar-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Dynamic Sub Area Loading
    $('#areaSelect').on('change', function() {
        var area_id = $(this).val();
        var $subareaSelect = $('#subareaSelect');
        $subareaSelect.html('<option value="">Select Sub Area...</option>');
        if (area_id) {
            $.ajax({
                url: '../ajax/get_subareas.php',
                type: 'POST',
                data: { area_id: area_id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $.each(response.data, function(index, subarea) {
                            $subareaSelect.append('<option value="' + subarea.id + '">' + subarea.subarea_name + '</option>');
                        });
                    }
                }
            });
        }
    });

    // Auto-suggest PPPoE Username
    $('#client_router').on('change', function() {
        var router_id = $(this).val();
        if(router_id) {
            $.ajax({
                url: '../ajax/suggest_username.php',
                type: 'GET',
                data: { router_id: router_id },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        $('input[name="username"]').val(response.username);
                        $('input[name="password"]').val(response.password);
                    }
                }
            });
        }
    });


    // Handle Add Request Form Submission
    $('#addRequestForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_connection_request.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#addRequestModal').modal('hide');
                    $('#addRequestForm')[0].reset();
                    setTimeout(function(){ location.reload(); }, 1000); 
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while communicating with the server.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });

    // Change Request Status
    $('.btn-status-change').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var status = $(this).data('status');
        
        $.ajax({
            url: '../ajax/update_connection_request_status.php',
            type: 'POST',
            data: { request_id: id, status: status },
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
                showToast('error', 'An error occurred while updating status.');
            }
        });
    });

    // Delete Request
    $(document).on('click', '.btn-delete-request', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Request?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'compact-swal-popup',
                title: 'compact-swal-title',
                htmlContainer: 'compact-swal-text',
                actions: 'compact-swal-actions',
                confirmButton: 'btn btn-sm btn-danger px-4',
                cancelButton: 'btn btn-sm btn-secondary px-3 text-white'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../ajax/delete_connection_request.php',
                    type: 'POST',
                    data: { request_id: id },
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
                        showToast('error', 'An error occurred while deleting.');
                    }
                });
            }
        });
    });
    // Handle Assign Modal
    $(document).on('click', '.btn-assign-request', function() {
        var id = $(this).data('id');
        $('#assign_request_id').val(id);
        $('#assignForm')[0].reset();
        $('#assignModal').modal('show');
    });

    // Handle Assign Form Submission
    $('#assignForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Assigning...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/assign_connection_request.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message);
                    $('#assignModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 800); 
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                showToast('error', 'An error occurred while communicating with the server.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });

    // Handle Add Client Modal Popup
    $(document).on('click', '.btn-add-client', function() {
        var id = $(this).data('id');
        $('#client_request_id').val(id);
        
        $('#client_name').val($(this).data('name'));
        $('#client_phone').val($(this).data('phone'));
        $('#client_email').val($(this).data('email'));
        $('#client_address').val($(this).data('address'));
        $('#client_note').val($(this).data('note'));
        $('#client_onu').val($(this).data('onu'));
        
        // Handle Area and Subarea selection
        var area_id = $(this).data('area');
        var subarea_id = $(this).data('subarea');
        
        $('#client_area').val(area_id);
        
        var $subareaSelect = $('#client_subarea');
        $subareaSelect.html('<option value="">Select Sub Area...</option>');
        if (area_id) {
            $.ajax({
                url: '../ajax/get_subareas.php',
                type: 'POST',
                data: { area_id: area_id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $.each(response.data, function(index, subarea) {
                            var selected = (subarea.id == subarea_id) ? 'selected' : '';
                            $subareaSelect.append('<option value="' + subarea.id + '" ' + selected + '>' + subarea.subarea_name + '</option>');
                        });
                    }
                }
            });
        }
        
        // Reset router and package and nid
        $('#client_nid').val('');
        $('#client_router').val('');
        $('#client_package').html('<option value="">Select Package...</option>');
        $('#client_package_price').val('');
        $('#client_original_price').val('');
        $('#client_discount').val('0.00');
        $('#existingNo').prop('checked', true);
        $('#client_previous_due').val('0.00');
        $('#previousDueContainer').hide();

        $('#addClientModal').modal('show');
    });
    
    // Dynamic Area/Subarea on Add Client Form
    $('#client_area').on('change', function() {
        var area_id = $(this).val();
        var $subareaSelect = $('#client_subarea');
        $subareaSelect.html('<option value="">Select Sub Area...</option>');

        if (area_id) {
            $.ajax({
                url: '../ajax/get_subareas.php',
                type: 'POST',
                data: { area_id: area_id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $.each(response.data, function(index, subarea) {
                            $subareaSelect.append('<option value="' + subarea.id + '">' + subarea.subarea_name + '</option>');
                        });
                    }
                }
            });
        }
    });

    // Handle Router Selection to fetch Packages
    $('#client_router').on('change', function() {
        var host_id = $(this).val();
        var $packageSelect = $('#client_package');
        $packageSelect.html('<option value="">Select Package...</option>');
        $('#client_package_price').val('');
        
        if (host_id) {
            $.ajax({
                url: '../ajax/get_packages.php',
                type: 'POST',
                data: { host_id: host_id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $.each(response.data, function(index, pkg) {
                            $packageSelect.append('<option value="' + pkg.id + '" data-price="' + pkg.price + '">' + pkg.given_name + '</option>');
                        });
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function() {
                    showToast('error', 'Failed to fetch packages.');
                }
            });
        }
    });

    // Populate Package Price on Package Selection
    $('#client_package').on('change', function() {
        var price = $(this).find(':selected').data('price') || 0;
        $('#client_original_price').val(price);
        calculateFinalPrice();
    });

    $('#client_discount').on('input', function() {
        calculateFinalPrice();
    });

    function calculateFinalPrice() {
        var originalPrice = parseFloat($('#client_original_price').val()) || 0;
        var discount = parseFloat($('#client_discount').val()) || 0;
        var finalPrice = originalPrice - discount;
        if(finalPrice < 0) finalPrice = 0;
        $('#client_package_price').val(finalPrice.toFixed(2));
    }

    $('input[name="is_existing_customer"]').on('change', function() {
        if ($(this).val() === '1') {
            $('#previousDueContainer').show();
        } else {
            $('#previousDueContainer').hide();
            $('#client_previous_due').val('0.00');
        }
    });

    // Handle Add Client Form Submit
    $('#addClientForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '../ajax/add_client.php', // Assuming this endpoint will be created or is not fully required to be implemented in this task per prompt wording
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    showToast('success', response.message || 'Client added successfully!');
                    $('#addClientModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1000); 
                } else {
                    showToast('error', response.message || 'Error adding client.');
                }
            },
            error: function() {
                showToast('error', 'An error occurred while communicating with the server.');
            },
            complete: function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }
        });
    });

});
</script>
