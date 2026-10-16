<?php
$files = [
    'c:\xampp\htdocs\billsoft\expense.php'                    => 'includes/doc_viewer_modal.php',
    'c:\xampp\htdocs\billsoft\billingportal\expense.php'      => '../includes/doc_viewer_modal.php',
    'c:\xampp\htdocs\billsoft\transmission\expense.php'       => '../includes/doc_viewer_modal.php',
    'c:\xampp\htdocs\billsoft\manager\expense.php'            => '../includes/doc_viewer_modal.php',
    'c:\xampp\htdocs\billsoft\owner\expense.php'              => '../includes/doc_viewer_modal.php',
];

$oldLink = '<a href="<?= $base_path ?><?= htmlspecialchars($doc_path) ?>" target="_blank" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none">';
$newLink = '<a href="#" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none doc-preview-btn"' . "\n" .
           '                                               data-url="<?= $base_path ?><?= htmlspecialchars($doc_path) ?>"' . "\n" .
           '                                               data-name="Doc <?= $idx + 1 ?>">';

foreach ($files as $file => $modal_path) {
    if (!file_exists($file)) {
        echo "SKIP (not found): $file\n";
        continue;
    }

    $content = file_get_contents($file);

    // 1. Replace the document anchor
    $content = str_replace($oldLink, $newLink, $content);

    // 2. Also handle variation without $base_path prefix (root expense.php uses different base)
    $oldLinkRoot = '<a href="<?= htmlspecialchars($doc_path) ?>" target="_blank" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none">';
    $newLinkRoot = '<a href="#" class="badge bg-light text-slate-600 border border-slate-200 text-decoration-none doc-preview-btn"' . "\n" .
                   '                                               data-url="<?= htmlspecialchars($doc_path) ?>"' . "\n" .
                   '                                               data-name="Doc <?= $idx + 1 ?>">';
    $content = str_replace($oldLinkRoot, $newLinkRoot, $content);

    // 3. Inject modal include before footer (handle both root and subportal footer patterns)
    $footerPatterns = [
        "<?php include 'includes/footer.php'; ?>",
        "<?php include '../includes/footer.php'; ?>",
    ];
    $modal_include = "<?php include '{$modal_path}'; ?>\n";

    foreach ($footerPatterns as $fp) {
        if (strpos($content, $fp) !== false && strpos($content, $modal_include) === false) {
            $content = str_replace($fp, $modal_include . $fp, $content);
            break;
        }
    }

    file_put_contents($file, $content);
    echo "Updated: $file\n";
}
echo "Done!\n";
