-- Busqueda (riddle quest) progress table
-- Stores per-user riddle quest state for cross-device sync

CREATE TABLE IF NOT EXISTS busqueda_progress (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    solved_riddles  JSON         NOT NULL DEFAULT ('[]'),
    bridge_segments TINYINT UNSIGNED NOT NULL DEFAULT 0,
    rana_opacity    DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rana_name       VARCHAR(30)  DEFAULT NULL,
    journal_entries JSON         NOT NULL DEFAULT ('[]'),
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (user_id),
    CONSTRAINT fk_busqueda_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
