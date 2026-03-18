/**
 * EvidenceEngine — Bridges game completion to learner knowledge state.
 *
 * Pipeline: Game complete → find Activity → extract LTs → write EvidenceEvent
 *         → update targetHistory → check LearningObjective satisfaction
 *
 * localStorage key: yaguara_evidence
 * Depends on: YaguaraOntology (ontology-api.js)
 */
(function (root) {
    'use strict';

    var STORAGE_KEY = 'yaguara_evidence';
    var MAX_EVENTS = 500;

    /* Skill inference from game type */
    var SKILL_MAP = {
        listening: 'listening',
        dictation: 'listening',
        dictogloss: 'listening',
        ritmo: 'listening',
        pair: 'reading',
        category: 'reading',
        corrector: 'reading',
        par_minimo: 'reading',
        fill: 'writing',
        conjugation: 'writing',
        builder: 'writing',
        translation: 'writing',
        resumen: 'writing',
        registro: 'writing',
        transformador: 'writing',
        descripcion: 'writing',
        portafolio: 'writing',
        conversation: 'speaking',
        debate: 'speaking',
        negociacion: 'speaking',
        narrative: 'reading',
        cronica: 'writing',
        escaperoom: 'reading',
        brecha: 'reading',
        cronometro: 'reading',
        autoevaluacion: 'writing'
    };

    var EvidenceEngine = {
        _ready: false,
        _ontology: null,
        _data: null,
        _lastRecordedTargets: [],

        /**
         * Initialize with a loaded YaguaraOntology instance.
         */
        init: function (ontology) {
            this._ontology = ontology;
            this._data = this._load();
            this._ready = true;
        },

        /**
         * Record evidence from a completed game encounter.
         * @param {Object} gameData - The normalized game object
         * @param {boolean} success - Whether the student succeeded
         * @param {string} destinationId - e.g. "dest1"
         */
        recordEvidence: function (gameData, success, destinationId) {
            if (!this._ready || !this._ontology) return;

            var gameType = gameData.type || '';
            var targetIds = this._resolveTargets(gameData, destinationId);
            var skill = SKILL_MAP[gameType] || 'reading';
            var inputMode = gameData._inputMode || gameData.inputMode || this._inferInputMode(gameData);
            var outcome = success ? 'correct' : 'incorrect';

            this._lastRecordedTargets = targetIds;
            this._data.encounterIndex++;

            /* Write EvidenceEvent */
            var evt = {
                encounterIndex: this._data.encounterIndex,
                destination: destinationId,
                gameType: gameType,
                targets: targetIds,
                skill: skill,
                inputMode: inputMode,
                outcome: outcome,
                timestamp: Date.now()
            };

            this._data.events.push(evt);
            if (this._data.events.length > MAX_EVENTS) {
                this._data.events = this._data.events.slice(-MAX_EVENTS);
            }

            /* Update targetHistory for each LT */
            for (var i = 0; i < targetIds.length; i++) {
                this._updateTargetHistory(targetIds[i], success, gameType, inputMode);
            }

            /* Check objective satisfaction */
            this._checkObjectives(targetIds);

            this._save();
        },

        /**
         * Get history for a specific linguistic target.
         */
        getTargetHistory: function (targetId) {
            if (!this._data) return null;
            return this._data.targetHistory[targetId] || null;
        },

        /**
         * Get all target history.
         */
        getAllTargetHistory: function () {
            return this._data ? this._data.targetHistory : {};
        },

        /**
         * Get evidence status for a learning objective.
         */
        getEvidenceForObjective: function (objectiveId) {
            if (!this._data) return { satisfied: false, count: 0, required: 1 };
            var status = this._data.objectiveStatus[objectiveId];
            if (!status) return { satisfied: false, count: 0, required: 1 };

            var obj = this._ontology.getObjective(objectiveId);
            var required = (obj && obj.requires && obj.requires.minEvidence) || 1;
            return {
                satisfied: status.satisfied,
                count: status.evidenceCount,
                required: required
            };
        },

        /**
         * Get the current global encounter index.
         */
        getEncounterIndex: function () {
            return this._data ? this._data.encounterIndex : 0;
        },

        /**
         * Get the target IDs from the most recent recordEvidence call.
         */
        getLastRecordedTargets: function () {
            return this._lastRecordedTargets;
        },

        /**
         * Check spiral entry readiness for a destination range.
         * @param {string} destRange - e.g. "dest7-dest12"
         */
        checkSpiralEntry: function (destRange) {
            if (!this._data) return { ready: false, gates: {} };
            var parts = destRange.split('-');
            var startNum = parseInt((parts[0] || '').replace('dest', ''), 10) || 0;
            var endNum = parseInt((parts[1] || '').replace('dest', ''), 10) || 0;

            var gates = { mastery: true, scaffoldComplete: true, destinationCoverage: true };
            var destsCovered = 0;
            var totalDests = endNum - startNum + 1;

            for (var d = startNum; d <= endNum; d++) {
                var destId = 'dest' + d;
                var targets = this._ontology.getTargetsForDestination(destId);
                var destHasEvidence = false;

                for (var t = 0; t < targets.length; t++) {
                    var h = this._data.targetHistory[targets[t].id];
                    if (!h) {
                        gates.mastery = false;
                        continue;
                    }
                    destHasEvidence = true;
                    /* Mastery: >=3 evidence, >=1 production, >=0.75 rate */
                    if (h.attempts < 3 || h.successes < 1 || (h.successes / h.attempts) < 0.75) {
                        gates.mastery = false;
                    }
                    /* Scaffold complete: voice reached */
                    if (!h.successByInputMode.voice && !h.successByInputMode.self_correction) {
                        gates.scaffoldComplete = false;
                    }
                }
                if (destHasEvidence) destsCovered++;
            }

            if (totalDests > 0 && destsCovered < totalDests) {
                gates.destinationCoverage = false;
            }

            return {
                ready: gates.mastery && gates.scaffoldComplete && gates.destinationCoverage,
                gates: gates
            };
        },

        /* ─── Internal ───────────────────────────────────────────── */

        _resolveTargets: function (gameData, destId) {
            /* 1. Direct linguisticTargetId on game data */
            if (gameData.linguisticTargetId) {
                return [gameData.linguisticTargetId];
            }

            /* 2. Find matching Activity in ontology */
            if (!this._ontology || !this._ontology._loaded) return [];
            var activities = this._ontology.getActivitiesForDestination(destId);
            var gameType = gameData.type || '';

            for (var i = 0; i < activities.length; i++) {
                if (activities[i].gameType === gameType && activities[i].practices) {
                    return activities[i].practices.slice();
                }
            }

            return [];
        },

        _inferInputMode: function (gameData) {
            var type = gameData.type || '';
            if (['listening', 'dictogloss', 'ritmo'].indexOf(type) !== -1) return 'listen';
            if (['pair', 'category', 'debate', 'cronometro'].indexOf(type) !== -1) return 'choice';
            if (['builder'].indexOf(type) !== -1) return 'drag';
            if (['fill', 'conjugation', 'translation', 'corrector', 'resumen', 'registro', 'transformador', 'descripcion', 'portafolio', 'cronica'].indexOf(type) !== -1) return 'typing';
            if (['conversation'].indexOf(type) !== -1) return 'voice';
            return 'choice';
        },

        _updateTargetHistory: function (targetId, success, gameType, inputMode) {
            var h = this._data.targetHistory[targetId];
            if (!h) {
                h = {
                    attempts: 0,
                    successes: 0,
                    lastInputMode: '',
                    lastGameType: '',
                    lastAttemptAt: 0,
                    successByGameType: {},
                    successByInputMode: {},
                    selfCorrectionAttempts: 0,
                    selfCorrectionSuccesses: 0
                };
                this._data.targetHistory[targetId] = h;
            }

            h.attempts++;
            h.lastInputMode = inputMode;
            h.lastGameType = gameType;
            h.lastAttemptAt = this._data.encounterIndex;

            if (success) {
                h.successes++;
                h.successByGameType[gameType] = (h.successByGameType[gameType] || 0) + 1;
                h.successByInputMode[inputMode] = (h.successByInputMode[inputMode] || 0) + 1;
            }

            if (inputMode === 'self_correction') {
                h.selfCorrectionAttempts++;
                if (success) h.selfCorrectionSuccesses++;
            }
        },

        _checkObjectives: function (targetIds) {
            if (!this._ontology) return;

            /* For each target, find objectives that reference it */
            for (var i = 0; i < targetIds.length; i++) {
                var objectives = this._ontology.getObjectivesForTarget(targetIds[i]);
                for (var j = 0; j < objectives.length; j++) {
                    this._evaluateObjective(objectives[j]);
                }
            }
        },

        _evaluateObjective: function (obj) {
            if (!obj || !obj.id) return;
            var minEvidence = (obj.requires && obj.requires.minEvidence) || 1;
            var targetList = obj.targets || [];
            var totalEvidence = 0;
            var allMet = true;

            for (var i = 0; i < targetList.length; i++) {
                var h = this._data.targetHistory[targetList[i]];
                if (h) {
                    totalEvidence += h.successes;
                    if (h.successes < 1) allMet = false;
                } else {
                    allMet = false;
                }
            }

            var satisfied = allMet && totalEvidence >= minEvidence;
            this._data.objectiveStatus[obj.id] = {
                satisfied: satisfied,
                evidenceCount: totalEvidence
            };
        },

        _load: function () {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (parsed && parsed.targetHistory) return parsed;
                }
            } catch (e) { /* corrupted — start fresh */ }
            return {
                events: [],
                targetHistory: {},
                objectiveStatus: {},
                encounterIndex: 0
            };
        },

        _save: function () {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this._data));
            } catch (e) {
                /* Storage full — trim events aggressively */
                this._data.events = this._data.events.slice(-100);
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(this._data));
                } catch (e2) { /* give up silently */ }
            }
        }
    };

    root.EvidenceEngine = EvidenceEngine;

})(typeof window !== 'undefined' ? window : this);
