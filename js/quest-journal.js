/**
 * Journal — Quest journal page for El Viaje del Jaguar
 *
 * Renders the bridge progress, rana visibility, and chronological
 * journal entries from the Busqueda riddle treasure hunt.
 *
 * Used by cuaderno.html.  Depends on riddle-quest.js (Busqueda).
 * Storage: localStorage key 'yaguara_busqueda'
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'yaguara_busqueda';
    var TOTAL_DESTS = 58;

    // ── SVG namespace ────────────────────────────────────────────────
    var NS = 'http://www.w3.org/2000/svg';

    // ── Rana SVG path ────────────────────────────────────────────────
    var RANA_PATH = 'M50,15 C55,5 65,2 70,8 C75,2 85,5 90,15 ' +
        'C95,20 95,28 90,33 L92,45 C92,52 85,58 80,60 ' +
        'L85,75 C85,82 78,85 73,82 L70,72 L68,82 C65,88 55,88 52,82 ' +
        'L50,72 L48,82 C45,88 35,88 32,82 L30,72 L27,82 C22,85 15,82 ' +
        'L15,75 L20,60 C15,58 8,52 8,45 L10,33 C5,28 5,20 10,15 ' +
        'C15,5 25,2 30,8 C35,2 45,5 50,15 Z';

    // ── Read state directly (no dependency on Busqueda loading) ──────
    function _readState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) { /* ignore */ }
        return {
            solvedRiddles: [],
            bridgeSegments: 0,
            ranaOpacity: 0.0,
            ranaName: null,
            journalEntries: [],
            version: 1
        };
    }

    // ── Helper: create element ───────────────────────────────────────
    function _el(tag, className, text) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (text) el.textContent = text;
        return el;
    }

    // ── Helper: create SVG element ───────────────────────────────────
    function _svg(tag, attrs) {
        var el = document.createElementNS(NS, tag);
        if (attrs) {
            for (var k in attrs) {
                if (attrs.hasOwnProperty(k)) {
                    el.setAttribute(k, attrs[k]);
                }
            }
        }
        return el;
    }

    // ── CSS injection ────────────────────────────────────────────────
    var _cssInjected = false;
    function _injectCSS() {
        if (_cssInjected) return;
        _cssInjected = true;
        var style = document.createElement('style');
        style.textContent =
            '.yg-journal{max-width:720px;margin:0 auto;padding:1.5rem;color:#e8d5b7;font-family:Georgia,serif;}' +
            '.yg-journal h2{color:#d4a843;text-align:center;margin-bottom:0.5rem;font-size:1.4rem;}' +
            '.yg-journal-subtitle{text-align:center;color:#a89070;font-style:italic;margin-bottom:2rem;font-size:0.95rem;}' +

            /* Bridge section */
            '.yg-journal-bridge-section{' +
                'background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);' +
                'border:1px solid #d4a843;border-radius:12px;padding:1.5rem;' +
                'margin-bottom:1.5rem;text-align:center;' +
            '}' +
            '.yg-journal-bridge-label{color:#d4a843;font-size:0.9rem;margin-bottom:0.6rem;}' +
            '.yg-journal-bridge-svg{display:block;margin:0 auto;overflow:visible;}' +
            '.yg-journal-bridge-count{color:#a89070;font-size:0.85rem;margin-top:0.6rem;}' +

            /* Rana section */
            '.yg-journal-rana-section{' +
                'background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);' +
                'border:1px solid #d4a843;border-radius:12px;padding:1.5rem;' +
                'margin-bottom:1.5rem;text-align:center;' +
            '}' +
            '.yg-journal-rana-label{color:#d4a843;font-size:0.9rem;margin-bottom:0.6rem;}' +
            '.yg-journal-rana-svg{display:block;margin:0.8rem auto;}' +
            '.yg-journal-rana-status{color:#a89070;font-size:0.85rem;font-style:italic;margin-top:0.4rem;}' +
            '.yg-journal-rana-name{color:#FFD700;font-size:1.1rem;margin-top:0.5rem;font-weight:bold;}' +

            /* Entries section */
            '.yg-journal-entries-section{margin-top:1.5rem;}' +
            '.yg-journal-entries-title{color:#d4a843;font-size:1.1rem;margin-bottom:1rem;border-bottom:1px solid rgba(212,168,67,0.3);padding-bottom:0.4rem;}' +
            '.yg-journal-entry{' +
                'background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);' +
                'border:1px solid rgba(212,168,67,0.3);border-radius:10px;' +
                'padding:1.2rem;margin-bottom:0.8rem;position:relative;' +
            '}' +
            '.yg-journal-entry.solved{border-color:#d4a843;}' +
            '.yg-journal-entry.unsolved{opacity:0.5;}' +
            '.yg-journal-entry-header{' +
                'display:flex;justify-content:space-between;align-items:center;' +
                'margin-bottom:0.6rem;' +
            '}' +
            '.yg-journal-entry-dest{color:#d4a843;font-weight:bold;font-size:1rem;}' +
            '.yg-journal-entry-date{color:#a89070;font-size:0.8rem;}' +
            '.yg-journal-entry-verse{' +
                'font-style:italic;color:#c8b89a;margin:0.6rem 0;' +
                'padding-left:1rem;border-left:2px solid rgba(212,168,67,0.3);' +
                'font-size:0.95rem;line-height:1.5;' +
            '}' +
            '.yg-journal-entry-answer{' +
                'color:#4CAF50;font-weight:bold;margin:0.4rem 0;font-size:0.95rem;' +
            '}' +
            '.yg-journal-entry-note{' +
                'color:#d4a843;font-style:italic;font-size:0.9rem;margin-top:0.5rem;' +
            '}' +
            '.yg-journal-entry-student{' +
                'background:rgba(255,255,255,0.04);border-radius:6px;' +
                'padding:0.6rem;margin-top:0.6rem;color:#c8b89a;font-size:0.9rem;' +
            '}' +
            '.yg-journal-entry-student-label{color:#a89070;font-size:0.8rem;margin-bottom:0.3rem;}' +
            '.yg-journal-blank{text-align:center;color:#555;font-size:1.5rem;letter-spacing:0.3rem;padding:0.5rem 0;}';
        document.head.appendChild(style);
    }

    // ═════════════════════════════════════════════════════════════════
    // Journal object
    // ═════════════════════════════════════════════════════════════════
    var Journal = {

        // ── Initialize ───────────────────────────────────────────────
        init: function () {
            _injectCSS();
            var state = _readState();

            var root = document.getElementById('journal-root');
            if (!root) {
                root = document.querySelector('.yg-journal');
            }
            if (!root) {
                root = _el('div', 'yg-journal');
                document.body.appendChild(root);
            }
            root.className = 'yg-journal';
            root.innerHTML = '';

            // Title
            var title = _el('h2', '', 'Cuaderno de Candelaria');
            root.appendChild(title);

            var subtitle = _el('p', 'yg-journal-subtitle',
                'Cada adivinanza resuelta revela un tramo del puente.');
            root.appendChild(subtitle);

            // Bridge
            var bridgeSection = _el('div', 'yg-journal-bridge-section');
            this._renderBridge(bridgeSection, state.bridgeSegments);
            root.appendChild(bridgeSection);

            // Rana
            var ranaSection = _el('div', 'yg-journal-rana-section');
            this._renderRana(ranaSection, state.ranaOpacity, state.ranaName);
            root.appendChild(ranaSection);

            // Entries
            var entriesSection = _el('div', 'yg-journal-entries-section');
            this._renderEntries(entriesSection, state.journalEntries, state.solvedRiddles);
            root.appendChild(entriesSection);
        },

        // ── Render the rope bridge SVG ───────────────────────────────
        _renderBridge: function (container, segments) {
            var label = _el('p', 'yg-journal-bridge-label', 'El Puente');
            container.appendChild(label);

            var svgW = 660;
            var svgH = 80;
            var svg = _svg('svg', {
                viewBox: '0 0 ' + svgW + ' ' + svgH,
                width: '100%',
                height: String(svgH),
                'class': 'yg-journal-bridge-svg'
            });
            svg.style.maxWidth = svgW + 'px';

            // Posts at each end
            var postL = _svg('rect', {
                x: '5', y: '10', width: '8', height: '60',
                fill: '#5D4037', rx: '2'
            });
            svg.appendChild(postL);
            var postR = _svg('rect', {
                x: String(svgW - 13), y: '10', width: '8', height: '60',
                fill: '#5D4037', rx: '2'
            });
            svg.appendChild(postR);

            // Top rope (catenary curve)
            var ropeTopD = 'M13,18 Q' + (svgW / 2) + ',8 ' + (svgW - 13) + ',18';
            var ropeTop = _svg('path', {
                d: ropeTopD, stroke: '#8B4513', 'stroke-width': '2.5',
                fill: 'none', 'stroke-linecap': 'round'
            });
            svg.appendChild(ropeTop);

            // Bottom rope
            var ropeBotD = 'M13,58 Q' + (svgW / 2) + ',48 ' + (svgW - 13) + ',58';
            var ropeBot = _svg('path', {
                d: ropeBotD, stroke: '#8B4513', 'stroke-width': '2.5',
                fill: 'none', 'stroke-linecap': 'round'
            });
            svg.appendChild(ropeBot);

            // Vertical ropes (handrails) — every few planks
            var plankArea = svgW - 40;
            var plankW = plankArea / TOTAL_DESTS;
            for (var v = 0; v < TOTAL_DESTS; v += 4) {
                var vx = 20 + v * plankW + plankW / 2;
                // Approximate catenary Y positions
                var t = (vx - 13) / (svgW - 26);
                var topY = 18 - 10 * 4 * t * (1 - t);  // slight upward curve
                var botY = 58 - 10 * 4 * t * (1 - t);
                var vRope = _svg('line', {
                    x1: String(vx), y1: String(topY),
                    x2: String(vx), y2: String(botY),
                    stroke: '#6D4C41', 'stroke-width': '1',
                    opacity: '0.4'
                });
                svg.appendChild(vRope);
            }

            // Planks
            for (var i = 0; i < TOTAL_DESTS; i++) {
                var px = 20 + i * plankW;
                // Slight catenary sag for plank Y position
                var ratio = (px + plankW / 2 - 13) / (svgW - 26);
                var sag = -10 * 4 * ratio * (1 - ratio);
                var py = 35 + sag;

                var plank = _svg('rect', {
                    x: String(px),
                    y: String(py),
                    width: String(Math.max(plankW - 1.5, 3)),
                    height: '14',
                    rx: '1.5'
                });

                if (i < segments) {
                    // Solved — golden plank
                    plank.setAttribute('fill', '#d4a843');
                    plank.setAttribute('opacity', '0.85');
                    plank.setAttribute('stroke', '#b8860b');
                    plank.setAttribute('stroke-width', '0.5');
                } else {
                    // Unsolved — dark/transparent
                    plank.setAttribute('fill', '#2c2c3e');
                    plank.setAttribute('opacity', '0.3');
                    plank.setAttribute('stroke', '#444');
                    plank.setAttribute('stroke-width', '0.3');
                }

                svg.appendChild(plank);
            }

            container.appendChild(svg);

            // Count text
            var count = _el('p', 'yg-journal-bridge-count',
                segments + ' de ' + TOTAL_DESTS + ' tramos revelados');
            container.appendChild(count);
        },

        // ── Render the rana SVG ──────────────────────────────────────
        _renderRana: function (container, opacity, name) {
            var label = _el('p', 'yg-journal-rana-label', 'La Rana');
            container.appendChild(label);

            var size = 100;
            var svg = _svg('svg', {
                viewBox: '0 0 100 90',
                width: String(size),
                height: String(Math.round(size * 0.9)),
                'class': 'yg-journal-rana-svg'
            });
            svg.style.opacity = String(opacity);

            // Body
            var body = _svg('path', {
                d: RANA_PATH,
                stroke: '#1b5e20',
                'stroke-width': '1.5'
            });

            // Color depends on threshold
            if (opacity >= 0.8) {
                // Full — vivid green
                body.setAttribute('fill', '#4CAF50');
            } else if (opacity >= 0.6) {
                // Color starts appearing
                body.setAttribute('fill', '#388E3C');
            } else if (opacity >= 0.4) {
                // Body fills — dark green
                body.setAttribute('fill', '#2E7D32');
            } else {
                // Silhouette only
                body.setAttribute('fill', '#1B5E20');
            }
            svg.appendChild(body);

            // Eyes appear at threshold 0.2+
            if (opacity >= 0.2) {
                var eyeL = _svg('circle', { cx: '35', cy: '18', r: '4', fill: '#FFD700' });
                var pupilL = _svg('circle', { cx: '36', cy: '18', r: '2', fill: '#000' });
                svg.appendChild(eyeL);
                svg.appendChild(pupilL);

                var eyeR = _svg('circle', { cx: '65', cy: '18', r: '4', fill: '#FFD700' });
                var pupilR = _svg('circle', { cx: '66', cy: '18', r: '2', fill: '#000' });
                svg.appendChild(eyeR);
                svg.appendChild(pupilR);
            }

            // Belly marking at 0.4+
            if (opacity >= 0.4) {
                var belly = _svg('ellipse', {
                    cx: '50', cy: '55', rx: '15', ry: '12',
                    fill: 'rgba(255,255,255,0.1)', stroke: 'none'
                });
                svg.appendChild(belly);
            }

            // Spots at 0.6+
            if (opacity >= 0.6) {
                var spots = [
                    { cx: '30', cy: '40', r: '3' },
                    { cx: '70', cy: '40', r: '3' },
                    { cx: '42', cy: '35', r: '2' },
                    { cx: '58', cy: '35', r: '2' }
                ];
                for (var s = 0; s < spots.length; s++) {
                    var spot = _svg('circle', {
                        cx: spots[s].cx, cy: spots[s].cy, r: spots[s].r,
                        fill: 'rgba(27,94,32,0.5)', stroke: 'none'
                    });
                    svg.appendChild(spot);
                }
            }

            // Smile at 0.8+
            if (opacity >= 0.8) {
                var smile = _svg('path', {
                    d: 'M40,28 Q50,35 60,28',
                    stroke: '#1B5E20', 'stroke-width': '1.5',
                    fill: 'none', 'stroke-linecap': 'round'
                });
                svg.appendChild(smile);
            }

            container.appendChild(svg);

            // Status text
            var statusText;
            if (name) {
                statusText = '';
            } else if (opacity >= 0.8) {
                statusText = 'La rana est\u00e1 casi lista para recibir su nombre.';
            } else if (opacity >= 0.6) {
                statusText = 'Los colores empiezan a brillar.';
            } else if (opacity >= 0.4) {
                statusText = 'La forma se llena poco a poco.';
            } else if (opacity >= 0.2) {
                statusText = 'Unos ojos dorados te miran desde la sombra.';
            } else if (opacity > 0) {
                statusText = 'Algo se mueve en la niebla...';
            } else {
                statusText = 'Resuelve adivinanzas para revelar a la rana.';
            }

            if (statusText) {
                var status = _el('p', 'yg-journal-rana-status', statusText);
                container.appendChild(status);
            }

            // Show name if named
            if (name) {
                var nameEl = _el('p', 'yg-journal-rana-name', name);
                container.appendChild(nameEl);
            }
        },

        // ── Render journal entries ───────────────────────────────────
        _renderEntries: function (container, entries, solvedRiddles) {
            var title = _el('h3', 'yg-journal-entries-title',
                'P\u00e1ginas del cuaderno');
            container.appendChild(title);

            // Build a lookup of solved destinations
            var solvedMap = {};
            for (var s = 0; s < solvedRiddles.length; s++) {
                solvedMap[solvedRiddles[s]] = true;
            }

            // Build a lookup of entries by dest number
            var entryMap = {};
            for (var e = 0; e < entries.length; e++) {
                entryMap[entries[e].dest] = entries[e];
            }

            // Render all 58 destinations
            for (var d = 1; d <= TOTAL_DESTS; d++) {
                var entry = entryMap[d];
                var isSolved = !!solvedMap[d];

                var card = _el('div', 'yg-journal-entry ' + (isSolved ? 'solved' : 'unsolved'));

                var header = _el('div', 'yg-journal-entry-header');

                if (isSolved && entry) {
                    // Solved entry
                    var destLabel = _el('span', 'yg-journal-entry-dest',
                        entry.destName || ('Destino ' + d));
                    header.appendChild(destLabel);

                    if (entry.solvedAt) {
                        var dateStr = new Date(entry.solvedAt).toLocaleDateString('es-CO', {
                            day: 'numeric', month: 'short', year: 'numeric'
                        });
                        var dateEl = _el('span', 'yg-journal-entry-date', dateStr);
                        header.appendChild(dateEl);
                    }

                    card.appendChild(header);

                    // Verse
                    if (entry.verse) {
                        var verseText = Array.isArray(entry.verse)
                            ? entry.verse.join('\n')
                            : entry.verse;
                        var verse = _el('div', 'yg-journal-entry-verse', verseText);
                        verse.style.whiteSpace = 'pre-line';
                        card.appendChild(verse);
                    }

                    // Answer
                    var answerEl = _el('p', 'yg-journal-entry-answer',
                        'Respuesta: ' + entry.answer);
                    card.appendChild(answerEl);

                    // Candelaria's note
                    if (entry.candelariaNote) {
                        var noteEl = _el('p', 'yg-journal-entry-note',
                            '\u00ab' + entry.candelariaNote + '\u00bb');
                        card.appendChild(noteEl);
                    }

                    // Student's riddle (if typed_create mode was used)
                    if (entry.studentRiddle) {
                        var studentWrap = _el('div', 'yg-journal-entry-student');
                        var studentLabel = _el('p', 'yg-journal-entry-student-label',
                            'Tu adivinanza:');
                        studentWrap.appendChild(studentLabel);
                        var studentText = _el('p', '', entry.studentRiddle);
                        studentWrap.appendChild(studentText);
                        card.appendChild(studentWrap);
                    }
                } else {
                    // Unsolved — blank page
                    var blankDest = _el('span', 'yg-journal-entry-dest', 'Destino ' + d);
                    header.appendChild(blankDest);
                    card.appendChild(header);

                    var blank = _el('p', 'yg-journal-blank', '? ? ?');
                    card.appendChild(blank);
                }

                container.appendChild(card);
            }
        }
    };

    // ── Auto-init on DOM ready (after server sync if available) ──────
    document.addEventListener('DOMContentLoaded', function () {
        if (window.Busqueda && Busqueda.pullServerState) {
            Busqueda.pullServerState(function () {
                Journal.init();
            });
        } else {
            Journal.init();
        }
    });

    window.Journal = Journal;
})();
