<?php
/**
 * Seed related words (synonyms, antonyms, derived) from es.wiktionary.
 *
 * Fetches es.wiktionary wikitext for each Spanish word and parses:
 *   - === Sinónimos ===
 *   - === Antónimos ===
 *   - === Derivados ===  (mapped to 'derived')
 *   - === Véase también === (mapped to 'see_also')
 *
 * Extracted words are matched against existing dict_words entries.
 * Bidirectional links are created (A synonym of B → B synonym of A).
 *
 * Resumable: skips words that already have related entries.
 * Rate-limited: ~500ms between API calls.
 *
 * Usage:
 *   php seed-related-words.php                  # Process up to 200 words
 *   php seed-related-words.php --batch=500      # Process 500 words
 *   php seed-related-words.php --all            # Process all words
 *   php seed-related-words.php --dry-run        # Show what would be inserted
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// CLI options
$batchSize = 200;
$dryRun = in_array('--dry-run', $argv ?? []);
$processAll = false;

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--batch=') === 0) {
        $batchSize = (int)substr($arg, 8);
    } elseif ($arg === '--all') {
        $processAll = true;
        $batchSize = 999999;
    }
}

echo "=== Related Words Seeder (es.wiktionary) ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n";
echo "  Batch: " . ($processAll ? 'ALL' : $batchSize) . "\n\n";

// ── HTTP helper ─────────────────────────────────────────────────────

function httpGet(string $url): ?string {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: YaguaraDictBot/1.0 (babelfree.com; related words seeder)\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if (!$response) return null;

    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/^HTTP\/\S+\s+(4|5)\d\d/', $h)) return null;
        }
    }

    return $response;
}

// ── Wikitext section parser ─────────────────────────────────────────

/**
 * Parse wikitext for synonym/antonym/derived/see_also sections.
 * Returns: ['synonym' => ['word1', 'word2'], 'antonym' => [...], ...]
 */
function parseRelatedSections(string $wikitext): array {
    $sectionMap = [
        'sinónimos'      => 'synonym',
        'sinonimos'      => 'synonym',
        'antónimos'      => 'antonym',
        'antonimos'      => 'antonym',
        'derivados'      => 'derived',
        'compuestos'     => 'derived',
        'véase también'  => 'see_also',
        'vease también'  => 'see_also',
        'véase tambien'  => 'see_also',
        'vease tambien'  => 'see_also',
        'relacionados'   => 'see_also',
    ];

    $results = [
        'synonym' => [],
        'antonym' => [],
        'derived' => [],
        'see_also' => [],
    ];

    // Split wikitext by section headers (=== Header ===)
    $sections = preg_split('/^(={2,4})\s*([^=]+?)\s*\1\s*$/mu', $wikitext, -1, PREG_SPLIT_DELIM_CAPTURE);

    $currentType = null;
    for ($i = 0; $i < count($sections); $i++) {
        // Check if this is a section header
        if (preg_match('/^={2,4}$/', $sections[$i] ?? '')) {
            $headerText = mb_strtolower(trim($sections[$i + 1] ?? ''));
            $currentType = $sectionMap[$headerText] ?? null;
            $i++; // Skip the header text
            continue;
        }

        if ($currentType && isset($results[$currentType])) {
            $block = $sections[$i];

            // Extract wiki links: [[word]], [[word|display]]
            if (preg_match_all('/\[\[([^\]|]+)(?:\|[^\]]+)?\]\]/', $block, $matches)) {
                foreach ($matches[1] as $linked) {
                    $linked = trim($linked);
                    // Skip links to other namespaces (Wikcionario:, Categoría:, etc.)
                    if (strpos($linked, ':') !== false) continue;
                    // Skip links with fragments (#)
                    if (strpos($linked, '#') !== false) continue;
                    // Clean
                    $linked = mb_strtolower($linked, 'UTF-8');
                    if (mb_strlen($linked) >= 2 && mb_strlen($linked) <= 100) {
                        $results[$currentType][] = $linked;
                    }
                }
            }

            // Stop parsing after we hit the next section of different type
            // (avoid bleeding into unrelated sections)
        }
    }

    // Deduplicate
    foreach ($results as $type => $words) {
        $results[$type] = array_values(array_unique($words));
    }

    return $results;
}

// ── Normalize helper ────────────────────────────────────────────────

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

// ── Find words to process ───────────────────────────────────────────

// Words that DON'T yet have any related entries
$sql = "
    SELECT w.id, w.word
    FROM dict_words w
    LEFT JOIN dict_related_words r ON r.word_id = w.id
    WHERE w.lang_code = 'es'
      AND r.id IS NULL
    ORDER BY w.frequency_rank ASC, w.id ASC
    LIMIT ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$batchSize]);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Words to process: " . count($words) . "\n\n";

if (empty($words)) {
    echo "Nothing to do. All words already have related entries.\n";
    exit(0);
}

// ── Preload word_normalized → id map for matching ───────────────────

$wordMap = []; // normalized => word_id
$stmt = $pdo->query("SELECT id, word_normalized FROM dict_words WHERE lang_code = 'es'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $wordMap[$row['word_normalized']] = (int)$row['id'];
}
echo "Loaded " . count($wordMap) . " Spanish words for matching.\n\n";

// ── Prepared statements ─────────────────────────────────────────────

// Uses INSERT IGNORE since we have a UNIQUE constraint (word_id, related_word_id, relation_type)
$stmtInsert = $pdo->prepare(
    "INSERT IGNORE INTO dict_related_words (word_id, related_word_id, relation_type) VALUES (?, ?, ?)"
);

// ── Process ─────────────────────────────────────────────────────────

$stats = [
    'processed' => 0,
    'with_relations' => 0,
    'links_inserted' => 0,
    'not_in_wiktionary' => 0,
    'no_sections' => 0,
];

$startTime = time();

foreach ($words as $i => $row) {
    $wordId = (int)$row['id'];
    $word = $row['word'];
    $num = $i + 1;

    // Progress every 25 words
    if ($num % 25 === 0 || $num === 1) {
        $elapsed = time() - $startTime;
        $rate = $elapsed > 0 ? round($num / $elapsed, 1) : 0;
        echo "  [$num/" . count($words) . "] Processing '$word' ({$rate}/s)\n";
    }

    // Fetch es.wiktionary wikitext
    $url = "https://es.wiktionary.org/w/api.php?"
         . http_build_query([
             'action' => 'parse',
             'page'   => $word,
             'prop'   => 'wikitext',
             'format' => 'json',
         ]);

    $json = httpGet($url);
    usleep(500000); // 500ms rate limit

    if (!$json) {
        $stats['not_in_wiktionary']++;
        continue;
    }

    $data = json_decode($json, true);
    $wikitext = $data['parse']['wikitext']['*'] ?? null;
    if (!$wikitext) {
        $stats['not_in_wiktionary']++;
        continue;
    }

    // Parse related sections
    $related = parseRelatedSections($wikitext);

    $totalForWord = 0;
    foreach ($related as $type => $relatedWords) {
        foreach ($relatedWords as $rWord) {
            $rNorm = normalize($rWord);
            if (!isset($wordMap[$rNorm])) continue;

            $relatedWordId = $wordMap[$rNorm];
            if ($relatedWordId === $wordId) continue; // Don't link to self

            if ($dryRun) {
                echo "    $word → [$type] $rWord\n";
                $totalForWord++;
                continue;
            }

            // Insert bidirectional links
            $stmtInsert->execute([$wordId, $relatedWordId, $type]);
            $stmtInsert->execute([$relatedWordId, $wordId, $type]);
            $totalForWord += 2;
        }
    }

    if ($totalForWord > 0) {
        $stats['with_relations']++;
        $stats['links_inserted'] += $totalForWord;
    } else {
        $stats['no_sections']++;
    }

    $stats['processed']++;
}

// ── Summary ─────────────────────────────────────────────────────────

$elapsed = time() - $startTime;
$minutes = round($elapsed / 60, 1);

echo "\n=== Related Words Seeder Complete ({$minutes} min) ===\n";
echo "  Words processed:           {$stats['processed']}\n";
echo "  Words with relations:      {$stats['with_relations']}\n";
echo "  Relation links inserted:   {$stats['links_inserted']}\n";
echo "  Not in Wiktionary:         {$stats['not_in_wiktionary']}\n";
echo "  No related sections found: {$stats['no_sections']}\n\n";

if (!$dryRun) {
    $totalLinks = $pdo->query("SELECT COUNT(*) FROM dict_related_words")->fetchColumn();
    $byType = $pdo->query("
        SELECT relation_type, COUNT(*) as c
        FROM dict_related_words
        GROUP BY relation_type
        ORDER BY c DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "--- DB Status ---\n";
    echo "  Total related word links: $totalLinks\n";
    foreach ($byType as $r) {
        echo "    {$r['relation_type']}: {$r['c']}\n";
    }

    $wordsWithRelations = $pdo->query("
        SELECT COUNT(DISTINCT word_id) FROM dict_related_words
    ")->fetchColumn();
    $totalWords = $pdo->query("SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es'")->fetchColumn();
    $pct = $totalWords > 0 ? round($wordsWithRelations / $totalWords * 100, 1) : 0;
    echo "  Words with relations: $wordsWithRelations / $totalWords ({$pct}%)\n";
}

echo "\nDone.\n";
