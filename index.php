<?php
include 'includes/db.php';
$page_title = 'KHAN ERP - Dashboard';
include 'includes/header.php';

// Get counts for dashboard
$host_count = 0;
$package_count = 0;

$host_query = $conn->query("SELECT COUNT(*) as count FROM routers");
if($host_query) {
    $row = $host_query->fetch_assoc();
    $host_count = $row['count'];
}

$package_query = $conn->query("SELECT COUNT(*) as count FROM packages");
if($package_query) {
    $row = $package_query->fetch_assoc();
    $package_count = $row['count'];
}
?>

<div class="page-header mb-4">
    <h4 class="mb-1 fw-bold text-dark">Dashboard</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active" aria-current="page">Overview</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Hosts Card -->
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100 border-0 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Total Hosts</div>
                        <div class="h3 mb-0 fw-bold text-dark"><?php echo $host_count; ?></div>
                    </div>
                    <div class="text-primary opacity-50">
                        <i class="fas fa-server fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 small">
                <a href="host.php" class="text-decoration-none text-primary fw-bold">View Hosts <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Packages Card -->
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100 border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Total Packages</div>
                        <div class="h3 mb-0 fw-bold text-dark"><?php echo $package_count; ?></div>
                    </div>
                    <div class="text-success opacity-50">
                        <i class="fas fa-box-open fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 small">
                <a href="packages.php" class="text-decoration-none text-success fw-bold">View Packages <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Customers Card (Placeholder) -->
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100 border-0 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Total Customers</div>
                        <div class="h3 mb-0 fw-bold text-dark">0</div>
                    </div>
                    <div class="text-info opacity-50">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 small">
                <a href="#" class="text-decoration-none text-info fw-bold">View Customers <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Revenue Card (Placeholder) -->
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100 border-0 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Revenue</div>
                        <div class="h3 mb-0 fw-bold text-dark">$0</div>
                    </div>
                    <div class="text-warning opacity-50">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 small">
                <a href="#" class="text-decoration-none text-warning fw-bold">View Reports <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                    <p>No recent activity to display.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">System Status</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-check-circle text-success me-2"></i> Database</span>
                        <span class="badge bg-success rounded-pill">Connected</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-check-circle text-success me-2"></i> API Service</span>
                        <span class="badge bg-success rounded-pill">Running</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
