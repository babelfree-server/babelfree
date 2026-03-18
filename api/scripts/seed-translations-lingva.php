<?php
/**
 * Lingva Translate Pipeline — Phase B languages via Lingva (Google Translate frontend)
 * No API key, no daily quota. ~200ms per request.
 *
 * Usage:
 *   php seed-translations-lingva.php --all          # All Phase B languages
 *   php seed-translations-lingva.php --lang=zh      # Single language
 *   php seed-translations-lingva.php --all --dry-run
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// ── CLI options ──────────────────────────────────────────────────────

$targetLangs = [];
$dryRun      = in_array('--dry-run', $argv ?? []);
$batchSize   = 500;
$allLangs    = in_array('--all', $argv ?? []);

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--lang=') === 0) $targetLangs[] = substr($arg, 7);
    if (strpos($arg, '--batch=') === 0) $batchSize = (int) substr($arg, 8);
}

$supported = ['zh', 'ja', 'ko', 'ru', 'ar', 'nl'];

if ($allLangs) {
    $targetLangs = $supported;
} elseif (empty($targetLangs)) {
    echo "Usage: php seed-translations-lingva.php --all | --lang=XX [--dry-run] [--batch=N]\n";
    echo "  Supported: " . implode(', ', $supported) . "\n";
    exit(1);
}

echo "=== Lingva Translation Pipeline (Phase B) ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n\n";

// ── Helpers ──────────────────────────────────────────────────────────

function normalize(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    return trim($word);
}

function ensureWordEntry(PDO $pdo, string $word, string $langCode,
                         ?string $cefrLevel = null, ?string $pos = null,
                         ?string $gender = null): ?int {
    static $insertStmt = null;
    static $findStmt   = null;

    if (!$insertStmt) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO dict_words (lang_code, word, word_normalized, cefr_level, part_of_speech, gender)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $findStmt = $pdo->prepare(
            'SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1'
        );
    }

    $norm = normalize($word);
    if (!$norm) return null;
    if (!$pos) $pos = 'noun';

    $insertStmt->execute([$langCode, $word, $norm, $cefrLevel, $pos, $gender]);
    $id = $pdo->lastInsertId();

    if (!$id) {
        $findStmt->execute([$langCode, $norm]);
        $row = $findStmt->fetch();
        $id = $row ? (int) $row['id'] : null;
    }

    return $id ? (int) $id : null;
}

function ensureTranslation(PDO $pdo, int $sourceId, int $targetId): void {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)'
        );
    }
    $stmt->execute([$sourceId, $targetId]);
    $stmt->execute([$targetId, $sourceId]);
}

function translationExists(PDO $pdo, int $esWordId, string $targetLang): bool {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM dict_translations dt
             JOIN dict_words tw ON tw.id = dt.target_word_id
             WHERE dt.source_word_id = ? AND tw.lang_code = ?
             LIMIT 1'
        );
    }
    $stmt->execute([$esWordId, $targetLang]);
    return (bool) $stmt->fetchColumn();
}

function lingvaTranslate(string $word, string $targetLang): ?string {
    static $hosts = [
        'lingva.ml',
        'translate.plausibility.cloud',
    ];
    static $hostIndex = 0;

    // Rotate hosts to spread load
    $host = $hosts[$hostIndex % count($hosts)];
    $hostIndex++;

    $url = "https://$host/api/v1/es/" . urlencode($targetLang) . '/' . urlencode($word);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) {
        // Try the other host on failure
        $host = $hosts[$hostIndex % count($hosts)];
        $hostIndex++;
        $url = "https://$host/api/v1/es/" . urlencode($targetLang) . '/' . urlencode($word);
        $json = @file_get_contents($url, false, $ctx);
        if (!$json) return null;
    }

    $data = json_decode($json, true);
    $translated = $data['translation'] ?? null;
    if (!$translated) return null;

    $translated = trim($translated);
    if (mb_strtolower($translated) === mb_strtolower($word)) return null;
    if (!$translated || mb_strlen($translated) > 100) return null;

    return $translated;
}

// ── Load all Spanish words ───────────────────────────────────────────

$stmt = $pdo->query(
    "SELECT id, word, word_normalized, cefr_level, part_of_speech, gender
     FROM dict_words
     WHERE lang_code = 'es'
     ORDER BY frequency_rank ASC, id ASC"
);
$spanishWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalES = count($spanishWords);
echo "Spanish source words: $totalES\n\n";

// ── Process each language ────────────────────────────────────────────

$stats = [];
foreach ($targetLangs as $lang) {
    $stats[$lang] = ['new' => 0, 'skipped' => 0, 'failed' => 0];
}

foreach ($targetLangs as $lang) {
    echo "--- Processing: $lang ---\n";
    $startTime = time();
    $processed = 0;

    foreach ($spanishWords as $sw) {
        $processed++;

        if ($processed % $batchSize === 0) {
            $elapsed = time() - $startTime;
            $rate = $processed / max($elapsed, 1);
            $remaining = ($totalES - $processed) / max($rate, 0.1);
            echo "  [$processed/$totalES] new={$stats[$lang]['new']}, skip={$stats[$lang]['skipped']}, "
               . "fail={$stats[$lang]['failed']}, " . round($rate, 1) . "/sec, ~"
               . round($remaining / 60, 1) . "min left\n";
        }

        // Skip if already has translation
        if (translationExists($pdo, (int) $sw['id'], $lang)) {
            $stats[$lang]['skipped']++;
            continue;
        }

        $translated = lingvaTranslate($sw['word'], $lang);
        if (!$translated) {
            $stats[$lang]['failed']++;
            usleep(100000); // 100ms on fail
            continue;
        }

        if ($dryRun) {
            echo "  [dry-run] {$sw['word']} → $lang: $translated\n";
            $stats[$lang]['new']++;
            continue;
        }

        $targetId = ensureWordEntry($pdo, $translated, $lang, $sw['cefr_level'], $sw['part_of_speech']);
        if ($targetId) {
            ensureTranslation($pdo, (int) $sw['id'], $targetId);
            $stats[$lang]['new']++;
        }

        // Courtesy pause — spread load across hosts
        usleep(150000);
    }

    $elapsed = time() - $startTime;
    echo "  $lang done in " . round($elapsed / 60, 1) . " min: "
       . "{$stats[$lang]['new']} new, {$stats[$lang]['skipped']} skipped, {$stats[$lang]['failed']} failed\n\n";
}

// ── Final Summary ────────────────────────────────────────────────────

echo "=== Lingva Pipeline Summary ===\n\n";
echo str_pad('Lang', 6) . str_pad('New', 8) . str_pad('Skipped', 10) . str_pad('Failed', 10) . "\n";
echo str_repeat('-', 34) . "\n";

foreach ($targetLangs as $lang) {
    $s = $stats[$lang];
    echo str_pad($lang, 6) . str_pad($s['new'], 8) . str_pad($s['skipped'], 10) . str_pad($s['failed'], 10) . "\n";
}

echo "\n--- Current DB Word Counts ---\n";
$stmt = $pdo->query(
    "SELECT lang_code, COUNT(*) as c FROM dict_words
     WHERE lang_code IN ('es','en','fr','de','pt','it','zh','ja','ko','ru','ar','nl')
     GROUP BY lang_code ORDER BY c DESC"
);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['lang_code']}: " . number_format($row['c']) . " words\n";
}

echo "\n--- Translation Links ---\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM dict_translations");
echo "  Total: " . number_format($stmt->fetchColumn()) . "\n";

echo "\n=== Pipeline Complete ===\n";
