#!/usr/bin/env python3
"""
replace-grammar-with-skits.py
One-time migration: replace grammar narrative games (type: "narrative", label: "Gramática")
at games[0] in dest1-12 with literary skit games (type: "skit").

Four Colombian authors appear as recurring characters who grow linguistically
alongside the student — from single words at their first appearance to full
sentences by their third.

Safety:
  - Asserts games[0].type == "narrative" and games[0].label == "Gramática"
  - Atomic write: .tmp → rename
  - Original files recoverable from git
"""

import json
import os
import sys

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')

SKITS = {
    1: {
        "type": "skit",
        "label": "Escena",
        "title": "El poeta y la rana",
        "instruction": "Escucha y responde.",
        "grammar": ["ser (yo/tú)", "greetings"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Un río. Una selva. Un jaguar abre los ojos."},
            {"speaker": "poeta", "name": "El poeta", "animation": "glow", "text": "Hola.", "target": ["Hola"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "El poeta sonríe. Una rana salta de su libro."},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "¡Hola! Yo soy Rinrín.", "target": ["soy"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Yo soy Yaguará.", "target": ["soy"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "¿Tú eres un jaguar?", "target": ["eres"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Sí. Yo soy un jaguar.", "target": ["Sí", "soy"], "interaction": {"type": "pick", "options": ["Sí", "No", "Adiós"], "answer": "Sí"}},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Y tú, viajero... ¿quién eres tú?", "target": ["eres"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "fade-out", "text": "¡Adiós, Yaguará!", "target": ["Adiós"]},
            {"speaker": "poeta", "name": "El poeta", "animation": "fade-out", "text": "Adiós.", "target": ["Adiós"]}
        ]
    },
    2: {
        "type": "skit",
        "label": "Escena",
        "title": "Nombrar las cosas",
        "instruction": "Escucha los nombres.",
        "grammar": ["articles (el/la/los/las)", "gender", "singular/plural"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará camina por la selva. Rinrín está con ella."},
            {"speaker": "poeta", "name": "El poeta", "animation": "glow", "text": "El río. La selva. El árbol.", "target": ["El", "La"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "¡Sí! El río, la selva, el árbol. Todo tiene nombre.", "target": ["El", "la", "el"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "El jaguar. La flor. El sol. La luna.", "target": ["El", "La"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "Uno: el mono. Muchos: los monos.", "target": ["el", "los"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "Una: la rana. Muchas: las ranas.", "target": ["la", "las"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "El árbol. Sí, el árbol.", "target": ["El árbol"], "interaction": {"type": "pick", "options": ["El árbol", "La árbol", "Los árbol"], "answer": "El árbol"}},
            {"speaker": "poeta", "name": "El poeta", "animation": "fade-out", "text": "El mundo tiene muchos nombres."}
        ]
    },
    3: {
        "type": "skit",
        "label": "Escena",
        "title": "Los colores del mundo",
        "instruction": "Escucha y aprende a describir.",
        "grammar": ["ser vs estar", "hay", "adjective agreement", "colors"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará y Rinrín llegan a una casa pequeña junto al río."},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "Esta es la casa de Doña Ratona.", "target": ["es"]},
            {"speaker": "ratona", "name": "Doña Ratona", "animation": "enter-left", "text": "¡Hola! Mi casa es bonita, ¿no?", "target": ["es"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Sí. La flor es roja. El árbol es verde.", "target": ["es"]},
            {"speaker": "ratona", "name": "Doña Ratona", "animation": "fade-in", "text": "Hay flores. Hay un árbol grande.", "target": ["Hay"]},
            {"speaker": "rinrin", "name": "Rinrín", "animation": "bounce", "text": "¿Dónde está Yaguará? Yaguará está en la selva.", "target": ["está"]},
            {"speaker": "poeta", "name": "El poeta", "animation": "glow", "text": "La selva es grande. Es verde y bonita.", "target": ["es"]},
            {"speaker": "ratona", "name": "Doña Ratona", "animation": "fade-in", "text": "Rinrín está en el río.", "target": ["está"], "interaction": {"type": "pick", "options": ["es en el río", "está en el río", "hay en el río"], "answer": "está en el río"}},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "El poeta sonríe. Los colores brillan."},
            {"speaker": "poeta", "name": "El poeta", "animation": "fade-out", "text": "El cielo es azul. El sol es amarillo. El mundo es bonito."}
        ]
    },
    4: {
        "type": "skit",
        "label": "Escena",
        "title": "Contar el mundo",
        "instruction": "Escucha y cuenta.",
        "grammar": ["numbers 0-31", "tener + age", "days of week", "time of day"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Es la mañana. Yaguará está junto al río. Hay alguien más."},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "¡Hola! Yo soy Río.", "target": ["soy"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Hola, Río. Yo tengo tres años. ¿Cuántos años tienes tú?", "target": ["tengo", "tienes"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "Yo tengo un año.", "target": ["tengo"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Un viajero camina por la selva. Tiene un cuaderno."},
            {"speaker": "viajero", "name": "El viajero", "animation": "glow", "text": "Uno... dos... tres.", "target": ["Uno", "dos", "tres"]},
            {"speaker": "rio", "name": "Río", "animation": "bounce", "text": "¡Cuatro ranas, cinco pájaros, seis flores!", "target": ["Cuatro", "cinco", "seis"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Hoy es lunes. Por la mañana, yo cuento.", "target": ["lunes", "mañana"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Yaguará tiene tres años.", "target": ["tres"], "interaction": {"type": "pick", "options": ["Dos", "Tres", "Cinco"], "answer": "Tres"}},
            {"speaker": "viajero", "name": "El viajero", "animation": "fade-out", "text": "Tres. Sí. Tres años."}
        ]
    },
    5: {
        "type": "skit",
        "label": "Escena",
        "title": "Lo que nos gusta",
        "instruction": "Escucha y responde.",
        "grammar": ["me gusta / no me gusta", "negation", "possessives (mi/tu/su)"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará y Río están junto al agua."},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "Me gusta el agua. Me gusta nadar.", "target": ["Me gusta"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Me gusta la selva. Me gustan los árboles.", "target": ["Me gusta", "Me gustan"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "¿Te gusta la lluvia?"},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "No. No me gusta la lluvia.", "target": ["No me gusta"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "El viajero escribe en su cuaderno."},
            {"speaker": "viajero", "name": "El viajero", "animation": "glow", "text": "Me gusta la selva. Mi selva.", "target": ["Me gusta", "Mi"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Mi río. Tu selva. Su casa.", "target": ["Mi", "Tu", "Su"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "No, no le gusta la lluvia.", "target": ["No", "le gusta"], "interaction": {"type": "pick", "options": ["Sí, le gusta", "No, no le gusta"], "answer": "No, no le gusta"}},
            {"speaker": "viajero", "name": "El viajero", "animation": "fade-out", "text": "No me gusta el silencio."}
        ]
    },
    6: {
        "type": "skit",
        "label": "Escena",
        "title": "Un día en la selva",
        "instruction": "Escucha lo que hacen.",
        "grammar": ["-AR conjugation", "prepositions"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Un nuevo día. Yaguará se despierta."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Por la mañana, yo camino por la selva.", "target": ["camino", "por"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "Yo nado en el río por la mañana.", "target": ["nado", "en"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Por la tarde, yo descanso en el árbol.", "target": ["descanso", "en"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "¿Tú hablas con el río?", "target": ["hablas", "con"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Sí. Yo hablo con el río. Yo escucho la selva.", "target": ["hablo", "escucho"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "El viajero camina entre los árboles. Escribe en su cuaderno."},
            {"speaker": "viajero", "name": "El viajero", "animation": "glow", "text": "Yo camino. Yo escucho. La selva habla.", "target": ["camino", "escucho", "habla"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Algo se mueve en la selva. Una voz lejana..."},
            {"speaker": "mama_jaguar", "name": "???", "animation": "glow", "text": "Camina, hija.", "target": ["Camina"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Yaguará camina por la selva por la mañana.", "target": ["camina"], "interaction": {"type": "pick", "options": ["Camina", "Nada", "Descansa"], "answer": "Camina"}}
        ]
    },
    7: {
        "type": "skit",
        "label": "Escena",
        "title": "Comer juntos",
        "instruction": "Escucha y aprende.",
        "grammar": ["food vocabulary", "-ER verbs (comer/beber)", "tener hambre/sed/frío/calor"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Es mediodía. Yaguará tiene hambre."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Tengo hambre. Tengo sed.", "target": ["Tengo hambre", "Tengo sed"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "enter-left", "text": "Come, hija. El río da."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "¿Qué es esto?"},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "fade-in", "text": "Pescado. Yo como pescado. Tú comes pescado.", "target": ["como", "comes"]},
            {"speaker": "rio", "name": "Río", "animation": "bounce", "text": "Yo como fruta. Bebo agua del río.", "target": ["como", "Bebo"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "fade-in", "text": "Comemos juntos. La comida es para todos.", "target": ["Comemos"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Un hombre viejo aparece junto al fuego."},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "glow", "text": "Come.", "target": ["Come"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Yaguará tiene hambre.", "target": ["hambre"], "interaction": {"type": "pick", "options": ["hambre", "frío", "sed"], "answer": "hambre"}},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "fade-out", "text": "Sí. Hambre."}
        ]
    },
    8: {
        "type": "skit",
        "label": "Escena",
        "title": "La familia",
        "instruction": "Escucha y aprende.",
        "grammar": ["family members", "este/esta", "¿quién?"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Junto al río, Yaguará mira a su familia."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Esta es mi mamá.", "target": ["Esta", "mi"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "enter-left", "text": "Y este es Río. Es mi hijo.", "target": ["este", "mi"]},
            {"speaker": "rio", "name": "Río", "animation": "bounce", "text": "¡Hola! Yo soy el hermano de Yaguará.", "target": ["hermano"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "¿Quién es ella?", "target": ["Quién"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "fade-in", "text": "Ella es tu abuela. Vive en el árbol grande."},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Don Tomás está junto al fuego. Mira a la familia."},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "glow", "text": "Mi familia es grande. Este es mi río.", "target": ["Mi familia", "Este"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "fade-in", "text": "Los nombres no se pierden. Se olvidan."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Río es mi hermano.", "target": ["mi hermano"], "interaction": {"type": "pick", "options": ["mi hermano", "mi mamá", "mi abuela"], "answer": "mi hermano"}}
        ]
    },
    9: {
        "type": "skit",
        "label": "Escena",
        "title": "Mi casa",
        "instruction": "Escucha y responde.",
        "grammar": ["rooms", "estar + states", "un/una"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Una casa junto al río. Yaguará entra."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Hay una cocina. Hay un dormitorio.", "target": ["una", "un"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "enter-left", "text": "La cocina es pequeña. El dormitorio es grande."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Estoy bien aquí. Estoy contenta.", "target": ["Estoy bien", "Estoy contenta"]},
            {"speaker": "mama_jaguar", "name": "Mamá Jaguar", "animation": "fade-in", "text": "¿Estás cansada?", "target": ["Estás"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "No. No estoy cansada. Estoy feliz.", "target": ["estoy"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Don Tomás escribe junto a la ventana."},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "glow", "text": "Mi casa está junto al río. Estoy bien aquí.", "target": ["está", "Estoy"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Estoy feliz. Estoy muy feliz.", "target": ["Estoy feliz"], "interaction": {"type": "pick", "options": ["Soy feliz", "Estoy feliz", "Tengo feliz"], "answer": "Estoy feliz"}},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "fade-out", "text": "Estoy bien. Mi casa. Mi río."}
        ]
    },
    10: {
        "type": "skit",
        "label": "Escena",
        "title": "El tiempo pasa",
        "instruction": "Escucha y cuenta.",
        "grammar": ["months", "clock time", "numbers 32-100", "¿cuándo?"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará mira el cielo. El sol está alto."},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "¿Qué hora es?"},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "Son las tres de la tarde.", "target": ["Son las tres"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "¿Cuándo es tu cumpleaños?", "target": ["Cuándo"]},
            {"speaker": "rio", "name": "Río", "animation": "enter-right", "text": "En mayo. ¿Y tú?", "target": ["mayo"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "En enero. El primer mes.", "target": ["enero"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Un hombre con lentes aparece junto al río. Tiene un libro."},
            {"speaker": "maestro", "name": "El maestro", "animation": "glow", "text": "Los números.", "target": ["números"]},
            {"speaker": "rio", "name": "Río", "animation": "bounce", "text": "¡Cuarenta peces! ¡Cincuenta flores! ¡Cien estrellas!", "target": ["Cuarenta", "Cincuenta", "Cien"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Son las tres de la tarde.", "target": ["Son las tres"], "interaction": {"type": "pick", "options": ["Es las tres", "Son las tres", "Está las tres"], "answer": "Son las tres"}},
            {"speaker": "maestro", "name": "El maestro", "animation": "fade-out", "text": "Tres. Sí. Son las tres."}
        ]
    },
    11: {
        "type": "skit",
        "label": "Escena",
        "title": "Los verbos del mundo",
        "instruction": "Escucha y aprende.",
        "grammar": ["-ER/-IR conjugation", "ir (irregular)", "plural possessives", "¿por qué?/porque"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará llega al árbol más grande de la selva. La ceiba."},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Tú caminas. Tú comes. Tú vives. Estos son los verbos.", "target": ["caminas", "comes", "vives"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Yo vivo en la selva. Yo como fruta.", "target": ["vivo", "como"]},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Nosotros vivimos juntos. Nuestro río. Nuestra selva.", "target": ["vivimos", "Nuestro", "Nuestra"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "¿Adónde voy?", "target": ["voy"]},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Tú vas al río. Nosotros vamos a la selva.", "target": ["vas", "vamos"]},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "El maestro está junto a la ceiba. Escribe en su cuaderno."},
            {"speaker": "maestro", "name": "El maestro", "animation": "glow", "text": "Yo escribo. Tú lees. Nosotros vivimos con palabras.", "target": ["escribo", "vivimos"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "¿Por qué escribes?", "target": ["Por qué"]},
            {"speaker": "maestro", "name": "El maestro", "animation": "glow", "text": "Porque me gusta. Escribo porque me gusta.", "target": ["Porque"], "interaction": {"type": "pick", "options": ["Porque me gusta", "Sí", "No sé"], "answer": "Porque me gusta"}},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Los verbos mueven el mundo."}
        ]
    },
    12: {
        "type": "skit",
        "label": "Escena",
        "title": "¿Quién soy yo?",
        "instruction": "Escucha. Todos hablan.",
        "grammar": ["nationality adjectives", "expanded colors", "A1 integration"],
        "beats": [
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Yaguará está frente a Abuela Ceiba. Ha llegado el momento."},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "¿Quién eres tú?", "target": ["eres"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "enter-left", "text": "Yo soy Yaguará. Soy colombiana. Vivo en la selva.", "target": ["soy", "colombiana"]},
            {"speaker": "yaguara", "name": "Yaguará", "animation": "fade-in", "text": "Tengo tres años. Me gusta el río. Mi familia está aquí."},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Los cuatro escritores aparecen entre los árboles."},
            {"speaker": "poeta", "name": "El poeta", "animation": "glow", "text": "Hola, Yaguará. El mundo tiene tus nombres."},
            {"speaker": "viajero", "name": "El viajero", "animation": "glow", "text": "La selva es tu casa. Camina."},
            {"speaker": "don_tomas", "name": "Don Tomás", "animation": "glow", "text": "Tu familia es grande. Come con ellos."},
            {"speaker": "maestro", "name": "El maestro", "animation": "glow", "text": "Tú escribes tu historia. Porque las palabras viven."},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Ahora tienes nombre. Ahora el mundo te ve."},
            {"speaker": "abuela_ceiba", "name": "Abuela Ceiba", "animation": "glow", "text": "Escucha. La primera palabra.", "target": ["Escucha"], "interaction": {"type": "pick", "options": ["Habla", "Escucha", "Camina"], "answer": "Escucha"}},
            {"speaker": "narrator", "name": "Narrador", "animation": "fade-in", "text": "Escucha. La primera palabra. La semilla."}
        ]
    }
}


def main():
    replaced = 0
    errors = []

    for dest_num in range(1, 13):
        filename = f'dest{dest_num}.json'
        filepath = os.path.join(CONTENT_DIR, filename)

        if not os.path.exists(filepath):
            errors.append(f'{filename}: file not found')
            continue

        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)

        games = data.get('games', [])
        if not games:
            errors.append(f'{filename}: no games array')
            continue

        old_game = games[0]
        if old_game.get('type') != 'narrative':
            errors.append(f'{filename}: games[0].type is "{old_game.get("type")}", expected "narrative"')
            continue
        if old_game.get('label') != 'Gramática':
            errors.append(f'{filename}: games[0].label is "{old_game.get("label")}", expected "Gramática"')
            continue

        if dest_num not in SKITS:
            errors.append(f'{filename}: no skit defined for dest{dest_num}')
            continue

        # Replace games[0]
        games[0] = SKITS[dest_num]
        data['games'] = games

        # Atomic write
        tmp_path = filepath + '.tmp'
        with open(tmp_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
            f.write('\n')
        os.rename(tmp_path, filepath)

        old_title = old_game.get('title', '(no title)')
        new_title = SKITS[dest_num]['title']
        print(f'  dest{dest_num}: "{old_title}" → "{new_title}" (narrative → skit)')
        replaced += 1

    print(f'\n  {replaced}/12 replacements successful')
    if errors:
        print(f'  {len(errors)} errors:')
        for e in errors:
            print(f'    - {e}')
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
