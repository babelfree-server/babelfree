/**
 * TemplateGenerator — Expands core templates into full destination content
 *
 * Takes 40 hand-authored templates and produces:
 *  1. Ecosystem variants (×8) — same grammar, different vocabulary worlds
 *  2. Input mode variants (×4) — choice → drag → typing → voice progression
 *  3. Fibonacci spiral scheduling — words return at spaced intervals
 *  4. Difficulty curves — option count, timers, scaffolding evolve
 *
 * The generator works at TWO levels:
 *  - Build time: produces static dest{N}.json with pre-baked ecosystem variants
 *  - Runtime: PersonalLexicon fills {pool:...} slots with student-specific vocabulary
 *
 * "40 seeds become a forest."
 */

(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.TemplateGenerator = factory();
  }
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  var ECOSYSTEMS = ['bosque', 'costa', 'desierto', 'islas', 'llanos', 'nevada', 'selva', 'sierra'];

  var INPUT_MODES = ['choice', 'drag', 'typing', 'voice'];

  // ── Ecosystem Variant Generator ──────────────────────────────────

  /**
   * Creates an ecosystem variant of a template by injecting vocabulary.
   *
   * @param {Object} template      — the core template
   * @param {string} ecosystem     — target ecosystem name
   * @param {Object} ecoVocab      — ecosystem vocabulary sets
   * @returns {Object|null}        — variant, or null if no ecosystem slots
   */
  function _createEcoVariant(template, ecosystem, ecoVocab) {
    var meta = template._template;
    if (!meta || !meta.ecosystemSlots || !meta.ecosystemSlots.length) {
      return null; // template has no ecosystem slots
    }

    var vocabSet = ecoVocab[ecosystem];
    if (!vocabSet) return null;

    // Deep clone
    var variant;
    try {
      variant = JSON.parse(JSON.stringify(template));
    } catch (e) {
      return null;
    }

    // Replace {pool:eco.CATEGORY} markers with actual words from this ecosystem
    var json = JSON.stringify(variant);

    json = json.replace(/\{pool:eco\.(\w+)\}/g, function (match, category) {
      var pool = vocabSet[category];
      if (pool && pool.length) {
        // Use deterministic selection based on category + ecosystem for consistency
        var hash = _simpleHash(ecosystem + ':' + category);
        return pool[hash % pool.length];
      }
      return match;
    });

    json = json.replace(/\{pool:eco\}/g, function () {
      var allWords = [];
      for (var cat in vocabSet) {
        if (vocabSet.hasOwnProperty(cat) && Array.isArray(vocabSet[cat])) {
          allWords = allWords.concat(vocabSet[cat]);
        }
      }
      if (!allWords.length) return '';
      var hash = _simpleHash(ecosystem + ':any');
      return allWords[hash % allWords.length];
    });

    try {
      variant = JSON.parse(json);
    } catch (e) {
      return null;
    }

    // Tag the variant
    if (!variant._template) variant._template = {};
    variant._template.ecosystem = ecosystem;
    variant._template.isVariant = true;
    variant._template.sourceId = meta.id;

    return variant;
  }

  /**
   * Simple deterministic hash for consistent ecosystem word selection.
   */
  function _simpleHash(str) {
    var hash = 0;
    for (var i = 0; i < str.length; i++) {
      var ch = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + ch;
      hash |= 0;
    }
    return Math.abs(hash);
  }

  // ── Input Mode Variants ──────────────────────────────────────────

  /**
   * Transforms a game template for a different input mode.
   * Not all game types support all modes.
   *
   * @param {Object} game     — game instance
   * @param {string} mode     — target input mode
   * @returns {Object|null}   — transformed game or null if unsupported
   */
  function _createInputModeVariant(game, mode) {
    var type = game.type;

    // Game types that don't have input mode variants
    var fixedTypes = ['skit', 'escaperoom', 'cronica', 'portafolio', 'autoevaluacion',
                      'cultura', 'explorador', 'narrative', 'ritmo', 'cartografo'];
    if (fixedTypes.indexOf(type) !== -1) return null;

    var variant;
    try {
      variant = JSON.parse(JSON.stringify(game));
    } catch (e) {
      return null;
    }

    switch (mode) {
      case 'choice':
        // Default mode — keep options, ensure they exist
        break;

      case 'drag':
        // For fill/builder: remove options, use drag interaction
        if (type === 'fill' && variant.questions) {
          for (var i = 0; i < variant.questions.length; i++) {
            // Keep options but mark as drag targets
            variant.questions[i]._inputMode = 'drag';
          }
        }
        if (type === 'category' || type === 'pair') {
          variant._inputMode = 'drag'; // already natural drag types
        }
        break;

      case 'typing':
        // Remove options — student must type the answer
        if (type === 'fill' && variant.questions) {
          for (var j = 0; j < variant.questions.length; j++) {
            delete variant.questions[j].options;
            variant.questions[j]._inputMode = 'typing';
          }
        }
        if (type === 'listening' && variant.questions) {
          for (var k = 0; k < variant.questions.length; k++) {
            delete variant.questions[k].options;
            variant.questions[k]._inputMode = 'typing';
          }
        }
        if (type === 'conjugation' && variant.questions) {
          for (var l = 0; l < variant.questions.length; l++) {
            delete variant.questions[l].options;
            variant.questions[l]._inputMode = 'typing';
          }
        }
        if (type === 'translation' && variant.questions) {
          for (var m = 0; m < variant.questions.length; m++) {
            delete variant.questions[m].options;
            variant.questions[m]._inputMode = 'typing';
          }
        }
        if (type === 'guardian' && variant.questions) {
          for (var n = 0; n < variant.questions.length; n++) {
            delete variant.questions[n].options;
            variant.questions[n]._inputMode = 'typing';
          }
        }
        break;

      case 'voice':
        // Transform to voice input
        if (type === 'fill' || type === 'conjugation' || type === 'guardian') {
          variant._inputMode = 'voice';
          // Remove options — student must speak
          if (variant.questions) {
            for (var o = 0; o < variant.questions.length; o++) {
              delete variant.questions[o].options;
              variant.questions[o]._inputMode = 'voice';
            }
          }
        }
        if (type === 'conjuro') {
          variant._inputMode = 'voice'; // already a voice type
        }
        if (type === 'sombra') {
          variant._inputMode = 'voice'; // already voice-adjacent
        }
        break;
    }

    // Tag the variant
    if (!variant._template) variant._template = {};
    variant._template.inputMode = mode;

    return variant;
  }

  // ── Difficulty Curve ─────────────────────────────────────────────

  /**
   * Applies difficulty adjustment for spiral returns.
   *
   * @param {Object} game       — game instance
   * @param {number} spiralPass — which return (0=first, 1=second, 2=third)
   * @returns {Object}          — adjusted game
   */
  function _applyDifficultyCurve(game, spiralPass) {
    var variant;
    try {
      variant = JSON.parse(JSON.stringify(game));
    } catch (e) {
      return game;
    }

    if (spiralPass === 0) return variant; // first pass: no changes

    // Second pass: add distractors, reduce time
    if (spiralPass >= 1) {
      if (variant.questions) {
        for (var i = 0; i < variant.questions.length; i++) {
          var q = variant.questions[i];
          // Add one extra distractor option if available
          if (q.options && q.options.length < 4) {
            q.options.push(_getDistractor(q.answer));
          }
          // Reduce time limit
          if (q.timeLimit) {
            q.timeLimit = Math.max(3, q.timeLimit - 2);
          }
        }
      }
      // Reduce global time limit
      if (variant.timeLimit) {
        variant.timeLimit = Math.max(3, variant.timeLimit - 2);
      }
    }

    // Third pass: remove hints/tips
    if (spiralPass >= 2) {
      delete variant.tip;
      if (variant.questions) {
        for (var j = 0; j < variant.questions.length; j++) {
          delete variant.questions[j].hint;
        }
      }
    }

    if (!variant._template) variant._template = {};
    variant._template.spiralPass = spiralPass;

    return variant;
  }

  /**
   * Returns a plausible distractor for a given answer.
   */
  function _getDistractor(answer) {
    var distractors = {
      'soy': 'somos', 'eres': 'son', 'es': 'sos',
      'hola': 'bueno', 'adiós': 'luego', 'sí': 'quizás',
      'El': 'Los', 'La': 'Las', 'un': 'unos', 'una': 'unas'
    };
    return distractors[answer] || '...';
  }

  // ── Fibonacci Spiral Scheduler ───────────────────────────────────

  // Starting at 8: deeper imprint at A1, richer spiral at C2
  var FIBONACCI = [1, 2, 3, 5, 8, 13, 21, 34];

  /**
   * Given a template's spiral return config, generates spiral instances
   * for insertion into other destinations.
   *
   * @param {Object} template      — the core template
   * @param {number} homeDest      — destination number where template lives
   * @returns {Object[]}           — array of { destNumber, game } for spiral placement
   */
  function _generateSpiralInstances(template, homeDest) {
    var meta = template._template;
    if (!meta || !meta.spiralReturn || !meta.spiralReturn.length) return [];

    var instances = [];
    for (var i = 0; i < meta.spiralReturn.length; i++) {
      var offset = meta.spiralReturn[i];
      // spiralReturn values are forward offsets: [3, 6] on dest19 → dest22, dest25
      var targetDest = homeDest + offset;
      if (targetDest > 58) continue; // beyond final destination

      var adjusted = _applyDifficultyCurve(template, i + 1);
      adjusted._template = adjusted._template || {};
      adjusted._template.spiralFrom = homeDest;
      adjusted._template.spiralPass = i + 1;
      adjusted._template.isSpiral = true;

      instances.push({
        destNumber: targetDest,
        game: adjusted
      });
    }
    return instances;
  }

  // ── Main Generator ───────────────────────────────────────────────

  var TemplateGenerator = {};

  /**
   * Expand a template file into full destination content.
   *
   * @param {Object} templateFile  — the loaded template JSON (with meta, ecosystemVocabulary, templates)
   * @param {Object} [options]     — generation options
   * @param {boolean} options.includeEcoVariants  — generate ecosystem variants (default: true)
   * @param {boolean} options.includeInputModes   — generate input mode variants (default: false for build-time)
   * @param {boolean} options.includeSpiralGames  — include spiral return games (default: false)
   * @param {string[]} options.ecosystems         — which ecosystems to generate (default: all 8)
   * @returns {Object}             — expanded destination JSON
   */
  TemplateGenerator.expand = function (templateFile, options) {
    var opts = options || {};
    var includeEco = opts.includeEcoVariants !== false;
    var includeInputModes = opts.includeInputModes === true;
    var ecosystems = opts.ecosystems || ECOSYSTEMS;
    var ecoVocab = templateFile.ecosystemVocabulary || {};

    var destNumber = parseInt((templateFile.meta.destination || '').replace('dest', ''), 10) || 1;

    // Start with the destination shell
    var dest = {
      meta: JSON.parse(JSON.stringify(templateFile.meta)),
      preArrival: templateFile.preArrival || [],
      arrival: templateFile.arrival || {},
      games: [],
      departure: templateFile.departure || {},
      characterMeta: templateFile.characterMeta || {}
    };

    var templates = templateFile.templates || [];
    var spiralQueue = []; // games to inject into other destinations

    for (var t = 0; t < templates.length; t++) {
      var template = templates[t];
      var meta = template._template || {};

      // 1. Add the core template (narrative spine)
      var coreGame = _stripTemplateMeta(template);
      coreGame._template = {
        id: meta.id,
        layer: meta.layer || 'core',
        ecosystem: 'canonical'
      };
      dest.games.push(coreGame);

      // 2. Generate ecosystem variants
      if (includeEco && meta.ecosystemSlots && meta.ecosystemSlots.length) {
        for (var e = 0; e < ecosystems.length; e++) {
          var ecoVariant = _createEcoVariant(template, ecosystems[e], ecoVocab);
          if (ecoVariant) {
            var ecoGame = _stripTemplateMeta(ecoVariant);
            ecoGame._template = {
              id: meta.id + '_' + ecosystems[e],
              layer: meta.layer || 'core',
              ecosystem: ecosystems[e],
              isVariant: true,
              sourceId: meta.id
            };
            dest.games.push(ecoGame);
          }
        }
      }

      // 3. Generate input mode variants (runtime only, not for static build)
      if (includeInputModes && meta.inputModes) {
        for (var m = 0; m < meta.inputModes.length; m++) {
          var mode = meta.inputModes[m];
          if (mode === 'choice') continue; // choice is the default

          var modeVariant = _createInputModeVariant(template, mode);
          if (modeVariant) {
            var modeGame = _stripTemplateMeta(modeVariant);
            modeGame._template = {
              id: meta.id + '_' + mode,
              layer: meta.layer || 'core',
              inputMode: mode,
              sourceId: meta.id
            };
            dest.games.push(modeGame);
          }
        }
      }

      // 4. Collect spiral return instances
      var spirals = _generateSpiralInstances(template, destNumber);
      spiralQueue = spiralQueue.concat(spirals);
    }

    // Attach spiral queue as metadata (for the build pipeline to distribute)
    dest._spiralQueue = spiralQueue;

    // Stats
    dest._stats = {
      coreTemplates: templates.length,
      totalGames: dest.games.length,
      ecosystemVariants: dest.games.filter(function (g) {
        return g._template && g._template.isVariant;
      }).length,
      spiralGamesGenerated: spiralQueue.length,
      ecosystemsUsed: ecosystems.length
    };

    return dest;
  };

  /**
   * Strip internal _template metadata for the final game object,
   * preserving only what the engine needs.
   */
  function _stripTemplateMeta(game) {
    var clean = JSON.parse(JSON.stringify(game));
    // Keep _template for layer/ecosystem tagging, remove build-only fields
    if (clean._template) {
      delete clean._template.spiralReturn;
      delete clean._template.inputModes;
      delete clean._template.ecosystemSlots;
      delete clean._template.description;
      delete clean._template.order;
    }
    return clean;
  }

  /**
   * Organize games by layer for the engine's content loader.
   *
   * @param {Object} dest — expanded destination
   * @returns {Object}    — { narrative: [...], core: [...], skill: [...], advanced: [...], meta: [...] }
   */
  TemplateGenerator.organizeByLayer = function (dest) {
    var layers = {
      narrative: [],
      core: [],
      skill: [],
      advanced: [],
      meta: []
    };

    var games = dest.games || [];
    for (var i = 0; i < games.length; i++) {
      var game = games[i];
      var layer = (game._template && game._template.layer) || 'core';
      if (layers[layer]) {
        layers[layer].push(game);
      } else {
        layers.core.push(game);
      }
    }

    return layers;
  };

  /**
   * Get games for a specific ecosystem.
   *
   * @param {Object} dest      — expanded destination
   * @param {string} ecosystem — ecosystem name or 'canonical'
   * @returns {Object[]}
   */
  TemplateGenerator.getEcosystemGames = function (dest, ecosystem) {
    return (dest.games || []).filter(function (g) {
      var eco = g._template && g._template.ecosystem;
      return eco === ecosystem || eco === 'canonical';
    });
  };

  /**
   * Merge spiral games from other destinations into a target destination.
   *
   * @param {Object} targetDest      — the destination to receive spiral games
   * @param {Object[]} spiralGames   — array of spiral game instances
   * @returns {Object}               — targetDest with spiral games added
   */
  TemplateGenerator.injectSpiralGames = function (targetDest, spiralGames) {
    if (!spiralGames || !spiralGames.length) return targetDest;

    for (var i = 0; i < spiralGames.length; i++) {
      var sg = spiralGames[i];
      sg._template = sg._template || {};
      sg._template.layer = 'spiral';
      sg._template.isSpiral = true;
      targetDest.games.push(sg);
    }

    return targetDest;
  };

  /**
   * Generate a complete build manifest for all A1 destinations.
   * Takes an array of template files and produces the full content.
   *
   * @param {Object[]} templateFiles — array of loaded template JSONs (dest1–12)
   * @param {Object} [options]       — generation options
   * @returns {Object}               — { dest1: {...}, dest2: {...}, ... }
   */
  TemplateGenerator.buildA1 = function (templateFiles, options) {
    var result = {};
    var allSpirals = {}; // destNumber → [games]

    // Pass 1: Expand all templates and collect spiral queues
    for (var i = 0; i < templateFiles.length; i++) {
      var tf = templateFiles[i];
      var destKey = tf.meta.destination;
      var expanded = TemplateGenerator.expand(tf, options);

      // Collect spirals
      if (expanded._spiralQueue) {
        for (var s = 0; s < expanded._spiralQueue.length; s++) {
          var spiral = expanded._spiralQueue[s];
          var targetKey = 'dest' + spiral.destNumber;
          if (!allSpirals[targetKey]) allSpirals[targetKey] = [];
          allSpirals[targetKey].push(spiral.game);
        }
        delete expanded._spiralQueue;
      }

      result[destKey] = expanded;
    }

    // Pass 2: Inject spiral games into their target destinations
    for (var destKey2 in allSpirals) {
      if (allSpirals.hasOwnProperty(destKey2) && result[destKey2]) {
        TemplateGenerator.injectSpiralGames(result[destKey2], allSpirals[destKey2]);
      }
    }

    return result;
  };

  return TemplateGenerator;
}));
