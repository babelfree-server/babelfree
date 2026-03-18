<?php
/**
 * Universal Frequency Rank Seeder
 *
 * Updates dict_words.frequency_rank for any supported language using
 * Hermit Dave's FrequencyWords 50k lists.
 *
 * Source: https://github.com/hermitdave/FrequencyWords
 * File format: word<SPACE>count (one per line, line number = rank)
 *
 * Arabic uses a different format (CAMeL Lab TSV) — use seed-arabic-frequency.php instead.
 *
 * Usage:
 *   php seed-frequency-all.php --lang=es              # Seed Spanish
 *   php seed-frequency-all.php --lang=all             # Seed all supported languages
 *   php seed-frequency-all.php --lang=es --dry-run    # Preview only
 *   php seed-frequency-all.php --lang=es --force      # Overwrite existing ranks
 */

ini_set('memory_limit', '512M');

require_once __DIR__ . '/../config/database.php';

$opts   = getopt('', ['lang:', 'dry-run', 'force']);
$dryRun = isset($opts['dry-run']);
$force  = isset($opts['force']);
$langArg = $opts['lang'] ?? null;

if (!$langArg) {
    echo "Usage: php seed-frequency-all.php --lang=XX [--dry-run] [--force]\n";
    echo "  --lang=XX    Language code (es, en, fr, de, pt, it, zh, ja, ko, ru, nl) or 'all'\n";
    echo "  --dry-run    Preview changes without writing to DB\n";
    echo "  --force      Overwrite existing frequency_rank values\n";
    exit(1);
}

$supportedLangs = ['es', 'en', 'fr', 'de', 'pt', 'it', 'zh', 'ja', 'ko', 'ru', 'nl'];

if ($langArg === 'all') {
    $langsToProcess = $supportedLangs;
} else {
    if ($langArg === 'ar') {
        echo "Arabic uses a different frequency source (CAMeL Lab TSV).\n";
        echo "Please use: php seed-arabic-frequency.php\n";
        exit(0);
    }
    if (!in_array($langArg, $supportedLangs)) {
        echo "ERROR: Unsupported language '{$langArg}'.\n";
        echo "Supported: " . implode(', ', $supportedLangs) . "\n";
        exit(1);
    }
    $langsToProcess = [$langArg];
}

$pdo = getDB();
$pdo->exec("SET innodb_lock_wait_timeout = 600");
$pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");

echo "=== Universal Frequency Rank Seeder ===\n";
echo "Source: Hermit Dave FrequencyWords (50k)\n";
if ($dryRun) echo "** DRY RUN — no changes will be made **\n";
if ($force)  echo "** FORCE — overwriting existing frequency_rank **\n";
echo "\n";

$grandTotals = ['matched' => 0, 'skipped' => 0, 'languages' => 0];

/**
 * Bulk-update frequency_rank using row-by-row updates with retry on lock timeout.
 */
function bulkUpdateFrequency(PDO $pdo, string $lang, array $updates): int
{
    if (empty($updates)) return 0;

    $stmt = $pdo->prepare("UPDATE dict_words SET frequency_rank = ? WHERE id = ?");
    $count = 0;
    $total = count($updates);

    foreach ($updates as $id => $rank) {
        $maxRetries = 5;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $stmt->execute([$rank, $id]);
                $count++;
                break;
            } catch (PDOException $e) {
                if ($attempt < $maxRetries && strpos($e->getMessage(), '1205') !== false) {
                    if ($attempt === 1) {
                        echo "\n    Lock timeout at row {$count}/{$total}, retrying...\n";
                    }
                    sleep($attempt * 2);
                    continue;
                }
                throw $e;
            }
        }
        if ($count % 5000 === 0) {
            echo "    Updated {$count} / {$total}...\r";
        }
    }
    return $count;
}

foreach ($langsToProcess as $lang) {
    echo str_repeat('─', 60) . "\n";
    echo "Processing: {$lang}\n";
    echo str_repeat('─', 60) . "\n";

    $freqFile = __DIR__ . "/data/frequency_{$lang}_50k.txt";
    if (!file_exists($freqFile)) {
        echo "  WARNING: Frequency file not found: {$freqFile} — skipping.\n\n";
        continue;
    }

    // ── Count current state ──────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code = ?");
    $stmt->execute([$lang]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Words in DB: " . number_format($info['total']) . "\n";
    echo "  Already have frequency_rank: " . number_format($info['has_freq']) . "\n";

    if ((int)$info['total'] === 0) {
        echo "  No words for '{$lang}' in dict_words. Skipping.\n\n";
        continue;
    }

    // ── Read frequency file (only 50k lines — small) ────────────────
    echo "  Reading frequency file...\n";
    $freqWords = []; // word => rank
    $fh = fopen($freqFile, 'r');
    if (!$fh) {
        echo "  ERROR: Cannot open {$freqFile}\n\n";
        continue;
    }

    $rank = 0;
    while (($line = fgets($fh)) !== false) {
        $rank++;
        $line = rtrim($line, "\r\n");
        if ($line === '') continue;

        // Hermit Dave format: word<SPACE>count
        $spacePos = strrpos($line, ' ');
        if ($spacePos === false) continue;

        $word = substr($line, 0, $spacePos);
        $freqWords[$word] = $rank;
    }
    fclose($fh);
    echo "  Loaded " . number_format(count($freqWords)) . " frequency entries\n";

    // ── Query DB words in batches and match against frequency list ───
    echo "  Matching against database...\n";
    $matched = 0;
    $skipped = 0;
    $updates = []; // id => rank
    $matchedUniqueWords = 0;

    // Use unbuffered query to avoid loading all rows into PHP memory
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $stmt = $pdo->prepare("SELECT id, word, frequency_rank FROM dict_words WHERE lang_code = ?");
    $stmt->execute([$lang]);

    $seenWords = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $word = $row['word'];
        if (!isset($freqWords[$word])) continue;

        if (!$force && $row['frequency_rank'] !== null) {
            $skipped++;
            continue;
        }

        $updates[(int)$row['id']] = $freqWords[$word];
        $matched++;

        if (!isset($seenWords[$word])) {
            $seenWords[$word] = true;
            $matchedUniqueWords++;
        }
    }
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    unset($seenWords);

    echo "  Matched: {$matched} word entries ({$matchedUniqueWords} unique words)\n";
    echo "  Skipped (already have rank): {$skipped}\n";

    if ($matched === 0) {
        echo "  Nothing to update.\n\n";
        $freqWords = null;
        continue;
    }

    // ── Apply updates ────────────────────────────────────────────────
    if ($dryRun) {
        echo "  Sample updates (first 10):\n";
        $i = 0;
        // Sort by rank to show top words
        asort($updates);
        $stmtSample = $pdo->prepare("SELECT word FROM dict_words WHERE id = ?");
        foreach ($updates as $id => $r) {
            $stmtSample->execute([$id]);
            $w = $stmtSample->fetchColumn();
            echo "    #{$r}: {$w} (id={$id})\n";
            if (++$i >= 10) break;
        }
        echo "  Dry run: {$matched} words would be updated.\n\n";
    } else {
        echo "  Updating frequency_rank in database (bulk method)...\n";
        $affected = bulkUpdateFrequency($pdo, $lang, $updates);
        echo "    Updated {$affected} rows\n";
    }

    // ── Per-language summary ─────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code = ?");
    $stmt->execute([$lang]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    $pct = $info['total'] > 0 ? round(100 * $info['has_freq'] / $info['total'], 1) : 0;
    echo "  Final coverage: " . number_format($info['has_freq']) . " / " . number_format($info['total']) . " ({$pct}%)\n\n";

    $grandTotals['matched'] += $matched;
    $grandTotals['skipped'] += $skipped;
    $grandTotals['languages']++;

    // Free memory before next language
    $freqWords = null;
    $updates = null;
}

// ── Grand summary ────────────────────────────────────────────────────
echo str_repeat('═', 60) . "\n";
echo "=== SUMMARY ===\n";
echo "Languages processed: {$grandTotals['languages']}\n";
echo "Total word entries updated: " . number_format($grandTotals['matched']) . "\n";
echo "Total skipped (already ranked): " . number_format($grandTotals['skipped']) . "\n";
if (!$dryRun && $grandTotals['matched'] > 0) {
    echo "\nNext step: php assign-cefr-levels.php --lang=XX --force\n";
}
echo "Done.\n";
