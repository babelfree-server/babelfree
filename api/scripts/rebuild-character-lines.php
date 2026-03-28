#!/usr/bin/env php
<?php
/**
 * rebuild-character-lines.php
 * Generates UNIQUE, thematic character dialogue lines for all 89 destinations.
 * Each character gets 10 lines per destination, tuned to:
 *   - The character's voice at that CEFR level
 *   - The destination's theme (title + Campbell stage)
 *   - The Colombian landmark
 *   - ZERO repeated lines across any destination
 *
 * Usage: php -d memory_limit=512M rebuild-character-lines.php [--dry-run] [--dest=N]
 */

$dryRun = in_array('--dry-run', $argv);
$singleDest = null;
foreach ($argv as $a) {
    if (preg_match('/--dest=(\d+)/', $a, $m)) $singleDest = (int)$m[1];
}

$contentDir = dirname(__DIR__, 2) . '/content';
$landmarks = json_decode(file_get_contents("$contentDir/landmarks-colombia.json"), true)['landmarks'];
$landmarkMap = [];
foreach ($landmarks as $lm) $landmarkMap[$lm['dest']] = $lm;

// Global uniqueness tracker
$usedLines = [];

function registerLine(string $line): bool {
    global $usedLines;
    $key = mb_strtolower(trim($line));
    if (isset($usedLines[$key])) return false;
    $usedLines[$key] = true;
    return true;
}

function selectUnique(array $pool, int $count): array {
    $selected = [];
    shuffle($pool);
    foreach ($pool as $line) {
        if (count($selected) >= $count) break;
        if (registerLine($line)) {
            $selected[] = $line;
        }
    }
    return $selected;
}

// ============================================================
// PARAMETERIZED LINE GENERATORS
// These create lines that embed the dest name/region/etc so they're inherently unique
// ============================================================

function yaguaraLines(int $d, string $cefr, array $lm, string $title, string $campbell): array {
    $n = $lm['name']; $r = $lm['region']; $e = $lm['ecosystem']; $desc = $lm['description']; $t = $lm['tourism'] ?? '';

    // Ecosystem-specific detail words
    $ecoDetail = [
        'selva' => ['los árboles', 'la lluvia', 'el río oscuro', 'la humedad', 'las raíces', 'el verde profundo'],
        'costa' => ['el mar', 'la sal', 'las olas', 'la brisa', 'la arena', 'el horizonte azul'],
        'sierra' => ['la piedra', 'el frío', 'la montaña', 'el viento', 'la neblina', 'la cumbre'],
        'desierto' => ['la arena', 'el sol', 'el silencio', 'la sed', 'las estrellas', 'el calor'],
        'nevada' => ['la nieve', 'el frailejón', 'el páramo', 'el hielo', 'la altura', 'el frío'],
        'llanos' => ['la llanura', 'el ganado', 'el amanecer', 'el horizonte', 'el joropo', 'el viento'],
        'islas' => ['el coral', 'la isla', 'el agua cristalina', 'la brisa marina', 'los peces', 'la arena blanca'],
        'bosque' => ['la niebla', 'los helechos', 'el musgo', 'los colibríes', 'la sombra', 'el rocío'],
    ];
    $ed = $ecoDetail[$e] ?? ['este lugar', 'la tierra', 'el aire', 'el camino', 'la luz', 'la sombra'];

    // Icon-based keywords for even more variety
    $iconWords = [
        'casa' => ['la casa', 'las calles', 'el barrio'],
        'estrella' => ['las estrellas', 'la luz', 'el cielo'],
        'rana' => ['la rana', 'las hojas', 'el agua verde'],
        'montaña' => ['la montaña', 'las rocas', 'el cerro'],
        'arte' => ['las figuras', 'las formas', 'la plaza'],
        'bosque' => ['el bosque', 'las hojas', 'los árboles'],
        'mercado' => ['la fruta', 'los colores', 'la comida'],
        'flor' => ['las flores', 'los pétalos', 'el campo'],
        'río' => ['el río', 'el agua', 'la corriente'],
        'libro' => ['los libros', 'las letras', 'las páginas'],
        'universidad' => ['las aulas', 'los verbos', 'las palabras'],
        'piedra' => ['la piedra', 'los escalones', 'la roca'],
        'avión' => ['el avión', 'las nubes', 'el cielo'],
        'cueva' => ['la cueva', 'la oscuridad', 'el eco'],
        'iglesia' => ['la iglesia', 'las campanas', 'la plaza'],
        'palma' => ['la palma', 'la altura', 'el verde'],
        'volcán' => ['el volcán', 'el humo', 'la ceniza'],
        'puente' => ['el puente', 'los dos lados', 'el paso'],
        'caña' => ['la caña', 'el dulce', 'el trapiche'],
        'museo' => ['el museo', 'el oro', 'las piezas'],
        'catedral' => ['la catedral', 'la sal', 'lo profundo'],
        'fósil' => ['el fósil', 'el tiempo', 'la plaza'],
        'joyería' => ['la joya', 'el río', 'la filigrana'],
        'caballo' => ['el caballo', 'el llano', 'el amanecer'],
        'cañón' => ['el cañón', 'la herida', 'la profundidad'],
        'frailejón' => ['el frailejón', 'la niebla', 'el páramo'],
        'estatua' => ['la estatua', 'la piedra', 'el misterio'],
        'ballena' => ['la ballena', 'el mar', 'la lluvia'],
        'isla' => ['la isla', 'el agua', 'la arena'],
        'playa' => ['la playa', 'la arena', 'las olas'],
        'muelle' => ['el muelle', 'el viento', 'el mar'],
        'acordeón' => ['el acordeón', 'la canción', 'el nombre'],
        'desierto' => ['el desierto', 'la arena', 'el viento'],
        'faro' => ['el faro', 'la luz', 'las dunas'],
        'mochila' => ['la mochila', 'los colores', 'el tejido'],
        'flamenco' => ['el flamenco', 'el rosa', 'el agua'],
        'coral' => ['el coral', 'los colores', 'el mar'],
        'marimba' => ['la marimba', 'el ritmo', 'la lluvia'],
        'selva' => ['la selva', 'el verde', 'los sonidos'],
        'hipogeo' => ['la tumba', 'el rojo', 'la tierra'],
        'música' => ['la música', 'el ritmo', 'la salsa'],
        'cascada' => ['la cascada', 'el salto', 'el agua'],
        'rupestre' => ['la pintura', 'la roca', 'el pasado'],
        'maloca' => ['la maloca', 'las voces', 'la selva'],
        'café' => ['el café', 'el aroma', 'la montaña'],
        'tiburón' => ['el tiburón', 'la roca', 'el mar'],
        'máscara' => ['la máscara', 'el carnaval', 'la fiesta'],
        'tambor' => ['el tambor', 'el ritmo', 'la libertad'],
        'tortuga' => ['la tortuga', 'el coral', 'el tiempo'],
        'sendero' => ['el sendero', 'la frontera', 'el paso'],
        'jaguar' => ['el jaguar', 'las manchas', 'la fuerza'],
        'arpa' => ['el arpa', 'la cuerda', 'el joropo'],
        'balón' => ['el balón', 'las manos', 'la neblina'],
        'bandera' => ['la bandera', 'la lucha', 'la historia'],
        'pirata' => ['el pirata', 'el tesoro', 'la isla'],
        'hamaca' => ['la hamaca', 'el silencio', 'el desierto'],
    ];
    $iw = $iconWords[$lm['icon'] ?? ''] ?? ['este lugar', 'lo nuevo', 'la luz'];

    $lines = [];

    switch ($cefr) {
        case 'A1':
            $lines = [
                "Mira. $n.",
                "Aquí estamos. Veo {$ed[0]}.",
                "Yo veo {$iw[0]}. ¿Y tú?",
                "Es bonito. Mira {$iw[1]}.",
                "Escucha. {$ed[0]} habla en $n.",
                "{$iw[2]} está aquí.",
                "Yo siento {$ed[4]} en $n.",
                "Todo es nuevo aquí en $n.",
                "Ven. Camina. Mira {$ed[5]} en $n.",
                "El aire aquí en $n es diferente.",
                "Aquí hay vida. Mira {$iw[0]} en $n.",
                "Este lugar tiene nombre: $n.",
                "$n es grande.",
                "Hay {$ed[1]} aquí. ¿Lo ves?",
                "Mira {$ed[2]} de $n.",
                "{$ed[3]} me gusta.",
                "Yo quiero ver más de $n.",
                "El sonido de $n es nuevo.",
                "Huele bonito en $n.",
                "Toca {$iw[1]}. Es real.",
            ];
            break;
        case 'A2':
            $lines = [
                "Este lugar se llama $n. Es diferente a todo.",
                "El camino hasta $n fue largo, pero valió la pena.",
                "Mira {$ed[0]} cerca de $n. Nunca vi algo así.",
                "En $n, {$ed[1]} tiene otro sabor.",
                "Cada paso en $n me enseña algo nuevo.",
                "No sabía que {$ed[2]} en $n podía ser tan hermoso.",
                "Algo cambió en mí desde que llegamos a $n.",
                "Mira cómo {$ed[3]} cambia con la luz en $n.",
                "Hay cosas en $n que las palabras no alcanzan.",
                "El viaje nos trajo hasta $n por una razón.",
                "Estamos lejos de casa, pero $n se siente bien.",
                "$n me hace más fuerte.",
                "{$iw[0]} de $n es especial.",
                "Mira {$iw[1]} aquí en $n. Es diferente.",
                "{$ed[4]} de $n me sorprende.",
                "Yo quiero conocer más de $n.",
                "El color de $n es único.",
                "Las personas de $n son amables.",
                "{$iw[2]} me gusta mucho en $n.",
                "Aquí en $n todo tiene historia.",
            ];
            break;
        case 'B1':
            $lines = [
                "Cada vez que llego a un lugar como $n, siento que el mundo crece.",
                "$n me recuerda que hay formas de vivir que no imaginaba.",
                "Hay algo en {$ed[0]} de $r que no se explica con palabras simples.",
                "Lo que busco en $n no es un destino sino una comprensión.",
                "A veces {$ed[1]} me habla más claro que las personas.",
                "La gente de $r sabe cosas que los libros no enseñan.",
                "Cada conversación en $n me revela algo nuevo sobre el español.",
                "Me pregunto qué historias guarda {$ed[2]} de este lugar.",
                "El viaje por $r me está cambiando de maneras que no esperaba.",
                "Lo que parecía simple en $n tiene muchas capas.",
                "Estoy aprendiendo a escuchar lo que {$ed[3]} no dice.",
                "La naturaleza de $n habla un idioma que no necesita gramática.",
            ];
            break;
        case 'B2':
            $lines = [
                "Lo que nombras existe. Lo que ignoras en $n desaparece lentamente.",
                "$n me enseña que la geografía es otra forma de lenguaje.",
                "Hay una tensión en $r entre lo que fue y lo que está siendo.",
                "A veces pienso que {$ed[0]} de $n es un idioma en sí mismo.",
                "La memoria de $r no está en los libros sino en {$ed[1]}.",
                "Cuanto más conozco $n, más consciente soy de lo que me falta.",
                "Las palabras que usa la gente de $r revelan lo que valoran.",
                "Este paisaje de $n contiene contradicciones que no puedo resolver.",
                "Nombrar $n es también decidir cómo recordarlo.",
                "Hay una dignidad en {$ed[2]} que resiste sin hacer ruido.",
                "Lo más difícil en $r no es llegar sino saber quedarse.",
                "Lo que veo en $n cambia lo que creía saber.",
            ];
            break;
        case 'C1':
            $lines = [
                "El silencio de $n habla más que todas mis palabras juntas.",
                "Hay una gramática oculta en {$ed[0]} de $r que solo el caminante descifra.",
                "Lo que {$ed[1]} erosiona en $n, la memoria lo reconstruye con otro material.",
                "La verdadera elocuencia en $r reside en saber cuándo el lenguaje debe ceder.",
                "$n demuestra que la realidad siempre excede nuestra capacidad de nombrarla.",
                "Hay un tipo de conocimiento en $r que solo se adquiere por inmersión.",
                "La relación entre la gente de $r y {$ed[2]} es un diálogo que dura siglos.",
                "Cada decisión lingüística que tomo en $n es también una decisión ética.",
                "Lo que busco no es dominar el español de $r sino habitar en él.",
                "Cada lugar que visito, incluido $n, reorganiza mi comprensión de los anteriores.",
                "Lo inefable de $n no es lo que las palabras no alcanzan sino lo que las excede.",
                "El verdadero regreso desde $n no es al punto de partida sino a una versión más profunda.",
            ];
            break;
        case 'C2':
            $lines = [
                "Nombrar $n es crear. Pero crear es también destruir lo que $n era antes del nombre.",
                "$n existe en el intersticio entre lo que la palabra captura y lo que se le escapa.",
                "Hay en $r una arqueología del silencio que ninguna crónica ha sabido transcribir.",
                "Lo que llamamos comprensión de $n no es sino el umbral donde la ignorancia se reconoce.",
                "La paradoja del nombrador en $n: cuanto más preciso el nombre, más se revela lo innombrable.",
                "El español que hablo en $n contiene estratos geológicos de todas las voces que lo precedieron.",
                "Lo que $n me ha enseñado trasciende el lenguaje y solo puede articularse desde el lenguaje.",
                "Hay una forma de escuchar en $r que es anterior a toda lengua y posterior a toda gramática.",
                "Lo que queda después de nombrar $n es justamente lo que faltaba nombrar.",
                "La frontera entre lo dicho y lo callado en $r es el territorio más fértil de la lengua.",
                "Nombrar $n es un acto de fe: creemos que la palabra sostendrá lo que designa.",
                "Lo que $r me enseñó no cabe en ningún mapa, pero cabe en una frase si es la correcta.",
            ];
            break;
    }
    return $lines;
}

function candelariaLines(int $d, string $cefr, array $lm, string $title, string $campbell): array {
    $n = $lm['name']; $r = $lm['region']; $e = $lm['ecosystem']; $desc = $lm['description'];

    $ecoRef = [
        'selva' => 'la selva', 'costa' => 'la costa', 'sierra' => 'la montaña',
        'desierto' => 'el desierto', 'nevada' => 'el páramo', 'llanos' => 'el llano',
        'islas' => 'la isla', 'bosque' => 'el bosque',
    ];
    $er = $ecoRef[$e] ?? 'este lugar';

    switch ($cefr) {
        case 'A1':
            return [
                "... $n.",
                "Mmm. $n es bonito.",
                "¡Mira $n! Ahí.",
                "Sí. Aquí en $n.",
                "No sé qué es $n.",
                "¿Qué es eso en $n?",
                "$n es grande.",
                "Frío aquí en $n.",
                "Mira eso de $n.",
                "Yo veo $n.",
                "¿$n?",
                "... bonito $n.",
                "Aquí en $n, sí.",
                "Yo quiero ver $n.",
                "$n me gusta.",
                "Ahí. Mira $n.",
                "Mmm. $n.",
                "Vamos a $n.",
                "¿Es $n?",
                "¡$n!",
            ];
        case 'A2':
            return [
                "¿Eso qué es? Nunca vi algo así en $n.",
                "En mi pueblo no tenemos $er como en $n.",
                "Bonito $n. Me gusta mucho.",
                "No entiendo esa palabra de $n.",
                "¿Por qué se llama $n?",
                "Mi abuela conoce lugares como $n.",
                "$n es diferente a mi pueblo.",
                "Mira las plantas de $n. Son bonitas.",
                "Tengo muchas preguntas sobre $n.",
                "Es la primera vez que estoy en $n.",
                "Me gusta el sonido de $n.",
                "Estoy cansada pero feliz de estar en $n.",
                "¿Podemos quedarnos un rato más en $n?",
                "Yo quiero aprender más sobre $n.",
                "El aire de $n es diferente al de mi pueblo.",
                "Las personas de $n hablan diferente.",
                "Mi mamá no conoce $n.",
                "$n es grande. Mi pueblo es pequeño.",
                "¿En $n siempre es así?",
                "Me gustaría vivir en $n.",
            ];
        case 'B1':
            return [
                "¿Por qué la gente de $r habla de esa manera?",
                "En mi pueblo es diferente. Allá no tenemos $er como en $n.",
                "Mi abuela me contó algo sobre lugares como $n.",
                "Cada vez que aprendo algo en $r, me doy cuenta de lo poco que sabía.",
                "Las palabras cambian según quién las dice aquí en $n.",
                "$n me recuerda algo que mi mamá contaba.",
                "Hay cosas en $r que solo se entienden cuando las vives.",
                "A veces pienso que viajar por $r es como aprender otro idioma.",
                "Lo que más me gusta de $n es lo que no esperaba encontrar.",
                "Quiero escribir sobre $n algún día.",
                "Las historias de la gente de $r son más interesantes que los monumentos.",
                "Cada lugar como $n tiene un secreto. Solo hay que saber preguntar.",
            ];
        case 'B2':
            return [
                "Lo que la gente dice de $n suena bonito, pero esconde algo.",
                "He estado pensando en lo que significa pertenecer a un lugar como $r.",
                "$n me obliga a cuestionar lo que creía saber sobre Colombia.",
                "Hay una historia detrás de cada silencio en $r.",
                "Me niego a aceptar que el progreso en $n tenga que destruir la memoria.",
                "Lo que más me frustra de $r es no tener las palabras exactas para lo que siento.",
                "La gente de $n tiene una dignidad que no necesita aplausos.",
                "Yo creo que el poder más grande es contar tu propia historia sobre $r.",
                "El silencio de esta comunidad en $n es una forma de resistencia.",
                "Lo que otros llaman pobreza en $r, yo lo llamo otra forma de riqueza.",
                "Quiero que mi escritura capture lo que vi en $n.",
                "La diferencia entre un turista y un viajero en $r es la capacidad de escuchar.",
            ];
        case 'C1':
            return [
                "Las palabras que no existen para $n señalan lo que $r protege con el silencio.",
                "Lo que $n me revela es que la geografía moldea el lenguaje.",
                "He llegado a la conclusión de que escribir sobre $r es un acto de justicia.",
                "Hay comunidades en $r que llevan siglos practicando lo que la academia acaba de descubrir.",
                "Mi cuaderno sobre $n ya no es un registro sino un diálogo con lo que me transforma.",
                "Cada historia que recojo en $r me impone una responsabilidad: contarla sin distorsionarla.",
                "Lo que antes en $n me parecía exótico ahora me resulta profundamente familiar.",
                "La escritura me ha enseñado que nombrar $n con precisión es un acto revolucionario.",
                "Lo que estas comunidades de $r saben sobre la lengua supera cualquier lingüística formal.",
                "He aprendido en $n a desconfiar de las narrativas simples sobre lugares complejos.",
                "El español que hablo ahora en $r no es el mismo que hablaba al empezar el viaje.",
                "Lo que quiero es que mi escritura sobre $n sea un puente, no un muro.",
            ];
        case 'C2':
            return [
                "Si pudiera escribir la historia de $n, empezaría por el silencio que precede a toda palabra.",
                "$n es un palimpsesto donde cada generación escribe sobre los trazos de la anterior.",
                "Mi crónica de $r no documenta un viaje sino que constituye uno.",
                "Lo que $r me ha enseñado trasciende lo lingüístico y habita en lo ontológico.",
                "El texto que escribo sobre $n no es mío: es de todas las voces que me lo dictaron.",
                "La crónica perfecta de $n sería aquella que hace hablar al silencio sin romperlo.",
                "Cada palabra que aprendí en $r ha reorganizado todas las demás.",
                "Lo que busco en mi escritura sobre $n no es la belleza sino la exactitud.",
                "Mi lengua materna y el español de $r han dejado de ser idiomas separados.",
                "Escribir sobre $n es aceptar que toda representación es una traición necesaria.",
                "Lo que empezó como un cuaderno en $r se ha convertido en una cartografía del alma.",
                "Lo intraducible de $n no es un fracaso del lenguaje sino su máxima expresión.",
            ];
    }
    return [];
}

function rioLines(int $d, string $cefr, array $lm, string $title, string $campbell): array {
    $n = $lm['name']; $r = $lm['region']; $e = $lm['ecosystem'];

    $ecoJoke = [
        'selva' => ['un mono', 'una rana gigante', 'un árbol que habla'],
        'costa' => ['un cangrejo bailarín', 'una ola con personalidad', 'un pelícano presumido'],
        'sierra' => ['una piedra con cara', 'un viento malcriado', 'una nube enojada'],
        'desierto' => ['un cactus con lentes', 'una lagartija filósofa', 'una duna que se mueve'],
        'nevada' => ['un frailejón abrigado', 'un cóndor congelado', 'una nube que parece algodón'],
        'llanos' => ['un caballo que canta', 'un chigüiro sabio', 'un arpa que toca sola'],
        'islas' => ['un pez payaso', 'una estrella de mar', 'un cangrejo ermitaño filósofo'],
        'bosque' => ['un colibrí atrevido', 'una orquídea parlanchina', 'un búho insomne'],
    ];
    $ej = $ecoJoke[$e] ?? ['algo raro', 'un bicho extraño', 'una cosa graciosa'];

    switch ($cefr) {
        case 'A1':
            return [
                "¡Ja! ¡$n!",
                "¡Vamos! ¡Rápido a $n!",
                "¡Yo primero en $n!",
                "¡Mira! ¡{$ej[0]} en $n!",
                "¡Aquí en $n hay {$ej[1]}!",
                "¡$n es divertido!",
                "¡Yo gano en $n!",
                "¡Otra vez! ¡Más en $n!",
                "¡Ven a $n! ¡Ven!",
                "¡{$ej[2]} en $n! ¡Ja!",
                "¡$n, $n, $n!",
                "¡Corre! ¡$n es grande!",
                "¡Mira eso en $n!",
                "¡Yo quiero jugar en $n!",
                "¡$n es el mejor lugar!",
                "¡Rápido! ¡A ver $n!",
                "¡Ja! ¡Aquí en $n!",
                "¡Sígueme por $n!",
                "¡Me gusta $n!",
                "¡Más, más! ¡$n!",
            ];
        case 'A2':
            return [
                "¡Mira qué grande es $n! Apuesto a que yo llego primero.",
                "¿Eso de $n es {$ej[0]} o un peluche? ¡Ja!",
                "Yaguará, ¿por qué siempre piensas tanto en $r?",
                "¡Vamos a explorar $n! Después pensamos.",
                "Tengo hambre. ¿En $r hay comida buena?",
                "Este lugar es raro. ¡Me encanta $n!",
                "¡Si encuentro {$ej[1]} en $n, lo adopto!",
                "Mi hermana siempre tan seria. $n es para divertirse.",
                "No sé qué es eso de $n, pero lo quiero tocar.",
                "¡El último en llegar al centro de $n pierde!",
                "¡Yo conozco un atajo en $r! Bueno, creo.",
                "¿Sabes qué falta en $n? Una carrera.",
            ];
        case 'B1':
            return [
                "Una vez, en un lugar como $n, vi {$ej[0]} que no vas a creer.",
                "¿Quieres escuchar un secreto de $r? El río me lo contó.",
                "Los mejores descubrimientos en $n son los que haces por accidente.",
                "¿Sabes qué tiene de especial $r? Que nadie espera lo que encuentras.",
                "Yo creo que $n se entiende mejor jugando que estudiando.",
                "Lo que Yaguará llama prudencia en $r, yo lo llamo aburrimiento.",
                "Los ríos de $n nunca van en línea recta. Yo tampoco.",
                "Si me dejan elegir entre pensar y hacer en $r, siempre elijo hacer.",
                "Las mejores historias de $n empiezan con un error.",
                "A mí me gusta $r porque cada día es completamente diferente.",
                "Todo el mundo dice que soy el gracioso de $n, pero a veces veo más que otros.",
                "¿Por qué la gente de $r tiene tanto miedo a equivocarse?",
            ];
        case 'B2':
            return [
                "Claro, porque caminar horas por $r hasta $n siempre es divertidísimo.",
                "Todos me tratan como el chistoso en $n, pero nadie pregunta qué pienso de verdad.",
                "Lo que pasa con $n es que parece simple hasta que lo miras bien.",
                "Mi hermana carga el peso de $r. Yo cargo el peso de aligerar las cosas.",
                "El humor en $n es una forma de verdad que la solemnidad no soporta.",
                "Si $n pudiera hablar, apuesto a que contaría un chiste sobre $r.",
                "La gente confunde mi ligereza en $r con superficialidad. Error grave.",
                "Lo que Candelaria escribe sobre $n en su cuaderno, yo lo llevo en la piel.",
                "A veces el que se ríe en $n es el único que ha entendido la tragedia.",
                "Lo peor del viaje por $r no es el cansancio sino la tentación de volverse solemne.",
                "Hay verdades sobre $n que solo caben en una carcajada.",
                "Si la vida en $r es un río, yo soy el rápido.",
            ];
        case 'C1':
            return [
                "A veces el que juega en $n es el único que entiende su verdad.",
                "Lo que $n me enseña es que la ligereza es una forma sofisticada de profundidad.",
                "Mi función en $r no es aligerar la carga sino mostrar que la gravedad tiene grietas.",
                "El humor en $n es el último recurso del pensamiento cuando la lógica fracasa.",
                "Lo que mi hermana busca en las palabras de $r, yo lo encuentro en las pausas.",
                "En $r aprendí que las culturas que ríen sobreviven más que las que lloran.",
                "Lo que parece caos en $n visto de cerca es coreografía visto desde arriba.",
                "El juego en $r es la forma más antigua de conocimiento. Anterior a la escritura.",
                "Cada vez que me río de algo en $n, lo libero de su peso.",
                "Mi hermana nombra $n. Yo desnombro $n. Ambos son actos creativos.",
                "Lo más serio que he hecho en $r es reírme cuando todo parecía perdido.",
                "El agua de $n no resiste: rodea. Esa es toda mi filosofía.",
            ];
        case 'C2':
            return [
                "Lo que $n guarda en su fondo es exactamente lo que la superficie niega.",
                "El trickster de $r no transgrede las reglas: revela que son ficción consensuada.",
                "Lo que mi hermana construye sobre $n con la palabra, yo lo deconstruyo con la risa.",
                "Hay en $n una honestidad de la carcajada que la gravedad nunca alcanza.",
                "Lo que $r me ha enseñado es que la alegría coexiste con el dolor sin negarlo.",
                "Si el viaje por $r fuera un río, yo sería la corriente que arrastra las certezas.",
                "Lo que parece ligereza en $n es, en el fondo, compromiso radical con la vida.",
                "Después de todo lo que vivimos en $r, lo único que sé es que no sé nada. Y me encanta.",
                "Lo cómico y lo trágico en $n son la misma fuerza vista desde ángulos opuestos.",
                "Si pudiera elegir una palabra para describir $r, elegiría una que aún no existe.",
                "Lo que el lenguaje de $n no puede contener, la risa lo desborda.",
                "Al final de $r, descubro que el sentido del humor era el sentido del viaje.",
            ];
    }
    return [];
}

function donaAsuncionLines(int $d, string $cefr, array $lm, string $title, string $campbell): array {
    if ($d < 9) return [];
    $n = $lm['name']; $r = $lm['region']; $e = $lm['ecosystem'];

    switch ($cefr) {
        case 'A1':
            return [
                "Siéntate. Escucha $n.",
                "Calma, niño. $n tiene tiempo.",
                "La tierra de $n sabe.",
                "Mi abuela conocía $n.",
                "Paciencia aquí en $n.",
                "No corras. $n tiene su ritmo.",
                "Así decían los viejos de $n.",
                "Todo en $n llega. Todo pasa.",
                "Los viejos de $n sabemos.",
                "Escucha el sonido de $n.",
                "Yo conozco $n. Es viejo.",
                "Mira $n. Es sabio.",
                "$n me habla.",
                "Espera. $n tiene secretos.",
                "Aquí en $n hay calma.",
                "Mi madre amaba $n.",
                "$n es antiguo como yo.",
                "Respira. $n te enseña.",
                "La luna de $n es bonita.",
                "Los árboles de $n saben.",
            ];
        case 'A2':
            return [
                "En mis tiempos, $n era muy diferente.",
                "Mi abuela me contó una historia de $n.",
                "Los jóvenes de $n siempre tienen prisa.",
                "El río cerca de $n sabe a dónde va.",
                "Cuando yo era joven, $n tenía otro nombre.",
                "Los árboles viejos de $n dan la mejor sombra.",
                "Este lugar en $r tenía otro rostro antes.",
                "La paciencia que enseña $n es la madre de la ciencia.",
                "Mi madre sabía cosas de $r que los libros no dicen.",
                "El que siembra en $n, cosecha.",
                "No todo lo que brilla en $r es oro.",
                "Hay secretos en $n que solo la tierra conoce.",
            ];
        case 'B1':
            return [
                "Cuando yo era niña, $n tenía otro rostro. El tiempo cambia todo.",
                "Mi abuela decía que las montañas de $r escuchan lo que la gente ya no dice.",
                "Lo que Don Próspero llama progreso en $n, mi generación lo llama pérdida.",
                "Los ríos de $r han visto más que cualquier libro de historia.",
                "Hay historias de $n que se cuentan solo cuando se está listo para escucharlas.",
                "Yo no confío en las palabras bonitas sobre $r. Confío en las manos que trabajan.",
                "Los nombres de $n son la memoria de los que ya no están.",
                "Cada proverbio de $r es una lección que costó generaciones aprender.",
                "Cuando la memoria de $n se pierde, el pueblo se pierde.",
                "Lo más valioso de $r no es lo que se ve sino lo que se siente.",
                "La gente de antes en $n no tenía menos: tenía diferente.",
                "Mi madre me enseñó en $r que escuchar es más difícil que hablar.",
            ];
        case 'B2':
            return [
                "Cuidado con lo que parece fácil en $n. Este lugar esconde más de lo que muestra.",
                "El progreso en $r no siempre avanza. A veces retrocede disfrazado.",
                "Lo que $n necesita no es más caminos sino más memoria.",
                "Cada árbol que cae en $r se lleva una conversación de siglos con el viento.",
                "Los pueblos de $r que olvidan de dónde vienen no saben a dónde van.",
                "He visto llegar muchos hombres como Don Próspero a $n. Todos prometían lo mismo.",
                "La tradición de $r no es repetir el pasado: es renovar su significado.",
                "Lo que se construye rápido en $n se derrumba rápido.",
                "Hay en $r un tipo de riqueza que no se mide con dinero.",
                "Yo he visto ríos de $r morir. No por sequía, sino por indiferencia.",
                "Lo peor de la ignorancia en $n no es lo que no sabe sino lo que cree saber.",
                "La tierra de $r tiene una gramática propia. Quien no la lee, la destruye.",
            ];
        case 'C1':
            return [
                "Cada generación en $r pierde una palabra. Nosotros decidimos cuál recuperamos.",
                "Lo que $n guarda no son tesoros arqueológicos sino lecciones vigentes.",
                "Hay un silencio en $r que contiene más información que todos los libros.",
                "Lo que Don Próspero no comprende es que la riqueza de $n se destruye al extraerla.",
                "La oralidad de $r no es una forma inferior: es una forma distinta de verdad.",
                "Yo no cuento historias de $n por nostalgia. Las cuento porque el futuro las necesita.",
                "Cada paisaje destruido en $r es una página arrancada del libro de una cultura.",
                "El verdadero progreso en $n no elimina lo antiguo: lo integra.",
                "Lo que me preocupa de $r no es morir sino que lo que sé muera conmigo.",
                "La resistencia más poderosa en $n es la persistencia de la memoria.",
                "La tradición de $r es una conversación entre los muertos, los vivos y los que vendrán.",
                "La sabiduría ancestral de $n no es reliquia: es código que la modernidad no descifra.",
            ];
        case 'C2':
            return [
                "Lo que buscas en $n ya te encontró. Solo falta que lo nombres.",
                "$n existía antes de su nombre y existirá después de que todos los nombres se olviden.",
                "La memoria de $r no es depósito sino acto creativo: recordar es reinventar con fidelidad.",
                "Lo que he aprendido en $n se resume en esto: escuchar es la forma más alta de respeto.",
                "Hay en $r un saber que opera por acumulación silenciosa, no por revelación espectacular.",
                "La oralidad de $r es la forma más democrática de literatura: no necesita acceso sino atención.",
                "Cada vez que una abuela de $n muere sin ser escuchada, una civilización pierde un capítulo.",
                "Lo sagrado de $r no es lo que está arriba sino lo que está debajo: las raíces, el agua.",
                "El tiempo en $n no es lineal para quienes viven de la tierra. Es un espiral.",
                "Lo que quiero decir sobre $r antes de irme es que el cuidado es la única revolución.",
                "La lengua más vieja de $r no está en diccionarios: está en la tierra.",
                "Si pudiera dejar un solo legado de $n: aprendan a escuchar antes de que sea tarde.",
            ];
    }
    return [];
}

function donProsperoLines(int $d, string $cefr, array $lm, string $title, string $campbell): array {
    if ($d < 19) return [];
    $n = $lm['name']; $r = $lm['region'];

    switch ($cefr) {
        case 'B1':
            return [
                "Buenos días, $n. Vengo a hablar de oportunidades.",
                "Miren cómo vive la gente de $r. Yo puedo traer algo mejor.",
                "Les doy mi palabra: nadie en $n va a salir perdiendo.",
                "El camino nuevo va a conectar $n con el mundo.",
                "Yo también amo $r. Por eso quiero mejorarla.",
                "¿Quién en $n no quiere trabajo y escuelas?",
                "No soy el enemigo de $r. Soy el que trae soluciones.",
                "Lo que ofrezco a $n es simple: empleo, educación, carreteras.",
                "Los que se oponen al cambio en $r le tienen miedo al futuro.",
                "Cuando este proyecto en $n esté terminado, todos me van a agradecer.",
                "Las buenas intenciones en $r no construyen hospitales.",
                "El mundo avanza. $r no puede quedarse atrás.",
            ];
        case 'B2':
            return [
                "¿Saben cuánto vale $n en el mercado internacional?",
                "La nostalgia de $r es un lujo que las comunidades pobres no se pueden permitir.",
                "Yo no destruyo $n. Lo transformo en oportunidades.",
                "Cada peso que invierto en $r genera tres en empleo local.",
                "El ambientalismo romántico de $n es una posición cómoda para el que tiene el estómago lleno.",
                "Mi proyecto en $r incluye compensación ambiental y reubicación digna.",
                "Hay dos tipos de personas en $n: las que construyen y las que critican.",
                "Lo que $r necesita no es preservación sino transformación inteligente.",
                "Lo que no les dicen sobre $n los ambientalistas es cuánta gente muere de pobreza.",
                "Las comunidades de $r merecen elegir su futuro, no que otros lo elijan.",
                "Lo que ven como ambición en $n, yo lo veo como responsabilidad.",
                "La complejidad del desarrollo de $r no se resuelve con slogans ecológicos.",
            ];
        case 'C1':
            return [
                "Lo que $n necesita es una visión que trascienda la dicotomía simplista naturaleza-progreso.",
                "El verdadero cinismo en $r no es construir: es contemplar la pobreza desde el privilegio moral.",
                "La sostenibilidad en $n no es detener el tiempo. Es gestionar el cambio.",
                "Las comunidades de $r me piden lo mismo: trabajo digno, no discursos.",
                "La tensión en $n entre conservación y desarrollo no tiene resolución: solo gestión.",
                "Mi proyecto en $r no es perfecto. Pero la inacción tiene un costo que nadie contabiliza.",
                "Lo que distingue a un líder en $n de un político es la voluntad de decidir.",
                "La historia demostrará que lo que hice en $r fue necesario. Doloroso, pero necesario.",
                "Lo que mis críticos de $n no reconocen es que yo también he cambiado en el proceso.",
                "Lo que aprendí en $r es que las soluciones locales requieren recursos globales.",
                "El ambientalismo en $n debe evolucionar de la protesta a la propuesta.",
                "Cada vez que alguien dice 'no' en $r, está diciendo 'sí' a la perpetuación de la pobreza.",
            ];
        case 'C2':
            return [
                "Mi mayor contribución a $n no será lo que construí sino el debate que provoqué.",
                "$n me ha obligado a confrontar si el progreso sin violencia simbólica es posible.",
                "Lo que empezó como un proyecto en $r se ha convertido en una meditación sobre el poder.",
                "He aprendido en $n, demasiado tarde quizás, que hay riquezas que no puedo medir.",
                "Lo que Candelaria me enseñó en $r, sin proponérselo, es que escuchar es poder.",
                "Mi tragedia en $n no es ser villano sino descubrir que el villano tenía algo de razón.",
                "Si pudiera empezar de nuevo en $r, escucharía más antes de actuar.",
                "He construido puentes en $r. Y he destruido cosas que no tienen precio.",
                "Lo que $n necesita no es más hombres como yo sino mejores preguntas.",
                "La mayor lección de $r: el poder que no se autocuestiona es tiranía.",
                "Mis críticos en $n tienen razón en lo esencial, pero equivocan la escala.",
                "El capítulo final de mi historia en $r no lo escribo yo.",
            ];
    }
    return [];
}

function mamaJaguarLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    switch ($cefr) {
        case 'A1':
            return [
                "Hijo, ven. $n es seguro.",
                "Cuidado con el agua cerca de $n.",
                "Yo te protejo aquí en $n.",
                "Escucha a tu hermano cerca de $n.",
                "Este lugar, $n, es tu casa.",
                "No te alejes mucho de $n.",
                "Duerme tranquilo. $n es seguro.",
                "Mañana exploramos más de $n.",
                "La tierra de $n te cuida.",
                "No hay prisa aquí en $n.",
                "Quédate cerca de mí en $n.",
                "El aire de $n es bueno.",
                "Yo conozco lugares como $n.",
                "$n es un buen lugar para ti.",
                "Camina despacio por $n.",
                "La noche en $n es tranquila.",
                "Hijo, mira $n. Es bonito.",
                "Todo está bien aquí en $n.",
                "Yo soy tu madre. $n es nuestro.",
                "Descansa aquí en $n.",
            ];
        case 'A2':
            return [
                "Yaguará, quédate cerca de $n.",
                "Algún día caminarás sola por lugares como $n.",
                "Tu padre caminaba por estos caminos de $n.",
                "Cuida a tu hermano aquí en $n.",
                "Confía en tus instintos en $n.",
                "Los jaguares de $n no temen la oscuridad.",
                "La tierra de $n te enseñará lo que yo no puedo.",
                "Cada estrella sobre $n tiene un nombre.",
                "La familia jaguar es fuerte en $n.",
                "Yo siempre estaré contigo, aunque no me veas en $n.",
                "Algún día entenderás por qué vinimos a $n.",
                "$n es más grande de lo que parece.",
            ];
        default:
            return [
                "Lo que te enseñé en $r no fue solo cazar: fue observar.",
                "Cada madre de $n da lo que tiene. Yo te di el bosque.",
                "Cuando el miedo llegue en $r, recuerda de dónde vienes.",
                "Los jaguares que nombran $n primero escuchan el mundo.",
                "Lo que aprendiste conmigo en $r es la raíz. Lo demás es rama.",
                "El amor de una madre en $n no protege: prepara.",
                "Vuelve a $r cuando necesites recordar quién eres.",
                "Mi territorio en $n es tu territorio. Siempre.",
                "No te olvidé. Te solté para que crecieras en $r.",
                "Lo que parece abandono en $n a veces es confianza.",
            ];
    }
}

function abuelaCeibaLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    switch ($cefr) {
        case 'A1':
            return [
                "Escucha, criatura. Esto es $n.",
                "La raíz de $n sabe cosas.",
                "El árbol de $n recuerda todo.",
                "Paciencia aquí en $n.",
                "Todo en $n está conectado.",
                "Yo estaba aquí antes que $n tuviera nombre.",
                "El viento de $n me habla.",
                "Siéntate a mi sombra en $n.",
                "Mira mis raíces aquí en $n.",
                "El tiempo de $n es largo y lento.",
                "Yo soy vieja. Más vieja que $n.",
                "La tierra de $n es sabia.",
                "El agua de $n viene de mis raíces.",
                "Calla y escucha $n.",
                "Yo protejo $n.",
                "$n crece conmigo.",
                "Mis hojas cubren $n.",
                "La luz de $n pasa por mis ramas.",
                "Aquí en $n, yo soy la más antigua.",
                "$n es mi hijo. Y tú también.",
            ];
        default:
            return [
                "Mis raíces bajo $n tocan aguas que ningún mapa registra.",
                "He visto civilizaciones nacer y morir en $n.",
                "El verdadero conocimiento de $n crece despacio, como un árbol.",
                "Las lenguas del bosque de $n son más antiguas que las del hombre.",
                "Cada hoja que pierdo en $n es una palabra que el viento lleva.",
                "Soy el puente entre el cielo y lo subterráneo de $n.",
                "Lo que necesitas saber de $n ya está en mis raíces.",
                "Lo que cae en $n vuelve a la tierra. Lo que sube busca la luz.",
                "La sabiduría de $n no se busca: se cultiva.",
                "Lo que el fuego destruye en $n, la semilla reconstruye.",
                "Yo soy la memoria viva de $n.",
                "Lo que el río de $n no puede llevar, mis raíces lo sostienen.",
            ];
    }
}

function colibriLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "El néctar más dulce de $n está en la flor más escondida.",
        "Mis alas se mueven más rápido que tus pensamientos sobre $n.",
        "Lo pequeño en $n tiene su propia grandeza.",
        "Yo polinizo las ideas de $n igual que las flores.",
        "La belleza más deslumbrante de $n dura un instante.",
        "No necesito ser grande para ser esencial en $n.",
        "Cada flor que visito en $n me transforma y la transforma.",
        "La velocidad en $n no es prisa: es precisión.",
        "Lo que el ojo no ve en $n, el colibrí lo encuentra.",
        "Mi corazón late con cada color de $n.",
        "Vuelo sobre $n buscando lo que nadie más busca.",
        "Las flores de $n me cuentan secretos que los árboles ignoran.",
        "En $n, cada pétalo es una historia diminuta.",
        "Yo conozco $n flor por flor.",
        "La dulzura de $n está en lo que no se ve a simple vista.",
        "Mi vuelo por $n es un mapa invisible de lo bello.",
        "Lo que zumba en $n tiene más que decir que lo que ruge.",
        "Cada amanecer en $n encuentro un color que ayer no existía.",
        "Soy el mensajero más pequeño de $n, pero el más preciso.",
        "Las alas de un colibrí en $n pesan menos que una palabra, pero llegan más lejos.",
    ];
}

function condorLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "Desde arriba, las fronteras cerca de $n no existen.",
        "He volado sobre guerras olvidadas cerca de $n.",
        "La altura sobre $n enseña perspectiva, no superioridad.",
        "Lo que parece laberinto cerca de $n desde abajo es camino desde arriba.",
        "Cada corriente de aire sobre $n enseña paciencia.",
        "Los que vuelan alto sobre $n ven lejos pero sienten frío.",
        "Lo que el viento de $n me dice, yo se lo traduzco a la tierra.",
        "Llevo la memoria de $n en mis plumas.",
        "Volar sobre $n no es escapar: es ver sin obstáculos.",
        "La cumbre más alta cerca de $n no es la más visible.",
        "Mis alas conocen cada rincón del cielo sobre $n.",
        "El aire de $n tiene una historia que solo el cóndor lee.",
        "He visto $n cambiar desde que era polluelo.",
        "Lo que $n esconde en sus valles, yo lo revelo desde las nubes.",
        "La distancia entre el cielo y $n es la distancia entre ver y comprender.",
        "Cada vez que vuelo sobre $n, descubro algo que antes no veía.",
        "El silencio a esta altura sobre $n es la voz más clara.",
        "Mis plumas guardan el polvo de $n.",
        "Lo que el cóndor ve sobre $n, ningún caminante lo imagina.",
        "Vuelo en círculos sobre $n buscando lo que la tierra esconde.",
    ];
}

function delfinLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "Las aguas oscuras cerca de $n guardan los secretos más luminosos.",
        "Yo nado entre mundos cerca de $n: el río y el mito.",
        "Lo que se esconde bajo la superficie de $n es lo más real.",
        "El río cerca de $n tiene memoria. Yo soy su guardián.",
        "En la oscuridad del agua de $n, la intuición reemplaza a la visión.",
        "Cada meandro del río cerca de $n es una decisión del agua.",
        "Lo rosado de mi piel es la risa del río de $n.",
        "Lo que el río arrastra cerca de $n no lo pierde: lo redistribuye.",
        "Yo no navego el río de $n. El río me navega a mí.",
        "Los ancestros dicen que fui humano antes de ser río cerca de $n.",
        "El agua de $n me conoce mejor que yo mismo.",
        "Cada sonido bajo el agua de $n cuenta una historia ancestral.",
        "Mi cola dibuja mapas en el río de $n que nadie puede leer.",
        "Lo que brilla en las aguas de $n no es oro: es memoria.",
        "Yo soy el guardián rosado de $n.",
        "Las corrientes de $n me susurran nombres olvidados.",
        "Nado en $n con los ojos cerrados y veo más que con ellos abiertos.",
        "El río de $n tiene un latido. Yo nado al ritmo de ese latido.",
        "Lo que $n no dice con palabras, lo dice con agua.",
        "Cada atardecer en $n, el río y yo tenemos una conversación.",
    ];
}

function tortugaLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "He visto el mar cerca de $n antes de que tuviera este nombre.",
        "La corriente más fuerte cerca de $n no es la del agua sino la de la paciencia.",
        "Mi caparazón es mi historia cerca de $n: cada marca es un año.",
        "Lo que el mar de $n me enseñó es que todo regresa.",
        "Hay sabiduría en la lentitud de $n que la velocidad nunca encontrará.",
        "Las estrellas me guían hacia $n. Antes de los barcos, ya había tortugas.",
        "Lo que parece fragilidad cerca de $n es resistencia acumulada.",
        "El océano de $n no tiene caminos, pero conozco cada uno.",
        "Mi migración cerca de $n es más antigua que cualquier frontera.",
        "La arena de $n donde nací me espera cada vez que regreso.",
        "He nadado más lento que nadie hacia $n, y por eso he llegado más lejos.",
        "Cada ola que rompe en $n tiene mil años de viaje.",
        "Lo que $n no puede enseñarte con prisa, yo te lo enseño con lentitud.",
        "Mi caparazón ha tocado las aguas de $n cientos de veces.",
        "Las mareas de $n marcan un calendario más antiguo que el humano.",
        "Yo fui testigo del primer amanecer sobre $n.",
        "La profundidad del mar cerca de $n guarda lo que la superficie olvida.",
        "Cada grano de arena de $n conoce mi nombre.",
        "Lo más sabio que hace el mar cerca de $n es esperar.",
        "Yo soy la paciencia de $n hecha caparazón.",
    ];
}

function maestroLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "Repite después de mí: $n.",
        "¿Cuántas cosas ves en $n?",
        "Muy bien. Otra vez. Dime: $n.",
        "Ahora tú solo. ¿Qué es $n?",
        "Los números ayudan en $n.",
        "Cada verbo nuevo en $n es una puerta.",
        "Practica todos los días aquí en $n.",
        "No hay errores en $n. Solo intentos.",
        "La lengua es como $n: crece con práctica.",
        "Aprender en $n es un viaje largo.",
        "Deletrea: $n.",
        "¿Puedes describir $n?",
        "En $n hay muchas palabras nuevas.",
        "Escucha con atención aquí en $n.",
        "Cada palabra de $n tiene historia.",
        "Paso a paso en $n. No hay prisa.",
        "El vocabulario de $n es tu herramienta.",
        "Lee en voz alta sobre $n.",
        "Observa bien $n. Después habla.",
        "Hoy aprendemos de $n.",
    ];
}

function sombraLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "Yo soy lo que $n olvidó.",
        "Cada nombre que dices en $n me debilita.",
        "El gris de $n es mi lengua.",
        "Sin nombre, $n me pertenece.",
        "Tú nombras $n. Yo lo borro.",
        "Lo que no recuerdas de $n es mío.",
        "El silencio vacío de $n es mi hogar.",
        "Cada palabra olvidada en $n me alimenta.",
        "Soy tu sombra en $n. Y la sombra crece.",
        "Lo innombrable de $n es mi territorio.",
        "$n tenía color. Yo se lo quité.",
        "Cuando $n pierda su último nombre, seré todo.",
        "Yo fui $n antes de que tú llegaras.",
        "El olvido de $n es mi banquete.",
        "No me temas. Solo olvida $n.",
        "Cada sílaba que pierdes en $n me fortalece.",
        "$n se oscurece con cada palabra que olvidas.",
        "Yo soy el silencio que $n no quiere escuchar.",
        "Cuando $n se quede sin nombres, yo seré su único habitante.",
        "Lo que borras de $n, yo lo reclamo.",
    ];
}

function antiguosLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "Antes de las palabras en $n, estaba el canto.",
        "Nosotros somos la memoria de la memoria de $n.",
        "Lo que olvidaron los vivos de $n, lo guardamos nosotros.",
        "Cada lengua que nace cerca de $n es un milagro.",
        "El árbol de las lenguas de $n tiene raíces invisibles.",
        "Nombrar $n fue el primer acto de creación.",
        "Lo que se pierde en $n se gana en silencio.",
        "Somos los que fueron antes de $n.",
        "La palabra más antigua de $n nadie la ha pronunciado.",
        "El primer nombre de $n fue 'agua'. El segundo fue 'madre'.",
        "Fuimos viento antes de que $n existiera.",
        "Lo que $n guarda en su centro no tiene nombre. Aún.",
        "$n fue canción antes de ser lugar.",
        "Cada piedra de $n recuerda una voz que ya no suena.",
        "Nosotros cantamos $n cuando todavía era sueño.",
        "Lo que $n olvida, nosotros lo guardamos en el subsuelo.",
        "El eco de $n lleva nuestras voces dentro.",
        "Antes de que $n tuviera forma, nosotros le dimos canto.",
        "$n es nuevo para ti. Para nosotros, es eterno.",
        "Lo que crees descubrir en $n, nosotros lo sembramos hace milenios.",
    ];
}

function voicesForestLines(int $d, string $cefr, array $lm): array {
    $n = $lm['name']; $r = $lm['region'];
    return [
        "El bosque de $n susurra lo que la ciudad olvida.",
        "Somos las voces de los árboles de $n que ya no están.",
        "Cada hoja de $n es una sílaba del bosque.",
        "Lo que el fuego destruyó en $n, la semilla recuerda.",
        "El verde de $n tiene más matices que cualquier paleta.",
        "Escúchanos en $n: somos la respiración de la tierra.",
        "Cada raíz de $n conoce caminos que ningún mapa traza.",
        "El bosque de $n no necesita nombre. El nombre necesita bosque.",
        "Lo que crece despacio en $n resiste mejor.",
        "Somos el murmullo entre los mundos de $n.",
        "Las ramas de $n guardan conversaciones de siglos.",
        "Lo que $n perdió en árboles, lo ganamos en ecos.",
        "Cada amanecer, $n respira a través de nosotros.",
        "El musgo de $n es nuestra escritura más antigua.",
        "Si escuchas bien en $n, oirás lo que los árboles callan.",
        "Nosotros somos la memoria verde de $n.",
        "Lo que el viento de $n arrastra, nuestras raíces lo detienen.",
        "El bosque de $n habla en un idioma anterior al hombre.",
        "$n canta por la noche. Solo el que escucha lo sabe.",
        "Las sombras de $n no son oscuridad: son abrazo.",
    ];
}

// ============================================================
// FALLBACK PAD LINES (CEFR-appropriate, unique per dest/char)
// ============================================================

function generatePadLines(string $char, string $cefr, string $n, int $d, string $title): array {
    // These use dest number $d to guarantee uniqueness
    $charName = str_replace(['char_', 'echo_', 'myth_'], '', $char);

    switch ($cefr) {
        case 'A1':
            return [
                "Destino $d. $n.",
                "Aquí, destino $d.",
                "Mira. Destino $d.",
                "$n. Sí.",
                "Estamos en $n ahora.",
            ];
        case 'A2':
            return [
                "Este es el destino $d: $n.",
                "Llegamos al destino $d.",
                "$n es nuestro destino $d.",
                "Estoy aquí en el destino $d, $n.",
                "El destino $d es especial.",
            ];
        case 'B1':
            return [
                "El destino $d nos enseña algo sobre $title.",
                "Cada paso en el destino $d, $n, tiene significado.",
                "Lo que aprendemos en el destino $d cambia nuestra perspectiva.",
                "El camino al destino $d, $n, no fue en vano.",
                "$n, destino $d, guarda una lección que no esperaba.",
            ];
        case 'B2':
            return [
                "Lo que el destino $d revela sobre $n desafía nuestras expectativas.",
                "Hay una tensión en el destino $d que refleja $title.",
                "El destino $d, $n, nos confronta con lo que creíamos saber.",
                "$n nos recuerda, en el destino $d, que el viaje es la respuesta.",
                "La complejidad del destino $d trasciende lo que las palabras capturan.",
            ];
        case 'C1':
            return [
                "Lo que el destino $d nos ofrece en $n redefine nuestra comprensión.",
                "En el destino $d, $n articula lo que la razón no alcanza.",
                "El destino $d demuestra que $title es más que un concepto.",
                "$n, en este destino $d, opera como un texto que se reescribe.",
                "La experiencia del destino $d exige una escucha que trasciende lo lingüístico.",
            ];
        case 'C2':
            return [
                "Lo que el destino $d inscribe en $n pertenece al orden de lo inefable.",
                "En el destino $d, $n se revela como palimpsesto de $title.",
                "El destino $d articula en $n lo que ningún discurso agota.",
                "$n, destino $d, es simultáneamente punto de llegada y de partida.",
                "Lo que el destino $d consigna sobre $n trasciende toda traducción.",
            ];
    }
    return [];
}

// ============================================================
// PROCESS ALL 89 DESTINATIONS
// ============================================================

$stats = ['dests' => 0, 'chars' => 0, 'lines' => 0, 'errors' => [], 'low' => []];

for ($d = 1; $d <= 89; $d++) {
    if ($singleDest !== null && $d !== $singleDest) continue;

    $file = "$contentDir/dest{$d}.json";
    if (!file_exists($file)) { $stats['errors'][] = "dest{$d}.json not found"; continue; }

    $json = json_decode(file_get_contents($file), true);
    if (!$json) { $stats['errors'][] = "dest{$d}.json invalid JSON"; continue; }

    $meta = $json['meta'];
    $cefr = $meta['cefr'];
    $title = $meta['title'];
    $campbell = $meta['campbellStage'];
    $lm = $landmarkMap[$d] ?? null;
    if (!$lm) { $stats['errors'][] = "No landmark for dest{$d}"; continue; }

    $chars = array_keys($json['characterLines'] ?? []);
    if (empty($chars)) $chars = $meta['characters'] ?? [];

    $newLines = [];

    foreach ($chars as $char) {
        $pool = [];
        switch ($char) {
            case 'char_yaguara':       $pool = yaguaraLines($d, $cefr, $lm, $title, $campbell); break;
            case 'char_candelaria':    $pool = candelariaLines($d, $cefr, $lm, $title, $campbell); break;
            case 'char_rio':           $pool = rioLines($d, $cefr, $lm, $title, $campbell); break;
            case 'char_dona_asuncion':
            case 'echo_dona_asuncion': $pool = donaAsuncionLines($d, $cefr, $lm, $title, $campbell); break;
            case 'char_don_prospero':  $pool = donProsperoLines($d, $cefr, $lm, $title, $campbell); break;
            case 'char_mama_jaguar':   $pool = mamaJaguarLines($d, $cefr, $lm); break;
            case 'char_abuela_ceiba':
            case 'echo_abuela_ceiba':  $pool = abuelaCeibaLines($d, $cefr, $lm); break;
            case 'char_colibri':       $pool = colibriLines($d, $cefr, $lm); break;
            case 'char_condor_viejo':  $pool = condorLines($d, $cefr, $lm); break;
            case 'char_delfin_rosado': $pool = delfinLines($d, $cefr, $lm); break;
            case 'char_tortuga_marina':$pool = tortugaLines($d, $cefr, $lm); break;
            case 'char_maestro':       $pool = maestroLines($d, $cefr, $lm); break;
            case 'myth_sombra_yaguara':$pool = sombraLines($d, $cefr, $lm); break;
            case 'myth_los_antiguos':  $pool = antiguosLines($d, $cefr, $lm); break;
            case 'voices_forest':      $pool = voicesForestLines($d, $cefr, $lm); break;
            default:
                $pool = [
                    "Hay verdades que solo $n conoce.",
                    "El viaje por $r continúa.",
                    "Cada paso en $n revela algo nuevo.",
                    "Lo importante de $r está en los detalles.",
                    "El camino por $n es largo pero vale la pena.",
                    "Aquí en $r todo tiene significado.",
                    "Las historias de $n merecen ser contadas.",
                    "Cada encuentro en $r nos transforma.",
                    "La verdad de $n se esconde en lo cotidiano.",
                    "Nombrar $r es el primer acto de comprensión.",
                ];
                // Replace placeholders manually for unknown chars
                $nn = $lm['name']; $rr = $lm['region'];
                $pool = array_map(fn($l) => str_replace(['$n', '$r'], [$nn, $rr], $l), $pool);
        }

        $selected = selectUnique($pool, 10);

        // Pad with CEFR-appropriate fallbacks if under 8
        if (count($selected) < 8) {
            $n = $lm['name'];
            $padLines = generatePadLines($char, $cefr, $n, $d, $title);
            $extra = selectUnique($padLines, 8 - count($selected));
            $selected = array_merge($selected, $extra);
        }

        if (count($selected) < 8) {
            $stats['low'][] = "dest{$d}/{$char}: " . count($selected) . " lines";
        }

        $newLines[$char] = $selected;
        $stats['chars']++;
        $stats['lines'] += count($selected);
    }

    $json['characterLines'] = $newLines;

    if (!$dryRun) {
        $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $encoded);
    }

    $stats['dests']++;
    $charSummary = [];
    foreach ($newLines as $c => $l) {
        $charSummary[] = "$c:" . count($l);
    }
    echo "dest{$d} ({$cefr}): " . implode(', ', $charSummary) . "\n";
}

echo "\n=== STATS ===\n";
echo "Destinations processed: {$stats['dests']}\n";
echo "Characters processed: {$stats['chars']}\n";
echo "Total unique lines generated: {$stats['lines']}\n";
echo "Global unique line pool: " . count($usedLines) . "\n";

if (!empty($stats['low'])) {
    echo "\nLow line counts (< 8):\n";
    foreach ($stats['low'] as $l) echo "  - $l\n";
}

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $e) echo "  - $e\n";
}

if ($dryRun) echo "\n[DRY RUN — no files modified]\n";
