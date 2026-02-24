<?php

class Glossary {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get vocabulary list filtered by ecosystem, CEFR level, or destination.
     * Includes user mastery data if user_id provided.
     */
    public function getWords(int $userId, ?string $ecosystem = null, ?string $cefrLevel = null, ?string $destId = null, int $limit = 200, int $offset = 0, ?string $lang = null): array {
        $sql = 'SELECT g.id, g.word, g.cefr_level, g.ecosystem, g.destination_id,
                       g.game_type, g.emoji, g.dict_word_id,
                       COALESCE(uv.times_seen, 0) as times_seen,
                       COALESCE(uv.times_correct, 0) as times_correct,
                       COALESCE(uv.mastery_level, 0) as mastery_level,
                       uv.last_seen_at,
                       dw.part_of_speech, dw.gender, dw.pronunciation_ipa, dw.frequency_rank';
        $params = [];

        if ($lang && $lang !== 'es') {
            $sql .= ', gt.translation';
        }

        $sql .= ' FROM glossary_words g
                LEFT JOIN user_vocabulary uv ON uv.glossary_word_id = g.id AND uv.user_id = ?
                LEFT JOIN dict_words dw ON dw.id = g.dict_word_id';
        $params[] = $userId;

        if ($lang && $lang !== 'es') {
            $sql .= ' LEFT JOIN glossary_translations gt ON gt.glossary_word_id = g.id AND gt.lang_code = ?';
            $params[] = $lang;
        }

        $sql .= ' WHERE 1=1';

        if ($ecosystem) {
            $sql .= ' AND g.ecosystem = ?';
            $params[] = $ecosystem;
        }
        if ($cefrLevel) {
            $sql .= ' AND g.cefr_level = ?';
            $params[] = $cefrLevel;
        }
        if ($destId) {
            $sql .= ' AND g.destination_id = ?';
            $params[] = $destId;
        }

        $sql .= ' ORDER BY g.cefr_level, g.word LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single word detail with game examples and translation.
     */
    public function getWord(int $glossaryWordId, int $userId, ?string $interfaceLang = null): ?array {
        // Word details
        $stmt = $this->pdo->prepare(
            'SELECT g.*, dw.part_of_speech, dw.gender, dw.pronunciation_ipa,
                    dw.frequency_rank, dw.cefr_level as dict_cefr,
                    COALESCE(uv.times_seen, 0) as times_seen,
                    COALESCE(uv.times_correct, 0) as times_correct,
                    COALESCE(uv.mastery_level, 0) as mastery_level
             FROM glossary_words g
             LEFT JOIN dict_words dw ON dw.id = g.dict_word_id
             LEFT JOIN user_vocabulary uv ON uv.glossary_word_id = g.id AND uv.user_id = ?
             WHERE g.id = ?'
        );
        $stmt->execute([$userId, $glossaryWordId]);
        $word = $stmt->fetch();

        if (!$word) return null;

        // Game examples
        $stmt = $this->pdo->prepare(
            'SELECT sentence_es, sentence_gloss, cefr_level, game_type, source_file
             FROM glossary_examples WHERE glossary_word_id = ? ORDER BY id LIMIT 10'
        );
        $stmt->execute([$glossaryWordId]);
        $word['examples'] = $stmt->fetchAll();

        // Translation in student's language
        if ($interfaceLang && $interfaceLang !== 'es') {
            $stmt = $this->pdo->prepare(
                'SELECT translation FROM glossary_translations
                 WHERE glossary_word_id = ? AND lang_code = ? LIMIT 1'
            );
            $stmt->execute([$glossaryWordId, $interfaceLang]);
            $trans = $stmt->fetch();
            $word['translation'] = $trans ? $trans['translation'] : null;
        }

        return $word;
    }

    /**
     * Mastery statistics by ecosystem and level.
     */
    public function getStats(int $userId): array {
        // Total words per ecosystem
        $stmt = $this->pdo->prepare(
            'SELECT g.ecosystem, g.cefr_level,
                    COUNT(DISTINCT g.id) as total_words,
                    COUNT(DISTINCT CASE WHEN uv.mastery_level >= 1 THEN g.id END) as seen,
                    COUNT(DISTINCT CASE WHEN uv.mastery_level >= 2 THEN g.id END) as practiced,
                    COUNT(DISTINCT CASE WHEN uv.mastery_level >= 3 THEN g.id END) as mastered
             FROM glossary_words g
             LEFT JOIN user_vocabulary uv ON uv.glossary_word_id = g.id AND uv.user_id = ?
             GROUP BY g.ecosystem, g.cefr_level
             ORDER BY g.ecosystem, g.cefr_level'
        );
        $stmt->execute([$userId]);
        $breakdown = $stmt->fetchAll();

        // Overall totals
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT g.id) as total_words,
                    COUNT(DISTINCT CASE WHEN uv.mastery_level >= 1 THEN g.id END) as seen,
                    COUNT(DISTINCT CASE WHEN uv.mastery_level >= 3 THEN g.id END) as mastered
             FROM glossary_words g
             LEFT JOIN user_vocabulary uv ON uv.glossary_word_id = g.id AND uv.user_id = ?'
        );
        $stmt->execute([$userId]);
        $totals = $stmt->fetch();

        return [
            'totals' => $totals,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Search game vocabulary by query.
     */
    public function search(string $query, ?int $userId = null, int $limit = 20, ?string $lang = null): array {
        $normalized = mb_strtolower(trim($query), 'UTF-8');
        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        }

        $sql = 'SELECT DISTINCT g.id, g.word, g.cefr_level, g.ecosystem, g.destination_id, g.emoji';
        $params = [];

        if ($userId) {
            $sql .= ', COALESCE(uv.mastery_level, 0) as mastery_level';
        }

        if ($lang && $lang !== 'es') {
            $sql .= ', gt.translation';
        }

        $sql .= ' FROM glossary_words g';

        if ($userId) {
            $sql .= ' LEFT JOIN user_vocabulary uv ON uv.glossary_word_id = g.id AND uv.user_id = ?';
            $params[] = $userId;
        }

        if ($lang && $lang !== 'es') {
            $sql .= ' LEFT JOIN glossary_translations gt ON gt.glossary_word_id = g.id AND gt.lang_code = ?';
            $params[] = $lang;
        }

        $sql .= ' WHERE (g.word_normalized LIKE ?';
        $params[] = $normalized . '%';

        // Also search in translations if a language is selected
        if ($lang && $lang !== 'es') {
            $sql .= ' OR gt.translation LIKE ?';
            $params[] = '%' . $query . '%';
        }

        $sql .= ') ORDER BY g.word LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Record vocabulary encounter from game.
     * Updates user_vocabulary with times_seen, times_correct, and recalculates mastery.
     */
    public function recordEncounter(int $userId, array $wordsSeen, array $wordsCorrect, string $destinationId): int {
        $updated = 0;

        // Resolve word strings to glossary_word_ids
        $findWord = $this->pdo->prepare(
            'SELECT id FROM glossary_words WHERE word_normalized = ? AND destination_id = ? LIMIT 1'
        );
        $findWordAny = $this->pdo->prepare(
            'SELECT id FROM glossary_words WHERE word_normalized = ? LIMIT 1'
        );

        $upsert = $this->pdo->prepare(
            'INSERT INTO user_vocabulary (user_id, glossary_word_id, times_seen, times_correct, mastery_level)
             VALUES (?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE
               times_seen = times_seen + 1,
               times_correct = times_correct + VALUES(times_correct),
               mastery_level = CASE
                 WHEN times_seen + 1 >= 5 AND (times_correct + VALUES(times_correct)) / (times_seen + 1) >= 0.8 THEN 3
                 WHEN times_seen + 1 >= 3 THEN 2
                 ELSE 1
               END'
        );

        foreach ($wordsSeen as $word) {
            $normalized = mb_strtolower(trim($word), 'UTF-8');
            if (function_exists('transliterator_transliterate')) {
                $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
            }

            // Find glossary word (prefer same destination, fallback to any)
            $findWord->execute([$normalized, $destinationId]);
            $row = $findWord->fetch();
            if (!$row) {
                $findWordAny->execute([$normalized]);
                $row = $findWordAny->fetch();
            }
            if (!$row) continue;

            $glossaryWordId = (int)$row['id'];
            $isCorrect = in_array($word, $wordsCorrect) ? 1 : 0;
            $initialMastery = 1; // seen

            $upsert->execute([$userId, $glossaryWordId, $isCorrect, $initialMastery]);
            $updated++;
        }

        return $updated;
    }
}
