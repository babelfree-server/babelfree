<?php
/**
 * Translate Spanish definitions into target languages via Google Cloud Translation API v2.
 *
 * For each translated definition:
 *   1. Insert on the Spanish word_id with lang_code=target — powers CEFR-gated definition
 *      language for logged-in users looking up Spanish words
 *   2. Find the corresponding target-language word via dict_translations, insert on that
 *      word_id with lang_code=target — powers the monolingual target-language dictionary
 *
 * Features:
 *   - Resume-capable: skips words that already have definitions in the target language
 *   - Transaction per language: rollback on failure, skip to next
 *   - Batches of 100 definitions per API call (Google max is 128)
 *   - Follows the exact pattern of seed-translations-curated.php
 *
 * Run:
 *   php seed-definitions-translated.php --key=AIzaSy... --lang=en
 *   php seed-definitions-translated.php --key=AIzaSy... --lang=en --limit=50 --dry-run
 *   php seed-definitions-translated.php --key=AIzaSy...   (all non-es languages)
 *
 * Cost estimate: ~878 defs x ~80 chars = ~70K chars per language (well within Google free tier).
 */

require_once __DIR__ . '/../config/database.php';

// ── CLI Options ────────────────────────────────────────────────────

$apiKey   = null;
$onlyLang = null;
$limit    = 0; // 0 = no limit
$dryRun   = in_array('--dry-run', $argv ?? []);

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--key=') === 0)   $apiKey   = substr($arg, 6);
    if (strpos($arg, '--lang=') === 0)  $onlyLang = substr($arg, 7);
    if (strpos($arg, '--limit=') === 0) $limit    = (int)substr($arg, 8);
}

if (!$apiKey) {
    echo "Usage: php seed-definitions-translated.php --key=YOUR_GOOGLE_API_KEY [--lang=en] [--limit=50] [--dry-run]\n";
    exit(1);
}

$pdo = getDB();

echo "=== Definition Translation Seeder ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n";
if ($onlyLang) echo "  Language: $onlyLang\n";
if ($limit)    echo "  Limit: $limit words\n";
echo "\n";

// ── Helpers ─────────────────────────────────────────────────────────

function normalize(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    $word = preg_replace('/[áàâã]/u', 'a', $word);
    $word = preg_replace('/[éèê]/u', 'e', $word);
    $word = preg_replace('/[íìî]/u', 'i', $word);
    $word = preg_replace('/[óòôõ]/u', 'o', $word);
    $word = preg_replace('/[úùû]/u', 'u', $word);
    $word = str_replace(['ñ', 'ç', 'ß'], ['n', 'c', 'ss'], $word);
    return trim($word);
}

// Map our language codes to Google Translate API codes
function googleLangCode(string $code): string {
    $map = [
        'zh'    => 'zh-CN',
        'zh-tw' => 'zh-TW',
        'no'    => 'nb',
        'tl'    => 'fil',
    ];
    return $map[$code] ?? $code;
}

// Call Google Translate API v2 — returns array of translated strings or null on error
function googleTranslateBatch(array $texts, string $targetLang, string $apiKey): ?array {
    $postData = json_encode([
        'q'      => $texts,
        'source' => 'es',
        'target' => googleLangCode($targetLang),
        'format' => 'text',
    ]);

    $url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "    CURL ERROR: $curlErr\n";
        return null;
    }

    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        $msg = $err['error']['message'] ?? 'Unknown';
        echo "    API ERROR (HTTP $httpCode): $msg\n";
        return null;
    }

    $result = json_decode($response, true);
    $translations = $result['data']['translations'] ?? [];

    return array_map(function ($t) {
        return html_entity_decode($t['translatedText'] ?? '', ENT_QUOTES, 'UTF-8');
    }, $translations);
}

// ── Load curated Spanish words with their definitions ──────────────

$sql = "
    SELECT w.id AS word_id, w.word, d.id AS def_id, d.definition, d.usage_note, d.sort_order
    FROM dict_words w
    INNER JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = 'es'
    WHERE w.lang_code = 'es'
    ORDER BY w.id, d.sort_order
";
if ($limit > 0) $sql = "SELECT * FROM ($sql) sub LIMIT $limit";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "Spanish definitions to translate: " . count($rows) . "\n";

if (empty($rows)) {
    echo "No Spanish definitions found. Run the definition seeders first.\n";
    exit(1);
}

// Group by word_id for resume checking
$byWordId = [];
foreach ($rows as $row) {
    $byWordId[(int)$row['word_id']][] = $row;
}
$wordIds = array_keys($byWordId);
echo "Unique Spanish words with definitions: " . count($wordIds) . "\n\n";

// ── Determine target languages ──────────────────────────────────────

if ($onlyLang) {
    $targetLangs = [$onlyLang];
} else {
    $stmt = $pdo->query("SELECT code FROM dict_languages WHERE code != 'es' ORDER BY code");
    $targetLangs = array_column($stmt->fetchAll(), 'code');
}

echo "Target languages: " . count($targetLangs) . "\n\n";

// ── Prepared statements ─────────────────────────────────────────────

// Check if definition already exists for a word+lang
$stmtCheckDef = $pdo->prepare(
    "SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND lang_code = ? LIMIT 1"
);

// Insert translated definition on the Spanish word_id (powers CEFR-gated def language)
$stmtInsertDef = $pdo->prepare("
    INSERT INTO dict_definitions (word_id, lang_code, definition, usage_note, sort_order)
    VALUES (?, ?, ?, ?, ?)
");

// Find translation target word_id for a given Spanish word in the target language
$stmtFindTranslation = $pdo->prepare("
    SELECT tw.id
    FROM dict_translations dt
    JOIN dict_words tw ON tw.id = dt.target_word_id
    WHERE dt.source_word_id = ? AND tw.lang_code = ?
    LIMIT 1
");

// ── Process each language ───────────────────────────────────────────

$batchSize  = 100;
$grandTotal = 0;

foreach ($targetLangs as $langCode) {
    echo "--- $langCode ---\n";

    // Find which words already have definitions in this language
    $alreadyDone = [];
    foreach ($wordIds as $wid) {
        $stmtCheckDef->execute([$wid, $langCode]);
        if ((int)$stmtCheckDef->fetchColumn() > 0) {
            $alreadyDone[] = $wid;
        }
    }
    $alreadyDoneSet = array_flip($alreadyDone);

    // Filter to words that need translation
    $todo = [];
    foreach ($byWordId as $wid => $defs) {
        if (!isset($alreadyDoneSet[$wid])) {
            $todo[$wid] = $defs;
        }
    }

    echo "  Already translated: " . count($alreadyDone) . " / " . count($wordIds) . "\n";
    echo "  To translate: " . count($todo) . "\n";

    if (empty($todo)) {
        echo "  Skipping (all done).\n\n";
        continue;
    }

    if ($dryRun) {
        // Count total definitions
        $defCount = 0;
        foreach ($todo as $defs) $defCount += count($defs);
        echo "  [DRY RUN] Would translate $defCount definitions for " . count($todo) . " words via Google API\n\n";
        continue;
    }

    // Flatten definitions for batch translation
    $flatDefs = [];
    foreach ($todo as $wid => $defs) {
        foreach ($defs as $def) {
            $flatDefs[] = [
                'word_id'    => (int)$wid,
                'def_text'   => $def['definition'],
                'usage_note' => $def['usage_note'],
                'sort_order' => (int)$def['sort_order'],
            ];
        }
    }

    echo "  Total definitions to translate: " . count($flatDefs) . "\n";

    // Process in batches
    $pdo->beginTransaction();
    $langCount  = 0;
    $langErrors = 0;

    try {
        for ($b = 0; $b < count($flatDefs); $b += $batchSize) {
            $batch = array_slice($flatDefs, $b, $batchSize);
            $texts = array_column($batch, 'def_text');

            // Call Google Translate API
            $translated = googleTranslateBatch($texts, $langCode, $apiKey);

            if ($translated === null) {
                echo "  API call failed at batch offset $b — rolling back this language.\n";
                $langErrors++;
                break;
            }

            if (count($translated) !== count($batch)) {
                echo "  WARNING: Got " . count($translated) . " translations for " . count($batch) . " definitions at offset $b\n";
            }

            $count = min(count($translated), count($batch));
            for ($i = 0; $i < $count; $i++) {
                $transText = trim($translated[$i]);
                if (!$transText) continue;

                $wordId    = $batch[$i]['word_id'];
                $usageNote = $batch[$i]['usage_note'];
                $sortOrder = $batch[$i]['sort_order'];

                // 1. Insert definition on the SPANISH word_id with target lang_code
                //    This powers CEFR-gated definition display for logged-in users
                $stmtInsertDef->execute([$wordId, $langCode, $transText, $usageNote, $sortOrder]);

                // 2. Find the corresponding target-language word via dict_translations
                //    and insert definition on THAT word_id too (powers monolingual dictionary)
                $stmtFindTranslation->execute([$wordId, $langCode]);
                $targetRow = $stmtFindTranslation->fetch(PDO::FETCH_ASSOC);
                if ($targetRow) {
                    $targetWordId = (int)$targetRow['id'];
                    // Check if definition already exists on target word
                    $stmtCheckDef->execute([$targetWordId, $langCode]);
                    if ((int)$stmtCheckDef->fetchColumn() === 0) {
                        $stmtInsertDef->execute([$targetWordId, $langCode, $transText, $usageNote, $sortOrder]);
                    }
                }

                $langCount++;
            }

            // Brief pause between batches to avoid rate limits
            usleep(50000); // 50ms

            if (($b + $batchSize) % 500 === 0 || ($b + $batchSize) >= count($flatDefs)) {
                echo "  Progress: " . min($b + $batchSize, count($flatDefs)) . "/" . count($flatDefs) . " ($langCount definitions)\n";
            }
        }

        if ($langErrors > 0) {
            $pdo->rollBack();
            echo "  ROLLED BACK due to errors.\n\n";
        } else {
            $pdo->commit();
            echo "  Committed: $langCount definitions for $langCode\n\n";
            $grandTotal += $langCount;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "  EXCEPTION: " . $e->getMessage() . "\n";
        echo "  ROLLED BACK.\n\n";
    }
}

// ── Summary ─────────────────────────────────────────────────────────

echo "=== Definition Translation Complete ===\n";
echo "  New definitions this run: $grandTotal\n\n";

// Stats
$stmt = $pdo->query("
    SELECT lang_code, COUNT(*) as c
    FROM dict_definitions
    WHERE lang_code != 'es'
    GROUP BY lang_code
    ORDER BY c DESC
    LIMIT 20
");
$rows = $stmt->fetchAll();
if ($rows) {
    echo "Top 20 languages by definition count:\n";
    foreach ($rows as $r) {
        echo "  {$r['lang_code']}: {$r['c']}\n";
    }
}

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_definitions");
echo "\nTotal definitions: " . $stmt->fetch()['c'] . "\n";

echo "\nDone.\n";
