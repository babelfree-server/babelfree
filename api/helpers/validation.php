<?php

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && mb_strlen($email) <= 255;
}

function validatePassword(string $password): bool {
    return mb_strlen($password) >= 8;
}

function sanitizeString(string $input, int $maxLen = 255): string {
    $trimmed = trim($input);
    if (mb_strlen($trimmed) > $maxLen) {
        $trimmed = mb_substr($trimmed, 0, $maxLen);
    }
    return htmlspecialchars($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
