<?php
// Bearer token authentication middleware
// Returns user row or sends 401

function authenticateRequest(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $m)) {
        jsonError('No autorizado', 401);
    }

    $token = $m[1];
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'SELECT s.user_id, s.expires_at, s.is_revoked,
                u.id, u.email, u.display_name, u.user_type, u.role, u.tier,
                u.cefr_level, u.interface_lang, u.detected_lang, u.email_verified, u.is_active
         FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.token = ?
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonError('Sesión inválida', 401);
    }
    if ($row['is_revoked']) {
        jsonError('Sesión revocada', 401);
    }
    if (strtotime($row['expires_at']) < time()) {
        jsonError('Sesión expirada', 401);
    }
    if (!$row['is_active']) {
        jsonError('Cuenta desactivada', 403);
    }

    return [
        'id'             => (int)$row['user_id'],
        'email'          => $row['email'],
        'display_name'   => $row['display_name'],
        'user_type'      => $row['user_type'],
        'role'           => $row['role'] ?? 'student',
        'tier'           => $row['tier'] ?? 'free',
        'cefr_level'     => $row['cefr_level'],
        'interface_lang'  => $row['interface_lang'],
        'detected_lang'  => $row['detected_lang'],
        'email_verified' => (bool)$row['email_verified'],
    ];
}
