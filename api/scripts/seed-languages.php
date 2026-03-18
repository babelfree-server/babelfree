<?php
/**
 * Seed 105 languages into dict_languages table.
 * Hard-coded array of all platform languages (104 non-Spanish + Spanish).
 *
 * Idempotent: uses INSERT IGNORE on unique `code` column.
 *
 * Run: php seed-languages.php [--dry-run]
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$dryRun = in_array('--dry-run', $argv ?? []);

echo "=== Seeding Languages ===\n";
echo "  Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . "\n\n";

// All 105 platform languages: [code, name_en, name_native]
$languages = [
    ['af', 'Afrikaans', 'Afrikaans'],
    ['am', 'Amharic', 'አማርኛ'],
    ['ar', 'Arabic', 'العربية'],
    ['az', 'Azerbaijani', 'Azərbaycanca'],
    ['be', 'Belarusian', 'Беларуская'],
    ['bg', 'Bulgarian', 'Български'],
    ['bn', 'Bengali', 'বাংলা'],
    ['bo', 'Tibetan', 'བོད་སྐད་'],
    ['bs', 'Bosnian', 'Bosanski'],
    ['ca', 'Catalan', 'Català'],
    ['cs', 'Czech', 'Čeština'],
    ['cy', 'Welsh', 'Cymraeg'],
    ['da', 'Danish', 'Dansk'],
    ['de', 'German', 'Deutsch'],
    ['el', 'Greek', 'Ελληνικά'],
    ['en', 'English', 'English'],
    ['eo', 'Esperanto', 'Esperanto'],
    ['es', 'Spanish', 'Español'],
    ['et', 'Estonian', 'Eesti'],
    ['eu', 'Basque', 'Euskara'],
    ['fa', 'Persian', 'فارسی'],
    ['fi', 'Finnish', 'Suomi'],
    ['fj', 'Fijian', 'Vosa Vakaviti'],
    ['fr', 'French', 'Français'],
    ['ga', 'Irish', 'Gaeilge'],
    ['gd', 'Scots Gaelic', 'Gàidhlig'],
    ['gl', 'Galician', 'Galego'],
    ['gu', 'Gujarati', 'ગુજરાતી'],
    ['ha', 'Hausa', 'Hausa'],
    ['haw', 'Hawaiian', 'ʻŌlelo Hawaiʻi'],
    ['he', 'Hebrew', 'עברית'],
    ['hi', 'Hindi', 'हिन्दी'],
    ['hr', 'Croatian', 'Hrvatski'],
    ['hu', 'Hungarian', 'Magyar'],
    ['hy', 'Armenian', 'Հայերեն'],
    ['id', 'Indonesian', 'Bahasa Indonesia'],
    ['ig', 'Igbo', 'Igbo'],
    ['is', 'Icelandic', 'Íslenska'],
    ['it', 'Italian', 'Italiano'],
    ['ja', 'Japanese', '日本語'],
    ['ka', 'Georgian', 'ქართული'],
    ['kk', 'Kazakh', 'Қазақ'],
    ['km', 'Khmer', 'ភាសាខ្មែរ'],
    ['kn', 'Kannada', 'ಕನ್ನಡ'],
    ['ko', 'Korean', '한국어'],
    ['ku', 'Kurdish', 'Kurdî'],
    ['ky', 'Kyrgyz', 'Кыргызча'],
    ['la', 'Latin', 'Latina'],
    ['lb', 'Luxembourgish', 'Lëtzebuergesch'],
    ['ln', 'Lingala', 'Lingála'],
    ['lo', 'Lao', 'ລາວ'],
    ['lt', 'Lithuanian', 'Lietuvių'],
    ['lv', 'Latvian', 'Latviešu'],
    ['mg', 'Malagasy', 'Malagasy'],
    ['mi', 'Maori', 'Te Reo Māori'],
    ['mk', 'Macedonian', 'Македонски'],
    ['ml', 'Malayalam', 'മലയാളം'],
    ['mn', 'Mongolian', 'Монгол'],
    ['mr', 'Marathi', 'मराठी'],
    ['ms', 'Malay', 'Bahasa Melayu'],
    ['mt', 'Maltese', 'Malti'],
    ['my', 'Burmese', 'မြန်မာဘာသာ'],
    ['ne', 'Nepali', 'नेपाली'],
    ['nl', 'Dutch', 'Nederlands'],
    ['no', 'Norwegian', 'Norsk'],
    ['or', 'Odia', 'ଓଡ଼ିଆ'],
    ['pa', 'Punjabi', 'ਪੰਜਾਬੀ'],
    ['pl', 'Polish', 'Polski'],
    ['ps', 'Pashto', 'پښتو'],
    ['pt', 'Portuguese', 'Português'],
    ['ro', 'Romanian', 'Română'],
    ['ru', 'Russian', 'Русский'],
    ['rw', 'Kinyarwanda', 'Kinyarwanda'],
    ['sd', 'Sindhi', 'سنڌي'],
    ['si', 'Sinhala', 'සිංහල'],
    ['sk', 'Slovak', 'Slovenčina'],
    ['sl', 'Slovenian', 'Slovenščina'],
    ['sm', 'Samoan', 'Gagana Samoa'],
    ['sn', 'Shona', 'chiShona'],
    ['so', 'Somali', 'Soomaali'],
    ['sq', 'Albanian', 'Shqip'],
    ['sr', 'Serbian', 'Српски'],
    ['st', 'Sesotho', 'Sesotho'],
    ['sv', 'Swedish', 'Svenska'],
    ['sw', 'Swahili', 'Kiswahili'],
    ['ta', 'Tamil', 'தமிழ்'],
    ['te', 'Telugu', 'తెలుగు'],
    ['tg', 'Tajik', 'Тоҷикӣ'],
    ['th', 'Thai', 'ไทย'],
    ['ti', 'Tigrinya', 'ትግርኛ'],
    ['tk', 'Turkmen', 'Türkmen'],
    ['tl', 'Filipino', 'Filipino'],
    ['tn', 'Tswana', 'Setswana'],
    ['tr', 'Turkish', 'Türkçe'],
    ['ug', 'Uyghur', 'ئۇيغۇرچە'],
    ['uk', 'Ukrainian', 'Українська'],
    ['ur', 'Urdu', 'اردو'],
    ['uz', 'Uzbek', 'Oʻzbek'],
    ['vi', 'Vietnamese', 'Tiếng Việt'],
    ['wo', 'Wolof', 'Wolof'],
    ['xh', 'Xhosa', 'isiXhosa'],
    ['yo', 'Yoruba', 'Yorùbá'],
    ['zh', 'Chinese', '中文'],
    ['zh-tw', 'Chinese Traditional', '繁體中文'],
    ['zu', 'Zulu', 'isiZulu'],
];

echo "  Languages in array: " . count($languages) . "\n\n";

$insert = $pdo->prepare(
    'INSERT IGNORE INTO dict_languages (code, name_en, name_native)
     VALUES (?, ?, ?)'
);

$inserted = 0;
$skipped = 0;

foreach ($languages as [$code, $nameEn, $nameNative]) {
    if ($dryRun) {
        echo "  [DRY] $code: $nameEn ($nameNative)\n";
        $inserted++;
        continue;
    }

    $insert->execute([$code, $nameEn, $nameNative]);
    if ($insert->rowCount() > 0) {
        $inserted++;
    } else {
        $skipped++;
    }
}

echo "  Inserted: $inserted\n";
echo "  Skipped (already existed): $skipped\n";

// Verify
$stmt = $pdo->query('SELECT COUNT(*) as c FROM dict_languages');
echo "\nTotal in dict_languages: " . $stmt->fetch()['c'] . "\n";
echo "\nDone.\n";
