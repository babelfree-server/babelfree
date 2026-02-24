<?php

function handleFreeplayRoutes(string $action, string $method): void {
    switch ($action) {
        case 'catalog':
            if ($method !== 'GET') jsonError('Método no permitido', 405);

            $catalogPath = __DIR__ . '/../../content/freeplay-catalog.json';
            if (!file_exists($catalogPath)) {
                jsonError('Catálogo no disponible', 404);
            }

            $catalog = json_decode(file_get_contents($catalogPath), true);
            if (!$catalog) {
                jsonError('Error al leer el catálogo', 500);
            }

            // Optional filters
            $level = $_GET['level'] ?? null;
            $type = $_GET['type'] ?? null;

            if ($level || $type) {
                $filtered = [];
                foreach ($catalog['games'] as $game) {
                    if ($level && strcasecmp($game['cefr'], $level) !== 0) continue;
                    if ($type && $game['type'] !== $type) continue;
                    $filtered[] = $game;
                }
                $catalog['games'] = $filtered;
                $catalog['totalGames'] = count($filtered);
            }

            jsonSuccess($catalog);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
