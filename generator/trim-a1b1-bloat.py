#!/usr/bin/env python3
"""
trim-a1b1-bloat.py
==================
Trims bloated A1-Advanced / A2 / B1 destinations down to 30-35 games.

Targets: dest10(46), dest11(44), dest12(48),
         dest14(48), dest15(48), dest16(48), dest17(48), dest18(48),
         dest19(52), dest21(49), dest22(48), dest23(49)

Rules:
1. Normalize type aliases to canonical forms (same mapping as yaguara-engine.js).
2. Group games by canonical type.
3. Score each game on richness:
   - Number of questions/items/turns/lines (weighted x10)
   - Total text length (weighted x0.01)
   - Story keyword matches (weighted x15 each)
   - 70% penalty for generic games (simple fill with <=2 questions and no story)
4. Per-type caps control how many survive of each type.
5. Adjust total to 30-35 range (remove weakest if over, add back richest if under).
6. PRESERVE original game order among kept games.
7. NEVER remove escaperoom or cronica.
8. Save removed games to content/trimmed/dest{N}_trimmed.json.

Outputs:
- content/trimmed/dest{N}_trimmed.json  (removed games, for reference)
- Overwrites content/dest{N}.json with trimmed version
- Prints before/after counts
"""

import json
import os
import re
from copy import deepcopy

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONTENT_DIR = os.path.join(BASE_DIR, "content")
TRIMMED_DIR = os.path.join(CONTENT_DIR, "trimmed")

# Target destinations
DESTINATIONS = [10, 11, 12, 14, 15, 16, 17, 18, 19, 21, 22, 23]

# ── Type alias → canonical (mirrors yaguara-engine.js typeMap) ──────────────
TYPE_ALIASES = {
    "fib":                   "fill",
    "fill-blank":            "fill",
    "fill-blank-multi":      "fill",
    "fill_blank":            "fill",
    "fill_blank_multi":      "fill",
    "sorting":               "category",
    "category_sort":         "category",
    "category_sort_three":   "category",
    "category-sort":         "category",
    "sort":                  "category",
    "sort3":                 "category",
    "pair_matching":         "pair",
    "pair-matching":         "pair",
    "pairs":                 "pair",
    "sentence_builder":      "builder",
    "sentence-builder":      "builder",
    "sb":                    "builder",
    "build":                 "builder",
    "conj":                  "conjugation",
    "verb_conjugation":      "conjugation",
    "verb_conjugation_multi":"conjugation",
    "verb-conjugation":      "conjugation",
    "verb-conjugation-multi":"conjugation",
    "conjugation_table":     "conjugation",
    "verb_table":            "conjugation",
    "listen":                "listening",
    "listening_multi":       "listening",
    "trans":                 "translation",
    "conv":                  "conversation",
    "dict":                  "dictation",
    "intro":                 "narrative",
    "teach":                 "narrative",
    "context":               "narrative",
    "explain":               "narrative",
    "song":                  "cancion",
    "lyrics":                "cancion",
    "canciones":             "cancion",
    "escape":                "escaperoom",
    "escape_room":           "escaperoom",
    "escape-room":           "escaperoom",
}

def canonical_type(raw_type):
    """Return the canonical game type for a raw type string."""
    return TYPE_ALIASES.get(raw_type, raw_type)

# ── Per-type caps ───────────────────────────────────────────────────────────
TYPE_CAPS = {
    "fill":         5,
    "conjugation":  2,
    "pair":         3,
    "listening":    4,
    "category":     2,
    "builder":      3,
    "narrative":    2,
    "conversation": 3,
    "translation":  2,
    "dictation":    2,
    "cancion":      1,
    "story":        1,
    "escaperoom":   1,
    "cronica":      1,
    "flashnote":    1,
    "crossword":    1,
    "explorador":   1,
    "kloo":         1,
}
DEFAULT_CAP = 2   # For any type not listed above

# ── Story keywords (each match = +15 points) ───────────────────────────────
STORY_KEYWORDS = [
    "yaguará", "yaguara",
    "kogi",
    "mamo",
    "antiguos",
    "montaña",
    "candelaria",
    "don próspero", "próspero",
    "selva",
    "río",
    "jaguar",
]

# ── Protected types: NEVER remove ──────────────────────────────────────────
PROTECTED_TYPES = {"escaperoom", "cronica"}

# ── Target range ────────────────────────────────────────────────────────────
TARGET_MIN = 30
TARGET_MAX = 35
TARGET_IDEAL = 32   # Aim for the middle


# ═══════════════════════════════════════════════════════════════════════════
# Helpers
# ═══════════════════════════════════════════════════════════════════════════

def load_dest(n):
    """Load a destination JSON file."""
    path = os.path.join(CONTENT_DIR, f"dest{n}.json")
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def save_dest(n, data):
    """Save a destination JSON file."""
    path = os.path.join(CONTENT_DIR, f"dest{n}.json")
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write("\n")


def save_trimmed(n, removed_games):
    """Save removed games to trimmed directory for reference."""
    os.makedirs(TRIMMED_DIR, exist_ok=True)
    path = os.path.join(TRIMMED_DIR, f"dest{n}_trimmed.json")
    with open(path, "w", encoding="utf-8") as f:
        json.dump({
            "destination": f"dest{n}",
            "note": "Games removed by trim-a1b1-bloat.py",
            "removedGames": removed_games
        }, ensure_ascii=False, indent=2, fp=f)
        f.write("\n")


def game_text(game):
    """Extract all textual content from a game for scoring."""
    texts = []

    def walk(obj):
        if isinstance(obj, str):
            texts.append(obj)
        elif isinstance(obj, list):
            for item in obj:
                walk(item)
        elif isinstance(obj, dict):
            for v in obj.values():
                walk(v)

    walk(game)
    return " ".join(texts)


def count_items(game):
    """
    Count the number of questions / items / turns / lines / pairs / etc.
    Returns the total count of interactive elements in the game.
    """
    count = 0
    for key in ("questions", "items", "turns", "lines", "pairs", "puzzles",
                "sentences", "exchanges", "steps", "forms", "verbs",
                "words", "cards", "clues", "locations", "prompts",
                "blanks", "categories", "sections"):
        val = game.get(key)
        if isinstance(val, list):
            count += len(val)
        elif isinstance(val, dict):
            count += len(val)
    # For narrative/story with text blocks, count rough paragraphs
    gtype = canonical_type(game.get("type", ""))
    if gtype in ("narrative", "story"):
        text = game.get("text", "")
        if isinstance(text, str) and len(text) > 0:
            count += max(1, len(text) // 100)
    return count


def story_keyword_score(game):
    """Score a game by how many distinct story keywords it contains."""
    text = game_text(game).lower()
    score = 0
    for kw in STORY_KEYWORDS:
        if kw in text:
            score += 1
    return score


def richness_score(game):
    """
    Composite richness score for a game.
    Higher = richer content = prefer to keep.
    """
    items = count_items(game)
    text_len = len(game_text(game))
    story = story_keyword_score(game)

    score = (
        items * 10 +           # more items = richer
        text_len * 0.01 +      # longer content = richer
        story * 15             # story connection = big bonus
    )
    return score


def is_generic(game):
    """
    Detect overly generic games: simple fill with <=2 questions and no story.
    These get a 70% penalty (score *= 0.3).
    """
    gtype = canonical_type(game.get("type", ""))

    if gtype == "fill":
        q_count = len(game.get("questions", []))
        # Also count 'sentences' since some fill variants use that key
        q_count += len(game.get("sentences", []))
        if q_count <= 2 and story_keyword_score(game) == 0:
            return True

    return False


# ═══════════════════════════════════════════════════════════════════════════
# Core trimming logic
# ═══════════════════════════════════════════════════════════════════════════

def trim_destination(n):
    """
    Trim a single destination. Returns (before_count, after_count, removed_games).
    """
    data = load_dest(n)
    games = data.get("games", [])
    before_count = len(games)

    if before_count <= TARGET_MAX:
        print(f"  dest{n}: {before_count} games -- already within target, skipping")
        return before_count, before_count, []

    # ── Step 1: Annotate each game with canonical type and richness score ──
    annotated = []
    for idx, game in enumerate(games):
        raw_type = game.get("type", "unknown")
        ctype = canonical_type(raw_type)
        score = richness_score(game)
        if is_generic(game):
            score *= 0.3   # 70% penalty
        annotated.append({
            "idx": idx,
            "game": game,
            "ctype": ctype,
            "score": score,
            "protected": ctype in PROTECTED_TYPES,
        })

    # ── Step 2: Group by canonical type ────────────────────────────────────
    groups = {}
    for entry in annotated:
        ctype = entry["ctype"]
        if ctype not in groups:
            groups[ctype] = []
        groups[ctype].append(entry)

    # ── Step 3: Within each group, rank by score (descending) ─────────────
    for ctype in groups:
        groups[ctype].sort(key=lambda e: -e["score"])

    # ── Step 4: Select games to keep ──────────────────────────────────────
    kept_indices = set()

    # 4a: Always keep all protected types (escaperoom, cronica)
    for ctype in PROTECTED_TYPES:
        if ctype in groups:
            for entry in groups[ctype]:
                kept_indices.add(entry["idx"])

    # 4b: For each non-protected type, keep top N up to cap
    for ctype, entries in groups.items():
        if ctype in PROTECTED_TYPES:
            continue
        cap = TYPE_CAPS.get(ctype, DEFAULT_CAP)
        for i, entry in enumerate(entries):
            if i < cap:
                kept_indices.add(entry["idx"])

    # ── Step 5: Adjust total to 30-35 range ───────────────────────────────
    total_kept = len(kept_indices)

    if total_kept > TARGET_MAX:
        # Remove weakest among kept (never touch protected)
        scored_kept = []
        for entry in annotated:
            if entry["idx"] in kept_indices and not entry["protected"]:
                scored_kept.append(entry)
        scored_kept.sort(key=lambda e: e["score"])  # ascending = weakest first

        # Track type counts so we never remove the last of a type
        type_counts = {}
        for entry in annotated:
            if entry["idx"] in kept_indices:
                ctype = entry["ctype"]
                type_counts[ctype] = type_counts.get(ctype, 0) + 1

        for entry in scored_kept:
            if total_kept <= TARGET_IDEAL:
                break
            ctype = entry["ctype"]
            if type_counts.get(ctype, 0) <= 1:
                continue   # preserve type diversity
            kept_indices.discard(entry["idx"])
            type_counts[ctype] -= 1
            total_kept -= 1

    elif total_kept < TARGET_MIN:
        # Add back richest removed games
        removed_entries = [
            e for e in annotated if e["idx"] not in kept_indices
        ]
        removed_entries.sort(key=lambda e: -e["score"])  # descending = richest first

        for entry in removed_entries:
            if total_kept >= TARGET_IDEAL:
                break
            kept_indices.add(entry["idx"])
            total_kept += 1

    # ── Step 6: Build final lists preserving original order ───────────────
    kept_games = []
    removed_games = []
    for idx, game in enumerate(games):
        if idx in kept_indices:
            kept_games.append(game)
        else:
            removed_games.append(game)

    after_count = len(kept_games)

    # ── Step 7: Save ──────────────────────────────────────────────────────
    data["games"] = kept_games
    save_dest(n, data)
    save_trimmed(n, removed_games)

    return before_count, after_count, removed_games


def type_summary(games):
    """Return a canonical-type -> count dict for a games list."""
    counts = {}
    for g in games:
        ctype = canonical_type(g.get("type", "unknown"))
        counts[ctype] = counts.get(ctype, 0) + 1
    return counts


# ═══════════════════════════════════════════════════════════════════════════
# Main
# ═══════════════════════════════════════════════════════════════════════════

def main():
    print("=" * 70)
    print("A1-Advanced / A2 / B1 Destination Trimming")
    print(f"Targets: {', '.join(f'dest{n}' for n in DESTINATIONS)}")
    print("=" * 70)
    print()

    total_before = 0
    total_after = 0
    total_removed = 0

    for n in DESTINATIONS:
        print(f"Processing dest{n}...")

        # Load for pre-analysis
        data = load_dest(n)
        games = data.get("games", [])
        before_types = type_summary(games)

        before, after, removed = trim_destination(n)

        # Reload for post-analysis
        data_after = load_dest(n)
        games_after = data_after.get("games", [])
        after_types = type_summary(games_after)

        total_before += before
        total_after += after
        total_removed += len(removed)

        if before != after:
            print(f"  Before: {before} games")
            print(f"  After:  {after} games  (removed {before - after})")
            print(f"  Type breakdown (canonical, before -> after):")
            all_types = sorted(set(list(before_types.keys()) + list(after_types.keys())))
            for t in all_types:
                b = before_types.get(t, 0)
                a = after_types.get(t, 0)
                marker = " *" if b != a else ""
                print(f"    {t:15s}: {b:2d} -> {a:2d}{marker}")
        print()

    print("=" * 70)
    print(f"TOTAL: {total_before} games -> {total_after} games (removed {total_removed})")
    print("=" * 70)
    print()
    print(f"Trimmed game backups saved to: {TRIMMED_DIR}/")


if __name__ == "__main__":
    main()
