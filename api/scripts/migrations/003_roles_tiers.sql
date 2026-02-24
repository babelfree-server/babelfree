-- Migration 003: Add role + tier columns to users table
-- Supports student/teacher roles and free/premium tiers
-- user_type stays for backward compatibility; role + tier are the new source of truth

ALTER TABLE users
  ADD COLUMN role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student' AFTER user_type,
  ADD COLUMN tier ENUM('free','premium') NOT NULL DEFAULT 'free' AFTER role,
  ADD COLUMN premium_expires_at DATETIME DEFAULT NULL AFTER tier;

-- Derive role from existing user_type
UPDATE users SET role = 'teacher' WHERE user_type = 'classroom';
UPDATE users SET role = 'student' WHERE user_type = 'individual';

-- Add indexes for efficient filtering
ALTER TABLE users ADD INDEX idx_role (role), ADD INDEX idx_tier (tier);

-- Optional: analytics table for free play sessions (anonymous or logged-in)
CREATE TABLE IF NOT EXISTS freeplay_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    cefr_level VARCHAR(10),
    game_type VARCHAR(50),
    encounter_count INT UNSIGNED DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    CONSTRAINT fk_freeplay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
