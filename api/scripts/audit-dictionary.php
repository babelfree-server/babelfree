<?php
/**
 * Dictionary Data Quality Audit & Cleanup
 * Fixes systemic import artifacts from Kaikki/Wiktionary:
 *
 *  1. IPA sanitizer     — flag/clear entries with wrong-language IPA
 *  2. POS stub fixer    — correct nouns that should be adj/verb/adverb
 *  3. Deduplicator      — remove duplicate definitions on same word
 *  4. Circular defs     — remove definitions that just restate the word
 *  5. Garbage defs      — remove "true friend, best friend" contamination
 *  6. Cross-lang defs   — remove definitions in wrong language
 *  7. Empty entries     — remove word entries with no defs/translations/examples
 *  8. Equiv POS fixer   — remove equivalents with POS mismatch (adj↔noun, etc.)
 *
 * Usage:
 *   php audit-dictionary.php --dry-run           # report only, no changes
 *   php audit-dictionary.php --apply             # apply all fixes
 *   php audit-dictionary.php --apply --step=1    # run only step 1
 *   php audit-dictionary.php --apply --step=1,3  # run steps 1 and 3
 *   php audit-dictionary.php --apply --lang=es   # only Spanish entries
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Parse CLI args ──────────────────────────────────────────────────
$dryRun = !in_array('--apply', $argv);
$langFilter = null;
$stepFilter = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--lang=') === 0) {
        $langFilter = substr($arg, 7);
    }
    if (strpos($arg, '--step=') === 0) {
        $stepFilter = array_map('intval', explode(',', substr($arg, 7)));
    }
}

$mode = $dryRun ? 'DRY RUN' : 'APPLYING FIXES';
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          Dictionary Data Quality Audit                      ║\n";
echo "║          Mode: $mode" . str_repeat(' ', 37 - strlen($mode)) . "║\n";
if ($langFilter) echo "║          Language: $langFilter" . str_repeat(' ', 34 - strlen($langFilter)) . "║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$totalFixed = 0;

function shouldRun(int $step): bool {
    global $stepFilter;
    return $stepFilter === null || in_array($step, $stepFilter);
}

function langWhere(string $alias = 'w'): string {
    global $langFilter;
    return $langFilter ? " AND {$alias}.lang_code = " . quote($langFilter) : '';
}

function quote(string $val): string {
    global $pdo;
    return $pdo->quote($val);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 1: IPA SANITIZER
// Detects IPA transcriptions that belong to the wrong language.
// Key markers:
//   ɹ (English postalveolar approximant) — never appears in ES/FR/DE/PT/IT/etc.
//   ɾ (alveolar tap) — Spanish/Portuguese, never English
//   ʁ (uvular fricative) — French/German, never English/Spanish
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(1)) {
    echo "── Step 1: IPA Sanitizer ──────────────────────────────────────\n";

    $flagged = 0;
    $cleared = 0;

    // Single query: non-EN words with English ɹ, plus EN words with French/German ʁ
    $sql = "SELECT id, lang_code, word, pronunciation_ipa FROM dict_words
            WHERE pronunciation_ipa IS NOT NULL
              AND pronunciation_ipa != ''
              AND (
                (lang_code != 'en' AND pronunciation_ipa LIKE '%ɹ%')
                OR (lang_code = 'en' AND pronunciation_ipa LIKE '%ʁ%')
              )
            " . ($langFilter ? "AND lang_code = " . quote($langFilter) : "") . "
            LIMIT 10000";

    $stmt = $pdo->query($sql);
    $updateStmt = $pdo->prepare("UPDATE dict_words SET pronunciation_ipa = NULL WHERE id = ?");

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $flagged++;
        if ($flagged <= 30) {
            $marker = $r['lang_code'] === 'en' ? 'has French/German ʁ' : 'has English ɹ';
            echo "  [{$r['lang_code']}] \"{$r['word']}\" IPA: {$r['pronunciation_ipa']} ← $marker\n";
        }
        if (!$dryRun) {
            $updateStmt->execute([$r['id']]);
            $cleared++;
        }
    }

    echo "  Flagged: $flagged | " . ($dryRun ? "Would clear: $flagged" : "Cleared: $cleared") . "\n\n";
    $totalFixed += ($dryRun ? 0 : $cleared);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 2: POS STUB FIXER
// When Kaikki importer creates target words that don't exist yet,
// it defaults POS to 'noun'. Fix by checking what POS the source
// words that link to these stubs actually are.
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(2)) {
    echo "── Step 2: POS Stub Fixer ─────────────────────────────────────\n";

    // Find words with no definitions that are translation targets,
    // and where ALL sources agree on a POS that isn't 'noun'
    $sql = "SELECT w.id, w.lang_code, w.word, w.part_of_speech,
                   (SELECT GROUP_CONCAT(DISTINCT w2.part_of_speech)
                    FROM dict_translations t
                    JOIN dict_words w2 ON w2.id = t.source_word_id
                    WHERE t.target_word_id = w.id
                      AND w2.part_of_speech IS NOT NULL
                      AND w2.part_of_speech != ''
                   ) AS source_pos
            FROM dict_words w
            WHERE w.part_of_speech = 'noun'
              AND NOT EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w.id)
              AND EXISTS (SELECT 1 FROM dict_translations t WHERE t.target_word_id = w.id)
              " . langWhere('w') . "
            LIMIT 10000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixed = 0;
    $updatePos = $pdo->prepare("UPDATE dict_words SET part_of_speech = ? WHERE id = ?");

    foreach ($rows as $r) {
        $sourceParts = $r['source_pos'] ? explode(',', $r['source_pos']) : [];
        // Only fix if ALL sources agree on a single non-noun POS
        if (count($sourceParts) === 1 && $sourceParts[0] !== 'noun') {
            $correctPos = $sourceParts[0];
            if ($fixed < 20) {
                echo "  [{$r['lang_code']}] \"{$r['word']}\" noun → $correctPos\n";
            }
            if (!$dryRun) {
                $updatePos->execute([$correctPos, $r['id']]);
            }
            $fixed++;
        }
    }

    echo "  " . ($dryRun ? "Would fix" : "Fixed") . ": $fixed POS stubs\n\n";
    $totalFixed += $fixed;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: DUPLICATE DEFINITIONS
// Remove exact duplicate definitions on the same word.
// Keep the one with the lowest id (earliest import).
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(3)) {
    echo "── Step 3: Duplicate Definitions ──────────────────────────────\n";

    // Use self-join to find duplicates — faster than GROUP BY on unindexed text
    $sql = "SELECT d2.id as dup_id, d1.id as keep_id, w.word, w.lang_code,
                   SUBSTRING(d1.definition, 1, 50) as def_preview
            FROM dict_definitions d1
            JOIN dict_definitions d2 ON d2.word_id = d1.word_id
                 AND d2.definition = d1.definition AND d2.id > d1.id
            JOIN dict_words w ON w.id = d1.word_id
            " . ($langFilter ? "WHERE w.lang_code = " . quote($langFilter) : "") . "
            LIMIT 50000";

    $stmt = $pdo->query($sql);
    $deleted = 0;
    $delStmt = $pdo->prepare("DELETE FROM dict_definitions WHERE id = ?");
    $shown = 0;

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($shown < 15) {
            echo "  \"{$r['word']}\" [{$r['lang_code']}] \"{$r['def_preview']}...\"\n";
            $shown++;
        }
        if (!$dryRun) {
            $delStmt->execute([$r['dup_id']]);
        }
        $deleted++;
    }

    echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": $deleted duplicate definitions\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 4: CIRCULAR DEFINITIONS
// Remove definitions where the definition text is just the word
// itself, or trivially circular (e.g., word="real", def="real").
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(4)) {
    echo "── Step 4: Circular Definitions ───────────────────────────────\n";

    $sql = "SELECT d.id, w.word, w.lang_code, d.definition
            FROM dict_definitions d
            JOIN dict_words w ON w.id = d.word_id
            WHERE (LOWER(TRIM(d.definition)) = LOWER(TRIM(w.word))
                   OR LOWER(TRIM(d.definition)) = LOWER(CONCAT(TRIM(w.word), '.'))
                   OR LOWER(TRIM(d.definition)) = LOWER(CONCAT(TRIM(w.word), ' .'))
                  )
            " . ($langFilter ? "AND w.lang_code = " . quote($langFilter) : "") . "
            LIMIT 50000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deleted = 0;
    $delStmt = $pdo->prepare("DELETE FROM dict_definitions WHERE id = ?");

    foreach ($rows as $r) {
        if ($deleted < 20) {
            echo "  [{$r['lang_code']}] \"{$r['word']}\" → def: \"{$r['definition']}\" ← CIRCULAR\n";
        }
        if (!$dryRun) {
            $delStmt->execute([$r['id']]);
        }
        $deleted++;
    }

    echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": $deleted circular definitions\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 5: GARBAGE DEFINITIONS
// Remove "true friend, best friend" and similar Wiktionary import
// artifacts that were mistakenly imported as definitions.
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(5)) {
    echo "── Step 5: Garbage Definition Cleanup ─────────────────────────\n";

    $garbagePatterns = [
        "true friend, best friend",
        "true friend",
        "best friend",
        "false friend",
        "This term needs a translation",
        "please see",
        "# ",                          // Wiktionary markup leaked
        "{{",                          // Template markup leaked
        "]]",                          // Wiki link markup leaked
        "[[",                          // Wiki link markup leaked
    ];

    $deleted = 0;

    foreach ($garbagePatterns as $pattern) {
        $likePattern = '%' . $pattern . '%';
        $stmt = $pdo->prepare(
            "SELECT d.id, w.word, w.lang_code, d.definition
             FROM dict_definitions d
             JOIN dict_words w ON w.id = d.word_id
             WHERE d.definition LIKE ?
             " . ($langFilter ? "AND w.lang_code = " . quote($langFilter) : "") . "
             LIMIT 5000"
        );
        $stmt->execute([$likePattern]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            echo "  Pattern \"$pattern\": " . count($rows) . " matches\n";
            foreach (array_slice($rows, 0, 3) as $r) {
                echo "    [{$r['lang_code']}] \"{$r['word']}\" → \"" . mb_substr($r['definition'], 0, 60) . "\"\n";
            }
        }

        if (!$dryRun) {
            foreach ($rows as $r) {
                $pdo->prepare("DELETE FROM dict_definitions WHERE id = ?")->execute([$r['id']]);
                $deleted++;
            }
        } else {
            $deleted += count($rows);
        }
    }

    // Also clean up equivalents with "true friend" in the word field
    $stmt = $pdo->prepare(
        "SELECT t.id, w1.word as src, w1.lang_code as src_lang, w2.word as tgt
         FROM dict_translations t
         JOIN dict_words w1 ON w1.id = t.source_word_id
         JOIN dict_words w2 ON w2.id = t.target_word_id
         WHERE LOWER(w2.word) LIKE '%true friend%'
            OR LOWER(w2.word) LIKE '%best friend%'
         LIMIT 5000"
    );
    $stmt->execute();
    $badEquivs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($badEquivs)) {
        echo "  Equivalents pointing to \"true friend\" entries: " . count($badEquivs) . "\n";
        foreach (array_slice($badEquivs, 0, 3) as $r) {
            echo "    [{$r['src_lang']}] \"{$r['src']}\" → \"{$r['tgt']}\"\n";
        }
        if (!$dryRun) {
            foreach ($badEquivs as $r) {
                $pdo->prepare("DELETE FROM dict_translations WHERE id = ?")->execute([$r['id']]);
            }
        }
        $deleted += count($badEquivs);
    }

    echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": $deleted garbage entries\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 6: CROSS-LANGUAGE DEFINITIONS
// Spanish words should have definitions in ES or EN (user's interface
// language). Definitions in FR/IT/PT on Spanish entries are Kaikki
// import artifacts from multilingual Wiktionary.
// Same logic applies to all languages: a word's definitions should
// be in its own language or in English (as fallback).
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(6)) {
    echo "── Step 6: Cross-Language Definitions ─────────────────────────\n";

    // For each word, definitions should be in:
    //   1. The word's own lang_code (native definition)
    //   2. English (universal fallback)
    // Anything else is a misimport.
    $sql = "SELECT d.id, w.lang_code as word_lang, d.lang_code as def_lang,
                   w.word, SUBSTRING(d.definition, 1, 60) as def_preview
            FROM dict_definitions d
            JOIN dict_words w ON w.id = d.word_id
            WHERE d.lang_code != w.lang_code
              AND d.lang_code != 'en'
              AND w.lang_code != 'en'
            " . ($langFilter ? "AND w.lang_code = " . quote($langFilter) : "") . "
            LIMIT 50000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deleted = 0;
    $delStmt = $pdo->prepare("DELETE FROM dict_definitions WHERE id = ?");

    // Group by word_lang → def_lang for reporting
    $groups = [];
    foreach ($rows as $r) {
        $key = "[{$r['word_lang']}] word with [{$r['def_lang']}] def";
        if (!isset($groups[$key])) $groups[$key] = 0;
        $groups[$key]++;
    }
    arsort($groups);
    foreach (array_slice($groups, 0, 10, true) as $key => $cnt) {
        echo "  $key: $cnt\n";
    }

    if (!$dryRun) {
        foreach ($rows as $r) {
            $delStmt->execute([$r['id']]);
            $deleted++;
        }
    } else {
        $deleted = count($rows);
    }

    // Special case: English words should only have EN definitions
    // (not ES, FR, etc. — those belong on the other language's entry)
    $sql2 = "SELECT d.id, w.word, d.lang_code as def_lang,
                    SUBSTRING(d.definition, 1, 60) as def_preview
             FROM dict_definitions d
             JOIN dict_words w ON w.id = d.word_id
             WHERE w.lang_code = 'en'
               AND d.lang_code != 'en'
             " . ($langFilter === 'en' ? "" : ($langFilter ? "AND 1=0" : "")) . "
             LIMIT 50000";

    $stmt2 = $pdo->query($sql2);
    $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows2)) {
        echo "  EN words with non-EN definitions: " . count($rows2) . "\n";
        if (!$dryRun) {
            foreach ($rows2 as $r) {
                $delStmt->execute([$r['id']]);
                $deleted++;
            }
        } else {
            $deleted += count($rows2);
        }
    }

    echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": $deleted cross-language definitions\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 7: EMPTY ENTRIES
// Remove word entries that have NO definitions, NO translations
// (as source), and NO examples. These are orphan stubs.
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(7)) {
    echo "── Step 7: Empty Entry Cleanup ────────────────────────────────\n";

    $sql = "SELECT w.id, w.lang_code, w.word, w.part_of_speech
            FROM dict_words w
            WHERE NOT EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w.id)
              AND NOT EXISTS (SELECT 1 FROM dict_translations t WHERE t.source_word_id = w.id)
              AND NOT EXISTS (SELECT 1 FROM dict_examples e WHERE e.word_id = w.id)
              AND NOT EXISTS (SELECT 1 FROM dict_translations t2 WHERE t2.target_word_id = w.id)
            " . langWhere('w') . "
            LIMIT 50000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by language for reporting
    $byLang = [];
    foreach ($rows as $r) {
        $byLang[$r['lang_code']] = ($byLang[$r['lang_code']] ?? 0) + 1;
    }
    arsort($byLang);
    foreach ($byLang as $lc => $cnt) {
        echo "  [$lc] $cnt orphan entries\n";
    }

    $deleted = 0;
    if (!$dryRun) {
        $delStmt = $pdo->prepare("DELETE FROM dict_words WHERE id = ?");
        foreach ($rows as $r) {
            $delStmt->execute([$r['id']]);
            $deleted++;
        }
    } else {
        $deleted = count($rows);
    }

    echo "  " . ($dryRun ? "Would delete" : "Deleted") . ": $deleted empty entries\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 8: EQUIVALENT POS MISMATCH CLEANUP
// Remove translation links where source and target have incompatible
// POS (e.g., adjective → noun). These are typically wrong links
// created by the stub import defaulting to 'noun'.
// Only removes when the mismatch is clearly wrong (adj↔noun, verb↔noun).
// Keeps verb↔noun links since nominalization is valid.
// ═══════════════════════════════════════════════════════════════════

if (shouldRun(8)) {
    echo "── Step 8: Equivalent POS Mismatch ────────────────────────────\n";

    // Only flag clear mismatches: adjective↔noun, adverb↔noun, verb↔adjective
    $sql = "SELECT t.id,
                   w1.lang_code as src_lang, w1.word as src_word, w1.part_of_speech as src_pos,
                   w2.lang_code as tgt_lang, w2.word as tgt_word, w2.part_of_speech as tgt_pos
            FROM dict_translations t
            JOIN dict_words w1 ON w1.id = t.source_word_id
            JOIN dict_words w2 ON w2.id = t.target_word_id
            WHERE (
                (w1.part_of_speech = 'adjective' AND w2.part_of_speech = 'noun')
                OR (w1.part_of_speech = 'noun' AND w2.part_of_speech = 'adjective')
                OR (w1.part_of_speech = 'adverb' AND w2.part_of_speech = 'noun')
                OR (w1.part_of_speech = 'noun' AND w2.part_of_speech = 'adverb')
                OR (w1.part_of_speech = 'verb' AND w2.part_of_speech = 'adjective')
                OR (w1.part_of_speech = 'adjective' AND w2.part_of_speech = 'verb')
              )
              -- Only target stubs with no definitions (likely wrong POS)
              AND NOT EXISTS (SELECT 1 FROM dict_definitions d WHERE d.word_id = w2.id)
            " . ($langFilter ? "AND w1.lang_code = " . quote($langFilter) : "") . "
            LIMIT 50000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group mismatches by type
    $types = [];
    foreach ($rows as $r) {
        $key = "{$r['src_pos']}→{$r['tgt_pos']}";
        $types[$key] = ($types[$key] ?? 0) + 1;
        if (($types[$key] ?? 0) <= 3) {
            echo "  [{$r['src_lang']}] \"{$r['src_word']}\" ({$r['src_pos']}) → [{$r['tgt_lang']}] \"{$r['tgt_word']}\" ({$r['tgt_pos']})\n";
        }
    }
    echo "  Mismatch breakdown:\n";
    foreach ($types as $type => $cnt) {
        echo "    $type: $cnt\n";
    }

    $deleted = 0;
    if (!$dryRun) {
        $delStmt = $pdo->prepare("DELETE FROM dict_translations WHERE id = ?");
        foreach ($rows as $r) {
            $delStmt->execute([$r['id']]);
            $deleted++;
        }
    } else {
        $deleted = count($rows);
    }

    echo "  " . ($dryRun ? "Would remove" : "Removed") . ": $deleted mismatched equivalents\n\n";
    $totalFixed += $deleted;
}

// ═══════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════

echo "══════════════════════════════════════════════════════════════\n";
echo "  Total issues " . ($dryRun ? "found" : "fixed") . ": $totalFixed\n";
if ($dryRun) {
    echo "  Run with --apply to fix these issues.\n";
}
echo "══════════════════════════════════════════════════════════════\n";
