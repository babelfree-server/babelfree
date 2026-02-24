/**
 * LinguisticTargetResolver
 *
 * Bridges ontology LinguisticTargets to concrete Spanish forms.
 *
 * Takes:  { id, category, lexicalItem, features }  (from ontology)
 * Uses:   conjugations_es_processed.json            (verb data)
 * Returns: resolved form(s)
 *
 * Usage:
 *   var resolver = new LinguisticTargetResolver(verbData);
 *
 *   // Single form
 *   resolver.resolve(ontology.getTarget('lt_verb_tener_imperative_negative'));
 *   // → { form: "no tengas", lemma: "tener", person: "tú" }
 *
 *   // Full paradigm (no person in LT)
 *   resolver.resolve(ontology.getTarget('lt_verb_ser_indicative_present'));
 *   // → { paradigm: { "yo": "soy", "tú": "eres", ... }, lemma: "ser" }
 *
 *   // Activity → all resolved targets
 *   resolver.resolveActivity(activity, ontology);
 *   // → [{ form: "no tengas", ... }]
 */

(function (root) {
  'use strict';

  // ─── Person keys ───────────────────────────────────────────

  var PERSONS_FULL = [
    'yo', 'tú', 'él/ella/usted', 'nosotros', 'vosotros', 'ellos/ellas/ustedes'
  ];

  var PERSONS_IMPERATIVE = [
    'tú', 'usted', 'nosotros', 'vosotros', 'ustedes'
  ];

  // Map indicative-style person keys to imperative-style keys
  var IMPERATIVE_PERSON_MAP = {
    'yo': null,
    'tú': 'tú',
    'él/ella/usted': 'usted',
    'usted': 'usted',
    'nosotros': 'nosotros',
    'vosotros': 'vosotros',
    'ellos/ellas/ustedes': 'ustedes',
    'ustedes': 'ustedes'
  };

  // Reflexive pronoun mapping by person
  var REFLEXIVE_PRONOUNS = {
    'yo': 'me',
    'tú': 'te',
    'él/ella/usted': 'se',
    'usted': 'se',
    'nosotros': 'nos',
    'vosotros': 'os',
    'ellos/ellas/ustedes': 'se',
    'ustedes': 'se'
  };

  // ─── Constructor ───────────────────────────────────────────

  function LinguisticTargetResolver(verbData) {
    if (!verbData || typeof verbData !== 'object') {
      throw new Error('LinguisticTargetResolver requires verb data object');
    }
    this._verbs = verbData;
  }

  // ─── Shape assertion ───────────────────────────────────────

  LinguisticTargetResolver.prototype.assertShape = function (target) {
    if (!target || !target.id) {
      throw new Error('LinguisticTarget missing id');
    }
    if (!target.category) {
      throw new Error('LinguisticTarget missing category: ' + target.id);
    }
    if (target.category === 'verb') {
      if (!target.lexicalItem || !target.lexicalItem.lemma) {
        throw new Error('Verb LinguisticTarget missing lexicalItem.lemma: ' + target.id);
      }
      if (!target.features || !target.features.mood || !target.features.tense) {
        throw new Error('Verb LinguisticTarget missing features.mood or features.tense: ' + target.id);
      }
    }
  };

  // ─── Main resolve ──────────────────────────────────────────

  LinguisticTargetResolver.prototype.resolve = function (target) {
    this.assertShape(target);

    switch (target.category) {
      case 'verb':    return this._resolveVerb(target);
      case 'grammar': return this._resolveGrammar(target);
      case 'vocab':   return this._resolveVocab(target);
      default:
        return { id: target.id, category: target.category, resolved: false };
    }
  };

  // ─── Verb resolution ───────────────────────────────────────

  LinguisticTargetResolver.prototype._resolveVerb = function (target) {
    var lemma = target.lexicalItem.lemma;
    var mood = target.features.mood;
    var tense = target.features.tense;
    var person = target.features.person || null;

    // Reflexivity lives on the lexical item, not discovered at runtime
    var isReflexive = !!target.lexicalItem.reflexive;

    // Lookup verb by lemma (which should be the base form, e.g. "llamar" not "llamarse")
    var verb = this._verbs[lemma];
    var baseLemma = lemma;

    // Fallback: if someone passes a -se lemma without lexicalItem.reflexive, strip it
    if (!verb && lemma.match(/[aei]rse$/)) {
      baseLemma = lemma.replace(/se$/, '');
      verb = this._verbs[baseLemma];
      isReflexive = true;
    }

    if (!verb) {
      throw new Error('Verb not found in conjugation data: ' + lemma);
    }

    // Paradigm resolution (no specific person → all forms)
    if (!person) {
      return this._resolveParadigm(target, verb, mood, tense, isReflexive);
    }

    // Single form resolution
    var form = this._lookupForm(verb, mood, tense, person);

    // Fallback: derive negative imperative from subjunctive.present
    if (form === null && mood === 'imperative' && tense === 'negative') {
      form = this._deriveNegativeImperative(verb, person);
    }

    if (form === null) {
      throw new Error(
        'Unresolvable LinguisticTarget: ' + target.id +
        ' (' + lemma + '.' + mood + '.' + tense + '.' + person + ')'
      );
    }

    // Prepend reflexive pronoun if needed
    if (isReflexive) {
      form = _addReflexivePronoun(form, person, mood);
    }

    return {
      id: target.id,
      category: 'verb',
      resolved: true,
      form: form,
      lemma: lemma,
      baseLemma: baseLemma !== lemma ? baseLemma : undefined,
      reflexive: isReflexive || undefined,
      mood: mood,
      tense: tense,
      person: person,
      cefr: target.cefr
    };
  };

  LinguisticTargetResolver.prototype._lookupForm = function (verb, mood, tense, person) {
    if (mood === 'imperative') {
      // Data shape: conjugations.imperative.{affirmative|negative}.{person}
      var impPerson = IMPERATIVE_PERSON_MAP[person] || person;
      return verb.conjugations &&
             verb.conjugations.imperative &&
             verb.conjugations.imperative[tense] &&
             verb.conjugations.imperative[tense][impPerson] || null;
    }

    // Data shape: conjugations.{mood}.{tense}.{person}
    return verb.conjugations &&
           verb.conjugations[mood] &&
           verb.conjugations[mood][tense] &&
           verb.conjugations[mood][tense][person] || null;
  };

  LinguisticTargetResolver.prototype._deriveNegativeImperative = function (verb, person) {
    // Negative imperative = "no " + subjunctive present form
    var impPerson = IMPERATIVE_PERSON_MAP[person] || person;
    var subjForm = verb.conjugations &&
                   verb.conjugations.subjunctive &&
                   verb.conjugations.subjunctive.present &&
                   verb.conjugations.subjunctive.present[impPerson];

    if (!subjForm) {
      // Try full person key for subjunctive
      var fullPerson = _imperativeToFullPerson(impPerson);
      subjForm = verb.conjugations &&
                 verb.conjugations.subjunctive &&
                 verb.conjugations.subjunctive.present &&
                 verb.conjugations.subjunctive.present[fullPerson];
    }

    return subjForm ? 'no ' + subjForm : null;
  };

  LinguisticTargetResolver.prototype._resolveParadigm = function (target, verb, mood, tense, isReflexive) {
    var persons = (mood === 'imperative') ? PERSONS_IMPERATIVE : PERSONS_FULL;
    var forms = {};
    var self = this;

    persons.forEach(function (p) {
      var form = self._lookupForm(verb, mood, tense, p);
      if (form !== null && isReflexive) {
        form = _addReflexivePronoun(form, p, mood);
      }
      forms[p] = form;
    });

    return {
      id: target.id,
      category: 'verb',
      resolved: true,
      paradigm: forms,
      lemma: target.lexicalItem.lemma,
      reflexive: isReflexive || undefined,
      mood: mood,
      tense: tense,
      cefr: target.cefr
    };
  };

  // ─── Grammar resolution ────────────────────────────────────

  LinguisticTargetResolver.prototype._resolveGrammar = function (target) {
    return {
      id: target.id,
      category: 'grammar',
      resolved: true,
      pattern: target.lexicalItem ? target.lexicalItem.pattern : null,
      features: target.features,
      cefr: target.cefr
    };
  };

  // ─── Vocab resolution ──────────────────────────────────────

  LinguisticTargetResolver.prototype._resolveVocab = function (target) {
    return {
      id: target.id,
      category: 'vocab',
      resolved: true,
      items: target.lexicalItem ? target.lexicalItem.items : [],
      features: target.features,
      cefr: target.cefr
    };
  };

  // ─── Activity resolution ───────────────────────────────────
  //
  // Takes an Activity object (with .practices[]) and an ontology instance.
  // Returns all resolved targets for that activity.

  LinguisticTargetResolver.prototype.resolveActivity = function (activity, ontology) {
    if (!activity || !activity.practices) return [];

    var self = this;
    return activity.practices.map(function (targetId) {
      var target = ontology.getTarget(targetId);
      if (!target) {
        return { id: targetId, resolved: false, error: 'Target not found in ontology' };
      }
      try {
        return self.resolve(target);
      } catch (e) {
        return { id: targetId, resolved: false, error: e.message };
      }
    });
  };

  // ─── Raw verb data accessor ─────────────────────────────────
  //
  // Returns the raw VerbEntry from conjugation data.
  // Handles reflexive lemma stripping (llamarse → llamar).
  // Used by the engine to hydrate conjugation game data.

  LinguisticTargetResolver.prototype.getVerbData = function (lemma) {
    var verb = this._verbs[lemma];
    // Fallback for legacy -se lemmas (canonical form uses base lemma + lexicalItem.reflexive)
    if (!verb && lemma.match(/[aei]rse$/)) {
      verb = this._verbs[lemma.replace(/se$/, '')];
    }
    return verb || null;
  };

  // ─── Batch: resolve all targets at a CEFR level ────────────

  LinguisticTargetResolver.prototype.resolveLevel = function (cefr, ontology) {
    var targets = ontology.getTargetsForLevel(cefr);
    var self = this;
    return targets.map(function (t) {
      try { return self.resolve(t); }
      catch (e) { return { id: t.id, resolved: false, error: e.message }; }
    });
  };

  // ─── Distractors ───────────────────────────────────────────
  //
  // For a verb LT with a specific person+form, generate plausible wrong answers.
  // Used by fill/choice games. NOT game logic — just form generation.

  LinguisticTargetResolver.prototype.getDistractors = function (target, count) {
    if (target.category !== 'verb' || !target.features.person) return [];

    var lemma = target.lexicalItem.lemma;
    var mood = target.features.mood;
    var tense = target.features.tense;
    var correctPerson = target.features.person;
    var verb = this._verbs[lemma];
    if (!verb) return [];

    var self = this;
    var correct = this._lookupForm(verb, mood, tense, correctPerson);
    var persons = (mood === 'imperative') ? PERSONS_IMPERATIVE : PERSONS_FULL;

    // Collect other person forms as distractors
    var distractors = [];
    persons.forEach(function (p) {
      if (p === correctPerson) return;
      var form = self._lookupForm(verb, mood, tense, p);
      if (form && form !== correct) {
        distractors.push(form);
      }
    });

    // Also try the indicative present form (common false friend for imperatives)
    if (mood === 'imperative') {
      var indicForm = this._lookupForm(verb, 'indicative', 'present', correctPerson);
      if (indicForm && indicForm !== correct && distractors.indexOf(indicForm) === -1) {
        distractors.push(indicForm);
      }
    }

    // Shuffle and limit
    _shuffle(distractors);
    return distractors.slice(0, count || 3);
  };

  // ─── Helpers ───────────────────────────────────────────────

  function _addReflexivePronoun(form, person, mood) {
    var pronoun = REFLEXIVE_PRONOUNS[person] || 'se';

    if (mood === 'imperative') {
      // Negative imperative: "no" + pronoun + verb → "no se llame"
      if (form.indexOf('no ') === 0) {
        var verbPart = form.slice(3);
        return 'no ' + pronoun + ' ' + verbPart;
      }
      // Affirmative imperative: verb + pronoun suffix → "llámese"
      // (Simplified — enclitic attachment has accent rules;
      //  use stored forms when available, this is the fallback)
      return form + pronoun;
    }

    // Indicative/subjunctive: pronoun + verb → "me llamo"
    return pronoun + ' ' + form;
  }

  function _imperativeToFullPerson(shortPerson) {
    var map = {
      'usted': 'él/ella/usted',
      'ustedes': 'ellos/ellas/ustedes'
    };
    return map[shortPerson] || shortPerson;
  }

  function _shuffle(arr) {
    for (var i = arr.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
    }
    return arr;
  }

  // ─── Export ────────────────────────────────────────────────

  // Constants available for external use
  LinguisticTargetResolver.PERSONS_FULL = PERSONS_FULL;
  LinguisticTargetResolver.PERSONS_IMPERATIVE = PERSONS_IMPERATIVE;
  LinguisticTargetResolver.IMPERATIVE_PERSON_MAP = IMPERATIVE_PERSON_MAP;

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = LinguisticTargetResolver;
  } else {
    root.LinguisticTargetResolver = LinguisticTargetResolver;
  }

})(typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : this);
