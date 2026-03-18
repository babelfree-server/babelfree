#!/usr/bin/env python3
"""
inject-skits.py — Replace grammar narrative encounters (games[0]) in dest1-12
with animated skit encounters. Idempotent: skips if games[0] is already a skit.
"""

import json
import os
import sys

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')

# ─────────────────────────────────────────────────────────────
# 12 SKIT DEFINITIONS (one per destination)
# ─────────────────────────────────────────────────────────────

SKITS = {

# ═══════════════════════════════════════════════════════════════
# DEST 1 — ser (yo/tú), hola/adiós, sí/no
# Scene: Hormiguita crosses path, Sol watches from above
# ═══════════════════════════════════════════════════════════════
1: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest1-grammar.jpg",
    "imageAlt": "Yaguará sentada junto al río, una hormiga cruza el camino y el sol brilla arriba",
    "grammar": ["ser (yo/tú)", "greetings"],
    "beats": [
        {
            "speaker": "hormiguita",
            "name": "Hormiguita",
            "animation": "enter-left",
            "text": "Hola."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hola, hormiguita."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo soy Yaguará.",
            "target": ["soy"]
        },
        {
            "speaker": "hormiguita",
            "name": "Hormiguita",
            "animation": "exit-right",
            "text": "Adiós."
        },
        {
            "speaker": "sol",
            "name": "Sol",
            "animation": "enter-top",
            "text": "Hola. Yo soy el Sol.",
            "target": ["soy"]
        },
        {
            "speaker": "sol",
            "name": "Sol",
            "text": "¿Tú eres... Yaguará?",
            "target": ["eres"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Sí, yo soy Yaguará.",
            "target": ["soy"],
            "interaction": {
                "type": "pick",
                "options": ["Sí, yo soy Yaguará.", "No, tú eres Yaguará.", "Adiós."],
                "answer": "Sí, yo soy Yaguará."
            }
        },
        {
            "speaker": "sol",
            "name": "Sol",
            "text": "¿Y tú eres {nombre}?",
            "target": ["eres"]
        },
        {
            "speaker": "tu",
            "name": "Tú",
            "text": "Sí, yo soy {nombre}.",
            "target": ["soy"],
            "interaction": {
                "type": "pick",
                "options": ["Sí, yo soy {nombre}.", "No, yo soy el Sol.", "Adiós."],
                "answer": "Sí, yo soy {nombre}."
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 2 — articles el/la/los/las, gender, plural
# Scene: Yaguará walks through the jungle naming things
# ═══════════════════════════════════════════════════════════════
2: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest2-grammar.jpg",
    "imageAlt": "Yaguará junto al río, señalando las cosas de la selva",
    "grammar": ["articles", "gender", "singular/plural"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Mira..."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "El río.",
            "target": ["El"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La flor.",
            "target": ["La"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Los árboles.",
            "target": ["Los"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Las ranas.",
            "target": ["Las"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ jaguar.",
            "target": ["El"],
            "interaction": {
                "type": "pick",
                "options": ["El", "La", "Los", "Las"],
                "answer": "El"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ selva.",
            "target": ["La"],
            "interaction": {
                "type": "pick",
                "options": ["El", "La", "Los", "Las"],
                "answer": "La"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ flores.",
            "target": ["Las"],
            "interaction": {
                "type": "pick",
                "options": ["El", "La", "Los", "Las"],
                "answer": "Las"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 3 — ser vs estar, hay, colors
# Scene: Yaguará and a frog by the river
# ═══════════════════════════════════════════════════════════════
3: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest3-grammar.jpg",
    "imageAlt": "Yaguará junto al río con una rana verde en una hoja",
    "grammar": ["ser vs estar", "hay", "colors"],
    "beats": [
        {
            "speaker": "rana",
            "name": "Rana",
            "animation": "enter-left",
            "text": "..."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La rana es verde.",
            "target": ["es"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La rana está en el río.",
            "target": ["está"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hay muchos árboles.",
            "target": ["Hay"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "El río es azul.",
            "target": ["azul"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La flor ___ roja.",
            "target": ["es"],
            "interaction": {
                "type": "pick",
                "options": ["es", "está", "hay"],
                "answer": "es"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "El mono ___ en el árbol.",
            "target": ["está"],
            "interaction": {
                "type": "pick",
                "options": ["es", "está", "hay"],
                "answer": "está"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ una rana en el río.",
            "target": ["Hay"],
            "interaction": {
                "type": "pick",
                "options": ["Es", "Está", "Hay"],
                "answer": "Hay"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 4 — numbers, tener + age, days
# Scene: Children counting fish by the river
# ═══════════════════════════════════════════════════════════════
4: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest4-grammar.jpg",
    "imageAlt": "Niños junto al río contando peces en el agua",
    "grammar": ["numbers", "tener + age", "days of week"],
    "beats": [
        {
            "speaker": "nino",
            "name": "Niño",
            "animation": "enter-left",
            "text": "Uno, dos, tres..."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Cuántos peces hay?"
        },
        {
            "speaker": "nino",
            "name": "Niño",
            "text": "Hay cinco peces.",
            "target": ["cinco"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Uno, dos, tres, cuatro, ___.",
            "interaction": {
                "type": "pick",
                "options": ["cinco", "seis", "diez"],
                "answer": "cinco"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Cuántos años tienes?",
            "target": ["tienes"]
        },
        {
            "speaker": "nino",
            "name": "Niño",
            "text": "Tengo diez años.",
            "target": ["Tengo"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo ___ cien años.",
            "target": ["tengo"],
            "interaction": {
                "type": "pick",
                "options": ["tengo", "tienes", "tiene"],
                "answer": "tengo"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hoy es lunes.",
            "target": ["lunes"],
            "animation": "glow"
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 5 — me gusta, negation, possessives
# Scene: Yaguará sharing likes and dislikes
# ═══════════════════════════════════════════════════════════════
5: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest5-grammar.jpg",
    "imageAlt": "Yaguará junto al río señalando las cosas que le gustan",
    "grammar": ["me gusta", "negation", "possessives"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Me gusta la selva.",
            "target": ["Me gusta"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Me gustan los animales.",
            "target": ["Me gustan"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "No me gusta la lluvia.",
            "target": ["No"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Mi nombre es Yaguará.",
            "target": ["Mi"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Y tu nombre?",
            "target": ["tu"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ me gusta el río.",
            "interaction": {
                "type": "pick",
                "options": ["No", "Sí", "Mi"],
                "answer": "No"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ nombre es Yaguará.",
            "target": ["Mi"],
            "interaction": {
                "type": "pick",
                "options": ["Mi", "Tu", "Su"],
                "answer": "Mi"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 6 — -AR verbs, prepositions, daily routines
# Scene: Yaguará and a monkey in the jungle
# ═══════════════════════════════════════════════════════════════
6: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest6-grammar.jpg",
    "imageAlt": "Yaguará y un mono caminando por la selva",
    "grammar": ["-AR conjugation", "prepositions"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo camino por la selva.",
            "target": ["camino", "por"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Tú nadas en el río.",
            "target": ["nadas", "en"]
        },
        {
            "speaker": "mono",
            "name": "Mono",
            "animation": "enter-left",
            "text": "¡Hola!"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "El mono salta de árbol en árbol.",
            "target": ["salta", "de"]
        },
        {
            "speaker": "mono",
            "name": "Mono",
            "animation": "bounce",
            "text": "¡Sí! Yo salto mucho.",
            "target": ["salto"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo ___ en el río.",
            "interaction": {
                "type": "pick",
                "options": ["nado", "nadas", "nada"],
                "answer": "nado"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Ella ___ español.",
            "interaction": {
                "type": "pick",
                "options": ["hablo", "hablas", "habla"],
                "answer": "habla"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 7 — -ER verbs, food, tener + states
# Scene: Yaguará and a toucan finding food
# ═══════════════════════════════════════════════════════════════
7: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest7-grammar.jpg",
    "imageAlt": "Yaguará y un tucán buscando comida en la selva",
    "grammar": ["food vocabulary", "comer/beber", "tener hambre/sed"],
    "beats": [
        {
            "speaker": "tucan",
            "name": "Tucán",
            "animation": "enter-left",
            "text": "Tengo hambre.",
            "target": ["Tengo hambre"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo también. Yo como plátano.",
            "target": ["como"]
        },
        {
            "speaker": "tucan",
            "name": "Tucán",
            "text": "Yo como fruta.",
            "target": ["como"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Tú bebes agua?",
            "target": ["bebes"]
        },
        {
            "speaker": "tucan",
            "name": "Tucán",
            "text": "Sí, bebo jugo.",
            "target": ["bebo"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Tengo ___. Bebo agua.",
            "interaction": {
                "type": "pick",
                "options": ["hambre", "sed", "calor"],
                "answer": "sed"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Ella ___ pescado.",
            "interaction": {
                "type": "pick",
                "options": ["como", "comes", "come"],
                "answer": "come"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 8 — family, este/esta, ¿quién?
# Scene: Yaguará meets a family by the river
# ═══════════════════════════════════════════════════════════════
8: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest8-grammar.jpg",
    "imageAlt": "Una familia junto al río, la abuela pesca",
    "grammar": ["family members", "este/esta", "¿quién?"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Mira, una familia."
        },
        {
            "speaker": "abuela",
            "name": "Abuela",
            "animation": "enter-left",
            "text": "Hola."
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Esta es la abuela.",
            "target": ["Esta"]
        },
        {
            "speaker": "nino",
            "name": "Niño",
            "animation": "enter-left",
            "text": "¡Hola!",
            "animation": "bounce"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Este es el niño.",
            "target": ["Este"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Quién es ella?",
            "target": ["Quién"],
            "interaction": {
                "type": "pick",
                "options": ["Es la mamá.", "Es el papá.", "Es el niño."],
                "answer": "Es la mamá."
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ es mi hermano.",
            "target": ["Este"],
            "interaction": {
                "type": "pick",
                "options": ["Este", "Esta", "Estos"],
                "answer": "Este"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 9 — house rooms, estar + states, un/una
# Scene: Yaguará explores a house
# ═══════════════════════════════════════════════════════════════
9: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest9-grammar.jpg",
    "imageAlt": "Una casa de madera junto al río con puertas abiertas",
    "grammar": ["rooms", "estar + states", "un/una"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Esta es una casa.",
            "target": ["una"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hay una cocina.",
            "target": ["una"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hay un dormitorio.",
            "target": ["un"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Estoy cansada.",
            "target": ["Estoy cansada"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hay ___ baño.",
            "interaction": {
                "type": "pick",
                "options": ["un", "una", "unos"],
                "answer": "un"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Mi hermano está ___.",
            "interaction": {
                "type": "pick",
                "options": ["enfermo", "cocina", "casa"],
                "answer": "enfermo"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 10 — numbers 32-100, months, clock time
# Scene: Yaguará reading a jungle clock
# ═══════════════════════════════════════════════════════════════
10: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest10-grammar.jpg",
    "imageAlt": "Yaguará mira al cielo, el sol marca la hora",
    "grammar": ["numbers 32-100", "months", "clock time"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Qué hora es?"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Son las tres.",
            "target": ["Son las tres"],
            "animation": "glow"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Cuántos árboles hay?"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Hay cuarenta y cinco árboles.",
            "target": ["cuarenta y cinco"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Estamos en febrero.",
            "target": ["febrero"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "¿Qué hora es? — ___ la una.",
            "interaction": {
                "type": "pick",
                "options": ["Es", "Son", "Hay"],
                "answer": "Es"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "50 = ___",
            "interaction": {
                "type": "pick",
                "options": ["cuarenta", "cincuenta", "sesenta"],
                "answer": "cincuenta"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 11 — -ER/-IR verbs, ir, possessives
# Scene: Yaguará and a friend going to the river
# ═══════════════════════════════════════════════════════════════
11: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest11-grammar.jpg",
    "imageAlt": "Yaguará caminando con un amigo hacia el río",
    "grammar": ["-ER/-IR conjugation", "ir", "plural possessives"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo vivo en la selva.",
            "target": ["vivo"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Nosotros vivimos aquí.",
            "target": ["vivimos"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo voy al río.",
            "target": ["voy"]
        },
        {
            "speaker": "amigo",
            "name": "Amigo",
            "animation": "enter-right",
            "text": "¿Adónde vas?"
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Vamos al río.",
            "target": ["Vamos"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Ella ___ una carta.",
            "interaction": {
                "type": "pick",
                "options": ["escribo", "escribes", "escribe"],
                "answer": "escribe"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "___ hermanos viven en la selva.",
            "target": ["Mis"],
            "interaction": {
                "type": "pick",
                "options": ["Mi", "Mis", "Su"],
                "answer": "Mis"
            }
        }
    ]
},

# ═══════════════════════════════════════════════════════════════
# DEST 12 — nationality adjectives, expanded colors
# Scene: Yaguará meeting travelers from different countries
# ═══════════════════════════════════════════════════════════════
12: {
    "type": "skit",
    "label": "Escena",
    "image": "img/destinations/dest12-grammar.jpg",
    "imageAlt": "Yaguará con viajeros de distintos países, la guacamaya vuela arriba",
    "grammar": ["nationality adjectives", "expanded colors"],
    "beats": [
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Yo soy colombiana.",
            "target": ["colombiana"]
        },
        {
            "speaker": "viajero1",
            "name": "Viajero",
            "animation": "enter-left",
            "text": "Yo soy mexicano.",
            "target": ["mexicano"]
        },
        {
            "speaker": "viajera",
            "name": "Viajera",
            "animation": "enter-right",
            "text": "Yo soy peruana.",
            "target": ["peruana"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La guacamaya es roja, azul y amarilla.",
            "target": ["roja", "azul", "amarilla"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "La flor es rosa.",
            "target": ["rosa"]
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "Ella es ___.",
            "interaction": {
                "type": "pick",
                "options": ["brasileña", "brasileño", "Brasil"],
                "answer": "brasileña"
            }
        },
        {
            "speaker": "yaguara",
            "name": "Yaguará",
            "text": "El tronco es ___.",
            "interaction": {
                "type": "pick",
                "options": ["marrón", "rosa", "gris"],
                "answer": "marrón"
            }
        }
    ]
}

}  # end SKITS


def main():
    dry_run = '--dry-run' in sys.argv
    changed = 0
    skipped = 0

    for dest_n in range(1, 13):
        fname = os.path.join(CONTENT_DIR, f'dest{dest_n}.json')
        if not os.path.exists(fname):
            print(f'  WARNING: {fname} not found')
            continue

        with open(fname, 'r') as f:
            data = json.load(f)

        games = data.get('games', [])
        if not games:
            print(f'  dest{dest_n}: no games array, skipping')
            skipped += 1
            continue

        # Check if already a skit
        if games[0].get('type') == 'skit':
            print(f'  dest{dest_n}: already has skit at games[0], skipping')
            skipped += 1
            continue

        # Verify the first game is a grammar narrative
        first_type = games[0].get('type', '')
        if first_type != 'narrative':
            print(f'  dest{dest_n}: games[0] is "{first_type}" (not narrative), skipping')
            skipped += 1
            continue

        skit = SKITS.get(dest_n)
        if not skit:
            print(f'  dest{dest_n}: no skit defined, skipping')
            skipped += 1
            continue

        if dry_run:
            print(f'  dest{dest_n}: WOULD replace grammar narrative with skit')
        else:
            games[0] = skit
            with open(fname, 'w') as f:
                json.dump(data, f, ensure_ascii=False, indent=2)
                f.write('\n')
            print(f'  dest{dest_n}: replaced grammar narrative with skit')

        changed += 1

    print(f'\nDone: {changed} replaced, {skipped} skipped' + (' (DRY RUN)' if dry_run else ''))


if __name__ == '__main__':
    main()
