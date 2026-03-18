#!/usr/bin/env python3
"""
Add image + imageAlt fields to every game encounter across all 58 destinations.
Skips encounters that already have an image field.

Naming convention: img/destinations/dest{N}-{type}[-{n}].jpg
  - Single instance of a type: dest5-pair.jpg
  - Multiple instances: dest5-fill-1.jpg, dest5-fill-2.jpg

imageAlt text is world-appropriate (selva / montaña / cosmos) and game-type-specific.
"""

import json, os, sys
from collections import Counter

BASE = '/home/babelfree.com/public_html/content/'

# ── World assignment ──────────────────────────────────────────────
def get_world(dest_num):
    if dest_num <= 18: return 'abajo'
    if dest_num <= 38: return 'medio'
    return 'arriba'

# ── imageAlt pools ────────────────────────────────────────────────
# 4 options per type × world. Cycled via modular index.

ALTS = {
    'abajo': {
        'fill': [
            "Hojas de la selva con espacios vacíos entre las venas, esperando ser completadas",
            "Frutos del bosque dispuestos en fila, uno falta en la secuencia",
            "Piedras del río con palabras grabadas, una piedra vacía espera su inscripción",
            "Musgo sobre corteza de árbol con huecos en forma de letras",
        ],
        'listening': [
            "Yaguará con la oreja levantada, ondas de sonido doradas entre los árboles",
            "El río murmurando entre las piedras, círculos de agua como notas musicales",
            "Pájaros en la copa del árbol, cada canto deja una estela de color",
            "Ranas cantando sobre hojas de loto en la orilla del río al anochecer",
        ],
        'pair': [
            "Dos huellas de jaguar conectadas por un hilo dorado en la tierra húmeda",
            "Hojas caídas sobre el agua, cada una reflejando su pareja en la superficie",
            "Luciérnagas emparejadas brillando entre las raíces de la ceiba",
            "Mariposas morpho posadas sobre flores gemelas a orillas del río",
        ],
        'builder': [
            "Piedras del río con palabras talladas, dispuestas en desorden sobre la orilla",
            "Ramas caídas formando un camino por armar sobre el barro del sendero",
            "Semillas esparcidas sobre una hoja gigante que forman una frase al alinearse",
            "Fragmentos de corteza con marcas de jaguar esperando ser ordenados",
        ],
        'conversation': [
            "Yaguará y el viajero junto a una fogata en la selva, diálogo flotando entre ellos",
            "Dos figuras sentadas en la ribera del río, el agua lleva palabras doradas",
            "Yaguará hablando bajo la copa de un árbol enorme, luciérnagas iluminando",
            "Encuentro en un claro del bosque, humo de fogata formando palabras",
        ],
        'category': [
            "Cestas tejidas al estilo wayúu bajo la copa de un árbol, cada una etiquetada",
            "Nidos de pájaros en diferentes ramas, cada uno con su etiqueta natural",
            "Caminos del bosque que se bifurcan, cada sendero lleva un nombre de musgo",
            "Conchas de río separadas en montoncitos sobre la arena, patrones distintos",
        ],
        'dictation': [
            "Una hoja grande de plátano como pergamino extendida junto al río",
            "Yaguará escribiendo con la pata en la arena húmeda de la orilla",
            "Gotas de rocío formando letras sobre una hoja gigante de la selva",
            "Palo trazando palabras en la tierra mojada junto a huellas de jaguar",
        ],
        'escaperoom': [
            "Entrada de una cueva entre raíces de ceiba, símbolos dorados brillando",
            "Puerta oculta en un tronco enorme, cerradura de letras resplandecientes",
            "Caverna con estalactitas brillantes, enigmas tallados en las paredes",
            "Cámara subterránea bajo la ceiba, petroglifos iluminados por luciérnagas",
        ],
        'cronica': [
            "Un códice abierto sobre una piedra musgosa, pluma de guacamaya al lado",
            "Corteza de árbol con marcas de jaguar que parecen escritura antigua",
            "Hoja de palma enrollada como pergamino, tinta de barro fresco al lado",
            "Diario de viajero atado con liana, abierto sobre una raíz de ceiba",
        ],
        'ritmo': [
            "Gotas de lluvia creando círculos rítmicos en un charco de la selva",
            "Yaguará golpeando un tronco hueco, ondas de ritmo visibles en el aire",
            "Ranas sobre hojas de loto, cada una en una nota musical diferente",
            "Lluvia sobre hojas de diferentes tamaños creando una melodía natural",
        ],
        'conjugation': [
            "Raíces de ceiba que se ramifican en diferentes formas bajo la tierra",
            "Un río que se divide en arroyos, cada corriente con un reflejo diferente",
            "Semillas brotando en diferentes etapas de crecimiento junto al sendero",
            "Huellas de jaguar transformándose gradualmente sobre el barro húmedo",
        ],
        'narrative': [
            "Claro del bosque al amanecer, luz dorada filtrándose entre la niebla",
            "Yaguará caminando por un sendero luminoso en la selva profunda",
            "El río serpenteando entre árboles enormes, reflejos dorados en el agua",
            "Vista desde la copa de la ceiba, la selva extendiéndose hasta el horizonte",
        ],
        'grammar': [
            "Yaguará junto al río, huellas de jaguar en la tierra formando patrones",
            "Raíces de ceiba saliendo de la tierra con formas que parecen letras",
            "Luciérnagas trazando líneas doradas entre los árboles como gramática viva",
            "Piedra del río con musgo formando patrones gramaticales naturales",
        ],
        'story': [
            "Sendero de la selva perdiéndose entre helechos gigantes y niebla baja",
            "Yaguará observando el amanecer desde una roca junto al río",
            "Claro mágico del bosque donde la luz cae en columnas doradas",
            "Orilla del río al atardecer, siluetas de árboles contra el cielo naranja",
        ],
    },
    'medio': {
        'fill': [
            "Muro de adobe con mosaicos incompletos, falta una pieza de cerámica",
            "Telar wayúu con hilos sueltos esperando ser tejidos en su patrón",
            "Escalones de piedra con inscripciones, un escalón vacío en la escalera",
            "Fachada colonial con azulejos, uno falta en el patrón de colores",
        ],
        'listening': [
            "Viento de montaña llevando voces entre los picos nevados",
            "Campanas de un pueblo andino resonando en el valle profundo",
            "Eco entre las paredes de un cañón, ondas sonoras visibles en el aire",
            "Músicos en una plaza de pueblo, notas musicales flotando hacia las montañas",
        ],
        'pair': [
            "Dos piedras talladas que encajan como piezas de un antiguo rompecabezas",
            "Hilos de colores en un telar, cada par formando un patrón diferente",
            "Puentes de cuerda conectando dos orillas de un precipicio andino",
            "Vasijas de barro emparejadas por sus diseños en un estante de alfarero",
        ],
        'builder': [
            "Bloques de piedra tallada apilados en desorden junto a un muro inca",
            "Piezas de cerámica dispersas en la mesa de un alfarero de pueblo",
            "Baldosas de barro con palabras, formando un camino empedrado",
            "Figuras de barro sin cocer esperando ser armadas sobre la mesa",
        ],
        'conversation': [
            "Personas reunidas alrededor de una fogata en un pueblo andino al anochecer",
            "Mercado de montaña con vendedores y compradores bajo toldos de colores",
            "Dos caminantes encontrándose en un cruce de senderos de montaña",
            "Tertulia en la plaza del pueblo, bancos de piedra, atardecer cálido",
        ],
        'category': [
            "Canastos de mimbre en un mercado de montaña, cada uno con su etiqueta",
            "Estantes de una tienda del pueblo con productos organizados por tipo",
            "Cajones de madera en un taller de artesano, herramientas clasificadas",
            "Cajas de especias en un mercado andino, colores y aromas separados",
        ],
        'dictation': [
            "Cuaderno abierto sobre una mesa de madera en una casa andina soleada",
            "Mano escribiendo con carbón sobre papel, montañas visibles por la ventana",
            "Pizarra de escuela en un pueblo de montaña, tiza y borrador al lado",
            "Carta sobre un escritorio de madera junto a una ventana con vista al valle",
        ],
        'escaperoom': [
            "Puerta tallada de una capilla colonial, cerradura de hierro con símbolos",
            "Laberinto de calles empedradas en un pueblo antiguo al anochecer",
            "Bodega con barriles y un cofre cerrado con candado de letras",
            "Ruinas de piedra en la montaña con una entrada secreta entre los muros",
        ],
        'cronica': [
            "Diario de viaje sobre una mesa de posada, tinta y pluma de ganso al lado",
            "Mural de pueblo contando una historia en imágenes de colores tierra",
            "Libro abierto bajo la luz de una vela, sombras danzando en la pared",
            "Cuaderno de campo sobre una manta de lana, lápiz entre las páginas",
        ],
        'ritmo': [
            "Tambores de cumbia alineados en un patio empedrado, manos listas",
            "Pasos de baile marcados en el suelo de tierra de una plaza",
            "Maracas y guacharacas cruzadas sobre una mesa de madera vieja",
            "Zapateo sobre un escenario de tablas, polvo levantándose al ritmo",
        ],
        'conjugation': [
            "Camino de montaña que se bifurca en varias sendas entre la neblina",
            "Río de montaña transformándose de arroyo a cascada a lo largo del valle",
            "Arcilla tomando diferentes formas en las manos pacientes del alfarero",
            "Telar mostrando un patrón que se transforma de simple a complejo",
        ],
        'narrative': [
            "Sendero de montaña al atardecer, pueblo visible en el valle abajo",
            "Plaza del pueblo con fuente central, personas conversando al anochecer",
            "Vista panorámica de los Andes, nubes por debajo de las cumbres",
            "Puente de piedra sobre un río de montaña, niebla levantándose",
        ],
        'grammar': [
            "Piedra tallada con inscripciones gramaticales junto a un camino andino",
            "Telar con patrones que ilustran las estructuras del idioma",
            "Muro de adobe con frases grabadas en relieve, luz del atardecer",
            "Pizarra de escuela rural con conjugaciones escritas en tiza blanca",
        ],
        'story': [
            "Sendero serpenteante entre terrazas de cultivo en la montaña verde",
            "Pueblo andino al amanecer, humo de chimeneas, gallo cantando",
            "Valle profundo con un río plateado, cóndor planeando arriba",
            "Casa de adobe con patio interior, flores y un gato dormido",
        ],
    },
    'arriba': {
        'fill': [
            "Constelación incompleta en el cielo nocturno, una estrella falta en el patrón",
            "Espejo de obsidiana con reflejos fragmentados esperando ser unidos",
            "Código de luz en el cielo, un símbolo por revelar entre las estrellas",
            "Mosaico de cristales con un hueco luminoso esperando su pieza",
        ],
        'listening': [
            "Aurora austral ondulando, cada color una frecuencia diferente",
            "Viento entre las estrellas llevando murmullos de voces ancestrales",
            "Cristales suspendidos en la oscuridad cósmica, vibrando con sonidos",
            "Cuenco tibetano resonando sobre una piedra volcánica bajo las estrellas",
        ],
        'pair': [
            "Estrellas gemelas brillando en lados opuestos del cielo profundo",
            "Reflejos en un lago nocturno, cada imagen buscando su original",
            "Plumas de cóndor y sus sombras correspondiéndose en la luz lunar",
            "Constelaciones en espejo a ambos lados de la Vía Láctea",
        ],
        'builder': [
            "Fragmentos de constelación flotando en la oscuridad, esperando orden",
            "Cristales de cuarzo con inscripciones dispersos en el aire nocturno",
            "Esferas de luz que forman un mensaje al alinearse en el cielo",
            "Runas flotantes reacomodándose lentamente en el espacio estelar",
        ],
        'conversation': [
            "Figuras luminosas conversando bajo un cielo de estrellas infinitas",
            "Espíritus reunidos en un círculo de luna llena sobre la montaña",
            "Diálogo entre sombras y luces en un templo de obsidiana pulida",
            "Voces que se cruzan como hilos de luz entre las constelaciones",
        ],
        'category': [
            "Estrellas de diferentes colores clasificándose en sus constelaciones",
            "Plumas mágicas flotando hacia diferentes nidos de luz estelar",
            "Cristales separándose por color en corrientes de viento cósmico",
            "Nebulosas organizándose por matiz en la inmensidad del cielo",
        ],
        'dictation': [
            "Mano trazando símbolos de luz en la oscuridad del cosmos infinito",
            "Estrellas formando letras lentamente en el cielo nocturno",
            "Superficie de obsidiana pulida donde aparecen palabras luminosas",
            "Pluma de luz escribiendo sobre un pergamino de cielo oscuro",
        ],
        'escaperoom': [
            "Portal de piedra volcánica con runas brillantes, umbral a otro plano",
            "Cámara circular con espejos infinitos, un acertijo en cada reflejo",
            "Torre antigua con escalera de caracol, puertas selladas con enigmas",
            "Laberinto de espejos bajo la luna llena, cada reflejo muestra un símbolo",
        ],
        'cronica': [
            "Pergamino flotando en el espacio, tinta de estrellas brillando",
            "Libro sagrado abierto en un altar de obsidiana, páginas luminosas",
            "Códice de sueños suspendido entre las constelaciones del cielo",
            "Diario cósmico con tapas de piedra lunar, páginas de luz plateada",
        ],
        'ritmo': [
            "Esferas celestes girando en órbitas rítmicas, armonía cósmica visible",
            "Latido del universo visible como ondas de luz pulsante y color",
            "Auroras danzando al ritmo de un tambor invisible en la noche",
            "Anillos de Saturno vibrando como cuerdas de un instrumento cósmico",
        ],
        'conjugation': [
            "Estrella transformándose, su luz refractada en diferentes formas",
            "Agua congelándose y derritiéndose en ciclos sobre piedra volcánica",
            "Sombra de jaguar cambiando de forma bajo la luna creciente",
            "Cristal girando lentamente, cada faceta reflejando una variación",
        ],
        'narrative': [
            "Cielo nocturno con la Vía Láctea cruzando de horizonte a horizonte",
            "Cumbre sobre las nubes, estrellas al alcance de la mano extendida",
            "Yaguará contemplando el cosmos desde la rama más alta de Abuela Ceiba",
            "Aurora boreal sobre un lago de montaña, reflejos infinitos",
        ],
        'grammar': [
            "Constelaciones formando diagramas gramaticales en el cielo profundo",
            "Red de hilos de luz entre estrellas mostrando relaciones lingüísticas",
            "Espiral de galaxia cuya estructura refleja la gramática del idioma",
            "Runas de gramática brillando en un círculo de piedras volcánicas",
        ],
        'story': [
            "Vía Láctea arqueándose sobre un paisaje de montañas silenciosas",
            "Yaguará dormida bajo las estrellas, sueños visibles como auroras",
            "Templo en la cima de la montaña, la luna llena justo encima",
            "Amanecer cósmico, el sol naciendo entre constelaciones que se apagan",
        ],
    },
}

# ── Fallback for uncommon types ───────────────────────────────────
WORLD_SCENES = {
    'abajo': [
        "Escena en la selva profunda, luz verde filtrándose entre las hojas",
        "Orilla del río en la selva, piedras y raíces bajo luz dorada",
        "Claro del bosque tropical, luciérnagas y niebla baja al amanecer",
        "Sendero entre helechos gigantes y árboles cubiertos de musgo",
    ],
    'medio': [
        "Escena en un sendero de montaña, nubes y picos a lo lejos",
        "Plaza de pueblo andino al atardecer, muros de adobe y flores",
        "Terraza de cultivo en la montaña, vista del valle profundo",
        "Puente de piedra en la montaña, agua cristalina debajo",
    ],
    'arriba': [
        "Escena bajo el cielo estrellado, constelaciones brillando intensamente",
        "Cima de la montaña sobre las nubes, cosmos visible arriba",
        "Paisaje nocturno con aurora y luna creciente sobre un lago",
        "Templo de obsidiana bajo las estrellas, luz plateada en el aire",
    ],
}


def get_alt(game_type, world, idx):
    """Return imageAlt for a game type in a world. Cycles through pool."""
    # Narrative with grammar field → use 'grammar' pool
    pool = ALTS.get(world, {}).get(game_type)
    if not pool:
        pool = WORLD_SCENES.get(world, WORLD_SCENES['abajo'])
    return pool[idx % len(pool)]


def process_destination(dest_num):
    filepath = os.path.join(BASE, f'dest{dest_num}.json')
    if not os.path.exists(filepath):
        print(f'  SKIP dest{dest_num}: file not found')
        return 0

    with open(filepath, 'r', encoding='utf-8') as f:
        data = json.load(f)

    games = data.get('games', [])
    if not games:
        print(f'  SKIP dest{dest_num}: no games array')
        return 0

    world = get_world(dest_num)
    added = 0

    # Count occurrences of each type to decide numbering
    type_counts = Counter()
    for g in games:
        t = g.get('type', 'unknown')
        if t == 'narrative' and g.get('grammar'):
            t = 'grammar'
        type_counts[t] += 1

    # Track per-type index for numbering
    type_idx = Counter()

    for g in games:
        if 'image' in g:
            # Already has image — skip but still count type index
            t = g.get('type', 'unknown')
            if t == 'narrative' and g.get('grammar'):
                t = 'grammar'
            type_idx[t] += 1
            continue

        raw_type = g.get('type', 'unknown')
        alt_type = raw_type
        file_type = raw_type

        # Narrative with grammar → special treatment
        if raw_type == 'narrative' and g.get('grammar'):
            alt_type = 'grammar'
            file_type = 'grammar'

        idx = type_idx[alt_type]
        type_idx[alt_type] += 1

        # Build filename
        if type_counts[alt_type] > 1:
            filename = f'img/destinations/dest{dest_num}-{file_type}-{idx + 1}.jpg'
        else:
            filename = f'img/destinations/dest{dest_num}-{file_type}.jpg'

        g['image'] = filename
        g['imageAlt'] = get_alt(alt_type, world, idx)
        added += 1

    # Write back with same formatting
    with open(filepath, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write('\n')

    return added


def main():
    total_added = 0
    total_games = 0

    for d in range(1, 59):
        added = process_destination(d)
        # Count total games
        filepath = os.path.join(BASE, f'dest{d}.json')
        if os.path.exists(filepath):
            with open(filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
            total_games += len(data.get('games', []))
        total_added += added
        if added > 0:
            print(f'  dest{d}: +{added} images')

    print(f'\nDone. Added {total_added} image fields across {total_games} total games.')
    print(f'Coverage: {((total_games - (total_games - total_added - 14)) / total_games * 100):.1f}%')
    # 14 = dest1 games that already had images


if __name__ == '__main__':
    main()
