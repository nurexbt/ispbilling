<div class="bg-white" id="sidebar-wrapper">
    <div class="sidebar-heading pb-0">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-weight: bold; font-size: 1.2rem;">R</div>
            <span class="fw-bold text-dark" style="font-size: 1.3rem;">RapidLogX</span>
        </div>
    </div>
    <div class="list-group list-group-flush my-3">
        <div class="small text-uppercase text-muted px-4 mb-2" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;">Dashboard</div>
        <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : 'bg-transparent'; ?>">
            <i class="fas fa-th-large me-3" style="width: 20px;"></i> Dashboard
        </a>

        <div class="small text-uppercase text-muted px-4 mt-4 mb-2" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;">Logs</div>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-search me-3" style="width: 20px;"></i> Log Search
        </a>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-users me-3" style="width: 20px;"></i> Online Users
        </a>

        <div class="small text-uppercase text-muted px-4 mt-4 mb-2" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;">Host Management</div>
        <a href="host.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'host.php' ? 'active' : 'bg-transparent'; ?>">
            <i class="fas fa-server me-3" style="width: 20px;"></i> Host
        </a>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-cogs me-3" style="width: 20px;"></i> Service
        </a>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-microchip me-3" style="width: 20px;"></i> OLT
        </a>

        <a href="packages.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : 'bg-transparent'; ?>">
            <i class="fas fa-box-open me-3" style="width: 20px;"></i> Package
        </a>
        <a href="pppoe.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'pppoe.php' ? 'active' : 'bg-transparent'; ?>">
            <i class="fas fa-network-wired me-3" style="width: 20px;"></i> PPPoE
        </a>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-cloud-upload-alt me-3" style="width: 20px;"></i> Upstream
        </a>

        <div class="small text-uppercase text-muted px-4 mt-4 mb-2" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;">Activities</div>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-history me-3" style="width: 20px;"></i> User
        </a>
        <a href="#" class="list-group-item list-group-item-action bg-transparent">
            <i class="fas fa-user-shield me-3" style="width: 20px;"></i> Admin
        </a>
    </div>
</div>
