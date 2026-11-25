#!/usr/bin/env python3
"""Emit GitHub annotations for failures in junit.xml, grouped by cause.

Usage: junit-annotations.py [offset] [count]
GitHub caps annotations at 10 per step, so CI runs this once per slice of groups.
"""
import re
import sys
import xml.etree.ElementTree as ET
from collections import defaultdict

offset = int(sys.argv[1]) if len(sys.argv) > 1 else 0
count = int(sys.argv[2]) if len(sys.argv) > 2 else 9

try:
    raw = open('junit.xml', encoding='utf-8', errors='replace').read()
    raw = re.sub('[\x00-\x08\x0b\x0c\x0e-\x1f\ufffe\uffff]', '', raw)
    root = ET.fromstring(raw.encode('utf-8'))
except Exception as e:  # noqa: BLE001
    print(f'::warning::no usable junit.xml ({e})')
    sys.exit(0)

groups = defaultdict(list)
for tc in root.iter('testcase'):
    for f in list(tc.findall('failure')) + list(tc.findall('error')):
        text = (f.text or f.get('message') or '').strip()
        key = re.sub(r'\d+', 'N', (f.get('message') or text).strip().splitlines()[0] if text else 'unknown')[:120]
        groups[key].append((tc.get('name') or '', text))

ordered = sorted(groups.items(), key=lambda kv: -len(kv[1]))
if offset == 0:
    print(f'::error title=summary::{sum(len(v) for v in groups.values())} failures, {len(ordered)} groups')

for i, (key, items) in enumerate(ordered[offset:offset + count], start=offset):
    names = ' ; '.join(n for n, _ in items[:4])
    text = items[0][1]
    detail = text if len(text) <= 900 else text[:350] + ' [...] ' + text[-500:]
    msg = f'#{i} x{len(items)} | {names} | {detail}'
    msg = msg.replace('%', '%25').replace('\r', '').replace('\n', '%0A')
    print(f'::error title=group {i}::{msg}')
