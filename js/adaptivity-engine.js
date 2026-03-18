/**
 * AdaptivityEngine — Executes ontology-defined rules to personalize learning.
 *
 * Pure function evaluator: reads targetHistory, returns recommendations.
 * Does NOT modify game data directly — the engine decides whether to apply.
 *
 * 4 Rules:
 *   1. adapt_scaffold_input_mode   — Step down input mode on repeated failure
 *   2. adapt_rotate_game_type      — Bridge recognition→production gap (v1: log only)
 *   3. adapt_spiral_revisit        — Review stale prerequisites (v1: log only)
 *   4. adapt_self_correction       — Promote to self-correction on mastery
 *
 * Depends on: EvidenceEngine (evidence-engine.js), YaguaraOntology (ontology-api.js)
 */
(function (root) {
    'use strict';

    var INPUT_MODE_LADDER = ['choice', 'drag', 'typing', 'voice', 'self_correction'];
    var PRODUCTION_LADDER = [
        'narrative', 'listening', 'pair', 'category', 'fill',
        'builder', 'conjugation', 'translation', 'conversation', 'dictation'
    ];
    var RECOGNITION_TYPES = ['pair', 'listening', 'fill'];
    var PRODUCTION_TYPES = ['conversation', 'translation', 'builder'];

    var CEFR_RANK = { 'A1': 1, 'A2': 2, 'B1': 3, 'B2': 4, 'C1': 5, 'C2': 6 };

    var AdaptivityEngine = {
        _ready: false,
        _ontology: null,
        _evidence: null,
        _pendingActions: [],

        /**
         * Initialize with ontology and evidence engine.
         */
        init: function (ontology, evidenceEngine) {
            this._ontology = ontology;
            this._evidence = evidenceEngine;
            this._pendingActions = [];
            this._ready = true;
        },

        /**
         * Evaluate all rules for a target after an encounter.
         * @param {string} targetId - The linguistic target ID
         * @param {boolean} success - Whether the student succeeded
         * @returns {{ actions: Array }} or null
         */
        evaluate: function (targetId, success) {
            if (!this._ready || !this._evidence) return null;

            var history = this._evidence.getTargetHistory(targetId);
            if (!history) return null;

            var actions = [];

            /* Rule 1: Scaffold input mode */
            var scaffold = this._ruleScaffold(targetId, history, success);
            if (scaffold) actions.push(scaffold);

            /* Rule 4: Self-correction promotion */
            var promote = this._rulePromote(targetId, history, success);
            if (promote) actions.push(promote);

            /* Rule 2: Game type rotation (v1: log only) */
            var rotate = this._ruleRotate(targetId, history, success);
            if (rotate) actions.push(rotate);

            /* Rule 3: Spiral prerequisite revisit (v1: log only) */
            var revisit = this._ruleRevisit(targetId, history, success);
            if (revisit) actions.push(revisit);

            if (actions.length > 0) {
                /* Store for getRecommendation() — only keep latest per target */
                for (var i = 0; i < actions.length; i++) {
                    this._pendingActions.push(actions[i]);
                }
                /* Cap pending actions */
                if (this._pendingActions.length > 20) {
                    this._pendingActions = this._pendingActions.slice(-20);
                }
                return { actions: actions };
            }
            return null;
        },

        /**
         * Get the next pending recommendation (consumed once).
         * Returns the highest-priority actionable recommendation.
         */
        getRecommendation: function () {
            if (this._pendingActions.length === 0) return null;

            /* Prioritize: scaffold and promote (directly applicable) over rotate/revisit (v2) */
            for (var i = 0; i < this._pendingActions.length; i++) {
                var a = this._pendingActions[i];
                if (a.rule === 'scaffold' || a.rule === 'promote') {
                    this._pendingActions.splice(i, 1);
                    return { actions: [a] };
                }
            }

            /* Return first non-actionable (for logging) */
            var action = this._pendingActions.shift();
            return action ? { actions: [action] } : null;
        },

        /* ─── Rule Implementations ───────────────────────────────── */

        /**
         * Rule 1: adapt_scaffold_input_mode
         * Trigger: 2+ failures, input mode not already at lowest
         */
        _ruleScaffold: function (targetId, history, success) {
            if (success) return null;

            var failures = history.attempts - history.successes;
            if (failures < 2) return null;

            var currentMode = history.lastInputMode || 'choice';
            var currentIdx = INPUT_MODE_LADDER.indexOf(currentMode);
            if (currentIdx <= 0) return null; /* already at choice */

            var newMode = INPUT_MODE_LADDER[currentIdx - 1];
            console.log('[Adaptivity] Rule 1 fired: scaffold ' + targetId + ' from ' + currentMode + ' → ' + newMode);
            return {
                rule: 'scaffold',
                targetId: targetId,
                newInputMode: newMode
            };
        },

        /**
         * Rule 4: adapt_self_correction
         * Trigger: success rate >=0.85, voice successes >=2, attempts >=4
         */
        _rulePromote: function (targetId, history, success) {
            if (!success) return null;
            if (history.attempts < 4) return null;

            var rate = history.successes / history.attempts;
            if (rate < 0.85) return null;

            var voiceSuccesses = history.successByInputMode.voice || 0;
            if (voiceSuccesses < 2) return null;

            /* Already at self_correction? */
            if (history.lastInputMode === 'self_correction') return null;

            console.log('[Adaptivity] Rule 4 fired: promote ' + targetId + ' to self_correction');
            return {
                rule: 'promote',
                targetId: targetId,
                newInputMode: 'self_correction'
            };
        },

        /**
         * Rule 2: adapt_rotate_game_type (v1: recommendation only)
         * Trigger: recognition success but production failure
         */
        _ruleRotate: function (targetId, history, success) {
            if (success) return null;

            var recogOk = false;
            for (var i = 0; i < RECOGNITION_TYPES.length; i++) {
                if ((history.successByGameType[RECOGNITION_TYPES[i]] || 0) >= 1) {
                    recogOk = true;
                    break;
                }
            }
            if (!recogOk) return null;

            var prodAllFail = true;
            var prodAttempts = 0;
            for (var j = 0; j < PRODUCTION_TYPES.length; j++) {
                if ((history.successByGameType[PRODUCTION_TYPES[j]] || 0) > 0) {
                    prodAllFail = false;
                    break;
                }
                /* Count prod attempts from total - since we can't track per-type attempts,
                   use the failure heuristic: successes == 0 and target has been attempted */
            }
            if (!prodAllFail) return null;

            /* Need at least 2 production attempts overall (heuristic: failures > recognition successes) */
            var totalRecogSuccess = 0;
            for (var k = 0; k < RECOGNITION_TYPES.length; k++) {
                totalRecogSuccess += (history.successByGameType[RECOGNITION_TYPES[k]] || 0);
            }
            if (history.attempts - history.successes < 2) return null;

            /* Find the next step on production ladder above current highest success */
            var highestIdx = -1;
            for (var m = 0; m < PRODUCTION_LADDER.length; m++) {
                if ((history.successByGameType[PRODUCTION_LADDER[m]] || 0) > 0) {
                    highestIdx = m;
                }
            }
            var bridgeType = PRODUCTION_LADDER[Math.min(highestIdx + 1, PRODUCTION_LADDER.length - 1)];

            console.log('[Adaptivity] Rule 2 fired (v2): rotate ' + targetId + ' → bridge type: ' + bridgeType);
            return {
                rule: 'rotate',
                targetId: targetId,
                bridgeGameType: bridgeType,
                _v2: true
            };
        },

        /**
         * Rule 3: adapt_spiral_revisit (v1: recommendation only)
         * Trigger: failure + stale prerequisite (>10 encounters ago)
         */
        _ruleRevisit: function (targetId, history, success) {
            if (success) return null;
            if (!this._ontology) return null;

            var target = this._ontology.getTarget(targetId);
            if (!target || !target.cefr || CEFR_RANK[target.cefr] <= 1) return null;

            /* Find objectives for this target, get prerequisite targets at lower CEFR */
            var objectives = this._ontology.getObjectivesForTarget(targetId);
            var currentIndex = this._evidence.getEncounterIndex();
            var stalePrereq = null;

            for (var i = 0; i < objectives.length; i++) {
                var obj = objectives[i];
                var targets = obj.targets || [];
                for (var j = 0; j < targets.length; j++) {
                    if (targets[j] === targetId) continue;
                    var prereqTarget = this._ontology.getTarget(targets[j]);
                    if (!prereqTarget) continue;
                    if ((CEFR_RANK[prereqTarget.cefr] || 0) >= (CEFR_RANK[target.cefr] || 0)) continue;

                    var prereqHistory = this._evidence.getTargetHistory(targets[j]);
                    if (!prereqHistory || (currentIndex - prereqHistory.lastAttemptAt) > 10) {
                        stalePrereq = targets[j];
                        break;
                    }
                }
                if (stalePrereq) break;
            }

            if (!stalePrereq) return null;

            console.log('[Adaptivity] Rule 3 fired (v2): revisit stale prereq ' + stalePrereq + ' for ' + targetId);
            return {
                rule: 'revisit',
                targetId: targetId,
                stalePrereqId: stalePrereq,
                reviewGameType: 'fill',
                reviewInputMode: 'choice',
                _v2: true
            };
        }
    };

    root.AdaptivityEngine = AdaptivityEngine;

})(typeof window !== 'undefined' ? window : this);
