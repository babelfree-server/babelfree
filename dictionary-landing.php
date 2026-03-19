<?php
/**
 * Dictionary Language Landing Page — Server-rendered, SEO-optimized
 * Renders /dictionary/{lang}/ with rich content, popular words, and stats.
 * Variables available: $landingLang, $landingI18n, $allI18n, $pdo
 */

// ── Language metadata ────────────────────────────────────────────────
$langName = $landingI18n['lang_name'] ?? ucfirst($landingLang);
$heroTitle = $landingI18n['hero_title'] ?? 'Dictionary';
$heroIntro = $landingI18n['hero_intro'] ?? '';
$searchPlaceholder = $landingI18n['search_placeholder'] ?? 'Search...';
$urlPrefix = $landingI18n['url_prefix'] ?? '';

// ── Stats ────────────────────────────────────────────────────────────
$wordCount = (int) $pdo->prepare('SELECT COUNT(*) FROM dict_words WHERE lang_code = ?')
    ->execute([$landingLang]) ? $pdo->query("SELECT COUNT(*) FROM dict_words WHERE lang_code = '{$landingLang}'")->fetchColumn() : 0;

$defCount = (int) $pdo->query("SELECT COUNT(*) FROM dict_definitions WHERE lang_code = '{$landingLang}'")->fetchColumn();

$transCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM dict_translations t JOIN dict_words w ON t.source_word_id = w.id WHERE w.lang_code = '{$landingLang}'"
)->fetchColumn();

// ── Popular words (sample with definitions) ─────────────────────────
$stmt = $pdo->prepare(
    'SELECT DISTINCT w.word, w.part_of_speech, w.cefr_level, d.definition
     FROM dict_words w
     INNER JOIN dict_definitions d ON d.word_id = w.id AND d.lang_code = ?
     WHERE w.lang_code = ?
     ORDER BY w.frequency_rank ASC, w.word ASC
     LIMIT 24'
);
$stmt->execute([$landingLang, $landingLang]);
$popularWords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no definitions yet, just show words
if (empty($popularWords)) {
    $stmt = $pdo->prepare(
        'SELECT w.word, w.part_of_speech, w.cefr_level
         FROM dict_words w
         WHERE w.lang_code = ?
         ORDER BY w.frequency_rank ASC, w.word ASC
         LIMIT 24'
    );
    $stmt->execute([$landingLang]);
    $popularWords = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── CEFR distribution ────────────────────────────────────────────────
$cefrDist = [];
$stmt = $pdo->prepare(
    'SELECT cefr_level, COUNT(*) as cnt FROM dict_words
     WHERE lang_code = ? AND cefr_level IS NOT NULL
     GROUP BY cefr_level ORDER BY cefr_level'
);
$stmt->execute([$landingLang]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cefrDist[$row['cefr_level']] = (int)$row['cnt'];
}

// ── Language-specific SEO content ────────────────────────────────────
$langSeoContent = [
    'en' => [
        'keywords' => [
            'learn spanish', 'how to learn spanish', 'best way to learn spanish',
            'how to learn spanish fast', 'learn spanish free', 'how long does it take to learn spanish',
            'to learn in spanish', 'best app to learn spanish', 'learn in spanish',
            'learn to speak spanish', 'fastest way to learn spanish', 'learn spanish fast',
            'best apps to learn spanish', 'learn spanish online',
        ],
        'sections' => [
            ['title' => 'Learn Spanish Free — Babel Free Dictionary',
             'text' => 'Looking for the best way to learn Spanish? Babel Free offers a free Spanish-English dictionary with over 26,000 words, definitions, equivalents, and CEFR levels from A1 to C2. Whether you want to learn Spanish online or learn to speak Spanish at your own pace, this dictionary helps you build vocabulary step by step.'],
            ['title' => 'How to Learn Spanish Fast',
             'text' => 'How to learn Spanish fast? Start with the most common words. Every entry is tagged with a CEFR level so you can focus on beginner vocabulary first, then progress naturally. Pair this dictionary with the best apps to learn Spanish — or use it as a standalone tool. How long does it take to learn Spanish? With consistent daily practice, you can reach conversational fluency in months.'],
            ['title' => 'Best Way to Learn Spanish Online',
             'text' => 'The fastest way to learn Spanish is to combine structured courses with a reliable dictionary. Learn Spanish free with Babel Free: look up any word, see its definition in context, explore equivalents across 12 languages, and track your level. This is not just a dictionary — it is your companion to learn in Spanish.'],
        ],
    ],
    'es' => [
        'keywords' => [
            'aprender español', 'aprendiendo espanol', 'aprendo espanol',
            'aprender español online', 'aprender español para extranjeros',
            'aprender español gratis', 'clases español online', 'estudiar español',
            'curso español online', 'curso de español online', 'aprender español desde cero',
            'quiero aprender español', 'clases online español', 'curso de español gratis',
        ],
        'sections' => [
            ['title' => 'Diccionario para aprender español — Babel Free',
             'text' => 'Babel Free es un diccionario gratuito con más de 26.000 palabras en español, definiciones, equivalentes en 12 idiomas y niveles CEFR. Si estás aprendiendo español o quieres aprender español desde cero, aquí encontrarás vocabulario organizado por nivel, desde A1 hasta C2.'],
            ['title' => 'Aprender español online gratis',
             'text' => '¿Quiero aprender español pero no sé por dónde empezar? Este diccionario es tu punto de partida. Busca cualquier palabra, consulta su definición, descubre sus equivalentes y aprende español gratis sin registro ni pago. Ideal para complementar clases español online o un curso de español online.'],
            ['title' => 'Curso de español y clases para extranjeros',
             'text' => 'Babel Free es un recurso perfecto para aprender español para extranjeros. Cada palabra incluye su nivel CEFR, parte del discurso y ejemplos de uso. Complementa tu curso de español gratis o tus clases online español con un diccionario diseñado para estudiantes de todos los niveles.'],
        ],
    ],
    'ja' => [
        'keywords' => [
            'スペイン語を学ぶ', 'スペイン語学ぶ', 'スペイン語を勉強する',
        ],
        'sections' => [
            ['title' => 'スペイン語を学ぶ — Babel Free 辞書',
             'text' => 'Babel Freeのスペイン語辞書は26,000語以上の単語、定義、翻訳、CEFRレベル（A1〜C2）を収録しています。スペイン語を学ぶ第一歩として、レベル別に整理された語彙を活用してください。初心者から上級者まで、スペイン語を勉強する全ての方に最適な無料オンライン辞書です。'],
            ['title' => 'スペイン語学ぶための効果的な方法',
             'text' => 'スペイン語学ぶには、まず基本的な単語から始めましょう。各単語にはCEFRレベルが付いているので、自分のレベルに合った語彙を見つけることができます。スペイン語を勉強する際は、このスペイン語辞書を日々の学習パートナーとしてお使いください。12言語への翻訳付きで、母語との比較も簡単です。'],
        ],
    ],
    'ko' => [
        'keywords' => ['스페인어', '스페인어 숫자', '스페인어 학습지', '스페인어 회화', '스페인어 배우기',
                       '스페인어 욕', '듀오링고 스페인어', '맛있는 스페인어', '수능 스페인어',
                       '스페인 숫자', '스페인 영어', '스페인어로', '스페인어 맛있다'],
        'sections' => [
            ['title' => '스페인어 사전 - Babel Free', 'text' => 'Babel Free 한국어-스페인어 사전은 26,000개 이상의 스페인어 단어와 정의, 번역, CEFR 레벨 태그를 제공합니다. 스페인어 배우기를 시작하는 학습자부터 수능 스페인어를 준비하는 학생까지 모두를 위한 무료 온라인 사전입니다.'],
            ['title' => '스페인어 회화와 학습', 'text' => '스페인어 회화 능력을 향상시키고 싶으신가요? 각 단어에는 CEFR 등급(A1 입문부터 C2 숙달까지)이 표시되어 있어 자신의 수준에 맞는 어휘를 찾을 수 있습니다. 듀오링고 스페인어와 함께 사용하면 더욱 효과적입니다.'],
            ['title' => '스페인어 숫자와 기본 표현', 'text' => '스페인어 숫자, 인사말, 기본 표현을 쉽게 찾아보세요. 맛있는 스페인어 표현부터 일상 회화까지, 각 단어의 발음, 뜻, 예문을 확인할 수 있습니다.'],
        ],
    ],
    'ar' => [
        'keywords' => [
            'قاموس اسباني عربي', 'تعلم الاسبانية', 'اللغة الاسبانية', 'ترجمة اسباني عربي',
            'كلمات اسبانية', 'ارقام بالاسبانية', 'حروف اللغة الاسبانية', 'قواعد اللغة الاسبانية',
            'تعلم الاسبانية للمبتدئين', 'تعلم الاسبانية من الصفر', 'مترجم اسباني عربي',
            'محادثة اسبانية', 'كلمات اسبانية من اصل عربي', 'جمل بالاسبانية', 'عبارات اسبانية',
        ],
        'sections' => [
            ['title' => 'قاموس اسباني عربي مجاني — Babel Free',
             'text' => 'يقدّم قاموس Babel Free الإسباني-العربي أكثر من 26,000 كلمة مع تعريفات وترجمات ومستويات CEFR (من A1 إلى C2). سواء كنت تبحث عن ترجمة اسباني عربي أو مترجم اسباني عربي دقيق، ستجد هنا كل ما تحتاجه مجاناً.'],
            ['title' => 'تعلم الاسبانية للمبتدئين من الصفر',
             'text' => 'هل تريد تعلم الاسبانية من الصفر؟ يساعدك هذا القاموس على البدء بخطوات واضحة: تعلّم حروف اللغة الاسبانية، ثم الارقام بالاسبانية، ثم الكلمات الأساسية. كل كلمة مصنّفة حسب مستوى CEFR لتتدرج من المبتدئ إلى المتقدم.'],
            ['title' => 'كلمات وجمل وعبارات اسبانية',
             'text' => 'تصفّح آلاف الكلمات الاسبانية مع أمثلة وجمل بالاسبانية وعبارات اسبانية للمحادثة اليومية. اكتشف أيضاً كلمات اسبانية من اصل عربي تربط بين اللغتين تاريخياً.'],
            ['title' => 'قواعد اللغة الاسبانية والمحادثة',
             'text' => 'يتضمن القاموس شروحات لقواعد اللغة الاسبانية وتصريفات الأفعال ونصائح للمحادثة الاسبانية. ابدأ رحلتك في تعلم الاسبانية مع أدوات مصممة خصيصاً للمتحدثين بالعربية.'],
        ],
    ],
    'pt' => [
        'keywords' => [
            'curso de espanhol', 'aulas de espanhol gratis', 'curso de espanhol online gratis',
            'curso de espanhol gratis e online', 'curso de espanhol online e gratis',
            'curso online de espanhol gratis', 'cursos de espanhol gratis online',
            'aulas de espanhol', 'curso em espanhol', 'curso online de espanhol',
            'falar em espanhol', 'espanhol falar', 'espanhol como falar',
            'basico do espanhol', 'espanhol conversacao', 'aulas espanhol',
            'aula em espanhol', 'aprenda espanhol', 'espanhol aprender',
            'espanhol como aprender', 'aprendendo o espanhol', 'sites para aprender espanhol',
            'site aprender espanhol', 'aprender espanhol online', 'aprender espanhol sozinho',
            'aprender falar espanhol', 'espanhol aprender online',
        ],
        'sections' => [
            ['title' => 'Dicionario de espanhol gratis — Babel Free',
             'text' => 'O Babel Free oferece um dicionario de espanhol completo e gratuito com mais de 26.000 palavras, definicoes, equivalentes em 12 idiomas e niveis CEFR (A1 a C2). Se voce quer aprender espanhol online ou esta procurando um curso de espanhol gratis e online, comece pelo vocabulario essencial.'],
            ['title' => 'Aprenda espanhol — aulas e curso online gratis',
             'text' => 'Quer aprender espanhol sozinho? Este dicionario e ideal para quem busca aulas de espanhol gratis ou um curso de espanhol online gratis. Cada palavra inclui nivel CEFR, classe gramatical e equivalentes, para que voce aprenda o basico do espanhol no seu ritmo. Combine com cursos de espanhol gratis online para resultados ainda melhores.'],
            ['title' => 'Falar espanhol — conversacao e pratica',
             'text' => 'Espanhol como falar com confianca? Comece dominando as palavras mais usadas. Use o Babel Free como seu site para aprender espanhol: consulte definicoes, descubra equivalentes e pratique espanhol conversacao diariamente. Aprender falar espanhol fica mais facil quando voce tem as ferramentas certas.'],
        ],
    ],
    'fr' => [
        'keywords' => [
            'apprendre l espagnol', 'cours d espagnol en ligne', 'apprendre l espagnol gratuitement',
            'apprendre l espagnol rapidement', 'cour espagnol', 'duolingo espagnol gratuit',
            'apprendre en espagnol', 'cour espagnol gratuit', 'apprendre l espagnol en ligne',
            'babbel espagnol', 'apprendre espagnol gratuit', 'cours d espagnol gratuit',
            'meilleur site pour apprendre l espagnol gratuitement', 'apprendre l espagnol débutant',
            'cours d espagnol en ligne gratuit',
        ],
        'sections' => [
            ['title' => 'Dictionnaire espagnol gratuit — Babel Free',
             'text' => 'Babel Free propose un dictionnaire espagnol-francais gratuit avec plus de 26 000 mots, definitions, équivalents dans 12 langues et niveaux CECR (A1 a C2). Que vous cherchiez a apprendre l espagnol gratuitement ou un cours d espagnol en ligne, ce dictionnaire est votre point de depart.'],
            ['title' => 'Apprendre l espagnol en ligne — cours gratuit',
             'text' => 'Comment apprendre l espagnol rapidement ? Commencez par les mots les plus courants. Chaque entree est classee par niveau CECR pour que vous puissiez progresser du debutant au niveau avance. Mieux qu un cour espagnol gratuit classique, ce dictionnaire vous accompagne a chaque etape. Ideal en complement de Duolingo espagnol gratuit ou Babbel espagnol.'],
            ['title' => 'Apprendre l espagnol débutant',
             'text' => 'Vous etes debutant et vous voulez apprendre l espagnol en ligne ? Babel Free est le meilleur site pour apprendre l espagnol gratuitement. Consultez definitions, équivalents et exemples. Combinez-le avec un cours d espagnol en ligne gratuit pour apprendre espagnol gratuit a votre rythme.'],
        ],
    ],
    'it' => [
        'keywords' => [
            'impara lo spagnolo', 'impara spagnolo', 'spagnolo imparare',
            'spagnolo impara', 'imparare il spagnolo', 'parlare spagnolo',
            'impara spagnolo velocemente', 'imparare in spagnolo', 'impara spagnolo gratis',
            'impara lo spagnolo pdf', 'imparare velocemente lo spagnolo', 'parlare lo spagnolo',
        ],
        'sections' => [
            ['title' => 'Dizionario spagnolo gratuito — Babel Free',
             'text' => 'Babel Free offre un dizionario spagnolo-italiano gratuito con oltre 26.000 parole, definizioni, traduzioni in 12 lingue e livelli QCER (da A1 a C2). Se vuoi imparare il spagnolo o stai cercando un modo per impara spagnolo gratis, inizia dal vocabolario essenziale.'],
            ['title' => 'Impara lo spagnolo — velocemente e gratis',
             'text' => 'Vuoi impara spagnolo velocemente? Ogni parola nel dizionario include il livello QCER, la categoria grammaticale e le traduzioni. Che tu stia cercando di imparare velocemente lo spagnolo o di parlare spagnolo con sicurezza, Babel Free ti aiuta a costruire il vocabolario passo dopo passo.'],
            ['title' => 'Parlare lo spagnolo — imparare in pratica',
             'text' => 'Parlare lo spagnolo richiede pratica quotidiana. Usa Babel Free come compagno di studio: cerca parole, scopri definizioni e traduzioni, e impara lo spagnolo al tuo ritmo. Imparare in spagnolo diventa semplice quando hai gli strumenti giusti a portata di mano.'],
        ],
    ],
    'de' => [
        'keywords' => [
            'spanisch lernen', 'spanisch lernen kostenlos', 'spanisch lernen app',
            'spanisch lernen für anfänger', 'spanisch lernen buch', 'spanisch lernen kostenlos app',
            'lernen spanisch', 'spanisch lernen online', 'ist spanisch schwer zu lernen',
            'kostenlos spanisch lernen', 'spanisch online lernen', 'spanisch lernen münchen',
            'wie lange braucht man um spanisch zu lernen', 'spanisch online lernen kostenlos',
            'spanisch lernen app kostenlos', 'spanisch lernen kostenlos ohne anmeldung',
            'duolingo spanisch lernen kostenlos', 'spanisch lernen online kostenlos',
            'spanisch lernen nürnberg',
        ],
        'sections' => [
            ['title' => 'Spanisch-Deutsch Wörterbuch — Babel Free',
             'text' => 'Babel Free bietet ein kostenloses Spanisch-Deutsch Wörterbuch mit über 26.000 Wörtern, Definitionen, Übersetzungen in 12 Sprachen und GER-Stufen (A1 bis C2). Ob Sie Spanisch lernen für Anfänger oder fortgeschrittene Vokabeln suchen — hier finden Sie alles kostenlos und ohne Anmeldung.'],
            ['title' => 'Spanisch lernen kostenlos online',
             'text' => 'Spanisch lernen kostenlos ohne Anmeldung? Mit Babel Free ist das möglich. Jedes Wort ist mit einem GER-Niveau gekennzeichnet, damit Sie Schritt für Schritt lernen können. Nutzen Sie dieses Wörterbuch als Ergänzung zu Duolingo Spanisch lernen kostenlos oder jeder anderen Spanisch lernen App. Ist Spanisch schwer zu lernen? Mit den richtigen Werkzeugen nicht.'],
            ['title' => 'Spanisch online lernen — Wortschatz aufbauen',
             'text' => 'Wie lange braucht man um Spanisch zu lernen? Das hängt von der täglichen Übung ab. Babel Free hilft Ihnen, Spanisch online lernen kostenlos zu gestalten: Nachschlagen, Definitionen lesen, Übersetzungen entdecken. Perfekt als Begleiter zu einem Spanisch lernen Buch oder Spanisch lernen online Kurs.'],
        ],
    ],
    'nl' => [
        'keywords' => [
            'spaans leren', 'cursus spaans', 'spaanse les', 'spaans cursus',
            'spaans les', 'leer spaans', 'lessen spaans', 'leren spaans',
            'duo lingo spaans', 'spaans leer', 'spaanse woordjes',
            'spaans leren in spanje', 'cursus spaans voor beginners',
            'taalcursus spaans', 'taalreis spaans', 'spaans voor beginners',
            'taalcursus spaans in spanje',
        ],
        'sections' => [
            ['title' => 'Gratis Spaans-Nederlands woordenboek — Babel Free',
             'text' => 'Babel Free biedt een gratis Spaans-Nederlands woordenboek met meer dan 26.000 woorden, definities, vertalingen naar 12 talen en ERK-niveaus (A1 tot C2). Of u nu Spaans wilt leren of Spaanse woordjes wilt opzoeken, dit woordenboek is uw ideale startpunt.'],
            ['title' => 'Spaans leren — cursus en lessen gratis',
             'text' => 'Wilt u Spaans leren maar weet u niet waar te beginnen? Elk woord in dit woordenboek bevat een ERK-niveau, zodat u kunt starten met de basis en stap voor stap kunt opbouwen. Gebruik Babel Free naast Duo Lingo Spaans of een cursus Spaans voor beginners voor de beste resultaten.'],
            ['title' => 'Leer Spaans — voor beginners en gevorderden',
             'text' => 'Leer Spaans op uw eigen tempo met Babel Free. Zoek definities, ontdek vertalingen en bouw uw woordenschat uit. Perfect als aanvulling op een taalcursus Spaans, Spaanse les of een taalreis Spaans in Spanje. Spaans voor beginners was nog nooit zo toegankelijk.'],
        ],
    ],
    'zh' => [
        'keywords' => [
            '學習西班牙語', '西班牙語自學', '西班牙文學習', '西班牙語學習',
            '西班牙語自學教材', '西班牙語補習班', '西班牙文學習網站',
            'lttc西班牙文', '德語法語西班牙語', '法語德語西班牙語',
        ],
        'sections' => [
            ['title' => '免费西班牙语词典 — Babel Free',
             'text' => 'Babel Free提供免费的西班牙语-中文词典，收录超过26,000个词条，包含释义、12种语言的翻译和CEFR等级（A1至C2）。无论您是西班牙語自學还是寻找西班牙文學習網站，这里都是您的最佳起点。'],
            ['title' => '學習西班牙語 — 自学教材与在线资源',
             'text' => '想要西班牙語自學？每个词条都标注了CEFR等级，帮助您从基础词汇开始，循序渐进地學習西班牙語。这个词典可以作为西班牙語自學教材的完美补充，也适合配合西班牙語補習班使用。'],
            ['title' => '西班牙文學習 — 多语言对照',
             'text' => 'Babel Free支持12种语言的翻译对照，包括德語法語西班牙語等多语种比较。西班牙文學習从未如此简单——查词义、看翻译、学语法，一站式免费在线学习平台。'],
        ],
    ],
    'ru' => [
        'keywords' => [
            'испанский', 'испанского языка', 'испанский язык',
            'испанский язык с нуля', 'курсы испанского языка', 'в каких странах говорят на испанском',
            'испанском', 'месяц на испанском', 'испанский онлайн',
            'курсы испанского', 'уроки испанского', 'курсы испанского языка онлайн',
            'учитель испанского языка', 'учить испанский язык', 'испанский с нуля',
        ],
        'sections' => [
            ['title' => 'Бесплатный словарь испанского языка — Babel Free',
             'text' => 'Babel Free — это бесплатный онлайн-словарь испанского языка с более чем 26 000 слов, определениями, переводами на 12 языков и уровнями CEFR (от A1 до C2). Если вы хотите учить испанский язык или начать изучение испанского языка с нуля, здесь вы найдёте всё необходимое.'],
            ['title' => 'Испанский язык с нуля — уроки и курсы онлайн',
             'text' => 'Хотите выучить испанский с нуля? Каждое слово в словаре помечено уровнем CEFR, чтобы вы могли начать с базовой лексики и постепенно переходить к продвинутым темам. Используйте Babel Free как дополнение к курсам испанского языка онлайн или урокам испанского с преподавателем.'],
            ['title' => 'Курсы испанского и практика онлайн',
             'text' => 'Испанский онлайн — это просто с правильными инструментами. Babel Free помогает расширять словарный запас, изучать переводы и понимать грамматику. Узнайте, в каких странах говорят на испанском, выучите месяц на испанском и другие базовые слова. Идеально для тех, кто ищет курсы испанского или хочет учить испанский язык самостоятельно.'],
        ],
    ],
];

$seoContent = $langSeoContent[$landingLang] ?? null;

// ── FAQ structured data (per language) ──────────────────────────────
$faqData = [
    'en' => [
        ['q' => 'How to learn Spanish fast?', 'a' => 'The fastest way to learn Spanish is to combine daily vocabulary practice with immersion. Start with the most common words tagged A1-A2 in our dictionary, then progress to B1 and beyond. With consistent practice, you can reach conversational fluency in 6-12 months.'],
        ['q' => 'What is the best way to learn Spanish for free?', 'a' => 'Babel Free offers a free Spanish dictionary with over 26,000 words, CEFR levels, and equivalents in 12 languages. Combine it with free apps and online courses to learn Spanish free at your own pace.'],
        ['q' => 'How long does it take to learn Spanish?', 'a' => 'For English speakers, reaching conversational Spanish typically takes 400-600 hours of study. Using a CEFR-leveled dictionary like Babel Free helps you track progress from A1 beginner to C2 mastery.'],
    ],
    'es' => [
        ['q' => '¿Cómo aprender español gratis online?', 'a' => 'Babel Free ofrece un diccionario gratuito con más de 26.000 palabras, niveles CEFR y equivalentes en 12 idiomas. Complementa con un curso de español online y clases para aprender español desde cero sin costo.'],
        ['q' => '¿Dónde encontrar un curso de español gratis?', 'a' => 'En Babel Free puedes estudiar español gratis con nuestro diccionario completo. Cada palabra incluye nivel CEFR, definiciones y equivalentes. Ideal para complementar cualquier curso de español online.'],
        ['q' => '¿Es difícil aprender español para extranjeros?', 'a' => 'El español es uno de los idiomas más accesibles para aprender. Con herramientas como el diccionario Babel Free, organizado por niveles CEFR de A1 a C2, aprender español para extranjeros es más fácil que nunca.'],
    ],
    'ja' => [
        ['q' => 'スペイン語を学ぶにはどうすればいいですか？', 'a' => 'まずは基本的な単語から始めましょう。Babel Freeの辞書では26,000語以上がCEFRレベル別に整理されています。A1の基礎単語からスタートして、段階的にスペイン語を勉強することができます。'],
        ['q' => 'スペイン語を勉強するのにおすすめの方法は？', 'a' => '毎日の語彙学習と実践を組み合わせることが効果的です。Babel Freeの無料オンライン辞書を使って、レベルに合った単語を学び、12言語の翻訳で理解を深めましょう。'],
    ],
    'ko' => [
        ['q' => '스페인어 배우기 어렵나요?', 'a' => '스페인어는 비교적 배우기 쉬운 언어입니다. Babel Free 사전에서 26,000개 이상의 단어를 CEFR 레벨별로 학습할 수 있습니다. A1 입문부터 시작하면 체계적으로 스페인어를 배울 수 있습니다.'],
        ['q' => '무료로 스페인어 회화를 배울 수 있나요?', 'a' => '네, Babel Free에서 무료로 스페인어 단어, 정의, 번역을 검색할 수 있습니다. 듀오링고 스페인어와 함께 사용하면 스페인어 회화 실력을 더 빠르게 향상시킬 수 있습니다.'],
        ['q' => '수능 스페인어 준비에 도움이 되나요?', 'a' => '네, 각 단어에 CEFR 등급이 표시되어 있어 수능 스페인어 대비에 필요한 어휘를 체계적으로 학습할 수 있습니다.'],
    ],
    'ar' => [
        ['q' => 'كيف أتعلم الاسبانية من الصفر؟', 'a' => 'ابدأ بتعلم حروف اللغة الاسبانية والارقام بالاسبانية، ثم انتقل إلى الكلمات الأساسية. قاموس Babel Free يصنف أكثر من 26,000 كلمة حسب مستوى CEFR لتتدرج من المبتدئ إلى المتقدم.'],
        ['q' => 'هل يوجد قاموس اسباني عربي مجاني؟', 'a' => 'نعم، يقدم Babel Free قاموس اسباني عربي مجاني بالكامل مع تعريفات وترجمات ومستويات CEFR. يمكنك استخدامه كمترجم اسباني عربي دقيق ومجاني.'],
        ['q' => 'ما هي أفضل طريقة لتعلم الاسبانية للمبتدئين؟', 'a' => 'أفضل طريقة هي البدء بالكلمات الأساسية والجمل البسيطة. استخدم قاموس Babel Free لتعلم كلمات اسبانية جديدة يومياً مع تعريفاتها وترجماتها العربية.'],
    ],
    'pt' => [
        ['q' => 'Como aprender espanhol sozinho?', 'a' => 'Com o dicionário Babel Free você pode aprender espanhol sozinho e de graça. São mais de 26.000 palavras com definições, traduções e níveis CEFR. Comece pelo básico do espanhol e avance no seu ritmo.'],
        ['q' => 'Onde encontrar um curso de espanhol online grátis?', 'a' => 'O Babel Free oferece um dicionário completo como base para seu curso de espanhol online grátis. Combine com aulas de espanhol grátis e pratique espanhol conversação diariamente.'],
        ['q' => 'Qual o melhor site para aprender espanhol?', 'a' => 'O Babel Free é um dos melhores sites para aprender espanhol, com mais de 26.000 palavras organizadas por nível CEFR, traduções em 12 idiomas e acesso totalmente gratuito.'],
    ],
    'fr' => [
        ['q' => "Comment apprendre l'espagnol gratuitement ?", 'a' => "Babel Free propose un dictionnaire espagnol-français gratuit avec plus de 26 000 mots et niveaux CECR. C'est le meilleur site pour apprendre l'espagnol gratuitement, en complément de Duolingo espagnol gratuit ou Babbel espagnol."],
        ['q' => "Comment apprendre l'espagnol rapidement ?", 'a' => "Pour apprendre l'espagnol rapidement, commencez par les mots les plus fréquents (niveau A1-A2) puis progressez vers B1 et au-delà. Le dictionnaire Babel Free classe chaque mot par niveau CECR pour un apprentissage structuré."],
        ['q' => "Où trouver un cours d'espagnol en ligne gratuit ?", 'a' => "Babel Free offre un dictionnaire complet comme base pour votre cours d'espagnol en ligne gratuit. Chaque mot inclut définition, équivalents et niveau CECR, du débutant au niveau avancé."],
    ],
    'it' => [
        ['q' => 'Come imparare lo spagnolo gratis?', 'a' => 'Con Babel Free puoi imparare lo spagnolo gratis: più di 26.000 parole con definizioni, traduzioni in 12 lingue e livelli QCER. Inizia dalle parole di livello A1 e progredisci al tuo ritmo.'],
        ['q' => 'Quanto tempo ci vuole per imparare lo spagnolo?', 'a' => 'Con pratica quotidiana, puoi raggiungere un livello conversazionale in 6-12 mesi. Il dizionario Babel Free ti aiuta a imparare spagnolo velocemente organizzando il vocabolario per livello QCER.'],
        ['q' => "Qual è il modo migliore per parlare spagnolo?", 'a' => 'Per parlare spagnolo con sicurezza, combina lo studio del vocabolario con la pratica. Usa Babel Free per imparare in spagnolo: cerca parole, leggi definizioni e scopri traduzioni ogni giorno.'],
    ],
    'de' => [
        ['q' => 'Ist Spanisch schwer zu lernen?', 'a' => 'Spanisch ist eine der zugänglichsten Sprachen für Deutschsprachige. Mit dem Babel Free Wörterbuch können Sie Spanisch lernen kostenlos — jedes Wort ist mit einem GER-Niveau gekennzeichnet, vom Anfänger (A1) bis zum Experten (C2).'],
        ['q' => 'Wie lange braucht man um Spanisch zu lernen?', 'a' => 'Mit regelmäßigem Üben können Sie in 6-12 Monaten Konversationsniveau erreichen. Babel Free hilft Ihnen, Spanisch online lernen kostenlos zu gestalten, mit über 26.000 Wörtern und GER-Stufen.'],
        ['q' => 'Wo kann man kostenlos Spanisch lernen?', 'a' => 'Babel Free bietet ein kostenloses Spanisch-Deutsch Wörterbuch mit über 26.000 Wörtern. Nutzen Sie es als Ergänzung zu Duolingo Spanisch lernen kostenlos oder jeder anderen Spanisch lernen App kostenlos.'],
    ],
    'nl' => [
        ['q' => 'Hoe kan ik Spaans leren?', 'a' => 'Met Babel Free kunt u gratis Spaans leren. Ons woordenboek bevat meer dan 26.000 woorden met ERK-niveaus, definities en vertalingen. Begin met de basis en bouw stap voor stap uw woordenschat op.'],
        ['q' => 'Waar vind ik een cursus Spaans voor beginners?', 'a' => 'Babel Free is het ideale startpunt voor Spaans voor beginners. Elk woord bevat een ERK-niveau, zodat u kunt beginnen met A1 en geleidelijk kunt opbouwen. Combineer het met Duo Lingo Spaans voor de beste resultaten.'],
        ['q' => 'Kan ik Spaans leren in Spanje?', 'a' => 'Een taalreis Spaans in Spanje is een uitstekende manier om te leren. Bereid u voor met het Babel Free woordenboek: leer Spaanse woordjes, oefen met vertalingen en start uw taalcursus Spaans goed voorbereid.'],
    ],
    'zh' => [
        ['q' => '如何自学西班牙语？', 'a' => '使用Babel Free免费西班牙语词典，超过26,000个词条按CEFR等级分类。从A1基础词汇开始，逐步提升到高级水平。西班牙語自學从未如此简单。'],
        ['q' => '有哪些好的西班牙文學習網站？', 'a' => 'Babel Free是一个优秀的西班牙文學習網站，提供释义、12种语言翻译和CEFR等级标签。可以作为西班牙語自學教材的在线补充工具。'],
        ['q' => '学西班牙语难吗？', 'a' => '西班牙语是世界上最容易学习的语言之一。通过Babel Free词典按CEFR等级系统學習西班牙語，从基础到高级循序渐进。'],
    ],
    'ru' => [],
];

// ── Language-specific SEO descriptions ──────────────────────────────
$seoDescriptions = [
    'es' => 'Diccionario para aprender español gratis: más de 26,000 palabras, definiciones, conjugaciones y niveles CEFR. Aprender español online desde cero con curso de español gratis.',
    'en' => 'Learn Spanish free with 26,000+ words, definitions, equivalents, and CEFR levels. The best way to learn Spanish online — from beginner to fluent. Learn to speak Spanish fast.',
    'fr' => 'Dictionnaire espagnol-francais gratuit: plus de 26 000 mots avec definitions et niveaux CECR. Apprendre l espagnol gratuitement en ligne — cours d espagnol gratuit pour debutants.',
    'de' => 'Kostenloses Spanisch-Deutsch Wörterbuch: über 26.000 Wörter mit Definitionen und GER-Stufen. Spanisch lernen kostenlos online — für Anfänger und Fortgeschrittene.',
    'pt' => 'Dicionario espanhol-portugues gratis: mais de 26.000 palavras com definicoes e niveis CEFR. Curso de espanhol online gratis — aprenda espanhol, aulas e conversacao.',
    'it' => 'Dizionario spagnolo-italiano gratuito: oltre 26.000 parole con definizioni e livelli QCER. Impara lo spagnolo gratis — parlare spagnolo, imparare velocemente.',
    'nl' => 'Gratis Spaans-Nederlands woordenboek: meer dan 26.000 woorden met definities en ERK-niveaus. Spaans leren, cursus Spaans voor beginners, Spaanse woordjes en lessen.',
    'ru' => 'Бесплатный словарь испанского языка: более 26 000 слов, определения, переводы и уровни CEFR. Испанский язык с нуля — курсы испанского онлайн, уроки и практика.',
    'zh' => '免费西班牙语-中文词典：超过26,000个词条，包含释义、翻译和CEFR等级。學習西班牙語、西班牙語自學、西班牙文學習網站——免费在线学习平台。',
    'ja' => '無料スペイン語辞書：26,000語以上の単語、定義、翻訳、CEFRレベル付き。スペイン語を学ぶ・スペイン語を勉強する方のための日本語対応オンライン辞書。',
    'ko' => '무료 한국어-스페인어 사전: 26,000개 이상의 단어, 정의, 번역, CEFR 등급 포함. 스페인어 배우기, 스페인어 회화, 스페인어 숫자를 검색하세요.',
    'ar' => 'قاموس اسباني عربي مجاني: أكثر من 26,000 كلمة مع تعريفات وترجمات ومستويات CEFR. تعلم الاسبانية للمبتدئين — كلمات، ارقام، حروف، قواعد ومحادثة اسبانية.',
];

$metaDesc = $seoDescriptions[$landingLang] ?? "Free {$langName} dictionary by Babel Free. {$wordCount}+ words with definitions, equivalents, and CEFR levels.";
$pageTitle = "{$langName} {$heroTitle} | Babel Free — " . number_format($wordCount) . "+ words";

$canonicalUrl = "https://babelfree.com/dictionary/{$landingLang}/";

// ── Language family colors ──────────────────────────────────────────
$langColors = [
    'es'=>'#e07a5f','fr'=>'#d4726a','it'=>'#c75d4a','pt'=>'#e08e6d',
    'en'=>'#4a7fb5','de'=>'#3a6fa5','nl'=>'#5a8fc5',
    'ru'=>'#7b4f8a','zh'=>'#2a9d8f','ja'=>'#3aad9f','ko'=>'#4abd9f',
    'ar'=>'#c4913f',
];
$accentColor = $langColors[$landingLang] ?? '#F4A5A5';

// ── RTL detection ───────────────────────────────────────────────────
$isRtl = in_array($landingLang, ['ar', 'he', 'fa', 'ur']);
$dirAttr = $isRtl ? ' dir="rtl"' : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($landingLang, ENT_QUOTES, 'UTF-8') ?>"<?= $dirAttr ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="Babel Free">
    <meta property="og:image" content="https://babelfree.com/assets/og-babel.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="<?= $accentColor ?>">
    <link rel="icon" type="image/png" href="/assets/tower-logo.png">
    <link rel="manifest" href="/manifest.json">
<?php
// hreflang links to all other language dictionaries
foreach ($allI18n as $hlLang => $hlI18n): ?>
    <link rel="alternate" hreflang="<?= $hlLang ?>" href="https://babelfree.com/dictionary/<?= $hlLang ?>/">
<?php endforeach; ?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'description' => $metaDesc,
        'url' => $canonicalUrl,
        'inLanguage' => $landingLang,
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'Babel Free', 'url' => 'https://babelfree.com'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => "https://babelfree.com/dictionary/{$landingLang}/{$urlPrefix}-{search_term_string}",
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
<?php
    $langFaq = $faqData[$landingLang] ?? [];
    if ($seoContent && !empty($langFaq)):
?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(function($item) {
            return [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['a'],
                ],
            ];
        }, $langFaq),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
<?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lucida+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/footer.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Lucida Sans', Arial, sans-serif; color: #333; line-height: 1.7; background: #fafbfc; }
        .header { position: fixed; top: 0; width: 100%; background: #fff; z-index: 1000; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 70px; }
        .logo-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .main-logo img { height: 45px; }
        .nav-menu { display: flex; list-style: none; gap: 2rem; align-items: center; }
        .nav-link { text-decoration: none; color: #333; font-size: 0.95rem; font-weight: 500; transition: color 0.3s; }
        .nav-link:hover { color: <?= $accentColor ?>; }
        .cta-nav { background: #F4A5A5; color: #fff; padding: 10px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; }
        .mobile-menu-toggle { display: none; cursor: pointer; flex-direction: column; gap: 5px; }
        .menu-bar { width: 25px; height: 3px; background: #333; border-radius: 3px; }
        .mobile-menu { display: none; }

        /* Hero */
        .landing-hero {
            padding: 7rem 2rem 3.5rem;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
            text-align: center; position: relative; overflow: hidden;
        }
        .landing-hero::before {
            content: ''; position: absolute; top: -50%; right: -20%;
            width: 600px; height: 600px; border-radius: 50%;
            background: radial-gradient(circle, <?= $accentColor ?>33, transparent 70%);
            pointer-events: none;
        }
        .landing-hero-content { position: relative; z-index: 2; max-width: 700px; margin: 0 auto; }
        .lang-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 64px; height: 64px; border-radius: 18px;
            background: <?= $accentColor ?>; color: #fff;
            font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;
        }
        .landing-hero h1 {
            font-family: 'Bebas Neue', sans-serif; font-size: 3.5rem;
            color: #fff; letter-spacing: 3px; margin-bottom: 0.5rem;
        }
        .landing-hero .intro {
            font-size: 1.1rem; color: rgba(255,255,255,0.65); max-width: 550px; margin: 0 auto 2rem;
        }
        .search-box { position: relative; max-width: 560px; margin: 0 auto; }
        .search-input {
            width: 100%; padding: 1.1rem 3.5rem 1.1rem 1.4rem;
            border: 2px solid rgba(255,255,255,0.15); border-radius: 16px;
            font-size: 1.15rem; color: #fff; background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px); font-family: inherit;
        }
        .search-input:focus { outline: none; border-color: <?= $accentColor ?>; box-shadow: 0 4px 30px <?= $accentColor ?>40; background: rgba(255,255,255,0.12); }
        .search-input::placeholder { color: rgba(255,255,255,0.4); }
        .search-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: <?= $accentColor ?>; border: none; border-radius: 12px;
            width: 40px; height: 40px; cursor: pointer; display: flex;
            align-items: center; justify-content: center;
        }
        .search-btn svg { width: 20px; height: 20px; stroke: white; fill: none; stroke-width: 2; }

        /* Stats bar */
        .stats-bar {
            display: flex; justify-content: center; gap: 2.5rem; flex-wrap: wrap;
            padding: 2rem; background: #fff; border-bottom: 1px solid #eef0f2;
        }
        .stat-item { text-align: center; }
        .stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: <?= $accentColor ?>; letter-spacing: 1px; }
        .stat-label { font-size: 0.8rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }

        /* Content sections */
        .landing-section { max-width: 1000px; margin: 0 auto; padding: 3rem 2rem; }
        .section-title {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem;
            color: #1a1a2e; letter-spacing: 2px; margin-bottom: 1.5rem;
            text-align: center;
        }

        /* Popular words grid */
        .words-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .word-card {
            display: block; padding: 1rem 1.25rem; background: #fff;
            border: 1px solid #eef0f2; border-radius: 14px;
            text-decoration: none; color: #333; transition: all 0.25s;
            border-left: 4px solid <?= $accentColor ?>;
        }
        .word-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); border-color: <?= $accentColor ?>; }
        .word-card .wc-word { font-weight: 700; font-size: 1.05rem; display: block; margin-bottom: 0.25rem; }
        .word-card .wc-pos { font-size: 0.75rem; color: #999; font-style: italic; }
        .word-card .wc-cefr { font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; color: #fff; background: <?= $accentColor ?>; float: right; }
        .word-card .wc-def { font-size: 0.85rem; color: #666; margin-top: 0.4rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* CEFR distribution */
        .cefr-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem; max-width: 700px; margin: 0 auto;
        }
        .cefr-card {
            text-align: center; padding: 1.25rem; background: #fff;
            border: 1px solid #eef0f2; border-radius: 14px;
        }
        .cefr-card .cefr-level { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; }
        .cefr-card .cefr-count { font-size: 0.85rem; color: #888; }
        .cefr-card .cefr-desc { font-size: 0.75rem; color: #aaa; margin-top: 0.2rem; }
        .cefr-A1 { color: #4CAF50; }
        .cefr-A2 { color: #8BC34A; }
        .cefr-B1 { color: #FF9800; }
        .cefr-B2 { color: #FF5722; }
        .cefr-C1 { color: #9C27B0; }
        .cefr-C2 { color: #673AB7; }

        /* Korean SEO sections */
        .seo-section { max-width: 800px; margin: 0 auto; padding: 2rem 2rem 3rem; }
        .seo-block { margin-bottom: 2rem; }
        .seo-block h2 { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; color: #1a1a2e; margin-bottom: 0.5rem; letter-spacing: 1px; }
        .seo-block p { color: #555; line-height: 1.8; }

        /* Other languages */
        .other-langs { background: #f5f6f8; padding: 3rem 2rem; }
        .other-langs .landing-section { padding: 0; }
        .lang-links { display: flex; flex-wrap: wrap; gap: 0.6rem; justify-content: center; }
        .lang-link {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.5rem 1rem; background: #fff; border: 1px solid #e9ecef;
            border-radius: 10px; text-decoration: none; color: #333;
            font-size: 0.9rem; transition: all 0.2s;
        }
        .lang-link:hover { border-color: <?= $accentColor ?>; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .lang-link.current { background: <?= $accentColor ?>; color: #fff; border-color: <?= $accentColor ?>; font-weight: 600; }

        /* CTA */
        .cta-banner {
            text-align: center; padding: 3rem 2rem;
            background: linear-gradient(160deg, #1a1a2e, #16213e);
            margin: 0;
        }
        .cta-banner h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: #fff; margin-bottom: 0.75rem; }
        .cta-banner p { max-width: 500px; margin: 0 auto 1.5rem; color: rgba(255,255,255,0.6); }
        .btn-primary { display: inline-block; background: #F4A5A5; color: #fff; padding: 14px 36px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.05rem; transition: all 0.3s; }
        .btn-primary:hover { background: #e89494; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .mobile-menu-toggle { display: flex; }
            .mobile-menu.active { display: flex; flex-direction: column; position: fixed; top: 70px; left: 0; right: 0; background: #fff; padding: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 999; }
            .mobile-menu a { padding: 12px 20px; text-decoration: none; color: #333; border-bottom: 1px solid #eee; }
            .landing-hero h1 { font-size: 2.5rem; }
            .stats-bar { gap: 1.5rem; }
            .words-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
        }
    </style>
</head>
<body>
<header class="header">
    <nav class="nav-container">
        <a href="/" class="logo-brand"><div class="main-logo"><img src="/assets/logo.png" alt="Babel Free Logo"></div></a>
        <ul class="nav-menu">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/services" class="nav-link">Services</a></li>
            <li><a href="/blog" class="nav-link">Blog</a></li>
            <li><a href="/dictionary" class="nav-link active">Dictionaries</a></li>
            <li><a href="/contact" class="nav-link">Contact</a></li>
            <li><a href="/elviajedeljaguar" class="cta-nav">Spanish Course</a></li>
        </ul>
        <div class="mobile-menu-toggle" onclick="document.getElementById('mobileMenu').classList.toggle('active')">
            <div class="menu-bar"></div><div class="menu-bar"></div><div class="menu-bar"></div>
        </div>
    </nav>
</header>
<div class="mobile-menu" id="mobileMenu">
    <a href="/">Home</a><a href="/services">Services</a><a href="/blog">Blog</a><a href="/dictionary">Dictionaries</a><a href="/contact">Contact</a><a href="/elviajedeljaguar">Spanish Course</a>
</div>

<!-- Hero -->
<section class="landing-hero">
    <div class="landing-hero-content">
        <div class="lang-icon"><?= strtoupper($landingLang) ?></div>
        <h1><?= htmlspecialchars($langName . ' ' . $heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="intro"><?= htmlspecialchars($heroIntro, ENT_QUOTES, 'UTF-8') ?></p>
        <form class="search-box" action="/dictionary/<?= $landingLang ?>/" method="get" onsubmit="event.preventDefault(); var w=this.querySelector('input').value.trim(); if(w) window.location='/dictionary/<?= $landingLang ?>/<?= $urlPrefix ?>-'+encodeURIComponent(w);">
            <input type="text" class="search-input" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" autofocus>
            <button type="submit" class="search-btn"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
        </form>
    </div>
</section>

<!-- Stats -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-num"><?= number_format($wordCount) ?></div>
        <div class="stat-label"><?= htmlspecialchars($landingLang === 'es' ? 'Palabras' : ($landingLang === 'fr' ? 'Mots' : ($landingLang === 'de' ? 'W&ouml;rter' : ($landingLang === 'ko' ? '단어' : ($landingLang === 'ja' ? '単語' : ($landingLang === 'zh' ? '词条' : ($landingLang === 'ar' ? 'كلمة' : ($landingLang === 'ru' ? 'Слов' : 'Words'))))))), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($defCount > 0): ?>
    <div class="stat-item">
        <div class="stat-num"><?= number_format($defCount) ?></div>
        <div class="stat-label"><?= htmlspecialchars($landingI18n['section_definitions'] ?? 'Definitions', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php endif; ?>
    <?php if ($transCount > 0): ?>
    <div class="stat-item">
        <div class="stat-num"><?= number_format($transCount) ?></div>
        <div class="stat-label"><?= htmlspecialchars($landingI18n['section_translations'] ?? 'Equivalents', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php endif; ?>
    <div class="stat-item">
        <div class="stat-num">CEFR</div>
        <div class="stat-label">A1 &mdash; C2</div>
    </div>
</div>

<?php if ($seoContent): ?>
<!-- Language SEO Content -->
<div class="seo-section"<?= $isRtl ? ' dir="rtl" style="text-align: right;"' : '' ?>>
    <?php foreach ($seoContent['sections'] as $sec): ?>
    <div class="seo-block">
        <h2><?= htmlspecialchars($sec['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($sec['text'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($popularWords)): ?>
<!-- Popular Words -->
<section class="landing-section">
    <h2 class="section-title"><?= htmlspecialchars(($landingLang === 'ko' ? '인기 단어' : ($landingLang === 'ja' ? '人気の単語' : ($landingLang === 'zh' ? '热门词汇' : ($landingLang === 'ar' ? 'كلمات شائعة' : ($landingLang === 'ru' ? 'Популярные слова' : ($landingLang === 'es' ? 'Palabras populares' : ($landingLang === 'fr' ? 'Mots populaires' : ($landingLang === 'de' ? 'Beliebte W&ouml;rter' : ($landingLang === 'pt' ? 'Palavras populares' : ($landingLang === 'it' ? 'Parole popolari' : ($landingLang === 'nl' ? 'Populaire woorden' : 'Popular words'))))))))))), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="words-grid">
        <?php foreach ($popularWords as $pw):
            $pwUrl = '/dictionary/' . $landingLang . '/' . $urlPrefix . '-' . urlencode(mb_strtolower($pw['word'], 'UTF-8'));
        ?>
        <a href="<?= $pwUrl ?>" class="word-card">
            <?php if (!empty($pw['cefr_level'])): ?><span class="wc-cefr"><?= $pw['cefr_level'] ?></span><?php endif; ?>
            <span class="wc-word"><?= htmlspecialchars($pw['word'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="wc-pos"><?= htmlspecialchars($landingI18n['pos_labels'][$pw['part_of_speech'] ?? ''] ?? $pw['part_of_speech'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($pw['definition'])): ?>
            <span class="wc-def"><?= htmlspecialchars(mb_substr($pw['definition'], 0, 80), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($cefrDist)): ?>
<!-- CEFR Distribution -->
<section class="landing-section" style="background: #f5f6f8; max-width: none; padding: 3rem 2rem;">
    <h2 class="section-title"><?= htmlspecialchars($landingI18n['cefr_section_title'] ?? 'CEFR levels', ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="cefr-grid">
        <?php foreach (['A1','A2','B1','B2','C1','C2'] as $level):
            $cnt = $cefrDist[$level] ?? 0;
            $cefrLabels = $landingI18n['cefr_labels'] ?? [];
            $desc = $cefrLabels[$level] ?? '';
        ?>
        <div class="cefr-card">
            <div class="cefr-level cefr-<?= $level ?>"><?= $level ?></div>
            <div class="cefr-count"><?= number_format($cnt) ?></div>
            <div class="cefr-desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Other languages -->
<div class="other-langs">
    <div class="landing-section">
        <h2 class="section-title"><?= $landingLang === 'ko' ? '다른 언어 사전' : ($landingLang === 'ja' ? '他の言語の辞書' : ($landingLang === 'zh' ? '其他语言词典' : ($landingLang === 'ar' ? 'قواميس أخرى' : ($landingLang === 'es' ? 'Otros idiomas' : ($landingLang === 'fr' ? 'Autres langues' : 'More dictionaries'))))) ?></h2>
        <div class="lang-links">
            <?php foreach ($allI18n as $olLang => $olI18n):
                $olName = $olI18n['lang_name'] ?? strtoupper($olLang);
                $isCurrent = ($olLang === $landingLang);
            ?>
            <a href="/dictionary/<?= $olLang ?>/" class="lang-link<?= $isCurrent ? ' current' : '' ?>"><?= htmlspecialchars($olName, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner">
    <h2><?= htmlspecialchars($landingI18n['cta_title'] ?? 'Learn in context', ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= $landingI18n['cta_text'] ? str_replace('{word}', '<em>' . htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') . '</em>', $landingI18n['cta_text']) : '' ?></p>
    <a href="/elviajedeljaguar" class="btn-primary"><?= htmlspecialchars($landingI18n['cta_button'] ?? 'Start Free Course', ENT_QUOTES, 'UTF-8') ?></a>
</div>

<footer class="site-footer light">
    <div class="footer-grid">
        <div class="footer-brand">
            <p class="footer-logo"><img src="/assets/tower-logo.png" alt="Babel Free" loading="lazy"></p>
            <p class="footer-desc">Language courses, professional translation, and immersive Spanish learning through El Viaje del Jaguar.</p>
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
</body>
</html>
