<?php
/**
 * Dictionary Expansion Verification — Reports coverage metrics.
 *
 * Queries the database and reports how well each quality dimension
 * has been filled after running the expansion pipeline.
 *
 * Usage:
 *   php verify-dictionary-expansion.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "=== Dictionary Expansion Verification ===\n\n";

// ── Spanish words ───────────────────────────────────────────────────

$totalES = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es'"
)->fetchColumn();

$withEsDefs = (int)$pdo->query(
    "SELECT COUNT(DISTINCT d.word_id) FROM dict_definitions d
     JOIN dict_words w ON w.id = d.word_id
     WHERE w.lang_code = 'es' AND d.lang_code = 'es'"
)->fetchColumn();

$withEnDefs = (int)$pdo->query(
    "SELECT COUNT(DISTINCT d.word_id) FROM dict_definitions d
     JOIN dict_words w ON w.id = d.word_id
     WHERE w.lang_code = 'es' AND d.lang_code = 'en'"
)->fetchColumn();

$withIPA = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_words
     WHERE lang_code = 'es' AND pronunciation_ipa IS NOT NULL AND pronunciation_ipa != ''"
)->fetchColumn();

$withExamples = (int)$pdo->query(
    "SELECT COUNT(DISTINCT e.word_id) FROM dict_examples e
     JOIN dict_words w ON w.id = e.word_id
     WHERE w.lang_code = 'es'"
)->fetchColumn();

$totalExamples = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_examples e
     JOIN dict_words w ON w.id = e.word_id
     WHERE w.lang_code = 'es'"
)->fetchColumn();

// ── Conjugations ────────────────────────────────────────────────────

$totalVerbs = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es' AND part_of_speech = 'verb'"
)->fetchColumn();

$conjugatedVerbs = (int)$pdo->query(
    "SELECT COUNT(DISTINCT c.word_id) FROM dict_conjugations c
     JOIN dict_words w ON w.id = c.word_id
     WHERE w.lang_code = 'es'"
)->fetchColumn();

// ── Related words ───────────────────────────────────────────────────

$totalRelated = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_related_words"
)->fetchColumn();

$wordsWithRelated = (int)$pdo->query(
    "SELECT COUNT(DISTINCT word_id) FROM dict_related_words"
)->fetchColumn();

$relatedByType = $pdo->query(
    "SELECT relation_type, COUNT(*) as c FROM dict_related_words GROUP BY relation_type ORDER BY c DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── CEFR distribution ───────────────────────────────────────────────

$cefrDist = $pdo->query(
    "SELECT cefr_level, COUNT(*) as c FROM dict_words
     WHERE lang_code = 'es' AND cefr_level IS NOT NULL
     GROUP BY cefr_level ORDER BY FIELD(cefr_level, 'A1','A2','B1','B2','C1','C2')"
)->fetchAll(PDO::FETCH_ASSOC);

$noCefr = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es' AND cefr_level IS NULL"
)->fetchColumn();

// ── POS distribution ────────────────────────────────────────────────

$posDist = $pdo->query(
    "SELECT part_of_speech, COUNT(*) as c FROM dict_words
     WHERE lang_code = 'es'
     GROUP BY part_of_speech ORDER BY c DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Language Parity ─────────────────────────────────────────────────

$parityLangs = ['es', 'en', 'fr', 'de', 'pt', 'it'];

$langWordCounts = [];
$langDefCounts  = [];
$langLinkCounts = [];

foreach ($parityLangs as $lang) {
    $langWordCounts[$lang] = (int)$pdo->query(
        "SELECT COUNT(*) FROM dict_words WHERE lang_code = '$lang'"
    )->fetchColumn();

    $langDefCounts[$lang] = (int)$pdo->query(
        "SELECT COUNT(*) FROM dict_definitions d
         JOIN dict_words w ON w.id = d.word_id
         WHERE w.lang_code = '$lang' AND d.lang_code = '$lang'"
    )->fetchColumn();

    if ($lang !== 'es') {
        $langLinkCounts[$lang] = (int)$pdo->query(
            "SELECT COUNT(DISTINCT dt.source_word_id) FROM dict_translations dt
             JOIN dict_words sw ON sw.id = dt.source_word_id AND sw.lang_code = 'es'
             JOIN dict_words tw ON tw.id = dt.target_word_id AND tw.lang_code = '$lang'"
        )->fetchColumn();
    }
}

// ── Translation source breakdown ─────────────────────────────────────

// Count definitions per language (total — no source_id column available yet)
$tierBreakdown = [];
foreach (['en', 'fr', 'de', 'pt', 'it'] as $lang) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT d.word_id) FROM dict_definitions d
         JOIN dict_words w ON w.id = d.word_id
         WHERE w.lang_code = ? AND d.lang_code = ?"
    );
    $stmt->execute([$lang, $lang]);
    $tierBreakdown[$lang] = (int) $stmt->fetchColumn();
}

// ── Report ──────────────────────────────────────────────────────────

function pct(int $part, int $total): string {
    if ($total === 0) return '0%';
    return round($part / $total * 100, 1) . '%';
}

function bar(int $part, int $total, int $width = 20): string {
    if ($total === 0) return str_repeat('░', $width);
    $filled = (int)round($part / $total * $width);
    return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
}

echo "--- Spanish Words ---\n";
echo "  Total:              " . number_format($totalES) . "\n";
echo "  With ES defs:       " . number_format($withEsDefs) . "  " . bar($withEsDefs, $totalES) . " " . pct($withEsDefs, $totalES) . "\n";
echo "  With EN defs:       " . number_format($withEnDefs) . "  " . bar($withEnDefs, $totalES) . " " . pct($withEnDefs, $totalES) . "\n";
echo "  With IPA:           " . number_format($withIPA) . "  " . bar($withIPA, $totalES) . " " . pct($withIPA, $totalES) . "\n";
echo "  With examples:      " . number_format($withExamples) . "  " . bar($withExamples, $totalES) . " " . pct($withExamples, $totalES) . "\n";
echo "  Total examples:     " . number_format($totalExamples) . "\n";

echo "\n--- Conjugations ---\n";
echo "  Total verbs:        " . number_format($totalVerbs) . "\n";
echo "  Conjugated:         " . number_format($conjugatedVerbs) . "  " . bar($conjugatedVerbs, $totalVerbs) . " " . pct($conjugatedVerbs, $totalVerbs) . "\n";
echo "  Missing:            " . number_format($totalVerbs - $conjugatedVerbs) . "\n";

// ── Language Parity Report ──────────────────────────────────────────

echo "\n--- Language Parity Report ---\n";
echo "  " . str_pad('Lang', 6) . str_pad('Words', 10) . str_pad('Defs', 10)
   . str_pad('ES→Link', 10) . str_pad('Parity', 10) . str_pad('Bar', 22) . "\n";
echo "  " . str_repeat('-', 68) . "\n";

foreach ($parityLangs as $lang) {
    $words = $langWordCounts[$lang];
    $defs  = $langDefCounts[$lang];
    $links = $lang === 'es' ? '-' : number_format($langLinkCounts[$lang] ?? 0);
    $parityPct = $totalES > 0 ? round($words / $totalES * 100, 1) . '%' : '0%';
    $note = $lang === 'es' ? 'baseline' : $parityPct . ' parity';

    echo "  " . str_pad($lang, 6)
       . str_pad(number_format($words), 10)
       . str_pad(number_format($defs), 10)
       . str_pad($links, 10)
       . str_pad($note, 10)
       . bar($words, $totalES)
       . "\n";
}

// ── Definition Coverage (per language) ────────────────────────────────

echo "\n--- Definition Coverage (per language) ---\n";
echo "  " . str_pad('Lang', 6) . str_pad('Words w/defs', 14) . str_pad('Bar', 22) . "\n";
echo "  " . str_repeat('-', 42) . "\n";

foreach (['en', 'fr', 'de', 'pt', 'it'] as $lang) {
    $wdefs = $tierBreakdown[$lang];
    $total = $langWordCounts[$lang];
    echo "  " . str_pad($lang, 6)
       . str_pad(number_format($wdefs) . ' / ' . number_format($total), 14)
       . bar($wdefs, max($total, 1))
       . " " . pct($wdefs, max($total, 1))
       . "\n";
}

// ── Translation Links ───────────────────────────────────────────────

$translationLinks = (int)$pdo->query(
    "SELECT COUNT(*) FROM dict_translations"
)->fetchColumn();

echo "\n--- Translation Links ---\n";
echo "  Total links:        " . number_format($translationLinks) . " (bidirectional)\n";

foreach (['en', 'fr', 'de', 'pt', 'it'] as $lang) {
    $count = $langLinkCounts[$lang] ?? 0;
    echo "  ES→$lang linked:     " . number_format($count) . " / " . number_format($totalES)
       . "  " . bar($count, $totalES) . " " . pct($count, $totalES) . "\n";
}

// ── Related Words ───────────────────────────────────────────────────

echo "\n--- Related Words ---\n";
echo "  Total links:        " . number_format($totalRelated) . "\n";
echo "  Words with links:   " . number_format($wordsWithRelated) . " / " . number_format($totalES) . "  " . bar($wordsWithRelated, $totalES) . " " . pct($wordsWithRelated, $totalES) . "\n";
foreach ($relatedByType as $r) {
    echo "    {$r['relation_type']}: " . number_format($r['c']) . "\n";
}

// ── CEFR Distribution ───────────────────────────────────────────────

echo "\n--- CEFR Distribution ---\n";
foreach ($cefrDist as $r) {
    echo "  {$r['cefr_level']}: " . str_pad(number_format($r['c']), 6, ' ', STR_PAD_LEFT) . "  " . bar($r['c'], $totalES) . "\n";
}
if ($noCefr > 0) {
    echo "  None: " . str_pad(number_format($noCefr), 6, ' ', STR_PAD_LEFT) . "  " . bar($noCefr, $totalES) . "\n";
}

// ── POS Distribution ────────────────────────────────────────────────

echo "\n--- POS Distribution ---\n";
foreach ($posDist as $r) {
    $pos = $r['part_of_speech'] ?: '(none)';
    echo "  " . str_pad($pos, 15) . number_format($r['c']) . "\n";
}

echo "\n=== Verification Complete ===\n";
