<?php
/**
 * Three-Tier Translation Pipeline — Brings EN, FR, DE, PT, IT to parity with ES
 *
 * Tier 1: es.wiktionary.org Traducciones section (curated, highest quality)
 * Tier 2: Apertium API (rule-based, good quality — es→en/fr/it/pt)
 * Tier 3: MyMemory API (crowdsourced, fair quality — es→de + remaining gaps)
 *
 * All APIs are free with no API keys required.
 *
 * Usage:
 *   php seed-translations-wiktionary.php --all                    # All 5 languages, all tiers
 *   php seed-translations-wiktionary.php --lang=fr                # Single language
 *   php seed-translations-wiktionary.php --lang=de --tier=3       # Single tier
 *   php seed-translations-wiktionary.php --all --dry-run           # Preview only
 *   php seed-translations-wiktionary.php --all --batch=500         # Custom batch reporting
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// ── CLI options ──────────────────────────────────────────────────────

$targetLangs = [];
$tierFilter  = 0;  // 0 = all tiers
$dryRun      = in_array('--dry-run', $argv ?? []);
$batchSize   = 500;
$allLangs    = in_array('--all', $argv ?? []);

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--lang=') === 0) {
        $targetLangs[] = substr($arg, 7);
    }
    if (strpos($arg, '--tier=') === 0) {
        $tierFilter = (int) substr($arg, 7);
    }
    if (strpos($arg, '--batch=') === 0) {
        $batchSize = (int) substr($arg, 8);
    }
}

$phaseALangs = ['en', 'fr', 'de', 'pt', 'it'];
$phaseBLangs = ['zh', 'ja', 'ko', 'ru', 'ar', 'nl'];
$allSupportedLangs = array_merge($phaseALangs, $phaseBLangs);
$phaseB = in_array('--phase=b', $argv ?? []) || in_array('--phase=B', $argv ?? []);

if ($allLangs && $phaseB) {
    $targetLangs = $phaseBLangs;
} elseif ($allLangs) {
    $targetLangs = $phaseALangs;
} elseif (empty($targetLangs)) {
    echo "Usage: php seed-translations-wiktionary.php --all [--phase=b] | --lang=XX [--tier=N] [--dry-run] [--batch=N]\n";
    echo "  Phase A (default): " . implode(', ', $phaseALangs) . "\n";
    echo "  Phase B (--phase=b): " . implode(', ', $phaseBLangs) . "\n";
    exit(1);
}

// Validate languages
foreach ($targetLangs as $lang) {
    if (!in_array($lang, $allSupportedLangs)) {
        echo "ERROR: Unsupported language '$lang'. Supported: " . implode(', ', $allSupportedLangs) . "\n";
        exit(1);
    }
}

echo "=== Three-Tier Translation Pipeline ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Tier filter: " . ($tierFilter ?: 'all') . "\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n";
echo "  Batch report every: $batchSize words\n\n";

// ── Helpers ──────────────────────────────────────────────────────────

function normalize(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    $word = preg_replace('/[áàâãä]/u', 'a', $word);
    $word = preg_replace('/[éèêë]/u', 'e', $word);
    $word = preg_replace('/[íìîï]/u', 'i', $word);
    $word = preg_replace('/[óòôõö]/u', 'o', $word);
    $word = preg_replace('/[úùûü]/u', 'u', $word);
    $word = str_replace(['ñ', 'ç', 'ß'], ['n', 'c', 'ss'], $word);
    return trim($word);
}

function ensureWordEntry(PDO $pdo, string $word, string $langCode,
                         ?string $cefrLevel = null, ?string $pos = null,
                         ?string $gender = null): ?int {
    static $insertStmt = null;
    static $findStmt   = null;

    if (!$insertStmt) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO dict_words (lang_code, word, word_normalized, cefr_level, part_of_speech, gender)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $findStmt = $pdo->prepare(
            'SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1'
        );
    }

    $norm = normalize($word);
    if (!$norm) return null;

    // part_of_speech is NOT NULL — default to 'noun' if unknown
    if (!$pos) $pos = 'noun';

    $insertStmt->execute([$langCode, $word, $norm, $cefrLevel, $pos, $gender]);
    $id = $pdo->lastInsertId();

    if (!$id) {
        $findStmt->execute([$langCode, $norm]);
        $row = $findStmt->fetch();
        $id = $row ? (int) $row['id'] : null;
    }

    return $id ? (int) $id : null;
}

function ensureTranslation(PDO $pdo, int $sourceId, int $targetId): void {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)'
        );
    }
    $stmt->execute([$sourceId, $targetId]);
    $stmt->execute([$targetId, $sourceId]);
}

/**
 * Check if a translation link already exists between a Spanish word and a target language.
 */
function translationExists(PDO $pdo, int $esWordId, string $targetLang): bool {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM dict_translations dt
             JOIN dict_words tw ON tw.id = dt.target_word_id
             WHERE dt.source_word_id = ? AND tw.lang_code = ?
             LIMIT 1'
        );
    }
    $stmt->execute([$esWordId, $targetLang]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Fetch wikitext from es.wiktionary.org for a given word.
 */
function fetchWikitext(string $word): ?string {
    $url = 'https://es.wiktionary.org/w/api.php?' . http_build_query([
        'action'  => 'parse',
        'page'    => $word,
        'prop'    => 'wikitext',
        'format'  => 'json',
    ]);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: BabelFreeDictBot/1.0 (https://babelfree.com)\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    return $data['parse']['wikitext']['*'] ?? null;
}

/**
 * Parse {{t|LANG|t1=word|t2=word2|g1=m|...}} templates from Traducciones section.
 *
 * es.wiktionary uses named params:
 *   {{t|fr|t1=laisser|t2=quitter}}
 *   {{t|en|a1=1-3|t1=leave|a2=5|t2=give up/quit}}
 *   {{t|de|t1=lassen}}
 *
 * Returns array keyed by lang code: ['en' => [['word' => 'leave', 'gender' => null], ...], ...]
 */
function parseTraducciones(string $wikitext, array $targetLangs): array {
    $results = [];
    foreach ($targetLangs as $lang) {
        $results[$lang] = [];
    }

    // Find Traducciones section(s)
    if (!preg_match('/={3,4}\s*Traducciones\s*={3,4}/u', $wikitext, $m, PREG_OFFSET_CAPTURE)) {
        return $results;
    }

    $start = $m[0][1];
    $sectionText = substr($wikitext, $start);
    // Cut at next heading of level 2-4
    if (preg_match('/\n={2,4}[^=]/u', $sectionText, $endMatch, PREG_OFFSET_CAPTURE, 10)) {
        $sectionText = substr($sectionText, 0, $endMatch[0][1]);
    }

    // Match each {{t|LANG|...}} or {{t+|LANG|...}} template (entire content between braces)
    preg_match_all('/\{\{t\+?\|([a-z]{2,3})\|([^}]+)\}\}/u', $sectionText, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $lang = $match[1];
        if (!in_array($lang, $targetLangs)) continue;

        $params = $match[2]; // e.g. "t1=laisser|t2=quitter" or "a1=1-3|t1=leave"

        // Extract all t1=, t2=, t3=... (translation words)
        preg_match_all('/\bt(\d+)=([^|]+)/u', $params, $tMatches, PREG_SET_ORDER);

        // Extract gender params g1=m, g2=f, etc.
        $genders = [];
        preg_match_all('/\bg(\d+)=([mfn])/u', $params, $gMatches, PREG_SET_ORDER);
        foreach ($gMatches as $gm) {
            $genders[$gm[1]] = $gm[2] === 'n' ? null : $gm[2];
        }

        foreach ($tMatches as $tm) {
            $idx  = $tm[1]; // 1, 2, 3...
            $word = trim($tm[2]);

            // Skip empty, too-long, or transliteration-only entries
            if (!$word || mb_strlen($word) > 100) continue;
            // Skip entries that look like wikitext links rather than words
            $word = preg_replace('/\[\[([^\]|]+)(?:\|[^\]]+)?\]\]/', '$1', $word);
            $word = trim($word, ' []');
            if (!$word) continue;

            $gender = $genders[$idx] ?? null;

            $results[$lang][] = [
                'word'   => $word,
                'gender' => $gender,
            ];
        }
    }

    return $results;
}

/**
 * Translate a word using Apertium API.
 * Available pairs: spa|eng, spa|fra, spa|ita, spa|por
 */
function apertiumTranslate(string $word, string $targetLang): ?string {
    $langPairs = [
        'en' => 'spa|eng',
        'fr' => 'spa|fra',
        'it' => 'spa|ita',
        'pt' => 'spa|por',
    ];

    if (!isset($langPairs[$targetLang])) return null;

    $url = 'https://apertium.org/apy/translate?' . http_build_query([
        'langpair' => $langPairs[$targetLang],
        'q'        => $word,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    $translated = $data['responseData']['translatedText'] ?? null;

    if (!$translated) return null;

    // Apertium marks unknown words with asterisk
    $translated = trim($translated);
    if (strpos($translated, '*') !== false) return null;

    // Clean up
    $translated = trim($translated, ' .');
    if (!$translated || mb_strlen($translated) > 100) return null;

    return $translated;
}

/**
 * Translate a word using MyMemory API.
 * Free tier: 30K requests/day, no API key needed.
 */
function myMemoryTranslate(string $word, string $targetLang): ?string {
    $langMap = [
        'en' => 'en',
        'fr' => 'fr',
        'de' => 'de',
        'pt' => 'pt',
        'it' => 'it',
        'zh' => 'zh-CN',
        'ja' => 'ja',
        'ko' => 'ko',
        'ru' => 'ru',
        'ar' => 'ar',
        'nl' => 'nl',
    ];

    if (!isset($langMap[$targetLang])) return null;

    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q'        => $word,
        'langpair' => 'es|' . $langMap[$targetLang],
        'de'       => 'info@babelfree.com',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) {
        // file_get_contents returns false on 429 — fall through to Lingva
        return lingvaTranslate($word, $targetLang);
    }

    $data = json_decode($json, true);

    // Check response status — if not 200, fall through to Lingva
    if (($data['responseStatus'] ?? 0) !== 200) {
        return lingvaTranslate($word, $targetLang);
    }

    $translated = $data['responseData']['translatedText'] ?? null;
    if (!$translated) return null;

    $translated = trim($translated);

    // MyMemory sometimes returns the same text untranslated
    if (mb_strtolower($translated) === mb_strtolower($word)) return null;

    // Skip if too long or empty
    if (!$translated || mb_strlen($translated) > 100) return null;

    return $translated;
}

/**
 * Translate a word using Lingva Translate (Google Translate frontend).
 * No API key, no quota. Fallback for when MyMemory is exhausted.
 */
function lingvaTranslate(string $word, string $targetLang): ?string {
    $url = 'https://lingva.ml/api/v1/es/' . urlencode($targetLang) . '/' . urlencode($word);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    $translated = $data['translation'] ?? null;
    if (!$translated) return null;

    $translated = trim($translated);

    // Skip if same as source or too long
    if (mb_strtolower($translated) === mb_strtolower($word)) return null;
    if (!$translated || mb_strlen($translated) > 100) return null;

    return $translated;
}

// ── Load all Spanish words ───────────────────────────────────────────

$stmt = $pdo->query(
    "SELECT id, word, word_normalized, cefr_level, part_of_speech, gender
     FROM dict_words
     WHERE lang_code = 'es'
     ORDER BY frequency_rank ASC, id ASC"
);
$spanishWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalES = count($spanishWords);
echo "Spanish source words: $totalES\n\n";

// ── Stats tracking ───────────────────────────────────────────────────

$stats = [];
foreach ($targetLangs as $lang) {
    $stats[$lang] = ['tier1' => 0, 'tier2' => 0, 'tier3' => 0, 'skipped' => 0, 'errors' => 0];
}

// ════════════════════════════════════════════════════════════════════
//  TIER 1: es.wiktionary.org Traducciones section
// ════════════════════════════════════════════════════════════════════

if ($tierFilter === 0 || $tierFilter === 1) {
    echo "--- Tier 1: es.wiktionary Traducciones ---\n";
    $tier1Start = time();
    $processed = 0;

    foreach ($spanishWords as $sw) {
        $processed++;

        if ($processed % $batchSize === 0) {
            $elapsed = time() - $tier1Start;
            $rate = $processed / max($elapsed, 1);
            $remaining = ($totalES - $processed) / max($rate, 0.1);
            echo "  [$processed/$totalES] " . round($rate, 1) . " words/sec, ~" . round($remaining / 60, 1) . " min remaining\n";
            foreach ($targetLangs as $lang) {
                echo "    $lang: tier1={$stats[$lang]['tier1']}, skipped={$stats[$lang]['skipped']}\n";
            }
        }

        // Fetch wikitext
        $wikitext = fetchWikitext($sw['word']);
        if (!$wikitext) {
            usleep(500000); // 500ms
            continue;
        }

        // Parse translations for all target languages at once
        $translations = parseTraducciones($wikitext, $targetLangs);

        foreach ($targetLangs as $lang) {
            if (empty($translations[$lang])) continue;

            // Check if translation already exists
            if (translationExists($pdo, (int) $sw['id'], $lang)) {
                $stats[$lang]['skipped']++;
                continue;
            }

            if ($dryRun) {
                $first = $translations[$lang][0]['word'];
                echo "  [dry-run] {$sw['word']} → $lang: $first\n";
                $stats[$lang]['tier1']++;
                continue;
            }

            // Insert first translation (highest quality — first in Wiktionary list)
            $trans = $translations[$lang][0];
            $targetId = ensureWordEntry(
                $pdo, $trans['word'], $lang,
                $sw['cefr_level'], $sw['part_of_speech'], $trans['gender']
            );

            if ($targetId) {
                ensureTranslation($pdo, (int) $sw['id'], $targetId);
                $stats[$lang]['tier1']++;
            }

            // Also insert additional translations (up to 3) as separate word entries + links
            $extra = array_slice($translations[$lang], 1, 2);
            foreach ($extra as $t) {
                $extraId = ensureWordEntry(
                    $pdo, $t['word'], $lang,
                    $sw['cefr_level'], $sw['part_of_speech'], $t['gender']
                );
                if ($extraId && $extraId !== $targetId) {
                    ensureTranslation($pdo, (int) $sw['id'], $extraId);
                }
            }
        }

        // Rate limit: 500ms between API calls
        usleep(500000);
    }

    $tier1Elapsed = time() - $tier1Start;
    echo "\n  Tier 1 complete in " . round($tier1Elapsed / 60, 1) . " minutes\n";
    foreach ($targetLangs as $lang) {
        echo "    $lang: {$stats[$lang]['tier1']} translations from Wiktionary\n";
    }
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════
//  TIER 2: Apertium API (rule-based) — es→en, fr, it, pt (NOT de)
// ════════════════════════════════════════════════════════════════════

$apertiumLangs = array_intersect($targetLangs, ['en', 'fr', 'it', 'pt']);

if (($tierFilter === 0 || $tierFilter === 2) && !empty($apertiumLangs)) {
    echo "--- Tier 2: Apertium API ---\n";
    echo "  Languages: " . implode(', ', $apertiumLangs) . " (de not available via Apertium)\n";
    $tier2Start = time();

    foreach ($apertiumLangs as $lang) {
        echo "\n  Processing: $lang\n";
        $langCount = 0;
        $langProcessed = 0;

        foreach ($spanishWords as $sw) {
            $langProcessed++;

            // Skip if already has translation
            if (translationExists($pdo, (int) $sw['id'], $lang)) {
                continue;
            }

            if ($langProcessed % $batchSize === 0) {
                echo "    [$langProcessed/$totalES] tier2_new=$langCount\n";
            }

            $translated = apertiumTranslate($sw['word'], $lang);
            if (!$translated) {
                usleep(200000);
                continue;
            }

            if ($dryRun) {
                echo "  [dry-run] {$sw['word']} → $lang: $translated\n";
                $langCount++;
                $stats[$lang]['tier2']++;
                usleep(200000);
                continue;
            }

            $targetId = ensureWordEntry($pdo, $translated, $lang, $sw['cefr_level'], $sw['part_of_speech']);
            if ($targetId) {
                ensureTranslation($pdo, (int) $sw['id'], $targetId);
                $langCount++;
                $stats[$lang]['tier2']++;
            }

            // Rate limit: 200ms
            usleep(200000);
        }

        echo "    $lang tier 2: $langCount new translations\n";
    }

    $tier2Elapsed = time() - $tier2Start;
    echo "\n  Tier 2 complete in " . round($tier2Elapsed / 60, 1) . " minutes\n\n";
}

// ════════════════════════════════════════════════════════════════════
//  TIER 3: MyMemory API — German + remaining gaps in other languages
// ════════════════════════════════════════════════════════════════════

if ($tierFilter === 0 || $tierFilter === 3) {
    echo "--- Tier 3: MyMemory API ---\n";

    // German always goes through MyMemory (no Apertium pair)
    // Other languages only if they still have gaps
    $tier3Langs = $targetLangs; // Check all — will skip those already covered
    echo "  Languages: " . implode(', ', $tier3Langs) . "\n";
    $tier3Start = time();

    foreach ($tier3Langs as $lang) {
        echo "\n  Processing: $lang\n";
        $langCount = 0;
        $langProcessed = 0;
        $gapCount = 0;

        foreach ($spanishWords as $sw) {
            $langProcessed++;

            // Skip if already has translation
            if (translationExists($pdo, (int) $sw['id'], $lang)) {
                continue;
            }
            $gapCount++;

            if ($langProcessed % $batchSize === 0) {
                echo "    [$langProcessed/$totalES] gaps=$gapCount, tier3_new=$langCount\n";
            }

            $translated = myMemoryTranslate($sw['word'], $lang);
            if (!$translated) {
                usleep(200000);
                continue;
            }

            if ($dryRun) {
                echo "  [dry-run] {$sw['word']} → $lang: $translated\n";
                $langCount++;
                $stats[$lang]['tier3']++;
                usleep(200000);
                continue;
            }

            $targetId = ensureWordEntry($pdo, $translated, $lang, $sw['cefr_level'], $sw['part_of_speech']);
            if ($targetId) {
                ensureTranslation($pdo, (int) $sw['id'], $targetId);
                $langCount++;
                $stats[$lang]['tier3']++;
            }

            // Rate limit: 200ms
            usleep(200000);
        }

        echo "    $lang tier 3: $langCount new translations (from $gapCount remaining gaps)\n";
    }

    $tier3Elapsed = time() - $tier3Start;
    echo "\n  Tier 3 complete in " . round($tier3Elapsed / 60, 1) . " minutes\n\n";
}

// ── Final Summary ────────────────────────────────────────────────────

echo "=== Translation Pipeline Summary ===\n\n";

echo str_pad('Lang', 6) . str_pad('Tier1', 8) . str_pad('Tier2', 8) . str_pad('Tier3', 8)
   . str_pad('Total', 8) . str_pad('Skipped', 10) . "\n";
echo str_repeat('-', 48) . "\n";

foreach ($targetLangs as $lang) {
    $s = $stats[$lang];
    $total = $s['tier1'] + $s['tier2'] + $s['tier3'];
    echo str_pad($lang, 6)
       . str_pad($s['tier1'], 8)
       . str_pad($s['tier2'], 8)
       . str_pad($s['tier3'], 8)
       . str_pad($total, 8)
       . str_pad($s['skipped'], 10)
       . "\n";
}

// Show current DB state
echo "\n--- Current DB Word Counts ---\n";
$stmt = $pdo->query(
    "SELECT lang_code, COUNT(*) as c FROM dict_words
     WHERE lang_code IN ('es','en','fr','de','pt','it','zh','ja','ko','ru','ar','nl')
     GROUP BY lang_code ORDER BY c DESC"
);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pct = $totalES > 0 ? round($row['c'] / $totalES * 100, 1) : 0;
    echo "  {$row['lang_code']}: " . number_format($row['c']) . " words ({$pct}% of ES)\n";
}

echo "\n--- Translation Links ---\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM dict_translations");
echo "  Total: " . number_format($stmt->fetchColumn()) . " (bidirectional)\n";

echo "\n=== Pipeline Complete ===\n";
