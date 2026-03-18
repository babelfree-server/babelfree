<?php
/**
 * Spanish Grammar Knowledge Base — El Viaje del Jaguar
 * 65 grammar topics organized by CEFR level (A1–C2)
 * Each topic: keywords, multi-level explanations, examples, common errors, mnemonics
 */

function removeAccents(string $s): string {
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n'];
    return strtr($s, $map);
}

function findGrammarMatch(string $question, string $userCefr = 'A1'): ?array {
    $topics = getGrammarTopics();
    $qNorm = mb_strtolower(trim($question));
    $qPlain = removeAccents($qNorm);
    $words = preg_split('/\s+/', $qPlain);

    $cefrOrder = ['A1','A2','B1','B2','C1','C2'];
    $userIdx = array_search(strtoupper($userCefr), $cefrOrder);
    if ($userIdx === false) $userIdx = 0;

    $bestScore = 0;
    $bestTopic = null;

    foreach ($topics as $topic) {
        $score = 0;
        foreach ($topic['keywords'] as $kw) {
            $kwNorm = mb_strtolower($kw);
            $kwPlain = removeAccents($kwNorm);
            if ($qPlain === $kwPlain || $qNorm === $kwNorm) {
                $score += 100;
            }
            foreach ($words as $w) {
                if (strlen($w) < 2) continue;
                if ($w === $kwPlain || $w === $kwNorm) {
                    $score += 20;
                } elseif (strlen($kwPlain) > 2 && strpos($qPlain, $kwPlain) !== false) {
                    $score += 15;
                } elseif (strlen($w) > 3 && strpos($kwPlain, $w) !== false) {
                    $score += 8;
                }
            }
        }
        $topicIdx = array_search($topic['cefr'], $cefrOrder);
        if ($topicIdx !== false && $topicIdx <= $userIdx) {
            $score += 3;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestTopic = $topic;
        }
    }

    if ($bestScore < 10) return null;

    $result = $bestTopic;
    $selectedExplanation = '';
    for ($i = $userIdx; $i >= 0; $i--) {
        if (isset($bestTopic['explanation'][$cefrOrder[$i]])) {
            $selectedExplanation = $bestTopic['explanation'][$cefrOrder[$i]];
            break;
        }
    }
    if (!$selectedExplanation) {
        foreach ($cefrOrder as $lv) {
            if (isset($bestTopic['explanation'][$lv])) {
                $selectedExplanation = $bestTopic['explanation'][$lv];
                break;
            }
        }
    }
    $result['matched_explanation'] = $selectedExplanation;
    $result['match_score'] = $bestScore;
    return $result;
}

function getGrammarTopics(): array {
    return [

// ══════════════════════════════════════════════
// A1 BASIC (dest1-6): Core verbs, articles, basics
// ══════════════════════════════════════════════

// 1. SER (present)
[
    'topic' => 'ser_present',
    'keywords' => ['ser','soy','eres','es','somos','son','to be','am','is','are','identity','origin','ser conjugation','yo soy','ella es'],
    'cefr' => 'A1',
    'title_es' => 'El verbo ser (presente)',
    'title_en' => 'The verb ser (present)',
    'explanation' => [
        'A1' => 'SER tells WHO or WHAT something is — identity, origin, profession. Conjugation: yo SOY, tú ERES, él/ella/usted ES, nosotros SOMOS, ellos/ustedes SON. "Yo soy María." "Ella es colombiana." "Nosotros somos estudiantes."',
        'A2' => 'SER is used for: (1) Identity — Soy María. (2) Origin — Somos de Colombia. (3) Profession — Es doctora. (4) Material — La mesa es de madera. (5) Time — Son las tres. (6) Events — La fiesta es aquí. (7) Inherent qualities — Es inteligente.',
        'B1' => 'SER with adjectives = inherent/defining qualities: "Es aburrido" = He IS boring (personality). SER in passive: "El libro fue escrito por García Márquez." SER for impersonal: "Es importante estudiar."',
    ],
    'examples' => [
        ['es' => 'Yo soy colombiana.', 'en' => 'I am Colombian. (origin)'],
        ['es' => 'Nosotros somos estudiantes.', 'en' => 'We are students. (role)'],
        ['es' => '¿De dónde eres tú?', 'en' => 'Where are you from? (origin)'],
        ['es' => 'Son las dos de la tarde.', 'en' => 'It is two in the afternoon. (time)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo estoy profesora.', 'right' => 'Yo soy profesora.', 'why' => 'Professions use ser (identity).'],
        ['wrong' => 'Yo soy de acuerdo.', 'right' => 'Yo estoy de acuerdo.', 'why' => '"Estar de acuerdo" is a fixed expression.'],
    ],
    'mnemonics' => [
        'en' => 'SER = essence. If it defines WHO you are, use SER.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/ser',
],

// 2. SER vs ESTAR
[
    'topic' => 'ser_vs_estar',
    'keywords' => ['ser','estar','soy','estoy','es','está','somos','son','están','difference ser estar','ser or estar','ser vs estar','to be','cuando usar ser','cuando usar estar'],
    'cefr' => 'A1',
    'title_es' => 'Ser y estar',
    'title_en' => 'Ser vs Estar',
    'explanation' => [
        'A1' => 'SER = identity, origin, profession. "Yo SOY María." ESTAR = location, temporary state. "Yo ESTOY en casa. Él ESTÁ contento." SER = who you ARE. ESTAR = how you FEEL or where you ARE right now.',
        'A2' => 'SER for inherent qualities (Es alto). ESTAR for states (Está enfermo). Location uses ESTAR (Bogotá está en Colombia), EXCEPT events (La fiesta es en mi casa). ESTAR + gerund = progressive (Estoy comiendo).',
        'B1' => 'Adjectives change meaning: "Es listo" = clever. "Está listo" = ready. "Es aburrido" = boring. "Está aburrido" = bored. "Es vivo" = sharp. "Está vivo" = alive. SER + participle = passive action. ESTAR + participle = resulting state.',
        'B2' => 'Literary uses: "La noche era oscura" (ser for narrative quality) vs "La noche estaba oscura" (estar for atmosphere). Estar for surprise: "¡Qué guapa estás!" (you look especially pretty now).',
    ],
    'examples' => [
        ['es' => 'Yo soy estudiante.', 'en' => 'I am a student. (identity = ser)'],
        ['es' => 'Yo estoy cansado.', 'en' => 'I am tired. (state = estar)'],
        ['es' => 'La fiesta es en mi casa.', 'en' => 'The party is at my house. (event = ser)'],
        ['es' => 'María está en su casa.', 'en' => 'María is at her house. (location = estar)'],
        ['es' => 'La sopa está fría.', 'en' => 'The soup is cold. (current state = estar)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo estoy María.', 'right' => 'Yo soy María.', 'why' => 'Names use ser (identity).'],
        ['wrong' => 'Bogotá es en Colombia.', 'right' => 'Bogotá está en Colombia.', 'why' => 'Physical location uses estar.'],
        ['wrong' => 'Ella es enferma.', 'right' => 'Ella está enferma.', 'why' => 'Illness is temporary = estar.'],
    ],
    'mnemonics' => [
        'en' => 'DOCTOR (Description, Occupation, Characteristic, Time, Origin, Relation) = SER. PLACE (Position, Location, Action, Condition, Emotion) = ESTAR.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/ser',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/ser-y-estar/',
],

// 3. ARTICLES
[
    'topic' => 'articles',
    'keywords' => ['el','la','los','las','un','una','unos','unas','articles','artículos','definite','indefinite','the','a','an','al','del'],
    'cefr' => 'A1',
    'title_es' => 'Los artículos',
    'title_en' => 'Articles (the, a/an)',
    'explanation' => [
        'A1' => 'Definite (= "the"): EL (masc.), LA (fem.), LOS (masc. pl.), LAS (fem. pl.). Indefinite (= "a/an"): UN (masc.), UNA (fem.), UNOS/UNAS (some). Must match gender and number: "el libro" / "la casa" / "los libros" / "las casas".',
        'A2' => 'Special: "el agua" — fem. nouns with stressed initial "a" use EL but stay feminine: "el agua fría". Contractions: AL = a + el ("Voy al parque"), DEL = de + el ("Vengo del mercado"). No article with professions after ser: "Soy profesora."',
        'B1' => 'Articles with abstract nouns: "La felicidad es importante." Days of week: "El lunes voy al médico." Body parts use article, not possessive: "Me lavo las manos." Percentages: "El 50% de la población..."',
    ],
    'examples' => [
        ['es' => 'El gato está en la mesa.', 'en' => 'The cat is on the table.'],
        ['es' => 'Necesito un lápiz.', 'en' => 'I need a pencil.'],
        ['es' => 'Las flores son bonitas.', 'en' => 'The flowers are pretty.'],
        ['es' => 'Voy al cine.', 'en' => 'I\'m going to the cinema. (a + el = al)'],
    ],
    'common_errors' => [
        ['wrong' => 'La agua', 'right' => 'El agua', 'why' => 'Fem. nouns with stressed "a-" take "el" but stay feminine.'],
        ['wrong' => 'Voy a el parque.', 'right' => 'Voy al parque.', 'why' => '"a + el" contracts to "al".'],
        ['wrong' => 'Soy una doctora.', 'right' => 'Soy doctora.', 'why' => 'No article with professions after ser.'],
    ],
    'mnemonics' => [
        'en' => 'EL = masc. THE. LA = fem. THE. -O nouns → usually EL. -A nouns → usually LA.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/el',
],

// 4. GENDER AGREEMENT
[
    'topic' => 'gender_agreement',
    'keywords' => ['gender','género','masculine','feminine','masculino','femenino','agreement','concordancia','-o','-a','noun gender'],
    'cefr' => 'A1',
    'title_es' => 'El género (masculino y femenino)',
    'title_en' => 'Gender agreement',
    'explanation' => [
        'A1' => 'Every Spanish noun is masculine or feminine. Usually: -O = masculine (el libro), -A = feminine (la casa). Adjectives must match: "el gato negro" / "la gata negra". Plurals: "los gatos negros" / "las gatas negras".',
        'A2' => 'Exceptions: el día, el mapa, el problema, el tema (masc. despite -a). La mano, la foto, la moto (fem. despite -o). Words in -ción/-sión = fem. (la canción). Words in -or = usually masc. (el color).',
        'B1' => 'Meaning changes with gender: el capital (money) / la capital (city). El orden (arrangement) / la orden (command). El cura (priest) / la cura (cure). El policía (policeman) / la policía (force).',
    ],
    'examples' => [
        ['es' => 'El perro blanco.', 'en' => 'The white dog. (masc. + masc.)'],
        ['es' => 'La casa grande.', 'en' => 'The big house. (fem. + adj. in -e = no change)'],
        ['es' => 'Los problemas difíciles.', 'en' => 'The difficult problems. ("problema" is masculine)'],
    ],
    'common_errors' => [
        ['wrong' => 'La problema', 'right' => 'El problema', 'why' => 'Greek-origin words in -ma are masculine.'],
        ['wrong' => 'El casa blanco', 'right' => 'La casa blanca', 'why' => '"Casa" is feminine; article and adjective must agree.'],
    ],
    'mnemonics' => [
        'en' => 'Most -O = masc., most -A = fem. Rebels: el día, el mapa, el problema, la mano.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/genero',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/genero-del-sustantivo/',
],

// 5. GREETINGS
[
    'topic' => 'greetings',
    'keywords' => ['hola','buenos días','buenas tardes','buenas noches','saludos','greetings','hello','goodbye','adiós','hasta luego','cómo estás','mucho gusto','presentarse'],
    'cefr' => 'A1',
    'title_es' => 'Saludos y despedidas',
    'title_en' => 'Greetings and farewells',
    'explanation' => [
        'A1' => 'Greetings: HOLA (hello), BUENOS DÍAS (good morning), BUENAS TARDES (good afternoon), BUENAS NOCHES (good night). Questions: ¿CÓMO ESTÁS? (informal), ¿CÓMO ESTÁ USTED? (formal). Introductions: ME LLAMO... (my name is...), MUCHO GUSTO (nice to meet you). Farewells: ADIÓS, HASTA LUEGO (see you later), CHAO (bye — Latin America).',
        'A2' => 'Register: TÚ = informal (friends). USTED = formal (elders, strangers). In Colombia, USTED is used even among friends in some regions. "¿Qué tal?" = casual. "¿Qué hubo?" / "¿Quiubo?" = very informal Colombian.',
    ],
    'examples' => [
        ['es' => '¡Hola! ¿Cómo estás?', 'en' => 'Hi! How are you? (informal)'],
        ['es' => 'Buenos días, ¿cómo está usted?', 'en' => 'Good morning, how are you? (formal)'],
        ['es' => 'Me llamo Carlos. Mucho gusto.', 'en' => 'My name is Carlos. Nice to meet you.'],
        ['es' => 'Hasta luego, ¡chao!', 'en' => 'See you later, bye!'],
    ],
    'common_errors' => [
        ['wrong' => 'Buenas días', 'right' => 'Buenos días', 'why' => '"Día" is masculine, so "buenos."'],
        ['wrong' => 'Mi nombre es Carlos.', 'right' => 'Me llamo Carlos.', 'why' => '"Me llamo" is more natural than "Mi nombre es."'],
    ],
    'mnemonics' => [
        'en' => 'BUENOS días (masc.), BUENAS tardes/noches (fem.). Match the noun\'s gender.',
    ],
],

// 6. NUMBERS
[
    'topic' => 'numbers',
    'keywords' => ['numbers','números','uno','dos','tres','cuatro','cinco','seis','siete','ocho','nueve','diez','count','contar','veinte','cien','mil'],
    'cefr' => 'A1',
    'title_es' => 'Los números',
    'title_en' => 'Numbers',
    'explanation' => [
        'A1' => '1=uno, 2=dos, 3=tres, 4=cuatro, 5=cinco, 6=seis, 7=siete, 8=ocho, 9=nueve, 10=diez, 11=once, 12=doce, 13=trece, 14=catorce, 15=quince, 16=dieciséis, 17=diecisiete, 18=dieciocho, 19=diecinueve, 20=veinte. "Uno" → "un" before masc. nouns (un libro), "una" before fem. nouns.',
        'A2' => '21-29 = one word: veintiuno, veintidós... 30=treinta, then "treinta y uno..." 100=cien (before nouns) or ciento (compounds: ciento uno). Hundreds agree in gender: doscientos/doscientas. 1000=mil (no plural: dos mil, not "dos miles").',
    ],
    'examples' => [
        ['es' => 'Tengo tres gatos.', 'en' => 'I have three cats.'],
        ['es' => 'Hay quince estudiantes.', 'en' => 'There are fifteen students.'],
        ['es' => 'Un café, por favor.', 'en' => 'One coffee, please. (uno → un)'],
    ],
    'common_errors' => [
        ['wrong' => 'Tengo uno libro.', 'right' => 'Tengo un libro.', 'why' => '"Uno" shortens to "un" before masculine nouns.'],
        ['wrong' => 'Diez y seis', 'right' => 'Dieciséis', 'why' => '16-19 are written as one word.'],
    ],
    'mnemonics' => [
        'en' => '11-15 = unique forms. 16-19 = dieci+number. 21-29 = veinti+number.',
    ],
],

// 7. COLORS
[
    'topic' => 'colors',
    'keywords' => ['colors','colores','rojo','azul','verde','amarillo','blanco','negro','color','red','blue','green','rosado','morado','naranja','gris'],
    'cefr' => 'A1',
    'title_es' => 'Los colores',
    'title_en' => 'Colors',
    'explanation' => [
        'A1' => 'Colors go AFTER the noun. Colors in -o change gender/number: rojo/roja/rojos/rojas. Colors in -e or consonant change only number: verde/verdes, azul/azules. NARANJA and ROSA never change: "las camisas naranja."',
        'A2' => 'Compound colors are invariable: "ojos verde oscuro." "Claro" (light) and "oscuro" (dark): azul claro, rojo oscuro. Colors as nouns: "El azul es mi favorito."',
    ],
    'examples' => [
        ['es' => 'La casa blanca.', 'en' => 'The white house.'],
        ['es' => 'Los zapatos negros.', 'en' => 'The black shoes.'],
        ['es' => 'Una flor azul.', 'en' => 'A blue flower. (azul = no gender change)'],
    ],
    'common_errors' => [
        ['wrong' => 'La casa es azula.', 'right' => 'La casa es azul.', 'why' => '"Azul" doesn\'t change for gender.'],
        ['wrong' => 'Los ojos verdes oscuros.', 'right' => 'Los ojos verde oscuro.', 'why' => 'Compound colors are invariable.'],
    ],
    'mnemonics' => [
        'en' => '-O colors change gender (rojo→roja). Others only change number. Compound colors never change.',
    ],
],

// 8. BASIC ADJECTIVES
[
    'topic' => 'basic_adjectives',
    'keywords' => ['adjective','adjetivo','grande','pequeño','bueno','malo','bonito','nuevo','viejo','adjectives','agreement','position','buen','mal','gran'],
    'cefr' => 'A1',
    'title_es' => 'Los adjetivos básicos',
    'title_en' => 'Basic adjectives',
    'explanation' => [
        'A1' => 'Adjectives go AFTER the noun and agree in gender/number: "un chico alto" / "una chica alta" / "unos chicos altos." Common: grande (big), pequeño (small), bueno (good), malo (bad), bonito (pretty), nuevo (new), viejo (old).',
        'A2' => 'Some shorten before masc. sing. nouns: bueno→buen, malo→mal, grande→gran. "Grande" after = big; before = great. "Un libro grande" vs "un gran libro."',
        'B1' => 'Position changes meaning: "un hombre pobre" (broke) vs "un pobre hombre" (pitiful). "Un amigo viejo" (elderly) vs "un viejo amigo" (long-time). "La única respuesta" (only) vs "una respuesta única" (unique).',
    ],
    'examples' => [
        ['es' => 'Es un libro interesante.', 'en' => 'It\'s an interesting book.'],
        ['es' => 'Es un buen amigo.', 'en' => 'He\'s a good friend. (bueno → buen)'],
        ['es' => 'Es un gran escritor.', 'en' => 'He\'s a great writer. (grande → gran)'],
    ],
    'common_errors' => [
        ['wrong' => 'Un bueno libro', 'right' => 'Un buen libro', 'why' => '"Bueno" shortens to "buen" before masc. sing. nouns.'],
        ['wrong' => 'Una interesante película', 'right' => 'Una película interesante', 'why' => 'Most adjectives go after the noun.'],
    ],
    'mnemonics' => [
        'en' => 'Noun FIRST, then adjective: "casa blanca." Short forms (buen, mal, gran) go BEFORE.',
    ],
],

// 9. HAY
[
    'topic' => 'hay',
    'keywords' => ['hay','there is','there are','existence','haber','no hay','hay un','cuántos hay','how many'],
    'cefr' => 'A1',
    'title_es' => 'Hay (haber impersonal)',
    'title_en' => 'Hay (there is / there are)',
    'explanation' => [
        'A1' => 'HAY = "there is" AND "there are" (same word!). "Hay un gato" = There is a cat. "Hay tres gatos" = There are three cats. "No hay leche" = There is no milk. Uses indefinite articles or no article, NOT "el/la."',
        'A2' => 'HAY vs ESTAR: HAY = existence. ESTAR = location. "Hay un banco en la esquina" (a bank exists). "El banco está en la esquina" (the bank is there). Past: HABÍA. Future: HABRÁ.',
        'B1' => 'Always singular: "Hubo muchos problemas" (NOT *hubieron). Other tenses: ha habido (pres. perfect), había habido (pluperfect). Many speakers say "habían muchos" but grammar says "había muchos."',
    ],
    'examples' => [
        ['es' => 'Hay una farmacia aquí.', 'en' => 'There is a pharmacy here.'],
        ['es' => 'No hay problema.', 'en' => 'There\'s no problem.'],
        ['es' => '¿Hay restaurantes cerca?', 'en' => 'Are there restaurants nearby?'],
    ],
    'common_errors' => [
        ['wrong' => 'Hay el libro en la mesa.', 'right' => 'El libro está en la mesa.', 'why' => 'HAY for indefinite/existence. Definite things use ESTAR.'],
        ['wrong' => 'Habían muchas personas.', 'right' => 'Había muchas personas.', 'why' => 'Impersonal haber is always singular.'],
    ],
    'mnemonics' => [
        'en' => 'HAY = EXISTS. ESTAR = IS LOCATED. "Hay un gato" (a cat exists). "El gato está aquí" (the cat is here).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/haber',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/habian-muchas-personas-hubo-quejas/',
],

// 10. ESTAR (present)
[
    'topic' => 'estar_present',
    'keywords' => ['estar','estoy','estás','está','estamos','están','location','state','temporary','feeling','mood','estar conjugation'],
    'cefr' => 'A1',
    'title_es' => 'El verbo estar (presente)',
    'title_en' => 'The verb estar (present)',
    'explanation' => [
        'A1' => 'ESTAR = where something IS or how it FEELS now. yo ESTOY, tú ESTÁS, él/ella ESTÁ, nosotros ESTAMOS, ellos ESTÁN. Location: "Estoy en casa." Feelings: "Estoy contento." States: "La puerta está abierta."',
        'A2' => 'ESTAR + gerund (-ando/-iendo) = progressive: "Estoy comiendo." Health: "Estoy bien/mal/enfermo." ESTAR + adjective = current condition: "La comida está caliente."',
        'B1' => 'ESTAR + past participle = resulting state: "La puerta está cerrada." Compare: "La carta fue escrita por ella" (ser = action) vs "La carta está escrita en español" (estar = result).',
    ],
    'examples' => [
        ['es' => '¿Dónde estás?', 'en' => 'Where are you? (location)'],
        ['es' => 'Estoy muy contento hoy.', 'en' => 'I\'m very happy today. (feeling)'],
        ['es' => 'El café está frío.', 'en' => 'The coffee is cold. (current state)'],
        ['es' => 'Estamos estudiando español.', 'en' => 'We are studying Spanish. (progressive)'],
    ],
    'common_errors' => [
        ['wrong' => 'Estoy profesora.', 'right' => 'Soy profesora.', 'why' => 'Professions = ser.'],
        ['wrong' => 'Yo soy en Colombia.', 'right' => 'Yo estoy en Colombia.', 'why' => 'Location = estar.'],
    ],
    'mnemonics' => [
        'en' => 'ESTAR = states and locations. If it can CHANGE, use ESTAR.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/estar',
],

// ══════════════════════════════════════════════
// A1 ADVANCED (dest7-12): More verbs, possessives, prepositions, time
// ══════════════════════════════════════════════

// 11. TENER
[
    'topic' => 'tener',
    'keywords' => ['tener','tengo','tienes','tiene','tenemos','tienen','to have','have','age','tener que','tengo hambre','tengo sed','tengo frío','tengo calor','tener años'],
    'cefr' => 'A1',
    'title_es' => 'El verbo tener',
    'title_en' => 'The verb tener (to have)',
    'explanation' => [
        'A1' => 'TENER = to have. yo TENGO, tú TIENES, él TIENE, nosotros TENEMOS, ellos TIENEN. For AGE: "Tengo 20 años." Expressions: tengo hambre (hungry), tengo sed (thirsty), tengo frío (cold), tengo calor (hot), tengo sueño (sleepy), tengo miedo (afraid).',
        'A2' => 'TENER QUE + infinitive = must/have to: "Tengo que estudiar." More expressions: tener razón (be right), tener prisa (be in a hurry), tener cuidado (be careful), tener suerte (be lucky), tener ganas de (feel like).',
    ],
    'examples' => [
        ['es' => 'Tengo dos hermanos.', 'en' => 'I have two siblings.'],
        ['es' => '¿Cuántos años tienes?', 'en' => 'How old are you?'],
        ['es' => 'Tengo mucha hambre.', 'en' => 'I\'m very hungry.'],
        ['es' => 'Tenemos que irnos.', 'en' => 'We have to leave.'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo soy 20 años.', 'right' => 'Yo tengo 20 años.', 'why' => 'Age uses TENER, not ser.'],
        ['wrong' => 'Estoy hambre.', 'right' => 'Tengo hambre.', 'why' => 'Physical sensations use TENER.'],
        ['wrong' => 'Tengo que estudio.', 'right' => 'Tengo que estudiar.', 'why' => 'After "tener que" use infinitive.'],
    ],
    'mnemonics' => [
        'en' => 'English: "I AM hungry/cold." Spanish: "I HAVE hunger/cold." TENER for physical sensations.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/tener',
],

// 12. IR
[
    'topic' => 'ir',
    'keywords' => ['ir','voy','vas','va','vamos','van','to go','going','ir a','future','voy a','movement','destination','near future','futuro próximo'],
    'cefr' => 'A1',
    'title_es' => 'El verbo ir',
    'title_en' => 'The verb ir (to go)',
    'explanation' => [
        'A1' => 'IR = to go. yo VOY, tú VAS, él VA, nosotros VAMOS, ellos VAN. Always + A: "Voy a la escuela." IR A + infinitive = near future: "Voy a comer" (I\'m going to eat). ¡VAMOS! = Let\'s go!',
        'A2' => 'IR vs IRSE: "ir" = go somewhere. "irse" = leave. "Voy al cine" vs "Me voy." Preterite of IR: fui, fuiste, fue, fuimos, fueron (same as SER preterite — context determines meaning).',
        'B1' => 'IR + gerund = gradual progression: "La situación va mejorando." Preterite: "Fui al cine" (I went) vs "Fue difícil" (It was — ser). IR in commands: "Vamos a ver" (let\'s see), "Ve a tu cuarto" (go to your room).',
    ],
    'examples' => [
        ['es' => 'Voy al supermercado.', 'en' => 'I\'m going to the supermarket.'],
        ['es' => '¿Adónde vas?', 'en' => 'Where are you going?'],
        ['es' => 'Vamos a bailar.', 'en' => 'We\'re going to dance.'],
        ['es' => 'Ella va a la universidad.', 'en' => 'She goes to the university.'],
    ],
    'common_errors' => [
        ['wrong' => 'Voy en la escuela.', 'right' => 'Voy a la escuela.', 'why' => 'IR uses "a" for destination, not "en."'],
        ['wrong' => 'Voy a como.', 'right' => 'Voy a comer.', 'why' => 'After "ir a" use infinitive.'],
    ],
    'mnemonics' => [
        'en' => 'IR A + infinitive = "going to." Same as English! "Voy a comer" = "I\'m going to eat."',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/ir',
],

// 13. PRESENT TENSE -AR
[
    'topic' => 'present_ar_verbs',
    'keywords' => ['present tense','-ar verbs','hablar','bailar','caminar','estudiar','trabajar','conjugation','presente','regular verbs','ar endings','-ar'],
    'cefr' => 'A1',
    'title_es' => 'Presente: verbos regulares en -ar',
    'title_en' => 'Present tense: regular -ar verbs',
    'explanation' => [
        'A1' => 'Remove -ar, add endings: yo -O, tú -AS, él -A, nosotros -AMOS, ellos -AN. HABLAR: hablo, hablas, habla, hablamos, hablan. ESTUDIAR: estudio, estudias, estudia, estudiamos, estudian.',
        'A2' => 'Common -AR: caminar (walk), comprar (buy), cocinar (cook), cantar (sing), bailar (dance), buscar (search), escuchar (listen), llamar (call), llevar (carry/wear), mirar (look), necesitar (need), pagar (pay), tomar (take/drink), viajar (travel).',
    ],
    'examples' => [
        ['es' => 'Yo hablo español.', 'en' => 'I speak Spanish.'],
        ['es' => 'Ella estudia medicina.', 'en' => 'She studies medicine.'],
        ['es' => 'Nosotros bailamos salsa.', 'en' => 'We dance salsa.'],
        ['es' => '¿Tú trabajas aquí?', 'en' => 'Do you work here?'],
    ],
    'common_errors' => [
        ['wrong' => 'Ella hablas mucho.', 'right' => 'Ella habla mucho.', 'why' => 'Third person uses -a, not -as.'],
        ['wrong' => 'Yo hablo. Yo estudio.', 'right' => 'Hablo. Estudio.', 'why' => 'Pronouns usually omitted — verb ending shows subject.'],
    ],
    'mnemonics' => [
        'en' => '-AR endings: O, AS, A, AMOS, AN.',
    ],
],

// 14. PRESENT TENSE -ER/-IR
[
    'topic' => 'present_er_ir_verbs',
    'keywords' => ['-er verbs','-ir verbs','comer','beber','vivir','escribir','leer','er conjugation','ir conjugation','-er','-ir'],
    'cefr' => 'A1',
    'title_es' => 'Presente: verbos en -er/-ir',
    'title_en' => 'Present tense: -er/-ir verbs',
    'explanation' => [
        'A1' => '-ER (comer): como, comes, come, comemos, comen. -IR (vivir): vivo, vives, vive, vivimos, viven. Almost identical! Only difference: nosotros (-EMOS vs -IMOS).',
        'A2' => 'Common -ER: aprender, comprender, correr, creer, deber, leer, vender. Common -IR: abrir, decidir, describir, recibir, subir, escribir.',
    ],
    'examples' => [
        ['es' => 'Yo como fruta todos los días.', 'en' => 'I eat fruit every day.'],
        ['es' => '¿Dónde vives?', 'en' => 'Where do you live?'],
        ['es' => 'Ella lee mucho.', 'en' => 'She reads a lot.'],
        ['es' => 'Nosotros escribimos cartas.', 'en' => 'We write letters.'],
    ],
    'common_errors' => [
        ['wrong' => 'Nosotros comimos.', 'right' => 'Nosotros comemos.', 'why' => '"Comimos" is past. Present nosotros -ER = -emos.'],
        ['wrong' => 'Él come. Él bebe.', 'right' => 'Come. Bebe.', 'why' => 'Omit pronoun when subject is clear.'],
    ],
    'mnemonics' => [
        'en' => '-ER: O, ES, E, EMOS, EN. -IR: O, ES, E, IMOS, EN. Only nosotros differs!',
    ],
],

// 15. POSSESSIVES
[
    'topic' => 'possessives',
    'keywords' => ['possessive','posesivo','mi','tu','su','mis','tus','sus','nuestro','nuestra','my','your','his','her','their','our','mío','tuyo','suyo'],
    'cefr' => 'A1',
    'title_es' => 'Los posesivos',
    'title_en' => 'Possessive adjectives',
    'explanation' => [
        'A1' => 'MI/MIS (my), TU/TUS (your), SU/SUS (his/her/their/your-formal). Plural when thing possessed is plural: "mi casa" / "mis libros." NUESTRO/NUESTRA/NUESTROS/NUESTRAS (our) = changes for gender AND number.',
        'A2' => 'SU is ambiguous. Clarify with "de": "el libro de él" (his). Stressed forms go AFTER: mío/mía, tuyo/tuya, suyo/suya. "Un amigo mío" (a friend of mine). "¡Es mío!" (It\'s mine!).',
        'B1' => 'Stressed possessives as pronouns: "El mío es azul" (Mine is blue). After ser: "Este libro es mío." Note accents: mí (pronoun: para mí) vs mi (possessive: mi casa).',
    ],
    'examples' => [
        ['es' => 'Mi familia es grande.', 'en' => 'My family is big.'],
        ['es' => '¿Dónde están tus llaves?', 'en' => 'Where are your keys?'],
        ['es' => 'Su casa es bonita.', 'en' => 'His/Her/Their house is pretty.'],
        ['es' => 'Nuestra escuela es nueva.', 'en' => 'Our school is new.'],
    ],
    'common_errors' => [
        ['wrong' => 'Mi amigos', 'right' => 'Mis amigos', 'why' => 'Plural noun = mis/tus/sus.'],
        ['wrong' => 'Nuestro madre', 'right' => 'Nuestra madre', 'why' => '"Nuestro" must match gender.'],
    ],
    'mnemonics' => [
        'en' => 'MI/TU/SU change only for NUMBER (mis/tus/sus). NUESTRO changes for BOTH gender and number.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/posesivos',
],

// 16. PREPOSITIONS (en/de/con/a)
[
    'topic' => 'prepositions_basic',
    'keywords' => ['preposition','preposición','en','de','con','a','prepositions','in','of','with','to','at','from','al','del'],
    'cefr' => 'A1',
    'title_es' => 'Preposiciones básicas (en, de, con, a)',
    'title_en' => 'Basic prepositions',
    'explanation' => [
        'A1' => 'EN = in/on/at: "Estoy en casa." DE = of/from: "Soy de Colombia." "Un vaso de agua." CON = with: "Café con leche." A = to: "Voy a la escuela." Contractions: a + el = AL, de + el = DEL.',
        'A2' => 'A before people (personal a): "Veo a mi madre" (I see my mother). No personal a with tener: "Tengo un hermano" (not "tengo a un hermano"). EN for transport: "Voy en bus." DE for possession: "El libro de María." CON + mí = conmigo, con + ti = contigo.',
    ],
    'examples' => [
        ['es' => 'Estoy en la oficina.', 'en' => 'I\'m at the office.'],
        ['es' => 'Vengo del mercado.', 'en' => 'I come from the market. (de + el = del)'],
        ['es' => 'Voy con mi amiga.', 'en' => 'I\'m going with my friend.'],
        ['es' => 'Llamo a mi madre.', 'en' => 'I call my mother. (personal a)'],
    ],
    'common_errors' => [
        ['wrong' => 'Veo mi madre.', 'right' => 'Veo a mi madre.', 'why' => 'People as direct objects need "personal a."'],
        ['wrong' => 'Con mí', 'right' => 'Conmigo', 'why' => '"Con + mí" becomes "conmigo" (one word).'],
    ],
    'mnemonics' => [
        'en' => 'Personal A: if the direct object is a PERSON, add "a" before them. "Veo a Juan." But not with things: "Veo la casa."',
    ],
],

// 17. TELLING TIME
[
    'topic' => 'telling_time',
    'keywords' => ['time','hora','son las','es la','qué hora es','telling time','clock','mediodía','medianoche','y media','y cuarto','menos'],
    'cefr' => 'A1',
    'title_es' => 'La hora',
    'title_en' => 'Telling time',
    'explanation' => [
        'A1' => '¿Qué hora es? ES LA una (1:00 — singular). SON LAS dos/tres/cuatro... (plural). Add minutes with Y: "Son las tres y diez" (3:10). Y MEDIA = :30. Y CUARTO = :15. MENOS CUARTO = :45 (quarter to). Mediodía = noon. Medianoche = midnight.',
        'A2' => 'DE LA MAÑANA (AM), DE LA TARDE (afternoon), DE LA NOCHE (PM/night). "Son las ocho de la mañana." For schedules: "A las nueve" (at nine). "Desde las dos hasta las cinco" (from 2 to 5).',
    ],
    'examples' => [
        ['es' => 'Son las tres y media.', 'en' => 'It\'s 3:30.'],
        ['es' => 'Es la una y cuarto.', 'en' => 'It\'s 1:15.'],
        ['es' => 'Son las cinco menos cuarto.', 'en' => 'It\'s 4:45 (quarter to five).'],
        ['es' => 'La clase es a las diez.', 'en' => 'The class is at ten.'],
    ],
    'common_errors' => [
        ['wrong' => 'Son la una.', 'right' => 'Es la una.', 'why' => '1:00 is singular = "es la una."'],
        ['wrong' => 'A las seis de la noche por la mañana.', 'right' => 'A las seis de la mañana.', 'why' => 'Use one time period only.'],
    ],
    'mnemonics' => [
        'en' => '"Es LA una" (1 = singular). "Son LAS dos+" (2+ = plural). Y = past the hour. MENOS = before the hour.',
    ],
],

// 18. FAMILY VOCABULARY
[
    'topic' => 'family',
    'keywords' => ['family','familia','madre','padre','hermano','hermana','hijo','hija','abuelo','abuela','tío','tía','primo','prima','parents','padres','siblings'],
    'cefr' => 'A1',
    'title_es' => 'La familia',
    'title_en' => 'Family vocabulary',
    'explanation' => [
        'A1' => 'Padre (father), madre (mother), padres (parents). Hermano/hermana (brother/sister). Hijo/hija (son/daughter). Abuelo/abuela (grandfather/grandmother). Tío/tía (uncle/aunt). Primo/prima (cousin). Esposo/esposa (husband/wife). Sobrino/sobrina (nephew/niece). Suegro/suegra (father/mother-in-law).',
        'A2' => 'Cuñado/cuñada (brother/sister-in-law). Yerno (son-in-law), nuera (daughter-in-law). Hermanastro/hermanastra (step-sibling). Medio hermano (half-sibling). Familia política (in-laws). Pareja (partner). Novio/novia (boyfriend/girlfriend).',
    ],
    'examples' => [
        ['es' => 'Tengo dos hermanos y una hermana.', 'en' => 'I have two brothers and one sister.'],
        ['es' => 'Mis abuelos viven en Bogotá.', 'en' => 'My grandparents live in Bogotá.'],
        ['es' => 'Mi tía es doctora.', 'en' => 'My aunt is a doctor.'],
    ],
    'common_errors' => [
        ['wrong' => 'Mis padres son mi mamá y mi papá.', 'right' => 'Correct! "Padres" = parents (not "fathers").', 'why' => '"Padres" in plural means parents, not fathers.'],
        ['wrong' => 'Parientes = parents', 'right' => 'Parientes = relatives', 'why' => 'False friend! Parientes = relatives. Parents = padres.'],
    ],
    'mnemonics' => [
        'en' => '"Padres" = parents (not fathers). "Parientes" = relatives (not parents). Common false friend!',
    ],
],

// ══════════════════════════════════════════════
// A2 BASIC (dest13-15): Reflexives, preterite, modal verbs, pronouns
// ══════════════════════════════════════════════

// 19. REFLEXIVE VERBS
[
    'topic' => 'reflexive_verbs',
    'keywords' => ['reflexive','reflexivo','se','me','te','nos','levantarse','llamarse','bañarse','vestirse','acostarse','sentirse','daily routine','rutina'],
    'cefr' => 'A2',
    'title_es' => 'Verbos reflexivos',
    'title_en' => 'Reflexive verbs',
    'explanation' => [
        'A2' => 'Reflexive verbs = action done to oneself. Pronouns: me, te, se, nos, se. "Me llamo Juan" (I call myself Juan). "Me levanto a las 7" (I get up at 7). Daily routine: levantarse, ducharse, vestirse, peinarse, acostarse. Some verbs change meaning: ir→irse (leave), dormir→dormirse (fall asleep), poner→ponerse (put on).',
        'B1' => 'Reciprocal: "Nos vemos" (We see each other). Impersonal se: "Se habla español aquí." Emphatic se: "Se comió toda la pizza" (ate ALL of it). Placement: before conjugated verb or attached to infinitive/gerund.',
    ],
    'examples' => [
        ['es' => 'Me despierto a las seis.', 'en' => 'I wake up at six.'],
        ['es' => 'Ella se viste rápido.', 'en' => 'She gets dressed quickly.'],
        ['es' => '¿Cómo te llamas?', 'en' => 'What\'s your name? (lit. how do you call yourself?)'],
        ['es' => 'Nos vemos mañana.', 'en' => 'We\'ll see each other tomorrow.'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo levanto a las 7.', 'right' => 'Me levanto a las 7.', 'why' => 'Reflexive pronoun needed.'],
        ['wrong' => 'Ella peina.', 'right' => 'Ella se peina.', 'why' => 'Doing to oneself = reflexive.'],
    ],
    'mnemonics' => [
        'en' => 'If you can add "myself/yourself/himself" and it makes sense, it\'s reflexive.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/se',
],

// 20. PRETERITE REGULAR
[
    'topic' => 'preterite_regular',
    'keywords' => ['preterite','pretérito','past tense','pasado','-é','-aste','-ó','-amos','-aron','-í','-iste','-ió','-ieron','yesterday','ayer','completed action'],
    'cefr' => 'A2',
    'title_es' => 'Pretérito regular',
    'title_en' => 'Preterite tense (regular)',
    'explanation' => [
        'A2' => 'For completed past actions. -AR: -é, -aste, -ó, -amos, -aron. HABLAR: hablé, hablaste, habló, hablamos, hablaron. -ER/-IR: -í, -iste, -ió, -imos, -ieron. COMER: comí, comiste, comió, comimos, comieron. Triggers: ayer, anoche, la semana pasada, el año pasado.',
        'B1' => 'Note: nosotros forms for -AR and -IR are same in present and preterite (hablamos, vivimos) — context clarifies. Spelling changes in yo: buscar→busqué, llegar→llegué, empezar→empecé (to preserve sound).',
    ],
    'examples' => [
        ['es' => 'Ayer hablé con mi madre.', 'en' => 'Yesterday I spoke with my mother.'],
        ['es' => 'Ella comió paella.', 'en' => 'She ate paella.'],
        ['es' => 'Viajamos a Bogotá el año pasado.', 'en' => 'We traveled to Bogotá last year.'],
        ['es' => '¿Estudiaste para el examen?', 'en' => 'Did you study for the exam?'],
    ],
    'common_errors' => [
        ['wrong' => 'Ayer yo hablo con ella.', 'right' => 'Ayer yo hablé con ella.', 'why' => '"Ayer" = past, so use preterite.'],
        ['wrong' => 'Yo llegé tarde.', 'right' => 'Yo llegué tarde.', 'why' => 'Spelling change: -gar → -gué to keep the "g" sound.'],
    ],
    'mnemonics' => [
        'en' => '-AR preterite: -É, -ASTE, -Ó, -AMOS, -ARON. -ER/-IR: -Í, -ISTE, -IÓ, -IMOS, -IERON.',
    ],
],

// 21. MODAL VERBS (necesitar/querer/poder + infinitive)
[
    'topic' => 'modal_verbs',
    'keywords' => ['necesitar','querer','poder','infinitive','modal','can','want','need','puedo','quiero','necesito','deber','should','have to','poder conjugation'],
    'cefr' => 'A2',
    'title_es' => 'Verbos modales + infinitivo',
    'title_en' => 'Modal verbs + infinitive',
    'explanation' => [
        'A2' => 'These verbs are followed by an INFINITIVE: PODER (can): "Puedo hablar." QUERER (want): "Quiero comer." NECESITAR (need): "Necesito estudiar." DEBER (should): "Debes dormir más." The modal conjugates; the second verb stays infinitive.',
        'B1' => 'PODER: puedo, puedes, puede, podemos, pueden (stem change o→ue). QUERER: quiero, quieres, quiere, queremos, quieren (e→ie). DEBER vs TENER QUE: "Debes estudiar" (you should) vs "Tienes que estudiar" (you have to — stronger). DEBER DE + inf. = probability: "Debe de estar en casa" (He must be at home).',
    ],
    'examples' => [
        ['es' => '¿Puedes ayudarme?', 'en' => 'Can you help me?'],
        ['es' => 'Quiero aprender español.', 'en' => 'I want to learn Spanish.'],
        ['es' => 'Necesitas descansar.', 'en' => 'You need to rest.'],
        ['es' => 'Debemos salir ahora.', 'en' => 'We should leave now.'],
    ],
    'common_errors' => [
        ['wrong' => 'Quiero como.', 'right' => 'Quiero comer.', 'why' => 'After modal verb, use INFINITIVE.'],
        ['wrong' => 'Yo podemos ir.', 'right' => 'Yo puedo ir. / Nosotros podemos ir.', 'why' => 'Subject and verb must agree.'],
    ],
    'mnemonics' => [
        'en' => 'Modal + INFINITIVE. Never conjugate both verbs: "Quiero COMER" not "Quiero como."',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/deber',
],

// 22. DIRECT OBJECT PRONOUNS
[
    'topic' => 'direct_object_pronouns',
    'keywords' => ['direct object','objeto directo','lo','la','los','las','me','te','nos','pronoun','pronombre','replace noun','it','them'],
    'cefr' => 'A2',
    'title_es' => 'Pronombres de objeto directo',
    'title_en' => 'Direct object pronouns',
    'explanation' => [
        'A2' => 'Replace the thing that receives the action: me (me), te (you), lo (him/it-m), la (her/it-f), nos (us), los (them-m), las (them-f). Go BEFORE the conjugated verb: "¿El libro? Lo tengo." "¿La carta? La escribo."',
        'B1' => 'With infinitives/gerunds: attached or before. "Quiero verlo" = "Lo quiero ver." "Estoy haciéndolo" = "Lo estoy haciendo." With commands: attached to affirmative ("Cómelo"), before negative ("No lo comas").',
        'B2' => 'Leísmo in Spain: "le" for masculine persons instead of "lo" ("Le vi" instead of "Lo vi"). Accepted by RAE for masculine singular persons only. Latin America generally uses lo/la system.',
    ],
    'examples' => [
        ['es' => '¿La pizza? La como todos los viernes.', 'en' => 'Pizza? I eat it every Friday.'],
        ['es' => '¿Los libros? Los necesito.', 'en' => 'The books? I need them.'],
        ['es' => 'Te quiero mucho.', 'en' => 'I love you a lot.'],
        ['es' => 'Quiero comprarla. / La quiero comprar.', 'en' => 'I want to buy it. (two positions)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo lo quiero el libro.', 'right' => 'Lo quiero. / Quiero el libro.', 'why' => 'Don\'t use BOTH the pronoun and the noun.'],
        ['wrong' => 'Quiero lo ver.', 'right' => 'Quiero verlo. / Lo quiero ver.', 'why' => 'Pronoun attaches to infinitive or goes before conjugated verb.'],
    ],
    'mnemonics' => [
        'en' => 'LO = him/it (masc.), LA = her/it (fem.), LOS/LAS = them. Place BEFORE conjugated verb.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/leismo',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/leismo-laismo-loismo/',
],

// ══════════════════════════════════════════════
// A2 ADVANCED (dest16-18): Weather, imperfect, comparisons, demonstratives
// ══════════════════════════════════════════════

// 23. WEATHER
[
    'topic' => 'weather',
    'keywords' => ['weather','clima','tiempo','hace calor','hace frío','llueve','nieva','está nublado','hay niebla','hace sol','hace viento','weather expressions'],
    'cefr' => 'A2',
    'title_es' => 'El clima / el tiempo',
    'title_en' => 'Weather expressions',
    'explanation' => [
        'A2' => 'Three structures: HACE + noun: hace calor (hot), hace frío (cold), hace sol (sunny), hace viento (windy), hace buen/mal tiempo. ESTÁ + adj: está nublado (cloudy), está despejado (clear). HAY + noun: hay niebla (foggy), hay tormenta (storm). Verbs: llueve (it rains), nieva (it snows), truena (it thunders).',
        'B1' => 'Past weather: "Hacía frío" (it was cold — imperfect for description), "Hizo calor ayer" (it was hot yesterday — preterite for completed). "Estaba lloviendo cuando salí" (it was raining when I left).',
    ],
    'examples' => [
        ['es' => 'Hoy hace mucho calor.', 'en' => 'Today it\'s very hot.'],
        ['es' => 'Está lloviendo.', 'en' => 'It\'s raining.'],
        ['es' => 'Hay niebla por la mañana.', 'en' => 'There\'s fog in the morning.'],
        ['es' => 'Ayer hizo frío.', 'en' => 'Yesterday it was cold.'],
    ],
    'common_errors' => [
        ['wrong' => 'Es calor.', 'right' => 'Hace calor.', 'why' => 'Weather uses HACE, not ser.'],
        ['wrong' => 'Hace nublado.', 'right' => 'Está nublado.', 'why' => 'Adjectives use ESTÁ, not hace.'],
    ],
    'mnemonics' => [
        'en' => 'HACE + noun, ESTÁ + adjective, HAY + noun, VERB alone. "Hace calor, está nublado, hay niebla, llueve."',
    ],
],

// 24. IMPERFECT TENSE
[
    'topic' => 'imperfect_tense',
    'keywords' => ['imperfect','imperfecto','-aba','-ía','era','tenía','vivía','used to','was doing','habitual past','description past','cuando era niño','de pequeño'],
    'cefr' => 'A2',
    'title_es' => 'El imperfecto',
    'title_en' => 'Imperfect tense',
    'explanation' => [
        'A2' => 'For habitual/ongoing past. -AR: -aba, -abas, -aba, -ábamos, -aban. HABLAR: hablaba, hablabas... -ER/-IR: -ía, -ías, -ía, -íamos, -ían. COMER: comía, comías... Only 3 irregulars: SER (era, eras, era, éramos, eran), IR (iba, ibas, iba, íbamos, iban), VER (veía, veías, veía, veíamos, veían).',
        'B1' => 'Uses: (1) Habitual past: "Jugaba todos los días." (2) Descriptions: "Era alta y bonita." (3) Background: "Llovía cuando llegué." (4) Age/time in past: "Tenía 10 años." (5) Polite requests: "Quería pedirte un favor." Triggers: siempre, todos los días, de niño, generalmente.',
    ],
    'examples' => [
        ['es' => 'Cuando era niño, jugaba en el parque.', 'en' => 'When I was a child, I played in the park.'],
        ['es' => 'Mi abuela cocinaba muy bien.', 'en' => 'My grandmother cooked very well. (habitual)'],
        ['es' => 'Eran las tres de la tarde.', 'en' => 'It was three in the afternoon.'],
        ['es' => 'Vivíamos en Cartagena.', 'en' => 'We used to live in Cartagena.'],
    ],
    'common_errors' => [
        ['wrong' => 'Cuando era niño, fui al parque cada día.', 'right' => 'Cuando era niño, iba al parque cada día.', 'why' => 'Habitual past = imperfect (iba), not preterite.'],
        ['wrong' => 'Hacieron buen tiempo ayer.', 'right' => 'Hizo/Hacía buen tiempo ayer.', 'why' => 'Weather in past usually uses hacer (singular).'],
    ],
    'mnemonics' => [
        'en' => 'Only 3 irregular imperfects: SER (era), IR (iba), VER (veía). Everything else is regular!',
    ],
],

// 25. COMPARISONS
[
    'topic' => 'comparisons',
    'keywords' => ['comparison','comparación','más que','menos que','tan como','tanto como','better','worse','mayor','menor','mejor','peor','superlative','comparative'],
    'cefr' => 'A2',
    'title_es' => 'Comparaciones',
    'title_en' => 'Comparisons',
    'explanation' => [
        'A2' => 'More: MÁS + adj. + QUE: "Ella es más alta que yo." Less: MENOS + adj. + QUE: "Es menos difícil que eso." Equal: TAN + adj. + COMO: "Es tan inteligente como tú." TANTO/A/OS/AS + noun + COMO: "Tengo tantos libros como tú."',
        'B1' => 'Irregular comparatives: bueno→mejor (better), malo→peor (worse), grande→mayor (older), pequeño→menor (younger). "Mi hermano es mayor que yo." Superlatives: "el/la más + adj.": "Es la más inteligente de la clase." Absolute superlative: -ísimo: "altísimo, facilísimo."',
    ],
    'examples' => [
        ['es' => 'Colombia es más grande que Ecuador.', 'en' => 'Colombia is bigger than Ecuador.'],
        ['es' => 'Este libro es tan bueno como ese.', 'en' => 'This book is as good as that one.'],
        ['es' => 'Ella es la mejor estudiante.', 'en' => 'She is the best student.'],
        ['es' => 'La comida está riquísima.', 'en' => 'The food is delicious! (-ísima)'],
    ],
    'common_errors' => [
        ['wrong' => 'Más bueno que', 'right' => 'Mejor que', 'why' => '"Bueno" has irregular comparative: mejor.'],
        ['wrong' => 'Ella es tan alta que yo.', 'right' => 'Ella es tan alta como yo.', 'why' => 'Equality uses COMO, not QUE.'],
        ['wrong' => 'Más mayor', 'right' => 'Mayor', 'why' => '"Mayor" already means "older/bigger." Don\'t add "más."'],
    ],
    'mnemonics' => [
        'en' => 'MÁS...QUE (more than), MENOS...QUE (less than), TAN...COMO (as...as). Irregulars: mejor, peor, mayor, menor.',
    ],
],

// 26. DEMONSTRATIVES
[
    'topic' => 'demonstratives',
    'keywords' => ['demonstrative','demostrativo','este','ese','aquel','esta','esa','aquella','estos','esos','aquellos','this','that','these','those'],
    'cefr' => 'A2',
    'title_es' => 'Los demostrativos',
    'title_en' => 'Demonstratives (this/that)',
    'explanation' => [
        'A2' => 'Three distances: ESTE/ESTA (this — near me), ESE/ESA (that — near you), AQUEL/AQUELLA (that over there — far from both). Plurals: estos/estas, esos/esas, aquellos/aquellas. Neutral (no gender): esto, eso, aquello — for ideas/unknown things: "¿Qué es esto?"',
        'B1' => 'In writing, demonstratives can refer to time: "este año" (this year), "ese día" (that day), "aquel verano" (that summer — distant past). As pronouns, they replaced: "Quiero este" (I want this one). RAE no longer requires accent on pronoun forms.',
    ],
    'examples' => [
        ['es' => 'Este libro es interesante.', 'en' => 'This book is interesting. (near me)'],
        ['es' => 'Esa casa es bonita.', 'en' => 'That house is pretty. (near you)'],
        ['es' => '¿Ves aquella montaña?', 'en' => 'Do you see that mountain over there?'],
        ['es' => '¿Qué es eso?', 'en' => 'What is that? (neutral — unknown thing)'],
    ],
    'common_errors' => [
        ['wrong' => 'Esto libro', 'right' => 'Este libro', 'why' => '"Esto" is neutral (no noun). Use "este" (masc.) or "esta" (fem.) with nouns.'],
        ['wrong' => 'Estas problemas', 'right' => 'Estos problemas', 'why' => '"Problema" is masculine, so "estos."'],
    ],
    'mnemonics' => [
        'en' => 'This/these have T\'s (este/estos). That/those don\'t. AQUEL = far away ("a WHALE of a distance").',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/este',
],

// ══════════════════════════════════════════════
// B1 BASIC (dest19-22): Pret vs Imp, indirect speech, por/para, pronouns
// ══════════════════════════════════════════════

// 27. PRETERITE VS IMPERFECT
[
    'topic' => 'preterite_vs_imperfect',
    'keywords' => ['pretérito','preterite','imperfecto','imperfect','preterite vs imperfect','past tense','completed','ongoing','background','foreground','narrative','when to use'],
    'cefr' => 'B1',
    'title_es' => 'Pretérito vs. imperfecto',
    'title_en' => 'Preterite vs Imperfect',
    'explanation' => [
        'A2' => 'Preterite = completed: "Comí una manzana." Imperfect = ongoing/habitual: "Comía manzanas de niño." They often appear together: "Llovía cuando salí."',
        'B1' => 'In narration: imperfect sets the SCENE (background), preterite advances the PLOT (events). "Era de noche. Hacía frío. De repente, alguien llamó a la puerta." Verbs change meaning: saber — sabía (knew) / supe (found out). Conocer — conocía (was acquainted) / conocí (met). Querer — quería (wanted) / quise (tried). No querer — no quería (didn\'t want) / no quise (refused).',
        'B2' => 'Imperfect for politeness: "Quería pedirte un favor." Double preterite = sequential. Double imperfect = simultaneous ongoing. "Mientras ella leía, yo cocinaba" (both ongoing). "Llegó y se sentó" (one then the other).',
    ],
    'examples' => [
        ['es' => 'Ayer comí paella.', 'en' => 'Yesterday I ate paella. (completed)'],
        ['es' => 'De niño comía paella los domingos.', 'en' => 'As a child I ate paella on Sundays. (habitual)'],
        ['es' => 'Llovía cuando llegué a casa.', 'en' => 'It was raining when I arrived home.'],
        ['es' => 'Conocí a María en la universidad.', 'en' => 'I met María at university. (first meeting = preterite)'],
    ],
    'common_errors' => [
        ['wrong' => 'Cuando era niño, fui al parque cada día.', 'right' => 'Cuando era niño, iba al parque cada día.', 'why' => 'Habitual = imperfect.'],
        ['wrong' => 'Ayer llovía todo el día.', 'right' => 'Ayer llovió todo el día.', 'why' => 'Completed duration (all day yesterday) = preterite.'],
    ],
    'mnemonics' => [
        'en' => 'Preterite = PHOTO (snapshot). Imperfect = VIDEO (ongoing scene). "Used to" or "was doing" = imperfect.',
    ],
],

// 28. POR VS PARA
[
    'topic' => 'por_vs_para',
    'keywords' => ['por','para','por vs para','for','because','through','in order to','purpose','cause','exchange','deadline','por qué','para qué'],
    'cefr' => 'B1',
    'title_es' => 'Por vs. para',
    'title_en' => 'Por vs Para',
    'explanation' => [
        'A2' => 'PARA = purpose/destination ("para ti" = for you, "para comer" = to eat). POR = cause/exchange ("por favor" = please, "gracias por" = thanks for).',
        'B1' => 'PARA: purpose (para estudiar), destination (para Colombia), deadline (para el lunes), recipient (para ti), opinion (para mí). POR: cause (por la lluvia), duration (por dos horas), exchange (por 10 dólares), through (por el parque), means (por teléfono), agent in passive (escrito por Cervantes).',
        'B2' => 'Fixed: por fin, por supuesto, por lo menos, para colmo, para siempre. "Estar por" (about to / in favor) vs "estar para" (ready to). "POR + infinitive" = because of: "Por no estudiar, reprobó." "¿Por qué?" (why — cause) vs "¿Para qué?" (what for — purpose).',
    ],
    'examples' => [
        ['es' => 'Este regalo es para ti.', 'en' => 'This gift is for you. (recipient)'],
        ['es' => 'Gracias por tu ayuda.', 'en' => 'Thanks for your help. (cause)'],
        ['es' => 'Caminé por el parque.', 'en' => 'I walked through the park.'],
        ['es' => 'Estudio para aprender.', 'en' => 'I study to learn. (purpose)'],
        ['es' => 'Lo compré por 20 dólares.', 'en' => 'I bought it for 20 dollars. (exchange)'],
    ],
    'common_errors' => [
        ['wrong' => 'Gracias para tu ayuda.', 'right' => 'Gracias por tu ayuda.', 'why' => 'Cause/reason uses POR.'],
        ['wrong' => 'Voy por la escuela.', 'right' => 'Voy para la escuela.', 'why' => 'Destination uses PARA.'],
    ],
    'mnemonics' => [
        'en' => 'PARA → forward (purpose, destination, deadline). POR → backward (cause, reason, exchange).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/por',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/por-y-para/',
],

// 29. INDIRECT OBJECT PRONOUNS
[
    'topic' => 'indirect_object_pronouns',
    'keywords' => ['indirect object','objeto indirecto','le','les','me','te','nos','se lo','se la','to him','to her','give','tell','send','double pronouns'],
    'cefr' => 'B1',
    'title_es' => 'Pronombres de objeto indirecto',
    'title_en' => 'Indirect object pronouns',
    'explanation' => [
        'B1' => 'Show who benefits/receives: me (to me), te (to you), le (to him/her/you-formal), nos (to us), les (to them). "Le doy el libro" (I give the book to him). Doubling is normal: "A María le gusta el café." When both direct + indirect: indirect first, and LE/LES become SE: "Se lo doy" (I give it to him/her).',
        'B2' => 'Placement: before conjugated verb ("Le doy"), attached to infinitive ("Quiero darle"), attached to gerund ("Estoy dándole"), attached to affirmative commands ("Dile"), before negative commands ("No le digas"). Order: SE > indirect > direct > verb. Clarify with "a": "Se lo di a ella."',
    ],
    'examples' => [
        ['es' => 'Le escribo una carta a mi madre.', 'en' => 'I write a letter to my mother.'],
        ['es' => '¿Me puedes ayudar?', 'en' => 'Can you help me?'],
        ['es' => 'Se lo doy mañana.', 'en' => 'I\'ll give it to him/her tomorrow.'],
        ['es' => 'Les compré regalos a mis hijos.', 'en' => 'I bought gifts for my children.'],
    ],
    'common_errors' => [
        ['wrong' => 'Le lo doy.', 'right' => 'Se lo doy.', 'why' => 'LE becomes SE before lo/la/los/las.'],
        ['wrong' => 'Quiero le dar.', 'right' => 'Quiero darle. / Le quiero dar.', 'why' => 'Pronoun attaches to infinitive or goes before conjugated verb.'],
    ],
    'mnemonics' => [
        'en' => 'RID order: Reflexive, Indirect, Direct. LE + LO = SE LO (le vanishes, se appears).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/laismo',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/leismo-laismo-loismo/',
],

// 30. INDIRECT SPEECH (basic)
[
    'topic' => 'indirect_speech_basic',
    'keywords' => ['indirect speech','reported speech','estilo indirecto','dijo que','dice que','said that','told me','reporting','he said','she said'],
    'cefr' => 'B1',
    'title_es' => 'El estilo indirecto (básico)',
    'title_en' => 'Indirect/reported speech (basic)',
    'explanation' => [
        'B1' => 'Report what someone said: DICE QUE + no tense change (present reporting): "Ella dice que está cansada." DIJO QUE + tense shifts back (past reporting): present→imperfect: "Dijo que estaba cansada." Preterite→pluperfect: "Dijo que había comido." Future→conditional: "Dijo que vendría."',
        'B2' => 'Full tense sequence: present→imperfect, preterite→pluperfect, future→conditional, present perfect→pluperfect, imperative→subjunctive. Pronouns and possessives shift too: "mi"→"su", "aquí"→"allí", "hoy"→"ese día", "mañana"→"al día siguiente."',
    ],
    'examples' => [
        ['es' => 'María dice que está cansada.', 'en' => 'María says she is tired. (no shift)'],
        ['es' => 'María dijo que estaba cansada.', 'en' => 'María said she was tired. (present→imperfect)'],
        ['es' => 'Me dijo que vendría mañana.', 'en' => 'He told me he would come tomorrow.'],
    ],
    'common_errors' => [
        ['wrong' => 'Dijo que está cansada.', 'right' => 'Dijo que estaba cansada.', 'why' => 'Past reporting verb (dijo) requires tense backshift.'],
        ['wrong' => 'Dijo que viene mañana.', 'right' => 'Dijo que vendría al día siguiente.', 'why' => 'Future becomes conditional; time references shift.'],
    ],
    'mnemonics' => [
        'en' => 'DICE QUE = no change. DIJO QUE = shift back one tense. Present→Imperfect. Future→Conditional.',
    ],
],

// 31. PRESENT TENSE IRREGULARS
[
    'topic' => 'present_irregulars',
    'keywords' => ['irregular','presente','present irregular','stem change','go verbs','yo irregular','e to ie','o to ue','e to i','pienso','puedo','pido','tengo','vengo','hago','digo','salgo'],
    'cefr' => 'A1',
    'title_es' => 'Presente: verbos irregulares',
    'title_en' => 'Present tense irregular verbs',
    'explanation' => [
        'A1' => 'Key irregulars: ser (soy...), ir (voy...), tener (tengo...), hacer (hago...). Learn these first — they\'re the most common verbs.',
        'A2' => 'Stem-changers in all forms except nosotros: e→ie (pensar: pienso, piensas, piensa, pensamos, piensan). o→ue (poder: puedo, puedes, puede, podemos, pueden). e→i (pedir: pido, pides, pide, pedimos, piden). "Go" verbs (-go in yo): tengo, vengo, pongo, salgo, digo, hago, traigo, oigo.',
        'B1' => '-CER/-CIR verbs: yo adds z: conocer→conozco, traducir→traduzco. Spelling changes: -GER/-GIR: escoger→escojo, dirigir→dirijo. These preserve sound, not truly irregular.',
    ],
    'examples' => [
        ['es' => 'Yo tengo dos hermanos.', 'en' => 'I have two siblings. (go verb)'],
        ['es' => 'Ella puede cantar bien.', 'en' => 'She can sing well. (o→ue)'],
        ['es' => 'Yo conozco a tu hermana.', 'en' => 'I know your sister. (-zco)'],
        ['es' => 'Pienso que es verdad.', 'en' => 'I think it\'s true. (e→ie)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo teno.', 'right' => 'Yo tengo.', 'why' => 'Irregular yo = tengo.'],
        ['wrong' => 'Ella pode.', 'right' => 'Ella puede.', 'why' => 'o→ue stem change.'],
    ],
    'mnemonics' => [
        'en' => '"Boot verbs" form a boot shape in charts — nosotros keeps the original stem.',
    ],
],

// 32. GUSTAR AND SIMILAR
[
    'topic' => 'gustar',
    'keywords' => ['gustar','gusta','gustan','me gusta','like','love','encantar','interesar','molestar','parecer','importar','faltar','doler','fascinar'],
    'cefr' => 'A2',
    'title_es' => 'Gustar y verbos similares',
    'title_en' => 'Gustar (to like) and similar verbs',
    'explanation' => [
        'A1' => '"Me gusta" = I like (lit. "it pleases me"). GUSTA + singular/verb: "Me gusta el café. Me gusta bailar." GUSTAN + plural: "Me gustan los gatos."',
        'A2' => 'All pronouns: me (I), te (you), le (he/she/you-formal), nos (we), les (they). Clarify LE: "A ella le gusta." Similar verbs: encantar (love), interesar (interest), molestar (bother), importar (matter), doler (hurt), faltar (lack), parecer (seem).',
        'B1' => 'Emphasis: "A MÍ me gusta, pero A TI no te gusta." With people: "Me gusta María." Past: "Me gustó" (preterite), "Me gustaba" (imperfect). Conditional: "Me gustaría viajar" (I would like to travel).',
    ],
    'examples' => [
        ['es' => 'Me gusta la música.', 'en' => 'I like music.'],
        ['es' => 'Nos gustan las películas.', 'en' => 'We like movies.'],
        ['es' => 'A ella le encanta bailar.', 'en' => 'She loves to dance.'],
        ['es' => 'Me duele la cabeza.', 'en' => 'My head hurts. (lit. the head hurts to me)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo gusto la música.', 'right' => 'Me gusta la música.', 'why' => 'The thing liked is the subject. You are the indirect object.'],
        ['wrong' => 'Me gusto los libros.', 'right' => 'Me gustan los libros.', 'why' => 'Plural subject = gustan.'],
    ],
    'mnemonics' => [
        'en' => 'Flip it: "I like coffee" → "Coffee pleases me" → "Me gusta el café." The liked thing is the SUBJECT.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/gustar',
],

// ══════════════════════════════════════════════
// B1 ADVANCED (dest23-28): Present perfect, passive, connectors, subjunctive basics, relative clauses
// ══════════════════════════════════════════════

// 33. PRESENT PERFECT
[
    'topic' => 'present_perfect',
    'keywords' => ['present perfect','pretérito perfecto','he','has','ha','hemos','han','haber','participle','participio','he comido','he ido','ya','todavía','nunca'],
    'cefr' => 'B1',
    'title_es' => 'Pretérito perfecto (he + participio)',
    'title_en' => 'Present perfect (have done)',
    'explanation' => [
        'B1' => 'HE/HAS/HA/HEMOS/HAN + past participle. -AR→-ado: hablado. -ER/-IR→-ido: comido, vivido. "He comido" (I have eaten). "¿Has visto esa película?" (Have you seen that movie?). Irregular participles: hecho (hacer), dicho (decir), escrito (escribir), visto (ver), puesto (poner), vuelto (volver), abierto (abrir), muerto (morir), roto (romper), cubierto (cubrir).',
        'B2' => 'In Spain = recent past: "Hoy he comido paella." In Latin America, preterite preferred: "Hoy comí paella." Never separate haber from participle: "He siempre comido" is WRONG → "Siempre he comido." Pronoun goes before haber: "Lo he visto" (I have seen it).',
    ],
    'examples' => [
        ['es' => 'He visitado Colombia tres veces.', 'en' => 'I have visited Colombia three times.'],
        ['es' => '¿Has hecho la tarea?', 'en' => 'Have you done the homework?'],
        ['es' => 'Nunca he visto nieve.', 'en' => 'I have never seen snow.'],
        ['es' => 'Ya hemos comido.', 'en' => 'We have already eaten.'],
    ],
    'common_errors' => [
        ['wrong' => 'He escribido.', 'right' => 'He escrito.', 'why' => 'Irregular participle: escribir → escrito.'],
        ['wrong' => 'He siempre querido ir.', 'right' => 'Siempre he querido ir.', 'why' => 'Nothing between haber and participle (except pronouns).'],
    ],
    'mnemonics' => [
        'en' => 'Irregular participles: "He DiScoVeRed A MuRdeR on PuRpose" — Dicho, eSCrito, Visto, Roto, Abierto, Muerto, Puesto.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/haber',
],

// 34. PASSIVE VOICE
[
    'topic' => 'passive_voice',
    'keywords' => ['passive','pasiva','fue','fue construido','ser + participle','se pasivo','passive voice','was built','was written','by','por'],
    'cefr' => 'B1',
    'title_es' => 'La voz pasiva',
    'title_en' => 'Passive voice',
    'explanation' => [
        'B1' => 'SER + past participle (+ por): "El libro fue escrito por García Márquez." Participle agrees with subject: "La casa fue construida en 1900." "Las cartas fueron enviadas." More common in Spanish: passive SE: "Se habla español" (Spanish is spoken). "Se venden casas" (Houses are sold/for sale).',
        'B2' => 'SER passive = emphasizes action/agent. ESTAR + participle = resulting state: "La puerta fue abierta por el viento" (action) vs "La puerta está abierta" (result). Pasiva refleja (se + verb) is preferred in speech. "Se construyó el puente en 2010." Agent rarely expressed with se-passive.',
    ],
    'examples' => [
        ['es' => 'El puente fue construido en 1920.', 'en' => 'The bridge was built in 1920.'],
        ['es' => 'Se habla español aquí.', 'en' => 'Spanish is spoken here.'],
        ['es' => 'Las ventanas fueron rotas por la tormenta.', 'en' => 'The windows were broken by the storm.'],
        ['es' => 'Se venden apartamentos.', 'en' => 'Apartments for sale. (passive se)'],
    ],
    'common_errors' => [
        ['wrong' => 'El libro fue escribido.', 'right' => 'El libro fue escrito.', 'why' => 'Irregular participle: escribir → escrito.'],
        ['wrong' => 'Se hablan español.', 'right' => 'Se habla español.', 'why' => '"Español" is singular, so verb is singular.'],
    ],
    'mnemonics' => [
        'en' => 'SER + participle = action happened. ESTAR + participle = state remains. SE + verb = most natural in speech.',
    ],
],

// 35. CONNECTORS (cause/consequence)
[
    'topic' => 'connectors_cause',
    'keywords' => ['connector','conector','porque','por eso','ya que','como','así que','por lo tanto','therefore','because','since','consequently','linking words','causa','consecuencia'],
    'cefr' => 'B1',
    'title_es' => 'Conectores de causa y consecuencia',
    'title_en' => 'Cause and consequence connectors',
    'explanation' => [
        'B1' => 'CAUSE: porque (because), ya que / puesto que / dado que (since/given that), como (since — at start of sentence), gracias a (thanks to), debido a (due to). CONSEQUENCE: por eso (therefore), así que (so), por lo tanto (therefore), de modo que / de manera que (so that), en consecuencia (consequently).',
        'B2' => 'Register: "porque" = universal. "ya que / puesto que" = formal. "como" = only at sentence start: "Como llovía, me quedé en casa." Subtle difference: "por eso" (for that reason — specific), "por lo tanto" (therefore — formal/logical), "así que" (so — conversational).',
    ],
    'examples' => [
        ['es' => 'No fui porque estaba enfermo.', 'en' => 'I didn\'t go because I was sick.'],
        ['es' => 'Llovía mucho, así que me quedé en casa.', 'en' => 'It was raining a lot, so I stayed home.'],
        ['es' => 'Como no tenía dinero, no compré nada.', 'en' => 'Since I had no money, I didn\'t buy anything.'],
        ['es' => 'Estudié mucho, por eso aprobé.', 'en' => 'I studied a lot, that\'s why I passed.'],
    ],
    'common_errors' => [
        ['wrong' => 'Porque llovía, me quedé.', 'right' => 'Como llovía, me quedé.', 'why' => 'At sentence start, use "como" (since), not "porque."'],
        ['wrong' => 'Por que no fui.', 'right' => 'Porque no fui. / ¿Por qué no fui?', 'why' => '"Porque" (because) = one word. "¿Por qué?" (why?) = two words + accent.'],
    ],
    'mnemonics' => [
        'en' => '¿Por qué? (why? — question). Porque (because — answer). Por que (for which — rare). Porqué (the reason — noun).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/porque',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/porque-por-que-porqu%C3%A9-por-qu%C3%A9/',
],

// 36. SUBJUNCTIVE BASICS
[
    'topic' => 'subjunctive_basics',
    'keywords' => ['subjunctive','subjuntivo','quiero que','espero que','ojalá','es importante que','wish','hope','want','subjunctive formation','present subjunctive'],
    'cefr' => 'B1',
    'title_es' => 'El subjuntivo (introducción)',
    'title_en' => 'Subjunctive mood (basics)',
    'explanation' => [
        'B1' => 'Subjunctive = verbs after expressions of wish, emotion, doubt, or influence. Formation: start from YO present, drop -o, add opposite endings. -AR→-e: hable, hables, hable, hablemos, hablen. -ER/-IR→-a: coma, comas, coma, comamos, coman. Triggers: quiero que, espero que, es importante que, ojalá. "Quiero que vengas" (I want you to come).',
        'B2' => 'WEIRDO: Wishes (querer, desear, esperar), Emotions (me alegra que, es triste que), Impersonal expressions (es necesario, es posible), Recommendations (recomendar, sugerir, aconsejar), Doubt/Denial (dudar, no creer, negar), Ojalá. Irregulars: sea (ser), vaya (ir), haya (haber), sepa (saber), dé (dar), esté (estar).',
    ],
    'examples' => [
        ['es' => 'Quiero que estudies más.', 'en' => 'I want you to study more.'],
        ['es' => 'Es importante que practiques.', 'en' => 'It\'s important that you practice.'],
        ['es' => 'Ojalá llueva mañana.', 'en' => 'I hope it rains tomorrow.'],
        ['es' => 'Espero que estés bien.', 'en' => 'I hope you are well.'],
    ],
    'common_errors' => [
        ['wrong' => 'Quiero que vienes.', 'right' => 'Quiero que vengas.', 'why' => 'After "quiero que" use subjunctive.'],
        ['wrong' => 'Es importante que estudias.', 'right' => 'Es importante que estudies.', 'why' => 'Impersonal expressions + que = subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'WEIRDO: Wishes, Emotions, Impersonal, Recommendations, Doubt, Ojalá. Formation: yo present → drop -o → opposite endings.',
    ],
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/subjuntivo/',
],

// 37. RELATIVE CLAUSES
[
    'topic' => 'relative_clauses',
    'keywords' => ['relative clause','cláusula relativa','que','quien','donde','el que','la que','el cual','lo que','which','who','where','that','whose','cuyo'],
    'cefr' => 'B1',
    'title_es' => 'Cláusulas relativas (que, quien, donde)',
    'title_en' => 'Relative clauses',
    'explanation' => [
        'B1' => 'QUE = that/which/who (most common): "El libro que leí es bueno." QUIEN/QUIENES = who (for people, after prepositions): "La persona con quien hablé." DONDE = where: "La ciudad donde nací." LO QUE = what (the thing that): "Lo que dices es verdad."',
        'B2' => 'EL/LA/LOS/LAS QUE = the one(s) who/that: "Los que estudian aprenden." After prepositions, use article+que: "La casa en la que vivo." EL/LA CUAL (formal written): "El tema sobre el cual hablamos." CUYO/A/OS/AS = whose (agrees with possessed noun): "El autor cuyo libro leí." Subjunctive in relative clauses when antecedent is unknown: "Busco a alguien que hable francés."',
    ],
    'examples' => [
        ['es' => 'El chico que vive aquí es mi amigo.', 'en' => 'The boy who lives here is my friend.'],
        ['es' => 'La ciudad donde nací es pequeña.', 'en' => 'The city where I was born is small.'],
        ['es' => 'Lo que necesitas es descansar.', 'en' => 'What you need is to rest.'],
        ['es' => 'La persona con quien trabajo es amable.', 'en' => 'The person with whom I work is kind.'],
    ],
    'common_errors' => [
        ['wrong' => 'La persona que hablé con.', 'right' => 'La persona con quien hablé.', 'why' => 'Preposition goes before "quien," not at the end.'],
        ['wrong' => 'El libro quien leí.', 'right' => 'El libro que leí.', 'why' => '"Quien" is only for people. Use "que" for things.'],
    ],
    'mnemonics' => [
        'en' => 'QUE = everything. QUIEN = people only (after prepositions). DONDE = places. LO QUE = "the thing that."',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/que',
],

// 38. ACCENT MARKS
[
    'topic' => 'accent_marks',
    'keywords' => ['accent','acento','tilde','á','é','í','ó','ú','stress','aguda','llana','grave','esdrújula','accent rules','written accent'],
    'cefr' => 'A2',
    'title_es' => 'Reglas de acentuación',
    'title_en' => 'Accent mark rules',
    'explanation' => [
        'A1' => 'Accents show stress and distinguish words: sí (yes) vs si (if), él (he) vs el (the), tú (you) vs tu (your). Question words always have accents: qué, quién, dónde, cuándo, cómo.',
        'A2' => 'Rules: words ending in vowel/n/s → stress 2nd-to-last (casa, joven). Words ending in other consonants → stress last (hablar, ciudad). If the word breaks these rules, write an accent: café, canción, música.',
        'B1' => 'Three types: AGUDAS (last syllable, tilde if ending vowel/n/s: café, canción). LLANAS (2nd-to-last, tilde if NOT ending vowel/n/s: árbol, lápiz). ESDRÚJULAS (3rd-to-last, ALWAYS tilde: música, teléfono). Diacritical: este/éste, aun/aún, mas/más, solo/sólo.',
    ],
    'examples' => [
        ['es' => '¿Cómo estás?', 'en' => 'How are you? (cómo = question word)'],
        ['es' => 'Él estudia música clásica.', 'en' => 'He studies classical music.'],
        ['es' => 'La canción es de mi país.', 'en' => 'The song is from my country.'],
    ],
    'common_errors' => [
        ['wrong' => 'Tu eres estudiante.', 'right' => 'Tú eres estudiante.', 'why' => 'tú = you (pronoun), tu = your (possessive).'],
        ['wrong' => 'El come mucho.', 'right' => 'Él come mucho.', 'why' => 'él = he, el = the.'],
    ],
    'mnemonics' => [
        'en' => 'All esdrújulas ALWAYS have accents. No exceptions: pájaro, teléfono, América.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/tilde',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/tilde/',
],

// ══════════════════════════════════════════════
// B2 BASIC (dest29-33): Subjunctive emotions/doubt, conditional, literary, si-clauses
// ══════════════════════════════════════════════

// 39. SUBJUNCTIVE WITH EMOTIONS
[
    'topic' => 'subjunctive_emotions',
    'keywords' => ['subjunctive emotion','me alegra que','es triste que','me sorprende que','siento que','lamento que','me molesta que','emotion subjunctive','feelings subjunctive'],
    'cefr' => 'B2',
    'title_es' => 'Subjuntivo con emociones',
    'title_en' => 'Subjunctive with emotions',
    'explanation' => [
        'B1' => 'Emotions trigger subjunctive in the subordinate clause: "Me alegra que estés aquí." "Es triste que no pueda venir." "Me sorprende que hables tan bien." The subject of each clause must be different; if same subject, use infinitive: "Me alegra estar aquí" (not "me alegra que yo esté").',
        'B2' => 'Common triggers: alegrarse de que, sentir que, lamentar que, temer que, molestar que, sorprender que, encantar que, es una lástima que, es increíble que. With indicative, some change meaning: "Siento que estás triste" (I sense you\'re sad — perception) vs "Siento que estés triste" (I\'m sorry you\'re sad — emotion).',
    ],
    'examples' => [
        ['es' => 'Me alegra que vengas a la fiesta.', 'en' => 'I\'m glad you\'re coming to the party.'],
        ['es' => 'Es triste que no podamos ir.', 'en' => 'It\'s sad that we can\'t go.'],
        ['es' => 'Me sorprende que hable tan bien.', 'en' => 'It surprises me that he speaks so well.'],
        ['es' => 'Siento que estés enfermo.', 'en' => 'I\'m sorry you\'re sick.'],
    ],
    'common_errors' => [
        ['wrong' => 'Me alegra que vienes.', 'right' => 'Me alegra que vengas.', 'why' => 'Emotion + que = subjunctive.'],
        ['wrong' => 'Me alegra que yo esté aquí.', 'right' => 'Me alegra estar aquí.', 'why' => 'Same subject = infinitive, not subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'Emotions = subjunctive. BUT same subject = infinitive. "Me alegra estar" vs "Me alegra que estés."',
    ],
],

// 40. CONDITIONAL TENSE
[
    'topic' => 'conditional',
    'keywords' => ['conditional','condicional','would','ría','haría','iría','podría','querría','sería','tendría','vendría','sabría','diría','conditional tense'],
    'cefr' => 'B2',
    'title_es' => 'El condicional',
    'title_en' => 'Conditional tense',
    'explanation' => [
        'A2' => 'Expresses "would." Add -ía, -ías, -ía, -íamos, -ían to the infinitive. Hablaría (I would speak). Used for polite requests: "¿Podrías ayudarme?"',
        'B1' => 'Irregulars (same stems as future): tendría, vendría, pondría, saldría, diría, haría, podría, sabría, querría, habría, valdría, cabría. Used in si-clauses: "Si tuviera tiempo, viajaría."',
        'B2' => 'Compound conditional: habría + participle = "would have": "Habría ido si me hubieras invitado." Probability in past: "¿Quién sería?" (Who could it have been?). Reported speech: "Dijo que vendría" (He said he would come). Polite softening: "¿Te importaría cerrar la ventana?"',
    ],
    'examples' => [
        ['es' => 'Me gustaría viajar a Colombia.', 'en' => 'I would like to travel to Colombia.'],
        ['es' => 'Si pudiera, estudiaría todo el día.', 'en' => 'If I could, I would study all day.'],
        ['es' => '¿Podrías repetir, por favor?', 'en' => 'Could you repeat, please?'],
        ['es' => 'Habría venido, pero estaba enfermo.', 'en' => 'I would have come, but I was sick.'],
    ],
    'common_errors' => [
        ['wrong' => 'Si tendría dinero, viajaría.', 'right' => 'Si tuviera dinero, viajaría.', 'why' => 'After "si," use imperfect subjunctive, never conditional.'],
        ['wrong' => 'Yo haré si pudiera.', 'right' => 'Yo haría si pudiera.', 'why' => 'Si + imp. subjunctive → conditional (not future).'],
    ],
    'mnemonics' => [
        'en' => 'Never conditional after SI. Pattern: "Si [imperfect subjunctive], [conditional]." Conditional = infinitive + imperfect endings.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/condicional',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/condicional/',
],

// 41. SUBJUNCTIVE WITH DOUBT
[
    'topic' => 'subjunctive_doubt',
    'keywords' => ['doubt','duda','dudo que','no creo que','es posible que','tal vez','quizás','quizá','doubt subjunctive','uncertainty','maybe','perhaps','negar que'],
    'cefr' => 'B2',
    'title_es' => 'Subjuntivo con duda y negación',
    'title_en' => 'Subjunctive with doubt and denial',
    'explanation' => [
        'B1' => 'Doubt/denial trigger subjunctive: "Dudo que venga." "No creo que sea verdad." "Es posible que llueva." "Niego que sea cierto." But certainty uses indicative: "Creo que es verdad." "Es seguro que viene."',
        'B2' => 'Tal vez / quizás / quizá: subjunctive for more doubt, indicative for more certainty. "Tal vez venga" (maybe he\'ll come — uncertain) vs "Tal vez viene" (maybe he\'s coming — I think so). "No es que sea difícil, es que..." (It\'s not that it\'s hard, it\'s that...) — negated "es que" takes subjunctive. "A lo mejor" always takes indicative: "A lo mejor viene."',
    ],
    'examples' => [
        ['es' => 'Dudo que llegue a tiempo.', 'en' => 'I doubt he\'ll arrive on time.'],
        ['es' => 'No creo que sea buena idea.', 'en' => 'I don\'t think it\'s a good idea.'],
        ['es' => 'Es posible que nieve mañana.', 'en' => 'It\'s possible it will snow tomorrow.'],
        ['es' => 'Quizás tenga razón.', 'en' => 'Maybe you\'re right.'],
    ],
    'common_errors' => [
        ['wrong' => 'Dudo que viene.', 'right' => 'Dudo que venga.', 'why' => 'Doubt = subjunctive.'],
        ['wrong' => 'No creo que es verdad.', 'right' => 'No creo que sea verdad.', 'why' => '"No creo que" = denial → subjunctive. But "Creo que es verdad" = belief → indicative.'],
    ],
    'mnemonics' => [
        'en' => 'CREO QUE + indicative (I believe). NO CREO QUE + subjunctive (I don\'t believe). Negation flips the mood.',
    ],
],

// 42. SI-CLAUSES (if-clauses)
[
    'topic' => 'si_clauses',
    'keywords' => ['si','if','si clause','conditional','if clause','si tuviera','si pudiera','si fuera','hypothetical','contrary to fact','si hubiera','unreal condition'],
    'cefr' => 'B2',
    'title_es' => 'Cláusulas con si (condicionales)',
    'title_en' => 'Si-clauses (if-clauses)',
    'explanation' => [
        'A2' => 'Real conditions (likely): SI + present, + present/future. "Si llueve, no voy." "Si estudias, aprobarás."',
        'B1' => 'Unlikely/hypothetical: SI + imperfect subjunctive, + conditional. "Si tuviera dinero, viajaría." "Si fuera tú, estudiaría más." Never use conditional or present subjunctive after SI.',
        'B2' => 'Past contrary-to-fact: SI + pluperfect subjunctive, + conditional perfect. "Si hubiera estudiado, habría aprobado." Mixed: "Si hubiera nacido en Colombia, hablaría español." COMO SI + imperfect/pluperfect subjunctive: "Habla como si fuera nativo." "Actuó como si no hubiera pasado nada."',
    ],
    'examples' => [
        ['es' => 'Si llueve, me quedo en casa.', 'en' => 'If it rains, I\'ll stay home. (real)'],
        ['es' => 'Si tuviera tiempo, viajaría más.', 'en' => 'If I had time, I would travel more. (hypothetical)'],
        ['es' => 'Si hubiera estudiado, habría aprobado.', 'en' => 'If I had studied, I would have passed. (past unreal)'],
        ['es' => 'Habla como si fuera colombiano.', 'en' => 'He speaks as if he were Colombian.'],
    ],
    'common_errors' => [
        ['wrong' => 'Si tendría dinero.', 'right' => 'Si tuviera dinero.', 'why' => 'NEVER conditional after "si." Use imperfect subjunctive.'],
        ['wrong' => 'Si habría estudiado.', 'right' => 'Si hubiera estudiado.', 'why' => 'NEVER conditional after "si." Use pluperfect subjunctive.'],
        ['wrong' => 'Como si es nativo.', 'right' => 'Como si fuera nativo.', 'why' => '"Como si" always takes subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'SI never touches conditional. Real: Si + present → future. Hypothetical: Si + imp. subj. → conditional. Past: Si + pluperf. subj. → cond. perfect.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/si',
],

// 43. COMMANDS / IMPERATIVE
[
    'topic' => 'imperative',
    'keywords' => ['imperative','imperativo','command','mandato','tú command','usted command','negative command','di','ven','haz','pon','sal','ten','sé','dime','no hagas'],
    'cefr' => 'A2',
    'title_es' => 'El imperativo (mandatos)',
    'title_en' => 'Commands / Imperative',
    'explanation' => [
        'A2' => 'Affirmative TÚ commands = él/ella present form: habla, come, escribe. 8 irregulars: di (decir), haz (hacer), ve (ir), pon (poner), sal (salir), sé (ser), ten (tener), ven (venir).',
        'B1' => 'Negative TÚ = subjunctive: no hables, no comas, no escribas. USTED commands = subjunctive (both aff. and neg.): hable/no hable. Pronouns: attached to affirmative (dime), before negative (no me digas).',
        'B2' => 'USTEDES = subjunctive: hablen, coman. NOSOTROS = subjunctive: hablemos (let\'s speak). Reflexive nosotros drops -s: sentémonos (not *sentemosnos). VAMOS (let\'s go) but NO VAYAMOS (let\'s not go).',
    ],
    'examples' => [
        ['es' => '¡Habla más despacio!', 'en' => 'Speak more slowly!'],
        ['es' => 'No comas tan rápido.', 'en' => 'Don\'t eat so fast.'],
        ['es' => 'Dígame, ¿en qué puedo ayudarle?', 'en' => 'Tell me, how can I help you? (usted)'],
        ['es' => '¡Ven aquí!', 'en' => 'Come here! (irregular tú)'],
    ],
    'common_errors' => [
        ['wrong' => 'No habla.', 'right' => 'No hables.', 'why' => 'Negative tú command = subjunctive.'],
        ['wrong' => 'Pone la mesa.', 'right' => 'Pon la mesa.', 'why' => 'Irregular tú command.'],
    ],
    'mnemonics' => [
        'en' => '8 irregular tú commands: Ven, Di, Sal, Haz, Ten, Ve, Pon, Sé. "Vin Diesel Has Ten Vets Pending Sérvice."',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/imperativo',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/imperativo/',
],

// ══════════════════════════════════════════════
// B2 ADVANCED (dest34-38): Reported speech, register, pluperfect, future perfect, tense sequence
// ══════════════════════════════════════════════

// 44. REPORTED SPEECH (advanced)
[
    'topic' => 'reported_speech_advanced',
    'keywords' => ['reported speech','estilo indirecto','dijo que había','told','said','reporting verbs','tense backshift','sequence of tenses','concordancia temporal'],
    'cefr' => 'B2',
    'title_es' => 'Estilo indirecto avanzado',
    'title_en' => 'Reported speech (advanced)',
    'explanation' => [
        'B1' => 'Basic backshift: present→imperfect. "Dice: \'Estoy cansada\'" → "Dijo que estaba cansada."',
        'B2' => 'Full sequence: present→imperfect, preterite→pluperfect, future→conditional, present perfect→pluperfect, imperative→imperfect subjunctive. "Come" → "dijo que comiera." Time/place shifts: hoy→ese día, aquí→allí, mañana→al día siguiente, ayer→el día anterior. Commands: "Ven aquí" → "Le dijo que fuera allí."',
    ],
    'examples' => [
        ['es' => '"He comido" → Dijo que había comido.', 'en' => '"I have eaten" → He said he had eaten.'],
        ['es' => '"Vendré mañana" → Dijo que vendría al día siguiente.', 'en' => '"I will come tomorrow" → He said he would come the next day.'],
        ['es' => '"Estudia más" → Le dijo que estudiara más.', 'en' => '"Study more" → She told him to study more.'],
    ],
    'common_errors' => [
        ['wrong' => 'Dijo que ha comido.', 'right' => 'Dijo que había comido.', 'why' => 'Present perfect → pluperfect in reported speech.'],
        ['wrong' => 'Dijo que vendrá mañana.', 'right' => 'Dijo que vendría al día siguiente.', 'why' => 'Future → conditional; time references shift.'],
    ],
    'mnemonics' => [
        'en' => 'Each tense goes "one step back": Present→Imperfect, Perfect→Pluperfect, Future→Conditional.',
    ],
],

// 45. PLUPERFECT
[
    'topic' => 'pluperfect',
    'keywords' => ['pluperfect','pluscuamperfecto','había','habías','había comido','había ido','had done','past perfect','before another past','antes de'],
    'cefr' => 'B2',
    'title_es' => 'El pluscuamperfecto (había + participio)',
    'title_en' => 'Pluperfect (had done)',
    'explanation' => [
        'B2' => 'HABÍA/HABÍAS/HABÍA/HABÍAMOS/HABÍAN + past participle. Describes an action completed BEFORE another past action. "Cuando llegué, ella ya había salido" (When I arrived, she had already left). "Nunca había visto algo así" (I had never seen anything like that). Same irregular participles as present perfect: hecho, dicho, escrito, visto, puesto, vuelto, abierto, roto.',
    ],
    'examples' => [
        ['es' => 'Cuando llegué, ya habían comido.', 'en' => 'When I arrived, they had already eaten.'],
        ['es' => 'Nunca había viajado a Colombia.', 'en' => 'I had never traveled to Colombia.'],
        ['es' => 'Me dijo que había estudiado mucho.', 'en' => 'She told me she had studied a lot.'],
        ['es' => 'Ya habíamos salido cuando llamaste.', 'en' => 'We had already left when you called.'],
    ],
    'common_errors' => [
        ['wrong' => 'Cuando llegué, ya comieron.', 'right' => 'Cuando llegué, ya habían comido.', 'why' => 'Action before another past action = pluperfect.'],
        ['wrong' => 'Había escribido.', 'right' => 'Había escrito.', 'why' => 'Irregular participle: escribir → escrito.'],
    ],
    'mnemonics' => [
        'en' => 'Pluperfect = "the past of the past." If action A happened BEFORE action B (both in past), A is pluperfect.',
    ],
],

// 46. FUTURE TENSE
[
    'topic' => 'future_tense',
    'keywords' => ['future','futuro','will','-ré','-rás','-rá','-remos','-rán','hablaré','comeré','viviré','future tense','probability','conjecture','wonder'],
    'cefr' => 'A2',
    'title_es' => 'El futuro simple',
    'title_en' => 'Future tense',
    'explanation' => [
        'A2' => 'Add endings to the WHOLE infinitive: -é, -ás, -á, -emos, -án. HABLAR: hablaré, hablarás, hablará, hablaremos, hablarán. COMER: comeré... VIVIR: viviré... Same endings for all verb types.',
        'B1' => 'Irregulars (modified stem + same endings): tendré, vendré, pondré, saldré, diré, haré, podré, sabré, querré, habré, valdré, cabré. Future of probability: "¿Qué hora será?" (I wonder what time it is). "Será las tres" (It\'s probably three).',
        'B2' => 'Future perfect: HABRÉ + participle = "will have done." "Para las cinco, habré terminado." Future of probability in past: "Habrá salido ya" (She must have left already). Note: in speech, IR A + infinitive is much more common than simple future: "Voy a comer" vs "Comeré."',
    ],
    'examples' => [
        ['es' => 'Mañana estudiaré español.', 'en' => 'Tomorrow I will study Spanish.'],
        ['es' => '¿Vendrás a la fiesta?', 'en' => 'Will you come to the party?'],
        ['es' => '¿Dónde estará María?', 'en' => 'Where could María be? (probability)'],
        ['es' => 'Para junio habré terminado.', 'en' => 'By June I will have finished.'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo teneré.', 'right' => 'Yo tendré.', 'why' => 'Irregular stem: tener → tendr-.'],
        ['wrong' => 'Yo hablaré comer.', 'right' => 'Yo comeré. / Yo voy a comer.', 'why' => 'Future endings go on the infinitive itself.'],
    ],
    'mnemonics' => [
        'en' => 'Future = infinitive + endings (-é, -ás, -á, -emos, -án). Irregular stems: drop vowel or change (tendr-, vendr-, pondr-, saldr-).',
    ],
],

// 47. REGISTER AND PRAGMATICS
[
    'topic' => 'register_pragmatics',
    'keywords' => ['register','registro','formal','informal','tú','usted','pragmatics','pragmática','politeness','cortesía','tone','tono','social context'],
    'cefr' => 'B2',
    'title_es' => 'Registro y pragmática',
    'title_en' => 'Register and pragmatics',
    'explanation' => [
        'B1' => 'TÚ = informal (friends, family, peers, children). USTED = formal (strangers, elders, professional). In Colombia, USTED can be used between friends in certain regions. "¿Me puedes ayudar?" (informal) vs "¿Me podría ayudar?" (formal).',
        'B2' => 'Softening devices: conditional (¿Podrías...?), imperfect (Quería pedirte...), diminutives (un momentito, un favorcito). Indirectness: "¿No tendrás un bolígrafo?" (You wouldn\'t have a pen, would you?). Register shifts: academic (por consiguiente, cabe señalar), journalistic (según fuentes), colloquial (o sea, bueno, pues). In Latin America, "vos" replaces "tú" in Argentina, Uruguay, parts of Colombia/Central America.',
    ],
    'examples' => [
        ['es' => '¿Me podrías pasar la sal?', 'en' => 'Could you pass me the salt? (polite conditional)'],
        ['es' => 'Quería preguntarte algo.', 'en' => 'I wanted to ask you something. (softened with imperfect)'],
        ['es' => '¿No tendrás un lápiz?', 'en' => 'You wouldn\'t happen to have a pencil? (very polite)'],
        ['es' => 'Un momentito, por favor.', 'en' => 'Just a little moment, please. (diminutive softens)'],
    ],
    'common_errors' => [
        ['wrong' => 'Using tú with a boss/elder.', 'right' => 'Use usted unless invited to use tú.', 'why' => 'Defaulting to formal is safer; informal can offend.'],
        ['wrong' => 'Dame eso.', 'right' => '¿Me podrías dar eso, por favor?', 'why' => 'Bare imperative can sound rude; soften with conditional.'],
    ],
    'mnemonics' => [
        'en' => 'When in doubt, use USTED. You can always switch to TÚ later, but upgrading from tú to usted is awkward.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/usted',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/tuteo-voseo-ustedeo/',
],

// 48. TENSE SEQUENCE MASTERY
[
    'topic' => 'tense_sequence',
    'keywords' => ['tense sequence','concordancia temporal','sequence of tenses','verb tense agreement','when to use which tense','tense combination','narrative tenses'],
    'cefr' => 'B2',
    'title_es' => 'Concordancia de tiempos verbales',
    'title_en' => 'Tense sequence / agreement',
    'explanation' => [
        'B2' => 'Main clause in PRESENT → subordinate uses present subjunctive: "Quiero que vengas." Main clause in PAST → subordinate uses imperfect subjunctive: "Quería que vinieras." Main clause in FUTURE → subordinate typically present subjunctive: "Será necesario que estudies." Perfect tenses follow the same logic: "He pedido que venga" vs "Había pedido que viniera."',
        'C1' => 'Exceptions: if subordinate refers to a still-valid state, present subjunctive after past main is acceptable: "Me pidió que esté listo para mañana" (still future). Narrative mixing: authors freely combine tenses for effect. Historical present: "En 1810, Bolívar cruza los Andes" (present for past to add immediacy).',
    ],
    'examples' => [
        ['es' => 'Quiero que vengas. / Quería que vinieras.', 'en' => 'I want you to come. / I wanted you to come.'],
        ['es' => 'Es importante que estudies. / Era importante que estudiaras.', 'en' => 'It\'s important you study. / It was important you studied.'],
        ['es' => 'Le he dicho que venga. / Le había dicho que viniera.', 'en' => 'I\'ve told him to come. / I had told him to come.'],
    ],
    'common_errors' => [
        ['wrong' => 'Quería que vengas.', 'right' => 'Quería que vinieras.', 'why' => 'Past main verb → imperfect subjunctive.'],
        ['wrong' => 'Fue necesario que estudies.', 'right' => 'Fue necesario que estudiaras.', 'why' => 'Past main clause → past subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'Present main → present subjunctive. Past main → imperfect subjunctive. Match the timeline.',
    ],
],

// ══════════════════════════════════════════════
// C1 BASIC (dest39-43): Imperfect subjunctive, complex subordination, concessive
// ══════════════════════════════════════════════

// 49. IMPERFECT SUBJUNCTIVE
[
    'topic' => 'imperfect_subjunctive',
    'keywords' => ['imperfect subjunctive','imperfecto de subjuntivo','subjuntivo imperfecto','-ra','-se','tuviera','fuera','pudiera','hiciera','quisiera','viniera','supiera','dijera','hubiera'],
    'cefr' => 'C1',
    'title_es' => 'El imperfecto de subjuntivo',
    'title_en' => 'Imperfect subjunctive',
    'explanation' => [
        'B2' => 'Two forms (-ra / -se), both correct and interchangeable: hablara/hablase, comiera/comiese, viviera/viviese. Formed from 3rd person plural preterite: hablaron→habla-ra/-se, comieron→comie-ra/-se, dijeron→dije-ra/-se. Used after past main verbs: "Quería que hablara/hablase."',
        'C1' => 'The -RA form is more common in speech; -SE is more literary/formal. -RA can replace conditional in some cases (literary): "Quisiera un café" = "Querría un café." Used in: si-clauses (Si pudiera...), como si (como si fuera...), ojalá for unlikely wishes (Ojalá tuviera más tiempo), after past triggers (pidió que viniera). All irregulars follow from the preterite 3rd plural stem.',
    ],
    'examples' => [
        ['es' => 'Si tuviera dinero, viajaría.', 'en' => 'If I had money, I would travel.'],
        ['es' => 'Quería que me ayudara.', 'en' => 'I wanted him to help me.'],
        ['es' => 'Ojalá pudiera ir contigo.', 'en' => 'I wish I could go with you.'],
        ['es' => 'Habla como si fuera nativo.', 'en' => 'He speaks as if he were a native.'],
    ],
    'common_errors' => [
        ['wrong' => 'Si tendría dinero.', 'right' => 'Si tuviera dinero.', 'why' => 'After "si" = imperfect subjunctive, never conditional.'],
        ['wrong' => 'Quería que viene.', 'right' => 'Quería que viniera.', 'why' => 'Past main clause → imperfect subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'From preterite ellos: dijeron→dijera, tuvieron→tuviera, fueron→fuera. Drop -ron, add -ra/-se.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/subjuntivo',
],

// 50. COMPLEX SUBORDINATION
[
    'topic' => 'complex_subordination',
    'keywords' => ['subordination','subordinación','clause','cláusula','noun clause','adjective clause','adverb clause','complex sentence','embedded clause','que','subjunctive clause'],
    'cefr' => 'C1',
    'title_es' => 'Subordinación compleja',
    'title_en' => 'Complex subordination',
    'explanation' => [
        'B2' => 'Three types of subordinate clauses: NOUN (acts as subject/object): "Quiero que vengas." ADJECTIVE/RELATIVE (modifies noun): "El libro que leí." ADVERBIAL (modifies verb — time, purpose, concession): "Cuando llegues, llámame."',
        'C1' => 'Multiple levels of embedding: "Es posible que María piense que deberíamos esperar hasta que termine el proyecto." Key: each subordinate may require indicative or subjunctive based on its own trigger. Purpose: para que + subj. Condition: con tal de que + subj. Time (future): cuando/antes de que/hasta que + subj. Cause: porque + indic. vs no porque + subj. ("No es que sea difícil, sino que requiere tiempo").',
    ],
    'examples' => [
        ['es' => 'Me dijo que esperara hasta que llegara su hermano.', 'en' => 'He told me to wait until his brother arrived.'],
        ['es' => 'Estudio para que mis hijos tengan un futuro mejor.', 'en' => 'I study so that my children have a better future.'],
        ['es' => 'No es que no quiera ir, sino que no puedo.', 'en' => 'It\'s not that I don\'t want to go, but that I can\'t.'],
    ],
    'common_errors' => [
        ['wrong' => 'Estudio para que mis hijos tienen...', 'right' => 'Estudio para que mis hijos tengan...', 'why' => '"Para que" always takes subjunctive.'],
        ['wrong' => 'Antes de que llega.', 'right' => 'Antes de que llegue.', 'why' => '"Antes de que" always takes subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'Always subjunctive after: para que, antes de que, sin que, con tal de que, a menos que, hasta que (future).',
    ],
],

// 51. CONCESSIVE CLAUSES
[
    'topic' => 'concessive_clauses',
    'keywords' => ['concessive','concesiva','aunque','even though','despite','a pesar de que','por más que','si bien','aun cuando','although','however','no matter'],
    'cefr' => 'C1',
    'title_es' => 'Cláusulas concesivas (aunque + subjuntivo)',
    'title_en' => 'Concessive clauses',
    'explanation' => [
        'B2' => 'AUNQUE + indicative = stating a fact: "Aunque llueve, salgo" (Even though it IS raining, I\'m going out). AUNQUE + subjunctive = hypothetical: "Aunque llueva, saldré" (Even if it rains, I\'ll go out).',
        'C1' => 'Other concessive expressions: A PESAR DE QUE (despite the fact that — indic. or subj.), POR MÁS QUE + subj. (no matter how much: "Por más que estudie, no aprueba"), POR MUCHO QUE + subj., SI BIEN (although — formal, always indic.), AUN CUANDO + subj. (even when). "Por muy inteligente que sea, necesita estudiar" (No matter how smart he is).',
    ],
    'examples' => [
        ['es' => 'Aunque estoy cansado, voy a trabajar.', 'en' => 'Although I\'m tired, I\'m going to work. (fact)'],
        ['es' => 'Aunque llueva mañana, iremos.', 'en' => 'Even if it rains tomorrow, we\'ll go. (hypothetical)'],
        ['es' => 'Por más que lo intente, no lo consigo.', 'en' => 'No matter how much I try, I can\'t manage.'],
        ['es' => 'A pesar de que era difícil, lo logró.', 'en' => 'Despite it being difficult, she succeeded.'],
    ],
    'common_errors' => [
        ['wrong' => 'Aunque llueva, estoy mojado.', 'right' => 'Aunque llueve, estoy mojado.', 'why' => 'It IS raining (fact) = indicative. Subjunctive only for hypothetical.'],
        ['wrong' => 'Por más que intenta, no puede.', 'right' => 'Por más que intente, no puede.', 'why' => '"Por más que" takes subjunctive.'],
    ],
    'mnemonics' => [
        'en' => 'AUNQUE + indic. = "it IS true, but..." AUNQUE + subj. = "even IF it were true..."',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/aunque',
],

// 52. REGISTER ADAPTATION
[
    'topic' => 'register_adaptation',
    'keywords' => ['register','academic','colloquial','literary','formal writing','informal speech','voseo','ustedeo','adaptation','style','sociolect'],
    'cefr' => 'C1',
    'title_es' => 'Adaptación de registro',
    'title_en' => 'Register adaptation',
    'explanation' => [
        'C1' => 'Academic: nominalizations (la obtención de resultados), passive constructions (se ha demostrado que...), impersonal (cabe señalar, conviene destacar), hedging (parece indicar, podría sugerir). Colloquial: tag questions (¿no?, ¿verdad?, ¿cierto?), filler words (o sea, bueno, pues, es que...), diminutives (ahorita, tantito), intensifiers (re-bueno, súper difícil). Literary: inverted syntax, subjunctive in independent clauses (¡Viva Colombia!), archaic forms.',
        'C2' => 'Sociolectal variation: "vos" forms (Argentina/Uruguay: vos tenés, vos hablás), "ustedes" replacing "vosotros" (all of Latin America), "ustedeo" (Colombia/Costa Rica using usted for intimacy). Regional vocab: bus = autobús/camión/colectivo/guagua/bus. Adjusting register mid-conversation based on social cues.',
    ],
    'examples' => [
        ['es' => 'Cabe señalar que los resultados indican...', 'en' => 'It should be noted that the results indicate... (academic)'],
        ['es' => 'O sea, no sé, ¿me entiendes?', 'en' => 'I mean, I don\'t know, you know? (colloquial)'],
        ['es' => '¿No tendrás un momentito?', 'en' => 'You wouldn\'t have a little moment? (polite diminutive)'],
    ],
    'common_errors' => [
        ['wrong' => 'Using "pues" and "o sea" in academic writing.', 'right' => 'Use "por lo tanto" and "es decir" in academic register.', 'why' => 'Filler words belong to colloquial, not academic register.'],
        ['wrong' => 'Mixing vos and tú in the same conversation.', 'right' => 'Be consistent with one form.', 'why' => 'Switching between vos and tú sounds unnatural.'],
    ],
    'mnemonics' => [
        'en' => 'Academic = long words, passive, impersonal. Colloquial = short, direct, fillers. Literary = creative, inverted, archaic.',
    ],
],

// ══════════════════════════════════════════════
// C1 ADVANCED (dest44-48): Metalinguistic, nominalizations, academic
// ══════════════════════════════════════════════

// 53. NOMINALIZATIONS
[
    'topic' => 'nominalizations',
    'keywords' => ['nominalization','nominalización','suffix','-ción','-miento','-dad','-eza','-ancia','-encia','abstract noun','noun formation','word formation'],
    'cefr' => 'C1',
    'title_es' => 'Nominalizaciones',
    'title_en' => 'Nominalizations',
    'explanation' => [
        'C1' => 'Turning verbs/adjectives into nouns. Verb→noun: -CIÓN (comunicar→comunicación, informar→información), -MIENTO (conocer→conocimiento, descubrir→descubrimiento), -NCIA/-ENCIA (existir→existencia, tolerr→tolerancia). Adj→noun: -DAD (feliz→felicidad, posible→posibilidad), -EZA (bello→belleza, triste→tristeza), -URA (alto→altura, dulce→dulzura).',
        'C2' => 'Academic writing relies heavily on nominalization: "La obtención de resultados" instead of "Cuando obtuvimos resultados." Nominalization compresses information and creates formal tone. Over-nominalization makes prose dense and hard to read — balance is key. Some verbs have multiple nominalizations: conocer→conocimiento/conocencia, vivir→vida/vivencia/viveza.',
    ],
    'examples' => [
        ['es' => 'La comunicación es fundamental.', 'en' => 'Communication is fundamental. (comunicar → comunicación)'],
        ['es' => 'El descubrimiento cambió la historia.', 'en' => 'The discovery changed history. (descubrir → descubrimiento)'],
        ['es' => 'La belleza de la naturaleza.', 'en' => 'The beauty of nature. (bello → belleza)'],
    ],
    'common_errors' => [
        ['wrong' => 'La comunicamiento.', 'right' => 'La comunicación.', 'why' => 'Each verb has a specific nominalization suffix; they\'re not interchangeable.'],
        ['wrong' => 'El felicidad.', 'right' => 'La felicidad.', 'why' => 'Words ending in -dad are feminine.'],
    ],
    'mnemonics' => [
        'en' => '-CIÓN = process/result (always feminine). -MIENTO = process (always masculine). -DAD = quality (always feminine).',
    ],
],

// 54. METALINGUISTIC ANALYSIS
[
    'topic' => 'metalinguistic',
    'keywords' => ['metalinguistic','metalingüístico','language about language','grammar terminology','syntax','morphology','semantics','pragmatics','discourse analysis','text analysis'],
    'cefr' => 'C1',
    'title_es' => 'Análisis metalingüístico',
    'title_en' => 'Metalinguistic analysis',
    'explanation' => [
        'C1' => 'Talking ABOUT language in Spanish. Key terms: sustantivo (noun), verbo (verb), adjetivo (adjective), adverbio (adverb), pronombre (pronoun), preposición (preposition), conjunción (conjunction), oración (sentence), sujeto (subject), predicado (predicate), complemento directo/indirecto (direct/indirect object), concordancia (agreement), modo (mood: indicativo, subjuntivo, imperativo), tiempo (tense), aspecto (aspect: perfectivo, imperfectivo).',
        'C2' => 'Discourse functions: tema/rema (topic/comment), foco (focus), topicalización (fronting for emphasis). Pragmatic concepts: acto de habla (speech act), implicatura (implicature), presuposición (presupposition), cortesía (politeness theory). Ability to explain WHY a grammar rule works, not just WHAT it is.',
    ],
    'examples' => [
        ['es' => 'En esta oración, el sujeto está elidido.', 'en' => 'In this sentence, the subject is elided.'],
        ['es' => 'El verbo está en modo subjuntivo.', 'en' => 'The verb is in the subjunctive mood.'],
        ['es' => 'Hay una discordancia de número.', 'en' => 'There is a number disagreement.'],
    ],
    'common_errors' => [
        ['wrong' => 'Confusing "oración" (sentence) with "frase" (phrase).', 'right' => 'An "oración" has a verb; a "frase" may or may not.', 'why' => 'Technical distinction matters in linguistic analysis.'],
        ['wrong' => 'Saying "adjectivo" instead of "adjetivo."', 'right' => 'Adjetivo (no c).', 'why' => 'Common spelling error even among natives.'],
    ],
    'mnemonics' => [
        'en' => 'Spanish grammar terms often mirror English with Spanish phonology: subject=sujeto, verb=verbo, adjective=adjetivo.',
    ],
],

// 55. ACADEMIC REGISTER
[
    'topic' => 'academic_register',
    'keywords' => ['academic','académico','essay','ensayo','formal writing','academic writing','thesis','argumentative','cabe señalar','conviene destacar','se ha demostrado','en primer lugar'],
    'cefr' => 'C1',
    'title_es' => 'Registro académico',
    'title_en' => 'Academic register',
    'explanation' => [
        'C1' => 'Structure markers: En primer lugar / En segundo lugar (firstly, secondly), Por un lado... por otro lado (on one hand... on the other), En conclusión / Para concluir, En resumen / En síntesis. Hedging: parece indicar (seems to indicate), podría sugerir (could suggest), cabe la posibilidad de que + subj. Impersonal constructions: Se ha demostrado que... (It has been demonstrated that), Conviene señalar que... (It is worth noting that), Cabe destacar que... (It should be highlighted that).',
        'C2' => 'Citation: Según [author] (According to), Como señala [author], [Author] sostiene/argumenta/propone que... Counterargument: Sin embargo (however), No obstante (nevertheless), A pesar de ello (despite this), Si bien es cierto que... (While it is true that...). Academic passive: "Los datos fueron analizados" vs reflexive "Se analizaron los datos."',
    ],
    'examples' => [
        ['es' => 'Cabe señalar que los datos indican una tendencia positiva.', 'en' => 'It should be noted that the data indicate a positive trend.'],
        ['es' => 'En primer lugar, se analizarán los factores económicos.', 'en' => 'Firstly, the economic factors will be analyzed.'],
        ['es' => 'Según García Márquez, la soledad es un tema universal.', 'en' => 'According to García Márquez, solitude is a universal theme.'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo pienso que está mal.', 'right' => 'Se podría argumentar que existe un problema.', 'why' => 'Academic writing avoids first person and strong personal opinions.'],
        ['wrong' => 'Está buenísimo este resultado.', 'right' => 'Los resultados son altamente satisfactorios.', 'why' => 'Colloquial expressions are inappropriate in academic writing.'],
    ],
    'mnemonics' => [
        'en' => 'Academic Spanish = impersonal, hedged, structured. Avoid yo/tú. Use se + verb. Organize with "En primer lugar..."',
    ],
],

// ══════════════════════════════════════════════
// C2 BASIC (dest49-53): Register mastery, literary, rhetoric, sociolinguistic
// ══════════════════════════════════════════════

// 56. LITERARY REGISTER
[
    'topic' => 'literary_register',
    'keywords' => ['literary','literario','literature','narrative','metaphor','metáfora','imagery','simbolismo','literary devices','figures of speech','estilo literario'],
    'cefr' => 'C2',
    'title_es' => 'Registro literario',
    'title_en' => 'Literary register',
    'explanation' => [
        'C1' => 'Literary Spanish uses: inverted word order for emphasis ("Grande fue su sorpresa"), subjunctive in independent clauses for wishes ("¡Viva la libertad!"), imperfect subjunctive -ra as conditional/past ("Aquella que fuera su esposa" = who had been his wife).',
        'C2' => 'Figures of speech: metáfora (metaphor), símil (simile: "como un río"), hipérbole (hyperbole), personificación (personification), sinécdoque (synecdoche), metonimia (metonymy). Narrative techniques: flujo de conciencia (stream of consciousness), narrador omnisciente/testigo, estilo indirecto libre (free indirect style — García Márquez\'s signature). Poetic license: altered syntax, archaic vocabulary, neologisms.',
    ],
    'examples' => [
        ['es' => 'Muchos años después, frente al pelotón de fusilamiento...', 'en' => 'Many years later, facing the firing squad... (García Márquez opening)'],
        ['es' => 'Grande fue su asombro al ver la ciudad.', 'en' => 'Great was his astonishment upon seeing the city. (inverted order)'],
        ['es' => '¡Que viva el pueblo!', 'en' => 'Long live the people! (independent subjunctive)'],
    ],
    'common_errors' => [
        ['wrong' => 'Using literary inversion in casual speech.', 'right' => 'Reserve inverted structures for writing.', 'why' => '"Grande fue su sorpresa" sounds stilted in conversation.'],
        ['wrong' => 'Overusing metaphors in academic writing.', 'right' => 'Literary devices belong in literary texts, not essays.', 'why' => 'Register mismatch.'],
    ],
    'mnemonics' => [
        'en' => 'Literary = freedom with syntax, imagery, and mood. Academic = precision and impersonality. Don\'t mix them.',
    ],
],

// 57. RHETORIC
[
    'topic' => 'rhetoric',
    'keywords' => ['rhetoric','retórica','persuasion','argumentación','speech','discurso','debate','ethos','pathos','logos','rhetorical question','anáfora','repetition'],
    'cefr' => 'C2',
    'title_es' => 'Retórica y argumentación',
    'title_en' => 'Rhetoric and argumentation',
    'explanation' => [
        'C1' => 'Argumentative structure: tesis (thesis), argumento (argument), evidencia (evidence), contraargumento (counterargument), conclusión. Connectors: por lo tanto (therefore), de ahí que + subj. (hence), en consecuencia (consequently). Persuasive techniques: rhetorical questions ("¿Acaso no es evidente?"), appeals to authority, emotional appeals.',
        'C2' => 'Classical rhetoric in Spanish: anáfora (repetition at start: "No pasarán. No nos vencerán. No nos callarán."), antítesis (opposing ideas: "No es la muerte lo que temo, sino el olvido"), paradoja (paradox), ironía (irony — saying the opposite). Discourse markers for argumentation: ahora bien (now then), dicho esto (having said this), en efecto (indeed), de hecho (in fact).',
    ],
    'examples' => [
        ['es' => '¿Acaso no merecemos algo mejor?', 'en' => 'Don\'t we deserve something better? (rhetorical question)'],
        ['es' => 'No es pobreza lo que nos define, sino nuestra respuesta ante ella.', 'en' => 'It is not poverty that defines us, but our response to it. (antithesis)'],
        ['es' => 'Debemos actuar. Debemos unirnos. Debemos cambiar.', 'en' => 'We must act. We must unite. We must change. (anaphora)'],
    ],
    'common_errors' => [
        ['wrong' => 'Overusing rhetorical questions in academic papers.', 'right' => 'One rhetorical question max; rely on evidence.', 'why' => 'Rhetorical questions are persuasive but not always academic.'],
        ['wrong' => 'Confusing ironía with sarcasmo.', 'right' => 'Ironía can be gentle; sarcasmo is always cutting.', 'why' => 'Different pragmatic force.'],
    ],
    'mnemonics' => [
        'en' => 'Rhetoric trinity: ethos (credibility), pathos (emotion), logos (logic). Spanish adds: "de hecho" (logos), "¿acaso?" (pathos).',
    ],
],

// 58. SOCIOLINGUISTIC VARIATION
[
    'topic' => 'sociolinguistic_variation',
    'keywords' => ['sociolinguistic','sociolingüístico','dialect','dialecto','variation','variación','voseo','seseo','yeísmo','regional','Colombian Spanish','Mexican Spanish','Argentine Spanish','Caribbean Spanish'],
    'cefr' => 'C2',
    'title_es' => 'Variación sociolingüística',
    'title_en' => 'Sociolinguistic variation',
    'explanation' => [
        'C1' => 'Key phenomena: SESEO (s=z=c, all Latin America + southern Spain), YEÍSMO (ll=y, most of Spanish-speaking world), VOSEO (vos instead of tú — Argentina, Uruguay, parts of Colombia/Central America: vos tenés, vos hablás). USTEDEO in Colombia: usted used for intimacy as well as formality.',
        'C2' => 'Regional features: Caribbean (aspiration/loss of final -s: "¿Cómo ehtá?"), Mexico (diminutives: ahorita, lueguito), Argentina (voseo + shesheo: "yo" → "sho", "calle" → "cashe"), Colombia (varied: costeño, paisa, bogotano, each with distinct features). Attitudes: prescriptivism vs descriptivism. No dialect is "better" — all are valid linguistic systems. Code-switching between dialects based on context.',
    ],
    'examples' => [
        ['es' => '¿Vos tenés hora? (Argentina)', 'en' => 'Do you have the time? (voseo form)'],
        ['es' => 'Ahorita vengo. (México)', 'en' => 'I\'ll be right back. (Mexican diminutive "ahorita")'],
        ['es' => '¿Usted cómo amaneció? (Colombia)', 'en' => 'How did you wake up? (Colombian intimacy with usted)'],
    ],
    'common_errors' => [
        ['wrong' => 'Saying one dialect is "wrong."', 'right' => 'All dialects are valid; context determines appropriateness.', 'why' => 'Linguistic prejudice is not based on grammar.'],
        ['wrong' => 'Mixing voseo conjugation with tú.', 'right' => '"Vos tenés" or "Tú tienes" — don\'t mix "Vos tienes."', 'why' => 'Each pronoun has its own verb forms.'],
    ],
    'mnemonics' => [
        'en' => 'Seseo = s everywhere. Yeísmo = no ll/y distinction. Voseo = vos + special endings (-ás, -és, -ís).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/voseo',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/seseo-ceceo-y-distincion/',
],

// ══════════════════════════════════════════════
// C2 ADVANCED (dest54-58): Full integration, pedagogy, neologisms, philosophy
// ══════════════════════════════════════════════

// 59. PEDAGOGICAL DISCOURSE
[
    'topic' => 'pedagogical_discourse',
    'keywords' => ['pedagogical','pedagógico','teaching','enseñanza','explaining','explaining grammar','classroom language','instruction','how to explain','didactic'],
    'cefr' => 'C2',
    'title_es' => 'Discurso pedagógico',
    'title_en' => 'Pedagogical discourse',
    'explanation' => [
        'C2' => 'Explaining grammar concepts IN Spanish, to Spanish learners. Key skills: metalanguage (sujeto, predicado, concordancia), scaffolding (building from known to unknown), reformulation (explaining the same concept differently), checking comprehension ("¿Me explico?", "¿Quedó claro?"). Structures: "Fíjense que..." (Notice that...), "Es decir..." (That is to say...), "Lo que quiero decir es que..." (What I mean is that...), "Vamos a ver un ejemplo" (Let\'s see an example).',
    ],
    'examples' => [
        ['es' => 'Fíjense que el subjuntivo se usa después de emociones.', 'en' => 'Notice that the subjunctive is used after emotions.'],
        ['es' => 'Es decir, cuando no estamos seguros, usamos el subjuntivo.', 'en' => 'That is, when we\'re not sure, we use the subjunctive.'],
        ['es' => '¿Me explico? ¿Tienen alguna pregunta?', 'en' => 'Am I clear? Do you have any questions?'],
    ],
    'common_errors' => [
        ['wrong' => 'Explaining grammar only with rules.', 'right' => 'Combine rules with examples and context.', 'why' => 'Pure rules without examples are hard to internalize.'],
        ['wrong' => 'Using English terms when explaining in Spanish.', 'right' => 'Learn and use Spanish grammatical terminology.', 'why' => 'At C2, you should operate in the target language.'],
    ],
    'mnemonics' => [
        'en' => 'Good explanation = context → example → rule → more examples → check understanding.',
    ],
],

// 60. NEOLOGISMS AND WORD FORMATION
[
    'topic' => 'neologisms',
    'keywords' => ['neologism','neologismo','word formation','formación de palabras','prefix','suffix','prefijo','sufijo','compound','compuesto','blending','new words','anglicism','anglicismo'],
    'cefr' => 'C2',
    'title_es' => 'Neologismos y formación de palabras',
    'title_en' => 'Neologisms and word formation',
    'explanation' => [
        'C1' => 'Productive prefixes: des- (undo: deshacer), re- (again: rehacer), in-/im- (not: imposible), pre- (before: predecir), sobre-/super- (over: sobrepasar). Productive suffixes: -mente (adverb: rápidamente), -ero/-era (agent: panadero), -ería (place: panadería), -izar (verb from adj: modernizar).',
        'C2' => 'Neologisms enter through: anglicisms (tuitear, surfear, googlear — some accepted, some resisted), calques (rascacielos = skyscraper), semantic extension (ratón = mouse for computers), blending (spanglish = español + English). RAE vs usage: prescriptive resistance vs organic adoption. Compound words: abrelatas (can opener), sacapuntas (pencil sharpener), paraguas (umbrella). Understanding word formation helps decode unknown words.',
    ],
    'examples' => [
        ['es' => 'Voy a googlear la respuesta.', 'en' => 'I\'m going to google the answer. (anglicism)'],
        ['es' => 'Necesito el sacapuntas.', 'en' => 'I need the pencil sharpener. (compound: saca+puntas)'],
        ['es' => 'El proceso de modernización.', 'en' => 'The modernization process. (-izar → -ización)'],
    ],
    'common_errors' => [
        ['wrong' => 'Creating non-existent words by over-applying patterns.', 'right' => 'Check if the word actually exists before using it.', 'why' => 'Not all logical formations are real words.'],
        ['wrong' => 'Assuming all anglicisms are accepted.', 'right' => 'Many have Spanish alternatives: computadora (not "computera").', 'why' => 'Some anglicisms are accepted, others are not.'],
    ],
    'mnemonics' => [
        'en' => 'Prefixes change MEANING (des- = undo, re- = again). Suffixes change CATEGORY (verb→noun: -ción, adj→adverb: -mente).',
    ],
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/neologismos/',
],

// 61. LANGUAGE PHILOSOPHY
[
    'topic' => 'language_philosophy',
    'keywords' => ['philosophy of language','filosofía del lenguaje','Sapir-Whorf','linguistic relativity','prescriptivism','descriptivism','language and thought','lenguaje y pensamiento','RAE','norma','uso'],
    'cefr' => 'C2',
    'title_es' => 'Filosofía del lenguaje',
    'title_en' => 'Philosophy of language',
    'explanation' => [
        'C2' => 'Key debates: PRESCRIPTIVISM (la norma — there is a "correct" Spanish, defined by RAE) vs DESCRIPTIVISM (el uso — language is what speakers actually do). Sapir-Whorf hypothesis: does Spanish structure thought differently? (e.g., ser/estar distinction encodes a worldview English lacks). The politics of language: whose Spanish counts? Why is castellano sometimes preferred over español? Language and identity: regional varieties as markers of cultural belonging. The RAE\'s role: arbiter or documenter? "Limpia, fija y da esplendor" vs organic evolution.',
    ],
    'examples' => [
        ['es' => '¿La lengua refleja el pensamiento o lo moldea?', 'en' => 'Does language reflect thought or shape it?'],
        ['es' => 'La RAE acepta "almóndiga" pero los hablantes lo rechazan.', 'en' => 'The RAE accepts "almóndiga" but speakers reject it.'],
        ['es' => 'El español no es uno sino muchos.', 'en' => 'Spanish is not one language but many.'],
    ],
    'common_errors' => [
        ['wrong' => '"La RAE says" as final authority on meaning.', 'right' => 'The RAE documents; speakers decide.', 'why' => 'Language evolves through use, not dictionaries.'],
        ['wrong' => 'Calling any dialect "incorrect."', 'right' => 'All native varieties are linguistically valid systems.', 'why' => '"Incorrect" is a social judgment, not a linguistic one.'],
    ],
    'mnemonics' => [
        'en' => 'Prescriptivism = "How should we speak?" Descriptivism = "How DO we speak?" Both perspectives have value.',
    ],
],

// ══════════════════════════════════════════════
// ADDITIONAL ESSENTIAL TOPICS (cross-level)
// ══════════════════════════════════════════════

// 62. PRETERITE IRREGULAR
[
    'topic' => 'preterite_irregular',
    'keywords' => ['preterite irregular','pretérito irregular','fui','fue','hice','dije','tuve','pude','puse','supe','vine','quise','traje','irregular past','u-stem','i-stem','j-stem'],
    'cefr' => 'A2',
    'title_es' => 'Pretérito irregular',
    'title_en' => 'Irregular preterite',
    'explanation' => [
        'A2' => 'Key irregular preterites: SER/IR: fui, fuiste, fue, fuimos, fueron (identical!). HACER: hice, hiciste, hizo, hicimos, hicieron. DAR: di, diste, dio, dimos, dieron. VER: vi, viste, vio, vimos, vieron.',
        'B1' => 'U-stem group (special endings: -e, -iste, -o, -imos, -ieron): tener→tuv-, estar→estuv-, poder→pud-, poner→pus-, saber→sup-, haber→hub-, caber→cup-, andar→anduv-. I-stem: hacer→hic-, querer→quis-, venir→vin-. J-stem: decir→dij-, traer→traj-, conducir→conduj- (no I in -eron: dijeron, not *dijieron).',
    ],
    'examples' => [
        ['es' => 'Fui al cine ayer.', 'en' => 'I went to the cinema yesterday. (ir)'],
        ['es' => 'Ella hizo la tarea.', 'en' => 'She did the homework. (hacer)'],
        ['es' => 'No pude dormir anoche.', 'en' => 'I couldn\'t sleep last night. (poder)'],
        ['es' => 'Me dijeron la verdad.', 'en' => 'They told me the truth. (decir)'],
    ],
    'common_errors' => [
        ['wrong' => 'Yo hací.', 'right' => 'Yo hice.', 'why' => 'Irregular stem hic- with special ending -e.'],
        ['wrong' => 'Ellos dijieron.', 'right' => 'Ellos dijeron.', 'why' => 'J-stem verbs drop the i: dijeron, trajeron, condujeron.'],
        ['wrong' => 'Yo sé la respuesta ayer.', 'right' => 'Yo supe la respuesta ayer.', 'why' => '"Supe" = found out (preterite of saber).'],
    ],
    'mnemonics' => [
        'en' => 'U-stems: tuv-, estuv-, pud-, pus-, sup-, hub-. I-stems: hic-, quis-, vin-. J-stems: dij-, traj-, conduj-. Special endings: -e, -iste, -o, -imos, -ieron.',
    ],
],

// 63. GERUND (present participle)
[
    'topic' => 'gerund',
    'keywords' => ['gerund','gerundio','-ando','-iendo','present participle','progressive','estoy comiendo','estoy hablando','doing','currently','right now'],
    'cefr' => 'A2',
    'title_es' => 'El gerundio (-ando, -iendo)',
    'title_en' => 'Gerund / present participle',
    'explanation' => [
        'A2' => '-AR→-ANDO (hablando, caminando). -ER/-IR→-IENDO (comiendo, viviendo). ESTAR + gerund = present progressive: "Estoy estudiando" (I\'m studying right now). Irregulars: leer→leyendo, dormir→durmiendo, pedir→pidiendo, ir→yendo, poder→pudiendo, decir→diciendo.',
        'B1' => 'Other uses: SEGUIR/CONTINUAR + gerund (keep doing): "Sigue lloviendo." IR + gerund (gradual): "Va mejorando." LLEVAR + time + gerund (duration): "Llevo dos horas estudiando" (I\'ve been studying for two hours). Never use gerund as adjective in Spanish (*"agua hirviendo" → "agua hirviente" or "agua que hierve").',
    ],
    'examples' => [
        ['es' => 'Estoy aprendiendo español.', 'en' => 'I\'m learning Spanish.'],
        ['es' => 'Sigue lloviendo.', 'en' => 'It keeps raining.'],
        ['es' => 'Llevo tres meses estudiando aquí.', 'en' => 'I\'ve been studying here for three months.'],
    ],
    'common_errors' => [
        ['wrong' => 'Estoy habliendo.', 'right' => 'Estoy hablando.', 'why' => '-AR verbs use -ando, not -iendo.'],
        ['wrong' => 'Una chica hablando español.', 'right' => 'Una chica que habla español.', 'why' => 'Spanish gerund is not used as an adjective (unlike English).'],
    ],
    'mnemonics' => [
        'en' => '-AR → -ANDO. -ER/-IR → -IENDO. Stem-change -IR verbs: e→i (pidiendo), o→u (durmiendo).',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/gerundio',
    'fundeu_url' => 'https://www.fundeu.es/recomendacion/gerundio/',
],

// 64. OBJECT PRONOUN PLACEMENT
[
    'topic' => 'pronoun_placement',
    'keywords' => ['pronoun placement','colocación de pronombres','before verb','attached','enclitic','proclitic','dámelo','no me lo des','se lo','pronoun order','double pronoun'],
    'cefr' => 'B1',
    'title_es' => 'Colocación de pronombres',
    'title_en' => 'Object pronoun placement',
    'explanation' => [
        'B1' => 'BEFORE conjugated verb: "Lo veo" (I see it). ATTACHED to infinitive: "Quiero verlo" (or "Lo quiero ver"). ATTACHED to gerund: "Estoy haciéndolo" (or "Lo estoy haciendo"). ATTACHED to affirmative commands: "Dámelo" (Give it to me). BEFORE negative commands: "No me lo des."',
        'B2' => 'Double pronouns order: reflexive → indirect → direct. "Se lo doy" (I give it to him). When attaching, add accent to maintain stress: "dándomelo" (giving it to me), "dígamelo" (tell it to me). With compound verbs (haber), pronoun goes before haber: "Se lo he dicho" (not *he se lo dicho). Three pronouns max.',
    ],
    'examples' => [
        ['es' => 'Se lo expliqué ayer.', 'en' => 'I explained it to him/her yesterday.'],
        ['es' => '¡Dámelo!', 'en' => 'Give it to me!'],
        ['es' => 'No se lo digas a nadie.', 'en' => 'Don\'t tell anyone. (negative command)'],
        ['es' => 'Estoy preparándotelo.', 'en' => 'I\'m preparing it for you.'],
    ],
    'common_errors' => [
        ['wrong' => 'Me lo da. / Damelo.', 'right' => 'Me lo da. / Dámelo.', 'why' => 'Written accent needed when attaching to maintain original stress.'],
        ['wrong' => 'He lo dicho se.', 'right' => 'Se lo he dicho.', 'why' => 'Pronouns before haber, in RID order.'],
    ],
    'mnemonics' => [
        'en' => 'Attached to: infinitives, gerunds, affirmative commands. Before: conjugated verbs, negative commands, haber.',
    ],
],

// 65. PERSONAL A
[
    'topic' => 'personal_a',
    'keywords' => ['personal a','a personal','a before person','ver a','conocer a','buscar a','direct object person','human direct object'],
    'cefr' => 'A2',
    'title_es' => 'La a personal',
    'title_en' => 'Personal a',
    'explanation' => [
        'A2' => 'When the DIRECT OBJECT is a specific person, add A before them: "Veo A mi madre." "Conozco A Juan." NOT with things: "Veo la casa" (no a). NOT with tener: "Tengo un hermano" (no a). NOT with hay: "Hay un doctor aquí" (no a).',
        'B1' => 'Also used with: pets ("Busco a mi perro"), personified things ("Teme a la muerte"), indefinite people with personal feel ("Busco a alguien que me ayude"). Omitted with: unspecified people ("Necesito un doctor" — any doctor), tener, hay, ser. After verbs of perception with que: "Vi que María llegó" (no personal a).',
    ],
    'examples' => [
        ['es' => 'Llamo a mi hermana.', 'en' => 'I call my sister. (person = a)'],
        ['es' => 'Conozco a tu profesora.', 'en' => 'I know your teacher. (specific person = a)'],
        ['es' => 'Necesito un médico.', 'en' => 'I need a doctor. (unspecified = no a)'],
        ['es' => 'Veo la montaña.', 'en' => 'I see the mountain. (thing = no a)'],
    ],
    'common_errors' => [
        ['wrong' => 'Veo mi madre.', 'right' => 'Veo a mi madre.', 'why' => 'Specific person as direct object needs "a."'],
        ['wrong' => 'Tengo a dos hermanos.', 'right' => 'Tengo dos hermanos.', 'why' => '"Tener" never takes personal a.'],
    ],
    'mnemonics' => [
        'en' => 'Person as direct object? Add A. Exception: tener and hay never take personal a.',
    ],
    'dpd_url' => 'https://www.rae.es/dpd/a',
],

]; // end topics array
} // end getGrammarTopics()
