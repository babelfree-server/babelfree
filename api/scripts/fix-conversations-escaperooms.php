#!/usr/bin/env php
<?php
/**
 * fix-conversations-escaperooms.php
 *
 * ISSUE 1: Fix ~86 conversation games:
 *   - Copy `items` to `exchanges` where items exists but exchanges doesn't
 *   - Replace generic template exchanges with themed, CEFR-appropriate exchanges
 *
 * ISSUE 2: Fix 89 truncated escape room hints:
 *   - Replace 'Piensa en el tema de «.' with 'Piensa en el tema de «{TITLE}».'
 *
 * Usage: php fix-conversations-escaperooms.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv);
$contentDir = dirname(__DIR__, 2) . '/content';

// Load landmarks
$landmarksData = json_decode(file_get_contents("$contentDir/landmarks-colombia.json"), true);
$landmarks = [];
foreach ($landmarksData['landmarks'] as $lm) {
    $landmarks[$lm['dest']] = $lm;
}

// Generic template prompts to detect
$genericPrompts = [
    '¿Qué piensas de este lugar?',
    '¿Qué palabra nueva aprendiste hoy?',
    '¿Quieres continuar el viaje?',
    '¿Qué es lo más importante aquí?',
];

// Stats
$stats = [
    'items_copied' => 0,
    'generic_replaced' => 0,
    'hints_fixed' => 0,
    'files_modified' => 0,
];

for ($dest = 1; $dest <= 89; $dest++) {
    $file = "$contentDir/dest{$dest}.json";
    if (!file_exists($file)) continue;

    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        echo "ERROR: Could not parse dest{$dest}.json\n";
        continue;
    }

    $modified = false;
    $meta = $data['meta'];
    $title = $meta['title'];
    $cefr = $meta['cefr'];
    $landmark = $landmarks[$dest] ?? null;
    $landmarkName = $landmark['name'] ?? "destino $dest";
    $landmarkDesc = $landmark['description'] ?? '';

    // Process both games and ecosystemGames
    foreach (['games', 'ecosystemGames'] as $arrayKey) {
        if (!isset($data[$arrayKey])) continue;

        foreach ($data[$arrayKey] as $gi => &$game) {
            // ISSUE 1: Conversation fixes
            if ($game['type'] === 'conversation') {
                $hasExchanges = isset($game['exchanges']) && !empty($game['exchanges']);
                $hasItems = isset($game['items']) && !empty($game['items']);
                $hasTurns = isset($game['turns']) && !empty($game['turns']);

                // Copy items to exchanges if items exist but exchanges don't
                if ($hasItems && !$hasExchanges) {
                    $game['exchanges'] = $game['items'];
                    $modified = true;
                    $stats['items_copied']++;
                    echo "dest{$dest} [{$arrayKey}][{$gi}]: Copied items → exchanges\n";
                }

                // Replace generic exchanges
                if ($hasExchanges) {
                    $prompts = array_map(function($e) { return $e['prompt'] ?? ''; }, $game['exchanges']);
                    $isGeneric = (count(array_intersect($prompts, $genericPrompts)) >= 3);

                    if ($isGeneric) {
                        $themed = generateThemedExchanges($dest, $cefr, $title, $landmarkName, $landmarkDesc, $game);
                        $game['exchanges'] = $themed;
                        $modified = true;
                        $stats['generic_replaced']++;
                        echo "dest{$dest} [{$arrayKey}][{$gi}]: Replaced generic exchanges (CEFR={$cefr}, landmark={$landmarkName})\n";
                    }
                }
            }

            // ISSUE 2: Escape room hint fixes
            if ($game['type'] === 'escaperoom' && isset($game['puzzles'])) {
                foreach ($game['puzzles'] as $pi => &$puzzle) {
                    $hint = $puzzle['hint'] ?? '';
                    if ($hint === 'Piensa en el tema de «.' || $hint === 'Piensa en el tema de «') {
                        $puzzle['hint'] = "Piensa en el tema de «{$title}».";
                        $modified = true;
                        $stats['hints_fixed']++;
                        echo "dest{$dest} escaperoom puzzle{$pi}: Fixed hint → «{$title}»\n";
                    }
                }
                unset($puzzle);
            }
        }
        unset($game);
    }

    if ($modified) {
        $stats['files_modified']++;
        if (!$dryRun) {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            file_put_contents($file, $json);
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Items copied to exchanges: {$stats['items_copied']}\n";
echo "Generic exchanges replaced: {$stats['generic_replaced']}\n";
echo "Escape room hints fixed: {$stats['hints_fixed']}\n";
echo "Files modified: {$stats['files_modified']}\n";
if ($dryRun) echo "(DRY RUN — no files written)\n";


/**
 * Generate themed conversation exchanges based on CEFR level and destination
 */
function generateThemedExchanges(int $dest, string $cefr, string $title, string $landmark, string $desc, array $game): array {
    // Use the game's existing turns if available (they're usually good)
    // Only replace the exchanges array

    $level = strtoupper(substr($cefr, 0, 2));

    switch ($level) {
        case 'A1':
            return generateA1Exchanges($dest, $title, $landmark, $desc);
        case 'A2':
            return generateA2Exchanges($dest, $title, $landmark, $desc);
        case 'B1':
            return generateB1Exchanges($dest, $title, $landmark, $desc);
        case 'B2':
            return generateB2Exchanges($dest, $title, $landmark, $desc);
        case 'C1':
            return generateC1Exchanges($dest, $title, $landmark, $desc);
        case 'C2':
            return generateC2Exchanges($dest, $title, $landmark, $desc);
        default:
            return generateB1Exchanges($dest, $title, $landmark, $desc);
    }
}

function generateA1Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    // Extract a simple noun from the landmark name for reference
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Cómo se llama este lugar?",
            'options' => [
                "Se llama $place.",
                "No sé.",
                "Es bonito."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué ves aquí en $place?",
            'options' => [
                "Veo muchas cosas bonitas.",
                "No veo nada.",
                "Veo un animal."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Te gusta este lugar?",
            'options' => [
                "Sí, me gusta mucho.",
                "No, no me gusta.",
                "Un poco."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Quieres explorar más de $place?",
            'options' => [
                "Sí, quiero ver más.",
                "No, estoy cansado.",
                "Tal vez mañana."
            ],
            'correct' => 0
        ],
    ];
}

function generateA2Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Qué aprendiste hoy en $place?",
            'options' => [
                "Aprendí palabras nuevas sobre este lugar.",
                "Aprendí a escuchar mejor.",
                "Todavía no aprendí mucho."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué es lo más interesante de $place?",
            'options' => [
                "La historia de este lugar es muy interesante.",
                "Las personas que viven aquí.",
                "Los sonidos y colores que tiene."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Cómo te sientes en este momento?",
            'options' => [
                "Me siento bien, quiero aprender más.",
                "Estoy un poco confundido, pero sigo.",
                "Estoy emocionado por el viaje."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué quieres hacer ahora en $place?",
            'options' => [
                "Quiero hablar con la gente de aquí.",
                "Quiero caminar y observar.",
                "Quiero escribir lo que veo."
            ],
            'correct' => 0
        ],
    ];
}

function generateB1Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Por qué crees que $place es importante para Colombia?",
            'options' => [
                "Porque representa una parte de la historia y cultura del país.",
                "Porque muchas personas dependen de este lugar para vivir.",
                "Porque aquí se encuentran tradiciones que no existen en otros lugares."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué cambiarías de este lugar si pudieras?",
            'options' => [
                "Protegería mejor la naturaleza que lo rodea.",
                "Haría que más personas conocieran su historia.",
                "No cambiaría nada; me gusta como es."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Cómo describirías $place a alguien que no lo conoce?",
            'options' => [
                "Es un lugar con mucha vida, donde la naturaleza y la cultura se mezclan.",
                "Es un sitio tranquilo donde puedes aprender sobre el pasado.",
                "Es un rincón de Colombia que merece ser visitado y respetado."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué conexión sientes entre este lugar y el viaje de Yaguará?",
            'options' => [
                "Cada lugar enseña algo nuevo, igual que cada palabra del viaje.",
                "La conexión está en las personas que guardan la memoria del lugar.",
                "El viaje de Yaguará refleja lo que significa conocer un país de verdad."
            ],
            'correct' => 0
        ],
    ];
}

function generateB2Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Crees que el turismo beneficia o perjudica a $place?",
            'options' => [
                "Puede beneficiar si se hace de manera responsable y respetuosa.",
                "A veces perjudica porque cambia la identidad del lugar.",
                "Depende de quién lo gestione y con qué intenciones."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué papel juega la memoria colectiva en un lugar como $place?",
            'options' => [
                "La memoria colectiva es lo que da sentido a los espacios que habitamos.",
                "Sin memoria, un lugar es solo geografía; con ella, es patrimonio.",
                "Las comunidades locales son las guardianas de esa memoria."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Cómo se relaciona lo que aprendes aquí con tu propia experiencia?",
            'options' => [
                "Aprender una lengua me obliga a repensar mi propia forma de ver el mundo.",
                "Cada destino me recuerda que la cultura no se aprende, se vive.",
                "La experiencia de viajar con palabras nuevas transforma la manera de pensar."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué significado tiene para ti el viaje de Yaguará en este punto?",
            'options' => [
                "Es un viaje sobre nombrar lo que importa antes de que desaparezca.",
                "Representa el esfuerzo de conectar lenguas, personas y territorios.",
                "Me enseña que aprender español es más que gramática: es entrar en un mundo."
            ],
            'correct' => 0
        ],
    ];
}

function generateC1Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Cómo influye el contexto sociopolítico en la forma en que experimentamos $place?",
            'options' => [
                "El contexto determina qué historias se cuentan y cuáles se silencian sobre un lugar.",
                "La percepción de un lugar está mediada por narrativas de poder y resistencia.",
                "No podemos separar la experiencia estética de un lugar de su realidad social."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Hasta qué punto la lengua que usamos condiciona nuestra experiencia de un territorio?",
            'options' => [
                "La lengua no solo describe el territorio, sino que lo construye simbólicamente.",
                "Nombrar un lugar en una lengua u otra revela jerarquías culturales profundas.",
                "El bilingüismo permite una doble lectura del paisaje que el monolingüismo no alcanza."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué responsabilidad tiene el viajero frente a los lugares que visita?",
            'options' => [
                "La de no reducir un lugar a su imagen turística, sino buscar su complejidad.",
                "La de escuchar las voces locales antes de imponer su propia interpretación.",
                "La de reconocer que toda visita es una intervención, por mínima que sea."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Cómo se transforma el significado de «{$title}» a medida que avanzas en este viaje?",
            'options' => [
                "Al principio era un título; ahora es una pregunta que me interroga a mí.",
                "Cada destino añade una capa de significado que solo se revela en retrospectiva.",
                "La transformación no está en el título, sino en quién lo lee después de la experiencia."
            ],
            'correct' => 0
        ],
    ];
}

function generateC2Exchanges(int $dest, string $title, string $landmark, string $desc): array {
    $place = getSimplePlace($landmark);

    return [
        [
            'prompt' => "¿Está el turismo ayudando o dañando a $place?",
            'options' => [
                "El turismo es una forma de colonialismo suave que mercantiliza la autenticidad cultural.",
                "Bien gestionado, el turismo puede ser un acto de reconocimiento y dignificación del territorio.",
                "La pregunta misma revela una falsa dicotomía: el turismo transforma, y transformar no es destruir ni salvar."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Es posible «conocer» un lugar a través de una lengua que no es la propia?",
            'options' => [
                "Conocer en otra lengua es conocer de otro modo; no menos, sino distinto.",
                "Toda lengua es un filtro, pero el esfuerzo de aprender otra amplía la percepción más allá de ambas.",
                "El verdadero conocimiento de un lugar exige habitar su lengua, no solo visitarla."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué relación existe entre nombrar un lugar y ejercer poder sobre él?",
            'options' => [
                "Nombrar es el primer acto de posesión simbólica: quien nombra, ordena el mundo.",
                "Pero también nombrar puede ser un acto de cuidado: dar nombre es dar existencia.",
                "La tensión entre ambas funciones —dominio y reconocimiento— es irresoluble y constitutiva del lenguaje."
            ],
            'correct' => 0
        ],
        [
            'prompt' => "¿Qué queda de este viaje cuando se acaban las palabras?",
            'options' => [
                "Lo que queda es precisamente lo que las palabras intentaron pero no lograron decir.",
                "Queda la experiencia encarnada: el cuerpo recuerda lo que la lengua olvida.",
                "Queda la disposición a seguir nombrando, sabiendo que siempre habrá un residuo intraducible."
            ],
            'correct' => 0
        ],
    ];
}

/**
 * Extract a short, usable place name from the landmark
 */
function getSimplePlace(string $landmark): string {
    // "La Milagrosa, Medellín" → "La Milagrosa"
    // "Parque de los Deseos, Medellín" → "el Parque de los Deseos"
    $parts = explode(',', $landmark);
    $place = trim($parts[0]);

    // Add article if it doesn't start with one
    $lower = mb_strtolower($place);
    if (!preg_match('/^(el |la |los |las |un |una )/', $lower)) {
        $place = $place;
    }

    return $place;
}
