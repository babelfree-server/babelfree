<?php

function handleGlossaryRoutes(string $action, string $method): void {
    checkRateLimit('general');

    $pdo = getDB();
    $model = new Glossary($pdo);

    // Parse full URI
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = preg_replace('#^/api/glossary/?#', '', $uri);
    $uri = trim($uri, '/');
    $parts = $uri ? explode('/', $uri) : [];

    $subAction = $parts[0] ?? '';

    // GET /api/glossary/search?q=...&lang=...  — public (optional auth)
    if ($method === 'GET' && $subAction === 'search') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) {
            jsonSuccess(['words' => []]);
        }

        // Optional auth
        $userId = null;
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $m)) {
            $token = $m[1];
            $stmt = $pdo->prepare(
                'SELECT s.user_id FROM sessions s
                 WHERE s.token = ? AND s.is_revoked = 0 AND s.expires_at > NOW() LIMIT 1'
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();
            if ($row) $userId = (int)$row['user_id'];
        }

        $lang = preg_replace('/[^a-z_-]/i', '', $_GET['lang'] ?? '');
        $words = $model->search($q, $userId, 20, $lang ?: null);
        jsonSuccess(['words' => $words]);
    }

    // All other endpoints require authentication
    $user = authenticateRequest();
    $userId = (int)$user['id'];

    // GET /api/glossary/words?ecosystem=&level=&dest=&lang=
    if ($method === 'GET' && $subAction === 'words') {
        $ecosystem = $_GET['ecosystem'] ?? null;
        $cefrLevel = $_GET['level'] ?? null;
        $destId = $_GET['dest'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 200), 500);
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        $lang = preg_replace('/[^a-z_-]/i', '', $_GET['lang'] ?? '');

        $words = $model->getWords($userId, $ecosystem, $cefrLevel, $destId, $limit, $offset, $lang ?: null);
        jsonSuccess(['words' => $words]);
    }

    // GET /api/glossary/word/{id}
    if ($method === 'GET' && $subAction === 'word') {
        $wordId = (int)($parts[1] ?? 0);
        if (!$wordId) {
            jsonError('ID de palabra requerido', 400);
        }

        $interfaceLang = $user['interface_lang'] ?? 'es';
        $word = $model->getWord($wordId, $userId, $interfaceLang);
        if (!$word) {
            jsonError('Palabra no encontrada', 404);
        }
        jsonSuccess(['word' => $word]);
    }

    // GET /api/glossary/stats
    if ($method === 'GET' && $subAction === 'stats') {
        $stats = $model->getStats($userId);
        jsonSuccess(['stats' => $stats]);
    }

    // POST /api/glossary/encounter
    if ($method === 'POST' && $subAction === 'encounter') {
        $body = getJsonBody();
        if (!$body) {
            jsonError('Cuerpo JSON requerido', 400);
        }

        $wordsSeen = $body['words'] ?? [];
        $wordsCorrect = $body['correct'] ?? [];
        $destinationId = $body['destination_id'] ?? '';

        if (empty($wordsSeen) || !$destinationId) {
            jsonError('Palabras y destination_id requeridos', 400);
        }

        $updated = $model->recordEncounter($userId, $wordsSeen, $wordsCorrect, $destinationId);
        jsonSuccess(['updated' => $updated]);
    }

    jsonError('Ruta de glosario no encontrada', 404);
}
