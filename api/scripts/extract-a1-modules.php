<?php
/**
 * Extract A1 Module Games to JSON
 *
 * Parses inline `var games = [...]` from 6 A1 module HTML files
 * and outputs content JSON files matching the ecosystem_{spiral}.json structure.
 *
 * Usage: php api/scripts/extract-a1-modules.php
 */

$baseDir = realpath(__DIR__ . '/../../a1_basic');
$contentDir = realpath(__DIR__ . '/../../content');

if (!$baseDir || !$contentDir) {
    echo "ERROR: Could not resolve directories.\n";
    exit(1);
}

$modules = [
    1 => ['dir' => 'module1_family',  'title' => 'Módulo 1: Familia',  'theme' => 'family'],
    2 => ['dir' => 'module2_animals', 'title' => 'Módulo 2: Sonidos',  'theme' => 'animals'],
    3 => ['dir' => 'module3_water',   'title' => 'Módulo 3: Agua',     'theme' => 'water'],
    4 => ['dir' => 'module4_home',    'title' => 'Módulo 4: Casa',     'theme' => 'home'],
    5 => ['dir' => 'module5_food',    'title' => 'Módulo 5: Comida',   'theme' => 'food'],
    6 => ['dir' => 'module6_friends', 'title' => 'Módulo 6: Amigos',   'theme' => 'friends'],
];

$totalGames = 0;

foreach ($modules as $num => $mod) {
    $htmlPath = $baseDir . '/' . $mod['dir'] . '/games.html';
    if (!file_exists($htmlPath)) {
        echo "WARNING: File not found: $htmlPath\n";
        continue;
    }

    $html = file_get_contents($htmlPath);

    // Extract var games = [...];
    if (!preg_match('/var\s+games\s*=\s*(\[[\s\S]*?\])\s*;/', $html, $m)) {
        echo "WARNING: Could not extract games array from: $htmlPath\n";
        continue;
    }

    $jsArray = $m[1];

    // Convert JS object notation to valid JSON
    $json = jsToJson($jsArray);

    $games = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: JSON parse failed for module $num: " . json_last_error_msg() . "\n";
        // Write debug output
        file_put_contents($contentDir . "/debug_modulo{$num}.txt", $json);
        echo "  Debug output written to content/debug_modulo{$num}.txt\n";
        continue;
    }

    $count = count($games);
    $totalGames += $count;

    $output = [
        'meta' => [
            'ecosystem' => 'modulo',
            'spiral' => 'a1',
            'cefr' => 'A1',
            'title' => $mod['title'],
            'module' => $num,
            'theme' => $mod['theme'],
            'encounterCount' => $count,
        ],
        'games' => $games,
    ];

    $outPath = $contentDir . "/modulo{$num}_a1.json";
    $written = file_put_contents(
        $outPath,
        json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    if ($written === false) {
        echo "ERROR: Could not write $outPath\n";
    } else {
        echo "OK: modulo{$num}_a1.json — $count games\n";
    }
}

echo "\nTotal: $totalGames games extracted from " . count($modules) . " modules.\n";

/**
 * Convert JavaScript object notation to valid JSON.
 * Handles: unquoted keys, single-quoted strings, trailing commas,
 * JS comments, template literals simplified.
 */
function jsToJson(string $js): string {
    // Remove single-line comments (but not inside strings)
    $js = preg_replace('#^\s*//.*$#m', '', $js);

    // Remove multi-line comments
    $js = preg_replace('#/\*[\s\S]*?\*/#', '', $js);

    // Replace single-quoted strings with double-quoted
    // This handles the common case; nested quotes are uncommon in game data
    $result = '';
    $len = strlen($js);
    $i = 0;
    while ($i < $len) {
        $ch = $js[$i];

        // Double-quoted string — pass through
        if ($ch === '"') {
            $result .= $ch;
            $i++;
            while ($i < $len) {
                $c = $js[$i];
                $result .= $c;
                $i++;
                if ($c === '\\' && $i < $len) {
                    $result .= $js[$i];
                    $i++;
                } elseif ($c === '"') {
                    break;
                }
            }
            continue;
        }

        // Single-quoted string — convert to double-quoted
        if ($ch === "'") {
            $result .= '"';
            $i++;
            while ($i < $len) {
                $c = $js[$i];
                if ($c === '\\' && $i + 1 < $len) {
                    $next = $js[$i + 1];
                    if ($next === "'") {
                        // \' → '  (no need to escape single quotes in double-quoted JSON)
                        $result .= "'";
                        $i += 2;
                    } else {
                        $result .= $c . $next;
                        $i += 2;
                    }
                } elseif ($c === '"') {
                    // Escape double quotes inside what was a single-quoted string
                    $result .= '\\"';
                    $i++;
                } elseif ($c === "'") {
                    $result .= '"';
                    $i++;
                    break;
                } else {
                    $result .= $c;
                    $i++;
                }
            }
            continue;
        }

        $result .= $ch;
        $i++;
    }

    $js = $result;

    // Add quotes around unquoted keys: word_chars followed by :
    // Must not be inside a string — simplified approach works for game data
    $js = preg_replace('/(?<=[\{\[,\n])\s*([a-zA-Z_]\w*)\s*:/', '"$1":', $js);

    // Remove trailing commas before } or ]
    $js = preg_replace('/,\s*([\]\}])/', '$1', $js);

    return $js;
}
