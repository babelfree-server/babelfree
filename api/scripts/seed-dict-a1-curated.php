<?php
/**
 * seed-dict-a1-curated.php — Seed curated A1 dictionary entries
 *
 * Loads dict_entries_a1.json and upserts:
 *   - dict_words (Spanish entries with POS, gender, IPA, CEFR=A1)
 *   - dict_definitions (Spanish definition per word)
 *   - dict_examples (context phrase per word)
 *
 * Run: php seed-dict-a1-curated.php [--dry-run]
 *
 * Safe to run multiple times (idempotent via ON DUPLICATE KEY UPDATE).
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$dataDir = __DIR__ . '/data';
$dryRun = in_array('--dry-run', $argv ?? []);

echo "=== A1 Curated Dictionary Seeder ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n\n";

// ── Helpers ──

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

// ── Load entries ──

$file = "$dataDir/dict_entries_a1.json";
if (!file_exists($file)) {
    echo "  ERROR: $file not found\n";
    exit(1);
}
$entries = json_decode(file_get_contents($file), true);
if (!$entries) {
    echo "  ERROR: Failed to parse JSON\n";
    exit(1);
}
echo "  Loaded " . count($entries) . " entries from dict_entries_a1.json\n\n";

// ── Prepare statements ──

$stmtWord = $pdo->prepare("
    INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, pronunciation_ipa, cefr_level)
    VALUES ('es', :word, :norm, :pos, :gender, :ipa, 'A1')
    ON DUPLICATE KEY UPDATE
        part_of_speech = COALESCE(VALUES(part_of_speech), part_of_speech),
        gender = COALESCE(VALUES(gender), gender),
        pronunciation_ipa = COALESCE(VALUES(pronunciation_ipa), pronunciation_ipa),
        cefr_level = 'A1'
");

$stmtGetWordId = $pdo->prepare("
    SELECT id FROM dict_words WHERE lang_code = 'es' AND word_normalized = :norm LIMIT 1
");

$stmtDefCheck = $pdo->prepare("
    SELECT id FROM dict_definitions WHERE word_id = :wid AND lang_code = 'es' LIMIT 1
");
$stmtDefInsert = $pdo->prepare("
    INSERT INTO dict_definitions (word_id, lang_code, definition, sort_order)
    VALUES (:wid, 'es', :def, 0)
");
$stmtDefUpdate = $pdo->prepare("
    UPDATE dict_definitions SET definition = :def WHERE id = :id
");

$stmtExCheck = $pdo->prepare("
    SELECT id FROM dict_examples WHERE word_id = :wid AND source = 'curated_a1' LIMIT 1
");
$stmtExInsert = $pdo->prepare("
    INSERT INTO dict_examples (word_id, sentence, cefr_level, source)
    VALUES (:wid, :sentence, 'A1', 'curated_a1')
");
$stmtExUpdate = $pdo->prepare("
    UPDATE dict_examples SET sentence = :sentence WHERE id = :id
");

// ── Process entries ──

$stats = ['words_upserted' => 0, 'defs_added' => 0, 'defs_updated' => 0, 'examples_added' => 0, 'examples_updated' => 0, 'errors' => 0];

$pdo->beginTransaction();

try {
    foreach ($entries as $i => $e) {
        $word = $e['word'];
        $norm = normalize($word);
        $pos = $e['pos'];
        // Map types not in DB enum
        if ($pos === 'numeral') $pos = 'adjective';
        $gender = $e['gender'];
        $ipa = $e['ipa'];
        $def = $e['def'];
        $example = $e['example'];

        if ($dryRun) {
            echo "  [DRY] $word ($pos) — \"$example\"\n";
            $stats['words_upserted']++;
            continue;
        }

        // 1. Upsert word
        $stmtWord->execute([
            ':word' => $word,
            ':norm' => $norm,
            ':pos' => $pos,
            ':gender' => $gender,
            ':ipa' => $ipa,
        ]);
        $stats['words_upserted']++;

        // 2. Get word ID
        $stmtGetWordId->execute([':norm' => $norm]);
        $row = $stmtGetWordId->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo "  WARNING: Could not find word_id for '$word' after upsert\n";
            $stats['errors']++;
            continue;
        }
        $wordId = $row['id'];

        // 3. Upsert Spanish definition
        $stmtDefCheck->execute([':wid' => $wordId]);
        $existingDef = $stmtDefCheck->fetch(PDO::FETCH_ASSOC);
        if ($existingDef) {
            $stmtDefUpdate->execute([':def' => $def, ':id' => $existingDef['id']]);
            $stats['defs_updated']++;
        } else {
            $stmtDefInsert->execute([':wid' => $wordId, ':def' => $def]);
            $stats['defs_added']++;
        }

        // 4. Upsert example sentence
        $stmtExCheck->execute([':wid' => $wordId]);
        $existingEx = $stmtExCheck->fetch(PDO::FETCH_ASSOC);
        if ($existingEx) {
            $stmtExUpdate->execute([':sentence' => $example, ':id' => $existingEx['id']]);
            $stats['examples_updated']++;
        } else {
            $stmtExInsert->execute([':wid' => $wordId, ':sentence' => $example]);
            $stats['examples_added']++;
        }
    }

    if (!$dryRun) {
        $pdo->commit();
    }
} catch (Exception $ex) {
    if (!$dryRun) {
        $pdo->rollBack();
    }
    echo "  FATAL: " . $ex->getMessage() . "\n";
    exit(1);
}

// ── Summary ──

echo "\n=== Summary ===\n";
echo "  Words upserted:    {$stats['words_upserted']}\n";
echo "  Defs added:        {$stats['defs_added']}\n";
echo "  Defs updated:      {$stats['defs_updated']}\n";
echo "  Examples added:    {$stats['examples_added']}\n";
echo "  Examples updated:  {$stats['examples_updated']}\n";
echo "  Errors:            {$stats['errors']}\n";
echo "\nDone.\n";
