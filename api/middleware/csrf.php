<?php
// Double-submit CSRF protection
// Sets a cookie on login; validates X-CSRF-Token header on POST requests

function setCsrfCookie(string $token): void {
    setcookie('csrf_token', $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'secure'   => true,
        'httponly'  => false, // JS must read it
        'samesite' => 'Strict',
    ]);
}

function validateCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $cookie = $_COOKIE['csrf_token'] ?? '';
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!$cookie || !$header || !hash_equals($cookie, $header)) {
        jsonError('Token CSRF inválido', 403);
    }
}
