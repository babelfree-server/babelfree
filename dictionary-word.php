<?php
/**
 * SEO Dictionary Word Page — Multilingual
 * Serves /dictionary/{lang}/{prefix}-{word} as a fully rendered HTML page for search engines.
 * Handles URL prefix validation, 301 redirects, and database-backed lookups for all languages.
 * Falls back to curated JSON for lang=es during transition, then to SPA for unknown words.
 */

// ── Load i18n strings ───────────────────────────────────────────────
$i18nFile = __DIR__ . '/api/scripts/data/dict_i18n.json';
$allI18n = file_exists($i18nFile) ? json_decode(file_get_contents($i18nFile), true) : [];
$defaultI18n = $allI18n['en'] ?? [];

// ── Extract URL segments ────────────────────────────────────────────
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Expected: dictionary/{lang}/{slug}
if (count($segments) < 3 || $segments[0] !== 'dictionary') {
    // Language index page — rich SEO landing page
    if (count($segments) === 2 && $segments[0] === 'dictionary' && preg_match('/^[a-z]{2}(-[a-z]{2,3})?$/', $segments[1])) {
        $landingLang = $segments[1];
        $landingI18n = $allI18n[$landingLang] ?? null;
        if ($landingI18n) {
            require_once __DIR__ . '/api/config/database.php';
            $pdo = getDB();
            include __DIR__ . '/dictionary-landing.php';
            exit;
        }
        // Unsupported language — fall back to SPA
        readfile(__DIR__ . '/dictionary.html');
        exit;
    }
    header('Location: /dictionary');
    exit;
}

$lang = $segments[1];
$rawSlug = urldecode(implode('/', array_slice($segments, 2))); // rejoin in case of multi-segment

// ── i18n for this language ──────────────────────────────────────────
$i18n = $allI18n[$lang] ?? $defaultI18n;
$expectedPrefix = $i18n['url_prefix'] ?? null;

// ── URL prefix handling ─────────────────────────────────────────────
// Determine if the slug starts with the correct prefix for this language
$wordSlug = $rawSlug;
$hasCorrectPrefix = false;

if ($expectedPrefix && strpos($rawSlug, $expectedPrefix . '-') === 0) {
    // Correct prefix — strip it
    $wordSlug = substr($rawSlug, strlen($expectedPrefix) + 1);
    $hasCorrectPrefix = true;
} else {
    // Check if slug starts with ANY known prefix (from a different language)
    foreach ($allI18n as $otherLang => $otherI18n) {
        $otherPrefix = $otherI18n['url_prefix'] ?? '';
        if ($otherPrefix && strpos($rawSlug, $otherPrefix . '-') === 0) {
            $wordSlug = substr($rawSlug, strlen($otherPrefix) + 1);
            break;
        }
    }
}

// If no prefix or wrong prefix, 301 redirect to correct prefix URL
if ($expectedPrefix && !$hasCorrectPrefix) {
    $correctUrl = '/dictionary/' . $lang . '/' . $expectedPrefix . '-' . urlencode($wordSlug);
    header('Location: ' . $correctUrl, true, 301);
    exit;
}

// ── Database lookup ─────────────────────────────────────────────────
require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/models/Dictionary.php';

$pdo = getDB();
$model = new Dictionary($pdo);
$entry = $model->lookup($lang, $wordSlug);

// ── Lemma redirect: plural/inflected → base form ────────────────────
if ($entry && isset($entry['_redirect_to'])) {
    $lemma = $entry['_redirect_to'];
    $lemmaSlug = $expectedPrefix
        ? $expectedPrefix . '-' . urlencode(mb_strtolower($lemma, 'UTF-8'))
        : urlencode(mb_strtolower($lemma, 'UTF-8'));
    header('Location: /dictionary/' . $lang . '/' . $lemmaSlug, true, 301);
    exit;
}

// ── "Did you mean?" redirect: fuzzy match → first suggestion ────────
if ($entry && isset($entry['_did_you_mean'])) {
    $suggestion = $entry['_did_you_mean'][0];
    $sugSlug = $expectedPrefix
        ? $expectedPrefix . '-' . urlencode(mb_strtolower($suggestion, 'UTF-8'))
        : urlencode(mb_strtolower($suggestion, 'UTF-8'));
    header('Location: /dictionary/' . $lang . '/' . $sugSlug, true, 302);
    exit;
}

// ── Fallback: curated JSON for lang=es ──────────────────────────────
$entryLevel = '';
if (!$entry && $lang === 'es') {
    $dataDir = __DIR__ . '/api/scripts/data/';
    $levels = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
    $curatedEntry = null;

    foreach ($levels as $level) {
        $file = $dataDir . "dict_entries_{$level}.json";
        if (!file_exists($file)) continue;
        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;
        foreach ($data as $item) {
            if (mb_strtolower($item['word']) === mb_strtolower($wordSlug)) {
                $curatedEntry = $item;
                $entryLevel = strtoupper($level);
                break 2;
            }
        }
    }

    if ($curatedEntry) {
        // Build a DB-like entry from curated data
        $entry = [
            'word' => $curatedEntry['word'],
            'part_of_speech' => $curatedEntry['pos'] ?? '',
            'gender' => $curatedEntry['gender'] ?? null,
            'pronunciation_ipa' => $curatedEntry['ipa'] ?? '',
            'cefr_level' => $entryLevel,
            'definitions' => $curatedEntry['def'] ? [['definition' => $curatedEntry['def'], 'usage_note' => null, 'lang_code' => 'es']] : [],
            'translations' => [],
            'examples' => $curatedEntry['example'] ? [['sentence' => $curatedEntry['example'], 'translation' => null, 'source' => null]] : [],
            'conjugations' => [],
            'related' => [],
            '_curated_meaning' => $curatedEntry['meaning'] ?? '',
        ];
    }
}

// ── If not found anywhere, serve the SPA (it handles its own 404) ──
if (!$entry) {
    include __DIR__ . '/dictionary.html';
    exit;
}

// ── Prepare display variables ───────────────────────────────────────
$word = htmlspecialchars($entry['word'], ENT_QUOTES, 'UTF-8');
$pos = $entry['part_of_speech'] ?? '';
$gender = $entry['gender'] ?? null;
$ipa = $entry['pronunciation_ipa'] ?? '';
$cefrLevel = $entry['cefr_level'] ?? $entryLevel;

// Definitions
$definitions = $entry['definitions'] ?? [];
$firstDef = !empty($definitions) ? $definitions[0]['definition'] : '';

// Curated meaning (ES→EN for backward compat)
$curatedMeaning = $entry['_curated_meaning'] ?? '';

// ── i18n-powered display strings ────────────────────────────────────
$titleWord = ucfirst($entry['word']);
$wordLower = mb_strtolower($entry['word']);

// POS label
$posLabels = $i18n['pos_labels'] ?? [];
$posLabel = $posLabels[$pos] ?? ucfirst($pos);

// Gender label
$genderLabels = $i18n['gender_labels'] ?? [];
$genderLabel = $genderLabels[$gender] ?? '';

// CEFR description
$cefrLabels = $i18n['cefr_labels'] ?? [];
$levelDesc = $cefrLabels[$cefrLevel] ?? '';

// Page title and meta
$pageTitle = str_replace('{word}', $titleWord, $i18n['page_title_seo'] ?? $i18n['page_title_format'] ?? '{word} | Babel Free');
$defText = htmlspecialchars($firstDef, ENT_QUOTES, 'UTF-8');
$ipaText = htmlspecialchars($ipa, ENT_QUOTES, 'UTF-8');

$metaTemplate = $firstDef
    ? ($i18n['meta_desc_with_meaning'] ?? '{word}: {def}. CEFR {level}. Free dictionary by Babel Free.')
    : ($i18n['meta_desc_without_meaning'] ?? '{word} — CEFR {level}. Free dictionary by Babel Free.');
$metaDesc = str_replace(
    ['{word}', '{def}', '{level}', '{ipa}'],
    [$wordLower, strip_tags(html_entity_decode($firstDef)), $cefrLevel, $ipa],
    $metaTemplate
);
$metaDesc = htmlspecialchars(mb_substr(strip_tags(html_entity_decode($metaDesc)), 0, 160), ENT_QUOTES, 'UTF-8');

// Canonical URL
$urlPrefix = $i18n['url_prefix'] ?? 'significado-de';
$canonicalUrl = "https://babelfree.com/dictionary/{$lang}/{$urlPrefix}-" . urlencode($wordLower);

// OG title
$ogTitle = str_replace('{word}', $word, $i18n['og_title'] ?? "Meaning of {word} | Babel Free");

// Section headers
$sectionDef = $i18n['section_definitions'] ?? 'Definitions';
$sectionExamples = $i18n['section_examples'] ?? 'Examples';
$cefrSectionTitle = $i18n['cefr_section_title'] ?? 'CEFR level';
$seeAlsoTitle = $i18n['see_also'] ?? 'See also';
$ctaTitle = $i18n['cta_title'] ?? 'Learn this word in context';
$ctaText = str_replace('{word}', "<em>{$word}</em>", $i18n['cta_text'] ?? "See <em>{word}</em> in context.");
$ctaButton = $i18n['cta_button'] ?? 'Start Free Course';
$backLabel = $i18n['back_to_search'] ?? 'Back to Dictionary';
$breadHome = $i18n['breadcrumb_home'] ?? 'Home';
$breadDict = $i18n['breadcrumb_dictionary'] ?? 'Dictionary';

// CEFR description text
$cefrDescText = str_replace(
    ['{level}', '{desc}'],
    [$cefrLevel, mb_strtolower($levelDesc)],
    $i18n['cefr_description'] ?? "This word is part of the CEFR {level} vocabulary — {desc} level."
);

// Language name for breadcrumb
$langDisplayName = $i18n['lang_name'] ?? ucfirst($lang);

// ── Load "See also" neighbors from database ─────────────────────────
$neighbors = [];
if ($pos) {
    $normalized = mb_strtolower($wordSlug);
    if (function_exists('transliterator_transliterate')) {
        $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
    }
    // Fast random neighbors: pick from a narrow alphabetical window instead of ORDER BY RAND() on millions of rows
    $stmt = $pdo->prepare(
        'SELECT word FROM dict_words
         WHERE lang_code = ? AND part_of_speech = ? AND word_normalized > ?
         ORDER BY word_normalized LIMIT 6'
    );
    $stmt->execute([$lang, $pos, $normalized]);
    $neighbors = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'word');
    if (count($neighbors) < 6) {
        $stmt2 = $pdo->prepare(
            'SELECT word FROM dict_words
             WHERE lang_code = ? AND part_of_speech = ? AND word_normalized < ?
             ORDER BY word_normalized DESC LIMIT ?'
        );
        $stmt2->execute([$lang, $pos, $normalized, 6 - count($neighbors)]);
        $neighbors = array_merge($neighbors, array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'word'));
    }
}

// Fallback to curated JSON for es neighbors if DB returned empty
if (empty($neighbors) && $lang === 'es') {
    $dataDir = __DIR__ . '/api/scripts/data/';
    $levels = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
    foreach ($levels as $level) {
        $file = $dataDir . "dict_entries_{$level}.json";
        if (!file_exists($file)) continue;
        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;
        foreach ($data as $item) {
            if (mb_strtolower($item['word']) !== mb_strtolower($wordSlug) && ($item['pos'] ?? '') === $pos) {
                $neighbors[] = $item['word'];
            }
            if (count($neighbors) >= 12) break;
        }
        if (count($neighbors) >= 12) break;
    }
    shuffle($neighbors);
    $neighbors = array_slice($neighbors, 0, 6);
}

// ── hreflang alternates ─────────────────────────────────────────────
$hreflangLinks = [];
// Find translations to build alternate URLs
if (!empty($entry['translations'])) {
    $seenLangs = [$lang => true];
    foreach ($entry['translations'] as $tr) {
        $trLang = $tr['lang'] ?? '';
        if (!$trLang || isset($seenLangs[$trLang])) continue;
        if (!isset($allI18n[$trLang])) continue; // only supported languages
        $trPrefix = $allI18n[$trLang]['url_prefix'] ?? '';
        if ($trPrefix) {
            $trWord = mb_strtolower($tr['word']);
            $hreflangLinks[] = [
                'lang' => $trLang,
                'url'  => "https://babelfree.com/dictionary/{$trLang}/{$trPrefix}-" . urlencode($trWord),
            ];
            $seenLangs[$trLang] = true;
        }
    }
}

// ── JSON-LD structured data ─────────────────────────────────────────
$dictName = $i18n['dictionary_name'] ?? 'Babel Free Dictionary';
$jsonLdData = [
    '@context' => 'https://schema.org',
    '@type' => 'DefinedTerm',
    'name' => $entry['word'],
    'description' => $firstDef,
    'inDefinedTermSet' => [
        '@type' => 'DefinedTermSet',
        'name' => $dictName,
        'url' => 'https://babelfree.com/dictionary'
    ],
    'termCode' => $cefrLevel,
    'url' => $canonicalUrl,
    'inLanguage' => $lang,
];
// Add pronunciation if available
if ($ipa) {
    $jsonLdData['pronunciation'] = ['@type' => 'PronounceableText', 'phoneticText' => $ipa];
}
// Add translations as sameAs links
$translations = $entry['translations'] ?? [];
if (!empty($translations)) {
    $sameAs = [];
    $seenLangs = [];
    foreach ($translations as $tr) {
        $trLang = $tr['lang'] ?? '';
        if ($trLang && !isset($seenLangs[$trLang]) && isset($allI18n[$trLang])) {
            $trPrefix = $allI18n[$trLang]['url_prefix'] ?? '';
            $trWord = mb_strtolower($tr['word'], 'UTF-8');
            $sameAs[] = 'https://babelfree.com/dictionary/' . $trLang . '/' . $trPrefix . '-' . rawurlencode($trWord);
            $seenLangs[$trLang] = true;
        }
    }
    if (!empty($sameAs)) {
        $jsonLdData['sameAs'] = $sameAs;
    }
}
$jsonLd = json_encode($jsonLdData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ── BreadcrumbList JSON-LD ───────────────────────────────────────────
$breadcrumbLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://babelfree.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dictionaries', 'item' => 'https://babelfree.com/dictionary/'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => ($allI18n[$lang]['lang_name'] ?? ucfirst($lang)) . ' Dictionary', 'item' => "https://babelfree.com/dictionary/{$lang}/"],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $entry['word']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:title" content="<?= $ogTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:site_name" content="Babel Free">
    <meta property="og:image" content="https://babelfree.com/assets/og-babel.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $ogTitle ?>">
    <meta name="twitter:description" content="<?= $metaDesc ?>">
    <meta name="twitter:image" content="https://babelfree.com/assets/og-babel.png">
    <meta name="theme-color" content="#F4A5A5">
    <link rel="icon" type="image/png" href="/assets/tower-logo.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<?php foreach ($hreflangLinks as $hl): ?>
    <link rel="alternate" hreflang="<?= $hl['lang'] ?>" href="<?= $hl['url'] ?>">
<?php endforeach; ?>
    <link rel="alternate" hreflang="<?= $lang ?>" href="<?= $canonicalUrl ?>">
    <script type="application/ld+json"><?= $jsonLd ?></script>
    <script type="application/ld+json"><?= $breadcrumbLd ?></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lucida+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/footer.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Lucida Sans', Arial, sans-serif; color: #333; line-height: 1.7; background: #fafbfc; }
        .header { position: fixed; top: 0; width: 100%; background: #fff; z-index: 1000; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 70px; }
        .logo-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .main-logo img { height: 45px; }
        .nav-menu { display: flex; list-style: none; gap: 2rem; align-items: center; }
        .nav-link { text-decoration: none; color: #333; font-size: 0.95rem; font-weight: 500; transition: color 0.3s; }
        .nav-link:hover { color: #F4A5A5; }
        .cta-nav { background: #F4A5A5; color: #fff; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .mobile-menu-toggle { display: none; cursor: pointer; flex-direction: column; gap: 5px; }
        .menu-bar { width: 25px; height: 3px; background: #333; border-radius: 3px; }
        .mobile-menu { display: none; }
        .breadcrumb { max-width: 900px; margin: 0 auto; padding: 6rem 2rem 0; font-size: 0.9rem; color: #888; }
        .breadcrumb a { color: #F4A5A5; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .word-page { max-width: 900px; margin: 0 auto; padding: 2rem 2rem 4rem; }
        .word-header {
            margin-bottom: 2rem; padding: 2rem 2.5rem;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
            border-radius: 20px; position: relative; overflow: hidden;
        }
        .word-header::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(244,165,165,0.15), transparent);
            pointer-events: none;
        }
        .word-header h1 { font-family: 'Bebas Neue', sans-serif; font-size: 3.5rem; color: #fff; letter-spacing: 3px; margin-bottom: 0.5rem; line-height: 1.1; position: relative; }
        .word-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1rem; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pos { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); }
        .badge-gender { background: rgba(244,165,165,0.2); color: #F4A5A5; }
        .badge-cefr { background: rgba(39,174,96,0.2); color: #6fcf97; }
        .ipa { font-size: 1.2rem; color: rgba(255,255,255,0.5); font-style: italic; margin-bottom: 0; display: block; }
        .english-meaning { font-size: 1.4rem; color: rgba(255,255,255,0.7); font-weight: 600; margin-top: 0.75rem; }
        .section-card {
            background: #fff; border: 1px solid #eef0f2; border-radius: 16px;
            padding: 1.75rem 2rem; margin-bottom: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .section-card h2 {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem;
            color: #F4A5A5; margin-bottom: 1rem; letter-spacing: 1.5px;
            text-transform: uppercase; padding-bottom: 0.5rem;
            border-bottom: 2px solid #fef0f0;
        }
        .definition { font-size: 1.1rem; color: #444; line-height: 1.8; }
        .def-list { padding-left: 1.5rem; }
        .def-list li { font-size: 1.05rem; color: #444; line-height: 1.8; margin-bottom: 0.5rem; }
        .def-note { font-size: 0.9rem; color: #888; font-style: italic; }
        .example-sentence {
            font-size: 1.05rem; color: #555; font-style: italic;
            padding: 1rem 1.5rem; background: linear-gradient(135deg, #fef8f8, #fef5f0);
            border-left: 4px solid #F4A5A5; border-radius: 0 12px 12px 0;
            margin-bottom: 0.75rem;
        }
        .cefr-info { display: flex; align-items: center; gap: 1rem; }
        .cefr-level-big { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: #27ae60; line-height: 1; }
        .cefr-detail { font-size: 1rem; color: #555; }
        .cefr-detail strong { color: #333; }
        .see-also { margin-top: 2rem; }
        .see-also h2 {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem;
            color: #F4A5A5; margin-bottom: 1rem; letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .see-also-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
        .see-also-link {
            display: block; padding: 0.75rem 1.25rem; background: #fff;
            border: 1px solid #eef0f2; border-radius: 12px;
            text-decoration: none; color: #333; font-weight: 500;
            transition: all 0.25s; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .see-also-link:hover { border-color: #F4A5A5; transform: translateY(-3px); box-shadow: 0 6px 20px rgba(244,165,165,0.12); }
        .see-also-link .link-word { font-size: 1.05rem; }
        .cta-banner {
            text-align: center; padding: 3rem 2rem;
            background: linear-gradient(160deg, #1a1a2e, #16213e);
            border-radius: 20px; margin-top: 2.5rem;
        }
        .cta-banner h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; margin-bottom: 0.75rem; color: #fff; }
        .cta-banner p { max-width: 500px; margin: 0 auto 1.5rem; color: rgba(255,255,255,0.6); }
        .btn-primary { display: inline-block; background: #F4A5A5; color: #fff; padding: 14px 36px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.05rem; transition: all 0.3s; }
        .btn-primary:hover { background: #e89494; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(244,165,165,0.4); }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: #F4A5A5; text-decoration: none; font-weight: 600; margin-bottom: 1.5rem; }
        .back-link:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .mobile-menu-toggle { display: flex; }
            .mobile-menu.active { display: flex; flex-direction: column; position: fixed; top: 70px; left: 0; right: 0; background: #fff; padding: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 999; }
            .mobile-menu a { padding: 12px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #eee; }
            .word-header h1 { font-size: 2.5rem; }
            .word-header { padding: 1.5rem 1.75rem; border-radius: 16px; }
            .word-page { padding: 1rem 1rem 3rem; }
            .section-card { padding: 1.25rem 1.5rem; }
            .breadcrumb { padding: 6rem 1.5rem 0; }
            .see-also-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .translations-grid { grid-template-columns: 1fr; }
        }
        .badge-freq { background: rgba(52,152,219,0.2); color: #5dade2; }
        .translations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; }
        .trans-lang-group { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.75rem 1rem; background: #f8f9fa; border-radius: 12px; border: 1px solid #eef0f2; }
        .trans-lang-label { font-size: 0.75rem; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.25rem; }
        .trans-word-link { color: #0f3460; text-decoration: none; font-weight: 500; font-size: 1rem; transition: color 0.2s; }
        .trans-word-link:hover { color: #F4A5A5; }
    </style>
</head>
<body>
<header class="header">
    <nav class="nav-container">
        <a href="/" class="logo-brand"><div class="main-logo"><img src="/assets/logo.png" alt="Babel Free Logo"></div></a>
        <ul class="nav-menu">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/services" class="nav-link">Services</a></li>
            <li><a href="/blog" class="nav-link">Blog</a></li>
            <li><a href="/dictionary" class="nav-link active">Dictionaries</a></li>
            <li><a href="/contact" class="nav-link">Contact</a></li>
            <li><a href="/elviajedeljaguar" class="cta-nav">Spanish Course</a></li>
        </ul>
        <div class="mobile-menu-toggle" onclick="document.getElementById('mobileMenu').classList.toggle('active')">
            <div class="menu-bar"></div><div class="menu-bar"></div><div class="menu-bar"></div>
        </div>
    </nav>
</header>
<div class="mobile-menu" id="mobileMenu">
    <a href="/">Home</a><a href="/services">Services</a><a href="/blog">Blog</a><a href="/dictionary">Dictionaries</a><a href="/contact">Contact</a><a href="/elviajedeljaguar">Spanish Course</a>
</div>

<div class="breadcrumb">
    <a href="/"><?= $breadHome ?></a> &rsaquo; <a href="/dictionary"><?= $breadDict ?></a> &rsaquo; <a href="/dictionary/<?= $lang ?>"><?= $langDisplayName ?></a> &rsaquo; <?= $word ?>
</div>

<main class="word-page">
    <a href="/dictionary" class="back-link">&larr; <?= $backLabel ?></a>

    <div class="word-header">
        <h1><?= str_replace('{word}', $word, $i18n['page_title_format'] ?? '{word}') ?></h1>
        <div class="word-meta">
            <?php if ($posLabel): ?><span class="badge badge-pos"><?= htmlspecialchars($posLabel, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <?php if ($genderLabel): ?><span class="badge badge-gender"><?= htmlspecialchars($genderLabel, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <?php if ($cefrLevel): ?><span class="badge badge-cefr">CEFR <?= htmlspecialchars($cefrLevel, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            <?php
            $freqRank = $entry['frequency_rank'] ?? null;
            $freqLabel = '';
            if ($freqRank !== null) {
                if ($freqRank <= 1000) $freqLabel = 'Common';
                elseif ($freqRank <= 5000) $freqLabel = 'Frequent';
                elseif ($freqRank <= 15000) $freqLabel = 'Standard';
                else $freqLabel = 'Specialized';
            }
            if ($freqLabel): ?>
                <span class="badge badge-freq"><?= $freqLabel ?></span>
            <?php endif; ?>
        </div>
        <?php if ($ipa): ?>
            <span class="ipa">/<?= htmlspecialchars(trim($ipa, '/'), ENT_QUOTES, 'UTF-8') ?>/</span>
        <?php endif; ?>
        <?php if ($curatedMeaning): ?>
            <div class="english-meaning"><?= htmlspecialchars($curatedMeaning, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($definitions)): ?>
    <div class="section-card">
        <h2><?= htmlspecialchars($sectionDef, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (count($definitions) === 1): ?>
            <p class="definition"><?= htmlspecialchars($definitions[0]['definition'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($definitions[0]['usage_note'])): ?>
                <p class="def-note"><?= htmlspecialchars($definitions[0]['usage_note'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <ol class="def-list">
            <?php foreach ($definitions as $def): ?>
                <li>
                    <?= htmlspecialchars($def['definition'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($def['usage_note'])): ?>
                        <div class="def-note"><?= htmlspecialchars($def['usage_note'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php
    $translations = $entry['translations'] ?? [];
    if (!empty($translations)):
        // Group translations by language
        $translationsByLang = [];
        foreach ($translations as $tr) {
            $trLang = $tr['lang'] ?? '';
            if ($trLang) $translationsByLang[$trLang][] = $tr;
        }
    ?>
    <div class="section-card">
        <h2><?= htmlspecialchars($i18n['section_translations'] ?? 'Translations', ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="translations-grid">
            <?php foreach ($translationsByLang as $trLang => $trWords): ?>
                <div class="trans-lang-group">
                    <span class="trans-lang-label"><?= htmlspecialchars($allI18n[$trLang]['lang_name'] ?? strtoupper($trLang), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php foreach (array_slice($trWords, 0, 3) as $tr): ?>
                        <a href="<?= htmlspecialchars($tr['url'], ENT_QUOTES, 'UTF-8') ?>" class="trans-word-link"><?= htmlspecialchars($tr['word'], ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    $examples = $entry['examples'] ?? [];
    if (!empty($examples)):
    ?>
    <div class="section-card">
        <h2><?= htmlspecialchars($sectionExamples, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php foreach ($examples as $ex): ?>
            <blockquote class="example-sentence">&ldquo;<?= htmlspecialchars($ex['sentence'], ENT_QUOTES, 'UTF-8') ?>&rdquo;</blockquote>
            <?php if (!empty($ex['translation'])): ?>
                <p style="margin:0.5rem 0 1rem 1.5rem;color:#777;font-size:0.95rem;"><?= htmlspecialchars($ex['translation'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($cefrLevel): ?>
    <div class="section-card">
        <h2><?= htmlspecialchars($cefrSectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="cefr-info">
            <span class="cefr-level-big"><?= htmlspecialchars($cefrLevel, ENT_QUOTES, 'UTF-8') ?></span>
            <div class="cefr-detail">
                <strong><?= htmlspecialchars($levelDesc, ENT_QUOTES, 'UTF-8') ?></strong><br>
                <?= htmlspecialchars($cefrDescText, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($neighbors)): ?>
    <div class="see-also">
        <h2><?= htmlspecialchars($seeAlsoTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="see-also-grid">
            <?php foreach ($neighbors as $n): ?>
                <a href="/dictionary/<?= $lang ?>/<?= $urlPrefix ?>-<?= urlencode(mb_strtolower($n)) ?>" class="see-also-link">
                    <span class="link-word"><?= htmlspecialchars($n, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="cta-banner">
        <h2><?= htmlspecialchars($ctaTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= $ctaText ?></p>
        <a href="/elviajedeljaguar" class="btn-primary"><?= htmlspecialchars($ctaButton, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</main>

<footer class="site-footer light">
    <div class="footer-grid">
        <div class="footer-brand">
            <p class="footer-logo"><img src="/assets/tower-logo.png" alt="Babel Free" loading="lazy"></p>
            <p class="footer-desc">Language courses, professional translation, and immersive Spanish learning through El Viaje del Jaguar — a CEFR-aligned journey powered by Colombian culture and storytelling.</p>
        </div>
        <nav class="footer-links" aria-label="Site map">
            <p class="footer-heading">Explore</p>
            <a href="/services">Language courses</a><a href="/elviajedeljaguar">El Viaje del Jaguar</a><a href="/dictionary">Dictionaries</a><a href="/languages">100+ languages</a><a href="/blog">Blog</a>
        </nav>
        <div class="footer-contact">
            <p class="footer-heading">Contact us</p>
            <a href="/translation-services" class="footer-cta">Translation services</a><a href="/contact" class="footer-cta">Message form</a><a href="mailto:info@babelfree.com" class="footer-cta-secondary">Email</a>
        </div>
    </div>
    <div class="footer-bottom"><p>&copy; 2026 Babel Free &middot; <a href="/privacy">Privacy</a></p></div>
</footer>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/service-worker.js');}</script>
</body>
</html>
