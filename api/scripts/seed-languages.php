<?php
/**
 * Seed 104 languages into dict_languages table.
 * Parses lang_data_1.py, lang_data_2.py, lang_data_3.py to extract
 * code, English name, native name, flag emoji, and text direction.
 *
 * Run: php seed-languages.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$basePath = realpath(__DIR__ . '/../../');

echo "=== Seeding 104 Languages ===\n\n";

// Parse all three lang_data files
$langFiles = [
    $basePath . '/lang_data_1.py',
    $basePath . '/lang_data_2.py',
    $basePath . '/lang_data_3.py',
];

$languages = [];

foreach ($langFiles as $file) {
    if (!file_exists($file)) {
        echo "  WARNING: $file not found, skipping\n";
        continue;
    }

    $content = file_get_contents($file);

    // Match Python tuple entries: ("code", "English Name", "Native Name", "Flag", ..., "dir")
    // Format: (code, name_en, name_native, flag, learn_spanish, free_tagline, start_cta, dir)
    preg_match_all(
        '/\(\s*"([^"]+)"\s*,\s*"([^"]+)"\s*,\s*"([^"]+)"\s*,\s*"([^"]+)"\s*,\s*"[^"]*"\s*,\s*"[^"]*"\s*,\s*"[^"]*"\s*,\s*"([^"]+)"\s*\)/u',
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $m) {
        $code = $m[1];
        $nameEn = $m[2];
        // Decode Python unicode escapes in native name
        $nameNative = json_decode('"' . $m[3] . '"') ?: $m[3];
        // Decode flag emoji (Python \UXXXXXXXX format)
        $flag = preg_replace_callback('/\\\\U([0-9a-fA-F]{8})/', function($fm) {
            return mb_chr(hexdec($fm[1]), 'UTF-8');
        }, $m[4]);
        // Also handle \u escapes in flag
        $flag = json_decode('"' . $flag . '"') ?: $flag;
        $dir = $m[5];

        $languages[$code] = [
            'code' => $code,
            'name_en' => $nameEn,
            'name_native' => $nameNative,
            'flag' => $flag,
            'dir' => $dir,
        ];
    }

    echo "  Parsed: " . basename($file) . " (" . count($matches) . " languages)\n";
}

// Add Spanish itself (not in lang_data files since it's the target language)
$languages['es'] = [
    'code' => 'es',
    'name_en' => 'Spanish',
    'name_native' => 'Español',
    'flag' => '🇪🇸',
    'dir' => 'ltr',
];

echo "\n  Total languages found: " . count($languages) . "\n";

// Insert into dict_languages
$pdo->exec('DELETE FROM dict_languages');

$insert = $pdo->prepare(
    'INSERT INTO dict_languages (code, name_native, name_en, flag, text_direction, is_active)
     VALUES (?, ?, ?, ?, ?, 1)'
);

$inserted = 0;
foreach ($languages as $lang) {
    try {
        $insert->execute([
            $lang['code'],
            $lang['name_native'],
            $lang['name_en'],
            $lang['flag'],
            $lang['dir'],
        ]);
        $inserted++;
    } catch (Exception $e) {
        echo "  ERROR inserting {$lang['code']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Inserted $inserted languages ===\n";

// Verify
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_languages');
echo "Total in dict_languages: " . $stmt->fetch()['c'] . "\n";

// Show RTL languages
$stmt = $pdo->query("SELECT code, name_en FROM dict_languages WHERE text_direction = 'rtl' ORDER BY name_en");
$rtl = $stmt->fetchAll();
if ($rtl) {
    echo "\nRTL languages:\n";
    foreach ($rtl as $r) {
        echo "  {$r['code']}: {$r['name_en']}\n";
    }
}
