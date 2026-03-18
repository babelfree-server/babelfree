<?php

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && mb_strlen($email) <= 255;
}

function validatePassword(string $password): bool {
    if (mb_strlen($password) < 10) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}

function sanitizeString(string $input, int $maxLen = 255): string {
    // Strip null bytes and control characters (except newline/tab)
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $input);
    $trimmed = trim($clean);
    if (mb_strlen($trimmed) > $maxLen) {
        $trimmed = mb_substr($trimmed, 0, $maxLen);
    }
    // Do NOT htmlspecialchars here — encode at output, not input.
    // All DB queries use prepared statements; JSON output is safe by default.
    return $trimmed;
}
