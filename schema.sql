-- El Viaje del Jaguar — Database Schema
-- Run: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS jaguar_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE jaguar_app;

-- ─── Users ───────────────────────────────────────────────────────────
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    display_name    VARCHAR(100) NOT NULL,
    user_type       ENUM('individual','classroom') NOT NULL DEFAULT 'individual',
    cefr_level      VARCHAR(20) NOT NULL DEFAULT 'A1',
    interface_lang  VARCHAR(10) NOT NULL DEFAULT 'es',
    detected_lang   VARCHAR(10) DEFAULT NULL,
    gender          VARCHAR(30) DEFAULT NULL,
    pronouns        VARCHAR(30) DEFAULT NULL,
    accent_pref     VARCHAR(10) DEFAULT 'co',
    email_verified  TINYINT(1) NOT NULL DEFAULT 0,
    verify_token    VARCHAR(64) DEFAULT NULL,
    verify_expires  DATETIME DEFAULT NULL,
    reset_token     VARCHAR(64) DEFAULT NULL,
    reset_expires   DATETIME DEFAULT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at   DATETIME DEFAULT NULL,

    INDEX idx_verify_token (verify_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB;

-- ─── Sessions ────────────────────────────────────────────────────────
CREATE TABLE sessions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    ip_address  VARCHAR(45) DEFAULT NULL,
    user_agent  VARCHAR(512) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NOT NULL,
    is_revoked  TINYINT(1) NOT NULL DEFAULT 0,

    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── A1 Progress ────────────────────────────────────────────────────
CREATE TABLE a1_progress (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    module_id     VARCHAR(50) NOT NULL,
    lesson_id     VARCHAR(50) NOT NULL,
    current_step  INT UNSIGNED NOT NULL DEFAULT 0,
    total_steps   INT UNSIGNED NOT NULL DEFAULT 0,
    is_complete   TINYINT(1) NOT NULL DEFAULT 0,
    matched_pairs INT UNSIGNED NOT NULL DEFAULT 0,
    started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at  DATETIME DEFAULT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_module_lesson (user_id, module_id, lesson_id),
    CONSTRAINT fk_a1_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── Destination Progress (A2+) ─────────────────────────────────────
CREATE TABLE destination_progress (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NOT NULL,
    destination_id    VARCHAR(50) NOT NULL,
    storage_key       VARCHAR(100) NOT NULL,
    current_encounter INT UNSIGNED NOT NULL DEFAULT 0,
    completed_count   INT UNSIGNED NOT NULL DEFAULT 0,
    completed_map     JSON DEFAULT NULL,
    total_encounters  INT UNSIGNED NOT NULL DEFAULT 0,
    is_complete       TINYINT(1) NOT NULL DEFAULT 0,
    started_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at      DATETIME DEFAULT NULL,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_destination (user_id, destination_id),
    CONSTRAINT fk_dest_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── User Stats (dashboard aggregates) ──────────────────────────────
CREATE TABLE user_stats (
    user_id              BIGINT UNSIGNED PRIMARY KEY,
    lessons_completed    INT UNSIGNED NOT NULL DEFAULT 0,
    vocabulary_mastered  INT UNSIGNED NOT NULL DEFAULT 0,
    day_streak           INT UNSIGNED NOT NULL DEFAULT 0,
    perfect_scores       INT UNSIGNED NOT NULL DEFAULT 0,
    cultural_knowledge   INT UNSIGNED NOT NULL DEFAULT 0,
    trees_protected      INT UNSIGNED NOT NULL DEFAULT 0,
    species_saved        INT UNSIGNED NOT NULL DEFAULT 0,
    rivers_cleaned       INT UNSIGNED NOT NULL DEFAULT 0,
    total_time_days      INT UNSIGNED NOT NULL DEFAULT 0,
    last_activity_date   DATE DEFAULT NULL,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── User Settings ──────────────────────────────────────────────────
CREATE TABLE user_settings (
    user_id        BIGINT UNSIGNED PRIMARY KEY,
    gender         VARCHAR(30) DEFAULT NULL,
    pronouns       VARCHAR(30) DEFAULT NULL,
    sounds_volume  TINYINT UNSIGNED NOT NULL DEFAULT 80,
    music_volume   TINYINT UNSIGNED NOT NULL DEFAULT 50,
    accent         VARCHAR(10) NOT NULL DEFAULT 'co',
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── Escape Room Progress ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS escape_room_progress (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    destination_id  VARCHAR(50) NOT NULL,
    puzzles_solved  JSON DEFAULT NULL,
    is_complete     TINYINT(1) NOT NULL DEFAULT 0,
    fragment_item   VARCHAR(100) DEFAULT NULL,
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME DEFAULT NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_escape (user_id, destination_id),
    CONSTRAINT fk_escape_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════════════
-- DICTIONARY SYSTEM
-- ═══════════════════════════════════════════════════════════════════

-- ─── dict_languages: 104 supported languages ──────────────────────
CREATE TABLE dict_languages (
    code            VARCHAR(10) PRIMARY KEY,
    name_native     VARCHAR(100) NOT NULL,
    name_en         VARCHAR(100) NOT NULL,
    flag            VARCHAR(20) DEFAULT NULL,
    text_direction  ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_words: multilingual word entries ─────────────────────────
CREATE TABLE dict_words (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lang_code       VARCHAR(10) NOT NULL,
    word            VARCHAR(200) NOT NULL,
    word_normalized VARCHAR(200) NOT NULL,
    part_of_speech  VARCHAR(30) DEFAULT NULL,
    gender          VARCHAR(5) DEFAULT NULL,
    pronunciation_ipa VARCHAR(200) DEFAULT NULL,
    cefr_level      VARCHAR(20) DEFAULT NULL,
    frequency_rank  INT UNSIGNED DEFAULT NULL,
    source          ENUM('curated','game','wiktionary','user') NOT NULL DEFAULT 'curated',
    is_verified     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_lang_normalized (lang_code, word_normalized),
    INDEX idx_lang_cefr (lang_code, cefr_level),
    INDEX idx_lang_freq (lang_code, frequency_rank),
    UNIQUE KEY uq_lang_word_norm (lang_code, word_normalized)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_definitions: multilingual definitions ───────────────────
CREATE TABLE dict_definitions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word_id     BIGINT UNSIGNED NOT NULL,
    lang_code   VARCHAR(10) NOT NULL DEFAULT 'es',
    definition  TEXT NOT NULL,
    usage_note  TEXT DEFAULT NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,

    INDEX idx_word_lang (word_id, lang_code),
    CONSTRAINT fk_def_word FOREIGN KEY (word_id) REFERENCES dict_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_translations: bidirectional word links ──────────────────
CREATE TABLE dict_translations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_word_id  BIGINT UNSIGNED NOT NULL,
    target_word_id  BIGINT UNSIGNED NOT NULL,
    context         VARCHAR(200) DEFAULT NULL,

    INDEX idx_source (source_word_id),
    INDEX idx_target (target_word_id),
    UNIQUE KEY uq_source_target (source_word_id, target_word_id),
    CONSTRAINT fk_trans_source FOREIGN KEY (source_word_id) REFERENCES dict_words(id) ON DELETE CASCADE,
    CONSTRAINT fk_trans_target FOREIGN KEY (target_word_id) REFERENCES dict_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_examples: usage examples ────────────────────────────────
CREATE TABLE dict_examples (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word_id     BIGINT UNSIGNED NOT NULL,
    sentence    TEXT NOT NULL,
    translation TEXT DEFAULT NULL,
    cefr_level  VARCHAR(20) DEFAULT NULL,
    source      VARCHAR(200) DEFAULT NULL,

    INDEX idx_word (word_id),
    CONSTRAINT fk_ex_word FOREIGN KEY (word_id) REFERENCES dict_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_conjugations: Spanish verb conjugation tables ───────────
CREATE TABLE dict_conjugations (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word_id     BIGINT UNSIGNED NOT NULL,
    tense       VARCHAR(50) NOT NULL,
    mood        VARCHAR(30) NOT NULL DEFAULT 'indicativo',
    yo          VARCHAR(100) DEFAULT NULL,
    tu          VARCHAR(100) DEFAULT NULL,
    vos         VARCHAR(100) DEFAULT NULL,
    el          VARCHAR(100) DEFAULT NULL,
    nosotros    VARCHAR(100) DEFAULT NULL,
    ustedes     VARCHAR(100) DEFAULT NULL,
    ellos       VARCHAR(100) DEFAULT NULL,
    is_irregular TINYINT(1) NOT NULL DEFAULT 0,

    INDEX idx_word (word_id),
    CONSTRAINT fk_conj_word FOREIGN KEY (word_id) REFERENCES dict_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dict_related_words: synonyms, antonyms, derived forms ───────
CREATE TABLE dict_related_words (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    word_id         BIGINT UNSIGNED NOT NULL,
    related_word_id BIGINT UNSIGNED NOT NULL,
    relation_type   VARCHAR(30) NOT NULL DEFAULT 'synonym',

    INDEX idx_word (word_id),
    CONSTRAINT fk_rel_word FOREIGN KEY (word_id) REFERENCES dict_words(id) ON DELETE CASCADE,
    CONSTRAINT fk_rel_related FOREIGN KEY (related_word_id) REFERENCES dict_words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- GLOSSARY SYSTEM (game vocabulary)
-- ═══════════════════════════════════════════════════════════════════

-- ─── glossary_words: game-extracted vocabulary ─────────────────────
CREATE TABLE glossary_words (
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
CREATE TABLE glossary_examples (
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
CREATE TABLE glossary_translations (
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
CREATE TABLE user_vocabulary (
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
