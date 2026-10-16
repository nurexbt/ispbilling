<?php
$files = ['c:\xampp\htdocs\billsoft\billingportal\expense.php', 'c:\xampp\htdocs\billsoft\expense.php'];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // The exact bad block that was injected previously
    $badBlock = "                            <td class=\"text-slate-600\" style=\"font-size: 12px; padding: 12px 16px;\">\n" .
                "                                <div class=\"d-flex align-items-center gap-2\">\n" .
                "                                    <div class=\"rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center\" style=\"width:24px;height:24px;font-weight:600;font-size:10px;\">\n" .
                "                                        <?= strtoupper(substr(\$row['added_by_name'] ?? '?', 0, 1)) ?>\n" .
                "                                    </div>\n" .
                "                                    <span><?= htmlspecialchars(\$row['added_by_name'] ?? 'Unknown') ?></span>\n" .
                "                                </div>\n" .
                "                            </td>\n";

    // Because I injected it twice now (one in the wrong place, one in the right place), 
    // I need to carefully remove ONLY the one in the wrong place!

    // The wrong place is exactly after `<?php else: ?>`
    $wrongSearch = "                                <?php else: ?>\n" . $badBlock;
    $wrongReplace = "                                <?php else: ?>\n";
    
    $content = str_replace($wrongSearch, $wrongReplace, $content);

    // Let's also check for \r\n
    $wrongSearchRN = "                                <?php else: ?>\r\n" . str_replace("\n", "\r\n", $badBlock);
    $wrongReplaceRN = "                                <?php else: ?>\r\n";
    $content = str_replace($wrongSearchRN, $wrongReplaceRN, $content);
    
    file_put_contents($file, $content);
}
echo "Done removing duplicate block.\n";