<?php
/**
 * generate-ipa.php — Rule-based Spanish IPA pronunciation generator
 *
 * Spanish orthography is nearly 100% predictable for pronunciation.
 * This script generates IPA transcriptions for all Spanish words in dict_words,
 * or can be run standalone against frequency_es.tsv to produce a TSV output.
 *
 * Usage:
 *   php generate-ipa.php                    → Update dict_words.pronunciation_ipa in DB
 *   php generate-ipa.php --standalone       → Read frequency_es.tsv, output ipa_es.tsv
 *   php generate-ipa.php --test             → Run built-in test suite
 *
 * Latin American Spanish (seseo, yeísmo):
 *   - c+e/i → /s/ (not /θ/)
 *   - z → /s/ (not /θ/)
 *   - ll → /ʝ/ (not /ʎ/)
 */

// ── IPA Conversion Engine ────────────────────────────────────────────

function spanishToIPA(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if ($word === '') return '';

    // Determine stress position
    $stressed = findStressedSyllable($word);
    $syllables = syllabify($word);

    // Character-by-character conversion with context
    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
    $len = count($chars);
    $ipa = '';

    for ($i = 0; $i < $len; $i++) {
        $c = $chars[$i];
        $next = $chars[$i + 1] ?? '';
        $prev = $chars[$i - 1] ?? '';
        $next2 = $chars[$i + 2] ?? '';

        switch ($c) {
            case 'a': case 'á':
                $ipa .= 'a'; break;
            case 'e': case 'é':
                $ipa .= 'e'; break;
            case 'i': case 'í':
                // 'i' between vowels or before vowel can be /j/
                if (isVowel($next) && !isVowel($prev) && $c === 'i') {
                    $ipa .= 'j';
                } else {
                    $ipa .= 'i';
                }
                break;
            case 'o': case 'ó':
                $ipa .= 'o'; break;
            case 'u': case 'ú':
                // Silent after g/q before e/i (gue, gui, que, qui)
                if (($prev === 'g' || $prev === 'q') && ($next === 'e' || $next === 'i' || $next === 'é' || $next === 'í')) {
                    // Silent u — skip
                } else {
                    $ipa .= 'u';
                }
                break;
            case 'ü':
                $ipa .= 'u'; break; // güe, güi — u is pronounced

            case 'b':
                // /b/ after pause or nasal, /β/ elsewhere
                if ($i === 0 || $prev === 'n' || $prev === 'm') {
                    $ipa .= 'b';
                } else {
                    $ipa .= 'β';
                }
                break;
            case 'v':
                // Same as b in Spanish
                if ($i === 0 || $prev === 'n' || $prev === 'm') {
                    $ipa .= 'b';
                } else {
                    $ipa .= 'β';
                }
                break;

            case 'c':
                if ($next === 'h') {
                    $ipa .= 'tʃ';
                    $i++; // skip 'h'
                } elseif ($next === 'e' || $next === 'i' || $next === 'é' || $next === 'í') {
                    $ipa .= 's'; // seseo (Latin American)
                } else {
                    $ipa .= 'k';
                }
                break;

            case 'd':
                if ($i === 0 || $prev === 'n' || $prev === 'l') {
                    $ipa .= 'd';
                } else {
                    $ipa .= 'ð';
                }
                break;

            case 'f':
                $ipa .= 'f'; break;

            case 'g':
                if ($next === 'e' || $next === 'i' || $next === 'é' || $next === 'í') {
                    // ge, gi → /x/ (like j)
                    $ipa .= 'x';
                } elseif ($next === 'u' && ($next2 === 'e' || $next2 === 'i' || $next2 === 'é' || $next2 === 'í')) {
                    // gue, gui → /g/ (u silent unless ü)
                    if ($i === 0 || $prev === 'n') {
                        $ipa .= 'ɡ';
                    } else {
                        $ipa .= 'ɣ';
                    }
                } else {
                    if ($i === 0 || $prev === 'n') {
                        $ipa .= 'ɡ';
                    } else {
                        $ipa .= 'ɣ';
                    }
                }
                break;

            case 'h':
                // Silent in Spanish
                break;

            case 'j':
                $ipa .= 'x'; break;

            case 'k':
                $ipa .= 'k'; break;

            case 'l':
                if ($next === 'l') {
                    $ipa .= 'ʝ'; // yeísmo (Latin American)
                    $i++; // skip second l
                } else {
                    $ipa .= 'l';
                }
                break;

            case 'm':
                $ipa .= 'm'; break;

            case 'n':
                // Assimilation: n before b/v/p → /m/, n before k/g → /ŋ/
                if ($next === 'b' || $next === 'v' || $next === 'p') {
                    $ipa .= 'm';
                } elseif ($next === 'g' || $next === 'k' || $next === 'j') {
                    $ipa .= 'ŋ';
                } else {
                    $ipa .= 'n';
                }
                break;

            case 'ñ':
                $ipa .= 'ɲ'; break;

            case 'p':
                $ipa .= 'p'; break;

            case 'q':
                // Always followed by 'u' — /k/, u is silent
                $ipa .= 'k';
                if ($next === 'u') $i++; // skip u
                break;

            case 'r':
                if ($next === 'r') {
                    $ipa .= 'r'; // trilled rr
                    $i++; // skip second r
                } elseif ($i === 0 || $prev === 'n' || $prev === 'l' || $prev === 's') {
                    $ipa .= 'r'; // trilled r at start or after n/l/s
                } else {
                    $ipa .= 'ɾ'; // flap r
                }
                break;

            case 's':
                $ipa .= 's'; break;

            case 't':
                $ipa .= 't'; break;

            case 'w':
                $ipa .= 'w'; break;

            case 'x':
                // Between vowels or before consonant: /ks/
                // In México, mexicano: /x/ (but we use the general rule)
                $ipa .= 'ks'; break;

            case 'y':
                if ($i === $len - 1) {
                    // Word-final y is vowel /i/
                    $ipa .= 'i';
                } elseif (isVowel($next) || isAccentedVowel($next)) {
                    $ipa .= 'ʝ'; // Consonantal y
                } else {
                    $ipa .= 'i'; // Vowel y
                }
                break;

            case 'z':
                $ipa .= 's'; break; // seseo (Latin American)

            default:
                // Pass through unknown characters (hyphens, etc.)
                if ($c !== '-' && $c !== ' ') {
                    $ipa .= $c;
                }
                break;
        }
    }

    // Add stress mark
    $ipa = addStressMark($ipa, $word);

    return $ipa;
}

function isVowel(string $c): bool {
    return in_array($c, ['a', 'e', 'i', 'o', 'u'], true);
}

function isAccentedVowel(string $c): bool {
    return in_array($c, ['á', 'é', 'í', 'ó', 'ú', 'ü'], true);
}

function isVowelOrAccented(string $c): bool {
    return isVowel($c) || isAccentedVowel($c);
}

/**
 * Find which syllable carries the stress.
 * Spanish stress rules:
 *   1. If the word has a written accent (tilde), stress falls on that syllable
 *   2. Words ending in vowel, n, or s → stress on penultimate syllable
 *   3. Words ending in consonant (except n, s) → stress on last syllable
 */
function findStressedSyllable(string $word): int {
    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

    // Check for written accent
    foreach ($chars as $i => $c) {
        if (isAccentedVowel($c)) {
            return $i; // Position of the accented vowel
        }
    }

    // No accent: apply default rules
    $last = end($chars);
    if (isVowel($last) || $last === 'n' || $last === 's') {
        return -2; // Penultimate
    }
    return -1; // Last
}

/**
 * Simplified syllabification for stress mark placement.
 * Returns approximate syllable count.
 */
function syllabify(string $word): int {
    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
    $syllables = 0;
    $prevVowel = false;

    foreach ($chars as $c) {
        $v = isVowelOrAccented($c);
        if ($v && !$prevVowel) {
            $syllables++;
        }
        // Diphthong: i/u + strong vowel or vice versa stay in same syllable
        // Hiatus: two strong vowels split
        if ($v && $prevVowel) {
            $strong1 = in_array($chars[array_search($c, $chars) - 1] ?? '', ['a', 'á', 'e', 'é', 'o', 'ó']);
            $strong2 = in_array($c, ['a', 'á', 'e', 'é', 'o', 'ó']);
            if ($strong1 && $strong2) {
                $syllables++; // Hiatus
            }
            // Accented weak vowel also creates hiatus
            if (in_array($c, ['í', 'ú'])) {
                $syllables++;
            }
        }
        $prevVowel = $v;
    }

    return max(1, $syllables);
}

/**
 * Check if an IPA character is a vowel.
 */
function isIPAVowel(string $c): bool {
    return in_array($c, ['a', 'e', 'i', 'o', 'u'], true);
}

/**
 * Check if an IPA character is a glide (semivowel).
 * Glides belong with the following vowel, not as consonant onsets.
 */
function isIPAGlide(string $c): bool {
    return in_array($c, ['j', 'w'], true);
}

/**
 * Check if two IPA consonants form a valid Spanish onset cluster.
 * Valid onsets: stop/fricative + liquid (r/l): pr, br, tr, dr, kr, gr, fr, pl, bl, kl, gl, fl
 * Also includes allophonic variants: β=b, ð=d, ɣ=g
 */
function isValidOnsetCluster(string $c1, string $c2): bool {
    // Normalize allophones to their underlying phonemes for cluster checking
    $norm = ['β' => 'b', 'ð' => 'd', 'ɣ' => 'ɡ'];
    $n1 = $norm[$c1] ?? $c1;
    $n2 = $norm[$c2] ?? $c2;

    $valid = [
        'pɾ','bɾ','tɾ','dɾ','kɾ','ɡɾ','fɾ',
        'pl','bl','tl','kl','ɡl','fl',
        'pr','br','tr','dr','kr','gr','fr',
    ];
    return in_array($n1 . $n2, $valid, true);
}

/**
 * Add IPA stress mark (ˈ) before the stressed syllable.
 * For monosyllabic words, no stress mark needed.
 * Works with UTF-8 multi-byte IPA characters via character arrays.
 */
function addStressMark(string $ipa, string $originalWord): string {
    // Split IPA into character array (handles multi-byte)
    $ipaChars = preg_split('//u', $ipa, -1, PREG_SPLIT_NO_EMPTY);
    $ipaLen = count($ipaChars);

    // Find vowel positions (indices into ipaChars)
    $vowelIndices = [];
    foreach ($ipaChars as $idx => $ch) {
        if (isIPAVowel($ch)) {
            $vowelIndices[] = $idx;
        }
    }

    if (count($vowelIndices) <= 1) {
        return $ipa; // Monosyllable — no stress mark
    }

    // Determine which vowel is stressed
    $hasAccent = preg_match('/[áéíóú]/u', $originalWord);
    $origChars = preg_split('//u', $originalWord, -1, PREG_SPLIT_NO_EMPTY);
    $lastChar = end($origChars);

    $stressVowelIdx = -1; // index into $vowelIndices

    if ($hasAccent) {
        $vowelCount = 0;
        foreach ($origChars as $oc) {
            if (isVowelOrAccented($oc)) {
                if (isAccentedVowel($oc)) {
                    $stressVowelIdx = $vowelCount;
                    break;
                }
                $vowelCount++;
            }
        }
    } else {
        if (isVowel($lastChar) || $lastChar === 'n' || $lastChar === 's') {
            $stressVowelIdx = count($vowelIndices) - 2; // Penultimate
        } else {
            $stressVowelIdx = count($vowelIndices) - 1; // Ultimate
        }
    }

    if ($stressVowelIdx < 0) $stressVowelIdx = 0;
    if ($stressVowelIdx >= count($vowelIndices)) $stressVowelIdx = count($vowelIndices) - 1;

    // Find the character position of the stressed vowel
    $stressPos = $vowelIndices[$stressVowelIdx];

    // Walk back from the stressed vowel to find syllable onset
    // First, include any glides (j, w) right before the vowel — they're part of the nucleus
    $nucleusStart = $stressPos;
    if ($nucleusStart > 0 && isIPAGlide($ipaChars[$nucleusStart - 1])) {
        $nucleusStart--;
    }

    // Now collect consonants between nucleus start and the previous vowel
    $insertAt = $nucleusStart;
    $consonantsBefore = [];
    $pos = $nucleusStart - 1;
    while ($pos >= 0 && !isIPAVowel($ipaChars[$pos])) {
        array_unshift($consonantsBefore, $ipaChars[$pos]);
        $pos--;
    }

    if (count($consonantsBefore) === 0) {
        // No consonants before — insert stress before the nucleus
        $insertAt = $nucleusStart;
    } elseif (count($consonantsBefore) === 1) {
        // Single consonant → it's the onset
        $insertAt = $nucleusStart - 1;
    } else {
        // Multiple consonants: check if last two form a valid onset cluster
        $last = $consonantsBefore[count($consonantsBefore) - 1];
        $secondLast = $consonantsBefore[count($consonantsBefore) - 2];
        if (isValidOnsetCluster($secondLast, $last)) {
            // Both belong to onset
            $insertAt = $nucleusStart - 2;
        } else {
            // Only the last consonant is the onset
            $insertAt = $nucleusStart - 1;
        }
    }

    // Build result: insert ˈ at insertAt position
    $result = '';
    for ($i = 0; $i < $ipaLen; $i++) {
        if ($i === $insertAt) $result .= 'ˈ';
        $result .= $ipaChars[$i];
    }

    return $result;
}


// ── Main Execution ───────────────────────────────────────────────────

$mode = 'db'; // default: update database
if (in_array('--standalone', $argv ?? [])) $mode = 'standalone';
if (in_array('--test', $argv ?? [])) $mode = 'test';

if ($mode === 'test') {
    runTests();
    exit(0);
}

if ($mode === 'standalone') {
    runStandalone();
    exit(0);
}

runDatabase();

// ── Modes ────────────────────────────────────────────────────────────

function runTests(): void {
    $tests = [
        // Basic vowels
        'casa' => 'ˈkasa',
        'mesa' => 'ˈmesa',
        // Accented
        'café' => 'kaˈfe',
        'mamá' => 'maˈma',
        // Silent h
        'hola' => 'ˈola',
        'hacer' => 'aˈseɾ',
        // ch → tʃ
        'noche' => 'ˈnotʃe',
        'mucho' => 'ˈmutʃo',
        // ll → ʝ (yeísmo)
        'calle' => 'ˈkaʝe',
        'ella' => 'ˈeʝa',
        // ñ → ɲ
        'niño' => 'ˈniɲo',
        'año' => 'ˈaɲo',
        // rr → r (trill)
        'perro' => 'ˈpero',
        // r initial → r (trill)
        'río' => 'ˈrio',
        // r intervocalic → ɾ (flap)
        'pero' => 'ˈpeɾo',
        // c+e/i → s (seseo)
        'cielo' => 'ˈsjelo',
        'ciudad' => 'sjuˈðað',
        // z → s (seseo)
        'zapato' => 'saˈpato',
        // g+e/i → x
        'gente' => 'ˈxente',
        // j → x
        'jardín' => 'xaɾˈðin',
        // qu → k
        'queso' => 'ˈkeso',
        // gue/gui → g (u silent)
        'guerra' => 'ˈɡera',
        // b/v → b/β
        'vamos' => 'ˈbamos',
        // d → d/ð
        'donde' => 'ˈdonde',
        // ñ
        'español' => 'espaˈɲol',
        // Monosyllables (no stress mark)
        'sol' => 'sol',
        'pan' => 'pan',
        'tres' => 'tɾes',
        // Stress: ends in consonant (not n/s) → last syllable
        'hablar' => 'aˈβlaɾ',
        'comer' => 'koˈmeɾ',
        'vivir' => 'biˈβiɾ',
        // Stress: ends in vowel/n/s → penultimate
        'libro' => 'ˈliβɾo',
        'hombre' => 'ˈombɾe',
    ];

    $pass = 0;
    $fail = 0;
    foreach ($tests as $word => $expected) {
        $got = spanishToIPA($word);
        $status = ($got === $expected) ? 'PASS' : 'FAIL';
        if ($status === 'FAIL') {
            echo "  $status: $word → got '$got', expected '$expected'\n";
            $fail++;
        } else {
            $pass++;
        }
    }
    echo "\nTests: $pass passed, $fail failed out of " . count($tests) . "\n";
}

function runStandalone(): void {
    $dataDir = __DIR__ . '/data';
    $freqFile = $dataDir . '/frequency_es.tsv';
    $outFile = $dataDir . '/ipa_es.tsv';

    if (!file_exists($freqFile)) {
        echo "ERROR: frequency_es.tsv not found\n";
        exit(1);
    }

    echo "=== IPA Generator (standalone) ===\n";

    $fp = fopen($freqFile, 'r');
    fgetcsv($fp, 0, "\t"); // Skip header
    $words = [];
    while (($row = fgetcsv($fp, 0, "\t")) !== false) {
        if (count($row) >= 2) {
            $words[] = ['rank' => (int)$row[0], 'word' => trim($row[1])];
        }
    }
    fclose($fp);

    echo "  Words to process: " . count($words) . "\n";

    $out = fopen($outFile, 'w');
    fwrite($out, "rank\tword\tipa\n");

    foreach ($words as $entry) {
        $ipa = spanishToIPA($entry['word']);
        fwrite($out, "{$entry['rank']}\t{$entry['word']}\t/$ipa/\n");
    }
    fclose($out);

    echo "  Output: $outFile\n";
    echo "  Done.\n";
}

function runDatabase(): void {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();

    echo "=== IPA Generator (database mode) ===\n";

    // Get all Spanish words without IPA
    $stmt = $pdo->query(
        "SELECT id, word FROM dict_words
         WHERE lang_code = 'es' AND (pronunciation_ipa IS NULL OR pronunciation_ipa = '')
         ORDER BY frequency_rank ASC"
    );
    $words = $stmt->fetchAll();
    echo "  Words without IPA: " . count($words) . "\n";

    $update = $pdo->prepare(
        "UPDATE dict_words SET pronunciation_ipa = ? WHERE id = ?"
    );

    $count = 0;
    foreach ($words as $row) {
        $ipa = '/' . spanishToIPA($row['word']) . '/';
        $update->execute([$ipa, $row['id']]);
        $count++;
    }

    echo "  Updated: $count words\n";

    // Show summary
    $stmt = $pdo->query(
        "SELECT COUNT(*) as total,
                SUM(CASE WHEN pronunciation_ipa IS NOT NULL AND pronunciation_ipa != '' THEN 1 ELSE 0 END) as with_ipa
         FROM dict_words WHERE lang_code = 'es'"
    );
    $row = $stmt->fetch();
    echo "  Total Spanish words: {$row['total']}\n";
    echo "  With IPA: {$row['with_ipa']}\n";
}
