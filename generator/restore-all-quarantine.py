#!/usr/bin/env python3
"""
Restore ALL remaining quarantined games — all 12 types now have renderers.
Inserts before the last 2 games (escaperoom + cronica).
"""
import json, os

CONTENT = '/home/babelfree.com/public_html/content'
QUARANTINE = os.path.join(CONTENT, 'quarantine/unimplemented_types.json')

with open(QUARANTINE) as f:
    qdata = json.load(f)

restored_total = 0

for dest_key, dest_data in qdata.get('quarantined_games', {}).items():
    games = dest_data['games']
    if not games:
        continue

    dest_file = os.path.join(CONTENT, f'{dest_key}.json')
    with open(dest_file) as f:
        content = json.load(f)

    dest_games = content.get('games', [])

    # Find insert point: before last 2 games (escaperoom + cronica)
    insert_idx = len(dest_games)
    for i in range(len(dest_games) - 1, max(len(dest_games) - 3, -1), -1):
        if i >= 0 and dest_games[i].get('type') in ('escaperoom', 'cronica'):
            insert_idx = i

    for game in reversed(games):
        dest_games.insert(insert_idx, game)

    content['games'] = dest_games

    with open(dest_file, 'w') as f:
        json.dump(content, f, ensure_ascii=False, indent=2)

    print(f"  {dest_key}: restored {len(games)} games ({', '.join(g['type'] for g in games)}), total now {len(dest_games)}")
    restored_total += len(games)

# Clear the quarantine file
cleared = {
    "description": "All quarantined games have been restored — renderers implemented for all types.",
    "unimplemented_types": [],
    "summary": {"total_removed": 0, "destinations_affected": 0, "by_destination": {}},
    "quarantined_games": {}
}
with open(QUARANTINE, 'w') as f:
    json.dump(cleared, f, ensure_ascii=False, indent=2)

print(f"\nRestored: {restored_total} games. Quarantine is now empty.")
