<?php

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $userType = $data['user_type'] ?? 'individual';
        $role = $data['role'] ?? ($userType === 'classroom' ? 'teacher' : 'student');

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, display_name, user_type, role, tier, interface_lang, detected_lang, native_lang, gender, verify_token, verify_expires)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_ARGON2ID),
            $data['display_name'],
            $userType,
            $role,
            'free',
            $data['interface_lang'] ?? 'es',
            $data['detected_lang'] ?? null,
            $data['native_lang'] ?? null,
            $data['gender'] ?? null,
            $data['verify_token'],
            $data['verify_expires'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function isPremium(int $userId): bool {
        $stmt = $this->pdo->prepare(
            'SELECT tier, premium_expires_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || $row['tier'] !== 'premium') return false;

        // Auto-downgrade if expired
        if ($row['premium_expires_at'] && strtotime($row['premium_expires_at']) < time()) {
            $this->updateTier($userId, 'free', null);
            return false;
        }
        return true;
    }

    public function updateTier(int $userId, string $tier, ?string $expiresAt): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET tier = ?, premium_expires_at = ? WHERE id = ?'
        );
        $stmt->execute([$tier, $expiresAt, $userId]);
    }

    public function updateRole(int $userId, string $role): void {
        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $userId]);
    }

    public function verify(string $token): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET email_verified = 1, verify_token = NULL, verify_expires = NULL
             WHERE verify_token = ? AND verify_expires > NOW() AND email_verified = 0'
        );
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function setResetToken(int $userId, string $token, string $expires): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?'
        );
        $stmt->execute([$token, $expires, $userId]);
    }

    public function findByResetToken(string $token): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $userId, string $newPassword): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?'
        );
        $stmt->execute([password_hash($newPassword, PASSWORD_ARGON2ID), $userId]);
    }

    public function updateLastLogin(int $userId): void {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
