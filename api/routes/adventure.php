<?php

function handleAdventureRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();

    switch ($action) {
        case 'progress':
            if ($method === 'GET') {
                $model = new AdventureProgress($pdo);
                $data = $model->getByUser($user['id']);
                jsonSuccess(['progress' => $data]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');
                $rawSize = strlen(json_encode($input));
                if ($rawSize > 2 * 1024 * 1024) jsonError('Payload demasiado grande', 413);

                $model = new AdventureProgress($pdo);
                $model->upsert($user['id'], [
                    'chapters'              => $input['chapters'] ?? [],
                    'earned_letters'        => $input['earned_letters'] ?? [],
                    'earned_words'          => $input['earned_words'] ?? [],
                    'earned_sentences'      => $input['earned_sentences'] ?? [],
                    'composition_revealed'  => $input['composition_revealed'] ?? false,
                    'total_words_written'   => $input['total_words_written'] ?? 0,
                    'started_at'            => $input['started_at'] ?? null,
                ]);
                jsonSuccess(['message' => 'Progreso de aventura guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
