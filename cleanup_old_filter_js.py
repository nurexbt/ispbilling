"""
Remove leftover old JS filter handlers from billingportal, transmission, txbill
"""

import re

files = [
    'billingportal/customer.php',
    'transmission/customer.php',
    'txbill/customer.php',
]

# Remove old showUnpaidOnly / showOfflineOnly / filterPaidStatus JS blocks
old_filter_js_pattern = re.compile(
    r"\s*var showUnpaidOnly.*?table\.draw\(\);\s*\}\);\s*\n",
    re.DOTALL
)

# Remove stray clearTableFilters handler that references old variables
clear_filter_js_pattern = re.compile(
    r"\s*\$\('#clearTableFilters'\)\.on\('click',\s*function\(\)\s*\{.*?\}\);\s*\n",
    re.DOTALL
)

for fpath in files:
    print(f"\n--- Cleaning JS in {fpath} ---")
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content

    m = old_filter_js_pattern.search(content)
    if m:
        print(f"  Removing old showUnpaidOnly JS block (chars {m.start()}-{m.end()})")
        content = content[:m.start()] + content[m.end():]
    else:
        print(f"  No old showUnpaidOnly JS found")

    m2 = clear_filter_js_pattern.search(content)
    if m2:
        print(f"  Removing stray clearTableFilters handler")
        content = content[:m2.start()] + content[m2.end():]
    else:
        print(f"  No stray clearTableFilters JS found")

    if content != original:
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"  Saved {fpath}")
    else:
        print(f"  No changes")

print("\nJS cleanup done!")
