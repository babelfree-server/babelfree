-- Migration 002: Glossary system tables
-- Game-extracted vocabulary, translations, examples, and user mastery tracking.
-- Run: mysql jaguar_app < 002_glossary_tables.sql

USE jaguar_app;

-- ─── glossary_words: game-extracted vocabulary ─────────────────────
CREATE TABLE IF NOT EXISTS glossary_words (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word            VARCHAR(200) NOT NULL,
    word_normalized VARCHAR(200) NOT NULL,
    cefr_level      VARCHAR(20) NOT NULL DEFAULT 'A1',
    ecosystem       VARCHAR(50) NOT NULL,
    destination_id  VARCHAR(50) NOT NULL,
    game_type       VARCHAR(50) DEFAULT NULL,
    emoji           VARCHAR(10) DEFAULT NULL,
    source_file     VARCHAR(200) DEFAULT NULL,
    dict_word_id    BIGINT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_cefr (cefr_level),
    INDEX idx_ecosystem (ecosystem),
    INDEX idx_dest (destination_id),
    INDEX idx_normalized (word_normalized),
    INDEX idx_dict_word (dict_word_id),
    UNIQUE KEY uq_word_dest (word_normalized, destination_id),
    CONSTRAINT fk_glossary_dict FOREIGN KEY (dict_word_id) REFERENCES dict_words(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── glossary_examples: example sentences from game content ────────
CREATE TABLE IF NOT EXISTS glossary_examples (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    glossary_word_id BIGINT UNSIGNED NOT NULL,
    sentence_es     TEXT NOT NULL,
    sentence_gloss  TEXT DEFAULT NULL,
    cefr_level      VARCHAR(20) NOT NULL DEFAULT 'A1',
    game_type       VARCHAR(50) DEFAULT NULL,
    source_file     VARCHAR(200) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_word (glossary_word_id),
    CONSTRAINT fk_glossary_ex_word FOREIGN KEY (glossary_word_id) REFERENCES glossary_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── glossary_translations: pre-computed translations ──────────────
CREATE TABLE IF NOT EXISTS glossary_translations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    glossary_word_id BIGINT UNSIGNED NOT NULL,
    lang_code       VARCHAR(10) NOT NULL,
    translation     VARCHAR(200) NOT NULL,
    is_verified     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_word_lang (glossary_word_id, lang_code),
    UNIQUE KEY uq_word_lang (glossary_word_id, lang_code),
    CONSTRAINT fk_glossary_trans_word FOREIGN KEY (glossary_word_id) REFERENCES glossary_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── user_vocabulary: per-student mastery tracking ─────────────────
CREATE TABLE IF NOT EXISTS user_vocabulary (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    glossary_word_id BIGINT UNSIGNED NOT NULL,
    times_seen      INT UNSIGNED NOT NULL DEFAULT 0,
    times_correct   INT UNSIGNED NOT NULL DEFAULT 0,
    mastery_level   TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=new, 1=seen, 2=practiced, 3=mastered',
    first_seen_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_word (user_id, glossary_word_id),
    INDEX idx_user_mastery (user_id, mastery_level),
    CONSTRAINT fk_uv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_uv_glossary FOREIGN KEY (glossary_word_id) REFERENCES glossary_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
