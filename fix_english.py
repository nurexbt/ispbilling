import re

with open('customerportal/instructions.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Revert my previous bijoy conversions first?
# No, let's keep the ones that worked.
# We just need to wrap all English words (letters) with a span.

def convert_eng(m):
    word = m.group(0)
    # Ignore if inside HTML tag
    return '<span style="font-family: \'Segoe UI\', Arial, sans-serif !important;">' + word + '</span>'

# Match english words not inside HTML tags
# A quick hack is to split by > and <, then replace inside the text parts
parts = re.split(r'(<[^>]+>)', text)
for i in range(len(parts)):
    if i % 2 == 0: # Not a tag
        parts[i] = re.sub(r'[A-Za-z]+(?:[A-Za-z0-9&_\.\-]*)', convert_eng, parts[i])

new_text = ''.join(parts)

with open('customerportal/instructions.php', 'w', encoding='utf-8') as f:
    f.write(new_text)

print('done')
