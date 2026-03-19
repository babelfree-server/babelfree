<?php
/**
 * Wiktionary Enrichment — Fetches definitions + IPA for existing dict_words.
 *
 * Data sources:
 *   - Spanish definitions: es.wiktionary.org MediaWiki action API (wikitext parse)
 *   - English definitions: en.wiktionary.org REST API (es key)
 *   - IPA: en.wiktionary.org parsed HTML (Spanish section)
 *   - Gender: es.wiktionary.org wikitext (sustantivo femenino/masculino templates)
 *
 * Resumable: only targets un-enriched words; safe to re-run.
 *
 * Usage:
 *   php enrich-wiktionary.php                    # Process Spanish (default), up to 200 words
 *   php enrich-wiktionary.php --lang=fr          # Process French
 *   php enrich-wiktionary.php --lang=de --all    # Process ALL un-enriched German words
 *   php enrich-wiktionary.php --batch=500        # Process 500 words
 *   php enrich-wiktionary.php --pos=verb         # Only verbs
 *   php enrich-wiktionary.php --cefr=A1          # Only A1 words
 *   php enrich-wiktionary.php --all              # Process ALL un-enriched words
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// CLI options
$batchSize = 200;
$posFilter = null;
$cefrFilter = null;
$processAll = false;
$langCode = 'es'; // Default to Spanish

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--batch=') === 0) {
        $batchSize = (int)substr($arg, 8);
    } elseif (strpos($arg, '--lang=') === 0) {
        $langCode = substr($arg, 7);
    } elseif (strpos($arg, '--pos=') === 0) {
        $posFilter = substr($arg, 6);
    } elseif (strpos($arg, '--cefr=') === 0) {
        $cefrFilter = strtoupper(substr($arg, 7));
    } elseif ($arg === '--all') {
        $processAll = true;
        $batchSize = 999999;
    }
}

echo "=== Wiktionary Enrichment ===\n";
echo "  Language: $langCode\n";
echo "  Batch size: " . ($processAll ? 'ALL' : $batchSize) . "\n";
if ($posFilter) echo "  POS filter: $posFilter\n";
if ($cefrFilter) echo "  CEFR filter: $cefrFilter\n";
echo "\n";

// Wiktionary edition to use for definitions (maps lang_code → Wiktionary subdomain)
// Most languages use their own code; some need mapping
$wiktionaryLangMap = [
    'zh-tw' => 'zh',
    'no' => 'nb', // Norwegian Bokmål on Wiktionary
];
$wiktLang = $wiktionaryLangMap[$langCode] ?? $langCode;

// ── Find words needing enrichment ──────────────────────────────────

$sql = "SELECT w.id, w.word, w.word_normalized, w.part_of_speech, w.pronunciation_ipa, w.gender
        FROM dict_words w
        LEFT JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = ?
        WHERE w.lang_code = ?
          AND d.id IS NULL";

$params = [$langCode, $langCode];

if ($posFilter) {
    $sql .= " AND w.part_of_speech = ?";
    $params[] = $posFilter;
}
if ($cefrFilter) {
    $sql .= " AND w.cefr_level = ?";
    $params[] = $cefrFilter;
}

$sql .= " ORDER BY w.frequency_rank ASC, w.id ASC LIMIT ?";
$params[] = $batchSize;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Words to enrich: " . count($words) . "\n\n";

if (count($words) === 0) {
    echo "Nothing to do. All {$langCode} words have definitions.\n";
    exit(0);
}

// ── Prepared statements ────────────────────────────────────────────

$insertDef = $pdo->prepare(
    'INSERT INTO dict_definitions (word_id, lang_code, definition, sort_order)
     VALUES (?, ?, ?, ?)'
);

$updateIPA = $pdo->prepare(
    'UPDATE dict_words SET pronunciation_ipa = ? WHERE id = ?'
);

$updateGender = $pdo->prepare(
    'UPDATE dict_words SET gender = ? WHERE id = ? AND gender IS NULL'
);

// ── HTTP helper ────────────────────────────────────────────────────

function httpGet(string $url): ?string {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: YaguaraDictBot/1.0 (babelfree.com; dictionary enrichment)\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    if (!$response) return null;

    // Check for error status codes
    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/^HTTP\/\S+\s+(4|5)\d\d/', $h)) return null;
        }
    }

    return $response;
}

// ── Source 1: Native definitions from {lang}.wiktionary wikitext ───

function fetchNativeDefs(string $word, string $wiktLang): array {
    $url = "https://{$wiktLang}.wiktionary.org/w/api.php?"
         . http_build_query([
             'action' => 'parse',
             'page'   => $word,
             'prop'   => 'wikitext',
             'format' => 'json',
         ]);

    $json = httpGet($url);
    if (!$json) return ['defs' => [], 'gender' => null, 'wikitext' => null];

    $data = json_decode($json, true);
    $wikitext = $data['parse']['wikitext']['*'] ?? null;
    if (!$wikitext) return ['defs' => [], 'gender' => null, 'wikitext' => null];

    // Extract definitions: ;N: definition text (common across many Wiktionary editions)
    $defs = [];
    if (preg_match_all('/^;(\d+)\s*[:\|]+\s*(.+)/mu', $wikitext, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $text = trim($m[2]);
            // Clean wiki templates: {{plm|word}} → word
            $text = preg_replace('/\{\{plm\|([^}|]+)[^}]*\}\}/', '$1', $text);
            // {{csem|word}}: prefix → remove
            $text = preg_replace('/\{\{csem\|[^}]+\}\}:\s*/', '', $text);
            // Generic templates: {{template|text}} → text (take first param after |)
            $text = preg_replace('/\{\{[^|]+\|([^}|]+)[^}]*\}\}/', '$1', $text);
            // Remove remaining templates
            $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);
            // Clean wiki links: [[text]] → text, [[link|text]] → text
            $text = preg_replace('/\[\[[^|\]]+\|([^\]]+)\]\]/', '$1', $text);
            $text = preg_replace('/\[\[([^\]]+)\]\]/', '$1', $text);
            // Clean references and html
            $text = preg_replace('/<ref[^>]*>.*?<\/ref>/s', '', $text);
            $text = strip_tags($text);
            $text = trim($text, " \t\n\r\0\x0B.");

            if ($text && mb_strlen($text) > 2) {
                $defs[] = ucfirst($text) . '.';
            }
        }
    }

    // Fallback: try # definition lines (used by en, fr, de, nl, and many others)
    if (empty($defs) && preg_match_all('/^#\s+(.+)/mu', $wikitext, $matches)) {
        foreach ($matches[1] as $raw) {
            $text = $raw;
            // Clean wiki templates and links (same as above)
            $text = preg_replace('/\{\{[^|]+\|([^}|]+)[^}]*\}\}/', '$1', $text);
            $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);
            $text = preg_replace('/\[\[[^|\]]+\|([^\]]+)\]\]/', '$1', $text);
            $text = preg_replace('/\[\[([^\]]+)\]\]/', '$1', $text);
            $text = preg_replace('/<ref[^>]*>.*?<\/ref>/s', '', $text);
            $text = strip_tags($text);
            $text = trim($text, " \t\n\r\0\x0B.");
            if ($text && mb_strlen($text) > 2) {
                $defs[] = ucfirst($text) . '.';
            }
        }
    }
    $defs = array_slice($defs, 0, 5);

    // Extract gender from template headers (works for es, fr, pt, it, de)
    $gender = null;
    if (preg_match('/\{\{sustantivo\s+femenino/u', $wikitext) ||
        preg_match('/\{\{S\|nom\|[^}]*genre=f/u', $wikitext) ||
        preg_match('/\{\{f\}\}/u', $wikitext)) {
        $gender = 'f';
    } elseif (preg_match('/\{\{sustantivo\s+masculino/u', $wikitext) ||
              preg_match('/\{\{S\|nom\|[^}]*genre=m/u', $wikitext) ||
              preg_match('/\{\{m\}\}/u', $wikitext)) {
        $gender = 'm';
    }

    return ['defs' => $defs, 'gender' => $gender, 'wikitext' => $wikitext];
}

// ── Source 2: English definitions from en.wiktionary REST API ──────

function fetchEnglishDefs(string $word, string $langCode = 'es'): array {
    $url = "https://en.wiktionary.org/api/rest_v1/page/definition/" . rawurlencode($word);
    $json = httpGet($url);
    if (!$json) return [];

    $data = json_decode($json, true);
    if (!$data) return [];

    $defs = [];
    $sections = $data[$langCode] ?? [];
    foreach ($sections as $section) {
        foreach ($section['definitions'] ?? [] as $defEntry) {
            $text = $defEntry['definition'] ?? '';
            $text = strip_tags($text);
            $text = preg_replace('/\[\d+\]/', '', $text);
            // Skip "inflection of" entries
            if (preg_match('/^(inflection|form) of\b/i', $text)) continue;
            $text = trim($text);
            if ($text && mb_strlen($text) > 2) {
                $defs[] = $text;
            }
        }
    }
    return array_slice($defs, 0, 5);
}

// ── Source 3: IPA from en.wiktionary parsed HTML ───────────────────

function fetchIPA(string $word, string $sectionName = 'Spanish'): ?string {
    $url = "https://en.wiktionary.org/w/api.php?"
         . http_build_query([
             'action' => 'parse',
             'page'   => $word,
             'prop'   => 'text',
             'format' => 'json',
         ]);

    $json = httpGet($url);
    if (!$json) return null;

    $data = json_decode($json, true);
    $html = $data['parse']['text']['*'] ?? '';
    if (!$html) return null;

    // Split by h2 headings to isolate the Spanish section
    $sections = preg_split('/(<div[^>]*class="mw-heading mw-heading2"[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

    $spanishSection = null;
    for ($i = 0; $i < count($sections); $i++) {
        if (strpos($sections[$i], 'mw-heading2') !== false) {
            $combined = $sections[$i] . ($sections[$i + 1] ?? '');
            if (strpos($combined, '>' . $sectionName . '<') !== false) {
                // Collect everything until next h2
                $spanishSection = $sections[$i + 1] ?? '';
                for ($j = $i + 2; $j < count($sections); $j++) {
                    if (strpos($sections[$j], 'mw-heading2') !== false) break;
                    $spanishSection .= $sections[$j];
                }
                break;
            }
        }
    }

    if (!$spanishSection) return null;

    // Find phonemic IPA: /.../ inside IPA spans (search first 10KB)
    $searchArea = substr($spanishSection, 0, 10000);
    if (preg_match_all('/<span[^>]*class="IPA[^"]*"[^>]*>(\/[^<]+\/)<\/span>/', $searchArea, $matches)) {
        foreach ($matches[1] as $ipa) {
            // Validate: must have at least one vowel-like IPA character and be >= 3 chars
            if (mb_strlen($ipa) >= 3 && preg_match('/[aeiouyɛɪɔʊəæɑ]/u', $ipa)) {
                return $ipa;
            }
        }
    }

    return null;
}

// ── Main enrichment loop ───────────────────────────────────────────

// Look up the English name for the IPA section heading on en.wiktionary
$langNameStmt = $pdo->prepare("SELECT name_en FROM dict_languages WHERE code = ?");
$langNameStmt->execute([$langCode]);
$langNameEn = $langNameStmt->fetchColumn() ?: 'Spanish';

// en.wiktionary REST API uses specific section keys (often the lang code, but some differ)
// Map our codes to the keys used in en.wiktionary REST API responses
$enWiktApiKeyMap = [
    'zh'    => 'zh',      // Chinese
    'zh-tw' => 'zh',      // Traditional Chinese → same section
    'no'    => 'nb',      // Norwegian Bokmål
];
$enWiktApiKey = $enWiktApiKeyMap[$langCode] ?? $langCode;

$stats = [
    'native_defs' => 0,
    'en_defs' => 0,
    'ipa' => 0,
    'gender' => 0,
    'skipped' => 0,
];

$startTime = time();

foreach ($words as $i => $row) {
    $wordId = (int)$row['id'];
    $word   = $row['word'];
    $num    = $i + 1;

    // Progress every 25 words
    if ($num % 25 === 0 || $num === 1) {
        $elapsed = time() - $startTime;
        $rate = $elapsed > 0 ? round($num / $elapsed, 1) : 0;
        echo "  [$num/" . count($words) . "] Processing '$word' ({$rate}/s)\n";
    }

    $gotAnything = false;

    // 1. Native definitions + gender from {lang}.wiktionary
    $nativeResult = fetchNativeDefs($word, $wiktLang);
    usleep(300000);

    if ($nativeResult['defs']) {
        foreach ($nativeResult['defs'] as $order => $def) {
            $insertDef->execute([$wordId, $langCode, $def, $order]);
            $stats['native_defs']++;
        }
        $gotAnything = true;
    }

    if ($nativeResult['gender'] && empty($row['gender'])) {
        $updateGender->execute([$nativeResult['gender'], $wordId]);
        $stats['gender']++;
    }

    // 2. English definitions from en.wiktionary REST API
    $enDefs = fetchEnglishDefs($word, $enWiktApiKey);
    usleep(300000);

    if ($enDefs) {
        foreach ($enDefs as $order => $def) {
            $insertDef->execute([$wordId, 'en', $def, $order]);
            $stats['en_defs']++;
        }
        $gotAnything = true;
    }

    // 3. IPA from en.wiktionary parsed HTML (if not already set)
    if (empty($row['pronunciation_ipa'])) {
        $ipa = fetchIPA($word, $langNameEn);
        usleep(300000);

        if ($ipa) {
            $updateIPA->execute([$ipa, $wordId]);
            $stats['ipa']++;
        }
    }

    if (!$gotAnything) {
        $stats['skipped']++;
    }

    // Small extra pause between words
    usleep(200000);
}

// ── Summary ────────────────────────────────────────────────────────

$elapsed = time() - $startTime;
$minutes = round($elapsed / 60, 1);

echo "\n=== Enrichment Complete ({$minutes} min) ===\n";
echo "  Language: {$langCode}\n";
echo "  Words processed: " . count($words) . "\n";
echo "  Native definitions added: {$stats['native_defs']}\n";
echo "  English definitions added: {$stats['en_defs']}\n";
echo "  IPA pronunciations added: {$stats['ipa']}\n";
echo "  Gender updates: {$stats['gender']}\n";
echo "  Skipped (not in Wiktionary): {$stats['skipped']}\n";

// Overall DB status
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM dict_words WHERE lang_code = ?");
$stmtTotal->execute([$langCode]);
$total = $stmtTotal->fetchColumn();

$stmtNativeDefs = $pdo->prepare("SELECT COUNT(DISTINCT word_id) FROM dict_definitions WHERE lang_code = ?");
$stmtNativeDefs->execute([$langCode]);
$withNativeDefs = $stmtNativeDefs->fetchColumn();

$stmtEnDefs = $pdo->prepare("SELECT COUNT(DISTINCT d.word_id) FROM dict_definitions d JOIN dict_words w ON d.word_id = w.id WHERE d.lang_code = 'en' AND w.lang_code = ?");
$stmtEnDefs->execute([$langCode]);
$withEnDefs = $stmtEnDefs->fetchColumn();

$stmtIPA = $pdo->prepare("SELECT COUNT(*) FROM dict_words WHERE lang_code = ? AND pronunciation_ipa IS NOT NULL AND pronunciation_ipa != ''");
$stmtIPA->execute([$langCode]);
$withIPA = $stmtIPA->fetchColumn();

$remaining = $total - $withNativeDefs;

echo "\n--- Overall Status ({$langCode}) ---\n";
echo "  Total words: $total\n";
echo "  With native defs: $withNativeDefs\n";
echo "  With English defs: $withEnDefs\n";
echo "  With IPA: $withIPA\n";
echo "  Remaining without defs: $remaining\n";
