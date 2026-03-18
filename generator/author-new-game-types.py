#!/usr/bin/env python3
"""
author-new-game-types.py — Content authoring for 10 new game types.
Injects games into dest13–dest58 (46 destinations).
Uses atomic swap when destinations are at the 38-game ceiling.
Idempotent: skips types already present.

Usage:
    python3 generator/author-new-game-types.py --dry-run
    python3 generator/author-new-game-types.py
"""

import json, os, sys, random, copy

# ── CONSTANTS ──────────────────────────────────────────────────────

CONTENT_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'content')
MAX_GAMES_PER_DEST = 38

DEST_CEFR = {}
for d in range(1, 13):   DEST_CEFR[d] = 'A1'
for d in range(13, 19):  DEST_CEFR[d] = 'A2'
for d in range(19, 29):  DEST_CEFR[d] = 'B1'
for d in range(29, 39):  DEST_CEFR[d] = 'B2'
for d in range(39, 49):  DEST_CEFR[d] = 'C1'
for d in range(49, 59):  DEST_CEFR[d] = 'C2'

TYPE_LEVELS = {
    'susurro':     ['A2', 'B1', 'B2', 'C1', 'C2'],
    'sombra':      ['A2', 'B1', 'B2'],
    'cartografo':  ['A2', 'B1', 'B2'],
    'eco_lejano':  ['B1', 'B2', 'C1', 'C2'],
    'tertulia':    ['B1', 'B2', 'C1', 'C2'],
    'pregonero':   ['B1', 'B2', 'C1', 'C2'],
    'oraculo':     ['B1', 'B2', 'C1', 'C2'],
    'raiz':        ['B2', 'C1', 'C2'],
    'codice':      ['B2', 'C1', 'C2'],
    'tejido':      ['B2', 'C1', 'C2'],
}

SWAP_PRIORITY = ['fill', 'conjugation', 'listening', 'category', 'builder',
                 'pair', 'conversation', 'dictation', 'translation', 'story']

# ── HELPERS ────────────────────────────────────────────────────────

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


def has_room(data):
    return len(data.get('games', [])) < MAX_GAMES_PER_DEST


def already_has_type(data, game_type):
    return any(g.get('type') == game_type for g in data.get('games', []))


def get_characters(meta):
    char_map = {
        'char_yaguara': 'Yaguará', 'char_candelaria': 'Candelaria',
        'char_don_prospero': 'Don Próspero', 'char_dona_asuncion': 'Doña Asunción',
        'char_abuela_ceiba': 'Abuela Ceiba', 'char_mama_jaguar': 'Mamá Jaguar',
        'char_rio': 'Río', 'myth_sombra_yaguara': 'La Sombra',
    }
    return [char_map.get(c, c.replace('char_', '').replace('_', ' ').title())
            for c in meta.get('characters', [])]


def find_swap_victim(data):
    """Find a duplicate game type to swap out. Returns index or None."""
    type_counts = {}
    for g in data.get('games', []):
        t = g.get('type', '')
        type_counts[t] = type_counts.get(t, 0) + 1
    candidates = {t: c for t, c in type_counts.items() if c >= 2}
    if not candidates:
        return None
    best_type, best_count, best_pri = None, 0, len(SWAP_PRIORITY)
    for t, c in candidates.items():
        pri = SWAP_PRIORITY.index(t) if t in SWAP_PRIORITY else len(SWAP_PRIORITY)
        if c > best_count or (c == best_count and pri < best_pri):
            best_type, best_count, best_pri = t, c, pri
    if best_type is None:
        return None
    for i in range(len(data['games']) - 1, -1, -1):
        if data['games'][i].get('type') == best_type:
            return i
    return None


def pick(pool, cefr, dest_n):
    if cefr not in pool:
        return None
    items = pool[cefr]
    return copy.deepcopy(items[(dest_n - 1) % len(items)])


# ── DATA POOLS ─────────────────────────────────────────────────────

# -- SUSURRO: audio sentences for TTS whisper recall --
SUSURRO_AUDIO = {
    'A2': [
        "El río está cerca. Necesito agua.",
        "Candelaria dibuja en su cuaderno azul.",
        "Hace calor hoy en la selva.",
        "Los pájaros cantan por la mañana.",
        "Quiero descansar debajo del árbol.",
        "Mi casa está lejos de aquí.",
    ],
    'B1': [
        "Aunque llueve mucho, Yaguará sigue caminando por el sendero.",
        "Si pudiéramos llegar al río antes del atardecer, descansaríamos.",
        "Candelaria dice que los árboles guardan secretos antiguos.",
        "Don Próspero habla de progreso, pero el bosque necesita protección.",
        "Cuando el viento sopla fuerte, las hojas cuentan historias.",
        "Es importante escuchar antes de hablar.",
    ],
    'B2': [
        "A pesar de que Don Próspero prometió conservar el bosque, sus acciones dicen otra cosa.",
        "Si los ríos pudieran hablar, contarían historias que nadie ha escrito todavía.",
        "Las comunidades ribereñas han protegido esta tierra durante generaciones enteras.",
        "Hubiera preferido que Candelaria no descubriera la verdad tan pronto.",
        "La biodiversidad de la selva colombiana supera cualquier expectativa.",
        "No basta con conocer las palabras; hay que sentir lo que significan.",
    ],
    'C1': [
        "La noción de progreso que defiende Don Próspero presupone la subordinación de lo natural a lo económico.",
        "Cabría preguntarse si la preservación del equilibrio ecológico es compatible con el desarrollo actual.",
        "Cuanto más se adentraba en la espesura, más evidente se hacía la fragilidad de sus certezas.",
        "El verdadero desafío no radica en conquistar el territorio, sino en aprender a habitarlo.",
        "Las tradiciones orales de los pueblos originarios constituyen un patrimonio epistemológico invaluable.",
        "Habría sido ingenuo suponer que las consecuencias de la deforestación no nos alcanzarían.",
    ],
    'C2': [
        "Subyace en el discurso desarrollista una tensión irresoluble entre explotación y pervivencia del ecosistema.",
        "La heteroglosia del bosque desafía la lógica monológica que pretende reducir lo vivo a recurso.",
        "Que la memoria colectiva persista en los cantos nocturnos no es metáfora: es epistemología.",
        "La aporía del viajero reside en que solo puede conocer el camino recorriéndolo, y cada paso lo transforma.",
        "En la confluencia de aguas, lenguas y tiempos se manifiesta lo que los wayúu llaman el tejido del mundo.",
        "Ni la taxonomía occidental ni la cosmovisión indígena agotan, por sí solas, la complejidad de lo vivo.",
    ],
}

# -- SOMBRA: audio sentences for speed dictation --
SOMBRA_AUDIO = {
    'A2': [
        "El gato duerme en la casa.",
        "Me gusta bailar cumbia.",
        "Los niños juegan en el parque.",
        "Candelaria tiene un cuaderno azul.",
        "El mercado está cerca del río.",
        "Hoy es un día bonito y tranquilo.",
    ],
    'B1': [
        "Si tuviera más tiempo, visitaría todos los pueblos del valle.",
        "Candelaria sabe que el camino es difícil, pero sigue adelante.",
        "Los árboles más altos del bosque pueden tener cien años.",
        "Cuando llueve en la selva, los sonidos cambian completamente.",
        "El mercado del pueblo abre los sábados muy temprano.",
        "Yaguará recuerda las palabras de la abuela ceiba.",
    ],
    'B2': [
        "Aunque el progreso traiga beneficios, es necesario preguntarse a qué costo se construyen los caminos.",
        "Si los ríos pudieran hablar, contarían historias que ningún libro ha registrado.",
        "La biodiversidad del Amazonas colombiano supera las expectativas de cualquier investigador.",
        "Habría sido imposible recorrer este sendero sin la guía de quienes conocen el territorio.",
        "Las comunidades ribereñas han desarrollado técnicas de pesca sostenible durante generaciones.",
        "Don Próspero no entendía que el verdadero valor del bosque no se mide en hectáreas.",
    ],
}

# -- CARTOGRAFO: spatial listening with questions --
CARTOGRAFO_DATA = {
    'A2': [
        {'audio': 'El río está a la izquierda. La casa está a la derecha. El árbol grande está en el centro.',
         'questions': [{'q': '¿Dónde está el río?', 'options': ['A la izquierda', 'A la derecha', 'En el centro'], 'answer': 'A la izquierda'},
                       {'q': '¿Dónde está la casa?', 'options': ['A la izquierda', 'A la derecha', 'En el centro'], 'answer': 'A la derecha'}],
         'gridLabels': ['el río', 'la casa', 'el árbol']},
        {'audio': 'La escuela está cerca del mercado. La iglesia está lejos, al norte. El parque está entre la escuela y la iglesia.',
         'questions': [{'q': '¿Qué está cerca del mercado?', 'options': ['La escuela', 'La iglesia', 'El parque'], 'answer': 'La escuela'},
                       {'q': '¿Dónde está la iglesia?', 'options': ['Cerca del mercado', 'Lejos, al norte', 'En el centro'], 'answer': 'Lejos, al norte'}],
         'gridLabels': ['la escuela', 'el mercado', 'la iglesia', 'el parque']},
        {'audio': 'Camina derecho. Gira a la derecha en la fuente. La tienda está al final de la calle.',
         'questions': [{'q': '¿Dónde giras?', 'options': ['En la fuente', 'En la tienda', 'Al principio'], 'answer': 'En la fuente'},
                       {'q': '¿Dónde está la tienda?', 'options': ['Al principio', 'En el medio', 'Al final de la calle'], 'answer': 'Al final de la calle'}],
         'gridLabels': ['la fuente', 'la tienda', 'la calle']},
        {'audio': 'La cueva está detrás del río. Los árboles están delante. Las piedras están al lado de la cueva.',
         'questions': [{'q': '¿Dónde está la cueva?', 'options': ['Delante del río', 'Detrás del río', 'Al lado del río'], 'answer': 'Detrás del río'},
                       {'q': '¿Dónde están las piedras?', 'options': ['Lejos', 'Al lado de la cueva', 'Delante de los árboles'], 'answer': 'Al lado de la cueva'}],
         'gridLabels': ['la cueva', 'el río', 'los árboles', 'las piedras']},
        {'audio': 'Yaguará está en el norte. El lago está al sur. Entre los dos hay un camino de piedras.',
         'questions': [{'q': '¿Dónde está Yaguará?', 'options': ['Al norte', 'Al sur', 'En el centro'], 'answer': 'Al norte'},
                       {'q': '¿Qué hay entre Yaguará y el lago?', 'options': ['Un río', 'Un camino de piedras', 'Un bosque'], 'answer': 'Un camino de piedras'}],
         'gridLabels': ['Yaguará', 'el lago', 'el camino']},
        {'audio': 'La ceiba está en la plaza. A su izquierda hay una fuente. A su derecha hay un banco. Detrás hay una montaña.',
         'questions': [{'q': '¿Qué hay a la izquierda de la ceiba?', 'options': ['Un banco', 'Una fuente', 'Una montaña'], 'answer': 'Una fuente'},
                       {'q': '¿Qué hay detrás de la ceiba?', 'options': ['Una fuente', 'Un banco', 'Una montaña'], 'answer': 'Una montaña'}],
         'gridLabels': ['la ceiba', 'la fuente', 'el banco', 'la montaña']},
    ],
    'B1': [
        {'audio': 'El pueblo está al sur del río. Si cruzas el puente, llegas a la plaza. La farmacia está a la derecha de la plaza y la biblioteca a la izquierda.',
         'questions': [{'q': '¿Cómo llegas a la plaza?', 'options': ['Cruzando el puente', 'Caminando al norte', 'Por la farmacia'], 'answer': 'Cruzando el puente'},
                       {'q': '¿Dónde está la biblioteca?', 'options': ['A la derecha de la plaza', 'A la izquierda de la plaza', 'Al sur del río'], 'answer': 'A la izquierda de la plaza'}],
         'gridLabels': ['el pueblo', 'el río', 'el puente', 'la plaza', 'la farmacia', 'la biblioteca']},
        {'audio': 'Desde la montaña puedes ver tres valles. El valle del norte tiene un río grande. El valle del este tiene muchos árboles. El valle del oeste está seco.',
         'questions': [{'q': '¿Qué tiene el valle del norte?', 'options': ['Muchos árboles', 'Un río grande', 'Está seco'], 'answer': 'Un río grande'},
                       {'q': '¿Cuál valle está seco?', 'options': ['El del norte', 'El del este', 'El del oeste'], 'answer': 'El del oeste'}],
         'gridLabels': ['la montaña', 'valle norte', 'valle este', 'valle oeste']},
        {'audio': 'Candelaria camina hacia el mercado. Primero pasa por la panadería que está a la derecha. Luego gira a la izquierda en la esquina. El mercado está al fondo de esa calle.',
         'questions': [{'q': '¿Dónde está la panadería?', 'options': ['A la izquierda', 'A la derecha', 'Al fondo'], 'answer': 'A la derecha'},
                       {'q': '¿Dónde gira Candelaria?', 'options': ['A la derecha', 'A la izquierda en la esquina', 'Derecho'], 'answer': 'A la izquierda en la esquina'}],
         'gridLabels': ['la panadería', 'la esquina', 'el mercado']},
        {'audio': 'La selva rodea el campamento. Al norte está el río. Al sur hay una cueva. El sendero va del campamento hacia el este, donde hay un claro.',
         'questions': [{'q': '¿Qué hay al sur del campamento?', 'options': ['El río', 'Una cueva', 'Un claro'], 'answer': 'Una cueva'},
                       {'q': '¿Hacia dónde va el sendero?', 'options': ['Al norte', 'Al sur', 'Al este'], 'answer': 'Al este'}],
         'gridLabels': ['el campamento', 'el río', 'la cueva', 'el sendero', 'el claro']},
        {'audio': 'El mapa muestra dos caminos. El camino corto pasa por el puente de piedra y llega al pueblo en una hora. El camino largo rodea la montaña por el sur y pasa por una cascada.',
         'questions': [{'q': '¿Por dónde pasa el camino corto?', 'options': ['Por la cascada', 'Por el puente de piedra', 'Por la montaña'], 'answer': 'Por el puente de piedra'},
                       {'q': '¿Qué hay en el camino largo?', 'options': ['Un puente', 'Un pueblo', 'Una cascada'], 'answer': 'Una cascada'}],
         'gridLabels': ['camino corto', 'camino largo', 'el puente', 'la cascada', 'el pueblo']},
        {'audio': 'Don Próspero señala el mapa. Su carretera nueva empieza en la costa, cruza la sierra por el centro y termina en los llanos orientales.',
         'questions': [{'q': '¿Dónde empieza la carretera?', 'options': ['En la sierra', 'En la costa', 'En los llanos'], 'answer': 'En la costa'},
                       {'q': '¿Por dónde cruza la sierra?', 'options': ['Por el norte', 'Por el centro', 'Por el sur'], 'answer': 'Por el centro'}],
         'gridLabels': ['la costa', 'la sierra', 'los llanos', 'la carretera']},
    ],
    'B2': [
        {'audio': 'La cuenca hidrográfica se extiende desde los páramos al norte hasta las llanuras aluviales al sur. Los afluentes principales nacen en la cordillera oriental y desembocan en el río Magdalena.',
         'questions': [{'q': '¿Dónde nacen los afluentes?', 'options': ['En las llanuras', 'En la cordillera oriental', 'En el río Magdalena'], 'answer': 'En la cordillera oriental'},
                       {'q': '¿Dónde desembocan?', 'options': ['En los páramos', 'En las llanuras', 'En el río Magdalena'], 'answer': 'En el río Magdalena'}],
         'gridLabels': ['los páramos', 'la cordillera', 'las llanuras', 'el río Magdalena']},
        {'audio': 'El resguardo indígena ocupa una franja que va desde la ribera occidental del río hasta las estribaciones de la sierra. La zona sagrada está en el centro, rodeada por parcelas comunitarias.',
         'questions': [{'q': '¿Dónde está la zona sagrada?', 'options': ['En la ribera', 'En el centro', 'En la sierra'], 'answer': 'En el centro'},
                       {'q': '¿Qué rodea la zona sagrada?', 'options': ['El río', 'Parcelas comunitarias', 'La sierra'], 'answer': 'Parcelas comunitarias'}],
         'gridLabels': ['la ribera', 'la zona sagrada', 'las parcelas', 'la sierra']},
        {'audio': 'La ruta del jaguar atraviesa tres ecosistemas: primero la selva húmeda al sureste, luego el bosque de niebla en las montañas centrales, y finalmente el páramo al noroeste.',
         'questions': [{'q': '¿Dónde está la selva húmeda?', 'options': ['Al noroeste', 'Al sureste', 'En el centro'], 'answer': 'Al sureste'},
                       {'q': '¿Qué ecosistema está en las montañas centrales?', 'options': ['La selva húmeda', 'El bosque de niebla', 'El páramo'], 'answer': 'El bosque de niebla'}],
         'gridLabels': ['selva húmeda', 'bosque de niebla', 'páramo']},
        {'audio': 'El proyecto de Don Próspero divide el territorio en cuatro zonas: extracción al este, agricultura al sur, vivienda al oeste y una pequeña reserva natural al norte, apenas un diez por ciento del total.',
         'questions': [{'q': '¿Qué zona está al este?', 'options': ['Vivienda', 'Extracción', 'Reserva natural'], 'answer': 'Extracción'},
                       {'q': '¿Cuánto ocupa la reserva natural?', 'options': ['La mitad', 'Un diez por ciento', 'Un cuarto'], 'answer': 'Un diez por ciento'}],
         'gridLabels': ['extracción', 'agricultura', 'vivienda', 'reserva natural']},
        {'audio': 'Según el mapa ancestral, el río sagrado nace en la laguna del este y fluye hacia el oeste pasando por el cementerio de las guacamayas. Donde el río se bifurca hay un petroglifo antiguo.',
         'questions': [{'q': '¿Dónde nace el río sagrado?', 'options': ['En el petroglifo', 'En la laguna del este', 'En el cementerio'], 'answer': 'En la laguna del este'},
                       {'q': '¿Qué hay donde el río se bifurca?', 'options': ['Una laguna', 'Un cementerio', 'Un petroglifo antiguo'], 'answer': 'Un petroglifo antiguo'}],
         'gridLabels': ['la laguna', 'el río', 'el cementerio', 'el petroglifo']},
        {'audio': 'La deforestación avanza desde el sureste hacia el noroeste. La zona intacta se redujo al triángulo entre la sierra, el río y el resguardo. Fuera de ese triángulo, la cobertura boscosa es inferior al treinta por ciento.',
         'questions': [{'q': '¿Desde dónde avanza la deforestación?', 'options': ['Desde el noroeste', 'Desde el sureste', 'Desde el centro'], 'answer': 'Desde el sureste'},
                       {'q': '¿Dónde está la zona intacta?', 'options': ['Al sureste', 'En todo el territorio', 'Entre la sierra, el río y el resguardo'], 'answer': 'Entre la sierra, el río y el resguardo'}],
         'gridLabels': ['la sierra', 'el río', 'el resguardo', 'zona deforestada']},
    ],
}

# -- ECO_LEJANO: passages with comprehension questions (fading text) --
ECO_LEJANO_DATA = {
    'B1': [
        {'passage': 'El mercado de Leticia abre todos los días al amanecer. Los pescadores traen bocachico y pirarucú del río Amazonas. Las mujeres venden frutas: copoazú, arazá y camu camu. El mercado cierra cuando el sol está alto.',
         'questions': [{'question': '¿Qué traen los pescadores?', 'options': ['Frutas', 'Bocachico y pirarucú', 'Verduras'], 'answer': 'Bocachico y pirarucú'},
                       {'question': '¿Cuándo cierra el mercado?', 'options': ['Al amanecer', 'Cuando el sol está alto', 'Por la noche'], 'answer': 'Cuando el sol está alto'}]},
        {'passage': 'Candelaria recuerda la primera vez que vio a Yaguará. Estaba lloviendo. El jaguar apareció entre los helechos como una sombra dorada. No tenía miedo. Candelaria tampoco.',
         'questions': [{'question': '¿Cómo estaba el clima?', 'options': ['Hacía sol', 'Estaba lloviendo', 'Hacía frío'], 'answer': 'Estaba lloviendo'},
                       {'question': '¿Quién no tenía miedo?', 'options': ['Solo Candelaria', 'Solo Yaguará', 'Ninguna de las dos'], 'answer': 'Ninguna de las dos'}]},
        {'passage': 'En Colombia hay tres cordilleras. La cordillera occidental está cerca del Pacífico. La cordillera central tiene volcanes activos. La cordillera oriental llega hasta Venezuela.',
         'questions': [{'question': '¿Cuál cordillera tiene volcanes?', 'options': ['La occidental', 'La central', 'La oriental'], 'answer': 'La central'},
                       {'question': '¿Cuántas cordilleras hay?', 'options': ['Dos', 'Tres', 'Cuatro'], 'answer': 'Tres'}]},
        {'passage': 'Don Próspero llegó al pueblo hace cinco años. Prometió trabajo, escuelas y un hospital. Construyó la carretera, pero el hospital nunca llegó. La gente todavía espera.',
         'questions': [{'question': '¿Qué construyó Don Próspero?', 'options': ['Un hospital', 'Una escuela', 'La carretera'], 'answer': 'La carretera'},
                       {'question': '¿Qué prometió y no cumplió?', 'options': ['La carretera', 'El hospital', 'El trabajo'], 'answer': 'El hospital'}]},
        {'passage': 'La ceiba puede vivir quinientos años. Sus raíces son tan grandes como una casa. Los wayúu dicen que conecta el mundo de abajo con el mundo de arriba. Por eso nadie corta una ceiba.',
         'questions': [{'question': '¿Cuántos años puede vivir una ceiba?', 'options': ['Cien', 'Trescientos', 'Quinientos'], 'answer': 'Quinientos'},
                       {'question': '¿Qué conecta la ceiba según los wayúu?', 'options': ['Dos ríos', 'El mundo de abajo y el de arriba', 'Dos pueblos'], 'answer': 'El mundo de abajo y el de arriba'}]},
        {'passage': 'El festival de la cumbia se celebra en El Banco, Magdalena. Los músicos tocan gaita y tambor. Las mujeres bailan con velas encendidas. La fiesta dura tres noches.',
         'questions': [{'question': '¿Dónde se celebra el festival?', 'options': ['En Cartagena', 'En El Banco, Magdalena', 'En Bogotá'], 'answer': 'En El Banco, Magdalena'},
                       {'question': '¿Qué llevan las mujeres al bailar?', 'options': ['Flores', 'Velas encendidas', 'Sombreros'], 'answer': 'Velas encendidas'}]},
    ],
    'B2': [
        {'passage': 'La minería ilegal ha contaminado más de treinta ríos en el Chocó durante la última década. El mercurio utilizado para separar el oro se filtra al agua y entra en la cadena alimenticia. Las comunidades afrodescendientes que dependen de la pesca han visto cómo sus fuentes de alimento se envenenan lentamente.',
         'questions': [{'question': '¿Qué sustancia contamina los ríos?', 'options': ['Plomo', 'Mercurio', 'Petróleo'], 'answer': 'Mercurio'},
                       {'question': '¿Quiénes se ven más afectados?', 'options': ['Los mineros', 'Las comunidades afrodescendientes', 'Los turistas'], 'answer': 'Las comunidades afrodescendientes'}]},
        {'passage': 'El concepto de "buen vivir" de los pueblos andinos propone una relación de equilibrio con la naturaleza, en contraste con el modelo extractivista que mide el progreso en términos de crecimiento económico. Para los kogui de la Sierra Nevada, la Tierra es un ser vivo que exige reciprocidad.',
         'questions': [{'question': '¿Qué propone el "buen vivir"?', 'options': ['Crecimiento económico', 'Equilibrio con la naturaleza', 'Más extracción'], 'answer': 'Equilibrio con la naturaleza'},
                       {'question': '¿Qué creen los kogui sobre la Tierra?', 'options': ['Es un recurso', 'Es un ser vivo', 'Es infinita'], 'answer': 'Es un ser vivo'}]},
        {'passage': 'Aunque Don Próspero presenta su proyecto como desarrollo sostenible, los datos revelan otra realidad. La tala ha reducido la cobertura boscosa en un cuarenta por ciento. Los corredores biológicos del jaguar se han fragmentado, aislando poblaciones que antes migraban libremente.',
         'questions': [{'question': '¿Cuánto se redujo la cobertura boscosa?', 'options': ['Veinte por ciento', 'Cuarenta por ciento', 'Sesenta por ciento'], 'answer': 'Cuarenta por ciento'},
                       {'question': '¿Qué pasó con los corredores del jaguar?', 'options': ['Se ampliaron', 'Se fragmentaron', 'Desaparecieron'], 'answer': 'Se fragmentaron'}]},
        {'passage': 'La lengua nasa yuwe tiene más de cien mil hablantes en el Cauca. Sin embargo, los jóvenes prefieren el español para comunicarse en redes sociales. Los abuelos temen que en dos generaciones la lengua desaparezca si no se integra en la educación formal.',
         'questions': [{'question': '¿Cuántos hablantes tiene el nasa yuwe?', 'options': ['Diez mil', 'Cien mil', 'Un millón'], 'answer': 'Cien mil'},
                       {'question': '¿Qué temen los abuelos?', 'options': ['Que los jóvenes no estudien', 'Que la lengua desaparezca', 'Que no usen redes sociales'], 'answer': 'Que la lengua desaparezca'}]},
        {'passage': 'El Pacífico colombiano recibe más lluvia que casi cualquier otro lugar del planeta. En Lloró, Chocó, caen más de trece mil milímetros al año. Esta humedad extrema sustenta una biodiversidad asombrosa pero también dificulta la construcción de infraestructura convencional.',
         'questions': [{'question': '¿Cuánta lluvia cae en Lloró?', 'options': ['Tres mil milímetros', 'Trece mil milímetros', 'Treinta mil milímetros'], 'answer': 'Trece mil milímetros'},
                       {'question': '¿Qué sustenta la humedad?', 'options': ['La agricultura', 'La biodiversidad', 'La infraestructura'], 'answer': 'La biodiversidad'}]},
        {'passage': 'Candelaria descubrió que el cuaderno de su abuela contenía mapas de los senderos que usaban los jaguares. Cada línea representaba una ruta migratoria. Los círculos marcaban lugares de descanso. El cuaderno era un atlas de otro modo de ver el territorio.',
         'questions': [{'question': '¿Qué contenía el cuaderno?', 'options': ['Recetas', 'Mapas de senderos de jaguares', 'Dibujos de plantas'], 'answer': 'Mapas de senderos de jaguares'},
                       {'question': '¿Qué marcaban los círculos?', 'options': ['Peligros', 'Lugares de descanso', 'Ríos'], 'answer': 'Lugares de descanso'}]},
    ],
    'C1': [
        {'passage': 'La noción de territorio trasciende la mera delimitación geográfica cuando se examina desde la cosmovisión de los pueblos originarios colombianos. Para los arhuacos, el territorio no se posee: se habita y se cuida. La "Línea Negra" que delimita sus tierras sagradas en la Sierra Nevada no es una frontera política sino un circuito de pagamentos espirituales.',
         'questions': [{'question': '¿Qué es la "Línea Negra"?', 'options': ['Una frontera política', 'Un circuito de pagamentos espirituales', 'Una carretera'], 'answer': 'Un circuito de pagamentos espirituales'},
                       {'question': '¿Cómo conciben el territorio los arhuacos?', 'options': ['Como propiedad', 'Como algo que se habita y se cuida', 'Como recurso económico'], 'answer': 'Como algo que se habita y se cuida'}]},
        {'passage': 'El conflicto entre conservación y desarrollo en la Amazonía colombiana ilustra una paradoja sistémica: las mismas comunidades que mejor preservan el bosque carecen de los servicios básicos que el desarrollo promete. Esta desigualdad estructural convierte la deforestación en una consecuencia, no una causa, de la marginación.',
         'questions': [{'question': '¿Cuál es la paradoja descrita?', 'options': ['Los conservacionistas destruyen', 'Quienes preservan carecen de servicios', 'El desarrollo es siempre positivo'], 'answer': 'Quienes preservan carecen de servicios'},
                       {'question': '¿La deforestación es presentada como...?', 'options': ['Una causa de marginación', 'Una consecuencia de la marginación', 'Un fenómeno natural'], 'answer': 'Una consecuencia de la marginación'}]},
        {'passage': 'La obra de Gabriel García Márquez no solo revolucionó la narrativa latinoamericana sino que reformuló la relación entre realidad y ficción. En Macondo, lo extraordinario se presenta como cotidiano y lo cotidiano como milagroso. Esta inversión no es un recurso estilístico sino una forma de epistemología: una manera distinta de conocer el mundo.',
         'questions': [{'question': '¿Qué reformuló García Márquez según el texto?', 'options': ['La gramática', 'La relación entre realidad y ficción', 'La historia de Colombia'], 'answer': 'La relación entre realidad y ficción'},
                       {'question': '¿Cómo describe el texto la inversión en Macondo?', 'options': ['Como recurso estilístico', 'Como epistemología', 'Como fantasía'], 'answer': 'Como epistemología'}]},
        {'passage': 'El sistema de justicia transicional colombiano enfrenta el desafío de conciliar la verdad histórica con la reconciliación social. Los testimonios recogidos por la Comisión de la Verdad revelan que las víctimas no buscan castigo sino reconocimiento: que se nombre lo que ocurrió y que no se repita.',
         'questions': [{'question': '¿Qué buscan las víctimas según el texto?', 'options': ['Castigo', 'Reconocimiento', 'Compensación económica'], 'answer': 'Reconocimiento'},
                       {'question': '¿Qué debe conciliar la justicia transicional?', 'options': ['Ley y orden', 'Verdad histórica y reconciliación', 'Gobierno y oposición'], 'answer': 'Verdad histórica y reconciliación'}]},
        {'passage': 'La etnobotánica del Amazonas colombiano documenta más de mil quinientas especies con uso medicinal conocido por las comunidades indígenas. Sin embargo, menos del cinco por ciento ha sido estudiada por la farmacología moderna. Este desfase no refleja un vacío de conocimiento sino un sesgo epistémico.',
         'questions': [{'question': '¿Cuántas especies medicinales se documentan?', 'options': ['Quinientas', 'Más de mil quinientas', 'Cinco mil'], 'answer': 'Más de mil quinientas'},
                       {'question': '¿Qué refleja el desfase según el texto?', 'options': ['Falta de plantas', 'Un sesgo epistémico', 'Desinterés científico'], 'answer': 'Un sesgo epistémico'}]},
        {'passage': 'Doña Asunción solía decir que escuchar es el acto más difícil del lenguaje. No se refería a la audición sino a la disposición de suspender el propio juicio para recibir la palabra del otro. En la tradición oral muisca, el que escucha participa en la creación del relato tanto como el que habla.',
         'questions': [{'question': '¿A qué se refería Doña Asunción con "escuchar"?', 'options': ['A la audición física', 'A suspender el juicio propio', 'A repetir lo que oyes'], 'answer': 'A suspender el juicio propio'},
                       {'question': '¿Qué papel tiene el oyente en la tradición muisca?', 'options': ['Pasivo', 'Participa en la creación del relato', 'Solo memoriza'], 'answer': 'Participa en la creación del relato'}]},
    ],
    'C2': [
        {'passage': 'La glotopolítica del español en América Latina revela una tensión constitutiva entre la lengua como instrumento de cohesión nacional y como vehículo de homogeneización cultural. El monolingüismo estatal, consagrado implícitamente en la mayoría de las constituciones hasta finales del siglo XX, operó como un dispositivo de invisibilización de las lenguas originarias.',
         'questions': [{'question': '¿Qué revela la glotopolítica del español?', 'options': ['La superioridad del español', 'Una tensión entre cohesión y homogeneización', 'La necesidad de más lenguas oficiales'], 'answer': 'Una tensión entre cohesión y homogeneización'},
                       {'question': '¿Cómo operó el monolingüismo estatal?', 'options': ['Como protección', 'Como dispositivo de invisibilización', 'Como política educativa'], 'answer': 'Como dispositivo de invisibilización'}]},
        {'passage': 'El palimpsesto territorial que constituye la Colombia contemporánea superpone cartografías incompatibles: las líneas rectas de la planificación estatal, las cuencas hidrográficas que organizan el saber indígena, los corredores del narcotráfico que desafían toda frontera, y las rutas migratorias de especies como el jaguar, indiferentes a la soberanía humana.',
         'questions': [{'question': '¿Qué metáfora usa el texto para Colombia?', 'options': ['Un mosaico', 'Un palimpsesto territorial', 'Un rompecabezas'], 'answer': 'Un palimpsesto territorial'},
                       {'question': '¿Qué organiza el saber indígena según el texto?', 'options': ['Líneas rectas', 'Cuencas hidrográficas', 'Corredores del narcotráfico'], 'answer': 'Cuencas hidrográficas'}]},
        {'passage': 'Sostener que la oralidad es una forma inferior de cultura letrada implica desconocer que la escritura misma es una tecnología relativamente reciente. Los cantos de los uitoto codifican conocimientos botánicos, astronómicos y ecológicos con una precisión que la taxonomía linneana difícilmente iguala, aunque emplee medios radicalmente distintos.',
         'questions': [{'question': '¿Qué codifican los cantos uitoto?', 'options': ['Historias de amor', 'Conocimientos botánicos, astronómicos y ecológicos', 'Reglas gramaticales'], 'answer': 'Conocimientos botánicos, astronómicos y ecológicos'},
                       {'question': '¿Con qué se compara la precisión de los cantos?', 'options': ['Con la poesía', 'Con la taxonomía linneana', 'Con la cartografía'], 'answer': 'Con la taxonomía linneana'}]},
        {'passage': 'La figura de La Sombra en el viaje de Yaguará no es una antagonista en el sentido convencional. Representa la dimensión del lenguaje que resiste la transparencia: la ambigüedad, la ironía, el silencio significativo. Aprender una lengua implica aceptar que siempre quedará un resto intraducible, un excedente de sentido que ningún diccionario agota.',
         'questions': [{'question': '¿Qué representa La Sombra?', 'options': ['El mal', 'La dimensión opaca del lenguaje', 'Un enemigo físico'], 'answer': 'La dimensión opaca del lenguaje'},
                       {'question': '¿Qué implica aprender una lengua según el texto?', 'options': ['Memorizar vocabulario', 'Aceptar un resto intraducible', 'Traducir todo perfectamente'], 'answer': 'Aceptar un resto intraducible'}]},
        {'passage': 'El principio de correspondencia — "como es arriba, es abajo" — estructura no solo la cosmología del viaje de Yaguará sino también su pedagogía. Cada nivel lingüístico replica la totalidad del sistema en una escala diferente: la fonología contiene la morfología en germen, la sintaxis anticipa el discurso, y la pragmática revela que toda gramática es, en última instancia, una negociación social.',
         'questions': [{'question': '¿Qué principio estructura la pedagogía?', 'options': ['El principio de autoridad', 'El principio de correspondencia', 'El principio de economía'], 'answer': 'El principio de correspondencia'},
                       {'question': '¿Qué revela la pragmática según el texto?', 'options': ['Que la gramática es matemática', 'Que toda gramática es una negociación social', 'Que la fonología es suficiente'], 'answer': 'Que toda gramática es una negociación social'}]},
        {'passage': 'Abuela Ceiba no habla: resuena. Su comunicación trasciende el código lingüístico y opera en el registro de la vibración. Cuando Yaguará apoya su costado contra la corteza, no escucha palabras sino patrones — ritmos que su cuerpo reconoce antes de que su mente los nombre. Esta escucha pre-semántica constituye, quizás, el fundamento sobre el cual todo lenguaje se erige.',
         'questions': [{'question': '¿Cómo se comunica Abuela Ceiba?', 'options': ['Con palabras claras', 'Mediante vibración y resonancia', 'A través de escritura'], 'answer': 'Mediante vibración y resonancia'},
                       {'question': '¿Qué es la escucha pre-semántica?', 'options': ['Escuchar con atención', 'Reconocer patrones antes de nombrarlos', 'No escuchar nada'], 'answer': 'Reconocer patrones antes de nombrarlos'}]},
    ],
}

# -- TERTULIA: multi-register conversation turns --
TERTULIA_DATA = {
    'B1': [
        {'turns': [{'speaker': 'A', 'name': 'Vendedor', 'text': '¡Buenos días! ¿Qué necesita?',
                     'options': ['Necesito dos kilos de yuca, por favor.', 'Dame yuca ya.', 'Oye, ¿hay yuca o qué?'], 'answer': 'Necesito dos kilos de yuca, por favor.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Profesora', 'text': 'Buenas tardes. ¿Podría presentarse?',
                     'options': ['Soy Candelaria, mucho gusto.', 'Yo soy Cande, ¿y qué?', 'Hola profe, me llaman Cande.'], 'answer': 'Soy Candelaria, mucho gusto.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Amigo', 'text': '¡Quiubo! ¿Vamos al río?',
                     'options': ['¡Dale, vamos!', 'Estimado amigo, acepto su invitación.', 'Sería un placer acompañarlo.'], 'answer': '¡Dale, vamos!'}]},
        {'turns': [{'speaker': 'A', 'name': 'Doctora', 'text': '¿Puede describir los síntomas?',
                     'options': ['Me duele la cabeza desde ayer y tengo fiebre.', 'Pues me siento maluco.', 'Uy, estoy remal.'], 'answer': 'Me duele la cabeza desde ayer y tengo fiebre.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Vecina', 'text': '¡Hola! ¿Supiste lo del festival?',
                     'options': ['Sí, dicen que va a ser en la plaza.', 'Estimada vecina, estoy informado del evento.', 'No me interesa, gracias.'], 'answer': 'Sí, dicen que va a ser en la plaza.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Alcalde', 'text': 'Señores, necesitamos voluntarios para la limpieza del río.',
                     'options': ['Cuente con nosotros, señor alcalde.', '¡Listo, parcero!', 'Va, hagámosle.'], 'answer': 'Cuente con nosotros, señor alcalde.'}]},
    ],
    'B2': [
        {'turns': [{'speaker': 'A', 'name': 'Don Próspero', 'text': 'Este proyecto generará empleo para toda la región.',
                     'options': ['Entiendo su posición, pero ¿ha considerado el impacto ambiental?', '¡Eso es mentira!', 'Sí señor, lo que usted diga.'], 'answer': 'Entiendo su posición, pero ¿ha considerado el impacto ambiental?'}]},
        {'turns': [{'speaker': 'A', 'name': 'Periodista', 'text': '¿Cuál es su opinión sobre la deforestación en la zona?',
                     'options': ['Considero que se requiere un enfoque integral que equilibre desarrollo y conservación.', 'Es horrible y ya.', 'Pues pa qué le digo, está feo eso.'], 'answer': 'Considero que se requiere un enfoque integral que equilibre desarrollo y conservación.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Abuela', 'text': 'Mija, ¿por qué no vienes a visitarme más seguido?',
                     'options': ['Ay, abuelita, es que el trabajo no me deja, pero voy este fin de semana.', 'Estimada abuela, lamento la infrecuencia de mis visitas.', 'Porque estoy ocupada.'], 'answer': 'Ay, abuelita, es que el trabajo no me deja, pero voy este fin de semana.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Investigadora', 'text': '¿Podría ampliar su análisis sobre los corredores biológicos?',
                     'options': ['Con gusto. Los corredores permiten la conectividad genética entre poblaciones aisladas.', 'Son como caminos de animales.', 'Pues los jaguares caminan por ahí.'], 'answer': 'Con gusto. Los corredores permiten la conectividad genética entre poblaciones aisladas.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Compañero', 'text': '¿Oíste lo que dijo Don Próspero en la reunión?',
                     'options': ['Sí, me pareció contradictorio. Dice que cuida el bosque pero tala más cada mes.', 'Señor, me permito discrepar de lo expresado.', 'Ese señor no me gusta.'], 'answer': 'Sí, me pareció contradictorio. Dice que cuida el bosque pero tala más cada mes.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Funcionaria', 'text': 'Necesitamos que presente la documentación antes del viernes.',
                     'options': ['Entendido. ¿Podría indicarme qué documentos específicos se requieren?', '¡Ay, qué pereza!', 'Bueno, yo veo.'], 'answer': 'Entendido. ¿Podría indicarme qué documentos específicos se requieren?'}]},
    ],
    'C1': [
        {'turns': [{'speaker': 'A', 'name': 'Panelista', 'text': '¿No cree usted que el desarrollo sostenible es un oxímoron?',
                     'options': ['Es una tensión productiva, no necesariamente una contradicción. Depende de cómo definamos desarrollo.', 'Sí, total.', 'No sé, tal vez.'], 'answer': 'Es una tensión productiva, no necesariamente una contradicción. Depende de cómo definamos desarrollo.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Colega', 'text': 'Leí tu artículo. Me parece que simplificas la posición de los indígenas.',
                     'options': ['Agradezco la observación. Quizás debería matizar la heterogeneidad de sus posturas.', 'No simplifico nada.', '¿A ti qué te importa?'], 'answer': 'Agradezco la observación. Quizás debería matizar la heterogeneidad de sus posturas.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Entrevistador', 'text': '¿Cómo explicaría la situación a alguien sin conocimiento previo?',
                     'options': ['En términos sencillos: hay quienes quieren construir y quienes quieren conservar, y ambos tienen razones legítimas.', 'Es complicado, no se puede simplificar así nomás.', 'Pues que hay unos que tumban árboles y otros que no quieren.'], 'answer': 'En términos sencillos: hay quienes quieren construir y quienes quieren conservar, y ambos tienen razones legítimas.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Diplomática', 'text': 'Su gobierno ha sido criticado por la lentitud en implementar los acuerdos ambientales.',
                     'options': ['Reconocemos los desafíos y estamos trabajando en un plan de acción con plazos verificables.', 'Pues sí, estamos lentos.', 'Eso no es cierto, es propaganda.'], 'answer': 'Reconocemos los desafíos y estamos trabajando en un plan de acción con plazos verificables.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Estudiante', 'text': 'Profe, no entiendo por qué no se puede simplemente prohibir la deforestación.',
                     'options': ['Buena pregunta. La prohibición sin alternativas económicas desplaza el problema en lugar de resolverlo.', 'Porque es complicado.', 'Lee el capítulo tres y me cuentas.'], 'answer': 'Buena pregunta. La prohibición sin alternativas económicas desplaza el problema en lugar de resolverlo.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Anciano kogui', 'text': 'Los hermanos menores no escuchan la tierra.',
                     'options': ['Su observación nos recuerda que hay formas de conocimiento que la ciencia occidental apenas empieza a reconocer.', '¿A qué se refiere?', 'Sí, tiene razón.'], 'answer': 'Su observación nos recuerda que hay formas de conocimiento que la ciencia occidental apenas empieza a reconocer.'}]},
    ],
    'C2': [
        {'turns': [{'speaker': 'A', 'name': 'Filósofa', 'text': '¿No será que el lenguaje mismo es el primer acto de colonización del territorio?',
                     'options': ['Cabe matizar: el lenguaje puede ser tanto instrumento de apropiación como medio de resistencia. La cuestión es quién nombra y desde qué lugar de enunciación.', 'Interesante.', 'No creo, el lenguaje es neutral.'], 'answer': 'Cabe matizar: el lenguaje puede ser tanto instrumento de apropiación como medio de resistencia. La cuestión es quién nombra y desde qué lugar de enunciación.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Poeta', 'text': 'La palabra "selva" ya no dice selva. Ha sido vaciada por el discurso turístico.',
                     'options': ['Coincido en que la mercantilización del léxico erosiona su capacidad referencial. Quizás necesitamos recuperar las palabras desde la experiencia.', 'Pues sí, suena bonita pero no dice nada.', 'Yo sigo usando "selva" normalmente.'], 'answer': 'Coincido en que la mercantilización del léxico erosiona su capacidad referencial. Quizás necesitamos recuperar las palabras desde la experiencia.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Antropóloga', 'text': 'Su tesis sobre la oralidad como epistemología requiere una fundamentación más rigurosa.',
                     'options': ['Tiene razón. Me propongo incorporar el marco de Mignolo sobre la colonialidad del saber para contextualizar la jerarquía oralidad-escritura.', 'Bueno, ya veré.', 'Mi tesis está bien así.'], 'answer': 'Tiene razón. Me propongo incorporar el marco de Mignolo sobre la colonialidad del saber para contextualizar la jerarquía oralidad-escritura.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Abuela Ceiba', 'text': 'Las raíces no preguntan permiso para crecer.',
                     'options': ['En esa imagen resuena el principio de que el conocimiento genuino se expande por necesidad interna, no por autorización externa.', 'Qué bonita frase.', 'No entiendo.'], 'answer': 'En esa imagen resuena el principio de que el conocimiento genuino se expande por necesidad interna, no por autorización externa.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Lingüista', 'text': '¿Cómo justifica la inclusión de formas dialectales en un currículo de español estándar?',
                     'options': ['El estándar es una abstracción útil pero no debe operar como norma excluyente. La competencia sociolingüística exige exposición a la variación real.', 'Porque así habla la gente.', 'No las incluyo, solo enseño estándar.'], 'answer': 'El estándar es una abstracción útil pero no debe operar como norma excluyente. La competencia sociolingüística exige exposición a la variación real.'}]},
        {'turns': [{'speaker': 'A', 'name': 'Juez', 'text': 'El tribunal requiere que el perito reformule su dictamen en términos accesibles para las partes.',
                     'options': ['Con la venia del tribunal: el informe concluye que la intervención propuesta causaría daños irreversibles al ecosistema, afectando los derechos fundamentales de las comunidades.', 'Pues que se va a dañar todo si siguen así.', 'Ya lo dije claro en el informe.'], 'answer': 'Con la venia del tribunal: el informe concluye que la intervención propuesta causaría daños irreversibles al ecosistema, afectando los derechos fundamentales de las comunidades.'}]},
    ],
}

# -- PREGONERO: register writing situations --
PREGONERO_DATA = {
    'B1': [
        {'situation': 'El pueblo celebra el festival de la cosecha. Escribe el anuncio para invitar a todos.', 'register': 'formal', 'keywords': ['festival', 'cosecha', 'invitar', 'plaza'], 'modelAnswer': 'Se invita a todos los habitantes del pueblo al festival de la cosecha, que se celebrará este sábado en la plaza principal a partir de las diez de la mañana.'},
        {'situation': 'Tu amigo perdió su perro en el mercado. Escribe un aviso para encontrarlo.', 'register': 'informal', 'keywords': ['perro', 'perdido', 'mercado', 'ayuda'], 'modelAnswer': '¡Ayuda! Se perdió un perro café cerca del mercado. Se llama Canelo. Si lo ves, avísanos por favor.'},
        {'situation': 'La escuela necesita libros donados. Escribe un anuncio para la comunidad.', 'register': 'formal', 'keywords': ['escuela', 'libros', 'donación', 'niños'], 'modelAnswer': 'La escuela del pueblo solicita donaciones de libros para los niños. Pueden entregarlos en la dirección de lunes a viernes.'},
        {'situation': 'Hay una reunión comunitaria sobre el río contaminado. Anuncia la reunión.', 'register': 'formal', 'keywords': ['reunión', 'río', 'comunidad', 'asistir'], 'modelAnswer': 'Se convoca a toda la comunidad a una reunión sobre la situación del río. Será el miércoles a las seis de la tarde en la casa comunal.'},
        {'situation': 'Un grupo de músicos va a tocar cumbia en la plaza. Escribe un aviso para los vecinos.', 'register': 'informal', 'keywords': ['cumbia', 'plaza', 'música', 'noche'], 'modelAnswer': '¡Esta noche hay cumbia en la plaza! Los músicos del pueblo van a tocar desde las ocho. ¡Todos están invitados!'},
        {'situation': 'El centro de salud ofrece vacunación gratuita. Escribe el anuncio oficial.', 'register': 'formal', 'keywords': ['vacunación', 'gratuita', 'centro', 'salud'], 'modelAnswer': 'El centro de salud informa que la jornada de vacunación gratuita se realizará el próximo lunes de ocho a cuatro de la tarde.'},
    ],
    'B2': [
        {'situation': 'Don Próspero anuncia su proyecto de carretera. Escribe el comunicado oficial que él publicaría.', 'register': 'formal', 'keywords': ['proyecto', 'desarrollo', 'empleo', 'infraestructura'], 'modelAnswer': 'La empresa Próspero S.A. tiene el agrado de informar a la comunidad sobre el proyecto de infraestructura vial que generará doscientos empleos directos y mejorará la conectividad regional.'},
        {'situation': 'Candelaria quiere organizar una protesta pacífica contra la tala del bosque. Redacta la convocatoria.', 'register': 'formal', 'keywords': ['convocatoria', 'protesta', 'bosque', 'derechos'], 'modelAnswer': 'Se convoca a la ciudadanía a una manifestación pacífica en defensa del bosque comunal. Exigimos que se respeten los derechos ambientales de nuestra comunidad.'},
        {'situation': 'Una ONG escribe una carta al gobernador pidiendo protección para los corredores del jaguar.', 'register': 'formal', 'keywords': ['corredores', 'jaguar', 'protección', 'gobernador'], 'modelAnswer': 'Excelentísimo señor gobernador: Nos dirigimos a usted para solicitar la declaración de los corredores biológicos del jaguar como zona de protección especial.'},
        {'situation': 'Un joven del pueblo escribe en redes sociales sobre lo que está pasando con el bosque.', 'register': 'informal', 'keywords': ['bosque', 'destruyendo', 'gente', 'parar'], 'modelAnswer': 'Gente, están destruyendo nuestro bosque. Cada día hay menos árboles y los animales no tienen adónde ir. Tenemos que hacer algo. Compartan esto.'},
        {'situation': 'Escribe la entrada de una enciclopedia sobre la ceiba pentandra.', 'register': 'académico', 'keywords': ['ceiba', 'especie', 'hábitat', 'altura'], 'modelAnswer': 'La ceiba pentandra es una especie arbórea tropical que puede alcanzar los setenta metros de altura. Se distribuye en zonas húmedas de América Central y del Sur.'},
        {'situation': 'Un pescador escribe un cartel para su puesto en el mercado.', 'register': 'informal', 'keywords': ['pescado', 'fresco', 'río', 'precio'], 'modelAnswer': '¡Pescado fresco del río! Bocachico y bagre, recién sacados esta mañana. Buenos precios. ¡Lleve, lleve!'},
    ],
    'C1': [
        {'situation': 'Redacta un artículo de opinión sobre el conflicto entre desarrollo económico y conservación ambiental.', 'register': 'académico', 'keywords': ['desarrollo', 'conservación', 'sostenible', 'equilibrio'], 'modelAnswer': 'El dilema entre desarrollo económico y conservación ambiental no admite soluciones binarias. Es imperativo articular un modelo que reconozca la interdependencia entre el bienestar humano y la integridad ecosistémica.'},
        {'situation': 'Escribe el discurso de apertura de un congreso sobre lenguas indígenas en peligro.', 'register': 'formal', 'keywords': ['lenguas', 'diversidad', 'patrimonio', 'preservación'], 'modelAnswer': 'Distinguidos colegas, bienvenidos a este congreso. Cada lengua que se extingue nos priva de una forma única de comprender el mundo. Nuestra tarea es urgente y no admite dilación.'},
        {'situation': 'Escribe la reseña de un libro sobre la historia oral de los wayúu.', 'register': 'académico', 'keywords': ['oralidad', 'wayúu', 'tradición', 'memoria'], 'modelAnswer': 'La obra constituye una contribución significativa al campo de los estudios de la oralidad. Mediante una metodología etnográfica rigurosa, la autora demuestra cómo la tradición oral wayúu opera como sistema de preservación de la memoria colectiva.'},
        {'situation': 'Redacta una carta formal al ministerio de educación proponiendo la enseñanza bilingüe.', 'register': 'formal', 'keywords': ['bilingüe', 'educación', 'propuesta', 'ministerio'], 'modelAnswer': 'Nos permitimos someter a su consideración la presente propuesta de implementación de un programa de educación bilingüe que integre las lenguas originarias en el currículo escolar.'},
        {'situation': 'Escribe la narración que un guía de ecoturismo haría durante un recorrido por la selva.', 'register': 'divulgativo', 'keywords': ['selva', 'biodiversidad', 'ecosistema', 'conservación'], 'modelAnswer': 'Estamos entrando en uno de los ecosistemas más biodiversos del planeta. Cada árbol que ven alberga decenas de especies. La selva no es solo un lugar: es una red de relaciones vivas.'},
        {'situation': 'Escribe un informe técnico sobre el estado de los ríos de la región.', 'register': 'técnico', 'keywords': ['contaminación', 'niveles', 'indicadores', 'recomendaciones'], 'modelAnswer': 'Los indicadores de calidad del agua revelan niveles de contaminación por mercurio que superan en un treinta por ciento los valores máximos permitidos. Se recomienda la suspensión inmediata de actividades extractivas.'},
    ],
    'C2': [
        {'situation': 'Redacta el alegato de un abogado que defiende los derechos de la naturaleza ante un tribunal.', 'register': 'jurídico', 'keywords': ['derechos', 'naturaleza', 'jurisprudencia', 'sujeto'], 'modelAnswer': 'Con fundamento en la jurisprudencia de la Corte Constitucional que reconoce a los ecosistemas como sujetos de derechos, solicitamos al tribunal que ordene la suspensión cautelar de las obras que amenazan la integridad del corredor biológico.'},
        {'situation': 'Escribe el prólogo de una antología de poesía del Pacífico colombiano.', 'register': 'literario', 'keywords': ['poesía', 'Pacífico', 'voz', 'resistencia'], 'modelAnswer': 'Las voces que este volumen reúne no buscan representar el Pacífico sino emanar de él. Cada poema es un acto de resistencia contra el silenciamiento, un recordatorio de que la palabra cantada precede y trasciende a la palabra escrita.'},
        {'situation': 'Escribe un ensayo filosófico sobre la relación entre lenguaje y territorio.', 'register': 'académico', 'keywords': ['lenguaje', 'territorio', 'ontología', 'habitar'], 'modelAnswer': 'La relación entre lenguaje y territorio excede la mera nominación. Nombrar un lugar no es describirlo sino instaurar una relación ontológica: el nombre crea un modo de habitar.'},
        {'situation': 'Redacta un comunicado diplomático sobre la protección transfronteriza de la Amazonía.', 'register': 'diplomático', 'keywords': ['Amazonía', 'cooperación', 'transfronterizo', 'soberanía'], 'modelAnswer': 'Los Estados parte reafirman su compromiso con la protección del bioma amazónico, reconociendo que su conservación exige mecanismos de cooperación que trasciendan las fronteras nacionales sin menoscabo de la soberanía.'},
        {'situation': 'Escribe la meditación que Yaguará haría al final de su viaje, mirando desde la copa de Abuela Ceiba.', 'register': 'literario', 'keywords': ['viaje', 'palabras', 'raíces', 'escuchar'], 'modelAnswer': 'Desde aquí arriba, las palabras se ven como lo que siempre fueron: raíces aéreas buscando tierra, semillas buscando grieta. El viaje no terminó: aprendí que escuchar es el único verbo que no se conjuga en pasado.'},
        {'situation': 'Escribe la crónica periodística de la ceremonia en que se consagra a Yaguará.', 'register': 'periodístico', 'keywords': ['ceremonia', 'consagración', 'comunidad', 'tradición'], 'modelAnswer': 'En una ceremonia que congregó a representantes de las cuatro comunidades guardianas, el espíritu de Yaguará fue consagrado como protector del territorio. Los mayores describieron el acto no como un fin sino como un umbral.'},
    ],
}

# -- ORACULO: pragmatic prediction (partial dialogue + predict next turn) --
ORACULO_DATA = {
    'B1': [
        {'scene': [{'speaker': 'Candelaria', 'text': 'Yaguará, mira esas nubes negras.'}, {'speaker': 'Yaguará', 'text': 'Sí, están muy oscuras.'}],
         'options': ['Vamos a buscar refugio.', '¡Qué bonitas las nubes!', 'Tengo hambre.'], 'answer': 'Vamos a buscar refugio.', 'explanation': 'Las nubes negras indican lluvia. La respuesta lógica es buscar refugio.'},
        {'scene': [{'speaker': 'Vendedor', 'text': '¿Algo más?'}, {'speaker': 'Candelaria', 'text': 'Sí, ¿cuánto cuesta el plátano?'}, {'speaker': 'Vendedor', 'text': 'Mil pesos el kilo.'}],
         'options': ['Deme dos kilos, por favor.', 'Adiós.', '¿Dónde está el río?'], 'answer': 'Deme dos kilos, por favor.', 'explanation': 'En un mercado, después de preguntar el precio, lo natural es comprar o negociar.'},
        {'scene': [{'speaker': 'Yaguará', 'text': 'Estoy muy cansada.'}, {'speaker': 'Candelaria', 'text': 'Yo también.'}],
         'options': ['Descansemos aquí un rato.', '¡Vamos a correr!', '¿Cuánto cuesta?'], 'answer': 'Descansemos aquí un rato.', 'explanation': 'Si ambas están cansadas, lo pragmático es proponer descansar.'},
        {'scene': [{'speaker': 'Niño', 'text': '¡Se me cayó el helado!'}, {'speaker': 'Mamá', 'text': 'Ay, mi amor.'}],
         'options': ['No llores, te compro otro.', '¡Qué bueno!', 'El helado es frío.'], 'answer': 'No llores, te compro otro.', 'explanation': 'La madre consuela al niño ofreciendo una solución.'},
        {'scene': [{'speaker': 'Don Próspero', 'text': 'Este camino va a ser muy bueno para el pueblo.'}, {'speaker': 'Candelaria', 'text': 'Pero pasa por el bosque de los jaguares.'}],
         'options': ['Hay que encontrar otra ruta.', 'Los jaguares no importan.', '¿Quieres helado?'], 'answer': 'Hay que encontrar otra ruta.', 'explanation': 'Candelaria señala un problema. La respuesta constructiva es buscar alternativa.'},
        {'scene': [{'speaker': 'Abuela', 'text': '¿Quién quiere arepas?'}, {'speaker': 'Todos', 'text': '¡Yo, yo, yo!'}],
         'options': ['Bueno, pues a cocinar.', 'No hay arepas.', '¿Dónde está el mercado?'], 'answer': 'Bueno, pues a cocinar.', 'explanation': 'Cuando todos quieren algo y la abuela pregunta, la acción natural es prepararlo.'},
    ],
    'B2': [
        {'scene': [{'speaker': 'Don Próspero', 'text': 'La carretera beneficiará a todos.'}, {'speaker': 'Líder comunal', 'text': 'Nadie nos preguntó si la queríamos.'}, {'speaker': 'Don Próspero', 'text': 'El progreso no espera consultas.'}],
         'options': ['El progreso que ignora a la gente no es progreso.', '¡Viva la carretera!', 'No entiendo.'], 'answer': 'El progreso que ignora a la gente no es progreso.', 'explanation': 'La tensión entre desarrollo impuesto y participación comunitaria requiere una respuesta que defienda el derecho a la consulta.'},
        {'scene': [{'speaker': 'Periodista', 'text': '¿Es cierto que la empresa contamina el río?'}, {'speaker': 'Representante', 'text': 'Nuestros niveles están dentro de la norma.'}],
         'options': ['¿Podría mostrarnos los estudios de impacto ambiental?', 'Ah, bueno, entonces no hay problema.', 'Los ríos siempre están sucios.'], 'answer': '¿Podría mostrarnos los estudios de impacto ambiental?', 'explanation': 'Ante una afirmación corporativa, lo pragmáticamente adecuado es solicitar evidencia.'},
        {'scene': [{'speaker': 'Candelaria', 'text': 'Mi abuela dice que antes el río era transparente.'}, {'speaker': 'Yaguará', 'text': 'Los ríos tienen memoria.'}],
         'options': ['Si escuchamos al río, nos dirá qué necesita.', 'Los ríos no tienen memoria, son agua.', 'Vamos a nadar.'], 'answer': 'Si escuchamos al río, nos dirá qué necesita.', 'explanation': 'El registro poético-ecológico de Yaguará invita a continuar con la misma sensibilidad.'},
        {'scene': [{'speaker': 'Investigadora', 'text': 'Los datos muestran una reducción del cuarenta por ciento en la población de jaguares.'}, {'speaker': 'Funcionario', 'text': 'Necesitamos más estudios antes de actuar.'}],
         'options': ['Cuando los estudios terminen, puede que no queden jaguares que estudiar.', 'Sí, más estudios.', 'Los jaguares se adaptan.'], 'answer': 'Cuando los estudios terminen, puede que no queden jaguares que estudiar.', 'explanation': 'La urgencia de la situación exige una respuesta que cuestione la dilación.'},
        {'scene': [{'speaker': 'Madre', 'text': '¿Por qué quieres ir a la selva? Es peligroso.'}, {'speaker': 'Candelaria', 'text': 'Porque allá está Yaguará, y me necesita.'}],
         'options': ['Ten cuidado, mija. Lleva agua y vuelve antes de que oscurezca.', '¡No vas a ir!', 'La selva no existe.'], 'answer': 'Ten cuidado, mija. Lleva agua y vuelve antes de que oscurezca.', 'explanation': 'La madre acepta la determinación de su hija y responde con cuidado práctico, no con prohibición.'},
        {'scene': [{'speaker': 'Turista', 'text': 'Quiero ver un jaguar en libertad.'}, {'speaker': 'Guía', 'text': 'El jaguar no se muestra a quien lo busca.'}],
         'options': ['Entonces ¿cómo puedo verlo?', '¡Qué estafa!', 'No me importa.'], 'answer': 'Entonces ¿cómo puedo verlo?', 'explanation': 'La curiosidad respetuosa es la respuesta pragmáticamente adecuada ante una afirmación enigmática.'},
    ],
    'C1': [
        {'scene': [{'speaker': 'Panelista A', 'text': 'La educación bilingüe es un derecho, no un privilegio.'}, {'speaker': 'Panelista B', 'text': 'Pero los recursos son limitados. No podemos enseñar en sesenta y cinco lenguas.'}],
         'options': ['No se trata de enseñar en todas, sino de no prohibir ninguna.', 'Tiene razón, es imposible.', 'Solo español.'], 'answer': 'No se trata de enseñar en todas, sino de no prohibir ninguna.', 'explanation': 'La respuesta reenmarca el debate: de la imposibilidad logística al derecho fundamental.'},
        {'scene': [{'speaker': 'Doña Asunción', 'text': 'Yo escuché este río cuando era niña. Cantaba diferente.'}, {'speaker': 'Yaguará', 'text': 'Los ríos cambian su canto cuando sufren.'}],
         'options': ['¿Qué podemos hacer para que vuelva a cantar como antes?', 'Es solo agua, no canta.', 'Qué interesante.'], 'answer': '¿Qué podemos hacer para que vuelva a cantar como antes?', 'explanation': 'La pregunta acepta el marco metafórico y lo transforma en acción.'},
        {'scene': [{'speaker': 'Estudiante', 'text': 'Profesor, ¿por qué estudiamos la cosmovisión indígena en una clase de ecología?'}, {'speaker': 'Profesor', 'text': 'Porque la ciencia occidental no tiene todas las respuestas.'}],
         'options': ['¿Podría darnos un ejemplo concreto de conocimiento indígena que complemente la ciencia?', 'Eso no es científico.', 'Bueno.'], 'answer': '¿Podría darnos un ejemplo concreto de conocimiento indígena que complemente la ciencia?', 'explanation': 'La pregunta profundiza con rigor sin rechazar la premisa.'},
        {'scene': [{'speaker': 'Embajador', 'text': 'Mi país considera la Amazonía un asunto de soberanía nacional.'}, {'speaker': 'Delegada', 'text': 'Y la comunidad internacional la considera patrimonio de la humanidad.'}],
         'options': ['Quizás necesitamos un marco que concilie soberanía con responsabilidad compartida.', 'La soberanía es más importante.', 'No es asunto mío.'], 'answer': 'Quizás necesitamos un marco que concilie soberanía con responsabilidad compartida.', 'explanation': 'La diplomacia exige buscar síntesis, no tomar partido absoluto.'},
        {'scene': [{'speaker': 'La Sombra', 'text': '¿Crees que las palabras pueden salvar un bosque?'}, {'speaker': 'Yaguará', 'text': '...'}],
         'options': ['Las palabras no salvan bosques, pero sin ellas nadie sabría que hay que salvarlos.', 'No.', 'Sí, claro.'], 'answer': 'Las palabras no salvan bosques, pero sin ellas nadie sabría que hay que salvarlos.', 'explanation': 'La paradoja de La Sombra exige una respuesta que reconozca la limitación y el poder del lenguaje simultáneamente.'},
        {'scene': [{'speaker': 'Mamá Jaguar', 'text': 'Ya casi eres lo que viniste a ser.'}, {'speaker': 'Yaguará', 'text': '¿Y qué vine a ser?'}],
         'options': ['Eso solo lo sabrás cuando dejes de preguntar y empieces a escuchar.', 'Un jaguar grande.', 'No sé.'], 'answer': 'Eso solo lo sabrás cuando dejes de preguntar y empieces a escuchar.', 'explanation': 'El registro oracular de Mamá Jaguar invita a una respuesta que privilegie la escucha sobre la explicación.'},
    ],
    'C2': [
        {'scene': [{'speaker': 'Filósofa', 'text': 'Si el lenguaje construye realidad, ¿destruir una lengua es destruir un mundo?'}, {'speaker': 'Lingüista', 'text': 'Es destruir una posibilidad de mundo.'}],
         'options': ['Y con cada posibilidad perdida, se estrecha el horizonte de lo que la humanidad puede llegar a pensar.', 'Interesante debate.', 'Exageran.'], 'answer': 'Y con cada posibilidad perdida, se estrecha el horizonte de lo que la humanidad puede llegar a pensar.', 'explanation': 'La respuesta extiende la cadena argumentativa hacia sus consecuencias epistemológicas.'},
        {'scene': [{'speaker': 'Abuela Ceiba', 'text': 'El que escucha, siembra.'}, {'speaker': 'Río', 'text': 'El que nombra, cosecha.'}],
         'options': ['Entonces el silencio es la tierra donde crece todo lenguaje.', 'Bonitas metáforas.', '¿Qué siembra?'], 'answer': 'Entonces el silencio es la tierra donde crece todo lenguaje.', 'explanation': 'El registro mítico exige completar la tríada: escuchar-nombrar-callar.'},
        {'scene': [{'speaker': 'Poeta', 'text': 'Escribí un poema sobre la selva, pero la selva no cabe en un poema.'}, {'speaker': 'Novelista', 'text': 'Ni en una novela.'}],
         'options': ['Quizás la selva no necesita caber. Quizás el poema necesita aprender a desbordarse.', 'Entonces no escriban.', 'Pues sí.'], 'answer': 'Quizás la selva no necesita caber. Quizás el poema necesita aprender a desbordarse.', 'explanation': 'La respuesta invierte la relación contenedor-contenido.'},
        {'scene': [{'speaker': 'La Sombra', 'text': 'Todo lo que aprendiste puede olvidarse.'}, {'speaker': 'Yaguará', 'text': 'Lo sé.'}],
         'options': ['Pero lo que viví al aprenderlo me transformó, y eso no se olvida.', 'Entonces ¿para qué aprender?', 'No es cierto.'], 'answer': 'Pero lo que viví al aprenderlo me transformó, y eso no se olvida.', 'explanation': 'La distinción entre conocimiento como dato y conocimiento como experiencia transformadora.'},
        {'scene': [{'speaker': 'Juez', 'text': 'El río no puede ser demandante porque no es persona.'}, {'speaker': 'Abogada', 'text': 'La Corte Constitucional ya reconoció al río Atrato como sujeto de derechos.'}],
         'options': ['El derecho evoluciona cuando la realidad lo desborda. La pregunta no es si el río es persona, sino si merece protección jurídica.', 'Tiene razón el juez.', 'No sé de leyes.'], 'answer': 'El derecho evoluciona cuando la realidad lo desborda. La pregunta no es si el río es persona, sino si merece protección jurídica.', 'explanation': 'La respuesta reenmarca la cuestión ontológica como cuestión pragmática de protección.'},
        {'scene': [{'speaker': 'Yaguará', 'text': 'Al principio del viaje busqué palabras. Al final, las palabras me encontraron.'}, {'speaker': 'Candelaria', 'text': '¿Y ahora?'}],
         'options': ['Ahora sé que aprender una lengua no es acumular palabras sino aprender a habitar el silencio entre ellas.', 'Ahora descanso.', 'No sé.'], 'answer': 'Ahora sé que aprender una lengua no es acumular palabras sino aprender a habitar el silencio entre ellas.', 'explanation': 'El cierre del viaje: la competencia última es la comprensión del silencio como componente del lenguaje.'},
    ],
}

# -- RAIZ: etymology + Babel Tree --
RAIZ_DATA = {
    'B2': [
        {'word': 'agua', 'etymology': 'Del latín "aqua". Una de las palabras más antiguas del español, casi sin cambio desde Roma.', 'relatedWords': ['acuático', 'acuarela', 'acueducto', 'aguacero'], 'distractors': ['agudo', 'agosto', 'águila'], 'cognates': [{'lang': 'portugués', 'word': 'água'}, {'lang': 'italiano', 'word': 'acqua'}, {'lang': 'francés', 'word': 'eau'}]},
        {'word': 'tierra', 'etymology': 'Del latín "terra". Nombra tanto el suelo como el planeta. La raíz aparece en muchas lenguas romances.', 'relatedWords': ['terreno', 'territorio', 'enterrar', 'terremoto'], 'distractors': ['tierno', 'tiempo', 'tigre'], 'cognates': [{'lang': 'portugués', 'word': 'terra'}, {'lang': 'italiano', 'word': 'terra'}, {'lang': 'francés', 'word': 'terre'}]},
        {'word': 'camino', 'etymology': 'Del latín vulgar "camminus", posiblemente de origen celta. Los celtas lo usaban para las rutas de comercio.', 'relatedWords': ['caminar', 'caminante', 'encaminar', 'caminata'], 'distractors': ['camisa', 'cama', 'cambio'], 'cognates': [{'lang': 'francés', 'word': 'chemin'}, {'lang': 'portugués', 'word': 'caminho'}, {'lang': 'catalán', 'word': 'camí'}]},
        {'word': 'nombre', 'etymology': 'Del latín "nomen". Nombrar es el primer acto del lenguaje: dar identidad a las cosas.', 'relatedWords': ['nombrar', 'renombre', 'pronombre', 'nomenclatura'], 'distractors': ['hombre', 'hambre', 'sombra'], 'cognates': [{'lang': 'inglés', 'word': 'name'}, {'lang': 'portugués', 'word': 'nome'}, {'lang': 'italiano', 'word': 'nome'}]},
        {'word': 'selva', 'etymology': 'Del latín "silva" (bosque). En español evolucionó a "selva" y se especializó para bosques tropicales densos.', 'relatedWords': ['silvestre', 'selvático', 'selvicultura'], 'distractors': ['salvar', 'servir', 'sembrar'], 'cognates': [{'lang': 'portugués', 'word': 'selva'}, {'lang': 'italiano', 'word': 'selva'}, {'lang': 'inglés', 'word': 'sylvan'}]},
        {'word': 'escuchar', 'etymology': 'Del latín "auscultare" (prestar oído). La palabra misma contiene la idea de esfuerzo: escuchar no es lo mismo que oír.', 'relatedWords': ['auscultar', 'escucha', 'escuchador'], 'distractors': ['escribir', 'escapar', 'escoger'], 'cognates': [{'lang': 'portugués', 'word': 'escutar'}, {'lang': 'italiano', 'word': 'ascoltare'}, {'lang': 'francés', 'word': 'écouter'}]},
    ],
    'C1': [
        {'word': 'álgebra', 'etymology': 'Del árabe "al-jabr" (restauración). Los árabes trajeron a España la matemática que Europa había olvidado.', 'relatedWords': ['algebraico', 'algoritmo'], 'distractors': ['alergia', 'albergue', 'alfombra'], 'cognates': [{'lang': 'inglés', 'word': 'algebra'}, {'lang': 'francés', 'word': 'algèbre'}, {'lang': 'árabe', 'word': 'الجبر'}]},
        {'word': 'ojalá', 'etymology': "Del árabe 'law šā Allāh' (si Dios quiere). Una de las palabras más hermosas del español, herencia de ocho siglos de convivencia.", 'relatedWords': [], 'distractors': ['ojo', 'oreja', 'ola'], 'cognates': [{'lang': 'árabe', 'word': 'إن شاء الله'}, {'lang': 'portugués', 'word': 'oxalá'}]},
        {'word': 'jaguar', 'etymology': 'Del tupí-guaraní "yaguara" (fiera). Viajó del Amazonas al español y de ahí al mundo entero.', 'relatedWords': ['yaguareté', 'yaguarundí'], 'distractors': ['jarra', 'jaula', 'jardín'], 'cognates': [{'lang': 'inglés', 'word': 'jaguar'}, {'lang': 'portugués', 'word': 'jaguar'}, {'lang': 'guaraní', 'word': 'jaguarete'}]},
        {'word': 'chocolate', 'etymology': 'Del náhuatl "xocolātl" (agua amarga). Los aztecas lo bebían con chile. España le añadió azúcar y lo llevó a Europa.', 'relatedWords': ['chocolatería', 'chocolatero'], 'distractors': ['chaleco', 'charco', 'chaqueta'], 'cognates': [{'lang': 'inglés', 'word': 'chocolate'}, {'lang': 'francés', 'word': 'chocolat'}, {'lang': 'náhuatl', 'word': 'xocolātl'}]},
        {'word': 'huracán', 'etymology': 'Del taíno "jurakán" (dios de la tormenta). Los pueblos del Caribe nombraron lo que Europa no conocía.', 'relatedWords': ['huracanado'], 'distractors': ['hormiga', 'humano', 'húmedo'], 'cognates': [{'lang': 'inglés', 'word': 'hurricane'}, {'lang': 'francés', 'word': 'ouragan'}, {'lang': 'taíno', 'word': 'jurakán'}]},
        {'word': 'canoa', 'etymology': 'Del taíno "canoa". Primera palabra americana que entró al español. Colón la anotó en su diario en 1492.', 'relatedWords': ['canoero', 'piragua'], 'distractors': ['canción', 'canasta', 'canal'], 'cognates': [{'lang': 'inglés', 'word': 'canoe'}, {'lang': 'francés', 'word': 'canoë'}, {'lang': 'portugués', 'word': 'canoa'}]},
    ],
    'C2': [
        {'word': 'nostalgia', 'etymology': 'Del griego "nostos" (regreso) + "algos" (dolor). Literalmente, el dolor del regreso imposible. Un médico suizo la acuñó en 1688 para describir la enfermedad de los soldados lejos de casa.', 'relatedWords': ['nostálgico', 'neuralgia', 'analgésico'], 'distractors': ['noticia', 'novela', 'norma'], 'cognates': [{'lang': 'inglés', 'word': 'nostalgia'}, {'lang': 'griego', 'word': 'νοσταλγία'}, {'lang': 'alemán', 'word': 'Nostalgie'}]},
        {'word': 'democracia', 'etymology': 'Del griego "demos" (pueblo) + "kratos" (poder). El poder del pueblo: una idea que viaja veinticinco siglos sin llegar nunca del todo.', 'relatedWords': ['demócrata', 'demografía', 'endemia', 'autocracia'], 'distractors': ['demoler', 'demonio', 'demorar'], 'cognates': [{'lang': 'inglés', 'word': 'democracy'}, {'lang': 'francés', 'word': 'démocratie'}, {'lang': 'griego', 'word': 'δημοκρατία'}]},
        {'word': 'saudade', 'etymology': 'Del portugués, posiblemente del latín "solitas" (soledad). Designa una melancolía dulce por lo ausente. El español no tiene equivalente exacto; "añoranza" se acerca pero no llega.', 'relatedWords': ['soledad', 'añoranza', 'morriña'], 'distractors': ['salud', 'saldo', 'saludo'], 'cognates': [{'lang': 'portugués', 'word': 'saudade'}, {'lang': 'gallego', 'word': 'saudade'}, {'lang': 'español', 'word': 'soledades'}]},
        {'word': 'avatar', 'etymology': 'Del sánscrito "avatāra" (descenso de un dios). Viajó del hinduismo al francés y de ahí al español. Hoy nombra identidades digitales, pero su origen es sagrado.', 'relatedWords': ['encarnación', 'metamorfosis'], 'distractors': ['avanzar', 'avalancha', 'avaricia'], 'cognates': [{'lang': 'inglés', 'word': 'avatar'}, {'lang': 'francés', 'word': 'avatar'}, {'lang': 'sánscrito', 'word': 'अवतार'}]},
        {'word': 'quetzal', 'etymology': 'Del náhuatl "quetzalli" (pluma preciosa). Ave sagrada de Mesoamérica cuyas plumas valían más que el oro. Hoy nombra la moneda de Guatemala y un ave casi extinta.', 'relatedWords': ['Quetzalcóatl'], 'distractors': ['queso', 'queja', 'quieto'], 'cognates': [{'lang': 'inglés', 'word': 'quetzal'}, {'lang': 'náhuatl', 'word': 'quetzalli'}]},
        {'word': 'ubuntu', 'etymology': 'Del zulú/xhosa "ubuntu" (humanidad hacia otros). No es español, pero nombra una idea que el español necesita: "yo soy porque nosotros somos."', 'relatedWords': ['comunidad', 'solidaridad', 'reciprocidad'], 'distractors': ['ubicar', 'último', 'único'], 'cognates': [{'lang': 'zulú', 'word': 'ubuntu'}, {'lang': 'xhosa', 'word': 'ubuntu'}]},
    ],
}

# -- CODICE: contextual inferencing --
CODICE_DATA = {
    'B2': [
        {'passage': 'El viejo pescador lanzó su atarraya sobre el remanso. El agua estaba turbia por las lluvias, pero él conocía los movimientos de los peces. Con un gesto certero, recogió la red llena de bocachico plateado.',
         'highlights': [{'word': 'atarraya', 'options': ['Un tipo de red circular para pescar', 'Un barco pequeño', 'Una caña de pescar'], 'answer': 'Un tipo de red circular para pescar'},
                        {'word': 'remanso', 'options': ['Zona de agua tranquila en un río', 'Una cascada', 'La orilla del mar'], 'answer': 'Zona de agua tranquila en un río'}]},
        {'passage': 'Candelaria encontró una mata de bore junto al sendero. Cortó las hojas más grandes para usarlas como sombrilla improvisada. La lluvia resbalaba por la superficie cerosa sin mojar a nadie.',
         'highlights': [{'word': 'bore', 'options': ['Una planta de hojas grandes', 'Un tipo de piedra', 'Un animal pequeño'], 'answer': 'Una planta de hojas grandes'},
                        {'word': 'cerosa', 'options': ['Con textura similar a la cera', 'De color oscuro', 'Muy delgada'], 'answer': 'Con textura similar a la cera'}]},
        {'passage': 'El chamán preparó el ambil frotando hojas de tabaco con sal vegetal hasta obtener una pasta negra y espesa. La aplicó sobre su lengua antes de comenzar el canto ceremonial.',
         'highlights': [{'word': 'ambil', 'options': ['Pasta concentrada de tabaco ritual', 'Un instrumento musical', 'Un tipo de comida'], 'answer': 'Pasta concentrada de tabaco ritual'},
                        {'word': 'sal vegetal', 'options': ['Ceniza de plantas usada como sal', 'Sal marina', 'Azúcar de caña'], 'answer': 'Ceniza de plantas usada como sal'}]},
        {'passage': 'La chalupa avanzaba despacio por el caño, esquivando troncos sumergidos. El boga manejaba la palanca con destreza, leyendo las corrientes como quien lee un mapa.',
         'highlights': [{'word': 'chalupa', 'options': ['Embarcación alargada de río', 'Un puente', 'Un tipo de animal acuático'], 'answer': 'Embarcación alargada de río'},
                        {'word': 'boga', 'options': ['Persona que maneja la embarcación', 'Un pez grande', 'Una herramienta de pesca'], 'answer': 'Persona que maneja la embarcación'}]},
        {'passage': 'Al atardecer, las chicharras comenzaron su estridente coro. El sonido era tan intenso que parecía vibrar dentro del cráneo. Yaguará apenas notó el estruendo; para ella era la voz habitual de la selva.',
         'highlights': [{'word': 'chicharras', 'options': ['Insectos que producen un sonido fuerte y agudo', 'Pájaros nocturnos', 'Ranas del río'], 'answer': 'Insectos que producen un sonido fuerte y agudo'},
                        {'word': 'estridente', 'options': ['Muy agudo y desagradable al oído', 'Suave y melodioso', 'Grave y profundo'], 'answer': 'Muy agudo y desagradable al oído'}]},
        {'passage': 'Don Próspero desplegó el plano sobre la mesa y señaló con el dedo la trocha que sus obreros habían abierto. La línea roja atravesaba la manigua como una cicatriz reciente.',
         'highlights': [{'word': 'trocha', 'options': ['Camino estrecho abierto en la vegetación', 'Un río pequeño', 'Una montaña baja'], 'answer': 'Camino estrecho abierto en la vegetación'},
                        {'word': 'manigua', 'options': ['Vegetación tropical densa y enmarañada', 'Un tipo de tierra', 'Una roca grande'], 'answer': 'Vegetación tropical densa y enmarañada'}]},
    ],
    'C1': [
        {'passage': 'La trashumancia del ganado por los páramos de Boyacá obedece a ritmos estacionales que los campesinos han memorizado durante generaciones. Cuando el frailejón florece, es señal de que los pastos altos están listos.',
         'highlights': [{'word': 'trashumancia', 'options': ['Migración estacional del ganado entre pastos', 'Un tipo de agricultura', 'Una ceremonia religiosa'], 'answer': 'Migración estacional del ganado entre pastos'},
                        {'word': 'frailejón', 'options': ['Planta típica de los páramos andinos', 'Un tipo de fraile o monje', 'Un animal de montaña'], 'answer': 'Planta típica de los páramos andinos'}]},
        {'passage': 'La sentencia de la Corte estableció que el principio de precaución prevalece sobre el interés económico cuando existe riesgo de daño irreversible. Este fallo sentó un precedente vinculante para todos los tribunales inferiores.',
         'highlights': [{'word': 'principio de precaución', 'options': ['Norma que obliga a prevenir daños aunque no haya certeza total', 'Una ley de comercio', 'Un código de conducta personal'], 'answer': 'Norma que obliga a prevenir daños aunque no haya certeza total'},
                        {'word': 'vinculante', 'options': ['Que obliga legalmente a cumplirse', 'Opcional', 'Informativo'], 'answer': 'Que obliga legalmente a cumplirse'}]},
        {'passage': 'El palafito se alzaba sobre el agua como un pájaro de madera posado en zancos. Debajo, entre los pilotes, las canoas dormían amarradas. La brisa del Pacífico traía olor a manglar y salitre.',
         'highlights': [{'word': 'palafito', 'options': ['Vivienda construida sobre pilotes en el agua', 'Un tipo de bote', 'Una torre de vigilancia'], 'answer': 'Vivienda construida sobre pilotes en el agua'},
                        {'word': 'salitre', 'options': ['Sustancia salina que deja el mar', 'Un tipo de arena', 'Un pez pequeño'], 'answer': 'Sustancia salina que deja el mar'}]},
        {'passage': 'La polifonía del vallenato tradicional integra la caja, la guacharaca y el acordeón en un tejido sonoro que refleja la confluencia de tradiciones africanas, indígenas y europeas en el Caribe colombiano.',
         'highlights': [{'word': 'guacharaca', 'options': ['Instrumento de percusión hecho de caña con ranuras', 'Un tipo de flauta', 'Un tambor grande'], 'answer': 'Instrumento de percusión hecho de caña con ranuras'},
                        {'word': 'polifonía', 'options': ['Combinación de varias voces o sonidos simultáneos', 'Un solo instrumento tocando', 'El silencio entre notas'], 'answer': 'Combinación de varias voces o sonidos simultáneos'}]},
        {'passage': 'La epifanía no llegó como un rayo sino como el amanecer: gradual, inevitable, transformadora. Yaguará comprendió que el viaje nunca había sido hacia un lugar sino hacia una forma de escuchar.',
         'highlights': [{'word': 'epifanía', 'options': ['Momento de comprensión súbita y profunda', 'Un tipo de planta', 'Una fiesta religiosa'], 'answer': 'Momento de comprensión súbita y profunda'}]},
        {'passage': 'El socavón dejado por la minería había convertido el valle fértil en un erial yermo. Donde antes crecía la yuca, ahora solo quedaba arcilla expuesta al sol implacable.',
         'highlights': [{'word': 'socavón', 'options': ['Excavación grande dejada por la minería', 'Un tipo de cueva natural', 'Un río subterráneo'], 'answer': 'Excavación grande dejada por la minería'},
                        {'word': 'erial', 'options': ['Terreno sin cultivar, estéril', 'Un jardín pequeño', 'Un tipo de bosque'], 'answer': 'Terreno sin cultivar, estéril'}]},
    ],
    'C2': [
        {'passage': 'La aporía fundamental del colonialismo lingüístico reside en que el instrumento de dominación es también el medio de liberación. Las literaturas poscoloniales se escriben en las lenguas del colonizador, subvirtiéndolas desde dentro.',
         'highlights': [{'word': 'aporía', 'options': ['Contradicción lógica sin solución aparente', 'Un tipo de argumento', 'Una forma de gobierno'], 'answer': 'Contradicción lógica sin solución aparente'},
                        {'word': 'subvirtiéndolas', 'options': ['Transformándolas desde dentro para alterar su sentido original', 'Destruyéndolas completamente', 'Traduciéndolas fielmente'], 'answer': 'Transformándolas desde dentro para alterar su sentido original'}]},
        {'passage': 'El palimpsesto territorial que constituye la Colombia contemporánea revela, bajo cada capa de nomenclatura oficial, los topónimos indígenas que persisten como cicatrices fonéticas del despojo.',
         'highlights': [{'word': 'palimpsesto', 'options': ['Manuscrito antiguo escrito sobre otro borrado, metáfora de capas superpuestas', 'Un tipo de mapa', 'Un documento oficial'], 'answer': 'Manuscrito antiguo escrito sobre otro borrado, metáfora de capas superpuestas'},
                        {'word': 'topónimos', 'options': ['Nombres propios de lugares geográficos', 'Nombres de personas', 'Tipos de terreno'], 'answer': 'Nombres propios de lugares geográficos'}]},
        {'passage': 'La heteroglosia bajtiniana encuentra en la selva amazónica su correlato ecológico: cada especie emite una voz que solo adquiere sentido en la polifonía del conjunto. Silenciar una es empobrecer todas.',
         'highlights': [{'word': 'heteroglosia', 'options': ['Coexistencia de múltiples voces y registros en un discurso', 'Un tipo de escritura', 'Una enfermedad de la voz'], 'answer': 'Coexistencia de múltiples voces y registros en un discurso'},
                        {'word': 'correlato', 'options': ['Equivalente o correspondencia en otro ámbito', 'Una contradicción', 'Un resultado numérico'], 'answer': 'Equivalente o correspondencia en otro ámbito'}]},
        {'passage': 'La praxis decolonial exige no solo la deconstrucción de las narrativas hegemónicas sino la articulación de epistemologías otras que operen desde lógicas no binarias ni jerárquicas.',
         'highlights': [{'word': 'praxis', 'options': ['Acción informada por la reflexión teórica', 'Una teoría abstracta', 'Un tipo de texto'], 'answer': 'Acción informada por la reflexión teórica'},
                        {'word': 'epistemologías', 'options': ['Formas de producir y validar conocimiento', 'Tipos de religión', 'Formas de gobierno'], 'answer': 'Formas de producir y validar conocimiento'}]},
        {'passage': 'Lo inefable no es lo que carece de palabras sino lo que las excede. Abuela Ceiba no habla porque su comunicación opera en un registro anterior al logos: el de la vibración, la savia, el crecimiento lento.',
         'highlights': [{'word': 'inefable', 'options': ['Que no puede expresarse con palabras', 'Que es muy fácil de decir', 'Que es falso'], 'answer': 'Que no puede expresarse con palabras'},
                        {'word': 'logos', 'options': ['Palabra, razón o discurso en la tradición filosófica griega', 'Un tipo de símbolo gráfico', 'Una marca comercial'], 'answer': 'Palabra, razón o discurso en la tradición filosófica griega'}]},
        {'passage': 'La taxonomía linneana clasifica al jaguar como Panthera onca; la cosmovisión tikuna lo nombra guardián del umbral entre mundos. Ambas son descripciones verdaderas; ninguna es completa.',
         'highlights': [{'word': 'taxonomía', 'options': ['Sistema de clasificación científica de los seres vivos', 'Un tipo de impuesto', 'Una forma de transporte'], 'answer': 'Sistema de clasificación científica de los seres vivos'},
                        {'word': 'umbral', 'options': ['Límite o frontera entre dos espacios o estados', 'Un tipo de columna', 'Una parte del techo'], 'answer': 'Límite o frontera entre dos espacios o estados'}]},
    ],
}

# -- TEJIDO: discourse weaving --
TEJIDO_DATA = {
    'B2': [
        {'mode': 'order', 'fragmentA': 'La deforestación avanzó durante años sin control.', 'fragmentB': 'Las comunidades decidieron organizarse para proteger el bosque.',
         'items': ['Primero talaron los árboles cerca del río.', 'Después la erosión destruyó los cultivos.', 'Entonces los pescadores perdieron su sustento.', 'Finalmente el pueblo entero se vio afectado.'], 'answer': 'Primero talaron los árboles cerca del río. Después la erosión destruyó los cultivos. Entonces los pescadores perdieron su sustento. Finalmente el pueblo entero se vio afectado.'},
        {'mode': 'order', 'fragmentA': 'Candelaria encontró el cuaderno de su abuela.', 'fragmentB': 'Decidió seguir los mapas hasta encontrar al jaguar.',
         'items': ['Abrió la primera página y vio un mapa.', 'Reconoció el río que pasaba por su pueblo.', 'Siguió las líneas hasta una marca en forma de huella.', 'Comprendió que su abuela también había buscado a Yaguará.'], 'answer': 'Abrió la primera página y vio un mapa. Reconoció el río que pasaba por su pueblo. Siguió las líneas hasta una marca en forma de huella. Comprendió que su abuela también había buscado a Yaguará.'},
        {'mode': 'connector', 'fragmentA': 'Don Próspero prometió empleo para todos.', 'fragmentB': 'Solo contrató a trabajadores de fuera.',
         'connectors': ['Sin embargo', 'Además', 'Por eso', 'Es decir'], 'answer': 'Sin embargo'},
        {'mode': 'connector', 'fragmentA': 'La selva produce el oxígeno que respiramos.', 'fragmentB': 'Debemos protegerla.',
         'connectors': ['Por lo tanto', 'Sin embargo', 'En cambio', 'Aunque'], 'answer': 'Por lo tanto'},
        {'mode': 'connector', 'fragmentA': 'Los jaguares necesitan grandes territorios para sobrevivir.', 'fragmentB': 'La fragmentación del bosque los está aislando.',
         'connectors': ['No obstante', 'Además', 'En consecuencia', 'Por ejemplo'], 'answer': 'No obstante'},
        {'mode': 'connector', 'fragmentA': 'El mercurio contamina los ríos durante décadas.', 'fragmentB': 'Los efectos de la minería no son inmediatos sino acumulativos.',
         'connectors': ['Es decir', 'Sin embargo', 'Aunque', 'En cambio'], 'answer': 'Es decir'},
    ],
    'C1': [
        {'mode': 'connector', 'fragmentA': 'La justicia transicional busca la reconciliación.', 'fragmentB': 'Las víctimas sienten que sus derechos no han sido reparados.',
         'connectors': ['No obstante', 'En consecuencia', 'Asimismo', 'Es decir'], 'answer': 'No obstante'},
        {'mode': 'connector', 'fragmentA': 'La constitución reconoce la diversidad cultural.', 'fragmentB': 'Las políticas educativas siguen siendo monolingües en la práctica.',
         'connectors': ['A pesar de ello', 'Por consiguiente', 'Además', 'Por ejemplo'], 'answer': 'A pesar de ello'},
        {'mode': 'bridge', 'fragmentA': 'García Márquez escribió que Macondo era un espejo de la realidad latinoamericana.', 'fragmentB': 'La selva de Yaguará también refleja verdades que la lógica convencional no alcanza.',
         'bridgeKeywords': ['realismo mágico', 'narrativa', 'verdad', 'ficción'], 'answer': 'Ambos espacios ficticios operan como lentes que magnifican lo que la mirada cotidiana pasa por alto.'},
        {'mode': 'bridge', 'fragmentA': 'Los pueblos indígenas han conservado la biodiversidad durante milenios.', 'fragmentB': 'La ciencia moderna apenas empieza a documentar ese conocimiento.',
         'bridgeKeywords': ['conocimiento', 'tradición', 'ciencia', 'tiempo'], 'answer': 'Lo que para la ciencia es descubrimiento reciente, para los pueblos originarios es sabiduría heredada.'},
        {'mode': 'bridge', 'fragmentA': 'El río Atrato fue declarado sujeto de derechos por la Corte Constitucional.', 'fragmentB': 'La cosmovisión embera siempre lo consideró un ser vivo.',
         'bridgeKeywords': ['derecho', 'cosmovisión', 'reconocimiento', 'naturaleza'], 'answer': 'El fallo judicial formalizó lo que el saber ancestral afirmaba desde siempre.'},
        {'mode': 'bridge', 'fragmentA': 'Don Próspero mide el valor del bosque en metros cúbicos de madera.', 'fragmentB': 'Doña Asunción lo mide en generaciones de memoria.',
         'bridgeKeywords': ['valor', 'medida', 'economía', 'memoria'], 'answer': 'Dos sistemas de valor inconmensurables coexisten en el mismo territorio.'},
    ],
    'C2': [
        {'mode': 'bridge', 'fragmentA': 'La escritura fija el pensamiento pero lo congela.', 'fragmentB': 'La oralidad lo mantiene vivo pero lo expone al olvido.',
         'bridgeKeywords': ['escritura', 'oralidad', 'memoria', 'transformación'], 'answer': 'Entre la permanencia inerte de lo escrito y la fragilidad vital de lo hablado, cada cultura negocia su forma de recordar.'},
        {'mode': 'bridge', 'fragmentA': 'Aprender una lengua es adquirir un nuevo sistema de signos.', 'fragmentB': 'Es también habitar una nueva forma de estar en el mundo.',
         'bridgeKeywords': ['lengua', 'identidad', 'transformación', 'habitar'], 'answer': 'La competencia lingüística no se agota en el dominio del código: implica una reconfiguración de la subjetividad.'},
        {'mode': 'bridge', 'fragmentA': 'La Sombra le dijo a Yaguará que las palabras son trampas.', 'fragmentB': 'Abuela Ceiba le dijo que las palabras son semillas.',
         'bridgeKeywords': ['palabra', 'trampa', 'semilla', 'verdad'], 'answer': 'Ambas tienen razón: la palabra que aprisiona y la que libera son la misma, vista desde orillas distintas.'},
        {'mode': 'bridge', 'fragmentA': 'El monolingüismo fue históricamente un instrumento de control.', 'fragmentB': 'El plurilingüismo puede ser un acto de resistencia.',
         'bridgeKeywords': ['poder', 'lengua', 'resistencia', 'diversidad'], 'answer': 'Donde el poder impuso una sola lengua, la supervivencia de las demás constituye una forma de desobediencia epistémica.'},
        {'mode': 'bridge', 'fragmentA': 'Al comenzar el viaje, Yaguará buscaba la palabra correcta.', 'fragmentB': 'Al terminarlo, comprendió que la escucha es anterior a la palabra.',
         'bridgeKeywords': ['viaje', 'palabra', 'escucha', 'silencio'], 'answer': 'El arco del aprendizaje va de la ansiedad por producir a la serenidad de recibir.'},
        {'mode': 'bridge', 'fragmentA': 'Cada rama del Árbol de Babel es una lengua.', 'fragmentB': 'El tronco es lo que todas comparten sin poder nombrarlo.',
         'bridgeKeywords': ['Babel', 'universalidad', 'inefable', 'raíz'], 'answer': 'La diversidad lingüística no fragmenta una unidad originaria sino que despliega las infinitas facetas de una experiencia compartida.'},
    ],
}


# ── GENERATORS ─────────────────────────────────────────────────────

def gen_susurro(n, meta, cefr):
    audio = pick(SUSURRO_AUDIO, cefr, n)
    if audio is None:
        return None
    return {'type': 'susurro', 'label': 'Susurro', 'audio': audio,
            'image': f'img/destinations/dest{n}-susurro.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_sombra(n, meta, cefr):
    audio = pick(SOMBRA_AUDIO, cefr, n)
    if audio is None:
        return None
    return {'type': 'sombra', 'label': 'Sombra', 'audio': audio,
            'image': f'img/destinations/dest{n}-sombra.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_cartografo(n, meta, cefr):
    item = pick(CARTOGRAFO_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'cartografo', 'label': 'Cartógrafo', **item,
            'image': f'img/destinations/dest{n}-cartografo.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_eco_lejano(n, meta, cefr):
    item = pick(ECO_LEJANO_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'eco_lejano', 'label': 'Eco lejano', **item,
            'image': f'img/destinations/dest{n}-eco_lejano.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_tertulia(n, meta, cefr):
    item = pick(TERTULIA_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'tertulia', 'label': 'Tertulia', **item,
            'image': f'img/destinations/dest{n}-tertulia.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_pregonero(n, meta, cefr):
    item = pick(PREGONERO_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'pregonero', 'label': 'Pregonero', **item,
            'image': f'img/destinations/dest{n}-pregonero.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_oraculo(n, meta, cefr):
    item = pick(ORACULO_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'oraculo', 'label': 'Oráculo', **item,
            'image': f'img/destinations/dest{n}-oraculo.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_raiz(n, meta, cefr):
    item = pick(RAIZ_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'raiz', 'label': 'Raíz', **item,
            'image': f'img/destinations/dest{n}-raiz.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_codice(n, meta, cefr):
    item = pick(CODICE_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'codice', 'label': 'Códice', **item,
            'image': f'img/destinations/dest{n}-codice.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


def gen_tejido(n, meta, cefr):
    item = pick(TEJIDO_DATA, cefr, n)
    if item is None:
        return None
    return {'type': 'tejido', 'label': 'Tejido', **item,
            'image': f'img/destinations/dest{n}-tejido.jpg',
            'imageAlt': f'Escena del viaje de Yaguará — destino {n}'}


# ── REGISTRY ───────────────────────────────────────────────────────

GENERATORS = {
    'susurro':    gen_susurro,
    'sombra':     gen_sombra,
    'cartografo': gen_cartografo,
    'eco_lejano': gen_eco_lejano,
    'tertulia':   gen_tertulia,
    'pregonero':  gen_pregonero,
    'oraculo':    gen_oraculo,
    'raiz':       gen_raiz,
    'codice':     gen_codice,
    'tejido':     gen_tejido,
}


# ── MAIN ───────────────────────────────────────────────────────────

def main():
    dry_run = '--dry-run' in sys.argv
    random.seed(42)

    total_added = 0
    total_swapped = 0
    type_counts = {t: 0 for t in GENERATORS}
    skipped_no_room = 0

    for dest_n in range(1, 59):
        data = load_dest(dest_n)
        if data is None:
            continue
        cefr = data.get('meta', {}).get('cefr', 'A1')
        meta = data.get('meta', {})
        dest_added = 0

        for game_type, gen_fn in GENERATORS.items():
            if cefr not in TYPE_LEVELS.get(game_type, []):
                continue
            if already_has_type(data, game_type):
                continue

            game = gen_fn(dest_n, meta, cefr)
            if game is None:
                continue

            if has_room(data):
                data['games'].append(game)
                total_added += 1
                dest_added += 1
                type_counts[game_type] += 1
                action = 'Added' if not dry_run else '[DRY RUN] Would add'
                print(f'  {action} {game_type} to dest{dest_n} ({cefr})')
            else:
                victim_idx = find_swap_victim(data)
                if victim_idx is not None:
                    old_type = data['games'][victim_idx]['type']
                    if not dry_run:
                        data['games'][victim_idx] = game
                    total_swapped += 1
                    dest_added += 1
                    type_counts[game_type] += 1
                    action = 'Swapped' if not dry_run else '[DRY RUN] Would swap'
                    print(f'  {action} {old_type}→{game_type} in dest{dest_n} ({cefr})')
                else:
                    skipped_no_room += 1

        if not dry_run and dest_added > 0:
            save_dest(dest_n, data)

    # Summary
    prefix = '[DRY RUN] ' if dry_run else ''
    print(f'\n{prefix}Summary:')
    print(f'  Added:   {total_added}')
    print(f'  Swapped: {total_swapped}')
    print(f'  Skipped (no room): {skipped_no_room}')
    print(f'  Total new games: {total_added + total_swapped}')
    print(f'\n  Per type:')
    for t, c in sorted(type_counts.items(), key=lambda x: -x[1]):
        if c > 0:
            print(f'    {t}: {c}')

    # Verify no destination exceeds MAX
    if not dry_run:
        for dest_n in range(1, 59):
            data = load_dest(dest_n)
            if data and len(data.get('games', [])) > MAX_GAMES_PER_DEST:
                print(f'  WARNING: dest{dest_n} has {len(data["games"])} games (over {MAX_GAMES_PER_DEST})')


if __name__ == '__main__':
    main()
