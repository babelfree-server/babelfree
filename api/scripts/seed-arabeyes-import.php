<?php
/**
 * Arabeyes English-Arabic Dictionary Importer
 *
 * Imports the Arabeyes EN→AR dictionary from a SQL dump file.
 * Source: https://github.com/usefksa/engAraDictionaryFrom_ArabEyes
 *
 * For each entry:
 *   1. Ensures the English word exists in dict_words (lang_code='en')
 *   2. Ensures the Arabic word exists in dict_words (lang_code='ar')
 *   3. Creates a dict_translations link between them
 *   4. Adds the Arabic text as a definition on the English word (lang_code='ar')
 *
 * Usage:
 *   php seed-arabeyes-import.php                           # Import from default path
 *   php seed-arabeyes-import.php --file=/path/to/dump.sql  # Custom SQL file
 *   php seed-arabeyes-import.php --dry-run                 # Preview only
 */

if (function_exists('ob_implicit_flush')) ob_implicit_flush(true);
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

// ── CLI parsing ────────────────────────────────────────────────────────

$opts = getopt('', ['file:', 'dry-run', 'batch-size:']);
$dryRun    = isset($opts['dry-run']);
$batchSize = (int)($opts['batch-size'] ?? 500);
$sqlFile   = $opts['file'] ?? '/tmp/arabeyes-dict/engAraDictionary.sql';

if (!file_exists($sqlFile)) {
    echo "ERROR: SQL file not found: $sqlFile\n";
    echo "Clone the repo first:\n";
    echo "  git clone https://github.com/usefksa/engAraDictionaryFrom_ArabEyes.git /tmp/arabeyes-dict\n";
    exit(1);
}

echo "=== Arabeyes EN→AR Dictionary Importer ===\n";
echo "  File: $sqlFile\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n";
echo "  Batch size: $batchSize\n\n";

// ── Helpers ────────────────────────────────────────────────────────────

function normalizeWord(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    return $word;
}

// ── Parse SQL dump ─────────────────────────────────────────────────────

/**
 * Parse INSERT INTO statements from the SQL dump.
 * Yields [eng, ara] pairs.
 */
function parseSqlDump(string $file): Generator {
    $fh = fopen($file, 'r');
    if (!$fh) {
        throw new RuntimeException("Cannot open file: $file");
    }

    $inInsert = false;

    while (($line = fgets($fh)) !== false) {
        $trimmed = trim($line);

        // Detect start of INSERT block
        if (str_starts_with($trimmed, 'INSERT INTO')) {
            $inInsert = true;
        }

        // Extract tuples from any line within an INSERT block
        if ($inInsert) {
            yield from extractTuplesFromLine($trimmed);

            // End of INSERT statement
            if (str_ends_with($trimmed, ';')) {
                $inInsert = false;
            }
        }
    }

    fclose($fh);
}

/**
 * Extract (eng, ara) value tuples from a single line of an INSERT statement.
 * Parses character-by-character to handle escaped quotes correctly.
 */
function extractTuplesFromLine(string $line): Generator {
    // Match individual value tuples: (id, 'eng', 'ara')
    if (!preg_match_all("/\(\s*\d+\s*,\s*'((?:[^'\\\\]|\\\\.|'')*?)'\s*,\s*'((?:[^'\\\\]|\\\\.|'')*?)'\s*\)/", $line, $matches, PREG_SET_ORDER)) {
        return;
    }

    foreach ($matches as $m) {
        $eng = str_replace(["\\'" , "''", "\\\\"], ["'", "'", "\\"], $m[1]);
        $ara = str_replace(["\\'" , "''", "\\\\"], ["'", "'", "\\"], $m[2]);
        $eng = trim($eng);
        $ara = trim($ara);
        if ($eng !== '' && $ara !== '') {
            yield [$eng, $ara];
        }
    }
}

// ── Prepared statements ────────────────────────────────────────────────

$stmtFindWord = $pdo->prepare(
    'SELECT id FROM dict_words WHERE lang_code = ? AND word = ? LIMIT 1'
);

$stmtFindWordPos = $pdo->prepare(
    'SELECT id FROM dict_words WHERE lang_code = ? AND word = ? AND part_of_speech = ? LIMIT 1'
);

$stmtInsertWord = $pdo->prepare(
    'INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, pronunciation_ipa, cefr_level, frequency_rank, etymology)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmtCheckTranslation = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_translations WHERE source_word_id = ? AND target_word_id = ?'
);

$stmtInsertTranslation = $pdo->prepare(
    'INSERT INTO dict_translations (source_word_id, target_word_id, context) VALUES (?, ?, ?)'
);

$stmtCheckDef = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND definition = ?'
);

$stmtInsertDef = $pdo->prepare(
    'INSERT INTO dict_definitions (word_id, lang_code, definition, usage_note, source_id, sort_order)
     VALUES (?, ?, ?, ?, ?, ?)'
);

// ── Word cache ─────────────────────────────────────────────────────────
// Cache word lookups in memory to avoid repeated DB queries

$wordCache = []; // "lang:word" => id

function getOrCreateWord(string $langCode, string $word, bool $dryRun): ?int {
    global $pdo, $stmtFindWord, $stmtFindWordPos, $stmtInsertWord, $wordCache;

    $cacheKey = "$langCode:" . mb_strtolower($word, 'UTF-8');
    if (isset($wordCache[$cacheKey])) {
        return $wordCache[$cacheKey];
    }

    if ($dryRun) {
        // In dry-run mode, just track unique words without DB queries
        $wordCache[$cacheKey] = -1;
        return null;
    }

    // Try to find existing word (any POS)
    $stmtFindWord->execute([$langCode, $word]);
    $row = $stmtFindWord->fetch();
    if ($row) {
        $wordCache[$cacheKey] = (int)$row['id'];
        return (int)$row['id'];
    }

    // Insert new word with POS 'noun' as default (Arabeyes has no POS data)
    $normalized = normalizeWord($word);
    $stmtInsertWord->execute([
        $langCode,      // lang_code
        $word,          // word
        $normalized,    // word_normalized
        'noun',         // part_of_speech (default)
        null,           // gender
        null,           // pronunciation_ipa
        null,           // cefr_level
        null,           // frequency_rank
        null,           // etymology
    ]);

    $id = (int)$pdo->lastInsertId();
    $wordCache[$cacheKey] = $id;
    return $id;
}

// ── Main import loop ───────────────────────────────────────────────────

$stats = [
    'total_parsed'      => 0,
    'en_words_created'  => 0,
    'en_words_existing' => 0,
    'ar_words_created'  => 0,
    'ar_words_existing' => 0,
    'translations_created' => 0,
    'translations_existing' => 0,
    'definitions_created'  => 0,
    'definitions_existing' => 0,
    'errors'            => 0,
];

echo "Parsing SQL dump and importing...\n\n";
$startTime = microtime(true);

if (!$dryRun) {
    $pdo->beginTransaction();
}

$batchCount = 0;

foreach (parseSqlDump($sqlFile) as [$eng, $ara]) {
    $stats['total_parsed']++;

    try {
        // The Arabic field may contain multiple translations separated by ::
        // We split them and handle each separately
        $araVariants = array_map('trim', explode('::', $ara));

        // 1. Get or create English word
        $enWordId = getOrCreateWord('en', $eng, $dryRun);
        if ($enWordId === null && !$dryRun) {
            $stats['errors']++;
            continue;
        }

        // Track if the EN word was just created or already existed
        // (We can check cache state, but simpler to just count at the end)

        foreach ($araVariants as $arText) {
            $arText = trim($arText);
            if ($arText === '') continue;

            // 2. Get or create Arabic word
            $arWordId = getOrCreateWord('ar', $arText, $dryRun);

            // 3. Create translation link (EN → AR)
            if (!$dryRun && $enWordId && $arWordId) {
                $stmtCheckTranslation->execute([$enWordId, $arWordId]);
                if ((int)$stmtCheckTranslation->fetchColumn() === 0) {
                    $stmtInsertTranslation->execute([$enWordId, $arWordId, null]);
                    $stats['translations_created']++;
                } else {
                    $stats['translations_existing']++;
                }
            } elseif ($dryRun) {
                $stats['translations_created']++;
            }

            // 4. Add Arabic definition to the English word
            if (!$dryRun && $enWordId) {
                $stmtCheckDef->execute([$enWordId, $arText]);
                if ((int)$stmtCheckDef->fetchColumn() === 0) {
                    $stmtInsertDef->execute([
                        $enWordId,
                        'ar',               // definition in Arabic
                        $arText,
                        null,               // usage_note
                        'arabeyes:' . $eng, // source_id
                        0,                  // sort_order
                    ]);
                    $stats['definitions_created']++;
                } else {
                    $stats['definitions_existing']++;
                }
            } elseif ($dryRun) {
                $stats['definitions_created']++;
            }
        }

        $batchCount++;

        // Commit in batches for performance
        if (!$dryRun && $batchCount >= $batchSize) {
            $pdo->commit();
            $pdo->beginTransaction();
            $batchCount = 0;
        }

    } catch (Exception $e) {
        $stats['errors']++;
        if ($stats['errors'] <= 10) {
            echo "  ERROR on '$eng': " . $e->getMessage() . "\n";
        }
    }

    // Progress report every 1000 entries
    if ($stats['total_parsed'] % 1000 === 0) {
        $elapsed = microtime(true) - $startTime;
        $rate = $stats['total_parsed'] / max($elapsed, 0.001);
        printf("  [%d entries] %.1fs elapsed, %.0f entries/sec\n",
            $stats['total_parsed'], $elapsed, $rate);
    }
}

// Final commit
if (!$dryRun && $batchCount > 0) {
    $pdo->commit();
}

// ── Count created vs existing words from cache ─────────────────────────

// We can approximate by checking how many unique words we processed
// The cache tells us all words that were touched

$elapsed = microtime(true) - $startTime;

echo "\n=== Import Complete ===\n";
echo "  Total entries parsed: " . number_format($stats['total_parsed']) . "\n";
echo "  Translations created: " . number_format($stats['translations_created']) . "\n";
echo "  Translations skipped (existing): " . number_format($stats['translations_existing']) . "\n";
echo "  Definitions created: " . number_format($stats['definitions_created']) . "\n";
echo "  Definitions skipped (existing): " . number_format($stats['definitions_existing']) . "\n";
echo "  Errors: " . number_format($stats['errors']) . "\n";
printf("  Time: %.1f seconds\n", $elapsed);

if ($dryRun) {
    echo "\n  ** DRY RUN — no changes were made to the database **\n";
}

echo "\nDone.\n";
