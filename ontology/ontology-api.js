/**
 * YaguaraOntology — Accessor for the living ontology
 *
 * Makes meaning queryable:
 *   - What linguistic targets does a learning objective require?
 *   - What objectives exist at B1?
 *   - What characters appear at dest14?
 *   - What cultural concepts belong to the bosque ecosystem?
 *
 * Usage:
 *   const onto = new YaguaraOntology();
 *   await onto.load();
 *   onto.getObjectivesForLevel('A1');
 *   onto.getTargetsForObjective('lo_introduce_self');
 *   onto.getCharactersAtDestination('dest8');
 */

(function (root) {
  'use strict';

  function YaguaraOntology(basePath) {
    this._basePath = basePath || '/ontology/';
    this._entities = {};    // all entities by id
    this._byType = {};      // { TypeName: Map(id → entity) }
    this._loaded = false;
  }

  // ─── Loading ───────────────────────────────────────────────

  YaguaraOntology.prototype.load = function () {
    var self = this;
    return Promise.all([
      _fetch(self._basePath + 'linguistic-targets.json'),
      _fetch(self._basePath + 'learning-objectives.json'),
      _fetch(self._basePath + 'story-world.json'),
      _fetch(self._basePath + 'activities.json')
    ]).then(function (results) {
      var targets = results[0];
      var objectives = results[1];
      var world = results[2];
      var activities = results[3];

      _index(self, targets.filter(_hasId), 'LinguisticTarget');
      _index(self, objectives.filter(_hasId), 'LearningObjective');
      _index(self, activities.filter(_hasId), 'Activity');
      _index(self, world.characters || [], 'Character');
      _index(self, world.ecosystems || [], 'Ecosystem');
      _index(self, world.places || [], 'Place');
      _index(self, world.storyNodes || [], 'StoryNode');
      _index(self, world.culturalConcepts || [], 'CulturalConcept');
      _index(self, world.mythologicalEntities || [], 'MythologicalEntity');

      self._loaded = true;
      return self;
    });
  };

  // ─── Get by ID ─────────────────────────────────────────────

  YaguaraOntology.prototype.get = function (id) {
    return this._entities[id] || null;
  };

  // ─── Get all of a type ─────────────────────────────────────

  YaguaraOntology.prototype.all = function (typeName) {
    var map = this._byType[typeName];
    return map ? Array.from(map.values()) : [];
  };

  // ─── Linguistic Targets ────────────────────────────────────

  YaguaraOntology.prototype.getTarget = function (id) {
    return (this._byType.LinguisticTarget || new Map()).get(id) || null;
  };

  YaguaraOntology.prototype.getTargetsForLevel = function (cefr) {
    return this.all('LinguisticTarget').filter(function (t) { return t.cefr === cefr; });
  };

  YaguaraOntology.prototype.getTargetsByCategory = function (category) {
    return this.all('LinguisticTarget').filter(function (t) { return t.category === category; });
  };

  YaguaraOntology.prototype.getTargetsByTag = function (tag) {
    return this.all('LinguisticTarget').filter(function (t) {
      return t.tags && t.tags.indexOf(tag) !== -1;
    });
  };

  // ─── Learning Objectives ───────────────────────────────────

  YaguaraOntology.prototype.getObjective = function (id) {
    return (this._byType.LearningObjective || new Map()).get(id) || null;
  };

  YaguaraOntology.prototype.getObjectivesForLevel = function (cefr) {
    return this.all('LearningObjective').filter(function (o) { return o.cefr === cefr; });
  };

  YaguaraOntology.prototype.getObjectivesForBlock = function (block) {
    return this.all('LearningObjective').filter(function (o) { return o.block === block; });
  };

  YaguaraOntology.prototype.getTargetsForObjective = function (objectiveId) {
    var self = this;
    var obj = this.getObjective(objectiveId);
    if (!obj || !obj.targets) return [];
    return obj.targets.map(function (id) { return self.getTarget(id); }).filter(Boolean);
  };

  // Reverse: which objectives practice a given target?
  YaguaraOntology.prototype.getObjectivesForTarget = function (targetId) {
    return this.all('LearningObjective').filter(function (o) {
      return o.targets && o.targets.indexOf(targetId) !== -1;
    });
  };

  // ─── Activities ─────────────────────────────────────────────

  YaguaraOntology.prototype.getActivity = function (id) {
    return (this._byType.Activity || new Map()).get(id) || null;
  };

  YaguaraOntology.prototype.getActivitiesForDestination = function (destId) {
    return this.all('Activity').filter(function (a) { return a.destination === destId; });
  };

  YaguaraOntology.prototype.getActivitiesForTarget = function (targetId) {
    return this.all('Activity').filter(function (a) {
      return a.practices && a.practices.indexOf(targetId) !== -1;
    });
  };

  YaguaraOntology.prototype.getActivitiesForObjective = function (objectiveId) {
    return this.all('Activity').filter(function (a) { return a.objective === objectiveId; });
  };

  YaguaraOntology.prototype.getActivitiesForLevel = function (cefr) {
    return this.all('Activity').filter(function (a) { return a.cefr === cefr; });
  };

  YaguaraOntology.prototype.getActivitiesForGameType = function (gameType) {
    return this.all('Activity').filter(function (a) { return a.gameType === gameType; });
  };

  // Returns all activities for a target, sorted by destination number — shows meaning evolution
  YaguaraOntology.prototype.getMeaningSpiral = function (targetId) {
    return this.getActivitiesForTarget(targetId).sort(function (a, b) {
      var numA = parseInt((a.destination || '').replace('dest', ''), 10) || 0;
      var numB = parseInt((b.destination || '').replace('dest', ''), 10) || 0;
      return numA - numB;
    });
  };

  // ─── Story World ───────────────────────────────────────────

  YaguaraOntology.prototype.getCharacter = function (id) {
    return (this._byType.Character || new Map()).get(id) || null;
  };

  YaguaraOntology.prototype.getCharactersAtLevel = function (cefr) {
    return this.all('Character').filter(function (c) { return c.introducedAt === cefr; });
  };

  YaguaraOntology.prototype.getCharactersAtDestination = function (destId) {
    var nodes = this.all('StoryNode').filter(function (s) { return s.destination === destId; });
    var charIds = {};
    var self = this;
    nodes.forEach(function (n) {
      (n.characters || []).forEach(function (id) { charIds[id] = true; });
    });
    return Object.keys(charIds).map(function (id) { return self.getCharacter(id); }).filter(Boolean);
  };

  YaguaraOntology.prototype.getEcosystem = function (id) {
    return (this._byType.Ecosystem || new Map()).get(id) || null;
  };

  YaguaraOntology.prototype.getPlacesForEcosystem = function (ecoId) {
    return this.all('Place').filter(function (p) { return p.ecosystem === ecoId; });
  };

  YaguaraOntology.prototype.getCulturalConceptsForEcosystem = function (ecoId) {
    return this.all('CulturalConcept').filter(function (c) { return c.ecosystem === ecoId; });
  };

  // ─── Story Nodes ───────────────────────────────────────────

  YaguaraOntology.prototype.getStoryNode = function (destId) {
    return this.all('StoryNode').filter(function (s) { return s.destination === destId; })[0] || null;
  };

  YaguaraOntology.prototype.getStoryNodesForAct = function (act) {
    return this.all('StoryNode').filter(function (s) { return s.act === act; });
  };

  YaguaraOntology.prototype.getStoryNodesForLevel = function (cefr) {
    return this.all('StoryNode').filter(function (s) { return s.cefr === cefr; });
  };

  // ─── Cross-cutting queries ─────────────────────────────────

  // Given a destination, what should the student learn?
  YaguaraOntology.prototype.getObjectivesForDestination = function (destId) {
    var node = this.getStoryNode(destId);
    if (!node || !node.objectives) return [];
    var self = this;
    return node.objectives.map(function (id) { return self.getObjective(id); }).filter(Boolean);
  };

  // Given a destination, what linguistic targets are practiced?
  YaguaraOntology.prototype.getTargetsForDestination = function (destId) {
    var objectives = this.getObjectivesForDestination(destId);
    var seen = {};
    var result = [];
    var self = this;
    objectives.forEach(function (obj) {
      (obj.targets || []).forEach(function (tid) {
        if (!seen[tid]) {
          seen[tid] = true;
          var t = self.getTarget(tid);
          if (t) result.push(t);
        }
      });
    });
    return result;
  };

  // What skills does a destination develop?
  YaguaraOntology.prototype.getSkillsForDestination = function (destId) {
    var objectives = this.getObjectivesForDestination(destId);
    var skills = {};
    objectives.forEach(function (obj) {
      (obj.skills || []).forEach(function (s) { skills[s] = true; });
    });
    return Object.keys(skills);
  };

  // ─── Stats ─────────────────────────────────────────────────

  YaguaraOntology.prototype.stats = function () {
    var self = this;
    var result = {};
    Object.keys(self._byType).forEach(function (type) {
      result[type] = self._byType[type].size;
    });
    return result;
  };

  // ─── Internals ─────────────────────────────────────────────

  function _fetch(url) {
    return fetch(url).then(function (r) {
      if (!r.ok) throw new Error('Ontology load failed: ' + url + ' (' + r.status + ')');
      return r.json();
    });
  }

  function _hasId(item) { return item && item.id; }

  function _index(onto, items, typeName) {
    if (!onto._byType[typeName]) onto._byType[typeName] = new Map();
    var map = onto._byType[typeName];
    items.forEach(function (item) {
      map.set(item.id, item);
      onto._entities[item.id] = item;
    });
  }

  // ─── Export ────────────────────────────────────────────────

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = YaguaraOntology;
  } else {
    root.YaguaraOntology = YaguaraOntology;
  }

})(typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : this);
