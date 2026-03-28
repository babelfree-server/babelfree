/* ================================================================
   DESTINATION ROUTER — Routes play.html?dest=N to destination content
   Fetches content/dest{N}.json, assembles the full game array
   (arrival → games → escapeRoom), configures departure, and
   hands off to YaguaraEngine.init().

   URL scheme:
     play.html?dest=19          — basic
     play.html?dest=19&eco=costa — B1+ with ecosystem overlay

   The engine handles departure rendering natively in _showWorldRestored().
   The engine handles journey completion tracking in _showWorldRestored().
   ================================================================ */
(function() {
    'use strict';

    /* ==========================================================
       CONSTANTS
    ========================================================== */
    var TOTAL_DESTINATIONS = 89;
    var FIRST_DEST_B1 = 22;

    var WORLD_META = {
        mundoDeAbajo:  { name: 'Mundo de Abajo',  subtitle: 'Nombrar el mundo',  bodyClass: 'yg-mundo-de-abajo'  },
        mundoDelMedio: { name: 'Mundo del Medio',  subtitle: 'Contar lo vivido',  bodyClass: 'yg-mundo-del-medio' },
        mundoDeArriba: { name: 'Mundo de Arriba',  subtitle: 'Cuidar la palabra', bodyClass: 'yg-mundo-de-arriba' }
    };

    var CEFR_SPEECH = {
        'A1': 0.5, 'A2': 0.6, 'B1': 0.7, 'B2': 0.8, 'C1': 0.9, 'C2': 1.0
    };

    /* ==========================================================
       URL PARSING
    ========================================================== */
    function getParams() {
        var params = new URLSearchParams(window.location.search);
        var dest = parseInt(params.get('dest'), 10);
        return {
            dest: isNaN(dest) ? null : dest,
            eco: (params.get('eco') || '').toLowerCase() || null,
            branch: params.get('branch') || null
        };
    }

    /* ==========================================================
       ECOSYSTEM OVERLAY (B1+ localization)
    ========================================================== */
    var _overlayCache = null;

    function loadOverlay(eco, callback) {
        if (!eco) { callback(null); return; }
        if (_overlayCache) { callback(_overlayCache[eco] || null); return; }

        fetch('/content/eco-overlays.json')
            .then(function(res) { return res.ok ? res.json() : null; })
            .then(function(data) {
                _overlayCache = data || {};
                callback(_overlayCache[eco] || null);
            })
            .catch(function() { callback(null); });
    }

    function applyOverlay(data, overlay) {
        if (!overlay || !overlay.nouns || !overlay.nouns.length) return;
        var replacements = overlay.nouns;

        function replaceInString(str) {
            if (typeof str !== 'string') return str;
            for (var i = 0; i < replacements.length; i++) {
                var r = replacements[i];
                if (r.from && r.to) {
                    str = str.split(r.from).join(r.to);
                }
            }
            return str;
        }

        function walk(obj) {
            if (typeof obj === 'string') return replaceInString(obj);
            if (Array.isArray(obj)) {
                for (var i = 0; i < obj.length; i++) obj[i] = walk(obj[i]);
                return obj;
            }
            if (obj && typeof obj === 'object') {
                var keys = Object.keys(obj);
                for (var i = 0; i < keys.length; i++) {
                    if (keys[i] === 'meta') continue; /* Don't mutate IDs */
                    obj[keys[i]] = walk(obj[keys[i]]);
                }
                return obj;
            }
            return obj;
        }

        /* Walk games, arrival, characterLines, departure — but not meta */
        if (data.arrival) walk(data.arrival);
        if (data.games) walk(data.games);
        if (data.characterLines) walk(data.characterLines);
        if (data.departure) walk(data.departure);
    }

    /* ==========================================================
       GAME ASSEMBLY
    ========================================================== */
    function assembleGames(data, eco) {
        var games = [];

        /* 0. Pre-arrival encounters (despertar vocabulary primers) */
        if (data.preArrival) {
            for (var p = 0; p < data.preArrival.length; p++) {
                games.push(data.preArrival[p]);
            }
        }

        /* 1. Arrival narrative */
        if (data.arrival) {
            games.push(data.arrival);
        }

        /* 2. Content games (skip _needsContent skeletons) */
        if (data.games) {
            for (var i = 0; i < data.games.length; i++) {
                if (!data.games[i]._needsContent) {
                    games.push(data.games[i]);
                }
            }
        }

        /* 3. Ecosystem variant games — merge if player has an ecosystem selected.
           Each student explores ONE ecosystem at a time; the engine picks
           games tagged with that ecosystem, adding variety without overwhelming. */
        if (data.ecosystemGames && data.ecosystemGames.length) {
            var targetEco = eco || null;
            for (var e = 0; e < data.ecosystemGames.length; e++) {
                var ecoGame = data.ecosystemGames[e];
                var gameEco = ecoGame._template && ecoGame._template.ecosystem;
                /* If player chose an ecosystem, only load that one.
                   If no ecosystem chosen, load all (engine/PersonalLexicon will filter). */
                if (!targetEco || gameEco === targetEco) {
                    games.push(ecoGame);
                }
            }
        }

        /* 4. Input mode variants — harder versions of core games (typing, drag, voice).
           The engine selects these based on student mastery via PersonalLexicon.
           Games with _inputMode are served when the student has mastered the
           choice version of the same game. */
        if (data.inputModeGames && data.inputModeGames.length) {
            for (var m = 0; m < data.inputModeGames.length; m++) {
                games.push(data.inputModeGames[m]);
            }
        }

        /* 5. Spiral return games — Steiner re-encounter rhythm.
           Games from earlier destinations reappear here with increased difficulty
           (more distractors, no hints). The 369 phase system's "breathing across
           destinations" — encounter → sleep → re-encounter at deeper level. */
        if (data.spiralGames && data.spiralGames.length) {
            for (var s = 0; s < data.spiralGames.length; s++) {
                games.push(data.spiralGames[s]);
            }
        }

        /* 6. Dynamic spiral return — Fibonacci-scheduled word revisits.
           Queries PersonalLexicon for words due for spiral return at this
           destination, generates reinforcement games on the fly. */
        if (window.PersonalLexicon) {
            try {
                var lex = new PersonalLexicon();
                var destNum = data.meta && data.meta.destination ? parseInt(data.meta.destination.replace('dest', ''), 10) : 0;
                if (destNum > 0) {
                    var dueWords = lex.getSpiralWords(destNum);
                    if (dueWords.length > 0) {
                        /* Build a fill game from the first 6 due words */
                        var spiralWords = dueWords.slice(0, 6);
                        var spiralGame = {
                            type: 'fill',
                            label: 'Retorno espiral',
                            title: 'Palabras que regresan',
                            instruction: 'Estas palabras vuelven a ti. ¿Las recuerdas?',
                            _spiral: true,
                            questions: []
                        };
                        for (var sw = 0; sw < spiralWords.length; sw++) {
                            var w = spiralWords[sw].word;
                            spiralGame.questions.push({
                                sentence: '¿Recuerdas la palabra «___»? (Escríbela)',
                                answer: w,
                                options: [w]
                            });
                        }
                        if (spiralGame.questions.length > 0) {
                            games.push(spiralGame);
                        }
                    }
                }
            } catch (e) {
                /* Spiral generation failed — continue without it */
            }
        }

        /* Escape rooms are now inline in games[] — no standalone escapeRoom key.
           Departure is NOT added as a game — the engine renders it
           natively in _showWorldRestored() via config.departure */

        return games;
    }

    /* ==========================================================
       CLUSTER DETECTION — groups narrative+practice into scroll units
    ========================================================== */
    function detectClusters(games) {
        for (var i = 0; i < games.length - 1; i++) {
            var curr = games[i];
            var next = games[i + 1];
            if (curr.type === 'narrative' && curr.grammar && curr.grammar.length > 0 && next.type !== 'narrative') {
                var currGrammar = curr.grammar || [];
                var nextGrammar = next.grammar || [];
                var overlap = false;
                for (var g = 0; g < currGrammar.length; g++) {
                    if (nextGrammar.indexOf(currGrammar[g]) >= 0) { overlap = true; break; }
                }
                if (overlap) {
                    curr._clusterWith = i + 1;
                    next._clusteredBy = i;
                }
            }
        }
    }

    /* ==========================================================
       DEPARTURE CONFIG
    ========================================================== */
    function buildDeparture(data, destNum, eco) {
        var dep = data.departure || {};
        var config = {};

        config.closingQuestion = dep.closingQuestion || data.meta.closingQuestion || '';
        config.yaguaraLine = dep.yaguaraLine || '';
        config.button = dep.button || 'Continuar el viaje';

        /* Build nextUrl — propagate eco param for B1+ */
        if (destNum < TOTAL_DESTINATIONS) {
            var nextDest = destNum + 1;
            config.nextUrl = '/play?dest=' + nextDest;
            if (eco && nextDest >= FIRST_DEST_B1) {
                config.nextUrl += '&eco=' + eco;
            }
        } else {
            /* dest89 — final destination */
            config.nextUrl = '/storymap';
        }

        return config;
    }

    /* ==========================================================
       PAGE CHROME
    ========================================================== */
    function updateChrome(meta, worldMeta) {
        /* Header */
        var titleEl = document.querySelector('.yg-header-title');
        var subtitleEl = document.querySelector('.yg-header-subtitle');
        if (titleEl) titleEl.textContent = meta.title || '';
        if (subtitleEl) subtitleEl.textContent = worldMeta.name + ' \u2014 ' + (meta.cefrSubLevel || meta.cefr || '');
        document.title = (meta.title || 'Destino') + ' \u2014 El Viaje del Jaguar';

        /* Body world class */
        document.body.classList.remove('yg-mundo-de-abajo', 'yg-mundo-del-medio', 'yg-mundo-de-arriba');
        document.body.classList.add(worldMeta.bodyClass);

        /* Back link → story map */
        var backBtn = document.querySelector('.yg-back-btn');
        if (backBtn) {
            backBtn.href = '/storymap';
            backBtn.textContent = '\u2190 Mapa';
        }
    }

    /* ==========================================================
       MAIN LOADER
    ========================================================== */
    /* ==========================================================
       BRANCH LOADER — story-driven sub-journeys from a1-branches.json
    ========================================================== */
    function loadBranch(branchId) {
        fetch('/content/a1-branches.json')
            .then(function(res) { return res.ok ? res.json() : null; })
            .then(function(data) {
                if (!data || !data.branches) { showError('Rama no encontrada.'); return; }
                var branch = null;
                for (var i = 0; i < data.branches.length; i++) {
                    if (data.branches[i].id === 'branch_' + branchId || data.branches[i].id === branchId) {
                        branch = data.branches[i];
                        break;
                    }
                }
                if (!branch) { showError('Rama "' + branchId + '" no encontrada.'); return; }

                /* Merge shared characterMeta */
                var charMeta = data.characterMeta || {};
                if (branch.characterMeta) {
                    for (var k in branch.characterMeta) charMeta[k] = branch.characterMeta[k];
                }

                var world = branch.world || 'mundoDeAbajo';
                var worldMeta = WORLD_META[world] || WORLD_META.mundoDeAbajo;
                var cefr = branch.cefr || 'A1';

                /* Assemble games: arrival + games */
                var games = [];
                if (branch.arrival) games.push(branch.arrival);
                if (branch.games) {
                    for (var g = 0; g < branch.games.length; g++) {
                        games.push(branch.games[g]);
                    }
                }

                /* Chrome */
                var titleEl = document.querySelector('.yg-header-title');
                var subtitleEl = document.querySelector('.yg-header-subtitle');
                if (titleEl) titleEl.textContent = branch.title || 'Rama';
                if (subtitleEl) subtitleEl.textContent = worldMeta.name + ' — ' + cefr;
                document.title = (branch.title || 'Rama') + ' — El Viaje del Jaguar';
                document.body.classList.remove('yg-mundo-de-abajo', 'yg-mundo-del-medio', 'yg-mundo-de-arriba');
                document.body.classList.add(worldMeta.bodyClass);

                /* Extract characters */
                var characters = [];
                if (branch.characterLines) {
                    for (var c in branch.characterLines) {
                        if (c !== 'char_yaguara') characters.push(c);
                    }
                }

                YaguaraEngine.init({
                    games: games,
                    container: document.getElementById('yaguaraCard'),
                    progressContainer: document.getElementById('yaguaraProgress'),
                    yaguaraPanel: document.getElementById('yaguaraPanel'),
                    destinationId: branch.id,
                    cefr: cefr,
                    world: world,
                    speechRate: CEFR_SPEECH[cefr] || 0.5,
                    storageKey: 'yaguara_' + branch.id + '_progress',
                    backUrl: '/storymap',
                    departure: branch.departure || {},
                    characters: characters,
                    characterLines: branch.characterLines || {},
                    characterMeta: charMeta
                });
            })
            .catch(function(err) {
                showError('No se pudo cargar la rama.');
                console.error('BranchLoader:', err);
            });
    }

    function loadDestination() {
        var p = getParams();

        /* Branch routing */
        if (p.branch) {
            loadBranch(p.branch);
            return;
        }

        if (!p.dest || p.dest < 1 || p.dest > TOTAL_DESTINATIONS) {
            showError('Destino no encontrado.');
            return;
        }

        var destNum = p.dest;
        var eco = p.eco;

        /* Load ontology in parallel with destination content — zero extra latency.
           If ontology fails, we continue without it (graceful degradation). */
        var ontologyPromise;
        if (window.YaguaraOntology) {
            var onto = new YaguaraOntology('/ontology/');
            ontologyPromise = onto.load().catch(function(err) {
                console.warn('DestinationRouter: ontology load failed, continuing without it', err);
                return null;
            });
        } else {
            ontologyPromise = Promise.resolve(null);
        }

        var destPromise = fetch('/content/dest' + destNum + '.json')
            .then(function(res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            });

        Promise.all([destPromise, ontologyPromise])
            .then(function(results) {
                var data = results[0];
                var ontology = results[1];
                var meta = data.meta || {};
                var world = meta.world || 'mundoDeAbajo';
                var worldMeta = WORLD_META[world] || WORLD_META.mundoDeAbajo;
                var cefr = meta.cefr || 'A1';

                /* Apply ecosystem overlay if B1+ and eco param present */
                var needsOverlay = eco && destNum >= FIRST_DEST_B1;

                function proceed() {
                    updateChrome(meta, worldMeta);

                    var games = assembleGames(data, eco);
                    detectClusters(games);

                    /* ── Mastery-based initial input mode ──
                       Use PersonalLexicon to compute avg mastery for each game's vocabulary,
                       then set _inputMode so the engine starts at the right difficulty. */
                    if (window.PersonalLexicon) {
                        var lexicon = PersonalLexicon.getInstance ? PersonalLexicon.getInstance() : null;
                        if (lexicon) {
                            var modeTypes = { fill: true, conjugation: true, translation: true, builder: true, dictation: true };
                            for (var mi = 0; mi < games.length; mi++) {
                                var g = games[mi];
                                if (!modeTypes[g.type] || g._inputMode) continue;
                                var vocab = g.vocabulary || [];
                                if (!vocab.length && g.questions) {
                                    for (var qi = 0; qi < g.questions.length; qi++) {
                                        var ans = g.questions[qi].answer;
                                        if (ans) vocab.push(ans);
                                    }
                                }
                                if (!vocab.length) continue;
                                var total = 0;
                                for (var vi = 0; vi < vocab.length; vi++) {
                                    total += lexicon.getMastery ? lexicon.getMastery(vocab[vi]) : 0;
                                }
                                var avg = total / vocab.length;
                                if (avg >= 0.85) g._inputMode = 'voice';
                                else if (avg >= 0.50) g._inputMode = 'typing';
                                else if (avg >= 0.30) g._inputMode = 'drag';
                                /* else: no _inputMode → defaults to choice */
                            }
                        }
                    }

                    var departure = buildDeparture(data, destNum, eco);

                    /* Filter characters — exclude yaguara from interjection pool */
                    var characters = (meta.characters || []).filter(function(c) {
                        return c !== 'char_yaguara' && data.characterLines && data.characterLines[c];
                    });

                    /* Start audio for this destination */
                    if (window.AudioManager) {
                        AudioManager.playWorldLoop(destNum);
                        if (eco) AudioManager.playEcosystem(eco);
                        AudioManager.playArrivalSting(destNum);
                    }

                    YaguaraEngine.init({
                        games: games,
                        container: document.getElementById('yaguaraCard'),
                        progressContainer: document.getElementById('yaguaraProgress'),
                        yaguaraPanel: document.getElementById('yaguaraPanel'),
                        destinationId: 'dest' + destNum,
                        destNum: destNum,
                        cefr: cefr,
                        world: world,
                        ecosystem: eco || null,
                        speechRate: CEFR_SPEECH[cefr] || 0.5,
                        storageKey: 'yaguara_dest' + destNum + '_progress',
                        backUrl: '/storymap',
                        departure: departure,
                        characters: characters,
                        characterLines: data.characterLines || {},
                        characterMeta: data.characterMeta || {},
                        ontology: ontology
                    });
                }

                if (needsOverlay) {
                    loadOverlay(eco, function(overlay) {
                        if (overlay) applyOverlay(data, overlay);
                        proceed();
                    });
                } else {
                    proceed();
                }
            })
            .catch(function(err) {
                showError('No se pudo cargar el destino. Intenta de nuevo.');
                console.error('DestinationRouter:', err);
            });
    }

    /* ==========================================================
       ERROR DISPLAY
    ========================================================== */
    function showError(msg) {
        var card = document.getElementById('yaguaraCard');
        if (card) {
            card.innerHTML = '<div style="text-align:center;padding:3rem 1.5rem;">' +
                '<p style="font-size:1.2rem;color:var(--sand-muted);margin-bottom:1.5rem;">' + msg + '</p>' +
                '<a href="/storymap" style="color:var(--ochre);text-decoration:underline;">Volver al mapa</a>' +
                '</div>';
        }
    }

    /* ==========================================================
       PUBLIC API
    ========================================================== */
    window.DestinationRouter = {
        TOTAL_DESTINATIONS: TOTAL_DESTINATIONS,
        WORLD_META: WORLD_META,
        load: loadDestination
    };

    /* Auto-load when DOM is ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadDestination);
    } else {
        loadDestination();
    }

})();
