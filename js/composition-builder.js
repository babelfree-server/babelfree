/**
 * CompositionBuilder — "Mi aventura por Colombia"
 *
 * Every word the student writes becomes part of their personal adventure.
 * Crónica fragments + riddle answers accumulate across 89 destinations
 * into a living literary composition — the student's own journey
 * alongside Yaguará through the lands of Colombia.
 *
 * Three-phase unlock:
 *   A1-A2 (dest 1-21):  Riddle answers earn LETTERS → spell key words
 *   B1-B2 (dest 22-55): Riddle answers earn WORDS  → form sentences
 *   C1-C2 (dest 56-89): Riddle answers earn SENTENCES → paragraphs
 *
 * The composition is NOT revealed all at once. It grows as the student
 * writes and solves riddles. At dest 89, the full text appears:
 * "Mi aventura por Colombia" — their treasure.
 *
 * Storage: localStorage key 'yaguara_aventura'
 * Depends: landmarks-colombia.json (loaded lazily)
 */
(function () {
  'use strict';

  var STORAGE_KEY  = 'yaguara_aventura';
  var LANDMARK_URL = 'content/landmarks-colombia.json';
  var VERSION      = 1;

  // ── Phase boundaries ──────────────────────────────────────────────
  var PHASE = {
    LETTERS:   { min: 1,  max: 21, label: 'Letras',    cefr: 'A1-A2' },
    WORDS:     { min: 22, max: 55, label: 'Palabras',   cefr: 'B1-B2' },
    SENTENCES: { min: 56, max: 89, label: 'Oraciones',  cefr: 'C1-C2' }
  };

  // ── World (chapter) boundaries ────────────────────────────────────
  var WORLDS = [
    { id: 'mundoDeAbajo',   label: 'Mundo de Abajo',   subtitle: 'El despertar', destMin: 1,  destMax: 21 },
    { id: 'mundoDelMedio',  label: 'Mundo del Medio',  subtitle: 'El camino',    destMin: 22, destMax: 55 },
    { id: 'mundoDeArriba',  label: 'Mundo de Arriba',  subtitle: 'El regreso',   destMin: 56, destMax: 89 }
  ];

  // ── Sentence starters for composition weaving ─────────────────────
  // Used when the student hasn't written a crónica for a destination.
  // The system fills with narrative connectors that weave riddle words.
  var CONNECTORS = {
    mundoDeAbajo: [
      'Yaguará abrió los ojos y vio ',
      'El primer sonido fue ',
      'En el camino encontré ',
      'La luz del amanecer iluminó ',
      'Todo comenzó con una palabra: '
    ],
    mundoDelMedio: [
      'El río nos llevó hasta ',
      'Candelaria señaló hacia ',
      'Entre las voces escuché ',
      'El mapa decía que más allá estaba ',
      'La sombra del jaguar cayó sobre '
    ],
    mundoDeArriba: [
      'Desde la cima vi ',
      'Las palabras se convirtieron en ',
      'Lo que aprendí se transformó en ',
      'El último paso fue hacia ',
      'Nombrar es crear, y yo creé '
    ]
  };

  // ── Default state ─────────────────────────────────────────────────
  function _defaultState() {
    return {
      version:       VERSION,
      chapters:      {},      // { "dest1": { cronica, riddleReward, landmark, timestamp } }
      earnedLetters: [],      // [ {dest, letter, word, position} ]
      earnedWords:   [],      // [ {dest, word} ]
      earnedSentences: [],    // [ {dest, sentence} ]
      compositionRevealed: false,
      totalWordsWritten: 0,
      startedAt:     null,
      lastUpdated:   null
    };
  }

  // ── Storage ───────────────────────────────────────────────────────
  function _load() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return _defaultState();
      var parsed = JSON.parse(raw);
      if (!parsed || parsed.version !== VERSION) return _defaultState();
      return parsed;
    } catch (e) { return _defaultState(); }
  }

  function _save(state) {
    state.lastUpdated = new Date().toISOString();
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) {
      if (e.name === 'QuotaExceededError') {
        console.warn('[CompositionBuilder] localStorage quota exceeded');
      }
    }
    // Server sync (fire-and-forget)
    if (typeof window !== 'undefined' && window.JaguarAPI && JaguarAPI.syncAdventureProgress) {
      JaguarAPI.syncAdventureProgress(state);
    }
  }

  // ── Landmark cache ────────────────────────────────────────────────
  var _landmarks = null;
  var _landmarkCallbacks = [];

  function _loadLandmarks(cb) {
    if (_landmarks) { if (cb) cb(_landmarks); return; }
    _landmarkCallbacks.push(cb);
    if (_landmarkCallbacks.length > 1) return; // already loading

    var xhr = new XMLHttpRequest();
    xhr.open('GET', LANDMARK_URL, true);
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          _landmarks = {};
          (data.landmarks || []).forEach(function (lm) {
            _landmarks[lm.dest] = lm;
          });
        } catch (e) { _landmarks = {}; }
      } else { _landmarks = {}; }
      _landmarkCallbacks.forEach(function (fn) { if (fn) fn(_landmarks); });
      _landmarkCallbacks = [];
    };
    xhr.onerror = function () {
      _landmarks = {};
      _landmarkCallbacks.forEach(function (fn) { if (fn) fn(_landmarks); });
      _landmarkCallbacks = [];
    };
    xhr.send();
  }

  // ── Helpers ───────────────────────────────────────────────────────
  function _getPhase(dest) {
    var d = parseInt(dest, 10) || 0;
    if (d <= PHASE.LETTERS.max)   return 'LETTERS';
    if (d <= PHASE.WORDS.max)     return 'WORDS';
    return 'SENTENCES';
  }

  function _getWorld(dest) {
    var d = parseInt(dest, 10) || 0;
    for (var i = 0; i < WORLDS.length; i++) {
      if (d >= WORLDS[i].destMin && d <= WORLDS[i].destMax) return WORLDS[i];
    }
    return WORLDS[2]; // default to last
  }

  function _getConnector(dest) {
    var world = _getWorld(dest);
    var pool = CONNECTORS[world.id] || CONNECTORS.mundoDeAbajo;
    return pool[dest % pool.length];
  }

  function _countWords(text) {
    if (!text) return 0;
    return text.trim().split(/\s+/).filter(function (w) { return w.length > 0; }).length;
  }

  // ── Riddle reward extraction ──────────────────────────────────────

  /**
   * Extract the reward from a riddle answer based on phase.
   * LETTERS: one letter from the answer (cycling through positions)
   * WORDS:   the full answer word
   * SENTENCES: a sentence woven from the answer + crónica
   */
  function _extractReward(dest, riddleAnswer, cronicaText) {
    var phase = _getPhase(dest);
    var d = parseInt(dest, 10) || 0;

    if (phase === 'LETTERS') {
      // Extract one letter per riddle. Cycle through positions.
      var cleanAnswer = (riddleAnswer || '').replace(/^(el|la|los|las|un|una)\s+/i, '').trim();
      var position = (d - 1) % Math.max(1, cleanAnswer.length);
      var letter = cleanAnswer.charAt(position) || '?';
      return {
        type: 'letter',
        letter: letter.toUpperCase(),
        word: cleanAnswer,
        position: position
      };
    }

    if (phase === 'WORDS') {
      return {
        type: 'word',
        word: (riddleAnswer || '').trim()
      };
    }

    // SENTENCES: combine riddle answer with student's crónica
    var sentence = '';
    if (cronicaText && cronicaText.trim().length > 10) {
      // Take the student's best sentence (longest non-trivial one)
      var sentences = cronicaText.split(/[.!?]+/).filter(function (s) {
        return s.trim().length > 15;
      });
      if (sentences.length > 0) {
        // Pick the longest
        sentences.sort(function (a, b) { return b.length - a.length; });
        sentence = sentences[0].trim();
        // Capitalize first letter
        sentence = sentence.charAt(0).toUpperCase() + sentence.slice(1);
        if (!/[.!?]$/.test(sentence)) sentence += '.';
      }
    }

    if (!sentence) {
      // Fallback: weave a sentence from the connector + riddle answer
      sentence = _getConnector(dest) + (riddleAnswer || '') + '.';
    }

    return {
      type: 'sentence',
      sentence: sentence
    };
  }

  // ── Composition assembly ──────────────────────────────────────────

  /**
   * Build the full composition text from state.
   * Returns array of chapter objects grouped by world.
   */
  function _assembleComposition(state, landmarks) {
    var composition = [];

    WORLDS.forEach(function (world) {
      var chapter = {
        world:    world,
        entries:  [],
        letterMosaic: '',
        wordChain: [],
        sentenceBlock: ''
      };

      for (var d = world.destMin; d <= world.destMax; d++) {
        var key = 'dest' + d;
        var ch = state.chapters[key] || null;
        var lm = (landmarks || {})[d] || null;

        var entry = {
          dest:      d,
          landmark:  lm,
          cronica:   ch ? ch.cronica : null,
          reward:    ch ? ch.riddleReward : null,
          completed: !!ch,
          timestamp: ch ? ch.timestamp : null
        };
        chapter.entries.push(entry);
      }

      // Build phase-specific accumulations
      var phase = _getPhase(world.destMin);
      if (phase === 'LETTERS') {
        chapter.letterMosaic = state.earnedLetters.map(function (el) {
          return el.letter;
        }).join('');
      } else if (phase === 'WORDS') {
        chapter.wordChain = state.earnedWords.map(function (ew) {
          return ew.word;
        });
      } else {
        chapter.sentenceBlock = state.earnedSentences.map(function (es) {
          return es.sentence;
        }).join(' ');
      }

      composition.push(chapter);
    });

    return composition;
  }

  // ── Public API ────────────────────────────────────────────────────

  var CompositionBuilder = {

    /**
     * Record a crónica submission for a destination.
     * Called by the engine after the student saves a crónica.
     */
    recordCronica: function (dest, text) {
      if (!dest || !text) return;
      var state = _load();
      var key = 'dest' + (parseInt(dest, 10) || 0);

      if (!state.chapters[key]) {
        state.chapters[key] = {};
      }
      state.chapters[key].cronica = text.trim();
      state.chapters[key].timestamp = new Date().toISOString();
      state.totalWordsWritten += _countWords(text);

      if (!state.startedAt) {
        state.startedAt = new Date().toISOString();
      }

      // Retroactive reward update: if riddle was already solved for this dest
      // but the sentence reward was generated without student text, regenerate it.
      var existingReward = state.chapters[key].riddleReward;
      if (existingReward && existingReward.type === 'sentence') {
        var newReward = _extractReward(d, existingReward._riddleAnswer || '', text);
        if (newReward.sentence && newReward.sentence.length > (existingReward.sentence || '').length) {
          state.chapters[key].riddleReward = newReward;
          // Update in earnedSentences array too
          for (var si = 0; si < state.earnedSentences.length; si++) {
            if (state.earnedSentences[si].dest === d) {
              state.earnedSentences[si].sentence = newReward.sentence;
              break;
            }
          }
        }
      }

      _save(state);
      this._emit('cronica', { dest: dest, words: _countWords(text) });
    },

    /**
     * Record a riddle solve. Extracts the phase-appropriate reward
     * and stores it in the composition.
     * Called by riddle-quest.js after successful solve.
     */
    recordRiddleSolve: function (dest, riddleAnswer) {
      if (!dest || !riddleAnswer) return;
      var state = _load();
      var d = parseInt(dest, 10) || 0;
      var key = 'dest' + d;

      // Get student's crónica for this dest (if any)
      var cronicaText = (state.chapters[key] || {}).cronica || '';

      // Extract reward
      var reward = _extractReward(d, riddleAnswer, cronicaText);

      // Store reward in chapter (plus the original answer for retroactive updates)
      if (!state.chapters[key]) {
        state.chapters[key] = {};
      }
      reward._riddleAnswer = riddleAnswer;
      state.chapters[key].riddleReward = reward;

      // Store in phase accumulator
      if (reward.type === 'letter') {
        state.earnedLetters.push({
          dest: d,
          letter: reward.letter,
          word: reward.word,
          position: reward.position
        });
      } else if (reward.type === 'word') {
        state.earnedWords.push({
          dest: d,
          word: reward.word
        });
      } else if (reward.type === 'sentence') {
        state.earnedSentences.push({
          dest: d,
          sentence: reward.sentence
        });
      }

      // Check if composition is complete (all 89 destinations)
      if (state.earnedLetters.length + state.earnedWords.length + state.earnedSentences.length >= 89) {
        state.compositionRevealed = true;
      }

      _save(state);
      this._emit('riddle', { dest: d, reward: reward });
      return reward;
    },

    /**
     * Get the full composition for rendering.
     * Returns via callback (async because landmarks may need loading).
     */
    getComposition: function (cb) {
      var state = _load();
      _loadLandmarks(function (landmarks) {
        var composition = _assembleComposition(state, landmarks);
        cb({
          composition: composition,
          stats: {
            totalWordsWritten: state.totalWordsWritten,
            destinationsVisited: Object.keys(state.chapters).length,
            lettersEarned: state.earnedLetters.length,
            wordsEarned: state.earnedWords.length,
            sentencesEarned: state.earnedSentences.length,
            isComplete: state.compositionRevealed,
            startedAt: state.startedAt,
            lastUpdated: state.lastUpdated
          },
          worlds: WORLDS,
          phases: PHASE
        });
      });
    },

    /**
     * Get a single landmark by destination number.
     */
    getLandmark: function (dest, cb) {
      _loadLandmarks(function (landmarks) {
        cb((landmarks || {})[parseInt(dest, 10)] || null);
      });
    },

    /**
     * Get the roadmap — all landmarks with completion status.
     */
    getRoadmap: function (cb) {
      var state = _load();
      _loadLandmarks(function (landmarks) {
        var roadmap = [];
        for (var d = 1; d <= 89; d++) {
          var lm = (landmarks || {})[d] || {};
          var key = 'dest' + d;
          var ch = state.chapters[key];
          roadmap.push({
            dest: d,
            name: lm.name || '',
            region: lm.region || '',
            ecosystem: lm.ecosystem || '',
            lat: lm.lat || 0,
            lng: lm.lng || 0,
            description: lm.description || '',
            tourism: lm.tourism || '',
            icon: lm.icon || '',
            visited: !!ch,
            hasCronica: !!(ch && ch.cronica),
            hasRiddle: !!(ch && ch.riddleReward),
            phase: _getPhase(d)
          });
        }
        cb(roadmap);
      });
    },

    /**
     * Get stats for the composition progress bar.
     */
    getProgress: function () {
      var state = _load();
      var total = state.earnedLetters.length + state.earnedWords.length + state.earnedSentences.length;
      return {
        total: total,
        of: 89,
        percent: Math.round((total / 89) * 100),
        letters: state.earnedLetters.length,
        words: state.earnedWords.length,
        sentences: state.earnedSentences.length,
        isComplete: state.compositionRevealed,
        wordsWritten: state.totalWordsWritten
      };
    },

    /**
     * Generate an exportable text version of the composition.
     */
    exportText: function (cb) {
      this.getComposition(function (data) {
        var lines = [];
        lines.push('MI AVENTURA POR COLOMBIA');
        lines.push('');

        data.composition.forEach(function (chapter) {
          lines.push('═══════════════════════════════════');
          lines.push(chapter.world.label.toUpperCase() + ' — ' + chapter.world.subtitle);
          lines.push('═══════════════════════════════════');
          lines.push('');

          chapter.entries.forEach(function (entry) {
            if (!entry.completed) return;
            var lm = entry.landmark || {};
            lines.push('── ' + (lm.name || 'Destino ' + entry.dest) + ' ──');
            if (lm.description) lines.push(lm.description);
            if (entry.cronica) {
              lines.push('');
              lines.push(entry.cronica);
            }
            if (entry.reward) {
              if (entry.reward.type === 'letter') {
                lines.push('[Letra ganada: ' + entry.reward.letter + ']');
              } else if (entry.reward.type === 'word') {
                lines.push('[Palabra ganada: ' + entry.reward.word + ']');
              } else if (entry.reward.type === 'sentence') {
                lines.push(entry.reward.sentence);
              }
            }
            lines.push('');
          });
        });

        lines.push('───────────────────────────────────');
        lines.push('Palabras escritas: ' + data.stats.totalWordsWritten);
        lines.push('Destinos visitados: ' + data.stats.destinationsVisited + '/89');
        lines.push('');
        lines.push('El Viaje del Jaguar — babelfree.com');

        cb(lines.join('\n'));
      });
    },

    // ── Event system ──────────────────────────────────────────────
    _listeners: {},

    on: function (event, fn) {
      if (!this._listeners[event]) this._listeners[event] = [];
      this._listeners[event].push(fn);
    },

    _emit: function (event, data) {
      (this._listeners[event] || []).forEach(function (fn) {
        try { fn(data); } catch (e) {}
      });
    }
  };

  // ── Expose globally ───────────────────────────────────────────────
  if (typeof window !== 'undefined') {
    window.CompositionBuilder = CompositionBuilder;

    // Pull from server on load (merge: server wins if more progress)
    if (window.JaguarAPI && JaguarAPI.getAdventureProgress) {
      try {
        JaguarAPI.getAdventureProgress().then(function (remote) {
          if (!remote) return;
          var local = _load();
          var remoteChapterCount = Object.keys(remote.chapters || {}).length;
          var localChapterCount  = Object.keys(local.chapters || {}).length;
          // Server wins if it has more chapters
          if (remoteChapterCount > localChapterCount) {
            local.chapters         = remote.chapters || local.chapters;
            local.earnedLetters    = remote.earned_letters || local.earnedLetters;
            local.earnedWords      = remote.earned_words || local.earnedWords;
            local.earnedSentences  = remote.earned_sentences || local.earnedSentences;
            local.compositionRevealed = remote.composition_revealed || local.compositionRevealed;
            local.totalWordsWritten = Math.max(local.totalWordsWritten, remote.total_words_written || 0);
            local.startedAt        = remote.started_at || local.startedAt;
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(local)); } catch (e) {}
          }
        }).catch(function () { /* silent — offline is fine */ });
      } catch (e) { /* silent */ }
    }
  }

})();
