#!/usr/bin/env python3
"""
Extract missing languages from the all-languages Kaikki raw dump.
Reads raw-wiktextract-data.jsonl.gz, filters for target languages,
writes one JSONL file per language.
"""

import gzip
import json
import sys
import os

DATA_DIR = os.path.dirname(os.path.abspath(__file__)) + '/data/kaikki'
INPUT = os.path.join(DATA_DIR, 'raw-wiktextract-data.jsonl.gz')

# Map Kaikki lang_code to our target codes
# Kaikki uses 2-letter codes matching ISO 639-1 mostly
TARGET_LANGS = {
    'hr': 'Croatian',
    'sr': 'Serbian',
    'bs': 'Bosnian',
    'gd': 'Scottish Gaelic',
    'ig': 'Igbo',
    'mi': 'Maori',
    'rw': 'Kinyarwanda',
    'si': 'Sinhala',
    'st': 'Sesotho',
    'tn': 'Setswana',
}
# zh-tw handled separately (filter zh entries with traditional chars)

counts = {lc: 0 for lc in TARGET_LANGS}
files = {}

for lc in TARGET_LANGS:
    outpath = os.path.join(DATA_DIR, f'kaikki-dict-{TARGET_LANGS[lc]}.jsonl')
    files[lc] = open(outpath, 'w', encoding='utf-8')

print(f"Reading {INPUT}...")
print(f"Extracting: {', '.join(TARGET_LANGS.values())}")

line_num = 0
with gzip.open(INPUT, 'rt', encoding='utf-8') as f:
    for line in f:
        line_num += 1
        if line_num % 500000 == 0:
            total = sum(counts.values())
            print(f"  Line {line_num:,} — extracted {total:,} entries so far: {dict((k,v) for k,v in counts.items() if v > 0)}")

        try:
            entry = json.loads(line)
        except json.JSONDecodeError:
            continue

        lang_code = entry.get('lang_code', '')

        if lang_code in TARGET_LANGS:
            files[lang_code].write(line)
            counts[lang_code] += 1

# Close all files
for f in files.values():
    f.close()

print(f"\nDone! Processed {line_num:,} lines.")
print("\nResults:")
for lc, name in sorted(TARGET_LANGS.items(), key=lambda x: -counts[x[0]]):
    c = counts[lc]
    if c > 0:
        print(f"  {lc} ({name}): {c:,} entries → kaikki-dict-{name}.jsonl")
    else:
        print(f"  {lc} ({name}): 0 entries (not in dump)")
