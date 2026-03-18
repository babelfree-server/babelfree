-- Migration 004: Dictionary Expansion support
-- Adds source_id columns for deduplication and a unique constraint on related words.
--
-- Run: mysql -u root jaguar_app < migrations/004_dict_expansion.sql

-- 1. source_id on dict_examples — allows Tatoeba sentence IDs for deduplication
ALTER TABLE dict_examples
    ADD COLUMN source_id VARCHAR(100) NULL AFTER source;

ALTER TABLE dict_examples
    ADD INDEX idx_source_id (source_id);

-- 2. Unique constraint on dict_related_words — prevent duplicate synonym/antonym pairs
ALTER TABLE dict_related_words
    ADD UNIQUE KEY uq_relation (word_id, related_word_id, relation_type);

-- 3. source_id on dict_definitions — allows deduplication on re-runs (e.g. 'wiktionary_es', 'wiktionary_en')
ALTER TABLE dict_definitions
    ADD COLUMN source_id VARCHAR(100) NULL AFTER usage_note;
