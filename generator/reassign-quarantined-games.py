#!/usr/bin/env python3
"""
reassign-quarantined-games.py
==============================
Reads CEFR-quarantined games from content/quarantine/ and reassigns them
to the earliest appropriate destination where the grammar has already been
introduced.

Grammar introduction points:
  dest1-12  (A1):  Present tense only
  dest13:          Reflexive verbs
  dest14:          Pretérito indefinido (preterite)
  dest15:          Modal verbs (querer, poder, necesitar + inf)
  dest16:          Weather + ir a + infinitive (near future)
  dest17:          Imperfecto
  dest18:          Review/consolidation of A2
  dest19:          Preterite/imperfect narrative contrast
  dest20:          Indirect speech
  dest21:          Present subjunctive (in noun clauses)
  dest22-23:       Relative clauses, passive se
  dest24-28:       B1 Advanced
  dest29-33:       B2 Basic
  dest34-38:       B2 Advanced
"""

import json
import os
import re
import sys
from collections import defaultdict

BASE = "/home/babelfree.com/public_html/content"
QUARANTINE = os.path.join(BASE, "quarantine")
REASSIGNED = os.path.join(QUARANTINE, "reassigned")

# Max games per destination before we skip it (bloat guard)
MAX_GAMES = 48

# ── Verb form detection patterns ─────────────────────────────────────

# Preterite markers (irregular + regular -é, -ó, -iste, -imos, -ieron, -aron endings)
PRETERITE_FORMS = re.compile(
    r'\b('
    # Common irregular preterites
    r'fu[ie]|fui(?:ste|mos|eron)?|'
    r'hic[ei](?:ste|mos|eron)?|hizo|'
    r'tuv[ei](?:ste|mos|eron)?|tuvo|'
    r'estuv[ei](?:ste|mos|eron)?|estuvo|'
    r'pud[ei](?:ste|mos|eron)?|pudo|'
    r'sup[ei](?:ste|mos|eron)?|supo|'
    r'pus[ei](?:ste|mos|eron)?|puso|'
    r'vin[ei](?:ste|mos|eron)?|vino|'
    r'dij[ei](?:ste|mos|eron)?|dijo|dijeron|'
    r'quis[ei](?:ste|mos|eron)?|quiso|'
    r'traj[ei](?:ste|mos|eron)?|trajo|trajeron|'
    r'vi(?:ste|mos|eron)?|vio|'
    # Regular preterite endings (-ar: -é, -aste, -ó, -amos, -aron)
    # (-er/-ir: -í, -iste, -ió, -imos, -ieron)
    r'[a-záéíóú]+é|'          # hablé, caminé, comí, bebí, dormí
    r'[a-záéíóú]+aste|'       # hablaste
    r'[a-záéíóú]+iste|'       # comiste
    r'[a-záéíóú]+ó|'          # habló, comió
    r'[a-záéíóú]+ió|'         # comió, vivió
    r'[a-záéíóú]+aron|'       # hablaron
    r'[a-záéíóú]+ieron'       # comieron
    r')\b',
    re.IGNORECASE | re.UNICODE
)

# More targeted preterite patterns (to avoid false positives from -ó words like "avión")
PRETERITE_ANSWERS = re.compile(
    r'\b('
    r'hablé|hablaste|habló|hablamos|hablaron|'
    r'comí|comiste|comió|comimos|comieron|'
    r'bebí|bebiste|bebió|bebimos|bebieron|'
    r'caminé|caminaste|caminó|caminamos|caminaron|'
    r'dormí|dormiste|durmió|dormimos|durmieron|'
    r'fui|fuiste|fue|fuimos|fueron|'
    r'hice|hiciste|hizo|hicimos|hicieron|'
    r'tuve|tuviste|tuvo|tuvimos|tuvieron|'
    r'estuve|estuviste|estuvo|estuvimos|estuvieron|'
    r'pude|pudiste|pudo|pudimos|pudieron|'
    r'supe|supiste|supo|supimos|supieron|'
    r'puse|pusiste|puso|pusimos|pusieron|'
    r'vine|viniste|vino|vinimos|vinieron|'
    r'dije|dijiste|dijo|dijimos|dijeron|'
    r'quise|quisiste|quiso|quisimos|quisieron|'
    r'traje|trajiste|trajo|trajimos|trajeron|'
    r'vi|viste|vio|vimos|vieron|'
    r'viajé|viajaste|viajó|viajamos|viajaron|'
    r'llegué|llegaste|llegó|llegamos|llegaron|'
    r'tomé|tomaste|tomó|tomamos|tomaron|'
    r'nadé|nadaste|nadó|nadamos|nadaron|'
    r'bailé|bailaste|bailó|bailamos|bailaron|'
    r'compré|compraste|compró|compramos|compraron|'
    r'estudié|estudiaste|estudió|estudiamos|estudiaron|'
    r'subí|subiste|subió|subimos|subieron|'
    r'bajé|bajaste|bajó|bajamos|bajaron|'
    r'voló|volamos|volaron|'
    r'usé|usaste|usó|usamos|usaron|'
    r'visité|visitaste|visitó|visitamos|visitaron|'
    r'cenó|cenamos|cenaron|cené|'
    r'volvimos|volvieron|volvió|volví|'
    r'tardó|tardamos|tardaron|'
    r'duró|duramos|duraron|'
    r'gustó|gustaron|'
    r'despertó|llovió|apareció|encontró|'
    r'dejó|cantó|nadó|'
    r'se despertó|se sentó'
    r')\b',
    re.IGNORECASE | re.UNICODE
)

# Subjunctive markers
SUBJUNCTIVE_FORMS = re.compile(
    r'\b('
    r'hable|hables|hablemos|hablen|'
    r'coma|comas|comamos|coman|'
    r'viva|vivas|vivamos|vivan|'
    r'sea|seas|seamos|sean|'
    r'tenga|tengas|tengamos|tengan|'
    r'vaya|vayas|vayamos|vayan|'
    r'haga|hagas|hagamos|hagan|'
    r'pueda|puedas|podamos|puedan|'
    r'sepa|sepas|sepamos|sepan|'
    r'quiera|quieras|queramos|quieran|'
    r'salga|salgas|salgamos|salgan|'
    r'diga|digas|digamos|digan|'
    r'venga|vengas|vengamos|vengan|'
    r'ponga|pongas|pongamos|pongan|'
    r'traiga|traigas|traigamos|traigan|'
    r'conozca|conozcas|conozcamos|conozcan|'
    r'pida|pidas|pidamos|pidan|'
    r'duerma|duermas|durmamos|duerman|'
    r'estés|esté|estemos|estén|'
    r'encuentre|encuentres|encontremos|encuentren|'
    r'proteja|protejas|protejamos|protejan|'
    r'construya|construyas|construyamos|construyan|'
    r'escuche|escuches|escuchemos|escuchen|'
    r'aprenda|aprendas|aprendamos|aprendan|'
    r'pare|pares|paremos|paren|'
    r'pierda|pierdas|perdamos|pierdan'
    r')\b',
    re.IGNORECASE | re.UNICODE
)

# Subjunctive trigger phrases
SUBJUNCTIVE_TRIGGERS = re.compile(
    r'(espero que|quiero que|ojalá que|ojalá|es necesario que|es importante que|'
    r'es mejor que|no creo que|dudo que|es bueno que|subjuntivo)',
    re.IGNORECASE | re.UNICODE
)

# Imperfect markers
IMPERFECT_FORMS = re.compile(
    r'\b('
    r'[a-záéíóú]+aba|[a-záéíóú]+abas|[a-záéíóú]+ábamos|[a-záéíóú]+aban|'
    r'[a-záéíóú]+ía|[a-záéíóú]+ías|[a-záéíóú]+íamos|[a-záéíóú]+ían|'
    r'era|eras|éramos|eran|'
    r'tenía|tenías|teníamos|tenían|'
    r'hacía|hacías|hacíamos|hacían|'
    r'había|'
    r'tomaba|tomabas|tomábamos|tomaban'
    r')\b',
    re.IGNORECASE | re.UNICODE
)

# "ir a + infinitive" (near future)
IR_A_INF = re.compile(
    r'\b(voy a|vas a|va a|vamos a|van a)\s+[a-záéíóú]+[aei]r\b',
    re.IGNORECASE | re.UNICODE
)

# Modal + infinitive
MODAL_INF = re.compile(
    r'\b(quiero|quieres|quiere|queremos|quieren|'
    r'puedo|puedes|puede|podemos|pueden|'
    r'necesito|necesitas|necesita|necesitamos|necesitan)\s+[a-záéíóú]+[aei]r\b',
    re.IGNORECASE | re.UNICODE
)

# Keyword signals in instruction/label text
PRETERITE_KEYWORDS = re.compile(
    r'(pret[eé]rito|pasado|ayer|la semana pasada|el año pasado|anoche|'
    r'el mes pasado|el fin de semana pasado)',
    re.IGNORECASE | re.UNICODE
)


def extract_all_text(game):
    """Extract all textual content from a game object for grammar analysis."""
    texts = []

    def walk(obj, depth=0):
        if depth > 10:
            return
        if isinstance(obj, str):
            texts.append(obj)
        elif isinstance(obj, list):
            for item in obj:
                walk(item, depth + 1)
        elif isinstance(obj, dict):
            for v in obj.values():
                walk(v, depth + 1)

    walk(game)
    return " ".join(texts)


def classify_grammar(game):
    """
    Classify a game's grammar requirements.
    Returns a set of grammar features found.
    """
    text = extract_all_text(game)
    features = set()

    # Check subjunctive first (highest priority)
    if SUBJUNCTIVE_FORMS.search(text) or SUBJUNCTIVE_TRIGGERS.search(text):
        features.add("subjunctive")

    # Check imperfect
    if IMPERFECT_FORMS.search(text):
        features.add("imperfect")

    # Check preterite (use answer-focused pattern to avoid false positives)
    if PRETERITE_ANSWERS.search(text) or PRETERITE_KEYWORDS.search(text):
        features.add("preterite")

    # Check ir a + infinitive
    if IR_A_INF.search(text):
        features.add("ir_a_inf")

    # Check modal + infinitive
    if MODAL_INF.search(text):
        features.add("modal_inf")

    # If nothing else found, it's present tense only
    if not features:
        features.add("present_only")

    return features


def earliest_destination(features):
    """
    Given a set of grammar features, return the earliest destination
    number where ALL features have been introduced.
    """
    # Map each feature to its introduction destination
    feature_dest = {
        "present_only": 1,
        "modal_inf": 15,
        "ir_a_inf": 16,
        "preterite": 14,
        "imperfect": 17,
        "subjunctive": 21,
    }

    min_dest = 1
    for f in features:
        d = feature_dest.get(f, 14)
        if d > min_dest:
            min_dest = d

    return min_dest


def find_target_dest(min_dest, dest_counts, max_games=MAX_GAMES):
    """
    Find the best destination starting from min_dest.
    Prefers same CEFR band, skips bloated destinations.
    Returns destination number or None.
    """
    # CEFR band ranges
    bands = [
        (14, 18),   # A2
        (19, 23),   # B1 Basic
        (24, 28),   # B1 Advanced
        (29, 33),   # B2 Basic
        (34, 38),   # B2 Advanced
    ]

    # For present-only games that belong in A1
    if min_dest <= 12:
        for d in range(1, 13):
            if dest_counts.get(d, 0) < max_games:
                return d
        # Overflow to A2
        min_dest = 14

    # Find which band min_dest falls into
    for band_start, band_end in bands:
        if min_dest <= band_end:
            # Try within this band first
            for d in range(max(min_dest, band_start), band_end + 1):
                if dest_counts.get(d, 0) < max_games:
                    return d
            # Try next bands
            continue

    # Fallback: try anything from min_dest to 38
    for d in range(min_dest, 39):
        if dest_counts.get(d, 0) < max_games:
            return d

    return None


def load_dest(dest_num):
    """Load a destination JSON file."""
    path = os.path.join(BASE, f"dest{dest_num}.json")
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def save_dest(dest_num, data):
    """Save a destination JSON file."""
    path = os.path.join(BASE, f"dest{dest_num}.json")
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write("\n")


def insert_before_tail(games, new_games):
    """
    Insert new_games into the games list before the trailing
    escaperoom and cronica entries.
    """
    # Find how many trailing special types there are
    tail_types = {"escaperoom", "cronica"}
    tail_start = len(games)
    while tail_start > 0 and games[tail_start - 1].get("type") in tail_types:
        tail_start -= 1

    # Insert before the tail
    return games[:tail_start] + new_games + games[tail_start:]


def main():
    # Create reassigned directory
    os.makedirs(REASSIGNED, exist_ok=True)

    quarantine_files = [
        "dest4_quarantined.json",
        "dest9_quarantined.json",
        "dest10_quarantined.json",
        "dest11_quarantined.json",
        "dest13_quarantined.json",
        "dest20_quarantined.json",
    ]

    # Load current game counts for all destinations
    dest_counts = {}
    for d in range(1, 59):
        try:
            data = load_dest(d)
            games = data.get("games", data if isinstance(data, list) else [])
            dest_counts[d] = len(games)
        except FileNotFoundError:
            dest_counts[d] = 0

    print("=" * 70)
    print("QUARANTINED GAME REASSIGNMENT")
    print("=" * 70)
    print()

    # Track assignments: {dest_num: [list of games]}
    assignments = defaultdict(list)
    total_reassigned = 0
    total_skipped = 0
    skipped_details = []

    for qfile in quarantine_files:
        qpath = os.path.join(QUARANTINE, qfile)
        if not os.path.exists(qpath):
            print(f"WARNING: {qfile} not found, skipping.")
            continue

        with open(qpath, "r", encoding="utf-8") as f:
            games = json.load(f)

        source = qfile.replace("_quarantined.json", "")
        print(f"--- {qfile} ({len(games)} games) ---")

        for i, game in enumerate(games):
            game_type = game.get("type", "?")
            game_label = game.get("label", game.get("title", game.get("badge", "?")))

            # Skip quarantined escape rooms -- destinations already have their own
            if game_type == "escaperoom":
                print(f"  [{i}] SKIP escaperoom (destinations already have escape rooms)")
                total_skipped += 1
                skipped_details.append(f"{source}[{i}]: escaperoom (duplicate)")
                continue

            features = classify_grammar(game)
            min_dest = earliest_destination(features)

            target = find_target_dest(min_dest, dest_counts)

            if target is None:
                print(f"  [{i}] SKIP {game_type} ({game_label}) -- no room (features: {features})")
                total_skipped += 1
                skipped_details.append(f"{source}[{i}]: {game_type} - no room")
                continue

            # Tag the game with its provenance
            game["_quarantine_source"] = source

            assignments[target].append(game)
            dest_counts[target] += 1
            total_reassigned += 1

            print(f"  [{i}] {game_type:20s} features={features!s:40s} -> dest{target} ({dest_counts[target]} games)")

        print()

    # Now write all assignments to destination files
    print("=" * 70)
    print("WRITING UPDATED DESTINATION FILES")
    print("=" * 70)

    dests_modified = []
    for dest_num in sorted(assignments.keys()):
        new_games = assignments[dest_num]
        data = load_dest(dest_num)

        if "games" in data:
            before = len(data["games"])
            data["games"] = insert_before_tail(data["games"], new_games)
            after = len(data["games"])
        else:
            # If it's a raw list (shouldn't happen for dest14+ but just in case)
            before = len(data)
            data = insert_before_tail(data, new_games)
            after = len(data)

        save_dest(dest_num, data)
        dests_modified.append((dest_num, before, after, len(new_games)))
        print(f"  dest{dest_num}.json: {before} -> {after} games (+{len(new_games)})")

    # Move quarantine files to reassigned/
    print()
    print("=" * 70)
    print("ARCHIVING QUARANTINE FILES")
    print("=" * 70)

    for qfile in quarantine_files:
        src = os.path.join(QUARANTINE, qfile)
        dst = os.path.join(REASSIGNED, qfile)
        if os.path.exists(src):
            os.rename(src, dst)
            print(f"  {qfile} -> reassigned/")

    # Summary
    print()
    print("=" * 70)
    print("SUMMARY")
    print("=" * 70)
    print(f"  Total games reassigned:  {total_reassigned}")
    print(f"  Total games skipped:     {total_skipped}")
    print(f"  Destinations modified:   {len(dests_modified)}")
    print()

    print("  Destination breakdown:")
    for dest_num, before, after, added in dests_modified:
        print(f"    dest{dest_num}: {before} -> {after} (+{added})")

    if skipped_details:
        print()
        print("  Skipped games:")
        for detail in skipped_details:
            print(f"    - {detail}")

    print()
    print("Done.")


if __name__ == "__main__":
    main()
