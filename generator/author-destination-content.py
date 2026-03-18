#!/usr/bin/env python3
"""
author-destination-content.py
Phase 5 content authoring: Rewrites all 58 dest{N}.json files with:
1. Spanish arrival narratives (replacing English narrativeBeat)
2. Departure yaguaraLines
3. Cronica games (one per destination, scaffolded by CEFR)
4. Escape room puzzles (A1-A2 complete, B1+ skeleton)
5. Removes _needsContent skeleton games
"""

import json
import os
from pathlib import Path

BASE = Path('/home/babelfree.com/public_html')
CONTENT_DIR = BASE / 'content'

# ============================================================
# ARRIVAL NARRATIVES (Spanish, voiced by Yaguará)
# ============================================================
ARRIVALS = {
    1: {
        'sections': [
            {'body': '<em>Viajero...</em> Abre los ojos. Estás en la selva amazónica. El río suena. Los árboles son muy grandes. El aire es húmedo.'},
            {'body': 'Soy <strong>Yaguará</strong>. Soy un jaguar. Camino entre los mundos. Voy a caminar contigo. Cada palabra es una <em>semilla</em>.'},
            {'body': '¿Cómo te llamas? Yo soy Yaguará. Y tú... ¿quién eres tú?'}
        ],
        'button': 'Entrar en la selva'
    },
    2: {
        'sections': [
            {'body': 'Mira a tu alrededor. El mundo tiene nombres. El <strong>árbol</strong>. El <strong>río</strong>. El <strong>cielo</strong>. El <strong>pájaro</strong>.'},
            {'body': 'Cuando nombras una cosa, esa cosa <em>brilla</em>. Cuando no la nombras, se apaga. Las cosas sin nombre están desapareciendo.'},
            {'body': 'Vamos a nombrar el mundo. Cada nombre es una luz.'}
        ],
        'button': 'Empezar a nombrar'
    },
    3: {
        'sections': [
            {'body': 'Yaguará mira el río. El río <strong>es</strong> grande. El agua <strong>es</strong> fría. El cielo <strong>es</strong> azul. El bosque <strong>es</strong> verde.'},
            {'body': 'Cuando describes algo, los colores son más fuertes. Cuando no dices nada, los colores se apagan.'},
            {'body': '¿Cómo es tu mundo? Descríbelo. Las palabras le dan color.'}
        ],
        'button': 'Describir el mundo'
    },
    4: {
        'sections': [
            {'body': 'Yaguará cuenta las cosas. Un árbol, dos pájaros, tres piedras en el río. <strong>Tengo</strong> tres años en este bosque.'},
            {'body': 'El tiempo pasa aquí, pero los días son más cortos que antes. Algo está comiendo el tiempo. El Río Madre susurra una advertencia.'},
            {'body': '¿Cuántos días llevas caminando? Vamos a contar.'}
        ],
        'button': 'Contar los días'
    },
    5: {
        'sections': [
            {'body': 'A Yaguará <strong>le gusta</strong> el agua fría. <strong>No le gusta</strong> el silencio. El silencio no es normal aquí. El silencio es una señal.'},
            {'body': 'El Río Madre dice: <em>"Cuando algo calla, algo se pierde."</em> Yaguará escucha. Tú también escucha.'},
            {'body': '¿Qué te gusta de este lugar? ¿Qué no te gusta? Tu voz es importante.'}
        ],
        'button': 'Expresar'
    },
    6: {
        'sections': [
            {'body': 'Cada día, Yaguará <strong>camina</strong> por la selva. <strong>Come</strong> fruta. <strong>Bebe</strong> agua del río. <strong>Duerme</strong> en un árbol grande.'},
            {'body': 'Mamá Jaguar aparece entre los árboles. Dice: <em>"Hija, camina con cuidado. El mundo cambia."</em> Y desaparece.'},
            {'body': 'Un día completo. ¿Qué haces tú cada día?'}
        ],
        'button': 'Vivir un día'
    },
    7: {
        'sections': [
            {'body': 'Hoy hay comida. Yaguará encuentra frutas, pescado del río, agua fresca. Comer juntos es importante.'},
            {'body': '<strong>Tengo hambre.</strong> <strong>Tengo sed.</strong> Estas palabras son necesarias. El cuerpo habla. Las palabras responden.'},
            {'body': 'En la selva, compartir comida es compartir vida. ¿Qué compartiste hoy?'}
        ],
        'button': 'Comer juntos'
    },
    8: {
        'sections': [
            {'body': 'El río suena más fuerte hoy. Junto al río hay una familia. La <strong>madre</strong>. El <strong>padre</strong>. La <strong>hermana</strong>. El <strong>hermano</strong>.'},
            {'body': 'Yaguará los mira. Recuerda a Mamá Jaguar. La familia es el primer mundo. Las primeras palabras son nombres de familia.'},
            {'body': '¿Quién camina contigo? ¿Quién es tu familia?'}
        ],
        'button': 'Conocer a la familia'
    },
    9: {
        'sections': [
            {'body': 'Una casa sobre el río. Tiene techo de hojas y paredes de madera. Es pequeña pero <strong>está</strong> bien.'},
            {'body': 'Yaguará entra. La <strong>cocina</strong> está a la izquierda. La <strong>hamaca</strong> está al fondo. Todo tiene un lugar.'},
            {'body': 'Doña Asunción, una mujer mayor, vive aquí. Dice: <em>"Entra. Mi casa es tu casa."</em> Es la primera vez que alguien habla con tanta calma.'}
        ],
        'button': 'Entrar en la casa'
    },
    10: {
        'sections': [
            {'body': '¿Qué hora es? El sol dice que es temprano. Son las <strong>siete de la mañana</strong>. Enero. El primer mes del año.'},
            {'body': 'Yaguará aprende a contar más alto. Treinta y dos, cincuenta, cien. Los números son como escalones.'},
            {'body': 'El tiempo tiene forma aquí. ¿Qué hora es en tu mundo?'}
        ],
        'button': 'Mirar el reloj'
    },
    11: {
        'sections': [
            {'body': 'Las palabras se mueven. <strong>Comer, beber, vivir, escribir, abrir.</strong> Los verbos son los motores del idioma.'},
            {'body': 'Yaguará <strong>va</strong> al río. <strong>Come</strong> una fruta. <strong>Vive</strong> en la selva. Cada verbo es una acción. Cada acción cambia el mundo.'},
            {'body': '¿Adónde vas? ¿Por qué? Las razones importan. <em>Porque quiero aprender.</em>'}
        ],
        'button': 'Conjugar el mundo'
    },
    12: {
        'sections': [
            {'body': 'Yaguará se detiene. Mira el reflejo en el río. ¿Quién es ella? Es colombiana — del corazón de la selva. Es fuerte. Es joven. Es curiosa.'},
            {'body': 'La primera espiral se cierra. Has nombrado el mundo. Has contado los días. Has dicho quién eres.'},
            {'body': 'Pero algo ha cambiado. Los colores del bosque no son tan fuertes como antes. Algo se está apagando. Y una voz dice: <em>"Es hora de avanzar."</em>'}
        ],
        'button': 'Descubrir quién soy'
    },
    13: {
        'sections': [
            {'body': 'El río canta. Yaguará escucha una melodía que no conoce. Es una canción sobre lo que pasó ayer. Sobre lo que <strong>fue</strong>.'},
            {'body': 'El pretérito llega como el agua: algo que ya pasó pero dejó huella. <em>Ayer caminé. Ayer comí. Ayer vi algo nuevo.</em>'},
            {'body': 'Los ríos cuentan historias del pasado. ¿Qué cuenta tu río?'}
        ],
        'button': 'Escuchar al río'
    },
    14: {
        'sections': [
            {'body': 'Candelaria aparece por primera vez. Es una niña de doce años. Tiene los ojos brillantes y una sonrisa que dice <em>"yo sé cosas"</em>.'},
            {'body': '— Yaguará — dice ella — yo vi lo que pasó ayer. ¿Quieres que te cuente?'},
            {'body': 'Contar lo que pasó. El pasado tiene poder. Candelaria lo sabe. Ella es el puente entre los mundos.'}
        ],
        'button': 'Escuchar a Candelaria'
    },
    15: {
        'sections': [
            {'body': 'Yaguará tiene hambre. Yaguará <strong>necesita</strong> agua. Yaguará <strong>quiere</strong> descansar.'},
            {'body': 'Necesitar y querer son diferentes. Lo que necesitas te mantiene viva. Lo que quieres te da dirección.'},
            {'body': 'El camino se bifurca. ¿Qué necesitas para seguir? ¿Qué quieres encontrar?'}
        ],
        'button': 'Elegir el camino'
    },
    16: {
        'sections': [
            {'body': 'El cielo cambia. Ayer hacía sol. Hoy llueve. Mañana... ¿quién sabe?'},
            {'body': 'El clima del bosque es el humor del mundo. Cuando el mundo está triste, llueve. Cuando está contento, sale el sol. Pero últimamente llueve demasiado.'},
            {'body': 'Algo está cambiando. Los animales se mueven. Los pájaros vuelan hacia el sur. ¿Qué cambiará mañana?'}
        ],
        'button': 'Mirar el cielo'
    },
    17: {
        'sections': [
            {'body': 'Yaguará recuerda. El bosque <strong>era</strong> más verde. El río <strong>era</strong> más ancho. Los pájaros <strong>cantaban</strong> más fuerte.'},
            {'body': 'El imperfecto es la memoria. No lo que pasó en un momento, sino cómo <em>eran</em> las cosas. Lo que duraba. Lo que se repetía.'},
            {'body': '¿Cómo era antes? ¿Qué recuerdas de cuando empezó el viaje?'}
        ],
        'button': 'Recordar'
    },
    18: {
        'sections': [
            {'body': 'El Mundo de Abajo se cierra. Has nombrado. Has contado. Has descrito. Has recordado. El mundo tiene tu voz dentro.'},
            {'body': 'Pero el camino sigue. Más allá de la selva hay pueblos, montañas, costas. El Mundo del Medio espera.'},
            {'body': 'Yaguará se detiene en el borde del bosque. Mira hacia adelante. Lo desconocido es grande. Pero tú ya no eres quien era al principio.'}
        ],
        'button': 'Cruzar el umbral'
    },
    19: {
        'sections': [
            {'body': 'Yaguará entra en el primer pueblo humano. Una aldea en el Chocó. Todo es más fuerte, más rápido, más complejo que el bosque.'},
            {'body': 'Candelaria camina a su lado. Traduce. Explica. Ella es su puente.'},
            {'body': 'Al borde del pueblo, un hombre habla con los líderes. Se llama Don Próspero. Tiene un portafolio de cuero. Habla de un camino que <em>"va a traer prosperidad"</em>. Algo no se siente bien.'}
        ],
        'button': 'Entrar en el pueblo'
    },
    20: {
        'sections': [
            {'body': 'La gente del pueblo cuenta historias. Historias de antes. Historias de ahora. Yaguará escucha todo.'},
            {'body': '— <em>Dicen que</em> el río está enfermo — cuenta una señora. — <em>Dicen que</em> un hombre quiere construir un camino por la selva.'},
            {'body': 'El subjuntivo aparece como una duda: <em>"Es posible que llueva. Ojalá no destruyan el bosque."</em> Las historias tienen más de una verdad.'}
        ],
        'button': 'Escuchar las historias'
    },
    21: {
        'sections': [
            {'body': 'Subir la montaña. El camino es largo. Primero, el valle. Después, la niebla. Luego, la cumbre. <em>Finalmente</em>, la vista.'},
            {'body': 'Los conectores son las escaleras del idioma: <em>primero, después, luego, sin embargo, por lo tanto, finalmente.</em>'},
            {'body': 'Desde arriba, Yaguará ve todo. El bosque, el pueblo, el río. Y más allá: el llano infinito.'}
        ],
        'button': 'Subir la montaña'
    },
    22: {
        'sections': [
            {'body': 'El llano se extiende hasta donde alcanza la vista. No hay paredes. No hay techo. Solo el cielo y la tierra.'},
            {'body': 'Aquí, la gente habla de dos tiempos: lo que <strong>pasó</strong> (de golpe) y lo que <strong>pasaba</strong> (siempre). <em>"Llovía cuando llegamos. Comíamos cuando empezó la tormenta."</em>'},
            {'body': '¿Qué hay al otro lado del llano? Solo una manera de saberlo: caminar.'}
        ],
        'button': 'Cruzar el llano'
    },
    23: {
        'sections': [
            {'body': 'El mar. Por primera vez, Yaguará ve el mar. Es inmenso. Es paciente. Nunca se apura.'},
            {'body': '— <em>Ella dijo que</em> el mar sabe esperar — cuenta Candelaria. El estilo indirecto llega como las olas: lo que alguien dijo, transformado por quien lo repite.'},
            {'body': 'La costa enseña paciencia. Las olas vienen y van. Las palabras también.'}
        ],
        'button': 'Llegar a la costa'
    },
    24: {
        'sections': [
            {'body': 'Dos historias se cruzan. La historia de Yaguará y la historia de Candelaria. No son iguales, pero caminan juntas.'},
            {'body': 'El subjuntivo se vuelve emocional: <em>"Me alegra que estés aquí. Temo que Don Próspero destruya el bosque."</em>'},
            {'body': 'Donde se cruzan las historias, nace algo nuevo.'}
        ],
        'button': 'Cruzar historias'
    },
    25: {
        'sections': [
            {'body': 'Don Próspero aparece otra vez. Esta vez no habla de caminos. Habla de <em>progreso</em>. De <em>futuro</em>. De lo que <strong>haría</strong> si tuviera más tierra.'},
            {'body': 'El condicional es el tiempo de los sueños y las mentiras: <em>"Si yo pudiera, haría grandes cosas."</em> Pero ¿qué haría realmente?'},
            {'body': 'Candelaria susurra: <em>"No le creas todo."</em>'}
        ],
        'button': 'Escuchar a Don Próspero'
    },
    26: {
        'sections': [
            {'body': 'Algo se ha perdido. Un lugar que existía ya no existe. Un río que cantaba ahora está callado.'},
            {'body': 'La voz pasiva cuenta lo que pasó sin decir quién: <em>"El bosque fue cortado. El río fue contaminado. Las voces fueron silenciadas."</em>'},
            {'body': 'Lo que se pierde no siempre vuelve. Pero nombrar lo perdido es una forma de memoria.'}
        ],
        'button': 'Recordar lo perdido'
    },
    27: {
        'sections': [
            {'body': 'Dos verdades. Don Próspero dice: <em>"El camino trae trabajo."</em> Candelaria dice: <em>"El camino destruye el bosque."</em>'},
            {'body': 'Argumentar es un arte. <em>Por un lado... por otro lado... en conclusión...</em> La verdad no siempre es una sola.'},
            {'body': '¿Cuál es tu verdad? ¿Puedes defender dos lados?'}
        ],
        'button': 'Escuchar las dos verdades'
    },
    28: {
        'sections': [
            {'body': 'Doña Asunción habla por última vez. Está vieja. Está cansada. Pero su voz es clara.'},
            {'body': '— <em>Escucha, hija</em> — le dice a Yaguará. — <em>Las palabras tienen memoria. Cada palabra que dices vive para siempre en alguien.</em>'},
            {'body': 'Doña Asunción cierra los ojos. El Mundo del Medio empieza a cambiar. La voz de la abuela se convierte en eco.'}
        ],
        'button': 'Escuchar a la abuela'
    },
    29: {
        'sections': [
            {'body': 'El silencio avanza. Donde antes había voces, ahora hay vacío. Los árboles no suenan. Los ríos no cantan.'},
            {'body': 'El subjuntivo pasado aparece como un lamento: <em>"Si hubiera escuchado antes... Si no hubiera permitido que esto pasara..."</em>'},
            {'body': 'El silencio tiene forma. El silencio tiene peso. ¿Qué calla este silencio?'}
        ],
        'button': 'Entrar en el silencio'
    },
    30: {
        'sections': [
            {'body': 'Yaguará tiene una sombra. Siempre la tuvo, pero ahora la sombra habla. Dice las cosas que Yaguará no quiere decir.'},
            {'body': 'Las perífrasis verbales son sombras del verbo: <em>"Iba a decir... estaba por hacer... acaba de comprender..."</em> Lo que casi fue, lo que está siendo, lo que acaba de pasar.'},
            {'body': '¿Qué esconde la sombra? Solo mirándola puedes saberlo.'}
        ],
        'button': 'Mirar la sombra'
    },
    31: {
        'sections': [
            {'body': 'Hay que hablar con los demás. No solo con los amigos. Con los desconocidos. Con los que piensan diferente.'},
            {'body': '<em>"Si yo fuera usted, consideraría otra opción."</em> Los condicionales mixtos son diplomacia: decir lo difícil con respeto.'},
            {'body': 'Hablar cuando importa es un acto de valentía. ¿Cómo hablamos cuando importa?'}
        ],
        'button': 'Hablar con los demás'
    },
    32: {
        'sections': [
            {'body': '¿Quién traicionó a quién? Don Próspero dice que ayuda. La tierra dice que sufre. El registro cambia según quién habla.'},
            {'body': 'El registro formal: <em>"Se solicita su colaboración."</em> El registro informal: <em>"Ayúdame, porfa."</em> Cada registro revela una intención.'},
            {'body': '¿Quién dice la verdad? El registro lo delata.'}
        ],
        'button': 'Cambiar el registro'
    },
    33: {
        'sections': [
            {'body': 'El lugar donde van los nombres olvidados. Aquí viven las palabras que nadie dice. Los idiomas muertos. Las voces perdidas.'},
            {'body': 'Argumentar con complejidad: <em>"A pesar de que la evidencia sugiere X, es necesario considerar Y, puesto que Z."</em>'},
            {'body': '¿Dónde van las palabras olvidadas? ¿Quién las recuerda?'}
        ],
        'button': 'Buscar los nombres'
    },
    34: {
        'sections': [
            {'body': 'El río de la memoria fluye hacia atrás. En él flotan las palabras de todos los que pasaron por aquí.'},
            {'body': 'Las cláusulas relativas complejas tejen la memoria: <em>"El río, cuyas aguas recuerdan cada voz que las tocó, fluye hacia un mar que nadie ha visto."</em>'},
            {'body': '¿Qué recuerda el río? ¿Qué has dejado tú en sus aguas?'}
        ],
        'button': 'Navegar el río'
    },
    35: {
        'sections': [
            {'body': 'La llanura tiene escritura. Las líneas en la tierra, los surcos del arado, las huellas de los animales. Todo es texto.'},
            {'body': 'El análisis literario revela capas: <em>"El autor emplea la metáfora del río como símbolo de la memoria colectiva, lo cual sugiere..."</em>'},
            {'body': '¿Qué escribió la tierra? Aprende a leer lo que el mundo escribió.'}
        ],
        'button': 'Leer la tierra'
    },
    36: {
        'sections': [
            {'body': 'La tierra tiene un precio. Don Próspero habla de economía. Candelaria habla de vida. ¿Se puede medir el valor de un bosque en dinero?'},
            {'body': 'El discurso formal requiere precisión: <em>"Los indicadores macroeconómicos señalan una tendencia preocupante en la relación entre desarrollo y conservación."</em>'},
            {'body': '¿Qué vale la tierra? ¿Quién decide?'}
        ],
        'button': 'Medir el valor'
    },
    37: {
        'sections': [
            {'body': 'Las especies desaparecen. Primero las más pequeñas. Las ranas. Las mariposas. Después las más grandes. Todo está conectado.'},
            {'body': 'La síntesis argumentativa une ideas opuestas: <em>"Aunque el desarrollo genera empleo, resulta imperativo que este no comprometa la biodiversidad, dado que..."</em>'},
            {'body': '¿Qué desaparece primero? ¿Qué perdemos cuando algo desaparece?'}
        ],
        'button': 'Contar lo que falta'
    },
    38: {
        'sections': [
            {'body': 'El Mundo del Medio se cierra. Has contado historias. Has argumentado. Has perdido y encontrado. El legado es lo que queda.'},
            {'body': 'La integración discursiva teje todo en uno: narración, argumentación, descripción, reflexión. Todo al servicio de una voz propia.'},
            {'body': 'Doña Asunción ya no está. Su voz vive en las palabras que dejó. ¿Qué dejarás tú?'}
        ],
        'button': 'Dejar un legado'
    },
    39: {
        'sections': [
            {'body': 'El Mundo de Arriba se abre. Las estrellas son más cercanas. El aire es diferente. Aquí las palabras tienen peso.'},
            {'body': 'La montaña habla, pero solo si sabes escuchar. Las subordinadas complejas son su idioma: <em>"Lo que la montaña susurra cuando nadie escucha es precisamente aquello que, al ser ignorado, transforma el silencio en olvido."</em>'},
            {'body': '¿Qué dice la montaña? Solo si subes lo suficiente podrás oírla.'}
        ],
        'button': 'Ascender al mundo de arriba'
    },
    40: {
        'sections': [
            {'body': 'Los mayores hablan. Los Antiguos — figuras que existieron antes del tiempo — comparten su sabiduría.'},
            {'body': 'El discurso académico es su lenguaje: <em>"La evidencia empírica sustenta la hipótesis de que la transmisión oral constituye el mecanismo primario de preservación cultural."</em>'},
            {'body': '¿Qué saben los mayores que nosotros hemos olvidado?'}
        ],
        'button': 'Escuchar a los mayores'
    },
    41: {
        'sections': [
            {'body': 'Cada tierra tiene su eco. El Chocó suena diferente que los Llanos. Cartagena no habla como Bogotá. Pero todos son español.'},
            {'body': 'El registro y el dialecto revelan identidad: <em>"Mira, ve" en Cali, "¿Qué más, pues?" en Medellín, "¡Ey, vale!" en el Caribe.</em>'},
            {'body': '¿Qué une todas estas voces? El español es un río con muchos afluentes.'}
        ],
        'button': 'Escuchar todos los ecos'
    },
    42: {
        'sections': [
            {'body': 'Las palabras se tejen como hilos. Los idioms, las perífrasis, las expresiones hechas — son el tejido del idioma vivo.'},
            {'body': '<em>"Echar de menos", "dar a luz", "tener en cuenta", "ir al grano"</em> — cada expresión es una historia comprimida en tres palabras.'},
            {'body': '¿Cómo se tejen las palabras? Cada hilo cuenta.'}
        ],
        'button': 'Tejer palabras'
    },
    43: {
        'sections': [
            {'body': 'Los hilos de luz conectan todo. Un párrafo con otro. Una idea con la siguiente. La cohesión textual es invisible pero sostiene todo.'},
            {'body': '<em>"En consecuencia", "a pesar de lo anterior", "de manera análoga", "en síntesis"</em> — los conectores avanzados son los hilos de luz del discurso.'},
            {'body': '¿Dónde termina un hilo? Donde empieza otro.'}
        ],
        'button': 'Seguir los hilos'
    },
    44: {
        'sections': [
            {'body': 'El templo del silencio. Un lugar donde las palabras se guardan. Donde el discurso se analiza, se disecciona, se comprende.'},
            {'body': 'El análisis del discurso revela lo que las palabras ocultan: <em>"La elección léxica del hablante sugiere una posición ideológica que, si bien no se explicita, permea cada enunciado."</em>'},
            {'body': '¿Qué guarda el templo? Los secretos del lenguaje.'}
        ],
        'button': 'Entrar en el templo'
    },
    45: {
        'sections': [
            {'body': 'La piedra que persuade. La retórica es el arte de mover con palabras. Desde los discursos de Bolívar hasta un grafiti en Bogotá.'},
            {'body': 'Ethos, pathos, logos — las tres piedras de la persuasión. <em>"Como ciudadanos responsables (ethos), no podemos ignorar el sufrimiento (pathos) que los datos claramente demuestran (logos)."</em>'},
            {'body': '¿Cómo mueve la palabra? ¿Cómo te mueve a ti?'}
        ],
        'button': 'Encontrar la piedra'
    },
    46: {
        'sections': [
            {'body': 'Las voces entre las ruinas. Textos que dialogan con otros textos. García Márquez con Rulfo. Shakira con Celia Cruz. Todo es conversación.'},
            {'body': 'La intertextualidad revela que ningún texto existe solo: <em>"Al evocar la soledad macondiana de García Márquez, el autor contemporáneo resignifica el concepto de aislamiento en el contexto digital."</em>'},
            {'body': '¿Qué dicen las ruinas? Lo que dijeron quienes vinieron antes.'}
        ],
        'button': 'Escuchar las ruinas'
    },
    47: {
        'sections': [
            {'body': '¿Quién nombra las cosas? ¿El que las ve primero? ¿El que las necesita? ¿El que tiene poder? Nombrar es un acto político.'},
            {'body': 'La filosofía del lenguaje pregunta lo fundamental: <em>"¿Precede el pensamiento al lenguaje o es el lenguaje el que moldea el pensamiento? Si los Wayúu tienen veinte palabras para lluvia, ¿ven veinte lluvias que nosotros no vemos?"</em>'},
            {'body': '¿Quién nombra? ¿Quién decide qué existe?'}
        ],
        'button': 'Nombrar lo innombrado'
    },
    48: {
        'sections': [
            {'body': 'La estrella que guía. La producción académica es crear conocimiento nuevo. No repetir — crear.'},
            {'body': 'Escribir un ensayo, defender una tesis, publicar una idea. <em>"El presente estudio contribuye al campo de la sociolingüística al proponer un marco teórico que..."</em>'},
            {'body': '¿Hacia dónde apunta tu estrella? ¿Qué conocimiento quieres crear?'}
        ],
        'button': 'Seguir la estrella'
    },
    49: {
        'sections': [
            {'body': 'La ciudad de todas las voces. Bogotá. Ciudad de ocho millones de historias. Aquí todos los registros conviven.'},
            {'body': 'Del lenguaje de la calle al lenguaje del congreso. Del WhatsApp al editorial. El dominio del registro es la libertad lingüística total.'},
            {'body': '¿Cómo suena una ciudad entera? Como todas las voces al mismo tiempo.'}
        ],
        'button': 'Entrar en la ciudad'
    },
    50: {
        'sections': [
            {'body': 'Tinta y territorio. Es hora de escribir. No para un examen. Para el mundo. Tu tinta marca el territorio.'},
            {'body': 'La creación literaria es el acto supremo del lenguaje: inventar mundos con palabras. <em>Escribir es crear realidad.</em>'},
            {'body': '¿Qué escribes en la tierra? ¿Qué historia es tuya?'}
        ],
        'button': 'Tomar la tinta'
    },
    51: {
        'sections': [
            {'body': 'El espejo del idioma. El metalenguaje — hablar sobre el hablar. Pensar sobre el pensar.'},
            {'body': '¿Qué es una metáfora? ¿Qué es una metonimia? ¿Por qué decimos <em>"el pie de la montaña"</em>? El idioma se mira a sí mismo.'},
            {'body': '¿Qué muestra el espejo? Lo que el idioma es cuando se ve a sí mismo.'}
        ],
        'button': 'Mirar el espejo'
    },
    52: {
        'sections': [
            {'body': 'Voces que transforman. Un discurso puede cambiar un país. Una canción puede cambiar una vida. Las palabras tienen poder real.'},
            {'body': 'El discurso público es responsabilidad: <em>"Cuando hablamos en público, cada palabra es una piedra que construye o destruye."</em>'},
            {'body': '¿Qué transforma una voz? ¿Qué transformará la tuya?'}
        ],
        'button': 'Levantar la voz'
    },
    53: {
        'sections': [
            {'body': 'El guardián despierta. Dentro de ti hay un guardián del idioma. Alguien que cuida las palabras. Alguien que sabe cuándo hablar y cuándo callar.'},
            {'body': 'La síntesis total une todo: gramática, estilo, registro, cultura, emoción, intención. El guardián domina cada nivel.'},
            {'body': '¿Qué guarda el guardián? Todo lo que has aprendido. Todo lo que eres.'}
        ],
        'button': 'Despertar al guardián'
    },
    54: {
        'sections': [
            {'body': 'El regreso. Yaguará vuelve a la selva. Pero la selva ya no es la misma. O quizás es Yaguará la que cambió.'},
            {'body': 'La traducción y la mediación: llevar significado de un mundo a otro. <em>"Traducir no es cambiar palabras. Es cambiar mundos."</em>'},
            {'body': '¿Qué cambió desde que partiste? ¿Eres la misma persona que empezó este viaje?'}
        ],
        'button': 'Regresar a la selva'
    },
    55: {
        'sections': [
            {'body': 'La crónica de los mundos. Tres mundos. Cincuenta y ocho destinos. Miles de palabras. Todo es una crónica.'},
            {'body': 'La escritura profesional da forma permanente a la experiencia: <em>"El acto de documentar lo vivido transforma la experiencia en patrimonio."</em>'},
            {'body': '¿Qué escribirías si tuvieras que contar todo el viaje en una página?'}
        ],
        'button': 'Escribir la crónica'
    },
    56: {
        'sections': [
            {'body': 'La lengua viva. El español no es un museo. Es un río. Cambia cada día. Cada generación le agrega algo.'},
            {'body': 'La sociolingüística revela cómo el idioma refleja la sociedad: <em>"Las variaciones dialectales no son errores sino evidencias de la vitalidad y adaptación continua de una lengua."</em>'},
            {'body': '¿Por qué vive una lengua? Porque la gente la habla. Porque la gente la necesita.'}
        ],
        'button': 'Sentir la lengua viva'
    },
    57: {
        'sections': [
            {'body': 'Los guardianes de historias. Tú eres uno de ellos ahora. Quien aprende un idioma guarda las historias que ese idioma cuenta.'},
            {'body': 'La transmisión cultural es el acto final del aprendizaje: no solo saber, sino <em>pasar lo aprendido</em>.'},
            {'body': 'Doña Asunción dijo: <em>"Las palabras tienen memoria."</em> ¿Quién guarda las historias? Tú.'}
        ],
        'button': 'Aceptar la responsabilidad'
    },
    58: {
        'sections': [
            {'body': 'El espíritu completo. Yaguará se detiene por última vez. El viaje termina donde empezó: en la selva. Pero ahora la selva habla contigo.'},
            {'body': 'Cincuenta y ocho destinos. Tres mundos. Un viaje. Y una palabra que lo resume todo.'},
            {'body': 'El Mapa de las Voces está completo. La última puerta se abre. Dentro hay una sola pregunta y una sola respuesta. La respuesta siempre fue la misma: <strong>escuchar</strong>.'}
        ],
        'button': 'Completar el viaje'
    },
}

# ============================================================
# DEPARTURE YAGUARA LINES
# ============================================================
DEPARTURES = {
    1:  'Cada nombre es una semilla. Ya plantaste la primera.',
    2:  'El mundo tiene más nombres de los que imaginas. Sigue buscando.',
    3:  'Los colores vuelven cuando alguien los describe. No dejes de mirar.',
    4:  'El tiempo pasa. Pero las palabras se quedan.',
    5:  'Lo que te gusta dice quién eres. Recuérdalo.',
    6:  'Un día completo. Mañana será diferente.',
    7:  'Compartir es la primera forma de hablar.',
    8:  'La familia es el primer idioma.',
    9:  'Una casa es un lugar donde las palabras descansan.',
    10: 'Los números cuentan historias. Escúchalas.',
    11: 'Los verbos mueven el mundo. Tú también puedes moverlo.',
    12: 'Ya sabes quién eres. Ahora descubre quién puedes ser.',
    13: 'El río lleva las historias de todos. La tuya también.',
    14: 'Lo que pasó ayer construye lo que pasa hoy.',
    15: 'Necesitar y querer son brújulas diferentes.',
    16: 'El cielo cambia. Tú también.',
    17: 'La memoria es un regalo. No la pierdas.',
    18: 'El primer mundo se cierra. El segundo se abre. Camina.',
    19: 'El pueblo tiene muchas voces. Aprende a escucharlas todas.',
    20: 'Las historias nunca cuentan toda la verdad. Pero se acercan.',
    21: 'Desde arriba, todo se ve diferente.',
    22: 'La paciencia del llano enseña más que la prisa de la ciudad.',
    23: 'El mar espera. Las olas vienen y van. Las palabras también.',
    24: 'Cuando las historias se cruzan, nace algo nuevo.',
    25: 'No todo lo que brilla es progreso.',
    26: 'Lo perdido vive en las palabras que lo nombran.',
    27: 'La verdad tiene más de un lado.',
    28: 'La voz de los mayores vive en los que escuchan.',
    29: 'El silencio tiene forma. Aprende a leerlo.',
    30: 'Tu sombra es tu maestra más honesta.',
    31: 'Hablar bien es un acto de respeto.',
    32: 'Cada registro revela una intención.',
    33: 'Las palabras olvidadas esperan a quien las busque.',
    34: 'El río recuerda todo. ¿Qué le dejaste?',
    35: 'La tierra escribe. Aprende a leer sus líneas.',
    36: 'El valor de lo vivo no cabe en un número.',
    37: 'Lo que desaparece nos cambia a todos.',
    38: 'El legado no es lo que tienes. Es lo que dejas.',
    39: 'La montaña habla para quien sabe escuchar.',
    40: 'Los mayores no dan respuestas. Dan preguntas mejores.',
    41: 'Cada voz es una ventana a un mundo.',
    42: 'Las expresiones son historias comprimidas. Desempácalas.',
    43: 'Los hilos invisibles son los más fuertes.',
    44: 'El silencio del templo guarda verdades.',
    45: 'La palabra que persuade también puede sanar.',
    46: 'Las ruinas hablan para quien sabe mirar.',
    47: 'Nombrar es el primer acto de existencia.',
    48: 'La estrella no se apaga. Solo cambia de lugar.',
    49: 'La ciudad suena diferente a cada hora. Escucha.',
    50: 'Tu tinta marca el territorio. Escribe sin miedo.',
    51: 'El espejo del idioma refleja tu alma.',
    52: 'Tu voz puede transformar algo. ¿Qué será?',
    53: 'El guardián despierta. Ya eres tú.',
    54: 'Regresar no es retroceder. Es ver con ojos nuevos.',
    55: 'Escribir es crear un mundo que no existía.',
    56: 'Una lengua vive porque alguien la necesita.',
    57: 'Guardar historias es guardar vidas.',
    58: 'La respuesta siempre fue la misma: escuchar.',
}

# ============================================================
# CRONICA GAMES (one per destination)
# ============================================================
def make_cronica(dest_num, cefr, title, question):
    """Generate a cronica game appropriate for the CEFR level."""

    if cefr == 'A1':
        scaffold = 'word'
        min_words = 1
        max_words = 10
        prompts = {
            1: 'Yaguará pregunta: ¿Cómo te llamas? Escribe tu nombre en español.',
            2: 'Mira a tu alrededor. Escribe el nombre de algo que ves.',
            3: 'Describe una cosa. Escribe: "El río es ___."',
            4: 'Escribe un número. ¿Cuántos árboles hay?',
            5: 'Escribe algo que te gusta: "Me gusta ___."',
            6: 'Escribe qué haces hoy: "Yo ___."',
            7: 'Escribe qué comiste: "Yo comí ___."',
            8: 'Escribe el nombre de alguien de tu familia.',
            9: 'Describe tu casa con una palabra: "Mi casa es ___."',
            10: '¿Qué hora es? Escribe la hora.',
            11: 'Escribe un verbo. ¿Qué puedes hacer?',
            12: 'Escribe quién eres: "Yo soy ___."',
        }
        placeholder = 'Escribe una palabra...'
    elif cefr == 'A2':
        scaffold = 'sentence'
        min_words = 5
        max_words = 30
        prompts = {
            13: 'El río cuenta una historia. Escribe una frase sobre lo que pasó ayer.',
            14: 'Candelaria te pregunta: ¿Qué pasó? Cuenta algo que pasó ayer.',
            15: 'Escribe qué necesitas y qué quieres para continuar el viaje.',
            16: 'El cielo cambió. Describe cómo está el cielo hoy.',
            17: 'Recuerda cómo era este lugar antes. Escribe una frase con "era" o "había".',
            18: 'El primer mundo se cierra. Escribe qué aprendiste en el Mundo de Abajo.',
        }
        placeholder = 'Escribe una o dos frases...'
    elif cefr == 'B1':
        scaffold = 'paragraph'
        min_words = 15
        max_words = 80
        prompts = {
            19: 'Yaguará entra en el pueblo por primera vez. Describe lo que ve. Usa al menos tres frases.',
            20: 'Alguien te contó una historia. Escríbela con tus propias palabras.',
            21: 'Subiste la montaña. Describe lo que ves desde la cumbre.',
            22: 'El llano es infinito. Describe lo que sientes al cruzarlo.',
            23: 'Llegaste a la costa. ¿Qué aprendiste del mar? Escribe un párrafo.',
            24: 'Dos historias se cruzan. Escribe cómo se conectan.',
            25: 'Don Próspero habla de progreso. ¿Qué piensas tú? Escribe tu opinión.',
            26: 'Algo se perdió. Escribe qué se perdió y por qué importa.',
            27: 'Hay dos verdades. Escribe las dos y di cuál prefieres.',
            28: 'Doña Asunción habló. Escribe lo que dijo y lo que significó para ti.',
        }
        placeholder = 'Escribe un párrafo corto...'
    elif cefr == 'B2':
        scaffold = 'paragraph'
        min_words = 30
        max_words = 150
        prompts = {
            29: 'El silencio avanza. Escribe un párrafo sobre lo que el silencio te dice.',
            30: 'Tu sombra habla. Escribe lo que dice tu sombra — las cosas que no dices en voz alta.',
            31: 'Escribe un mensaje formal a alguien que piensa diferente. Argumenta con respeto.',
            32: 'Escribe dos versiones del mismo mensaje: una formal y una informal.',
            33: 'Escribe un texto argumentativo: ¿se pueden recuperar las palabras olvidadas?',
            34: 'El río de la memoria. Escribe un párrafo usando al menos dos cláusulas relativas.',
            35: 'Analiza un texto breve: ¿qué dice literalmente? ¿Qué dice entre líneas?',
            36: '¿Se puede medir el valor de la naturaleza? Escribe un argumento.',
            37: 'Escribe sobre la biodiversidad en riesgo. ¿Qué desaparece? ¿Qué perdemos?',
            38: 'Escribe tu legado: ¿qué dejas atrás al final del Mundo del Medio?',
        }
        placeholder = 'Escribe un párrafo desarrollado...'
    elif cefr == 'C1':
        scaffold = 'free'
        min_words = 50
        max_words = 300
        prompts = {
            39: 'La montaña habla. Escribe lo que dice usando subordinadas complejas.',
            40: 'Escribe un párrafo académico sobre la transmisión oral del conocimiento.',
            41: 'Compara dos dialectos del español. ¿Qué revelan sobre sus hablantes?',
            42: 'Elige tres expresiones idiomáticas y explica la historia detrás de cada una.',
            43: 'Escribe un texto cohesivo de al menos tres párrafos sobre un tema libre.',
            44: 'Analiza un discurso: ¿qué dice? ¿Qué oculta? ¿Qué revela la elección de palabras?',
            45: 'Escribe un discurso persuasivo sobre un tema que te importa.',
            46: 'Conecta dos textos diferentes. ¿Cómo dialogan entre sí?',
            47: '¿Precede el pensamiento al lenguaje? Escribe tu reflexión filosófica.',
            48: 'Escribe el abstract de una investigación que te gustaría hacer.',
        }
        placeholder = 'Escribe libremente...'
    else:  # C2
        scaffold = 'free'
        min_words = 80
        max_words = 500
        prompts = {
            49: 'Escribe una crónica de la ciudad de todas las voces.',
            50: 'Escribe un fragmento literario original. La historia es tuya.',
            51: 'Reflexiona sobre el lenguaje mismo: ¿qué es una palabra?',
            52: 'Escribe un discurso que transforme algo. ¿Qué quieres cambiar?',
            53: 'El guardián despierta. Escribe su monólogo interior.',
            54: 'Traduce una experiencia de tu vida a este mundo. No traduzcas palabras — traduce significado.',
            55: 'Escribe la crónica de los tres mundos en una página.',
            56: '¿Por qué vive una lengua? Escribe un ensayo breve.',
            57: 'Eres guardián de historias. Escribe la historia que quieres guardar.',
            58: 'La última palabra del mapa. Escribe lo que significa para ti "escuchar".',
        }
        placeholder = 'Escribe sin límites...'

    prompt = prompts.get(dest_num, 'Escribe lo que ves, sientes o piensas.')

    return {
        'type': 'cronica',
        'label': 'Crónica',
        'prompt': prompt,
        'placeholder': placeholder,
        'minWords': min_words,
        'maxWords': max_words,
        'scaffoldType': scaffold,
        'storyKey': 'cronica_dest%d' % dest_num,
        'destination': 'dest%d' % dest_num,
        'button': 'Guardar en la crónica'
    }


# ============================================================
# ESCAPE ROOM PUZZLES (A1-A2 complete)
# ============================================================
ESCAPE_ROOMS = {
    1: {
        'room': {'name': 'La primera puerta', 'description': 'Una puerta de madera vieja. Tiene una cerradura con letras.', 'ambience': 'El sonido del río. Gotas de agua.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Yaguará dice: "Mi nombre tiene 7 letras. Empieza con Y."', 'clue': '_ _ _ _ _ _ _', 'answer': 'yaguara'}
        ],
        'fragment': 'raiz-abrazo'
    },
    2: {
        'room': {'name': 'La cámara de los nombres', 'description': 'Una sala redonda. Las paredes tienen dibujos de animales.', 'ambience': 'Cantos de pájaros lejanos.'},
        'puzzles': [
            {'puzzleType': 'cipher', 'prompt': 'Cada animal tiene un nombre. Descifra: B=A, J=I, P=O → BHP', 'clue': 'Cambia cada letra por la anterior.', 'answer': 'rio'}
        ],
        'fragment': 'raiz-huella'
    },
    3: {
        'room': {'name': 'La cámara de los colores', 'description': 'Las paredes cambian de color. Hay una pregunta en el centro.', 'ambience': 'Luz que cambia.'},
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Soy el color del cielo cuando no llueve. Soy el color del agua del río. ¿Qué color soy?', 'clue': 'Piensa en el cielo limpio.', 'answer': 'azul'}
        ],
        'fragment': 'raiz-ola'
    },
    4: {
        'room': {'name': 'La cámara del tiempo', 'description': 'Un reloj enorme en la pared. No tiene números. Solo letras.', 'ambience': 'Tic-tac suave.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'El primer mes del año. Tiene 5 letras.', 'clue': '_ _ _ _ _', 'answer': 'enero'}
        ],
        'fragment': 'raiz-techo'
    },
    5: {
        'room': {'name': 'La cámara de los gustos', 'description': 'Frutas en la mesa. Flores en el suelo. Todo tiene olor.', 'ambience': 'Aroma de frutas tropicales.'},
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Yaguará dice: "A mí me gusta mucho. Es líquida. Es fría. Sale del río." ¿Qué es?', 'clue': 'Todos la necesitan para vivir.', 'answer': 'agua'}
        ],
        'fragment': 'raiz-nube'
    },
    6: {
        'room': {'name': 'La cámara del día', 'description': 'Una ventana muestra el sol moviéndose por el cielo.', 'ambience': 'Sonido de la mañana.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'El verbo que dice lo que haces todo el día. Empieza con V. Tiene 5 letras.', 'clue': '_ _ _ _ _', 'answer': 'vivir'}
        ],
        'fragment': 'raiz-raiz'
    },
    7: {
        'room': {'name': 'La cámara del hambre', 'description': 'Una mesa con comida. Pero la puerta está cerrada.', 'ambience': 'Olor de comida.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': '¿Qué siente Yaguará cuando no come? Tiene 6 letras. "Tengo ___."', 'clue': '_ _ _ _ _ _', 'answer': 'hambre'}
        ],
        'fragment': 'raiz-fuego'
    },
    8: {
        'room': {'name': 'La cámara de la familia', 'description': 'Fotos en la pared. Rostros de familia.', 'ambience': 'Voces lejanas. Risas.'},
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'La madre de tu madre. ¿Quién es?', 'clue': 'Empieza con A.', 'answer': 'abuela'}
        ],
        'fragment': 'raiz-canto'
    },
    9: {
        'room': {'name': 'La cámara de la casa', 'description': 'Una casa pequeña. Hay que encontrar la llave.', 'ambience': 'Crujir de madera.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'Donde cocinas. Tiene 6 letras.', 'clue': '_ _ _ _ _ _', 'answer': 'cocina'}
        ],
        'fragment': 'raiz-puerta'
    },
    10: {
        'room': {'name': 'La cámara del reloj', 'description': 'Muchos relojes. Todos marcan horas diferentes.', 'ambience': 'Tic-tac de muchos relojes.'},
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': '60 minutos. ¿Qué es?', 'clue': 'Una ___.', 'answer': 'hora'}
        ],
        'fragment': 'raiz-estrella'
    },
    11: {
        'room': {'name': 'La cámara de los verbos', 'description': 'Palabras flotando en el aire. Todas son verbos.', 'ambience': 'Susurros de verbos.'},
        'puzzles': [
            {'puzzleType': 'wordlock', 'prompt': 'El verbo irregular más importante. "Yo ___ a la selva." 3 letras.', 'clue': '_ _ _', 'answer': 'voy'}
        ],
        'fragment': 'raiz-semilla'
    },
    12: {
        'room': {'name': 'La cámara del espejo', 'description': 'Un espejo en el centro. Refleja algo que no eres.', 'ambience': 'Eco de tu propia voz.'},
        'puzzles': [
            {'puzzleType': 'riddle', 'prompt': 'Te miras en el agua. Ves tu cara. ¿Cómo se llama lo que ves?', 'clue': 'Empieza con R.', 'answer': 'reflejo'}
        ],
        'fragment': 'raiz-reflejo'
    },
}

# ============================================================
# CEFR LEVELS BY DESTINATION
# ============================================================
DEST_CEFR = {}
for d in range(1, 13):   DEST_CEFR[d] = 'A1'
for d in range(13, 19):  DEST_CEFR[d] = 'A2'
for d in range(19, 29):  DEST_CEFR[d] = 'B1'
for d in range(29, 39):  DEST_CEFR[d] = 'B2'
for d in range(39, 49):  DEST_CEFR[d] = 'C1'
for d in range(49, 59):  DEST_CEFR[d] = 'C2'


# ============================================================
# MAIN
# ============================================================
def main():
    print("=" * 60)
    print("Phase 5: Content Authoring Pass")
    print("=" * 60)

    for dest_num in range(1, 59):
        path = CONTENT_DIR / ('dest%d.json' % dest_num)
        with open(path, 'r', encoding='utf-8') as f:
            data = json.load(f)

        meta = data.get('meta', {})
        cefr = DEST_CEFR.get(dest_num, 'A1')
        title = meta.get('title', 'Destino %d' % dest_num)
        question = meta.get('closingQuestion', '')

        changes = []

        # 1. Fix arrival narrative (replace English with Spanish)
        if dest_num in ARRIVALS:
            arrival = data.get('arrival', {})
            arr_data = ARRIVALS[dest_num]
            arrival['sections'] = arr_data['sections']
            arrival['button'] = arr_data['button']
            if meta.get('previousClosingQuestion'):
                arrival['previousClosingQuestion'] = meta['previousClosingQuestion']
            data['arrival'] = arrival
            changes.append('arrival')

        # 2. Fix departure yaguaraLine
        if dest_num in DEPARTURES:
            dep = data.get('departure', {})
            dep['yaguaraLine'] = DEPARTURES[dest_num]
            data['departure'] = dep
            changes.append('departure')

        # 3. Add cronica game (insert before escape room, after content games)
        cronica = make_cronica(dest_num, cefr, title, question)
        # Check if cronica already exists
        has_cronica = any(g.get('type') == 'cronica' for g in data.get('games', []))
        if not has_cronica:
            data['games'].append(cronica)
            changes.append('cronica')

        # 4. Add escape room puzzles (A1-A2)
        if dest_num in ESCAPE_ROOMS:
            er = ESCAPE_ROOMS[dest_num]
            data['escapeRoom'] = {
                'type': 'escaperoom',
                'room': er['room'],
                'puzzles': er['puzzles'],
                'fragment': er['fragment']
            }
            changes.append('escape')

        # 5. Remove _needsContent skeleton games
        original_count = len(data.get('games', []))
        data['games'] = [g for g in data.get('games', []) if not g.get('_needsContent')]
        removed = original_count - len(data['games'])
        if removed:
            changes.append('-%d skeletons' % removed)

        # Write back
        with open(path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)

        print('  dest%d: %s (%d games)' % (dest_num, ', '.join(changes), len(data['games'])))

    print("\n" + "=" * 60)
    print("COMPLETE: All 58 destinations authored")
    print("=" * 60)


if __name__ == '__main__':
    main()
