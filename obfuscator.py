import sys
import random
import string
import re

def random_name():
    chars = "嗨死你妈跟我搞" + string.ascii_letters
    return "".join(random.choice(chars) for _ in range(8))

filename = sys.argv[1]

with open(filename, "r", encoding="utf-8") as f:
    code = f.read()

methods = re.findall(r'void\s+(\w+)\s*\(', code)

mapping = {}

for m in methods:
    if m not in mapping:
        mapping[m] = random_name()

for old, new in mapping.items():
    code = re.sub(rf'\b{old}\b', new, code)

out = "obf_" + filename
with open(out, "w", encoding="utf-8") as f:
    f.write(code)

print("Done:", out)