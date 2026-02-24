<?php

class UserStats {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function get(int $userId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $userId, array $data): void {
        // Ensure row exists
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO user_stats (user_id) VALUES (?)'
        );
        $stmt->execute([$userId]);

        $allowed = [
            'lessons_completed', 'vocabulary_mastered', 'day_streak',
            'perfect_scores', 'cultural_knowledge', 'trees_protected',
            'species_saved', 'rivers_cleaned', 'total_time_days',
            'last_activity_date',
        ];

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
        $sql = 'UPDATE user_stats SET ' . implode(', ', $sets) . ' WHERE user_id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function recalculate(int $userId): array {
        // Count completed A1 lessons
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as cnt FROM a1_progress WHERE user_id = ? AND is_complete = 1'
        );
        $stmt->execute([$userId]);
        $a1 = (int)$stmt->fetch()['cnt'];

        // Count completed destinations
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as cnt FROM destination_progress WHERE user_id = ? AND is_complete = 1'
        );
        $stmt->execute([$userId]);
        $dest = (int)$stmt->fetch()['cnt'];

        $total = $a1 + $dest;

        $this->update($userId, [
            'lessons_completed'  => $total,
            'last_activity_date' => date('Y-m-d'),
        ]);

        return $this->get($userId);
    }
}
