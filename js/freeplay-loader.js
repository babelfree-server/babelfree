/**
 * El Viaje del Jaguar — Free Play Loader
 * IIFE exposing window.FreePlayLoader
 * Loads catalog, renders picker UI, launches engine in arcade mode
 */
(function() {
    'use strict';

    var CATALOG_URL = '/content/freeplay-catalog.json';
    var MAX_GAMES_PER_SESSION = 10;

    var _catalog = null;
    var _pickerEl = null;
    var _engineEl = null;
    var _selectedLevel = null;
    var _selectedType = null;

    // Friendly labels per CEFR level
    var LEVEL_LABELS = {
        'A1': 'A1 — Principiante',
        'A2': 'A2 — Elemental',
        'B1': 'B1 — Intermedio',
        'B2': 'B2 — Intermedio alto',
        'C1': 'C1 — Avanzado',
        'C2': 'C2 — Maestr\u00eda'
    };

    var LEVEL_COLORS = {
        'A1': '#c9a227', 'A2': '#d4a843',
        'B1': '#5a8a5e', 'B2': '#4a7a4e',
        'C1': '#4a6fa5', 'C2': '#3a5f95'
    };

    function _loadCatalog() {
        return fetch(CATALOG_URL).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (data.success) {
                _catalog = data.data;
            } else {
                _catalog = data;
            }
            return _catalog;
        });
    }

    function _getTypesForLevel(level) {
        if (!_catalog || !_catalog.games) return [];
        var types = {};
        for (var i = 0; i < _catalog.games.length; i++) {
            var g = _catalog.games[i];
            if (g.cefr === level) {
                types[g.type] = (types[g.type] || 0) + 1;
            }
        }
        var result = [];
        for (var t in types) {
            result.push({ type: t, count: types[t], label: _catalog.games.find(function(g) { return g.type === t; }).label || t });
        }
        result.sort(function(a, b) { return b.count - a.count; });
        return result;
    }

    function _filterGames(level, type) {
        if (!_catalog || !_catalog.games) return [];
        return _catalog.games.filter(function(g) {
            if (level && g.cefr !== level) return false;
            if (type && g.type !== type) return false;
            return true;
        });
    }

    function _shuffle(arr) {
        var a = arr.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = a[i]; a[i] = a[j]; a[j] = tmp;
        }
        return a;
    }

    function _renderLevelPicker() {
        _pickerEl = document.getElementById('fpPicker');
        _engineEl = document.getElementById('fpEngineArea');
        if (!_pickerEl) return;

        var levels = (_catalog && _catalog.levels) || ['A1', 'A2'];
        var grid = _pickerEl.querySelector('#fpLevelGrid');
        if (!grid) return;

        grid.innerHTML = '';
        for (var i = 0; i < levels.length; i++) {
            var lv = levels[i];
            var btn = document.createElement('button');
            btn.className = 'fp-level-btn';
            btn.setAttribute('data-level', lv);
            btn.textContent = LEVEL_LABELS[lv] || lv;
            btn.style.borderColor = LEVEL_COLORS[lv] || '#c9a227';
            btn.addEventListener('click', (function(level) {
                return function() { _selectLevel(level); };
            })(lv));
            grid.appendChild(btn);
        }

        // Hide type grid until level is picked
        var typeSection = _pickerEl.querySelector('#fpTypeSection');
        if (typeSection) typeSection.style.display = 'none';
    }

    function _selectLevel(level) {
        _selectedLevel = level;
        _selectedType = null;

        // Highlight selected
        var btns = _pickerEl.querySelectorAll('.fp-level-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.toggle('active', btns[i].getAttribute('data-level') === level);
        }

        // Render type grid
        var typeSection = _pickerEl.querySelector('#fpTypeSection');
        var typeGrid = _pickerEl.querySelector('#fpTypeGrid');
        if (!typeSection || !typeGrid) return;

        typeSection.style.display = '';
        typeGrid.innerHTML = '';

        var types = _getTypesForLevel(level);
        for (var i = 0; i < types.length; i++) {
            var t = types[i];
            var btn = document.createElement('button');
            btn.className = 'fp-type-btn';
            btn.setAttribute('data-type', t.type);
            btn.innerHTML = '<span class="fp-type-label">' + t.label + '</span><span class="fp-type-count">' + t.count + '</span>';
            btn.addEventListener('click', (function(type) {
                return function() { _selectType(type); };
            })(t.type));
            typeGrid.appendChild(btn);
        }

        // Enable play button
        var playBtn = _pickerEl.querySelector('#fpPlayBtn');
        if (playBtn) playBtn.disabled = false;
    }

    function _selectType(type) {
        _selectedType = type;
        var btns = _pickerEl.querySelectorAll('.fp-type-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.toggle('active', btns[i].getAttribute('data-type') === type);
        }
    }

    function _startPlay(randomize) {
        var level = randomize ? null : _selectedLevel;
        var type = randomize ? null : _selectedType;

        if (!randomize && !level) {
            alert('Elige un nivel primero.');
            return;
        }

        // If fully random, pick random level from catalog
        if (randomize && _catalog && _catalog.levels) {
            var lv = _catalog.levels;
            level = lv[Math.floor(Math.random() * lv.length)];
        }

        var candidates = _filterGames(level, type);
        if (candidates.length === 0) {
            alert('No hay juegos disponibles para esta selecci\u00f3n.');
            return;
        }

        var selected = _shuffle(candidates).slice(0, MAX_GAMES_PER_SESSION);

        // Group by source file to minimize fetches
        var sourceMap = {};
        for (var i = 0; i < selected.length; i++) {
            var s = selected[i];
            if (!sourceMap[s.source]) sourceMap[s.source] = [];
            sourceMap[s.source].push(s);
        }

        // Fetch source files and extract games
        var fetchPromises = [];
        var sourceKeys = Object.keys(sourceMap);
        for (var k = 0; k < sourceKeys.length; k++) {
            (function(sourceFile) {
                var p = fetch('/content/' + sourceFile).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    return { file: sourceFile, data: data };
                }).catch(function() {
                    return { file: sourceFile, data: null };
                });
                fetchPromises.push(p);
            })(sourceKeys[k]);
        }

        Promise.all(fetchPromises).then(function(results) {
            var fileData = {};
            for (var i = 0; i < results.length; i++) {
                if (results[i].data) fileData[results[i].file] = results[i].data;
            }

            var games = [];
            for (var i = 0; i < selected.length; i++) {
                var entry = selected[i];
                var src = fileData[entry.source];
                if (src && src.games && src.games[entry.sourceIdx]) {
                    games.push(src.games[entry.sourceIdx]);
                }
            }

            if (games.length === 0) {
                alert('Error al cargar los juegos.');
                return;
            }

            _launchEngine(games, level);
        });
    }

    function _launchEngine(games, level) {
        if (_pickerEl) _pickerEl.style.display = 'none';
        if (_engineEl) _engineEl.style.display = '';

        var spiral = (level || 'A1').toLowerCase();

        // Use a separate localStorage key that never touches story progress
        var storageKey = 'freeplay_' + (level || 'mix') + '_' + Date.now();

        window.YaguaraEngine.init({
            arcadeMode: true,
            games: games,
            container: document.getElementById('yaguaraCard'),
            progressContainer: document.getElementById('yaguaraProgress'),
            yaguaraPanel: document.getElementById('yaguaraPanel'),
            spiral: spiral,
            storageKey: storageKey,
            world: 'mundoDeAbajo'
        });
    }

    function showPicker() {
        if (_engineEl) _engineEl.style.display = 'none';
        if (_pickerEl) _pickerEl.style.display = '';
        _selectedLevel = null;
        _selectedType = null;

        // Reset button states
        var levelBtns = document.querySelectorAll('.fp-level-btn');
        for (var i = 0; i < levelBtns.length; i++) levelBtns[i].classList.remove('active');
        var typeBtns = document.querySelectorAll('.fp-type-btn');
        for (var i = 0; i < typeBtns.length; i++) typeBtns[i].classList.remove('active');

        var typeSection = document.getElementById('fpTypeSection');
        if (typeSection) typeSection.style.display = 'none';
    }

    function init() {
        _loadCatalog().then(function() {
            _renderLevelPicker();

            // Bind play/random buttons
            var playBtn = document.getElementById('fpPlayBtn');
            var randBtn = document.getElementById('fpRandomBtn');
            if (playBtn) playBtn.addEventListener('click', function() { _startPlay(false); });
            if (randBtn) randBtn.addEventListener('click', function() { _startPlay(true); });
        }).catch(function(err) {
            var picker = document.getElementById('fpPicker');
            if (picker) {
                picker.innerHTML = '<p style="text-align:center;color:#c9a227;padding:2rem;">El cat\u00e1logo de juegos no est\u00e1 disponible. Int\u00e9ntalo m\u00e1s tarde.</p>';
            }
        });
    }

    window.FreePlayLoader = {
        init: init,
        showPicker: showPicker
    };
})();
