<?php

function handleForumRoutes(string $action, string $method): void {
    $pdo = getDB();

    // Parse action — could be "list", "publish", a slug, or a numeric ID
    $actionParts = explode('/', $action);
    $mainAction = $actionParts[0] ?? '';

    switch ($mainAction) {
        case 'list':
            if ($method !== 'GET') jsonError('Método no permitido', 405);

            // Public — no auth required
            $where = ['is_published = 1'];
            $params = [];
            $selectPrefix = 'SELECT';
            $selectFields = 'id, question, answer, cefr_level, tags, slug, source, fundeu_url, dpd_url, views, created_at';

            // Search
            $q = trim($_GET['q'] ?? '');
            if ($q !== '') {
                if (mb_strlen($q) >= 4) {
                    // FULLTEXT search (min word length is typically 4)
                    $selectPrefix = 'SELECT';
                    $selectFields = 'id, question, answer, cefr_level, tags, slug, source, fundeu_url, dpd_url, views, created_at, '
                        . 'MATCH(question, answer) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance';
                    $where[] = 'MATCH(question, answer) AGAINST(? IN NATURAL LANGUAGE MODE)';
                    $params[] = $q; // for relevance column
                    $params[] = $q; // for WHERE clause
                } else {
                    // Short query — use LIKE fallback
                    $like = '%' . $q . '%';
                    $where[] = '(question LIKE ? OR answer LIKE ?)';
                    $params[] = $like;
                    $params[] = $like;
                }
            }

            // CEFR filter
            if (!empty($_GET['cefr'])) {
                $where[] = 'cefr_level = ?';
                $params[] = $_GET['cefr'];
            }

            // Tag filter
            if (!empty($_GET['tag'])) {
                $where[] = 'JSON_CONTAINS(tags, ?)';
                $params[] = json_encode($_GET['tag'], JSON_UNESCAPED_UNICODE);
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            // Pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM forum_posts {$whereClause}");
            // Count params: skip the relevance param if FULLTEXT
            $countParams = $params;
            if ($q !== '' && mb_strlen($q) >= 4) {
                // Remove the first param (relevance column) — count doesn't need it
                array_shift($countParams);
            }
            $countStmt->execute($countParams);
            $total = (int)$countStmt->fetchColumn();

            // Fetch items
            $orderBy = ($q !== '' && mb_strlen($q) >= 4) ? 'ORDER BY relevance DESC, created_at DESC' : 'ORDER BY created_at DESC';
            $stmt = $pdo->prepare(
                "{$selectPrefix} {$selectFields} FROM forum_posts {$whereClause} {$orderBy} LIMIT {$limit} OFFSET {$offset}"
            );
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            // Truncate question/answer for list view
            foreach ($items as &$item) {
                $item['question'] = mb_substr($item['question'], 0, 300);
                $item['answer'] = mb_substr($item['answer'], 0, 500);
                if (is_string($item['tags'])) {
                    $item['tags'] = json_decode($item['tags'], true);
                }
                unset($item['relevance']);
            }
            unset($item);

            // Tag cloud: distinct tags from published posts
            $tagCloud = [];
            $tagStmt = $pdo->query(
                'SELECT tags FROM forum_posts WHERE is_published = 1 AND tags IS NOT NULL AND tags != \'[]\''
            );
            $tagRows = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
            $tagCounts = [];
            foreach ($tagRows as $tagJson) {
                $decoded = json_decode($tagJson, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $t) {
                        $t = trim($t);
                        if ($t !== '') {
                            $tagCounts[$t] = ($tagCounts[$t] ?? 0) + 1;
                        }
                    }
                }
            }
            arsort($tagCounts);
            foreach ($tagCounts as $tag => $count) {
                $tagCloud[] = ['tag' => $tag, 'count' => $count];
            }

            jsonSuccess([
                'items'     => $items,
                'total'     => $total,
                'page'      => $page,
                'pages'     => (int)ceil($total / $limit),
                'tag_cloud' => $tagCloud,
            ]);
            break;

        case 'publish':
            if ($method !== 'POST') jsonError('Método no permitido', 405);

            // Admin only
            $user = authenticateRequest();
            if (($user['role'] ?? '') !== 'admin') {
                jsonError('No autorizado', 403);
            }

            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');

            $question = trim($input['question'] ?? '');
            $answer = trim($input['answer'] ?? '');
            $source = sanitizeString($input['source'] ?? 'admin', 50);
            $cefrLevel = sanitizeString($input['cefr_level'] ?? '', 10);
            $tags = $input['tags'] ?? [];
            $fundeuUrl = isset($input['fundeu_url']) ? sanitizeString($input['fundeu_url'], 500) : null;
            $dpdUrl = isset($input['dpd_url']) ? sanitizeString($input['dpd_url'], 500) : null;
            $feedbackId = isset($input['feedback_id']) ? (int)$input['feedback_id'] : null;

            // If feedback_id provided and no question, load from feedback
            if ($feedbackId && !$question) {
                $fbStmt = $pdo->prepare('SELECT message FROM feedback WHERE id = ? LIMIT 1');
                $fbStmt->execute([$feedbackId]);
                $fbRow = $fbStmt->fetch();
                if ($fbRow) {
                    $question = $fbRow['message'];
                }
            }

            if (!$question) jsonError('La pregunta es obligatoria');
            if (!$answer) jsonError('La respuesta es obligatoria');

            $slug = generateForumSlug($question, $pdo);
            $tagsJson = json_encode(is_array($tags) ? $tags : []);

            $stmt = $pdo->prepare(
                'INSERT INTO forum_posts (feedback_id, question, answer, source, cefr_level, tags, fundeu_url, dpd_url, slug, is_published)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $feedbackId, $question, $answer, $source, $cefrLevel ?: null,
                $tagsJson, $fundeuUrl, $dpdUrl, $slug
            ]);

            $id = (int)$pdo->lastInsertId();

            jsonSuccess([
                'id'   => $id,
                'slug' => $slug,
            ], 201);
            break;

        default:
            // Numeric ID: direct lookup (public)
            if (is_numeric($mainAction)) {
                if ($method !== 'GET') jsonError('Método no permitido', 405);

                $postId = (int)$mainAction;
                $stmt = $pdo->prepare('SELECT * FROM forum_posts WHERE id = ? AND is_published = 1 LIMIT 1');
                $stmt->execute([$postId]);
                $post = $stmt->fetch();

                if (!$post) jsonError('Post no encontrado', 404);

                // Increment views
                $pdo->prepare('UPDATE forum_posts SET views = views + 1 WHERE id = ?')->execute([$postId]);

                if (is_string($post['tags'])) {
                    $post['tags'] = json_decode($post['tags'], true);
                }

                jsonSuccess($post);
                break;
            }

            // Slug lookup (public)
            if ($method !== 'GET') jsonError('Método no permitido', 405);

            $slug = sanitizeString($mainAction, 200);
            $stmt = $pdo->prepare('SELECT * FROM forum_posts WHERE slug = ? AND is_published = 1 LIMIT 1');
            $stmt->execute([$slug]);
            $post = $stmt->fetch();

            if (!$post) jsonError('Post no encontrado', 404);

            // Increment views
            $pdo->prepare('UPDATE forum_posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);

            if (is_string($post['tags'])) {
                $post['tags'] = json_decode($post['tags'], true);
            }

            jsonSuccess($post);
            break;
    }
}

/**
 * Generate a URL-friendly slug from text, ensuring uniqueness in forum_posts.
 */
function generateForumSlug(string $text, PDO $pdo): string {
    $slug = mb_strtolower($text);

    // Transliterate Spanish accented characters
    $slug = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $slug
    );

    // Strip non-alphanumeric except hyphens
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

    // Replace whitespace with hyphens
    $slug = preg_replace('/\s+/', '-', $slug);

    // Collapse multiple hyphens
    $slug = preg_replace('/-+/', '-', $slug);

    // Trim hyphens from ends
    $slug = trim($slug, '-');

    // Truncate at 80 chars
    $slug = mb_substr($slug, 0, 80);
    $slug = rtrim($slug, '-');

    if ($slug === '') {
        $slug = 'post';
    }

    // Check uniqueness
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM forum_posts WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }

    return $slug;
}

/**
 * Auto-publish a grammar question/answer to the forum.
 * Called from the grammar responder after a successful KB match.
 */
function autoPublishToForum(PDO $pdo, int $feedbackId, string $question, array $result, string $cefr): void {
    // Skip if feedback already has a forum post
    $check = $pdo->prepare('SELECT id FROM forum_posts WHERE feedback_id = ? LIMIT 1');
    $check->execute([$feedbackId]);
    if ($check->fetch()) return;

    // For KB source: deduplicate by kb_topic
    $kbTopic = $result['kb_topic'] ?? null;
    if ($kbTopic) {
        $topicCheck = $pdo->prepare('SELECT id FROM forum_posts WHERE kb_topic = ? LIMIT 1');
        $topicCheck->execute([$kbTopic]);
        if ($topicCheck->fetch()) return;
    }

    $answer = $result['answer'] ?? $result['matched_explanation'] ?? '';
    if (!$answer || mb_strlen($answer) < 20) return;

    // Only auto-publish KB answers; languagetool goes unpublished for admin review
    $isPublished = ($result['source'] === 'kb' || $result['source'] === 'kb+translated' || $result['source'] === 'admin') ? 1 : 0;

    $slug = generateForumSlug($question, $pdo);
    $source = ($result['source'] === 'kb+translated') ? 'kb' : ($result['source'] ?? 'kb');
    $tags = json_encode(array_filter([$cefr, $source]));

    $stmt = $pdo->prepare(
        'INSERT INTO forum_posts (feedback_id, question, answer, source, cefr_level, tags, kb_topic, fundeu_url, dpd_url, slug, is_published)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $feedbackId, $question, $answer, $source, $cefr, $tags, $kbTopic,
        $result['fundeu_url'] ?? null, $result['dpd_url'] ?? null,
        $slug, $isPublished
    ]);
}
