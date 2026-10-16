with open('manager/customer.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if '<!-- Filter Offcanvas -->' in line:
        print('Offcanvas starts at line', i)
    if 'id="filterOffcanvas"' in line:
        print('filterOffcanvas id found at line', i)
    if 'Filter Customers' in line:
        print('Filter Customers at line', i)
    if '<script' in line:
        print('<script at line', i)
