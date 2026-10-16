<?php 
$base_path = isset($base_path) ? $base_path : ""; 
$is_manager = isset($is_manager) ? $is_manager : false;
?>
<?php
$company_name = 'My Company';
$company_logo = '';
if (isset($conn)) {
    $c_res = $conn->query("SELECT company_name, logo_path FROM company_tbl LIMIT 1");
    if ($c_res && $c_res->num_rows > 0) {
        $c_row = $c_res->fetch_assoc();
        $company_name = htmlspecialchars($c_row['company_name']);
        if (!empty($c_row['logo_path'])) {
            $company_logo = $base_path . htmlspecialchars($c_row['logo_path']);
        }
    }
}
$company_initials = strtoupper(substr($company_name, 0, 2));
?>
        <!-- Sidebar -->
        <aside class="sidebar position-fixed d-flex flex-column h-100" id="sidebar">
            <!-- Brand Logo -->
            <div class="sidebar-brand d-flex align-items-center">
                <?php if ($company_logo): ?>
                <div class="sidebar-logo-box me-3 shadow-sm bg-white p-1" style="overflow: hidden;">
                    <img id="sidebarLogoImg" src="<?php echo $company_logo; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <?php else: ?>
                <div class="sidebar-logo-box me-3 shadow-sm" id="sidebarLogoText">
                    <?php echo $company_initials; ?>
                </div>
                <?php endif; ?>
                <?php
                $name_len = strlen($company_name);
                $name_font_size = "0.95rem";
                if ($name_len > 25) {
                    $name_font_size = "0.70rem";
                } elseif ($name_len > 15) {
                    $name_font_size = "0.80rem";
                }
                ?>
                <div class="d-flex flex-column lh-1" style="overflow: hidden;">
                    <span class="fw-bold text-slate-900" id="sidebarCompanyName" style="font-size: <?php echo $name_font_size; ?>; white-space: normal; line-height: 1.2;" title="<?php echo $company_name; ?>"><?php echo $company_name; ?></span>
                    <small class="text-slate-500 mt-1" style="font-size: 0.70rem;">ERP Management</small>
                </div>
                <button class="btn btn-sm btn-light d-md-none ms-auto" id="sidebarClose"><i class="bi bi-x fs-5"></i></button>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-menu flex-grow-1 overflow-auto pb-3 custom-scrollbar">
                <ul class="nav flex-column mt-2">
                    
                    <?php if (isset($is_tx) && $is_tx): ?>
                    <li class="sidebar-menu-title">Transmission</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>tx-collector/index.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>tx-collector/customer.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'customer') ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> <span>Customer</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>tx-collector/equipment.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'equipment') ? 'active' : ''; ?>">
                            <i class="bi bi-router"></i> <span>Equipment</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>tx-collector/profile.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'profile') ? 'active' : ''; ?>">
                            <i class="bi bi-person"></i> <span>Profile</span>
                        </a>
                    </li>
                    <?php elseif (isset($is_transmission) && $is_transmission): ?>
                    <li class="sidebar-menu-title">Transmission Portal</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>transmission/index.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>transmission/customer.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'customer') ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> <span>Customer</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>transmission/connection_request.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'connection_request') ? 'active' : ''; ?>">
                            <i class="bi bi-plug"></i> <span>Connection Request</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>transmission/equipments.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'equipments') ? 'active' : ''; ?>">
                            <i class="bi bi-router"></i> <span>Equipments</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>transmission/profile.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'profile') ? 'active' : ''; ?>">
                            <i class="bi bi-person"></i> <span>Profile</span>
                        </a>
                    </li>
                    
                    <?php if (has_permission('support_ticket_view')): ?>
                    <li class="sidebar-menu-title">Support</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>support_tickets.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'support_tickets') ? 'active' : ''; ?>">
                            <i class="bi bi-headset"></i> <span>Support Ticket</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <li class="sidebar-menu-title">Main</li>
                    <?php if (has_permission('dashboard_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?><?php echo $is_manager ? 'manager/' : ''; ?>index.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (!$is_manager): ?>
                    <?php if (!$is_manager && (has_permission('host_view') || has_permission('package_view'))): ?>
                    <li class="sidebar-menu-title">Settings</li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('host_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>host.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'host') ? 'active' : ''; ?>">
                            <i class="bi bi-server"></i> <span>Host</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('package_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>package.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'package') ? 'active' : ''; ?>">
                            <i class="bi bi-box"></i> <span>Package</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (!$is_manager && (has_permission('area_view') || has_permission('company_view'))): ?>
                    <li class="sidebar-menu-title">Reports & Financials</li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('area_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>area.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'area') ? 'active' : ''; ?>">
                            <i class="bi bi-geo-alt"></i> <span>Area</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('company_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>company.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'company') ? 'active' : ''; ?>">
                            <i class="bi bi-building"></i> <span>Company</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (!$is_manager && (has_permission('customer_view') || has_permission('connection_request_view') || has_permission('payment_record_view') || has_permission('amount_collect_view'))): ?>
                    <li class="sidebar-menu-title">Client & Billing</li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('customer_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>customer.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'customer') ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> <span>Customer</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('connection_request_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>connection_request.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'connection_request') ? 'active' : ''; ?>">
                            <i class="bi bi-plug"></i> <span>Connection Request</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('payment_record_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>payment_record.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'payment_record') ? 'active' : ''; ?>">
                            <i class="bi bi-receipt"></i> <span>Payment Record</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('amount_collect_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>amount_collect.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'amount_collect') ? 'active' : ''; ?>">
                            <i class="bi bi-cash-coin"></i> <span>Amount Collect</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('store_record_view')): ?>
                    <li class="sidebar-menu-title">Store Management</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?><?php echo $is_manager ? 'manager/' : ''; ?>store.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'store') ? 'active' : ''; ?>">
                            <i class="bi bi-shop"></i> <span>Store Record</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (has_permission('employee_view') || has_permission('expense_view') || has_permission('salary_view')): ?>
                    <li class="sidebar-menu-title">HRM & Cost</li>
                    <?php endif; ?>
                    <?php if (has_permission('employee_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?><?php echo $is_manager ? 'manager/' : ''; ?>employee.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'employee') ? 'active' : ''; ?>">
                            <i class="bi bi-person-badge"></i> <span>Employee</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('expense_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?><?php echo $is_manager ? 'manager/' : (isset($is_transmission) && $is_transmission ? 'transmission/' : ''); ?>expense.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'expense') ? 'active' : ''; ?>">
                            <i class="bi bi-wallet2"></i> <span>Expense</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($is_manager && has_permission('salary_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>manager/salary.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'salary') ? 'active' : ''; ?>">
                            <i class="bi bi-cash-stack"></i> <span>Salary</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (!$is_manager): ?>
                    <?php if (!$is_manager && (has_permission('portal_user_view') || has_permission('role_manager_view'))): ?>
                    <li class="sidebar-menu-title">User Management</li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('portal_user_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>portal_user.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'portal_user') ? 'active' : ''; ?>">
                            <i class="bi bi-person-circle"></i> <span>Portal User</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!$is_manager && has_permission('role_manager_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>role_manager.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'role_manager') ? 'active' : ''; ?>">
                            <i class="bi bi-shield-lock"></i> <span>Role Manager</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('support_ticket_view')): ?>
                    <li class="sidebar-menu-title">Support</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>support_tickets.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'support_tickets') ? 'active' : ''; ?>">
                            <i class="bi bi-headset"></i> <span>Support Ticket</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($is_manager): ?>
                    <li class="sidebar-menu-title">Communications</li>
                    <li class="nav-item mb-1">
                        <a href="<?php echo $base_path; ?>manager/sms_portal.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'sms_portal') ? 'active' : ''; ?>">
                            <i class="bi bi-chat-dots"></i> <span>SMS Portal</span>
                        </a>
                    </li>
                    <?php endif; ?>


                    <?php if (!$is_manager && has_permission('user_activity_view')): ?>
                    <li class="sidebar-menu-title">System</li>
                    <li class="nav-item mb-1">
                        <a href="#" class="nav-link">
                            <i class="bi bi-activity"></i> <span>User Activity</span>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>
                </ul>
            </div>

            <!-- Bottom User Profile Card -->
            <div class="p-3 bg-white mt-auto border-top border-slate-200">
                <!-- Mobile Only: Profile & Small Logout -->
                <div class="d-flex d-md-none align-items-center gap-2">
                    <div class="flex-grow-1 d-flex align-items-center justify-content-between cursor-pointer" data-bs-toggle="modal" data-bs-target="#userProfileModal" style="padding: 0.375rem; border: 1px solid var(--border-slate-200); border-radius: 0.75rem;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white d-flex justify-content-center align-items-center fw-bold me-2 shadow-sm text-uppercase" style="width: 28px; height: 28px; border-radius: 0.5rem; font-size: 0.8rem;">
                                <?php echo substr($_SESSION['username'] ?? 'U', 0, 1); ?>
                            </div>
                            <div class="d-flex flex-column lh-1 pe-2" style="max-width: 110px;">
                                <span class="fw-semibold text-slate-900 text-truncate" style="font-size: 0.8rem;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                                <small class="text-slate-500 mt-1 text-truncate" style="font-size: 0.65rem;"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></small>
                            </div>
                        </div>
                        <i class="bi bi-person-gear text-slate-400 ms-1" style="font-size: 16px;"></i>
                    </div>
                    
                    <a href="<?php echo $base_path; ?>logout.php" class="btn rounded-xl d-flex align-items-center justify-content-center text-slate-600 border border-slate-200" style="width: 42px; height: 42px; background: var(--bg-white); transition: all 0.2s; padding: 0;" onmouseover="this.classList.replace('text-slate-600', 'text-danger'); this.classList.replace('border-slate-200', 'border-danger-subtle')" onmouseout="this.classList.replace('text-danger', 'text-slate-600'); this.classList.replace('border-danger-subtle', 'border-slate-200')">
                        <i class="bi bi-box-arrow-right" style="font-size: 1.1rem;"></i>
                    </a>
                </div>

                <!-- Desktop Only: Full Logout Button -->
                <a href="<?php echo $base_path; ?>logout.php" class="btn w-100 d-none d-md-flex rounded-xl align-items-center justify-content-center py-2 text-slate-600 border border-slate-200" style="font-size: 0.85rem; background: var(--bg-white); transition: all 0.2s;" onmouseover="this.classList.replace('text-slate-600', 'text-danger'); this.classList.replace('border-slate-200', 'border-danger-subtle')" onmouseout="this.classList.replace('text-danger', 'text-slate-600'); this.classList.replace('border-danger-subtle', 'border-slate-200')">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign out
                </a>
            </div>
        </aside>

        <!-- Main Content Area Wrapper -->
        <main class="content flex-grow-1">
