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
                    <li class="sidebar-menu-title">Main</li>
                    <li class="nav-item mb-1">
                        <a href="index.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="sidebar-menu-title">Store Management</li>
                    <li class="nav-item mb-1">
                        <a href="store.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'store') ? 'active' : ''; ?>">
                            <i class="bi bi-shop"></i> <span>Store Record</span>
                        </a>
                    </li>

                    <li class="sidebar-menu-title">Customer Management</li>
                    <li class="nav-item mb-1">
                        <a href="customer.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'customer') ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i> <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="connection_request.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'connection_request') ? 'active' : ''; ?>">
                            <i class="bi bi-router"></i> <span>Connection Request</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="payment_record.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'payment_record') ? 'active' : ''; ?>">
                            <i class="bi bi-receipt"></i> <span>Payment Record</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="amount_collect.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'amount_collect') ? 'active' : ''; ?>">
                            <i class="bi bi-cash-coin"></i> <span>Amount Collect</span>
                        </a>
                    </li>

                    <li class="sidebar-menu-title">HRM & Cost</li>
                    <li class="nav-item mb-1">
                        <a href="employee.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'employee') ? 'active' : ''; ?>">
                            <i class="bi bi-person-badge"></i> <span>Employee</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="expense.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'expense') ? 'active' : ''; ?>">
                            <i class="bi bi-wallet2"></i> <span>Expense</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="salary.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'salary') ? 'active' : ''; ?>">
                            <i class="bi bi-cash-stack"></i> <span>Salary</span>
                        </a>
                    </li>

                    <li class="sidebar-menu-title">Support</li>
                    <li class="nav-item mb-1">
                        <a href="support_tickets.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'support_tickets') ? 'active' : ''; ?>">
                            <i class="bi bi-headset"></i> <span>Support Ticket</span>
                        </a>
                    </li>

                    <li class="sidebar-menu-title">Communications</li>
                    <li class="nav-item mb-1">
                        <a href="sms_portal.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'sms_portal') ? 'active' : ''; ?>">
                            <i class="bi bi-chat-dots"></i> <span>SMS Portal</span>
                        </a>
                    </li>

                    <li class="sidebar-menu-title">System & Settings</li>
                    <li class="nav-item mb-1">
                        <a href="host.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'host') ? 'active' : ''; ?>">
                            <i class="bi bi-hdd-network"></i> <span>Host & Devices</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="area.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'area') ? 'active' : ''; ?>">
                            <i class="bi bi-geo-alt"></i> <span>Area & Zones</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="company.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'company') ? 'active' : ''; ?>">
                            <i class="bi bi-building"></i> <span>Company Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="portal_user.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'portal_user') ? 'active' : ''; ?>">
                            <i class="bi bi-person-gear"></i> <span>Portal Users</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a href="role_manager.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'role_manager') ? 'active' : ''; ?>">
                            <i class="bi bi-shield-lock"></i> <span>Roles & Permissions</span>
                        </a>
                    </li>
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
<!-- DEBUG_MANAGER_SIDEBAR -->