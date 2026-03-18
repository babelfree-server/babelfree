<?php
/**
 * Wiktionary Word Seeder — Imports word entries from en.wiktionary.org language categories
 *
 * Uses the "Category:{Language}_lemmas" category on English Wiktionary to get
 * real dictionary words for each language. Much more accurate than allpages.
 *
 * Available lemmas on en.wiktionary.org:
 *   ES: 114K, EN: 855K, FR: 97K, DE: 102K, PT: 73K, IT: 128K,
 *   NL: 64K, RU: 60K, ZH: 302K, JA: 122K, KO: 54K, AR: 22K
 *
 * Usage:
 *   php seed-words-wiktionary.php --lang=de                  # Single language
 *   php seed-words-wiktionary.php --lang=ja --target=26000   # Custom target
 *   php seed-words-wiktionary.php --all                      # All 12 languages
 *   php seed-words-wiktionary.php --all --phase=b            # Phase B only
 *   php seed-words-wiktionary.php --lang=ko --dry-run        # Preview
 */

if (function_exists('ob_implicit_flush')) ob_implicit_flush(true);

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// ── CLI options ──────────────────────────────────────────────────────

$targetLangs = [];
$dryRun      = in_array('--dry-run', $argv ?? []);
$allLangs    = in_array('--all', $argv ?? []);
$targetCount = 26000;

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--lang=') === 0) $targetLangs[] = substr($arg, 7);
    if (strpos($arg, '--target=') === 0) $targetCount = (int) substr($arg, 9);
}

$phaseALangs = ['es', 'en', 'fr', 'de', 'pt', 'it'];
$phaseBLangs = ['nl', 'ru', 'zh', 'ja', 'ko', 'ar'];
$phaseB = in_array('--phase=b', $argv ?? []) || in_array('--phase=B', $argv ?? []);

if ($allLangs && $phaseB) {
    $targetLangs = $phaseBLangs;
} elseif ($allLangs) {
    $targetLangs = array_merge($phaseALangs, $phaseBLangs);
} elseif (empty($targetLangs)) {
    echo "Usage: php seed-words-wiktionary.php --all [--phase=b] | --lang=XX [--target=N] [--dry-run]\n";
    exit(1);
}

// Language name for Wiktionary categories
$langNames = [
    'es' => 'Spanish', 'en' => 'English', 'fr' => 'French', 'de' => 'German',
    'pt' => 'Portuguese', 'it' => 'Italian', 'nl' => 'Dutch', 'ru' => 'Russian',
    'zh' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean', 'ar' => 'Arabic',
];

echo "=== Wiktionary Word Seeder (Category-based) ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Target: ~" . number_format($targetCount) . " words per language\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n\n";

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

function isValidWord(string $title): bool {
    // Skip multi-word phrases (allow hyphens and apostrophes, but not spaces for most)
    // Allow spaces only for CJK (some Chinese words have spaces in Wiktionary titles)
    if (preg_match('/\s/', $title) && !preg_match('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $title)) {
        return false;
    }
    // Skip very long entries or entries with special chars
    if (mb_strlen($title) > 50 || mb_strlen($title) < 1) return false;
    if (preg_match('/[#\[\]{}|<>@=+\/\\\\]/', $title)) return false;
    // Skip Reconstruction:, Appendix:, etc.
    if (strpos($title, ':') !== false) return false;
    // Must contain at least one letter-like character
    if (!preg_match('/\p{L}/u', $title)) return false;
    return true;
}

/**
 * Fetch category members from en.wiktionary.org
 */
function fetchCategoryMembers(string $category, ?string $continueFrom = null, int $limit = 500): array {
    $params = [
        'action'    => 'query',
        'list'      => 'categorymembers',
        'cmtitle'   => $category,
        'cmnamespace' => 0,
        'cmlimit'   => $limit,
        'cmtype'    => 'page',
        'format'    => 'json',
    ];
    if ($continueFrom) {
        $params['cmcontinue'] = $continueFrom;
    }

    $url = 'https://en.wiktionary.org/w/api.php?' . http_build_query($params);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header'  => "User-Agent: BabelFreeDictionaryBot/1.0 (https://babelfree.com; info@babelfree.com)\r\n",
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if (!$json) return [[], null];

    $data = json_decode($json, true);
    if (!$data) return [[], null];

    $titles = [];
    foreach ($data['query']['categorymembers'] ?? [] as $member) {
        $titles[] = $member['title'];
    }

    $continue = $data['continue']['cmcontinue'] ?? null;

    return [$titles, $continue];
}

// ── Prepared statements ──────────────────────────────────────────────

$checkWord = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_words WHERE lang_code = ? AND word_normalized = ?'
);

$insertWord = $pdo->prepare(
    'INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, cefr_level, frequency_rank)
     VALUES (?, ?, ?, ?, ?, ?)'
);

// ── Main loop ────────────────────────────────────────────────────────

$grandTotal = 0;

foreach ($targetLangs as $lang) {
    $langName = $langNames[$lang] ?? ucfirst($lang);
    $category = "Category:{$langName} lemmas";

    // Get current word count
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM dict_words WHERE lang_code = ?');
    $stmt->execute([$lang]);
    $currentCount = (int) $stmt->fetchColumn();

    $needed = $targetCount - $currentCount;
    if ($needed <= 0) {
        echo "[$lang] Already at " . number_format($currentCount) . " words (target: " . number_format($targetCount) . "). Skipping.\n\n";
        continue;
    }

    echo "[$lang] Current: " . number_format($currentCount) . ". Need: +" . number_format($needed) . " → " . number_format($targetCount) . "\n";
    echo "  Source: en.wiktionary.org → $category\n";
    fflush(STDOUT);

    $inserted = 0;
    $skippedExists = 0;
    $skippedInvalid = 0;
    $apiCalls = 0;
    $continueToken = null;
    $startTime = time();

    while ($inserted < $needed) {
        [$titles, $continueToken] = fetchCategoryMembers($category, $continueToken);
        $apiCalls++;

        if (empty($titles)) {
            if (!$continueToken) {
                echo "  Category exhausted after $apiCalls API calls.\n";
                fflush(STDOUT);
                break;
            }
            usleep(500000);
            continue;
        }

        foreach ($titles as $title) {
            if ($inserted >= $needed) break;

            if (!isValidWord($title)) {
                $skippedInvalid++;
                continue;
            }

            $word = $title;
            $normalized = normalize($word);

            // Check if already exists
            $checkWord->execute([$lang, $normalized]);
            if ((int)$checkWord->fetchColumn() > 0) {
                $skippedExists++;
                continue;
            }

            if ($dryRun) {
                $inserted++;
                if ($inserted <= 10 || $inserted % 5000 === 0) {
                    echo "  [DRY] $word\n";
                }
                continue;
            }

            try {
                $insertWord->execute([
                    $lang,
                    $word,
                    $normalized,
                    'noun',  // POS — default, will be enriched later
                    null,    // CEFR — will be enriched later
                    null,    // frequency_rank
                ]);
                $inserted++;
            } catch (Exception $e) {
                $skippedExists++;
            }

            // Progress every 2000 words
            if ($inserted > 0 && $inserted % 2000 === 0) {
                $elapsed = time() - $startTime;
                $rate = $elapsed > 0 ? round($inserted / $elapsed, 0) : 0;
                echo "  [$lang] +$inserted/$needed — exists: $skippedExists, invalid: $skippedInvalid — $rate words/s (API: $apiCalls)\n";
                fflush(STDOUT);
            }
        }

        if (!$continueToken) {
            echo "  Category exhausted after $apiCalls API calls.\n";
            fflush(STDOUT);
            break;
        }

        // Rate limit: 0.2s between API calls
        usleep(200000);
    }

    $elapsed = time() - $startTime;
    echo "[$lang] Done: +$inserted words, $skippedExists existed, $skippedInvalid invalid ({$elapsed}s, $apiCalls API calls)\n\n";
    fflush(STDOUT);
    $grandTotal += $inserted;
}

echo "=== COMPLETE: $grandTotal new word entries created ===\n";
