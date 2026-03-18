<?php
/**
 * Validation API — Server-side content validation for QA Dashboard
 *
 * GET /api/validation/dest/{n}           — Validate single destination (CEFR + structure)
 * GET /api/validation/bulk?check=cefr    — Validate all 58 destinations
 * GET /api/validation/stats              — Quick DB stats (word counts, CEFR coverage)
 *
 * All endpoints require admin password via X-QA-Auth header.
 */

function handleValidationRoutes(string $action, string $method): void {
    // Admin auth — Bearer token + admin role
    $user = authenticateRequest();
    if (($user['role'] ?? '') !== 'admin') {
        jsonError('Unauthorized — admin role required', 403);
    }

    if ($method !== 'GET') {
        jsonError('Method not allowed', 405);
    }

    $parts = explode('/', $action);
    $mainAction = $parts[0] ?? '';

    switch ($mainAction) {
        case 'dest':
            $destNum = (int)($parts[1] ?? 0);
            if ($destNum < 1 || $destNum > 58) {
                jsonError('Invalid destination (1-58)', 400);
            }
            runValidation($destNum, $destNum);
            break;

        case 'bulk':
            runValidation(1, 58);
            break;

        case 'stats':
            getDbStats();
            break;

        default:
            jsonError('Unknown validation action', 404);
    }
}

function runValidation(int $start, int $end): void {
    $check = $_GET['check'] ?? 'cefr,structure';

    $pdo = getDB();
    $CEFR_ORDER = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
    $contentDir = dirname(__DIR__, 2) . '/content';
    $templateDir = $contentDir . '/templates';
    $wordCache = [];
    $checks = explode(',', $check);

    $ALLOWLIST = [
        // UI/instruction words
        'completar','escena','escucha','escuchas','elige','ordena','responde','construir',
        'completa','arrastra','conecta','selecciona','descubre','explora','observa',
        'saludos','vocabulario','gramática','repaso','práctica','clasificar',
        'conversación','traducción','correcta','correcto','encuentra','resuelve',
        'repite','crónica','respuestas','preguntas','enigmas','letras','une',
        'escribe','traduce','traducir','respuesta','frase','frases',
        // Game type labels
        'emparejar','dictado','susurro','eco','senda','corrector','corrige',
        'oráculo','explorador','conjugación','conjugar','cartógrafo','pregonero',
        'tertulia','ritmo','grabación','graba',
        // Common words
        'una','un','el','la','los','las','del','al','es','son',
        'tiene','hay','dice','va','hace','puede','quiere','sabe',
        'ojos','mano','manos','casa','agua','sol','luna','tierra',
        'día','noche','luz','color','nombre','palabra','palabras',
        'hombre','mujer','niño','niña','amigo','amiga','familia',
        'voz','lugar','primer','primera','último','última',
        'cae','salta','brilla','camina','corre','vuela','nace','crece',
        'abrazo','cielo','fuego','sangre','sombra','silencio','piedra',
        'rana','pájaro','estrella','raíz','raíces','ramas',
        // Character/narrative frame words
        'poeta','viajero','jaguar','yaguará','rinrín','narrador',
        'selva','río','bosque','árbol','árboles','montaña',
        'camino','puerta','mundo','mundos','historia','aventura','viaje',
        'sombra','ceiba','cóndor','colibrí','guardián','guerrero',
        'espíritu','espiritu','ancestral','sagrado','sagrada','antiguo','antigua',
        'ceremonia','ritual','misterio','destino','profundo','profunda',
        // Image alt text
        'dorado','dorada','dorados','doradas',
        'enormes','talladas','dispuestas','conectadas','flotando',
        'huellas','ondas','gotas','hojas','piedras','orilla',
        'frente','junto','entre','sobre','hacia','desde','hasta',
        'hilo','forma','formas',
        // Non-Spanish + Colombian slang
        'yes','no','am','are','is','you','the',
        'quiubo','parcero','mijo','bacano',
        // Common verbs/forms misleveled by heuristic
        'despertar','dormido','dormida','despierta','despiertas','despierto',
        'oscuro','oscura','lejano','lejana','quieta','quieto',
        'sonríe','murmura','susurra','brille','arde','fluye',
        'rompe','cruza','toca','llama','nombra','nace',
        'descalzo','descalza','cerradura','dibujo','pared','borde',
        'vacío','vacía','huella','identidad','verbo',
        // Common B1-tagged words clearly learned at A1/A2
        'taxi','gato','reloj','pan','dientes','imagen','ruido','sonido',
        'preguntar','vender','volar','caminar','luchar','correr','averiguar',
        'corriendo','yendo','encantado','encantada','decidido','decidida',
        'colegio','televisión','television','caballero','hielo','dama',
        'responsable','héroe','heroe','película','peliculas','películas',
        'millón','millon','código','codigo','prensa','riesgo',
        'anillo','camión','camion','lucha','tanta','tanto','tantos','tantas',
        'malas','malo','malos','alguno','alguna','algunos','algunas',
        'pasada','pasado','pasados','pasadas','vengan','llame',
        'querer','llegamos','vamos','hagamos','vayamos',
        // Ecosystem-specific vocabulary (immersion, not errors)
        'chiva','chivas','chivo','chivos','ranchería','rancherías',
        'maloca','palafita','marimba','cumbia','currulao','joropo',
        'arpa','gaita','tiple','vallenato','bambuco','guabina',
        'arepa','empanada','tamales','bandeja','sancocho','ajiaco',
        'encocado','mamona','guarapo','masato','chicha','viche','aguardiente',
        'llanero','llanera','wayúu','kogi','raizal','palenquero',
        'ceiba','frailejón','dividivi','manglar','moriche','cocotero',
        'tucán','cóndor','colibrí','chigüiro','flamenco','jaguar',
        'páramo','sabana','arrecife','estero','caño','morichal',
        'fogón','hamaca','mochila','sombrero','poncho','ruana',
    ];

    // Preload word cache for speed — use MIN(cefr_level) to get the most favorable level per word
    $stmt = $pdo->query("SELECT word_normalized, MIN(FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')) as min_level FROM dict_words WHERE lang_code='es' AND cefr_level IS NOT NULL AND frequency_rank <= 10000 GROUP BY word_normalized");
    $levelMap = [1=>'A1',2=>'A2',3=>'B1',4=>'B2',5=>'C1',6=>'C2'];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $wordCache[$row['word_normalized']] = $levelMap[$row['min_level']] ?? 'C2';
    }

    $results = [];
    $stats = ['destinations' => 0, 'total_cefr_violations' => 0, 'total_structure_issues' => 0, 'words_checked' => 0];

    for ($d = $start; $d <= $end; $d++) {
        $destCefr = getDestCefrApi($d);
        $destFile = "{$contentDir}/dest{$d}.json";
        $tmplFile = "{$templateDir}/dest{$d}-templates.json";
        $issues = [];
        $stats['destinations']++;

        // Load files
        $destData = null;
        $tmplData = null;
        if (file_exists($destFile)) {
            $destData = json_decode(file_get_contents($destFile), true);
            if ($destData === null) {
                $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => 'Invalid JSON: ' . json_last_error_msg()];
            }
        } else {
            $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => 'dest file not found'];
        }
        if (file_exists($tmplFile)) {
            $tmplData = json_decode(file_get_contents($tmplFile), true);
        }

        // Structure checks
        if (in_array('structure', $checks) && $destData) {
            $issues = array_merge($issues, checkStructure($destData, $tmplData, $d));
        }

        // CEFR checks
        if (in_array('cefr', $checks) && $destData) {
            $cefrIssues = checkCefr($pdo, $destData, $destCefr, $CEFR_ORDER, $ALLOWLIST, $wordCache, $stats);
            $issues = array_merge($issues, $cefrIssues);
            // Also check templates
            if ($tmplData && !empty($tmplData['templates'])) {
                $tmplCefrIssues = checkCefr($pdo, ['games' => $tmplData['templates']], $destCefr, $CEFR_ORDER, $ALLOWLIST, $wordCache, $stats);
                foreach ($tmplCefrIssues as &$i) $i['file'] = 'templates';
                $issues = array_merge($issues, $tmplCefrIssues);
            }
        }

        $cefrCount = count(array_filter($issues, fn($i) => $i['type'] === 'cefr'));
        $structCount = count(array_filter($issues, fn($i) => $i['type'] === 'structure'));
        $errorCount = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'error'));
        $stats['total_cefr_violations'] += $cefrCount;
        $stats['total_structure_issues'] += $structCount;

        $results["dest{$d}"] = [
            'cefr' => $destCefr,
            'title' => $destData['meta']['title'] ?? "dest{$d}",
            'games' => count($destData['games'] ?? []),
            'templates' => count($tmplData['templates'] ?? []),
            'cefr_violations' => $cefrCount,
            'structure_issues' => $structCount,
            'errors' => $errorCount,
            'issues' => $issues,
        ];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function checkStructure(array $data, ?array $tmplData, int $dest): array {
    $issues = [];

    // Required meta
    foreach (['destination', 'title', 'cefr', 'world'] as $f) {
        if (empty($data['meta'][$f])) {
            $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => "Missing meta.{$f}"];
        }
    }

    // Games
    $games = $data['games'] ?? [];
    if (empty($games)) {
        $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => 'No games'];
    } else {
        foreach ($games as $gi => $g) {
            if (empty($g['type'])) {
                $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => "games[{$gi}] missing type"];
            }
            // fill/pick without questions
            if (in_array($g['type'] ?? '', ['fill', 'pick', 'match', 'order'])) {
                if (empty($g['questions']) && empty($g['pairs']) && empty($g['items'])) {
                    $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => "games[{$gi}] ({$g['type']}) has no questions"];
                }
            }
            // Answer not in options
            if (!empty($g['questions'])) {
                foreach ($g['questions'] as $qi => $q) {
                    $ans = mb_strtolower(trim($q['answer'] ?? ''));
                    if ($ans && !empty($q['options'])) {
                        $found = false;
                        foreach ($q['options'] as $o) {
                            if (mb_strtolower(trim($o)) === $ans) { $found = true; break; }
                        }
                        if (!$found) {
                            $issues[] = ['type' => 'structure', 'severity' => 'error', 'message' => "games[{$gi}].q[{$qi}]: answer '{$ans}' not in options"];
                        }
                    }
                }
            }
        }
        // Escape room check
        $hasEscape = false;
        foreach ($games as $g) { if (($g['type'] ?? '') === 'escaperoom') $hasEscape = true; }
        if (!$hasEscape) {
            $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => 'No escape room'];
        }
    }

    // Arrival/departure
    if (empty($data['arrival'])) $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => 'No arrival'];
    if (empty($data['departure'])) $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => 'No departure'];

    // Templates
    if ($tmplData) {
        $tc = count($tmplData['templates'] ?? []);
        if ($tc < 40) {
            $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => "Only {$tc}/40 templates"];
        }
    } else {
        $issues[] = ['type' => 'structure', 'severity' => 'warning', 'message' => 'No template file'];
    }

    return $issues;
}

function checkCefr(PDO $pdo, array $data, string $destCefr, array $order, array $allowlist, array &$cache, array &$stats): array {
    $issues = [];
    $texts = extractTextsApi($data);
    $seen = []; // Track unique words to avoid duplicate reports

    foreach ($texts as $entry) {
        $words = extractWordsApi($entry['text']);
        foreach ($words as $word) {
            $stats['words_checked']++;
            $lower = mb_strtolower($word);
            if (isset($seen[$lower])) continue; // Report each word only once per destination
            if (in_array($lower, $allowlist)) continue;

            // Lookup — use MIN to get most favorable CEFR level across duplicate entries
            if (!array_key_exists($lower, $cache)) {
                $stmt = $pdo->prepare("SELECT MIN(FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')) as min_level FROM dict_words WHERE lang_code=? AND word_normalized=? AND cefr_level IS NOT NULL");
                $stmt->execute(['es', $lower]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $lMap = [1=>'A1',2=>'A2',3=>'B1',4=>'B2',5=>'C1',6=>'C2'];
                $cache[$lower] = ($row && $row['min_level']) ? ($lMap[$row['min_level']] ?? null) : null;
            }

            $wordCefr = $cache[$lower];
            if ($wordCefr) {
                $gap = ($order[$wordCefr] ?? 0) - ($order[$destCefr] ?? 0);
                if ($gap >= 2) {
                    $issues[] = [
                        'type' => 'cefr',
                        'severity' => $gap >= 3 ? 'error' : 'warning',
                        'message' => "'{$lower}' is {$wordCefr} (dest is {$destCefr})",
                        'word' => $lower,
                        'word_cefr' => $wordCefr,
                        'dest_cefr' => $destCefr,
                        'context' => mb_substr($entry['text'], 0, 80),
                    ];
                    $seen[$lower] = true;
                }
            }
        }
    }

    return $issues;
}

function extractTextsApi(array $data, string $prefix = ''): array {
    $texts = [];
    $keys = ['text','sentence','instruction','body','answer','prompt','question','label','title','hint','tip','closingQuestion'];
    foreach ($data as $k => $v) {
        $path = $prefix ? "{$prefix}.{$k}" : (string)$k;
        if (is_string($v) && in_array($k, $keys) && mb_strlen($v) > 1) {
            $texts[] = ['text' => $v, 'path' => $path];
        } elseif ($k === 'options' && is_array($v)) {
            foreach ($v as $i => $o) {
                if (is_string($o)) $texts[] = ['text' => $o, 'path' => "{$path}[{$i}]"];
            }
        } elseif (is_array($v)) {
            $texts = array_merge($texts, extractTextsApi($v, $path));
        }
    }
    return $texts;
}

function extractWordsApi(string $text): array {
    $text = preg_replace('/\{[^}]+\}/', '', $text);
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $words = preg_split('/\s+/', mb_strtolower(trim($text)));
    return array_filter($words, fn($w) => mb_strlen($w) > 2);
}

function getDestCefrApi(int $d): string {
    if ($d <= 12) return 'A1';
    if ($d <= 18) return 'A2';
    if ($d <= 28) return 'B1';
    if ($d <= 38) return 'B2';
    if ($d <= 48) return 'C1';
    return 'C2';
}

function getDbStats(): void {
    $pdo = getDB();

    $langs = $pdo->query("
        SELECT lang_code,
               COUNT(*) as total,
               SUM(CASE WHEN cefr_level IS NOT NULL THEN 1 ELSE 0 END) as leveled,
               SUM(CASE WHEN frequency_rank IS NOT NULL THEN 1 ELSE 0 END) as with_freq
        FROM dict_words
        GROUP BY lang_code
        ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Spanish CEFR distribution
    $esDist = $pdo->query("
        SELECT cefr_level, COUNT(*) as cnt
        FROM dict_words WHERE lang_code='es' AND cefr_level IS NOT NULL
        GROUP BY cefr_level ORDER BY FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')
    ")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'languages' => $langs,
        'es_cefr_distribution' => $esDist,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
