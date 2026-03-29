<?php
/**
 * Build conjugation exercise games for all 89 destinations.
 * Inserts one conjugation game with 8 questions at games[3] position.
 *
 * Usage: php build-conjugation-games.php [--dry-run] [--dest=N]
 */

$dryRun = in_array('--dry-run', $argv);
$singleDest = null;
foreach ($argv as $arg) {
    if (preg_match('/^--dest=(\d+)$/', $arg, $m)) {
        $singleDest = (int)$m[1];
    }
}

$contentDir = __DIR__;

// ============================================================
// COMPLETE VERB CONJUGATION DATABASE
// ============================================================

$conjugations = [
    // ---- PRESENT INDICATIVE ----
    'ser' => [
        'presente de indicativo' => ['soy','eres','es','somos','sois','son'],
        'pretérito indefinido' => ['fui','fuiste','fue','fuimos','fuisteis','fueron'],
        'imperfecto' => ['era','eras','era','éramos','erais','eran'],
        'presente de subjuntivo' => ['sea','seas','sea','seamos','seáis','sean'],
        'condicional' => ['sería','serías','sería','seríamos','seríais','serían'],
        'futuro' => ['seré','serás','será','seremos','seréis','serán'],
        'imperfecto de subjuntivo' => ['fuera','fueras','fuera','fuéramos','fuerais','fueran'],
        'pretérito pluscuamperfecto' => ['había sido','habías sido','había sido','habíamos sido','habíais sido','habían sido'],
    ],
    'estar' => [
        'presente de indicativo' => ['estoy','estás','está','estamos','estáis','están'],
        'pretérito indefinido' => ['estuve','estuviste','estuvo','estuvimos','estuvisteis','estuvieron'],
        'imperfecto' => ['estaba','estabas','estaba','estábamos','estabais','estaban'],
        'presente de subjuntivo' => ['esté','estés','esté','estemos','estéis','estén'],
        'condicional' => ['estaría','estarías','estaría','estaríamos','estaríais','estarían'],
        'futuro' => ['estaré','estarás','estará','estaremos','estaréis','estarán'],
        'imperfecto de subjuntivo' => ['estuviera','estuvieras','estuviera','estuviéramos','estuvierais','estuvieran'],
    ],
    'tener' => [
        'presente de indicativo' => ['tengo','tienes','tiene','tenemos','tenéis','tienen'],
        'pretérito indefinido' => ['tuve','tuviste','tuvo','tuvimos','tuvisteis','tuvieron'],
        'imperfecto' => ['tenía','tenías','tenía','teníamos','teníais','tenían'],
        'presente de subjuntivo' => ['tenga','tengas','tenga','tengamos','tengáis','tengan'],
        'condicional' => ['tendría','tendrías','tendría','tendríamos','tendríais','tendrían'],
        'futuro' => ['tendré','tendrás','tendrá','tendremos','tendréis','tendrán'],
        'imperfecto de subjuntivo' => ['tuviera','tuvieras','tuviera','tuviéramos','tuvierais','tuvieran'],
    ],
    'ir' => [
        'presente de indicativo' => ['voy','vas','va','vamos','vais','van'],
        'pretérito indefinido' => ['fui','fuiste','fue','fuimos','fuisteis','fueron'],
        'imperfecto' => ['iba','ibas','iba','íbamos','ibais','iban'],
        'presente de subjuntivo' => ['vaya','vayas','vaya','vayamos','vayáis','vayan'],
        'condicional' => ['iría','irías','iría','iríamos','iríais','irían'],
        'futuro' => ['iré','irás','irá','iremos','iréis','irán'],
        'imperfecto de subjuntivo' => ['fuera','fueras','fuera','fuéramos','fuerais','fueran'],
    ],
    'hacer' => [
        'presente de indicativo' => ['hago','haces','hace','hacemos','hacéis','hacen'],
        'pretérito indefinido' => ['hice','hiciste','hizo','hicimos','hicisteis','hicieron'],
        'imperfecto' => ['hacía','hacías','hacía','hacíamos','hacíais','hacían'],
        'presente de subjuntivo' => ['haga','hagas','haga','hagamos','hagáis','hagan'],
        'condicional' => ['haría','harías','haría','haríamos','haríais','harían'],
        'futuro' => ['haré','harás','hará','haremos','haréis','harán'],
        'imperfecto de subjuntivo' => ['hiciera','hicieras','hiciera','hiciéramos','hicierais','hicieran'],
    ],
    'querer' => [
        'presente de indicativo' => ['quiero','quieres','quiere','queremos','queréis','quieren'],
        'pretérito indefinido' => ['quise','quisiste','quiso','quisimos','quisisteis','quisieron'],
        'imperfecto' => ['quería','querías','quería','queríamos','queríais','querían'],
        'presente de subjuntivo' => ['quiera','quieras','quiera','queramos','queráis','quieran'],
        'condicional' => ['querría','querrías','querría','querríamos','querríais','querrían'],
        'futuro' => ['querré','querrás','querrá','querremos','querréis','querrán'],
        'imperfecto de subjuntivo' => ['quisiera','quisieras','quisiera','quisiéramos','quisierais','quisieran'],
    ],
    'poder' => [
        'presente de indicativo' => ['puedo','puedes','puede','podemos','podéis','pueden'],
        'pretérito indefinido' => ['pude','pudiste','pudo','pudimos','pudisteis','pudieron'],
        'imperfecto' => ['podía','podías','podía','podíamos','podíais','podían'],
        'presente de subjuntivo' => ['pueda','puedas','pueda','podamos','podáis','puedan'],
        'condicional' => ['podría','podrías','podría','podríamos','podríais','podrían'],
        'futuro' => ['podré','podrás','podrá','podremos','podréis','podrán'],
        'imperfecto de subjuntivo' => ['pudiera','pudieras','pudiera','pudiéramos','pudierais','pudieran'],
    ],
    'hablar' => [
        'presente de indicativo' => ['hablo','hablas','habla','hablamos','habláis','hablan'],
        'pretérito indefinido' => ['hablé','hablaste','habló','hablamos','hablasteis','hablaron'],
        'imperfecto' => ['hablaba','hablabas','hablaba','hablábamos','hablabais','hablaban'],
        'presente de subjuntivo' => ['hable','hables','hable','hablemos','habléis','hablen'],
        'condicional' => ['hablaría','hablarías','hablaría','hablaríamos','hablaríais','hablarían'],
        'futuro' => ['hablaré','hablarás','hablará','hablaremos','hablaréis','hablarán'],
        'imperfecto de subjuntivo' => ['hablara','hablaras','hablara','habláramos','hablarais','hablaran'],
        'futuro de subjuntivo' => ['hablare','hablares','hablare','habláremos','hablareis','hablaren'],
    ],
    'comer' => [
        'presente de indicativo' => ['como','comes','come','comemos','coméis','comen'],
        'pretérito indefinido' => ['comí','comiste','comió','comimos','comisteis','comieron'],
        'imperfecto' => ['comía','comías','comía','comíamos','comíais','comían'],
        'presente de subjuntivo' => ['coma','comas','coma','comamos','comáis','coman'],
        'condicional' => ['comería','comerías','comería','comeríamos','comeríais','comerían'],
        'futuro' => ['comeré','comerás','comerá','comeremos','comeréis','comerán'],
        'imperfecto de subjuntivo' => ['comiera','comieras','comiera','comiéramos','comierais','comieran'],
        'futuro de subjuntivo' => ['comiere','comieres','comiere','comiéremos','comiereis','comieren'],
    ],
    'beber' => [
        'presente de indicativo' => ['bebo','bebes','bebe','bebemos','bebéis','beben'],
        'pretérito indefinido' => ['bebí','bebiste','bebió','bebimos','bebisteis','bebieron'],
        'imperfecto' => ['bebía','bebías','bebía','bebíamos','bebíais','bebían'],
        'presente de subjuntivo' => ['beba','bebas','beba','bebamos','bebáis','beban'],
        'condicional' => ['bebería','beberías','bebería','beberíamos','beberíais','beberían'],
        'futuro' => ['beberé','beberás','beberá','beberemos','beberéis','beberán'],
        'imperfecto de subjuntivo' => ['bebiera','bebieras','bebiera','bebiéramos','bebierais','bebieran'],
    ],
    'vivir' => [
        'presente de indicativo' => ['vivo','vives','vive','vivimos','vivís','viven'],
        'pretérito indefinido' => ['viví','viviste','vivió','vivimos','vivisteis','vivieron'],
        'imperfecto' => ['vivía','vivías','vivía','vivíamos','vivíais','vivían'],
        'presente de subjuntivo' => ['viva','vivas','viva','vivamos','viváis','vivan'],
        'condicional' => ['viviría','vivirías','viviría','viviríamos','viviríais','vivirían'],
        'futuro' => ['viviré','vivirás','vivirá','viviremos','viviréis','vivirán'],
        'imperfecto de subjuntivo' => ['viviera','vivieras','viviera','viviéramos','vivierais','vivieran'],
        'futuro de subjuntivo' => ['viviere','vivieres','viviere','viviéremos','viviereis','vivieren'],
    ],
    'gustar' => [
        'presente de indicativo' => ['gusto','gustas','gusta','gustamos','gustáis','gustan'],
        'pretérito indefinido' => ['gusté','gustaste','gustó','gustamos','gustasteis','gustaron'],
        'imperfecto' => ['gustaba','gustabas','gustaba','gustábamos','gustabais','gustaban'],
        'presente de subjuntivo' => ['guste','gustes','guste','gustemos','gustéis','gusten'],
    ],
    'llamarse' => [
        'presente de indicativo' => ['me llamo','te llamas','se llama','nos llamamos','os llamáis','se llaman'],
    ],
    // A2+ verbs
    'contar' => [
        'presente de indicativo' => ['cuento','cuentas','cuenta','contamos','contáis','cuentan'],
        'pretérito indefinido' => ['conté','contaste','contó','contamos','contasteis','contaron'],
        'imperfecto' => ['contaba','contabas','contaba','contábamos','contabais','contaban'],
        'presente de subjuntivo' => ['cuente','cuentes','cuente','contemos','contéis','cuenten'],
        'condicional' => ['contaría','contarías','contaría','contaríamos','contaríais','contarían'],
        'futuro' => ['contaré','contarás','contará','contaremos','contaréis','contarán'],
        'imperfecto de subjuntivo' => ['contara','contaras','contara','contáramos','contarais','contaran'],
    ],
    'encontrar' => [
        'presente de indicativo' => ['encuentro','encuentras','encuentra','encontramos','encontráis','encuentran'],
        'pretérito indefinido' => ['encontré','encontraste','encontró','encontramos','encontrasteis','encontraron'],
        'imperfecto' => ['encontraba','encontrabas','encontraba','encontrábamos','encontrabais','encontraban'],
        'presente de subjuntivo' => ['encuentre','encuentres','encuentre','encontremos','encontréis','encuentren'],
        'condicional' => ['encontraría','encontrarías','encontraría','encontraríamos','encontraríais','encontrarían'],
    ],
    'salir' => [
        'presente de indicativo' => ['salgo','sales','sale','salimos','salís','salen'],
        'pretérito indefinido' => ['salí','saliste','salió','salimos','salisteis','salieron'],
        'imperfecto' => ['salía','salías','salía','salíamos','salíais','salían'],
        'presente de subjuntivo' => ['salga','salgas','salga','salgamos','salgáis','salgan'],
        'condicional' => ['saldría','saldrías','saldría','saldríamos','saldríais','saldrían'],
        'futuro' => ['saldré','saldrás','saldrá','saldremos','saldréis','saldrán'],
        'imperfecto de subjuntivo' => ['saliera','salieras','saliera','saliéramos','salierais','salieran'],
    ],
    'volver' => [
        'presente de indicativo' => ['vuelvo','vuelves','vuelve','volvemos','volvéis','vuelven'],
        'pretérito indefinido' => ['volví','volviste','volvió','volvimos','volvisteis','volvieron'],
        'imperfecto' => ['volvía','volvías','volvía','volvíamos','volvíais','volvían'],
        'presente de subjuntivo' => ['vuelva','vuelvas','vuelva','volvamos','volváis','vuelvan'],
        'condicional' => ['volvería','volverías','volvería','volveríamos','volveríais','volverían'],
        'futuro' => ['volveré','volverás','volverá','volveremos','volveréis','volverán'],
        'imperfecto de subjuntivo' => ['volviera','volvieras','volviera','volviéramos','volvierais','volvieran'],
    ],
    'sentir' => [
        'presente de indicativo' => ['siento','sientes','siente','sentimos','sentís','sienten'],
        'pretérito indefinido' => ['sentí','sentiste','sintió','sentimos','sentisteis','sintieron'],
        'imperfecto' => ['sentía','sentías','sentía','sentíamos','sentíais','sentían'],
        'presente de subjuntivo' => ['sienta','sientas','sienta','sintamos','sintáis','sientan'],
        'condicional' => ['sentiría','sentirías','sentiría','sentiríamos','sentiríais','sentirían'],
        'imperfecto de subjuntivo' => ['sintiera','sintieras','sintiera','sintiéramos','sintierais','sintieran'],
    ],
    'decir' => [
        'presente de indicativo' => ['digo','dices','dice','decimos','decís','dicen'],
        'pretérito indefinido' => ['dije','dijiste','dijo','dijimos','dijisteis','dijeron'],
        'imperfecto' => ['decía','decías','decía','decíamos','decíais','decían'],
        'presente de subjuntivo' => ['diga','digas','diga','digamos','digáis','digan'],
        'condicional' => ['diría','dirías','diría','diríamos','diríais','dirían'],
        'futuro' => ['diré','dirás','dirá','diremos','diréis','dirán'],
        'imperfecto de subjuntivo' => ['dijera','dijeras','dijera','dijéramos','dijerais','dijeran'],
        'futuro de subjuntivo' => ['dijere','dijeres','dijere','dijéremos','dijereis','dijeren'],
    ],
    'saber' => [
        'presente de indicativo' => ['sé','sabes','sabe','sabemos','sabéis','saben'],
        'pretérito indefinido' => ['supe','supiste','supo','supimos','supisteis','supieron'],
        'imperfecto' => ['sabía','sabías','sabía','sabíamos','sabíais','sabían'],
        'presente de subjuntivo' => ['sepa','sepas','sepa','sepamos','sepáis','sepan'],
        'condicional' => ['sabría','sabrías','sabría','sabríamos','sabríais','sabrían'],
        'futuro' => ['sabré','sabrás','sabrá','sabremos','sabréis','sabrán'],
        'imperfecto de subjuntivo' => ['supiera','supieras','supiera','supiéramos','supierais','supieran'],
        'futuro de subjuntivo' => ['supiere','supieres','supiere','supiéremos','supiereis','supieren'],
    ],
    'conocer' => [
        'presente de indicativo' => ['conozco','conoces','conoce','conocemos','conocéis','conocen'],
        'pretérito indefinido' => ['conocí','conociste','conoció','conocimos','conocisteis','conocieron'],
        'imperfecto' => ['conocía','conocías','conocía','conocíamos','conocíais','conocían'],
        'presente de subjuntivo' => ['conozca','conozcas','conozca','conozcamos','conozcáis','conozcan'],
        'imperfecto de subjuntivo' => ['conociera','conocieras','conociera','conociéramos','conocierais','conocieran'],
    ],
    // B1+ verbs
    'creer' => [
        'presente de indicativo' => ['creo','crees','cree','creemos','creéis','creen'],
        'pretérito indefinido' => ['creí','creíste','creyó','creímos','creísteis','creyeron'],
        'presente de subjuntivo' => ['crea','creas','crea','creamos','creáis','crean'],
        'condicional' => ['creería','creerías','creería','creeríamos','creeríais','creerían'],
        'futuro' => ['creeré','creerás','creerá','creeremos','creeréis','creerán'],
        'imperfecto de subjuntivo' => ['creyera','creyeras','creyera','creyéramos','creyerais','creyeran'],
    ],
    'pensar' => [
        'presente de indicativo' => ['pienso','piensas','piensa','pensamos','pensáis','piensan'],
        'pretérito indefinido' => ['pensé','pensaste','pensó','pensamos','pensasteis','pensaron'],
        'presente de subjuntivo' => ['piense','pienses','piense','pensemos','penséis','piensen'],
        'condicional' => ['pensaría','pensarías','pensaría','pensaríamos','pensaríais','pensarían'],
        'futuro' => ['pensaré','pensarás','pensará','pensaremos','pensaréis','pensarán'],
        'imperfecto de subjuntivo' => ['pensara','pensaras','pensara','pensáramos','pensarais','pensaran'],
    ],
    'esperar' => [
        'presente de indicativo' => ['espero','esperas','espera','esperamos','esperáis','esperan'],
        'pretérito indefinido' => ['esperé','esperaste','esperó','esperamos','esperasteis','esperaron'],
        'presente de subjuntivo' => ['espere','esperes','espere','esperemos','esperéis','esperen'],
        'condicional' => ['esperaría','esperarías','esperaría','esperaríamos','esperaríais','esperarían'],
        'futuro' => ['esperaré','esperarás','esperará','esperaremos','esperaréis','esperarán'],
        'imperfecto de subjuntivo' => ['esperara','esperaras','esperara','esperáramos','esperarais','esperaran'],
    ],
    'necesitar' => [
        'presente de indicativo' => ['necesito','necesitas','necesita','necesitamos','necesitáis','necesitan'],
        'pretérito indefinido' => ['necesité','necesitaste','necesitó','necesitamos','necesitasteis','necesitaron'],
        'presente de subjuntivo' => ['necesite','necesites','necesite','necesitemos','necesitéis','necesiten'],
        'condicional' => ['necesitaría','necesitarías','necesitaría','necesitaríamos','necesitaríais','necesitarían'],
        'futuro' => ['necesitaré','necesitarás','necesitará','necesitaremos','necesitaréis','necesitarán'],
        'imperfecto de subjuntivo' => ['necesitara','necesitaras','necesitara','necesitáramos','necesitarais','necesitaran'],
    ],
    'construir' => [
        'presente de indicativo' => ['construyo','construyes','construye','construimos','construís','construyen'],
        'pretérito indefinido' => ['construí','construiste','construyó','construimos','construisteis','construyeron'],
        'presente de subjuntivo' => ['construya','construyas','construya','construyamos','construyáis','construyan'],
        'condicional' => ['construiría','construirías','construiría','construiríamos','construiríais','construirían'],
        'futuro' => ['construiré','construirás','construirá','construiremos','construiréis','construirán'],
        'imperfecto de subjuntivo' => ['construyera','construyeras','construyera','construyéramos','construyerais','construyeran'],
    ],
    'destruir' => [
        'presente de indicativo' => ['destruyo','destruyes','destruye','destruimos','destruís','destruyen'],
        'pretérito indefinido' => ['destruí','destruiste','destruyó','destruimos','destruisteis','destruyeron'],
        'presente de subjuntivo' => ['destruya','destruyas','destruya','destruyamos','destruyáis','destruyan'],
        'condicional' => ['destruiría','destruirías','destruiría','destruiríamos','destruiríais','destruirían'],
        'imperfecto de subjuntivo' => ['destruyera','destruyeras','destruyera','destruyéramos','destruyerais','destruyeran'],
    ],
    // B2+ verbs
    'interpretar' => [
        'presente de indicativo' => ['interpreto','interpretas','interpreta','interpretamos','interpretáis','interpretan'],
        'pretérito indefinido' => ['interpreté','interpretaste','interpretó','interpretamos','interpretasteis','interpretaron'],
        'presente de subjuntivo' => ['interprete','interpretes','interprete','interpretemos','interpretéis','interpreten'],
        'condicional' => ['interpretaría','interpretarías','interpretaría','interpretaríamos','interpretaríais','interpretarían'],
        'imperfecto de subjuntivo' => ['interpretara','interpretaras','interpretara','interpretáramos','interpretarais','interpretaran'],
    ],
    'argumentar' => [
        'presente de indicativo' => ['argumento','argumentas','argumenta','argumentamos','argumentáis','argumentan'],
        'pretérito indefinido' => ['argumenté','argumentaste','argumentó','argumentamos','argumentasteis','argumentaron'],
        'presente de subjuntivo' => ['argumente','argumentes','argumente','argumentemos','argumentéis','argumenten'],
        'condicional' => ['argumentaría','argumentarías','argumentaría','argumentaríamos','argumentaríais','argumentarían'],
        'imperfecto de subjuntivo' => ['argumentara','argumentaras','argumentara','argumentáramos','argumentarais','argumentaran'],
    ],
    'analizar' => [
        'presente de indicativo' => ['analizo','analizas','analiza','analizamos','analizáis','analizan'],
        'pretérito indefinido' => ['analicé','analizaste','analizó','analizamos','analizasteis','analizaron'],
        'presente de subjuntivo' => ['analice','analices','analice','analicemos','analicéis','analicen'],
        'condicional' => ['analizaría','analizarías','analizaría','analizaríamos','analizaríais','analizarían'],
        'imperfecto de subjuntivo' => ['analizara','analizaras','analizara','analizáramos','analizarais','analizaran'],
    ],
    'proponer' => [
        'presente de indicativo' => ['propongo','propones','propone','proponemos','proponéis','proponen'],
        'pretérito indefinido' => ['propuse','propusiste','propuso','propusimos','propusisteis','propusieron'],
        'presente de subjuntivo' => ['proponga','propongas','proponga','propongamos','propongáis','propongan'],
        'condicional' => ['propondría','propondrías','propondría','propondríamos','propondríais','propondrían'],
        'futuro' => ['propondré','propondrás','propondrá','propondremos','propondréis','propondrán'],
        'imperfecto de subjuntivo' => ['propusiera','propusieras','propusiera','propusiéramos','propusierais','propusieran'],
    ],
    'resolver' => [
        'presente de indicativo' => ['resuelvo','resuelves','resuelve','resolvemos','resolvéis','resuelven'],
        'pretérito indefinido' => ['resolví','resolviste','resolvió','resolvimos','resolvisteis','resolvieron'],
        'presente de subjuntivo' => ['resuelva','resuelvas','resuelva','resolvamos','resolváis','resuelvan'],
        'condicional' => ['resolvería','resolverías','resolvería','resolveríamos','resolveríais','resolverían'],
        'imperfecto de subjuntivo' => ['resolviera','resolvieras','resolviera','resolviéramos','resolvierais','resolvieran'],
    ],
    // C1 irregulars
    'andar' => [
        'presente de indicativo' => ['ando','andas','anda','andamos','andáis','andan'],
        'pretérito indefinido' => ['anduve','anduviste','anduvo','anduvimos','anduvisteis','anduvieron'],
        'presente de subjuntivo' => ['ande','andes','ande','andemos','andéis','anden'],
        'condicional' => ['andaría','andarías','andaría','andaríamos','andaríais','andarían'],
        'futuro' => ['andaré','andarás','andará','andaremos','andaréis','andarán'],
        'imperfecto de subjuntivo' => ['anduviera','anduvieras','anduviera','anduviéramos','anduvierais','anduvieran'],
        'futuro de subjuntivo' => ['anduviere','anduvieres','anduviere','anduviéremos','anduviereis','anduvieren'],
    ],
    'caber' => [
        'presente de indicativo' => ['quepo','cabes','cabe','cabemos','cabéis','caben'],
        'pretérito indefinido' => ['cupe','cupiste','cupo','cupimos','cupisteis','cupieron'],
        'presente de subjuntivo' => ['quepa','quepas','quepa','quepamos','quepáis','quepan'],
        'condicional' => ['cabría','cabrías','cabría','cabríamos','cabríais','cabrían'],
        'futuro' => ['cabré','cabrás','cabrá','cabremos','cabréis','cabrán'],
        'imperfecto de subjuntivo' => ['cupiera','cupieras','cupiera','cupiéramos','cupierais','cupieran'],
        'futuro de subjuntivo' => ['cupiere','cupieres','cupiere','cupiéremos','cupiereis','cupieren'],
    ],
    'traducir' => [
        'presente de indicativo' => ['traduzco','traduces','traduce','traducimos','traducís','traducen'],
        'pretérito indefinido' => ['traduje','tradujiste','tradujo','tradujimos','tradujisteis','tradujeron'],
        'presente de subjuntivo' => ['traduzca','traduzcas','traduzca','traduzcamos','traduzcáis','traduzcan'],
        'condicional' => ['traduciría','traducirías','traduciría','traduciríamos','traduciríais','traducirían'],
        'imperfecto de subjuntivo' => ['tradujera','tradujeras','tradujera','tradujéramos','tradujerais','tradujeran'],
        'futuro de subjuntivo' => ['tradujere','tradujeres','tradujere','tradujéremos','tradujereis','tradujeren'],
    ],
    'poner' => [
        'presente de indicativo' => ['pongo','pones','pone','ponemos','ponéis','ponen'],
        'pretérito indefinido' => ['puse','pusiste','puso','pusimos','pusisteis','pusieron'],
        'presente de subjuntivo' => ['ponga','pongas','ponga','pongamos','pongáis','pongan'],
        'condicional' => ['pondría','pondrías','pondría','pondríamos','pondríais','pondrían'],
        'futuro' => ['pondré','pondrás','pondrá','pondremos','pondréis','pondrán'],
        'imperfecto de subjuntivo' => ['pusiera','pusieras','pusiera','pusiéramos','pusierais','pusieran'],
    ],
    'venir' => [
        'presente de indicativo' => ['vengo','vienes','viene','venimos','venís','vienen'],
        'pretérito indefinido' => ['vine','viniste','vino','vinimos','vinisteis','vinieron'],
        'presente de subjuntivo' => ['venga','vengas','venga','vengamos','vengáis','vengan'],
        'condicional' => ['vendría','vendrías','vendría','vendríamos','vendríais','vendrían'],
        'futuro' => ['vendré','vendrás','vendrá','vendremos','vendréis','vendrán'],
        'imperfecto de subjuntivo' => ['viniera','vinieras','viniera','viniéramos','vinierais','vinieran'],
    ],
    'dar' => [
        'presente de indicativo' => ['doy','das','da','damos','dais','dan'],
        'pretérito indefinido' => ['di','diste','dio','dimos','disteis','dieron'],
        'presente de subjuntivo' => ['dé','des','dé','demos','deis','den'],
        'imperfecto de subjuntivo' => ['diera','dieras','diera','diéramos','dierais','dieran'],
        'futuro de subjuntivo' => ['diere','dieres','diere','diéremos','diereis','dieren'],
    ],
    'escribir' => [
        'presente de indicativo' => ['escribo','escribes','escribe','escribimos','escribís','escriben'],
        'pretérito indefinido' => ['escribí','escribiste','escribió','escribimos','escribisteis','escribieron'],
        'presente de subjuntivo' => ['escriba','escribas','escriba','escribamos','escribáis','escriban'],
        'condicional' => ['escribiría','escribirías','escribiría','escribiríamos','escribiríais','escribirían'],
        'imperfecto de subjuntivo' => ['escribiera','escribieras','escribiera','escribiéramos','escribierais','escribieran'],
    ],
    'leer' => [
        'presente de indicativo' => ['leo','lees','lee','leemos','leéis','leen'],
        'pretérito indefinido' => ['leí','leíste','leyó','leímos','leísteis','leyeron'],
        'presente de subjuntivo' => ['lea','leas','lea','leamos','leáis','lean'],
        'imperfecto de subjuntivo' => ['leyera','leyeras','leyera','leyéramos','leyerais','leyeran'],
        'futuro de subjuntivo' => ['leyere','leyeres','leyere','leyéremos','leyereis','leyeren'],
    ],
    'haber' => [
        'presente de indicativo' => ['he','has','ha','hemos','habéis','han'],
        'pretérito indefinido' => ['hube','hubiste','hubo','hubimos','hubisteis','hubieron'],
        'presente de subjuntivo' => ['haya','hayas','haya','hayamos','hayáis','hayan'],
        'condicional' => ['habría','habrías','habría','habríamos','habríais','habrían'],
        'futuro' => ['habré','habrás','habrá','habremos','habréis','habrán'],
        'imperfecto de subjuntivo' => ['hubiera','hubieras','hubiera','hubiéramos','hubierais','hubieran'],
        'futuro de subjuntivo' => ['hubiere','hubieres','hubiere','hubiéremos','hubiereis','hubieren'],
    ],
    // C2 voseo
    'ver' => [
        'presente de indicativo' => ['veo','ves','ve','vemos','veis','ven'],
        'pretérito indefinido' => ['vi','viste','vio','vimos','visteis','vieron'],
        'presente de subjuntivo' => ['vea','veas','vea','veamos','veáis','vean'],
        'imperfecto' => ['veía','veías','veía','veíamos','veíais','veían'],
        'imperfecto de subjuntivo' => ['viera','vieras','viera','viéramos','vierais','vieran'],
    ],
    'dormir' => [
        'presente de indicativo' => ['duermo','duermes','duerme','dormimos','dormís','duermen'],
        'pretérito indefinido' => ['dormí','dormiste','durmió','dormimos','dormisteis','durmieron'],
        'presente de subjuntivo' => ['duerma','duermas','duerma','durmamos','durmáis','duerman'],
        'imperfecto de subjuntivo' => ['durmiera','durmieras','durmiera','durmiéramos','durmierais','durmieran'],
    ],
    'pedir' => [
        'presente de indicativo' => ['pido','pides','pide','pedimos','pedís','piden'],
        'pretérito indefinido' => ['pedí','pediste','pidió','pedimos','pedisteis','pidieron'],
        'presente de subjuntivo' => ['pida','pidas','pida','pidamos','pidáis','pidan'],
        'imperfecto de subjuntivo' => ['pidiera','pidieras','pidiera','pidiéramos','pidierais','pidieran'],
    ],
    'seguir' => [
        'presente de indicativo' => ['sigo','sigues','sigue','seguimos','seguís','siguen'],
        'pretérito indefinido' => ['seguí','seguiste','siguió','seguimos','seguisteis','siguieron'],
        'presente de subjuntivo' => ['siga','sigas','siga','sigamos','sigáis','sigan'],
        'imperfecto de subjuntivo' => ['siguiera','siguieras','siguiera','siguiéramos','siguierais','siguieran'],
    ],
    'traer' => [
        'presente de indicativo' => ['traigo','traes','trae','traemos','traéis','traen'],
        'pretérito indefinido' => ['traje','trajiste','trajo','trajimos','trajisteis','trajeron'],
        'presente de subjuntivo' => ['traiga','traigas','traiga','traigamos','traigáis','traigan'],
        'imperfecto de subjuntivo' => ['trajera','trajeras','trajera','trajéramos','trajerais','trajeran'],
    ],
    'oír' => [
        'presente de indicativo' => ['oigo','oyes','oye','oímos','oís','oyen'],
        'pretérito indefinido' => ['oí','oíste','oyó','oímos','oísteis','oyeron'],
        'presente de subjuntivo' => ['oiga','oigas','oiga','oigamos','oigáis','oigan'],
        'imperfecto de subjuntivo' => ['oyera','oyeras','oyera','oyéramos','oyerais','oyeran'],
    ],
    'producir' => [
        'presente de indicativo' => ['produzco','produces','produce','producimos','producís','producen'],
        'pretérito indefinido' => ['produje','produjiste','produjo','produjimos','produjisteis','produjeron'],
        'presente de subjuntivo' => ['produzca','produzcas','produzca','produzcamos','produzcáis','produzcan'],
        'imperfecto de subjuntivo' => ['produjera','produjeras','produjera','produjéramos','produjerais','produjeran'],
    ],
    'elegir' => [
        'presente de indicativo' => ['elijo','eliges','elige','elegimos','elegís','eligen'],
        'pretérito indefinido' => ['elegí','elegiste','eligió','elegimos','elegisteis','eligieron'],
        'presente de subjuntivo' => ['elija','elijas','elija','elijamos','elijáis','elijan'],
        'imperfecto de subjuntivo' => ['eligiera','eligieras','eligiera','eligiéramos','eligierais','eligieran'],
    ],
    'nacer' => [
        'presente de indicativo' => ['nazco','naces','nace','nacemos','nacéis','nacen'],
        'pretérito indefinido' => ['nací','naciste','nació','nacimos','nacisteis','nacieron'],
        'presente de subjuntivo' => ['nazca','nazcas','nazca','nazcamos','nazcáis','nazcan'],
    ],
    'morir' => [
        'presente de indicativo' => ['muero','mueres','muere','morimos','morís','mueren'],
        'pretérito indefinido' => ['morí','moriste','murió','morimos','moristeis','murieron'],
        'presente de subjuntivo' => ['muera','mueras','muera','muramos','muráis','mueran'],
        'imperfecto de subjuntivo' => ['muriera','murieras','muriera','muriéramos','murierais','murieran'],
    ],
];

// Subject pronouns indexed 0-5
$subjects = ['yo', 'tú', 'él/ella', 'nosotros', 'vosotros', 'ellos/ellas'];

// ============================================================
// VERB POOLS BY CEFR LEVEL
// ============================================================

$verbPoolA1 = ['ser','estar','tener','ir','hacer','querer','poder','comer','beber','vivir','hablar','gustar'];
$verbPoolA2 = array_merge($verbPoolA1, ['contar','encontrar','salir','volver','sentir','decir','saber','conocer']);
$verbPoolB1 = array_merge($verbPoolA2, ['creer','pensar','esperar','necesitar','construir','destruir','dar','escribir','leer','poner','venir']);
$verbPoolB2 = array_merge($verbPoolB1, ['interpretar','argumentar','analizar','proponer','resolver','ver','dormir','pedir','seguir']);
$verbPoolC1 = array_merge($verbPoolB2, ['andar','caber','traducir','traer','oír','producir','elegir','nacer','morir','haber']);
$verbPoolC2 = $verbPoolC1;

// Tense pools by CEFR
$tensesA1 = ['presente de indicativo'];
$tensesA2 = ['pretérito indefinido', 'imperfecto'];
$tensesB1 = ['presente de subjuntivo', 'condicional', 'futuro'];
$tensesB2 = ['imperfecto de subjuntivo', 'pretérito pluscuamperfecto'];
$tensesC1 = ['presente de indicativo', 'pretérito indefinido', 'imperfecto', 'presente de subjuntivo', 'condicional', 'futuro', 'imperfecto de subjuntivo'];
$tensesC2 = ['futuro de subjuntivo', 'presente de indicativo', 'pretérito indefinido', 'presente de subjuntivo', 'condicional', 'imperfecto de subjuntivo'];

// ============================================================
// VOSEO FORMS FOR C2
// ============================================================

$voseo = [
    'hablar' => ['vos hablás', 'presente de indicativo (voseo)'],
    'comer'  => ['vos comés', 'presente de indicativo (voseo)'],
    'vivir'  => ['vos vivís', 'presente de indicativo (voseo)'],
    'ser'    => ['vos sos', 'presente de indicativo (voseo)'],
    'tener'  => ['vos tenés', 'presente de indicativo (voseo)'],
    'poder'  => ['vos podés', 'presente de indicativo (voseo)'],
    'decir'  => ['vos decís', 'presente de indicativo (voseo)'],
    'salir'  => ['vos salís', 'presente de indicativo (voseo)'],
    'venir'  => ['vos venís', 'presente de indicativo (voseo)'],
    'sentir' => ['vos sentís', 'presente de indicativo (voseo)'],
    'saber'  => ['vos sabés', 'presente de indicativo (voseo)'],
    'hacer'  => ['vos hacés', 'presente de indicativo (voseo)'],
    'ir'     => ['vos vas', 'presente de indicativo (voseo)'],
    'querer' => ['vos querés', 'presente de indicativo (voseo)'],
    'ver'    => ['vos ves', 'presente de indicativo (voseo)'],
    'pedir'  => ['vos pedís', 'presente de indicativo (voseo)'],
    'dormir' => ['vos dormís', 'presente de indicativo (voseo)'],
    'escribir' => ['vos escribís', 'presente de indicativo (voseo)'],
    'leer'   => ['vos leés', 'presente de indicativo (voseo)'],
    'oír'    => ['vos oís', 'presente de indicativo (voseo)'],
];

// ============================================================
// HELPER: generate one question
// ============================================================

function makeQuestion($verb, $subjectIdx, $tense, $conjugations, $subjects, $verbPool) {
    global $voseo;

    $subject = $subjects[$subjectIdx];
    $forms = $conjugations[$verb][$tense] ?? null;
    if (!$forms) return null;

    $answer = $forms[$subjectIdx];

    // Build distractors: other persons of same verb/tense
    $distractors = [];
    for ($i = 0; $i < 6; $i++) {
        if ($i !== $subjectIdx && isset($forms[$i]) && $forms[$i] !== $answer) {
            $distractors[] = $forms[$i];
        }
    }

    // Also add same-person forms from other tenses of same verb
    foreach ($conjugations[$verb] as $otherTense => $otherForms) {
        if ($otherTense !== $tense && isset($otherForms[$subjectIdx]) && $otherForms[$subjectIdx] !== $answer) {
            $distractors[] = $otherForms[$subjectIdx];
        }
    }

    $distractors = array_unique($distractors);
    shuffle($distractors);
    $distractors = array_slice($distractors, 0, 2);

    if (count($distractors) < 2) {
        // Fall back: add forms from other verbs, same person, same tense
        foreach ($verbPool as $otherVerb) {
            if ($otherVerb === $verb) continue;
            if (isset($conjugations[$otherVerb][$tense][$subjectIdx])) {
                $form = $conjugations[$otherVerb][$tense][$subjectIdx];
                if ($form !== $answer && !in_array($form, $distractors)) {
                    $distractors[] = $form;
                    if (count($distractors) >= 2) break;
                }
            }
        }
    }

    if (count($distractors) < 2) return null;

    $options = array_merge([$answer], $distractors);
    shuffle($options);

    return [
        'verb' => $verb,
        'subject' => $subject,
        'answer' => $answer,
        'options' => array_values($options),
        'context' => $tense,
    ];
}

function makeVoseoQuestion($verb, $voseo, $conjugations, $verbPool) {
    if (!isset($voseo[$verb])) return null;

    $answer = $voseo[$verb][0];
    $context = $voseo[$verb][1];

    // Distractors: tú form + él form from present
    $distractors = [];
    if (isset($conjugations[$verb]['presente de indicativo'])) {
        $pres = $conjugations[$verb]['presente de indicativo'];
        $distractors[] = $pres[1]; // tú form
        $distractors[] = $pres[2]; // él form
    }

    // Filter
    $distractors = array_filter($distractors, fn($d) => $d !== $answer);
    $distractors = array_values(array_unique($distractors));

    if (count($distractors) < 2) {
        // Add other voseo forms
        foreach ($voseo as $v => $data) {
            if ($v !== $verb && $data[0] !== $answer && !in_array($data[0], $distractors)) {
                $distractors[] = $data[0];
                if (count($distractors) >= 2) break;
            }
        }
    }

    $distractors = array_slice($distractors, 0, 2);
    if (count($distractors) < 2) return null;

    $options = array_merge([$answer], $distractors);
    shuffle($options);

    return [
        'verb' => $verb,
        'subject' => 'vos',
        'answer' => $answer,
        'options' => array_values($options),
        'context' => $context,
    ];
}

function makeFutSubjQuestion($verb, $conjugations, $subjects) {
    if (!isset($conjugations[$verb]['futuro de subjuntivo'])) return null;

    $forms = $conjugations[$verb]['futuro de subjuntivo'];
    $subjectIdx = rand(0, 5);
    $answer = $forms[$subjectIdx];
    $subject = $subjects[$subjectIdx];

    $distractors = [];
    // Use imperfecto de subjuntivo as distractor (similar but different)
    if (isset($conjugations[$verb]['imperfecto de subjuntivo'][$subjectIdx])) {
        $distractors[] = $conjugations[$verb]['imperfecto de subjuntivo'][$subjectIdx];
    }
    // Use presente de subjuntivo
    if (isset($conjugations[$verb]['presente de subjuntivo'][$subjectIdx])) {
        $distractors[] = $conjugations[$verb]['presente de subjuntivo'][$subjectIdx];
    }
    // Other persons
    for ($i = 0; $i < 6; $i++) {
        if ($i !== $subjectIdx && $forms[$i] !== $answer && !in_array($forms[$i], $distractors)) {
            $distractors[] = $forms[$i];
        }
    }

    $distractors = array_filter($distractors, fn($d) => $d !== $answer);
    $distractors = array_values(array_unique($distractors));
    $distractors = array_slice($distractors, 0, 2);

    if (count($distractors) < 2) return null;

    $options = array_merge([$answer], $distractors);
    shuffle($options);

    return [
        'verb' => $verb,
        'subject' => $subject,
        'answer' => $answer,
        'options' => array_values($options),
        'context' => 'futuro de subjuntivo',
    ];
}

// ============================================================
// GENERATE GAME FOR ONE DESTINATION
// ============================================================

function buildConjugationGame($destNum, $cefr, $destTitle) {
    global $conjugations, $subjects, $voseo,
           $verbPoolA1, $verbPoolA2, $verbPoolB1, $verbPoolB2, $verbPoolC1, $verbPoolC2,
           $tensesA1, $tensesA2, $tensesB1, $tensesB2, $tensesC1, $tensesC2;

    // Seed with dest number for reproducibility but variety
    srand($destNum * 7919);

    switch ($cefr) {
        case 'A1':
            $verbPool = $verbPoolA1;
            $tenses = $tensesA1;
            $title = "Los verbos de " . trim(preg_replace('/\{nombre\},?\s*/', '', $destTitle));
            $instruction = "Elige la forma correcta del verbo.";
            break;
        case 'A2':
            $verbPool = $verbPoolA2;
            $tenses = $tensesA2;
            $title = "Lo que pasó — conjugación";
            $instruction = "Conjuga el verbo en la forma correcta.";
            break;
        case 'B1':
            $verbPool = $verbPoolB1;
            $tenses = $tensesB1;
            $title = "Si pudiera... — subjuntivo y condicional";
            $instruction = "Conjuga el verbo en la forma correcta.";
            break;
        case 'B2':
            $verbPool = $verbPoolB2;
            $tenses = $tensesB2;
            $title = "Hipótesis y posibilidades";
            $instruction = "Conjuga el verbo en la forma correcta.";
            break;
        case 'C1':
            $verbPool = $verbPoolC1;
            $tenses = $tensesC1;
            $title = "El verbo en toda su complejidad";
            $instruction = "Conjuga el verbo en la forma correcta.";
            break;
        case 'C2':
            $verbPool = $verbPoolC2;
            $tenses = $tensesC2;
            $title = "El verbo como instrumento literario";
            $instruction = "Conjuga el verbo en la forma correcta.";
            break;
        default:
            $verbPool = $verbPoolA1;
            $tenses = $tensesA1;
            $title = "Conjugación";
            $instruction = "Conjuga el verbo en la forma correcta.";
    }

    // If A1 title is too short, use fallback
    if ($cefr === 'A1') {
        $cleaned = trim(preg_replace('/\{nombre\},?\s*/', '', $destTitle));
        if (strlen($cleaned) < 3) {
            $title = "Los verbos del bosque";
        } else {
            $title = "Los verbos de " . mb_strtolower(preg_replace('/^(el|la|los|las)\s+/i', '', $cleaned));
        }
    }

    $questions = [];
    $usedCombos = []; // track verb+subject+tense combos to avoid repeats

    // For C2: include 2 voseo questions and 2 futuro de subjuntivo questions
    if ($cefr === 'C2') {
        $voseoVerbs = array_keys($voseo);
        shuffle($voseoVerbs);
        $addedVoseo = 0;
        foreach ($voseoVerbs as $v) {
            $q = makeVoseoQuestion($v, $voseo, $conjugations, $verbPool);
            if ($q) {
                $questions[] = $q;
                $addedVoseo++;
                if ($addedVoseo >= 2) break;
            }
        }

        // Futuro de subjuntivo
        $futSubjVerbs = array_filter(array_keys($conjugations), fn($v) => isset($conjugations[$v]['futuro de subjuntivo']));
        $futSubjVerbs = array_values($futSubjVerbs);
        shuffle($futSubjVerbs);
        $addedFut = 0;
        foreach ($futSubjVerbs as $v) {
            $q = makeFutSubjQuestion($v, $conjugations, $subjects);
            if ($q) {
                $questions[] = $q;
                $addedFut++;
                if ($addedFut >= 2) break;
            }
        }
    }

    // Fill remaining slots
    $attempts = 0;
    while (count($questions) < 8 && $attempts < 200) {
        $attempts++;

        $verb = $verbPool[array_rand($verbPool)];
        if ($verb === 'llamarse' || $verb === 'gustar') {
            // Limited tenses, handle carefully
            if ($cefr !== 'A1') continue;
        }

        $tense = $tenses[array_rand($tenses)];
        $subjectIdx = rand(0, 5);

        // Check we have this conjugation
        if (!isset($conjugations[$verb][$tense][$subjectIdx])) continue;

        $combo = "$verb|$subjectIdx|$tense";
        if (isset($usedCombos[$combo])) continue;

        $q = makeQuestion($verb, $subjectIdx, $tense, $conjugations, $subjects, $verbPool);
        if (!$q) continue;

        $usedCombos[$combo] = true;
        $questions[] = $q;
    }

    // Ensure exactly 8
    $questions = array_slice($questions, 0, 8);

    // Shuffle questions
    shuffle($questions);

    return [
        'type' => 'conjugation',
        'label' => 'Conjugación',
        'title' => $title,
        'instruction' => $instruction,
        'questions' => $questions,
    ];
}

// ============================================================
// MAIN: Process all destinations
// ============================================================

$start = $singleDest ?? 1;
$end = $singleDest ?? 89;

$successCount = 0;
$errorCount = 0;

for ($d = $start; $d <= $end; $d++) {
    $file = "$contentDir/dest{$d}.json";

    if (!file_exists($file)) {
        echo "ERROR: $file not found\n";
        $errorCount++;
        continue;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!$data) {
        echo "ERROR: Failed to parse dest{$d}.json\n";
        $errorCount++;
        continue;
    }

    $cefr = $data['meta']['cefr'] ?? 'A1';
    $destTitle = $data['meta']['title'] ?? "destino $d";

    // Check if conjugation game already exists
    $hasConj = false;
    $conjIdx = -1;
    foreach ($data['games'] as $idx => $game) {
        if (($game['type'] ?? '') === 'conjugation') {
            $hasConj = true;
            $conjIdx = $idx;
            break;
        }
    }

    if ($hasConj) {
        echo "SKIP: dest{$d} already has conjugation game at position {$conjIdx}\n";
        $successCount++;
        continue;
    }

    // Build the conjugation game
    $conjGame = buildConjugationGame($d, $cefr, $destTitle);

    if (count($conjGame['questions']) < 8) {
        echo "WARNING: dest{$d} only got " . count($conjGame['questions']) . " questions (CEFR: $cefr)\n";
    }

    // Insert at position 3 (after game[2])
    $insertPos = min(3, count($data['games']));
    array_splice($data['games'], $insertPos, 0, [$conjGame]);

    if ($dryRun) {
        echo "DRY-RUN: dest{$d} ($cefr) — would insert conjugation game at position $insertPos with " . count($conjGame['questions']) . " questions: \"{$conjGame['title']}\"\n";
        $successCount++;
        continue;
    }

    // Write back
    $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($file, $output);

    echo "OK: dest{$d} ($cefr) — inserted conjugation at position $insertPos: \"{$conjGame['title']}\" [{$conjGame['questions'][0]['verb']}/{$conjGame['questions'][0]['context']}...]\n";
    $successCount++;
}

echo "\n=== SUMMARY ===\n";
echo "Success: $successCount\n";
echo "Errors: $errorCount\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE") . "\n";
