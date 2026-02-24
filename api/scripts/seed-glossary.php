<?php
/**
 * Glossary Seeder — Extracts game vocabulary from content JSON files
 * and games.html files into glossary_words, glossary_examples tables.
 * Links to dict_words for curated metadata.
 *
 * Fixes over old seeder:
 *  - Proper Unicode/HTML entity decoding
 *  - No POS guessing — links to dict_words for curated metadata
 *  - Tracks ecosystem, destination, game_type, source_file per word
 *
 * Run: php seed-glossary.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$basePath = realpath(__DIR__ . '/../../');

echo "=== Glossary Seeder ===\n\n";

// ── Helpers ────────────────────────────────────────────────────────

function normalize(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    $word = preg_replace('/[áà]/u', 'a', $word);
    $word = preg_replace('/[éè]/u', 'e', $word);
    $word = preg_replace('/[íì]/u', 'i', $word);
    $word = preg_replace('/[óò]/u', 'o', $word);
    $word = preg_replace('/[úù]/u', 'u', $word);
    return str_replace('ñ', 'n', $word);
}

function cleanWord(string $w): string {
    // Decode HTML entities and Unicode escapes
    $w = html_entity_decode($w, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Fix JSON-encoded Unicode escapes
    if (strpos($w, '\\u') !== false) {
        $decoded = json_decode('"' . $w . '"');
        if ($decoded) $w = $decoded;
    }
    $w = strip_tags($w);
    $w = trim($w, " \t\n\r\0\x0B.,;:!?¡¿\"'()[]{}");
    return $w;
}

function getEcosystem(string $filename): string {
    $ecosystems = ['bosque', 'sierra', 'costa', 'llanos', 'nevada', 'selva', 'islas', 'desierto'];
    foreach ($ecosystems as $eco) {
        if (stripos($filename, $eco) !== false) return $eco;
    }
    return 'general';
}

function getCefr(string $path): string {
    if (preg_match('/[_-]a1/i', $path)) return 'A1';
    if (preg_match('/[_-]a2/i', $path)) return 'A2';
    if (preg_match('/[_-]b1/i', $path)) return 'B1';
    if (preg_match('/[_-]b2/i', $path)) return 'B2';
    if (preg_match('/[_-]c1/i', $path)) return 'C1';
    if (preg_match('/[_-]c2/i', $path)) return 'C2';
    return 'A1';
}

function getDestinationId(string $filename, string $eco, string $cefr): string {
    // Try to extract destination ID from path
    if (preg_match('/dest(\d+)/', $filename, $m)) {
        return 'dest' . $m[1];
    }
    if (preg_match('/module(\d+)/', $filename, $m)) {
        return 'module' . $m[1];
    }
    return $eco . '_' . strtolower($cefr);
}

// ── Collect vocabulary ─────────────────────────────────────────────

$glossaryWords = []; // 'normalized|destId' => data
$glossaryExamples = []; // list of example sentences

function addGlossaryWord(string $raw, string $cefr, string $eco, string $destId, ?string $gameType, string $sourceFile) {
    global $glossaryWords;
    $w = cleanWord($raw);
    if (mb_strlen($w) < 2 || mb_strlen($w) > 100) return;
    if (preg_match('/^[\d\s.,]+$/', $w)) return;
    if (preg_match('/^[\?\.\-]+$/u', $w)) return;

    $norm = normalize($w);
    if (!$norm) return;

    $key = $norm . '|' . $destId;
    if (!isset($glossaryWords[$key])) {
        $glossaryWords[$key] = [
            'word' => $w,
            'normalized' => $norm,
            'cefr' => $cefr,
            'ecosystem' => $eco,
            'destination_id' => $destId,
            'game_type' => $gameType,
            'source_file' => $sourceFile,
        ];
    }
}

function addGlossaryExample(string $es, ?string $gloss, string $cefr, string $wordNorm, ?string $gameType, string $sourceFile) {
    global $glossaryExamples;
    $es = trim(strip_tags(html_entity_decode($es, ENT_QUOTES, 'UTF-8')));
    if (!$es || mb_strlen($es) < 3) return;
    $glossaryExamples[] = [
        'sentence_es' => $es,
        'sentence_gloss' => $gloss ? trim(strip_tags($gloss)) : null,
        'cefr' => $cefr,
        'word_norm' => $wordNorm,
        'game_type' => $gameType,
        'source_file' => $sourceFile,
    ];
}

// ── Phase 1: Parse content JSON files ──────────────────────────────

echo "--- Phase 1: Parsing content JSON files ---\n";

$jsonFiles = glob($basePath . '/content/*.json');
foreach ($jsonFiles as $jsonFile) {
    $filename = basename($jsonFile);
    $eco = getEcosystem($filename);
    $cefr = getCefr($filename);
    $destId = getDestinationId($filename, $eco, $cefr);
    $sourceFile = 'content/' . $filename;

    $data = json_decode(file_get_contents($jsonFile), true);
    if (!$data || !isset($data['games'])) continue;

    foreach ($data['games'] as $gameIdx => $game) {
        $gameType = $game['type'] ?? null;
        $gameDest = $destId . '_g' . $gameIdx;

        // vocabulary arrays
        if (!empty($game['vocabulary'])) {
            foreach ($game['vocabulary'] as $v) {
                addGlossaryWord($v, $cefr, $eco, $destId, $gameType, $sourceFile);
            }
        }

        // pairs
        if (!empty($game['pairs'])) {
            foreach ($game['pairs'] as $pair) {
                if (is_array($pair) && count($pair) >= 2) {
                    addGlossaryWord($pair[0], $cefr, $eco, $destId, $gameType, $sourceFile);
                    // Add pair as example
                    $norm = normalize(cleanWord($pair[0]));
                    addGlossaryExample($pair[0], $pair[1], $cefr, $norm, $gameType, $sourceFile);
                }
            }
        }

        // sections with examples
        if (!empty($game['sections'])) {
            foreach ($game['sections'] as $section) {
                if (!empty($section['examples'])) {
                    foreach ($section['examples'] as $ex) {
                        $es = $ex['es'] ?? '';
                        $gloss = $ex['gloss'] ?? $ex['en'] ?? '';
                        if ($es) {
                            // Extract words from example
                            $words = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($es));
                            foreach ($words as $w) {
                                if (mb_strlen(trim($w)) >= 2) {
                                    addGlossaryWord($w, $cefr, $eco, $destId, $gameType, $sourceFile);
                                }
                            }
                            $firstWordNorm = '';
                            foreach ($words as $w) {
                                $n = normalize(cleanWord($w));
                                if ($n && mb_strlen($n) > 2) { $firstWordNorm = $n; break; }
                            }
                            addGlossaryExample($es, $gloss, $cefr, $firstWordNorm, $gameType, $sourceFile);
                        }
                    }
                }
            }
        }

        // questions (fill, choice, etc.)
        if (!empty($game['questions'])) {
            foreach ($game['questions'] as $q) {
                if (!empty($q['answer'])) addGlossaryWord($q['answer'], $cefr, $eco, $destId, $gameType, $sourceFile);
                if (!empty($q['verb'])) addGlossaryWord($q['verb'], $cefr, $eco, $destId, $gameType, $sourceFile);
                if (!empty($q['options'])) {
                    foreach ($q['options'] as $opt) addGlossaryWord($opt, $cefr, $eco, $destId, $gameType, $sourceFile);
                }
            }
        }

        // items (category, sorting)
        if (!empty($game['items'])) {
            foreach ($game['items'] as $item) {
                $text = is_array($item) ? ($item['text'] ?? '') : $item;
                addGlossaryWord($text, $cefr, $eco, $destId, $gameType, $sourceFile);
            }
        }

        // words (builder)
        if (!empty($game['words'])) {
            foreach ($game['words'] as $bw) {
                addGlossaryWord($bw, $cefr, $eco, $destId, $gameType, $sourceFile);
            }
        }

        // escape room puzzles
        if (!empty($game['puzzles'])) {
            foreach ($game['puzzles'] as $puzzle) {
                if (!empty($puzzle['answer'])) addGlossaryWord($puzzle['answer'], $cefr, $eco, $destId, 'escaperoom', $sourceFile);
                if (!empty($puzzle['clue'])) {
                    $clueWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($puzzle['clue']));
                    foreach ($clueWords as $cw) {
                        if (mb_strlen(trim($cw)) >= 2) addGlossaryWord($cw, $cefr, $eco, $destId, 'escaperoom', $sourceFile);
                    }
                }
            }
        }
    }

    echo "  Parsed: $filename ($eco / $cefr) — " . count($data['games']) . " games\n";
}

// ── Phase 2: Parse games.html files ────────────────────────────────

echo "\n--- Phase 2: Parsing games.html files ---\n";

$gamesDirs = glob($basePath . '/*/dest*/games.html');
$gamesDirs = array_merge($gamesDirs, glob($basePath . '/*/module*/games.html'));

foreach ($gamesDirs as $gamesFile) {
    $relativePath = str_replace($basePath . '/', '', $gamesFile);
    $eco = getEcosystem($relativePath);
    $cefr = getCefr($relativePath);
    $destId = getDestinationId($relativePath, $eco, $cefr);
    $html = file_get_contents($gamesFile);

    // Extract the var games = [...]; block
    if (preg_match('/var\s+games\s*=\s*\[(.+)\];/s', $html, $m)) {
        $jsContent = $m[1];
        preg_match_all("/['\"]([^'\"]{2,60})['\"](?:\s*[,\]\)])/", $jsContent, $strMatches);

        if (!empty($strMatches[1])) {
            foreach ($strMatches[1] as $str) {
                // Skip game mechanics strings
                if (preg_match('/^(pair|fill|fib|conjugation|listening|category|sorting|builder|translation|conversation|dictation|story|narrative|cancion|escaperoom|type|label|instruction|answer|category|text|sentence|verb|subject|prompt|riddle|clue|hint|puzzleType|wordlock|cipher|onSolve|ambience|item|raiz-|historia-|constelacion-)/', $str)) {
                    continue;
                }
                $cleaned = cleanWord($str);
                if (mb_strlen($cleaned) >= 2 && mb_strlen($cleaned) < 30) {
                    addGlossaryWord($cleaned, $cefr, $eco, $destId, null, $relativePath);
                }
            }
        }
    }

    echo "  Parsed: $relativePath ($eco / $cefr)\n";
}

// ── Phase 3: Insert into database ──────────────────────────────────

echo "\n--- Phase 3: Inserting into database ---\n";
echo "  Unique glossary words: " . count($glossaryWords) . "\n";
echo "  Examples: " . count($glossaryExamples) . "\n";

// Look up dict_word_id for cross-referencing
$findDictWord = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = 'es' AND word_normalized = ? LIMIT 1"
);

$insertGlossary = $pdo->prepare(
    'INSERT INTO glossary_words (word, word_normalized, cefr_level, ecosystem, destination_id, game_type, source_file, dict_word_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), game_type = COALESCE(VALUES(game_type), game_type)'
);

$insertExample = $pdo->prepare(
    'INSERT INTO glossary_examples (glossary_word_id, sentence_es, sentence_gloss, cefr_level, game_type, source_file)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$pdo->beginTransaction();

try {
    $wordIdMap = []; // normalized|destId => glossary_word_id
    $wordCount = 0;

    foreach ($glossaryWords as $key => $info) {
        // Find dict_word_id
        $dictWordId = null;
        $findDictWord->execute([$info['normalized']]);
        $dictRow = $findDictWord->fetch();
        if ($dictRow) $dictWordId = (int)$dictRow['id'];

        $insertGlossary->execute([
            $info['word'],
            $info['normalized'],
            $info['cefr'],
            $info['ecosystem'],
            $info['destination_id'],
            $info['game_type'],
            $info['source_file'],
            $dictWordId,
        ]);

        $id = $pdo->lastInsertId();
        if ($id) {
            $wordIdMap[$key] = (int)$id;
        }
        $wordCount++;
    }

    echo "  Inserted glossary words: $wordCount\n";

    // Insert examples
    $exCount = 0;
    $seenExamples = [];

    foreach ($glossaryExamples as $ex) {
        $exKey = normalize($ex['sentence_es']);
        if (isset($seenExamples[$exKey])) continue;
        $seenExamples[$exKey] = true;

        // Find the glossary_word_id for this example
        $glossaryWordId = null;
        if ($ex['word_norm']) {
            // Search across all destinations for this normalized word
            foreach ($wordIdMap as $mapKey => $mapId) {
                if (strpos($mapKey, $ex['word_norm'] . '|') === 0) {
                    $glossaryWordId = $mapId;
                    break;
                }
            }
        }

        if ($glossaryWordId) {
            $insertExample->execute([
                $glossaryWordId,
                $ex['sentence_es'],
                $ex['sentence_gloss'],
                $ex['cefr'],
                $ex['game_type'],
                $ex['source_file'],
            ]);
            $exCount++;
        }
    }

    echo "  Inserted examples: $exCount\n";

    $pdo->commit();
    echo "\n=== Glossary Seeding Complete ===\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Stats
$stmt = $pdo->query('SELECT COUNT(*) as c FROM glossary_words');
echo "\nTotal glossary words: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query('SELECT COUNT(*) as c FROM glossary_examples');
echo "Total glossary examples: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query('SELECT ecosystem, COUNT(*) as c FROM glossary_words GROUP BY ecosystem ORDER BY ecosystem');
echo "\nBy ecosystem:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['ecosystem']}: {$row['c']}\n";
}

$stmt = $pdo->query('SELECT cefr_level, COUNT(*) as c FROM glossary_words GROUP BY cefr_level ORDER BY cefr_level');
echo "\nBy CEFR level:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['cefr_level']}: {$row['c']}\n";
}

$stmt = $pdo->query('SELECT COUNT(*) as c FROM glossary_words WHERE dict_word_id IS NOT NULL');
echo "\nLinked to dictionary: " . $stmt->fetch()['c'] . "\n";
