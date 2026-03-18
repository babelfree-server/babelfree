#!/usr/bin/env python3
"""
Illustration Checklist Generator
Scans 58 dest JSONs + ontology story-world.json to produce a Markdown
checklist of all illustration needs, priorities, and current status.

Usage:
    python3 generator/illustration-checklist.py
"""

import json
import os
import sys

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONTENT_DIR = os.path.join(BASE, 'content')
STORY_WORLD = os.path.join(BASE, 'ontology', 'story-world.json')
TOTAL_DESTS = 58

# Priority tiers
PRIORITY = {
    'A1': 'P1',  # Core onboarding — illustrate first
    'A2': 'P2',
    'B1': 'P2',
    'B2': 'P3',
    'C1': 'P3',
    'C2': 'P4',
}


def load_story_world():
    with open(STORY_WORLD, 'r', encoding='utf-8') as f:
        return json.load(f)


def load_dest(n):
    path = os.path.join(CONTENT_DIR, f'dest{n}.json')
    if not os.path.exists(path):
        return None
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def get_story_node(story_world, dest_id):
    for node in story_world.get('storyNodes', []):
        if node.get('destination') == dest_id:
            return node
    return None


def get_character_name(story_world, char_id):
    for c in story_world.get('characters', []):
        if c.get('id') == char_id:
            return c.get('name', char_id)
    return char_id


def main():
    story_world = load_story_world()

    lines = []
    lines.append('# Illustration Checklist — El Viaje del Jaguar')
    lines.append('')
    lines.append(f'Generated: {__import__("datetime").date.today().isoformat()}')
    lines.append('')
    lines.append('---')
    lines.append('')

    has_arrival = 0
    missing_arrival = 0
    escape_rooms = 0
    total_illustrations_needed = 0

    for n in range(1, TOTAL_DESTS + 1):
        dest_id = f'dest{n}'
        dest_data = load_dest(n)
        node = get_story_node(story_world, dest_id)

        if not dest_data and not node:
            lines.append(f'## Destino {n} — NO DATA')
            lines.append('')
            continue

        meta = (dest_data or {}).get('meta', {})
        title = meta.get('title') or (node or {}).get('title', '???')
        cefr = meta.get('cefr') or (node or {}).get('cefr', '??')
        campbell = (node or {}).get('campbellStage', '—')
        narrative_beat = (node or {}).get('narrativeBeat', '—')
        priority = PRIORITY.get(cefr, 'P4')

        # Characters
        char_ids = (node or {}).get('characters', [])
        char_names = [get_character_name(story_world, c) for c in char_ids]

        # Arrival image status
        arrival = (dest_data or {}).get('arrival', {})
        has_image = bool(arrival.get('image'))
        if has_image:
            has_arrival += 1
        else:
            missing_arrival += 1

        # Escape room
        escape_room = (node or {}).get('escapeRoom')
        has_escape = bool(escape_room)
        if has_escape:
            escape_rooms += 1

        total_illustrations_needed += 1  # arrival
        if has_escape:
            total_illustrations_needed += 1  # escape room illustration

        lines.append(f'## Destino {n}: {title}')
        lines.append('')
        lines.append(f'- **CEFR**: {cefr} | **Priority**: {priority}')
        lines.append(f'- **Campbell stage**: {campbell}')
        lines.append(f'- **Characters**: {", ".join(char_names) if char_names else "—"}')
        lines.append(f'- **Narrative beat**: {narrative_beat}')
        lines.append(f'- **Arrival illustration**: {"[x] " + arrival.get("image", "") if has_image else "[ ] NEEDED"}')
        if has_escape:
            er_name = escape_room.get('name', '—')
            lines.append(f'- **Escape room illustration**: [ ] NEEDED — "{er_name}"')
        lines.append('')

    # Summary
    summary = [
        '',
        '---',
        '',
        '# Summary',
        '',
        f'- Total destinations: {TOTAL_DESTS}',
        f'- Arrival illustrations done: {has_arrival}',
        f'- Arrival illustrations needed: {missing_arrival}',
        f'- Escape rooms needing illustration: {escape_rooms}',
        f'- **Total illustrations needed: {total_illustrations_needed}**',
        '',
        '## Priority breakdown',
        '',
    ]

    # Count by priority
    priority_counts = {}
    for n in range(1, TOTAL_DESTS + 1):
        dest_data = load_dest(n)
        node = get_story_node(story_world, f'dest{n}')
        cefr = ((dest_data or {}).get('meta', {}).get('cefr')
                or (node or {}).get('cefr', '??'))
        p = PRIORITY.get(cefr, 'P4')
        priority_counts[p] = priority_counts.get(p, 0) + 1

    for p in sorted(priority_counts.keys()):
        summary.append(f'- {p}: {priority_counts[p]} destinations')

    lines.extend(summary)

    output = '\n'.join(lines)
    print(output)


if __name__ == '__main__':
    main()
