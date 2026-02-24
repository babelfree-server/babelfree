#!/usr/bin/env python3
"""
build-frequency-list.py — Generate a 10,000-word Spanish frequency list
using the wordfreq library (MIT license, based on SUBTLEX and other corpora).

Output: api/scripts/data/frequency_es.tsv
Format: rank<TAB>word<TAB>zipf_frequency

Usage: python3 build-frequency-list.py [--limit=10000]
"""

import sys
import os
import re

from wordfreq import top_n_list, zipf_frequency

TARGET = 10000
for arg in sys.argv[1:]:
    if arg.startswith('--limit='):
        TARGET = int(arg.split('=')[1])

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(SCRIPT_DIR, 'data')
OUTPUT = os.path.join(DATA_DIR, 'frequency_es.tsv')

# Filters
SKIP_SINGLE_CHARS = True
MIN_LENGTH = 2
# Basic profanity / slang filter (Spanish)
PROFANITY = {
    'puta', 'puto', 'mierda', 'coño', 'joder', 'cabrón', 'cabrona',
    'pendejo', 'pendeja', 'verga', 'culo', 'chingar', 'chingada',
    'marica', 'maricón', 'pinche', 'culero', 'culera', 'mamón',
    'hijueputa', 'malparido', 'gonorrea', 'cojones', 'carajo',
    'follar', 'hostia', 'gilipollas', 'capullo', 'zorra',
}

def is_valid_word(word):
    """Filter out non-words, numbers, single chars, profanity."""
    if len(word) < MIN_LENGTH:
        return False
    # Skip pure numbers or words with digits
    if re.search(r'\d', word):
        return False
    # Skip words with non-letter characters (allow hyphens and accented chars)
    if not re.match(r'^[a-záéíóúüñ\-]+$', word):
        return False
    # Skip profanity
    if word in PROFANITY:
        return False
    return True

def main():
    print(f"Generating top {TARGET} Spanish words from wordfreq...")

    # Get more than needed to account for filtering
    raw_list = top_n_list('es', TARGET * 2)
    print(f"  Raw candidates: {len(raw_list)}")

    # Filter
    filtered = []
    for word in raw_list:
        if is_valid_word(word) and len(filtered) < TARGET:
            filtered.append(word)

    print(f"  After filtering: {len(filtered)}")

    # Write TSV
    os.makedirs(DATA_DIR, exist_ok=True)
    with open(OUTPUT, 'w', encoding='utf-8') as f:
        f.write("rank\tword\tfrequency\n")
        for i, word in enumerate(filtered, 1):
            freq = zipf_frequency(word, 'es')
            f.write(f"{i}\t{word}\t{freq:.2f}\n")

    print(f"  Written: {OUTPUT}")
    print(f"  Total words: {len(filtered)}")

    # Quick stats
    print("\n  Sample (first 20):")
    for i, word in enumerate(filtered[:20], 1):
        freq = zipf_frequency(word, 'es')
        print(f"    {i:5d}  {word:<20s}  {freq:.2f}")

    print(f"\n  Sample (last 10):")
    for i, word in enumerate(filtered[-10:], len(filtered) - 9):
        freq = zipf_frequency(word, 'es')
        print(f"    {i:5d}  {word:<20s}  {freq:.2f}")

if __name__ == '__main__':
    main()
