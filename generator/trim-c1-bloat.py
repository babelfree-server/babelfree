#!/usr/bin/env python3
"""
trim-c1-bloat.py
================
Trims bloated C1 destinations (dest39-48) from ~50-60 games down to ~30-35.

Rules:
1. ALWAYS keep: arrival (separate from games[]), escaperoom (1 per dest), cronica (always last)
2. Keep at least one of each game type present (diversity)
3. Among duplicates of same type, prefer the one with richer content
4. Prefer games connected to the destination's story theme
5. Remove overly generic games
6. Target mix: ~4-5 fib, ~3-4 conversation, ~3-4 listening, ~2-3 builder,
   ~2-3 pair, ~2 conjugation, ~2 sorting, ~1-2 translation, ~1-2 dictation,
   ~1-2 narrative, 1 cancion (if present), 1 story (if present),
   1 escaperoom, 1 cronica

Outputs:
- content/trimmed/dest{N}_trimmed.json  (removed games, for reference)
- Overwrites content/dest{N}.json with trimmed version
- Prints before/after counts
"""

import json
import os
import sys
import re
from copy import deepcopy

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONTENT_DIR = os.path.join(BASE_DIR, "content")
TRIMMED_DIR = os.path.join(CONTENT_DIR, "trimmed")

# Target destinations
DESTINATIONS = list(range(39, 49))  # dest39-48

# Target maximums per game type
# These are soft caps -- the algorithm keeps at least 1 of every type present,
# then fills to the cap, preferring richer games.
TYPE_CAPS = {
    "fib":          5,
    "conversation": 4,
    "listening":    4,
    "builder":      3,
    "pair":         3,
    "conjugation":  2,
    "sorting":      2,
    "translation":  2,
    "dictation":    2,
    "narrative":    2,
    "cancion":      1,
    "story":        1,
    # Always keep exactly 1
    "escaperoom":   1,
    "cronica":      1,
}

# Keywords that signal story-connected content (score bonus)
STORY_KEYWORDS = [
    "yaguará", "yaguara", "jaguar",
    "candelaria", "cande",
    "don próspero", "próspero", "prospero",
    "doña asunción", "asunción", "asuncion",
    "antiguos", "antigua",
    "kogi", "mamo",
    "sierra", "nevada",
    "teyuna",
    "mundo de arriba",
    "pagamento",
    "espíritu", "espiritu",
    "mapa de las voces",
    "constelación", "constelacion",
    "héroe", "heroe", "viaje",
    "montaña",
    "selva", "bosque", "río",
    "ceremonia", "ritual",
]


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
            "note": "Games removed by trim-c1-bloat.py",
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


def count_questions(game):
    """Count the number of questions/items/turns/lines in a game."""
    count = 0
    if "questions" in game:
        count += len(game["questions"])
    if "items" in game:
        count += len(game["items"])
    if "turns" in game:
        count += len(game["turns"])
    if "lines" in game:
        count += len(game["lines"])
    if "pairs" in game:
        count += len(game["pairs"])
    if "puzzles" in game:
        count += len(game["puzzles"])
    # For narrative/story, count by text length
    if game.get("type") in ("narrative", "story"):
        text = game.get("text", "")
        if isinstance(text, str):
            count += len(text) // 100  # rough paragraph count
    return count


def story_keyword_score(game):
    """Score a game by how many story keywords it contains."""
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
    q_count = count_questions(game)
    text_len = len(game_text(game))
    story_score = story_keyword_score(game)

    # Weighted composite
    score = (
        q_count * 10 +          # more questions = richer
        text_len * 0.01 +       # longer content = richer
        story_score * 15         # story connection = big bonus
    )
    return score


def is_generic(game):
    """
    Detect overly generic games (simple "Completa la frase" with minimal content).
    Returns True if the game seems generic/low-value.
    """
    gtype = game.get("type", "")
    instruction = game.get("instruction", "").lower()

    # Simple fill-in with very few questions and no story connection
    if gtype == "fib":
        q_count = len(game.get("questions", []))
        if q_count <= 2 and story_keyword_score(game) == 0:
            return True

    # Builder with very few words and no story
    if gtype == "builder":
        words = game.get("words", [])
        if len(words) <= 5 and story_keyword_score(game) == 0:
            return True

    return False


def trim_destination(n):
    """
    Trim a single destination. Returns (before_count, after_count, removed_games).
    """
    data = load_dest(n)
    games = data.get("games", [])
    before_count = len(games)

    if before_count <= 35:
        print(f"  dest{n}: {before_count} games -- already within target, skipping")
        return before_count, before_count, []

    # Group games by type, preserving original index for order restoration
    groups = {}
    for idx, game in enumerate(games):
        gtype = game.get("type", "unknown")
        if gtype not in groups:
            groups[gtype] = []
        groups[gtype].append((idx, game))

    # Determine which games to keep
    kept_indices = set()

    # Phase 1: Always keep escaperoom and cronica
    for always_keep in ("escaperoom", "cronica"):
        if always_keep in groups:
            for idx, game in groups[always_keep]:
                kept_indices.add(idx)

    # Phase 2: For each type, score and select top N
    for gtype, entries in groups.items():
        if gtype in ("escaperoom", "cronica"):
            continue  # already handled

        cap = TYPE_CAPS.get(gtype, 1)  # default cap of 1 for unknown types

        # Score each game
        scored = []
        for idx, game in entries:
            score = richness_score(game)
            # Penalize generic games
            if is_generic(game):
                score *= 0.3
            scored.append((score, idx, game))

        # Sort by score descending (richest first)
        scored.sort(key=lambda x: -x[0])

        # Keep top `cap` games
        for i, (score, idx, game) in enumerate(scored):
            if i < cap:
                kept_indices.add(idx)

    # Phase 3: Check total -- if still above 35, we need to trim more
    # If below 30, we can add back some removed games (prefer story-connected ones)
    total_kept = len(kept_indices)

    if total_kept > 35:
        # Need to remove more -- remove lowest-scored among kept (except escaperoom/cronica)
        scored_kept = []
        for idx in kept_indices:
            game = games[idx]
            gtype = game.get("type", "")
            if gtype in ("escaperoom", "cronica"):
                continue
            scored_kept.append((richness_score(game), idx, gtype))

        scored_kept.sort(key=lambda x: x[0])  # ascending = weakest first

        # Count how many of each type we're keeping
        type_counts = {}
        for idx in kept_indices:
            gtype = games[idx].get("type", "")
            type_counts[gtype] = type_counts.get(gtype, 0) + 1

        # Remove weakest until we hit 33 (target middle of 30-35)
        target = 33
        for score, idx, gtype in scored_kept:
            if total_kept <= target:
                break
            # Don't remove the last instance of any type
            if type_counts.get(gtype, 0) <= 1:
                continue
            kept_indices.discard(idx)
            type_counts[gtype] -= 1
            total_kept -= 1

    elif total_kept < 30:
        # Add back some removed games (prefer richer ones)
        removed_scored = []
        for idx, game in enumerate(games):
            if idx not in kept_indices:
                score = richness_score(game)
                removed_scored.append((score, idx))

        removed_scored.sort(key=lambda x: -x[0])  # descending = richest first

        target = 32
        for score, idx in removed_scored:
            if total_kept >= target:
                break
            kept_indices.add(idx)
            total_kept += 1

    # Build final games list preserving original order
    kept_games = []
    removed_games = []
    for idx, game in enumerate(games):
        if idx in kept_indices:
            kept_games.append(game)
        else:
            removed_games.append(game)

    after_count = len(kept_games)

    # Update data and save
    data["games"] = kept_games
    save_dest(n, data)
    save_trimmed(n, removed_games)

    return before_count, after_count, removed_games


def type_summary(games):
    """Return a type -> count dict for a games list."""
    counts = {}
    for g in games:
        t = g.get("type", "unknown")
        counts[t] = counts.get(t, 0) + 1
    return counts


def main():
    print("=" * 70)
    print("C1 Destination Trimming (dest39-48)")
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
            print(f"  Type breakdown (before -> after):")
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
