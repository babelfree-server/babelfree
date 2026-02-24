<?php

class Session {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $userId, int $expiryDays = 30): string {
        $token = generateToken();
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))'
        );
        $stmt->execute([
            $userId,
            $token,
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
            $expiryDays,
        ]);
        return $token;
    }

    public function validate(string $token): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.email, u.display_name, u.user_type, u.cefr_level,
                    u.interface_lang, u.detected_lang, u.email_verified
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.token = ? AND s.is_revoked = 0 AND s.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function revoke(string $token): void {
        $stmt = $this->pdo->prepare('UPDATE sessions SET is_revoked = 1 WHERE token = ?');
        $stmt->execute([$token]);
    }

    public function revokeAll(int $userId): void {
        $stmt = $this->pdo->prepare('UPDATE sessions SET is_revoked = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
