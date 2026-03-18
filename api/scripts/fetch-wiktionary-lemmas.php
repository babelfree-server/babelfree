<?php
/**
 * fetch-wiktionary-lemmas.php — Expand dictionary with high-frequency Spanish words
 *
 * Reads the 50K frequency list (OpenSubtitles via FrequencyWords),
 * finds words we don't already have, inserts them with CEFR level,
 * then enriches from Wiktionary (POS + definitions).
 *
 * Phase A (fast): Insert new words from frequency list → dict_words
 * Phase B (slow): Enrich with Wiktionary definitions (respects rate limits)
 *
 * Usage:
 *   php fetch-wiktionary-lemmas.php                    # Insert + enrich 3000 new words
 *   php fetch-wiktionary-lemmas.php --limit=5000       # Custom limit
 *   php fetch-wiktionary-lemmas.php --insert-only      # Phase A only (no Wiktionary calls)
 *   php fetch-wiktionary-lemmas.php --enrich-only      # Phase B only (enrich existing empty words)
 *   php fetch-wiktionary-lemmas.php --dry-run          # Preview without inserting
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$dataDir = __DIR__ . '/data';

// CLI options
$dryRun = in_array('--dry-run', $argv ?? []);
$insertOnly = in_array('--insert-only', $argv ?? []);
$enrichOnly = in_array('--enrich-only', $argv ?? []);
$limit = 3000;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) $limit = (int)substr($arg, 8);
}

echo "=== Dictionary Lemma Expander ===\n";
echo "  Limit: $limit words\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : ($insertOnly ? 'INSERT ONLY' : ($enrichOnly ? 'ENRICH ONLY' : 'FULL'))) . "\n\n";

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

function getCefrByRank(int $rank): string {
    if ($rank <= 500)   return 'A1';
    if ($rank <= 1200)  return 'A2';
    if ($rank <= 2500)  return 'B1';
    if ($rank <= 5000)  return 'B2';
    if ($rank <= 10000) return 'C1';
    return 'C2';
}

function isSpanishWord(string $word): bool {
    // Must be lowercase, at least 3 chars, no digits/symbols, no slashes
    if (mb_strlen($word) < 3) return false;
    if (preg_match('/[0-9@#$%^&*+=\/]/', $word)) return false;
    if (mb_strtoupper($word) === $word && preg_match('/^[A-Z]{2,}$/', $word)) return false;
    // Must contain at least one letter
    if (!preg_match('/[a-záéíóúñü]/ui', $word)) return false;
    // Skip multi-word phrases
    if (str_contains($word, ' ')) return false;
    return true;
}

// English-only words to skip
$englishOnly = array_flip([
    'the', 'and', 'for', 'with', 'that', 'this', 'from', 'not', 'but', 'you',
    'your', 'they', 'them', 'can', 'all', 'one', 'two', 'see', 'get', 'set',
    'out', 'new', 'now', 'how', 'did', 'its', 'may', 'our', 'own', 'too',
    'use', 'any', 'few', 'her', 'him', 'his', 'she', 'who', 'old', 'got',
    'let', 'put', 'say', 'ran', 'yet', 'run', 'try', 'bit', 'cut',
    'hot', 'lot', 'sat', 'six', 'ten', 'top', 'add', 'bad', 'big', 'buy',
    'dry', 'eat', 'end', 'eye', 'far', 'fit', 'fly', 'fun', 'guy', 'hit',
    'job', 'kid', 'lie', 'low', 'map', 'mix', 'net', 'nor', 'odd', 'pay',
    'per', 'pin', 'pop', 'raw', 'red', 'rid', 'row', 'sad', 'sky', 'sum',
    'sun', 'tea', 'tie', 'tip', 'wet', 'win', 'won', 'yes', 'road',
    'like', 'over', 'look', 'down', 'city', 'back', 'best', 'call', 'come',
    'left', 'line', 'long', 'make', 'much', 'need', 'play', 'show',
    'some', 'song', 'sure', 'take', 'team', 'tell', 'time', 'turn', 'very',
    'want', 'west', 'what', 'when', 'work', 'year', 'been', 'done', 'each',
    'even', 'find', 'free', 'good', 'help', 'here', 'high', 'home', 'hope',
    'into', 'just', 'keep', 'kind', 'last', 'life', 'live', 'made', 'many',
    'more', 'most', 'must', 'name', 'next', 'only', 'open', 'part', 'past',
    'plan', 'rest', 'same', 'side', 'such', 'talk', 'than', 'then', 'upon',
    'used', 'user', 'wait', 'walk', 'went', 'wide', 'word', 'about', 'after',
    'again', 'being', 'black', 'bring', 'close', 'could', 'dream', 'early',
    'every', 'first', 'given', 'going', 'great', 'green', 'group', 'house',
    'known', 'large', 'later', 'least', 'level', 'light', 'might', 'money',
    'month', 'moved', 'never', 'night', 'often', 'order', 'other', 'place',
    'point', 'power', 'quite', 'right', 'round', 'seven', 'shall', 'since',
    'small', 'sound', 'south', 'space', 'start', 'state', 'still', 'story',
    'study', 'taken', 'their', 'there', 'think', 'third', 'those', 'three',
    'times', 'today', 'under', 'until', 'using', 'water', 'while',
    'white', 'whole', 'woman', 'women', 'world', 'would', 'write', 'wrong',
    'young', 'people', 'should', 'through', 'before', 'between', 'another',
    'because', 'during', 'without', 'really', 'going', 'could', 'would',
    'where', 'which', 'there', 'these', 'those', 'being', 'doing',
    'having', 'saying', 'getting', 'making', 'coming', 'taking',
    'looking', 'thinking', 'feeling', 'trying', 'leaving', 'calling',
]);

// Words that look English but ARE valid Spanish
$spanishExceptions = array_flip([
    'bar', 'sol', 'club', 'van', 'red', 'son', 'fin', 'pan', 'ven',
    'dan', 'den', 'sin', 'don', 'dos', 'gas', 'mal', 'par', 'pie',
    'sal', 'ser', 'tan', 'ver', 'voz', 'sur', 'mes', 'mar', 'luz', 'ley',
    'ojo', 'dia', 'rio', 'oro', 'hoy', 'hay', 'fue', 'era', 'dar',
    'bus', 'test', 'chef', 'show', 'rock', 'jazz', 'rap',
    'fan', 'web', 'link', 'chat', 'blog', 'app',
]);

// ── Phase A: Insert new words from frequency list ──────────────────

if (!$enrichOnly) {
    echo "--- Phase A: Loading 50K frequency list ---\n";

    $freqFile = $dataDir . '/frequency_es_50k.txt';
    if (!file_exists($freqFile)) {
        echo "  ERROR: frequency_es_50k.txt not found. Download from:\n";
        echo "  https://raw.githubusercontent.com/hermitdave/FrequencyWords/master/content/2018/es/es_50k.txt\n";
        exit(1);
    }

    // Load existing words
    $stmt = $pdo->query("SELECT LOWER(word) AS w FROM dict_words WHERE lang_code='es'");
    $existing = [];
    while ($row = $stmt->fetch()) {
        $existing[$row['w']] = true;
    }
    echo "  Existing ES words: " . count($existing) . "\n";

    // Load CEFR wordlists
    $cefrMap = [];
    $cefrFile = $dataDir . '/cefr_wordlists.json';
    if (file_exists($cefrFile)) {
        $cefrData = json_decode(file_get_contents($cefrFile), true);
        if ($cefrData) {
            foreach ($cefrData as $level => $words) {
                foreach ($words as $w) {
                    $cefrMap[normalize($w)] = strtoupper($level);
                }
            }
        }
    }

    // Read frequency list and find new words
    $fp = fopen($freqFile, 'r');
    $rank = 0;
    $newWords = [];

    while (($line = fgets($fp)) !== false) {
        $rank++;
        $parts = preg_split('/\s+/', trim($line), 2);
        if (count($parts) < 1) continue;

        $word = mb_strtolower(trim($parts[0]), 'UTF-8');

        if (!isSpanishWord($word)) continue;
        if (isset($existing[$word])) continue;
        if (isset($englishOnly[$word]) && !isset($spanishExceptions[$word])) continue;

        // Skip English suffix patterns
        if (preg_match('/(ing|tion|ness|ment|ful|less|ous|ble|ship|ght|tch|wh)$/', $word)) continue;

        $norm = normalize($word);
        $cefr = $cefrMap[$norm] ?? getCefrByRank($rank);

        $newWords[] = [
            'word' => $word,
            'normalized' => $norm,
            'rank' => $rank,
            'cefr' => $cefr,
        ];

        if (count($newWords) >= $limit) break;
    }
    fclose($fp);

    echo "  New candidates: " . count($newWords) . "\n";

    if (!$dryRun && count($newWords) > 0) {
        $insertWord = $pdo->prepare(
            'INSERT INTO dict_words (lang_code, word, word_normalized, cefr_level, frequency_rank)
             VALUES ("es", ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               frequency_rank = COALESCE(VALUES(frequency_rank), frequency_rank),
               cefr_level = COALESCE(cefr_level, VALUES(cefr_level))'
        );

        $inserted = 0;
        foreach ($newWords as $nw) {
            $insertWord->execute([$nw['word'], $nw['normalized'], $nw['cefr'], $nw['rank']]);
            $inserted++;
        }
        echo "  Inserted: $inserted new words\n";
    } elseif ($dryRun) {
        echo "  [DRY RUN] Would insert " . count($newWords) . " words\n";
        // Show sample
        echo "  Sample:\n";
        foreach (array_slice($newWords, 0, 20) as $nw) {
            echo "    #{$nw['rank']} {$nw['word']} ({$nw['cefr']})\n";
        }
    }

    echo "\n";
}

// ── Phase B: Enrich words without definitions from Wiktionary ─────

if (!$insertOnly && !$dryRun) {
    echo "--- Phase B: Wiktionary enrichment ---\n";

    // Get words without definitions, ordered by frequency rank
    $stmt = $pdo->prepare(
        "SELECT w.id, w.word FROM dict_words w
         WHERE w.lang_code = 'es'
           AND w.part_of_speech IS NULL
           AND BINARY w.word = LOWER(w.word)
           AND CHAR_LENGTH(w.word) >= 3
           AND NOT EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w.id)
         ORDER BY w.frequency_rank ASC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    $toEnrich = $stmt->fetchAll();

    echo "  Words to enrich: " . count($toEnrich) . "\n\n";

    if (count($toEnrich) === 0) {
        echo "  Nothing to enrich.\n\n";
    } else {
        $updatePOS = $pdo->prepare(
            'UPDATE dict_words SET part_of_speech = ?, gender = ?, pronunciation_ipa = ? WHERE id = ?'
        );
        $insertDef = $pdo->prepare(
            'INSERT INTO dict_definitions (word_id, lang_code, definition, sort_order)
             VALUES (?, "es", ?, ?)
             ON DUPLICATE KEY UPDATE definition = VALUES(definition)'
        );

        $enriched = 0;
        $defCount = 0;
        $noData = 0;
        $startTime = time();
        $consecutiveErrors = 0;

        foreach ($toEnrich as $i => $row) {
            // Progress every 25 words
            if ($i > 0 && $i % 25 === 0) {
                $elapsed = max(1, time() - $startTime);
                $rate = round($i / $elapsed, 1);
                echo "  [$i/" . count($toEnrich) . "] Enriched: $enriched, No data: $noData ($rate/s)\n";
            }

            // Back off if we're getting rate limited
            if ($consecutiveErrors >= 5) {
                echo "  Rate limited — pausing 30s...\n";
                sleep(30);
                $consecutiveErrors = 0;
            }

            $word = $row['word'];
            $url = "https://es.wiktionary.org/api/rest_v1/page/definition/" . rawurlencode($word);

            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'header' => "User-Agent: YaguaraDictBot/1.0 (babelfree.com)\r\n",
                ],
            ]);

            $json = @file_get_contents($url, false, $ctx);
            usleep(500000); // 500ms between requests

            if (!$json) {
                $consecutiveErrors++;
                $noData++;
                continue;
            }

            $consecutiveErrors = 0;
            $data = json_decode($json, true);
            if (!$data || empty($data['es'])) {
                $noData++;
                continue;
            }

            // Extract POS
            $posMap = [
                'Sustantivo' => 'noun', 'Nombre' => 'noun',
                'Verbo' => 'verb', 'Adjetivo' => 'adjective',
                'Adverbio' => 'adverb', 'Preposición' => 'preposition',
                'Conjunción' => 'conjunction', 'Pronombre' => 'pronoun',
                'Interjección' => 'interjection', 'Artículo' => 'article',
            ];

            $pos = null;
            $gender = null;
            $defs = [];

            foreach ($data['es'] as $section) {
                $rawPos = $section['partOfSpeech'] ?? '';
                if (!$pos && isset($posMap[$rawPos])) {
                    $pos = $posMap[$rawPos];
                }
                foreach ($section['definitions'] ?? [] as $defEntry) {
                    $text = strip_tags($defEntry['definition'] ?? '');
                    $text = trim($text);
                    if ($text && mb_strlen($text) > 2) {
                        if ($pos === 'noun' && !$gender) {
                            if (preg_match('/\b(masculino|masc\.?)\b/i', $text)) $gender = 'm';
                            elseif (preg_match('/\b(femenino|fem\.?)\b/i', $text)) $gender = 'f';
                        }
                        $defs[] = $text;
                    }
                }
            }

            // Extract IPA if available
            $ipa = null;
            // IPA is not in the definition API, skip for now

            if ($pos) {
                $updatePOS->execute([$pos, $gender, $ipa, $row['id']]);
            }

            foreach (array_slice($defs, 0, 5) as $order => $def) {
                $insertDef->execute([$row['id'], $def, $order]);
                $defCount++;
            }

            if ($pos || !empty($defs)) {
                $enriched++;
            } else {
                $noData++;
            }
        }

        $elapsed = max(1, time() - $startTime);
        echo "\n  Enrichment done: $enriched words, $defCount definitions ({$elapsed}s)\n";
        echo "  No Wiktionary data: $noData\n\n";
    }
}

// ── Summary ───────────────────────────────────────────────────────

if (!$dryRun) {
    echo "=== Updated Totals ===\n";

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM dict_words WHERE lang_code='es'");
    echo "  ES words: " . $stmt->fetch()['c'] . "\n";

    $stmt = $pdo->query(
        "SELECT COUNT(DISTINCT w.id) AS c FROM dict_words w
         JOIN dict_definitions d ON d.word_id = w.id WHERE w.lang_code='es'"
    );
    echo "  With definitions: " . $stmt->fetch()['c'] . "\n";

    $stmt = $pdo->query(
        "SELECT COUNT(*) AS c FROM dict_words w
         WHERE w.lang_code='es'
           AND w.part_of_speech IS NOT NULL
           AND w.cefr_level IS NOT NULL
           AND EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w.id)"
    );
    echo "  Quality entries: " . $stmt->fetch()['c'] . "\n";

    $stmt = $pdo->query(
        "SELECT cefr_level, COUNT(*) AS c FROM dict_words w
         WHERE w.lang_code='es'
           AND w.part_of_speech IS NOT NULL
           AND w.cefr_level IS NOT NULL
           AND EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w.id)
         GROUP BY cefr_level ORDER BY FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')"
    );
    echo "  By CEFR:\n";
    while ($row = $stmt->fetch()) {
        echo "    {$row['cefr_level']}: {$row['c']}\n";
    }
}

echo "\nDone.\n";
