<?php
header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Honeypot check (spam bot trap)
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
    exit;
}

// Rate limiting via session
session_start();
$now = time();
if (isset($_SESSION['last_contact']) && ($now - $_SESSION['last_contact']) < 60) {
    echo json_encode(['success' => false, 'message' => 'Please wait a minute before sending another message.']);
    exit;
}

// Sanitize inputs
$name = trim(strip_tags($_POST['name'] ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$subject = trim(strip_tags($_POST['subject'] ?? 'General inquiry'));
$message = trim(strip_tags($_POST['message'] ?? ''));

// Validate
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($message) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long. Please keep it under 5000 characters.']);
    exit;
}

// Check for header injection attempts
if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input detected.']);
    exit;
}

// Build email
$to = 'info@babelfree.com';
$email_subject = "[Babel Free] $subject — from $name";

$body = "New contact form submission from babelfree.com\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Subject: $subject\n\n";
$body .= "Message:\n$message\n\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$body .= "Sent: " . date('Y-m-d H:i:s T') . "\n";
$body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

$headers = "From: noreply@babelfree.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: BabelFree-Contact/1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send
$sent = mail($to, $email_subject, $body, $headers);

if ($sent) {
    $_SESSION['last_contact'] = $now;
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent. We\'ll get back to you soon.']);
} else {
    echo json_encode(['success' => false, 'message' => 'There was a problem sending your message. Please email us directly at info@babelfree.com']);
}
