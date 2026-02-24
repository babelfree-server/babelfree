<?php

class A1Progress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT module_id, lesson_id, current_step, total_steps, is_complete,
                    matched_pairs, started_at, completed_at, updated_at
             FROM a1_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function upsert(int $userId, array $data): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO a1_progress (user_id, module_id, lesson_id, current_step, total_steps, is_complete, matched_pairs, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                current_step = VALUES(current_step),
                total_steps = VALUES(total_steps),
                is_complete = VALUES(is_complete),
                matched_pairs = VALUES(matched_pairs),
                completed_at = VALUES(completed_at)'
        );
        $stmt->execute([
            $userId,
            $data['module_id'],
            $data['lesson_id'],
            (int)($data['current_step'] ?? 0),
            (int)($data['total_steps'] ?? 0),
            (int)($data['is_complete'] ?? 0),
            (int)($data['matched_pairs'] ?? 0),
            !empty($data['is_complete']) ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
