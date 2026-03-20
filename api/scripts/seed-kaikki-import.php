<?php
/**
 * Kaikki.org Bulk Dictionary Importer
 *
 * Imports pre-parsed Wiktionary data from kaikki.org JSONL files.
 * TWO sources per language:
 *   1. English Wiktionary per-language file → words, English glosses, IPA, etymology, synonyms, translations
 *   2. Native Wiktionary extract (if available) → native-language definitions
 *
 * Usage:
 *   php seed-kaikki-import.php --lang=es                      # Single language
 *   php seed-kaikki-import.php --lang=es --source=en          # English Wiktionary only
 *   php seed-kaikki-import.php --lang=es --source=native      # Native Wiktionary only
 *   php seed-kaikki-import.php --lang=es --skip-download      # Use cached file
 *   php seed-kaikki-import.php --lang=es --translations-only  # Only extract translations
 *   php seed-kaikki-import.php --all                          # All 12 languages
 *   php seed-kaikki-import.php --lang=es --dry-run            # Preview only
 */

if (function_exists('ob_implicit_flush')) ob_implicit_flush(true);
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

// ── CLI parsing ────────────────────────────────────────────────────────

$opts = getopt('', ['lang:', 'source:', 'all', 'dry-run', 'skip-download', 'translations-only', 'batch-size:']);
$dryRun          = isset($opts['dry-run']);
$skipDownload    = isset($opts['skip-download']);
$translationsOnly = isset($opts['translations-only']);
$sourceFilter    = $opts['source'] ?? 'all';  // 'en', 'native', 'all'
$batchSize       = (int)($opts['batch-size'] ?? 1000);

// Language config: code → [English name (kaikki.org URL), Wiktionary native code (if extract exists)]
// 105 languages — all platform-supported languages
$LANGUAGES = [
    // ── Original 12 ──
    'es' => ['Spanish',       'es'],
    'en' => ['English',       'en'],
    'fr' => ['French',        'fr'],
    'de' => ['German',        'de'],
    'pt' => ['Portuguese',    'pt'],
    'it' => ['Italian',       'it'],
    'nl' => ['Dutch',         'nl'],
    'ru' => ['Russian',       'ru'],
    'zh' => ['Chinese',       'zh'],
    'ja' => ['Japanese',      'ja'],
    'ko' => ['Korean',        'ko'],
    'ar' => ['Arabic',        null],
    // ── Tier 1: Large European ──
    'pl' => ['Polish',        'pl'],
    'fi' => ['Finnish',       'fi'],
    'sv' => ['Swedish',       'sv'],
    'ca' => ['Catalan',       'ca'],
    'hu' => ['Hungarian',     'hu'],
    'cs' => ['Czech',         'cs'],
    'el' => ['Greek',         'el'],
    'tr' => ['Turkish',       'tr'],
    'ro' => ['Romanian',      'ro'],
    'da' => ['Danish',        'da'],
    'no' => ['Norwegian',       'no'],
    'bg' => ['Bulgarian',     'bg'],
    'sr' => ['Serbian',       'sr'],
    'hr' => ['Croatian',      'hr'],
    'sk' => ['Slovak',        'sk'],
    'lt' => ['Lithuanian',    'lt'],
    'lv' => ['Latvian',       'lv'],
    'sl' => ['Slovene',       'sl'],
    'et' => ['Estonian',      'et'],
    'sq' => ['Albanian',      'sq'],
    'is' => ['Icelandic',     'is'],
    'gl' => ['Galician',      'gl'],
    'eu' => ['Basque',        'eu'],
    'la' => ['Latin',         'la'],
    'eo' => ['Esperanto',     'eo'],
    'uk' => ['Ukrainian',     'uk'],
    'bs' => ['Bosnian',       'bs'],
    'mk' => ['Macedonian',    'mk'],
    'mt' => ['Maltese',       'mt'],
    'cy' => ['Welsh',         'cy'],
    'ga' => ['Irish',         'ga'],
    'gd' => ['Scottish Gaelic', 'gd'],
    'lb' => ['Luxembourgish', 'lb'],
    'af' => ['Afrikaans',     'af'],
    // ── Tier 1: Large Asian/Middle East ──
    'hi' => ['Hindi',         'hi'],
    'th' => ['Thai',          'th'],
    'he' => ['Hebrew',        'he'],
    'vi' => ['Vietnamese',    'vi'],
    'id' => ['Indonesian',    'id'],
    'fa' => ['Persian',       'fa'],
    'ms' => ['Malay',         'ms'],
    'bn' => ['Bengali',       'bn'],
    'ur' => ['Urdu',          'ur'],
    'ta' => ['Tamil',         'ta'],
    'te' => ['Telugu',        'te'],
    'ml' => ['Malayalam',     'ml'],
    'kn' => ['Kannada',       'kn'],
    'gu' => ['Gujarati',      'gu'],
    'mr' => ['Marathi',       'mr'],
    'pa' => ['Punjabi',       'pa'],
    'ne' => ['Nepali',        'ne'],
    'si' => ['Sinhala',       'si'],
    'km' => ['Khmer',         'km'],
    'lo' => ['Lao',           'lo'],
    'my' => ['Burmese',       'my'],
    'ka' => ['Georgian',      'ka'],
    'hy' => ['Armenian',      'hy'],
    'mn' => ['Mongolian',     'mn'],
    // ── Tier 1: Large African ──
    'sw' => ['Swahili',       'sw'],
    'ha' => ['Hausa',         'ha'],
    'yo' => ['Yoruba',        'yo'],
    'ig' => ['Igbo',          'ig'],
    'am' => ['Amharic',       'am'],
    'so' => ['Somali',        'so'],
    // ── Tier 2: Central Asian / Turkic ──
    'az' => ['Azerbaijani',   'az'],
    'kk' => ['Kazakh',        'kk'],
    'ky' => ['Kyrgyz',        'ky'],
    'uz' => ['Uzbek',         'uz'],
    'tk' => ['Turkmen',       'tk'],
    'tg' => ['Tajik',         'tg'],
    'ug' => ['Uyghur',        'ug'],
    'ku' => ['Kurdish',       'ku'],
    'ps' => ['Pashto',        'ps'],
    'sd' => ['Sindhi',        'sd'],
    'or' => ['Odia',          'or'],
    // ── Tier 2: African / Other ──
    'rw' => ['Kinyarwanda',   'rw'],
    'sn' => ['Shona',         'sn'],
    'st' => ['Sesotho',       null],
    'tn' => ['Tswana',        null],
    'xh' => ['Xhosa',         null],
    'zu' => ['Zulu',          null],
    'wo' => ['Wolof',         null],
    'ti' => ['Tigrinya',      null],
    'ln' => ['Lingala',       'ln'],
    'mg' => ['Malagasy',      'mg'],
    'tl' => ['Tagalog',       'tl'],
    'be' => ['Belarusian',    'be'],
    // ── Tier 3: Small / Rare ──
    'bo' => ['Tibetan',       null],
    'fj' => ['Fijian',        null],
    'haw' => ['Hawaiian',     null],
    'mi' => ['Maori',         'mi'],
    'sm' => ['Samoan',        null],
    'zh-tw' => ['Chinese',    'zh'],  // Same source as zh, filtered by script
];

$targetLangs = [];
if (isset($opts['all']) || in_array('--all', $argv ?? [])) {
    $targetLangs = array_keys($LANGUAGES);
} elseif (isset($opts['lang'])) {
    $targetLangs = is_array($opts['lang']) ? $opts['lang'] : [$opts['lang']];
} else {
    echo "Usage: php seed-kaikki-import.php --lang=XX | --all [--source=en|native] [--dry-run] [--skip-download]\n";
    exit(1);
}

$DOWNLOAD_DIR = __DIR__ . '/data/kaikki';
if (!is_dir($DOWNLOAD_DIR)) mkdir($DOWNLOAD_DIR, 0755, true);

echo "=== Kaikki.org Bulk Dictionary Importer ===\n";
echo "  Languages: " . implode(', ', $targetLangs) . "\n";
echo "  Source: $sourceFilter\n";
echo "  Dry run: " . ($dryRun ? 'YES' : 'no') . "\n";
echo "  Batch size: $batchSize\n\n";

// ── Helpers ────────────────────────────────────────────────────────────

function downloadFile(string $url, string $dest): bool {
    echo "  Downloading: $url\n";
    echo "  → $dest\n";

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 600,
            'header'  => "User-Agent: BabelFreeDictionaryBot/1.0 (https://babelfree.com)\r\n",
        ]
    ]);

    // Use curl for large files with progress
    $cmd = sprintf(
        'curl -L -o %s --progress-bar -H "User-Agent: BabelFreeDictionaryBot/1.0" %s 2>&1',
        escapeshellarg($dest),
        escapeshellarg($url)
    );
    passthru($cmd, $ret);
    return $ret === 0 && file_exists($dest) && filesize($dest) > 1000;
}

function normalizeWord(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    return $word;
}

function mapPos(string $kaikkiPos): string {
    $map = [
        'noun' => 'noun', 'verb' => 'verb', 'adj' => 'adjective', 'adv' => 'adverb',
        'pron' => 'pronoun', 'prep' => 'preposition', 'conj' => 'conjunction',
        'intj' => 'interjection', 'det' => 'article', 'article' => 'article',
        'phrase' => 'phrase', 'name' => 'noun', 'num' => 'adjective',
        'particle' => 'adverb', 'prefix' => 'phrase', 'suffix' => 'phrase',
        'abbrev' => 'phrase', 'proverb' => 'phrase', 'idiom' => 'phrase',
    ];
    return $map[$kaikkiPos] ?? 'noun';
}

function extractGender(array $entry): ?string {
    $tags = [];
    foreach ($entry['head_templates'] ?? [] as $ht) {
        $expansion = $ht['expansion'] ?? '';
        if (preg_match('/\b(masculine|feminine|neuter)\b/i', $expansion, $m)) {
            $g = strtolower($m[1]);
            if ($g === 'masculine') return 'm';
            if ($g === 'feminine') return 'f';
            if ($g === 'neuter') return 'n';
        }
        // Check args for gender
        foreach ($ht['args'] ?? [] as $k => $v) {
            if ($k === 'g' || $k === 'g1' || $k === 'gender') {
                $v = strtolower($v);
                if (str_starts_with($v, 'm')) return 'm';
                if (str_starts_with($v, 'f')) return 'f';
                if (str_starts_with($v, 'n')) return 'n';
            }
        }
    }
    // Check tags on first sense
    foreach ($entry['senses'][0]['tags'] ?? [] as $tag) {
        $tag = strtolower($tag);
        if ($tag === 'masculine') return 'm';
        if ($tag === 'feminine') return 'f';
        if ($tag === 'neuter') return 'n';
    }
    return null;
}

function isValidEntry(array $entry): bool {
    $word = $entry['word'] ?? '';
    if (mb_strlen($word) < 1 || mb_strlen($word) > 100) return false;
    if (strpos($word, ':') !== false) return false;  // Reconstruction: etc.
    if (!preg_match('/\p{L}/u', $word)) return false;
    // Skip entries marked as forms only (e.g. "plural of X" with no real definition)
    $senses = $entry['senses'] ?? [];
    if (empty($senses)) return false;
    return true;
}

// ── Cache all supported language codes from DB (not just $LANGUAGES) ───
$SUPPORTED_LANG_CODES = [];
$langRows = $pdo->query('SELECT code FROM dict_languages')->fetchAll(PDO::FETCH_COLUMN);
foreach ($langRows as $code) {
    $SUPPORTED_LANG_CODES[$code] = true;
}
echo "  Supported languages (from DB): " . count($SUPPORTED_LANG_CODES) . "\n\n";

// ── Prepared statements ────────────────────────────────────────────────

$stmtCheckWord = $pdo->prepare(
    'SELECT id FROM dict_words WHERE lang_code = ? AND word = ? AND part_of_speech = ? LIMIT 1'
);

$stmtInsertWord = $pdo->prepare(
    'INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech, gender, pronunciation_ipa, cefr_level, frequency_rank, etymology)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmtUpdateWord = $pdo->prepare(
    'UPDATE dict_words SET pronunciation_ipa = COALESCE(pronunciation_ipa, ?), etymology = COALESCE(etymology, ?), gender = COALESCE(gender, ?) WHERE id = ?'
);

$stmtCheckDef = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_definitions WHERE word_id = ? AND definition = ?'
);

$stmtInsertDef = $pdo->prepare(
    'INSERT INTO dict_definitions (word_id, lang_code, definition, usage_note, source_id, sort_order)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$stmtCheckRelated = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_related_words WHERE word_id = ? AND related_word_id = ? AND relation_type = ?'
);

$stmtInsertRelated = $pdo->prepare(
    'INSERT INTO dict_related_words (word_id, related_word_id, relation_type) VALUES (?, ?, ?)'
);

$stmtFindWord = $pdo->prepare(
    'SELECT id FROM dict_words WHERE lang_code = ? AND word = ? LIMIT 1'
);

$stmtCheckTranslation = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_translations WHERE source_word_id = ? AND target_word_id = ?'
);

$stmtInsertTranslation = $pdo->prepare(
    'INSERT INTO dict_translations (source_word_id, target_word_id, context) VALUES (?, ?, ?)'
);

$stmtInsertExample = $pdo->prepare(
    'INSERT INTO dict_examples (word_id, sentence, translation, source, source_id) VALUES (?, ?, ?, ?, ?)'
);

$stmtCheckExample = $pdo->prepare(
    'SELECT COUNT(*) FROM dict_examples WHERE word_id = ? AND source_id = ?'
);

// ── Process a single JSONL entry ───────────────────────────────────────

function processEntry(array $entry, string $langCode, string $defLangCode, array &$stats, bool $dryRun): void {
    global $pdo, $stmtCheckWord, $stmtInsertWord, $stmtUpdateWord,
           $stmtCheckDef, $stmtInsertDef, $stmtCheckRelated, $stmtInsertRelated,
           $stmtFindWord, $stmtCheckTranslation, $stmtInsertTranslation,
           $stmtInsertExample, $stmtCheckExample;

    if (!isValidEntry($entry)) {
        $stats['skipped_invalid']++;
        return;
    }

    $word = $entry['word'];
    $pos = mapPos($entry['pos'] ?? 'noun');
    $normalized = normalizeWord($word);
    $gender = extractGender($entry);
    $ipa = null;
    foreach ($entry['sounds'] ?? [] as $s) {
        if (!empty($s['ipa'])) { $ipa = $s['ipa']; break; }
    }
    $etymology = !empty($entry['etymology_text']) ? mb_substr($entry['etymology_text'], 0, 2000) : null;

    if ($dryRun) {
        $stats['words_new']++;
        return;
    }

    // Upsert word
    $stmtCheckWord->execute([$langCode, $word, $pos]);
    $existing = $stmtCheckWord->fetchColumn();

    if ($existing) {
        $wordId = (int)$existing;
        // Update missing fields
        $stmtUpdateWord->execute([$ipa, $etymology, $gender, $wordId]);
        $stats['words_updated']++;
    } else {
        try {
            $stmtInsertWord->execute([
                $langCode, $word, $normalized, $pos, $gender, $ipa, null, null, $etymology
            ]);
            $wordId = (int)$pdo->lastInsertId();
            $stats['words_new']++;
        } catch (\PDOException $e) {
            // Duplicate — race condition, just look it up
            $stmtCheckWord->execute([$langCode, $word, $pos]);
            $wordId = (int)$stmtCheckWord->fetchColumn();
            if (!$wordId) { $stats['errors']++; return; }
            $stats['words_updated']++;
        }
    }

    // Process senses → definitions
    foreach ($entry['senses'] ?? [] as $si => $sense) {
        foreach ($sense['glosses'] ?? [] as $gi => $gloss) {
            if (mb_strlen($gloss) < 2 || mb_strlen($gloss) > 2000) continue;
            // Skip form-of definitions for the main definition set
            if ($gi === 0 && preg_match('/^(plural|feminine|masculine|diminutive|past participle|inflection|misspelling|alternative|obsolete) (of|form)\b/i', $gloss)) {
                continue;
            }

            $stmtCheckDef->execute([$wordId, $gloss]);
            if ((int)$stmtCheckDef->fetchColumn() > 0) continue;

            $usageNote = null;
            $tags = $sense['tags'] ?? [];
            if (!empty($tags)) {
                $usageNote = implode(', ', array_slice($tags, 0, 10));
            }

            try {
                $stmtInsertDef->execute([
                    $wordId, $defLangCode, $gloss, $usageNote,
                    'kaikki:' . ($sense['id'] ?? $word . '-' . $si . '-' . $gi),
                    $si
                ]);
                $stats['defs_new']++;
            } catch (\PDOException $e) {
                // Skip duplicates
            }
        }

        // Examples
        foreach ($sense['examples'] ?? [] as $ex) {
            $text = $ex['text'] ?? '';
            if (mb_strlen($text) < 3) continue;
            $exId = 'kaikki:' . md5($langCode . $word . $text);

            $stmtCheckExample->execute([$wordId, $exId]);
            if ((int)$stmtCheckExample->fetchColumn() > 0) continue;

            try {
                $stmtInsertExample->execute([
                    $wordId, $text, $ex['english'] ?? null, 'kaikki.org', $exId
                ]);
                $stats['examples_new']++;
            } catch (\PDOException $e) { /* skip */ }
        }

        // Synonyms & antonyms
        foreach (['synonyms' => 'synonym', 'antonyms' => 'antonym', 'derived' => 'derived'] as $field => $relType) {
            foreach ($sense[$field] ?? [] as $rel) {
                $relWord = $rel['word'] ?? '';
                if (empty($relWord) || mb_strlen($relWord) > 100) continue;

                $stmtFindWord->execute([$langCode, $relWord]);
                $relId = $stmtFindWord->fetchColumn();

                if (!$relId) {
                    // Create stub entry for the related word
                    try {
                        $stmtInsertWord->execute([
                            $langCode, $relWord, normalizeWord($relWord), 'noun', null, null, null, null, null
                        ]);
                        $relId = (int)$pdo->lastInsertId();
                    } catch (\PDOException $e) {
                        $stmtFindWord->execute([$langCode, $relWord]);
                        $relId = $stmtFindWord->fetchColumn();
                    }
                }

                if ($relId && $relId != $wordId) {
                    $stmtCheckRelated->execute([$wordId, $relId, $relType]);
                    if ((int)$stmtCheckRelated->fetchColumn() === 0) {
                        try {
                            $stmtInsertRelated->execute([$wordId, $relId, $relType]);
                            $stats['related_new']++;
                        } catch (\PDOException $e) { /* skip */ }
                    }
                }
            }
        }

        // Translations (only present in English Wiktionary entries)
        foreach ($sense['translations'] ?? [] as $tr) {
            $trLang = $tr['lang_code'] ?? $tr['code'] ?? '';
            $trWord = $tr['word'] ?? '';
            if (empty($trLang) || empty($trWord) || mb_strlen($trWord) > 100) continue;
            // Only import translations for platform-supported languages (all 105)
            if (!isset($GLOBALS['SUPPORTED_LANG_CODES'][$trLang])) continue;

            // Find or create the target word
            $stmtFindWord->execute([$trLang, $trWord]);
            $targetId = $stmtFindWord->fetchColumn();

            if (!$targetId) {
                try {
                    $stmtInsertWord->execute([
                        $trLang, $trWord, normalizeWord($trWord), 'noun', null, null, null, null, null
                    ]);
                    $targetId = (int)$pdo->lastInsertId();
                } catch (\PDOException $e) {
                    $stmtFindWord->execute([$trLang, $trWord]);
                    $targetId = $stmtFindWord->fetchColumn();
                }
            }

            if ($targetId && $targetId != $wordId) {
                $stmtCheckTranslation->execute([$wordId, $targetId]);
                if ((int)$stmtCheckTranslation->fetchColumn() === 0) {
                    $context = $tr['sense'] ?? null;
                    try {
                        $stmtInsertTranslation->execute([$wordId, $targetId, $context]);
                        $stats['translations_new']++;
                    } catch (\PDOException $e) { /* skip */ }
                }
            }
        }
    }
}

// ── Process a JSONL file ───────────────────────────────────────────────

function processFile(string $filePath, string $langCode, string $defLangCode, bool $dryRun, bool $translationsOnly): array {
    global $batchSize, $pdo;

    $stats = [
        'words_new' => 0, 'words_updated' => 0, 'defs_new' => 0,
        'related_new' => 0, 'translations_new' => 0, 'examples_new' => 0,
        'skipped_invalid' => 0, 'errors' => 0, 'total_lines' => 0,
    ];

    $isGz = str_ends_with($filePath, '.gz');
    $handle = $isGz ? gzopen($filePath, 'r') : fopen($filePath, 'r');
    if (!$handle) {
        echo "  ERROR: Cannot open $filePath\n";
        return $stats;
    }

    $lineNum = 0;
    $startTime = time();

    // Use transactions in batches for speed
    $batchCount = 0;
    $pdo->beginTransaction();

    while (($line = $isGz ? gzgets($handle) : fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;

        $entry = json_decode($line, true);
        if (!$entry || !isset($entry['word'])) continue;

        // For native extracts: filter to entries matching our target language
        $entryLang = $entry['lang_code'] ?? '';
        if ($entryLang && $entryLang !== $langCode) continue;

        $stats['total_lines']++;

        if (!$translationsOnly) {
            processEntry($entry, $langCode, $defLangCode, $stats, $dryRun);
        } else {
            // Only process translations from English entries
            foreach ($entry['senses'] ?? [] as $sense) {
                foreach ($sense['translations'] ?? [] as $tr) {
                    // ... handled inside processEntry
                }
            }
            processEntry($entry, $langCode, $defLangCode, $stats, $dryRun);
        }

        $batchCount++;
        if ($batchCount >= $batchSize) {
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            $pdo->beginTransaction();
            $batchCount = 0;
        }

        // Progress every 5000 lines
        if ($lineNum % 5000 === 0) {
            $elapsed = time() - $startTime;
            $rate = $elapsed > 0 ? round($lineNum / $elapsed) : 0;
            echo sprintf(
                "  [%s] %s lines — +%s words, +%s defs, +%s related, +%s trans, +%s ex — %d/s\n",
                $langCode, number_format($lineNum),
                number_format($stats['words_new']),
                number_format($stats['defs_new']),
                number_format($stats['related_new']),
                number_format($stats['translations_new']),
                number_format($stats['examples_new']),
                $rate
            );
            fflush(STDOUT);
        }
    }

    // Final commit
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    if ($isGz) gzclose($handle); else fclose($handle);

    return $stats;
}

// ── Main loop ──────────────────────────────────────────────────────────

$grandStats = ['words_new' => 0, 'defs_new' => 0, 'related_new' => 0, 'translations_new' => 0, 'examples_new' => 0];

foreach ($targetLangs as $lang) {
    if (!isset($LANGUAGES[$lang])) {
        echo "Unknown language: $lang\n";
        continue;
    }

    [$engName, $nativeCode] = $LANGUAGES[$lang];
    echo "=== Processing: $lang ($engName) ===\n";

    // Source 1: English Wiktionary per-language file
    if ($sourceFilter === 'all' || $sourceFilter === 'en') {
        $enFile = "$DOWNLOAD_DIR/kaikki-dict-$engName.jsonl";

        if (!$skipDownload || !file_exists($enFile)) {
            $url = "https://kaikki.org/dictionary/$engName/kaikki.org-dictionary-$engName.jsonl";
            if (!downloadFile($url, $enFile)) {
                echo "  WARN: Failed to download English Wiktionary file for $lang\n";
            }
        }

        if (file_exists($enFile)) {
            echo "  Processing English Wiktionary entries...\n";
            $stats = processFile($enFile, $lang, 'en', $dryRun, $translationsOnly);
            echo sprintf(
                "  EN source done: %s lines, +%s words, +%s defs, +%s related, +%s trans, +%s examples\n",
                number_format($stats['total_lines']),
                number_format($stats['words_new']),
                number_format($stats['defs_new']),
                number_format($stats['related_new']),
                number_format($stats['translations_new']),
                number_format($stats['examples_new'])
            );

            // Clean up to save disk
            if (!$skipDownload) {
                unlink($enFile);
                echo "  Cleaned up: $enFile\n";
            }

            foreach (['words_new', 'defs_new', 'related_new', 'translations_new', 'examples_new'] as $k) {
                $grandStats[$k] += $stats[$k];
            }
        }
    }

    // Source 2: Native Wiktionary extract (has definitions in the native language)
    if (($sourceFilter === 'all' || $sourceFilter === 'native') && $nativeCode) {
        $nativeFile = "$DOWNLOAD_DIR/$nativeCode-extract.jsonl.gz";

        if (!$skipDownload || !file_exists($nativeFile)) {
            $url = "https://kaikki.org/dictionary/downloads/$nativeCode/$nativeCode-extract.jsonl.gz";
            if (!downloadFile($url, $nativeFile)) {
                echo "  WARN: Failed to download native extract for $lang\n";
            }
        }

        if (file_exists($nativeFile)) {
            echo "  Processing native Wiktionary entries...\n";
            // Native extracts contain entries for ALL languages defined in that Wiktionary
            // We need to filter to only entries with the right lang_code
            $stats = processFile($nativeFile, $lang, $lang, $dryRun, false);
            echo sprintf(
                "  Native source done: %s lines, +%s words, +%s defs, +%s related, +%s examples\n",
                number_format($stats['total_lines']),
                number_format($stats['words_new']),
                number_format($stats['defs_new']),
                number_format($stats['related_new']),
                number_format($stats['examples_new'])
            );

            if (!$skipDownload) {
                unlink($nativeFile);
                echo "  Cleaned up: $nativeFile\n";
            }

            foreach (['words_new', 'defs_new', 'related_new', 'examples_new'] as $k) {
                $grandStats[$k] += $stats[$k];
            }
        }
    }

    echo "\n";
}

echo "=== GRAND TOTAL ===\n";
echo "  Words: +" . number_format($grandStats['words_new']) . "\n";
echo "  Definitions: +" . number_format($grandStats['defs_new']) . "\n";
echo "  Related: +" . number_format($grandStats['related_new']) . "\n";
echo "  Translations: +" . number_format($grandStats['translations_new']) . "\n";
echo "  Examples: +" . number_format($grandStats['examples_new']) . "\n";
echo "=== COMPLETE ===\n";
