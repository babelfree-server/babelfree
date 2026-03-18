<?php
// Early access email signup endpoint
// POST /api/early-access.php  body: {"email":"..."}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://babelfree.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

// Validate email
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Reasonable length check
if (strlen($email) > 255) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email address is too long.']);
    exit;
}

try {
    require __DIR__ . '/config/database.php';
    $pdo = getDB();

    $stmt = $pdo->prepare('INSERT IGNORE INTO early_access_signups (email, source) VALUES (:email, :source)');
    $stmt->execute(['email' => $email, 'source' => 'jaguar']);

    // rowCount 0 means duplicate (IGNORE skipped it), still success from user's perspective
    echo json_encode(['ok' => true, 'message' => "You're on the list! We'll notify you when we launch."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again later.']);
}
