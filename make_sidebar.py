import re
content = open('includes/sidebar.php', 'r', encoding='utf-8').read()

menu_start = content.find('<ul class="nav flex-column mt-2">')
menu_end = content.find('</ul>', menu_start)

clean_menu = '''<ul class="nav flex-column mt-2">
                    <?php if (has_permission('dashboard_view')): ?>
                    <li class="sidebar-menu-title">Main</li>
                    <li class="nav-item mb-1">
                        <a href="index.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (has_permission('store_record_view')): ?>
                    <li class="sidebar-menu-title">Store Management</li>
                    <li class="nav-item mb-1">
                        <a href="store.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'store') ? 'active' : ''; ?>">
                            <i class="bi bi-shop"></i> <span>Store Record</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (has_permission('employee_view') || has_permission('expense_view') || has_permission('salary_view')): ?>
                    <li class="sidebar-menu-title">HRM & Cost</li>
                    <?php endif; ?>
                    <?php if (has_permission('employee_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="employee.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'employee') ? 'active' : ''; ?>">
                            <i class="bi bi-person-badge"></i> <span>Employee</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('expense_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="expense.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'expense') ? 'active' : ''; ?>">
                            <i class="bi bi-wallet2"></i> <span>Expense</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('salary_view')): ?>
                    <li class="nav-item mb-1">
                        <a href="salary.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'salary') ? 'active' : ''; ?>">
                            <i class="bi bi-cash-stack"></i> <span>Salary</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (has_permission('support_ticket_view')): ?>
                    <li class="sidebar-menu-title">Support</li>
                    <li class="nav-item mb-1">
                        <a href="../support_tickets.php" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'support_tickets') ? 'active' : ''; ?>">
                            <i class="bi bi-headset"></i> <span>Support Ticket</span>
                        </a>
                    </li>
                    <?php endif; ?>
'''

new_content = content[:menu_start] + clean_menu + content[menu_end:]
open('includes/sidebar_manager.php', 'w', encoding='utf-8').write(new_content)
print('sidebar_manager.php created')
