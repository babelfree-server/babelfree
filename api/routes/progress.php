<?php

function handleProgressRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();

    switch ($action) {
        case 'a1':
            if ($method === 'GET') {
                $model = new A1Progress($pdo);
                jsonSuccess(['progress' => $model->getAllByUser($user['id'])]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $moduleId = sanitizeString($input['module_id'] ?? '', 50);
                $lessonId = sanitizeString($input['lesson_id'] ?? '', 50);
                if (!$moduleId || !$lessonId) jsonError('module_id y lesson_id son obligatorios');

                $model = new A1Progress($pdo);
                $model->upsert($user['id'], [
                    'module_id'    => $moduleId,
                    'lesson_id'    => $lessonId,
                    'current_step' => $input['current_step'] ?? 0,
                    'total_steps'  => $input['total_steps'] ?? 0,
                    'is_complete'  => $input['is_complete'] ?? false,
                    'matched_pairs' => $input['matched_pairs'] ?? 0,
                ]);
                jsonSuccess(['message' => 'Progreso guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        case 'destinations':
            if ($method === 'GET') {
                $model = new DestProgress($pdo);
                jsonSuccess(['progress' => $model->getAllByUser($user['id'])]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $destId = sanitizeString($input['destination_id'] ?? '', 50);
                if (!$destId) jsonError('destination_id es obligatorio');

                $model = new DestProgress($pdo);
                $model->upsert($user['id'], [
                    'destination_id'    => $destId,
                    'storage_key'       => $input['storage_key'] ?? '',
                    'current_encounter' => $input['current_encounter'] ?? 0,
                    'completed_count'   => $input['completed_count'] ?? 0,
                    'completed_map'     => $input['completed_map'] ?? null,
                    'total_encounters'  => $input['total_encounters'] ?? 0,
                    'is_complete'       => $input['is_complete'] ?? false,
                ]);
                jsonSuccess(['message' => 'Progreso guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        case 'escaperooms':
            if ($method === 'GET') {
                $model = new EscapeRoomProgress($pdo);
                jsonSuccess(['progress' => $model->getAllByUser($user['id'])]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $destId = sanitizeString($input['destination_id'] ?? '', 50);
                if (!$destId) jsonError('destination_id es obligatorio');

                $model = new EscapeRoomProgress($pdo);
                $model->upsert($user['id'], [
                    'destination_id' => $destId,
                    'puzzles_solved' => $input['puzzles_solved'] ?? null,
                    'is_complete'    => $input['is_complete'] ?? false,
                    'fragment_item'  => isset($input['fragment_item'])
                        ? sanitizeString($input['fragment_item'], 100) : null,
                ]);
                jsonSuccess(['message' => 'Progreso guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        case 'fragments':
            if ($method === 'GET') {
                $model = new EscapeRoomProgress($pdo);
                jsonSuccess(['fragments' => $model->getFragments($user['id'])]);
            }
            jsonError('Método no permitido', 405);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
