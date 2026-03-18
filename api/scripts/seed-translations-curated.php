<?php
/**
 * Batch-translate curated Spanish dictionary entries into 104 target languages
 * via Google Cloud Translation API v2.
 *
 * Translates word-level entries only (not definitions or examples).
 * Creates dict_words entries for each target language and bidirectional
 * dict_translations links.
 *
 * Features:
 *   - Resume-capable: skips words that already have translations for a given language
 *   - Transaction per language: rollback on failure, skip to next
 *   - Batches of 100 words per API call (Google max is 128)
 *   - Google code mapping for mismatched codes (zh→zh-CN, etc.)
 *
 * Run:
 *   php seed-translations-curated.php --key=AIzaSy...
 *   php seed-translations-curated.php --key=AIzaSy... --lang=en --limit=10 --dry-run
 *   php seed-translations-curated.php --key=AIzaSy... --lang=fr
 *
 * Cost estimate: ~878 words × 8 chars avg × 104 langs = ~730K chars.
 *   Google free tier: 500K chars/month. Overage: ~$4-5 one-time.
 */

require_once __DIR__ . '/../config/database.php';

// ── CLI Options ────────────────────────────────────────────────────

$apiKey  = null;
$onlyLang = null;
$limit   = 0; // 0 = no limit
$dryRun  = in_array('--dry-run', $argv ?? []);

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--key=') === 0)   $apiKey   = substr($arg, 6);
    if (strpos($arg, '--lang=') === 0)  $onlyLang = substr($arg, 7);
    if (strpos($arg, '--limit=') === 0) $limit    = (int)substr($arg, 8);
}

if (!$apiKey) {
    echo "Usage: php seed-translations-curated.php --key=YOUR_GOOGLE_API_KEY [--lang=en] [--limit=50] [--dry-run]\n";
    exit(1);
}

$pdo = getDB();

echo "=== Curated Translation Seeder ===\n";
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

// ── Load curated Spanish words ──────────────────────────────────────

$sql = "
    SELECT DISTINCT w.id, w.word, w.word_normalized, w.part_of_speech, w.cefr_level
    FROM dict_words w
    INNER JOIN dict_examples e ON e.word_id = w.id
    WHERE w.lang_code = 'es'
      AND e.source LIKE 'curated_%'
    ORDER BY w.id
";
if ($limit > 0) $sql .= " LIMIT $limit";

$spanishWords = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "Curated Spanish words: " . count($spanishWords) . "\n\n";

if (empty($spanishWords)) {
    echo "No curated words found. Run the curated seeders first.\n";
    exit(1);
}

// Index by id for fast lookup
$spanishById = [];
foreach ($spanishWords as $sw) {
    $spanishById[(int)$sw['id']] = $sw;
}
$spanishIds = array_keys($spanishById);

// ── Determine target languages ──────────────────────────────────────

if ($onlyLang) {
    $targetLangs = [$onlyLang];
} else {
    $stmt = $pdo->query("SELECT code FROM dict_languages WHERE code != 'es' ORDER BY code");
    $targetLangs = array_column($stmt->fetchAll(), 'code');
}

echo "Target languages: " . count($targetLangs) . "\n\n";

// ── Prepared statements ─────────────────────────────────────────────

$stmtFindWord = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1"
);

$stmtInsertWord = $pdo->prepare("
    INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, cefr_level)
    VALUES (?, ?, ?, ?, ?)
");

$stmtInsertLink = $pdo->prepare(
    "INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)"
);

// ── Process each language ───────────────────────────────────────────

$batchSize = 100;
$grandTotal = 0;

foreach ($targetLangs as $langCode) {
    echo "--- $langCode ---\n";

    // Find which Spanish words already have translations for this language
    $placeholders = implode(',', array_fill(0, count($spanishIds), '?'));
    $checkSql = "
        SELECT DISTINCT dt.source_word_id
        FROM dict_translations dt
        INNER JOIN dict_words tw ON tw.id = dt.target_word_id
        WHERE dt.source_word_id IN ($placeholders)
          AND tw.lang_code = ?
    ";
    $checkStmt = $pdo->prepare($checkSql);
    $checkParams = $spanishIds;
    $checkParams[] = $langCode;
    $checkStmt->execute($checkParams);
    $alreadyDone = array_column($checkStmt->fetchAll(), 'source_word_id');
    $alreadyDoneSet = array_flip($alreadyDone);

    // Filter to untranslated words
    $todo = [];
    foreach ($spanishWords as $sw) {
        if (!isset($alreadyDoneSet[$sw['id']])) {
            $todo[] = $sw;
        }
    }

    echo "  Already translated: " . count($alreadyDone) . " / " . count($spanishWords) . "\n";
    echo "  To translate: " . count($todo) . "\n";

    if (empty($todo)) {
        echo "  Skipping (all done).\n\n";
        continue;
    }

    if ($dryRun) {
        echo "  [DRY RUN] Would translate " . count($todo) . " words via Google API\n\n";
        continue;
    }

    // Process in batches
    $pdo->beginTransaction();
    $langCount = 0;
    $langErrors = 0;

    try {
        for ($b = 0; $b < count($todo); $b += $batchSize) {
            $batch = array_slice($todo, $b, $batchSize);
            $texts = array_column($batch, 'word');

            // Call Google Translate API
            $translated = googleTranslateBatch($texts, $langCode, $apiKey);

            if ($translated === null) {
                echo "  API call failed at batch offset $b — rolling back this language.\n";
                $langErrors++;
                break;
            }

            if (count($translated) !== count($batch)) {
                echo "  WARNING: Got " . count($translated) . " translations for " . count($batch) . " words at offset $b\n";
            }

            $count = min(count($translated), count($batch));
            for ($i = 0; $i < $count; $i++) {
                $transText = trim($translated[$i]);
                if (!$transText) continue;

                // Skip if translation is identical to source (Google sometimes echoes back)
                if (mb_strtolower($transText) === mb_strtolower($batch[$i]['word'])) continue;

                $transNorm = normalize($transText);
                if (!$transNorm) continue;

                $sourceId = (int)$batch[$i]['id'];
                $pos = $batch[$i]['part_of_speech'];
                $cefr = $batch[$i]['cefr_level'];

                // Check if target word already exists
                $stmtFindWord->execute([$langCode, $transNorm]);
                $existing = $stmtFindWord->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $targetId = (int)$existing['id'];
                } else {
                    // Insert new word
                    $stmtInsertWord->execute([$langCode, $transText, $transNorm, $pos, $cefr]);
                    $targetId = (int)$pdo->lastInsertId();
                }

                if (!$targetId) continue;

                // Bidirectional translation links
                $stmtInsertLink->execute([$sourceId, $targetId]);
                $stmtInsertLink->execute([$targetId, $sourceId]);
                $langCount++;
            }

            // Brief pause between batches to avoid rate limits
            usleep(50000); // 50ms

            if (($b + $batchSize) % 500 === 0 || ($b + $batchSize) >= count($todo)) {
                echo "  Progress: " . min($b + $batchSize, count($todo)) . "/" . count($todo) . " ($langCount translations)\n";
            }
        }

        if ($langErrors > 0) {
            $pdo->rollBack();
            echo "  ROLLED BACK due to errors.\n\n";
        } else {
            $pdo->commit();
            echo "  Committed: $langCount translations for $langCode\n\n";
            $grandTotal += $langCount;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "  EXCEPTION: " . $e->getMessage() . "\n";
        echo "  ROLLED BACK.\n\n";
    }
}

// ── Summary ─────────────────────────────────────────────────────────

echo "=== Translation Seeding Complete ===\n";
echo "  New translations this run: $grandTotal\n\n";

// Stats
$stmt = $pdo->query("
    SELECT lang_code, COUNT(*) as c
    FROM dict_words
    WHERE lang_code != 'es'
    GROUP BY lang_code
    ORDER BY c DESC
    LIMIT 20
");
$rows = $stmt->fetchAll();
if ($rows) {
    echo "Top 20 languages by word count:\n";
    foreach ($rows as $r) {
        echo "  {$r['lang_code']}: {$r['c']}\n";
    }
}

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_translations");
echo "\nTotal translation links: " . $stmt->fetch()['c'] . "\n";

echo "\nDone.\n";
