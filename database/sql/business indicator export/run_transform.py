import re, json
from collections import OrderedDict
sq = chr(39)  # single quote
dq = chr(34)  # double quote
bt = chr(96)  # backtick
bs = chr(92)  # backslash


filepath = r'c:\project\SmartAgro-API\database\sql\business indicator export\harvest_prices_clean.sql'
outpath  = r'c:\project\SmartAgro-API\database\sql\business indicator export\harvest_prices_final.sql'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

insert_marker = 'INSERT INTO `harvest_prices`'
first_insert = content.find(insert_marker)
header = content[:first_insert]

old_str = (
    '  `id_plan` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,'
)
new_str = (
    '  `id_plan` int(11) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,'
)
header = header.replace(old_str, new_str)

def parse_tuples(section):
    vm = re.search(r'VALUES\s*', section)
    if not vm: return []
    pos = vm.end()
    tuples, cur, depth = [], "", 0
    in_str, esc, sc = False, False, None
    i = pos
    while i < len(section):
        c = section[i]
        if esc: cur += c; esc = False; i += 1; continue
        if c == bs and in_str: cur += c; esc = True; i += 1; continue
        if in_str:
            if c == sc: in_str = False
            cur += c; i += 1; continue
        if c in (sq, dq, bt): in_str = True; sc = c; cur += c; i += 1; continue
        if c == '(': depth += 1; cur += c; i += 1; continue
        if c == ')':
            depth -= 1; cur += c
            if depth == 0:
                tuples.append(cur.strip()); cur = ""
                i += 1
                while i < len(section) and section[i] in (',', ' ', '\n', '\r', '\t'): i += 1
                continue
            i += 1; continue
        if depth > 0: cur += c
        i += 1
    return tuples
