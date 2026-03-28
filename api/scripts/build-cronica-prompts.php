<?php
/**
 * Build unique, thematic crónica prompts for all 89 destinations.
 * Each dest gets 4 prompts + 8-10 vocabulary words + evocative instruction.
 * Prompts are specific to the Colombian landmark, narrative theme, and CEFR level.
 *
 * Usage: php build-cronica-prompts.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$contentDir = __DIR__ . '/../../content';

// Load landmarks
$landmarks = json_decode(file_get_contents("$contentDir/landmarks-colombia.json"), true)['landmarks'];
$landmarkMap = [];
foreach ($landmarks as $lm) {
    $landmarkMap[$lm['dest']] = $lm;
}

// ============================================================
// ALL 89 DESTINATION CRONICA DATA
// ============================================================

$cronicaData = [
    // ==================== A1 (dest 1-12) ====================
    1 => [
        'title' => 'Mi primer día en La Milagrosa',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Es tu primer día en Medellín.',
        'prompts' => [
            'Yaguará está en el barrio La Milagrosa. Las calles suben y suben. Escribe tres cosas que ves desde un balcón.',
            '¿Cómo es La Milagrosa? Usa estas palabras: empinado, colorido, ruidoso, tranquilo.',
            'Escribe una carta corta a tu familia: "Querida familia, hoy estoy en Medellín. El barrio se llama..."',
            'Dibuja La Milagrosa con palabras. ¿Qué colores tienen las casas? ¿Qué flores hay en los balcones?'
        ],
        'vocabulary' => ['barrio', 'calle', 'balcón', 'flor', 'empinado', 'colorido', 'iglesia', 'escalera', 'vecino', 'grafiti']
    ],
    2 => [
        'title' => 'Las estrellas del Parque de los Deseos',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Mira el cielo.',
        'prompts' => [
            'Estás en el Parque de los Deseos. Hay un planetario y espejos de agua. Escribe tres cosas que ves.',
            '¿Cómo es el parque de noche? Usa: oscuro, brillante, grande, mágico.',
            'Escribe un deseo para el parque. Empieza con: "Yo quiero..."',
            'El agua refleja las estrellas. ¿Qué más refleja el agua? Escribe tres cosas.'
        ],
        'vocabulary' => ['estrella', 'deseo', 'agua', 'cielo', 'noche', 'planetario', 'espejo', 'brillante', 'parque', 'soñar']
    ],
    3 => [
        'title' => 'Las mariposas del Jardín Botánico',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Estás rodeado de naturaleza.',
        'prompts' => [
            'Estás en el Jardín Botánico de Medellín. Hay orquídeas, mariposas y tortugas. Escribe tres cosas que te gustan.',
            '¿Cómo es una mariposa? Usa: pequeña, bonita, rápida, azul, amarilla.',
            'Rinrín Renacuajo sale de paseo por el jardín. ¿Adónde va? Escribe tres lugares del jardín.',
            'Escribe una lista: "En el jardín hay..." (escribe cinco plantas o animales).'
        ],
        'vocabulary' => ['mariposa', 'orquídea', 'tortuga', 'jardín', 'planta', 'flor', 'lago', 'verde', 'rana', 'árbol']
    ],
    4 => [
        'title' => 'Desde la cima del Cerro Nutibara',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Estás en lo alto de la montaña.',
        'prompts' => [
            'Estás en el Cerro Nutibara. Desde arriba ves toda la ciudad. Escribe tres cosas que ves abajo.',
            '¿Cómo es el Pueblito Paisa? Usa: pequeño, antiguo, bonito, blanco.',
            'Escribe una postal desde el cerro: "Hola, estoy en un cerro en Medellín. Desde aquí veo..."',
            'Sube los escalones del cerro. ¿Cuántos escalones hay? ¿Estás cansado o contento? Escribe.'
        ],
        'vocabulary' => ['cerro', 'cima', 'ciudad', 'pueblo', 'escalón', 'vista', 'montaña', 'antiguo', 'subir', 'mirar']
    ],
    5 => [
        'title' => 'Las estatuas gordas de la Plaza Botero',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. El arte está en todas partes.',
        'prompts' => [
            'En la Plaza Botero hay 23 estatuas muy grandes y gordas. Escribe tres estatuas que ves (un gato, un caballo, una mujer...).',
            '¿Cómo son las estatuas de Botero? Usa: gordo, grande, redondo, pesado, gracioso.',
            'Escribe una conversación entre dos estatuas. ¿Qué se dicen? "Hola, yo soy el gato. Tú eres..."',
            'Tócale la nariz al gato de Botero. Dicen que trae suerte. ¿Qué suerte quieres tú? Escribe un deseo.'
        ],
        'vocabulary' => ['estatua', 'gordo', 'plaza', 'arte', 'museo', 'gato', 'grande', 'redondo', 'bronce', 'suerte']
    ],
    6 => [
        'title' => 'El bosque de niebla del Parque Arví',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Estás en las nubes.',
        'prompts' => [
            'Llegas al Parque Arví en metrocable. Vuelas sobre la ciudad. Escribe tres cosas que ves desde el aire.',
            '¿Cómo es el bosque de niebla? Usa: húmedo, frío, verde, silencioso, misterioso.',
            'Escribe una carta a un amigo: "Hoy estoy en un bosque de niebla. No puedo ver lejos porque..."',
            'En el mercado campesino hay frutas. ¿Cuáles? Escribe una lista de cinco frutas o comidas.'
        ],
        'vocabulary' => ['bosque', 'niebla', 'metrocable', 'frío', 'húmedo', 'sendero', 'pájaro', 'mercado', 'fruta', 'nube']
    ],
    7 => [
        'title' => 'Los sabores de la Plaza Minorista',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. ¡Cuántos sabores!',
        'prompts' => [
            'Estás en la Plaza Minorista. Hay frutas de todos los colores. Escribe tres frutas que ves.',
            '¿Cómo es el mercado? Usa: ruidoso, colorido, grande, rico, lleno.',
            'Compras un jugo de lulo. ¿Qué sabor tiene? ¿Es dulce, ácido, frío? Escribe tres cosas sobre el jugo.',
            'Escribe un menú para el almuerzo. "Para comer: ... Para beber: ... De postre: ..."'
        ],
        'vocabulary' => ['mercado', 'fruta', 'jugo', 'arepa', 'dulce', 'ácido', 'lulo', 'guanábana', 'comer', 'sabor']
    ],
    8 => [
        'title' => 'Las flores de Santa Elena',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Las flores cuentan historias.',
        'prompts' => [
            'En Santa Elena, las familias llevan flores en la espalda. Se llaman silleteros. Escribe tres cosas sobre ellos.',
            '¿Cómo es una silleta de flores? Usa: pesada, colorida, grande, hermosa, fragante.',
            'Escribe una carta desde Santa Elena: "Aquí las familias hacen algo increíble con flores..."',
            '¿Qué flores pones tú en una silleta? Escribe una lista de cinco flores y sus colores.'
        ],
        'vocabulary' => ['flor', 'silleta', 'silletero', 'familia', 'espalda', 'pesado', 'color', 'tradición', 'caminar', 'orgullo']
    ],
    9 => [
        'title' => 'El río que renace',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. El río tiene una historia.',
        'prompts' => [
            'El río Medellín cruza toda la ciudad. Antes estaba sucio, ahora renace. Escribe tres cosas que ves en el río.',
            '¿Cómo es el río? Usa: largo, sucio, limpio, grande, importante.',
            'Escribe al río Medellín una carta: "Querido río, yo quiero que tú..."',
            'El domingo hay ciclovía junto al río. ¿Qué haces? ¿Caminas, corres, montas bicicleta? Escribe tu domingo.'
        ],
        'vocabulary' => ['río', 'agua', 'limpio', 'sucio', 'ciudad', 'puente', 'bicicleta', 'caminar', 'domingo', 'renacer']
    ],
    10 => [
        'title' => 'Las tres rocas de la Biblioteca España',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Los libros te esperan.',
        'prompts' => [
            'La Biblioteca España son tres rocas negras en la montaña. Escribe tres cosas que ves desde allí.',
            '¿Cómo es la biblioteca? Usa: negra, alta, moderna, grande, importante.',
            'Entras a la biblioteca. ¿Qué libro quieres leer? Escribe el título y por qué te gusta.',
            'Desde la biblioteca ves todo el barrio Santo Domingo. ¿Cómo es? Escribe tres cosas.'
        ],
        'vocabulary' => ['biblioteca', 'libro', 'leer', 'roca', 'negro', 'montaña', 'barrio', 'moderno', 'alto', 'número']
    ],
    11 => [
        'title' => 'Los verbos de la Universidad',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Todos aprenden aquí.',
        'prompts' => [
            'Estás en la Universidad de Antioquia. Es una ciudad dentro de la ciudad. Escribe tres cosas que ves.',
            '¿Cómo es la universidad? Usa: grande, verde, llena de gente, interesante.',
            'Escribe tu horario de clases ideal: "A las 8:00, estudio... A las 10:00, estudio..."',
            'En el campus hay un museo y un teatro. ¿Cuál visitas primero? ¿Por qué?'
        ],
        'vocabulary' => ['universidad', 'estudiante', 'estudiar', 'clase', 'museo', 'teatro', 'campus', 'aprender', 'profesor', 'verbo']
    ],
    12 => [
        'title' => 'Los 740 escalones de Guatapé',
        'instruction' => 'Yaguará te pide que escribas en tu cuaderno. Sube con nosotros.',
        'prompts' => [
            'La Piedra de Guatapé tiene 740 escalones. Subes y subes. Escribe tres cosas que sientes.',
            '¿Cómo es Guatapé? Usa: alto, colorido, azul, verde, impresionante.',
            'Llegas a la cima. Ves agua, islas y montañas. Escribe una postal: "Desde arriba veo..."',
            'El pueblo de Guatapé tiene casas con dibujos en las paredes. ¿Qué dibujos ves? Escribe cinco.'
        ],
        'vocabulary' => ['piedra', 'escalón', 'subir', 'cima', 'lago', 'isla', 'zócalo', 'colorido', 'cansado', 'impresionante']
    ],

    // ==================== A2 (dest 13-18) ====================
    13 => [
        'title' => 'El primer adiós en el aeropuerto',
        'instruction' => 'Yaguará te invita a escribir. Sales de Medellín por primera vez.',
        'prompts' => [
            'Ayer saliste de Medellín en avión. El avión cruzó la cordillera. ¿Qué viste por la ventana?',
            'Si pudieras volver a un lugar de Medellín, ¿cuál sería? ¿Por qué?',
            'Candelaria te dio algo antes de irte del aeropuerto. ¿Qué fue? ¿Por qué es importante?',
            'Escribe un mensaje de texto a un amigo en Medellín: "Acabo de salir de la ciudad y ya extraño..."'
        ],
        'vocabulary' => ['aeropuerto', 'avión', 'cordillera', 'salir', 'volar', 'ventana', 'despedida', 'extrañar', 'viaje', 'maleta']
    ],
    14 => [
        'title' => 'El cuento de Jardín',
        'instruction' => 'Yaguará te invita a escribir. Este pueblo parece un cuento.',
        'prompts' => [
            'Ayer caminamos por Jardín, un pueblo que parece un cuento. ¿Qué pasó en la plaza principal?',
            'Si pudieras vivir en Jardín, ¿qué harías cada mañana? ¿Y cada noche?',
            'Candelaria te contó una historia en la plaza. ¿Qué historia fue? Inventa el comienzo.',
            'Escribe un mensaje a tu familia: "Estoy en un pueblo que se llama Jardín porque..."'
        ],
        'vocabulary' => ['pueblo', 'cueva', 'plaza', 'cuento', 'mañana', 'gallito de roca', 'historia', 'contar', 'teleférico', 'trucha']
    ],
    15 => [
        'title' => 'El subjuntivo de Jericó',
        'instruction' => 'Yaguará te invita a escribir. Aquí lo que pasó se cuenta distinto.',
        'prompts' => [
            'Ayer visitamos Jericó, el pueblo de Carrasquilla. ¿Qué viste en la Casa Museo?',
            'Si pudieras hablar con Tomás Carrasquilla, ¿qué le preguntarías sobre Jericó?',
            'Candelaria encontró un libro viejo en Jericó. ¿Qué decía la primera página?',
            'Escribe un mensaje a un amigo contándole sobre el cerro del Salvador: "Subimos hasta..."'
        ],
        'vocabulary' => ['pueblo', 'escritor', 'museo', 'libro', 'cerro', 'iglesia', 'patrimonio', 'subir', 'contar', 'viejo']
    ],
    16 => [
        'title' => 'Entre las palmas de cera de Salento',
        'instruction' => 'Yaguará te invita a escribir. Las palmas más altas del mundo te rodean.',
        'prompts' => [
            'Ayer caminamos por el Valle de Cocora entre palmas de cera de 60 metros. ¿Qué sentiste?',
            'Si pudieras vivir en Salento, ¿qué harías con el café que crece aquí?',
            'Candelaria encontró una palma de cera bebé. ¿Cuántos años necesita para crecer? Inventa su historia.',
            'Escribe un mensaje a un amigo: "En Salento descubrí que las palmas de cera son..."'
        ],
        'vocabulary' => ['palma', 'valle', 'café', 'alto', 'niebla', 'sendero', 'trucha', 'crecer', 'cera', 'montaña']
    ],
    17 => [
        'title' => 'El volcán que vio Rivera',
        'instruction' => 'Yaguará te invita a escribir. El volcán huele a azufre.',
        'prompts' => [
            'Ayer vimos el Nevado del Ruiz desde Manizales. Rivera también lo vio. ¿Qué ves tú?',
            'Si pudieras subir al volcán, ¿qué llevarías en tu mochila? ¿Por qué?',
            'Candelaria tomó un café de Manizales y cerró los ojos. ¿Qué soñó?',
            'Escribe un mensaje desde Manizales: "Hoy amaneció y vi el volcán. El cielo estaba..."'
        ],
        'vocabulary' => ['volcán', 'nevado', 'café', 'amanecer', 'frío', 'nieve', 'azufre', 'montaña', 'termales', 'nube']
    ],
    18 => [
        'title' => 'El río que no espera en Honda',
        'instruction' => 'Yaguará te invita a escribir. El río Magdalena pasa rápido.',
        'prompts' => [
            'Ayer llegamos a Honda. El río Magdalena no espera a nadie. ¿Qué viste en el puerto?',
            'Si pudieras navegar el río Magdalena, ¿adónde irías? ¿Con quién?',
            'Candelaria compró un pescado de subienda en el mercado. ¿Cómo lo cocinaron?',
            'Escribe un mensaje a un amigo: "En Honda hay un puente viejo que cruza el río y..."'
        ],
        'vocabulary' => ['río', 'puente', 'pescado', 'puerto', 'navegar', 'colonial', 'corriente', 'subienda', 'mercado', 'cruzar']
    ],

    // ==================== B1 (dest 19-28) ====================
    19 => [
        'title' => 'Tierra dulce en Villeta',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Es tu turno de escribir.',
        'prompts' => [
            '¿Por qué es importante la panela para los pueblos como Villeta? ¿Qué representa?',
            'Compara un trapiche panelero con una fábrica moderna. ¿Qué se gana y qué se pierde?',
            'Un turista llega a Villeta y no sabe qué es la panela. Explícale en cinco oraciones.',
            'Si la caña de azúcar pudiera hablar, ¿qué historia contaría desde que crece hasta que se convierte en panela?'
        ],
        'vocabulary' => ['panela', 'trapiche', 'caña', 'dulce', 'tradición', 'campesino', 'proceso', 'cascada', 'calor', 'artesanal']
    ],
    20 => [
        'title' => 'Las piedras de La Candelaria',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Bogotá tiene muchas historias.',
        'prompts' => [
            '¿Por qué es importante La Candelaria para la historia de Colombia? Menciona tres razones.',
            'Compara una calle de La Candelaria con una calle de tu ciudad. ¿En qué se parecen? ¿En qué son diferentes?',
            'Un turista te pregunta qué hacer en La Candelaria. Recomiéndale tres lugares y explica por qué.',
            'Si las paredes de La Candelaria pudieran hablar, ¿qué historias contarían? Escribe una.'
        ],
        'vocabulary' => ['colonial', 'museo', 'calle', 'piedra', 'capital', 'chichería', 'grafiti', 'historia', 'cerro', 'café']
    ],
    21 => [
        'title' => 'La catedral bajo tierra',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Bajo la tierra hay un templo.',
        'prompts' => [
            '¿Por qué la Catedral de Sal de Zipaquirá es importante para Colombia? ¿Qué la hace única?',
            'Compara la Catedral de Sal con otro lugar sagrado que conozcas. ¿Qué tienen en común?',
            'Un turista te pregunta sobre Zipaquirá. ¿Qué le dices sobre la catedral que está 200 metros bajo tierra?',
            'Si la sal pudiera hablar, ¿qué diría sobre los mineros que construyeron este templo?'
        ],
        'vocabulary' => ['catedral', 'sal', 'mina', 'subterráneo', 'templo', 'profundo', 'minero', 'construir', 'sagrado', 'tren']
    ],
    22 => [
        'title' => 'El tiempo de Villa de Leyva',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Aquí el tiempo se mide en millones de años.',
        'prompts' => [
            '¿Por qué es importante Villa de Leyva para la ciencia? Los fósiles tienen 130 millones de años.',
            'Compara la plaza de Villa de Leyva con una plaza de tu ciudad. ¿Cuál es más grande? ¿Cuál tiene más historia?',
            'Un turista quiere visitar Villa de Leyva. ¿Qué tres cosas debe ver? ¿Por qué?',
            'Si un fósil de 130 millones de años pudiera hablar, ¿qué diría sobre este lugar?'
        ],
        'vocabulary' => ['fósil', 'plaza', 'empedrado', 'millón', 'antiguo', 'desierto', 'viñedo', 'convento', 'paciencia', 'piedra']
    ],
    23 => [
        'title' => 'Los techos pintados de Tunja',
        'instruction' => 'Candelaria abre su cuaderno y te mira. En Tunja, hasta los techos cuentan historias.',
        'prompts' => [
            '¿Por qué son importantes las casas coloniales de Tunja? ¿Qué nos dicen los techos pintados?',
            'Compara Tunja con Bogotá. Las dos son ciudades de la cordillera, pero ¿en qué se diferencian?',
            'Un turista te pregunta sobre la historia de Tunja. Cuéntale sobre la Batalla de Boyacá que ocurrió cerca.',
            'Si los techos pintados de sol y luna de Tunja pudieran hablar, ¿qué dirían sobre el paso del tiempo?'
        ],
        'vocabulary' => ['techo', 'colonial', 'pintura', 'fundador', 'templo', 'batalla', 'independencia', 'sol', 'luna', 'cordillera']
    ],
    24 => [
        'title' => 'El río que se abre en Mompox',
        'instruction' => 'Candelaria abre su cuaderno y te mira. El río Magdalena se divide aquí.',
        'prompts' => [
            '¿Por qué Mompox es Patrimonio de la Humanidad? ¿Qué la hace diferente de otras ciudades coloniales?',
            'Compara Mompox con un pueblo colonial de tu país. ¿Qué los conecta? ¿Qué los separa?',
            'Un turista quiere comprar filigrana momposina. Explícale qué es y por qué es especial.',
            'Si el río Magdalena pudiera hablar al pasar por Mompox, ¿qué historia contaría?'
        ],
        'vocabulary' => ['filigrana', 'río', 'patrimonio', 'colonial', 'joyería', 'Semana Santa', 'brazo', 'artesano', 'oro', 'calor']
    ],
    25 => [
        'title' => 'El amanecer llanero de Yopal',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Los llanos se extienden hasta el horizonte.',
        'prompts' => [
            '¿Por qué son importantes los llanos orientales para Colombia? ¿Qué vida hay en esta inmensidad?',
            'Compara los llanos de Yopal con un paisaje plano de tu país. ¿En qué se parecen?',
            'Un turista quiere vivir la experiencia llanera. ¿Qué le recomiendas? Menciona el joropo, el coleo, el hato.',
            'Si un caballo llanero pudiera hablar, ¿qué diría sobre los amaneceres de Yopal?'
        ],
        'vocabulary' => ['llano', 'horizonte', 'caballo', 'joropo', 'coleo', 'amanecer', 'hato', 'llanero', 'ganado', 'inmensidad']
    ],
    26 => [
        'title' => 'El silencio de Chicaque',
        'instruction' => 'Candelaria abre su cuaderno y te mira. El bosque de niebla escucha.',
        'prompts' => [
            '¿Por qué es importante proteger un bosque de niebla como Chicaque? ¿Qué pasaría si desapareciera?',
            'Compara el silencio de Chicaque con el ruido de Bogotá, que está a solo una hora. ¿Qué necesitamos más?',
            'Un turista te pregunta por qué los árboles de Chicaque están cubiertos de musgo. Explícale.',
            'Si el musgo de Chicaque pudiera hablar, ¿qué diría sobre la humedad, la luz y el tiempo?'
        ],
        'vocabulary' => ['bosque', 'niebla', 'musgo', 'silencio', 'colibrí', 'húmedo', 'reserva', 'proteger', 'sendero', 'canopy']
    ],
    27 => [
        'title' => 'La piedra tallada de Barichara',
        'instruction' => 'Candelaria abre su cuaderno y te mira. Cada piedra de Barichara fue puesta a mano.',
        'prompts' => [
            '¿Por qué Barichara es considerado el pueblo más lindo de Colombia? ¿Qué lo hace especial?',
            'Compara el Camino Real de Guane con un sendero o camino antiguo que conozcas.',
            'Un turista te pregunta sobre los artesanos de Barichara. ¿Qué trabajan? ¿Cómo viven?',
            'Si las piedras del Camino Real pudieran hablar, ¿qué historias contarían de los viajeros que pasaron?'
        ],
        'vocabulary' => ['piedra', 'tallar', 'artesano', 'camino', 'capilla', 'tapia', 'antiguo', 'oficio', 'bello', 'sendero']
    ],
    28 => [
        'title' => 'La herida del Chicamocha',
        'instruction' => 'Candelaria abre su cuaderno y te mira. La tierra se abrió aquí.',
        'prompts' => [
            '¿Por qué el Cañón del Chicamocha impresiona a todos los que lo ven? ¿Qué nos dice sobre la fuerza de la tierra?',
            'Compara el Cañón del Chicamocha con el Gran Cañón u otro cañón que conozcas. ¿Qué sienten al verlos?',
            'Un turista tiene miedo de cruzar el teleférico del cañón. Convéncelo de que vale la pena.',
            'Si el cañón pudiera hablar, ¿qué diría sobre los millones de años que tardó el río en abrirlo?'
        ],
        'vocabulary' => ['cañón', 'profundo', 'teleférico', 'río', 'roca', 'parapente', 'vértigo', 'erosión', 'tierra', 'miedo']
    ],

    // ==================== B2 (dest 29-38) ====================
    29 => [
        'title' => 'Los frailejones del Sumapaz',
        'instruction' => 'El cuaderno se abre solo. El páramo más grande del mundo te rodea.',
        'prompts' => [
            '¿Debe el Páramo de Sumapaz abrirse al turismo masivo o mantenerse protegido? Argumenta tu posición.',
            'Escribe la historia de un frailejón que ha vivido 500 años en Sumapaz. ¿Qué ha visto?',
            '¿Qué perdería Colombia si el páramo de Sumapaz desapareciera? Piensa en el agua, la vida, la memoria.',
            'Inventa una leyenda sobre por qué los frailejones crecen solamente en los páramos.'
        ],
        'vocabulary' => ['páramo', 'frailejón', 'agua', 'proteger', 'ecosistema', 'neblina', 'laguna', 'glaciar', 'nacimiento', 'amenaza']
    ],
    30 => [
        'title' => 'Las estrellas de la Tatacoa',
        'instruction' => 'El cuaderno se abre solo. El desierto tiene la noche más clara.',
        'prompts' => [
            '¿Debe el Desierto de la Tatacoa cambiar para recibir más turistas, o debe quedarse como está? Argumenta.',
            'Escribe la historia del desierto desde la perspectiva de un astrónomo que mira las estrellas cada noche.',
            '¿Qué perdería Colombia si la Tatacoa se convirtiera en una zona urbana? Piensa en el cielo, la tierra, el silencio.',
            'Inventa una leyenda sobre cómo el desierto de la Tatacoa se volvió rojo y gris.'
        ],
        'vocabulary' => ['desierto', 'estrella', 'rojo', 'gris', 'observatorio', 'astronomía', 'fantasma', 'cielo', 'nocturno', 'erosión']
    ],
    31 => [
        'title' => 'Las estatuas que nadie entiende',
        'instruction' => 'El cuaderno se abre solo. San Agustín guarda un misterio de piedra.',
        'prompts' => [
            '¿Deben las estatuas de San Agustín quedarse en el parque o deberían estar en un museo de Bogotá? Argumenta.',
            'Escribe la historia de una estatua de San Agustín desde su propia perspectiva. Lleva siglos observando.',
            '¿Qué perdería Colombia si nadie visitara San Agustín? ¿Qué perdería el mundo?',
            'Inventa una leyenda sobre quiénes tallaron las estatuas y por qué las dejaron aquí.'
        ],
        'vocabulary' => ['estatua', 'misterio', 'civilización', 'tallar', 'arqueología', 'precolombino', 'tumba', 'sagrado', 'patrimonio', 'piedra']
    ],
    32 => [
        'title' => 'La lluvia del Chocó',
        'instruction' => 'El cuaderno se abre solo. En Quibdó, la lluvia es un idioma.',
        'prompts' => [
            '¿Debe Colombia invertir más en Quibdó y el Chocó? ¿Qué significa el abandono de una región tan rica?',
            'Escribe un día en Quibdó desde la perspectiva del río Atrato, que ve pasar la vida en sus aguas.',
            '¿Qué perdería Colombia si se perdiera la cultura afrocolombiana del Chocó?',
            'Inventa una leyenda sobre por qué llueve más en Quibdó que en casi cualquier otro lugar del planeta.'
        ],
        'vocabulary' => ['lluvia', 'río', 'selva', 'afrocolombiano', 'Atrato', 'festival', 'resistencia', 'abundancia', 'olvido', 'biodiversidad']
    ],
    33 => [
        'title' => 'Las ballenas de Nuquí',
        'instruction' => 'El cuaderno se abre solo. Las ballenas vinieron desde la Antártida para estar aquí.',
        'prompts' => [
            '¿Debe Nuquí crecer como destino turístico o debe mantener su aislamiento? Argumenta las dos posturas.',
            'Escribe un día en Nuquí desde la perspectiva de una ballena jorobada que viene a dar a luz.',
            '¿Qué perdería el Pacífico colombiano si las ballenas dejaran de venir a Nuquí?',
            'Inventa una leyenda sobre la primera ballena que llegó a las costas de Nuquí y lo que le dijo al mar.'
        ],
        'vocabulary' => ['ballena', 'Pacífico', 'jorobada', 'migración', 'aislamiento', 'surf', 'selva', 'nacer', 'profundo', 'termales']
    ],
    34 => [
        'title' => 'La isla-prisión de Gorgona',
        'instruction' => 'El cuaderno se abre solo. Gorgona fue cárcel y ahora es santuario.',
        'prompts' => [
            '¿Debe la historia de la cárcel de Gorgona recordarse o debe olvidarse para que la isla sea solo naturaleza?',
            'Escribe la historia de Gorgona desde la perspectiva de un preso que vio la isla convertirse en santuario.',
            '¿Qué perdería Colombia si Gorgona dejara de ser un parque natural protegido?',
            'Inventa una leyenda sobre cómo la naturaleza reconquistó la isla después de que cerraron la prisión.'
        ],
        'vocabulary' => ['isla', 'cárcel', 'santuario', 'biodiversidad', 'coral', 'preso', 'libertad', 'serpiente', 'buceo', 'transformación']
    ],
    35 => [
        'title' => 'Las murallas de Cartagena',
        'instruction' => 'El cuaderno se abre solo. Las murallas guardan historias que no todos conocen.',
        'prompts' => [
            '¿Debe Cartagena conservar su centro histórico exactamente como está, o debe adaptarse al siglo XXI? Argumenta.',
            'Escribe la historia de Cartagena desde la perspectiva de una muralla que vio llegar piratas, esclavos y héroes.',
            '¿Qué perdería Colombia si Cartagena perdiera su declaración de Patrimonio de la Humanidad?',
            'Inventa una leyenda sobre un pirata que intentó entrar a Cartagena pero las murallas le hablaron.'
        ],
        'vocabulary' => ['muralla', 'pirata', 'esclavo', 'colonial', 'patrimonio', 'castillo', 'héroe', 'Getsemaní', 'fortaleza', 'resistencia']
    ],
    36 => [
        'title' => 'La selva que baja al mar',
        'instruction' => 'El cuaderno se abre solo. En el Tayrona, la selva toca el Caribe.',
        'prompts' => [
            '¿Debe el Parque Tayrona cerrarse temporalmente para proteger la naturaleza, como hacen los kogui? Argumenta.',
            'Escribe un amanecer en el Tayrona desde la perspectiva de un indígena kogui que cuida la Sierra.',
            '¿Qué perdería Colombia si el turismo masivo destruyera las playas del Tayrona?',
            'Inventa una leyenda sobre por qué la selva del Tayrona decidió bajar hasta tocar el mar.'
        ],
        'vocabulary' => ['selva', 'playa', 'kogui', 'hamaca', 'sendero', 'Caribe', 'sagrado', 'proteger', 'indígena', 'biodiversidad']
    ],
    37 => [
        'title' => 'La ciudad más antigua',
        'instruction' => 'El cuaderno se abre solo. Santa Marta tiene 500 años de historias.',
        'prompts' => [
            '¿Debe Santa Marta priorizar su identidad histórica o su desarrollo turístico moderno? Argumenta.',
            'Escribe la historia de Santa Marta desde la perspectiva de la Quinta de San Pedro Alejandrino, donde murió Bolívar.',
            '¿Qué perdería Colombia si Santa Marta olvidara que es la ciudad más antigua del país?',
            'Inventa una leyenda sobre la fundación de Santa Marta y el primer día que alguien miró el Caribe desde sus costas.'
        ],
        'vocabulary' => ['antiguo', 'fundación', 'Caribe', 'Bolívar', 'Quinta', 'muelle', 'sierra', 'puerto', 'historia', 'memoria']
    ],
    38 => [
        'title' => 'Los escalones de Ciudad Perdida',
        'instruction' => 'El cuaderno se abre solo. Ciudad Perdida esperó siglos para ser encontrada.',
        'prompts' => [
            '¿Debe Ciudad Perdida ser más accesible para todos, o debe mantenerse como un trek difícil de 4 días? Argumenta.',
            'Escribe la historia de Ciudad Perdida desde la perspectiva de los 1.200 escalones de piedra tairona.',
            '¿Qué perdería la humanidad si Ciudad Perdida fuera olvidada de nuevo, cubierta por la selva?',
            'Inventa una leyenda sobre por qué los tairona construyeron su ciudad en lo alto de la sierra y luego la abandonaron.'
        ],
        'vocabulary' => ['perdido', 'escalón', 'tairona', 'selva', 'terraza', 'trek', 'indígena', 'descubrir', 'piedra', 'ancestral']
    ],

    // ==================== C1 (dest 39-48) ====================
    39 => [
        'title' => 'El corazón de la Sierra Nevada',
        'instruction' => 'El jaguar espera tu voz. La montaña costera más alta del mundo guarda secretos.',
        'prompts' => [
            'Analiza la relación entre la Sierra Nevada de Santa Marta y la identidad de los pueblos kogui, arhuaco y wiwa.',
            'Escribe una crónica periodística sobre la Sierra Nevada: la montaña costera más alta del mundo que alberga todas las alturas.',
            '¿Cómo se cruzan la memoria indígena y el olvido moderno en la Sierra Nevada de Santa Marta?',
            'Si fueras guía de la Sierra Nevada, ¿qué historia contarías que los turistas no conocen?'
        ],
        'vocabulary' => ['sierra', 'sagrado', 'kogui', 'arhuaco', 'cóndor', 'glaciar', 'cosmovisión', 'pagamento', 'equilibrio', 'custodio']
    ],
    40 => [
        'title' => 'Los palafitos de la Ciénaga',
        'instruction' => 'El jaguar espera tu voz. Aquí la gente vive sobre el agua.',
        'prompts' => [
            'Analiza la relación entre Nueva Venecia (pueblo sobre el agua) y la identidad del Caribe colombiano.',
            'Escribe una crónica periodística sobre la Ciénaga Grande: manglar muerto y vivo, pelícanos y palafitos.',
            '¿Cómo se cruzan la vida y la muerte en un manglar como la Ciénaga Grande de Santa Marta?',
            'Si fueras guía de la Ciénaga Grande, ¿qué historia contarías sobre la gente que vive flotando?'
        ],
        'vocabulary' => ['palafito', 'ciénaga', 'manglar', 'pelícano', 'pescador', 'flotante', 'marea', 'humedal', 'amenaza', 'resiliencia']
    ],
    41 => [
        'title' => 'El acordeón de Valledupar',
        'instruction' => 'El jaguar espera tu voz. Aquí las canciones nombran a la gente.',
        'prompts' => [
            'Analiza la relación entre el vallenato y la identidad del Caribe colombiano. ¿Por qué la UNESCO lo declaró patrimonio?',
            'Escribe una crónica periodística sobre el Festival de la Leyenda Vallenata: música, competencia y memoria.',
            '¿Cómo se cruzan la tradición oral y la música en Valledupar? ¿Qué pasa cuando una canción nombra a alguien?',
            'Si fueras guía de Valledupar, ¿qué historia contarías sobre un juglar vallenato que los turistas no conocen?'
        ],
        'vocabulary' => ['vallenato', 'acordeón', 'juglar', 'canción', 'leyenda', 'festival', 'nombrar', 'tradición', 'guacharaca', 'caja']
    ],
    42 => [
        'title' => 'Las aguas del Guatapurí',
        'instruction' => 'El jaguar espera tu voz. El río baja frío desde la Sierra Nevada.',
        'prompts' => [
            'Analiza la relación entre el río Guatapurí y la vida espiritual de los arhuacos que hacen pagamentos en sus aguas.',
            'Escribe una crónica periodística sobre el Guatapurí: un río sagrado que también es balneario popular.',
            '¿Cómo se cruzan lo sagrado y lo cotidiano en el río Guatapurí? ¿Puede un río ser las dos cosas?',
            'Si fueras guía del río Guatapurí, ¿qué historia contarías sobre el agua que baja de la Sierra Nevada?'
        ],
        'vocabulary' => ['río', 'sagrado', 'pagamento', 'arhuaco', 'balneario', 'corriente', 'sierra', 'espiritual', 'cotidiano', 'fresco']
    ],
    43 => [
        'title' => 'El desierto wayúu',
        'instruction' => 'El jaguar espera tu voz. Donde el desierto toca el mar.',
        'prompts' => [
            'Analiza la relación entre el Cabo de la Vela y la cosmogonía wayúu. ¿Qué significa Jepirra para los muertos?',
            'Escribe una crónica periodística sobre el Cabo de la Vela: kitesurf y rancherías, turismo y tradición.',
            '¿Cómo se cruzan el turismo de aventura y la vida wayúu en el Cabo de la Vela? ¿Coexisten o se contradicen?',
            'Si fueras guía del Cabo de la Vela, ¿qué historia contarías sobre Jepirra que los turistas no conocen?'
        ],
        'vocabulary' => ['desierto', 'wayúu', 'ranchería', 'kitesurf', 'Jepirra', 'alma', 'pilón', 'atardecer', 'cosmogonía', 'viento']
    ],
    44 => [
        'title' => 'Las dunas que caen al mar',
        'instruction' => 'El jaguar espera tu voz. Estás en el punto más al norte de Sudamérica.',
        'prompts' => [
            'Analiza qué significa Punta Gallinas como límite geográfico: el punto más septentrional de Sudamérica.',
            'Escribe una crónica periodística sobre las dunas de Taroa, que caen directamente al mar Caribe.',
            '¿Cómo se cruzan el fin del mapa y el comienzo de algo nuevo en un lugar como Punta Gallinas?',
            'Si fueras guía de Punta Gallinas, ¿qué historia contarías sobre la comunidad wayúu que vive en el extremo?'
        ],
        'vocabulary' => ['duna', 'extremo', 'septentrional', 'faro', 'límite', 'arena', 'Caribe', 'wayúu', 'aislamiento', 'frontera']
    ],
    45 => [
        'title' => 'El muelle de Riohacha',
        'instruction' => 'El jaguar espera tu voz. El viento trae historias del mar.',
        'prompts' => [
            'Analiza la relación entre Riohacha, la cultura wayúu y el comercio de mochilas como encuentro de dos mundos.',
            'Escribe una crónica periodística sobre el muelle de Riohacha: viento, mar y artesanía wayúu.',
            '¿Cómo se cruzan la tradición artesanal wayúu y la economía moderna en Riohacha?',
            'Si fueras guía de Riohacha, ¿qué historia contarías sobre las tejedoras de mochilas que los turistas no conocen?'
        ],
        'vocabulary' => ['mochila', 'wayúu', 'tejer', 'muelle', 'viento', 'artesanía', 'flamenco', 'malecón', 'comercio', 'tradición']
    ],
    46 => [
        'title' => 'Los flamencos rosados',
        'instruction' => 'El jaguar espera tu voz. Los flamencos pintan el agua de rosa.',
        'prompts' => [
            'Analiza la relación entre el Santuario de Flamencos y la conservación de ecosistemas en La Guajira.',
            'Escribe una crónica periodística sobre los flamencos de Camarones: rosados sobre agua salada.',
            '¿Cómo se cruzan la fragilidad del ecosistema y la resistencia de los flamencos que regresan cada año?',
            'Si fueras guía del santuario, ¿qué historia contarías sobre por qué los flamencos escogieron este lugar?'
        ],
        'vocabulary' => ['flamenco', 'santuario', 'rosado', 'sal', 'conservación', 'migración', 'humedal', 'kayak', 'fragilidad', 'ecosistema']
    ],
    47 => [
        'title' => 'El mar de siete colores',
        'instruction' => 'El jaguar espera tu voz. San Andrés refleja otra Colombia.',
        'prompts' => [
            'Analiza la relación entre San Andrés, la cultura raizal y la identidad colombiana. ¿Es Colombia caribeña?',
            'Escribe una crónica periodística sobre San Andrés: mar de siete colores, creole, turismo de masa.',
            '¿Cómo se cruzan la identidad raizal, el turismo masivo y la sobreexplotación en San Andrés?',
            'Si fueras guía raizal de San Andrés, ¿qué historia contarías que los turistas de todo incluido no conocen?'
        ],
        'vocabulary' => ['raizal', 'creole', 'archipiélago', 'coral', 'sobrepoblación', 'identidad', 'turismo', 'multilingüe', 'insular', 'resistencia']
    ],
    48 => [
        'title' => 'Tres lenguas en Providencia',
        'instruction' => 'El jaguar espera tu voz. Aquí tres idiomas se trenzan como la corriente.',
        'prompts' => [
            'Analiza qué significa que en Providencia convivan el creole, el español y el inglés. ¿Qué dice sobre Colombia?',
            'Escribe una crónica periodística sobre la reconstrucción de Providencia después del huracán Iota.',
            '¿Cómo se cruzan la destrucción (huracán Iota) y la reconstrucción en la identidad de Providencia?',
            'Si fueras guía de Providencia, ¿qué historia contarías sobre cómo la isla renació después de la tormenta?'
        ],
        'vocabulary' => ['creole', 'huracán', 'reconstrucción', 'coral', 'barrera', 'trilingüe', 'resiliencia', 'pico', 'isla', 'identidad']
    ],

    // ==================== C2 (dest 49-58) ====================
    49 => [
        'title' => 'El río de cinco colores',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Caño Cristales: el río que se vuelve rojo, amarillo, verde, azul y negro.',
            'Caño Cristales como metáfora. ¿De qué? ¿De la diversidad? ¿De lo efímero? ¿De lo sagrado?',
            'Escribe el monólogo interior de la Macarenia clavigera, la planta que pinta el río de rojo.',
            'En cien palabras, crea un mito fundacional para Caño Cristales: ¿por qué el río tiene cinco colores?'
        ],
        'vocabulary' => ['clavigera', 'efímero', 'pigmento', 'serranía', 'endémico', 'cromático', 'metamorfosis', 'cauce', 'sagrado', 'espectro']
    ],
    50 => [
        'title' => 'Los delfines rosados del Orinoco',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Puerto Carreño, donde el Orinoco recibe al Meta y los delfines rosados nadan en la frontera.',
            'Puerto Carreño como metáfora. ¿De qué? ¿De la frontera? ¿Del encuentro? ¿De la periferia?',
            'Escribe el monólogo interior de un delfín rosado que navega entre Colombia y Venezuela.',
            'En cien palabras, crea un mito fundacional para la confluencia de los ríos Meta y Orinoco.'
        ],
        'vocabulary' => ['confluencia', 'delfín', 'frontera', 'Orinoco', 'rosado', 'periferia', 'corriente', 'navegable', 'encuentro', 'salvaje']
    ],
    51 => [
        'title' => 'La Ciudad Blanca',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Popayán: paredes blancas, procesiones lentas, poesía que se lee en voz alta.',
            'Popayán como metáfora. ¿De qué? ¿De la pureza? ¿De la máscara? ¿De la tradición que pesa?',
            'Escribe el monólogo interior de una pared blanca de Popayán que ha visto 500 años de procesiones.',
            'En cien palabras, crea un mito fundacional para Popayán: ¿por qué la ciudad decidió ser blanca?'
        ],
        'vocabulary' => ['blanco', 'procesión', 'colonial', 'gastronomía', 'silencio', 'tradición', 'máscara', 'recogimiento', 'devoto', 'claustro']
    ],
    52 => [
        'title' => 'El azul misak de Silvia',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Silvia: el mercado guambiano de los martes, colores azules contra la montaña verde.',
            'Silvia como metáfora. ¿De qué? ¿Del eco? ¿Del caracol? ¿De lo que resiste sin gritar?',
            'Escribe el monólogo interior de una falda azul misak colgada en el tendedero después del mercado.',
            'En cien palabras, crea un mito fundacional para el mercado de Silvia: ¿por qué los misak eligieron el martes?'
        ],
        'vocabulary' => ['misak', 'guambiano', 'azul', 'mercado', 'páramo', 'resistencia', 'ancestral', 'espiral', 'tejido', 'identidad']
    ],
    53 => [
        'title' => 'Las tumbas pintadas de Tierradentro',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Tierradentro: tumbas subterráneas pintadas de rojo y negro, un arte funerario que celebra la vida.',
            'Tierradentro como metáfora. ¿De qué? ¿De lo enterrado? ¿De lo que se niega a morir? ¿Del misterio?',
            'Escribe el monólogo interior de un hipogeo pintado que lleva siglos en la oscuridad esperando que alguien baje.',
            'En cien palabras, crea un mito fundacional para Tierradentro: ¿por qué pintaron los muertos con colores de vida?'
        ],
        'vocabulary' => ['hipogeo', 'funerario', 'subterráneo', 'pigmento', 'rojo', 'espiral', 'ancestro', 'oscuridad', 'rito', 'permanencia']
    ],
    54 => [
        'title' => 'La salsa de Cali',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Cali: la salsa, el cuerpo que se mueve, Cristo Rey mirando desde arriba.',
            'Cali como metáfora. ¿De qué? ¿Del ritmo? ¿De la alegría que resiste al dolor? ¿Del cuerpo como lenguaje?',
            'Escribe el monólogo interior de la tarima de una escuela de salsa en Juanchito a las tres de la mañana.',
            'En cien palabras, crea un mito fundacional para Cali: ¿por qué la ciudad eligió bailar?'
        ],
        'vocabulary' => ['salsa', 'ritmo', 'cuerpo', 'tarima', 'barrio', 'alegría', 'sudor', 'trompeta', 'madrugada', 'resistencia']
    ],
    55 => [
        'title' => 'La marimba de Buenaventura',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Buenaventura: marimba, currulao, lluvia sobre el puerto del Pacífico.',
            'Buenaventura como metáfora. ¿De qué? ¿Del abandono? ¿De la resistencia? ¿De la música que nace del dolor?',
            'Escribe el monólogo interior de una marimba de chonta del Pacífico colombiano.',
            'En cien palabras, crea un mito fundacional para Buenaventura: ¿por qué la lluvia nunca deja de caer sobre este puerto?'
        ],
        'vocabulary' => ['marimba', 'currulao', 'chonta', 'Pacífico', 'puerto', 'lluvia', 'abandono', 'resistencia', 'ballena', 'percusión']
    ],
    56 => [
        'title' => 'La triple frontera del Amazonas',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Leticia: tres países beben del mismo río, la selva no conoce fronteras.',
            'Leticia como metáfora. ¿De qué? ¿De la frontera que no existe? ¿Del límite arbitrario? ¿De lo que une?',
            'Escribe el monólogo interior del río Amazonas al pasar por Leticia, donde tres banderas lo miran.',
            'En cien palabras, crea un mito fundacional para Leticia: ¿por qué tres países decidieron encontrarse aquí?'
        ],
        'vocabulary' => ['frontera', 'Amazonas', 'tikuna', 'triple', 'selva', 'arbitrario', 'confluencia', 'delfín', 'Victoria regia', 'soberanía']
    ],
    57 => [
        'title' => 'El pueblo sin carros',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Puerto Nariño: un pueblo sin carros, sin motos, solo el río y la selva.',
            'Puerto Nariño como metáfora. ¿De qué? ¿De la utopía? ¿Del silencio elegido? ¿Del tiempo sin prisa?',
            'Escribe el monólogo interior de un delfín rosado del Lago Tarapoto que ve llegar y partir las canoas.',
            'En cien palabras, crea un mito fundacional para Puerto Nariño: ¿por qué el pueblo eligió el silencio?'
        ],
        'vocabulary' => ['silencio', 'canoa', 'delfín', 'tarapoto', 'indígena', 'sostenible', 'caimán', 'nocturno', 'utopía', 'lento']
    ],
    58 => [
        'title' => 'El nombre en tikuna',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el Parque Amacayacu: selva primaria donde cada planta tiene nombre en tikuna.',
            'Amacayacu como metáfora. ¿De qué? ¿Del conocimiento que se pierde? ¿De lo innombrable? ¿De la última palabra?',
            'Escribe el monólogo interior de un árbol de la selva de Amacayacu que tiene un nombre en tikuna que ningún diccionario recoge.',
            'En cien palabras, crea un mito fundacional para Amacayacu: ¿quién le puso nombre a cada planta y por qué?'
        ],
        'vocabulary' => ['tikuna', 'canopy', 'primario', 'ancestral', 'medicina', 'innombrable', 'biodiversidad', 'raíz', 'nomenclatura', 'chamán']
    ],

    // ==================== C1 (dest 59-68) ====================
    59 => [
        'title' => 'La cascada del Fin del Mundo',
        'instruction' => 'El jaguar espera tu voz. Mocoa guarda una cascada que lleva un nombre imposible.',
        'prompts' => [
            'Analiza la relación entre la Cascada del Fin del Mundo y la identidad del Putumayo, región olvidada y renaciente.',
            'Escribe una crónica periodística sobre Mocoa: la tragedia de 2017, la reconstrucción y la cascada como símbolo.',
            '¿Cómo se cruzan la destrucción natural (avalancha) y la belleza natural (cascada) en la memoria de Mocoa?',
            'Si fueras guía de la Cascada del Fin del Mundo, ¿qué historia contarías sobre este lugar que los turistas no conocen?'
        ],
        'vocabulary' => ['cascada', 'fin', 'avalancha', 'reconstrucción', 'kamentsá', 'Sibundoy', 'resiliencia', 'caída', 'renacimiento', 'selva']
    ],
    60 => [
        'title' => 'Las pinturas de 12.000 años',
        'instruction' => 'El jaguar espera tu voz. Alguien pintó aquí antes de las pirámides.',
        'prompts' => [
            'Analiza la relación entre las pinturas rupestres de San José del Guaviare y la identidad amazónica de Colombia.',
            'Escribe una crónica periodística sobre Cerro Azul: arte rupestre de 12.000 años en la puerta de la Amazonía.',
            '¿Cómo se cruzan el arte rupestre de 12.000 años y el arte contemporáneo? ¿Qué hemos aprendido? ¿Qué hemos olvidado?',
            'Si fueras guía de Cerro Azul, ¿qué historia contarías sobre los primeros artistas de América?'
        ],
        'vocabulary' => ['rupestre', 'milenario', 'pigmento', 'jaguar', 'transición', 'Amazonía', 'Guayabero', 'ancestral', 'megafauna', 'ocre']
    ],
    61 => [
        'title' => 'La Flor de Inírida',
        'instruction' => 'El jaguar espera tu voz. Una flor crece donde tres ríos se encuentran.',
        'prompts' => [
            'Analiza qué significa la Estrella Fluvial de Oriente, donde confluyen el Guaviare, el Atabapo y el Inírida.',
            'Escribe una crónica periodística sobre Inírida: la Flor de Inírida, los cerros de Mavicure y las aguas negras.',
            '¿Cómo se cruzan el aislamiento geográfico y la riqueza natural en un lugar como Inírida?',
            'Si fueras guía de los cerros de Mavicure, ¿qué historia contarías sobre la flor que solo crece aquí?'
        ],
        'vocabulary' => ['confluencia', 'fluvial', 'Mavicure', 'endémico', 'tepuy', 'aguas negras', 'estrella', 'aislamiento', 'Inírida', 'biodiversidad']
    ],
    62 => [
        'title' => 'Las 27 lenguas de Mitú',
        'instruction' => 'El jaguar espera tu voz. Aquí se hablan más lenguas que en muchos países.',
        'prompts' => [
            'Analiza qué significa que en el Vaupés se hablen 27 lenguas indígenas. ¿Qué nos dice sobre la diversidad lingüística?',
            'Escribe una crónica periodística sobre Mitú: malocas tucano, raudales de Jirijirimo, selva profunda.',
            '¿Cómo se cruzan la riqueza lingüística (27 lenguas) y la amenaza de extinción en un lugar como Mitú?',
            'Si fueras guía de una maloca tucana, ¿qué historia contarías sobre la lengua que se habla dentro?'
        ],
        'vocabulary' => ['maloca', 'tucano', 'multilingüe', 'raudal', 'extinción', 'lingüístico', 'diversidad', 'selva', 'cosmogonía', 'oralidad']
    ],
    63 => [
        'title' => 'El bahareque de Salamina',
        'instruction' => 'El jaguar espera tu voz. Las casas de Salamina resisten como palabras.',
        'prompts' => [
            'Analiza la relación entre la arquitectura de bahareque de Salamina y la identidad del Paisaje Cultural Cafetero.',
            'Escribe una crónica periodística sobre Salamina: pueblo patrimonio donde las casas de bahareque resisten.',
            '¿Cómo se cruzan la fragilidad del bahareque y la resistencia de un pueblo que se niega a desaparecer?',
            'Si fueras guía de Salamina, ¿qué historia contarías sobre la técnica del bahareque que los turistas no conocen?'
        ],
        'vocabulary' => ['bahareque', 'patrimonio', 'cafetero', 'cementerio', 'técnica', 'terremoto', 'resiliencia', 'oficio', 'altura', 'memoria']
    ],
    64 => [
        'title' => 'Los canastos de Filandia',
        'instruction' => 'El jaguar espera tu voz. Aquí las cosas todavía tienen nombre.',
        'prompts' => [
            'Analiza la relación entre la cestería artesanal de Filandia y la economía del Eje Cafetero.',
            'Escribe una crónica periodística sobre Filandia: miradores de café, colibríes de Barbas-Bremen, canastos de bejuco.',
            '¿Cómo se cruzan la tradición artesanal (cestería) y el nuevo turismo cafetero en Filandia?',
            'Si fueras guía de Filandia, ¿qué historia contarías sobre los tejedores de bejuco que los turistas no conocen?'
        ],
        'vocabulary' => ['cestería', 'bejuco', 'mirador', 'colibrí', 'café', 'artesanal', 'oficio', 'tejer', 'reserva', 'tradición']
    ],
    65 => [
        'title' => 'La roca solitaria del Pacífico',
        'instruction' => 'El jaguar espera tu voz. Malpelo está a 500 kilómetros de todo.',
        'prompts' => [
            'Analiza qué significa Malpelo como patrimonio de la humanidad: una roca solitaria a 500 km de la costa.',
            'Escribe una crónica periodística sobre Malpelo: tiburones martillo, biodiversidad marina, aislamiento absoluto.',
            '¿Cómo se cruzan la soledad de Malpelo y la abundancia de vida submarina que la rodea?',
            'Si fueras guía de Malpelo (donde casi nadie puede ir), ¿qué historia contarías sobre esta roca?'
        ],
        'vocabulary' => ['aislamiento', 'tiburón', 'martillo', 'patrimonio', 'marino', 'roca', 'profundidad', 'endémico', 'corriente', 'soledad']
    ],
    66 => [
        'title' => 'La Puerta de Oro',
        'instruction' => 'El jaguar espera tu voz. Barranquilla es donde el río Magdalena se encuentra con el mar.',
        'prompts' => [
            'Analiza la relación entre el Carnaval de Barranquilla y la identidad colombiana. ¿Qué se celebra realmente?',
            'Escribe una crónica periodística sobre Barranquilla: Carnaval, industria, Bocas de Ceniza, la Puerta de Oro.',
            '¿Cómo se cruzan la fiesta (Carnaval) y la industria (puerto) en la identidad de Barranquilla?',
            'Si fueras guía de Barranquilla, ¿qué historia contarías sobre la ciudad que los turistas del Carnaval no conocen?'
        ],
        'vocabulary' => ['carnaval', 'cumbia', 'marimonda', 'industrial', 'Magdalena', 'desembocadura', 'máscara', 'Garabato', 'alegría', 'identidad']
    ],
    67 => [
        'title' => 'La lengua palenquera',
        'instruction' => 'El jaguar espera tu voz. Palenque habla un idioma que no existe en ningún otro lugar.',
        'prompts' => [
            'Analiza qué significa San Basilio de Palenque: primer pueblo libre de América, lengua criolla viva.',
            'Escribe una crónica periodística sobre Palenque: lengua palenquera, champeta, dulces, historia cimarrona.',
            '¿Cómo se cruzan la libertad (primer pueblo libre) y la lengua (palenquero) como formas de resistencia?',
            'Si fueras guía de Palenque, ¿qué historia contarías sobre la lengua que sobrevivió a la esclavitud?'
        ],
        'vocabulary' => ['palenquero', 'cimarrón', 'criollo', 'libertad', 'esclavitud', 'champeta', 'resistencia', 'tambor', 'dulce', 'patrimonio']
    ],
    68 => [
        'title' => 'La isla del futuro',
        'instruction' => 'El jaguar espera tu voz. Isla Fuerte vive sin carros, con sol.',
        'prompts' => [
            'Analiza qué significa Isla Fuerte como modelo de sostenibilidad: sin carros, con energía solar, con coral vivo.',
            'Escribe una crónica periodística sobre Isla Fuerte: bioluminiscencia, tortugas carey, comunidad pesquera.',
            '¿Cómo se cruzan la tradición pesquera y la energía solar en la visión de futuro de Isla Fuerte?',
            'Si fueras guía de Isla Fuerte, ¿qué historia contarías sobre las noches de bioluminiscencia?'
        ],
        'vocabulary' => ['sostenible', 'solar', 'coral', 'bioluminiscencia', 'tortuga', 'pesquero', 'futuro', 'aislamiento', 'coralino', 'comunidad']
    ],

    // ==================== C2 (dest 69-89) ====================
    69 => [
        'title' => 'La frontera del Darién',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Capurganá: donde la selva del Darién toca el mar cristalino y Panamá está a un paso.',
            'Capurganá como metáfora. ¿De qué? ¿Del umbral? ¿De la frontera que la naturaleza se niega a abrir?',
            'Escribe el monólogo interior del Tapón del Darién: la selva que no dejó construir la carretera.',
            'En cien palabras, crea un mito fundacional para Capurganá: ¿por qué la selva se cerró entre dos continentes?'
        ],
        'vocabulary' => ['frontera', 'Darién', 'tapón', 'umbral', 'impenetrable', 'cristalino', 'migración', 'selva', 'Panamá', 'paso']
    ],
    70 => [
        'title' => 'Las ballenas que escriben',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Bahía Solano: las ballenas escriben su ruta migratoria en el Pacífico.',
            'Bahía Solano como metáfora. ¿De qué? ¿De la escritura del cuerpo? ¿De la ruta que no se ve?',
            'Escribe el monólogo interior de una tortuga que desova en las playas de Bahía Solano.',
            'En cien palabras, crea un mito fundacional para Bahía Solano: ¿por qué las ballenas eligen esta bahía para dar a luz?'
        ],
        'vocabulary' => ['ballena', 'migración', 'desove', 'tortuga', 'ruta', 'escritura', 'Pacífico', 'cascada', 'nocturno', 'ciclo']
    ],
    71 => [
        'title' => 'El espejo del café',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Pereira: la ciudad del café que refleja la montaña, puerta del Paisaje Cultural Cafetero.',
            'Pereira como metáfora. ¿De qué? ¿Del reflejo? ¿De la transformación? ¿De la montaña que se urbaniza?',
            'Escribe el monólogo interior de un grano de café de Risaralda, desde el arbusto hasta la taza.',
            'En cien palabras, crea un mito fundacional para Pereira: ¿por qué la ciudad eligió crecer entre cafetales?'
        ],
        'vocabulary' => ['café', 'reflejo', 'paisaje', 'cultural', 'cafetero', 'termales', 'Quimbaya', 'grano', 'montaña', 'transformación']
    ],
    72 => [
        'title' => 'El volcán que vigila',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Pasto: el volcán Galeras vigila la ciudad, el Carnaval de Negros y Blancos la libera.',
            'Pasto como metáfora. ¿De qué? ¿De vivir bajo el volcán? ¿De la fiesta como desafío al peligro?',
            'Escribe el monólogo interior del volcán Galeras, que mira la ciudad de Pasto y su carnaval cada enero.',
            'En cien palabras, crea un mito fundacional para Pasto: ¿por qué la ciudad decidió quedarse al pie del volcán?'
        ],
        'vocabulary' => ['volcán', 'Galeras', 'carnaval', 'barniz', 'peligro', 'celebración', 'erupción', 'Negros y Blancos', 'desafío', 'artesanía']
    ],
    73 => [
        'title' => 'La isla en el lago',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre la Laguna de la Cocha: un lago andino con una isla-santuario en su centro, neblina perpetua.',
            'La Laguna de la Cocha como metáfora. ¿De qué? ¿Del centro oculto? ¿Del misterio que no se disipa?',
            'Escribe el monólogo interior de la Isla de La Corota, santuario flotante en medio de la neblina.',
            'En cien palabras, crea un mito fundacional para la Laguna de la Cocha: ¿quién puso una isla dentro de un lago dentro de una montaña?'
        ],
        'vocabulary' => ['laguna', 'neblina', 'santuario', 'isla', 'andino', 'Corota', 'trucha', 'perpetuo', 'misterio', 'centro']
    ],
    74 => [
        'title' => 'La iglesia imposible',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el Santuario de Las Lajas: una iglesia construida sobre un puente, dentro de un cañón, sobre un río.',
            'Las Lajas como metáfora. ¿De qué? ¿De la fe que desafía la gravedad? ¿De lo imposible construido?',
            'Escribe el monólogo interior del río Guáitara, que pasa bajo la iglesia más hermosa de Colombia.',
            'En cien palabras, crea un mito fundacional para Las Lajas: ¿por qué alguien decidió construir una iglesia en el vacío?'
        ],
        'vocabulary' => ['santuario', 'puente', 'cañón', 'neogótico', 'fe', 'Guáitara', 'imposible', 'gravedad', 'milagro', 'abismo']
    ],
    75 => [
        'title' => 'La crónica de los mundos',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Tumaco: puerto del Pacífico sur, marimba y currulao, resistencia y olvido.',
            'Tumaco como metáfora. ¿De qué? ¿Del abandono? ¿Del ritmo que sobrevive? ¿Del manglar como piel?',
            'Escribe el monólogo interior de un manglar de Tumaco que sostiene la tierra con sus raíces.',
            'En cien palabras, crea un mito fundacional para Tumaco: ¿por qué la marimba nació donde la tierra termina?'
        ],
        'vocabulary' => ['manglar', 'marimba', 'currulao', 'Pacífico', 'resistencia', 'abandono', 'raíz', 'marea', 'Morro', 'percusión']
    ],
    76 => [
        'title' => 'El último pueblo',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Sapzurro: el último pueblo colombiano antes de Panamá, un sendero y el mar.',
            'Sapzurro como metáfora. ¿De qué? ¿Del final? ¿Del comienzo? ¿De la frontera que se camina?',
            'Escribe el monólogo interior del sendero que conecta Sapzurro con La Miel, en Panamá.',
            'En cien palabras, crea un mito fundacional para Sapzurro: ¿por qué el último pueblo eligió mirar al otro lado?'
        ],
        'vocabulary' => ['sendero', 'frontera', 'último', 'Panamá', 'La Miel', 'tranquilidad', 'mirador', 'buceo', 'confín', 'horizonte']
    ],
    77 => [
        'title' => 'Los guardianes de Chiribiquete',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Chiribiquete: tepuyes con pinturas rupestres de 20.000 años, jaguares pintados en la roca.',
            'Chiribiquete como metáfora. ¿De qué? ¿De lo intocable? ¿Del arte que precede a todo? ¿De la selva como museo?',
            'Escribe el monólogo interior de un jaguar pintado en la pared de Chiribiquete hace 20.000 años.',
            'En cien palabras, crea un mito fundacional para Chiribiquete: ¿quién pintó los jaguares y por qué nadie puede ir?'
        ],
        'vocabulary' => ['tepuy', 'rupestre', 'jaguar', 'intocable', 'patrimonio', 'pintura', 'milenio', 'sobrevuelo', 'chamán', 'guardián']
    ],
    78 => [
        'title' => 'Los glaciares del Cocuy',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el Nevado del Cocuy: glaciares que desaparecen, picos como catedrales de hielo.',
            'El Cocuy como metáfora. ¿De qué? ¿De lo que se pierde? ¿Del tiempo que se derrite? ¿De la belleza fugaz?',
            'Escribe el monólogo interior de un glaciar del Cocuy que sabe que está desapareciendo.',
            'En cien palabras, crea un mito fundacional para el Cocuy: ¿por qué los u\'wa consideran la nieve sagrada?'
        ],
        'vocabulary' => ['glaciar', 'deshielo', 'u\'wa', 'sagrado', 'Púlpito', 'catedral', 'hielo', 'fugaz', 'extinción', 'cumbre']
    ],
    79 => [
        'title' => 'El español que nació aquí',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Girón: calles coloniales donde el español de América empezó a sonar diferente.',
            'Girón como metáfora. ¿De qué? ¿Del idioma que cambia al cruzar el océano? ¿De la piedra que recuerda?',
            'Escribe el monólogo interior del puente de calicanto de Girón, que lleva siglos uniendo las dos orillas.',
            'En cien palabras, crea un mito fundacional para Girón: ¿cómo empezó el español a sonar diferente en estas calles?'
        ],
        'vocabulary' => ['calicanto', 'colonial', 'puente', 'idioma', 'transformación', 'patrimonio', 'orilla', 'piedra', 'milagro', 'acento']
    ],
    80 => [
        'title' => 'El cuerpo que escribe',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre la Selva de Matavén: seis pueblos indígenas, arte corporal como escritura.',
            'Matavén como metáfora. ¿De qué? ¿Del cuerpo como libro? ¿De la escritura sin papel? ¿De lo que se lee en la piel?',
            'Escribe el monólogo interior de una pintura corporal que vive solo un día sobre la piel de alguien.',
            'En cien palabras, crea un mito fundacional para Matavén: ¿por qué estos pueblos eligieron escribir en el cuerpo y no en la piedra?'
        ],
        'vocabulary' => ['corporal', 'pintura', 'efímero', 'piel', 'indígena', 'raudal', 'transicional', 'rito', 'identidad', 'símbolo']
    ],
    81 => [
        'title' => 'La isla más densa del mundo',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Santa Cruz del Islote: la isla más densamente poblada del mundo, risas sobre el coral.',
            'La Isla de la Tortuga como metáfora. ¿De qué? ¿De la comunidad extrema? ¿Del humor que salva? ¿De vivir apretados?',
            'Escribe el monólogo interior de Santa Cruz del Islote, una isla donde caben 1.200 personas.',
            'En cien palabras, crea un mito fundacional: ¿por qué 1.200 personas eligieron vivir en una isla del tamaño de un campo de fútbol?'
        ],
        'vocabulary' => ['islote', 'densidad', 'comunidad', 'coral', 'langosta', 'humor', 'convivencia', 'apretado', 'solidaridad', 'mar']
    ],
    82 => [
        'title' => 'El arpa del llano',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Arauca: arpa, cuatro y maracas, el joropo nace donde el llano no tiene fin.',
            'Arauca como metáfora. ¿De qué? ¿De la música como horizonte? ¿Del caballo como libertad? ¿Del amanecer eterno?',
            'Escribe el monólogo interior de un arpa llanera que suena en un hato de Arauca al amanecer.',
            'En cien palabras, crea un mito fundacional para Arauca: ¿por qué el llano necesitó inventar el joropo?'
        ],
        'vocabulary' => ['arpa', 'joropo', 'cuatro', 'maracas', 'llanero', 'hato', 'coleo', 'estero', 'amanecer', 'inmensidad']
    ],
    83 => [
        'title' => 'Los balones de Monguí',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Monguí: el pueblo de los balones, hechos a mano entre la neblina del páramo.',
            'Monguí como metáfora. ¿De qué? ¿Del oficio que resiste? ¿De lo que se cose a mano cuando todo es máquina?',
            'Escribe el monólogo interior de un balón de Monguí que viaja del páramo al estadio.',
            'En cien palabras, crea un mito fundacional para Monguí: ¿por qué un pueblo entre nubes empezó a fabricar balones?'
        ],
        'vocabulary' => ['balón', 'costura', 'artesanal', 'páramo', 'Ocetá', 'neblina', 'oficio', 'cuero', 'estadio', 'resistencia']
    ],
    84 => [
        'title' => 'La revolución comunera',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Socorro: donde los comuneros se levantaron en 1781, antes de la independencia.',
            'Socorro como metáfora. ¿De qué? ¿De la voz que se levanta primero? ¿Del grito que llega antes que el nombre?',
            'Escribe el monólogo interior de la plaza de Socorro en 1781, el día que los comuneros decidieron marchar.',
            'En cien palabras, crea un mito fundacional para Socorro: ¿por qué la revolución empezó aquí y no en Bogotá?'
        ],
        'vocabulary' => ['comunero', 'revolución', 'levantamiento', 'independencia', 'plaza', 'grito', 'impuesto', 'marcha', 'justicia', 'pueblo']
    ],
    85 => [
        'title' => 'La isla de Morgan',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre Old Providence: isla de piratas reales, barrera de coral, cangrejo negro bajo la luna.',
            'Providencia como metáfora. ¿De qué? ¿Del tesoro escondido? ¿Del mundo que solo existe si nadie lo busca?',
            'Escribe el monólogo interior del Fuerte de Morgan, que ha visto piratas, tormentas y huracanes.',
            'En cien palabras, crea un mito fundacional para Providencia: ¿por qué Morgan escondió su tesoro aquí y nunca volvió?'
        ],
        'vocabulary' => ['pirata', 'Morgan', 'tesoro', 'coral', 'barrera', 'cangrejo', 'fuerte', 'escondido', 'leyenda', 'providencia']
    ],
    86 => [
        'title' => 'El río que cuenta la historia',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el río Amazonas en la triple frontera: tres países, una corriente, ninguna bandera en el agua.',
            'La triple frontera como metáfora. ¿De qué? ¿De la arbitrariedad de los mapas? ¿Del río que ignora las naciones?',
            'Escribe el monólogo interior del río Amazonas al pasar el punto donde tres países se miran.',
            'En cien palabras, crea un mito fundacional: ¿por qué el río Amazonas no reconoce fronteras?'
        ],
        'vocabulary' => ['Amazonas', 'frontera', 'triple', 'corriente', 'soberanía', 'arbitrario', 'nación', 'confluencia', 'mono', 'mercado']
    ],
    87 => [
        'title' => 'El silencio elocuente',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el silencio del desierto de La Guajira: un silencio que dice más que mil palabras.',
            'El desierto de Nazareth como metáfora. ¿De qué? ¿Del silencio como lenguaje? ¿De lo que no necesita decirse?',
            'Escribe el monólogo interior de un chinchorro wayúu que cuelga entre dos postes en la Alta Guajira.',
            'En cien palabras, crea un mito fundacional: ¿por qué los wayúu eligieron el desierto y no la montaña?'
        ],
        'vocabulary' => ['silencio', 'elocuente', 'chinchorro', 'wayúu', 'desierto', 'Uribia', 'artesanía', 'viento', 'palabra', 'aridez']
    ],
    88 => [
        'title' => 'Los frailejones centenarios',
        'instruction' => 'Las palabras son tuyas. El jaguar espera.',
        'prompts' => [
            'Escribe un poema sobre el Páramo de Berlín: frailejones centenarios a 4.100 metros, la carretera más alta de Colombia.',
            'El Páramo de Berlín como metáfora. ¿De qué? ¿De la altura como soledad? ¿Del frío que conserva? ¿De las lenguas que se bifurcan?',
            'Escribe el monólogo interior de un frailejón de 500 años en el Páramo de Berlín.',
            'En cien palabras, crea un mito fundacional: ¿por qué los frailejones crecen un centímetro por año, como si el tiempo no tuviera prisa?'
        ],
        'vocabulary' => ['frailejón', 'páramo', 'centenario', 'altitud', 'neblina', 'lento', 'carretera', 'frío', 'centímetro', 'paciencia']
    ],
    89 => [
        'title' => 'Nombrar es crear',
        'instruction' => 'Las palabras son tuyas. Siempre lo fueron. El jaguar te devuelve el nombre.',
        'prompts' => [
            'Escribe un poema sobre el Cerro El Volador: regreso a Medellín, la ciudad entera abajo, el viaje completo.',
            'El Cerro El Volador como metáfora. ¿De qué? ¿Del regreso? ¿Del vuelo? ¿De nombrar lo que ahora conoces?',
            'Escribe el monólogo interior de Yaguará al volver al Cerro El Volador después de 89 destinos.',
            'En cien palabras, escribe tu propio mito fundacional: tú llegaste a Colombia sin palabras y ahora tienes un idioma. ¿Cómo empezó?'
        ],
        'vocabulary' => ['nombrar', 'crear', 'volver', 'cerro', 'mirador', 'viaje', 'palabra', 'idioma', 'jaguar', 'comienzo']
    ],
];

// ============================================================
// APPLY TO DEST FILES
// ============================================================

$updated = 0;
$errors = [];

for ($d = 1; $d <= 89; $d++) {
    $file = "$contentDir/dest{$d}.json";
    if (!file_exists($file)) {
        $errors[] = "dest{$d}.json not found";
        continue;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);
    if (!$data) {
        $errors[] = "dest{$d}.json JSON parse error";
        continue;
    }

    if (!isset($cronicaData[$d])) {
        $errors[] = "dest{$d} has no cronica data defined";
        continue;
    }

    $cd = $cronicaData[$d];
    $found = false;

    foreach ($data['games'] as $i => &$game) {
        if (isset($game['type']) && $game['type'] === 'cronica') {
            $game['title'] = $cd['title'];
            $game['instruction'] = $cd['instruction'];
            $game['prompts'] = $cd['prompts'];
            $game['vocabulary'] = $cd['vocabulary'];
            $found = true;
            break;
        }
    }
    unset($game);

    if (!$found) {
        $errors[] = "dest{$d} has no cronica game type";
        continue;
    }

    if ($dryRun) {
        echo "[DRY RUN] dest{$d}: would update cronica '{$cd['title']}'\n";
    } else {
        $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $output);
        echo "Updated dest{$d}: '{$cd['title']}'\n";
    }
    $updated++;
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated / 89\n";
if ($errors) {
    echo "Errors (" . count($errors) . "):\n";
    foreach ($errors as $e) echo "  - $e\n";
}
