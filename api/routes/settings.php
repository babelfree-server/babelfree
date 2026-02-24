<?php

function handleSettingsRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    $pdo = getDB();
    $model = new UserSettings($pdo);

    // Default action is the root settings endpoint
    if ($action === '' || $action === 'index') {
        if ($method === 'GET') {
            $settings = $model->get($user['id']);
            if (!$settings) {
                $model->update($user['id'], []);
                $settings = $model->get($user['id']);
            }
            unset($settings['user_id']);
            jsonSuccess(['settings' => $settings]);
        }
        if ($method === 'POST') {
            validateCsrf();
            checkRateLimit('general');
            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');
            $model->update($user['id'], $input);
            jsonSuccess(['message' => 'Preferencias actualizadas']);
        }
        jsonError('Método no permitido', 405);
    }

    jsonError('Ruta no encontrada', 404);
}
