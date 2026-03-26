-- Migration: adventure_progress table for "Mi aventura por Colombia"
-- Run: mysql -u root -p babelfree < api/scripts/migrate-adventure.sql

CREATE TABLE IF NOT EXISTS adventure_progress (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               BIGINT UNSIGNED NOT NULL,
    chapters              JSON DEFAULT NULL COMMENT 'Per-destination data: cronica text, riddle reward, timestamp',
    earned_letters        JSON DEFAULT NULL COMMENT 'A1-A2: letters earned from riddle answers',
    earned_words          JSON DEFAULT NULL COMMENT 'B1-B2: words earned from riddle answers',
    earned_sentences      JSON DEFAULT NULL COMMENT 'C1-C2: sentences composed from student writing + riddles',
    composition_revealed  TINYINT(1) NOT NULL DEFAULT 0,
    total_words_written   INT UNSIGNED NOT NULL DEFAULT 0,
    started_at            DATETIME DEFAULT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user (user_id),
    CONSTRAINT fk_adventure_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
