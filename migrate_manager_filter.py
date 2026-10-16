import sys
import re

# Read nocportal customer.php
with open('nocportal/customer.php', 'r', encoding='utf-8') as f:
    noc_content = f.read()

# Extract filter offcanvas
offcanvas_match = re.search(r'<!-- Filter Offcanvas -->.*?</div>\s*</div>\s*</div>', noc_content, re.DOTALL)
if not offcanvas_match:
    print('Could not find Filter Offcanvas')
    sys.exit(1)
offcanvas_html = offcanvas_match.group(0)

# Extract JS logic
js_match = re.search(r'    // Track switch interaction for filtering.*?// Router Status Async Check', noc_content, re.DOTALL)
if not js_match:
    print('Could not find JS logic')
    sys.exit(1)
js_logic = js_match.group(0)

# Extract filter button
button_html = '''                <button class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" style="font-size: 12px; font-weight: 500; color: #475569;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
'''

# Read manager customer.php
with open('manager/customer.php', 'r', encoding='utf-8') as f:
    manager_content = f.read()

# Insert button
manager_content = re.sub(
    r'(<div class=\"d-flex gap-2\">)(\s*<a href=\"\.\./ajax/export_customers\.php)', 
    r'\1\n' + button_html + r'\2', 
    manager_content,
    count=1
)

# Insert offcanvas html
manager_content = manager_content.replace('<script>', offcanvas_html + '\n\n<script>')

# Insert JS logic
manager_content = manager_content.replace(
    '    // Custom Toolbar Search\n    $(\'#searchCustomer\').on(\'keyup\', function() {\n        table.search(this.value).draw();\n    });\n',
    '    // Custom Toolbar Search\n    $(\'#searchCustomer\').on(\'keyup\', function() {\n        table.search(this.value).draw();\n    });\n\n' + js_logic
)

with open('manager/customer.php', 'w', encoding='utf-8') as f:
    f.write(manager_content)

print('Updated manager/customer.php successfully')
