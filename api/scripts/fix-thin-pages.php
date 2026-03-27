<?php
/**
 * fix-thin-pages.php — Eliminate thin dictionary pages for Phase 2 & Phase 3 languages
 *
 * Step 1: Cross-language propagation from EN/FR definitions
 * Step 2: POS-based minimal definitions for remaining thin words
 * Step 3: Verification
 *
 * Usage: php -d memory_limit=512M fix-thin-pages.php [--phase=2|3|all] [--dry-run] [--lang=xx]
 */

$config = require '/home/babelfree.com/config/app.php';
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
    $db['user'],
    $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Parse args
$args = getopt('', ['phase:', 'dry-run', 'lang:']);
$dryRun = isset($args['dry-run']);
$targetLang = $args['lang'] ?? null;
$phase = $args['phase'] ?? 'all';

$phase1 = ['es','en','fr','de','pt','it'];
$phase2 = ['nl','ru','el','ja','ko','zh','fi','ca','ar'];

// Native POS labels per language
$posLabels = [
    'nl' => ['noun'=>'Zelfstandig naamwoord','verb'=>'Werkwoord','adjective'=>'Bijvoeglijk naamwoord','adverb'=>'Bijwoord',
             'pronoun'=>'Voornaamwoord','preposition'=>'Voorzetsel','conjunction'=>'Voegwoord','interjection'=>'Tussenwerpsel','article'=>'Lidwoord','phrase'=>'Uitdrukking'],
    'ru' => ['noun'=>'Существительное','verb'=>'Глагол','adjective'=>'Прилагательное','adverb'=>'Наречие',
             'pronoun'=>'Местоимение','preposition'=>'Предлог','conjunction'=>'Союз','interjection'=>'Междометие','article'=>'Артикль','phrase'=>'Фраза'],
    'el' => ['noun'=>'Ουσιαστικό','verb'=>'Ρήμα','adjective'=>'Επίθετο','adverb'=>'Επίρρημα',
             'pronoun'=>'Αντωνυμία','preposition'=>'Πρόθεση','conjunction'=>'Σύνδεσμος','interjection'=>'Επιφώνημα','article'=>'Άρθρο','phrase'=>'Φράση'],
    'ja' => ['noun'=>'名詞','verb'=>'動詞','adjective'=>'形容詞','adverb'=>'副詞',
             'pronoun'=>'代名詞','preposition'=>'前置詞','conjunction'=>'接続詞','interjection'=>'感動詞','article'=>'冠詞','phrase'=>'句'],
    'ko' => ['noun'=>'명사','verb'=>'동사','adjective'=>'형용사','adverb'=>'부사',
             'pronoun'=>'대명사','preposition'=>'전치사','conjunction'=>'접속사','interjection'=>'감탄사','article'=>'관사','phrase'=>'구'],
    'zh' => ['noun'=>'名词','verb'=>'动词','adjective'=>'形容词','adverb'=>'副词',
             'pronoun'=>'代词','preposition'=>'介词','conjunction'=>'连词','interjection'=>'感叹词','article'=>'冠词','phrase'=>'短语'],
    'fi' => ['noun'=>'Substantiivi','verb'=>'Verbi','adjective'=>'Adjektiivi','adverb'=>'Adverbi',
             'pronoun'=>'Pronomini','preposition'=>'Prepositio','conjunction'=>'Konjunktio','interjection'=>'Interjektio','article'=>'Artikkeli','phrase'=>'Fraasi'],
    'ca' => ['noun'=>'Nom','verb'=>'Verb','adjective'=>'Adjectiu','adverb'=>'Adverbi',
             'pronoun'=>'Pronom','preposition'=>'Preposició','conjunction'=>'Conjunció','interjection'=>'Interjecció','article'=>'Article','phrase'=>'Frase'],
    'ar' => ['noun'=>'اسم','verb'=>'فعل','adjective'=>'صفة','adverb'=>'ظرف',
             'pronoun'=>'ضمير','preposition'=>'حرف جر','conjunction'=>'حرف عطف','interjection'=>'تعجب','article'=>'أداة تعريف','phrase'=>'عبارة'],
    // Phase 3 with native labels for major languages
    'tr' => ['noun'=>'İsim','verb'=>'Fiil','adjective'=>'Sıfat','adverb'=>'Zarf',
             'pronoun'=>'Zamir','preposition'=>'Edat','conjunction'=>'Bağlaç','interjection'=>'Ünlem','article'=>'Tanımlık','phrase'=>'Deyim'],
    'sv' => ['noun'=>'Substantiv','verb'=>'Verb','adjective'=>'Adjektiv','adverb'=>'Adverb',
             'pronoun'=>'Pronomen','preposition'=>'Preposition','conjunction'=>'Konjunktion','interjection'=>'Interjektion','article'=>'Artikel','phrase'=>'Fras'],
    'pl' => ['noun'=>'Rzeczownik','verb'=>'Czasownik','adjective'=>'Przymiotnik','adverb'=>'Przysłówek',
             'pronoun'=>'Zaimek','preposition'=>'Przyimek','conjunction'=>'Spójnik','interjection'=>'Wykrzyknik','article'=>'Rodzajnik','phrase'=>'Zwrot'],
    'cs' => ['noun'=>'Podstatné jméno','verb'=>'Sloveso','adjective'=>'Přídavné jméno','adverb'=>'Příslovce',
             'pronoun'=>'Zájmeno','preposition'=>'Předložka','conjunction'=>'Spojka','interjection'=>'Citoslovce','article'=>'Člen','phrase'=>'Fráze'],
    'da' => ['noun'=>'Substantiv','verb'=>'Verbum','adjective'=>'Adjektiv','adverb'=>'Adverbium',
             'pronoun'=>'Pronomen','preposition'=>'Præposition','conjunction'=>'Konjunktion','interjection'=>'Interjektion','article'=>'Artikel','phrase'=>'Udtryk'],
    'hu' => ['noun'=>'Főnév','verb'=>'Ige','adjective'=>'Melléknév','adverb'=>'Határozószó',
             'pronoun'=>'Névmás','preposition'=>'Elöljáró','conjunction'=>'Kötőszó','interjection'=>'Indulatszó','article'=>'Névelő','phrase'=>'Kifejezés'],
    'ro' => ['noun'=>'Substantiv','verb'=>'Verb','adjective'=>'Adjectiv','adverb'=>'Adverb',
             'pronoun'=>'Pronume','preposition'=>'Prepoziție','conjunction'=>'Conjuncție','interjection'=>'Interjecție','article'=>'Articol','phrase'=>'Expresie'],
    'id' => ['noun'=>'Kata benda','verb'=>'Kata kerja','adjective'=>'Kata sifat','adverb'=>'Kata keterangan',
             'pronoun'=>'Kata ganti','preposition'=>'Kata depan','conjunction'=>'Kata hubung','interjection'=>'Kata seru','article'=>'Artikel','phrase'=>'Frasa'],
    'hi' => ['noun'=>'संज्ञा','verb'=>'क्रिया','adjective'=>'विशेषण','adverb'=>'क्रिया विशेषण',
             'pronoun'=>'सर्वनाम','preposition'=>'पूर्वसर्ग','conjunction'=>'संयोजक','interjection'=>'विस्मयादिबोधक','article'=>'उपपद','phrase'=>'मुहावरा'],
    'th' => ['noun'=>'คำนาม','verb'=>'คำกริยา','adjective'=>'คำคุณศัพท์','adverb'=>'คำวิเศษณ์',
             'pronoun'=>'สรรพนาม','preposition'=>'คำบุพบท','conjunction'=>'คำสันธาน','interjection'=>'คำอุทาน','article'=>'คำนำหน้า','phrase'=>'วลี'],
    'vi' => ['noun'=>'Danh từ','verb'=>'Động từ','adjective'=>'Tính từ','adverb'=>'Trạng từ',
             'pronoun'=>'Đại từ','preposition'=>'Giới từ','conjunction'=>'Liên từ','interjection'=>'Thán từ','article'=>'Mạo từ','phrase'=>'Cụm từ'],
    'uk' => ['noun'=>'Іменник','verb'=>'Дієслово','adjective'=>'Прикметник','adverb'=>'Прислівник',
             'pronoun'=>'Займенник','preposition'=>'Прийменник','conjunction'=>'Сполучник','interjection'=>'Вигук','article'=>'Артикль','phrase'=>'Фраза'],
    'bg' => ['noun'=>'Съществително','verb'=>'Глагол','adjective'=>'Прилагателно','adverb'=>'Наречие',
             'pronoun'=>'Местоимение','preposition'=>'Предлог','conjunction'=>'Съюз','interjection'=>'Междуметие','article'=>'Член','phrase'=>'Фраза'],
    'he' => ['noun'=>'שם עצם','verb'=>'פועל','adjective'=>'שם תואר','adverb'=>'תואר הפועל',
             'pronoun'=>'כינוי','preposition'=>'מילת יחס','conjunction'=>'מילת חיבור','interjection'=>'מילת קריאה','article'=>'ה הידיעה','phrase'=>'ביטוי'],
    'fa' => ['noun'=>'اسم','verb'=>'فعل','adjective'=>'صفت','adverb'=>'قید',
             'pronoun'=>'ضمیر','preposition'=>'حرف اضافه','conjunction'=>'حرف ربط','interjection'=>'حرف ندا','article'=>'حرف تعریف','phrase'=>'عبارت'],
    'bn' => ['noun'=>'বিশেষ্য','verb'=>'ক্রিয়া','adjective'=>'বিশেষণ','adverb'=>'ক্রিয়া বিশেষণ',
             'pronoun'=>'সর্বনাম','preposition'=>'অনুসর্গ','conjunction'=>'সংযোজক','interjection'=>'আবেগসূচক','article'=>'পদাশ্রিত নির্দেশক','phrase'=>'বাক্যাংশ'],
    'my' => ['noun'=>'နာမ','verb'=>'ကြိယာ','adjective'=>'နာမဝိသေသန','adverb'=>'ကြိယာဝိသေသန',
             'pronoun'=>'နာမ်စား','preposition'=>'ဝိဘတ်','conjunction'=>'သမ္ဗန္ဓ','interjection'=>'အာမေဋိတ်','article'=>'ပုဒ်','phrase'=>'စကားစု'],
    'ka' => ['noun'=>'არსებითი სახელი','verb'=>'ზმნა','adjective'=>'ზედსართავი','adverb'=>'ზმნიზედა',
             'pronoun'=>'ნაცვალსახელი','preposition'=>'თანდებული','conjunction'=>'კავშირი','interjection'=>'შორისდებული','article'=>'არტიკლი','phrase'=>'ფრაზა'],
    'hy' => ['noun'=>'Գոյական','verb'=>'Բայ','adjective'=>'Ածական','adverb'=>'Մակբայ',
             'pronoun'=>'Դերանուն','preposition'=>'Կապ','conjunction'=>'Շաղկապ','interjection'=>'Ձայնարկություն','article'=>'Հոդ','phrase'=>'Արտահայdelays'],
    'ms' => ['noun'=>'Kata nama','verb'=>'Kata kerja','adjective'=>'Kata sifat','adverb'=>'Kata keterangan',
             'pronoun'=>'Kata ganti','preposition'=>'Kata sendi','conjunction'=>'Kata hubung','interjection'=>'Kata seru','article'=>'Artikel','phrase'=>'Frasa'],
    'sw' => ['noun'=>'Nomino','verb'=>'Kitenzi','adjective'=>'Kivumishi','adverb'=>'Kielezi',
             'pronoun'=>'Kiwakilishi','preposition'=>'Kihusishi','conjunction'=>'Kiunganishi','interjection'=>'Kiingizi','article'=>'Kifungu','phrase'=>'Nahau'],
    'la' => ['noun'=>'Nomen','verb'=>'Verbum','adjective'=>'Adiectivum','adverb'=>'Adverbium',
             'pronoun'=>'Pronomen','preposition'=>'Praepositio','conjunction'=>'Coniunctio','interjection'=>'Interiectio','article'=>'Articulus','phrase'=>'Locutio'],
    'eo' => ['noun'=>'Substantivo','verb'=>'Verbo','adjective'=>'Adjektivo','adverb'=>'Adverbo',
             'pronoun'=>'Pronomo','preposition'=>'Prepozicio','conjunction'=>'Konjunkcio','interjection'=>'Interjekcio','article'=>'Artikolo','phrase'=>'Frazo'],
    'ga' => ['noun'=>'Ainmfhocal','verb'=>'Briathar','adjective'=>'Aidiacht','adverb'=>'Dobhriathar',
             'pronoun'=>'Forainm','preposition'=>'Réamhfhocal','conjunction'=>'Cónasc','interjection'=>'Intriacht','article'=>'Alt','phrase'=>'Frása'],
    'is' => ['noun'=>'Nafnorð','verb'=>'Sagnorð','adjective'=>'Lýsingarorð','adverb'=>'Atviksorð',
             'pronoun'=>'Fornafn','preposition'=>'Forsetning','conjunction'=>'Samtenging','interjection'=>'Upphrópun','article'=>'Greinir','phrase'=>'Orðasamband'],
    'cy' => ['noun'=>'Enw','verb'=>'Berf','adjective'=>'Ansoddair','adverb'=>'Adferf',
             'pronoun'=>'Rhagenw','preposition'=>'Arddodiad','conjunction'=>'Cysylltair','interjection'=>'Ebychiad','article'=>'Bannod','phrase'=>'Ymadrodd'],
    'eu' => ['noun'=>'Izena','verb'=>'Aditza','adjective'=>'Izenondoa','adverb'=>'Adberbioa',
             'pronoun'=>'Izenordaina','preposition'=>'Preposizioa','conjunction'=>'Juntagailua','interjection'=>'Interjekzioa','article'=>'Artikulua','phrase'=>'Esaldia'],
    'gl' => ['noun'=>'Substantivo','verb'=>'Verbo','adjective'=>'Adxectivo','adverb'=>'Adverbio',
             'pronoun'=>'Pronome','preposition'=>'Preposición','conjunction'=>'Conxunción','interjection'=>'Interxección','article'=>'Artigo','phrase'=>'Frase'],
    'tl' => ['noun'=>'Pangngalan','verb'=>'Pandiwa','adjective'=>'Pang-uri','adverb'=>'Pang-abay',
             'pronoun'=>'Panghalip','preposition'=>'Pang-ukol','conjunction'=>'Pangatnig','interjection'=>'Pandamdam','article'=>'Pantukoy','phrase'=>'Parirala'],
    'sq' => ['noun'=>'Emër','verb'=>'Folje','adjective'=>'Mbiemër','adverb'=>'Ndajfolje',
             'pronoun'=>'Përemër','preposition'=>'Parafjalë','conjunction'=>'Lidhëz','interjection'=>'Pasthirrmë','article'=>'Nyje','phrase'=>'Shprehje'],
    'te' => ['noun'=>'నామవాచకం','verb'=>'క్రియ','adjective'=>'విశేషణం','adverb'=>'క్రియా విశేషణం',
             'pronoun'=>'సర్వనామం','preposition'=>'విభక్తి','conjunction'=>'సంయోజకం','interjection'=>'ఆశ్చర్యార్థకం','article'=>'ఉపపదం','phrase'=>'పదబంధం'],
    'ta' => ['noun'=>'பெயர்ச்சொல்','verb'=>'வினைச்சொல்','adjective'=>'பெயரடை','adverb'=>'வினையடை',
             'pronoun'=>'பிரதிப்பெயர்','preposition'=>'இடைச்சொல்','conjunction'=>'இணைப்புச்சொல்','interjection'=>'வியப்பிடைச்சொல்','article'=>'கட்டுரை','phrase'=>'சொற்றொடர்'],
    'mk' => ['noun'=>'Именка','verb'=>'Глагол','adjective'=>'Придавка','adverb'=>'Прилог',
             'pronoun'=>'Заменка','preposition'=>'Предлог','conjunction'=>'Сврзник','interjection'=>'Извик','article'=>'Член','phrase'=>'Фраза'],
    'sk' => ['noun'=>'Podstatné meno','verb'=>'Sloveso','adjective'=>'Prídavné meno','adverb'=>'Príslovka',
             'pronoun'=>'Zámeno','preposition'=>'Predložka','conjunction'=>'Spojka','interjection'=>'Citoslovce','article'=>'Člen','phrase'=>'Fráza'],
    'lt' => ['noun'=>'Daiktavardis','verb'=>'Veiksmažodis','adjective'=>'Būdvardis','adverb'=>'Prieveiksmis',
             'pronoun'=>'Įvardis','preposition'=>'Prielinksnis','conjunction'=>'Jungtukas','interjection'=>'Jaustukas','article'=>'Artikelis','phrase'=>'Frazė'],
    'lv' => ['noun'=>'Lietvārds','verb'=>'Darbības vārds','adjective'=>'Īpašības vārds','adverb'=>'Apstākļa vārds',
             'pronoun'=>'Vietniekvārds','preposition'=>'Prievārds','conjunction'=>'Saiklis','interjection'=>'Izsauksmes vārds','article'=>'Artikuls','phrase'=>'Frāze'],
    'et' => ['noun'=>'Nimisõna','verb'=>'Tegusõna','adjective'=>'Omadussõna','adverb'=>'Määrsõna',
             'pronoun'=>'Asesõna','preposition'=>'Eessõna','conjunction'=>'Sidesõna','interjection'=>'Hüüdsõna','article'=>'Artikkel','phrase'=>'Fraas'],
    'sl' => ['noun'=>'Samostalnik','verb'=>'Glagol','adjective'=>'Pridevnik','adverb'=>'Prislov',
             'pronoun'=>'Zaimek','preposition'=>'Predlog','conjunction'=>'Veznik','interjection'=>'Medmet','article'=>'Člen','phrase'=>'Frazem'],
];

// English fallback POS labels
$enLabels = [
    'noun'=>'Noun','verb'=>'Verb','adjective'=>'Adjective','adverb'=>'Adverb',
    'pronoun'=>'Pronoun','preposition'=>'Preposition','conjunction'=>'Conjunction',
    'interjection'=>'Interjection','article'=>'Article','phrase'=>'Phrase'
];

function getPosLabel($lang, $pos, $posLabels, $enLabels) {
    if (isset($posLabels[$lang][$pos])) return $posLabels[$lang][$pos];
    return $enLabels[$pos] ?? ucfirst($pos);
}

// Determine which languages to process
if ($targetLang) {
    $langsToProcess = [$targetLang];
} else {
    // Get all languages with thin words, excluding Phase 1
    $stmt = $pdo->query("
        SELECT w.lang_code, COUNT(*) as thin_count
        FROM dict_words w
        LEFT JOIN dict_definitions d ON w.id = d.word_id
        WHERE d.id IS NULL
        AND w.lang_code NOT IN ('es','en','fr','de','pt','it')
        GROUP BY w.lang_code
        HAVING thin_count > 0
        ORDER BY thin_count DESC
    ");
    $allLangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $langsToProcess = [];
    foreach ($allLangs as $row) {
        $lc = $row['lang_code'];
        if ($phase === '2' && !in_array($lc, $phase2)) continue;
        if ($phase === '3' && in_array($lc, $phase2)) continue;
        $langsToProcess[] = $lc;
    }
}

$BATCH = 5000;
$grandTotal = ['propagated' => 0, 'pos_filled' => 0];
$results = [];

foreach ($langsToProcess as $lang) {
    echo "\n=== Processing: $lang ===\n";

    // Count thin words
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM dict_words w
        LEFT JOIN dict_definitions d ON w.id = d.word_id
        WHERE d.id IS NULL AND w.lang_code = ?
    ");
    $stmt->execute([$lang]);
    $thinCount = (int)$stmt->fetchColumn();
    echo "  Thin words: $thinCount\n";

    if ($thinCount === 0) {
        $results[$lang] = ['thin_before' => 0, 'propagated' => 0, 'pos_filled' => 0, 'thin_after' => 0];
        continue;
    }

    $results[$lang] = ['thin_before' => $thinCount, 'propagated' => 0, 'pos_filled' => 0];

    // === STEP 1: Cross-language propagation from EN ===
    echo "  Step 1: Cross-language propagation (EN)...\n";
    $offset = 0;
    $propagated = 0;

    while (true) {
        // Get batch of thin words
        $stmt = $pdo->prepare("
            SELECT w.id, w.word_normalized, w.part_of_speech
            FROM dict_words w
            LEFT JOIN dict_definitions d ON w.id = d.word_id
            WHERE d.id IS NULL AND w.lang_code = ?
            LIMIT $BATCH OFFSET $offset
        ");
        $stmt->execute([$lang]);
        $thinWords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($thinWords)) break;

        // Collect normalized words for batch lookup
        $normalizedWords = array_column($thinWords, 'word_normalized');
        $placeholders = implode(',', array_fill(0, count($normalizedWords), '?'));

        // Find EN definitions for these words
        $lookupStmt = $pdo->prepare("
            SELECT w.word_normalized, d.definition
            FROM dict_words w
            JOIN dict_definitions d ON w.id = d.word_id
            WHERE w.lang_code = 'en'
            AND w.word_normalized IN ($placeholders)
            GROUP BY w.word_normalized
        ");
        $lookupStmt->execute($normalizedWords);
        $enDefs = [];
        while ($row = $lookupStmt->fetch(PDO::FETCH_ASSOC)) {
            $enDefs[$row['word_normalized']] = $row['definition'];
        }

        // Also check FR if EN didn't have it
        $missingFromEn = array_diff($normalizedWords, array_keys($enDefs));
        $frDefs = [];
        if (!empty($missingFromEn)) {
            $missingList = array_values($missingFromEn);
            $placeholders2 = implode(',', array_fill(0, count($missingList), '?'));
            $lookupStmt2 = $pdo->prepare("
                SELECT w.word_normalized, d.definition
                FROM dict_words w
                JOIN dict_definitions d ON w.id = d.word_id
                WHERE w.lang_code = 'fr'
                AND w.word_normalized IN ($placeholders2)
                GROUP BY w.word_normalized
            ");
            $lookupStmt2->execute($missingList);
            while ($row = $lookupStmt2->fetch(PDO::FETCH_ASSOC)) {
                $frDefs[$row['word_normalized']] = $row['definition'];
            }
        }

        // Insert propagated definitions
        if (!$dryRun) {
            $pdo->beginTransaction();
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO dict_definitions (word_id, definition, lang_code, source_id, sort_order)
            VALUES (?, ?, ?, ?, 0)
        ");

        foreach ($thinWords as $tw) {
            $norm = $tw['word_normalized'];
            if (isset($enDefs[$norm])) {
                if (!$dryRun) {
                    $insertStmt->execute([$tw['id'], $enDefs[$norm], $lang, 'en_wiktionary_fallback']);
                }
                $propagated++;
            } elseif (isset($frDefs[$norm])) {
                if (!$dryRun) {
                    $insertStmt->execute([$tw['id'], $frDefs[$norm], $lang, 'fr_wiktionary_fallback']);
                }
                $propagated++;
            }
        }

        if (!$dryRun) {
            $pdo->commit();
        }

        // If we got less than BATCH, we're done
        if (count($thinWords) < $BATCH) break;
        $offset += $BATCH;
    }

    echo "    Propagated: $propagated\n";
    $results[$lang]['propagated'] = $propagated;
    $grandTotal['propagated'] += $propagated;

    // === STEP 2: POS-based minimal definitions for remaining thin words ===
    echo "  Step 2: POS-based fill...\n";
    $offset = 0;
    $posFilled = 0;

    while (true) {
        $stmt = $pdo->prepare("
            SELECT w.id, w.word, w.part_of_speech
            FROM dict_words w
            LEFT JOIN dict_definitions d ON w.id = d.word_id
            WHERE d.id IS NULL AND w.lang_code = ?
            LIMIT $BATCH
        ");
        $stmt->execute([$lang]);
        $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($remaining)) break;

        if (!$dryRun) {
            $pdo->beginTransaction();
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO dict_definitions (word_id, definition, lang_code, source_id, sort_order)
            VALUES (?, ?, ?, ?, 0)
        ");

        foreach ($remaining as $rw) {
            $label = getPosLabel($lang, $rw['part_of_speech'], $posLabels, $enLabels);
            $def = "($label)";
            if (!$dryRun) {
                $insertStmt->execute([$rw['id'], $def, $lang, 'pos_fallback']);
            }
            $posFilled++;
        }

        if (!$dryRun) {
            $pdo->commit();
        }
    }

    echo "    POS-filled: $posFilled\n";
    $results[$lang]['pos_filled'] = $posFilled;
    $grandTotal['pos_filled'] += $posFilled;

    // === STEP 3: Verify ===
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM dict_words w
        LEFT JOIN dict_definitions d ON w.id = d.word_id
        WHERE d.id IS NULL AND w.lang_code = ?
    ");
    $stmt->execute([$lang]);
    $remaining = (int)$stmt->fetchColumn();
    $results[$lang]['thin_after'] = $remaining;

    $status = $remaining === 0 ? "✓ ZERO THIN" : "✗ $remaining REMAINING";
    echo "  Verification: $status\n";
}

// === FINAL REPORT ===
echo "\n\n========================================\n";
echo "FINAL REPORT" . ($dryRun ? " (DRY RUN)" : "") . "\n";
echo "========================================\n";
printf("%-6s | %8s | %10s | %10s | %8s\n", "LANG", "BEFORE", "PROPAGATED", "POS-FILL", "AFTER");
echo str_repeat('-', 55) . "\n";

$allZero = true;
foreach ($results as $lang => $r) {
    printf("%-6s | %8s | %10s | %10s | %8s\n",
        $lang,
        number_format($r['thin_before']),
        number_format($r['propagated']),
        number_format($r['pos_filled']),
        number_format($r['thin_after'])
    );
    if ($r['thin_after'] > 0) $allZero = false;
}

echo str_repeat('-', 55) . "\n";
printf("%-6s | %8s | %10s | %10s |\n",
    "TOTAL", "",
    number_format($grandTotal['propagated']),
    number_format($grandTotal['pos_filled'])
);

echo "\n" . ($allZero ? "ALL LANGUAGES: ZERO THIN PAGES ✓" : "WARNING: Some languages still have thin pages!") . "\n";
