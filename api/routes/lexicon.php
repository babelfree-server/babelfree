<?php

function handleLexiconRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();

    switch ($action) {
        case 'progress':
            if ($method === 'GET') {
                $model = new LexiconProgress($pdo);
                $data = $model->getByUser($user['id']);
                jsonSuccess(['progress' => $data]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $model = new LexiconProgress($pdo);
                $model->upsert($user['id'], [
                    'lexicon_data' => $input['lexicon_data'] ?? [],
                    'word_count'   => $input['word_count'] ?? 0,
                ]);
                jsonSuccess(['message' => 'Léxico personal guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
