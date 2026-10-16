import os
import re

files = [
    r"c:\xampp\htdocs\billsoft\nocportal\store_record.php",
    r"c:\xampp\htdocs\billsoft\manager\store.php"
]

php_logic_block = """
// Calculate Status Summaries
$category_stats = [];
if ($store_result && $store_result->num_rows > 0) {
    while ($row = $store_result->fetch_assoc()) {
        $item_name = $row['item_name'];
        if (!isset($category_stats[$item_name])) {
            $category_stats[$item_name] = [
                'In Stock' => 0,
                'Used' => 0,
                'Damage' => 0,
                'Replace' => 0,
                'Warranty' => 0,
                'Not approve yet' => 0,
                'Delivered' => 0,
                'Total' => 0
            ];
        }
        
        $is_in_stock = empty($row['connection_request_id']) && empty($row['return_reason']) && empty($row['employee_name']);
        $current_status = '';
        if ($is_in_stock) {
            $current_status = 'In Stock';
        } elseif (!empty($row['connection_request_id'])) {
            $current_status = 'Used';
        } elseif (!empty($row['return_reason'])) {
            $current_status = $row['return_reason']; // Damaged, Replace, Warranty
            if ($current_status == 'Damaged') $current_status = 'Damage';
        } elseif (!empty($row['employee_name'])) {
            if ($row['delivery_status'] == 'pending') {
                $current_status = 'Not approve yet';
            } else {
                $current_status = 'Delivered';
            }
        }
        
        if (isset($category_stats[$item_name][$current_status])) {
            $category_stats[$item_name][$current_status]++;
        } else {
            $category_stats[$item_name][$current_status] = 1;
        }
        $category_stats[$item_name]['Total']++;
    }
    $store_result->data_seek(0);
}

// Fetch Employees for Delivery
"""

html_cards_block = """
            <!-- STORE INVENTORY TAB -->
            <div class="tab-pane fade" id="inventory_pane" role="tabpanel">
            
                <!-- START SUMMARY CARDS -->
                <div class="p-3 border-bottom border-slate-100 bg-slate-50" style="background: #f8fafc;">
                    <?php if (empty($category_stats)): ?>
                        <div class="text-slate-400" style="font-size:13px;">No store items available.</div>
                    <?php else: ?>
                        <?php foreach($category_stats as $cat_name => $stats): ?>
                        <div class="mb-3">
                            <h6 class="text-slate-800 fw-bold mb-2 text-uppercase d-flex align-items-center gap-2" style="font-size:12px; letter-spacing:0.5px;">
                                <i class="bi bi-box"></i> <?= htmlspecialchars($cat_name) ?>
                            </h6>
                            <div class="d-flex flex-nowrap gap-3 pb-1" style="overflow-x:auto;">
                                <!-- IN STOCK -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #22c55e; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">In Stock</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['In Stock'] ?? 0 ?></div>
                                </div>
                                
                                <!-- USED -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #3b82f6; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Used</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Used'] ?? 0 ?></div>
                                </div>

                                <!-- DAMAGED -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #ef4444; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Damage</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Damage'] ?? 0 ?></div>
                                </div>

                                <!-- REPLACE -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #f59e0b; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Replace</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Replace'] ?? 0 ?></div>
                                </div>

                                <!-- WARRANTY -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #8b5cf6; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Warranty</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Warranty'] ?? 0 ?></div>
                                </div>

                                <!-- DELIVERED -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #0ea5e9; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Delivered</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Delivered'] ?? 0 ?></div>
                                </div>
                                
                                <!-- NOT APPROVE YET -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #64748b; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Pending</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Not approve yet'] ?? 0 ?></div>
                                </div>
                                
                                <!-- TOTAL -->
                                <div class="bg-white rounded shadow-sm" style="min-width:130px; border-left: 3px solid #0f172a; padding:12px 16px;">
                                    <div class="text-slate-400 fw-semibold text-uppercase mb-1" style="font-size:10px; letter-spacing:0.5px;">Total</div>
                                    <div class="text-slate-900 fw-bold fs-5"><?= $stats['Total'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- END SUMMARY CARDS -->

                <div class="p-3 border-bottom border-slate-100 bg-white">
"""

for file_path in files:
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # 1. Inject PHP logic
        if "// Calculate Status Summaries" not in content:
            content = content.replace("// Fetch Employees for Delivery", php_logic_block)
            
        # 2. Inject HTML cards
        if "<!-- START SUMMARY CARDS -->" not in content:
            search_str = '''<!-- STORE INVENTORY TAB -->
            <div class="tab-pane fade" id="inventory_pane" role="tabpanel">
                <div class="p-3 border-bottom border-slate-100 bg-light">'''
            content = content.replace(search_str, html_cards_block)
            
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated", file_path)
