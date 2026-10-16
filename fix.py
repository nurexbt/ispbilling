with open('manager/customer.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if 'export_customers.php' in line or 'importClientModal' in line or 'data-bs-target="#filterOffcanvas"' in line:
        print(f'{i}: {line.strip()}')
