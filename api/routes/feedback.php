<?php

function handleFeedbackRoutes(string $action, string $method): void {
    $pdo = getDB();

    // Parse action — could be "submit", "list", or a numeric ID for PATCH
    $actionParts = explode('/', $action);
    $mainAction = $actionParts[0] ?? '';

    switch ($mainAction) {
        case 'submit':
            if ($method !== 'POST') jsonError('Método no permitido', 405);

            // CSRF protection for authenticated users
            if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
                validateCsrf();
            }

            // Rate limit: 10 per hour per IP
            checkFeedbackRateLimit();

            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');

            $message = trim($input['message'] ?? '');
            if (!$message) jsonError('El mensaje es obligatorio');
            if (mb_strlen($message) > 5000) jsonError('El mensaje no puede exceder 5000 caracteres');

            $feedbackType = in_array($input['feedback_type'] ?? '', ['error', 'suggestion', 'content', 'technical', 'question', 'other'])
                ? $input['feedback_type'] : 'other';

            $destination = isset($input['destination']) ? sanitizeString($input['destination'], 20) : null;
            $gameIndex = isset($input['game_index']) ? (int)$input['game_index'] : null;
            $gameType = isset($input['game_type']) ? sanitizeString($input['game_type'], 50) : null;
            $contextSnapshot = isset($input['context_snapshot']) ? mb_substr($input['context_snapshot'], 0, 10000) : null;
            $url = isset($input['url']) ? sanitizeString($input['url'], 500) : null;
            $userAgent = sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? '', 500);

            // Auth is optional — try to get user info if token is present
            $userId = null;
            $userEmail = null;
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $m)) {
                $token = $m[1];
                $stmt = $pdo->prepare(
                    'SELECT s.user_id, u.email
                     FROM sessions s
                     JOIN users u ON u.id = s.user_id
                     WHERE s.token = ? AND s.is_revoked = 0 AND s.expires_at > NOW() AND u.is_active = 1
                     LIMIT 1'
                );
                $stmt->execute([$token]);
                $row = $stmt->fetch();
                if ($row) {
                    $userId = (int)$row['user_id'];
                    $userEmail = $row['email'];
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO feedback (user_id, user_email, destination, game_index, game_type, feedback_type, message, context_snapshot, url, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId, $userEmail, $destination, $gameIndex, $gameType,
                $feedbackType, $message, $contextSnapshot, $url, $userAgent
            ]);

            $id = (int)$pdo->lastInsertId();

            // Grammar question auto-responder
            $grammarResponseSent = false;
            if ($feedbackType === 'question' && $userId && $userEmail) {
                require_once __DIR__ . '/../helpers/grammar-responder.php';

                // Rate limit: 3 grammar questions per hour per user
                if (checkGrammarQuestionRateLimit($userId)) {
                    // Get user's CEFR level and native language
                    $userStmt = $pdo->prepare(
                        'SELECT display_name, cefr_level, native_lang FROM users WHERE id = ? LIMIT 1'
                    );
                    $userStmt->execute([$userId]);
                    $userData = $userStmt->fetch();

                    $userName = $userData['display_name'] ?? 'Estudiante';
                    $userCefr = $userData['cefr_level'] ?? 'A1';
                    $userNativeLang = $userData['native_lang'] ?? 'en';

                    $result = handleGrammarQuestion(
                        $message,
                        $userEmail,
                        $userName,
                        $userCefr,
                        $userNativeLang,
                        $destination,
                        $gameType
                    );

                    $grammarResponseSent = $result['success'] ?? false;

                    // Store the response source in admin_notes
                    if ($grammarResponseSent) {
                        $noteText = '[Auto-responded: ' . ($result['source'] ?? 'unknown') . '] ' . date('Y-m-d H:i:s');
                        $updateStmt = $pdo->prepare('UPDATE feedback SET admin_notes = ? WHERE id = ?');
                        $updateStmt->execute([$noteText, $id]);

                        // Auto-publish to forum
                        if (in_array($result['source'] ?? '', ['kb', 'kb+translated'])) {
                            autoPublishToForum($pdo, $id, $message, $result, $userCefr);
                        }
                    }
                } else {
                    error_log("Grammar question rate limited for user {$userId}");
                }
            }

            jsonSuccess(['id' => $id, 'grammar_response_sent' => $grammarResponseSent], 201);
            break;

        case 'list':
            if ($method !== 'GET') jsonError('Método no permitido', 405);

            // Admin only
            $user = authenticateRequest();
            if (($user['role'] ?? '') !== 'admin') {
                jsonError('No autorizado', 403);
            }

            // Filters
            $where = [];
            $params = [];

            if (!empty($_GET['status'])) {
                $where[] = 'status = ?';
                $params[] = $_GET['status'];
            }
            if (!empty($_GET['destination'])) {
                $where[] = 'destination = ?';
                $params[] = $_GET['destination'];
            }
            if (!empty($_GET['type'])) {
                $where[] = 'feedback_type = ?';
                $params[] = $_GET['type'];
            }

            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            // Pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback {$whereClause}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // Fetch items (LIMIT/OFFSET as bound parameters)
            $params[] = $limit;
            $params[] = $offset;
            $stmt = $pdo->prepare(
                "SELECT * FROM feedback {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?"
            );
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            jsonSuccess([
                'items' => $items,
                'total' => $total,
                'page'  => $page,
                'pages' => (int)ceil($total / $limit),
            ]);
            break;

        default:
            if (!is_numeric($mainAction)) {
                jsonError('Ruta no encontrada', 404);
            }

            $user = authenticateRequest();
            if (($user['role'] ?? '') !== 'admin') {
                jsonError('No autorizado', 403);
            }

            $feedbackId = (int)$mainAction;
            $subAction = $actionParts[1] ?? '';

            // POST /feedback/{id}/reply — send email reply to student
            if ($subAction === 'reply' && $method === 'POST') {
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $replyText = trim($input['reply'] ?? '');
                if (!$replyText) jsonError('La respuesta es obligatoria');
                if (mb_strlen($replyText) > 10000) jsonError('La respuesta no puede exceder 10000 caracteres');

                // Get feedback + user info
                $stmt = $pdo->prepare(
                    'SELECT f.*, u.display_name FROM feedback f
                     LEFT JOIN users u ON u.id = f.user_id
                     WHERE f.id = ? LIMIT 1'
                );
                $stmt->execute([$feedbackId]);
                $feedback = $stmt->fetch();
                if (!$feedback) jsonError('Feedback no encontrado', 404);
                if (!$feedback['user_email']) jsonError('Este feedback no tiene email asociado');

                require_once __DIR__ . '/../helpers/grammar-responder.php';

                $studentName = $feedback['display_name'] ?? 'Estudiante';
                $sent = sendAdminReply(
                    $feedback['user_email'],
                    $studentName,
                    $feedback['message'],
                    $replyText
                );

                if (!$sent) jsonError('Error al enviar el email', 500);

                // Update status to resolved and append reply note
                $now = date('Y-m-d H:i:s');
                $existingNotes = $feedback['admin_notes'] ?? '';
                $replyNote = "[Replied {$now}] " . mb_substr($replyText, 0, 200);
                $newNotes = $existingNotes ? $existingNotes . "\n" . $replyNote : $replyNote;

                $stmt = $pdo->prepare(
                    'UPDATE feedback SET status = ?, admin_notes = ?, resolved_at = ? WHERE id = ?'
                );
                $stmt->execute(['resolved', $newNotes, $now, $feedbackId]);

                // Auto-publish Q&A to forum (for question-type feedback)
                if (($feedback['feedback_type'] ?? '') === 'question') {
                    autoPublishToForum($pdo, $feedbackId, $feedback['message'], [
                        'source' => 'admin',
                        'answer' => $replyText,
                    ], 'A1');
                }

                jsonSuccess(['message' => 'Respuesta enviada']);
            }

            // PATCH /feedback/{id} — update status/notes
            if ($method === 'PATCH') {
                validateCsrf();
                $input = getJsonBody();
                if (!$input) jsonError('Datos inválidos');

                $newStatus = $input['status'] ?? '';
                if (!in_array($newStatus, ['new', 'reviewed', 'accepted', 'resolved', 'dismissed'])) {
                    jsonError('Estado inválido');
                }

                $adminNotes = isset($input['admin_notes']) ? mb_substr($input['admin_notes'], 0, 5000) : null;

                // Check feedback exists
                $stmt = $pdo->prepare('SELECT id FROM feedback WHERE id = ?');
                $stmt->execute([$feedbackId]);
                if (!$stmt->fetch()) {
                    jsonError('Feedback no encontrado', 404);
                }

                $resolvedAt = ($newStatus === 'resolved') ? date('Y-m-d H:i:s') : null;

                if ($adminNotes !== null) {
                    $stmt = $pdo->prepare(
                        'UPDATE feedback SET status = ?, admin_notes = ?, resolved_at = ? WHERE id = ?'
                    );
                    $stmt->execute([$newStatus, $adminNotes, $resolvedAt, $feedbackId]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE feedback SET status = ?, resolved_at = ? WHERE id = ?'
                    );
                    $stmt->execute([$newStatus, $resolvedAt, $feedbackId]);
                }

                jsonSuccess(['message' => 'Feedback actualizado']);
            }

            jsonError('Método no permitido', 405);
            break;
    }
}

/**
 * Feedback-specific rate limit: 10 submissions per hour per IP
 */
function checkFeedbackRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = "jaguar_rl:feedback:{$ip}";
    $max = 10;
    $window = 3600; // 1 hour

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);

        $current = (int)$redis->get($key);
        if ($current >= $max) {
            $ttl = $redis->ttl($key);
            header("Retry-After: {$ttl}");
            jsonError('Demasiados envíos. Intenta de nuevo más tarde.', 429);
        }

        $redis->incr($key);
        if ($current === 0) {
            $redis->expire($key, $window);
        }
        $redis->close();
    } catch (\Exception $e) {
        // If Redis is down, deny the request (fail closed)
        error_log("Redis feedback rate limit error (blocking request): " . $e->getMessage());
        jsonError('Servicio temporalmente no disponible. Intenta de nuevo en unos minutos.', 503);
    }
}
