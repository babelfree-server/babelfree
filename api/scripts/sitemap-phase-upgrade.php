<?php
/**
 * Sitemap Phase Upgrade — Automatically adds dictionary languages to sitemap index.
 *
 * Phase 1 (launch):  ES, EN, FR, DE, PT, IT
 * Phase 2 (May 1):   +NL, RU, EL, JA, KO, ZH, FI, CA, AR
 * Phase 3 (June 1):  +all remaining languages
 *
 * Run via cron daily: php api/scripts/sitemap-phase-upgrade.php
 * Safe to run multiple times — only upgrades when the date threshold passes.
 */

$webroot = dirname(__DIR__, 2);
$sitemapFile = "$webroot/sitemap.xml";
$today = date('Y-m-d');

// Phase definitions — edit dates here to control rollout
$phases = [
    1 => ['date' => '2026-03-27', 'langs' => ['es','en','fr','de','pt','it']],
    2 => ['date' => '2026-05-01', 'langs' => ['nl','ru','el','ja','ko','zh','fi','ca','ar']],
    3 => ['date' => '2026-06-01', 'langs' => []], // empty = all remaining
];

// Determine current phase
$activeLangs = [];
foreach ($phases as $num => $phase) {
    if ($today >= $phase['date']) {
        if (empty($phase['langs'])) {
            // Phase 3: include ALL languages
            $activeLangs = null; // null = no filter
            break;
        }
        $activeLangs = array_merge($activeLangs, $phase['langs']);
    }
}

// Collect sitemap files
$files = glob("$webroot/sitemap-dict-*.xml");
sort($files);

$included = [];
foreach ($files as $f) {
    $basename = basename($f);
    preg_match('/sitemap-dict-([a-z]+)/', $basename, $m);
    $lang = $m[1] ?? '';

    // If activeLangs is null (Phase 3+), include everything
    // Otherwise, only include if language is in active list
    if ($activeLangs === null || in_array($lang, $activeLangs)) {
        $included[] = $basename;
    }
}

// Build sitemap index
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

// Main sitemap always included
$xml .= "  <sitemap>\n";
$xml .= "    <loc>https://babelfree.com/sitemap-main.xml</loc>\n";
$xml .= "    <lastmod>$today</lastmod>\n";
$xml .= "  </sitemap>\n";

foreach ($included as $basename) {
    $mod = date('Y-m-d', filemtime("$webroot/$basename"));
    $xml .= "  <sitemap>\n";
    $xml .= "    <loc>https://babelfree.com/$basename</loc>\n";
    $xml .= "    <lastmod>$mod</lastmod>\n";
    $xml .= "  </sitemap>\n";
}

$xml .= "</sitemapindex>\n";

// Only write if changed
$current = file_exists($sitemapFile) ? file_get_contents($sitemapFile) : '';
if ($xml !== $current) {
    file_put_contents($sitemapFile, $xml);
    $entryCount = substr_count($xml, '<sitemap>');
    $langCount = count(array_unique(array_map(function ($f) {
        preg_match('/sitemap-dict-([a-z]+)/', $f, $m);
        return $m[1] ?? '?';
    }, $included)));
    echo date('Y-m-d H:i') . " — Sitemap upgraded: $entryCount entries, $langCount languages\n";
} else {
    echo date('Y-m-d H:i') . " — No change needed\n";
}
