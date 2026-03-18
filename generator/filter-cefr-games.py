#!/usr/bin/env python3
"""
filter-cefr-games.py
Removes extracted games that use grammar not yet available at their destination.
Quarantined games are saved to content/quarantine/ for potential reassignment.

Rules:
  dest1-12 (A1): NO past tense forms, NO subjunctive, NO conditional
  dest13 (A2 reflexives): NO pretérito (introduced dest14)
  dest19-20 (B1): NO subjunctive (introduced dest21)
"""

import json
import re
import os
from pathlib import Path

CONTENT_DIR = Path('/home/babelfree.com/public_html/content')
QUARANTINE_DIR = CONTENT_DIR / 'quarantine'
QUARANTINE_DIR.mkdir(exist_ok=True)

# ============================================================
# PAST TENSE DETECTION (for A1 filtering)
# ============================================================
PAST_FORMS = re.compile(
    r'\b('
    # Common pretérito endings
    r'ayer|la\s+semana\s+pasada|el\s+año\s+pasado|el\s+mes\s+pasado'
    r'|pretérito|imperfect[oa]'
    # Specific preterite forms
    r'|comí|caminé|viajé|bailé|nadé|hablé|bebí|escribí|dormí|visité|compré'
    r'|estudié|trabajé|cociné|jugué|llegué|canté|tomé|salí|leí|corrí'
    r'|comió|caminó|viajó|bailó|nadó|habló|bebió|escribió|durmió|visitó'
    r'|compró|estudió|trabajó|cocinó|jugó|llegó|cantó|tomó|salió|leyó'
    r'|corrió|nació|dijo|hizo|fue|fui|fuimos|fueron|fuiste'
    r'|hiciste|hicimos|hicieron|dieron|dio|vi|vio|vimos|vieron'
    r'|vinieron|vino|viniste'
    r'|comiste|viajaste|hablaste|bailaste'
    # Imperfecto forms
    r'|era[ns]?|estaba[ns]?|tenía[ns]?|había|hacía|cantaba[ns]?|llovía'
    r'|vivía[ns]?|comía[ns]?|iba[ns]?|dormía[ns]?'
    # Present perfect
    r'|ha\s+\w+ado|ha\s+\w+ido|has\s+\w+ado|has\s+\w+ido'
    r'|he\s+\w+ado|he\s+\w+ido'
    r')\b',
    re.IGNORECASE
)

# ============================================================
# SUBJUNCTIVE DETECTION (for B1 dest19-20 filtering)
# ============================================================
SUBJ_FORMS = re.compile(
    r'\b('
    r'subjuntivo|subjunctive'
    r'|llueva|destruyan|destruya|hable[ns]?|come[ns]|viva[ns]?'
    r'|sea[ns]?|esté[ns]?|tenga[ns]?|haga[ns]?|vaya[ns]?|diga[ns]?'
    r'|ponga[ns]?|salga[ns]?|venga[ns]?|quiera[ns]?|pueda[ns]?'
    r'|sepa[ns]?|conozca[ns]?|duerma[ns]?|sienta[ns]?|piense[ns]?'
    r'|ojalá|es\s+posible\s+que|es\s+importante\s+que'
    r'|es\s+necesario\s+que|espero\s+que|quiero\s+que'
    r')\b',
    re.IGNORECASE
)

# ============================================================
# FILTER RULES
# ============================================================
def should_quarantine(game, dest_num):
    """Return True if this game uses grammar not yet available at dest_num."""
    # Skip authored content types
    if game.get('type') in ('cronica',):
        return False

    text = json.dumps(game, ensure_ascii=False)

    # A1 (dest1-12): No past tense at all
    if 1 <= dest_num <= 12:
        if PAST_FORMS.search(text):
            return True

    # dest13: No pretérito (only reflexives being introduced)
    if dest_num == 13:
        if PAST_FORMS.search(text):
            return True

    # dest19-20: No subjunctive (introduced at dest21)
    if dest_num in (19, 20):
        if SUBJ_FORMS.search(text):
            return True

    return False


def main():
    print("=" * 60)
    print("CEFR Game Content Filter")
    print("=" * 60)

    total_quarantined = 0

    for dest_num in range(1, 59):
        path = CONTENT_DIR / ('dest%d.json' % dest_num)
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        games = data.get('games', [])
        kept = []
        quarantined = []

        for game in games:
            if should_quarantine(game, dest_num):
                quarantined.append(game)
            else:
                kept.append(game)

        if quarantined:
            data['games'] = kept
            with open(path, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)

            # Save quarantined games
            q_path = QUARANTINE_DIR / ('dest%d_quarantined.json' % dest_num)
            with open(q_path, 'w', encoding='utf-8') as f:
                json.dump(quarantined, f, ensure_ascii=False, indent=2)

            print('  dest%d: quarantined %d games (%d remaining)' % (
                dest_num, len(quarantined), len(kept)))
            total_quarantined += len(quarantined)

    print('\n' + '=' * 60)
    print('COMPLETE: %d games quarantined to content/quarantine/' % total_quarantined)
    print('These games can be reassigned to the correct CEFR level later.')
    print('=' * 60)


if __name__ == '__main__':
    main()
