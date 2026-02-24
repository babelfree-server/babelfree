<?php
/**
 * Conjugation Generator — Generates conjugation JSON for Spanish verbs.
 * Produces accurate forms for regular, spelling-change, and stem-changing verbs.
 *
 * Run: php generate-conjugations.php
 * Output: Merges into data/conjugations_es.json
 */

$dataDir = __DIR__ . '/data';
$outFile = $dataDir . '/conjugations_es.json';

// Load existing conjugations
$existing = [];
if (file_exists($outFile)) {
    $existing = json_decode(file_get_contents($outFile), true) ?: [];
    echo "Loaded " . count($existing) . " existing verbs\n";
}

// ─── Conjugation engine ───────────────────────────────────────────

function conjugateAR(string $stem, array $opts = []): array {
    $sc = $opts['stem_change'] ?? null;    // e.g. ['e','ie'] or ['o','ue']
    $spell = $opts['spelling'] ?? null;     // e.g. ['g','gu'] or ['c','qu'] or ['z','c']

    // Stems for present tense (stem-changing: 1s,2s,3s,3p change; nosotros/vos keep)
    $ps = $stem; // present stem (regular)
    $psc = $stem; // present stem changed
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        // Find last occurrence of the changing vowel
        $pos = mb_strrpos($stem, $from);
        if ($pos !== false) {
            $psc = mb_substr($stem, 0, $pos) . $to . mb_substr($stem, $pos + mb_strlen($from));
        }
    }

    // Spelling change affects preterite 1s and subjunctive (before 'e')
    $pret1s = $stem . 'é';
    $subjStem = $stem;
    if ($spell) {
        $from = $spell[0];
        $to = $spell[1];
        // Replace at end of stem before adding ending with 'e'
        if (mb_substr($stem, -mb_strlen($from)) === $from) {
            $base = mb_substr($stem, 0, -mb_strlen($from));
            $pret1s = $base . $to . 'é';
            $subjStem = $base . $to;
        }
    }

    // Subjunctive present: stem-changing verbs also change in subj (1s,2s,3s,3p) but NOT nosotros
    $subjStemChanged = $subjStem;
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        $pos = mb_strrpos($subjStem, $from);
        if ($pos !== false) {
            $subjStemChanged = mb_substr($subjStem, 0, $pos) . $to . mb_substr($subjStem, $pos + mb_strlen($from));
        }
    }

    // Vos imperative: stem + á (accent on last syllable)
    $vosImp = $stem . 'á';

    return [
        'indicativo' => [
            'presente' => [
                'yo' => $psc . 'o',
                'tú' => $psc . 'as',
                'vos' => $ps . 'ás',
                'él' => $psc . 'a',
                'nosotros' => $ps . 'amos',
                'ustedes' => $psc . 'an',
                'ellos' => $psc . 'an',
            ],
            'pretérito indefinido' => [
                'yo' => $pret1s,
                'tú' => $stem . 'aste',
                'vos' => $stem . 'aste',
                'él' => $stem . 'ó',
                'nosotros' => $stem . 'amos',
                'ustedes' => $stem . 'aron',
                'ellos' => $stem . 'aron',
            ],
            'pretérito imperfecto' => [
                'yo' => $stem . 'aba',
                'tú' => $stem . 'abas',
                'vos' => $stem . 'abas',
                'él' => $stem . 'aba',
                'nosotros' => $stem . 'ábamos',
                'ustedes' => $stem . 'aban',
                'ellos' => $stem . 'aban',
            ],
            'futuro' => [
                'yo' => $stem . 'aré',
                'tú' => $stem . 'arás',
                'vos' => $stem . 'arás',
                'él' => $stem . 'ará',
                'nosotros' => $stem . 'aremos',
                'ustedes' => $stem . 'arán',
                'ellos' => $stem . 'arán',
            ],
            'condicional' => [
                'yo' => $stem . 'aría',
                'tú' => $stem . 'arías',
                'vos' => $stem . 'arías',
                'él' => $stem . 'aría',
                'nosotros' => $stem . 'aríamos',
                'ustedes' => $stem . 'arían',
                'ellos' => $stem . 'arían',
            ],
        ],
        'subjuntivo' => [
            'presente' => [
                'yo' => $subjStemChanged . 'e',
                'tú' => $subjStemChanged . 'es',
                'vos' => $subjStemChanged . 'es',
                'él' => $subjStemChanged . 'e',
                'nosotros' => $subjStem . 'emos',
                'ustedes' => $subjStemChanged . 'en',
                'ellos' => $subjStemChanged . 'en',
            ],
            'pretérito imperfecto' => [
                'yo' => $stem . 'ara',
                'tú' => $stem . 'aras',
                'vos' => $stem . 'aras',
                'él' => $stem . 'ara',
                'nosotros' => $stem . 'áramos',
                'ustedes' => $stem . 'aran',
                'ellos' => $stem . 'aran',
            ],
        ],
        'imperativo' => [
            'afirmativo' => [
                'tú' => $psc . 'a',
                'vos' => $vosImp,
                'él' => $subjStemChanged . 'e',
                'nosotros' => $subjStem . 'emos',
                'ustedes' => $subjStemChanged . 'en',
                'ellos' => $subjStemChanged . 'en',
            ],
        ],
    ];
}

function conjugateER(string $stem, array $opts = []): array {
    $sc = $opts['stem_change'] ?? null;
    $spell = $opts['spelling'] ?? null;

    $ps = $stem;
    $psc = $stem;
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        $pos = mb_strrpos($stem, $from);
        if ($pos !== false) {
            $psc = mb_substr($stem, 0, $pos) . $to . mb_substr($stem, $pos + mb_strlen($from));
        }
    }

    $subjStem = $stem;
    $subjStemChanged = $subjStem;
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        $pos = mb_strrpos($subjStem, $from);
        if ($pos !== false) {
            $subjStemChanged = mb_substr($subjStem, 0, $pos) . $to . mb_substr($subjStem, $pos + mb_strlen($from));
        }
    }

    return [
        'indicativo' => [
            'presente' => [
                'yo' => $psc . 'o',
                'tú' => $psc . 'es',
                'vos' => $ps . 'és',
                'él' => $psc . 'e',
                'nosotros' => $ps . 'emos',
                'ustedes' => $psc . 'en',
                'ellos' => $psc . 'en',
            ],
            'pretérito indefinido' => [
                'yo' => $stem . 'í',
                'tú' => $stem . 'iste',
                'vos' => $stem . 'iste',
                'él' => $stem . 'ió',
                'nosotros' => $stem . 'imos',
                'ustedes' => $stem . 'ieron',
                'ellos' => $stem . 'ieron',
            ],
            'pretérito imperfecto' => [
                'yo' => $stem . 'ía',
                'tú' => $stem . 'ías',
                'vos' => $stem . 'ías',
                'él' => $stem . 'ía',
                'nosotros' => $stem . 'íamos',
                'ustedes' => $stem . 'ían',
                'ellos' => $stem . 'ían',
            ],
            'futuro' => [
                'yo' => $stem . 'eré',
                'tú' => $stem . 'erás',
                'vos' => $stem . 'erás',
                'él' => $stem . 'erá',
                'nosotros' => $stem . 'eremos',
                'ustedes' => $stem . 'erán',
                'ellos' => $stem . 'erán',
            ],
            'condicional' => [
                'yo' => $stem . 'ería',
                'tú' => $stem . 'erías',
                'vos' => $stem . 'erías',
                'él' => $stem . 'ería',
                'nosotros' => $stem . 'eríamos',
                'ustedes' => $stem . 'erían',
                'ellos' => $stem . 'erían',
            ],
        ],
        'subjuntivo' => [
            'presente' => [
                'yo' => $subjStemChanged . 'a',
                'tú' => $subjStemChanged . 'as',
                'vos' => $subjStemChanged . 'as',
                'él' => $subjStemChanged . 'a',
                'nosotros' => $subjStem . 'amos',
                'ustedes' => $subjStemChanged . 'an',
                'ellos' => $subjStemChanged . 'an',
            ],
            'pretérito imperfecto' => [
                'yo' => $stem . 'iera',
                'tú' => $stem . 'ieras',
                'vos' => $stem . 'ieras',
                'él' => $stem . 'iera',
                'nosotros' => $stem . 'iéramos',
                'ustedes' => $stem . 'ieran',
                'ellos' => $stem . 'ieran',
            ],
        ],
        'imperativo' => [
            'afirmativo' => [
                'tú' => $psc . 'e',
                'vos' => $ps . 'é',
                'él' => $subjStemChanged . 'a',
                'nosotros' => $subjStem . 'amos',
                'ustedes' => $subjStemChanged . 'an',
                'ellos' => $subjStemChanged . 'an',
            ],
        ],
    ];
}

function conjugateIR(string $stem, array $opts = []): array {
    $sc = $opts['stem_change'] ?? null;
    // For -ir stem-changing verbs, preterite 3s/3p also change (e→i, o→u)
    $pretSc = $opts['pret_stem_change'] ?? null;

    $ps = $stem;
    $psc = $stem;
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        $pos = mb_strrpos($stem, $from);
        if ($pos !== false) {
            $psc = mb_substr($stem, 0, $pos) . $to . mb_substr($stem, $pos + mb_strlen($from));
        }
    }

    $pretStem = $stem;
    if ($pretSc) {
        $from = $pretSc[0];
        $to = $pretSc[1];
        $pos = mb_strrpos($stem, $from);
        if ($pos !== false) {
            $pretStem = mb_substr($stem, 0, $pos) . $to . mb_substr($stem, $pos + mb_strlen($from));
        }
    }

    $subjStem = $stem;
    $subjStemChanged = $subjStem;
    if ($sc) {
        $from = $sc[0];
        $to = $sc[1];
        $pos = mb_strrpos($subjStem, $from);
        if ($pos !== false) {
            $subjStemChanged = mb_substr($subjStem, 0, $pos) . $to . mb_substr($subjStem, $pos + mb_strlen($from));
        }
    }
    // For -ir stem-changers, subjunctive nosotros uses the preterite stem change
    $subjNos = $stem;
    if ($pretSc) {
        $from = $pretSc[0];
        $to = $pretSc[1];
        $pos = mb_strrpos($stem, $from);
        if ($pos !== false) {
            $subjNos = mb_substr($stem, 0, $pos) . $to . mb_substr($stem, $pos + mb_strlen($from));
        }
    }

    // Subjunctive imperfect uses preterite stem
    $subjImpStem = $pretSc ? $pretStem : $stem;

    return [
        'indicativo' => [
            'presente' => [
                'yo' => $psc . 'o',
                'tú' => $psc . 'es',
                'vos' => $ps . 'ís',
                'él' => $psc . 'e',
                'nosotros' => $ps . 'imos',
                'ustedes' => $psc . 'en',
                'ellos' => $psc . 'en',
            ],
            'pretérito indefinido' => [
                'yo' => $stem . 'í',
                'tú' => $stem . 'iste',
                'vos' => $stem . 'iste',
                'él' => ($pretSc ? $pretStem : $stem) . 'ió',
                'nosotros' => $stem . 'imos',
                'ustedes' => ($pretSc ? $pretStem : $stem) . 'ieron',
                'ellos' => ($pretSc ? $pretStem : $stem) . 'ieron',
            ],
            'pretérito imperfecto' => [
                'yo' => $stem . 'ía',
                'tú' => $stem . 'ías',
                'vos' => $stem . 'ías',
                'él' => $stem . 'ía',
                'nosotros' => $stem . 'íamos',
                'ustedes' => $stem . 'ían',
                'ellos' => $stem . 'ían',
            ],
            'futuro' => [
                'yo' => $stem . 'iré',
                'tú' => $stem . 'irás',
                'vos' => $stem . 'irás',
                'él' => $stem . 'irá',
                'nosotros' => $stem . 'iremos',
                'ustedes' => $stem . 'irán',
                'ellos' => $stem . 'irán',
            ],
            'condicional' => [
                'yo' => $stem . 'iría',
                'tú' => $stem . 'irías',
                'vos' => $stem . 'irías',
                'él' => $stem . 'iría',
                'nosotros' => $stem . 'iríamos',
                'ustedes' => $stem . 'irían',
                'ellos' => $stem . 'irían',
            ],
        ],
        'subjuntivo' => [
            'presente' => [
                'yo' => $subjStemChanged . 'a',
                'tú' => $subjStemChanged . 'as',
                'vos' => $subjStemChanged . 'as',
                'él' => $subjStemChanged . 'a',
                'nosotros' => ($pretSc ? $subjNos : $subjStem) . 'amos',
                'ustedes' => $subjStemChanged . 'an',
                'ellos' => $subjStemChanged . 'an',
            ],
            'pretérito imperfecto' => [
                'yo' => $subjImpStem . 'iera',
                'tú' => $subjImpStem . 'ieras',
                'vos' => $subjImpStem . 'ieras',
                'él' => $subjImpStem . 'iera',
                'nosotros' => $subjImpStem . 'iéramos',
                'ustedes' => $subjImpStem . 'ieran',
                'ellos' => $subjImpStem . 'ieran',
            ],
        ],
        'imperativo' => [
            'afirmativo' => [
                'tú' => $psc . 'e',
                'vos' => $ps . 'í',
                'él' => $subjStemChanged . 'a',
                'nosotros' => ($pretSc ? $subjNos : $subjStem) . 'amos',
                'ustedes' => $subjStemChanged . 'an',
                'ellos' => $subjStemChanged . 'an',
            ],
        ],
    ];
}

// ─── Define the 50 verbs ──────────────────────────────────────────

$verbs = [];

// === Regular -AR ===
$regularAR = ['pasar','llevar','dejar','llamar','tratar','mirar','esperar',
              'entrar','trabajar','estudiar','comprar','usar','bajar',
              'ganar','olvidar','cambiar','escuchar','terminar','crear',
              'aceptar','evitar'];
foreach ($regularAR as $v) {
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateAR($stem);
    $verbs[$v]['is_irregular'] = false;
}

// === Regular -AR with spelling changes ===
// llegar: g→gu before e
$stem = 'lleg';
$data = conjugateAR($stem, ['spelling' => ['g', 'gu']]);
$data['is_irregular'] = false;
$verbs['llegar'] = $data;

// buscar: c→qu before e
$stem = 'busc';
$data = conjugateAR($stem, ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['buscar'] = $data;

// pagar: g→gu before e
$stem = 'pag';
$data = conjugateAR($stem, ['spelling' => ['g', 'gu']]);
$data['is_irregular'] = false;
$verbs['pagar'] = $data;

// explicar: c→qu before e
$stem = 'explic';
$data = conjugateAR($stem, ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['explicar'] = $data;

// === Stem-changing -AR (e→ie) ===
// pensar
$data = conjugateAR('pens', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['pensar'] = $data;

// cerrar
$data = conjugateAR('cerr', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['cerrar'] = $data;

// empezar: e→ie + z→c before e
$data = conjugateAR('empez', ['stem_change' => ['e', 'ie'], 'spelling' => ['z', 'c']]);
$data['is_irregular'] = true;
// Fix preterite 1s manually: empecé
$data['indicativo']['pretérito indefinido']['yo'] = 'empecé';
$verbs['empezar'] = $data;

// === Stem-changing -AR (o→ue) ===
// encontrar
$data = conjugateAR('encontr', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['encontrar'] = $data;

// contar
$data = conjugateAR('cont', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['contar'] = $data;

// recordar
$data = conjugateAR('record', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['recordar'] = $data;

// mostrar
$data = conjugateAR('mostr', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['mostrar'] = $data;

// === Regular -ER ===
$regularER = ['aprender', 'vender'];
foreach ($regularER as $v) {
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateER($stem);
    $verbs[$v]['is_irregular'] = false;
}

// creer: regular -er but with y in 3rd person preterite (creyó, creyeron)
$data = conjugateER('cre');
$data['is_irregular'] = false;
// Fix preterite: creyó, creyeron (hiatus breaking y)
$data['indicativo']['pretérito indefinido']['él'] = 'creyó';
$data['indicativo']['pretérito indefinido']['ustedes'] = 'creyeron';
$data['indicativo']['pretérito indefinido']['ellos'] = 'creyeron';
// Also gerund would be creyendo, but we don't track gerunds
$verbs['creer'] = $data;

// leer: same pattern as creer
$data = conjugateER('le');
$data['is_irregular'] = false;
$data['indicativo']['pretérito indefinido']['él'] = 'leyó';
$data['indicativo']['pretérito indefinido']['ustedes'] = 'leyeron';
$data['indicativo']['pretérito indefinido']['ellos'] = 'leyeron';
$verbs['leer'] = $data;

// === Stem-changing -ER (e→ie) ===
// perder
$data = conjugateER('perd', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['perder'] = $data;

// === Regular -IR ===
$regularIR = ['existir', 'escribir', 'subir', 'permitir', 'decidir'];
foreach ($regularIR as $v) {
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateIR($stem);
    $verbs[$v]['is_irregular'] = false;
}

// abrir: regular -ir (irregular participle abierto, but we don't track that)
$data = conjugateIR('abr');
$data['is_irregular'] = false;
$verbs['abrir'] = $data;

// === Irregular -IR ===

// seguir: e→i stem change + gu→g before a/o
$data = conjugateIR('sig', [
    'stem_change' => ['i', 'i'],  // already changed to 'i' in stem 'sig'
    'pret_stem_change' => ['i', 'i'],
]);
$data['is_irregular'] = true;
// Actually seguir is special: sigo, sigues, sigue, seguimos, siguen
// Let me override manually
$data['indicativo']['presente'] = [
    'yo' => 'sigo', 'tú' => 'sigues', 'vos' => 'seguís',
    'él' => 'sigue', 'nosotros' => 'seguimos', 'ustedes' => 'siguen', 'ellos' => 'siguen',
];
$data['indicativo']['pretérito indefinido'] = [
    'yo' => 'seguí', 'tú' => 'seguiste', 'vos' => 'seguiste',
    'él' => 'siguió', 'nosotros' => 'seguimos', 'ustedes' => 'siguieron', 'ellos' => 'siguieron',
];
$data['indicativo']['pretérito imperfecto'] = [
    'yo' => 'seguía', 'tú' => 'seguías', 'vos' => 'seguías',
    'él' => 'seguía', 'nosotros' => 'seguíamos', 'ustedes' => 'seguían', 'ellos' => 'seguían',
];
$data['indicativo']['futuro'] = [
    'yo' => 'seguiré', 'tú' => 'seguirás', 'vos' => 'seguirás',
    'él' => 'seguirá', 'nosotros' => 'seguiremos', 'ustedes' => 'seguirán', 'ellos' => 'seguirán',
];
$data['indicativo']['condicional'] = [
    'yo' => 'seguiría', 'tú' => 'seguirías', 'vos' => 'seguirías',
    'él' => 'seguiría', 'nosotros' => 'seguiríamos', 'ustedes' => 'seguirían', 'ellos' => 'seguirían',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'siga', 'tú' => 'sigas', 'vos' => 'sigas',
    'él' => 'siga', 'nosotros' => 'sigamos', 'ustedes' => 'sigan', 'ellos' => 'sigan',
];
$data['subjuntivo']['pretérito imperfecto'] = [
    'yo' => 'siguiera', 'tú' => 'siguieras', 'vos' => 'siguieras',
    'él' => 'siguiera', 'nosotros' => 'siguiéramos', 'ustedes' => 'siguieran', 'ellos' => 'siguieran',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'sigue', 'vos' => 'seguí', 'él' => 'siga',
    'nosotros' => 'sigamos', 'ustedes' => 'sigan', 'ellos' => 'sigan',
];
$verbs['seguir'] = $data;

// sentir: e→ie present, e→i preterite/subjunctive
$data = conjugateIR('sent', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['sentir'] = $data;

// caer: caigo in present, cayó in preterite
$data = conjugateER('ca'); // base like -er for preterite pattern
$data['is_irregular'] = true;
// Override everything properly
$data['indicativo']['presente'] = [
    'yo' => 'caigo', 'tú' => 'caes', 'vos' => 'caés',
    'él' => 'cae', 'nosotros' => 'caemos', 'ustedes' => 'caen', 'ellos' => 'caen',
];
$data['indicativo']['pretérito indefinido'] = [
    'yo' => 'caí', 'tú' => 'caíste', 'vos' => 'caíste',
    'él' => 'cayó', 'nosotros' => 'caímos', 'ustedes' => 'cayeron', 'ellos' => 'cayeron',
];
$data['indicativo']['pretérito imperfecto'] = [
    'yo' => 'caía', 'tú' => 'caías', 'vos' => 'caías',
    'él' => 'caía', 'nosotros' => 'caíamos', 'ustedes' => 'caían', 'ellos' => 'caían',
];
$data['indicativo']['futuro'] = [
    'yo' => 'caeré', 'tú' => 'caerás', 'vos' => 'caerás',
    'él' => 'caerá', 'nosotros' => 'caeremos', 'ustedes' => 'caerán', 'ellos' => 'caerán',
];
$data['indicativo']['condicional'] = [
    'yo' => 'caería', 'tú' => 'caerías', 'vos' => 'caerías',
    'él' => 'caería', 'nosotros' => 'caeríamos', 'ustedes' => 'caerían', 'ellos' => 'caerían',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'caiga', 'tú' => 'caigas', 'vos' => 'caigas',
    'él' => 'caiga', 'nosotros' => 'caigamos', 'ustedes' => 'caigan', 'ellos' => 'caigan',
];
$data['subjuntivo']['pretérito imperfecto'] = [
    'yo' => 'cayera', 'tú' => 'cayeras', 'vos' => 'cayeras',
    'él' => 'cayera', 'nosotros' => 'cayéramos', 'ustedes' => 'cayeran', 'ellos' => 'cayeran',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'cae', 'vos' => 'caé', 'él' => 'caiga',
    'nosotros' => 'caigamos', 'ustedes' => 'caigan', 'ellos' => 'caigan',
];
$verbs['caer'] = $data;

// ofrecer: -zco verb (ofrezco)
$data = conjugateER('ofrec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'ofrezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'ofrezca', 'tú' => 'ofrezcas', 'vos' => 'ofrezcas',
    'él' => 'ofrezca', 'nosotros' => 'ofrezcamos', 'ustedes' => 'ofrezcan', 'ellos' => 'ofrezcan',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'ofrece', 'vos' => 'ofrecé', 'él' => 'ofrezca',
    'nosotros' => 'ofrezcamos', 'ustedes' => 'ofrezcan', 'ellos' => 'ofrezcan',
];
$verbs['ofrecer'] = $data;

// === Batch 2 regular -AR ===
$batch2AR = ['entender','imaginar','considerar','suponer','temer','desear',
             'necesitar','ayudar','apoyar','cuidar','respetar','controlar',
             'organizar','preparar','participar','colaborar','intentar',
             'lograr','fallar','mejorar','empeorar','aumentar',
             'mover','parar','quedar','viajar','caminar','correr',
             'despertar','descansar','gritar','arreglar','tirar','empujar','jalar'];
foreach ($batch2AR as $v) {
    if (in_array($v, ['entender','temer','correr'])) continue; // -ER verbs, handle below
    if (in_array($v, ['suponer','mover','resolver','reducir','mantener','elegir',
                       'conducir','construir','destruir','salir','volver','regresar'])) continue; // irregular, handle below
    $stem = mb_substr($v, 0, -2);
    $conj = conjugateAR($stem);
    $conj['is_irregular'] = false;
    $verbs[$v] = $conj;
}

// Batch 2 regular -ER
foreach (['temer','correr'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $conj = conjugateER($stem);
    $conj['is_irregular'] = false;
    $verbs[$v] = $conj;
}

// entender: e→ie stem change -ER
$data = conjugateER('entend', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['entender'] = $data;

// preferir: e→ie present, e→i preterite -IR
$data = conjugateIR('prefer', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['preferir'] = $data;

// reducir: -zco verb + irregular preterite (reduje)
$verbs['reducir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'reduzco', 'tú' => 'reduces', 'vos' => 'reducís',
            'él' => 'reduce', 'nosotros' => 'reducimos', 'ustedes' => 'reducen', 'ellos' => 'reducen',
        ],
        'pretérito indefinido' => [
            'yo' => 'reduje', 'tú' => 'redujiste', 'vos' => 'redujiste',
            'él' => 'redujo', 'nosotros' => 'redujimos', 'ustedes' => 'redujeron', 'ellos' => 'redujeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'reducía', 'tú' => 'reducías', 'vos' => 'reducías',
            'él' => 'reducía', 'nosotros' => 'reducíamos', 'ustedes' => 'reducían', 'ellos' => 'reducían',
        ],
        'futuro' => [
            'yo' => 'reduciré', 'tú' => 'reducirás', 'vos' => 'reducirás',
            'él' => 'reducirá', 'nosotros' => 'reduciremos', 'ustedes' => 'reducirán', 'ellos' => 'reducirán',
        ],
        'condicional' => [
            'yo' => 'reduciría', 'tú' => 'reducirías', 'vos' => 'reducirías',
            'él' => 'reduciría', 'nosotros' => 'reduciríamos', 'ustedes' => 'reducirían', 'ellos' => 'reducirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'reduzca', 'tú' => 'reduzcas', 'vos' => 'reduzcas',
            'él' => 'reduzca', 'nosotros' => 'reduzcamos', 'ustedes' => 'reduzcan', 'ellos' => 'reduzcan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'redujera', 'tú' => 'redujeras', 'vos' => 'redujeras',
            'él' => 'redujera', 'nosotros' => 'redujéramos', 'ustedes' => 'redujeran', 'ellos' => 'redujeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'reduce', 'vos' => 'reducí', 'él' => 'reduzca',
            'nosotros' => 'reduzcamos', 'ustedes' => 'reduzcan', 'ellos' => 'reduzcan',
        ],
    ],
];

// mantener (like tener)
$verbs['mantener'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'mantengo', 'tú' => 'mantienes', 'vos' => 'mantenés',
            'él' => 'mantiene', 'nosotros' => 'mantenemos', 'ustedes' => 'mantienen', 'ellos' => 'mantienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'mantuve', 'tú' => 'mantuviste', 'vos' => 'mantuviste',
            'él' => 'mantuvo', 'nosotros' => 'mantuvimos', 'ustedes' => 'mantuvieron', 'ellos' => 'mantuvieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'mantenía', 'tú' => 'mantenías', 'vos' => 'mantenías',
            'él' => 'mantenía', 'nosotros' => 'manteníamos', 'ustedes' => 'mantenían', 'ellos' => 'mantenían',
        ],
        'futuro' => [
            'yo' => 'mantendré', 'tú' => 'mantendrás', 'vos' => 'mantendrás',
            'él' => 'mantendrá', 'nosotros' => 'mantendremos', 'ustedes' => 'mantendrán', 'ellos' => 'mantendrán',
        ],
        'condicional' => [
            'yo' => 'mantendría', 'tú' => 'mantendrías', 'vos' => 'mantendrías',
            'él' => 'mantendría', 'nosotros' => 'mantendríamos', 'ustedes' => 'mantendrían', 'ellos' => 'mantendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'mantenga', 'tú' => 'mantengas', 'vos' => 'mantengas',
            'él' => 'mantenga', 'nosotros' => 'mantengamos', 'ustedes' => 'mantengan', 'ellos' => 'mantengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'mantuviera', 'tú' => 'mantuvieras', 'vos' => 'mantuvieras',
            'él' => 'mantuviera', 'nosotros' => 'mantuviéramos', 'ustedes' => 'mantuvieran', 'ellos' => 'mantuvieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'mantén', 'vos' => 'mantené', 'él' => 'mantenga',
            'nosotros' => 'mantengamos', 'ustedes' => 'mantengan', 'ellos' => 'mantengan',
        ],
    ],
];

// resolver: o→ue stem change -ER
$data = conjugateER('resolv', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['resolver'] = $data;

// elegir: e→i stem change -IR + g→j before a/o
$verbs['elegir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'elijo', 'tú' => 'eliges', 'vos' => 'elegís',
            'él' => 'elige', 'nosotros' => 'elegimos', 'ustedes' => 'eligen', 'ellos' => 'eligen',
        ],
        'pretérito indefinido' => [
            'yo' => 'elegí', 'tú' => 'elegiste', 'vos' => 'elegiste',
            'él' => 'eligió', 'nosotros' => 'elegimos', 'ustedes' => 'eligieron', 'ellos' => 'eligieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'elegía', 'tú' => 'elegías', 'vos' => 'elegías',
            'él' => 'elegía', 'nosotros' => 'elegíamos', 'ustedes' => 'elegían', 'ellos' => 'elegían',
        ],
        'futuro' => [
            'yo' => 'elegiré', 'tú' => 'elegirás', 'vos' => 'elegirás',
            'él' => 'elegirá', 'nosotros' => 'elegiremos', 'ustedes' => 'elegirán', 'ellos' => 'elegirán',
        ],
        'condicional' => [
            'yo' => 'elegiría', 'tú' => 'elegirías', 'vos' => 'elegirías',
            'él' => 'elegiría', 'nosotros' => 'elegiríamos', 'ustedes' => 'elegirían', 'ellos' => 'elegirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'elija', 'tú' => 'elijas', 'vos' => 'elijas',
            'él' => 'elija', 'nosotros' => 'elijamos', 'ustedes' => 'elijan', 'ellos' => 'elijan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'eligiera', 'tú' => 'eligieras', 'vos' => 'eligieras',
            'él' => 'eligiera', 'nosotros' => 'eligiéramos', 'ustedes' => 'eligieran', 'ellos' => 'eligieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'elige', 'vos' => 'elegí', 'él' => 'elija',
            'nosotros' => 'elijamos', 'ustedes' => 'elijan', 'ellos' => 'elijan',
        ],
    ],
];

// suponer (like poner)
$verbs['suponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'supongo', 'tú' => 'supones', 'vos' => 'suponés',
            'él' => 'supone', 'nosotros' => 'suponemos', 'ustedes' => 'suponen', 'ellos' => 'suponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'supuse', 'tú' => 'supusiste', 'vos' => 'supusiste',
            'él' => 'supuso', 'nosotros' => 'supusimos', 'ustedes' => 'supusieron', 'ellos' => 'supusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'suponía', 'tú' => 'suponías', 'vos' => 'suponías',
            'él' => 'suponía', 'nosotros' => 'suponíamos', 'ustedes' => 'suponían', 'ellos' => 'suponían',
        ],
        'futuro' => [
            'yo' => 'supondré', 'tú' => 'supondrás', 'vos' => 'supondrás',
            'él' => 'supondrá', 'nosotros' => 'supondremos', 'ustedes' => 'supondrán', 'ellos' => 'supondrán',
        ],
        'condicional' => [
            'yo' => 'supondría', 'tú' => 'supondrías', 'vos' => 'supondrías',
            'él' => 'supondría', 'nosotros' => 'supondríamos', 'ustedes' => 'supondrían', 'ellos' => 'supondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'suponga', 'tú' => 'supongas', 'vos' => 'supongas',
            'él' => 'suponga', 'nosotros' => 'supongamos', 'ustedes' => 'supongan', 'ellos' => 'supongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'supusiera', 'tú' => 'supusieras', 'vos' => 'supusieras',
            'él' => 'supusiera', 'nosotros' => 'supusiéramos', 'ustedes' => 'supusieran', 'ellos' => 'supusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'supón', 'vos' => 'suponé', 'él' => 'suponga',
            'nosotros' => 'supongamos', 'ustedes' => 'supongan', 'ellos' => 'supongan',
        ],
    ],
];

// conducir (-zco + irregular preterite like reducir)
$verbs['conducir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'conduzco', 'tú' => 'conduces', 'vos' => 'conducís',
            'él' => 'conduce', 'nosotros' => 'conducimos', 'ustedes' => 'conducen', 'ellos' => 'conducen',
        ],
        'pretérito indefinido' => [
            'yo' => 'conduje', 'tú' => 'condujiste', 'vos' => 'condujiste',
            'él' => 'condujo', 'nosotros' => 'condujimos', 'ustedes' => 'condujeron', 'ellos' => 'condujeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'conducía', 'tú' => 'conducías', 'vos' => 'conducías',
            'él' => 'conducía', 'nosotros' => 'conducíamos', 'ustedes' => 'conducían', 'ellos' => 'conducían',
        ],
        'futuro' => [
            'yo' => 'conduciré', 'tú' => 'conducirás', 'vos' => 'conducirás',
            'él' => 'conducirá', 'nosotros' => 'conduciremos', 'ustedes' => 'conducirán', 'ellos' => 'conducirán',
        ],
        'condicional' => [
            'yo' => 'conduciría', 'tú' => 'conducirías', 'vos' => 'conducirías',
            'él' => 'conduciría', 'nosotros' => 'conduciríamos', 'ustedes' => 'conducirían', 'ellos' => 'conducirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'conduzca', 'tú' => 'conduzcas', 'vos' => 'conduzcas',
            'él' => 'conduzca', 'nosotros' => 'conduzcamos', 'ustedes' => 'conduzcan', 'ellos' => 'conduzcan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'condujera', 'tú' => 'condujeras', 'vos' => 'condujeras',
            'él' => 'condujera', 'nosotros' => 'condujéramos', 'ustedes' => 'condujeran', 'ellos' => 'condujeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'conduce', 'vos' => 'conducí', 'él' => 'conduzca',
            'nosotros' => 'conduzcamos', 'ustedes' => 'conduzcan', 'ellos' => 'conduzcan',
        ],
    ],
];

// salir (irregular)
$verbs['salir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'salgo', 'tú' => 'sales', 'vos' => 'salís',
            'él' => 'sale', 'nosotros' => 'salimos', 'ustedes' => 'salen', 'ellos' => 'salen',
        ],
        'pretérito indefinido' => [
            'yo' => 'salí', 'tú' => 'saliste', 'vos' => 'saliste',
            'él' => 'salió', 'nosotros' => 'salimos', 'ustedes' => 'salieron', 'ellos' => 'salieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'salía', 'tú' => 'salías', 'vos' => 'salías',
            'él' => 'salía', 'nosotros' => 'salíamos', 'ustedes' => 'salían', 'ellos' => 'salían',
        ],
        'futuro' => [
            'yo' => 'saldré', 'tú' => 'saldrás', 'vos' => 'saldrás',
            'él' => 'saldrá', 'nosotros' => 'saldremos', 'ustedes' => 'saldrán', 'ellos' => 'saldrán',
        ],
        'condicional' => [
            'yo' => 'saldría', 'tú' => 'saldrías', 'vos' => 'saldrías',
            'él' => 'saldría', 'nosotros' => 'saldríamos', 'ustedes' => 'saldrían', 'ellos' => 'saldrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'salga', 'tú' => 'salgas', 'vos' => 'salgas',
            'él' => 'salga', 'nosotros' => 'salgamos', 'ustedes' => 'salgan', 'ellos' => 'salgan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'saliera', 'tú' => 'salieras', 'vos' => 'salieras',
            'él' => 'saliera', 'nosotros' => 'saliéramos', 'ustedes' => 'salieran', 'ellos' => 'salieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'sal', 'vos' => 'salí', 'él' => 'salga',
            'nosotros' => 'salgamos', 'ustedes' => 'salgan', 'ellos' => 'salgan',
        ],
    ],
];

// volver: o→ue stem change -ER
$data = conjugateER('volv', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['volver'] = $data;

// regresar: regular -AR
$data = conjugateAR('regres');
$data['is_irregular'] = false;
$verbs['regresar'] = $data;

// Reflexive base verbs (conjugated without the "se")
// sentarse: stem-changing e→ie -AR
$data = conjugateAR('sent', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['sentarse'] = $data;

// levantarse: regular -AR
$data = conjugateAR('levant');
$data['is_irregular'] = false;
$verbs['levantarse'] = $data;

// acostarse: o→ue -AR
$data = conjugateAR('acost', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['acostarse'] = $data;

// mover: o→ue -ER
$data = conjugateER('mov', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['mover'] = $data;

// reír (irregular -IR)
$verbs['reír'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'río', 'tú' => 'ríes', 'vos' => 'reís',
            'él' => 'ríe', 'nosotros' => 'reímos', 'ustedes' => 'ríen', 'ellos' => 'ríen',
        ],
        'pretérito indefinido' => [
            'yo' => 'reí', 'tú' => 'reíste', 'vos' => 'reíste',
            'él' => 'rio', 'nosotros' => 'reímos', 'ustedes' => 'rieron', 'ellos' => 'rieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'reía', 'tú' => 'reías', 'vos' => 'reías',
            'él' => 'reía', 'nosotros' => 'reíamos', 'ustedes' => 'reían', 'ellos' => 'reían',
        ],
        'futuro' => [
            'yo' => 'reiré', 'tú' => 'reirás', 'vos' => 'reirás',
            'él' => 'reirá', 'nosotros' => 'reiremos', 'ustedes' => 'reirán', 'ellos' => 'reirán',
        ],
        'condicional' => [
            'yo' => 'reiría', 'tú' => 'reirías', 'vos' => 'reirías',
            'él' => 'reiría', 'nosotros' => 'reiríamos', 'ustedes' => 'reirían', 'ellos' => 'reirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'ría', 'tú' => 'rías', 'vos' => 'rías',
            'él' => 'ría', 'nosotros' => 'riamos', 'ustedes' => 'rían', 'ellos' => 'rían',
        ],
        'pretérito imperfecto' => [
            'yo' => 'riera', 'tú' => 'rieras', 'vos' => 'rieras',
            'él' => 'riera', 'nosotros' => 'riéramos', 'ustedes' => 'rieran', 'ellos' => 'rieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'ríe', 'vos' => 'reí', 'él' => 'ría',
            'nosotros' => 'riamos', 'ustedes' => 'rían', 'ellos' => 'rían',
        ],
    ],
];

// sonreír (like reír)
$verbs['sonreír'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'sonrío', 'tú' => 'sonríes', 'vos' => 'sonreís',
            'él' => 'sonríe', 'nosotros' => 'sonreímos', 'ustedes' => 'sonríen', 'ellos' => 'sonríen',
        ],
        'pretérito indefinido' => [
            'yo' => 'sonreí', 'tú' => 'sonreíste', 'vos' => 'sonreíste',
            'él' => 'sonrio', 'nosotros' => 'sonreímos', 'ustedes' => 'sonrieron', 'ellos' => 'sonrieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'sonreía', 'tú' => 'sonreías', 'vos' => 'sonreías',
            'él' => 'sonreía', 'nosotros' => 'sonreíamos', 'ustedes' => 'sonreían', 'ellos' => 'sonreían',
        ],
        'futuro' => [
            'yo' => 'sonreiré', 'tú' => 'sonreirás', 'vos' => 'sonreirás',
            'él' => 'sonreirá', 'nosotros' => 'sonreiremos', 'ustedes' => 'sonreirán', 'ellos' => 'sonreirán',
        ],
        'condicional' => [
            'yo' => 'sonreiría', 'tú' => 'sonreirías', 'vos' => 'sonreirías',
            'él' => 'sonreiría', 'nosotros' => 'sonreiríamos', 'ustedes' => 'sonreirían', 'ellos' => 'sonreirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'sonría', 'tú' => 'sonrías', 'vos' => 'sonrías',
            'él' => 'sonría', 'nosotros' => 'sonriamos', 'ustedes' => 'sonrían', 'ellos' => 'sonrían',
        ],
        'pretérito imperfecto' => [
            'yo' => 'sonriera', 'tú' => 'sonrieras', 'vos' => 'sonrieras',
            'él' => 'sonriera', 'nosotros' => 'sonriéramos', 'ustedes' => 'sonrieran', 'ellos' => 'sonrieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'sonríe', 'vos' => 'sonreí', 'él' => 'sonría',
            'nosotros' => 'sonriamos', 'ustedes' => 'sonrían', 'ellos' => 'sonrían',
        ],
    ],
];

// llorar: regular -AR
$data = conjugateAR('llor');
$data['is_irregular'] = false;
$verbs['llorar'] = $data;

// romper: regular -ER (irregular participle roto, but we don't track that)
$data = conjugateER('romp');
$data['is_irregular'] = false;
$verbs['romper'] = $data;

// construir: -uir verb (y insertion)
$verbs['construir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'construyo', 'tú' => 'construyes', 'vos' => 'construís',
            'él' => 'construye', 'nosotros' => 'construimos', 'ustedes' => 'construyen', 'ellos' => 'construyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'construí', 'tú' => 'construiste', 'vos' => 'construiste',
            'él' => 'construyó', 'nosotros' => 'construimos', 'ustedes' => 'construyeron', 'ellos' => 'construyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'construía', 'tú' => 'construías', 'vos' => 'construías',
            'él' => 'construía', 'nosotros' => 'construíamos', 'ustedes' => 'construían', 'ellos' => 'construían',
        ],
        'futuro' => [
            'yo' => 'construiré', 'tú' => 'construirás', 'vos' => 'construirás',
            'él' => 'construirá', 'nosotros' => 'construiremos', 'ustedes' => 'construirán', 'ellos' => 'construirán',
        ],
        'condicional' => [
            'yo' => 'construiría', 'tú' => 'construirías', 'vos' => 'construirías',
            'él' => 'construiría', 'nosotros' => 'construiríamos', 'ustedes' => 'construirían', 'ellos' => 'construirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'construya', 'tú' => 'construyas', 'vos' => 'construyas',
            'él' => 'construya', 'nosotros' => 'construyamos', 'ustedes' => 'construyan', 'ellos' => 'construyan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'construyera', 'tú' => 'construyeras', 'vos' => 'construyeras',
            'él' => 'construyera', 'nosotros' => 'construyéramos', 'ustedes' => 'construyeran', 'ellos' => 'construyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'construye', 'vos' => 'construí', 'él' => 'construya',
            'nosotros' => 'construyamos', 'ustedes' => 'construyan', 'ellos' => 'construyan',
        ],
    ],
];

// destruir (like construir)
$verbs['destruir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'destruyo', 'tú' => 'destruyes', 'vos' => 'destruís',
            'él' => 'destruye', 'nosotros' => 'destruimos', 'ustedes' => 'destruyen', 'ellos' => 'destruyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'destruí', 'tú' => 'destruiste', 'vos' => 'destruiste',
            'él' => 'destruyó', 'nosotros' => 'destruimos', 'ustedes' => 'destruyeron', 'ellos' => 'destruyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'destruía', 'tú' => 'destruías', 'vos' => 'destruías',
            'él' => 'destruía', 'nosotros' => 'destruíamos', 'ustedes' => 'destruían', 'ellos' => 'destruían',
        ],
        'futuro' => [
            'yo' => 'destruiré', 'tú' => 'destruirás', 'vos' => 'destruirás',
            'él' => 'destruirá', 'nosotros' => 'destruiremos', 'ustedes' => 'destruirán', 'ellos' => 'destruirán',
        ],
        'condicional' => [
            'yo' => 'destruiría', 'tú' => 'destruirías', 'vos' => 'destruirías',
            'él' => 'destruiría', 'nosotros' => 'destruiríamos', 'ustedes' => 'destruirían', 'ellos' => 'destruirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'destruya', 'tú' => 'destruyas', 'vos' => 'destruyas',
            'él' => 'destruya', 'nosotros' => 'destruyamos', 'ustedes' => 'destruyan', 'ellos' => 'destruyan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'destruyera', 'tú' => 'destruyeras', 'vos' => 'destruyeras',
            'él' => 'destruyera', 'nosotros' => 'destruyéramos', 'ustedes' => 'destruyeran', 'ellos' => 'destruyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'destruye', 'vos' => 'destruí', 'él' => 'destruya',
            'nosotros' => 'destruyamos', 'ustedes' => 'destruyan', 'ellos' => 'destruyan',
        ],
    ],
];

// llenar, vaciar, guardar: regular -AR
foreach (['llenar','vaciar','guardar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// despertar: e→ie stem change -AR
$data = conjugateAR('despert', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['despertar'] = $data;

// proteger: g→j before a/o -ER
$data = conjugateER('proteg');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'protejo';
$data['subjuntivo']['presente'] = [
    'yo' => 'proteja', 'tú' => 'protejas', 'vos' => 'protejas',
    'él' => 'proteja', 'nosotros' => 'protejamos', 'ustedes' => 'protejan', 'ellos' => 'protejan',
];
$data['imperativo']['afirmativo']['él'] = 'proteja';
$data['imperativo']['afirmativo']['nosotros'] = 'protejamos';
$data['imperativo']['afirmativo']['ustedes'] = 'protejan';
$data['imperativo']['afirmativo']['ellos'] = 'protejan';
$verbs['proteger'] = $data;

// === Additional irregular verbs from extended list ===

// haber (auxiliary)
$verbs['haber'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'he', 'tú' => 'has', 'vos' => 'has',
            'él' => 'ha', 'nosotros' => 'hemos', 'ustedes' => 'han', 'ellos' => 'han',
        ],
        'pretérito indefinido' => [
            'yo' => 'hube', 'tú' => 'hubiste', 'vos' => 'hubiste',
            'él' => 'hubo', 'nosotros' => 'hubimos', 'ustedes' => 'hubieron', 'ellos' => 'hubieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'había', 'tú' => 'habías', 'vos' => 'habías',
            'él' => 'había', 'nosotros' => 'habíamos', 'ustedes' => 'habían', 'ellos' => 'habían',
        ],
        'futuro' => [
            'yo' => 'habré', 'tú' => 'habrás', 'vos' => 'habrás',
            'él' => 'habrá', 'nosotros' => 'habremos', 'ustedes' => 'habrán', 'ellos' => 'habrán',
        ],
        'condicional' => [
            'yo' => 'habría', 'tú' => 'habrías', 'vos' => 'habrías',
            'él' => 'habría', 'nosotros' => 'habríamos', 'ustedes' => 'habrían', 'ellos' => 'habrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'haya', 'tú' => 'hayas', 'vos' => 'hayas',
            'él' => 'haya', 'nosotros' => 'hayamos', 'ustedes' => 'hayan', 'ellos' => 'hayan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'hubiera', 'tú' => 'hubieras', 'vos' => 'hubieras',
            'él' => 'hubiera', 'nosotros' => 'hubiéramos', 'ustedes' => 'hubieran', 'ellos' => 'hubieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'he', 'vos' => 'he', 'él' => 'haya',
            'nosotros' => 'hayamos', 'ustedes' => 'hayan', 'ellos' => 'hayan',
        ],
    ],
];

// deber (regular -er)
$data = conjugateER('deb');
$data['is_irregular'] = false;
$verbs['deber'] = $data;

// poner (irregular)
$verbs['poner'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'pongo', 'tú' => 'pones', 'vos' => 'ponés',
            'él' => 'pone', 'nosotros' => 'ponemos', 'ustedes' => 'ponen', 'ellos' => 'ponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'puse', 'tú' => 'pusiste', 'vos' => 'pusiste',
            'él' => 'puso', 'nosotros' => 'pusimos', 'ustedes' => 'pusieron', 'ellos' => 'pusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'ponía', 'tú' => 'ponías', 'vos' => 'ponías',
            'él' => 'ponía', 'nosotros' => 'poníamos', 'ustedes' => 'ponían', 'ellos' => 'ponían',
        ],
        'futuro' => [
            'yo' => 'pondré', 'tú' => 'pondrás', 'vos' => 'pondrás',
            'él' => 'pondrá', 'nosotros' => 'pondremos', 'ustedes' => 'pondrán', 'ellos' => 'pondrán',
        ],
        'condicional' => [
            'yo' => 'pondría', 'tú' => 'pondrías', 'vos' => 'pondrías',
            'él' => 'pondría', 'nosotros' => 'pondríamos', 'ustedes' => 'pondrían', 'ellos' => 'pondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'ponga', 'tú' => 'pongas', 'vos' => 'pongas',
            'él' => 'ponga', 'nosotros' => 'pongamos', 'ustedes' => 'pongan', 'ellos' => 'pongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'pusiera', 'tú' => 'pusieras', 'vos' => 'pusieras',
            'él' => 'pusiera', 'nosotros' => 'pusiéramos', 'ustedes' => 'pusieran', 'ellos' => 'pusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'pon', 'vos' => 'poné', 'él' => 'ponga',
            'nosotros' => 'pongamos', 'ustedes' => 'pongan', 'ellos' => 'pongan',
        ],
    ],
];

// parecer (-zco verb)
$data = conjugateER('parec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'parezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'parezca', 'tú' => 'parezcas', 'vos' => 'parezcas',
    'él' => 'parezca', 'nosotros' => 'parezcamos', 'ustedes' => 'parezcan', 'ellos' => 'parezcan',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'parece', 'vos' => 'parecé', 'él' => 'parezca',
    'nosotros' => 'parezcamos', 'ustedes' => 'parezcan', 'ellos' => 'parezcan',
];
$verbs['parecer'] = $data;

// === Batch 3 verbs ===

// Regular -AR batch 3
$batch3AR = ['cocinar','lavar','limpiar','ordenar','apagar','conectar',
             'desconectar','revisar','memorizar','cruzar','pintar',
             'dibujar','cortar','pegar','mezclar','preguntar',
             'contestar','narrar','discutir','aclarar','saludar',
             'invitar','avisar','confirmar','cancelar','enseñar',
             'presentar'];
foreach ($batch3AR as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -ER batch 3
foreach (['beber','responder','coser','prometer'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateER($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -IR batch 3
foreach (['describir','practicar'] as $v) {
    // practicar is -AR, handle separately
}
$data = conjugateIR('describ');
$data['is_irregular'] = false;
$verbs['describir'] = $data;

// practicar: c→qu before e
$data = conjugateAR('practic', ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['practicar'] = $data;

// alcanzar: z→c before e
$data = conjugateAR('alcanz', ['spelling' => ['z', 'c']]);
$data['is_irregular'] = false;
$verbs['alcanzar'] = $data;

// encender: e→ie -ER
$data = conjugateER('encend', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['encender'] = $data;

// probar: o→ue -AR
$data = conjugateAR('prob', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['probar'] = $data;

// repetir: e→i -IR
$data = conjugateIR('repet', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
// Fix: present tense for repetir
$data['indicativo']['presente'] = [
    'yo' => 'repito', 'tú' => 'repites', 'vos' => 'repetís',
    'él' => 'repite', 'nosotros' => 'repetimos', 'ustedes' => 'repiten', 'ellos' => 'repiten',
];
$verbs['repetir'] = $data;

// servir: e→i -IR (like repetir)
$data = conjugateIR('serv', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'sirvo', 'tú' => 'sirves', 'vos' => 'servís',
    'él' => 'sirve', 'nosotros' => 'servimos', 'ustedes' => 'sirven', 'ellos' => 'sirven',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'sirva', 'tú' => 'sirvas', 'vos' => 'sirvas',
    'él' => 'sirva', 'nosotros' => 'sirvamos', 'ustedes' => 'sirvan', 'ellos' => 'sirvan',
];
$verbs['servir'] = $data;

// calentar: e→ie -AR
$data = conjugateAR('calent', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['calentar'] = $data;

// perseguir: like seguir (e→i + gu→g)
$verbs['perseguir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'persigo', 'tú' => 'persigues', 'vos' => 'perseguís',
            'él' => 'persigue', 'nosotros' => 'perseguimos', 'ustedes' => 'persiguen', 'ellos' => 'persiguen',
        ],
        'pretérito indefinido' => [
            'yo' => 'perseguí', 'tú' => 'perseguiste', 'vos' => 'perseguiste',
            'él' => 'persiguió', 'nosotros' => 'perseguimos', 'ustedes' => 'persiguieron', 'ellos' => 'persiguieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'perseguía', 'tú' => 'perseguías', 'vos' => 'perseguías',
            'él' => 'perseguía', 'nosotros' => 'perseguíamos', 'ustedes' => 'perseguían', 'ellos' => 'perseguían',
        ],
        'futuro' => [
            'yo' => 'perseguiré', 'tú' => 'perseguirás', 'vos' => 'perseguirás',
            'él' => 'perseguirá', 'nosotros' => 'perseguiremos', 'ustedes' => 'perseguirán', 'ellos' => 'perseguirán',
        ],
        'condicional' => [
            'yo' => 'perseguiría', 'tú' => 'perseguirías', 'vos' => 'perseguirías',
            'él' => 'perseguiría', 'nosotros' => 'perseguiríamos', 'ustedes' => 'perseguirían', 'ellos' => 'perseguirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'persiga', 'tú' => 'persigas', 'vos' => 'persigas',
            'él' => 'persiga', 'nosotros' => 'persigamos', 'ustedes' => 'persigan', 'ellos' => 'persigan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'persiguiera', 'tú' => 'persiguieras', 'vos' => 'persiguieras',
            'él' => 'persiguiera', 'nosotros' => 'persiguiéramos', 'ustedes' => 'persiguieran', 'ellos' => 'persiguieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'persigue', 'vos' => 'perseguí', 'él' => 'persiga',
            'nosotros' => 'persigamos', 'ustedes' => 'persigan', 'ellos' => 'persigan',
        ],
    ],
];

// medir: e→i -IR
$data = conjugateIR('med', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'mido', 'tú' => 'mides', 'vos' => 'medís',
    'él' => 'mide', 'nosotros' => 'medimos', 'ustedes' => 'miden', 'ellos' => 'miden',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'mida', 'tú' => 'midas', 'vos' => 'midas',
    'él' => 'mida', 'nosotros' => 'midamos', 'ustedes' => 'midan', 'ellos' => 'midan',
];
$verbs['medir'] = $data;

// despedirse: e→i -IR (conjugate base despedir)
$data = conjugateIR('desped', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'despido', 'tú' => 'despides', 'vos' => 'despedís',
    'él' => 'despide', 'nosotros' => 'despedimos', 'ustedes' => 'despiden', 'ellos' => 'despiden',
];
$verbs['despedirse'] = $data;

// rechazar: z→c before e
$data = conjugateAR('rechaz', ['spelling' => ['z', 'c']]);
$data['is_irregular'] = false;
$verbs['rechazar'] = $data;

// advertir: e→ie present, e→i preterite -IR
$data = conjugateIR('advert', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['advertir'] = $data;

// corregir: e→i + g→j before a/o
$verbs['corregir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'corrijo', 'tú' => 'corriges', 'vos' => 'corregís',
            'él' => 'corrige', 'nosotros' => 'corregimos', 'ustedes' => 'corrigen', 'ellos' => 'corrigen',
        ],
        'pretérito indefinido' => [
            'yo' => 'corregí', 'tú' => 'corregiste', 'vos' => 'corregiste',
            'él' => 'corrigió', 'nosotros' => 'corregimos', 'ustedes' => 'corrigieron', 'ellos' => 'corrigieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'corregía', 'tú' => 'corregías', 'vos' => 'corregías',
            'él' => 'corregía', 'nosotros' => 'corregíamos', 'ustedes' => 'corregían', 'ellos' => 'corregían',
        ],
        'futuro' => [
            'yo' => 'corregiré', 'tú' => 'corregirás', 'vos' => 'corregirás',
            'él' => 'corregirá', 'nosotros' => 'corregiremos', 'ustedes' => 'corregirán', 'ellos' => 'corregirán',
        ],
        'condicional' => [
            'yo' => 'corregiría', 'tú' => 'corregirías', 'vos' => 'corregirías',
            'él' => 'corregiría', 'nosotros' => 'corregiríamos', 'ustedes' => 'corregirían', 'ellos' => 'corregirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'corrija', 'tú' => 'corrijas', 'vos' => 'corrijas',
            'él' => 'corrija', 'nosotros' => 'corrijamos', 'ustedes' => 'corrijan', 'ellos' => 'corrijan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'corrigiera', 'tú' => 'corrigieras', 'vos' => 'corrigieras',
            'él' => 'corrigiera', 'nosotros' => 'corrigiéramos', 'ustedes' => 'corrigieran', 'ellos' => 'corrigieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'corrige', 'vos' => 'corregí', 'él' => 'corrija',
            'nosotros' => 'corrijamos', 'ustedes' => 'corrijan', 'ellos' => 'corrijan',
        ],
    ],
];

// evaluar: regular -AR (u gets accent in some forms: evalúo)
$data = conjugateAR('evalu');
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'evalúo', 'tú' => 'evalúas', 'vos' => 'evaluás',
    'él' => 'evalúa', 'nosotros' => 'evaluamos', 'ustedes' => 'evalúan', 'ellos' => 'evalúan',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'evalúe', 'tú' => 'evalúes', 'vos' => 'evalúes',
    'él' => 'evalúe', 'nosotros' => 'evaluemos', 'ustedes' => 'evalúen', 'ellos' => 'evalúen',
];
$data['imperativo']['afirmativo']['tú'] = 'evalúa';
$verbs['evaluar'] = $data;

// aprobar: o→ue -AR
$data = conjugateAR('aprob', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['aprobar'] = $data;

// reprobar: o→ue -AR
$data = conjugateAR('reprob', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['reprobar'] = $data;

// exponer: like poner
$verbs['exponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'expongo', 'tú' => 'expones', 'vos' => 'exponés',
            'él' => 'expone', 'nosotros' => 'exponemos', 'ustedes' => 'exponen', 'ellos' => 'exponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'expuse', 'tú' => 'expusiste', 'vos' => 'expusiste',
            'él' => 'expuso', 'nosotros' => 'expusimos', 'ustedes' => 'expusieron', 'ellos' => 'expusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'exponía', 'tú' => 'exponías', 'vos' => 'exponías',
            'él' => 'exponía', 'nosotros' => 'exponíamos', 'ustedes' => 'exponían', 'ellos' => 'exponían',
        ],
        'futuro' => [
            'yo' => 'expondré', 'tú' => 'expondrás', 'vos' => 'expondrás',
            'él' => 'expondrá', 'nosotros' => 'expondremos', 'ustedes' => 'expondrán', 'ellos' => 'expondrán',
        ],
        'condicional' => [
            'yo' => 'expondría', 'tú' => 'expondrías', 'vos' => 'expondrías',
            'él' => 'expondría', 'nosotros' => 'expondríamos', 'ustedes' => 'expondrían', 'ellos' => 'expondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'exponga', 'tú' => 'expongas', 'vos' => 'expongas',
            'él' => 'exponga', 'nosotros' => 'expongamos', 'ustedes' => 'expongan', 'ellos' => 'expongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'expusiera', 'tú' => 'expusieras', 'vos' => 'expusieras',
            'él' => 'expusiera', 'nosotros' => 'expusiéramos', 'ustedes' => 'expusieran', 'ellos' => 'expusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'expón', 'vos' => 'exponé', 'él' => 'exponga',
            'nosotros' => 'expongamos', 'ustedes' => 'expongan', 'ellos' => 'expongan',
        ],
    ],
];

// Reflexive bases (acercarse → acercar, alejarse → alejar)
foreach (['acercar','alejar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v . 'se'] = $data;
}

// === Batch 4 verbs ===

// Regular -AR batch 4
$batch4AR = ['dudar','notar','emocionarse','preocuparse','amar','odiar',
             'molestar','importar','afectar','sospechar','explorar',
             'conservar','abandonar','recuperar','hallar','continuar'];
foreach ($batch4AR as $v) {
    $stem = mb_substr(str_replace('se', '', $v), 0, -2);
    // Handle reflexive: strip -se to get base infinitive for conjugation
    $base = preg_replace('/se$/', '', $v);
    $stem = mb_substr($base, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// gustar, encantar: regular -AR (used as defective but conjugated normally)
foreach (['gustar','encantar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// confiar: regular -AR but with accent on í (confío)
$data = conjugateAR('confi');
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'confío', 'tú' => 'confías', 'vos' => 'confiás',
    'él' => 'confía', 'nosotros' => 'confiamos', 'ustedes' => 'confían', 'ellos' => 'confían',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'confíe', 'tú' => 'confíes', 'vos' => 'confíes',
    'él' => 'confíe', 'nosotros' => 'confiemos', 'ustedes' => 'confíen', 'ellos' => 'confíen',
];
$data['imperativo']['afirmativo']['tú'] = 'confía';
$verbs['confiar'] = $data;

// arriesgar: g→gu before e
$data = conjugateAR('arriesg', ['spelling' => ['g', 'gu']]);
$data['is_irregular'] = false;
$verbs['arriesgar'] = $data;

// rendirse: e→i -IR (like pedir)
$verbs['rendirse'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'rindo', 'tú' => 'rindes', 'vos' => 'rendís',
            'él' => 'rinde', 'nosotros' => 'rendimos', 'ustedes' => 'rinden', 'ellos' => 'rinden',
        ],
        'pretérito indefinido' => [
            'yo' => 'rendí', 'tú' => 'rendiste', 'vos' => 'rendiste',
            'él' => 'rindió', 'nosotros' => 'rendimos', 'ustedes' => 'rindieron', 'ellos' => 'rindieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'rendía', 'tú' => 'rendías', 'vos' => 'rendías',
            'él' => 'rendía', 'nosotros' => 'rendíamos', 'ustedes' => 'rendían', 'ellos' => 'rendían',
        ],
        'futuro' => [
            'yo' => 'rendiré', 'tú' => 'rendirás', 'vos' => 'rendirás',
            'él' => 'rendirá', 'nosotros' => 'rendiremos', 'ustedes' => 'rendirán', 'ellos' => 'rendirán',
        ],
        'condicional' => [
            'yo' => 'rendiría', 'tú' => 'rendirías', 'vos' => 'rendirías',
            'él' => 'rendiría', 'nosotros' => 'rendiríamos', 'ustedes' => 'rendirían', 'ellos' => 'rendirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'rinda', 'tú' => 'rindas', 'vos' => 'rindas',
            'él' => 'rinda', 'nosotros' => 'rindamos', 'ustedes' => 'rindan', 'ellos' => 'rindan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'rindiera', 'tú' => 'rindieras', 'vos' => 'rindieras',
            'él' => 'rindiera', 'nosotros' => 'rindiéramos', 'ustedes' => 'rindieran', 'ellos' => 'rindieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'rinde', 'vos' => 'rendí', 'él' => 'rinda',
            'nosotros' => 'rindamos', 'ustedes' => 'rindan', 'ellos' => 'rindan',
        ],
    ],
];

// nacer: c→zc before a/o (like conocer)
$data = conjugateER('nac');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'nazco';
$data['subjuntivo']['presente'] = [
    'yo' => 'nazca', 'tú' => 'nazcas', 'vos' => 'nazcas',
    'él' => 'nazca', 'nosotros' => 'nazcamos', 'ustedes' => 'nazcan', 'ellos' => 'nazcan',
];
$data['imperativo']['afirmativo']['él'] = 'nazca';
$data['imperativo']['afirmativo']['nosotros'] = 'nazcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'nazcan';
$data['imperativo']['afirmativo']['ellos'] = 'nazcan';
$verbs['nacer'] = $data;

// crecer: c→zc (like nacer)
$data = conjugateER('crec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'crezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'crezca', 'tú' => 'crezcas', 'vos' => 'crezcas',
    'él' => 'crezca', 'nosotros' => 'crezcamos', 'ustedes' => 'crezcan', 'ellos' => 'crezcan',
];
$data['imperativo']['afirmativo']['él'] = 'crezca';
$data['imperativo']['afirmativo']['nosotros'] = 'crezcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'crezcan';
$data['imperativo']['afirmativo']['ellos'] = 'crezcan';
$verbs['crecer'] = $data;

// morir: o→ue present, o→u preterite -IR (like dormir)
$verbs['morir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'muero', 'tú' => 'mueres', 'vos' => 'morís',
            'él' => 'muere', 'nosotros' => 'morimos', 'ustedes' => 'mueren', 'ellos' => 'mueren',
        ],
        'pretérito indefinido' => [
            'yo' => 'morí', 'tú' => 'moriste', 'vos' => 'moriste',
            'él' => 'murió', 'nosotros' => 'morimos', 'ustedes' => 'murieron', 'ellos' => 'murieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'moría', 'tú' => 'morías', 'vos' => 'morías',
            'él' => 'moría', 'nosotros' => 'moríamos', 'ustedes' => 'morían', 'ellos' => 'morían',
        ],
        'futuro' => [
            'yo' => 'moriré', 'tú' => 'morirás', 'vos' => 'morirás',
            'él' => 'morirá', 'nosotros' => 'moriremos', 'ustedes' => 'morirán', 'ellos' => 'morirán',
        ],
        'condicional' => [
            'yo' => 'moriría', 'tú' => 'morirías', 'vos' => 'morirías',
            'él' => 'moriría', 'nosotros' => 'moriríamos', 'ustedes' => 'morirían', 'ellos' => 'morirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'muera', 'tú' => 'mueras', 'vos' => 'mueras',
            'él' => 'muera', 'nosotros' => 'muramos', 'ustedes' => 'mueran', 'ellos' => 'mueran',
        ],
        'pretérito imperfecto' => [
            'yo' => 'muriera', 'tú' => 'murieras', 'vos' => 'murieras',
            'él' => 'muriera', 'nosotros' => 'muriéramos', 'ustedes' => 'murieran', 'ellos' => 'murieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'muere', 'vos' => 'morí', 'él' => 'muera',
            'nosotros' => 'muramos', 'ustedes' => 'mueran', 'ellos' => 'mueran',
        ],
    ],
];

// detener: like tener
$verbs['detener'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'detengo', 'tú' => 'detienes', 'vos' => 'detenés',
            'él' => 'detiene', 'nosotros' => 'detenemos', 'ustedes' => 'detienen', 'ellos' => 'detienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'detuve', 'tú' => 'detuviste', 'vos' => 'detuviste',
            'él' => 'detuvo', 'nosotros' => 'detuvimos', 'ustedes' => 'detuvieron', 'ellos' => 'detuvieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'detenía', 'tú' => 'detenías', 'vos' => 'detenías',
            'él' => 'detenía', 'nosotros' => 'deteníamos', 'ustedes' => 'detenían', 'ellos' => 'detenían',
        ],
        'futuro' => [
            'yo' => 'detendré', 'tú' => 'detendrás', 'vos' => 'detendrás',
            'él' => 'detendrá', 'nosotros' => 'detendremos', 'ustedes' => 'detendrán', 'ellos' => 'detendrán',
        ],
        'condicional' => [
            'yo' => 'detendría', 'tú' => 'detendrías', 'vos' => 'detendrías',
            'él' => 'detendría', 'nosotros' => 'detendríamos', 'ustedes' => 'detendrían', 'ellos' => 'detendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'detenga', 'tú' => 'detengas', 'vos' => 'detengas',
            'él' => 'detenga', 'nosotros' => 'detengamos', 'ustedes' => 'detengan', 'ellos' => 'detengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'detuviera', 'tú' => 'detuvieras', 'vos' => 'detuvieras',
            'él' => 'detuviera', 'nosotros' => 'detuviéramos', 'ustedes' => 'detuvieran', 'ellos' => 'detuvieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'detén', 'vos' => 'detené', 'él' => 'detenga',
            'nosotros' => 'detengamos', 'ustedes' => 'detengan', 'ellos' => 'detengan',
        ],
    ],
];

// partir: regular -IR
$data = conjugateIR('part');
$data['is_irregular'] = false;
$verbs['partir'] = $data;

// mudarse: regular -AR
$data = conjugateAR('mud');
$data['is_irregular'] = false;
$verbs['mudarse'] = $data;

// descubrir: regular -IR
$data = conjugateIR('descubr');
$data['is_irregular'] = false;
$verbs['descubrir'] = $data;

// conocer: c→zc before a/o
$data = conjugateER('conoc');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'conozco';
$data['subjuntivo']['presente'] = [
    'yo' => 'conozca', 'tú' => 'conozcas', 'vos' => 'conozcas',
    'él' => 'conozca', 'nosotros' => 'conozcamos', 'ustedes' => 'conozcan', 'ellos' => 'conozcan',
];
$data['imperativo']['afirmativo']['él'] = 'conozca';
$data['imperativo']['afirmativo']['nosotros'] = 'conozcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'conozcan';
$data['imperativo']['afirmativo']['ellos'] = 'conozcan';
$verbs['conocer'] = $data;

// reconocer: like conocer
$data = conjugateER('reconoc');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'reconozco';
$data['subjuntivo']['presente'] = [
    'yo' => 'reconozca', 'tú' => 'reconozcas', 'vos' => 'reconozcas',
    'él' => 'reconozca', 'nosotros' => 'reconozcamos', 'ustedes' => 'reconozcan', 'ellos' => 'reconozcan',
];
$data['imperativo']['afirmativo']['él'] = 'reconozca';
$data['imperativo']['afirmativo']['nosotros'] = 'reconozcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'reconozcan';
$data['imperativo']['afirmativo']['ellos'] = 'reconozcan';
$verbs['reconocer'] = $data;

// === Batch 5 verbs ===

// Regular -AR batch 5
$batch5AR = ['fabricar','desarrollar','diseñar','planificar','coordinar',
             'administrar','gestionar','supervisar','analizar','comparar',
             'calcular','estimar','ahorrar','gastar','cobrar','negociar',
             'contratar','editar','actualizar','archivar','registrar',
             'documentar','informar','reportar','observar','detectar',
             'agarrar','sujetar','golpear','rozar','presionar','separar',
             'relacionar','cumplir','acusar','justificar','argumentar',
             'cooperar','involucrar','resistir','transformar','modificar',
             'alterar','adaptar','ajustar','optimizar','deteriorar',
             'causar','provocar','generar','impactar','dominar','manejar',
             'limitar','restringir'];
foreach ($batch5AR as $v) {
    // Some of these are -IR, handle below
    if (in_array($v, ['cumplir','resistir','restringir','expandir'])) continue;
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -ER batch 5
foreach (['ceder','convencer'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateER($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -IR batch 5
foreach (['cumplir','resistir','expandir'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateIR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// restringir: g→j before a/o
$data = conjugateIR('restrinj');
// Actually the stem is restring-, and only yo present + subjuntive change
$data = conjugateIR('restring');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'restrinjo';
$data['subjuntivo']['presente'] = [
    'yo' => 'restrinja', 'tú' => 'restrinjas', 'vos' => 'restrinjas',
    'él' => 'restrinja', 'nosotros' => 'restrinjamos', 'ustedes' => 'restrinjan', 'ellos' => 'restrinjan',
];
$verbs['restringir'] = $data;

// producir: like reducir (-zco + j preterite)
$verbs['producir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'produzco', 'tú' => 'produces', 'vos' => 'producís',
            'él' => 'produce', 'nosotros' => 'producimos', 'ustedes' => 'producen', 'ellos' => 'producen',
        ],
        'pretérito indefinido' => [
            'yo' => 'produje', 'tú' => 'produjiste', 'vos' => 'produjiste',
            'él' => 'produjo', 'nosotros' => 'produjimos', 'ustedes' => 'produjeron', 'ellos' => 'produjeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'producía', 'tú' => 'producías', 'vos' => 'producías',
            'él' => 'producía', 'nosotros' => 'producíamos', 'ustedes' => 'producían', 'ellos' => 'producían',
        ],
        'futuro' => [
            'yo' => 'produciré', 'tú' => 'producirás', 'vos' => 'producirás',
            'él' => 'producirá', 'nosotros' => 'produciremos', 'ustedes' => 'producirán', 'ellos' => 'producirán',
        ],
        'condicional' => [
            'yo' => 'produciría', 'tú' => 'producirías', 'vos' => 'producirías',
            'él' => 'produciría', 'nosotros' => 'produciríamos', 'ustedes' => 'producirían', 'ellos' => 'producirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'produzca', 'tú' => 'produzcas', 'vos' => 'produzcas',
            'él' => 'produzca', 'nosotros' => 'produzcamos', 'ustedes' => 'produzcan', 'ellos' => 'produzcan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'produjera', 'tú' => 'produjeras', 'vos' => 'produjeras',
            'él' => 'produjera', 'nosotros' => 'produjéramos', 'ustedes' => 'produjeran', 'ellos' => 'produjeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'produce', 'vos' => 'producí', 'él' => 'produzca',
            'nosotros' => 'produzcamos', 'ustedes' => 'produzcan', 'ellos' => 'produzcan',
        ],
    ],
];

// dirigir: g→j before a/o
$data = conjugateIR('dirig');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'dirijo';
$data['subjuntivo']['presente'] = [
    'yo' => 'dirija', 'tú' => 'dirijas', 'vos' => 'dirijas',
    'él' => 'dirija', 'nosotros' => 'dirijamos', 'ustedes' => 'dirijan', 'ellos' => 'dirijan',
];
$data['imperativo']['afirmativo']['él'] = 'dirija';
$data['imperativo']['afirmativo']['nosotros'] = 'dirijamos';
$data['imperativo']['afirmativo']['ustedes'] = 'dirijan';
$data['imperativo']['afirmativo']['ellos'] = 'dirijan';
$verbs['dirigir'] = $data;

// invertir: e→ie present, e→i preterite -IR
$data = conjugateIR('invert', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['invertir'] = $data;

// despedir: e→i -IR (like pedir)
$data = conjugateIR('desped', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'despido', 'tú' => 'despides', 'vos' => 'despedís',
    'él' => 'despide', 'nosotros' => 'despedimos', 'ustedes' => 'despiden', 'ellos' => 'despiden',
];
$verbs['despedir'] = $data;

// publicar: c→qu before e
$data = conjugateAR('public', ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['publicar'] = $data;

// oír: highly irregular
$verbs['oír'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'oigo', 'tú' => 'oyes', 'vos' => 'oís',
            'él' => 'oye', 'nosotros' => 'oímos', 'ustedes' => 'oyen', 'ellos' => 'oyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'oí', 'tú' => 'oíste', 'vos' => 'oíste',
            'él' => 'oyó', 'nosotros' => 'oímos', 'ustedes' => 'oyeron', 'ellos' => 'oyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'oía', 'tú' => 'oías', 'vos' => 'oías',
            'él' => 'oía', 'nosotros' => 'oíamos', 'ustedes' => 'oían', 'ellos' => 'oían',
        ],
        'futuro' => [
            'yo' => 'oiré', 'tú' => 'oirás', 'vos' => 'oirás',
            'él' => 'oirá', 'nosotros' => 'oiremos', 'ustedes' => 'oirán', 'ellos' => 'oirán',
        ],
        'condicional' => [
            'yo' => 'oiría', 'tú' => 'oirías', 'vos' => 'oirías',
            'él' => 'oiría', 'nosotros' => 'oiríamos', 'ustedes' => 'oirían', 'ellos' => 'oirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'oiga', 'tú' => 'oigas', 'vos' => 'oigas',
            'él' => 'oiga', 'nosotros' => 'oigamos', 'ustedes' => 'oigan', 'ellos' => 'oigan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'oyera', 'tú' => 'oyeras', 'vos' => 'oyeras',
            'él' => 'oyera', 'nosotros' => 'oyéramos', 'ustedes' => 'oyeran', 'ellos' => 'oyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'oye', 'vos' => 'oí', 'él' => 'oiga',
            'nosotros' => 'oigamos', 'ustedes' => 'oigan', 'ellos' => 'oigan',
        ],
    ],
];

// percibir: regular -IR
$data = conjugateIR('percib');
$data['is_irregular'] = false;
$verbs['percibir'] = $data;

// tocar: c→qu before e
$data = conjugateAR('toc', ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['tocar'] = $data;

// soltar: o→ue -AR
$data = conjugateAR('solt', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['soltar'] = $data;

// apretar: e→ie -AR
$data = conjugateAR('apret', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['apretar'] = $data;

// unir: regular -IR
$data = conjugateIR('un');
$data['is_irregular'] = false;
$verbs['unir'] = $data;

// dividir: regular -IR
$data = conjugateIR('divid');
$data['is_irregular'] = false;
$verbs['dividir'] = $data;

// obedecer: c→zc (like conocer)
$data = conjugateER('obedec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'obedezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'obedezca', 'tú' => 'obedezcas', 'vos' => 'obedezcas',
    'él' => 'obedezca', 'nosotros' => 'obedezcamos', 'ustedes' => 'obedezcan', 'ellos' => 'obedezcan',
];
$data['imperativo']['afirmativo']['él'] = 'obedezca';
$data['imperativo']['afirmativo']['nosotros'] = 'obedezcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'obedezcan';
$data['imperativo']['afirmativo']['ellos'] = 'obedezcan';
$verbs['obedecer'] = $data;

// violar: regular -AR
$data = conjugateAR('viol');
$data['is_irregular'] = false;
$verbs['violar'] = $data;

// exigir: g→j before a/o
$data = conjugateIR('exig');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'exijo';
$data['subjuntivo']['presente'] = [
    'yo' => 'exija', 'tú' => 'exijas', 'vos' => 'exijas',
    'él' => 'exija', 'nosotros' => 'exijamos', 'ustedes' => 'exijan', 'ellos' => 'exijan',
];
$verbs['exigir'] = $data;

// prohibir: accent on í in present (prohíbo)
$data = conjugateIR('prohib');
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'prohíbo', 'tú' => 'prohíbes', 'vos' => 'prohibís',
    'él' => 'prohíbe', 'nosotros' => 'prohibimos', 'ustedes' => 'prohíben', 'ellos' => 'prohíben',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'prohíba', 'tú' => 'prohíbas', 'vos' => 'prohíbas',
    'él' => 'prohíba', 'nosotros' => 'prohibamos', 'ustedes' => 'prohíban', 'ellos' => 'prohíban',
];
$verbs['prohibir'] = $data;

// defender: e→ie -ER
$data = conjugateER('defend', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['defender'] = $data;

// influir: -uir verb (like construir)
$verbs['influir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'influyo', 'tú' => 'influyes', 'vos' => 'influís',
            'él' => 'influye', 'nosotros' => 'influimos', 'ustedes' => 'influyen', 'ellos' => 'influyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'influí', 'tú' => 'influiste', 'vos' => 'influiste',
            'él' => 'influyó', 'nosotros' => 'influimos', 'ustedes' => 'influyeron', 'ellos' => 'influyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'influía', 'tú' => 'influías', 'vos' => 'influías',
            'él' => 'influía', 'nosotros' => 'influíamos', 'ustedes' => 'influían', 'ellos' => 'influían',
        ],
        'futuro' => [
            'yo' => 'influiré', 'tú' => 'influirás', 'vos' => 'influirás',
            'él' => 'influirá', 'nosotros' => 'influiremos', 'ustedes' => 'influirán', 'ellos' => 'influirán',
        ],
        'condicional' => [
            'yo' => 'influiría', 'tú' => 'influirías', 'vos' => 'influirías',
            'él' => 'influiría', 'nosotros' => 'influiríamos', 'ustedes' => 'influirían', 'ellos' => 'influirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'influya', 'tú' => 'influyas', 'vos' => 'influyas',
            'él' => 'influya', 'nosotros' => 'influyamos', 'ustedes' => 'influyan', 'ellos' => 'influyan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'influyera', 'tú' => 'influyeras', 'vos' => 'influyeras',
            'él' => 'influyera', 'nosotros' => 'influyéramos', 'ustedes' => 'influyeran', 'ellos' => 'influyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'influye', 'vos' => 'influí', 'él' => 'influya',
            'nosotros' => 'influyamos', 'ustedes' => 'influyan', 'ellos' => 'influyan',
        ],
    ],
];

// competir: e→i -IR (like repetir)
$data = conjugateIR('compet', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'compito', 'tú' => 'compites', 'vos' => 'competís',
    'él' => 'compite', 'nosotros' => 'competimos', 'ustedes' => 'compiten', 'ellos' => 'compiten',
];
$verbs['competir'] = $data;

// oponerse: like poner
$verbs['oponerse'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'opongo', 'tú' => 'opones', 'vos' => 'oponés',
            'él' => 'opone', 'nosotros' => 'oponemos', 'ustedes' => 'oponen', 'ellos' => 'oponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'opuse', 'tú' => 'opusiste', 'vos' => 'opusiste',
            'él' => 'opuso', 'nosotros' => 'opusimos', 'ustedes' => 'opusieron', 'ellos' => 'opusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'oponía', 'tú' => 'oponías', 'vos' => 'oponías',
            'él' => 'oponía', 'nosotros' => 'oponíamos', 'ustedes' => 'oponían', 'ellos' => 'oponían',
        ],
        'futuro' => [
            'yo' => 'opondré', 'tú' => 'opondrás', 'vos' => 'opondrás',
            'él' => 'opondrá', 'nosotros' => 'opondremos', 'ustedes' => 'opondrán', 'ellos' => 'opondrán',
        ],
        'condicional' => [
            'yo' => 'opondría', 'tú' => 'opondrías', 'vos' => 'opondrías',
            'él' => 'opondría', 'nosotros' => 'opondríamos', 'ustedes' => 'opondrían', 'ellos' => 'opondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'oponga', 'tú' => 'opongas', 'vos' => 'opongas',
            'él' => 'oponga', 'nosotros' => 'opongamos', 'ustedes' => 'opongan', 'ellos' => 'opongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'opusiera', 'tú' => 'opusieras', 'vos' => 'opusieras',
            'él' => 'opusiera', 'nosotros' => 'opusiéramos', 'ustedes' => 'opusieran', 'ellos' => 'opusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'opón', 'vos' => 'oponé', 'él' => 'oponga',
            'nosotros' => 'opongamos', 'ustedes' => 'opongan', 'ellos' => 'opongan',
        ],
    ],
];

// conseguir: like seguir
$verbs['conseguir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'consigo', 'tú' => 'consigues', 'vos' => 'conseguís',
            'él' => 'consigue', 'nosotros' => 'conseguimos', 'ustedes' => 'consiguen', 'ellos' => 'consiguen',
        ],
        'pretérito indefinido' => [
            'yo' => 'conseguí', 'tú' => 'conseguiste', 'vos' => 'conseguiste',
            'él' => 'consiguió', 'nosotros' => 'conseguimos', 'ustedes' => 'consiguieron', 'ellos' => 'consiguieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'conseguía', 'tú' => 'conseguías', 'vos' => 'conseguías',
            'él' => 'conseguía', 'nosotros' => 'conseguíamos', 'ustedes' => 'conseguían', 'ellos' => 'conseguían',
        ],
        'futuro' => [
            'yo' => 'conseguiré', 'tú' => 'conseguirás', 'vos' => 'conseguirás',
            'él' => 'conseguirá', 'nosotros' => 'conseguiremos', 'ustedes' => 'conseguirán', 'ellos' => 'conseguirán',
        ],
        'condicional' => [
            'yo' => 'conseguiría', 'tú' => 'conseguirías', 'vos' => 'conseguirías',
            'él' => 'conseguiría', 'nosotros' => 'conseguiríamos', 'ustedes' => 'conseguirían', 'ellos' => 'conseguirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'consiga', 'tú' => 'consigas', 'vos' => 'consigas',
            'él' => 'consiga', 'nosotros' => 'consigamos', 'ustedes' => 'consigan', 'ellos' => 'consigan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'consiguiera', 'tú' => 'consiguieras', 'vos' => 'consiguieras',
            'él' => 'consiguiera', 'nosotros' => 'consiguiéramos', 'ustedes' => 'consiguieran', 'ellos' => 'consiguieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'consigue', 'vos' => 'conseguí', 'él' => 'consiga',
            'nosotros' => 'consigamos', 'ustedes' => 'consigan', 'ellos' => 'consigan',
        ],
    ],
];

// regular: regular -AR (the verb "regular")
$data = conjugateAR('regul');
$data['is_irregular'] = false;
$verbs['regular'] = $data;

// persuadir: regular -IR
$data = conjugateIR('persuad');
$data['is_irregular'] = false;
$verbs['persuadir'] = $data;

// === Batch 6 verbs ===

// Regular -AR batch 6
$batch6AR = ['reflexionar','razonar','valorar','interpretar',
             'plantear','formular','cuestionar','criticar',
             'verificar','descartar','afirmar',
             'programar','codificar','configurar','instalar',
             'borrar','enviar','copiar','navegar',
             'iniciar','bloquear','desbloquear',
             'pactar','debatir','dialogar','mediar','arbitrar','conciliar',
             'atacar','amenazar','castigar','sancionar','premiar',
             'recompensar','compensar','multar',
             'evolucionar','desarrollarse','transformarse','adaptarse',
             'marcar','determinar','condicionar','orientar','guiar',
             'inspirar','motivar','aportar','heredar','preservar'];
foreach ($batch6AR as $v) {
    $base = preg_replace('/se$/', '', $v);
    $stem = mb_substr($base, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -ER batch 6
foreach (['comprender','someter','acceder'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateER($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -IR batch 6
foreach (['asumir','compartir','difundir','transmitir',
          'persistir','definir','imprimir','recibir'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateIR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// deducir: like reducir (-zco + j preterite)
$verbs['deducir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'deduzco', 'tú' => 'deduces', 'vos' => 'deducís',
            'él' => 'deduce', 'nosotros' => 'deducimos', 'ustedes' => 'deducen', 'ellos' => 'deducen',
        ],
        'pretérito indefinido' => [
            'yo' => 'deduje', 'tú' => 'dedujiste', 'vos' => 'dedujiste',
            'él' => 'dedujo', 'nosotros' => 'dedujimos', 'ustedes' => 'dedujeron', 'ellos' => 'dedujeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'deducía', 'tú' => 'deducías', 'vos' => 'deducías',
            'él' => 'deducía', 'nosotros' => 'deducíamos', 'ustedes' => 'deducían', 'ellos' => 'deducían',
        ],
        'futuro' => [
            'yo' => 'deduciré', 'tú' => 'deducirás', 'vos' => 'deducirás',
            'él' => 'deducirá', 'nosotros' => 'deduciremos', 'ustedes' => 'deducirán', 'ellos' => 'deducirán',
        ],
        'condicional' => [
            'yo' => 'deduciría', 'tú' => 'deducirías', 'vos' => 'deducirías',
            'él' => 'deduciría', 'nosotros' => 'deduciríamos', 'ustedes' => 'deducirían', 'ellos' => 'deducirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'deduzca', 'tú' => 'deduzcas', 'vos' => 'deduzcas',
            'él' => 'deduzca', 'nosotros' => 'deduzcamos', 'ustedes' => 'deduzcan', 'ellos' => 'deduzcan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'dedujera', 'tú' => 'dedujeras', 'vos' => 'dedujeras',
            'él' => 'dedujera', 'nosotros' => 'dedujéramos', 'ustedes' => 'dedujeran', 'ellos' => 'dedujeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'deduce', 'vos' => 'deducí', 'él' => 'deduzca',
            'nosotros' => 'deduzcamos', 'ustedes' => 'deduzcan', 'ellos' => 'deduzcan',
        ],
    ],
];

// inferir: e→ie present, e→i preterite -IR
$data = conjugateIR('infer', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['inferir'] = $data;

// concluir: -uir verb (like construir)
$verbs['concluir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'concluyo', 'tú' => 'concluyes', 'vos' => 'concluís',
            'él' => 'concluye', 'nosotros' => 'concluimos', 'ustedes' => 'concluyen', 'ellos' => 'concluyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'concluí', 'tú' => 'concluiste', 'vos' => 'concluiste',
            'él' => 'concluyó', 'nosotros' => 'concluimos', 'ustedes' => 'concluyeron', 'ellos' => 'concluyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'concluía', 'tú' => 'concluías', 'vos' => 'concluías',
            'él' => 'concluía', 'nosotros' => 'concluíamos', 'ustedes' => 'concluían', 'ellos' => 'concluían',
        ],
        'futuro' => [
            'yo' => 'concluiré', 'tú' => 'concluirás', 'vos' => 'concluirás',
            'él' => 'concluirá', 'nosotros' => 'concluiremos', 'ustedes' => 'concluirán', 'ellos' => 'concluirán',
        ],
        'condicional' => [
            'yo' => 'concluiría', 'tú' => 'concluirías', 'vos' => 'concluirías',
            'él' => 'concluiría', 'nosotros' => 'concluiríamos', 'ustedes' => 'concluirían', 'ellos' => 'concluirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'concluya', 'tú' => 'concluyas', 'vos' => 'concluyas',
            'él' => 'concluya', 'nosotros' => 'concluyamos', 'ustedes' => 'concluyan', 'ellos' => 'concluyan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'concluyera', 'tú' => 'concluyeras', 'vos' => 'concluyeras',
            'él' => 'concluyera', 'nosotros' => 'concluyéramos', 'ustedes' => 'concluyeran', 'ellos' => 'concluyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'concluye', 'vos' => 'concluí', 'él' => 'concluya',
            'nosotros' => 'concluyamos', 'ustedes' => 'concluyan', 'ellos' => 'concluyan',
        ],
    ],
];

// proponer: like poner
$verbs['proponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'propongo', 'tú' => 'propones', 'vos' => 'proponés',
            'él' => 'propone', 'nosotros' => 'proponemos', 'ustedes' => 'proponen', 'ellos' => 'proponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'propuse', 'tú' => 'propusiste', 'vos' => 'propusiste',
            'él' => 'propuso', 'nosotros' => 'propusimos', 'ustedes' => 'propusieron', 'ellos' => 'propusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'proponía', 'tú' => 'proponías', 'vos' => 'proponías',
            'él' => 'proponía', 'nosotros' => 'proponíamos', 'ustedes' => 'proponían', 'ellos' => 'proponían',
        ],
        'futuro' => [
            'yo' => 'propondré', 'tú' => 'propondrás', 'vos' => 'propondrás',
            'él' => 'propondrá', 'nosotros' => 'propondremos', 'ustedes' => 'propondrán', 'ellos' => 'propondrán',
        ],
        'condicional' => [
            'yo' => 'propondría', 'tú' => 'propondrías', 'vos' => 'propondrías',
            'él' => 'propondría', 'nosotros' => 'propondríamos', 'ustedes' => 'propondrían', 'ellos' => 'propondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'proponga', 'tú' => 'propongas', 'vos' => 'propongas',
            'él' => 'proponga', 'nosotros' => 'propongamos', 'ustedes' => 'propongan', 'ellos' => 'propongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'propusiera', 'tú' => 'propusieras', 'vos' => 'propusieras',
            'él' => 'propusiera', 'nosotros' => 'propusiéramos', 'ustedes' => 'propusieran', 'ellos' => 'propusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'propón', 'vos' => 'proponé', 'él' => 'proponga',
            'nosotros' => 'propongamos', 'ustedes' => 'propongan', 'ellos' => 'propongan',
        ],
    ],
];

// sugerir: e→ie present, e→i preterite -IR
$data = conjugateIR('suger', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['sugerir'] = $data;

// comprobar: o→ue -AR
$data = conjugateAR('comprob', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['comprobar'] = $data;

// negar: e→ie + g→gu before e
$data = conjugateAR('neg', ['stem_change' => ['e', 'ie'], 'spelling' => ['g', 'gu']]);
$data['is_irregular'] = true;
$verbs['negar'] = $data;

// descargar: g→gu before e
$data = conjugateAR('descarg', ['spelling' => ['g', 'gu']]);
$data['is_irregular'] = false;
$verbs['descargar'] = $data;

// eliminar: regular -AR
$data = conjugateAR('elimin');
$data['is_irregular'] = false;
$verbs['eliminar'] = $data;

// registrarse: regular -AR
$data = conjugateAR('registr');
$data['is_irregular'] = false;
$verbs['registrarse'] = $data;

// imponer: like poner
$verbs['imponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'impongo', 'tú' => 'impones', 'vos' => 'imponés',
            'él' => 'impone', 'nosotros' => 'imponemos', 'ustedes' => 'imponen', 'ellos' => 'imponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'impuse', 'tú' => 'impusiste', 'vos' => 'impusiste',
            'él' => 'impuso', 'nosotros' => 'impusimos', 'ustedes' => 'impusieron', 'ellos' => 'impusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'imponía', 'tú' => 'imponías', 'vos' => 'imponías',
            'él' => 'imponía', 'nosotros' => 'imponíamos', 'ustedes' => 'imponían', 'ellos' => 'imponían',
        ],
        'futuro' => [
            'yo' => 'impondré', 'tú' => 'impondrás', 'vos' => 'impondrás',
            'él' => 'impondrá', 'nosotros' => 'impondremos', 'ustedes' => 'impondrán', 'ellos' => 'impondrán',
        ],
        'condicional' => [
            'yo' => 'impondría', 'tú' => 'impondrías', 'vos' => 'impondrías',
            'él' => 'impondría', 'nosotros' => 'impondríamos', 'ustedes' => 'impondrían', 'ellos' => 'impondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'imponga', 'tú' => 'impongas', 'vos' => 'impongas',
            'él' => 'imponga', 'nosotros' => 'impongamos', 'ustedes' => 'impongan', 'ellos' => 'impongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'impusiera', 'tú' => 'impusieras', 'vos' => 'impusieras',
            'él' => 'impusiera', 'nosotros' => 'impusiéramos', 'ustedes' => 'impusieran', 'ellos' => 'impusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'impón', 'vos' => 'imponé', 'él' => 'imponga',
            'nosotros' => 'impongamos', 'ustedes' => 'impongan', 'ellos' => 'impongan',
        ],
    ],
];

// indemnizar: z→c before e
$data = conjugateAR('indemniz', ['spelling' => ['z', 'c']]);
$data['is_irregular'] = false;
$verbs['indemnizar'] = $data;

// desaparecer: c→zc (like conocer)
$data = conjugateER('desaparec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'desaparezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'desaparezca', 'tú' => 'desaparezcas', 'vos' => 'desaparezcas',
    'él' => 'desaparezca', 'nosotros' => 'desaparezcamos', 'ustedes' => 'desaparezcan', 'ellos' => 'desaparezcan',
];
$data['imperativo']['afirmativo']['él'] = 'desaparezca';
$data['imperativo']['afirmativo']['nosotros'] = 'desaparezcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'desaparezcan';
$data['imperativo']['afirmativo']['ellos'] = 'desaparezcan';
$verbs['desaparecer'] = $data;

// surgir: g→j before a/o
$data = conjugateIR('surg');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'surjo';
$data['subjuntivo']['presente'] = [
    'yo' => 'surja', 'tú' => 'surjas', 'vos' => 'surjas',
    'él' => 'surja', 'nosotros' => 'surjamos', 'ustedes' => 'surjan', 'ellos' => 'surjan',
];
$verbs['surgir'] = $data;

// emerger: g→j before a/o -ER
$data = conjugateER('emerg');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'emerjo';
$data['subjuntivo']['presente'] = [
    'yo' => 'emerja', 'tú' => 'emerjas', 'vos' => 'emerjas',
    'él' => 'emerja', 'nosotros' => 'emerjamos', 'ustedes' => 'emerjan', 'ellos' => 'emerjan',
];
$verbs['emerger'] = $data;

// contribuir: -uir verb (like construir)
$verbs['contribuir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'contribuyo', 'tú' => 'contribuyes', 'vos' => 'contribuís',
            'él' => 'contribuye', 'nosotros' => 'contribuimos', 'ustedes' => 'contribuyen', 'ellos' => 'contribuyen',
        ],
        'pretérito indefinido' => [
            'yo' => 'contribuí', 'tú' => 'contribuiste', 'vos' => 'contribuiste',
            'él' => 'contribuyó', 'nosotros' => 'contribuimos', 'ustedes' => 'contribuyeron', 'ellos' => 'contribuyeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'contribuía', 'tú' => 'contribuías', 'vos' => 'contribuías',
            'él' => 'contribuía', 'nosotros' => 'contribuíamos', 'ustedes' => 'contribuían', 'ellos' => 'contribuían',
        ],
        'futuro' => [
            'yo' => 'contribuiré', 'tú' => 'contribuirás', 'vos' => 'contribuirás',
            'él' => 'contribuirá', 'nosotros' => 'contribuiremos', 'ustedes' => 'contribuirán', 'ellos' => 'contribuirán',
        ],
        'condicional' => [
            'yo' => 'contribuiría', 'tú' => 'contribuirías', 'vos' => 'contribuirías',
            'él' => 'contribuiría', 'nosotros' => 'contribuiríamos', 'ustedes' => 'contribuirían', 'ellos' => 'contribuirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'contribuya', 'tú' => 'contribuyas', 'vos' => 'contribuyas',
            'él' => 'contribuya', 'nosotros' => 'contribuyamos', 'ustedes' => 'contribuyan', 'ellos' => 'contribuyan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'contribuyera', 'tú' => 'contribuyeras', 'vos' => 'contribuyeras',
            'él' => 'contribuyera', 'nosotros' => 'contribuyéramos', 'ustedes' => 'contribuyeran', 'ellos' => 'contribuyeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'contribuye', 'vos' => 'contribuí', 'él' => 'contribuya',
            'nosotros' => 'contribuyamos', 'ustedes' => 'contribuyan', 'ellos' => 'contribuyan',
        ],
    ],
];

// trascender: e→ie -ER
$data = conjugateER('trascend', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['trascender'] = $data;

// acordar: o→ue -AR
$data = conjugateAR('acord', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['acordar'] = $data;

// criticar: c→qu before e
$data = conjugateAR('critic', ['spelling' => ['c', 'qu']]);
$data['is_irregular'] = false;
$verbs['criticar'] = $data;

// === Batch 7 verbs ===

// Regular -AR batch 7
$batch7AR = ['cultivar','cosechar','podar','fertilizar','contaminar',
             'reciclar','reutilizar','migrar','habitar','ocupar',
             'desplazarse','asentarse','marchitarse',
             'pausar','alternar','acelerar','frenar','anticipar',
             'aplazar','retrasar','adelantar','durar','progresar',
             'estancarse','planear',
             'actuar','cantar','bailar','emocionar','sorprender',
             'aburrir','estimular',
             'significar','simbolizar','revelar','ocultar',
             'comunicar','inventar','expresar','contemplar',
             'meditar','representar'];
foreach ($batch7AR as $v) {
    $base = preg_replace('/se$/', '', $v);
    $stem = mb_substr($base, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -ER batch 7
foreach (['suceder','ocurrir'] as $v) {
    if ($v === 'ocurrir') {
        $stem = mb_substr($v, 0, -2);
        $data = conjugateIR($stem);
    } else {
        $stem = mb_substr($v, 0, -2);
        $data = conjugateER($stem);
    }
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// Regular -IR batch 7
foreach (['sobrevivir','esculpir'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateIR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// florecer: c→zc (like conocer)
$data = conjugateER('florec');
$data['is_irregular'] = true;
$data['indicativo']['presente']['yo'] = 'florezco';
$data['subjuntivo']['presente'] = [
    'yo' => 'florezca', 'tú' => 'florezcas', 'vos' => 'florezcas',
    'él' => 'florezca', 'nosotros' => 'florezcamos', 'ustedes' => 'florezcan', 'ellos' => 'florezcan',
];
$data['imperativo']['afirmativo']['él'] = 'florezca';
$data['imperativo']['afirmativo']['nosotros'] = 'florezcamos';
$data['imperativo']['afirmativo']['ustedes'] = 'florezcan';
$data['imperativo']['afirmativo']['ellos'] = 'florezcan';
$verbs['florecer'] = $data;

// germinar: regular -AR
$data = conjugateAR('germin');
$data['is_irregular'] = false;
$verbs['germinar'] = $data;

// sembrar: e→ie -AR
$data = conjugateAR('sembr', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['sembrar'] = $data;

// regar: e→ie + g→gu before e
$data = conjugateAR('reg', ['stem_change' => ['e', 'ie'], 'spelling' => ['g', 'gu']]);
$data['is_irregular'] = true;
$verbs['regar'] = $data;

// variar: accent on í (like confiar)
$data = conjugateAR('vari');
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'varío', 'tú' => 'varías', 'vos' => 'variás',
    'él' => 'varía', 'nosotros' => 'variamos', 'ustedes' => 'varían', 'ellos' => 'varían',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'varíe', 'tú' => 'varíes', 'vos' => 'varíes',
    'él' => 'varíe', 'nosotros' => 'variemos', 'ustedes' => 'varíen', 'ellos' => 'varíen',
];
$data['imperativo']['afirmativo']['tú'] = 'varía';
$verbs['variar'] = $data;

// prever: like ver but with pre-
$verbs['prever'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'preveo', 'tú' => 'prevés', 'vos' => 'prevés',
            'él' => 'prevé', 'nosotros' => 'prevemos', 'ustedes' => 'prevén', 'ellos' => 'prevén',
        ],
        'pretérito indefinido' => [
            'yo' => 'preví', 'tú' => 'previste', 'vos' => 'previste',
            'él' => 'previo', 'nosotros' => 'previmos', 'ustedes' => 'previeron', 'ellos' => 'previeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'preveía', 'tú' => 'preveías', 'vos' => 'preveías',
            'él' => 'preveía', 'nosotros' => 'preveíamos', 'ustedes' => 'preveían', 'ellos' => 'preveían',
        ],
        'futuro' => [
            'yo' => 'preveré', 'tú' => 'preverás', 'vos' => 'preverás',
            'él' => 'preverá', 'nosotros' => 'preveremos', 'ustedes' => 'preverán', 'ellos' => 'preverán',
        ],
        'condicional' => [
            'yo' => 'prevería', 'tú' => 'preverías', 'vos' => 'preverías',
            'él' => 'prevería', 'nosotros' => 'preveríamos', 'ustedes' => 'preverían', 'ellos' => 'preverían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'prevea', 'tú' => 'preveas', 'vos' => 'preveas',
            'él' => 'prevea', 'nosotros' => 'preveamos', 'ustedes' => 'prevean', 'ellos' => 'prevean',
        ],
        'pretérito imperfecto' => [
            'yo' => 'previera', 'tú' => 'previeras', 'vos' => 'previeras',
            'él' => 'previera', 'nosotros' => 'previéramos', 'ustedes' => 'previeran', 'ellos' => 'previeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'prevé', 'vos' => 'prevé', 'él' => 'prevea',
            'nosotros' => 'preveamos', 'ustedes' => 'prevean', 'ellos' => 'prevean',
        ],
    ],
];

// posponer: like poner
$verbs['posponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'pospongo', 'tú' => 'pospones', 'vos' => 'posponés',
            'él' => 'pospone', 'nosotros' => 'posponemos', 'ustedes' => 'posponen', 'ellos' => 'posponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'pospuse', 'tú' => 'pospusiste', 'vos' => 'pospusiste',
            'él' => 'pospuso', 'nosotros' => 'pospusimos', 'ustedes' => 'pospusieron', 'ellos' => 'pospusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'posponía', 'tú' => 'posponías', 'vos' => 'posponías',
            'él' => 'posponía', 'nosotros' => 'posponíamos', 'ustedes' => 'posponían', 'ellos' => 'posponían',
        ],
        'futuro' => [
            'yo' => 'pospondré', 'tú' => 'pospondrás', 'vos' => 'pospondrás',
            'él' => 'pospondrá', 'nosotros' => 'pospondremos', 'ustedes' => 'pospondrán', 'ellos' => 'pospondrán',
        ],
        'condicional' => [
            'yo' => 'pospondría', 'tú' => 'pospondrías', 'vos' => 'pospondrías',
            'él' => 'pospondría', 'nosotros' => 'pospondríamos', 'ustedes' => 'pospondrían', 'ellos' => 'pospondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'posponga', 'tú' => 'pospongas', 'vos' => 'pospongas',
            'él' => 'posponga', 'nosotros' => 'pospongamos', 'ustedes' => 'pospongan', 'ellos' => 'pospongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'pospusiera', 'tú' => 'pospusieras', 'vos' => 'pospusieras',
            'él' => 'pospusiera', 'nosotros' => 'pospusiéramos', 'ustedes' => 'pospusieran', 'ellos' => 'pospusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'pospón', 'vos' => 'posponé', 'él' => 'posponga',
            'nosotros' => 'pospongamos', 'ustedes' => 'pospongan', 'ellos' => 'pospongan',
        ],
    ],
];

// transcurrir: regular -IR
$data = conjugateIR('transcurr');
$data['is_irregular'] = false;
$verbs['transcurrir'] = $data;

// componer: like poner
$verbs['componer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'compongo', 'tú' => 'compones', 'vos' => 'componés',
            'él' => 'compone', 'nosotros' => 'componemos', 'ustedes' => 'componen', 'ellos' => 'componen',
        ],
        'pretérito indefinido' => [
            'yo' => 'compuse', 'tú' => 'compusiste', 'vos' => 'compusiste',
            'él' => 'compuso', 'nosotros' => 'compusimos', 'ustedes' => 'compusieron', 'ellos' => 'compusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'componía', 'tú' => 'componías', 'vos' => 'componías',
            'él' => 'componía', 'nosotros' => 'componíamos', 'ustedes' => 'componían', 'ellos' => 'componían',
        ],
        'futuro' => [
            'yo' => 'compondré', 'tú' => 'compondrás', 'vos' => 'compondrás',
            'él' => 'compondrá', 'nosotros' => 'compondremos', 'ustedes' => 'compondrán', 'ellos' => 'compondrán',
        ],
        'condicional' => [
            'yo' => 'compondría', 'tú' => 'compondrías', 'vos' => 'compondrías',
            'él' => 'compondría', 'nosotros' => 'compondríamos', 'ustedes' => 'compondrían', 'ellos' => 'compondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'componga', 'tú' => 'compongas', 'vos' => 'compongas',
            'él' => 'componga', 'nosotros' => 'compongamos', 'ustedes' => 'compongan', 'ellos' => 'compongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'compusiera', 'tú' => 'compusieras', 'vos' => 'compusieras',
            'él' => 'compusiera', 'nosotros' => 'compusiéramos', 'ustedes' => 'compusieran', 'ellos' => 'compusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'compón', 'vos' => 'componé', 'él' => 'componga',
            'nosotros' => 'compongamos', 'ustedes' => 'compongan', 'ellos' => 'compongan',
        ],
    ],
];

// entretener: like tener
$verbs['entretener'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'entretengo', 'tú' => 'entretienes', 'vos' => 'entretenés',
            'él' => 'entretiene', 'nosotros' => 'entretenemos', 'ustedes' => 'entretienen', 'ellos' => 'entretienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'entretuve', 'tú' => 'entretuviste', 'vos' => 'entretuviste',
            'él' => 'entretuvo', 'nosotros' => 'entretuvimos', 'ustedes' => 'entretuvieron', 'ellos' => 'entretuvieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'entretenía', 'tú' => 'entretenías', 'vos' => 'entretenías',
            'él' => 'entretenía', 'nosotros' => 'entreteníamos', 'ustedes' => 'entretenían', 'ellos' => 'entretenían',
        ],
        'futuro' => [
            'yo' => 'entretendré', 'tú' => 'entretendrás', 'vos' => 'entretendrás',
            'él' => 'entretendrá', 'nosotros' => 'entretendremos', 'ustedes' => 'entretendrán', 'ellos' => 'entretendrán',
        ],
        'condicional' => [
            'yo' => 'entretendría', 'tú' => 'entretendrías', 'vos' => 'entretendrías',
            'él' => 'entretendría', 'nosotros' => 'entretendríamos', 'ustedes' => 'entretendrían', 'ellos' => 'entretendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'entretenga', 'tú' => 'entretengas', 'vos' => 'entretengas',
            'él' => 'entretenga', 'nosotros' => 'entretengamos', 'ustedes' => 'entretengan', 'ellos' => 'entretengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'entretuviera', 'tú' => 'entretuvieras', 'vos' => 'entretuvieras',
            'él' => 'entretuviera', 'nosotros' => 'entretuviéramos', 'ustedes' => 'entretuvieran', 'ellos' => 'entretuvieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'entretén', 'vos' => 'entretené', 'él' => 'entretenga',
            'nosotros' => 'entretengamos', 'ustedes' => 'entretengan', 'ellos' => 'entretengan',
        ],
    ],
];

// distraer: like traer
$verbs['distraer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'distraigo', 'tú' => 'distraes', 'vos' => 'distraés',
            'él' => 'distrae', 'nosotros' => 'distraemos', 'ustedes' => 'distraen', 'ellos' => 'distraen',
        ],
        'pretérito indefinido' => [
            'yo' => 'distraje', 'tú' => 'distrajiste', 'vos' => 'distrajiste',
            'él' => 'distrajo', 'nosotros' => 'distrajimos', 'ustedes' => 'distrajeron', 'ellos' => 'distrajeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'distraía', 'tú' => 'distraías', 'vos' => 'distraías',
            'él' => 'distraía', 'nosotros' => 'distraíamos', 'ustedes' => 'distraían', 'ellos' => 'distraían',
        ],
        'futuro' => [
            'yo' => 'distraeré', 'tú' => 'distraerás', 'vos' => 'distraerás',
            'él' => 'distraerá', 'nosotros' => 'distraeremos', 'ustedes' => 'distraerán', 'ellos' => 'distraerán',
        ],
        'condicional' => [
            'yo' => 'distraería', 'tú' => 'distraerías', 'vos' => 'distraerías',
            'él' => 'distraería', 'nosotros' => 'distraeríamos', 'ustedes' => 'distraerían', 'ellos' => 'distraerían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'distraiga', 'tú' => 'distraigas', 'vos' => 'distraigas',
            'él' => 'distraiga', 'nosotros' => 'distraigamos', 'ustedes' => 'distraigan', 'ellos' => 'distraigan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'distrajera', 'tú' => 'distrajeras', 'vos' => 'distrajeras',
            'él' => 'distrajera', 'nosotros' => 'distrajéramos', 'ustedes' => 'distrajeran', 'ellos' => 'distrajeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'distrae', 'vos' => 'distraé', 'él' => 'distraiga',
            'nosotros' => 'distraigamos', 'ustedes' => 'distraigan', 'ellos' => 'distraigan',
        ],
    ],
];

// conmover: o→ue -ER
$data = conjugateER('conmov', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['conmover'] = $data;

// referir: e→ie present, e→i preterite -IR
$data = conjugateIR('refer', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['referir'] = $data;

// juzgar: g→gu before e
$data = conjugateAR('juzg', ['spelling' => ['g', 'gu']]);
$data['is_irregular'] = false;
$verbs['juzgar'] = $data;

// === Batch 8 verbs ===

// Regular -AR batch 8
$batch8AR = ['experimentar','clasificar','modelar','simular',
             'legislar','vigilar','auditar','investigar','sentenciar'];
foreach ($batch8AR as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// demostrar: o→ue -AR
$data = conjugateAR('demostr', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['demostrar'] = $data;

// gobernar: e→ie -AR
$data = conjugateAR('gobern', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['gobernar'] = $data;

// predecir: like decir
$verbs['predecir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'predigo', 'tú' => 'predices', 'vos' => 'predecís',
            'él' => 'predice', 'nosotros' => 'predecimos', 'ustedes' => 'predicen', 'ellos' => 'predicen',
        ],
        'pretérito indefinido' => [
            'yo' => 'predije', 'tú' => 'predijiste', 'vos' => 'predijiste',
            'él' => 'predijo', 'nosotros' => 'predijimos', 'ustedes' => 'predijeron', 'ellos' => 'predijeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'predecía', 'tú' => 'predecías', 'vos' => 'predecías',
            'él' => 'predecía', 'nosotros' => 'predecíamos', 'ustedes' => 'predecían', 'ellos' => 'predecían',
        ],
        'futuro' => [
            'yo' => 'predeciré', 'tú' => 'predecirás', 'vos' => 'predecirás',
            'él' => 'predecirá', 'nosotros' => 'predeciremos', 'ustedes' => 'predecirán', 'ellos' => 'predecirán',
        ],
        'condicional' => [
            'yo' => 'predeciría', 'tú' => 'predecirías', 'vos' => 'predecirías',
            'él' => 'predeciría', 'nosotros' => 'predeciríamos', 'ustedes' => 'predecirían', 'ellos' => 'predecirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'prediga', 'tú' => 'predigas', 'vos' => 'predigas',
            'él' => 'prediga', 'nosotros' => 'predigamos', 'ustedes' => 'predigan', 'ellos' => 'predigan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'predijera', 'tú' => 'predijeras', 'vos' => 'predijeras',
            'él' => 'predijera', 'nosotros' => 'predijéramos', 'ustedes' => 'predijeran', 'ellos' => 'predijeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'predice', 'vos' => 'predecí', 'él' => 'prediga',
            'nosotros' => 'predigamos', 'ustedes' => 'predigan', 'ellos' => 'predigan',
        ],
    ],
];

// absolver: o→ue -ER
$data = conjugateER('absolv', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['absolver'] = $data;

// concebir: e→i -IR (like pedir)
$data = conjugateIR('conceb', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'concibo', 'tú' => 'concibes', 'vos' => 'concebís',
    'él' => 'concibe', 'nosotros' => 'concebimos', 'ustedes' => 'conciben', 'ellos' => 'conciben',
];
$verbs['concebir'] = $data;

// ─── Phase 2: Remaining 121 verbs ────────────────────────────────

// === Regular -AR (no irregularity) ===
$phase2RegularAR = [
    'abordar','adoptar','afinar','aplicar','arrear','asar',
    'brillar','bucear','callar','captar','categorizar','cenar',
    'concentrar','contraargumentar','contrastar','conversar',
    'decorar','denunciar','desayunar','destacar','diversificar',
    'doblar','enamorar','escampar','escapar','evidenciar',
    'extrañar','funcionar','identificar','implementar',
    'inaugurar','indicar','manipular','montar','nadar','nombrar',
    'opinar','pelear','pregonar','preocupar','quemar','reclamar',
    'reparar','rescatar','resultar','resumir','retomar',
    'reformular','sintetizar','solicitar','surfear','tolerar',
    'tomar','triunfar','visitar','vislumbrar',
    'conjugar','declarar','corroborar','reflejar',
];
foreach ($phase2RegularAR as $v) {
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateAR($stem);
    $verbs[$v]['is_irregular'] = false;
}

// === Spelling-change -AR (c→qu before e) ===
foreach (['amplificar','pescar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR(mb_substr($v, 0, -2), ['spelling' => ['c', 'qu']]);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// === Spelling-change -AR (g→gu before e) ===
foreach (['luchar','madrugar','pregonar'] as $v) {
    // Only madrugar has g→gu
    if ($v === 'madrugar') {
        $stem = mb_substr($v, 0, -2);
        $data = conjugateAR($stem, ['spelling' => ['g', 'gu']]);
        $data['is_irregular'] = false;
        $verbs[$v] = $data;
    }
}
// luchar: regular (no spelling change)
$verbs['luchar'] = conjugateAR('luch');
$verbs['luchar']['is_irregular'] = false;

// === Spelling-change -AR (z→c before e) ===
foreach (['autorizar','garantizar','gozar','reubicar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    if (mb_substr($stem, -1) === 'z') {
        $data = conjugateAR($stem, ['spelling' => ['z', 'c']]);
    } elseif (mb_substr($stem, -1) === 'c') {
        // reubicar: c→qu before e
        $data = conjugateAR($stem, ['spelling' => ['c', 'qu']]);
    } else {
        $data = conjugateAR($stem);
    }
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// === Spelling-change -AR (g→gu) ===
foreach (['cabalgar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem, ['spelling' => ['g', 'gu']]);
    $data['is_irregular'] = false;
    $verbs[$v] = $data;
}

// === Stem-changing -AR (o→ue) ===
foreach (['costar','sonar','tostar','volar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem, ['stem_change' => ['o', 'ue']]);
    $data['is_irregular'] = true;
    $verbs[$v] = $data;
}

// === Stem-changing -AR (o→ue + z→c) ===
// almorzar: o→ue + z→c
$data = conjugateAR('almorz', ['stem_change' => ['o', 'ue'], 'spelling' => ['z', 'c']]);
$data['is_irregular'] = true;
$data['indicativo']['pretérito indefinido']['yo'] = 'almorcé';
$verbs['almorzar'] = $data;

// comenzar: o→ue + z→c  (actually e→ie + z→c)
$data = conjugateAR('comenz', ['stem_change' => ['e', 'ie'], 'spelling' => ['z', 'c']]);
$data['is_irregular'] = true;
$data['indicativo']['pretérito indefinido']['yo'] = 'comencé';
$verbs['comenzar'] = $data;

// === Stem-changing -AR (e→ie) ===
foreach (['nevar'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateAR($stem, ['stem_change' => ['e', 'ie']]);
    $data['is_irregular'] = true;
    $verbs[$v] = $data;
}

// === Stem-changing -AR (u→ue) ===
// jugar
$data = conjugateAR('jug', ['stem_change' => ['u', 'ue']]);
$data['is_irregular'] = true;
// jugar also has g→gu in preterite 1s and subjunctive
$data['indicativo']['pretérito indefinido']['yo'] = 'jugué';
$subjBase = 'juegu';
$data['subjuntivo']['presente'] = [
    'yo' => 'juegue', 'tú' => 'juegues', 'vos' => 'juegues',
    'él' => 'juegue', 'nosotros' => 'juguemos', 'ustedes' => 'jueguen', 'ellos' => 'jueguen',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'juega', 'vos' => 'jugá', 'él' => 'juegue',
    'nosotros' => 'juguemos', 'ustedes' => 'jueguen', 'ellos' => 'jueguen',
];
$verbs['jugar'] = $data;

// === -ER verbs: -zco pattern (agradecer, amanecer, atardecer, establecer, fallecer, pertenecer, renacer) ===
$zcoVerbs = ['agradecer','amanecer','atardecer','establecer','fallecer','pertenecer','renacer'];
foreach ($zcoVerbs as $v) {
    $stem = mb_substr($v, 0, -2); // e.g. 'agradec'
    $data = conjugateER($stem);
    $data['is_irregular'] = true;
    // -zco in yo present
    $zcoStem = mb_substr($stem, 0, -1) . 'zc'; // agradec → agradezc
    $data['indicativo']['presente']['yo'] = $zcoStem . 'o';
    // Subjunctive present uses -zco stem
    $data['subjuntivo']['presente'] = [
        'yo' => $zcoStem . 'a', 'tú' => $zcoStem . 'as', 'vos' => $zcoStem . 'as',
        'él' => $zcoStem . 'a', 'nosotros' => $zcoStem . 'amos', 'ustedes' => $zcoStem . 'an', 'ellos' => $zcoStem . 'an',
    ];
    $data['imperativo']['afirmativo'] = [
        'tú' => $stem . 'e', 'vos' => $stem . 'é', 'él' => $zcoStem . 'a',
        'nosotros' => $zcoStem . 'amos', 'ustedes' => $zcoStem . 'an', 'ellos' => $zcoStem . 'an',
    ];
    $verbs[$v] = $data;
}

// === Regular -ER ===
$phase2RegularER = [
    'comprometer','recorrer','pesar','tejer',
];
foreach ($phase2RegularER as $v) {
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateER($stem);
    $verbs[$v]['is_irregular'] = false;
}

// === Stem-changing -ER (e→ie) ===
// atender: e→ie
$data = conjugateER('atend', ['stem_change' => ['e', 'ie']]);
$data['is_irregular'] = true;
$verbs['atender'] = $data;

// === Stem-changing -ER (o→ue) ===
foreach (['moler','morder','llover'] as $v) {
    $stem = mb_substr($v, 0, -2);
    $data = conjugateER($stem, ['stem_change' => ['o', 'ue']]);
    $data['is_irregular'] = true;
    $verbs[$v] = $data;
}

// === Spelling-change -ER (g→j before a/o) ===
// ejercer: c→z before a/o
$data = conjugateER('ejerc');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'ejerzo';
$data['subjuntivo']['presente'] = [
    'yo' => 'ejerza', 'tú' => 'ejerzas', 'vos' => 'ejerzas',
    'él' => 'ejerza', 'nosotros' => 'ejerzamos', 'ustedes' => 'ejerzan', 'ellos' => 'ejerzan',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'ejerce', 'vos' => 'ejercé', 'él' => 'ejerza',
    'nosotros' => 'ejerzamos', 'ustedes' => 'ejerzan', 'ellos' => 'ejerzan',
];
$verbs['ejercer'] = $data;

// recoger: g→j before a/o
$data = conjugateER('recog');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'recojo';
$data['subjuntivo']['presente'] = [
    'yo' => 'recoja', 'tú' => 'recojas', 'vos' => 'recojas',
    'él' => 'recoja', 'nosotros' => 'recojamos', 'ustedes' => 'recojan', 'ellos' => 'recojan',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'recoge', 'vos' => 'recogé', 'él' => 'recoja',
    'nosotros' => 'recojamos', 'ustedes' => 'recojan', 'ellos' => 'recojan',
];
$verbs['recoger'] = $data;

// === Regular -IR ===
$phase2RegularIR = [
    'aludir','asistir','fingir','incumplir','insistir','remitir',
    'corroborar', // actually -ar, caught above
];
foreach ($phase2RegularIR as $v) {
    if (!preg_match('/ir$/', $v)) continue; // skip non -ir
    $stem = mb_substr($v, 0, -2);
    $verbs[$v] = conjugateIR($stem);
    $verbs[$v]['is_irregular'] = false;
}

// fingir: g→j before a/o
$data = conjugateIR('fing');
$data['is_irregular'] = false;
$data['indicativo']['presente']['yo'] = 'finjo';
$data['subjuntivo']['presente'] = [
    'yo' => 'finja', 'tú' => 'finjas', 'vos' => 'finjas',
    'él' => 'finja', 'nosotros' => 'finjamos', 'ustedes' => 'finjan', 'ellos' => 'finjan',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'finge', 'vos' => 'fingí', 'él' => 'finja',
    'nosotros' => 'finjamos', 'ustedes' => 'finjan', 'ellos' => 'finjan',
];
$verbs['fingir'] = $data;

// === -UIR verbs (y-insertion: incluir, constituir, diluir, distribuir) ===
$uirVerbs = ['incluir','constituir','diluir','distribuir'];
foreach ($uirVerbs as $v) {
    $stem = mb_substr($v, 0, -2); // e.g. 'inclu'
    $baseStem = mb_substr($v, 0, -3); // e.g. 'incl'
    $data = conjugateIR($stem);
    $data['is_irregular'] = true;
    // Present: y-insertion in yo, tú, él, ellos
    $data['indicativo']['presente'] = [
        'yo' => $baseStem . 'uyo', 'tú' => $baseStem . 'uyes', 'vos' => $baseStem . 'uís',
        'él' => $baseStem . 'uye', 'nosotros' => $baseStem . 'uimos', 'ustedes' => $baseStem . 'uyen', 'ellos' => $baseStem . 'uyen',
    ];
    // Preterite: 3s/3p use y (incluyó, incluyeron)
    $data['indicativo']['pretérito indefinido']['él'] = $baseStem . 'uyó';
    $data['indicativo']['pretérito indefinido']['ustedes'] = $baseStem . 'uyeron';
    $data['indicativo']['pretérito indefinido']['ellos'] = $baseStem . 'uyeron';
    // Subjunctive present
    $data['subjuntivo']['presente'] = [
        'yo' => $baseStem . 'uya', 'tú' => $baseStem . 'uyas', 'vos' => $baseStem . 'uyas',
        'él' => $baseStem . 'uya', 'nosotros' => $baseStem . 'uyamos', 'ustedes' => $baseStem . 'uyan', 'ellos' => $baseStem . 'uyan',
    ];
    // Subjunctive imperfect
    $data['subjuntivo']['pretérito imperfecto'] = [
        'yo' => $baseStem . 'uyera', 'tú' => $baseStem . 'uyeras', 'vos' => $baseStem . 'uyeras',
        'él' => $baseStem . 'uyera', 'nosotros' => $baseStem . 'uyéramos', 'ustedes' => $baseStem . 'uyeran', 'ellos' => $baseStem . 'uyeran',
    ];
    // Imperative
    $data['imperativo']['afirmativo'] = [
        'tú' => $baseStem . 'uye', 'vos' => $baseStem . 'uí', 'él' => $baseStem . 'uya',
        'nosotros' => $baseStem . 'uyamos', 'ustedes' => $baseStem . 'uyan', 'ellos' => $baseStem . 'uyan',
    ];
    $verbs[$v] = $data;
}

// === Stem-changing -IR (e→ie, e→i in pret) ===
// hervir: e→ie present, e→i preterite
$data = conjugateIR('herv', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['hervir'] = $data;

// requerir: e→ie present, e→i preterite
$data = conjugateIR('requer', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['requerir'] = $data;

// transferir: e→ie present, e→i preterite
$data = conjugateIR('transfer', ['stem_change' => ['e', 'ie'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$verbs['transferir'] = $data;

// intervenir (like venir)
$verbs['intervenir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'intervengo', 'tú' => 'intervienes', 'vos' => 'intervenís',
            'él' => 'interviene', 'nosotros' => 'intervenimos', 'ustedes' => 'intervienen', 'ellos' => 'intervienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'intervine', 'tú' => 'interviniste', 'vos' => 'interviniste',
            'él' => 'intervino', 'nosotros' => 'intervinimos', 'ustedes' => 'intervinieron', 'ellos' => 'intervinieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'intervenía', 'tú' => 'intervenías', 'vos' => 'intervenías',
            'él' => 'intervenía', 'nosotros' => 'interveníamos', 'ustedes' => 'intervenían', 'ellos' => 'intervenían',
        ],
        'futuro' => [
            'yo' => 'intervendré', 'tú' => 'intervendrás', 'vos' => 'intervendrás',
            'él' => 'intervendrá', 'nosotros' => 'intervendremos', 'ustedes' => 'intervendrán', 'ellos' => 'intervendrán',
        ],
        'condicional' => [
            'yo' => 'intervendría', 'tú' => 'intervendrías', 'vos' => 'intervendrías',
            'él' => 'intervendría', 'nosotros' => 'intervendríamos', 'ustedes' => 'intervendrían', 'ellos' => 'intervendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'intervenga', 'tú' => 'intervengas', 'vos' => 'intervengas',
            'él' => 'intervenga', 'nosotros' => 'intervengamos', 'ustedes' => 'intervengan', 'ellos' => 'intervengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'interviniera', 'tú' => 'intervinieras', 'vos' => 'intervinieras',
            'él' => 'interviniera', 'nosotros' => 'interviniéramos', 'ustedes' => 'intervinieran', 'ellos' => 'intervinieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'intervén', 'vos' => 'intervení', 'él' => 'intervenga',
            'nosotros' => 'intervengamos', 'ustedes' => 'intervengan', 'ellos' => 'intervengan',
        ],
    ],
];

// prevenir (like venir)
$verbs['prevenir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'prevengo', 'tú' => 'previenes', 'vos' => 'prevenís',
            'él' => 'previene', 'nosotros' => 'prevenimos', 'ustedes' => 'previenen', 'ellos' => 'previenen',
        ],
        'pretérito indefinido' => [
            'yo' => 'previne', 'tú' => 'previniste', 'vos' => 'previniste',
            'él' => 'previno', 'nosotros' => 'previnimos', 'ustedes' => 'previnieron', 'ellos' => 'previnieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'prevenía', 'tú' => 'prevenías', 'vos' => 'prevenías',
            'él' => 'prevenía', 'nosotros' => 'preveníamos', 'ustedes' => 'prevenían', 'ellos' => 'prevenían',
        ],
        'futuro' => [
            'yo' => 'prevendré', 'tú' => 'prevendrás', 'vos' => 'prevendrás',
            'él' => 'prevendrá', 'nosotros' => 'prevendremos', 'ustedes' => 'prevendrán', 'ellos' => 'prevendrán',
        ],
        'condicional' => [
            'yo' => 'prevendría', 'tú' => 'prevendrías', 'vos' => 'prevendrías',
            'él' => 'prevendría', 'nosotros' => 'prevendríamos', 'ustedes' => 'prevendrían', 'ellos' => 'prevendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'prevenga', 'tú' => 'prevengas', 'vos' => 'prevengas',
            'él' => 'prevenga', 'nosotros' => 'prevengamos', 'ustedes' => 'prevengan', 'ellos' => 'prevengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'previniera', 'tú' => 'previnieras', 'vos' => 'previnieras',
            'él' => 'previniera', 'nosotros' => 'previniéramos', 'ustedes' => 'previnieran', 'ellos' => 'previnieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'prevén', 'vos' => 'prevení', 'él' => 'prevenga',
            'nosotros' => 'prevengamos', 'ustedes' => 'prevengan', 'ellos' => 'prevengan',
        ],
    ],
];

// convenir (like venir)
$verbs['convenir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'convengo', 'tú' => 'convienes', 'vos' => 'convenís',
            'él' => 'conviene', 'nosotros' => 'convenimos', 'ustedes' => 'convienen', 'ellos' => 'convienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'convine', 'tú' => 'conviniste', 'vos' => 'conviniste',
            'él' => 'convino', 'nosotros' => 'convinimos', 'ustedes' => 'convinieron', 'ellos' => 'convinieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'convenía', 'tú' => 'convenías', 'vos' => 'convenías',
            'él' => 'convenía', 'nosotros' => 'conveníamos', 'ustedes' => 'convenían', 'ellos' => 'convenían',
        ],
        'futuro' => [
            'yo' => 'convendré', 'tú' => 'convendrás', 'vos' => 'convendrás',
            'él' => 'convendrá', 'nosotros' => 'convendremos', 'ustedes' => 'convendrán', 'ellos' => 'convendrán',
        ],
        'condicional' => [
            'yo' => 'convendría', 'tú' => 'convendrías', 'vos' => 'convendrías',
            'él' => 'convendría', 'nosotros' => 'convendríamos', 'ustedes' => 'convendrían', 'ellos' => 'convendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'convenga', 'tú' => 'convengas', 'vos' => 'convengas',
            'él' => 'convenga', 'nosotros' => 'convengamos', 'ustedes' => 'convengan', 'ellos' => 'convengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'conviniera', 'tú' => 'convinieras', 'vos' => 'convinieras',
            'él' => 'conviniera', 'nosotros' => 'conviniéramos', 'ustedes' => 'convinieran', 'ellos' => 'convinieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'convén', 'vos' => 'convení', 'él' => 'convenga',
            'nosotros' => 'convengamos', 'ustedes' => 'convengan', 'ellos' => 'convengan',
        ],
    ],
];

// disponer (like poner)
$verbs['disponer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'dispongo', 'tú' => 'dispones', 'vos' => 'disponés',
            'él' => 'dispone', 'nosotros' => 'disponemos', 'ustedes' => 'disponen', 'ellos' => 'disponen',
        ],
        'pretérito indefinido' => [
            'yo' => 'dispuse', 'tú' => 'dispusiste', 'vos' => 'dispusiste',
            'él' => 'dispuso', 'nosotros' => 'dispusimos', 'ustedes' => 'dispusieron', 'ellos' => 'dispusieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'disponía', 'tú' => 'disponías', 'vos' => 'disponías',
            'él' => 'disponía', 'nosotros' => 'disponíamos', 'ustedes' => 'disponían', 'ellos' => 'disponían',
        ],
        'futuro' => [
            'yo' => 'dispondré', 'tú' => 'dispondrás', 'vos' => 'dispondrás',
            'él' => 'dispondrá', 'nosotros' => 'dispondremos', 'ustedes' => 'dispondrán', 'ellos' => 'dispondrán',
        ],
        'condicional' => [
            'yo' => 'dispondría', 'tú' => 'dispondrías', 'vos' => 'dispondrías',
            'él' => 'dispondría', 'nosotros' => 'dispondríamos', 'ustedes' => 'dispondrían', 'ellos' => 'dispondrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'disponga', 'tú' => 'dispongas', 'vos' => 'dispongas',
            'él' => 'disponga', 'nosotros' => 'dispongamos', 'ustedes' => 'dispongan', 'ellos' => 'dispongan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'dispusiera', 'tú' => 'dispusieras', 'vos' => 'dispusieras',
            'él' => 'dispusiera', 'nosotros' => 'dispusiéramos', 'ustedes' => 'dispusieran', 'ellos' => 'dispusieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'dispón', 'vos' => 'disponé', 'él' => 'disponga',
            'nosotros' => 'dispongamos', 'ustedes' => 'dispongan', 'ellos' => 'dispongan',
        ],
    ],
];

// sostener (like tener)
$verbs['sostener'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'sostengo', 'tú' => 'sostienes', 'vos' => 'sostenés',
            'él' => 'sostiene', 'nosotros' => 'sostenemos', 'ustedes' => 'sostienen', 'ellos' => 'sostienen',
        ],
        'pretérito indefinido' => [
            'yo' => 'sostuve', 'tú' => 'sostuviste', 'vos' => 'sostuviste',
            'él' => 'sostuvo', 'nosotros' => 'sostuvimos', 'ustedes' => 'sostuvieron', 'ellos' => 'sostuvieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'sostenía', 'tú' => 'sostenías', 'vos' => 'sostenías',
            'él' => 'sostenía', 'nosotros' => 'sosteníamos', 'ustedes' => 'sostenían', 'ellos' => 'sostenían',
        ],
        'futuro' => [
            'yo' => 'sostendré', 'tú' => 'sostendrás', 'vos' => 'sostendrás',
            'él' => 'sostendrá', 'nosotros' => 'sostendremos', 'ustedes' => 'sostendrán', 'ellos' => 'sostendrán',
        ],
        'condicional' => [
            'yo' => 'sostendría', 'tú' => 'sostendrías', 'vos' => 'sostendrías',
            'él' => 'sostendría', 'nosotros' => 'sostendríamos', 'ustedes' => 'sostendrían', 'ellos' => 'sostendrían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'sostenga', 'tú' => 'sostengas', 'vos' => 'sostengas',
            'él' => 'sostenga', 'nosotros' => 'sostengamos', 'ustedes' => 'sostengan', 'ellos' => 'sostengan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'sostuviera', 'tú' => 'sostuvieras', 'vos' => 'sostuvieras',
            'él' => 'sostuviera', 'nosotros' => 'sostuviéramos', 'ustedes' => 'sostuvieran', 'ellos' => 'sostuvieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'sostén', 'vos' => 'sostené', 'él' => 'sostenga',
            'nosotros' => 'sostengamos', 'ustedes' => 'sostengan', 'ellos' => 'sostengan',
        ],
    ],
];

// satisfacer (like hacer)
$verbs['satisfacer'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'satisfago', 'tú' => 'satisfaces', 'vos' => 'satisfacés',
            'él' => 'satisface', 'nosotros' => 'satisfacemos', 'ustedes' => 'satisfacen', 'ellos' => 'satisfacen',
        ],
        'pretérito indefinido' => [
            'yo' => 'satisfice', 'tú' => 'satisficiste', 'vos' => 'satisficiste',
            'él' => 'satisfizo', 'nosotros' => 'satisficimos', 'ustedes' => 'satisficieron', 'ellos' => 'satisficieron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'satisfacía', 'tú' => 'satisfacías', 'vos' => 'satisfacías',
            'él' => 'satisfacía', 'nosotros' => 'satisfacíamos', 'ustedes' => 'satisfacían', 'ellos' => 'satisfacían',
        ],
        'futuro' => [
            'yo' => 'satisfaré', 'tú' => 'satisfarás', 'vos' => 'satisfarás',
            'él' => 'satisfará', 'nosotros' => 'satisfaremos', 'ustedes' => 'satisfarán', 'ellos' => 'satisfarán',
        ],
        'condicional' => [
            'yo' => 'satisfaría', 'tú' => 'satisfarías', 'vos' => 'satisfarías',
            'él' => 'satisfaría', 'nosotros' => 'satisfaríamos', 'ustedes' => 'satisfarían', 'ellos' => 'satisfarían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'satisfaga', 'tú' => 'satisfagas', 'vos' => 'satisfagas',
            'él' => 'satisfaga', 'nosotros' => 'satisfagamos', 'ustedes' => 'satisfagan', 'ellos' => 'satisfagan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'satisficiera', 'tú' => 'satisficieras', 'vos' => 'satisficieras',
            'él' => 'satisficiera', 'nosotros' => 'satisficiéramos', 'ustedes' => 'satisficieran', 'ellos' => 'satisficieran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'satisfaz', 'vos' => 'satisfacé', 'él' => 'satisfaga',
            'nosotros' => 'satisfagamos', 'ustedes' => 'satisfagan', 'ellos' => 'satisfagan',
        ],
    ],
];

// traducir (like reducir — -zco + -uj- preterite)
$verbs['traducir'] = [
    'is_irregular' => true,
    'indicativo' => [
        'presente' => [
            'yo' => 'traduzco', 'tú' => 'traduces', 'vos' => 'traducís',
            'él' => 'traduce', 'nosotros' => 'traducimos', 'ustedes' => 'traducen', 'ellos' => 'traducen',
        ],
        'pretérito indefinido' => [
            'yo' => 'traduje', 'tú' => 'tradujiste', 'vos' => 'tradujiste',
            'él' => 'tradujo', 'nosotros' => 'tradujimos', 'ustedes' => 'tradujeron', 'ellos' => 'tradujeron',
        ],
        'pretérito imperfecto' => [
            'yo' => 'traducía', 'tú' => 'traducías', 'vos' => 'traducías',
            'él' => 'traducía', 'nosotros' => 'traducíamos', 'ustedes' => 'traducían', 'ellos' => 'traducían',
        ],
        'futuro' => [
            'yo' => 'traduciré', 'tú' => 'traducirás', 'vos' => 'traducirás',
            'él' => 'traducirá', 'nosotros' => 'traduciremos', 'ustedes' => 'traducirán', 'ellos' => 'traducirán',
        ],
        'condicional' => [
            'yo' => 'traduciría', 'tú' => 'traducirías', 'vos' => 'traducirías',
            'él' => 'traduciría', 'nosotros' => 'traduciríamos', 'ustedes' => 'traducirían', 'ellos' => 'traducirían',
        ],
    ],
    'subjuntivo' => [
        'presente' => [
            'yo' => 'traduzca', 'tú' => 'traduzcas', 'vos' => 'traduzcas',
            'él' => 'traduzca', 'nosotros' => 'traduzcamos', 'ustedes' => 'traduzcan', 'ellos' => 'traduzcan',
        ],
        'pretérito imperfecto' => [
            'yo' => 'tradujera', 'tú' => 'tradujeras', 'vos' => 'tradujeras',
            'él' => 'tradujera', 'nosotros' => 'tradujéramos', 'ustedes' => 'tradujeran', 'ellos' => 'tradujeran',
        ],
    ],
    'imperativo' => [
        'afirmativo' => [
            'tú' => 'traduce', 'vos' => 'traducí', 'él' => 'traduzca',
            'nosotros' => 'traduzcamos', 'ustedes' => 'traduzcan', 'ellos' => 'traduzcan',
        ],
    ],
];

// traer (already exists in conjugations but may need to be in this generation script)
// Check: if already in $verbs, skip
if (!isset($verbs['traer'])) {
    $verbs['traer'] = [
        'is_irregular' => true,
        'indicativo' => [
            'presente' => [
                'yo' => 'traigo', 'tú' => 'traes', 'vos' => 'traés',
                'él' => 'trae', 'nosotros' => 'traemos', 'ustedes' => 'traen', 'ellos' => 'traen',
            ],
            'pretérito indefinido' => [
                'yo' => 'traje', 'tú' => 'trajiste', 'vos' => 'trajiste',
                'él' => 'trajo', 'nosotros' => 'trajimos', 'ustedes' => 'trajeron', 'ellos' => 'trajeron',
            ],
            'pretérito imperfecto' => [
                'yo' => 'traía', 'tú' => 'traías', 'vos' => 'traías',
                'él' => 'traía', 'nosotros' => 'traíamos', 'ustedes' => 'traían', 'ellos' => 'traían',
            ],
            'futuro' => [
                'yo' => 'traeré', 'tú' => 'traerás', 'vos' => 'traerás',
                'él' => 'traerá', 'nosotros' => 'traeremos', 'ustedes' => 'traerán', 'ellos' => 'traerán',
            ],
            'condicional' => [
                'yo' => 'traería', 'tú' => 'traerías', 'vos' => 'traerías',
                'él' => 'traería', 'nosotros' => 'traeríamos', 'ustedes' => 'traerían', 'ellos' => 'traerían',
            ],
        ],
        'subjuntivo' => [
            'presente' => [
                'yo' => 'traiga', 'tú' => 'traigas', 'vos' => 'traigas',
                'él' => 'traiga', 'nosotros' => 'traigamos', 'ustedes' => 'traigan', 'ellos' => 'traigan',
            ],
            'pretérito imperfecto' => [
                'yo' => 'trajera', 'tú' => 'trajeras', 'vos' => 'trajeras',
                'él' => 'trajera', 'nosotros' => 'trajéramos', 'ustedes' => 'trajeran', 'ellos' => 'trajeran',
            ],
        ],
        'imperativo' => [
            'afirmativo' => [
                'tú' => 'trae', 'vos' => 'traé', 'él' => 'traiga',
                'nosotros' => 'traigamos', 'ustedes' => 'traigan', 'ellos' => 'traigan',
            ],
        ],
    ];
}

// pedir: e→i -IR
$data = conjugateIR('ped', ['stem_change' => ['e', 'i'], 'pret_stem_change' => ['e', 'i']]);
$data['is_irregular'] = true;
$data['indicativo']['presente'] = [
    'yo' => 'pido', 'tú' => 'pides', 'vos' => 'pedís',
    'él' => 'pide', 'nosotros' => 'pedimos', 'ustedes' => 'piden', 'ellos' => 'piden',
];
$data['indicativo']['pretérito indefinido'] = [
    'yo' => 'pedí', 'tú' => 'pediste', 'vos' => 'pediste',
    'él' => 'pidió', 'nosotros' => 'pedimos', 'ustedes' => 'pidieron', 'ellos' => 'pidieron',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'pida', 'tú' => 'pidas', 'vos' => 'pidas',
    'él' => 'pida', 'nosotros' => 'pidamos', 'ustedes' => 'pidan', 'ellos' => 'pidan',
];
$data['subjuntivo']['pretérito imperfecto'] = [
    'yo' => 'pidiera', 'tú' => 'pidieras', 'vos' => 'pidieras',
    'él' => 'pidiera', 'nosotros' => 'pidiéramos', 'ustedes' => 'pidieran', 'ellos' => 'pidieran',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'pide', 'vos' => 'pedí', 'él' => 'pida',
    'nosotros' => 'pidamos', 'ustedes' => 'pidan', 'ellos' => 'pidan',
];
$verbs['pedir'] = $data;

// reescribir: regular -ir (irregular participle, but we don't track that)
$data = conjugateIR('reescrib');
$data['is_irregular'] = false;
$verbs['reescribir'] = $data;

// === Reflexive verbs: store base form conjugations ===
// moverse: o→ue stem change -ER
$data = conjugateER('mov', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['moverse'] = $data;

// quedarse: regular -AR
$data = conjugateAR('qued');
$data['is_irregular'] = false;
$verbs['quedarse'] = $data;

// === Impersonal/defective verbs ===
// llover: o→ue, normally only 3rd person
$data = conjugateER('llov', ['stem_change' => ['o', 'ue']]);
$data['is_irregular'] = true;
$verbs['llover'] = $data;

// amanecer/atardecer: already handled in -zco section above

// escampar: regular -AR (impersonal but conjugated normally)
// already in regularAR list above

// === Remaining -AR with special attention ===
// atenuar: regular -AR (the u doesn't change)
$data = conjugateAR('atenu');
// atenuar actually has accent changes: atenúo, atenúas, etc.
$data['indicativo']['presente'] = [
    'yo' => 'atenúo', 'tú' => 'atenúas', 'vos' => 'atenuás',
    'él' => 'atenúa', 'nosotros' => 'atenuamos', 'ustedes' => 'atenúan', 'ellos' => 'atenúan',
];
$data['subjuntivo']['presente'] = [
    'yo' => 'atenúe', 'tú' => 'atenúes', 'vos' => 'atenúes',
    'él' => 'atenúe', 'nosotros' => 'atenuemos', 'ustedes' => 'atenúen', 'ellos' => 'atenúen',
];
$data['imperativo']['afirmativo'] = [
    'tú' => 'atenúa', 'vos' => 'atenuá', 'él' => 'atenúe',
    'nosotros' => 'atenuemos', 'ustedes' => 'atenúen', 'ellos' => 'atenúen',
];
$data['is_irregular'] = true;
$verbs['atenuar'] = $data;

// === Remaining simple -AR verbs that may have been missed ===
$moreRegularAR = ['girar','implicar','secar','tomar'];
foreach ($moreRegularAR as $v) {
    if (!isset($verbs[$v])) {
        $stem = mb_substr($v, 0, -2);
        if ($v === 'secar') {
            $data = conjugateAR($stem, ['spelling' => ['c', 'qu']]);
        } else {
            $data = conjugateAR($stem);
        }
        $data['is_irregular'] = false;
        $verbs[$v] = $data;
    }
}

// nevar: already handled above in stem-changing section

// ─── Merge with existing ──────────────────────────────────────────

$merged = $existing;
$added = 0;
$updated = 0;

foreach ($verbs as $infinitive => $conjugation) {
    if (isset($merged[$infinitive])) {
        $updated++;
    } else {
        $added++;
    }
    $merged[$infinitive] = $conjugation;
}

// Write output
$json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($outFile, $json . "\n");

echo "\n=== Conjugation Generation Complete ===\n";
echo "  Added: $added new verbs\n";
echo "  Updated: $updated existing verbs\n";
echo "  Total: " . count($merged) . " verbs in file\n";

// Verify a few
echo "\n--- Spot check ---\n";
$checks = ['pensar', 'seguir', 'llegar', 'ofrecer', 'sentir'];
foreach ($checks as $v) {
    if (isset($merged[$v])) {
        $p = $merged[$v]['indicativo']['presente'];
        echo "  $v presente: yo={$p['yo']}, tú={$p['tú']}, él={$p['él']}, nosotros={$p['nosotros']}\n";
    }
}
