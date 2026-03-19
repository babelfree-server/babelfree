# Multilingual Blueprint — El Viaje del Jaguar

Spanish is the **master blueprint**. Every system — game engine, content structure, CEFR pipeline, narrative architecture, audio, illustrations — is designed to be replicated across languages. This document defines how.

## Architecture: 4 Layers

### Layer 0: Language-Agnostic Systems (already shared)
These work for ANY target language without modification:
- Game engine (`yaguara-engine.js`) — renders all 58 game types
- AudioManager (`audio-manager.js`) — loops, stings, SFX (same files per language)
- Evidence engine + Adaptivity engine — track mastery of linguistic targets
- PersonalLexicon — spaced repetition + mastery scoring
- Riddle quest engine (`riddle-quest.js`) — bridge/rana/journal mechanics
- Escape room renderer — 7 puzzle types
- Storymap, dashboard, auth, PWA, ad system
- QA validator + analytics dashboard

### Layer 1: Content (per target language)
Each language needs its own content set:

| Asset | Spanish count | Per-language effort |
|---|---|---|
| `content/dest1-58.json` | 58 files, 2,237 games | Translate + culturally adapt |
| `content/templates/dest1-58-templates.json` | 58 files, 40 templates each | Translate |
| `content/expanded/dest1-58-expanded.json` | 58 files, 14,430 eco games | Auto-generated from templates |
| Ecosystem files (8 × CEFR levels) | 24 files | Translate + localize biome references |
| `content/busqueda-riddles.json` | 58 riddles | Replace with target-language poet/riddles |
| Escape room puzzles (in dest JSON) | 179 puzzles across 58 rooms | Translate |
| Arrival narratives | 58 narrative sections | Translate + adapt cultural references |
| Character dialogue (`characterLines`) | ~200+ lines per dest | Translate |

**Narrative adaptation**: The Colombian setting (Yaguará, ceiba, Atrato, vallenato) is Spanish-specific. Other languages need their own mythological frame:
- French → could be West African/Québécois mythology
- German → could be Black Forest/alpine mythology
- Japanese → could be Shinto/nature spirit mythology
- Each language gets its own spirit guide, its own "world that is forgetting names"

### Layer 2: Dictionary (per target language)
Already operational for 12 languages:
- Kaikki import pipeline: `seed-kaikki-import.php`
- CEFR leveling: `seed-frequency-all.php` → `propagate-frequency.php` → `assign-cefr-levels.php`
- Wiktionary enrichment: `enrich-wiktionary.php`
- Each language has complete word → definition → example → IPA → conjugation data

### Layer 3: UI Strings (per interface language)
Already operational for 104 languages:
- `login_lang_data.js` — auth UI (104 languages)
- `api/scripts/data/dict_i18n.json` — dictionary UI (12 languages)
- `users.interface_lang` — stored per user, immersion-gated by CEFR

## Immersion Rule

**Immersion is level-gated**:
- A1–A2 Basic → UI in student's native language
- A2 Advanced–C2 → 100% target language

This rule is universal across all languages. The `grammar-responder.php` already implements it.

## Adding a New Target Language: Checklist

### Phase 1: Dictionary (1-2 days, automated)
- [ ] Download Kaikki dump for target language
- [ ] Run `seed-kaikki-import.php --lang={code}`
- [ ] Run `seed-frequency-all.php --lang={code}`
- [ ] Run `propagate-frequency.php --lang={code}`
- [ ] Run `assign-cefr-levels.php --lang={code} --force`
- [ ] Run `enrich-wiktionary.php --lang={code} --all`
- [ ] Run `generate-sitemap-split.php` to create SEO sitemaps
- [ ] Add i18n entry to `dict_i18n.json`
- [ ] Verify: `validate-content.php --lang={code}` passes

### Phase 2: UI Translations (1 day)
- [ ] Add language to `dict_i18n.json` (40+ UI strings)
- [ ] Verify login page translations in `login_lang_data.js`
- [ ] Add SEO landing page (`learn-{language}.html`)

### Phase 3: Game Content (weeks-months)
- [ ] Design mythological frame (spirit guide, setting, narrative arc)
- [ ] Author 58 destination narratives (arrival, departure, character dialogue)
- [ ] Create 2,320 game templates (40 per destination)
- [ ] Expand via `build-ecosystem-variants.js` (→ ~16,750 games)
- [ ] Create 58 escape room puzzles (3 per room)
- [ ] Source/create riddle content for busqueda quest
- [ ] Run content validator: `validate-content.php --dest=1-58 --check=all`

### Phase 4: Audio + Art (parallel with Phase 3)
- [ ] Commission illustrations (same brief structure, adapted for cultural setting)
- [ ] Commission audio (same brief structure, adapted for musical traditions)

### Phase 5: QA + Launch
- [ ] CEFR validation pass (allowlist expansion for target language)
- [ ] Grammar auto-fix pass
- [ ] Live testing with native speakers
- [ ] SEO: sitemaps, Schema.org, hreflang tags
- [ ] Analytics dashboard verification

## Database Schema (already multilingual)

```sql
-- Users store language preferences
users.interface_lang    -- UI display language
users.detected_lang     -- browser-detected language
users.native_lang       -- self-reported native language

-- Dictionary supports all languages
dict_words.lang_code    -- word's language
dict_definitions.lang_code -- definition's language
dict_languages          -- 105 supported languages

-- Content is language-keyed by directory
content/{lang}/dest1-58.json  -- future: per-language content dirs
```

## Current Status

| Language | Dictionary | UI | Game Content | Audio | Art |
|---|---|---|---|---|---|
| **Spanish** | 735K words | Full | 58 dest, 16,667 games | Brief ready | Brief ready |
| French | 1.68M words | Full | — | — | — |
| Dutch | 687K words | Full | — | — | — |
| Russian | 456K words | Full | — | — | — |
| Chinese | 437K words | Full | — | — | — |
| Italian | 405K words | Full | — | — | — |
| Portuguese | 385K words | Full | — | — | — |
| German | 364K words | Full | — | — | — |
| Japanese | 202K words | Full | — | — | — |
| Korean | 166K words | Full | — | — | — |
| Arabic | 120K words | Full | — | — | — |
| English | 109K words | Full | — | — | — |

## Key Principle

> "Spanish is the master blueprint; all systems must be replicable across languages."

The game engine, evidence system, adaptivity engine, riddle quest mechanics, escape room renderer, practice engine, personal lexicon, audio manager, and analytics dashboard are **100% language-agnostic**. Only content (Layer 1) needs to be created per language.
