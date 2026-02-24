<?php
/**
 * Dictionary Cleanup Script
 * Fixes corrupted data from the original seed-dictionary.php:
 *  1. Unicode escapes (ven\u00edan → venían)
 *  2. Broken diacritics (mam? → mamá)
 *  3. Fake "English" entries (Spanish text in en lang_code)
 *  4. Broken translations pointing to fake English entries
 *  5. Reset guessed POS to NULL for re-assignment
 *  6. Remove duplicate normalized entries
 *
 * Run: php cleanup-dictionary.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "=== Dictionary Cleanup ===\n\n";

// ── 1. Fix Unicode escapes (\u00XX sequences) ──────────────────────

echo "--- Step 1: Fixing Unicode escape sequences ---\n";

$stmt = $pdo->query("SELECT id, word FROM dict_words WHERE word LIKE '%\\\\u00%' OR word LIKE '%\\\\u01%'");
$unicodeRows = $stmt->fetchAll();
$fixedUnicode = 0;

$updateWord = $pdo->prepare('UPDATE dict_words SET word = ?, word_normalized = ? WHERE id = ?');

foreach ($unicodeRows as $row) {
    // json_decode converts \uXXXX sequences to actual UTF-8
    $fixed = json_decode('"' . $row['word'] . '"');
    if ($fixed && $fixed !== $row['word']) {
        $normalized = mb_strtolower($fixed, 'UTF-8');
        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        }
        $updateWord->execute([$fixed, $normalized, $row['id']]);
        $fixedUnicode++;
    }
}
echo "  Fixed: $fixedUnicode Unicode escape entries\n";

// ── 2. Fix broken diacritics (? replacing accented chars) ──────────

echo "\n--- Step 2: Fixing broken diacritics ---\n";

// Common Spanish words with accents that get corrupted to ?
$diacriticMap = [
    'mam?' => 'mamá', 'pap?' => 'papá', 'caf?' => 'café',
    'est?' => 'está', 'as?' => 'así', 'aqu?' => 'aquí',
    'tambi?n' => 'también', 'com?' => 'comó', 'por qu?' => 'por qué',
    'qu?' => 'qué', 'd?nde' => 'dónde', 'c?mo' => 'cómo',
    'ni?o' => 'niño', 'ni?a' => 'niña', 'a?o' => 'año',
    'espa?ol' => 'español', 'se?or' => 'señor', 'se?ora' => 'señora',
    'canci?n' => 'canción', 'coraz?n' => 'corazón', 'le?n' => 'león',
    'jard?n' => 'jardín', 'avi?n' => 'avión', 'estaci?n' => 'estación',
];

$stmt = $pdo->query("SELECT id, word FROM dict_words WHERE word LIKE '%?%'");
$brokenRows = $stmt->fetchAll();
$fixedDiacritics = 0;

foreach ($brokenRows as $row) {
    $word = $row['word'];
    $lower = mb_strtolower($word, 'UTF-8');
    foreach ($diacriticMap as $broken => $correct) {
        if ($lower === $broken) {
            $normalized = mb_strtolower($correct, 'UTF-8');
            if (function_exists('transliterator_transliterate')) {
                $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
            }
            $updateWord->execute([$correct, $normalized, $row['id']]);
            $fixedDiacritics++;
            break;
        }
    }
}
echo "  Fixed: $fixedDiacritics broken diacritic entries\n";

// ── 3. Delete fake "English" entries (Spanish text with lang_code=en) ─

echo "\n--- Step 3: Removing fake English entries ---\n";

// Spanish-only characters/patterns that should never appear in English words
$fakeEnStmt = $pdo->query(
    "SELECT id, word FROM dict_words WHERE lang_code = 'en'
     AND (word REGEXP '[áéíóúñ¿¡]'
          OR word LIKE '%ción%'
          OR word LIKE '%mente%'
          OR word LIKE '% de %'
          OR word LIKE '% los %'
          OR word LIKE '% las %'
          OR word LIKE '% del %'
          OR word LIKE '% una %'
          OR word LIKE '% un %')"
);
$fakeEnRows = $fakeEnStmt->fetchAll();
$fakeEnIds = array_column($fakeEnRows, 'id');

if (count($fakeEnIds) > 0) {
    // First delete translations pointing to these entries
    $placeholders = implode(',', array_fill(0, count($fakeEnIds), '?'));
    $pdo->prepare("DELETE FROM dict_translations WHERE target_word_id IN ($placeholders)")->execute($fakeEnIds);
    $pdo->prepare("DELETE FROM dict_translations WHERE source_word_id IN ($placeholders)")->execute($fakeEnIds);
    // Delete the fake entries themselves
    $pdo->prepare("DELETE FROM dict_words WHERE id IN ($placeholders)")->execute($fakeEnIds);
}
echo "  Deleted: " . count($fakeEnIds) . " fake English entries\n";

// ── 4. Delete broken translations ──────────────────────────────────

echo "\n--- Step 4: Cleaning broken translations ---\n";

// Remove translations where source or target word no longer exists
$stmt = $pdo->query(
    "DELETE t FROM dict_translations t
     LEFT JOIN dict_words s ON s.id = t.source_word_id
     LEFT JOIN dict_words tgt ON tgt.id = t.target_word_id
     WHERE s.id IS NULL OR tgt.id IS NULL"
);
$brokenTrans = $stmt->rowCount();
echo "  Deleted: $brokenTrans orphaned translations\n";

// ── 5. Reset POS to NULL (for re-assignment from curated data) ─────

echo "\n--- Step 5: Resetting guessed POS ---\n";

$stmt = $pdo->query("UPDATE dict_words SET part_of_speech = NULL WHERE source = 'game' OR is_verified = 0");
$resetPOS = $stmt->rowCount();
echo "  Reset: $resetPOS POS values to NULL\n";

// ── 6. Remove duplicate normalized entries ─────────────────────────

echo "\n--- Step 6: Removing duplicate normalized entries ---\n";

// Keep the entry with the lowest ID (earliest) for each lang_code + word_normalized pair
$stmt = $pdo->query(
    "SELECT lang_code, word_normalized, MIN(id) as keep_id, COUNT(*) as cnt
     FROM dict_words
     GROUP BY lang_code, word_normalized
     HAVING cnt > 1"
);
$dupes = $stmt->fetchAll();
$deletedDupes = 0;

$deleteDupe = $pdo->prepare(
    'DELETE FROM dict_words WHERE lang_code = ? AND word_normalized = ? AND id != ?'
);
foreach ($dupes as $dupe) {
    $deleteDupe->execute([$dupe['lang_code'], $dupe['word_normalized'], $dupe['keep_id']]);
    $deletedDupes += $dupe['cnt'] - 1;
}
echo "  Deleted: $deletedDupes duplicate entries\n";

// ── Summary ────────────────────────────────────────────────────────

echo "\n=== Cleanup Complete ===\n";
$stmt = $pdo->query("SELECT lang_code, COUNT(*) as c FROM dict_words GROUP BY lang_code");
echo "Remaining entries by language:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['lang_code']}: {$row['c']}\n";
}
