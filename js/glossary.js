/**
 * Glossary — Game vocabulary viewer
 * Requires authentication. Shows words by ecosystem with mastery tracking.
 */
(function() {
    'use strict';

    var API = '/api/glossary';
    var ecosystems = ['bosque', 'sierra', 'costa', 'llanos', 'nevada', 'selva', 'islas', 'desierto'];
    var ecosystemLabels = {
        bosque: 'Bosque', sierra: 'Sierra', costa: 'Costa', llanos: 'Llanos',
        nevada: 'Nevada', selva: 'Selva', islas: 'Islas', desierto: 'Desierto'
    };
    var ecosystemEmoji = {
        bosque: '\uD83C\uDF33', sierra: '\u26F0\uFE0F', costa: '\uD83C\uDFD6\uFE0F',
        llanos: '\uD83C\uDF3E', nevada: '\uD83C\uDFD4\uFE0F', selva: '\uD83C\uDF3F',
        islas: '\uD83C\uDFDD\uFE0F', desierto: '\uD83C\uDFDC\uFE0F'
    };

    var currentEcosystem = 'bosque';
    var currentLevel = '';
    var currentMastery = '';
    var statsData = null;

    var els = {};

    function init() {
        // Check auth
        if (!window.JaguarAPI || !JaguarAPI.isAuthenticated()) {
            window.location.href = '/login';
            return;
        }

        els.tabs = document.getElementById('ecoTabs');
        els.wordGrid = document.getElementById('wordGrid');
        els.wordDetail = document.getElementById('wordDetail');
        els.statsBar = document.getElementById('statsBar');
        els.levelFilter = document.getElementById('levelFilter');
        els.masteryFilter = document.getElementById('masteryFilter');
        els.searchInput = document.getElementById('glossarySearch');

        renderTabs();
        bindEvents();
        loadStats();
        loadWords();
    }

    function getToken() {
        try {
            var session = JSON.parse(localStorage.getItem('jaguarUserSession') || '{}');
            return session.serverToken || null;
        } catch(e) { return null; }
    }

    function apiRequest(method, path, body) {
        var headers = { 'Content-Type': 'application/json' };
        var token = getToken();
        if (token) headers['Authorization'] = 'Bearer ' + token;

        var opts = { method: method, headers: headers, credentials: 'same-origin' };
        if (body && method === 'POST') opts.body = JSON.stringify(body);

        return fetch('/api' + path, opts).then(function(r) {
            if (r.status === 401) {
                window.location.href = '/login';
                return Promise.reject(new Error('Not authenticated'));
            }
            return r.json();
        });
    }

    function renderTabs() {
        var html = '';
        for (var i = 0; i < ecosystems.length; i++) {
            var eco = ecosystems[i];
            html += '<button class="eco-tab' + (eco === currentEcosystem ? ' active' : '') + '" data-eco="' + eco + '">' +
                '<span class="eco-emoji">' + ecosystemEmoji[eco] + '</span>' +
                '<span class="eco-name">' + ecosystemLabels[eco] + '</span>' +
            '</button>';
        }
        els.tabs.innerHTML = html;

        var btns = els.tabs.querySelectorAll('.eco-tab');
        for (var j = 0; j < btns.length; j++) {
            btns[j].addEventListener('click', function() {
                currentEcosystem = this.dataset.eco;
                renderTabs();
                loadWords();
                closeDetail();
            });
        }
    }

    function bindEvents() {
        els.levelFilter.addEventListener('change', function() {
            currentLevel = this.value;
            loadWords();
        });
        els.masteryFilter.addEventListener('change', function() {
            currentMastery = this.value;
            loadWords();
        });

        var searchTimer;
        els.searchInput.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 1) { loadWords(); return; }
            searchTimer = setTimeout(function() { searchWords(q); }, 300);
        });
    }

    function loadStats() {
        apiRequest('GET', '/glossary/stats').then(function(res) {
            if (res.success) {
                statsData = res.data.stats;
                renderStats();
            }
        }).catch(function() {});
    }

    function renderStats() {
        if (!statsData || !statsData.totals) return;
        var t = statsData.totals;
        els.statsBar.innerHTML =
            '<div class="stat-item"><span class="stat-num">' + (t.seen || 0) + '</span> seen</div>' +
            '<div class="stat-item"><span class="stat-num">' + (t.mastered || 0) + '</span> mastered</div>' +
            '<div class="stat-item"><span class="stat-num">' + (t.total_words || 0) + '</span> total</div>';
    }

    function loadWords() {
        var params = '?ecosystem=' + currentEcosystem;
        if (currentLevel) params += '&level=' + currentLevel;

        els.wordGrid.innerHTML = '<div class="glossary-loading">Loading...</div>';

        apiRequest('GET', '/glossary/words' + params).then(function(res) {
            if (res.success) {
                renderWords(filterByMastery(res.data.words));
            }
        }).catch(function() {
            els.wordGrid.innerHTML = '<div class="glossary-loading">Error loading vocabulary</div>';
        });
    }

    function searchWords(q) {
        apiRequest('GET', '/glossary/search?q=' + encodeURIComponent(q)).then(function(res) {
            if (res.success) {
                renderWords(res.data.words);
            }
        }).catch(function() {});
    }

    function filterByMastery(words) {
        if (!currentMastery) return words;
        var m = parseInt(currentMastery);
        return words.filter(function(w) {
            return (w.mastery_level || 0) === m;
        });
    }

    function renderWords(words) {
        if (!words || !words.length) {
            els.wordGrid.innerHTML = '<div class="glossary-empty">No vocabulary found for this selection.</div>';
            return;
        }

        var html = '';
        for (var i = 0; i < words.length; i++) {
            var w = words[i];
            var mastery = parseInt(w.mastery_level) || 0;
            var masteryClass = mastery >= 3 ? 'mastered' : mastery >= 2 ? 'practiced' : mastery >= 1 ? 'seen' : 'new';

            html += '<div class="word-card ' + masteryClass + '" data-id="' + w.id + '">';
            if (w.emoji) html += '<span class="word-emoji">' + esc(w.emoji) + '</span>';
            html += '<span class="word-text">' + esc(w.word) + '</span>';
            html += '<span class="word-badges">';
            if (w.cefr_level) html += '<span class="cefr-badge cefr-' + w.cefr_level + '">' + w.cefr_level + '</span>';
            html += '<span class="mastery-dots">';
            for (var d = 0; d < 3; d++) {
                html += '<span class="dot' + (d < mastery ? ' filled' : '') + '"></span>';
            }
            html += '</span>';
            html += '</span>';
            html += '</div>';
        }

        els.wordGrid.innerHTML = html;

        // Bind click handlers
        var cards = els.wordGrid.querySelectorAll('.word-card');
        for (var j = 0; j < cards.length; j++) {
            cards[j].addEventListener('click', function() {
                loadWordDetail(this.dataset.id);
            });
        }
    }

    function loadWordDetail(wordId) {
        apiRequest('GET', '/glossary/word/' + wordId).then(function(res) {
            if (res.success && res.data.word) {
                renderWordDetail(res.data.word);
            }
        }).catch(function() {});
    }

    function renderWordDetail(w) {
        var html = '<div class="detail-header">';
        html += '<button class="detail-close" id="detailClose">&times;</button>';
        html += '<h2 class="detail-word">' + esc(w.word) + '</h2>';
        html += '<div class="detail-meta">';
        if (w.part_of_speech) html += '<span class="detail-pos">' + esc(w.part_of_speech) + '</span>';
        if (w.gender) html += '<span class="detail-gender">' + esc(w.gender) + '</span>';
        if (w.cefr_level) html += '<span class="cefr-badge cefr-' + w.cefr_level + '">' + w.cefr_level + '</span>';
        html += '</div>';

        if (w.pronunciation_ipa) {
            html += '<div class="detail-ipa">/' + esc(w.pronunciation_ipa) + '/</div>';
        }

        if (w.translation) {
            html += '<div class="detail-translation">' + esc(w.translation) + '</div>';
        }

        html += '</div>';

        // Mastery
        var mastery = parseInt(w.mastery_level) || 0;
        var masteryLabels = ['New', 'Seen', 'Practiced', 'Mastered'];
        html += '<div class="detail-mastery">';
        html += '<span class="mastery-label">' + masteryLabels[mastery] + '</span>';
        html += '<span class="mastery-stats">Seen ' + (w.times_seen || 0) + 'x &middot; Correct ' + (w.times_correct || 0) + 'x</span>';
        html += '</div>';

        // Examples from games
        if (w.examples && w.examples.length) {
            html += '<div class="detail-section">';
            html += '<h3>Game examples</h3>';
            for (var i = 0; i < w.examples.length; i++) {
                var ex = w.examples[i];
                html += '<div class="detail-example">';
                html += '<div class="detail-ex-es">' + esc(ex.sentence_es) + '</div>';
                if (ex.sentence_gloss) html += '<div class="detail-ex-gloss">' + esc(ex.sentence_gloss) + '</div>';
                html += '</div>';
            }
            html += '</div>';
        }

        // Link to dictionary
        if (w.dict_word_id) {
            html += '<a href="/dictionary/es/' + encodeURIComponent(w.word) + '" class="detail-dict-link">See in dictionary &rarr;</a>';
        }

        els.wordDetail.innerHTML = html;
        els.wordDetail.classList.add('visible');

        document.getElementById('detailClose').addEventListener('click', closeDetail);
    }

    function closeDetail() {
        els.wordDetail.classList.remove('visible');
        els.wordDetail.innerHTML = '';
    }

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
