#!/usr/bin/env python3
"""
validate-content-shapes.py — Structural validator for CEFR-gap game types.

Checks that every CEFR-gap game in all 58 dest JSONs has the data shape
its renderer expects. Run: python3 generator/validate-content-shapes.py
"""

import json
import os
import sys

BASE = os.path.join(os.path.dirname(__file__), '..', 'content')

# Required fields per game type.
# Nested dicts mean: key must be a list, each element must have those sub-keys.
SHAPES = {
    'par_minimo': {
        'pairs': {'_each': ['wordA', 'wordB', 'correct']}
    },
    'dictogloss': {
        'text': str,
        'keywords': list,
        'minMatch': (int, float)
    },
    'corrector': {
        'passage': str,
        'errors': {'_each': ['wrong', 'correct']}
    },
    'brecha': {
        'cardA': {'facts': list},
        'cardB': {'facts': list},
        'questions': list
    },
    'resumen': {
        'passage': str,
        'keySentences': list,
        'modelSummary': str
    },
    'registro': {
        'sourceText': str,
        'targetRegister': str,
        'modelAnswer': str
    },
    'debate': {
        'proposition': str,
        'rounds': {'_each': ['options', 'correctIndex']}
    },
    'descripcion': {
        '_oneOf': [
            {'scene': str},
            {'promptQuestion': str}
        ],
        '_oneOf2': [
            {'vocabulary': list},
            {'options': list}
        ]
    },
    'ritmo': {
        'words': {'_each': ['word', 'syllables', 'stressIndex']}
    },
    'cronometro': {
        'questions': {'_each': ['prompt', 'options', 'timeLimit']}
    },
    'portafolio': {
        'prompt': str,
        'guidingQuestions': list
    },
    'autoevaluacion': {
        'statements': {'_each': ['text']}
    },
    'negociacion': {
        'positionA': {'speaker': str, 'text': str},
        'positionB': {'speaker': str, 'text': str},
        'mediationOptions': list
    },
    'transformador': {
        'sourceText': str,
        'targetGenre': str,
        'modelAnswer': str
    }
}


def check_field(game, key, spec, path):
    """Check a single field. Returns list of error strings."""
    errors = []
    val = game.get(key)
    if val is None:
        errors.append(f'{path}: missing required field "{key}"')
        return errors

    # Type check for simple types
    if spec is str:
        if not isinstance(val, str) or not val.strip():
            errors.append(f'{path}: "{key}" must be a non-empty string')
    elif spec is list:
        if not isinstance(val, list) or len(val) == 0:
            errors.append(f'{path}: "{key}" must be a non-empty list')
    elif isinstance(spec, tuple):
        if not isinstance(val, spec):
            errors.append(f'{path}: "{key}" must be {spec}')
    elif isinstance(spec, dict):
        if '_each' in spec:
            # val must be a list, each item must have the sub-keys
            if not isinstance(val, list) or len(val) == 0:
                errors.append(f'{path}: "{key}" must be a non-empty list')
            else:
                for i, item in enumerate(val):
                    if not isinstance(item, dict):
                        errors.append(f'{path}: "{key}"[{i}] must be an object')
                        continue
                    for sub_key in spec['_each']:
                        if sub_key not in item:
                            errors.append(f'{path}: "{key}"[{i}] missing "{sub_key}"')
        else:
            # Nested object check
            if not isinstance(val, dict):
                errors.append(f'{path}: "{key}" must be an object')
            else:
                for sub_key, sub_spec in spec.items():
                    errors.extend(check_field(val, sub_key, sub_spec, f'{path}.{key}'))
    return errors


def validate_game(game, game_type, path):
    """Validate a single game against its shape. Returns list of error strings."""
    shape = SHAPES.get(game_type)
    if not shape:
        return []

    errors = []
    for key, spec in shape.items():
        if key == '_oneOf':
            # At least one of the alternatives must be present
            found = False
            for alt in spec:
                alt_key = list(alt.keys())[0]
                if alt_key in game and game[alt_key]:
                    found = True
                    break
            if not found:
                alt_keys = [list(a.keys())[0] for a in spec]
                errors.append(f'{path}: needs at least one of {alt_keys}')
        elif key == '_oneOf2':
            found = False
            for alt in spec:
                alt_key = list(alt.keys())[0]
                if alt_key in game and game[alt_key]:
                    found = True
                    break
            if not found:
                alt_keys = [list(a.keys())[0] for a in spec]
                errors.append(f'{path}: needs at least one of {alt_keys}')
        else:
            errors.extend(check_field(game, key, spec, path))

    return errors


def main():
    total_errors = 0
    total_warnings = 0
    total_games_checked = 0
    gap_types = set(SHAPES.keys())

    for dest_num in range(1, 59):
        filename = f'dest{dest_num}.json'
        filepath = os.path.join(BASE, filename)
        if not os.path.exists(filepath):
            print(f'WARNING: {filename} not found')
            total_warnings += 1
            continue

        with open(filepath, 'r', encoding='utf-8') as f:
            try:
                data = json.load(f)
            except json.JSONDecodeError as e:
                print(f'ERROR: {filename} invalid JSON: {e}')
                total_errors += 1
                continue

        games = data.get('games', [])
        for i, game in enumerate(games):
            gtype = game.get('type', '')
            if gtype not in gap_types:
                continue

            total_games_checked += 1
            path = f'{filename}:games[{i}] ({gtype})'
            errors = validate_game(game, gtype, path)
            for err in errors:
                print(f'ERROR: {err}')
                total_errors += 1

    print(f'\n--- Content Shape Validation ---')
    print(f'Destinations scanned: 58')
    print(f'CEFR-gap games checked: {total_games_checked}')
    print(f'Errors: {total_errors}')
    print(f'Warnings: {total_warnings}')

    if total_errors > 0:
        sys.exit(1)
    else:
        print('All shapes valid.')
        sys.exit(0)


if __name__ == '__main__':
    main()
