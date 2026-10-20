"""
Cleanup: remove leftover old inline filter HTML from billingportal, transmission, txbill
"""

import re

files = [
    'billingportal/customer.php',
    'transmission/customer.php',
    'txbill/customer.php',
]

# Pattern 1: individual form-check divs (filterPaidStatus, filterPPPStatus)
form_check_pattern = re.compile(
    r'\s*<div class="form-check form-switch m-0 d-flex align-items-center gap-2">\s*'
    r'<input[^>]*id="filter(?:PaidStatus|PPPStatus)"[^>]*>\s*'
    r'<label[^>]*for="filter(?:PaidStatus|PPPStatus)"[^>]*>.*?</label>\s*'
    r'</div>',
    re.DOTALL
)

# Pattern 2: clearTableFilters button
clear_btn_pattern = re.compile(
    r'\s*<button type="button" id="clearTableFilters"[^>]*>.*?</button>',
    re.DOTALL
)

# Pattern 3: closing stray </div> that was the ms-auto container closer
# After removing the inner divs, there may be a leftover </div>\n\n
# We'll also remove the closing stray </div> that immediately follows (before gap-2 div)
stray_close_pattern = re.compile(
    r'(\s*</div>\n)(\s*\n\s*<div class="d-flex gap-2">)',
)

for fpath in files:
    print(f"\n--- Cleaning {fpath} ---")
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content

    matches = form_check_pattern.findall(content)
    print(f"  Found {len(matches)} form-check filter divs")
    content = form_check_pattern.sub('', content)

    btn_matches = clear_btn_pattern.findall(content)
    print(f"  Found {len(btn_matches)} clearTableFilters buttons")
    content = clear_btn_pattern.sub('', content)

    # Now clean up the stray closing </div> from the ms-auto container
    # Before the d-flex gap-2 div there should only be one </div> (closing searchCustomer div)
    # Pattern: two consecutive </div> lines before <div class="d-flex gap-2">
    # Replace double </div> before gap-2 with single </div>
    content = re.sub(
        r'(\s*</div>\s*\n)(\s*</div>\s*\n)(\s*\n\s*<div class="d-flex gap-2">)',
        r'\1\3',
        content
    )

    if content != original:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"  Cleaned and saved {fpath}")
    else:
        print(f"  No changes needed")

print("\nCleanup done!")
