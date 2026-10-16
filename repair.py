import re
import sys

# 1. Read manager/customer.php
with open('manager/customer.php', 'r', encoding='utf-8') as f:
    manager_content = f.read()

# 2. Extract full Offcanvas from nocportal/customer.php
with open('nocportal/customer.php', 'r', encoding='utf-8') as f:
    noc_content = f.read()

offcanvas_match = re.search(r'<!-- Filter Offcanvas -->.*?<script', noc_content, re.DOTALL)
if not offcanvas_match:
    print('Failed to find offcanvas in nocportal')
    sys.exit(1)

offcanvas_html = offcanvas_match.group(0)[:-7].strip() # remove '<script'

# 3. Remove all broken Offcanvas blocks in manager/customer.php
manager_content = re.sub(r'<!-- Filter Offcanvas -->.*?<script', '<script', manager_content, flags=re.DOTALL)

# 4. Insert full offcanvas before the very last <script>
# Wait, manager_content has only one <script> tag now because we removed the duplicates? No, there might be multiple <script> tags.
# In nocportal/customer.php, the offcanvas is right before the JS block.
# Let's insert it before the first <script> we find near the end of the file.
manager_content = manager_content.replace('<script', offcanvas_html + '\n\n<script', 1)

# 5. Fix the button placement
button_regex = r'\s*<button class=\"btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1\" type=\"button\" data-bs-toggle=\"offcanvas\" data-bs-target=\"#filterOffcanvas\"[^>]*>.*?<\/button>\n'
manager_content = re.sub(button_regex, '', manager_content, flags=re.DOTALL)

button_html = '''
                <button class="btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" style="font-size: 12px; font-weight: 500; color: #475569;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
'''

import_btn_regex = r'(<button type=\"button\" class=\"btn btn-sm btn-white border-slate-200 shadow-sm d-flex align-items-center gap-1\" data-bs-toggle=\"modal\" data-bs-target=\"#importClientModal\".*?<\/button>\n\s*<\?php endif; \?>)'
manager_content = re.sub(import_btn_regex, r'\1' + button_html, manager_content, count=1, flags=re.DOTALL)

with open('manager/customer.php', 'w', encoding='utf-8') as f:
    f.write(manager_content)

print('Repaired manager/customer.php successfully')
