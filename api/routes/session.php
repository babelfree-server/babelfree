<?php

function handleSessionRoutes(string $action, string $method): void {
    switch ($action) {
        case 'validate':
            if ($method !== 'GET') jsonError('Método no permitido', 405);

            $user = authenticateRequest();
            $config = require __DIR__ . '/../config/app.php';
            $immersionLocked = in_array($user['cefr_level'], $config['immersion_locked_levels']);

            jsonSuccess([
                'user' => [
                    'id'              => $user['id'],
                    'email'           => $user['email'],
                    'display_name'    => $user['display_name'],
                    'user_type'       => $user['user_type'],
                    'role'            => $user['role'] ?? 'student',
                    'tier'            => $user['tier'] ?? 'free',
                    'cefr_level'      => $user['cefr_level'],
                    'interface_lang'   => $immersionLocked ? 'es' : $user['interface_lang'],
                    'detected_lang'   => $user['detected_lang'],
                    'email_verified'  => $user['email_verified'],
                    'immersion_locked' => $immersionLocked,
                ],
            ]);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
