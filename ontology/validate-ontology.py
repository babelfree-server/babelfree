#!/usr/bin/env python3
"""
Ontology validation — verifies all cross-references, ID formats, enums, and CEFR levels.
Run: python3 ontology/validate-ontology.py
"""

import json
import os
import re
import sys
from collections import Counter

BASE = os.path.dirname(os.path.abspath(__file__))

VALID_GAME_TYPES = {
    "narrative", "pair", "fill", "conjugation", "listening", "category",
    "builder", "translation", "conversation", "dictation", "story",
    "cancion", "escaperoom", "crossword", "bingo", "scrabble", "madlibs", "kloo", "boggle", "spaceman", "bananagrams", "consequences", "madgab", "conjuro", "explorador", "senda", "guardian", "clon", "eco_restaurar", "flashnote", "cultura"
}
VALID_INPUT_MODES = {"choice", "typing", "voice", "drag", "listen", "self_correction"}
VALID_DIFFICULTIES = {"guided", "semi_guided", "open"}
VALID_CEFR = {"A1", "A2", "B1", "B2", "C1", "C2"}
VALID_PERSONS = {"yo", "tú", "él/ella/usted", "nosotros", "ellos/ellas/ustedes"}
CEFR_RANK = {"A1": 1, "A2": 2, "B1": 3, "B2": 4, "C1": 5, "C2": 6}

errors = []
warnings = []


def load_json(filename):
    path = os.path.join(BASE, filename)
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def error(msg):
    errors.append(msg)
    print(f"  ERROR: {msg}")


def warn(msg):
    warnings.append(msg)
    print(f"  WARN:  {msg}")


def main():
    print("Loading ontology files...")
    targets_raw = load_json("linguistic-targets.json")
    objectives_raw = load_json("learning-objectives.json")
    activities_raw = load_json("activities.json")
    world = load_json("story-world.json")

    # Build ID sets
    target_ids = {t["id"] for t in targets_raw if "id" in t}
    objective_ids = {o["id"] for o in objectives_raw if "id" in o}
    activities = [a for a in activities_raw if "id" in a]
    activity_ids = {a["id"] for a in activities}

    story_nodes = [sn for sn in world.get("storyNodes", []) if "id" in sn]
    sn_by_dest = {}
    for sn in story_nodes:
        if "destination" in sn:
            sn_by_dest[sn["destination"]] = sn
    valid_destinations = set(sn_by_dest.keys())

    target_cefr = {}
    for t in targets_raw:
        if "id" in t and "cefr" in t:
            target_cefr[t["id"]] = t["cefr"]

    sn_cefr = {}
    for sn in story_nodes:
        sn_cefr[sn["destination"]] = sn.get("cefr", "A1")

    print(f"\nLoaded: {len(target_ids)} targets, {len(objective_ids)} objectives, "
          f"{len(activities)} activities, {len(valid_destinations)} story nodes\n")

    # --- Check activities ---
    print("=== Validating activities ===")

    # Duplicate IDs
    id_counts = Counter(a["id"] for a in activities)
    for aid, count in id_counts.items():
        if count > 1:
            error(f"Duplicate activity ID: {aid} (appears {count} times)")

    for a in activities:
        aid = a["id"]

        # ID format: act_{dest}_{gameType}_{slug}
        # gameType is a known word, slug is everything after
        id_ok = False
        m = re.match(r"^act_(dest\d+)_(.+)$", aid)
        if not m:
            error(f"{aid}: ID does not match act_{{dest}}_{{gameType}}_{{slug}}")
        else:
            id_dest = m.group(1)
            remainder = m.group(2)  # e.g. "fill_ser_yo" or "self_correction_no_tengas"
            # Try to match known gameType at start of remainder
            id_game = None
            gt_sorted = sorted(VALID_GAME_TYPES, key=len, reverse=True)
            # Also check self_correction as it can appear in IDs
            all_known = list(gt_sorted) + ["self_correction"]
            for candidate in all_known:
                if remainder == candidate or remainder.startswith(candidate + "_"):
                    id_game = candidate
                    break
            if a.get("destination") and id_dest != a["destination"]:
                error(f"{aid}: dest in ID ({id_dest}) != destination field ({a['destination']})")
            if id_game and a.get("gameType") and id_game != a["gameType"]:
                # self_correction in ID is an inputMode, not gameType — skip gameType check for it
                if id_game != "self_correction":
                    error(f"{aid}: gameType in ID ({id_game}) != gameType field ({a['gameType']})")
            id_ok = True

        # practices[] reference valid LTs
        for lt_id in a.get("practices", []):
            if lt_id not in target_ids:
                error(f"{aid}: practices references unknown LT: {lt_id}")

        # objective references valid LO
        obj = a.get("objective")
        if obj and obj not in objective_ids:
            error(f"{aid}: objective references unknown LO: {obj}")

        # destination references valid StoryNode
        dest = a.get("destination")
        if dest and dest not in valid_destinations:
            error(f"{aid}: destination '{dest}' has no matching StoryNode")

        # gameType is valid
        gt = a.get("gameType")
        if gt and gt not in VALID_GAME_TYPES:
            error(f"{aid}: invalid gameType: {gt}")

        # inputMode is valid
        im = a.get("inputMode")
        if im and im not in VALID_INPUT_MODES:
            error(f"{aid}: invalid inputMode: {im}")

        # difficulty is valid
        diff = a.get("difficulty")
        if diff and diff not in VALID_DIFFICULTIES:
            error(f"{aid}: invalid difficulty: {diff}")

        # CEFR is valid
        cefr = a.get("cefr")
        if cefr and cefr not in VALID_CEFR:
            error(f"{aid}: invalid cefr: {cefr}")

        # targetPerson is valid
        tp = a.get("targetPerson")
        if tp:
            if tp not in VALID_PERSONS:
                if tp == "vosotros":
                    warn(f"{aid}: targetPerson is 'vosotros' — LatAm only project")
                else:
                    error(f"{aid}: invalid targetPerson: {tp}")

        # CEFR of activity >= CEFR of destination's StoryNode (spiral recycling OK)
        if cefr and dest and dest in sn_cefr:
            act_rank = CEFR_RANK.get(cefr, 0)
            dest_rank = CEFR_RANK.get(sn_cefr[dest], 0)
            if act_rank < dest_rank:
                warn(f"{aid}: activity CEFR ({cefr}) < destination CEFR ({sn_cefr[dest]}) — check spiral logic")

        # scaffoldable consistency (inv_scaffolding_global)
        scaffoldable = a.get("scaffoldable")
        scaffoldable_modes = {"choice", "typing"}
        non_scaffoldable_modes = {"listen", "drag", "voice", "self_correction"}
        if im in scaffoldable_modes and scaffoldable is not True:
            warn(f"{aid}: inputMode '{im}' should have scaffoldable: true")
        if im in non_scaffoldable_modes and scaffoldable is True:
            error(f"{aid}: inputMode '{im}' must NOT have scaffoldable: true (mode is inherent to mechanic)")

    # --- Check learning objectives ---
    print("\n=== Validating learning objectives ===")
    for o in objectives_raw:
        if "id" not in o:
            continue
        oid = o["id"]
        for lt_id in o.get("targets", []):
            if lt_id not in target_ids:
                error(f"{oid}: targets references unknown LT: {lt_id}")

        req = o.get("requires")
        if req and isinstance(req, dict):
            for lt_id in req.get("linguisticTargets", []):
                if lt_id not in target_ids:
                    error(f"{oid}: requires.linguisticTargets references unknown LT: {lt_id}")

    # --- Summary ---
    print("\n=== Summary ===")

    # Activities per destination
    dest_counts = Counter(a.get("destination") for a in activities)
    print("\nActivities per destination:")
    for dest in sorted(dest_counts, key=lambda d: int(d.replace("dest", "")) if d else 0):
        print(f"  {dest}: {dest_counts[dest]}")

    # Activities per game type
    gt_counts = Counter(a.get("gameType") for a in activities)
    print("\nActivities per game type:")
    for gt in sorted(gt_counts):
        print(f"  {gt}: {gt_counts[gt]}")

    # Activities per CEFR
    cefr_counts = Counter(a.get("cefr") for a in activities)
    print("\nActivities per CEFR level:")
    for c in sorted(cefr_counts, key=lambda x: CEFR_RANK.get(x, 99)):
        print(f"  {c}: {cefr_counts[c]}")

    # Activities per input mode
    im_counts = Counter(a.get("inputMode") for a in activities)
    print("\nActivities per input mode:")
    for im in sorted(im_counts):
        print(f"  {im}: {im_counts[im]}")

    # Activities per difficulty
    diff_counts = Counter(a.get("difficulty") for a in activities)
    print("\nActivities per difficulty:")
    for d in sorted(diff_counts):
        print(f"  {d}: {diff_counts[d]}")

    # Final
    print(f"\n{'='*40}")
    print(f"Total activities: {len(activities)}")
    print(f"Errors: {len(errors)}")
    print(f"Warnings: {len(warnings)}")

    if errors:
        print("\nVALIDATION FAILED")
        sys.exit(1)
    else:
        print("\nVALIDATION PASSED")
        sys.exit(0)


if __name__ == "__main__":
    main()
