import os
import re

dest_dir = r"c:\xampp\htdocs\billsoft\nocportal"
files = ["host.php", "package.php", "area.php", "customer.php", "connection_request.php", "payment_record.php", "store_record.php"]

for file in files:
    dest_path = os.path.join(dest_dir, file)
    
    with open(dest_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    # Update includes/footer.php
    content = content.replace("include 'includes/footer.php';", "include '../includes/footer.php';")
    content = content.replace('include "includes/footer.php";', 'include "../includes/footer.php";')
    
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Fixed footer in", file)
