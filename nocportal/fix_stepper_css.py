import os
import re

style_block = """
<style>
.stepper-wrapper { display: flex; align-items: flex-start; justify-content: space-between; position: relative; width: 100%; min-width: 250px; padding-top: 5px; }
.step-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 2; }
.step-circle { width: 14px; height: 14px; border-radius: 50%; background-color: #fff; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; position: relative; z-index: 3; }
.step-circle::after { content: ''; width: 6px; height: 6px; border-radius: 50%; background-color: transparent; }
.step-item.completed .step-circle { border-color: #10b981; }
.step-item.completed .step-circle::after { background-color: #10b981; }
.step-item.error .step-circle { border-color: #ef4444; }
.step-item.error .step-circle::after { background-color: #ef4444; }
.step-line { position: absolute; top: 6px; left: 50%; width: 100%; height: 2px; background-color: #e2e8f0; z-index: 1; }
.step-item.completed .step-line { background-color: #10b981; }
.step-label { font-size: 9px; color: #64748b; margin-top: 4px; font-weight: 500; text-align: center; }
.step-item.completed .step-label { color: #10b981; font-weight: 600; }
.step-item.error .step-label { color: #ef4444; font-weight: 600; }
.step-time { font-size: 8px; color: #94a3b8; margin-top: 2px; text-align: center; line-height: 1.1; }
</style>
"""

files = [
    r"c:\xampp\htdocs\billsoft\nocportal\store_record.php",
    r"c:\xampp\htdocs\billsoft\manager\store.php"
]

for file_path in files:
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # 1. Remove old inline style block if it exists
        old_style_pattern = re.compile(r'<\?php if \(\$i == 2\): // only output style once \?>\s*<style>.*?</style>\s*<\?php endif; \?>', re.DOTALL)
        content = old_style_pattern.sub('', content)
        
        # Also remove if it's there without the php wrapper just in case
        old_style_pattern2 = re.compile(r'<style>\s*\.stepper-wrapper \{.*?</style>', re.DOTALL)
        content = old_style_pattern2.sub('', content)
            
        # 2. Inject CSS at the top
        content = content.replace('<div class="container-fluid pt-2">', '<div class="container-fluid pt-2">\n' + style_block)
            
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print("Fixed stepper CSS in", file_path)
