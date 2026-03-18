<?php
/**
 * Clean frequency list — Filters non-Spanish entries from frequency_es.tsv.
 *
 * Applies filters:
 *   - Skip words < 2 characters
 *   - Skip ALL-CAPS entries (abbreviations)
 *   - Skip known English-only words not used in Spanish
 *   - Skip entries that are clearly non-words (numbers, symbols)
 *
 * Outputs a cleaned TSV to stdout or overwrites in-place with --in-place.
 * Use --dry-run to see what would be filtered without writing.
 *
 * Usage:
 *   php clean-frequency-list.php                    # Output to stdout
 *   php clean-frequency-list.php --dry-run          # Show filtered entries only
 *   php clean-frequency-list.php --in-place         # Overwrite frequency_es.tsv
 */

$dataDir = __DIR__ . '/data';
$inputFile = $dataDir . '/frequency_es.tsv';

$dryRun = in_array('--dry-run', $argv ?? []);
$inPlace = in_array('--in-place', $argv ?? []);

if (!file_exists($inputFile)) {
    fwrite(STDERR, "ERROR: frequency_es.tsv not found at $inputFile\n");
    exit(1);
}

// Known English-only words that are NOT Spanish (false positives in frequency corpora)
// Only include words that have zero Spanish meaning — many English words ARE valid Spanish
$englishOnly = array_flip([
    'the', 'and', 'for', 'with', 'that', 'this', 'from', 'not', 'but', 'you',
    'your', 'they', 'them', 'can', 'all', 'one', 'two', 'see', 'get', 'set',
    'out', 'new', 'now', 'how', 'did', 'its', 'may', 'our', 'own', 'too',
    'use', 'any', 'few', 'her', 'him', 'his', 'she', 'who', 'old', 'got',
    'let', 'put', 'say', 'ran', 'yet', 'run', 'sir', 'try', 'bit', 'cut',
    'hot', 'lot', 'sat', 'six', 'ten', 'top', 'add', 'bad', 'big', 'buy',
    'dry', 'eat', 'end', 'eye', 'far', 'fit', 'fly', 'fun', 'guy', 'hit',
    'job', 'kid', 'lie', 'low', 'map', 'mix', 'net', 'nor', 'odd', 'pay',
    'per', 'pin', 'pop', 'raw', 'red', 'rid', 'row', 'sad', 'sky', 'sum',
    'sun', 'tea', 'tie', 'tip', 'van', 'wet', 'win', 'won', 'yes', 'road',
    'like', 'over', 'look', 'down', 'city', 'back', 'best', 'call', 'come',
    'club', 'left', 'line', 'long', 'make', 'much', 'need', 'play', 'show',
    'some', 'song', 'sure', 'take', 'team', 'tell', 'time', 'turn', 'very',
    'want', 'west', 'what', 'when', 'work', 'year', 'been', 'done', 'each',
    'even', 'find', 'free', 'good', 'help', 'here', 'high', 'home', 'hope',
    'into', 'just', 'keep', 'kind', 'last', 'life', 'live', 'made', 'many',
    'more', 'most', 'must', 'name', 'next', 'only', 'open', 'part', 'past',
    'plan', 'rest', 'same', 'side', 'such', 'talk', 'than', 'then', 'upon',
    'used', 'user', 'wait', 'walk', 'went', 'wide', 'word', 'about', 'after',
    'again', 'being', 'black', 'bring', 'close', 'could', 'dream', 'early',
    'every', 'first', 'given', 'going', 'great', 'green', 'group', 'house',
    'known', 'large', 'later', 'least', 'level', 'light', 'might', 'money',
    'month', 'moved', 'never', 'night', 'often', 'order', 'other', 'place',
    'point', 'power', 'quite', 'right', 'round', 'seven', 'shall', 'since',
    'small', 'sound', 'south', 'space', 'start', 'state', 'still', 'story',
    'study', 'taken', 'their', 'there', 'think', 'third', 'those', 'three',
    'times', 'today', 'total', 'under', 'until', 'using', 'water', 'while',
    'white', 'whole', 'woman', 'women', 'world', 'would', 'write', 'wrong',
    'young', 'people', 'should', 'through', 'before', 'between', 'another',
    'because', 'during', 'without',
]);

// Words that look English but ARE valid Spanish — do NOT filter these
$spanishExceptions = array_flip([
    'bar', 'sol', 'plan', 'club', 'van', 'red', 'son', 'fin', 'pan', 'ven',
    'dan', 'den', 'sin', 'don', 'dos', 'gas', 'gen', 'mal', 'par', 'pie',
    'sal', 'ser', 'tan', 'ver', 'voz', 'sur', 'mes', 'mar', 'luz', 'ley',
    'ojo', 'dia', 'rio', 'oro', 'hoy', 'hay', 'fue', 'era', 'son', 'dar',
    'sir', 'bus', 'bit', 'set', // Accepted loanwords in Spanish
    'test', 'stop', 'chef', 'look', 'show', 'rock', 'jazz', 'rap', 'pop',
    'hit', 'fan', 'web', 'link', 'chat', 'blog', 'app', 'wifi', 'spam',
]);

$fp = fopen($inputFile, 'r');
$header = fgets($fp);

$kept = 0;
$filtered = 0;
$output = $header;
$filterLog = [];

while (($line = fgets($fp)) !== false) {
    $parts = explode("\t", trim($line));
    if (count($parts) < 2) continue;

    $rank = (int)$parts[0];
    $word = trim($parts[1]);
    $freq = $parts[2] ?? '';
    $reason = null;

    // Filter 1: Skip words < 2 characters
    if (mb_strlen($word) < 2) {
        $reason = 'too_short';
    }

    // Filter 2: Skip ALL-CAPS (abbreviations)
    if (!$reason && mb_strtoupper($word) === $word && preg_match('/^[A-Z]{2,}$/', $word)) {
        $reason = 'abbreviation';
    }

    // Filter 3: Skip entries with digits or symbols
    if (!$reason && preg_match('/[0-9@#$%^&*+=]/', $word)) {
        $reason = 'non_alpha';
    }

    // Filter 4: Skip English-only words (if not a valid Spanish word)
    $lower = mb_strtolower($word, 'UTF-8');
    if (!$reason && isset($englishOnly[$lower]) && !isset($spanishExceptions[$lower])) {
        // Only filter English words ranked > 5000 — high-rank ones are likely actual Spanish corpus entries
        if ($rank > 5000) {
            $reason = 'english_only';
        }
    }

    // Filter 5: Pure ASCII-only words > rank 7000 that match common English patterns
    if (!$reason && $rank > 7000 && preg_match('/^[a-z]+$/', $word) && !preg_match('/[ñ]/u', $word)) {
        // English suffix patterns not common in Spanish
        if (preg_match('/(ing|tion|ness|ment|ful|less|ous|ble|ship|ght|tch|wh|th[^e])$/', $word)) {
            $reason = 'english_pattern';
        }
    }

    if ($reason) {
        $filtered++;
        $filterLog[] = [$rank, $word, $reason];
    } else {
        $kept++;
        $output .= "$line";
    }
}

fclose($fp);

// Output
if ($dryRun) {
    fwrite(STDERR, "=== Frequency List Cleaner (DRY RUN) ===\n\n");
    fwrite(STDERR, "Filtered entries:\n");
    foreach ($filterLog as [$rank, $word, $reason]) {
        fwrite(STDERR, "  #{$rank}\t{$word}\t→ {$reason}\n");
    }
    fwrite(STDERR, "\nKept: {$kept}\n");
    fwrite(STDERR, "Filtered: {$filtered}\n");
    fwrite(STDERR, "Total: " . ($kept + $filtered) . "\n");
} elseif ($inPlace) {
    file_put_contents($inputFile, $output);
    fwrite(STDERR, "=== Frequency List Cleaner ===\n");
    fwrite(STDERR, "  Kept: {$kept}\n");
    fwrite(STDERR, "  Filtered: {$filtered}\n");
    fwrite(STDERR, "  Written to: {$inputFile}\n");
} else {
    echo $output;
    fwrite(STDERR, "=== Frequency List Cleaner ===\n");
    fwrite(STDERR, "  Kept: {$kept}\n");
    fwrite(STDERR, "  Filtered: {$filtered}\n");
    fwrite(STDERR, "  (Output sent to stdout — use --in-place to overwrite file)\n");
}
