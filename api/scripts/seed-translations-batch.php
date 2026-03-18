<?php
/**
 * Batch Translation Seeder — Creates multilingual word entries + translation links
 *
 * Strategy:
 *  Phase A: English, French, German, Portuguese, Italian from Wiktionary (free, high quality)
 *  Phase B: Remaining 99 languages via Google Cloud Translation API
 *
 * For the hyperlink model: every translation target gets its own dict_words entry
 * with lang_code set to the target language, linked via dict_translations.
 *
 * Run: php seed-translations-batch.php [--phase=A|B|all] [--limit=500] [--dry-run]
 *
 * Phase B requires GOOGLE_TRANSLATE_API_KEY environment variable.
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// CLI options
$phase = 'all';
$limit = 5000;
$dryRun = in_array('--dry-run', $argv ?? []);
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--phase=') === 0) $phase = strtoupper(substr($arg, 8));
    if (strpos($arg, '--limit=') === 0) $limit = (int)substr($arg, 8);
}

echo "=== Batch Translation Seeder ===\n";
echo "  Phase: $phase\n";
echo "  Limit: $limit\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n\n";

// ── Helpers ────────────────────────────────────────────────────────

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
    $word = str_replace(['ñ','ç','ß'], ['n','c','ss'], $word);
    return trim($word);
}

// Insert a word into dict_words for a given language, return its ID
function ensureWordEntry(PDO $pdo, string $word, string $langCode, ?string $cefrLevel = null, ?string $pos = null): ?int {
    static $insertStmt = null;
    static $findStmt = null;

    if (!$insertStmt) {
        $insertStmt = $pdo->prepare(
            'INSERT INTO dict_words (lang_code, word, word_normalized, cefr_level, part_of_speech)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)'
        );
        $findStmt = $pdo->prepare(
            'SELECT id FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1'
        );
    }

    $norm = normalize($word);
    if (!$norm) return null;

    if (!$pos) $pos = 'noun';

    $insertStmt->execute([$langCode, $word, $norm, $cefrLevel, $pos]);
    $id = $pdo->lastInsertId();

    if (!$id) {
        $findStmt->execute([$langCode, $norm]);
        $row = $findStmt->fetch();
        $id = $row ? (int)$row['id'] : null;
    }

    return $id ? (int)$id : null;
}

// Link source → target in dict_translations
function ensureTranslation(PDO $pdo, int $sourceId, int $targetId): void {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO dict_translations (source_word_id, target_word_id) VALUES (?, ?)'
        );
    }
    $stmt->execute([$sourceId, $targetId]);
    // Bidirectional link
    $stmt->execute([$targetId, $sourceId]);
}

// ── Load Spanish source words ──────────────────────────────────────

$stmt = $pdo->prepare(
    "SELECT id, word, word_normalized, cefr_level FROM dict_words
     WHERE lang_code = 'es'
     ORDER BY frequency_rank ASC
     LIMIT ?"
);
$stmt->execute([$limit]);
$spanishWords = $stmt->fetchAll();
echo "Source Spanish words: " . count($spanishWords) . "\n\n";

// ── Phase A: Wiktionary translations (en, fr, de, pt, it) ─────────

$phaseALangs = ['en', 'fr', 'de', 'pt', 'it'];

if ($phase === 'A' || $phase === 'ALL') {
    echo "--- Phase A: Wiktionary translations ---\n";

    foreach ($phaseALangs as $targetLang) {
        echo "\n  Language: $targetLang\n";
        $count = 0;

        foreach ($spanishWords as $sw) {
            // Try Wiktionary API for this word in target language's Wiktionary
            $url = "https://$targetLang.wiktionary.org/api/rest_v1/page/definition/" . rawurlencode($sw['word']);
            $ctx = stream_context_create([
                'http' => ['timeout' => 8, 'header' => "User-Agent: YaguaraDictBot/1.0\r\n"],
            ]);
            $json = @file_get_contents($url, false, $ctx);

            if ($json) {
                $data = json_decode($json, true);
                // Look for the Spanish section which often contains translations
                $translations = [];
                if (!empty($data['es'])) {
                    foreach ($data['es'] as $section) {
                        foreach ($section['definitions'] ?? [] as $def) {
                            // Extract translation hints from definition text
                            $defText = strip_tags($def['definition'] ?? '');
                            if ($defText && mb_strlen($defText) < 100) {
                                $translations[] = $defText;
                            }
                        }
                    }
                }

                // If we found a translation-like definition, use first one
                if (!empty($translations) && !$dryRun) {
                    $transWord = $translations[0];
                    // Clean up: take first word/phrase before comma or semicolon
                    $transWord = preg_split('/[,;]/', $transWord)[0];
                    $transWord = trim($transWord, ' .');

                    if ($transWord && mb_strlen($transWord) < 100) {
                        $targetId = ensureWordEntry($pdo, $transWord, $targetLang, $sw['cefr_level']);
                        if ($targetId) {
                            ensureTranslation($pdo, (int)$sw['id'], $targetId);
                            $count++;
                        }
                    }
                }
            }

            // Rate limit: 300ms between requests
            usleep(300000);

            if ($count > 0 && $count % 100 === 0) {
                echo "    Progress: $count translations\n";
            }
        }

        echo "    Total for $targetLang: $count translations\n";
    }
}

// ── Phase B: Google Cloud Translation API (remaining languages) ────

if ($phase === 'B' || $phase === 'ALL') {
    echo "\n--- Phase B: Google Cloud Translation ---\n";

    $apiKey = getenv('GOOGLE_TRANSLATE_API_KEY');
    if (!$apiKey) {
        echo "  SKIPPED: Set GOOGLE_TRANSLATE_API_KEY environment variable to enable.\n";
    } else {
        // Get all active languages except Spanish and Phase A languages
        $skipLangs = array_merge(['es'], $phaseALangs);
        $placeholders = implode(',', array_fill(0, count($skipLangs), '?'));

        $langStmt = $pdo->prepare(
            "SELECT code FROM dict_languages WHERE is_active = 1 AND code NOT IN ($placeholders) ORDER BY code"
        );
        $langStmt->execute($skipLangs);
        $targetLangs = array_column($langStmt->fetchAll(), 'code');

        echo "  Target languages: " . count($targetLangs) . "\n";

        // Batch translate: Google API accepts up to 128 texts per request
        $batchSize = 100;
        $totalTranslations = 0;

        foreach ($targetLangs as $targetLang) {
            echo "\n  Language: $targetLang\n";
            $langCount = 0;

            // Map Google language codes (some differ from our codes)
            $googleLang = $targetLang;
            if ($targetLang === 'zh') $googleLang = 'zh-CN';
            if ($targetLang === 'zh-tw') $googleLang = 'zh-TW';

            for ($b = 0; $b < count($spanishWords); $b += $batchSize) {
                $batch = array_slice($spanishWords, $b, $batchSize);
                $texts = array_column($batch, 'word');

                // Google Translate API v2 batch request
                $postData = [
                    'q' => $texts,
                    'source' => 'es',
                    'target' => $googleLang,
                    'format' => 'text',
                    'key' => $apiKey,
                ];

                $ch = curl_init('https://translation.googleapis.com/language/translate/v2');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($postData),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                ]);

                if ($dryRun) {
                    curl_close($ch);
                    echo "    [dry-run] Would translate batch of " . count($texts) . " words\n";
                    continue;
                }

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    echo "    ERROR: HTTP $httpCode for batch at offset $b\n";
                    $errorData = json_decode($response, true);
                    if ($errorData) {
                        echo "    " . ($errorData['error']['message'] ?? 'Unknown error') . "\n";
                    }
                    continue;
                }

                $result = json_decode($response, true);
                $translations = $result['data']['translations'] ?? [];

                foreach ($translations as $i => $trans) {
                    $translatedText = html_entity_decode($trans['translatedText'] ?? '', ENT_QUOTES, 'UTF-8');
                    if (!$translatedText) continue;

                    $sourceWord = $batch[$i];
                    $targetId = ensureWordEntry($pdo, $translatedText, $targetLang, $sourceWord['cefr_level']);
                    if ($targetId) {
                        ensureTranslation($pdo, (int)$sourceWord['id'], $targetId);
                        $langCount++;
                    }
                }

                // Rate limit: 100ms between batch requests
                usleep(100000);
            }

            echo "    Total for $targetLang: $langCount translations\n";
            $totalTranslations += $langCount;
        }

        echo "\n  Phase B total: $totalTranslations translations\n";
    }
}

// ── Summary ────────────────────────────────────────────────────────

echo "\n=== Translation Seeding Complete ===\n";

$stmt = $pdo->query(
    "SELECT lang_code, COUNT(*) as c FROM dict_words GROUP BY lang_code ORDER BY c DESC LIMIT 20"
);
echo "Top 20 languages by word count:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['lang_code']}: {$row['c']}\n";
}

$stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_translations");
echo "\nTotal translation links: " . $stmt->fetch()['c'] . "\n";
