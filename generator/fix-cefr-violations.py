#!/usr/bin/env python3
"""
fix-cefr-violations.py
Patches all authored content (arrivals, departures, cronicas, escape rooms)
to strictly follow CEFR grammar progression.

Principle: Content at destination N may ONLY use grammar from destinations 1–(N-1).
The arrival narrative may expose the NEW grammar sparingly (bold/italic),
but escape rooms, departures, and cronica prompts must use only known grammar.

A1 (dest1-12): Present tense ONLY (ser, estar, tener, hay, regular -ar/-er/-ir)
  NO past tenses, NO subjunctive, NO "cuando" clauses, NO "para + infinitive"
  NO "donde"/"que" relative clauses, NO "querer/saber/poder + infinitive"
  NO reciprocal reflexives, NO impersonal "se"

A2 progression:
  dest13: reflexive verbs (student only knows A1 present)
  dest14: pretérito regular (now knows present + reflexives)
  dest15: modal+infinitive querer/poder/necesitar+inf (now + pretérito regular)
  dest16: weather, comparatives (now + modals)
  dest17: imperfecto (now + weather/comparatives)
  dest18: ir a + future (now + imperfecto)

B1 progression:
  dest19: pretérito vs imperfecto narrative (all A2 grammar, NO subjunctive)
  dest20: indirect speech (NO subjunctive)
  dest21: present subjunctive basic, connectors
  dest22-23: pret/imperf narrative, indirect speech advanced
  dest24: relative clauses + present perfect
  dest25: concessive (aunque), opinion expressions
  dest26: passive voice
  dest27-28: argumentation, integration
"""

import json
from pathlib import Path

CONTENT_DIR = Path('/home/babelfree.com/public_html/content')


# ============================================================
# FIXED ARRIVALS
# ============================================================
FIXED_ARRIVALS = {
    # --- A1: Present tense ONLY ---
    1: {
        'sections': [
            {'body': '<em>Viajero...</em> Abre los ojos. Estás en la selva amazónica. El río suena. Los árboles son muy grandes. El aire es húmedo.'},
            {'body': 'Soy <strong>Yaguará</strong>. Soy un jaguar. Camino entre los mundos. Camino contigo. Cada palabra es una <em>semilla</em>.'},
            {'body': '¿Cómo te llamas? Yo soy Yaguará. Y tú... ¿quién eres tú?'}
        ],
        'button': 'Entrar en la selva'
    },
    2: {
        'sections': [
            {'body': 'Mira a tu alrededor. El mundo tiene nombres. El <strong>árbol</strong>. El <strong>río</strong>. El <strong>cielo</strong>. El <strong>pájaro</strong>.'},
            {'body': 'Tú nombras una cosa. Esa cosa <em>brilla</em>. Sin nombre, la cosa no brilla. Las cosas sin nombre están oscuras.'},
            {'body': 'Nombra el mundo. Cada nombre es una luz.'}
        ],
        'button': 'Empezar a nombrar'
    },
    3: {
        'sections': [
            {'body': 'Yaguará mira el río. El río <strong>es</strong> grande. El agua <strong>es</strong> fría. El cielo <strong>es</strong> azul. El bosque <strong>es</strong> verde.'},
            {'body': 'Tú describes algo. Los colores son más fuertes. Sin palabras, los colores no son fuertes.'},
            {'body': '¿Cómo es tu mundo? Descríbelo. Las palabras dan color al mundo.'}
        ],
        'button': 'Describir el mundo'
    },
    4: {
        'sections': [
            {'body': 'Yaguará cuenta las cosas. Un árbol, dos pájaros, tres piedras en el río. <strong>Tengo</strong> tres años en este bosque.'},
            {'body': 'El tiempo pasa aquí. Los días son cortos. Algo come el tiempo. El Río Madre habla.'},
            {'body': '¿Cuántos días caminas? Contamos juntos.'}
        ],
        'button': 'Contar los días'
    },
    5: {
        'sections': [
            {'body': 'A Yaguará <strong>le gusta</strong> el agua fría. <strong>No le gusta</strong> el silencio. El silencio no es normal aquí.'},
            {'body': 'El Río Madre dice: <em>"El silencio es malo. Sin sonido, las cosas no están."</em> Yaguará escucha. Tú también escucha.'},
            {'body': '¿Qué te gusta de este lugar? ¿Qué no te gusta? Tu voz es importante.'}
        ],
        'button': 'Expresar'
    },
    6: {
        'sections': [
            {'body': 'Cada día, Yaguará <strong>camina</strong> por la selva. <strong>Come</strong> fruta. <strong>Bebe</strong> agua del río. <strong>Duerme</strong> en un árbol grande.'},
            {'body': 'Mamá Jaguar aparece entre los árboles. Dice: <em>"Hija, camina con cuidado. El mundo cambia."</em> Y no está más.'},
            {'body': 'Un día completo. ¿Qué haces tú cada día?'}
        ],
        'button': 'Vivir un día'
    },
    7: {
        'sections': [
            {'body': 'Hoy hay comida. Yaguará encuentra frutas, pescado del río, agua fresca. Comer juntos es importante.'},
            {'body': '<strong>Tengo hambre.</strong> <strong>Tengo sed.</strong> Estas palabras son necesarias. El cuerpo habla. Las palabras responden.'},
            {'body': 'En la selva, compartir comida es compartir vida. ¿Qué compartes hoy?'}
        ],
        'button': 'Comer juntos'
    },
    8: {
        'sections': [
            {'body': 'El río suena más fuerte hoy. Junto al río hay una familia. La <strong>madre</strong>. El <strong>padre</strong>. La <strong>hermana</strong>. El <strong>hermano</strong>.'},
            {'body': 'Yaguará los mira. Recuerda a Mamá Jaguar. La familia es el primer mundo. Las primeras palabras son nombres de familia.'},
            {'body': '¿Quién camina contigo? ¿Quién es tu familia?'}
        ],
        'button': 'Conocer a la familia'
    },
    9: {
        'sections': [
            {'body': 'Una casa sobre el río. Tiene techo de hojas y paredes de madera. Es pequeña pero <strong>está</strong> bien.'},
            {'body': 'Yaguará entra. La <strong>cocina</strong> está a la izquierda. La <strong>hamaca</strong> está al fondo. Todo tiene un lugar.'},
            {'body': 'Doña Asunción, una mujer mayor, vive aquí. Dice: <em>"Entra. Mi casa es tu casa."</em>'}
        ],
        'button': 'Entrar en la casa'
    },
    10: {
        'sections': [
            {'body': '¿Qué hora es? El sol dice: es temprano. Son las <strong>siete de la mañana</strong>. Enero. El primer mes del año.'},
            {'body': 'Yaguará aprende números nuevos. Treinta y dos, cincuenta, cien. Los números son como escalones.'},
            {'body': 'El tiempo tiene forma aquí. ¿Qué hora es en tu mundo?'}
        ],
        'button': 'Mirar el reloj'
    },
    11: {
        'sections': [
            {'body': 'Las palabras se mueven. <strong>Comer, beber, vivir, escribir, abrir.</strong> Los verbos son los motores del idioma.'},
            {'body': 'Yaguará <strong>va</strong> al río. <strong>Come</strong> una fruta. <strong>Vive</strong> en la selva. Cada verbo es una acción. Cada acción cambia el mundo.'},
            {'body': '¿Adónde vas? ¿Por qué? Las razones son importantes.'}
        ],
        'button': 'Conjugar el mundo'
    },
    12: {
        'sections': [
            {'body': 'Yaguará mira el reflejo en el río. ¿Quién es ella? Es colombiana — del corazón de la selva. Es fuerte. Es joven. Es curiosa.'},
            {'body': 'La primera espiral termina. Tú nombras el mundo. Tú cuentas los días. Tú dices quién eres.'},
            {'body': 'Pero algo es diferente. Los colores del bosque no son tan fuertes. Una voz dice: <em>"Camina. Es hora."</em>'}
        ],
        'button': 'Descubrir quién soy'
    },

    # --- A2: Respect grammar progression ---
    13: {
        # dest13 introduces REFLEXIVE VERBS. Student only knows A1 present.
        # Arrival exposes reflexives (bold), NOT preterite.
        'sections': [
            {'body': 'El sol sale. Yaguará <strong>se despierta</strong>. <strong>Se levanta</strong>. Mira el río. El agua está fría. <strong>Se baña</strong> en el río.'},
            {'body': 'Los verbos reflexivos son nuevos: <em>me, te, se, nos.</em> El verbo habla de ti mismo. <strong>Me llamo</strong> Yaguará. <strong>Me siento</strong> feliz. <strong>Me muevo</strong> por la selva.'},
            {'body': '¿Cómo es tu mañana? ¿A qué hora te levantas? Los verbos reflexivos cuentan tu rutina.'}
        ],
        'button': 'Empezar la rutina'
    },
    14: {
        # dest14 introduces PRETÉRITO REGULAR. Student knows present + reflexives.
        # Arrival exposes preterite (bold).
        'sections': [
            {'body': 'Candelaria aparece por primera vez. Es una niña de doce años. Tiene los ojos brillantes y una sonrisa grande.'},
            {'body': '— Yaguará — dice ella. — Yo <strong>vi</strong> algo ayer. ¿Quieres escuchar?'},
            {'body': 'Contar algo del pasado. El pasado tiene poder. Candelaria lo sabe. Ella es el puente entre los mundos.'}
        ],
        'button': 'Escuchar a Candelaria'
    },
    15: {
        # dest15 introduces MODAL+INFINITIVE. Student knows present + reflexives + pretérito.
        # Arrival exposes querer/poder/necesitar + inf (bold).
        'sections': [
            {'body': 'Yaguará tiene hambre. Yaguará <strong>necesita</strong> agua. Yaguará <strong>quiere</strong> descansar.'},
            {'body': '<strong>Necesitar</strong> y <strong>querer</strong> son diferentes. Lo necesario te mantiene viva. Lo deseado te da dirección.'},
            {'body': 'El camino tiene dos direcciones. ¿Qué necesitas? ¿Qué quieres encontrar?'}
        ],
        'button': 'Elegir el camino'
    },
    16: {
        # dest16 introduces WEATHER + COMPARATIVES. Student knows + modals.
        # NO imperfecto (dest17), NO future tense.
        'sections': [
            {'body': 'El cielo cambia. Hoy <strong>llueve</strong>. Ayer llovió. El sol no está.'},
            {'body': 'El clima del bosque es el humor del mundo. El sol es la felicidad. La lluvia es la tristeza. Pero la lluvia también es vida.'},
            {'body': 'Los animales se mueven. Los pájaros vuelan. Algo cambia. ¿Qué es diferente hoy?'}
        ],
        'button': 'Mirar el cielo'
    },
    17: {
        # dest17 introduces IMPERFECTO. Student knows + weather/comparatives.
        # Arrival exposes imperfecto (bold/italic).
        'sections': [
            {'body': 'Yaguará recuerda. El bosque <strong>era</strong> más verde. El río <strong>era</strong> más ancho. Los pájaros <strong>cantaban</strong> más fuerte.'},
            {'body': 'El imperfecto es la memoria. No un momento — cómo <em>eran</em> las cosas. Lo repetido. Lo habitual.'},
            {'body': '¿Cómo era antes? ¿Qué recuerdas del principio del viaje?'}
        ],
        'button': 'Recordar'
    },
    18: {
        # dest18 introduces IR A + FUTURE. Student knows + imperfecto.
        # NO present perfect (dest24).
        'sections': [
            {'body': 'El Mundo de Abajo termina. Tú nombraste el mundo. Contaste los días. Describiste los colores. Recordaste el pasado.'},
            {'body': 'Pero el camino sigue. Más allá de la selva hay pueblos, montañas, costas. El Mundo del Medio espera.'},
            {'body': 'Yaguará mira el borde del bosque. Mira hacia adelante. Lo desconocido es grande. Pero tú eres diferente ahora.'}
        ],
        'button': 'Cruzar el umbral'
    },

    # --- B1: Respect grammar progression ---
    20: {
        # dest20 introduces INDIRECT SPEECH. NO subjunctive (that's dest21).
        'sections': [
            {'body': 'La gente del pueblo cuenta historias. Historias de antes. Historias de ahora. Yaguará escucha todo.'},
            {'body': '— <em>Dicen que</em> el río está enfermo — cuenta una señora. — <em>Cuentan que</em> un hombre quiere construir un camino por la selva.'},
            {'body': 'El estilo indirecto llega como un eco: lo que alguien dijo, repetido por otra voz. Las historias tienen más de una verdad.'}
        ],
        'button': 'Escuchar las historias'
    },
    25: {
        # dest25 introduces CONCESSIVE (aunque) + OPINION.
        # NO conditional (dest29), NO imperfect subjunctive (B2).
        'sections': [
            {'body': 'Don Próspero aparece otra vez. Esta vez no habla de caminos. Habla de <em>progreso</em>. De <em>futuro</em>. <strong>Aunque</strong> dice que quiere ayudar, algo no está bien.'},
            {'body': 'La concesión es un puente entre dos ideas opuestas: <em>"Aunque conozco los riesgos, creo que es necesario actuar."</em> Dos verdades en una frase.'},
            {'body': 'Candelaria susurra: <em>"No le creas todo."</em>'}
        ],
        'button': 'Escuchar a Don Próspero'
    },
}

# ============================================================
# FIXED DEPARTURES
# ============================================================
FIXED_DEPARTURES = {
    # A1: Present tense only. No past, no cuando, no que-relative, no impersonal se.
    1:  'Cada nombre es una semilla. Ya tienes la primera.',
    2:  'El mundo tiene muchos nombres. Sigue buscando.',
    3:  'Los colores son fuertes con tus palabras. Mira siempre.',
    4:  'El tiempo pasa. Las palabras están aquí siempre.',
    5:  'Lo que te gusta dice quién eres. Es importante.',
    6:  'Un día completo. Mañana es un nuevo día.',
    7:  'Compartir es la primera forma de hablar.',
    8:  'La familia es el primer idioma.',
    9:  'Una casa es un lugar de palabras tranquilas.',
    10: 'Los números cuentan historias. Escúchalas.',
    11: 'Los verbos mueven el mundo. Tú también mueves el mundo.',
    12: 'Tú eres alguien. El viaje continúa.',

    # A2: Respect progression
    13: 'Cada mañana es nueva. Tu rutina es tu primer poema.',  # reflexives theme
    # 14-16 are fine
    17: 'La memoria es un regalo. Cuida la memoria.',  # no "no pierdas" (subjunctive)
    18: 'El primer mundo termina. El segundo empieza. Camina.',

    # B1: respect progression
    # 25 was using conditional — fix
    25: 'No todo brilla igual. Mira con tus propios ojos.',
}

# ============================================================
# FIXED CRONICA PROMPTS
# ============================================================
FIXED_CRONICAS = {
    # A1: Present tense only
    7:  {'prompt': 'Escribe qué comes hoy: "Yo como ___."'},
    11: {'prompt': 'Escribe un verbo. ¿Qué haces tú?'},

    # A2: dest13 must use reflexives, not preterite
    13: {'prompt': 'Escribe tu rutina de la mañana: "Yo me levanto. Yo me ___."'},
}

# ============================================================
# FIXED ESCAPE ROOMS
# ============================================================
FIXED_ESCAPE_ROOMS = {
    # A1 fixes
    3: {
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Soy el color del cielo limpio. Soy el color del agua del río. ¿Qué color soy?', 'clue': 'Mira el cielo. No hay nubes.', 'answer': 'azul'}
        ]
    },
    5: {
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Yaguará dice: "A mí me gusta mucho. Es líquida. Es fría. Sale del río." ¿Qué es?', 'clue': 'Sin ella, no hay vida.', 'answer': 'agua'}
        ]
    },
    6: {
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Todos hacemos esto cada día. Empieza con V. Tiene 5 letras.', 'clue': '_ _ _ _ _', 'answer': 'vivir'}
        ]
    },

    # A2 fixes
    13: {
        # Must be about reflexive verbs, NOT preterite
        'room': {
            'description': 'Una sala con un espejo grande. El espejo muestra tu rutina de la mañana.',
            'ambience': 'Agua corriente. Sonido de la mañana.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'El tipo de verbo con me, te, se, nos. Tiene 9 letras.', 'clue': '_ _ _ _ _ _ _ _ _', 'answer': 'reflexivo'}
        ]
    },
    15: {
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'No es comida. No es agua. Tu cuerpo necesita esto todas las noches. Cierras los ojos.', 'clue': 'Todos lo hacemos en la noche.', 'answer': 'dormir'}
        ]
    },

    # B1 fixes
    20: {
        # Must be about indirect speech, NOT subjunctive
        'room': {
            'description': 'Una casa de madera junto al río. Las paredes están llenas de historias pintadas.',
            'ambience': 'El río murmura. Una abuela cuenta historias.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Ella habló. Tú cuentas lo que ella dijo. El verbo del pasado más importante en las historias. Empieza con D. 4 letras.', 'clue': 'Ella ___ que el río estaba enfermo.', 'answer': 'dijo'}
        ]
    },
    25: {
        # Must use concessive (aunque), NOT conditional/imperf subjunctive
        'room': {
            'description': 'Una oficina improvisada en la selva. Hay papeles sobre la mesa con letras pequeñas.',
            'ambience': 'Bolígrafo en papel. Don Próspero suspira.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Don Próspero dice: "Aunque conozco los árboles, necesito el camino." Si el camino destruye los árboles, ¿cuál es la contradicción?', 'clue': 'Él dice que conoce algo, pero lo destruye.', 'answer': 'destruye lo que conoce'}
        ]
    },

    # B2 fixes — change synthesis puzzle type to logic where needed
    28: {
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Doña Asunción dijo: "Las palabras tienen memoria." Si las palabras tienen memoria y tú usas palabras, ¿qué llevas contigo?', 'clue': 'Lo que las palabras recuerdan vive en ti.', 'answer': 'la memoria de otros'}
        ]
    },
    29: {
        # Fix pluperfect subjunctive → use basic conditional (dest29's topic)
        'room': {
            'description': 'Una sala sin color. Todo es gris. Las paredes absorben el sonido.',
            'ambience': 'Silencio total. Tu propia respiración.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'El silencio avanza. Si no escuchamos ahora, ¿qué pasa? ¿Se puede cambiar lo que está pasando?', 'clue': 'La acción es ahora, no en el pasado.', 'answer': 'solo si actuamos ahora'}
        ]
    },
    33: {
        # Change synthesis to logic (synthesis is C1-C2 only)
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Aquí se guardan las palabras olvidadas. ¿Por qué es importante recordar las palabras que nadie usa?', 'clue': 'Piensa en lo que se pierde cuando una palabra desaparece.', 'answer': 'se pierde un mundo'}
        ]
    },
    36: {
        # Change synthesis to logic at B2
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Tengo raíces pero no soy planta. Tengo hojas pero no soy libro. Tengo tronco pero no soy cuerpo. Doy aire pero no soy ventilador.', 'clue': 'Empieza con A. Vive en el bosque.', 'answer': 'arbol'},
            {'puzzleType': 'logic', 'prompt': 'Un bosque produce oxígeno, agua limpia y biodiversidad. Estos beneficios valen más que la madera. Pero solo la madera se puede vender. ¿Cómo se resuelve esta contradicción?', 'clue': 'El valor real no tiene precio de mercado.', 'answer': 'dar valor a lo invisible'},
            {'puzzleType': 'logic', 'prompt': 'Combina estas ideas: "el desarrollo genera empleo" + "la biodiversidad no tiene precio" = ¿Qué modelo económico necesitamos?', 'clue': 'Un modelo que incluya lo que no se puede comprar.', 'answer': 'desarrollo sostenible'}
        ]
    },
}

# ============================================================
# FIXED CLOSING QUESTIONS
# ============================================================
FIXED_CLOSING_QUESTIONS = {
    10: '¿Qué hora es en tu mundo?',  # was "¿Cuánto tiempo ha pasado?" (present perfect)
}

# ============================================================
# MAIN
# ============================================================
def main():
    print("=" * 60)
    print("CEFR Grammar Violation Fixes")
    print("=" * 60)

    total_fixes = 0

    for dest_num in range(1, 59):
        path = CONTENT_DIR / ('dest%d.json' % dest_num)
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        fixes = []

        # Fix arrival
        if dest_num in FIXED_ARRIVALS:
            arr_data = FIXED_ARRIVALS[dest_num]
            arrival = data.get('arrival', {})
            arrival['sections'] = arr_data['sections']
            arrival['button'] = arr_data['button']
            if data.get('meta', {}).get('previousClosingQuestion'):
                arrival['previousClosingQuestion'] = data['meta']['previousClosingQuestion']
            data['arrival'] = arrival
            fixes.append('arrival')

        # Fix departure yaguaraLine
        if dest_num in FIXED_DEPARTURES:
            dep = data.get('departure', {})
            dep['yaguaraLine'] = FIXED_DEPARTURES[dest_num]
            data['departure'] = dep
            fixes.append('departure')

        # Fix cronica prompt
        if dest_num in FIXED_CRONICAS:
            cronica_fix = FIXED_CRONICAS[dest_num]
            for game in data.get('games', []):
                if game.get('type') == 'cronica':
                    if 'prompt' in cronica_fix:
                        game['prompt'] = cronica_fix['prompt']
                    fixes.append('cronica')
                    break

        # Fix closing question
        if dest_num in FIXED_CLOSING_QUESTIONS:
            data['meta']['closingQuestion'] = FIXED_CLOSING_QUESTIONS[dest_num]
            dep = data.get('departure', {})
            dep['closingQuestion'] = FIXED_CLOSING_QUESTIONS[dest_num]
            data['departure'] = dep
            fixes.append('closingQuestion')

        # Fix escape room
        if dest_num in FIXED_ESCAPE_ROOMS:
            er_fix = FIXED_ESCAPE_ROOMS[dest_num]
            er = data.get('escapeRoom', {})

            if 'room' in er_fix:
                room = er.get('room', {})
                if 'description' in er_fix['room']:
                    room['description'] = er_fix['room']['description']
                if 'ambience' in er_fix['room']:
                    room['ambience'] = er_fix['room']['ambience']
                er['room'] = room

            if 'puzzles' in er_fix:
                er['puzzles'] = er_fix['puzzles']

            # Remove _needsContent if present
            if '_needsContent' in er:
                del er['_needsContent']

            data['escapeRoom'] = er
            fixes.append('escapeRoom')

        if fixes:
            with open(path, 'w', encoding='utf-8') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
            print('  dest%d: fixed %s' % (dest_num, ', '.join(fixes)))
            total_fixes += len(fixes)

    print('\n' + '=' * 60)
    print('COMPLETE: %d fixes applied' % total_fixes)
    print('=' * 60)


if __name__ == '__main__':
    main()
