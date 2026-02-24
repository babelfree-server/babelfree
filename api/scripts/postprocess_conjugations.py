#!/usr/bin/env python3
"""
Post-processor for Spanish verb conjugations.

Pipeline:  generate-conjugations.php → translate → post_process_verb → output
           (Spanish keys)             (English keys + compound persons)

The post-processor does NOT generate morphology.
The translator does NOT invent forms — it only remaps keys.
"""

from copy import deepcopy
from datetime import datetime
import json
import sys
import os

# ═══════════════════════════════════════════════════════════════════════════
# CONSTANTS — agreed spec
# ═══════════════════════════════════════════════════════════════════════════

PERSONS_FULL = [
    "yo",
    "tú",
    "él/ella/usted",
    "nosotros",
    "vosotros",
    "ellos/ellas/ustedes"
]

IMPERATIVE_PERSONS = [
    "tú",
    "usted",
    "nosotros",
    "vosotros",
    "ustedes"
]

DISALLOWED_KEYS = {
    "vos",
    "voseo",
    "archaic"
}


class PostProcessError(Exception):
    pass


# ═══════════════════════════════════════════════════════════════════════════
# POST-PROCESSOR — canonical reference implementation
# ═══════════════════════════════════════════════════════════════════════════

def post_process_verb(verb_entry: dict) -> dict:
    """
    Deterministic post-processor for Spanish verb conjugations.
    Does NOT generate morphology.
    """

    verb = deepcopy(verb_entry)

    validate_required_sections(verb)
    normalize_structure(verb)
    remove_disallowed_variants(verb)
    add_negative_imperative(verb)
    validate_final_structure(verb)
    add_metadata(verb)

    return verb


# ---------------- Validation ---------------- #

def validate_required_sections(verb: dict):
    try:
        _ = verb["conjugations"]["subjunctive"]["present"]
        _ = verb["conjugations"]["indicative"]
    except KeyError as e:
        raise PostProcessError(
            f"Missing required section for negative imperative derivation: {e}"
        )


# ---------------- Normalization ---------------- #

def normalize_structure(verb: dict):
    for mood, mood_data in verb.get("conjugations", {}).items():
        if not isinstance(mood_data, dict):
            continue
        for tense, table in mood_data.items():
            if isinstance(table, dict):
                ensure_all_persons_exist(table)


def ensure_all_persons_exist(table: dict):
    for person in PERSONS_FULL:
        table.setdefault(person, None)


# ---------------- Cleanup ---------------- #

def remove_disallowed_variants(obj):
    if isinstance(obj, dict):
        for key in list(obj.keys()):
            if key in DISALLOWED_KEYS:
                del obj[key]
            else:
                remove_disallowed_variants(obj[key])
    elif isinstance(obj, list):
        for item in obj:
            remove_disallowed_variants(item)


# ---------------- Negative Imperative ---------------- #

def add_negative_imperative(verb: dict):
    subj_present = verb["conjugations"]["subjunctive"]["present"]

    negative = {}

    for person in IMPERATIVE_PERSONS:
        source_key = subj_key_for_person(person)
        form = subj_present.get(source_key)

        negative[person] = f"no {form}" if form else None

    verb.setdefault("conjugations", {}) \
        .setdefault("imperative", {})["negative"] = negative


def subj_key_for_person(person: str) -> str:
    if person == "usted":
        return "él/ella/usted"
    if person == "ustedes":
        return "ellos/ellas/ustedes"
    return person


# ---------------- Final Validation ---------------- #

def validate_final_structure(verb: dict):
    try:
        neg = verb["conjugations"]["imperative"]["negative"]
    except KeyError:
        raise PostProcessError("Negative imperative not generated")

    for person in IMPERATIVE_PERSONS:
        if person not in neg:
            raise PostProcessError(
                f"Missing negative imperative form for: {person}"
            )


# ---------------- Metadata ---------------- #

def add_metadata(verb: dict):
    verb["postprocess_meta"] = {
        "negative_imperative_derived": True,
        "derived_from": "subjunctive.present",
        "timestamp": datetime.utcnow().isoformat() + "Z"
    }


# ═══════════════════════════════════════════════════════════════════════════
# TRANSLATOR — converts generator output to the post-processor's input format
# No forms are invented. Only keys are remapped.
# ═══════════════════════════════════════════════════════════════════════════

# Source (generator) → target mood keys
MOOD_MAP = {
    "indicativo": "indicative",
    "subjuntivo": "subjunctive",
    "imperativo": "imperative",
}

# Source → target tense keys
TENSE_MAP = {
    "presente": "present",
    "pretérito indefinido": "preterite",
    "pretérito imperfecto": "imperfect",
    "futuro": "future",
    "condicional": "conditional",
    "afirmativo": "affirmative",
}

# Source → target person keys for indicative/subjunctive
PERSON_MAP_FULL = {
    "yo":        "yo",
    "tú":        "tú",
    "él":        "él/ella/usted",
    "nosotros":  "nosotros",
    "ellos":     "ellos/ellas/ustedes",
    # "ustedes" is dropped — same forms live under ellos/ellas/ustedes
    # "vos" is dropped — disallowed
}

# Source → target person keys for imperative
PERSON_MAP_IMPERATIVE = {
    "tú":        "tú",
    "él":        "usted",
    "nosotros":  "nosotros",
    "ustedes":   "ustedes",
    # "ellos" is dropped — redundant with ustedes in imperative
    # "vos" is dropped — disallowed
}


def translate_from_generator(lemma: str, gen_data: dict) -> dict:
    """
    Translate a single verb from the generator's format (Spanish keys,
    simple person labels) to the post-processor's input format (English keys,
    compound person labels).

    No forms are invented — only keys are remapped.
    """
    entry = {
        "lemma": lemma,
        "verb_type": derive_verb_type(lemma, gen_data.get("is_irregular", False)),
        "conjugations": {},
    }

    conj = entry["conjugations"]

    # ── Indicative ──────────────────────────────────────────────────────
    if "indicativo" in gen_data:
        conj["indicative"] = {}
        for es_tense, forms in gen_data["indicativo"].items():
            en_tense = TENSE_MAP.get(es_tense, es_tense)
            conj["indicative"][en_tense] = remap_persons(forms, PERSON_MAP_FULL)

    # ── Subjunctive ─────────────────────────────────────────────────────
    if "subjuntivo" in gen_data:
        conj["subjunctive"] = {}
        for es_tense, forms in gen_data["subjuntivo"].items():
            en_tense = TENSE_MAP.get(es_tense, es_tense)
            conj["subjunctive"][en_tense] = remap_persons(forms, PERSON_MAP_FULL)

    # ── Imperative (affirmative only — negative added by post-processor) ──
    if "imperativo" in gen_data and "afirmativo" in gen_data["imperativo"]:
        conj["imperative"] = {
            "affirmative": remap_persons(
                gen_data["imperativo"]["afirmativo"],
                PERSON_MAP_IMPERATIVE
            )
        }

    return entry


def remap_persons(forms: dict, person_map: dict) -> dict:
    """
    Remap person keys from generator format to spec format.
    Only keys present in person_map are carried over.
    No forms are invented.
    """
    result = {}
    for src_key, target_key in person_map.items():
        if src_key in forms:
            result[target_key] = forms[src_key]
    return result


def derive_verb_type(lemma: str, is_irregular: bool) -> str:
    """Derive verb_type from lemma ending and irregularity flag."""
    import unicodedata
    regularity = "irregular" if is_irregular else "regular"

    # Strip reflexive -se to find conjugation class
    base = lemma
    if base.endswith("se"):
        base = base[:-2]

    # Normalize accents for ending detection (oír → oir, reír → reir)
    base_stripped = unicodedata.normalize("NFD", base)
    base_stripped = "".join(c for c in base_stripped if unicodedata.category(c) != "Mn")

    if base_stripped.endswith("ar"):
        return f"{regularity} -ar"
    elif base_stripped.endswith("er"):
        return f"{regularity} -er"
    elif base_stripped.endswith("ir"):
        return f"{regularity} -ir"

    return f"{regularity} -unknown"


# ═══════════════════════════════════════════════════════════════════════════
# MAIN — full pipeline: read generator JSON → translate → post-process → write
# ═══════════════════════════════════════════════════════════════════════════

def main():
    import argparse

    parser = argparse.ArgumentParser(
        description="Post-process Spanish verb conjugations"
    )
    parser.add_argument(
        "--input", "-i",
        default=os.path.join(os.path.dirname(__file__), "data", "conjugations_es.json"),
        help="Path to generator output (default: data/conjugations_es.json)"
    )
    parser.add_argument(
        "--output", "-o",
        default=os.path.join(os.path.dirname(__file__), "data", "conjugations_es_processed.json"),
        help="Path to write processed output"
    )
    parser.add_argument(
        "--test",
        help="Comma-separated list of verbs to process (test mode)"
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Print stats without writing output"
    )
    args = parser.parse_args()

    # ── Load ────────────────────────────────────────────────────────────
    with open(args.input, "r", encoding="utf-8") as f:
        source = json.load(f)

    print(f"Loaded {len(source)} verbs from source.")

    # ── Filter if --test ────────────────────────────────────────────────
    if args.test:
        test_verbs = [v.strip() for v in args.test.split(",")]
        filtered = {}
        for v in test_verbs:
            if v in source:
                filtered[v] = source[v]
            else:
                print(f"WARNING: Verb '{v}' not found in source, skipping.", file=sys.stderr)
        source = filtered
        print(f"Test mode: processing {len(source)} verbs.")

    # ── Pipeline ────────────────────────────────────────────────────────
    output = {}
    stats = {
        "processed": 0,
        "negative_added": 0,
        "validation_passed": 0,
        "validation_failed": 0,
        "errors": [],
    }

    for lemma, gen_data in source.items():
        try:
            # Step 1: Translate from generator format
            translated = translate_from_generator(lemma, gen_data)

            # Step 2: Post-process (the canonical pipeline)
            processed = post_process_verb(translated)

            output[lemma] = processed
            stats["processed"] += 1
            stats["negative_added"] += 1
            stats["validation_passed"] += 1

        except PostProcessError as e:
            stats["errors"].append(f"{lemma}: {e}")
            stats["validation_failed"] += 1
            print(f"ERROR processing '{lemma}': {e}", file=sys.stderr)

        except Exception as e:
            stats["errors"].append(f"{lemma}: {e}")
            stats["validation_failed"] += 1
            print(f"ERROR processing '{lemma}': {e}", file=sys.stderr)

    # ── Output ──────────────────────────────────────────────────────────
    if not args.dry_run:
        with open(args.output, "w", encoding="utf-8") as f:
            json.dump(output, f, ensure_ascii=False, indent=4)
        print(f"Wrote {len(output)} verbs to {args.output}")
    else:
        print(f"[DRY RUN] Would write {len(output)} verbs.")

    # ── Report ──────────────────────────────────────────────────────────
    print()
    print("── Post-Processor Report ──")
    print(f"Verbs processed:       {stats['processed']}")
    print(f"Negative imp. added:   {stats['negative_added']}")
    print(f"Validation passed:     {stats['validation_passed']}")
    print(f"Validation failed:     {stats['validation_failed']}")

    if stats["errors"]:
        print("ERRORS:")
        for err in stats["errors"]:
            print(f"  - {err}")
    else:
        print("Errors:                (none)")

    print("──────────────────────────")
    safe = len(stats["errors"]) == 0 and stats["validation_failed"] == 0
    print(f"Safe to scale: {'YES' if safe else 'NO — fix errors first'}")

    return 0 if safe else 1


if __name__ == "__main__":
    sys.exit(main())
