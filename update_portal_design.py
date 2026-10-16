import os

portal_dir = r"c:\xampp\htdocs\billsoft\customerportal"
includes_dir = os.path.join(portal_dir, "includes")

files = {
    "includes/sidebar.php": """<?php 
$base_path = isset($base_path) ? $base_path : "../"; 
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
                <div class="d-flex flex-column lh-1" style="overflow: hidden;">
                    <span class="fw-bold text-slate-900" id="sidebarCompanyName" style="font-size: 0.95rem; white-space: normal; line-height: 1.2;" title="<?php echo $company_name; ?>"><?php echo $company_name; ?></span>
                    <small class="text-slate-500 mt-1" style="font-size: 0.70rem;">Customer Portal</small>
                </div>
                <button class="btn btn-sm btn-light d-md-none ms-auto" id="sidebarClose"><i class="bi bi-x fs-5"></i></button>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-menu flex-grow-1 overflow-auto pb-3 custom-scrollbar">
                <div class="menu-label">MENU</div>
                <a href="index.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
                <a href="payments.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card"></i> <span>Payments</span>
                </a>
                <a href="notifications.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>">
                    <i class="bi bi-bell"></i> <span>Notifications</span>
                </a>
                <a href="instructions.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'instructions.php') ? 'active' : ''; ?>">
                    <i class="bi bi-info-circle"></i> <span>Instructions</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="btn btn-logout w-100 d-flex align-items-center justify-content-center border-0 shadow-sm">
                    <i class="bi bi-box-arrow-right me-2"></i> <span>Logout</span>
                </a>
            </div>
        </aside>""",

    "includes/header.php": """<?php
session_start();
require_once '../includes/db.php';
$base_path = "../";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$header_company_name = 'My Company';
$header_company_logo = '';
if (isset($conn)) {
    $c_res_hdr = $conn->query("SELECT company_name, logo_path FROM company_tbl LIMIT 1");
    if ($c_res_hdr && $c_res_hdr->num_rows > 0) {
        $c_row_hdr = $c_res_hdr->fetch_assoc();
        $header_company_name = htmlspecialchars($c_row_hdr['company_name']);
        if (!empty($c_row_hdr['logo_path'])) {
            $header_company_logo = $base_path . htmlspecialchars($c_row_hdr['logo_path']);
        }
    }
}
$header_company_initials = strtoupper(substr($header_company_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/components.css">
</head>
<body class="bg-light">

<div class="wrapper d-flex flex-column vh-100">
    <!-- Main Header -->
    <header class="main-header bg-white d-flex align-items-center justify-content-between px-4 position-fixed" style="top: 0; right: 0; z-index: 1040; transition: all 0.3s;">
        
        <div class="header-left d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-md-none border-0 shadow-sm rounded-circle d-flex align-items-center justify-content-center" id="sidebarToggle" style="width: 35px; height: 35px;">
                    <i class="bi bi-three-dots-vertical fs-5 text-primary"></i>
                </button>
                
                <div class="d-md-none d-flex align-items-center ms-2">
                    <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 0.95rem;">
                        <?php echo $header_company_name; ?>
                    </h5>
                </div>

                <h5 class="mb-0 fw-bold text-slate-700 text-uppercase d-none d-md-block" style="font-size: 0.95rem;"><?php echo isset($page_title) ? $page_title : 'Customer Portal'; ?></h5>
            </div>
        </div>

        <div class="header-right d-flex align-items-center gap-4">
            <div class="dropdown d-none d-md-block">
                <a href="#" class="header-user-btn d-flex align-items-center text-decoration-none bg-white" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white d-flex justify-content-center align-items-center fw-bold me-2 shadow-sm text-uppercase" style="width: 28px; height: 28px; border-radius: 0.5rem; font-size: 0.85rem;">
                        <?php echo substr($_SESSION['customer_name'] ?? 'C', 0, 1); ?>
                    </div>
                    <div class="d-flex flex-column lh-1 pe-2">
                        <span class="fw-semibold text-slate-900" style="font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Customer'); ?></span>
                        <small class="text-slate-500" style="font-size: 0.7rem;">Customer</small>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <div class="main-container d-flex flex-grow-1" style="margin-top: 60px;">
        <?php include 'sidebar.php'; ?>
        <main class="content flex-grow-1 p-3 p-md-4 custom-scrollbar" style="height: calc(100vh - 60px); overflow-y: auto; background-color: #f8fafc;">
""",

    "includes/footer.php": """        </main>
    </div> <!-- end .main-container -->
</div> <!-- end .wrapper -->

<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 for Toast Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    function showToast(type, message) {
        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Sidebar Toggle Logic
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }
</script>
</body>
</html>""",

    "login.php": """<?php
session_start();
require_once '../includes/db.php';
$base_path = "../";

if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, status FROM customer_tbl WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if ($customer['status'] !== 'Active') {
                $error = "Your account is inactive. Please contact support.";
            } else {
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_name'] = $customer['name'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid PPPoE username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Login</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 420px; border: 1px solid #e2e8f0; }
        .login-box h4 { text-align: center; margin-bottom: 0.5rem; font-weight: 700; color: #1e293b; }
        .login-box p.text-muted { text-align: center; margin-bottom: 2rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h4>Customer Portal</h4>
        <p class="text-muted">Sign in to manage your account</p>
        <?php if($error): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem; border-radius: 8px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-medium text-slate-700" style="font-size: 0.9rem;">PPPoE Username</label>
                <input type="text" name="username" class="form-control" style="border-radius: 8px;" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-medium text-slate-700" style="font-size: 0.9rem;">Password</label>
                <input type="password" name="password" class="form-control" style="border-radius: 8px;" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold" style="border-radius: 8px; padding: 0.6rem;">Sign In</button>
        </form>
    </div>
</body>
</html>""",

    "index.php": """<?php
$page_title = 'Dashboard';
include 'includes/header.php';

$stmt = $conn->prepare("SELECT * FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 text-slate-900 fw-bold">Dashboard</h4>
        <p class="text-slate-500 mb-0" style="font-size: 0.9rem;">Welcome back, <?= htmlspecialchars($customer['name']) ?></p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between mb-4">
                    <div class="stat-icon-box bg-primary-subtle text-primary">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <h6 class="text-slate-500 fw-medium mb-1">Profile Information</h6>
                <table class="table table-borderless table-sm mt-3 mb-0" style="font-size: 0.9rem;">
                    <tr><td class="text-slate-500">Name</td><td class="fw-medium text-slate-800 text-end"><?= htmlspecialchars($customer['name']) ?></td></tr>
                    <tr><td class="text-slate-500">Phone</td><td class="fw-medium text-slate-800 text-end"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td></tr>
                    <tr><td class="text-slate-500">Email</td><td class="fw-medium text-slate-800 text-end"><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></td></tr>
                    <tr><td class="text-slate-500">PPPoE Username</td><td class="fw-medium text-slate-800 text-end"><?= htmlspecialchars($customer['username']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between mb-4">
                    <div class="stat-icon-box bg-success-subtle text-success">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
                <h6 class="text-slate-500 fw-medium mb-1">Account Status</h6>
                <table class="table table-borderless table-sm mt-3 mb-0" style="font-size: 0.9rem;">
                    <tr><td class="text-slate-500">Status</td><td class="text-end">
                        <?php if($customer['status'] == 'Active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= htmlspecialchars($customer['status']) ?></span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><td class="text-slate-500">Previous Due</td><td class="fw-bold text-danger text-end">$<?= number_format($customer['previous_due'] ?? 0, 2) ?></td></tr>
                    <tr><td class="text-slate-500">Package Price</td><td class="fw-medium text-slate-800 text-end">$<?= number_format($customer['package_price'] ?? 0, 2) ?></td></tr>
                    <tr><td class="text-slate-500">Billing Date</td><td class="fw-medium text-slate-800 text-end"><?= htmlspecialchars($customer['billing_date'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>""",

    "payments.php": """<?php
$page_title = 'Payments';
include 'includes/header.php';

$stmt = $conn->prepare("SELECT * FROM payment_tbl WHERE customer_id = ? ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $payments = $stmt->get_result();
} else {
    $payments = false;
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 text-slate-900 fw-bold">Payments</h4>
        <p class="text-slate-500 mb-0" style="font-size: 0.9rem;">Your payment history</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table custom-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="bg-light text-slate-500 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.5rem;">Date</th>
                        <th class="bg-light text-slate-500 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.5rem;">Amount</th>
                        <th class="bg-light text-slate-500 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.5rem;">Discount</th>
                        <th class="bg-light text-slate-500 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.5rem;">Method</th>
                        <th class="bg-light text-slate-500 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.5rem;">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payments && $payments->num_rows > 0): ?>
                        <?php while($pay = $payments->fetch_assoc()): ?>
                            <tr>
                                <td style="padding: 1rem 1.5rem;" class="fw-medium text-slate-700"><?= htmlspecialchars($pay['payment_date'] ?? ($pay['created_at'] ?? 'N/A')) ?></td>
                                <td style="padding: 1rem 1.5rem;" class="fw-bold text-success">$<?= number_format($pay['amount'] ?? $pay['payment_amount'] ?? 0, 2) ?></td>
                                <td style="padding: 1rem 1.5rem;" class="text-slate-600">$<?= number_format($pay['discount'] ?? 0, 2) ?></td>
                                <td style="padding: 1rem 1.5rem;"><span class="badge bg-light text-dark border"><?= htmlspecialchars($pay['payment_method'] ?? 'N/A') ?></span></td>
                                <td style="padding: 1rem 1.5rem;" class="text-slate-500"><?= htmlspecialchars($pay['note'] ?? $pay['remarks'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-slate-500">No payment records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php 
if ($stmt) $stmt->close();
include 'includes/footer.php'; 
?>""",

    "notifications.php": """<?php
$page_title = 'Notifications';
include 'includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 text-slate-900 fw-bold">Notifications</h4>
        <p class="text-slate-500 mb-0" style="font-size: 0.9rem;">Recent alerts and messages</p>
    </div>
</div>
<div class="card border-0 shadow-sm rounded-xl">
    <div class="card-body p-4">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0">
            <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i> 
            <div>
                <h6 class="mb-0 fw-bold text-slate-800">No new notifications</h6>
                <p class="mb-0 text-slate-600" style="font-size: 0.85rem;">You're all caught up!</p>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>""",

    "instructions.php": """<?php
$page_title = 'Instructions';
include 'includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 text-slate-900 fw-bold">Instructions</h4>
        <p class="text-slate-500 mb-0" style="font-size: 0.9rem;">Helpful guides and support</p>
    </div>
</div>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-subtle text-primary rounded p-2 me-3"><i class="bi bi-wallet2 fs-5"></i></div>
                    <h5 class="mb-0 fw-bold text-slate-800">How to pay your bill</h5>
                </div>
                <ul class="text-slate-600" style="font-size: 0.95rem; line-height: 1.6;">
                    <li>Go to the payments tab.</li>
                    <li>Review your pending dues.</li>
                    <li>Use our accepted mobile banking methods to complete payment.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-xl h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning-subtle text-warning rounded p-2 me-3"><i class="bi bi-tools fs-5"></i></div>
                    <h5 class="mb-0 fw-bold text-slate-800">Connection Issues?</h5>
                </div>
                <ul class="text-slate-600" style="font-size: 0.95rem; line-height: 1.6;">
                    <li>Try restarting your router (turn off, wait 10 seconds, turn back on).</li>
                    <li>Ensure the optical fiber cable is securely plugged in.</li>
                    <li>If the PON light is red, please contact our support team immediately.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-xl bg-slate-50">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold text-slate-800 mb-2">Need more help?</h5>
                <p class="text-slate-600 mb-4" style="font-size: 0.95rem;">Our support team is available 24/7 to assist you.</p>
                <div class="d-flex justify-content-center gap-4">
                    <div class="d-flex align-items-center text-slate-700">
                        <i class="bi bi-envelope text-primary me-2"></i> support@example.com
                    </div>
                    <div class="d-flex align-items-center text-slate-700">
                        <i class="bi bi-telephone text-primary me-2"></i> +1 234 567 890
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>"""
}

for rel_path, content in files.items():
    full_path = os.path.join(portal_dir, rel_path)
    with open(full_path, "w", encoding="utf-8") as f:
        f.write(content)

print("Redesign applied successfully.")
