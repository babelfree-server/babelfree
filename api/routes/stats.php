<?php

function handleStatsRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();
    $model = new UserStats($pdo);

    // Default action is the root stats endpoint
    if ($action === '' || $action === 'index') {
        if ($method === 'GET') {
            $stats = $model->get($user['id']);
            if (!$stats) {
                // Initialize empty stats row
                $model->update($user['id'], []);
                $stats = $model->get($user['id']);
            }
            unset($stats['user_id']);
            jsonSuccess(['stats' => $stats]);
        }
        if ($method === 'POST') {
            validateCsrf();
            checkRateLimit('general');
            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');
            $model->update($user['id'], $input);
            jsonSuccess(['message' => 'Estadísticas actualizadas']);
        }
        jsonError('Método no permitido', 405);
    }

    jsonError('Ruta no encontrada', 404);
}
