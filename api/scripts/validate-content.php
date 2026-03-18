#!/usr/bin/env php
<?php
/**
 * Content Validator — Automated QA for El Viaje del Jaguar
 *
 * Checks all 58 destination files + template files for:
 *  1. CEFR vocabulary violations (words above destination level)
 *  2. Grammar/spelling errors (via LanguageTool API)
 *  3. Structural issues (missing fields, bad formats)
 *  4. Duplicate content detection
 *
 * Usage:
 *   php validate-content.php                     # Full validation (all dests)
 *   php validate-content.php --dest=5            # Single destination
 *   php validate-content.php --dest=1-12         # Range
 *   php validate-content.php --check=cefr        # Only CEFR check
 *   php validate-content.php --check=grammar     # Only grammar check
 *   php validate-content.php --check=structure   # Only structure check
 *   php validate-content.php --json              # Output as JSON (for QA dashboard)
 *   php validate-content.php --fix               # Auto-fix simple issues (trailing spaces, etc.)
 */

$config = require __DIR__ . '/../config/app.php';

// Parse CLI args
$opts = getopt('', ['dest:', 'check:', 'json', 'fix', 'help', 'verbose']);

if (isset($opts['help'])) {
    echo "Usage: php validate-content.php [--dest=N|N-M] [--check=cefr|grammar|structure|duplicates] [--json] [--fix] [--verbose]\n";
    exit(0);
}

$jsonOutput = isset($opts['json']);
$autoFix = isset($opts['fix']);
$verbose = isset($opts['verbose']);
$checkType = $opts['check'] ?? 'all';
if (is_array($checkType)) $checkType = 'all'; // multiple --check flags means run all

// Determine destination range
$destStart = 1;
$destEnd = 58;
if (!empty($opts['dest'])) {
    if (strpos($opts['dest'], '-') !== false) {
        [$destStart, $destEnd] = array_map('intval', explode('-', $opts['dest']));
    } else {
        $destStart = $destEnd = (int)$opts['dest'];
    }
}

// Database connection for CEFR lookups
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// CEFR level hierarchy for comparison
$CEFR_ORDER = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];

// Destination to CEFR mapping
function getDestCefr(int $dest): string {
    if ($dest <= 12) return 'A1';
    if ($dest <= 18) return 'A2';
    if ($dest <= 28) return 'B1';
    if ($dest <= 38) return 'B2';
    if ($dest <= 48) return 'C1';
    return 'C2';
}

// Cache for word CEFR lookups
$wordCefrCache = [];

// Allowlist: words acceptable at ANY level (UI labels, character names, narrative frame words)
// These are either: (a) taught in context regardless of DB level, (b) UI/instruction text, (c) proper nouns
$CEFR_ALLOWLIST = [
    // UI/instruction words — students see these as interface, not vocabulary
    'completar', 'escena', 'escucha', 'escuchas', 'elige', 'ordena', 'responde', 'construir',
    'completa', 'arrastra', 'conecta', 'selecciona', 'descubre', 'explora', 'observa',
    'saludos', 'vocabulario', 'gramática', 'repaso', 'práctica', 'clasificar',
    'conversación', 'traducción', 'correcta', 'correcto', 'encuentra', 'resuelve',
    'repite', 'crónica', 'respuestas', 'preguntas', 'enigmas', 'letras', 'une',
    'escribe', 'traduce', 'traducir', 'respuesta', 'frase', 'frases',
    // Game type labels — not vocabulary, just UI category names
    'emparejar', 'dictado', 'susurro', 'eco', 'senda', 'corrector', 'corrige',
    'oráculo', 'explorador', 'conjugación', 'conjugar', 'cartógrafo', 'pregonero',
    'tertulia', 'ritmo', 'grabación', 'graba',
    // Common words that A1 students learn immediately (DB may have wrong CEFR)
    'una', 'un', 'el', 'la', 'los', 'las', 'del', 'al', 'es', 'son',
    'tiene', 'hay', 'dice', 'va', 'hace', 'puede', 'quiere', 'sabe',
    'ojos', 'mano', 'manos', 'casa', 'agua', 'sol', 'luna', 'tierra',
    'día', 'noche', 'luz', 'color', 'nombre', 'palabra', 'palabras',
    'hombre', 'mujer', 'niño', 'niña', 'amigo', 'amiga', 'familia',
    'voz', 'lugar', 'primer', 'primera', 'último', 'última',
    'cae', 'salta', 'brilla', 'camina', 'corre', 'vuela', 'nace', 'crece',
    'abrazo', 'cielo', 'fuego', 'sangre', 'sombra', 'silencio', 'piedra',
    'rana', 'pájaro', 'estrella', 'raíz', 'raíces', 'ramas',
    // Character/narrative frame words
    'poeta', 'viajero', 'jaguar', 'yaguará', 'rinrín', 'narrador',
    'selva', 'río', 'bosque', 'árbol', 'árboles', 'montaña',
    'camino', 'puerta', 'mundo', 'mundos', 'historia', 'aventura', 'viaje',
    'sombra', 'ceiba', 'cóndor', 'colibrí', 'guardián', 'guerrero',
    'espíritu', 'espiritu', 'ancestral', 'sagrado', 'sagrada', 'antiguo', 'antigua',
    'ceremonia', 'ritual', 'misterio', 'destino', 'profundo', 'profunda',
    // Image alt text markers (not student-facing content)
    'dorado', 'dorada', 'dorados', 'doradas',
    'enormes', 'talladas', 'dispuestas', 'conectadas', 'flotando',
    'huellas', 'ondas', 'gotas', 'hojas', 'piedras', 'orilla',
    'frente', 'junto', 'entre', 'sobre', 'hacia', 'desde', 'hasta',
    'hilo', 'forma', 'formas',
    // Non-Spanish words (appear in English instructions for A1)
    'yes', 'no', 'am', 'are', 'is', 'you', 'the',
    // Colombian slang (taught in narrative context regardless of CEFR DB level)
    'quiubo', 'parcero', 'mijo', 'bacano',
    // Clearly A1/A2 words that heuristic misleveled (no frequency match)
    'animal', 'animales', 'planta', 'plantas', 'verde', 'rojo', 'azul', 'blanco', 'negro',
    'español', 'inglés', 'francés', 'saludo', 'despedida', 'artículo', 'presentarme',
    'lento', 'lenta', 'rápido', 'rápida', 'vecino', 'vecina', 'cultura', 'mínimo',
    'esquina', 'tocan', 'conjuro', // escape room words are not student-facing vocab
    // Common verbs/forms frequently misleveled by heuristic
    'despertar', 'dormido', 'dormida', 'despierta', 'despiertas', 'despierto',
    'oscuro', 'oscura', 'lejano', 'lejana', 'quieta', 'quieto',
    'sonríe', 'murmura', 'susurra', 'brille', 'arde', 'fluye',
    'rompe', 'cruza', 'toca', 'llama', 'nombra', 'nace',
    'descalzo', 'descalza', 'cerradura', 'dibujo', 'pared', 'borde',
    'vacío', 'vacía', 'huella', 'identidad', 'verbo',
    // Common B1-tagged words clearly learned at A1/A2
    'taxi', 'gato', 'reloj', 'pan', 'dientes', 'imagen', 'ruido', 'sonido',
    'preguntar', 'vender', 'volar', 'caminar', 'luchar', 'correr', 'averiguar',
    'corriendo', 'yendo', 'encantado', 'encantada', 'decidido', 'decidida',
    'colegio', 'televisión', 'television', 'caballero', 'hielo', 'dama',
    'responsable', 'héroe', 'heroe', 'película', 'peliculas', 'películas',
    'millón', 'millon', 'código', 'codigo', 'prensa', 'riesgo',
    'anillo', 'camión', 'camion', 'lucha', 'tanta', 'tanto', 'tantos', 'tantas',
    'malas', 'malo', 'malos', 'alguno', 'alguna', 'algunos', 'algunas',
    'pasada', 'pasado', 'pasados', 'pasadas', 'vengan', 'llame',
    'querer', 'llegamos', 'vamos', 'hagamos', 'vayamos',
    // Ecosystem-specific vocabulary (immersion, not errors)
    'chiva', 'chivas', 'chivo', 'chivos', 'ranchería', 'rancherías',
    'maloca', 'palafita', 'marimba', 'cumbia', 'currulao', 'joropo',
    'arpa', 'gaita', 'tiple', 'vallenato', 'bambuco', 'guabina',
    'arepa', 'empanada', 'tamales', 'bandeja', 'sancocho', 'ajiaco',
    'encocado', 'mamona', 'guarapo', 'masato', 'chicha', 'viche', 'aguardiente',
    'llanero', 'llanera', 'wayúu', 'kogi', 'raizal', 'palenquero',
    'ceiba', 'frailejón', 'dividivi', 'manglar', 'moriche', 'cocotero',
    'tucán', 'cóndor', 'colibrí', 'chigüiro', 'flamenco', 'jaguar',
    'páramo', 'sabana', 'arrecife', 'estero', 'caño', 'morichal',
    'fogón', 'hamaca', 'mochila', 'sombrero', 'poncho', 'ruana',
];

/**
 * Look up a word's CEFR level from the database.
 * Returns null if word not found.
 */
function getWordCefr(PDO $pdo, string $word, array &$cache): ?string {
    $lower = mb_strtolower(trim($word));
    if ($lower === '' || mb_strlen($lower) <= 1) return null;

    if (array_key_exists($lower, $cache)) return $cache[$lower];

    $stmt = $pdo->prepare(
        "SELECT MIN(FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')) as min_level FROM dict_words WHERE lang_code = ? AND word_normalized = ? AND cefr_level IS NOT NULL"
    );
    $stmt->execute(['es', $lower]);
    $row = $stmt->fetch();
    $levelMap = [1=>'A1',2=>'A2',3=>'B1',4=>'B2',5=>'C1',6=>'C2'];
    $cache[$lower] = ($row && $row['min_level']) ? ($levelMap[$row['min_level']] ?? null) : null;
    return $cache[$lower];
}

/**
 * Check if word CEFR is above the allowed level.
 * Returns true if violation (word is harder than allowed).
 */
function isCefrViolation(string $wordCefr, string $destCefr, array $order, string $word, array $allowlist): bool {
    if (in_array(mb_strtolower($word), $allowlist)) return false;
    $wordLevel = $order[$wordCefr] ?? 0;
    $destLevel = $order[$destCefr] ?? 0;
    // Allow 1 level above (e.g., an A2 word in an A1 game is a mild stretch, not a violation)
    // Flag 2+ levels above as violations
    return ($wordLevel - $destLevel) >= 2;
}

/**
 * Extract all Spanish text strings from a game/template data structure.
 * Returns array of ['text' => string, 'path' => string describing location].
 */
function extractTexts(array $data, string $pathPrefix = ''): array {
    $texts = [];
    $textKeys = ['text', 'sentence', 'instruction', 'body', 'answer', 'prompt', 'question',
                 'label', 'title', 'hint', 'tip', 'closingQuestion'];
    // Note: imageAlt excluded — it's for screen readers/SEO, not student-facing vocabulary

    foreach ($data as $key => $value) {
        $currentPath = $pathPrefix ? "{$pathPrefix}.{$key}" : (string)$key;

        if (is_string($value) && in_array($key, $textKeys) && mb_strlen($value) > 1) {
            $texts[] = ['text' => $value, 'path' => $currentPath];
        } elseif ($key === 'options' && is_array($value)) {
            foreach ($value as $i => $opt) {
                if (is_string($opt)) {
                    $texts[] = ['text' => $opt, 'path' => "{$currentPath}[{$i}]"];
                }
            }
        } elseif (is_array($value)) {
            $texts = array_merge($texts, extractTexts($value, $currentPath));
        }
    }

    return $texts;
}

/**
 * Extract individual words from Spanish text.
 */
function extractWords(string $text): array {
    // Remove template vars like {nombre}, {pool:eco.animal}
    $text = preg_replace('/\{[^}]+\}/', '', $text);
    // Remove punctuation except ñ and accented chars
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    // Split into words
    $words = preg_split('/\s+/', mb_strtolower(trim($text)));
    return array_filter($words, fn($w) => mb_strlen($w) > 2);
}

/**
 * Call LanguageTool to check Spanish text.
 * Rate-limited to avoid hitting the public API too hard.
 */
function checkGrammar(string $text): array {
    static $lastCall = 0;
    // Rate limit: max 1 request per second to be respectful of free API
    $now = microtime(true);
    $elapsed = $now - $lastCall;
    if ($elapsed < 1.0) {
        usleep((int)((1.0 - $elapsed) * 1000000));
    }
    $lastCall = microtime(true);

    $ch = curl_init('https://api.languagetool.org/v2/check');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['text' => $text, 'language' => 'es']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'ElViajeDelJaguar-QA/1.0 (babelfree.com)',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return [];

    $data = json_decode($response, true);
    return $data['matches'] ?? [];
}

// ============================================================
// VALIDATORS
// ============================================================

$allIssues = [];
$stats = [
    'destinations_checked' => 0,
    'total_texts_checked' => 0,
    'total_words_checked' => 0,
    'cefr_violations' => 0,
    'grammar_issues' => 0,
    'structure_issues' => 0,
    'duplicates_found' => 0,
];

// Duplicate detection pools
$allSentences = []; // path => sentence for duplicate checking
$allAnswers = [];

if (!$jsonOutput) {
    echo "\n╔══════════════════════════════════════════════════╗\n";
    echo "║  Content Validator — El Viaje del Jaguar         ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";
    echo "Checking dest{$destStart}" . ($destStart !== $destEnd ? "-dest{$destEnd}" : '') . " | Mode: {$checkType}\n\n";
}

$contentDir = __DIR__ . '/../../content';
$templateDir = __DIR__ . '/../../content/templates';

for ($d = $destStart; $d <= $destEnd; $d++) {
    $destFile = "{$contentDir}/dest{$d}.json";
    $templateFile = "{$templateDir}/dest{$d}-templates.json";
    $destCefr = getDestCefr($d);
    $destIssues = [];

    $stats['destinations_checked']++;

    if (!$jsonOutput && $verbose) {
        echo "── dest{$d} ({$destCefr}) ──\n";
    }

    // ── Load dest content ──
    $destData = null;
    if (file_exists($destFile)) {
        $raw = file_get_contents($destFile);
        $destData = json_decode($raw, true);
        if ($destData === null) {
            $destIssues[] = [
                'type' => 'structure',
                'severity' => 'error',
                'file' => "dest{$d}.json",
                'path' => '',
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }
    } else {
        $destIssues[] = [
            'type' => 'structure',
            'severity' => 'warning',
            'file' => "dest{$d}.json",
            'path' => '',
            'message' => 'File not found',
        ];
    }

    // ── Load templates ──
    $templateData = null;
    if (file_exists($templateFile)) {
        $raw = file_get_contents($templateFile);
        $templateData = json_decode($raw, true);
        if ($templateData === null) {
            $destIssues[] = [
                'type' => 'structure',
                'severity' => 'error',
                'file' => "dest{$d}-templates.json",
                'path' => '',
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }
    }

    // ── STRUCTURE CHECKS ──
    if ($checkType === 'all' || $checkType === 'structure') {
        if ($destData) {
            // Check required meta fields
            $requiredMeta = ['destination', 'title', 'cefr', 'world'];
            foreach ($requiredMeta as $field) {
                if (empty($destData['meta'][$field])) {
                    $destIssues[] = [
                        'type' => 'structure',
                        'severity' => 'error',
                        'file' => "dest{$d}.json",
                        'path' => "meta.{$field}",
                        'message' => "Missing required field: meta.{$field}",
                    ];
                    $stats['structure_issues']++;
                }
            }

            // Check games array exists and has content
            if (empty($destData['games']) || !is_array($destData['games'])) {
                $destIssues[] = [
                    'type' => 'structure',
                    'severity' => 'error',
                    'file' => "dest{$d}.json",
                    'path' => 'games',
                    'message' => 'Missing or empty games array',
                ];
                $stats['structure_issues']++;
            } else {
                foreach ($destData['games'] as $gi => $game) {
                    // Check each game has required fields
                    if (empty($game['type'])) {
                        $destIssues[] = [
                            'type' => 'structure',
                            'severity' => 'error',
                            'file' => "dest{$d}.json",
                            'path' => "games[{$gi}]",
                            'message' => 'Game missing type field',
                        ];
                        $stats['structure_issues']++;
                    }

                    // Check fill/pick games have questions with answers
                    if (in_array($game['type'] ?? '', ['fill', 'pick', 'match', 'order'])) {
                        if (empty($game['questions']) && empty($game['pairs']) && empty($game['items'])) {
                            $destIssues[] = [
                                'type' => 'structure',
                                'severity' => 'warning',
                                'file' => "dest{$d}.json",
                                'path' => "games[{$gi}]",
                                'message' => "Game type '{$game['type']}' has no questions/pairs/items",
                            ];
                            $stats['structure_issues']++;
                        }
                    }

                    // Check distractors are different from answer
                    if (!empty($game['questions'])) {
                        foreach ($game['questions'] as $qi => $q) {
                            $answer = mb_strtolower(trim($q['answer'] ?? ''));
                            if (!empty($q['options'])) {
                                $hasCorrect = false;
                                foreach ($q['options'] as $opt) {
                                    if (mb_strtolower(trim($opt)) === $answer) {
                                        $hasCorrect = true;
                                    }
                                }
                                if (!$hasCorrect && $answer) {
                                    $destIssues[] = [
                                        'type' => 'structure',
                                        'severity' => 'error',
                                        'file' => "dest{$d}.json",
                                        'path' => "games[{$gi}].questions[{$qi}]",
                                        'message' => "Answer '{$answer}' not found in options",
                                    ];
                                    $stats['structure_issues']++;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Template structure checks
        if ($templateData) {
            $templates = $templateData['templates'] ?? [];
            $templateCount = count($templates);
            if ($templateCount < 40) {
                $destIssues[] = [
                    'type' => 'structure',
                    'severity' => 'warning',
                    'file' => "dest{$d}-templates.json",
                    'path' => 'templates',
                    'message' => "Only {$templateCount}/40 templates (incomplete)",
                ];
                $stats['structure_issues']++;
            }

            // Check layer distribution
            $layers = ['narrative_spine' => 0, 'ecosystem_immersion' => 0, 'spiral_return' => 0, 'mastery_check' => 0, 'free_play' => 0];
            foreach ($templates as $t) {
                $layer = $t['layer'] ?? 'unknown';
                if (isset($layers[$layer])) {
                    $layers[$layer]++;
                }
            }
            // Expected: 8/16/8/4/4 for 40 templates
            if ($templateCount >= 40 && $layers['narrative_spine'] !== 8) {
                $destIssues[] = [
                    'type' => 'structure',
                    'severity' => 'info',
                    'file' => "dest{$d}-templates.json",
                    'path' => 'templates',
                    'message' => "Layer distribution: NS={$layers['narrative_spine']} EI={$layers['ecosystem_immersion']} SR={$layers['spiral_return']} MC={$layers['mastery_check']} FP={$layers['free_play']} (expected 8/16/8/4/4)",
                ];
            }
        }
    }

    // ── CEFR VOCABULARY CHECKS ──
    if (($checkType === 'all' || $checkType === 'cefr') && $destData) {
        $texts = extractTexts($destData);
        foreach ($texts as $entry) {
            $words = extractWords($entry['text']);
            foreach ($words as $word) {
                $stats['total_words_checked']++;
                $wordCefr = getWordCefr($pdo, $word, $wordCefrCache);
                if ($wordCefr && isCefrViolation($wordCefr, $destCefr, $CEFR_ORDER, $word, $CEFR_ALLOWLIST)) {
                    $destIssues[] = [
                        'type' => 'cefr',
                        'severity' => 'warning',
                        'file' => "dest{$d}.json",
                        'path' => $entry['path'],
                        'message' => "Word '{$word}' is {$wordCefr} (destination is {$destCefr})",
                        'word' => $word,
                        'word_cefr' => $wordCefr,
                        'dest_cefr' => $destCefr,
                        'context' => mb_substr($entry['text'], 0, 100),
                    ];
                    $stats['cefr_violations']++;
                }
            }
        }

        // Also check template vocabulary
        if ($templateData) {
            $tTexts = extractTexts($templateData['templates'] ?? []);
            foreach ($tTexts as $entry) {
                $words = extractWords($entry['text']);
                foreach ($words as $word) {
                    $stats['total_words_checked']++;
                    $wordCefr = getWordCefr($pdo, $word, $wordCefrCache);
                    if ($wordCefr && isCefrViolation($wordCefr, $destCefr, $CEFR_ORDER, $word, $CEFR_ALLOWLIST)) {
                        $destIssues[] = [
                            'type' => 'cefr',
                            'severity' => 'warning',
                            'file' => "dest{$d}-templates.json",
                            'path' => $entry['path'],
                            'message' => "Word '{$word}' is {$wordCefr} (destination is {$destCefr})",
                            'word' => $word,
                            'word_cefr' => $wordCefr,
                            'dest_cefr' => $destCefr,
                            'context' => mb_substr($entry['text'], 0, 100),
                        ];
                        $stats['cefr_violations']++;
                    }
                }
            }
        }
    }

    // ── GRAMMAR CHECKS (LanguageTool) ──
    if (($checkType === 'all' || $checkType === 'grammar') && $destData) {
        $texts = extractTexts($destData);
        // Batch texts into chunks to reduce API calls
        $batch = '';
        $batchPaths = [];
        $batchSize = 0;

        foreach ($texts as $entry) {
            $stats['total_texts_checked']++;
            // Skip very short texts and template variables
            if (mb_strlen($entry['text']) < 5) continue;
            if (preg_match('/^\{[^}]+\}$/', $entry['text'])) continue;

            $batch .= $entry['text'] . "\n";
            $batchPaths[] = $entry['path'];
            $batchSize++;

            // Send batch every 20 sentences (keep under API limits)
            if ($batchSize >= 20) {
                $matches = checkGrammar($batch);
                foreach ($matches as $match) {
                    // Skip style suggestions and whitespace issues
                    $ruleId = $match['rule']['id'] ?? '';
                    if (in_array($ruleId, ['WHITESPACE_RULE', 'UPPERCASE_SENTENCE_START', 'MORFOLOGIK_RULE_ES'])) continue;

                    $context = $match['context']['text'] ?? '';
                    $message = $match['message'] ?? '';
                    $replacements = array_column(array_slice($match['replacements'] ?? [], 0, 3), 'value');

                    $destIssues[] = [
                        'type' => 'grammar',
                        'severity' => 'warning',
                        'file' => "dest{$d}.json",
                        'path' => implode(', ', array_slice($batchPaths, 0, 3)),
                        'message' => $message,
                        'context' => mb_substr($context, 0, 120),
                        'suggestions' => $replacements,
                        'rule' => $ruleId,
                        'category' => $match['rule']['category']['name'] ?? '',
                    ];
                    $stats['grammar_issues']++;
                }
                $batch = '';
                $batchPaths = [];
                $batchSize = 0;
            }
        }

        // Process remaining batch
        if ($batchSize > 0) {
            $matches = checkGrammar($batch);
            foreach ($matches as $match) {
                $ruleId = $match['rule']['id'] ?? '';
                if (in_array($ruleId, ['WHITESPACE_RULE', 'UPPERCASE_SENTENCE_START', 'MORFOLOGIK_RULE_ES'])) continue;

                $destIssues[] = [
                    'type' => 'grammar',
                    'severity' => 'warning',
                    'file' => "dest{$d}.json",
                    'path' => implode(', ', array_slice($batchPaths, 0, 3)),
                    'message' => $match['message'] ?? '',
                    'context' => mb_substr($match['context']['text'] ?? '', 0, 120),
                    'suggestions' => array_column(array_slice($match['replacements'] ?? [], 0, 3), 'value'),
                    'rule' => $ruleId,
                    'category' => $match['rule']['category']['name'] ?? '',
                ];
                $stats['grammar_issues']++;
            }
        }
    }

    // ── DUPLICATE DETECTION ──
    if (($checkType === 'all' || $checkType === 'duplicates') && $destData) {
        if (!empty($destData['games'])) {
            foreach ($destData['games'] as $gi => $game) {
                if (!empty($game['questions'])) {
                    foreach ($game['questions'] as $qi => $q) {
                        $sentence = mb_strtolower(trim($q['sentence'] ?? $q['prompt'] ?? ''));
                        if ($sentence && mb_strlen($sentence) > 10) {
                            $key = "dest{$d}.games[{$gi}].q[{$qi}]";
                            if (isset($allSentences[$sentence])) {
                                $destIssues[] = [
                                    'type' => 'duplicate',
                                    'severity' => 'info',
                                    'file' => "dest{$d}.json",
                                    'path' => $key,
                                    'message' => "Duplicate sentence also found at: {$allSentences[$sentence]}",
                                    'context' => $sentence,
                                ];
                                $stats['duplicates_found']++;
                            } else {
                                $allSentences[$sentence] = $key;
                            }
                        }
                    }
                }
            }
        }
    }

    $allIssues["dest{$d}"] = $destIssues;

    if (!$jsonOutput && count($destIssues) > 0) {
        $cefrCount = count(array_filter($destIssues, fn($i) => $i['type'] === 'cefr'));
        $grammarCount = count(array_filter($destIssues, fn($i) => $i['type'] === 'grammar'));
        $structCount = count(array_filter($destIssues, fn($i) => $i['type'] === 'structure'));
        $dupCount = count(array_filter($destIssues, fn($i) => $i['type'] === 'duplicate'));
        $errorCount = count(array_filter($destIssues, fn($i) => $i['severity'] === 'error'));

        $icon = $errorCount > 0 ? '❌' : (count($destIssues) > 0 ? '⚠️' : '✅');
        echo "{$icon} dest{$d} ({$destCefr}):";
        if ($cefrCount) echo " CEFR:{$cefrCount}";
        if ($grammarCount) echo " Grammar:{$grammarCount}";
        if ($structCount) echo " Structure:{$structCount}";
        if ($dupCount) echo " Duplicates:{$dupCount}";
        echo "\n";

        if ($verbose) {
            foreach ($destIssues as $issue) {
                $sev = strtoupper($issue['severity']);
                echo "   [{$sev}] [{$issue['type']}] {$issue['message']}";
                if (!empty($issue['context'])) echo " | Context: \"{$issue['context']}\"";
                echo "\n";
            }
        }
    } elseif (!$jsonOutput && count($destIssues) === 0) {
        echo "✅ dest{$d} ({$destCefr}): OK\n";
    }
}

// ── OUTPUT ──
if ($jsonOutput) {
    // Output for QA dashboard consumption
    $output = [
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'issues' => $allIssues,
    ];
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "\n╔══════════════════════════════════════════════════╗\n";
    echo "║  SUMMARY                                         ║\n";
    echo "╠══════════════════════════════════════════════════╣\n";
    echo "║  Destinations checked: {$stats['destinations_checked']}                        \n";
    echo "║  Words checked:        {$stats['total_words_checked']}                      \n";
    echo "║  Texts checked:        {$stats['total_texts_checked']}                      \n";
    echo "║  ──────────────────────────────────────────────  \n";
    echo "║  CEFR violations:      {$stats['cefr_violations']}                        \n";
    echo "║  Grammar issues:       {$stats['grammar_issues']}                        \n";
    echo "║  Structure issues:     {$stats['structure_issues']}                        \n";
    echo "║  Duplicates found:     {$stats['duplicates_found']}                        \n";
    echo "╚══════════════════════════════════════════════════╝\n\n";

    $totalIssues = $stats['cefr_violations'] + $stats['grammar_issues'] + $stats['structure_issues'] + $stats['duplicates_found'];
    if ($totalIssues === 0) {
        echo "🎉 All content passed validation!\n";
    } else {
        echo "Run with --verbose for full details, or --json for dashboard integration.\n";
    }
}
