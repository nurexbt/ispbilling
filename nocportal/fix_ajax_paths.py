import os
import re

dest_dir = r"c:\xampp\htdocs\billsoft\nocportal"
files = ["host.php", "package.php", "area.php", "customer.php", "connection_request.php", "payment_record.php", "store_record.php"]

for file in files:
    dest_path = os.path.join(dest_dir, file)
    
    with open(dest_path, "r", encoding="utf-8") as f:
        content = f.read()
    
    # We replace any occurrences of 'ajax/' or "ajax/" with '../ajax/' and "../ajax/"
    # Need to be careful not to double replace '../ajax/' -> '../../ajax/'
    # So we use negative lookbehind: (?<!\.\./)ajax/
    # And we match the quote.
    
    content = re.sub(r"(?<=(['\"]))ajax/", "../ajax/", content)
    
    with open(dest_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Fixed AJAX paths in", file)
