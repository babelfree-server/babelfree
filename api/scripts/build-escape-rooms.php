<?php
/**
 * Build 89 unique narrative-driven escape rooms
 * Usage: php build-escape-rooms.php [--dry-run] [--dest=N] [--dest=N-M]
 */

$dryRun = in_array('--dry-run', $argv);
$destRange = null;
foreach ($argv as $arg) {
    if (preg_match('/--dest=(\d+)(?:-(\d+))?/', $arg, $m)) {
        $destRange = [(int)$m[1], isset($m[2]) ? (int)$m[2] : (int)$m[1]];
    }
}

$landmarks = json_decode(file_get_contents(__DIR__ . '/../../content/landmarks-colombia.json'), true)['landmarks'];
$lm = [];
foreach ($landmarks as $l) $lm[$l['dest']] = $l;

// ============================================================
// ALL 89 ESCAPE ROOMS — each one unique, narrative-driven
// ============================================================
$rooms = [];

// ----------------------------------------------------------
// DEST 1 — La Milagrosa, Medellín (A1, Ordinary World)
// ----------------------------------------------------------
$rooms[1] = [
    'title' => 'La puerta del barrio',
    'instruction' => 'Estás en La Milagrosa. Una puerta vieja se cierra detrás de ti. Para salir, necesitas recordar lo que ves en este barrio.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El balcón cerrado','riddle'=>'Miras hacia arriba. En los balcones hay plantas con flores. Yaguará dice: «Aquí creció alguien especial.» ¿Qué animal creció en este barrio?','answer'=>'rana','options'=>['rana','gato','pájaro'],'hint'=>'Piensa en la rana sin dientes que nació aquí.'],
        ['type'=>'wordlock','prompt'=>'La pared con letras','riddle'=>'En la pared de la iglesia hay una palabra escrita. Tiene 4 letras. Empieza con «C» y es donde vives.','answer'=>'casa','hint'=>'Es el lugar donde duermes y comes.'],
        ['type'=>'riddle','prompt'=>'Las calles empinadas','riddle'=>'Las calles de La Milagrosa suben y suben. Yaguará pregunta: «¿Qué dirección es?»','answer'=>'arriba','options'=>['arriba','abajo','lejos'],'hint'=>'Lo contrario de abajo.'],
        ['type'=>'sequence','prompt'=>'El camino a la iglesia','riddle'=>'Para llegar a la iglesia, ordena estos pasos: primero lo más cerca, después lo más lejos.','answer'=>'balcón,calle,iglesia','options'=>['iglesia','calle','balcón'],'hint'=>'Empieza por lo que está en tu casa.'],
        ['type'=>'wordlock','prompt'=>'La llave de La Milagrosa','riddle'=>'Yaguará susurra: «Abre los ojos. La primera palabra de tu viaje tiene 4 letras. Es lo que haces con los ojos.»','answer'=>'ver','hint'=>'Sinónimo de mirar, pero más corto... solo 3 letras.'],
    ]
];
// Fix: ver is 3 letters, adjust
$rooms[1]['puzzles'][4]['riddle'] = 'Yaguará susurra: «Abre los ojos. La primera palabra de tu viaje tiene 4 letras. Yaguará te dice que lo hagas.»';
$rooms[1]['puzzles'][4]['answer'] = 'ojos';
$rooms[1]['puzzles'][4]['hint'] = 'Yaguará dijo: «Abre los...»';

// ----------------------------------------------------------
// DEST 2 — Parque de los Deseos (A1, Los nombres de las cosas)
// ----------------------------------------------------------
$rooms[2] = [
    'title' => 'El planetario de los nombres',
    'instruction' => 'En el Parque de los Deseos, las estrellas proyectan palabras en el suelo. Nombra cada cosa para abrir la puerta del planetario.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La primera estrella','riddle'=>'Una luz cae del planetario y dibuja algo en el agua. Es redonda, brilla de noche y vive en el cielo. ¿Qué es?','answer'=>'luna','options'=>['luna','sol','nube'],'hint'=>'Sale de noche y es blanca.'],
        ['type'=>'wordlock','prompt'=>'El espejo de agua','riddle'=>'El espejo de agua refleja una palabra de 5 letras. Es lo que sientes cuando ves las estrellas.','answer'=>'deseo','hint'=>'El parque se llama Parque de los...'],
        ['type'=>'riddle','prompt'=>'El cielo abierto','riddle'=>'Yaguará mira hacia arriba. «¿De qué color es el cielo hoy?» Hay sol y no hay nubes.','answer'=>'azul','options'=>['azul','gris','negro'],'hint'=>'El color del cielo cuando hace buen tiempo.'],
        ['type'=>'sequence','prompt'=>'Del día a la noche','riddle'=>'Ordena el cielo: ¿qué viene primero, qué después?','answer'=>'sol,tarde,luna','options'=>['luna','sol','tarde'],'hint'=>'Empieza por la mañana.'],
        ['type'=>'wordlock','prompt'=>'La puerta del planetario','riddle'=>'Para salir necesitas la palabra que describe este lugar. Aquí las cosas tienen... (6 letras).','answer'=>'nombre','hint'=>'El título del destino dice: Los _______ de las cosas.'],
    ]
];

// ----------------------------------------------------------
// DEST 3 — Jardín Botánico (A1, ¿Cómo es el mundo?)
// ----------------------------------------------------------
$rooms[3] = [
    'title' => 'La cueva donde nacen las palabras',
    'instruction' => 'Dentro del Orquideorama, las mariposas guardan secretos. Cada una lleva una palabra en las alas.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La mariposa azul','riddle'=>'Una mariposa se posa en una orquídea. Yaguará pregunta: «¿De qué color es la mariposa?»','answer'=>'azul','options'=>['azul','roja','verde'],'hint'=>'Es el color del cielo.'],
        ['type'=>'wordlock','prompt'=>'La orquídea escondida','riddle'=>'Entre las plantas hay una flor especial de Colombia. Tiene 8 letras y empieza con «O».','answer'=>'orquídea','hint'=>'Es la flor nacional de Colombia.'],
        ['type'=>'riddle','prompt'=>'El paseo de Rinrín','riddle'=>'«Rinrín Renacuajo salió de paseo.» ¿Qué tipo de animal es Rinrín?','answer'=>'rana','options'=>['rana','pez','tortuga'],'hint'=>'Vive en el agua y salta en la tierra.'],
        ['type'=>'sequence','prompt'=>'De semilla a flor','riddle'=>'Ordena el ciclo de la planta.','answer'=>'semilla,raíz,hoja,flor','options'=>['flor','raíz','semilla','hoja'],'hint'=>'Empieza por lo más pequeño, bajo tierra.'],
        ['type'=>'wordlock','prompt'=>'La salida del jardín','riddle'=>'La pregunta de este destino es: «¿Cómo es el...?» Completa con 5 letras.','answer'=>'mundo','hint'=>'Es donde vivimos todos.'],
    ]
];

// ----------------------------------------------------------
// DEST 4 — Cerro Nutibara (A1, Contar los días)
// ----------------------------------------------------------
$rooms[4] = [
    'title' => 'El reloj del cerro',
    'instruction' => 'En la cima del Cerro Nutibara hay un reloj de piedra. Los números están desordenados. Ponlos en su lugar.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La vista desde arriba','riddle'=>'Subes al cerro y miras abajo. Ves toda la ciudad. Yaguará pregunta: «¿Qué día es hoy si ayer fue lunes?»','answer'=>'martes','options'=>['martes','miércoles','domingo'],'hint'=>'El día después de lunes.'],
        ['type'=>'wordlock','prompt'=>'El Pueblito Paisa','riddle'=>'En la cima hay un pueblito. Se llama Pueblito ______. 5 letras, empieza con «P».','answer'=>'Paisa','hint'=>'Es el nombre de la gente de Antioquia.'],
        ['type'=>'riddle','prompt'=>'Los meses del cerro','riddle'=>'Yaguará dice: «Diciembre es el mes número...»','answer'=>'doce','options'=>['doce','diez','once'],'hint'=>'Es el último mes del año. ¿Cuántos meses hay?'],
        ['type'=>'sequence','prompt'=>'Los días de la semana','riddle'=>'Ordena estos días de la semana correctamente.','answer'=>'lunes,martes,jueves,sábado','options'=>['jueves','sábado','martes','lunes'],'hint'=>'Lunes es el primer día de trabajo.'],
        ['type'=>'wordlock','prompt'=>'La llave del tiempo','riddle'=>'El cerro cuenta los días. ¿Cuál es la palabra de 4 letras para lo que pasa y no vuelve?','answer'=>'días','hint'=>'El título dice: Contar los...'],
    ]
];

// ----------------------------------------------------------
// DEST 5 — Plaza Botero (A1, Lo que me gusta)
// ----------------------------------------------------------
$rooms[5] = [
    'title' => 'El museo de las formas',
    'instruction' => 'Las 23 esculturas de Botero cobran vida. Cada una te hace una pregunta sobre lo que te gusta.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La escultura que pregunta','riddle'=>'Una escultura grande y redonda te mira. «¿Qué haces cuando algo te gusta mucho?» Yaguará sonríe.','answer'=>'sonrío','options'=>['sonrío','lloro','grito'],'hint'=>'Lo haces con la boca cuando estás feliz.'],
        ['type'=>'wordlock','prompt'=>'El nombre del artista','riddle'=>'Las esculturas las hizo un artista famoso de Medellín. Su apellido tiene 6 letras.','answer'=>'Botero','hint'=>'La plaza se llama Plaza...'],
        ['type'=>'riddle','prompt'=>'Arte en la plaza','riddle'=>'Yaguará pregunta: «¿Cuántas esculturas hay en la plaza? Más de veinte, menos de veinticinco.»','answer'=>'veintitrés','options'=>['veintitrés','veinte','veinticinco'],'hint'=>'Son 23.'],
        ['type'=>'sequence','prompt'=>'Lo que me gusta','riddle'=>'Ordena de lo más pequeño a lo más grande: las cosas que puedes gustar.','answer'=>'flor,gato,escultura,museo','options'=>['museo','flor','escultura','gato'],'hint'=>'La flor es lo más pequeño, el museo lo más grande.'],
        ['type'=>'wordlock','prompt'=>'La puerta del museo','riddle'=>'Para salir, di qué sientes cuando algo te gusta. Tiene 5 letras, empieza con «G».','answer'=>'gusto','hint'=>'Me _______ el arte. Lo que me...'],
    ]
];

// ----------------------------------------------------------
// DEST 6 — Parque Arví (A1, Un día en el mundo)
// ----------------------------------------------------------
$rooms[6] = [
    'title' => 'El metrocable de las nubes',
    'instruction' => 'El metrocable se detiene sobre el bosque de niebla. Para llegar al Parque Arví, resuelve los acertijos del viento.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El bosque de niebla','riddle'=>'Miras por la ventana del metrocable. Todo está blanco. No es nieve, no es humo. ¿Qué es?','answer'=>'niebla','options'=>['niebla','lluvia','nieve'],'hint'=>'Es como una nube que baja a la tierra.'],
        ['type'=>'wordlock','prompt'=>'El pájaro del bosque','riddle'=>'Escuchas un canto entre los árboles. Es un animal con plumas. Tiene 6 letras.','answer'=>'pájaro','hint'=>'Vuela y canta en los árboles.'],
        ['type'=>'riddle','prompt'=>'Las horas del día','riddle'=>'Yaguará dice: «En el bosque, un día es largo.» ¿A qué hora sale el sol normalmente?','answer'=>'seis','options'=>['seis','doce','nueve'],'hint'=>'Muy temprano en la mañana. Las 6.'],
        ['type'=>'sequence','prompt'=>'Un día en el bosque','riddle'=>'Ordena las actividades de un día en el bosque.','answer'=>'caminar,comer,descansar,dormir','options'=>['dormir','comer','caminar','descansar'],'hint'=>'Primero te mueves, después comes.'],
        ['type'=>'wordlock','prompt'=>'La salida del bosque','riddle'=>'Para salir del bosque de niebla necesitas una palabra de 6 letras: lo que haces todo el día, cada día.','answer'=>'vivir','hint'=>'Es el verbo más importante. Yo vivo, tú vives...'],
    ]
];
// fix: vivir is 5 letters
$rooms[6]['puzzles'][4]['riddle'] = 'Para salir del bosque necesitas la palabra que describe todo lo que pasa entre el amanecer y el anochecer. 3 letras.';
$rooms[6]['puzzles'][4]['answer'] = 'día';
$rooms[6]['puzzles'][4]['hint'] = 'Un _______ en el mundo. El título del destino.';

// ----------------------------------------------------------
// DEST 7 — Plaza Minorista (A1, Comer juntos)
// ----------------------------------------------------------
$rooms[7] = [
    'title' => 'La cocina secreta del mercado',
    'instruction' => 'En la Plaza Minorista hay una cocina secreta. Las puertas solo se abren si conoces los sabores de Colombia.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La fruta misteriosa','riddle'=>'Es verde por fuera, blanca por dentro, con semillas negras. Se come en jugo. Es tropical.','answer'=>'guanábana','options'=>['guanábana','mango','limón'],'hint'=>'Es una fruta grande y espinosa por fuera.'],
        ['type'=>'wordlock','prompt'=>'La arepa caliente','riddle'=>'Es redonda, de maíz, y en Antioquia se come todos los días. 5 letras.','answer'=>'arepa','hint'=>'La comida más típica de Colombia.'],
        ['type'=>'riddle','prompt'=>'Los colores del jugo','riddle'=>'En el mercado hay jugos de todos los colores. Yaguará quiere uno del color del sol. ¿Qué fruta elige?','answer'=>'mango','options'=>['mango','mora','lulo'],'hint'=>'Es amarilla y dulce como el sol.'],
        ['type'=>'sequence','prompt'=>'La receta del mercado','riddle'=>'Ordena los pasos para hacer un jugo.','answer'=>'fruta,agua,azúcar,mezclar','options'=>['mezclar','azúcar','fruta','agua'],'hint'=>'Primero necesitas la fruta.'],
        ['type'=>'wordlock','prompt'=>'La mesa compartida','riddle'=>'En el mercado todos comen en la misma mesa. La palabra clave tiene 6 letras: comer _______.','answer'=>'juntos','hint'=>'El título dice: Comer...'],
    ]
];

// ----------------------------------------------------------
// DEST 8 — Santa Elena (A1, La familia junto al río)
// ----------------------------------------------------------
$rooms[8] = [
    'title' => 'La silleta de flores',
    'instruction' => 'Las familias silleteras de Santa Elena guardan un secreto en sus silletas de flores. Cada flor esconde una pista.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La flor de la silleta','riddle'=>'Los silleteros cargan flores en la espalda. ¿En qué mes es la Feria de las Flores de Medellín?','answer'=>'agosto','options'=>['agosto','diciembre','marzo'],'hint'=>'Es el octavo mes del año.'],
        ['type'=>'wordlock','prompt'=>'La carga del silletero','riddle'=>'El silletero lleva las flores en algo que se pone en la espalda. 7 letras, empieza con «S».','answer'=>'silleta','hint'=>'Es como una silla que llevas en la espalda, llena de flores.'],
        ['type'=>'riddle','prompt'=>'Los lazos de familia','riddle'=>'Yaguará pregunta: «¿Quién te enseña a cargar la silleta?» Es la persona que te dio la vida.','answer'=>'madre','options'=>['madre','amigo','profesor'],'hint'=>'Mamá, pero con otra palabra.'],
        ['type'=>'sequence','prompt'=>'Generaciones','riddle'=>'Ordena las generaciones de una familia, de la mayor a la menor.','answer'=>'abuela,madre,hija,bebé','options'=>['hija','abuela','bebé','madre'],'hint'=>'La abuela es la mayor.'],
        ['type'=>'wordlock','prompt'=>'El lazo que une','riddle'=>'Lo que une a las generaciones de silleteros. 7 letras, empieza con «F».','answer'=>'familia','hint'=>'El título habla de la _______ junto al río.'],
    ]
];

// ----------------------------------------------------------
// DEST 9 — Río Medellín (A1, Mi casa sobre el río)
// ----------------------------------------------------------
$rooms[9] = [
    'title' => 'El puente sobre el río sucio',
    'instruction' => 'El Río Medellín está renaciendo. Un puente viejo guarda cinco candados oxidados. Cada uno necesita una palabra para abrirse.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El agua del río','riddle'=>'El río antes estaba sucio. Ahora está limpio. ¿Cuál es lo opuesto de «sucio»?','answer'=>'limpio','options'=>['limpio','grande','viejo'],'hint'=>'Es lo contrario de sucio.'],
        ['type'=>'wordlock','prompt'=>'El nombre del valle','riddle'=>'El río cruza un valle. El valle de Medellín tiene otro nombre. 6 letras, empieza con «A».','answer'=>'Aburrá','hint'=>'Río Medellín, también llamado río...'],
        ['type'=>'riddle','prompt'=>'La casa del valle','riddle'=>'Yaguará dice: «Mi casa está sobre el río.» ¿Dónde vive Yaguará?','answer'=>'valle','options'=>['valle','montaña','mar'],'hint'=>'Es la tierra baja entre dos montañas.'],
        ['type'=>'sequence','prompt'=>'El ciclo del agua','riddle'=>'Ordena el viaje del agua.','answer'=>'lluvia,río,mar,nube','options'=>['mar','lluvia','nube','río'],'hint'=>'Empieza cuando cae del cielo.'],
        ['type'=>'wordlock','prompt'=>'El candado final','riddle'=>'El río pasa por debajo de tu ventana. La palabra de este destino es donde vives. 4 letras.','answer'=>'casa','hint'=>'Mi _______ sobre el río.'],
    ]
];

// ----------------------------------------------------------
// DEST 10 — Biblioteca España (A1, El tiempo y los números)
// ----------------------------------------------------------
$rooms[10] = [
    'title' => 'Las tres rocas de piedra negra',
    'instruction' => 'La Biblioteca España tiene tres torres como rocas negras. Dentro, los libros guardan acertijos de números y tiempo.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'Las torres de la biblioteca','riddle'=>'La Biblioteca España parece tres rocas gigantes. ¿Cuántas torres tiene?','answer'=>'tres','options'=>['tres','dos','cinco'],'hint'=>'Mira la descripción: tres rocas negras.'],
        ['type'=>'wordlock','prompt'=>'El libro abierto','riddle'=>'En la biblioteca hay miles de estos objetos con páginas. 5 letras.','answer'=>'libro','hint'=>'Lo lees, tiene páginas y una portada.'],
        ['type'=>'riddle','prompt'=>'El reloj de la ladera','riddle'=>'Yaguará mira un reloj en la pared. Son las 3 de la tarde. ¿Cuántas horas faltan para las 8 de la noche?','answer'=>'cinco','options'=>['cinco','tres','ocho'],'hint'=>'Cuenta: 3, 4, 5, 6, 7, 8. ¿Cuántos saltos?'],
        ['type'=>'sequence','prompt'=>'Contar hasta llegar','riddle'=>'El metrocable sube a Santo Domingo. Ordena estos números de menor a mayor.','answer'=>'cien,mil,diez mil,un millón','options'=>['un millón','cien','diez mil','mil'],'hint'=>'100, 1000, 10000, 1000000.'],
        ['type'=>'wordlock','prompt'=>'La llave de la biblioteca','riddle'=>'Los libros y los números viven aquí. La palabra del destino es lo que pasa y no para. 6 letras.','answer'=>'tiempo','hint'=>'El _______ y los números.'],
    ]
];

// ----------------------------------------------------------
// DEST 11 — Universidad de Antioquia (A1, Todos los verbos del mundo)
// ----------------------------------------------------------
$rooms[11] = [
    'title' => 'El aula donde los verbos despiertan',
    'instruction' => 'En la Universidad de Antioquia, cada aula enseña un verbo diferente. Pero alguien mezcló los verbos. Ponlos en orden.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El primer verbo','riddle'=>'Es el verbo más importante. Sin él, no existes. «Yo _______» Tiene 3 letras.','answer'=>'soy','options'=>['soy','voy','doy'],'hint'=>'Yo _______ estudiante. El verbo ser.'],
        ['type'=>'wordlock','prompt'=>'El verbo del campus','riddle'=>'En la universidad, todos hacen esto con los libros. 4 letras, empieza con «L».','answer'=>'leer','hint'=>'Lo que haces con un libro abierto.'],
        ['type'=>'riddle','prompt'=>'Conjugar en la plaza','riddle'=>'Yaguará dice: «Yo como, tú comes, él...»','answer'=>'come','options'=>['come','comer','comemos'],'hint'=>'Tercera persona singular del verbo comer.'],
        ['type'=>'sequence','prompt'=>'La cadena de verbos','riddle'=>'Ordena estos verbos del más básico al más complejo para un estudiante.','answer'=>'ser,tener,hablar,escribir','options'=>['escribir','ser','hablar','tener'],'hint'=>'Primero existes, después posees, luego hablas, al final escribes.'],
        ['type'=>'wordlock','prompt'=>'La puerta del conocimiento','riddle'=>'Todos los _______ del mundo se conjugan aquí. 6 letras.','answer'=>'verbos','hint'=>'Son las palabras de acción: ser, estar, comer, vivir...'],
    ]
];

// ----------------------------------------------------------
// DEST 12 — Guatapé (A1, ¿Quién soy yo?)
// ----------------------------------------------------------
$rooms[12] = [
    'title' => 'Los 740 escalones de la piedra',
    'instruction' => 'La Piedra del Peñol tiene 740 escalones. En cada descanso hay un acertijo. Si los resuelves, sabrás quién eres cuando llegues arriba.',
    'timeLimit' => 360,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El primer descanso','riddle'=>'Subes la piedra. Miras abajo y ves agua azul por todas partes. ¿Qué rodea la piedra?','answer'=>'embalse','options'=>['embalse','río','mar'],'hint'=>'Es agua que se guarda entre montañas. No es río ni mar.'],
        ['type'=>'wordlock','prompt'=>'Los zócalos del pueblo','riddle'=>'Guatapé es famoso por sus casas decoradas. En las paredes hay dibujos de colores llamados... 7 letras.','answer'=>'zócalos','hint'=>'Son las decoraciones en la parte baja de las paredes.'],
        ['type'=>'riddle','prompt'=>'La pregunta de la piedra','riddle'=>'Llegas a la mitad. La piedra te pregunta: «¿Qué eres?» Yaguará responde por ti. ¿Qué dice?','answer'=>'humano','options'=>['humano','piedra','agua'],'hint'=>'Eres una persona, un ser...'],
        ['type'=>'sequence','prompt'=>'Subir la piedra','riddle'=>'Ordena los pasos para subir la Piedra del Peñol.','answer'=>'mirar,respirar,subir,llegar','options'=>['subir','llegar','mirar','respirar'],'hint'=>'Primero miras, luego respiras hondo, subes y al final llegas.'],
        ['type'=>'wordlock','prompt'=>'La cima de la piedra','riddle'=>'Arriba, con toda la vista, Yaguará te pregunta: «¿Quién eres...?» La última palabra tiene 2 letras.','answer'=>'yo','hint'=>'Es el pronombre más personal. ¿Quién soy ___?'],
    ]
];

// ----------------------------------------------------------
// DEST 13 — Aeropuerto (A2, El primer adiós)
// ----------------------------------------------------------
$rooms[13] = [
    'title' => 'La sala de espera entre dos mundos',
    'instruction' => 'En el aeropuerto José María Córdova, tu vuelo se retrasa. Las pantallas muestran acertijos en lugar de destinos.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La pantalla de salidas','riddle'=>'La pantalla dice: «Vuelo retrasado.» ¿Qué significa «retrasado»?','answer'=>'tarde','options'=>['tarde','rápido','cancelado'],'hint'=>'Llega después de la hora prevista.'],
        ['type'=>'wordlock','prompt'=>'La maleta perdida','riddle'=>'Perdiste algo donde guardas tu ropa para viajar. 6 letras, empieza con «M».','answer'=>'maleta','hint'=>'La llevas al aeropuerto con tu ropa dentro.'],
        ['type'=>'riddle','prompt'=>'El avión y la cordillera','riddle'=>'El avión cruza las montañas de Antioquia. ¿Cómo se llaman las montañas largas de Colombia?','answer'=>'cordillera','options'=>['cordillera','volcán','cerro'],'hint'=>'Es una cadena de montañas. Colombia tiene tres.'],
        ['type'=>'sequence','prompt'=>'Antes de volar','riddle'=>'Ordena los pasos para tomar un vuelo.','answer'=>'llegar,mostrar pasaporte,esperar,abordar','options'=>['esperar','abordar','llegar','mostrar pasaporte'],'hint'=>'Primero llegas al aeropuerto.'],
        ['type'=>'wordlock','prompt'=>'La puerta de embarque','riddle'=>'Es tu primer viaje lejos de casa. Dices una palabra de 5 letras a tu familia. Empieza con «A».','answer'=>'adiós','hint'=>'El título dice: El primer...'],
    ]
];

// ----------------------------------------------------------
// DEST 14 — Jardín, Antioquia (A2, Candelaria cuenta)
// ----------------------------------------------------------
$rooms[14] = [
    'title' => 'La plaza donde los cuentos son reales',
    'instruction' => 'Candelaria se sienta en la plaza de Jardín y empieza a contar una historia. Pero le faltan palabras. Ayúdala a terminar.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La cueva del gallito','riddle'=>'Cerca de Jardín hay una cueva con una cascada dentro. Se llama Cueva del... ¿Qué brilla allí?','answer'=>'Esplendor','options'=>['Esplendor','Miedo','Silencio'],'hint'=>'Algo que brilla mucho tiene esplendor.'],
        ['type'=>'wordlock','prompt'=>'El pájaro rojo','riddle'=>'En los bosques de Jardín vive un pájaro con una cresta roja espectacular. Gallito de... 4 letras.','answer'=>'roca','hint'=>'Es una piedra grande. El gallito vive cerca de las...'],
        ['type'=>'riddle','prompt'=>'La historia de Candelaria','riddle'=>'Candelaria dice: «Ayer fui al mercado y compré...» ¿En qué tiempo verbal habla?','answer'=>'pasado','options'=>['pasado','presente','futuro'],'hint'=>'«Ayer» indica que ya pasó.'],
        ['type'=>'sequence','prompt'=>'El cuento de la plaza','riddle'=>'Candelaria cuenta un cuento. Ordena las partes.','answer'=>'había una vez,un día,entonces,y al final','options'=>['entonces','y al final','había una vez','un día'],'hint'=>'Los cuentos empiezan con «Había una vez».'],
        ['type'=>'wordlock','prompt'=>'La última palabra','riddle'=>'Candelaria cierra su historia. Lo que ella hace es... 6 letras, empieza con «C».','answer'=>'contar','hint'=>'Candelaria _______ historias.'],
    ]
];

// ----------------------------------------------------------
// DEST 15 — Jericó (A2, Contar lo que pasó)
// ----------------------------------------------------------
$rooms[15] = [
    'title' => 'La casa de Carrasquilla',
    'instruction' => 'En la casa museo de Tomás Carrasquilla, los personajes de sus cuentos te hablan desde los cuadros. Cada uno recuerda algo que pasó.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El escritor del pueblo','riddle'=>'Jericó es el pueblo de un gran escritor antioqueño. Escribió cuentos sobre la vida del campo. ¿Quién es?','answer'=>'Carrasquilla','options'=>['Carrasquilla','García Márquez','Pombo'],'hint'=>'Su casa museo está en Jericó. Tomás...'],
        ['type'=>'wordlock','prompt'=>'La palabra prohibida','riddle'=>'En Jericó, las historias se cuentan en un modo verbal especial. Expresa duda, deseo. 10 letras.','answer'=>'subjuntivo','hint'=>'Es un modo verbal: indicativo, _______, imperativo.'],
        ['type'=>'riddle','prompt'=>'El cuadro que habla','riddle'=>'Un personaje del cuadro dice: «Ojalá que llueva.» ¿Qué expresa esta frase?','answer'=>'deseo','options'=>['deseo','certeza','orden'],'hint'=>'«Ojalá» expresa algo que quieres que pase.'],
        ['type'=>'sequence','prompt'=>'La historia del pueblo','riddle'=>'Ordena la historia de Jericó cronológicamente.','answer'=>'fundación,Carrasquilla nace,escribe cuentos,hoy es museo','options'=>['hoy es museo','fundación','escribe cuentos','Carrasquilla nace'],'hint'=>'Primero se funda el pueblo.'],
        ['type'=>'wordlock','prompt'=>'El secreto del subjuntivo','riddle'=>'Lo que pasó en Jericó se cuenta de manera especial. La clave del destino: lo que... 4 letras.','answer'=>'pasó','hint'=>'Contar lo que _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 16 — Salento (A2, Lo que necesito)
// ----------------------------------------------------------
$rooms[16] = [
    'title' => 'El valle de las palmas gigantes',
    'instruction' => 'En el Valle de Cocora, las palmas de cera alcanzan 60 metros. Entre ellas hay cinco pistas escondidas sobre lo que realmente necesitas.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La palma más alta','riddle'=>'La palma de cera es el árbol nacional de Colombia. ¿Cuántos metros puede medir?','answer'=>'sesenta','options'=>['sesenta','veinte','cien'],'hint'=>'Mide hasta 60 metros de alto.'],
        ['type'=>'wordlock','prompt'=>'La bebida de Salento','riddle'=>'En las fincas de Salento cultivan algo que todo el mundo bebe por la mañana. 4 letras.','answer'=>'café','hint'=>'Colombia es famosa por esta bebida.'],
        ['type'=>'riddle','prompt'=>'Niebla entre las palmas','riddle'=>'Caminas por el valle y la niebla te rodea. Yaguará pregunta: «¿Qué necesitas ahora?» Tienes frío.','answer'=>'abrigo','options'=>['abrigo','agua','comida'],'hint'=>'Cuando tienes frío necesitas cubrirte.'],
        ['type'=>'sequence','prompt'=>'Necesidades básicas','riddle'=>'Ordena estas necesidades de la más urgente a la menos urgente.','answer'=>'respirar,beber,comer,dormir','options'=>['comer','respirar','dormir','beber'],'hint'=>'Sin respirar no puedes vivir ni un minuto.'],
        ['type'=>'wordlock','prompt'=>'La raíz de la palma','riddle'=>'Entre las palmas de cera descubres lo que realmente buscas. La palabra clave: lo que... 8 letras.','answer'=>'necesito','hint'=>'Lo que _______ está entre las palmas.'],
    ]
];

// ----------------------------------------------------------
// DEST 17 — Manizales (A2, Cómo era el mundo)
// ----------------------------------------------------------
$rooms[17] = [
    'title' => 'El amanecer sobre el volcán',
    'instruction' => 'Desde Manizales ves el Nevado del Ruiz al amanecer. El volcán guarda memorias de cómo era el mundo antes.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El volcán nevado','riddle'=>'El Nevado del Ruiz tiene nieve en la cima pero fuego adentro. ¿Qué tipo de montaña es?','answer'=>'volcán','options'=>['volcán','cerro','colina'],'hint'=>'Tiene lava dentro y a veces hace erupción.'],
        ['type'=>'wordlock','prompt'=>'El escritor del volcán','riddle'=>'Un famoso escritor colombiano también vio este volcán. Escribió «La vorágine». Apellido: 6 letras.','answer'=>'Rivera','hint'=>'José Eustasio _______.'],
        ['type'=>'riddle','prompt'=>'El imperfecto del volcán','riddle'=>'Yaguará dice: «Antes, el volcán _______ más alto.» ¿Qué verbo completa la frase en pasado?','answer'=>'era','options'=>['era','es','será'],'hint'=>'Imperfecto del verbo ser: yo era, él _______.'],
        ['type'=>'sequence','prompt'=>'La historia del Ruiz','riddle'=>'Ordena los eventos del volcán.','answer'=>'nace el volcán,crece la nieve,erupción,hoy se ve desde Manizales','options'=>['hoy se ve desde Manizales','erupción','nace el volcán','crece la nieve'],'hint'=>'El volcán nació hace millones de años.'],
        ['type'=>'wordlock','prompt'=>'La ventana al pasado','riddle'=>'El amanecer revela cómo era el mundo. La palabra clave del destino: cómo... 3 letras.','answer'=>'era','hint'=>'Imperfecto de ser. Cómo _______ el mundo.'],
    ]
];

// ----------------------------------------------------------
// DEST 18 — Honda (A2, El río no espera)
// ----------------------------------------------------------
$rooms[18] = [
    'title' => 'El reloj de arena del río Magdalena',
    'instruction' => 'En el puerto de Honda, el río Magdalena arrastra un reloj de arena gigante. Los peces de la subienda nadan contra el tiempo.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El río más largo','riddle'=>'El río que pasa por Honda es el más importante de Colombia. Nace en el sur y llega al mar Caribe. ¿Cómo se llama?','answer'=>'Magdalena','options'=>['Magdalena','Cauca','Amazonas'],'hint'=>'Comparte nombre con una región y una santa.'],
        ['type'=>'wordlock','prompt'=>'Los peces que suben','riddle'=>'Cada año, miles de peces suben por el río. Este fenómeno se llama... 8 letras.','answer'=>'subienda','hint'=>'Viene del verbo subir. Los peces suben el río.'],
        ['type'=>'riddle','prompt'=>'El puente colonial','riddle'=>'Honda tiene un puente histórico de hierro. Yaguará cruza y dice: «Este puente conecta dos...»','answer'=>'orillas','options'=>['orillas','ciudades','países'],'hint'=>'Los dos lados de un río se llaman...'],
        ['type'=>'sequence','prompt'=>'El viaje del pez','riddle'=>'Ordena el viaje del pez durante la subienda.','answer'=>'mar,río,subir,desovar','options'=>['subir','mar','desovar','río'],'hint'=>'Los peces empiezan en el mar.'],
        ['type'=>'wordlock','prompt'=>'La corriente final','riddle'=>'El río no para, no espera a nadie. La palabra del destino: el río no... 6 letras.','answer'=>'espera','hint'=>'Es lo contrario de irse. Es quedarse, pero con paciencia.'],
    ]
];

// Write first batch - will continue in next calls
echo "Rooms defined: " . count($rooms) . "\n";

// ----------------------------------------------------------
// DEST 19 — Villeta (B1, ¿Por qué vino este hombre?)
// ----------------------------------------------------------
$rooms[19] = [
    'title' => 'El trapiche de los secretos',
    'instruction' => 'En un trapiche panelero de Villeta, la caña de azúcar guarda una pregunta antigua: ¿por qué vino este hombre por el camino real?',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El dulce del camino','riddle'=>'En Villeta se produce algo dulce y oscuro que se hace con caña de azúcar. No es azúcar. ¿Qué es?','answer'=>'panela','options'=>['panela','miel','chocolate'],'hint'=>'Es un bloque duro y marrón que se disuelve en agua caliente.'],
        ['type'=>'wordlock','prompt'=>'El personaje del camino','riddle'=>'En «La vorágine», un hombre llamado Arturo bajó por un camino como este. Su apellido tiene 5 letras, empieza con «C».','answer'=>'Cova','hint'=>'Arturo _______, protagonista de La vorágine. 4 letras.'],
        ['type'=>'riddle','prompt'=>'La pregunta del trapiche','riddle'=>'El trapichero pregunta: «¿Por qué viniste?» ¿Qué tipo de pregunta es esta: busca causa, tiempo o lugar?','answer'=>'causa','options'=>['causa','tiempo','lugar'],'hint'=>'«Por qué» pregunta la razón, la...'],
        ['type'=>'sequence','prompt'=>'De la caña a la panela','riddle'=>'Ordena el proceso de hacer panela.','answer'=>'cortar caña,exprimir jugo,hervir,moldear','options'=>['moldear','cortar caña','hervir','exprimir jugo'],'hint'=>'Primero cortas la caña del campo.'],
        ['type'=>'wordlock','prompt'=>'La respuesta del camino','riddle'=>'La panela se endurece en el molde. La pregunta del destino queda: ¿Por qué _______ este hombre? 4 letras.','answer'=>'vino','hint'=>'Pasado simple de venir: él _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 20 — Bogotá, La Candelaria (B1, Las historias que escucho)
// ----------------------------------------------------------
$rooms[20] = [
    'title' => 'La chichería de las voces',
    'instruction' => 'En una chichería de La Candelaria, los parroquianos cuentan historias contradictorias. Descubre cuál es verdadera.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El museo dorado','riddle'=>'En Bogotá hay un museo con más de 55.000 piezas de oro precolombino. ¿Cómo se llama?','answer'=>'Museo del Oro','options'=>['Museo del Oro','Museo Botero','Museo Nacional'],'hint'=>'Su nombre describe su contenido: piezas de metal precioso amarillo.'],
        ['type'=>'wordlock','prompt'=>'La bebida ancestral','riddle'=>'En las chicherías se bebe una bebida fermentada de maíz que tomaban los muiscas. 6 letras.','answer'=>'chicha','hint'=>'La chichería toma su nombre de esta bebida.'],
        ['type'=>'riddle','prompt'=>'El narrador poco confiable','riddle'=>'Un hombre en la chichería dice: «Yo vi al libertador ayer.» Bolívar murió en 1830. ¿Qué hay en esta historia?','answer'=>'mentira','options'=>['mentira','verdad','exageración'],'hint'=>'Si Bolívar murió hace casi 200 años, no pudo verlo ayer.'],
        ['type'=>'sequence','prompt'=>'Las capas de la historia','riddle'=>'Ordena estos periodos históricos de Bogotá del más antiguo al más reciente.','answer'=>'muisca,colonia,independencia,hoy','options'=>['independencia','hoy','muisca','colonia'],'hint'=>'Los muiscas estaban antes de los españoles.'],
        ['type'=>'wordlock','prompt'=>'La verdad de la chichería','riddle'=>'Escuchas muchas voces en La Candelaria. La clave del destino: las historias que... 7 letras.','answer'=>'escucho','hint'=>'Yo _______ las historias. Primera persona de escuchar.'],
    ]
];

// ----------------------------------------------------------
// DEST 21 — Zipaquirá (B1, Las palabras que engañan)
// ----------------------------------------------------------
$rooms[21] = [
    'title' => 'La catedral bajo tierra',
    'instruction' => 'A 200 metros bajo tierra, la Catedral de Sal de Zipaquirá esconde acertijos donde nada es lo que parece. Las palabras engañan.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La mina que no es mina','riddle'=>'Este lugar parece una mina, pero no se extrae mineral. Parece una iglesia, pero está bajo tierra. ¿Qué es realmente?','answer'=>'catedral','options'=>['catedral','cueva','museo'],'hint'=>'Es un templo religioso muy grande, pero subterráneo.'],
        ['type'=>'wordlock','prompt'=>'El mineral brillante','riddle'=>'Las paredes brillan. Están hechas de un mineral blanco que usas en la comida. 3 letras.','answer'=>'sal','hint'=>'La pones en la sopa para darle sabor.'],
        ['type'=>'riddle','prompt'=>'La trampa lingüística','riddle'=>'Yaguará dice: «No todo lo que brilla es oro.» ¿Qué figura retórica usa esta frase?','answer'=>'refrán','options'=>['refrán','metáfora','ironía'],'hint'=>'Es una frase popular que transmite una enseñanza. Un dicho, un...'],
        ['type'=>'sequence','prompt'=>'Profundizar en la verdad','riddle'=>'Ordena estas frases de la más superficial a la más profunda.','answer'=>'esto es una mina,esto es un templo,las palabras engañan,la verdad está abajo','options'=>['las palabras engañan','esto es una mina','la verdad está abajo','esto es un templo'],'hint'=>'Empieza por la apariencia simple y termina con la verdad profunda.'],
        ['type'=>'wordlock','prompt'=>'La salida de la catedral','riddle'=>'Para salir debes aceptar que las palabras no siempre dicen la verdad. La clave: las palabras que... 7 letras.','answer'=>'engañan','hint'=>'Es lo contrario de decir la verdad.'],
    ]
];

// ----------------------------------------------------------
// DEST 22 — Villa de Leyva (B1, El tiempo de la paciencia)
// ----------------------------------------------------------
$rooms[22] = [
    'title' => 'La plaza de los fósiles',
    'instruction' => 'La plaza empedrada más grande de Colombia esconde fósiles de 130 millones de años. El tiempo aquí se mide en piedras, no en relojes.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El fósil del desierto','riddle'=>'Villa de Leyva tiene fósiles marinos en un desierto. ¿Cómo es posible? ¿Qué había aquí hace 130 millones de años?','answer'=>'mar','options'=>['mar','selva','volcán'],'hint'=>'Los fósiles son de animales marinos. Este desierto fue...'],
        ['type'=>'wordlock','prompt'=>'La virtud del fósil','riddle'=>'Un fósil esperó 130 millones de años para ser encontrado. ¿Qué virtud necesitó? 9 letras.','answer'=>'paciencia','hint'=>'El título del destino dice: el tiempo de la...'],
        ['type'=>'riddle','prompt'=>'El subjuntivo del tiempo','riddle'=>'«Si tuviera más tiempo, viajaría a todas las épocas.» ¿Qué modo verbal usa «tuviera»?','answer'=>'subjuntivo','options'=>['subjuntivo','indicativo','imperativo'],'hint'=>'Expresa una situación hipotética, no real.'],
        ['type'=>'sequence','prompt'=>'Las edades de la plaza','riddle'=>'Ordena estos periodos del más antiguo al más reciente.','answer'=>'fósiles marinos,dinosaurios,muiscas,hoy','options'=>['muiscas','hoy','fósiles marinos','dinosaurios'],'hint'=>'Los fósiles marinos son de hace 130 millones de años.'],
        ['type'=>'wordlock','prompt'=>'La piedra que espera','riddle'=>'Villa de Leyva enseña que lo verdadero necesita tiempo. La clave del destino: 6 letras, lo que pasa.','answer'=>'tiempo','hint'=>'El _______ de la paciencia.'],
    ]
];

// ----------------------------------------------------------
// DEST 23 — Tunja (B1, Lo que fue y lo que será)
// ----------------------------------------------------------
$rooms[23] = [
    'title' => 'Los techos pintados de sol y luna',
    'instruction' => 'Las casas coloniales de Tunja tienen techos pintados con soles y lunas. Cada pintura cuenta lo que fue y predice lo que será.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El techo que recuerda','riddle'=>'Los techos de Tunja están pintados con símbolos. El sol representa el día y la luna la noche. Juntos representan...','answer'=>'el tiempo','options'=>['el tiempo','el clima','la guerra'],'hint'=>'Sol y luna marcan el paso de los días, es decir, del...'],
        ['type'=>'wordlock','prompt'=>'La batalla del puente','riddle'=>'Cerca de Tunja hay un puente donde se decidió la independencia de Colombia. Puente de... 6 letras.','answer'=>'Boyacá','hint'=>'Es el nombre del departamento donde está Tunja.'],
        ['type'=>'riddle','prompt'=>'Pasado y futuro','riddle'=>'«Lo que fue no vuelve, pero lo que será depende de hoy.» ¿Qué tiempo verbal es «será»?','answer'=>'futuro','options'=>['futuro','pasado','presente'],'hint'=>'«Será» habla de algo que todavía no pasa.'],
        ['type'=>'sequence','prompt'=>'La línea del tiempo','riddle'=>'Ordena la historia de Tunja.','answer'=>'muiscas,colonia española,independencia,república','options'=>['independencia','muiscas','república','colonia española'],'hint'=>'Los muiscas estaban primero.'],
        ['type'=>'wordlock','prompt'=>'El techo final','riddle'=>'El último techo muestra el futuro. Lo que fue y lo que... 4 letras.','answer'=>'será','hint'=>'Futuro del verbo ser: _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 24 — Mompox (B1, Lo que otros dijeron)
// ----------------------------------------------------------
$rooms[24] = [
    'title' => 'El taller de filigrana',
    'instruction' => 'En Mompox, los orfebres hacen filigrana: hilos de oro y plata tan finos como palabras. Cada pieza repite lo que otros dijeron.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El río que se abre','riddle'=>'En Mompox, el río Magdalena se divide en dos brazos. Esta división se llama... como los brazos de una persona.','answer'=>'brazos','options'=>['brazos','bocas','cauces'],'hint'=>'El río se abre en... como cuando abres los brazos.'],
        ['type'=>'wordlock','prompt'=>'El arte del orfebre','riddle'=>'El arte de hacer joyas con hilos de oro y plata muy finos. 9 letras.','answer'=>'filigrana','hint'=>'Es el arte más famoso de Mompox.'],
        ['type'=>'riddle','prompt'=>'La cita famosa','riddle'=>'García Márquez dijo que Mompox «no existe». Cuando citas las palabras exactas de otra persona, usas el estilo...','answer'=>'directo','options'=>['directo','indirecto','libre'],'hint'=>'Estilo _______ = palabras exactas entre comillas.'],
        ['type'=>'sequence','prompt'=>'Voces a través del río','riddle'=>'Ordena estas formas de citar, de la más fiel a la más libre.','answer'=>'cita textual,estilo directo,estilo indirecto,paráfrasis','options'=>['paráfrasis','cita textual','estilo indirecto','estilo directo'],'hint'=>'La cita textual reproduce las palabras exactas.'],
        ['type'=>'wordlock','prompt'=>'El eco de Mompox','riddle'=>'Las voces llegan por el río. La clave: lo que otros... 7 letras.','answer'=>'dijeron','hint'=>'Pasado de decir: ellos _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 25 — Yopal (B1, Si pudiera cambiar algo)
// ----------------------------------------------------------
$rooms[25] = [
    'title' => 'El hato llanero del subjuntivo',
    'instruction' => 'En los llanos de Yopal, un viejo llanero monta a caballo y habla de lo que cambiaría si pudiera. Cada acertijo es una posibilidad.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El llano infinito','riddle'=>'Yopal está en una llanura que se extiende hasta el horizonte. ¿Cómo se llaman estas tierras planas de Colombia?','answer'=>'llanos','options'=>['llanos','pampas','sabana'],'hint'=>'Se llaman Los _______ Orientales.'],
        ['type'=>'wordlock','prompt'=>'El animal del llanero','riddle'=>'El llanero monta un animal fuerte para cruzar los llanos. 7 letras.','answer'=>'caballo','hint'=>'El mejor amigo del llanero. Tiene cuatro patas y crines.'],
        ['type'=>'riddle','prompt'=>'La condición del vaquero','riddle'=>'«Si pudiera volar, vería toda la llanura.» ¿Qué tipo de oración es esta?','answer'=>'condicional','options'=>['condicional','imperativa','interrogativa'],'hint'=>'Empieza con «Si» y plantea una condición irreal.'],
        ['type'=>'sequence','prompt'=>'La cadena del deseo','riddle'=>'Ordena la lógica del condicional irreal.','answer'=>'deseo,condición,consecuencia,realidad','options'=>['realidad','deseo','consecuencia','condición'],'hint'=>'Primero deseas algo, luego imaginas la condición.'],
        ['type'=>'wordlock','prompt'=>'El atardecer llanero','riddle'=>'El llanero mira el atardecer y suspira. Si pudiera cambiar... 4 letras.','answer'=>'algo','hint'=>'La palabra más indefinida. Si pudiera cambiar _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 26 — Chicaque (B1, La voz pasiva del bosque)
// ----------------------------------------------------------
$rooms[26] = [
    'title' => 'El sendero del musgo silencioso',
    'instruction' => 'En la reserva de Chicaque, el bosque de niebla habla en voz pasiva. Los árboles no actúan: son actuados por el viento, la lluvia, el tiempo.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'El bosque cubierto','riddle'=>'Los árboles de Chicaque están cubiertos de algo verde y suave. No son hojas. ¿Qué es?','answer'=>'musgo','options'=>['musgo','hierba','algas'],'hint'=>'Crece sobre las rocas y los troncos en lugares húmedos.'],
        ['type'=>'wordlock','prompt'=>'La niebla que cubre','riddle'=>'«El bosque es cubierto por la niebla.» Esta oración está en voz... 6 letras.','answer'=>'pasiva','hint'=>'Lo opuesto de voz activa.'],
        ['type'=>'riddle','prompt'=>'Activa o pasiva','riddle'=>'«El viento mueve las hojas» es activa. ¿Cómo se dice en pasiva?','answer'=>'Las hojas son movidas por el viento','options'=>['Las hojas son movidas por el viento','Las hojas mueven el viento','El viento es movido'],'hint'=>'En la pasiva, el objeto se convierte en sujeto.'],
        ['type'=>'sequence','prompt'=>'Los agentes del bosque','riddle'=>'Ordena estos agentes naturales del más silencioso al más ruidoso.','answer'=>'musgo,niebla,lluvia,tormenta','options'=>['lluvia','musgo','tormenta','niebla'],'hint'=>'El musgo crece en completo silencio.'],
        ['type'=>'wordlock','prompt'=>'El silencio del canopy','riddle'=>'En la copa del árbol, todo es silencio. La voz _______ del bosque. 6 letras.','answer'=>'pasiva','hint'=>'El bosque no grita, recibe. Su voz es...'],
    ]
];

// ----------------------------------------------------------
// DEST 27 — Barichara (B1, Las voces que se cruzan)
// ----------------------------------------------------------
$rooms[27] = [
    'title' => 'El camino real de las piedras talladas',
    'instruction' => 'El Camino Real de Barichara a Guane tiene piedras talladas a mano hace siglos. Cada piedra es una voz que cruza el tiempo.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La piedra del artesano','riddle'=>'Barichara está construida con piedra tallada a mano. Esta técnica ancestral se llama...','answer'=>'tapia pisada','options'=>['tapia pisada','ladrillo','cemento'],'hint'=>'Es una técnica con tierra compactada entre moldes.'],
        ['type'=>'wordlock','prompt'=>'El pueblo más lindo','riddle'=>'Barichara es conocida como el pueblo más _______ de Colombia. 5 letras.','answer'=>'lindo','hint'=>'Sinónimo de bonito, hermoso.'],
        ['type'=>'riddle','prompt'=>'Las voces del camino','riddle'=>'En el Camino Real, un viajero del pasado y uno del presente dicen lo mismo pero con palabras distintas. Esto es un ejemplo de...','answer'=>'registro','options'=>['registro','dialecto','acento'],'hint'=>'Formal vs. informal es una diferencia de...'],
        ['type'=>'sequence','prompt'=>'Las capas de la piedra','riddle'=>'Ordena la historia de Barichara por sus voces.','answer'=>'guane precolombino,colonia española,artesanos,turistas de hoy','options'=>['turistas de hoy','colonia española','guane precolombino','artesanos'],'hint'=>'Los guanes estaban antes que los españoles.'],
        ['type'=>'wordlock','prompt'=>'El cruce de voces','riddle'=>'Todas las voces se encuentran en Barichara. Las voces que se... 6 letras.','answer'=>'cruzan','hint'=>'Cuando dos caminos se encuentran, se _______.'],
    ]
];

// ----------------------------------------------------------
// DEST 28 — Cañón del Chicamocha (B1, La herida y el nombre)
// ----------------------------------------------------------
$rooms[28] = [
    'title' => 'El teleférico sobre la herida de la tierra',
    'instruction' => 'El teleférico cruza el Cañón del Chicamocha, una herida de 2 km de profundidad. Mientras cruzas, cada acertijo toca más hondo.',
    'timeLimit' => 300,
    'puzzles' => [
        ['type'=>'riddle','prompt'=>'La profundidad del cañón','riddle'=>'El Chicamocha es el segundo cañón más profundo del mundo. Un cañón es una grieta enorme en la tierra. ¿Qué la creó?','answer'=>'el río','options'=>['el río','un terremoto','el viento'],'hint'=>'El agua del río erosionó la roca durante millones de años.'],
        ['type'=>'wordlock','prompt'=>'La palabra que duele','riddle'=>'Una herida en la tierra, una herida en el cuerpo, una herida en las palabras. Todas comparten esta palabra. 6 letras.','answer'=>'herida','hint'=>'El título dice: La _______ y el nombre.'],
        ['type'=>'riddle','prompt'=>'El nombre del dolor','riddle'=>'«Chicamocha» viene de una lengua indígena. Cuando una palabra tiene origen en otra lengua, se llama...','answer'=>'préstamo','options'=>['préstamo','neologismo','arcaísmo'],'hint'=>'Es una palabra prestada de otro idioma.'],
        ['type'=>'sequence','prompt'=>'De la superficie al fondo','riddle'=>'Ordena estas metáforas de la más superficial a la más profunda.','answer'=>'el nombre,la marca,la cicatriz,la herida','options'=>['la cicatriz','el nombre','la herida','la marca'],'hint'=>'El nombre es lo más superficial, la herida lo más profundo.'],
        ['type'=>'wordlock','prompt'=>'El fondo del cañón','riddle'=>'En el fondo del cañón, el río susurra tu nombre. La herida y el... 6 letras.','answer'=>'nombre','hint'=>'Lo que te identifica. Tu _______.'],
    ]
];

echo "Rooms defined: " . count($rooms) . "\n";

// Save progress and continue
file_put_contents('/tmp/escape-rooms-batch1.json', json_encode($rooms, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Batch 1 (dest 1-28) saved to /tmp/escape-rooms-batch1.json\n";
'