import re

file_path = r"c:\xampp\htdocs\billsoft\nocportal\support_tickets.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# Update includes
content = content.replace("require_once 'includes/db.php';", "require_once '../includes/db.php';\n$base_path = '../';\n$is_noc = true;")
content = content.replace("include 'includes/header.php';", "include '../includes/header.php';")
content = content.replace("include 'includes/sidebar.php';", "include 'sidebar.php';")
content = content.replace("include 'includes/footer.php';", "include '../includes/footer.php';")

# Replace permission check block
pattern = r"(include '\.\./includes/header\.php';\s*)(if \(!has_permission.*?\}.*?exit;\s*\})(\s*include 'sidebar\.php';)"
new_perm_block = """
if (!has_permission('noc_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}"""
content = re.sub(pattern, r"\1" + new_perm_block + r"\3", content, flags=re.DOTALL)

# Fallback permission replace if regex didn't hit
if "if (!has_permission('noc_full_access'))" not in content:
    pattern_fallback = r"(if \(!has_permission.*?\}.*?exit;\s*\})"
    content = re.sub(pattern_fallback, new_perm_block, content, count=1, flags=re.DOTALL)

# Replace AJAX URLs
content = re.sub(r"url:\s*'ajax/", "url: '../ajax/", content)
content = re.sub(r'url:\s*"ajax/', 'url: "../ajax/', content)
content = re.sub(r"fetch\('ajax/", "fetch('../ajax/", content)
content = re.sub(r'fetch\("ajax/', 'fetch("../ajax/', content)
content = re.sub(r"href='ajax/", "href='../ajax/", content)
content = re.sub(r'href="ajax/', 'href="../ajax/', content)

# Images / assets
content = re.sub(r"src='uploads/", "src='../uploads/", content)
content = re.sub(r'src="uploads/', 'src="../uploads/', content)
content = re.sub(r'src="assets/', 'src="../assets/', content)
content = re.sub(r"src='assets/", "src='../assets/", content)
content = re.sub(r"href='assets/", "href='../assets/", content)
content = re.sub(r'href="assets/', 'href="../assets/', content)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
print("Done migrating support_tickets.php")
