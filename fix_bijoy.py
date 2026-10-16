import re
from bijoy2unicode.converter import Unicode

with open('customerportal/instructions.php', 'r', encoding='utf-8') as f:
    text = f.read()

text = text.replace('class="bangla-text"', '')
text = text.replace(' bangla-text', '')
text = text.replace('<div class="bijoy-text">', '<div>')
text = text.replace(' class="bijoy-text"', '')

c = Unicode()

def convert_match(m):
    word = m.group(0)
    try:
        bijoy_text = c.convertUnicodeToBijoy(word)
        return '<span class="bijoy-text">' + bijoy_text + '</span>'
    except Exception as e:
        return '<span class="bijoy-text">' + word + '</span>'

new_text = re.sub(r'[\u0980-\u09FF]+', convert_match, text)

with open('customerportal/instructions.php', 'w', encoding='utf-8') as f:
    f.write(new_text)

print('done')
