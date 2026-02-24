#!/usr/bin/env python3
"""
Add missing vocab LTs, fix orphan LOs/LTs, expand thin B2 objectives,
add voice activities, flashnotes, and cultura activities.
"""
import json
import os
from collections import defaultdict

BASE = os.path.dirname(os.path.abspath(__file__))


def load_json(filename):
    with open(os.path.join(BASE, filename), "r", encoding="utf-8") as f:
        return json.load(f)


def save_json(filename, data):
    with open(os.path.join(BASE, filename), "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        f.write("\n")


# ─── Load ────────────────────────────────────────────────────
targets = load_json("linguistic-targets.json")
activities = load_json("activities.json")

# ═══════════════════════════════════════════════════════════════
# 1. ADD MISSING VOCAB LTs
# ═══════════════════════════════════════════════════════════════
new_lts = [
    {
        "id": "lt_vocab_daily_routines",
        "type": "LinguisticTarget",
        "category": "vocab",
        "lexicalItem": {
            "type": "VocabGroup",
            "items": ["levantarse", "desayunar", "almorzar", "trabajar",
                      "estudiar", "cocinar", "limpiar", "descansar", "acostarse"]
        },
        "features": {"function": "daily_activities"},
        "cefr": "A2",
        "tags": ["routines", "daily life", "reflexive", "la llamada"]
    },
    {
        "id": "lt_vocab_abstract_concepts",
        "type": "LinguisticTarget",
        "category": "vocab",
        "lexicalItem": {
            "type": "VocabGroup",
            "items": ["libertad", "justicia", "igualdad", "paz", "poder",
                      "derecho", "deber", "respeto", "verdad", "memoria"]
        },
        "features": {"function": "abstract_social"},
        "cefr": "B1",
        "tags": ["abstract", "social concepts", "values", "las voces"]
    },
    {
        "id": "lt_vocab_formal_register",
        "type": "LinguisticTarget",
        "category": "vocab",
        "lexicalItem": {
            "type": "VocabGroup",
            "items": ["desarrollo", "inversión", "impacto", "compromiso",
                      "sostenibilidad", "patrimonio", "diversidad", "inclusión",
                      "infraestructura", "recurso"]
        },
        "features": {"function": "institutional_formal"},
        "cefr": "B2",
        "tags": ["formal register", "institutional", "development", "el legado"]
    },
    {
        "id": "lt_vocab_idioms_colloquial",
        "type": "LinguisticTarget",
        "category": "vocab",
        "lexicalItem": {
            "type": "VocabGroup",
            "items": ["echar de menos", "dar la lata", "no dar pie con bola",
                      "quedarse en blanco", "dar en el clavo", "tomar el pelo",
                      "ponerse las pilas", "meter la pata", "estar hecho polvo",
                      "irse por las ramas"]
        },
        "features": {"function": "idiomatic_expression"},
        "cefr": "C1",
        "tags": ["idioms", "colloquial", "expressions", "las voces antiguas"]
    },
    {
        "id": "lt_vocab_literary_specialized",
        "type": "LinguisticTarget",
        "category": "vocab",
        "lexicalItem": {
            "type": "VocabGroup",
            "items": ["metáfora", "sinestesia", "alegoría", "oxímoron",
                      "elipsis", "epifanía", "leitmotiv", "pastiche",
                      "palimpsesto", "intertextualidad"]
        },
        "features": {"function": "literary_metalanguage"},
        "cefr": "C2",
        "tags": ["literary", "specialized", "metalanguage", "la palabra del corazon"]
    }
]

# Insert each LT after the appropriate anchor
anchors = {
    "lt_vocab_daily_routines": "lt_vocab_weather_seasons",
    "lt_vocab_abstract_concepts": "lt_vocab_community_society",
    "lt_vocab_formal_register": "lt_vocab_abstract_inner_world",
    "lt_vocab_idioms_colloquial": "lt_vocab_academic_formal",
    "lt_vocab_literary_specialized": "lt_grammar_total_integration",
}

for lt in reversed(new_lts):
    anchor = anchors[lt["id"]]
    idx = next((i for i, t in enumerate(targets) if t.get("id") == anchor), None)
    if idx is not None:
        targets.insert(idx + 1, lt)
    else:
        targets.append(lt)

save_json("linguistic-targets.json", targets)
lt_count = sum(1 for t in targets if "id" in t)
print(f"Added {len(new_lts)} new vocab LTs. Total LTs: {lt_count}")


# ═══════════════════════════════════════════════════════════════
# 2. ADD NEW ACTIVITIES
# ═══════════════════════════════════════════════════════════════
new_acts = []

# ─── Orphan LO: lo_recognize_familiar_words (A1) ────────────
new_acts.extend([
    {
        "id": "act_dest1_listening_familiar_words",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_vocab_greetings", "lt_vocab_family"],
        "objective": "lo_recognize_familiar_words",
        "inputMode": "listen",
        "cefr": "A1",
        "destination": "dest1",
        "prompt": "Escucha. ¿Qué palabra oyes? Selecciona la imagen.",
        "feedback": {"onCorrect": "Bien. Reconoces la palabra.", "onIncorrect": "Escucha otra vez. La palabra es..."},
        "difficulty": "guided"
    },
    {
        "id": "act_dest2_fill_familiar_words",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_vocab_nature_landscape", "lt_vocab_animals_general"],
        "objective": "lo_recognize_familiar_words",
        "inputMode": "choice",
        "cefr": "A1",
        "destination": "dest2",
        "prompt": "Mira la imagen. ¿Qué ves? Selecciona la palabra.",
        "feedback": {"onCorrect": "Correcto. Ese es el nombre.", "onIncorrect": "Mira otra vez. Eso es un..."},
        "difficulty": "guided",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_understand_simple_instructions (A1) ──────
new_acts.extend([
    {
        "id": "act_dest3_listening_instructions",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_verb_ser_indicative_present", "lt_grammar_prepositions_basic"],
        "objective": "lo_understand_simple_instructions",
        "inputMode": "listen",
        "cefr": "A1",
        "destination": "dest3",
        "prompt": "Yaguará dice: 'Camina al río.' Escucha y sigue la instrucción.",
        "feedback": {"onCorrect": "Muy bien. Entiendes la instrucción.", "onIncorrect": "Escucha otra vez. Camina... al río."},
        "difficulty": "guided"
    },
    {
        "id": "act_dest4_fill_instructions",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_prepositions_basic"],
        "objective": "lo_understand_simple_instructions",
        "inputMode": "choice",
        "cefr": "A1",
        "destination": "dest4",
        "prompt": "Completa la instrucción: Camina ___ el bosque. (en / de / a)",
        "feedback": {"onCorrect": "Correcto. Camina al bosque.", "onIncorrect": "La preposición correcta es 'a'. Camina al bosque."},
        "difficulty": "guided",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_understand_relevant_phrases (A2) ─────────
new_acts.extend([
    {
        "id": "act_dest13_listening_relevant_phrases",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_grammar_preterite_regular", "lt_vocab_travel"],
        "objective": "lo_understand_relevant_phrases",
        "inputMode": "listen",
        "cefr": "A2",
        "destination": "dest13",
        "prompt": "Escucha la historia de viaje. ¿Qué pasó primero?",
        "feedback": {"onCorrect": "Bien. Entiendes el orden.", "onIncorrect": "Escucha otra vez. Primero..."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest14_fill_relevant_phrases",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_reflexive_verbs", "lt_vocab_travel"],
        "objective": "lo_understand_relevant_phrases",
        "inputMode": "choice",
        "cefr": "A2",
        "destination": "dest14",
        "prompt": "Lee y completa: Me ___ temprano para el viaje. (levanté / levanto / levantaré)",
        "feedback": {"onCorrect": "Correcto. Me levanté.", "onIncorrect": "En el pasado: me levanté."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_simple_exchange (A2) ─────────────────────
new_acts.extend([
    {
        "id": "act_dest13_conversation_simple_exchange",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_reflexive_verbs", "lt_grammar_object_pronouns", "lt_vocab_emotions_expanded"],
        "objective": "lo_simple_exchange",
        "inputMode": "choice",
        "cefr": "A2",
        "destination": "dest13",
        "prompt": "Candelaria pregunta: '¿Cómo te sientes hoy?' Responde con una emoción.",
        "feedback": {"onCorrect": "Bien. Compartiste cómo te sientes.", "onIncorrect": "Puedes decir: Me siento bien / mal / cansado."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest15_conversation_exchange_object",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_object_pronouns", "lt_vocab_emotions_expanded"],
        "objective": "lo_simple_exchange",
        "inputMode": "typing",
        "cefr": "A2",
        "destination": "dest15",
        "prompt": "Pregúntale a Yaguará: '¿Lo viste?' y describe lo que pasó.",
        "feedback": {"onCorrect": "Bien. Usaste los pronombres.", "onIncorrect": "Recuerda: lo/la van antes del verbo."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_give_negative_commands (A2) ──────────────
new_acts.extend([
    {
        "id": "act_dest15_fill_negative_commands",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_imperative_negative", "lt_grammar_imperative_affirmative"],
        "objective": "lo_give_negative_commands",
        "inputMode": "choice",
        "cefr": "A2",
        "destination": "dest15",
        "prompt": "Completa: ¡No ___ solo por el bosque! (camines / camina / caminando)",
        "feedback": {"onCorrect": "Correcto. No camines: imperativo negativo.", "onIncorrect": "Imperativo negativo: no + subjuntivo. No camines."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest16_conjugation_imperative_neg",
        "type": "Activity",
        "gameType": "conjugation",
        "practices": ["lt_verb_tener_imperative_negative", "lt_grammar_imperative_negative"],
        "objective": "lo_give_negative_commands",
        "inputMode": "typing",
        "cefr": "A2",
        "destination": "dest16",
        "prompt": "Conjuga en imperativo negativo: tener (tú) → no ___",
        "feedback": {"onCorrect": "Correcto. No tengas.", "onIncorrect": "Imperativo negativo de tener: no tengas."},
        "difficulty": "semi_guided",
        "targetPerson": "tú",
        "scaffoldable": True
    },
    {
        "id": "act_dest16_conversation_commands_voice",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_imperative_affirmative", "lt_grammar_imperative_negative"],
        "objective": "lo_give_negative_commands",
        "inputMode": "voice",
        "cefr": "A2",
        "destination": "dest16",
        "prompt": "Yaguará está en peligro. Dale instrucciones: corre, no pares, no tengas miedo.",
        "feedback": {"onCorrect": "Bien. Tus instrucciones son claras.", "onIncorrect": "Usa: corre, no pares, no tengas miedo."},
        "difficulty": "semi_guided"
    }
])

# ─── Orphan LO: lo_express_fluently (C1) ────────────────────
new_acts.extend([
    {
        "id": "act_dest39_conversation_fluent_express",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_subjunctive_imperfect", "lt_grammar_si_clauses", "lt_grammar_register_adaptation"],
        "objective": "lo_express_fluently",
        "inputMode": "voice",
        "cefr": "C1",
        "destination": "dest39",
        "prompt": "El Mamo pregunta: 'Si pudieras restaurar un solo lugar, ¿cuál sería y por qué?' Responde con fluidez.",
        "feedback": {"onCorrect": "Expresión fluida y precisa.", "onIncorrect": "Usa: si + imperfecto de subjuntivo + condicional."},
        "difficulty": "open"
    },
    {
        "id": "act_dest40_conversation_spontaneous_c1",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_register_adaptation", "lt_grammar_si_clauses"],
        "objective": "lo_express_fluently",
        "inputMode": "typing",
        "cefr": "C1",
        "destination": "dest40",
        "prompt": "Debate abierto: ¿Es posible conservar la naturaleza y el progreso económico al mismo tiempo?",
        "feedback": {"onCorrect": "Argumentación fluida.", "onIncorrect": "Conecta ideas: por un lado... por otro, si bien... no obstante."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_flexible_language_use (C1) ───────────────
new_acts.extend([
    {
        "id": "act_dest41_conversation_register_flex",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_register_adaptation", "lt_grammar_si_clauses"],
        "objective": "lo_flexible_language_use",
        "inputMode": "typing",
        "cefr": "C1",
        "destination": "dest41",
        "prompt": "Explica el mismo concepto a tres personas: un niño, un profesor, un político. Adapta tu registro.",
        "feedback": {"onCorrect": "Excelente adaptación de registro.", "onIncorrect": "Cada audiencia necesita un registro diferente."},
        "difficulty": "open",
        "scaffoldable": True
    },
    {
        "id": "act_dest42_builder_register_flex",
        "type": "Activity",
        "gameType": "builder",
        "practices": ["lt_grammar_register_adaptation", "lt_vocab_idioms_colloquial"],
        "objective": "lo_flexible_language_use",
        "inputMode": "drag",
        "cefr": "C1",
        "destination": "dest42",
        "prompt": "Ordena las frases según su registro: coloquial, neutro, formal, académico.",
        "feedback": {"onCorrect": "Correcto. Cada registro tiene su lugar.", "onIncorrect": "Piensa en quién habla y a quién."},
        "difficulty": "open"
    }
])

# ─── Orphan LO: lo_figurative_meaning (C1) ──────────────────
new_acts.extend([
    {
        "id": "act_dest43_listening_figurative_c1",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_grammar_subjunctive_imperfect", "lt_grammar_register_adaptation"],
        "objective": "lo_figurative_meaning",
        "inputMode": "listen",
        "cefr": "C1",
        "destination": "dest43",
        "prompt": "Escucha al Mamo. ¿Qué significa 'la montaña se durmió'?",
        "feedback": {"onCorrect": "Entiendes el significado figurado.", "onIncorrect": "'La montaña se durmió' es una metáfora de la pérdida de memoria cultural."},
        "difficulty": "open"
    },
    {
        "id": "act_dest44_fill_figurative_c1",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_register_adaptation"],
        "objective": "lo_figurative_meaning",
        "inputMode": "typing",
        "cefr": "C1",
        "destination": "dest44",
        "prompt": "El texto dice: 'las piedras guardan palabras.' Explica el significado figurado.",
        "feedback": {"onCorrect": "Excelente interpretación metafórica.", "onIncorrect": "¿Qué representan las piedras y las palabras?"},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_precision_nuance (C2) ────────────────────
new_acts.extend([
    {
        "id": "act_dest49_conversation_precision_c2",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_literary_register", "lt_grammar_nuanced_connectors"],
        "objective": "lo_precision_nuance",
        "inputMode": "typing",
        "cefr": "C2",
        "destination": "dest49",
        "prompt": "Distingue: 'El río canta' vs 'El río suena' vs 'El río murmura'. ¿Qué matiz aporta cada verbo?",
        "feedback": {"onCorrect": "Precisión notable.", "onIncorrect": "Canta (alegría), suena (neutro), murmura (misterio)."},
        "difficulty": "open",
        "scaffoldable": True
    },
    {
        "id": "act_dest50_fill_nuance_c2",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_nuanced_connectors", "lt_grammar_literary_register"],
        "objective": "lo_precision_nuance",
        "inputMode": "typing",
        "cefr": "C2",
        "destination": "dest50",
        "prompt": "Elige el conector preciso: 'La deforestación avanza; ___, las comunidades resisten.' (no obstante / por consiguiente / de ahí que)",
        "feedback": {"onCorrect": "Conector preciso para el contraste.", "onIncorrect": "Aquí se necesita contraste: no obstante."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_mediate_viewpoints (C2) ──────────────────
new_acts.extend([
    {
        "id": "act_dest51_conversation_mediation_c2",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_mediation_reformulation", "lt_grammar_nuanced_connectors"],
        "objective": "lo_mediate_viewpoints",
        "inputMode": "voice",
        "cefr": "C2",
        "destination": "dest51",
        "prompt": "El biólogo y el ganadero discuten. Media entre ambos: reformula lo que cada uno dice.",
        "feedback": {"onCorrect": "Mediación eficaz.", "onIncorrect": "Reformula: 'Lo que quiere decir es que...'"},
        "difficulty": "open"
    },
    {
        "id": "act_dest52_conversation_mediation_cultural",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_mediation_reformulation"],
        "objective": "lo_mediate_viewpoints",
        "inputMode": "typing",
        "cefr": "C2",
        "destination": "dest52",
        "prompt": "El Mamo y el funcionario tienen visiones diferentes del 'progreso'. Encuentra terreno común.",
        "feedback": {"onCorrect": "Mediación respetuosa.", "onIncorrect": "Busca lo que comparten: bienestar para la comunidad."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Orphan LO: lo_reformulate_for_audiences (C2) ───────────
new_acts.extend([
    {
        "id": "act_dest55_conversation_reformulate_c2",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_mediation_reformulation", "lt_grammar_literary_register"],
        "objective": "lo_reformulate_for_audiences",
        "inputMode": "typing",
        "cefr": "C2",
        "destination": "dest55",
        "prompt": "Reformula para tres audiencias (niño, académico, abuelo): 'La biodiversidad es un indicador de salud ecosistémica.'",
        "feedback": {"onCorrect": "Reformulación efectiva.", "onIncorrect": "Para el niño: 'Cuando hay muchos animales, el bosque está sano.'"},
        "difficulty": "open",
        "scaffoldable": True
    },
    {
        "id": "act_dest56_builder_reformulate_c2",
        "type": "Activity",
        "gameType": "builder",
        "practices": ["lt_grammar_mediation_reformulation"],
        "objective": "lo_reformulate_for_audiences",
        "inputMode": "drag",
        "cefr": "C2",
        "destination": "dest56",
        "prompt": "Ordena los fragmentos para construir la misma idea en registro académico vs poético.",
        "feedback": {"onCorrect": "Dos registros, una idea.", "onIncorrect": "Académico: nominalizaciones. Poético: imágenes."},
        "difficulty": "open"
    }
])

# ─── Thin B2: lo_fluent_interaction (+5) ────────────────────
new_acts.extend([
    {
        "id": "act_dest29_conversation_fluent_subj",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_subjunctive_in_clauses", "lt_grammar_conditional"],
        "objective": "lo_fluent_interaction",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest29",
        "prompt": "El Silencio Gris avanza. ¿Qué harían si perdieran la voz?",
        "feedback": {"onCorrect": "Buena respuesta con condicional.", "onIncorrect": "Si perdiera la voz, yo haría... (condicional)."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest29_fill_conditional_clauses",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_conditional", "lt_grammar_subjunctive_in_clauses"],
        "objective": "lo_fluent_interaction",
        "inputMode": "typing",
        "cefr": "B2",
        "destination": "dest29",
        "prompt": "Completa: Aunque ___ (poder) hablar, a veces elegimos el silencio.",
        "feedback": {"onCorrect": "Aunque podamos... subjuntivo.", "onIncorrect": "Aunque + subjuntivo: aunque podamos."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest30_conversation_fluent_debate",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_subjunctive_in_clauses", "lt_grammar_conditional"],
        "objective": "lo_fluent_interaction",
        "inputMode": "typing",
        "cefr": "B2",
        "destination": "dest30",
        "prompt": "Debate con Candelaria: ¿Merece Don Próspero una segunda oportunidad? Argumenta.",
        "feedback": {"onCorrect": "Interacción fluida.", "onIncorrect": "Expresa: creo que..., no creo que + subjuntivo."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest31_builder_conditional",
        "type": "Activity",
        "gameType": "builder",
        "practices": ["lt_grammar_conditional"],
        "objective": "lo_fluent_interaction",
        "inputMode": "drag",
        "cefr": "B2",
        "destination": "dest31",
        "prompt": "Construye oraciones: ordena piezas para formar si + subjuntivo + condicional.",
        "feedback": {"onCorrect": "Estructura condicional correcta.", "onIncorrect": "Si + imperfecto subjuntivo, + condicional."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest32_conversation_spontaneous_b2",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_subjunctive_in_clauses", "lt_grammar_conditional"],
        "objective": "lo_fluent_interaction",
        "inputMode": "voice",
        "cefr": "B2",
        "destination": "dest32",
        "prompt": "Habla libremente: ¿Qué consejo le darías al joven Yaguará?",
        "feedback": {"onCorrect": "Expresión oral fluida.", "onIncorrect": "Yo le diría que... Es importante que..."},
        "difficulty": "open"
    }
])

# ─── Thin B2: lo_sustain_viewpoints (+5) ────────────────────
new_acts.extend([
    {
        "id": "act_dest30_fill_reported_speech",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_reported_speech", "lt_grammar_conditional"],
        "objective": "lo_sustain_viewpoints",
        "inputMode": "typing",
        "cefr": "B2",
        "destination": "dest30",
        "prompt": "Don Próspero dijo: 'El progreso lo justifica todo.' Reformula: dijo que el progreso lo ___ todo.",
        "feedback": {"onCorrect": "Justificaba — discurso indirecto.", "onIncorrect": "Discurso indirecto: dijo que + imperfecto."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest31_conversation_viewpoint",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_conditional", "lt_grammar_reported_speech"],
        "objective": "lo_sustain_viewpoints",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest31",
        "prompt": "Doña Asunción dice que la tierra tiene memoria. ¿Estás de acuerdo? Sustenta.",
        "feedback": {"onCorrect": "Argumento bien sustentado.", "onIncorrect": "Usa: creo que..., porque..., además..., por lo tanto..."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest32_builder_argument_b2",
        "type": "Activity",
        "gameType": "builder",
        "practices": ["lt_grammar_conditional"],
        "objective": "lo_sustain_viewpoints",
        "inputMode": "drag",
        "cefr": "B2",
        "destination": "dest32",
        "prompt": "Ordena fragmentos para un argumento coherente sobre la conservación del río.",
        "feedback": {"onCorrect": "Argumento bien estructurado.", "onIncorrect": "Tesis, razón, evidencia, conclusión."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest33_conversation_defend_view",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_reported_speech", "lt_grammar_conditional"],
        "objective": "lo_sustain_viewpoints",
        "inputMode": "typing",
        "cefr": "B2",
        "destination": "dest33",
        "prompt": "El consejo te pide opinión: ¿Debe el río ser protegido o usado para industria?",
        "feedback": {"onCorrect": "Posición clara con argumentos.", "onIncorrect": "En primer lugar..., por otra parte..., en conclusión..."},
        "difficulty": "open",
        "scaffoldable": True
    },
    {
        "id": "act_dest34_conversation_debate_view",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_conditional", "lt_grammar_reported_speech"],
        "objective": "lo_sustain_viewpoints",
        "inputMode": "voice",
        "cefr": "B2",
        "destination": "dest34",
        "prompt": "Debate oral: presenta tu visión sobre el futuro del ecosistema y responde objeciones.",
        "feedback": {"onCorrect": "Discurso oral sostenido.", "onIncorrect": "Anticipa: 'Algunos dirían que..., sin embargo...'"},
        "difficulty": "open"
    }
])

# ─── Thin B2: lo_understand_implicit (+4) ───────────────────
new_acts.extend([
    {
        "id": "act_dest31_listening_implicit_b2",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_grammar_subjunctive_in_clauses", "lt_grammar_reported_speech"],
        "objective": "lo_understand_implicit",
        "inputMode": "listen",
        "cefr": "B2",
        "destination": "dest31",
        "prompt": "Escucha el diálogo entre Don Próspero y Candelaria. ¿Qué quiere decir realmente?",
        "feedback": {"onCorrect": "Entiendes el significado implícito.", "onIncorrect": "Las palabras dicen una cosa, el tono dice otra."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest32_fill_implicit_b2",
        "type": "Activity",
        "gameType": "fill",
        "practices": ["lt_grammar_reported_speech"],
        "objective": "lo_understand_implicit",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest32",
        "prompt": "Don Próspero dice: 'Estoy aquí para ayudar.' Sus acciones muestran que realmente quiere...",
        "feedback": {"onCorrect": "Distingues lo dicho de lo implicado.", "onIncorrect": "Lee entre líneas: lo que dice vs lo que hace."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    {
        "id": "act_dest33_listening_pragmatic_b2",
        "type": "Activity",
        "gameType": "listening",
        "practices": ["lt_grammar_subjunctive_in_clauses"],
        "objective": "lo_understand_implicit",
        "inputMode": "listen",
        "cefr": "B2",
        "destination": "dest33",
        "prompt": "Doña Asunción dice: 'el silencio también habla.' ¿Qué significa?",
        "feedback": {"onCorrect": "Interpretación pragmática correcta.", "onIncorrect": "Lo no dicho tiene poder."},
        "difficulty": "open"
    },
    {
        "id": "act_dest34_conversation_implicit_b2",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_reported_speech", "lt_grammar_subjunctive_in_clauses"],
        "objective": "lo_understand_implicit",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest34",
        "prompt": "El personaje dice algo ambiguo. Elige la interpretación más adecuada.",
        "feedback": {"onCorrect": "Excelente inferencia pragmática.", "onIncorrect": "Considera: quién habla, a quién, y por qué."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Voice activities for A1-A2 (6) ─────────────────────────
new_acts.extend([
    {
        "id": "act_dest1_conversation_voice_greeting",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_vocab_greetings", "lt_verb_ser_indicative_present"],
        "objective": "lo_introduce_self",
        "inputMode": "voice",
        "cefr": "A1",
        "destination": "dest1",
        "prompt": "Di en voz alta: 'Hola, yo soy [tu nombre].' Yaguará te escucha.",
        "feedback": {"onCorrect": "Bien dicho. Yaguará te escuchó.", "onIncorrect": "Repite: Hola, yo soy... y di tu nombre."},
        "difficulty": "guided"
    },
    {
        "id": "act_dest3_conversation_voice_describe",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_verb_ser_indicative_present", "lt_grammar_adjective_agreement"],
        "objective": "lo_describe_environment",
        "inputMode": "voice",
        "cefr": "A1",
        "destination": "dest3",
        "prompt": "Describe en voz alta: 'El bosque es grande y verde.'",
        "feedback": {"onCorrect": "Bien. Describiste el bosque.", "onIncorrect": "Di: El bosque es... y un adjetivo."},
        "difficulty": "guided"
    },
    {
        "id": "act_dest5_conversation_voice_preferences",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_verb_gustar_indicative_present", "lt_grammar_negation"],
        "objective": "lo_express_preferences",
        "inputMode": "voice",
        "cefr": "A1",
        "destination": "dest5",
        "prompt": "Di en voz alta qué te gusta: 'Me gusta el río' o 'No me gusta el frío.'",
        "feedback": {"onCorrect": "Expresaste tu preferencia.", "onIncorrect": "Di: Me gusta... o No me gusta..."},
        "difficulty": "guided"
    },
    {
        "id": "act_dest13_conversation_voice_routines",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_reflexive_verbs", "lt_vocab_daily_routines"],
        "objective": "lo_describe_routines_detail",
        "inputMode": "voice",
        "cefr": "A2",
        "destination": "dest13",
        "prompt": "Cuenta tu rutina en voz alta: 'Me levanto, desayuno, estudio...'",
        "feedback": {"onCorrect": "Bien. Describiste tu rutina.", "onIncorrect": "Empieza: Me levanto a las... Después..."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest15_conversation_voice_needs",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_verb_necesitar_indicative_present", "lt_grammar_modal_infinitive"],
        "objective": "lo_express_needs_wants",
        "inputMode": "voice",
        "cefr": "A2",
        "destination": "dest15",
        "prompt": "Di en voz alta: 'Necesito encontrar el camino.'",
        "feedback": {"onCorrect": "Bien expresado.", "onIncorrect": "Di: Necesito + infinitivo."},
        "difficulty": "semi_guided"
    },
    {
        "id": "act_dest17_conversation_voice_integrate",
        "type": "Activity",
        "gameType": "conversation",
        "practices": ["lt_grammar_imperfect_regular", "lt_grammar_ir_a_future"],
        "objective": "lo_integrate_a2",
        "inputMode": "voice",
        "cefr": "A2",
        "destination": "dest17",
        "prompt": "Cuenta: ¿cómo era antes? ¿cómo va a ser el futuro?",
        "feedback": {"onCorrect": "Narración temporal clara.", "onIncorrect": "Pasado: era... Futuro: va a ser..."},
        "difficulty": "semi_guided"
    }
])

# ─── Flashnote activities (12 — one per sub-level) ──────────
new_acts.extend([
    # A1 Basic
    {
        "id": "act_dest2_flashnote_articles",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_articles_definite", "lt_grammar_noun_gender"],
        "objective": "lo_name_objects",
        "inputMode": "choice",
        "cefr": "A1",
        "destination": "dest2",
        "prompt": "Cápsula: Las palabras tienen género. El río (m), la selva (f). ¿Cuál es correcto: el agua o la agua?",
        "feedback": {"onCorrect": "'El agua' — excepción por fonética.", "onIncorrect": "Femeninas con 'a' tónica usan 'el': el agua, el águila."},
        "difficulty": "guided",
        "scaffoldable": True
    },
    # A1 Advanced
    {
        "id": "act_dest7_flashnote_ser_estar",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_ser_vs_estar"],
        "objective": "lo_describe_environment",
        "inputMode": "choice",
        "cefr": "A1",
        "destination": "dest7",
        "prompt": "Cápsula: Ser = identidad. Estar = estado. Yaguará ES un jaguar. Yaguará ESTÁ cansada. '¿La fruta ___ verde?'",
        "feedback": {"onCorrect": "ES verde (color) o ESTÁ verde (inmadura). Ambos correctos.", "onIncorrect": "Ser = permanente. Estar = temporal."},
        "difficulty": "guided",
        "scaffoldable": True
    },
    # A2 Basic
    {
        "id": "act_dest13_flashnote_preterite",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_preterite_regular"],
        "objective": "lo_describe_background",
        "inputMode": "choice",
        "cefr": "A2",
        "destination": "dest13",
        "prompt": "Cápsula: Pretérito = acción completada. -AR: hablé. -ER/-IR: comí. ¿Cómo dices 'yo viajé'?",
        "feedback": {"onCorrect": "Viajé — pretérito de viajar.", "onIncorrect": "Viajar → viaj + é = viajé."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # A2 Advanced
    {
        "id": "act_dest16_flashnote_imperative",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_imperative_negative", "lt_grammar_imperative_affirmative"],
        "objective": "lo_give_negative_commands",
        "inputMode": "choice",
        "cefr": "A2",
        "destination": "dest16",
        "prompt": "Cápsula: Afirmativo: ¡Habla! Negativo: ¡No hables! (subjuntivo). ¿Correcto: 'No comes' o 'No comas'?",
        "feedback": {"onCorrect": "¡No comas! — imperativo negativo.", "onIncorrect": "Negativo: no + subjuntivo. No comas."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B1 Basic
    {
        "id": "act_dest19_flashnote_pret_vs_imp",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_preterite_vs_imperfect"],
        "objective": "lo_narrate_events",
        "inputMode": "choice",
        "cefr": "B1",
        "destination": "dest19",
        "prompt": "Cápsula: Pretérito = completado (llegué). Imperfecto = contexto (llovía). 'Cuando ___ al pueblo, ___.'",
        "feedback": {"onCorrect": "Llegué + llovía.", "onIncorrect": "Acción: pretérito. Contexto: imperfecto."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B1 Advanced
    {
        "id": "act_dest24_flashnote_subjunctive",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_subjunctive_present_basic"],
        "objective": "lo_explain_opinions",
        "inputMode": "choice",
        "cefr": "B1",
        "destination": "dest24",
        "prompt": "Cápsula: Subjuntivo = deseos, duda. 'Quiero que vengas.' 'Espero que tú ___ (entender).'",
        "feedback": {"onCorrect": "Entiendas — subjuntivo.", "onIncorrect": "Espero que + subjuntivo. Entender → entiendas."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B2 Basic
    {
        "id": "act_dest29_flashnote_conditional",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_conditional", "lt_grammar_hypothetical_basic"],
        "objective": "lo_hypothesize_abstract",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest29",
        "prompt": "Cápsula: Si + imperfecto subjuntivo + condicional. 'Si tuviera alas, volaría.' 'Si ___ (poder), lo haría.'",
        "feedback": {"onCorrect": "Si pudiera, lo haría.", "onIncorrect": "Si + subjuntivo: pudiera. Condicional: haría."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B2 Advanced
    {
        "id": "act_dest35_flashnote_cuyo",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_relative_clauses_complex"],
        "objective": "lo_complex_relative_description",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest35",
        "prompt": "Cápsula: Cuyo/cuya = 'whose'. Concuerda con lo poseído. 'La mujer ___ voz escuchamos.'",
        "feedback": {"onCorrect": "Cuya voz — femenino por 'voz'.", "onIncorrect": "Cuyo/a concuerda con lo poseído: voz (f) → cuya."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # C1 Basic
    {
        "id": "act_dest39_flashnote_subordination",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_complex_subordination"],
        "objective": "lo_complex_subordination",
        "inputMode": "choice",
        "cefr": "C1",
        "destination": "dest39",
        "prompt": "Cápsula: para que, sin que, a menos que + subjuntivo. 'Te lo digo para que lo ___.' (saber)",
        "feedback": {"onCorrect": "Sepas — subjuntivo.", "onIncorrect": "Para que + subjuntivo siempre. Saber → sepas."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C1 Advanced
    {
        "id": "act_dest45_flashnote_pragmatics",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_discourse_pragmatics"],
        "objective": "lo_discourse_analysis",
        "inputMode": "choice",
        "cefr": "C1",
        "destination": "dest45",
        "prompt": "Cápsula: Actos de habla — '¿Tienes hora?' no pregunta si tienes reloj. ¿Qué es '¡Qué bonito vestido!'?",
        "feedback": {"onCorrect": "Un cumplido (acto expresivo).", "onIncorrect": "Parece descripción, pero es un cumplido."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C2 Basic
    {
        "id": "act_dest49_flashnote_register",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_register_mastery"],
        "objective": "lo_register_mastery",
        "inputMode": "choice",
        "cefr": "C2",
        "destination": "dest49",
        "prompt": "Cápsula: Cuatro registros — coloquial (tintico), neutro (café), formal (infusión), literario (brebaje oscuro). ¿Qué registro es 'se procedió a la evaluación'?",
        "feedback": {"onCorrect": "Institucional/formal.", "onIncorrect": "'Se procedió a' es lenguaje burocrático."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C2 Advanced
    {
        "id": "act_dest55_flashnote_metalinguistic",
        "type": "Activity",
        "gameType": "flashnote",
        "practices": ["lt_grammar_metalinguistic_analysis"],
        "objective": "lo_metalinguistic_analysis",
        "inputMode": "choice",
        "cefr": "C2",
        "destination": "dest55",
        "prompt": "Cápsula: Ambigüedad — sintáctica, léxica, pragmática. ¿Qué tipo? 'La puerta está abierta.'",
        "feedback": {"onCorrect": "Pragmática: literal o invitación.", "onIncorrect": "Literal (la puerta) vs intencional (puedes entrar/hablar)."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Cultura activities (12 — one per sub-level) ────────────
new_acts.extend([
    # A1 Basic — Bosque del Pacífico
    {
        "id": "act_dest3_cultura_bosque_pacifico",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_nature_landscape", "lt_vocab_animals_general"],
        "objective": "lo_describe_environment",
        "inputMode": "listen",
        "cefr": "A1",
        "destination": "dest3",
        "prompt": "Nota cultural: El Chocó es una de las regiones más biodiversas del mundo. Aquí viven jaguares, ranas de colores y tucanes.",
        "feedback": {"onCorrect": "El Chocó es hogar de miles de especies.", "onIncorrect": "El Chocó tiene más especies por metro cuadrado que casi cualquier lugar."},
        "difficulty": "guided"
    },
    # A1 Advanced — Río Atrato
    {
        "id": "act_dest8_cultura_rio_atrato",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_nature_landscape"],
        "objective": "lo_describe_home",
        "inputMode": "listen",
        "cefr": "A1",
        "destination": "dest8",
        "prompt": "Nota cultural: El río Atrato tiene derechos. En 2017, Colombia lo reconoció como sujeto de derechos.",
        "feedback": {"onCorrect": "El Atrato tiene derechos legales.", "onIncorrect": "Colombia fue pionera: un río protegido legalmente."},
        "difficulty": "guided"
    },
    # A2 Basic — Marimba de chonta
    {
        "id": "act_dest14_cultura_marimba",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_emotions_expanded"],
        "objective": "lo_describe_routines_detail",
        "inputMode": "listen",
        "cefr": "A2",
        "destination": "dest14",
        "prompt": "Nota cultural: La marimba de chonta es Patrimonio de la Humanidad. Se toca en celebraciones y funerales del Pacífico.",
        "feedback": {"onCorrect": "La marimba conecta alegría y duelo.", "onIncorrect": "Se hace con madera de palma, central en cultura afrocolombiana."},
        "difficulty": "semi_guided"
    },
    # A2 Advanced — Llanos y joropo
    {
        "id": "act_dest17_cultura_llanos",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_weather_seasons"],
        "objective": "lo_integrate_a2",
        "inputMode": "listen",
        "cefr": "A2",
        "destination": "dest17",
        "prompt": "Nota cultural: Los Llanos tienen dos estaciones: sequía e inundación. El joropo es el baile del llanero.",
        "feedback": {"onCorrect": "El llanero vive entre sol y lluvia.", "onIncorrect": "Los Llanos cambian completamente. El joropo celebra esa vida."},
        "difficulty": "semi_guided"
    },
    # B1 Basic — Vallenato
    {
        "id": "act_dest20_cultura_vallenato",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_community_society", "lt_vocab_abstract_concepts"],
        "objective": "lo_retell_stories",
        "inputMode": "listen",
        "cefr": "B1",
        "destination": "dest20",
        "prompt": "Nota cultural: El vallenato nació como forma de contar noticias entre pueblos. Cada canción es una crónica cantada.",
        "feedback": {"onCorrect": "El vallenato es periodismo en música.", "onIncorrect": "Antes de la radio, el juglar llevaba noticias de pueblo en pueblo."},
        "difficulty": "semi_guided"
    },
    # B1 Advanced — Café
    {
        "id": "act_dest26_cultura_cafe",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_community_society"],
        "objective": "lo_present_arguments",
        "inputMode": "choice",
        "cefr": "B1",
        "destination": "dest26",
        "prompt": "Nota cultural: El Paisaje Cafetero es Patrimonio. ¿Por qué el café creó una cultura, no solo una industria?",
        "feedback": {"onCorrect": "El café formó pueblos, arquitectura, relaciones.", "onIncorrect": "El café creó formas de vivir, construir y relacionarse."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B2 Basic — Lenguas en peligro
    {
        "id": "act_dest30_cultura_lenguas_peligro",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_abstract_inner_world"],
        "objective": "lo_express_fears_doubt",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest30",
        "prompt": "Nota cultural: Colombia tiene 65 lenguas indígenas, varias en peligro. Cuando muere una lengua, ¿qué se pierde?",
        "feedback": {"onCorrect": "Se pierde una forma única de entender el mundo.", "onIncorrect": "Cada lengua codifica conocimiento que no existe en otra."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # B2 Advanced — Casanare
    {
        "id": "act_dest36_cultura_casanare",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_formal_register"],
        "objective": "lo_formal_institutional_discourse",
        "inputMode": "choice",
        "cefr": "B2",
        "destination": "dest36",
        "prompt": "Nota cultural: Casanare produce petróleo pero alberga humedales únicos. ¿Desarrollo o conservación?",
        "feedback": {"onCorrect": "Buena reflexión sobre el equilibrio.", "onIncorrect": "La riqueza del subsuelo compite con la biodiversidad."},
        "difficulty": "semi_guided",
        "scaffoldable": True
    },
    # C1 Basic — Kogi
    {
        "id": "act_dest40_cultura_kogi",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_vocab_academic_formal"],
        "objective": "lo_academic_discourse",
        "inputMode": "choice",
        "cefr": "C1",
        "destination": "dest40",
        "prompt": "Nota cultural: Los Kogi se consideran 'hermanos mayores'. Su concepto 'aluna' no tiene traducción directa. Analiza.",
        "feedback": {"onCorrect": "Aluna integra pensamiento, espíritu y responsabilidad.", "onIncorrect": "Aluna no es solo 'pensamiento' ni 'espíritu': es conexión mente-naturaleza-deber."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C1 Advanced — Teyuna
    {
        "id": "act_dest46_cultura_teyuna",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_grammar_intertextuality"],
        "objective": "lo_intertextual_reading",
        "inputMode": "choice",
        "cefr": "C1",
        "destination": "dest46",
        "prompt": "Nota cultural: Teyuna (Ciudad Perdida) fue construida 650 años antes que Machu Picchu. ¿Es una ruina o un texto vivo?",
        "feedback": {"onCorrect": "Teyuna es un texto vivo: la piedra como escritura.", "onIncorrect": "Para los Kogi, Teyuna sigue siendo un lugar sagrado activo."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C2 Basic — Realismo mágico
    {
        "id": "act_dest50_cultura_realismo_magico",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_grammar_literary_creation", "lt_vocab_literary_specialized"],
        "objective": "lo_literary_creation",
        "inputMode": "choice",
        "cefr": "C2",
        "destination": "dest50",
        "prompt": "Nota cultural: García Márquez dijo que el realismo mágico no era ficción sino la realidad. Analiza un fragmento.",
        "feedback": {"onCorrect": "Describe lo real desde una lógica cultural diferente.", "onIncorrect": "Lo 'mágico' es cómo la gente vive y cuenta su realidad."},
        "difficulty": "open",
        "scaffoldable": True
    },
    # C2 Advanced — Lenguas y futuro
    {
        "id": "act_dest57_cultura_lenguas_futuro",
        "type": "Activity",
        "gameType": "cultura",
        "practices": ["lt_grammar_sociolinguistic_variation"],
        "objective": "lo_sociolinguistic_analysis",
        "inputMode": "choice",
        "cefr": "C2",
        "destination": "dest57",
        "prompt": "Nota cultural: De 65 lenguas indígenas de Colombia, 5 tienen menos de 100 hablantes. ¿Qué causa la muerte de una lengua?",
        "feedback": {"onCorrect": "Análisis sociolingüístico completo.", "onIncorrect": "Factores: prestigio, educación, migración, políticas."},
        "difficulty": "open",
        "scaffoldable": True
    }
])

# ─── Orphan LT: lt_verb_escribir (no activity practices it) ─
new_acts.append({
    "id": "act_dest11_fill_escribir",
    "type": "Activity",
    "gameType": "fill",
    "practices": ["lt_verb_escribir_indicative_present", "lt_verb_vivir_indicative_present"],
    "objective": "lo_use_three_verb_groups",
    "inputMode": "typing",
    "cefr": "A1",
    "destination": "dest11",
    "prompt": "Completa: Yo ___ (escribir) una carta. Ella ___ (vivir) en el bosque.",
    "feedback": {"onCorrect": "Escribo, vive. Verbos -IR regulares.", "onIncorrect": "Escribir: yo escribo. Vivir: ella vive."},
    "difficulty": "semi_guided",
    "scaffoldable": True
})

# ═══════════════════════════════════════════════════════════════
# 3. INSERT ACTIVITIES INTO CORRECT POSITIONS
# ═══════════════════════════════════════════════════════════════

# Check for duplicate IDs
existing_ids = {a["id"] for a in activities if "id" in a}
new_ids = {a["id"] for a in new_acts}
duplicates = existing_ids & new_ids
if duplicates:
    print(f"WARNING: Duplicate IDs found: {duplicates}")
    # Remove duplicates from new_acts
    new_acts = [a for a in new_acts if a["id"] not in existing_ids]

# Group by destination
new_by_dest = defaultdict(list)
for act in new_acts:
    new_by_dest[act["destination"]].append(act)

# Find last index of each destination in existing activities
dest_last_idx = {}
for i, item in enumerate(activities):
    dest = item.get("destination")
    if dest:
        dest_last_idx[dest] = i

# Insert in reverse order of index to avoid shifting
sorted_dests = sorted(new_by_dest.keys(),
                       key=lambda d: dest_last_idx.get(d, len(activities)),
                       reverse=True)

for dest in sorted_dests:
    idx = dest_last_idx.get(dest, len(activities) - 1) + 1
    for act in reversed(new_by_dest[dest]):
        activities.insert(idx, act)

save_json("activities.json", activities)
act_count = sum(1 for a in activities if "id" in a)
print(f"Added {len(new_acts)} new activities. Total activities: {act_count}")
print(f"  - Orphan LO fixes: 25")
print(f"  - Thin B2 expansion: 14")
print(f"  - Voice activities: 6")
print(f"  - Flashnotes: 12")
print(f"  - Cultura: 12")
print(f"  - Orphan LT fix: 1")
