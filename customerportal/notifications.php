<?php
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
<?php include 'includes/footer.php'; ?>