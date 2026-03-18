#!/bin/bash
# Dictionary Expansion Pipeline — 5,282 → 10,000 Spanish words + quality fill
#
# Runs all phases in order with logging and error checking.
# Long-running phases (2, 5, 6) can be interrupted and resumed safely.
#
# Usage:
#   bash run-dictionary-expansion.sh              # Full pipeline
#   bash run-dictionary-expansion.sh --phase=N    # Run single phase (0-9)
#   bash run-dictionary-expansion.sh --from=N     # Start from phase N
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

LOG_DIR="data"
LOG="$LOG_DIR/expansion_$(date +%Y%m%d_%H%M%S).log"
mkdir -p "$LOG_DIR"

# Parse args
SINGLE_PHASE=""
START_FROM=0
for arg in "$@"; do
    case "$arg" in
        --phase=*) SINGLE_PHASE="${arg#--phase=}" ;;
        --from=*)  START_FROM="${arg#--from=}" ;;
    esac
done

log() {
    echo "[$(date '+%H:%M:%S')] $1" | tee -a "$LOG"
}

run_phase() {
    local phase=$1
    local desc=$2
    shift 2
    log "═══ Phase $phase: $desc ═══"
    "$@" 2>&1 | tee -a "$LOG"
    local status=${PIPESTATUS[0]}
    if [ $status -ne 0 ]; then
        log "ERROR: Phase $phase failed with exit code $status"
        log "Check log: $LOG"
        exit $status
    fi
    log "Phase $phase complete."
    echo "" | tee -a "$LOG"
}

should_run() {
    local phase=$1
    if [ -n "$SINGLE_PHASE" ]; then
        [ "$SINGLE_PHASE" = "$phase" ]
    else
        [ "$phase" -ge "$START_FROM" ]
    fi
}

log "Dictionary Expansion Pipeline started"
log "Log file: $LOG"
echo "" | tee -a "$LOG"

# ── Phase 0: Schema migration ──────────────────────────────────────

if should_run 0; then
    run_phase 0 "Schema migration" \
        mysql -u root jaguar_app -e "
            -- Add source_id to dict_examples if not exists
            SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA='jaguar_app' AND TABLE_NAME='dict_examples' AND COLUMN_NAME='source_id');
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE dict_examples ADD COLUMN source_id VARCHAR(100) NULL AFTER source, ADD INDEX idx_source_id (source_id)',
                'SELECT \"source_id already exists on dict_examples\"');
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

            -- Add unique constraint to dict_related_words if not exists
            SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA='jaguar_app' AND TABLE_NAME='dict_related_words' AND INDEX_NAME='uq_relation');
            SET @sql = IF(@idx_exists = 0,
                'ALTER TABLE dict_related_words ADD UNIQUE KEY uq_relation (word_id, related_word_id, relation_type)',
                'SELECT \"uq_relation already exists on dict_related_words\"');
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

            -- Add source_id to dict_definitions if not exists
            SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA='jaguar_app' AND TABLE_NAME='dict_definitions' AND COLUMN_NAME='source_id');
            SET @sql = IF(@col_exists = 0,
                'ALTER TABLE dict_definitions ADD COLUMN source_id VARCHAR(100) NULL AFTER usage_note',
                'SELECT \"source_id already exists on dict_definitions\"');
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        "
fi

# ── Phase 1: Expand to 10K words ───────────────────────────────────

if should_run 1; then
    run_phase 1 "Clean frequency list" \
        php clean-frequency-list.php --dry-run

    run_phase 1 "Expand to 10K words" \
        php seed-dictionary-v2.php --limit=10000 --skip-wiktionary
fi

# ── Phase 2: Wiktionary enrichment ─────────────────────────────────

if should_run 2; then
    log "Phase 2 is long-running (~100 min). Use Ctrl+C to interrupt; safe to resume."
    run_phase 2 "Wiktionary enrichment" \
        php enrich-wiktionary.php --all
fi

# ── Phase 3: Expand conjugations ───────────────────────────────────

if should_run 3; then
    run_phase 3 "Generate conjugations" \
        php generate-conjugations.php

    run_phase 3 "Post-process conjugations" \
        python3 postprocess_conjugations.py

    run_phase 3 "Seed conjugations to DB" \
        php seed-conjugations.php
fi

# ── Phase 4: English entries from Wiktionary ───────────────────────

if should_run 4; then
    run_phase 4 "Build English entries" \
        php seed-english-from-wiktionary.php
fi

# ── Phase 5: Tatoeba examples ──────────────────────────────────────

if should_run 5; then
    log "Phase 5 downloads ~200MB of Tatoeba data on first run."
    run_phase 5 "Tatoeba example sentences" \
        php seed-tatoeba.php --limit=10000 --max-per-word=3
fi

# ── Phase 6: Related words ─────────────────────────────────────────

if should_run 6; then
    log "Phase 6 is long-running (~85 min). Use Ctrl+C to interrupt; safe to resume."
    run_phase 6 "Related words (synonyms/antonyms)" \
        php seed-related-words.php --all
fi

# ── Phase 7: Sitemap ──────────────────────────────────────────────

if should_run 7; then
    run_phase 7 "Generate dictionary sitemap" \
        php generate-sitemap-dictionary.php
    log "Sitemap output sent to stdout — redirect to file and splice into sitemap.xml"
fi

# ── Phase 8: Verification ─────────────────────────────────────────

if should_run 8; then
    run_phase 8 "Verification report" \
        php verify-dictionary-expansion.php
fi

log "═══ Pipeline Complete ═══"
log "Full log: $LOG"
