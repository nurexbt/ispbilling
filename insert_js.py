import re
import sys

# 1. Read manager/customer.php
with open('manager/customer.php', 'r', encoding='utf-8') as f:
    manager_content = f.read()

# 2. Extract JS from nocportal/customer.php
with open('nocportal/customer.php', 'r', encoding='utf-8') as f:
    noc_content = f.read()

js_match = re.search(r'    // Track switch interaction for filtering.*?// Router Status Async Check', noc_content, re.DOTALL)
if not js_match:
    print('Failed to find JS in nocportal')
    sys.exit(1)

js_logic = js_match.group(0)

# Check if it's already there
if 'Track switch interaction for filtering' not in manager_content:
    # Insert it right after the Custom Toolbar Search block
    search_block = "    // Custom Toolbar Search\n    $('#searchCustomer').on('keyup', function() {\n        table.search(this.value).draw();\n    });"
    if search_block in manager_content:
        manager_content = manager_content.replace(search_block, search_block + '\n\n' + js_logic)
        with open('manager/customer.php', 'w', encoding='utf-8') as f:
            f.write(manager_content)
        print('JS logic inserted successfully')
    else:
        print('Could not find search block to anchor JS insertion')
        
        # Alternatively find table initialization
        table_block = "var table = $('#customerTable').DataTable({"
        if table_block in manager_content:
             print("Found table block instead")
        else:
             print("Table block missing too")
else:
    print('JS logic already exists')
