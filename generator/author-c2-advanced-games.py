#!/usr/bin/env python3
"""
Author new games for C2 Advanced destinations (dest54-58).

Current counts: dest54=30, dest55=23, dest56=20, dest57=18, dest58=34.
Target: bring each to at least 30 games.

C2 Advanced grammar focus:
  - Subjunctive in all tenses (imperfect, pluperfect, future)
  - Conditional perfect (habría + participio)
  - Passive voice with ser + participio
  - Literary/journalistic register
  - Complex subordination (aunque + subjunctive, a pesar de que, en caso de que)
  - Idiomatic expressions
  - NO English. All prompts, options, feedback in Spanish.

Story context:
  dest54: "El regreso a la selva" — translation, mediation, returning transformed
  dest55: "Crónica de los mundos" — professional writing, crónica, essay
  dest56: "La lengua viva" — sociolinguistics, dialectal variation, identity
  dest57: "Guardianes de historias" — transmission, teaching, oral tradition
  dest58: "El espíritu completo" — final synthesis, La Palabra del Corazón (escuchar)

New games are inserted BEFORE escaperoom + cronica (which stay at end).
"""

import json
import os

CONTENT_DIR = "/home/babelfree.com/public_html/content"
TARGET_TOTAL = 30


# ─────────────────────────────────────────────────────────────
# New games for dest54: "El regreso a la selva"
# Theme: translation, mediation, returning transformed
# Current: 30 games — already at target, no new games needed
# ─────────────────────────────────────────────────────────────
DEST54_NEW_GAMES = []


# ─────────────────────────────────────────────────────────────
# New games for dest55: "Crónica de los mundos"
# Theme: professional writing, crónica, essay, literary register
# Current: 23 games, need 7 new
# ─────────────────────────────────────────────────────────────
DEST55_NEW_GAMES = [
    # 1. fill — subjunctive in subordinate clauses about writing craft
    {
        "type": "fill",
        "label": "Completar",
        "instruction": "Completa con la forma correcta del subjuntivo en contextos de escritura profesional.",
        "sentences": [
            {
                "text": "Aunque el cronista ___ (dedicar) meses a la investigación, la escritura le exigió el doble de tiempo.",
                "blank": "hubiera dedicado",
                "options": [
                    "hubiera dedicado",
                    "había dedicado",
                    "dedicó",
                    "dedicaría"
                ]
            },
            {
                "text": "En caso de que el editor ___ (rechazar) el manuscrito, el autor tenía preparada una versión alternativa.",
                "blank": "rechazara",
                "options": [
                    "rechazara",
                    "rechazó",
                    "rechazaría",
                    "ha rechazado"
                ]
            },
            {
                "text": "A pesar de que la crónica ___ (ser) un género híbrido, exige el rigor del periodismo y la belleza de la literatura.",
                "blank": "sea",
                "options": [
                    "sea",
                    "es",
                    "fuera",
                    "sería"
                ]
            },
            {
                "text": "No habría logrado la fluidez narrativa si no ___ (leer) a los cronistas latinoamericanos con atención.",
                "blank": "hubiera leído",
                "options": [
                    "hubiera leído",
                    "habría leído",
                    "había leído",
                    "leyó"
                ]
            },
            {
                "text": "Quien ___ (dominar) la crónica dominará, por extensión, toda la escritura profesional.",
                "blank": "domine",
                "options": [
                    "domine",
                    "domina",
                    "dominara",
                    "dominará"
                ]
            },
            {
                "text": "Sin que nadie se lo ___ (pedir), el cronista reescribió el párrafo final siete veces.",
                "blank": "pidiera",
                "options": [
                    "pidiera",
                    "pidió",
                    "pediría",
                    "ha pedido"
                ]
            }
        ]
    },
    # 2. conversation — editorial discussion about a crónica draft
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Participas en una reunión editorial. Analiza el borrador de una crónica y elige la intervención más precisa.",
        "conversations": [
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Editora",
                        "text": "El inicio engancha, pero el tercer párrafo pierde tensión. Parece que el cronista hubiera abandonado la escena para dar una conferencia."
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "Coincido. El problema es que la voz narrativa pasa del mostrar al explicar. Si el cronista hubiera mantenido la escena y dejado que los detalles transmitieran la idea, no habría necesitado el párrafo expositivo.",
                    "Yo creo que está bien así. La información es importante.",
                    "Habría que eliminarlo todo y empezar de nuevo.",
                    "No importa, el lector no se va a dar cuenta."
                ],
                "correct": "Coincido. El problema es que la voz narrativa pasa del mostrar al explicar. Si el cronista hubiera mantenido la escena y dejado que los detalles transmitieran la idea, no habría necesitado el párrafo expositivo."
            },
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Editora",
                        "text": "El cierre me parece abrupto. Es como si se le hubiera acabado el espacio y cerrara deprisa."
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "Propongo que el cierre retome la imagen del inicio, de modo que la crónica forme un arco circular. Si la primera escena fuera el río al amanecer, la última podría ser el mismo río al anochecer, pero transformado por lo que el lector ya sabe.",
                    "Los cierres siempre son difíciles. Que lo deje así.",
                    "Bastaría con añadir una frase motivacional.",
                    "Habría que ponerle un final feliz."
                ],
                "correct": "Propongo que el cierre retome la imagen del inicio, de modo que la crónica forme un arco circular. Si la primera escena fuera el río al amanecer, la última podría ser el mismo río al anochecer, pero transformado por lo que el lector ya sabe."
            },
            {
                "lines": [
                    {
                        "speaker": "C",
                        "name": "Cronista",
                        "text": "Pero si cambio el final, tendría que reescribir los últimos tres párrafos. ¿No sería excesivo?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "No necesariamente. A veces basta con que la última frase contenga un eco —una palabra, una imagen, un ritmo— que remita al lector al principio sin que sea una repetición literal. El efecto es poderoso y la reescritura, mínima.",
                    "Sí, sería excesivo. Déjalo como está.",
                    "Reescríbelo todo desde cero, es lo mejor.",
                    "No importa el final, lo importante es el contenido."
                ],
                "correct": "No necesariamente. A veces basta con que la última frase contenga un eco —una palabra, una imagen, un ritmo— que remita al lector al principio sin que sea una repetición literal. El efecto es poderoso y la reescritura, mínima."
            }
        ]
    },
    # 3. conjugation — literary tenses in professional writing
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga los verbos en el tiempo y modo que exija el contexto de escritura profesional.",
        "sentences": [
            {
                "text": "Si el ensayista ___ (construir) mejor su argumento, la tesis habría sido irrefutable.",
                "blank": "hubiera construido",
                "verb": "construir",
                "tense": "pretérito pluscuamperfecto de subjuntivo"
            },
            {
                "text": "El documento ___ (ser, pasiva) redactado por un equipo de cuatro cronistas.",
                "blank": "fue redactado",
                "verb": "redactar",
                "tense": "pretérito indefinido (voz pasiva)"
            },
            {
                "text": "Era imprescindible que cada párrafo ___ (justificar) su existencia dentro del texto.",
                "blank": "justificara",
                "verb": "justificar",
                "tense": "pretérito imperfecto de subjuntivo"
            },
            {
                "text": "Si no ___ (existir) la crónica, la memoria colectiva habría perdido sus mejores páginas.",
                "blank": "hubiera existido",
                "verb": "existir",
                "tense": "pretérito pluscuamperfecto de subjuntivo"
            },
            {
                "text": "La columna de opinión ___ (publicar, pasiva) sin la autorización del autor.",
                "blank": "fue publicada",
                "verb": "publicar",
                "tense": "pretérito indefinido (voz pasiva)"
            },
            {
                "text": "Dudaba de que el reportaje ___ (poder) captar la complejidad del conflicto.",
                "blank": "pudiera",
                "verb": "poder",
                "tense": "pretérito imperfecto de subjuntivo"
            }
        ]
    },
    # 4. builder — complex conditional + passive voice in essay writing
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena los segmentos para formar una oración con estructura condicional compuesta.",
        "words": [
            "Si la crónica",
            "hubiera sido escrita",
            "sin haber visitado",
            "el lugar de los hechos,",
            "habría carecido",
            "de la autenticidad",
            "que solo da",
            "la presencia."
        ],
        "answer": "Si la crónica hubiera sido escrita sin haber visitado el lugar de los hechos, habría carecido de la autenticidad que solo da la presencia."
    },
    # 5. listening — analyzing literary style in a passage
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Lee el siguiente fragmento de ensayo y analiza las decisiones gramaticales del autor.",
        "text": "Cada palabra que es elegida por el cronista desplaza a mil palabras que fueron descartadas. El oficio de escribir no consiste en llenar páginas sino en vaciarlas: retirar todo lo que no sea imprescindible hasta que lo que quede no pueda ser dicho de otra manera. Si García Márquez hubiera sido más generoso con sus adjetivos, habría sido menos preciso. Si Caparrós no hubiera aprendido a escuchar antes de escribir, sus crónicas habrían carecido de la voz de la calle. La escritura profesional es, en última instancia, un ejercicio de renuncia disciplinada.",
        "questions": [
            {
                "q": "¿Qué tiempo verbal predomina en las hipótesis sobre García Márquez y Caparrós?",
                "options": [
                    "Pluscuamperfecto de subjuntivo + condicional perfecto (si hubiera... habría...)",
                    "Pretérito imperfecto de subjuntivo + condicional simple",
                    "Presente de subjuntivo + futuro simple",
                    "Pretérito indefinido + pretérito pluscuamperfecto"
                ],
                "answer": "Pluscuamperfecto de subjuntivo + condicional perfecto (si hubiera... habría...)"
            },
            {
                "q": "¿Qué función cumple la voz pasiva en «cada palabra que es elegida por el cronista»?",
                "options": [
                    "Sitúa la palabra como protagonista y al cronista como agente secundario, enfatizando el resultado sobre el proceso",
                    "Es un error gramatical que debería corregirse",
                    "Simplifica la oración para hacerla más breve",
                    "Oculta al verdadero responsable de la acción"
                ],
                "answer": "Sitúa la palabra como protagonista y al cronista como agente secundario, enfatizando el resultado sobre el proceso"
            },
            {
                "q": "¿Qué paradoja presenta la definición de escribir como «vaciar páginas»?",
                "options": [
                    "Que el oficio de añadir palabras consiste, en realidad, en eliminarlas hasta quedarse con las esenciales",
                    "Que los cronistas no saben escribir correctamente",
                    "Que es mejor no escribir nada",
                    "Que las páginas vacías son más valiosas que las llenas"
                ],
                "answer": "Que el oficio de añadir palabras consiste, en realidad, en eliminarlas hasta quedarse con las esenciales"
            }
        ]
    },
    # 6. pair — literary devices and their effects
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada recurso retórico con el efecto que produce en la escritura profesional.",
        "pairs": [
            [
                "Analepsis (flashback)",
                "Interrumpe la línea temporal para enriquecer la comprensión del presente narrativo"
            ],
            [
                "Prolepsis (anticipación)",
                "Crea tensión al adelantar un hecho que aún no se ha narrado"
            ],
            [
                "Elipsis narrativa",
                "Omite información para que el lector la reconstruya, generando un efecto de participación activa"
            ],
            [
                "Polifonía narrativa",
                "Integra múltiples voces y puntos de vista en un mismo texto"
            ],
            [
                "Focalización interna",
                "Restringe la información a lo que un personaje percibe, creando subjetividad"
            ],
            [
                "Estilo indirecto libre",
                "Funde la voz del narrador con el pensamiento del personaje sin marcas de citación"
            ]
        ]
    },
    # 7. dictation — professional register with complex grammar
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe este fragmento sobre el oficio de la crónica. Presta atención a las formas del subjuntivo y la voz pasiva.",
        "text": "Si la crónica no hubiera sido reinventada por los autores latinoamericanos del siglo veintiuno, el periodismo habría perdido su forma más ambiciosa. La crónica exige que cada frase sea justificada, que cada dato sea verificado y que cada silencio sea deliberado. Quien domine este género habrá demostrado que la escritura, aunque sea un oficio solitario, es siempre un acto de responsabilidad colectiva."
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest56: "La lengua viva"
# Theme: sociolinguistics, dialectal variation, identity, living language
# Current: 20 games, need 10 new
# ─────────────────────────────────────────────────────────────
DEST56_NEW_GAMES = [
    # 1. fill — subjunctive in sociolinguistic discourse
    {
        "type": "fill",
        "label": "Completar",
        "instruction": "Completa con la forma correcta del subjuntivo en contextos de variación lingüística.",
        "sentences": [
            {
                "text": "Aunque cada dialecto ___ (tener) sus propias reglas, todos son igualmente válidos desde el punto de vista lingüístico.",
                "blank": "tenga",
                "options": [
                    "tenga",
                    "tiene",
                    "tuviera",
                    "tendría"
                ]
            },
            {
                "text": "En caso de que una lengua ___ (perder) todos sus hablantes, se considera extinta.",
                "blank": "pierda",
                "options": [
                    "pierda",
                    "pierde",
                    "perdiera",
                    "perderá"
                ]
            },
            {
                "text": "A pesar de que el seseo ___ (ser) un fenómeno mayoritario en el mundo hispanohablante, algunos puristas lo consideran incorrecto.",
                "blank": "sea",
                "options": [
                    "sea",
                    "es",
                    "fuera",
                    "sería"
                ]
            },
            {
                "text": "Si las políticas lingüísticas no ___ (proteger) las lenguas indígenas, muchas habrían desaparecido por completo.",
                "blank": "hubieran protegido",
                "options": [
                    "hubieran protegido",
                    "habrían protegido",
                    "protegieron",
                    "protegerían"
                ]
            },
            {
                "text": "No es que el habla informal ___ (carecer) de estructura, sino que sus reglas son diferentes de las del registro formal.",
                "blank": "carezca",
                "options": [
                    "carezca",
                    "carece",
                    "careciera",
                    "carecería"
                ]
            },
            {
                "text": "Sin que nadie lo ___ (decidir), la lengua evoluciona con cada generación que la habla.",
                "blank": "decida",
                "options": [
                    "decida",
                    "decide",
                    "decidiera",
                    "decidirá"
                ]
            }
        ]
    },
    # 2. fill — passive voice in academic register about language
    {
        "type": "fill",
        "label": "Completar",
        "instruction": "Completa con la forma pasiva correcta en contextos de investigación lingüística.",
        "sentences": [
            {
                "text": "El fenómeno del voseo ha ___ (estudiar, pasiva) extensamente por los dialectólogos rioplatenses.",
                "blank": "sido estudiado",
                "options": [
                    "sido estudiado",
                    "estudiando",
                    "sido estudiando",
                    "estado estudiado"
                ]
            },
            {
                "text": "Las lenguas criollas fueron ___ (crear, pasiva) a partir del contacto entre lenguas europeas y africanas.",
                "blank": "creadas",
                "options": [
                    "creadas",
                    "creando",
                    "crean",
                    "crearían"
                ]
            },
            {
                "text": "Numerosos neologismos son ___ (incorporar, pasiva) al diccionario cada año.",
                "blank": "incorporados",
                "options": [
                    "incorporados",
                    "incorporando",
                    "incorporan",
                    "incorporarían"
                ]
            },
            {
                "text": "La teoría de Saussure fue ___ (publicar, pasiva) póstumamente por sus alumnos.",
                "blank": "publicada",
                "options": [
                    "publicada",
                    "publicando",
                    "publicó",
                    "publicaría"
                ]
            },
            {
                "text": "El Atlas Lingüístico de Hispanoamérica está siendo ___ (elaborar, pasiva) por equipos de investigadores de veinte países.",
                "blank": "elaborado",
                "options": [
                    "elaborado",
                    "elaborando",
                    "elaboró",
                    "elaboraría"
                ]
            }
        ]
    },
    # 3. conversation — debate about linguistic prescriptivism
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Participas en un debate académico sobre prescriptivismo y descriptivismo lingüístico. Elige la intervención más fundamentada.",
        "conversations": [
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Profesor",
                        "text": "La Real Academia Española debería ser la autoridad que determine qué es correcto y qué no en nuestra lengua."
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "La RAE cumple una función de registro y orientación, pero la autoridad última reside en los hablantes. Si quinientos millones de personas usan una forma lingüística, negarle validez sería como pretender que el río corra en otra dirección.",
                    "Sí, la RAE tiene siempre la razón.",
                    "La RAE no sirve para nada, habría que eliminarla.",
                    "Es un tema que no le interesa a nadie."
                ],
                "correct": "La RAE cumple una función de registro y orientación, pero la autoridad última reside en los hablantes. Si quinientos millones de personas usan una forma lingüística, negarle validez sería como pretender que el río corra en otra dirección."
            },
            {
                "lines": [
                    {
                        "speaker": "C",
                        "name": "Estudiante",
                        "text": "Pero si no hubiera reglas, ¿no terminaríamos hablando cada uno una lengua diferente?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "Toda comunidad de hablantes genera sus propias normas de manera natural, sin necesidad de que una institución las imponga. El descriptivismo no niega las reglas; sostiene que las reglas emergen del uso colectivo, no de un decreto académico.",
                    "Es verdad, sin reglas todo sería un desastre.",
                    "Las reglas no importan, lo importante es comunicarse.",
                    "Eso ya está pasando y no tiene solución."
                ],
                "correct": "Toda comunidad de hablantes genera sus propias normas de manera natural, sin necesidad de que una institución las imponga. El descriptivismo no niega las reglas; sostiene que las reglas emergen del uso colectivo, no de un decreto académico."
            },
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Profesor",
                        "text": "Si aceptáramos cualquier uso como válido, ¿cómo distinguiríamos un error de una variante dialectal?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "La distinción está en la sistematicidad: un error es un desvío individual y esporádico, mientras que una variante dialectal es un patrón compartido por una comunidad. Si una forma fuera usada sistemáticamente por un grupo de hablantes, dejaría de ser un error para convertirse en una regla diferente.",
                    "No se pueden distinguir, es lo mismo.",
                    "Todo es un error si no sigue la norma de España.",
                    "No hay errores en el lenguaje, todo vale."
                ],
                "correct": "La distinción está en la sistematicidad: un error es un desvío individual y esporádico, mientras que una variante dialectal es un patrón compartido por una comunidad. Si una forma fuera usada sistemáticamente por un grupo de hablantes, dejaría de ser un error para convertirse en una regla diferente."
            }
        ]
    },
    # 4. conjugation — advanced tenses in linguistic analysis
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga los verbos en el tiempo y modo que exija el contexto de análisis lingüístico.",
        "sentences": [
            {
                "text": "Si Saussure ___ (vivir) en la era digital, habría reformulado su concepto de signo lingüístico.",
                "blank": "hubiera vivido",
                "verb": "vivir",
                "tense": "pretérito pluscuamperfecto de subjuntivo"
            },
            {
                "text": "Es improbable que el español ___ (fragmentarse) en lenguas mutuamente incomprensibles.",
                "blank": "se fragmente",
                "verb": "fragmentarse",
                "tense": "presente de subjuntivo"
            },
            {
                "text": "Las variedades dialectales fueron ___ (documentar) por los primeros dialectólogos en el siglo XIX.",
                "blank": "documentadas",
                "verb": "documentar",
                "tense": "pretérito indefinido (voz pasiva)"
            },
            {
                "text": "Si no ___ (existir) el contacto entre lenguas, no se habrían formado las lenguas criollas.",
                "blank": "hubiera existido",
                "verb": "existir",
                "tense": "pretérito pluscuamperfecto de subjuntivo"
            },
            {
                "text": "Dudaba de que el estudio ___ (abarcar) todas las variedades regionales del español.",
                "blank": "abarcara",
                "verb": "abarcar",
                "tense": "pretérito imperfecto de subjuntivo"
            },
            {
                "text": "La hipótesis de Sapir-Whorf ___ (cuestionar, pasiva) por numerosos investigadores desde su formulación.",
                "blank": "ha sido cuestionada",
                "verb": "cuestionar",
                "tense": "pretérito perfecto compuesto (voz pasiva)"
            }
        ]
    },
    # 5. builder — complex subordination about language variation
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena los segmentos para formar una oración con subordinación compleja sobre variación lingüística.",
        "words": [
            "Aunque el español",
            "sea una sola lengua,",
            "las diferencias dialectales",
            "que han sido documentadas",
            "a lo largo de los siglos",
            "demuestran que",
            "cada comunidad de hablantes",
            "la reinventa",
            "según sus necesidades."
        ],
        "answer": "Aunque el español sea una sola lengua, las diferencias dialectales que han sido documentadas a lo largo de los siglos demuestran que cada comunidad de hablantes la reinventa según sus necesidades."
    },
    # 6. listening — analyzing linguistic variation in a text
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Lee el siguiente análisis sobre el español colombiano y responde las preguntas.",
        "text": "El español de Colombia ha sido descrito como uno de los más diversos del continente. En un país donde coexisten la costa caribeña, los Andes, la Amazonía y el Pacífico, sería ingenuo suponer que todos hablaran de la misma manera. El costeño aspira la «s» final y usa un ritmo melódico que habría sido incomprensible para un viajero del siglo XVI. El pastuso conserva rasgos fonéticos que fueron perdidos en otras regiones hace siglos. El bogotano, aunque haya sido idealizado como «el mejor español del mundo» —afirmación que ningún lingüista serio respaldaría—, es simplemente una variedad más, cuyo prestigio se debe a factores políticos, no lingüísticos.",
        "questions": [
            {
                "q": "¿Por qué dice el texto que la diversidad lingüística colombiana era esperable?",
                "options": [
                    "Porque la diversidad geográfica del país —costa, montaña, selva, Pacífico— genera comunidades con necesidades comunicativas diferentes",
                    "Porque los colombianos no fueron a la escuela",
                    "Porque el español de Colombia viene de otra lengua diferente",
                    "Porque la RAE no tiene presencia en Colombia"
                ],
                "answer": "Porque la diversidad geográfica del país —costa, montaña, selva, Pacífico— genera comunidades con necesidades comunicativas diferentes"
            },
            {
                "q": "¿Qué tiempo verbal usa el autor al descartar que el bogotano sea «el mejor español»?",
                "options": [
                    "Condicional simple (respaldaría) para expresar la improbabilidad de que un experto sostenga esa posición",
                    "Futuro simple para hacer una predicción",
                    "Subjuntivo presente para expresar un deseo",
                    "Pretérito indefinido para narrar un hecho pasado"
                ],
                "answer": "Condicional simple (respaldaría) para expresar la improbabilidad de que un experto sostenga esa posición"
            },
            {
                "q": "¿Qué estructura condicional implícita contiene «sería ingenuo suponer que todos hablaran de la misma manera»?",
                "options": [
                    "Una condicional contrafactual: si alguien supusiera eso, sería ingenuo — usando imperfecto de subjuntivo + condicional",
                    "Una condicional real del presente",
                    "Una condicional del futuro probable",
                    "No hay estructura condicional en esa oración"
                ],
                "answer": "Una condicional contrafactual: si alguien supusiera eso, sería ingenuo — usando imperfecto de subjuntivo + condicional"
            }
        ]
    },
    # 7. pair — dialectal phenomena and their definitions
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada fenómeno dialectal con su descripción técnica.",
        "pairs": [
            [
                "Seseo",
                "Pronunciación de «c» ante «e/i» y «z» como [s], generalizada en América"
            ],
            [
                "Yeísmo",
                "Pronunciación idéntica de «ll» y «y», extendida en la mayoría del mundo hispanohablante"
            ],
            [
                "Aspiración de /s/",
                "Debilitamiento de la «s» implosiva en posición final de sílaba, típico del Caribe y Andalucía"
            ],
            [
                "Voseo",
                "Uso del pronombre «vos» en lugar de «tú», con formas verbales propias"
            ],
            [
                "Leísmo",
                "Uso de «le» como complemento directo masculino, aceptado por la RAE solo en algunos casos"
            ],
            [
                "Dequeísmo",
                "Inserción de la preposición «de» antes de «que» en contextos donde no es normativa"
            ]
        ]
    },
    # 8. translation — register variation (formal to informal)
    {
        "type": "translation",
        "label": "Mediación textual",
        "instruction": "Empareja cada fragmento académico con su reformulación en lenguaje coloquial equivalente.",
        "pairs": [
            {
                "source": "El fenómeno de la alternancia de código se manifiesta en hablantes bilingües que integran elementos de ambos sistemas lingüísticos en una misma intervención comunicativa.",
                "target": "Los bilingües mezclan los dos idiomas cuando hablan, y eso es algo natural, no un error."
            },
            {
                "source": "La estandarización de una variedad dialectal obedece a factores de poder político y económico, no a criterios de corrección lingüística inherente.",
                "target": "El dialecto que se considera «correcto» no lo es porque sea mejor, sino porque lo hablan los que tienen el poder."
            },
            {
                "source": "El desplazamiento lingüístico intergeneracional constituye la principal amenaza para la supervivencia de las lenguas minorizadas.",
                "target": "Cuando los padres dejan de hablarles a los hijos en su lengua, esa lengua empieza a morir."
            },
            {
                "source": "La competencia sociolingüística implica la capacidad de adecuar el registro al contexto comunicativo.",
                "target": "Saber hablar bien significa saber cuándo hablar formal y cuándo hablar relajado."
            }
        ]
    },
    # 9. builder — academic register sentence construction
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena los segmentos para formar una hipótesis contrafactual sobre política lingüística.",
        "words": [
            "Si las lenguas indígenas",
            "hubieran sido reconocidas",
            "como lenguas oficiales",
            "desde la independencia,",
            "la diversidad lingüística",
            "no habría sufrido",
            "el deterioro",
            "que hoy lamentamos."
        ],
        "answer": "Si las lenguas indígenas hubieran sido reconocidas como lenguas oficiales desde la independencia, la diversidad lingüística no habría sufrido el deterioro que hoy lamentamos."
    },
    # 10. dictation — sociolinguistic register with advanced grammar
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe este pasaje sobre la vitalidad de la lengua. Presta atención al subjuntivo y a la voz pasiva.",
        "text": "Aunque una lengua sea hablada por millones de personas, su supervivencia no está garantizada. Las lenguas que han sido abandonadas por las generaciones jóvenes desaparecen en el plazo de dos o tres generaciones, sin que nadie las llore hasta que ya es demasiado tarde. Si hubiéramos comprendido antes que cada lengua es un universo irrepetible, habríamos protegido lo que ahora añoramos."
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest57: "Guardianes de historias"
# Theme: transmission, teaching, oral tradition, cultural preservation
# Current: 18 games, need 12 new
# ─────────────────────────────────────────────────────────────
DEST57_NEW_GAMES = [
    # 1. fill — subjunctive in educational and transmission contexts
    {
        "type": "fill",
        "label": "Completar",
        "instruction": "Completa con la forma correcta del subjuntivo en contextos de enseñanza y transmisión cultural.",
        "sentences": [
            {
                "text": "Aunque la tradición oral ___ (carecer) de soporte escrito, su estructura narrativa es tan compleja como la de cualquier novela.",
                "blank": "carezca",
                "options": [
                    "carezca",
                    "carece",
                    "careciera",
                    "carecería"
                ]
            },
            {
                "text": "En caso de que el maestro no ___ (adaptar) su lenguaje al nivel del aprendiz, la transmisión fracasa.",
                "blank": "adapte",
                "options": [
                    "adapte",
                    "adapta",
                    "adaptara",
                    "adaptaría"
                ]
            },
            {
                "text": "Si Doña Asunción no ___ (transmitir) sus historias a Candelaria, un universo entero se habría perdido.",
                "blank": "hubiera transmitido",
                "options": [
                    "hubiera transmitido",
                    "habría transmitido",
                    "transmitió",
                    "transmitiría"
                ]
            },
            {
                "text": "A pesar de que la escritura ___ (revolucionar) la comunicación humana, no ha reemplazado la fuerza de la voz.",
                "blank": "haya revolucionado",
                "options": [
                    "haya revolucionado",
                    "ha revolucionado",
                    "hubiera revolucionado",
                    "revolucionara"
                ]
            },
            {
                "text": "Sin que los guardianes de historias lo ___ (saber), cada relato que cuentan es también un acto de resistencia.",
                "blank": "sepan",
                "options": [
                    "sepan",
                    "saben",
                    "supieran",
                    "sabrían"
                ]
            },
            {
                "text": "No es que la oralidad ___ (ser) inferior a la escritura, sino que opera con mecanismos diferentes.",
                "blank": "sea",
                "options": [
                    "sea",
                    "es",
                    "fuera",
                    "sería"
                ]
            }
        ]
    },
    # 2. fill — conditional perfect + complex subordination
    {
        "type": "fill",
        "label": "Completar",
        "instruction": "Completa con la forma verbal correcta en hipótesis sobre la transmisión del conocimiento.",
        "sentences": [
            {
                "text": "Si las comunidades ___ (disponer) de recursos para documentar sus saberes, la pérdida habría sido menor.",
                "blank": "hubieran dispuesto",
                "options": [
                    "hubieran dispuesto",
                    "habrían dispuesto",
                    "dispusieron",
                    "dispondrían"
                ]
            },
            {
                "text": "La historia oral ___ (perder, condicional perfecto) toda credibilidad si no hubiera mantenido su coherencia interna.",
                "blank": "habría perdido",
                "options": [
                    "habría perdido",
                    "hubiera perdido",
                    "había perdido",
                    "perdería"
                ]
            },
            {
                "text": "Quien ___ (escuchar, futuro de subjuntivo) con atención comprenderá lo que las palabras dicen y lo que callan.",
                "blank": "escuchare",
                "options": [
                    "escuchare",
                    "escucha",
                    "escuchara",
                    "escuchará"
                ]
            },
            {
                "text": "Las técnicas pedagógicas fueron ___ (transformar, pasiva) radicalmente con la llegada de la educación digital.",
                "blank": "transformadas",
                "options": [
                    "transformadas",
                    "transformando",
                    "transforman",
                    "transformarían"
                ]
            },
            {
                "text": "Habríamos ___ (conservar) más lenguas indígenas si la política educativa hubiera sido diferente.",
                "blank": "conservado",
                "options": [
                    "conservado",
                    "conservando",
                    "conservar",
                    "conserva"
                ]
            }
        ]
    },
    # 3. conversation — designing how to transmit a story
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Candelaria y tú planean cómo transmitir la historia de Yaguará a la siguiente generación. Elige la intervención más reflexiva.",
        "conversations": [
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Candelaria",
                        "text": "Podríamos escribir todo lo que pasó, punto por punto. Así nadie se confundiría."
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "El registro escrito preserva los hechos, pero la tradición oral preserva la emoción. Si solo escribiéramos los hechos, perderíamos el tono de la voz, la pausa antes de una revelación, el gesto que convierte una anécdota en sabiduría. Quizás lo ideal sería combinar ambos medios.",
                    "Sí, hay que escribirlo todo.",
                    "No, mejor no escribir nada y dejar que se pierda.",
                    "Da igual cómo se cuente, el contenido es lo mismo."
                ],
                "correct": "El registro escrito preserva los hechos, pero la tradición oral preserva la emoción. Si solo escribiéramos los hechos, perderíamos el tono de la voz, la pausa antes de una revelación, el gesto que convierte una anécdota en sabiduría. Quizás lo ideal sería combinar ambos medios."
            },
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Candelaria",
                        "text": "Doña Asunción nunca escribió nada. ¿Significa eso que su conocimiento valía menos?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "En absoluto. El conocimiento de Doña Asunción fue transmitido durante décadas a través de la escucha atenta y la repetición transformadora. Si su saber hubiera sido menos valioso, no habría sobrevivido tantas generaciones. La oralidad tiene su propia forma de rigor.",
                    "Sí, lo escrito vale más que lo hablado.",
                    "No importa, ya no está aquí para saberlo.",
                    "Es una pregunta irrelevante."
                ],
                "correct": "En absoluto. El conocimiento de Doña Asunción fue transmitido durante décadas a través de la escucha atenta y la repetición transformadora. Si su saber hubiera sido menos valioso, no habría sobrevivido tantas generaciones. La oralidad tiene su propia forma de rigor."
            },
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Candelaria",
                        "text": "¿Y si quien reciba la historia la cambia? ¿No habríamos fracasado?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "Al contrario: que la historia sea transformada por quien la recibe es señal de que está viva. Si la historia fuera repetida sin cambio alguno, sería un fósil, no una tradición. Transmitir no es copiar; es confiar en que el otro le dará nueva forma sin perder la esencia.",
                    "Sí, habríamos fracasado. La historia debe repetirse exactamente.",
                    "No importa si la cambian, total nadie se acuerda.",
                    "Mejor no contarla y así no se puede cambiar."
                ],
                "correct": "Al contrario: que la historia sea transformada por quien la recibe es señal de que está viva. Si la historia fuera repetida sin cambio alguno, sería un fósil, no una tradición. Transmitir no es copiar; es confiar en que el otro le dará nueva forma sin perder la esencia."
            }
        ]
    },
    # 4. conversation — mentoring a new teacher
    {
        "type": "conversation",
        "label": "Conversación",
        "instruction": "Un joven profesor te pide consejo sobre cómo enseñar a escuchar. Elige la respuesta más pedagógicamente sólida.",
        "conversations": [
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Profesor joven",
                        "text": "Mis estudiantes no escuchan. Hablan todo el tiempo. ¿Qué hago?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "Antes de pedirles que escuchen, demuéstrales que tú los escuchas. Si un estudiante percibe que su voz es valorada, estará más dispuesto a ceder el espacio a la voz del otro. La escucha se enseña con el ejemplo, no con la exigencia.",
                    "Castígalos hasta que aprendan a callarse.",
                    "No te preocupes, así son los jóvenes de hoy.",
                    "Dales más tarea para que no tengan tiempo de hablar."
                ],
                "correct": "Antes de pedirles que escuchen, demuéstrales que tú los escuchas. Si un estudiante percibe que su voz es valorada, estará más dispuesto a ceder el espacio a la voz del otro. La escucha se enseña con el ejemplo, no con la exigencia."
            },
            {
                "lines": [
                    {
                        "speaker": "A",
                        "name": "Profesor joven",
                        "text": "¿Y si hubiera un estudiante que simplemente no quisiera aprender? ¿Debería rendirme?"
                    },
                    {
                        "speaker": "B",
                        "name": "Tú",
                        "text": ""
                    }
                ],
                "options": [
                    "No existe el estudiante que no quiere aprender; existe el estudiante cuya motivación aún no ha sido descubierta. Si pudieras encontrar qué conecta con su mundo interior, tendrías la llave. Doña Asunción decía: «Las palabras tienen memoria.» A veces hay que esperar a que la palabra correcta despierte esa memoria.",
                    "Sí, algunos son imposibles. Ríndete.",
                    "Oblígalo a estudiar y punto.",
                    "Ignóralo y concéntrate en los buenos estudiantes."
                ],
                "correct": "No existe el estudiante que no quiere aprender; existe el estudiante cuya motivación aún no ha sido descubierta. Si pudieras encontrar qué conecta con su mundo interior, tendrías la llave. Doña Asunción decía: «Las palabras tienen memoria.» A veces hay que esperar a que la palabra correcta despierte esa memoria."
            }
        ]
    },
    # 5. conjugation — literary and pedagogical tenses
    {
        "type": "conjugation",
        "label": "Conjugación",
        "instruction": "Conjuga los verbos en el tiempo y modo que exija el contexto de transmisión del conocimiento.",
        "sentences": [
            {
                "text": "Si Doña Asunción ___ (poder) ver lo que Candelaria ha construido, habría sonreído.",
                "blank": "hubiera podido",
                "verb": "poder",
                "tense": "pretérito pluscuamperfecto de subjuntivo"
            },
            {
                "text": "Era fundamental que cada guardián de historias ___ (comprender) que transmitir es transformar.",
                "blank": "comprendiera",
                "verb": "comprender",
                "tense": "pretérito imperfecto de subjuntivo"
            },
            {
                "text": "Las historias fueron ___ (contar, pasiva) de generación en generación sin que nadie las escribiera.",
                "blank": "contadas",
                "verb": "contar",
                "tense": "pretérito indefinido (voz pasiva)"
            },
            {
                "text": "Si el conocimiento no ___ (ser) compartido, deja de existir.",
                "blank": "es",
                "verb": "ser",
                "tense": "presente de indicativo"
            },
            {
                "text": "Quien ___ (querer, futuro de subjuntivo) guardar una historia deberá primero aprender a escucharla.",
                "blank": "quisiere",
                "verb": "querer",
                "tense": "futuro de subjuntivo"
            },
            {
                "text": "La sabiduría ancestral habría ___ (desaparecer, condicional perfecto) si las abuelas no la hubieran protegido.",
                "blank": "desaparecido",
                "verb": "desaparecer",
                "tense": "condicional perfecto"
            }
        ]
    },
    # 6. builder — complex sentence about oral tradition
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena los segmentos para formar una reflexión sobre la tradición oral con estructura concesiva.",
        "words": [
            "A pesar de que",
            "la tradición oral",
            "haya sido considerada",
            "durante siglos",
            "inferior a la escritura,",
            "los pueblos que",
            "la mantuvieron viva",
            "demostraron que",
            "la voz humana",
            "es el primer libro."
        ],
        "answer": "A pesar de que la tradición oral haya sido considerada durante siglos inferior a la escritura, los pueblos que la mantuvieron viva demostraron que la voz humana es el primer libro."
    },
    # 7. builder — conditional perfect about lost knowledge
    {
        "type": "builder",
        "label": "Construir",
        "instruction": "Ordena los segmentos para formar una hipótesis contrafactual sobre la pérdida de conocimiento.",
        "words": [
            "Si cada generación",
            "hubiera escuchado",
            "con la misma atención",
            "con la que habló,",
            "ninguna historia",
            "habría sido olvidada",
            "y ninguna lengua",
            "habría muerto en silencio."
        ],
        "answer": "Si cada generación hubiera escuchado con la misma atención con la que habló, ninguna historia habría sido olvidada y ninguna lengua habría muerto en silencio."
    },
    # 8. listening — analyzing pedagogical strategies in a passage
    {
        "type": "listening",
        "label": "Escuchar",
        "instruction": "Lee el siguiente texto sobre la transmisión del conocimiento y responde las preguntas.",
        "text": "Doña Asunción nunca dijo «te voy a enseñar algo». Se sentaba junto a la ceiba, empezaba a hablar, y quien quisiera escuchar se acercaba. No había examen ni diploma. La prueba era otra: si la persona a quien le fue contada la historia podía, años después, contársela a alguien más sin que se perdiera la semilla de verdad que contenía, entonces la transmisión había sido exitosa. Este modelo pedagógico, aunque hubiera sido rechazado por cualquier ministerio de educación moderno, produjo generaciones de sabedores cuyo conocimiento habría sido reconocido como patrimonio inmaterial de la humanidad si alguien hubiera tenido la visión de documentarlo a tiempo.",
        "questions": [
            {
                "q": "¿Cuál era el criterio de evaluación en la pedagogía de Doña Asunción?",
                "options": [
                    "Que el receptor pudiera retransmitir la historia conservando su verdad esencial",
                    "Que el estudiante repitiera la historia palabra por palabra",
                    "Que el estudiante aprobara un examen escrito",
                    "Que el estudiante memorizara fechas y datos"
                ],
                "answer": "Que el receptor pudiera retransmitir la historia conservando su verdad esencial"
            },
            {
                "q": "¿Qué estructura gramatical usa el texto para señalar una oportunidad perdida?",
                "options": [
                    "Pluscuamperfecto de subjuntivo + condicional perfecto: «habría sido reconocido si alguien hubiera tenido la visión»",
                    "Presente de indicativo con valor atemporal",
                    "Futuro de subjuntivo con valor hipotético",
                    "Imperativo negativo con función persuasiva"
                ],
                "answer": "Pluscuamperfecto de subjuntivo + condicional perfecto: «habría sido reconocido si alguien hubiera tenido la visión»"
            },
            {
                "q": "¿Por qué la cláusula «aunque hubiera sido rechazado por cualquier ministerio» refuerza el argumento?",
                "options": [
                    "Porque concede la objeción más fuerte —la falta de reconocimiento oficial— y aun así sostiene la validez del modelo",
                    "Porque debilita el argumento al admitir un fallo",
                    "Porque es una digresión innecesaria",
                    "Porque elogia al ministerio de educación"
                ],
                "answer": "Porque concede la objeción más fuerte —la falta de reconocimiento oficial— y aun así sostiene la validez del modelo"
            }
        ]
    },
    # 9. pair — types of knowledge transmission and examples
    {
        "type": "pair",
        "label": "Emparejar",
        "instruction": "Conecta cada forma de transmisión del conocimiento con su característica distintiva.",
        "pairs": [
            [
                "Tradición oral",
                "El conocimiento se adapta a cada contexto de enunciación y cambia con cada narrador"
            ],
            [
                "Texto escrito",
                "El conocimiento queda fijado en una forma estable que trasciende al autor"
            ],
            [
                "Aprendizaje por observación",
                "El conocimiento se transmite sin mediación verbal, a través del ejemplo"
            ],
            [
                "Enseñanza formal",
                "El conocimiento se estructura en secuencias progresivas con evaluación explícita"
            ],
            [
                "Relato mítico",
                "El conocimiento se codifica en símbolos y arquetipos de significado colectivo"
            ],
            [
                "Juego tradicional",
                "El conocimiento se transmite a través de reglas implícitas aprendidas por participación"
            ]
        ]
    },
    # 10. translation — idiomatic expressions about wisdom and teaching
    {
        "type": "translation",
        "label": "Mediación textual",
        "instruction": "Empareja cada expresión idiomática sobre la enseñanza con su significado.",
        "pairs": [
            {
                "source": "Dar en el clavo",
                "target": "Encontrar exactamente la explicación o el ejemplo que hace que el aprendiz comprenda"
            },
            {
                "source": "Predicar en el desierto",
                "target": "Enseñar sin que nadie preste atención ni aplique lo aprendido"
            },
            {
                "source": "Meter la cucharada",
                "target": "Intervenir en la conversación ajena para aportar un dato o una corrección"
            },
            {
                "source": "Hablar hasta por los codos",
                "target": "Explicar de manera excesiva sin dar espacio a la participación del otro"
            },
            {
                "source": "Caer en saco roto",
                "target": "Que una enseñanza o consejo sea ignorado y no produzca ningún efecto"
            },
            {
                "source": "Dar gato por liebre",
                "target": "Presentar información falsa o de baja calidad haciéndola pasar por conocimiento legítimo"
            }
        ]
    },
    # 11. category — classifying pedagogical approaches
    {
        "type": "category",
        "label": "Clasificar",
        "instruction": "Clasifica cada práctica pedagógica según el paradigma al que pertenece.",
        "categories": [
            "Paradigma conductista",
            "Paradigma constructivista",
            "Paradigma sociocultural"
        ],
        "items": [
            {"text": "El estudiante repite la forma correcta hasta que la automatiza.", "category": "Paradigma conductista"},
            {"text": "El aprendiz construye su propia comprensión a partir de la experiencia.", "category": "Paradigma constructivista"},
            {"text": "El maestro actúa como mediador entre el conocimiento y el aprendiz.", "category": "Paradigma sociocultural"},
            {"text": "Se refuerzan las respuestas correctas y se corrigen las incorrectas.", "category": "Paradigma conductista"},
            {"text": "El aprendizaje ocurre en la zona de desarrollo próximo con apoyo del más experto.", "category": "Paradigma sociocultural"},
            {"text": "El error es una oportunidad de reorganizar las estructuras mentales.", "category": "Paradigma constructivista"}
        ]
    },
    # 12. dictation — passage about guardians of stories
    {
        "type": "dictation",
        "label": "Dictado",
        "instruction": "Escucha y escribe este pasaje sobre los guardianes de historias. Presta atención a las formas del subjuntivo, la voz pasiva y las expresiones idiomáticas.",
        "text": "Si las historias hubieran sido consideradas patrimonio desde el principio, los guardianes de la tradición oral habrían sido reconocidos como lo que siempre fueron: los primeros maestros. Aunque la academia los haya ignorado durante siglos, sin que nadie les pidiera permiso, siguieron contando. Porque una historia que no es contada cae en saco roto, y un pueblo sin historias es un pueblo sin memoria."
    }
]


# ─────────────────────────────────────────────────────────────
# New games for dest58: "El espíritu completo"
# Theme: final synthesis, La Palabra del Corazón (escuchar)
# Current: 34 games — already above target, no new games needed
# ─────────────────────────────────────────────────────────────
DEST58_NEW_GAMES = []


# ─────────────────────────────────────────────────────────────
# Map destination IDs to their new games
# ─────────────────────────────────────────────────────────────
NEW_GAMES_MAP = {
    "dest54": DEST54_NEW_GAMES,
    "dest55": DEST55_NEW_GAMES,
    "dest56": DEST56_NEW_GAMES,
    "dest57": DEST57_NEW_GAMES,
    "dest58": DEST58_NEW_GAMES,
}


def process_destination(dest_id: str) -> dict:
    """Read dest JSON, insert new games before escaperoom+cronica, write back."""
    path = os.path.join(CONTENT_DIR, f"{dest_id}.json")
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    games = data["games"]
    old_count = len(games)

    # Separate tail games (escaperoom, cronica) from body
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
    current_total = len(body_games) + len(tail_games)
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
    print("Authoring new games for C2 Advanced (dest54-58)")
    print(f"Target: {TARGET_TOTAL} games per destination")
    print("=" * 60)
    print()

    results = []
    for d in range(54, 59):
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
