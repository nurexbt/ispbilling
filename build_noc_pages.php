<?php
// Build all NOC portal stub pages

$pages = [
    'host' => ['Host', 'hdd-network', 'Manage network hosts and routers'],
    'package' => ['Package', 'box-seam', 'Manage internet packages and plans'],
    'area' => ['Area', 'geo-alt', 'Manage service areas and zones'],
    'customer' => ['Customer', 'people', 'Manage registered customers'],
    'connection_request' => ['Connection Request', 'diagram-3', 'Manage new connection requests'],
    'payment_record' => ['Payment Record', 'credit-card', 'View and manage payment history'],
    'store_record' => ['Store Record', 'archive', 'Manage inventory and store records'],
    'support_tickets' => ['Support Ticket', 'headset', 'Manage and track support tickets'],
];

$base = 'c:\xampp\htdocs\billsoft\nocportal\\';

foreach ($pages as $slug => $info) {
    list($title, $icon, $subtitle) = $info;
    $file = $base . $slug . '.php';
    if (file_exists($file)) {
        echo "SKIP (exists): $slug.php\n";
        continue;
    }
    $content = <<<PHP
<?php
require_once '../includes/db.php';
\$base_path = '../';
\$is_noc = true;
\$active_menu = '$slug';
\$page_title = '$title';
\$page_subtitle = '$subtitle';
include '../includes/header.php';

if (!has_permission('noc_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}

include 'sidebar.php';
?>

<div class="container-fluid pt-2">
    <div class="d-flex align-items-center gap-3 mb-4 mt-3">
        <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(59,130,246,0.1);">
            <i class="bi bi-$icon text-primary" style="font-size:18px;"></i>
        </div>
        <div>
            <h5 class="mb-0 text-slate-900 fw-bold">$title</h5>
            <p class="text-slate-400 mb-0" style="font-size:12px;">$subtitle</p>
        </div>
    </div>

    <div class="dashboard-card p-5 border-0 text-center" style="border-radius:12px;">
        <i class="bi bi-$icon text-slate-300 mb-3" style="font-size: 56px;"></i>
        <h5 class="text-slate-500 mb-2">$title</h5>
        <p class="text-slate-400 mb-0" style="font-size: 13px;">This section is under construction. Content will be available soon.</p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
PHP;
    file_put_contents($file, $content);
    echo "Created: $slug.php\n";
}
echo "Done!\n";
