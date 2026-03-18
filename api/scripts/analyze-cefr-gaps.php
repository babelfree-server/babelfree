#!/usr/bin/env php
<?php
/**
 * Analyze CEFR false positive root causes for Spanish
 */
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

echo "=== Sample words and their CEFR levels ===\n";
$words = ['rana','despertar','sonríe','brillan','susurra','abre','ojos','camina',
    'siente','escuchar','brillante','antiguo','sagrado','profundo','misterio',
    'ceremonia','espíritu','ancestral','ritual','silencio','estrella','oscuro',
    'piedra','puente','fuego','sangre','sombra','guerrero','destino','dorado',
    'dormido','despiertas','huella','jaguar','ceiba','raíz','selva','responde',
    'construiste','abismo','conjuro','rana','murmura','canta','brilla','nace',
    'crece','fluye','arde','rompe','cruza','toca','llama','corre','vuela','sueña'];
$ph = implode(',', array_fill(0, count($words), '?'));
$stmt = $pdo->prepare("SELECT word_normalized, cefr_level, frequency_rank FROM dict_words WHERE lang_code='es' AND word_normalized IN ($ph) ORDER BY cefr_level, word_normalized");
$stmt->execute($words);
foreach ($stmt as $r) {
    $src = $r['frequency_rank'] ? 'freq' : 'heur';
    printf("  %-15s %s  rank=%-6s (%s)\n", $r['word_normalized'], $r['cefr_level'], $r['frequency_rank'] ?? 'null', $src);
}

echo "\n=== Frequency coverage ===\n";
$r = $pdo->query("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code='es'")->fetch(PDO::FETCH_ASSOC);
echo "Total: {$r['total']}, Has frequency: {$r['has_freq']}\n";

echo "\n=== CEFR distribution by source ===\n";
$stmt = $pdo->query("SELECT cefr_level,
    SUM(frequency_rank IS NOT NULL) as freq_based,
    SUM(frequency_rank IS NULL) as heuristic_based,
    COUNT(*) as total
    FROM dict_words WHERE lang_code='es' AND cefr_level IS NOT NULL
    GROUP BY cefr_level ORDER BY FIELD(cefr_level,'A1','A2','B1','B2','C1','C2')");
foreach ($stmt as $r) {
    printf("  %s: freq=%6d  heur=%6d  total=%6d\n", $r['cefr_level'], $r['freq_based'], $r['heuristic_based'], $r['total']);
}

echo "\n=== Multi-word vs single-word entries ===\n";
$r = $pdo->query("SELECT SUM(word_normalized NOT LIKE '% %') as single_w, SUM(word_normalized LIKE '% %') as multi_w FROM dict_words WHERE lang_code='es'")->fetch(PDO::FETCH_ASSOC);
echo "Single words: {$r['single_w']}, Multi-word phrases: {$r['multi_w']}\n";

echo "\n=== Frequency file sample ===\n";
$lines = array_slice(file('data/frequency_es_50k.txt', FILE_IGNORE_NEW_LINES), 0, 15);
foreach ($lines as $l) echo "  $l\n";

echo "\n=== Unique single-word forms in DB ===\n";
$r = $pdo->query("SELECT COUNT(DISTINCT word_normalized) as cnt FROM dict_words WHERE lang_code='es' AND word_normalized NOT LIKE '% %'")->fetch(PDO::FETCH_ASSOC);
echo "Unique: {$r['cnt']}\n";

echo "\n=== Top 20 heuristic-C2 words that are probably wrong ===\n";
$stmt = $pdo->query("SELECT word_normalized, cefr_level FROM dict_words
    WHERE lang_code='es' AND frequency_rank IS NULL AND cefr_level = 'C2'
    AND LENGTH(word_normalized) BETWEEN 3 AND 10
    AND word_normalized NOT LIKE '% %'
    ORDER BY LENGTH(word_normalized) ASC
    LIMIT 40");
foreach ($stmt as $r) {
    printf("  %-20s %s\n", $r['word_normalized'], $r['cefr_level']);
}

echo "\n=== Conjugated forms check: do verb conjugations exist in freq file? ===\n";
// Check if common conjugations like 'camina', 'habla', 'come' are in freq file
$conjForms = ['camina','habla','come','vive','dice','sabe','puede','quiere','tiene','hace',
    'canta','brilla','murmura','susurra','sonríe','duerme','corre','vuela','crece','nace'];
$ph2 = implode(',', array_fill(0, count($conjForms), '?'));
$stmt = $pdo->prepare("SELECT word_normalized, cefr_level, frequency_rank FROM dict_words WHERE lang_code='es' AND word_normalized IN ($ph2) ORDER BY word_normalized");
$stmt->execute($conjForms);
echo "  Word            CEFR  Freq_rank  Source\n";
foreach ($stmt as $r) {
    $src = $r['frequency_rank'] ? 'FREQ' : 'HEUR';
    printf("  %-15s %s    %-8s   %s\n", $r['word_normalized'], $r['cefr_level'], $r['frequency_rank'] ?? 'null', $src);
}
