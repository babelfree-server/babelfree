<?php
/**
 * Generate audio manifest from all 89 destination files.
 * Outputs: audio-manifest.html + content/audio-script.json
 */

$baseDir = '/home/babelfree.com/public_html';
$contentDir = $baseDir . '/content';

$lines = []; // All voiced lines
$stats = [
    'byType' => [],
    'bySpeaker' => [],
    'byDest' => [],
    'total' => 0,
    'totalDuration' => 0
];

function addLine(&$lines, &$stats, $dest, $type, $speaker, $text, $cefr = '') {
    if (empty(trim($text)) || strlen(trim($text)) < 2) return;
    // Clean text
    $text = trim($text);
    // Word count / 2.5 for Spanish speech rate
    $words = str_word_count($text, 0, 'áéíóúñüÁÉÍÓÚÑÜ');
    $duration = round($words / 2.5, 1);

    $speaker = strtolower(trim($speaker));
    // Normalize speaker names
    $speakerMap = [
        'yaguará' => 'yaguara',
        'char_yaguara' => 'yaguara',
        'char_candelaria' => 'candelaria',
        'char_ceiba' => 'ceiba',
        'char_rio' => 'rio',
        'char_condor' => 'condor_viejo',
        'char_condor_viejo' => 'condor_viejo',
        'char_colibri' => 'colibri',
        'char_delfin_rosado' => 'delfin_rosado',
        'char_don_prospero' => 'don_prospero',
        'char_dona_asuncion' => 'dona_asuncion',
        'char_mama_jaguar' => 'mama_jaguar',
        'char_maestro' => 'maestro',
        'char_rana' => 'rana',
        'char_sombra' => 'sombra',
    ];
    if (isset($speakerMap[$speaker])) $speaker = $speakerMap[$speaker];

    $line = [
        'dest' => (int)$dest,
        'type' => $type,
        'speaker' => $speaker,
        'text' => $text,
        'cefr' => $cefr,
        'duration_est' => $duration,
        'words' => $words
    ];
    $lines[] = $line;

    $stats['total']++;
    $stats['totalDuration'] += $duration;
    @$stats['byType'][$type]['count']++;
    @$stats['byType'][$type]['duration'] += $duration;
    @$stats['bySpeaker'][$speaker]['count']++;
    @$stats['bySpeaker'][$speaker]['duration'] += $duration;
    @$stats['byDest'][$dest]['count']++;
    @$stats['byDest'][$dest]['duration'] += $duration;
    if ($cefr && !isset($stats['byDest'][$dest]['cefr'])) {
        $stats['byDest'][$dest]['cefr'] = $cefr;
    }
}

function extractBeats(&$lines, &$stats, $beats, $dest, $type, $cefr) {
    if (!is_array($beats)) return;
    foreach ($beats as $beat) {
        if (isset($beat['speaker']) && isset($beat['text'])) {
            addLine($lines, $stats, $dest, $type, $beat['speaker'], $beat['text'], $cefr);
        }
    }
}

function extractGames(&$lines, &$stats, $games, $dest, $cefr) {
    if (!is_array($games)) return;
    foreach ($games as $game) {
        $type = $game['type'] ?? 'unknown';

        // Skit games - extract beats
        if ($type === 'skit' && isset($game['beats'])) {
            extractBeats($lines, $stats, $game['beats'], $dest, 'skit', $cefr);
        }

        // Listening games - extract audio field from items
        if ($type === 'listening' && isset($game['items'])) {
            foreach ($game['items'] as $item) {
                if (isset($item['audio'])) {
                    addLine($lines, $stats, $dest, 'listening', 'narrator', $item['audio'], $cefr);
                }
            }
        }

        // Dictation games - extract audio or sentence field
        if ($type === 'dictation' && isset($game['items'])) {
            foreach ($game['items'] as $item) {
                $text = $item['audio'] ?? $item['sentence'] ?? '';
                if ($text) {
                    addLine($lines, $stats, $dest, 'dictation', 'narrator', $text, $cefr);
                }
            }
        }

        // Conversation games - extract prompt from items
        if ($type === 'conversation' && isset($game['items'])) {
            foreach ($game['items'] as $item) {
                if (isset($item['prompt'])) {
                    addLine($lines, $stats, $dest, 'conversation', 'narrator', $item['prompt'], $cefr);
                }
            }
        }
    }
}

// Process all 89 destinations
for ($d = 1; $d <= 89; $d++) {
    $file = "$contentDir/dest{$d}.json";
    if (!file_exists($file)) {
        echo "SKIP: dest{$d}.json not found\n";
        continue;
    }

    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if (!$data) {
        echo "ERROR: dest{$d}.json invalid JSON\n";
        continue;
    }

    $cefr = $data['meta']['cefr'] ?? '';
    $title = $data['meta']['title'] ?? "Dest $d";
    $stats['byDest'][$d]['title'] = $title;
    $stats['byDest'][$d]['cefr'] = $cefr;

    // 1. Arrival beats
    if (isset($data['arrival']['beats'])) {
        extractBeats($lines, $stats, $data['arrival']['beats'], $d, 'arrival', $cefr);
    }

    // 2. Departure — yaguaraLine
    if (isset($data['departure']['yaguaraLine'])) {
        addLine($lines, $stats, $d, 'departure', 'yaguara', $data['departure']['yaguaraLine'], $cefr);
    }

    // 3. Core games
    if (isset($data['games'])) {
        extractGames($lines, $stats, $data['games'], $d, $cefr);
    }

    // 4. Ecosystem games
    if (isset($data['ecosystemGames'])) {
        extractGames($lines, $stats, $data['ecosystemGames'], $d, $cefr);
    }

    // Also check eval files
    $evalFile = "$contentDir/dest{$d}-eval.json";
    if (file_exists($evalFile)) {
        $evalData = json_decode(file_get_contents($evalFile), true);
        if ($evalData) {
            // Eval files have sections with games arrays
            if (isset($evalData['sections'])) {
                foreach ($evalData['sections'] as $section) {
                    if (isset($section['games'])) {
                        extractGames($lines, $stats, $section['games'], $d, $cefr);
                    }
                }
            }
            if (isset($evalData['games'])) {
                extractGames($lines, $stats, $evalData['games'], $d, $cefr);
            }
        }
    }

    echo "Processed dest{$d}: " . ($stats['byDest'][$d]['count'] ?? 0) . " lines\n";
}

// Sort stats
arsort($stats['bySpeaker']);
ksort($stats['byDest']);

// Write JSON
$jsonOut = [];
foreach ($lines as $l) {
    $jsonOut[] = [
        'dest' => $l['dest'],
        'type' => $l['type'],
        'speaker' => $l['speaker'],
        'text' => $l['text'],
        'duration_est' => $l['duration_est']
    ];
}
file_put_contents("$contentDir/audio-script.json", json_encode($jsonOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nWrote audio-script.json: " . count($jsonOut) . " lines\n";

// Audio manager files list
$audioFiles = [
    ['world-abajo-a1', 'world-abajo-a1.mp3', 'World theme loop — Mundo de Abajo A1 (mysterious, introductory)'],
    ['world-abajo-a2', 'world-abajo-a2.mp3', 'World theme loop — Mundo de Abajo A2 (growing confidence)'],
    ['world-medio-b1', 'world-medio-b1.mp3', 'World theme loop — Mundo del Medio B1 (adventure, complexity)'],
    ['world-medio-b2', 'world-medio-b2.mp3', 'World theme loop — Mundo del Medio B2 (tension, depth)'],
    ['world-arriba-c1', 'world-arriba-c1.mp3', 'World theme loop — Mundo de Arriba C1 (mastery, elevation)'],
    ['world-arriba-c2', 'world-arriba-c2.mp3', 'World theme loop — Mundo de Arriba C2 (transcendence)'],
    ['eco-bosque', 'eco-bosque-ambient.mp3', 'Ecosystem ambient — Tropical forest (birds, insects, rustling)'],
    ['eco-costa', 'eco-costa-ambient.mp3', 'Ecosystem ambient — Coast (waves, seabirds, wind)'],
    ['eco-desierto', 'eco-desierto-ambient.mp3', 'Ecosystem ambient — Desert (dry wind, silence, distant hawk)'],
    ['eco-islas', 'eco-islas-ambient.mp3', 'Ecosystem ambient — Islands (gentle surf, tropical birds)'],
    ['eco-llanos', 'eco-llanos-ambient.mp3', 'Ecosystem ambient — Plains (wind through grass, cattle, harps)'],
    ['eco-nevada', 'eco-nevada-ambient.mp3', 'Ecosystem ambient — Snow peaks (wind, ice, sparse birds)'],
    ['eco-selva', 'eco-selva-ambient.mp3', 'Ecosystem ambient — Jungle (dense canopy, rain, monkeys)'],
    ['eco-sierra', 'eco-sierra-ambient.mp3', 'Ecosystem ambient — Mountains (wind, condor, streams)'],
    ['escape-heartbeat', 'escape-heartbeat-loop.mp3', 'Escape room ambience — Heartbeat tension loop'],
    ['escape-river', 'escape-river-loop.mp3', 'Escape room ambience — River flow loop'],
    ['escape-wind', 'escape-wind-loop.mp3', 'Escape room ambience — Wind howl loop'],
    ['escape-solved-abajo', 'escape-solved-abajo.mp3', 'Escape room sting — Puzzle solved (Abajo)'],
    ['escape-solved-medio', 'escape-solved-medio.mp3', 'Escape room sting — Puzzle solved (Medio)'],
    ['escape-solved-arriba', 'escape-solved-arriba.mp3', 'Escape room sting — Puzzle solved (Arriba)'],
    ['escape-complete-abajo', 'escape-complete-abajo.mp3', 'Escape room sting — Room complete (Abajo)'],
    ['escape-complete-medio', 'escape-complete-medio.mp3', 'Escape room sting — Room complete (Medio)'],
    ['escape-complete-arriba', 'escape-complete-arriba.mp3', 'Escape room sting — Room complete (Arriba)'],
    ['char-yaguara-1', 'char-yaguara-sonadora.mp3', 'Character leitmotif — Yaguara phase 1 (dreamer, curious)'],
    ['char-yaguara-2', 'char-yaguara-caminante.mp3', 'Character leitmotif — Yaguara phase 2 (traveler, determined)'],
    ['char-yaguara-3', 'char-yaguara-guardiana.mp3', 'Character leitmotif — Yaguara phase 3 (guardian, wise)'],
    ['char-candelaria', 'char-candelaria.mp3', 'Character leitmotif — Candelaria (warmth, handwriting sounds)'],
    ['char-prospero', 'char-prospero.mp3', 'Character leitmotif — Don Prospero (dark, industrial, dissonant)'],
    ['char-ceiba', 'char-ceiba.mp3', 'Character leitmotif — Ceiba (deep roots, ancient, resonant)'],
    ['char-asuncion', 'char-asuncion.mp3', 'Character leitmotif — Dona Asuncion (elder wisdom, warmth)'],
    ['char-rana-silent', null, 'Character — Rana (silence — no file needed)'],
    ['char-rana-note', 'char-rana-note.mp3', 'Character — Rana first note (single hopeful tone)'],
    ['char-rana-song', 'char-rana-song.mp3', 'Character — Rana full song (the naming ceremony)'],
    ['sfx-correct-01', 'sfx-correct-01.mp3', 'Gameplay SFX — Correct answer variant 1'],
    ['sfx-correct-02', 'sfx-correct-02.mp3', 'Gameplay SFX — Correct answer variant 2'],
    ['sfx-correct-03', 'sfx-correct-03.mp3', 'Gameplay SFX — Correct answer variant 3'],
    ['sfx-incorrect-01', 'sfx-incorrect-01.mp3', 'Gameplay SFX — Incorrect answer variant 1'],
    ['sfx-incorrect-02', 'sfx-incorrect-02.mp3', 'Gameplay SFX — Incorrect answer variant 2'],
    ['sfx-incorrect-03', 'sfx-incorrect-03.mp3', 'Gameplay SFX — Incorrect answer variant 3'],
    ['sfx-encounter-start', 'sfx-encounter-start.mp3', 'Gameplay SFX — Encounter/game begin'],
    ['sfx-phase-transition', 'sfx-phase-transition.mp3', 'Gameplay SFX — Phase transition whoosh'],
    ['sfx-drag-pick', 'sfx-drag-pick.mp3', 'Gameplay SFX — Drag pick up'],
    ['sfx-drag-drop', 'sfx-drag-drop.mp3', 'Gameplay SFX — Drag drop/place'],
    ['sfx-voice-start', 'sfx-voice-start.mp3', 'Gameplay SFX — Voice recording start'],
    ['sfx-voice-end', 'sfx-voice-end.mp3', 'Gameplay SFX — Voice recording end'],
    ['sfx-pair-match', 'sfx-pair-match.mp3', 'Gameplay SFX — Pair match success'],
    ['sting-complete-abajo', 'sting-complete-abajo.mp3', 'Narrative sting — Destination complete (Abajo)'],
    ['sting-complete-medio', 'sting-complete-medio.mp3', 'Narrative sting — Destination complete (Medio)'],
    ['sting-complete-arriba', 'sting-complete-arriba.mp3', 'Narrative sting — Destination complete (Arriba)'],
    ['sting-awakening', 'sting-awakening.mp3', 'Narrative sting — The Awakening (dest1 opening)'],
    ['sting-candelaria', 'sting-candelaria-appears.mp3', 'Narrative sting — Candelaria first appearance'],
    ['sting-prospero', 'sting-prospero-arrives.mp3', 'Narrative sting — Don Prospero arrives (menacing)'],
    ['sting-grey-place', 'sting-grey-place.mp3', 'Narrative sting — Grey/silence place encountered'],
    ['sting-music-returns', 'sting-music-returns.mp3', 'Narrative sting — Music/color returns after grey'],
    ['sting-portal-pombo', 'sting-portal-pombo.mp3', 'Narrative sting — Portal to Pombo literary world'],
    ['sting-portal-rivera', 'sting-portal-rivera.mp3', 'Narrative sting — Portal to Rivera literary world'],
    ['sting-portal-carrasquilla', 'sting-portal-carrasquilla.mp3', 'Narrative sting — Portal to Carrasquilla literary world'],
    ['sting-portal-macias', 'sting-portal-macias.mp3', 'Narrative sting — Portal to Macias literary world'],
    ['quest-riddle-appears', 'quest-riddle-appears.mp3', 'Riddle quest — Riddle appears (mysterious shimmer)'],
    ['quest-bridge-plank', 'quest-bridge-plank.mp3', 'Riddle quest — Bridge plank placed (wooden thunk)'],
    ['quest-rana-shimmer', 'quest-rana-shimmer.mp3', 'Riddle quest — Rana visibility shimmer'],
    ['quest-cuaderno-open', 'quest-cuaderno-open.mp3', 'Riddle quest — Cuaderno/journal opens'],
    ['finale-chord', 'finale-58-chord.mp3', 'Finale — Grand resolution chord (dest58/89)'],
    ['finale-rana-first', 'finale-rana-first-sound.mp3', 'Finale — Rana speaks for first and only time'],
    ['finale-naming', 'finale-naming-ceremony.mp3', 'Finale — The naming ceremony (all characters)'],
];

// Speaker display names
$speakerNames = [
    'narrator' => 'Narrator',
    'yaguara' => 'Yaguara',
    'ceiba' => 'Ceiba',
    'candelaria' => 'Candelaria',
    'rio' => 'Rio',
    'mama_jaguar' => 'Mama Jaguar',
    'don_prospero' => 'Don Prospero',
    'dona_asuncion' => 'Dona Asuncion',
    'colibri' => 'Colibri',
    'condor_viejo' => 'Condor Viejo',
    'delfin_rosado' => 'Delfin Rosado',
    'maestro' => 'El Maestro',
    'sombra' => 'La Sombra',
    'rana' => 'La Rana',
    'pombo' => 'Rafael Pombo',
    'rivera' => 'Jose E. Rivera',
    'carrasquilla' => 'Tomas Carrasquilla',
    'macias' => 'Jairo A. Macias',
    'viejecita' => 'La Viejecita',
    'rinrin' => 'Rinrin Renacuajo',
    'abuela_jaguar' => 'Abuela Jaguar',
    'arbol' => 'El Arbol',
    'serpiente' => 'La Serpiente',
    'loro' => 'El Loro',
];

$typeNames = [
    'arrival' => 'Arrival',
    'departure' => 'Departure',
    'skit' => 'Skit',
    'listening' => 'Listening',
    'dictation' => 'Dictation',
    'conversation' => 'Conversation',
];

// Sort speakers by count descending
uasort($stats['bySpeaker'], function($a, $b) { return ($b['count'] ?? 0) - ($a['count'] ?? 0); });

// CEFR color map
$cefrColors = [
    'A1' => '#4CAF50',
    'A2' => '#8BC34A',
    'B1' => '#FF9800',
    'B2' => '#FF5722',
    'C1' => '#9C27B0',
    'C2' => '#E91E63',
];

// Build HTML
$totalMinutes = round($stats['totalDuration'] / 60, 1);
$totalHours = round($stats['totalDuration'] / 3600, 1);
$uniqueSpeakers = count($stats['bySpeaker']);
$now = date('Y-m-d H:i');

// CEFR breakdown
$cefrStats = [];
foreach ($lines as $l) {
    $c = $l['cefr'] ?: 'unknown';
    @$cefrStats[$c]['count']++;
    @$cefrStats[$c]['duration'] += $l['duration_est'];
}

$html = <<<'HTMLHEAD'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audio Manifest — El Viaje del Jaguar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #0a0a0f;
    color: #e8e4dc;
    line-height: 1.6;
    padding: 0;
}
body::before { display: none !important; }
body::after { display: none !important; }

.container { max-width: 1600px; margin: 0 auto; padding: 2rem; }

h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.4rem;
    color: #d4a843;
    margin-bottom: 0.5rem;
}
h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    color: #d4a843;
    margin: 2.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(212,168,67,0.2);
}
h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    color: #f0d68a;
    margin: 1.5rem 0 0.8rem;
}
.subtitle { color: #999; font-size: 0.95rem; margin-bottom: 2rem; }
.generated { color: #666; font-size: 0.8rem; margin-bottom: 2rem; }

/* Stats cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: rgba(212,168,67,0.06);
    border: 1px solid rgba(212,168,67,0.15);
    border-radius: 12px;
    padding: 1.2rem;
    text-align: center;
}
.stat-card .number {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    color: #d4a843;
    font-weight: 700;
}
.stat-card .label {
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.3rem;
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0 2rem;
    font-size: 0.85rem;
}
th {
    background: rgba(212,168,67,0.1);
    color: #d4a843;
    padding: 0.7rem 0.8rem;
    text-align: left;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
th:hover { background: rgba(212,168,67,0.2); }
td {
    padding: 0.5rem 0.8rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    vertical-align: top;
}
tr:hover td { background: rgba(212,168,67,0.03); }
.text-col { max-width: 500px; word-wrap: break-word; }

/* Tags */
.tag {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.tag-arrival { background: rgba(76,175,80,0.15); color: #4CAF50; }
.tag-departure { background: rgba(255,152,0,0.15); color: #FF9800; }
.tag-skit { background: rgba(33,150,243,0.15); color: #2196F3; }
.tag-listening { background: rgba(156,39,176,0.15); color: #CE93D8; }
.tag-dictation { background: rgba(0,188,212,0.15); color: #00BCD4; }
.tag-conversation { background: rgba(233,30,99,0.15); color: #E91E63; }

.cefr-tag {
    display: inline-block;
    padding: 0.15rem 0.4rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
}

/* Filter bar */
.filter-bar {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 1rem 0;
    align-items: center;
}
.filter-bar label {
    font-size: 0.85rem;
    color: #999;
}
.filter-bar select, .filter-bar input {
    background: #1a1a24;
    color: #e8e4dc;
    border: 1px solid rgba(212,168,67,0.2);
    border-radius: 6px;
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
    font-family: inherit;
}
.filter-bar input { min-width: 200px; }
.filter-bar select:focus, .filter-bar input:focus {
    outline: none;
    border-color: #d4a843;
}
.count-display {
    color: #d4a843;
    font-size: 0.85rem;
    margin-left: auto;
}

/* Audio files section */
.audio-file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 0.5rem;
    margin: 1rem 0;
}
.audio-file-item {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
    display: flex;
    gap: 0.8rem;
    align-items: center;
}
.audio-file-item .af-name {
    color: #d4a843;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    min-width: 180px;
}
.audio-file-item .af-desc {
    color: #bbb;
    font-size: 0.8rem;
}
.audio-file-item .af-null {
    color: #666;
    font-style: italic;
}

/* Dest breakdown table */
.dest-row td:first-child { font-weight: 600; color: #d4a843; }
.mini-bar {
    height: 6px;
    background: rgba(212,168,67,0.15);
    border-radius: 3px;
    overflow: hidden;
    min-width: 100px;
}
.mini-bar-fill {
    height: 100%;
    background: #d4a843;
    border-radius: 3px;
}

/* Print */
@media print {
    body { background: #fff; color: #222; }
    .filter-bar { display: none; }
    th { background: #f0f0f0; color: #333; }
    .stat-card { border-color: #ccc; }
    .stat-card .number { color: #333; }
}

/* Responsive */
@media (max-width: 768px) {
    .container { padding: 1rem; }
    h1 { font-size: 1.6rem; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .audio-file-grid { grid-template-columns: 1fr; }
    table { font-size: 0.75rem; }
}
</style>
</head>
<body>
<div class="container">
HTMLHEAD;

$html .= "<h1>Audio Manifest</h1>\n";
$html .= "<p class=\"subtitle\">El Viaje del Jaguar — Complete voice recording script for all 89 destinations</p>\n";
$html .= "<p class=\"generated\">Generated: $now | Source: content/dest1.json through dest89.json + eval files</p>\n";

// Summary stats
$html .= "<div class=\"stats-grid\">\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">" . number_format($stats['total']) . "</div><div class=\"label\">Total voiced lines</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">" . number_format($totalMinutes, 0) . "</div><div class=\"label\">Est. minutes</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">" . number_format($totalHours, 1) . "</div><div class=\"label\">Est. hours</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">$uniqueSpeakers</div><div class=\"label\">Unique speakers</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">" . count($stats['byType']) . "</div><div class=\"label\">Game types</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">89</div><div class=\"label\">Destinations</div></div>\n";
$html .= "<div class=\"stat-card\"><div class=\"number\">65</div><div class=\"label\">Audio files (mapped)</div></div>\n";
$html .= "</div>\n";

// By speaker
$html .= "<h2>Lines by speaker</h2>\n";
$html .= "<table><tr><th>Speaker</th><th>Lines</th><th>Est. minutes</th><th>% of total</th><th>Distribution</th></tr>\n";
foreach ($stats['bySpeaker'] as $spk => $s) {
    $name = $speakerNames[$spk] ?? ucfirst(str_replace('_', ' ', $spk));
    $mins = round(($s['duration'] ?? 0) / 60, 1);
    $pct = round(($s['count'] / $stats['total']) * 100, 1);
    $barW = round($pct * 3, 1); // scale
    $html .= "<tr><td><strong>$name</strong></td><td>" . number_format($s['count']) . "</td><td>$mins</td><td>{$pct}%</td>";
    $html .= "<td><div class=\"mini-bar\"><div class=\"mini-bar-fill\" style=\"width:{$barW}px\"></div></div></td></tr>\n";
}
$html .= "</table>\n";

// By type
$html .= "<h2>Lines by game type</h2>\n";
$html .= "<table><tr><th>Type</th><th>Lines</th><th>Est. minutes</th><th>% of total</th></tr>\n";
// Sort by count desc
uasort($stats['byType'], function($a, $b) { return ($b['count'] ?? 0) - ($a['count'] ?? 0); });
foreach ($stats['byType'] as $type => $s) {
    $tname = $typeNames[$type] ?? ucfirst($type);
    $mins = round(($s['duration'] ?? 0) / 60, 1);
    $pct = round(($s['count'] / $stats['total']) * 100, 1);
    $html .= "<tr><td><span class=\"tag tag-$type\">$tname</span></td><td>" . number_format($s['count']) . "</td><td>$mins</td><td>{$pct}%</td></tr>\n";
}
$html .= "</table>\n";

// By CEFR level
$html .= "<h2>Lines by CEFR level</h2>\n";
$html .= "<table><tr><th>Level</th><th>Lines</th><th>Est. minutes</th><th>% of total</th></tr>\n";
ksort($cefrStats);
foreach ($cefrStats as $c => $s) {
    $color = $cefrColors[$c] ?? '#999';
    $mins = round($s['duration'] / 60, 1);
    $pct = round(($s['count'] / $stats['total']) * 100, 1);
    $html .= "<tr><td><span class=\"cefr-tag\" style=\"background:rgba(" . implode(',', sscanf($color, '#%02x%02x%02x')) . ",0.15);color:$color\">$c</span></td>";
    $html .= "<td>" . number_format($s['count']) . "</td><td>$mins</td><td>{$pct}%</td></tr>\n";
}
$html .= "</table>\n";

// By destination
$html .= "<h2>Lines by destination</h2>\n";
$html .= "<table><tr><th>Dest</th><th>Title</th><th>CEFR</th><th>Lines</th><th>Est. min</th></tr>\n";
for ($d = 1; $d <= 89; $d++) {
    $ds = $stats['byDest'][$d] ?? ['count' => 0, 'duration' => 0, 'title' => '???', 'cefr' => ''];
    $cefr = $ds['cefr'] ?? '';
    $color = $cefrColors[$cefr] ?? '#999';
    $mins = round(($ds['duration'] ?? 0) / 60, 1);
    $html .= "<tr class=\"dest-row\"><td>$d</td><td>" . htmlspecialchars($ds['title'] ?? '') . "</td>";
    $html .= "<td><span class=\"cefr-tag\" style=\"background:rgba(" . implode(',', sscanf($color, '#%02x%02x%02x') ?: [153,153,153]) . ",0.15);color:$color\">$cefr</span></td>";
    $html .= "<td>" . number_format($ds['count'] ?? 0) . "</td><td>$mins</td></tr>\n";
}
$html .= "</table>\n";

// Audio manager files
$html .= "<h2>Audio Manager — 65 mapped files</h2>\n";
$html .= "<p style=\"color:#999;font-size:0.85rem;margin-bottom:1rem;\">These are the music/SFX files defined in <code>js/audio-manager.js</code>. Separate from voice lines below.</p>\n";
$html .= "<div class=\"audio-file-grid\">\n";
foreach ($audioFiles as $af) {
    $html .= "<div class=\"audio-file-item\">";
    $html .= "<span class=\"af-name\">" . htmlspecialchars($af[0]) . "</span>";
    if ($af[1]) {
        $html .= "<span class=\"af-desc\">" . htmlspecialchars($af[2]) . "</span>";
    } else {
        $html .= "<span class=\"af-null\">" . htmlspecialchars($af[2]) . "</span>";
    }
    $html .= "</div>\n";
}
$html .= "</div>\n";

// Main voice lines table with filters
$html .= "<h2>All voiced lines</h2>\n";
$html .= <<<'FILTERHTML'
<div class="filter-bar">
    <label>Dest: <select id="fDest"><option value="">All</option></select></label>
    <label>Type: <select id="fType"><option value="">All</option></select></label>
    <label>Speaker: <select id="fSpeaker"><option value="">All</option></select></label>
    <label>Search: <input type="text" id="fSearch" placeholder="Search text..."></label>
    <span class="count-display" id="fCount"></span>
</div>
FILTERHTML;

$html .= "<table id=\"linesTable\"><thead><tr><th>Dest</th><th>CEFR</th><th>Type</th><th>Speaker</th><th class=\"text-col\">Text</th><th>Words</th><th>Est. sec</th></tr></thead>\n<tbody>\n";

foreach ($lines as $i => $l) {
    $cefr = $l['cefr'] ?: '';
    $color = $cefrColors[$cefr] ?? '#999';
    $spkName = $speakerNames[$l['speaker']] ?? ucfirst(str_replace('_', ' ', $l['speaker']));
    $type = $l['type'];
    $tname = $typeNames[$type] ?? ucfirst($type);
    $text = htmlspecialchars($l['text']);

    $html .= "<tr data-dest=\"{$l['dest']}\" data-type=\"$type\" data-speaker=\"{$l['speaker']}\">";
    $html .= "<td>{$l['dest']}</td>";
    if ($cefr) {
        $rgb = sscanf($color, '#%02x%02x%02x') ?: [153,153,153];
        $html .= "<td><span class=\"cefr-tag\" style=\"background:rgba(" . implode(',', $rgb) . ",0.15);color:$color\">$cefr</span></td>";
    } else {
        $html .= "<td></td>";
    }
    $html .= "<td><span class=\"tag tag-$type\">$tname</span></td>";
    $html .= "<td>$spkName</td>";
    $html .= "<td class=\"text-col\">$text</td>";
    $html .= "<td>{$l['words']}</td>";
    $html .= "<td>{$l['duration_est']}</td>";
    $html .= "</tr>\n";
}

$html .= "</tbody></table>\n";

// JavaScript for filtering and sorting
$html .= <<<'JSBLOCK'
<script>
(function() {
    var table = document.getElementById('linesTable');
    var tbody = table.querySelector('tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));
    var fDest = document.getElementById('fDest');
    var fType = document.getElementById('fType');
    var fSpeaker = document.getElementById('fSpeaker');
    var fSearch = document.getElementById('fSearch');
    var fCount = document.getElementById('fCount');

    // Populate filter options
    var dests = new Set(), types = new Set(), speakers = new Set();
    rows.forEach(function(r) {
        dests.add(r.dataset.dest);
        types.add(r.dataset.type);
        speakers.add(r.dataset.speaker);
    });
    Array.from(dests).sort(function(a,b){return a-b}).forEach(function(d) {
        fDest.innerHTML += '<option value="'+d+'">Dest '+d+'</option>';
    });
    Array.from(types).sort().forEach(function(t) {
        fType.innerHTML += '<option value="'+t+'">'+t.charAt(0).toUpperCase()+t.slice(1)+'</option>';
    });
    Array.from(speakers).sort().forEach(function(s) {
        fSpeaker.innerHTML += '<option value="'+s+'">'+s.replace(/_/g,' ')+'</option>';
    });

    function applyFilters() {
        var d = fDest.value, t = fType.value, s = fSpeaker.value;
        var q = fSearch.value.toLowerCase();
        var shown = 0;
        rows.forEach(function(r) {
            var show = true;
            if (d && r.dataset.dest !== d) show = false;
            if (t && r.dataset.type !== t) show = false;
            if (s && r.dataset.speaker !== s) show = false;
            if (q && r.textContent.toLowerCase().indexOf(q) === -1) show = false;
            r.style.display = show ? '' : 'none';
            if (show) shown++;
        });
        fCount.textContent = shown + ' of ' + rows.length + ' lines';
    }

    fDest.addEventListener('change', applyFilters);
    fType.addEventListener('change', applyFilters);
    fSpeaker.addEventListener('change', applyFilters);
    fSearch.addEventListener('input', applyFilters);
    applyFilters();

    // Column sorting
    var headers = table.querySelectorAll('th');
    headers.forEach(function(th, idx) {
        th.addEventListener('click', function() {
            var asc = th.dataset.sort !== 'asc';
            headers.forEach(function(h) { h.dataset.sort = ''; });
            th.dataset.sort = asc ? 'asc' : 'desc';
            rows.sort(function(a, b) {
                var av = a.children[idx].textContent;
                var bv = b.children[idx].textContent;
                var an = parseFloat(av), bn = parseFloat(bv);
                if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
                return asc ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            rows.forEach(function(r) { tbody.appendChild(r); });
        });
    });
})();
</script>
JSBLOCK;

$html .= "</div>\n</body>\n</html>";

file_put_contents("$baseDir/audio-manifest.html", $html);
echo "Wrote audio-manifest.html: " . strlen($html) . " bytes\n";
echo "\nDone! Total: {$stats['total']} lines, ~{$totalMinutes} minutes ({$totalHours} hours)\n";
