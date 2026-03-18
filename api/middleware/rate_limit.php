<?php
// Redis-backed rate limiting

function checkRateLimit(string $action): void {
    $config = require __DIR__ . '/../config/app.php';
    $limits = $config['rate_limits'];
    $limit = $limits[$action] ?? $limits['general'];

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = "jaguar_rl:{$action}:{$ip}";

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);

        $current = (int)$redis->get($key);
        if ($current >= $limit['max']) {
            $ttl = $redis->ttl($key);
            header("Retry-After: {$ttl}");
            jsonError('Demasiados intentos. Intenta de nuevo más tarde.', 429);
        }

        $redis->incr($key);
        if ($current === 0) {
            $redis->expire($key, $limit['window']);
        }
        $redis->close();
    } catch (\Exception $e) {
        // If Redis is down, deny the request (fail closed)
        error_log("Redis rate limit error (blocking request): " . $e->getMessage());
        jsonError('Servicio temporalmente no disponible. Intenta de nuevo en unos minutos.', 503);
    }
}
