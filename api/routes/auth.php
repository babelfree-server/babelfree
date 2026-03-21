<?php

function handleAuthRoutes(string $action, string $method): void {
    $pdo = getDB();
    $userModel = new User($pdo);
    $sessionModel = new Session($pdo);
    $config = require __DIR__ . '/../config/app.php';

    switch ($action) {
        case 'me':
            if ($method !== 'GET') jsonError('Método no permitido', 405);
            $user = authenticateRequest();
            jsonSuccess($user);
            break;

        case 'register':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            checkRateLimit('register');

            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');

            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $displayName = sanitizeString($input['display_name'] ?? '', 100);
            $userType = in_array($input['user_type'] ?? '', ['individual', 'classroom'])
                ? $input['user_type'] : 'individual';
            $role = in_array($input['role'] ?? '', ['student', 'teacher'])
                ? $input['role'] : ($userType === 'classroom' ? 'teacher' : 'student');
            $interfaceLang = sanitizeString($input['interface_lang'] ?? 'es', 10);
            $detectedLang = isset($input['detected_lang'])
                ? sanitizeString($input['detected_lang'], 10) : null;
            $nativeLang = isset($input['native_lang'])
                ? sanitizeString($input['native_lang'], 10) : null;
            $gender = in_array($input['gender'] ?? '', ['M', 'F', 'X'])
                ? $input['gender'] : null;
            $dob = isset($input['dob']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['dob'])
                ? $input['dob'] : null;
            $country = isset($input['country']) ? sanitizeString($input['country'], 2) : null;
            $phone = isset($input['phone']) ? sanitizeString($input['phone'], 30) : null;
            $source = in_array($input['source'] ?? '', ['search', 'social', 'friend', 'teacher', 'other'])
                ? $input['source'] : null;
            $goal = in_array($input['goal'] ?? '', ['travel', 'work', 'school', 'heritage', 'curiosity'])
                ? $input['goal'] : null;
            $marketingConsent = !empty($input['marketing_consent']) ? 1 : 0;
            $dataConsent = !empty($input['data_consent']) ? 1 : 0;

            if (!validateEmail($email)) jsonError('Email inválido');
            if (!validatePassword($password)) jsonError('La contraseña debe tener al menos 10 caracteres, con mayúscula, minúscula y número');
            if (mb_strlen($displayName) < 1) jsonError('El nombre es obligatorio');

            if ($userModel->findByEmail($email)) {
                jsonError('Ya existe una cuenta con este email');
            }

            $verifyToken = generateToken();
            $verifyExpires = date('Y-m-d H:i:s', time() + $config['verify_expiry_hours'] * 3600);

            $userId = $userModel->create([
                'email'          => $email,
                'password'       => $password,
                'display_name'   => $displayName,
                'user_type'      => $userType,
                'role'           => $role,
                'interface_lang'  => $interfaceLang,
                'detected_lang'  => $detectedLang,
                'native_lang'    => $nativeLang,
                'gender'         => $gender,
                'dob'            => $dob,
                'country'        => $country,
                'phone'          => $phone,
                'source'         => $source,
                'goal'           => $goal,
                'marketing_consent' => $marketingConsent,
                'data_consent'   => $dataConsent,
                'verify_token'   => $verifyToken,
                'verify_expires' => $verifyExpires,
            ]);

            sendVerificationEmail($email, $displayName, $verifyToken);

            jsonSuccess(['message' => 'Cuenta creada. Revisa tu correo para verificar tu cuenta.'], 201);
            break;

        case 'login':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            checkRateLimit('login');

            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');

            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';

            if (!$email || !$password) jsonError('Email y contraseña son obligatorios');

            // Account lockout: check failed attempts
            $lockoutKey = 'jaguar_lockout:' . md5($email);
            $lockedOut = false;
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $failCount = (int)$redis->get($lockoutKey);
                if ($failCount >= 10) {
                    $ttl = $redis->ttl($lockoutKey);
                    error_log("Account locked out: {$email} ({$failCount} failures, {$ttl}s remaining)");
                    jsonError('Cuenta bloqueada temporalmente por demasiados intentos fallidos. Intenta en ' . ceil($ttl / 60) . ' minutos.', 429);
                }
                $redis->close();
            } catch (\Exception $e) {
                // Redis down — skip lockout check (rate limit will still apply)
            }

            $user = $userModel->findByEmail($email);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                // Increment failed attempt counter
                try {
                    $redis = new Redis();
                    $redis->connect('127.0.0.1', 6379);
                    $redis->incr($lockoutKey);
                    $redis->expire($lockoutKey, 1800); // 30 min window
                    $redis->close();
                } catch (\Exception $e) {}
                error_log("Failed login attempt for: {$email} from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                jsonError('Email o contraseña incorrectos', 401);
            }

            // Clear lockout on successful login
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->del($lockoutKey);
                $redis->close();
            } catch (\Exception $e) {}

            if (!$user['is_active']) {
                jsonError('Cuenta desactivada', 403);
            }

            // Rehash if needed
            if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
                $userModel->updatePassword((int)$user['id'], $password);
            }

            $token = $sessionModel->create((int)$user['id'], $config['token_expiry_days']);
            $userModel->updateLastLogin((int)$user['id']);

            // Set CSRF cookie
            $csrfToken = generateToken();
            setCsrfCookie($csrfToken);

            // Determine immersion lock
            $immersionLocked = in_array($user['cefr_level'], $config['immersion_locked_levels']);

            jsonSuccess([
                'token' => $token,
                'user'  => [
                    'id'              => (int)$user['id'],
                    'email'           => $user['email'],
                    'display_name'    => $user['display_name'],
                    'user_type'       => $user['user_type'],
                    'role'            => $user['role'] ?? 'student',
                    'tier'            => $user['tier'] ?? 'free',
                    'cefr_level'      => $user['cefr_level'],
                    'interface_lang'   => $immersionLocked ? 'es' : $user['interface_lang'],
                    'detected_lang'   => $user['detected_lang'],
                    'native_lang'     => $user['native_lang'],
                    'gender'          => $user['gender'],
                    'email_verified'  => (bool)$user['email_verified'],
                    'immersion_locked' => $immersionLocked,
                ],
            ]);
            break;

        case 'logout':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $m)) {
                $sessionModel->revoke($m[1]);
            }
            jsonSuccess(['message' => 'Sesión cerrada']);
            break;

        case 'forgot':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            checkRateLimit('forgot');

            $input = getJsonBody();
            $email = trim($input['email'] ?? '');

            // Always return 200 to prevent email enumeration
            if ($email) {
                $user = $userModel->findByEmail($email);
                if ($user && $user['is_active']) {
                    $resetToken = generateToken();
                    $resetExpires = date('Y-m-d H:i:s', time() + $config['reset_expiry_hours'] * 3600);
                    $userModel->setResetToken((int)$user['id'], $resetToken, $resetExpires);
                    sendPasswordResetEmail($email, $user['display_name'], $resetToken);
                }
            }

            jsonSuccess(['message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.']);
            break;

        case 'reset':
            if ($method !== 'POST') jsonError('Método no permitido', 405);

            $input = getJsonBody();
            $token = $input['token'] ?? '';
            $newPassword = $input['password'] ?? '';

            if (!$token || !$newPassword) jsonError('Datos incompletos');
            if (!validatePassword($newPassword)) jsonError('La contraseña debe tener al menos 10 caracteres, con mayúscula, minúscula y número');

            $user = $userModel->findByResetToken($token);
            if (!$user) {
                jsonError('Enlace inválido o expirado');
            }

            $userModel->updatePassword((int)$user['id'], $newPassword);
            $sessionModel->revokeAll((int)$user['id']);

            jsonSuccess(['message' => 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.']);
            break;

        case 'verify':
            if ($method !== 'GET') jsonError('Método no permitido', 405);
            checkRateLimit('general');

            $token = $_GET['token'] ?? '';
            if (!$token) jsonError('Token requerido');

            if ($userModel->verify($token)) {
                jsonSuccess(['message' => 'Email verificado correctamente. Ya puedes iniciar sesión.']);
            } else {
                jsonError('Enlace de verificación inválido o expirado');
            }
            break;

        case 'delete-account':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            $user = authenticateRequest();
            validateCsrf();
            checkRateLimit('general');

            $input = getJsonBody();
            $password = $input['password'] ?? '';
            if (!$password) jsonError('La contraseña es obligatoria');

            $fullUser = $userModel->findById($user['id']);
            if (!$fullUser || !password_verify($password, $fullUser['password_hash'])) {
                jsonError('Contraseña incorrecta', 401);
            }

            // CASCADE handles sessions, progress, stats, settings, escape_room_progress
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);

            jsonSuccess(['message' => 'Cuenta eliminada permanentemente']);
            break;

        case 'export-data':
            if ($method !== 'GET') jsonError('Método no permitido', 405);
            checkRateLimit('general');
            $user = authenticateRequest();

            $fullUser = $userModel->findById($user['id']);
            // Strip sensitive fields
            unset($fullUser['password_hash'], $fullUser['verify_token'], $fullUser['verify_expires'],
                  $fullUser['reset_token'], $fullUser['reset_expires']);

            $a1Model = new A1Progress($pdo);
            $destModel = new DestProgress($pdo);
            $statsModel = new UserStats($pdo);
            $settingsModel = new UserSettings($pdo);
            $escapeModel = new EscapeRoomProgress($pdo);

            $stats = $statsModel->get($user['id']);
            if ($stats) unset($stats['id'], $stats['user_id']);

            $settings = $settingsModel->get($user['id']);
            if ($settings) unset($settings['id'], $settings['user_id']);

            $export = [
                'exported_at'           => date('c'),
                'account'               => $fullUser,
                'a1_progress'           => $a1Model->getAllByUser($user['id']),
                'destination_progress'  => $destModel->getAllByUser($user['id']),
                'escape_room_progress'  => $escapeModel->getAllByUser($user['id']),
                'stats'                 => $stats,
                'settings'              => $settings,
            ];

            jsonSuccess($export);
            break;

        case 'update-profile':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            $user = authenticateRequest();
            $input = getJsonBody();
            if (!$input) jsonError('Datos inválidos');

            $updates = [];
            $params = [];

            if (isset($input['display_name'])) {
                $dn = sanitizeString($input['display_name'], 100);
                if (mb_strlen($dn) < 1) jsonError('El nombre es obligatorio');
                $updates[] = 'display_name = ?';
                $params[] = $dn;
            }
            if (isset($input['gender']) && in_array($input['gender'], ['M', 'F', 'X'])) {
                $updates[] = 'gender = ?';
                $params[] = $input['gender'];
            }
            if (isset($input['native_lang'])) {
                $updates[] = 'native_lang = ?';
                $params[] = sanitizeString($input['native_lang'], 10);
            }
            if (isset($input['dob'])) {
                $updates[] = 'dob = ?';
                $params[] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['dob']) ? $input['dob'] : null;
            }
            if (isset($input['country'])) {
                $updates[] = 'country = ?';
                $params[] = sanitizeString($input['country'], 2);
            }
            if (isset($input['phone'])) {
                $updates[] = 'phone = ?';
                $params[] = sanitizeString($input['phone'], 30);
            }
            if (isset($input['interface_lang'])) {
                $updates[] = 'interface_lang = ?';
                $params[] = sanitizeString($input['interface_lang'], 10);
            }
            if (isset($input['marketing_consent'])) {
                $updates[] = 'marketing_consent = ?';
                $params[] = !empty($input['marketing_consent']) ? 1 : 0;
            }

            if (empty($updates)) jsonError('No hay cambios');

            $params[] = $user['id'];
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $updated = $userModel->findById($user['id']);
            unset($updated['password_hash'], $updated['verify_token'], $updated['verify_expires'],
                  $updated['reset_token'], $updated['reset_expires']);
            jsonSuccess($updated);
            break;

        case 'change-password':
            if ($method !== 'POST') jsonError('Método no permitido', 405);
            $user = authenticateRequest();
            checkRateLimit('general');
            $input = getJsonBody();

            $currentPw = $input['current_password'] ?? '';
            $newPw = $input['new_password'] ?? '';

            if (!$currentPw || !$newPw) jsonError('Ambas contraseñas son obligatorias');
            if (!validatePassword($newPw)) jsonError('La nueva contraseña debe tener al menos 10 caracteres, con mayúscula, minúscula y número');

            $fullUser = $userModel->findById($user['id']);
            if (!$fullUser || !password_verify($currentPw, $fullUser['password_hash'])) {
                jsonError('Contraseña actual incorrecta', 401);
            }

            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($newPw, PASSWORD_ARGON2ID), $user['id']]);

            jsonSuccess(['message' => 'Contraseña actualizada correctamente']);
            break;

        default:
            jsonError('Ruta no encontrada', 404);
    }
}
