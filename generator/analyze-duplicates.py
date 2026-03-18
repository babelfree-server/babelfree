#!/usr/bin/env python3
"""
Duplicate Game Analyzer for El Viaje del Jaguar
================================================

Scans all dest*.json files in /content/ for games that share the same
type + instruction/prompt within a destination.

For each such group, compares the FULL game content (all fields except
the prompt/instruction itself) to categorize:

  (a) TRUE duplicates  — identical content beyond type+prompt → safe to remove
  (b) FALSE duplicates — same type+prompt but different questions/options/pairs → keep both

Output: per-destination summary + removal recommendations for true duplicates.

IMPORTANT: This script is READ-ONLY. It does NOT modify any files.
"""

import json
import os
import sys
import copy
from collections import defaultdict

CONTENT_DIR = "/home/babelfree.com/public_html/content"

# Fields that form the "grouping key" (type + prompt text)
PROMPT_FIELDS = {"instruction", "prompt"}
# Fields to EXCLUDE when comparing content (they're part of the grouping key
# or are cosmetic/non-content fields)
EXCLUDE_FROM_CONTENT = {"type", "instruction", "prompt", "label"}


def get_prompt_key(game):
    """Return a (type, prompt_text) tuple for grouping."""
    game_type = game.get("type", "unknown")
    # Try 'instruction' first (most game types), then 'prompt' (cronica, etc.)
    prompt_text = game.get("instruction", game.get("prompt", ""))
    return (game_type, prompt_text)


def get_content_fingerprint(game):
    """
    Return a comparable representation of the game's content,
    excluding the grouping key fields and cosmetic fields.
    We do a deep copy to avoid mutating the original, then strip
    the key/cosmetic fields and JSON-serialize for comparison.
    """
    g = copy.deepcopy(game)
    for field in EXCLUDE_FROM_CONTENT:
        g.pop(field, None)
    # Sort keys for deterministic comparison
    return json.dumps(g, sort_keys=True, ensure_ascii=False)


def analyze_destination(filepath):
    """
    Analyze a single dest JSON file.

    Returns:
      dest_name (str),
      true_dupes (list of dicts with group info + indices to remove),
      false_dupes (list of dicts with group info),
      total_games (int)
    """
    with open(filepath, "r", encoding="utf-8") as f:
        data = json.load(f)

    dest_name = data.get("meta", {}).get("destination", os.path.basename(filepath))
    games = data.get("games", [])
    total_games = len(games)

    # Group games by (type, prompt)
    groups = defaultdict(list)
    for idx, game in enumerate(games):
        key = get_prompt_key(game)
        groups[key].append((idx, game))

    true_dupes = []
    false_dupes = []

    for key, members in groups.items():
        if len(members) < 2:
            continue  # No duplicates possible

        game_type, prompt_text = key

        # Within this group, sub-group by content fingerprint
        by_fingerprint = defaultdict(list)
        for idx, game in members:
            fp = get_content_fingerprint(game)
            by_fingerprint[fp].append(idx)

        if len(by_fingerprint) == 1:
            # All members have identical content → TRUE duplicates
            all_indices = list(by_fingerprint.values())[0]
            keep = all_indices[0]
            remove = all_indices[1:]
            true_dupes.append({
                "type": game_type,
                "prompt": prompt_text[:80],
                "count": len(all_indices),
                "keep_index": keep,
                "remove_indices": remove,
            })
        elif len(by_fingerprint) < len(members):
            # Some identical, some different — mixed case
            # Find sub-groups with >1 member (those are true dupes within the group)
            has_true = False
            has_false = False
            for fp, indices in by_fingerprint.items():
                if len(indices) > 1:
                    has_true = True
                    keep = indices[0]
                    remove = indices[1:]
                    true_dupes.append({
                        "type": game_type,
                        "prompt": prompt_text[:80],
                        "count": len(indices),
                        "keep_index": keep,
                        "remove_indices": remove,
                        "note": "mixed group (some unique variants exist too)"
                    })
                # Single-member fingerprint sub-groups are unique variants — not dupes
            # Also record the whole group as having false duplicates
            false_dupes.append({
                "type": game_type,
                "prompt": prompt_text[:80],
                "count": len(members),
                "indices": [idx for idx, _ in members],
                "unique_variants": len(by_fingerprint),
            })
        else:
            # All members have different content → FALSE duplicates
            false_dupes.append({
                "type": game_type,
                "prompt": prompt_text[:80],
                "count": len(members),
                "indices": [idx for idx, _ in members],
                "unique_variants": len(by_fingerprint),
            })

    return dest_name, true_dupes, false_dupes, total_games


def get_field_diff_summary(game_a, game_b):
    """Return a brief summary of which fields differ between two games."""
    diffs = []
    all_keys = set(game_a.keys()) | set(game_b.keys())
    for k in sorted(all_keys):
        if k in EXCLUDE_FROM_CONTENT:
            continue
        val_a = game_a.get(k)
        val_b = game_b.get(k)
        if val_a != val_b:
            diffs.append(k)
    return diffs


def main():
    # Find all dest*.json files (not in quarantine)
    dest_files = []
    for fname in sorted(os.listdir(CONTENT_DIR)):
        if fname.startswith("dest") and fname.endswith(".json"):
            dest_files.append(os.path.join(CONTENT_DIR, fname))

    if not dest_files:
        print("No dest*.json files found in", CONTENT_DIR)
        sys.exit(1)

    print("=" * 72)
    print("DUPLICATE GAME ANALYSIS")
    print(f"Scanning {len(dest_files)} destination files in {CONTENT_DIR}")
    print("=" * 72)
    print()

    grand_total_games = 0
    grand_true_dupes = 0
    grand_true_removable = 0
    grand_false_dupes = 0
    dests_with_true = []
    dests_with_false = []

    # Also collect all games for a global type+prompt cross-check
    all_prompt_keys = defaultdict(int)

    for filepath in dest_files:
        dest_name, true_dupes, false_dupes, total_games = analyze_destination(filepath)
        grand_total_games += total_games

        n_true_groups = len(true_dupes)
        n_true_removable = sum(len(td["remove_indices"]) for td in true_dupes)
        n_false_groups = len(false_dupes)

        grand_true_dupes += n_true_groups
        grand_true_removable += n_true_removable
        grand_false_dupes += n_false_groups

        # Collect prompt keys
        with open(filepath, "r", encoding="utf-8") as f:
            data = json.load(f)
        for game in data.get("games", []):
            key = get_prompt_key(game)
            all_prompt_keys[key] += 1

        if n_true_groups > 0 or n_false_groups > 0:
            header = f"  {dest_name} ({total_games} games)"
            print(header)
            print("  " + "-" * (len(header) - 2))

            if n_true_groups > 0:
                dests_with_true.append(dest_name)
                print(f"    TRUE DUPLICATES: {n_true_groups} group(s), "
                      f"{n_true_removable} game(s) removable")
                for td in true_dupes:
                    note = f" [{td['note']}]" if "note" in td else ""
                    print(f"      type={td['type']}, prompt=\"{td['prompt']}\"")
                    print(f"        {td['count']} identical copies. "
                          f"Keep index {td['keep_index']}, "
                          f"remove {td['remove_indices']}{note}")

            if n_false_groups > 0:
                dests_with_false.append(dest_name)
                print(f"    FALSE DUPLICATES (keep all): {n_false_groups} group(s)")
                for fd in false_dupes:
                    print(f"      type={fd['type']}, prompt=\"{fd['prompt']}\"")
                    print(f"        {fd['count']} games at indices {fd['indices']}, "
                          f"{fd['unique_variants']} unique variant(s)")

                    # Show what differs between them
                    # Read the games for diff summary
                    with open(filepath, "r", encoding="utf-8") as f:
                        data = json.load(f)
                    games_list = data.get("games", [])
                    first_idx = fd["indices"][0]
                    for other_idx in fd["indices"][1:]:
                        diffs = get_field_diff_summary(
                            games_list[first_idx], games_list[other_idx]
                        )
                        if diffs:
                            print(f"        game[{first_idx}] vs game[{other_idx}] "
                                  f"differ in: {', '.join(diffs)}")
                        else:
                            print(f"        game[{first_idx}] vs game[{other_idx}] "
                                  f"IDENTICAL (should be true dupe?)")

            print()

    # Grand summary
    print("=" * 72)
    print("GRAND SUMMARY")
    print("=" * 72)
    print(f"Total destination files scanned: {len(dest_files)}")
    print(f"Total games across all destinations: {grand_total_games}")
    print()
    print(f"TRUE DUPLICATE groups: {grand_true_dupes}")
    print(f"  Games safely removable: {grand_true_removable}")
    print(f"  Destinations affected: {', '.join(dests_with_true) if dests_with_true else 'none'}")
    print()
    print(f"FALSE DUPLICATE groups (same type+prompt, different content): {grand_false_dupes}")
    print(f"  Destinations affected: {', '.join(dests_with_false) if dests_with_false else 'none'}")
    print()

    # Show the most common type+prompt combos across ALL destinations
    print("-" * 72)
    print("MOST COMMON type+prompt COMBOS (across all destinations):")
    print("(These prompts appear in multiple games and may cause false positives)")
    print("-" * 72)
    for (gtype, prompt), count in sorted(all_prompt_keys.items(),
                                          key=lambda x: -x[1])[:20]:
        if count >= 2:
            print(f"  [{count:3d}x] type={gtype}, prompt=\"{prompt[:70]}\"")

    print()
    print("=" * 72)
    print("REMOVAL PLAN (true duplicates only)")
    print("=" * 72)

    if grand_true_removable == 0:
        print("No true duplicates found. Nothing to remove.")
    else:
        for filepath in dest_files:
            dest_name, true_dupes, _, _ = analyze_destination(filepath)
            if not true_dupes:
                continue
            print(f"\n  {dest_name} ({os.path.basename(filepath)}):")
            all_remove = []
            for td in true_dupes:
                all_remove.extend(td["remove_indices"])
            all_remove.sort(reverse=True)
            print(f"    Remove game indices (0-based): {sorted(all_remove)}")
            print(f"    Total to remove: {len(all_remove)}")

    print()
    print("DONE. No files were modified.")


if __name__ == "__main__":
    main()
