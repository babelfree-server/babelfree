<?php
/**
 * Build Free Play Catalog
 *
 * Scans all /content/*.json files and builds a unified catalog of games
 * suitable for standalone free play (no story context required).
 *
 * Excludes: narrative, escaperoom, story types (story-dependent)
 *
 * Usage: php api/scripts/build-freeplay-catalog.php
 */

$contentDir = realpath(__DIR__ . '/../../content');
if (!$contentDir) {
    echo "ERROR: Could not resolve content directory.\n";
    exit(1);
}

// Game types excluded from free play (story-dependent or non-interactive)
$excludedTypes = ['narrative', 'escaperoom', 'story', 'cancion'];

// Type alias map (matches engine's Normalizer)
$typeAliases = [
    'fib' => 'fill',
    'fill-in' => 'fill',
    'matching' => 'pair',
    'match' => 'pair',
    'conj' => 'conjugation',
    'conjugate' => 'conjugation',
    'listen' => 'listening',
    'audio' => 'listening',
    'sort' => 'category',
    'sorting' => 'category',
    'build' => 'builder',
    'sentence' => 'builder',
    'trans' => 'translation',
    'translate' => 'translation',
    'conv' => 'conversation',
    'dialogue' => 'conversation',
    'dialog' => 'conversation',
    'escape' => 'escaperoom',
    'dictate' => 'dictation',
];

// Friendly labels for game types
$typeLabels = [
    'pair' => 'Emparejar',
    'fill' => 'Completar',
    'conjugation' => 'Conjugar',
    'listening' => 'Escuchar',
    'category' => 'Clasificar',
    'builder' => 'Construir',
    'translation' => 'Traducir',
    'conversation' => 'Conversar',
    'dictation' => 'Dictado',
    'crossword' => 'Crucigrama',
    'bingo' => 'Bingo',
    'boggle' => 'Boggle',
    'scrabble' => 'Scrabble',
    'madlibs' => 'Mad Libs',
    'kloo' => 'Kloo',
    'spaceman' => 'Spaceman',
    'bananagrams' => 'Bananagrams',
    'consequences' => 'Consecuencias',
    'madgab' => 'Madgab',
    'conjuro' => 'Conjuro',
    'explorador' => 'Explorador',
    'senda' => 'Senda',
    'guardian' => 'Guardián',
    'clon' => 'Clon',
    'eco_restaurar' => 'Restaurar',
    'flashnote' => 'Flashnote',
    'cultura' => 'Cultura',
];

$catalog = [];
$levels = [];
$gameTypes = [];
$id = 0;

$files = glob($contentDir . '/*.json');
sort($files);

foreach ($files as $file) {
    $basename = basename($file);

    // Skip the catalog itself and debug files
    if ($basename === 'freeplay-catalog.json' || strpos($basename, 'debug_') === 0) {
        continue;
    }

    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if (!$data || !isset($data['games'])) {
        echo "SKIP: $basename (no games array)\n";
        continue;
    }

    $meta = $data['meta'] ?? [];
    $ecosystem = $meta['ecosystem'] ?? pathinfo($basename, PATHINFO_FILENAME);
    $cefr = $meta['cefr'] ?? 'A1';

    echo "Processing: $basename ({$cefr}, " . count($data['games']) . " games)...\n";

    foreach ($data['games'] as $idx => $game) {
        $rawType = strtolower(trim($game['type'] ?? ''));
        $normalizedType = $typeAliases[$rawType] ?? $rawType;

        // Skip excluded types
        if (in_array($normalizedType, $excludedTypes)) {
            continue;
        }

        $id++;
        $label = $typeLabels[$normalizedType] ?? ucfirst($normalizedType);
        $title = $game['title'] ?? $game['instruction'] ?? $game['label'] ?? $label;

        $catalog[] = [
            'id' => 'fp_' . $id,
            'source' => $basename,
            'sourceIdx' => $idx,
            'ecosystem' => $ecosystem,
            'cefr' => strtoupper($cefr),
            'type' => $normalizedType,
            'label' => $label,
            'title' => mb_substr($title, 0, 100),
        ];

        $levels[strtoupper($cefr)] = true;
        $gameTypes[$normalizedType] = true;
    }
}

// Sort levels in CEFR order
$cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
$sortedLevels = array_values(array_intersect($cefrOrder, array_keys($levels)));
$sortedTypes = array_keys($gameTypes);
sort($sortedTypes);

$output = [
    'games' => $catalog,
    'levels' => $sortedLevels,
    'gameTypes' => $sortedTypes,
    'totalGames' => count($catalog),
    'builtAt' => date('c'),
];

$outPath = $contentDir . '/freeplay-catalog.json';
$written = file_put_contents(
    $outPath,
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

if ($written === false) {
    echo "ERROR: Could not write $outPath\n";
    exit(1);
}

echo "\nCatalog built: " . count($catalog) . " playable games across " . count($sortedLevels) . " levels and " . count($sortedTypes) . " game types.\n";
echo "Output: $outPath\n";
