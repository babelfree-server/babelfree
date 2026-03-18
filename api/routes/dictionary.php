<?php

function handleDictionaryRoutes(string $action, string $method): void {
    if ($method !== 'GET') {
        jsonError('Método no permitido', 405);
    }

    checkRateLimit('general');

    $pdo = getDB();
    $model = new Dictionary($pdo);

    // Try to get authenticated user (optional — don't fail if not logged in)
    $user = null;
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $m)) {
        $token = $m[1];
        $stmt = $pdo->prepare(
            'SELECT u.cefr_level, u.interface_lang
             FROM sessions s JOIN users u ON u.id = s.user_id
             WHERE s.token = ? AND s.is_revoked = 0 AND s.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if ($row) {
            $user = ['cefr_level' => $row['cefr_level'], 'interface_lang' => $row['interface_lang']];
        }
    }

    // Parse full URI for multi-segment paths
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = preg_replace('#^/api/dictionary/?#', '', $uri);
    $uri = trim($uri, '/');
    $parts = $uri ? explode('/', $uri) : [];

    $subAction = $parts[0] ?? '';

    // GET /api/dictionary/i18n/{lang} — localized UI strings
    if ($subAction === 'i18n') {
        $lang = $parts[1] ?? 'en';
        $i18nFile = __DIR__ . '/../scripts/data/dict_i18n.json';
        if (!file_exists($i18nFile)) {
            jsonError('i18n data not found', 500);
        }
        $i18nData = json_decode(file_get_contents($i18nFile), true);
        if (!$i18nData) {
            jsonError('i18n data corrupt', 500);
        }
        // Return requested language, fall back to English
        $strings = $i18nData[$lang] ?? $i18nData['en'] ?? [];
        jsonSuccess(['lang' => $lang, 'strings' => $strings]);
    }

    // GET /api/dictionary/languages
    if ($subAction === 'languages') {
        $languages = $model->getLanguages();
        jsonSuccess(['languages' => $languages]);
    }

    // GET /api/dictionary/suggest?q=...&lang=es  (also supports old ?pair=es-en)
    if ($subAction === 'suggest') {
        $q = trim($_GET['q'] ?? '');
        $lang = $_GET['lang'] ?? null;

        // Backward compat: accept ?pair=es-en
        if (!$lang && !empty($_GET['pair'])) {
            $langParts = explode('-', $_GET['pair']);
            $lang = $langParts[0] ?? 'es';
        }
        $lang = $lang ?: 'es';

        if (strlen($q) < 1) {
            jsonSuccess(['suggestions' => []]);
        }

        $suggestions = $model->suggest($q, $lang, 10);
        jsonSuccess(['suggestions' => $suggestions]);
    }

    // GET /api/dictionary/random?lang=es&level=A1  (also supports old ?pair=es-en)
    if ($subAction === 'random') {
        $lang = $_GET['lang'] ?? null;

        // Backward compat
        if (!$lang && !empty($_GET['pair'])) {
            $langParts = explode('-', $_GET['pair']);
            $lang = $langParts[0] ?? 'es';
        }
        $lang = $lang ?: 'es';
        $level = $_GET['level'] ?? null;

        $word = $model->getRandomWord($lang, $level);
        if (!$word) {
            jsonError('No hay palabras disponibles', 404);
        }
        jsonSuccess(['word' => $word]);
    }

    // GET /api/dictionary/conjugate/{word}
    if ($subAction === 'conjugate') {
        $word = $parts[1] ?? '';
        if (!$word) {
            jsonError('Palabra requerida', 400);
        }

        $result = $model->getConjugations(urldecode($word));
        if (!$result) {
            jsonError('Verbo no encontrado', 404);
        }
        jsonSuccess(['verb' => $result]);
    }

    // GET /api/dictionary/lookup/{lang}/{word}  — new URL pattern
    // GET /api/dictionary/lookup/{pair}/{word}  — backward compat (es-en/word)
    if ($subAction === 'lookup') {
        $langOrPair = $parts[1] ?? '';
        $word = $parts[2] ?? '';

        if (!$langOrPair || !$word) {
            jsonError('Idioma y palabra requeridos', 400);
        }

        // Check if it's a pair (es-en) or single language (es)
        if (strpos($langOrPair, '-') !== false) {
            // Backward-compatible pair format: es-en
            $langParts = explode('-', $langOrPair);
            if (count($langParts) !== 2) {
                jsonError('Par de idiomas inválido (ej: es-en)', 400);
            }
            $result = $model->lookupByPair($langParts[0], $langParts[1], urldecode($word), $user);
        } else {
            // New single-language format: es
            $result = $model->lookup($langOrPair, urldecode($word), $user);
        }

        if (!$result) {
            jsonError('Palabra no encontrada', 404);
        }
        jsonSuccess(['entry' => $result]);
    }

    jsonError('Ruta de diccionario no encontrada', 404);
}
