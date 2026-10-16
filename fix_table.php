<?php
$file = 'c:\xampp\htdocs\billsoft\billingportal\expense.php';
$content = file_get_contents($file);

// 1. Remove the completely misplaced TD
$badTd = '                            <td class="text-slate-600" style="font-size: 12px; padding: 12px 16px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-weight:600;font-size:10px;">
                                        <?= strtoupper(substr($row[\'added_by_name\'] ?? \'?\', 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($row[\'added_by_name\'] ?? \'Unknown\') ?></span>
                                </div>
                            </td>
';
$content = str_replace($badTd, '', $content);

// 2. Inject it at the correct place!
// We want to insert it right before the Status Progress td
$search = '                            <td style="padding: 12px 16px;">
                                <div class="d-flex flex-column gap-2" style="font-size: 10px;">
                                    <div class="d-flex align-items-center gap-2">';

$replace = $badTd . $search;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed billingportal/expense.php\n";

$file2 = 'c:\xampp\htdocs\billsoft\expense.php';
$content2 = file_get_contents($file2);
$content2 = str_replace($badTd, '', $content2);
$content2 = str_replace($search, $replace, $content2);
file_put_contents($file2, $content2);
echo "Fixed expense.php\n";