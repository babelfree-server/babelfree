#!/usr/bin/env python3
"""
dedup-games.py — Remove duplicate games from dest1-58.json files.

Fingerprint: type + prompt/instruction/question + first 200 chars of
JSON-serialized pairs/options/questions/turns/categories/items/sections.

Protected types: 'cronica' and 'escaperoom' are NEVER removed, even if
they look like duplicates.
"""

import json
import os
import sys

CONTENT_DIR = "/home/babelfree.com/public_html/content"
PROTECTED_TYPES = {"cronica", "escaperoom"}

# Keys used for the text component of the fingerprint (tried in order, first hit wins)
TEXT_KEYS = ["prompt", "instruction", "question", "title", "text"]

# Keys used for the data component of the fingerprint (all present are combined)
DATA_KEYS = ["pairs", "options", "questions", "turns", "categories", "items",
             "sections", "words", "puzzles"]


def fingerprint(game: dict) -> str:
    """Build a dedup fingerprint for a game object."""
    gtype = game.get("type", "unknown")

    # Text part: first non-empty value among TEXT_KEYS
    text_part = ""
    for k in TEXT_KEYS:
        val = game.get(k)
        if val:
            text_part = str(val).strip()
            break

    # Data part: concatenate JSON of all present DATA_KEYS, truncated to 200 chars
    data_parts = []
    for k in DATA_KEYS:
        val = game.get(k)
        if val is not None:
            data_parts.append(json.dumps(val, ensure_ascii=False, sort_keys=True))
    data_blob = "|".join(data_parts)[:200]

    return f"{gtype}||{text_part}||{data_blob}"


def dedup_destination(filepath: str) -> int:
    """Dedup games in a single destination file. Returns count of removed dupes."""
    with open(filepath, "r", encoding="utf-8") as f:
        data = json.load(f)

    games = data.get("games")
    if not games or not isinstance(games, list):
        return 0

    seen = set()
    deduped = []
    removed = 0

    for game in games:
        gtype = game.get("type", "unknown")

        # Never remove protected types
        if gtype in PROTECTED_TYPES:
            deduped.append(game)
            continue

        fp = fingerprint(game)

        if fp in seen:
            removed += 1
        else:
            seen.add(fp)
            deduped.append(game)

    if removed > 0:
        data["games"] = deduped
        with open(filepath, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.write("\n")

    return removed


def main():
    total_removed = 0
    results = []

    for i in range(1, 59):
        filename = f"dest{i}.json"
        filepath = os.path.join(CONTENT_DIR, filename)

        if not os.path.exists(filepath):
            print(f"  SKIP  {filename} (not found)")
            continue

        removed = dedup_destination(filepath)
        total_removed += removed

        if removed > 0:
            results.append((filename, removed))
            print(f"  DEDUP {filename}: removed {removed} duplicate(s)")
        else:
            print(f"  OK    {filename}: no duplicates")

    print()
    print("=" * 50)
    print(f"TOTAL duplicates removed: {total_removed}")
    if results:
        print(f"Files modified: {len(results)}")
        for fn, n in results:
            print(f"  {fn}: {n}")
    else:
        print("No duplicates found in any file.")

    return 0


if __name__ == "__main__":
    sys.exit(main())
