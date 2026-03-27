<?php
/**
 * Analytics Routes — Admin-only aggregate data for dashboard
 *
 * GET /api/analytics/overview     — User counts, completion rates
 * GET /api/analytics/destinations — Per-destination starts/completions
 * GET /api/analytics/funnel       — Drop-off funnel (users per destination)
 * GET /api/analytics/escaperooms  — Escape room stats
 * GET /api/analytics/busqueda     — Riddle quest aggregate
 * GET /api/analytics/feedback     — Feedback overview
 * GET /api/analytics/trends       — Registration + activity trends (30d)
 * GET /api/analytics/cefr         — CEFR level distribution
 * GET /api/analytics/struggles    — Top 10 hardest destinations
 * GET /api/analytics/languages    — Native language distribution
 * GET /api/analytics/all          — Everything in one call
 */

function handleAnalyticsRoutes(string $action, string $method): void {
    if ($method !== 'GET') {
        jsonError('Method not allowed', 405);
        return;
    }

    // Require admin role
    $user = authenticateRequest();
    if (!$user || $user['role'] !== 'admin') {
        jsonError('Admin access required', 403);
        return;
    }

    $pdo = getDB();
    $analytics = new Analytics($pdo);

    switch ($action) {
        case 'overview':
            jsonSuccess(['overview' => $analytics->getOverview()]);
            break;

        case 'destinations':
            jsonSuccess(['destinations' => $analytics->getDestinationStats()]);
            break;

        case 'funnel':
            jsonSuccess(['funnel' => $analytics->getFunnel()]);
            break;

        case 'escaperooms':
            jsonSuccess(['escapeRooms' => $analytics->getEscapeRoomStats()]);
            break;

        case 'busqueda':
            jsonSuccess(['busqueda' => $analytics->getBusquedaStats()]);
            break;

        case 'feedback':
            jsonSuccess(['feedback' => $analytics->getFeedbackStats()]);
            break;

        case 'trends':
            jsonSuccess([
                'registrations' => $analytics->getRegistrationTrend(),
                'activity' => $analytics->getActivityTrend(),
            ]);
            break;

        case 'cefr':
            jsonSuccess(['cefr' => $analytics->getCefrDistribution()]);
            break;

        case 'struggles':
            jsonSuccess(['struggles' => $analytics->getStrugglePoints()]);
            break;

        case 'languages':
            jsonSuccess(['languages' => $analytics->getNativeLanguageDistribution()]);
            break;

        case 'all':
            jsonSuccess([
                'overview'     => $analytics->getOverview(),
                'destinations' => $analytics->getDestinationStats(),
                'funnel'       => $analytics->getFunnel(),
                'escapeRooms'  => $analytics->getEscapeRoomStats(),
                'busqueda'     => $analytics->getBusquedaStats(),
                'feedback'     => $analytics->getFeedbackStats(),
                'registrations'=> $analytics->getRegistrationTrend(),
                'activity'     => $analytics->getActivityTrend(),
                'cefr'         => $analytics->getCefrDistribution(),
                'struggles'    => $analytics->getStrugglePoints(),
                'languages'    => $analytics->getNativeLanguageDistribution(),
            ]);
            break;

        default:
            jsonError('Unknown analytics endpoint: ' . $action, 404);
    }
}
