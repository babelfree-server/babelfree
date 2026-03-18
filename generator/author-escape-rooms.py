#!/usr/bin/env python3
"""
author-escape-rooms.py
Authors escape room puzzles for dest13-58.
Fills in room descriptions, ambiences, and puzzle content (prompt, clue, answer).
Removes _needsContent flags from escape rooms.
"""

import json
from pathlib import Path

CONTENT_DIR = Path('/home/babelfree.com/public_html/content')

# ============================================================
# ESCAPE ROOM CONTENT: dest13-58
# Each entry: room (description, ambience), puzzles list
# Puzzle types: wordlock, cipher, riddle, sequence, extract, logic, synthesis
# ============================================================

ESCAPE_ROOMS = {
    # === A2 (dest13-18) — "Las raíces" quest arc ===
    13: {
        'room': {
            'description': 'Una gruta húmeda detrás de una cascada. El agua cae como cortina de cristal.',
            'ambience': 'Agua cayendo. Eco profundo.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Lo que hiciste ayer. El tiempo verbal del pasado. Tiene 9 letras.', 'clue': '_ _ _ _ _ _ _ _ _', 'answer': 'preterito'}
        ]
    },
    14: {
        'room': {
            'description': 'Un túnel de raíces con marcas en la corteza. Cada marca es una fecha.',
            'ambience': 'Crujido de raíces. Candelaria susurra.'
        },
        'puzzles': [
            {'puzzleType': 'cipher', 'prompt': 'Candelaria dejó un mensaje cifrado: cambia cada consonante por la siguiente. PBTÓ → ?', 'clue': 'P→Q, B→C, T→V. Las vocales no cambian.', 'answer': 'qcvo'}
        ]
    },
    15: {
        'room': {
            'description': 'Una sala con dos puertas: una dice NECESITAR, otra dice QUERER.',
            'ambience': 'Viento entre las puertas. Decisión.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'No es lo que comes ni lo que bebes. Es lo que tu cuerpo pide para seguir caminando. Yaguará lo necesita todos los días.', 'clue': 'Cierras los ojos y lo haces cada noche.', 'answer': 'dormir'}
        ]
    },
    16: {
        'room': {
            'description': 'Una esfera de vidrio que muestra el cielo. Las nubes se mueven dentro.',
            'ambience': 'Truenos lejanos. Gotas en el vidrio.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Cae del cielo cuando las nubes son grises. Tiene 6 letras.', 'clue': '_ _ _ _ _ _', 'answer': 'lluvia'}
        ]
    },
    17: {
        'room': {
            'description': 'Una sala circular con espejos viejos. Cada espejo muestra un momento diferente.',
            'ambience': 'Susurros del pasado. Melodía antigua.'
        },
        'puzzles': [
            {'puzzleType': 'sequence', 'prompt': 'Los espejos muestran tiempos verbales en orden: presente, pretérito, ___. ¿Cuál es el tercer espejo?', 'clue': 'El tiempo que describe cómo eran las cosas.', 'answer': 'imperfecto'}
        ]
    },
    18: {
        'room': {
            'description': 'La puerta más grande que has visto. Tres cerraduras. Detrás: el Mundo del Medio.',
            'ambience': 'Viento que llama. Luz del otro lado.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Soy la línea entre lo conocido y lo desconocido. Cuando me cruzas, ya no puedes volver atrás.', 'clue': 'Empieza con U. Es el límite entre dos mundos.', 'answer': 'umbral'},
            {'puzzleType': 'sequence', 'prompt': 'nombrar → describir → contar → expresar → ___. ¿Qué viene después?', 'clue': 'Lo que Yaguará aprendió al final del Mundo de Abajo.', 'answer': 'recordar'},
            {'puzzleType': 'wordlock', 'prompt': 'El animal que camina entre mundos. Empieza con J. 6 letras.', 'clue': '_ _ _ _ _ _', 'answer': 'jaguar'}
        ]
    },

    # === B1 (dest19-28) — "Las historias" quest arc ===
    19: {
        'room': {
            'description': 'Un puesto del mercado cubierto con telas de colores. Hay objetos de cada región.',
            'ambience': 'Voces del mercado. Música de marimba.'
        },
        'puzzles': [
            {'puzzleType': 'sequence', 'prompt': 'Ordena la historia: Yaguará salió del bosque → ___ → Candelaria tradujo → Don Próspero apareció', 'clue': 'Lo que hizo Yaguará al ver el pueblo por primera vez.', 'answer': 'entro en el pueblo'}
        ]
    },
    20: {
        'room': {
            'description': 'Una casa de madera junto al río. Las paredes están llenas de historias pintadas.',
            'ambience': 'El río murmura. Una abuela cuenta historias.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Dicen que es posible. Dicen que ojalá. Dicen que quizás. ¿Qué modo verbal expresa duda y deseo?', 'clue': 'No es indicativo. Empieza con S.', 'answer': 'subjuntivo'}
        ]
    },
    21: {
        'room': {
            'description': 'Una cueva en la montaña. Escalones de piedra suben en espiral.',
            'ambience': 'Viento de montaña. Eco de pasos.'
        },
        'puzzles': [
            {'puzzleType': 'sequence', 'prompt': 'Completa la secuencia de conectores: primero → después → luego → sin embargo → ___', 'clue': 'El último paso del argumento. Empieza con F.', 'answer': 'finalmente'}
        ]
    },
    22: {
        'room': {
            'description': 'Una sala amplia con un arpa llanera en el centro. Las cuerdas vibran solas.',
            'ambience': 'Cuerdas del arpa. Viento del llano.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Dos tiempos conviven: lo que pasó de golpe y lo que pasaba siempre. Llovía cuando llegamos. ¿Cómo se llama mezclar estos dos tiempos?', 'clue': 'Es una forma de narrar. Combina pretérito e imperfecto.', 'answer': 'contraste'}
        ]
    },
    23: {
        'room': {
            'description': 'Una bóveda submarina de coral. Las paredes brillan con bioluminiscencia.',
            'ambience': 'Burbujas. Olas amortiguadas.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"Ella dijo que el mar sabe esperar. Él respondió que las olas no mienten." Extrae el verbo principal del estilo indirecto.', 'clue': 'Es el verbo que introduce lo que alguien dijo.', 'answer': 'dijo'}
        ]
    },
    24: {
        'room': {
            'description': 'Dos caminos se cruzan en una sala con dos puertas. Cada puerta tiene una emoción escrita.',
            'ambience': 'Dos voces hablan al mismo tiempo.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"Me alegra que estés aquí. Temo que destruyan el bosque." Extrae las dos emociones que activan el subjuntivo.', 'clue': 'Una es positiva y otra negativa.', 'answer': 'alegria y temor'}
        ]
    },
    25: {
        'room': {
            'description': 'Una oficina improvisada en la selva. Hay un contrato sobre la mesa con letras pequeñas.',
            'ambience': 'Bolígrafo en papel. Don Próspero suspira.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Don Próspero dice: "Si tuviera más tierra, haría grandes cosas." Pero si hiciera grandes cosas, destruiría el bosque. Si destruye el bosque, no tiene más tierra. ¿Cuál es la paradoja?', 'clue': 'Lo que promete se destruye al cumplirlo.', 'answer': 'destruye lo que necesita'}
        ]
    },
    26: {
        'room': {
            'description': 'Una sala vacía donde antes había algo. Solo quedan marcas en el suelo.',
            'ambience': 'Silencio pesado. Eco de lo que fue.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"El bosque fue cortado. El río fue contaminado. Las voces fueron silenciadas." ¿Quién hizo estas acciones? La voz pasiva lo oculta.', 'clue': 'La frase no dice quién. Ese es el punto.', 'answer': 'no se dice'}
        ]
    },
    27: {
        'room': {
            'description': 'Una sala con dos puertas: una dice PROGRESO, otra dice NATURALEZA. Ambas están abiertas.',
            'ambience': 'Debate lejano. Pájaros y máquinas.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Don Próspero: "El camino trae trabajo." Candelaria: "El camino destruye el bosque." Los dos tienen evidencia. ¿Es posible que ambos tengan razón?', 'clue': 'La respuesta no es sí ni no.', 'answer': 'ambos tienen razon'}
        ]
    },
    28: {
        'room': {
            'description': 'Una sala cálida con una hamaca. La voz de Doña Asunción resuena en las paredes.',
            'ambience': 'Respiración tranquila. Pájaros cantando lejos.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': 'Doña Asunción dijo: "Las palabras tienen memoria." Si las palabras tienen memoria y tú usas palabras, ¿qué llevas contigo?', 'clue': 'Lo que las palabras recuerdan vive en ti.', 'answer': 'la memoria de otros'}
        ]
    },

    # === B2 (dest29-38) — "Las historias" quest arc continues ===
    29: {
        'room': {
            'description': 'Una sala sin color. Todo es gris. Las paredes absorben el sonido.',
            'ambience': 'Silencio total. Tu propia respiración.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': '"Si hubiera escuchado antes, esto no habría pasado." Pero no escuchó. ¿Se puede cambiar lo que ya pasó con el subjuntivo pasado?', 'clue': 'El subjuntivo pasado expresa algo que no ocurrió.', 'answer': 'no se puede cambiar'}
        ]
    },
    30: {
        'room': {
            'description': 'Un espejo que no refleja tu cara. Refleja tus palabras escritas en el aire.',
            'ambience': 'Susurros propios. Eco invertido.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Siempre te sigo. Cuando hay luz, me ves. Cuando oscurece, desaparezco. Pero nunca dejo de existir.', 'clue': 'Empieza con S. Es la parte de ti que no habla en voz alta.', 'answer': 'sombra'}
        ]
    },
    31: {
        'room': {
            'description': 'Una mesa redonda con sillas para seis personas. Solo dos están ocupadas.',
            'ambience': 'Murmullos de negociación. Café humeante.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': '"Si yo fuera usted, consideraría otra opción." ¿Es una orden, un consejo o una amenaza? Depende del tono y el contexto.', 'clue': 'La forma es condicional. La intención puede variar.', 'answer': 'depende del contexto'}
        ]
    },
    32: {
        'room': {
            'description': 'Una sala dividida en dos. Un lado es una oficina elegante. El otro, una cocina humilde.',
            'ambience': 'Teclas de computador y olla hirviendo.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Mensaje formal: "Se solicita su colaboración." Mensaje informal: "Ayúdame, porfa." ¿Dicen lo mismo? Si sí, ¿por qué existen dos formas?', 'clue': 'El contenido es igual. La relación entre hablantes es diferente.', 'answer': 'la relacion cambia'}
        ]
    },
    33: {
        'room': {
            'description': 'Un archivo polvoriento lleno de cajones. Cada cajón tiene un nombre que nadie recuerda.',
            'ambience': 'Papel viejo. Polvo en el aire.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': 'Aquí se guardan las palabras olvidadas. Para abrir la puerta, construye un argumento: ¿por qué es importante recordar las palabras que nadie usa?', 'clue': 'Piensa en lo que se pierde cuando una palabra desaparece.', 'answer': 'se pierde un mundo'}
        ]
    },
    34: {
        'room': {
            'description': 'Una oficina abandonada con documentos amarillentos. Un mapa del río cubre toda una pared.',
            'ambience': 'Viento por la ventana rota. Papel que se mueve.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"El río, cuyas aguas recuerdan cada voz que las tocó, fluye hacia un mar que nadie ha visto." Extrae la cláusula relativa.', 'clue': 'Empieza con "cuyas".', 'answer': 'cuyas aguas recuerdan cada voz que las toco'},
            {'puzzleType': 'wordlock', 'prompt': 'El pronombre relativo que indica posesión. Tiene 5 letras.', 'clue': '_ _ _ _ _', 'answer': 'cuyas'},
            {'puzzleType': 'logic', 'prompt': 'Si el río recuerda cada voz, y nadie ha visto el mar al que fluye, ¿adónde van las memorias?', 'clue': 'El mar es una metáfora.', 'answer': 'al olvido'}
        ]
    },
    35: {
        'room': {
            'description': 'Una sala con manuscritos en las paredes. Cada texto tiene marcas de análisis.',
            'ambience': 'Pluma en pergamino. Luz de vela.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"El autor emplea la metáfora del río como símbolo de la memoria colectiva." Extrae el recurso literario mencionado.', 'clue': 'Es una figura retórica que compara sin usar "como".', 'answer': 'metafora'},
            {'puzzleType': 'riddle', 'prompt': 'Digo una cosa pero quiero decir otra. Comparo sin decir "como". ¿Qué soy?', 'clue': 'Empieza con M. Es una figura literaria.', 'answer': 'metafora'},
            {'puzzleType': 'wordlock', 'prompt': 'Lo que el río simboliza según el texto. 7 letras.', 'clue': '_ _ _ _ _ _ _', 'answer': 'memoria'}
        ]
    },
    36: {
        'room': {
            'description': 'Una galería de espejos donde cada espejo muestra un precio diferente para el mismo bosque.',
            'ambience': 'Números que cambian. Cristal que refleja.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Tengo raíces pero no soy planta. Tengo hojas pero no soy libro. Tengo tronco pero no soy cuerpo. Doy aire pero no soy ventilador.', 'clue': 'Empieza con A. Vive en el bosque.', 'answer': 'arbol'},
            {'puzzleType': 'logic', 'prompt': 'Un bosque produce oxígeno, agua limpia y biodiversidad. Estos beneficios valen más que la madera. Pero solo la madera se puede vender. ¿Cómo se resuelve esta contradicción?', 'clue': 'El valor real no tiene precio de mercado.', 'answer': 'dar valor a lo invisible'},
            {'puzzleType': 'synthesis', 'prompt': 'Combina estas ideas: "el desarrollo genera empleo" + "la biodiversidad no tiene precio" = ¿Qué modelo económico necesitamos?', 'clue': 'Un modelo que incluya lo que no se puede comprar.', 'answer': 'desarrollo sostenible'}
        ]
    },
    37: {
        'room': {
            'description': 'Una sala donde los sonidos del mercado se mezclan con el canto de los pájaros.',
            'ambience': 'Regateo. Cantos de aves que se apagan.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Desaparezco cuando nadie me mira. Pero mi ausencia cambia todo. Soy pequeña y colorida. Polinizo flores. Estoy en peligro.', 'clue': 'Un insecto esencial. Empieza con A.', 'answer': 'abeja'},
            {'puzzleType': 'extract', 'prompt': '"Aunque el desarrollo genera empleo, resulta imperativo que este no comprometa la biodiversidad, dado que los ecosistemas sustentan la vida." Extrae la concesión.', 'clue': 'La parte que reconoce el punto del oponente.', 'answer': 'aunque el desarrollo genera empleo'},
            {'puzzleType': 'cipher', 'prompt': 'Código: cada palabra es la primera letra. Todo Está Conectado Al Destino Español Natural Ahora = ?', 'clue': 'Lee solo las iniciales.', 'answer': 'tecadena'}
        ]
    },
    38: {
        'room': {
            'description': 'Un tribunal junto al río. Sillas de piedra en semicírculo. La naturaleza es la jueza.',
            'ambience': 'Agua corriente. Gaviotas. Silencio solemne.'
        },
        'puzzles': [
            {'puzzleType': 'sequence', 'prompt': 'El discurso avanza: narración → descripción → argumentación → ___. ¿Qué integra todo?', 'clue': 'El paso final que une todos los tipos de discurso.', 'answer': 'reflexion'},
            {'puzzleType': 'logic', 'prompt': 'Si Doña Asunción ya no está, y su voz vive en las palabras que dejó, ¿está realmente muerta?', 'clue': 'Las palabras sobreviven a quien las dice.', 'answer': 'vive en las palabras'},
            {'puzzleType': 'riddle', 'prompt': 'No soy persona pero tengo voz. No soy libro pero cuento historias. No soy reloj pero guardo el tiempo. Fluyo siempre hacia adelante.', 'clue': 'Empieza con R. Está en el tribunal.', 'answer': 'rio'}
        ]
    },

    # === C1 (dest39-48) — "Las constelaciones" quest arc ===
    39: {
        'room': {
            'description': 'Una cueva profunda donde las sombras forman palabras en la pared.',
            'ambience': 'Goteo constante. Eco multiplicado.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"Lo que la montaña susurra cuando nadie escucha es precisamente aquello que, al ser ignorado, transforma el silencio en olvido." Extrae la oración subordinada principal.', 'clue': 'Empieza con "lo que".', 'answer': 'lo que la montana susurra cuando nadie escucha'},
            {'puzzleType': 'logic', 'prompt': 'Si el silencio se transforma en olvido cuando se ignora, ¿qué transforma el olvido en memoria?', 'clue': 'Lo opuesto de ignorar.', 'answer': 'escuchar'},
            {'puzzleType': 'synthesis', 'prompt': 'Combina: "la montaña habla" + "solo quien sube escucha" + "el silencio es olvido". ¿Cuál es la tesis?', 'clue': 'El conocimiento requiere esfuerzo y atención.', 'answer': 'el saber exige escuchar'}
        ]
    },
    40: {
        'room': {
            'description': 'Un laberinto de estantes con libros que se mueven. Cada libro tiene una tesis diferente.',
            'ambience': 'Páginas que pasan solas. Murmullos académicos.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Premisa 1: La transmisión oral preserva cultura. Premisa 2: La escritura reemplaza la oralidad. Conclusión: ¿qué se pierde con la escritura?', 'clue': 'Lo que la oralidad tiene que la escritura no.', 'answer': 'la voz viva'},
            {'puzzleType': 'sequence', 'prompt': 'Método científico: observación → hipótesis → experimentación → ___', 'clue': 'Lo que se hace con los resultados.', 'answer': 'conclusion'},
            {'puzzleType': 'synthesis', 'prompt': '"La evidencia empírica sustenta la hipótesis." Si la hipótesis es que la oralidad preserva mejor que la escritura, ¿qué evidencia necesitas?', 'clue': 'Piensa en culturas sin escritura que conservan su historia.', 'answer': 'culturas orales con historia viva'}
        ]
    },
    41: {
        'room': {
            'description': 'Un jardín interior con plantas de cada región de Colombia. Cada planta habla diferente.',
            'ambience': 'Acentos mezclados. Hojas que susurran dialectos.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'En Cali dicen "mirá ve". En Medellín dicen "¿qué más, pues?". En Bogotá dicen "quiubo". Soy el mismo idioma pero sueno diferente. ¿Qué soy?', 'clue': 'Empieza con D. Es una variación regional.', 'answer': 'dialecto'},
            {'puzzleType': 'extract', 'prompt': '"Mira, ve" (Cali), "¿Qué más, pues?" (Medellín), "¡Ey, vale!" (Caribe). Extrae el elemento común que identifica a cada región.', 'clue': 'No es una palabra. Es una forma de hablar.', 'answer': 'la muletilla regional'},
            {'puzzleType': 'logic', 'prompt': 'Si el voseo es "incorrecto" según la RAE pero millones lo usan, ¿quién tiene razón: la academia o los hablantes?', 'clue': 'La lengua pertenece a quien la habla.', 'answer': 'los hablantes'}
        ]
    },
    42: {
        'room': {
            'description': 'Una sala circular donde las paredes son telares. Cada hilo es una expresión idiomática.',
            'ambience': 'Telar que trabaja. Hilos que vibran.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'No estoy en ningún lugar pero me echas. No soy flor pero doy a luz. No soy matemática pero me tienes en cuenta. ¿Qué tipo de lenguaje soy?', 'clue': 'Expresiones que no significan lo que dicen literalmente.', 'answer': 'idiomatico'},
            {'puzzleType': 'logic', 'prompt': '"Echar de menos" no es un acto de lanzar ni una operación matemática. Si las expresiones no significan lo que dicen, ¿cómo las aprende un extranjero?', 'clue': 'No se puede deducir. Solo se puede...', 'answer': 'vivir el idioma'},
            {'puzzleType': 'synthesis', 'prompt': 'Las expresiones idiomáticas son historias comprimidas. "Dar a luz" comprime el nacimiento en tres palabras. ¿Qué dice esto sobre el lenguaje?', 'clue': 'El lenguaje es más que gramática.', 'answer': 'el lenguaje comprime experiencia'}
        ]
    },
    43: {
        'room': {
            'description': 'Una sala donde los hilos de luz conectan frases en el aire. Algunos hilos están rotos.',
            'ambience': 'Zumbido de energía. Luz pulsante.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': 'Conecta estas ideas usando un conector avanzado: "El río está contaminado" + "La gente sigue bebiendo de él". ¿Qué conector une estas ideas?', 'clue': 'Un conector que expresa contradicción.', 'answer': 'a pesar de que'},
            {'puzzleType': 'logic', 'prompt': 'Si un texto tiene ideas perfectas pero no tiene conectores, ¿se entiende? Si tiene conectores perfectos pero ideas vacías, ¿vale algo?', 'clue': 'Se necesitan ambos.', 'answer': 'ideas y conexion juntas'},
            {'puzzleType': 'extract', 'prompt': '"En consecuencia, la deforestación afecta no solo la biodiversidad sino también el ciclo del agua, lo cual repercute de manera análoga en la agricultura." Extrae los dos conectores avanzados.', 'clue': 'Uno indica resultado. Otro indica semejanza.', 'answer': 'en consecuencia y de manera analoga'}
        ]
    },
    44: {
        'room': {
            'description': 'Un templo de piedra oscura. Las paredes tienen discursos grabados. El silencio es pesado.',
            'ambience': 'Piedra fría. Silencio que pesa. Eco distante.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': '"La elección léxica del hablante sugiere una posición ideológica." Si alguien dice "recursos naturales" en vez de "naturaleza", ¿qué posición revela?', 'clue': 'Una palabra ve la naturaleza como materia prima.', 'answer': 'vision economica'},
            {'puzzleType': 'logic', 'prompt': 'Un político dice "daños colaterales" en vez de "muertes civiles". ¿Qué logra con esta elección léxica?', 'clue': 'Reduce el impacto emocional.', 'answer': 'ocultar la realidad'},
            {'puzzleType': 'extract', 'prompt': '"Si bien no se explicita, la posición ideológica permea cada enunciado." Extrae la concesión y la afirmación principal.', 'clue': 'La concesión empieza con "si bien".', 'answer': 'concesion si bien no se explicita y afirmacion permea cada enunciado'}
        ]
    },
    45: {
        'room': {
            'description': 'Una escalera de piedra que sube tres pisos. Cada piso tiene un nombre: Ethos, Pathos, Logos.',
            'ambience': 'Pasos en piedra. Voz que resuena.'
        },
        'puzzles': [
            {'puzzleType': 'logic', 'prompt': 'Un discurso usa ethos (credibilidad), pathos (emoción) y logos (lógica). Si solo usas logos, convences pero no mueves. Si solo usas pathos, mueves pero no convences. ¿Qué necesitas?', 'clue': 'La respuesta combina los tres.', 'answer': 'los tres juntos'},
            {'puzzleType': 'synthesis', 'prompt': 'Construye un argumento que use las tres piedras: ethos ("como ciudadano"), pathos ("el sufrimiento"), logos ("los datos"). ¿Cuál es la tesis más fuerte?', 'clue': 'Une credibilidad, emoción y evidencia.', 'answer': 'debemos actuar con responsabilidad ante la evidencia del sufrimiento'}
        ]
    },
    46: {
        'room': {
            'description': 'Una biblioteca antigua donde los libros hablan entre sí. Las voces se cruzan.',
            'ambience': 'Murmullos de libros. Páginas que se abren solas.'
        },
        'puzzles': [
            {'puzzleType': 'extract', 'prompt': '"Al evocar la soledad macondiana de García Márquez, el autor resignifica el aislamiento digital." Extrae la referencia intertextual.', 'clue': 'Es la referencia a otra obra literaria.', 'answer': 'la soledad macondiana de garcia marquez'},
            {'puzzleType': 'synthesis', 'prompt': 'García Márquez escribió sobre Macondo (aislamiento). Un autor moderno escribe sobre redes sociales (conexión). ¿Qué paradoja revela la intertextualidad?', 'clue': 'Estamos más conectados pero más solos.', 'answer': 'la conexion digital es soledad nueva'},
            {'puzzleType': 'logic', 'prompt': 'Si todo texto dialoga con textos anteriores, ¿existe un texto completamente original?', 'clue': 'La originalidad absoluta es imposible si el lenguaje es compartido.', 'answer': 'no existe texto sin influencia'}
        ]
    },
    47: {
        'room': {
            'description': 'Una sala donde los nombres flotan en el aire. Algunos brillan. Otros se desvanecen.',
            'ambience': 'Nombres susurrados. Brillo intermitente.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': 'Los Wayúu tienen veinte palabras para lluvia. El español tiene una. ¿Qué dice esto sobre la relación entre lenguaje y percepción?', 'clue': 'Más palabras = más distinciones perceptivas.', 'answer': 'el lenguaje moldea la percepcion'},
            {'puzzleType': 'riddle', 'prompt': 'Precedo al pensamiento o el pensamiento me precede. Nadie sabe quién llegó primero. Soy herramienta y prisión. Soy libertad y límite.', 'clue': 'Empieza con L. Es el tema de la filosofía del lenguaje.', 'answer': 'lenguaje'},
            {'puzzleType': 'logic', 'prompt': 'Si nombrar algo le da existencia, ¿existen las cosas que no tienen nombre?', 'clue': 'La pregunta es más importante que la respuesta.', 'answer': 'existen pero no las vemos'}
        ]
    },
    48: {
        'room': {
            'description': 'Una cúpula abierta al cielo nocturno. Una estrella brilla más que las demás.',
            'ambience': 'Viento nocturno. Estrellas que susurran.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': 'Has recorrido el Mundo de Arriba. Has analizado discursos, conectado textos, cuestionado el lenguaje. ¿Cuál es la pregunta más importante que el lenguaje puede hacer?', 'clue': 'No es sobre gramática. Es sobre existencia.', 'answer': 'quien soy cuando hablo'}
        ]
    },

    # === C2 (dest49-58) — "Las constelaciones" continue + final ===
    49: {
        'room': {
            'description': 'Un archivo con miles de registros de voz. Cada cajón contiene un registro diferente del español.',
            'ambience': 'Cintas grabadas. Voces de todas las regiones.'
        },
        'puzzles': [
            {'puzzleType': 'cipher', 'prompt': 'Mensaje en lenguaje formal cifrado: primera letra de cada palabra. Los Indicadores Bursátiles Están Realmente Transformando Activos Determinantes. = ?', 'clue': 'Lee las iniciales.', 'answer': 'libertad'},
            {'puzzleType': 'extract', 'prompt': '"Del lenguaje de la calle al lenguaje del congreso. Del WhatsApp al editorial." Extrae los cuatro registros mencionados en orden.', 'clue': 'De menos formal a más formal.', 'answer': 'calle whatsapp congreso editorial'},
            {'puzzleType': 'logic', 'prompt': 'Si dominas todos los registros (calle, académico, legal, literario), ¿eres más libre o más limitado por saber tanto?', 'clue': 'Saber más opciones es poder elegir.', 'answer': 'mas libre'},
            {'puzzleType': 'synthesis', 'prompt': 'La libertad lingüística es dominar todos los registros. ¿Qué relación hay entre lenguaje y poder?', 'clue': 'Quien domina el lenguaje domina la situación.', 'answer': 'el lenguaje es poder'}
        ]
    },
    50: {
        'room': {
            'description': 'Una biblioteca sin paredes. Los libros flotan en el espacio. Algunos aún no están escritos.',
            'ambience': 'Páginas en blanco que susurran. Tinta que gotea.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Creo mundos que no existen. Doy vida a personas que nunca nacieron. Mi herramienta es la palabra. Mi material es la imaginación.', 'clue': 'Empieza con E. Es un oficio y un arte.', 'answer': 'escritura'},
            {'puzzleType': 'extract', 'prompt': '"Escribir es crear realidad. La creación literaria es el acto supremo del lenguaje." Extrae la tesis principal.', 'clue': 'La frase más radical.', 'answer': 'escribir es crear realidad'},
            {'puzzleType': 'wordlock', 'prompt': 'Lo que marca el escritor en su territorio. Tiene 5 letras.', 'clue': '_ _ _ _ _', 'answer': 'tinta'},
            {'puzzleType': 'synthesis', 'prompt': 'Si escribir es crear realidad, y cada estudiante escribe su crónica, ¿cuántas realidades existen en este viaje?', 'clue': 'Tantas como estudiantes.', 'answer': 'una por cada persona'}
        ]
    },
    51: {
        'room': {
            'description': 'Un espejo infinito que refleja espejos. Cada reflejo es una capa de significado.',
            'ambience': 'Cristal vibrando. Tu voz repetida infinitamente.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Hablar sobre el hablar. Pensar sobre el pensar. El prefijo que significa "sobre sí mismo". 4 letras.', 'clue': '_ _ _ _', 'answer': 'meta'},
            {'puzzleType': 'riddle', 'prompt': 'La montaña tiene pie. La mesa tiene patas. El reloj tiene cara. ¿Cómo se llama cuando una palabra corporal describe algo no corporal?', 'clue': 'Empieza con M. Es una figura retórica.', 'answer': 'metafora'},
            {'puzzleType': 'logic', 'prompt': 'Si decimos "el pie de la montaña" y todo el mundo entiende, ¿es error o evolución del lenguaje?', 'clue': 'Lo que todos entienden no puede ser error.', 'answer': 'evolucion'},
            {'puzzleType': 'synthesis', 'prompt': 'El metalenguaje es el lenguaje mirándose a sí mismo. ¿Puede un espejo reflejarse a sí mismo completamente?', 'clue': 'Hay límites a la autorreferencia.', 'answer': 'siempre queda algo fuera'}
        ]
    },
    52: {
        'room': {
            'description': 'Una plaza pública con un micrófono en el centro. Las paredes son de cristal para que todos vean.',
            'ambience': 'Multitud expectante. Micrófono encendido.'
        },
        'puzzles': [
            {'puzzleType': 'cipher', 'prompt': 'Discurso cifrado: letra siguiente. Ozkzaqzr = ?', 'clue': 'Cada letra retrocede una posición en el abecedario.', 'answer': 'palabras'},
            {'puzzleType': 'extract', 'prompt': '"Cuando hablamos en público, cada palabra es una piedra que construye o destruye." Extrae la metáfora central.', 'clue': 'La palabra se compara con algo físico.', 'answer': 'la palabra es una piedra'},
            {'puzzleType': 'logic', 'prompt': 'Si las palabras construyen y destruyen, ¿es más responsable hablar o callar?', 'clue': 'El silencio también es una elección con consecuencias.', 'answer': 'hablar con responsabilidad'},
            {'puzzleType': 'synthesis', 'prompt': 'Un discurso cambia un país. Una canción cambia una vida. ¿Qué tienen en común? ¿Qué los hace poderosos?', 'clue': 'No es el contenido solo. Es la conexión.', 'answer': 'conectan con la emocion colectiva'}
        ]
    },
    53: {
        'room': {
            'description': 'Un observatorio en la cumbre más alta. Telescopios apuntan a constelaciones de palabras.',
            'ambience': 'Viento de altura. Estrellas cercanas.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Quien cuida las palabras, la gramática, el estilo, la cultura, la emoción y la intención. 8 letras.', 'clue': '_ _ _ _ _ _ _ _', 'answer': 'guardian'},
            {'puzzleType': 'sequence', 'prompt': 'El guardián domina estos niveles en orden: fonética → gramática → léxico → pragmática → ___', 'clue': 'El nivel más alto: el efecto en el otro.', 'answer': 'retorica'},
            {'puzzleType': 'synthesis', 'prompt': 'La síntesis total une gramática + estilo + registro + cultura + emoción + intención. ¿Qué palabra describe a quien domina todo esto?', 'clue': 'No es "profesor". Es alguien que sabe usar el lenguaje completo.', 'answer': 'hablante competente'},
            {'puzzleType': 'logic', 'prompt': 'Si el guardián domina cada nivel del lenguaje, ¿puede existir alguien que lo domine todo perfectamente?', 'clue': 'El dominio total es asintótico.', 'answer': 'siempre se puede aprender mas'}
        ]
    },
    54: {
        'room': {
            'description': 'Un puente entre dos orillas. En una orilla hay un idioma. En la otra, otro. Tú estás en el medio.',
            'ambience': 'Dos idiomas hablando al mismo tiempo.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'No cambiar palabras sino mundos. El acto de llevar significado entre idiomas. 10 letras.', 'clue': '_ _ _ _ _ _ _ _ _ _', 'answer': 'traduccion'},
            {'puzzleType': 'extract', 'prompt': '"Traducir no es cambiar palabras. Es cambiar mundos." Extrae lo que la traducción NO es y lo que SÍ es.', 'clue': 'El no y el sí.', 'answer': 'no es cambiar palabras es cambiar mundos'},
            {'puzzleType': 'riddle', 'prompt': 'Estoy entre dos mundos. Cargo significado de un lado a otro. Nunca llego exactamente igual. Soy un puente imperfecto pero necesario.', 'clue': 'Empieza con T. Es un acto creativo.', 'answer': 'traduccion'},
            {'puzzleType': 'synthesis', 'prompt': 'Si traducir es cambiar mundos, ¿qué se pierde inevitablemente al traducir? ¿Y qué se gana?', 'clue': 'Se pierde lo intransferible. Se gana el diálogo.', 'answer': 'se pierde el matiz y se gana la conexion'}
        ]
    },
    55: {
        'room': {
            'description': 'Un archivo de documentos vivos. Los textos respiran, crecen y cambian con el tiempo.',
            'ambience': 'Papel vivo. Letras que se reorganizan.'
        },
        'puzzles': [
            {'puzzleType': 'cipher', 'prompt': 'Documento cifrado: vocales reemplazadas por números. 1=a, 2=e, 3=i, 4=o, 5=u. Cr4n3c1 = ?', 'clue': 'Reemplaza los números por vocales.', 'answer': 'cronica'},
            {'puzzleType': 'sequence', 'prompt': 'La crónica avanza: observar → documentar → reflexionar → ___', 'clue': 'El paso final de la escritura profesional.', 'answer': 'publicar'},
            {'puzzleType': 'logic', 'prompt': '"El acto de documentar lo vivido transforma la experiencia en patrimonio." Si no lo documentas, ¿existió?', 'clue': 'Existió pero no trasciende.', 'answer': 'existe pero no trasciende'},
            {'puzzleType': 'wordlock', 'prompt': 'Lo que la experiencia se convierte cuando se documenta. 10 letras.', 'clue': '_ _ _ _ _ _ _ _ _ _', 'answer': 'patrimonio'}
        ]
    },
    56: {
        'room': {
            'description': 'Una cámara con mil voces que suenan al unísono. Cada voz es un hablante de español.',
            'ambience': 'Mil voces en armonía y disonancia.'
        },
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'No soy persona pero vivo. No soy animal pero evoluciono. Nazco con los humanos y muero cuando me olvidan. Soy más vieja que cualquier gobierno.', 'clue': 'Empieza con L. Es lo que estudias.', 'answer': 'lengua'},
            {'puzzleType': 'extract', 'prompt': '"Las variaciones dialectales no son errores sino evidencias de la vitalidad y adaptación continua de una lengua." Extrae la tesis central.', 'clue': 'Lo que las variaciones realmente son.', 'answer': 'evidencias de vitalidad'},
            {'puzzleType': 'logic', 'prompt': 'Si una lengua deja de cambiar, ¿está perfeccionada o muerta?', 'clue': 'Solo las lenguas muertas no cambian.', 'answer': 'muerta'},
            {'puzzleType': 'synthesis', 'prompt': '580 millones hablan español. Cada generación agrega algo. ¿Qué mantiene viva una lengua?', 'clue': 'No son las reglas. Son las personas.', 'answer': 'la necesidad de sus hablantes'}
        ]
    },
    57: {
        'room': {
            'description': 'Una sala de herencia con objetos de cada destino del viaje. 57 objetos en 57 vitrinas.',
            'ambience': 'Memorias de todo el viaje. Eco de cada destino.'
        },
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Lo que Doña Asunción dijo que tienen las palabras. 7 letras.', 'clue': '_ _ _ _ _ _ _', 'answer': 'memoria'},
            {'puzzleType': 'sequence', 'prompt': 'El viaje del guardián: nombrar → describir → narrar → argumentar → analizar → ___', 'clue': 'Lo que hace un guardián con todo lo que sabe.', 'answer': 'transmitir'},
            {'puzzleType': 'synthesis', 'prompt': '"La transmisión cultural es el acto final del aprendizaje: no solo saber, sino pasar lo aprendido." ¿Cuándo termina realmente el aprendizaje?', 'clue': 'Cuando enseñas lo que sabes.', 'answer': 'cuando lo compartes'},
            {'puzzleType': 'riddle', 'prompt': 'Fui semilla. Fui palabra. Fui argumento. Fui análisis. Ahora soy guardián. ¿Quién soy?', 'clue': 'Eres tú.', 'answer': 'el estudiante'}
        ]
    },
    58: {
        'room': {
            'description': 'La última cámara. No tiene paredes. No tiene techo. Es un espacio que tú construyes con tu voz.',
            'ambience': 'Silencio absoluto que espera tu voz.'
        },
        'puzzles': [
            {'puzzleType': 'synthesis', 'prompt': '58 destinos. 3 mundos. Un viaje. Miles de palabras. Una sola las resume todas. El Mapa de las Voces te hace una última pregunta: ¿cuál es la palabra más importante del español?', 'clue': 'La respuesta siempre fue la misma.', 'answer': 'escuchar'},
            {'puzzleType': 'wordlock', 'prompt': 'La Palabra del Corazón. 8 letras. Es lo que hiciste durante todo el viaje.', 'clue': '_ _ _ _ _ _ _ _', 'answer': 'escuchar'}
        ]
    },
}


def main():
    print("=" * 60)
    print("Authoring escape rooms for dest13-58")
    print("=" * 60)

    authored = 0
    for dest_num in range(13, 59):
        path = CONTENT_DIR / ('dest%d.json' % dest_num)
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        if dest_num not in ESCAPE_ROOMS:
            print('  dest%d: SKIPPED (no authored content)' % dest_num)
            continue

        er_content = ESCAPE_ROOMS[dest_num]
        er = data.get('escapeRoom', {})

        # Update room description and ambience (keep existing name)
        room = er.get('room', {})
        room['description'] = er_content['room']['description']
        room['ambience'] = er_content['room']['ambience']
        er['room'] = room

        # Replace puzzles with authored content
        er['puzzles'] = er_content['puzzles']

        # Remove _needsContent flag
        if '_needsContent' in er:
            del er['_needsContent']

        data['escapeRoom'] = er

        with open(path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)

        puzzle_count = len(er_content['puzzles'])
        print('  dest%d: %d puzzles authored (%s)' % (
            dest_num, puzzle_count,
            ', '.join(p['puzzleType'] for p in er_content['puzzles'])
        ))
        authored += 1

    print('\n' + '=' * 60)
    print('COMPLETE: %d escape rooms authored' % authored)
    print('=' * 60)


if __name__ == '__main__':
    main()
