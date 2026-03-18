#!/usr/bin/env python3
"""
build-destination-content.py
Generates 58 destination JSON files (content/dest1.json through content/dest58.json)
by extracting and unifying content from three sources:

1. Ecosystem JSONs (content/selva_a1.json etc.) — A1-A2 concrete games by block
2. Destination folder games.html (var games = [...]) — B1-C2 inline games
3. Ontology (story-world.json, activities.json) — story context + activity validation

Each dest{N}.json becomes the single source of playable content for that destination.
"""

import json
import os
import re
import sys
from pathlib import Path

# ============================================================
# PATHS
# ============================================================
BASE = Path('/home/babelfree.com/public_html')
CONTENT_DIR = BASE / 'content'
ONTOLOGY_DIR = BASE / 'ontology'
OUTPUT_DIR = CONTENT_DIR  # dest{N}.json files go into content/

# ============================================================
# DESTINATION STRUCTURE
# ============================================================

# CEFR level → world mapping
DEST_WORLDS = {}
for d in range(1, 19):   DEST_WORLDS[d] = 'mundoDeAbajo'
for d in range(19, 39):  DEST_WORLDS[d] = 'mundoDelMedio'
for d in range(39, 59):  DEST_WORLDS[d] = 'mundoDeArriba'

# CEFR level labels
DEST_CEFR = {}
for d in range(1, 7):    DEST_CEFR[d] = 'A1'
for d in range(7, 13):   DEST_CEFR[d] = 'A1'
for d in range(13, 17):  DEST_CEFR[d] = 'A2'
for d in range(17, 19):  DEST_CEFR[d] = 'A2'
for d in range(19, 24):  DEST_CEFR[d] = 'B1'
for d in range(24, 29):  DEST_CEFR[d] = 'B1'
for d in range(29, 34):  DEST_CEFR[d] = 'B2'
for d in range(34, 39):  DEST_CEFR[d] = 'B2'
for d in range(39, 44):  DEST_CEFR[d] = 'C1'
for d in range(44, 49):  DEST_CEFR[d] = 'C1'
for d in range(49, 54):  DEST_CEFR[d] = 'C2'
for d in range(54, 59):  DEST_CEFR[d] = 'C2'

# Block-to-destination mapping for ecosystem JSONs
A1_BLOCKS = [
    'Despertar', 'Nombrar', 'Describir', 'Contar',
    'Expresar', 'Vivir', 'Comer', 'Familia',
    'Mi casa', 'El tiempo', 'Conjugar', 'Identidad'
]
# A1 blocks 0-5 → dest1-6 (Basic), blocks 6-11 → dest7-12 (Advanced)
A1_BLOCK_TO_DEST = {block: i + 1 for i, block in enumerate(A1_BLOCKS)}

A2_BLOCKS = [
    'Rutinas', 'Historias', 'Comida',
    'Necesidades', 'Clima', 'Integración'
]
A2_BLOCK_TO_DEST = {block: i + 13 for i, block in enumerate(A2_BLOCKS)}

# Folder mapping for inline games
# A1 Basic uses module{N} naming
A1_BASIC_FOLDERS = {
    1: 'a1_basic/module1_family',
    2: 'a1_basic/module2_animals',
    3: 'a1_basic/module3_water',
    4: 'a1_basic/module4_home',
    5: 'a1_basic/module5_food',
    6: 'a1_basic/module6_friends',
}

# A1 Advanced through C2 Advanced use dest{N} naming
# We'll auto-discover these from the filesystem
def discover_dest_folders():
    """Find all dest{N}_* folders across all level directories."""
    folders = {}
    level_dirs = [
        'a1_advanced', 'a2_basic', 'a2_advanced',
        'b1_basic', 'b1_advanced', 'b2_basic', 'b2_advanced',
        'c1_basic', 'c1_advanced', 'c2_basic', 'c2_advanced'
    ]
    for level_dir in level_dirs:
        level_path = BASE / level_dir
        if not level_path.is_dir():
            continue
        for subfolder in sorted(level_path.iterdir()):
            if not subfolder.is_dir():
                continue
            # Extract dest number from folder name
            m = re.match(r'dest(\d+)_', subfolder.name)
            if m:
                dest_num = int(m.group(1))
                if dest_num not in folders:
                    folders[dest_num] = []
                folders[dest_num].append(subfolder)
    # Also add A1 Basic module folders
    for dest_num, rel_path in A1_BASIC_FOLDERS.items():
        folder = BASE / rel_path
        if folder.is_dir():
            if dest_num not in folders:
                folders[dest_num] = []
            folders[dest_num].append(folder)
    return folders


# ============================================================
# CONTENT EXTRACTION
# ============================================================

def load_json(path):
    """Load a JSON file."""
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def extract_games_from_html(html_path):
    """Extract 'var games = [...]' from a games.html file.
    Returns a list of game dicts, or empty list on failure."""
    try:
        with open(html_path, 'r', encoding='utf-8') as f:
            content = f.read()
    except FileNotFoundError:
        return []

    # Find the var games = [...] block
    m = re.search(r'var\s+games\s*=\s*\[', content)
    if not m:
        return []

    # Strategy 1: Use regex to find the array ending with ];
    # This is more robust than character-by-character bracket matching
    # because it handles Unicode escapes and edge cases better
    var_start = m.start()
    script_end = content.find('</script>', var_start)
    if script_end < 0:
        script_end = len(content)
    js_block = content[var_start:script_end]

    # Find the array using greedy regex
    arr_match = re.search(r'var\s+games\s*=\s*(\[.*\]);', js_block, re.DOTALL)
    if not arr_match:
        # Fallback: try to find last ]; in the block
        last_close = js_block.rfind('];')
        if last_close > 0:
            arr_start = js_block.index('[')
            js_array = js_block[arr_start:last_close + 1]
        else:
            return []
    else:
        js_array = arr_match.group(1)

    # Convert JS object literal to valid JSON
    # Replace single quotes with double quotes (careful with apostrophes)
    # Add quotes around unquoted keys
    # Remove trailing commas
    # Handle JS comments
    json_str = js_to_json(js_array)

    try:
        games = json.loads(json_str)
        return games if isinstance(games, list) else []
    except json.JSONDecodeError as e:
        print(f"  WARNING: Failed to parse games from {html_path}: {e}", file=sys.stderr)
        return []


def js_to_json(js_str):
    """Convert a JavaScript object/array literal to valid JSON.
    Handles: unquoted keys, single quotes, trailing commas, comments."""
    # Remove single-line comments
    result = re.sub(r'//[^\n]*', '', js_str)
    # Remove multi-line comments
    result = re.sub(r'/\*.*?\*/', '', result, flags=re.DOTALL)

    # Process character by character to handle strings properly
    output = []
    i = 0
    while i < len(result):
        c = result[i]

        # Handle strings
        if c in ('"', "'"):
            quote = c
            s = '"'  # Always output double quotes
            i += 1
            while i < len(result) and result[i] != quote:
                if result[i] == '\\':
                    if i + 1 < len(result):
                        next_c = result[i + 1]
                        if quote == "'" and next_c == "'":
                            s += "'"
                            i += 2
                            continue
                        elif next_c == '"' and quote == "'":
                            s += '\\"'
                            i += 2
                            continue
                        else:
                            s += result[i:i+2]
                            i += 2
                            continue
                elif result[i] == '"' and quote == "'":
                    s += '\\"'
                    i += 1
                    continue
                s += result[i]
                i += 1
            s += '"'
            i += 1  # skip closing quote
            output.append(s)
            continue

        # Handle unquoted keys: identifier followed by ':'
        if c.isalpha() or c == '_':
            # Collect the identifier
            ident = ''
            j = i
            while j < len(result) and (result[j].isalnum() or result[j] == '_'):
                ident += result[j]
                j += 1
            # Check if followed by ':'
            k = j
            while k < len(result) and result[k] in (' ', '\t', '\n', '\r'):
                k += 1
            if k < len(result) and result[k] == ':':
                # JS keywords that are valid values
                if ident in ('true', 'false', 'null'):
                    # Could be a key or a value — check context
                    # If preceded by comma, opening brace, or start → it's a key
                    prev_significant = ''
                    for p in range(len(output) - 1, -1, -1):
                        stripped = output[p].strip()
                        if stripped:
                            prev_significant = stripped[-1]
                            break
                    if prev_significant in ('{', ',', '[', ''):
                        output.append('"' + ident + '"')
                    else:
                        output.append(ident)
                else:
                    output.append('"' + ident + '"')
                i = j
            else:
                # Not a key — could be true/false/null or something else
                output.append(ident)
                i = j
            continue

        output.append(c)
        i += 1

    json_str = ''.join(output)

    # Remove trailing commas before ] or }
    json_str = re.sub(r',\s*([\]}])', r'\1', json_str)

    return json_str


def extract_ecosystem_games(eco_json_path, block_to_dest):
    """Extract games from an ecosystem JSON, grouped by block → destination."""
    data = load_json(eco_json_path)
    games_by_dest = {}

    for game in data.get('games', []):
        block = game.get('block', '')
        dest_num = block_to_dest.get(block)
        if dest_num is None:
            continue
        if dest_num not in games_by_dest:
            games_by_dest[dest_num] = []
        # Remove the block field from the game (not needed in dest JSON)
        game_copy = {k: v for k, v in game.items() if k != 'block'}
        games_by_dest[dest_num].append(game_copy)

    return games_by_dest


# ============================================================
# ONTOLOGY LOADING
# ============================================================

def load_ontology():
    """Load all ontology data."""
    story_world = load_json(ONTOLOGY_DIR / 'story-world.json')
    activities = load_json(ONTOLOGY_DIR / 'activities.json')

    # Index story nodes by destination
    story_nodes = {}
    for node in story_world.get('storyNodes', []):
        dest = node.get('destination', '')
        if dest:
            story_nodes[dest] = node

    # Index activities by destination
    activities_by_dest = {}
    for act in activities:
        if act.get('_section'):
            continue  # Skip section headers
        dest = act.get('destination', '')
        if dest:
            if dest not in activities_by_dest:
                activities_by_dest[dest] = []
            activities_by_dest[dest].append(act)

    # Index characters by ID
    characters = {}
    for char in story_world.get('characters', []):
        characters[char['id']] = char

    return {
        'storyNodes': story_nodes,
        'activitiesByDest': activities_by_dest,
        'characters': characters,
        'storyWorld': story_world
    }


# ============================================================
# CHARACTER METADATA
# ============================================================

CHARACTER_AVATARS = {
    'char_yaguara': '\U0001F406',        # leopard emoji (jaguar)
    'char_candelaria': '\U0001F469',      # woman
    'char_don_prospero': '\U0001F468\u200D\U0001F4BC',  # man office worker
    'char_dona_asuncion': '\U0001F9D3',   # older person
    'char_mama_jaguar': '\U0001F405',     # tiger (closest to mother jaguar)
    'char_colibri': '\U0001F426',         # bird
    'char_abuela_ceiba': '\U0001F333',    # deciduous tree
    'char_los_antiguos': '\U0001F30C',    # milky way
    'char_sombra': '\U0001F311',          # new moon (dark)
    'char_rio_madre': '\U0001F30A',       # wave
    'char_zunzun': '\U0001F426',          # bird (hummingbird)
}

CHARACTER_NAMES = {
    'char_yaguara': 'Yaguará',
    'char_candelaria': 'Candelaria',
    'char_don_prospero': 'Don Próspero',
    'char_dona_asuncion': 'Doña Asunción',
    'char_mama_jaguar': 'Mamá Jaguar',
    'char_colibri': 'Zunzún',
    'char_abuela_ceiba': 'Abuela Ceiba',
    'char_los_antiguos': 'Los Antiguos',
    'char_sombra': 'La Sombra',
    'char_rio_madre': 'Río Madre',
    'char_zunzun': 'Zunzún',
}

# Default character lines for interjections (per character)
DEFAULT_CHARACTER_LINES = {
    'char_candelaria': [
        'Yaguará... mira.',
        'Aquí es diferente.',
        'Escucha lo que dicen.',
        'Ten cuidado.',
        'Yo conozco este lugar.',
    ],
    'char_don_prospero': [
        'Buenos días. ¿Conocen este lugar?',
        'El progreso no espera.',
        'Hay que ser prácticos.',
    ],
    'char_dona_asuncion': [
        'Escucha, hija.',
        'Las palabras tienen memoria.',
        'Todo tiene su tiempo.',
    ],
    'char_mama_jaguar': [
        'Mi pequeña... camina con cuidado.',
        'El bosque es tu casa.',
        'Recuerda de dónde vienes.',
    ],
}


# ============================================================
# DESTINATION JSON BUILDER
# ============================================================

def build_destination_json(dest_num, ontology, eco_games, folder_games):
    """Build a single dest{N}.json file."""
    dest_key = f'dest{dest_num}'
    story_node = ontology['storyNodes'].get(dest_key, {})
    ontology_activities = ontology['activitiesByDest'].get(dest_key, [])

    # --- META ---
    meta = {
        'destination': dest_key,
        'title': story_node.get('title', f'Destino {dest_num}'),
        'cefr': DEST_CEFR.get(dest_num, 'A1'),
        'world': DEST_WORLDS.get(dest_num, 'mundoDeAbajo'),
        'campbellStage': story_node.get('campbellStage', ''),
        'characters': story_node.get('characters', []),
        'closingQuestion': story_node.get('closingQuestion', ''),
    }

    # Get previous destination's closing question
    if dest_num > 1:
        prev_key = f'dest{dest_num - 1}'
        prev_node = ontology['storyNodes'].get(prev_key, {})
        meta['previousClosingQuestion'] = prev_node.get('closingQuestion', '')

    # --- ARRIVAL NARRATIVE ---
    arrival = {
        'type': 'narrative',
        'speaker': 'yaguara',
        'label': 'Yaguará',
    }
    if meta.get('previousClosingQuestion'):
        arrival['previousClosingQuestion'] = meta['previousClosingQuestion']

    narrative_beat = story_node.get('narrativeBeat', '')
    if narrative_beat:
        arrival['sections'] = [{'body': narrative_beat}]
    else:
        arrival['sections'] = [{'body': f'Yaguará llega a un nuevo lugar...'}]

    arrival['button'] = 'Comenzar'

    # --- GAMES ---
    # Priority: ecosystem games (A1-A2) > folder inline games > ontology skeletons
    games = []

    # 1. Ecosystem-sourced games
    if dest_num in eco_games:
        games.extend(eco_games[dest_num])

    # 2. Folder inline games
    if dest_num in folder_games:
        for fg in folder_games[dest_num]:
            # Avoid duplicating narratives that overlap with arrival
            if fg.get('type') == 'narrative' and not games:
                # Use folder narrative as arrival instead
                if fg.get('sections') or fg.get('text'):
                    arrival_sections = fg.get('sections', [])
                    if not arrival_sections and fg.get('text'):
                        arrival_sections = [{'body': fg['text']}]
                    if arrival_sections:
                        arrival['sections'] = arrival_sections
                    if fg.get('button'):
                        arrival['button'] = fg['button']
                continue
            games.append(fg)

    # 3. Ontology skeleton games for missing activities
    existing_game_types = set()
    for g in games:
        t = g.get('type', '')
        existing_game_types.add(t)

    for act in ontology_activities:
        act_type = act.get('gameType', '')
        if act_type == 'narrative':
            continue  # Narratives are handled by arrival/departure
        if act_type not in existing_game_types or len(games) < 8:
            skeleton = {
                'type': act_type,
                'instruction': act.get('prompt', ''),
                'label': act_type.capitalize(),
                '_needsContent': True,
                '_activityId': act.get('id', ''),
                '_objective': act.get('objective', ''),
                '_practices': act.get('practices', []),
            }
            if act.get('feedback'):
                skeleton['_feedback'] = act['feedback']
            games.append(skeleton)

    # --- ESCAPE ROOM ---
    escape_room = None
    escape_info = story_node.get('escapeRoom', {})
    if escape_info:
        escape_room = {
            'type': 'escaperoom',
            'room': {
                'name': escape_info.get('name', f'Sala {dest_num}'),
                'description': '',
                'ambience': '',
            },
            'puzzles': [],  # Will be authored in Phase 5
            'fragment': escape_info.get('fragment', ''),
            '_needsContent': True,
        }
        puzzle_types = escape_info.get('puzzleTypes', ['wordlock'])
        for pt in puzzle_types:
            escape_room['puzzles'].append({
                'puzzleType': pt,
                'prompt': '',
                'clue': '',
                'answer': '',
                '_needsContent': True,
            })

    # --- DEPARTURE ---
    departure = {
        'type': 'narrative',
        'closingQuestion': story_node.get('closingQuestion', ''),
        'yaguaraLine': '',
        'button': 'Continuar el viaje',
    }
    if dest_num < 58:
        departure['nextUrl'] = f'play.html?dest={dest_num + 1}'
    else:
        departure['nextUrl'] = 'storymap.html'
        departure['button'] = 'Completar el viaje'

    # --- CHARACTER LINES ---
    char_ids = story_node.get('characters', [])
    character_lines = {}
    character_meta = {}
    for cid in char_ids:
        if cid == 'char_yaguara':
            continue  # Yaguará has her own system
        if cid in DEFAULT_CHARACTER_LINES:
            character_lines[cid] = DEFAULT_CHARACTER_LINES[cid]
        character_meta[cid] = {
            'name': CHARACTER_NAMES.get(cid, cid),
            'avatar': CHARACTER_AVATARS.get(cid, '\U0001F464'),
        }

    # --- ASSEMBLE ---
    dest_json = {
        'meta': meta,
        'arrival': arrival,
        'games': games,
    }
    if escape_room:
        dest_json['escapeRoom'] = escape_room
    dest_json['departure'] = departure
    if character_lines:
        dest_json['characterLines'] = character_lines
    if character_meta:
        dest_json['characterMeta'] = character_meta

    return dest_json


# ============================================================
# MAIN
# ============================================================

def main():
    print("=" * 60)
    print("Building 58 destination content JSONs")
    print("=" * 60)

    # Load ontology
    print("\nLoading ontology...")
    ontology = load_ontology()
    print(f"  Story nodes: {len(ontology['storyNodes'])}")
    print(f"  Characters: {len(ontology['characters'])}")
    act_count = sum(len(v) for v in ontology['activitiesByDest'].values())
    print(f"  Activities: {act_count} across {len(ontology['activitiesByDest'])} destinations")

    # Extract ecosystem games (A1 + A2)
    print("\nExtracting ecosystem content...")
    eco_games = {}

    # A1 — try selva as primary source
    selva_a1 = CONTENT_DIR / 'selva_a1.json'
    if selva_a1.exists():
        a1_games = extract_ecosystem_games(selva_a1, A1_BLOCK_TO_DEST)
        for dest_num, games in a1_games.items():
            eco_games[dest_num] = games
        print(f"  selva_a1.json: {sum(len(v) for v in a1_games.values())} games across {len(a1_games)} blocks")

    # A2 — try selva as primary source
    selva_a2 = CONTENT_DIR / 'selva_a2.json'
    if selva_a2.exists():
        a2_games = extract_ecosystem_games(selva_a2, A2_BLOCK_TO_DEST)
        for dest_num, games in a2_games.items():
            if dest_num not in eco_games:
                eco_games[dest_num] = []
            eco_games[dest_num].extend(games)
        print(f"  selva_a2.json: {sum(len(v) for v in a2_games.values())} games across {len(a2_games)} blocks")

    # Extract inline games from destination folders
    print("\nDiscovering destination folders...")
    dest_folders = discover_dest_folders()
    print(f"  Found folders for {len(dest_folders)} destinations")

    folder_games = {}
    for dest_num, folders in sorted(dest_folders.items()):
        folder_games[dest_num] = []
        for folder in folders:
            games_html = folder / 'games.html'
            if games_html.exists():
                games = extract_games_from_html(games_html)
                if games:
                    folder_games[dest_num].extend(games)
                    print(f"  dest{dest_num} ({folder.name}): {len(games)} games")

    # Build all 58 destination JSONs
    print("\nBuilding destination JSONs...")
    stats = {'total': 0, 'with_games': 0, 'needs_content': 0, 'total_games': 0}

    for dest_num in range(1, 59):
        dest_json = build_destination_json(dest_num, ontology, eco_games, folder_games)

        # Count stats
        stats['total'] += 1
        game_count = len(dest_json['games'])
        stats['total_games'] += game_count
        if game_count > 0:
            stats['with_games'] += 1
        needs = sum(1 for g in dest_json['games'] if g.get('_needsContent'))
        if dest_json.get('escapeRoom', {}).get('_needsContent'):
            needs += 1
        if needs:
            stats['needs_content'] += 1

        # Write
        output_path = OUTPUT_DIR / f'dest{dest_num}.json'
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(dest_json, f, ensure_ascii=False, indent=2)

        status = f"{'OK' if game_count >= 8 else 'SPARSE' if game_count > 0 else 'EMPTY'}"
        print(f"  dest{dest_num}.json: {game_count} games, {needs} need content [{status}]")

    # Summary
    print("\n" + "=" * 60)
    print(f"COMPLETE: {stats['total']} destination files generated")
    print(f"  With games: {stats['with_games']}/58")
    print(f"  Total game count: {stats['total_games']}")
    print(f"  Destinations needing content: {stats['needs_content']}")
    print("=" * 60)


if __name__ == '__main__':
    main()
