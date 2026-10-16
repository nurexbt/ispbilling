<?php
$files = ['c:\xampp\htdocs\billsoft\billingportal\expense.php', 'c:\xampp\htdocs\billsoft\expense.php'];

foreach ($files as $file) {
    $lines = file($file);
    $out = [];
    
    $newTd = "                            <td class=\"text-slate-600\" style=\"font-size: 12px; padding: 12px 16px;\">\n" .
             "                                <div class=\"d-flex align-items-center gap-2\">\n" .
             "                                    <div class=\"rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center\" style=\"width:24px;height:24px;font-weight:600;font-size:10px;\">\n" .
             "                                        <?= strtoupper(substr(\$row['added_by_name'] ?? '?', 0, 1)) ?>\n" .
             "                                    </div>\n" .
             "                                    <span><?= htmlspecialchars(\$row['added_by_name'] ?? 'Unknown') ?></span>\n" .
             "                                </div>\n" .
             "                            </td>\n";

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        // Remove bad placement
        if (strpos($line, '<td class="text-slate-600"') !== false && isset($lines[$i+4]) && strpos($lines[$i+4], 'strtoupper(substr($row[\'added_by_name\']') !== false) {
            $i += 8; // skip 9 lines
            continue;
        }

        // Insert at good placement
        if (strpos($line, '<td style="padding: 12px 16px;">') !== false && 
            isset($lines[$i+1]) && strpos($lines[$i+1], '<div class="d-flex flex-column gap-2" style="font-size: 10px;">') !== false &&
            isset($lines[$i+2]) && strpos($lines[$i+2], '<div class="d-flex align-items-center gap-2">') !== false) {
            
            $out[] = $newTd;
            $out[] = $line;
            continue;
        }

        $out[] = $line;
    }
    
    file_put_contents($file, implode("", $out));
}
echo "Done fixing both files.\n";
