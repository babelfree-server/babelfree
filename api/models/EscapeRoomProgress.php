<?php

class EscapeRoomProgress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT destination_id, puzzles_solved, is_complete, fragment_item,
                    started_at, completed_at, updated_at
             FROM escape_room_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['puzzles_solved'] = json_decode($row['puzzles_solved'], true);
        }
        return $rows;
    }

    public function upsert(int $userId, array $data): void {
        $puzzlesSolved = isset($data['puzzles_solved']) ? json_encode($data['puzzles_solved']) : null;
        $isComplete = !empty($data['is_complete']) ? 1 : 0;

        $stmt = $this->pdo->prepare(
            'INSERT INTO escape_room_progress
                (user_id, destination_id, puzzles_solved, is_complete, fragment_item, completed_at)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                puzzles_solved = VALUES(puzzles_solved),
                is_complete = VALUES(is_complete),
                fragment_item = VALUES(fragment_item),
                completed_at = VALUES(completed_at)'
        );
        $stmt->execute([
            $userId,
            $data['destination_id'],
            $puzzlesSolved,
            $isComplete,
            $data['fragment_item'] ?? null,
            $isComplete ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function getFragments(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT destination_id, fragment_item, completed_at
             FROM escape_room_progress
             WHERE user_id = ? AND is_complete = 1 AND fragment_item IS NOT NULL
             ORDER BY completed_at ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
