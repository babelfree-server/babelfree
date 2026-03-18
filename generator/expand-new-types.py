#!/usr/bin/env python3
"""
Expand new game types beyond B1-B2. Adds 2-3 CEFR-appropriate variety games
to A1, A2, C1, and C2 destinations that currently lack them.

A1 (dest1-12): flashnote, crossword, spaceman, kloo (simple vocab/grammar)
A2 (dest13-18): + cultura, explorador, bananagrams, clon
C1 (dest39-48): all types (complex grammar)
C2 (dest49-58): all types (literary register)
"""
import json
import os

CONTENT = "/home/babelfree.com/public_html/content"

# ─────────────────────────────────────────────────────────────
# A1 GAMES — Present tense only, basic vocab
# ─────────────────────────────────────────────────────────────

A1_GAMES = {
    "dest1": [  # Despertar — ser/estar, family
        {
            "type": "flashnote", "label": "Cápsula", "instruction": "Recuerda esta regla.",
            "note": "Ser = permanente (soy, eres, es). Estar = temporal (estoy, estás, está).",
            "question": "Mi mamá ___ alta. (permanente)",
            "answer": "es",
            "options": ["es", "está", "tiene", "hay"]
        },
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina la palabra de la familia.",
            "phrases": [
                {"answer": "madre", "hint": "mamá"},
                {"answer": "padre", "hint": "papá"},
                {"answer": "hermano", "hint": "un chico de tu familia"},
                {"answer": "familia", "hint": "todos juntos"}
            ]
        }
    ],
    "dest2": [  # Sonidos — sounds, nature
        {
            "type": "crossword", "label": "Crucigrama", "instruction": "Escribe la palabra correcta.",
            "clues": [
                {"direction": "across", "number": 1, "clue": "Animal grande de la selva, con manchas", "answer": "jaguar"},
                {"direction": "down", "number": 1, "clue": "Color de las hojas", "answer": "verde"},
                {"direction": "across", "number": 3, "clue": "Líquido que fluye en el río", "answer": "agua"},
                {"direction": "down", "number": 2, "clue": "Lo contrario de pequeño", "answer": "grande"}
            ]
        },
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina los sonidos de la selva.",
            "phrases": [
                {"answer": "agua", "hint": "lo que tiene el río"},
                {"answer": "viento", "hint": "mueve las hojas"},
                {"answer": "selva", "hint": "bosque tropical"},
                {"answer": "noche", "hint": "cuando no hay sol"}
            ]
        }
    ],
    "dest3": [  # Agua — needs, tener
        {
            "type": "kloo", "label": "Kloo", "instruction": "Ordena las cartas.",
            "cards": [
                {"color": "red", "text": "Yo"},
                {"color": "blue", "text": "tengo"},
                {"color": "green", "text": "mucha"},
                {"color": "orange", "text": "sed"}
            ],
            "answer": "Yo tengo mucha sed"
        },
        {
            "type": "flashnote", "label": "Cápsula", "instruction": "Recuerda.",
            "note": "Tener = to have (tengo, tienes, tiene). Tener sed = to be thirsty. Tener hambre = to be hungry.",
            "question": "Ella ___ hambre.",
            "answer": "tiene",
            "options": ["tiene", "es", "está", "hay"]
        }
    ],
    "dest4": [  # Casa — places, hay
        {
            "type": "crossword", "label": "Crucigrama", "instruction": "Escribe la palabra.",
            "clues": [
                {"direction": "across", "number": 1, "clue": "Donde dormimos", "answer": "casa"},
                {"direction": "down", "number": 1, "clue": "Donde cocinamos", "answer": "cocina"},
                {"direction": "across", "number": 3, "clue": "Donde nos sentamos a comer", "answer": "mesa"},
                {"direction": "down", "number": 2, "clue": "Donde hay agua caliente", "answer": "baño"}
            ]
        }
    ],
    "dest5": [  # Comida — food, gustar
        {
            "type": "kloo", "label": "Kloo", "instruction": "Ordena las cartas.",
            "cards": [
                {"color": "red", "text": "A mí"},
                {"color": "blue", "text": "me gusta"},
                {"color": "green", "text": "el arroz"},
                {"color": "orange", "text": "con pollo"}
            ],
            "answer": "A mí me gusta el arroz con pollo"
        },
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina la comida.",
            "phrases": [
                {"answer": "arroz", "hint": "blanco, pequeño, se cocina con agua"},
                {"answer": "pollo", "hint": "animal de la granja"},
                {"answer": "fruta", "hint": "dulce, natural, tiene semillas"},
                {"answer": "pan", "hint": "se hace con harina"}
            ]
        }
    ],
    "dest6": [  # Amigos — basic interaction
        {
            "type": "flashnote", "label": "Cápsula", "instruction": "Recuerda.",
            "note": "Para presentarse: Me llamo... Soy de... Tengo... años. Me gusta...",
            "question": "___ llamo Carlos.",
            "answer": "Me",
            "options": ["Me", "Te", "Se", "Le"]
        },
        {
            "type": "kloo", "label": "Kloo", "instruction": "Ordena las cartas para presentarte.",
            "cards": [
                {"color": "red", "text": "Me llamo"},
                {"color": "blue", "text": "Ana"},
                {"color": "green", "text": "y soy"},
                {"color": "orange", "text": "de Colombia"}
            ],
            "answer": "Me llamo Ana y soy de Colombia"
        }
    ],
    "dest7": [  # A1 Advanced
        {
            "type": "crossword", "label": "Crucigrama", "instruction": "Escribe la palabra.",
            "clues": [
                {"direction": "across", "number": 1, "clue": "Lo contrario de día", "answer": "noche"},
                {"direction": "down", "number": 1, "clue": "Lo que hace Yaguará en la selva", "answer": "camina"},
                {"direction": "across", "number": 3, "clue": "Lo que usamos para ver", "answer": "ojos"},
                {"direction": "down", "number": 2, "clue": "Lo que sentimos cuando estamos bien", "answer": "feliz"}
            ]
        }
    ],
    "dest8": [
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina la palabra.",
            "phrases": [
                {"answer": "camino", "hint": "por donde caminamos"},
                {"answer": "estrella", "hint": "brilla en la noche"},
                {"answer": "lluvia", "hint": "agua que cae del cielo"},
                {"answer": "fuego", "hint": "caliente y rojo"}
            ]
        }
    ],
    "dest9": [
        {
            "type": "kloo", "label": "Kloo", "instruction": "Ordena las cartas.",
            "cards": [
                {"color": "red", "text": "El río"},
                {"color": "blue", "text": "es"},
                {"color": "green", "text": "muy"},
                {"color": "orange", "text": "grande"}
            ],
            "answer": "El río es muy grande"
        }
    ],
    "dest10": [
        {
            "type": "flashnote", "label": "Cápsula", "instruction": "Recuerda esta regla.",
            "note": "Hay = there is / there are. Es invariable: hay un gato, hay dos gatos.",
            "question": "___ muchos árboles en la selva.",
            "answer": "Hay",
            "options": ["Hay", "Son", "Están", "Tienen"]
        }
    ],
    "dest11": [
        {
            "type": "crossword", "label": "Crucigrama", "instruction": "Escribe la palabra.",
            "clues": [
                {"direction": "across", "number": 1, "clue": "Lo que hacemos con los oídos", "answer": "oír"},
                {"direction": "down", "number": 1, "clue": "Lo que hacemos con la boca", "answer": "hablar"},
                {"direction": "across", "number": 3, "clue": "Lo que hace el río", "answer": "fluye"},
                {"direction": "down", "number": 2, "clue": "Animal que vuela", "answer": "pájaro"}
            ]
        }
    ],
    "dest12": [
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina la palabra.",
            "phrases": [
                {"answer": "identidad", "hint": "quién eres"},
                {"answer": "nombre", "hint": "cómo te llamas"},
                {"answer": "historia", "hint": "algo que cuentas"},
                {"answer": "palabra", "hint": "lo que dices"}
            ]
        }
    ]
}

# ─────────────────────────────────────────────────────────────
# A2 GAMES — Past tenses, more complex structures
# ─────────────────────────────────────────────────────────────

A2_GAMES = {
    "dest13": [
        {
            "type": "cultura", "label": "Cultura", "instruction": "Lee y responde.",
            "text": "El río Atrato en Colombia es uno de los más importantes del Chocó. Las comunidades que viven junto al río dependen de él para pescar, transportarse y vivir. En 2016, el río Atrato fue declarado sujeto de derechos — es decir, el río tiene derechos como una persona.",
            "question": "¿Qué pasó con el río Atrato en 2016?",
            "answer": "Fue declarado sujeto de derechos",
            "options": ["Fue declarado sujeto de derechos", "Se secó completamente", "Cambió de nombre", "Fue contaminado"]
        },
        {
            "type": "explorador", "label": "Explorador", "instruction": "Explora la selva.",
            "locations": [
                {"name": "El río", "text": "El agua es clara. Los peces saltan.", "question": "¿Cómo es el agua?", "answer": "clara", "options": ["clara", "sucia", "roja", "negra"]},
                {"name": "La cueva", "text": "Dentro hay pinturas antiguas en las paredes.", "question": "¿Qué hay en las paredes?", "answer": "pinturas antiguas", "options": ["pinturas antiguas", "ventanas", "espejos", "flores"]}
            ]
        }
    ],
    "dest14": [
        {
            "type": "clon", "label": "Clon", "instruction": "Cambia del presente al pasado.",
            "pairs": [
                {"present": "Candelaria camina por el pueblo.", "past": "Candelaria caminó por el pueblo."},
                {"present": "Yaguará mira el río.", "past": "Yaguará miró el río."},
                {"present": "Los niños juegan en la plaza.", "past": "Los niños jugaron en la plaza."}
            ]
        },
        {
            "type": "bananagrams", "label": "Bananagrams", "instruction": "Forma palabras del pueblo.",
            "letters": ["P","U","E","B","L","O","C","A","S","A","R","I","O","M","E","R","C","A","D","O"],
            "targetWords": ["pueblo", "casa", "río", "mercado"]
        }
    ],
    "dest15": [
        {
            "type": "flashnote", "label": "Cápsula", "instruction": "Recuerda.",
            "note": "Pretérito indefinido: -é, -aste, -ó, -amos, -aron (verbos -ar). Ejemplo: caminé, caminaste, caminó.",
            "question": "Ayer yo ___ (caminar) por el bosque.",
            "answer": "caminé",
            "options": ["caminé", "camino", "caminaba", "caminaré"]
        },
        {
            "type": "crossword", "label": "Crucigrama", "instruction": "Escribe la palabra.",
            "clues": [
                {"direction": "across", "number": 1, "clue": "Lo que hizo Yaguará ayer (caminar)", "answer": "caminó"},
                {"direction": "down", "number": 1, "clue": "Lo que hizo Candelaria (hablar)", "answer": "habló"},
                {"direction": "across", "number": 3, "clue": "Lo que pasó con el sol (salir)", "answer": "salió"}
            ]
        }
    ],
    "dest16": [
        {
            "type": "kloo", "label": "Kloo", "instruction": "Ordena las cartas para contar qué pasó.",
            "cards": [
                {"color": "red", "text": "Ayer"},
                {"color": "blue", "text": "Candelaria"},
                {"color": "green", "text": "encontró"},
                {"color": "orange", "text": "un mapa antiguo"}
            ],
            "answer": "Ayer Candelaria encontró un mapa antiguo"
        },
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina la palabra.",
            "phrases": [
                {"answer": "pueblo", "hint": "donde vive mucha gente junta"},
                {"answer": "mercado", "hint": "donde compras y vendes"},
                {"answer": "camino", "hint": "conecta dos lugares"},
                {"answer": "historia", "hint": "lo que cuentas"}
            ]
        }
    ],
    "dest17": [  # A2 Advanced
        {
            "type": "guardian", "label": "Guardián", "instruction": "¡Rápido! ¿Pretérito o imperfecto?",
            "timeLimit": 8,
            "questions": [
                {"prompt": "caminé", "answer": "pretérito", "options": ["pretérito", "imperfecto"]},
                {"prompt": "caminaba", "answer": "imperfecto", "options": ["pretérito", "imperfecto"]},
                {"prompt": "dijo", "answer": "pretérito", "options": ["pretérito", "imperfecto"]},
                {"prompt": "decía", "answer": "imperfecto", "options": ["pretérito", "imperfecto"]},
                {"prompt": "llegó", "answer": "pretérito", "options": ["pretérito", "imperfecto"]},
                {"prompt": "vivía", "answer": "imperfecto", "options": ["pretérito", "imperfecto"]}
            ]
        }
    ],
    "dest18": [
        {
            "type": "senda", "label": "Senda", "instruction": "Elige tu camino.",
            "scenario": "Yaguará llega a una bifurcación. A la izquierda, un camino con flores. A la derecha, un sendero oscuro.",
            "choices": [
                {"text": "El camino con flores (parece seguro)", "consequence": "El camino lleva a un claro donde hay una cascada. El agua es clara y fresca."},
                {"text": "El sendero oscuro (puede ser interesante)", "consequence": "El sendero lleva a una cueva con pinturas antiguas. Las pinturas cuentan una historia."}
            ]
        },
        {
            "type": "madgab", "label": "Descifrar", "instruction": "Lee rápido y descubre la frase.",
            "rounds": [
                {"phonetic": "YA GUA RÁ CA MI NÓ", "answer": "Yaguará caminó", "hint": "Ella hizo algo con los pies"},
                {"phonetic": "EL RÍ O CAN TA BA", "answer": "El río cantaba", "hint": "El agua hacía sonidos"},
                {"phonetic": "LA SEL VA DOR MÍ A", "answer": "La selva dormía", "hint": "De noche, todo descansa"}
            ]
        }
    ]
}

# ─────────────────────────────────────────────────────────────
# C1 GAMES — Complex subordination, subjunctive, nuance
# ─────────────────────────────────────────────────────────────

C1_GAMES = {
    "dest39": [
        {
            "type": "conjuro", "label": "Conjuro", "instruction": "Produce la forma correcta del subjuntivo.",
            "spells": [
                {"prompt": "Es posible que la montaña ___ (guardar) secretos.", "answer": "guarde", "hint": "subjuntivo presente, ella"},
                {"prompt": "Ojalá que los mamos ___ (poder) enseñarnos.", "answer": "puedan", "hint": "subjuntivo presente, ellos"},
                {"prompt": "Dudo que ___ (existir) una respuesta simple.", "answer": "exista", "hint": "subjuntivo presente, ella"}
            ]
        },
        {
            "type": "cultura", "label": "Cultura", "instruction": "Lee y reflexiona.",
            "text": "Los mamos kogis son líderes espirituales que consideran la Sierra Nevada de Santa Marta como el corazón del mundo. Según su cosmología, todo lo que pasa en la Sierra afecta al planeta entero. Los mamos practican el «pagamento»: devolver a la tierra lo que se toma. No es una transacción, sino una relación de reciprocidad.",
            "question": "¿Qué es el pagamento para los mamos kogis?",
            "answer": "Una relación de reciprocidad con la tierra",
            "options": ["Una relación de reciprocidad con la tierra", "Un impuesto al gobierno", "Una oración católica", "Un tipo de comida"]
        }
    ],
    "dest41": [
        {
            "type": "consequences", "label": "Consecuencias", "instruction": "Construye una historia sobre las consecuencias del conocimiento.",
            "prompts": [
                {"label": "Yaguará aprendió que...", "options": ["el conocimiento ancestral es vulnerable", "los mamos no confían en extraños", "la montaña tiene memoria", "el silencio habla"]},
                {"label": "Decidió entonces...", "options": ["aprender la lengua de los mamos", "escribir todo lo que oyó", "guardar silencio como los antiguos", "buscar a alguien que tradujera"]},
                {"label": "La consecuencia fue...", "options": ["entendió que traducir no es lo mismo que comprender", "encontró palabras que no tenían equivalente", "el mamo le dio permiso para escuchar", "se dio cuenta de que el español no basta"]}
            ]
        }
    ],
    "dest43": [
        {
            "type": "madlibs", "label": "Madlibs", "instruction": "Completa con el marcador discursivo correcto.",
            "template": "{m1}, la situación requiere que actuemos con urgencia. {m2}, debemos considerar las perspectivas de todas las comunidades. {m3} algunas soluciones parecen simples, las consecuencias podrían ser irreversibles.",
            "blanks": [
                {"id": "m1", "label": "marcador de inicio", "answer": "En primer lugar", "options": ["En primer lugar", "Sin embargo", "Por lo tanto", "Además"]},
                {"id": "m2", "label": "marcador de adición", "answer": "Además", "options": ["Además", "En conclusión", "Sin embargo", "Primero"]},
                {"id": "m3", "label": "marcador de contraste", "answer": "Aunque", "options": ["Aunque", "Además", "Por eso", "En primer lugar"]}
            ]
        }
    ],
    "dest45": [
        {
            "type": "explorador", "label": "Explorador", "instruction": "Explora el territorio sagrado.",
            "locations": [
                {"name": "La cima", "text": "Desde aquí se ven los tres mundos. El mamo dice que la montaña conecta lo de abajo con lo de arriba.", "question": "¿Qué conecta la montaña según el mamo?", "answer": "Lo de abajo con lo de arriba", "options": ["Lo de abajo con lo de arriba", "Dos ciudades", "El mar con el cielo", "Nada"]},
                {"name": "El lago", "text": "El agua del lago sagrado no se puede tocar. Es el espejo del pensamiento de la tierra.", "question": "¿Por qué no se toca el agua?", "answer": "Es el espejo del pensamiento de la tierra", "options": ["Es el espejo del pensamiento de la tierra", "Está contaminada", "Es muy fría", "No existe"]}
            ]
        }
    ],
    "dest47": [
        {
            "type": "clon", "label": "Clon", "instruction": "Transforma del indicativo al subjuntivo.",
            "pairs": [
                {"present": "Creo que los antiguos tenían razón.", "past": "No creo que los antiguos tuvieran razón."},
                {"present": "Es cierto que la montaña habla.", "past": "No es cierto que la montaña hable."},
                {"present": "Sé que podemos entender.", "past": "Dudo que podamos entender."},
                {"present": "Es verdad que el conocimiento se pierde.", "past": "No es verdad que el conocimiento se pierda."}
            ]
        }
    ]
}

# ─────────────────────────────────────────────────────────────
# C2 GAMES — Literary register, synthesis
# ─────────────────────────────────────────────────────────────

C2_GAMES = {
    "dest49": [
        {
            "type": "eco_restaurar", "label": "Restaurar", "instruction": "El Silencio Gris ha llegado al pensamiento abstracto. Restaura cada concepto con su nombre poético.",
            "scenes": [
                {"faded": "Un impulso que mueve sin ser visto.", "restored": "La esperanza: el hilo invisible que conecta lo que somos con lo que podríamos ser.", "prompt": "¿Qué concepto se ha perdido?", "options": ["La esperanza: el hilo invisible que conecta lo que somos con lo que podríamos ser.", "Un viento fuerte.", "Una enfermedad."]},
                {"faded": "Una sensación que aparece cuando algo termina.", "restored": "La nostalgia: la certeza de que lo vivido fue real, aunque ya no exista.", "prompt": "¿Qué sentimiento es?", "options": ["La nostalgia: la certeza de que lo vivido fue real, aunque ya no exista.", "El aburrimiento.", "La rabia."]}
            ]
        }
    ],
    "dest51": [
        {
            "type": "boggle", "label": "Boggle", "instruction": "Encuentra palabras sobre la identidad y la lengua.",
            "grid": [["L","E","N","G","U"],["I","D","E","N","A"],["V","O","Z","T","I"],["R","A","I","Z","D"],["S","E","R","C","O"]],
            "words": ["lengua", "identidad", "voz", "raíz", "ser"],
            "minWords": 3
        }
    ],
    "dest53": [
        {
            "type": "spaceman", "label": "Spaceman", "instruction": "Adivina conceptos abstractos del viaje.",
            "phrases": [
                {"answer": "trascendencia", "hint": "ir más allá de los límites"},
                {"answer": "metamorfosis", "hint": "cambio profundo de forma"},
                {"answer": "epifanía", "hint": "revelación súbita de una verdad"},
                {"answer": "legado", "hint": "lo que dejas para los que vienen"}
            ]
        }
    ]
}


def add_games_to_dest(dest_num, new_games):
    """Insert games before escaperoom + cronica at end."""
    path = os.path.join(CONTENT, f"dest{dest_num}.json")
    with open(path) as f:
        data = json.load(f)

    games = data.get("games", [])
    before = len(games)

    # Find insert point
    insert_idx = len(games)
    for i in range(len(games) - 1, max(len(games) - 3, -1), -1):
        if i >= 0 and games[i].get("type") in ("escaperoom", "cronica"):
            insert_idx = i

    for game in reversed(new_games):
        games.insert(insert_idx, game)

    data["games"] = games
    with open(path, "w") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    return before, len(games)


print("=" * 60)
print("Expanding new game types across all CEFR levels")
print("=" * 60)

total_added = 0

for label, game_map in [("A1", A1_GAMES), ("A2", A2_GAMES), ("C1", C1_GAMES), ("C2", C2_GAMES)]:
    print(f"\n--- {label} ---")
    for dest_key, games in sorted(game_map.items()):
        num = int(dest_key.replace("dest", ""))
        before, after = add_games_to_dest(num, games)
        added = after - before
        total_added += added
        print(f"  {dest_key}: {before} -> {after} (+{added})")

print(f"\nTotal new games added: {total_added}")
print("Done.")
