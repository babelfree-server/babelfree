<?php
/**
 * Post-cleanup audit fixes:
 *  1. Merge duplicate word entries (same word+lang+POS) — keep lowest ID, migrate defs/translations/examples
 *  2. Remove sentence-length "words" (>80 chars)
 */
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

$dryRun = !in_array('--apply', $argv);
$mode = $dryRun ? 'DRY RUN' : 'APPLYING';
echo "=== Post-Cleanup Audit Fix ($mode) ===\n\n";

// ── Step 1: Merge duplicate words ─────────────────────────────────
echo "── Step 1: Merge Duplicate Words ──────────────────────────────\n";

// Find all groups of duplicates
$stmt = $pdo->query("
    SELECT word, lang_code, part_of_speech, GROUP_CONCAT(id ORDER BY id) as ids, COUNT(*) as cnt
    FROM dict_words
    GROUP BY word, lang_code, part_of_speech
    HAVING cnt > 1
    ORDER BY cnt DESC
");
$groups = $stmt->fetchAll();
echo "  Found " . count($groups) . " duplicate groups\n";

$mergedWords = 0;
$deletedDupes = 0;
$migratedDefs = 0;
$migratedTrans = 0;
$migratedExamples = 0;

// Prepared statements for migration
$moveDefs = $pdo->prepare("UPDATE dict_definitions SET word_id = ? WHERE word_id = ?");
$moveExamples = $pdo->prepare("UPDATE dict_examples SET word_id = ? WHERE word_id = ?");
$deleteWord = $pdo->prepare("DELETE FROM dict_words WHERE id = ?");

// For translations: delete dupes that would conflict, then migrate the rest
$delConflictTransSrc = $pdo->prepare("
    DELETE FROM dict_translations WHERE source_word_id = ? AND target_word_id IN (
        SELECT target_word_id FROM (
            SELECT t2.target_word_id FROM dict_translations t2 WHERE t2.source_word_id = ?
        ) AS existing
    )
");
$delConflictTransTgt = $pdo->prepare("
    DELETE FROM dict_translations WHERE target_word_id = ? AND source_word_id IN (
        SELECT source_word_id FROM (
            SELECT t2.source_word_id FROM dict_translations t2 WHERE t2.target_word_id = ?
        ) AS existing
    )
");
$moveTransSrc = $pdo->prepare("UPDATE dict_translations SET source_word_id = ? WHERE source_word_id = ?");
$moveTransTgt = $pdo->prepare("UPDATE dict_translations SET target_word_id = ? WHERE target_word_id = ?");

// For dedup after migration: remove duplicate defs that now share the same word_id
$dedupDefs = $pdo->prepare("
    DELETE d2 FROM dict_definitions d1
    JOIN dict_definitions d2 ON d2.word_id = d1.word_id
        AND d2.definition = d1.definition AND d2.id > d1.id
    WHERE d1.word_id = ?
");

// For dedup translations after migration
$dedupTrans = $pdo->prepare("
    DELETE t2 FROM dict_translations t1
    JOIN dict_translations t2 ON t2.source_word_id = t1.source_word_id
        AND t2.target_word_id = t1.target_word_id AND t2.id > t1.id
    WHERE t1.source_word_id = ?
");

$shown = 0;
foreach ($groups as $g) {
    $ids = explode(',', $g['ids']);
    $keepId = (int)$ids[0]; // lowest ID
    $dupeIds = array_slice($ids, 1);

    if ($shown < 10) {
        echo "  [{$g['lang_code']}] \"{$g['word']}\" ({$g['part_of_speech']}): keep #{$keepId}, merge " . count($dupeIds) . " dupes\n";
        $shown++;
    }

    if (!$dryRun) {
        foreach ($dupeIds as $dupeId) {
            $dupeId = (int)$dupeId;
            // Migrate everything to the keeper
            $moveDefs->execute([$keepId, $dupeId]);
            $migratedDefs += $moveDefs->rowCount();

            // Translations: delete conflicts first, then migrate
            $delConflictTransSrc->execute([$dupeId, $keepId]);
            $moveTransSrc->execute([$keepId, $dupeId]);
            $migratedTrans += $moveTransSrc->rowCount();

            $delConflictTransTgt->execute([$dupeId, $keepId]);
            $moveTransTgt->execute([$keepId, $dupeId]);
            $migratedTrans += $moveTransTgt->rowCount();

            $moveExamples->execute([$keepId, $dupeId]);
            $migratedExamples += $moveExamples->rowCount();

            // Delete the duplicate word entry
            $deleteWord->execute([$dupeId]);
            $deletedDupes++;
        }

        // Dedup definitions and translations on the keeper
        $dedupDefs->execute([$keepId]);
        $dedupTrans->execute([$keepId]);
    } else {
        $deletedDupes += count($dupeIds);
    }
    $mergedWords++;
}

echo "  Groups merged: $mergedWords\n";
echo "  Duplicate entries " . ($dryRun ? "to delete" : "deleted") . ": $deletedDupes\n";
if (!$dryRun) {
    echo "  Definitions migrated: $migratedDefs\n";
    echo "  Translations migrated: $migratedTrans\n";
    echo "  Examples migrated: $migratedExamples\n";
}

// ── Step 2: Remove sentence-length "words" ────────────────────────
echo "\n── Step 2: Remove Sentence-Length Words (>80 chars) ───────────\n";

$stmt = $pdo->query("SELECT id, word, lang_code, CHAR_LENGTH(word) as len
    FROM dict_words WHERE CHAR_LENGTH(word) > 80 ORDER BY len DESC");
$longWords = $stmt->fetchAll();
echo "  Found " . count($longWords) . " entries\n";

$deletedLong = 0;
$delWord = $pdo->prepare("DELETE FROM dict_words WHERE id = ?");
$delWordDefs = $pdo->prepare("DELETE FROM dict_definitions WHERE word_id = ?");
$delWordTrans1 = $pdo->prepare("DELETE FROM dict_translations WHERE source_word_id = ?");
$delWordTrans2 = $pdo->prepare("DELETE FROM dict_translations WHERE target_word_id = ?");
$delWordEx = $pdo->prepare("DELETE FROM dict_examples WHERE word_id = ?");

foreach ($longWords as $r) {
    echo "  [{$r['lang_code']}] ({$r['len']}ch) \"" . mb_substr($r['word'], 0, 70) . "...\"\n";
    if (!$dryRun) {
        $delWordDefs->execute([$r['id']]);
        $delWordTrans1->execute([$r['id']]);
        $delWordTrans2->execute([$r['id']]);
        $delWordEx->execute([$r['id']]);
        $delWord->execute([$r['id']]);
        $deletedLong++;
    }
}

echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": " . ($dryRun ? count($longWords) : $deletedLong) . " sentence-length entries\n";

echo "\n=== Done ===\n";
