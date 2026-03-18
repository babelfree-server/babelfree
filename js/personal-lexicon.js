/**
 * PersonalLexicon — The student's living word universe
 *
 * Every word the student encounters becomes part of their personal world.
 * The lexicon tracks acquisition, harvests creative writing, manages
 * ecosystem affinity, and generates personalized game content.
 *
 * "A toddler doesn't learn 'the 500 most common words.'
 *  A toddler learns THEIR words."
 *
 * Storage: localStorage key 'yaguara_lexicon'
 * Sync: pushes to server on login via JaguarAPI
 */

(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.PersonalLexicon = factory();
  }
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  // ─── Constants ───────────────────────────────────────────────────
  var STORAGE_KEY   = 'yaguara_lexicon';
  var AFFINITY_KEY  = 'yaguara_eco_affinity';
  var HARVEST_KEY   = 'yaguara_harvest';
  var VERSION       = 1;

  // Mastery thresholds
  var MASTERY = {
    ACQUIRED:  0.7,
    EMERGING:  0.3,
    SEED:      0.0,
    // Per-encounter gains
    ENCOUNTERED: 0.05,   // heard/read
    RECOGNIZED:  0.08,   // chose correctly
    PRODUCED:    0.12,    // typed/spoke correctly
    CREATIVE:    0.15,    // used in crónica/oráculo
    // Caps per mode
    CAP_ENCOUNTER: 0.30,
    CAP_RECOGNIZE: 0.50,
    CAP_PRODUCE:   0.85,
    CAP_CREATIVE:  1.00,
    // Decay
    DECAY_PER_DAY: 0.02,
    DECAY_FLOOR:   0.10
  };

  // Ecosystem list
  var ECOSYSTEMS = ['bosque', 'costa', 'desierto', 'islas', 'llanos', 'nevada', 'selva', 'sierra'];

  // Fibonacci intervals for spiral return (in destinations)
  var FIBONACCI = [1, 1, 2, 3, 5, 8, 13, 21];

  // ─── Storage helpers ─────────────────────────────────────────────

  function _load(key) {
    try {
      var raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      console.warn('[PersonalLexicon] Failed to load ' + key, e);
      return null;
    }
  }

  function _save(key, data) {
    try {
      localStorage.setItem(key, JSON.stringify(data));
    } catch (e) {
      console.warn('[PersonalLexicon] Failed to save ' + key, e);
    }
  }

  function _now() {
    return Math.floor(Date.now() / 1000);
  }

  function _daysSince(timestamp) {
    return Math.max(0, (_now() - timestamp) / 86400);
  }

  // ─── Word Acquisition Record ─────────────────────────────────────

  /**
   * Creates a new word record
   */
  function _newWordRecord(word, source, ecosystem) {
    return {
      w: word.toLowerCase(),
      src: source,           // e.g. "dest1:skit_poeta"
      eco: ecosystem || null,
      first: _now(),
      last: _now(),
      enc: 0,                // times encountered (heard/read)
      rec: 0,                // times recognized (chose correctly)
      pro: 0,                // times produced (typed/spoke)
      cre: 0,                // times used creatively
      m: 0,                  // current mastery score
      cm: [],                // crónica mentions [destN, ...]
      pc: null               // personal context (from crónica harvesting)
    };
  }

  /**
   * Calculates mastery from encounter counts with caps
   */
  function _calculateMastery(record) {
    var fromEnc = Math.min(record.enc * MASTERY.ENCOUNTERED, MASTERY.CAP_ENCOUNTER);
    var fromRec = Math.min(record.rec * MASTERY.RECOGNIZED,  MASTERY.CAP_RECOGNIZE);
    var fromPro = Math.min(record.pro * MASTERY.PRODUCED,    MASTERY.CAP_PRODUCE);
    var fromCre = Math.min(record.cre * MASTERY.CREATIVE,    MASTERY.CAP_CREATIVE);

    // Take the highest cap reached (not sum — each mode opens a ceiling)
    var raw = Math.max(fromEnc, fromRec, fromPro, fromCre);

    // Apply time decay
    var daysSinceLast = _daysSince(record.last);
    var decay = daysSinceLast * MASTERY.DECAY_PER_DAY;
    var decayed = raw - decay;

    // Floor: once known, never fully forgotten
    if (raw > MASTERY.SEED) {
      decayed = Math.max(decayed, MASTERY.DECAY_FLOOR);
    }

    return Math.min(1.0, Math.max(0, decayed));
  }

  // ─── Ecosystem Affinity ──────────────────────────────────────────

  function _defaultAffinity() {
    var a = {};
    for (var i = 0; i < ECOSYSTEMS.length; i++) {
      a[ECOSYSTEMS[i]] = 1 / ECOSYSTEMS.length; // equal starting weight
    }
    return a;
  }

  function _normalizeAffinity(affinity) {
    var total = 0;
    var key;
    for (key in affinity) {
      if (affinity.hasOwnProperty(key)) total += affinity[key];
    }
    if (total === 0) return _defaultAffinity();
    var normalized = {};
    for (key in affinity) {
      if (affinity.hasOwnProperty(key)) {
        normalized[key] = affinity[key] / total;
      }
    }
    return normalized;
  }

  // ─── Crónica Harvester ───────────────────────────────────────────

  /**
   * Extracts meaningful entities and preferences from student writing.
   * Returns an object with named entities, preferences, and vocabulary.
   */
  function _harvestText(text) {
    if (!text || typeof text !== 'string') return null;

    var harvest = {
      entities: [],    // named things: { name, type, context }
      preferences: [], // "me gusta X" patterns
      adjectives: [],  // descriptive words used
      vocabulary: [],  // all Spanish words used
      raw: text
    };

    // Extract "mi X se llama Y" patterns FIRST (pet/creature naming — takes priority)
    var petNames = {};
    var petPattern = /mi\s+([a-záéíóúñü\w]+)\s+se\s+llama\s+([A-ZÁÉÍÓÚÑÜa-záéíóúñü]+)/gi;
    var pm;
    while ((pm = petPattern.exec(text)) !== null) {
      harvest.entities.push({
        name: pm[2],
        type: 'pet',
        species: pm[1],
        context: pm[0]
      });
      petNames[pm[2]] = true;
    }

    // Extract "me llamo X" / "soy X" patterns (skip names already captured as pets)
    var namePatterns = [
      /(?:me llamo|soy|mi nombre es)\s+([A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]+)/gi,
      /(?:se llama|es)\s+([A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]+)/gi
    ];
    for (var i = 0; i < namePatterns.length; i++) {
      var m;
      while ((m = namePatterns[i].exec(text)) !== null) {
        if (!petNames[m[1]]) {
          harvest.entities.push({ name: m[1], type: 'named', context: m[0] });
        }
      }
    }

    // Extract "me gusta/gustan X" patterns
    var gustaPattern = /me\s+gusta(?:n)?\s+(?:el|la|los|las|mi|tu)?\s*([a-záéíóúñü]+)/gi;
    var gm;
    while ((gm = gustaPattern.exec(text)) !== null) {
      harvest.preferences.push(gm[1].trim().toLowerCase());
    }

    // Extract ecosystem affinity signals
    var ecoSignals = {
      bosque:   /bosque|selva|árbol|lluvia|rana|mono|tucán|mariposa/i,
      costa:    /costa|mar|playa|ola|pez|cangrejo|barco|sal/i,
      desierto: /desierto|arena|cactus|sol|calor|piedra|escorpión/i,
      islas:    /isla|tortuga|coral|palmera|agua|arena/i,
      llanos:   /llano|caballo|sabana|garza|arpa|toro|pasto/i,
      nevada:   /nevada|nieve|cóndor|montaña|frío|páramo|frailejón/i,
      selva:    /amazo|anaconda|piraña|guacamaya|liana|jaguar/i,
      sierra:   /sierra|café|mula|camino|arriero|montaña|frijol/i
    };
    harvest._ecoSignals = {};
    for (var eco in ecoSignals) {
      if (ecoSignals.hasOwnProperty(eco)) {
        var matches = text.match(ecoSignals[eco]);
        if (matches) harvest._ecoSignals[eco] = matches.length;
      }
    }

    // Extract all words (for vocabulary tracking)
    var words = text.toLowerCase().replace(/[^a-záéíóúñü\s]/g, '').split(/\s+/);
    for (var w = 0; w < words.length; w++) {
      if (words[w].length > 1) harvest.vocabulary.push(words[w]);
    }

    return harvest;
  }

  // ─── Pool-Based Game Generation ──────────────────────────────────

  /**
   * Ecosystem vocabulary pools — 8 sets of themed nouns per destination.
   * These are loaded from the template files and cached.
   */
  var _ecosystemVocab = {};

  /**
   * Fills pool slots in a game template with personalized vocabulary.
   *
   * Template slots use the format:
   *   {pool:core}     — mandatory core vocabulary (unchanged)
   *   {pool:eco}      — replaced with student's ecosystem-weighted word
   *   {pool:personal} — replaced with student's harvested entity/word
   *   {pool:eco.animal}   — ecosystem animal noun
   *   {pool:eco.place}    — ecosystem place noun
   *   {pool:eco.nature}   — ecosystem nature noun
   *   {pool:personal.name} — student's name
   *   {pool:personal.pet}  — student's named creature
   *
   * @param {Object} template - Game template with pool markers
   * @param {Object} lexicon  - The PersonalLexicon instance
   * @param {string} eco      - Target ecosystem (or null for affinity-weighted)
   * @returns {Object} - Filled game instance
   */
  function _fillPools(template, lexicon, eco) {
    if (!template) return template;

    var json = JSON.stringify(template);

    // Determine ecosystem
    var targetEco = eco || lexicon.getWeightedEcosystem();
    var ecoVocab = _ecosystemVocab[targetEco] || {};

    // Get personal entities
    var personal = lexicon.getPersonalEntities();
    var studentName = '';
    try {
      var user = window.JaguarAPI && window.JaguarAPI.getUser();
      studentName = (user && user.name) || '';
    } catch (e) { /* no user context */ }

    // Replace pool markers
    json = json.replace(/\{pool:eco\.(\w+)\}/g, function (match, category) {
      var pool = ecoVocab[category];
      if (pool && pool.length) {
        return pool[Math.floor(Math.random() * pool.length)];
      }
      return match; // leave unresolved if no vocab
    });

    json = json.replace(/\{pool:eco\}/g, function () {
      var allEcoWords = [];
      for (var cat in ecoVocab) {
        if (ecoVocab.hasOwnProperty(cat) && Array.isArray(ecoVocab[cat])) {
          allEcoWords = allEcoWords.concat(ecoVocab[cat]);
        }
      }
      return allEcoWords.length ? allEcoWords[Math.floor(Math.random() * allEcoWords.length)] : '';
    });

    json = json.replace(/\{pool:personal\.name\}/g, studentName || '{nombre}');

    json = json.replace(/\{pool:personal\.pet\}/g, function () {
      if (personal.pets && personal.pets.length) {
        return personal.pets[0].name;
      }
      return studentName || 'viajero';
    });

    json = json.replace(/\{pool:personal\.petSpecies\}/g, function () {
      if (personal.pets && personal.pets.length) {
        return personal.pets[0].species;
      }
      return 'jaguar';
    });

    json = json.replace(/\{pool:personal\}/g, function () {
      if (personal.favorites && personal.favorites.length) {
        return personal.favorites[Math.floor(Math.random() * personal.favorites.length)];
      }
      return '';
    });

    // Standard {nombre} replacement
    if (studentName) {
      json = json.replace(/\{nombre\}/g, studentName);
    }

    try {
      return JSON.parse(json);
    } catch (e) {
      console.warn('[PersonalLexicon] Pool fill produced invalid JSON', e);
      return template;
    }
  }

  // ─── Fibonacci Spiral Scheduler ──────────────────────────────────

  /**
   * Given a word's home destination and the current destination,
   * determines if the word is due for spiral return.
   *
   * @param {number} homeDest    — destination where word was introduced
   * @param {number} currentDest — destination being played
   * @param {number} encounters  — how many times already returned
   * @returns {boolean}
   */
  function _isSpiralDue(homeDest, currentDest, encounters) {
    if (encounters >= FIBONACCI.length) return false;
    var interval = FIBONACCI[Math.min(encounters, FIBONACCI.length - 1)];
    return currentDest === homeDest + interval;
  }

  // ─── The PersonalLexicon Class ───────────────────────────────────

  function PersonalLexicon() {
    this._words     = {};  // word → record
    this._affinity  = _defaultAffinity();
    this._harvested = { entities: [], preferences: [], pets: [], favorites: [] };
    this._loaded    = false;
    this._load();
  }

  var P = PersonalLexicon.prototype;

  // ── Persistence ──

  P._load = function () {
    var data = _load(STORAGE_KEY);
    if (data && data.v === VERSION) {
      this._words = data.words || {};
      this._loaded = true;
    }
    var aff = _load(AFFINITY_KEY);
    if (aff) this._affinity = aff;

    var har = _load(HARVEST_KEY);
    if (har) this._harvested = har;
  };

  P._persist = function () {
    _save(STORAGE_KEY, { v: VERSION, words: this._words, ts: _now() });
    _save(AFFINITY_KEY, this._affinity);
    _save(HARVEST_KEY, this._harvested);
  };

  // ── Word Recording ──

  /**
   * Record a word encounter.
   *
   * @param {string} word       — the word
   * @param {string} source     — e.g. "dest1:skit_poeta"
   * @param {string} mode       — 'encountered'|'recognized'|'produced'|'creative'
   * @param {string} [ecosystem] — ecosystem context if applicable
   */
  P.record = function (word, source, mode, ecosystem) {
    if (!word || typeof word !== 'string') return;
    var key = word.toLowerCase().trim();
    if (!key) return;

    if (!this._words[key]) {
      this._words[key] = _newWordRecord(key, source, ecosystem);
    }

    var rec = this._words[key];
    rec.last = _now();

    switch (mode) {
      case 'encountered': rec.enc++; break;
      case 'recognized':  rec.rec++; break;
      case 'produced':    rec.pro++; break;
      case 'creative':    rec.cre++; break;
      default:            rec.enc++; break;
    }

    // Update mastery
    rec.m = _calculateMastery(rec);

    // Update ecosystem affinity if applicable
    if (ecosystem && ECOSYSTEMS.indexOf(ecosystem) !== -1) {
      this._affinity[ecosystem] = (this._affinity[ecosystem] || 0) + 0.01;
      this._affinity = _normalizeAffinity(this._affinity);
    }

    this._persist();
  };

  /**
   * Record multiple words at once (e.g. from a skit's target words).
   */
  P.recordBatch = function (words, source, mode, ecosystem) {
    if (!Array.isArray(words)) return;
    for (var i = 0; i < words.length; i++) {
      this.record(words[i], source, mode, ecosystem);
    }
    // Single persist at end
    this._persist();
  };

  // ── Crónica Harvesting ──

  /**
   * Harvest personal language from a crónica entry.
   *
   * @param {string} text          — the student's written text
   * @param {string} [destination] — e.g. "dest1"
   */
  P.harvest = function (text, destination) {
    var result = _harvestText(text);
    if (!result) return;

    // Merge entities
    for (var i = 0; i < result.entities.length; i++) {
      var entity = result.entities[i];
      var exists = false;
      for (var j = 0; j < this._harvested.entities.length; j++) {
        if (this._harvested.entities[j].name === entity.name) {
          exists = true;
          break;
        }
      }
      if (!exists) {
        this._harvested.entities.push(entity);
        if (entity.type === 'pet') {
          this._harvested.pets.push(entity);
        }
      }
    }

    // Merge preferences
    for (var p = 0; p < result.preferences.length; p++) {
      if (this._harvested.preferences.indexOf(result.preferences[p]) === -1) {
        this._harvested.preferences.push(result.preferences[p]);
        this._harvested.favorites.push(result.preferences[p]);
      }
    }

    // Record vocabulary as creative uses
    for (var v = 0; v < result.vocabulary.length; v++) {
      this.record(result.vocabulary[v], (destination || 'cronica') + ':cronica', 'creative');
    }

    // Update ecosystem affinity from text signals
    if (result._ecoSignals) {
      for (var eco in result._ecoSignals) {
        if (result._ecoSignals.hasOwnProperty(eco)) {
          this._affinity[eco] = (this._affinity[eco] || 0) + result._ecoSignals[eco] * 0.02;
        }
      }
      this._affinity = _normalizeAffinity(this._affinity);
    }

    // Track crónica mentions on word records
    if (destination) {
      for (var w = 0; w < result.vocabulary.length; w++) {
        var key = result.vocabulary[w].toLowerCase();
        if (this._words[key] && this._words[key].cm.indexOf(destination) === -1) {
          this._words[key].cm.push(destination);
        }
      }
    }

    this._persist();
    return result;
  };

  // ── Mastery Queries ──

  P.getMastery = function (word) {
    var key = (word || '').toLowerCase().trim();
    var rec = this._words[key];
    if (!rec) return 0;
    rec.m = _calculateMastery(rec);
    return rec.m;
  };

  P.getWordRecord = function (word) {
    return this._words[(word || '').toLowerCase().trim()] || null;
  };

  P.getAcquired = function () {
    return this._getByMastery(MASTERY.ACQUIRED, 1.0);
  };

  P.getEmerging = function () {
    return this._getByMastery(MASTERY.EMERGING, MASTERY.ACQUIRED);
  };

  P.getSeeds = function () {
    return this._getByMastery(MASTERY.SEED, MASTERY.EMERGING);
  };

  P._getByMastery = function (min, max) {
    var result = [];
    for (var key in this._words) {
      if (!this._words.hasOwnProperty(key)) continue;
      var rec = this._words[key];
      rec.m = _calculateMastery(rec);
      if (rec.m >= min && rec.m < max) {
        result.push({ word: key, mastery: rec.m, record: rec });
      }
    }
    result.sort(function (a, b) { return b.mastery - a.mastery; });
    return result;
  };

  P.getTotalWords = function () {
    return Object.keys(this._words).length;
  };

  P.getAcquiredCount = function () {
    return this.getAcquired().length;
  };

  // ── Ecosystem Affinity ──

  P.getEcoAffinity = function () {
    return Object.assign({}, this._affinity);
  };

  /**
   * Returns a weighted random ecosystem based on affinity.
   * 60% chance of top 2, 30% middle 3, 10% bottom 3.
   */
  P.getWeightedEcosystem = function () {
    var sorted = ECOSYSTEMS.slice().sort(function (a, b) {
      return (this._affinity[b] || 0) - (this._affinity[a] || 0);
    }.bind(this));

    var r = Math.random();
    var pool;
    if (r < 0.60) {
      pool = sorted.slice(0, 2);       // top 2
    } else if (r < 0.90) {
      pool = sorted.slice(2, 5);       // middle 3
    } else {
      pool = sorted.slice(5, 8);       // bottom 3
    }
    return pool[Math.floor(Math.random() * pool.length)];
  };

  /**
   * Nudge affinity toward an ecosystem (e.g. when student clicks on it).
   */
  P.nudgeAffinity = function (ecosystem, amount) {
    if (ECOSYSTEMS.indexOf(ecosystem) === -1) return;
    this._affinity[ecosystem] = (this._affinity[ecosystem] || 0) + (amount || 0.05);
    this._affinity = _normalizeAffinity(this._affinity);
    this._persist();
  };

  // ── Personal Entities ──

  P.getPersonalEntities = function () {
    return {
      entities: (this._harvested.entities || []).slice(),
      pets: (this._harvested.pets || []).slice(),
      preferences: (this._harvested.preferences || []).slice(),
      favorites: (this._harvested.favorites || []).slice()
    };
  };

  // ── Pool-Based Game Generation ──

  /**
   * Fill a game template with personalized vocabulary from pools.
   *
   * @param {Object} template  — game template with {pool:...} markers
   * @param {string} [ecosystem] — force a specific ecosystem (or auto-weighted)
   * @returns {Object} — filled game instance
   */
  P.generatePool = function (template, ecosystem) {
    return _fillPools(template, this, ecosystem);
  };

  /**
   * Generate ecosystem variants of a template.
   *
   * @param {Object} template  — the core template
   * @param {string[]} [ecos]  — ecosystems to generate (default: all 8)
   * @returns {Object[]} — array of filled variants
   */
  P.generateEcoVariants = function (template, ecos) {
    var targets = ecos || ECOSYSTEMS;
    var variants = [];
    for (var i = 0; i < targets.length; i++) {
      var variant = _fillPools(template, this, targets[i]);
      if (variant._template) {
        variant._template.ecosystem = targets[i];
      }
      variants.push(variant);
    }
    return variants;
  };

  // ── Fibonacci Spiral ──

  /**
   * Get words due for spiral return at the current destination.
   *
   * @param {number} currentDest — destination number (1-58)
   * @returns {Object[]} — word records due for spiral
   */
  P.getSpiralWords = function (currentDest) {
    var due = [];
    for (var key in this._words) {
      if (!this._words.hasOwnProperty(key)) continue;
      var rec = this._words[key];
      // Extract home destination number from source
      var destMatch = (rec.src || '').match(/dest(\d+)/);
      if (!destMatch) continue;
      var homeDest = parseInt(destMatch[1], 10);
      if (homeDest >= currentDest) continue; // only spiral from PREVIOUS destinations

      var totalReturns = rec.enc + rec.rec + rec.pro - 1; // subtract first encounter
      if (_isSpiralDue(homeDest, currentDest, Math.max(0, totalReturns))) {
        rec.m = _calculateMastery(rec);
        due.push({ word: key, mastery: rec.m, record: rec, homeDest: homeDest });
      }
    }
    // Sort: lowest mastery first (most needed)
    due.sort(function (a, b) { return a.mastery - b.mastery; });
    return due;
  };

  /**
   * Get words that need reinforcement (emerging, not yet acquired).
   *
   * @param {number} [limit] — max words to return (default 20)
   * @returns {Object[]}
   */
  P.getNeedsReinforcement = function (limit) {
    var emerging = this.getEmerging();
    return emerging.slice(0, limit || 20);
  };

  // ── Ecosystem Vocabulary Registry ──

  /**
   * Register ecosystem vocabulary for pool generation.
   * Called when template files are loaded.
   *
   * @param {string} ecosystem — e.g. 'bosque'
   * @param {Object} vocab     — { animal: [...], place: [...], nature: [...], ... }
   */
  P.registerEcoVocab = function (ecosystem, vocab) {
    _ecosystemVocab[ecosystem] = vocab;
  };

  /**
   * Register all ecosystem vocabularies at once.
   *
   * @param {Object} allVocab — { bosque: {...}, costa: {...}, ... }
   */
  P.registerAllEcoVocab = function (allVocab) {
    for (var eco in allVocab) {
      if (allVocab.hasOwnProperty(eco)) {
        _ecosystemVocab[eco] = allVocab[eco];
      }
    }
  };

  // ── Server Sync ──

  /**
   * Sync lexicon to server via JaguarAPI.
   * Returns a promise if JaguarAPI supports it, otherwise fires and forgets.
   */
  P.sync = function () {
    if (typeof window === 'undefined' || !window.JaguarAPI) return;

    var payload = {
      words: this._words,
      affinity: this._affinity,
      harvested: this._harvested,
      version: VERSION,
      ts: _now()
    };

    if (typeof window.JaguarAPI.saveLexicon === 'function') {
      return window.JaguarAPI.saveLexicon(payload);
    }
  };

  /**
   * Load lexicon from server (on login).
   */
  P.loadFromServer = function (serverData) {
    if (!serverData || serverData.version !== VERSION) return;

    // Merge server data with local (server wins on conflicts by timestamp)
    if (serverData.words) {
      for (var key in serverData.words) {
        if (!serverData.words.hasOwnProperty(key)) continue;
        var serverRec = serverData.words[key];
        var localRec = this._words[key];
        if (!localRec || serverRec.last > localRec.last) {
          this._words[key] = serverRec;
        }
      }
    }

    if (serverData.affinity) {
      this._affinity = serverData.affinity;
    }

    if (serverData.harvested) {
      // Merge entities (deduplicate by name)
      var existingNames = {};
      for (var i = 0; i < this._harvested.entities.length; i++) {
        existingNames[this._harvested.entities[i].name] = true;
      }
      var serverEntities = serverData.harvested.entities || [];
      for (var j = 0; j < serverEntities.length; j++) {
        if (!existingNames[serverEntities[j].name]) {
          this._harvested.entities.push(serverEntities[j]);
        }
      }
      // Merge other arrays
      var arrays = ['pets', 'preferences', 'favorites'];
      for (var a = 0; a < arrays.length; a++) {
        var arr = serverData.harvested[arrays[a]] || [];
        var local = this._harvested[arrays[a]] || [];
        for (var k = 0; k < arr.length; k++) {
          var item = arr[k];
          var found = false;
          for (var l = 0; l < local.length; l++) {
            if (JSON.stringify(local[l]) === JSON.stringify(item)) { found = true; break; }
          }
          if (!found) local.push(item);
        }
        this._harvested[arrays[a]] = local;
      }
    }

    this._persist();
  };

  // ── Stats & Debug ──

  P.getStats = function () {
    var acquired = 0, emerging = 0, seeds = 0;
    for (var key in this._words) {
      if (!this._words.hasOwnProperty(key)) continue;
      var m = _calculateMastery(this._words[key]);
      if (m >= MASTERY.ACQUIRED) acquired++;
      else if (m >= MASTERY.EMERGING) emerging++;
      else seeds++;
    }
    return {
      total: Object.keys(this._words).length,
      acquired: acquired,
      emerging: emerging,
      seeds: seeds,
      affinity: this.getEcoAffinity(),
      entities: this._harvested.entities.length,
      pets: this._harvested.pets.length,
      preferences: this._harvested.preferences.length
    };
  };

  /**
   * Reset all data (for testing or account reset).
   */
  P.reset = function () {
    this._words = {};
    this._affinity = _defaultAffinity();
    this._harvested = { entities: [], preferences: [], pets: [], favorites: [] };
    this._persist();
  };

  // ── Singleton ──

  var _instance = null;

  PersonalLexicon.getInstance = function () {
    if (!_instance) _instance = new PersonalLexicon();
    return _instance;
  };

  // Expose for testing
  PersonalLexicon._harvestText = _harvestText;
  PersonalLexicon._calculateMastery = _calculateMastery;
  PersonalLexicon._isSpiralDue = _isSpiralDue;
  PersonalLexicon.MASTERY = MASTERY;
  PersonalLexicon.ECOSYSTEMS = ECOSYSTEMS;
  PersonalLexicon.FIBONACCI = FIBONACCI;

  return PersonalLexicon;
}));
