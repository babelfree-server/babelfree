-- Migration 001: Dictionary schema enhancements
-- Adds source tracking, verification, multilingual definitions,
-- vos conjugation form, and expanded language support.
-- Run: mysql jaguar_app < 001_dict_schema_update.sql

USE jaguar_app;

-- ─── dict_words: add source tracking, verification flag ────────────
ALTER TABLE dict_words
    ADD COLUMN source ENUM('curated','game','wiktionary','user') NOT NULL DEFAULT 'curated' AFTER frequency_rank,
    ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER source,
    ADD INDEX idx_lang_cefr (lang_code, cefr_level),
    ADD INDEX idx_lang_freq (lang_code, frequency_rank);

-- ─── dict_definitions: add lang_code for multilingual definitions ──
ALTER TABLE dict_definitions
    ADD COLUMN lang_code VARCHAR(10) NOT NULL DEFAULT 'es' AFTER word_id,
    ADD INDEX idx_word_lang (word_id, lang_code);

-- ─── dict_conjugations: add vos form + irregularity flag ───────────
ALTER TABLE dict_conjugations
    ADD COLUMN vos VARCHAR(100) DEFAULT NULL AFTER tu,
    ADD COLUMN is_irregular TINYINT(1) NOT NULL DEFAULT 0;

-- ─── dict_languages: expand to 104, add flag + direction ───────────
-- Clear old 2-language data and add new columns
TRUNCATE TABLE dict_languages;

ALTER TABLE dict_languages
    ADD COLUMN flag VARCHAR(20) DEFAULT NULL,
    ADD COLUMN text_direction ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
