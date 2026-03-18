<?php
/**
 * TheFreeDictionary Definition Extractor
 *
 * Fills definition gaps by extracting from TheFreeDictionary.com
 * Supports 22 languages via TFD's language subdomains.
 *
 * Usage:
 *   php seed-definitions-tfd.php --lang=es              # Single language
 *   php seed-definitions-tfd.php --all                  # Phase A languages
 *   php seed-definitions-tfd.php --all --phase=b        # Phase B languages
 *   php seed-definitions-tfd.php --lang=fr --limit=100  # Limit words processed
 *   php seed-definitions-tfd.php --lang=en --dry-run    # Preview only
 *   php seed-definitions-tfd.php --lang=it --delay=3    # Custom delay (seconds)
 */

// Flush output immediately for background logging
if (function_exists('ob_implicit_flush')) ob_implicit_flush(true);

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// ── CLI options ──────────────────────────────────────────────────────

$targetLangs = [];
$dryRun      = in_array('--dry-run', $argv ?? []);
$allLangs    = in_array('--all', $argv ?? []);
$limit       = 0;
$delay       = 2; // seconds between requests

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--lang=') === 0) $targetLangs[] = substr($arg, 7);
    if (strpos($arg, '--limit=') === 0) $limit = (int) substr($arg, 8);
    if (strpos($arg, '--delay=') === 0) $delay = (int) substr($arg, 8);
}

$phaseALangs = ['es', 'en', 'fr', 'de', 'pt', 'it'];
$phaseBLangs = ['nl', 'ru', 'ja', 'ko', 'ar', 'zh'];
$phaseB = in_array('--phase=b', $argv ?? []) || in_array('--phase=B', $argv ?? []);

if ($allLangs && $phaseB) {
    $targetLangs = $phaseBLangs;
} elseif ($allLangs) {
    $targetLangs = $phaseALangs;
} elseif (empty($targetLangs)) {
    echo "Usage: php seed-definitions-tfd.php --all [--phase=b] | --lang=XX [--limit=N] [--delay=N] [--dry-run]\n";
    echo "  Phase A: " . implode(', ', $phaseALangs) . "\n";
    echo "  Phase B: " . implode(', ', $phaseBLangs) . "\n";
    exit(1);
}

// ── TFD subdomain map ────────────────────────────────────────────────

$tfdHosts = [
    'es' => 'es.thefreedictionary.com',
    'en' => 'www.thefreedictionary.com',
    'fr' => 'fr.thefreedictionary.com',
    'de' => 'de.thefreedictionary.com',
    'it' => 'it.thefreedictionary.com',
    'pt' => 'pt.thefreedictionary.com',
    'nl' => 'nl.thefreedictionary.com',
    'ru' => 'ru.thefreedictionary.com',
    'zh' => 'cn.thefreedictionary.com',
    'ja' => 'ja.thefreedictionary.com',
    'ko' => 'ko.thefreedictionary.com',
    'ar' => 'ar.thefreedictionary.com',
    'no' => 'no.thefreedictionary.com',
    'pl' => 'pl.thefreedictionary.com',
    'ro' => 'ro.thefreedictionary.com',
    'th' => 'th.thefreedictionary.com',
    'tr' => 'tr.thefreedictionary.com',
    'el' => 'el.thefreedictionary.com',
    'hu' => 'hu.thefreedictionary.com',
    'fi' => 'fi.thefreedictionary.com',
    'da' => 'da.thefreedictionary.com',
    'sv' => 'sv.thefreedictionary.com',
];

// Filter unsupported languages
$targetLangs = array_filter($targetLangs, function($l) use ($tfdHosts) {
    if (!isset($tfdHosts[$l])) {
        echo "SKIP: '$l' not available on TheFreeDictionary\n";
        return false;
    }
    return true;
});

if (empty($targetLangs)) {
    echo "No supported languages to process.\n";
    exit(1);
}

echo "=== TheFreeDictionary Definition Extractor ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Delay: {$delay}s between requests\n";
echo "  Limit: " . ($limit ?: 'all') . "\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n\n";

// ── Prepared statements ──────────────────────────────────────────────

$insertDef = $pdo->prepare(
    'INSERT INTO dict_definitions (word_id, definition, usage_note, lang_code, sort_order, source_id)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$checkDef = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND lang_code = ?'
);

// ── Extraction function ──────────────────────────────────────────────

function fetchTfdDefinitions(string $word, string $host): array {
    $url = 'https://' . $host . '/' . rawurlencode($word);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-US,en;q=0.9',
        ],
        CURLOPT_ENCODING       => 'gzip, deflate',
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$html) {
        return [];
    }

    $definitions = [];

    // Strategy 1: Extract ds-single entries (richer, one definition per div)
    // These are within the main dictionary section (data-src="hm" for EN, or first section)
    if (preg_match_all('/<div class="ds-single">(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $raw) {
            $def = parseTfdDefinition($raw);
            if ($def) $definitions[] = $def;
        }
    }

    // Strategy 2: Extract ds-list entries (numbered definitions)
    if (preg_match_all('/<div class="ds-list">(.*?)<\/div>/s', $html, $matches)) {
        foreach ($matches[1] as $raw) {
            $def = parseTfdDefinition($raw);
            if ($def) $definitions[] = $def;
        }
    }

    // Deduplicate by normalized text
    $seen = [];
    $unique = [];
    foreach ($definitions as $def) {
        $key = mb_strtolower(mb_substr($def['definition'], 0, 60));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $def;
        }
    }

    return array_slice($unique, 0, 10); // Cap at 10 definitions
}

function parseTfdDefinition(string $raw): ?array {
    // Extract usage note from <i> tags at the start (domain labels like "medicina", "NÁUTICA")
    $usageNote = null;
    if (preg_match('/<i>\s*([^<]+)\s*<\/i>/', $raw, $im)) {
        $candidate = trim($im[1]);
        // Only treat as usage note if short and looks like a domain label
        if (mb_strlen($candidate) < 40 && !preg_match('/[.!?]/', $candidate)) {
            $usageNote = $candidate;
        }
    }

    // Strip all HTML
    $text = strip_tags($raw);
    // Remove numbering like "1. ", "a. ", "1. a. "
    $text = preg_replace('/^\s*\d+\.\s*[a-z]?\.\s*/u', '', $text);
    $text = preg_replace('/^\s*[a-z]\.\s*/u', '', $text);
    $text = preg_replace('/^\s*\d+\.\s*/u', '', $text);
    // Clean up whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));

    // Skip if too short or looks like a cross-reference only
    if (mb_strlen($text) < 5) return null;
    if (preg_match('/^See (also )?related/i', $text)) return null;
    if (preg_match('/^Variant of /i', $text)) return null;

    // Cap definition length
    if (mb_strlen($text) > 500) {
        $text = mb_substr($text, 0, 497) . '...';
    }

    return [
        'definition' => $text,
        'usage_note' => $usageNote,
    ];
}

// ── Main loop ────────────────────────────────────────────────────────

$totalInserted = 0;
$totalSkipped = 0;
$totalErrors = 0;
$totalProcessed = 0;

foreach ($targetLangs as $lang) {
    $host = $tfdHosts[$lang];

    // Get words without definitions in this language
    $sql = 'SELECT w.id, w.word
            FROM dict_words w
            LEFT JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = ?
            WHERE w.lang_code = ? AND d.id IS NULL
            ORDER BY w.frequency_rank ASC, w.word ASC';
    if ($limit > 0) $sql .= " LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lang, $lang]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = count($words);
    echo "[$lang] {$count} words without definitions (TFD host: $host)\n";

    if ($count === 0) continue;

    $langInserted = 0;
    $langSkipped = 0;
    $langErrors = 0;
    $startTime = time();

    foreach ($words as $i => $row) {
        $wordId = (int) $row['id'];
        $word = $row['word'];
        $totalProcessed++;

        // Progress every 50 words
        if (($i + 1) % 50 === 0 || $i === 0) {
            $elapsed = time() - $startTime;
            $rate = $elapsed > 0 ? round($langInserted / $elapsed, 1) : 0;
            echo "  [$lang] " . ($i + 1) . "/$count — inserted: $langInserted, skipped: $langSkipped, errors: $langErrors ($rate defs/s)\n";
            fflush(STDOUT);
        }

        // Fetch from TFD
        $defs = fetchTfdDefinitions($word, $host);

        if (empty($defs)) {
            $langSkipped++;
            $totalSkipped++;
            sleep($delay);
            continue;
        }

        if ($dryRun) {
            echo "  [DRY] $word: " . count($defs) . " defs — " . mb_substr($defs[0]['definition'], 0, 80) . "\n";
            $langInserted += count($defs);
            sleep($delay);
            continue;
        }

        // Insert definitions
        try {
            foreach ($defs as $sortIdx => $def) {
                $insertDef->execute([
                    $wordId,
                    $def['definition'],
                    $def['usage_note'],
                    $lang,
                    $sortIdx + 1,
                    'thefreedictionary'
                ]);
                $langInserted++;
                $totalInserted++;
            }
        } catch (Exception $e) {
            $langErrors++;
            $totalErrors++;
            echo "  ERROR [{$word}]: " . $e->getMessage() . "\n";
        }

        sleep($delay);
    }

    $elapsed = time() - $startTime;
    echo "[$lang] Done — inserted: $langInserted, skipped: $langSkipped, errors: $langErrors (elapsed: {$elapsed}s)\n\n";
}

echo "=== COMPLETE ===\n";
echo "  Total processed: $totalProcessed\n";
echo "  Total definitions inserted: $totalInserted\n";
echo "  Total words skipped (no TFD entry): $totalSkipped\n";
echo "  Total errors: $totalErrors\n";
