/**
 * SOUL — The emotional layer of El Viaje del Jaguar
 *
 * No scores. No percentages. No numbers.
 * Only Yaguará's voice, the ecosystem breathing, and the rana growing visible.
 *
 * This file enhances the existing engine without modifying it.
 * Load AFTER yaguara-engine.js.
 */

(function (window) {
  'use strict';

  /* ═══════════════════════════════════════════════════
     1. RESPONSE-AWARE YAGUARÁ LINES
     Not "you got 80%" — but "the forest heard you"
  ═══════════════════════════════════════════════════ */

  var SOUL_LINES = {
    /* When the student flows — everything lands naturally */
    flow: [
      'El bosque te escuchó.',
      'Así se nombra el mundo.',
      'Las palabras confían en ti.',
      'El río canta más fuerte cuando hablas.',
      'Cada nombre que dices enciende una luz.',
      'La ceiba sonríe. Las raíces lo sienten.',
      'Estás nombrando. Estás creando.',
      'Sigue. El camino se abre.'
    ],
    /* When the student struggles — never shame, always patience */
    patience: [
      'El bosque es paciente. Tú también.',
      'La rana sin dientes tampoco pudo la primera vez.',
      'Cada error es una raíz que crece.',
      'Las palabras vuelven. Siempre vuelven.',
      'Respira. La selva respira contigo.',
      'No hay prisa. El nombre te va a encontrar.',
      'Doña Asunción escuchó mil veces antes de hablar una.',
      'El Cóndor también aprendió a volar cayendo.'
    ],
    /* El Maestro — loading screen quotes between destinations */
    maestro: [
      'Nombrar es crear.',
      'Las palabras no se aprenden. Se habitan.',
      'Quien nombra, decide qué existe.',
      'La rana sin dientes puede sostener palabras.',
      'Irse no es olvidar. Irse es llevar el lugar adentro.',
      'Sin nombre, la rana desaparece un poco cada día.',
      'Los sueños necesitan nombres para hacerse reales.',
      'El río no espera. Las palabras tampoco.',
      'Cada idioma ve una realidad diferente.',
      'Lo que no se nombra, se olvida. Lo olvidado, desaparece.',
      'Las lenguas no se conservan. Se hablan.',
      'El silencio también es un idioma. El más difícil.'
    ]
  };

  /* ═══════════════════════════════════════════════════
     2. BREATHING PADDLE — the moment between games
  ═══════════════════════════════════════════════════ */

  function showBreathingPaddle(container, mood, callback) {
    if (!container) { if (callback) callback(); return; }

    /* Choose line based on mood */
    var pool = (mood === 'flow') ? SOUL_LINES.flow : SOUL_LINES.patience;
    var line = pool[Math.floor(Math.random() * pool.length)];

    /* Build the paddle */
    var paddle = document.createElement('div');
    paddle.className = 'soul-paddle';
    paddle.innerHTML =
      '<div class="soul-paddle-inner">' +
        '<div class="soul-paddle-avatar">🐆</div>' +
        '<div class="soul-paddle-text">' + line + '</div>' +
      '</div>';

    container.innerHTML = '';
    container.appendChild(paddle);

    /* Animate in */
    requestAnimationFrame(function () {
      paddle.classList.add('soul-paddle-visible');
    });

    /* TTS */
    if (window.speechSynthesis) {
      var utterance = new SpeechSynthesisUtterance(line);
      utterance.lang = 'es-CO';
      utterance.rate = 0.85;
      utterance.pitch = 0.95;
      /* Try to find a Spanish voice */
      var voices = speechSynthesis.getVoices();
      for (var i = 0; i < voices.length; i++) {
        if (voices[i].lang && voices[i].lang.indexOf('es') === 0) {
          utterance.voice = voices[i];
          break;
        }
      }
      speechSynthesis.speak(utterance);
    }

    /* Fade out after 3 seconds */
    setTimeout(function () {
      paddle.classList.remove('soul-paddle-visible');
      paddle.classList.add('soul-paddle-exit');
      setTimeout(function () {
        if (container.contains(paddle)) container.removeChild(paddle);
        if (callback) callback();
      }, 600);
    }, 3200);
  }

  /* ═══════════════════════════════════════════════════
     3. ECOSYSTEM BACKGROUND — the world breathes
  ═══════════════════════════════════════════════════ */

  var ECO_GRADIENTS = {
    bosque:   'linear-gradient(180deg, #1a3a0a 0%, #2D5016 50%, #1a3a0a 100%)',
    costa:    'linear-gradient(180deg, #0a3d5c 0%, #1B6B8A 50%, #0a3d5c 100%)',
    desierto: 'linear-gradient(180deg, #8B6914 0%, #C49A3C 50%, #8B6914 100%)',
    islas:    'linear-gradient(180deg, #0d7377 0%, #1AA5A5 50%, #0d7377 100%)',
    llanos:   'linear-gradient(180deg, #8B6914 0%, #D4A843 50%, #8B6914 100%)',
    nevada:   'linear-gradient(180deg, #4a5568 0%, #a0aec0 50%, #4a5568 100%)',
    selva:    'linear-gradient(180deg, #0a2e0a 0%, #1a5a1a 50%, #0a2e0a 100%)',
    sierra:   'linear-gradient(180deg, #5C3317 0%, #8B6914 50%, #5C3317 100%)'
  };

  var ECO_WORLD_DEFAULTS = {
    mundoDeAbajo:  'selva',
    mundoDelMedio: 'llanos',
    mundoDeArriba: 'nevada'
  };

  function applyEcosystemBackground(ecosystem, world) {
    var eco = ecosystem || ECO_WORLD_DEFAULTS[world] || 'selva';
    var gradient = ECO_GRADIENTS[eco] || ECO_GRADIENTS.selva;

    var gameContainer = document.querySelector('.yg-game-area') ||
                        document.querySelector('.yg-encounter') ||
                        document.getElementById('gameContainer');

    if (gameContainer) {
      gameContainer.style.background = gradient;
      gameContainer.style.transition = 'background 1.5s ease';
    }

    /* Also set a subtle body overlay */
    document.body.setAttribute('data-ecosystem', eco);
  }

  /* ═══════════════════════════════════════════════════
     4. EL MAESTRO LOADING SCREEN — between destinations
  ═══════════════════════════════════════════════════ */

  function showMaestroQuote(container, callback) {
    if (!container) { if (callback) callback(); return; }

    var line = SOUL_LINES.maestro[Math.floor(Math.random() * SOUL_LINES.maestro.length)];

    var screen = document.createElement('div');
    screen.className = 'soul-maestro-screen';
    screen.innerHTML =
      '<div class="soul-maestro-inner">' +
        '<div class="soul-maestro-quote">"' + line + '"</div>' +
        '<div class="soul-maestro-attribution">— El Maestro</div>' +
      '</div>';

    container.innerHTML = '';
    container.appendChild(screen);

    requestAnimationFrame(function () {
      screen.classList.add('soul-maestro-visible');
    });

    setTimeout(function () {
      screen.classList.remove('soul-maestro-visible');
      screen.classList.add('soul-maestro-exit');
      setTimeout(function () {
        if (container.contains(screen)) container.removeChild(screen);
        if (callback) callback();
      }, 800);
    }, 3500);
  }

  /* ═══════════════════════════════════════════════════
     5. RANA VISIBILITY — grows with riddle progress
  ═══════════════════════════════════════════════════ */

  function updateRanaVisibility() {
    if (!window.RiddleQuest) return;

    var state = RiddleQuest.getState ? RiddleQuest.getState() : null;
    if (!state) return;

    var solved = (state.solvedRiddles || []).length;
    var total = 89;
    var opacity = Math.min(solved / total, 1);

    /* Find rana elements */
    var ranas = document.querySelectorAll('.soul-rana, .yg-rana, [data-character="rana"]');
    for (var i = 0; i < ranas.length; i++) {
      ranas[i].style.opacity = Math.max(0.05, opacity);
      ranas[i].style.filter = 'saturate(' + (opacity * 100) + '%)';
      ranas[i].style.transition = 'opacity 2s ease, filter 2s ease';
    }

    /* Also update the bridge in the cuaderno if visible */
    var bridge = document.getElementById('riddleBridge');
    if (bridge) {
      bridge.style.setProperty('--bridge-progress', (opacity * 100) + '%');
    }
  }

  /* ═══════════════════════════════════════════════════
     6. HOOK INTO THE ENGINE — monkey-patch transitions
  ═══════════════════════════════════════════════════ */

  function hookEngine() {
    if (!window.YaguaraEngine) {
      /* Engine not loaded yet — retry */
      setTimeout(hookEngine, 500);
      return;
    }

    var engine = YaguaraEngine;

    /* Hook into game completion to show breathing paddle */
    var originalOnComplete = engine._onEncounterComplete;
    if (originalOnComplete) {
      engine._onEncounterComplete = function (idx, success, data) {
        /* Determine mood — not from score, from the feeling */
        var attempts = this._encounterAttempts || 1;
        var mood = (attempts <= 2) ? 'flow' : 'patience';

        var panel = this._yaguaraPanel;
        var self = this;

        /* Show breathing paddle before advancing */
        showBreathingPaddle(panel, mood, function () {
          /* Call original completion handler */
          originalOnComplete.call(self, idx, success, data);
        });
      };
    }

    /* Hook into destination load to apply ecosystem */
    var originalInit = engine.init;
    if (originalInit) {
      engine.init = function (opts) {
        /* opts is a single config object: {games, container, ecosystem, world, ...} */
        var eco = (opts && opts.ecosystem) || null;
        var world = (opts && opts.world) || '_default';
        applyEcosystemBackground(eco, world);

        /* Update rana visibility */
        updateRanaVisibility();

        /* Call original init */
        return originalInit.call(engine, opts);
      };
    }
  }

  /* ═══════════════════════════════════════════════════
     7. EXPORT & INITIALIZE
  ═══════════════════════════════════════════════════ */

  window.Soul = {
    showBreathingPaddle: showBreathingPaddle,
    showMaestroQuote: showMaestroQuote,
    applyEcosystemBackground: applyEcosystemBackground,
    updateRanaVisibility: updateRanaVisibility,
    LINES: SOUL_LINES,
    ECO_GRADIENTS: ECO_GRADIENTS
  };

  /* Auto-hook when DOM is ready */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hookEngine);
  } else {
    hookEngine();
  }

})(window);

/* ═══════════════════════════════════════════════════
   8. THE FINAL MOMENT — "Ahora, nombra."
   After dest89, the student becomes a guardian.
   They choose one word. It becomes a star.
═══════════════════════════════════════════════════ */

Soul.showFinalMoment = function (container, callback) {
  if (!container) return;

  container.innerHTML = '';
  container.style.background = '#000';
  container.style.minHeight = '100vh';
  container.style.display = 'flex';
  container.style.alignItems = 'center';
  container.style.justifyContent = 'center';
  container.style.transition = 'background 3s ease';

  /* Phase 1: Black silence — 3 seconds */
  setTimeout(function () {
    /* Phase 2: "Ahora, nombra." fades in */
    var prompt = document.createElement('div');
    prompt.className = 'soul-final';
    prompt.innerHTML =
      '<div class="soul-final-prompt" id="soulFinalPrompt">Ahora, nombra.</div>' +
      '<div class="soul-final-input-wrap" id="soulFinalWrap" style="opacity:0">' +
        '<input type="text" class="soul-final-input" id="soulFinalInput" ' +
          'placeholder="Una palabra..." autocomplete="off" maxlength="30" lang="es">' +
      '</div>' +
      '<div class="soul-final-star" id="soulFinalStar" style="opacity:0"></div>';
    container.appendChild(prompt);

    requestAnimationFrame(function () {
      document.getElementById('soulFinalPrompt').classList.add('visible');
    });

    /* Phase 3: Input appears after 2 seconds */
    setTimeout(function () {
      var wrap = document.getElementById('soulFinalWrap');
      wrap.style.transition = 'opacity 1.5s ease';
      wrap.style.opacity = '1';
      var input = document.getElementById('soulFinalInput');
      input.focus();

      /* Phase 4: When they type and press Enter */
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && input.value.trim()) {
          var word = input.value.trim();
          input.disabled = true;
          wrap.style.opacity = '0';

          /* The word becomes a star */
          var star = document.getElementById('soulFinalStar');
          star.textContent = word;
          star.style.transition = 'opacity 2s ease, transform 2s ease, text-shadow 2s ease';
          star.style.opacity = '1';

          setTimeout(function () {
            star.classList.add('soul-star-glow');
          }, 100);

          /* Save their word */
          try {
            var guardianWords = JSON.parse(localStorage.getItem('yaguara_guardian_words') || '[]');
            guardianWords.push({
              word: word,
              date: new Date().toISOString(),
              destination: 89
            });
            localStorage.setItem('yaguara_guardian_words', JSON.stringify(guardianWords));
          } catch (ex) {}

          /* After the star glows, dissolve into constellation */
          setTimeout(function () {
            star.classList.add('soul-star-dissolve');

            setTimeout(function () {
              /* Final message */
              container.innerHTML =
                '<div class="soul-final">' +
                  '<div class="soul-final-coda visible">' +
                    '<div class="soul-coda-line">Nombrar es crear.</div>' +
                    '<div class="soul-coda-line soul-coda-small">El viaje continúa.</div>' +
                    '<a href="/storymap" class="soul-coda-link">Volver al mapa</a>' +
                  '</div>' +
                '</div>';

              container.style.background =
                'radial-gradient(ellipse at center, rgba(201,162,39,0.15) 0%, #000 70%)';

              if (callback) callback(word);
            }, 2000);
          }, 3000);
        }
      });
    }, 2000);
  }, 3000);
};
