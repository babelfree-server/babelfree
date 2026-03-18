<?php

function handleBusquedaRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();

    switch ($action) {
        case 'progress':
            if ($method === 'GET') {
                $model = new BusquedaProgress($pdo);
                $data = $model->getByUser($user['id']);
                jsonSuccess(['progress' => $data]);
            }
            if ($method === 'POST') {
                validateCsrf();
                checkRateLimit('general');
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $model = new BusquedaProgress($pdo);
                $model->upsert($user['id'], [
                    'solved_riddles'  => $input['solved_riddles'] ?? [],
                    'bridge_segments' => $input['bridge_segments'] ?? 0,
                    'rana_opacity'    => $input['rana_opacity'] ?? 0.0,
                    'rana_name'       => $input['rana_name'] ?? null,
                    'journal_entries' => $input['journal_entries'] ?? [],
                ]);
                jsonSuccess(['message' => 'Progreso de búsqueda guardado']);
            }
            jsonError('Método no permitido', 405);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
