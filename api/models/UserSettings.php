<?php

class UserSettings {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function get(int $userId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $userId, array $data): void {
        // Ensure row exists
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO user_settings (user_id) VALUES (?)'
        );
        $stmt->execute([$userId]);

        $allowed = ['gender', 'pronouns', 'sounds_volume', 'music_volume', 'accent'];

        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($sets)) return;

        $params[] = $userId;
        $sql = 'UPDATE user_settings SET ' . implode(', ', $sets) . ' WHERE user_id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
