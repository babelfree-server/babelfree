#!/usr/bin/env php
<?php
/**
 * Extract cross-language equivalents from English Wiktionary dump.
 *
 * The main kaikki-dict-English.jsonl contains English word entries with
 * translations to many languages. This script:
 *   1. Reads the English dump line by line
 *   2. For each sense with translations, finds matching words in our DB
 *   3. Creates bidirectional dict_translations links
 *
 * Usage:
 *   php extract-translations.php                    # Process all
 *   php extract-translations.php --dry-run          # Preview only
 *   php extract-translations.php --limit=50000      # Process N lines
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pdo->exec("SET innodb_lock_wait_timeout = 300");

$opts = getopt('', ['dry-run', 'limit:']);
$dryRun = isset($opts['dry-run']);
$limit = isset($opts['limit']) ? (int)$opts['limit'] : 0;

// All supported language codes
$supportedLangs = [];
$rows = $pdo->query("SELECT code FROM dict_languages")->fetchAll(PDO::FETCH_COLUMN);
foreach ($rows as $code) $supportedLangs[$code] = true;

// Build a word lookup cache (lang_code + word → id) loaded on demand
$wordCache = [];

function findWordId(PDO $pdo, string $langCode, string $word, array &$cache): ?int {
    $key = "$langCode:$word";
    if (array_key_exists($key, $cache)) return $cache[$key];

    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare('SELECT id FROM dict_words WHERE lang_code = ? AND word = ? LIMIT 1');
    }
    $stmt->execute([$langCode, $word]);
    $id = $stmt->fetchColumn();
    $cache[$key] = $id ?: null;
    return $cache[$key];
}

$stmtCheckTrans = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_translations WHERE source_word_id = ? AND target_word_id = ?'
);
$stmtInsertTrans = $pdo->prepare(
    'INSERT INTO dict_translations (source_word_id, target_word_id, context) VALUES (?, ?, ?)'
);

// Find the English dump
$enFile = __DIR__ . '/data/kaikki/kaikki-dict-English.jsonl';
if (!file_exists($enFile)) {
    echo "ERROR: English Wiktionary dump not found at $enFile\n";
    echo "Download from: https://kaikki.org/dictionary/English/kaikki.org-dictionary-English.jsonl\n";
    exit(1);
}

$fileSize = filesize($enFile);
echo "=== Cross-Language Equivalents Extraction ===\n";
echo "  Source: $enFile (" . round($fileSize / 1024 / 1024) . " MB)\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n";
echo "  Limit: " . ($limit ?: 'none') . "\n\n";

$fh = fopen($enFile, 'r');
if (!$fh) { echo "ERROR: Cannot open file\n"; exit(1); }

$stats = ['lines' => 0, 'entries_with_trans' => 0, 'new_links' => 0, 'skipped_existing' => 0, 'skipped_no_word' => 0];
$startTime = time();
$batchCount = 0;
$pdo->beginTransaction();

while (($line = fgets($fh)) !== false) {
    $stats['lines']++;
    if ($limit && $stats['lines'] > $limit) break;

    $entry = json_decode($line, true);
    if (!$entry || empty($entry['word'])) continue;

    $enWord = $entry['word'];
    $enLang = $entry['lang_code'] ?? 'en';

    // We want entries in ANY of our supported languages (not just English)
    // But translations are only on English-language entries
    if ($enLang !== 'en') continue;

    // Find the English word in our DB
    $enWordId = findWordId($pdo, 'en', $enWord, $wordCache);

    $hasTranslations = false;

    foreach ($entry['senses'] ?? [] as $sense) {
        foreach ($sense['translations'] ?? [] as $tr) {
            $trLang = $tr['lang_code'] ?? $tr['code'] ?? '';
            $trWord = $tr['word'] ?? '';

            if (empty($trLang) || empty($trWord) || mb_strlen($trWord) > 100) continue;
            if (!isset($supportedLangs[$trLang])) continue;

            $hasTranslations = true;

            // Find the target word in our DB
            $targetId = findWordId($pdo, $trLang, $trWord, $wordCache);
            if (!$targetId) {
                $stats['skipped_no_word']++;
                continue;
            }

            if ($dryRun) {
                $stats['new_links']++;
                continue;
            }

            // Link EN → target language
            if ($enWordId) {
                $stmtCheckTrans->execute([$enWordId, $targetId]);
                if ((int)$stmtCheckTrans->fetchColumn() === 0) {
                    $context = $tr['sense'] ?? $tr['english'] ?? null;
                    if ($context && mb_strlen($context) > 200) $context = mb_substr($context, 0, 200);
                    try {
                        $stmtInsertTrans->execute([$enWordId, $targetId, $context]);
                        $stats['new_links']++;
                    } catch (\PDOException $e) { /* duplicate */ }
                } else {
                    $stats['skipped_existing']++;
                }

                // Also link target → EN (bidirectional)
                $stmtCheckTrans->execute([$targetId, $enWordId]);
                if ((int)$stmtCheckTrans->fetchColumn() === 0) {
                    try {
                        $stmtInsertTrans->execute([$targetId, $enWordId, null]);
                        $stats['new_links']++;
                    } catch (\PDOException $e) { /* duplicate */ }
                }
            }
        }
    }

    if ($hasTranslations) $stats['entries_with_trans']++;

    $batchCount++;
    if ($batchCount >= 1000) {
        if ($pdo->inTransaction()) $pdo->commit();
        $pdo->beginTransaction();
        $batchCount = 0;

        // Clear cache periodically to avoid memory bloat
        if (count($wordCache) > 500000) {
            $wordCache = [];
        }
    }

    // Progress every 50000 lines
    if ($stats['lines'] % 50000 === 0) {
        $elapsed = time() - $startTime;
        $rate = $elapsed > 0 ? round($stats['lines'] / $elapsed) : 0;
        $pct = round(($stats['lines'] / 6000000) * 100, 1); // ~6M lines in EN dump
        echo sprintf(
            "  %s lines (%s%%) — %s entries w/trans — +%s links — %d/s\n",
            number_format($stats['lines']), $pct,
            number_format($stats['entries_with_trans']),
            number_format($stats['new_links']),
            $rate
        );
        fflush(STDOUT);
    }
}

if ($pdo->inTransaction()) $pdo->commit();
fclose($fh);

$elapsed = time() - $startTime;
$minutes = round($elapsed / 60, 1);

echo "\n=== Complete ({$minutes} min) ===\n";
echo "  Lines processed: " . number_format($stats['lines']) . "\n";
echo "  Entries with translations: " . number_format($stats['entries_with_trans']) . "\n";
echo "  New equivalents created: " . number_format($stats['new_links']) . "\n";
echo "  Skipped (already exist): " . number_format($stats['skipped_existing']) . "\n";
echo "  Skipped (word not in DB): " . number_format($stats['skipped_no_word']) . "\n";
echo "Done.\n";
