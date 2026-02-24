<?php

class DestProgress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT destination_id, storage_key, current_encounter, completed_count,
                    completed_map, total_encounters, is_complete, started_at, completed_at, updated_at
             FROM destination_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        // Decode JSON completed_map
        foreach ($rows as &$row) {
            $row['completed_map'] = json_decode($row['completed_map'], true);
        }
        return $rows;
    }

    public function upsert(int $userId, array $data): void {
        $completedMap = isset($data['completed_map']) ? json_encode($data['completed_map']) : null;
        $isComplete = !empty($data['is_complete']) ? 1 : 0;

        $stmt = $this->pdo->prepare(
            'INSERT INTO destination_progress
                (user_id, destination_id, storage_key, current_encounter, completed_count,
                 completed_map, total_encounters, is_complete, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                storage_key = VALUES(storage_key),
                current_encounter = VALUES(current_encounter),
                completed_count = VALUES(completed_count),
                completed_map = VALUES(completed_map),
                total_encounters = VALUES(total_encounters),
                is_complete = VALUES(is_complete),
                completed_at = VALUES(completed_at)'
        );
        $stmt->execute([
            $userId,
            $data['destination_id'],
            $data['storage_key'] ?? '',
            (int)($data['current_encounter'] ?? 0),
            (int)($data['completed_count'] ?? 0),
            $completedMap,
            (int)($data['total_encounters'] ?? 0),
            $isComplete,
            $isComplete ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
