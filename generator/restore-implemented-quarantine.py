#!/usr/bin/env python3
"""
Restore quarantined games whose types now have renderers.
Only restores: flashnote, crossword, explorador, kloo
Inserts before the last 2 games (escaperoom + cronica) in each destination.
"""
import json, os

CONTENT = '/home/babelfree.com/public_html/content'
QUARANTINE = os.path.join(CONTENT, 'quarantine/unimplemented_types.json')
IMPLEMENTED = {'flashnote', 'crossword', 'explorador', 'kloo'}

with open(QUARANTINE) as f:
    qdata = json.load(f)

restored_total = 0
still_quarantined = 0
remaining_games = {}

for dest_key, dest_data in qdata.get('quarantined_games', {}).items():
    to_restore = []
    to_keep = []

    for game in dest_data['games']:
        if game['type'] in IMPLEMENTED:
            to_restore.append(game)
        else:
            to_keep.append(game)

    if to_restore:
        dest_file = os.path.join(CONTENT, f'{dest_key}.json')
        with open(dest_file) as f:
            content = json.load(f)

        games = content.get('games', [])

        # Find insert point: before last 2 games (escaperoom + cronica)
        # Or if no escaperoom/cronica, just append
        insert_idx = len(games)
        for i in range(len(games) - 1, max(len(games) - 3, -1), -1):
            if i >= 0 and games[i].get('type') in ('escaperoom', 'cronica'):
                insert_idx = i

        for game in reversed(to_restore):
            games.insert(insert_idx, game)

        content['games'] = games

        with open(dest_file, 'w') as f:
            json.dump(content, f, ensure_ascii=False, indent=2)

        print(f"  {dest_key}: restored {len(to_restore)} games ({', '.join(g['type'] for g in to_restore)}), total now {len(games)}")
        restored_total += len(to_restore)

    if to_keep:
        remaining_games[dest_key] = {
            'count': len(to_keep),
            'types_found': list(set(g['type'] for g in to_keep)),
            'games': to_keep
        }
        still_quarantined += len(to_keep)

# Update quarantine file with remaining games
remaining_types = set()
for d in remaining_games.values():
    remaining_types.update(d['types_found'])

updated = {
    'description': 'Games quarantined because their game type has no normalizer/renderer in yaguara-engine.js',
    'unimplemented_types': sorted(remaining_types),
    'summary': {
        'total_removed': still_quarantined,
        'destinations_affected': len(remaining_games),
        'by_destination': {k: v['count'] for k, v in remaining_games.items()}
    },
    'quarantined_games': remaining_games
}

with open(QUARANTINE, 'w') as f:
    json.dump(updated, f, ensure_ascii=False, indent=2)

print(f"\nRestored: {restored_total} games")
print(f"Still quarantined: {still_quarantined} games ({len(remaining_types)} types: {', '.join(sorted(remaining_types))})")
