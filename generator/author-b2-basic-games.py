#!/usr/bin/env python3
"""
Author new games for B2 Basic destinations (dest29-33).

Each destination currently has 7-10 games and needs to reach 25.
New games are inserted BEFORE the escaperoom and cronica (which stay at the end).

B2 Basic grammar focus:
  - Conditional (simple + perfect)
  - Subjunctive in adverbial clauses (aunque + subj, para que + subj, antes de que + subj)
  - Passive voice (ser + participio, se pasiva)
  - Advanced relative clauses (cuyo, en el que, lo que)
  - Reported speech transformations (dijo que + imperfecto/pluscuamperfecto)
  - Concessive clauses (a pesar de que, si bien)
  - Complex conditionals (si hubiera..., habría...)

Story context:
  dest29: "El silencio avanza" — silence/loss
  dest30: "La sombra de Yaguará" — shadow/identity
  dest31: "Hablar con los demás" — discourse/persuasion
  dest32: "¿Quién traicionó a quién?" — betrayal/perspective
  dest33: "El lugar donde van los nombres" — memory/oblivion
"""

import json
import os
import copy

CONTENT_DIR = "/home/babelfree.com/public_html/content"
TARGET_TOTAL = 25


# ─────────────────────────────────────────────────────────────
# New games for dest29: "El silencio avanza"
# Theme: silence, loss, existential threat, naming
# ─────────────────────────────────────────────────────────────
DEST29_NEW_GAMES = [
    # 1. conjugation — conditional + imperfect subjunctive
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga el verbo en condicional simple o imperfecto de subjuntivo según el contexto.",
        "questions": [
            {
                "verb": "desaparecer (condicional)",
                "subject": "el nombre",
                "answer": "desaparecería",
                "options": ["desaparecería", "desapareciera", "desaparece", "desapareció"]
            },
            {
                "verb": "recordar (imperfecto de subjuntivo)",
                "subject": "nosotros",
                "answer": "recordáramos",
                "options": ["recordáramos", "recordaríamos", "recordamos", "recordábamos"]
            },
            {
                "verb": "poder (condicional)",
                "subject": "yo",
                "answer": "podría",
                "options": ["podría", "pudiera", "puedo", "podía"]
            },
            {
                "verb": "nombrar (imperfecto de subjuntivo)",
                "subject": "alguien",
                "answer": "nombrara",
                "options": ["nombrara", "nombraría", "nombra", "nombró"]
            }
        ]
    },
    # 2. sorting — passive voice vs active voice
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada oración según su voz: activa o pasiva.",
        "categories": ["Voz activa", "Voz pasiva"],
        "items": [
            {"text": "El Silencio borró los nombres.", "category": "Voz activa"},
            {"text": "Los nombres fueron borrados por el Silencio.", "category": "Voz pasiva"},
            {"text": "Cóndor Viejo vio la mancha gris.", "category": "Voz activa"},
            {"text": "La mancha gris fue vista desde las alturas.", "category": "Voz pasiva"},
            {"text": "Se perdieron las voces del río.", "category": "Voz pasiva"},
            {"text": "El río perdió sus voces.", "category": "Voz activa"},
            {"text": "Las historias fueron olvidadas por el pueblo.", "category": "Voz pasiva"},
            {"text": "Yaguará recogió las palabras que quedaban.", "category": "Voz activa"}
        ]
    },
    # 3. fib — concessive clauses (aunque + subjunctive)
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con la forma correcta del subjuntivo en cláusulas concesivas.",
        "questions": [
            {
                "sentence": "Aunque el Silencio ___ cada vez más fuerte, los guías no se rinden.",
                "answer": "sea",
                "options": ["sea", "es", "sería", "fue"]
            },
            {
                "sentence": "Aunque nadie ___ una solución todavía, seguiremos buscando.",
                "answer": "tenga",
                "options": ["tenga", "tiene", "tendría", "tuvo"]
            },
            {
                "sentence": "Aunque Cóndor Viejo ___ viejo, su vista sigue siendo la más aguda.",
                "answer": "sea",
                "options": ["sea", "es", "fuera", "sería"]
            },
            {
                "sentence": "A pesar de que el mundo ___ olvidándose, las palabras guardadas en el agua resisten.",
                "answer": "esté",
                "options": ["esté", "está", "estaría", "estaba"]
            }
        ]
    },
    # 4. narrative — passive voice and conditional
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee este fragmento sobre la avanzada del Silencio Gris. Observa el uso de la voz pasiva y el condicional.",
        "text": "Los primeros nombres fueron borrados sin que nadie lo notara. Eran nombres pequeños: el nombre de una flor silvestre, el nombre de un arroyo que solo los viejos conocían. Después fueron olvidados los nombres de los senderos. Los caminos que habían sido recorridos durante generaciones dejaron de tener nombre, y sin nombre, dejaron de ser recorridos. Si alguien hubiera prestado atención antes, quizás el Silencio habría sido detenido. Pero el olvido es silencioso — avanza sin ser visto, y cuando es descubierto, ya es demasiado tarde."
    },
    # 5. pair — matching conditional causes with consequences
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada condición hipotética con su consecuencia lógica.",
        "pairs": [
            ["Si los guías hubieran actuado antes,", "el Silencio no habría avanzado tanto."],
            ["Si nadie nombrara las cosas,", "el mundo se olvidaría de sí mismo."],
            ["Si Yaguará pudiera volar como Cóndor,", "vería la mancha gris desde arriba."],
            ["Si el río recordara sus propios nombres,", "el olvido no podría borrarlos."],
            ["Si protegiéramos las historias,", "las palabras no se perderían."]
        ]
    },
    # 6. dictation — B2 conditional + passive
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe. Presta atención a la voz pasiva y al condicional.",
        "audio": "Si los nombres no hubieran sido olvidados, el Silencio Gris no habría encontrado espacio para avanzar. Las palabras que fueron guardadas en el agua todavía resisten.",
        "answer": "Si los nombres no hubieran sido olvidados, el Silencio Gris no habría encontrado espacio para avanzar. Las palabras que fueron guardadas en el agua todavía resisten."
    },
    # 7. translation — conditional perfect
    {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Traduce al español usando el condicional perfecto y las palabras disponibles.",
        "source": "If the silence had not advanced, the names would have survived.",
        "words": ["Si", "el", "silencio", "no", "hubiera", "avanzado,", "los", "nombres", "habrían", "sobrevivido."],
        "answer": "Si el silencio no hubiera avanzado, los nombres habrían sobrevivido."
    },
    # 8. builder — purpose clause (para que + subjunctive)
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras para formar una oración con cláusula de propósito.",
        "words": ["Yaguará", "recoge", "los", "nombres", "para", "que", "no", "sean", "olvidados", "por", "el", "Silencio."],
        "answer": "Yaguará recoge los nombres para que no sean olvidados por el Silencio."
    },
    # 9. listening — advanced relative clauses
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha la descripción de Tortuga Marina y responde.",
        "audio": "El lugar en el que vivimos tiene memoria. Las raíces, cuyas historias son más antiguas que las nuestras, guardan los nombres que nosotros olvidamos. El río, a través del cual viajan todas las voces, lleva cada nombre a un lugar seguro. Pero si el Silencio Gris llega al río, lo que fue guardado podría perderse para siempre.",
        "question": "Según Tortuga Marina, ¿qué función cumple el río?",
        "answer": "El río transporta y guarda las voces y los nombres en un lugar seguro.",
        "options": [
            "El río transporta y guarda las voces y los nombres en un lugar seguro.",
            "El río destruye los nombres que ya no se usan.",
            "El río crea nombres nuevos cada día.",
            "El río no tiene ninguna función especial."
        ]
    },
    # 10. fib — reported speech transformations
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Transforma al discurso reportado. Completa con la forma verbal correcta.",
        "questions": [
            {
                "sentence": "Cóndor Viejo dijo: «Veo una mancha gris.» → Cóndor Viejo dijo que ___ una mancha gris.",
                "answer": "veía",
                "options": ["veía", "ve", "vería", "vio"]
            },
            {
                "sentence": "Delfín dijo: «El agua recuerda.» → Delfín dijo que el agua ___.",
                "answer": "recordaba",
                "options": ["recordaba", "recuerda", "recordaría", "recordó"]
            },
            {
                "sentence": "Tortuga dijo: «Hemos protegido los nombres.» → Tortuga dijo que ___ protegido los nombres.",
                "answer": "habían",
                "options": ["habían", "han", "habrían", "hubieran"]
            },
            {
                "sentence": "Colibrí dijo: «Recogeré las palabras.» → Colibrí dijo que ___ las palabras.",
                "answer": "recogería",
                "options": ["recogería", "recoge", "recogiera", "recogió"]
            }
        ]
    },
    # 11. conversation — adverbial clauses + conditional
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Los guías planean una estrategia contra el Silencio. Usa cláusulas adverbiales y condicionales.",
        "turns": [
            {
                "dialogue": [
                    {"speaker": "A", "name": "Delfín Rosado", "text": "Antes de que el Silencio llegue al río, necesitamos un plan. ¿Qué propones, Yaguará?"},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Propongo que cada guía guarde los nombres de su territorio para que, aunque el Silencio avance, las palabras estén protegidas en diferentes lugares. Si las distribuimos, no podrán ser borradas todas a la vez.",
                    "No sé qué hacer.",
                    "Hay que correr."
                ],
                "answer": "Propongo que cada guía guarde los nombres de su territorio para que, aunque el Silencio avance, las palabras estén protegidas en diferentes lugares. Si las distribuimos, no podrán ser borradas todas a la vez."
            },
            {
                "dialogue": [
                    {"speaker": "A", "name": "Tortuga Marina", "text": "Eso funcionaría a menos que el Silencio encontrara todos los escondites. ¿Cómo nos aseguramos de que los lugares sean secretos?"},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Los nombres serían guardados en la memoria viva de las personas, no en objetos físicos. Para que un nombre sea borrado, alguien tendría que olvidarlo, y si cada persona recuerda al menos un nombre, el olvido no podría completarse.",
                    "Con una puerta grande.",
                    "No importa, ya nos vencieron."
                ],
                "answer": "Los nombres serían guardados en la memoria viva de las personas, no en objetos físicos. Para que un nombre sea borrado, alguien tendría que olvidarlo, y si cada persona recuerda al menos un nombre, el olvido no podría completarse."
            }
        ]
    },
    # 12. sorting — subjunctive triggers (adverbial clauses)
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada conector según si exige subjuntivo o indicativo.",
        "categories": ["Exige subjuntivo", "Admite indicativo"],
        "items": [
            {"text": "para que", "category": "Exige subjuntivo"},
            {"text": "antes de que", "category": "Exige subjuntivo"},
            {"text": "a menos que", "category": "Exige subjuntivo"},
            {"text": "sin que", "category": "Exige subjuntivo"},
            {"text": "porque", "category": "Admite indicativo"},
            {"text": "después de que (pasado)", "category": "Admite indicativo"},
            {"text": "ya que", "category": "Admite indicativo"},
            {"text": "puesto que", "category": "Admite indicativo"}
        ]
    },
    # 13. builder — concessive clause
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras para formar una oración concesiva.",
        "words": ["Aunque", "el", "Silencio", "Gris", "haya", "borrado", "los", "nombres,", "las", "raíces", "aún", "guardan", "la", "memoria."],
        "answer": "Aunque el Silencio Gris haya borrado los nombres, las raíces aún guardan la memoria."
    },
    # 14. narrative — with comprehension embedded in story context
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee el testimonio de Colibrí sobre lo que fue perdido.",
        "text": "Colibrí contó que había volado sobre el valle esa mañana y que todo había cambiado. Dijo que los colores, que antes eran brillantes, se habían apagado. Explicó que las flores, cuyo perfume solía guiarlo, ya no olían a nada. Afirmó que si hubiera sabido que el Silencio avanzaba tan rápido, habría avisado antes. Pero nadie lo habría creído, porque el olvido avanza sin ser visto. Fue descubierto demasiado tarde, cuando los nombres ya habían sido borrados."
    },
    # 15. conjugation — passive voice (ser + participio)
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga el verbo ser en el tiempo indicado para formar la voz pasiva.",
        "questions": [
            {
                "verb": "ser borrado (pretérito)",
                "subject": "los nombres",
                "answer": "fueron borrados",
                "options": ["fueron borrados", "son borrados", "serían borrados", "eran borrados"]
            },
            {
                "verb": "ser protegido (condicional)",
                "subject": "las palabras",
                "answer": "serían protegidas",
                "options": ["serían protegidas", "son protegidas", "fueron protegidas", "fueran protegidas"]
            },
            {
                "verb": "ser guardado (pretérito perfecto)",
                "subject": "la memoria",
                "answer": "ha sido guardada",
                "options": ["ha sido guardada", "fue guardada", "sería guardada", "es guardada"]
            },
            {
                "verb": "ser olvidado (pluscuamperfecto)",
                "subject": "el río",
                "answer": "había sido olvidado",
                "options": ["había sido olvidado", "fue olvidado", "ha sido olvidado", "sería olvidado"]
            }
        ]
    },
    # 16. listening — reported speech
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha el relato y responde.",
        "audio": "Cóndor Viejo contó que había volado sobre los Llanos antes del amanecer. Dijo que la mancha gris, que la semana anterior solo cubría una pequeña parte del valle, ya se había extendido hasta el borde del río. Afirmó que si las comunidades no actuaban pronto, los nombres serían borrados antes de que alguien pudiera salvarlos.",
        "question": "¿Qué cambio notó Cóndor Viejo respecto a la semana anterior?",
        "answer": "La mancha gris se había extendido desde una pequeña parte del valle hasta el borde del río.",
        "options": [
            "La mancha gris se había extendido desde una pequeña parte del valle hasta el borde del río.",
            "Los ríos habían desaparecido completamente.",
            "Los animales habían dejado de hablar entre ellos.",
            "El sol había dejado de salir por la mañana."
        ]
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest30: "La sombra de Yaguará"
# Theme: shadow, identity, inner enemy, doubt/courage
# ─────────────────────────────────────────────────────────────
DEST30_NEW_GAMES = [
    # 1. conjugation — conditional for hypotheticals about identity
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga el verbo en condicional simple. Yaguará imagina otra versión de sí misma.",
        "questions": [
            {
                "verb": "ser (condicional)",
                "subject": "yo",
                "answer": "sería",
                "options": ["sería", "fuera", "soy", "era"]
            },
            {
                "verb": "tener (condicional)",
                "subject": "yo",
                "answer": "tendría",
                "options": ["tendría", "tuviera", "tengo", "tenía"]
            },
            {
                "verb": "saber (condicional)",
                "subject": "yo",
                "answer": "sabría",
                "options": ["sabría", "supiera", "sé", "sabía"]
            },
            {
                "verb": "decir (condicional)",
                "subject": "la sombra",
                "answer": "diría",
                "options": ["diría", "dijera", "dice", "decía"]
            }
        ]
    },
    # 2. sorting — indicative (what IS) vs subjunctive (what is FEARED/DOUBTED)
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada oración según si expresa una realidad (indicativo) o un miedo/duda (subjuntivo).",
        "categories": ["Realidad (indicativo)", "Miedo o duda (subjuntivo)"],
        "items": [
            {"text": "Yaguará comete errores.", "category": "Realidad (indicativo)"},
            {"text": "Temo que nunca pueda hablar bien.", "category": "Miedo o duda (subjuntivo)"},
            {"text": "Candelaria la escucha.", "category": "Realidad (indicativo)"},
            {"text": "Dudo que alguien me tome en serio.", "category": "Miedo o duda (subjuntivo)"},
            {"text": "La Sombra habla con su voz.", "category": "Realidad (indicativo)"},
            {"text": "No creo que los errores signifiquen fracaso.", "category": "Miedo o duda (subjuntivo)"},
            {"text": "El viaje ha sido largo.", "category": "Realidad (indicativo)"},
            {"text": "Es posible que el miedo sea más fuerte que la voluntad.", "category": "Miedo o duda (subjuntivo)"}
        ]
    },
    # 3. narrative — identity and passive voice
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee esta reflexión de Yaguará sobre su sombra. Observa cómo la voz pasiva expresa lo que la sombra hace.",
        "text": "La Sombra no es una criatura. Es una parte de mí que fue ignorada durante mucho tiempo. Los miedos que no son enfrentados se convierten en sombras. Las dudas que no son dichas en voz alta crecen en el silencio. Cada error que fue escondido en vez de aceptado se hizo más pesado. Si yo hubiera escuchado a mi sombra antes, habría entendido que no era mi enemiga. Era mi maestra más honesta. Lo que es temido se vuelve poderoso. Lo que es aceptado pierde su poder sobre nosotros."
    },
    # 4. fib — passive voice with ser
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con la forma correcta de la voz pasiva (ser + participio).",
        "questions": [
            {
                "sentence": "Los miedos que no son ___ se convierten en sombras.",
                "answer": "enfrentados",
                "options": ["enfrentados", "enfrentando", "enfrentar", "enfrentaron"]
            },
            {
                "sentence": "Las palabras de La Sombra fueron ___ como una verdad absoluta, pero solo eran verdades a medias.",
                "answer": "interpretadas",
                "options": ["interpretadas", "interpretando", "interpretan", "interpretaron"]
            },
            {
                "sentence": "La identidad de Yaguará ha sido ___ por cada experiencia del viaje.",
                "answer": "moldeada",
                "options": ["moldeada", "moldeando", "moldeó", "moldearía"]
            },
            {
                "sentence": "Si el miedo hubiera sido ___ antes, la sombra no habría crecido tanto.",
                "answer": "reconocido",
                "options": ["reconocido", "reconociendo", "reconoce", "reconocería"]
            }
        ]
    },
    # 5. builder — although + subjunctive (inner struggle)
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras para formar una oración concesiva sobre el coraje.",
        "words": ["Aunque", "la", "Sombra", "diga", "que", "no", "soy", "suficiente,", "cada", "paso", "demuestra", "lo", "contrario."],
        "answer": "Aunque la Sombra diga que no soy suficiente, cada paso demuestra lo contrario."
    },
    # 6. listening — interpreting La Sombra's words with reported speech
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha lo que dijo La Sombra y cómo Yaguará lo interpreta. Responde la pregunta.",
        "audio": "La Sombra dijo que Yaguará nunca sería suficiente. Dijo que sus palabras no eran suyas y que los demás notaban cada error que cometía. Pero Yaguará respondió que no creía que eso fuera cierto. Afirmó que sus amigos la entendían y que los errores no la definían. Dijo que aunque tuviera miedo, seguiría caminando.",
        "question": "¿Cuál es la diferencia principal entre lo que dice La Sombra y lo que responde Yaguará?",
        "answer": "La Sombra habla en términos absolutos (nunca, nadie), mientras Yaguará matiza y busca la verdad parcial.",
        "options": [
            "La Sombra habla en términos absolutos (nunca, nadie), mientras Yaguará matiza y busca la verdad parcial.",
            "La Sombra usa el subjuntivo y Yaguará usa el indicativo.",
            "Las dos dicen exactamente lo mismo pero con palabras diferentes.",
            "Yaguará está de acuerdo con todo lo que dice La Sombra."
        ]
    },
    # 7. translation — confronting the shadow
    {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Traduce al español usando las palabras disponibles.",
        "source": "Although I make mistakes, I do not believe that they define me.",
        "words": ["Aunque", "cometa", "errores,", "no", "creo", "que", "me", "definan."],
        "answer": "Aunque cometa errores, no creo que me definan."
    },
    # 8. fib — adverbial clauses (antes de que, sin que, para que)
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa las oraciones con cláusulas adverbiales que requieren subjuntivo.",
        "questions": [
            {
                "sentence": "Yaguará enfrentó a La Sombra antes de que esta ___ más fuerte.",
                "answer": "se hiciera",
                "options": ["se hiciera", "se hizo", "se haría", "se hace"]
            },
            {
                "sentence": "Candelaria habló sin que nadie se lo ___.",
                "answer": "pidiera",
                "options": ["pidiera", "pidió", "pediría", "pide"]
            },
            {
                "sentence": "Yaguará escucha a La Sombra para que el miedo no ___ en silencio.",
                "answer": "crezca",
                "options": ["crezca", "crece", "crecería", "creció"]
            },
            {
                "sentence": "La Sombra no desaparecerá a menos que Yaguará la ___.",
                "answer": "acepte",
                "options": ["acepte", "acepta", "aceptaría", "aceptó"]
            }
        ]
    },
    # 9. pair — reported speech: direct vs indirect
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada cita directa con su versión en discurso reportado.",
        "pairs": [
            ["La Sombra dijo: «No vas a poder.»", "La Sombra dijo que no iba a poder."],
            ["Yaguará respondió: «No te creo.»", "Yaguará respondió que no le creía."],
            ["Candelaria afirmó: «Todos tenemos una sombra.»", "Candelaria afirmó que todos tenían una sombra."],
            ["La Sombra insistió: «Nunca serás parte de este mundo.»", "La Sombra insistió en que nunca sería parte de ese mundo."],
            ["Yaguará declaró: «El coraje es caminar a pesar del miedo.»", "Yaguará declaró que el coraje era caminar a pesar del miedo."]
        ]
    },
    # 10. dictation — B2 concessive + conditional
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe. Presta atención a las cláusulas concesivas y los tiempos condicionales.",
        "audio": "Aunque la Sombra hable con mi voz, no es toda mi verdad. Si hubiera dejado que el miedo me detuviera, nunca habría llegado hasta aquí. El coraje no es la ausencia del miedo sino la decisión de seguir caminando.",
        "answer": "Aunque la Sombra hable con mi voz, no es toda mi verdad. Si hubiera dejado que el miedo me detuviera, nunca habría llegado hasta aquí. El coraje no es la ausencia del miedo sino la decisión de seguir caminando."
    },
    # 11. builder — conditional perfect
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras para formar una hipótesis sobre el pasado.",
        "words": ["Si", "Yaguará", "no", "hubiera", "enfrentado", "a", "su", "sombra,", "el", "miedo", "habría", "crecido", "sin", "control."],
        "answer": "Si Yaguará no hubiera enfrentado a su sombra, el miedo habría crecido sin control."
    },
    # 12. conjugation — subjunctive in noun clauses (doubt/emotion)
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga el verbo en presente de subjuntivo. Expresa duda o emoción.",
        "questions": [
            {
                "verb": "significar (subjuntivo)",
                "subject": "los errores",
                "answer": "signifiquen",
                "options": ["signifiquen", "significan", "significarían", "significaron"]
            },
            {
                "verb": "definir (subjuntivo)",
                "subject": "una sola cosa",
                "answer": "defina",
                "options": ["defina", "define", "definiría", "definió"]
            },
            {
                "verb": "pertenecer (subjuntivo)",
                "subject": "las palabras",
                "answer": "pertenezcan",
                "options": ["pertenezcan", "pertenecen", "pertenecerían", "pertenecieron"]
            },
            {
                "verb": "detener (subjuntivo)",
                "subject": "la sombra",
                "answer": "detenga",
                "options": ["detenga", "detiene", "detendría", "detuvo"]
            }
        ]
    },
    # 13. sorting — what La Sombra says (absolute) vs what Yaguará believes (nuanced)
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada afirmación: ¿la dice La Sombra (verdad absoluta/negativa) o Yaguará (verdad matizada)?",
        "categories": ["La Sombra (absoluto)", "Yaguará (matizado)"],
        "items": [
            {"text": "Nunca vas a hablar bien.", "category": "La Sombra (absoluto)"},
            {"text": "Hablar bien no significa hablar perfecto.", "category": "Yaguará (matizado)"},
            {"text": "Todos notan tus errores.", "category": "La Sombra (absoluto)"},
            {"text": "Los errores son parte del camino, no el final.", "category": "Yaguará (matizado)"},
            {"text": "Las palabras no son tuyas.", "category": "La Sombra (absoluto)"},
            {"text": "Las palabras son de quien las usa con intención.", "category": "Yaguará (matizado)"},
            {"text": "No vas a poder.", "category": "La Sombra (absoluto)"},
            {"text": "Tiemblo, pero que tiemble no significa que me detenga.", "category": "Yaguará (matizado)"}
        ]
    },
    # 14. listening — Candelaria's wisdom about shadows
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha la reflexión de Candelaria y responde.",
        "audio": "Candelaria dijo que todas las personas tenían una sombra, incluso ella. Explicó que su sombra le decía que era demasiado joven para que alguien la escuchara. Pero añadió que había aprendido algo importante: que aunque la sombra dijera verdades a medias, no había que dejar que una verdad a medias se convirtiera en toda la historia. Afirmó que la diferencia entre un error y un fracaso era la actitud con la que se enfrentaba.",
        "question": "¿Cuál es la lección principal de Candelaria sobre las sombras?",
        "answer": "Que las sombras dicen verdades a medias y no debemos dejar que se conviertan en toda nuestra historia.",
        "options": [
            "Que las sombras dicen verdades a medias y no debemos dejar que se conviertan en toda nuestra historia.",
            "Que las sombras siempre mienten y no debemos escucharlas nunca.",
            "Que las sombras solo aparecen cuando somos jóvenes.",
            "Que la mejor solución es ignorar la sombra completamente."
        ]
    },
    # 15. narrative — inner monologue with advanced grammar
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee el monólogo interior de Yaguará después de enfrentar a La Sombra.",
        "text": "Si me hubieran preguntado antes de este viaje quién era yo, habría respondido con certezas. Ahora, después de haber sido desafiada por mi propia sombra, las respuestas son más complicadas. No creo que los errores me definan, aunque a veces lo parezca. Las palabras que fueron dichas con miedo no fueron menos verdaderas que las dichas con confianza. Cada paso que fue dado con duda fue tan real como cada paso dado con seguridad. La Sombra será siempre parte de mí, pero ya no será la que decida por mí."
    },
    # 16. fib — advanced relative clauses (cuyo, en el que, lo que)
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con el pronombre relativo correcto.",
        "questions": [
            {
                "sentence": "La Sombra, ___ voz se parece a la de Yaguará, dice las cosas que ella no quiere oír.",
                "answer": "cuya",
                "options": ["cuya", "que", "la cual", "quien"]
            },
            {
                "sentence": "El espejo, en ___ Yaguará vio su reflejo verdadero, se rompió al final.",
                "answer": "el que",
                "options": ["el que", "que", "cuyo", "lo cual"]
            },
            {
                "sentence": "___ dijo La Sombra no era del todo falso, pero tampoco era toda la verdad.",
                "answer": "Lo que",
                "options": ["Lo que", "Que", "El que", "Cuyo"]
            },
            {
                "sentence": "El camino, a lo largo ___ Yaguará ha viajado, la ha transformado.",
                "answer": "del cual",
                "options": ["del cual", "que", "cuyo", "lo que"]
            }
        ]
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest31: "Hablar con los demás"
# Theme: discourse, persuasion, public speech
# ─────────────────────────────────────────────────────────────
DEST31_NEW_GAMES = [
    # 1. conjugation — subjunctive in purpose/concessive clauses (formal speech)
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga el verbo en presente de subjuntivo para cláusulas de propósito y concesión.",
        "questions": [
            {
                "verb": "proteger (subjuntivo, nosotros)",
                "subject": "nosotros",
                "answer": "protejamos",
                "options": ["protejamos", "protegemos", "protegiéramos", "protegeríamos"]
            },
            {
                "verb": "tomar (subjuntivo, nosotros)",
                "subject": "nosotros",
                "answer": "tomemos",
                "options": ["tomemos", "tomamos", "tomáramos", "tomaríamos"]
            },
            {
                "verb": "destruir (subjuntivo, ellos)",
                "subject": "las compañías",
                "answer": "destruyan",
                "options": ["destruyan", "destruyen", "destruirían", "destruyeron"]
            },
            {
                "verb": "considerar (subjuntivo, usted)",
                "subject": "el consejo",
                "answer": "considere",
                "options": ["considere", "considera", "considerara", "consideraría"]
            }
        ]
    },
    # 2. pair — formal vs informal register
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada expresión informal con su equivalente formal.",
        "pairs": [
            ["Yo creo que sí.", "Considero que la evidencia apoya esta posición."],
            ["Eso no está bien.", "Esta situación resulta inaceptable."],
            ["Hay que hacer algo ya.", "Es imperativo que se tomen medidas inmediatas."],
            ["No estoy de acuerdo.", "Discrepo respetuosamente de esa perspectiva."],
            ["¿Y qué hacemos?", "¿Cuál sería el curso de acción más apropiado?"]
        ]
    },
    # 3. narrative — a structured argument with discourse markers
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee el discurso formal que Yaguará preparó. Identifica los marcadores del discurso.",
        "text": "Estimados miembros del consejo: En primer lugar, quiero agradecer la oportunidad de hablar. El motivo de mi intervención es la situación del bosque, cuya destrucción se ha acelerado en los últimos meses. Además, es necesario considerar que sin los árboles más antiguos, las fuentes de agua se secarán. No obstante, comprendo que las familias necesitan trabajar. Por otra parte, existen alternativas que han sido implementadas con éxito en otras comunidades. En conclusión, proteger el bosque no es un lujo sino una necesidad, para que las generaciones futuras tengan un lugar donde vivir."
    },
    # 4. fib — conditional for diplomatic suggestions
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con el condicional para expresar sugerencias diplomáticas.",
        "questions": [
            {
                "sentence": "Si yo fuera usted, ___ otra opción antes de decidir.",
                "answer": "consideraría",
                "options": ["consideraría", "considero", "considerara", "consideré"]
            },
            {
                "sentence": "¿No ___ mejor buscar una solución que beneficie a todos?",
                "answer": "sería",
                "options": ["sería", "es", "fuera", "fue"]
            },
            {
                "sentence": "Yo en su lugar ___ la propuesta con más cuidado antes de rechazarla.",
                "answer": "analizaría",
                "options": ["analizaría", "analizo", "analizara", "analicé"]
            },
            {
                "sentence": "Quizás ___ conveniente escuchar a todas las partes antes de votar.",
                "answer": "sería",
                "options": ["sería", "es", "fuera", "fue"]
            }
        ]
    },
    # 5. listening — argument structure recognition
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha el contraargumento del ganadero y responde.",
        "audio": "El ganadero dijo: «En primer lugar, mi familia ha vivido aquí durante tres generaciones. Además, el ganado es nuestra única fuente de ingresos. Sin embargo, reconozco que la tierra se está secando. Por lo tanto, estaría dispuesto a considerar alternativas, siempre que no pongan en riesgo la economía de mi familia.»",
        "question": "¿Qué posición tiene el ganadero?",
        "answer": "Está dispuesto a considerar alternativas, pero exige que no pongan en riesgo la economía familiar.",
        "options": [
            "Está dispuesto a considerar alternativas, pero exige que no pongan en riesgo la economía familiar.",
            "Se niega completamente a cualquier cambio.",
            "Quiere abandonar la ganadería inmediatamente.",
            "No le importa lo que pase con el bosque."
        ]
    },
    # 6. translation — discourse markers in argument
    {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Traduce al español usando las palabras disponibles.",
        "source": "Although logging creates jobs, the environmental damage exceeds the economic benefits.",
        "words": ["Aunque", "la", "tala", "genere", "empleo,", "el", "daño", "ambiental", "supera", "los", "beneficios", "económicos."],
        "answer": "Aunque la tala genere empleo, el daño ambiental supera los beneficios económicos."
    },
    # 7. dictation — formal argumentation
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe. Presta atención a los marcadores del discurso y al registro formal.",
        "audio": "En primer lugar, es necesario reconocer que la tala indiscriminada ha causado daños irreversibles. Además, las comunidades que dependen del bosque se han visto afectadas. Por lo tanto, proponemos que se establezca una zona de protección.",
        "answer": "En primer lugar, es necesario reconocer que la tala indiscriminada ha causado daños irreversibles. Además, las comunidades que dependen del bosque se han visto afectadas. Por lo tanto, proponemos que se establezca una zona de protección."
    },
    # 8. sorting — discourse marker function
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada expresión según si introduce una causa o una consecuencia.",
        "categories": ["Introduce causa", "Introduce consecuencia"],
        "items": [
            {"text": "Dado que", "category": "Introduce causa"},
            {"text": "Puesto que", "category": "Introduce causa"},
            {"text": "Ya que", "category": "Introduce causa"},
            {"text": "Debido a que", "category": "Introduce causa"},
            {"text": "Por lo tanto", "category": "Introduce consecuencia"},
            {"text": "En consecuencia", "category": "Introduce consecuencia"},
            {"text": "De ahí que", "category": "Introduce consecuencia"},
            {"text": "Por consiguiente", "category": "Introduce consecuencia"}
        ]
    },
    # 9. builder — formal argument with purpose clause
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena las palabras para formar un argumento formal con cláusula de propósito.",
        "words": ["Es", "necesario", "que", "protejamos", "el", "bosque", "para", "que", "las", "generaciones", "futuras", "tengan", "un", "lugar", "donde", "vivir."],
        "answer": "Es necesario que protejamos el bosque para que las generaciones futuras tengan un lugar donde vivir."
    },
    # 10. fib — passive voice in formal reports
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con la forma pasiva correcta, como en un informe formal.",
        "questions": [
            {
                "sentence": "Los árboles más antiguos fueron ___ sin autorización del consejo.",
                "answer": "talados",
                "options": ["talados", "talando", "talar", "talarían"]
            },
            {
                "sentence": "La propuesta fue ___ por la mayoría de los miembros del consejo.",
                "answer": "aprobada",
                "options": ["aprobada", "aprobando", "aprobar", "aprobaría"]
            },
            {
                "sentence": "Las alternativas económicas han sido ___ por varias comunidades de la región.",
                "answer": "implementadas",
                "options": ["implementadas", "implementando", "implementar", "implementarían"]
            },
            {
                "sentence": "Si la zona de protección hubiera sido ___ antes, los daños habrían sido menores.",
                "answer": "establecida",
                "options": ["establecida", "estableciendo", "establecer", "establecería"]
            }
        ]
    },
    # 11. conversation — diplomatic disagreement
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Practica el desacuerdo diplomático. Usa el condicional y marcadores de contraste.",
        "turns": [
            {
                "dialogue": [
                    {"speaker": "A", "name": "Representante de la empresa", "text": "La construcción del camino traerá progreso a la región. No hay razón para oponerse."},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Entiendo su perspectiva. No obstante, me gustaría señalar que el concepto de progreso debería incluir la preservación del medio ambiente. ¿No sería posible encontrar una ruta que no atravesara el bosque más antiguo?",
                    "Eso es mentira.",
                    "No me importa el progreso."
                ],
                "answer": "Entiendo su perspectiva. No obstante, me gustaría señalar que el concepto de progreso debería incluir la preservación del medio ambiente. ¿No sería posible encontrar una ruta que no atravesara el bosque más antiguo?"
            },
            {
                "dialogue": [
                    {"speaker": "A", "name": "Representante de la empresa", "text": "Pero los costos serían mucho más altos si cambiamos la ruta. La empresa no puede asumir esos gastos."},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Comprendo la preocupación económica. Sin embargo, habría que considerar los costos a largo plazo de destruir el ecosistema. Dado que el bosque provee agua a toda la región, su destrucción podría generar pérdidas mucho mayores en el futuro.",
                    "Pues que la empresa pague más.",
                    "Los costos no son mi problema."
                ],
                "answer": "Comprendo la preocupación económica. Sin embargo, habría que considerar los costos a largo plazo de destruir el ecosistema. Dado que el bosque provee agua a toda la región, su destrucción podría generar pérdidas mucho mayores en el futuro."
            }
        ]
    },
    # 12. conjugation — passive voice (ser + participio) in formal register
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Forma la voz pasiva en el tiempo indicado para un informe formal.",
        "questions": [
            {
                "verb": "ser aprobado (pretérito)",
                "subject": "la propuesta",
                "answer": "fue aprobada",
                "options": ["fue aprobada", "es aprobada", "sería aprobada", "sea aprobada"]
            },
            {
                "verb": "ser presentado (pretérito perfecto)",
                "subject": "los argumentos",
                "answer": "han sido presentados",
                "options": ["han sido presentados", "fueron presentados", "son presentados", "serían presentados"]
            },
            {
                "verb": "ser considerado (condicional)",
                "subject": "todas las opciones",
                "answer": "serían consideradas",
                "options": ["serían consideradas", "son consideradas", "fueron consideradas", "fueran consideradas"]
            },
            {
                "verb": "ser rechazado (pluscuamperfecto de subjuntivo)",
                "subject": "la oferta",
                "answer": "hubiera sido rechazada",
                "options": ["hubiera sido rechazada", "había sido rechazada", "fue rechazada", "sería rechazada"]
            }
        ]
    },
    # 13. pair — concessive connectors with their functions
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada conector concesivo con la oración que lo completa correctamente.",
        "pairs": [
            ["Aunque la tala genere empleo,", "los daños ambientales son irreversibles."],
            ["A pesar de que la empresa prometió compensaciones,", "las comunidades no han recibido nada."],
            ["Si bien el camino facilitaría el transporte,", "destruiría el hábitat de cientos de especies."],
            ["Por más que Don Próspero insista en los beneficios,", "la evidencia muestra lo contrario."],
            ["Aun cuando la mayoría vote a favor,", "la decisión debe basarse en evidencia y no en presión."]
        ]
    },
    # 14. narrative — the art of persuasion
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee la reflexión de Candelaria sobre el arte de persuadir.",
        "text": "Candelaria le dijo a Yaguará que convencer a alguien no era lo mismo que ganarle una discusión. Explicó que si la otra persona se sentía atacada, se cerraría, aunque tuvieras razón. Dijo que lo más importante era escuchar primero para que el otro supiera que lo respetabas. Añadió que los mejores argumentos no eran los más fuertes sino los que hacían que el otro quisiera cambiar de opinión por sí mismo. «Si hubieras gritado en el consejo», le dijo, «nadie te habría escuchado. Pero hablaste con respeto, y por eso te escucharon.»"
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest32: "¿Quién traicionó a quién?"
# Theme: betrayal, perspective, multiple testimonies
# ─────────────────────────────────────────────────────────────
DEST32_NEW_GAMES = [
    # 1. conjugation — conditional perfect for past hypotheticals
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga en condicional perfecto (habría + participio). Hipótesis sobre el pasado.",
        "questions": [
            {
                "verb": "confiar (condicional perfecto)",
                "subject": "la comunidad",
                "answer": "habría confiado",
                "options": ["habría confiado", "había confiado", "hubiera confiado", "confiaría"]
            },
            {
                "verb": "actuar (condicional perfecto)",
                "subject": "Tomás",
                "answer": "habría actuado",
                "options": ["habría actuado", "había actuado", "hubiera actuado", "actuaría"]
            },
            {
                "verb": "descubrir (condicional perfecto)",
                "subject": "nosotros",
                "answer": "habríamos descubierto",
                "options": ["habríamos descubierto", "habíamos descubierto", "hubiéramos descubierto", "descubriríamos"]
            },
            {
                "verb": "prevenir (condicional perfecto)",
                "subject": "alguien",
                "answer": "habría prevenido",
                "options": ["habría prevenido", "había prevenido", "hubiera prevenido", "preveniría"]
            }
        ]
    },
    # 2. sorting — reported speech: who said what
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada declaración según quién la dijo.",
        "categories": ["María dijo que...", "Tomás dijo que...", "Don Aurelio dijo que..."],
        "items": [
            {"text": "había visto a Tomás dar un papel a unos desconocidos", "category": "María dijo que..."},
            {"text": "el papel era un contrato de trabajo, no un mapa", "category": "Tomás dijo que..."},
            {"text": "lo que pasó en el mercado no era lo que todos pensaban", "category": "Don Aurelio dijo que..."},
            {"text": "los hombres fueron al bosque después de la conversación", "category": "María dijo que..."},
            {"text": "necesitaba dinero para la medicina de su madre", "category": "Tomás dijo que..."},
            {"text": "ambos estaban equivocados y él sabía la verdad", "category": "Don Aurelio dijo que..."}
        ]
    },
    # 3. narrative — analyzing perspective through grammar
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee las tres versiones del mismo evento. Observa cómo el punto de vista cambia la gramática.",
        "text": "Versión de María (testigo): «Vi a Tomás hablando con los hombres. Parecía nervioso. Les dio algo.» En discurso reportado: María afirmó que había visto a Tomás hablando con los hombres y que le había parecido nervioso. Dijo que les había dado algo. Versión de Tomás (acusado): «Hablé con ellos, pero me ofrecieron trabajo. El papel era un contrato.» Tomás admitió que había hablado con los hombres, pero explicó que le habían ofrecido trabajo y que el papel había sido un contrato. Versión de don Aurelio (mediador): «Hubo una conversación, pero no fue lo que ustedes piensan.» Don Aurelio confirmó que había habido una conversación, pero advirtió que los hechos no habían sido como los demás pensaban."
    },
    # 4. pair — cause and consequence in the betrayal
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada causa con su consecuencia usando condicionales perfectos.",
        "pairs": [
            ["Si la comunidad hubiera tenido alternativas económicas,", "Tomás no habría necesitado hablar con los hombres de Don Próspero."],
            ["Si María no hubiera visto la conversación,", "nadie habría sospechado de Tomás."],
            ["Si don Aurelio hubiera dicho la verdad desde el principio,", "el pueblo no habría perdido la confianza."],
            ["Si Don Próspero no hubiera ofrecido trabajo,", "la tentación no habría existido."],
            ["Si alguien hubiera protegido el mapa,", "la información no habría podido ser copiada."]
        ]
    },
    # 5. fib — advanced relative clauses in testimony
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con el pronombre relativo o la expresión relativa correcta.",
        "questions": [
            {
                "sentence": "El hombre, ___ nombre nadie conocía, fue visto en el mercado.",
                "answer": "cuyo",
                "options": ["cuyo", "que", "el cual", "quien"]
            },
            {
                "sentence": "El papel, ___ contenido sigue siendo un misterio, fue entregado esa tarde.",
                "answer": "cuyo",
                "options": ["cuyo", "que", "el cual", "donde"]
            },
            {
                "sentence": "La razón por ___ Tomás habló con ellos era económica, no ideológica.",
                "answer": "la cual",
                "options": ["la cual", "que", "cuya", "donde"]
            },
            {
                "sentence": "El mercado, en ___ ocurrió la conversación, está en el centro del pueblo.",
                "answer": "el cual",
                "options": ["el cual", "que", "cuyo", "donde"]
            }
        ]
    },
    # 6. listening — evaluating testimony credibility
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha el análisis de Yaguará sobre los testimonios y responde.",
        "audio": "Después de escuchar a todos, Yaguará llegó a una conclusión: los tres testimonios coincidían en que hubo una conversación en el mercado. Sin embargo, cada persona había interpretado los hechos de manera diferente. María, cuyo conflicto con Tomás era anterior, podría haber sido influenciada por sus emociones. Tomás, aunque admitió haber hablado con los hombres, tenía una razón comprensible. Y don Aurelio, quien afirmó saber la verdad, no la había compartido completamente.",
        "question": "¿Por qué Yaguará duda de la objetividad de María?",
        "answer": "Porque María tenía un conflicto previo con Tomás que podría haber influido en su interpretación.",
        "options": [
            "Porque María tenía un conflicto previo con Tomás que podría haber influido en su interpretación.",
            "Porque María no estuvo presente en el mercado.",
            "Porque María es amiga de Don Próspero.",
            "Porque María no habla español."
        ]
    },
    # 7. builder — complex conditional about the betrayal
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena para formar una hipótesis sobre la traición.",
        "words": ["Si", "la", "comunidad", "hubiera", "tenido", "alternativas", "económicas,", "nadie", "habría", "necesitado", "aceptar", "la", "oferta", "de", "Don", "Próspero."],
        "answer": "Si la comunidad hubiera tenido alternativas económicas, nadie habría necesitado aceptar la oferta de Don Próspero."
    },
    # 8. translation — perspective and reported speech
    {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Traduce al español usando las palabras disponibles.",
        "source": "María said that she had seen Tomás, but Tomás said that the paper had been a work contract.",
        "words": ["María", "dijo", "que", "había", "visto", "a", "Tomás,", "pero", "Tomás", "dijo", "que", "el", "papel", "había", "sido", "un", "contrato", "de", "trabajo."],
        "answer": "María dijo que había visto a Tomás, pero Tomás dijo que el papel había sido un contrato de trabajo."
    },
    # 9. dictation — nuanced judgment
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe. Presta atención al condicional perfecto y al discurso reportado.",
        "audio": "No habríamos llegado a este punto si la comunidad hubiera tenido alternativas. La pregunta no es quién traicionó a quién, sino por qué alguien se sintió tan desesperado como para hacerlo.",
        "answer": "No habríamos llegado a este punto si la comunidad hubiera tenido alternativas. La pregunta no es quién traicionó a quién, sino por qué alguien se sintió tan desesperado como para hacerlo."
    },
    # 10. fib — reported speech with subjunctive (speculation about motives)
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa las especulaciones sobre los motivos con la forma correcta.",
        "questions": [
            {
                "sentence": "Es posible que Tomás no ___ otra opción cuando aceptó el trabajo.",
                "answer": "tuviera",
                "options": ["tuviera", "tenía", "tendría", "tenga"]
            },
            {
                "sentence": "No creo que María ___ con mala intención, pero su perspectiva estaba condicionada.",
                "answer": "hablara",
                "options": ["hablara", "habló", "hablaría", "hable"]
            },
            {
                "sentence": "Dudo que don Aurelio ___ toda la verdad desde el principio.",
                "answer": "dijera",
                "options": ["dijera", "dijo", "diría", "diga"]
            },
            {
                "sentence": "Es probable que alguien más ___ involucrado sin que lo sepamos.",
                "answer": "estuviera",
                "options": ["estuviera", "estaba", "estaría", "esté"]
            }
        ]
    },
    # 11. conversation — cross-examining don Aurelio
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Entrevista a don Aurelio. Presiona con diplomacia para obtener la verdad.",
        "turns": [
            {
                "dialogue": [
                    {"speaker": "A", "name": "Yaguará", "text": "Don Aurelio, usted dijo que sabía la verdad. ¿Por qué no la compartió antes?"},
                    {"speaker": "B", "name": "Don Aurelio", "text": "???"}
                ],
                "options": [
                    "Porque si hubiera hablado antes, habría puesto en peligro a alguien. A veces la verdad se guarda no por cobardía, sino por protección. No es tan simple como parece.",
                    "No quería meterme en problemas.",
                    "No es asunto de nadie."
                ],
                "answer": "Porque si hubiera hablado antes, habría puesto en peligro a alguien. A veces la verdad se guarda no por cobardía, sino por protección. No es tan simple como parece."
            },
            {
                "dialogue": [
                    {"speaker": "A", "name": "Yaguará", "text": "Pero el pueblo necesita saber. Si usted tiene información que podría haber prevenido la pérdida de los árboles, ¿no era su responsabilidad compartirla?"},
                    {"speaker": "B", "name": "Don Aurelio", "text": "???"}
                ],
                "options": [
                    "Tiene razón. Si lo hubiera contado todo, quizás los árboles no habrían sido marcados. Pero también es posible que la persona que copió el mapa hubiera sido castigada injustamente, porque sus motivos eran más complicados de lo que parece.",
                    "Yo no tengo la culpa de nada.",
                    "Los árboles no me importan."
                ],
                "answer": "Tiene razón. Si lo hubiera contado todo, quizás los árboles no habrían sido marcados. Pero también es posible que la persona que copió el mapa hubiera sido castigada injustamente, porque sus motivos eran más complicados de lo que parece."
            }
        ]
    },
    # 12. sorting — fact vs interpretation
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada afirmación como un hecho comprobable o una interpretación personal.",
        "categories": ["Hecho comprobable", "Interpretación personal"],
        "items": [
            {"text": "Tomás habló con los hombres de Don Próspero en el mercado.", "category": "Hecho comprobable"},
            {"text": "Tomás parecía nervioso durante la conversación.", "category": "Interpretación personal"},
            {"text": "Tomás le dio un papel a uno de los hombres.", "category": "Hecho comprobable"},
            {"text": "El papel contenía información del bosque.", "category": "Interpretación personal"},
            {"text": "Los hombres fueron al bosque después de la conversación.", "category": "Hecho comprobable"},
            {"text": "Tomás traicionó al pueblo por dinero.", "category": "Interpretación personal"},
            {"text": "María y Tomás tuvieron una discusión un mes antes.", "category": "Hecho comprobable"},
            {"text": "El conflicto anterior influyó en el testimonio de María.", "category": "Interpretación personal"}
        ]
    },
    # 13. narrative — reflection on justice
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee la reflexión de Yaguará sobre la justicia y la perspectiva.",
        "text": "Después de hablar con todos, Yaguará entendió algo que antes no había comprendido: que la verdad no siempre es un hecho, a veces es una interpretación. María había visto lo que había visto, pero sus ojos estaban condicionados por un conflicto anterior. Tomás había hecho lo que había hecho, pero sus motivos eran comprensibles. Don Aurelio había callado lo que había callado, pero por razones que todavía no eran claras. Si la justicia fuera solo castigar al culpable, sería fácil. Pero la justicia verdadera requiere entender por qué alguien actuó como actuó, aunque eso sea más difícil de aceptar."
    },
    # 14. conjugation — pluperfect subjunctive for unreal past
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga en pluscuamperfecto de subjuntivo (hubiera + participio).",
        "questions": [
            {
                "verb": "saber (pluscuamperfecto de subjuntivo)",
                "subject": "yo",
                "answer": "hubiera sabido",
                "options": ["hubiera sabido", "habría sabido", "había sabido", "supiera"]
            },
            {
                "verb": "hablar (pluscuamperfecto de subjuntivo)",
                "subject": "don Aurelio",
                "answer": "hubiera hablado",
                "options": ["hubiera hablado", "habría hablado", "había hablado", "hablara"]
            },
            {
                "verb": "ver (pluscuamperfecto de subjuntivo)",
                "subject": "María",
                "answer": "hubiera visto",
                "options": ["hubiera visto", "habría visto", "había visto", "viera"]
            },
            {
                "verb": "tener (pluscuamperfecto de subjuntivo)",
                "subject": "la comunidad",
                "answer": "hubiera tenido",
                "options": ["hubiera tenido", "habría tenido", "había tenido", "tuviera"]
            }
        ]
    },
    # 15. listening — the hidden truth
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha la revelación de don Aurelio y responde.",
        "audio": "Don Aurelio finalmente reveló lo que sabía. Dijo que la persona que había copiado el mapa no había sido Tomás, sino un joven que trabajaba para él en la finca. El joven habría actuado por desesperación, ya que su familia estaba siendo amenazada por los hombres de Don Próspero. Don Aurelio explicó que si hubiera revelado esto antes, el joven habría sido expuesto a represalias. Añadió que la situación era mucho más complicada de lo que parecía desde fuera.",
        "question": "¿Por qué don Aurelio había guardado silencio hasta ahora?",
        "answer": "Para proteger al joven que copió el mapa, quien había actuado bajo amenazas y habría sufrido represalias.",
        "options": [
            "Para proteger al joven que copió el mapa, quien había actuado bajo amenazas y habría sufrido represalias.",
            "Porque él era el verdadero culpable.",
            "Porque no le importaba el bosque.",
            "Porque quería que Tomás fuera castigado."
        ]
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest33: "El lugar donde van los nombres"
# Theme: memory, oblivion, naming, poetic language
# ─────────────────────────────────────────────────────────────
DEST33_NEW_GAMES = [
    # 1. conjugation — subjunctive for naming / bringing back
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga en presente de subjuntivo. Los nombres piden ser recordados.",
        "questions": [
            {
                "verb": "nombrar (subjuntivo)",
                "subject": "alguien",
                "answer": "nombre",
                "options": ["nombre", "nombra", "nombraría", "nombró"]
            },
            {
                "verb": "recordar (subjuntivo)",
                "subject": "nosotros",
                "answer": "recordemos",
                "options": ["recordemos", "recordamos", "recordáramos", "recordaríamos"]
            },
            {
                "verb": "existir (subjuntivo)",
                "subject": "las palabras",
                "answer": "existan",
                "options": ["existan", "existen", "existieran", "existirían"]
            },
            {
                "verb": "volver (subjuntivo)",
                "subject": "los nombres",
                "answer": "vuelvan",
                "options": ["vuelvan", "vuelven", "volverían", "volvieron"]
            }
        ]
    },
    # 2. pair — things lost and their poetic descriptions
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada cosa olvidada con su descripción poética.",
        "pairs": [
            ["Un árbol", "Lo que sostenía el cielo con sus ramas y cantaba cuando el viento lo tocaba."],
            ["Un río", "Lo que llevaba historias de un pueblo al siguiente y enseñaba a las abuelas."],
            ["La comida", "Lo que las familias compartían alrededor del fuego y hacía amigos de los extraños."],
            ["El hogar", "La sensación de pertenecer a un sitio, el olor de la mañana conocida."],
            ["La historia", "El hilo que une todos los nombres en una tela con sentido."]
        ]
    },
    # 3. fib — concessive and purpose clauses about memory
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con la forma correcta del subjuntivo en cláusulas concesivas y de propósito.",
        "questions": [
            {
                "sentence": "Aunque los nombres ___ sido borrados, sus ecos todavía flotan en el Silencio.",
                "answer": "hayan",
                "options": ["hayan", "han", "habrían", "habían"]
            },
            {
                "sentence": "Yaguará nombra las cosas para que ___ a existir.",
                "answer": "vuelvan",
                "options": ["vuelvan", "vuelven", "volverían", "volvieron"]
            },
            {
                "sentence": "A pesar de que el olvido ___ más fuerte cada día, la memoria resiste.",
                "answer": "sea",
                "options": ["sea", "es", "sería", "fue"]
            },
            {
                "sentence": "Los nombres esperan sin que nadie los ___.",
                "answer": "busque",
                "options": ["busque", "busca", "buscaría", "buscó"]
            }
        ]
    },
    # 4. sorting — concrete description vs metaphorical description
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica cada descripción como concreta o metafórica.",
        "categories": ["Descripción concreta", "Descripción metafórica"],
        "items": [
            {"text": "Un árbol de 20 metros con hojas verdes.", "category": "Descripción concreta"},
            {"text": "Lo que sostenía el cielo con sus ramas.", "category": "Descripción metafórica"},
            {"text": "Un río de aguas oscuras que cruza el valle.", "category": "Descripción concreta"},
            {"text": "Lo que llevaba historias de un pueblo al siguiente.", "category": "Descripción metafórica"},
            {"text": "Una casa de madera con techo de paja.", "category": "Descripción concreta"},
            {"text": "La sensación de pertenecer a un sitio.", "category": "Descripción metafórica"},
            {"text": "Una mujer de 80 años que vive junto al río.", "category": "Descripción concreta"},
            {"text": "El hilo que une todos los nombres en una tela con sentido.", "category": "Descripción metafórica"}
        ]
    },
    # 5. narrative — the place where forgotten names go
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee la descripción del lugar donde van los nombres olvidados. Observa la voz pasiva y los pronombres relativos.",
        "text": "El lugar donde van los nombres olvidados no tiene coordenadas. Es un espacio que fue creado por el olvido mismo, cuyas paredes están hechas de casi-sonidos y cuyos objetos tiemblan entre ser y no ser. Los nombres que fueron perdidos flotan aquí como ecos sin boca. Los idiomas que fueron abandonados por sus hablantes repiten frases que nadie entiende. Cada nombre que es recuperado desaparece de este lugar y vuelve al mundo. Pero para que un nombre sea recuperado, alguien tiene que recordar no solo la palabra, sino la historia que la palabra llevaba dentro."
    },
    # 6. fib — passive voice in poetic descriptions
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa con la forma pasiva correcta en el contexto poético.",
        "questions": [
            {
                "sentence": "Los nombres que fueron ___ por el Silencio todavía esperan ser dichos.",
                "answer": "borrados",
                "options": ["borrados", "borrando", "borrar", "borrarían"]
            },
            {
                "sentence": "Las historias que habían sido ___ durante generaciones desaparecieron en una sola noche.",
                "answer": "contadas",
                "options": ["contadas", "contando", "contar", "contarían"]
            },
            {
                "sentence": "Los idiomas que fueron ___ por sus hablantes repiten frases solas.",
                "answer": "abandonados",
                "options": ["abandonados", "abandonando", "abandonar", "abandonarían"]
            },
            {
                "sentence": "Cada cosa que es ___ con belleza existe dos veces: en el mundo y en la palabra.",
                "answer": "nombrada",
                "options": ["nombrada", "nombrando", "nombrar", "nombraría"]
            }
        ]
    },
    # 7. builder — conditional perfect about lost names
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena para formar una hipótesis sobre el olvido.",
        "words": ["Si", "alguien", "hubiera", "recordado", "los", "nombres,", "este", "lugar", "no", "habría", "sido", "creado", "por", "el", "olvido."],
        "answer": "Si alguien hubiera recordado los nombres, este lugar no habría sido creado por el olvido."
    },
    # 8. conjugation — pluperfect subjunctive + conditional perfect
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga en el tiempo indicado para hipótesis sobre nombres perdidos.",
        "questions": [
            {
                "verb": "olvidar (pluscuamperfecto de subjuntivo, pasiva)",
                "subject": "los nombres",
                "answer": "hubieran sido olvidados",
                "options": ["hubieran sido olvidados", "habrían sido olvidados", "fueron olvidados", "serían olvidados"]
            },
            {
                "verb": "sobrevivir (condicional perfecto)",
                "subject": "las palabras",
                "answer": "habrían sobrevivido",
                "options": ["habrían sobrevivido", "hubieran sobrevivido", "habían sobrevivido", "sobrevivirían"]
            },
            {
                "verb": "recordar (pluscuamperfecto de subjuntivo)",
                "subject": "la gente",
                "answer": "hubiera recordado",
                "options": ["hubiera recordado", "habría recordado", "había recordado", "recordaría"]
            },
            {
                "verb": "existir (condicional perfecto)",
                "subject": "este lugar",
                "answer": "habría existido",
                "options": ["habría existido", "hubiera existido", "había existido", "existiría"]
            }
        ]
    },
    # 9. listening — Yaguará names the forgotten things
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Escucha cómo Yaguará le devuelve el nombre a una cosa olvidada y responde.",
        "audio": "Una forma gris tembló frente a Yaguará. Susurraba: «Fui algo que conectaba a las personas a pesar de la distancia. Viajaba de boca en boca y cambiaba un poco con cada persona que me contaba. Era tan antigua como la memoria humana y tan frágil como una voz.» Yaguará la miró y dijo: «Tu nombre es historia. Eres el hilo que une todos los nombres en una tela con sentido.» Y la forma gris se llenó de color.",
        "question": "¿Qué característica de la historia destaca el texto?",
        "answer": "Que la historia cambia con cada persona que la cuenta, porque viaja de boca en boca.",
        "options": [
            "Que la historia cambia con cada persona que la cuenta, porque viaja de boca en boca.",
            "Que la historia es siempre idéntica y nunca cambia.",
            "Que la historia solo existe en los libros.",
            "Que la historia no tiene relación con la memoria."
        ]
    },
    # 10. pair — advanced relative pronouns
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada inicio de oración con el pronombre relativo correcto y su continuación.",
        "pairs": [
            ["El lugar donde van los nombres,", "cuyas paredes están hechas de casi-sonidos..."],
            ["Los idiomas que fueron abandonados,", "cuyos hablantes ya no viven..."],
            ["La ceiba, a través de", "la cual los antepasados enviaban mensajes al cielo..."],
            ["Lo que fue nombrado con belleza", "existe dos veces: en el mundo y en la palabra."],
            ["El Silencio Gris, contra", "el cual Yaguará lucha, no tiene cuerpo ni voz."]
        ]
    },
    # 11. translation — naming as creation
    {
        "type": "translation",
        "label": "Traducir",
        "instruction": "Traduce al español usando las palabras disponibles.",
        "source": "What is not named ceases to exist, but what is named with beauty exists twice.",
        "words": ["Lo", "que", "no", "es", "nombrado", "deja", "de", "existir,", "pero", "lo", "que", "es", "nombrado", "con", "belleza", "existe", "dos", "veces."],
        "answer": "Lo que no es nombrado deja de existir, pero lo que es nombrado con belleza existe dos veces."
    },
    # 12. dictation — poetic passage with advanced grammar
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe. Presta atención a los pronombres relativos y la voz pasiva.",
        "audio": "Los nombres que fueron olvidados esperan en un lugar cuyas paredes están hechas de ecos. Para que un nombre sea recuperado, alguien tiene que recordar no solo la palabra, sino la historia que la palabra llevaba dentro.",
        "answer": "Los nombres que fueron olvidados esperan en un lugar cuyas paredes están hechas de ecos. Para que un nombre sea recuperado, alguien tiene que recordar no solo la palabra, sino la historia que la palabra llevaba dentro."
    },
    # 13. conversation — naming the hardest thing to name
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "El Silencio Gris presenta la prueba más difícil. Yaguará debe nombrar algo abstracto.",
        "turns": [
            {
                "dialogue": [
                    {"speaker": "A", "name": "El Silencio Gris", "text": "Hay algo que no puedes nombrar describiendo sus partes. No es un objeto, ni un lugar, ni una persona. Es lo que sientes cuando pierdes algo que no sabías que tenías. Es lo que queda cuando alguien se va y el espacio que ocupaba sigue doliendo."},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Tu nombre es ausencia. No eres un vacío, porque el vacío no duele. Eres la forma exacta de lo que falta. Existes solo porque algo existió antes, y por eso eres la prueba de que lo perdido fue real.",
                    "Eres tristeza.",
                    "No sé qué eres."
                ],
                "answer": "Tu nombre es ausencia. No eres un vacío, porque el vacío no duele. Eres la forma exacta de lo que falta. Existes solo porque algo existió antes, y por eso eres la prueba de que lo perdido fue real."
            },
            {
                "dialogue": [
                    {"speaker": "A", "name": "El Silencio Gris", "text": "La forma gris tiembla pero no desaparece. Dice: «Hay otra cosa más. Lo que hace que alguien repita un nombre aunque sepa que nadie va a contestar. Lo que hace que los viejos hablen con los muertos.»"},
                    {"speaker": "B", "name": "Yaguará", "text": "???"}
                ],
                "options": [
                    "Tu nombre es memoria. No eres solo datos guardados — eres el acto de insistir en que lo pasado sigue importando. Eres la voz que dice los nombres de los que ya no están, para que el silencio no sea la última palabra.",
                    "Eso es locura.",
                    "No tiene nombre."
                ],
                "answer": "Tu nombre es memoria. No eres solo datos guardados — eres el acto de insistir en que lo pasado sigue importando. Eres la voz que dice los nombres de los que ya no están, para que el silencio no sea la última palabra."
            }
        ]
    },
    # 14. fib — reported speech about lost languages
    {
        "type": "fib",
        "label": "Completar",
        "instruction": "Completa el discurso reportado sobre los idiomas perdidos.",
        "questions": [
            {
                "sentence": "Un eco dijo que ese idioma ___ hablado por miles de personas antes de ser olvidado.",
                "answer": "había sido",
                "options": ["había sido", "fue", "sería", "ha sido"]
            },
            {
                "sentence": "Otro eco afirmó que las palabras ___ poder si alguien las pronunciara de nuevo.",
                "answer": "recuperarían",
                "options": ["recuperarían", "recuperaban", "recuperan", "recuperaron"]
            },
            {
                "sentence": "Un tercer eco susurró que si hubiera sido escuchado a tiempo, no ___ en este lugar de olvido.",
                "answer": "estaría",
                "options": ["estaría", "estaba", "está", "esté"]
            },
            {
                "sentence": "El último eco contó que antes de ser olvidado, su idioma ___ usado para cantar las lluvias y nombrar las estrellas.",
                "answer": "había sido",
                "options": ["había sido", "fue", "era", "sería"]
            }
        ]
    },
    # 15. sorting — things that can be named concretely vs abstractly
    {
        "type": "sorting",
        "label": "Clasificar",
        "instruction": "Clasifica: ¿qué cosas se nombran con una sola palabra y cuáles necesitan una historia?",
        "categories": ["Se nombra con una palabra", "Necesita una historia para ser nombrado"],
        "items": [
            {"text": "Un árbol", "category": "Se nombra con una palabra"},
            {"text": "La sensación de pertenecer a un lugar", "category": "Necesita una historia para ser nombrado"},
            {"text": "Un río", "category": "Se nombra con una palabra"},
            {"text": "Lo que queda cuando alguien se va", "category": "Necesita una historia para ser nombrado"},
            {"text": "El sol", "category": "Se nombra con una palabra"},
            {"text": "La conexión entre todas las generaciones de una familia", "category": "Necesita una historia para ser nombrado"},
            {"text": "Una casa", "category": "Se nombra con una palabra"},
            {"text": "El hilo invisible que une los nombres con sus significados", "category": "Necesita una historia para ser nombrado"}
        ]
    },
    # 16. builder — poetic passive voice
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena para formar una oración poética con voz pasiva.",
        "words": ["Lo", "que", "no", "es", "nombrado", "deja", "de", "existir.", "Lo", "que", "es", "nombrado", "con", "belleza", "existe", "dos", "veces."],
        "answer": "Lo que no es nombrado deja de existir. Lo que es nombrado con belleza existe dos veces."
    },
    # 17. narrative — conclusion about naming
    {
        "type": "narrative",
        "label": "Narración",
        "instruction": "Lee la conclusión de Yaguará sobre el poder de nombrar.",
        "text": "Al final, Yaguará entendió que nombrar no era simplemente decir una palabra. Era un acto de creación. Cada nombre que fue pronunciado con intención devolvía algo al mundo. Las cosas que habían sido olvidadas no desaparecían del todo — esperaban en el Silencio Gris a que alguien las buscara. Pero para que fueran recuperadas, no bastaba con decir su nombre: había que recordar la historia que cada nombre llevaba dentro. Un árbol nombrado sin amor era solo una palabra. Un árbol nombrado con la memoria de su sombra, de sus raíces, de los pájaros que dormían en sus ramas, era un árbol que existía dos veces."
    }
]


# ─────────────────────────────────────────────────────────────
# Main logic: read, insert, write
# ─────────────────────────────────────────────────────────────
NEW_GAMES_MAP = {
    "dest29": DEST29_NEW_GAMES,
    "dest30": DEST30_NEW_GAMES,
    "dest31": DEST31_NEW_GAMES,
    "dest32": DEST32_NEW_GAMES,
    "dest33": DEST33_NEW_GAMES,
}


def process_destination(dest_id: str) -> dict:
    """Read dest JSON, insert new games before escaperoom+cronica, write back."""
    path = os.path.join(CONTENT_DIR, f"{dest_id}.json")
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    games = data["games"]
    old_count = len(games)

    # Find the position of escaperoom (and cronica after it)
    # They should be the last two items
    tail_types = {"escaperoom", "cronica"}
    tail_games = []
    body_games = []
    for g in games:
        if g["type"] in tail_types:
            tail_games.append(g)
        else:
            body_games.append(g)

    new_games = NEW_GAMES_MAP.get(dest_id, [])

    # How many do we need to add?
    current_body = len(body_games)
    current_tail = len(tail_games)
    current_total = current_body + current_tail
    needed = TARGET_TOTAL - current_total
    if needed <= 0:
        print(f"  {dest_id}: already at {current_total} games, no new games needed")
        return {"dest": dest_id, "before": old_count, "after": old_count, "added": 0}

    games_to_add = new_games[:needed]
    actual_added = len(games_to_add)

    # Rebuild: body + new + tail
    data["games"] = body_games + games_to_add + tail_games
    new_count = len(data["games"])

    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        f.write("\n")

    print(f"  {dest_id}: {old_count} -> {new_count} games (+{actual_added})")
    return {"dest": dest_id, "before": old_count, "after": new_count, "added": actual_added}


def main():
    print("=" * 60)
    print("Authoring new games for B2 Basic (dest29-33)")
    print(f"Target: {TARGET_TOTAL} games per destination")
    print("=" * 60)
    print()

    results = []
    for d in range(29, 34):
        dest_id = f"dest{d}"
        result = process_destination(dest_id)
        results.append(result)

    print()
    print("=" * 60)
    print("Summary")
    print("=" * 60)
    total_added = 0
    for r in results:
        total_added += r["added"]
        # Count game types in the updated file
        path = os.path.join(CONTENT_DIR, f"{r['dest']}.json")
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
        types = {}
        for g in data["games"]:
            t = g["type"]
            types[t] = types.get(t, 0) + 1
        type_str = ", ".join(f"{k}:{v}" for k, v in sorted(types.items()))
        print(f"  {r['dest']}: {r['before']} -> {r['after']} games  [{type_str}]")

    print()
    print(f"Total new games added: {total_added}")
    print("Escape room and cronica preserved at end of each destination.")
    print("Done.")


if __name__ == "__main__":
    main()
