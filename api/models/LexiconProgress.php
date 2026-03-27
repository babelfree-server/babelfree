<?php

class LexiconProgress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByUser(int $userId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT lexicon_data, word_count, updated_at
             FROM lexicon_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['lexicon_data'] = json_decode($row['lexicon_data'], true) ?: [];
        $row['word_count']   = (int) $row['word_count'];
        return $row;
    }

    public function upsert(int $userId, array $data): void {
        $lexiconData = json_encode($data['lexicon_data'] ?? []);
        $wordCount   = max(0, (int) ($data['word_count'] ?? 0));

        $stmt = $this->pdo->prepare(
            'INSERT INTO lexicon_progress
                (user_id, lexicon_data, word_count)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                lexicon_data = VALUES(lexicon_data),
                word_count   = VALUES(word_count)'
        );
        $stmt->execute([
            $userId,
            $lexiconData,
            $wordCount,
        ]);
    }
}
