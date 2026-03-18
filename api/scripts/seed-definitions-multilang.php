<?php
/**
 * Multilingual Definitions Seeder — Adds definitions for word entries in all supported languages
 *
 * Two sources (tried in order):
 *   A) Target-language Wiktionary (e.g., fr.wiktionary.org for French words)
 *   B) Translate Spanish definitions via Apertium / MyMemory
 *
 * Definitions are inserted on both:
 *   - The Spanish word_id (lang_code = target) — bilingual dictionary view
 *   - The target-language word_id (lang_code = target) — monolingual dictionary view
 *
 * Requires: seed-translations-wiktionary.php to have been run first (creates word entries + links).
 *
 * Usage:
 *   php seed-definitions-multilang.php --all              # Phase A languages (en, fr, de, pt, it)
 *   php seed-definitions-multilang.php --all --phase=b    # Phase B languages (zh, ja, ko, ru, ar, nl)
 *   php seed-definitions-multilang.php --lang=fr          # Single language
 *   php seed-definitions-multilang.php --lang=de --source=translate  # Skip Wiktionary, translate only
 *   php seed-definitions-multilang.php --all --dry-run    # Preview
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// ── CLI options ──────────────────────────────────────────────────────

$targetLangs  = [];
$dryRun       = in_array('--dry-run', $argv ?? []);
$allLangs     = in_array('--all', $argv ?? []);
$sourceFilter = ''; // '', 'wiktionary', 'translate'
$batchSize    = 500;

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--lang=') === 0) $targetLangs[] = substr($arg, 7);
    if (strpos($arg, '--source=') === 0) $sourceFilter = substr($arg, 9);
    if (strpos($arg, '--batch=') === 0) $batchSize = (int) substr($arg, 8);
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
    echo "Usage: php seed-definitions-multilang.php --all [--phase=b] | --lang=XX [--source=wiktionary|translate] [--dry-run]\n";
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

echo "=== Multilingual Definitions Seeder ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Source filter: " . ($sourceFilter ?: 'both') . "\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n\n";

// ── Helpers ──────────────────────────────────────────────────────────

/**
 * Fetch definitions from target-language Wiktionary REST API.
 * Returns array of definition strings, or empty array.
 */
function fetchWiktionaryDefinitions(string $word, string $lang): array {
    // Map our lang codes to Wiktionary language section names
    $sectionMap = [
        'en' => 'en',
        'fr' => 'fr',
        'de' => 'de',
        'pt' => 'pt',
        'it' => 'it',
        'zh' => 'zh',
        'ja' => 'ja',
        'ko' => 'ko',
        'ru' => 'ru',
        'ar' => 'ar',
        'nl' => 'nl',
    ];

    $sectionLang = $sectionMap[$lang] ?? $lang;

    $url = "https://$lang.wiktionary.org/api/rest_v1/page/definition/" . rawurlencode($word);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: BabelFreeDictBot/1.0 (https://babelfree.com)\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return [];

    $data = json_decode($json, true);
    if (!$data) return [];

    $defs = [];

    // Look for the target language's own section first
    $sections = $data[$sectionLang] ?? $data[$lang] ?? [];
    if (empty($sections)) {
        // Try all sections — some Wiktionaries use different keys
        foreach ($data as $key => $secs) {
            if (is_array($secs)) {
                $sections = array_merge($sections, $secs);
            }
        }
    }

    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        foreach ($section['definitions'] ?? [] as $def) {
            $text = strip_tags($def['definition'] ?? '');
            $text = trim($text, " .\t\n\r");
            if ($text && mb_strlen($text) > 2 && mb_strlen($text) < 500) {
                $defs[] = $text;
            }
        }
    }

    return array_slice($defs, 0, 5); // Max 5 definitions
}

/**
 * Translate text using Apertium.
 */
function apertiumTranslateText(string $text, string $targetLang): ?string {
    $langPairs = [
        'en' => 'spa|eng',
        'fr' => 'spa|fra',
        'it' => 'spa|ita',
        'pt' => 'spa|por',
    ];

    if (!isset($langPairs[$targetLang])) return null;

    $url = 'https://apertium.org/apy/translate?' . http_build_query([
        'langpair' => $langPairs[$targetLang],
        'q'        => $text,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    $translated = $data['responseData']['translatedText'] ?? null;
    if (!$translated) return null;

    $translated = trim($translated);
    if (strpos($translated, '*') !== false) return null; // Unknown word marker
    if (!$translated || mb_strlen($translated) > 1000) return null;

    return $translated;
}

/**
 * Translate text using MyMemory.
 */
function myMemoryTranslateText(string $text, string $targetLang): ?string {
    $langMap = [
        'en' => 'en', 'fr' => 'fr', 'de' => 'de', 'pt' => 'pt', 'it' => 'it',
        'zh' => 'zh-CN', 'ja' => 'ja', 'ko' => 'ko', 'ru' => 'ru', 'ar' => 'ar', 'nl' => 'nl',
    ];
    if (!isset($langMap[$targetLang])) return null;

    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q'        => $text,
        'langpair' => 'es|' . $langMap[$targetLang],
        'de'       => 'info@babelfree.com',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header'  => "User-Agent: BabelFreeDictBot/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (($data['responseStatus'] ?? 0) !== 200) return null;

    $translated = $data['responseData']['translatedText'] ?? null;
    if (!$translated) return null;

    $translated = trim($translated);
    if (!$translated || mb_strlen($translated) > 1000) return null;

    return $translated;
}

/**
 * Translate a definition text to target language, trying Apertium first, then MyMemory.
 */
function translateDefinition(string $text, string $targetLang): ?string {
    // Try Apertium first (better quality, but no German)
    $result = apertiumTranslateText($text, $targetLang);
    if ($result) return $result;

    // Fallback to MyMemory
    return myMemoryTranslateText($text, $targetLang);
}

/**
 * Insert a definition if it doesn't already exist.
 */
function insertDefinition(PDO $pdo, int $wordId, string $langCode, string $definition,
                          int $sortOrder = 0): bool {
    static $checkStmt = null;
    static $insertStmt = null;

    if (!$checkStmt) {
        $checkStmt = $pdo->prepare(
            'SELECT 1 FROM dict_definitions WHERE word_id = ? AND lang_code = ? AND definition = ? LIMIT 1'
        );
        $insertStmt = $pdo->prepare(
            'INSERT INTO dict_definitions (word_id, lang_code, definition, sort_order)
             VALUES (?, ?, ?, ?)'
        );
    }

    // Avoid exact duplicates
    $checkStmt->execute([$wordId, $langCode, $definition]);
    if ($checkStmt->fetchColumn()) return false;

    $insertStmt->execute([$wordId, $langCode, $definition, $sortOrder]);
    return true;
}

// ── Process each language ────────────────────────────────────────────

foreach ($targetLangs as $lang) {
    echo "\n=== Processing: $lang ===\n";

    // Get all Spanish words that have a translation link to this language
    $stmt = $pdo->prepare("
        SELECT sw.id AS es_word_id, sw.word AS es_word, tw.id AS target_word_id, tw.word AS target_word
        FROM dict_words sw
        JOIN dict_translations dt ON dt.source_word_id = sw.id
        JOIN dict_words tw ON tw.id = dt.target_word_id AND tw.lang_code = ?
        WHERE sw.lang_code = 'es'
        ORDER BY sw.frequency_rank ASC, sw.id ASC
    ");
    $stmt->execute([$lang]);
    $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPairs = count($pairs);
    echo "  Word pairs (ES→$lang): $totalPairs\n";

    // Count how many target words already have definitions
    $stmtCheck = $pdo->prepare(
        "SELECT COUNT(DISTINCT tw.id)
         FROM dict_words tw
         JOIN dict_definitions d ON d.word_id = tw.id AND d.lang_code = ?
         WHERE tw.lang_code = ?"
    );
    $stmtCheck->execute([$lang, $lang]);
    $existingDefs = (int) $stmtCheck->fetchColumn();
    echo "  Already have definitions: $existingDefs\n";

    $wiktCount = 0;
    $transCount = 0;
    $skipCount = 0;
    $processed = 0;
    $startTime = time();

    // Prepare statement to get Spanish definitions
    $stmtEsDef = $pdo->prepare(
        "SELECT definition FROM dict_definitions
         WHERE word_id = ? AND lang_code = 'es'
         ORDER BY sort_order ASC
         LIMIT 3"
    );

    // Check if target word already has defs in target language
    $stmtHasDef = $pdo->prepare(
        "SELECT 1 FROM dict_definitions WHERE word_id = ? AND lang_code = ? LIMIT 1"
    );

    foreach ($pairs as $pair) {
        $processed++;

        if ($processed % $batchSize === 0) {
            $elapsed = time() - $startTime;
            $rate = $processed / max($elapsed, 1);
            echo "  [$processed/$totalPairs] wikt=$wiktCount, trans=$transCount, skip=$skipCount, "
               . round($rate, 1) . " words/sec\n";
        }

        $targetWordId = (int) $pair['target_word_id'];
        $esWordId     = (int) $pair['es_word_id'];
        $targetWord   = $pair['target_word'];

        // Skip if target word already has definitions in this language
        $stmtHasDef->execute([$targetWordId, $lang]);
        if ($stmtHasDef->fetchColumn()) {
            $skipCount++;
            continue;
        }

        // ── Source A: Target-language Wiktionary ──
        if ($sourceFilter === '' || $sourceFilter === 'wiktionary') {
            $wiktDefs = fetchWiktionaryDefinitions($targetWord, $lang);

            if (!empty($wiktDefs) && !$dryRun) {
                $order = 0;
                foreach ($wiktDefs as $def) {
                    // Insert on target word_id (monolingual view)
                    insertDefinition($pdo, $targetWordId, $lang, $def, $order);
                    // Also insert on Spanish word_id (bilingual view)
                    insertDefinition($pdo, $esWordId, $lang, $def, $order);
                    $order++;
                }
                $wiktCount++;
                usleep(500000); // 500ms between Wiktionary calls
                continue;
            }

            if (!empty($wiktDefs) && $dryRun) {
                echo "  [dry-run] {$targetWord} ($lang): " . count($wiktDefs) . " defs from Wiktionary\n";
                $wiktCount++;
                usleep(200000);
                continue;
            }

            usleep(300000); // 300ms even on miss
        }

        // ── Source B: Translate Spanish definitions ──
        if ($sourceFilter === '' || $sourceFilter === 'translate') {
            $stmtEsDef->execute([$esWordId]);
            $esDefs = $stmtEsDef->fetchAll(PDO::FETCH_COLUMN);

            if (empty($esDefs)) continue;

            $insertedAny = false;
            $order = 0;

            foreach ($esDefs as $esDef) {
                $translated = translateDefinition($esDef, $lang);
                if (!$translated) continue;

                if ($dryRun) {
                    echo "  [dry-run] {$pair['es_word']} → $lang: " . mb_substr($translated, 0, 60) . "...\n";
                    $insertedAny = true;
                    continue;
                }

                // Insert on target word_id
                insertDefinition($pdo, $targetWordId, $lang, $translated, $order);
                // Insert on Spanish word_id
                insertDefinition($pdo, $esWordId, $lang, $translated, $order);
                $insertedAny = true;
                $order++;

                usleep(200000); // 200ms between translation calls
            }

            if ($insertedAny) $transCount++;
        }
    }

    $elapsed = time() - $startTime;
    echo "\n  $lang complete in " . round($elapsed / 60, 1) . " minutes\n";
    echo "    Wiktionary definitions: $wiktCount words\n";
    echo "    Translated definitions: $transCount words\n";
    echo "    Already had defs: $skipCount words\n";
}

// ── Final Summary ────────────────────────────────────────────────────

echo "\n=== Definitions Summary ===\n\n";

$stmt = $pdo->query("
    SELECT d.lang_code, COUNT(*) as def_count, COUNT(DISTINCT d.word_id) as word_count
    FROM dict_definitions d
    JOIN dict_words w ON w.id = d.word_id
    WHERE d.lang_code IN ('es','en','fr','de','pt','it','zh','ja','ko','ru','ar','nl')
    GROUP BY d.lang_code
    ORDER BY def_count DESC
");

echo str_pad('Lang', 6) . str_pad('Words w/defs', 15) . str_pad('Total defs', 12) . "\n";
echo str_repeat('-', 33) . "\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo str_pad($row['lang_code'], 6)
       . str_pad(number_format($row['word_count']), 15)
       . str_pad(number_format($row['def_count']), 12)
       . "\n";
}

echo "\n=== Seeding Complete ===\n";
