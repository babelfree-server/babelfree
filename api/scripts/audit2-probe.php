<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

echo "=== Duplicate Words (top 10 languages) ===\n";
$rows = $pdo->query("SELECT lang_code, COUNT(*) as cnt FROM (
    SELECT word, lang_code, part_of_speech, COUNT(*) as c
    FROM dict_words GROUP BY word, lang_code, part_of_speech HAVING c > 1
) d GROUP BY lang_code ORDER BY cnt DESC LIMIT 10")->fetchAll();
foreach ($rows as $r) echo "  [{$r['lang_code']}] {$r['cnt']}\n";

echo "\n=== Sample duplicates (most copies) ===\n";
$rows = $pdo->query("SELECT word, lang_code, part_of_speech, COUNT(*) as cnt
    FROM dict_words GROUP BY word, lang_code, part_of_speech
    HAVING cnt > 1 ORDER BY cnt DESC LIMIT 15")->fetchAll();
foreach ($rows as $r) echo "  [{$r['lang_code']}] \"{$r['word']}\" ({$r['part_of_speech']}): {$r['cnt']} copies\n";

echo "\n=== Ultra-short defs (≤2 chars) top values ===\n";
$rows = $pdo->query("SELECT definition, COUNT(*) as cnt
    FROM dict_definitions WHERE CHAR_LENGTH(definition) <= 2
    GROUP BY definition ORDER BY cnt DESC LIMIT 15")->fetchAll();
foreach ($rows as $r) echo "  \"{$r['definition']}\": {$r['cnt']} entries\n";

echo "\n=== Ultra-short defs: sample words ===\n";
$rows = $pdo->query("SELECT d.definition, w.word, w.lang_code
    FROM dict_definitions d JOIN dict_words w ON w.id = d.word_id
    WHERE CHAR_LENGTH(d.definition) <= 2 LIMIT 20")->fetchAll();
foreach ($rows as $r) echo "  [{$r['lang_code']}] \"{$r['word']}\" → \"{$r['definition']}\"\n";

echo "\n=== Suspiciously long words (>80 chars) ===\n";
$rows = $pdo->query("SELECT word, lang_code, CHAR_LENGTH(word) as len
    FROM dict_words WHERE CHAR_LENGTH(word) > 80
    ORDER BY len DESC LIMIT 15")->fetchAll();
foreach ($rows as $r) echo "  [{$r['lang_code']}] ({$r['len']} chars) \"{$r['word']}\"\n";
