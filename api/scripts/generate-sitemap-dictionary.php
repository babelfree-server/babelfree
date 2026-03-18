<?php
/**
 * Generate dictionary sitemap entries for all languages with i18n support.
 * Outputs XML entries for inclusion in sitemap.xml with hreflang cross-links.
 *
 * Loops through all languages configured in dict_i18n.json and generates
 * URLs with full hreflang cross-linking between all available translations.
 *
 * Run:
 *   php generate-sitemap-dictionary.php > /tmp/dict-sitemap.xml
 *
 * Then replace the dictionary word section in sitemap.xml with the output.
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Load i18n for URL prefixes
$i18nFile = __DIR__ . '/data/dict_i18n.json';
$allI18n = file_exists($i18nFile) ? json_decode(file_get_contents($i18nFile), true) : [];

$today = date('Y-m-d');

// Get all languages we support (from i18n config)
$supportedLangs = array_keys($allI18n);

// Get all Spanish words that have at least one definition
$stmt = $pdo->query("
    SELECT DISTINCT w.id, w.word
    FROM dict_words w
    INNER JOIN dict_definitions d ON d.word_id = w.id
    WHERE w.lang_code = 'es'
    ORDER BY w.word
");
$spanishWords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare statement to find translations for a given Spanish word in a target language
$stmtTrans = $pdo->prepare("
    SELECT tw.word
    FROM dict_translations dt
    JOIN dict_words tw ON tw.id = dt.target_word_id
    WHERE dt.source_word_id = ? AND tw.lang_code = ?
    ORDER BY tw.frequency_rank ASC
    LIMIT 1
");

echo "  <!-- ============================================ -->\n";
echo "  <!-- 13. DICTIONARY WORD PAGES (" . count($spanishWords) . " words, " . count($supportedLangs) . " languages) -->\n";

$urlCounts = [];
foreach ($supportedLangs as $lang) {
    $urlCounts[$lang] = 0;
}

foreach ($spanishWords as $sw) {
    $esWordId = (int) $sw['id'];

    // Build URL map for all languages where a translation exists
    $langUrls = [];

    // Spanish always present
    $esPrefix = $allI18n['es']['url_prefix'] ?? 'significado-de';
    $esWord = mb_strtolower($sw['word']);
    $esSlug = $esPrefix . '-' . rawurlencode($esWord);
    $langUrls['es'] = "https://babelfree.com/dictionary/es/{$esSlug}";

    // Find translations in other supported languages
    foreach ($supportedLangs as $lang) {
        if ($lang === 'es') continue;

        $stmtTrans->execute([$esWordId, $lang]);
        $transRow = $stmtTrans->fetch(PDO::FETCH_ASSOC);

        if ($transRow) {
            $prefix = $allI18n[$lang]['url_prefix'] ?? 'meaning-of';
            $transWord = mb_strtolower($transRow['word']);
            $slug = $prefix . '-' . rawurlencode($transWord);
            $langUrls[$lang] = "https://babelfree.com/dictionary/{$lang}/{$slug}";
        }
    }

    // Generate <url> entries for each language variant
    foreach ($langUrls as $lang => $url) {
        echo "  <url>\n";
        echo "    <loc>{$url}</loc>\n";
        echo "    <lastmod>{$today}</lastmod>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.5</priority>\n";

        // Add hreflang links to ALL available language variants
        foreach ($langUrls as $hrefLang => $hrefUrl) {
            echo "    <xhtml:link rel=\"alternate\" hreflang=\"{$hrefLang}\" href=\"{$hrefUrl}\"/>\n";
        }

        echo "  </url>\n";
        $urlCounts[$lang]++;
    }
}

// Summary to stderr
fwrite(STDERR, "Generated sitemap entries:\n");
$totalUrls = 0;
foreach ($urlCounts as $lang => $count) {
    if ($count > 0) {
        fwrite(STDERR, "  $lang: $count entries\n");
        $totalUrls += $count;
    }
}
fwrite(STDERR, "Total URLs: $totalUrls\n");
