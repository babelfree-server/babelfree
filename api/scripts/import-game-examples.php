<?php
/**
 * Import game content examples into the dictionary
 * Run: php api/scripts/import-game-examples.php
 */
ini_set('memory_limit', '256M');
require __DIR__ . '/../config/database.php';
$pdo = getDB();

$examples = json_decode(file_get_contents('/tmp/game-examples.json'), true);
if (!$examples) { echo "No examples file found.\n"; exit(1); }

$inserted = 0;
$skipped = 0;
$notFound = 0;
$total = count($examples);
$i = 0;

$pdo->beginTransaction();

foreach ($examples as $word => $exList) {
    $i++;
    if ($i % 100 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        echo "  Progress: $i/$total words...\r";
    }

    // Find word in Spanish dictionary
    $stmt = $pdo->prepare('SELECT id FROM dict_words WHERE lang_code = ? AND word = ? LIMIT 1');
    $stmt->execute(['es', $word]);
    $wordId = $stmt->fetchColumn();

    if (!$wordId) {
        $stmt = $pdo->prepare('SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1');
        $stmt->execute(['es', mb_strtolower($word)]);
        $wordId = $stmt->fetchColumn();
    }

    if (!$wordId) { $notFound++; continue; }

    foreach ($exList as $ex) {
        $sentence = $ex[0];
        $cefr = $ex[1];
        $source = $ex[2];

        // Skip if exists (use source_id to avoid full text comparison)
        $sourceId = 'jaguar-' . md5($sentence);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM dict_examples WHERE word_id = ? AND source_id = ?');
        $stmt->execute([$wordId, $sourceId]);
        if ($stmt->fetchColumn() > 0) { $skipped++; continue; }

        $stmt = $pdo->prepare('INSERT INTO dict_examples (word_id, sentence, cefr_level, source, source_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$wordId, $sentence, $cefr, $source, $sourceId]);
        $inserted++;
    }
}

$pdo->commit();

echo "\nDictionary enrichment complete:\n";
echo "  Inserted: $inserted new examples\n";
echo "  Skipped: $skipped (duplicates)\n";
echo "  Words not in dict: $notFound\n";
