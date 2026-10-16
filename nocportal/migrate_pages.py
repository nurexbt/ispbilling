import os
import re
import shutil

src_dir = r"c:\xampp\htdocs\billsoft"
dest_dir = r"c:\xampp\htdocs\billsoft\nocportal"
files = ["host.php", "package.php", "area.php", "customer.php", "connection_request.php", "payment_record.php"]

for file in files:
    src_path = os.path.join(src_dir, file)
    dest_path = os.path.join(dest_dir, file)
    
    with open(src_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    # Update includes
    content = content.replace("require_once 'includes/db.php';", "require_once '../includes/db.php';\n$base_path = '../';\n$is_noc = true;")
    content = content.replace("include 'includes/header.php';", "include '../includes/header.php';")
    content = content.replace("include 'includes/sidebar.php';", "include 'sidebar.php';")
    
    # Replace permission check block
    pattern = r"(include '\.\./includes/header\.php';\s*)(if \(!has_permission.*?\}.*?exit;\s*\})(\s*include 'sidebar\.php';)"
    
    new_perm_block = """
if (!has_permission('noc_full_access')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-danger shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Access Denied.</div></div>';
    echo '</main></div></div></body></html>';
    exit;
}"""
    content = re.sub(pattern, r"\1" + new_perm_block + r"\3", content, flags=re.DOTALL)
    
    # Replace AJAX URLs
    content = re.sub(r"url:\s*'ajax/", "url: '../ajax/", content)
    content = re.sub(r'url:\s*"ajax/', 'url: "../ajax/', content)
    
    # In case there are links to images/uploads
    content = re.sub(r"src='uploads/", "src='../uploads/", content)
    content = re.sub(r'src="uploads/', 'src="../uploads/', content)
    
    # Maybe some header paths like href="package.php" should remain local, which they are.
    
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Migrated", file)
