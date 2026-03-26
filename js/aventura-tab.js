/**
 * AventuraTab — Renders "Mi aventura por Colombia" in the cuaderno
 *
 * Reads from CompositionBuilder and displays:
 *   - Progress bar (letters + words + sentences out of 89)
 *   - Three chapters (Mundo de Abajo / Medio / Arriba)
 *   - Phase mosaics (letter grid, word chain, sentence block)
 *   - Landmark cards with tourism info and student crónica
 *   - Final reveal ceremony when all 89 destinations complete
 *
 * Depends: composition-builder.js (CompositionBuilder)
 */
(function () {
  'use strict';

  // ── Icon map for landmarks ────────────────────────────────────────
  var ICONS = {
    casa: '&#127968;', estrella: '&#11088;', rana: '&#128056;', 'montaña': '&#9968;',
    arte: '&#127912;', bosque: '&#127794;', mercado: '&#127859;', flor: '&#127804;',
    'río': '&#127754;', libro: '&#128214;', universidad: '&#127891;', piedra: '&#129704;',
    'avión': '&#9992;', cueva: '&#128065;', iglesia: '&#9962;', palma: '&#127796;',
    'volcán': '&#127755;', puente: '&#128736;', 'caña': '&#127854;', museo: '&#127963;',
    catedral: '&#9962;', 'fósil': '&#129430;', 'joyería': '&#128142;', caballo: '&#128014;',
    'cañón': '&#127956;', 'frailejón': '&#127807;', estatua: '&#128509;', selva: '&#127795;',
    ballena: '&#128011;', isla: '&#127965;', muralla: '&#127984;', playa: '&#127958;',
    muelle: '&#9875;', escalera: '&#129534;', manglares: '&#127795;',
    'acordeón': '&#127929;', desierto: '&#127964;', faro: '&#128294;', mochila: '&#127890;',
    flamenco: '&#129435;', coral: '&#127800;', 'música': '&#127925;', marimba: '&#127929;',
    'delfín': '&#128044;', cascada: '&#128167;', rupestre: '&#129704;', maloca: '&#127969;',
    'café': '&#9749;', 'tiburón': '&#129416;', 'máscara': '&#127917;', tambor: '&#129345;',
    tortuga: '&#128034;', pirata: '&#9760;', arpa: '&#127930;', 'balón': '&#9917;',
    bandera: '&#127987;', hamaca: '&#128716;', sendero: '&#128694;', jaguar: '&#128006;',
    'default': '&#128205;'
  };

  var WORLD_ICONS = {
    mundoDeAbajo:  '&#127793;',
    mundoDelMedio: '&#128293;',
    mundoDeArriba: '&#11088;'
  };

  var WORLD_CLASS = {
    mundoDeAbajo:  'abajo',
    mundoDelMedio: 'medio',
    mundoDeArriba: 'arriba'
  };

  // ── Helpers ───────────────────────────────────────────────────────
  function _el(tag, className, html) {
    var el = document.createElement(tag);
    if (className) el.className = className;
    if (html !== undefined) el.innerHTML = html;
    return el;
  }

  function _icon(name) {
    return ICONS[name] || ICONS['default'];
  }

  // ── Render ────────────────────────────────────────────────────────
  var AventuraTab = {
    _rendered: false,

    render: function (rootId) {
      if (!window.CompositionBuilder) return;
      var root = document.getElementById(rootId);
      if (!root) return;

      root.innerHTML = '<div style="text-align:center;color:#b8a48a;padding:40px;">Cargando tu aventura...</div>';

      CompositionBuilder.getComposition(function (data) {
        root.innerHTML = '';
        AventuraTab._renderContent(root, data);
        AventuraTab._rendered = true;
      });
    },

    _renderContent: function (root, data) {
      var stats = data.stats;
      var composition = data.composition;

      // ── Title ──
      var titleEl = _el('div', '', '');
      titleEl.style.cssText = 'text-align:center;margin-bottom:24px;';
      titleEl.innerHTML =
        '<h2 style="font-family:Cormorant Garamond,Georgia,serif;font-size:24px;color:#c9a227;margin:0 0 4px;">' +
        'Mi aventura por Colombia</h2>' +
        '<p style="font-size:14px;color:#b8a48a;font-style:italic;margin:0;">' +
        'Tu viaje junto a Yaguará por las tierras de Colombia</p>';
      root.appendChild(titleEl);

      // ── Progress bar ──
      var progress = _el('div', 'av-progress', '');
      var pct = stats.destinationsVisited > 0 ? Math.round((stats.destinationsVisited / 89) * 100) : 0;
      progress.innerHTML =
        '<div style="font-size:13px;color:#b8a48a;">Progreso del viaje</div>' +
        '<div class="av-progress-bar"><div class="av-progress-fill" style="width:' + pct + '%;"></div></div>' +
        '<div class="av-progress-stats">' +
          '<div><span class="av-progress-stat-num">' + stats.lettersEarned + '</span> letras</div>' +
          '<div><span class="av-progress-stat-num">' + stats.wordsEarned + '</span> palabras</div>' +
          '<div><span class="av-progress-stat-num">' + stats.sentencesEarned + '</span> oraciones</div>' +
          '<div><span class="av-progress-stat-num">' + stats.totalWordsWritten + '</span> escritas</div>' +
        '</div>';
      root.appendChild(progress);

      // ── Chapters ──
      composition.forEach(function (chapter) {
        var chEl = _el('div', 'av-chapter', '');
        var world = chapter.world;

        // Chapter header
        var header = _el('div', 'av-chapter-header', '');
        var iconEl = _el('div', 'av-chapter-icon ' + (WORLD_CLASS[world.id] || ''),
          WORLD_ICONS[world.id] || '');
        header.appendChild(iconEl);
        var titleBlock = _el('div', '', '');
        titleBlock.innerHTML =
          '<div class="av-chapter-title">' + world.label + '</div>' +
          '<div class="av-chapter-subtitle">' + world.subtitle + '</div>';
        header.appendChild(titleBlock);
        chEl.appendChild(header);

        // Phase mosaic
        var mosaic = _el('div', 'av-mosaic', '');
        var phase = CompositionBuilder.getProgress();
        if (world.id === 'mundoDeAbajo') {
          mosaic.innerHTML =
            '<div class="av-mosaic-label">Letras ganadas</div>' +
            '<div class="av-letters">' + (chapter.letterMosaic || '&middot; &middot; &middot;') + '</div>';
        } else if (world.id === 'mundoDelMedio') {
          var wordHtml = chapter.wordChain.length > 0
            ? chapter.wordChain.join(' &middot; ')
            : '&middot; &middot; &middot;';
          mosaic.innerHTML =
            '<div class="av-mosaic-label">Palabras ganadas</div>' +
            '<div class="av-words">' + wordHtml + '</div>';
        } else {
          mosaic.innerHTML =
            '<div class="av-mosaic-label">Tu composición</div>' +
            '<div class="av-sentences">' + (chapter.sentenceBlock || '<em style="color:#8a7a6a;">Escribe y resuelve adivinanzas para construir tu texto...</em>') + '</div>';
        }
        chEl.appendChild(mosaic);

        // Landmark cards
        chapter.entries.forEach(function (entry) {
          var lm = entry.landmark;
          if (!lm) return;

          var card = _el('div', 'av-landmark ' + (entry.completed ? 'visited' : 'unvisited'), '');

          // Top: icon + name + region
          var top = _el('div', 'av-landmark-top', '');
          var pin = _el('div', 'av-landmark-pin', _icon(lm.icon));
          top.appendChild(pin);
          var info = _el('div', '', '');
          info.innerHTML =
            '<div class="av-landmark-name">' + lm.name + '</div>' +
            '<div class="av-landmark-region">Destino ' + entry.dest + ' &middot; ' + lm.region + '</div>';
          top.appendChild(info);
          card.appendChild(top);

          // Description
          if (entry.completed) {
            var desc = _el('div', 'av-landmark-desc', lm.description);
            card.appendChild(desc);

            // Tourism tip
            var tourism = _el('div', 'av-landmark-tourism', lm.tourism);
            card.appendChild(tourism);

            // Student's crónica
            if (entry.cronica) {
              var cronicaEl = _el('div', 'av-landmark-cronica', '');
              cronicaEl.innerHTML =
                '<div class="av-landmark-cronica-label">Lo que escribiste</div>' +
                entry.cronica;
              card.appendChild(cronicaEl);
            }

            // Riddle reward
            if (entry.reward) {
              var rewardEl = _el('div', 'av-landmark-reward', '');
              if (entry.reward.type === 'letter') {
                rewardEl.innerHTML = 'Letra ganada: <strong>' + entry.reward.letter + '</strong>';
              } else if (entry.reward.type === 'word') {
                rewardEl.innerHTML = 'Palabra ganada: <em>' + entry.reward.word + '</em>';
              } else if (entry.reward.type === 'sentence') {
                rewardEl.innerHTML = entry.reward.sentence;
              }
              card.appendChild(rewardEl);
            }
          }

          chEl.appendChild(card);
        });

        root.appendChild(chEl);
      });

      // ── Final reveal (if complete) ──
      if (stats.isComplete) {
        var reveal = _el('div', 'av-reveal', '');
        reveal.innerHTML =
          '<div class="av-reveal-title">Mi aventura por Colombia</div>' +
          '<div class="av-reveal-sub">Tu viaje est&aacute; completo. 89 destinos. Tu historia.</div>' +
          '<button class="av-export-btn" id="avExportBtn">Descargar mi aventura</button>';
        root.appendChild(reveal);

        setTimeout(function () {
          var btn = document.getElementById('avExportBtn');
          if (btn) {
            btn.addEventListener('click', function () {
              CompositionBuilder.exportText(function (text) {
                var blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'mi-aventura-por-colombia.txt';
                a.click();
                URL.revokeObjectURL(url);
              });
            });
          }
        }, 100);
      }

      // ── Empty state ──
      if (stats.destinationsVisited === 0) {
        var empty = _el('div', 'cn-empty', '');
        empty.style.marginTop = '24px';
        empty.innerHTML =
          '<div class="cn-empty-icon">&#128205;</div>' +
          '<div>Tu aventura comienza cuando escribes tu primera crónica.<br>' +
          'Cada destino revela un lugar real de Colombia.</div>';
        root.appendChild(empty);
      }
    }
  };

  // ── Expose globally ───────────────────────────────────────────────
  if (typeof window !== 'undefined') {
    window.AventuraTab = AventuraTab;
  }

})();
