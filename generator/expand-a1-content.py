#!/usr/bin/env python3
"""
Expand A1 destination content — generates additional game variants
for each destination using existing vocabulary and grammar.

This script reads dest1-12.json files, analyzes vocabulary/grammar,
and inserts additional games to roughly double game count per destination.

New game types added:
- susurro (whisper encounter — Phase 3)
- par_minimo (minimal pairs — Phase 3)
- translation (L1→L2 — Phase 6)
- sombra (shadow repetition — Phase 6)
- eco_lejano (echo comprehension — Phase 6)
- conjuro (speed production — Phase 6)
- senda (path choice — Phase 6)
- corrector (error correction — Phase 9)
- transformador (text transformation — Phase 9)
- ritmo (rhythm dictation — Phase 9)

Run: python3 generator/expand-a1-content.py [--dest N] [--dry-run]
"""
import json
import os
import sys
import random
import copy
import argparse

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')

def load_dest(n):
    path = os.path.join(CONTENT_DIR, f'dest{n}.json')
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_dest(n, data):
    path = os.path.join(CONTENT_DIR, f'dest{n}.json')
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f'  Saved dest{n}.json ({len(data.get("games",[]))} games)')

def extract_vocabulary(data):
    """Extract all vocabulary words from a destination."""
    words = set()
    sentences = []

    # Include preArrival despertar words
    for pa in data.get('preArrival', []):
        for w in pa.get('words', []):
            if isinstance(w, str):
                words.add(w.lower())
            elif isinstance(w, dict) and w.get('word'):
                words.add(w['word'].lower())

    for game in data.get('games', []):
        for w in game.get('vocabulary', []):
            if isinstance(w, str):
                words.add(w.lower())
            elif isinstance(w, dict) and w.get('word'):
                words.add(w['word'].lower())
        for w in game.get('words', []):
            if isinstance(w, str):
                words.add(w.lower())
            elif isinstance(w, dict) and w.get('word'):
                words.add(w['word'].lower())
        if game.get('answer'):
            for w in game['answer'].split():
                words.add(w.lower().strip('.,!?¿¡'))
        for q in game.get('questions', []):
            if q.get('answer'):
                words.add(q['answer'].lower().strip('.,!?¿¡'))
            if q.get('sentence') and '___' not in q['sentence']:
                sentences.append(q['sentence'].strip())
        if game.get('pairs'):
            for pair in game['pairs']:
                if isinstance(pair, list):
                    for p in pair:
                        words.add(p.lower())
        if game.get('turns'):
            for turn in game['turns']:
                if turn.get('answer'):
                    sentences.append(turn['answer'])

    # Collect from skit beats
    for game in data.get('games', []):
        if game.get('type') == 'skit' and game.get('beats'):
            for beat in game['beats']:
                if beat.get('text') and '___' not in beat['text'] and len(beat['text'].split()) >= 3:
                    sentences.append(beat['text'])
                if beat.get('target'):
                    targets = beat['target'] if isinstance(beat['target'], list) else [beat['target']]
                    for t in targets:
                        words.add(t.lower())

    # Collect from arrival narrative
    arrival = data.get('arrival', {})
    for section in arrival.get('sections', []):
        body = section.get('body', '')
        if body and '___' not in body and len(body.split()) >= 3:
            sentences.append(body)

    # Collect from conversation answers (clean complete sentences)
    for game in data.get('games', []):
        if game.get('turns'):
            for turn in game['turns']:
                answer = turn.get('answer', '')
                if answer and '___' not in answer and len(answer.split()) >= 3:
                    sentences.append(answer)

    # Deduplicate
    seen = set()
    unique_sentences = []
    for s in sentences:
        if s not in seen:
            seen.add(s)
            unique_sentences.append(s)

    return list(words), unique_sentences

def extract_grammar(data):
    """Extract grammar points from games."""
    grammar = set()
    for game in data.get('games', []):
        for g in game.get('grammar', []):
            grammar.add(g)
    return list(grammar)

def make_susurro(vocab, sentences, dest_num):
    """Susurro — whisper encounter (Phase 3). Student hears TTS whisper, identifies word."""
    if len(vocab) < 4:
        return None
    selected = random.sample(vocab, min(5, len(vocab)))
    questions = []
    for word in selected:
        distractors = [w for w in vocab if w != word]
        opts = [word] + random.sample(distractors, min(2, len(distractors)))
        random.shuffle(opts)
        questions.append({
            "audio": word,
            "question": "¿Qué palabra es?",
            "answer": word,
            "options": opts
        })
    return {
        "type": "susurro",
        "label": "Susurro",
        "instruction": "Escucha el susurro y elige la palabra.",
        "questions": questions
    }

def make_translation(vocab, sentences, dest_num):
    """Translation game — translate simple phrases."""
    # Common A1 translations for core vocabulary
    translations = {
        "hola": "hello", "adiós": "goodbye", "soy": "I am", "eres": "you are",
        "yo": "I", "tú": "you", "sí": "yes", "no": "no",
        "es": "is", "un": "a/an", "una": "a/an", "el": "the", "la": "the",
        "grande": "big", "pequeño": "small", "bonito": "beautiful",
        "bueno": "good", "malo": "bad", "agua": "water", "río": "river",
        "casa": "house", "familia": "family", "amigo": "friend",
        "gato": "cat", "perro": "dog", "pájaro": "bird", "árbol": "tree",
        "sol": "sun", "luna": "moon", "día": "day", "noche": "night",
        "comer": "to eat", "beber": "to drink", "hablar": "to speak",
        "nombre": "name", "mamá": "mom", "papá": "dad",
        "hermano": "brother", "hermana": "sister",
        "rojo": "red", "azul": "blue", "verde": "green", "blanco": "white",
        "negro": "black", "amarillo": "yellow",
        "uno": "one", "dos": "two", "tres": "three", "cuatro": "four",
        "cinco": "five", "seis": "six", "siete": "seven", "ocho": "eight",
        "nueve": "nine", "diez": "ten",
        "tiene": "has", "hay": "there is", "necesita": "needs",
        "quiere": "wants", "puede": "can",
    }

    translatable = [w for w in vocab if w in translations]
    if len(translatable) < 3:
        return None

    selected = random.sample(translatable, min(5, len(translatable)))
    questions = []
    for word in selected:
        # Direction: English → Spanish (recognition)
        distractors = [w for w in translatable if w != word]
        opts = [word] + random.sample(distractors, min(2, len(distractors)))
        random.shuffle(opts)
        questions.append({
            "prompt": translations[word],
            "answer": word,
            "options": opts
        })
    return {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Elige la traducción.",
        "questions": questions
    }

def make_sombra(sentences, dest_num):
    """Sombra — shadow repetition (Phase 6). Listen and repeat."""
    usable = [s for s in sentences if 3 <= len(s.split()) <= 8 and s.strip()]
    if len(usable) < 3:
        return None
    selected = random.sample(usable, min(5, len(usable)))
    phrases = []
    for s in selected:
        phrases.append({
            "audio": s,
            "text": s
        })
    return {
        "type": "sombra",
        "label": "Sombra",
        "instruction": "Escucha y repite.",
        "timeLimit": 5000,
        "phrases": phrases
    }

def make_corrector(vocab, sentences, dest_num):
    """Corrector — find the error (Phase 9)."""
    # Generate sentences with deliberate errors
    error_patterns = [
        ("Yo eres {name}.", "Yo soy {name}.", "eres → soy"),
        ("Tú soy {name}.", "Tú eres {name}.", "soy → eres"),
        ("Yo soy un {name}.", "Yo soy {name}.", "remove 'un'"),
    ]

    usable = [s for s in sentences if len(s.split()) >= 3]
    if len(usable) < 2:
        return None

    # Create error versions of real sentences
    items = []
    for s in usable[:4]:
        words = s.split()
        if len(words) >= 3:
            # Swap two adjacent words
            idx = random.randint(0, len(words) - 2)
            wrong = words.copy()
            wrong[idx], wrong[idx + 1] = wrong[idx + 1], wrong[idx]
            items.append({
                "sentence": ' '.join(wrong),
                "corrected": s,
                "errorType": "word order"
            })

    if len(items) < 2:
        return None

    return {
        "type": "corrector",
        "label": "Corrector",
        "instruction": "Encuentra y corrige el error.",
        "items": items[:4]
    }

def make_eco_lejano(vocab, sentences, dest_num):
    """Eco lejano — echo comprehension (Phase 6). Hear phrase, pick missing word."""
    usable = [s for s in sentences if len(s.split()) >= 3]
    if len(usable) < 3:
        return None

    items = []
    for s in usable[:5]:
        words = s.split()
        # Pick a content word to blank out (skip first/last for better context)
        candidates = [i for i in range(len(words))
                      if len(words[i].strip('.,!?¿¡')) > 2]
        if not candidates:
            continue
        blank_idx = random.choice(candidates)
        answer = words[blank_idx].strip('.,!?¿¡')
        blanked = words.copy()
        blanked[blank_idx] = '___'

        distractors = [w for w in vocab if w.lower() != answer.lower()]
        opts = [answer] + random.sample(distractors, min(2, len(distractors)))
        random.shuffle(opts)

        items.append({
            "audio": s,           # Full sentence for TTS (no blanks)
            "sentence": ' '.join(blanked),  # Display with blank
            "answer": answer,
            "options": opts
        })

    # Validate: audio must be the ORIGINAL sentence, not blanked
    for item in items:
        if '___' in item.get('audio', ''):
            # Bug: audio got blanked somehow, discard
            items.remove(item)

    if len(items) < 2:
        return None

    return {
        "type": "eco_lejano",
        "label": "Eco lejano",
        "instruction": "Escucha el eco y completa la palabra que falta.",
        "items": items
    }

def make_senda(vocab, sentences, dest_num):
    """Senda — path choice (Phase 6). Contextual sentence completion with branching."""
    usable = [s for s in sentences if len(s.split()) >= 4]
    if len(usable) < 2:
        return None

    steps = []
    for s in usable[:4]:
        words = s.split()
        # Blank a word
        candidates = [i for i in range(1, len(words))
                      if len(words[i].strip('.,!?¿¡')) > 2]
        if not candidates:
            continue
        blank_idx = random.choice(candidates)
        answer = words[blank_idx].strip('.,!?¿¡')
        blanked = words.copy()
        blanked[blank_idx] = '___'

        distractors = [w for w in vocab if w.lower() != answer.lower()]
        opts = [answer] + random.sample(distractors, min(2, len(distractors)))
        random.shuffle(opts)

        steps.append({
            "sentence": ' '.join(blanked),
            "answer": answer,
            "options": opts
        })

    if len(steps) < 2:
        return None

    return {
        "type": "senda",
        "label": "Senda",
        "instruction": "Elige el camino correcto.",
        "steps": steps
    }

def make_additional_fill(vocab, grammar, dest_num):
    """Additional fill-in-the-blank focused on grammar."""
    # Generate extra fill exercises based on grammar points
    ser_forms = {
        "yo": "soy", "tú": "eres", "él": "es", "ella": "es",
        "nosotros": "somos", "ellos": "son", "ellas": "son"
    }

    questions = []
    grammar_str = ' '.join(grammar).lower()

    if 'ser' in grammar_str:
        subjects = list(ser_forms.keys())
        random.shuffle(subjects)
        for subj in subjects[:4]:
            form = ser_forms[subj]
            all_forms = list(set(ser_forms.values()))
            opts = [form] + [f for f in all_forms if f != form][:2]
            random.shuffle(opts)
            questions.append({
                "sentence": f"{subj.capitalize()} ___ ...",
                "answer": form,
                "options": opts
            })

    if not questions:
        return None

    return {
        "type": "fill",
        "label": "Completar",
        "grammar": grammar,
        "instruction": "Elige la forma correcta.",
        "questions": questions
    }

def make_additional_builder(sentences, dest_num):
    """Additional builder (word ordering) from existing sentences."""
    usable = [s for s in sentences if 3 <= len(s.split()) <= 7]
    if len(usable) < 1:
        return None

    s = random.choice(usable)
    words = s.split()
    shuffled = words.copy()
    random.shuffle(shuffled)
    # Make sure shuffled != original
    attempts = 0
    while shuffled == words and attempts < 5:
        random.shuffle(shuffled)
        attempts += 1

    return {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras.",
        "words": shuffled,
        "answer": s
    }

def make_additional_pair(vocab, dest_num):
    """Additional pair matching with different word groupings."""
    if len(vocab) < 6:
        return None

    # Group by semantic relations for A1
    opposite_pairs = [
        ("hola", "adiós"), ("sí", "no"), ("grande", "pequeño"),
        ("bueno", "malo"), ("día", "noche"), ("sol", "luna"),
        ("blanco", "negro"), ("mamá", "papá"), ("hermano", "hermana"),
        ("rojo", "azul"), ("uno", "dos"), ("tres", "cuatro"),
        ("yo", "tú"), ("soy", "eres"), ("el", "la"),
    ]

    available = [(a, b) for a, b in opposite_pairs
                 if a in vocab or b in vocab]

    if len(available) < 3:
        return None

    selected = random.sample(available, min(4, len(available)))
    pairs = [[a, b] for a, b in selected]

    return {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Une cada palabra con su pareja.",
        "pairs": pairs
    }

def expand_destination(data, dest_num, dry_run=False):
    """Expand a single destination with additional games."""
    # Strip previously generated games to avoid stacking
    if data.get('games'):
        data['games'] = [g for g in data['games'] if not g.get('_generated')]

    vocab, sentences = extract_vocabulary(data)
    grammar = extract_grammar(data)

    original_count = len(data.get('games', []))
    new_games = []

    # Generate new games (order follows 369 phases)
    generators = [
        ('susurro', lambda: make_susurro(vocab, sentences, dest_num)),
        ('eco_lejano', lambda: make_eco_lejano(vocab, sentences, dest_num)),
        ('translation', lambda: make_translation(vocab, sentences, dest_num)),
        ('sombra', lambda: make_sombra(sentences, dest_num)),
        ('senda', lambda: make_senda(vocab, sentences, dest_num)),
        ('fill_extra', lambda: make_additional_fill(vocab, grammar, dest_num)),
        ('builder_extra', lambda: make_additional_builder(sentences, dest_num)),
        ('pair_extra', lambda: make_additional_pair(vocab, dest_num)),
        ('corrector', lambda: make_corrector(vocab, sentences, dest_num)),
    ]

    for name, gen_fn in generators:
        try:
            game = gen_fn()
            if game:
                game['_generated'] = True
                game['_generatorId'] = f'{name}_dest{dest_num}'
                new_games.append(game)
        except Exception as e:
            print(f'  Warning: {name} generator failed for dest{dest_num}: {e}')

    if not new_games:
        print(f'  dest{dest_num}: No new games generated (vocabulary too small?)')
        return data

    # Insert new games before the escape room (which is typically near the end)
    games = data.get('games', [])
    # Find insertion point — before escaperoom/cronica/ritmo (Phase 9 games)
    insert_idx = len(games)
    for i, g in enumerate(games):
        if g.get('type') in ('escaperoom', 'cronica', 'portafolio', 'autoevaluacion'):
            insert_idx = i
            break

    # Sort new games by phase order
    phase_order = {'susurro': 3, 'eco_lejano': 6, 'translation': 6, 'sombra': 6,
                   'senda': 6, 'fill': 6, 'builder': 6, 'pair': 6, 'corrector': 9}
    new_games.sort(key=lambda g: phase_order.get(g['type'], 6))

    # Insert
    for i, game in enumerate(new_games):
        games.insert(insert_idx + i, game)

    data['games'] = games

    added = len(new_games)
    total = len(games)
    print(f'  dest{dest_num}: {original_count} → {total} games (+{added})')

    if not dry_run:
        save_dest(dest_num, data)

    return data

def main():
    parser = argparse.ArgumentParser(description='Expand A1 content')
    parser.add_argument('--dest', type=int, help='Expand specific destination (1-12)')
    parser.add_argument('--dry-run', action='store_true', help='Preview without saving')
    args = parser.parse_args()

    if args.dest:
        if args.dest < 1 or args.dest > 12:
            print('Error: --dest must be 1-12 for A1')
            sys.exit(1)
        dests = [args.dest]
    else:
        dests = range(1, 13)

    print('=== A1 Content Expansion ===')
    total_before = 0
    total_after = 0

    for n in dests:
        data = load_dest(n)
        total_before += len(data.get('games', []))
        data = expand_destination(data, n, dry_run=args.dry_run)
        total_after += len(data.get('games', []))

    print(f'\nTotal: {total_before} → {total_after} games (+{total_after - total_before})')
    if args.dry_run:
        print('(dry run — no files modified)')

if __name__ == '__main__':
    main()
