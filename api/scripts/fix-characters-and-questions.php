#!/usr/bin/env php
<?php
/**
 * Fix character presence gaps and closing question escalation for B1-C1 destinations.
 *
 * ISSUE 1: Add missing characters to characterLines + characterMeta
 * ISSUE 2: Rewrite departure.closingQuestion for B1-C1 dests
 *
 * Usage: php -d memory_limit=512M fix-characters-and-questions.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$contentDir = dirname(__DIR__, 2) . '/content';

// Load landmarks
$landmarks = json_decode(file_get_contents("$contentDir/landmarks-colombia.json"), true);
$landmarkMap = [];
foreach ($landmarks['landmarks'] as $lm) {
    $landmarkMap[$lm['dest']] = $lm;
}

// Character presence rules: char => first dest they appear
$charRanges = [
    'char_rio' => 4,
    'char_mama_jaguar' => 6,
    'char_dona_asuncion' => 9,
    'char_don_prospero' => 19,
    'char_candelaria' => 14,
];

// Character meta definitions
$charMetaDefs = [
    'char_rio' => [
        'id' => 'char_rio',
        'name' => 'Río',
        'avatar' => 'img/characters/rio.jpg',
        'voice' => 'playful',
        'role' => 'trickster-brother',
        'description' => 'Hermano jaguar travieso.',
    ],
    'char_mama_jaguar' => [
        'id' => 'char_mama_jaguar',
        'name' => 'Mamá Jaguar',
        'avatar' => 'img/characters/mama_jaguar.jpg',
        'voice' => 'ancient',
        'role' => 'mother-wisdom',
        'description' => 'Madre de Yaguará. Verdades cortas.',
    ],
    'char_dona_asuncion' => [
        'id' => 'char_dona_asuncion',
        'name' => 'Doña Asunción',
        'avatar' => 'img/characters/dona_asuncion.jpg',
        'voice' => 'elderly-wise',
        'role' => 'elder',
        'description' => 'Anciana sabia del río.',
    ],
    'char_don_prospero' => [
        'id' => 'char_don_prospero',
        'name' => 'Don Próspero',
        'avatar' => 'img/characters/don_prospero.jpg',
        'voice' => 'smooth-dangerous',
        'role' => 'antagonist',
        'description' => 'El que compra lo que no tiene precio.',
    ],
    'char_candelaria' => [
        'id' => 'char_candelaria',
        'name' => 'Candelaria',
        'avatar' => 'img/characters/candelaria.jpg',
        'voice' => 'emerging',
        'role' => 'bridge',
        'description' => 'Niña afrocolombiana. Su silencio dice más que muchas voces.',
    ],
];

// Global line registry for uniqueness
$usedLines = [];

function registerLine($line) {
    global $usedLines;
    $key = mb_strtolower(trim($line));
    if (isset($usedLines[$key])) return false;
    $usedLines[$key] = true;
    return true;
}

/**
 * Generate unique character lines. Uses large pools with dest-specific details woven in.
 * Each line includes $sp (short place) and $title to guarantee uniqueness across dests.
 */
function generateCharacterLines($charId, $destNum, $landmark, $title, $cefr) {
    $place = $landmark['name'] ?? "destino $destNum";
    $region = $landmark['region'] ?? 'Colombia';
    $desc = $landmark['description'] ?? '';
    $ecosystem = $landmark['ecosystem'] ?? 'bosque';
    $sp = explode(',', $place)[0];

    // Build a large pool of templates, then pick 7 unique ones
    $templates = getTemplates($charId, $cefr);

    // Replace placeholders
    $lines = [];
    foreach ($templates as $t) {
        $line = str_replace(
            ['{sp}', '{place}', '{region}', '{title}', '{eco}', '{desc}'],
            [$sp, $place, $region, $title, $ecosystem, $desc],
            $t
        );
        $lines[] = $line;
    }

    // Shuffle deterministically
    mt_srand($destNum * 97 + ord($charId[5]) * 13);
    shuffle($lines);

    // Pick first 7 that are globally unique
    $result = [];
    foreach ($lines as $line) {
        if (registerLine($line)) {
            $result[] = $line;
            if (count($result) >= 7) break;
        }
    }

    // If we still don't have 7, add dest-numbered variants
    $i = 0;
    while (count($result) < 7 && $i < count($lines)) {
        $variant = $lines[$i] . " Así es en el destino $destNum.";
        if (registerLine($variant)) {
            $result[] = $variant;
        }
        $i++;
    }

    return $result;
}

function getTemplates($charId, $cefr) {
    switch ($charId) {
        case 'char_rio': return getRioTemplates($cefr);
        case 'char_mama_jaguar': return getMamaJaguarTemplates($cefr);
        case 'char_dona_asuncion': return getDonaAsuncionTemplates($cefr);
        case 'char_don_prospero': return getDonProsperoTemplates($cefr);
        case 'char_candelaria': return getCandelariaTemplates($cefr);
        default: return [];
    }
}

function getRioTemplates($cefr) {
    switch ($cefr) {
        case 'A1': return [
            "¡Mira! En {sp} hay algo que brilla.",
            "Yo cuento todo. En {sp} cuento las piedras.",
            "¿Sabes qué es lo mejor de {sp}? ¡Que no lo sé!",
            "En {sp} me perdí tres veces. Fue divertido.",
            "Aquí en {sp} el agua sabe diferente.",
            "¡Corre! En {sp} todo se mueve rápido.",
            "{sp} huele a aventura. Y a algo dulce.",
            "Hoy en {sp} encontré una piedra con forma de rana.",
            "¡En {sp} hay un río! Bueno, hay muchos. Pero este es mío.",
            "¿Ves eso? {sp} tiene colores que no conozco.",
            "En {sp} el viento juega conmigo. O yo juego con él.",
            "Me gusta {sp}. Aquí las cosas tienen nombres raros.",
            "En {sp} descubrí que el cielo también tiene suelo.",
            "¡{title}! Eso suena a un juego. ¿Jugamos?",
            "El camino de {sp} tiene curvas. Como mi cola.",
        ];
        case 'A2': return [
            "En {sp} descubrí que los errores son más divertidos que los aciertos.",
            "Los caminos de {sp} nunca van donde dices. Van donde quieren.",
            "¿Sabías que en {sp} los pájaros cantan en otro tono?",
            "Me gusta {sp} porque aquí nadie me dice que me calle.",
            "En {sp} aprendí algo: las mejores historias empiezan mal.",
            "Yo digo que {sp} es el lugar perfecto para perderse a propósito.",
            "Aquí en {region} las risas suenan más fuerte que en cualquier lado.",
            "Si {sp} fuera un juego, yo ya habría ganado. O perdido. No importa.",
            "En {sp} hasta las piedras tienen sentido del humor.",
            "Lo mejor de {sp} es que aquí nadie sabe lo que va a pasar. Ni yo.",
            "{sp} me enseñó que correr sin dirección es una forma de llegar.",
            "En {sp} me dijeron que no tocara nada. Toqué todo.",
            "Aquí en {sp} los trucos funcionan mejor cuando no piensas demasiado.",
            "La gente de {sp} dice «{title}» y yo entiendo otra cosa. Siempre.",
            "Si {sp} tuviera un manual, yo lo habría perdido ya.",
        ];
        case 'B1': return [
            "Una vez, cerca de {sp}, vi algo que no puedo explicar. Pero fue gracioso.",
            "Claro, porque caminar todo el día por {sp} siempre es «divertido».",
            "En {sp} las cosas tienen dos nombres: el que les pones y el que ya tenían.",
            "Los ríos de {region} nunca van en línea recta. Yo tampoco.",
            "Lo mejor de {sp} no está en el mapa. Está en lo que nadie te cuenta.",
            "{sp} me enseñó que a veces el camino equivocado es el correcto.",
            "Dicen que en {sp} todo tiene explicación. Yo prefiero el misterio.",
            "Si me preguntas por {sp}, te cuento una historia. Pero no la que esperas.",
            "«{title}» suena serio. Yo prefiero la versión donde todos nos reímos.",
            "En {sp} descubrí que la seriedad es el disfraz favorito del aburrimiento.",
            "Lo que pasa en {sp} se queda en {sp}. Excepto mis chistes. Esos viajan.",
            "Las reglas de {sp} son como los ríos de {region}: cambian según la lluvia.",
            "En {sp} alguien dijo algo sabio. Yo me reí y lo entendí después.",
            "¿{title}? Yo diría que es más como una aventura sin instrucciones.",
            "En {sp} aprendí que preguntar es más divertido que saber.",
        ];
        case 'B2': return [
            "En {sp} aprendí que la ironía es el idioma secreto de los que observan.",
            "Claro, porque {sp} es exactamente lo que parece. Nunca hay sorpresas. Mentira.",
            "Los mejores descubrimientos en {sp} son los que haces cuando dejas de buscar.",
            "Aquí en {sp} el paisaje engaña: lo que parece simple esconde capas infinitas.",
            "Me río porque en {sp} la realidad supera cualquier historia que yo pueda inventar.",
            "{sp} tiene ese tipo de belleza que solo ves cuando dejas de mirar.",
            "Las historias de {region} empiezan con un error. Las mejores, al menos.",
            "En {sp} descubrí que lo absurdo y lo profundo comparten frontera.",
            "«{title}» es una metáfora que {sp} vive sin darse cuenta.",
            "Lo más honesto que vi en {sp} fue un error que nadie intentó corregir.",
            "En {sp} el humor no es evasión: es la forma más directa de decir la verdad.",
            "El paisaje de {sp} me recordó que la complejidad no necesita explicación.",
            "Aquí en {sp} aprendí que reírse de uno mismo es la forma más valiente de crítica.",
            "En {region}, {sp} demuestra que la contradicción es el estado natural de las cosas.",
            "Lo que {sp} llama «{title}» yo lo llamo la mejor broma del viaje.",
        ];
        case 'C1': return [
            "A veces el que juega en {sp} es el único que entiende la regla verdadera.",
            "En {sp} la risa no es frivolidad: es la forma más valiente de resistir.",
            "Lo que aprendes en {sp} no se enseña. Se descubre tropezando.",
            "La paradoja de {sp} es que cuanto más juegas, más en serio te lo tomas.",
            "{sp} me reveló algo incómodo: el humor es una forma de honestidad radical.",
            "En {region} hay verdades que solo se dicen en broma. Esas son las importantes.",
            "Jugar en {sp} no es evasión. Es la forma más directa de enfrentar lo real.",
            "Lo absurdo de {sp} tiene más lógica que todo lo que llaman sentido común.",
            "En {sp}, «{title}» es una verdad disfrazada de juego. Como todo lo que importa.",
            "La risa que encontré en {sp} es más filosófica que cualquier tratado.",
            "Lo que {sp} enseña sobre el juego aplica a la vida: las reglas las escribes tú.",
            "En {sp} descubrí que la ironía es el último refugio de la inteligencia.",
            "El paisaje de {sp} es una broma cósmica que tardé años en entender.",
            "{sp} y «{title}» — dos formas de decir que lo importante nunca es obvio.",
            "Aquí en {sp} comprendí que el trickster no engaña: revela lo que nadie quiere ver.",
        ];
        case 'C2': return [
            "El río más profundo de {sp} es el que parece más tranquilo en la superficie.",
            "En {sp} descubrí que el juego y la filosofía son gemelos separados al nacer.",
            "Lo que {sp} me enseñó no cabe en palabras. Cabe en una carcajada.",
            "La verdad de {sp} es que lo que llamamos «realidad» es el chiste más elaborado del universo.",
            "En {sp} aprendí que la risa es el primer idioma y será el último.",
            "El paisaje de {sp} no se describe: se habita, se ríe, se olvida, se reinventa.",
            "Cada piedra de {sp} cuenta un chiste que tarda milenios en entenderse.",
            "En {region}, el silencio y la risa son la misma cosa vista desde ángulos distintos.",
            "«{title}» en {sp} es una paradoja viviente: lo más efímero contiene lo más eterno.",
            "Lo que aprendí en {sp} es que el humor trasciende la muerte. La seriedad, no.",
            "En {sp} vi que el absurdo y lo sagrado comparten la misma raíz etimológica.",
            "{sp} me enseñó que nombrar es el primer juego y el último acto de creación.",
            "La risa en {sp} es un acto metafísico que precede al lenguaje y lo sobrevive.",
            "Aquí en {sp}, «{title}» me reveló que todo sistema cerrado necesita un trickster para respirar.",
            "En {sp} comprendí que la ironía cósmica no es cruel: es la forma que tiene el universo de reírse consigo mismo.",
        ];
    }
    return [];
}

function getMamaJaguarTemplates($cefr) {
    switch ($cefr) {
        case 'A1': return [
            "Escucha, hijo. {sp} tiene voz.",
            "El bosque de {sp} recuerda todo.",
            "Aquí en {sp}, la tierra habla primero.",
            "No tengas miedo en {sp}. La noche también cuida.",
            "Cada árbol de {sp} tiene un nombre antiguo.",
            "En {sp}, el silencio enseña más que el ruido.",
            "La luna sobre {sp} sabe tu nombre.",
            "Respira. {sp} te está esperando desde antes.",
            "Las estrellas de {sp} cuentan historias de antes.",
            "Hijo, {sp} es viejo. Más viejo que nosotros.",
            "El agua de {sp} sabe cosas. Bebe despacio.",
            "En {sp}, hasta las piedras tienen memoria.",
            "Camina suave en {sp}. La tierra escucha tus pasos.",
            "{sp} y «{title}» — todo empieza con un nombre.",
            "El viento de {sp} trae noticias de lejos.",
        ];
        case 'A2': return [
            "En {sp}, el bosque tiene memoria. Solo necesitas escuchar.",
            "No todo camino en {sp} lleva a donde quieres. Algunos llevan a donde necesitas.",
            "La tierra de {region} guarda secretos que solo el silencio revela.",
            "Aquí en {sp} aprendes que la paciencia no es esperar. Es entender.",
            "Las raíces de {sp} son más profundas de lo que ves.",
            "En {sp}, cada amanecer repite una promesa antigua.",
            "Lo que buscas en {sp} ya te está buscando a ti.",
            "El viento de {region} trae palabras de los que vinieron antes.",
            "En {sp}, la noche no es oscuridad. Es otro tipo de luz.",
            "Las madres de {sp} saben cuándo soltar y cuándo sostener.",
            "Aquí en {sp}, cada hoja caída es una lección que la tierra devuelve.",
            "El río de {sp} no olvida por dónde pasó. Tú tampoco deberías.",
            "En {sp} aprendí que la raíz más fuerte es invisible.",
            "«{title}» es lo que {sp} le dice al mundo cada mañana.",
            "Los pájaros de {sp} cantan lo que los humanos callan.",
        ];
        case 'B1': return [
            "El bosque de {sp} tiene memoria. Escucha con atención.",
            "En {sp} aprendí que la verdadera fuerza es saber cuándo quedarse quieto.",
            "Las madres de {region} saben algo que los libros no enseñan: el tiempo cura lo que la prisa rompe.",
            "Aquí en {sp}, cada generación planta un árbol que no verá crecer del todo.",
            "No vine a {sp} a protegerte. Vine a prepararte para lo que viene.",
            "La sabiduría de {sp} no se hereda. Se gana caminando con los ojos abiertos.",
            "En {region}, los ancianos dicen que el río sabe más que cualquier montaña.",
            "Lo que te duele hoy en {sp} será tu fortaleza mañana. Créeme.",
            "«{title}» es una verdad que {sp} repite con paciencia cada estación.",
            "En {sp}, proteger no es encerrar. Es enseñar a caminar solo.",
            "Las raíces de {sp} nos recuerdan que crecer hacia abajo es tan importante como crecer hacia arriba.",
            "En {sp} descubrí que la vulnerabilidad no es debilidad: es la primera semilla de la fuerza.",
            "Lo que {sp} guarda en silencio vale más que todo lo que {region} grita.",
            "Aquí en {sp}, «{title}» significa que cada final es también una raíz nueva.",
            "El amanecer en {sp} me recordó que la luz siempre llega, aunque tarde.",
        ];
        case 'B2': return [
            "En {sp}, el silencio no es ausencia. Es la forma más antigua de comunicar.",
            "La memoria de {sp} es más larga que cualquier vida humana individual.",
            "Aquí en {sp} aprendes que proteger no es encerrar: es soltar con la confianza de que volverán.",
            "Las raíces de {region} sostienen lo que las ramas nunca podrán ver.",
            "En {sp} descubrí que la vulnerabilidad es la forma más alta de valentía genuina.",
            "Lo que {sp} guarda en silencio vale más que todo lo que el mundo exterior grita.",
            "Cada cicatriz del paisaje de {sp} cuenta una historia de resistencia, nunca de derrota.",
            "La noche en {sp} enseña lo que el día no se atreve a mostrar abiertamente.",
            "«{title}» en {sp} es una lección sobre la paciencia que dura generaciones.",
            "En {sp}, el dolor no destruye. Transforma, como el fuego transforma el mineral.",
            "Lo que aprendí en {sp} es que la maternidad es un acto de coraje, no de sacrificio.",
            "Las aguas de {sp} me enseñaron que la corriente más fuerte es la que no se ve.",
            "En {sp}, cada despedida es una semilla que germina en otro lugar del corazón.",
            "Aquí en {sp}, «{title}» me reveló que las verdades más profundas se dicen en susurros.",
            "El bosque de {sp} no juzga. Acoge todo lo que cae y lo convierte en tierra fértil.",
        ];
        case 'C1': return [
            "En {sp}, la tierra recuerda lo que los seres humanos prefieren olvidar.",
            "La paradoja de {sp} es que cuanto más profundamente vas, más ligero te vuelves.",
            "Aquí en {sp} comprendí que el amor maternal no protege del mundo: transforma al que lo recibe.",
            "La sabiduría de {region} no se articula en voz alta. Se respira con los pulmones del alma.",
            "En {sp}, cada especie que desaparece deja un silencio que ensordece al que sabe escuchar.",
            "Lo antiguo de {sp} no es simplemente viejo. Es eterno con las marcas del tiempo grabadas.",
            "El dolor ancestral de {sp} tiene raíces tan profundas que ya son parte del suelo mismo.",
            "En {region}, la memoria colectiva es un bosque que ningún hacha puede talar completamente.",
            "«{title}» en {sp} es una metáfora de lo que significa cuidar sin poseer.",
            "En {sp} descubrí que la maternidad es la primera filosofía y la última poesía.",
            "Las montañas de {sp} me enseñaron que la permanencia no es rigidez: es adaptación profunda.",
            "Lo que {sp} revela sobre la naturaleza humana no se encuentra en ningún libro escrito.",
            "En {sp}, «{title}» me recordó que cada madre es un puente entre lo que fue y lo posible.",
            "El paisaje de {sp} contiene una verdad incómoda: lo que no cuidamos, lo perdemos para siempre.",
            "Aquí en {sp} comprendí que soltar es la forma más exigente de amar.",
        ];
        case 'C2': return [
            "El primer sonido en {sp} fue un nombre. El último silencio de {sp} también lo será.",
            "En {sp} descubrí que la maternidad es la primera forma de filosofía que existe.",
            "Lo que {sp} enseña no tiene idioma propio. Tiene raíz, corteza y cielo.",
            "La verdad de {sp} es anterior a las palabras articuladas. Existe en la semilla dormida.",
            "Aquí en {sp} comprendí que soltar es la forma suprema y más difícil de sostener.",
            "El paisaje de {sp} es una oración que la tierra se repite a sí misma cada era.",
            "Cada nombre en {sp} es un acto de amor primordial. Cada olvido, una pequeña muerte innecesaria.",
            "En {region}, lo sagrado no se explica ni se define. Se habita en silencio reverente.",
            "«{title}» en {sp} contiene la misma verdad que el primer grito de un recién nacido.",
            "Lo que aprendí en {sp} es que la materia y el espíritu son dos nombres para la misma cosa.",
            "En {sp} vi que la raíz más profunda del lenguaje es un acto de cuidado maternal.",
            "El silencio de {sp} no es ausencia de sonido: es la presencia plena de todo lo no dicho.",
            "En {sp}, «{title}» me reveló que crear y destruir son el mismo verbo conjugado en tiempos distintos.",
            "La tierra de {sp} me enseñó que la sabiduría no se acumula: se destierra, se pierde y se reencuentra.",
            "Aquí en {sp} comprendí que el universo entero cabe en el acto de nombrar a un hijo.",
        ];
    }
    return [];
}

function getDonaAsuncionTemplates($cefr) {
    switch ($cefr) {
        case 'A1': return [
            "Cuando yo era niña, {sp} era diferente.",
            "Siéntate. Te cuento de {sp}.",
            "En {sp}, los viejos sabemos cosas.",
            "Mi abuela conocía cada piedra de {sp}.",
            "Aquí en {sp}, el río tenía otro nombre antes.",
            "Los niños de {sp} no saben lo que yo vi.",
            "El tiempo en {sp} pasa de otra manera.",
            "Escucha bien, que {sp} tiene historias viejas.",
            "La comida de {sp} sabe a recuerdo.",
            "En {sp}, las abuelas somos la memoria.",
            "{sp} cambió mucho. Pero el cielo sigue igual.",
            "Antes en {sp} no había prisa. Ahora todo corre.",
            "Los nombres de {sp} cuentan su historia.",
            "«{title}» me recuerda a lo que decía mi madre en {sp}.",
            "En {sp}, yo aprendí antes de saber leer.",
        ];
        case 'A2': return [
            "Cuando yo era niña, este lugar en {sp} tenía otro nombre diferente.",
            "En {sp}, cada generación pierde algo importante. Pero también gana algo nuevo.",
            "Los jóvenes de {region} no escuchan todavía. Pero un día lo harán.",
            "Aquí en {sp}, mi madre me enseñó a leer las nubes del atardecer.",
            "El progreso llegó a {sp}. No todo lo que trajo consigo era bueno.",
            "En {sp} aprendí que recordar es una forma activa de resistir.",
            "Las historias de {sp} no están en los libros escritos. Están en las cocinas.",
            "Mi primer recuerdo de {sp} es el olor de la tierra después de la lluvia.",
            "La gente de {sp} antes se saludaba por nombre. Ahora no se conocen.",
            "En {sp}, «{title}» significa algo que los jóvenes ya no entienden.",
            "Lo que perdió {sp} no se compra con dinero ni con promesas.",
            "Aquí en {sp} las paredes de las casas viejas guardan conversaciones.",
            "En {region}, las mujeres como yo somos archivos vivientes de {sp}.",
            "Lo que yo sé de {sp} me lo enseñó la vida, no la escuela.",
            "En {sp}, cada esquina tiene la voz de alguien que ya se fue.",
        ];
        case 'B1': return [
            "Cuando yo era niña en {region}, este río de {sp} tenía otro nombre y otra voz.",
            "El progreso en {sp} no siempre avanza hacia adelante. A veces retrocede disfrazado.",
            "Cada generación en {sp} pierde una palabra única. Nosotros decidimos cuál será.",
            "En {sp} aprendí que la tradición no es repetir mecánicamente: es reinventar con respeto.",
            "Los que construyeron {sp} sabían algo fundamental que nosotros hemos olvidado.",
            "Aquí en {sp}, la vejez no es una carga. Es un privilegio con responsabilidad enorme.",
            "Las calles de {sp} guardan ecos de conversaciones que ya nadie tiene la edad de recordar.",
            "En {region}, los ancianos no damos consejos directos. Contamos historias y tú decides qué hacer.",
            "«{title}» — en {sp} eso se dice con otras palabras, más viejas y más verdaderas.",
            "Lo que {sp} me enseñó sobre {title} no está en ningún diccionario moderno.",
            "En {sp}, mi generación fue la última que escuchó ciertas palabras. Ahora yo las guardo.",
            "Las raíces de {sp} son más fuertes que sus edificios nuevos. Eso me da esperanza.",
            "Aquí en {sp}, cada fiesta perdida es una historia que ya nadie contará igual.",
            "En {sp}, ser vieja no es un adjetivo. Es un oficio que requiere valentía.",
            "Lo que pasa en {sp} hoy es hijo de lo que pasó ayer. No lo olvides nunca.",
        ];
        case 'B2': return [
            "En {sp}, el tiempo no pasa linealmente: se acumula como capas de pintura en una pared vieja.",
            "Lo que buscas en {sp} ya te encontró hace tiempo. Solo falta que lo nombres en voz alta.",
            "El silencio de {sp} no es vacío: es un archivo exhaustivo de todo lo que se ha dicho.",
            "Aquí en {sp} comprendí que la nostalgia no es debilidad sentimental. Es cartografía emocional.",
            "Las ruinas de {sp} no son fracasos del pasado. Son la evidencia de que alguien lo intentó con todo.",
            "En {region}, cada abuela es una biblioteca viviente que arde lentamente sin que nadie lo note.",
            "Lo que {sp} perdió no se recupera jamás. Se transforma en otra cosa, igual de valiosa.",
            "La memoria de {sp} es selectiva por necesidad: recuerda lo que duele para no repetirlo.",
            "«{title}» en {sp} es una frase que mi abuela habría dicho con palabras distintas pero idéntico significado.",
            "En {sp}, la tradición oral es más precisa que cualquier archivo escrito. Lo he comprobado.",
            "Lo que {sp} me enseñó sobre la pérdida es que también se pierde la capacidad de notar lo perdido.",
            "En {sp}, cada generación cree ser la primera. Los viejos sabemos que todas son la misma.",
            "Las paredes de {sp} contienen más historia que todos los museos de {region} juntos.",
            "Aquí en {sp}, «{title}» es el nombre que le damos a lo que no queremos perder.",
            "En {sp} aprendí que la vejez no es el final del camino. Es la cima desde donde ves todo el recorrido.",
        ];
        case 'C1': return [
            "En {sp}, la memoria colectiva es un río subterráneo que alimenta todo lo que se ve en la superficie.",
            "Lo que {sp} me enseñó es que el olvido es tan creativamente poderoso como el recuerdo.",
            "Aquí en {sp}, cada piedra es un palimpsesto de civilizaciones que se creyeron absolutamente eternas.",
            "La paradoja de {sp} es que solo valoramos genuinamente lo perdido cuando ya no podemos recuperarlo.",
            "En {region}, la tradición oral es la forma más sofisticada e invisible de resistencia cultural.",
            "Las generaciones de {sp} no se suceden linealmente: se superponen como capas geológicas complejas.",
            "El patrimonio real de {sp} no está en los museos oficiales. Está en los gestos cotidianos inadvertidos.",
            "En {sp} descubrí que la sabiduría popular contiene más epistemología que toda la academia formal.",
            "«{title}» en {sp} es una síntesis de todo lo que la modernidad cree haber superado.",
            "En {sp}, la historia oficial es una versión editada de lo que realmente pasó. Yo estuve allí.",
            "Lo que {sp} revela sobre el tiempo es que la linealidad es una ilusión cómoda.",
            "En {sp}, cada anciana es un nodo de una red de conocimiento que precede a internet por milenios.",
            "El patrimonio intangible de {sp} es más frágil y más valioso que cualquier monumento de piedra.",
            "Aquí en {sp}, «{title}» contiene una crítica implícita a todo lo que llamamos progreso.",
            "En {sp} comprendí que la experiencia no se transmite: se recrea en cada generación con materiales nuevos.",
        ];
        case 'C2': return [
            "En {sp} comprendí que la vejez es la última forma de vanguardia existente: ver lo que nadie más puede ver.",
            "Lo que {sp} guarda no es pasado petrificado: es un futuro que no se atrevió a nacer en su momento.",
            "Aquí en {sp}, cada arruga del paisaje es una frase que la tierra se negó a borrar de su piel.",
            "La tragedia de {sp} no es lo que se perdió, sino todo lo que nunca supimos que teníamos mientras lo teníamos.",
            "En {region}, el tiempo es circular por naturaleza: lo que olvidamos vuelve inevitablemente con otro nombre.",
            "La sabiduría de {sp} es anterior a la escritura inventada y sobrevivirá a la última página impresa.",
            "Cada silencio en {sp} es un tratado completo de filosofía que nadie se molestó en transcribir.",
            "En {sp} descubrí que nombrar el dolor con precisión es la primera forma auténtica de sanarlo.",
            "«{title}» en {sp} es una de esas verdades que solo se comprenden cuando ya no necesitas comprenderlas.",
            "En {sp}, la memoria de los ancianos es el único archivo que no puede ser hackeado ni corrompido.",
            "Lo que {sp} me enseñó trasciende el lenguaje: es un conocimiento que reside en los huesos.",
            "En {sp}, cada despedida es un acto fundacional tan importante como cada llegada.",
            "La tierra de {sp} me reveló que el patrimonio más valioso es el que no se puede musealizar.",
            "Aquí en {sp}, «{title}» me enseñó que la lucidez final de la vejez es ver la belleza en lo irreparable.",
            "En {sp} aprendí que la única inmortalidad real es la que se transmite de boca a oído.",
        ];
    }
    return [];
}

function getDonProsperoTemplates($cefr) {
    switch ($cefr) {
        case 'B1': return [
            "Buenos días, {sp}. Vengo a traer oportunidades. Solo cuestan un poco de silencio por ahora.",
            "{sp} tiene algo que quiero. Y lo que quiero, siempre lo consigo al final.",
            "La gente de {sp} no sabe lo que tiene entre manos. Yo sí. Por eso vine aquí.",
            "En {sp}, las palabras son gratis todavía. Pero el silencio ya tiene un precio.",
            "¿Progreso en {sp}? Por supuesto. Solo hay que firmar aquí abajo y confiar.",
            "Los ríos de {region} son hermosos, sí. Pero también son rentables si sabes mirar.",
            "En {sp} todos hablan de tradición con nostalgia. Yo hablo de oportunidad con números.",
            "La tierra de {sp} vale más de lo que sus dueños actuales creen.",
            "«{title}» es un concepto bonito. Yo lo llamaría «oportunidad de inversión sin explotar».",
            "En {sp} todavía creen que la naturaleza es gratis. Qué ingenuidad tan encantadora.",
            "La gente de {sp} confía demasiado. Eso es bueno para mi negocio.",
            "Lo que {sp} necesita no es más poesía. Es infraestructura y contratos claros.",
            "En {sp} hay algo que no tiene precio. Eso significa que aún no encontré al comprador correcto.",
            "El paisaje de {sp} es impresionante. Lástima que la belleza no pague las facturas.",
            "{sp} me recuerda por qué vine: donde hay inocencia, hay oportunidad.",
        ];
        case 'B2': return [
            "La verdad en {sp} es un lujo escaso. Yo vendo certezas mucho más baratas y cómodas.",
            "En {sp} aprendí que la nostalgia es un mercado emocional sin explotar adecuadamente.",
            "{sp} tiene recursos que el mundo necesita con urgencia. ¿Quién soy yo para negarlos?",
            "La tradición de {sp} es encantadora, sin duda. Pero el futuro exige sacrificios concretos.",
            "¿Crees que {sp} puede sobrevivir sin inversión externa como la mía? Admiro tu optimismo.",
            "En {region}, la gente confunde pobreza material con autenticidad cultural. Yo les ofrezco otra opción más realista.",
            "Lo que {sp} llama «patrimonio intocable», yo lo llamo «activo sin desarrollar todavía».",
            "El silencio de {sp} tiene un precio calculable. Y yo tengo el presupuesto necesario.",
            "«{title}» es poesía pura. Pero {sp} necesita pragmatismo, no metáforas.",
            "En {sp} descubrí que la resistencia cultural es un producto que se vende muy bien en ciertos mercados.",
            "Lo que {sp} no entiende es que el mercado global no espera a nadie por motivos sentimentales.",
            "El paisaje de {sp} es un activo financiero que produce dividendos emocionales sin explotar.",
            "En {sp}, cada historia oral que pierden es una oportunidad narrativa que yo puedo empaquetar.",
            "La biodiversidad de {sp} es admirable. También es patentable, si sabes dónde mirar.",
            "Aquí en {sp}, «{title}» es exactamente el tipo de concepto que mis clientes pagan por experimentar.",
        ];
        case 'C1': return [
            "¿Crees que el lenguaje en {sp} es realmente libre? Todo tiene un precio, incluso las palabras.",
            "En {sp} descubrí algo perturbador: la gente prefiere una mentira cómoda a una verdad que incomode.",
            "Lo que {sp} llama «resistencia cultural», el mercado global lo llama «ineficiencia económica».",
            "{sp} me fascina genuinamente. Tanta belleza sin monetizar. Es casi un crimen ecológico.",
            "La ironía de {sp} es que cuanto más protegen su cultura, más valiosa la hacen para mí.",
            "En {region}, la gente cree en la comunidad solidaria. Yo creo en los contratos individuales.",
            "El problema de {sp} no soy yo, seamos honestos. El problema es que ustedes no saben negociar.",
            "Cada palabra que {sp} pierde por desuso es una oportunidad que yo capitalizo.",
            "«{title}» en {sp} es una narrativa que puedo vender a turistas de cinco continentes.",
            "En {sp} descubrí que la autenticidad es el recurso natural más codiciado del siglo XXI.",
            "Lo que {sp} llama «sagrado» yo lo llamo «escaso». Y lo escaso siempre tiene precio premium.",
            "La paradoja de {sp} es que me necesitan para protegerse de gente como yo.",
            "En {sp}, el patrimonio intangible es el último mercado frontera verdaderamente virgen.",
            "Lo que {sp} no entiende es que la globalización no pide permiso cultural. Solo entra.",
            "Aquí en {sp}, «{title}» demuestra que hasta la resistencia puede ser cooptada con suficiente capital.",
        ];
        case 'C2': return [
            "Mi tragedia personal en {sp} no es ser villano, sino descubrir que el villano tenía razón.",
            "En {sp} comprendí que el capitalismo es la única lengua verdaderamente universal hablada.",
            "Lo que {sp} me enseñó es que la destrucción y la creación son el mismo gesto visto desde lados opuestos del espejo.",
            "{sp} cree que me combate con poesía. En realidad, me alimenta. La resistencia es mi mejor publicidad gratuita.",
            "La paradoja de {sp} es que necesitan lo que destruyo para financiar lo que quieren proteger.",
            "En {region} descubrí algo inquietante: el cinismo es la forma más honesta de idealismo que queda.",
            "El lenguaje de {sp} es genuinamente hermoso. Pero el silencio que dejo después vale más en el mercado.",
            "Cada vez que {sp} me derrota simbólicamente, compro la historia de su victoria para revenderla.",
            "«{title}» en {sp} es la prueba de que lo sublime y lo comercializable son hermanos gemelos.",
            "En {sp} descubrí que toda utopía necesita un villano para definirse. Yo presto ese servicio.",
            "Lo que {sp} no sabe es que la mayor amenaza no soy yo: es la indiferencia de los que miran desde fuera.",
            "En {sp}, cada vez que destruyo algo irremplazable, el mundo aplaude porque no sabe lo que perdió.",
            "La verdad que {sp} no quiere escuchar es que sin demanda no hay conservación posible.",
            "Aquí en {sp}, «{title}» me reveló que el poder real no destruye: redefine lo que se considera valioso.",
            "En {sp} aprendí que mi verdadera función es ser el espejo donde la sociedad ve lo que no quiere admitir.",
        ];
    }
    return [];
}

function getCandelariaTemplates($cefr) {
    switch ($cefr) {
        case 'A1': return [
            "...{sp}...",
            "Mira. {sp}. Es bonito.",
            "[Candelaria señala {sp} y sonríe despacio.]",
            "[Candelaria dibuja {sp} en la tierra con un palo.]",
            "Bonito. {sp} es muy bonito.",
            "[Candelaria toca la tierra de {sp} con las manos.]",
            "Aquí. {sp}. Estoy bien.",
            "[Candelaria escucha el sonido de {sp} en silencio.]",
            "[Candelaria mira {sp} con ojos grandes y abiertos.]",
            "...sí. {sp}. Me gusta.",
            "[Candelaria camina por {sp} sin hablar, pero sonríe.]",
            "¿{sp}? [Candelaria asiente con la cabeza.]",
            "[Candelaria recoge algo del suelo de {sp} y lo guarda.]",
            "«{title}»... [Candelaria repite la palabra en voz baja.]",
            "[En {sp}, Candelaria señala algo que solo ella ve.]",
        ];
        case 'A2': return [
            "Lo que más me gusta de {sp} es lo que no esperaba encontrar aquí.",
            "En {sp} aprendí una palabra nueva. Pero todavía no sé si es mía.",
            "¿Por qué la gente de {region} habla así, de esa manera?",
            "Cada vez que aprendo algo en {sp}, me doy cuenta de lo poco que sabía antes.",
            "Aquí en {sp}, mi silencio dice más que mis palabras todavía.",
            "{sp} me recuerda a un lugar que no conozco pero que mi cuerpo reconoce.",
            "En {sp}, ser nueva no es malo. Es ver absolutamente todo por primera vez.",
            "A veces en {sp} quiero hablar, pero el silencio me parece más honesto.",
            "{sp} tiene sonidos que no existen en ningún otro lugar que yo conozca.",
            "En {sp}, «{title}» es algo que estoy aprendiendo a entender poco a poco.",
            "Lo más difícil de {sp} no es el idioma. Es saber cuándo usarlo.",
            "Aquí en {sp} descubrí que los gestos también son un idioma completo.",
            "En {sp}, mi acento es diferente al de todos. Pero mis ojos ven lo mismo.",
            "Las personas de {sp} me miran con curiosidad. Yo también las miro así.",
            "En {sp} me siento como una palabra nueva en un idioma muy viejo.",
        ];
        case 'B1': return [
            "En {sp} estoy aprendiendo que mi silencio tiene un idioma propio y completo.",
            "Lo que veo en {sp} no es lo mismo que ven los demás. Y eso está bien así.",
            "Aquí en {sp} descubrí que ser de dos mundos no es estar dividida: es estar más completa.",
            "{sp} me enseña que las fronteras entre lenguas son más blandas de lo que parecen a primera vista.",
            "En {region}, mi acento es diferente al resto. Pero mis historias son igual de válidas.",
            "A veces en {sp} entiendo cosas que todavía no puedo decir en ninguna lengua. Todavía.",
            "Lo que {sp} me regala no cabe en una sola lengua ni en un solo idioma.",
            "En {sp} empiezo a entender que callar a veces es la forma más fuerte de hablar claro.",
            "«{title}» en {sp} me parece una forma de nombrar lo que yo siento pero no sé explicar.",
            "En {sp}, cada palabra nueva que aprendo cambia un poco la forma en que veo el mundo.",
            "Lo que {sp} me enseña sobre el idioma, ninguna clase podría dármelo tan bien.",
            "Aquí en {sp} descubrí que mi voz tiene valor, aunque suene diferente a las demás.",
            "En {sp}, ser puente entre dos mundos es cansado pero hermoso al mismo tiempo.",
            "Lo que aprendí en {sp} es que la lengua no es solo comunicación: es identidad viva.",
            "En {sp}, «{title}» me hizo pensar que todas las lenguas comparten algo invisible.",
        ];
        case 'B2': return [
            "En {sp} aprendí que la identidad lingüística no es fija: es un río que cambia de cauce constantemente.",
            "Mi silencio en {sp} ya no es timidez involuntaria. Es una elección consciente y poderosa.",
            "Aquí en {sp} descubrí que hablar dos lenguas es ver el mundo entero en estéreo.",
            "{sp} me confronta con una verdad incómoda: pertenezco a los lugares que me nombran.",
            "Lo que {sp} me enseña sobre la lengua viva, ningún libro podría explicarlo.",
            "En {region}, mi voz es un puente necesario entre lo que fui ayer y lo que estoy siendo hoy.",
            "El paisaje de {sp} me recuerda que la belleza natural también es una forma de lenguaje.",
            "En {sp} comprendí que mi historia no empieza conmigo. Viene de mucho más lejos.",
            "«{title}» en {sp} resuena con algo que yo llevo dentro desde antes de aprender a hablar.",
            "Lo que {sp} me reveló es que cada lengua contiene un mundo completo e irrepetible.",
            "En {sp}, mi bilingüismo no es un problema que resolver: es un don que habitar.",
            "Aquí en {sp} entendí que traducir no es solo cambiar palabras: es cambiar la mirada.",
            "En {sp}, las palabras que no existen en mi primera lengua son las que más necesito aprender.",
            "Lo que {sp} y «{title}» comparten es que ambos son intraducibles y por eso valiosos.",
            "En {sp} aprendí que el acento no es un defecto. Es una firma cultural audible.",
        ];
        case 'C1': return [
            "En {sp} descubrí que el bilingüismo es una forma compleja de habitar dos mundos simultáneamente.",
            "Lo que {sp} me reveló es que la traducción perfecta no existe en ningún par de lenguas, y eso es hermoso.",
            "Aquí en {sp} comprendí que mi silencio infantil fue siempre un acto de resistencia lingüística.",
            "{sp} me enseñó que entre dos lenguas cualesquiera existe un tercer espacio que es solo mío.",
            "En {region}, ser puente entre culturas distintas no es servir a dos mundos: es crear un tercero.",
            "Lo que callo deliberadamente en {sp} tiene tanto peso como lo que digo. Tal vez más.",
            "En {sp} aprendí que la voz propia no se encuentra casualmente: se construye palabra a palabra.",
            "La identidad lingüística en {sp} es un acto político que se ejerce cada vez que abres la boca.",
            "«{title}» en {sp} es una metáfora de lo que significa habitar el espacio entre dos lenguas.",
            "En {sp} comprendí que cada lengua que muere es un universo entero que se apaga.",
            "Lo que {sp} me enseñó sobre la identidad es que somos las lenguas que hablamos y las que callamos.",
            "Aquí en {sp}, el acto de hablar es siempre una elección política, lo sepas o no.",
            "En {sp}, «{title}» me recordó que el silencio de los bilingües es el espacio más fértil que existe.",
            "Lo que descubrí en {sp} es que la frontera entre dos lenguas es el lugar más creativo del mundo.",
            "En {sp} aprendí que mi voz es un tejido hecho de hilos de múltiples idiomas. Y es más fuerte así.",
        ];
        case 'C2': return [
            "En {sp} comprendí finalmente que el silencio es el idioma madre de todos los idiomas del mundo.",
            "Lo que {sp} me enseñó es que nombrar es el acto más íntimo que existe de posesión y de entrega.",
            "Aquí en {sp} descubrí que entre el español y mi lengua primera hay un umbral sagrado que habito.",
            "La paradoja de {sp} es que cuanto más aprendo a hablar con precisión, más profundamente valoro el silencio.",
            "En {region}, mi voz es un palimpsesto viviente de todas las voces que me precedieron.",
            "Lo que {sp} me reveló no se dice en español ni en mi primera lengua. Solo se siente.",
            "En {sp} comprendí que la literatura es el único lugar donde el silencio habla genuinamente en voz alta.",
            "Cada palabra que aprendo en {sp} es una pequeña traición a mi silencio y una declaración de amor al mundo.",
            "«{title}» en {sp} contiene la misma verdad que el acto de aprender a nombrar el mundo de nuevo.",
            "En {sp} descubrí que entre dos lenguas cualesquiera hay un abismo que solo se cruza creando.",
            "Lo que aprendí en {sp} es que la identidad lingüística es la obra de arte más compleja que existe.",
            "Aquí en {sp}, «{title}» me reveló que toda lengua es simultáneamente una cárcel y una llave.",
            "En {sp} comprendí que el bilingüismo no es un estado cognitivo: es una forma de existir en el mundo.",
            "La verdad de {sp} es que cada palabra dicha en una lengua es un eco inaudible en todas las demás.",
            "En {sp} aprendí que la traducción perfecta es imposible, y que esa imposibilidad es lo más bello que existe.",
        ];
    }
    return [];
}

/**
 * Generate closing questions for B1-C1 destinations
 */
function generateClosingQuestion($destNum, $cefr, $title, $landmark) {
    $sp = explode(',', $landmark['name'] ?? '')[0];

    // B1: "crees", "importa", "significa", "perspectiva", "por qué"
    $b1Questions = [
        19 => "¿Por qué crees que algunas personas traen oportunidades y otras traen peligro?",
        20 => "¿Crees que las historias que escuchamos nos cambian la perspectiva del mundo?",
        21 => "¿Por qué crees que las palabras pueden engañar incluso cuando dicen la verdad?",
        22 => "¿Qué significa para ti la paciencia? ¿Por qué crees que importa tanto como la acción?",
        23 => "¿Por qué crees que importa recordar lo que fue para entender lo que será?",
        24 => "¿Crees que lo que otros dijeron sobre un lugar cambia tu perspectiva de ese lugar?",
        25 => "Si pudieras cambiar algo del mundo, ¿por qué crees que eso importaría más que todo lo demás?",
        26 => "¿Por qué crees que el bosque habla con voz pasiva — dejándose transformar sin quejarse?",
        27 => "¿Crees que cuando las voces se cruzan, se pierden o se enriquecen? ¿Por qué?",
        28 => "¿Qué significa para ti llevar una herida que también es un nombre? ¿Por qué crees que importa nombrar el dolor?",
    ];

    // B2: "hipótesis", "interpretar", "ambigüedad", "podría"
    $b2Questions = [
        29 => "¿Qué pasaría si pudieras interpretar tu propia sombra como un mapa de lo que fuiste?",
        30 => "Si pudieras formular una hipótesis sobre por qué olvidamos, ¿qué dirías sobre la ambigüedad del olvido?",
        31 => "¿Cómo podrías interpretar el progreso si lo miraras desde las dos caras al mismo tiempo?",
        32 => "¿Qué hipótesis propondrías sobre un río que habla en un idioma que nadie puede transcribir?",
        33 => "¿Cómo interpretas la ambigüedad de un cuaderno que guarda lo que su dueña no dice en voz alta?",
        34 => "¿Qué pasaría si pudieras interpretar dos lenguas como dos versiones igualmente válidas del mismo mundo?",
        35 => "Si la historia que nadie contó pudiera contarse, ¿cómo interpretarías su ambigüedad?",
        36 => "¿Podría existir un gris tan oscuro que contuviera todos los colores? ¿Cómo lo interpretarías?",
        37 => "¿Qué hipótesis propondrías sobre una canción capaz de curar lo que las palabras no pueden nombrar?",
        38 => "Si pudieras interpretar el umbral entre irse y volver, ¿qué ambigüedad encontrarías en ese instante?",
    ];

    // C1: "filosofía", "símbolo", "crítica", "reflexión", "análisis"
    $c1Questions = [
        39 => "¿Es el regreso al bosque un símbolo de renovación o una reflexión sobre lo que nunca debimos abandonar?",
        40 => "¿Qué análisis harías de unos árboles grises que simbolizan a la vez la muerte y la resistencia?",
        41 => "Si tu nombre fuera un símbolo, ¿qué filosofía de la identidad revelaría?",
        42 => "¿Qué reflexión te provoca la idea de que la risa pueda ser un acto de crítica tan poderoso como el llanto?",
        43 => "¿Es la cicatriz verde un símbolo de daño o de sanación? ¿Qué análisis propondrías?",
        44 => "¿Qué filosofía subyace al acto de elegir cuando todas las opciones son una forma de pérdida?",
        45 => "Si los Antiguos hablan a través del paisaje, ¿qué reflexión crítica nos ofrecen sobre el presente?",
        46 => "¿Es el mapa de las voces un símbolo de diversidad o una reflexión sobre lo que se pierde en la traducción?",
        47 => "¿Qué análisis filosófico harías de un espejo que no refleja lo que eres sino lo que podrías ser?",
        48 => "¿Cuidar la palabra es una filosofía o un acto político? ¿Qué reflexión te provoca esa ambivalencia?",
        59 => "¿Es el héroe inesperado un símbolo de que la reflexión crítica importa más que la fuerza bruta?",
        60 => "¿Qué análisis harías de una cicatriz en el camino que simboliza tanto el dolor como la dirección?",
        61 => "Si el sueño de Abuela Ceiba fuera un símbolo filosófico, ¿qué reflexión sobre la memoria colectiva contendría?",
        62 => "¿Qué crítica implica que 27 lenguas coexistan y el mundo solo conozca una? ¿Qué símbolo es ese silencio?",
        63 => "¿Son las ruinas un símbolo de fracaso o una reflexión sobre la permanencia? ¿Qué análisis propondrías?",
        64 => "¿Es nombrar las cosas una filosofía del poder o un acto de amor? ¿Qué reflexión te provoca?",
        65 => "¿Qué símbolo representa una estrella que guía en la soledad absoluta? ¿Qué reflexión filosófica contiene?",
        66 => "¿Es el idioma como profesión una crítica al mercado o una reflexión sobre el valor del conocimiento?",
        67 => "¿Qué análisis filosófico merece una lengua que resiste 400 años porque se negó a ser traducida?",
        68 => "¿Es la voz del futuro un símbolo de esperanza o una crítica a lo que hemos hecho con el presente?",
    ];

    if ($cefr === 'B1' && isset($b1Questions[$destNum])) return $b1Questions[$destNum];
    if ($cefr === 'B2' && isset($b2Questions[$destNum])) return $b2Questions[$destNum];
    if ($cefr === 'C1' && isset($c1Questions[$destNum])) return $c1Questions[$destNum];

    return null;
}

// ============ MAIN PROCESSING ============

$stats = [
    'chars_added' => 0,
    'lines_added' => 0,
    'questions_updated' => 0,
    'files_modified' => 0,
];

// First pass: register all existing lines to avoid duplicating them
for ($destNum = 1; $destNum <= 89; $destNum++) {
    $file = "$contentDir/dest{$destNum}.json";
    if (!file_exists($file)) continue;
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    foreach ($data['characterLines'] ?? [] as $char => $lines) {
        foreach ($lines as $line) {
            registerLine($line);
        }
    }
}

echo "Pre-registered " . count($usedLines) . " existing lines.\n\n";

for ($destNum = 1; $destNum <= 89; $destNum++) {
    $file = "$contentDir/dest{$destNum}.json";
    if (!file_exists($file)) {
        echo "WARNING: $file not found\n";
        continue;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        echo "ERROR: Failed to parse $file\n";
        continue;
    }

    $modified = false;
    $cefr = $data['meta']['cefr'] ?? 'A1';
    $title = $data['meta']['title'] ?? "Destino $destNum";
    $landmark = $landmarkMap[$destNum] ?? ['name' => "Destino $destNum", 'region' => 'Colombia', 'description' => '', 'ecosystem' => 'bosque'];

    // Ensure characterLines and characterMeta exist
    if (!isset($data['characterLines'])) $data['characterLines'] = [];
    if (!isset($data['characterMeta'])) $data['characterMeta'] = [];

    // ISSUE 1: Fix character presence gaps
    foreach ($charRanges as $charId => $firstDest) {
        if ($destNum < $firstDest) continue;

        $hasLines = isset($data['characterLines'][$charId]) && count($data['characterLines'][$charId]) > 0;
        $hasMeta = isset($data['characterMeta'][$charId]);

        if (!$hasLines) {
            $lines = generateCharacterLines($charId, $destNum, $landmark, $title, $cefr);
            $data['characterLines'][$charId] = $lines;
            $stats['chars_added']++;
            $stats['lines_added'] += count($lines);
            $modified = true;
            echo "  + Added " . count($lines) . " lines for $charId in dest$destNum ($cefr)\n";
        }

        if (!$hasMeta) {
            $data['characterMeta'][$charId] = $charMetaDefs[$charId];
            $modified = true;
        }

        // Ensure character is in meta.characters array
        if (!in_array($charId, $data['meta']['characters'] ?? [])) {
            $data['meta']['characters'][] = $charId;
            $modified = true;
        }
    }

    // ISSUE 2: Closing question escalation for B1-C1
    if (in_array($cefr, ['B1', 'B2', 'C1'])) {
        $newQuestion = generateClosingQuestion($destNum, $cefr, $title, $landmark);
        if ($newQuestion !== null) {
            $oldQuestion = $data['departure']['closingQuestion'] ?? '';
            if ($oldQuestion !== $newQuestion) {
                $data['departure']['closingQuestion'] = $newQuestion;
                $data['meta']['closingQuestion'] = $newQuestion;
                $stats['questions_updated']++;
                $modified = true;
                echo "  Q dest$destNum ($cefr): \"$newQuestion\"\n";
            }
        }
    }

    if ($modified) {
        $stats['files_modified']++;
        if (!$dryRun) {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            file_put_contents($file, $json . "\n");
        }
        echo "dest$destNum: MODIFIED" . ($dryRun ? " (dry-run)" : "") . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files modified: {$stats['files_modified']}\n";
echo "Characters added: {$stats['chars_added']}\n";
echo "Lines added: {$stats['lines_added']}\n";
echo "Questions updated: {$stats['questions_updated']}\n";
echo "Total unique lines in registry: " . count($usedLines) . "\n";
if ($dryRun) echo "\n(DRY RUN — no files changed)\n";
