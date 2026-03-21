<?php
/**
 * Tracking API — Silent evaluation engine
 * Records game performance, word mastery, grammar mastery
 * Calculates DELE readiness scores
 */

function handleTrackingRoutes(string $action, string $method): void {
    $pdo = getDB();

    switch ($action) {

    // Record game performance (called after every game)
    case 'game':
        if ($method !== 'POST') jsonError('Método no permitido', 405);
        $user = authenticateRequest();
        $input = getJsonBody();
        if (!$input) jsonError('Datos inválidos');

        $destId = sanitizeString($input['destination_id'] ?? '', 20);
        $gameIndex = (int)($input['game_index'] ?? 0);
        $gameType = sanitizeString($input['game_type'] ?? '', 30);
        $pipelineStage = (int)($input['pipeline_stage'] ?? 0);
        $totalItems = (int)($input['total_items'] ?? 0);
        $correctItems = (int)($input['correct_items'] ?? 0);
        $timeSeconds = isset($input['time_seconds']) ? (int)$input['time_seconds'] : null;
        $deleSkill = in_array($input['dele_skill'] ?? '', ['reading', 'listening', 'writing', 'speaking'])
            ? $input['dele_skill'] : null;

        if (!$destId || !$gameType) jsonError('Faltan campos obligatorios');

        // Get attempt number
        $stmt = $pdo->prepare('SELECT MAX(attempt_number) FROM game_performance WHERE user_id = ? AND destination_id = ? AND game_index = ?');
        $stmt->execute([$user['id'], $destId, $gameIndex]);
        $attempt = ($stmt->fetchColumn() ?: 0) + 1;

        $avgPerItem = ($totalItems > 0 && $timeSeconds) ? round($timeSeconds / $totalItems, 2) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO game_performance (user_id, destination_id, game_index, game_type, pipeline_stage, total_items, correct_items, time_seconds, avg_seconds_per_item, attempt_number, dele_skill)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $destId, $gameIndex, $gameType, $pipelineStage ?: null, $totalItems, $correctItems, $timeSeconds, $avgPerItem, $attempt, $deleSkill]);

        // Process word mastery updates if provided
        if (!empty($input['words'])) {
            foreach ($input['words'] as $wordData) {
                $word = mb_strtolower(trim($wordData['word'] ?? ''));
                if (!$word) continue;
                $correct = !empty($wordData['correct']);
                $stage = (int)($wordData['stage'] ?? $pipelineStage);

                // Upsert word mastery
                $stageCol = 'stage_' . min(max($stage, 1), 7);
                $stageMap = [1 => 'stage_1_exposed', 2 => 'stage_2_recognized', 3 => 'stage_3_matched',
                             4 => 'stage_4_produced_guided', 5 => 'stage_5_produced_free',
                             6 => 'stage_6_transferred', 7 => 'stage_7_spiral_reviewed'];
                $stageField = $stageMap[$stage] ?? 'stage_1_exposed';

                $stmt = $pdo->prepare(
                    "INSERT INTO word_mastery (user_id, word, lang_code, {$stageField}, times_correct, times_incorrect, last_seen_at, first_dest, last_dest)
                     VALUES (?, ?, 'es', 1, ?, ?, NOW(), ?, ?)
                     ON DUPLICATE KEY UPDATE
                        {$stageField} = 1,
                        times_correct = times_correct + VALUES(times_correct),
                        times_incorrect = times_incorrect + VALUES(times_incorrect),
                        last_seen_at = NOW(),
                        last_dest = VALUES(last_dest)"
                );
                $stmt->execute([
                    $user['id'], $word,
                    $correct ? 1 : 0,
                    $correct ? 0 : 1,
                    $destId, $destId
                ]);
            }
        }

        // Process grammar updates if provided
        if (!empty($input['grammar'])) {
            foreach ($input['grammar'] as $grammarData) {
                $structure = sanitizeString($grammarData['structure'] ?? '', 100);
                if (!$structure) continue;
                $correct = !empty($grammarData['correct']);

                $stmt = $pdo->prepare(
                    "INSERT INTO grammar_mastery (user_id, structure, times_correct, times_incorrect, first_dest, last_dest)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        times_correct = times_correct + VALUES(times_correct),
                        times_incorrect = times_incorrect + VALUES(times_incorrect),
                        last_dest = VALUES(last_dest)"
                );
                $stmt->execute([
                    $user['id'], $structure,
                    $correct ? 1 : 0,
                    $correct ? 0 : 1,
                    $destId, $destId
                ]);
            }
        }

        jsonSuccess(['recorded' => true, 'attempt' => $attempt]);
        break;

    // Get DELE readiness for a user
    case 'dele-readiness':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        $user = authenticateRequest();
        $level = sanitizeString($_GET['level'] ?? 'A1', 10);

        // Calculate from game_performance
        $skills = ['reading' => 0, 'listening' => 0, 'writing' => 0, 'speaking' => 0];
        $counts = ['reading' => 0, 'listening' => 0, 'writing' => 0, 'speaking' => 0];

        $stmt = $pdo->prepare(
            'SELECT dele_skill, AVG(accuracy) as avg_acc, COUNT(*) as cnt
             FROM game_performance
             WHERE user_id = ? AND dele_skill IS NOT NULL
             GROUP BY dele_skill'
        );
        $stmt->execute([$user['id']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skill = $row['dele_skill'];
            $skills[$skill] = round($row['avg_acc'], 2);
            $counts[$skill] = $row['cnt'];
        }

        // Count mastered words
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM word_mastery WHERE user_id = ? AND mastery_score >= 80');
        $stmt->execute([$user['id']]);
        $wordsMastered = $stmt->fetchColumn();

        // Upsert dele_readiness
        $overall = array_sum($skills) / 4;
        $gamesEvaluated = array_sum($counts);

        $stmt = $pdo->prepare(
            'INSERT INTO dele_readiness (user_id, cefr_level, reading_score, listening_score, writing_score, speaking_score, games_evaluated, words_mastered, last_calculated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                reading_score = VALUES(reading_score), listening_score = VALUES(listening_score),
                writing_score = VALUES(writing_score), speaking_score = VALUES(speaking_score),
                games_evaluated = VALUES(games_evaluated), words_mastered = VALUES(words_mastered),
                last_calculated_at = NOW()'
        );
        $stmt->execute([$user['id'], $level, $skills['reading'], $skills['listening'], $skills['writing'], $skills['speaking'], $gamesEvaluated, $wordsMastered]);

        jsonSuccess([
            'level' => $level,
            'reading' => $skills['reading'],
            'listening' => $skills['listening'],
            'writing' => $skills['writing'],
            'speaking' => $skills['speaking'],
            'overall' => round($overall, 2),
            'is_ready' => $skills['reading'] >= 60 && $skills['listening'] >= 60 && $skills['writing'] >= 60 && $skills['speaking'] >= 60,
            'games_evaluated' => $gamesEvaluated,
            'words_mastered' => $wordsMastered
        ]);
        break;

    // Get word mastery for a user
    case 'words':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        $user = authenticateRequest();
        $filter = $_GET['filter'] ?? 'all'; // all, weak, strong, review

        $where = 'user_id = ?';
        $params = [$user['id']];

        if ($filter === 'weak') { $where .= ' AND mastery_score < 60'; }
        elseif ($filter === 'strong') { $where .= ' AND mastery_score >= 80'; }
        elseif ($filter === 'review') { $where .= ' AND next_review_at <= NOW()'; }

        $stmt = $pdo->prepare("SELECT word, mastery_score, times_correct, times_incorrect, stage_1_exposed, stage_2_recognized, stage_3_matched, stage_4_produced_guided, stage_5_produced_free, stage_6_transferred, stage_7_spiral_reviewed, last_seen_at FROM word_mastery WHERE {$where} ORDER BY mastery_score ASC LIMIT 200");
        $stmt->execute($params);

        jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // Get grammar mastery
    case 'grammar':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        $user = authenticateRequest();

        $stmt = $pdo->prepare('SELECT structure, mastery_score, times_correct, times_incorrect FROM grammar_mastery WHERE user_id = ? ORDER BY mastery_score ASC');
        $stmt->execute([$user['id']]);

        jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // Get learning sessions summary
    case 'sessions':
        if ($method !== 'GET') jsonError('Método no permitido', 405);
        $user = authenticateRequest();

        $stmt = $pdo->prepare('SELECT DATE(started_at) as date, SUM(games_played) as games, SUM(total_seconds) as seconds, SUM(words_correct) as correct, SUM(words_encountered) as total FROM learning_sessions WHERE user_id = ? GROUP BY DATE(started_at) ORDER BY date DESC LIMIT 30');
        $stmt->execute([$user['id']]);

        jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        jsonError('Ruta no encontrada', 404);
}
}
