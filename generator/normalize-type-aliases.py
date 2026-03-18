#!/usr/bin/env python3
"""
Normalize game type aliases to canonical names across all 58 destination JSONs.

Reads the typeMap from js/yaguara-engine.js, then scans each dest{N}.json
in content/ and replaces any alias game.type with its canonical form.
"""

import json
import os
import re
import sys
from collections import defaultdict

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ENGINE_PATH = os.path.join(BASE_DIR, "js", "yaguara-engine.js")
CONTENT_DIR = os.path.join(BASE_DIR, "content")


def extract_type_map(engine_path):
    """Parse the typeMap object from yaguara-engine.js source."""
    with open(engine_path, "r", encoding="utf-8") as f:
        source = f.read()

    # Find the typeMap block: "typeMap: { ... }"
    match = re.search(r"typeMap:\s*\{([^}]+)\}", source)
    if not match:
        print("ERROR: Could not find typeMap in", engine_path)
        sys.exit(1)

    block = match.group(1)

    # Extract each 'alias': 'canonical' pair
    type_map = {}
    for pair in re.finditer(r"'([^']+)'\s*:\s*'([^']+)'", block):
        alias, canonical = pair.group(1), pair.group(2)
        type_map[alias] = canonical

    return type_map


def normalize_destination(filepath, type_map, stats):
    """Normalize game types in a single destination JSON file.

    Returns True if the file was modified.
    """
    with open(filepath, "r", encoding="utf-8") as f:
        data = json.load(f)

    modified = False
    dest_name = os.path.basename(filepath)

    # Check games array
    games = data.get("games", [])
    for game in games:
        game_type = game.get("type", "")
        if game_type in type_map:
            canonical = type_map[game_type]
            stats[(game_type, canonical)] += 1
            game["type"] = canonical
            modified = True

    # Also check arrival.type (sometimes has aliases)
    arrival = data.get("arrival", {})
    if isinstance(arrival, dict):
        arrival_type = arrival.get("type", "")
        if arrival_type in type_map:
            canonical = type_map[arrival_type]
            stats[(arrival_type, canonical)] += 1
            arrival["type"] = canonical
            modified = True

    # Check escaperoom if present
    escaperoom = data.get("escaperoom", {})
    if isinstance(escaperoom, dict):
        esc_type = escaperoom.get("type", "")
        if esc_type in type_map:
            canonical = type_map[esc_type]
            stats[(esc_type, canonical)] += 1
            escaperoom["type"] = canonical
            modified = True

    if modified:
        with open(filepath, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.write("\n")

    return modified


def main():
    # Step 1: Extract typeMap
    type_map = extract_type_map(ENGINE_PATH)
    print(f"Extracted {len(type_map)} aliases from typeMap:\n")
    for alias, canonical in sorted(type_map.items(), key=lambda x: (x[1], x[0])):
        print(f"  '{alias}' -> '{canonical}'")
    print()

    # Step 2: Process each destination file
    stats = defaultdict(int)
    files_modified = 0

    for n in range(1, 59):
        filepath = os.path.join(CONTENT_DIR, f"dest{n}.json")
        if not os.path.exists(filepath):
            print(f"WARNING: {filepath} not found, skipping")
            continue
        if normalize_destination(filepath, type_map, stats):
            files_modified += 1

    # Step 3: Report
    total = sum(stats.values())
    print(f"--- Results ---")
    print(f"Files scanned:  58")
    print(f"Files modified: {files_modified}")
    print(f"Total replacements: {total}\n")

    if stats:
        print("Replacements by alias -> canonical:")
        for (alias, canonical), count in sorted(stats.items(), key=lambda x: (-x[1], x[0])):
            print(f"  '{alias}' -> '{canonical}': {count}")
    else:
        print("No aliases found — all game types are already canonical.")


if __name__ == "__main__":
    main()
