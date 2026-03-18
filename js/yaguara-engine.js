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
        yaguaraMaxInterval: 5,

        /* 369 Phase System — Tesla/Steiner rhythm
           Phase 3: Encounter (will/doing) — first contact with new material
           Phase 6: Elaboration (feeling/heart) — deepen through practice
           Phase 9: Integration (thinking/head) — synthesize and produce */
        phaseBreathDuration: 2400,
        phaseTransitionSpeed: 1200,
        phaseMap: {
            /* Phase 3 — Encounter: meet the material (will/doing) */
            despertar: 3, narrative: 3, skit: 3, susurro: 3,
            cartografo: 3, par_minimo: 3,
            cancion: 3, cultura: 3, explorador: 3, story: 3,
            /* Phase 6 — Elaboration: work with the material (feeling/heart) */
            fill: 6, pair: 6, builder: 6, conversation: 6,
            listening: 6, category: 6, tertulia: 6, eco_lejano: 6, oraculo: 6,
            codice: 6, pregonero: 6, debate: 6, negociacion: 6,
            boggle: 6, brecha: 6, clon: 6, conjugation: 6, consequences: 6,
            conjuro: 6, cronometro: 6, sombra: 6,
            crossword: 6, descripcion: 6, dictogloss: 6, flashnote: 6,
            guardian: 6, kloo: 6, madgab: 6, madlibs: 6, registro: 6,
            senda: 6, spaceman: 6, translation: 6,
            /* Phase 9 — Integration: produce and reflect (thinking/head) */
            dictation: 9, escaperoom: 9, cronica: 9, ritmo: 9, raiz: 9,
            tejido: 9, transformador: 9, corrector: 9, resumen: 9,
            portafolio: 9, autoevaluacion: 9, bananagrams: 9, eco_restaurar: 9
        }
    };

    /* ==========================================================
       1b. ACCESSIBILITY UTILITIES
    ========================================================== */
    var A11y = {
        _liveRegion: null,
        _captionsEnabled: false,

        /** Create and inject the aria-live region (called once from Engine.init) */
        initLiveRegion: function() {
            if (this._liveRegion) return;
            var el = document.createElement('div');
            el.id = 'ygA11yLive';
            el.className = 'yg-a11y-live';
            el.setAttribute('aria-live', 'polite');
            el.setAttribute('aria-atomic', 'true');
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
            this._liveRegion = el;
        },

        /** Announce a message to screen readers via the live region */
        announce: function(message) {
            if (!this._liveRegion) this.initLiveRegion();
            /* Clear then set to force re-announcement of same text */
            this._liveRegion.textContent = '';
            var el = this._liveRegion;
            setTimeout(function() { el.textContent = message; }, 50);
        },

        /** Add tabindex and role to a list of option elements */
        makeOptionGroup: function(container, optionSelector, groupRole, itemRole) {
            if (!container) return;
            container.setAttribute('role', groupRole || 'radiogroup');
            var items = container.querySelectorAll(optionSelector);
            for (var i = 0; i < items.length; i++) {
                items[i].setAttribute('role', itemRole || 'radio');
                items[i].setAttribute('tabindex', '0');
                items[i].setAttribute('aria-checked', 'false');
            }
        },

        /** Arrow key navigation within an option group */
        bindArrowNav: function(container, optionSelector) {
            if (!container) return;
            container.addEventListener('keydown', function(e) {
                var items = container.querySelectorAll(optionSelector + ':not([style*="pointer-events: none"]):not(.sorted):not(.used):not(.matched)');
                if (!items.length) return;
                var currentIdx = -1;
                for (var i = 0; i < items.length; i++) {
                    if (items[i] === document.activeElement) { currentIdx = i; break; }
                }
                var nextIdx = -1;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextIdx = (currentIdx + 1) % items.length;
                } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                    e.preventDefault();
                    nextIdx = (currentIdx - 1 + items.length) % items.length;
                }
                if (nextIdx >= 0) items[nextIdx].focus();
            });
        },

        /** Bind Enter/Space to trigger click on focusable items */
        bindActivateKeys: function(container, selector) {
            if (!container) return;
            container.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    var el = e.target.closest(selector);
                    if (el) {
                        e.preventDefault();
                        el.click();
                    }
                }
            });
        },

        /** Trap focus within a container (for modals/escape rooms) */
        trapFocus: function(container) {
            if (!container) return;
            var focusable = container.querySelectorAll('button, [href], input, textarea, select, [tabindex]:not([tabindex="-1"])');
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            container.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === first) {
                            e.preventDefault();
                            last.focus();
                        }
                    } else {
                        if (document.activeElement === last) {
                            e.preventDefault();
                            first.focus();
                        }
                    }
                }
            });
        },

        /** Escape key handler to dismiss overlays */
        bindEscapeDismiss: function(container, callback) {
            if (!container) return;
            container.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    if (callback) callback();
                }
            });
        },

        /** Build caption toggle button HTML for audio game types */
        buildCaptionToggle: function(audioText) {
            var html = '<button class="yg-caption-toggle" id="ygCaptionToggle" ' +
                       'aria-label="Mostrar subtítulos" aria-pressed="false" tabindex="0">' +
                       '&#9776; Subtítulos</button>';
            html += '<div class="yg-caption-text" id="ygCaptionText" aria-hidden="true">' +
                    (audioText || '') + '</div>';
            return html;
        },

        /** Bind caption toggle click behavior */
        bindCaptionToggle: function() {
            var btn = document.getElementById('ygCaptionToggle');
            var text = document.getElementById('ygCaptionText');
            if (!btn || !text) return;

            btn.addEventListener('click', function() {
                var isPressed = btn.getAttribute('aria-pressed') === 'true';
                btn.setAttribute('aria-pressed', isPressed ? 'false' : 'true');
                text.classList.toggle('yg-caption-visible');
                text.setAttribute('aria-hidden', isPressed ? 'true' : 'false');
            });
        },

        /** Mark an option as selected (aria-checked) and unmark siblings */
        selectOption: function(el, container, optionSelector) {
            if (!container) return;
            var items = container.querySelectorAll(optionSelector);
            for (var i = 0; i < items.length; i++) {
                items[i].setAttribute('aria-checked', 'false');
            }
            if (el) el.setAttribute('aria-checked', 'true');
        }
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
            /* Duck background music during speech */
            if (window.AudioManager) AudioManager.duckForSpeech();
            var userOnEnd = (opts && opts.onEnd) || null;
            u.onend = function() {
                if (window.AudioManager) AudioManager.unduckForSpeech();
                if (userOnEnd) userOnEnd();
            };
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

            /* Accessibility: announce correct answer */
            A11y.announce('\u00a1Correcto!');

            /* File-based SFX if available, else synthesized fallback */
            if (window.AudioManager) {
                AudioManager.playCorrect();
            } else {
                var sounds = ['water-drop', 'bird'];
                Audio.playNatureSound(sounds[Math.floor(Math.random() * sounds.length)]);
            }

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

            /* Accessibility: announce incorrect answer */
            A11y.announce('Intenta otra vez');

            Audio.cancel();
            if (window.AudioManager) AudioManager.playIncorrect();

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

        /* Show a specific line (used by departure screen) */
        showLine: function(panelEl, line) {
            if (!panelEl || !line) return;
            panelEl.innerHTML =
                '<div class="yg-yaguara-bubble" id="ygYaguaraBubble">' +
                    '<div class="yg-yaguara-avatar">\uD83D\uDC06</div>' +
                    '<div class="yg-yaguara-text">' + line + '</div>' +
                '</div>';
            var bubble = document.getElementById('ygYaguaraBubble');
            setTimeout(function() { if (bubble) bubble.classList.add('visible'); }, 50);
            Audio.speak(line, { rate: 0.5 });
        },

        /* Character interjection — swap Yaguará for a character line */
        characterInterject: function(panelEl, character, line, cb) {
            if (!panelEl) { if (cb) cb(); return; }
            var avatar = character.avatar || '\uD83D\uDC64';
            var name = character.name || '';

            panelEl.innerHTML =
                '<div class="yg-yaguara-bubble yg-character-bubble" id="ygYaguaraBubble">' +
                    '<div class="yg-yaguara-avatar">' + avatar + '</div>' +
                    '<div class="yg-character-info">' +
                        (name ? '<div class="yg-character-name">' + name + '</div>' : '') +
                        '<div class="yg-yaguara-text">' + line + '</div>' +
                    '</div>' +
                '</div>';

            var bubble = document.getElementById('ygYaguaraBubble');
            setTimeout(function() { if (bubble) bubble.classList.add('visible'); }, 50);
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
            .replace(/[úù]/g, 'u')
            .replace(/[^a-zñ0-9\s]/g, '').trim().replace(/\s+/g, ' ');
    }

    /* --- Levenshtein distance for fuzzy matching --- */
    function _levenshtein(a, b) {
        if (a.length === 0) return b.length;
        if (b.length === 0) return a.length;
        var matrix = [];
        for (var i = 0; i <= b.length; i++) matrix[i] = [i];
        for (var j = 0; j <= a.length; j++) matrix[0][j] = j;
        for (var i = 1; i <= b.length; i++) {
            for (var j = 1; j <= a.length; j++) {
                var cost = a.charAt(j - 1) === b.charAt(i - 1) ? 0 : 1;
                matrix[i][j] = Math.min(matrix[i - 1][j] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j - 1] + cost);
            }
        }
        return matrix[b.length][a.length];
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
        constelacion: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1.5"/><circle cx="5" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/><circle cx="8" cy="19" r="1.5"/><circle cx="16" cy="19" r="1.5"/><line x1="12" y1="5" x2="5" y2="12"/><line x1="12" y1="5" x2="19" y2="12"/><line x1="5" y1="12" x2="8" y2="19"/><line x1="19" y1="12" x2="16" y2="19"/><line x1="8" y1="19" x2="16" y2="19"/></svg>',
        /* Cronica — quill/feather writing icon */
        cronica: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.24 12.24a6 6 0 00-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/><line x1="17.5" y1="15" x2="9" y2="15"/></svg>',
        despertar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><line x1="12" y1="3" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="21"/><line x1="3" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="21" y2="12"/><line x1="6" y1="6" x2="7.5" y2="7.5"/><line x1="16.5" y1="16.5" x2="18" y2="18"/></svg>'
    };

    /* Map canonical game types to icon keys */
    var TYPE_ICONS = {
        pair: 'pair', fill: 'fill', conjugation: 'conjugation',
        listening: 'listening', category: 'category', builder: 'builder',
        translation: 'translation', conversation: 'conversation',
        dictation: 'dictation', story: 'story', narrative: 'narrative',
        cancion: 'cancion', escaperoom: 'escaperoom', cronica: 'cronica',
        flashnote: 'fill', crossword: 'fill', explorador: 'story', kloo: 'builder',
        cultura: 'story', senda: 'story', consequences: 'conversation', madlibs: 'fill',
        guardian: 'conjugation', madgab: 'listening', boggle: 'pair', bananagrams: 'pair',
        clon: 'pair', conjuro: 'conjugation', eco_restaurar: 'story', spaceman: 'fill',
        par_minimo: 'listening', dictogloss: 'listening', corrector: 'fill',
        brecha: 'conversation', resumen: 'narrative', registro: 'conversation',
        debate: 'conversation', descripcion: 'narrative', ritmo: 'listening',
        cronometro: 'conjugation', portafolio: 'cronica', autoevaluacion: 'narrative',
        negociacion: 'conversation', transformador: 'fill',
        bingo: 'listening', scrabble: 'builder',
        susurro: 'listening', eco_lejano: 'narrative', tertulia: 'conversation',
        pregonero: 'cronica', raiz: 'story', codice: 'fill',
        sombra: 'dictation', oraculo: 'conversation', tejido: 'builder',
        cartografo: 'listening',
        skit: 'conversation',
        despertar: 'despertar'
    };

    /* Map A1 module slugs to icon keys */
    var MODULE_ICONS = {
        familia: 'familia', sonidos: 'sonidos', agua: 'agua',
        casa: 'casa', comida: 'comida', amigos: 'amigos'
    };

    /* ==========================================================
       DESPERTAR — Symbolic Vocabulary Scaffolding
       Cave-painting SVGs: single-stroke, warm ochre on transparent.
       Covers A1 vocabulary (~90 symbols).
       ========================================================== */
    var SYMBOLS = {
        /* --- Greetings & social --- */
        hola:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 26c0-4 3-6 3-10a5 5 0 00-10 0"/><path d="M12 16c-1-2-3-2-4 0"/><path d="M20 16c1-2 3-2 4 0"/><circle cx="16" cy="8" r="3"/><path d="M10 6c-1-2 1-4 3-3"/><path d="M22 6c1-2-1-4-3-3"/></svg>',
        adios:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v6"/><path d="M10 16l-3-2"/><path d="M14 16l3-2"/><path d="M10 19l-2 5"/><path d="M14 19l2 5"/><path d="M20 8l4 0"/><path d="M22 6l2 2-2 2"/></svg>',
        buenos_dias:    '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 20a8 8 0 010-16"/><path d="M4 20h24"/><line x1="16" y1="4" x2="16" y2="1"/><line x1="8" y1="6" x2="6" y2="4"/><line x1="24" y1="6" x2="26" y2="4"/><line x1="5" y1="12" x2="2" y2="12"/><line x1="27" y1="12" x2="30" y2="12"/></svg>',
        buenas_tardes:  '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="6"/><path d="M4 22h24"/><line x1="16" y1="6" x2="16" y2="8"/><line x1="24" y1="10" x2="22" y2="11"/><line x1="8" y1="10" x2="10" y2="11"/></svg>',
        buenas_noches:  '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M22 8a8 8 0 11-12 10 6 6 0 0012-10z"/><circle cx="22" cy="10" r="1" fill="#b87333"/><circle cx="18" cy="6" r="0.8" fill="#b87333"/><circle cx="26" cy="14" r="0.8" fill="#b87333"/></svg>',
        hasta_luego:    '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v4"/><path d="M14 15l3-1"/><path d="M10 17l-2 5"/><path d="M14 17l2 5"/><path d="M22 12a4 4 0 100-8 4 4 0 000 8z" stroke-dasharray="2 2"/></svg>',
        gracias:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="10" r="3"/><path d="M16 13v6"/><path d="M13 16l-2 2"/><path d="M19 16l2 2"/><path d="M12 23c2 2 6 2 8 0"/></svg>',

        /* --- Identity & pronouns --- */
        yo:             '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M16 11v8"/><path d="M12 15l-3 3"/><path d="M20 15l3 3"/><path d="M13 19l-2 5"/><path d="M19 19l2 5"/><circle cx="16" cy="16" r="1.5" fill="#b87333"/></svg>',
        tu:             '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M16 11v8"/><path d="M13 19l-2 5"/><path d="M19 19l2 5"/><path d="M18 14l6 2"/><path d="M22 15l3 1"/><circle cx="26" cy="16" r="1" fill="#b87333"/></svg>',
        el:             '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="8" r="3"/><path d="M10 11v8"/><path d="M7 19l-2 5"/><path d="M13 19l2 5"/><path d="M20 14l4-2"/></svg>',
        ella:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="8" r="3"/><path d="M10 11v4"/><path d="M10 15c-2 1-3 4-3 7"/><path d="M10 15c2 1 3 4 3 7"/><path d="M20 14l4-2"/></svg>',
        nosotros:       '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="8" r="2.5"/><circle cx="22" cy="8" r="2.5"/><path d="M10 11v7"/><path d="M22 11v7"/><path d="M8 18l-2 4"/><path d="M12 18l2 4"/><path d="M20 18l-2 4"/><path d="M24 18l2 4"/><path d="M12 14h8"/></svg>',
        ellos:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="2"/><circle cx="16" cy="8" r="2"/><circle cx="24" cy="8" r="2"/><path d="M8 10v6"/><path d="M16 10v6"/><path d="M24 10v6"/><path d="M6 16l-1 4"/><path d="M10 16l1 4"/><path d="M14 16l-1 4"/><path d="M18 16l1 4"/><path d="M22 16l-1 4"/><path d="M26 16l1 4"/></svg>',
        nombre:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v6"/><rect x="18" y="8" width="10" height="6" rx="1"/><line x1="20" y1="11" x2="26" y2="11"/></svg>',

        /* --- Ser / Estar --- */
        ser:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><line x1="8" y1="12" x2="24" y2="12"/><line x1="8" y1="18" x2="24" y2="18"/></svg>',
        estar:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="3" fill="#b87333"/><circle cx="16" cy="16" r="8"/><path d="M16 4v4"/><path d="M16 24v4"/></svg>',
        tener:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M10 12c0-3 2-5 6-5s6 2 6 5"/><path d="M8 12h16v6c0 4-3 7-8 7s-8-3-8-7v-6z"/></svg>',
        hay:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="12" r="2" fill="#b87333"/><circle cx="20" cy="10" r="2" fill="#b87333"/><circle cx="16" cy="18" r="2" fill="#b87333"/><path d="M6 24h20"/></svg>',

        /* --- Family --- */
        familia:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="8" r="2.5"/><circle cx="22" cy="8" r="2.5"/><circle cx="16" cy="14" r="2"/><path d="M10 11v7"/><path d="M22 11v7"/><path d="M16 16v4"/><path d="M8 18l-2 5"/><path d="M12 18l2-1"/><path d="M20 18l-2-1"/><path d="M24 18l2 5"/><path d="M14 20l-1 4"/><path d="M18 20l1 4"/></svg>',
        madre:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="14" cy="8" r="3"/><path d="M14 11v4"/><path d="M14 15c-2 1-3 4-3 7"/><path d="M14 15c2 1 3 4 3 7"/><circle cx="22" cy="14" r="2"/><path d="M22 16v3"/><path d="M16 14l4 1"/></svg>',
        padre:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="14" cy="7" r="3"/><path d="M14 10v9"/><path d="M11 14l-3 3"/><path d="M17 14l3 3"/><path d="M11 19l-2 5"/><path d="M17 19l2 5"/><circle cx="23" cy="14" r="2"/><path d="M23 16v3"/></svg>',
        hermano:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="2.5"/><circle cx="22" cy="10" r="2.5"/><path d="M12 13v6"/><path d="M22 13v6"/><path d="M10 19l-2 4"/><path d="M14 19l2 4"/><path d="M20 19l-2 4"/><path d="M24 19l2 4"/></svg>',
        hermana:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="2.5"/><circle cx="22" cy="10" r="2.5"/><path d="M12 13v3"/><path d="M12 16c-2 0-3 3-3 6"/><path d="M12 16c2 0 3 3 3 6"/><path d="M22 13v3"/><path d="M22 16c-2 0-3 3-3 6"/><path d="M22 16c2 0 3 3 3 6"/></svg>',
        abuelo:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M16 11v8"/><path d="M16 19l-3 5"/><path d="M16 19l3 5"/><path d="M12 14l-4 1"/><path d="M20 14l4 1"/><path d="M8 15l-2 3" stroke-dasharray="2 1"/></svg>',
        abuela:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M16 11v4"/><path d="M16 15c-3 0-5 4-5 8"/><path d="M16 15c3 0 5 4 5 8"/><path d="M12 14l-4 1"/><path d="M20 14l4 1"/><path d="M8 15l-2 3" stroke-dasharray="2 1"/></svg>',
        hijo:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="12" r="2.5"/><path d="M16 15v5"/><path d="M14 20l-1 4"/><path d="M18 20l1 4"/><path d="M16 6v3" stroke-dasharray="1.5 1"/></svg>',
        hija:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="12" r="2.5"/><path d="M16 15v2"/><path d="M16 17c-2 0-3 3-3 6"/><path d="M16 17c2 0 3 3 3 6"/><path d="M16 6v3" stroke-dasharray="1.5 1"/></svg>',

        /* --- Nature / Jungle --- */
        selva:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M8 28v-10"/><path d="M8 18c-4-2-5-6-3-10 2 3 5 4 7 3-1 3 0 5 2 7"/><path d="M22 28v-12"/><path d="M22 16c-4-2-5-8-2-12 2 4 5 5 8 4-2 4-1 6 1 8"/><path d="M15 28v-6"/><path d="M15 22c-2-1-3-4-1-7 1 2 3 3 5 2 0 2 0 4 1 5"/></svg>',
        rio:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M4 8c4 2 8-2 12 0s8-2 12 0"/><path d="M4 16c4 2 8-2 12 0s8-2 12 0"/><path d="M4 24c4 2 8-2 12 0s8-2 12 0"/></svg>',
        agua:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 4c0 0-8 8-8 14a8 8 0 0016 0c0-6-8-14-8-14z"/></svg>',
        arbol:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 28v-14"/><path d="M16 14c-5-1-7-6-4-10 2 3 5 3 7 2 0 3 2 6 4 8-2 0-5 0-7 0z"/></svg>',
        flor:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="12" r="2.5" fill="#b87333"/><circle cx="16" cy="7" r="2.5"/><circle cx="20" cy="10" r="2.5"/><circle cx="20" cy="14" r="2.5"/><circle cx="16" cy="17" r="2.5"/><circle cx="12" cy="14" r="2.5"/><circle cx="12" cy="10" r="2.5"/><path d="M16 19v9"/></svg>',
        cielo:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M4 22c2-2 4-3 7-3 2 0 3 1 5 1s3-1 5-1c3 0 5 1 7 3"/><circle cx="16" cy="10" r="4"/><line x1="16" y1="3" x2="16" y2="5"/><line x1="10" y1="6" x2="11" y2="7.5"/><line x1="22" y1="6" x2="21" y2="7.5"/><line x1="8" y1="10" x2="10" y2="10"/><line x1="22" y1="10" x2="24" y2="10"/></svg>',
        sol:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="5"/><line x1="16" y1="6" x2="16" y2="9"/><line x1="16" y1="23" x2="16" y2="26"/><line x1="6" y1="16" x2="9" y2="16"/><line x1="23" y1="16" x2="26" y2="16"/><line x1="9" y1="9" x2="11" y2="11"/><line x1="21" y1="21" x2="23" y2="23"/><line x1="9" y1="23" x2="11" y2="21"/><line x1="21" y1="11" x2="23" y2="9"/></svg>',
        luna:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M22 8a10 10 0 11-12 12 7 7 0 0012-12z"/></svg>',

        /* --- Animals --- */
        jaguar:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="14" rx="8" ry="6"/><path d="M8 11l-3-4"/><path d="M24 11l3-4"/><circle cx="13" cy="13" r="1" fill="#b87333"/><circle cx="19" cy="13" r="1" fill="#b87333"/><ellipse cx="16" cy="16" rx="2" ry="1" fill="#b87333"/><path d="M10 18c-2 2-3 6-3 8"/><path d="M22 18c2 2 3 6 3 8"/><path d="M14 20v6"/><path d="M18 20v6"/><circle cx="11" cy="12" r="0.6" fill="#b87333"/><circle cx="21" cy="12" r="0.6" fill="#b87333"/><circle cx="14" cy="10" r="0.6" fill="#b87333"/><circle cx="18" cy="10" r="0.6" fill="#b87333"/></svg>',
        pajaro:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="16" rx="6" ry="4"/><circle cx="12" cy="14" r="2"/><circle cx="11" cy="14" r="0.6" fill="#b87333"/><path d="M10 16l-4 1"/><path d="M22 14c3-3 6-2 7 0"/><path d="M22 18c3 3 6 2 7 0"/><path d="M14 20l-1 4"/><path d="M18 20l1 4"/></svg>',
        mono:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="12" r="5"/><circle cx="10" cy="10" r="2"/><circle cx="22" cy="10" r="2"/><circle cx="14" cy="11" r="0.8" fill="#b87333"/><circle cx="18" cy="11" r="0.8" fill="#b87333"/><path d="M14 14c1 1 3 1 4 0"/><path d="M16 17v3"/><path d="M12 18l-4 4"/><path d="M20 18l4 4"/><path d="M14 20l-1 4"/><path d="M18 20l1 4"/></svg>',
        rana:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="18" rx="8" ry="5"/><circle cx="11" cy="11" r="3"/><circle cx="21" cy="11" r="3"/><circle cx="11" cy="11" r="1" fill="#b87333"/><circle cx="21" cy="11" r="1" fill="#b87333"/><path d="M8 22l-4 4"/><path d="M24 22l4 4"/></svg>',
        pez:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="14" cy="16" rx="8" ry="5"/><path d="M22 16l6-5v10l-6-5z"/><circle cx="10" cy="15" r="1" fill="#b87333"/></svg>',
        serpiente:      '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M4 24c2-4 6-4 8 0s6-4 8 0 6-4 8 0"/><circle cx="4" cy="24" r="1.5"/><circle cx="3" cy="23" r="0.5" fill="#b87333"/></svg>',

        /* --- Food & drink --- */
        comida:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="20" rx="10" ry="4"/><path d="M6 20c0-6 4-12 10-14 6 2 10 8 10 14"/></svg>',
        pescado:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="14" cy="16" rx="8" ry="5"/><path d="M22 16l6-5v10l-6-5z"/><circle cx="10" cy="15" r="1" fill="#b87333"/><path d="M12 18l4 0" stroke-dasharray="1 1"/></svg>',
        fruta:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="18" r="7"/><path d="M16 11c-1-3 1-5 3-6"/><path d="M16 11c1 0 4-1 5-3"/></svg>',
        platano:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M8 24c-1-6 2-14 10-18 0 4 4 10 6 14-4 4-12 6-16 4z"/></svg>',
        arroz:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="22" rx="8" ry="3"/><path d="M8 22v-4c0-2 4-4 8-4s8 2 8 4v4"/><circle cx="12" cy="19" r="0.8" fill="#b87333"/><circle cx="16" cy="18" r="0.8" fill="#b87333"/><circle cx="20" cy="19" r="0.8" fill="#b87333"/><circle cx="14" cy="21" r="0.8" fill="#b87333"/><circle cx="18" cy="21" r="0.8" fill="#b87333"/></svg>',
        jugo:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M10 10h12l-2 16h-8l-2-16z"/><path d="M12 10c0-3 2-4 4-4s4 1 4 4"/><circle cx="16" cy="18" r="2"/></svg>',
        yuca:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><ellipse cx="16" cy="18" rx="4" ry="8"/><path d="M16 10c-1-3 0-5 2-6"/><path d="M16 10c1-3 0-5-2-6"/></svg>',

        /* --- House / rooms --- */
        casa:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M4 16l12-10 12 10"/><rect x="8" y="16" width="16" height="10" rx="1"/><rect x="13" y="20" width="6" height="6"/></svg>',
        cocina:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><rect x="6" y="14" width="20" height="12" rx="1"/><path d="M6 20h20"/><circle cx="12" cy="17" r="1.5"/><circle cx="20" cy="17" r="1.5"/><path d="M14 10c0-2 1-3 2-3s2 1 2 3"/></svg>',
        dormitorio:     '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><rect x="4" y="18" width="24" height="8" rx="1"/><path d="M4 18c0-3 4-6 12-6s12 3 12 6"/><rect x="6" y="14" width="6" height="4" rx="1"/></svg>',
        bano:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M4 16h24"/><path d="M6 16v-8a2 2 0 014 0v8"/><path d="M6 16c0 4 4 8 10 8s10-4 10-8"/><path d="M12 24v2"/><path d="M20 24v2"/></svg>',

        /* --- Numbers --- */
        uno:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><circle cx="16" cy="16" r="3" fill="#b87333"/></svg>',
        dos:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="16" r="3" fill="#b87333"/><circle cx="21" cy="16" r="3" fill="#b87333"/></svg>',
        tres:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><circle cx="16" cy="10" r="2.5" fill="#b87333"/><circle cx="10" cy="20" r="2.5" fill="#b87333"/><circle cx="22" cy="20" r="2.5" fill="#b87333"/></svg>',

        /* --- Colors --- */
        verde:          '<svg viewBox="0 0 32 32" fill="none" stroke="#5a6e4a" stroke-width="1.5" stroke-linecap="round"><path d="M8 24c0-8 4-16 8-18 4 2 8 10 8 18-4-2-12-2-16 0z"/><path d="M16 6v18"/></svg>',
        rojo:           '<svg viewBox="0 0 32 32" fill="none" stroke="#a04040" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="8" fill="none" stroke="#a04040"/><circle cx="16" cy="16" r="4" fill="#a04040"/></svg>',
        azul:           '<svg viewBox="0 0 32 32" fill="none" stroke="#4a6a8a" stroke-width="1.5" stroke-linecap="round"><path d="M4 12c4 2 8-2 12 0s8-2 12 0"/><path d="M4 18c4 2 8-2 12 0s8-2 12 0"/><path d="M4 24c4 2 8-2 12 0s8-2 12 0"/></svg>',
        blanco:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="8"/></svg>',
        negro:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="8" fill="#3a3a3a"/></svg>',
        amarillo:       '<svg viewBox="0 0 32 32" fill="none" stroke="#c9a227" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="6"/><line x1="16" y1="6" x2="16" y2="8"/><line x1="16" y1="24" x2="16" y2="26"/><line x1="6" y1="16" x2="8" y2="16"/><line x1="24" y1="16" x2="26" y2="16"/></svg>',

        /* --- Basic adjectives --- */
        grande:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="10"/></svg>',
        pequeno:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="4"/></svg>',
        bonito:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 6l2 5h5l-4 3 2 5-5-3-5 3 2-5-4-3h5z"/></svg>',
        fuerte:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><path d="M10 20c0-4 2-6 6-6s6 2 6 6"/><path d="M8 14l-2-6 4 2"/><path d="M24 14l2-6-4 2"/></svg>',
        feliz:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><circle cx="12" cy="14" r="1" fill="#b87333"/><circle cx="20" cy="14" r="1" fill="#b87333"/><path d="M11 20c2 3 8 3 10 0"/></svg>',
        nuevo:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 6l2 4h4l-3 3 1 5-4-3-4 3 1-5-3-3h4z"/><circle cx="16" cy="16" r="1" fill="#b87333"/></svg>',
        bueno:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><path d="M8 16l4 5 10-10"/></svg>',
        malo:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><path d="M10 10l12 12"/><path d="M22 10l-12 12"/></svg>',

        /* --- Basic verbs (motion, daily) --- */
        caminar:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="6" r="2.5"/><path d="M16 9v7"/><path d="M16 16l-4 8"/><path d="M16 16l4 8"/><path d="M13 12l-4 3"/><path d="M19 12l4 3"/></svg>',
        comer:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="10" r="3"/><path d="M16 13v4"/><path d="M16 17l-3 7"/><path d="M16 17l3 7"/><path d="M13 15l-5-1"/><path d="M19 15l2-2"/><circle cx="22" cy="12" r="2"/></svg>',
        beber:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v4"/><path d="M12 17l-3 7"/><path d="M12 17l3 7"/><path d="M15 14l4-2"/><path d="M20 8h4l-2 12h-4l-2-12z"/></svg>',
        nadar:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="12" r="2.5"/><path d="M13 12h10"/><path d="M14 14l4 2"/><path d="M14 10l4-2"/><path d="M4 22c3-2 6 2 9 0s6-2 9 0s6 2 9 0" stroke-dasharray="2 1"/></svg>',
        hablar:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="14" cy="10" r="3"/><path d="M14 13v6"/><path d="M14 19l-3 5"/><path d="M14 19l3 5"/><path d="M20 8c2-1 6 0 7 3s0 6-3 6l-3-2z"/></svg>',
        escuchar:       '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="14" cy="10" r="3"/><path d="M14 13v6"/><path d="M14 19l-3 5"/><path d="M14 19l3 5"/><path d="M18 8c2 0 4 2 4 4s-2 4-4 4"/><path d="M20 6c3 0 6 3 6 6s-3 6-6 6"/></svg>',
        vivir:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M6 16l10-10 10 10"/><rect x="10" y="16" width="12" height="8" rx="1"/><circle cx="16" cy="12" r="2.5"/><path d="M16 15v4"/></svg>',
        ir:             '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="10" r="3"/><path d="M10 13v6"/><path d="M8 19l-2 5"/><path d="M12 19l2 5"/><path d="M16 12h8"/><path d="M22 9l3 3-3 3"/></svg>',
        correr:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="18" cy="6" r="2.5"/><path d="M16 9l-4 6"/><path d="M12 15l-5 5"/><path d="M16 9l5 4"/><path d="M21 13l4 7"/><path d="M12 15l8-2"/></svg>',
        dormir:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M6 22h20"/><path d="M8 22c0-3 3-6 8-6s8 3 8 6"/><circle cx="12" cy="18" r="2"/><path d="M11 17.5h2" stroke-width="0.8"/><path d="M20 10l3-2"/><path d="M22 6l3-2"/><path d="M24 10l2-2"/></svg>',
        escribir:       '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M22 4l4 4-14 14H8v-4L22 4z"/><path d="M6 26h20"/></svg>',

        /* --- Question words --- */
        que:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><circle cx="16" cy="12" r="7"/><path d="M14 10c0-2 2-3 3-2 1 1 1 3-1 4v3"/><circle cx="16" cy="20" r="1" fill="#b87333"/></svg>',
        donde:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 4c-5 0-9 4-9 9 0 7 9 15 9 15s9-8 9-15c0-5-4-9-9-9z"/><circle cx="16" cy="13" r="3"/></svg>',
        como:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><path d="M12 12c0-2 2-3 4-3s4 1 4 3-2 3-4 4v2"/><circle cx="16" cy="23" r="1" fill="#b87333"/></svg>',
        cuando:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><path d="M16 10v6l4 3"/></svg>',
        cuantos:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="10" r="2" fill="#b87333"/><circle cx="20" cy="10" r="2" fill="#b87333"/><circle cx="10" cy="20" r="2" fill="#b87333"/><circle cx="20" cy="20" r="2" fill="#b87333"/><circle cx="16" cy="15" r="2" fill="#b87333"/><circle cx="16" cy="26" r="1" fill="#b87333"/><path d="M16 3v2"/><path d="M16 5c-2 0-2 2 0 2s2 2 0 2"/></svg>',
        por_que:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="2" stroke-linecap="round"><circle cx="16" cy="12" r="7"/><path d="M14 10c0-2 2-3 3-2 1 1 1 3-1 4v3"/><circle cx="16" cy="20" r="1" fill="#b87333"/><path d="M10 26h12"/></svg>',

        /* --- Time / days --- */
        manana_tiempo:  '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 22a8 8 0 010-16"/><path d="M4 22h24"/><path d="M24 10l2-2"/><path d="M16 4v-2"/></svg>',
        tarde:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="14" r="5"/><path d="M4 22h24"/><line x1="16" y1="5" x2="16" y2="7"/><line x1="23" y1="10" x2="25" y2="8"/><line x1="9" y1="10" x2="7" y2="8"/></svg>',
        noche:          '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M20 6a8 8 0 11-10 10 6 6 0 0010-10z"/><circle cx="22" cy="12" r="0.8" fill="#b87333"/><circle cx="18" cy="8" r="0.6" fill="#b87333"/></svg>',
        hora:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="10"/><path d="M16 8v8l5 3"/></svg>',

        /* --- Weather --- */
        lluvia:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M8 14c-2 0-4-2-4-4s3-5 6-5c1-3 4-4 6-4s5 1 6 4c3 0 6 2 6 5s-2 4-4 4H8z"/><line x1="10" y1="18" x2="10" y2="22"/><line x1="16" y1="18" x2="16" y2="24"/><line x1="22" y1="18" x2="22" y2="22"/></svg>',
        frio:           '<svg viewBox="0 0 32 32" fill="none" stroke="#4a6a8a" stroke-width="1.5" stroke-linecap="round"><line x1="16" y1="4" x2="16" y2="28"/><line x1="4" y1="16" x2="28" y2="16"/><line x1="8" y1="8" x2="24" y2="24"/><line x1="24" y1="8" x2="8" y2="24"/></svg>',
        calor:          '<svg viewBox="0 0 32 32" fill="none" stroke="#a04040" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="5"/><path d="M16 6v-2"/><path d="M16 28v-2"/><path d="M6 16h-2"/><path d="M28 16h-2"/><path d="M9 9l-2-2"/><path d="M25 25l-2-2"/><path d="M9 23l-2 2"/><path d="M25 7l-2 2"/></svg>',

        /* --- Emotions / needs --- */
        hambre:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><circle cx="12" cy="14" r="1" fill="#b87333"/><circle cx="20" cy="14" r="1" fill="#b87333"/><path d="M12 21c2-2 6-2 8 0"/></svg>',
        sed:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><circle cx="12" cy="14" r="1" fill="#b87333"/><circle cx="20" cy="14" r="1" fill="#b87333"/><ellipse cx="16" cy="21" rx="2" ry="1.5"/></svg>',
        cansado:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><line x1="11" y1="14" x2="14" y2="14"/><line x1="18" y1="14" x2="21" y2="14"/><path d="M12 20h8"/></svg>',
        bien:           '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="16" r="9"/><circle cx="12" cy="14" r="1" fill="#b87333"/><circle cx="20" cy="14" r="1" fill="#b87333"/><path d="M11 19c2 3 8 3 10 0"/></svg>',

        /* --- Gustar & preferences --- */
        gustar:         '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 28s-10-6-10-14a6 6 0 0112 0 6 6 0 0112 0c0 8-10 14-14 14z" stroke-dasharray="0"/></svg>',
        no_gustar:      '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><path d="M16 28s-10-6-10-14a6 6 0 0112 0 6 6 0 0112 0c0 8-10 14-14 14z"/><line x1="6" y1="6" x2="26" y2="26" stroke-width="2"/></svg>',

        /* --- Possessives --- */
        mi:             '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v6"/><circle cx="22" cy="14" r="3"/><path d="M14 14l5 0"/></svg>',

        /* --- Months (generic calendar symbol) --- */
        mes:            '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><rect x="4" y="6" width="24" height="20" rx="2"/><line x1="4" y1="12" x2="28" y2="12"/><line x1="10" y1="6" x2="10" y2="3"/><line x1="22" y1="6" x2="22" y2="3"/></svg>',

        /* --- Reflexive / daily routine --- */
        levantarse:     '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M16 11v4"/><path d="M12 22h8"/><path d="M10 22v-3c0-2 2-4 6-4s6 2 6 4v3"/><path d="M12 14l-4-3"/><path d="M20 14l4-3"/><path d="M14 4l2-2 2 2"/></svg>',
        banarse:        '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="16" cy="8" r="3"/><path d="M6 16h20"/><path d="M8 16c0 5 3 8 8 8s8-3 8-8"/><path d="M12 12l-2 4"/><path d="M20 12l2 4"/><circle cx="12" cy="4" r="0.8" fill="#b87333"/><circle cx="20" cy="4" r="0.8" fill="#b87333"/></svg>',
        llamarse:       '<svg viewBox="0 0 32 32" fill="none" stroke="#b87333" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 13v6"/><path d="M12 19l-3 5"/><path d="M12 19l3 5"/><path d="M18 8h8v4h-8z" rx="1"/><line x1="20" y1="10" x2="24" y2="10"/></svg>'
    };

    /**
     * Normalize a word key for SYMBOLS lookup.
     * Strips accents, lowercases, replaces spaces with underscore.
     */
    function symbolKey(word) {
        if (!word) return '';
        return word.toLowerCase()
            .replace(/[áà]/g, 'a').replace(/[éè]/g, 'e')
            .replace(/[íì]/g, 'i').replace(/[óò]/g, 'o')
            .replace(/[úù]/g, 'u').replace(/ñ/g, 'n')
            .replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
    }

    /**
     * Render a symbol assist icon next to a word.
     * @param {string} word   — The Spanish word
     * @param {string} key    — Optional explicit SYMBOLS key (overrides auto-lookup)
     * @param {string} density — 'heavy'|'moderate'|'light'|null
     * @returns {string} HTML string (empty if no symbol found or density is null)
     */
    function renderSymbolAssist(word, key, density) {
        if (!density) return '';
        var k = key || symbolKey(word);
        var svg = SYMBOLS[k];
        if (!svg) return '';
        var cls = 'yg-symbol-assist';
        if (density === 'heavy') cls += ' yg-symbol-heavy';
        else if (density === 'moderate') cls += ' yg-symbol-moderate';
        else if (density === 'light') cls += ' yg-symbol-light';
        return '<span class="' + cls + '" data-symbol="' + k + '">' + svg + '</span>';
    }

    /**
     * Determine symbol density for a given CEFR level and destination number.
     * Returns 'heavy'|'moderate'|'light'|null
     */
    function getSymbolDensity(cefr, destNum) {
        if (!destNum) destNum = 0;
        if (destNum >= 1 && destNum <= 6) return 'heavy';
        if (destNum >= 7 && destNum <= 12) return 'moderate';
        if (destNum >= 13 && destNum <= 16) return 'light';
        return null;
    }

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

    /* Scene image injection — adds an illustration above renderer content.
       Narrative and despertar handle their own images, so they are excluded.
       Returns the element the renderer should use as its container. */
    function prepareSceneImage(container, data) {
        if (data.image && data.type !== 'narrative' && data.type !== 'despertar') {
            var scene = document.createElement('div');
            scene.className = 'yg-scene-image';
            var img = document.createElement('img');
            img.src = data.image;
            img.alt = data.imageAlt || '';
            img.className = 'yg-scene-img';
            img.loading = 'lazy';
            img.onerror = function() { scene.style.display = 'none'; };
            scene.appendChild(img);
            container.appendChild(scene);

            var content = document.createElement('div');
            content.className = 'yg-scene-content';
            container.appendChild(content);
            return content;
        }
        return container;
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
            'verb-conjugation-multi': 'conjugation',
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
            'quest': 'escaperoom',
            'flash': 'flashnote',
            'flash_note': 'flashnote',
            'note': 'flashnote',
            'crucigrama': 'crossword',
            'explore': 'explorador',
            'explorer': 'explorador',
            'map': 'explorador',
            'cards': 'kloo',
            'card_builder': 'kloo',
            'culture': 'cultura', 'cultural': 'cultura',
            'path': 'senda', 'trail': 'senda',
            'chain': 'consequences', 'chain_story': 'consequences',
            'mad_libs': 'madlibs', 'mad-libs': 'madlibs',
            'mad_gab': 'madgab', 'mad-gab': 'madgab', 'descifrar': 'madgab',
            'spell': 'conjuro', 'spellcast': 'conjuro',
            'hangman': 'spaceman', 'ahorcado': 'spaceman',
            'word_grid': 'boggle',
            'restore': 'eco_restaurar', 'restaurar': 'eco_restaurar',
            'clone': 'clon', 'perspective': 'clon',
            'word_build': 'bananagrams', 'letter_build': 'bananagrams',
            'defend': 'guardian', 'defense': 'guardian', 'rapid': 'guardian',
            'minimal_pair': 'par_minimo', 'minimal-pair': 'par_minimo', 'phoneme': 'par_minimo',
            'dicto': 'dictogloss', 'dicto_gloss': 'dictogloss', 'reconstruct': 'dictogloss',
            'error': 'corrector', 'error_spot': 'corrector', 'proofread': 'corrector',
            'gap': 'brecha', 'info_gap': 'brecha', 'information_gap': 'brecha',
            'summary': 'resumen', 'summarize': 'resumen', 'summarise': 'resumen',
            'register': 'registro', 'register_shift': 'registro', 'formality': 'registro',
            'argumentation': 'debate', 'tribunal': 'debate', 'argue': 'debate',
            'describe': 'descripcion', 'description': 'descripcion',
            'rhythm': 'ritmo', 'stress': 'ritmo', 'prosody': 'ritmo',
            'timer': 'cronometro', 'timed': 'cronometro', 'speed': 'cronometro',
            'portfolio': 'portafolio', 'journal': 'portafolio', 'diario': 'portafolio',
            'self_assess': 'autoevaluacion', 'self-assessment': 'autoevaluacion', 'reflection': 'autoevaluacion',
            'negotiate': 'negociacion', 'mediation': 'negociacion', 'mediate': 'negociacion',
            'transform': 'transformador', 'text_transform': 'transformador', 'rewrite': 'transformador',
            'whisper': 'susurro', 'jungle_whisper': 'susurro',
            'fading_text': 'eco_lejano', 'fading': 'eco_lejano', 'fade_read': 'eco_lejano',
            'gathering': 'tertulia', 'social': 'tertulia', 'register_game': 'tertulia',
            'town_crier': 'pregonero', 'crier': 'pregonero', 'announce': 'pregonero',
            'root': 'raiz', 'etymology': 'raiz', 'babel': 'raiz', 'cognate': 'raiz',
            'codex': 'codice', 'ancient': 'codice', 'infer': 'codice',
            'shadow': 'sombra', 'shadow_dictation': 'sombra', 'speed_dictation': 'sombra',
            'oracle': 'oraculo', 'predict': 'oraculo', 'pragmatic': 'oraculo',
            'weave': 'tejido', 'weaving': 'tejido', 'connector': 'tejido', 'cohesion': 'tejido',
            'cartographer': 'cartografo', 'map_listen': 'cartografo', 'spatial': 'cartografo',
            'awaken': 'despertar', 'dawn': 'despertar', 'vocab_intro': 'despertar'
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
                case 'cronica': g = this.normalizeCronica(g); break;
                case 'flashnote': g = this.normalizeFlashnote(g); break;
                case 'crossword': g = this.normalizeCrossword(g); break;
                case 'explorador': g = this.normalizeExplorador(g); break;
                case 'kloo': g = this.normalizeKloo(g); break;
                case 'cultura': g = this.normalizeCultura(g); break;
                case 'senda': g = this.normalizeSenda(g); break;
                case 'consequences': g = this.normalizeConsequences(g); break;
                case 'madlibs': g = this.normalizeMadlibs(g); break;
                case 'guardian': g = this.normalizeGuardian(g); break;
                case 'madgab': g = this.normalizeMadgab(g); break;
                case 'boggle': g = this.normalizeBoggle(g); break;
                case 'bananagrams': g = this.normalizeBananagrams(g); break;
                case 'clon': g = this.normalizeClon(g); break;
                case 'conjuro': g = this.normalizeConjuro(g); break;
                case 'eco_restaurar': g = this.normalizeEcoRestaurar(g); break;
                case 'spaceman': g = this.normalizeSpaceman(g); break;
                case 'par_minimo': g = this.normalizeParMinimo(g); break;
                case 'dictogloss': g = this.normalizeDictogloss(g); break;
                case 'corrector': g = this.normalizeCorrector(g); break;
                case 'brecha': g = this.normalizeBrecha(g); break;
                case 'resumen': g = this.normalizeResumen(g); break;
                case 'registro': g = this.normalizeRegistro(g); break;
                case 'debate': g = this.normalizeDebate(g); break;
                case 'descripcion': g = this.normalizeDescripcion(g); break;
                case 'ritmo': g = this.normalizeRitmo(g); break;
                case 'cronometro': g = this.normalizeCronometro(g); break;
                case 'portafolio': g = this.normalizePortafolio(g); break;
                case 'autoevaluacion': g = this.normalizeAutoevaluacion(g); break;
                case 'negociacion': g = this.normalizeNegociacion(g); break;
                case 'transformador': g = this.normalizeTransformador(g); break;
                case 'bingo': g = this.normalizeBingo(g); break;
                case 'scrabble': g = this.normalizeScrabble(g); break;
                case 'susurro': g = this.normalizeSusurro(g); break;
                case 'eco_lejano': g = this.normalizeEcoLejano(g); break;
                case 'tertulia': g = this.normalizeTertulia(g); break;
                case 'pregonero': g = this.normalizePregonero(g); break;
                case 'raiz': g = this.normalizeRaiz(g); break;
                case 'codice': g = this.normalizeCodice(g); break;
                case 'sombra': g = this.normalizeSombra(g); break;
                case 'oraculo': g = this.normalizeOraculo(g); break;
                case 'tejido': g = this.normalizeTejido(g); break;
                case 'cartografo': g = this.normalizeCartografo(g); break;
                case 'skit': g = this.normalizeSkit(g); break;
                case 'despertar': g = this.normalizeDespertar(g); break;
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

        normalizeDespertar: function(data) {
            /* Canonical: { words: [{word, symbol, audio?}], density: 'heavy'|'moderate'|'light' } */
            if (!data.words) data.words = [];
            if (!data.density) data.density = 'heavy';
            for (var i = 0; i < data.words.length; i++) {
                var w = data.words[i];
                if (typeof w === 'string') {
                    data.words[i] = { word: w, symbol: symbolKey(w) };
                }
                if (!data.words[i].symbol) {
                    data.words[i].symbol = symbolKey(data.words[i].word);
                }
            }
            if (!data.label) data.label = 'El despertar';
            return data;
        },

        normalizeCronica: function(data) {
            /* Canonical: {
                 prompt: string,          — narrative context / Yaguará speaks
                 placeholder: string,     — ghost text in the textarea
                 minWords: number,        — minimum word count (scales with CEFR)
                 maxWords: number,        — maximum word count
                 scaffoldType: string,    — 'word'|'sentence'|'paragraph'|'free'
                 storyKey: string,        — localStorage key fragment for persistence
                 echoPrevious: string,    — optional: student's previous cronica text echoed back
                 vocabularyHints: [],     — optional: suggested words
                 button: string
               } */
            if (!data.prompt) data.prompt = '';
            if (!data.placeholder) data.placeholder = 'Escribe aqu\u00ed...';
            if (!data.minWords) data.minWords = 1;
            if (!data.maxWords) data.maxWords = 200;
            if (!data.scaffoldType) data.scaffoldType = 'sentence';
            if (!data.storyKey) data.storyKey = 'cronica_' + Date.now();
            if (!data.button) data.button = 'Guardar en la cr\u00f3nica';
            return data;
        },

        normalizeCancion: function(data) {
            /* Canonical: {youtubeId, artist, songTitle, lines: [{text, blank?, options?}, ...]} */
            if (!data.lines) data.lines = [];
            /* Ensure each line has at least a text field; map answer→blank */
            for (var i = 0; i < data.lines.length; i++) {
                var line = data.lines[i];
                if (typeof line === 'string') {
                    data.lines[i] = { text: line };
                } else if (line.answer !== undefined && line.blank === undefined) {
                    line.blank = line.answer;
                }
            }
            return data;
        },

        normalizeFlashnote: function(data) {
            /* Canonical: {note, question, answer, options[]} */
            if (!data.note) data.note = '';
            if (!data.question) data.question = '';
            if (!data.answer) data.answer = '';
            if (!data.options) data.options = [data.answer];
            return data;
        },

        normalizeCrossword: function(data) {
            /* Canonical: {clues: [{direction, number, clue, answer}]} */
            if (!data.clues) data.clues = [];
            for (var i = 0; i < data.clues.length; i++) {
                var c = data.clues[i];
                if (!c.direction) c.direction = 'across';
                if (!c.number) c.number = i + 1;
                if (!c.clue) c.clue = '';
                if (!c.answer) c.answer = '';
            }
            return data;
        },

        normalizeExplorador: function(data) {
            /* Canonical: {locations: [{name, text, question, answer, options[]}]} */
            if (!data.locations) data.locations = [];
            for (var i = 0; i < data.locations.length; i++) {
                var loc = data.locations[i];
                if (!loc.name) loc.name = 'Lugar ' + (i + 1);
                if (!loc.text) loc.text = '';
                if (!loc.question) loc.question = '';
                if (!loc.answer) loc.answer = '';
                if (!loc.options) loc.options = [loc.answer];
            }
            return data;
        },

        normalizeKloo: function(data) {
            /* Canonical: {cards: [{color, text}], answer} */
            if (!data.cards) data.cards = [];
            if (!data.answer) data.answer = '';
            for (var i = 0; i < data.cards.length; i++) {
                if (!data.cards[i].color) data.cards[i].color = 'gray';
                if (!data.cards[i].text) data.cards[i].text = '';
            }
            return data;
        },

        normalizeCultura: function(data) {
            if (!data.text) data.text = '';
            if (!data.question) data.question = '';
            if (!data.answer) data.answer = '';
            if (!data.options) data.options = [data.answer];
            return data;
        },

        normalizeSenda: function(data) {
            /* Unify: simple={scenario, choices[{text,consequence}]}, complex={paths[{scene, options[{text,correct,next,feedback}]}]} */
            if (!data.paths && data.choices) {
                data.paths = [{ scene: data.scenario || '', options: data.choices.map(function(c) {
                    return { text: c.text, correct: true, feedback: c.consequence, next: -1 };
                })}];
            }
            if (!data.paths) data.paths = [];
            return data;
        },

        normalizeConsequences: function(data) {
            if (!data.prompts) data.prompts = [];
            for (var i = 0; i < data.prompts.length; i++) {
                if (!data.prompts[i].options) data.prompts[i].options = [];
                if (!data.prompts[i].label) data.prompts[i].label = '';
            }
            return data;
        },

        normalizeMadlibs: function(data) {
            if (!data.template) data.template = '';
            if (!data.blanks) data.blanks = [];
            for (var i = 0; i < data.blanks.length; i++) {
                if (!data.blanks[i].label) data.blanks[i].label = '';
                if (!data.blanks[i].answer) data.blanks[i].answer = '';
                if (!data.blanks[i].options) data.blanks[i].options = [data.blanks[i].answer];
                if (!data.blanks[i].id) data.blanks[i].id = data.blanks[i].label;
            }
            return data;
        },

        normalizeGuardian: function(data) {
            /* Unify waves/questions into questions */
            if (!data.questions && data.waves) data.questions = data.waves;
            if (!data.questions) data.questions = [];
            if (!data.timeLimit) data.timeLimit = 8;
            for (var i = 0; i < data.questions.length; i++) {
                if (!data.questions[i].prompt) data.questions[i].prompt = '';
                if (!data.questions[i].answer) data.questions[i].answer = '';
                if (!data.questions[i].options) data.questions[i].options = [data.questions[i].answer];
            }
            return data;
        },

        normalizeMadgab: function(data) {
            /* Unify phrases/rounds into rounds; scrambled/phonetic into phonetic */
            if (!data.rounds && data.phrases) data.rounds = data.phrases;
            if (!data.rounds) data.rounds = [];
            for (var i = 0; i < data.rounds.length; i++) {
                var r = data.rounds[i];
                if (!r.phonetic && r.scrambled) r.phonetic = r.scrambled;
                if (!r.phonetic) r.phonetic = '';
                if (!r.answer) r.answer = '';
                if (!r.hint) r.hint = '';
            }
            return data;
        },

        normalizeBoggle: function(data) {
            /* Unify letters/grid → grid; targetWords/words → words */
            if (!data.grid && data.letters) data.grid = data.letters;
            if (!data.grid) data.grid = [];
            if (!data.words && data.targetWords) data.words = data.targetWords;
            if (!data.words) data.words = [];
            if (!data.minWords) data.minWords = Math.ceil(data.words.length / 2);
            return data;
        },

        normalizeBananagrams: function(data) {
            if (!data.letters) data.letters = [];
            if (!data.targetWords) data.targetWords = [];
            return data;
        },

        normalizeBingo: function(data) {
            /* Canonical: { words: [str, ...], gridSize: 3|4, callCount: N }
               words = the vocabulary pool. Grid is generated by shuffling a subset.
               callCount = how many correct calls needed to win (default: gridSize). */
            if (!data.words && data.items) {
                data.words = data.items.map(function(it) { return typeof it === 'string' ? it : (it.word || it.text || ''); });
            }
            if (!data.words) data.words = [];
            if (!data.gridSize) data.gridSize = data.words.length >= 16 ? 4 : 3;
            var gridTotal = data.gridSize * data.gridSize;
            if (!data.callCount) data.callCount = Math.min(data.gridSize + 2, gridTotal);
            /* Ensure enough words for the grid */
            while (data.words.length < gridTotal) {
                data.words.push('...');
            }
            return data;
        },

        normalizeScrabble: function(data) {
            /* Canonical: { letters: [char, ...], validWords: [str, ...], targetCount: N }
               Player forms words from letter tiles. validWords accepted as correct.
               targetCount = how many valid words needed to complete. */
            if (!data.letters && data.tiles) data.letters = data.tiles;
            if (!data.letters) {
                /* Generate a reasonable letter pool from validWords */
                var pool = '';
                if (data.validWords) {
                    for (var i = 0; i < data.validWords.length; i++) pool += data.validWords[i];
                }
                /* Add extra common Spanish letters for playability */
                pool += 'aeiouaeiousrnltdcmp';
                data.letters = shuffle(pool.split('')).slice(0, 20);
            }
            if (typeof data.letters === 'string') data.letters = data.letters.split('');
            if (!data.validWords && data.targetWords) data.validWords = data.targetWords;
            if (!data.validWords && data.words) data.validWords = data.words;
            if (!data.validWords) data.validWords = [];
            if (!data.targetCount) data.targetCount = Math.max(3, Math.ceil(data.validWords.length / 2));
            return data;
        },

        normalizeClon: function(data) {
            if (!data.pairs) data.pairs = [];
            for (var i = 0; i < data.pairs.length; i++) {
                if (!data.pairs[i].present) data.pairs[i].present = '';
                if (!data.pairs[i].past) data.pairs[i].past = '';
            }
            return data;
        },

        normalizeConjuro: function(data) {
            /* Unify: spell-based={spells[{prompt,answer,hint}]}, challenge-based={challenge,starters[],evaluation} */
            if (!data.spells && !data.starters) data.spells = [];
            if (data.spells) {
                for (var i = 0; i < data.spells.length; i++) {
                    if (!data.spells[i].prompt) data.spells[i].prompt = '';
                    if (!data.spells[i].answer) data.spells[i].answer = '';
                }
            }
            return data;
        },

        normalizeEcoRestaurar: function(data) {
            /* Unify scenes/zones into scenes */
            if (!data.scenes && data.zones) {
                data.scenes = data.zones.map(function(z) {
                    return {
                        faded: z.grey_description || '',
                        restored: z.correct_name || '',
                        prompt: z.name || '',
                        color: z.color_restored || '',
                        options: z.options || null
                    };
                });
            }
            if (!data.scenes) data.scenes = [];
            return data;
        },

        normalizeSpaceman: function(data) {
            if (!data.phrases) data.phrases = [];
            for (var i = 0; i < data.phrases.length; i++) {
                if (!data.phrases[i].answer) data.phrases[i].answer = '';
                if (!data.phrases[i].hint) data.phrases[i].hint = '';
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
        },

        normalizeParMinimo: function(data) {
            if (!data.pairs) data.pairs = [];
            for (var i = 0; i < data.pairs.length; i++) {
                var p = data.pairs[i];
                if (!p.wordA) p.wordA = '';
                if (!p.wordB) p.wordB = '';
                if (!p.correct) p.correct = 'A';
                if (!p.hint) p.hint = '';
            }
            if (!data.instruction) data.instruction = 'Escucha y elige la palabra correcta.';
            return data;
        },

        normalizeDictogloss: function(data) {
            if (!data.text) data.text = '';
            if (!data.displayTime) data.displayTime = 5000;
            if (!data.keywords) data.keywords = [];
            if (!data.minMatch) data.minMatch = Math.max(1, Math.floor(data.keywords.length * 0.5));
            if (!data.instruction) data.instruction = 'Lee, recuerda, reconstruye.';
            return data;
        },

        normalizeCorrector: function(data) {
            if (!data.passage) data.passage = '';
            if (!data.errors) data.errors = [];
            for (var i = 0; i < data.errors.length; i++) {
                var e = data.errors[i];
                if (e.position === undefined) e.position = 0;
                if (!e.wrong) e.wrong = '';
                if (!e.correct) e.correct = '';
                if (!e.type) e.type = 'grammar';
            }
            if (!data.instruction) data.instruction = 'Encuentra y corrige los errores.';
            return data;
        },

        normalizeBrecha: function(data) {
            if (!data.cardA) data.cardA = { text: '', facts: [] };
            if (!data.cardB) data.cardB = { text: '', facts: [] };
            if (!data.questions) data.questions = [];
            for (var i = 0; i < data.questions.length; i++) {
                var q = data.questions[i];
                if (!q.question) q.question = '';
                if (!q.answer) q.answer = '';
                if (!q.source) q.source = 'B';
                if (!q.options) q.options = [];
            }
            if (!data.instruction) data.instruction = 'Lee tu tarjeta y responde las preguntas.';
            return data;
        },

        normalizeResumen: function(data) {
            if (!data.passage) data.passage = '';
            if (!data.keySentences) data.keySentences = [];
            if (!data.modelSummary) data.modelSummary = '';
            if (!data.maxWords) data.maxWords = 50;
            if (!data.instruction) data.instruction = 'Resume lo esencial.';
            return data;
        },

        normalizeRegistro: function(data) {
            if (!data.situation) data.situation = '';
            if (!data.sourceRegister) data.sourceRegister = '';
            if (!data.sourceText) data.sourceText = '';
            if (!data.targetRegister) data.targetRegister = '';
            if (!data.modelAnswer) data.modelAnswer = '';
            if (!data.keywords) data.keywords = [];
            if (!data.options) data.options = [];
            if (!data.instruction) data.instruction = 'Cambia el registro del texto.';
            return data;
        },

        normalizeDebate: function(data) {
            if (!data.proposition) data.proposition = '';
            if (!data.forArguments) data.forArguments = [];
            if (!data.againstArguments) data.againstArguments = [];
            if (!data.conclusion) data.conclusion = '';
            if (!data.rounds) data.rounds = [];
            for (var i = 0; i < data.rounds.length; i++) {
                var r = data.rounds[i];
                if (!r.prompt) r.prompt = '';
                if (!r.options) r.options = [];
                if (r.correctIndex === undefined) r.correctIndex = 0;
            }
            if (!data.instruction) data.instruction = 'Argumenta tu posición.';
            return data;
        },

        normalizeDescripcion: function(data) {
            if (!data.scene) data.scene = '';
            if (!data.promptQuestion) data.promptQuestion = '';
            if (!data.vocabulary) data.vocabulary = [];
            if (!data.modelAnswer) data.modelAnswer = '';
            if (!data.options) data.options = [];
            if (!data.instruction) data.instruction = 'Describe lo que ves.';
            return data;
        },

        normalizeRitmo: function(data) {
            if (!data.words) data.words = [];
            for (var i = 0; i < data.words.length; i++) {
                var w = data.words[i];
                if (!w.word) w.word = '';
                if (!w.syllables) w.syllables = [];
                if (w.stressIndex === undefined) w.stressIndex = 0;
                if (!w.pattern) w.pattern = '';
            }
            if (!data.instruction) data.instruction = '¿Dónde cae el golpe?';
            return data;
        },

        normalizeCronometro: function(data) {
            if (!data.questions) data.questions = [];
            for (var i = 0; i < data.questions.length; i++) {
                var q = data.questions[i];
                if (!q.prompt) q.prompt = '';
                if (!q.answer) q.answer = '';
                if (!q.options) q.options = [];
                if (!q.timeLimit) q.timeLimit = data.defaultTime || 10;
            }
            if (!data.defaultTime) data.defaultTime = 10;
            if (!data.instruction) data.instruction = 'Responde antes de que el agua llegue.';
            return data;
        },

        normalizePortafolio: function(data) {
            if (!data.prompt) data.prompt = '';
            if (!data.guidingQuestions) data.guidingQuestions = [];
            if (!data.minWords) data.minWords = 10;
            if (!data.instruction) data.instruction = 'Escribe en tu diario de viaje.';
            return data;
        },

        normalizeAutoevaluacion: function(data) {
            if (!data.statements) data.statements = [];
            for (var i = 0; i < data.statements.length; i++) {
                if (!data.statements[i].text) data.statements[i].text = '';
            }
            if (!data.reflection) data.reflection = '';
            if (!data.instruction) data.instruction = '¿Qué sabes ahora?';
            return data;
        },

        normalizeNegociacion: function(data) {
            if (!data.positionA) data.positionA = { speaker: '', text: '' };
            if (!data.positionB) data.positionB = { speaker: '', text: '' };
            if (!data.mediationOptions) data.mediationOptions = [];
            if (data.correctIndex === undefined) data.correctIndex = 0;
            if (!data.modelMediation) data.modelMediation = '';
            if (!data.instruction) data.instruction = 'Encuentra las palabras que ambos puedan aceptar.';
            return data;
        },

        normalizeTransformador: function(data) {
            if (!data.sourceText) data.sourceText = '';
            if (!data.sourceGenre) data.sourceGenre = '';
            if (!data.targetGenre) data.targetGenre = '';
            if (!data.modelAnswer) data.modelAnswer = '';
            if (!data.keywords) data.keywords = [];
            if (!data.options) data.options = [];
            if (!data.instruction) data.instruction = 'Transforma el texto.';
            return data;
        },

        /* --- 10 NEW GAME TYPES (Feb 2026) --- */

        normalizeSusurro: function(data) {
            /* Canonical: {audio, keywords[], minMatch, silenceDuration} */
            if (!data.audio && data.text) data.audio = data.text;
            if (!data.audio && data.sentence) data.audio = data.sentence;
            if (!data.audio) data.audio = '';
            if (!data.keywords) {
                data.keywords = data.audio.split(/\s+/).filter(function(w) { return w.length > 3; });
            }
            if (!data.minMatch) data.minMatch = Math.max(1, Math.ceil(data.keywords.length * 0.5));
            if (!data.silenceDuration) data.silenceDuration = 3000;
            if (!data.instruction) data.instruction = 'Escucha el susurro de la selva y escribe lo que oíste.';
            return data;
        },

        normalizeEcoLejano: function(data) {
            /* Canonical: {passage, readTime, questions: [{q, options, answer}], fadeSpeed} */
            if (!data.passage && data.text) data.passage = data.text;
            if (!data.passage) data.passage = '';
            if (!data.readTime) {
                var wordCount = data.passage.split(/\s+/).length;
                data.readTime = Math.max(5000, wordCount * 400);
            }
            if (!data.questions) data.questions = [];
            for (var i = 0; i < data.questions.length; i++) {
                var q = data.questions[i];
                if (q.question && !q.q) q.q = q.question;
                if (!q.q) q.q = '';
                if (!q.answer && q.correct !== undefined && q.options) {
                    q.answer = typeof q.correct === 'number' ? q.options[q.correct] : q.correct;
                }
                if (!q.answer) q.answer = '';
                if (!q.options) q.options = [];
            }
            if (!data.fadeSpeed) data.fadeSpeed = 800;
            if (!data.instruction) data.instruction = 'Lee con atención. El texto se desvanecerá.';
            return data;
        },

        normalizeTertulia: function(data) {
            /* Canonical: {turns: [{speaker, name, text, avatar?, options, answer}]} */
            if (!data.turns) data.turns = [];
            for (var i = 0; i < data.turns.length; i++) {
                var t = data.turns[i];
                if (!t.speaker) t.speaker = '';
                if (!t.name) t.name = t.speaker;
                if (!t.text) t.text = '';
                if (!t.options) t.options = [];
                if (!t.answer && t.correctIndex !== undefined && t.options.length > 0) {
                    t.answer = t.options[t.correctIndex];
                }
                if (!t.answer) t.answer = '';
            }
            if (!data.instruction) data.instruction = 'Responde con el registro adecuado a cada persona.';
            return data;
        },

        normalizePregonero: function(data) {
            /* Canonical: {situation, register, placeholder, minWords, maxWords, keywords, modelAnswer} */
            if (!data.situation) data.situation = '';
            if (!data.register) data.register = 'formal';
            if (!data.placeholder) data.placeholder = 'Escribe tu pregón...';
            if (!data.minWords) data.minWords = 5;
            if (!data.maxWords) data.maxWords = 100;
            if (!data.keywords) data.keywords = [];
            if (!data.modelAnswer) data.modelAnswer = '';
            if (!data.instruction) data.instruction = 'Eres el pregonero. Escribe el anuncio.';
            return data;
        },

        normalizeRaiz: function(data) {
            /* Canonical: {word, etymology, relatedWords[], distractors[], cognates: [{lang, word}]} */
            if (!data.word) data.word = '';
            if (!data.etymology) data.etymology = '';
            if (!data.relatedWords) data.relatedWords = [];
            if (!data.distractors) data.distractors = [];
            if (!data.cognates) data.cognates = [];
            if (!data.instruction) data.instruction = 'Busca la raíz. Encuentra las palabras hermanas.';
            return data;
        },

        normalizeCodice: function(data) {
            /* Canonical: {passage, highlights: [{word, position, options, answer}]} */
            if (!data.passage && data.text) data.passage = data.text;
            if (!data.passage) data.passage = '';
            if (!data.highlights) data.highlights = [];
            for (var i = 0; i < data.highlights.length; i++) {
                var h = data.highlights[i];
                if (!h.word) h.word = '';
                if (!h.options) h.options = [];
                if (!h.answer && h.correctIndex !== undefined && h.options.length > 0) {
                    h.answer = h.options[h.correctIndex];
                }
                if (!h.answer) h.answer = '';
            }
            if (!data.instruction) data.instruction = 'Descifra el códice. Infiere el significado por el contexto.';
            return data;
        },

        normalizeSombra: function(data) {
            /* Canonical: {audio, answer, timeMultiplier, replayLimit} */
            if (!data.audio && data.text) data.audio = data.text;
            if (!data.audio && data.sentence) data.audio = data.sentence;
            if (!data.audio) data.audio = '';
            if (!data.answer) data.answer = data.audio;
            if (!data.timeMultiplier) data.timeMultiplier = 2.0;
            if (data.replayLimit === undefined) data.replayLimit = 2;
            if (!data.instruction) data.instruction = 'Sé la sombra de la voz. Escucha y escribe.';
            return data;
        },

        normalizeOraculo: function(data) {
            /* Canonical: {scene: [{speaker, text}], options, answer, explanation, character} */
            if (!data.scene) data.scene = [];
            if (data.dialogue && !data.scene.length) data.scene = data.dialogue;
            for (var i = 0; i < data.scene.length; i++) {
                if (!data.scene[i].speaker) data.scene[i].speaker = '';
                if (!data.scene[i].text) data.scene[i].text = '';
            }
            if (!data.options) data.options = [];
            if (!data.answer && data.correctIndex !== undefined && data.options.length > 0) {
                data.answer = data.options[data.correctIndex];
            }
            if (!data.answer) data.answer = '';
            if (!data.explanation) data.explanation = '';
            if (!data.character) data.character = '';
            if (!data.instruction) data.instruction = 'El oráculo pregunta: ¿qué viene después?';
            return data;
        },

        normalizeTejido: function(data) {
            /* Canonical: {fragmentA, fragmentB, mode, items[], connectors[], answer, bridgeKeywords[]} */
            if (!data.fragmentA) data.fragmentA = '';
            if (!data.fragmentB) data.fragmentB = '';
            if (!data.mode) data.mode = 'order'; /* order | connector | bridge */
            if (!data.items) data.items = [];
            if (!data.connectors) data.connectors = [];
            if (!data.answer) data.answer = '';
            if (!data.bridgeKeywords) data.bridgeKeywords = [];
            if (!data.instruction) data.instruction = 'Teje los hilos. Conecta los fragmentos.';
            return data;
        },

        normalizeCartografo: function(data) {
            /* Canonical: {audio, replayLimit, questions: [{q, options, answer}], gridLabels[]} */
            if (!data.audio && data.text) data.audio = data.text;
            if (!data.audio) data.audio = '';
            if (data.replayLimit === undefined) data.replayLimit = 2;
            if (!data.questions) data.questions = [];
            for (var i = 0; i < data.questions.length; i++) {
                var q = data.questions[i];
                if (q.question && !q.q) q.q = q.question;
                if (!q.q) q.q = '';
                if (!q.answer && q.correctIndex !== undefined && q.options) {
                    q.answer = typeof q.correct === 'number' ? q.options[q.correct] : q.correct;
                }
                if (!q.answer) q.answer = '';
                if (!q.options) q.options = [];
            }
            if (!data.gridLabels) data.gridLabels = [];
            if (!data.instruction) data.instruction = 'Escucha la descripción y responde sobre el mapa.';
            return data;
        },

        /* ---------- SKIT (animated micro-scene) ---------- */
        normalizeSkit: function(data) {
            /* Canonical: {beats: [{speaker, name?, animation?, text, tts?, target?, interaction?}]} */
            if (!data.beats) data.beats = [];
            for (var i = 0; i < data.beats.length; i++) {
                var b = data.beats[i];
                if (!b.speaker) b.speaker = 'narrator';
                if (!b.name) b.name = b.speaker;
                if (!b.animation) b.animation = 'fade-in';
                if (!b.text) b.text = '';
                if (b.tts === undefined) b.tts = true;
                if (b.target && !Array.isArray(b.target)) b.target = [b.target];
                if (b.interaction) {
                    if (!b.interaction.type) b.interaction.type = 'pick';
                    if (!b.interaction.options) b.interaction.options = [];
                    if (!b.interaction.answer) b.interaction.answer = '';
                }
            }
            if (!data.label) data.label = 'Escena';
            if (!data.instruction) data.instruction = '';
            return data;
        }
    };

    /* ==========================================================
       7b. INPUT MODE HELPERS — Drag & Voice
    ========================================================== */

    /* ── Character-level diff highlight for self-correction ── */
    function _diffHighlight(student, correct) {
        var result = '';
        var sNorm = normalize(student);
        var cNorm = normalize(correct);
        if (sNorm === cNorm) return '<span class="yg-diff-match">' + student + '</span>';
        for (var i = 0; i < student.length; i++) {
            var sChar = student[i];
            var cChar = correct[i] || '';
            if (sChar.toLowerCase() === cChar.toLowerCase()) {
                result += sChar;
            } else {
                result += '<span class="yg-diff-wrong">' + sChar + '</span>';
            }
        }
        return result;
    }

    /* ── DragHelper: mouse + touch drag-and-drop for chip-to-zone ── */
    var DragHelper = {
        init: function(container, onDrop) {
            var chips = container.querySelectorAll('.yg-drag-chip');
            var zones = container.querySelectorAll('.yg-drop-zone');
            var ghost = null;
            var dragData = null;

            function createGhost(el, x, y) {
                ghost = el.cloneNode(true);
                ghost.className = 'yg-drag-ghost';
                ghost.style.left = x + 'px';
                ghost.style.top = y + 'px';
                document.body.appendChild(ghost);
            }

            function moveGhost(x, y) {
                if (ghost) { ghost.style.left = x + 'px'; ghost.style.top = y + 'px'; }
            }

            function removeGhost() {
                if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
                ghost = null;
            }

            function hitTest(x, y) {
                for (var i = 0; i < zones.length; i++) {
                    var r = zones[i].getBoundingClientRect();
                    if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) return zones[i];
                }
                return null;
            }

            /* Mouse events */
            for (var c = 0; c < chips.length; c++) {
                (function(chip) {
                    chip.setAttribute('draggable', 'true');
                    chip.addEventListener('dragstart', function(e) {
                        dragData = chip.dataset.val;
                        chip.classList.add('yg-dragging');
                        e.dataTransfer.setData('text/plain', dragData);
                        e.dataTransfer.effectAllowed = 'move';
                        if (window.AudioManager) AudioManager.playDragPick();
                    });
                    chip.addEventListener('dragend', function() {
                        chip.classList.remove('yg-dragging');
                        dragData = null;
                    });
                })(chips[c]);
            }

            for (var z = 0; z < zones.length; z++) {
                (function(zone) {
                    zone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        zone.classList.add('drag-over');
                    });
                    zone.addEventListener('dragleave', function() {
                        zone.classList.remove('drag-over');
                    });
                    zone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        zone.classList.remove('drag-over');
                        if (window.AudioManager) AudioManager.playDragDrop();
                        var val = e.dataTransfer.getData('text/plain');
                        if (val) onDrop(val, zone);
                    });
                })(zones[z]);
            }

            /* Touch events (mobile) */
            for (var t = 0; t < chips.length; t++) {
                (function(chip) {
                    chip.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        var touch = e.touches[0];
                        dragData = chip.dataset.val;
                        chip.classList.add('yg-dragging');
                        createGhost(chip, touch.pageX - 30, touch.pageY - 20);
                    }, { passive: false });

                    chip.addEventListener('touchmove', function(e) {
                        e.preventDefault();
                        var touch = e.touches[0];
                        moveGhost(touch.pageX - 30, touch.pageY - 20);
                        var zone = hitTest(touch.clientX, touch.clientY);
                        for (var i = 0; i < zones.length; i++) zones[i].classList.remove('drag-over');
                        if (zone) zone.classList.add('drag-over');
                    }, { passive: false });

                    chip.addEventListener('touchend', function(e) {
                        chip.classList.remove('yg-dragging');
                        removeGhost();
                        if (!dragData) return;
                        var touch = e.changedTouches[0];
                        var zone = hitTest(touch.clientX, touch.clientY);
                        if (zone) {
                            zone.classList.remove('drag-over');
                            onDrop(dragData, zone);
                        }
                        dragData = null;
                    });
                })(chips[t]);
            }
        }
    };

    /* ── VoiceInput: Web Speech API wrapper with fallback ── */
    var VoiceInput = {
        isSupported: function() {
            return !!(window.SpeechRecognition || window.webkitSpeechRecognition);
        },

        listen: function(lang, callback) {
            var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) { callback(null, 'not_supported'); return; }

            var recognition = new SR();
            recognition.lang = lang || 'es-419';
            recognition.maxAlternatives = 3;
            recognition.interimResults = false;
            recognition.continuous = false;

            var timeout = setTimeout(function() {
                recognition.abort();
                callback(null, 'timeout');
            }, 5000);

            recognition.onresult = function(e) {
                clearTimeout(timeout);
                var results = [];
                for (var i = 0; i < e.results[0].length; i++) {
                    results.push(e.results[0][i].transcript);
                }
                callback(results, null);
            };

            recognition.onerror = function(e) {
                clearTimeout(timeout);
                callback(null, e.error || 'error');
            };

            recognition.onend = function() {
                clearTimeout(timeout);
            };

            recognition.start();
            return recognition;
        }
    };

    /* ==========================================================
       8. ARCHETYPE RENDERERS
    ========================================================== */
    var Renderers = {};

    /* ----- PAIR MATCHING (NamingRitual) ----- */
    Renderers.pair = {
        render: function(data, container, state, onComplete) {
            var density = data.symbolAssist ? getSymbolDensity(Engine._config.cefr, Engine._config.destNum) : null;
            var cards = [];
            for (var i = 0; i < data.pairs.length; i++) {
                cards.push({ pairId: i, side: 0, text: data.pairs[i][0] });
                cards.push({ pairId: i, side: 1, text: data.pairs[i][1] });
            }
            cards = shuffle(cards);

            var html = renderTypeLabel(data.label || 'Emparejar', 'pair') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-pair-grid" id="ygPairGrid" role="grid" aria-label="Tarjetas para emparejar">';
            for (var c = 0; c < cards.length; c++) {
                var symHtml = density ? renderSymbolAssist(cards[c].text, null, density) : '';
                html += '<div class="yg-pair-card" data-pair="' + cards[c].pairId + '" data-side="' + cards[c].side + '" tabindex="0" role="button" aria-label="Tarjeta ' + (c + 1) + ', voltear para ver">' +
                            '<div class="yg-card-face yg-card-back">&#9679;</div>' +
                            '<div class="yg-card-face yg-card-front">' + symHtml + cards[c].text + '</div>' +
                        '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            /* Accessibility: announce game type */
            A11y.announce('Emparejar: ' + (data.instruction || data.title || 'Encuentra las parejas'));

            state.flipped = [];
            state.matched = 0;
            state.total = data.pairs.length;
            state.busy = false;

            var grid = document.getElementById('ygPairGrid');
            /* Keyboard: Enter/Space to flip cards, arrow keys to navigate */
            A11y.bindActivateKeys(grid, '.yg-pair-card');
            A11y.bindArrowNav(grid, '.yg-pair-card');
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
                            if (window.AudioManager) AudioManager.playPairMatch();
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
            var density = data.symbolAssist ? getSymbolDensity(Engine._config.cefr, Engine._config.destNum) : null;
            state.subIdx = state.subIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;

            /* Model sentence exposure: show + speak the full sentence before each blank */
            if (data.modelSentence && !state.modelShown) {
                state.modelShown = true;
                var mHtml = renderTypeLabel(data.label || 'Completar', 'fill') +
                    '<div class="yg-fib-sentence">' + data.modelSentence + '</div>' +
                    '<button class="yg-listo-btn" id="ygModelContinue">\u2192</button>';
                container.innerHTML = mHtml;
                Audio.speak(data.modelSentence, { rate: 0.55 });
                var self = this;
                document.getElementById('ygModelContinue').addEventListener('click', function() {
                    self.render(data, container, state, onComplete);
                });
                return;
            }

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

            if (q._dragMode && q.options) {
                /* ── DRAG MODE: draggable chips → drop zone ── */
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-drag-chips" id="ygDragChips">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<span class="yg-drag-chip" data-val="' + opts[i] + '" tabindex="0" aria-label="' + opts[i] + '">' + opts[i] + '</span>';
                }
                html += '</div>';
            } else if (q._selfCorrection) {
                /* ── SELF-CORRECTION: text input → comparison panel ── */
                html += '<input type="text" class="yg-dict-input" id="ygFillInput" placeholder="Escribe tu respuesta..." value="" autocomplete="off" autocapitalize="sentences" aria-label="Escribe tu respuesta">';
                html += '<button class="yg-listo-btn" id="ygFillListo" aria-label="Enviar respuesta">Listo</button>';
            } else if (q._voiceMode) {
                /* ── VOICE MODE: mic button + fallback ── */
                html += '<div class="yg-voice-area" id="ygVoiceArea">';
                html += '<button class="yg-voice-btn" id="ygVoiceBtn" aria-label="Hablar respuesta">';
                html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>';
                html += '</button>';
                html += '<div class="yg-voice-transcript" id="ygVoiceTranscript"></div>';
                html += '<a href="#" class="yg-voice-fallback" id="ygVoiceFallback">Escribir</a>';
                html += '</div>';
            } else if (q.options) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-fib-options" id="ygFibOptions" role="radiogroup" aria-label="Opciones de respuesta">';
                for (var i = 0; i < opts.length; i++) {
                    var sel = (state.answers[state.subIdx] === opts[i]) ? ' selected' : '';
                    var symHtml = density ? renderSymbolAssist(opts[i], null, density) : '';
                    html += '<div class="yg-fib-option' + sel + '" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="' + (sel ? 'true' : 'false') + '" aria-label="' + opts[i] + '">' + symHtml + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                /* Free text input */
                html += '<input type="text" class="yg-dict-input" id="ygFillInput" placeholder="Escribe tu respuesta..." value="' + (state.answers[state.subIdx] || '') + '" autocomplete="off" autocapitalize="sentences" aria-label="Escribe tu respuesta">';
                if (q.hint) html += '<div class="yg-dict-hint">' + q.hint + '</div>';
                html += '<button class="yg-listo-btn" id="ygFillListo" aria-label="Enviar respuesta">Listo</button>';
            }

            container.innerHTML = html;

            /* Accessibility: announce game type */
            A11y.announce('Completar: ' + (data.instruction || data.title || 'Completa la frase'));

            var self = this;
            if (q._dragMode && q.options) {
                /* ── DRAG event wiring ── */
                /* Make the blank a drop zone */
                var blankEl = document.getElementById('ygFibBlank');
                blankEl.classList.add('yg-drop-zone');
                DragHelper.init(container, function(val) {
                    if (state.resolved) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;
                    blankEl.textContent = val;
                    blankEl.classList.remove('yg-drop-zone');

                    /* Disable remaining chips */
                    var chips = container.querySelectorAll('.yg-drag-chip');
                    for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, blankEl, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, blankEl, function() {
                            state.resolved = false;
                            blankEl.classList.add('yg-drop-zone');
                            blankEl.textContent = '______';
                            for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = '';
                        });
                    }
                });
                /* Also allow click/tap on chips as fallback */
                var chipContainer = document.getElementById('ygDragChips');
                chipContainer.addEventListener('click', function(e) {
                    var chip = e.target.closest('.yg-drag-chip');
                    if (!chip || state.resolved) return;
                    state.resolved = true;
                    var val = chip.dataset.val;
                    state.answers[state.subIdx] = val;
                    blankEl.textContent = val;
                    blankEl.classList.remove('yg-drop-zone');

                    var chips = container.querySelectorAll('.yg-drag-chip');
                    for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, blankEl, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, blankEl, function() {
                            state.resolved = false;
                            blankEl.classList.add('yg-drop-zone');
                            blankEl.textContent = '______';
                            for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = '';
                        });
                    }
                });
            } else if (q._selfCorrection) {
                /* ── SELF-CORRECTION event wiring ── */
                var scInput = document.getElementById('ygFillInput');
                document.getElementById('ygFillListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var val = (scInput.value || '').trim();
                    if (!val) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;

                    /* Replace input area with comparison panel */
                    var isCorrect = normalize(val) === normalize(q.answer);
                    var panel = '<div class="yg-self-compare">' +
                        '<div class="yg-self-yours"><span class="yg-self-label">Tu respuesta</span>' + _diffHighlight(val, q.answer) + '</div>' +
                        '<div class="yg-self-correct"><span class="yg-self-label">Respuesta correcta</span>' + q.answer + '</div>' +
                        '</div>';
                    panel += '<div class="yg-self-buttons">';
                    if (isCorrect) {
                        panel += '<button class="yg-self-btn-correct" id="ygSelfCorrect">Lo sab\u00eda</button>';
                    } else {
                        panel += '<button class="yg-self-btn-correct" id="ygSelfCorrect">Lo sab\u00eda</button>';
                        panel += '<button class="yg-self-btn-retry" id="ygSelfRetry">Necesito practicar</button>';
                    }
                    panel += '</div>';

                    scInput.style.display = 'none';
                    document.getElementById('ygFillListo').style.display = 'none';
                    var panelDiv = document.createElement('div');
                    panelDiv.innerHTML = panel;
                    container.appendChild(panelDiv);

                    var correctBtn = document.getElementById('ygSelfCorrect');
                    if (correctBtn) correctBtn.addEventListener('click', function() {
                        WorldReaction.harmony(container, correctBtn, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    });
                    var retryBtn = document.getElementById('ygSelfRetry');
                    if (retryBtn) retryBtn.addEventListener('click', function() {
                        WorldReaction.desequilibrio(container, retryBtn, function() {
                            state.resolved = false;
                            self.render(data, container, state, onComplete);
                        });
                    });
                });
            } else if (q._voiceMode) {
                /* ── VOICE event wiring ── */
                var voiceBtn = document.getElementById('ygVoiceBtn');
                var transcript = document.getElementById('ygVoiceTranscript');
                var fallbackLink = document.getElementById('ygVoiceFallback');

                if (!VoiceInput.isSupported()) {
                    /* Fallback: swap to typing */
                    delete q._voiceMode;
                    if (q._originalOptions) { q.options = q._originalOptions; delete q._originalOptions; }
                    self.render(data, container, state, onComplete);
                    return;
                }

                voiceBtn.addEventListener('click', function() {
                    if (state.resolved) return;
                    voiceBtn.classList.add('listening');
                    transcript.textContent = 'Escuchando...';
                    if (window.AudioManager) AudioManager.playVoiceStart();

                    VoiceInput.listen('es-419', function(results, err) {
                        voiceBtn.classList.remove('listening');
                        if (window.AudioManager) AudioManager.playVoiceEnd();
                        if (err || !results || !results.length) {
                            transcript.textContent = err === 'timeout' ? 'No se detect\u00f3 voz. Int\u00e9ntalo de nuevo.' : 'Error. Int\u00e9ntalo de nuevo.';
                            return;
                        }

                        /* Check all alternatives */
                        var bestMatch = null;
                        for (var r = 0; r < results.length; r++) {
                            if (normalize(results[r]) === normalize(q.answer)) {
                                bestMatch = results[r]; break;
                            }
                        }

                        var spoken = bestMatch || results[0];
                        transcript.textContent = spoken;
                        state.resolved = true;
                        state.answers[state.subIdx] = spoken;
                        document.getElementById('ygFibBlank').textContent = spoken;

                        var isCorrect = !!bestMatch;
                        if (isCorrect) {
                            WorldReaction.harmony(container, transcript, function() {
                                self._advance(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, transcript, function() {
                                state.resolved = false;
                                transcript.textContent = '';
                                var correctSentence = (q.sentence || q.text || '').replace(/___+|_+/, q.answer);
                                Audio.speak(correctSentence, { rate: 0.5 });
                            });
                        }
                    });
                });

                fallbackLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    delete q._voiceMode;
                    if (q._originalOptions) { q.options = q._originalOptions; delete q._originalOptions; }
                    self.render(data, container, state, onComplete);
                });
            } else if (q.options) {
                var optContainer = document.getElementById('ygFibOptions');
                /* Keyboard: Enter/Space to select, arrow keys to navigate */
                A11y.bindActivateKeys(optContainer, '.yg-fib-option');
                A11y.bindArrowNav(optContainer, '.yg-fib-option');
                optContainer.addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-fib-option');
                    if (!el || state.resolved) return;
                    A11y.selectOption(el, optContainer, '.yg-fib-option');
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
                            /* Speak the correct sentence so the student hears the model before retrying */
                            var correctSentence = (q.sentence || q.text || '').replace(/___+|_+/, q.answer);
                            Audio.speak(correctSentence, { rate: 0.5 });
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
                            /* Speak the correct sentence so the student hears the model before retrying */
                            var correctSentence = (q.sentence || q.text || '').replace(/___+|_+/, q.answer);
                            Audio.speak(correctSentence, { rate: 0.5 });
                        });
                    }
                });
            }
        },

        _advance: function(data, container, state, onComplete) {
            if (state.subIdx < data.questions.length - 1) {
                state.subIdx++;
                state.resolved = false;
                if (data.modelSentence) state.modelShown = false;
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
            var density = data.symbolAssist ? getSymbolDensity(Engine._config.cefr, Engine._config.destNum) : null;
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

            var subjectSym = density ? renderSymbolAssist(q.subject, null, density) : '';
            html += '<div class="yg-vc-prompt">' +
                        '<div class="yg-vc-verb">' + q.verb + '</div>' +
                        '<div class="yg-vc-subject">' + subjectSym + '(' + q.subject + ')</div>' +
                        (q.context ? '<div class="yg-vc-context">' + q.context + '</div>' : '') +
                    '</div>';

            if (q._dragMode && q.options) {
                /* ── DRAG MODE ── */
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-drag-chips" id="ygDragChips">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<span class="yg-drag-chip" data-val="' + opts[i] + '" tabindex="0" aria-label="' + opts[i] + '">' + opts[i] + '</span>';
                }
                html += '</div>';
                html += '<div class="yg-drop-zone" id="ygConjDrop" aria-label="Suelta tu respuesta aqu\u00ed">Suelta aqu\u00ed</div>';
            } else if (q._selfCorrection) {
                /* ── SELF-CORRECTION ── */
                html += '<input type="text" class="yg-dict-input" id="ygConjInput" placeholder="Escribe la conjugaci\u00f3n..." value="" autocomplete="off" aria-label="Escribe la conjugaci\u00f3n">';
                html += '<button class="yg-listo-btn" id="ygConjListo" aria-label="Enviar respuesta">Listo</button>';
            } else if (q._voiceMode) {
                /* ── VOICE MODE ── */
                html += '<div class="yg-voice-area" id="ygVoiceArea">';
                html += '<button class="yg-voice-btn" id="ygVoiceBtn" aria-label="Hablar respuesta">';
                html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>';
                html += '</button>';
                html += '<div class="yg-voice-transcript" id="ygVoiceTranscript"></div>';
                html += '<a href="#" class="yg-voice-fallback" id="ygVoiceFallback">Escribir</a>';
                html += '</div>';
            } else if (q.options) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-vc-options" id="ygVcOptions" role="radiogroup" aria-label="Opciones de conjugaci\u00f3n">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-vc-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygConjInput" placeholder="Escribe la conjugaci\u00f3n..." value="" autocomplete="off" aria-label="Escribe la conjugaci\u00f3n">';
                html += '<button class="yg-listo-btn" id="ygConjListo" aria-label="Enviar respuesta">Listo</button>';
            }

            container.innerHTML = html;

            /* Accessibility: announce game type */
            A11y.announce('Conjugaci\u00f3n: ' + q.verb + ' (' + q.subject + ')');

            var self = this;
            if (q._dragMode && q.options) {
                /* ── DRAG event wiring ── */
                var dropZone = document.getElementById('ygConjDrop');
                DragHelper.init(container, function(val) {
                    if (state.resolved) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;
                    dropZone.textContent = val;
                    dropZone.classList.add('yg-drop-filled');

                    var chips = container.querySelectorAll('.yg-drag-chip');
                    for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, dropZone, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, dropZone, function() {
                            state.resolved = false;
                            dropZone.textContent = 'Suelta aqu\u00ed';
                            dropZone.classList.remove('yg-drop-filled');
                            for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = '';
                        });
                    }
                });
                /* Click fallback */
                document.getElementById('ygDragChips').addEventListener('click', function(e) {
                    var chip = e.target.closest('.yg-drag-chip');
                    if (!chip || state.resolved) return;
                    state.resolved = true;
                    var val = chip.dataset.val;
                    state.answers[state.subIdx] = val;
                    dropZone.textContent = val;
                    dropZone.classList.add('yg-drop-filled');

                    var chips = container.querySelectorAll('.yg-drag-chip');
                    for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = 'none';

                    var isCorrect = normalize(val) === normalize(q.answer);
                    if (isCorrect) {
                        WorldReaction.harmony(container, dropZone, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, dropZone, function() {
                            state.resolved = false;
                            dropZone.textContent = 'Suelta aqu\u00ed';
                            dropZone.classList.remove('yg-drop-filled');
                            for (var j = 0; j < chips.length; j++) chips[j].style.pointerEvents = '';
                        });
                    }
                });
            } else if (q._selfCorrection) {
                /* ── SELF-CORRECTION event wiring ── */
                document.getElementById('ygConjListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var input = document.getElementById('ygConjInput');
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;
                    state.answers[state.subIdx] = val;

                    var isCorrect = normalize(val) === normalize(q.answer);
                    var panel = '<div class="yg-self-compare">' +
                        '<div class="yg-self-yours"><span class="yg-self-label">Tu respuesta</span>' + _diffHighlight(val, q.answer) + '</div>' +
                        '<div class="yg-self-correct"><span class="yg-self-label">Respuesta correcta</span>' + q.answer + '</div>' +
                        '</div>';
                    panel += '<div class="yg-self-buttons">';
                    if (isCorrect) {
                        panel += '<button class="yg-self-btn-correct" id="ygSelfCorrect">Lo sab\u00eda</button>';
                    } else {
                        panel += '<button class="yg-self-btn-correct" id="ygSelfCorrect">Lo sab\u00eda</button>';
                        panel += '<button class="yg-self-btn-retry" id="ygSelfRetry">Necesito practicar</button>';
                    }
                    panel += '</div>';

                    input.style.display = 'none';
                    document.getElementById('ygConjListo').style.display = 'none';
                    var panelDiv = document.createElement('div');
                    panelDiv.innerHTML = panel;
                    container.appendChild(panelDiv);

                    var correctBtn = document.getElementById('ygSelfCorrect');
                    if (correctBtn) correctBtn.addEventListener('click', function() {
                        WorldReaction.harmony(container, correctBtn, function() {
                            self._advance(data, container, state, onComplete);
                        });
                    });
                    var retryBtn = document.getElementById('ygSelfRetry');
                    if (retryBtn) retryBtn.addEventListener('click', function() {
                        WorldReaction.desequilibrio(container, retryBtn, function() {
                            state.resolved = false;
                            self.render(data, container, state, onComplete);
                        });
                    });
                });
            } else if (q._voiceMode) {
                /* ── VOICE event wiring ── */
                var voiceBtn = document.getElementById('ygVoiceBtn');
                var transcript = document.getElementById('ygVoiceTranscript');
                var fallbackLink = document.getElementById('ygVoiceFallback');

                if (!VoiceInput.isSupported()) {
                    delete q._voiceMode;
                    if (q._originalOptions) { q.options = q._originalOptions; delete q._originalOptions; }
                    self.render(data, container, state, onComplete);
                    return;
                }

                voiceBtn.addEventListener('click', function() {
                    if (state.resolved) return;
                    voiceBtn.classList.add('listening');
                    transcript.textContent = 'Escuchando...';
                    if (window.AudioManager) AudioManager.playVoiceStart();

                    VoiceInput.listen('es-419', function(results, err) {
                        voiceBtn.classList.remove('listening');
                        if (window.AudioManager) AudioManager.playVoiceEnd();
                        if (err || !results || !results.length) {
                            transcript.textContent = err === 'timeout' ? 'No se detect\u00f3 voz. Int\u00e9ntalo de nuevo.' : 'Error. Int\u00e9ntalo de nuevo.';
                            return;
                        }

                        var bestMatch = null;
                        for (var r = 0; r < results.length; r++) {
                            if (normalize(results[r]) === normalize(q.answer)) { bestMatch = results[r]; break; }
                        }

                        var spoken = bestMatch || results[0];
                        transcript.textContent = spoken;
                        state.resolved = true;
                        state.answers[state.subIdx] = spoken;

                        if (bestMatch) {
                            WorldReaction.harmony(container, transcript, function() {
                                self._advance(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, transcript, function() {
                                state.resolved = false;
                                transcript.textContent = '';
                            });
                        }
                    });
                });

                fallbackLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    delete q._voiceMode;
                    if (q._originalOptions) { q.options = q._originalOptions; delete q._originalOptions; }
                    self.render(data, container, state, onComplete);
                });
            } else if (q.options) {
                var vcOptContainer = document.getElementById('ygVcOptions');
                A11y.bindActivateKeys(vcOptContainer, '.yg-vc-option');
                A11y.bindArrowNav(vcOptContainer, '.yg-vc-option');
                vcOptContainer.addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-vc-option');
                    if (!el || state.resolved) return;
                    A11y.selectOption(el, vcOptContainer, '.yg-vc-option');
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
            var density = data.symbolAssist ? getSymbolDensity(Engine._config.cefr, Engine._config.destNum) : null;
            state.selected = null;
            state.resolved = false;
            var opts = shuffle((data.options || []).slice());

            var html = renderTypeLabel(data.label || 'Escuchar', 'listening') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-listen-area">' +
                           '<button class="yg-listen-btn" id="ygListenBtn" aria-label="Reproducir audio">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn" aria-label="Repetir audio">' + ICONS.repeat + '</button>' +
                       '</div>';

            /* Caption toggle for audio game type */
            html += A11y.buildCaptionToggle(data.audio);

            if (data.question) {
                html += '<div class="yg-listen-question">' + data.question + '</div>';
            }

            html += '<div class="yg-listen-options" id="ygListenOpts" role="radiogroup" aria-label="Opciones de respuesta">';
            for (var i = 0; i < opts.length; i++) {
                var symHtml = density ? renderSymbolAssist(opts[i], null, density) : '';
                html += '<div class="yg-listen-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + symHtml + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            /* Accessibility: announce game type and bind captions */
            A11y.announce('Escuchar: ' + (data.instruction || data.title || 'Escucha y selecciona'));
            A11y.bindCaptionToggle();

            document.getElementById('ygListenBtn').addEventListener('click', function() { Audio.speak(data.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { Audio.speak(data.audio); });

            setTimeout(function() { Audio.speak(data.audio); }, 400);

            var listenOptContainer = document.getElementById('ygListenOpts');
            A11y.bindActivateKeys(listenOptContainer, '.yg-listen-option');
            A11y.bindArrowNav(listenOptContainer, '.yg-listen-option');
            listenOptContainer.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-listen-option');
                if (!el || state.resolved) return;
                A11y.selectOption(el, listenOptContainer, '.yg-listen-option');
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

            /* Model sentence exposure: show + speak the full sentence before each word */
            if (data.modelSentence && !state.modelShown) {
                state.modelShown = true;
                var mHtml = renderTypeLabel(data.label || 'Escuchar', 'listening') +
                    '<div class="yg-fib-sentence">' + data.modelSentence + '</div>' +
                    '<button class="yg-listo-btn" id="ygModelContinue">\u2192</button>';
                container.innerHTML = mHtml;
                Audio.speak(data.modelSentence, { rate: 0.55 });
                var self = this;
                document.getElementById('ygModelContinue').addEventListener('click', function() {
                    self._renderMulti(data, container, state, onComplete);
                });
                return;
            }

            var answer = typeof q.correct === 'number' ? q.options[q.correct] : (q.correct || q.answer || '');
            var opts = shuffle(q.options.slice());

            var html = renderTypeLabel(data.label || 'Escuchar', 'listening') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-sub-counter">Pregunta ' + (state.subIdx + 1) + ' de ' + questions.length + '</div>' +
                       '<div class="yg-listen-area">' +
                           '<button class="yg-listen-btn" id="ygListenBtn" aria-label="Reproducir audio">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn" aria-label="Repetir audio">' + ICONS.repeat + '</button>' +
                       '</div>';

            html += A11y.buildCaptionToggle(q.audio);

            if (q.question) {
                html += '<div class="yg-listen-question">' + q.question + '</div>';
            }

            html += '<div class="yg-listen-options" id="ygListenOpts" role="radiogroup" aria-label="Opciones de respuesta">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-listen-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            A11y.bindCaptionToggle();

            document.getElementById('ygListenBtn').addEventListener('click', function() { Audio.speak(q.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { Audio.speak(q.audio); });
            setTimeout(function() { Audio.speak(q.audio); }, 400);

            var multiListenOpts = document.getElementById('ygListenOpts');
            A11y.bindActivateKeys(multiListenOpts, '.yg-listen-option');
            A11y.bindArrowNav(multiListenOpts, '.yg-listen-option');

            var self = this;
            multiListenOpts.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-listen-option');
                if (!el || state.resolved) return;
                A11y.selectOption(el, multiListenOpts, '.yg-listen-option');
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
                            if (data.modelSentence) state.modelShown = false;
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
            var density = data.symbolAssist ? getSymbolDensity(Engine._config.cefr, Engine._config.destNum) : null;
            state.placements = state.placements || {};
            state.activeItem = null;
            var items = data.items;

            var html = renderTypeLabel(data.label || 'Clasificar', 'category') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>' +
                       '<div class="yg-sort-hint">Haz clic en un elemento, luego en la categor\u00eda.</div>' +
                       '<div class="yg-sort-items" id="ygSortItems" role="listbox" aria-label="Elementos para clasificar">';
            for (var i = 0; i < items.length; i++) {
                var sorted = state.placements[items[i].text] ? ' sorted' : '';
                var symHtml = density ? renderSymbolAssist(items[i].text, null, density) : '';
                html += '<div class="yg-sort-item' + sorted + '" data-item="' + items[i].text + '" role="option" tabindex="0" aria-label="' + items[i].text + '">' + symHtml + items[i].text + '</div>';
            }
            html += '</div><div class="yg-sort-categories" id="ygSortCats">';
            for (var c = 0; c < data.categories.length; c++) {
                html += '<div class="yg-sort-category" data-cat="' + data.categories[c] + '" role="group" aria-label="Categor\u00eda: ' + data.categories[c] + '" tabindex="0">' +
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

            /* Accessibility: announce game type */
            A11y.announce('Clasificar: ' + (data.instruction || data.title || 'Clasifica los elementos'));

            var sortItems = document.getElementById('ygSortItems');
            var sortCats = document.getElementById('ygSortCats');
            A11y.bindActivateKeys(sortItems, '.yg-sort-item');
            A11y.bindArrowNav(sortItems, '.yg-sort-item');
            A11y.bindActivateKeys(sortCats, '.yg-sort-category');
            A11y.bindArrowNav(sortCats, '.yg-sort-category');

            sortItems.addEventListener('click', function(e) {
                var el = e.target.closest('.yg-sort-item');
                if (!el || el.classList.contains('sorted')) return;
                var allItems = document.querySelectorAll('.yg-sort-item');
                for (var j = 0; j < allItems.length; j++) { allItems[j].classList.remove('active-item'); allItems[j].setAttribute('aria-selected', 'false'); }
                el.classList.add('active-item');
                el.setAttribute('aria-selected', 'true');
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

            /* Model sentence exposure: show + speak the full sentence before assembly */
            if (data.modelSentence && !state.modelShown) {
                state.modelShown = true;
                var mHtml = renderTypeLabel(data.label || 'Construir', 'builder') +
                    '<div class="yg-fib-sentence">' + data.modelSentence + '</div>' +
                    '<button class="yg-listo-btn" id="ygModelContinue">\u2192</button>';
                container.innerHTML = mHtml;
                Audio.speak(data.modelSentence, { rate: 0.55 });
                var self = this;
                document.getElementById('ygModelContinue').addEventListener('click', function() {
                    self.render(data, container, state, onComplete);
                });
                return;
            }

            state.built = state.built || [];
            state.usedIndices = state.usedIndices || {};
            if (!state._shuffled) {
                state._shuffled = shuffle(data.words.map(function(w, i) { return { word: w, origIdx: i }; }));
            }
            var shuffled = state._shuffled;

            var html = renderTypeLabel(data.label || 'Construir', 'builder') +
                       '<div class="yg-instruction">' + (data.instruction || data.title || '') + '</div>';

            html += '<div class="yg-sb-target" id="ygSbTarget" role="listbox" aria-label="Frase construida">';
            if (state.built.length === 0) {
                html += '<span class="yg-sb-placeholder">Haz clic en las palabras para construir la frase...</span>';
            } else {
                for (var b = 0; b < state.built.length; b++) {
                    html += '<span class="yg-sb-chip in-target" data-built-idx="' + b + '" role="option" tabindex="0" aria-label="Quitar: ' + state.built[b] + '">' + state.built[b] + '</span>';
                }
            }
            html += '</div>';

            html += '<div class="yg-sb-bank" id="ygSbBank" role="listbox" aria-label="Palabras disponibles">';
            for (var i = 0; i < shuffled.length; i++) {
                var used = state.usedIndices[i] ? ' used' : '';
                var ariaGrabbed = state.usedIndices[i] ? 'true' : 'false';
                html += '<span class="yg-sb-chip' + used + '" data-bank-idx="' + i + '" role="option" tabindex="0" aria-grabbed="' + ariaGrabbed + '" aria-label="' + shuffled[i].word + '">' + shuffled[i].word + '</span>';
            }
            html += '</div>';
            html += '<button class="yg-sb-clear" id="ygSbClear" aria-label="Borrar frase">\u21BB Borrar</button> ';
            html += '<button class="yg-listo-btn" id="ygSbListo" aria-label="Enviar frase">Listo</button>';
            container.innerHTML = html;

            /* Accessibility: announce game type */
            A11y.announce('Construir: ' + (data.instruction || data.title || 'Construye la frase'));

            var sbBank = document.getElementById('ygSbBank');
            var sbTarget = document.getElementById('ygSbTarget');
            A11y.bindActivateKeys(sbBank, '.yg-sb-chip');
            A11y.bindArrowNav(sbBank, '.yg-sb-chip');
            A11y.bindActivateKeys(sbTarget, '.yg-sb-chip.in-target');

            var self = this;
            sbBank.addEventListener('click', function(e) {
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

            var convOpts = shuffle(turn.options.slice());
            html += '<div class="yg-conv-options" id="ygConvOptions">';
            for (var i = 0; i < convOpts.length; i++) {
                html += '<div class="yg-conv-option" data-val="' + convOpts[i] + '">' + convOpts[i] + '</div>';
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

                /* Replace mystery bubble with answer — preserve speaker name */
                var mystery = document.querySelector('.yg-conv-bubble.mystery');
                var speakerName = mystery ? (mystery.querySelector('.yg-conv-speaker') || {}).textContent || 'Tú' : 'Tú';
                if (mystery) {
                    mystery.innerHTML = '<div class="yg-conv-speaker">' + speakerName + '</div>' + el.dataset.val;
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
                            mystery.innerHTML = '<div class="yg-conv-speaker">' + speakerName + '</div>???';
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
                           '<button class="yg-listen-btn" id="ygListenBtn" aria-label="Reproducir audio">' + ICONS.speaker + '</button>' +
                           '<button class="yg-repeat-btn" id="ygRepeatBtn" aria-label="Repetir audio">' + ICONS.repeat + '</button>' +
                       '</div>';
            html += A11y.buildCaptionToggle(data.audio);
            html += '<input type="text" class="yg-dict-input" id="ygDictInput" placeholder="Escribe lo que oyes aqu\u00ed..." value="' + (state.typed || '') + '" autocomplete="off" autocapitalize="sentences" aria-label="Escribe lo que oyes">';
            if (data.hint) html += '<div class="yg-dict-hint">' + data.hint + '</div>';
            html += '<button class="yg-listo-btn" id="ygDictListo" aria-label="Enviar respuesta">Listo</button>';
            container.innerHTML = html;

            /* Accessibility: announce game type and bind captions */
            A11y.announce('Dictado: ' + (data.instruction || data.title || 'Escucha y escribe'));
            A11y.bindCaptionToggle();

            document.getElementById('ygListenBtn').addEventListener('click', function() { if (data.audio) Audio.speak(data.audio); });
            document.getElementById('ygRepeatBtn').addEventListener('click', function() { if (data.audio) Audio.speak(data.audio); });
            if (data.audio) setTimeout(function() { Audio.speak(data.audio); }, 400);

            var input = document.getElementById('ygDictInput');
            input.addEventListener('input', function() { state.typed = input.value; });

            document.getElementById('ygDictListo').addEventListener('click', function() {
                if (state.resolved) return;
                var val = (input.value || '').trim();
                if (!val) return;
                state.resolved = true;
                state.typed = val;

                var normVal = normalize(val);
                var normAns = normalize(data.answer || '');
                var isCorrect = normVal === normAns || _levenshtein(normVal, normAns) <= Math.max(1, Math.floor(normAns.length * 0.15));
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
                            '<div class="yg-story-q-options" role="radiogroup" aria-label="Pregunta ' + (q + 1) + '">';
                for (var o = 0; o < quest.options.length; o++) {
                    var sel = (state.answers[q] === quest.options[o]) ? ' selected' : '';
                    var disabled = (state.answers[q] !== undefined) ? ' style="pointer-events:none"' : '';
                    html += '<div class="yg-story-q-option' + sel + '" data-qidx="' + q + '" data-val="' + quest.options[o] + '" role="radio" tabindex="0" aria-checked="' + (sel ? 'true' : 'false') + '" aria-label="' + quest.options[o] + '"' + disabled + '>' + quest.options[o] + '</div>';
                }
                html += '</div></div>';
            }

            container.innerHTML = html;

            /* Accessibility: announce game type */
            A11y.announce('Lectura: ' + (data.instruction || data.title || 'Lee y responde'));

            /* Keyboard: Enter/Space to select options */
            var storyQGroups = container.querySelectorAll('.yg-story-q-options');
            for (var sg = 0; sg < storyQGroups.length; sg++) {
                A11y.bindActivateKeys(storyQGroups[sg], '.yg-story-q-option');
                A11y.bindArrowNav(storyQGroups[sg], '.yg-story-q-option');
            }

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

        /* Word-by-word arrival reveal — sentences appear word by word with TTS + gold glow */
        _renderWordReveal: function(data, container, onComplete) {
            var html = '';

            /* Type label badge */
            var labelText = data.label || 'Yaguará';
            html += renderTypeLabel(labelText, 'narrative', 'yg-type-narrative');

            /* Arrival illustration */
            if (data.image) {
                html += '<div class="yg-arrival-image">';
                html += '<img src="' + data.image + '" alt="' + (data.imageAlt || '') + '" class="yg-arrival-img" loading="lazy" onerror="this.style.display=\'none\'; this.parentElement.classList.add(\'yg-arrival-no-img\')">';
                html += '<div class="yg-arrival-fallback">' + (data.imageAlt || '') + '</div>';
                html += '</div>';
            }

            /* Speaker avatar */
            var speaker = data.speaker || 'yaguara';
            if (speaker === 'yaguara') {
                html += '<div class="yg-narrative-speaker">' +
                    '<div class="yg-narrative-avatar">\uD83D\uDC06</div>' +
                    '<div class="yg-narrative-name">Yaguará</div>' +
                '</div>';
            }

            /* Resolve {nombre} */
            var _narrativeName = '';
            try {
                var _nUser = window.JaguarAPI && JaguarAPI.getUser();
                _narrativeName = (_nUser && (_nUser.display_name || _nUser.name)) || '';
            } catch(e) {}
            var _nameStr = _narrativeName || '...';

            /* Build sections — wrap each word in a span */
            var sections = data.sections || [];
            var sentenceTexts = [];
            for (var i = 0; i < sections.length; i++) {
                var sec = sections[i];
                if (sec.body) {
                    var bodyText = sec.body.replace(/\{nombre\}/g, _nameStr);
                    sentenceTexts.push(bodyText);
                    var words = bodyText.split(/\s+/);
                    html += '<div class="yg-narrative-section yg-arrival-sentence" data-sidx="' + i + '">';
                    html += '<div class="yg-narrative-body">';
                    for (var w = 0; w < words.length; w++) {
                        html += '<span class="yg-arrival-word">' + words[w] + '</span>';
                        if (w < words.length - 1) html += ' ';
                    }
                    html += '</div></div>';
                }
            }

            container.innerHTML = html;

            /* ---- Sequential word reveal animation ---- */
            var perWord = 600;          /* ms per word highlight */
            var sentencePause = 800;    /* ms pause between sentences */
            var initialDelay = 600;     /* ms before first sentence */

            var sentenceEls = container.querySelectorAll('.yg-arrival-sentence');
            var totalDelay = initialDelay;

            for (var s = 0; s < sentenceEls.length; s++) {
                (function(secEl, secIdx, startTime) {
                    var wordEls = secEl.querySelectorAll('.yg-arrival-word');
                    var sentence = sentenceTexts[secIdx];

                    /* Speak the full sentence */
                    setTimeout(function() {
                        Audio.speak(sentence);
                    }, startTime);

                    /* Reveal + highlight each word in sequence */
                    for (var w = 0; w < wordEls.length; w++) {
                        (function(wordEl, wordIdx) {
                            /* Reveal word + add glow */
                            setTimeout(function() {
                                wordEl.classList.add('yg-arrival-word-visible');
                                wordEl.classList.add('yg-arrival-word-glow');
                            }, startTime + wordIdx * perWord);

                            /* Remove glow after highlight period */
                            setTimeout(function() {
                                wordEl.classList.remove('yg-arrival-word-glow');
                            }, startTime + (wordIdx + 1) * perWord);
                        })(wordEls[w], w);
                    }
                })(sentenceEls[s], s, totalDelay);

                var wordCount = sentenceEls[s].querySelectorAll('.yg-arrival-word').length;
                totalDelay += wordCount * perWord + sentencePause;
            }

            /* After all reveals: make words tappable + show button */
            setTimeout(function() {
                var allWords = container.querySelectorAll('.yg-arrival-word');
                for (var w = 0; w < allWords.length; w++) {
                    allWords[w].classList.add('yg-arrival-word-tappable');
                    allWords[w].addEventListener('click', (function(el) {
                        return function() {
                            var text = el.textContent.replace(/[.,;:!?¡¿]/g, '').trim();
                            Audio.speak(text);
                            el.classList.add('yg-arrival-word-glow');
                            setTimeout(function() {
                                el.classList.remove('yg-arrival-word-glow');
                            }, 600);
                        };
                    })(allWords[w]));
                }

                /* Auto-advance after 2 seconds of tappable exploration */
                setTimeout(function() {
                    onComplete(true);
                }, 2000);
            }, totalDelay);

            /* Word reveal auto-advances via setTimeout above */
        },

        render: function(data, container, state, onComplete) {
            /* Word-by-word arrival reveal mode */
            if (data.wordReveal) {
                this._renderWordReveal(data, container, onComplete);
                return;
            }
            var html = '';

            /* Type label badge */
            var labelText = data.label || 'Yaguará';
            html += renderTypeLabel(labelText, 'narrative', 'yg-type-narrative');

            /* Previous closing question echo — thread between destinations */
            if (data.previousClosingQuestion) {
                html += '<div class="yg-narrative-epigraph">' + data.previousClosingQuestion + '</div>';
            }

            /* Arrival illustration — CSS world-palette fallback when image is missing */
            if (data.image) {
                html += '<div class="yg-arrival-image">';
                html += '<img src="' + data.image + '" alt="' + (data.imageAlt || '') + '" class="yg-arrival-img" loading="lazy" onerror="this.style.display=\'none\'; this.parentElement.classList.add(\'yg-arrival-no-img\')">';
                html += '<div class="yg-arrival-fallback">' + (data.imageAlt || '') + '</div>';
                html += '</div>';
            }

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

            /* Resolve {nombre} placeholder to student's name */
            var _narrativeName = '';
            try {
                var _nUser = window.JaguarAPI && JaguarAPI.getUser();
                _narrativeName = (_nUser && (_nUser.display_name || _nUser.name)) || '';
            } catch(e) {}
            var _nameStr = _narrativeName || '...';

            /* Sections — rich content blocks */
            var sections = data.sections || [];
            for (var i = 0; i < sections.length; i++) {
                var sec = sections[i];
                html += '<div class="yg-narrative-section">';

                if (sec.heading) {
                    html += '<div class="yg-narrative-heading">' + sec.heading.replace(/\{nombre\}/g, _nameStr) + '</div>';
                }

                if (sec.body) {
                    html += '<div class="yg-narrative-body">' + sec.body.replace(/\{nombre\}/g, _nameStr) + '</div>';
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

            /* a1Simplified — annotate symbol-words with inline SVG + TTS click */
            if (data.a1Simplified) {
                var symWords = container.querySelectorAll('.yg-symbol-word');
                for (var sw = 0; sw < symWords.length; sw++) {
                    var symEl = symWords[sw];
                    var symKey = symEl.getAttribute('data-symbol');
                    if (symKey && SYMBOLS[symKey]) {
                        var iconSpan = document.createElement('span');
                        iconSpan.className = 'yg-symbol-inline';
                        iconSpan.innerHTML = SYMBOLS[symKey];
                        symEl.insertBefore(iconSpan, symEl.firstChild);
                    }
                    /* TTS on click */
                    symEl.addEventListener('click', (function(el) {
                        return function() {
                            Audio.speak(el.textContent.trim());
                            el.classList.add('yg-symbol-word-active');
                            setTimeout(function() { el.classList.remove('yg-symbol-word-active'); }, 600);
                        };
                    })(symEl));
                }
            }

            /* Continue button — always succeeds (no right/wrong) */
            document.getElementById('ygNarrativeContinue').addEventListener('click', function() {
                onComplete(true);
            });
        }
    };

    /* ----- DESPERTAR (Symbolic vocabulary introduction) ----- */
    Renderers.despertar = {
        /* Build the full sentence from words, resolving {nombre} */
        _buildSentence: function(words, studentName) {
            var parts = [];
            for (var i = 0; i < words.length; i++) {
                parts.push(words[i].word.replace(/\{nombre\}/g, studentName || '...'));
            }
            return parts.join(' ');
        },

        /* Highlight words one by one in the grid while TTS speaks the sentence */
        _replayWithHighlight: function(container, words, sentence, callback) {
            var cards = container.querySelectorAll('.yg-despertar-card');
            var delay = 0;
            var perWord = 600; /* ms per word highlight */

            /* Speak the full sentence */
            Audio.speak(sentence);

            /* Highlight each card in sequence */
            for (var i = 0; i < cards.length; i++) {
                (function(card, idx) {
                    setTimeout(function() {
                        /* Remove highlight from all */
                        for (var j = 0; j < cards.length; j++) {
                            cards[j].classList.remove('yg-despertar-highlight');
                        }
                        card.classList.add('yg-despertar-highlight');
                    }, idx * perWord);
                })(cards[i], i);
            }

            /* Clear last highlight and proceed */
            setTimeout(function() {
                for (var j = 0; j < cards.length; j++) {
                    cards[j].classList.remove('yg-despertar-highlight');
                }
                if (callback) callback();
            }, cards.length * perWord + 400);
        },

        render: function(data, container, state, onComplete) {
            var density = data.density || 'heavy';
            var words = data.words || [];
            state.revealed = state.revealed || {};
            state.phase = state.phase || (density === 'heavy' ? 'listen' : 'explore');
            /* Phases (heavy): listen → explore → replay → recognize */
            state.recognizeIdx = state.recognizeIdx || 0;

            /* Resolve {nombre} placeholder to student's name */
            var studentName = '';
            try {
                var user = window.JaguarAPI && JaguarAPI.getUser();
                studentName = (user && (user.display_name || user.name)) || '';
            } catch(e) {}
            var nameStr = studentName || '...';
            var fullSentence = this._buildSentence(words, nameStr);

            var self = this;

            if (state.phase === 'recognize') {
                this._renderRecognize(data, container, state, onComplete, nameStr);
                return;
            }

            /* --- Phase 1: LISTEN — Yaguará's image + full sentence --- */
            if (state.phase === 'listen') {
                var html = renderTypeLabel(data.label || 'El despertar', 'despertar', 'yg-type-despertar');

                /* Character image */
                var img = data.image || 'img/jaguar-hero.jpg';
                html += '<div class="yg-despertar-listen-phase">';
                html += '<div class="yg-despertar-portrait"><img src="' + img + '" alt="' + (data.speaker || 'Yaguará') + '"></div>';
                if (data.speaker) {
                    html += '<div class="yg-despertar-speaker">' + data.speaker + '</div>';
                }
                html += '<button class="yg-listen-btn yg-despertar-play" id="ygDespertarPlay">' + ICONS.speaker + '</button>';
                html += '<div class="yg-despertar-hint">Escucha...</div>';
                html += '</div>';
                html += '<button class="yg-listo-btn yg-narrative-btn" id="ygDespertarContinue" style="margin-top:1.2rem; opacity:0; pointer-events:none;">Continuar</button>';

                container.innerHTML = html;

                /* Auto-play the full sentence */
                setTimeout(function() { Audio.speak(fullSentence); }, 500);

                /* Replay button */
                document.getElementById('ygDespertarPlay').addEventListener('click', function() {
                    Audio.speak(fullSentence);
                });

                /* Show continue button after sentence has had time to play */
                var listenTime = Math.max(words.length * 500 + 800, 2000);
                setTimeout(function() {
                    var btn = document.getElementById('ygDespertarContinue');
                    if (btn) { btn.style.opacity = '1'; btn.style.pointerEvents = 'auto'; }
                }, listenTime);

                document.getElementById('ygDespertarContinue').addEventListener('click', function() {
                    state.phase = 'explore';
                    self.render(data, container, state, onComplete);
                });
                return;
            }

            /* --- Phase 3: REPLAY — full sentence with word highlighting --- */
            if (state.phase === 'replay') {
                var html = renderTypeLabel(data.label || 'El despertar', 'despertar', 'yg-type-despertar');
                if (data.speaker) {
                    html += '<div class="yg-despertar-speaker">' + data.speaker + '</div>';
                }

                html += '<div class="yg-despertar-constellation yg-despertar-' + density + '" id="ygDespertarGrid">';
                for (var i = 0; i < words.length; i++) {
                    var displayWord = words[i].word.replace(/\{nombre\}/g, nameStr);
                    html += '<div class="yg-despertar-card revealed" data-idx="' + i + '">' +
                        '<div class="yg-despertar-word visible">' + displayWord + '</div>' +
                    '</div>';
                }
                html += '</div>';
                container.innerHTML = html;

                /* Play sentence with word-by-word highlighting, then move to recognize */
                setTimeout(function() {
                    self._replayWithHighlight(container, words, fullSentence, function() {
                        setTimeout(function() {
                            state.phase = 'recognize';
                            self.render(data, container, state, onComplete);
                        }, 600);
                    });
                }, 400);
                return;
            }

            /* --- Phase 2: EXPLORE — word-by-word card tapping --- */
            var html = renderTypeLabel(data.label || 'El despertar', 'despertar', 'yg-type-despertar');

            if (data.speaker) {
                html += '<div class="yg-despertar-speaker">' + data.speaker + '</div>';
            }

            if (data.instruction) {
                html += '<div class="yg-instruction">' + data.instruction + '</div>';
            }

            html += '<div class="yg-despertar-constellation yg-despertar-' + density + '" id="ygDespertarGrid">';
            for (var i = 0; i < words.length; i++) {
                var w = words[i];
                var revealed = state.revealed[i];
                var displayWord = w.word.replace(/\{nombre\}/g, nameStr);

                if (density === 'heavy') {
                    html += '<div class="yg-despertar-card' + (revealed ? ' revealed' : '') + '" data-idx="' + i + '">' +
                        '<div class="yg-despertar-word' + (revealed ? ' visible' : '') + '">' + displayWord + '</div>' +
                    '</div>';
                } else if (density === 'moderate') {
                    html += '<div class="yg-despertar-card revealed" data-idx="' + i + '">' +
                        '<div class="yg-despertar-word visible">' + displayWord + '</div>' +
                    '</div>';
                } else {
                    html += '<div class="yg-despertar-card revealed yg-despertar-light-card" data-idx="' + i + '">' +
                        '<div class="yg-despertar-word visible">' + displayWord + '</div>' +
                    '</div>';
                }
            }
            html += '</div>';

            /* Progress hint */
            if (density === 'heavy') {
                var revCount = Object.keys(state.revealed).length;
                if (revCount < words.length) {
                    html += '<div class="yg-despertar-hint" id="ygDespertarHint">Toca cada palabra</div>';
                }
            }

            container.innerHTML = html;

            var grid = document.getElementById('ygDespertarGrid');
            grid.addEventListener('click', function(e) {
                var card = e.target.closest('.yg-despertar-card');
                if (!card) return;
                var idx = parseInt(card.dataset.idx, 10);
                var w = words[idx];

                /* Speak the word */
                var spokenWord = (w.audio || w.word).replace(/\{nombre\}/g, nameStr);
                Audio.speak(spokenWord);

                if (density === 'heavy' && !state.revealed[idx]) {
                    state.revealed[idx] = true;
                    card.classList.add('revealed');
                    var wordEl = card.querySelector('.yg-despertar-word');
                    if (wordEl) wordEl.classList.add('visible');
                    WorldReaction.harmony(container, card);

                    /* All revealed → go to replay phase */
                    if (Object.keys(state.revealed).length >= words.length) {
                        var hint = document.getElementById('ygDespertarHint');
                        if (hint) hint.style.display = 'none';
                        setTimeout(function() {
                            state.phase = 'replay';
                            self.render(data, container, state, onComplete);
                        }, CONFIG.harmonyDuration + 400);
                    }
                } else if (density === 'moderate') {
                    card.classList.add('yg-despertar-tapped');
                    state.revealed[idx] = true;
                    if (Object.keys(state.revealed).length >= words.length) {
                        setTimeout(function() {
                            state.phase = 'recognize';
                            self.render(data, container, state, onComplete);
                        }, 800);
                    }
                }
            });

            /* Light density: add continue button */
            if (density === 'light') {
                var btn = document.createElement('button');
                btn.className = 'yg-listo-btn yg-narrative-btn';
                btn.textContent = 'Continuar';
                btn.style.marginTop = '1.5rem';
                container.appendChild(btn);
                btn.addEventListener('click', function() { onComplete(true); });
            }
        },

        /* --- Recognition phase: TTS plays, student taps matching word --- */
        _renderRecognize: function(data, container, state, onComplete, studentName) {
            var words = data.words || [];
            var nameStr = studentName || '...';
            var shuffled = state._shuffledRecognize;
            if (!shuffled) {
                shuffled = shuffle(words.map(function(w, i) { return { word: w, origIdx: i }; }));
                state._shuffledRecognize = shuffled;
            }

            var current = shuffled[state.recognizeIdx];
            if (!current) { onComplete(true); return; }

            var html = renderTypeLabel(data.label || 'El despertar', 'despertar', 'yg-type-despertar');
            html += '<div class="yg-instruction">Escucha y toca la palabra.</div>';
            html += '<div class="yg-despertar-recognize-prompt">';
            html += '<button class="yg-listen-btn yg-despertar-listen" id="ygDespertarListen">' + ICONS.speaker + '</button>';
            html += '<div class="yg-sub-counter">' + (state.recognizeIdx + 1) + ' de ' + shuffled.length + '</div>';
            html += '</div>';

            html += '<div class="yg-despertar-constellation yg-despertar-recognize" id="ygDespertarRecGrid">';
            for (var i = 0; i < words.length; i++) {
                var w = words[i];
                var displayWord = w.word.replace(/\{nombre\}/g, nameStr);
                html += '<div class="yg-despertar-card yg-despertar-rec-card revealed" data-idx="' + i + '">' +
                    '<div class="yg-despertar-word visible">' + displayWord + '</div>' +
                '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            /* Auto-play current word — for {nombre} cards, speak the student's name */
            var audioWord = current.word.audio || current.word.word;
            var audioText = audioWord.replace(/\{nombre\}/g, nameStr);
            setTimeout(function() { Audio.speak(audioText); }, 300);

            document.getElementById('ygDespertarListen').addEventListener('click', function() {
                Audio.speak(audioText);
            });

            var self = this;
            document.getElementById('ygDespertarRecGrid').addEventListener('click', function(e) {
                var card = e.target.closest('.yg-despertar-rec-card');
                if (!card || state._recBusy) return;
                var idx = parseInt(card.dataset.idx, 10);

                if (idx === current.origIdx) {
                    state._recBusy = true;
                    WorldReaction.harmony(container, card, function() {
                        state._recBusy = false;
                        state.recognizeIdx++;
                        if (state.recognizeIdx >= shuffled.length) {
                            onComplete(true);
                        } else {
                            self._renderRecognize(data, container, state, onComplete, nameStr);
                        }
                    });
                } else {
                    state._recBusy = true;
                    WorldReaction.desequilibrio(container, card, function() {
                        state._recBusy = false;
                    });
                }
            });
        }
    };

    /* ----- CRÓNICA (Student writing that feeds into the jaguar's story) ----- */
    Renderers.cronica = {
        render: function(data, container, state, onComplete) {
            var html = '';

            /* Type label badge */
            html += renderTypeLabel(data.label || 'Cr\u00f3nica', 'cronica', 'yg-type-cronica');

            /* Echo previous cronica entry if present */
            if (data.echoPrevious) {
                html += '<div class="yg-cronica-echo">' +
                    '<div class="yg-cronica-echo-label">Tu voz anterior:</div>' +
                    '<div class="yg-cronica-echo-text">' + data.echoPrevious + '</div>' +
                '</div>';
            }

            /* Narrative prompt — Yaguará sets the scene */
            if (data.prompt) {
                html += '<div class="yg-cronica-prompt">' + data.prompt + '</div>';
            }

            /* Vocabulary hints (scaffolding) */
            if (data.vocabularyHints && data.vocabularyHints.length) {
                html += '<div class="yg-cronica-hints">';
                for (var i = 0; i < data.vocabularyHints.length; i++) {
                    html += '<span class="yg-cronica-hint">' + data.vocabularyHints[i] + '</span>';
                }
                html += '</div>';
            }

            /* Writing area */
            html += '<div class="yg-cronica-writing">';
            html += '<textarea class="yg-cronica-textarea" id="ygCronicaText" ' +
                'placeholder="' + (data.placeholder || 'Escribe aqu\u00ed...') + '" ' +
                'rows="' + (data.scaffoldType === 'word' ? 2 : data.scaffoldType === 'paragraph' ? 8 : 4) + '">' +
                (state.text || '') + '</textarea>';

            /* Word count indicator */
            html += '<div class="yg-cronica-wordcount" id="ygCronicaCount">' +
                '<span id="ygCronicaWords">0</span> palabras' +
                (data.minWords > 1 ? ' (m\u00ednimo ' + data.minWords + ')' : '') +
                '</div>';
            html += '</div>';

            /* Save button */
            html += '<button class="yg-listo-btn yg-cronica-btn" id="ygCronicaSave" disabled>' +
                (data.button || 'Guardar en la cr\u00f3nica') + '</button>';

            container.innerHTML = html;

            /* Bind events */
            var textarea = document.getElementById('ygCronicaText');
            var countEl = document.getElementById('ygCronicaWords');
            var saveBtn = document.getElementById('ygCronicaSave');
            var minWords = data.minWords || 1;

            function countWords(text) {
                var trimmed = text.trim();
                if (!trimmed) return 0;
                return trimmed.split(/\s+/).length;
            }

            textarea.addEventListener('input', function() {
                var wc = countWords(textarea.value);
                countEl.textContent = wc;
                state.text = textarea.value;
                saveBtn.disabled = wc < minWords;
            });

            saveBtn.addEventListener('click', function() {
                var text = textarea.value.trim();
                if (!text) return;

                /* Save to cronica store */
                try {
                    var key = 'yaguara_cronica';
                    var store = JSON.parse(localStorage.getItem(key) || '{}');
                    store[data.storyKey || data.destination || 'unknown'] = {
                        text: text,
                        destination: data.destination || '',
                        timestamp: new Date().toISOString()
                    };
                    localStorage.setItem(key, JSON.stringify(store));
                } catch(e) { /* silent */ }

                /* PersonalLexicon — harvest personal language from student writing */
                if (window.PersonalLexicon) {
                    var lex = Engine._lexicon || PersonalLexicon.getInstance();
                    lex.harvest(text, data.destination || null);
                }

                WorldReaction.harmony(container, textarea, function() { onComplete(true); });
            });

            /* Restore previous text if any */
            if (state.text) {
                textarea.value = state.text;
                var wc = countWords(state.text);
                countEl.textContent = wc;
                saveBtn.disabled = wc < minWords;
            }

            /* Focus the textarea */
            setTimeout(function() { textarea.focus(); }, 300);
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

            /* YouTube embed — only if youtubeId is provided */
            if (data.youtubeId) {
                html += '<div class="yg-cancion-video">' +
                            '<div class="yg-cancion-video-wrap">' +
                                '<iframe src="https://www.youtube-nocookie.com/embed/' + data.youtubeId + '?rel=0&modestbranding=1" ' +
                                    'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope" ' +
                                    'allowfullscreen></iframe>' +
                            '</div>' +
                        '</div>';
            }

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
            if (!data.puzzles || !data.puzzles.length) { onComplete(true); return; }
            state.puzzleIdx = state.puzzleIdx || 0;
            state.solved = state.solved || {};
            state.resolved = false;
            data._escapeState = state;

            /* Start escape room ambience on first render */
            if (window.AudioManager && data.room && data.room.ambience && !state._ambiencePlaying) {
                state._ambiencePlaying = true;
                AudioManager.playEscapeAmbience(data.room.ambience);
            }

            /* If all puzzles solved, show fragment */
            if (state.puzzleIdx >= data.puzzles.length) {
                this._showFragment(data, container, state, onComplete);
                return;
            }

            var html = this._buildRoomChrome(data, state);
            html += this._buildPuzzle(data.puzzles[state.puzzleIdx], state.puzzleIdx);
            container.innerHTML = html;
            this._bindPuzzle(data, container, state, onComplete);

            /* Accessibility: announce escape room and puzzle progress, trap focus */
            A11y.announce('Sala de enigmas: ' + data.room.name + '. Enigma ' + (state.puzzleIdx + 1) + ' de ' + data.puzzles.length);
            A11y.trapFocus(container);
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
            html += '<div class="yg-escape-locks" role="progressbar" aria-label="Progreso de enigmas" aria-valuenow="' + Object.keys(state.solved).length + '" aria-valuemin="0" aria-valuemax="' + data.puzzles.length + '">';
            for (var i = 0; i < data.puzzles.length; i++) {
                var cls = 'yg-escape-lock';
                var lockLabel = 'Enigma ' + (i + 1);
                if (state.solved[i]) { cls += ' yg-lock-open'; lockLabel += ': resuelto'; }
                else if (i === state.puzzleIdx) { cls += ' yg-lock-active'; lockLabel += ': actual'; }
                else { cls += ' yg-lock-closed'; lockLabel += ': bloqueado'; }
                html += '<div class="' + cls + '" aria-label="' + lockLabel + '">' + (state.solved[i] ? '&#10003;' : (i + 1)) + '</div>';
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
            html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la respuesta..." autocomplete="off" autocapitalize="none" aria-label="Ingresa tu respuesta">';
            html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Abrir cerradura">Abrir</button>';
            return html;
        },

        _buildCipher: function(puzzle) {
            var letters = (puzzle.scrambled || '').split('');
            if (!letters.length && puzzle.answer) {
                /* Auto-scramble from answer */
                letters = shuffle(puzzle.answer.split(''));
            }
            var html = '<div class="yg-escape-tiles" id="ygEscapeTiles" role="listbox" aria-label="Letras disponibles">';
            for (var i = 0; i < letters.length; i++) {
                html += '<div class="yg-sb-chip yg-escape-tile" data-letter="' + letters[i] + '" role="option" tabindex="0" aria-label="Letra: ' + letters[i] + '">' + letters[i] + '</div>';
            }
            html += '</div>';
            html += '<div class="yg-escape-built" id="ygEscapeBuilt" aria-label="Palabra formada" aria-live="polite"></div>';
            html += '<div class="yg-escape-cipher-actions">';
            html += '<button class="yg-listo-btn yg-escape-undo" id="ygEscapeUndo" aria-label="Borrar letras">Borrar</button>';
            html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Enviar respuesta">Listo</button>';
            html += '</div>';
            return html;
        },

        _buildRiddle: function(puzzle) {
            var html = '';
            if (puzzle.riddle) html += '<div class="yg-escape-riddle">' + puzzle.riddle + '</div>';
            html += '<div class="yg-fib-options" id="ygEscapeOptions" role="radiogroup" aria-label="Opciones de respuesta">';
            var opts = shuffle(puzzle.options.slice());
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            return html;
        },

        _buildSequence: function(puzzle) {
            var items = shuffle(puzzle.items.slice());
            var html = '<div class="yg-escape-sequence-pool" id="ygEscapePool" role="listbox" aria-label="Elementos para ordenar">';
            for (var i = 0; i < items.length; i++) {
                html += '<div class="yg-sb-chip yg-escape-seq-item" data-val="' + items[i] + '" role="option" tabindex="0" aria-label="' + items[i] + '">' + items[i] + '</div>';
            }
            html += '</div>';
            html += '<div class="yg-escape-sequence-slots" id="ygEscapeSlots" aria-label="Espacios de secuencia">';
            for (var i = 0; i < puzzle.items.length; i++) {
                html += '<div class="yg-escape-seq-slot" data-slot="' + i + '" tabindex="0" aria-label="Posici\u00f3n ' + (i + 1) + '">' + (i + 1) + '</div>';
            }
            html += '</div>';
            html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Enviar secuencia">Listo</button>';
            return html;
        },

        _buildExtract: function(puzzle) {
            var html = '';
            if (puzzle.passage) html += '<div class="yg-escape-passage yg-story-text">' + puzzle.passage + '</div>';
            if (puzzle.options && puzzle.options.length) {
                html += '<div class="yg-fib-options" id="ygEscapeOptions" role="radiogroup" aria-label="Opciones de respuesta">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la respuesta..." autocomplete="off" autocapitalize="sentences" aria-label="Ingresa tu respuesta">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Enviar respuesta">Listo</button>';
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
                html += '<div class="yg-fib-options" id="ygEscapeOptions" role="radiogroup" aria-label="Opciones de respuesta">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe la conclusi\u00f3n..." autocomplete="off" autocapitalize="sentences" aria-label="Ingresa tu respuesta">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Enviar respuesta">Listo</button>';
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
                html += '<div class="yg-fib-options" id="ygEscapeOptions" role="radiogroup" aria-label="Opciones de respuesta">';
                var opts = shuffle(puzzle.options.slice());
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option yg-escape-option" data-val="' + opts[i] + '" role="radio" tabindex="0" aria-checked="false" aria-label="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input yg-escape-input" id="ygEscapeInput" placeholder="Escribe tu s\u00edntesis..." autocomplete="off" autocapitalize="sentences" aria-label="Ingresa tu respuesta">';
                html += '<button class="yg-listo-btn" id="ygEscapeListo" aria-label="Enviar respuesta">Listo</button>';
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

            /* Keyboard: Enter/Space to select, arrow keys to navigate */
            A11y.bindActivateKeys(optContainer, '.yg-escape-option');
            A11y.bindArrowNav(optContainer, '.yg-escape-option');

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

            /* Keyboard: Enter/Space to select tiles, arrow keys to navigate */
            A11y.bindActivateKeys(tilesEl, '.yg-escape-tile');
            A11y.bindArrowNav(tilesEl, '.yg-escape-tile');

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

            /* Keyboard: Enter/Space to place items, arrow keys to navigate */
            A11y.bindActivateKeys(pool, '.yg-escape-seq-item');
            A11y.bindArrowNav(pool, '.yg-escape-seq-item');

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

            /* Escape room puzzle solved SFX */
            if (window.AudioManager && Engine._config && Engine._config.destNum) {
                AudioManager.playEscapeSolved(Engine._config.destNum);
            }

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

            /* Room complete — stop ambience, play completion sting */
            if (window.AudioManager) {
                AudioManager.stopEscapeAmbience();
                if (Engine._config && Engine._config.destNum) {
                    AudioManager.playEscapeComplete(Engine._config.destNum);
                }
            }

            document.getElementById('ygEscapeContinue').addEventListener('click', function() {
                onComplete(true);
            });
        }
    };

    /* ----- FLASHNOTE (Grammar Capsule) ----- */
    Renderers.flashnote = {
        render: function(data, container, state, onComplete) {
            state.resolved = false;
            state.phase = state.phase || 'note'; /* 'note' → 'question' */

            if (state.phase === 'note') {
                var html = renderTypeLabel(data.label || 'Cápsula', 'flashnote') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                           '<div class="yg-flash-note">' + (data.note || '') + '</div>' +
                           '<button class="yg-listo-btn" id="ygFlashContinue">Entendido</button>';
                container.innerHTML = html;

                var self = this;
                document.getElementById('ygFlashContinue').addEventListener('click', function() {
                    state.phase = 'question';
                    self.render(data, container, state, onComplete);
                });
                return;
            }

            /* Question phase */
            var html = renderTypeLabel(data.label || 'Cápsula', 'flashnote') +
                       '<div class="yg-flash-question">' + (data.question || '') + '</div>';

            if (data.options && data.options.length) {
                var opts = shuffle(data.options.slice());
                html += '<div class="yg-fib-options" id="ygFlashOpts">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<div class="yg-fib-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygFlashInput" placeholder="Escribe tu respuesta..." autocomplete="off" autocapitalize="sentences">';
                html += '<button class="yg-listo-btn" id="ygFlashListo">Listo</button>';
            }
            container.innerHTML = html;

            if (data.options && data.options.length) {
                document.getElementById('ygFlashOpts').addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-fib-option');
                    if (!el || state.resolved) return;
                    state.resolved = true;
                    var all = container.querySelectorAll('.yg-fib-option');
                    for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                    if (normalize(el.dataset.val) === normalize(data.answer)) {
                        WorldReaction.harmony(container, el, function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, el, function() {
                            state.resolved = false;
                            for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                        });
                    }
                });
            } else {
                var input = document.getElementById('ygFlashInput');
                document.getElementById('ygFlashListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;

                    if (normalize(val) === normalize(data.answer)) {
                        WorldReaction.harmony(container, input, function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, input, function() { state.resolved = false; });
                    }
                });
                input.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ygFlashListo').click(); });
            }
        }
    };


    /* ----- CROSSWORD (Interactive Grid Filling) ----- */
    Renderers.crossword = {
        render: function(data, container, state, onComplete) {
            state.subIdx = state.subIdx || 0;
            state.answers = state.answers || [];
            state.resolved = false;
            var clue = data.clues[state.subIdx];
            if (!clue) { onComplete(true); return; }

            var html = renderTypeLabel(data.label || 'Crucigrama', 'crossword') +
                       '<div class="yg-instruction">' + (data.instruction || 'Lee las definiciones. Escribe la palabra correcta.') + '</div>';

            /* Clue list */
            if (data.clues.length > 1) {
                html += '<div class="yg-cross-clue-list">';
                for (var ci = 0; ci < data.clues.length; ci++) {
                    var done = state.answers[ci] ? ' yg-cross-clue-done' : '';
                    var active = ci === state.subIdx ? ' yg-cross-clue-active' : '';
                    var dir = data.clues[ci].direction === 'down' ? '\u2193' : '\u2192';
                    html += '<div class="yg-cross-clue-item' + done + active + '" data-clue="' + ci + '">';
                    html += '<span class="yg-cross-num">' + data.clues[ci].number + dir + '</span> ';
                    html += data.clues[ci].clue;
                    html += '</div>';
                }
                html += '</div>';
            }

            /* Current clue */
            var dirLabel = clue.direction === 'down' ? '\u2193' : '\u2192';
            html += '<div class="yg-cross-current">';
            html += '<span class="yg-cross-num">' + clue.number + dirLabel + '</span> ' + clue.clue;
            html += '</div>';

            /* Letter boxes as inputs */
            var len = clue.answer.length;
            html += '<div class="yg-cross-boxes" id="ygCrossBoxes">';
            for (var i = 0; i < len; i++) {
                var val = state.answers[state.subIdx] ? clue.answer[i] : '';
                html += '<input type="text" class="yg-cross-box-input" maxlength="1" data-pos="' + i + '" value="' + val + '" autocomplete="off" autocapitalize="none">';
            }
            html += '</div>';

            html += '<button class="yg-listo-btn" id="ygCrossListo">Listo</button>';
            container.innerHTML = html;

            var self = this;
            var boxes = container.querySelectorAll('.yg-cross-box-input');

            /* Auto-advance between boxes */
            for (var bi = 0; bi < boxes.length; bi++) {
                (function(idx) {
                    boxes[idx].addEventListener('input', function() {
                        if (this.value && idx < boxes.length - 1) boxes[idx + 1].focus();
                    });
                    boxes[idx].addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && !this.value && idx > 0) boxes[idx - 1].focus();
                        if (e.key === 'Enter') document.getElementById('ygCrossListo').click();
                    });
                })(bi);
            }

            for (var fi = 0; fi < boxes.length; fi++) {
                if (!boxes[fi].value) { boxes[fi].focus(); break; }
            }

            /* Clue navigation */
            var clueItems = container.querySelectorAll('.yg-cross-clue-item');
            for (var ci = 0; ci < clueItems.length; ci++) {
                clueItems[ci].addEventListener('click', function() {
                    var newIdx = parseInt(this.dataset.clue);
                    if (newIdx !== state.subIdx) {
                        state.subIdx = newIdx;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    }
                });
            }

            document.getElementById('ygCrossListo').addEventListener('click', function() {
                if (state.resolved) return;
                var val = '';
                for (var i = 0; i < boxes.length; i++) val += (boxes[i].value || '');
                if (!val.trim()) return;
                state.resolved = true;

                if (normalize(val) === normalize(clue.answer)) {
                    state.answers[state.subIdx] = clue.answer;
                    WorldReaction.harmony(container, document.getElementById('ygCrossBoxes'), function() {
                        self._advance(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, boxes[0], function() { state.resolved = false; });
                }
            });
        },
        _advance: function(data, container, state, onComplete) {
            if (state.subIdx < data.clues.length - 1) {
                state.subIdx++;
                state.resolved = false;
                this.render(data, container, state, onComplete);
            } else {
                var correct = 0;
                for (var i = 0; i < data.clues.length; i++) { if (state.answers[i]) correct++; }
                onComplete(correct >= Math.ceil(data.clues.length / 2));
            }
        }
    };


    /* ----- EXPLORADOR (Location Discovery Map with Breadcrumbs) ----- */
    Renderers.explorador = {
        render: function(data, container, state, onComplete) {
            state.visited = state.visited || {};
            state.currentLoc = (state.currentLoc !== undefined) ? state.currentLoc : -1;
            state.visitOrder = state.visitOrder || [];

            if (state.currentLoc === -1) {
                this._renderMap(data, container, state, onComplete);
            } else {
                this._renderLocation(data, container, state, onComplete);
            }
        },

        _renderMap: function(data, container, state, onComplete) {
            var html = renderTypeLabel(data.label || 'Explorador', 'explorador') +
                       '<div class="yg-instruction">' + (data.instruction || 'Explora cada lugar.') + '</div>';

            /* Breadcrumb trail */
            if (state.visitOrder.length > 0) {
                html += '<div class="yg-explore-breadcrumb">';
                for (var b = 0; b < state.visitOrder.length; b++) {
                    if (b > 0) html += '<span class="yg-explore-bread-sep">\u2192</span>';
                    html += '<span class="yg-explore-bread-item">' + data.locations[state.visitOrder[b]].name + '</span>';
                }
                html += '</div>';
            }

            /* Mini map with connected nodes */
            html += '<div class="yg-explore-minimap" id="ygExploreMap">';
            for (var i = 0; i < data.locations.length; i++) {
                var loc = data.locations[i];
                var visited = state.visited[i] ? ' yg-explore-visited' : '';
                html += '<div class="yg-explore-node' + visited + '" data-loc="' + i + '">';
                html += '<div class="yg-explore-node-icon">' + (state.visited[i] ? '\u2713' : (i + 1)) + '</div>';
                html += '<div class="yg-explore-node-name">' + loc.name + '</div>';
                html += '</div>';
                if (i < data.locations.length - 1) {
                    html += '<div class="yg-explore-connector' + (state.visited[i] ? ' yg-explore-connector-done' : '') + '"></div>';
                }
            }
            html += '</div>';

            /* Progress */
            var visitedCount = Object.keys(state.visited).length;
            html += '<div class="yg-boggle-count">' + visitedCount + ' de ' + data.locations.length + ' explorados</div>';

            var allVisited = visitedCount >= data.locations.length;
            if (allVisited) {
                html += '<button class="yg-listo-btn" id="ygExploreDone">Continuar</button>';
            }
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygExploreMap').addEventListener('click', function(e) {
                var pin = e.target.closest('.yg-explore-node');
                if (!pin) return;
                var idx = parseInt(pin.dataset.loc);
                state.currentLoc = idx;
                self.render(data, container, state, onComplete);
            });

            if (allVisited) {
                document.getElementById('ygExploreDone').addEventListener('click', function() { onComplete(true); });
            }
        },

        _renderLocation: function(data, container, state, onComplete) {
            var loc = data.locations[state.currentLoc];
            var html = renderTypeLabel(data.label || 'Explorador', 'explorador') +
                       '<div class="yg-explore-loc-header">';
            html += '<button class="yg-explore-back-btn" id="ygExploreBack">\u2190</button>';
            html += '<div class="yg-explore-loc-name">' + loc.name + '</div>';
            html += '</div>';
            html += '<div class="yg-explore-text yg-story-text">' + loc.text + '</div>';

            if (loc.question && !state.visited[state.currentLoc]) {
                html += '<div class="yg-explore-question">' + loc.question + '</div>';
                if (loc.options && loc.options.length) {
                    var opts = shuffle(loc.options.slice());
                    html += '<div class="yg-fib-options" id="ygExploreOpts">';
                    for (var i = 0; i < opts.length; i++) {
                        html += '<div class="yg-fib-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
                    }
                    html += '</div>';
                } else {
                    html += '<input type="text" class="yg-dict-input" id="ygExploreInput" placeholder="Escribe tu respuesta..." autocomplete="off" autocapitalize="sentences">';
                    html += '<button class="yg-listo-btn" id="ygExploreListo">Listo</button>';
                }
            } else {
                html += '<button class="yg-listo-btn" id="ygExploreBackBtn">\u2190 Volver al mapa</button>';
            }
            container.innerHTML = html;

            var self = this;

            /* Back button */
            var backBtn = document.getElementById('ygExploreBack') || document.getElementById('ygExploreBackBtn');
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    state.currentLoc = -1;
                    self.render(data, container, state, onComplete);
                });
            }

            if (loc.question && !state.visited[state.currentLoc]) {
                if (loc.options && loc.options.length) {
                    document.getElementById('ygExploreOpts').addEventListener('click', function(e) {
                        var el = e.target.closest('.yg-fib-option');
                        if (!el) return;
                        var all = container.querySelectorAll('.yg-fib-option');
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                        if (normalize(el.dataset.val) === normalize(loc.answer)) {
                            state.visited[state.currentLoc] = true;
                            state.visitOrder.push(state.currentLoc);
                            WorldReaction.harmony(container, el, function() {
                                state.currentLoc = -1;
                                self.render(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, el, function() {
                                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                            });
                        }
                    });
                } else {
                    var input = document.getElementById('ygExploreInput');
                    document.getElementById('ygExploreListo').addEventListener('click', function() {
                        var val = (input.value || '').trim();
                        if (!val) return;
                        if (normalize(val) === normalize(loc.answer)) {
                            state.visited[state.currentLoc] = true;
                            state.visitOrder.push(state.currentLoc);
                            WorldReaction.harmony(container, input, function() {
                                state.currentLoc = -1;
                                self.render(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, input);
                        }
                    });
                    input.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ygExploreListo').click(); });
                }
            }
        }
    };


    /* ----- KLOO (Colored Card Sentence Builder) ----- */
    Renderers.kloo = {
        render: function(data, container, state, onComplete) {
            state.built = state.built || [];
            state.usedIndices = state.usedIndices || {};
            if (!state._shuffled) {
                state._shuffled = shuffle(data.cards.map(function(c, i) { return { card: c, origIdx: i }; }));
            }
            var shuffled = state._shuffled;

            var html = renderTypeLabel(data.label || 'Kloo', 'kloo') +
                       '<div class="yg-instruction">' + (data.instruction || 'Ordena las cartas para formar la frase.') + '</div>';

            /* Built sentence area */
            html += '<div class="yg-kloo-target" id="ygKlooTarget">';
            if (state.built.length === 0) {
                html += '<span class="yg-sb-placeholder">Haz clic en las cartas para construir la frase...</span>';
            } else {
                for (var b = 0; b < state.built.length; b++) {
                    html += '<span class="yg-kloo-chip in-target" data-built-idx="' + b + '" style="border-color:' + state.built[b].color + ';">' + state.built[b].text + '</span>';
                }
            }
            html += '</div>';

            /* Card bank with flip animation */
            html += '<div class="yg-kloo-bank" id="ygKlooBank">';
            for (var i = 0; i < shuffled.length; i++) {
                var used = state.usedIndices[i] ? ' yg-kloo-used' : '';
                var card = shuffled[i].card;
                var catLabel = card.category ? '<span class="yg-kloo-cat">' + card.category + '</span>' : '';
                html += '<div class="yg-kloo-card-wrap' + used + '" data-bank-idx="' + i + '">';
                html += '<div class="yg-kloo-card-inner">';
                html += '<div class="yg-kloo-card-front" style="background-color:' + card.color + ';">' + catLabel + '<span class="yg-kloo-card-text">' + card.text + '</span></div>';
                html += '<div class="yg-kloo-card-back"></div>';
                html += '</div></div>';
            }
            html += '</div>';
            html += '<div class="yg-kloo-actions">';
            html += '<button class="yg-sb-clear" id="ygKlooClear">\u21BB Borrar</button> ';
            html += '<button class="yg-listo-btn" id="ygKlooListo">Listo</button>';
            html += '</div>';
            container.innerHTML = html;

            var self = this;

            document.getElementById('ygKlooBank').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-kloo-card-wrap');
                if (!el || el.classList.contains('yg-kloo-used')) return;
                var bIdx = parseInt(el.dataset.bankIdx);
                state.built.push({ text: shuffled[bIdx].card.text, color: shuffled[bIdx].card.color });
                state.usedIndices[bIdx] = true;
                el.classList.add('yg-kloo-flipping');
                setTimeout(function() { self.render(data, container, state, onComplete); }, 250);
            });

            document.getElementById('ygKlooTarget').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-kloo-chip.in-target');
                if (!el) return;
                var bltIdx = parseInt(el.dataset.builtIdx);
                var removedText = state.built[bltIdx].text;
                state.built.splice(bltIdx, 1);
                for (var k = 0; k < shuffled.length; k++) {
                    if (state.usedIndices[k] && shuffled[k].card.text === removedText) {
                        delete state.usedIndices[k]; break;
                    }
                }
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygKlooClear').addEventListener('click', function() {
                state.built = [];
                state.usedIndices = {};
                self.render(data, container, state, onComplete);
            });

            document.getElementById('ygKlooListo').addEventListener('click', function() {
                if (state.built.length === 0) return;
                var userSentence = normalize(state.built.map(function(b) { return b.text; }).join(' '));
                var correct = normalize(data.answer);
                var target = document.getElementById('ygKlooTarget');

                if (userSentence === correct) {
                    WorldReaction.harmony(container, target, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, target, function() {});
                }
            });
        }
    };

    /* ----- CULTURA (Cultural Reading + Comprehension) ----- */
    Renderers.cultura = {
        render: function(data, container, state, onComplete) {
            state.resolved = false;
            var html = renderTypeLabel(data.label || 'Cultura', 'cultura') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                       '<div class="yg-cultura-text yg-story-text">' + data.text + '</div>' +
                       '<div class="yg-cultura-question">' + data.question + '</div>';

            var opts = shuffle(data.options.slice());
            html += '<div class="yg-fib-options" id="ygCulturaOpts">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            document.getElementById('ygCulturaOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-fib-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                var all = container.querySelectorAll('.yg-fib-option');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                if (normalize(el.dataset.val) === normalize(data.answer)) {
                    WorldReaction.harmony(container, el, function() { onComplete(true); });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.resolved = false;
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                    });
                }
            });
        }
    };


    /* ----- SENDA (Path Navigation with Visual Trail) ----- */
    Renderers.senda = {
        render: function(data, container, state, onComplete) {
            state.pathIdx = state.pathIdx || 0;
            state.history = state.history || [];
            if (state.pathIdx >= data.paths.length) { onComplete(true); return; }

            var step = data.paths[state.pathIdx];
            var html = renderTypeLabel(data.label || 'Senda', 'senda') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            /* Visual path trail */
            if (state.history.length > 0 || state.pathIdx > 0) {
                html += '<div class="yg-senda-trail">';
                for (var h = 0; h < state.history.length; h++) {
                    html += '<div class="yg-senda-trail-node">';
                    html += '<span class="yg-senda-trail-dot"></span>';
                    html += '<span class="yg-senda-trail-text">' + state.history[h] + '</span>';
                    html += '</div>';
                    if (h < state.history.length - 1 || state.pathIdx < data.paths.length) {
                        html += '<div class="yg-senda-trail-line"></div>';
                    }
                }
                /* Current position */
                html += '<div class="yg-senda-trail-node yg-senda-trail-current">';
                html += '<span class="yg-senda-trail-dot"></span>';
                html += '<span class="yg-senda-trail-text">?</span>';
                html += '</div>';
                html += '</div>';
            }

            /* Step counter */
            html += '<div class="yg-sub-counter">Paso ' + (state.pathIdx + 1) + ' de ' + data.paths.length + '</div>';

            /* Scene */
            html += '<div class="yg-senda-scene yg-story-text">' + step.scene + '</div>';

            /* Branching choices */
            html += '<div class="yg-senda-choices" id="ygSendaChoices">';
            for (var i = 0; i < step.options.length; i++) {
                html += '<div class="yg-senda-choice" data-idx="' + i + '">';
                html += '<span class="yg-senda-choice-arrow">\u279c</span> ';
                html += step.options[i].text;
                html += '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygSendaChoices').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-senda-choice');
                if (!el) return;
                var idx = parseInt(el.dataset.idx);
                var choice = step.options[idx];

                var all = container.querySelectorAll('.yg-senda-choice');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                if (choice.correct !== false) {
                    state.history.push(choice.text.substring(0, 40) + (choice.text.length > 40 ? '...' : ''));
                    var feedback = choice.feedback || choice.consequence || '';
                    WorldReaction.harmony(container, el, function() {
                        if (feedback) {
                            /* Show consequence with animation */
                            container.innerHTML = renderTypeLabel(data.label || 'Senda', 'senda') +
                                '<div class="yg-senda-consequence">' +
                                '<div class="yg-senda-consequence-icon">\u2714</div>' +
                                '<div class="yg-senda-feedback yg-story-text">' + feedback + '</div>' +
                                '</div>' +
                                '<button class="yg-listo-btn" id="ygSendaContinue">Continuar</button>';
                            document.getElementById('ygSendaContinue').addEventListener('click', function() {
                                if (choice.next === -1 || state.pathIdx >= data.paths.length - 1) {
                                    onComplete(true);
                                } else {
                                    state.pathIdx = (choice.next !== undefined) ? choice.next : state.pathIdx + 1;
                                    self.render(data, container, state, onComplete);
                                }
                            });
                        } else {
                            onComplete(true);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        if (choice.feedback) {
                            /* Show wrong consequence with visual */
                            var fb = document.createElement('div');
                            fb.className = 'yg-senda-wrong';
                            fb.innerHTML = '<span class="yg-senda-wrong-icon">\u2718</span> ' + choice.feedback;
                            el.parentNode.appendChild(fb);
                        }
                        for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                        el.style.pointerEvents = 'none';
                        el.style.opacity = '0.4';
                    });
                }
            });
        }
    };


    /* ----- CONSEQUENCES (Chain Story Builder with Visual Chain) ----- */
    Renderers.consequences = {
        render: function(data, container, state, onComplete) {
            state.promptIdx = state.promptIdx || 0;
            state.choices = state.choices || [];

            if (state.promptIdx >= data.prompts.length) {
                /* Show completed story as visual chain */
                var html = renderTypeLabel(data.label || 'Consecuencias', 'consequences') +
                           '<div class="yg-conseq-chain">';
                for (var i = 0; i < state.choices.length; i++) {
                    html += '<div class="yg-conseq-chain-node yg-conseq-chain-enter" style="animation-delay:' + (i * 0.15) + 's">';
                    html += '<div class="yg-conseq-chain-num">' + (i + 1) + '</div>';
                    html += '<div class="yg-conseq-chain-text">' + state.choices[i] + '</div>';
                    html += '</div>';
                    if (i < state.choices.length - 1) {
                        html += '<div class="yg-conseq-chain-arrow">\u2193</div>';
                    }
                }
                html += '</div><button class="yg-listo-btn" id="ygConseqDone">Continuar</button>';
                container.innerHTML = html;
                document.getElementById('ygConseqDone').addEventListener('click', function() { onComplete(true); });
                return;
            }

            var prompt = data.prompts[state.promptIdx];
            var html = renderTypeLabel(data.label || 'Consecuencias', 'consequences') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
            if (data.prompts.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.promptIdx + 1) + ' de ' + data.prompts.length + '</div>';
            }

            /* Show prior choices as a chain */
            if (state.choices.length) {
                html += '<div class="yg-conseq-prior-chain">';
                for (var i = 0; i < state.choices.length; i++) {
                    html += '<div class="yg-conseq-prior-node">';
                    html += '<span class="yg-conseq-prior-num">' + (i + 1) + '</span>';
                    html += '<span class="yg-conseq-prior-text">' + state.choices[i] + '</span>';
                    html += '</div>';
                    html += '<div class="yg-conseq-chain-arrow">\u2193</div>';
                }
                html += '</div>';
            }

            html += '<div class="yg-conseq-prompt">' + prompt.label + '</div>';
            html += '<div class="yg-conseq-options" id="ygConseqOpts">';
            var opts = shuffle(prompt.options.slice());
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option yg-conseq-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygConseqOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-fib-option');
                if (!el) return;
                state.choices.push(el.dataset.val);
                state.promptIdx++;
                WorldReaction.harmony(container, el, function() {
                    self.render(data, container, state, onComplete);
                });
            });
        }
    };

    /* ----- MADLIBS (Template Fill) ----- */
    Renderers.madlibs = {
        render: function(data, container, state, onComplete) {
            state.blankIdx = state.blankIdx || 0;
            state.answers = state.answers || {};
            state.resolved = false;

            if (state.blankIdx >= data.blanks.length) {
                /* Show completed template */
                var filled = data.template;
                for (var k in state.answers) {
                    filled = filled.split('{' + k + '}').join('<strong>' + state.answers[k] + '</strong>');
                }
                var html = renderTypeLabel(data.label || 'Mad Libs', 'madlibs') +
                           '<div class="yg-madlibs-result yg-story-text">' + filled + '</div>' +
                           '<button class="yg-listo-btn" id="ygMadlibsDone">Continuar</button>';
                container.innerHTML = html;

                var correct = 0;
                for (var i = 0; i < data.blanks.length; i++) {
                    if (normalize(state.answers[data.blanks[i].id] || '') === normalize(data.blanks[i].answer)) correct++;
                }
                document.getElementById('ygMadlibsDone').addEventListener('click', function() {
                    onComplete(correct >= Math.ceil(data.blanks.length / 2));
                });
                return;
            }

            var blank = data.blanks[state.blankIdx];
            /* Show template with highlighted current blank */
            var preview = data.template;
            for (var k in state.answers) {
                preview = preview.split('{' + k + '}').join('<strong>' + state.answers[k] + '</strong>');
            }
            preview = preview.split('{' + blank.id + '}').join('<span class="yg-madlibs-blank">___</span>');
            /* Remove remaining placeholders */
            preview = preview.replace(/\{[^}]+\}/g, '___');

            var html = renderTypeLabel(data.label || 'Mad Libs', 'madlibs') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                       '<div class="yg-sub-counter">' + (state.blankIdx + 1) + ' de ' + data.blanks.length + '</div>' +
                       '<div class="yg-madlibs-preview yg-story-text">' + preview + '</div>' +
                       '<div class="yg-madlibs-label">' + blank.label + (blank.hint ? ' <span class="yg-madlibs-hint">(' + blank.hint + ')</span>' : '') + '</div>';

            var opts = shuffle(blank.options.slice());
            html += '<div class="yg-fib-options" id="ygMadlibsOpts">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygMadlibsOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-fib-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                state.answers[blank.id] = el.dataset.val;

                var isCorrect = normalize(el.dataset.val) === normalize(blank.answer);
                if (isCorrect) {
                    WorldReaction.harmony(container, el, function() {
                        state.blankIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.blankIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                }
            });
        }
    };


    /* ----- GUARDIAN (Timed Rapid-Fire Defense) ----- */
    Renderers.guardian = {
        render: function(data, container, state, onComplete) {
            state.qIdx = state.qIdx || 0;
            state.correct = state.correct || 0;
            state.resolved = false;

            if (state.qIdx >= data.questions.length) {
                onComplete(state.correct >= Math.ceil(data.questions.length / 2));
                return;
            }

            var q = data.questions[state.qIdx];
            var perQ = Math.max(3000, Math.round((data.timeLimit || 8) * 1000 / data.questions.length));
            var totalQ = data.questions.length;
            var healthPct = Math.round(((totalQ - (state.qIdx - state.correct)) / totalQ) * 100);
            if (healthPct < 0) healthPct = 0;
            var healthColor = healthPct > 60 ? '#5a6e4a' : healthPct > 30 ? '#c9a227' : '#e07a5f';

            var html = renderTypeLabel(data.label || 'Guardi\u00e1n', 'guardian') +
                       '<div class="yg-instruction">' + (data.instruction || 'Defiende el camino. Responde r\u00e1pido.') + '</div>';

            /* Wave counter */
            html += '<div class="yg-guardian-wave">Oleada ' + (state.qIdx + 1) + ' de ' + totalQ + '</div>';

            /* Health bar */
            html += '<div class="yg-guardian-health">';
            html += '<div class="yg-guardian-health-label">Defensa</div>';
            html += '<div class="yg-guardian-health-bar"><div class="yg-guardian-health-fill" style="width:' + healthPct + '%;background:' + healthColor + ';"></div></div>';
            html += '</div>';

            /* Timer bar */
            html += '<div class="yg-guardian-bar"><div class="yg-guardian-fill" id="ygGuardianFill"></div></div>';

            /* Threat visual */
            html += '<div class="yg-guardian-threat" id="ygGuardianThreat">';
            html += '<div class="yg-guardian-shadow"></div>';
            html += '</div>';

            /* Question */
            html += '<div class="yg-guardian-prompt">' + q.prompt + '</div>';

            var opts = shuffle(q.options.slice());
            html += '<div class="yg-fib-options yg-guardian-opts" id="ygGuardianOpts">';
            for (var i = 0; i < opts.length; i++) {
                html += '<div class="yg-fib-option yg-guardian-option" data-val="' + opts[i] + '">' + opts[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            /* Animate timer bar */
            var fill = document.getElementById('ygGuardianFill');
            fill.style.transition = 'width ' + perQ + 'ms linear';
            setTimeout(function() { fill.style.width = '0%'; }, 50);

            /* Animate threat approaching */
            var threat = document.getElementById('ygGuardianThreat');
            threat.style.transition = 'transform ' + perQ + 'ms linear';
            setTimeout(function() { threat.classList.add('yg-guardian-threat-active'); }, 50);

            var self = this;
            var timer = setTimeout(function() {
                state.qIdx++;
                self.render(data, container, state, onComplete);
            }, perQ);

            document.getElementById('ygGuardianOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-fib-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                clearTimeout(timer);

                var isCorrect = normalize(el.dataset.val) === normalize(q.answer);
                if (isCorrect) {
                    state.correct++;
                    /* Push threat back */
                    threat.classList.add('yg-guardian-threat-pushed');
                    WorldReaction.harmony(container, el, function() {
                        state.qIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.qIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                }
            });
        }
    };


    /* ----- MADGAB (Phonetic Decoding with Syllable Blocks) ----- */
    Renderers.madgab = {
        render: function(data, container, state, onComplete) {
            state.roundIdx = state.roundIdx || 0;
            state.correct = state.correct || 0;
            state.resolved = false;

            if (state.roundIdx >= data.rounds.length) {
                onComplete(state.correct >= Math.ceil(data.rounds.length / 2));
                return;
            }

            var r = data.rounds[state.roundIdx];
            var html = renderTypeLabel(data.label || 'Descifrar', 'madgab') +
                       '<div class="yg-instruction">' + (data.instruction || 'Lee las s\u00edlabas r\u00e1pido para descubrir la frase.') + '</div>' +
                       '<div class="yg-sub-counter">' + (state.roundIdx + 1) + ' de ' + data.rounds.length + '</div>';

            /* Split phonetic into syllable blocks */
            var syllables = r.phonetic.split(/[\s\-]+/);
            html += '<div class="yg-madgab-syllables" id="ygMadgabSyl">';
            for (var s = 0; s < syllables.length; s++) {
                html += '<span class="yg-madgab-block" style="animation-delay:' + (s * 0.12) + 's">' + syllables[s] + '</span>';
            }
            html += '</div>';

            /* Play button */
            html += '<button class="yg-parmin-play yg-madgab-play" id="ygMadgabPlay">&#9654; Escuchar r\u00e1pido</button>';

            if (r.hint) html += '<div class="yg-madgab-hint">' + r.hint + '</div>';
            html += '<input type="text" class="yg-dict-input" id="ygMadgabInput" placeholder="Escribe la frase real..." autocomplete="off" autocapitalize="sentences">';
            html += '<button class="yg-listo-btn" id="ygMadgabListo">Listo</button>';
            container.innerHTML = html;

            /* TTS for phonetic */
            document.getElementById('ygMadgabPlay').addEventListener('click', function() {
                Audio.speak(r.phonetic, { rate: 1.2 });
            });

            var self = this;
            var input = document.getElementById('ygMadgabInput');
            document.getElementById('ygMadgabListo').addEventListener('click', function() {
                if (state.resolved) return;
                var val = (input.value || '').trim();
                if (!val) return;
                state.resolved = true;

                if (normalize(val) === normalize(r.answer)) {
                    state.correct++;
                    WorldReaction.harmony(container, input, function() {
                        state.roundIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                } else {
                    input.value = r.answer;
                    WorldReaction.desequilibrio(container, input, function() {
                        state.roundIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
                    });
                }
            });
            input.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ygMadgabListo').click(); });
        }
    };

    /* ----- BOGGLE (Word Grid Search) ----- */
    Renderers.boggle = {
        render: function(data, container, state, onComplete) {
            state.found = state.found || {};
            state.selected = state.selected || [];
            state.tracing = false;

            var foundCount = Object.keys(state.found).length;
            var totalWords = data.words.length;
            var minWords = data.minWords || Math.ceil(totalWords / 2);
            var pct = Math.round((foundCount / totalWords) * 100);

            var html = renderTypeLabel(data.label || 'Boggle', 'boggle') +
                       '<div class="yg-instruction">' + (data.instruction || 'Encuentra las palabras ocultas.') + '</div>';

            /* Progress bar */
            html += '<div class="yg-boggle-progress"><div class="yg-boggle-progress-fill" style="width:' + pct + '%;"></div></div>';
            html += '<div class="yg-boggle-count">' + foundCount + ' de ' + totalWords + '</div>';

            /* Clickable letter grid */
            html += '<div class="yg-boggle-grid" id="ygBoggleGrid">';
            for (var r = 0; r < data.grid.length; r++) {
                html += '<div class="yg-boggle-row">';
                for (var c = 0; c < data.grid[r].length; c++) {
                    html += '<div class="yg-boggle-cell" data-r="' + r + '" data-c="' + c + '">' + data.grid[r][c] + '</div>';
                }
                html += '</div>';
            }
            html += '</div>';

            /* Current traced word preview */
            html += '<div class="yg-boggle-traced" id="ygBoggleTraced"></div>';

            /* Word list with found markers */
            html += '<div class="yg-boggle-words">';
            for (var i = 0; i < data.words.length; i++) {
                var w = data.words[i];
                var cls = state.found[normalize(w)] ? ' yg-boggle-found' : '';
                html += '<span class="yg-boggle-word' + cls + '">' + (state.found[normalize(w)] ? w : w.replace(/./g, '\u2022')) + '</span>';
            }
            html += '</div>';

            /* Fallback text input */
            html += '<div class="yg-boggle-input-row">';
            html += '<input type="text" class="yg-dict-input" id="ygBoggleInput" placeholder="...o escribe aqu\u00ed" autocomplete="off" autocapitalize="none">';
            html += '<button class="yg-listo-btn" id="ygBoggleSubmit">Buscar</button>';
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            var input = document.getElementById('ygBoggleInput');
            var grid = document.getElementById('ygBoggleGrid');
            var tracedEl = document.getElementById('ygBoggleTraced');
            var selectedCells = [];

            function isAdjacent(r1, c1, r2, c2) {
                return Math.abs(r1 - r2) <= 1 && Math.abs(c1 - c2) <= 1 && !(r1 === r2 && c1 === c2);
            }

            function getTracedWord() {
                var word = '';
                for (var i = 0; i < selectedCells.length; i++) {
                    word += data.grid[selectedCells[i].r][selectedCells[i].c];
                }
                return word;
            }

            function updateTraced() {
                var w = getTracedWord();
                tracedEl.textContent = w || '';
                tracedEl.style.opacity = w ? '1' : '0';
            }

            function clearSelection() {
                var cells = grid.querySelectorAll('.yg-boggle-cell');
                for (var i = 0; i < cells.length; i++) cells[i].classList.remove('yg-boggle-selected', 'yg-boggle-head');
                selectedCells = [];
                updateTraced();
            }

            function submitTracedWord() {
                var word = getTracedWord();
                if (!word) return;
                tryWordValue(word, tracedEl);
                clearSelection();
            }

            /* Grid click handler — trace words by clicking adjacent cells */
            grid.addEventListener('click', function(e) {
                var cell = e.target.closest('.yg-boggle-cell');
                if (!cell) return;
                var r = parseInt(cell.dataset.r);
                var c = parseInt(cell.dataset.c);

                /* If clicking already-selected head cell, submit the word */
                if (selectedCells.length > 0) {
                    var last = selectedCells[selectedCells.length - 1];
                    if (last.r === r && last.c === c) {
                        submitTracedWord();
                        return;
                    }
                }

                /* Check if cell already selected */
                for (var i = 0; i < selectedCells.length; i++) {
                    if (selectedCells[i].r === r && selectedCells[i].c === c) {
                        /* Deselect from this point forward */
                        selectedCells = selectedCells.slice(0, i);
                        var cells = grid.querySelectorAll('.yg-boggle-cell');
                        for (var j = 0; j < cells.length; j++) cells[j].classList.remove('yg-boggle-selected', 'yg-boggle-head');
                        for (var j = 0; j < selectedCells.length; j++) {
                            var sel = grid.querySelector('[data-r="' + selectedCells[j].r + '"][data-c="' + selectedCells[j].c + '"]');
                            if (sel) sel.classList.add('yg-boggle-selected');
                        }
                        if (selectedCells.length > 0) {
                            var lsel = selectedCells[selectedCells.length - 1];
                            var headEl = grid.querySelector('[data-r="' + lsel.r + '"][data-c="' + lsel.c + '"]');
                            if (headEl) headEl.classList.add('yg-boggle-head');
                        }
                        updateTraced();
                        return;
                    }
                }

                /* Must be adjacent to last selected cell (or first selection) */
                if (selectedCells.length > 0) {
                    var lastCell = selectedCells[selectedCells.length - 1];
                    if (!isAdjacent(lastCell.r, lastCell.c, r, c)) return;
                    /* Remove head class from previous */
                    var prevHead = grid.querySelector('.yg-boggle-head');
                    if (prevHead) prevHead.classList.remove('yg-boggle-head');
                }

                selectedCells.push({ r: r, c: c });
                cell.classList.add('yg-boggle-selected', 'yg-boggle-head');
                updateTraced();
            });

            /* Double-tap outside grid to clear */
            tracedEl.addEventListener('click', function() { submitTracedWord(); });

            function tryWordValue(val, feedbackEl) {
                if (!val) return;
                var norm = normalize(val);
                var isTarget = false;
                for (var i = 0; i < data.words.length; i++) {
                    if (normalize(data.words[i]) === norm) { isTarget = true; break; }
                }
                if (isTarget && !state.found[norm]) {
                    state.found[norm] = true;
                    WorldReaction.harmony(container, feedbackEl, function() {
                        if (Object.keys(state.found).length >= minWords) {
                            onComplete(true);
                        } else {
                            self.render(data, container, state, onComplete);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, feedbackEl, function() { if (input) input.value = ''; });
                }
            }

            function tryWord() {
                var val = (input.value || '').trim();
                tryWordValue(val, input);
            }

            document.getElementById('ygBoggleSubmit').addEventListener('click', tryWord);
            input.addEventListener('keydown', function(e) { if (e.key === 'Enter') tryWord(); });
        }
    };

    /* ----- BANANAGRAMS (Word Building from Letters) ----- */
    Renderers.bananagrams = {
        render: function(data, container, state, onComplete) {
            if (!data.targetWords || !data.targetWords.length || !data.letters) { onComplete(true); return; }
            state.found = state.found || {};
            state.built = state.built || [];
            state.usedTiles = state.usedTiles || {};
            var target = Math.ceil(data.targetWords.length / 2);
            var foundCount = Object.keys(state.found).length;
            var pct = Math.round((foundCount / data.targetWords.length) * 100);

            var html = renderTypeLabel(data.label || 'Bananagrams', 'bananagrams') +
                       '<div class="yg-instruction">' + (data.instruction || 'Forma palabras con las letras.') + '</div>';

            /* Progress bar */
            html += '<div class="yg-boggle-progress"><div class="yg-boggle-progress-fill" style="width:' + pct + '%;"></div></div>';
            html += '<div class="yg-boggle-count">' + foundCount + ' de ' + data.targetWords.length + '</div>';

            /* Word builder area */
            html += '<div class="yg-banana-builder" id="ygBananaBuilder">';
            if (state.built.length === 0) {
                html += '<span class="yg-banana-placeholder">Haz clic en las letras para formar una palabra...</span>';
            } else {
                for (var b = 0; b < state.built.length; b++) {
                    html += '<span class="yg-banana-built-tile" data-bidx="' + b + '">' + state.built[b].letter + '</span>';
                }
            }
            html += '</div>';

            /* Tile rack */
            html += '<div class="yg-banana-rack" id="ygBananaRack">';
            for (var i = 0; i < data.letters.length; i++) {
                var usedCls = state.usedTiles[i] ? ' yg-banana-used' : '';
                html += '<span class="yg-banana-tile' + usedCls + '" data-tidx="' + i + '">' + data.letters[i] + '</span>';
            }
            html += '</div>';

            /* Action buttons */
            html += '<div class="yg-banana-actions">';
            html += '<button class="yg-sb-clear" id="ygBananaClear">\u21BB Borrar</button>';
            html += '<button class="yg-listo-btn" id="ygBananaSubmit">Enviar</button>';
            html += '</div>';

            /* Target words */
            html += '<div class="yg-boggle-words">';
            for (var i = 0; i < data.targetWords.length; i++) {
                var w = data.targetWords[i];
                var cls = state.found[normalize(w)] ? ' yg-boggle-found' : '';
                html += '<span class="yg-boggle-word' + cls + '">' + (state.found[normalize(w)] ? w : w.replace(/./g, '\u2022')) + '</span>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;

            /* Click tile to add to builder */
            document.getElementById('ygBananaRack').addEventListener('click', function(e) {
                var tile = e.target.closest('.yg-banana-tile');
                if (!tile || tile.classList.contains('yg-banana-used')) return;
                var idx = parseInt(tile.dataset.tidx);
                state.built.push({ letter: data.letters[idx], tileIdx: idx });
                state.usedTiles[idx] = true;
                self.render(data, container, state, onComplete);
            });

            /* Click built tile to remove */
            document.getElementById('ygBananaBuilder').addEventListener('click', function(e) {
                var tile = e.target.closest('.yg-banana-built-tile');
                if (!tile) return;
                var bidx = parseInt(tile.dataset.bidx);
                var removed = state.built.splice(bidx, 1)[0];
                if (removed) delete state.usedTiles[removed.tileIdx];
                self.render(data, container, state, onComplete);
            });

            /* Clear button */
            document.getElementById('ygBananaClear').addEventListener('click', function() {
                state.built = [];
                state.usedTiles = {};
                self.render(data, container, state, onComplete);
            });

            /* Submit built word */
            document.getElementById('ygBananaSubmit').addEventListener('click', function() {
                var word = state.built.map(function(b) { return b.letter; }).join('');
                if (!word) return;
                var norm = normalize(word);
                var isTarget = false;
                for (var i = 0; i < data.targetWords.length; i++) {
                    if (normalize(data.targetWords[i]) === norm) { isTarget = true; break; }
                }
                var builder = document.getElementById('ygBananaBuilder');
                if (isTarget && !state.found[norm]) {
                    state.found[norm] = true;
                    state.built = [];
                    state.usedTiles = {};
                    WorldReaction.harmony(container, builder, function() {
                        if (Object.keys(state.found).length >= target) {
                            onComplete(true);
                        } else {
                            self.render(data, container, state, onComplete);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, builder, function() {
                        state.built = [];
                        state.usedTiles = {};
                        self.render(data, container, state, onComplete);
                    });
                }
            });
        }
    };


    /* ----- CLON (Perspective Shift with Side-by-Side) ----- */
    Renderers.clon = {
        render: function(data, container, state, onComplete) {
            state.pairIdx = state.pairIdx || 0;
            state.resolved = false;

            if (state.pairIdx >= data.pairs.length) { onComplete(true); return; }

            var pair = data.pairs[state.pairIdx];
            var html = renderTypeLabel(data.label || 'Clon', 'clon') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                       '<div class="yg-sub-counter">' + (state.pairIdx + 1) + ' de ' + data.pairs.length + '</div>';

            /* Side-by-side display */
            html += '<div class="yg-clon-split">';
            html += '<div class="yg-clon-side yg-clon-before">';
            html += '<div class="yg-clon-label">Antes</div>';
            html += '<div class="yg-clon-text">' + pair.present + '</div>';
            html += '</div>';
            html += '<div class="yg-clon-morph-arrow">\u27a1</div>';
            html += '<div class="yg-clon-side yg-clon-after">';
            html += '<div class="yg-clon-label">Despu\u00e9s</div>';
            html += '<div class="yg-clon-text yg-clon-mystery">?</div>';
            html += '</div>';
            html += '</div>';

            var options = [pair.past];
            for (var i = 0; i < data.pairs.length && options.length < 3; i++) {
                if (i !== state.pairIdx) options.push(data.pairs[i].past);
            }
            options = shuffle(options);

            html += '<div class="yg-fib-options" id="ygClonOpts">';
            for (var i = 0; i < options.length; i++) {
                html += '<div class="yg-fib-option" data-val="' + options[i] + '">' + options[i] + '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygClonOpts').addEventListener('click', function(e) {
                var el = e.target.closest('.yg-fib-option');
                if (!el || state.resolved) return;
                state.resolved = true;
                var all = container.querySelectorAll('.yg-fib-option');
                for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                if (normalize(el.dataset.val) === normalize(pair.past)) {
                    var mystery = container.querySelector('.yg-clon-mystery');
                    if (mystery) {
                        mystery.textContent = pair.past;
                        mystery.classList.remove('yg-clon-mystery');
                        mystery.classList.add('yg-clon-revealed');
                    }
                    WorldReaction.harmony(container, el, function() {
                        state.pairIdx++;
                        state.resolved = false;
                        self.render(data, container, state, onComplete);
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

    /* ----- CONJURO (Spell-casting Production) ----- */
    Renderers.conjuro = {
        render: function(data, container, state, onComplete) {
            /* Spell-based mode (spells[]) */
            if (data.spells && data.spells.length) {
                state.spellIdx = state.spellIdx || 0;
                state.correct = state.correct || 0;
                state.resolved = false;

                if (state.spellIdx >= data.spells.length) {
                    onComplete(state.correct >= Math.ceil(data.spells.length / 2));
                    return;
                }

                var spell = data.spells[state.spellIdx];
                var html = renderTypeLabel(data.label || 'Conjuro', 'conjuro') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                           '<div class="yg-sub-counter">' + (state.spellIdx + 1) + ' de ' + data.spells.length + '</div>' +
                           '<div class="yg-conjuro-prompt">' + spell.prompt + '</div>';
                if (spell.hint) html += '<div class="yg-conjuro-hint">' + spell.hint + '</div>';
                html += '<input type="text" class="yg-dict-input" id="ygConjuroInput" placeholder="Escribe la forma..." autocomplete="off" autocapitalize="none">';
                html += '<button class="yg-listo-btn" id="ygConjuroListo">Listo</button>';
                container.innerHTML = html;

                var self = this;
                var input = document.getElementById('ygConjuroInput');
                document.getElementById('ygConjuroListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;

                    if (normalize(val) === normalize(spell.answer)) {
                        state.correct++;
                        WorldReaction.harmony(container, input, function() {
                            state.spellIdx++;
                            state.resolved = false;
                            self.render(data, container, state, onComplete);
                        });
                    } else {
                        input.value = spell.answer;
                        WorldReaction.desequilibrio(container, input, function() {
                            state.spellIdx++;
                            state.resolved = false;
                            self.render(data, container, state, onComplete);
                        });
                    }
                });
                input.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ygConjuroListo').click(); });
                return;
            }

            /* Challenge-based mode (starters + evaluation) — free production */
            var html = renderTypeLabel(data.label || 'Conjuro', 'conjuro') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
            if (data.challenge) html += '<div class="yg-conjuro-challenge">' + data.challenge + '</div>';
            if (data.starters && data.starters.length) {
                html += '<div class="yg-conjuro-starters">';
                for (var i = 0; i < data.starters.length; i++) {
                    html += '<span class="yg-conjuro-starter">' + data.starters[i] + '</span>';
                }
                html += '</div>';
            }
            html += '<textarea class="yg-dict-input yg-conjuro-textarea" id="ygConjuroText" rows="4" placeholder="Escribe tu conjuro..."></textarea>';
            html += '<button class="yg-listo-btn" id="ygConjuroListo">Listo</button>';
            container.innerHTML = html;

            document.getElementById('ygConjuroListo').addEventListener('click', function() {
                var val = (document.getElementById('ygConjuroText').value || '').trim();
                if (!val || val.length < 10) return;
                /* Accept any production of reasonable length */
                var el = document.getElementById('ygConjuroText');
                WorldReaction.harmony(container, el, function() { onComplete(true); });
            });
        }
    };

    /* ----- ECO_RESTAURAR (Ecosystem Color Restoration) ----- */
    Renderers.eco_restaurar = {
        render: function(data, container, state, onComplete) {
            if (!data.scenes || !data.scenes.length) { onComplete(true); return; }
            state.sceneIdx = state.sceneIdx || 0;
            state.resolved = false;

            if (state.sceneIdx >= data.scenes.length) { onComplete(true); return; }

            var scene = data.scenes[state.sceneIdx];
            var html = renderTypeLabel(data.label || 'Restaurar', 'eco_restaurar') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>' +
                       '<div class="yg-sub-counter">' + (state.sceneIdx + 1) + ' de ' + data.scenes.length + '</div>' +
                       '<div class="yg-eco-faded">' + scene.faded + '</div>';
            if (scene.prompt) html += '<div class="yg-eco-prompt">' + scene.prompt + '</div>';

            if (scene.options && scene.options.length) {
                html += '<div class="yg-eco-options" id="ygEcoOpts">';
                for (var i = 0; i < scene.options.length; i++) {
                    html += '<div class="yg-eco-option" data-idx="' + i + '">' + scene.options[i] + '</div>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygEcoInput" placeholder="Escribe tu respuesta..." autocomplete="off" autocapitalize="sentences">';
                html += '<button class="yg-listo-btn" id="ygEcoListo">Restaurar</button>';
            }
            container.innerHTML = html;

            var self = this;
            if (scene.options && scene.options.length) {
                document.getElementById('ygEcoOpts').addEventListener('click', function(e) {
                    var el = e.target.closest('.yg-eco-option');
                    if (!el || state.resolved) return;
                    state.resolved = true;
                    var all = container.querySelectorAll('.yg-eco-option');
                    for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = 'none';

                    if (normalize(el.textContent) === normalize(scene.restored)) {
                        /* Show restored version with color */
                        WorldReaction.harmony(container, el, function() {
                            container.innerHTML = renderTypeLabel(data.label || 'Restaurar', 'eco_restaurar') +
                                '<div class="yg-eco-restored">' + scene.restored + '</div>' +
                                (scene.color ? '<div class="yg-eco-color" style="color:' + scene.color + ';">\u2605 ' + scene.color + '</div>' : '') +
                                '<button class="yg-listo-btn" id="ygEcoContinue">Continuar</button>';
                            document.getElementById('ygEcoContinue').addEventListener('click', function() {
                                state.sceneIdx++;
                                state.resolved = false;
                                self.render(data, container, state, onComplete);
                            });
                        });
                    } else {
                        WorldReaction.desequilibrio(container, el, function() {
                            state.resolved = false;
                            for (var j = 0; j < all.length; j++) all[j].style.pointerEvents = '';
                        });
                    }
                });
            } else {
                var input = document.getElementById('ygEcoInput');
                document.getElementById('ygEcoListo').addEventListener('click', function() {
                    if (state.resolved) return;
                    var val = (input.value || '').trim();
                    if (!val) return;
                    state.resolved = true;

                    /* Accept if answer contains key words from the restored text */
                    var restoredWords = normalize(scene.restored || '').split(/\s+/).filter(function(w) { return w.length > 3; });
                    var matchCount = 0;
                    var normVal = normalize(val);
                    for (var rw = 0; rw < restoredWords.length; rw++) {
                        if (normVal.indexOf(restoredWords[rw]) !== -1) matchCount++;
                    }
                    var threshold = Math.max(1, Math.ceil(restoredWords.length * 0.4));
                    if (matchCount >= threshold) {
                        WorldReaction.harmony(container, input, function() {
                            /* Show restored version like options mode does */
                            container.innerHTML = renderTypeLabel(data.label || 'Restaurar', 'eco_restaurar') +
                                '<div class="yg-eco-restored">' + scene.restored + '</div>' +
                                (scene.color ? '<div class="yg-eco-color" style="color:' + scene.color + ';">\u2605 ' + scene.color + '</div>' : '') +
                                '<button class="yg-listo-btn" id="ygEcoContinueFT">Continuar</button>';
                            document.getElementById('ygEcoContinueFT').addEventListener('click', function() {
                                state.sceneIdx++;
                                state.resolved = false;
                                self.render(data, container, state, onComplete);
                            });
                        });
                    } else {
                        WorldReaction.desequilibrio(container, input, function() { state.resolved = false; });
                    }
                });
                input.addEventListener('keydown', function(e) { if (e.key === 'Enter') document.getElementById('ygEcoListo').click(); });
            }
        }
    };


    /* ----- SPACEMAN (Hangman Variant \u2014 Jaguar Figure) ----- */
    Renderers.spaceman = {
        _jaguarSVG: function(misses) {
            var parts = [
                '<circle cx="50" cy="20" r="10" fill="none" stroke-width="2.5"/>',
                '<line x1="50" y1="30" x2="50" y2="58" stroke-width="2.5"/>',
                '<line x1="50" y1="38" x2="34" y2="50" stroke-width="2"/>',
                '<line x1="50" y1="38" x2="66" y2="50" stroke-width="2"/>',
                '<line x1="50" y1="58" x2="36" y2="75" stroke-width="2"/>',
                '<line x1="50" y1="58" x2="64" y2="75" stroke-width="2"/>'
            ];
            var extras = [
                '<polygon points="42,12 45,4 48,14" fill="none" stroke-width="1.5"/><polygon points="52,14 55,4 58,12" fill="none" stroke-width="1.5"/>',
                '<circle cx="46" cy="18" r="1.5"/><circle cx="54" cy="18" r="1.5"/>',
                '<path d="M50,58 Q68,62 72,52 Q76,44 70,40" fill="none" stroke-width="2"/>'
            ];
            var svg = '<svg viewBox="0 0 100 82" class="yg-jaguar-svg">';
            for (var i = 0; i < Math.min(misses, 6); i++) {
                svg += '<g class="yg-jaguar-part" style="animation-delay:' + (i * 0.1) + 's">' + parts[i] + '</g>';
            }
            if (misses >= 3) svg += '<g class="yg-jaguar-detail">' + extras[0] + '</g>';
            if (misses >= 4) svg += '<g class="yg-jaguar-detail">' + extras[1] + '</g>';
            if (misses >= 5) svg += '<g class="yg-jaguar-detail">' + extras[2] + '</g>';
            svg += '</svg>';
            return svg;
        },
        render: function(data, container, state, onComplete) {
            state.phraseIdx = state.phraseIdx || 0;
            state.correct = state.correct || 0;

            if (state.phraseIdx >= data.phrases.length) {
                onComplete(state.correct >= Math.ceil(data.phrases.length / 2));
                return;
            }

            var phrase = data.phrases[state.phraseIdx];
            state.guessed = state.guessed || {};
            state.misses = state.misses || 0;
            var maxMisses = 6;
            var answer = phrase.answer.toUpperCase();

            /* Build letter boxes */
            var allRevealed = true;
            var letterBoxes = '';
            for (var i = 0; i < answer.length; i++) {
                var ch = answer[i];
                if (ch === ' ') {
                    letterBoxes += '<span class="yg-spaceman-space"></span>';
                } else if (ch === '-' || ch === '\'') {
                    letterBoxes += '<span class="yg-spaceman-ltr yg-spaceman-revealed">' + ch + '</span>';
                } else if (state.guessed[ch]) {
                    letterBoxes += '<span class="yg-spaceman-ltr yg-spaceman-revealed">' + ch + '</span>';
                } else {
                    letterBoxes += '<span class="yg-spaceman-ltr"></span>';
                    allRevealed = false;
                }
            }

            if (allRevealed) {
                state.correct++;
                state.phraseIdx++;
                state.guessed = {};
                state.misses = 0;
                var self = this;
                WorldReaction.harmony(container, container, function() {
                    self.render(data, container, state, onComplete);
                });
                return;
            }

            if (state.misses >= maxMisses) {
                state.phraseIdx++;
                state.guessed = {};
                state.misses = 0;
                var self = this;
                WorldReaction.desequilibrio(container, container, function() {
                    self.render(data, container, state, onComplete);
                });
                return;
            }

            var html = renderTypeLabel(data.label || 'Yaguar\u00e1', 'spaceman') +
                       '<div class="yg-instruction">' + (data.instruction || 'Adivina la palabra antes de que el yaguar\u00e1 aparezca.') + '</div>' +
                       '<div class="yg-sub-counter">' + (state.phraseIdx + 1) + ' de ' + data.phrases.length + '</div>';
            if (phrase.hint) html += '<div class="yg-spaceman-hint">' + phrase.hint + '</div>';

            /* SVG Jaguar figure */
            html += '<div class="yg-spaceman-figure">' + this._jaguarSVG(state.misses) + '</div>';

            /* Life indicators */
            html += '<div class="yg-spaceman-lives">';
            for (var li = 0; li < maxMisses; li++) {
                html += '<span class="yg-spaceman-life' + (li < state.misses ? ' yg-spaceman-life-lost' : '') + '"></span>';
            }
            html += '</div>';

            /* Word display with letter boxes */
            html += '<div class="yg-spaceman-word-display">' + letterBoxes + '</div>';

            /* Alphabet keyboard - grouped rows */
            html += '<div class="yg-spaceman-keyboard" id="ygSpacemanKB">';
            var rows = ['QWERTYUIOP', 'ASDFGHJKL\u00d1', 'ZXCVBNM', '\u00c1\u00c9\u00cd\u00d3\u00da\u00dc'];
            for (var ri = 0; ri < rows.length; ri++) {
                html += '<div class="yg-spaceman-kb-row">';
                for (var ci = 0; ci < rows[ri].length; ci++) {
                    var ch = rows[ri][ci];
                    var inWord = answer.indexOf(ch) !== -1;
                    var usedCls = '';
                    if (state.guessed[ch]) {
                        usedCls = inWord ? ' yg-spaceman-hit' : ' yg-spaceman-miss';
                    }
                    html += '<span class="yg-spaceman-key' + usedCls + '" data-key="' + ch + '">' + ch + '</span>';
                }
                html += '</div>';
            }
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            document.getElementById('ygSpacemanKB').addEventListener('click', function(e) {
                var key = e.target.closest('.yg-spaceman-key');
                if (!key || key.classList.contains('yg-spaceman-hit') || key.classList.contains('yg-spaceman-miss')) return;
                var ch = key.dataset.key;
                state.guessed[ch] = true;
                if (answer.indexOf(ch) === -1) { state.misses++; }
                self.render(data, container, state, onComplete);
            });
        }
    };


    /* ----- PAR MÍNIMO (Minimal Pair with Waveform Visual) ----- */
    Renderers.par_minimo = {
        render: function(data, container, state, onComplete) {
            state.idx = state.idx || 0;
            state.correct = state.correct || 0;
            var pair = data.pairs[state.idx];
            if (!pair) { onComplete(state.correct >= Math.ceil(data.pairs.length * 0.6)); return; }

            var html = renderTypeLabel(data.label || 'Par m\u00ednimo', 'par_minimo') +
                       '<div class="yg-instruction">' + (data.instruction || 'Escucha y elige la palabra correcta.') + '</div>';
            if (data.pairs.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.idx + 1) + ' de ' + data.pairs.length + '</div>';
            }

            html += '<div class="yg-parmin-area">';
            html += '<button class="yg-parmin-play yg-parmin-play-main" id="ygParMinPlay">';
            html += '<span class="yg-parmin-wave"><span></span><span></span><span></span><span></span><span></span></span>';
            html += ' Escuchar</button>';

            html += '<div class="yg-parmin-choices">';
            html += '<div class="yg-parmin-card" data-pick="A">';
            html += '<div class="yg-parmin-card-word">' + pair.wordA + '</div>';
            if (pair.ipaA) html += '<div class="yg-parmin-card-ipa">/' + pair.ipaA + '/</div>';
            html += '</div>';
            html += '<div class="yg-parmin-vs">o</div>';
            html += '<div class="yg-parmin-card" data-pick="B">';
            html += '<div class="yg-parmin-card-word">' + pair.wordB + '</div>';
            if (pair.ipaB) html += '<div class="yg-parmin-card-ipa">/' + pair.ipaB + '/</div>';
            html += '</div>';
            html += '</div>';
            if (pair.hint) html += '<div class="yg-parmin-hint">' + pair.hint + '</div>';
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            var spoken = false;
            var playBtn = document.getElementById('ygParMinPlay');
            playBtn.addEventListener('click', function() {
                var word = pair.correct === 'A' ? pair.wordA : pair.wordB;
                Audio.speak(word);
                spoken = true;
                playBtn.classList.add('yg-parmin-playing');
                setTimeout(function() { playBtn.classList.remove('yg-parmin-playing'); }, 1500);
            });

            var cards = container.querySelectorAll('.yg-parmin-card');
            for (var i = 0; i < cards.length; i++) {
                cards[i].addEventListener('click', function() {
                    if (!spoken) {
                        Audio.speak(pair.correct === 'A' ? pair.wordA : pair.wordB);
                        spoken = true;
                        return;
                    }
                    var pick = this.getAttribute('data-pick');
                    var el = this;
                    if (pick === pair.correct) {
                        state.correct++;
                        WorldReaction.harmony(container, el, function() {
                            state.idx++;
                            self.render(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, el, function() {
                            state.idx++;
                            self.render(data, container, state, onComplete);
                        });
                    }
                });
            }
        }
    };

    /* ----- DICTOGLOSS (Listen-Reconstruct) ----- */
    Renderers.dictogloss = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'read';

            if (state.phase === 'read') {
                var html = renderTypeLabel(data.label || 'Dictogloss', 'dictogloss') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-dictogloss-text" id="ygDictoText">' + data.text + '</div>';
                html += '<div class="yg-dictogloss-timer" id="ygDictoTimer"></div>';
                container.innerHTML = html;

                var timerEl = document.getElementById('ygDictoTimer');
                var remaining = Math.ceil(data.displayTime / 1000);
                timerEl.textContent = remaining + 's';
                var iv = setInterval(function() {
                    remaining--;
                    timerEl.textContent = remaining > 0 ? remaining + 's' : '';
                    if (remaining <= 0) {
                        clearInterval(iv);
                        state.phase = 'write';
                        Renderers.dictogloss.render(data, container, state, onComplete);
                    }
                }, 1000);
            } else if (state.phase === 'write') {
                var html = renderTypeLabel(data.label || 'Dictogloss', 'dictogloss') +
                           '<div class="yg-instruction">Reconstruye el texto de memoria.</div>';
                html += '<textarea class="yg-dictogloss-input" id="ygDictoInput" rows="5" placeholder="Escribe lo que recuerdas..."></textarea>';
                html += '<button class="yg-listo-btn" id="ygDictoSubmit">Listo</button>';
                container.innerHTML = html;

                var self = this;
                document.getElementById('ygDictoSubmit').addEventListener('click', function() {
                    var text = (document.getElementById('ygDictoInput').value || '').trim();
                    state.attempt = text;
                    state.phase = 'compare';
                    self.render(data, container, state, onComplete);
                });
            } else if (state.phase === 'compare') {
                var html = renderTypeLabel(data.label || 'Dictogloss', 'dictogloss') +
                           '<div class="yg-instruction">Compara tu texto con el original.</div>';
                html += '<div class="yg-dictogloss-original"><strong>Original:</strong> ' + data.text + '</div>';
                html += '<div class="yg-dictogloss-attempt"><strong>Tu texto:</strong> ' + (state.attempt || '') + '</div>';

                /* Keyword match scoring */
                var matched = 0;
                var attemptNorm = normalize(state.attempt || '');
                for (var i = 0; i < data.keywords.length; i++) {
                    if (attemptNorm.indexOf(normalize(data.keywords[i])) !== -1) matched++;
                }
                var pct = data.keywords.length > 0 ? Math.round(matched / data.keywords.length * 100) : 0;
                html += '<div class="yg-dictogloss-score">Palabras clave: ' + matched + '/' + data.keywords.length + ' (' + pct + '%)</div>';
                html += '<button class="yg-listo-btn" id="ygDictoContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygDictoContinue').addEventListener('click', function() {
                    onComplete(matched >= data.minMatch);
                });
            }
        }
    };


    /* ----- CORRECTOR (Error Spotting with Inline Highlights) ----- */
    Renderers.corrector = {
        render: function(data, container, state, onComplete) {
            state.found = state.found || {};
            state.corrections = state.corrections || {};
            state.activeErr = (state.activeErr !== undefined) ? state.activeErr : -1;

            var foundCount = Object.keys(state.found).length;
            var totalErrors = data.errors.length;
            var pct = Math.round((foundCount / totalErrors) * 100);

            var html = renderTypeLabel(data.label || 'Corrector', 'corrector') +
                       '<div class="yg-instruction">' + (data.instruction || 'Encuentra y corrige los errores.') + '</div>';

            /* Progress bar */
            html += '<div class="yg-boggle-progress"><div class="yg-boggle-progress-fill" style="width:' + pct + '%;"></div></div>';
            html += '<div class="yg-corrector-count">' + foundCount + ' de ' + totalErrors + ' errores corregidos</div>';

            /* Build passage with clickable error spans */
            var passage = data.passage;
            var words = passage.split(/(\s+)/);
            html += '<div class="yg-corrector-passage" id="ygCorrectorPassage">';
            for (var w = 0; w < words.length; w++) {
                var clean = words[w].replace(/[.,;:!?\u00a1\u00bf]/g, '').trim();
                var errIdx = -1;
                for (var e = 0; e < data.errors.length; e++) {
                    if (normalize(clean) === normalize(data.errors[e].wrong)) { errIdx = e; break; }
                }
                if (errIdx >= 0 && !state.found[errIdx]) {
                    var activeCls = state.activeErr === errIdx ? ' yg-corrector-active' : '';
                    html += '<span class="yg-corrector-word yg-corrector-clickable' + activeCls + '" data-err="' + errIdx + '">' + words[w] + '</span>';
                } else if (errIdx >= 0 && state.found[errIdx]) {
                    html += '<span class="yg-corrector-word yg-corrector-fixed">' + (state.corrections[errIdx] || data.errors[errIdx].correct) + '</span>';
                } else {
                    html += words[w];
                }
            }
            html += '</div>';

            /* Correction input area */
            html += '<div class="yg-corrector-input-area" id="ygCorrectorInput" style="display:' + (state.activeErr >= 0 ? 'block' : 'none') + ';">';
            html += '<div class="yg-corrector-prompt" id="ygCorrectorPrompt">' + (state.activeErr >= 0 ? '\u00ab' + data.errors[state.activeErr].wrong + '\u00bb \u2192 \u00bfcorrecci\u00f3n?' : '') + '</div>';
            html += '<div class="yg-corrector-fix-row">';
            html += '<input type="text" class="yg-dict-input" id="ygCorrectorAnswer" placeholder="Correcci\u00f3n..." autocomplete="off" autocapitalize="none">';
            html += '<button class="yg-listo-btn" id="ygCorrectorSubmit">Corregir</button>';
            html += '</div>';
            html += '</div>';
            container.innerHTML = html;

            var self = this;
            var clickableWords = container.querySelectorAll('.yg-corrector-clickable');
            for (var c = 0; c < clickableWords.length; c++) {
                clickableWords[c].addEventListener('click', function() {
                    var idx = parseInt(this.getAttribute('data-err'));
                    state.activeErr = idx;
                    /* Highlight active word */
                    var all = container.querySelectorAll('.yg-corrector-clickable');
                    for (var a = 0; a < all.length; a++) all[a].classList.remove('yg-corrector-active');
                    this.classList.add('yg-corrector-active');
                    var inputArea = document.getElementById('ygCorrectorInput');
                    inputArea.style.display = 'block';
                    document.getElementById('ygCorrectorPrompt').textContent = '\u00ab' + data.errors[idx].wrong + '\u00bb \u2192 \u00bfcorrecci\u00f3n?';
                    var ansInput = document.getElementById('ygCorrectorAnswer');
                    ansInput.value = '';
                    ansInput.setAttribute('data-err', idx);
                    ansInput.focus();
                });
            }

            var submitBtn = document.getElementById('ygCorrectorSubmit');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    var ansInput = document.getElementById('ygCorrectorAnswer');
                    var idx = parseInt(ansInput.getAttribute('data-err'));
                    if (isNaN(idx)) return;
                    var val = (ansInput.value || '').trim();
                    if (normalize(val) === normalize(data.errors[idx].correct)) {
                        state.found[idx] = true;
                        state.corrections[idx] = val;
                        state.activeErr = -1;
                        WorldReaction.harmony(container, ansInput, function() {
                            if (Object.keys(state.found).length >= data.errors.length) {
                                onComplete(true);
                            } else {
                                self.render(data, container, state, onComplete);
                            }
                        });
                    } else {
                        WorldReaction.desequilibrio(container, ansInput);
                    }
                });
                var ansInput = document.getElementById('ygCorrectorAnswer');
                if (ansInput) {
                    ansInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') submitBtn.click(); });
                }
            }
        }
    };


    /* ----- BRECHA (Information Gap with Card Visuals) ----- */
    Renderers.brecha = {
        render: function(data, container, state, onComplete) {
            state.qIdx = state.qIdx || 0;
            state.correct = state.correct || 0;
            var q = data.questions[state.qIdx];
            if (!q) { onComplete(state.correct >= Math.ceil(data.questions.length * 0.6)); return; }

            var html = renderTypeLabel(data.label || 'Brecha', 'brecha') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            if (data.questions.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.qIdx + 1) + ' de ' + data.questions.length + '</div>';
            }

            /* Two cards side by side */
            html += '<div class="yg-brecha-cards">';
            html += '<div class="yg-brecha-card yg-brecha-card-a">';
            html += '<div class="yg-brecha-card-header">';
            html += '<span class="yg-brecha-card-badge">A</span>';
            html += '<span class="yg-brecha-card-label">Tu tarjeta</span>';
            html += '</div>';
            html += '<div class="yg-brecha-card-text">' + data.cardA.text + '</div>';
            html += '</div>';
            html += '<div class="yg-brecha-card yg-brecha-card-b">';
            html += '<div class="yg-brecha-card-header">';
            html += '<span class="yg-brecha-card-badge">B</span>';
            html += '<span class="yg-brecha-card-label">Tarjeta oculta</span>';
            html += '</div>';
            html += '<div class="yg-brecha-card-text yg-brecha-hidden">' + (data.cardB ? data.cardB.text : '???') + '</div>';
            html += '</div>';
            html += '</div>';

            /* Question */
            html += '<div class="yg-brecha-question">' + q.question + '</div>';

            /* Options or typing */
            if (q.options && q.options.length > 0) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-fib-options" id="ygBrechaOptions">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygBrechaInput" placeholder="Tu respuesta..." autocomplete="off" autocapitalize="none">';
                html += '<button class="yg-listo-btn" id="ygBrechaSubmit">Responder</button>';
            }
            container.innerHTML = html;

            var self = this;
            function advance(success) {
                if (success) state.correct++;
                state.qIdx++;
                self.render(data, container, state, onComplete);
            }

            if (q.options && q.options.length > 0) {
                var btns = container.querySelectorAll('.yg-fib-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        if (normalize(this.textContent) === normalize(q.answer)) {
                            WorldReaction.harmony(container, this, function() { advance(true); });
                        } else {
                            WorldReaction.desequilibrio(container, this, function() { advance(false); });
                        }
                    });
                }
            } else {
                document.getElementById('ygBrechaSubmit').addEventListener('click', function() {
                    var val = (document.getElementById('ygBrechaInput').value || '').trim();
                    if (normalize(val) === normalize(q.answer)) {
                        WorldReaction.harmony(container, document.getElementById('ygBrechaInput'), function() { advance(true); });
                    } else {
                        WorldReaction.desequilibrio(container, document.getElementById('ygBrechaInput'), function() { advance(false); });
                    }
                });
            }
        }
    };

    /* ----- RESUMEN (Summarization) ----- */
    Renderers.resumen = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'read';

            if (state.phase === 'read') {
                var html = renderTypeLabel(data.label || 'Resumen', 'resumen') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-resumen-passage">' + data.passage + '</div>';
                html += '<button class="yg-listo-btn" id="ygResumenReady">He leído — resumir</button>';
                container.innerHTML = html;

                document.getElementById('ygResumenReady').addEventListener('click', function() {
                    state.phase = 'write';
                    Renderers.resumen.render(data, container, state, onComplete);
                });
            } else if (state.phase === 'write') {
                var html = renderTypeLabel(data.label || 'Resumen', 'resumen') +
                           '<div class="yg-instruction">Resume lo esencial en ' + data.maxWords + ' palabras o menos.</div>';
                html += '<textarea class="yg-dictogloss-input" id="ygResumenInput" rows="4" placeholder="Tu resumen..."></textarea>';
                html += '<div class="yg-resumen-wordcount" id="ygResumenWC">0 palabras</div>';
                html += '<button class="yg-listo-btn" id="ygResumenSubmit">Listo</button>';
                container.innerHTML = html;

                var textarea = document.getElementById('ygResumenInput');
                var wcEl = document.getElementById('ygResumenWC');
                textarea.addEventListener('input', function() {
                    var words = (textarea.value || '').trim().split(/\s+/).filter(function(w) { return w.length > 0; });
                    wcEl.textContent = words.length + ' palabras';
                });

                document.getElementById('ygResumenSubmit').addEventListener('click', function() {
                    state.attempt = (textarea.value || '').trim();
                    state.phase = 'compare';
                    Renderers.resumen.render(data, container, state, onComplete);
                });
            } else if (state.phase === 'compare') {
                var html = renderTypeLabel(data.label || 'Resumen', 'resumen') +
                           '<div class="yg-instruction">Compara tu resumen.</div>';
                html += '<div class="yg-dictogloss-attempt"><strong>Tu resumen:</strong> ' + (state.attempt || '') + '</div>';
                html += '<div class="yg-dictogloss-original"><strong>Resumen modelo:</strong> ' + (data.modelSummary || '') + '</div>';

                /* Key sentence coverage */
                var keySentences = data.keySentences || [];
                var matched = 0;
                var attemptNorm = normalize(state.attempt || '');
                for (var i = 0; i < keySentences.length; i++) {
                    var keyWords = keySentences[i].split(/\s+/);
                    var found = 0;
                    for (var k = 0; k < keyWords.length; k++) {
                        if (attemptNorm.indexOf(normalize(keyWords[k])) !== -1) found++;
                    }
                    if (found >= Math.ceil(keyWords.length * 0.4)) matched++;
                }
                html += '<div class="yg-dictogloss-score">Ideas clave capturadas: ' + matched + '/' + keySentences.length + '</div>';
                html += '<button class="yg-listo-btn" id="ygResumenContinue">Continuar</button>';
                container.innerHTML = html;

                var pass = keySentences.length === 0 || matched >= Math.ceil(keySentences.length * 0.4);
                document.getElementById('ygResumenContinue').addEventListener('click', function() {
                    WorldReaction.harmony(container, container.querySelector('.yg-dictogloss-attempt'), function() { onComplete(pass); });
                });
            }
        }
    };


    /* ----- REGISTRO (Register Shifting with Visual Meter) ----- */
    Renderers.registro = {
        render: function(data, container, state, onComplete) {
            var html = renderTypeLabel(data.label || 'Registro', 'registro') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            /* Register meter */
            var registers = ['coloquial', 'informal', 'neutro', 'formal', 'acad\u00e9mico'];
            var sourceIdx = -1, targetIdx = -1;
            for (var ri = 0; ri < registers.length; ri++) {
                if (normalize(data.sourceRegister).indexOf(normalize(registers[ri])) !== -1) sourceIdx = ri;
                if (normalize(data.targetRegister).indexOf(normalize(registers[ri])) !== -1) targetIdx = ri;
            }

            html += '<div class="yg-registro-meter">';
            for (var ri = 0; ri < registers.length; ri++) {
                var cls = 'yg-registro-meter-seg';
                if (ri === sourceIdx) cls += ' yg-registro-source-seg';
                if (ri === targetIdx) cls += ' yg-registro-target-seg';
                html += '<div class="' + cls + '">' + registers[ri] + '</div>';
            }
            html += '</div>';

            html += '<div class="yg-registro-situation">' + data.situation + '</div>';

            /* Source text */
            html += '<div class="yg-registro-source">';
            html += '<span class="yg-registro-tag">' + data.sourceRegister + '</span>';
            html += '<div class="yg-registro-text">' + data.sourceText + '</div>';
            html += '</div>';

            html += '<div class="yg-registro-target-tag">\u2192 ' + data.targetRegister + '</div>';

            /* Choice mode (B1) or typing mode (B2+) */
            if (data.options && data.options.length > 0) {
                var opts = shuffle(data.options.slice());
                html += '<div class="yg-fib-options" id="ygRegistroOpts">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<textarea class="yg-dictogloss-input" id="ygRegistroInput" rows="3" placeholder="Reescribe en registro ' + data.targetRegister + '..."></textarea>';
                html += '<button class="yg-listo-btn" id="ygRegistroSubmit">Listo</button>';
            }
            container.innerHTML = html;

            if (data.options && data.options.length > 0) {
                var btns = container.querySelectorAll('.yg-fib-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        var el = this;
                        if (normalize(el.textContent) === normalize(data.modelAnswer)) {
                            WorldReaction.harmony(container, el, function() { onComplete(true); });
                        } else {
                            WorldReaction.desequilibrio(container, el, function() {
                                el.style.opacity = '0.4';
                                el.style.pointerEvents = 'none';
                            });
                        }
                    });
                }
            } else {
                document.getElementById('ygRegistroSubmit').addEventListener('click', function() {
                    var val = (document.getElementById('ygRegistroInput').value || '').trim();
                    var matched = 0;
                    for (var i = 0; i < data.keywords.length; i++) {
                        if (normalize(val).indexOf(normalize(data.keywords[i])) !== -1) matched++;
                    }
                    var pass = matched >= Math.ceil(data.keywords.length * 0.5);
                    if (pass) {
                        WorldReaction.harmony(container, document.getElementById('ygRegistroInput'), function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, document.getElementById('ygRegistroInput'));
                    }
                });
            }
        }
    };

    /* ----- DEBATE (Structured Argumentation) ----- */
    Renderers.debate = {
        render: function(data, container, state, onComplete) {
            state.roundIdx = state.roundIdx || 0;
            state.correct = state.correct || 0;
            var round = data.rounds[state.roundIdx];
            if (!round) { onComplete(state.correct >= Math.ceil(data.rounds.length * 0.5)); return; }

            var html = renderTypeLabel(data.label || 'Debate', 'debate') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            html += '<div class="yg-debate-proposition">' + data.proposition + '</div>';

            if (data.rounds.length > 1) {
                html += '<div class="yg-sub-counter">Ronda ' + (state.roundIdx + 1) + ' de ' + data.rounds.length + '</div>';
            }

            html += '<div class="yg-debate-prompt">' + round.prompt + '</div>';

            if (round.options && round.options.length > 0) {
                var debOpts = shuffle(round.options.slice());
                html += '<div class="yg-fib-options" id="ygDebateOpts">';
                for (var i = 0; i < debOpts.length; i++) {
                    html += '<button class="yg-fib-option yg-debate-option">' + debOpts[i] + '</button>';
                }
                html += '</div>';
            }
            container.innerHTML = html;

            var self = this;
            var debateAnswer = round.options ? round.options[round.correctIndex] : '';
            var btns = container.querySelectorAll('.yg-debate-option');
            for (var b = 0; b < btns.length; b++) {
                (function(idx) {
                    btns[idx].addEventListener('click', function() {
                        if (normalize(btns[idx].textContent) === normalize(debateAnswer)) {
                            state.correct++;
                            WorldReaction.harmony(container, btns[idx], function() {
                                state.roundIdx++;
                                self.render(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, btns[idx], function() {
                                state.roundIdx++;
                                self.render(data, container, state, onComplete);
                            });
                        }
                    });
                })(b);
            }
        }
    };

    /* ----- DESCRIPCION (Descriptive Production) ----- */
    Renderers.descripcion = {
        render: function(data, container, state, onComplete) {
            var html = renderTypeLabel(data.label || 'Descripción', 'descripcion') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            html += '<div class="yg-descripcion-scene">' + data.scene + '</div>';

            if (data.promptQuestion) {
                html += '<div class="yg-descripcion-prompt">' + data.promptQuestion + '</div>';
            }

            if (data.vocabulary && data.vocabulary.length > 0) {
                html += '<div class="yg-descripcion-vocab">';
                for (var i = 0; i < data.vocabulary.length; i++) {
                    html += '<span class="yg-descripcion-word">' + data.vocabulary[i] + '</span>';
                }
                html += '</div>';
            }

            /* Choice mode (A1-A2) or writing mode (B1+) */
            if (data.options && data.options.length > 0) {
                html += '<div class="yg-fib-options" id="ygDescOpts">';
                for (var i = 0; i < data.options.length; i++) {
                    html += '<button class="yg-fib-option yg-descripcion-option">' + data.options[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<textarea class="yg-dictogloss-input" id="ygDescInput" rows="4" placeholder="Describe lo que ves..."></textarea>';
                html += '<button class="yg-listo-btn" id="ygDescSubmit">Listo</button>';
            }
            container.innerHTML = html;

            if (data.options && data.options.length > 0) {
                var btns = container.querySelectorAll('.yg-descripcion-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        var el = this;
                        if (normalize(el.textContent) === normalize(data.modelAnswer)) {
                            WorldReaction.harmony(container, el, function() { onComplete(true); });
                        } else {
                            WorldReaction.desequilibrio(container, el, function() {
                                el.style.opacity = '0.4';
                                el.style.pointerEvents = 'none';
                            });
                        }
                    });
                }
            } else {
                document.getElementById('ygDescSubmit').addEventListener('click', function() {
                    var val = (document.getElementById('ygDescInput').value || '').trim();
                    var vocabUsed = 0;
                    for (var i = 0; i < data.vocabulary.length; i++) {
                        if (normalize(val).indexOf(normalize(data.vocabulary[i])) !== -1) vocabUsed++;
                    }
                    var pass = vocabUsed >= Math.ceil(data.vocabulary.length * 0.3) || val.split(/\s+/).length >= 5;
                    if (pass) {
                        WorldReaction.harmony(container, document.getElementById('ygDescInput'), function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, document.getElementById('ygDescInput'));
                    }
                });
            }
        }
    };

    /* ----- RITMO (Stress/Prosody) ----- */
    Renderers.ritmo = {
        render: function(data, container, state, onComplete) {
            state.idx = state.idx || 0;
            state.correct = state.correct || 0;
            var w = data.words[state.idx];
            if (!w) { onComplete(state.correct >= Math.ceil(data.words.length * 0.6)); return; }

            var html = renderTypeLabel(data.label || 'Ritmo', 'ritmo') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            if (data.words.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.idx + 1) + ' de ' + data.words.length + '</div>';
            }

            html += '<div class="yg-ritmo-word">' + w.word + '</div>';
            html += '<div class="yg-ritmo-play"><button class="yg-parmin-play" id="ygRitmoPlay">&#9654; Escuchar</button></div>';
            html += '<div class="yg-ritmo-syllables" id="ygRitmoSyl">';
            for (var i = 0; i < w.syllables.length; i++) {
                html += '<button class="yg-ritmo-syl" data-syl="' + i + '">' + w.syllables[i] + '</button>';
            }
            html += '</div>';
            if (w.pattern) {
                html += '<div class="yg-ritmo-pattern-label">¿Tipo?</div>';
                html += '<div class="yg-ritmo-patterns">';
                var patterns = ['aguda', 'llana', 'esdrújula'];
                for (var p = 0; p < patterns.length; p++) {
                    html += '<button class="yg-ritmo-pattern-btn" data-pat="' + patterns[p] + '">' + patterns[p] + '</button>';
                }
                html += '</div>';
            }
            container.innerHTML = html;

            document.getElementById('ygRitmoPlay').addEventListener('click', function() {
                Audio.speak(w.word);
            });

            var self = this;
            var answered = false;
            var sylBtns = container.querySelectorAll('.yg-ritmo-syl');
            for (var s = 0; s < sylBtns.length; s++) {
                sylBtns[s].addEventListener('click', function() {
                    if (answered) return;
                    answered = true;
                    var idx = parseInt(this.getAttribute('data-syl'));
                    if (idx === w.stressIndex) {
                        this.classList.add('yg-ritmo-correct');
                        state.correct++;
                        WorldReaction.harmony(container, this, function() {
                            state.idx++;
                            self.render(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, this, function() {
                            var correctSyl = container.querySelector('[data-syl="' + w.stressIndex + '"]');
                            if (correctSyl) correctSyl.classList.add('yg-ritmo-correct');
                            setTimeout(function() {
                                state.idx++;
                                self.render(data, container, state, onComplete);
                            }, 800);
                        });
                    }
                });
            }

            /* Pattern classification buttons (aguda/llana/esdrújula) */
            var patBtns = container.querySelectorAll('.yg-ritmo-pattern-btn');
            for (var p = 0; p < patBtns.length; p++) {
                patBtns[p].addEventListener('click', function() {
                    var pat = this.getAttribute('data-pat');
                    if (pat === w.pattern) {
                        this.classList.add('yg-ritmo-correct');
                    } else {
                        this.style.opacity = '0.3';
                        /* Highlight correct pattern */
                        var correct = container.querySelector('[data-pat="' + w.pattern + '"]');
                        if (correct) correct.classList.add('yg-ritmo-correct');
                    }
                });
            }
        }
    };

    /* ----- CRONÓMETRO (Timed Fluency) ----- */
    Renderers.cronometro = {
        render: function(data, container, state, onComplete) {
            state.qIdx = state.qIdx || 0;
            state.correct = state.correct || 0;
            var q = data.questions[state.qIdx];
            if (!q) { onComplete(state.correct >= Math.ceil(data.questions.length * 0.5)); return; }

            var timeLimit = q.timeLimit || data.defaultTime || 10;
            var html = renderTypeLabel(data.label || 'Cronómetro', 'cronometro') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            if (data.questions.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.qIdx + 1) + ' de ' + data.questions.length + '</div>';
            }

            html += '<div class="yg-crono-bar-wrap"><div class="yg-crono-bar" id="ygCronoBar"></div></div>';
            html += '<div class="yg-crono-prompt">' + q.prompt + '</div>';

            if (q.options && q.options.length > 0) {
                var opts = shuffle(q.options.slice());
                html += '<div class="yg-fib-options" id="ygCronoOpts">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<input type="text" class="yg-dict-input" id="ygCronoInput" placeholder="Responde..." autocomplete="off" autocapitalize="none">';
                html += '<button class="yg-listo-btn" id="ygCronoSubmit">&#10132;</button>';
            }
            container.innerHTML = html;

            /* Timer animation */
            var bar = document.getElementById('ygCronoBar');
            bar.style.transition = 'width ' + timeLimit + 's linear';
            setTimeout(function() { bar.style.width = '0%'; }, 50);

            var self = this;
            var answered = false;
            var timer = setTimeout(function() {
                if (!answered) {
                    answered = true;
                    WorldReaction.desequilibrio(container, bar, function() {
                        state.qIdx++;
                        self.render(data, container, state, onComplete);
                    });
                }
            }, timeLimit * 1000);

            function checkAnswer(val, el) {
                if (answered) return;
                answered = true;
                clearTimeout(timer);
                if (normalize(val) === normalize(q.answer)) {
                    state.correct++;
                    WorldReaction.harmony(container, el, function() {
                        state.qIdx++;
                        self.render(data, container, state, onComplete);
                    });
                } else {
                    WorldReaction.desequilibrio(container, el, function() {
                        state.qIdx++;
                        self.render(data, container, state, onComplete);
                    });
                }
            }

            if (q.options && q.options.length > 0) {
                var btns = container.querySelectorAll('.yg-fib-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        checkAnswer(this.textContent, this);
                    });
                }
            } else {
                var submitBtn = document.getElementById('ygCronoSubmit');
                var input = document.getElementById('ygCronoInput');
                submitBtn.addEventListener('click', function() { checkAnswer(input.value || '', input); });
                input.addEventListener('keydown', function(e) { if (e.key === 'Enter') checkAnswer(input.value || '', input); });
            }
        }
    };

    /* ----- PORTAFOLIO (Reflective Journal) ----- */
    Renderers.portafolio = {
        render: function(data, container, state, onComplete) {
            var storageKey = 'yaguara_portafolio_' + (data.destination || 'unknown');
            var saved = '';
            try { saved = localStorage.getItem(storageKey) || ''; } catch(e) {}

            var html = renderTypeLabel(data.label || 'Diario de viaje', 'portafolio') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            html += '<div class="yg-portafolio-prompt">' + data.prompt + '</div>';

            if (data.guidingQuestions && data.guidingQuestions.length > 0) {
                html += '<div class="yg-portafolio-guiding">';
                for (var i = 0; i < data.guidingQuestions.length; i++) {
                    html += '<div class="yg-portafolio-question">• ' + data.guidingQuestions[i] + '</div>';
                }
                html += '</div>';
            }

            html += '<textarea class="yg-dictogloss-input yg-portafolio-textarea" id="ygPortInput" rows="6" placeholder="Escribe aqu\u00ed...">' + saved + '</textarea>';
            html += '<div class="yg-resumen-wordcount" id="ygPortWC">0 palabras</div>';
            html += '<button class="yg-listo-btn" id="ygPortSubmit">Guardar en el diario</button>';
            container.innerHTML = html;

            var textarea = document.getElementById('ygPortInput');
            var wcEl = document.getElementById('ygPortWC');
            function updateWC() {
                var words = (textarea.value || '').trim().split(/\s+/).filter(function(w) { return w.length > 0; });
                wcEl.textContent = words.length + ' palabras';
            }
            updateWC();
            textarea.addEventListener('input', updateWC);

            document.getElementById('ygPortSubmit').addEventListener('click', function() {
                var val = (textarea.value || '').trim();
                var wordCount = val.split(/\s+/).filter(function(w) { return w.length > 0; }).length;
                var minW = data.minWords || 1;
                if (wordCount >= minW) {
                    try { localStorage.setItem(storageKey, val); } catch(e) {}
                    WorldReaction.harmony(container, textarea, function() { onComplete(true); });
                } else {
                    wcEl.textContent = 'Mínimo ' + minW + ' palabras';
                    WorldReaction.desequilibrio(container, textarea);
                }
            });
        }
    };

    /* ----- AUTOEVALUACIÓN (Self-Assessment) ----- */
    Renderers.autoevaluacion = {
        render: function(data, container, state, onComplete) {
            var statements = data.statements || [];
            if (!statements.length) { onComplete(true); return; }
            state.ratings = state.ratings || {};

            var html = renderTypeLabel(data.label || 'Autoevaluación', 'autoevaluacion') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            if (data.reflection) {
                html += '<div class="yg-autoeval-reflection">' + data.reflection + '</div>';
            }

            html += '<div class="yg-autoeval-statements">';
            for (var i = 0; i < statements.length; i++) {
                var s = data.statements[i];
                var rated = state.ratings[i] !== undefined;
                html += '<div class="yg-autoeval-item' + (rated ? ' yg-autoeval-rated' : '') + '">';
                html += '<div class="yg-autoeval-text">' + s.text + '</div>';
                html += '<div class="yg-autoeval-buttons" data-stmt="' + i + '">';
                var levels = [
                    { val: 'good', label: 'Puedo hacerlo bien' },
                    { val: 'practice', label: 'Necesito práctica' },
                    { val: 'not_yet', label: 'No puedo todavía' }
                ];
                for (var l = 0; l < levels.length; l++) {
                    var sel = state.ratings[i] === levels[l].val ? ' yg-autoeval-selected' : '';
                    html += '<button class="yg-autoeval-btn' + sel + '" data-val="' + levels[l].val + '">' + levels[l].label + '</button>';
                }
                html += '</div></div>';
            }
            html += '</div>';

            var allRated = Object.keys(state.ratings).length >= statements.length;
            html += '<button class="yg-listo-btn' + (allRated ? '' : ' yg-btn-disabled') + '" id="ygAutoEvalSubmit"' + (allRated ? '' : ' disabled') + '>Continuar</button>';
            container.innerHTML = html;

            var self = this;
            var btnGroups = container.querySelectorAll('.yg-autoeval-buttons');
            for (var g = 0; g < btnGroups.length; g++) {
                var btns = btnGroups[g].querySelectorAll('.yg-autoeval-btn');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        var stmtIdx = parseInt(this.parentNode.getAttribute('data-stmt'));
                        state.ratings[stmtIdx] = this.getAttribute('data-val');
                        self.render(data, container, state, onComplete);
                    });
                }
            }

            document.getElementById('ygAutoEvalSubmit').addEventListener('click', function() {
                if (Object.keys(state.ratings).length >= statements.length) {
                    /* Save self-assessment to progress */
                    try {
                        var key = 'yaguara_autoeval_' + (data.destination || 'unknown');
                        localStorage.setItem(key, JSON.stringify(state.ratings));
                    } catch(e) {}
                    WorldReaction.harmony(container, container.querySelector('.yg-autoeval-statements'), function() { onComplete(true); });
                }
            });
        }
    };

    /* ----- NEGOCIACIÓN (Mediation/Negotiation) ----- */
    Renderers.negociacion = {
        render: function(data, container, state, onComplete) {
            var html = renderTypeLabel(data.label || 'Negociación', 'negociacion') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            html += '<div class="yg-negoc-positions">';
            html += '<div class="yg-negoc-position yg-negoc-a">';
            html += '<div class="yg-negoc-speaker">' + data.positionA.speaker + '</div>';
            html += '<div class="yg-negoc-text">' + data.positionA.text + '</div>';
            html += '</div>';
            html += '<div class="yg-negoc-vs">⟷</div>';
            html += '<div class="yg-negoc-position yg-negoc-b">';
            html += '<div class="yg-negoc-speaker">' + data.positionB.speaker + '</div>';
            html += '<div class="yg-negoc-text">' + data.positionB.text + '</div>';
            html += '</div>';
            html += '</div>';

            if (data.mediationOptions && data.mediationOptions.length > 0) {
                html += '<div class="yg-negoc-prompt">¿Cuál es la mejor mediación?</div>';
                html += '<div class="yg-fib-options" id="ygNegocOpts">';
                for (var i = 0; i < data.mediationOptions.length; i++) {
                    html += '<button class="yg-fib-option yg-negoc-option" data-idx="' + i + '">' + data.mediationOptions[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<textarea class="yg-dictogloss-input" id="ygNegocInput" rows="3" placeholder="Escribe una propuesta de mediación..."></textarea>';
                html += '<button class="yg-listo-btn" id="ygNegocSubmit">Proponer</button>';
            }
            container.innerHTML = html;

            if (data.mediationOptions && data.mediationOptions.length > 0) {
                var btns = container.querySelectorAll('.yg-negoc-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        var el = this;
                        var idx = parseInt(el.getAttribute('data-idx'));
                        if (idx === data.correctIndex) {
                            WorldReaction.harmony(container, el, function() { onComplete(true); });
                        } else {
                            WorldReaction.desequilibrio(container, el, function() {
                                el.style.opacity = '0.4';
                                el.style.pointerEvents = 'none';
                            });
                        }
                    });
                }
            } else {
                document.getElementById('ygNegocSubmit').addEventListener('click', function() {
                    var val = (document.getElementById('ygNegocInput').value || '').trim();
                    var modelNorm = normalize(data.modelMediation);
                    var valNorm = normalize(val);
                    /* Soft match — check keyword overlap */
                    var modelWords = modelNorm.split(/\s+/);
                    var matched = 0;
                    for (var i = 0; i < modelWords.length; i++) {
                        if (valNorm.indexOf(modelWords[i]) !== -1) matched++;
                    }
                    var pass = matched >= Math.ceil(modelWords.length * 0.3) || val.split(/\s+/).length >= 5;
                    if (pass) {
                        WorldReaction.harmony(container, document.getElementById('ygNegocInput'), function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, document.getElementById('ygNegocInput'));
                    }
                });
            }
        }
    };


    /* ----- TRANSFORMADOR (Text Transform with Split Display) ----- */
    Renderers.transformador = {
        render: function(data, container, state, onComplete) {
            var html = renderTypeLabel(data.label || 'Transformador', 'transformador') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            /* Split-screen display */
            html += '<div class="yg-transform-split">';
            html += '<div class="yg-transform-panel yg-transform-before">';
            html += '<span class="yg-registro-tag">' + data.sourceGenre + '</span>';
            html += '<div class="yg-transform-text">' + data.sourceText + '</div>';
            html += '</div>';
            html += '<div class="yg-transform-arrow">\u27a1</div>';
            html += '<div class="yg-transform-panel yg-transform-after">';
            html += '<span class="yg-registro-tag yg-registro-tag-target">' + data.targetGenre + '</span>';
            html += '<div class="yg-transform-text yg-transform-placeholder">Tu transformaci\u00f3n...</div>';
            html += '</div>';
            html += '</div>';

            if (data.options && data.options.length > 0) {
                var opts = shuffle(data.options.slice());
                html += '<div class="yg-fib-options" id="ygTransOpts">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<button class="yg-fib-option yg-transform-option">' + opts[i] + '</button>';
                }
                html += '</div>';
            } else {
                html += '<textarea class="yg-dictogloss-input" id="ygTransInput" rows="4" placeholder="Transforma el texto..."></textarea>';
                html += '<button class="yg-listo-btn" id="ygTransSubmit">Listo</button>';
            }
            container.innerHTML = html;

            var afterPanel = container.querySelector('.yg-transform-after .yg-transform-text');

            if (data.options && data.options.length > 0) {
                var btns = container.querySelectorAll('.yg-transform-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        if (normalize(this.textContent) === normalize(data.modelAnswer)) {
                            if (afterPanel) { afterPanel.textContent = this.textContent; afterPanel.classList.remove('yg-transform-placeholder'); }
                            WorldReaction.harmony(container, this, function() { onComplete(true); });
                        } else {
                            WorldReaction.desequilibrio(container, this);
                        }
                    });
                }
            } else {
                var inputEl = document.getElementById('ygTransInput');
                inputEl.addEventListener('input', function() {
                    if (afterPanel) {
                        afterPanel.textContent = inputEl.value || 'Tu transformaci\u00f3n...';
                        afterPanel.classList.toggle('yg-transform-placeholder', !inputEl.value);
                    }
                });
                document.getElementById('ygTransSubmit').addEventListener('click', function() {
                    var val = (inputEl.value || '').trim();
                    if (!val) return;
                    var keywords = data.keywords || [];
                    var matched = 0;
                    for (var i = 0; i < keywords.length; i++) {
                        if (normalize(val).indexOf(normalize(keywords[i])) !== -1) matched++;
                    }
                    var pass = keywords.length === 0 || matched >= Math.ceil(keywords.length * 0.4);
                    if (pass) {
                        WorldReaction.harmony(container, inputEl, function() {
                            container.innerHTML = renderTypeLabel(data.label || 'Transformador', 'transformador') +
                                '<div class="yg-transform-compare">' +
                                '<div class="yg-dictogloss-attempt"><strong>Tu versi\u00f3n:</strong> ' + val + '</div>' +
                                (data.modelAnswer ? '<div class="yg-dictogloss-original"><strong>Versi\u00f3n modelo:</strong> ' + data.modelAnswer + '</div>' : '') +
                                '</div>' +
                                '<button class="yg-listo-btn" id="ygTransContinue">Continuar</button>';
                            document.getElementById('ygTransContinue').addEventListener('click', function() { onComplete(true); });
                        });
                    } else {
                        WorldReaction.desequilibrio(container, inputEl);
                    }
                });
            }
        }
    };


    /* ----- BINGO (Vocabulary Recognition Grid) ----- */
    Renderers.bingo = {
        _checkWin: function(marked, gridSize) {
            /* Check rows, columns, diagonals */
            var i, j, win;
            for (i = 0; i < gridSize; i++) {
                /* Row */
                win = true;
                for (j = 0; j < gridSize; j++) { if (!marked[i * gridSize + j]) { win = false; break; } }
                if (win) return { type: 'row', idx: i };
                /* Col */
                win = true;
                for (j = 0; j < gridSize; j++) { if (!marked[j * gridSize + i]) { win = false; break; } }
                if (win) return { type: 'col', idx: i };
            }
            /* Diagonal TL-BR */
            win = true;
            for (i = 0; i < gridSize; i++) { if (!marked[i * gridSize + i]) { win = false; break; } }
            if (win) return { type: 'diag', idx: 0 };
            /* Diagonal TR-BL */
            win = true;
            for (i = 0; i < gridSize; i++) { if (!marked[i * gridSize + (gridSize - 1 - i)]) { win = false; break; } }
            if (win) return { type: 'diag', idx: 1 };
            return null;
        },
        render: function(data, container, state, onComplete) {
            state.marked = state.marked || {};
            state.callIdx = state.callIdx || 0;
            state.correctCount = state.correctCount || 0;
            state.busy = false;

            var gridSize = data.gridSize || 4;
            var gridTotal = gridSize * gridSize;

            /* Generate grid on first render */
            if (!state.gridWords) {
                state.gridWords = shuffle(data.words.slice()).slice(0, gridTotal);
                state.calls = shuffle(state.gridWords.slice());
            }

            var html = renderTypeLabel(data.label || 'Bingo', 'bingo') +
                       '<div class="yg-instruction">' + (data.instruction || data.prompt || 'Escucha y marca la palabra.') + '</div>';

            /* Current call display — animated */
            var currentCall = state.calls[state.callIdx] || '';
            html += '<div class="yg-bingo-call" id="ygBingoCall">' +
                    '<span class="yg-bingo-call-label">Busca:</span> ' +
                    '<span class="yg-bingo-call-word yg-bingo-call-anim">' + currentCall + '</span>' +
                    '<button class="yg-bingo-listen" id="ygBingoListen" title="Escuchar">&#128266;</button>' +
                    '</div>';

            /* Progress */
            var pct = Math.round((state.correctCount / (data.callCount || gridTotal)) * 100);
            html += '<div class="yg-bingo-progressbar"><div class="yg-bingo-progressfill" style="width:' + pct + '%;"></div></div>';

            /* Grid — proper bingo card */
            html += '<div class="yg-bingo-card">';
            html += '<div class="yg-bingo-grid" id="ygBingoGrid" style="display:grid;grid-template-columns:repeat(' + gridSize + ',1fr);gap:6px;">';
            var winLine = this._checkWin(state.marked, gridSize);
            for (var i = 0; i < state.gridWords.length; i++) {
                var word = state.gridWords[i];
                var markedCls = state.marked[i] ? ' yg-bingo-marked' : '';
                var winCls = '';
                if (winLine) {
                    var row = Math.floor(i / gridSize), col = i % gridSize;
                    if (winLine.type === 'row' && winLine.idx === row) winCls = ' yg-bingo-win';
                    else if (winLine.type === 'col' && winLine.idx === col) winCls = ' yg-bingo-win';
                    else if (winLine.type === 'diag' && winLine.idx === 0 && row === col) winCls = ' yg-bingo-win';
                    else if (winLine.type === 'diag' && winLine.idx === 1 && row === (gridSize - 1 - col)) winCls = ' yg-bingo-win';
                }
                html += '<div class="yg-bingo-cell' + markedCls + winCls + '" data-idx="' + i + '">' + word + '</div>';
            }
            html += '</div></div>';

            html += '<div class="yg-boggle-count">' + state.correctCount + ' de ' + (data.callCount || gridTotal) + '</div>';
            container.innerHTML = html;

            var self = this;
            Audio.speak(currentCall);

            document.getElementById('ygBingoListen').addEventListener('click', function() { Audio.speak(currentCall); });

            document.getElementById('ygBingoGrid').addEventListener('click', function(e) {
                var cell = e.target.closest('.yg-bingo-cell');
                if (!cell || state.busy) return;
                var idx = parseInt(cell.dataset.idx, 10);
                if (state.marked[idx]) return;

                var tappedWord = state.gridWords[idx];
                var isCorrect = normalize(tappedWord) === normalize(currentCall);

                if (isCorrect) {
                    state.busy = true;
                    state.marked[idx] = true;
                    state.correctCount++;
                    cell.classList.add('yg-bingo-marked');

                    /* Check for bingo line */
                    var hasWin = self._checkWin(state.marked, gridSize);

                    WorldReaction.harmony(container, cell, function() {
                        state.busy = false;
                        if (hasWin) {
                            /* Celebration overlay */
                            var overlay = document.createElement('div');
                            overlay.className = 'yg-bingo-celebration';
                            overlay.innerHTML = '<div class="yg-bingo-celebration-text">\u00a1BINGO!</div>';
                            container.appendChild(overlay);
                            setTimeout(function() { onComplete(true); }, 1800);
                        } else if (state.correctCount >= (data.callCount || gridTotal)) {
                            onComplete(true);
                        } else {
                            state.callIdx++;
                            self.render(data, container, state, onComplete);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, cell, function() {});
                }
            });
        }
    };

    /* ----- SCRABBLE (Word Formation from Letter Tiles) ----- */
    Renderers.scrabble = {
        render: function(data, container, state, onComplete) {
            state.found = state.found || {};
            state.currentWord = state.currentWord || '';

            var html = renderTypeLabel(data.label || 'Scrabble', 'scrabble') +
                       '<div class="yg-instruction">' + (data.instruction || data.prompt || 'Forma palabras con las letras.') + '</div>';

            /* Letter tiles */
            html += '<div class="yg-scrabble-tiles" id="ygScrabbleTiles">';
            for (var i = 0; i < data.letters.length; i++) {
                html += '<span class="yg-banana-letter yg-scrabble-tile" data-letter="' + data.letters[i] + '">' + data.letters[i].toUpperCase() + '</span>';
            }
            html += '</div>';

            /* Input area */
            html += '<input type="text" class="yg-dict-input" id="ygScrabbleInput" placeholder="Escribe una palabra..." autocomplete="off" autocapitalize="none" value="' + (state.currentWord || '') + '">';
            html += '<button class="yg-listo-btn" id="ygScrabbleSubmit">Enviar</button>';

            /* Found words */
            html += '<div class="yg-boggle-words">';
            for (var w = 0; w < data.validWords.length; w++) {
                var vw = data.validWords[w];
                var cls = state.found[normalize(vw)] ? ' yg-boggle-found' : '';
                html += '<span class="yg-boggle-word' + cls + '">' + (state.found[normalize(vw)] ? vw : vw.replace(/./g, '\u2022')) + '</span>';
            }
            html += '</div>';

            /* Progress */
            var foundCount = Object.keys(state.found).length;
            html += '<div class="yg-boggle-count">' + foundCount + ' de ' + data.targetCount + '</div>';

            container.innerHTML = html;

            var self = this;
            var input = document.getElementById('ygScrabbleInput');

            /* Tile click → append letter to input */
            document.getElementById('ygScrabbleTiles').addEventListener('click', function(e) {
                var tile = e.target.closest('.yg-scrabble-tile');
                if (!tile) return;
                input.value += tile.dataset.letter;
                input.focus();
            });

            function tryWord() {
                var val = (input.value || '').trim();
                if (!val) return;
                var norm = normalize(val);

                /* Check against valid words list */
                var isValid = false;
                for (var i = 0; i < data.validWords.length; i++) {
                    if (normalize(data.validWords[i]) === norm) { isValid = true; break; }
                }

                if (isValid && !state.found[norm]) {
                    state.found[norm] = true;
                    state.currentWord = '';
                    WorldReaction.harmony(container, input, function() {
                        if (Object.keys(state.found).length >= data.targetCount) {
                            onComplete(true);
                        } else {
                            self.render(data, container, state, onComplete);
                        }
                    });
                } else {
                    WorldReaction.desequilibrio(container, input, function() {
                        input.value = '';
                        state.currentWord = '';
                    });
                }
            }

            document.getElementById('ygScrabbleSubmit').addEventListener('click', tryWord);
            input.addEventListener('keydown', function(e) { if (e.key === 'Enter') tryWord(); });
            input.addEventListener('input', function() { state.currentWord = input.value; });
        }
    };

    /* ==========================================================
       8b. NEW GAME TYPE RENDERERS (Feb 2026)
       10 types: susurro, eco_lejano, tertulia, pregonero,
       raiz, codice, sombra, oraculo, tejido, cartografo
    ========================================================== */

    /* ----- SUSURRO (The Jungle Whisper — Listening→Writing) ----- */
    Renderers.susurro = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'listen';
            var self = this;

            if (state.phase === 'listen') {
                var html = renderTypeLabel(data.label || 'Susurro', 'susurro') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-susurro-visual" id="ygSusurroVisual" aria-hidden="true">';
                html += '<div class="yg-susurro-ripple"></div>';
                html += '<div class="yg-susurro-ripple yg-susurro-ripple-2"></div>';
                html += '<div class="yg-susurro-ripple yg-susurro-ripple-3"></div>';
                html += '</div>';
                html += '<div class="yg-susurro-status" id="ygSusurroStatus" aria-live="polite">Escuchando...</div>';
                html += A11y.buildCaptionToggle(data.audio);
                container.innerHTML = html;

                A11y.announce('Susurro: ' + (data.instruction || 'Escucha el susurro de la selva'));
                A11y.bindCaptionToggle();

                Audio.speak(data.audio, { rate: 0.85 });

                /* Estimate TTS duration (rough: 80ms per char + 500ms buffer) */
                var ttsDuration = Math.max(2000, data.audio.length * 80 + 500);
                setTimeout(function() {
                    state.phase = 'silence';
                    self.render(data, container, state, onComplete);
                }, ttsDuration);

            } else if (state.phase === 'silence') {
                var html = renderTypeLabel(data.label || 'Susurro', 'susurro') +
                           '<div class="yg-susurro-visual yg-susurro-fading">';
                html += '<div class="yg-susurro-ripple"></div>';
                html += '</div>';
                html += '<div class="yg-susurro-status">El eco se desvanece...</div>';
                container.innerHTML = html;

                setTimeout(function() {
                    state.phase = 'write';
                    self.render(data, container, state, onComplete);
                }, data.silenceDuration);

            } else if (state.phase === 'write') {
                var html = renderTypeLabel(data.label || 'Susurro', 'susurro') +
                           '<div class="yg-instruction">Escribe lo que susurr\u00f3 la selva.</div>';
                html += '<textarea class="yg-dictogloss-input yg-susurro-input" id="ygSusurroInput" rows="3" placeholder="Escribe lo que recuerdas..." aria-label="Escribe lo que recuerdas del susurro"></textarea>';
                html += '<button class="yg-listo-btn" id="ygSusurroSubmit" aria-label="Enviar respuesta">Listo</button>';
                container.innerHTML = html;
                document.getElementById('ygSusurroInput').focus();

                document.getElementById('ygSusurroSubmit').addEventListener('click', function() {
                    state.attempt = (document.getElementById('ygSusurroInput').value || '').trim();
                    state.phase = 'compare';
                    self.render(data, container, state, onComplete);
                });

            } else if (state.phase === 'compare') {
                var html = renderTypeLabel(data.label || 'Susurro', 'susurro') +
                           '<div class="yg-instruction">Compara con el susurro original.</div>';

                /* Word-by-word comparison */
                var origWords = data.audio.split(/\s+/);
                var attemptNorm = normalize(state.attempt || '');
                html += '<div class="yg-susurro-compare">';
                for (var i = 0; i < origWords.length; i++) {
                    var w = origWords[i];
                    var cleanW = normalize(w);
                    var cls = 'yg-susurro-word-missed';
                    if (attemptNorm.indexOf(cleanW) !== -1) {
                        cls = 'yg-susurro-word-match';
                    }
                    html += '<span class="yg-susurro-word ' + cls + '">' + w + '</span> ';
                }
                html += '</div>';

                /* Keyword scoring */
                var matched = 0;
                for (var i = 0; i < data.keywords.length; i++) {
                    if (attemptNorm.indexOf(normalize(data.keywords[i])) !== -1) matched++;
                }
                var pct = data.keywords.length > 0 ? Math.round(matched / data.keywords.length * 100) : 0;
                html += '<div class="yg-dictogloss-score">Palabras clave: ' + matched + '/' + data.keywords.length + ' (' + pct + '%)</div>';
                html += '<div class="yg-susurro-attempt"><strong>Tu texto:</strong> ' + (state.attempt || '') + '</div>';
                html += '<button class="yg-listo-btn" id="ygSusurroContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygSusurroContinue').addEventListener('click', function() {
                    onComplete(matched >= data.minMatch);
                });
            }
        }
    };

    /* ----- ECO LEJANO (Fading Text — Deep Reading) ----- */
    Renderers.eco_lejano = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'read';
            var self = this;

            if (state.phase === 'read') {
                var html = renderTypeLabel(data.label || 'Eco lejano', 'eco_lejano') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-eco-timer-wrap" role="timer" aria-label="Tiempo restante"><div class="yg-eco-timer-bar" id="ygEcoTimer"></div></div>';

                /* Split passage into lines for fading */
                var lines = data.passage.split(/\.\s+/).filter(function(l) { return l.trim(); });
                html += '<div class="yg-eco-passage" id="ygEcoPassage">';
                for (var i = 0; i < lines.length; i++) {
                    html += '<div class="yg-eco-line" data-line="' + i + '">' + lines[i] + (lines[i].match(/[.!?]$/) ? '' : '.') + '</div>';
                }
                html += '</div>';
                html += A11y.buildCaptionToggle(data.passage);
                container.innerHTML = html;

                A11y.announce('Eco lejano: ' + (data.instruction || 'Lee antes de que el texto se desvanezca'));
                A11y.bindCaptionToggle();

                /* Timer bar animation */
                var timerBar = document.getElementById('ygEcoTimer');
                var totalTime = data.readTime / 1000;
                timerBar.style.transition = 'width ' + totalTime + 's linear';
                setTimeout(function() { timerBar.style.width = '0%'; }, 50);

                /* Fade lines progressively */
                var lineEls = container.querySelectorAll('.yg-eco-line');
                var fadeInterval = data.readTime / (lines.length + 1);
                for (var li = 0; li < lineEls.length; li++) {
                    (function(el, delay) {
                        setTimeout(function() {
                            el.style.transition = 'opacity ' + (data.fadeSpeed / 1000) + 's ease';
                            el.style.opacity = '0';
                        }, delay);
                    })(lineEls[li], fadeInterval * (li + 1));
                }

                /* Transition to questions after readTime */
                setTimeout(function() {
                    state.phase = 'questions';
                    state.qIdx = 0;
                    state.correct = 0;
                    self.render(data, container, state, onComplete);
                }, data.readTime + 500);

            } else if (state.phase === 'questions') {
                var q = data.questions[state.qIdx];
                if (!q) {
                    onComplete(state.correct >= Math.ceil(data.questions.length * 0.5));
                    return;
                }

                var html = renderTypeLabel(data.label || 'Eco lejano', 'eco_lejano') +
                           '<div class="yg-instruction">Responde de memoria.</div>';
                if (data.questions.length > 1) {
                    html += '<div class="yg-sub-counter">' + (state.qIdx + 1) + ' de ' + data.questions.length + '</div>';
                }
                html += '<div class="yg-eco-question">' + q.q + '</div>';

                if (q.options && q.options.length > 0) {
                    var opts = shuffle(q.options.slice());
                    html += '<div class="yg-fib-options" id="ygEcoOpts">';
                    for (var i = 0; i < opts.length; i++) {
                        html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                    }
                    html += '</div>';
                } else {
                    html += '<input type="text" class="yg-dict-input" id="ygEcoInput" placeholder="Tu respuesta..." autocomplete="off" autocapitalize="none">';
                    html += '<button class="yg-listo-btn" id="ygEcoSubmit">Responder</button>';
                }
                container.innerHTML = html;

                function advanceEco(success) {
                    if (success) state.correct++;
                    state.qIdx++;
                    self.render(data, container, state, onComplete);
                }

                if (q.options && q.options.length > 0) {
                    var btns = container.querySelectorAll('.yg-fib-option');
                    for (var b = 0; b < btns.length; b++) {
                        btns[b].addEventListener('click', function() {
                            if (normalize(this.textContent) === normalize(q.answer)) {
                                WorldReaction.harmony(container, this, function() { advanceEco(true); });
                            } else {
                                WorldReaction.desequilibrio(container, this, function() { advanceEco(false); });
                            }
                        });
                    }
                } else {
                    document.getElementById('ygEcoSubmit').addEventListener('click', function() {
                        var val = (document.getElementById('ygEcoInput').value || '').trim();
                        if (normalize(val) === normalize(q.answer)) {
                            WorldReaction.harmony(container, document.getElementById('ygEcoInput'), function() { advanceEco(true); });
                        } else {
                            WorldReaction.desequilibrio(container, document.getElementById('ygEcoInput'), function() { advanceEco(false); });
                        }
                    });
                }
            }
        }
    };


    /* ----- TERTULIA (Circular Conversation Gathering) ----- */
    Renderers.tertulia = {
        render: function(data, container, state, onComplete) {
            state.turnIdx = state.turnIdx || 0;
            state.correct = state.correct || 0;
            var turn = data.turns[state.turnIdx];
            if (!turn) { onComplete(state.correct >= Math.ceil(data.turns.length * 0.6)); return; }

            var html = renderTypeLabel(data.label || 'Tertulia', 'tertulia') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

            if (data.turns.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.turnIdx + 1) + ' de ' + data.turns.length + '</div>';
            }

            /* Circular seating arrangement */
            var speakers = [];
            var seenNames = {};
            for (var t = 0; t < data.turns.length; t++) {
                if (!seenNames[data.turns[t].name]) {
                    speakers.push(data.turns[t].name);
                    seenNames[data.turns[t].name] = true;
                }
            }
            if (speakers.length > 1) {
                html += '<div class="yg-tertulia-circle">';
                for (var s = 0; s < speakers.length; s++) {
                    var active = speakers[s] === turn.name ? ' yg-tertulia-speaking' : '';
                    var angle = (s / speakers.length) * 360 - 90;
                    html += '<div class="yg-tertulia-seat' + active + '" style="--seat-angle:' + angle + 'deg;">';
                    html += '<div class="yg-tertulia-avatar">' + speakers[s].charAt(0).toUpperCase() + '</div>';
                    html += '<div class="yg-tertulia-seat-name">' + speakers[s] + '</div>';
                    html += '</div>';
                }
                html += '</div>';
            }

            html += '<div class="yg-tertulia-bubble">';
            html += '<div class="yg-tertulia-speaker">' + turn.name + '</div>';
            html += '<div class="yg-tertulia-text">' + turn.text + '</div>';
            html += '<button class="yg-parmin-play yg-tertulia-listen" id="ygTertuliaListen">&#9654; Escuchar</button>';
            html += '</div>';

            if (turn.options && turn.options.length > 0) {
                html += '<div class="yg-tertulia-prompt">\u00bfC\u00f3mo respondes?</div>';
                var tertOpts = shuffle(turn.options.slice());
                html += '<div class="yg-fib-options" id="ygTertuliaOpts">';
                for (var i = 0; i < tertOpts.length; i++) {
                    html += '<button class="yg-fib-option yg-tertulia-option">' + tertOpts[i] + '</button>';
                }
                html += '</div>';
            }
            container.innerHTML = html;

            document.getElementById('ygTertuliaListen').addEventListener('click', function() { Audio.speak(turn.text); });
            Audio.speak(turn.text);

            var self = this;
            var btns = container.querySelectorAll('.yg-tertulia-option');
            for (var b = 0; b < btns.length; b++) {
                btns[b].addEventListener('click', function() {
                    if (normalize(this.textContent) === normalize(turn.answer)) {
                        state.correct++;
                        WorldReaction.harmony(container, this, function() {
                            state.turnIdx++;
                            self.render(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, this, function() {
                            state.turnIdx++;
                            self.render(data, container, state, onComplete);
                        });
                    }
                });
            }
        }
    };

    /* ----- PREGONERO (The Town Crier — Register Writing) ----- */
    Renderers.pregonero = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'write';
            var self = this;

            if (state.phase === 'write') {
                var html = renderTypeLabel(data.label || 'Pregonero', 'pregonero') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-pregonero-situation">' + data.situation + '</div>';
                html += '<div class="yg-pregonero-register-tag">' + data.register + '</div>';
                html += '<textarea class="yg-dictogloss-input yg-pregonero-input" id="ygPregoneroInput" rows="4" placeholder="' + data.placeholder + '"></textarea>';
                html += '<div class="yg-resumen-wordcount" id="ygPregoneroWC">0 palabras</div>';
                html += '<button class="yg-listo-btn" id="ygPregoneroSubmit">Anunciar</button>';
                container.innerHTML = html;

                var textarea = document.getElementById('ygPregoneroInput');
                var wcEl = document.getElementById('ygPregoneroWC');
                textarea.addEventListener('input', function() {
                    var words = (textarea.value || '').trim().split(/\s+/).filter(function(w) { return w.length > 0; });
                    wcEl.textContent = words.length + ' palabras';
                });

                document.getElementById('ygPregoneroSubmit').addEventListener('click', function() {
                    var val = (textarea.value || '').trim();
                    var wordCount = val.split(/\s+/).filter(function(w) { return w.length > 0; }).length;
                    if (wordCount < data.minWords) {
                        wcEl.textContent = 'Mínimo ' + data.minWords + ' palabras';
                        WorldReaction.desequilibrio(container, textarea);
                        return;
                    }
                    state.attempt = val;
                    state.phase = 'review';
                    self.render(data, container, state, onComplete);
                });

            } else if (state.phase === 'review') {
                var html = renderTypeLabel(data.label || 'Pregonero', 'pregonero') +
                           '<div class="yg-instruction">Tu pregón:</div>';
                html += '<div class="yg-pregonero-result">' + state.attempt + '</div>';

                /* Keyword scoring */
                var matched = 0;
                var valNorm = normalize(state.attempt || '');
                for (var i = 0; i < data.keywords.length; i++) {
                    if (valNorm.indexOf(normalize(data.keywords[i])) !== -1) matched++;
                }

                if (data.modelAnswer) {
                    html += '<div class="yg-dictogloss-original"><strong>Modelo:</strong> ' + data.modelAnswer + '</div>';
                }

                var pass = data.keywords.length > 0
                    ? matched >= Math.ceil(data.keywords.length * 0.4)
                    : (state.attempt || '').split(/\s+/).length >= data.minWords;

                html += '<div class="yg-dictogloss-score">Vocabulario del registro: ' + matched + '/' + data.keywords.length + '</div>';
                html += '<button class="yg-listo-btn" id="ygPregoneroContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygPregoneroContinue').addEventListener('click', function() {
                    onComplete(pass);
                });
            }
        }
    };

    /* ----- RAÍZ (The Root — Babel Tree Etymology) ----- */
    Renderers.raiz = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'related';
            var self = this;

            if (state.phase === 'related') {
                var html = renderTypeLabel(data.label || 'Raíz', 'raiz') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

                html += '<div class="yg-raiz-word">' + data.word + '</div>';
                if (data.etymology) {
                    html += '<div class="yg-raiz-etymology">' + data.etymology + '</div>';
                }

                /* Phase 1: Select related Spanish words */
                html += '<div class="yg-raiz-prompt">Selecciona las palabras que comparten esta raíz:</div>';
                var pool = shuffle(data.relatedWords.concat(data.distractors));
                state.selectedRelated = state.selectedRelated || {};
                html += '<div class="yg-raiz-pool" id="ygRaizPool">';
                for (var i = 0; i < pool.length; i++) {
                    var sel = state.selectedRelated[pool[i]] ? ' yg-raiz-selected' : '';
                    html += '<button class="yg-raiz-chip' + sel + '" data-word="' + pool[i] + '">' + pool[i] + '</button>';
                }
                html += '</div>';
                html += '<button class="yg-listo-btn" id="ygRaizSubmit">Confirmar</button>';
                container.innerHTML = html;

                /* Toggle selection */
                var chips = container.querySelectorAll('.yg-raiz-chip');
                for (var c = 0; c < chips.length; c++) {
                    chips[c].addEventListener('click', function() {
                        var w = this.getAttribute('data-word');
                        if (state.selectedRelated[w]) {
                            delete state.selectedRelated[w];
                            this.classList.remove('yg-raiz-selected');
                        } else {
                            state.selectedRelated[w] = true;
                            this.classList.add('yg-raiz-selected');
                        }
                    });
                }

                document.getElementById('ygRaizSubmit').addEventListener('click', function() {
                    /* Score phase 1 */
                    var correct = 0;
                    var wrong = 0;
                    var selected = Object.keys(state.selectedRelated);
                    for (var i = 0; i < selected.length; i++) {
                        var isRelated = false;
                        for (var j = 0; j < data.relatedWords.length; j++) {
                            if (normalize(selected[i]) === normalize(data.relatedWords[j])) { isRelated = true; break; }
                        }
                        if (isRelated) correct++;
                        else wrong++;
                    }
                    state.relatedScore = correct;
                    state.relatedTotal = data.relatedWords.length;

                    if (data.cognates && data.cognates.length > 0) {
                        state.phase = 'cognates';
                        self.render(data, container, state, onComplete);
                    } else {
                        /* No cognate phase — finish */
                        var pass = correct >= Math.ceil(data.relatedWords.length * 0.5);
                        if (pass) {
                            WorldReaction.harmony(container, container, function() { onComplete(true); });
                        } else {
                            WorldReaction.desequilibrio(container, container, function() { onComplete(false); });
                        }
                    }
                });

            } else if (state.phase === 'cognates') {
                var html = renderTypeLabel(data.label || 'Raíz', 'raiz') +
                           '<div class="yg-instruction">Ahora busca los cognados en otros idiomas.</div>';

                html += '<div class="yg-raiz-word">' + data.word + '</div>';
                html += '<div class="yg-raiz-tree-visual">';
                html += '<div class="yg-raiz-trunk"></div>';
                html += '</div>';

                state.cogIdx = state.cogIdx || 0;
                state.cogCorrect = state.cogCorrect || 0;
                var cog = data.cognates[state.cogIdx];
                if (!cog) {
                    /* All cognates done */
                    var totalScore = state.relatedScore + state.cogCorrect;
                    var totalPossible = state.relatedTotal + data.cognates.length;
                    onComplete(totalScore >= Math.ceil(totalPossible * 0.5));
                    return;
                }

                html += '<div class="yg-raiz-cog-prompt">¿Cuál es el cognado en <strong>' + cog.lang + '</strong>?</div>';
                if (cog.options && cog.options.length > 0) {
                    var opts = shuffle(cog.options.slice());
                    html += '<div class="yg-fib-options" id="ygRaizCogOpts">';
                    for (var i = 0; i < opts.length; i++) {
                        html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                    }
                    html += '</div>';
                } else {
                    html += '<input type="text" class="yg-dict-input" id="ygRaizCogInput" placeholder="Cognado..." autocomplete="off">';
                    html += '<button class="yg-listo-btn" id="ygRaizCogSubmit">Confirmar</button>';
                }
                container.innerHTML = html;

                function advanceCog(success) {
                    if (success) state.cogCorrect++;
                    state.cogIdx++;
                    self.render(data, container, state, onComplete);
                }

                if (cog.options && cog.options.length > 0) {
                    var btns = container.querySelectorAll('.yg-fib-option');
                    for (var b = 0; b < btns.length; b++) {
                        btns[b].addEventListener('click', function() {
                            var answer = cog.word || cog.answer || '';
                            if (normalize(this.textContent) === normalize(answer)) {
                                WorldReaction.harmony(container, this, function() { advanceCog(true); });
                            } else {
                                WorldReaction.desequilibrio(container, this, function() { advanceCog(false); });
                            }
                        });
                    }
                } else {
                    document.getElementById('ygRaizCogSubmit').addEventListener('click', function() {
                        var val = (document.getElementById('ygRaizCogInput').value || '').trim();
                        var answer = cog.word || cog.answer || '';
                        if (normalize(val) === normalize(answer)) {
                            WorldReaction.harmony(container, document.getElementById('ygRaizCogInput'), function() { advanceCog(true); });
                        } else {
                            WorldReaction.desequilibrio(container, document.getElementById('ygRaizCogInput'), function() { advanceCog(false); });
                        }
                    });
                }
            }
        }
    };

    /* ----- CÓDICE (The Ancient Codex — Contextual Inferencing) ----- */
    Renderers.codice = {
        render: function(data, container, state, onComplete) {
            state.hlIdx = state.hlIdx || 0;
            state.correct = state.correct || 0;
            state.answered = state.answered || {};

            var hl = data.highlights[state.hlIdx];
            if (!hl) {
                /* All done — show full glossary */
                var html = renderTypeLabel(data.label || 'Códice', 'codice') +
                           '<div class="yg-instruction">El códice se revela.</div>';
                html += '<div class="yg-codice-passage yg-codice-unlocked">' + data.passage + '</div>';
                html += '<div class="yg-codice-glossary">';
                for (var i = 0; i < data.highlights.length; i++) {
                    var h = data.highlights[i];
                    var cls = state.answered[i] ? 'yg-codice-correct' : 'yg-codice-missed';
                    html += '<div class="yg-codice-gloss-item ' + cls + '"><strong>' + h.word + '</strong>: ' + h.answer + '</div>';
                }
                html += '</div>';
                html += '<div class="yg-dictogloss-score">' + state.correct + ' de ' + data.highlights.length + ' correctas</div>';
                html += '<button class="yg-listo-btn" id="ygCodiceContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygCodiceContinue').addEventListener('click', function() {
                    onComplete(state.correct >= Math.ceil(data.highlights.length * 0.5));
                });
                return;
            }

            var html = renderTypeLabel(data.label || 'Códice', 'codice') +
                       '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
            if (data.highlights.length > 1) {
                html += '<div class="yg-sub-counter">' + (state.hlIdx + 1) + ' de ' + data.highlights.length + '</div>';
            }

            /* Passage with highlighted word */
            var passageHtml = data.passage.replace(
                new RegExp('(' + hl.word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'),
                '<span class="yg-codice-highlight">$1</span>'
            );
            html += '<div class="yg-codice-passage">' + passageHtml + '</div>';

            html += '<div class="yg-codice-question">¿Qué significa <strong>«' + hl.word + '»</strong>?</div>';
            if (hl.options && hl.options.length > 0) {
                var opts = shuffle(hl.options.slice());
                html += '<div class="yg-fib-options" id="ygCodiceOpts">';
                for (var i = 0; i < opts.length; i++) {
                    html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                }
                html += '</div>';
            }
            container.innerHTML = html;

            var self = this;
            var btns = container.querySelectorAll('.yg-fib-option');
            for (var b = 0; b < btns.length; b++) {
                btns[b].addEventListener('click', function() {
                    if (normalize(this.textContent) === normalize(hl.answer)) {
                        state.correct++;
                        state.answered[state.hlIdx] = true;
                        WorldReaction.harmony(container, this, function() {
                            state.hlIdx++;
                            self.render(data, container, state, onComplete);
                        });
                    } else {
                        WorldReaction.desequilibrio(container, this, function() {
                            state.hlIdx++;
                            self.render(data, container, state, onComplete);
                        });
                    }
                });
            }
        }
    };

    /* ----- SOMBRA (The Shadow — Speed Dictation) ----- */
    Renderers.sombra = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'listen';
            var self = this;

            if (state.phase === 'listen') {
                var html = renderTypeLabel(data.label || 'Sombra', 'sombra') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-sombra-visual" id="ygSombraVisual" aria-hidden="true">';
                html += '<div class="yg-sombra-pulse"></div>';
                html += '</div>';
                html += '<div class="yg-sombra-status" aria-live="polite">Escuchando...</div>';
                html += A11y.buildCaptionToggle(data.audio);
                container.innerHTML = html;

                A11y.announce('Sombra: ' + (data.instruction || 'Escucha y repite r\u00e1pido'));
                A11y.bindCaptionToggle();

                Audio.speak(data.audio);
                state.replaysUsed = 0;

                /* Estimate TTS duration and transition to typing */
                var ttsDuration = Math.max(1500, data.audio.length * 70 + 400);
                setTimeout(function() {
                    state.phase = 'type';
                    state.startTime = Date.now();
                    self.render(data, container, state, onComplete);
                }, ttsDuration);

            } else if (state.phase === 'type') {
                /* Calculate time limit */
                var ttsDuration = Math.max(1500, data.audio.length * 70 + 400);
                var timeLimit = Math.ceil(ttsDuration * data.timeMultiplier / 1000);

                var html = renderTypeLabel(data.label || 'Sombra', 'sombra') +
                           '<div class="yg-instruction">Escribe lo que oíste — rápido.</div>';
                html += '<div class="yg-crono-bar-wrap" role="timer" aria-label="Tiempo restante"><div class="yg-crono-bar" id="ygSombraBar"></div></div>';
                html += '<input type="text" class="yg-dict-input yg-sombra-input" id="ygSombraInput" placeholder="Escribe..." autocomplete="off" autocapitalize="none" aria-label="Escribe lo que o\u00edste">';
                html += '<div class="yg-sombra-controls">';
                if (state.replaysUsed < data.replayLimit) {
                    html += '<button class="yg-parmin-play" id="ygSombraReplay" aria-label="Repetir audio">&#128260; Repetir (' + (data.replayLimit - state.replaysUsed) + ')</button>';
                }
                html += '<button class="yg-listo-btn" id="ygSombraSubmit" aria-label="Enviar respuesta">Enviar</button>';
                html += '</div>';
                container.innerHTML = html;

                /* Timer */
                var bar = document.getElementById('ygSombraBar');
                bar.style.transition = 'width ' + timeLimit + 's linear';
                setTimeout(function() { bar.style.width = '0%'; }, 50);
                document.getElementById('ygSombraInput').focus();

                var answered = false;
                var timer = setTimeout(function() {
                    if (!answered) {
                        answered = true;
                        state.attempt = (document.getElementById('ygSombraInput').value || '').trim();
                        state.phase = 'result';
                        self.render(data, container, state, onComplete);
                    }
                }, timeLimit * 1000);

                /* Replay */
                var replayBtn = document.getElementById('ygSombraReplay');
                if (replayBtn) {
                    replayBtn.addEventListener('click', function() {
                        state.replaysUsed++;
                        Audio.speak(data.audio);
                        if (state.replaysUsed >= data.replayLimit) {
                            replayBtn.disabled = true;
                            replayBtn.style.opacity = '0.3';
                        } else {
                            replayBtn.textContent = '\uD83D\uDD01 Repetir (' + (data.replayLimit - state.replaysUsed) + ')';
                        }
                    });
                }

                /* Submit */
                document.getElementById('ygSombraSubmit').addEventListener('click', function() {
                    if (answered) return;
                    answered = true;
                    clearTimeout(timer);
                    state.attempt = (document.getElementById('ygSombraInput').value || '').trim();
                    state.elapsedMs = Date.now() - state.startTime;
                    state.phase = 'result';
                    self.render(data, container, state, onComplete);
                });
                document.getElementById('ygSombraInput').addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') document.getElementById('ygSombraSubmit').click();
                });

            } else if (state.phase === 'result') {
                var html = renderTypeLabel(data.label || 'Sombra', 'sombra') +
                           '<div class="yg-instruction">Resultado</div>';

                /* Word-by-word comparison */
                var origWords = data.answer.split(/\s+/);
                var attemptWords = (state.attempt || '').split(/\s+/);
                var matchCount = 0;
                html += '<div class="yg-sombra-compare">';
                for (var i = 0; i < origWords.length; i++) {
                    var matchFound = false;
                    for (var j = 0; j < attemptWords.length; j++) {
                        if (normalize(origWords[i]) === normalize(attemptWords[j])) { matchFound = true; break; }
                    }
                    if (matchFound) matchCount++;
                    var cls = matchFound ? 'yg-susurro-word-match' : 'yg-susurro-word-missed';
                    html += '<span class="yg-susurro-word ' + cls + '">' + origWords[i] + '</span> ';
                }
                html += '</div>';

                var pct = origWords.length > 0 ? Math.round(matchCount / origWords.length * 100) : 0;
                html += '<div class="yg-dictogloss-score">Precisión: ' + pct + '%</div>';
                if (state.elapsedMs) {
                    html += '<div class="yg-sombra-speed">Tiempo: ' + (state.elapsedMs / 1000).toFixed(1) + 's</div>';
                }
                html += '<button class="yg-listo-btn" id="ygSombraContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygSombraContinue').addEventListener('click', function() {
                    onComplete(pct >= 60);
                });
            }
        }
    };

    /* ----- ORÁCULO (The Oracle — Pragmatic Prediction) ----- */
    Renderers.oraculo = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'predict';

            if (state.phase === 'predict') {
                var html = renderTypeLabel(data.label || 'Oráculo', 'oraculo') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

                /* Partial scene */
                html += '<div class="yg-oraculo-scene">';
                for (var i = 0; i < data.scene.length; i++) {
                    var s = data.scene[i];
                    html += '<div class="yg-oraculo-line">';
                    html += '<span class="yg-oraculo-speaker">' + s.speaker + ':</span> ';
                    html += '<span class="yg-oraculo-text">' + s.text + '</span>';
                    html += '</div>';
                }
                html += '<div class="yg-oraculo-ellipsis">...</div>';
                html += '</div>';

                /* Prediction options */
                html += '<div class="yg-oraculo-prompt">¿Qué viene después?</div>';
                if (data.options && data.options.length > 0) {
                    var oracOpts = shuffle(data.options.slice());
                    html += '<div class="yg-fib-options" id="ygOraculoOpts">';
                    for (var i = 0; i < oracOpts.length; i++) {
                        html += '<button class="yg-fib-option yg-oraculo-option">' + oracOpts[i] + '</button>';
                    }
                    html += '</div>';
                }
                container.innerHTML = html;

                var self = this;
                var btns = container.querySelectorAll('.yg-oraculo-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        state.chosen = this.textContent;
                        state.wasCorrect = normalize(this.textContent) === normalize(data.answer);
                        state.phase = 'reveal';
                        if (state.wasCorrect) {
                            WorldReaction.harmony(container, this, function() {
                                self.render(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, this, function() {
                                self.render(data, container, state, onComplete);
                            });
                        }
                    });
                }

            } else if (state.phase === 'reveal') {
                var html = renderTypeLabel(data.label || 'Oráculo', 'oraculo') +
                           '<div class="yg-instruction">El oráculo revela la escena completa.</div>';

                /* Full scene */
                html += '<div class="yg-oraculo-scene">';
                for (var i = 0; i < data.scene.length; i++) {
                    var s = data.scene[i];
                    html += '<div class="yg-oraculo-line">';
                    html += '<span class="yg-oraculo-speaker">' + s.speaker + ':</span> ';
                    html += '<span class="yg-oraculo-text">' + s.text + '</span>';
                    html += '</div>';
                }
                /* Reveal the answer */
                html += '<div class="yg-oraculo-line yg-oraculo-reveal">';
                if (data.character) {
                    html += '<span class="yg-oraculo-speaker">' + data.character + ':</span> ';
                }
                html += '<span class="yg-oraculo-text">' + data.answer + '</span>';
                html += '</div>';
                html += '</div>';

                if (data.explanation) {
                    html += '<div class="yg-oraculo-explanation">' + data.explanation + '</div>';
                }

                html += '<button class="yg-listo-btn" id="ygOraculoContinue">Continuar</button>';
                container.innerHTML = html;

                document.getElementById('ygOraculoContinue').addEventListener('click', function() {
                    onComplete(state.wasCorrect);
                });
            }
        }
    };

    /* ----- TEJIDO (The Weaving — Discourse Cohesion) ----- */
    Renderers.tejido = {
        render: function(data, container, state, onComplete) {
            var self = this;

            if (data.mode === 'order') {
                /* Drag-to-order sentences */
                if (!state.order) {
                    state.order = shuffle(data.items.map(function(item, idx) { return idx; }));
                }

                var html = renderTypeLabel(data.label || 'Tejido', 'tejido') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-tejido-fragments">';
                html += '<div class="yg-tejido-fragment yg-tejido-a">' + data.fragmentA + '</div>';
                html += '<div class="yg-tejido-fragment yg-tejido-b">' + data.fragmentB + '</div>';
                html += '</div>';

                html += '<div class="yg-tejido-prompt">Ordena las oraciones para crear un texto coherente:</div>';
                html += '<div class="yg-tejido-items" id="ygTejidoItems">';
                for (var i = 0; i < state.order.length; i++) {
                    var itemIdx = state.order[i];
                    var text = typeof data.items[itemIdx] === 'string' ? data.items[itemIdx] : (data.items[itemIdx].text || '');
                    html += '<div class="yg-tejido-item" data-idx="' + itemIdx + '" draggable="true">';
                    html += '<span class="yg-tejido-handle">&#8942;</span> ' + text;
                    html += '</div>';
                }
                html += '</div>';
                html += '<button class="yg-listo-btn" id="ygTejidoSubmit">Tejer</button>';
                container.innerHTML = html;

                /* Simple click-to-swap reordering */
                var items = container.querySelectorAll('.yg-tejido-item');
                var selected = null;
                for (var it = 0; it < items.length; it++) {
                    items[it].addEventListener('click', function() {
                        if (selected === null) {
                            selected = this;
                            this.classList.add('yg-tejido-selected');
                        } else {
                            /* Swap positions in state.order */
                            var aIdx = Array.prototype.indexOf.call(items, selected);
                            var bIdx = Array.prototype.indexOf.call(items, this);
                            var tmp = state.order[aIdx];
                            state.order[aIdx] = state.order[bIdx];
                            state.order[bIdx] = tmp;
                            selected = null;
                            self.render(data, container, state, onComplete);
                        }
                    });
                }

                document.getElementById('ygTejidoSubmit').addEventListener('click', function() {
                    /* Check if order matches expected (0,1,2,...) */
                    var correct = true;
                    for (var i = 0; i < state.order.length; i++) {
                        if (state.order[i] !== i) { correct = false; break; }
                    }
                    if (correct) {
                        WorldReaction.harmony(container, container, function() { onComplete(true); });
                    } else {
                        WorldReaction.desequilibrio(container, container, function() { onComplete(false); });
                    }
                });

            } else if (data.mode === 'connector') {
                /* Insert discourse connectors */
                state.connIdx = state.connIdx || 0;
                state.correct = state.correct || 0;
                var conn = data.connectors[state.connIdx];
                if (!conn) {
                    onComplete(state.correct >= Math.ceil(data.connectors.length * 0.5));
                    return;
                }

                var html = renderTypeLabel(data.label || 'Tejido', 'tejido') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                html += '<div class="yg-tejido-fragments">';
                html += '<div class="yg-tejido-fragment yg-tejido-a">' + data.fragmentA + '</div>';
                html += '<div class="yg-tejido-fragment yg-tejido-b">' + data.fragmentB + '</div>';
                html += '</div>';

                if (data.connectors.length > 1) {
                    html += '<div class="yg-sub-counter">' + (state.connIdx + 1) + ' de ' + data.connectors.length + '</div>';
                }

                html += '<div class="yg-tejido-gap">' + (conn.before || '') + ' <span class="yg-tejido-blank">___</span> ' + (conn.after || '') + '</div>';

                if (conn.options && conn.options.length > 0) {
                    var opts = shuffle(conn.options.slice());
                    html += '<div class="yg-fib-options" id="ygTejidoOpts">';
                    for (var i = 0; i < opts.length; i++) {
                        html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                    }
                    html += '</div>';
                }
                container.innerHTML = html;

                var btns = container.querySelectorAll('.yg-fib-option');
                for (var b = 0; b < btns.length; b++) {
                    btns[b].addEventListener('click', function() {
                        var answer = conn.answer || '';
                        if (normalize(this.textContent) === normalize(answer)) {
                            state.correct++;
                            WorldReaction.harmony(container, this, function() {
                                state.connIdx++;
                                self.render(data, container, state, onComplete);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, this, function() {
                                state.connIdx++;
                                self.render(data, container, state, onComplete);
                            });
                        }
                    });
                }

            } else if (data.mode === 'bridge') {
                /* Write a bridging sentence */
                if (!state.phase) state.phase = 'write';

                if (state.phase === 'write') {
                    var html = renderTypeLabel(data.label || 'Tejido', 'tejido') +
                               '<div class="yg-instruction">' + (data.instruction || '') + '</div>';
                    html += '<div class="yg-tejido-fragments">';
                    html += '<div class="yg-tejido-fragment yg-tejido-a">' + data.fragmentA + '</div>';
                    html += '<div class="yg-tejido-bridge-gap">Escribe la oración puente:</div>';
                    html += '<div class="yg-tejido-fragment yg-tejido-b">' + data.fragmentB + '</div>';
                    html += '</div>';
                    html += '<textarea class="yg-dictogloss-input" id="ygTejidoInput" rows="2" placeholder="Tu oración puente..."></textarea>';
                    html += '<button class="yg-listo-btn" id="ygTejidoSubmit">Tejer</button>';
                    container.innerHTML = html;

                    document.getElementById('ygTejidoSubmit').addEventListener('click', function() {
                        state.attempt = (document.getElementById('ygTejidoInput').value || '').trim();
                        state.phase = 'review';
                        self.render(data, container, state, onComplete);
                    });

                } else if (state.phase === 'review') {
                    var html = renderTypeLabel(data.label || 'Tejido', 'tejido') +
                               '<div class="yg-instruction">Tu tejido:</div>';
                    html += '<div class="yg-tejido-result">';
                    html += '<div>' + data.fragmentA + '</div>';
                    html += '<div class="yg-tejido-bridge-text">' + state.attempt + '</div>';
                    html += '<div>' + data.fragmentB + '</div>';
                    html += '</div>';

                    /* Keyword scoring */
                    var matched = 0;
                    var valNorm = normalize(state.attempt || '');
                    for (var i = 0; i < data.bridgeKeywords.length; i++) {
                        if (valNorm.indexOf(normalize(data.bridgeKeywords[i])) !== -1) matched++;
                    }
                    var pass = data.bridgeKeywords.length > 0
                        ? matched >= Math.ceil(data.bridgeKeywords.length * 0.3)
                        : (state.attempt || '').split(/\s+/).length >= 3;

                    if (data.answer) {
                        html += '<div class="yg-dictogloss-original"><strong>Modelo:</strong> ' + data.answer + '</div>';
                    }
                    html += '<button class="yg-listo-btn" id="ygTejidoContinue">Continuar</button>';
                    container.innerHTML = html;

                    document.getElementById('ygTejidoContinue').addEventListener('click', function() {
                        onComplete(pass);
                    });
                }

            } else {
                /* Fallback: treat as order mode */
                data.mode = 'order';
                this.render(data, container, state, onComplete);
            }
        }
    };

    /* ----- CARTÓGRAFO (The Cartographer — Spatial Listening) ----- */
    Renderers.cartografo = {
        render: function(data, container, state, onComplete) {
            if (!state.phase) state.phase = 'listen';
            var self = this;

            if (state.phase === 'listen') {
                var html = renderTypeLabel(data.label || 'Cartógrafo', 'cartografo') +
                           '<div class="yg-instruction">' + (data.instruction || '') + '</div>';

                html += '<div class="yg-cartografo-visual">';
                html += '<div class="yg-cartografo-map" id="ygCartoMap">';
                /* Placeholder grid */
                for (var i = 0; i < 9; i++) {
                    html += '<div class="yg-cartografo-cell" data-cell="' + i + '">?</div>';
                }
                html += '</div>';
                html += '</div>';

                html += '<div class="yg-cartografo-controls">';
                html += '<button class="yg-parmin-play" id="ygCartoPlay">&#9654; Escuchar descripción</button>';
                if (data.replayLimit > 0) {
                    html += '<span class="yg-cartografo-replays" id="ygCartoReplays">Repeticiones: ' + data.replayLimit + '</span>';
                }
                html += '</div>';
                html += '<button class="yg-listo-btn" id="ygCartoReady">He escuchado — responder</button>';
                container.innerHTML = html;

                state.replaysUsed = state.replaysUsed || 0;

                /* Play audio */
                Audio.speak(data.audio, { rate: 0.85 });

                document.getElementById('ygCartoPlay').addEventListener('click', function() {
                    if (state.replaysUsed < data.replayLimit) {
                        state.replaysUsed++;
                        Audio.speak(data.audio, { rate: 0.85 });
                        var replaysEl = document.getElementById('ygCartoReplays');
                        if (replaysEl) {
                            var remaining = data.replayLimit - state.replaysUsed;
                            replaysEl.textContent = 'Repeticiones: ' + remaining;
                            if (remaining <= 0) {
                                document.getElementById('ygCartoPlay').disabled = true;
                                document.getElementById('ygCartoPlay').style.opacity = '0.3';
                            }
                        }
                    }
                });

                document.getElementById('ygCartoReady').addEventListener('click', function() {
                    state.phase = 'questions';
                    state.qIdx = 0;
                    state.correct = 0;
                    self.render(data, container, state, onComplete);
                });

            } else if (state.phase === 'questions') {
                var q = data.questions[state.qIdx];
                if (!q) {
                    onComplete(state.correct >= Math.ceil(data.questions.length * 0.5));
                    return;
                }

                var html = renderTypeLabel(data.label || 'Cartógrafo', 'cartografo') +
                           '<div class="yg-instruction">Responde sobre el mapa.</div>';

                if (data.questions.length > 1) {
                    html += '<div class="yg-sub-counter">' + (state.qIdx + 1) + ' de ' + data.questions.length + '</div>';
                }

                /* Map with revealed cells */
                html += '<div class="yg-cartografo-visual">';
                html += '<div class="yg-cartografo-map" id="ygCartoMap">';
                for (var i = 0; i < 9; i++) {
                    var label = '?';
                    if (data.gridLabels && data.gridLabels[i] && state.revealed && state.revealed[i]) {
                        label = data.gridLabels[i];
                    }
                    var revealCls = (state.revealed && state.revealed[i]) ? ' yg-cartografo-revealed' : '';
                    html += '<div class="yg-cartografo-cell' + revealCls + '" data-cell="' + i + '">' + label + '</div>';
                }
                html += '</div>';
                html += '</div>';

                html += '<div class="yg-cartografo-question">' + q.q + '</div>';

                if (q.options && q.options.length > 0) {
                    var opts = shuffle(q.options.slice());
                    html += '<div class="yg-fib-options" id="ygCartoOpts">';
                    for (var i = 0; i < opts.length; i++) {
                        html += '<button class="yg-fib-option">' + opts[i] + '</button>';
                    }
                    html += '</div>';
                } else {
                    html += '<input type="text" class="yg-dict-input" id="ygCartoInput" placeholder="Tu respuesta..." autocomplete="off" autocapitalize="none">';
                    html += '<button class="yg-listo-btn" id="ygCartoSubmit">Responder</button>';
                }
                container.innerHTML = html;

                function advanceCarto(success) {
                    if (success) {
                        state.correct++;
                        /* Reveal a map cell */
                        if (!state.revealed) state.revealed = {};
                        state.revealed[state.qIdx % 9] = true;
                    }
                    state.qIdx++;
                    self.render(data, container, state, onComplete);
                }

                if (q.options && q.options.length > 0) {
                    var btns = container.querySelectorAll('.yg-fib-option');
                    for (var b = 0; b < btns.length; b++) {
                        btns[b].addEventListener('click', function() {
                            if (normalize(this.textContent) === normalize(q.answer)) {
                                WorldReaction.harmony(container, this, function() { advanceCarto(true); });
                            } else {
                                WorldReaction.desequilibrio(container, this, function() { advanceCarto(false); });
                            }
                        });
                    }
                } else {
                    document.getElementById('ygCartoSubmit').addEventListener('click', function() {
                        var val = (document.getElementById('ygCartoInput').value || '').trim();
                        if (normalize(val) === normalize(q.answer)) {
                            WorldReaction.harmony(container, document.getElementById('ygCartoInput'), function() { advanceCarto(true); });
                        } else {
                            WorldReaction.desequilibrio(container, document.getElementById('ygCartoInput'), function() { advanceCarto(false); });
                        }
                    });
                }
            }
        }
    };

    /* ----- SKIT (Animated micro-scene — replaces grammar explanations) ----- */
    Renderers.skit = {
        render: function(data, container, state, onComplete) {
            if (state.beatIdx === undefined) state.beatIdx = 0;

            /* Resolve {nombre} */
            var _nameStr = '...';
            try { var u = window.JaguarAPI && JaguarAPI.getUser(); _nameStr = (u && (u.display_name || u.name)) || '...'; } catch(e){}
            state._nameStr = _nameStr;

            var html = renderTypeLabel(data.label || 'Escena', 'skit');
            if (data.instruction) {
                html += '<div class="yg-instruction">' + data.instruction + '</div>';
            }
            html += '<div class="yg-skit-stage" id="ygSkitStage"></div>';
            container.innerHTML = html;

            this._playBeat(data, container, state, onComplete);
        },

        _playBeat: function(data, container, state, onComplete) {
            if (state.beatIdx >= data.beats.length) {
                /* All beats done — pause then complete */
                setTimeout(function() { onComplete(true); }, 1500);
                return;
            }

            var beat = data.beats[state.beatIdx];
            var stage = document.getElementById('ygSkitStage');
            if (!stage) return;
            var self = this;
            var text = (beat.text || '').replace(/\{nombre\}/g, state._nameStr);

            /* Build display text with target-word highlights */
            var displayText = text;
            if (beat.target) {
                var targets = Array.isArray(beat.target) ? beat.target : [beat.target];
                for (var t = 0; t < targets.length; t++) {
                    var esc = targets[t].replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    displayText = displayText.replace(new RegExp('(' + esc + ')', 'gi'),
                        '<span class="yg-skit-target">$1</span>');
                }
            }

            var isExit = beat.animation && beat.animation.indexOf('exit') === 0;
            var isFadeOut = beat.animation === 'fade-out';

            /* Create bubble */
            var bubble = document.createElement('div');
            bubble.className = 'yg-skit-bubble yg-skit-enter';
            bubble.setAttribute('data-speaker', beat.speaker || '');

            /* Speaker label */
            var nameEl = document.createElement('div');
            nameEl.className = 'yg-skit-speaker';
            nameEl.textContent = beat.name || beat.speaker || '';
            bubble.appendChild(nameEl);

            /* Text */
            var textEl = document.createElement('div');
            textEl.className = 'yg-skit-text';

            if (beat.interaction) {
                textEl.textContent = '???';
                bubble.classList.add('yg-skit-mystery');
            } else {
                textEl.innerHTML = displayText;
            }
            bubble.appendChild(textEl);

            /* Animation class */
            if (beat.animation && beat.animation !== 'fade-in' && !isExit && !isFadeOut) {
                bubble.classList.add('yg-skit-' + beat.animation);
            }

            stage.appendChild(bubble);
            stage.scrollTop = stage.scrollHeight;

            /* ---------- INTERACTION BEAT ---------- */
            if (beat.interaction) {
                setTimeout(function() {
                    var optDiv = document.createElement('div');
                    optDiv.className = 'yg-skit-options';
                    var opts = beat.interaction.options;
                    for (var o = 0; o < opts.length; o++) {
                        var btn = document.createElement('div');
                        btn.className = 'yg-skit-option';
                        btn.dataset.val = opts[o];
                        btn.textContent = opts[o];
                        optDiv.appendChild(btn);
                    }
                    stage.appendChild(optDiv);
                    stage.scrollTop = stage.scrollHeight;

                    var resolved = false;
                    optDiv.addEventListener('click', function(e) {
                        var el = e.target.closest('.yg-skit-option');
                        if (!el || resolved) return;
                        resolved = true;

                        var allOpts = optDiv.querySelectorAll('.yg-skit-option');
                        for (var j = 0; j < allOpts.length; j++) allOpts[j].style.pointerEvents = 'none';

                        if (normalize(el.dataset.val) === normalize(beat.interaction.answer)) {
                            textEl.innerHTML = displayText;
                            bubble.classList.remove('yg-skit-mystery');
                            if (beat.tts !== false && text) Audio.speak(text);
                            WorldReaction.harmony(container, el, function() {
                                optDiv.remove();
                                state.beatIdx++;
                                setTimeout(function() { self._playBeat(data, container, state, onComplete); }, 800);
                            });
                        } else {
                            WorldReaction.desequilibrio(container, el, function() {
                                resolved = false;
                                for (var j = 0; j < allOpts.length; j++) allOpts[j].style.pointerEvents = '';
                            });
                        }
                    });
                }, 500);
                return;
            }

            /* ---------- NARRATIVE BEAT (auto-advance) ---------- */
            if (beat.tts !== false && text) {
                setTimeout(function() { Audio.speak(text); }, 200);
            }

            var advDelay = Math.max(text.length * 55 + 800, 1500);

            if (isExit || isFadeOut) {
                /* Show line, then animate bubble out */
                setTimeout(function() {
                    bubble.classList.add('yg-skit-leaving');
                    setTimeout(function() {
                        state.beatIdx++;
                        self._playBeat(data, container, state, onComplete);
                    }, 600);
                }, advDelay);
            } else {
                setTimeout(function() {
                    state.beatIdx++;
                    self._playBeat(data, container, state, onComplete);
                }, advDelay);
            }
        }
    };

    /* ==========================================================
       9. ENCOUNTER ORCHESTRATOR (5-beat rhythm)
    ========================================================== */
    /* ----- TIP (Knowledge Pill) ----- */
    function injectTipButton(container, data) {
        if (!data.tip) return;
        var instr = container.querySelector('.yg-instruction');
        if (!instr) return;
        /* Wrap instruction in flex row with tip button */
        var wrapper = document.createElement('div');
        wrapper.className = 'yg-instruction-row';
        instr.parentNode.insertBefore(wrapper, instr);
        wrapper.appendChild(instr);
        var btn = document.createElement('button');
        btn.className = 'yg-tip-btn';
        btn.setAttribute('aria-label', 'Consejo');
        btn.innerHTML = '?';
        wrapper.appendChild(btn);
        btn.addEventListener('click', function() { showTipOverlay(data.tip); });
    }

    function showTipOverlay(tip) {
        var existing = document.getElementById('ygTipOverlay');
        if (existing) existing.remove();

        var html = '<div class="yg-tip-overlay" id="ygTipOverlay">' +
            '<div class="yg-tip-backdrop" id="ygTipBackdrop"></div>' +
            '<div class="yg-tip-card">' +
                '<div class="yg-tip-header">' +
                    '<span class="yg-tip-title">' + (tip.title || '') + '</span>' +
                    '<button class="yg-tip-close" id="ygTipClose">&times;</button>' +
                '</div>' +
                '<div class="yg-tip-lines">';
        if (tip.lines && tip.lines.length) {
            for (var i = 0; i < tip.lines.length; i++) {
                html += '<div class="yg-tip-line">' + tip.lines[i] + '</div>';
            }
        }
        html += '</div>';
        if (tip.gloss) {
            html += '<div class="yg-tip-gloss">' + tip.gloss + '</div>';
        }
        html += '</div></div>';

        var overlay = document.createElement('div');
        overlay.innerHTML = html;
        document.body.appendChild(overlay.firstChild);

        var close = function() {
            var el = document.getElementById('ygTipOverlay');
            if (el) { el.classList.add('yg-tip-closing'); setTimeout(function() { el.remove(); }, 300); }
        };
        document.getElementById('ygTipClose').addEventListener('click', close);
        document.getElementById('ygTipBackdrop').addEventListener('click', close);

        setTimeout(function() {
            var el = document.getElementById('ygTipOverlay');
            if (el) el.classList.add('yg-tip-visible');
        }, 30);
    }

    /* ── Input Mode Transformer ──
       Bridges adaptivity engine (_inputMode) → renderer behavior.
       Mutates question objects so renderers pick the right UI. */
    function _applyInputMode(data) {
        var mode = data._inputMode;
        if (!mode || mode === 'choice') return; /* choice = default, no-op */

        var questions = data.questions;
        if (!questions || !questions.length) return;

        for (var i = 0; i < questions.length; i++) {
            var q = questions[i];
            if (mode === 'drag') {
                q._dragMode = true;
                /* keep q.options — drag chips need them */
            } else if (mode === 'typing') {
                if (q.options) {
                    q._originalOptions = q.options;
                    delete q.options;
                }
            } else if (mode === 'voice') {
                if (q.options) {
                    q._originalOptions = q.options;
                    delete q.options;
                }
                q._voiceMode = true;
            } else if (mode === 'self_correction') {
                if (q.options) {
                    q._originalOptions = q.options;
                    delete q.options;
                }
                q._selfCorrection = true;
            }
        }
    }

    function runEncounter(data, container, state, onComplete) {
        var wrapper = container.closest('.yg-encounter-wrapper') || container;

        /* Encounter start SFX */
        if (window.AudioManager) AudioManager.playEncounterStart();

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
                var renderTarget = prepareSceneImage(container, data);
                var renderer = Renderers[data.type];
                if (renderer) {
                    renderer.render(data, renderTarget, state, function(success) {
                        /* Beat 4 — REACTION is handled inside renderers via WorldReaction */
                        /* Beat 5 — REFLECTION */
                        setTimeout(function() {
                            onComplete(success);
                        }, CONFIG.reflectionPause);
                    });
                    /* Inject optional tip button after renderer builds DOM */
                    injectTipButton(renderTarget, data);
                } else {
                    renderTarget.innerHTML = '<div class="yg-instruction">Tipo de encuentro no reconocido: ' + data.type + '</div>';
                }
            }, CONFIG.intentDuration);
        }, CONFIG.immersionDuration);
    }

    /* ==========================================================
       10. MAIN ENGINE
    ========================================================== */
    /* ── Lexicon mode map: game type → acquisition mode ── */
    var LEXICON_MODE = {
        /* encountered: student heard/read */
        skit: 'encountered', narrative: 'encountered', story: 'encountered',
        despertar: 'encountered', cancion: 'encountered', cultura: 'encountered',
        susurro: 'encountered', explorador: 'encountered',
        /* recognized: student chose correctly */
        fill: 'recognized', pair: 'recognized', category: 'recognized',
        listening: 'recognized', translation: 'recognized', par_minimo: 'recognized',
        flashnote: 'recognized', boggle: 'recognized', brecha: 'recognized',
        clon: 'recognized', crossword: 'recognized', madgab: 'recognized',
        madlibs: 'recognized', spaceman: 'recognized', kloo: 'recognized',
        guardian: 'recognized', senda: 'recognized', registro: 'recognized',
        cartografo: 'recognized', codice: 'recognized', oraculo: 'recognized',
        eco_lejano: 'recognized',
        /* produced: student typed/constructed */
        builder: 'produced', dictation: 'produced', conjugation: 'produced',
        conversation: 'produced', tertulia: 'produced', pregonero: 'produced',
        debate: 'produced', negociacion: 'produced', descripcion: 'produced',
        dictogloss: 'produced', corrector: 'produced', resumen: 'produced',
        raiz: 'produced', tejido: 'produced', transformador: 'produced',
        cronometro: 'produced', conjuro: 'produced', sombra: 'produced',
        consequences: 'produced', bananagrams: 'produced', escaperoom: 'produced',
        ritmo: 'produced', eco_restaurar: 'produced',
        /* creative: student created original text */
        cronica: 'creative', portafolio: 'creative', autoevaluacion: 'creative'
    };

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
        _lexicon: null,
        _currentEco: null,

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

            /* Evidence + Adaptivity pipeline */
            if (window.EvidenceEngine && opts.ontology) {
                EvidenceEngine.init(opts.ontology);
                if (window.AdaptivityEngine) {
                    AdaptivityEngine.init(opts.ontology, EvidenceEngine);
                }
            }

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
                var normalized = Normalizer.normalizeGame(rawGames[i]);
                /* Fill {pool:...} markers with personalized vocabulary */
                if (window.PersonalLexicon) {
                    var lex = this._lexicon || PersonalLexicon.getInstance();
                    normalized = lex.generatePool(normalized, this._currentEco || opts.ecosystem || null);
                }
                this._games.push(normalized);
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
            this._phaseBreaths = {};

            this._migrateStorage();
            this._loadProgress();
            this._bindNav();
            this._injectResourcesMenu();
            this._initCharKeyboard();

            /* Accessibility — live region for screen reader announcements */
            A11y.initLiveRegion();

            /* Feedback widget — floating button for bug reports / suggestions */
            this._initFeedbackWidget();

            /* PersonalLexicon — singleton, created once */
            if (window.PersonalLexicon) {
                this._lexicon = PersonalLexicon.getInstance();
                if (opts.ecosystem) this._currentEco = opts.ecosystem;
            }

            /* Opening Yaguará interjection — skip in arcade mode and A1 */
            var self = this;
            if (this._arcadeMode || this._config.cefr === 'A1') {
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
                '</a>' +
                '<div class="yg-res-divider"></div>' +
                '<a href="cuaderno.html" class="yg-res-item">' +
                    ICONS.story +
                    '<span>Cuaderno de Candelaria</span>' +
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

        _initCharKeyboard: function() {
            if (document.getElementById('ygCharKeyboard')) return;

            var chars = ['ñ','á','é','í','ó','ú','ü','¿','¡'];
            var kb = document.createElement('div');
            kb.className = 'yg-charkb';
            kb.id = 'ygCharKeyboard';

            var toggle = document.createElement('button');
            toggle.className = 'yg-charkb-toggle';
            toggle.type = 'button';
            toggle.textContent = 'ñ';
            toggle.title = 'Caracteres especiales';

            var tray = document.createElement('div');
            tray.className = 'yg-charkb-tray';
            for (var i = 0; i < chars.length; i++) {
                var btn = document.createElement('button');
                btn.className = 'yg-charkb-key';
                btn.type = 'button';
                btn.textContent = chars[i];
                btn.setAttribute('data-char', chars[i]);
                tray.appendChild(btn);
            }

            kb.appendChild(toggle);
            kb.appendChild(tray);
            document.body.appendChild(kb);

            var activeInput = null;
            var open = false;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                open = !open;
                tray.classList.toggle('visible', open);
                toggle.classList.toggle('open', open);
            });

            tray.addEventListener('click', function(e) {
                var key = e.target.closest('.yg-charkb-key');
                if (!key) return;
                e.preventDefault();
                e.stopPropagation();
                var ch = key.getAttribute('data-char');
                if (activeInput && typeof activeInput.setRangeText === 'function') {
                    var start = activeInput.selectionStart;
                    activeInput.setRangeText(ch, start, activeInput.selectionEnd, 'end');
                    activeInput.focus();
                    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            /* Latin keyboard tip for non-Latin-script users (one-time) */
            var _latinTipShown = false;
            var _nonLatinScripts = {
                ar: 'العربية', ja: '日本語', ko: '한국어', zh: '中文',
                ru: 'Русский', uk: 'Українська', el: 'Ελληνικά',
                he: 'עברית', hi: 'हिन्दी', th: 'ไทย', bn: 'বাংলা',
                ta: 'தமிழ்', te: 'తెలుగు', ka: 'ქართული',
                hy: 'Հայերեն', am: 'አማርኛ', my: 'မြန်မာ', km: 'ខ្មែរ'
            };
            var _latinTipTexts = {
                ar: 'للكتابة بالإسبانية، تحتاج إلى لوحة مفاتيح لاتينية (إنجليزية) على جهازك. يمكنك التبديل من إعدادات لوحة المفاتيح.',
                ja: 'スペイン語を入力するには、デバイスにラテン文字（英語）キーボードが必要です。キーボード設定から切り替えできます。',
                ko: '스페인어를 입력하려면 기기에 라틴(영어) 키보드가 필요합니다. 키보드 설정에서 전환할 수 있습니다.',
                zh: '要输入西班牙语，您需要在设备上安装拉丁（英文）键盘。可以在键盘设置中切换。',
                ru: 'Для ввода на испанском вам нужна латинская (английская) клавиатура. Переключите её в настройках клавиатуры.',
                uk: 'Для введення іспанською потрібна латинська (англійська) клавіатура. Перемкніть її в налаштуваннях.',
                el: 'Για να γράψετε στα ισπανικά, χρειάζεστε λατινικό (αγγλικό) πληκτρολόγιο. Αλλάξτε το στις ρυθμίσεις.',
                he: 'כדי להקליד בספרדית, צריך מקלדת לטינית (אנגלית). ניתן להחליף בהגדרות המקלדת.',
                hi: 'स्पेनिश में टाइप करने के लिए आपको लैटिन (अंग्रेज़ी) कीबोर्ड चाहिए। कीबोर्ड सेटिंग्स में बदलें।',
                th: 'หากต้องการพิมพ์ภาษาสเปน คุณต้องมีแป้นพิมพ์ละติน (อังกฤษ) สลับได้ในการตั้งค่าแป้นพิมพ์',
                bn: 'স্প্যানিশ টাইপ করতে আপনার ল্যাটিন (ইংরেজি) কীবোর্ড দরকার। কীবোর্ড সেটিংস থেকে পরিবর্তন করুন।',
                ta: 'ஸ்பானிஷ் தட்டச்சு செய்ய லத்தீன் (ஆங்கில) விசைப்பலகை தேவை. விசைப்பலகை அமைப்புகளில் மாற்றவும்.',
                te: 'స్పానిష్ టైప్ చేయడానికి లాటిన్ (ఆంగ్ల) కీబోర్డ్ అవసరం. కీబోర్డ్ సెట్టింగ్‌లలో మార్చండి.',
                ka: 'ესპანურად საწერად გჭირდებათ ლათინური (ინგლისური) კლავიატურა. გადართეთ კლავიატურის პარამეტრებში.',
                hy: 'Իspanish — Latin keyboard required.',
                am: 'በስፓኒሽ ለመጻፍ የላቲን (እንግሊዝኛ) ቁልፍ ሰሌዳ ያስፈልጋል። በቁልፍ ሰሌዳ ቅንብሮች ይቀይሩ።',
                my: 'စပိန်ဘာသာ ရိုက်ရန် လက်တင် (အင်္ဂလိပ်) ကီးဘုတ် လိုအပ်ပါသည်။ ကီးဘုတ် ဆက်တင်တွင် ပြောင်းပါ။',
                km: 'ដើម្បីវាយអក្សរភាសាអេស្ប៉ាញ អ្នកត្រូវការក្តារចុចឡាតាំង (អង់គ្លេស)។ ប្តូរក្នុងការកំណត់ក្តារចុច។'
            };

            function _showLatinTip(inputEl) {
                if (_latinTipShown || localStorage.getItem('yaguara_latinkb_tip')) return;
                var session;
                try { session = JSON.parse(localStorage.getItem('jaguarUserSession') || '{}'); } catch(e) { return; }
                var lang = session.nativeLanguage || session.interfaceLanguage || session.detectedLanguage || '';
                var baseLang = lang.split('-')[0];
                if (!_nonLatinScripts[baseLang]) return;

                _latinTipShown = true;
                var text = _latinTipTexts[baseLang] || _latinTipTexts['en'];
                if (!text) return;

                var tip = document.createElement('div');
                tip.className = 'yg-latintip';
                tip.innerHTML = '<div class="yg-latintip-text">' + text + '</div>' +
                    '<button type="button" class="yg-latintip-close">✓</button>';
                document.body.appendChild(tip);
                requestAnimationFrame(function() { tip.classList.add('visible'); });

                tip.querySelector('.yg-latintip-close').addEventListener('click', function() {
                    tip.classList.remove('visible');
                    setTimeout(function() { tip.remove(); }, 300);
                    try { localStorage.setItem('yaguara_latinkb_tip', 'true'); } catch(e) {}
                });
            }

            document.addEventListener('focusin', function(e) {
                var el = e.target;
                if (el.matches('.yg-dict-input, .yg-cancion-blank, .yg-escape-input, .yg-cronica-area')) {
                    activeInput = el;
                    kb.classList.add('yg-charkb-active');
                    _showLatinTip(el);
                } else {
                    kb.classList.remove('yg-charkb-active');
                }
            });

            document.addEventListener('click', function(e) {
                if (open && !e.target.closest('.yg-charkb')) {
                    open = false;
                    tray.classList.remove('visible');
                    toggle.classList.remove('open');
                }
            });
        },

        _initFeedbackWidget: function() {
            if (document.getElementById('ygFeedbackBtn')) return;

            var self = this;
            var SESSION_KEY = 'ygFeedbackCount';
            var MAX_PER_SESSION = 5;
            var isOpen = false;

            function getCount() { try { return parseInt(sessionStorage.getItem(SESSION_KEY), 10) || 0; } catch(e) { return 0; } }
            function incCount() { try { sessionStorage.setItem(SESSION_KEY, getCount() + 1); } catch(e) {} }

            /* Determine title by CEFR level */
            var cefr = (this._config.cefr || 'A1').toUpperCase();
            var isAdvanced = cefr.charAt(0) >= 'B';
            var title = isAdvanced ? 'Comparte tu opini\u00f3n' : '\u00bfAlgo que decirnos?';

            /* Floating button */
            var btn = document.createElement('button');
            btn.className = 'yg-feedback-btn';
            btn.id = 'ygFeedbackBtn';
            btn.setAttribute('aria-label', 'Enviar comentarios');
            btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';

            /* Overlay */
            var overlay = document.createElement('div');
            overlay.className = 'yg-feedback-overlay';

            /* Panel */
            var panel = document.createElement('div');
            panel.className = 'yg-feedback-panel';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-label', 'Formulario de comentarios');
            panel.setAttribute('aria-hidden', 'true');
            panel.innerHTML =
                '<div class="yg-feedback-header">' +
                    '<h3 class="yg-feedback-title">' + title + '</h3>' +
                    '<button class="yg-feedback-close" aria-label="Cerrar">\u00d7</button>' +
                '</div>' +
                '<div class="yg-feedback-type" role="radiogroup" aria-label="Tipo de comentario">' +
                    '<label><input type="radio" name="ygFbType" value="error"><span class="yg-feedback-type-icon">\ud83d\udd34</span> Encontr\u00e9 un error</label>' +
                    '<label><input type="radio" name="ygFbType" value="suggestion"><span class="yg-feedback-type-icon">\ud83d\udca1</span> Tengo una idea</label>' +
                    '<label><input type="radio" name="ygFbType" value="content"><span class="yg-feedback-type-icon">\ud83d\udcdd</span> Sobre el contenido</label>' +
                    '<label><input type="radio" name="ygFbType" value="technical"><span class="yg-feedback-type-icon">\u2699\ufe0f</span> Problema t\u00e9cnico</label>' +
                    '<label><input type="radio" name="ygFbType" value="question"><span class="yg-feedback-type-icon">\u2753</span> Pregunta de gram\u00e1tica</label>' +
                '</div>' +
                '<div class="yg-feedback-question-note" style="display:none;padding:8px 12px;margin:0 0 8px;background:#c9a84c1a;border-radius:4px;color:#c9a84c;font-size:13px;line-height:1.4;"></div>' +
                '<textarea class="yg-feedback-textarea" placeholder="Escribe aqu\u00ed..." rows="4" aria-label="Tu mensaje"></textarea>' +
                '<div class="yg-feedback-error" style="display:none"></div>' +
                '<button class="yg-feedback-submit">Enviar</button>';

            function openPanel() {
                if (isOpen) return;
                isOpen = true;
                panel.classList.add('yg-feedback-open');
                overlay.classList.add('yg-feedback-open');
                btn.style.display = 'none';
                panel.setAttribute('aria-hidden', 'false');
                var first = panel.querySelector('input[type="radio"]');
                if (first) first.focus();
            }
            function closePanel() {
                if (!isOpen) return;
                isOpen = false;
                panel.classList.remove('yg-feedback-open');
                overlay.classList.remove('yg-feedback-open');
                btn.style.display = '';
                panel.setAttribute('aria-hidden', 'true');
                btn.focus();
            }

            btn.addEventListener('click', openPanel);
            overlay.addEventListener('click', closePanel);
            panel.querySelector('.yg-feedback-close').addEventListener('click', closePanel);

            /* Radio highlight + question note */
            var labels = panel.querySelectorAll('.yg-feedback-type label');
            var questionNote = panel.querySelector('.yg-feedback-question-note');
            for (var i = 0; i < labels.length; i++) {
                labels[i].addEventListener('change', function() {
                    for (var j = 0; j < labels.length; j++) labels[j].classList.remove('yg-fb-selected');
                    this.classList.add('yg-fb-selected');
                    var selectedVal = this.querySelector('input').value;
                    if (selectedVal === 'question' && questionNote) {
                        var isLoggedIn = window.JaguarAPI && typeof JaguarAPI.isAuthenticated === 'function' && JaguarAPI.isAuthenticated();
                        questionNote.textContent = isLoggedIn
                            ? 'Te enviaremos la respuesta a tu correo electr\u00f3nico.'
                            : 'Inicia sesi\u00f3n para recibir respuestas por correo.';
                        questionNote.style.display = '';
                    } else if (questionNote) {
                        questionNote.style.display = 'none';
                    }
                });
            }

            /* Escape key */
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isOpen) closePanel();
            });

            /* Submit handler */
            panel.querySelector('.yg-feedback-submit').addEventListener('click', function() {
                if (getCount() >= MAX_PER_SESSION) {
                    showLimit();
                    return;
                }
                var typeEl = panel.querySelector('input[name="ygFbType"]:checked');
                var textarea = panel.querySelector('.yg-feedback-textarea');
                var errDiv = panel.querySelector('.yg-feedback-error');
                var submitBtn = panel.querySelector('.yg-feedback-submit');

                errDiv.style.display = 'none';
                if (!typeEl) { errDiv.textContent = 'Selecciona un tipo de comentario.'; errDiv.style.display = ''; return; }
                var message = (textarea.value || '').trim();
                if (!message) { errDiv.textContent = 'Escribe un mensaje.'; errDiv.style.display = ''; return; }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando\u2026';

                /* Gather game context */
                var gameData = self._games[self._currentIdx] || {};
                var ctx = {
                    url: window.location.href,
                    userAgent: navigator.userAgent,
                    destination: self._config.destinationId || null,
                    destNum: self._config.destNum || null,
                    cefr: self._config.cefr || null,
                    gameIndex: self._currentIdx,
                    gameType: gameData.type || null,
                    gameInstruction: (gameData.instruction || gameData.prompt || gameData.question || '').substring(0, 200),
                    totalEncounters: self._games.length,
                    timestamp: new Date().toISOString()
                };

                var payload = { feedback_type: typeEl.value, message: message, context: ctx };

                var headers = { 'Content-Type': 'application/json' };
                if (window.JaguarAPI && typeof JaguarAPI.isAuthenticated === 'function' && JaguarAPI.isAuthenticated()) {
                    try {
                        var raw = localStorage.getItem('jaguarUserSession');
                        var session = raw ? JSON.parse(raw) : null;
                        if (session && session.serverToken) headers['Authorization'] = 'Bearer ' + session.serverToken;
                    } catch(e) {}
                }
                var csrfMatch = document.cookie.match(/(?:^|;\s*)csrf_token=([^;]+)/);
                if (csrfMatch) headers['X-CSRF-Token'] = csrfMatch[1];

                fetch('/api/feedback/submit', {
                    method: 'POST',
                    headers: headers,
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                }).then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        incCount();
                        showSuccess();
                    } else {
                        showError();
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Enviar';
                    }
                }).catch(function() {
                    showError();
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar';
                });
            });

            function showSuccess() {
                var body = panel.querySelector('.yg-feedback-type');
                var ta = panel.querySelector('.yg-feedback-textarea');
                var sub = panel.querySelector('.yg-feedback-submit');
                var err = panel.querySelector('.yg-feedback-error');
                var qNote = panel.querySelector('.yg-feedback-question-note');
                body.style.display = 'none'; ta.style.display = 'none'; sub.style.display = 'none'; err.style.display = 'none';
                if (qNote) qNote.style.display = 'none';

                var msg = document.createElement('div');
                msg.className = 'yg-feedback-success';
                msg.textContent = '\u00a1Gracias!';
                panel.appendChild(msg);

                setTimeout(function() {
                    closePanel();
                    setTimeout(function() {
                        msg.remove();
                        body.style.display = ''; ta.style.display = ''; sub.style.display = '';
                        sub.disabled = false; sub.textContent = 'Enviar';
                        ta.value = '';
                        for (var k = 0; k < labels.length; k++) labels[k].classList.remove('yg-fb-selected');
                        var radios = panel.querySelectorAll('input[name="ygFbType"]');
                        for (var k = 0; k < radios.length; k++) radios[k].checked = false;
                    }, 500);
                }, 2000);
            }

            function showError() {
                var err = panel.querySelector('.yg-feedback-error');
                err.textContent = 'Error al enviar. Int\u00e9ntalo m\u00e1s tarde.';
                err.style.display = '';
            }

            function showLimit() {
                var body = panel.querySelector('.yg-feedback-type');
                var ta = panel.querySelector('.yg-feedback-textarea');
                var sub = panel.querySelector('.yg-feedback-submit');
                var err = panel.querySelector('.yg-feedback-error');
                body.style.display = 'none'; ta.style.display = 'none'; sub.style.display = 'none'; err.style.display = 'none';

                var msg = document.createElement('div');
                msg.className = 'yg-feedback-limit';
                msg.textContent = 'Has alcanzado el l\u00edmite de comentarios por sesi\u00f3n. \u00a1Gracias por tu inter\u00e9s!';
                panel.appendChild(msg);
            }

            document.body.appendChild(overlay);
            document.body.appendChild(panel);
            document.body.appendChild(btn);
        },

        _updateUI: function() {
            var idx = this._currentIdx;
            var total = this._games.length;

            Progress.render(this._progressContainer, idx + 1, total);

            var prevBtn = document.getElementById('ygBtnPrev');
            var nextBtn = document.getElementById('ygBtnNext');

            if (prevBtn) prevBtn.disabled = (idx === 0);
            /* Next is enabled if current encounter is completed OR if any encounter ahead is already completed (free navigation) */
            if (nextBtn) {
                var canAdvance = this._completed[idx] && idx < total - 1;
                nextBtn.disabled = !canAdvance;
            }
        },

        _loadEncounter: function(index) {
            if (index < 0 || index >= this._games.length) return;
            this._currentIdx = index;
            var data = this._games[index];
            var state = this._gameStates[index];
            var self = this;

            this._updateUI();

            /* ---- Cluster leader: render narrative + practice as one scrollable unit ---- */
            if (typeof data._clusterWith === 'number' && !this._completed[index]) {
                var memberIdx = data._clusterWith;
                var memberData = this._games[memberIdx];
                var memberState = this._gameStates[memberIdx];

                this._encounterStartTime = Date.now();
                this._encounterAttempts = 0;

                /* Build cluster wrapper */
                this._container.style.opacity = '0';
                this._container.style.transform = 'translateY(16px)';
                this._container.innerHTML = '';

                setTimeout(function() {
                    self._container.style.transition = 'all ' + CONFIG.transitionSpeed + 'ms cubic-bezier(0.23, 1, 0.32, 1)';
                    self._container.style.opacity = '1';
                    self._container.style.transform = 'translateY(0)';

                    var cluster = document.createElement('div');
                    cluster.className = 'yg-cluster';

                    /* Leader section */
                    var leaderSection = document.createElement('div');
                    leaderSection.className = 'yg-cluster-leader';
                    cluster.appendChild(leaderSection);

                    /* Divider */
                    var divider = document.createElement('div');
                    divider.className = 'yg-cluster-divider';
                    cluster.appendChild(divider);

                    /* Member section (hidden initially) */
                    var memberSection = document.createElement('div');
                    memberSection.className = 'yg-cluster-next';
                    cluster.appendChild(memberSection);

                    self._container.appendChild(cluster);

                    /* Render leader (narrative) */
                    setTimeout(function() {
                        var leaderRenderer = Renderers[data.type];
                        if (leaderRenderer) {
                            leaderRenderer.render(data, leaderSection, state, function() {
                                /* Leader "completed" (narrative button clicked) — reveal member */
                                self._completed[index] = true;

                                /* Render member (practice encounter) */
                                _applyInputMode(memberData);
                                var memberRenderTarget = prepareSceneImage(memberSection, memberData);
                                var memberRenderer = Renderers[memberData.type];
                                if (memberRenderer) {
                                    memberRenderer.render(memberData, memberRenderTarget, memberState, function(success) {
                                        /* Member completed — advance past both */
                                        self._onClusterMemberComplete(memberIdx, success);
                                    });
                                }

                                /* Reveal + scroll to member */
                                setTimeout(function() {
                                    memberSection.classList.add('yg-cluster-visible');
                                    memberSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }, 200);
                            });
                        }
                    }, CONFIG.intentDuration);
                }, CONFIG.immersionDuration);

                return;
            }

            /* If already completed, re-render without orchestrator */
            if (this._completed[index]) {
                _applyInputMode(data);
                this._container.innerHTML = '';
                var renderTarget = prepareSceneImage(this._container, data);
                var renderer = Renderers[data.type];
                if (renderer) {
                    renderer.render(data, renderTarget, state, function() {});
                }
                return;
            }

            /* Start timing for this encounter */
            this._encounterStartTime = Date.now();
            this._encounterAttempts = 0;

            _applyInputMode(data);
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
            /* New game types (Feb 2026) */
            if (data.type === 'susurro' || data.type === 'sombra') {
                if (data.keywords) vocab = vocab.concat(data.keywords);
            }
            if (data.type === 'eco_lejano' && data.questions) {
                for (var i = 0; i < data.questions.length; i++) {
                    if (data.questions[i].answer) vocab.push(data.questions[i].answer);
                }
            }
            if (data.type === 'tertulia' && data.turns) {
                for (var i = 0; i < data.turns.length; i++) {
                    if (data.turns[i].answer) vocab.push(data.turns[i].answer);
                }
            }
            if (data.type === 'pregonero' && data.keywords) {
                vocab = vocab.concat(data.keywords);
            }
            if (data.type === 'raiz') {
                if (data.word) vocab.push(data.word);
                if (data.relatedWords) vocab = vocab.concat(data.relatedWords);
            }
            if (data.type === 'codice' && data.highlights) {
                for (var i = 0; i < data.highlights.length; i++) {
                    if (data.highlights[i].word) vocab.push(data.highlights[i].word);
                }
            }
            if (data.type === 'oraculo') {
                if (data.answer) vocab.push(data.answer);
            }
            if (data.type === 'cartografo' && data.questions) {
                for (var i = 0; i < data.questions.length; i++) {
                    if (data.questions[i].answer) vocab.push(data.questions[i].answer);
                }
            }
            /* Skit: extract target words + interaction answers */
            if (data.type === 'skit' && data.beats) {
                for (var i = 0; i < data.beats.length; i++) {
                    if (data.beats[i].target) {
                        var targets = Array.isArray(data.beats[i].target) ? data.beats[i].target : [data.beats[i].target];
                        vocab = vocab.concat(targets);
                    }
                    if (data.beats[i].interaction && data.beats[i].interaction.answer) {
                        vocab.push(data.beats[i].interaction.answer);
                    }
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
            var self = this;
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

            /* PersonalLexicon — record target words */
            if (this._lexicon && success && vocab.length > 0) {
                var lexMode = LEXICON_MODE[data.type] || 'encountered';
                var lexSource = (this._config.destinationId || 'unknown') + ':' + (data.type || 'game');
                this._lexicon.recordBatch(vocab, lexSource, lexMode, this._currentEco || null);
            }

            /* Evidence pipeline */
            if (window.EvidenceEngine && EvidenceEngine._ready) {
                EvidenceEngine.recordEvidence(data, !!success, self._config.destinationId || '');
            }
            /* Adaptivity check */
            if (window.AdaptivityEngine && AdaptivityEngine._ready && window.EvidenceEngine) {
                var _targetIds = EvidenceEngine.getLastRecordedTargets();
                for (var _ti = 0; _ti < _targetIds.length; _ti++) {
                    AdaptivityEngine.evaluate(_targetIds[_ti], !!success);
                }
            }

            this._completed[idx] = true;
            this._yaguaraCounter++;
            this._saveProgress();
            this._updateUI();

            /* Accessibility: announce progress */
            var completedCount = 0;
            for (var ci = 0; ci < this._completed.length; ci++) { if (this._completed[ci]) completedCount++; }
            A11y.announce('Progreso: ' + completedCount + ' de ' + this._games.length + ' encuentros completados');

            /* Yaguará interjection — phase boundaries handle character lines now,
               so mid-phase interjections are Yaguará-only, at reduced frequency */
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

        _onClusterMemberComplete: function(memberIdx, success) {
            /* Record timing + vocab for the member encounter */
            var memberData = this._games[memberIdx];
            var responseTimeMs = this._encounterStartTime ? (Date.now() - this._encounterStartTime) : 0;
            this._timings.push({
                encounterIdx: memberIdx,
                gameType: memberData.type || 'unknown',
                responseTimeMs: responseTimeMs,
                attemptCount: this._encounterAttempts || 1,
                timestamp: new Date().toISOString()
            });
            var vocab = this._extractVocabulary(memberData);
            if (vocab.length > 0) {
                this._vocabularyLog.push({
                    encounterIdx: memberIdx,
                    gameType: memberData.type || 'unknown',
                    words: vocab,
                    success: !!success,
                    timestamp: new Date().toISOString()
                });
            }

            /* PersonalLexicon — record target words for cluster member */
            if (this._lexicon && success && vocab.length > 0) {
                var lexMode = LEXICON_MODE[memberData.type] || 'encountered';
                var lexSource = (this._config.destinationId || 'unknown') + ':' + (memberData.type || 'game');
                this._lexicon.recordBatch(vocab, lexSource, lexMode, this._currentEco || null);
            }

            /* Evidence pipeline for member */
            if (window.EvidenceEngine && EvidenceEngine._ready) {
                EvidenceEngine.recordEvidence(memberData, !!success, this._config.destinationId || '');
            }
            if (window.AdaptivityEngine && AdaptivityEngine._ready && window.EvidenceEngine) {
                var _targetIds = EvidenceEngine.getLastRecordedTargets();
                for (var _ti = 0; _ti < _targetIds.length; _ti++) {
                    AdaptivityEngine.evaluate(_targetIds[_ti], !!success);
                }
            }

            this._completed[memberIdx] = true;
            this._yaguaraCounter++;
            this._currentIdx = memberIdx;
            this._saveProgress();
            this._updateUI();

            /* Auto-advance past the cluster */
            this._maybeAutoAdvance();
        },

        _maybeAutoAdvance: function() {
            var idx = this._currentIdx;
            if (idx === this._games.length - 1) {
                var self = this;
                /* Check for riddle quest before departure */
                if (window.Busqueda && self._config.destNum) {
                    setTimeout(function() {
                        Busqueda.checkAndShow(self._config.destNum, self._container, function() {
                            self._showWorldRestored();
                        });
                    }, 800);
                } else {
                    setTimeout(function() { self._showWorldRestored(); }, 800);
                }
                return;
            }

            var self = this;
            var currentPhase = this._getPhase(idx);
            var nextPhase = this._getPhase(idx + 1);

            /* Phase boundary — breath only on first forward transition of each kind */
            var breathKey = currentPhase + '-' + nextPhase;
            if (!this._phaseBreaths) this._phaseBreaths = {};
            if (currentPhase && nextPhase && nextPhase > currentPhase && !this._phaseBreaths[breathKey]) {
                this._phaseBreaths[breathKey] = true;
                this._showPhaseBreathe(currentPhase, nextPhase, function() {
                    self.nextEncounter();
                });
            } else {
                setTimeout(function() {
                    self.nextEncounter();
                }, 500);
            }
        },

        /* 369: Get phase for a game index */
        _getPhase: function(idx) {
            var game = this._games[idx];
            if (!game) return null;
            /* Explicit phase in JSON overrides auto-assignment */
            if (game.phase) return game.phase;
            return CONFIG.phaseMap[game.type] || 6;
        },

        /* 369: Breath moment between phases — a Steiner "sleep" pause */
        _showPhaseBreathe: function(fromPhase, toPhase, callback) {
            if (window.AudioManager) AudioManager.playPhaseTransition();
            var container = this._container;
            if (!container) { callback(); return; }

            /* Character interjection at phase boundary (if available) */
            var charLines = this._config.characterLines;
            var characters = this._config.characters;
            if (charLines && characters && characters.length) {
                var charId = characters[Math.floor(Math.random() * characters.length)];
                var lines = charLines[charId];
                if (lines && lines.length) {
                    var charMeta = (this._config.characterMeta && this._config.characterMeta[charId]) || {};
                    var line = lines[Math.floor(Math.random() * lines.length)];
                    Yaguara.characterInterject(this._yaguaraPanel, charMeta, line, function() {
                        setTimeout(callback, CONFIG.phaseBreathDuration / 2);
                    });
                    return;
                }
            }

            /* No character available — visual breath */
            var phaseNames = { 3: 'encuentro', 6: 'elaboración', 9: 'integración' };
            var nextName = phaseNames[toPhase] || '';
            var breathEl = document.createElement('div');
            breathEl.className = 'yg-phase-breath';
            breathEl.innerHTML = '<div class="yg-phase-breath-inner">' +
                '<div class="yg-phase-breath-symbol">∿</div>' +
                (nextName ? '<div class="yg-phase-breath-label">' + nextName + '</div>' : '') +
                '</div>';
            breathEl.style.opacity = '0';
            breathEl.style.transition = 'opacity ' + CONFIG.phaseTransitionSpeed + 'ms ease';
            container.innerHTML = '';
            container.appendChild(breathEl);

            setTimeout(function() { breathEl.style.opacity = '1'; }, 50);
            setTimeout(function() {
                breathEl.style.opacity = '0';
                setTimeout(callback, CONFIG.phaseTransitionSpeed);
            }, CONFIG.phaseBreathDuration);
        },

        nextEncounter: function() {
            if (this._currentIdx < this._games.length - 1) {
                var nextIdx = this._currentIdx + 1;
                /* Skip cluster members that were already rendered inline with their leader */
                var nextData = this._games[nextIdx];
                if (nextData && typeof nextData._clusteredBy === 'number' && this._completed[nextIdx]) {
                    nextIdx++;
                    if (nextIdx >= this._games.length) return;
                }
                /* Apply adaptivity recommendation to next game */
                if (window.AdaptivityEngine && AdaptivityEngine._ready) {
                    var rec = AdaptivityEngine.getRecommendation();
                    if (rec && rec.actions && rec.actions.length > 0) {
                        this._applyAdaptivity(rec, nextIdx);
                    }
                }
                this._loadEncounter(nextIdx);
            }
        },

        _applyAdaptivity: function(rec, nextIdx) {
            var nextGame = this._games[nextIdx];
            if (!nextGame) return;
            for (var i = 0; i < rec.actions.length; i++) {
                var a = rec.actions[i];
                if (a.rule === 'scaffold' && a.newInputMode) {
                    nextGame._inputMode = a.newInputMode;
                    nextGame._adaptivityApplied = 'scaffold';
                    console.log('[Adaptivity] Applied scaffold: ' + a.newInputMode + ' to encounter ' + nextIdx);
                } else if (a.rule === 'promote' && a.newInputMode) {
                    nextGame._inputMode = a.newInputMode;
                    nextGame._adaptivityApplied = 'promote';
                    console.log('[Adaptivity] Applied promote: self_correction to encounter ' + nextIdx);
                } else if (a.rule === 'rotate' && a.targetId) {
                    /* v2: search next 5 uncompleted games for one practicing same target,
                       override its inputMode to 'typing' (push toward production) */
                    var rotateApplied = false;
                    var searchEnd = Math.min(nextIdx + 5, this._games.length);
                    for (var r = nextIdx; r < searchEnd; r++) {
                        if (this._completed[r]) continue;
                        var g = this._games[r];
                        if (g && g.linguisticTargetId === a.targetId && g._type !== 'narrative') {
                            g._inputMode = 'typing';
                            g._adaptivityApplied = 'rotate';
                            rotateApplied = true;
                            console.log('[Adaptivity] Applied rotate: typing on encounter ' + r + ' for target ' + a.targetId);
                            break;
                        }
                    }
                    if (!rotateApplied) {
                        if (!this._pendingRevisits) this._pendingRevisits = [];
                        this._pendingRevisits.push({ rule: 'rotate', targetId: a.targetId, bridgeGameType: a.bridgeGameType });
                        console.log('[Adaptivity] Stored rotate recommendation for target ' + a.targetId);
                    }
                } else if (a.rule === 'revisit' && a.stalePrereqId) {
                    /* v2: search next 8 uncompleted games for one practicing stalePrereqId,
                       swap it to nextIdx+1 position, set inputMode to 'choice' (fast review) */
                    var revisitApplied = false;
                    var swapTarget = nextIdx + 1;
                    if (swapTarget < this._games.length) {
                        var revisitEnd = Math.min(swapTarget + 8, this._games.length);
                        for (var v = swapTarget; v < revisitEnd; v++) {
                            if (this._completed[v]) continue;
                            var rg = this._games[v];
                            if (rg && rg.linguisticTargetId === a.stalePrereqId && rg._type !== 'narrative') {
                                /* Swap v with swapTarget — keep _games, _gameStates, _completed in sync */
                                if (v !== swapTarget) {
                                    var tmpGame = this._games[swapTarget];
                                    this._games[swapTarget] = this._games[v];
                                    this._games[v] = tmpGame;

                                    var tmpState = this._gameStates[swapTarget];
                                    this._gameStates[swapTarget] = this._gameStates[v];
                                    this._gameStates[v] = tmpState;

                                    var tmpComp = this._completed[swapTarget];
                                    this._completed[swapTarget] = this._completed[v];
                                    this._completed[v] = tmpComp;
                                }
                                this._games[swapTarget]._inputMode = 'choice';
                                this._games[swapTarget]._adaptivityApplied = 'revisit';
                                revisitApplied = true;
                                console.log('[Adaptivity] Applied revisit: swapped encounter ' + v + ' to ' + swapTarget + ' for prereq ' + a.stalePrereqId);
                                break;
                            }
                        }
                    }
                    if (!revisitApplied) {
                        if (!this._pendingRevisits) this._pendingRevisits = [];
                        this._pendingRevisits.push({ rule: 'revisit', stalePrereqId: a.stalePrereqId });
                        console.log('[Adaptivity] Stored revisit recommendation for prereq ' + a.stalePrereqId);
                    }
                }
            }
        },

        previousEncounter: function() {
            if (this._currentIdx > 0) {
                var prevIdx = this._currentIdx - 1;
                /* If prev is a cluster member, jump to its leader instead */
                var prevData = this._games[prevIdx];
                if (prevData && typeof prevData._clusteredBy === 'number') {
                    prevIdx = prevData._clusteredBy;
                }
                this._loadEncounter(prevIdx);
            }
        },

        setEcosystem: function(eco) {
            this._currentEco = eco || null;
        },

        _showWorldRestored: function() {
            /* Destination complete — play sting */
            if (window.AudioManager && this._config.destNum) {
                AudioManager.playDestinationComplete(this._config.destNum);
            }

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
                /* Departure config: optional per-destination narrative ending */
                var dep = this._config.departure || null;

                if (dep && dep.closingQuestion) {
                    /* --- Destination departure: closing question as title --- */
                    var question = document.createElement('div');
                    question.className = 'yg-completion-question';
                    question.textContent = dep.closingQuestion;
                    completionEl.appendChild(question);

                    if (dep.yaguaraLine) {
                        Yaguara.showLine(this._yaguaraPanel, dep.yaguaraLine);
                    } else {
                        Yaguara.showClosing(this._yaguaraPanel, this._config.world || '_default');
                    }

                    var text = document.createElement('div');
                    text.className = 'yg-completion-text';
                    text.textContent = dep.yaguaraLine || 'Tu voz forma parte de este lugar.';
                    completionEl.appendChild(text);

                    if (dep.nextUrl) {
                        var link = document.createElement('a');
                        link.className = 'yg-completion-link';
                        link.href = dep.nextUrl;
                        link.textContent = dep.button || 'Continuar el viaje \u2192';
                        completionEl.appendChild(link);
                    }

                    /* Practice link */
                    var practiceLink = document.createElement('a');
                    practiceLink.className = 'yg-completion-link';
                    practiceLink.href = 'play.html?mode=practice';
                    practiceLink.textContent = 'Practicar \u2192';
                    practiceLink.style.display = 'block';
                    practiceLink.style.marginTop = '0.75rem';
                    practiceLink.style.opacity = '0.6';
                    practiceLink.style.fontSize = '0.85rem';
                    completionEl.appendChild(practiceLink);
                } else {
                    /* --- Default completion (no departure config) --- */
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

                    /* Practice link */
                    var practiceLink2 = document.createElement('a');
                    practiceLink2.className = 'yg-completion-link';
                    practiceLink2.href = 'play.html?mode=practice';
                    practiceLink2.textContent = 'Practicar \u2192';
                    practiceLink2.style.display = 'block';
                    practiceLink2.style.marginTop = '0.75rem';
                    practiceLink2.style.opacity = '0.6';
                    practiceLink2.style.fontSize = '0.85rem';
                    completionEl.appendChild(practiceLink2);
                }
            }

            /* Hide nav */
            var nav = document.getElementById('yaguaraNav');
            if (nav) nav.style.display = 'none';

            /* Update progress to full */
            Progress.render(this._progressContainer, this._games.length, this._games.length);

            /* Feed vocabulary to SRS practice engine */
            if (window.PracticeEngine && PracticeEngine.autoIngest) {
                PracticeEngine.autoIngest();
            }

            /* Mark destination complete in journey progress */
            if (this._config.destinationId) {
                try {
                    var journey = JSON.parse(localStorage.getItem('yaguara_journey') || '{}');
                    if (!journey.completedDestinations) journey.completedDestinations = [];
                    var destMatch = (this._config.destinationId || '').match(/dest(\d+)/);
                    if (destMatch) {
                        var destNum = parseInt(destMatch[1], 10);
                        if (journey.completedDestinations.indexOf(destNum) === -1) {
                            journey.completedDestinations.push(destNum);
                            journey.completedDestinations.sort(function(a, b) { return a - b; });
                        }
                        journey.currentDestination = Math.max(journey.currentDestination || 1, destNum + 1);
                        if (journey.currentDestination > 58) journey.currentDestination = 58;
                        journey.lastPlayedAt = new Date().toISOString();

                        /* Collect portafolio + autoevaluacion data for this destination */
                        if (!journey.portafolios) journey.portafolios = {};
                        if (!journey.autoevaluaciones) journey.autoevaluaciones = {};
                        var portKey = 'yaguara_portafolio_dest' + destNum;
                        var autoKey = 'yaguara_autoeval_dest' + destNum;
                        var portVal = localStorage.getItem(portKey);
                        var autoVal = localStorage.getItem(autoKey);
                        if (portVal) journey.portafolios['dest' + destNum] = portVal;
                        if (autoVal) {
                            try { journey.autoevaluaciones['dest' + destNum] = JSON.parse(autoVal); } catch(e2) {}
                        }

                        localStorage.setItem('yaguara_journey', JSON.stringify(journey));
                    }
                } catch(e) { /* silent */ }
            }
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
        A11y: A11y,
        CONFIG: CONFIG,
        assertVerbShape: assertVerbShape,
        getForm: getForm,
        getResolver: function() { return Normalizer._resolver; },
        getOntology: function() { return Normalizer._ontology; }
    };

})();
