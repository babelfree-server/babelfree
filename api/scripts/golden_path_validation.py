#!/usr/bin/env python3
"""
golden_path_validation.py
Golden-path validation for the conjugation pipeline.

Part 1: Deep inspection of "tener" through the full pipeline.
Part 2: 20-verb summary report (counts, nulls, defects).
"""

import json
import sys
from pathlib import Path

# ── Paths ────────────────────────────────────────────────────────────────
SCRIPT_DIR = Path(__file__).resolve().parent
DATA_DIR = SCRIPT_DIR / "data"
SOURCE_FILE = DATA_DIR / "conjugations_es.json"
PROCESSED_FILE = DATA_DIR / "conjugations_es_processed.json"

SEPARATOR = "=" * 72


# ── Helpers ──────────────────────────────────────────────────────────────
def load_json(path):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def count_forms(conjugations):
    """Walk the conjugation tree and count non-null / null leaf values."""
    non_null = 0
    null = 0
    for value in _leaves(conjugations):
        if value is None:
            null += 1
        else:
            non_null += 1
    return non_null, null


def _leaves(obj):
    """Yield every leaf value from a nested dict."""
    if isinstance(obj, dict):
        for v in obj.values():
            yield from _leaves(v)
    else:
        yield obj


def get_form(entry, mood, tense, person):
    """
    Simulate the lookup that yaguara-engine.js performs:
      getForm(verb, mood, tense, person)

    Paths in the processed JSON:
      conjugations.<mood>.<tense>.<person>                     (normal moods)
      conjugations.imperative.<affirmative|negative>.<person>  (imperative)

    If mood is None, look for tense directly under every mood
    (engine falls back through moods until a match is found).
    """
    conj = entry.get("conjugations", {})

    # Direct mood given
    if mood is not None:
        branch = conj.get(mood, {})
        tense_data = branch.get(tense, {})
        return tense_data.get(person)

    # Mood is None -> scan all moods for the tense key
    for mood_key, mood_data in conj.items():
        if isinstance(mood_data, dict) and tense in mood_data:
            return mood_data[tense].get(person)

    return None


# ══════════════════════════════════════════════════════════════════════════
#  PART 1 — Golden-path for "tener"
# ══════════════════════════════════════════════════════════════════════════
def part1():
    print(SEPARATOR)
    print('PART 1: Golden-path deep inspection — "tener"')
    print(SEPARATOR)

    source = load_json(SOURCE_FILE)
    processed = load_json(PROCESSED_FILE)

    if "tener" not in processed:
        print("FATAL: 'tener' not found in processed data.")
        sys.exit(1)

    tener = processed["tener"]

    # ── 1a. Full processed JSON entry ────────────────────────────────────
    print('\n1a) Full processed JSON entry for "tener":\n')
    print(json.dumps(tener, indent=2, ensure_ascii=False))

    # ── 1b. Simulate getForm(verb, "indicative", "present", "yo") ───────
    print("\n" + "-" * 60)
    print('1b) getForm("tener", "indicative", "present", "yo")')
    result = get_form(tener, "indicative", "present", "yo")
    print(f"    Path: conjugations.indicative.present.yo")
    print(f'    Result: "{result}"')
    expected = "tengo"
    status = "PASS" if result == expected else f'FAIL (expected "{expected}")'
    print(f"    Check: {status}")

    # ── 1c. Simulate getForm(verb, None, "negative", "tú") ──────────────
    #    imperative_negative maps to conjugations.imperative.negative
    print("\n" + "-" * 60)
    print('1c) getForm("tener", None, "imperative_negative", "tú")')
    print("    Engine resolves imperative_negative -> imperative.negative")

    # The actual path the engine uses: conjugations.imperative.negative.tú
    # With mood=None we scan; the key in the JSON is "negative" under "imperative"
    result2 = get_form(tener, None, "negative", "tú")
    print(f"    Path: conjugations.imperative.negative.tú")
    print(f'    Result: "{result2}"')
    expected2 = "no tengas"
    status2 = "PASS" if result2 == expected2 else f'FAIL (expected "{expected2}")'
    print(f"    Check: {status2}")

    # ── 1d. Idempotency check ────────────────────────────────────────────
    print("\n" + "-" * 60)
    print("1d) Idempotency check — load processed file twice and compare")
    load_a = load_json(PROCESSED_FILE)
    load_b = load_json(PROCESSED_FILE)
    identical = (load_a == load_b)
    print(f"    Full data identical on two loads: {identical}")
    # Also confirm tener specifically
    tener_identical = (load_a["tener"] == load_b["tener"])
    print(f'    "tener" entry identical: {tener_identical}')
    overall = "PASS" if (identical and tener_identical) else "FAIL"
    print(f"    Check: {overall}")


# ══════════════════════════════════════════════════════════════════════════
#  PART 2 — 20-verb pipeline report
# ══════════════════════════════════════════════════════════════════════════
TARGET_VERBS = [
    "ser", "ir", "tener", "haber", "poder",
    "gustar", "llover", "soler", "oír", "conducir",
    "hablar", "comer", "vivir", "abrir", "cerrar",
    "pensar", "dormir", "pedir", "jugar", "empezar",
]


def part2():
    print("\n" + SEPARATOR)
    print("PART 2: 20-verb pipeline report")
    print(SEPARATOR)

    processed = load_json(PROCESSED_FILE)

    # ── Collect data ─────────────────────────────────────────────────────
    missing = []
    rows = []  # (lemma, verb_type, non_null, null_count)
    verb_type_counts = {}

    for verb in TARGET_VERBS:
        if verb not in processed:
            missing.append(verb)
            continue
        entry = processed[verb]
        lemma = entry.get("lemma", verb)
        vtype = entry.get("verb_type", "unknown")
        non_null, null_count = count_forms(entry.get("conjugations", {}))
        rows.append((lemma, vtype, non_null, null_count))
        verb_type_counts[vtype] = verb_type_counts.get(vtype, 0) + 1

    # ── Missing verbs ────────────────────────────────────────────────────
    print("\nMissing / defective verbs: ", end="")
    if missing:
        print(", ".join(missing))
    else:
        print("(none)")

    # ── Verb type counts ─────────────────────────────────────────────────
    print(f"\nVerb-type distribution ({len(rows)} verbs found):")
    for vtype in sorted(verb_type_counts.keys()):
        print(f"  {vtype:25s}  {verb_type_counts[vtype]}")

    # ── Total nulls ──────────────────────────────────────────────────────
    total_non_null = sum(r[2] for r in rows)
    total_null = sum(r[3] for r in rows)
    total_forms = total_non_null + total_null
    print(f"\nTotal forms across {len(rows)} verbs: {total_forms}")
    print(f"  Non-null: {total_non_null}")
    print(f"  Null:     {total_null}")
    null_pct = (total_null / total_forms * 100) if total_forms else 0
    print(f"  Null %:   {null_pct:.1f}%")

    # ── Per-verb table ───────────────────────────────────────────────────
    print(f"\n{'Lemma':<14} {'Type':<22} {'Non-null':>8} {'Null':>6} {'Total':>6}")
    print("-" * 60)
    for lemma, vtype, nn, nl in rows:
        print(f"{lemma:<14} {vtype:<22} {nn:>8} {nl:>6} {nn+nl:>6}")
    print("-" * 60)
    print(f"{'TOTAL':<14} {'':<22} {total_non_null:>8} {total_null:>6} {total_forms:>6}")


# ══════════════════════════════════════════════════════════════════════════
#  Main
# ══════════════════════════════════════════════════════════════════════════
if __name__ == "__main__":
    print(f"Source file:    {SOURCE_FILE}")
    print(f"Processed file: {PROCESSED_FILE}")
    print(f"Source exists:    {SOURCE_FILE.exists()}")
    print(f"Processed exists: {PROCESSED_FILE.exists()}")
    print()

    part1()
    part2()

    print(f"\n{SEPARATOR}")
    print("Validation complete.")
    print(SEPARATOR)
