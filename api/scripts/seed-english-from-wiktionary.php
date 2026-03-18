<?php
/**
 * Build English dictionary entries from Wiktionary English definitions.
 *
 * After enrich-wiktionary.php runs, many Spanish words have English definitions
 * (lang_code='en' on the Spanish word_id) but no matching English word entry.
 * This script:
 *   1. Finds Spanish words with English definitions but no EN translation link
 *   2. Extracts the English meaning from the first definition
 *   3. Creates or finds the English dict_words entry
 *   4. Creates bidirectional translation links
 *   5. Copies English definitions to the English word_id
 *
 * Pattern follows seed-english-from-curated.php.
 *
 * Usage:
 *   php seed-english-from-wiktionary.php
 *   php seed-english-from-wiktionary.php --dry-run
 *   php seed-english-from-wiktionary.php --limit=1000
 */

require_once __DIR__ . '/../config/database.php';

$dryRun = in_array('--dry-run', $argv ?? []);
$limit = 0; // 0 = no limit
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    }
}

$pdo = getDB();

echo "=== English Dictionary Builder (from Wiktionary data) ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n";
if ($limit) echo "  Limit: $limit\n";
echo "\n";

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

/**
 * Extract the English "meaning" from a Wiktionary-style definition.
 * Handles patterns like:
 *   "to be" → "to be"
 *   "A type of small boat." → "small boat" (simplified)
 *   "Something used for..." → take first few words
 */
function extractMeaning(string $definition): ?string {
    $def = trim($definition);

    // Skip definitions that are references to other forms
    if (preg_match('/^(inflection|form|plural|past tense|feminine|masculine) of\b/i', $def)) {
        return null;
    }

    // If it starts with "to " — it's a verb meaning, keep as-is
    if (preg_match('/^to\s+\w+/i', $def)) {
        // Keep "to verb" + first few words
        if (preg_match('/^(to\s+\w+(?:\s+\w+){0,3})/', $def, $m)) {
            $meaning = rtrim($m[1], ' ,;.');
            return mb_strlen($meaning) >= 3 ? $meaning : null;
        }
    }

    // Clean: remove trailing period, parenthetical notes
    $def = rtrim($def, '.');
    $def = preg_replace('/\s*\([^)]*\)\s*/', ' ', $def);
    $def = trim($def);

    // If short enough, use as-is
    if (mb_strlen($def) <= 40) {
        return $def ?: null;
    }

    // Otherwise take first clause (up to comma, semicolon, or "used to")
    if (preg_match('/^([^,;]+)/', $def, $m)) {
        $short = trim($m[1]);
        if (mb_strlen($short) >= 3 && mb_strlen($short) <= 60) {
            return $short;
        }
    }

    // Last resort: first 5 words
    $words = preg_split('/\s+/', $def);
    $short = implode(' ', array_slice($words, 0, 5));
    return mb_strlen($short) >= 3 ? $short : null;
}

// ── Find Spanish words with EN defs but no EN translation link ──────

$sql = "
    SELECT w.id AS es_word_id, w.word AS es_word, w.part_of_speech, w.cefr_level,
           d.definition AS en_def, d.sort_order
    FROM dict_words w
    INNER JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = 'en'
    LEFT JOIN dict_translations t ON t.source_word_id = w.id
    LEFT JOIN dict_words tw ON tw.id = t.target_word_id AND tw.lang_code = 'en'
    WHERE w.lang_code = 'es'
      AND tw.id IS NULL
    ORDER BY w.frequency_rank ASC, d.sort_order ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by Spanish word (may have multiple definitions)
$byWord = [];
foreach ($rows as $row) {
    $key = $row['es_word_id'];
    if (!isset($byWord[$key])) {
        $byWord[$key] = [
            'es_word_id' => (int)$row['es_word_id'],
            'es_word' => $row['es_word'],
            'pos' => $row['part_of_speech'],
            'cefr' => $row['cefr_level'],
            'defs' => [],
        ];
    }
    $byWord[$key]['defs'][] = $row['en_def'];
}

if ($limit > 0) {
    $byWord = array_slice($byWord, 0, $limit, true);
}

echo "Spanish words with EN defs but no EN word entry: " . count($byWord) . "\n\n";

if (empty($byWord)) {
    echo "Nothing to do. All Spanish words with EN definitions already have EN entries.\n";
    exit(0);
}

// ── Prepared statements ─────────────────────────────────────────────

$stmtFindEN = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = 'en' AND word_normalized = ? LIMIT 1"
);

$stmtInsertWord = $pdo->prepare("
    INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, cefr_level)
    VALUES ('en', ?, ?, ?, ?)
");

$stmtInsertLink = $pdo->prepare(
    "INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)"
);

$stmtCheckDef = $pdo->prepare(
    "SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND lang_code = 'en'"
);

$stmtGetDefs = $pdo->prepare(
    "SELECT definition, usage_note, sort_order FROM dict_definitions
     WHERE word_id = ? AND lang_code = 'en'
     ORDER BY sort_order"
);

$stmtInsertDef = $pdo->prepare(
    "INSERT INTO dict_definitions (word_id, lang_code, definition, usage_note, sort_order)
     VALUES (?, 'en', ?, ?, ?)"
);

// ── Process ─────────────────────────────────────────────────────────

$stats = [
    'en_words_created' => 0,
    'en_words_existed' => 0,
    'links_created' => 0,
    'defs_copied' => 0,
    'defs_created' => 0,
    'no_meaning' => 0,
    'multi_meanings' => 0,
];

// Map POS to allowed ENUM values
$posMap = [
    'numeral' => 'adjective',
    'determiner' => 'article',
];

if (!$dryRun) {
    $pdo->beginTransaction();
}

try {
    $processed = 0;
    foreach ($byWord as $entry) {
        $esWordId = $entry['es_word_id'];
        $pos = $posMap[$entry['pos']] ?? $entry['pos'];
        $cefr = $entry['cefr'];

        // Extract English meaning from first definition
        $meanings = [];
        foreach ($entry['defs'] as $def) {
            $meaning = extractMeaning($def);
            if ($meaning && !in_array(mb_strtolower($meaning), array_map('mb_strtolower', $meanings))) {
                $meanings[] = $meaning;
            }
            if (count($meanings) >= 3) break; // Cap at 3 meanings per Spanish word
        }

        if (empty($meanings)) {
            $stats['no_meaning']++;
            continue;
        }

        if (count($meanings) > 1) {
            $stats['multi_meanings']++;
        }

        foreach ($meanings as $enWord) {
            if (mb_strlen($enWord) < 1 || mb_strlen($enWord) > 100) continue;

            $enNorm = normalize($enWord);
            if (!$enNorm) continue;

            if ($dryRun) {
                echo "  [DRY] {$entry['es_word']} → {$enWord}\n";
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
                $stmtInsertWord->execute([
                    $enWord,
                    $enNorm,
                    $pos,
                    $cefr,
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
                    // Create a minimal definition from the meaning
                    $simpleDef = ucfirst($enWord) . '.';
                    $stmtInsertDef->execute([$enWordId, $simpleDef, null, 0]);
                    $stats['defs_created']++;
                }
            }
        }

        $processed++;
        if ($processed % 500 === 0) {
            echo "  Progress: $processed / " . count($byWord) . "\n";
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
echo "  No extractable meaning:    {$stats['no_meaning']}\n";
echo "  Multi-meaning entries:     {$stats['multi_meanings']}\n\n";

if (!$dryRun) {
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
