<?php
/**
 * SEO Forum Post Page — El Viaje del Jaguar
 * Serves /foro/{slug} as a fully rendered HTML page for search engines.
 * Rewritten via .htaccess: /foro/{slug} → foro-post.php?slug={slug}
 */

// ── Database connection ─────────────────────────────────────────────
$config = require __DIR__ . '/api/config/app.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ── Get slug ────────────────────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '', '/');
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (!$slug) {
    header('Location: /foro');
    exit;
}

// ── Fetch post ──────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM forum_posts WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

// ── 404 ─────────────────────────────────────────────────────────────
if (!$post) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pregunta no encontrada — Foro de gramática | El Viaje del Jaguar</title>
    <link rel="stylesheet" href="/luxury.css">
    <meta name="robots" content="noindex">
    <style>
        body::before, body::after { display: none !important; }
        .foro-404 {
            max-width: 600px;
            margin: 120px auto;
            padding: 40px 20px;
            text-align: center;
            color: #e8e0d0;
            font-family: 'Cormorant Garamond', 'Georgia', serif;
        }
        .foro-404 h1 {
            font-size: 3rem;
            color: #c9a84c;
            margin-bottom: 16px;
        }
        .foro-404 p {
            font-size: 1.15rem;
            line-height: 1.6;
            color: rgba(232,224,208,0.7);
            margin-bottom: 32px;
        }
        .foro-404 a {
            display: inline-block;
            padding: 12px 32px;
            background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.4);
            border-radius: 8px;
            color: #c9a84c;
            text-decoration: none;
            font-size: 1.05rem;
            transition: background 0.2s, border-color 0.2s;
        }
        .foro-404 a:hover {
            background: rgba(201,168,76,0.25);
            border-color: rgba(201,168,76,0.6);
        }
    </style>
</head>
<body>
    <div class="foro-404">
        <h1>404</h1>
        <p>Esta pregunta no existe o no ha sido publicada.</p>
        <a href="/foro">&larr; Volver al foro</a>
    </div>
</body>
</html>
    <?php
    exit;
}

// ── Increment views ─────────────────────────────────────────────────
$pdo->prepare('UPDATE forum_posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);

// ── Prepare display variables ───────────────────────────────────────
$question = htmlspecialchars($post['question'], ENT_QUOTES, 'UTF-8');
$answer = htmlspecialchars($post['answer'], ENT_QUOTES, 'UTF-8');
$cefrLevel = htmlspecialchars($post['cefr_level'] ?? '', ENT_QUOTES, 'UTF-8');
$source = htmlspecialchars($post['source'] ?? '', ENT_QUOTES, 'UTF-8');
$fundeuUrl = $post['fundeu_url'] ?? '';
$dpdUrl = $post['dpd_url'] ?? '';
$views = (int)($post['views'] ?? 0) + 1; // +1 for current visit
$createdAt = $post['created_at'] ?? '';
$createdFormatted = $createdAt ? date('j \d\e F \d\e Y', strtotime($createdAt)) : '';

// Parse tags from JSON
$tags = [];
if (!empty($post['tags'])) {
    $decoded = json_decode($post['tags'], true);
    if (is_array($decoded)) {
        $tags = $decoded;
    }
}

// Canonical URL
$canonicalUrl = 'https://babelfree.com/foro/' . urlencode($slug);

// Meta description: first 155 chars of answer
$metaDesc = htmlspecialchars(
    mb_substr(strip_tags(html_entity_decode($post['answer'])), 0, 155, 'UTF-8') . '...',
    ENT_QUOTES, 'UTF-8'
);

// Schema.org JSON-LD
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'QAPage',
    'mainEntity' => [
        '@type' => 'Question',
        'name' => $post['question'],
        'text' => $post['question'],
        'dateCreated' => $createdAt,
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $post['answer'],
            'dateCreated' => $createdAt,
        ],
    ],
];
$schemaJson = json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

// Breadcrumb JSON-LD
$breadcrumbData = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => 'https://babelfree.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Foro', 'item' => 'https://babelfree.com/foro'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['question']],
    ],
];
$breadcrumbJson = json_encode($breadcrumbData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

// ── Related posts ───────────────────────────────────────────────────
$related = [];
if ($cefrLevel) {
    $relStmt = $pdo->prepare(
        'SELECT slug, question, views, cefr_level FROM forum_posts WHERE cefr_level = ? AND id != ? AND is_published = 1 ORDER BY views DESC LIMIT 3'
    );
    $relStmt->execute([$post['cefr_level'], $post['id']]);
    $related = $relStmt->fetchAll();
}

// ── CEFR badge colors ───────────────────────────────────────────────
$cefrColors = [
    'A1' => '#4caf50', 'A2' => '#66bb6a',
    'B1' => '#2196f3', 'B2' => '#42a5f5',
    'C1' => '#ab47bc', 'C2' => '#ce93d8',
];
$badgeColor = $cefrColors[strtoupper($cefrLevel)] ?? '#c9a84c';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $question ?> — Foro de gramática | El Viaje del Jaguar</title>
    <meta name="description" content="<?= $metaDesc ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= $question ?> — Foro de gramática">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:site_name" content="El Viaje del Jaguar — Babel Free">
    <?php if ($createdAt): ?>
    <meta property="article:published_time" content="<?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= $question ?>">
    <meta name="twitter:description" content="<?= $metaDesc ?>">

    <!-- Structured Data -->
    <script type="application/ld+json"><?= $schemaJson ?></script>
    <script type="application/ld+json"><?= $breadcrumbJson ?></script>

    <link rel="stylesheet" href="/luxury.css">
    <link rel="icon" href="/assets/tower-logo.png" type="image/png">

    <style>
        body::before, body::after { display: none !important; }

        .foro-post-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            font-family: 'Cormorant Garamond', 'Georgia', serif;
            color: #e8e0d0;
        }

        /* Breadcrumb */
        .foro-breadcrumb {
            font-size: 0.85rem;
            color: rgba(232,224,208,0.45);
            margin-bottom: 24px;
        }
        .foro-breadcrumb a {
            color: rgba(232,224,208,0.55);
            text-decoration: none;
            transition: color 0.2s;
        }
        .foro-breadcrumb a:hover {
            color: #c9a84c;
        }
        .foro-breadcrumb span {
            margin: 0 6px;
        }

        /* Back link */
        .foro-back {
            display: inline-block;
            margin-bottom: 28px;
            color: #c9a84c;
            text-decoration: none;
            font-size: 1rem;
            transition: opacity 0.2s;
        }
        .foro-back:hover {
            opacity: 0.8;
        }

        /* Badges */
        .foro-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .foro-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .foro-badge-cefr {
            color: #fff;
        }
        .foro-badge-source {
            background: rgba(232,224,208,0.08);
            border: 1px solid rgba(232,224,208,0.15);
            color: rgba(232,224,208,0.6);
        }

        /* Question */
        .foro-question {
            font-size: 2rem;
            line-height: 1.3;
            color: #c9a84c;
            margin: 0 0 32px 0;
            font-weight: 600;
        }

        /* Answer */
        .foro-answer {
            font-size: 1.15rem;
            line-height: 1.7;
            color: rgba(232,224,208,0.88);
            white-space: pre-wrap;
            margin-bottom: 36px;
        }

        /* Citation block */
        .foro-citation {
            background: rgba(201,168,76,0.06);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 36px;
        }
        .foro-citation h3 {
            font-size: 1rem;
            color: #c9a84c;
            margin: 0 0 14px 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 0.85rem;
        }
        .foro-citation-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .foro-citation a {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: 6px;
            color: #c9a84c;
            text-decoration: none;
            font-size: 0.95rem;
            transition: background 0.2s, border-color 0.2s;
        }
        .foro-citation a:hover {
            background: rgba(201,168,76,0.2);
            border-color: rgba(201,168,76,0.5);
        }

        /* Tags */
        .foro-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .foro-tag {
            display: inline-block;
            padding: 4px 14px;
            background: rgba(201,168,76,0.08);
            border: 1px solid rgba(201,168,76,0.18);
            border-radius: 20px;
            color: rgba(201,168,76,0.8);
            font-size: 0.82rem;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Meta info */
        .foro-meta {
            display: flex;
            gap: 20px;
            color: rgba(232,224,208,0.4);
            font-size: 0.85rem;
            font-family: system-ui, -apple-system, sans-serif;
            margin-bottom: 48px;
            flex-wrap: wrap;
        }

        /* Related posts */
        .foro-related {
            border-top: 1px solid rgba(201,168,76,0.12);
            padding-top: 36px;
            margin-top: 12px;
        }
        .foro-related h2 {
            font-size: 1.2rem;
            color: #c9a84c;
            margin: 0 0 20px 0;
            font-weight: 600;
        }
        .foro-related-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .foro-related-item a {
            display: block;
            padding: 16px 20px;
            background: rgba(232,224,208,0.03);
            border: 1px solid rgba(232,224,208,0.08);
            border-radius: 8px;
            color: #e8e0d0;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s;
        }
        .foro-related-item a:hover {
            background: rgba(201,168,76,0.06);
            border-color: rgba(201,168,76,0.2);
        }
        .foro-related-question {
            font-size: 1.05rem;
            margin-bottom: 6px;
        }
        .foro-related-meta {
            font-size: 0.78rem;
            color: rgba(232,224,208,0.4);
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Footer */
        .foro-footer {
            text-align: center;
            padding: 48px 20px 32px;
            color: rgba(232,224,208,0.3);
            font-size: 0.85rem;
        }
        .foro-footer a {
            color: rgba(201,168,76,0.5);
            text-decoration: none;
        }
        .foro-footer a:hover {
            color: #c9a84c;
        }

        @media (max-width: 600px) {
            .foro-post-container { padding: 24px 16px; }
            .foro-question { font-size: 1.5rem; }
            .foro-answer { font-size: 1.05rem; }
        }
    </style>
</head>
<body>
    <div class="foro-post-container">

        <!-- Breadcrumb -->
        <nav class="foro-breadcrumb" aria-label="Navegación de migas de pan">
            <a href="/">Inicio</a>
            <span>&rsaquo;</span>
            <a href="/foro">Foro</a>
            <span>&rsaquo;</span>
            <span><?= mb_substr($question, 0, 60, 'UTF-8') ?><?= mb_strlen($post['question'], 'UTF-8') > 60 ? '...' : '' ?></span>
        </nav>

        <!-- Back link -->
        <a href="/foro" class="foro-back" aria-label="Volver al foro">&larr; Volver al foro</a>

        <!-- Badges -->
        <div class="foro-badges">
            <?php if ($cefrLevel): ?>
            <span class="foro-badge foro-badge-cefr" style="background: <?= $badgeColor ?>"><?= $cefrLevel ?></span>
            <?php endif; ?>
            <?php if ($source): ?>
            <span class="foro-badge foro-badge-source"><?= $source ?></span>
            <?php endif; ?>
        </div>

        <!-- Question -->
        <h1 class="foro-question"><?= $question ?></h1>

        <!-- Answer -->
        <div class="foro-answer"><?= $answer ?></div>

        <!-- Citation block -->
        <?php if ($fundeuUrl || $dpdUrl): ?>
        <div class="foro-citation">
            <h3>Referencias oficiales</h3>
            <div class="foro-citation-links">
                <?php if ($fundeuUrl): ?>
                <a href="<?= htmlspecialchars($fundeuUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Fundéu RAE — Recomendación</a>
                <?php endif; ?>
                <?php if ($dpdUrl): ?>
                <a href="<?= htmlspecialchars($dpdUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Diccionario panhispánico de dudas — RAE</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
        <div class="foro-tags" aria-label="Etiquetas">
            <?php foreach ($tags as $tag): ?>
            <span class="foro-tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Meta info -->
        <div class="foro-meta">
            <span><?= number_format($views) ?> vista<?= $views !== 1 ? 's' : '' ?></span>
            <?php if ($createdFormatted): ?>
            <span>Publicado el <?= $createdFormatted ?></span>
            <?php endif; ?>
        </div>

        <!-- Related posts -->
        <?php if (!empty($related)): ?>
        <section class="foro-related">
            <h2>Preguntas relacionadas</h2>
            <ul class="foro-related-list">
                <?php foreach ($related as $rel): ?>
                <li class="foro-related-item">
                    <a href="/foro/<?= htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="foro-related-question"><?= htmlspecialchars($rel['question'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="foro-related-meta">
                            <?= htmlspecialchars($rel['cefr_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            &middot;
                            <?= number_format((int)$rel['views']) ?> vistas
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer class="foro-footer">
        <a href="/">El Viaje del Jaguar</a> — <a href="https://babelfree.com">babelfree.com</a>
    </footer>

</body>
</html>
