#!/usr/bin/env python3
"""
author-cefr-gap-games.py
Generates narrative-anchored games for the 14 CEFR-gap game types
and injects them into dest{N}.json files.

Each game is a scene in Yaguará's story. The mechanic serves the narrative.

Usage:
    python3 generator/author-cefr-gap-games.py [--dry-run]
"""

import json
import os
import sys
import random
import copy

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')
MAX_GAMES_PER_DEST = 38  # ceiling (some already have 36)

# CEFR level ordering
CEFR_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']

# Destination → CEFR mapping
DEST_CEFR = {}
for d in range(1, 13):
    DEST_CEFR[d] = 'A1'
for d in range(13, 19):
    DEST_CEFR[d] = 'A2'
for d in range(19, 29):
    DEST_CEFR[d] = 'B1'
for d in range(29, 39):
    DEST_CEFR[d] = 'B2'
for d in range(39, 49):
    DEST_CEFR[d] = 'C1'
for d in range(49, 59):
    DEST_CEFR[d] = 'C2'

# Which types apply at which CEFR levels
TYPE_LEVELS = {
    'par_minimo':     ['A1', 'A2', 'B1'],
    'dictogloss':     ['B1', 'B2', 'C1', 'C2'],
    'corrector':      ['A2', 'B1', 'B2', 'C1', 'C2'],
    'brecha':         ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'],
    'resumen':        ['B1', 'B2', 'C1', 'C2'],
    'registro':       ['B1', 'B2', 'C1', 'C2'],
    'debate':         ['B2', 'C1', 'C2'],
    'descripcion':    ['A1', 'A2', 'B1', 'B2'],
    'ritmo':          ['A1', 'A2', 'B1'],
    'cronometro':     ['A2', 'B1', 'B2', 'C1', 'C2'],
    'portafolio':     ['B1', 'B2', 'C1', 'C2'],
    'autoevaluacion': ['A2', 'B1', 'B2', 'C1', 'C2'],
    'negociacion':    ['B1', 'B2', 'C1', 'C2'],
    'transformador':  ['B1', 'B2', 'C1', 'C2'],
}

# ──────────────────────────────────────────────
# MINIMAL PAIR DATA (par_minimo)
# ──────────────────────────────────────────────
MINIMAL_PAIRS = {
    'A1': [
        {'wordA': 'pero', 'wordB': 'perro', 'correct': 'B', 'hint': 'Escucha la erre'},
        {'wordA': 'caro', 'wordB': 'carro', 'correct': 'A', 'hint': 'Una erre suave'},
        {'wordA': 'casa', 'wordB': 'caza', 'correct': 'A', 'hint': 'Escucha la ese'},
        {'wordA': 'peso', 'wordB': 'beso', 'correct': 'B', 'hint': 'Labios juntos'},
        {'wordA': 'pata', 'wordB': 'bata', 'correct': 'A', 'hint': 'Sonido fuerte al inicio'},
        {'wordA': 'mala', 'wordB': 'malla', 'correct': 'B', 'hint': 'Escucha la elle'},
    ],
    'A2': [
        {'wordA': 'hola', 'wordB': 'ola', 'correct': 'A', 'hint': 'Sonido idéntico'},
        {'wordA': 'tubo', 'wordB': 'tuvo', 'correct': 'B', 'hint': 'b/v en español suenan igual'},
        {'wordA': 'siento', 'wordB': 'ciento', 'correct': 'A', 'hint': 'Escucha la primera letra'},
        {'wordA': 'vaca', 'wordB': 'baca', 'correct': 'A', 'hint': 'Mismo sonido, diferente significado'},
        {'wordA': 'hecho', 'wordB': 'echo', 'correct': 'B', 'hint': 'La hache no suena'},
        {'wordA': 'cerro', 'wordB': 'cero', 'correct': 'A', 'hint': 'Doble erre vs simple'},
    ],
    'B1': [
        {'wordA': 'calle', 'wordB': 'caye', 'correct': 'A', 'hint': 'Yeísmo: ll/y'},
        {'wordA': 'haya', 'wordB': 'halla', 'correct': 'B', 'hint': 'Subjuntivo vs encontrar'},
        {'wordA': 'vello', 'wordB': 'bello', 'correct': 'A', 'hint': 'Pelo vs hermoso'},
        {'wordA': 'coser', 'wordB': 'cocer', 'correct': 'B', 'hint': 'Con aguja vs con fuego'},
        {'wordA': 'asar', 'wordB': 'azar', 'correct': 'A', 'hint': 'Cocinar vs suerte'},
        {'wordA': 'cima', 'wordB': 'sima', 'correct': 'A', 'hint': 'Arriba vs abajo'},
    ],
}

# ──────────────────────────────────────────────
# RITMO DATA (stress patterns)
# ──────────────────────────────────────────────
RITMO_WORDS = {
    'A1': [
        {'word': 'mamá', 'syllables': ['ma', 'má'], 'stressIndex': 1, 'pattern': 'aguda'},
        {'word': 'casa', 'syllables': ['ca', 'sa'], 'stressIndex': 0, 'pattern': 'llana'},
        {'word': 'agua', 'syllables': ['a', 'gua'], 'stressIndex': 0, 'pattern': 'llana'},
        {'word': 'amigo', 'syllables': ['a', 'mi', 'go'], 'stressIndex': 1, 'pattern': 'llana'},
        {'word': 'feliz', 'syllables': ['fe', 'liz'], 'stressIndex': 1, 'pattern': 'aguda'},
        {'word': 'árbol', 'syllables': ['ár', 'bol'], 'stressIndex': 0, 'pattern': 'llana'},
    ],
    'A2': [
        {'word': 'médico', 'syllables': ['mé', 'di', 'co'], 'stressIndex': 0, 'pattern': 'esdrújula'},
        {'word': 'teléfono', 'syllables': ['te', 'lé', 'fo', 'no'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'corazón', 'syllables': ['co', 'ra', 'zón'], 'stressIndex': 2, 'pattern': 'aguda'},
        {'word': 'difícil', 'syllables': ['di', 'fí', 'cil'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'canción', 'syllables': ['can', 'ción'], 'stressIndex': 1, 'pattern': 'aguda'},
        {'word': 'pájaro', 'syllables': ['pá', 'ja', 'ro'], 'stressIndex': 0, 'pattern': 'esdrújula'},
    ],
    'B1': [
        {'word': 'exámenes', 'syllables': ['e', 'xá', 'me', 'nes'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'kilómetro', 'syllables': ['ki', 'ló', 'me', 'tro'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'atmósfera', 'syllables': ['at', 'mós', 'fe', 'ra'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'periódico', 'syllables': ['pe', 'rió', 'di', 'co'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'político', 'syllables': ['po', 'lí', 'ti', 'co'], 'stressIndex': 1, 'pattern': 'esdrújula'},
        {'word': 'tradición', 'syllables': ['tra', 'di', 'ción'], 'stressIndex': 2, 'pattern': 'aguda'},
    ],
}

# ──────────────────────────────────────────────
# CORRECTOR passages by CEFR (with errors)
# ──────────────────────────────────────────────
CORRECTOR_DATA = {
    'A2': {
        'passage': 'Yaguará camina por el bosque. Ella es un jaguar muy fuerte. Ella tiene quatro patas grandes. La selva es su casa y ella conose todos los caminos.',
        'errors': [
            {'position': 0, 'wrong': 'quatro', 'correct': 'cuatro', 'type': 'spelling'},
            {'position': 1, 'wrong': 'conose', 'correct': 'conoce', 'type': 'spelling'},
        ]
    },
    'B1': {
        'passage': 'Don Próspero llegó al pueblo con su portafolio. Él quiere construir un camino que va a travéz del bosque. Los líderes de la aldea no están seguros. Candelaria piensa que él no dice toda la verdad. "Nosotros tenemos que protejer nuestro hogar", dice ella.',
        'errors': [
            {'position': 0, 'wrong': 'travéz', 'correct': 'través', 'type': 'spelling'},
            {'position': 1, 'wrong': 'protejer', 'correct': 'proteger', 'type': 'spelling'},
        ]
    },
    'B2': {
        'passage': 'La comunidad ha decidido reunirse para discutir la propuesta. Don Próspero insiste en que el desarroyo económico es necesario, pero Candelaria argumenta que el bosque es insustituíble. El debate revela tensiones profundas entre tradisión y progreso.',
        'errors': [
            {'position': 0, 'wrong': 'desarroyo', 'correct': 'desarrollo', 'type': 'spelling'},
            {'position': 1, 'wrong': 'insustituíble', 'correct': 'insustituible', 'type': 'accent'},
            {'position': 2, 'wrong': 'tradisión', 'correct': 'tradición', 'type': 'spelling'},
        ]
    },
    'C1': {
        'passage': 'La dialéctica entre conservación y desarrollo constituye un dilema que trasciende las fronteras locales. Don Próspero, quien representa la modernización, no consive un futuro sin infraestructura. Sin embargo, Doña Asunción sostiene que la memoria colectiva del bosque es imprescindible para la identidad comunitaria. La resolusión de este conflicto requiere una comprensión holística.',
        'errors': [
            {'position': 0, 'wrong': 'consive', 'correct': 'concibe', 'type': 'spelling'},
            {'position': 1, 'wrong': 'resolusión', 'correct': 'resolución', 'type': 'spelling'},
        ]
    },
    'C2': {
        'passage': 'La hermenéutica del paisaje revela que cada ecosistema es un palimpsesto de significados superpuestos. Yaguará, como entidad mitológica, transita entre el mundo de abajo y el mundo de arriba, personificando la dialectica entre lo telúrico y lo celeste. Esta dualidad no es una contradicsión sino una complementariedad que el pensamiento occidental difísilmente comprende.',
        'errors': [
            {'position': 0, 'wrong': 'dialectica', 'correct': 'dialéctica', 'type': 'accent'},
            {'position': 1, 'wrong': 'contradicsión', 'correct': 'contradicción', 'type': 'spelling'},
            {'position': 2, 'wrong': 'difísilmente', 'correct': 'difícilmente', 'type': 'accent'},
        ]
    },
}

# ──────────────────────────────────────────────
# AUTOEVALUACIÓN can-do statements by CEFR
# ──────────────────────────────────────────────
AUTOEVAL_STATEMENTS = {
    'A2': [
        'Puedo presentarme y presentar a mi familia.',
        'Puedo describir mi casa con frases simples.',
        'Puedo pedir comida en un restaurante.',
        'Puedo entender instrucciones básicas.',
    ],
    'B1': [
        'Puedo contar una experiencia pasada.',
        'Puedo expresar planes para el futuro.',
        'Puedo dar mi opinión sobre temas conocidos.',
        'Puedo entender textos sobre temas familiares.',
    ],
    'B2': [
        'Puedo argumentar a favor y en contra de una posición.',
        'Puedo entender artículos sobre temas contemporáneos.',
        'Puedo escribir textos claros y detallados.',
        'Puedo interactuar con fluidez con hablantes nativos.',
    ],
    'C1': [
        'Puedo expresar ideas complejas con matices.',
        'Puedo comprender textos largos y exigentes.',
        'Puedo usar el lenguaje con flexibilidad en contextos sociales.',
        'Puedo producir textos bien estructurados sobre temas complejos.',
    ],
    'C2': [
        'Puedo resumir información de diversas fuentes orales y escritas.',
        'Puedo expresarme con precisión y sutileza en situaciones complejas.',
        'Puedo comprender sin esfuerzo prácticamente todo lo que leo o escucho.',
        'Puedo mediar entre hablantes de diferentes niveles lingüísticos.',
    ],
}


def load_dest(n):
    path = os.path.join(CONTENT_DIR, f'dest{n}.json')
    if not os.path.exists(path):
        return None
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def save_dest(n, data):
    path = os.path.join(CONTENT_DIR, f'dest{n}.json')
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write('\n')


def dest_cefr(n):
    return DEST_CEFR.get(n, 'A1')


def has_room(data):
    return len(data.get('games', [])) < MAX_GAMES_PER_DEST


def already_has_type(data, game_type):
    for g in data.get('games', []):
        if g.get('type') == game_type:
            return True
    return False


def get_characters(meta):
    chars = meta.get('characters', [])
    names = []
    for c in chars:
        name = c.replace('char_', '').replace('_', ' ').title()
        if name == 'Yaguara':
            name = 'Yaguará'
        elif name == 'Don Prospero':
            name = 'Don Próspero'
        elif name.startswith('Dona ') or name.startswith('Doña '):
            name = 'Doña Asunción'
        names.append(name)
    return names


def pick_pairs(level, count=5):
    pool = MINIMAL_PAIRS.get(level, MINIMAL_PAIRS['A1'])
    random.shuffle(pool)
    return pool[:count]


def pick_ritmo(level, count=5):
    pool = RITMO_WORDS.get(level, RITMO_WORDS['A1'])
    random.shuffle(pool)
    return pool[:count]


# ──────────────────────────────────────────────
# GAME GENERATORS
# ──────────────────────────────────────────────

def gen_par_minimo(n, meta, cefr):
    pairs = pick_pairs(cefr)
    return {
        'type': 'par_minimo',
        'label': 'Par mínimo',
        'instruction': 'Yaguará escucha dos sonidos en la selva. ¿Cuál es cuál?',
        'pairs': pairs
    }


def gen_dictogloss(n, meta, cefr):
    title = meta.get('title', '')
    closing = meta.get('closingQuestion', '')
    chars = get_characters(meta)
    speaker = chars[1] if len(chars) > 1 else 'Yaguará'

    texts = {
        'B1': f'{speaker} cuenta: "Llegamos al pueblo cuando el sol estaba alto. La gente nos miraba con curiosidad. Un niño se acercó y preguntó: ¿De dónde vienen?"',
        'B2': f'{speaker} recuerda: "Aquella noche, el río cambió de color. Los ancianos dijeron que era una señal. Nadie durmió hasta que la luna mostró su cara completa entre las nubes del Chocó."',
        'C1': f'{speaker} reflexiona: "La memoria del bosque no se mide en años sino en capas. Cada árbol guarda testimonios de generaciones que pasaron, lucharon y dejaron sus historias en la corteza y las raíces."',
        'C2': f'{speaker} medita: "La tensión entre lo que se dice y lo que se calla constituye el verdadero tejido de la comunicación humana. En cada silencio habita una palabra que no fue dicha, y en cada palabra pronunciada, un silencio que la precede y la sostiene."',
    }
    text = texts.get(cefr, texts['B1'])

    keywords_map = {
        'B1': ['pueblo', 'sol', 'gente', 'curiosidad', 'niño', 'vienen'],
        'B2': ['río', 'color', 'ancianos', 'señal', 'luna', 'nubes'],
        'C1': ['memoria', 'bosque', 'capas', 'árbol', 'generaciones', 'historias'],
        'C2': ['tensión', 'silencio', 'comunicación', 'palabra', 'tejido', 'pronunciada'],
    }
    keywords = keywords_map.get(cefr, keywords_map['B1'])

    display_times = {'B1': 6000, 'B2': 5000, 'C1': 4000, 'C2': 3500}

    return {
        'type': 'dictogloss',
        'label': 'Dictogloss',
        'instruction': f'{speaker} cuenta una historia una sola vez. ¿Puedes reconstruirla?',
        'text': text,
        'displayTime': display_times.get(cefr, 5000),
        'keywords': keywords,
        'minMatch': max(2, len(keywords) // 2)
    }


def gen_corrector(n, meta, cefr):
    cd = CORRECTOR_DATA.get(cefr)
    if not cd:
        return None
    chars = get_characters(meta)
    author = 'Don Próspero' if 'Don Próspero' in chars else (chars[0] if chars else 'Yaguará')

    return {
        'type': 'corrector',
        'label': 'Corrector',
        'instruction': f'{author} dejó este documento. Tiene errores. Encuéntralos y corrígelos.',
        'passage': cd['passage'],
        'errors': copy.deepcopy(cd['errors'])
    }


def gen_brecha(n, meta, cefr):
    title = meta.get('title', '')
    chars = get_characters(meta)

    if cefr == 'A1':
        return {
            'type': 'brecha',
            'label': 'Brecha',
            'instruction': 'Yaguará sabe algo. Tú sabes algo. Juntos tienen la verdad completa.',
            'cardA': {
                'text': 'Yaguará vive en la selva. Tiene cuatro patas. Es grande y fuerte.',
                'facts': ['vive en la selva', 'cuatro patas', 'grande y fuerte']
            },
            'cardB': {
                'text': 'Yaguará come peces del río. Duerme en los árboles. Es un jaguar.',
                'facts': ['come peces', 'duerme en árboles', 'es un jaguar']
            },
            'questions': [
                {'question': '¿Dónde vive Yaguará?', 'answer': 'en la selva', 'source': 'A',
                 'options': ['en la selva', 'en la ciudad', 'en el mar']},
                {'question': '¿Qué come Yaguará?', 'answer': 'peces del río', 'source': 'B',
                 'options': ['peces del río', 'frutas del árbol', 'pan']},
                {'question': '¿Qué animal es Yaguará?', 'answer': 'un jaguar', 'source': 'B',
                 'options': ['un jaguar', 'un pájaro', 'un pez']},
            ]
        }
    elif cefr == 'A2':
        return {
            'type': 'brecha',
            'label': 'Brecha',
            'instruction': 'Lee tu tarjeta y responde las preguntas.',
            'cardA': {
                'text': 'La familia de Candelaria vive cerca del río Atrato. Su mamá es pescadora. Cande tiene doce años.',
                'facts': ['río Atrato', 'mamá pescadora', 'doce años']
            },
            'cardB': {
                'text': 'Candelaria quiere ser maestra. Su papá trabaja en el campo. Ella tiene dos hermanos menores.',
                'facts': ['quiere ser maestra', 'papá en el campo', 'dos hermanos']
            },
            'questions': [
                {'question': '¿Dónde vive la familia de Candelaria?', 'answer': 'cerca del río Atrato', 'source': 'A',
                 'options': ['cerca del río Atrato', 'en la ciudad', 'en la montaña']},
                {'question': '¿Qué quiere ser Candelaria?', 'answer': 'maestra', 'source': 'B',
                 'options': ['maestra', 'pescadora', 'doctora']},
                {'question': '¿Cuántos hermanos tiene Candelaria?', 'answer': 'dos', 'source': 'B',
                 'options': ['dos', 'tres', 'uno']},
            ]
        }
    else:
        char_a = chars[0] if chars else 'Yaguará'
        char_b = chars[1] if len(chars) > 1 else 'Candelaria'
        return {
            'type': 'brecha',
            'label': 'Brecha',
            'instruction': f'{char_a} sabe algo. {char_b} sabe algo. Juntos tienen la verdad completa.',
            'cardA': {
                'text': f'{char_a} observa que el bosque está cambiando. Los pájaros cantan menos. El río baja más turbio que antes.',
                'facts': ['bosque cambiando', 'pájaros cantan menos', 'río turbio']
            },
            'cardB': {
                'text': f'{char_b} ha escuchado que una empresa quiere construir cerca del río. Los pescadores están preocupados. El alcalde no ha dicho nada.',
                'facts': ['empresa construir', 'pescadores preocupados', 'alcalde callado']
            },
            'questions': [
                {'question': '¿Qué observa sobre el río?', 'answer': 'baja más turbio', 'source': 'A',
                 'options': ['baja más turbio', 'está seco', 'tiene peces nuevos']},
                {'question': '¿Qué quiere hacer una empresa?', 'answer': 'construir cerca del río', 'source': 'B',
                 'options': ['construir cerca del río', 'plantar árboles', 'pescar']},
                {'question': '¿Cómo están los pescadores?', 'answer': 'preocupados', 'source': 'B',
                 'options': ['preocupados', 'contentos', 'indiferentes']},
            ]
        }


def gen_resumen(n, meta, cefr):
    chars = get_characters(meta)
    title = meta.get('title', '')

    passages = {
        'B1': 'Candelaria llevó a Yaguará al mercado del pueblo. Había frutas de todos los colores: mangos amarillos, guanábanas verdes, chontaduros naranjas. Una señora vendía arepas de choclo. El olor llenaba toda la plaza. Un niño pequeño cantaba mientras su abuela tejía una canasta. "Así es nuestro pueblo", dijo Candelaria con orgullo.',
        'B2': 'La asamblea duró tres horas. Don Próspero presentó mapas y cifras: el camino traería empleo, conexión con la ciudad, acceso a hospitales. Los ancianos escucharon en silencio. Cuando terminó, Doña Asunción habló: "Mis abuelos caminaron estas tierras sin caminos de cemento. El bosque nos da todo lo que necesitamos. ¿Qué nos dará ese camino que el bosque no pueda dar?" Nadie respondió.',
        'C1': 'El conflicto entre desarrollo y conservación no es exclusivo de esta comunidad. A lo largo de América Latina, pueblos indígenas y afrodescendientes enfrentan presiones similares. La diferencia aquí es que Yaguará, como símbolo del bosque, personifica la voz de un ecosistema que no puede hablar por sí mismo. La pregunta no es solo económica: es ontológica. ¿Tiene el bosque derechos? ¿Puede un jaguar ser testigo?',
        'C2': 'La cosmovisión que sustenta la relación entre los pueblos del Chocó y su entorno natural trasciende las categorías occidentales de "recurso natural" y "medio ambiente". Para estas comunidades, el bosque no es un objeto exterior sino un sujeto relacional: tiene memoria, tiene voz, tiene derechos que preceden a cualquier legislación humana. Yaguará, al transitar entre mundos, encarna precisamente esta ontología relacional que el pensamiento moderno comienza apenas a vislumbrar.',
    }

    key_sentences_map = {
        'B1': ['mercado con frutas de colores', 'señora vendía arepas', 'niño cantaba', 'orgullo de Candelaria'],
        'B2': ['Don Próspero presentó mapas', 'el camino traería empleo', 'Doña Asunción habló de los abuelos', 'nadie respondió'],
        'C1': ['conflicto entre desarrollo y conservación', 'Yaguará personifica la voz del bosque', 'la pregunta es ontológica'],
        'C2': ['cosmovisión trasciende categorías occidentales', 'bosque es sujeto relacional', 'ontología relacional'],
    }

    model_summaries = {
        'B1': 'Candelaria muestra el mercado del pueblo a Yaguará. El pueblo tiene vida, color y tradición.',
        'B2': 'Don Próspero propone un camino para el progreso. Doña Asunción defiende el bosque como fuente de todo lo necesario. La comunidad queda en silencio.',
        'C1': 'El conflicto local refleja un dilema continental. Yaguará simboliza la voz del bosque. La pregunta central es ontológica, no solo económica.',
        'C2': 'La cosmovisión del Chocó ve al bosque como sujeto con derechos. Yaguará encarna una ontología relacional que desafía el pensamiento occidental.',
    }

    passage = passages.get(cefr, passages['B1'])
    return {
        'type': 'resumen',
        'label': 'Resumen',
        'instruction': 'La abuela Ceiba habló por mucho tiempo. ¿Cuál es lo esencial?',
        'passage': passage,
        'keySentences': key_sentences_map.get(cefr, []),
        'modelSummary': model_summaries.get(cefr, ''),
        'maxWords': {'B1': 30, 'B2': 40, 'C1': 50, 'C2': 60}.get(cefr, 40)
    }


def gen_registro(n, meta, cefr):
    situations = {
        'B1': {
            'situation': 'Llueve mucho en el Chocó.',
            'sourceRegister': 'informal',
            'sourceText': '¡Está lloviendo un montón! No vamos a poder salir hoy.',
            'targetRegister': 'formal',
            'modelAnswer': 'Se reportan precipitaciones intensas en la región del Chocó. Se recomienda no salir.',
            'keywords': ['precipitaciones', 'región', 'recomienda'],
            'options': [
                'Se reportan precipitaciones intensas en la región del Chocó.',
                '¡Qué lluvia tan horrible! ¡No aguanto más!',
                'Llueve, llueve, llueve sin parar en mi corazón.',
            ]
        },
        'B2': {
            'situation': 'Don Próspero quiere construir un camino por el bosque.',
            'sourceRegister': 'coloquial',
            'sourceText': 'Ese señor quiere meter una carretera por la mitad del monte. Los del pueblo no están nada contentos.',
            'targetRegister': 'periodístico',
            'modelAnswer': 'Un empresario propone la construcción de una vía que atravesaría una zona boscosa protegida, generando descontento entre los habitantes locales.',
            'keywords': ['empresario', 'construcción', 'vía', 'zona', 'habitantes'],
            'options': []
        },
        'C1': {
            'situation': 'La comunidad defiende su territorio.',
            'sourceRegister': 'testimonio oral',
            'sourceText': 'Mi abuela siempre decía: "Esta tierra nos cuida. Nosotros cuidamos la tierra. Es un pacto." Yo creo lo mismo.',
            'targetRegister': 'académico',
            'modelAnswer': 'La relación entre la comunidad y el territorio se fundamenta en un principio de reciprocidad intergeneracional, donde la tierra es concebida como agente activo de un pacto ecológico.',
            'keywords': ['reciprocidad', 'intergeneracional', 'territorio', 'agente', 'pacto'],
            'options': []
        },
        'C2': {
            'situation': 'Yaguará transita entre mundos.',
            'sourceRegister': 'poético',
            'sourceText': 'Yaguará camina donde el agua se convierte en cielo y el cielo se convierte en raíz. Sus patas tocan lo que no tiene nombre.',
            'targetRegister': 'científico',
            'modelAnswer': 'El jaguar (Panthera onca) habita ecosistemas que funcionan como zonas de transición ecológica, conectando hábitats acuáticos, terrestres y de dosel arbóreo.',
            'keywords': ['jaguar', 'Panthera', 'ecosistemas', 'transición', 'hábitats'],
            'options': []
        },
    }
    s = situations.get(cefr)
    if not s:
        return None
    return {
        'type': 'registro',
        'label': 'Registro',
        'instruction': 'La misma realidad, diferente voz. Cambia el registro.',
        **s
    }


def gen_debate(n, meta, cefr):
    chars = get_characters(meta)

    debates = {
        'B2': {
            'proposition': 'El camino de Don Próspero traerá prosperidad al pueblo.',
            'rounds': [
                {
                    'prompt': 'Elige el mejor argumento A FAVOR:',
                    'options': [
                        'El camino conectará el pueblo con hospitales y escuelas de la ciudad.',
                        'Don Próspero es muy simpático y habla bonito.',
                        'Los caminos siempre son buenos porque son modernos.',
                    ],
                    'correctIndex': 0
                },
                {
                    'prompt': 'Elige el mejor argumento EN CONTRA:',
                    'options': [
                        'No me gusta Don Próspero.',
                        'La construcción destruiría ecosistemas que sustentan la alimentación y el agua de la comunidad.',
                        'Los caminos son feos.',
                    ],
                    'correctIndex': 1
                },
                {
                    'prompt': 'Elige la mejor CONCLUSIÓN:',
                    'options': [
                        'Hay que destruir todo y empezar de cero.',
                        'Es necesario evaluar si los beneficios económicos compensan los costos ambientales y culturales.',
                        'Da igual, nadie puede cambiar nada.',
                    ],
                    'correctIndex': 1
                },
            ]
        },
        'C1': {
            'proposition': 'La preservación cultural debe tener prioridad sobre el desarrollo económico.',
            'rounds': [
                {
                    'prompt': 'Argumento más sólido A FAVOR:',
                    'options': [
                        'La identidad cultural es un derecho fundamental reconocido por organismos internacionales, y su pérdida es irreversible.',
                        'La cultura es bonita y hay que conservarla.',
                        'El dinero no importa nunca.',
                    ],
                    'correctIndex': 0
                },
                {
                    'prompt': 'Contraargumento más fuerte:',
                    'options': [
                        'Las culturas cambian naturalmente, y el desarrollo puede ser un catalizador de evolución cultural, no necesariamente de destrucción.',
                        'El progreso es inevitable.',
                        'Los pueblos necesitan dinero para vivir y punto.',
                    ],
                    'correctIndex': 0
                },
                {
                    'prompt': 'Síntesis más equilibrada:',
                    'options': [
                        'Es imposible resolver este conflicto.',
                        'El desarrollo debe diseñarse en diálogo con la comunidad, integrando saberes locales en la planificación para que el progreso fortalezca en vez de erosionar la identidad cultural.',
                        'Hay que elegir uno u otro, no hay punto medio.',
                    ],
                    'correctIndex': 1
                },
            ]
        },
        'C2': {
            'proposition': 'El concepto de "naturaleza" como entidad separada del ser humano es una construcción colonial.',
            'rounds': [
                {
                    'prompt': 'Argumento filosófico más riguroso A FAVOR:',
                    'options': [
                        'Las cosmovisiones indígenas no distinguen entre naturaleza y cultura; esta separación surge del dualismo cartesiano y se exportó a través de la colonización como paradigma universal.',
                        'Los indígenas siempre vivieron en la naturaleza.',
                        'La colonización fue mala.',
                    ],
                    'correctIndex': 0
                },
                {
                    'prompt': 'Mejor contraargumento epistemológico:',
                    'options': [
                        'La distinción naturaleza-cultura ha permitido avances científicos reales; la cuestión no es si es "colonial" sino si es operativa para resolver los problemas ecológicos actuales.',
                        'La ciencia siempre tiene razón.',
                        'No importa de dónde viene la idea, solo importa si funciona.',
                    ],
                    'correctIndex': 0
                },
            ]
        },
    }
    d = debates.get(cefr)
    if not d:
        return None
    return {
        'type': 'debate',
        'label': 'Debate',
        'instruction': 'El tribunal del río. Argumenta tu posición.',
        **d
    }


def gen_descripcion(n, meta, cefr):
    title = meta.get('title', '')
    chars = get_characters(meta)

    if cefr == 'A1':
        return {
            'type': 'descripcion',
            'label': 'Descripción',
            'instruction': 'Yaguará llega a un lugar nuevo. ¿Qué ves?',
            'scene': 'Un río grande. Árboles muy altos. El agua es azul y verde. Hay un pájaro rojo en un árbol. El sol es fuerte.',
            'promptQuestion': '¿Cuál es la mejor descripción?',
            'vocabulary': ['río', 'árboles', 'agua', 'pájaro', 'sol'],
            'modelAnswer': 'Hay un río grande con árboles altos. El agua es azul.',
            'options': [
                'Hay un río grande con árboles altos y un pájaro rojo.',
                'La ciudad tiene muchos edificios.',
                'Hace frío y hay nieve.',
            ]
        }
    elif cefr == 'A2':
        return {
            'type': 'descripcion',
            'label': 'Descripción',
            'instruction': 'Describe lo que ves.',
            'scene': 'Un mercado pequeño junto al río. Hay mujeres con canastas de frutas. Un hombre toca una guitarra. Los niños juegan con un perro. Huele a café y a pan recién hecho.',
            'promptQuestion': '¿Qué descripción es correcta?',
            'vocabulary': ['mercado', 'frutas', 'guitarra', 'niños', 'café'],
            'modelAnswer': 'En el mercado hay mujeres con frutas, un hombre toca guitarra y los niños juegan.',
            'options': [
                'En el mercado hay mujeres con frutas y un hombre toca guitarra.',
                'El mercado está vacío y silencioso.',
                'Hay un supermercado grande con muchos productos.',
            ]
        }
    elif cefr == 'B1':
        return {
            'type': 'descripcion',
            'label': 'Descripción',
            'instruction': 'Yaguará llega a un lugar nuevo. Describe lo que ves con tus propias palabras.',
            'scene': 'La aldea aparece entre la niebla de la mañana. Las casas están hechas de madera y palma. El humo de las cocinas sube lentamente. Una mujer lava ropa en el río mientras canta. Dos ancianos conversan bajo un árbol de ceiba.',
            'promptQuestion': 'Describe esta escena usando al menos 3 de las palabras sugeridas.',
            'vocabulary': ['aldea', 'niebla', 'madera', 'humo', 'río', 'ceiba'],
            'modelAnswer': 'Una aldea de casas de madera aparece entre la niebla. Se ve humo de cocinas y una mujer que lava en el río.',
            'options': []
        }
    else:  # B2
        return {
            'type': 'descripcion',
            'label': 'Descripción',
            'instruction': 'Observa y describe. Yaguará necesita tus ojos.',
            'scene': 'La selva al amanecer es un teatro de sonidos y sombras. Las copas de los árboles filtran una luz verdosa que dibuja patrones en el suelo húmedo. Una bandada de loros cruza el cielo como una pincelada esmeralda. El aire huele a tierra mojada, a musgo, a vida que se renueva.',
            'promptQuestion': 'Describe esta escena con detalles sensoriales (vista, sonido, olor).',
            'vocabulary': ['amanecer', 'sombras', 'luz', 'loros', 'tierra', 'musgo', 'renovar'],
            'modelAnswer': 'Al amanecer, la selva se llena de luz verdosa. Los loros cruzan el cielo y el aire huele a tierra mojada y musgo.',
            'options': []
        }


def gen_ritmo(n, meta, cefr):
    words = pick_ritmo(cefr)
    return {
        'type': 'ritmo',
        'label': 'Ritmo',
        'instruction': 'La selva tiene un ritmo. Las palabras también. ¿Dónde cae el golpe?',
        'words': words
    }


def gen_cronometro(n, meta, cefr):
    chars = get_characters(meta)

    questions_map = {
        'A2': [
            {'prompt': '¿Cómo se dice "house" en español?', 'answer': 'casa', 'options': ['casa', 'cosa', 'caso'], 'timeLimit': 8},
            {'prompt': '¿Cuál es el plural de "árbol"?', 'answer': 'árboles', 'options': ['árboles', 'árbols', 'arbolés'], 'timeLimit': 8},
            {'prompt': 'Yo ___ (ser) estudiante.', 'answer': 'soy', 'options': ['soy', 'es', 'eres'], 'timeLimit': 7},
            {'prompt': '¿Cuántas patas tiene un jaguar?', 'answer': 'cuatro', 'options': ['cuatro', 'dos', 'seis'], 'timeLimit': 6},
        ],
        'B1': [
            {'prompt': 'Ayer yo ___ (ir) al mercado.', 'answer': 'fui', 'options': ['fui', 'iba', 'iré'], 'timeLimit': 7},
            {'prompt': 'Si llueve, no ___ (salir, nosotros).', 'answer': 'saldremos', 'options': ['saldremos', 'salimos', 'saldríamos'], 'timeLimit': 8},
            {'prompt': '¿Sinónimo de "grande"?', 'answer': 'enorme', 'options': ['enorme', 'pequeño', 'rápido'], 'timeLimit': 6},
            {'prompt': 'Candelaria ___ (querer) ser maestra.', 'answer': 'quiere', 'options': ['quiere', 'quería', 'querrá'], 'timeLimit': 7},
        ],
        'B2': [
            {'prompt': 'Si yo ___ (tener) tiempo, viajaría.', 'answer': 'tuviera', 'options': ['tuviera', 'tengo', 'tendré'], 'timeLimit': 7},
            {'prompt': '¿Antónimo de "progreso"?', 'answer': 'retroceso', 'options': ['retroceso', 'avance', 'desarrollo'], 'timeLimit': 6},
            {'prompt': 'Es importante que tú ___ (saber) la verdad.', 'answer': 'sepas', 'options': ['sepas', 'sabes', 'supieras'], 'timeLimit': 7},
            {'prompt': 'Don Próspero insiste ___ que el camino es necesario.', 'answer': 'en', 'options': ['en', 'de', 'a'], 'timeLimit': 6},
        ],
        'C1': [
            {'prompt': 'No creo que ___ (haber) una solución fácil.', 'answer': 'haya', 'options': ['haya', 'hay', 'habría'], 'timeLimit': 6},
            {'prompt': '¿Qué figura retórica es "el bosque habla"?', 'answer': 'personificación', 'options': ['personificación', 'metáfora', 'hipérbole'], 'timeLimit': 7},
            {'prompt': 'Ojalá que la comunidad ___ (poder) decidir.', 'answer': 'pueda', 'options': ['pueda', 'puede', 'pudiera'], 'timeLimit': 6},
        ],
        'C2': [
            {'prompt': '¿Qué término describe la relación bosque-comunidad?', 'answer': 'simbiosis', 'options': ['simbiosis', 'parasitismo', 'competencia'], 'timeLimit': 6},
            {'prompt': 'De ___ que llegó, todo cambió. (conjunción temporal)', 'answer': 'desde', 'options': ['desde', 'cuando', 'porque'], 'timeLimit': 5},
            {'prompt': 'La comunidad, ___ territorio estaba amenazado, resistió.', 'answer': 'cuyo', 'options': ['cuyo', 'que', 'cual'], 'timeLimit': 5},
        ],
    }
    questions = questions_map.get(cefr, questions_map['B1'])

    return {
        'type': 'cronometro',
        'label': 'Cronómetro',
        'instruction': 'El río sube. Responde antes de que el agua llegue.',
        'questions': questions,
        'defaultTime': 8
    }


def gen_portafolio(n, meta, cefr):
    title = meta.get('title', '')
    closing = meta.get('closingQuestion', '')

    prompts = {
        'B1': f'Yaguará se detiene y piensa. Estás en «{title}». {closing}',
        'B2': f'Ha sido un viaje intenso hasta «{title}». ¿Qué has aprendido? ¿Qué te sorprendió?',
        'C1': f'Reflexiona sobre tu experiencia en «{title}». ¿Cómo ha cambiado tu comprensión del mundo de Yaguará?',
        'C2': f'En este punto del viaje — «{title}» — ¿qué conexiones ves entre la historia de Yaguará y tu propia experiencia con el lenguaje?',
    }

    guiding_map = {
        'B1': ['¿Qué palabras nuevas aprendiste?', '¿Qué fue difícil?', '¿Qué quieres practicar más?'],
        'B2': ['¿Qué ideas te parecieron interesantes?', '¿Cómo te sentiste durante los ejercicios?'],
        'C1': ['¿Qué matices del lenguaje descubriste?', '¿Cómo se relaciona este contenido con otros temas?'],
        'C2': ['¿Qué aspectos del lenguaje todavía te desafían?', '¿Cómo ha evolucionado tu relación con el español?'],
    }

    min_words = {'B1': 15, 'B2': 25, 'C1': 40, 'C2': 60}

    return {
        'type': 'portafolio',
        'label': 'Diario de viaje',
        'instruction': 'Yaguará se detiene y piensa. Escribe en tu diario de viaje.',
        'prompt': prompts.get(cefr, prompts['B1']),
        'guidingQuestions': guiding_map.get(cefr, []),
        'minWords': min_words.get(cefr, 15),
        'destination': f'dest{n}'
    }


def gen_autoevaluacion(n, meta, cefr):
    statements = AUTOEVAL_STATEMENTS.get(cefr)
    if not statements:
        return None
    return {
        'type': 'autoevaluacion',
        'label': 'Autoevaluación',
        'instruction': 'Yaguará mira su reflejo en el agua. ¿Qué sabes ahora?',
        'reflection': '¿Qué sabes ahora que no sabías antes?',
        'statements': [{'text': s} for s in statements],
        'destination': f'dest{n}'
    }


def gen_negociacion(n, meta, cefr):
    chars = get_characters(meta)

    negotiations = {
        'B1': {
            'positionA': {'speaker': 'Candelaria', 'text': 'El río es sagrado para mi comunidad. No podemos permitir que lo contaminen.'},
            'positionB': {'speaker': 'Don Próspero', 'text': 'El camino traerá trabajo y escuelas. El pueblo necesita progresar.'},
            'mediationOptions': [
                'El camino se puede construir, pero debe pasar lejos del río y la comunidad debe participar en las decisiones.',
                'Hay que destruir el río para construir el camino.',
                'No se puede construir nada nunca.',
                'Don Próspero tiene toda la razón.',
            ],
            'correctIndex': 0,
            'modelMediation': 'Se puede buscar una ruta alternativa que respete el río y que beneficie al pueblo.'
        },
        'B2': {
            'positionA': {'speaker': 'Los ancianos', 'text': 'Nuestros abuelos nos enseñaron a cuidar el bosque. Si lo destruimos, perdemos nuestra identidad.'},
            'positionB': {'speaker': 'Los jóvenes', 'text': 'Necesitamos internet, hospital y escuela. No podemos vivir como hace cien años.'},
            'mediationOptions': [
                'Se pueden crear proyectos de ecoturismo que conecten al pueblo con el mundo sin destruir el bosque, usando la tradición como valor económico.',
                'Los ancianos no entienden el mundo moderno.',
                'Los jóvenes deben irse a la ciudad.',
                'No hay solución posible.',
            ],
            'correctIndex': 0,
            'modelMediation': 'El ecoturismo y la tecnología pueden llegar al pueblo respetando las tradiciones.'
        },
        'C1': {
            'positionA': {'speaker': 'Ambientalistas', 'text': 'El bosque del Chocó es uno de los puntos de mayor biodiversidad del planeta. Su destrucción tendría consecuencias irreversibles.'},
            'positionB': {'speaker': 'Gobierno regional', 'text': 'La inversión en infraestructura reducirá la pobreza. Los indicadores de desarrollo humano de la región son los más bajos del país.'},
            'mediationOptions': [
                'Un plan de desarrollo sostenible que combine corredores ecológicos protegidos con infraestructura de bajo impacto podría atender ambas necesidades.',
                'El medio ambiente no importa cuando hay pobreza.',
                'Hay que prohibir toda actividad económica en la región.',
            ],
            'correctIndex': 0,
            'modelMediation': 'El desarrollo sostenible integra protección ambiental con mejora de indicadores sociales.'
        },
        'C2': {
            'positionA': {'speaker': 'Comunidad indígena', 'text': 'La tierra no nos pertenece; nosotros pertenecemos a la tierra. No es un recurso: es un ser vivo con el que tenemos un pacto ancestral.'},
            'positionB': {'speaker': 'Corporación de desarrollo', 'text': 'El bienestar objetivo de los habitantes — salud, educación, esperanza de vida — requiere integración económica. La románticización de la pobreza es irresponsable.'},
            'mediationOptions': [
                'Un marco de gobernanza intercultural que reconozca la personalidad jurídica de los ecosistemas, como lo han hecho Nueva Zelanda y Ecuador, permitiría avanzar en desarrollo sin violentar la cosmovisión comunitaria.',
                'Uno de los dos tiene razón y el otro está equivocado.',
                'Este conflicto no tiene solución posible.',
            ],
            'correctIndex': 0,
            'modelMediation': 'Los derechos de la naturaleza y los derechos humanos pueden coexistir en marcos jurídicos interculturales.'
        },
    }
    neg = negotiations.get(cefr)
    if not neg:
        return None
    return {
        'type': 'negociacion',
        'label': 'Negociación',
        'instruction': 'Dos voces. Un puente. Encuentra las palabras que ambos puedan aceptar.',
        **neg
    }


def gen_transformador(n, meta, cefr):
    transformations = {
        'B1': {
            'sourceText': 'Candelaria le dijo a Yaguará: "Mañana vamos al mercado. Voy a comprar frutas para mi mamá. ¿Quieres venir?"',
            'sourceGenre': 'diálogo',
            'targetGenre': 'carta',
            'modelAnswer': 'Querida Yaguará: Te escribo para invitarte al mercado mañana. Voy a comprar frutas para mi mamá. ¿Puedes acompañarme? Con cariño, Candelaria.',
            'keywords': ['querida', 'escribo', 'invitarte', 'cariño'],
            'options': [
                'Querida Yaguará: Te escribo para invitarte al mercado mañana. Voy a comprar frutas para mi mamá. Con cariño, Candelaria.',
                'Mañana mercado. Frutas. ¿Vienes?',
                'ATENCIÓN: Se informa que mañana habrá compras.',
            ]
        },
        'B2': {
            'sourceText': 'El río Atrato baja cargado de historias. Cada gota lleva el eco de una voz que ya no está. Las piedras recuerdan pasos de abuelos que caminaron descalzos buscando la orilla de un sueño.',
            'sourceGenre': 'poema',
            'targetGenre': 'informe geográfico',
            'modelAnswer': 'El río Atrato es un sistema fluvial de la región del Chocó, con un caudal significativo y una importancia cultural e histórica para las comunidades ribereñas.',
            'keywords': ['río', 'sistema', 'fluvial', 'región', 'comunidades'],
            'options': []
        },
        'C1': {
            'sourceText': 'Don Próspero llegó al pueblo con un portafolio lleno de promesas. Habló de carreteras, de empleos, de un futuro brillante. Los ancianos lo escucharon en silencio. Cuando se fue, el viento olía a cemento.',
            'sourceGenre': 'narrativa literaria',
            'targetGenre': 'crónica periodística',
            'modelAnswer': 'Un empresario identificado como Don Próspero García presentó ayer ante la comunidad un proyecto vial que promete generación de empleo e infraestructura. La propuesta fue recibida con escepticismo por los líderes comunitarios.',
            'keywords': ['empresario', 'proyecto', 'vial', 'empleo', 'escepticismo', 'comunitarios'],
            'options': []
        },
        'C2': {
            'sourceText': 'La conferencia del Dr. Ramírez estableció que los ecosistemas tropicales húmedos, particularmente los del Chocó biogeográfico, presentan tasas de endemismo superiores al 25%, lo cual justifica su designación como áreas de protección prioritaria.',
            'sourceGenre': 'texto académico',
            'targetGenre': 'relato oral de un anciano',
            'modelAnswer': 'Mira, hijo: este bosque es único en el mundo. Los doctores de la ciudad vinieron y dijeron que muchos de los animales y plantas que viven aquí no existen en ningún otro lugar. Por eso hay que cuidarlo. Lo que tenemos es sagrado.',
            'keywords': ['bosque', 'único', 'animales', 'plantas', 'cuidar', 'sagrado'],
            'options': []
        },
    }
    t = transformations.get(cefr)
    if not t:
        return None
    return {
        'type': 'transformador',
        'label': 'Transformador',
        'instruction': 'La misma historia tiene muchas formas. Cámbiala.',
        **t
    }


# ──────────────────────────────────────────────
# GENERATOR REGISTRY
# ──────────────────────────────────────────────
GENERATORS = {
    'par_minimo': gen_par_minimo,
    'dictogloss': gen_dictogloss,
    'corrector': gen_corrector,
    'brecha': gen_brecha,
    'resumen': gen_resumen,
    'registro': gen_registro,
    'debate': gen_debate,
    'descripcion': gen_descripcion,
    'ritmo': gen_ritmo,
    'cronometro': gen_cronometro,
    'portafolio': gen_portafolio,
    'autoevaluacion': gen_autoevaluacion,
    'negociacion': gen_negociacion,
    'transformador': gen_transformador,
}

# Which destination in each CEFR band gets each type
# (spread across destinations, not all on one)
def get_target_dests_for_type(game_type, levels):
    """Return list of (dest_number, cefr) where this type should be placed.
    Places in ALL destinations at applicable levels (has_room check happens later)."""
    targets = []
    for level in levels:
        dests_at_level = [d for d in range(1, 59) if DEST_CEFR.get(d) == level]
        for d in dests_at_level:
            targets.append((d, level))
    return targets


def main():
    dry_run = '--dry-run' in sys.argv
    random.seed(42)  # reproducible

    total_added = 0
    type_counts = {t: 0 for t in GENERATORS}

    # Round-robin: iterate per-destination, add one game of each applicable type
    # This prevents early types from filling dests before later types get a chance
    for dest_n in range(1, 59):
        data = load_dest(dest_n)
        if data is None:
            continue

        cefr = data.get('meta', {}).get('cefr', 'A1')
        meta = data.get('meta', {})

        for game_type, gen_fn in GENERATORS.items():
            if not has_room(data):
                break

            if cefr not in TYPE_LEVELS[game_type]:
                continue

            if already_has_type(data, game_type):
                continue

            game = gen_fn(dest_n, meta, cefr)
            if game is None:
                continue

            if 'games' not in data:
                data['games'] = []

            data['games'].append(game)
            total_added += 1
            type_counts[game_type] += 1

            action = '[DRY RUN] Would add' if dry_run else ''
            if action:
                print(f"  {action} {game_type} to dest{dest_n} (CEFR {cefr})")
            else:
                print(f"  Added {game_type} to dest{dest_n} (CEFR {cefr})")

        if not dry_run:
            save_dest(dest_n, data)

    print(f"\n{'=' * 50}")
    print(f"Total games added: {total_added}")
    print(f"\nBy type:")
    for t, c in sorted(type_counts.items()):
        print(f"  {t}: {c}")

    # Verification: check game counts
    print(f"\nDestination game counts:")
    over_limit = 0
    for d in range(1, 59):
        dd = load_dest(d)
        if dd:
            count = len(dd.get('games', []))
            if count > MAX_GAMES_PER_DEST:
                print(f"  ⚠ dest{d}: {count} games (OVER LIMIT)")
                over_limit += 1
    if over_limit == 0:
        print(f"  All destinations within {MAX_GAMES_PER_DEST}-game limit.")


if __name__ == '__main__':
    main()
