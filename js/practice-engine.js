/* ================================================================
   PRACTICE ENGINE — Spaced Repetition & Review System
   El Viaje del Jaguar

   Multiplies study time by generating review sessions from completed
   destinations. Uses a simplified Leitner box system with vocabulary
   tracking from the main engine.

   Three modes:
   1. REVIEW — replay a completed destination with shuffled games
      and progressive difficulty (choice → typing → production)
   2. DAILY PRACTICE — mixed games from ALL completed destinations,
      prioritizing weak vocabulary and stale items
   3. VOCABULARY DRILL — focused practice on specific word sets
      extracted from completed destinations

   Storage: localStorage key "yaguara_srs" (JSON)
   ================================================================ */
(function() {
    'use strict';

    /* ==========================================================
       CONSTANTS
    ========================================================== */
    var STORAGE_KEY = 'yaguara_srs';
    var JOURNEY_KEY = 'yaguara_journey';
    var SESSION_LENGTH = 15;          /* games per daily practice session */
    var REVIEW_LENGTH = 20;           /* games per destination review */
    var DRILL_LENGTH = 10;            /* games per vocabulary drill */

    /* Leitner intervals (in sessions) */
    var BOX_INTERVALS = [1, 3, 7, 14, 30];

    /* Input mode progression: encounter → choice → drag → typing → voice */
    var MODE_PROGRESSION = ['choice', 'drag', 'typing', 'voice'];

    /* Game types suitable for review remix (Phase 6 elaboration types) */
    var REVIEWABLE_TYPES = {
        fill: true, pair: true, builder: true, listening: true,
        category: true, translation: true, spaceman: true,
        conjugation: true, dictation: true, crossword: true,
        boggle: true, kloo: true, madgab: true, madlibs: true,
        senda: true, brecha: true, clon: true, flashnote: true,
        registro: true, descripcion: true, negociacion: true,
        debate: true, oraculo: true, codice: true, cronometro: true,
        guardian: true, eco_lejano: true, conversation: true,
        tertulia: true, pregonero: true, consequences: true,
        dictogloss: true, corrector: true, bananagrams: true
    };

    /* Types that should NOT be repeated in review (one-time experiences) */
    var SKIP_IN_REVIEW = {
        narrative: true, despertar: true, skit: true, story: true,
        cronica: true, portafolio: true, autoevaluacion: true,
        escaperoom: true, resumen: true, cancion: true
    };

    /* ==========================================================
       SRS DATA MODEL
    ========================================================== */

    /**
     * SRS state shape:
     * {
     *   version: 1,
     *   sessionCount: 0,              // total practice sessions completed
     *   lastPracticeAt: null,          // ISO timestamp
     *   vocabulary: {                  // word → box data
     *     "hola": { box: 1, lastSeen: 0, encounters: 2, correct: 1 },
     *     ...
     *   },
     *   destinationReviews: {          // destN → review data
     *     "dest1": { reviewCount: 0, lastReviewAt: null, nextReviewSession: 1 },
     *     ...
     *   }
     * }
     */

    function _loadSRS() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                var data = JSON.parse(raw);
                if (data && data.version === 1) return data;
            }
        } catch(e) { /* silent */ }
        return {
            version: 1,
            sessionCount: 0,
            lastPracticeAt: null,
            vocabulary: {},
            destinationReviews: {}
        };
    }

    function _saveSRS(srs) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(srs));
        } catch(e) { /* silent */ }
    }

    function _getJourney() {
        try {
            return JSON.parse(localStorage.getItem(JOURNEY_KEY) || '{}');
        } catch(e) { return {}; }
    }

    function _getCompletedDests() {
        var journey = _getJourney();
        return journey.completedDestinations || [];
    }

    /* ==========================================================
       VOCABULARY TRACKING
    ========================================================== */

    /**
     * Ingest vocabulary from engine's growth data after a session.
     * Promotes words through Leitner boxes based on success.
     */
    function ingestVocabulary(growthData, srs) {
        if (!growthData || !growthData.vocabulary) return srs;
        var vocab = growthData.vocabulary;

        for (var i = 0; i < vocab.length; i++) {
            var entry = vocab[i];
            var words = entry.words || [];
            var success = entry.success;

            for (var w = 0; w < words.length; w++) {
                var word = (words[w] || '').toLowerCase().trim();
                if (!word || word.length < 2) continue;

                if (!srs.vocabulary[word]) {
                    srs.vocabulary[word] = {
                        box: 1,
                        lastSeen: srs.sessionCount,
                        encounters: 0,
                        correct: 0,
                        source: entry.gameType || 'unknown'
                    };
                }

                var item = srs.vocabulary[word];
                item.encounters++;
                item.lastSeen = srs.sessionCount;

                if (success) {
                    item.correct++;
                    /* Promote: move up one box (max 5) */
                    if (item.box < BOX_INTERVALS.length) {
                        item.box++;
                    }
                } else {
                    /* Demote: drop back to box 1 */
                    item.box = 1;
                }
            }
        }

        return srs;
    }

    /**
     * Get words that are due for review based on Leitner schedule.
     */
    function getDueWords(srs) {
        var due = [];
        var currentSession = srs.sessionCount;
        var words = srs.vocabulary;

        for (var word in words) {
            var item = words[word];
            var interval = BOX_INTERVALS[item.box - 1] || 1;
            var sessionsSince = currentSession - item.lastSeen;

            if (sessionsSince >= interval) {
                due.push({
                    word: word,
                    box: item.box,
                    staleness: sessionsSince / interval, /* >1 = overdue */
                    accuracy: item.encounters > 0 ? item.correct / item.encounters : 0,
                    source: item.source
                });
            }
        }

        /* Sort: most overdue first, then lowest accuracy */
        due.sort(function(a, b) {
            if (b.staleness !== a.staleness) return b.staleness - a.staleness;
            return a.accuracy - b.accuracy;
        });

        return due;
    }

    /* ==========================================================
       DESTINATION REVIEW SCHEDULING
    ========================================================== */

    function getDestsDueForReview(srs) {
        var completed = _getCompletedDests();
        var due = [];
        var currentSession = srs.sessionCount;

        for (var i = 0; i < completed.length; i++) {
            var destNum = completed[i];
            var key = 'dest' + destNum;
            var review = srs.destinationReviews[key];

            if (!review) {
                /* Never reviewed — due immediately after first completion */
                due.push({ destNum: destNum, priority: 100, reviewCount: 0 });
                continue;
            }

            if (currentSession >= review.nextReviewSession) {
                var staleness = currentSession - review.nextReviewSession;
                due.push({
                    destNum: destNum,
                    priority: staleness + 1,
                    reviewCount: review.reviewCount
                });
            }
        }

        /* Sort by priority (most overdue first) */
        due.sort(function(a, b) { return b.priority - a.priority; });
        return due;
    }

    function markDestReviewed(srs, destNum) {
        var key = 'dest' + destNum;
        if (!srs.destinationReviews[key]) {
            srs.destinationReviews[key] = { reviewCount: 0, lastReviewAt: null, nextReviewSession: 0 };
        }
        var review = srs.destinationReviews[key];
        review.reviewCount++;
        review.lastReviewAt = new Date().toISOString();
        /* Increasing intervals: 1, 2, 4, 8, 16... sessions between reviews */
        var interval = Math.min(16, Math.pow(2, review.reviewCount - 1));
        review.nextReviewSession = srs.sessionCount + interval;
        return srs;
    }

    /* ==========================================================
       GAME SELECTION & REMIX
    ========================================================== */

    function _shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
        }
        return arr;
    }

    /**
     * Filter games from a destination JSON for review.
     * Skips narrative/one-time games, returns reviewable games.
     */
    function extractReviewableGames(destData) {
        var games = [];

        if (destData.games) {
            for (var i = 0; i < destData.games.length; i++) {
                var g = destData.games[i];
                if (g._needsContent) continue;
                if (SKIP_IN_REVIEW[g.type]) continue;
                if (REVIEWABLE_TYPES[g.type] || !SKIP_IN_REVIEW[g.type]) {
                    games.push(g);
                }
            }
        }

        return games;
    }

    /**
     * Apply input mode escalation for review games.
     * First review: same mode. Second+: push toward typing/production.
     */
    function escalateMode(game, reviewCount) {
        if (reviewCount <= 0) return game;

        /* Only escalate types that support input modes */
        var escalatable = { fill: true, translation: true, conjugation: true,
                           builder: true, dictation: true, corrector: true };

        if (escalatable[game.type]) {
            var modeIdx = Math.min(reviewCount - 1, MODE_PROGRESSION.length - 1);
            game._inputMode = MODE_PROGRESSION[modeIdx];
            game._isReview = true;
        }

        return game;
    }

    /* ==========================================================
       SESSION GENERATORS
    ========================================================== */

    /**
     * MODE 1: Destination Review
     * Replay a specific completed destination with shuffled games
     * and escalated difficulty.
     */
    function generateDestinationReview(destData, destNum, srs) {
        var reviewable = extractReviewableGames(destData);
        if (reviewable.length === 0) return [];

        _shuffle(reviewable);

        /* Cap at REVIEW_LENGTH games */
        var selected = reviewable.slice(0, REVIEW_LENGTH);

        /* Get review count for difficulty escalation */
        var key = 'dest' + destNum;
        var reviewCount = srs.destinationReviews[key]
            ? srs.destinationReviews[key].reviewCount
            : 0;

        /* Deep-copy and escalate */
        var games = [];
        for (var i = 0; i < selected.length; i++) {
            var copy = JSON.parse(JSON.stringify(selected[i]));
            copy._reviewSource = 'dest' + destNum;
            escalateMode(copy, reviewCount);
            games.push(copy);
        }

        /* Sort by 369 phase order: encounter → elaboration → integration */
        var phaseMap = window.YaguaraEngine ? window.YaguaraEngine.CONFIG.phaseMap : {};
        games.sort(function(a, b) {
            var pa = (a.phase || phaseMap[a.type] || 6);
            var pb = (b.phase || phaseMap[b.type] || 6);
            return pa - pb;
        });

        return games;
    }

    /**
     * MODE 2: Daily Practice
     * Mixed games from ALL completed destinations, prioritizing
     * due vocabulary and stale destinations.
     */
    function generateDailyPractice(destDataMap, srs) {
        var dueWords = getDueWords(srs);
        var dueDests = getDestsDueForReview(srs);
        var allGames = [];

        /* Collect reviewable games from each completed destination */
        for (var destId in destDataMap) {
            var destData = destDataMap[destId];
            var reviewable = extractReviewableGames(destData);
            for (var i = 0; i < reviewable.length; i++) {
                var copy = JSON.parse(JSON.stringify(reviewable[i]));
                copy._reviewSource = destId;
                allGames.push(copy);
            }
        }

        if (allGames.length === 0) return [];

        /* Score each game by vocabulary overlap with due words */
        var dueWordSet = {};
        for (var d = 0; d < Math.min(dueWords.length, 50); d++) {
            dueWordSet[dueWords[d].word] = dueWords[d].staleness;
        }

        /* Due destination boost */
        var dueDestSet = {};
        for (var dd = 0; dd < dueDests.length; dd++) {
            dueDestSet['dest' + dueDests[dd].destNum] = dueDests[dd].priority;
        }

        for (var g = 0; g < allGames.length; g++) {
            var game = allGames[g];
            var score = 0;

            /* Vocabulary overlap score */
            var gameWords = _extractGameWords(game);
            for (var gw = 0; gw < gameWords.length; gw++) {
                var w = gameWords[gw].toLowerCase();
                if (dueWordSet[w]) {
                    score += dueWordSet[w] * 10;
                }
            }

            /* Destination staleness score */
            if (game._reviewSource && dueDestSet[game._reviewSource]) {
                score += dueDestSet[game._reviewSource] * 5;
            }

            /* Random jitter to prevent exact same sessions */
            score += Math.random() * 3;

            game._practiceScore = score;
        }

        /* Sort by score descending, take top SESSION_LENGTH */
        allGames.sort(function(a, b) { return b._practiceScore - a._practiceScore; });
        var selected = allGames.slice(0, SESSION_LENGTH);

        /* Re-sort by 369 phase order */
        var phaseMap = window.YaguaraEngine ? window.YaguaraEngine.CONFIG.phaseMap : {};
        selected.sort(function(a, b) {
            var pa = (a.phase || phaseMap[a.type] || 6);
            var pb = (b.phase || phaseMap[b.type] || 6);
            return pa - pb;
        });

        return selected;
    }

    /**
     * MODE 3: Vocabulary Drill
     * Generates games targeting specific weak words.
     * Creates fill-in-the-blank, translation, and matching exercises
     * from the SRS vocabulary data.
     */
    function generateVocabularyDrill(destDataMap, srs) {
        var dueWords = getDueWords(srs);
        if (dueWords.length === 0) return [];

        var targetWords = dueWords.slice(0, 20);
        var targetSet = {};
        for (var i = 0; i < targetWords.length; i++) {
            targetSet[targetWords[i].word] = true;
        }

        /* Find games containing target words */
        var matchingGames = [];
        for (var destId in destDataMap) {
            var destData = destDataMap[destId];
            var reviewable = extractReviewableGames(destData);
            for (var g = 0; g < reviewable.length; g++) {
                var gameWords = _extractGameWords(reviewable[g]);
                var overlap = 0;
                for (var w = 0; w < gameWords.length; w++) {
                    if (targetSet[gameWords[w].toLowerCase()]) overlap++;
                }
                if (overlap > 0) {
                    var copy = JSON.parse(JSON.stringify(reviewable[g]));
                    copy._reviewSource = destId;
                    copy._vocabOverlap = overlap;
                    copy._inputMode = 'typing'; /* drills push to production */
                    copy._isReview = true;
                    matchingGames.push(copy);
                }
            }
        }

        /* Sort by overlap, take top DRILL_LENGTH */
        matchingGames.sort(function(a, b) { return b._vocabOverlap - a._vocabOverlap; });
        return matchingGames.slice(0, DRILL_LENGTH);
    }

    /**
     * Extract words from a game for vocabulary matching.
     */
    function _extractGameWords(game) {
        var words = [];
        if (game.vocabulary) words = words.concat(game.vocabulary);
        if (game.words) words = words.concat(game.words);
        if (game.answer) words.push(game.answer);
        if (game.sentence) {
            var sentenceWords = game.sentence.replace(/[^a-záéíóúüñ\s]/gi, '').split(/\s+/);
            words = words.concat(sentenceWords);
        }
        if (game.pairs) {
            for (var i = 0; i < game.pairs.length; i++) {
                if (Array.isArray(game.pairs[i])) {
                    words = words.concat(game.pairs[i]);
                }
            }
        }
        if (game.options) {
            for (var o = 0; o < game.options.length; o++) {
                if (typeof game.options[o] === 'string') words.push(game.options[o]);
                else if (game.options[o] && game.options[o].text) words.push(game.options[o].text);
            }
        }
        return words.filter(function(w) { return typeof w === 'string' && w.length > 1; });
    }

    /* ==========================================================
       SESSION COMPLETION HANDLER
    ========================================================== */

    /**
     * Call after a practice/review session completes.
     * Updates SRS state with results.
     */
    function completeSession(mode, destNum) {
        var srs = _loadSRS();
        srs.sessionCount++;
        srs.lastPracticeAt = new Date().toISOString();

        /* Ingest vocabulary from the engine if available */
        if (window.YaguaraEngine && YaguaraEngine.getGrowthData) {
            var growth = YaguaraEngine.getGrowthData();
            ingestVocabulary(growth, srs);
        }

        /* Mark destination as reviewed */
        if (mode === 'review' && destNum) {
            markDestReviewed(srs, destNum);
        }

        /* For daily practice, mark all source destinations */
        if (mode === 'daily') {
            var completed = _getCompletedDests();
            for (var i = 0; i < completed.length; i++) {
                var key = 'dest' + completed[i];
                if (!srs.destinationReviews[key]) {
                    srs.destinationReviews[key] = {
                        reviewCount: 0,
                        lastReviewAt: null,
                        nextReviewSession: srs.sessionCount + 1
                    };
                }
            }
        }

        _saveSRS(srs);
        return srs;
    }

    /* ==========================================================
       STATS & DISPLAY
    ========================================================== */

    function getStats() {
        var srs = _loadSRS();
        var completed = _getCompletedDests();
        var dueWords = getDueWords(srs);
        var dueDests = getDestsDueForReview(srs);

        var totalWords = Object.keys(srs.vocabulary).length;
        var weakWords = 0;
        var strongWords = 0;
        for (var w in srs.vocabulary) {
            if (srs.vocabulary[w].box <= 2) weakWords++;
            if (srs.vocabulary[w].box >= 4) strongWords++;
        }

        return {
            sessionCount: srs.sessionCount,
            lastPracticeAt: srs.lastPracticeAt,
            completedDestinations: completed.length,
            totalWords: totalWords,
            weakWords: weakWords,
            strongWords: strongWords,
            dueWordCount: dueWords.length,
            dueDestCount: dueDests.length,
            dueDests: dueDests.slice(0, 5),
            topDueWords: dueWords.slice(0, 10).map(function(d) { return d.word; }),
            /* Estimated practice time remaining (in minutes) */
            estimatedMinutes: Math.max(0,
                (dueWords.length * 1.5) +  /* ~1.5 min per due word drill */
                (dueDests.length * 25)      /* ~25 min per destination review */
            )
        };
    }

    /**
     * Check if a practice session is recommended right now.
     * Returns null or a recommendation object.
     */
    function getRecommendation() {
        var srs = _loadSRS();
        var completed = _getCompletedDests();

        if (completed.length === 0) return null;

        var dueWords = getDueWords(srs);
        var dueDests = getDestsDueForReview(srs);

        /* Priority 1: Never-reviewed destinations */
        for (var i = 0; i < dueDests.length; i++) {
            if (dueDests[i].reviewCount === 0) {
                return {
                    type: 'first_review',
                    destNum: dueDests[i].destNum,
                    message: 'Repasa el destino ' + dueDests[i].destNum + ' para fortalecer lo aprendido.'
                };
            }
        }

        /* Priority 2: Overdue vocabulary (>20 words due) */
        if (dueWords.length > 20) {
            return {
                type: 'vocabulary_drill',
                dueCount: dueWords.length,
                message: dueWords.length + ' palabras esperan tu repaso.'
            };
        }

        /* Priority 3: Stale destinations */
        if (dueDests.length > 0) {
            return {
                type: 'destination_review',
                destNum: dueDests[0].destNum,
                message: 'Es buen momento para revisitar el destino ' + dueDests[0].destNum + '.'
            };
        }

        /* Priority 4: Daily practice (general mix) */
        if (dueWords.length > 0) {
            return {
                type: 'daily_practice',
                dueCount: dueWords.length,
                message: 'Tu práctica diaria tiene ' + dueWords.length + ' palabras para repasar.'
            };
        }

        return null;
    }

    /* ==========================================================
       PRACTICE UI LAUNCHER
    ========================================================== */

    /**
     * Render the practice picker UI in a given container.
     * Shows available practice modes and stats.
     */
    function renderPicker(container) {
        var stats = getStats();
        var rec = getRecommendation();

        var html = '<div class="yg-practice-picker">';

        /* Header */
        html += '<div class="yg-practice-header">';
        html += '<h2 class="yg-practice-title">Práctica</h2>';
        if (stats.sessionCount > 0) {
            html += '<div class="yg-practice-streak">' +
                stats.sessionCount + ' sesiones · ' +
                stats.totalWords + ' palabras</div>';
        }
        html += '</div>';

        /* Recommendation banner */
        if (rec) {
            html += '<div class="yg-practice-rec">';
            html += '<div class="yg-practice-rec-text">' + rec.message + '</div>';
            html += '<button class="yg-practice-rec-btn" data-mode="' + rec.type + '"' +
                (rec.destNum ? ' data-dest="' + rec.destNum + '"' : '') + '>Comenzar</button>';
            html += '</div>';
        }

        /* Mode cards */
        html += '<div class="yg-practice-modes">';

        /* Daily Practice */
        if (stats.completedDestinations > 0) {
            html += '<div class="yg-practice-card" data-mode="daily">';
            html += '<div class="yg-practice-card-icon">∿</div>';
            html += '<div class="yg-practice-card-title">Práctica diaria</div>';
            html += '<div class="yg-practice-card-desc">' +
                SESSION_LENGTH + ' juegos de ' + stats.completedDestinations + ' destinos</div>';
            if (stats.dueWordCount > 0) {
                html += '<div class="yg-practice-card-due">' + stats.dueWordCount + ' palabras pendientes</div>';
            }
            html += '</div>';
        }

        /* Vocabulary Drill */
        if (stats.dueWordCount > 5) {
            html += '<div class="yg-practice-card" data-mode="drill">';
            html += '<div class="yg-practice-card-icon">◈</div>';
            html += '<div class="yg-practice-card-title">Vocabulario</div>';
            html += '<div class="yg-practice-card-desc">' +
                Math.min(stats.dueWordCount, 20) + ' palabras para repasar</div>';
            if (stats.weakWords > 0) {
                html += '<div class="yg-practice-card-due">' + stats.weakWords + ' palabras débiles</div>';
            }
            html += '</div>';
        }

        /* Destination Reviews */
        if (stats.dueDests.length > 0) {
            for (var i = 0; i < Math.min(stats.dueDests.length, 3); i++) {
                var d = stats.dueDests[i];
                html += '<div class="yg-practice-card" data-mode="review" data-dest="' + d.destNum + '">';
                html += '<div class="yg-practice-card-icon">↻</div>';
                html += '<div class="yg-practice-card-title">Destino ' + d.destNum + '</div>';
                html += '<div class="yg-practice-card-desc">' +
                    (d.reviewCount === 0 ? 'Primer repaso' : 'Repaso #' + (d.reviewCount + 1)) + '</div>';
                html += '</div>';
            }
        }

        html += '</div>'; /* .yg-practice-modes */

        /* Vocabulary overview */
        if (stats.totalWords > 0) {
            html += '<div class="yg-practice-vocab-summary">';
            html += '<div class="yg-practice-vocab-bar">';
            var strongPct = Math.round((stats.strongWords / stats.totalWords) * 100);
            var weakPct = Math.round((stats.weakWords / stats.totalWords) * 100);
            var midPct = 100 - strongPct - weakPct;
            html += '<div class="yg-practice-bar-strong" style="width:' + strongPct + '%"></div>';
            html += '<div class="yg-practice-bar-mid" style="width:' + midPct + '%"></div>';
            html += '<div class="yg-practice-bar-weak" style="width:' + weakPct + '%"></div>';
            html += '</div>';
            html += '<div class="yg-practice-vocab-legend">' +
                '<span class="yg-pv-strong">' + stats.strongWords + ' firmes</span>' +
                '<span class="yg-pv-mid">' + (stats.totalWords - stats.strongWords - stats.weakWords) + ' en proceso</span>' +
                '<span class="yg-pv-weak">' + stats.weakWords + ' débiles</span>' +
                '</div>';
            html += '</div>';
        }

        /* Empty state */
        if (stats.completedDestinations === 0) {
            html += '<div class="yg-practice-empty">';
            html += '<p>Completa tu primer destino para desbloquear la práctica.</p>';
            html += '<a href="/play?dest=1" class="yg-practice-start-link">Comenzar destino 1 →</a>';
            html += '</div>';
        }

        html += '</div>'; /* .yg-practice-picker */

        container.innerHTML = html;

        /* Bind click events */
        var cards = container.querySelectorAll('[data-mode]');
        for (var c = 0; c < cards.length; c++) {
            cards[c].addEventListener('click', function() {
                var mode = this.getAttribute('data-mode');
                var dest = this.getAttribute('data-dest');
                _launchPractice(mode, dest ? parseInt(dest, 10) : null);
            });
        }
    }

    /* ==========================================================
       PRACTICE LAUNCHER
    ========================================================== */

    function _launchPractice(mode, destNum) {
        var srs = _loadSRS();

        if (mode === 'review' || mode === 'first_review' || mode === 'destination_review') {
            if (!destNum) {
                var dueDests = getDestsDueForReview(srs);
                if (dueDests.length > 0) destNum = dueDests[0].destNum;
                else return;
            }
            _loadAndLaunchReview(destNum, srs);
        } else if (mode === 'daily' || mode === 'daily_practice') {
            _loadAndLaunchDaily(srs);
        } else if (mode === 'drill' || mode === 'vocabulary_drill') {
            _loadAndLaunchDrill(srs);
        }
    }

    function _loadAndLaunchReview(destNum, srs) {
        fetch('content/dest' + destNum + '.json')
            .then(function(res) { return res.ok ? res.json() : null; })
            .then(function(data) {
                if (!data) return;
                var games = generateDestinationReview(data, destNum, srs);
                if (games.length === 0) return;

                _startEngine(games, data.meta || {}, 'review', destNum);
            })
            .catch(function(err) {
                console.error('PracticeEngine: failed to load dest' + destNum, err);
            });
    }

    function _loadAndLaunchDaily(srs) {
        var completed = _getCompletedDests();
        if (completed.length === 0) return;

        /* Load all completed destination JSONs in parallel */
        var promises = [];
        for (var i = 0; i < completed.length; i++) {
            promises.push(
                fetch('content/dest' + completed[i] + '.json')
                    .then(function(res) { return res.ok ? res.json() : null; })
                    .catch(function() { return null; })
            );
        }

        Promise.all(promises).then(function(results) {
            var destDataMap = {};
            for (var i = 0; i < results.length; i++) {
                if (results[i]) {
                    destDataMap['dest' + completed[i]] = results[i];
                }
            }

            var games = generateDailyPractice(destDataMap, srs);
            if (games.length === 0) return;

            /* Use meta from first destination for chrome */
            var firstMeta = results[0] ? (results[0].meta || {}) : {};
            firstMeta.title = 'Práctica diaria';
            _startEngine(games, firstMeta, 'daily', null);
        });
    }

    function _loadAndLaunchDrill(srs) {
        var completed = _getCompletedDests();
        if (completed.length === 0) return;

        var promises = [];
        for (var i = 0; i < completed.length; i++) {
            promises.push(
                fetch('content/dest' + completed[i] + '.json')
                    .then(function(res) { return res.ok ? res.json() : null; })
                    .catch(function() { return null; })
            );
        }

        Promise.all(promises).then(function(results) {
            var destDataMap = {};
            for (var i = 0; i < results.length; i++) {
                if (results[i]) {
                    destDataMap['dest' + completed[i]] = results[i];
                }
            }

            var games = generateVocabularyDrill(destDataMap, srs);
            if (games.length === 0) return;

            var firstMeta = results[0] ? (results[0].meta || {}) : {};
            firstMeta.title = 'Vocabulario';
            _startEngine(games, firstMeta, 'drill', null);
        });
    }

    /**
     * Start the engine with practice games.
     */
    function _startEngine(games, meta, mode, destNum) {
        var cefr = meta.cefr || 'A1';
        var world = meta.world || 'mundoDeAbajo';
        var CEFR_SPEECH = { 'A1': 0.5, 'A2': 0.6, 'B1': 0.7, 'B2': 0.8, 'C1': 0.9, 'C2': 1.0 };

        /* Store mode info for completion handler */
        _activePracticeMode = mode;
        _activePracticeDest = destNum;

        YaguaraEngine.init({
            games: games,
            container: document.getElementById('yaguaraCard'),
            progressContainer: document.getElementById('yaguaraProgress'),
            yaguaraPanel: document.getElementById('yaguaraPanel'),
            destinationId: destNum ? 'dest' + destNum : 'practice_' + mode,
            cefr: cefr,
            world: world,
            speechRate: CEFR_SPEECH[cefr] || 0.5,
            storageKey: 'yaguara_practice_' + mode + '_progress',
            arcadeMode: true, /* Practice sessions use arcade completion flow */
            backUrl: window.location.href
        });

        /* Update chrome */
        var titleEl = document.querySelector('.yg-header-title');
        var subtitleEl = document.querySelector('.yg-header-subtitle');
        if (titleEl) titleEl.textContent = meta.title || 'Práctica';
        if (subtitleEl) {
            var modeLabel = { review: 'Repaso', daily: 'Práctica diaria', drill: 'Vocabulario' };
            subtitleEl.textContent = (modeLabel[mode] || 'Práctica') + ' — ' + cefr;
        }
    }

    var _activePracticeMode = null;
    var _activePracticeDest = null;

    /* ==========================================================
       AUTO-INGEST HOOK
       Call this from destination-router after engine completion
       to automatically feed vocabulary into the SRS.
    ========================================================== */

    function autoIngest() {
        if (!window.YaguaraEngine || !YaguaraEngine.getGrowthData) return;
        var growth = YaguaraEngine.getGrowthData();
        if (!growth || !growth.vocabulary || growth.vocabulary.length === 0) return;

        var srs = _loadSRS();
        ingestVocabulary(growth, srs);
        _saveSRS(srs);
    }

    /* ==========================================================
       PUBLIC API
    ========================================================== */
    window.PracticeEngine = {
        /* Core */
        getStats: getStats,
        getRecommendation: getRecommendation,
        getDueWords: function() { return getDueWords(_loadSRS()); },
        getDueDestinations: function() { return getDestsDueForReview(_loadSRS()); },

        /* Session generators (for external use) */
        generateDestinationReview: function(destData, destNum) {
            return generateDestinationReview(destData, destNum, _loadSRS());
        },
        generateDailyPractice: function(destDataMap) {
            return generateDailyPractice(destDataMap, _loadSRS());
        },
        generateVocabularyDrill: function(destDataMap) {
            return generateVocabularyDrill(destDataMap, _loadSRS());
        },

        /* UI */
        renderPicker: renderPicker,
        launch: _launchPractice,

        /* Lifecycle */
        completeSession: function(mode, destNum) {
            return completeSession(
                mode || _activePracticeMode,
                destNum || _activePracticeDest
            );
        },
        autoIngest: autoIngest,

        /* Direct SRS access */
        _loadSRS: _loadSRS,
        _saveSRS: _saveSRS
    };

})();
