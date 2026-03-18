#!/usr/bin/env php
<?php
/**
 * Session cleanup — deletes expired and revoked sessions.
 * Run via cron: 0 3 * * * php /home/babelfree.com/public_html/api/scripts/cleanup-sessions.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Delete sessions expired more than 7 days ago (grace period for debugging)
$stmt = $pdo->prepare(
    'DELETE FROM sessions WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY) OR (is_revoked = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY))'
);
$stmt->execute();
$deleted = $stmt->rowCount();

echo date('Y-m-d H:i:s') . " — Cleaned up {$deleted} expired/revoked sessions.\n";
