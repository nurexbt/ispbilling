import os

files = [
    r"c:\xampp\htdocs\billsoft\nocportal\store_record.php",
    r"c:\xampp\htdocs\billsoft\manager\store.php"
]

for file_path in files:
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Update flex container to span full width and reduce gap
        content = content.replace('class="d-flex flex-nowrap gap-3 pb-1"', 'class="d-flex flex-nowrap gap-2 pb-1 w-100"')
        
        # Make cards flex: 1, reduce min-width, and reduce padding for a smaller height
        content = content.replace('style="min-width:130px; border-left: 3px solid #', 'style="flex: 1; min-width: 90px; border-left: 3px solid #')
        content = content.replace('padding:12px 16px;"', 'padding:8px 12px;"')
        
        # Reduce font sizes slightly
        content = content.replace('style="font-size:10px; letter-spacing:0.5px;"', 'style="font-size:9px; letter-spacing:0.5px;"')
        content = content.replace('fs-5"', 'fs-6"')
            
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated UI in", file_path)
