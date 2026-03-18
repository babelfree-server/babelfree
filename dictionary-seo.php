<?php
/**
 * Dictionary SEO Landing Page Template
 *
 * Renders keyword-targeted dictionary pages (e.g., /spanish-dictionary,
 * /spanish-to-english-dictionary). Each page has unique content optimized
 * for specific search intents while funneling users to the actual dictionary.
 *
 * Routed via .htaccess rewrite rules.
 */

require_once __DIR__ . '/api/config/database.php';
$pdo = getDB();

// ── Determine which page to render ──────────────────────────────────
$slug = $_GET['seo_page'] ?? '';
if (!$slug) { header('Location: /dictionary'); exit; }

// ── Live stats from DB ──────────────────────────────────────────────
$stats = [];
try {
    $row = $pdo->query("SELECT
        (SELECT COUNT(*) FROM dict_words WHERE lang_code='es') AS es_words,
        (SELECT COUNT(*) FROM dict_words WHERE lang_code='en') AS en_words,
        (SELECT COUNT(*) FROM dict_words WHERE lang_code='zh') AS zh_words,
        (SELECT COUNT(*) FROM dict_definitions) AS total_defs,
        (SELECT COUNT(*) FROM dict_translations) AS total_trans,
        (SELECT COUNT(DISTINCT lang_code) FROM dict_words) AS lang_count,
        (SELECT COUNT(*) FROM dict_conjugations) AS conjugations
    ")->fetch(PDO::FETCH_ASSOC);
    $stats = $row;
} catch (Exception $e) {
    $stats = ['es_words'=>'100,000+','en_words'=>'26,000+','total_defs'=>'200,000+','total_trans'=>'150,000+','lang_count'=>12,'conjugations'=>'5,000+'];
}

$esWords = number_format((int)($stats['es_words'] ?? 0));
$enWords = number_format((int)($stats['en_words'] ?? 0));
$zhWords = number_format((int)($stats['zh_words'] ?? 0));
$totalDefs = number_format((int)($stats['total_defs'] ?? 0));
$totalTrans = number_format((int)($stats['total_trans'] ?? 0));
$langCount = (int)($stats['lang_count'] ?? 12);
$conjugations = number_format((int)($stats['conjugations'] ?? 0));

// ── Page data registry ──────────────────────────────────────────────
$pages = [
    'spanish-dictionary' => [
        'title' => 'Free Spanish Dictionary Online — Babel Free',
        'h1' => 'Spanish Dictionary',
        'meta' => "Free online Spanish dictionary with {$esWords} words, definitions, translations, verb conjugations, example sentences, and CEFR level tags. Look up any Spanish word instantly.",
        'intro' => "The most comprehensive free Spanish dictionary online. With <strong>{$esWords} Spanish words</strong>, <strong>{$totalDefs} definitions</strong>, and translations across <strong>{$langCount} languages</strong>, Babel Free gives you everything you need to understand and use Spanish — whether you're a beginner or advanced learner.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'faq' => [
            'What makes Babel Free different from other Spanish dictionaries?' =>
                "Babel Free combines a comprehensive Spanish dictionary ({$esWords} words) with a complete Spanish course (El Viaje del Jaguar, A1–C2). Every word entry includes CEFR level tags so you know exactly when to learn it, verb conjugation tables covering 9 tenses, translations across {$langCount} languages, example sentences, frequency rankings, and related words. It's completely free with no premium tier.",
            'How many words does the Spanish dictionary have?' =>
                "The Babel Free Spanish dictionary currently contains {$esWords} words with {$totalDefs} definitions, growing daily through automated imports from Wiktionary. The database includes {$conjugations} verb conjugation forms across 9 tenses and {$totalTrans} cross-language translations.",
            'Is the Spanish dictionary really free?' =>
                "Yes, 100% free. No account required, no premium features locked behind a paywall, no subscription. Every word, definition, conjugation, and translation is accessible to everyone. Revenue comes from ads, not users.",
        ],
    ],

    'spanish-to-english-dictionary' => [
        'title' => 'Spanish to English Dictionary — Free Online | Babel Free',
        'h1' => 'Spanish to English Dictionary',
        'meta' => "Free Spanish to English dictionary with {$esWords} words. Instant translations, definitions, conjugation tables, example sentences, and CEFR level tags for learners.",
        'intro' => "Translate any Spanish word to English instantly. Our Spanish to English dictionary covers <strong>{$esWords} words</strong> with clear English definitions, contextual example sentences, and verb conjugation tables. Every entry links to translations in <strong>{$langCount} other languages</strong> so you can explore beyond English.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'faq' => [
            'How do I translate a Spanish word to English?' =>
                "Type any Spanish word in the search box above. You'll get English definitions, example sentences showing the word in context, verb conjugations if applicable, and translations in {$langCount} languages. You can also browse by clicking any word in the results to discover related terms.",
            'Does this dictionary work for English to Spanish too?' =>
                "Yes. The Babel Free dictionary works in both directions. You can search for English words and get Spanish translations, or search Spanish words for English definitions. The dictionary supports lookups in {$langCount} languages total.",
            'How accurate are the translations?' =>
                "Translations are sourced from Wiktionary's community-curated multilingual database, one of the most comprehensive open-source translation resources available. Each entry is cross-referenced across languages and verified against multiple sources. The dictionary currently contains {$totalTrans} verified translations.",
        ],
    ],

    'spanish-dictionary-translator' => [
        'title' => 'Spanish Dictionary & Translator — Free Online | Babel Free',
        'h1' => 'Spanish Dictionary & Translator',
        'meta' => "Free Spanish dictionary with built-in translator. Look up words, get translations in {$langCount} languages, conjugate verbs, and see example sentences — all in one place.",
        'intro' => "Dictionary and translator in one. Look up any Spanish word and get <strong>definitions</strong>, <strong>translations in {$langCount} languages</strong>, <strong>verb conjugations</strong>, and <strong>example sentences</strong> — all on a single page. No switching between apps.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'faq' => [
            'Is this a dictionary or a translator?' =>
                "Both. Every word lookup gives you dictionary-style definitions plus translations into {$langCount} languages. For verbs, you also get full conjugation tables. It's designed to be the only tool you need for understanding Spanish.",
            'Can I translate full sentences?' =>
                "The Babel Free dictionary is optimized for word and phrase lookups, giving you deeper understanding than machine translation. For single words and short phrases, you get definitions, usage examples, and translations across {$langCount} languages. For full sentence translation, we recommend using the dictionary alongside your preferred machine translator for the best results.",
        ],
    ],

    'spanish-frequency-dictionary' => [
        'title' => 'Spanish Frequency Dictionary — Most Common Words | Babel Free',
        'h1' => 'Spanish Frequency Dictionary',
        'meta' => "Spanish frequency dictionary with words ranked by how commonly they appear. CEFR-tagged (A1–C2) so you learn the most useful words first. Free online.",
        'intro' => "Learn the words that matter most. Our Spanish frequency dictionary ranks <strong>{$esWords} words</strong> by how commonly they appear in real Spanish, tagged with <strong>CEFR levels (A1–C2)</strong> so you always know which words to learn next. Start with the most frequent A1 words and work your way up.",
        'features' => ['frequency','cefr','definitions','examples','translations','conjugations'],
        'faq' => [
            'What is a frequency dictionary?' =>
                "A frequency dictionary ranks words by how often they appear in a language. The most common Spanish words — like ser, tener, hacer, ir — appear at the top. Learning words in frequency order is one of the most efficient ways to build vocabulary, because a small number of high-frequency words covers the majority of everyday speech and writing.",
            'How are the CEFR levels assigned?' =>
                "Each word is tagged with a CEFR level (A1 through C2) based on its frequency rank and the vocabulary lists defined by the Common European Framework of Reference for Languages. A1 words are the most basic and frequent, while C2 words are rare or specialized. This lets you focus on words appropriate to your current level.",
        ],
    ],

    'spanish-language-dictionary' => [
        'title' => 'Spanish Language Dictionary — Complete Reference | Babel Free',
        'h1' => 'Spanish Language Dictionary',
        'meta' => "Comprehensive Spanish language dictionary with {$esWords} words, definitions, translations, verb conjugations, and CEFR level tags. Free online reference for learners and native speakers.",
        'intro' => "A complete reference for the Spanish language. With <strong>{$esWords} words</strong>, detailed definitions, <strong>{$conjugations} verb conjugation forms</strong>, and translations across <strong>{$langCount} languages</strong>, this is the Spanish language dictionary built for both learners and native speakers.",
        'features' => ['definitions','conjugations','translations','cefr','examples','frequency'],
        'faq' => [
            'Is this dictionary suitable for native Spanish speakers?' =>
                "Yes. While the CEFR level tags are designed for learners, the dictionary contains {$esWords} words with detailed definitions, etymology, and related words that make it useful for native speakers too. The conjugation tables cover 562 verbs across 9 tenses with {$conjugations} forms.",
        ],
    ],

    'spanish-urban-dictionary' => [
        'title' => 'Spanish Slang & Informal Dictionary — Babel Free',
        'h1' => 'Spanish Urban Dictionary',
        'meta' => "Spanish slang, colloquial expressions, and informal vocabulary. Look up everyday Spanish as it's actually spoken, with definitions, examples, and regional context.",
        'intro' => "Spanish as it's actually spoken. Our dictionary includes <strong>colloquial expressions</strong>, <strong>informal vocabulary</strong>, and <strong>regional slang</strong> alongside standard definitions. Every entry shows real-world usage with example sentences so you understand not just what a word means, but how and when to use it.",
        'features' => ['definitions','examples','translations','frequency','cefr','conjugations'],
        'faq' => [
            'Does this dictionary include slang?' =>
                "Yes. The Babel Free dictionary draws from Wiktionary, which includes informal, colloquial, and slang entries alongside standard vocabulary. Many entries note regional usage (Colombian, Mexican, Argentine, etc.) so you can understand the varieties of Spanish spoken across Latin America and Spain.",
            'How is this different from Urban Dictionary?' =>
                "Unlike Urban Dictionary, Babel Free provides structured dictionary entries with proper definitions, example sentences, translations, and CEFR level tags. The informal and slang entries follow the same format as standard vocabulary, making them useful for serious learners who want to understand everyday spoken Spanish.",
        ],
    ],

    'rhyming-dictionary-in-spanish' => [
        'title' => 'Rhyming Dictionary in Spanish — Find Spanish Rhymes | Babel Free',
        'h1' => 'Rhyming Dictionary in Spanish',
        'meta' => "Find rhyming words in Spanish. Free online rhyming dictionary for poets, songwriters, and Spanish learners. Search by word ending to discover perfect and near rhymes.",
        'intro' => "Find the perfect rhyme in Spanish. Search any word and discover Spanish words that rhyme with it — organized by ending, syllable count, and frequency. Perfect for <strong>poets</strong>, <strong>songwriters</strong>, <strong>students</strong>, and anyone who loves the musicality of Spanish.",
        'features' => ['definitions','examples','frequency','cefr','translations','conjugations'],
        'faq' => [
            'How does the Spanish rhyming dictionary work?' =>
                "Search for any Spanish word and the dictionary will show you words with matching endings. Because Spanish is a highly phonetic language, words that share the same ending almost always rhyme perfectly. You can filter results by CEFR level, part of speech, and frequency to find exactly the rhyme you need.",
            'Can I use this for writing Spanish poetry?' =>
                "Absolutely. The rhyming dictionary is designed for creative writing in Spanish. Each rhyming word includes its definition, example sentences, and frequency ranking so you can choose rhymes that sound natural and fit your meaning. Combined with the CEFR tags, it's also a great tool for language learners writing their first poems in Spanish.",
        ],
    ],

    'thesaurus-in-spanish' => [
        'title' => 'Spanish Thesaurus — Synonyms & Antonyms | Babel Free',
        'h1' => 'Thesaurus in Spanish',
        'meta' => "Free Spanish thesaurus with synonyms, antonyms, and related words. Expand your vocabulary and find the perfect word for any context. CEFR-tagged for learners.",
        'intro' => "Find the right word every time. Our Spanish thesaurus shows you <strong>synonyms</strong>, <strong>antonyms</strong>, and <strong>related words</strong> for any Spanish term — all with definitions and CEFR level tags so you can expand your vocabulary at your own pace.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'faq' => [
            'Does the Babel Free dictionary include synonyms and antonyms?' =>
                "Yes. Word entries include synonyms, antonyms, and derived forms sourced from Wiktionary. Each related word is clickable, linking directly to its own dictionary page with full definitions, translations, and conjugations. This makes it easy to explore vocabulary clusters and find exactly the word you need.",
        ],
    ],

    'dictionary-real-academia-espanola' => [
        'title' => 'Real Academia Española Dictionary Alternative — Babel Free',
        'h1' => 'Dictionary of the Real Academia Española',
        'meta' => "Free alternative to the RAE dictionary. Look up Spanish words with definitions, translations, conjugations, and CEFR level tags. Multilingual — not just Spanish.",
        'intro' => "The <em>Real Academia Española</em> (RAE) is the definitive authority on the Spanish language. While the RAE's <em>Diccionario de la lengua española</em> is the gold standard for native speakers, Babel Free complements it with <strong>translations in {$langCount} languages</strong>, <strong>CEFR level tags</strong> for learners, <strong>verb conjugation tables</strong>, and <strong>example sentences</strong> — features the RAE dictionary doesn't offer.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'faq' => [
            'How is Babel Free different from the RAE dictionary?' =>
                "The RAE dictionary (dle.rae.es) is a monolingual Spanish reference — definitions are in Spanish only, with no translations, no conjugation tables, and no learner features. Babel Free provides definitions in multiple languages, translations across {$langCount} languages, full conjugation tables for 562 verbs, CEFR level tags (A1–C2), example sentences, and frequency rankings. Think of the RAE as the authoritative reference and Babel Free as the learner-friendly companion.",
            'Is Babel Free affiliated with the RAE?' =>
                "No. Babel Free is an independent language education platform. We complement the RAE dictionary by adding multilingual support, learner features, and cross-language translations that the RAE does not provide.",
        ],
    ],

    'cambridge-dictionary-english-spanish' => [
        'title' => 'Cambridge English-Spanish Dictionary Alternative — Babel Free',
        'h1' => 'Cambridge Dictionary: English–Spanish',
        'meta' => "Free alternative to the Cambridge English-Spanish dictionary. Look up words with translations, conjugations, CEFR level tags, and example sentences across {$langCount} languages.",
        'intro' => "Looking for a Cambridge-style English-Spanish dictionary? Babel Free offers a <strong>free alternative</strong> with <strong>{$esWords} Spanish words</strong>, translations across <strong>{$langCount} languages</strong> (not just English–Spanish), <strong>CEFR level tags</strong>, and <strong>verb conjugation tables</strong>. No subscription required.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'faq' => [
            'How does Babel Free compare to the Cambridge dictionary?' =>
                "Cambridge is an excellent dictionary with expert-curated entries. Babel Free complements it by offering translations in {$langCount} languages (Cambridge covers fewer), CEFR level tags on every word, full conjugation tables for 562 Spanish verbs, and integration with the El Viaje del Jaguar Spanish course. Both are free to use online.",
        ],
    ],

    'oxford-spanish-dictionary' => [
        'title' => 'Oxford Spanish Dictionary Alternative — Free Online | Babel Free',
        'h1' => 'Oxford Spanish Dictionary',
        'meta' => "Free alternative to the Oxford Spanish Dictionary. {$esWords} words, translations, conjugations, CEFR tags, and example sentences. No subscription needed.",
        'intro' => "The Oxford Spanish Dictionary is a trusted reference. Babel Free offers a <strong>free online alternative</strong> with <strong>{$esWords} words</strong>, <strong>{$totalDefs} definitions</strong>, translations in <strong>{$langCount} languages</strong>, and features Oxford doesn't: <strong>CEFR level tags</strong> and <strong>full verb conjugation tables</strong>.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'faq' => [
            'Is the Babel Free dictionary as good as Oxford?' =>
                "Oxford has decades of editorial expertise. Babel Free offers a different approach: community-sourced from Wiktionary with {$esWords} words, multilingual translations ({$langCount} languages), CEFR level tags for learners, and verb conjugation tables — all completely free online with no account required.",
        ],
    ],

    'collins-spanish-dictionary' => [
        'title' => 'Collins Spanish Dictionary Alternative — Free Online | Babel Free',
        'h1' => 'Collins Spanish Dictionary',
        'meta' => "Free alternative to the Collins Spanish Dictionary. {$esWords} words, definitions, translations in {$langCount} languages, conjugations, and CEFR level tags.",
        'intro' => "Collins is one of the most popular Spanish dictionaries. Babel Free offers a <strong>free alternative</strong> with <strong>{$esWords} words</strong>, <strong>translations in {$langCount} languages</strong>, and learner features Collins doesn't have: <strong>CEFR level tags (A1–C2)</strong> on every word and integration with a full Spanish course.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'faq' => [
            'How does Babel Free compare to Collins?' =>
                "Collins offers expertly edited entries and clear definitions. Babel Free complements Collins with translations across {$langCount} languages (Collins covers fewer), CEFR level tags, full conjugation tables, and integration with the El Viaje del Jaguar Spanish course. Both have free online versions.",
        ],
    ],

    'linguee-english-to-spanish' => [
        'title' => 'Linguee English-Spanish Alternative — Free Dictionary | Babel Free',
        'h1' => 'Linguee: English to Spanish',
        'meta' => "Free alternative to Linguee for English-Spanish lookups. Dictionary with translations, definitions, conjugations, and example sentences in {$langCount} languages.",
        'intro' => "Linguee excels at showing translations in context from real documents. Babel Free takes a different approach: <strong>structured dictionary entries</strong> with definitions, <strong>translations in {$langCount} languages</strong>, <strong>verb conjugation tables</strong>, <strong>CEFR level tags</strong>, and <strong>example sentences</strong>. Use both for the best results.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'faq' => [
            'Should I use Linguee or Babel Free?' =>
                "Use both — they complement each other. Linguee shows translations in context from parallel texts (EU documents, news, etc.), which is great for seeing how phrases are used in real documents. Babel Free gives you structured dictionary entries with definitions, conjugation tables, CEFR levels, and translations across {$langCount} languages. Together, they cover both contextual usage and formal reference.",
        ],
    ],

    'wordreference-spanish' => [
        'title' => 'WordReference Spanish Alternative — Free Dictionary | Babel Free',
        'h1' => 'WordReference Spanish Dictionary',
        'meta' => "Free alternative to WordReference for Spanish lookups. {$esWords} words, translations in {$langCount} languages, verb conjugations, CEFR tags, and example sentences.",
        'intro' => "WordReference is beloved for its community forums and detailed entries. Babel Free offers a <strong>complementary alternative</strong> with <strong>{$esWords} words</strong>, <strong>translations in {$langCount} languages</strong>, <strong>CEFR level tags</strong>, and integration with a complete A1–C2 Spanish course. Use both for the fullest picture.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'faq' => [
            'How does Babel Free compare to WordReference?' =>
                "WordReference is excellent for its forum discussions and detailed bilingual entries. Babel Free adds CEFR level tags on every word, translations across {$langCount} languages (WordReference covers about 15), full conjugation tables, and integration with the El Viaje del Jaguar Spanish course. WordReference has community forums; Babel Free has a gamified learning path.",
        ],
    ],

    'spanish-dictionary-words' => [
        'title' => 'Spanish Dictionary Words — Browse & Search | Babel Free',
        'h1' => 'Spanish Dictionary Words',
        'meta' => "Browse {$esWords} Spanish dictionary words with definitions, translations, and CEFR level tags. Search by letter, frequency, or level (A1–C2).",
        'intro' => "Browse through <strong>{$esWords} Spanish words</strong> with definitions, translations, and CEFR level tags. Search for any word, or explore by starting letter, frequency rank, or proficiency level. Every word links to a full entry with conjugations, examples, and cross-language translations.",
        'features' => ['definitions','frequency','cefr','translations','examples','conjugations'],
        'faq' => [
            'How many words does the Babel Free dictionary have?' =>
                "The Spanish dictionary contains {$esWords} words with {$totalDefs} definitions, {$totalTrans} translations across {$langCount} languages, and {$conjugations} verb conjugation forms. The database grows daily through automated imports.",
        ],
    ],

    'pocket-spanish-dictionary' => [
        'title' => 'Pocket Spanish Dictionary — Free Mobile Dictionary | Babel Free',
        'h1' => 'Pocket Spanish Dictionary',
        'meta' => "Free pocket Spanish dictionary that works on any phone or tablet. {$esWords} words, instant search, conjugations, and CEFR tags — no app download needed.",
        'intro' => "A pocket-sized dictionary that fits in your browser. Babel Free works perfectly on <strong>any phone or tablet</strong> — no app download needed. Get instant access to <strong>{$esWords} Spanish words</strong>, <strong>verb conjugations</strong>, <strong>translations</strong>, and <strong>CEFR level tags</strong> anywhere you have a connection.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'faq' => [
            'Do I need to download an app?' =>
                "No. Babel Free is a Progressive Web App (PWA) that works in any mobile browser. You can add it to your home screen for instant access — it looks and feels like a native app but requires no download from the App Store or Google Play. Your pocket Spanish dictionary is always one tap away.",
        ],
    ],

    'oxford-picture-dictionary-english-spanish' => [
        'title' => 'Visual Spanish Dictionary — English-Spanish Picture Dictionary | Babel Free',
        'h1' => 'Oxford Picture Dictionary: English–Spanish',
        'meta' => "Visual English-Spanish dictionary alternative. Look up words with definitions, translations, and example sentences. Free online — no textbook needed.",
        'intro' => "The Oxford Picture Dictionary is a classic visual learning resource. While Babel Free doesn't include illustrations (yet), it offers a <strong>free online alternative</strong> with <strong>{$esWords} words</strong>, <strong>clear definitions</strong>, <strong>example sentences</strong> that paint a picture with words, and <strong>CEFR level tags</strong> — accessible on any device without buying a textbook.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'faq' => [
            'Does Babel Free have pictures?' =>
                "Not yet — visual dictionary features are planned for the future. Currently, Babel Free compensates with rich example sentences that show each word in context, definitions in multiple languages, and CEFR level tags. Combined with the El Viaje del Jaguar course (which uses visual storytelling), it provides a comprehensive learning experience.",
        ],
    ],
    // ================================================================
    // CHINESE (zh) Dictionary SEO Pages
    // ================================================================

    'chinese-spanish-dictionary' => [
        'title' => '中文西班牙语词典 — 免费在线查词 | Babel Free',
        'h1' => '中文西班牙语词典',
        'meta' => "免费中文西班牙语词典，收录{$esWords}个西班牙语单词。提供定义、翻译、动词变位、例句和CEFR等级标签。支持{$langCount}种语言互译。",
        'intro' => "最全面的免费中文西班牙语词典。收录<strong>{$esWords}个西班牙语单词</strong>，<strong>{$totalDefs}条释义</strong>，以及<strong>{$langCount}种语言</strong>的翻译。无论你是初学者还是高级学习者，Babel Free都能帮助你理解和使用西班牙语。",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'pageLang' => 'zh',
        'searchPlaceholder' => '输入一个西班牙语单词...',
        'searchLang' => 'es',
        'faq' => [
            '这个词典是免费的吗？' =>
                "完全免费。无需注册账号，无需付费，无需订阅。所有单词、释义、动词变位和翻译对所有人开放。数据库目前包含{$esWords}个西班牙语单词和{$totalTrans}条跨语言翻译。",
            '这个词典支持哪些语言？' =>
                "Babel Free词典支持{$langCount}种语言之间的查询和翻译，包括中文、英语、西班牙语、法语、德语、葡萄牙语、意大利语、日语、韩语、俄语、阿拉伯语、荷兰语等。每个词条都可以看到多语言翻译。",
            '如何使用中文查找西班牙语单词？' =>
                "在上方搜索框中输入西班牙语单词，即可获得中文释义、例句、动词变位（如适用）以及{$langCount}种语言的翻译。你也可以浏览词典，点击任何单词来发现相关词汇。",
        ],
    ],

    'english-chinese-dictionary' => [
        'title' => '英中文字典 — 英翻中在线词典 | Babel Free',
        'h1' => '英中文字典',
        'meta' => "免费英中文字典，支持英语到中文翻译。提供{$enWords}个英语单词的中文释义、例句和跨语言翻译。同时支持西班牙语等{$langCount}种语言。",
        'intro' => "英语到中文翻译词典。查找<strong>{$enWords}个英语单词</strong>的中文释义，同时获得<strong>西班牙语</strong>和其他<strong>{$langCount}种语言</strong>的翻译。每个词条都包含例句、词性和使用频率排名。",
        'features' => ['translations','definitions','examples','frequency','cefr','conjugations'],
        'pageLang' => 'zh',
        'searchPlaceholder' => '输入一个英语单词...',
        'searchLang' => 'en',
        'faq' => [
            '这个词典和其他英中词典有什么不同？' =>
                "Babel Free不仅提供英中翻译，还同时提供{$langCount}种语言的翻译。你可以看到一个英语单词在中文、西班牙语、法语、德语等多种语言中的对应词。此外还有CEFR等级标签，帮助语言学习者了解每个单词的难度级别。",
            '支持繁体中文吗？' =>
                "目前词典主要使用简体中文。我们正在扩展繁体中文支持。所有西班牙语和英语词条对简体和繁体中文用户都完全可用。",
        ],
    ],

    'traditional-chinese-dictionary' => [
        'title' => '繁體中文字典 — 西班牙語詞典 | Babel Free',
        'h1' => '繁體中文字典',
        'meta' => "免費繁體中文西班牙語字典。收錄{$esWords}個西班牙語單詞，提供繁體中文釋義、翻譯和動詞變位表。支持{$langCount}種語言。",
        'intro' => "為繁體中文使用者打造的西班牙語字典。查找<strong>{$esWords}個西班牙語單詞</strong>的釋義，獲得<strong>{$langCount}種語言</strong>的翻譯，包括完整的動詞變位表和CEFR等級標籤。",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'pageLang' => 'zh-Hant',
        'searchPlaceholder' => '輸入一個西班牙語單詞...',
        'searchLang' => 'es',
        'faq' => [
            '這個字典是免費的嗎？' =>
                "完全免費。無需註冊帳號，無需付費。所有單詞、釋義、動詞變位和翻譯對所有人開放。",
            '這個字典支持哪些語言？' =>
                "Babel Free字典支持{$langCount}種語言，包括中文（簡體和繁體）、英語、西班牙語、法語、德語、葡萄牙語、意大利語、日語、韓語、俄語、阿拉伯語和荷蘭語。",
        ],
    ],

    'how-to-say-in-spanish-chinese' => [
        'title' => '用西班牙语怎么说 — 中西翻译工具 | Babel Free',
        'h1' => '用西班牙语怎么说',
        'meta' => "查找中文词汇的西班牙语说法。免费中西翻译词典，收录{$esWords}个西班牙语单词，提供发音、例句和动词变位。",
        'intro' => "想知道<strong>用西班牙语怎么说</strong>？在Babel Free词典中搜索任何单词，立即获得西班牙语翻译、发音指南、例句和相关词汇。收录<strong>{$esWords}个西班牙语单词</strong>，覆盖从日常用语到专业术语。",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'zh',
        'searchPlaceholder' => '输入一个词...',
        'searchLang' => 'es',
        'faq' => [
            '"对不起"用西班牙语怎么说？' =>
                "\"对不起\"用西班牙语说\"Lo siento\"（表达歉意）或\"Perdón\"（请求原谅）。在Babel Free词典中搜索\"siento\"或\"perdón\"，可以看到完整的释义、例句和动词变位。",
            '如何学习更多西班牙语表达？' =>
                "除了词典查询，Babel Free还提供完整的西班牙语课程\"El Viaje del Jaguar\"（美洲豹之旅），从A1初级到C2精通，包含58个互动目的地、534项活动和28种游戏类型。课程完全免费，支持中文界面。",
        ],
    ],

    // ── Korean (ko) ──────────────────────────────────────────────────────
    '스페인어-사전' => [
        'title' => '스페인어 사전 — 무료 온라인 | Babel Free',
        'h1' => '스페인어 사전',
        'meta' => "무료 스페인어 사전. {$esWords}개의 스페인어 단어, 정의, {$langCount}개 언어 번역, 동사 활용표, 예문, CEFR 레벨 포함.",
        'intro' => "가장 포괄적인 무료 <strong>스페인어 사전</strong>. <strong>{$esWords}개의 스페인어 단어</strong>, <strong>{$totalDefs}개의 정의</strong>, <strong>{$langCount}개 언어</strong>의 번역을 제공합니다. 초급자부터 고급자까지, 스페인어를 이해하고 활용하는 데 필요한 모든 것을 제공합니다.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ko',
        'searchPlaceholder' => '스페인어 단어를 입력하세요...',
        'searchLang' => 'es',
        'faq' => [
            'Babel Free는 다른 스페인어 사전과 무엇이 다른가요?' =>
                "Babel Free는 종합 스페인어 사전({$esWords}개 단어)과 완전한 스페인어 과정(El Viaje del Jaguar, A1~C2)을 결합합니다. 각 항목에는 CEFR 레벨, 9개 시제의 동사 활용, {$langCount}개 언어의 번역, 예문, 빈도 순위가 포함됩니다. 모두 완전 무료입니다.",
            '이 사전은 정말 무료인가요?' =>
                "네, 100% 무료입니다. 계정 등록 불필요, 유료 프리미엄 기능 없음, 구독 없음. 모든 단어, 정의, 활용, 번역을 누구나 이용할 수 있습니다.",
        ],
    ],

    '한국어-스페인어-사전' => [
        'title' => '한국어 스페인어 사전 — 무료 온라인 | Babel Free',
        'h1' => '한국어 스페인어 사전',
        'meta' => "무료 한국어-스페인어 사전. {$esWords}개의 스페인어 단어를 한국어로 검색. 정의, 번역, 동사 활용표, 예문 제공.",
        'intro' => "한국어에서 스페인어로, 또는 스페인어에서 한국어로 번역할 수 있는 무료 사전. <strong>{$esWords}개의 스페인어 단어</strong>와 <strong>{$langCount}개 언어</strong>의 번역, 동사 활용표, 예문을 제공합니다.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'ko',
        'searchPlaceholder' => '스페인어 단어를 입력하세요...',
        'searchLang' => 'es',
        'faq' => [
            '한국어에서 스페인어로 번역하려면 어떻게 하나요?' =>
                "위의 검색 필드에 스페인어 또는 한국어 단어를 입력하세요. 정의, 예문, 동사 활용(해당되는 경우), {$langCount}개 언어의 번역이 표시됩니다.",
            '스페인어에서 한국어로도 번역할 수 있나요?' =>
                "네. Babel Free 사전은 양방향으로 작동합니다. 스페인어 단어를 검색하여 한국어 정의를 얻거나, {$langCount}개 언어의 번역을 확인할 수 있습니다.",
        ],
    ],

    '무료-스페인어-사전' => [
        'title' => '무료 스페인어 사전 — 온라인 | Babel Free',
        'h1' => '무료 스페인어 사전',
        'meta' => "완전 무료 스페인어 사전. {$esWords}개 단어, {$totalDefs}개 정의, {$langCount}개 언어 번역, 동사 활용표. 계정 등록 불필요.",
        'intro' => "완전 <strong>무료</strong> 스페인어 사전. <strong>{$esWords}개의 스페인어 단어</strong>, <strong>{$totalDefs}개의 정의</strong>, <strong>{$langCount}개 언어</strong>의 번역을 제공합니다. 계정 등록도 구독도 필요 없습니다 — 모든 것이 무료입니다.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ko',
        'searchPlaceholder' => '스페인어 단어를 입력하세요...',
        'searchLang' => 'es',
        'faq' => [
            '정말 무료인가요?' =>
                "네, 100% 무료입니다. 계정 등록 불필요, 유료 기능 없음, 구독 없음. {$esWords}개의 모든 단어, 정의, 활용, 번역을 누구나 이용할 수 있습니다.",
            '사전 외에 스페인어 학습도 가능한가요?' =>
                "네! 사전 외에도 Babel Free는 El Viaje del Jaguar(재규어의 여행)라는 완전한 스페인어 과정을 제공합니다. A1부터 C2까지, 58개의 목적지, 활동, 게임이 포함된 인터랙티브 학습 경험입니다. 모두 무료이며 한국어 인터페이스를 지원합니다.",
        ],
    ],

    // ── Russian (ru) ─────────────────────────────────────────────────────
    'испано-русский-перевод' => [
        'title' => 'Испано-русский перевод — Бесплатный онлайн | Babel Free',
        'h1' => 'Испано-русский перевод',
        'meta' => "Бесплатный испано-русский переводчик. {$esWords} испанских слов с определениями, переводами на {$langCount} языков, спряжениями глаголов и примерами предложений.",
        'intro' => "Переводите с <strong>испанского на русский</strong> с помощью нашего бесплатного словаря. <strong>{$esWords} испанских слов</strong> с определениями, примерами предложений, таблицами спряжений и переводами на <strong>{$langCount} языков</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'ru',
        'searchPlaceholder' => 'Введите испанское слово...',
        'searchLang' => 'es',
        'faq' => [
            'Как перевести с испанского на русский?' =>
                "Введите испанское слово в поле поиска выше. Вы получите русский перевод, определения, примеры предложений и спряжения глаголов. Словарь содержит {$esWords} испанских слов и поддерживает {$langCount} языков.",
            'Переводчик бесплатный?' =>
                "Да, полностью бесплатный. Без регистрации, без подписки, без скрытых платежей. Все переводы, определения и спряжения доступны бесплатно.",
            'Можно также переводить с русского на испанский?' =>
                "Да, словарь работает в обоих направлениях. Ищите русское слово, чтобы найти испанский перевод, или испанское слово для русского определения.",
        ],
    ],

    'испано-русский-словарь' => [
        'title' => 'Испано-русский словарь — Бесплатный онлайн | Babel Free',
        'h1' => 'Испано-русский словарь',
        'meta' => "Бесплатный испано-русский словарь с {$esWords} словами, определениями, переводами, спряжениями глаголов, примерами предложений и уровнями CEFR.",
        'intro' => "Самый полный бесплатный <strong>испано-русский словарь</strong> онлайн. <strong>{$esWords} испанских слов</strong>, <strong>{$totalDefs} определений</strong> и переводы на <strong>{$langCount} языков</strong>. Идеально для изучающих испанский язык — от начинающих до продвинутых.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ru',
        'searchPlaceholder' => 'Введите испанское слово...',
        'searchLang' => 'es',
        'faq' => [
            'Чем Babel Free отличается от других испано-русских словарей?' =>
                "Babel Free сочетает полный словарь испанского языка ({$esWords} слов) с интегрированным курсом испанского (El Viaje del Jaguar, A1–C2). Каждая статья содержит уровни CEFR, спряжения для 9 времён, переводы на {$langCount} языков, примеры предложений и рейтинги частотности. Всё бесплатно.",
            'Сколько слов в словаре?' =>
                "Испано-русский словарь Babel Free содержит {$esWords} слов с {$totalDefs} определениями. База данных включает {$conjugations} форм спряжения для 9 времён и {$totalTrans} многоязычных переводов.",
            'Словарь действительно бесплатный?' =>
                "Да, 100% бесплатно. Без аккаунта, без премиум-функций, без подписки. Все слова, определения, спряжения и переводы доступны каждому.",
        ],
    ],

    'словарь-испанского-языка' => [
        'title' => 'Словарь испанского языка — Бесплатный онлайн | Babel Free',
        'h1' => 'Словарь испанского языка',
        'meta' => "Бесплатный словарь испанского языка онлайн. {$esWords} слов с определениями, переводами на {$langCount} языков, спряжениями и уровнями CEFR.",
        'intro' => "Бесплатный <strong>словарь испанского языка</strong> с <strong>{$esWords} словами</strong>, <strong>{$totalDefs} определениями</strong> и переводами на <strong>{$langCount} языков</strong>. Каждое слово содержит уровень CEFR, спряжения глаголов, примеры предложений и рейтинг частотности.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ru',
        'searchPlaceholder' => 'Введите слово...',
        'searchLang' => 'es',
        'faq' => [
            'Что предлагает этот словарь?' =>
                "Babel Free предлагает {$esWords} испанских слов с {$totalDefs} определениями, {$conjugations} формами спряжения, переводами на {$langCount} языков, примерами предложений и уровнями CEFR (A1–C2). Кроме того, доступен полный курс испанского языка от A1 до C2.",
            'Какие языки поддерживаются?' =>
                "Babel Free поддерживает {$langCount} языков: русский, английский, испанский, французский, немецкий, португальский, итальянский, японский, корейский, арабский, нидерландский и китайский.",
        ],
    ],

    // ── Japanese (ja) ────────────────────────────────────────────────────
    'スペイン語辞書' => [
        'title' => 'スペイン語辞書 — 無料オンライン | Babel Free',
        'h1' => 'スペイン語辞書',
        'meta' => "無料のスペイン語辞書。{$esWords}語のスペイン語単語、定義、{$langCount}言語の翻訳、動詞活用表、例文、CEFRレベル付き。",
        'intro' => "最も充実した無料の<strong>スペイン語辞書</strong>。<strong>{$esWords}語のスペイン語単語</strong>、<strong>{$totalDefs}件の定義</strong>、<strong>{$langCount}言語</strong>の翻訳を収録。初心者から上級者まで、スペイン語の理解と活用に必要なすべてを提供します。",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ja',
        'searchPlaceholder' => 'スペイン語の単語を入力...',
        'searchLang' => 'es',
        'faq' => [
            'Babel Freeは他のスペイン語辞書と何が違いますか？' =>
                "Babel Freeは総合的なスペイン語辞書（{$esWords}語）と完全なスペイン語コース（El Viaje del Jaguar、A1〜C2）を組み合わせています。各エントリにはCEFRレベル、9時制の動詞活用、{$langCount}言語の翻訳、例文、頻度ランキングが含まれます。すべて完全無料です。",
            'この辞書は本当に無料ですか？' =>
                "はい、100%無料です。アカウント登録不要、有料プレミアム機能なし、サブスクリプションなし。すべての単語、定義、活用、翻訳が誰でもアクセスできます。",
        ],
    ],

    '日本語スペイン語辞書' => [
        'title' => '日本語スペイン語辞書 — 無料オンライン | Babel Free',
        'h1' => '日本語スペイン語辞書',
        'meta' => "無料の日本語-スペイン語辞書。{$esWords}語のスペイン語単語を日本語で検索。定義、翻訳、動詞活用表、例文付き。",
        'intro' => "日本語からスペイン語へ、またはスペイン語から日本語へ翻訳できる無料辞書。<strong>{$esWords}語のスペイン語単語</strong>を収録し、<strong>{$langCount}言語</strong>の翻訳、動詞活用表、例文を提供します。",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'ja',
        'searchPlaceholder' => 'スペイン語の単語を入力...',
        'searchLang' => 'es',
        'faq' => [
            '日本語からスペイン語に翻訳するにはどうすればいいですか？' =>
                "上の検索フィールドにスペイン語または日本語の単語を入力してください。定義、例文、動詞活用（該当する場合）、{$langCount}言語の翻訳が表示されます。",
            'スペイン語から日本語にも翻訳できますか？' =>
                "はい。Babel Free辞書は双方向で機能します。スペイン語の単語を検索して日本語の定義を取得したり、{$langCount}言語の翻訳を確認できます。",
        ],
    ],

    '無料スペイン語辞書' => [
        'title' => '無料スペイン語辞書 — オンライン | Babel Free',
        'h1' => '無料スペイン語辞書',
        'meta' => "完全無料のスペイン語辞書。{$esWords}語、{$totalDefs}件の定義、{$langCount}言語の翻訳、動詞活用表。アカウント不要。",
        'intro' => "完全<strong>無料</strong>のスペイン語辞書。<strong>{$esWords}語のスペイン語単語</strong>、<strong>{$totalDefs}件の定義</strong>、<strong>{$langCount}言語</strong>の翻訳を収録。アカウント登録もサブスクリプションも不要 — すべて無料でアクセスできます。",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'ja',
        'searchPlaceholder' => 'スペイン語の単語を入力...',
        'searchLang' => 'es',
        'faq' => [
            '本当に無料ですか？' =>
                "はい、100%無料です。アカウント登録不要、有料機能なし、サブスクリプションなし。{$esWords}語すべての単語、定義、活用、翻訳が誰でもアクセスできます。",
            '辞書の他にスペイン語学習もできますか？' =>
                "はい！辞書に加えて、Babel FreeはEl Viaje del Jaguar（ジャガーの旅）という完全なスペイン語コースを提供しています。A1からC2まで、58の目的地、アクティビティ、ゲームを含むインタラクティブな学習体験です。すべて無料で、日本語インターフェースに対応しています。",
        ],
    ],

    // ── Spanish (es) ─────────────────────────────────────────────────────
    'diccionario-espanol' => [
        'title' => 'Diccionario de espanol gratis en linea — Babel Free',
        'h1' => 'Diccionario de espanol',
        'meta' => "Diccionario de espanol gratis con {$esWords} palabras, definiciones, traducciones, conjugaciones verbales, oraciones de ejemplo y niveles MCER. Busca cualquier palabra.",
        'intro' => "El diccionario de espanol gratis mas completo en linea. Con <strong>{$esWords} palabras</strong>, <strong>{$totalDefs} definiciones</strong> y traducciones en <strong>{$langCount} idiomas</strong>, Babel Free te ofrece todo lo que necesitas para dominar el espanol — desde el nivel A1 hasta el C2.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra...',
        'searchLang' => 'es',
        'faq' => [
            'Que hace diferente a Babel Free de otros diccionarios de espanol?' =>
                "Babel Free combina un diccionario completo ({$esWords} palabras) con un curso de espanol integrado (El Viaje del Jaguar, A1–C2). Cada entrada incluye niveles MCER, conjugaciones para 9 tiempos, traducciones en {$langCount} idiomas, oraciones de ejemplo y clasificaciones de frecuencia. Todo completamente gratis.",
            'Cuantas palabras tiene el diccionario?' =>
                "El diccionario de espanol de Babel Free contiene actualmente {$esWords} palabras con {$totalDefs} definiciones, en crecimiento diario. La base de datos incluye {$conjugations} formas de conjugacion en 9 tiempos y {$totalTrans} traducciones multilingues.",
            'El diccionario es realmente gratis?' =>
                "Si, 100% gratis. Sin cuenta obligatoria, sin funciones premium de pago, sin suscripcion. Cada palabra, definicion, conjugacion y traduccion es accesible para todos.",
        ],
    ],

    'diccionario-ingles-espanol' => [
        'title' => 'Diccionario ingles espanol — Gratis en linea | Babel Free',
        'h1' => 'Diccionario ingles espanol',
        'meta' => "Diccionario ingles-espanol gratis con {$esWords} palabras en espanol y {$enWords} en ingles. Traducciones, definiciones, conjugaciones y oraciones de ejemplo.",
        'intro' => "Traduce cualquier palabra del ingles al espanol o viceversa. Nuestro diccionario ingles-espanol contiene <strong>{$esWords} palabras en espanol</strong> y <strong>{$enWords} en ingles</strong>, con definiciones claras, oraciones de ejemplo y traducciones en <strong>{$langCount} idiomas</strong>.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra en ingles o espanol...',
        'searchLang' => 'es',
        'faq' => [
            'Como traduzco una palabra del ingles al espanol?' =>
                "Escribe la palabra en el campo de busqueda arriba. Recibiras definiciones, traducciones, oraciones de ejemplo y conjugaciones verbales. El diccionario soporta busquedas en {$langCount} idiomas.",
            'El diccionario funciona tambien de espanol a ingles?' =>
                "Si. El diccionario Babel Free funciona en ambas direcciones. Busca una palabra en espanol para obtener definiciones en ingles, o busca en ingles para encontrar la traduccion al espanol.",
            'Es mejor que otros diccionarios ingles-espanol?' =>
                "Babel Free ofrece no solo traducciones ingles-espanol, sino traducciones en {$langCount} idiomas simultaneamente, niveles MCER para cada palabra, conjugaciones verbales completas y un curso integrado de espanol. Todo gratis.",
        ],
    ],

    'diccionario-de-la-rae' => [
        'title' => 'Diccionario de la RAE — Alternativa gratis | Babel Free',
        'h1' => 'Diccionario de la Real Academia Espanola',
        'meta' => "Diccionario de espanol como alternativa a la RAE. {$esWords} palabras con definiciones, conjugaciones, niveles MCER, oraciones de ejemplo y traducciones en {$langCount} idiomas.",
        'intro' => "Buscas un <strong>diccionario como el de la RAE</strong>? Babel Free ofrece <strong>{$esWords} palabras</strong> con definiciones detalladas, tablas de conjugacion, niveles MCER y traducciones en <strong>{$langCount} idiomas</strong>. Ademas, incluye un curso completo de espanol de A1 a C2 — todo gratis.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra...',
        'searchLang' => 'es',
        'faq' => [
            'Cual es la diferencia entre Babel Free y el diccionario de la RAE?' =>
                "El diccionario de la RAE es la referencia oficial del espanol. Babel Free complementa la RAE con funcionalidades adicionales: traducciones en {$langCount} idiomas, niveles MCER (A1–C2) para cada palabra, conjugaciones para 9 tiempos, oraciones de ejemplo y un curso interactivo de espanol. Todo gratis.",
            'Es gratis?' =>
                "Si, 100% gratis. Sin cuenta, sin suscripcion, sin costos ocultos. Las {$esWords} palabras, definiciones y conjugaciones son de libre acceso.",
        ],
    ],

    'diccionario-de-sinonimos' => [
        'title' => 'Diccionario de sinonimos y antonimos — Gratis | Babel Free',
        'h1' => 'Diccionario de sinonimos',
        'meta' => "Diccionario de sinonimos y antonimos en espanol gratis. {$esWords} palabras con sinonimos, definiciones, traducciones en {$langCount} idiomas y niveles MCER.",
        'intro' => "Encuentra <strong>sinonimos y antonimos</strong> de cualquier palabra en espanol. Nuestro diccionario contiene <strong>{$esWords} palabras</strong> con definiciones, traducciones en <strong>{$langCount} idiomas</strong>, oraciones de ejemplo y niveles MCER — todo gratis.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Buscar sinonimos de...',
        'searchLang' => 'es',
        'faq' => [
            'Como encuentro sinonimos de una palabra en espanol?' =>
                "Escribe la palabra en el campo de busqueda arriba. Recibiras la definicion completa, palabras relacionadas, traducciones en {$langCount} idiomas y oraciones de ejemplo que te ayudaran a entender los matices de cada sinonimo.",
            'El diccionario incluye antonimos?' =>
                "Si, el diccionario Babel Free muestra palabras relacionadas que incluyen sinonimos y antonimos. Ademas, cada entrada tiene traducciones en {$langCount} idiomas, conjugaciones verbales y niveles MCER.",
        ],
    ],

    'diccionario-frances-espanol' => [
        'title' => 'Diccionario frances espanol — Gratis en linea | Babel Free',
        'h1' => 'Diccionario frances espanol',
        'meta' => "Diccionario frances-espanol gratis. {$esWords} palabras en espanol con traducciones al frances, definiciones, conjugaciones y oraciones de ejemplo.",
        'intro' => "Traduce entre <strong>frances y espanol</strong> con nuestro diccionario gratuito. Con <strong>{$esWords} palabras en espanol</strong> y traducciones en <strong>{$langCount} idiomas</strong>, incluyendo frances, Babel Free es tu diccionario frances-espanol ideal.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra en frances o espanol...',
        'searchLang' => 'es',
        'faq' => [
            'Como traduzco del frances al espanol?' =>
                "Escribe una palabra francesa o espanola en el campo de busqueda. Recibiras traducciones, definiciones, oraciones de ejemplo y conjugaciones. El diccionario soporta {$langCount} idiomas.",
            'Tambien traduce del espanol al frances?' =>
                "Si, el diccionario funciona en ambas direcciones. Cada entrada muestra traducciones en {$langCount} idiomas, incluyendo frances, ingles, aleman, portugues, italiano y mas.",
        ],
    ],

    'diccionario-italiano-espanol' => [
        'title' => 'Diccionario italiano espanol — Gratis en linea | Babel Free',
        'h1' => 'Diccionario italiano espanol',
        'meta' => "Diccionario italiano-espanol gratis. {$esWords} palabras con traducciones al italiano, definiciones, conjugaciones y oraciones de ejemplo.",
        'intro' => "Traduce entre <strong>italiano y espanol</strong> con nuestro diccionario gratuito. <strong>{$esWords} palabras en espanol</strong> con traducciones en <strong>{$langCount} idiomas</strong>, incluyendo italiano — todo gratis y sin cuenta.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra en italiano o espanol...',
        'searchLang' => 'es',
        'faq' => [
            'Como traduzco del italiano al espanol?' =>
                "Escribe una palabra italiana o espanola en el campo de busqueda. Obtendras traducciones, definiciones y oraciones de ejemplo. El diccionario soporta {$langCount} idiomas.",
        ],
    ],

    'diccionario-portugues-espanol' => [
        'title' => 'Diccionario portugues espanol — Gratis en linea | Babel Free',
        'h1' => 'Diccionario portugues espanol',
        'meta' => "Diccionario portugues-espanol gratis. {$esWords} palabras con traducciones al portugues, definiciones, conjugaciones y oraciones de ejemplo.",
        'intro' => "Traduce entre <strong>portugues y espanol</strong> con nuestro diccionario gratuito. <strong>{$esWords} palabras en espanol</strong> con traducciones en <strong>{$langCount} idiomas</strong>, incluyendo portugues — todo gratis y sin cuenta.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra en portugues o espanol...',
        'searchLang' => 'es',
        'faq' => [
            'Como traduzco del portugues al espanol?' =>
                "Escribe una palabra portuguesa o espanola en el campo de busqueda. Obtendras traducciones, definiciones y oraciones de ejemplo en {$langCount} idiomas.",
        ],
    ],

    'significado-de-palabras' => [
        'title' => 'Significado de palabras en espanol — Babel Free',
        'h1' => 'Significado de palabras',
        'meta' => "Descubre el significado de cualquier palabra en espanol. Diccionario gratis con {$esWords} palabras, definiciones, traducciones, oraciones de ejemplo y niveles MCER.",
        'intro' => "Quieres saber el <strong>significado de</strong> una palabra? Busca en nuestro diccionario gratis con <strong>{$esWords} palabras en espanol</strong>. Cada busqueda muestra definiciones, traducciones en <strong>{$langCount} idiomas</strong>, oraciones de ejemplo y niveles MCER.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'es',
        'searchPlaceholder' => 'Escribe una palabra...',
        'searchLang' => 'es',
        'faq' => [
            'Como descubro el significado de una palabra?' =>
                "Escribe la palabra en el campo de busqueda arriba y presiona Enter. Recibiras el significado completo, oraciones de ejemplo, conjugaciones verbales y traducciones en {$langCount} idiomas.",
            'El diccionario es gratis?' =>
                "Si, 100% gratis. Sin cuenta, sin suscripcion. Todas las {$esWords} palabras, definiciones y traducciones son de libre acceso para todos.",
        ],
    ],

    // ── Italian (it) ─────────────────────────────────────────────────────
    'dizionario-spagnolo' => [
        'title' => 'Dizionario spagnolo gratuito online — Babel Free',
        'h1' => 'Dizionario spagnolo',
        'meta' => "Dizionario spagnolo gratuito online con {$esWords} parole, definizioni, traduzioni, coniugazioni verbali, frasi di esempio e livelli QCER. Cerca qualsiasi parola spagnola.",
        'intro' => "Il dizionario spagnolo gratuito piu completo online. Con <strong>{$esWords} parole spagnole</strong>, <strong>{$totalDefs} definizioni</strong> e traduzioni in <strong>{$langCount} lingue</strong>, Babel Free ti offre tutto cio di cui hai bisogno per capire e usare lo spagnolo — che tu sia principiante o avanzato.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'it',
        'searchPlaceholder' => 'Digita una parola spagnola...',
        'searchLang' => 'es',
        'faq' => [
            'Cosa rende Babel Free diverso dagli altri dizionari di spagnolo?' =>
                "Babel Free combina un dizionario completo di spagnolo ({$esWords} parole) con un corso completo di spagnolo (El Viaje del Jaguar, A1–C2). Ogni voce include livelli QCER, coniugazioni per 9 tempi, traduzioni in {$langCount} lingue, frasi di esempio e classifiche di frequenza. Tutto completamente gratuito.",
            'Quante parole ha il dizionario spagnolo?' =>
                "Il dizionario spagnolo di Babel Free contiene attualmente {$esWords} parole con {$totalDefs} definizioni, in crescita quotidiana tramite importazioni automatizzate da Wiktionary. Il database include {$conjugations} forme di coniugazione su 9 tempi e {$totalTrans} traduzioni multilingue.",
            'Il dizionario spagnolo e davvero gratuito?' =>
                "Si, 100% gratuito. Nessun account richiesto, nessuna funzionalita premium a pagamento, nessun abbonamento. Ogni parola, definizione, coniugazione e traduzione e accessibile a tutti.",
        ],
    ],

    'dizionario-spagnolo-italiano' => [
        'title' => 'Dizionario spagnolo italiano — Gratuito online | Babel Free',
        'h1' => 'Dizionario spagnolo italiano',
        'meta' => "Dizionario spagnolo-italiano gratuito con {$esWords} parole. Traduzioni, definizioni, coniugazioni verbali, frasi di esempio e livelli QCER per studenti.",
        'intro' => "Traduci qualsiasi parola dallo spagnolo all'italiano istantaneamente. Il nostro dizionario spagnolo-italiano copre <strong>{$esWords} parole</strong> con definizioni chiare in italiano, frasi di esempio contestuali e tabelle di coniugazione verbale. Ogni voce offre anche traduzioni in <strong>{$langCount} altre lingue</strong>.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'it',
        'searchPlaceholder' => 'Digita una parola spagnola...',
        'searchLang' => 'es',
        'faq' => [
            'Come traduco una parola dallo spagnolo all\'italiano?' =>
                "Digita una parola spagnola nel campo di ricerca qui sopra. Riceverai definizioni in italiano, frasi di esempio che mostrano la parola nel contesto, coniugazioni verbali (se applicabili) e traduzioni in {$langCount} lingue.",
            'Questo dizionario funziona anche dall\'italiano allo spagnolo?' =>
                "Si. Il dizionario Babel Free funziona in entrambe le direzioni. Puoi cercare parole italiane e ottenere traduzioni in spagnolo, o cercare parole spagnole per definizioni in italiano. Il dizionario supporta ricerche in {$langCount} lingue.",
            'Quanto sono affidabili le traduzioni?' =>
                "Le traduzioni provengono dal database multilingue curato dalla comunita di Wiktionary, una delle fonti di traduzione open-source piu complete disponibili. Il dizionario contiene attualmente {$totalTrans} traduzioni verificate.",
        ],
    ],

    'traduzione-italiano-spagnolo' => [
        'title' => 'Traduzione italiano spagnolo — Traduttore gratuito | Babel Free',
        'h1' => 'Traduzione italiano spagnolo',
        'meta' => "Traduttore italiano-spagnolo gratuito. Traduci parole dall'italiano allo spagnolo con definizioni, frasi di esempio e coniugazioni. {$esWords} parole spagnole.",
        'intro' => "Traduci dall'<strong>italiano allo spagnolo</strong> con il nostro dizionario gratuito. Digita una parola e ottieni istantaneamente la traduzione spagnola, le definizioni, le frasi di esempio e le traduzioni in <strong>{$langCount} lingue</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'it',
        'searchPlaceholder' => 'Digita una parola...',
        'searchLang' => 'es',
        'faq' => [
            'Come traduco dall\'italiano allo spagnolo?' =>
                "Digita una parola italiana o spagnola nel campo di ricerca qui sopra. Otterrai istantaneamente traduzioni, definizioni e frasi di esempio. Il dizionario contiene {$esWords} parole spagnole e supporta {$langCount} lingue.",
            'Il traduttore e gratuito?' =>
                "Si, completamente gratuito. Nessun account, nessun abbonamento, nessun costo nascosto. Tutte le traduzioni, definizioni e coniugazioni sono liberamente accessibili.",
            'Posso tradurre anche dallo spagnolo all\'italiano?' =>
                "Si, il dizionario funziona in entrambe le direzioni. Cerca una parola spagnola per ottenere definizioni in italiano, o cerca una parola italiana per trovare la traduzione spagnola.",
        ],
    ],

    'significato-di' => [
        'title' => 'Significato di parole spagnole — Babel Free',
        'h1' => 'Significato di parole spagnole',
        'meta' => "Scopri il significato di qualsiasi parola spagnola. Dizionario gratuito con {$esWords} parole, definizioni, traduzioni, frasi di esempio e livelli QCER.",
        'intro' => "Vuoi sapere il <strong>significato di</strong> una parola spagnola? Cerca istantaneamente nel nostro dizionario gratuito con <strong>{$esWords} parole spagnole</strong>. Ogni ricerca mostra definizioni, traduzioni in <strong>{$langCount} lingue</strong>, frasi di esempio e livelli QCER.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'it',
        'searchPlaceholder' => 'Digita una parola spagnola...',
        'searchLang' => 'es',
        'faq' => [
            'Come scopro il significato di una parola spagnola?' =>
                "Digita la parola nel campo di ricerca qui sopra e premi Invio. Riceverai il significato completo, frasi di esempio, coniugazioni verbali e traduzioni in {$langCount} lingue.",
            'Posso vedere il significato in italiano?' =>
                "Si, Babel Free mostra traduzioni in {$langCount} lingue, incluso l'italiano. Puoi cercare qualsiasi parola spagnola e trovare la traduzione italiana, insieme a definizioni e frasi di esempio.",
            'Il dizionario insegna anche lo spagnolo?' =>
                "Si! Oltre al dizionario, Babel Free offre il corso completo El Viaje del Jaguar — un viaggio interattivo dall'A1 al C2 con 58 destinazioni, attivita e giochi. Il corso e completamente gratuito e supporta l'interfaccia in italiano.",
        ],
    ],

    // ── Dutch (nl) ──────────────────────────────────────────────────────
    'spaans-woordenboek' => [
        'title' => 'Gratis Spaans woordenboek online — Babel Free',
        'h1' => 'Spaans woordenboek',
        'meta' => "Gratis online Spaans woordenboek met {$esWords} woorden, definities, vertalingen, werkwoordvervoegingen, voorbeeldzinnen en CEFR-niveaulabels. Zoek elk Spaans woord direct op.",
        'intro' => "Het meest uitgebreide gratis Spaans woordenboek online. Met <strong>{$esWords} Spaanse woorden</strong>, <strong>{$totalDefs} definities</strong> en vertalingen in <strong>{$langCount} talen</strong> geeft Babel Free je alles wat je nodig hebt om Spaans te begrijpen en te gebruiken — of je nu beginner of gevorderd bent.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Wat maakt Babel Free anders dan andere Spaanse woordenboeken?' =>
                "Babel Free combineert een uitgebreid Spaans woordenboek ({$esWords} woorden) met een complete Spaanse cursus (El Viaje del Jaguar, A1–C2). Elk woord bevat CEFR-niveaulabels, werkwoordvervoegingen voor 9 tijden, vertalingen in {$langCount} talen, voorbeeldzinnen en frequentierankings. Het is volledig gratis zonder premium tier.",
            'Hoeveel woorden heeft het Spaans woordenboek?' =>
                "Het Babel Free Spaans woordenboek bevat momenteel {$esWords} woorden met {$totalDefs} definities, dagelijks groeiend via geautomatiseerde imports van Wiktionary. De database bevat {$conjugations} werkwoordvervoegingen over 9 tijden en {$totalTrans} meertalige vertalingen.",
            'Is het Spaans woordenboek echt gratis?' =>
                "Ja, 100% gratis. Geen account nodig, geen premium functies achter een betaalmuur, geen abonnement. Elk woord, elke definitie, vervoeging en vertaling is toegankelijk voor iedereen.",
        ],
    ],

    'woordenboek-spaans-naar-nederlands' => [
        'title' => 'Woordenboek Spaans naar Nederlands — Gratis online | Babel Free',
        'h1' => 'Woordenboek Spaans naar Nederlands',
        'meta' => "Gratis woordenboek Spaans naar Nederlands met {$esWords} woorden. Directe vertalingen, definities, werkwoordvervoegingen, voorbeeldzinnen en CEFR-niveaulabels.",
        'intro' => "Vertaal elk Spaans woord direct naar het Nederlands. Ons woordenboek Spaans-Nederlands bevat <strong>{$esWords} woorden</strong> met duidelijke Nederlandse definities, contextuele voorbeeldzinnen en werkwoordvervoegingen. Elk woord linkt naar vertalingen in <strong>{$langCount} andere talen</strong>.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Hoe vertaal ik een Spaans woord naar het Nederlands?' =>
                "Typ een Spaans woord in het zoekvak hierboven. Je krijgt Nederlandse definities, voorbeeldzinnen die het woord in context tonen, werkwoordvervoegingen indien van toepassing, en vertalingen in {$langCount} talen.",
            'Werkt dit woordenboek ook van Nederlands naar Spaans?' =>
                "Ja. Het Babel Free woordenboek werkt in beide richtingen. Je kunt Nederlandse woorden zoeken en Spaanse vertalingen krijgen, of Spaanse woorden zoeken voor Nederlandse definities. Het woordenboek ondersteunt zoekopdrachten in {$langCount} talen.",
            'Hoe betrouwbaar zijn de vertalingen?' =>
                "Vertalingen zijn afkomstig uit de door de gemeenschap samengestelde meertalige database van Wiktionary, een van de meest uitgebreide open-source vertaalbronnen. Het woordenboek bevat momenteel {$totalTrans} geverifieerde vertalingen.",
        ],
    ],

    'ned-spaans-woordenboek' => [
        'title' => 'Nederlands Spaans woordenboek — Gratis online | Babel Free',
        'h1' => 'Nederlands Spaans woordenboek',
        'meta' => "Gratis Nederlands-Spaans woordenboek. Zoek Nederlandse woorden op en vind Spaanse vertalingen, definities en voorbeeldzinnen. {$esWords} Spaanse woorden beschikbaar.",
        'intro' => "Zoek Nederlandse woorden op en vind direct de Spaanse vertaling. Met <strong>{$esWords} Spaanse woorden</strong> en vertalingen in <strong>{$langCount} talen</strong> is Babel Free je ideale Nederlands-Spaans woordenboek — volledig gratis.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een woord...',
        'searchLang' => 'es',
        'faq' => [
            'Kan ik van Nederlands naar Spaans zoeken?' =>
                "Ja, het Babel Free woordenboek ondersteunt zoekopdrachten in beide richtingen. Zoek een Nederlands of Spaans woord en krijg vertalingen, definities en voorbeeldzinnen in {$langCount} talen.",
            'Hoeveel talen worden ondersteund?' =>
                "Babel Free ondersteunt {$langCount} talen: Nederlands, Engels, Spaans, Frans, Duits, Portugees, Italiaans, Japans, Koreaans, Russisch, Arabisch en Chinees. Elk woord kan in alle talen worden opgezocht.",
        ],
    ],

    'engels-spaans-woordenboek' => [
        'title' => 'Engels Spaans woordenboek — Gratis online | Babel Free',
        'h1' => 'Engels Spaans woordenboek',
        'meta' => "Gratis Engels-Spaans woordenboek met {$esWords} Spaanse en {$enWords} Engelse woorden. Vertalingen, definities, werkwoordvervoegingen en voorbeeldzinnen.",
        'intro' => "Zoek Engelse woorden op en vind Spaanse vertalingen, of andersom. Met <strong>{$esWords} Spaanse woorden</strong> en <strong>{$enWords} Engelse woorden</strong> biedt Babel Free een compleet tweetalig woordenboek — gratis en zonder account.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Engels of Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Wat is het verschil met andere Engels-Spaanse woordenboeken?' =>
                "Babel Free biedt niet alleen Engels-Spaans vertalingen, maar ook vertalingen in {$langCount} talen tegelijk. Daarnaast bevat elk woord CEFR-niveaulabels, werkwoordvervoegingen, frequentierankings en voorbeeldzinnen — allemaal gratis.",
            'Kan ik ook Spaans naar Engels zoeken?' =>
                "Ja, het woordenboek werkt in beide richtingen. Zoek een Spaans woord op en krijg Engelse definities, of zoek een Engels woord voor Spaanse vertalingen.",
        ],
    ],

    'spaans-engels-woordenboek' => [
        'title' => 'Spaans Engels woordenboek — Gratis online | Babel Free',
        'h1' => 'Spaans Engels woordenboek',
        'meta' => "Gratis Spaans-Engels woordenboek. Vertaal Spaanse woorden naar het Engels met definities, voorbeeldzinnen en werkwoordvervoegingen. {$esWords} woorden beschikbaar.",
        'intro' => "Vertaal Spaanse woorden direct naar het Engels. Ons Spaans-Engels woordenboek bevat <strong>{$esWords} Spaanse woorden</strong> met Engelse definities, voorbeeldzinnen en werkwoordvervoegingen voor 9 tijden.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Hoe gebruik ik het Spaans-Engels woordenboek?' =>
                "Typ een Spaans woord in het zoekvak. Je krijgt direct Engelse vertalingen, definities, voorbeeldzinnen en werkwoordvervoegingen. Je kunt ook doorklikken naar vertalingen in {$langCount} andere talen.",
        ],
    ],

    'spaanse-woorden' => [
        'title' => 'Spaanse woorden — Betekenis en vertaling | Babel Free',
        'h1' => 'Spaanse woorden',
        'meta' => "Zoek de betekenis van Spaanse woorden op. {$esWords} Spaanse woorden met definities, vertalingen, voorbeeldzinnen en CEFR-niveaulabels. Gratis online woordenboek.",
        'intro' => "Ontdek de betekenis van <strong>{$esWords} Spaanse woorden</strong>. Elk woord bevat definities, vertalingen in <strong>{$langCount} talen</strong>, voorbeeldzinnen en CEFR-niveaulabels zodat je precies weet wanneer je elk woord leert.",
        'features' => ['definitions','translations','cefr','examples','frequency','conjugations'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Zoek een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Hoe vind ik de betekenis van een Spaans woord?' =>
                "Typ het woord in het zoekvak hierboven. Je krijgt direct de definitie, voorbeeldzinnen, werkwoordvervoegingen en vertalingen in {$langCount} talen.",
            'Zijn er ook frequentielijsten beschikbaar?' =>
                "Ja, elk woord heeft een frequentieranking zodat je kunt zien hoe vaak het in het dagelijks Spaans wordt gebruikt. Dit helpt je om de meest nuttige woorden als eerste te leren.",
        ],
    ],

    'betekenis-van' => [
        'title' => 'Betekenis van Spaanse woorden — Gratis opzoeken | Babel Free',
        'h1' => 'Betekenis van Spaanse woorden',
        'meta' => "Zoek de betekenis van elk Spaans woord op. Gratis woordenboek met {$esWords} woorden, definities, vertalingen en voorbeeldzinnen.",
        'intro' => "Wil je de <strong>betekenis van</strong> een Spaans woord weten? Zoek direct op in ons gratis woordenboek met <strong>{$esWords} Spaanse woorden</strong>. Elke zoekopdracht toont definities, vertalingen in <strong>{$langCount} talen</strong>, voorbeeldzinnen en CEFR-niveaulabels.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Hoe zoek ik de betekenis van een Spaans woord op?' =>
                "Typ het woord in het zoekvak hierboven en druk op Enter. Je krijgt direct de volledige betekenis, voorbeeldzinnen, werkwoordvervoegingen en vertalingen in {$langCount} talen.",
            'Kan ik ook de betekenis in het Nederlands zien?' =>
                "Ja, Babel Free toont vertalingen in {$langCount} talen waaronder Nederlands. Je kunt elk Spaans woord opzoeken en de Nederlandse vertaling vinden, samen met definities en voorbeeldzinnen.",
        ],
    ],

    'engels-spaans' => [
        'title' => 'Engels Spaans vertalen — Gratis vertaler | Babel Free',
        'h1' => 'Engels Spaans vertalen',
        'meta' => "Gratis Engels naar Spaans vertaler. Zoek Engelse woorden op en vind Spaanse vertalingen met definities, voorbeeldzinnen en werkwoordvervoegingen.",
        'intro' => "Vertaal <strong>Engels naar Spaans</strong> met ons gratis woordenboek. Zoek een Engels woord op en krijg direct de Spaanse vertaling, definities, voorbeeldzinnen en vertalingen in <strong>{$langCount} talen</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Typ een Engels of Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Hoe vertaal ik van Engels naar Spaans?' =>
                "Typ een Engels woord in het zoekvak hierboven. Je krijgt direct Spaanse vertalingen, definities en voorbeeldzinnen. Het woordenboek bevat {$esWords} Spaanse en {$enWords} Engelse woorden.",
        ],
    ],

    'mijnwoordenboek-spaans' => [
        'title' => 'Mijn woordenboek Spaans — Gratis online | Babel Free',
        'h1' => 'Mijn woordenboek Spaans',
        'meta' => "Jouw persoonlijk Spaans woordenboek online. {$esWords} woorden met definities, vertalingen in {$langCount} talen, werkwoordvervoegingen en CEFR-niveaulabels. Gratis.",
        'intro' => "Maak van Babel Free <strong>jouw persoonlijke Spaans woordenboek</strong>. Met <strong>{$esWords} Spaanse woorden</strong>, definities, vertalingen in <strong>{$langCount} talen</strong> en een complete Spaanse cursus (A1–C2) heb je alles in een plek — gratis en zonder abonnement.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'nl',
        'searchPlaceholder' => 'Zoek een Spaans woord...',
        'searchLang' => 'es',
        'faq' => [
            'Waarom Babel Free als mijn Spaans woordenboek?' =>
                "Babel Free combineert een uitgebreid woordenboek met een interactieve Spaanse cursus. Je krijgt niet alleen definities en vertalingen, maar ook CEFR-niveaulabels, werkwoordvervoegingen voor 9 tijden en een leertraject van A1 tot C2. Alles gratis.",
            'Moet ik een account aanmaken?' =>
                "Nee, het woordenboek is volledig toegankelijk zonder account. Als je de cursus El Viaje del Jaguar wilt volgen, kun je optioneel een gratis account aanmaken om je voortgang bij te houden.",
        ],
    ],

    // ── German (de) ─────────────────────────────────────────────────────
    'spanisch-deutsch-woerterbuch' => [
        'title' => 'Spanisch Deutsch Worterbuch — Kostenloses Online-Worterbuch | Babel Free',
        'h1' => 'Spanisch Deutsch Worterbuch',
        'meta' => "Kostenloses Spanisch-Deutsch Worterbuch mit {$esWords} spanischen Wortern, Definitionen, Ubersetzungen, Konjugationstabellen, Beispielsatzen und GER-Niveaustufen.",
        'intro' => "Das umfassendste kostenlose Spanisch-Deutsch Worterbuch online. Mit <strong>{$esWords} spanischen Wortern</strong>, <strong>{$totalDefs} Definitionen</strong> und Ubersetzungen in <strong>{$langCount} Sprachen</strong> bietet Babel Free alles, was du brauchst, um Spanisch zu verstehen und anzuwenden.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Was macht Babel Free anders als andere Spanisch-Worterbücher?' =>
                "Babel Free kombiniert ein umfassendes Spanisch-Worterbuch ({$esWords} Worter) mit einem vollstandigen Spanischkurs (El Viaje del Jaguar, A1–C2). Jeder Eintrag enthalt GER-Niveaustufen, Konjugationstabellen fur 9 Zeiten, Ubersetzungen in {$langCount} Sprachen, Beispielsatze und Haufigkeitsrankings. Alles komplett kostenlos.",
            'Wie viele Worter hat das Spanisch-Worterbuch?' =>
                "Das Babel Free Spanisch-Worterbuch enthalt derzeit {$esWords} Worter mit {$totalDefs} Definitionen. Die Datenbank umfasst {$conjugations} Konjugationsformen uber 9 Zeiten und {$totalTrans} mehrsprachige Ubersetzungen.",
            'Ist das Spanisch-Worterbuch wirklich kostenlos?' =>
                "Ja, 100% kostenlos. Kein Konto erforderlich, keine Premium-Funktionen hinter einer Bezahlschranke, kein Abonnement. Jedes Wort, jede Definition, Konjugation und Ubersetzung ist fur alle zuganglich.",
        ],
    ],

    'deutsch-spanisch-uebersetzer' => [
        'title' => 'Deutsch Spanisch Ubersetzer — Kostenlos online | Babel Free',
        'h1' => 'Deutsch Spanisch Ubersetzer',
        'meta' => "Kostenloser Deutsch-Spanisch Ubersetzer. Ubersetze deutsche Worter ins Spanische mit Definitionen, Beispielsatzen und Konjugationstabellen. {$esWords} spanische Worter.",
        'intro' => "Ubersetze <strong>Deutsch nach Spanisch</strong> mit unserem kostenlosen Worterbuch. Gib ein deutsches Wort ein und erhalte sofort die spanische Ubersetzung, Definitionen, Beispielsatze und Ubersetzungen in <strong>{$langCount} Sprachen</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Wie ubersetze ich von Deutsch nach Spanisch?' =>
                "Gib ein deutsches oder spanisches Wort in das Suchfeld oben ein. Du erhaltst sofort Ubersetzungen, Definitionen und Beispielsatze. Das Worterbuch enthalt {$esWords} spanische Worter und unterstutzt {$langCount} Sprachen.",
            'Ist der Ubersetzer kostenlos?' =>
                "Ja, komplett kostenlos. Kein Konto, kein Abonnement, keine versteckten Kosten. Alle Ubersetzungen, Definitionen und Konjugationen sind frei zuganglich.",
            'Kann ich auch ganze Satze ubersetzen?' =>
                "Babel Free ist ein Worterbuch, kein Satze-Ubersetzer. Du kannst einzelne Worter nachschlagen und erhaltst Definitionen, Ubersetzungen, Beispielsatze und Konjugationen. Fur Satzubersetzungen empfehlen wir, die Worter einzeln nachzuschlagen und mit den Beispielsatzen den Kontext zu verstehen.",
        ],
    ],

    'spanisch-zu-deutsch-uebersetzer' => [
        'title' => 'Spanisch zu Deutsch Ubersetzer — Kostenlos | Babel Free',
        'h1' => 'Spanisch zu Deutsch Ubersetzer',
        'meta' => "Kostenloser Spanisch-zu-Deutsch Ubersetzer. {$esWords} spanische Worter mit deutschen Definitionen, Konjugationstabellen und Beispielsatzen.",
        'intro' => "Ubersetze spanische Worter direkt ins Deutsche. Unser Spanisch-Deutsch Ubersetzer bietet <strong>{$esWords} spanische Worter</strong> mit klaren deutschen Definitionen, Beispielsatzen und Konjugationstabellen fur 9 Zeiten.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Wie ubersetze ich ein spanisches Wort ins Deutsche?' =>
                "Gib das spanische Wort in das Suchfeld ein und drucke Enter. Du erhaltst sofort die deutsche Ubersetzung, Definitionen, Beispielsatze und Konjugationen.",
            'Welche Sprachen werden unterstutzt?' =>
                "Babel Free unterstutzt {$langCount} Sprachen: Deutsch, Englisch, Spanisch, Franzosisch, Portugiesisch, Italienisch, Japanisch, Koreanisch, Russisch, Arabisch, Niederlandisch und Chinesisch.",
        ],
    ],

    'uebersetzer-deutsch-spanisch-kostenlos' => [
        'title' => 'Ubersetzer Deutsch Spanisch kostenlos — Online | Babel Free',
        'h1' => 'Ubersetzer Deutsch Spanisch kostenlos',
        'meta' => "Kostenloser Online-Ubersetzer Deutsch-Spanisch. Kein Konto, kein Abonnement. {$esWords} spanische Worter mit Definitionen, Konjugationen und Beispielsatzen.",
        'intro' => "Kostenlos <strong>Deutsch nach Spanisch ubersetzen</strong> — ohne Konto, ohne Abonnement, ohne Einschrankungen. Unser Worterbuch enthalt <strong>{$esWords} spanische Worter</strong> mit Definitionen, Ubersetzungen in <strong>{$langCount} Sprachen</strong> und vollstandigen Konjugationstabellen.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Ist der Ubersetzer wirklich kostenlos?' =>
                "Ja, 100% kostenlos und ohne Einschrankungen. Kein Konto erforderlich, keine Premium-Funktionen, kein Abonnement. Jedes Wort, jede Ubersetzung und Konjugation ist frei zuganglich.",
            'Brauche ich ein Konto?' =>
                "Nein. Das Worterbuch ist vollstandig ohne Anmeldung nutzbar. Wenn du den Spanischkurs El Viaje del Jaguar nutzen mochtest, kannst du optional ein kostenloses Konto erstellen.",
        ],
    ],

    'deutsch-spanisch-uebersetzen' => [
        'title' => 'Deutsch Spanisch ubersetzen — Kostenloses Worterbuch | Babel Free',
        'h1' => 'Deutsch Spanisch ubersetzen',
        'meta' => "Deutsch nach Spanisch ubersetzen mit dem kostenlosen Babel Free Worterbuch. {$esWords} Worter, Definitionen, Konjugationen und Beispielsatze.",
        'intro' => "<strong>Deutsch nach Spanisch ubersetzen</strong> war noch nie so einfach. Gib einfach ein Wort in das Suchfeld ein und erhalte sofort Ubersetzungen, Definitionen, Beispielsatze und Konjugationen — alles kostenlos.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Wie kann ich Deutsch nach Spanisch ubersetzen?' =>
                "Gib ein deutsches oder spanisches Wort in das Suchfeld oben ein. Du erhaltst sofort Ubersetzungen in {$langCount} Sprachen, Definitionen, Beispielsatze und bei Verben die vollstandige Konjugation fur 9 Zeiten.",
        ],
    ],

    'google-uebersetzer-spanisch-deutsch' => [
        'title' => 'Spanisch Deutsch Ubersetzer — Alternative zu Google Translate | Babel Free',
        'h1' => 'Spanisch Deutsch Ubersetzer',
        'meta' => "Spanisch-Deutsch Ubersetzer als Alternative zu Google Translate. {$esWords} spanische Worter mit Definitionen, Konjugationen, GER-Stufen und Beispielsatzen. Kostenlos.",
        'intro' => "Suchst du einen <strong>Spanisch-Deutsch Ubersetzer</strong>? Babel Free bietet mehr als eine einfache Ubersetzung: <strong>{$esWords} spanische Worter</strong> mit ausfuhrlichen Definitionen, Konjugationstabellen, GER-Niveaustufen und Beispielsatzen — alles kostenlos und ohne Konto.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Was bietet Babel Free gegenuber Google Translate?' =>
                "Wahrend Google Translate Satze ubersetzt, bietet Babel Free ein tieferes Wortverstandnis: ausfuhrliche Definitionen, Konjugationstabellen fur 9 Zeiten, GER-Niveaustufen (A1–C2), Beispielsatze und Ubersetzungen in {$langCount} Sprachen. Ideal fur Sprachlerner, die Worter wirklich verstehen wollen.",
            'Ist es kostenlos?' =>
                "Ja, vollstandig kostenlos. Kein Konto, kein Abonnement, keine versteckten Kosten.",
        ],
    ],

    'spanisch-uebersetzung-deutsch' => [
        'title' => 'Spanisch Ubersetzung Deutsch — Kostenloses Worterbuch | Babel Free',
        'h1' => 'Spanisch Ubersetzung Deutsch',
        'meta' => "Spanische Worter ins Deutsche ubersetzen. Kostenloses Worterbuch mit {$esWords} Wortern, Definitionen, Konjugationen und Beispielsatzen.",
        'intro' => "Finde die <strong>Ubersetzung</strong> jedes spanischen Wortes ins <strong>Deutsche</strong>. Unser kostenloses Worterbuch enthalt <strong>{$esWords} spanische Worter</strong> mit Definitionen, Beispielsatzen, Konjugationstabellen und Ubersetzungen in <strong>{$langCount} Sprachen</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Wie finde ich die Ubersetzung eines spanischen Wortes?' =>
                "Gib das Wort in das Suchfeld ein. Du erhaltst sofort die deutsche Ubersetzung, Definitionen, Beispielsatze und bei Verben die Konjugation fur 9 Zeiten. Jeder Eintrag zeigt auch Ubersetzungen in {$langCount} weiteren Sprachen.",
        ],
    ],

    'deutsch-spanisch-woerterbuch' => [
        'title' => 'Deutsch Spanisch Worterbuch — Kostenlos online | Babel Free',
        'h1' => 'Deutsch Spanisch Worterbuch',
        'meta' => "Kostenloses Deutsch-Spanisch Worterbuch. Deutsche Worter auf Spanisch nachschlagen mit Definitionen, Konjugationen und Beispielsatzen. {$esWords} spanische Worter.",
        'intro' => "Schlage deutsche Worter auf Spanisch nach mit unserem kostenlosen <strong>Deutsch-Spanisch Worterbuch</strong>. Mit <strong>{$esWords} spanischen Wortern</strong>, <strong>{$totalDefs} Definitionen</strong> und Ubersetzungen in <strong>{$langCount} Sprachen</strong>.",
        'features' => ['definitions','translations','conjugations','examples','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Deutsches oder spanisches Wort...',
        'searchLang' => 'es',
        'faq' => [
            'Kann ich von Deutsch nach Spanisch suchen?' =>
                "Ja, das Babel Free Worterbuch unterstutzt Suchen in beiden Richtungen. Gib ein deutsches Wort ein und finde die spanische Ubersetzung, oder suche ein spanisches Wort fur die deutsche Definition.",
            'Wie viele Sprachen werden unterstutzt?' =>
                "Babel Free unterstutzt {$langCount} Sprachen, darunter Deutsch, Englisch, Spanisch, Franzosisch, Portugiesisch, Italienisch, Japanisch, Koreanisch, Russisch, Arabisch, Niederlandisch und Chinesisch.",
        ],
    ],

    'spanisches-woerterbuch' => [
        'title' => 'Spanisches Worterbuch — Kostenlos online | Babel Free',
        'h1' => 'Spanisches Worterbuch',
        'meta' => "Kostenloses spanisches Worterbuch online mit {$esWords} Wortern, {$totalDefs} Definitionen, Konjugationstabellen und Ubersetzungen in {$langCount} Sprachen.",
        'intro' => "Das umfassendste kostenlose <strong>spanische Worterbuch</strong> im Internet. <strong>{$esWords} Worter</strong> mit Definitionen, Konjugationstabellen fur 9 Zeiten, Beispielsatzen und GER-Niveaustufen — alles kostenlos und ohne Konto.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Was bietet dieses spanische Worterbuch?' =>
                "Babel Free bietet {$esWords} spanische Worter mit {$totalDefs} Definitionen, {$conjugations} Konjugationsformen, Ubersetzungen in {$langCount} Sprachen, Beispielsatze und GER-Niveaustufen (A1–C2). Ausserdem gibt es einen vollstandigen Spanischkurs von A1 bis C2.",
        ],
    ],

    'pons-spanisch-deutsch' => [
        'title' => 'Spanisch Deutsch Worterbuch — Alternative zu PONS | Babel Free',
        'h1' => 'Spanisch Deutsch Worterbuch',
        'meta' => "Spanisch-Deutsch Worterbuch als Alternative zu PONS. {$esWords} spanische Worter mit Definitionen, Konjugationen, GER-Stufen und Beispielsatzen. Kostenlos.",
        'intro' => "Suchst du ein <strong>Spanisch-Deutsch Worterbuch</strong> wie PONS? Babel Free bietet <strong>{$esWords} spanische Worter</strong> mit ausfuhrlichen Definitionen, Konjugationstabellen, GER-Niveaustufen und Ubersetzungen in <strong>{$langCount} Sprachen</strong> — vollstandig kostenlos.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Was bietet Babel Free im Vergleich zu PONS?' =>
                "Babel Free bietet ahnliche Funktionen wie PONS — Definitionen, Ubersetzungen, Konjugationen — plus GER-Niveaustufen fur jedes Wort, Ubersetzungen in {$langCount} Sprachen statt nur Deutsch-Spanisch, und einen integrierten Spanischkurs (A1–C2). Alles kostenlos.",
            'Ist es wirklich kostenlos?' =>
                "Ja, 100% kostenlos. Kein Konto, kein Premium-Abonnement. Alle {$esWords} Worter, Definitionen und Konjugationen sind frei zuganglich.",
        ],
    ],

    'leo-spanisch-deutsch' => [
        'title' => 'Spanisch Deutsch Worterbuch — Alternative zu LEO | Babel Free',
        'h1' => 'Spanisch Deutsch Worterbuch',
        'meta' => "Spanisch-Deutsch Worterbuch als Alternative zu LEO. {$esWords} Worter, Konjugationen, GER-Stufen, Beispielsatze. Kostenlos und ohne Anmeldung.",
        'intro' => "Ein <strong>Spanisch-Deutsch Worterbuch</strong> wie LEO, aber mit mehr Funktionen: <strong>{$esWords} spanische Worter</strong>, GER-Niveaustufen, Konjugationstabellen fur 9 Zeiten und Ubersetzungen in <strong>{$langCount} Sprachen</strong>. Komplett kostenlos.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Was bietet Babel Free im Vergleich zu LEO?' =>
                "Wie LEO bietet Babel Free Definitionen, Ubersetzungen und Konjugationen. Zusatzlich gibt es GER-Niveaustufen (A1–C2) fur jedes Wort, Ubersetzungen in {$langCount} Sprachen, Frequenzrankings und einen vollstandigen Spanischkurs — alles kostenlos.",
        ],
    ],

    'linguee-deutsch-spanisch' => [
        'title' => 'Deutsch Spanisch Worterbuch — Alternative zu Linguee | Babel Free',
        'h1' => 'Deutsch Spanisch Worterbuch',
        'meta' => "Deutsch-Spanisch Worterbuch als Alternative zu Linguee. {$esWords} Worter mit Definitionen, Beispielsatzen, Konjugationen und GER-Stufen. Kostenlos.",
        'intro' => "Ein <strong>Deutsch-Spanisch Worterbuch</strong> mit Kontextbeispielen wie Linguee — plus <strong>{$esWords} spanische Worter</strong>, GER-Niveaustufen, Konjugationstabellen und Ubersetzungen in <strong>{$langCount} Sprachen</strong>. Alles kostenlos.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Deutsches oder spanisches Wort...',
        'searchLang' => 'es',
        'faq' => [
            'Was bietet Babel Free im Vergleich zu Linguee?' =>
                "Wahrend Linguee sich auf Kontextubersetzungen spezialisiert, bietet Babel Free zusatzlich vollstandige Konjugationstabellen, GER-Niveaustufen, Frequenzrankings und einen integrierten Spanischkurs. Alle {$esWords} Worter sind in {$langCount} Sprachen ubersetzt.",
        ],
    ],

    'deepl-spanisch-deutsch' => [
        'title' => 'Spanisch Deutsch — Alternative zu DeepL | Babel Free',
        'h1' => 'Spanisch Deutsch Worterbuch',
        'meta' => "Spanisch-Deutsch Worterbuch als Erganzung zu DeepL. {$esWords} Worter mit Definitionen, Konjugationen, GER-Stufen und Beispielsatzen. Kostenlos.",
        'intro' => "Suchst du eine Erganzung zu DeepL fur <strong>Spanisch-Deutsch</strong>? Babel Free bietet ein tiefes Wortverstandnis: <strong>{$esWords} Worter</strong> mit Definitionen, Konjugationstabellen, GER-Niveaustufen und Ubersetzungen in <strong>{$langCount} Sprachen</strong>.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Wie unterscheidet sich Babel Free von DeepL?' =>
                "DeepL ubersetzt Satze und Texte, Babel Free ist ein Worterbuch fur tiefes Wortverstandnis. Du erhaltst Definitionen, Konjugationstabellen fur 9 Zeiten, GER-Niveaustufen, Beispielsatze und Ubersetzungen in {$langCount} Sprachen. Ideal als Erganzung zu DeepL fur Sprachlerner.",
        ],
    ],

    'uebersetzen-spanisch-deutsch-text' => [
        'title' => 'Spanisch Deutsch Text ubersetzen — Worterbuch | Babel Free',
        'h1' => 'Spanisch Deutsch Text ubersetzen',
        'meta' => "Spanische Texte verstehen: Schlage einzelne Worter im kostenlosen Worterbuch nach. {$esWords} Worter mit Definitionen, Konjugationen und Beispielsatzen.",
        'intro' => "Mochtest du einen <strong>spanischen Text ins Deutsche ubersetzen</strong>? Schlage einzelne Worter in unserem Worterbuch nach: <strong>{$esWords} spanische Worter</strong> mit Definitionen, Konjugationen, Beispielsatzen und Ubersetzungen in <strong>{$langCount} Sprachen</strong>.",
        'features' => ['definitions','translations','examples','conjugations','cefr','frequency'],
        'pageLang' => 'de',
        'searchPlaceholder' => 'Spanisches Wort eingeben...',
        'searchLang' => 'es',
        'faq' => [
            'Kann ich ganze Texte ubersetzen?' =>
                "Babel Free ist ein Worterbuch, das einzelne Worter ubersetzt. Fur ganze Texte empfehlen wir, unbekannte Worter einzeln nachzuschlagen — so lernst du die Worter wirklich und verstehst den Kontext besser. Jeder Eintrag enthalt Beispielsatze und Konjugationen.",
        ],
    ],

    // ── Portuguese (pt) ─────────────────────────────────────────────────
    'dicionario-espanhol' => [
        'title' => 'Dicionario espanhol gratis online — Babel Free',
        'h1' => 'Dicionario espanhol',
        'meta' => "Dicionario espanhol gratis online com {$esWords} palavras, definicoes, traducoes, conjugacoes verbais, frases de exemplo e niveis CEFR. Pesquise qualquer palavra em espanhol.",
        'intro' => "O mais completo <strong>dicionario espanhol</strong> gratis online. Com <strong>{$esWords} palavras em espanhol</strong>, <strong>{$totalDefs} definicoes</strong> e traducoes em <strong>{$langCount} idiomas</strong>, o Babel Free oferece tudo o que voce precisa para entender e usar o espanhol — seja voce iniciante ou avancado.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'pt',
        'searchPlaceholder' => 'Digite uma palavra em espanhol...',
        'searchLang' => 'es',
        'faq' => [
            'O que diferencia o Babel Free de outros dicionarios de espanhol?' =>
                "O Babel Free combina um dicionario completo de espanhol ({$esWords} palavras) com um curso completo de espanhol (El Viaje del Jaguar, A1–C2). Cada entrada inclui niveis CEFR, conjugacoes verbais para 9 tempos, traducoes em {$langCount} idiomas, frases de exemplo e rankings de frequencia. Tudo completamente gratis.",
            'Quantas palavras tem o dicionario de espanhol?' =>
                "O dicionario de espanhol do Babel Free contem atualmente {$esWords} palavras com {$totalDefs} definicoes, crescendo diariamente por meio de importacoes automatizadas do Wiktionary. O banco de dados inclui {$conjugations} formas de conjugacao em 9 tempos e {$totalTrans} traducoes multilinguais.",
            'O dicionario de espanhol e realmente gratis?' =>
                "Sim, 100% gratis. Sem necessidade de conta, sem funcionalidades premium por tras de uma paywall, sem assinatura. Cada palavra, definicao, conjugacao e traducao esta acessivel a todos.",
        ],
    ],

    'dicionario-espanhol-portugues' => [
        'title' => 'Dicionario espanhol portugues — Gratis online | Babel Free',
        'h1' => 'Dicionario espanhol portugues',
        'meta' => "Dicionario espanhol-portugues gratis com {$esWords} palavras. Traducoes, definicoes, conjugacoes verbais, frases de exemplo e niveis CEFR para estudantes.",
        'intro' => "Traduza qualquer palavra do espanhol para o portugues instantaneamente. Nosso dicionario espanhol-portugues cobre <strong>{$esWords} palavras</strong> com definicoes claras em portugues, frases de exemplo contextuais e tabelas de conjugacao verbal. Cada entrada tambem oferece traducoes em <strong>{$langCount} outros idiomas</strong>.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'pt',
        'searchPlaceholder' => 'Digite uma palavra em espanhol...',
        'searchLang' => 'es',
        'faq' => [
            'Como traducir uma palavra do espanhol para o portugues?' =>
                "Digite qualquer palavra em espanhol no campo de busca acima. Voce recebera definicoes em portugues, frases de exemplo mostrando a palavra em contexto, conjugacoes verbais (se aplicavel) e traducoes em {$langCount} idiomas.",
            'Este dicionario funciona tambem de portugues para espanhol?' =>
                "Sim. O dicionario Babel Free funciona em ambas as direcoes. Voce pode pesquisar palavras em portugues e obter traducoes em espanhol, ou pesquisar palavras em espanhol para definicoes em portugues. O dicionario suporta consultas em {$langCount} idiomas.",
            'Qual a diferenca entre este dicionario e outros dicionarios espanhol-portugues?' =>
                "Alem das traducoes espanhol-portugues, o Babel Free oferece traducoes em {$langCount} idiomas simultaneamente, niveis CEFR para cada palavra, conjugacoes verbais completas para 9 tempos e um curso integrado de espanhol do A1 ao C2 — tudo gratis.",
        ],
    ],

    'dicionario-portugues-espanhol' => [
        'title' => 'Dicionario portugues espanhol — Gratis online | Babel Free',
        'h1' => 'Dicionario portugues espanhol',
        'meta' => "Dicionario portugues-espanhol gratis. Pesquise palavras em portugues e encontre traducoes em espanhol com definicoes, frases de exemplo e conjugacoes. {$esWords} palavras disponiveis.",
        'intro' => "Pesquise palavras em portugues e encontre a traducao em espanhol instantaneamente. Com <strong>{$esWords} palavras em espanhol</strong> e traducoes em <strong>{$langCount} idiomas</strong>, o Babel Free e seu dicionario portugues-espanhol ideal — totalmente gratis.",
        'features' => ['translations','definitions','conjugations','examples','cefr','frequency'],
        'pageLang' => 'pt',
        'searchPlaceholder' => 'Digite uma palavra...',
        'searchLang' => 'es',
        'faq' => [
            'Posso pesquisar de portugues para espanhol?' =>
                "Sim, o dicionario Babel Free suporta consultas em ambas as direcoes. Pesquise uma palavra em portugues ou espanhol e obtenha traducoes, definicoes e frases de exemplo em {$langCount} idiomas.",
            'Quantos idiomas sao suportados?' =>
                "O Babel Free suporta {$langCount} idiomas: portugues, ingles, espanhol, frances, alemao, italiano, japones, coreano, russo, arabe, holandes e chines. Cada palavra pode ser consultada em todos os idiomas.",
        ],
    ],

    'significado-de' => [
        'title' => 'Significado de palavras em espanhol — Babel Free',
        'h1' => 'Significado de palavras em espanhol',
        'meta' => "Descubra o significado de qualquer palavra em espanhol. Dicionario gratis com {$esWords} palavras, definicoes, traducoes, frases de exemplo e niveis CEFR.",
        'intro' => "Quer saber o <strong>significado de</strong> uma palavra em espanhol? Pesquise instantaneamente em nosso dicionario gratis com <strong>{$esWords} palavras em espanhol</strong>. Cada consulta mostra definicoes, traducoes em <strong>{$langCount} idiomas</strong>, frases de exemplo e niveis CEFR.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'pt',
        'searchPlaceholder' => 'Digite uma palavra em espanhol...',
        'searchLang' => 'es',
        'faq' => [
            'Como descubro o significado de uma palavra em espanhol?' =>
                "Digite a palavra no campo de busca acima e pressione Enter. Voce recebera o significado completo, frases de exemplo, conjugacoes verbais e traducoes em {$langCount} idiomas.",
            'Posso ver o significado em portugues?' =>
                "Sim, o Babel Free mostra traducoes em {$langCount} idiomas, incluindo portugues. Voce pode pesquisar qualquer palavra em espanhol e encontrar a traducao em portugues, junto com definicoes e frases de exemplo.",
            'O dicionario tambem ensina espanhol?' =>
                "Sim! Alem do dicionario, o Babel Free oferece o curso completo El Viaje del Jaguar — uma jornada interativa do A1 ao C2 com 58 destinos, atividades e jogos. O curso e totalmente gratis e suporta interface em portugues.",
        ],
    ],

    // ── French (fr) ──────────────────────────────────────────────────────
    'dictionnaire-francais-espagnol' => [
        'title' => 'Dictionnaire francais espagnol — Gratuit en ligne | Babel Free',
        'h1' => 'Dictionnaire francais espagnol',
        'meta' => "Dictionnaire francais-espagnol gratuit avec {$esWords} mots espagnols, definitions, conjugaisons, phrases d'exemple et niveaux CECR. Traductions dans {$langCount} langues.",
        'intro' => "Le dictionnaire <strong>francais-espagnol</strong> le plus complet en ligne. Avec <strong>{$esWords} mots espagnols</strong>, <strong>{$totalDefs} definitions</strong> et des traductions dans <strong>{$langCount} langues</strong>, Babel Free vous offre tout ce dont vous avez besoin pour comprendre et utiliser l'espagnol — que vous soyez debutant ou avance.",
        'features' => ['translations','definitions','conjugations','cefr','examples','frequency'],
        'pageLang' => 'fr',
        'searchPlaceholder' => 'Tapez un mot espagnol...',
        'searchLang' => 'es',
        'faq' => [
            'Qu\'est-ce qui differencie Babel Free des autres dictionnaires espagnol-francais ?' =>
                "Babel Free combine un dictionnaire complet ({$esWords} mots espagnols) avec un cours d'espagnol integre (El Viaje del Jaguar, A1–C2). Chaque entree inclut des niveaux CECR, des conjugaisons pour 9 temps, des traductions dans {$langCount} langues, des phrases d'exemple et des classements de frequence. Le tout gratuitement.",
            'Combien de mots contient le dictionnaire ?' =>
                "Le dictionnaire espagnol de Babel Free contient actuellement {$esWords} mots avec {$totalDefs} definitions, en croissance quotidienne. La base de donnees comprend {$conjugations} formes de conjugaison sur 9 temps et {$totalTrans} traductions multilingues.",
            'Le dictionnaire est-il vraiment gratuit ?' =>
                "Oui, 100% gratuit. Pas de compte requis, pas de fonctionnalites premium payantes, pas d'abonnement. Chaque mot, definition, conjugaison et traduction est accessible a tous.",
        ],
    ],

    'traduction-francais-espagnol' => [
        'title' => 'Traduction francais espagnol — Traducteur gratuit | Babel Free',
        'h1' => 'Traduction francais espagnol',
        'meta' => "Traducteur francais-espagnol gratuit. Traduisez des mots du francais vers l'espagnol avec definitions, phrases d'exemple et conjugaisons. {$esWords} mots espagnols.",
        'intro' => "Traduisez du <strong>francais vers l'espagnol</strong> avec notre dictionnaire gratuit. Tapez un mot et obtenez instantanement la traduction espagnole, les definitions, les phrases d'exemple et les traductions dans <strong>{$langCount} langues</strong>.",
        'features' => ['translations','definitions','examples','conjugations','cefr','frequency'],
        'pageLang' => 'fr',
        'searchPlaceholder' => 'Tapez un mot...',
        'searchLang' => 'es',
        'faq' => [
            'Comment traduire du francais vers l\'espagnol ?' =>
                "Tapez un mot francais ou espagnol dans le champ de recherche ci-dessus. Vous obtiendrez instantanement des traductions, des definitions et des phrases d'exemple. Le dictionnaire contient {$esWords} mots espagnols et prend en charge {$langCount} langues.",
            'Le traducteur est-il gratuit ?' =>
                "Oui, completement gratuit. Pas de compte, pas d'abonnement, pas de frais caches. Toutes les traductions, definitions et conjugaisons sont librement accessibles.",
            'Puis-je aussi traduire de l\'espagnol vers le francais ?' =>
                "Oui, le dictionnaire fonctionne dans les deux sens. Cherchez un mot espagnol pour obtenir des definitions en francais, ou cherchez un mot francais pour trouver la traduction espagnole.",
        ],
    ],

    'dictionnaire-francais' => [
        'title' => 'Dictionnaire francais — Gratuit en ligne | Babel Free',
        'h1' => 'Dictionnaire francais',
        'meta' => "Dictionnaire francais gratuit en ligne. Recherchez des mots et obtenez des definitions, des traductions dans {$langCount} langues, des phrases d'exemple et des niveaux CECR.",
        'intro' => "Votre <strong>dictionnaire francais</strong> gratuit en ligne. Recherchez n'importe quel mot et obtenez des definitions, des traductions dans <strong>{$langCount} langues</strong> (dont l'espagnol, l'anglais, l'allemand, le portugais et plus), des phrases d'exemple et des niveaux CECR.",
        'features' => ['definitions','translations','examples','cefr','frequency','conjugations'],
        'pageLang' => 'fr',
        'searchPlaceholder' => 'Tapez un mot...',
        'searchLang' => 'es',
        'faq' => [
            'Que propose ce dictionnaire ?' =>
                "Babel Free propose {$esWords} mots espagnols avec {$totalDefs} definitions, des traductions dans {$langCount} langues, des conjugaisons pour 9 temps, des phrases d'exemple et des niveaux CECR (A1–C2). En plus du dictionnaire, un cours complet d'espagnol (El Viaje del Jaguar) est disponible gratuitement.",
            'Quelles langues sont prises en charge ?' =>
                "Babel Free prend en charge {$langCount} langues : francais, anglais, espagnol, allemand, portugais, italien, japonais, coreen, russe, arabe, neerlandais et chinois. Chaque mot peut etre recherche et traduit dans toutes ces langues.",
            'Est-ce gratuit ?' =>
                "Oui, 100% gratuit. Aucun compte requis, aucun abonnement. Tous les mots, definitions et traductions sont accessibles a tous.",
        ],
    ],

    'dictionnaire-francais-anglais' => [
        'title' => 'Dictionnaire francais anglais — Gratuit en ligne | Babel Free',
        'h1' => 'Dictionnaire francais anglais',
        'meta' => "Dictionnaire francais-anglais gratuit avec {$enWords} mots anglais et {$esWords} mots espagnols. Definitions, traductions dans {$langCount} langues, phrases d'exemple et conjugaisons.",
        'intro' => "Recherchez des mots en francais ou en anglais et obtenez des traductions instantanees. Notre dictionnaire francais-anglais contient <strong>{$enWords} mots anglais</strong> et <strong>{$esWords} mots espagnols</strong>, avec des traductions dans <strong>{$langCount} langues</strong> — le tout gratuitement.",
        'features' => ['translations','definitions','examples','frequency','cefr','conjugations'],
        'pageLang' => 'fr',
        'searchPlaceholder' => 'Tapez un mot anglais ou francais...',
        'searchLang' => 'en',
        'faq' => [
            'Comment fonctionne le dictionnaire francais-anglais ?' =>
                "Tapez un mot anglais ou francais dans le champ de recherche. Vous recevrez des definitions, des traductions dans {$langCount} langues (dont le francais, l'espagnol, l'allemand et plus), des phrases d'exemple et des niveaux CECR.",
            'Puis-je aussi traduire vers l\'espagnol ?' =>
                "Oui ! Babel Free est un dictionnaire multilingue. Chaque entree montre des traductions dans {$langCount} langues, dont le francais, l'anglais et l'espagnol. Vous pouvez facilement passer d'une langue a l'autre.",
            'Quelle est la difference avec les autres dictionnaires anglais-francais ?' =>
                "Babel Free offre non seulement des traductions anglais-francais, mais aussi des traductions dans {$langCount} langues simultanement, des niveaux CECR pour chaque mot et un cours integre d'espagnol — le tout gratuitement.",
        ],
    ],

    'traduction-anglais-francais' => [
        'title' => 'Traduction anglais francais — Gratuite en ligne | Babel Free',
        'h1' => 'Traduction anglais francais',
        'meta' => "Traduction anglais-francais gratuite. {$enWords} mots anglais avec definitions, traductions dans {$langCount} langues, phrases d'exemple et niveaux CECR.",
        'intro' => "Traduisez de l'<strong>anglais vers le francais</strong> gratuitement. Notre dictionnaire contient <strong>{$enWords} mots anglais</strong> avec des definitions claires, des traductions dans <strong>{$langCount} langues</strong> et des phrases d'exemple. Ideal pour les etudiants et les professionnels.",
        'features' => ['translations','definitions','examples','frequency','cefr','conjugations'],
        'pageLang' => 'fr',
        'searchPlaceholder' => 'Tapez un mot anglais...',
        'searchLang' => 'en',
        'faq' => [
            'Comment traduire de l\'anglais vers le francais ?' =>
                "Tapez un mot anglais dans le champ de recherche ci-dessus. Vous obtiendrez la traduction francaise, des definitions, des phrases d'exemple et des traductions dans {$langCount} langues supplementaires.",
            'La traduction est-elle gratuite ?' =>
                "Oui, completement gratuite. Aucun compte requis, aucune fonctionnalite premium, aucun abonnement. Toutes les traductions et definitions sont librement accessibles a tous.",
        ],
    ],

    'dicionario-real-academia-espanhola' => [
        'title' => 'Dicionario da Real Academia Espanhola — Alternativa gratis | Babel Free',
        'h1' => 'Dicionario da Real Academia Espanhola',
        'meta' => "Dicionario de espanhol como alternativa a RAE. {$esWords} palavras com definicoes, conjugacoes, niveis CEFR, frases de exemplo e traducoes em {$langCount} idiomas. Gratis.",
        'intro' => "Procurando um <strong>dicionario como o da Real Academia Espanhola</strong>? O Babel Free oferece <strong>{$esWords} palavras em espanhol</strong> com definicoes detalhadas, tabelas de conjugacao, niveis CEFR e traducoes em <strong>{$langCount} idiomas</strong> — incluindo portugues. Totalmente gratis.",
        'features' => ['definitions','translations','conjugations','cefr','examples','frequency'],
        'pageLang' => 'pt',
        'searchPlaceholder' => 'Digite uma palavra em espanhol...',
        'searchLang' => 'es',
        'faq' => [
            'Qual a diferenca entre o Babel Free e o dicionario da RAE?' =>
                "O dicionario da RAE (Real Academia Espanhola) e a referencia oficial para o espanhol, com definicoes em espanhol. O Babel Free oferece funcionalidades adicionais para estudantes: traducoes em {$langCount} idiomas (incluindo portugues), niveis CEFR (A1–C2) para cada palavra, conjugacoes verbais para 9 tempos e um curso integrado de espanhol. Tudo gratis.",
            'E gratis?' =>
                "Sim, 100% gratis. Sem conta, sem assinatura, sem custos ocultos. Todas as {$esWords} palavras, definicoes e conjugacoes estao livremente acessiveis.",
        ],
    ],
];

// ── Validate slug ───────────────────────────────────────────────────
if (!isset($pages[$slug])) {
    header('Location: /dictionary');
    exit;
}

$page = $pages[$slug];
$canonical = "https://babelfree.com/{$slug}";

// ── Feature descriptions ────────────────────────────────────────────
$featureMap = [
    'definitions' => ['icon'=>'📖', 'title'=>'Definitions', 'desc'=>"Detailed definitions for {$esWords} Spanish words with part of speech and usage notes"],
    'translations' => ['icon'=>'🌍', 'title'=>"Translations in {$langCount} Languages", 'desc'=>"Every word translated across {$langCount} languages — not just English and Spanish"],
    'conjugations' => ['icon'=>'📝', 'title'=>'Verb Conjugations', 'desc'=>"Full conjugation tables for 562 Spanish verbs across 9 tenses ({$conjugations} forms)"],
    'cefr' => ['icon'=>'🎯', 'title'=>'CEFR Level Tags', 'desc'=>'Every word tagged A1 through C2 so you learn the right words at the right time'],
    'examples' => ['icon'=>'💬', 'title'=>'Example Sentences', 'desc'=>'Real-world example sentences showing each word in natural context'],
    'frequency' => ['icon'=>'📊', 'title'=>'Frequency Rankings', 'desc'=>'Words ranked by how commonly they appear in real Spanish'],
];

// ── Page-level language and search config ────────────────────────────
$pageLang = $page['pageLang'] ?? 'en';
$searchPlaceholder = $page['searchPlaceholder'] ?? 'Type a Spanish word...';
$searchLang = $page['searchLang'] ?? 'es';
$searchPrefix = ($searchLang === 'es') ? 'significado-de-' : (($searchLang === 'en') ? 'meaning-of-' : 'significado-de-');

// ── Localized UI strings ────────────────────────────────────────────
$ui = [
    'en' => ['what_you_get'=>'What You Get','try_words'=>'Try These Words','faq_title'=>'Frequently Asked Questions','start_exploring'=>'Start Exploring Spanish','start_desc'=>'Search any word, discover its meaning, and learn how it\'s used — for free.','open_dict'=>'Open the Dictionary','related'=>'Related','stats_words'=>'Spanish Words','stats_defs'=>'Definitions','stats_trans'=>'Translations','stats_langs'=>'Languages','stats_conj'=>'Conjugations'],
    'zh' => ['what_you_get'=>'功能特色','try_words'=>'试试这些单词','faq_title'=>'常见问题','start_exploring'=>'开始探索西班牙语','start_desc'=>'搜索任何单词，发现其含义，了解其用法——完全免费。','open_dict'=>'打开词典','related'=>'相关页面','stats_words'=>'西班牙语单词','stats_defs'=>'释义','stats_trans'=>'翻译','stats_langs'=>'语言','stats_conj'=>'动词变位'],
    'zh-Hant' => ['what_you_get'=>'功能特色','try_words'=>'試試這些單詞','faq_title'=>'常見問題','start_exploring'=>'開始探索西班牙語','start_desc'=>'搜索任何單詞，發現其含義，了解其用法——完全免費。','open_dict'=>'打開字典','related'=>'相關頁面','stats_words'=>'西班牙語單詞','stats_defs'=>'釋義','stats_trans'=>'翻譯','stats_langs'=>'語言','stats_conj'=>'動詞變位'],
    'nl' => ['what_you_get'=>'Wat je krijgt','try_words'=>'Probeer deze woorden','faq_title'=>'Veelgestelde vragen','start_exploring'=>'Begin met Spaans ontdekken','start_desc'=>'Zoek elk woord op, ontdek de betekenis en leer hoe het wordt gebruikt — gratis.','open_dict'=>'Open het woordenboek','related'=>'Gerelateerd','stats_words'=>'Spaanse woorden','stats_defs'=>'Definities','stats_trans'=>'Vertalingen','stats_langs'=>'Talen','stats_conj'=>'Vervoegingen'],
    'de' => ['what_you_get'=>'Was du bekommst','try_words'=>'Probiere diese Worter','faq_title'=>'Haufig gestellte Fragen','start_exploring'=>'Spanisch entdecken','start_desc'=>'Suche ein Wort, entdecke seine Bedeutung und lerne, wie es verwendet wird — kostenlos.','open_dict'=>'Worterbuch offnen','related'=>'Verwandt','stats_words'=>'Spanische Worter','stats_defs'=>'Definitionen','stats_trans'=>'Ubersetzungen','stats_langs'=>'Sprachen','stats_conj'=>'Konjugationen'],
    'ko' => ['what_you_get'=>'제공 기능','try_words'=>'이 단어들을 시도해보세요','faq_title'=>'자주 묻는 질문','start_exploring'=>'스페인어 탐색 시작','start_desc'=>'어떤 단어든 검색하고, 의미를 발견하고, 사용법을 배우세요 — 무료로.','open_dict'=>'사전 열기','related'=>'관련 페이지','stats_words'=>'스페인어 단어','stats_defs'=>'정의','stats_trans'=>'번역','stats_langs'=>'언어','stats_conj'=>'활용형'],
    'ru' => ['what_you_get'=>'Что вы получаете','try_words'=>'Попробуйте эти слова','faq_title'=>'Часто задаваемые вопросы','start_exploring'=>'Начните изучать испанский','start_desc'=>'Ищите любое слово, узнавайте его значение и учитесь его использовать — бесплатно.','open_dict'=>'Открыть словарь','related'=>'Похожие страницы','stats_words'=>'Испанских слов','stats_defs'=>'Определений','stats_trans'=>'Переводов','stats_langs'=>'Языков','stats_conj'=>'Спряжений'],
    'ja' => ['what_you_get'=>'機能紹介','try_words'=>'これらの単語を試す','faq_title'=>'よくある質問','start_exploring'=>'スペイン語を探索する','start_desc'=>'任意の単語を検索し、意味を発見し、使い方を学ぶ — 無料で。','open_dict'=>'辞書を開く','related'=>'関連ページ','stats_words'=>'スペイン語単語','stats_defs'=>'定義','stats_trans'=>'翻訳','stats_langs'=>'言語','stats_conj'=>'活用形'],
    'es' => ['what_you_get'=>'Lo que obtienes','try_words'=>'Prueba estas palabras','faq_title'=>'Preguntas frecuentes','start_exploring'=>'Empieza a explorar','start_desc'=>'Busca cualquier palabra, descubre su significado y aprende a usarla — gratis.','open_dict'=>'Abrir el diccionario','related'=>'Relacionado','stats_words'=>'Palabras en espanol','stats_defs'=>'Definiciones','stats_trans'=>'Traducciones','stats_langs'=>'Idiomas','stats_conj'=>'Conjugaciones'],
    'it' => ['what_you_get'=>'Cosa ottieni','try_words'=>'Prova queste parole','faq_title'=>'Domande frequenti','start_exploring'=>'Inizia a esplorare lo spagnolo','start_desc'=>'Cerca qualsiasi parola, scopri il suo significato e impara come usarla — gratis.','open_dict'=>'Apri il dizionario','related'=>'Correlati','stats_words'=>'Parole spagnole','stats_defs'=>'Definizioni','stats_trans'=>'Traduzioni','stats_langs'=>'Lingue','stats_conj'=>'Coniugazioni'],
    'fr' => ['what_you_get'=>'Ce que vous obtenez','try_words'=>'Essayez ces mots','faq_title'=>'Questions frequentes','start_exploring'=>'Commencez a explorer l\'espagnol','start_desc'=>'Recherchez n\'importe quel mot, decouvrez sa signification et apprenez comment l\'utiliser — gratuitement.','open_dict'=>'Ouvrir le dictionnaire','related'=>'Pages liees','stats_words'=>'Mots espagnols','stats_defs'=>'Definitions','stats_trans'=>'Traductions','stats_langs'=>'Langues','stats_conj'=>'Conjugaisons'],
    'pt' => ['what_you_get'=>'O que voce recebe','try_words'=>'Experimente estas palavras','faq_title'=>'Perguntas frequentes','start_exploring'=>'Comece a explorar o espanhol','start_desc'=>'Pesquise qualquer palavra, descubra seu significado e aprenda como usa-la — de graca.','open_dict'=>'Abrir o dicionario','related'=>'Relacionado','stats_words'=>'Palavras em espanhol','stats_defs'=>'Definicoes','stats_trans'=>'Traducoes','stats_langs'=>'Idiomas','stats_conj'=>'Conjugacoes'],
];
$t = $ui[$pageLang] ?? $ui['en'];

// ── Get sample words for dynamic content ────────────────────────────
$sampleWords = [];
try {
    $sampleLang = ($searchLang === 'en') ? 'en' : 'es';
    // Fast random sample: use random offset instead of ORDER BY RAND() on millions of rows
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM dict_words w INNER JOIN dict_definitions d ON d.word_id = w.id WHERE w.lang_code = ?");
    $countStmt->execute([$sampleLang]);
    $total = (int) $countStmt->fetchColumn();
    $offset = $total > 6 ? random_int(0, $total - 6) : 0;
    $stmt = $pdo->prepare("
        SELECT w.word, d.definition
        FROM dict_words w
        INNER JOIN dict_definitions d ON d.word_id = w.id
        WHERE w.lang_code = ?
        LIMIT 6 OFFSET $offset
    ");
    $stmt->execute([$sampleLang]);
    $sampleWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($pageLang) ?>"<?= in_array($pageLang, ['ar','he','fa','ur']) ? ' dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page['meta']) ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $canonical ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page['meta']) ?>">
    <meta property="og:site_name" content="Babel Free">
    <meta property="og:image" content="https://babelfree.com/assets/og-babel.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page['meta']) ?>">
    <meta name="twitter:image" content="https://babelfree.com/assets/og-babel.png">
    <meta name="theme-color" content="#F4A5A5">
    <link rel="icon" type="image/png" href="/assets/tower-logo.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebPage",
          "@id": "<?= $canonical ?>",
          "url": "<?= $canonical ?>",
          "name": <?= json_encode($page['title']) ?>,
          "description": <?= json_encode($page['meta']) ?>,
          "isPartOf": {"@id": "https://babelfree.com/#website"},
          "about": {"@id": "https://babelfree.com/#organization"},
          "dateModified": "<?= date('Y-m-d') ?>",
          "inLanguage": "en",
          "breadcrumb": {"@id": "<?= $canonical ?>#breadcrumb"}
        },
        {
          "@type": "BreadcrumbList",
          "@id": "<?= $canonical ?>#breadcrumb",
          "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Babel Free", "item": "https://babelfree.com/"},
            {"@type": "ListItem", "position": 2, "name": "Dictionary", "item": "https://babelfree.com/dictionary"},
            {"@type": "ListItem", "position": 3, "name": <?= json_encode($page['h1']) ?>, "item": "<?= $canonical ?>"}
          ]
        }
        <?php if (!empty($page['faq'])): ?>,
        {
          "@type": "FAQPage",
          "mainEntity": [
            <?php $faqItems = []; foreach ($page['faq'] as $q => $a): ?>
            <?php $faqItems[] = '{"@type":"Question","name":' . json_encode($q) . ',"acceptedAnswer":{"@type":"Answer","text":' . json_encode($a) . '}}'; ?>
            <?php endforeach; echo implode(",\n            ", $faqItems); ?>
          ]
        }
        <?php endif; ?>
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lucida+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/footer.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lucida Sans', Arial, sans-serif;
            line-height: 1.6; color: #333; background: #fff;
            min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden;
        }

        /* Header */
        .header { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; top: 0; width: 100%; z-index: 1000; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo-brand { display: flex; align-items: center; gap: 1rem; text-decoration: none; }
        .main-logo { height: 70px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; }
        .main-logo img { height: 100%; width: auto; object-fit: contain; max-width: none; }
        .nav-menu { display: flex; gap: 2rem; list-style: none; align-items: center; }
        .nav-link { color: #1a1a1a; text-decoration: none; font-weight: 600; transition: color 0.3s; position: relative; }
        .nav-link:hover { color: #F4A5A5; }
        .nav-link::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 0; height: 2px; background: #F4A5A5; transition: width 0.3s; }
        .nav-link:hover::after { width: 100%; }
        .nav-link.active { color: #F4A5A5; }
        .nav-link.active::after { width: 100%; }
        .cta-nav { background: #F4A5A5; color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(244,165,165,0.3); }
        .cta-nav:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(244,165,165,0.4); }
        .mobile-menu-toggle { display: none; flex-direction: column; cursor: pointer; padding: 0.5rem; }
        .menu-bar { width: 25px; height: 3px; background: #1a1a1a; margin: 3px 0; transition: 0.3s; border-radius: 2px; }
        .mobile-menu { display: none; position: fixed; top: 100px; left: 0; width: 100%; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 2rem; z-index: 999; }
        .mobile-menu.active { display: block; }
        .mobile-menu-item { display: block; color: #1a1a1a; text-decoration: none; padding: 1rem 0; border-bottom: 1px solid #ecf0f1; font-weight: 600; }

        /* Hero */
        .seo-hero {
            padding: 8rem 2rem 3rem;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
            text-align: center; position: relative; overflow: hidden;
        }
        .seo-hero::before {
            content: '📖'; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); font-size: 15rem; opacity: 0.04; pointer-events: none;
        }
        .seo-hero-content { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; }
        .seo-hero h1 {
            font-family: 'Bebas Neue', cursive; font-size: clamp(2.5rem, 5vw, 3.5rem);
            color: #fff; letter-spacing: 3px; margin-bottom: 1rem;
        }
        .seo-hero .intro { font-size: 1.15rem; color: rgba(255,255,255,0.8); line-height: 1.8; margin-bottom: 2rem; }

        /* Search box */
        .seo-search { max-width: 600px; margin: 0 auto; position: relative; }
        .seo-search input {
            width: 100%; padding: 1rem 1.5rem; padding-right: 4rem;
            font-size: 1.1rem; border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50px; background: rgba(255,255,255,0.1);
            color: #fff; outline: none; transition: all 0.3s;
        }
        .seo-search input::placeholder { color: rgba(255,255,255,0.5); }
        .seo-search input:focus { border-color: #F4A5A5; background: rgba(255,255,255,0.15); }
        .seo-search button {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: #F4A5A5; border: none; color: white; padding: 0.65rem 1.2rem;
            border-radius: 50px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.3s;
        }
        .seo-search button:hover { background: #e8989e; }

        /* Stats bar */
        .stats-bar {
            display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap;
            padding: 1.5rem 2rem; background: #f8f9fa; border-bottom: 1px solid #e9ecef;
        }
        .stat { text-align: center; }
        .stat-num { font-family: 'Bebas Neue', cursive; font-size: 1.8rem; color: #0f3460; letter-spacing: 1px; }
        .stat-label { font-size: 0.85rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Content sections */
        .seo-content { max-width: 1000px; margin: 0 auto; padding: 3rem 2rem; flex: 1; }

        /* Features grid */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0 3rem; }
        .feature-card {
            background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
            padding: 1.5rem; transition: all 0.3s;
        }
        .feature-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-3px); }
        .feature-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .feature-card h3 { font-size: 1.1rem; color: #1a1a2e; margin-bottom: 0.5rem; }
        .feature-card p { font-size: 0.95rem; color: #666; }

        /* Sample words */
        .sample-section { margin: 2rem 0 3rem; }
        .sample-section h2 { font-family: 'Bebas Neue', cursive; font-size: 2rem; color: #1a1a2e; letter-spacing: 1px; margin-bottom: 1rem; }
        .sample-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
        .sample-word {
            background: linear-gradient(135deg, #f8f9fa, #fff); border: 1px solid #e9ecef;
            border-radius: 10px; padding: 1.2rem; cursor: pointer; transition: all 0.3s; text-decoration: none; color: inherit;
        }
        .sample-word:hover { border-color: #F4A5A5; box-shadow: 0 4px 15px rgba(244,165,165,0.15); }
        .sample-word strong { color: #0f3460; font-size: 1.1rem; }
        .sample-word span { display: block; color: #666; font-size: 0.9rem; margin-top: 0.3rem; }

        /* FAQ */
        .faq-section { margin: 2rem 0 3rem; }
        .faq-section h2 { font-family: 'Bebas Neue', cursive; font-size: 2rem; color: #1a1a2e; letter-spacing: 1px; margin-bottom: 1rem; }
        .faq-item { border-bottom: 1px solid #e9ecef; padding: 1.5rem 0; }
        .faq-item:last-child { border-bottom: none; }
        .faq-q { font-weight: 600; color: #1a1a2e; font-size: 1.05rem; margin-bottom: 0.5rem; cursor: pointer; }
        .faq-q::before { content: '▸ '; color: #F4A5A5; }
        .faq-a { color: #555; line-height: 1.7; }

        /* CTA */
        .cta-section {
            text-align: center; padding: 3rem 2rem; margin: 2rem 0;
            background: linear-gradient(135deg, #1a1a2e, #0f3460); border-radius: 16px; color: white;
        }
        .cta-section h2 { font-family: 'Bebas Neue', cursive; font-size: 2rem; letter-spacing: 2px; margin-bottom: 0.5rem; }
        .cta-section p { color: rgba(255,255,255,0.8); margin-bottom: 1.5rem; }
        .cta-btn {
            display: inline-block; background: #F4A5A5; color: white; padding: 1rem 2.5rem;
            border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem;
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(244,165,165,0.4);
        }
        .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(244,165,165,0.5); }

        /* Related pages */
        .related-section { margin: 2rem 0 1rem; }
        .related-section h2 { font-family: 'Bebas Neue', cursive; font-size: 1.5rem; color: #1a1a2e; letter-spacing: 1px; margin-bottom: 1rem; }
        .related-links { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .related-links a {
            display: inline-block; padding: 0.5rem 1rem; background: #f8f9fa;
            border: 1px solid #e9ecef; border-radius: 25px; text-decoration: none;
            color: #0f3460; font-size: 0.9rem; transition: all 0.3s;
        }
        .related-links a:hover { background: #F4A5A5; color: white; border-color: #F4A5A5; }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .mobile-menu-toggle { display: flex; }
            .stats-bar { gap: 1.5rem; }
            .seo-hero { padding: 7rem 1.5rem 2.5rem; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <nav class="nav-container">
        <a href="/" class="logo-brand">
            <div class="main-logo"><img src="/assets/logo.png" alt="Babel Free"></div>
        </a>
        <ul class="nav-menu">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/learn-spanish" class="nav-link">Learn Spanish</a></li>
            <li><a href="/dictionary" class="nav-link active">Dictionary</a></li>
            <li><a href="/storymap" class="nav-link">Story Map</a></li>
            <li><a href="/login" class="cta-nav">Start Learning</a></li>
        </ul>
        <div class="mobile-menu-toggle" onclick="document.querySelector('.mobile-menu').classList.toggle('active')">
            <div class="menu-bar"></div><div class="menu-bar"></div><div class="menu-bar"></div>
        </div>
    </nav>
</header>
<div class="mobile-menu">
    <a href="/" class="mobile-menu-item">Home</a>
    <a href="/learn-spanish" class="mobile-menu-item">Learn Spanish</a>
    <a href="/dictionary" class="mobile-menu-item">Dictionary</a>
    <a href="/storymap" class="mobile-menu-item">Story Map</a>
    <a href="/login" class="mobile-menu-item" style="color:#F4A5A5;">Start Learning</a>
</div>

<!-- Hero with search -->
<section class="seo-hero">
    <div class="seo-hero-content">
        <h1><?= htmlspecialchars($page['h1']) ?></h1>
        <p class="intro"><?= $page['intro'] ?></p>
        <div class="seo-search">
            <input type="text" id="seoSearchInput" placeholder="<?= htmlspecialchars($searchPlaceholder) ?>" autocomplete="off" autofocus>
            <button onclick="doSearch()">Search</button>
        </div>
    </div>
</section>

<!-- Stats bar -->
<div class="stats-bar">
    <div class="stat"><div class="stat-num"><?= $esWords ?></div><div class="stat-label"><?= $t['stats_words'] ?></div></div>
    <div class="stat"><div class="stat-num"><?= $totalDefs ?></div><div class="stat-label"><?= $t['stats_defs'] ?></div></div>
    <div class="stat"><div class="stat-num"><?= $totalTrans ?></div><div class="stat-label"><?= $t['stats_trans'] ?></div></div>
    <div class="stat"><div class="stat-num"><?= $langCount ?></div><div class="stat-label"><?= $t['stats_langs'] ?></div></div>
    <div class="stat"><div class="stat-num"><?= $conjugations ?></div><div class="stat-label"><?= $t['stats_conj'] ?></div></div>
</div>

<main class="seo-content">

    <!-- Features -->
    <section>
        <h2 style="font-family:'Bebas Neue',cursive;font-size:2rem;color:#1a1a2e;letter-spacing:1px;margin-bottom:1rem;"><?= $t['what_you_get'] ?></h2>
        <div class="features-grid">
            <?php foreach ($page['features'] as $fKey): ?>
            <?php $f = $featureMap[$fKey] ?? null; if (!$f) continue; ?>
            <div class="feature-card">
                <div class="feature-icon"><?= $f['icon'] ?></div>
                <h3><?= $f['title'] ?></h3>
                <p><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sample words -->
    <?php if (!empty($sampleWords)): ?>
    <section class="sample-section">
        <h2><?= $t['try_words'] ?></h2>
        <div class="sample-grid">
            <?php foreach ($sampleWords as $sw): ?>
            <a class="sample-word" href="/dictionary/<?= $searchLang ?>/<?= $searchPrefix ?><?= rawurlencode(mb_strtolower($sw['word'])) ?>">
                <strong><?= htmlspecialchars($sw['word']) ?></strong>
                <span><?= htmlspecialchars(mb_substr($sw['definition'], 0, 120)) ?><?= mb_strlen($sw['definition']) > 120 ? '...' : '' ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (!empty($page['faq'])): ?>
    <section class="faq-section">
        <h2><?= $t['faq_title'] ?></h2>
        <?php foreach ($page['faq'] as $q => $a): ?>
        <div class="faq-item">
            <div class="faq-q"><?= htmlspecialchars($q) ?></div>
            <div class="faq-a"><?= htmlspecialchars($a) ?></div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="cta-section">
        <h2><?= $t['start_exploring'] ?></h2>
        <p><?= $t['start_desc'] ?></p>
        <a href="/dictionary" class="cta-btn"><?= $t['open_dict'] ?></a>
    </section>

    <!-- Related pages -->
    <section class="related-section">
        <h2><?= $t['related'] ?></h2>
        <div class="related-links">
            <?php
            $relatedSlugs = array_keys($pages);
            shuffle($relatedSlugs);
            $shown = 0;
            foreach ($relatedSlugs as $rs):
                if ($rs === $slug) continue;
                if ($shown >= 8) break;
            ?>
            <a href="/<?= $rs ?>"><?= htmlspecialchars($pages[$rs]['h1']) ?></a>
            <?php $shown++; endforeach; ?>
            <a href="/dictionary">Dictionary Home</a>
            <a href="/learn-spanish">Learn Spanish</a>
        </div>
    </section>

</main>

<!-- Footer -->
<footer class="site-footer light">
    <div class="footer-grid">
        <div class="footer-brand">
            <p class="footer-logo"><img src="/assets/tower-logo.png" alt="Babel Free" loading="lazy"></p>
            <p class="footer-desc">Language courses, professional translation, and immersive Spanish learning through El Viaje del Jaguar — a CEFR-aligned journey powered by Colombian culture and storytelling.</p>
        </div>
        <nav class="footer-links" aria-label="Site map">
            <p class="footer-heading">Explore</p>
            <a href="/services">Language courses</a><a href="/elviajedeljaguar">El Viaje del Jaguar</a><a href="/dictionary">Dictionaries</a><a href="/languages">100+ languages</a><a href="/blog">Blog</a>
        </nav>
        <div class="footer-contact">
            <p class="footer-heading">Contact us</p>
            <a href="/translation-services" class="footer-cta">Translation services</a><a href="/contact" class="footer-cta">Message form</a><a href="mailto:info@babelfree.com" class="footer-cta-secondary">Email</a>
        </div>
    </div>
    <div class="footer-bottom"><p>&copy; 2026 Babel Free &middot; <a href="/privacy">Privacy</a></p></div>
</footer>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/service-worker.js');}</script>

<script>
function doSearch() {
    const q = document.getElementById('seoSearchInput').value.trim();
    if (q) window.location.href = '/dictionary/<?= $searchLang ?>/<?= $searchPrefix ?>' + encodeURIComponent(q.toLowerCase());
}
document.getElementById('seoSearchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doSearch();
});
</script>

</body>
</html>
