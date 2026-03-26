<?php

class AdventureProgress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByUser(int $userId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT chapters, earned_letters, earned_words, earned_sentences,
                    composition_revealed, total_words_written, started_at, updated_at
             FROM adventure_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['chapters']         = json_decode($row['chapters'], true) ?: [];
        $row['earned_letters']   = json_decode($row['earned_letters'], true) ?: [];
        $row['earned_words']     = json_decode($row['earned_words'], true) ?: [];
        $row['earned_sentences'] = json_decode($row['earned_sentences'], true) ?: [];
        $row['composition_revealed'] = (bool) $row['composition_revealed'];
        $row['total_words_written']  = (int) $row['total_words_written'];
        return $row;
    }

    public function upsert(int $userId, array $data): void {
        $chapters        = json_encode($data['chapters'] ?? []);
        $earnedLetters   = json_encode($data['earned_letters'] ?? []);
        $earnedWords     = json_encode($data['earned_words'] ?? []);
        $earnedSentences = json_encode($data['earned_sentences'] ?? []);
        $revealed        = !empty($data['composition_revealed']) ? 1 : 0;
        $totalWords      = max(0, (int) ($data['total_words_written'] ?? 0));
        $startedAt       = $data['started_at'] ?? null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO adventure_progress
                (user_id, chapters, earned_letters, earned_words, earned_sentences,
                 composition_revealed, total_words_written, started_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                chapters             = VALUES(chapters),
                earned_letters       = VALUES(earned_letters),
                earned_words         = VALUES(earned_words),
                earned_sentences     = VALUES(earned_sentences),
                composition_revealed = VALUES(composition_revealed),
                total_words_written  = VALUES(total_words_written),
                started_at           = COALESCE(started_at, VALUES(started_at))'
        );
        $stmt->execute([
            $userId,
            $chapters,
            $earnedLetters,
            $earnedWords,
            $earnedSentences,
            $revealed,
            $totalWords,
            $startedAt,
        ]);
    }
}
