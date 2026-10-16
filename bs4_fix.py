from bs4 import BeautifulSoup, NavigableString
from bijoy2unicode.converter import Unicode
import re

c = Unicode()

with open('customerportal/instructions.php', 'r', encoding='utf-8') as f:
    html = f.read()

soup = BeautifulSoup(html, 'html.parser')

def translate_bengali(text):
    def convert_match(m):
        word = m.group(0)
        try:
            return c.convertUnicodeToBijoy(word)
        except Exception:
            return word
    return re.sub(r'[\u0980-\u09FF]+', convert_match, text)

for element in soup.find_all(string=True):
    if isinstance(element, NavigableString):
        # Skip content inside script and style tags
        if element.parent.name in ['script', 'style']:
            continue
        new_text = translate_bengali(element)
        if new_text != element:
            element.replace_with(new_text)

with open('customerportal/instructions.php', 'w', encoding='utf-8') as f:
    # bs4 prettify might break php tags, so just str()
    f.write(str(soup))
print('done')
