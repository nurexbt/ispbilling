<?php
session_start();
require_once '../includes/db.php';
$base_path = "../";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION["customer_id"];
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
