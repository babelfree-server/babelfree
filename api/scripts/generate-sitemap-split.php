<?php
/**
 * Generate split sitemap system for babelfree.com
 *
 * Produces:
 *   sitemap.xml          — sitemap index pointing to all sub-sitemaps
 *   sitemap-main.xml     — non-dictionary pages (lines 1-495 of old sitemap)
 *   sitemap-dict-{lang}.xml — one per language, dictionary word pages with hreflang
 *
 * Run:
 *   php api/scripts/generate-sitemap-split.php
 *
 * Requires: database.php, dict_i18n.json
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$root = dirname(__DIR__, 2); // /home/babelfree.com/public_html
$today = date('Y-m-d');
$baseUrl = 'https://babelfree.com';

// Load i18n for URL prefixes (used for hreflang where available)
$i18nFile = __DIR__ . '/data/dict_i18n.json';
$allI18n = json_decode(file_get_contents($i18nFile), true) ?: [];

// Pull ALL supported languages from DB (not just i18n-translated ones)
$supportedLangs = array_column(
    $pdo->query("SELECT code FROM dict_languages ORDER BY code")->fetchAll(PDO::FETCH_ASSOC),
    'code'
);

// ============================================================
// 1. Generate sitemap-main.xml (non-dictionary pages)
// ============================================================
fwrite(STDERR, "=== Generating sitemap-main.xml ===\n");

$mainPages = [
    ['/', '1.0', 'weekly'],
    ['/learn-spanish', '0.9', 'weekly'],
    ['/storymap', '0.8', 'weekly'],
    ['/dictionary', '0.9', 'daily'],
    ['/glossary', '0.7', 'weekly'],
    ['/blog', '0.7', 'weekly'],
    ['/languages', '0.6', 'monthly'],
    ['/contact', '0.4', 'monthly'],
    ['/privacy', '0.3', 'yearly'],
    ['/services', '0.6', 'monthly'],
    // SEO keyword homepage variants
    ['/aprender-espanol', '0.8', 'monthly'],
    ['/impara-lo-spagnolo', '0.8', 'monthly'],
    ['/apprendre-l-espagnol', '0.8', 'monthly'],
    ['/spanisch-lernen', '0.8', 'monthly'],
    ['/curso-de-espanhol', '0.8', 'monthly'],
    ['/スペイン語を学ぶ', '0.8', 'monthly'],
    ['/스페인어-배우기', '0.8', 'monthly'],
    ['/学西班牙语', '0.8', 'monthly'],
    ['/учить-испанский', '0.8', 'monthly'],
    ['/spaans-leren', '0.8', 'monthly'],
    ['/تعلم-الإسبانية', '0.8', 'monthly'],
    // Dictionary SEO keyword pages (English)
    ['/spanish-dictionary', '0.8', 'monthly'],
    ['/spanish-to-english-dictionary', '0.8', 'monthly'],
    ['/spanish-dictionary-translator', '0.7', 'monthly'],
    ['/spanish-frequency-dictionary', '0.7', 'monthly'],
    ['/spanish-language-dictionary', '0.7', 'monthly'],
    ['/spanish-urban-dictionary', '0.6', 'monthly'],
    ['/rhyming-dictionary-in-spanish', '0.6', 'monthly'],
    ['/thesaurus-in-spanish', '0.7', 'monthly'],
    ['/dictionary-real-academia-espanola', '0.6', 'monthly'],
    ['/cambridge-dictionary-english-spanish', '0.6', 'monthly'],
    ['/oxford-spanish-dictionary', '0.6', 'monthly'],
    ['/collins-spanish-dictionary', '0.6', 'monthly'],
    ['/linguee-english-to-spanish', '0.6', 'monthly'],
    ['/wordreference-spanish', '0.6', 'monthly'],
    ['/spanish-dictionary-words', '0.7', 'monthly'],
    ['/pocket-spanish-dictionary', '0.6', 'monthly'],
    ['/oxford-picture-dictionary-english-spanish', '0.5', 'monthly'],
    // Dictionary SEO keyword pages (Chinese)
    ['/chinese-spanish-dictionary', '0.7', 'monthly'],
    ['/english-chinese-dictionary', '0.7', 'monthly'],
    ['/traditional-chinese-dictionary', '0.6', 'monthly'],
    ['/how-to-say-in-spanish-chinese', '0.6', 'monthly'],
    // Dictionary SEO keyword pages (Dutch)
    ['/spaans-woordenboek', '0.7', 'monthly'],
    ['/woordenboek-spaans-naar-nederlands', '0.7', 'monthly'],
    ['/ned-spaans-woordenboek', '0.6', 'monthly'],
    ['/engels-spaans-woordenboek', '0.6', 'monthly'],
    ['/spaans-engels-woordenboek', '0.6', 'monthly'],
    ['/spaanse-woorden', '0.7', 'monthly'],
    ['/betekenis-van', '0.7', 'monthly'],
    ['/engels-spaans', '0.6', 'monthly'],
    ['/mijnwoordenboek-spaans', '0.6', 'monthly'],
    // Dictionary SEO keyword pages (German)
    ['/spanisch-deutsch-woerterbuch', '0.7', 'monthly'],
    ['/deutsch-spanisch-uebersetzer', '0.7', 'monthly'],
    ['/spanisch-zu-deutsch-uebersetzer', '0.7', 'monthly'],
    ['/uebersetzer-deutsch-spanisch-kostenlos', '0.7', 'monthly'],
    ['/deutsch-spanisch-uebersetzen', '0.6', 'monthly'],
    ['/google-uebersetzer-spanisch-deutsch', '0.6', 'monthly'],
    ['/spanisch-uebersetzung-deutsch', '0.6', 'monthly'],
    ['/deutsch-spanisch-woerterbuch', '0.7', 'monthly'],
    ['/spanisches-woerterbuch', '0.7', 'monthly'],
    ['/pons-spanisch-deutsch', '0.6', 'monthly'],
    ['/leo-spanisch-deutsch', '0.6', 'monthly'],
    ['/linguee-deutsch-spanisch', '0.6', 'monthly'],
    ['/deepl-spanisch-deutsch', '0.6', 'monthly'],
    ['/uebersetzen-spanisch-deutsch-text', '0.6', 'monthly'],
    // Dictionary SEO keyword pages (Korean)
    ['/스페인어-사전', '0.7', 'monthly'],
    ['/한국어-스페인어-사전', '0.7', 'monthly'],
    ['/무료-스페인어-사전', '0.6', 'monthly'],
    // Dictionary SEO keyword pages (Russian)
    ['/испано-русский-перевод', '0.7', 'monthly'],
    ['/испано-русский-словарь', '0.7', 'monthly'],
    ['/словарь-испанского-языка', '0.7', 'monthly'],
    // Dictionary SEO keyword pages (Japanese)
    ['/スペイン語辞書', '0.7', 'monthly'],
    ['/日本語スペイン語辞書', '0.7', 'monthly'],
    ['/無料スペイン語辞書', '0.6', 'monthly'],
    // Dictionary SEO keyword pages (Spanish)
    ['/diccionario-espanol', '0.8', 'monthly'],
    ['/diccionario-ingles-espanol', '0.8', 'monthly'],
    ['/diccionario-de-la-rae', '0.7', 'monthly'],
    ['/diccionario-de-sinonimos', '0.7', 'monthly'],
    ['/diccionario-frances-espanol', '0.7', 'monthly'],
    ['/diccionario-italiano-espanol', '0.7', 'monthly'],
    ['/diccionario-portugues-espanol', '0.7', 'monthly'],
    ['/significado-de-palabras', '0.7', 'monthly'],
    // Dictionary SEO keyword pages (Italian)
    ['/dizionario-spagnolo', '0.7', 'monthly'],
    ['/dizionario-spagnolo-italiano', '0.7', 'monthly'],
    ['/traduzione-italiano-spagnolo', '0.7', 'monthly'],
    ['/significato-di', '0.7', 'monthly'],
    // Dictionary SEO keyword pages (French)
    ['/dictionnaire-francais-espagnol', '0.7', 'monthly'],
    ['/traduction-francais-espagnol', '0.7', 'monthly'],
    ['/dictionnaire-francais', '0.7', 'monthly'],
    ['/dictionnaire-francais-anglais', '0.7', 'monthly'],
    ['/traduction-anglais-francais', '0.7', 'monthly'],
    // Dictionary SEO keyword pages (Portuguese)
    ['/dicionario-espanhol', '0.7', 'monthly'],
    ['/dicionario-espanhol-portugues', '0.7', 'monthly'],
    ['/dicionario-portugues-espanhol', '0.7', 'monthly'],
    ['/significado-de', '0.7', 'monthly'],
    ['/dicionario-real-academia-espanhola', '0.6', 'monthly'],
    // Long-tail SEO pages
    ['/learn-spanish-online', '0.7', 'monthly'],
    ['/learn-spanish-free', '0.7', 'monthly'],
    ['/learn-spanish-fast', '0.7', 'monthly'],
    ['/learn-spanish-for-beginners', '0.7', 'monthly'],
    ['/learn-spanish-for-kids', '0.6', 'monthly'],
    ['/learn-spanish-as-an-adult', '0.6', 'monthly'],
    ['/learn-spanish-at-home', '0.6', 'monthly'],
    ['/learn-spanish-with-ai', '0.7', 'monthly'],
    ['/learn-spanish-in-medellin', '0.6', 'monthly'],
    ['/learn-spanish-podcast', '0.6', 'monthly'],
    ['/learn-to-speak-spanish', '0.6', 'monthly'],
    ['/best-app-to-learn-spanish', '0.7', 'monthly'],
    ['/best-program-to-learn-spanish', '0.6', 'monthly'],
    ['/best-way-to-learn-spanish', '0.7', 'monthly'],
    ['/easiest-way-to-learn-spanish', '0.6', 'monthly'],
    ['/how-long-does-it-take-to-learn-spanish', '0.6', 'monthly'],
    ['/is-spanish-hard-to-learn', '0.6', 'monthly'],
    ['/why-learn-spanish', '0.6', 'monthly'],
    ['/learn-colombian-spanish', '0.7', 'monthly'],
    ['/learn-mexican-spanish', '0.6', 'monthly'],
    // Castilian
    ['/castilian', '0.6', 'monthly'],
    ['/learn-castilian-spanish', '0.6', 'monthly'],
    ['/castilian-grammar', '0.5', 'monthly'],
    ['/castilian-pronunciation', '0.5', 'monthly'],
    ['/castilian-vocabulary', '0.5', 'monthly'],
    // Services
    ['/translation-services', '0.5', 'monthly'],
    ['/document-translation', '0.5', 'monthly'],
    ['/website-localization', '0.5', 'monthly'],
    ['/business-communications', '0.5', 'monthly'],
    ['/cultural-adaptation', '0.5', 'monthly'],
];

// Auto-discover Jaguar language pages (es-{lang}.html → /el-viaje-del-jaguar/{lang}/)
foreach (glob($root . '/es-*.html') as $f) {
    $lang = substr(basename($f, '.html'), 3);
    if ($lang && strlen($lang) >= 2 && strlen($lang) <= 6) {
        $mainPages[] = ["/el-viaje-del-jaguar/$lang/", '0.5', 'monthly'];
    }
}

$mainXml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$mainXml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($mainPages as [$path, $priority, $freq]) {
    $mainXml .= "  <url>\n";
    $mainXml .= "    <loc>{$baseUrl}{$path}</loc>\n";
    $mainXml .= "    <lastmod>{$today}</lastmod>\n";
    $mainXml .= "    <changefreq>{$freq}</changefreq>\n";
    $mainXml .= "    <priority>{$priority}</priority>\n";
    $mainXml .= "  </url>\n";
}

$mainXml .= '</urlset>' . "\n";

file_put_contents($root . '/sitemap-main.xml', $mainXml);
$mainCount = count($mainPages);
fwrite(STDERR, "  Written: sitemap-main.xml ({$mainCount} URLs)\n");

// ============================================================
// 2. Generate sitemap-dict-{lang}.xml for each language
// ============================================================
// Streaming approach: process one language at a time using cursor queries.
// Hreflangs are looked up per-word to avoid building a massive in-memory graph.

fwrite(STDERR, "\n=== Generating dictionary sitemaps (streaming) ===\n");

/**
 * Build URL for a dictionary word page.
 */
function dictUrl(string $lang, string $word, array $allI18n): string {
    $prefix = $allI18n[$lang]['url_prefix'] ?? 'meaning-of';
    $slug = $prefix . '-' . rawurlencode(mb_strtolower($word));
    return "https://babelfree.com/dictionary/{$lang}/{$slug}";
}

/**
 * Look up hreflang alternates for a word_id via DB query (no in-memory graph).
 * Returns: [ lang => url, ... ] including the word itself.
 */
function getHreflangsStreaming(PDO $pdo, int $wordId, string $lang, string $word, array $allI18n): array {
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $pdo->prepare("
            SELECT w.lang_code, w.word
            FROM dict_translations t
            JOIN dict_words w ON w.id = CASE WHEN t.source_word_id = ? THEN t.target_word_id ELSE t.source_word_id END
            JOIN dict_definitions d ON d.word_id = w.id
            WHERE (t.source_word_id = ? OR t.target_word_id = ?)
            GROUP BY w.lang_code, w.word
        ");
    }

    $result = [$lang => dictUrl($lang, $word, $allI18n)];

    $stmt->execute([$wordId, $wordId, $wordId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $seen = [$lang => true];

    foreach ($rows as $r) {
        $linkedLang = $r['lang_code'];
        if (isset($seen[$linkedLang])) continue;
        $seen[$linkedLang] = true;
        $result[$linkedLang] = dictUrl($linkedLang, $r['word'], $allI18n);
    }

    return $result;
}

// Prepare a statement to count words per lang
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT w.id)
    FROM dict_words w
    INNER JOIN dict_definitions d ON d.word_id = w.id
    WHERE w.lang_code = ?
");

$sitemapFiles = [];  // lang => [filename, filename, ...]
$totalDictUrls = 0;
$BATCH_SIZE = 5000;
$MAX_URLS_PER_SITEMAP = 45000;  // Google limit is 50K; leave margin

foreach ($supportedLangs as $lang) {
    $countStmt->execute([$lang]);
    $totalWords = (int)$countStmt->fetchColumn();

    if ($totalWords === 0) {
        fwrite(STDERR, "  Skipping {$lang}: no words with definitions\n");
        continue;
    }

    $numParts = (int)ceil($totalWords / $MAX_URLS_PER_SITEMAP);
    fwrite(STDERR, "\n=== Generating sitemap-dict-{$lang} ({$totalWords} words, {$numParts} part(s)) ===\n");

    // Stream words in batches using LIMIT/OFFSET
    $offset = 0;
    $count = 0;
    $partNum = 1;
    $partCount = 0;
    $fp = null;
    $sitemapFiles[$lang] = [];

    $wordStmt = $pdo->prepare("
        SELECT DISTINCT w.id, w.word
        FROM dict_words w
        INNER JOIN dict_definitions d ON d.word_id = w.id
        WHERE w.lang_code = ?
        ORDER BY w.word
        LIMIT ? OFFSET ?
    ");

    // Helper to open a new sitemap part file
    $openPart = function() use (&$fp, &$partNum, &$partCount, &$sitemapFiles, $root, $lang, $numParts) {
        if ($fp !== null) {
            fwrite($fp, '</urlset>' . "\n");
            fclose($fp);
        }
        $filename = $numParts > 1
            ? "sitemap-dict-{$lang}-{$partNum}.xml"
            : "sitemap-dict-{$lang}.xml";
        $fp = fopen($root . '/' . $filename, 'w');
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($fp, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n");
        $sitemapFiles[$lang][] = $filename;
        $partCount = 0;
    };

    $openPart();

    while ($offset < $totalWords) {
        $wordStmt->execute([$lang, $BATCH_SIZE, $offset]);
        $batch = $wordStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($batch)) break;

        foreach ($batch as $w) {
            // Split into new part if current one is full
            if ($partCount >= $MAX_URLS_PER_SITEMAP) {
                $partNum++;
                $openPart();
            }

            $wordId = (int)$w['id'];
            $wordText = $w['word'];
            $url = dictUrl($lang, $wordText, $allI18n);

            fwrite($fp, "  <url>\n");
            fwrite($fp, "    <loc>{$url}</loc>\n");
            fwrite($fp, "    <lastmod>{$today}</lastmod>\n");
            fwrite($fp, "    <changefreq>monthly</changefreq>\n");
            fwrite($fp, "    <priority>0.5</priority>\n");

            // Skip per-word hreflang lookups for large languages (>50K words) — too slow
            if ($totalWords <= 50000) {
                $hreflangs = getHreflangsStreaming($pdo, $wordId, $lang, $wordText, $allI18n);
                if (count($hreflangs) > 1) {
                    foreach ($hreflangs as $hrefLang => $hrefUrl) {
                        fwrite($fp, "    <xhtml:link rel=\"alternate\" hreflang=\"{$hrefLang}\" href=\"{$hrefUrl}\"/>\n");
                    }
                }
            }

            fwrite($fp, "  </url>\n");
            $count++;
            $partCount++;
        }

        $offset += $BATCH_SIZE;
        if ($offset % 50000 === 0 || $offset >= $totalWords) {
            fwrite(STDERR, "  {$lang}: {$count}/{$totalWords} (part {$partNum})...\n");
        }
    }

    // Close final part
    if ($fp !== null) {
        fwrite($fp, '</urlset>' . "\n");
        fclose($fp);
        $fp = null;
    }

    $totalDictUrls += $count;
    fwrite(STDERR, "  {$lang}: {$count} URLs written to " . count($sitemapFiles[$lang]) . " file(s)\n");
}

// ============================================================
// 3. Generate sitemap.xml (sitemap index)
// ============================================================
fwrite(STDERR, "\n=== Generating sitemap.xml (index) ===\n");

$indexXml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$indexXml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Main sitemap
$indexXml .= "  <sitemap>\n";
$indexXml .= "    <loc>{$baseUrl}/sitemap-main.xml</loc>\n";
$indexXml .= "    <lastmod>{$today}</lastmod>\n";
$indexXml .= "  </sitemap>\n";

// Dictionary sitemaps (in supported-lang order)
foreach ($supportedLangs as $lang) {
    if (!isset($sitemapFiles[$lang])) continue;
    foreach ($sitemapFiles[$lang] as $filename) {
        $indexXml .= "  <sitemap>\n";
        $indexXml .= "    <loc>{$baseUrl}/{$filename}</loc>\n";
        $indexXml .= "    <lastmod>{$today}</lastmod>\n";
        $indexXml .= "  </sitemap>\n";
    }
}

$indexXml .= '</sitemapindex>' . "\n";

file_put_contents($root . '/sitemap.xml', $indexXml);

$totalFiles = array_sum(array_map('count', $sitemapFiles));
fwrite(STDERR, "\n=== SUMMARY ===\n");
fwrite(STDERR, "  sitemap.xml (index): 1 + {$totalFiles} sub-sitemaps\n");
fwrite(STDERR, "  sitemap-main.xml: non-dictionary pages\n");
foreach ($sitemapFiles as $lang => $filenames) {
    foreach ($filenames as $filename) {
        fwrite(STDERR, "  {$filename}\n");
    }
}
fwrite(STDERR, "  Total dictionary URLs: " . number_format($totalDictUrls) . "\n");
fwrite(STDERR, "\nDone.\n");
