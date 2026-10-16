<?php 
$base_path = isset($base_path) ? $base_path : ''; 
require_once __DIR__ . '/auth.php';

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
    <title>ISP Billing Software</title>
    
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/components.css">
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
                
                <!-- Mobile Logo and Company Name -->
                <div class="d-md-none d-flex align-items-center ms-2">
                    <?php if ($header_company_logo): ?>
                    <img src="<?php echo $header_company_logo; ?>" alt="Logo" style="height: 28px; width: auto; object-fit: contain;" class="me-2">
                    <?php else: ?>
                    <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 12px; font-weight: bold;">
                        <?php echo $header_company_initials; ?>
                    </div>
                    <?php endif; ?>
                    <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">
                        <?php echo $header_company_name; ?>
                    </h5>
                </div>

                <!-- Desktop Page Title -->
                <h5 class="mb-0 fw-bold text-slate-700 text-uppercase d-none d-md-block" style="font-size: 0.95rem;"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
            </div>
            <small class="text-slate-500 ms-md-0 ms-5 d-none d-md-block" style="font-size: 0.75rem;"><?php echo isset($page_subtitle) ? $page_subtitle : 'Real-time system overview'; ?></small>
        </div>

        <div class="header-right d-flex align-items-center gap-4">
            
            <!-- Live Badge & Timer -->
            <div class="d-none d-md-flex align-items-center gap-2">
                <div class="live-badge border border-success-subtle">
                    <span class="live-dot"></span> Live
                </div>
                <span class="text-slate-500 fw-medium font-monospace" style="font-size: 0.8rem;" id="header-time">02:05:45</span>
            </div>

            <!-- Notifications -->
            <a href="#" class="text-slate-500 position-relative fs-5 hover-slate-900 transition-colors">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-white rounded-circle">
                    <span class="visually-hidden">New alerts</span>
                </span>
            </a>

            <!-- User Dropdown -->
            <div class="dropdown d-none d-md-block">
                <a href="#" class="header-user-btn d-flex align-items-center text-decoration-none bg-white" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white d-flex justify-content-center align-items-center fw-bold me-2 shadow-sm text-uppercase" style="width: 28px; height: 28px; border-radius: 0.5rem; font-size: 0.85rem;">
                        <?php echo substr($_SESSION['username'] ?? 'U', 0, 1); ?>
                    </div>
                    <div class="d-flex flex-column lh-1 pe-2">
                        <span class="fw-semibold text-slate-900" style="font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        <small class="text-slate-500" style="font-size: 0.7rem;"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></small>
                    </div>
                    <i class="bi bi-chevron-down text-slate-400 ms-1" style="font-size: 0.7rem;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-slate-200 rounded-xl mt-2">
                    <li><a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#userProfileModal" style="font-size: 13px;"><i class="bi bi-person me-2 text-slate-400" style="font-size: 14px;"></i> Profile</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="main-container d-flex flex-grow-1" style="margin-top: 60px;">
