import sys
sys.path.insert(0, 'bondhon-master')
import bondhon
from bs4 import BeautifulSoup, NavigableString
import re

with open('customerportal/instructions.php', 'r', encoding='utf-8') as f:
    html = f.read()

soup = BeautifulSoup(html, 'html.parser')

def translate_bengali(text):
    def convert_match(m):
        word = m.group(0)
        try:
            return bondhon.convert('utf-8', 'bijoy', word)
        except Exception:
            return word
    return re.sub(r'[\u0980-\u09FF]+', convert_match, text)

for element in soup.find_all(string=True):
    if isinstance(element, NavigableString):
        if element.parent.name in ['script', 'style']:
            continue
        new_text = translate_bengali(element)
        if new_text != element:
            element.replace_with(new_text)

with open('customerportal/instructions.php', 'w', encoding='utf-8') as f:
    f.write(str(soup))
print('done')
