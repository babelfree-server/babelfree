<?php
/**
 * Build English dictionary entries from curated Spanish word data.
 *
 * Uses the `meaning` field from dict_entries_{level}.json files to:
 *   1. Create English word entries in dict_words
 *   2. Create bidirectional translation links in dict_translations
 *   3. Copy English definitions from Spanish word_id to English word_id
 *
 * No external API needed — all data comes from the curated JSON files.
 *
 * Run:
 *   php seed-english-from-curated.php
 *   php seed-english-from-curated.php --dry-run
 */

require_once __DIR__ . '/../config/database.php';

$dryRun = in_array('--dry-run', $argv ?? []);
$pdo = getDB();

echo "=== English Dictionary Builder (from curated data) ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n\n";

// ── Helpers ─────────────────────────────────────────────────────────

function normalize(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    $word = preg_replace('/[áàâã]/u', 'a', $word);
    $word = preg_replace('/[éèê]/u', 'e', $word);
    $word = preg_replace('/[íìî]/u', 'i', $word);
    $word = preg_replace('/[óòôõ]/u', 'o', $word);
    $word = preg_replace('/[úùû]/u', 'u', $word);
    $word = str_replace(['ñ', 'ç', 'ß'], ['n', 'c', 'ss'], $word);
    return trim($word);
}

// ── Load curated Spanish words with meanings ────────────────────────

$dataDir = __DIR__ . '/data/';
$levels = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
$curatedWords = [];

foreach ($levels as $level) {
    $file = $dataDir . "dict_entries_{$level}.json";
    if (!file_exists($file)) continue;
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    foreach ($data as $item) {
        if (!empty($item['meaning'])) {
            $curatedWords[] = [
                'word' => $item['word'],
                'meaning' => $item['meaning'],
                'pos' => $item['pos'] ?? null,
                'gender' => $item['gender'] ?? null,
                'ipa' => $item['ipa'] ?? null,
                'def' => $item['def'] ?? null,
                'level' => strtoupper($level),
            ];
        }
    }
}

echo "Curated Spanish words with English meanings: " . count($curatedWords) . "\n\n";

if (empty($curatedWords)) {
    echo "No curated words found with meanings.\n";
    exit(1);
}

// ── Find Spanish word IDs in database ───────────────────────────────

$stmtFindES = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = 'es' AND word_normalized = ? LIMIT 1"
);

$stmtFindEN = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = 'en' AND word_normalized = ? LIMIT 1"
);

$stmtInsertWord = $pdo->prepare("
    INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, cefr_level)
    VALUES ('en', ?, ?, ?, ?, ?)
");

$stmtInsertLink = $pdo->prepare(
    "INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)"
);

$stmtCheckDef = $pdo->prepare(
    "SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND lang_code = 'en'"
);

$stmtGetDefs = $pdo->prepare(
    "SELECT definition, usage_note, sort_order FROM dict_definitions WHERE word_id = ? AND lang_code = 'en' ORDER BY sort_order"
);

$stmtInsertDef = $pdo->prepare(
    "INSERT INTO dict_definitions (word_id, lang_code, definition, usage_note, sort_order) VALUES (?, 'en', ?, ?, ?)"
);

// ── Process ─────────────────────────────────────────────────────────

$stats = [
    'en_words_created' => 0,
    'en_words_existed' => 0,
    'links_created' => 0,
    'links_existed' => 0,
    'defs_copied' => 0,
    'defs_created' => 0,
    'es_not_found' => 0,
    'multi_meanings' => 0,
];

if (!$dryRun) {
    $pdo->beginTransaction();
}

try {
    foreach ($curatedWords as $cw) {
        $esNorm = normalize($cw['word']);
        $stmtFindES->execute([$esNorm]);
        $esRow = $stmtFindES->fetch();

        if (!$esRow) {
            $stats['es_not_found']++;
            continue;
        }

        $esWordId = (int)$esRow['id'];

        // The meaning field may have multiple translations: "hello, hi"
        // Split carefully: respect parentheses so "to be (identity, origin)" stays together
        $raw = $cw['meaning'];

        // Step 1: Split by semicolons (always a separator)
        $parts = array_map('trim', explode(';', $raw));

        // Step 2: Split each part by commas, but only outside parentheses
        $meanings = [];
        foreach ($parts as $part) {
            $depth = 0;
            $current = '';
            for ($ci = 0; $ci < mb_strlen($part); $ci++) {
                $ch = mb_substr($part, $ci, 1);
                if ($ch === '(') $depth++;
                elseif ($ch === ')') $depth = max(0, $depth - 1);
                elseif ($ch === ',' && $depth === 0) {
                    $trimmed = trim($current);
                    if ($trimmed) $meanings[] = $trimmed;
                    $current = '';
                    continue;
                }
                $current .= $ch;
            }
            $trimmed = trim($current);
            if ($trimmed) $meanings[] = $trimmed;
        }

        // Step 3: Clean parenthetical notes: "you (informal)" → "you"
        $cleanMeanings = [];
        foreach ($meanings as $m) {
            $clean = trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', $m));
            $clean = trim($clean);
            if ($clean && mb_strlen($clean) > 0 && !in_array($clean, $cleanMeanings)) {
                $cleanMeanings[] = $clean;
            }
        }

        if (count($cleanMeanings) > 1) {
            $stats['multi_meanings']++;
        }

        foreach ($cleanMeanings as $enWord) {
            if (mb_strlen($enWord) < 1 || mb_strlen($enWord) > 100) continue;

            $enNorm = normalize($enWord);
            if (!$enNorm) continue;

            if ($dryRun) {
                echo "  [DRY] {$cw['word']} → {$enWord}\n";
                $stats['en_words_created']++;
                continue;
            }

            // Find or create English word entry
            $stmtFindEN->execute([$enNorm]);
            $enRow = $stmtFindEN->fetch();

            if ($enRow) {
                $enWordId = (int)$enRow['id'];
                $stats['en_words_existed']++;
            } else {
                // Map POS to allowed ENUM values
                $posMap = [
                    'numeral' => 'adjective',
                    'determiner' => 'article',
                ];
                $safePos = $posMap[$cw['pos']] ?? $cw['pos'];
                $stmtInsertWord->execute([
                    $enWord,
                    $enNorm,
                    $safePos,
                    $cw['gender'],
                    $cw['level'],
                ]);
                $enWordId = (int)$pdo->lastInsertId();
                $stats['en_words_created']++;
            }

            // Create bidirectional translation links
            $stmtInsertLink->execute([$esWordId, $enWordId]);
            $stmtInsertLink->execute([$enWordId, $esWordId]);
            $stats['links_created'] += 2;

            // Copy English definitions from Spanish word_id to English word_id
            $stmtCheckDef->execute([$enWordId]);
            $enHasDefs = (int)$stmtCheckDef->fetchColumn() > 0;

            if (!$enHasDefs) {
                // Check if Spanish word has English definitions
                $stmtGetDefs->execute([$esWordId]);
                $esDefs = $stmtGetDefs->fetchAll();

                if (!empty($esDefs)) {
                    foreach ($esDefs as $def) {
                        $stmtInsertDef->execute([
                            $enWordId,
                            $def['definition'],
                            $def['usage_note'],
                            $def['sort_order'] ?? 0,
                        ]);
                        $stats['defs_copied']++;
                    }
                } else {
                    // No English definitions exist yet — create a simple one from the curated def
                    if ($cw['def']) {
                        // The curated def is in Spanish — use the meaning as a minimal definition
                        $simpleDef = ucfirst($cw['meaning']) . '.';
                        $stmtInsertDef->execute([$enWordId, $simpleDef, null, 0]);
                        $stats['defs_created']++;
                    }
                }
            }
        }
    }

    if (!$dryRun) {
        $pdo->commit();
        echo "Transaction committed.\n\n";
    }
} catch (Exception $e) {
    if (!$dryRun) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "ROLLED BACK.\n";
    exit(1);
}

// ── Summary ─────────────────────────────────────────────────────────

echo "=== Results ===\n";
echo "  English words created:     {$stats['en_words_created']}\n";
echo "  English words existed:     {$stats['en_words_existed']}\n";
echo "  Translation links created: {$stats['links_created']}\n";
echo "  Definitions copied:        {$stats['defs_copied']}\n";
echo "  Definitions created:       {$stats['defs_created']}\n";
echo "  Spanish words not in DB:   {$stats['es_not_found']}\n";
echo "  Multi-meaning entries:     {$stats['multi_meanings']}\n\n";

if (!$dryRun) {
    // Final counts
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_words WHERE lang_code = 'en'");
    echo "Total English words: " . $stmt->fetch()['c'] . "\n";

    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT dt.source_word_id) as c
        FROM dict_translations dt
        JOIN dict_words sw ON sw.id = dt.source_word_id AND sw.lang_code = 'es'
        JOIN dict_words tw ON tw.id = dt.target_word_id AND tw.lang_code = 'en'
    ");
    echo "ES→EN linked words: " . $stmt->fetch()['c'] . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_definitions WHERE lang_code = 'en'");
    echo "Total EN definitions: " . $stmt->fetch()['c'] . "\n";
}

echo "\nDone.\n";
