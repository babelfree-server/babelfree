<?php
/**
 * Conjugation Loader — Imports verb conjugation data into dict_conjugations.
 * Reads from api/scripts/data/conjugations_es_processed.json (post-processed format).
 *
 * Expected JSON format (per verb):
 * {
 *   "hablar": {
 *     "lemma": "hablar",
 *     "verb_type": "regular -ar",
 *     "conjugations": {
 *       "indicative":  { "present": {"yo":"hablo", "tú":"hablas", "él/ella/usted":"habla", ...}, ... },
 *       "subjunctive": { "present": {...}, "imperfect": {...} },
 *       "imperative":  { "affirmative": {"tú":"habla", "usted":"hable", ...},
 *                        "negative":    {"tú":"no hables", "usted":"no hable", ...} }
 *     },
 *     "postprocess_meta": { ... }
 *   }
 * }
 *
 * DB columns: word_id, tense, mood, yo, tu, vos, el, nosotros, ustedes, ellos, is_irregular
 * Mood/tense stored in Spanish (backward compatible).
 * Person keys mapped from compound labels to DB columns.
 *
 * Run: php seed-conjugations.php
 */

require_once __DIR__ . '/../config/database.php';

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS — mapping processed format → DB values
// ═══════════════════════════════════════════════════════════════════════════

// English mood → Spanish DB mood
const MOOD_TO_DB = [
    'indicative'  => 'indicativo',
    'subjunctive' => 'subjuntivo',
    'imperative'  => 'imperativo',
];

// English tense → Spanish DB tense
const TENSE_TO_DB = [
    'present'     => 'presente',
    'preterite'   => 'pretérito indefinido',
    'imperfect'   => 'pretérito imperfecto',
    'future'      => 'futuro',
    'conditional' => 'condicional',
    'affirmative' => 'afirmativo',
    'negative'    => 'negativo',
];

// ═══════════════════════════════════════════════════════════════════════════
// PERSON KEY → DB COLUMN MAPPING
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Extract DB column values from a processed person→form table.
 * Handles both indicative/subjunctive labels (él/ella/usted, ellos/ellas/ustedes)
 * and imperative labels (usted, ustedes).
 */
function mapPersonsToColumns(array $forms): array {
    return [
        'yo'        => $forms['yo'] ?? null,
        'tu'        => $forms['tú'] ?? null,
        'vos'       => null, // removed from processed data
        'el'        => $forms['él/ella/usted'] ?? $forms['usted'] ?? null,
        'nosotros'  => $forms['nosotros'] ?? null,
        'ustedes'   => $forms['ellos/ellas/ustedes'] ?? $forms['ustedes'] ?? null,
        'ellos'     => $forms['ellos/ellas/ustedes'] ?? $forms['ustedes'] ?? null,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// NORMALIZE (for dict_words lookup)
// ═══════════════════════════════════════════════════════════════════════════

function normalizeWord(string $word): string {
    $word = mb_strtolower(trim($word), 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        return transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
    }
    $word = preg_replace('/[áà]/u', 'a', $word);
    $word = preg_replace('/[éè]/u', 'e', $word);
    $word = preg_replace('/[íì]/u', 'i', $word);
    $word = preg_replace('/[óò]/u', 'o', $word);
    $word = preg_replace('/[úù]/u', 'u', $word);
    return str_replace('ñ', 'n', $word);
}

// ═══════════════════════════════════════════════════════════════════════════
// MAIN
// ═══════════════════════════════════════════════════════════════════════════

$pdo = getDB();
$dataDir = __DIR__ . '/data';

echo "=== Conjugation Loader (processed format) ===\n\n";

$conjFile = $dataDir . '/conjugations_es_processed.json';

if (!file_exists($conjFile)) {
    echo "ERROR: conjugations_es_processed.json not found in $dataDir\n";
    echo "Run: python3 postprocess_conjugations.py\n";
    exit(1);
}

$rawJson = file_get_contents($conjFile);
$conjData = json_decode($rawJson, true);

if (!$conjData) {
    echo "ERROR: Invalid JSON in conjugations_es_processed.json\n";
    exit(1);
}

echo "Loaded " . count($conjData) . " verbs from processed file\n\n";

// Prepared statements
$findWord = $pdo->prepare(
    "SELECT id FROM dict_words WHERE lang_code = 'es' AND word_normalized = ? LIMIT 1"
);

$insertVerb = $pdo->prepare(
    'INSERT INTO dict_words (lang_code, word, word_normalized, part_of_speech)
     VALUES ("es", ?, ?, "verb")'
);

$deleteConj = $pdo->prepare(
    'DELETE FROM dict_conjugations WHERE word_id = ?'
);

$insertConj = $pdo->prepare(
    'INSERT INTO dict_conjugations (word_id, tense, mood, yo, tu, vos, el, nosotros, ustedes, ellos, is_irregular)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$verbCount = 0;
$conjCount = 0;
$negCount = 0;
$notFound = 0;

$pdo->beginTransaction();

try {
    foreach ($conjData as $lemma => $entry) {
        $verb = $entry['lemma'] ?? $lemma;
        $norm = normalizeWord($verb);

        // Derive is_irregular from verb_type
        $isIrregular = str_starts_with($entry['verb_type'] ?? '', 'irregular') ? 1 : 0;

        // Find or create the word entry
        $findWord->execute([$norm]);
        $row = $findWord->fetch();

        if (!$row) {
            $insertVerb->execute([$verb, $norm]);
            $wordId = $pdo->lastInsertId();
            if (!$wordId) {
                $findWord->execute([$norm]);
                $row = $findWord->fetch();
                $wordId = $row ? (int)$row['id'] : null;
            }
        } else {
            $wordId = (int)$row['id'];
            $pdo->prepare("UPDATE dict_words SET part_of_speech = 'verb' WHERE id = ?")->execute([$wordId]);
        }

        if (!$wordId) {
            $notFound++;
            continue;
        }

        // Clear existing conjugation rows for this verb (idempotent re-runs)
        $deleteConj->execute([$wordId]);

        // Iterate moods and tenses from processed format
        $conjugations = $entry['conjugations'] ?? [];

        foreach ($conjugations as $engMood => $tenses) {
            $dbMood = MOOD_TO_DB[$engMood] ?? $engMood;
            if (!is_array($tenses)) continue;

            foreach ($tenses as $engTense => $forms) {
                if (!is_array($forms)) continue;
                $dbTense = TENSE_TO_DB[$engTense] ?? $engTense;

                $cols = mapPersonsToColumns($forms);

                $insertConj->execute([
                    $wordId,
                    $dbTense,
                    $dbMood,
                    $cols['yo'],
                    $cols['tu'],
                    $cols['vos'],
                    $cols['el'],
                    $cols['nosotros'],
                    $cols['ustedes'],
                    $cols['ellos'],
                    $isIrregular,
                ]);
                $conjCount++;

                if ($engTense === 'negative') {
                    $negCount++;
                }
            }
        }
        $verbCount++;
    }

    $pdo->commit();
    echo "=== Conjugation Loading Complete ===\n";
    echo "  Verbs processed:      $verbCount\n";
    echo "  Conjugation rows:     $conjCount\n";
    echo "  Negative imp. rows:   $negCount\n";
    if ($notFound > 0) {
        echo "  Not found/created:    $notFound\n";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
