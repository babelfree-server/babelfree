#!/usr/bin/env python3
"""
Updates all dest*.json files with imageAlt descriptions for the arrival illustrations.
Also generates illustration-brief-arrivals.html for the illustrator.

Run from project root:
    python3 generator/update-arrival-imagealt.py
"""

import json
import re
import os

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONTENT_DIR = os.path.join(BASE, 'content')

# ── Scene descriptions for each destination ──────────────────────────────
# Derived from arrival narrative text, Campbell stage, and world context.
# Each is a concise visual scene that an illustrator can paint.

SCENES = {
    # ── A1 Basic: Mundo de Abajo (dest 1–6) ──
    1:  "La selva amazónica al amanecer — luz dorada filtrándose entre árboles enormes, río visible al fondo, niebla baja",
    2:  "Yaguará sentada junto a un río cristalino, mirando un árbol, un pájaro y el cielo — todo nombrado por primera vez",
    3:  "El río grande visto desde arriba — agua fría azulada, bosque verde intenso, cielo azul, Yaguará observando desde la orilla",
    4:  "Yaguará y Río contando piedras junto al agua — un árbol, dos pájaros, tres piedras en la ribera del río",
    5:  "Yaguará bebiendo agua fría del río mientras Río juega entre las raíces — el silencio inusual del bosque",
    6:  "Un día completo en la selva — Yaguará caminando bajo la copa de los árboles, fruta en el suelo, Mamá Jaguar al fondo",

    # ── A1 Advanced (dest 7–12) ──
    7:  "Una escena de comida junto al río — frutas, pescado, agua fresca, Yaguará y su familia reunidos bajo un árbol",
    8:  "La familia de jaguares junto al río — madre, padre, hermana, hermano, todos juntos, el río sonando fuerte",
    9:  "Una casa de madera y hojas sobre pilotes en el río — pequeña, acogedora, rodeada de vegetación tropical",
    10: "El amanecer sobre la selva — el sol naciente iluminando el río, marcando las siete de la mañana, enero",
    11: "Yaguará sentada bajo la Abuela Ceiba escuchando — el árbol enorme con raíces como ríos, luciérnagas tenues",
    12: "Yaguará mirando su reflejo en el agua del río — el reflejo nítido, un momento de identidad y reconocimiento",

    # ── A2 Basic (dest 13–16) ──
    13: "El amanecer en el río — Yaguará despertando, bañándose en el agua fría, los sonidos del bosque volviendo",
    14: "Candelaria aparece por primera vez — una niña de doce años con ojos brillantes y uniforme de escuela, junto a Yaguará en el bosque",
    15: "Yaguará junto a un río seco — expresión de hambre y cansancio, el contraste entre lo que necesita y lo que quiere",
    16: "El cielo del bosque cambiando — nubes de lluvia sobre la selva, gotas cayendo entre las hojas, luz gris y verde",

    # ── A2 Advanced (dest 17–18) ──
    17: "Yaguará mirando un bosque que recuerda más verde — árboles con menos hojas, un río más estrecho que antes, nostalgia",
    18: "Vista panorámica del Mundo de Abajo completo — la selva, el río, la ceiba, todo visto desde una altura, Yaguará mirando atrás antes de partir",

    # ── B1 Basic: Mundo del Medio (dest 19–23) ──
    19: "Yaguará entrando en una aldea del Chocó — casas de madera coloridas, gente en movimiento, Colibrí volando adelante, la selva detrás",
    20: "El pueblo al atardecer — gente sentada en círculo contando historias, antorchas encendidas, Yaguará escuchando desde las sombras",
    21: "El sendero de montaña en los Andes — el valle abajo, la niebla al medio, la cumbre arriba, Cóndor Viejo sobrevolando",
    22: "El llano infinito — pasto dorado hasta el horizonte, cielo enorme, palmeras dispersas, el Delfín Rosado asomando en un río que cruza la llanura",
    23: "Yaguará viendo el mar por primera vez — olas turquesa rompiendo en la costa caribeña, Tortuga Marina emergiendo del agua",

    # ── B1 Advanced (dest 24–28) ──
    24: "Dos caminos que se cruzan en el bosque — Yaguará por uno, Candelaria por el otro, Doña Asunción al fondo junto al río",
    25: "Don Próspero de pie en una colina, señalando un mapa extendido — su silueta elegante contra el cielo, Yaguará y Candelaria observando desde abajo",
    26: "Un claro del bosque donde antes había un río — tierra seca, troncos caídos, el silencio visible como neblina gris",
    27: "Una escena dividida — a un lado Don Próspero con planos y trabajadores, al otro Candelaria protegiendo un árbol, el bosque entre ambos",
    28: "Doña Asunción sentada junto al río — anciana, pañuelo colorido, manos tocando el agua, Candelaria escuchando arrodillada a su lado",

    # ── B2 Basic: Mundo del Medio (dest 29–33) ──
    29: "El bosque perdiendo color — árboles grises, río silencioso, una niebla gris avanzando desde el horizonte, Cóndor Viejo en una rama desnuda",
    30: "Yaguará enfrentando su propia sombra — la sombra distorsionada como un reflejo en agua turbulenta, ojos de espejo",
    31: "Una plaza del pueblo llena de gente diversa hablando — mercado, gestos, desacuerdos, Yaguará entre la multitud",
    32: "El mismo paisaje visto desde dos perspectivas — mitad de la imagen próspera y mitad dañada, Don Próspero y Candelaria de espaldas uno al otro",
    33: "Un cementerio de palabras — letras talladas en piedras cubiertas de musgo, un lugar donde descansan los nombres olvidados",

    # ── B2 Advanced (dest 34–38) ──
    34: "Un río que fluye hacia atrás — palabras y rostros flotando en el agua luminosa, la corriente invertida, luz ámbar",
    35: "La llanura al atardecer — surcos en la tierra formando escritura, huellas de animales como jeroglíficos, Yaguará leyendo el suelo",
    36: "Don Próspero y Candelaria frente a un bosque con etiquetas de precio colgando de los árboles — la tensión entre economía y vida",
    37: "Un mural natural en desaparición — ranas, mariposas, y aves desvanecidas como si el color se borrara, la cadena de la vida rompiéndose",
    38: "El Mundo del Medio al anochecer — Yaguará y Candelaria mirando atrás desde un sendero que sube, todo lo recorrido visible abajo",

    # ── C1 Basic: Mundo de Arriba (dest 39–43) ──
    39: "El paso al Mundo de Arriba — estrellas cercanas, aire índigo-plateado, las palabras visibles como constelaciones en el cielo",
    40: "Los Antiguos — figuras luminosas entre las estrellas, formas ancestrales, Yaguará y Candelaria mirando hacia arriba con reverencia",
    41: "Un mapa sonoro de Colombia — ondas de sonido visibles saliendo de diferentes regiones, el Chocó, los Llanos, la costa, Bogotá, todos diferentes",
    42: "Hilos de luz tejidos como un telar Wayúu — las palabras convertidas en hilos brillantes formando un tejido cósmico",
    43: "Hilos de luz dorada conectando párrafos flotantes en el cielo — la cohesión textual como red luminosa entre ideas",

    # ── C1 Advanced (dest 44–48) ──
    44: "Un templo de silencio hecho de luz y sombra — columnas de palabras suspendidas, un espacio para la contemplación del lenguaje",
    45: "Una piedra flotante grabada con retórica — discursos de Bolívar, grafitis urbanos, todo transformado en argumentación visible",
    46: "Ruinas donde suenan voces — textos superpuestos como palimpsesto, García Márquez junto a grafitis contemporáneos, diálogo entre tiempos",
    47: "Un espacio vacío donde se nombran las cosas — el acto de nombrar como poder visible, la primera palabra creando forma en la oscuridad",
    48: "Una estrella-brújula en el cielo nocturno — guiando a Yaguará y Candelaria por un camino de producción académica, conocimiento como luz",

    # ── C2 Basic: Mundo de Arriba (dest 49–53) ──
    49: "Bogotá nocturna vista desde arriba — ocho millones de luces como voces, la ciudad entera vibrando con registros lingüísticos",
    50: "Una mano escribiendo en el aire — la tinta formando territorios, mapas, mundos, la palabra como acto de creación",
    51: "Un espejo cósmico que refleja el idioma — Yaguará mirando el español desde fuera y desde dentro simultáneamente",
    52: "Voces transformándose en olas de cambio — un discurso convirtiéndose en música, la música convirtiéndose en acción, ondas expansivas de palabras",
    53: "Un guardián despertando dentro de una constelación — Yaguará brillando como una estrella entre otras estrellas-guardianes del idioma",

    # ── C2 Advanced (dest 54–58) ──
    54: "El regreso a la selva — Yaguará volviendo al bosque del inicio, pero ahora la selva brilla con constelaciones entre las ramas",
    55: "Los tres mundos vistos desde lo alto — Abajo, Medio, Arriba apilados como capas de un mismo mundo, 58 destinos brillando como puntos de luz",
    56: "El español como río vivo — un cauce que cambia de curso, se bifurca, recibe afluentes, nunca se detiene, cada generación agregando agua",
    57: "Yaguará y Candelaria adulta como guardianas — ambas de pie ante la Abuela Ceiba, la copa del árbol mostrando ramas hacia otras lenguas",
    58: "El espíritu completo — Yaguará en la selva del amanecer, idéntica al inicio pero radiando luz dorada, el viaje como círculo cerrado",
}

# ── Character name mapping ───────────────────────────────────────────────
CHAR_NAMES = {
    'char_yaguara': 'Yaguará',
    'char_rio': 'Río',
    'char_mama_jaguar': 'Mamá Jaguar',
    'char_abuela_ceiba': 'Abuela Ceiba',
    'char_colibri': 'Colibrí',
    'char_condor_viejo': 'Cóndor Viejo',
    'char_delfin_rosado': 'Delfín Rosado',
    'char_tortuga_marina': 'Tortuga Marina',
    'char_candelaria': 'Candelaria',
    'char_don_prospero': 'Don Próspero',
    'char_dona_asuncion': 'Doña Asunción',
}

WORLD_NAMES = {
    'mundoDeAbajo': 'Mundo de Abajo',
    'mundoDelMedio': 'Mundo del Medio',
    'mundoDeArriba': 'Mundo de Arriba',
}

WORLD_PALETTES = {
    'mundoDeAbajo': 'Verdes profundos, azules de río, marrones de tierra, toques dorados',
    'mundoDelMedio': 'Terracota, ocre, gris-azul de montaña, turquesa caribeño',
    'mundoDeArriba': 'Índigo profundo, plata estelar, ámbar luminoso, negro cósmico',
}

CEFR_RANGES = {
    'A1': 'dest1–dest12',
    'A2': 'dest13–dest18',
    'B1': 'dest19–dest28',
    'B2': 'dest29–dest38',
    'C1': 'dest39–dest48',
    'C2': 'dest49–dest58',
}


def update_json_files():
    """Inject imageAlt into each dest JSON."""
    updated = 0
    for num, scene in SCENES.items():
        path = os.path.join(CONTENT_DIR, f'dest{num}.json')
        if not os.path.exists(path):
            print(f"  SKIP: {path} does not exist")
            continue

        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        if 'arrival' not in data:
            print(f"  SKIP: dest{num}.json has no arrival")
            continue

        data['arrival']['imageAlt'] = scene
        # Ensure image path is set
        if not data['arrival'].get('image'):
            data['arrival']['image'] = f'img/destinations/dest{num}-arrival.jpg'

        with open(path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)

        updated += 1

    print(f"Updated {updated} destination files with imageAlt descriptions.")


def generate_brief():
    """Generate HTML illustrator brief for all 58 arrival scenes."""
    rows = []

    for num in range(1, 59):
        path = os.path.join(CONTENT_DIR, f'dest{num}.json')
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        meta = data.get('meta', {})
        arrival = data.get('arrival', {})
        title = meta.get('title', '')
        cefr = meta.get('cefr', '')
        world = meta.get('world', '')
        campbell = meta.get('campbellStage', '')
        chars = [CHAR_NAMES.get(c, c) for c in meta.get('characters', [])]
        scene = SCENES.get(num, '')

        # Extract first paragraph for narrative context
        sections = arrival.get('sections', [])
        narrative = ''
        if sections:
            narrative = re.sub(r'<[^>]+>', '', sections[0].get('body', ''))

        rows.append({
            'num': num,
            'title': title,
            'cefr': cefr,
            'world': world,
            'worldName': WORLD_NAMES.get(world, world),
            'palette': WORLD_PALETTES.get(world, ''),
            'campbell': campbell,
            'chars': ', '.join(chars),
            'scene': scene,
            'narrative': narrative,
            'filename': f'dest{num}-arrival.jpg',
        })

    # Build HTML
    html = """<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Brief del ilustrador — 58 escenas de llegada</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #1a1208;
    color: #e8d5b7;
    line-height: 1.6;
    padding: 40px 24px;
    max-width: 1100px;
    margin: 0 auto;
}
h1 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 2.2rem;
    color: #c9a227;
    margin-bottom: 12px;
}
.intro {
    color: #b8a48a;
    font-size: 15px;
    margin-bottom: 40px;
    max-width: 750px;
}
.intro strong { color: #e8d5b7; }

/* World sections */
.world-header {
    margin: 48px 0 24px 0;
    padding: 16px 20px;
    border-radius: 12px;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.5rem;
}
.world-header.abajo { background: rgba(0, 80, 60, 0.3); border-left: 4px solid #2E8B8B; }
.world-header.medio { background: rgba(140, 80, 40, 0.2); border-left: 4px solid #c67a4a; }
.world-header.arriba { background: rgba(30, 30, 80, 0.4); border-left: 4px solid #6060c0; }
.world-header small {
    display: block;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #b8a48a;
    margin-top: 4px;
}

/* Destination cards */
.dest {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(201, 162, 39, 0.12);
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
}
.dest-header {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 8px;
}
.dest-num {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.3rem;
    color: #c9a227;
    min-width: 50px;
}
.dest-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #e8d5b7;
}
.dest-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.tag {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(201, 162, 39, 0.12);
    color: #c9a227;
}
.tag.cefr { background: rgba(46, 139, 139, 0.2); color: #5dc0c0; }
.tag.campbell { background: rgba(160, 100, 60, 0.2); color: #d4913a; }

.scene-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #8a7a60;
    margin-bottom: 4px;
}
.scene-desc {
    font-size: 15px;
    color: #e8d5b7;
    margin-bottom: 12px;
    padding: 12px 16px;
    background: rgba(201, 162, 39, 0.06);
    border-left: 3px solid rgba(201, 162, 39, 0.3);
    border-radius: 0 8px 8px 0;
}
.narrative {
    font-size: 13px;
    color: #9a8a70;
    font-style: italic;
    margin-bottom: 8px;
}
.filename {
    font-family: monospace;
    font-size: 12px;
    color: #6a6a5a;
}

/* Specs section */
.specs {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(201, 162, 39, 0.12);
    border-radius: 12px;
    padding: 24px;
    margin: 40px 0;
}
.specs h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    color: #c9a227;
    font-size: 1.4rem;
    margin-bottom: 16px;
}
.specs ul {
    list-style: none;
    padding: 0;
}
.specs li {
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: 14px;
}
.specs li strong { color: #c9a227; }

@media print {
    body { background: #fff; color: #222; }
    .dest { border-color: #ccc; }
    .world-header { border-color: #999; }
    .scene-desc { border-color: #c9a227; }
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<h1>Brief del ilustrador: 58 escenas de llegada</h1>
<p class="intro">
    Cada destino del juego comienza con una <strong>escena de llegada</strong> — una ilustración
    que establece el tono visual y emocional antes de que el estudiante comience las actividades.
    Este documento describe las 58 escenas, organizadas por mundo y nivel CEFR.
    <br><br>
    Consultar <strong>illustration-specs.md</strong> para las especificaciones técnicas completas
    (paletas de color, formatos de archivo, estilo visual, referencias artísticas).
</p>

<div class="specs">
    <h2>Especificaciones técnicas</h2>
    <ul>
        <li><strong>Cantidad:</strong> 58 ilustraciones (una por destino)</li>
        <li><strong>Formato:</strong> JPG (web), PNG fuente con transparencia (editables)</li>
        <li><strong>Dimensiones:</strong> 1200 × 800px mínimo (paisaje). Se usará con object-fit: cover.</li>
        <li><strong>Relación de aspecto:</strong> 3:2 (apaisado)</li>
        <li><strong>Nombrar como:</strong> <code>dest{N}-arrival.jpg</code> (ej: dest1-arrival.jpg, dest58-arrival.jpg)</li>
        <li><strong>Carpeta destino:</strong> <code>img/destinations/</code></li>
        <li><strong>Estilo:</strong> Ilustrado, cálido, orgánico. NO fotorrealista, NO anime, NO clipart. Ver illustration-specs.md.</li>
        <li><strong>Progresión visual:</strong> A1 = simple, cálido, pocos elementos. C2 = complejo, cósmico, simbólico.</li>
    </ul>
</div>
"""

    current_world = None
    for r in rows:
        # World header
        if r['world'] != current_world:
            current_world = r['world']
            cls = 'abajo' if 'Abajo' in r['worldName'] else ('medio' if 'Medio' in r['worldName'] else 'arriba')
            html += f"""
<div class="world-header {cls}">
    {r['worldName']}
    <small>Paleta: {r['palette']}</small>
</div>
"""

        html += f"""
<div class="dest">
    <div class="dest-header">
        <span class="dest-num">#{r['num']:02d}</span>
        <span class="dest-title">{r['title']}</span>
    </div>
    <div class="dest-tags">
        <span class="tag cefr">{r['cefr']}</span>
        <span class="tag">{r['worldName']}</span>
        <span class="tag campbell">{r['campbell']}</span>
        {''.join(f'<span class="tag">{c}</span>' for c in r['chars'].split(', ') if c)}
    </div>
    <div class="scene-label">Escena para ilustrar</div>
    <div class="scene-desc">{r['scene']}</div>
    <div class="narrative">Contexto narrativo: &laquo;{r['narrative'][:180]}{'...' if len(r['narrative']) > 180 else ''}&raquo;</div>
    <div class="filename">Archivo: <code>img/destinations/{r['filename']}</code></div>
</div>
"""

    html += """
<div class="specs" style="margin-top: 60px;">
    <h2>Documentos relacionados</h2>
    <ul>
        <li><strong>illustration-specs.md</strong> — Paletas de color, estilo, referencias artísticas, personajes completos</li>
        <li><strong>illustration-specs-escaperoom.md</strong> — Salas de escape y personajes humanos</li>
        <li><strong>story-bible.md</strong> — Historia completa, arco narrativo, personajes</li>
        <li><strong>story-arc-complete.md</strong> — Arco dest1–dest58 completo</li>
    </ul>
</div>

</body>
</html>
"""

    out_path = os.path.join(BASE, 'illustration-brief-arrivals.html')
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(html)

    print(f"Generated illustrator brief: {out_path}")


if __name__ == '__main__':
    print("=== Updating dest JSON imageAlt fields ===")
    update_json_files()
    print()
    print("=== Generating illustrator brief ===")
    generate_brief()
    print()
    print("Done.")
