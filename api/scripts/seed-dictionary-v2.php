<?php
/**
 * Dictionary Seeder v2 — Curated vocabulary pipeline
 *
 * Steps:
 *  1. Load Spanish frequency list → dict_words (lang_code='es')
 *  2. Fetch POS/gender/IPA from Wiktionary API (es.wiktionary.org)
 *  3. Fetch Spanish definitions from Wiktionary → dict_definitions (lang_code='es')
 *  4. Fetch English definitions from en.wiktionary.org → dict_definitions (lang_code='en')
 *  5. Assign CEFR levels from cefr_wordlists.json or frequency fallback
 *
 * Replaces the old seed-dictionary.php.
 * Run: php seed-dictionary-v2.php [--skip-wiktionary] [--limit=500]
 *
 * Data files required in api/scripts/data/:
 *   - frequency_es.tsv   (rank\tword\tfrequency)
 *   - cefr_wordlists.json ({"A1": ["word",...], "A2": [...], ...})
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$dataDir = __DIR__ . '/data';

// CLI options
$skipWiktionary = in_array('--skip-wiktionary', $argv ?? []);
$limit = 5000;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    }
}

echo "=== Dictionary Seeder v2 ===\n";
echo "  Limit: $limit words\n";
echo "  Wiktionary: " . ($skipWiktionary ? 'SKIPPED' : 'enabled') . "\n\n";

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

function wiktionaryFetch(string $word, string $lang = 'es'): ?array {
    $host = $lang . '.wiktionary.org';
    $url = "https://$host/api/rest_v1/page/definition/" . rawurlencode($word);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: YaguaraDictBot/1.0 (babelfree.com)\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    return $data ?: null;
}

function extractPOS(array $wikiData): ?string {
    $map = [
        'Sustantivo' => 'noun', 'Nombre' => 'noun',
        'Verbo' => 'verb',
        'Adjetivo' => 'adjective',
        'Adverbio' => 'adverb',
        'Preposición' => 'preposition',
        'Conjunción' => 'conjunction',
        'Pronombre' => 'pronoun',
        'Interjección' => 'interjection',
        'Artículo' => 'article',
        'Determinante' => 'determiner',
        // English POS for en.wiktionary
        'Noun' => 'noun', 'Verb' => 'verb', 'Adjective' => 'adjective',
        'Adverb' => 'adverb', 'Preposition' => 'preposition',
        'Conjunction' => 'conjunction', 'Pronoun' => 'pronoun',
        'Interjection' => 'interjection',
    ];

    if (!empty($wikiData['es'])) {
        foreach ($wikiData['es'] as $section) {
            $pos = $section['partOfSpeech'] ?? '';
            if (isset($map[$pos])) return $map[$pos];
        }
    }
    return null;
}

function extractDefinitions(array $wikiData, string $lang = 'es'): array {
    $defs = [];
    $sections = $wikiData[$lang] ?? $wikiData['en'] ?? [];
    foreach ($sections as $section) {
        foreach ($section['definitions'] ?? [] as $defEntry) {
            $text = strip_tags($defEntry['definition'] ?? '');
            $text = trim($text);
            if ($text && mb_strlen($text) > 2) {
                $defs[] = $text;
            }
        }
    }
    return array_slice($defs, 0, 5); // Max 5 definitions
}

// ── Step 1: Load frequency list ────────────────────────────────────

echo "--- Step 1: Loading frequency list ---\n";

$freqFile = $dataDir . '/frequency_es.tsv';
if (!file_exists($freqFile)) {
    echo "  WARNING: frequency_es.tsv not found. Creating from existing dict_words.\n";
    // Fallback: export current words ordered by frequency_rank
    $stmt = $pdo->query(
        "SELECT word, frequency_rank FROM dict_words
         WHERE lang_code = 'es' AND word != ''
         ORDER BY frequency_rank ASC LIMIT $limit"
    );
    $existingWords = $stmt->fetchAll();

    if (count($existingWords) > 0) {
        $fp = fopen($freqFile, 'w');
        fwrite($fp, "rank\tword\tfrequency\n");
        foreach ($existingWords as $ew) {
            fwrite($fp, "{$ew['frequency_rank']}\t{$ew['word']}\t0\n");
        }
        fclose($fp);
        echo "  Generated frequency file from " . count($existingWords) . " existing words\n";
    } else {
        echo "  ERROR: No frequency data available. Place frequency_es.tsv in data/ directory.\n";
        echo "  Format: rank<TAB>word<TAB>frequency (one per line, header row)\n";
        exit(1);
    }
}

$freqWords = [];
$fp = fopen($freqFile, 'r');
$header = fgetcsv($fp, 0, "\t"); // Skip header
while (($row = fgetcsv($fp, 0, "\t")) !== false) {
    if (count($row) < 2) continue;
    $rank = (int)$row[0];
    $word = trim($row[1]);
    if ($word && $rank > 0 && $rank <= $limit) {
        $freqWords[$rank] = $word;
    }
}
fclose($fp);
echo "  Loaded: " . count($freqWords) . " frequency entries\n";

// ── Step 2: Load CEFR wordlists ────────────────────────────────────

echo "\n--- Step 2: Loading CEFR assignments ---\n";

$cefrFile = $dataDir . '/cefr_wordlists.json';
$cefrMap = []; // normalized_word => CEFR level

if (file_exists($cefrFile)) {
    $cefrData = json_decode(file_get_contents($cefrFile), true);
    if ($cefrData) {
        foreach ($cefrData as $level => $words) {
            foreach ($words as $w) {
                $cefrMap[normalize($w)] = strtoupper($level);
            }
        }
    }
    echo "  Loaded: " . count($cefrMap) . " CEFR-assigned words\n";
} else {
    echo "  WARNING: cefr_wordlists.json not found. Using frequency fallback.\n";
}

function getCefrByFrequency(int $rank): string {
    if ($rank <= 500)  return 'A1';
    if ($rank <= 1200) return 'A2';
    if ($rank <= 2500) return 'B1';
    if ($rank <= 4000) return 'B2';
    if ($rank <= 4800) return 'C1';
    return 'C2';
}

// ── Step 3: Insert/update Spanish words ────────────────────────────

echo "\n--- Step 3: Inserting Spanish words ---\n";

$insertWord = $pdo->prepare(
    'INSERT INTO dict_words (lang_code, word, word_normalized, cefr_level, frequency_rank)
     VALUES ("es", ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       frequency_rank = VALUES(frequency_rank),
       cefr_level = COALESCE(VALUES(cefr_level), cefr_level)'
);

$inserted = 0;
foreach ($freqWords as $rank => $word) {
    $norm = normalize($word);
    $cefr = $cefrMap[$norm] ?? getCefrByFrequency($rank);

    $insertWord->execute([$word, $norm, $cefr, $rank]);
    $inserted++;
}
echo "  Inserted/updated: $inserted Spanish words\n";

// ── Step 4: Wiktionary enrichment (POS, definitions) ──────────────

if (!$skipWiktionary) {
    echo "\n--- Step 4: Wiktionary enrichment ---\n";

    // Get all Spanish words without POS or definitions
    $stmt = $pdo->prepare(
        "SELECT id, word, word_normalized FROM dict_words
         WHERE lang_code = 'es' AND (part_of_speech IS NULL OR id NOT IN (SELECT word_id FROM dict_definitions WHERE lang_code = 'es'))
         ORDER BY frequency_rank ASC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    $toEnrich = $stmt->fetchAll();

    $updatePOS = $pdo->prepare(
        'UPDATE dict_words SET part_of_speech = ?, gender = ? WHERE id = ?'
    );
    $insertDef = $pdo->prepare(
        'INSERT INTO dict_definitions (word_id, lang_code, definition, sort_order)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE definition = VALUES(definition)'
    );

    $enriched = 0;
    $defCount = 0;
    $batchSize = 50;
    $requestCount = 0;

    foreach ($toEnrich as $i => $row) {
        // Rate limiting: ~200 requests/min ≈ 1 request per 300ms
        if ($requestCount > 0 && $requestCount % $batchSize === 0) {
            echo "  Progress: $requestCount / " . count($toEnrich) . " ($enriched enriched, $defCount defs)\n";
            usleep(300000); // 300ms pause every 50 requests
        }

        // Spanish Wiktionary for POS + Spanish definitions
        $wikiData = wiktionaryFetch($row['word'], 'es');
        $requestCount++;

        if ($wikiData) {
            $pos = extractPOS($wikiData);
            if ($pos) {
                $gender = null;
                if ($pos === 'noun') {
                    // Try to determine gender from Wiktionary data
                    $firstSection = $wikiData['es'][0] ?? [];
                    $rawDef = $firstSection['definitions'][0]['definition'] ?? '';
                    if (preg_match('/\b(masculino|masc\.?|m\.)\b/i', $rawDef)) $gender = 'm';
                    elseif (preg_match('/\b(femenino|fem\.?|f\.)\b/i', $rawDef)) $gender = 'f';
                }
                $updatePOS->execute([$pos, $gender, $row['id']]);
                $enriched++;
            }

            $defs = extractDefinitions($wikiData, 'es');
            foreach ($defs as $order => $def) {
                $insertDef->execute([$row['id'], 'es', $def, $order]);
                $defCount++;
            }
        }

        // English Wiktionary for English definitions (A1-A2 level gating)
        $enWikiData = wiktionaryFetch($row['word'], 'en');
        $requestCount++;

        if ($enWikiData) {
            $enDefs = extractDefinitions($enWikiData, 'en');
            foreach ($enDefs as $order => $def) {
                $insertDef->execute([$row['id'], 'en', $def, $order]);
                $defCount++;
            }
        }

        usleep(300000); // 300ms between words
    }

    echo "  Enriched POS: $enriched words\n";
    echo "  Added definitions: $defCount\n";
} else {
    echo "\n--- Step 4: Wiktionary enrichment SKIPPED ---\n";
}

// ── Summary ────────────────────────────────────────────────────────

echo "\n=== Seeding Complete ===\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_words WHERE lang_code = 'es'");
echo "Spanish words: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_words WHERE lang_code = 'es' AND part_of_speech IS NOT NULL");
echo "With POS: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_definitions WHERE lang_code = 'es'");
echo "Spanish definitions: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_definitions WHERE lang_code = 'en'");
echo "English definitions: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query(
    "SELECT cefr_level, COUNT(*) as c FROM dict_words
     WHERE lang_code = 'es' GROUP BY cefr_level ORDER BY cefr_level"
);
echo "\nBy CEFR level:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['cefr_level']}: {$row['c']}\n";
}
