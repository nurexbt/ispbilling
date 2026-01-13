<?php
include 'includes/db.php';
$page_title = 'KHAN ERP - PPPoE Management';

// Pagination & Filtering
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$router_filter = isset($_GET['router_filter']) ? (int)$_GET['router_filter'] : 0;

// Sorting
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
$sort_dir = isset($_GET['sort_dir']) ? strtolower($_GET['sort_dir']) : 'desc';
$allowed_sorts = ['id','username','password','profile','billing_date','router','status'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'id';
if ($sort_dir !== 'asc' && $sort_dir !== 'desc') $sort_dir = 'desc';
$col_map = [
    'id' => 'p.id',
    'username' => 'p.username',
    'password' => 'p.password',
    'profile' => 'COALESCE(p.given_profile, p.original_profile)',
    'billing_date' => 'p.billing_date',
    'router' => 'r.name',
    'status' => 'p.status'
];

// Build Query
$where_clauses = [];
if ($search) {
    $search = $conn->real_escape_string($search);
    // Broad search across multiple columns using table alias 'p' to avoid ambiguity
    $where_clauses[] = "(p.username LIKE '%$search%' 
                        OR p.comment LIKE '%$search%' 
                        OR p.given_profile LIKE '%$search%' 
                        OR p.original_profile LIKE '%$search%' 
                        OR p.password LIKE '%$search%' 
                        OR p.id LIKE '%$search%')";
}

// Only apply router filter if NOT searching (Global Search)
if ($router_filter && empty($search)) {
    $where_clauses[] = "p.router_id = $router_filter";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Get Total Count (Must use alias 'p' because where clause uses 'p.')
$count_sql = "SELECT COUNT(*) as total FROM pppoe_users p $where_sql";
$count_result = $conn->query($count_sql);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = $total_rows > 0 ? ceil($total_rows / $limit) : 1;

// Fetch Data
$order_sql = "ORDER BY " . $col_map[$sort_by] . " " . strtoupper($sort_dir);
$sql = "SELECT p.*, r.name as router_name, pkg.price as package_price, dues.total_due, dues.bill_count 
        FROM pppoe_users p 
        LEFT JOIN routers r ON p.router_id = r.id 
        LEFT JOIN packages pkg ON p.router_id = pkg.router_id AND (p.original_profile = pkg.original_name OR p.given_profile = pkg.given_name OR p.given_profile = pkg.original_name) 
        LEFT JOIN (
            SELECT user_id, 
                   SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as total_due,
                   COUNT(*) as bill_count
            FROM pppoe_billings 
            GROUP BY user_id
        ) dues ON p.id = dues.user_id 
        $where_sql $order_sql LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

if (!$result) {
    // Debugging: echo "Error: " . $conn->error;
}

// Auto-create table if not exists (workaround for CLI issue)
$checkTable = $conn->query("SHOW TABLES LIKE 'pppoe_users'");
if ($checkTable->num_rows == 0) {
    $sql = "CREATE TABLE pppoe_users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        router_id INT(6) UNSIGNED,
        username VARCHAR(255) NOT NULL,
        full_name VARCHAR(255),
        phone VARCHAR(50),
        nid VARCHAR(50),
        address TEXT,
        image VARCHAR(255),
        password VARCHAR(255) NOT NULL,
        original_profile VARCHAR(255) NOT NULL,
        given_profile VARCHAR(255),
        comment TEXT,
        billing_date DATE,
        status VARCHAR(50) DEFAULT 'enabled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
} else {
    // Schema Migration: Check if billing_date is INT, if so convert to DATE
    $checkColumn = $conn->query("SHOW COLUMNS FROM pppoe_users LIKE 'billing_date'");
    if ($checkColumn && $checkColumn->num_rows > 0) {
        $col = $checkColumn->fetch_assoc();
        if (strpos(strtolower($col['Type']), 'int') !== false) {
            // 1. Change to VARCHAR to hold date string
            $conn->query("ALTER TABLE pppoe_users MODIFY billing_date VARCHAR(20)");
            // 2. Update existing records to current month 1st
            $current_month_1st = date('Y-m-01');
            $conn->query("UPDATE pppoe_users SET billing_date = '$current_month_1st'");
            // 3. Change to DATE
            $conn->query("ALTER TABLE pppoe_users MODIFY billing_date DATE");
        }
    }

    // Schema Migration: Add new columns for User Details (Full Name, Phone, NID, Address, Image)
    $checkCol = $conn->query("SHOW COLUMNS FROM pppoe_users LIKE 'full_name'");
    if ($checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN full_name VARCHAR(255) AFTER username");
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN phone VARCHAR(50) AFTER full_name");
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN nid VARCHAR(50) AFTER phone");
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN address TEXT AFTER nid");
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN image VARCHAR(255) AFTER address");
    }

    // Schema Migration: Add new columns for ONU SN and Connection Date
    $checkColOnu = $conn->query("SHOW COLUMNS FROM pppoe_users LIKE 'onu_sn'");
    if ($checkColOnu->num_rows == 0) {
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN onu_sn VARCHAR(100) AFTER address");
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN connection_date DATE AFTER onu_sn");
    }

    // Schema Migration: Add is_special_user column
    $checkColSpecial = $conn->query("SHOW COLUMNS FROM pppoe_users LIKE 'is_special_user'");
    if ($checkColSpecial->num_rows == 0) {
        $conn->query("ALTER TABLE pppoe_users ADD COLUMN is_special_user TINYINT(1) DEFAULT 0 AFTER status");
    }
}

include 'includes/header.php';
?>

<div class="card mb-4" style="background-color: var(--color-lightinfo); border: none; border-radius: 12px;">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;">PPPoE Management</h5>
                <div class="small text-muted" style="font-size: 0.8rem;">
                    <a href="index.php" class="text-decoration-none text-muted">Dashboard</a> 
                    <span class="mx-1">•</span> 
                    PPPoE Users
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-danger me-2 d-none" id="btnDeleteSelected" style="border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-trash-alt me-1"></i> Delete Selected
                </button>
                <button type="button" class="btn btn-info text-white me-2 d-none" id="btnSpecialUser" style="border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-star me-1"></i> Special User
                </button>
                <button type="button" class="btn btn-warning me-2" id="btnCheckExpiry" style="border-radius: 6px; font-weight: 500; color: white;">
                    <i class="fas fa-clock me-1"></i> Check Expiry
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncUserModal" style="border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-sync me-1"></i> Sync User
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <!-- Search & Filter -->
            <div class="col-md-3">
                <form action="" method="GET" id="searchForm">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <input type="hidden" name="router_filter" value="<?php echo $router_filter; ?>">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput" autocomplete="off">
                    </div>
                </form>
            </div>

            <!-- Stats Display -->
            <div class="col-md-6 border-start border-end">
                <div class="d-flex justify-content-center align-items-center gap-3">
                    <select class="form-select form-select-sm" style="width: 150px;" id="statsRouterSelect" onchange="updateStats(this.value)">
                        <option value="">Select Router...</option>
                        <?php
                        $r_res = $conn->query("SELECT id, name FROM routers");
                        while($r = $r_res->fetch_assoc()) {
                            $selected = ($router_filter == $r['id']) ? 'selected' : '';
                            echo "<option value='" . $r['id'] . "' $selected>" . $r['name'] . "</option>";
                        }
                        ?>
                    </select>
                    
                    <div class="text-center px-2">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Added Secret</small>
                        <span class="h6 fw-bold text-primary mb-0" id="statAddedSecret">-</span>
                    </div>
                    <div class="text-center px-2 border-start">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;">In Router Secret</small>
                        <span class="h6 fw-bold text-info mb-0" id="statRouterSecret">-</span>
                    </div>
                    <div class="text-center px-2 border-start">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Missing Secret</small>
                        <span class="h6 fw-bold text-danger mb-0" id="statMissingSecret">-</span>
                    </div>
                </div>
            </div>

            <!-- Pagination Control -->
            <div class="col-md-3 d-flex justify-content-end align-items-center">
                <span class="me-2 text-muted small">Show:</span>
                <select class="form-select form-select-sm me-2" style="width: 70px;" onchange="changeLimit(this.value)">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="100000" <?php echo $limit == 100000 ? 'selected' : ''; ?>>All</option>
                </select>
                <span class="me-3 text-muted small">entries</span>
                <button class="btn btn-outline-secondary btn-sm">Columns <i class="fas fa-chevron-down ms-1"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light" id="pppoeTableHead">
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" id="selectAllRows" class="form-check-input">
                        </th>
                        <th style="width: 50px;">Actions</th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=id&sort_dir=<?php echo ($sort_by==='id' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                ID <i class="fas <?php echo ($sort_by==='id' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=username&sort_dir=<?php echo ($sort_by==='username' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Username <i class="fas <?php echo ($sort_by==='username' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=password&sort_dir=<?php echo ($sort_by==='password' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Password <i class="fas <?php echo ($sort_by==='password' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=profile&sort_dir=<?php echo ($sort_by==='profile' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Profile <i class="fas <?php echo ($sort_by==='profile' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=billing_date&sort_dir=<?php echo ($sort_by==='billing_date' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Billing Date <i class="fas <?php echo ($sort_by==='billing_date' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=router&sort_dir=<?php echo ($sort_by==='router' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Router <i class="fas <?php echo ($sort_by==='router' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                        <th>Bill / Due</th>
                        <th>
                            <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>&sort_by=status&sort_dir=<?php echo ($sort_by==='status' && $sort_dir==='asc') ? 'desc' : 'asc'; ?>" class="text-decoration-none sort-link">
                                Status <i class="fas <?php echo ($sort_by==='status' ? ($sort_dir==='asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'); ?> text-muted small ms-1"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody id="pppoeTableBody">
                    <?php
                    // Use the result from the top of the file which has correct pagination/filtering
                    // $result is already defined and executed at line ~35
                    if ($result && $result->num_rows > 0) {
                        // Reset pointer just in case (though not strictly needed if not iterated yet)
                        $result->data_seek(0); 
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><input type='checkbox' class='form-check-input row-checkbox' value='" . $row['id'] . "'></td>";
                            echo "<td>
                                    <div class='dropdown' style='position: static;'>
                                        <button class='btn btn-sm btn-link text-muted' type='button' data-bs-toggle='dropdown' aria-expanded='false' data-bs-boundary='viewport'>
                                            <i class='fas fa-ellipsis-h'></i>
                                        </button>
                                        <ul class='dropdown-menu dropdown-menu-end shadow border-0' style='border-radius: 8px;'>
                                            <li><h6 class='dropdown-header fw-bold text-dark'>Actions</h6></li>
                                            <li><a class='dropdown-item edit-user-btn' href='#' data-id='" . $row['id'] . "'><i class='fas fa-edit text-primary me-2'></i> Edit</a></li>
                                            <li><a class='dropdown-item ledger-btn' href='#' data-id='" . $row['id'] . "'><i class='fas fa-book text-info me-2'></i> Ledger</a></li>
                                            <li><a class='dropdown-item delete-user-btn text-danger' href='#' data-id='" . $row['id'] . "'><i class='fas fa-trash-alt me-2'></i> Delete</a></li>
                                        </ul>
                                    </div>
                                  </td>";

                            echo "<td>" . $row['id'] . "</td>";
                            
                            $ext_badge = '';
                            if (!empty($row['extend_date'])) {
                                $ext_date = strtotime($row['extend_date']);
                                $today_ts = strtotime(date('Y-m-d'));
                                if ($ext_date >= $today_ts) {
                                    $ext_badge = ' <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7em;" title="Extended until ' . $row['extend_date'] . '">Ext</span>';
                                }
                            }
                            
                            $su_badge = '';
                            if (!empty($row['is_special_user']) && $row['is_special_user'] == 1) {
                                $su_badge = ' <span class="badge bg-danger ms-1" style="font-size: 0.55em; border-radius: 50%; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; position: relative; top: -8px;" title="Special User">SU</span>';
                            }
                            
                            echo "<td>" . htmlspecialchars($row['username']) . $ext_badge . $su_badge . "</td>";
                            
                            echo "<td>" . htmlspecialchars($row['password']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['given_profile'] ?: $row['original_profile']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['billing_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['router_name'] ?? 'Unknown') . "</td>";

                            // Bill / Due Calculation
                            $bill_amount = isset($row['package_price']) ? (float)$row['package_price'] : 0;
                            
                            $has_bills = !empty($row['bill_count']) && $row['bill_count'] > 0;
                            $db_due = isset($row['total_due']) ? (float)$row['total_due'] : 0;

                            if ($has_bills) {
                                // Trust the DB if bills exist (synced)
                                $due_amount = $db_due;
                                $is_paid = ($due_amount <= 0);
                            } else {
                                // Fallback to calculation if no bills exist (Legacy/Unsynced)
                                $is_paid = false;
                                if (!empty($row['billing_date'])) {
                                    $bd_ts = strtotime($row['billing_date']);
                                    $today_ts = strtotime(date('Y-m-d'));
                                    $is_paid = ($bd_ts >= $today_ts);
                                }
                                
                                $due_amount = 0;
                                if (!$is_paid && !empty($row['billing_date']) && $bill_amount > 0) {
                                    $b_date = new DateTime($row['billing_date']);
                                    $now = new DateTime();
                                    if ($now >= $b_date) {
                                        $diff = $now->diff($b_date);
                                        $months_due = ($diff->y * 12) + $diff->m + 1;
                                        $due_amount = $months_due * $bill_amount;
                                    }
                                }
                            }

                            echo "<td>
                                    <div class='d-flex flex-column small'>
                                        <span class='text-nowrap fw-bold'>" . number_format($bill_amount, 0) . " <i class='fas fa-long-arrow-alt-right text-muted mx-1'></i> Bill</span>
                                        <span class='text-nowrap fw-bold " . ($is_paid ? "text-success" : "text-danger") . "'>" . number_format($due_amount, 0) . " <i class='fas fa-long-arrow-alt-right text-muted mx-1'></i> Due</span>
                                    </div>
                                  </td>";

                            $paid_badge = $is_paid ? "<span class='badge bg-success ms-1'>Paid</span>" : "<span class='badge bg-danger ms-1'>Unpaid</span>";
                            echo "<td>
                                    <span class='badge bg-secondary router-status-badge' data-username='" . htmlspecialchars($row['username']) . "' data-router-id='" . $row['router_id'] . "'>Checking...</span>
                                    $paid_badge
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center py-4 text-muted'>No users found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small" id="showingEntries">
            <?php
            $start_entry = ($total_rows > 0) ? $offset + 1 : 0;
            $end_entry = min($offset + $limit, $total_rows);
            echo "Showing $start_entry to $end_entry of $total_rows entries";
            ?>
        </div>
        <nav aria-label="Page navigation" id="paginationContainer">
            <ul class="pagination pagination-sm mb-0">
                <!-- Previous Link -->
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>">Previous</a>
                </li>

                <!-- Page Numbers -->
                <?php
                // Simple pagination: show all or max 5? Let's show all for now if pages are few, or simple window
                // Implementing a simple window: First, Prev, Current-2..Current+2, Next, Last
                $range = 2;
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                        $active = ($i == $page) ? 'active' : '';
                        echo "<li class='page-item $active'><a class='page-link' href='?page=$i&limit=$limit&search=" . urlencode($search) . "&router_filter=$router_filter'>$i</a></li>";
                    } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                }
                ?>

                <!-- Next Link -->
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&router_filter=<?php echo $router_filter; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Sync User Modal -->
<div class="modal fade" id="syncUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Sync Users from Mikrotik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label for="syncRouterSelect" class="form-label">Select Mikrotik Router</label>
                    <div class="input-group">
                        <select class="form-select" id="syncRouterSelect">
                            <option value="">Select a router...</option>
                            <?php
                            $routers = $conn->query("SELECT id, name, ip_address FROM routers");
                            while($r = $routers->fetch_assoc()) {
                                echo "<option value='" . $r['id'] . "'>" . $r['name'] . " (" . $r['ip_address'] . ")</option>";
                            }
                            ?>
                        </select>
                        <button class="btn btn-primary" type="button" id="btnFetchUsers">
                            <i class="fas fa-sync me-1"></i> Sync Users
                        </button>
                    </div>
                </div>
                
                <div id="usersListArea" class="d-none">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            Found <span id="foundUsersCount" class="fw-bold">0</span> users. Please confirm to add them to the database.
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3">Available PPP Secrets</h6>
                    <form id="syncUsersForm">
                        <input type="hidden" name="router_id" id="selectedRouterId">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="40" class="text-center"><input type="checkbox" id="selectAllUsers" checked></th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Profile</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <!-- Users will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success disabled" id="btnSaveUsers">Add Selected Users</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" id="editUsername" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="text" class="form-control" name="password" id="editPassword" readonly disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" id="editFullName" placeholder="Enter full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" id="editPhone" placeholder="Enter phone number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NID Number</label>
                                    <input type="text" class="form-control" name="nid" id="editNid" placeholder="National ID">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" id="editAddress" rows="2" placeholder="Enter address"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ONU SN</label>
                                    <input type="text" class="form-control" name="onu_sn" id="editOnuSn" placeholder="Enter ONU Serial Number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Connection Date</label>
                                    <input type="date" class="form-control" name="connection_date" id="editConnectionDate">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Billing Date</label>
                                    <input type="date" class="form-control" name="billing_date" id="editBillingDate">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Comment</label>
                                    <input type="text" class="form-control" name="comment" id="editComment">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnUpdateUser">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function changeLimit(limit) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit', limit);
    url.searchParams.set('page', 1); // Reset to page 1
    
    // Preserve search term from input if available
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        if (searchInput.value) {
            url.searchParams.set('search', searchInput.value);
        } else {
            url.searchParams.delete('search'); // Remove if empty
        }
    }
    
    window.location.href = url.toString();
}

function updateStats(routerId) {
    // Reload page with filter if it's the main dropdown
    // Wait, the stats dropdown and filter are combined in my design above?
    // "select class='form-select' id='statsRouterSelect' onchange='updateStats(this.value)'"
    
    // Actually, if I change the stats router, I probably want to filter the table too?
    // The user said "filter not working".
    // Let's make the Stats Dropdown ALSO act as the Table Filter.
    // So when you pick a router, it reloads the page with ?router_filter=X
    // AND then fetches the stats.
    
    const searchInput = document.querySelector('input[name="search"]');

    if (routerId === "") {
        // Clear filter
        const url = new URL(window.location.href);
        url.searchParams.delete('router_filter');
        
        // Preserve search term from input if available
        if (searchInput) {
            if (searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }
        }

        window.location.href = url.toString();
        return;
    }
    
    // If the URL param matches, we don't need to reload, just fetch stats
    // But if it differs, we reload.
    const url = new URL(window.location.href);
    const currentFilter = url.searchParams.get('router_filter');
    
    if (currentFilter !== routerId) {
        url.searchParams.set('router_filter', routerId);
        url.searchParams.set('page', 1);

        // Preserve search term from input if available
        if (searchInput) {
            if (searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }
        }

        window.location.href = url.toString();
        return;
    }

    // Fetch Stats via AJAX
    $('#statAddedSecret').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#statRouterSecret').html('<span class="spinner-border spinner-border-sm"></span>');
    $('#statMissingSecret').html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'api/get_router_stats.php',
        type: 'GET',
        data: { router_id: routerId },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#statAddedSecret').text(response.db_count);
                $('#statRouterSecret').text(response.router_count);
                $('#statMissingSecret').text(response.missing_count);
            } else {
                toastr.error(response.message);
                $('#statAddedSecret').text('-');
                $('#statRouterSecret').text('-');
                $('#statMissingSecret').text('-');
            }
        },
        error: function() {
            toastr.error('Failed to fetch stats');
            $('#statAddedSecret').text('Err');
            $('#statRouterSecret').text('Err');
            $('#statMissingSecret').text('Err');
        }
    });
}

// Live Search Logic
let searchDebounceTimer;
$('#searchInput').on('input', function() {
    const searchTerm = $(this).val();
    const url = new URL(window.location.href);
    const limit = url.searchParams.get('limit') || 10;
    const routerFilter = url.searchParams.get('router_filter') || '';

    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        // Update URL state (optional, but good for refresh)
        if (searchTerm.trim()) {
            url.searchParams.set('search', searchTerm.trim());
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.set('page', 1); // Reset to page 1 on search
        window.history.pushState({}, '', url);

        // Fetch new data
        $.get(url.toString(), function(data) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            
            // Update Table Body
            const newBody = doc.getElementById('pppoeTableBody');
            if (newBody) {
                $('#pppoeTableBody').html(newBody.innerHTML);
            }
            
            // Update Pagination
            const newPagination = doc.getElementById('paginationContainer');
            if (newPagination) {
                $('#paginationContainer').html(newPagination.innerHTML);
            }
            
            // Update Showing Entries
            const newShowing = doc.getElementById('showingEntries');
            if (newShowing) {
                $('#showingEntries').html(newShowing.innerHTML);
            }
            
            // Re-bind events if necessary (e.g., delete buttons, tooltips)
            // Note: Since we use delegated events for delete/edit (document.on), we don't need to re-bind those.
            // Checkbox 'select all' might need reset
            $('#selectAllRows').prop('checked', false);
            toggleDeleteButton();
            
        }).fail(function() {
            toastr.error('Search failed');
        });
    }, 500); // 500ms delay
});

// Intercept Pagination Clicks for AJAX experience
$(document).on('click', '.page-link', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href || href === '#') return;
    
    // Construct full URL
    // href is relative like ?page=2&limit=...
    const fullUrl = window.location.pathname + href;
    
    // Update history
    window.history.pushState({}, '', fullUrl);
    
    // Fetch
    $.get(fullUrl, function(data) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');
        
        $('#pppoeTableBody').html(doc.getElementById('pppoeTableBody').innerHTML);
        const newHead = doc.getElementById('pppoeTableHead');
        if (newHead) $('#pppoeTableHead').html(newHead.innerHTML);
        $('#paginationContainer').html(doc.getElementById('paginationContainer').innerHTML);
        $('#showingEntries').html(doc.getElementById('showingEntries').innerHTML);
        
        $('#selectAllRows').prop('checked', false);
        toggleDeleteButton();
    });
});

// Intercept Sort Clicks
$(document).on('click', '.sort-link', function(e) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href || href === '#') return;
    const fullUrl = window.location.pathname + href;
    window.history.pushState({}, '', fullUrl);
    $.get(fullUrl, function(data) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');
        const newHead = doc.getElementById('pppoeTableHead');
        if (newHead) $('#pppoeTableHead').html(newHead.innerHTML);
        $('#pppoeTableBody').html(doc.getElementById('pppoeTableBody').innerHTML);
        $('#paginationContainer').html(doc.getElementById('paginationContainer').innerHTML);
        $('#showingEntries').html(doc.getElementById('showingEntries').innerHTML);
        $('#selectAllRows').prop('checked', false);
        toggleDeleteButton();
    });
});
$(document).ready(function() {
    // Trigger stats load if filter is present
    const urlParams = new URLSearchParams(window.location.search);
    const routerFilter = urlParams.get('router_filter');
    if (routerFilter) {
        updateStats(routerFilter);
    }

    // Row Selection Logic
    $('#selectAllRows').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleDeleteButton();
    });

    $(document).on('change', '.row-checkbox', function() {
        toggleDeleteButton();
        // Update Select All checkbox state
        const allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
        $('#selectAllRows').prop('checked', allChecked);
    });

    function toggleDeleteButton() {
        // Re-query checkboxes to handle dynamic content updates
        const count = $('.row-checkbox:checked').length;
        if (count > 0) {
            $('#btnDeleteSelected').removeClass('d-none').text(`Delete (${count})`);
            $('#btnSpecialUser').removeClass('d-none');
        } else {
            $('#btnDeleteSelected').addClass('d-none');
            $('#btnSpecialUser').addClass('d-none');
        }
    }

    // Special User Action
    $('#btnSpecialUser').click(function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        // Ask for action type: Enable or Disable Special Status?
        // Simple prompt for now or just toggle ON? 
        // Requirement implies "Add on special user", so let's set to ON.
        if (confirm(`Mark ${selectedIds.length} users as Special User? This prevents them from being disabled when expired.`)) {
             $.ajax({
                url: 'api/set_special_user.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: selectedIds, status: 1 }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('System Error');
                }
            });
        }
    });

    // Bulk Delete Action
    $('#btnDeleteSelected').click(function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (confirm(`Are you sure you want to delete ${selectedIds.length} users?`)) {
            $.ajax({
                url: 'api/delete_pppoe_users.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: selectedIds }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('System Error');
                }
            });
        }
    });

    // Fetch Users
    $('#btnFetchUsers').click(function() {
        const routerId = $('#syncRouterSelect').val();
        if(!routerId) {
            toastr.warning('Please select a router first');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Syncing...');
        
        $.ajax({
            url: 'api/sync_pppoe_users.php',
            type: 'GET',
            data: { router_id: routerId },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Sync Users');
                
                if(response.success) {
                    $('#selectedRouterId').val(routerId);
                    $('#usersTableBody').empty();
                    $('#foundUsersCount').text(response.data.length);
                    
                    if(response.data.length > 0) {
                        response.data.forEach(function(user, index) {
                            const row = `
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input user-check" name="users[${index}][selected]" value="1" checked>
                                        <input type="hidden" name="users[${index}][username]" value="${user.name}">
                                        <input type="hidden" name="users[${index}][password]" value="${user.password}">
                                        <input type="hidden" name="users[${index}][profile]" value="${user.profile}">
                                        <input type="hidden" name="users[${index}][comment]" value="${user.comment}">
                                    </td>
                                    <td>${user.name}</td>
                                    <td>${user.password}</td>
                                    <td>${user.profile}</td>
                                    <td>${user.comment}</td>
                                </tr>
                            `;
                            $('#usersTableBody').append(row);
                        });
                        $('#usersListArea').removeClass('d-none');
                        $('#btnSaveUsers').removeClass('disabled');
                    } else {
                        toastr.info('No PPP secrets found on this router');
                        $('#usersListArea').addClass('d-none');
                    }
                } else {
                    toastr.error(response.message, 'Sync Failed');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Sync Users');
                toastr.error('Failed to connect to API', 'Error');
            }
        });
    });

    // Select All Checkbox
    $('#selectAllUsers').change(function() {
        $('.user-check').prop('checked', $(this).prop('checked'));
    });

    // Save Users
    $('#btnSaveUsers').click(function() {
        if($('.user-check:checked').length === 0) {
            toastr.warning('Please select at least one user to save');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Adding...');

        // Collect selected users data
        const selectedUsers = [];
        $('.user-check:checked').each(function() {
            const index = $(this).attr('name').match(/\[(\d+)\]/)[1];
            selectedUsers.push({
                selected: 1,
                username: $(`input[name="users[${index}][username]"]`).val(),
                password: $(`input[name="users[${index}][password]"]`).val(),
                profile: $(`input[name="users[${index}][profile]"]`).val(),
                comment: $(`input[name="users[${index}][comment]"]`).val()
            });
        });

        const routerId = $('#selectedRouterId').val();

        $.ajax({
            url: 'api/save_pppoe_users.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                router_id: routerId,
                users: selectedUsers
            }),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('Add Selected Users');
                
                if (response.errors && response.errors.length > 0) {
                    // Show errors
                    let errorMsg = response.message + '<br><br><strong>Errors:</strong><br>';
                    // Limit to first 10 errors to avoid huge toastr
                    const displayErrors = response.errors.slice(0, 10);
                    errorMsg += displayErrors.join('<br>');
                    if(response.errors.length > 10) {
                        errorMsg += '<br>...and ' + (response.errors.length - 10) + ' more.';
                    }
                    
                    if (response.success) {
                        toastr.warning(errorMsg, 'Completed with Errors', {timeOut: 10000, extendedTimeOut: 5000});
                        setTimeout(() => location.reload(), 3000);
                    } else {
                        toastr.error(errorMsg, 'Failed', {timeOut: 10000, extendedTimeOut: 5000});
                    }
                } else if(response.success) {
                    toastr.success(response.message);
                    $('#syncUserModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                btn.prop('disabled', false).html('Add Selected Users');
                toastr.error('System Error: ' + error);
            }
        });
    });
    // Check Active Status
    function checkActiveStatus() {
        // Group users by router to minimize API calls
        const usersByRouter = {};
        
        $('.router-status-badge').each(function() {
            const username = $(this).data('username');
            const routerId = $(this).data('router-id');
            
            if (username && routerId) {
                if (!usersByRouter[routerId]) {
                    usersByRouter[routerId] = [];
                }
                usersByRouter[routerId].push(username);
            }
        });
        
        // Process each router
        for (const routerId in usersByRouter) {
            const usernames = usersByRouter[routerId];
            if (usernames.length === 0) continue;
            
            $.ajax({
                url: 'api/check_secret_status.php',
                type: 'GET',
                data: { 
                    router_id: routerId,
                    usernames: usernames 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.statuses) {
                        const statuses = response.statuses; // map username => enabled|disabled
                        
                        // Update UI for this router's users
                        $(`.router-status-badge[data-router-id="${routerId}"]`).each(function() {
                            // Force string conversion as jQuery .data() auto-converts numbers
                            const user = String($(this).data('username'));
                            const state = statuses[user];
                            if (state === 'enabled') {
                                $(this).removeClass('bg-secondary bg-danger').addClass('bg-success').text('Enabled');
                                $(this).attr('title', 'Enabled on router');
                            } else {
                                $(this).removeClass('bg-secondary bg-success').addClass('bg-danger').text('Disabled');
                                $(this).attr('title', 'Disabled on router');
                            }
                        });
                    }
                }
            });
        }
    }

    // Call status check on load and after search/pagination
    checkActiveStatus();
    
    // Hook into existing AJAX complete events if possible, or manual calls
    // Since we replaced the body content in search/pagination, we need to re-run checkActiveStatus
    // Update the search/pagination success callbacks to call checkActiveStatus()
    
    // Store original AJAX success handler for search
    // Actually, simpler to just add it to the search callback in the existing code
    // But since I can't easily modify the middle of a function with search/replace without large context,
    // I'll use a MutationObserver to watch for table body changes!
    
    const observer = new MutationObserver(function(mutations) {
        checkActiveStatus();
    });
    
    const tableBody = document.getElementById('pppoeTableBody');
    if (tableBody) {
        observer.observe(tableBody, { childList: true });
    }

    // Edit User Button Click
    $(document).on('click', '.edit-user-btn', function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        
        // Reset form
        $('#editUserForm')[0].reset();
        
        // Fetch user data
        $.ajax({
            url: 'api/get_pppoe_user.php',
            type: 'GET',
            data: { id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const user = response.data;
                    $('#editUserId').val(user.id);
                    $('#editUsername').val(user.username);
                    $('#editPassword').val(user.password);
                    $('#editFullName').val(user.full_name);
                    $('#editPhone').val(user.phone);
                    $('#editNid').val(user.nid);
                    $('#editAddress').val(user.address);
                    $('#editOnuSn').val(user.onu_sn);
                    $('#editConnectionDate').val(user.connection_date);
                    $('#editBillingDate').val(user.billing_date);
                    $('#editComment').val(user.comment);
                    
                    if (user.image) {
                        $('#editUserImagePreview').attr('src', user.image);
                    }
                    
                    $('#editUserModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to fetch user details');
            }
        });
    });

    // Image Preview Removed


    // Update User Submit
    $('#btnUpdateUser').click(function() {
        const btn = $(this);
        const formData = new FormData($('#editUserForm')[0]);
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        
        $.ajax({
            url: 'api/update_pppoe_user.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('Save Changes');
                if (response.success) {
                    toastr.success(response.message);
                    $('#editUserModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('Save Changes');
                toastr.error('System Error');
            }
        });
    });
});

// Ledger Logic
    $(document).on('click', '.ledger-btn', function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        $('#cp_user_id').val(userId);
        $('#ledgerModal').modal('show');
        loadLedger(userId);
    });

    // Check Expiry Logic
    $('#btnCheckExpiry').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Checking...');
        $.get('api/check_expire.php', function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-clock me-1"></i> Check Expiry');
            if(res.success) {
                toastr.success(res.message);
                if (res.processed > 0) {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                toastr.error('Failed to check expiration');
            }
        }).fail(function() {
            btn.prop('disabled', false).html('<i class="fas fa-clock me-1"></i> Check Expiry');
            toastr.error('System Error');
        });
    });

    // Ledger Logic Variables
    let currentLedgerPage = 1;
    let ledgerPageSize = 10;
    let currentInvoices = [];

    function loadLedger(userId) {
        $('#ledgerTableBody').html('<tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>');
        $('#ledgerUsername').text('Loading...');
        
        $.get('api/get_ledger.php', { user_id: userId }, function(res) {
            if(res.success) {
                // Title
                $('#ledgerUsername').text(res.user.username || 'Unknown');

                // Summary
                let totalPaid = 0;
                let currentDue = 0;
                let monthlyBill = parseFloat(res.user.package_price || 0).toFixed(2);
                
                if (res.invoices) {
                    res.invoices.forEach(inv => {
                        if (inv.status === 'paid') totalPaid += parseFloat(inv.amount);
                        else currentDue += parseFloat(inv.amount);
                    });
                }

                $('#ledgerTotalPaid').text(totalPaid.toFixed(2));
                $('#ledgerCurrentDue').text(currentDue.toFixed(2));
                $('#ledgerMonthlyBill').text(monthlyBill);
                $('#cp_amount').val(monthlyBill);

                // Initialize Table
                currentInvoices = res.invoices || [];
                currentLedgerPage = 1;
                ledgerPageSize = parseInt($('#ledgerPageSize').val()) || 10;
                renderLedgerTable();
                
            } else {
                $('#ledgerTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">' + res.message + '</td></tr>');
                $('#ledgerUsername').text('Error');
            }
        }).fail(function() {
            $('#ledgerTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load ledger</td></tr>');
            $('#ledgerUsername').text('Error');
        });
    }

    function renderLedgerTable() {
        // Filter first
        const fMonth = $('#ledgerFilterMonth').val();
        const fYear = $('#ledgerFilterYear').val();
        const fStatus = $('#ledgerFilterStatus').val();

        let filtered = currentInvoices.filter(inv => {
            let matchesMonth = true;
            let matchesYear = true;
            let matchesStatus = true;
            
            // Date Filter
            if (fMonth || fYear) {
                if (inv.bill_date) {
                    // Assuming format YYYY-MM-DD HH:MM:SS
                    const datePart = inv.bill_date.split(' ')[0]; // 2026-01-01
                    const parts = datePart.split('-'); // [2026, 01, 01]
                    
                    if (parts.length >= 2) {
                        if (fMonth && parts[1] !== fMonth) matchesMonth = false;
                        if (fYear && parts[0] !== fYear) matchesYear = false;
                    } else {
                         matchesMonth = false; 
                         matchesYear = false;
                    }
                } else {
                    matchesMonth = false;
                    matchesYear = false;
                }
            }
            
            // Status Filter
            if (fStatus && inv.status !== fStatus) {
                matchesStatus = false;
            }
            
            return matchesMonth && matchesYear && matchesStatus;
        });

        // Pagination
        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / ledgerPageSize);
        if(currentLedgerPage > totalPages && totalPages > 0) currentLedgerPage = totalPages;
        if(currentLedgerPage < 1) currentLedgerPage = 1;
        
        const start = (currentLedgerPage - 1) * ledgerPageSize;
        const end = Math.min(start + ledgerPageSize, totalItems);
        const pagedItems = filtered.slice(start, end);

        let html = '';
        if(pagedItems.length > 0) {
            pagedItems.forEach(p => {
                let statusBadge = p.status === 'paid' 
                    ? '<span class="badge bg-success rounded-pill px-3">PAID</span>' 
                    : '<span class="badge bg-danger rounded-pill px-3">UNPAID</span>';
                
                let actionBtn = '<div class="text-end">';
                if(p.status !== 'paid') {
                     actionBtn += `<button class="btn btn-primary btn-sm pay-now-btn me-1 shadow-sm" data-id="${p.id}" data-amount="${p.amount}" data-invoice="${p.invoice_number}"><i class="fas fa-money-bill-wave me-1"></i> Pay Now</button>`;
                } else {
                     actionBtn += `<button class="btn btn-light btn-sm print-receipt-btn shadow-sm" data-id="${p.id}" title="Print"><i class="fas fa-print text-muted"></i></button>`;
                }
                actionBtn += '</div>';

                html += `<tr>
                    <td class="ps-3">${p.id}</td>
                    <td class="fw-bold text-dark">${p.invoice_number}</td>
                    <td>${p.service_type || 'Unknown'}</td>
                    <td>${p.bill_date}</td>
                    <td class="fw-bold">${p.amount}</td>
                    <td>${statusBadge}</td>
                    <td class="pe-3">${actionBtn}</td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="7" class="text-center text-muted py-4">No entries found</td></tr>';
        }
        $('#ledgerTableBody').html(html);
        
        // Update Footer Info
        $('#ledgerShowingText').text(`Showing ${totalItems > 0 ? start + 1 : 0} to ${end} of ${totalItems} entries`);
        
        // Update Pagination Controls
        let pagHtml = `
            <li class="page-item ${currentLedgerPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentLedgerPage - 1}">Previous</a>
            </li>
        `;
        
        // Simple pagination logic (1, 2, ... current ... last)
        // For simplicity, just showing current +/- 2
        for(let i = 1; i <= totalPages; i++) {
             if (i == 1 || i == totalPages || (i >= currentLedgerPage - 1 && i <= currentLedgerPage + 1)) {
                 pagHtml += `<li class="page-item ${i === currentLedgerPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
             } else if (i == currentLedgerPage - 2 || i == currentLedgerPage + 2) {
                 pagHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
             }
        }
        
        pagHtml += `
            <li class="page-item ${currentLedgerPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentLedgerPage + 1}">Next</a>
            </li>
        `;
        $('#ledgerPagination').html(pagHtml);
    }

    // Event Listeners for Ledger
    $(document).on('change', '#ledgerPageSize', function() {
        ledgerPageSize = parseInt($(this).val());
        currentLedgerPage = 1;
        renderLedgerTable();
    });

    $(document).on('change', '#ledgerFilterMonth, #ledgerFilterYear, #ledgerFilterStatus', function() {
        currentLedgerPage = 1;
        renderLedgerTable();
    });

    $(document).on('click', '#ledgerPagination .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if(page) {
            currentLedgerPage = page;
            renderLedgerTable();
        }
    });

    // Ledger Sort Logic
    $('.sortable').click(function() {
        const sortKey = $(this).data('sort');
        // Simple sort implementation on currentInvoices array would be better than DOM sort for pagination support
        // But for now keeping DOM sort logic or switching to array sort?
        // Let's switch to array sort for consistent pagination
        
        // Map sort key to property
        const map = {
            'id': 'id',
            'date': 'bill_date',
            'invoice': 'invoice_number',
            'service': 'service_type',
            'amount': 'amount',
            'status': 'status'
        };
        const prop = map[sortKey];
        if(!prop) return;

        this.asc = !this.asc;
        currentInvoices.sort((a, b) => {
            let valA = a[prop] || '';
            let valB = b[prop] || '';
            // Numeric check
            if(!isNaN(parseFloat(valA)) && isFinite(valA) && !isNaN(parseFloat(valB)) && isFinite(valB)) {
                valA = parseFloat(valA);
                valB = parseFloat(valB);
                return this.asc ? valA - valB : valB - valA;
            }
            // String check
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
            if(valA < valB) return this.asc ? -1 : 1;
            if(valA > valB) return this.asc ? 1 : -1;
            return 0;
        });
        
        // Update Icons
        $('.sortable i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).find('i').removeClass('fa-sort').addClass(this.asc ? 'fa-sort-up' : 'fa-sort-down');
        
        renderLedgerTable();
    });



// Pay Now Button Click
$(document).on('click', '.pay-now-btn', function() {
    const amount = $(this).data('amount');
    const invoiceNo = $(this).data('invoice');
    const invoiceId = $(this).data('id');
    
    // Populate form
    $('#cp_amount').val(amount);
    $('#cp_notes').val('Payment for ' + invoiceNo);
    $('#cp_invoice_id').val(invoiceId); // We need to add this hidden field
    
    // Show collapse
    $('#collectPaymentCollapse').collapse('show');
    
    // Scroll to form
    document.getElementById('collectPaymentCollapse').scrollIntoView({ behavior: 'smooth' });
});

// Print Receipt Button Click
$(document).on('click', '.print-receipt-btn', function() {
    const invoiceId = $(this).data('id');
    const url = 'invoice_print.php?id=' + invoiceId;
    window.open(url, '_blank', 'width=900,height=800,scrollbars=yes');
});

// Collect Payment Submit
$(document).on('submit', '#collectPaymentForm', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type="submit"]');
    const data = {
        user_id: $('#cp_user_id').val(),
        invoice_id: $('#cp_invoice_id').val(),
        amount: $('#cp_amount').val(),
        notes: $('#cp_notes').val()
    };

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: 'api/collect_payment.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Submit');
            if(res.success) {
                toastr.success(res.message);
                $('#collectPaymentForm')[0].reset();
                $('#collectPaymentCollapse').collapse('hide');
                loadLedger(data.user_id);
                // Force reload of status table
                setTimeout(() => location.reload(), 1500); 
            } else {
                toastr.error(res.message);
                console.error("Payment Error:", res);
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Submit');
            console.error("AJAX Error:", status, error, xhr.responseText);
            toastr.error('System Error: ' + error);
        }
    });
});

// Show Extend Form
$(document).on('click', '#btnShowExtend', function() {
    $('#collectPaymentCollapse').collapse('hide');
    $('#extendUserCollapse').collapse('toggle');
    // Set default date to today + 3 days
    const date = new Date();
    date.setDate(date.getDate() + 3);
    $('#ext_date').val(date.toISOString().split('T')[0]);
    
    // Set User ID from Ledger Modal logic (it's not directly accessible, so we grab it from CP form or global)
    // We need to ensure we have the user ID. 
    // In loadLedger function, we set it to #cp_user_id. Let's copy it.
    $('#ext_user_id').val($('#cp_user_id').val());
});

// Submit Extend Form
$(document).on('submit', '#extendUserForm', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type="submit"]');
    const data = {
        user_id: $('#ext_user_id').val(),
        extend_date: $('#ext_date').val()
    };
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    $.ajax({
        url: 'api/extend_user.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-clock me-1"></i> Extend');
            if(res.success) {
                toastr.success(res.message);
                $('#extendUserCollapse').collapse('hide');
                // Reload status
                checkActiveStatus();
            } else {
                toastr.error(res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-clock me-1"></i> Extend');
            toastr.error('System Error');
        }
    });
});

</script>

<!-- Ledger Modal -->
<div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light position-relative">
                <h5 class="modal-title fw-bold"><i class="fas fa-book me-2"></i>Customer Ledger</h5>
                <h5 class="fw-bold text-primary mb-0 position-absolute start-50 translate-middle-x" id="ledgerUsername"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- User Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-1">Total Paid</h6>
                                <h4 class="fw-bold text-success mb-0" id="ledgerTotalPaid">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-1">Current Due</h6>
                                <h4 class="fw-bold text-danger mb-0" id="ledgerCurrentDue">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center p-3">
                                <h6 class="text-muted mb-1">Monthly Bill</h6>
                                <h4 class="fw-bold text-primary mb-0" id="ledgerMonthlyBill">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Collect Payment Form (Collapsible) -->
                <div class="collapse mb-4" id="collectPaymentCollapse">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white fw-bold">
                            Collect Payment
                        </div>
                        <div class="card-body">
                            <form id="collectPaymentForm">
                                <input type="hidden" id="cp_user_id" name="user_id">
                                <input type="hidden" id="cp_invoice_id" name="invoice_id">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Amount</label>
                                        <input type="number" class="form-control" id="cp_amount" name="amount" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold">Notes</label>
                                        <input type="text" class="form-control" id="cp_notes" name="notes" placeholder="Optional">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success w-100 fw-bold">
                                            <i class="fas fa-check me-1"></i> Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Extend User Form (Collapsible) -->
                <div class="collapse mb-4" id="extendUserCollapse">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark fw-bold">
                            Extend User Access
                        </div>
                        <div class="card-body">
                            <form id="extendUserForm">
                                <input type="hidden" id="ext_user_id" name="user_id">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Extend Until Date</label>
                                        <input type="date" class="form-control" id="ext_date" name="extend_date" required>
                                        <small class="text-muted">User won't be disabled until this date passes.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                                            <i class="fas fa-clock me-1"></i> Extend
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payment History Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="ledgerFilterMonth" style="width: 120px;">
                                    <option value="">All Months</option>
                                    <option value="01">January</option>
                                    <option value="02">February</option>
                                    <option value="03">March</option>
                                    <option value="04">April</option>
                                    <option value="05">May</option>
                                    <option value="06">June</option>
                                    <option value="07">July</option>
                                    <option value="08">August</option>
                                    <option value="09">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <select class="form-select form-select-sm" id="ledgerFilterYear" style="width: 100px;">
                                    <option value="">All Years</option>
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                    <option value="2026">2026</option>
                                    <option value="2027">2027</option>
                                </select>
                                <select class="form-select form-select-sm" id="ledgerFilterStatus" style="width: 110px;">
                                    <option value="">All Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2">Show:</span>
                                <select class="form-select form-select-sm me-3" style="width: 70px;" id="ledgerPageSize">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="text-muted small me-2">entries</span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="ledgerTable">
                            <thead class="bg-light">
                                <tr class="text-uppercase text-secondary small fw-bold">
                                    <th class="py-3 ps-3 sortable" data-sort="id">Id <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable" data-sort="invoice">Invoice <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable" data-sort="service">Package <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable" data-sort="date">Date <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable" data-sort="amount">Amount <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable" data-sort="status">Status <i class="fas fa-sort text-muted small ms-1"></i></th>
                                    <th class="py-3 sortable text-end pe-3" data-sort="action">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerTableBody" class="border-top-0">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white py-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small" id="ledgerShowingText">
                                Showing 1 to 10 of 10 entries
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="ledgerPagination">
                                    <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning text-dark" id="btnShowExtend">
                    <i class="fas fa-clock me-1"></i> Extend
                </button>
            </div>
        </div>
    </div>
</div>
