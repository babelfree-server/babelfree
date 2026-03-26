/**
 * Busqueda — Riddle treasure hunt system for El Viaje del Jaguar
 *
 * Candelaria's cuaderno: 89 riddles woven through the journey.
 * Each solved riddle reveals a bridge segment and brings the rana
 * closer to visibility. At destination 89, the student names the rana.
 *
 * Storage: localStorage key 'yaguara_busqueda'
 * Data:    content/busqueda-riddles.json
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'yaguara_busqueda';
    var RIDDLE_URL  = 'content/busqueda-riddles.json';

    // ── Normalize for answer comparison ──────────────────────────────
    // Local copy so we don't depend on engine load order.
    function _normalize(s) {
        if (!s) return '';
        return s.toLowerCase()
            .replace(/[áà]/g, 'a').replace(/[éè]/g, 'e')
            .replace(/[íì]/g, 'i').replace(/[óò]/g, 'o')
            .replace(/[úù]/g, 'u')
            .replace(/[^a-zñ0-9\s]/g, '').trim().replace(/\s+/g, ' ');
    }

    // ── Default empty state ──────────────────────────────────────────
    function _defaultState() {
        return {
            solvedRiddles:  [],
            bridgeSegments: 0,
            ranaOpacity:    0.0,
            ranaName:       null,
            journalEntries: [],
            version:        1
        };
    }

    // ── SVG helpers ──────────────────────────────────────────────────
    var RANA_PATH = 'M50,15 C55,5 65,2 70,8 C75,2 85,5 90,15 ' +
        'C95,20 95,28 90,33 L92,45 C92,52 85,58 80,60 ' +
        'L85,75 C85,82 78,85 73,82 L70,72 L68,82 C65,88 55,88 52,82 ' +
        'L50,72 L48,82 C45,88 35,88 32,82 L30,72 L27,82 C22,85 15,82 ' +
        'L15,75 L20,60 C15,58 8,52 8,45 L10,33 C5,28 5,20 10,15 ' +
        'C15,5 25,2 30,8 C35,2 45,5 50,15 Z';

    function _createRanaSVG(opacity, size) {
        var w = size || 80;
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 100 90');
        svg.setAttribute('width', String(w));
        svg.setAttribute('height', String(Math.round(w * 0.9)));
        svg.setAttribute('class', 'yg-busqueda-rana');
        svg.style.opacity = String(opacity);
        svg.style.transition = 'opacity 1.2s ease';

        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', RANA_PATH);
        path.setAttribute('fill', opacity >= 0.6 ? '#4CAF50' : '#2e7d32');
        path.setAttribute('stroke', '#1b5e20');
        path.setAttribute('stroke-width', '1.5');
        svg.appendChild(path);

        // Eyes appear at 0.2+
        if (opacity >= 0.2) {
            var eyeL = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            eyeL.setAttribute('cx', '35'); eyeL.setAttribute('cy', '18');
            eyeL.setAttribute('r', '4'); eyeL.setAttribute('fill', '#FFD700');
            svg.appendChild(eyeL);
            var pupilL = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            pupilL.setAttribute('cx', '36'); pupilL.setAttribute('cy', '18');
            pupilL.setAttribute('r', '2'); pupilL.setAttribute('fill', '#000');
            svg.appendChild(pupilL);

            var eyeR = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            eyeR.setAttribute('cx', '65'); eyeR.setAttribute('cy', '18');
            eyeR.setAttribute('r', '4'); eyeR.setAttribute('fill', '#FFD700');
            svg.appendChild(eyeR);
            var pupilR = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            pupilR.setAttribute('cx', '66'); pupilR.setAttribute('cy', '18');
            pupilR.setAttribute('r', '2'); pupilR.setAttribute('fill', '#000');
            svg.appendChild(pupilR);
        }

        return svg;
    }

    // ── CSS injection (once) ─────────────────────────────────────────
    var _cssInjected = false;
    function _injectCSS() {
        if (_cssInjected) return;
        _cssInjected = true;
        var style = document.createElement('style');
        style.textContent =
            '.yg-busqueda-card{' +
                'background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);' +
                'border:1px solid #d4a843;border-radius:12px;padding:2rem;' +
                'max-width:560px;margin:1.5rem auto;color:#e8d5b7;' +
                'font-family:Georgia,serif;position:relative;overflow:hidden;' +
            '}' +
            '.yg-busqueda-card::before{' +
                'content:"";position:absolute;top:0;left:0;right:0;bottom:0;' +
                'background:radial-gradient(circle at 50% 0%,rgba(212,168,67,0.08) 0%,transparent 70%);' +
                'pointer-events:none;' +
            '}' +
            '.yg-busqueda-intro{' +
                'font-style:italic;color:#d4a843;margin-bottom:1.2rem;' +
                'font-size:0.95rem;text-align:center;' +
            '}' +
            '.yg-busqueda-speaker{' +
                'font-weight:bold;color:#d4a843;margin-bottom:0.8rem;' +
                'font-size:1.05rem;text-align:center;' +
            '}' +
            '.yg-busqueda-verse{margin:1.2rem 0;text-align:center;}' +
            '.yg-busqueda-line{' +
                'opacity:0;transform:translateY(8px);' +
                'animation:busquedaLineIn 0.6s ease forwards;' +
                'margin:0.4rem 0;font-size:1.05rem;line-height:1.6;' +
            '}' +
            '@keyframes busquedaLineIn{' +
                'to{opacity:1;transform:translateY(0);}' +
            '}' +
            '.yg-busqueda-options{' +
                'display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;' +
                'margin-top:1.5rem;' +
            '}' +
            '.yg-busqueda-option{' +
                'background:rgba(212,168,67,0.1);border:1px solid #d4a843;' +
                'color:#e8d5b7;padding:0.7rem 1rem;border-radius:8px;' +
                'cursor:pointer;font-size:1rem;font-family:Georgia,serif;' +
                'transition:all 0.25s ease;' +
            '}' +
            '.yg-busqueda-option:hover{' +
                'background:rgba(212,168,67,0.25);transform:scale(1.03);' +
            '}' +
            '.yg-busqueda-option.correct{' +
                'background:rgba(76,175,80,0.3);border-color:#4CAF50;' +
                'animation:busquedaPulse 0.6s ease;' +
            '}' +
            '.yg-busqueda-option.incorrect{' +
                'animation:busquedaShake 0.5s ease;' +
            '}' +
            '@keyframes busquedaPulse{' +
                '0%,100%{transform:scale(1);}50%{transform:scale(1.06);}' +
            '}' +
            '@keyframes busquedaShake{' +
                '0%,100%{transform:translateX(0);}' +
                '20%,60%{transform:translateX(-6px);}' +
                '40%,80%{transform:translateX(6px);}' +
            '}' +
            '.yg-busqueda-input-row{' +
                'display:flex;gap:0.75rem;margin-top:1.5rem;align-items:center;' +
                'flex-wrap:wrap;justify-content:center;' +
            '}' +
            '.yg-busqueda-input{' +
                'flex:1;min-width:180px;background:rgba(255,255,255,0.06);' +
                'border:1px solid #d4a843;color:#e8d5b7;padding:0.7rem 1rem;' +
                'border-radius:8px;font-size:1rem;font-family:Georgia,serif;' +
                'outline:none;' +
            '}' +
            '.yg-busqueda-input:focus{border-color:#FFD700;box-shadow:0 0 6px rgba(255,215,0,0.3);}' +
            '.yg-busqueda-btn{' +
                'background:linear-gradient(135deg,#d4a843,#b8860b);' +
                'color:#1a1a2e;border:none;padding:0.7rem 1.4rem;' +
                'border-radius:8px;cursor:pointer;font-weight:bold;' +
                'font-size:0.95rem;font-family:Georgia,serif;' +
                'transition:all 0.25s ease;' +
            '}' +
            '.yg-busqueda-btn:hover{transform:scale(1.04);box-shadow:0 2px 8px rgba(212,168,67,0.4);}' +
            '.yg-busqueda-btn-hint{' +
                'background:transparent;border:1px solid rgba(212,168,67,0.4);' +
                'color:#d4a843;padding:0.5rem 1rem;border-radius:8px;' +
                'cursor:pointer;font-size:0.85rem;font-family:Georgia,serif;' +
                'transition:all 0.25s ease;' +
            '}' +
            '.yg-busqueda-hint{' +
                'color:#d4a843;font-style:italic;margin-top:0.8rem;' +
                'font-size:0.9rem;text-align:center;opacity:0;' +
                'animation:busquedaLineIn 0.4s ease forwards;' +
            '}' +
            '.yg-busqueda-feedback{' +
                'text-align:center;margin-top:1rem;font-size:0.95rem;' +
            '}' +
            '.yg-busqueda-feedback.correct{color:#4CAF50;}' +
            '.yg-busqueda-feedback.incorrect{color:#e57373;}' +
            '.yg-busqueda-bridge-mini{' +
                'margin:1.2rem auto;display:block;' +
            '}' +
            '.yg-busqueda-success{' +
                'text-align:center;margin-top:1.5rem;' +
                'animation:busquedaLineIn 0.8s ease forwards;' +
            '}' +
            '.yg-busqueda-success p{margin:0.5rem 0;}' +
            '.yg-busqueda-candelaria-note{' +
                'font-style:italic;color:#d4a843;font-size:0.9rem;' +
                'margin-top:0.8rem;text-align:center;' +
            '}' +
            '.yg-busqueda-create{' +
                'margin-top:1.5rem;text-align:center;' +
                'animation:busquedaLineIn 0.6s ease forwards;' +
                'animation-delay:0.8s;opacity:0;' +
            '}' +
            '.yg-busqueda-create textarea{' +
                'width:100%;min-height:80px;background:rgba(255,255,255,0.06);' +
                'border:1px solid #d4a843;color:#e8d5b7;padding:0.8rem;' +
                'border-radius:8px;font-family:Georgia,serif;font-size:0.95rem;' +
                'resize:vertical;margin-top:0.6rem;' +
            '}' +
            '.yg-busqueda-create label{' +
                'color:#d4a843;font-size:0.9rem;' +
            '}' +
            '.yg-busqueda-naming{text-align:center;padding:1.5rem 0;}' +
            '.yg-busqueda-naming input{' +
                'background:rgba(255,255,255,0.06);border:1px solid #FFD700;' +
                'color:#FFD700;padding:0.8rem 1.2rem;border-radius:8px;' +
                'font-size:1.2rem;font-family:Georgia,serif;text-align:center;' +
                'outline:none;margin:1rem 0;display:block;width:80%;' +
                'margin-left:auto;margin-right:auto;' +
            '}';
        document.head.appendChild(style);
    }

    // ── Helper: create element with class and optional text ──────────
    function _el(tag, className, text) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (text) el.textContent = text;
        return el;
    }

    // ═════════════════════════════════════════════════════════════════
    // Busqueda object
    // ═════════════════════════════════════════════════════════════════
    var Busqueda = {
        _cache: null,
        _state: null,

        // ── Load riddle data (cached after first fetch) ──────────────
        load: function (callback) {
            if (this._cache) {
                callback(null, this._cache);
                return;
            }
            var self = this;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', RIDDLE_URL, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        self._cache = JSON.parse(xhr.responseText);
                        callback(null, self._cache);
                    } catch (e) {
                        callback('Error parsing busqueda riddles: ' + e.message);
                    }
                } else {
                    callback('Error loading busqueda riddles: HTTP ' + xhr.status);
                }
            };
            xhr.send();
        },

        // ── Read / initialize state from localStorage ────────────────
        getState: function () {
            if (this._state) return this._state;
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                if (raw) {
                    this._state = JSON.parse(raw);
                } else {
                    this._state = _defaultState();
                }
            } catch (e) {
                this._state = _defaultState();
            }
            return this._state;
        },

        // ── Persist state ────────────────────────────────────────────
        _saveState: function () {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this._state));
            } catch (e) {
                // Storage full or unavailable — silently ignore
            }
            // Push to server (fire-and-forget)
            if (window.JaguarAPI && JaguarAPI.syncBusquedaProgress) {
                JaguarAPI.syncBusquedaProgress(this._state);
            }
        },

        // ── Pull server state on first load (cross-device) ────────────
        pullServerState: function (callback) {
            if (!window.JaguarAPI || !JaguarAPI.getBusquedaProgress) {
                if (callback) callback();
                return;
            }
            var self = this;
            JaguarAPI.getBusquedaProgress().then(function (remote) {
                if (!remote) { if (callback) callback(); return; }
                var local = self.getState();
                // Merge: keep whichever has more solved riddles
                var remoteSolved = remote.solved_riddles || [];
                if (remoteSolved.length > local.solvedRiddles.length) {
                    local.solvedRiddles  = remoteSolved;
                    local.bridgeSegments = remote.bridge_segments || remoteSolved.length;
                    local.ranaOpacity    = remote.rana_opacity || 0;
                    local.ranaName       = remote.rana_name || local.ranaName;
                    // Merge journal entries — keep the longer list
                    var remoteJournal = remote.journal_entries || [];
                    if (remoteJournal.length > local.journalEntries.length) {
                        local.journalEntries = remoteJournal;
                    }
                    self._state = local;
                    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(local)); }
                    catch (e) {}
                }
                if (callback) callback();
            }).catch(function () {
                if (callback) callback();
            });
        },

        // ── Main entry: check destination for unsolved riddle ────────
        checkAndShow: function (destNum, container, onDone) {
            _injectCSS();
            var self = this;
            var state = this.getState();

            // Already solved?
            if (state.solvedRiddles.indexOf(destNum) !== -1) {
                if (onDone) onDone({ alreadySolved: true, destNum: destNum });
                return;
            }

            this.load(function (err, data) {
                if (err) {
                    console.warn('[Busqueda]', err);
                    if (onDone) onDone({ error: err });
                    return;
                }

                // Find riddle for this destination
                var riddle = null;
                var riddles = data.riddles || data;
                for (var i = 0; i < riddles.length; i++) {
                    if (riddles[i].dest === destNum || riddles[i].destNum === destNum) {
                        riddle = riddles[i];
                        break;
                    }
                }

                if (!riddle) {
                    if (onDone) onDone({ noRiddle: true, destNum: destNum });
                    return;
                }

                if (window.AudioManager) AudioManager.playRiddleAppears();
                self._renderRiddle(riddle, container, function () {
                    if (onDone) onDone({ solved: true, destNum: destNum });
                });
            });
        },

        // ── Render the riddle encounter ──────────────────────────────
        _renderRiddle: function (riddle, container, onSolved) {
            var self = this;
            container.innerHTML = '';

            var card = _el('div', 'yg-busqueda-card');

            // Intro
            var intro = _el('p', 'yg-busqueda-intro',
                'El cuaderno de Candelaria brilla...');
            card.appendChild(intro);

            // Speaker
            var speaker = _el('p', 'yg-busqueda-speaker',
                '\u00abViajero... escucha esto.\u00bb');
            card.appendChild(speaker);

            // Verse lines with staggered animation
            var verseWrap = _el('div', 'yg-busqueda-verse');
            var lines = riddle.verse || [];
            if (typeof lines === 'string') lines = lines.split('\n');

            for (var i = 0; i < lines.length; i++) {
                var line = _el('p', 'yg-busqueda-line', lines[i]);
                line.style.animationDelay = (i * 0.5) + 's';
                verseWrap.appendChild(line);
            }
            card.appendChild(verseWrap);

            // Input area — after all lines have animated in
            var inputDelay = lines.length * 500 + 400;
            var mode = riddle.inputMode || 'choice';

            setTimeout(function () {
                self._renderInput(mode, riddle, card, onSolved);
            }, inputDelay);

            container.appendChild(card);
        },

        // ── Render input controls based on mode ──────────────────────
        _renderInput: function (mode, riddle, card, onSolved) {
            var self = this;

            if (mode === 'choice') {
                self._renderChoiceInput(riddle, card, onSolved);
            } else if (mode === 'typed') {
                self._renderTypedInput(riddle, card, false, false, onSolved);
            } else if (mode === 'typed_hint') {
                self._renderTypedInput(riddle, card, true, false, onSolved);
            } else if (mode === 'typed_create') {
                self._renderTypedInput(riddle, card, false, true, onSolved);
            } else {
                // Fallback to choice
                self._renderChoiceInput(riddle, card, onSolved);
            }
        },

        // ── Choice mode: 4 option buttons ────────────────────────────
        _renderChoiceInput: function (riddle, card, onSolved) {
            var self = this;
            var options = riddle.answerOptions || riddle.options || [];
            var answer = riddle.answer || '';
            var grid = _el('div', 'yg-busqueda-options');

            for (var i = 0; i < options.length; i++) {
                (function (opt) {
                    var btn = _el('button', 'yg-busqueda-option', opt);
                    btn.addEventListener('click', function () {
                        var isCorrect = _normalize(opt) === _normalize(answer);
                        if (isCorrect) {
                            btn.classList.add('correct');
                            // Disable all buttons
                            var allBtns = grid.querySelectorAll('.yg-busqueda-option');
                            for (var j = 0; j < allBtns.length; j++) {
                                allBtns[j].disabled = true;
                                allBtns[j].style.pointerEvents = 'none';
                            }
                            self._onSolved(riddle, opt);
                            self._showSuccess(riddle, card, onSolved);
                        } else {
                            btn.classList.add('incorrect');
                            setTimeout(function () {
                                btn.classList.remove('incorrect');
                            }, 600);
                            self._showFeedback(card, false);
                        }
                    });
                    grid.appendChild(btn);
                })(options[i]);
            }

            card.appendChild(grid);
        },

        // ── Typed mode: text input + optional hint/create ────────────
        _renderTypedInput: function (riddle, card, showHint, showCreate, onSolved) {
            var self = this;
            var answer = riddle.answer || '';
            var row = _el('div', 'yg-busqueda-input-row');

            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'yg-busqueda-input';
            input.placeholder = 'Tu respuesta...';
            input.autocomplete = 'off';
            input.spellcheck = false;
            row.appendChild(input);

            var btnListo = _el('button', 'yg-busqueda-btn', 'Listo');
            row.appendChild(btnListo);

            if (showHint && riddle.hint) {
                var btnHint = _el('button', 'yg-busqueda-btn-hint', 'Pista');
                btnHint.addEventListener('click', function () {
                    // Only add hint once
                    if (!card.querySelector('.yg-busqueda-hint')) {
                        var hintEl = _el('p', 'yg-busqueda-hint', riddle.hint);
                        card.appendChild(hintEl);
                    }
                    btnHint.disabled = true;
                    btnHint.style.opacity = '0.4';
                });
                row.appendChild(btnHint);
            }

            card.appendChild(row);

            function checkAnswer() {
                var val = input.value.trim();
                if (!val) return;

                // Support multiple accepted answers separated by |
                var accepted = answer.split('|');
                var isCorrect = false;
                for (var k = 0; k < accepted.length; k++) {
                    if (_normalize(val) === _normalize(accepted[k])) {
                        isCorrect = true;
                        break;
                    }
                }

                if (isCorrect) {
                    input.disabled = true;
                    btnListo.disabled = true;
                    input.style.borderColor = '#4CAF50';
                    self._onSolved(riddle, val);
                    self._showSuccess(riddle, card, function () {
                        if (showCreate) {
                            self._showCreatePrompt(riddle, card, onSolved);
                        } else if (onSolved) {
                            onSolved();
                        }
                    });
                } else {
                    input.style.borderColor = '#e57373';
                    input.classList.add('incorrect');
                    self._showFeedback(card, false);
                    setTimeout(function () {
                        input.style.borderColor = '#d4a843';
                        input.classList.remove('incorrect');
                    }, 600);
                }
            }

            btnListo.addEventListener('click', checkAnswer);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) checkAnswer();
            });

            // Focus with slight delay
            setTimeout(function () { input.focus(); }, 100);
        },

        // ── Show feedback text ───────────────────────────────────────
        _showFeedback: function (card, correct) {
            // Remove old feedback
            var old = card.querySelector('.yg-busqueda-feedback');
            if (old) old.parentNode.removeChild(old);

            var fb = _el('p', 'yg-busqueda-feedback ' + (correct ? 'correct' : 'incorrect'),
                correct ? '' : 'No es esa... intenta de nuevo.');
            card.appendChild(fb);

            if (!correct) {
                setTimeout(function () {
                    if (fb.parentNode) fb.parentNode.removeChild(fb);
                }, 2500);
            }
        },

        // ── Show success: bridge + rana animation ────────────────────
        _showSuccess: function (riddle, card, callback) {
            var self = this;
            var state = this.getState();

            // Remove feedback
            var old = card.querySelector('.yg-busqueda-feedback');
            if (old) old.parentNode.removeChild(old);

            var wrap = _el('div', 'yg-busqueda-success');

            // Candelaria's response
            var note = _el('p', 'yg-busqueda-candelaria-note',
                riddle.candelariaNote || '\u00abBien... el puente recuerda.\u00bb');
            wrap.appendChild(note);

            // Bridge segment animation
            self._animateBridge(state.bridgeSegments, wrap);

            // Rana animation
            self._animateRana(state.ranaOpacity, wrap);

            card.appendChild(wrap);

            // Delay before calling back — chain naming ceremony if all 89 solved
            setTimeout(function () {
                if (self.isComplete() && !state.ranaName) {
                    // All riddles solved — trigger the naming ceremony
                    var container = card.parentNode || card;
                    self.namingCeremony(container, function () {
                        if (callback) callback();
                    });
                } else {
                    if (callback) callback();
                }
            }, 2200);
        },

        // ── Show create-your-own prompt (typed_create mode) ──────────
        _showCreatePrompt: function (riddle, card, onSolved) {
            var createWrap = _el('div', 'yg-busqueda-create');
            var label = _el('label', '', 'Ahora escribe tu propia adivinanza:');
            createWrap.appendChild(label);

            var textarea = document.createElement('textarea');
            textarea.placeholder = 'Mi adivinanza...';
            createWrap.appendChild(textarea);

            var btnGuardar = _el('button', 'yg-busqueda-btn', 'Guardar');
            btnGuardar.style.marginTop = '0.6rem';
            btnGuardar.addEventListener('click', function () {
                var text = textarea.value.trim();
                if (text) {
                    // Store in journal entry
                    var state = Busqueda.getState();
                    var destNum = riddle.dest || riddle.destNum;
                    for (var i = 0; i < state.journalEntries.length; i++) {
                        if (state.journalEntries[i].dest === destNum) {
                            state.journalEntries[i].studentRiddle = text;
                            break;
                        }
                    }
                    Busqueda._saveState();

                    // Feed to PersonalLexicon if available
                    if (window.PersonalLexicon && window.PersonalLexicon.harvestCreativeText) {
                        window.PersonalLexicon.harvestCreativeText(text, 'busqueda_create');
                    }

                    textarea.disabled = true;
                    btnGuardar.disabled = true;
                    btnGuardar.textContent = 'Guardado';
                }
                if (onSolved) onSolved();
            });
            createWrap.appendChild(btnGuardar);

            card.appendChild(createWrap);
        },

        // ── Update state after solving ───────────────────────────────
        _onSolved: function (riddle, answer) {
            var state = this.getState();
            var destNum = riddle.dest || riddle.destNum;

            // Add to solved list (guard duplicates)
            if (state.solvedRiddles.indexOf(destNum) === -1) {
                state.solvedRiddles.push(destNum);
            }

            // Increment bridge
            state.bridgeSegments = state.solvedRiddles.length;

            // Update rana opacity (linear 0→1 over 89 riddles)
            state.ranaOpacity = Math.min(1.0,
                Math.round((state.solvedRiddles.length / 89) * 100) / 100);

            // Journal entry — use curated journalEntry from riddle data when available
            state.journalEntries.push({
                dest:           destNum,
                destName:       riddle.destName || riddle.title || ('Destino ' + destNum),
                verse:          riddle.verse,
                answer:         answer,
                journalText:    riddle.journalEntry || '',
                candelariaNote: riddle.candelariaNote || '',
                solvedAt:       new Date().toISOString(),
                studentRiddle:  null
            });

            // Feed vocabulary to PersonalLexicon
            if (window.PersonalLexicon && riddle.vocabulary) {
                for (var i = 0; i < riddle.vocabulary.length; i++) {
                    var word = riddle.vocabulary[i];
                    if (typeof word === 'string') {
                        window.PersonalLexicon.encounter(word, 'busqueda');
                    } else if (word && word.word) {
                        window.PersonalLexicon.encounter(word.word, 'busqueda');
                    }
                }
            }

            this._saveState();

            // Feed to CompositionBuilder — "Mi aventura por Colombia"
            if (window.CompositionBuilder) {
                CompositionBuilder.recordRiddleSolve(destNum, answer);
            }

            // Audio: bridge plank + rana shimmer
            if (window.AudioManager) {
                AudioManager.playBridgePlank();
                setTimeout(function () { AudioManager.playRanaShimmer(); }, 600);
            }
        },

        // ── Animate a bridge segment reveal ──────────────────────────
        _animateBridge: function (segment, container) {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 300 40');
            svg.setAttribute('width', '280');
            svg.setAttribute('height', '40');
            svg.setAttribute('class', 'yg-busqueda-bridge-mini');

            // Rope lines (top and bottom)
            var ropeTop = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            ropeTop.setAttribute('x1', '10'); ropeTop.setAttribute('y1', '8');
            ropeTop.setAttribute('x2', '290'); ropeTop.setAttribute('y2', '8');
            ropeTop.setAttribute('stroke', '#8B4513'); ropeTop.setAttribute('stroke-width', '2');
            svg.appendChild(ropeTop);

            var ropeBot = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            ropeBot.setAttribute('x1', '10'); ropeBot.setAttribute('y1', '32');
            ropeBot.setAttribute('x2', '290'); ropeBot.setAttribute('y2', '32');
            ropeBot.setAttribute('stroke', '#8B4513'); ropeBot.setAttribute('stroke-width', '2');
            svg.appendChild(ropeBot);

            // Planks — one per segment, max 58 visible in the mini view
            var plankWidth = 280 / 89;
            for (var i = 0; i < Math.min(segment, 89); i++) {
                var plank = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                var x = 10 + i * plankWidth;
                plank.setAttribute('x', String(x));
                plank.setAttribute('y', '10');
                plank.setAttribute('width', String(Math.max(plankWidth - 1, 2)));
                plank.setAttribute('height', '20');
                plank.setAttribute('rx', '1');

                if (i === segment - 1) {
                    // Newest plank glows gold
                    plank.setAttribute('fill', '#FFD700');
                    plank.setAttribute('opacity', '0');
                    var anim = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
                    anim.setAttribute('attributeName', 'opacity');
                    anim.setAttribute('from', '0'); anim.setAttribute('to', '1');
                    anim.setAttribute('dur', '0.8s'); anim.setAttribute('fill', 'freeze');
                    plank.appendChild(anim);
                } else {
                    plank.setAttribute('fill', '#d4a843');
                    plank.setAttribute('opacity', '0.7');
                }

                svg.appendChild(plank);
            }

            container.appendChild(svg);
        },

        // ── Animate rana visibility change ────────────────────────────
        _animateRana: function (newOpacity, container) {
            var wrap = _el('div', '');
            wrap.style.textAlign = 'center';
            wrap.style.marginTop = '0.8rem';

            var rana = _createRanaSVG(newOpacity, 60);
            // Start slightly less visible, then transition to new opacity
            rana.style.opacity = String(Math.max(0, newOpacity - 0.04));
            wrap.appendChild(rana);

            container.appendChild(wrap);

            // Flicker animation
            setTimeout(function () {
                rana.style.opacity = String(newOpacity);
            }, 200);
        },

        // ── Get journal entries ──────────────────────────────────────
        getJournalEntries: function () {
            return this.getState().journalEntries;
        },

        // ── Get bridge progress (0-89) ───────────────────────────────
        getBridgeProgress: function () {
            return this.getState().bridgeSegments;
        },

        // ── Get rana opacity (0.0-1.0) ───────────────────────────────
        getRanaOpacity: function () {
            return this.getState().ranaOpacity;
        },

        // ── Check if all 89 riddles solved ───────────────────────────
        isComplete: function () {
            return this.getState().solvedRiddles.length >= 89;
        },

        // ── The naming ceremony — dest 89 when all solved ────────────
        namingCeremony: function (container, onDone) {
            _injectCSS();
            var self = this;
            var state = this.getState();

            if (state.ranaName) {
                // Already named
                if (onDone) onDone({ name: state.ranaName, alreadyNamed: true });
                return;
            }

            container.innerHTML = '';
            var card = _el('div', 'yg-busqueda-card');

            var naming = _el('div', 'yg-busqueda-naming');

            var p1 = _el('p', '', 'Has cruzado el puente entero.');
            p1.style.fontSize = '1.1rem';
            p1.style.marginBottom = '0.5rem';
            naming.appendChild(p1);

            var p2 = _el('p', 'yg-busqueda-candelaria-note',
                '\u00abLa rana ha esperado mucho tiempo. Ahora puede escuchar su nombre.\u00bb');
            naming.appendChild(p2);

            // Show full rana
            var rana = _createRanaSVG(1.0, 100);
            rana.style.margin = '1rem auto';
            rana.style.display = 'block';
            naming.appendChild(rana);

            var p3 = _el('p', '', '\u00bfC\u00f3mo se llama?');
            p3.style.color = '#d4a843';
            p3.style.marginTop = '1rem';
            naming.appendChild(p3);

            var nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.placeholder = 'El nombre de tu rana...';
            nameInput.maxLength = 30;
            naming.appendChild(nameInput);

            var btnName = _el('button', 'yg-busqueda-btn', 'Nombrar');
            btnName.style.marginTop = '0.5rem';
            naming.appendChild(btnName);

            card.appendChild(naming);
            container.appendChild(card);

            function doName() {
                var name = nameInput.value.trim();
                if (!name) return;

                state.ranaName = name;
                self._saveState();

                // Clear and show celebration
                naming.innerHTML = '';

                var celebRana = _createRanaSVG(1.0, 120);
                celebRana.style.margin = '1rem auto';
                celebRana.style.display = 'block';
                naming.appendChild(celebRana);

                var congrats = _el('p', '',
                    name + ' canta por primera vez.');
                congrats.style.fontSize = '1.2rem';
                congrats.style.color = '#FFD700';
                congrats.style.marginTop = '1rem';
                naming.appendChild(congrats);

                var note = _el('p', 'yg-busqueda-candelaria-note',
                    '\u00abEl puente est\u00e1 completo. ' + name +
                    ' guardar\u00e1 tus palabras para siempre.\u00bb');
                naming.appendChild(note);

                setTimeout(function () {
                    if (onDone) onDone({ name: name });
                }, 3000);
            }

            btnName.addEventListener('click', doName);
            nameInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) doName();
            });

            setTimeout(function () { nameInput.focus(); }, 300);
        }
    };

    window.Busqueda = Busqueda;

    // Auto-pull server state on load for cross-device sync
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            Busqueda.pullServerState();
        });
    } else {
        Busqueda.pullServerState();
    }
})();
