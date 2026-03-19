#!/usr/bin/env php
<?php
/**
 * Propagate frequency ranks across duplicate word entries.
 *
 * Problem: The same word_normalized often has multiple dict_words rows (from
 * different kaikki imports — EN-source vs native, different POS, etc.).
 * The frequency seeder only matched ONE entry per word, leaving siblings
 * with no frequency_rank. The CEFR assigner then gave them heuristic levels,
 * creating conflicts (same word = A1 + C1).
 *
 * Fix: For each word_normalized, take the BEST (lowest) frequency_rank from
 * any entry and propagate it to all sibling entries. Then re-run CEFR assignment.
 *
 * Usage:
 *   php propagate-frequency.php --lang=es          # Spanish only
 *   php propagate-frequency.php --all              # All languages
 *   php propagate-frequency.php --lang=es --dry-run
 */

require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

$opts = getopt('', ['lang:', 'all', 'dry-run']);
$dryRun = isset($opts['dry-run']);
$langs = [];

if (isset($opts['all'])) {
    $stmt = $pdo->query("SELECT DISTINCT lang_code FROM dict_words ORDER BY lang_code");
    $langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
} elseif (!empty($opts['lang'])) {
    $langs = [trim($opts['lang'])];
} else {
    echo "Usage: php propagate-frequency.php --lang=es [--dry-run]\n";
    exit(1);
}

foreach ($langs as $lang) {
    echo "\n=== Processing: {$lang} ===\n";

    // Find words where at least one entry has frequency_rank but others don't
    $stmt = $pdo->prepare("
        SELECT word_normalized, MIN(frequency_rank) as best_rank, COUNT(*) as entries,
               SUM(frequency_rank IS NULL) as missing_freq
        FROM dict_words
        WHERE lang_code = ?
        GROUP BY word_normalized
        HAVING MIN(frequency_rank) IS NOT NULL AND SUM(frequency_rank IS NULL) > 0
    ");
    $stmt->execute([$lang]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "  Words needing propagation: " . count($words) . "\n";

    if ($dryRun) {
        echo "  [DRY RUN] Would update entries for " . count($words) . " words\n";
        // Show first 20
        foreach (array_slice($words, 0, 20) as $w) {
            echo "    {$w['word_normalized']}: best_rank={$w['best_rank']}, entries={$w['entries']}, missing={$w['missing_freq']}\n";
        }
        continue;
    }

    $updateStmt = $pdo->prepare("
        UPDATE dict_words
        SET frequency_rank = ?
        WHERE lang_code = ? AND word_normalized = ? AND frequency_rank IS NULL
    ");

    $updated = 0;
    foreach ($words as $w) {
        $updateStmt->execute([$w['best_rank'], $lang, $w['word_normalized']]);
        $updated += $updateStmt->rowCount();
    }

    echo "  Rows updated: {$updated}\n";

    // Now also propagate from frequency file to words not in DB yet
    // (conjugated forms that share a lemma with a frequency-ranked word)
    // This is Phase 2: lemma-based propagation
}

echo "\n=== Phase 2: Lemma-based frequency propagation ===\n";

foreach ($langs as $lang) {
    if ($lang !== 'es') {
        echo "  Skipping {$lang} (lemma logic only implemented for Spanish)\n";
        continue;
    }

    echo "\n=== Spanish lemma propagation ===\n";

    // Spanish verb conjugation patterns: if 'caminar' has rank X, then
    // 'camino', 'caminas', 'camina', 'caminamos', etc. should inherit it.
    // Also: noun/adjective forms (plural -s/-es, feminine -a/-o)

    // Step 1: Get all words WITH frequency that look like infinitives
    $stmt = $pdo->prepare("
        SELECT DISTINCT word_normalized, MIN(frequency_rank) as best_rank
        FROM dict_words
        WHERE lang_code = ? AND frequency_rank IS NOT NULL
        AND (word_normalized LIKE '%ar' OR word_normalized LIKE '%er' OR word_normalized LIKE '%ir')
        AND word_normalized NOT LIKE '% %'
        AND LENGTH(word_normalized) >= 4
        GROUP BY word_normalized
    ");
    $stmt->execute([$lang]);
    $infinitives = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Infinitives with frequency: " . count($infinitives) . "\n";

    // Step 2: For each infinitive, find conjugated forms without frequency
    // Common Spanish conjugation suffixes for regular verbs
    $arSuffixes = ['o','as','a','amos','áis','an','é','aste','ó','amos','aron',
        'aba','abas','aba','ábamos','aban','aré','arás','ará','aremos','arán',
        'aría','arías','aría','aríamos','arían','e','es','emos','en',
        'ando','ado','ada','ados','adas'];
    $erSuffixes = ['o','es','e','emos','éis','en','í','iste','ió','imos','ieron',
        'ía','ías','ía','íamos','ían','eré','erás','erá','eremos','erán',
        'ería','erías','ería','eríamos','erían','a','as','amos','an',
        'iendo','ido','ida','idos','idas'];
    $irSuffixes = ['o','es','e','imos','ís','en','í','iste','ió','imos','ieron',
        'ía','ías','ía','íamos','ían','iré','irás','irá','iremos','irán',
        'iría','irías','iría','iríamos','irían','a','as','amos','an',
        'iendo','ido','ida','idos','idas'];

    // Two update statements:
    // 1. Set rank for forms with no frequency data
    // 2. Lower rank for forms whose current rank is worse than the lemma's derived rank
    $updateNullStmt = $pdo->prepare("
        UPDATE dict_words
        SET frequency_rank = ?
        WHERE lang_code = ? AND word_normalized = ? AND frequency_rank IS NULL
    ");
    $updateHighStmt = $pdo->prepare("
        UPDATE dict_words
        SET frequency_rank = ?
        WHERE lang_code = ? AND word_normalized = ? AND frequency_rank > ?
    ");

    $propagated = 0;
    $lowered = 0;
    $checkedInf = 0;

    foreach ($infinitives as $inf) {
        $word = $inf['word_normalized'];
        $rank = $inf['best_rank'];
        $checkedInf++;

        // Determine verb type and stem
        $suffixes = [];
        if (substr($word, -2) === 'ar') {
            $stem = substr($word, 0, -2);
            $suffixes = $arSuffixes;
        } elseif (substr($word, -2) === 'er') {
            $stem = substr($word, 0, -2);
            $suffixes = $erSuffixes;
        } elseif (substr($word, -2) === 'ir') {
            $stem = substr($word, 0, -2);
            $suffixes = $irSuffixes;
        }

        if (empty($suffixes) || strlen($stem) < 2) continue;

        // Use a slightly worse rank for derived forms (rank + 500)
        // so they don't outrank directly-measured words
        $derivedRank = min($rank + 500, 50000);

        foreach ($suffixes as $suf) {
            $form = $stem . $suf;
            if ($form === $word) continue; // skip the infinitive itself

            // Fill in missing ranks
            $updateNullStmt->execute([$derivedRank, $lang, $form]);
            $propagated += $updateNullStmt->rowCount();

            // Lower ranks that are worse than the lemma's derived rank
            $updateHighStmt->execute([$derivedRank, $lang, $form, $derivedRank]);
            $lowered += $updateHighStmt->rowCount();
        }

        if ($checkedInf % 1000 === 0) {
            echo "  Checked {$checkedInf}/{" . count($infinitives) . "} infinitives, propagated {$propagated} forms...\n";
        }
    }

    echo "  Infinitives checked: {$checkedInf}\n";
    echo "  Conjugated forms propagated (new): {$propagated}\n";
    echo "  Conjugated forms lowered (existing): {$lowered}\n";

    // Step 3: Noun/adjective plural and gender forms
    echo "\n  === Noun/adjective form propagation ===\n";

    // Get words with frequency that are likely base forms (no -s/-es ending)
    $stmt = $pdo->prepare("
        SELECT word_normalized, MIN(frequency_rank) as best_rank
        FROM dict_words
        WHERE lang_code = ? AND frequency_rank IS NOT NULL
        AND word_normalized NOT LIKE '% %'
        AND LENGTH(word_normalized) >= 3
        GROUP BY word_normalized
    ");
    $stmt->execute([$lang]);
    $baseWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Base words with frequency: " . count($baseWords) . "\n";

    $nounPropagated = 0;
    $nounLowered = 0;
    foreach ($baseWords as $bw) {
        $w = $bw['word_normalized'];
        $rank = min($bw['best_rank'] + 200, 50000);

        // Generate potential derived forms
        $forms = [];
        // Plural: -s, -es
        $forms[] = $w . 's';
        if (preg_match('/[lnrdszx]$/', $w)) $forms[] = $w . 'es';
        // Gender swap: -o/-a, -os/-as
        if (substr($w, -1) === 'o') {
            $forms[] = substr($w, 0, -1) . 'a';
            $forms[] = $w . 's';
            $forms[] = substr($w, 0, -1) . 'as';
        } elseif (substr($w, -1) === 'a') {
            $forms[] = substr($w, 0, -1) . 'o';
            $forms[] = $w . 's';
            $forms[] = substr($w, 0, -1) . 'os';
        }
        // Diminutive: -ito/-ita (less common, skip for now)

        foreach (array_unique($forms) as $form) {
            $updateNullStmt->execute([$rank, $lang, $form]);
            $nounPropagated += $updateNullStmt->rowCount();

            // Also lower existing high ranks
            $updateHighStmt->execute([$rank, $lang, $form, $rank]);
            $nounLowered += $updateHighStmt->rowCount();
        }
    }

    echo "  Noun/adj forms propagated (new): {$nounPropagated}\n";
    echo "  Noun/adj forms lowered (existing): {$nounLowered}\n";
    echo "  Total propagated for {$lang}: " . ($propagated + $nounPropagated) . " new, " . ($lowered + $nounLowered) . " lowered\n";
}

// Final stats
foreach ($langs as $lang) {
    $r = $pdo->query("SELECT COUNT(*) as total, SUM(frequency_rank IS NOT NULL) as has_freq FROM dict_words WHERE lang_code='{$lang}'")->fetch(PDO::FETCH_ASSOC);
    $pct = round(($r['has_freq'] / $r['total']) * 100, 1);
    echo "\n  {$lang}: {$r['has_freq']}/{$r['total']} ({$pct}%) now have frequency data\n";
}

echo "\nDone. Next: php assign-cefr-levels.php --lang=es --force\n";
