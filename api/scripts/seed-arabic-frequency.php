<?php
/**
 * Arabic Frequency Rank Seeder
 *
 * Updates dict_words.frequency_rank for Arabic (ar) words using the
 * CAMeL Lab MSA (Modern Standard Arabic) frequency list.
 *
 * Source: https://github.com/CAMeL-Lab/Camel_Arabic_Frequency_Lists
 * File format: TSV — word<TAB>raw_frequency (sorted by frequency descending)
 * Line number = frequency rank (1 = most frequent).
 *
 * Usage:
 *   php seed-arabic-frequency.php                  # Update Arabic frequency_rank
 *   php seed-arabic-frequency.php --dry-run        # Preview only
 *   php seed-arabic-frequency.php --force          # Overwrite existing frequency_rank
 *   php seed-arabic-frequency.php --limit=50000    # Only process top N words
 */

require_once __DIR__ . '/../config/database.php';

$opts   = getopt('', ['dry-run', 'force', 'limit:']);
$dryRun = isset($opts['dry-run']);
$force  = isset($opts['force']);
$limit  = isset($opts['limit']) ? (int)$opts['limit'] : 0;

$freqFile = __DIR__ . '/data/frequency_ar_msa.tsv';

if (!file_exists($freqFile)) {
    echo "ERROR: Frequency file not found: {$freqFile}\n";
    echo "Download from: https://github.com/CAMeL-Lab/Camel_Arabic_Frequency_Lists/releases\n";
    exit(1);
}

echo "=== Arabic Frequency Rank Seeder ===\n";
echo "Source: CAMeL Lab MSA frequency list\n";
if ($dryRun) echo "** DRY RUN — no changes will be made **\n";
if ($force)  echo "** FORCE — overwriting existing frequency_rank **\n";
if ($limit)  echo "** LIMIT — processing top {$limit} words only **\n";
echo "\n";

$pdo = getDB();
$pdo->exec("SET innodb_lock_wait_timeout = 120");

// ── Count current state ──────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code = 'ar'");
$info = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Arabic words in DB: " . number_format($info['total']) . "\n";
echo "Already have frequency_rank: " . number_format($info['has_freq']) . "\n\n";

if ((int)$info['total'] === 0) {
    echo "No Arabic words in dict_words. Run kaikki import first.\n";
    exit(0);
}

// ── Load frequency list into memory (word → rank) ────────────────────
// The file is ~11.4M lines; we only need words that exist in our DB.
// Strategy: first load all Arabic words from DB into a lookup set,
// then scan the TSV and assign ranks only for words we have.

echo "Loading Arabic words from database...\n";
$dbWords = [];
$stmt = $pdo->query("SELECT id, word, frequency_rank FROM dict_words WHERE lang_code = 'ar'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $word = $row['word'];
    // Multiple rows can share the same word (different POS), collect all IDs
    if (!isset($dbWords[$word])) {
        $dbWords[$word] = [];
    }
    $dbWords[$word][] = [
        'id'   => (int)$row['id'],
        'freq' => $row['frequency_rank'],
    ];
}
$totalDbWords = count($dbWords);
echo "  Unique Arabic words in DB: " . number_format($totalDbWords) . "\n\n";

// ── Scan TSV and match ──────────────────────────────────────────────
echo "Scanning MSA frequency list...\n";
$fh = fopen($freqFile, 'r');
if (!$fh) {
    echo "ERROR: Cannot open {$freqFile}\n";
    exit(1);
}

$rank      = 0;
$matched   = 0;
$skipped   = 0;
$updates   = []; // id => rank

while (($line = fgets($fh)) !== false) {
    $rank++;

    if ($limit > 0 && $rank > $limit) break;

    $line = rtrim($line, "\r\n");
    if ($line === '') continue;

    $parts = explode("\t", $line);
    if (count($parts) < 2) continue;

    $word = $parts[0];

    if (!isset($dbWords[$word])) continue;

    foreach ($dbWords[$word] as $entry) {
        if (!$force && $entry['freq'] !== null) {
            $skipped++;
            continue;
        }
        $updates[$entry['id']] = $rank;
        $matched++;
    }

    // Progress every 1M lines
    if ($rank % 1000000 === 0) {
        echo "  Scanned " . number_format($rank) . " lines, matched {$matched} so far...\n";
    }
}
fclose($fh);

echo "  Scanned " . number_format($rank) . " total lines\n";
echo "  Matched: {$matched} word entries\n";
echo "  Skipped (already have rank): {$skipped}\n\n";

if ($matched === 0) {
    echo "Nothing to update.\n";
    exit(0);
}

// ── Apply updates ───────────────────────────────────────────────────
if ($dryRun) {
    // Show a sample
    echo "Sample updates (first 20):\n";
    $i = 0;
    $stmtSample = $pdo->prepare("SELECT word FROM dict_words WHERE id = ?");
    foreach ($updates as $id => $r) {
        $stmtSample->execute([$id]);
        $w = $stmtSample->fetchColumn();
        echo "  #{$r}: {$w} (id={$id})\n";
        if (++$i >= 20) break;
    }
    echo "\nDry run complete. {$matched} words would be updated.\n";
    exit(0);
}

echo "Updating frequency_rank in database...\n";
$updateStmt = $pdo->prepare("UPDATE dict_words SET frequency_rank = ? WHERE id = ?");

$batchSize  = 5000;
$count      = 0;

$pdo->beginTransaction();
foreach ($updates as $id => $r) {
    $updateStmt->execute([$r, $id]);
    $count++;

    if ($count % $batchSize === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        echo "  Updated {$count} / {$matched}...\r";
    }
}
$pdo->commit();
echo "  Updated {$count} / {$matched}       \n\n";

// ── Summary ─────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code = 'ar'");
$info = $stmt->fetch(PDO::FETCH_ASSOC);
echo "=== Final State ===\n";
echo "Arabic words in DB: " . number_format($info['total']) . "\n";
echo "With frequency_rank: " . number_format($info['has_freq']) . "\n";
$pct = $info['total'] > 0 ? round(100 * $info['has_freq'] / $info['total'], 1) : 0;
echo "Coverage: {$pct}%\n\n";

echo "Done. Now run: php assign-cefr-levels.php --lang=ar --force --freq-only\n";
echo "to assign CEFR levels based on the new frequency data.\n";
