#!/usr/bin/env python3
"""
add-grammar-tips.py
Adds optional knowledge-pill tips to practice encounters in dest2-12.

Tips are:
  - Primarily Spanish examples
  - Student's language gloss only when strictly necessary
  - Never shown by default — rendered as a ? button

Tip structure:
  {
    "title": "ser",                    # Spanish label (always)
    "lines": ["yo soy", "tú eres"],   # Spanish examples (always)
    "gloss": "ser = to be"             # student's language (optional, only when needed)
  }

Safety:
  - Only adds tip to games that don't already have one
  - Atomic write: .tmp → rename
"""

import json
import os
import sys

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')

# ─── DEST2: articles (el/la/los/las), gender, singular/plural ───

DEST2_TIPS = {
    1: {  # pair: article-noun pairs
        "title": "Artículos",
        "lines": [
            "masculino → el (uno) / los (muchos)",
            "femenino → la (una) / las (muchas)"
        ],
        "gloss": "el/la = the"
    },
    2: {  # category: masculine/feminine
        "title": "Género",
        "lines": [
            "Palabras con -o → masculino: el río, el árbol",
            "Palabras con -a → femenino: la selva, la flor",
            "Excepciones: el día, la mano"
        ]
    },
    3: {  # fill: articles
        "title": "Singular y plural",
        "lines": [
            "el río → los ríos",
            "la flor → las flores",
            "el árbol → los árboles"
        ]
    },
    15: {  # conjugation: ser
        "title": "ser",
        "lines": [
            "yo soy",
            "tú eres",
            "él/ella es",
            "nosotros somos",
            "ellos/ellas son"
        ],
        "gloss": "ser = to be (identity)"
    },
    16: {  # conjugation: estar
        "title": "estar",
        "lines": [
            "yo estoy",
            "tú estás",
            "él/ella está",
            "nosotros estamos",
            "ellos/ellas están"
        ],
        "gloss": "estar = to be (location, state)"
    },
}

# ─── DEST3: ser vs estar, hay, adjective agreement, colors ───

DEST3_TIPS = {
    1: {  # listening
        "title": "ser / estar / hay",
        "lines": [
            "ser → qué es: La selva es grande.",
            "estar → dónde está: Yaguará está en la selva.",
            "hay → existe: Hay flores."
        ]
    },
    2: {  # fill: es/está/hay
        "title": "¿es, está o hay?",
        "lines": [
            "es → descripción: La flor es roja.",
            "está → lugar o estado: El río está aquí.",
            "hay → existencia: Hay un árbol."
        ]
    },
    13: {  # conjugation: tener
        "title": "tener",
        "lines": [
            "yo tengo",
            "tú tienes",
            "él/ella tiene",
            "nosotros tenemos",
            "ellos/ellas tienen"
        ],
        "gloss": "tener = to have"
    },
    20: {  # conversation
        "title": "Adjetivos",
        "lines": [
            "masculino: El río es bonito.",
            "femenino: La selva es bonita.",
            "El adjetivo sigue al nombre."
        ]
    },
}

# ─── DEST4: numbers 0-31, tener + age, days, time of day ───

DEST4_TIPS = {
    1: {  # pair: number-word
        "title": "Números 0-10",
        "lines": [
            "0 cero, 1 uno, 2 dos, 3 tres, 4 cuatro, 5 cinco",
            "6 seis, 7 siete, 8 ocho, 9 nueve, 10 diez"
        ]
    },
    2: {  # fill: tener + numbers
        "title": "tener + años",
        "lines": [
            "Yo tengo tres años.",
            "Tú tienes cinco años.",
            "Él tiene diez años.",
            "¡No soy tres años! → ¡Tengo tres años!"
        ],
        "gloss": "tener + años = to be ... years old"
    },
    3: {  # conversation: numbers/time
        "title": "Los días",
        "lines": [
            "lunes, martes, miércoles, jueves",
            "viernes, sábado, domingo",
            "Hoy es lunes. Mañana es martes."
        ]
    },
    11: {  # listening
        "title": "El momento del día",
        "lines": [
            "la mañana (☀️ temprano)",
            "el mediodía (☀️ alto)",
            "la tarde (🌅)",
            "la noche (🌙)"
        ]
    },
    17: {  # category: ser vs estar
        "title": "ser / estar",
        "lines": [
            "ser → identidad: Yo soy Yaguará.",
            "estar → lugar: Yo estoy en la selva.",
            "ser → descripción: La selva es grande.",
            "estar → estado: Yo estoy bien."
        ]
    },
}

# ─── DEST5: me gusta / no me gusta, negation, possessives ───

DEST5_TIPS = {
    1: {  # listening
        "title": "gustar",
        "lines": [
            "me gusta + una cosa: Me gusta el río.",
            "me gustan + muchas cosas: Me gustan los árboles.",
            "no me gusta: No me gusta la lluvia."
        ],
        "gloss": "gustar = to like (lit. to please)"
    },
    2: {  # fill: gustos/posesiones
        "title": "Posesivos",
        "lines": [
            "yo → mi: mi río, mi selva",
            "tú → tu: tu casa, tu nombre",
            "él/ella → su: su libro, su familia"
        ],
        "gloss": "mi = my, tu = your, su = his/her"
    },
    3: {  # category: me gusta / no me gusta
        "title": "Negación",
        "lines": [
            "Sí → Me gusta el agua.",
            "No → No me gusta la lluvia.",
            "no + verbo: No camino. No como."
        ]
    },
    13: {  # conjugation: gustar
        "title": "gustar (formas)",
        "lines": [
            "me gusta / me gustan (yo)",
            "te gusta / te gustan (tú)",
            "le gusta / le gustan (él/ella)",
            "nos gusta / nos gustan (nosotros)",
            "les gusta / les gustan (ellos/ellas)"
        ]
    },
}

# ─── DEST6: -AR conjugation, prepositions ───

DEST6_TIPS = {
    1: {  # conjugation: caminar
        "title": "Verbos -AR",
        "lines": [
            "caminar: yo camino, tú caminas, él camina",
            "nosotros caminamos, ellos caminan",
            "Igual: hablar, escuchar, nadar, descansar"
        ]
    },
    2: {  # builder: routine
        "title": "Preposiciones",
        "lines": [
            "en → lugar: en la selva, en el río",
            "por → movimiento: por la mañana, por la selva",
            "con → compañía: con Río, con mi familia",
            "a → dirección: voy a la selva",
            "de → origen: de la selva, del río"
        ]
    },
    8: {  # fill
        "title": "Verbos -AR (repaso)",
        "lines": [
            "-o (yo), -as (tú), -a (él/ella)",
            "-amos (nosotros), -an (ellos/ellas)",
            "hablar → hablo, hablas, habla, hablamos, hablan"
        ]
    },
    13: {  # conjugation: llamarse
        "title": "Verbos reflexivos",
        "lines": [
            "llamarse: yo me llamo, tú te llamas",
            "él/ella se llama",
            "nosotros nos llamamos",
            "ellos/ellas se llaman"
        ],
        "gloss": "llamarse = to be called"
    },
}

# ─── DEST7: food, -ER verbs, tener hambre/sed/frío/calor ───

DEST7_TIPS = {
    2: {  # fill: verb forms
        "title": "Verbos -ER",
        "lines": [
            "comer: yo como, tú comes, él come",
            "nosotros comemos, ellos comen",
            "Igual: beber, leer, correr"
        ]
    },
    3: {  # conversation
        "title": "tener + sensación",
        "lines": [
            "Tengo hambre. (quiero comer)",
            "Tengo sed. (quiero beber)",
            "Tengo frío. / Tengo calor.",
            "¡No estoy hambre! → ¡Tengo hambre!"
        ],
        "gloss": "hambre = hunger, sed = thirst"
    },
    13: {  # conjugation: comer
        "title": "comer",
        "lines": [
            "yo como",
            "tú comes",
            "él/ella come",
            "nosotros comemos",
            "ellos/ellas comen"
        ],
        "gloss": "comer = to eat"
    },
    14: {  # conjugation: beber
        "title": "beber",
        "lines": [
            "yo bebo",
            "tú bebes",
            "él/ella bebe",
            "nosotros bebemos",
            "ellos/ellas beben"
        ],
        "gloss": "beber = to drink"
    },
    16: {  # category: hambre/sed
        "title": "tener + sensación",
        "lines": [
            "hambre → quiero comer",
            "sed → quiero beber",
            "frío → necesito calor",
            "calor → necesito frío"
        ]
    },
}

# ─── DEST8: family, este/esta, ¿quién? ───

DEST8_TIPS = {
    1: {  # pair: person-description
        "title": "La familia",
        "lines": [
            "mamá / papá",
            "hermano / hermana",
            "hijo / hija",
            "abuelo / abuela"
        ]
    },
    2: {  # category: masculine/feminine
        "title": "este / esta",
        "lines": [
            "masculino → este: Este es mi hermano.",
            "femenino → esta: Esta es mi mamá.",
            "plural → estos / estas"
        ],
        "gloss": "este/esta = this"
    },
    3: {  # fill: family
        "title": "¿Quién?",
        "lines": [
            "¿Quién es ella? → Ella es mi mamá.",
            "¿Quién es él? → Él es mi hermano.",
            "¿Quién eres tú? → Yo soy Yaguará."
        ],
        "gloss": "¿quién? = who?"
    },
    13: {  # conjugation: ser
        "title": "ser (con familia)",
        "lines": [
            "Ella es mi mamá.",
            "Él es mi hermano.",
            "Ellos son mi familia.",
            "Nosotros somos hermanos."
        ]
    },
}

# ─── DEST9: rooms, estar + states, un/una ───

DEST9_TIPS = {
    1: {  # listening
        "title": "estar + estado",
        "lines": [
            "Estoy bien. Estoy mal.",
            "Estoy contenta. Estoy cansada.",
            "Estoy feliz. Estoy triste.",
            "¿Cómo estás? → Estoy bien."
        ]
    },
    2: {  # fill: estar / un / una
        "title": "un / una",
        "lines": [
            "masculino → un: un dormitorio, un río",
            "femenino → una: una cocina, una casa",
            "Hay un árbol. Hay una flor."
        ],
        "gloss": "un/una = a, an"
    },
    10: {  # fill: ser vs estar
        "title": "ser / estar",
        "lines": [
            "ser → qué es: La casa es grande.",
            "estar → cómo está: Estoy feliz.",
            "estar → dónde está: La cocina está aquí."
        ]
    },
    13: {  # conjugation: estar
        "title": "estar",
        "lines": [
            "yo estoy",
            "tú estás",
            "él/ella está",
            "nosotros estamos",
            "ellos/ellas están"
        ]
    },
    15: {  # category: ser vs estar
        "title": "¿ser o estar?",
        "lines": [
            "ser → identidad, descripción permanente",
            "estar → lugar, estado temporal",
            "La selva es grande. (siempre)",
            "Estoy contenta. (ahora)"
        ]
    },
}

# ─── DEST10: months, clock time, numbers 32-100, ¿cuándo? ───

DEST10_TIPS = {
    1: {  # pair: number-word
        "title": "Números 32-100",
        "lines": [
            "treinta y dos, treinta y tres...",
            "cuarenta (40), cincuenta (50), sesenta (60)",
            "setenta (70), ochenta (80), noventa (90)",
            "cien (100)"
        ]
    },
    2: {  # fill: time
        "title": "La hora",
        "lines": [
            "¿Qué hora es?",
            "Es la una. (1:00)",
            "Son las dos. Son las tres. (2:00, 3:00)",
            "Son las diez de la mañana."
        ],
        "gloss": "¿qué hora es? = what time is it?"
    },
    6: {  # listening
        "title": "Los meses",
        "lines": [
            "enero, febrero, marzo, abril",
            "mayo, junio, julio, agosto",
            "septiembre, octubre, noviembre, diciembre"
        ]
    },
    14: {  # fill: hora/mes
        "title": "¿Cuándo?",
        "lines": [
            "¿Cuándo es tu cumpleaños?",
            "En enero. En mayo. En diciembre.",
            "¿Cuándo? → pregunta de tiempo"
        ],
        "gloss": "¿cuándo? = when?"
    },
}

# ─── DEST11: -ER/-IR verbs, ir, plural possessives, ¿por qué?/porque ───

DEST11_TIPS = {
    1: {  # fill: verb forms
        "title": "Verbos -ER / -IR",
        "lines": [
            "-ER: como, comes, come, comemos, comen",
            "-IR: vivo, vives, vive, vivimos, viven",
            "Igual: escribir, leer, correr"
        ]
    },
    6: {  # fill
        "title": "ir (irregular)",
        "lines": [
            "yo voy",
            "tú vas",
            "él/ella va",
            "nosotros vamos",
            "ellos/ellas van"
        ],
        "gloss": "ir = to go"
    },
    8: {  # fill: ir a + verbo
        "title": "ir a + verbo",
        "lines": [
            "Voy a caminar. (futuro)",
            "Vas a comer.",
            "Vamos a vivir aquí.",
            "ir a + infinitivo → futuro próximo"
        ],
        "gloss": "ir a + verb = going to"
    },
    11: {  # category: verb groups
        "title": "Tres grupos de verbos",
        "lines": [
            "-AR: caminar, hablar, nadar, escuchar",
            "-ER: comer, beber, leer, correr",
            "-IR: vivir, escribir, ir"
        ]
    },
    18: {  # conjugation
        "title": "¿Por qué? / Porque",
        "lines": [
            "¿Por qué escribes? (pregunta)",
            "Porque me gusta. (respuesta)",
            "¿Por qué? = pregunta",
            "Porque = respuesta"
        ],
        "gloss": "¿por qué? = why?, porque = because"
    },
}

# ─── DEST12: nationality adjectives, expanded colors, A1 integration ───

DEST12_TIPS = {
    1: {  # fill: general
        "title": "Nacionalidades",
        "lines": [
            "colombiano / colombiana",
            "mexicano / mexicana",
            "español / española",
            "El adjetivo cambia: -o (él) / -a (ella)"
        ]
    },
    7: {  # fill: verbs
        "title": "ser / estar / tener",
        "lines": [
            "ser → identidad: Soy colombiana.",
            "estar → estado: Estoy feliz.",
            "tener → posesión/edad: Tengo tres años.",
            "Tres verbos, tres usos diferentes."
        ]
    },
    10: {  # category: ser/estar/tener
        "title": "¿ser, estar o tener?",
        "lines": [
            "Soy Yaguará. (ser = quién)",
            "Estoy en la selva. (estar = dónde)",
            "Tengo tres años. (tener = qué tengo)",
            "Estoy contenta. (estar = cómo)"
        ]
    },
    15: {  # fill: A1 review
        "title": "Palabras interrogativas",
        "lines": [
            "¿Quién? → persona",
            "¿Qué? → cosa",
            "¿Dónde? → lugar",
            "¿Cuándo? → tiempo",
            "¿Por qué? → razón",
            "¿Cómo? → manera"
        ]
    },
    20: {  # category: ser/estar/tener (second)
        "title": "Repaso A1: los colores",
        "lines": [
            "rojo/roja, azul, verde, amarillo/amarilla",
            "blanco/blanca, negro/negra",
            "El cielo es azul. La flor es roja.",
            "Colores con -o/-a cambian. Azul y verde no."
        ]
    },
}

# ─── Master map ───

ALL_TIPS = {
    2: DEST2_TIPS,
    3: DEST3_TIPS,
    4: DEST4_TIPS,
    5: DEST5_TIPS,
    6: DEST6_TIPS,
    7: DEST7_TIPS,
    8: DEST8_TIPS,
    9: DEST9_TIPS,
    10: DEST10_TIPS,
    11: DEST11_TIPS,
    12: DEST12_TIPS,
}


def main():
    total_tips = 0
    errors = []

    for dest_num in range(2, 13):
        filename = f'dest{dest_num}.json'
        filepath = os.path.join(CONTENT_DIR, filename)

        if not os.path.exists(filepath):
            errors.append(f'{filename}: file not found')
            continue

        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)

        games = data.get('games', [])
        tips_for_dest = ALL_TIPS.get(dest_num, {})
        added = 0

        for game_idx, tip in tips_for_dest.items():
            if game_idx >= len(games):
                errors.append(f'{filename}: game index {game_idx} out of range (has {len(games)} games)')
                continue

            if 'tip' in games[game_idx]:
                print(f'  dest{dest_num} games[{game_idx}]: already has tip, skipping')
                continue

            games[game_idx]['tip'] = tip
            added += 1

        data['games'] = games

        # Atomic write
        tmp_path = filepath + '.tmp'
        with open(tmp_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.write('\n')
        os.rename(tmp_path, filepath)

        tip_titles = [t['title'] for t in tips_for_dest.values()]
        print(f'  dest{dest_num}: +{added} tips ({", ".join(tip_titles)})')
        total_tips += added

    print(f'\n  {total_tips} tips added across dest2-12')
    if errors:
        print(f'  {len(errors)} errors:')
        for e in errors:
            print(f'    - {e}')
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
