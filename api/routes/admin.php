<?php

function handleAdminRoutes(string $action, string $method): void {
    $user = authenticateRequest();
    if (($user['role'] ?? '') !== 'admin') {
        jsonError('Acceso denegado', 403);
    }
    $pdo = getDB();

    switch ($action) {
        case 'students':
            if ($method === 'GET') {
                $stmt = $pdo->query("
                    SELECT u.id, u.email, u.display_name, u.role, u.created_at,
                        (SELECT COUNT(*) FROM destination_progress dp WHERE dp.user_id = u.id) as dests_completed,
                        (SELECT bp.rana_opacity FROM busqueda_progress bp WHERE bp.user_id = u.id LIMIT 1) as riddle_progress,
                        (SELECT ap.total_words_written FROM adventure_progress ap WHERE ap.user_id = u.id LIMIT 1) as words_written,
                        (SELECT ap.composition_revealed FROM adventure_progress ap WHERE ap.user_id = u.id LIMIT 1) as composition_complete,
                        (SELECT lp.word_count FROM lexicon_progress lp WHERE lp.user_id = u.id LIMIT 1) as vocab_size,
                        (SELECT MAX(s.last_active) FROM sessions s WHERE s.user_id = u.id) as last_active
                    FROM users u
                    ORDER BY u.created_at DESC
                ");
                $students = $stmt->fetchAll();
                jsonSuccess(['students' => $students, 'total' => count($students)]);
            }
            jsonError('Método no permitido', 405);
            break;

        case 'student':
            if ($method === 'GET') {
                $studentId = $_GET['id'] ?? null;
                if (!$studentId) jsonError('ID requerido');

                $stmt = $pdo->prepare("SELECT id, email, display_name, role, created_at FROM users WHERE id = ?");
                $stmt->execute([(int)$studentId]);
                $student = $stmt->fetch();
                if (!$student) jsonError('Estudiante no encontrado', 404);

                // Get destination progress
                $stmt2 = $pdo->prepare("SELECT dest_num, completed, score, updated_at FROM destination_progress WHERE user_id = ? ORDER BY dest_num");
                $stmt2->execute([(int)$studentId]);
                $destProgress = $stmt2->fetchAll();

                // Get adventure progress
                $stmt3 = $pdo->prepare("SELECT chapters, earned_letters, earned_words, earned_sentences, total_words_written, composition_revealed FROM adventure_progress WHERE user_id = ?");
                $stmt3->execute([(int)$studentId]);
                $adventure = $stmt3->fetch();
                if ($adventure) {
                    $adventure['chapters'] = json_decode($adventure['chapters'], true) ?: [];
                    $adventure['earned_letters'] = json_decode($adventure['earned_letters'], true) ?: [];
                    $adventure['earned_words'] = json_decode($adventure['earned_words'], true) ?: [];
                    $adventure['earned_sentences'] = json_decode($adventure['earned_sentences'], true) ?: [];
                }

                // Get busqueda progress
                $stmt4 = $pdo->prepare("SELECT solved_riddles, bridge_segments, rana_opacity, rana_name FROM busqueda_progress WHERE user_id = ?");
                $stmt4->execute([(int)$studentId]);
                $busqueda = $stmt4->fetch();
                if ($busqueda) {
                    $busqueda['solved_riddles'] = json_decode($busqueda['solved_riddles'], true) ?: [];
                }

                // Get lexicon
                $stmt5 = $pdo->prepare("SELECT word_count FROM lexicon_progress WHERE user_id = ?");
                $stmt5->execute([(int)$studentId]);
                $lexicon = $stmt5->fetch();

                jsonSuccess([
                    'student' => $student,
                    'destinations' => $destProgress,
                    'adventure' => $adventure,
                    'busqueda' => $busqueda,
                    'lexicon' => $lexicon,
                ]);
            }
            jsonError('Método no permitido', 405);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
