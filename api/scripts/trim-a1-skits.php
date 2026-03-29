<?php
/**
 * Trim A1 skits (dest1-12) to max 8 beats.
 * Rules:
 * - Keep first and last beat always
 * - Keep beats with "gris" or "escucha"
 * - Keep character introductions ("Yo soy...")
 * - Keep most narratively important middle beats
 * - Remove filler narrator descriptions (single-word narrator beats are vocab tags, keep last one only)
 * - Simplify beats with 15+ words to under 12 words
 */

$MAX_BEATS = 8;
$contentDir = dirname(__DIR__, 2) . '/content';
$report = [];

for ($d = 1; $d <= 12; $d++) {
    $file = "$contentDir/dest{$d}.json";
    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        echo "ERROR: Could not parse dest{$d}.json\n";
        continue;
    }

    $destReport = [];
    $changed = false;

    // Process arrival
    if (isset($data['arrival']['beats'])) {
        $before = count($data['arrival']['beats']);
        $data['arrival']['beats'] = trimBeats($data['arrival']['beats'], $MAX_BEATS);
        $after = count($data['arrival']['beats']);
        if ($before !== $after) $changed = true;
        // Also simplify long beats
        $simplified = simplifyLongBeats($data['arrival']['beats']);
        if ($simplified) $changed = true;
        $destReport[] = "  arrival: $before -> $after beats" . ($simplified ? " (+ simplified)" : "");
    }

    // Process games
    if (isset($data['games'])) {
        foreach ($data['games'] as $gi => &$game) {
            if (isset($game['type']) && $game['type'] === 'skit' && isset($game['beats'])) {
                $before = count($game['beats']);
                $game['beats'] = trimBeats($game['beats'], $MAX_BEATS);
                $after = count($game['beats']);
                if ($before !== $after) $changed = true;
                $simplified = simplifyLongBeats($game['beats']);
                if ($simplified) $changed = true;
                $label = $game['label'] ?? "game[$gi]";
                $destReport[] = "  game[$gi] \"$label\": $before -> $after beats" . ($simplified ? " (+ simplified)" : "");
            }
        }
        unset($game);
    }

    // Process departure
    if (isset($data['departure']['beats'])) {
        $before = count($data['departure']['beats']);
        $data['departure']['beats'] = trimBeats($data['departure']['beats'], $MAX_BEATS);
        $after = count($data['departure']['beats']);
        if ($before !== $after) $changed = true;
        $simplified = simplifyLongBeats($data['departure']['beats']);
        if ($simplified) $changed = true;
        $destReport[] = "  departure: $before -> $after beats" . ($simplified ? " (+ simplified)" : "");
    }

    if ($changed) {
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $report["dest$d"] = $destReport;
}

// Print report
echo "=== A1 SKIT TRIM REPORT ===\n\n";
foreach ($report as $dest => $lines) {
    echo "$dest:\n";
    foreach ($lines as $line) {
        echo "$line\n";
    }
    echo "\n";
}

function trimBeats(array $beats, int $max): array {
    $total = count($beats);
    if ($total <= $max) return $beats;

    // Separate vocab-tag beats at the end (single-word narrator beats like "Caldo,frutas,leche,pan")
    // These are typically the last 1-5 beats and are vocab tags, not narrative
    $vocabTags = [];
    $narrativeBeats = $beats;

    // Scan from end for vocab tag beats (narrator beats with <=5 words or single words)
    while (count($narrativeBeats) > 0) {
        $last = end($narrativeBeats);
        if ($last['speaker'] === 'narrator' && isVocabTag($last['text'])) {
            array_unshift($vocabTags, array_pop($narrativeBeats));
        } else {
            break;
        }
    }

    // If we stripped vocab tags, keep only the last one (with the comma-separated words)
    $bestVocabTag = null;
    foreach ($vocabTags as $vt) {
        if (strpos($vt['text'], ',') !== false) {
            $bestVocabTag = $vt;
        }
    }
    // If no comma tag, keep the last vocab tag
    if (!$bestVocabTag && !empty($vocabTags)) {
        $bestVocabTag = end($vocabTags);
    }

    $total = count($narrativeBeats);
    if ($total <= 0) return $beats; // safety

    // Now trim narrative beats to (max - 1 if we have a vocab tag, else max)
    $targetNarrative = $bestVocabTag ? ($max - 1) : $max;

    if ($total <= $targetNarrative) {
        $result = $narrativeBeats;
        if ($bestVocabTag) $result[] = $bestVocabTag;
        return $result;
    }

    // Score each beat for importance
    $scores = [];
    for ($i = 0; $i < $total; $i++) {
        $beat = $narrativeBeats[$i];
        $text = $beat['text'];
        $speaker = $beat['speaker'];
        $score = 0;

        // First and last are mandatory
        if ($i === 0) { $score += 1000; }
        if ($i === $total - 1) { $score += 900; }

        // "gris" or "escucha" reference
        if (preg_match('/\bgris\b/i', $text)) $score += 500;
        if (preg_match('/\bescucha/i', $text)) $score += 500;

        // Character introduction ("Yo soy...")
        if (preg_match('/\bYo soy\b/i', $text)) $score += 400;

        // Named character speaking (not narrator) gets base priority
        if ($speaker !== 'narrator') $score += 100;

        // New character's first appearance
        static $seenSpeakers = [];
        if (!isset($seenSpeakers[$speaker])) {
            $score += 200;
            $seenSpeakers[$speaker] = true;
        }

        // Question beats are engaging
        if (strpos($text, '?') !== false) $score += 50;

        // Ceiba speaking is important
        if ($speaker === 'ceiba') $score += 150;

        // Position preference: earlier beats slightly preferred for scene-setting
        $score += max(0, 30 - $i * 2);

        $scores[$i] = $score;
    }
    // Reset static for next call
    $seenSpeakers = [];

    // Sort by score, keep top N
    arsort($scores);
    $keepIndices = array_slice(array_keys($scores), 0, $targetNarrative);
    sort($keepIndices); // restore original order

    $result = [];
    foreach ($keepIndices as $i) {
        $result[] = $narrativeBeats[$i];
    }
    if ($bestVocabTag) {
        $result[] = $bestVocabTag;
    }

    return $result;
}

function isVocabTag(string $text): bool {
    // Single word or comma-separated short words (vocab tags)
    $text = trim($text);
    if (str_word_count($text) <= 1) return true;
    // Comma-separated list with no spaces after commas typically = vocab tag
    if (preg_match('/^[\wáéíóúüñÁÉÍÓÚÜÑ]+(,[\wáéíóúüñÁÉÍÓÚÜÑ]+)+\.?$/', $text)) return true;
    // Short comma-separated with spaces
    if (str_word_count($text) <= 5 && substr_count($text, ',') >= 2) return true;
    return false;
}

function simplifyLongBeats(array &$beats): bool {
    $simplified = false;
    foreach ($beats as &$beat) {
        if (isVocabTag($beat['text'])) continue;

        $wordCount = countSpanishWords($beat['text']);
        if ($wordCount >= 15) {
            $beat['text'] = simplifyText($beat['text'], $beat['speaker']);
            $simplified = true;
        }
    }
    unset($beat);
    return $simplified;
}

function countSpanishWords(string $text): int {
    // Count words more accurately for Spanish (handles ¿ ¡ etc)
    $clean = preg_replace('/[¿¡«»""\—\-]/', ' ', $text);
    return str_word_count($clean);
}

function simplifyText(string $text, string $speaker): string {
    // Strategy: split into sentences, keep the most important ones, trim each
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);

    if (count($sentences) <= 1) {
        // Single long sentence: truncate to key clause
        return truncateSentence($text);
    }

    // Score each sentence
    $scored = [];
    foreach ($sentences as $i => $s) {
        $score = 0;
        if ($i === 0) $score += 10; // first sentence
        if (preg_match('/\bgris\b/i', $s)) $score += 20;
        if (preg_match('/\bescucha/i', $s)) $score += 20;
        if (preg_match('/\bYo soy\b/i', $s)) $score += 15;
        if (strpos($s, '?') !== false) $score += 5;
        $score += max(0, 5 - $i); // prefer earlier
        $scored[$i] = $score;
    }
    arsort($scored);

    // Accumulate sentences until we hit ~10 words
    $kept = [];
    $totalWords = 0;
    $keptIndices = [];
    foreach (array_keys($scored) as $idx) {
        $wc = countSpanishWords($sentences[$idx]);
        if ($totalWords + $wc <= 12 || empty($kept)) {
            $keptIndices[] = $idx;
            $totalWords += $wc;
            if ($totalWords >= 10) break;
        }
    }
    sort($keptIndices);

    $result = '';
    foreach ($keptIndices as $idx) {
        $result .= ($result ? ' ' : '') . trim($sentences[$idx]);
    }

    // If still too long, truncate
    if (countSpanishWords($result) > 14) {
        $result = truncateSentence($result);
    }

    return $result;
}

function truncateSentence(string $text): string {
    // Split into words, keep first 10, add period if needed
    $words = preg_split('/\s+/', trim($text));
    $kept = array_slice($words, 0, 10);
    $result = implode(' ', $kept);
    // Clean trailing punctuation then add period
    $result = rtrim($result, ',;: ');
    if (!preg_match('/[.!?]$/', $result)) {
        $result .= '.';
    }
    return $result;
}
