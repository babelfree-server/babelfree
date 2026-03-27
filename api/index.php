<?php
/**
 * El Viaje del Jaguar — API Front Controller
 * All requests routed through here via .htaccess
 */

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
    error_log('[API] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit;
});

// Autoload PHPMailer
require_once __DIR__ . '/vendor/autoload.php';

// Config & helpers
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/helpers/token.php';
require_once __DIR__ . '/helpers/mailer.php';

// Middleware
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/rate_limit.php';
require_once __DIR__ . '/middleware/csrf.php';

// Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Session.php';
require_once __DIR__ . '/models/A1Progress.php';
require_once __DIR__ . '/models/DestProgress.php';
require_once __DIR__ . '/models/UserStats.php';
require_once __DIR__ . '/models/UserSettings.php';
require_once __DIR__ . '/models/EscapeRoomProgress.php';
require_once __DIR__ . '/models/Dictionary.php';
require_once __DIR__ . '/models/Glossary.php';
require_once __DIR__ . '/models/BusquedaProgress.php';
require_once __DIR__ . '/models/Analytics.php';
require_once __DIR__ . '/models/LexiconProgress.php';

// Routes
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/session.php';
require_once __DIR__ . '/routes/progress.php';
require_once __DIR__ . '/routes/stats.php';
require_once __DIR__ . '/routes/settings.php';
require_once __DIR__ . '/routes/dictionary.php';
require_once __DIR__ . '/routes/glossary.php';
require_once __DIR__ . '/routes/freeplay.php';
require_once __DIR__ . '/routes/feedback.php';
require_once __DIR__ . '/routes/forum.php';
require_once __DIR__ . '/routes/validation.php';
require_once __DIR__ . '/routes/busqueda.php';
require_once __DIR__ . '/routes/adventure.php';
require_once __DIR__ . '/routes/analytics.php';
require_once __DIR__ . '/routes/tracking.php';
require_once __DIR__ . '/routes/lexicon.php';

// CORS
setCorsHeaders();

// Read request body ONCE (OLS LSAPI can only read php://input reliably once)
$GLOBALS['_RAW_BODY'] = file_get_contents('php://input');

// Parse route: /api/{resource}/{action}
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('#^/api/?#', '', $path);
$path = trim($path, '/');
$segments = $path ? explode('/', $path, 2) : [];

$resource = $segments[0] ?? '';
$action = $segments[1] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Route dispatch
switch ($resource) {
    case 'auth':
        handleAuthRoutes($action, $method);
        break;
    case 'session':
        handleSessionRoutes($action, $method);
        break;
    case 'progress':
        handleProgressRoutes($action, $method);
        break;
    case 'stats':
        handleStatsRoutes($action, $method);
        break;
    case 'settings':
        handleSettingsRoutes($action, $method);
        break;
    case 'dictionary':
        handleDictionaryRoutes($action, $method);
        break;
    case 'glossary':
        handleGlossaryRoutes($action, $method);
        break;
    case 'freeplay':
        handleFreeplayRoutes($action, $method);
        break;
    case 'feedback':
        handleFeedbackRoutes($action, $method);
        break;
    case 'forum':
        handleForumRoutes($action, $method);
        break;
    case 'validation':
        handleValidationRoutes($action, $method);
        break;
    case 'busqueda':
        handleBusquedaRoutes($action, $method);
        break;
    case 'adventure':
        handleAdventureRoutes($action, $method);
        break;
    case 'analytics':
        handleAnalyticsRoutes($action, $method);
        break;
    case 'tracking':
        handleTrackingRoutes($action, $method);
        break;
    case 'lexicon':
        handleLexiconRoutes($action, $method);
        break;
    default:
        jsonError('Ruta no encontrada', 404);
}
