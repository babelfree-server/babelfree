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
 *   php enrich-wiktionary.php                    # Process up to 200 words
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

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--batch=') === 0) {
        $batchSize = (int)substr($arg, 8);
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
echo "  Batch size: " . ($processAll ? 'ALL' : $batchSize) . "\n";
if ($posFilter) echo "  POS filter: $posFilter\n";
if ($cefrFilter) echo "  CEFR filter: $cefrFilter\n";
echo "\n";

// ── Find words needing enrichment ──────────────────────────────────

$sql = "SELECT w.id, w.word, w.word_normalized, w.part_of_speech, w.pronunciation_ipa, w.gender
        FROM dict_words w
        LEFT JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = 'es'
        WHERE w.lang_code = 'es'
          AND d.id IS NULL";

$params = [];

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
    echo "Nothing to do. All words have Spanish definitions.\n";
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

// ── Source 1: Spanish definitions from es.wiktionary wikitext ──────

function fetchSpanishDefs(string $word): array {
    $url = "https://es.wiktionary.org/w/api.php?"
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

    // Extract definitions: ;N: definition text
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
    $defs = array_slice($defs, 0, 5);

    // Extract gender from template headers
    $gender = null;
    if (preg_match('/\{\{sustantivo\s+femenino/u', $wikitext)) {
        $gender = 'f';
    } elseif (preg_match('/\{\{sustantivo\s+masculino/u', $wikitext)) {
        $gender = 'm';
    }

    return ['defs' => $defs, 'gender' => $gender, 'wikitext' => $wikitext];
}

// ── Source 2: English definitions from en.wiktionary REST API ──────

function fetchEnglishDefs(string $word): array {
    $url = "https://en.wiktionary.org/api/rest_v1/page/definition/" . rawurlencode($word);
    $json = httpGet($url);
    if (!$json) return [];

    $data = json_decode($json, true);
    if (!$data) return [];

    $defs = [];
    $sections = $data['es'] ?? [];
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

function fetchIPA(string $word): ?string {
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
            if (strpos($combined, '>Spanish<') !== false) {
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

$stats = [
    'es_defs' => 0,
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

    // 1. Spanish definitions + gender from es.wiktionary
    $esResult = fetchSpanishDefs($word);
    usleep(300000);

    if ($esResult['defs']) {
        foreach ($esResult['defs'] as $order => $def) {
            $insertDef->execute([$wordId, 'es', $def, $order]);
            $stats['es_defs']++;
        }
        $gotAnything = true;
    }

    if ($esResult['gender'] && empty($row['gender'])) {
        $updateGender->execute([$esResult['gender'], $wordId]);
        $stats['gender']++;
    }

    // 2. English definitions from en.wiktionary REST API
    $enDefs = fetchEnglishDefs($word);
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
        $ipa = fetchIPA($word);
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
echo "  Words processed: " . count($words) . "\n";
echo "  Spanish definitions added: {$stats['es_defs']}\n";
echo "  English definitions added: {$stats['en_defs']}\n";
echo "  IPA pronunciations added: {$stats['ipa']}\n";
echo "  Gender updates: {$stats['gender']}\n";
echo "  Skipped (not in Wiktionary): {$stats['skipped']}\n";

// Overall DB status
$total = $pdo->query("SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es'")->fetchColumn();
$withEsDefs = $pdo->query("SELECT COUNT(DISTINCT word_id) FROM dict_definitions WHERE lang_code = 'es'")->fetchColumn();
$withEnDefs = $pdo->query("SELECT COUNT(DISTINCT word_id) FROM dict_definitions WHERE lang_code = 'en'")->fetchColumn();
$withIPA = $pdo->query("SELECT COUNT(*) FROM dict_words WHERE lang_code = 'es' AND pronunciation_ipa IS NOT NULL AND pronunciation_ipa != ''")->fetchColumn();
$remaining = $total - $withEsDefs;

echo "\n--- Overall Status ---\n";
echo "  Total Spanish words: $total\n";
echo "  With Spanish defs: $withEsDefs\n";
echo "  With English defs: $withEnDefs\n";
echo "  With IPA: $withIPA\n";
echo "  Remaining without defs: $remaining\n";
