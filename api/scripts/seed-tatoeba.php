<?php
/**
 * seed-tatoeba.php — Fetch and match Tatoeba example sentences to dictionary words
 *
 * Tatoeba (tatoeba.org) provides CC-licensed sentence pairs.
 * This script downloads Spanish sentences + English translation links,
 * then matches them to dict_words entries.
 *
 * Data files (auto-downloaded from Tatoeba exports):
 *   - sentences_spa.tsv   (Spanish sentences)
 *   - sentences_eng.tsv   (English sentences)
 *   - links.csv           (sentence translation links)
 *
 * Usage:
 *   php seed-tatoeba.php                         → Download + seed to database
 *   php seed-tatoeba.php --skip-download         → Use existing TSV files
 *   php seed-tatoeba.php --standalone             → Output JSON file instead of DB
 *   php seed-tatoeba.php --limit=500             → Limit words processed
 *   php seed-tatoeba.php --max-per-word=2        → Max examples per word (default: 2)
 *
 * Tatoeba data is CC-BY 2.0: https://tatoeba.org/en/downloads
 */

$dataDir = __DIR__ . '/data';
$cacheDir = $dataDir . '/tatoeba_cache';

// CLI options
$skipDownload = in_array('--skip-download', $argv ?? []);
$standalone = in_array('--standalone', $argv ?? []);
$limit = 10000;
$maxPerWord = 2;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--limit=') === 0) $limit = (int)substr($arg, 8);
    if (strpos($arg, '--max-per-word=') === 0) $maxPerWord = (int)substr($arg, 15);
}

echo "=== Tatoeba Example Sentence Seeder ===\n";
echo "  Limit: $limit words\n";
echo "  Max per word: $maxPerWord\n";
echo "  Mode: " . ($standalone ? 'standalone (JSON)' : 'database') . "\n\n";

// ── Step 1: Download Tatoeba data ────────────────────────────────────

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$spaFile = $cacheDir . '/sentences_spa.tsv';
$engFile = $cacheDir . '/sentences_eng.tsv';
$linksFile = $cacheDir . '/links.csv';

if (!$skipDownload) {
    echo "--- Step 1: Downloading Tatoeba data ---\n";
    echo "  (This may take a few minutes for initial download)\n\n";

    // Tatoeba weekly exports — per-language sentence files
    $downloads = [
        // Spanish sentences
        [
            'url' => 'https://downloads.tatoeba.org/exports/per_language/spa/spa_sentences_detailed.tsv.bz2',
            'dest' => $cacheDir . '/spa_sentences_detailed.tsv.bz2',
            'extract' => $spaFile,
        ],
        // English sentences
        [
            'url' => 'https://downloads.tatoeba.org/exports/per_language/eng/eng_sentences_detailed.tsv.bz2',
            'dest' => $cacheDir . '/eng_sentences_detailed.tsv.bz2',
            'extract' => $engFile,
        ],
        // Translation links
        [
            'url' => 'https://downloads.tatoeba.org/exports/links.tar.bz2',
            'dest' => $cacheDir . '/links.tar.bz2',
            'extract' => $linksFile,
        ],
    ];

    foreach ($downloads as $dl) {
        if (file_exists($dl['extract'])) {
            echo "  Already cached: " . basename($dl['extract']) . "\n";
            continue;
        }

        echo "  Downloading: " . basename($dl['url']) . "...\n";
        $ch = curl_init($dl['url']);
        $fp = fopen($dl['dest'], 'w');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_USERAGENT => 'YaguaraDictBot/1.0 (babelfree.com)',
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
            echo "  WARNING: HTTP $httpCode for " . basename($dl['url']) . "\n";
            echo "  Trying alternative format...\n";
            // Try simpler format
            continue;
        }

        // Extract bz2
        echo "  Extracting: " . basename($dl['dest']) . "...\n";
        if (str_ends_with($dl['dest'], '.tar.bz2')) {
            exec("cd " . escapeshellarg($cacheDir) . " && tar xjf " . escapeshellarg(basename($dl['dest'])) . " 2>&1", $out, $ret);
            // links.tar.bz2 extracts to links.csv
            if (file_exists($cacheDir . '/links.csv')) {
                echo "  Extracted links.csv\n";
            }
        } else {
            exec("bzcat " . escapeshellarg($dl['dest']) . " > " . escapeshellarg($dl['extract']) . " 2>&1", $out, $ret);
        }

        if (file_exists($dl['extract'])) {
            echo "  OK: " . basename($dl['extract']) . "\n";
        }
    }
    echo "\n";
} else {
    echo "--- Step 1: Download SKIPPED ---\n\n";
}

// ── Step 2: Load Spanish sentences ──────────────────────────────────

echo "--- Step 2: Loading Spanish sentences ---\n";

$spaSentences = []; // id => text

if (file_exists($spaFile)) {
    $fp = fopen($spaFile, 'r');
    $count = 0;
    while (($line = fgets($fp)) !== false) {
        $parts = explode("\t", trim($line));
        if (count($parts) >= 3) {
            $id = (int)$parts[0];
            $text = trim($parts[2]);
            // Filter: prefer shorter sentences (5-80 chars), skip very long or very short
            $len = mb_strlen($text);
            if ($len >= 5 && $len <= 100) {
                $spaSentences[$id] = $text;
                $count++;
            }
        }
        if ($count >= 500000) break; // Cap at 500k sentences for memory
    }
    fclose($fp);
    echo "  Loaded: $count Spanish sentences\n";
} else {
    echo "  WARNING: Spanish sentences file not found.\n";
    echo "  Generating from inline examples instead.\n";
    // Fallback: create minimal example set from frequency list words
    generateFallbackExamples($dataDir, $standalone, $limit, $maxPerWord);
    exit(0);
}

// ── Step 3: Load translation links (spa → eng) ─────────────────────

echo "\n--- Step 3: Loading translation links ---\n";

$spaToEng = []; // spa_sentence_id => eng_sentence_id

if (file_exists($linksFile)) {
    $fp = fopen($linksFile, 'r');
    $count = 0;
    while (($line = fgets($fp)) !== false) {
        $parts = explode("\t", trim($line));
        if (count($parts) >= 2) {
            $id1 = (int)$parts[0];
            $id2 = (int)$parts[1];
            // Only keep links where source is a Spanish sentence
            if (isset($spaSentences[$id1])) {
                $spaToEng[$id1] = $id2;
                $count++;
            }
        }
        if ($count >= 300000) break;
    }
    fclose($fp);
    echo "  Loaded: $count spa→eng links\n";
} else {
    echo "  WARNING: Links file not found.\n";
}

// ── Step 4: Load English sentences for matched links ────────────────

echo "\n--- Step 4: Loading English translations ---\n";

$engSentences = []; // id => text
$neededEngIds = array_flip(array_values($spaToEng));

if (file_exists($engFile) && !empty($neededEngIds)) {
    $fp = fopen($engFile, 'r');
    $count = 0;
    while (($line = fgets($fp)) !== false) {
        $parts = explode("\t", trim($line));
        if (count($parts) >= 3) {
            $id = (int)$parts[0];
            if (isset($neededEngIds[$id])) {
                $engSentences[$id] = trim($parts[2]);
                $count++;
            }
        }
    }
    fclose($fp);
    echo "  Loaded: $count English sentences\n";
} else {
    echo "  English sentences file not found or no links.\n";
}

// ── Step 5: Build word → sentence index ─────────────────────────────

echo "\n--- Step 5: Building word → sentence index ---\n";

// Normalize helper
function normalizeWord(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    $word = preg_replace('/[áà]/u', 'a', $word);
    $word = preg_replace('/[éè]/u', 'e', $word);
    $word = preg_replace('/[íì]/u', 'i', $word);
    $word = preg_replace('/[óò]/u', 'o', $word);
    $word = preg_replace('/[úù]/u', 'u', $word);
    return str_replace('ñ', 'n', $word);
}

// Load target words from frequency list
$freqFile = $dataDir . '/frequency_es.tsv';
$targetWords = [];
if (file_exists($freqFile)) {
    $fp = fopen($freqFile, 'r');
    fgetcsv($fp, 0, "\t"); // header
    while (($row = fgetcsv($fp, 0, "\t")) !== false) {
        if (count($row) >= 2 && (int)$row[0] <= $limit) {
            $word = trim($row[1]);
            $targetWords[normalizeWord($word)] = $word;
        }
    }
    fclose($fp);
}
echo "  Target words: " . count($targetWords) . "\n";

// For each Spanish sentence, tokenize and map words to sentences
$wordSentences = []; // normalized_word => [[spa_text, eng_text, spa_id], ...]

foreach ($spaSentences as $spaId => $spaText) {
    // Tokenize: split on non-letter characters
    $tokens = preg_split('/[^a-záéíóúüñ]+/iu', mb_strtolower($spaText, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);

    foreach ($tokens as $token) {
        $norm = normalizeWord($token);
        if (!isset($targetWords[$norm])) continue;
        if (isset($wordSentences[$norm]) && count($wordSentences[$norm]) >= $maxPerWord * 3) continue; // Keep some extras for quality selection

        $engText = '';
        if (isset($spaToEng[$spaId]) && isset($engSentences[$spaToEng[$spaId]])) {
            $engText = $engSentences[$spaToEng[$spaId]];
        }

        $wordSentences[$norm][] = [
            'spa' => $spaText,
            'eng' => $engText,
            'id' => $spaId,
            'len' => mb_strlen($spaText),
        ];
    }
}

echo "  Words with examples: " . count($wordSentences) . "\n";

// ── Step 6: Select best examples per word ───────────────────────────

echo "\n--- Step 6: Selecting best examples ---\n";

$examples = []; // normalized_word => [{spa, eng, tatoeba_id}, ...]

foreach ($wordSentences as $norm => $sentences) {
    // Prefer sentences with English translations, then shortest
    usort($sentences, function ($a, $b) {
        // Prefer ones with English translation
        $aHasEng = $a['eng'] !== '' ? 0 : 1;
        $bHasEng = $b['eng'] !== '' ? 0 : 1;
        if ($aHasEng !== $bHasEng) return $aHasEng - $bHasEng;
        // Then prefer shorter
        return $a['len'] - $b['len'];
    });

    $examples[$norm] = [];
    foreach (array_slice($sentences, 0, $maxPerWord) as $s) {
        $examples[$norm][] = [
            'spa' => $s['spa'],
            'eng' => $s['eng'],
            'tatoeba_id' => $s['id'],
        ];
    }
}

$totalExamples = array_sum(array_map('count', $examples));
echo "  Selected: $totalExamples examples for " . count($examples) . " words\n";

// ── Step 7: Output ──────────────────────────────────────────────────

if ($standalone) {
    // JSON output
    $outFile = $dataDir . '/tatoeba_examples.json';
    $output = [];
    foreach ($examples as $norm => $exList) {
        $word = $targetWords[$norm] ?? $norm;
        $output[$word] = $exList;
    }
    file_put_contents($outFile, json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "\n  Output: $outFile\n";
    echo "  Done.\n";
} else {
    // Database mode
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();

    echo "\n--- Step 7: Inserting into dict_examples ---\n";

    $findWord = $pdo->prepare(
        "SELECT id FROM dict_words WHERE lang_code = 'es' AND word_normalized = ? LIMIT 1"
    );
    $insertExample = $pdo->prepare(
        "INSERT INTO dict_examples (word_id, sentence, translation, source, source_id)
         VALUES (?, ?, ?, 'tatoeba', ?)
         ON DUPLICATE KEY UPDATE sentence = VALUES(sentence), translation = VALUES(translation)"
    );

    $pdo->beginTransaction();
    $inserted = 0;

    foreach ($examples as $norm => $exList) {
        $findWord->execute([$norm]);
        $row = $findWord->fetch();
        if (!$row) continue;

        $wordId = (int)$row['id'];
        foreach ($exList as $ex) {
            $insertExample->execute([
                $wordId,
                $ex['spa'],
                $ex['eng'] ?: null,
                $ex['tatoeba_id'],
            ]);
            $inserted++;
        }
    }

    $pdo->commit();
    echo "  Inserted: $inserted examples\n";

    // Summary
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM dict_examples WHERE source = 'tatoeba'");
    echo "  Total Tatoeba examples in DB: " . $stmt->fetch()['c'] . "\n";
}

echo "\n=== Tatoeba Seeder Complete ===\n";

// ── Fallback: generate basic examples from templates ─────────────────

function generateFallbackExamples(string $dataDir, bool $standalone, int $limit, int $maxPerWord): void {
    echo "\n--- Fallback: Generating template-based examples ---\n";

    // Basic sentence templates for common word types
    $templates = [
        'noun' => [
            'El/La {word} es importante.',
            'Necesito un/una {word}.',
        ],
        'verb' => [
            'Me gusta {word}.',
            'Es importante {word} cada día.',
        ],
        'adjective' => [
            'Es muy {word}.',
            'La vida es {word}.',
        ],
        'default' => [
            'Uso la palabra "{word}" con frecuencia.',
        ],
    ];

    $freqFile = $dataDir . '/frequency_es.tsv';
    if (!file_exists($freqFile)) {
        echo "  ERROR: No frequency file available.\n";
        return;
    }

    $fp = fopen($freqFile, 'r');
    fgetcsv($fp, 0, "\t");
    $output = [];
    $count = 0;

    while (($row = fgetcsv($fp, 0, "\t")) !== false) {
        if (count($row) < 2 || (int)$row[0] > $limit) break;
        $word = trim($row[1]);

        // Guess type from ending
        $type = 'default';
        if (preg_match('/[aeiíó]r$/', $word)) $type = 'verb';
        elseif (preg_match('/(ción|dad|mente|ismo)$/', $word)) $type = 'noun';
        elseif (preg_match('/(oso|ble|ivo|ico|ado)$/', $word)) $type = 'adjective';

        $examples = [];
        foreach (array_slice($templates[$type], 0, $maxPerWord) as $tpl) {
            $examples[] = [
                'spa' => str_replace('{word}', $word, $tpl),
                'eng' => '',
                'tatoeba_id' => 0,
            ];
        }
        $output[$word] = $examples;
        $count++;
    }
    fclose($fp);

    if ($standalone) {
        $outFile = $dataDir . '/tatoeba_examples.json';
        file_put_contents($outFile, json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "  Fallback output: $outFile ($count words)\n";
    } else {
        echo "  Fallback: $count words with template examples\n";
        echo "  NOTE: Run with Tatoeba data files for real examples.\n";
    }
}
