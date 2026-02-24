/* ================================================================
   YAGUARÁ GAME ENGINE v1.0
   Silence-based feedback · 5-beat encounter rhythm · seed progress
   No scores, no streaks, no "correct/incorrect" text
   ================================================================ */
(function() {
    'use strict';

    /* ==========================================================
       1. CONFIG
    ========================================================== */
    var CONFIG = {
        silenceDuration: 500,
        reflectionPause: 1200,
        immersionDuration: 800,
        intentDuration: 600,
        harmonyDuration: 1200,
        dimDuration: 500,
        restoreDuration: 1000,
        transitionSpeed: 700,
        speechRate: { a1: 0.5, a2: 0.6, b1: 0.7, b2: 0.8, c1: 0.9, c2: 1.0 },
        speechLang: 'es-CO',
        seedStages: 7,
        yaguaraMinInterval: 3,
        yaguaraMaxInterval: 5
    };

    /* ==========================================================
       2. AUDIO
    ========================================================== */
    var Audio = {
        _ctx: null,
        _getCtx: function() {
            if (!this._ctx) {
                try { this._ctx = new (window.AudioContext || window.webkitAudioContext)(); }
                catch(e) { /* silent */ }
            }
            return this._ctx;
        },
        _spiralRate: null,
        setSpiralRate: function(rate) { this._spiralRate = rate; },
        speak: function(text, opts) {
            if (!window.speechSynthesis || !text) return;
            window.speechSynthesis.cancel();
            var u = new SpeechSynthesisUtterance(text);
            u.lang = (opts && opts.lang) || CONFIG.speechLang;
            u.rate = (opts && opts.rate) || this._spiralRate || CONFIG.speechRate.a2;
            u.pitch = 1.0;
            u.volume = 1.0;
            if (opts && opts.onEnd) u.onend = opts.onEnd;
            try { speechSynthesis.speak(u); } catch(e) { /* silent */ }
        },
        cancel: function() {
            if (window.speechSynthesis) {
                try { speechSynthesis.cancel(); } catch(e) { /* silent */ }
            }
        },
        silence: function(ms, cb) {
            setTimeout(cb || function(){}, ms || CONFIG.silenceDuration);
        },
        playNatureSound: function(type) {
            var ctx = this._getCtx();
            if (!ctx) return;
            try {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                var now = ctx.currentTime;

                if (type === 'water-drop') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(800, now);
                    osc.frequency.exponentialRampToValueAtTime(400, now + 0.15);
                    gain.gain.setValueAtTime(0.12, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else if (type === 'wind') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(120, now);
                    osc.frequency.linearRampToValueAtTime(180, now + 0.5);
                    gain.gain.setValueAtTime(0.03, now);
                    gain.gain.linearRampToValueAtTime(0.06, now + 0.3);
                    gain.gain.linearRampToValueAtTime(0.001, now + 0.8);
                    osc.start(now);
                    osc.stop(now + 0.8);
                } else if (type === 'bird') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(1200, now);
                    osc.frequency.linearRampToValueAtTime(1600, now + 0.08);
                    osc.frequency.linearRampToValueAtTime(1100, now + 0.16);
                    osc.frequency.linearRampToValueAtTime(1500, now + 0.24);
                    gain.gain.setValueAtTime(0.08, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                    osc.start(now);
                    osc.stop(now + 0.35);
                }
            } catch(e) { /* silent */ }
        }
    };

    /* ==========================================================
       3. WORLD REACTION (Feedback System)
       No text saying "correct" or "incorrect" — ever
    ========================================================== */
    var WorldReaction = {
        _dimTimer: null,
        _harmonyTimer: null,

        harmony: function(containerEl, targetEl, cb) {
            clearTimeout(this._harmonyTimer);
            clearTimeout(this._dimTimer);

            if (targetEl) targetEl.classList.add('yg-glow');
            if (containerEl) document.body.classList.add('yg-world-harmony');

            var sounds = ['water-drop', 'bird'];
            Audio.playNatureSound(sounds[Math.floor(Math.random() * sounds.length)]);

            var self = this;
            this._harmonyTimer = setTimeout(function() {
                if (targetEl) targetEl.classList.remove('yg-glow');
                document.body.classList.remove('yg-world-harmony');
                if (cb) cb();
            }, CONFIG.harmonyDuration);
        },

        desequilibrio: function(containerEl, targetEl, retryCb) {
            clearTimeout(this._dimTimer);
            clearTimeout(this._harmonyTimer);

            /* Track retry attempts for growth data */
            if (Engine._encounterStartTime) Engine._encounterAttempts++;

            Audio.cancel();

            if (containerEl) document.body.classList.add('yg-world-dim');
            if (targetEl) targetEl.classList.add('yg-retreat');

            var self = this;
            this._dimTimer = setTimeout(function() {
                document.body.classList.remove('yg-world-dim');
                if (targetEl) targetEl.classList.remove('yg-retreat');
                if (retryCb) retryCb();
            }, CONFIG.dimDuration + CONFIG.silenceDuration);
        }
    };

    /* ==========================================================
       4. PROGRESS (Seed Visualization)
    ========================================================== */
    var Progress = {
        _seedPaths: [
            'M24 40 Q24 38 24 36',
            'M24 40 Q24 34 24 30 Q22 28 20 28 M24 30 Q26 28 28 28',
            'M24 40 Q24 30 24 24 Q20 22 16 22 M24 24 Q28 22 32 22 M24 30 Q22 28 20 29',
            'M24 40 Q24 26 24 20 Q18 16 14 18 M24 20 Q30 16 34 18 M24 28 Q20 25 17 27 M24 28 Q28 25 31 27',
            'M24 42 Q24 24 24 16 Q16 10 12 14 M24 16 Q32 10 36 14 M24 24 Q18 20 14 22 M24 24 Q30 20 34 22 M24 32 Q20 29 16 31 M24 32 Q28 29 32 31',
            'M24 42 Q24 22 24 12 Q16 6 10 10 M24 12 Q32 6 38 10 M24 20 Q16 14 10 18 M24 20 Q32 14 38 18 M24 28 Q18 24 14 26 M24 28 Q30 24 34 26 M24 36 Q20 33 17 35 M24 36 Q28 33 31 35',
            'M24 44 Q24 20 24 10 Q16 2 8 8 M24 10 Q32 2 40 8 M24 16 Q14 8 8 14 M24 16 Q34 8 40 14 M24 24 Q16 18 10 22 M24 24 Q32 18 38 22 M24 32 Q18 28 14 30 M24 32 Q30 28 34 30 M20 6 Q24 0 28 6'
        ],

        render: function(container, current, total) {
            if (!container) return;
            var stageIdx = Math.min(Math.floor((current / total) * CONFIG.seedStages), CONFIG.seedStages - 1);
            if (current >= total) stageIdx = CONFIG.seedStages - 1;
            var pct = Math.round((current / total) * 100);

            container.innerHTML =
                '<div class="yg-seed-container">' +
                    '<svg class="yg-seed-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                        '<circle cx="24" cy="42" r="4" fill="#5a3e1a" opacity="0.5"/>' +
                        '<path d="' + this._seedPaths[stageIdx] + '" stroke="#c9a227" stroke-width="2" stroke-linecap="round" fill="none"' +
                            (stageIdx >= 5 ? ' stroke="#5a6e4a"' : '') + '/>' +
                        (stageIdx >= 6 ? '<circle cx="24" cy="6" r="4" fill="#c9a227" opacity="0.8"/>' : '') +
                    '</svg>' +
                    '<div style="flex:1">' +
                        '<div class="yg-seed-label">Encuentro ' + current + ' de ' + total + '</div>' +
                        '<div class="yg-seed-track"><div class="yg-seed-fill" style="width:' + pct + '%"></div></div>' +
                    '</div>' +
                '</div>';
        },

        renderCompletion: function(container) {
            if (!container) return;
            var lastPath = this._seedPaths[CONFIG.seedStages - 1];
            container.innerHTML =
                '<svg class="yg-completion-seed" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                    '<circle cx="24" cy="42" r="5" fill="#5a3e1a" opacity="0.6"/>' +
                    '<path d="' + lastPath + '" stroke="#5a6e4a" stroke-width="2.5" stroke-linecap="round" fill="none"/>' +
                    '<circle cx="24" cy="6" r="5" fill="#c9a227" opacity="0.9"/>' +
                    '<circle cx="24" cy="6" r="8" fill="none" stroke="#c9a227" stroke-width="1" opacity="0.3"/>' +
                '</svg>';
        }
    };

    /* ==========================================================
       5. YAGUARÁ (Poetic Interjections)
    ========================================================== */
    var Yaguara = {
        _lines: {
            mundoDeAbajo: [
                'Escucha... el r\u00edo recuerda tu nombre.',
                'Cada palabra es una semilla.',
                'La selva espera. No tiene prisa.',
                'Nombrar es despertar.',
                'El silencio tambi\u00e9n habla.',
                'Las ra\u00edces conocen el camino.',
                'El agua sabe esperar.',
                'Camina despacio. Las palabras te siguen.',
                'Un \u00e1rbol crece cuando lo nombras.',
                'Respira. La selva respira contigo.',
                'No todas las palabras se dicen. Algunas se cuidan.'
            ],
            mundoDelMedio: [
                'El viento trae historias de lejos.',
                'Cada paso deja una huella.',
                'La monta\u00f1a escucha. Habla con calma.',
                'Los caminos se abren cuando caminas.',
                'La tierra conoce todas las voces.',
                'Hay palabras que viven en las piedras.',
                'El sol se mueve despacio. T\u00fa tambi\u00e9n.',
                'No hay prisa. El camino espera.'
            ],
            mundoDeArriba: [
                'Las estrellas tambi\u00e9n escuchan.',
                'El cielo no tiene paredes.',
                'Cada voz es una constelaci\u00f3n.',
                'Las palabras vuelan. D\u00e9jalas.',
                'Mira arriba. Todo est\u00e1 conectado.',
                'La luna sabe de silencios.',
                'Las nubes cambian. Las palabras tambi\u00e9n.'
            ],
            _default: [
                'Cada palabra es una semilla.',
                'El silencio tambi\u00e9n habla.',
                'No hay prisa. El camino espera.',
                'Camina despacio. Las palabras te siguen.',
                'Respira. La selva respira contigo.',
                'Escucha... el r\u00edo recuerda tu nombre.',
                'Las ra\u00edces conocen el camino.'
            ]
        },
        _lastIdx: -1,

        _getLine: function(world) {
            var pool = this._lines[world] || this._lines._default;
            var idx;
            do { idx = Math.floor(Math.random() * pool.length); } while (idx === this._lastIdx && pool.length > 1);
            this._lastIdx = idx;
            return pool[idx];
        },

        interject: function(panelEl, world, cb) {
            if (!panelEl) { if (cb) cb(); return; }
            var line = this._getLine(world);

            panelEl.innerHTML =
                '<div class="yg-yaguara-bubble" id="ygYaguaraBubble">' +
                    '<div class="yg-yaguara-avatar">\uD83D\uDC06</div>' +
                    '<div class="yg-yaguara-text">' + line + '</div>' +
                '</div>';

            var bubble = document.getElementById('ygYaguaraBubble');
            setTimeout(function() {
                if (bubble) bubble.classList.add('visible');
            }, 50);

            Audio.speak(line, { rate: 0.55 });

            setTimeout(function() {
                if (bubble) bubble.classList.remove('visible');
                setTimeout(function() {
                    panelEl.innerHTML = '';
                    if (cb) cb();
                }, 600);
            }, 3500);
        },

        showClosing: function(panelEl, world) {
            if (!panelEl) return;
            var closingLines = [
                'El mundo recuerda. Tu voz forma parte de \u00e9l.',
                'La semilla ha crecido. El viaje contin\u00faa.',
                'Has caminado bien. La selva te conoce.',
                'Cada paso fue necesario. Sigue caminando.'
            ];
            var line = closingLines[Math.floor(Math.random() * closingLines.length)];

            panelEl.innerHTML =
                '<div class="yg-yaguara-bubble" id="ygYaguaraBubble">' +
                    '<div class="yg-yaguara-avatar">\uD83D\uDC06</div>' +
                    '<div class="yg-yaguara-text">' + line + '</div>' +
                '</div>';

            var bubble = document.getElementById('ygYaguaraBubble');
            setTimeout(function() {
                if (bubble) bubble.classList.add('visible');
            }, 50);

            Audio.speak(line, { rate: 0.5 });
        }
    };

    /* ==========================================================
       6. UTILITY
    ========================================================== */
    function shuffle(arr) {
        var a = arr.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }

    function normalize(s) {
        if (!s) return '';
        return s.toLowerCase()
            .replace(/[áà]/g, 'a').replace(/[éè]/g, 'e')
            .replace(/[íì]/g, 'i').replace(/[óò]/g, 'o')
            .replace(/[úù]/g, 'u').replace(/ñ/g, 'n')
            .replace(/[^a-z0-9\s]/g, '').trim().replace(/\s+/g, ' ');
    }

    /* --- Processed conjugation accessors --- */

    function assertVerbShape(verb) {
        if (!verb.conjugations || !verb.conjugations.imperative || !verb.conjugations.imperative.negative) {
            throw new Error('Invalid verb schema: missing negative imperative');
        }
    }

    function getForm(verb, mood, tense, person) {
        if (tense === 'imperative_negative') {
            return verb.conjugations.imperative.negative[person];
        }
        if (tense === 'imperative_affirmative') {
            return verb.conjugations.imperative.affirmative[person];
        }
        return verb.conjugations[mood][tense][person];
    }

    /**
     * Build canonical questions array from a processed VerbEntry.
     * data must have: { conjugations, mood?, tense?, persons? }
     * Uses getForm() — treats the processed shape as canonical.
     */
    function buildQuestionsFromTable(data) {
        var mood = data.mood || 'indicative';
        var tense = data.tense || 'present';
        var lemma = data.lemma || data.verb || '';
        var isImperative = tense === 'imperative_negative' || tense === 'imperative_affirmative';

        /* Resolve the person→form table for this mood/tense */
        var table;
        if (tense === 'imperative_negative') {
            table = data.conjugations.imperative.negative;
        } else if (tense === 'imperative_affirmative') {
            table = data.conjugations.imperative.affirmative;
        } else {
            table = (data.conjugations[mood] || {})[tense] || {};
        }

        /* Determine which persons to quiz */
        var persons = data.persons || Object.keys(table);

        /* Collect all non-null forms for distractor generation */
        var allForms = [];
        var key;
        for (key in table) {
            if (table[key] !== null) allForms.push(table[key]);
        }

        /* Build questions — skip null forms (e.g. vosotros in LatAm) */
        var questions = [];
        for (var i = 0; i < persons.length; i++) {
            var person = persons[i];
            var answer = getForm(data, mood, tense, person);
            if (answer === null || answer === undefined) continue;

            questions.push({
                verb: lemma,
                subject: person,
                answer: answer,
                options: data.options || generateDistractors(answer, allForms, 4),
                context: data.context || (isImperative ? tense.replace('_', ' ') : tense)
            });
        }
        return questions;
    }

    /**
     * Generate multiple-choice options from sibling forms in the same tense.
     * Returns [correct, ...distractors] (shuffled by renderer later).
     */
    function generateDistractors(correct, allForms, count) {
        var pool = [];
        for (var i = 0; i < allForms.length; i++) {
            if (allForms[i] !== correct) pool.push(allForms[i]);
        }
        pool = shuffle(pool);
        var options = [correct];
        for (var i = 0; i < Math.min(count - 1, pool.length); i++) {
            options.push(pool[i]);
        }
        return options;
    }

    function decodeHTML(s) {
        if (!s) return '';
        var el = document.createElement('span');
        el.innerHTML = s;
        return el.textContent || el.innerText || '';
    }

    /* SVG icon helpers */
    var ICONS = {
        speaker: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>',
        repeat: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>',
        /* Module icons (24x24 stroke-based) */
        familia: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="7" r="2.5"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M17 11.5a3 3 0 013 3V21"/></svg>',
        sonidos: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10a6 6 0 006 6v0a6 6 0 006-6"/><path d="M12 16v0a4 4 0 01-4-4V8a4 4 0 018 0v4a4 4 0 01-4 4z"/><path d="M17 10a8 8 0 01.5 2"/><path d="M19 8a10 10 0 01.7 4"/></svg>',
        agua: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C12 2 5 10 5 14.5A7 7 0 0019 14.5C19 10 12 2 12 2z"/></svg>',
        casa: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 10v9a1 1 0 001 1h12a1 1 0 001-1v-9"/><rect x="9" y="14" width="6" height="6" rx="0.5"/></svg>',
        comida: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="14" r="7"/><path d="M12 7V3"/><path d="M15 5c0 0-1.5 2-3 2S9 5 9 5"/></svg>',
        amigos: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 12h10"/><path d="M7 12c-2 0-4-1.5-4-3.5S5 5 7 5s3 1.5 3 3.5"/><path d="M17 12c2 0 4-1.5 4-3.5S19 5 17 5s-3 1.5-3 3.5"/><path d="M7 12c-2 0-4 1.5-4 3.5S5 19 7 19s3-1.5 3-3.5"/><path d="M17 12c2 0 4 1.5 4 3.5S19 19 17 19s-3-1.5-3-3.5"/></svg>',
        /* Game type icons (24x24 stroke-based) */
        pair: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="9" cy="9" rx="5" ry="4"/><ellipse cx="15" cy="15" rx="5" ry="4"/></svg>',
        fill: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3l4 4-10 10H7v-4L17 3z"/><path d="M4 21h16"/><path d="M13 7l4 4"/></svg>',
        conjugation: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v16"/><path d="M6 12h6l4-6"/><path d="M12 12l4 6"/></svg>',
        listening: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14h2a2 2 0 012 2v2a2 2 0 01-2 2H3V14z"/><path d="M21 14h-2a2 2 0 00-2 2v2a2 2 0 002 2h2V14z"/><path d="M3 14V10a9 9 0 0118 0v4"/></svg>',
        category: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>',
        builder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="16" width="8" height="5" rx="0.5"/><rect x="8" y="16" width="8" height="5" rx="0.5"/><rect x="14" y="16" width="8" height="5" rx="0.5"/><rect x="5" y="10" width="8" height="5" rx="0.5"/><rect x="11" y="10" width="8" height="5" rx="0.5"/><rect x="8" y="4" width="8" height="5" rx="0.5"/></svg>',
        translation: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h6M8 5v3m-3 4c1 2 3 4 6 4"/><path d="M14 5l3 9 3-9"/><path d="M15 11h4"/><path d="M2 16l4-4"/><path d="M18 19l4-4"/></svg>',
        conversation: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
        dictation: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a4 4 0 014 4v6a4 4 0 01-8 0V5a4 4 0 014-4z"/><path d="M19 11a7 7 0 01-14 0"/><line x1="12" y1="18" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>',
        story: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
        narrative: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/></svg>',
        cancion: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        escaperoom: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
        /* Fragment icons (24x24 stroke-based) */
        raiz: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6"/><path d="M12 8c-3 3-6 8-6 12"/><path d="M12 8c3 3 6 8 6 12"/><path d="M12 8c-1 4-1 8 0 12"/><path d="M9 12c-2 1-4 3-5 5"/><path d="M15 12c2 1 4 3 5 5"/></svg>',
        historia: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M6 6c-1 0-2 .5-2 2v8c0 1.5 1 2 2 2"/><line x1="10" y1="8" x2="14" y2="8"/><line x1="10" y1="12" x2="14" y2="12"/></svg>',
        constelacion: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1.5"/><circle cx="5" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/><circle cx="8" cy="19" r="1.5"/><circle cx="16" cy="19" r="1.5"/><line x1="12" y1="5" x2="5" y2="12"/><line x1="12" y1="5" x2="19" y2="12"/><line x1="5" y1="12" x2="8" y2="19"/><line x1="19" y1="12" x2="16" y2="19"/><line x1="8" y1="19" x2="16" y2="19"/></svg>'
    };

    /* Map canonical game types to icon keys */
    var TYPE_ICONS = {
        pair: 'pair', fill: 'fill', conjugation: 'conjugation',
        listening: 'listening', category: 'category', builder: 'builder',
        translation: 'translation', conversation: 'conversation',
        dictation: 'dictation', story: 'story', narrative: 'narrative',
        cancion: 'cancion', escaperoom: 'escaperoom'
    };

    /* Map A1 module slugs to icon keys */
    var MODULE_ICONS = {
        familia: 'familia', sonidos: 'sonidos', agua: 'agua',
        casa: 'casa', comida: 'comida', amigos: 'amigos'
    };

    /**
     * Render a type-label div with an SVG icon prepended.
     * @param {string} label  — Display text (e.g. "Emparejar")
     * @param {string} type   — Canonical game type (e.g. "pair")
     * @param {string} [extraClass] — Additional CSS class(es) for the label
     * @returns {string} HTML string
     */
    function renderTypeLabel(label, type, extraClass) {
        var iconKey = TYPE_ICONS[type] || '';
        var iconSvg = iconKey && ICONS[iconKey] ? ICONS[iconKey] : '';
        var iconHtml = iconSvg ? '<span class="yg-type-icon">' + iconSvg + '</span>' : '';
        var cls = 'yg-type-label' + (extraClass ? ' ' + extraClass : '');
        return '<div class="' + cls + '">' + iconHtml + label + '</div>';
    }

    /* ==========================================================
       7. DATA NORMALIZER
       Converts all data format variants into canonical form
    ========================================================== */
    var Normalizer = {
        /* Type alias map */
        typeMap: {
            'fib': 'fill',
            'sorting': 'category',
            'fill-blank': 'fill',
            'fill-blank-multi': 'fill',
            'fill_blank': 'fill',
            'fill_blank_multi': 'fill',
            'pair_matching': 'pair',
            'pair-matching': 'pair',
            'pairs': 'pair',
            'category_sort': 'category',
            'category_sort_three': 'category',
            'category-sort': 'category',
            'sort': 'category',
            'sort3': 'category',
            'sentence_builder': 'builder',
            'sentence-builder': 'builder',
            'sb': 'builder',
            'build': 'builder',
            'conj': 'conjugation',
            'verb_conjugation': 'conjugation',
            'verb_conjugation_multi': 'conjugation',
            'verb-conjugation': 'conjugation',
            'conjugation_table': 'conjugation',
            'verb_table': 'conjugation',
            'listen': 'listening',
            'listening_multi': 'listening',
            'trans': 'translation',
            'conv': 'conversation',
            'dict': 'dictation',
            'intro': 'narrative',
            'teach': 'narrative',
            'context': 'narrative',
            'explain': 'narrative',
            'song': 'cancion',
            'lyrics': 'cancion',
            'canciones': 'cancion',
            'escape': 'escaperoom',
            'escape_room': 'escaperoom',
            'escape-room': 'escaperoom',
            'room': 'escaperoom',
            'quest': 'escaperoom'
        },

        normalizeType: function(type) {
            return this.typeMap[type] || type;
        },

        normalizePairs: function(data) {
            /* Canonical: pairs = [['a','b'], ...] */
            if (!data.pairs || !data.pairs.length) return data;
            if (typeof data.pairs[0] === 'object' && !Array.isArray(data.pairs[0])) {
                data.pairs = data.pairs.map(function(p) {
                    return [p.left || p.a || p.spanish || '', p.right || p.b || p.english || ''];
                });
            }
            return data;
        },

        normalizeFill: function(data) {
            /* Canonical: questions = [{sentence, answer, options(optional), hint(optional)}, ...] */
            if (data.questions) return data;
            if (data.sentences) {
                data.questions = [];
                for (var si = 0; si < data.sentences.length; si++) {
                    var s = data.sentences[si];
                    if (s.blanks && Array.isArray(s.blanks)) {
                        /* dest12 format: blanks is array of answers for multiple blanks in one sentence */
                        for (var bi = 0; bi < s.blanks.length; bi++) {
                            data.questions.push({
                                sentence: s.text || s.sentence || '',
                                answer: s.blanks[bi],
                                options: s.options || s.choices || null,
                                hint: s.hint || null
                            });
                        }
                    } else if (s.answers && Array.isArray(s.answers)) {
                        /* dest11 fill_blank_multi format: answers is array */
                        for (var ai = 0; ai < s.answers.length; ai++) {
                            data.questions.push({
                                sentence: s.text || s.sentence || '',
                                answer: s.answers[ai],
                                options: s.options || s.choices || null,
                                hint: s.hint || null
                            });
                        }
                    } else {
                        data.questions.push({
                            sentence: s.text || s.sentence || '',
                            answer: s.answer || s.blank || '',
                            options: s.options || s.choices || null,
                            hint: s.hint || null
                        });
                    }
                }
            } else if (data.sentence && data.blanks) {
                /* fill_blank_multi: single sentence with named blanks */
                var keys = Object.keys(data.blanks).sort();
                data.questions = keys.map(function(k) {
                    var b = data.blanks[k];
                    return {
                        sentence: data.sentence.replace(k, '___'),
                        answer: b.answer || b,
                        options: null,
                        hint: b.hint || null
                    };
                });
                data._fillMultiSentence = data.sentence;
                data._fillMultiBlanks = data.blanks;
            } else if (data.prompt && data.answer && !data.words) {
                /* Single fill item (dest13 format) */
                data.questions = [{
                    sentence: data.prompt,
                    answer: data.answer,
                    options: data.options || data.choices || null,
                    hint: data.hint || null
                }];
            }
            return data;
        },

        normalizeConjugation: function(data) {
            /* Canonical: questions = [{verb, subject, answer, options(optional), context(optional)}, ...] */
            if (data.questions) return data;

            /* Ontology-driven: linguisticTargetId (string) references an ontology LT.
               The resolver hydrates verb data + sets the pointer, then falls through
               to the existing linguisticTarget handler below. */
            if (data.linguisticTargetId && Normalizer._resolver && Normalizer._ontology) {
                var lt = Normalizer._ontology.getTarget(data.linguisticTargetId);
                if (lt && lt.category === 'verb') {
                    var verbData = Normalizer._resolver.getVerbData(lt.lexicalItem.lemma);
                    if (verbData) {
                        data.conjugations = verbData.conjugations;
                        data.lemma = data.lemma || lt.lexicalItem.lemma;
                        data.verb_type = verbData.verb_type;
                        /* Set the pointer so existing handler below slices correctly */
                        if (!data.linguisticTarget) data.linguisticTarget = lt.features;
                        /* Tag for vocabulary extraction and adaptivity */
                        data._ontologyTarget = lt;
                    }
                }
            }

            /* linguisticTarget: a lightweight pointer into the frozen verb data.
               { mood: "imperative", tense: "negative", person: "tú" }
               Sets data.mood, data.tense, data.persons so buildQuestionsFromTable
               reads the right slice. No logic encoded — just a selector. */
            if (data.linguisticTarget) {
                var lt = data.linguisticTarget;
                if (lt.mood === 'imperative') {
                    data.tense = 'imperative_' + (lt.tense || 'affirmative');
                } else {
                    data.mood = lt.mood || data.mood;
                    data.tense = lt.tense || data.tense;
                }
                if (lt.person) data.persons = [lt.person];
            }

            if (data.conjugations) {
                /* Processed VerbEntry format:
                   { lemma, verb_type, conjugations: {mood: {tense: {person: form}}},
                     mood?, tense?, persons? }
                   Treated as canonical — negative imperative always exists,
                   missing forms are null (not absent). */
                assertVerbShape(data);
                data.questions = buildQuestionsFromTable(data);
            } else if (data.prompts) {
                data.questions = data.prompts.map(function(p) {
                    return {
                        verb: p.verb || data.verb || '',
                        subject: p.subject || p.pronoun || '',
                        answer: p.answer,
                        options: p.options || null,
                        context: p.context || null
                    };
                });
            } else if (data.forms) {
                /* dest9 format: {verb, forms: [{pronoun, answer}]} */
                data.questions = data.forms.map(function(f) {
                    return {
                        verb: data.verb || '',
                        subject: f.pronoun || '',
                        answer: f.answer,
                        options: null,
                        context: null
                    };
                });
            } else if (data.items) {
                /* verb_conjugation_multi: {items: [{prompt, answer, hint}]} */
                data.questions = data.items.map(function(item) {
                    return {
                        verb: item.prompt || '',
                        subject: '',
                        answer: item.answer,
                        options: item.options || null,
                        context: item.hint || null
                    };
                });
            } else if (data.prompt && data.answer) {
                /* Single verb_conjugation: {prompt, subject, verb, tense, answer} */
                data.questions = [{
                    verb: data.prompt || (data.verb || ''),
                    subject: data.subject || '',
                    answer: data.answer,
                    options: null,
                    context: data.tense || null
                }];
            }
            return data;
        },

        normalizeListening: function(data) {
            /* Canonical: {audio, question, answer, options} — single question per game */
            /* Map audioText/audio_text/listenText/sentence → audio */
            if (data.audioText && !data.audio) data.audio = data.audioText;
            if (data.audio_text && !data.audio) data.audio = data.audio_text;
            if (data.listenText && !data.audio) data.audio = data.listenText;
            if (data.sentence && !data.audio && !data.sentences) data.audio = data.sentence;
            /* Map correctIndex → correct */
            if (data.correctIndex !== undefined && data.correct === undefined) data.correct = data.correctIndex;
            /* Derive answer from options + correct index */
            if (data.audio && data.answer === undefined && data.options && data.correct !== undefined) {
                data.answer = typeof data.correct === 'number' ? data.options[data.correct] : data.correct;
            }
            /* Map choices → options */
            if (data.choices && !data.options) data.options = data.choices;
            if (data.audio && data.answer !== undefined && !data.questions) return data;
            if (data.questions && data.questions.length) {
                /* Multi-question: engine handles sub-questions via _multiListening */
                data._multiListening = data.questions.map(function(q) {
                    var ci = q.correctIndex !== undefined ? q.correctIndex : q.correct;
                    return {
                        audio: q.audio || q.audioText || data.audio || '',
                        question: q.question || q.q || '',
                        options: q.options || q.choices || [],
                        correct: ci,
                        answer: (ci !== undefined && q.options) ? q.options[ci] : (q.answer || '')
                    };
                });
                var first = data._multiListening[0];
                data.audio = first.audio;
                data.question = first.question || data.question || '';
                data.answer = first.answer;
                data.options = first.options;
            }
            return data;
        },

        normalizeCategory: function(data) {
            /* Canonical: {categories: [...], items: [{text, category(name)}, ...]} */
            if (data.items && data.items.length) {
                data.items = data.items.map(function(item) {
                    var text = item.text || item.word || '';
                    var cat = item.category || item.cat || '';
                    if (!cat && item.correct !== undefined && data.categories) {
                        cat = data.categories[item.correct] || '';
                    }
                    return { text: text, category: cat };
                });
            }
            return data;
        },

        normalizeBuilder: function(data) {
            /* Canonical: {words, answer} */
            if (data.words && data.answer !== undefined) return data;
            if (data.words && data.correct !== undefined) {
                data.answer = data.correct;
            }
            if (data.sentences && data.sentences.length) {
                data._multiBuilder = data.sentences;
                var s = data.sentences[0];
                data.words = s.words;
                data.answer = s.answer || s.correct || '';
            }
            return data;
        },

        normalizeTranslation: function(data) {
            /* Canonical: {source, words, answer} */
            if (!data.source && data.english) data.source = data.english;
            if (!data.source && data.en) data.source = data.en;
            if (data.source && data.answer !== undefined) return data;
            if (data.source && data.correct !== undefined) {
                data.answer = data.correct;
            }
            /* Handle questions[] as alias for sentences[] */
            if (data.questions && data.questions.length && !data.sentences) {
                data.sentences = data.questions.map(function(q) {
                    var s = { source: q.source || q.english || q.en || '', answer: q.answer || q.correct || '' };
                    if (q.words) { s.words = q.words; }
                    else { s.words = s.answer.split(/\s+/); }
                    return s;
                });
                delete data.questions;
            }
            if (data.sentences && data.sentences.length) {
                /* Ensure each sentence has words array */
                for (var i = 0; i < data.sentences.length; i++) {
                    if (!data.sentences[i].words && data.sentences[i].answer) {
                        data.sentences[i].words = data.sentences[i].answer.split(/\s+/);
                    }
                }
                data._multiTranslation = data.sentences;
                var s = data.sentences[0];
                data.source = s.english || s.en || s.source || '';
                data.words = s.words;
                data.answer = s.answer || s.correct || '';
            }
            return data;
        },

        normalizeConversation: function(data) {
            /* Canonical: turns = [{dialogue: [{speaker, name, text},...], options, answer}, ...] */
            if (data.turns) {
                /* Check if turns use npc/options/correctIndex format (dest14-18) */
                if (data.turns.length && data.turns[0].npc !== undefined) {
                    data.turns = data.turns.map(function(t) {
                        var ci = t.correctIndex !== undefined ? t.correctIndex : t.correct;
                        var ans = (ci !== undefined && typeof ci === 'number') ? t.options[ci] : (t.answer || t.correct || '');
                        return {
                            dialogue: [
                                { speaker: 'A', name: 'Otro', text: t.npc || '' },
                                { speaker: 'B', name: 'T\u00fa', text: '???' }
                            ],
                            options: t.options,
                            answer: ans
                        };
                    });
                    return data;
                }
                /* Ensure each turn has .answer (not just .correct/.correctIndex) */
                data.turns.forEach(function(t) {
                    if (t.correctIndex !== undefined && t.correct === undefined) t.correct = t.correctIndex;
                    if (t.answer === undefined && t.correct !== undefined) {
                        if (typeof t.correct === 'number') t.answer = t.options[t.correct];
                        else t.answer = t.correct;
                    }
                });
                return data;
            }
            if (data.exchanges) {
                data.turns = data.exchanges.map(function(ex) {
                    var dialogue = [];
                    if (ex.context) {
                        for (var i = 0; i < ex.context.length; i++) {
                            var c = ex.context[i];
                            dialogue.push({
                                speaker: c.speaker || (i % 2 === 0 ? 'A' : 'B'),
                                name: c.name || (i % 2 === 0 ? 'Otro' : 'T\u00fa'),
                                text: c.text || c
                            });
                        }
                    }
                    dialogue.push({ speaker: 'B', name: 'T\u00fa', text: '???' });
                    var ans = typeof ex.correct === 'number' ? ex.options[ex.correct] : (ex.correct || ex.answer || '');
                    return { dialogue: dialogue, options: ex.options, answer: ans };
                });
                return data;
            }
            if (data.steps) {
                data.turns = data.steps.map(function(step) {
                    var dialogue = [
                        { speaker: 'A', name: 'Otro', text: step.npc || '' },
                        { speaker: 'B', name: 'T\u00fa', text: '???' }
                    ];
                    var ans = typeof step.correct === 'number' ? step.options[step.correct] : (step.correct || step.answer || '');
                    return { dialogue: dialogue, options: step.options, answer: ans };
                });
                return data;
            }
            return data;
        },

        normalizeDictation: function(data) {
            /* Canonical: {audio, answer, hint(optional)} */
            if (data.audioText && !data.audio) data.audio = data.audioText;
            if (data.audio_text && !data.audio) data.audio = data.audio_text;
            if (data.listenText && !data.audio) data.audio = data.listenText;
            if (data.audio && data.answer !== undefined) return data;
            /* Handle questions[] as alias for sentences[] */
            if (data.questions && data.questions.length && !data.sentences) {
                data.sentences = data.questions.map(function(q) {
                    return { audio: q.audio || q.audioText || q.text || '', answer: q.answer || q.text || '', hint: q.hint || null };
                });
                delete data.questions;
            }
            if (data.sentences && data.sentences.length) {
                data._multiDictation = data.sentences;
                var s = data.sentences[0];
                data.audio = s.audio || s.audioText || s.audio_text || s.listenText || s.text || '';
                data.answer = s.answer || s.text || '';
                data.hint = s.hint || null;
            }
            return data;
        },

        normalizeStory: function(data) {
            /* Canonical: {title, text, questions: [{q, options, answer}, ...]} */
            if (!data.text && data.story) data.text = data.story;
            if (data.questions) {
                data.questions.forEach(function(question) {
                    /* Map question → q */
                    if (question.question && !question.q) question.q = question.question;
                    /* Map correctIndex → correct */
                    if (question.correctIndex !== undefined && question.correct === undefined) question.correct = question.correctIndex;
                    if (question.answer === undefined && question.correct !== undefined) {
                        if (typeof question.correct === 'number') {
                            question.answer = question.options[question.correct];
                        } else {
                            question.answer = question.correct;
                        }
                    }
                });
            }
            return data;
        },

        normalizeGame: function(game) {
            var g = {};
            for (var k in game) { if (game.hasOwnProperty(k)) g[k] = game[k]; }
            g.type = this.normalizeType(g.type);

            /* Normalize common field aliases */
            if (!g.instruction && g.instructions) g.instruction = g.instructions;
            if (!g.instruction && g.description) g.instruction = g.description;

            switch (g.type) {
                case 'pair': g = this.normalizePairs(g); break;
                case 'fill': g = this.normalizeFill(g); break;
                case 'conjugation': g = this.normalizeConjugation(g); break;
                case 'listening': g = this.normalizeListening(g); break;
                case 'category': g = this.normalizeCategory(g); break;
                case 'builder': g = this.normalizeBuilder(g); break;
                case 'translation': g = this.normalizeTranslation(g); break;
                case 'conversation': g = this.normalizeConversation(g); break;
                case 'dictation': g = this.normalizeDictation(g); break;
                case 'story': g = this.normalizeStory(g); break;
                case 'narrative': g = this.normalizeNarrative(g); break;
                case 'cancion': g = this.normalizeCancion(g); break;
                case 'escaperoom': g = this.normalizeEscapeRoom(g); break;
            }

            /* Tag ontology target on any game type (for tracking, not logic) */
            if (g.linguisticTargetId && !g._ontologyTarget && Normalizer._ontology) {
                g._ontologyTarget = Normalizer._ontology.getTarget(g.linguisticTargetId) || null;
            }

            return g;
        },

        normalizeNarrative: function(data) {
            /* Ensure sections array exists */
            if (!data.sections) {
                data.sections = [];
                if (data.text) {
                    data.sections.push({ body: data.text });
                }
            }
            if (!data.button) data.button = 'Continuar';
            return data;
        },

        normalizeCancion: function(data) {
            /* Canonical: {youtubeId, artist, songTitle, lines: [{text, blank?, options?}, ...]} */
            if (!data.lines) data.lines = [];
            /* Ensure each line has at least a text field */
            for (var i = 0; i < data.lines.length; i++) {
                var line = data.lines[i];
                if (typeof line === 'string') {
                    data.lines[i] = { text: line };
                }
            }
            return data;
        },

        normalizeEscapeRoom: function(data) {
            /* Canonical: {room: {name, description, ambience}, puzzles: [{puzzleType, prompt, clue, answer, ...}], fragment: {text, questClue, item}} */
            if (!data.room) data.room = { name: 'Sala de enigmas', description: '', ambience: '' };
            if (!data.room.name) data.room.name = 'Sala de enigmas';
            if (!data.puzzles) data.puzzles = [];
            for (var i = 0; i < data.puzzles.length; i++) {
                var p = data.puzzles[i];
                if (!p.puzzleType) p.puzzleType = 'wordlock';
                if (!p.prompt) p.prompt = '';
                if (!p.clue) p.clue = '';
                if (!p.answer && p.puzzleType !== 'sequence') p.answer = '';
                if (!p.onSolve) p.onSolve = '';
                if (!p.hint) p.hint = '';
                if (!p.options) p.options = [];
                if (!p.passage) p.passage = '';
                if (!p.scrambled) p.scrambled = '';
                if (!p.items) p.items = [];
                if (!p.premises) p.premises = [];
                if (!p.sources) p.sources = [];
            }
            if (!data.fragment) data.fragment = { text: '', questClue: '', item: '' };
            if (!data.label) data.label = 'Sala de enigmas';
            if (!data.instruction) data.instruction = 'Resuelve los enigmas para abrir la puerta.';
            return data;
        }
    };

    /* ==========================================================
       8. ARCHETYPE RENDERERS
    ========================================================== */
    var Renderers = {};

    /* ----- PAIR MATCHING (NamingRitual) ----- */
    Renderers.pair = {
        render: function(data, container, state, onComplete) {
            var cards = [];
            for (var i = 0; i < data.pairs.length; i++) {
                cards.push({ pairId: i, side: 0, text: data.pairs[i][0] });
                cards.push({ pairId: i, side: 1, text: data.pairs[i][1] });
            }
            cards = shuffle(cards);

            var html = renderTypeLabel(data.label || 'Emparejar', 'pair') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-pair-grid" id="ygPairGrid">';
            for (var c = 0; c < cards.length; c++) {
                html += '<div class="yg-pair-card" data-pair="' + cards[c].pairId + '" data-side="' + cards[c].side + '">' +
                            '<div class="yg-card-face yg-card-back">&#9679;</div>' +
                            '<div class="yg-card-face yg-card-front">' + cards[c].text + '</div>' +
                        '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            state.flipped = [];
            state.matched = 0;
            state.total = data.pairs.length;
            state.busy = false;

            var grid = document.getElementById('ygPairGrid');
            grid.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-pair-card');
                if (!el || el.classList.contains('flipped') || el.classList.contains('matched') || state.busy) return;
                el.classList.add('flipped');
                state.flipped.push(el);

                if (state.flipped.length === 2) {
                    state.busy = true;
                    var a = state.flipped[0];
                    var b = state.flipped[1];
                    if (a.dataset.pair === b.dataset.pair && a.dataset.side !== b.dataset.side) {
                        setTimeout(function() {
                            a.classList.add('matched');
                            b.classList.add('matched');
                            WorldReaction.harmony(container, a);
                            state.matched++;
                            state.flipped = [];
                            state.busy = false;
                            if (state.matched === state.total) {
                                setTimeout(function() { onComplete(true); }, CONFIG.harmonyDuration);
                            }
                        }, 400);
                    } else {
                        WorldReaction.desequilibrio(container, b, function() {
                            a.classList.remove('flipped');
                            b.classList.remove('flipped');
                            state.flipped = [];
                            state.busy = false;
                        });
                    }
                }
            });
        }
    };

    /* ----- FILL IN THE BLANK (StoryWeaving) ----- */
    Renderers.fill = {
        render: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;
            var q = data.questions[state.subIdx];
            if (!q) { onComplete(true); return; }

            var html = renderTypeLabel(data.label || 'Completar', 'fill') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            if (data.questions.length > 1) {
                html += '<div class="yg-sub-counter">Pregunta ' + (state.subIdx + 1) + ' de ' + data.questions.length + '</div>';
            }

            var sentence = q.sentence || q.text || '';
            var parts = sentence.split('___');
            if (parts.length < 2) parts = sentence.split('_');
            var displayed = parts[0] + '<span class="yg-fib-blank" id="ygFibBlank">' + (state.answers[state.subIdx] || '______') + '</span>' + (parts[1] || '');
            html += '<div class="yg-fib-sentence">' + displayed + '</div>';

            if (q.options) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-fib-options" id="ygFibOptions">';
                for (var i = 0; i < opts.length; i++) {
                    var sel = (state.answers[state.subIdx] === opts[i]) ? ' selected' : '';
                    html += '<div class="yg-fib-option' + sel + '" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                /* Free text input */
                html += '<input type="text" class="yg-dict-input" id="ygFillInput" placeholder="Escribe tu respuesta..." value="' + (state.answers[state.subIdx] || '') + '" autocomplete="off" autocapitalize="sentences">';
                if (q.hint) html += '<div class="yg-dict-hint">' + q.hint + '</div>';
                html += '<button class="yg-listo-btn" id="ygFillListo">Listo</button>';
            }

            container.innerHTML = html;

            var self = this;
            if (q.options) {
                var optContainer = document.getElementById('ygFibOptions');
                optContainer.addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-fib-option');
                    if (!el || state.resolved) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = el.dataset.val;
                    document.getElementById('ygFibBlank').textContent = el.dataset.val;

                    var all = optContainer.querySelectorAll('.yg-fib-option');
                    for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(el.dataset.val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, el, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, el, function() {
                            state.resolved = false;
                            for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                        });
                    }
                });
            } else {
                var input = document.getElementById('ygFillInput');
                input.addEventListener('input', function() { state.answers[state.subIdx] = input.value; });
                document.getElementById('ygFillListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, input, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, input, function() {
                            state.resolved = false;
                        });
                    }
                });
            }
        },

        _advance: function(data, container, state, onComplete) {
            if (state.subIdx < data.questions.length - 1) {
                state.subIdx++;
                state.resolved = false;
                this.render(data, container, state, onComplete);
            } else {
                var correctCount = 0;
                for (var i = 0; i < data.questions.length; i++) {
                    if (state.answers[i] && normalize(state.answers[i]) === normalize(data.questions[i].answer)) correctCount++;
                }
                onComplete(correctCount >= Math.ceil(data.questions.length / 2));
            }
        }
    };

    /* ----- VERB CONJUGATION (NamingRitual) ----- */
    Renderers.conjugation = {
        render: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;
            var q = data.questions[state.subIdx];
            if (!q) { onComplete(true); return; }

            var html = renderTypeLabel(data.label || 'Conjugaci\u00f3n', 'conjugation') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            if (data.questions.length > 1) {
                html += '<div class="yg-sub-counter">Pregunta ' + (state.subIdx + 1) + ' de ' + data.questions.length + '</div>';
            }

            html += '<div class="yg-vc-prompt">' +
                        '<div class="yg-vc-verb">' + q.verb + '</div>' +
                        '<div class="yg-vc-subject">(' + q.subject + ')</div>' +
                        (q.context ? '<div class="yg-vc-context">' + q.context + '</div>' : '') +
                    '</div>';

            if (q.options) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-vc-options" id="ygVcOptions">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-vc-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygConjInput" placeholder="Escribe la conjugaci\u00f3n..." value="" autocomplete="off">';
                html += '<button class="yg-listo-btn" id="ygConjListo">Listo</button>';
            }

            container.innerHTML = html;

            var self = this;
            if (q.options) {
                document.getElementById('ygVcOptions').addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-vc-option');
                    if (!el || state.resolved) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = el.dataset.val;

                    var all = document.querySelectorAll('.yg-vc-option');
                    for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(el.dataset.val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, el, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, el, function() {
                            state.resolved = false;
                            for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                        });
                    }
                });
            } else {
                document.getElementById('ygConjListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var input = document.getElementById('ygConjInput');
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, input, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, input, function() {
                            state.resolved = false;
                        });
                    }
                });
            }
        },

        _advance: function(data, container, state, onComplete) {
            if (state.subIdx < data.questions.length - 1) {
                state.subIdx++;
                state.resolved = false;
                this.render(data, container, state, onComplete);
            } else {
                var correctCount = 0;
                for (var i = 0; i < data.questions.length; i++) {
                    if (state.answers[i] && normalize(state.answers[i]) === normalize(data.questions[i].answer)) correctCount++;
                }
                onComplete(correctCount >= Math.ceil(data.questions.length / 2));
            }
        }
    };

    /* ----- LISTENING (NamingRitual) ----- */
    Renderers.listening = {
        render: function(data, container, state, onComplete) {
            if (data._multiListening && data._multiListening.length > 1) {
                return this._renderMulti(data, container, state, onComplete);
            }
            state.selected = null;
            state.resolved = false;
            var opts = shuffle((data.options || []).slice());

            var html = renderTypeLabel(data.label || 'Escuchar', 'listening') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-listen-area">' +
                           '<button class="yg-listen-btn" id="ygListenBtn">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn">' + ICONS.repeat + '</button>' +
                       '</div>';

            if (data.question) {
                html += '<div class="yg-listen-question">' + data.question + '</div>';
            }

            html += '<div class="yg-listen-options" id="ygListenOpts">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-listen-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            document.getElementById('ygListenBtn').addEventListener('click', function() { Audio.speak(data.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { Audio.speak(data.audio); });

            setTimeout(function() { Audio.speak(data.audio); }, 400);

            document.getElementById('ygListenOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-listen-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                state.selected = el.dataset.val;

                var all = document.querySelectorAll('.yg-listen-option');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                var isCorrect = normalize(el.dataset.val) === normalize(data.answer);
                if (isCorrect) {
                    WorldReaction.harmony(container, el, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.resolved = false;
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                    });
                }
            });
        },

        _renderMulti: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;
            var questions = data._multiListening;
            var q = questions[state.subIdx];

            var answer = typeof q.correct === 'number' ? q.options[q.correct] : (q.correct || q.answer || '');
            var opts = shuffle(q.options.slice());

            var html = renderTypeLabel(data.label || 'Escuchar', 'listening') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-sub-counter">Pregunta ' + (state.subIdx + 1) + ' de ' + questions.length + '</div>' +
                       '<div class="yg-listen-area">' +
                           '<button class="yg-listen-btn" id="ygListenBtn">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn">' + ICONS.repeat + '</button>' +
                       '</div>';

            if (q.question) {
                html += '<div class="yg-listen-question">' + q.question + '</div>';
            }

            html += '<div class="yg-listen-options" id="ygListenOpts">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-listen-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            document.getElementById('ygListenBtn').addEventListener('click', function() { Audio.speak(q.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { Audio.speak(q.audio); });
            setTimeout(function() { Audio.speak(q.audio); }, 400);

            var self = this;
            document.getElementById('ygListenOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-listen-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                state.answers[state.subIdx] = el.dataset.val;

                var all = document.querySelectorAll('.yg-listen-option');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                var isCorrect = normalize(el.dataset.val) === normalize(answer);
                if (isCorrect) {
                    WorldReaction.harmony(container, el, function() {
                        if (state.subIdx < questions.length - 1) {
                            state.subIdx++;
                            state.resolved = false;
                            self._renderMulti(data, container, state, onComplete);
                        } else {
                            onComplete(true);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.resolved = false;
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                    });
                }
            });
        }
    };

    /* ----- CATEGORY SORTING (GuidedMovement) ----- */
    Renderers.category = {
        render: function(data, container, state, onComplete) {
            state.placements = state.placements || {};
            state.activeItem = null;
            var items = data.items;

            var html = renderTypeLabel(data.label || 'Clasificar', 'category') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-sort-hint">Haz clic en un elemento, luego en la categor\u00eda.</div>' +
                       '<div class="yg-sort-items" id="ygSortItems">';
            for (var i = 0; i < items.length; i++) {
                var sorted = state.placements[items[i].text] ? ' sorted' : '';
                html += '<div class="yg-sort-item' + sorted + '" data-item="' + items[i].text + '">' + items[i].text + '</div>';
            }
            html += '</div><div class="yg-sort-categories" id="ygSortCats">';
            for (var c = 0; c < data.categories.length; c++) {
                html += '<div class="yg-sort-category" data-cat="' + data.categories[c] + '">' +
                            '<div class="yg-sort-cat-title">' + data.categories[c] + '</div>' +
                            '<div class="yg-sort-cat-items" id="ygSortCat_' + c + '">';
                for (var p in state.placements) {
                    if (state.placements[p] === data.categories[c]) {
                        html += '<span class="yg-sorted-chip">' + p + '</span>';
                    }
                }
                html += '</div></div>';
            }
            html += '</div>';
            container.innerHTML = html;

            document.getElementById('ygSortItems').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sort-item');
                if (!el || el.classList.contains('sorted')) return;
                var allItems = document.querySelectorAll('.yg-sort-item');
                for (var j = 0; j < allItems.length; j++) allItems[j].classList.remove('active-item');
                el.classList.add('active-item');
                state.activeItem = el.dataset.item;
            });

            document.getElementById('ygSortCats').addEventListener('click', function(e) {
                var cat = e.target.closest('.yg-sort-category');
                if (!cat || !state.activeItem) return;
                var catName = cat.dataset.cat;

                /* Check if correct category */
                var correctCat = '';
                for (var j = 0; j < items.length; j++) {
                    if (items[j].text === state.activeItem) { correctCat = items[j].category; break; }
                }

                var itemEl = document.querySelector('.yg-sort-item[data-item="' + state.activeItem + '"]');

                if (catName === correctCat) {
                    state.placements[state.activeItem] = catName;
                    if (itemEl) { itemEl.classList.add('sorted'); itemEl.classList.remove('active-item'); }

                    var chipContainer = cat.querySelector('.yg-sort-cat-items');
                    var chip = document.createElement('span');
                    chip.className = 'yg-sorted-chip';
                    chip.textContent = state.activeItem;
                    chipContainer.appendChild(chip);

                    WorldReaction.harmony(container, chip);
                    state.activeItem = null;

                    if (Object.keys(state.placements).length === items.length) {
                        setTimeout(function() { onComplete(true); }, CONFIG.harmonyDuration);
                    }
                } else {
                    WorldReaction.desequilibrio(container, itemEl, function() {
                        if (itemEl) itemEl.classList.remove('active-item');
                        state.activeItem = null;
                    });
                }
            });
        }
    };

    /* ----- SENTENCE BUILDER (StoryWeaving) ----- */
    Renderers.builder = {
        render: function(data, container, state, onComplete) {
            if (data._multiBuilder && data._multiBuilder.length > 1) {
                return this._renderMulti(data, container, state, onComplete);
            }

            state.built = state.built || [];
            state.usedIndices = state.usedIndices || {};
            if (!state._shuffled) {
                state._shuffled = shuffle(data.words.map(function(w, i) { return { word: w, origIdx: i }; }));
            }
            var shuffled = state._shuffled;

            var html = renderTypeLabel(data.label || 'Construir', 'builder') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            html += '<div class="yg-sb-target" id="ygSbTarget">';
            if (state.built.length === 0) {
                html += '<span class="yg-sb-placeholder">Haz clic en las palabras para construir la frase...</span>';
            } else {
                for (var b = 0; b < state.built.length; b++) {
                    html += '<span class="yg-sb-chip in-target" data-built-idx="' + b + '">' + state.built[b] + '</span>';
                }
            }
            html += '</div>';

            html += '<div class="yg-sb-bank" id="ygSbBank">';
            for (var i = 0; i < shuffled.length; i++) {
                var used = state.usedIndices[i] ? ' used' : '';
                html += '<span class="yg-sb-chip' + used + '" data-bank-idx="' + i + '">' + shuffled[i].word + '</span>';
            }
            html += '</div>';
            html += '<button class="yg-sb-clear" id="ygSbClear">\u21BB Borrar</button> ';
            html += '<button class="yg-listo-btn" id="ygSbListo">Listo</button>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygSbBank').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sb-chip');
                if (!el || el.classList.contains('used')) return;
                var bIdx = parseInt(el.dataset.bankIdx);
                state.built.push(shuffled[bIdx].word);
                state.usedIndices[bIdx] = true;
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbTarget').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sb-chip.in-target');
                if (!el) return;
                var bltIdx = parseInt(el.dataset.builtIdx);
                var removedWord = state.built[bltIdx];
                state.built.splice(bltIdx, 1);
                for (var k = 0; k < shuffled.length; k++) {
                    if (state.usedIndices[k] && shuffled[k].word === removedWord) {
                        delete state.usedIndices[k]; break;
                    }
                }
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbClear').addEventListener('click', function() {
                state.built = [];
                state.usedIndices = {};
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbListo').addEventListener('click', function() {
                if (state.built.length === 0) return;
                var userSentence = normalize(state.built.join(' '));
                var correct = normalize(data.answer);
                var target = document.getElementById('ygSbTarget');

                if (userSentence === correct) {
                    WorldReaction.harmony(container, target, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, target, function() {});
                }
            });
        },

        _renderMulti: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            var sentences = data._multiBuilder;
            var s = sentences[state.subIdx];
            var subData = { label: data.label, instruction: data.instruction || data.title, words: s.words, answer: s.answer || s.correct };
            var subState = { built: [], usedIndices: {} };
            var self = this;
            this.render(subData, container, subState, function() {
                if (state.subIdx < sentences.length - 1) {
                    state.subIdx++;
                    self._renderMulti(data, container, state, onComplete);
                } else {
                    onComplete(true);
                }
            });
        }
    };

    /* ----- TRANSLATION (StoryWeaving) ----- */
    Renderers.translation = {
        render: function(data, container, state, onComplete) {
            if (data._multiTranslation && data._multiTranslation.length > 1) {
                return this._renderMulti(data, container, state, onComplete);
            }

            state.built = state.built || [];
            state.usedIndices = state.usedIndices || {};
            if (!state._shuffled) {
                state._shuffled = shuffle(data.words.map(function(w, i) { return { word: w, origIdx: i }; }));
            }
            var shuffled = state._shuffled;

            var html = renderTypeLabel(data.label || 'Traducir', 'translation') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-trans-source">' + data.source + '</div>';

            html += '<div class="yg-sb-target" id="ygSbTarget">';
            if (state.built.length === 0) {
                html += '<span class="yg-sb-placeholder">Haz clic en las palabras para traducir...</span>';
            } else {
                for (var b = 0; b < state.built.length; b++) {
                    html += '<span class="yg-sb-chip in-target" data-built-idx="' + b + '">' + state.built[b] + '</span>';
                }
            }
            html += '</div>';

            html += '<div class="yg-sb-bank" id="ygSbBank">';
            for (var i = 0; i < shuffled.length; i++) {
                var used = state.usedIndices[i] ? ' used' : '';
                html += '<span class="yg-sb-chip' + used + '" data-bank-idx="' + i + '">' + shuffled[i].word + '</span>';
            }
            html += '</div>';
            html += '<button class="yg-sb-clear" id="ygSbClear">\u21BB Borrar</button> ';
            html += '<button class="yg-listo-btn" id="ygSbListo">Listo</button>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygSbBank').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sb-chip');
                if (!el || el.classList.contains('used')) return;
                var bIdx = parseInt(el.dataset.bankIdx);
                state.built.push(shuffled[bIdx].word);
                state.usedIndices[bIdx] = true;
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbTarget').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sb-chip.in-target');
                if (!el) return;
                var bltIdx = parseInt(el.dataset.builtIdx);
                var removedWord = state.built[bltIdx];
                state.built.splice(bltIdx, 1);
                for (var k = 0; k < shuffled.length; k++) {
                    if (state.usedIndices[k] && shuffled[k].word === removedWord) {
                        delete state.usedIndices[k]; break;
                    }
                }
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbClear').addEventListener('click', function() {
                state.built = [];
                state.usedIndices = {};
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygSbListo').addEventListener('click', function() {
                if (state.built.length === 0) return;
                var userSentence = normalize(state.built.join(' '));
                var correct = normalize(data.answer);
                var target = document.getElementById('ygSbTarget');

                if (userSentence === correct) {
                    WorldReaction.harmony(container, target, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, target, function() {});
                }
            });
        },

        _renderMulti: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            var sentences = data._multiTranslation;
            var s = sentences[state.subIdx];
            var subData = { label: data.label, instruction: data.instruction || data.title, source: s.english || s.source, words: s.words, answer: s.answer || s.correct };
            var subState = { built: [], usedIndices: {} };
            var self = this;
            this.render(subData, container, subState, function() {
                if (state.subIdx < sentences.length - 1) {
                    state.subIdx++;
                    self._renderMulti(data, container, state, onComplete);
                } else {
                    onComplete(true);
                }
            });
        }
    };

    /* ----- CONVERSATION (DialogueLivingWorld) ----- */
    Renderers.conversation = {
        render: function(data, container, state, onComplete) {
            state.turnIdx = state.turnIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;
            var turn = data.turns[state.turnIdx];

            var html = renderTypeLabel(data.label || 'Conversaci\u00f3n', 'conversation') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            if (data.turns.length > 1) {
                html += '<div class="yg-sub-counter">Turno ' + (state.turnIdx + 1) + ' de ' + data.turns.length + '</div>';
            }

            html += '<div class="yg-conv-dialogue">';
            for (var d = 0; d < turn.dialogue.length; d++) {
                var line = turn.dialogue[d];
                if (line.text === '???') {
                    html += '<div class="yg-conv-bubble mystery speaker-b"><div class="yg-conv-speaker">' + (line.name || 'T\u00fa') + '</div>???</div>';
                } else {
                    html += '<div class="yg-conv-bubble speaker-' + line.speaker.toLowerCase() + '"><div class="yg-conv-speaker">' + (line.name || line.speaker) + '</div>' + line.text + '</div>';
                }
            }
            html += '</div>';

            html += '<div class="yg-conv-options" id="ygConvOptions">';
            for (var i = 0; i < turn.options.length; i++) {
                html += '<div class="yg-conv-option" data-val="' + turn.options[i] + '">' + turn.options[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygConvOptions').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-conv-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                state.answers[state.turnIdx] = el.dataset.val;

                var all = document.querySelectorAll('.yg-conv-option');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                /* Replace mystery bubble with answer */
                var mystery = document.querySelector('.yg-conv-bubble.mystery');
                if (mystery) {
                    mystery.textContent = el.dataset.val;
                    mystery.classList.remove('mystery');
                    mystery.classList.add('speaker-b');
                }

                var isCorrect = normalize(el.dataset.val) === normalize(turn.answer);
                if (isCorrect) {
                    WorldReaction.harmony(container, el, function() {
                        self._advance(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.resolved = false;
                        if (mystery) {
                            mystery.textContent = '???';
                            mystery.classList.add('mystery');
                            mystery.classList.remove('speaker-b');
                        }
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                    });
                }
            });
        },

        _advance: function(data, container, state, onComplete) {
            if (state.turnIdx < data.turns.length - 1) {
                state.turnIdx++;
                state.resolved = false;
                this.render(data, container, state, onComplete);
            } else {
                var correctCount = 0;
                for (var i = 0; i < data.turns.length; i++) {
                    if (normalize(state.answers[i]) === normalize(data.turns[i].answer)) correctCount++;
                }
                onComplete(correctCount >= Math.ceil(data.turns.length / 2));
            }
        }
    };

    /* ----- DICTATION (NamingRitual) ----- */
    Renderers.dictation = {
        render: function(data, container, state, onComplete) {
            if (data._multiDictation && data._multiDictation.length > 1) {
                return this._renderMulti(data, container, state, onComplete);
            }
            state.typed = state.typed || '';
            state.resolved = false;

            var html = renderTypeLabel(data.label || 'Dictado', 'dictation') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-listen-area">' +
                           '<button class="yg-listen-btn" id="ygListenBtn">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn">' + ICONS.repeat + '</button>' +
                       '</div>' +
                       '<input type="text" class="yg-dict-input" id="ygDictInput" placeholder="Escribe lo que oyes aqu\u00ed..." value="' + (state.typed || '') + '" autocomplete="off" autocapitalize="sentences">';
            if (data.hint) html += '<div class="yg-dict-hint">' + data.hint + '</div>';
            html += '<button class="yg-listo-btn" id="ygDictListo">Listo</button>';
            container.innerHTML = html;

            document.getElementById('ygListenBtn').addEventListener('click', function() { Audio.speak(data.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { Audio.speak(data.audio); });
            setTimeout(function() { Audio.speak(data.audio); }, 400);

            var input = document.getElementById('ygDictInput');
            input.addEventListener('input', function() { state.typed = input.value; });

            document.getElementById('ygDictListo').addEventListener('click', function() {
                if (state.resolved) return;
                var val = (input.value || '').trim();
                if (!val) return;
                state.resolved = true;
                state.typed = val;

                var isCorrect = normalize(val) === normalize(data.answer);
                if (isCorrect) {
                    WorldReaction.harmony(container, input, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, input, function() {
                        state.resolved = false;
                    });
                }
            });
        },

        _renderMulti: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            var sentences = data._multiDictation;
            var s = sentences[state.subIdx];
            var subData = { label: data.label, instruction: data.instruction || data.title, audio: s.audio || s.text, answer: s.answer || s.text, hint: s.hint };
            var subState = { typed: '' };
            var self = this;
            this.render(subData, container, subState, function() {
                if (state.subIdx < sentences.length - 1) {
                    state.subIdx++;
                    self._renderMulti(data, container, state, onComplete);
                } else {
                    onComplete(true);
                }
            });
        }
    };

    /* ----- STORY COMPREHENSION (SymbolInterpretation) ----- */
    Renderers.story = {
        render: function(data, container, state, onComplete) {
            state.answers = state.answers || {};
            state.answeredCount = 0;
            for (var k in state.answers) { if (state.answers.hasOwnProperty(k)) state.answeredCount++; }

            var html = renderTypeLabel(data.label || 'Lectura', 'story') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            if (data.title) {
                html += '<div class="yg-story-title">' + data.title + '</div>';
            }

            html += '<div class="yg-story-text">' + data.text + '</div>';

            for (var q = 0; q < data.questions.length; q++) {
                var quest = data.questions[q];
                html += '<div class="yg-story-question" data-qidx="' + q + '">' +
                            '<div class="yg-story-q-text">' + (q + 1) + '. ' + quest.q + '</div>' +
                            '<div class="yg-story-q-options">';
                for (var o = 0; o < quest.options.length; o++) {
                    var sel = (state.answers[q] === quest.options[o]) ? ' selected' : '';
                    var disabled = (state.answers[q] !== undefined) ? ' style="pointer-events:none"' : '';
                    html += '<div class="yg-story-q-option' + sel + '" data-qidx="' + q + '" data-val="' + quest.options[o] + '"' + disabled + '>' + quest.options[o] + '</div>';
                }
                html += '</div></div>';
            }

            container.innerHTML = html;

            var self = this;
            container.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-story-q-option');
                if (!el) return;
                var qIdx = parseInt(el.dataset.qidx);
                if (state.answers[qIdx] !== undefined) return;

                var quest = data.questions[qIdx];
                state.answers[qIdx] = el.dataset.val;

                var isCorrect = normalize(el.dataset.val) === normalize(quest.answer);
                var parentQ = el.closest('.yg-story-question');
                var siblings = parentQ.querySelectorAll('.yg-story-q-option');
                for (var j = 0; j < siblings.length; j++) siblings[j].style.pointerEvents = 'none';

                if (isCorrect) {
                    WorldReaction.harmony(container, el);
                } else {
                    /* On incorrect, allow retry for this question */
                    WorldReaction.desequilibrio(container, el, function() {
                        delete state.answers[qIdx];
                        for (var j = 0; j < siblings.length; j++) {
                            siblings[j].style.pointerEvents = '';
                            siblings[j].classList.remove('selected');
                        }
                    });
                    return;
                }

                /* Check if all answered */
                var answeredAll = true;
                for (var i = 0; i < data.questions.length; i++) {
                    if (state.answers[i] === undefined) { answeredAll = false; break; }
                }

                if (answeredAll) {
                    var correctCount = 0;
                    for (var i = 0; i < data.questions.length; i++) {
                        if (normalize(state.answers[i]) === normalize(data.questions[i].answer)) correctCount++;
                    }
                    setTimeout(function() {
                        onComplete(correctCount >= Math.ceil(data.questions.length * 0.5));
                    }, CONFIG.harmonyDuration);
                }
            });
        }
    };

    /* ----- NARRATIVE (Yaguará intros, grammar teaching, cosmic positioning) ----- */
    Renderers.narrative = {
        render: function(data, container, state, onComplete) {
            var html = '';

            /* Type label badge */
            var labelText = data.label || 'Yaguará';
            html += renderTypeLabel(labelText, 'narrative', 'yg-type-narrative');

            /* Title */
            if (data.title) {
                html += '<div class="yg-narrative-title">' + data.title + '</div>';
            }

            /* Speaker avatar — if Yaguará is speaking */
            var speaker = data.speaker || 'yaguara';
            if (speaker === 'yaguara') {
                html += '<div class="yg-narrative-speaker">' +
                    '<div class="yg-narrative-avatar">🐆</div>' +
                    '<div class="yg-narrative-name">Yaguará</div>' +
                '</div>';
            }

            /* Sections — rich content blocks */
            var sections = data.sections || [];
            for (var i = 0; i < sections.length; i++) {
                var sec = sections[i];
                html += '<div class="yg-narrative-section">';

                if (sec.heading) {
                    html += '<div class="yg-narrative-heading">' + sec.heading + '</div>';
                }

                if (sec.body) {
                    html += '<div class="yg-narrative-body">' + sec.body + '</div>';
                }

                /* Pattern highlight — for grammar teaching */
                if (sec.pattern) {
                    html += '<div class="yg-narrative-pattern">' + sec.pattern + '</div>';
                }

                /* Examples — for grammar teaching */
                if (sec.examples && sec.examples.length) {
                    html += '<div class="yg-narrative-examples">';
                    for (var j = 0; j < sec.examples.length; j++) {
                        var ex = sec.examples[j];
                        if (typeof ex === 'string') {
                            html += '<div class="yg-narrative-example">' + ex + '</div>';
                        } else {
                            html += '<div class="yg-narrative-example">' +
                                '<span class="yg-narrative-es">' + (ex.es || '') + '</span>';
                            if (ex.gloss) {
                                html += '<span class="yg-narrative-gloss">' + ex.gloss + '</span>';
                            }
                            html += '</div>';
                        }
                    }
                    html += '</div>';
                }

                html += '</div>';
            }

            /* Continue button */
            html += '<button class="yg-listo-btn yg-narrative-btn" id="ygNarrativeContinue">' + (data.button || 'Continuar') + '</button>';

            container.innerHTML = html;

            /* Animate sections in sequence */
            var sectionEls = container.querySelectorAll('.yg-narrative-section');
            for (var k = 0; k < sectionEls.length; k++) {
                sectionEls[k].style.animationDelay = (0.2 + k * 0.15) + 's';
            }

            /* Continue button — always succeeds (no right/wrong) */
            document.getElementById('ygNarrativeContinue').addEventListener('click', function() {
                onComplete(true);
            });
        }
    };

    /* ----- CANCIÓN (Song Lyrics) ----- */
    Renderers.cancion = {
        render: function(data, container, state, onComplete) {
            state.filled = state.filled || {};
            state.resolved = false;

            /* Count blanks */
            var blanks = [];
            for (var i = 0; i < data.lines.length; i++) {
                if (data.lines[i].blank !== undefined) blanks.push(i);
            }

            var html = renderTypeLabel(data.label || 'Canción', 'cancion') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-cancion-layout">';

            /* YouTube embed */
            html += '<div class="yg-cancion-video">' +
                        '<div class="yg-cancion-video-wrap">' +
                            '<iframe src="https://www.youtube-nocookie.com/embed/' + (data.youtubeId || '') + '?rel=0&modestbranding=1" ' +
                                'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope" ' +
                                'allowfullscreen></iframe>' +
                        '</div>' +
                    '</div>';

            /* Lyrics panel */
            html += '<div class="yg-cancion-lyrics">';

            /* Artist / song title meta */
            if (data.artist || data.songTitle) {
                html += '<div class="yg-cancion-meta">';
                if (data.songTitle) html += '<span class="yg-cancion-song-title">' + data.songTitle + '</span>';
                if (data.artist) html += '<span class="yg-cancion-artist">' + data.artist + '</span>';
                html += '</div>';
            }

            /* Lyric lines */
            for (var li = 0; li < data.lines.length; li++) {
                var line = data.lines[li];
                html += '<div class="yg-cancion-line">';

                if (line.blank !== undefined) {
                    /* Line with a blank */
                    var parts = line.text.split('___');
                    html += '<span>' + parts[0] + '</span>';

                    if (line.options && line.options.length) {
                        /* Dropdown (A1–A2 scaffolding) */
                        html += '<select class="yg-cancion-blank" data-idx="' + li + '">';
                        html += '<option value="">...</option>';
                        var opts = shuffle(line.options.slice());
                        for (var oi = 0; oi < opts.length; oi++) {
                            var sel = (state.filled[li] === opts[oi]) ? ' selected' : '';
                            html += '<option value="' + opts[oi] + '"' + sel + '>' + opts[oi] + '</option>';
                        }
                        html += '</select>';
                    } else {
                        /* Free text input (B1+) */
                        html += '<input type="text" class="yg-cancion-blank" data-idx="' + li + '" ' +
                                'placeholder="..." value="' + (state.filled[li] || '') + '" ' +
                                'autocomplete="off" autocapitalize="sentences">';
                    }

                    html += '<span>' + (parts[1] || '') + '</span>';
                } else {
                    /* Plain lyric line */
                    html += line.text;
                }

                html += '</div>';
            }

            html += '</div>'; /* end lyrics */
            html += '</div>'; /* end layout */

            /* Listo button */
            html += '<button class="yg-listo-btn" id="ygCancionListo" style="margin-top:16px">Listo</button>';

            container.innerHTML = html;

            /* Bind input/select changes to state */
            var blankEls = container.querySelectorAll('.yg-cancion-blank');
            for (var bi = 0; bi < blankEls.length; bi++) {
                (function(el) {
                    var idx = el.dataset.idx;
                    var evt = el.tagName === 'SELECT' ? 'change' : 'input';
                    el.addEventListener(evt, function() {
                        state.filled[idx] = el.value;
                    });
                })(blankEls[bi]);
            }

            /* Submit handler */
            var self = this;
            document.getElementById('ygCancionListo').addEventListener('click', function() {
                if (state.resolved) return;
                self._check(data, container, state, onComplete, blanks);
            });
        },

        _check: function(data, container, state, onComplete, blanks) {
            var allCorrect = true;
            var anyFilled = false;

            for (var bi = 0; bi < blanks.length; bi++) {
                var idx = blanks[bi];
                var line = data.lines[idx];
                var el = container.querySelector('.yg-cancion-blank[data-idx="' + idx + '"]');
                if (!el) continue;

                var userVal = (el.value || '').trim();
                if (userVal) anyFilled = true;

                var isCorrect = normalize(userVal) === normalize(line.blank);

                if (isCorrect) {
                    el.classList.remove('incorrect');
                    el.classList.add('correct');
                    if (el.tagName === 'INPUT') el.disabled = true;
                    if (el.tagName === 'SELECT') el.disabled = true;
                    WorldReaction.harmony(container, el);
                } else {
                    allCorrect = false;
                    if (userVal) {
                        el.classList.add('incorrect');
                        WorldReaction.desequilibrio(container, el);
                    }
                }
            }

            if (!anyFilled) return; /* Nothing entered yet */

            if (allCorrect) {
                state.resolved = true;
                setTimeout(function() { onComplete(true); }, CONFIG.harmonyDuration + 200);
            }
            /* If not all correct, blanks remain editable for retry */
        }
    };

    /* ----- ESCAPE ROOM (Las puertas de la memoria) ----- */
    Renderers.escaperoom = {
        render: function(data, container, state, onComplete) {
            state.puzzleIdx = state.puzzleIdx || 0;
            state.solved = state.solved || {};
            state.resolved = false;
            data._escapeState = state;

            /* If all puzzles solved, show fragment */
            if (state.puzzleIdx >= data.puzzles.length) {
                this._showFragment(data, container, state, onComplete);
                return;
            }

            var html = this._buildRoomChrome(data, state);
            html += this._buildPuzzle(data.puzzles[state.puzzleIdx], state.puzzleIdx);
            container.innerHTML = html;
            this._bindPuzzle(data, container, state, onComplete);
        },

        _buildRoomChrome: function(data, state) {
            var html = renderTypeLabel(data.label || 'Sala de enigmas', 'escaperoom', 'yg-type-escaperoom');
            html += '<div class="yg-escape-room-header">';
            html += '<div class="yg-escape-room-name">' + data.room.name + '</div>';
            if (data.room.description) {
                html += '<div class="yg-escape-room-desc">' + data.room.description + '</div>';
            }
            html += '</div>';

            /* Lock progress indicators */
            html += '<div class="yg-escape-locks">';
            for (var i = 0; i < data.puzzles.length; i++) {
                var cls = 'yg-escape-lock';
                if (state.solved[i]) cls += ' yg-lock-open';
                else if (i === state.puzzleIdx) cls += ' yg-lock-active';
                else cls += ' yg-lock-closed';
                html += '<div class="' + cls + '">' + (state.solved[i] ? '&#10003;' : (i + 1)) + '</div>';
            }
            html += '</div>';
            return html;
        },

        _buildPuzzle: function(puzzle, idx) {
            var html = '<div class="yg-escape-puzzle" data-pidx="' + idx + '">';
            html += '<div class="yg-escape-prompt">' + puzzle.prompt + '</div>';
            if (puzzle.clue) html += '<div class="yg-escape-clue">' + puzzle.clue + '</div>';

            switch (puzzle.puzzleType) {
                case 'wordlock': html += this._buildWordlock(puzzle); break;
                case 'cipher': html += this._buildCipher(puzzle); break;
                case 'riddle': html += this._buildRiddle(puzzle); break;
                case 'sequence': html += this._buildSequence(puzzle); break;
                case 'extract': html += this._buildExtract(puzzle); break;
                case 'logic': html += this._buildLogic(puzzle); break;
                case 'synthesis': html += this._buildSynthesis(puzzle); break;
                default: html += this._buildWordlock(puzzle); break;
            }

            html += '</div>';
            return html;
        },

        _buildWordlock: function(puzzle) {
            var html = '';
            if (puzzle.hint) html += '<div class="yg-escape-hint">' + puzzle.hint + '</div>';
            html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la respuesta..." autocomplete="off" autocapitalize="none">';
            html += '<button class="yg-listo-btn" id="ygEscapeListo">Abrir</button>';
            return html;
        },

        _buildCipher: function(puzzle) {
            var letters = (puzzle.scrambled || '').split('');
            if (!letters.length && puzzle.answer) {
                /* Auto-scramble from answer */
                letters = shuffle(puzzle.answer.split(''));
            }
            var html = '<div class="yg-escape-tiles" id="ygEscapeTiles">';
            for (var i = 0; i < letters.length; i++) {
                html += '<div class="yg-sb-chip yg-escape-tile" data-letter="' + letters[i] + '">' + letters[i] + '</div>';
            }
            html += '</div>';
            html += '<div class="yg-escape-built" id="ygEscapeBuilt"></div>';
            html += '<div class="yg-escape-cipher-actions">';
            html += '<button class="yg-listo-btn yg-escape-undo" id="ygEscapeUndo">Borrar</button>';
            html += '<button class="yg-listo-btn" id="ygEscapeListo">Listo</button>';
            html += '</div>';
            return html;
        },

        _buildRiddle: function(puzzle) {
            var html = '';
            if (puzzle.riddle) html += '<div class="yg-escape-riddle">' + puzzle.riddle + '</div>';
            html += '<div class="yg-fib-options" id="ygEscapeOptions">';
            var opts = shuffle(puzzle.options.slice());
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            return html;
        },

        _buildSequence: function(puzzle) {
            var items = shuffle(puzzle.items.slice());
            var html = '<div class="yg-escape-sequence-pool" id="ygEscapePool">';
            for (var i = 0; i < items.length; i++) {
                html += '<div class="yg-sb-chip yg-escape-seq-item" data-val="' + items[i] + '">' + items[i] + '</div>';
            }
            html += '</div>';
            html += '<div class="yg-escape-sequence-slots" id="ygEscapeSlots">';
            for (var i = 0; i < puzzle.items.length; i++) {
                html += '<div class="yg-escape-seq-slot" data-slot="' + i + '">' + (i + 1) + '</div>';
            }
            html += '</div>';
            html += '<button class="yg-listo-btn" id="ygEscapeListo">Listo</button>';
            return html;
        },

        _buildExtract: function(puzzle) {
            var html = '';
            if (puzzle.passage) html += '<div class="yg-escape-passage yg-story-text">' + puzzle.passage + '</div>';
            if (puzzle.options && puzzle.options.length) {
                html += '<div class="yg-fib-options" id="ygEscapeOptions">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la respuesta..." autocomplete="off" autocapitalize="sentences">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo">Listo</button>';
            }
            return html;
        },

        _buildLogic: function(puzzle) {
            var html = '';
            if (puzzle.premises && puzzle.premises.length) {
                html += '<div class="yg-escape-premises">';
                for (var i = 0; i < puzzle.premises.length; i++) {
                    html += '<div class="yg-escape-premise">' + puzzle.premises[i] + '</div>';
                }
                html += '</div>';
            }
            if (puzzle.options && puzzle.options.length) {
                html += '<div class="yg-fib-options" id="ygEscapeOptions">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la conclusión..." autocomplete="off" autocapitalize="sentences">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo">Listo</button>';
            }
            return html;
        },

        _buildSynthesis: function(puzzle) {
            var html = '';
            if (puzzle.sources && puzzle.sources.length) {
                html += '<div class="yg-escape-sources">';
                for (var i = 0; i < puzzle.sources.length; i++) {
                    html += '<div class="yg-escape-source yg-story-text">' + puzzle.sources[i] + '</div>';
                }
                html += '</div>';
            }
            if (puzzle.options && puzzle.options.length) {
                html += '<div class="yg-fib-options" id="ygEscapeOptions">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe tu síntesis..." autocomplete="off" autocapitalize="sentences">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo">Listo</button>';
            }
            return html;
        },

        _bindPuzzle: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            var self = this;

            switch (puzzle.puzzleType) {
                case 'cipher':
                    this._bindCipher(data, container, state, onComplete);
                    break;
                case 'riddle':
                case 'extract':
                case 'logic':
                case 'synthesis':
                    if (puzzle.options && puzzle.options.length) {
                        this._bindOptions(data, container, state, onComplete);
                    } else {
                        this._bindTextInput(data, container, state, onComplete);
                    }
                    break;
                case 'sequence':
                    this._bindSequence(data, container, state, onComplete);
                    break;
                default: /* wordlock + fallback */
                    this._bindTextInput(data, container, state, onComplete);
                    break;
            }
        },

        _bindTextInput: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            var self = this;
            var btn = document.getElementById('ygEscapeListo');
            var input = document.getElementById('ygEscapeInput');
            if (!btn || !input) return;

            btn.addEventListener('click', function() {
                if (state.resolved) return;
                var val = (input.value || '').trim();
                if (!val) return;

                var isCorrect = normalize(val) === normalize(puzzle.answer);
                if (isCorrect) {
                    state.resolved = true;
                    WorldReaction.harmony(container, input, function() {
                        self._onPuzzleSolved(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, input);
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') btn.click();
            });
        },

        _bindOptions: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            var self = this;
            var optContainer = document.getElementById('ygEscapeOptions');
            if (!optContainer) return;

            optContainer.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-escape-option');
                if (!el || state.resolved) return;

                var isCorrect = normalize(el.dataset.val) === normalize(puzzle.answer);
                if (isCorrect) {
                    state.resolved = true;
                    var all = optContainer.querySelectorAll('.yg-escape-option');
                    for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';
                    WorldReaction.harmony(container, el, function() {
                        self._onPuzzleSolved(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, el);
                }
            });
        },

        _bindCipher: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            var self = this;
            var tilesEl = document.getElementById('ygEscapeTiles');
            var builtEl = document.getElementById('ygEscapeBuilt');
            var undoBtn = document.getElementById('ygEscapeUndo');
            var listoBtn = document.getElementById('ygEscapeListo');
            if (!tilesEl || !builtEl) return;

            var built = [];

            tilesEl.addEventListener('click', function(e) {
                var tile = e.target.closest('.yg-escape-tile');
                if (!tile || tile.classList.contains('used') || state.resolved) return;
                tile.classList.add('used');
                built.push({ letter: tile.dataset.letter, el: tile });
                builtEl.textContent = built.map(function(b) { return b.letter; }).join('');
            });

            undoBtn.addEventListener('click', function() {
                if (state.resolved || !built.length) return;
                var last = built.pop();
                last.el.classList.remove('used');
                builtEl.textContent = built.map(function(b) { return b.letter; }).join('');
            });

            listoBtn.addEventListener('click', function() {
                if (state.resolved) return;
                var val = built.map(function(b) { return b.letter; }).join('');
                if (!val) return;

                var isCorrect = normalize(val) === normalize(puzzle.answer);
                if (isCorrect) {
                    state.resolved = true;
                    WorldReaction.harmony(container, builtEl, function() {
                        self._onPuzzleSolved(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, builtEl, function() {
                        /* Reset tiles */
                        for (var i = 0; i < built.length; i++) built[i].el.classList.remove('used');
                        built = [];
                        builtEl.textContent = '';
                    });
                }
            });
        },

        _bindSequence: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            var self = this;
            var pool = document.getElementById('ygEscapePool');
            var slotsEl = document.getElementById('ygEscapeSlots');
            var listoBtn = document.getElementById('ygEscapeListo');
            if (!pool || !slotsEl) return;

            var placed = [];
            var nextSlot = 0;

            pool.addEventListener('click', function(e) {
                var item = e.target.closest('.yg-escape-seq-item');
                if (!item || item.classList.contains('used') || state.resolved) return;
                if (nextSlot >= puzzle.items.length) return;

                item.classList.add('used');
                placed.push(item.dataset.val);
                var slot = slotsEl.querySelector('[data-slot="' + nextSlot + '"]');
                if (slot) slot.textContent = item.dataset.val;
                nextSlot++;
            });

            listoBtn.addEventListener('click', function() {
                if (state.resolved || placed.length < puzzle.items.length) return;

                var isCorrect = true;
                for (var i = 0; i < puzzle.items.length; i++) {
                    if (normalize(placed[i]) !== normalize(puzzle.items[i])) {
                        isCorrect = false;
                        break;
                    }
                }

                if (isCorrect) {
                    state.resolved = true;
                    WorldReaction.harmony(container, slotsEl, function() {
                        self._onPuzzleSolved(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, slotsEl, function() {
                        /* Reset sequence */
                        placed = [];
                        nextSlot = 0;
                        var items = pool.querySelectorAll('.yg-escape-seq-item');
                        for (var i = 0; i < items.length; i++) items[i].classList.remove('used');
                        var slots = slotsEl.querySelectorAll('.yg-escape-seq-slot');
                        for (var i = 0; i < slots.length; i++) slots[i].textContent = (i + 1);
                    });
                }
            });
        },

        _onPuzzleSolved: function(data, container, state, onComplete) {
            var puzzle = data.puzzles[state.puzzleIdx];
            state.solved[state.puzzleIdx] = true;

            if (puzzle.onSolve) {
                /* Show transition text */
                var solvedDiv = document.createElement('div');
                solvedDiv.className = 'yg-escape-onsolved';
                solvedDiv.textContent = puzzle.onSolve;
                container.appendChild(solvedDiv);
            }

            var self = this;
            setTimeout(function() {
                state.puzzleIdx++;
                state.resolved = false;
                self.render(data, container, state, onComplete);
            }, puzzle.onSolve ? 1800 : 600);
        },

        _showFragment: function(data, container, state, onComplete) {
            var frag = data.fragment;
            var html = this._buildRoomChrome(data, state);
            html += '<div class="yg-escape-fragment">';
            html += '<div class="yg-escape-fragment-icon">&#10024;</div>';
            if (frag.text) html += '<div class="yg-escape-fragment-text">' + frag.text + '</div>';
            if (frag.questClue) html += '<div class="yg-escape-fragment-clue">' + frag.questClue + '</div>';
            html += '</div>';
            html += '<button class="yg-listo-btn yg-escape-continue" id="ygEscapeContinue">Continuar</button>';
            container.innerHTML = html;

            document.getElementById('ygEscapeContinue').addEventListener('click', function() {
                onComplete(true);
            });
        }
    };

    /* ==========================================================
       9. ENCOUNTER ORCHESTRATOR (5-beat rhythm)
    ========================================================== */
    function runEncounter(data, container, state, onComplete) {
        var wrapper = container.closest('.yg-encounter-wrapper') || container;

        /* Beat 1 — IMMERSION */
        container.style.opacity = '0';
        container.style.transform = 'translateY(16px)';
        container.innerHTML = '';

        setTimeout(function() {
            /* Beat 2 — INTENT */
            container.style.transition = 'all ' + CONFIG.transitionSpeed + 'ms cubic-bezier(0.23, 1, 0.32, 1)';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';

            /* Beat 3 — ACTION */
            setTimeout(function() {
                var renderer = Renderers[data.type];
                if (renderer) {
                    renderer.render(data, container, state, function(success) {
                        /* Beat 4 — REACTION is handled inside renderers via WorldReaction */
                        /* Beat 5 — REFLECTION */
                        setTimeout(function() {
                            onComplete(success);
                        }, CONFIG.reflectionPause);
                    });
                } else {
                    container.innerHTML = '<div class="yg-instruction">Tipo de encuentro no reconocido: ' + data.type + '</div>';
                }
            }, CONFIG.intentDuration);
        }, CONFIG.immersionDuration);
    }

    /* ==========================================================
       10. MAIN ENGINE
    ========================================================== */
    var Engine = {
        _games: [],
        _container: null,
        _progressContainer: null,
        _yaguaraPanel: null,
        _navEl: null,
        _currentIdx: 0,
        _gameStates: [],
        _completed: [],
        _config: {},
        _yaguaraCounter: 0,
        _yaguaraNextAt: 0,
        _timings: [],
        _vocabularyLog: [],
        _encounterStartTime: 0,
        _encounterAttempts: 0,

        init: function(opts) {
            this._config = opts || {};
            this._arcadeMode = !!opts.arcadeMode;
            this._container = opts.container || document.getElementById('yaguaraCard');
            this._progressContainer = opts.progressContainer || document.getElementById('yaguaraProgress');
            this._yaguaraPanel = opts.yaguaraPanel || document.getElementById('yaguaraPanel');

            /* Ontology resolver — optional. Enables linguisticTargetId in game data.
               When provided, conjugation games can reference ontology LTs by ID
               instead of embedding full verb tables. */
            Normalizer._resolver = opts.resolver || null;
            Normalizer._ontology = opts.ontology || null;

            /* Set speech rate from spiral */
            if (opts.speechRate) {
                Audio.setSpiralRate(opts.speechRate);
            } else if (opts.spiral && CONFIG.speechRate[opts.spiral]) {
                Audio.setSpiralRate(CONFIG.speechRate[opts.spiral]);
            }

            /* Normalize all games */
            var rawGames = opts.games || [];
            this._games = [];
            for (var i = 0; i < rawGames.length; i++) {
                this._games.push(Normalizer.normalizeGame(rawGames[i]));
            }

            this._gameStates = new Array(this._games.length);
            this._completed = new Array(this._games.length);
            this._timings = [];
            this._vocabularyLog = [];
            for (var i = 0; i < this._games.length; i++) {
                this._gameStates[i] = {};
                this._completed[i] = false;
            }

            this._yaguaraNextAt = Math.floor(Math.random() * (CONFIG.yaguaraMaxInterval - CONFIG.yaguaraMinInterval + 1)) + CONFIG.yaguaraMinInterval;

            this._migrateStorage();
            this._loadProgress();
            this._bindNav();
            this._injectResourcesMenu();

            /* Opening Yaguará interjection — skip in arcade mode */
            var self = this;
            if (this._arcadeMode) {
                this._loadEncounter(this._currentIdx);
            } else {
                Yaguara.interject(this._yaguaraPanel, this._config.world || '_default', function() {
                    self._loadEncounter(self._currentIdx);
                });
            }
        },

        _bindNav: function() {
            var self = this;
            var prevBtn = document.getElementById('ygBtnPrev');
            var nextBtn = document.getElementById('ygBtnNext');

            if (prevBtn) prevBtn.addEventListener('click', function() { self.previousEncounter(); });
            if (nextBtn) nextBtn.addEventListener('click', function() { self.nextEncounter(); });
        },

        _injectResourcesMenu: function() {
            var header = document.querySelector('.yg-header');
            if (!header || document.getElementById('ygResourcesBtn')) return;

            var btn = document.createElement('button');
            btn.className = 'yg-resources-btn';
            btn.id = 'ygResourcesBtn';
            btn.title = 'Recursos';
            btn.innerHTML = ICONS.story; /* open book icon */

            var dropdown = document.createElement('div');
            dropdown.className = 'yg-resources-dropdown';
            dropdown.id = 'ygResourcesDrop';
            dropdown.innerHTML =
                '<a href="/dictionary" target="_blank" class="yg-res-item">' +
                    ICONS.story +
                    '<span>Diccionario</span>' +
                '</a>' +
                '<div class="yg-res-divider"></div>' +
                '<a href="/elviajedeljaguar" target="_blank" class="yg-res-item">' +
                    ICONS.narrative +
                    '<span>Mi progreso</span>' +
                '</a>';

            btn.appendChild(dropdown);
            header.appendChild(btn);

            btn.addEventListener('click', function(e) {
                if (e.target.closest('.yg-res-item')) return; /* let links work */
                e.stopPropagation();
                var open = dropdown.classList.toggle('visible');
                btn.classList.toggle('open', open);
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#ygResourcesBtn')) {
                    dropdown.classList.remove('visible');
                    btn.classList.remove('open');
                }
            });
        },

        _updateUI: function() {
            var idx = this._currentIdx;
            var total = this._games.length;

            Progress.render(this._progressContainer, idx + 1, total);

            var prevBtn = document.getElementById('ygBtnPrev');
            var nextBtn = document.getElementById('ygBtnNext');

            if (prevBtn) prevBtn.disabled = (idx === 0);
            if (nextBtn) nextBtn.disabled = !this._completed[idx];
        },

        _loadEncounter: function(index) {
            if (index < 0 || index >= this._games.length) return;
            this._currentIdx = index;
            var data = this._games[index];
            var state = this._gameStates[index];
            var self = this;

            this._updateUI();

            /* If already completed, re-render without orchestrator */
            if (this._completed[index]) {
                var renderer = Renderers[data.type];
                if (renderer) {
                    renderer.render(data, this._container, state, function() {});
                }
                return;
            }

            /* Start timing for this encounter */
            this._encounterStartTime = Date.now();
            this._encounterAttempts = 0;

            runEncounter(data, this._container, state, function(success) {
                self._onEncounterComplete(success);
            });
        },

        _extractVocabulary: function(data) {
            var vocab = [];
            if (data.vocabulary && Array.isArray(data.vocabulary)) return data.vocabulary;
            if (data.type === 'pair' && data.pairs) {
                for (var i = 0; i < data.pairs.length; i++) {
                    vocab.push(data.pairs[i][0]);
                    vocab.push(data.pairs[i][1]);
                }
            }
            if (data.type === 'fill' && data.questions) {
                for (var i = 0; i < data.questions.length; i++) {
                    if (data.questions[i].answer) vocab.push(data.questions[i].answer);
                }
            }
            if (data.type === 'conjugation') {
                if (data.lemma) vocab.push(data.lemma);
                if (data.questions) {
                    for (var i = 0; i < data.questions.length; i++) {
                        if (data.questions[i].verb) vocab.push(data.questions[i].verb);
                        if (data.questions[i].answer) vocab.push(data.questions[i].answer);
                    }
                }
            }
            if (data.type === 'category' && data.items) {
                for (var i = 0; i < data.items.length; i++) {
                    if (data.items[i].text) vocab.push(data.items[i].text);
                }
            }
            if (data.type === 'listening') {
                if (data._multiListening) {
                    for (var i = 0; i < data._multiListening.length; i++) {
                        if (data._multiListening[i].answer) vocab.push(data._multiListening[i].answer);
                    }
                } else if (data.answer) {
                    vocab.push(data.answer);
                }
            }
            if (data.type === 'builder' && data.words) {
                vocab = vocab.concat(data.words);
            }
            if (data.type === 'conversation' && data.turns) {
                for (var i = 0; i < data.turns.length; i++) {
                    if (data.turns[i].answer) vocab.push(data.turns[i].answer);
                }
            }
            if (data.type === 'escaperoom' && data.puzzles) {
                for (var i = 0; i < data.puzzles.length; i++) {
                    if (data.puzzles[i].answer) vocab.push(data.puzzles[i].answer);
                }
            }
            /* Ontology-tagged: extract from resolved LinguisticTarget */
            if (data._ontologyTarget) {
                var ot = data._ontologyTarget;
                if (ot.category === 'verb' && ot.lexicalItem && ot.lexicalItem.lemma) {
                    if (vocab.indexOf(ot.lexicalItem.lemma) === -1) vocab.push(ot.lexicalItem.lemma);
                }
                if (ot.category === 'vocab' && ot.lexicalItem && ot.lexicalItem.items) {
                    for (var vi = 0; vi < ot.lexicalItem.items.length; vi++) {
                        if (vocab.indexOf(ot.lexicalItem.items[vi]) === -1) vocab.push(ot.lexicalItem.items[vi]);
                    }
                }
            }
            return vocab;
        },

        _onEncounterComplete: function(success) {
            var idx = this._currentIdx;
            var data = this._games[idx];

            /* Record timing data */
            var responseTimeMs = this._encounterStartTime ? (Date.now() - this._encounterStartTime) : 0;
            this._timings.push({
                encounterIdx: idx,
                gameType: data.type || 'unknown',
                responseTimeMs: responseTimeMs,
                attemptCount: this._encounterAttempts || 1,
                timestamp: new Date().toISOString()
            });

            /* Extract and log vocabulary */
            var vocab = this._extractVocabulary(data);
            if (vocab.length > 0) {
                this._vocabularyLog.push({
                    encounterIdx: idx,
                    gameType: data.type || 'unknown',
                    words: vocab,
                    success: !!success,
                    timestamp: new Date().toISOString()
                });
            }

            this._completed[idx] = true;
            this._yaguaraCounter++;
            this._saveProgress();
            this._updateUI();

            /* Yaguará interjection every 3-5 encounters — skip in arcade mode */
            var self = this;
            if (!this._arcadeMode && this._yaguaraCounter >= this._yaguaraNextAt) {
                this._yaguaraCounter = 0;
                this._yaguaraNextAt = Math.floor(Math.random() * (CONFIG.yaguaraMaxInterval - CONFIG.yaguaraMinInterval + 1)) + CONFIG.yaguaraMinInterval;
                Yaguara.interject(this._yaguaraPanel, this._config.world || '_default', function() {
                    self._maybeAutoAdvance();
                });
            } else {
                this._maybeAutoAdvance();
            }
        },

        _maybeAutoAdvance: function() {
            var idx = this._currentIdx;
            if (idx === this._games.length - 1) {
                var self = this;
                setTimeout(function() { self._showWorldRestored(); }, 800);
            } else {
                var self = this;
                setTimeout(function() {
                    self.nextEncounter();
                }, 500);
            }
        },

        nextEncounter: function() {
            if (this._currentIdx < this._games.length - 1) {
                this._loadEncounter(this._currentIdx + 1);
            }
        },

        previousEncounter: function() {
            if (this._currentIdx > 0) {
                this._loadEncounter(this._currentIdx - 1);
            }
        },

        _showWorldRestored: function() {
            var card = this._container;
            var seedDiv = document.createElement('div');
            seedDiv.id = 'ygCompletionSeed';

            card.innerHTML = '<div class="yg-completion" id="ygCompletion"></div>';
            var completionEl = document.getElementById('ygCompletion');

            Progress.renderCompletion(seedDiv);

            completionEl.innerHTML = '';
            completionEl.appendChild(seedDiv);

            var title = document.createElement('div');
            title.className = 'yg-completion-title';

            if (this._arcadeMode) {
                title.textContent = 'Sesi\u00f3n completa';
                completionEl.appendChild(title);

                var text = document.createElement('div');
                text.className = 'yg-completion-text';
                text.textContent = 'Has completado todos los juegos de esta sesi\u00f3n.';
                completionEl.appendChild(text);

                var playAgain = document.createElement('button');
                playAgain.className = 'yg-completion-link';
                playAgain.textContent = 'Jugar otro';
                playAgain.style.cursor = 'pointer';
                playAgain.style.background = 'none';
                playAgain.style.border = 'none';
                playAgain.style.font = 'inherit';
                playAgain.style.color = 'inherit';
                playAgain.style.textDecoration = 'underline';
                playAgain.addEventListener('click', function() {
                    if (window.FreePlayLoader) {
                        FreePlayLoader.showPicker();
                    } else {
                        window.location.reload();
                    }
                });
                completionEl.appendChild(playAgain);

                var journeyCta = document.createElement('a');
                journeyCta.className = 'yg-completion-link';
                journeyCta.href = '/elviajedeljaguar';
                journeyCta.textContent = 'Comienza el viaje \u2192';
                journeyCta.style.display = 'block';
                journeyCta.style.marginTop = '1rem';
                completionEl.appendChild(journeyCta);
            } else {
                title.textContent = 'El mundo ha sido restaurado';
                completionEl.appendChild(title);

                Yaguara.showClosing(this._yaguaraPanel, this._config.world || '_default');

                var text = document.createElement('div');
                text.className = 'yg-completion-text';
                text.textContent = 'Tu voz forma parte de este lugar. Cada palabra que nombraste dej\u00f3 una huella.';
                completionEl.appendChild(text);

                var link = document.createElement('a');
                link.className = 'yg-completion-link';
                link.href = this._config.backUrl || '#';
                link.textContent = 'Continuar el viaje \u2192';
                completionEl.appendChild(link);
            }

            /* Hide nav */
            var nav = document.getElementById('yaguaraNav');
            if (nav) nav.style.display = 'none';

            /* Update progress to full */
            Progress.render(this._progressContainer, this._games.length, this._games.length);
        },

        _saveProgress: function() {
            try {
                var key = this._config.storageKey || 'yaguaraProgress';
                var completed = 0;
                for (var i = 0; i < this._completed.length; i++) {
                    if (this._completed[i]) completed++;
                }
                var data = {
                    version: 3,
                    currentEncounter: this._currentIdx,
                    completedCount: completed,
                    completedMap: this._completed,
                    destinationId: this._config.destinationId,
                    timings: this._timings,
                    vocabulary: this._vocabularyLog,
                    timestamp: new Date().toISOString()
                };
                localStorage.setItem(key, JSON.stringify(data));
                // Sync to server if JaguarAPI available (skip in arcade mode)
                if (!this._arcadeMode && window.JaguarAPI && this._config.destinationId) {
                    JaguarAPI.syncDestinationProgress(
                        this._config.destinationId,
                        this._config.storageKey || 'yaguaraProgress'
                    );

                    // Sync escape room progress if current game is an escape room
                    var currentGame = this._games[this._currentIdx];
                    if (currentGame && currentGame.type === 'escaperoom' && currentGame._escapeState) {
                        var escState = currentGame._escapeState;
                        var puzzlesSolved = {};
                        var allSolved = true;
                        for (var p = 0; p < currentGame.puzzles.length; p++) {
                            puzzlesSolved[p] = !!escState.solved[p];
                            if (!escState.solved[p]) allSolved = false;
                        }
                        var fragItem = allSolved ? (currentGame.fragment || null) : null;
                        JaguarAPI.syncEscapeRoomProgress(
                            this._config.destinationId,
                            puzzlesSolved,
                            allSolved,
                            fragItem
                        );
                        // Also write to localStorage
                        try {
                            var erKey = 'jaguarEscapeRoomProgress';
                            var erRaw = localStorage.getItem(erKey);
                            var erData = erRaw ? JSON.parse(erRaw) : {};
                            erData[this._config.destinationId] = {
                                destinationId: this._config.destinationId,
                                puzzlesSolved: puzzlesSolved,
                                isComplete: allSolved,
                                fragmentItem: fragItem,
                                timestamp: new Date().toISOString()
                            };
                            localStorage.setItem(erKey, JSON.stringify(erData));
                        } catch(e2) { /* silent */ }
                    }

                    // Sync vocabulary encounter to glossary
                    var currentGameForVocab = this._games[this._currentIdx];
                    if (currentGameForVocab && JaguarAPI.recordVocabularyEncounter) {
                        var vocabWords = [];
                        var correctWords = [];

                        // Extract vocabulary from current game
                        if (currentGameForVocab.vocabulary) {
                            vocabWords = vocabWords.concat(currentGameForVocab.vocabulary);
                        }
                        if (currentGameForVocab.pairs) {
                            for (var vi = 0; vi < currentGameForVocab.pairs.length; vi++) {
                                if (Array.isArray(currentGameForVocab.pairs[vi]) && currentGameForVocab.pairs[vi][0]) {
                                    vocabWords.push(currentGameForVocab.pairs[vi][0]);
                                }
                            }
                        }
                        if (currentGameForVocab.words) {
                            vocabWords = vocabWords.concat(currentGameForVocab.words);
                        }

                        // If this encounter was completed, all words are "correct"
                        if (this._completed[this._currentIdx]) {
                            correctWords = vocabWords.slice();
                        }

                        if (vocabWords.length > 0) {
                            JaguarAPI.recordVocabularyEncounter(
                                vocabWords, correctWords, this._config.destinationId
                            );
                        }
                    }
                }
            } catch(e) { /* silent */ }
        },

        _loadProgress: function() {
            try {
                var key = this._config.storageKey || 'yaguaraProgress';
                var raw = localStorage.getItem(key);
                if (!raw) return;
                var data = JSON.parse(raw);
                if (data && (data.version === 2 || data.version === 3)) {
                    this._currentIdx = Math.min(data.currentEncounter || 0, this._games.length - 1);
                    if (data.completedMap) {
                        for (var i = 0; i < Math.min(data.completedMap.length, this._games.length); i++) {
                            this._completed[i] = !!data.completedMap[i];
                        }
                    } else if (data.completedCount) {
                        for (var i = 0; i < Math.min(data.completedCount, this._games.length); i++) {
                            this._completed[i] = true;
                        }
                    }
                    /* Restore growth tracking data from v3 */
                    if (data.version === 3) {
                        this._timings = data.timings || [];
                        this._vocabularyLog = data.vocabulary || [];
                    }
                    /* Start from first incomplete */
                    for (var i = 0; i < this._games.length; i++) {
                        if (!this._completed[i]) { this._currentIdx = i; break; }
                    }
                }
            } catch(e) { /* silent */ }
        },

        _migrateStorage: function() {
            try {
                /* Read old format keys */
                var oldKeys = ['jaguarA2BasicProgress', 'jaguarA2AdvancedProgress'];
                var newKey = this._config.storageKey || 'yaguaraProgress';

                if (localStorage.getItem(newKey)) return;

                for (var k = 0; k < oldKeys.length; k++) {
                    var raw = localStorage.getItem(oldKeys[k]);
                    if (!raw) continue;
                    var old = JSON.parse(raw);
                    if (!old) continue;

                    var destId = this._config.destinationId;
                    var dp = null;
                    if (old.gameState && old.gameState.destinationProgress && old.gameState.destinationProgress[destId]) {
                        dp = old.gameState.destinationProgress[destId];
                    }
                    if (old['dest' + destId]) {
                        dp = old['dest' + destId];
                    }
                    if (!dp) continue;

                    var completedCount = dp.completed || dp.current || dp.completedCount || 0;
                    if (completedCount > 0) {
                        var completedMap = new Array(this._games.length);
                        for (var i = 0; i < this._games.length; i++) {
                            completedMap[i] = i < completedCount;
                        }
                        var newData = {
                            version: 2,
                            currentEncounter: Math.min(completedCount, this._games.length - 1),
                            completedCount: completedCount,
                            completedMap: completedMap,
                            destinationId: destId,
                            timestamp: new Date().toISOString(),
                            migratedFrom: oldKeys[k]
                        };
                        localStorage.setItem(newKey, JSON.stringify(newData));
                        break;
                    }
                }
            } catch(e) { /* silent */ }
        }
    };

    /* ==========================================================
       PUBLIC API
    ========================================================== */
    window.YaguaraEngine = {
        init: function(opts) { Engine.init(opts); },
        getTimings: function() { return Engine._timings; },
        getVocabulary: function() { return Engine._vocabularyLog; },
        getGrowthData: function() {
            return {
                timings: Engine._timings,
                vocabulary: Engine._vocabularyLog,
                completed: Engine._completed,
                totalEncounters: Engine._games.length,
                destinationId: Engine._config.destinationId
            };
        },
        Audio: Audio,
        WorldReaction: WorldReaction,
        Progress: Progress,
        Yaguara: Yaguara,
        Renderers: Renderers,
        Normalizer: Normalizer,
        CONFIG: CONFIG,
        assertVerbShape: assertVerbShape,
        getForm: getForm,
        getResolver: function() { return Normalizer._resolver; },
        getOntology: function() { return Normalizer._ontology; }
    };

})();
