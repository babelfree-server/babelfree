<?php
/**
 * Bulk CEFR Level Assigner
 *
 * Assigns CEFR levels to dict_words based on:
 * 1. Frequency rank (if available)
 * 2. Part of speech heuristics
 * 3. Word complexity (length, syllables)
 *
 * Usage:
 *   php assign-cefr-levels.php --lang=es              # Assign for Spanish
 *   php assign-cefr-levels.php --lang=all              # All languages
 *   php assign-cefr-levels.php --lang=es --force        # Overwrite existing levels
 *   php assign-cefr-levels.php --lang=es --dry-run      # Preview only
 *   php assign-cefr-levels.php --lang=es --freq-only    # Only words with frequency_rank
 */

require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pdo->exec("SET innodb_lock_wait_timeout = 120");

// ── Parse CLI args ──────────────────────────────────────────────────
$opts = getopt('', ['lang:', 'force', 'dry-run', 'freq-only']);
$lang = $opts['lang'] ?? null;
$force = isset($opts['force']);
$dryRun = isset($opts['dry-run']);
$freqOnly = isset($opts['freq-only']);

if (!$lang) {
    echo "Usage: php assign-cefr-levels.php --lang=es [--force] [--dry-run] [--freq-only]\n";
    exit(1);
}

$languages = ($lang === 'all')
    ? array_column($pdo->query("SELECT DISTINCT lang_code FROM dict_words")->fetchAll(PDO::FETCH_ASSOC), 'lang_code')
    : [$lang];

// ── CEFR frequency thresholds ───────────────────────────────────────
// Based on standard frequency-to-CEFR mappings:
// A1: most frequent 500 words (core survival vocabulary)
// A2: 501-1500 (everyday situations)
// B1: 1501-3500 (independent user, familiar topics)
// B2: 3501-6000 (complex texts, abstract topics)
// C1: 6001-10000 (wide range, implicit meaning)
// C2: 10001+ (near-native, specialized/rare)
function cefrFromFrequency(int $rank): string {
    if ($rank <= 500) return 'A1';
    if ($rank <= 1500) return 'A2';
    if ($rank <= 3500) return 'B1';
    if ($rank <= 6000) return 'B2';
    if ($rank <= 10000) return 'C1';
    return 'C2';
}

// ── Heuristic CEFR assignment for words WITHOUT frequency data ──────
// Uses word length, part of speech, and character analysis
function cefrFromHeuristics(string $word, ?string $pos, string $lang): string {
    $len = mb_strlen($word);
    $hasSpaces = str_contains($word, ' ');

    // Multi-word phrases → B2+
    if ($hasSpaces) {
        $wordCount = substr_count($word, ' ') + 1;
        if ($wordCount >= 4) return 'C2';
        if ($wordCount >= 3) return 'C1';
        return 'B2';
    }

    // Very short common words tend to be basic
    if ($len <= 3) return 'A2';
    if ($len <= 5) return 'B1';

    // Part of speech heuristics
    if ($pos === 'interjection') return 'A2';
    if ($pos === 'article') return 'A1';
    if ($pos === 'pronoun') return 'A1';
    if ($pos === 'preposition') return 'A2';
    if ($pos === 'conjunction') return 'B1';

    // Longer words tend to be more advanced
    if ($len <= 7) return 'B1';
    if ($len <= 10) return 'B2';
    if ($len <= 13) return 'C1';
    return 'C2';
}

// ── Process each language ───────────────────────────────────────────
foreach ($languages as $langCode) {
    echo "\n=== Processing: {$langCode} ===\n";

    // Count current state
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(cefr_level IS NOT NULL) as leveled, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code = ?");
    $stmt->execute([$langCode]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Total words: " . number_format($info['total']) . "\n";
    echo "  Already leveled: " . number_format($info['leveled']) . "\n";
    echo "  Have frequency: " . number_format($info['has_freq']) . "\n";

    $updated = 0;
    $skipped = 0;

    // ── Phase 1: Frequency-based assignment ─────────────────────────
    $where = "lang_code = ? AND frequency_rank IS NOT NULL";
    if (!$force) $where .= " AND cefr_level IS NULL";

    $stmt = $pdo->prepare("SELECT id, word, frequency_rank, part_of_speech FROM dict_words WHERE {$where}");
    $stmt->execute([$langCode]);

    $updateStmt = $pdo->prepare("UPDATE dict_words SET cefr_level = ? WHERE id = ?");

    if (!$dryRun) $pdo->beginTransaction();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $level = cefrFromFrequency((int)$row['frequency_rank']);
        if (!$dryRun) {
            $updateStmt->execute([$level, $row['id']]);
        }
        $updated++;
    }
    if (!$dryRun) $pdo->commit();
    echo "  Phase 1 (frequency): {$updated} words assigned\n";

    if ($freqOnly) {
        echo "  --freq-only: skipping heuristic phase\n";
        continue;
    }

    // ── Phase 2: Heuristic assignment for remaining words ───────────
    $where2 = "lang_code = ? AND frequency_rank IS NULL";
    if (!$force) $where2 .= " AND cefr_level IS NULL";

    $stmt = $pdo->prepare("SELECT id, word, part_of_speech FROM dict_words WHERE {$where2}");
    $stmt->execute([$langCode]);

    $heuristicCount = 0;
    $batchSize = 5000;
    $batch = [];

    if (!$dryRun) $pdo->beginTransaction();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $level = cefrFromHeuristics($row['word'], $row['part_of_speech'], $langCode);
        if (!$dryRun) {
            for ($retry = 0; $retry < 5; $retry++) {
                try {
                    $updateStmt->execute([$level, $row['id']]);
                    break;
                } catch (PDOException $e) {
                    if ($retry === 4) throw $e;
                    usleep(1000000 * ($retry + 1)); // 1s, 2s, 3s, 4s backoff
                }
            }
        }
        $heuristicCount++;

        if ($heuristicCount % $batchSize === 0) {
            if (!$dryRun) {
                $pdo->commit();
                $pdo->beginTransaction();
            }
            echo "  Phase 2 (heuristic): {$heuristicCount} words...\r";
        }
    }
    if (!$dryRun) $pdo->commit();
    echo "  Phase 2 (heuristic): {$heuristicCount} words assigned\n";

    // ── Summary ─────────────────────────────────────────────────────
    $totalAssigned = $updated + $heuristicCount;
    echo "  TOTAL assigned: {$totalAssigned} words\n";

    // Show new distribution
    if (!$dryRun) {
        $stmt = $pdo->prepare("SELECT cefr_level, COUNT(*) as c FROM dict_words WHERE lang_code = ? AND cefr_level IS NOT NULL GROUP BY cefr_level ORDER BY FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')");
        $stmt->execute([$langCode]);
        echo "  Distribution:\n";
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "    {$r['cefr_level']}: " . number_format($r['c']) . "\n";
        }
    }
}

echo "\nDone.\n";
