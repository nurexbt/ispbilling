"""
Add Filter Offcanvas to billingportal, defaultportal, transmission, txbill customer.php
Copies the full filter (offcanvas HTML + JS) from manager/customer.php
"""

import re

# ── Read source (manager) ──────────────────────────────────────────────────────
with open('manager/customer.php', 'r', encoding='utf-8') as f:
    src = f.read()

# Extract the Filter Offcanvas block (from comment to closing </div>)
offcanvas_match = re.search(
    r'(<!-- Filter Offcanvas -->.*?</div>\s*\n)',
    src, re.DOTALL
)
if not offcanvas_match:
    raise RuntimeError("Could not extract Filter Offcanvas from manager/customer.php")
offcanvas_html = offcanvas_match.group(1)

# The Filter button HTML to insert into toolbar
filter_button_html = """                <button class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" style="font-size: 12px; font-weight: 500; color: #475569;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
"""

js_filter_block = """
    // Track switch interaction for filtering
    $('.filter-switch-checkbox').on('change', function() {
        $(this).attr('data-filtered', 'true');
    });

    // Custom Filters
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (settings.nTable.id !== 'customerTable') return true;

        var isBillingFiltered = $('#billingNeuSwitch').attr('data-filtered') === 'true';
        var filterBilling = isBillingFiltered ? ($('#billingNeuSwitch').is(':checked') ? 'Paid' : 'Unpaid') : '';

        var isRouterFiltered = $('#routerNeuSwitch').attr('data-filtered') === 'true';
        var filterRouter = isRouterFiltered ? ($('#routerNeuSwitch').is(':checked') ? 'Enabled' : 'Disabled') : '';

        var isNidFiltered = $('#nidNeuSwitch').attr('data-filtered') === 'true';
        var filterNid = isNidFiltered ? ($('#nidNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';

        var isSpecialFiltered = $('#specialNeuSwitch').attr('data-filtered') === 'true';
        var filterSpecial = isSpecialFiltered ? ($('#specialNeuSwitch').is(':checked') ? 'Yes' : 'No') : '';

        var filterDateFrom = $('#filterDateFrom').val();
        var filterDateTo = $('#filterDateTo').val();

        var clientNodeHtml = settings.aoData[dataIndex].anCells[1].innerHTML || '';
        var contactNodeText = settings.aoData[dataIndex].anCells[2].innerText || '';
        var statusNodeText = settings.aoData[dataIndex].anCells[7].innerText || '';
        var datesNodeText = settings.aoData[dataIndex].anCells[5].innerText || '';

        if (filterNid) {
            var hasNid = contactNodeText.indexOf('NID:') !== -1;
            if (filterNid === 'Yes' && hasNid) return false;
            if (filterNid === 'No' && !hasNid) return false;
        }

        if (filterSpecial) {
            var hasSpecial = clientNodeHtml.indexOf('Special User') !== -1;
            if (filterSpecial === 'Yes' && !hasSpecial) return false;
            if (filterSpecial === 'No' && hasSpecial) return false;
        }

        if (filterBilling && statusNodeText.indexOf(filterBilling) === -1) return false;
        if (filterRouter && statusNodeText.indexOf(filterRouter) === -1) return false;

        if (filterDateFrom || filterDateTo) {
            var cycleMatch = datesNodeText.match(/Bill:\\s*(\\d{4}-\\d{2}-\\d{2})/);
            if (cycleMatch && cycleMatch[1]) {
                var cycleDate = new Date(cycleMatch[1]);
                if (filterDateFrom && cycleDate < new Date(filterDateFrom)) return false;
                if (filterDateTo && cycleDate > new Date(filterDateTo)) return false;
            } else {
                return false;
            }
        }

        return true;
    });

    $('#applyFilterBtn').on('click', function() {
        table.draw();
        var offcanvasElement = document.getElementById('filterOffcanvas');
        var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (offcanvas) offcanvas.hide();
    });

    $('#resetFilterBtn').on('click', function() {
        $('#customerFilterForm')[0].reset();
        $('.filter-switch-checkbox').attr('data-filtered', 'false');
        table.draw();
    });
"""

portals = [
    ('defaultportal/customer.php', 'simple'),
    ('billingportal/customer.php', 'inline_filters'),
    ('transmission/customer.php', 'inline_filters'),
    ('txbill/customer.php', 'inline_filters'),
]


def process_portal(fpath, toolbar_type):
    print(f"\n--- Processing {fpath} ---")

    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    if 'filterOffcanvas' in content:
        print(f"  Already has filterOffcanvas. Skipping.")
        return

    # ── 1. Toolbar: add Filter button ──────────────────────────────────────────
    if toolbar_type == 'inline_filters':
        # Remove old inline filter div (filterPaidStatus, filterPPPStatus, clearTableFilters)
        content = re.sub(
            r'\s*<div class="d-flex align-items-center gap-4 ms-auto">.*?</div>',
            '',
            content,
            count=1,
            flags=re.DOTALL
        )
        print(f"  Removed old inline filter toggles")

    # Add Filter button before closing the d-flex gap-2 toolbar div
    # Target: <?php endif; ?>\n            </div> (end of gap-2)
    added = False
    for endpattern in [
        '                <?php endif; ?>\n            </div>\n        </div>\n',
        '                <?php endif; ?>\r\n            </div>\r\n        </div>\r\n',
    ]:
        if endpattern in content:
            content = content.replace(
                endpattern,
                '                <?php endif; ?>\n' + filter_button_html + '            </div>\n        </div>\n',
                1
            )
            print(f"  Filter button added to toolbar")
            added = True
            break
    if not added:
        print(f"  WARNING: Could not inject Filter button")

    # ── 2. Insert Offcanvas HTML before footer ─────────────────────────────────
    footer_include = "<?php include '../includes/footer.php'; ?>"
    if footer_include in content:
        content = content.replace(
            footer_include,
            offcanvas_html + '\n' + footer_include,
            1
        )
        print(f"  Filter Offcanvas HTML inserted before footer")
    else:
        print(f"  WARNING: Could not find footer include")

    # ── 3. Insert filter JS ────────────────────────────────────────────────────
    # Try to replace old filterPaidStatus + filterPPPStatus JS block
    old_block = re.search(
        r"( {4}var showUnpaidOnly.*?table\.draw\(\);\s*\n {4}\}\);)",
        content, re.DOTALL
    )
    if old_block:
        # Replace old filter logic with new filter js
        content = content[:old_block.start()] + js_filter_block.strip() + '\n' + content[old_block.end():]
        print(f"  Old inline filter JS replaced with new filter JS")
    else:
        # No old js – inject before closing }); of document.ready
        content = content.replace('\n});\n</script>', '\n' + js_filter_block.strip() + '\n\n});\n</script>', 1)
        print(f"  Filter JS injected before end of document.ready")

    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"  DONE: {fpath}")


for fpath, ttype in portals:
    process_portal(fpath, ttype)

print("\nAll portals processed!")
