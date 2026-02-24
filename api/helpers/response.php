<?php

function getJsonBody(): ?array {
    $raw = $GLOBALS['_RAW_BODY'] ?? file_get_contents('php://input');
    return json_decode($raw, true);
}

function jsonSuccess(array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
