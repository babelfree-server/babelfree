<?php
// Dictionary Seeder: extracts vocabulary from content sources, populates dict_* tables

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$basePath = realpath(__DIR__ . '/../../');

// ========== HELPERS ==========

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
    // Strip HTML tags, trim
    $w = strip_tags($w);
    $w = trim($w, " \t\n\r\0\x0B.,;:!?¡¿\"'()[]{}");
    return $w;
}

function guessPOS(string $word): string {
    $word = mb_strtolower($word, 'UTF-8');
    // Common verb infinitive endings
    if (preg_match('/(ar|er|ir|ír)$/u', $word)) return 'verb';
    // Common adjective patterns
    if (preg_match('/(oso|osa|ble|nte|ivo|iva|ado|ada|ido|ida)$/u', $word)) return 'adjective';
    // Common adverb patterns
    if (preg_match('/mente$/u', $word)) return 'adverb';
    // Default to noun
    return 'noun';
}

function guessGender(string $word, string $pos): ?string {
    if ($pos !== 'noun' && $pos !== 'adjective') return null;
    $word = mb_strtolower($word, 'UTF-8');
    if (preg_match('/(a|ción|sión|dad|tad|tud|umbre)$/u', $word)) return 'f';
    if (preg_match('/(o|or|aje|ón)$/u', $word)) return 'm';
    return null;
}

function getCefrFromPath(string $path): string {
    if (strpos($path, '_a1') !== false || strpos($path, 'a1_') !== false) return 'A1';
    if (strpos($path, '_a2') !== false || strpos($path, 'a2_') !== false) return 'A2';
    if (strpos($path, 'b1_') !== false) return 'B1';
    if (strpos($path, 'b2_') !== false) return 'B2';
    if (strpos($path, 'c1_') !== false) return 'C1';
    if (strpos($path, 'c2_') !== false) return 'C2';
    return 'A1';
}

// ========== WORD COLLECTION ==========

$words = [];     // normalized => ['word' => original, 'cefr' => level, 'pos' => guess]
$examples = [];  // ['es' => sentence, 'en' => gloss, 'cefr' => level, 'source' => file, 'word_norm' => normalized key word]
$pairs = [];     // ['es' => spanish, 'en' => english] for translations

function addWord(string $raw, string $cefr, string $source) {
    global $words;
    $w = cleanWord($raw);
    if (mb_strlen($w) < 2 || mb_strlen($w) > 100) return;
    // Skip pure numbers, punctuation-only, or HTML artifacts
    if (preg_match('/^[\d\s.,]+$/', $w)) return;
    if (preg_match('/^[\?\.\-]+$/u', $w)) return;

    $norm = normalize($w);
    if (!$norm) return;

    // Keep earliest CEFR level
    if (!isset($words[$norm]) || cefrOrder($cefr) < cefrOrder($words[$norm]['cefr'])) {
        $words[$norm] = [
            'word' => $w,
            'cefr' => $cefr,
            'source' => $source,
        ];
    }
}

function cefrOrder(string $l): int {
    $map = ['A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6];
    return $map[$l] ?? 9;
}

function addExample(string $es, string $en, string $cefr, string $source, string $keyWord = '') {
    global $examples;
    $es = trim(strip_tags($es));
    $en = trim(strip_tags($en));
    if (!$es) return;
    $examples[] = [
        'es' => $es,
        'en' => $en,
        'cefr' => $cefr,
        'source' => $source,
        'word_norm' => $keyWord ? normalize(cleanWord($keyWord)) : '',
    ];
}

function addTranslation(string $es, string $en) {
    global $pairs;
    $es = trim(cleanWord($es));
    $en = trim(cleanWord($en));
    if (!$es || !$en) return;
    $pairs[normalize($es) . '|' . mb_strtolower($en)] = ['es' => $es, 'en' => $en];
}

// ========== PHASE 1: Content JSONs ==========

echo "=== Phase 1: Parsing content JSON files ===\n";

$jsonFiles = glob($basePath . '/content/*.json');
foreach ($jsonFiles as $jsonFile) {
    $filename = basename($jsonFile);
    $cefr = getCefrFromPath($filename);
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!$data || !isset($data['games'])) continue;

    $source = 'El Viaje del Jaguar ' . $cefr;

    foreach ($data['games'] as $game) {
        // vocabulary arrays
        if (!empty($game['vocabulary'])) {
            foreach ($game['vocabulary'] as $v) {
                addWord($v, $cefr, $source);
            }
        }

        // pairs (array of [spanish, meaning/translation])
        if (!empty($game['pairs'])) {
            foreach ($game['pairs'] as $pair) {
                if (is_array($pair) && count($pair) >= 2) {
                    addWord($pair[0], $cefr, $source);
                    addWord($pair[1], $cefr, $source);
                    addTranslation($pair[0], $pair[1]);
                }
            }
        }

        // examples with glosses
        if (!empty($game['sections'])) {
            foreach ($game['sections'] as $section) {
                if (!empty($section['examples'])) {
                    foreach ($section['examples'] as $ex) {
                        $es = $ex['es'] ?? '';
                        $en = $ex['gloss'] ?? $ex['en'] ?? '';
                        addExample($es, $en, $cefr, $source);
                        // Also extract individual words from Spanish example
                        $esWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($es));
                        foreach ($esWords as $ew) {
                            if (mb_strlen(trim($ew)) >= 2) addWord($ew, $cefr, $source);
                        }
                    }
                }
            }
        }

        // fill-in-blank questions
        if (!empty($game['questions'])) {
            foreach ($game['questions'] as $q) {
                if (!empty($q['answer'])) addWord($q['answer'], $cefr, $source);
                if (!empty($q['options'])) {
                    foreach ($q['options'] as $opt) addWord($opt, $cefr, $source);
                }
                if (!empty($q['verb'])) addWord($q['verb'], $cefr, $source);
            }
        }

        // category/sort items
        if (!empty($game['items'])) {
            foreach ($game['items'] as $item) {
                $text = is_array($item) ? ($item['text'] ?? '') : $item;
                addWord($text, $cefr, $source);
            }
        }

        // builder words
        if (!empty($game['words'])) {
            foreach ($game['words'] as $bw) {
                addWord($bw, $cefr, $source);
            }
        }

        // conversation turns
        if (!empty($game['turns'])) {
            foreach ($game['turns'] as $turn) {
                if (!empty($turn['options'])) {
                    foreach ($turn['options'] as $opt) {
                        // These are full sentences -extract words
                        $turnWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($opt));
                        foreach ($turnWords as $tw) {
                            if (mb_strlen(trim($tw)) >= 2) addWord($tw, $cefr, $source);
                        }
                    }
                }
                if (!empty($turn['answer'])) {
                    addExample($turn['answer'], '', $cefr, $source);
                }
            }
        }

        // dictation audio/answer
        if (!empty($game['audio'])) {
            $audioWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($game['audio']));
            foreach ($audioWords as $aw) {
                if (mb_strlen(trim($aw)) >= 2) addWord($aw, $cefr, $source);
            }
            if (!empty($game['answer'])) {
                addExample($game['answer'], '', $cefr, $source);
            }
        }

        // story text
        if (!empty($game['text'])) {
            $storyWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($game['text']));
            foreach ($storyWords as $sw) {
                if (mb_strlen(trim($sw)) >= 2) addWord($sw, $cefr, $source);
            }
        }

        // narrative body text
        if (!empty($game['body'])) {
            $bodyWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($game['body']));
            foreach ($bodyWords as $bw2) {
                if (mb_strlen(trim($bw2)) >= 2) addWord($bw2, $cefr, $source);
            }
        }
    }

    echo "  Parsed: $filename ($cefr)\n";
}

// ========== PHASE 2: Games HTML files (inline JS) ==========

echo "\n=== Phase 2: Parsing games.html files ===\n";

$gamesDirs = glob($basePath . '/*/dest*/games.html');
$gamesDirs = array_merge($gamesDirs, glob($basePath . '/*/module*/games.html'));

foreach ($gamesDirs as $gamesFile) {
    $relativePath = str_replace($basePath . '/', '', $gamesFile);
    $cefr = getCefrFromPath($relativePath);
    $source = 'El Viaje del Jaguar ' . $cefr;
    $html = file_get_contents($gamesFile);

    // Extract the var games = [...]; block
    if (preg_match('/var\s+games\s*=\s*\[(.+)\];/s', $html, $m)) {
        $jsContent = $m[1];

        // Extract quoted strings that look like Spanish words/sentences
        // Match single-quoted and double-quoted strings
        preg_match_all("/['\"]([^'\"]{2,100})['\"](?:\s*[,\]\)])/", $jsContent, $strMatches);

        if (!empty($strMatches[1])) {
            foreach ($strMatches[1] as $str) {
                // Skip obvious non-word strings (types, labels, CSS classes, etc.)
                if (preg_match('/^(pair|fill|fib|conjugation|listening|category|sorting|builder|translation|conversation|dictation|story|narrative|cancion|escaperoom|escape|Emparejar|Completar|Clasificar|Construir|Escuchar|Dictado|Conversación|Conjugación|Lectura|Narración|Canción|Sala de enigmas|A|B|speaker|name|type|label|instruction|answer|category|text|sentence|verb|subject|prompt|riddle|clue|hint|puzzleType|wordlock|cipher|riddle|onSolve|ambience|heartbeat|wind|rain|item|raiz-|historia-|constelacion-)/', $str)) {
                    continue;
                }
                // Skip unicode escape sequences that are just mechanics
                if (preg_match('/^\\\\u[0-9a-f]+/', $str)) continue;

                // If it's a short word (< 30 chars, no HTML), add as vocabulary
                $cleaned = strip_tags(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
                if (mb_strlen($cleaned) < 30 && mb_strlen($cleaned) >= 2) {
                    addWord($cleaned, $cefr, $source);
                }
                // If longer, treat as example sentence
                if (mb_strlen($cleaned) >= 15 && mb_strlen($cleaned) < 200) {
                    addExample($cleaned, '', $cefr, $source);
                }
            }
        }
    }

    echo "  Parsed: $relativePath ($cefr)\n";
}

// ========== PHASE 3: Insert into database ==========

echo "\n=== Phase 3: Inserting into database ===\n";
echo "  Unique Spanish words: " . count($words) . "\n";
echo "  Example sentences: " . count($examples) . "\n";
echo "  Translation pairs: " . count($pairs) . "\n";

// Start transaction
$pdo->beginTransaction();

try {
    // Insert Spanish words
    $insertWord = $pdo->prepare(
        'INSERT IGNORE INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, cefr_level, frequency_rank)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $rank = 0;
    $wordIdMap = []; // normalized => id

    foreach ($words as $norm => $info) {
        $rank++;
        $pos = guessPOS($info['word']);
        $gender = guessGender($info['word'], $pos);

        $insertWord->execute([
            'es',
            $info['word'],
            $norm,
            $pos,
            $gender,
            $info['cefr'],
            $rank
        ]);

        $id = $pdo->lastInsertId();
        if ($id) {
            $wordIdMap[$norm] = (int)$id;
        } else {
            // Word already existed, look it up
            $stmt = $pdo->prepare('SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1');
            $stmt->execute(['es', $norm]);
            $row = $stmt->fetch();
            if ($row) $wordIdMap[$norm] = (int)$row['id'];
        }
    }

    echo "  Inserted Spanish words: " . count($wordIdMap) . "\n";

    // Insert English translation words + translation pairs
    $insertEn = $pdo->prepare(
        'INSERT IGNORE INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, cefr_level)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertTrans = $pdo->prepare(
        'INSERT IGNORE INTO dict_translations (source_word_id, target_word_id, context)
         VALUES (?, ?, ?)'
    );

    $transCount = 0;
    foreach ($pairs as $key => $pair) {
        $parts = explode('|', $key);
        $esNorm = $parts[0];
        $enNorm = normalize($pair['en']);

        if (!isset($wordIdMap[$esNorm])) continue;
        $sourceId = $wordIdMap[$esNorm];

        // Insert English word
        $enPos = guessPOS($pair['en']);
        $insertEn->execute(['en', $pair['en'], $enNorm, $enPos, null, null]);

        $enId = $pdo->lastInsertId();
        if (!$enId) {
            $stmt = $pdo->prepare('SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1');
            $stmt->execute(['en', $enNorm]);
            $row = $stmt->fetch();
            if ($row) $enId = (int)$row['id'];
        }

        if ($enId) {
            $insertTrans->execute([$sourceId, (int)$enId, null]);
            $transCount++;
        }
    }

    echo "  Inserted translations: $transCount\n";

    // Insert example sentences
    $insertEx = $pdo->prepare(
        'INSERT INTO dict_examples (word_id, sentence, translation, cefr_level, source)
         VALUES (?, ?, ?, ?, ?)'
    );

    $exCount = 0;
    $seenExamples = [];
    foreach ($examples as $ex) {
        $exKey = normalize($ex['es']);
        if (isset($seenExamples[$exKey])) continue;
        $seenExamples[$exKey] = true;

        // Find the best matching word for this example
        $wordId = null;
        if ($ex['word_norm'] && isset($wordIdMap[$ex['word_norm']])) {
            $wordId = $wordIdMap[$ex['word_norm']];
        } else {
            // Try to match first content word from the sentence
            $sentWords = preg_split('/[\s,;.!?¡¿]+/u', strip_tags($ex['es']));
            foreach ($sentWords as $sw) {
                $sn = normalize(cleanWord($sw));
                if ($sn && isset($wordIdMap[$sn]) && mb_strlen($sn) > 2) {
                    $wordId = $wordIdMap[$sn];
                    break;
                }
            }
        }

        if ($wordId) {
            $insertEx->execute([
                $wordId,
                $ex['es'],
                $ex['en'] ?: null,
                $ex['cefr'],
                $ex['source']
            ]);
            $exCount++;
        }
    }

    echo "  Inserted examples: $exCount\n";

    $pdo->commit();
    echo "\n=== Seeding complete! ===\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Final stats
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_words WHERE lang_code = "es"');
echo "\nFinal stats:\n";
echo "  Spanish words: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_words WHERE lang_code = "en"');
echo "  English words: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_translations');
echo "  Translations: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_examples');
echo "  Examples: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query('SELECT cefr_level, COUNT(*) as c FROM dict_words WHERE lang_code = "es" GROUP BY cefr_level ORDER BY cefr_level');
echo "  By CEFR level:\n";
while ($row = $stmt->fetch()) {
    echo "    {$row['cefr_level']}: {$row['c']}\n";
}
