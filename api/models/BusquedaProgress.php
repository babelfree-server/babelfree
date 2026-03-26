<?php

class BusquedaProgress {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByUser(int $userId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT solved_riddles, bridge_segments, rana_opacity, rana_name,
                    journal_entries, updated_at
             FROM busqueda_progress WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['solved_riddles']  = json_decode($row['solved_riddles'], true) ?: [];
        $row['journal_entries'] = json_decode($row['journal_entries'], true) ?: [];
        $row['bridge_segments'] = (int) $row['bridge_segments'];
        $row['rana_opacity']    = (float) $row['rana_opacity'];
        return $row;
    }

    public function upsert(int $userId, array $data): void {
        $solvedRiddles  = json_encode($data['solved_riddles'] ?? []);
        $journalEntries = json_encode($data['journal_entries'] ?? []);
        $bridgeSegments = min(89, max(0, (int) ($data['bridge_segments'] ?? 0)));
        $ranaOpacity    = min(1.0, max(0.0, (float) ($data['rana_opacity'] ?? 0.0)));
        $ranaName       = isset($data['rana_name']) && $data['rana_name'] !== ''
            ? mb_substr($data['rana_name'], 0, 30) : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO busqueda_progress
                (user_id, solved_riddles, bridge_segments, rana_opacity, rana_name, journal_entries)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                solved_riddles  = VALUES(solved_riddles),
                bridge_segments = VALUES(bridge_segments),
                rana_opacity    = VALUES(rana_opacity),
                rana_name       = VALUES(rana_name),
                journal_entries = VALUES(journal_entries)'
        );
        $stmt->execute([
            $userId,
            $solvedRiddles,
            $bridgeSegments,
            $ranaOpacity,
            $ranaName,
            $journalEntries,
        ]);
    }
}
