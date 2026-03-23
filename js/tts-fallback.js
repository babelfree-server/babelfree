/**
 * TTS Fallback for AudioManager — El Viaje del Jaguar
 *
 * TEMPORARY solution until real composed audio files arrive.
 *
 * When AudioManager tries to play a file and it 404s, this module
 * intercepts the failure and uses the Web Speech API (speechSynthesis)
 * to speak a contextual Spanish phrase instead.
 *
 * How it works:
 *   - Monkey-patches AudioManager's play methods AFTER audio-manager.js loads
 *   - For SFX (correct/incorrect/drag), uses short tone-words as audio cues
 *   - For character leitmotifs, speaks the character's name softly
 *   - For narrative stings, speaks a brief description
 *   - For loops (world/eco/escape), does nothing (loops would be annoying as TTS)
 *
 * Load order: audio-manager.js → tts-fallback.js
 * Remove this file once real audio assets are in /audio/
 */
(function () {
    'use strict';

    /* ── Guard: only activate if speechSynthesis exists ──────────── */
    if (!window.speechSynthesis) return;

    /* ── Spanish voice selection ─────────────────────────────────── */
    var _spanishVoice = null;
    var _voiceReady = false;

    function _findSpanishVoice() {
        var voices = speechSynthesis.getVoices();
        if (!voices.length) return;
        _voiceReady = true;

        /* Prefer Latin American Spanish, then any Spanish */
        var priorities = ['es-MX', 'es-CO', 'es-AR', 'es-US', 'es-419', 'es-CL', 'es-PE', 'es-ES', 'es'];
        for (var p = 0; p < priorities.length; p++) {
            for (var i = 0; i < voices.length; i++) {
                if (voices[i].lang && voices[i].lang.replace('_', '-').toLowerCase().indexOf(priorities[p]) === 0) {
                    _spanishVoice = voices[i];
                    return;
                }
            }
        }
        /* Fallback: any voice with 'es' in the lang */
        for (var j = 0; j < voices.length; j++) {
            if (voices[j].lang && voices[j].lang.toLowerCase().indexOf('es') !== -1) {
                _spanishVoice = voices[j];
                return;
            }
        }
    }

    /* Voices load asynchronously in some browsers */
    if (speechSynthesis.onvoiceschanged !== undefined) {
        speechSynthesis.onvoiceschanged = _findSpanishVoice;
    }
    _findSpanishVoice();

    /* ── Speak a short phrase as fallback ─────────────────────────── */
    function _speakFallback(text, opts) {
        if (!text) return;
        /* Don't interrupt existing engine TTS */
        if (speechSynthesis.speaking) return;

        var u = new SpeechSynthesisUtterance(text);
        u.lang = 'es-MX';
        if (_spanishVoice) u.voice = _spanishVoice;
        u.rate = (opts && opts.rate) || 0.9;
        u.pitch = (opts && opts.pitch) || 1.0;
        u.volume = (opts && opts.volume) || 0.4;
        if (opts && opts.onEnd) {
            u.onend = opts.onEnd;
        }

        try { speechSynthesis.speak(u); } catch (e) { /* silent */ }
    }

    /* ── Contextual fallback phrases ─────────────────────────────── */
    var SFX_PHRASES = {
        'correct':          ['Bien', 'Correcto', 'Muy bien', 'Exacto', 'Perfecto'],
        'incorrect':        ['No', 'Otra vez', 'Intenta de nuevo'],
        'encounter-start':  ['Comenzamos'],
        'phase-transition': ['Siguiente'],
        'drag-pick':        null,   /* silent — too frequent */
        'drag-drop':        null,   /* silent — too frequent */
        'voice-start':      null,   /* silent — would conflict with actual TTS */
        'voice-end':        null,   /* silent */
        'pair-match':       ['Par']
    };

    var CHARACTER_GREETINGS = {
        'yaguara':    ['Yaguará te acompaña', 'El jaguar te guía'],
        'candelaria': ['Candelaria aparece', 'Doña Candelaria'],
        'prospero':   ['Don Próspero', 'Próspero se acerca'],
        'ceiba':      ['La Ceiba habla', 'Ceiba'],
        'asuncion':   ['Doña Asunción', 'La abuela Asunción'],
        'rana':       null  /* silence is intentional for the frog */
    };

    var STING_PHRASES = {
        'awakening':            'El viaje comienza',
        'candelaria':           'Candelaria aparece',
        'prospero':             'Próspero llega',
        'grey-place':           'El lugar gris',
        'music-returns':        'La música regresa',
        'portal-pombo':         'Portal de Pombo',
        'portal-rivera':        'Portal de Rivera',
        'portal-carrasquilla':  'Portal de Carrasquilla',
        'portal-macias':        'Portal de Macías',
        'complete-abajo':       'Destino completado',
        'complete-medio':       'Destino completado',
        'complete-arriba':      'Destino completado'
    };

    var QUEST_PHRASES = {
        'riddle-appears':   'Un acertijo aparece',
        'bridge-plank':     'Un tablón del puente',
        'rana-shimmer':     null,  /* subtle — skip */
        'cuaderno-open':    'El cuaderno'
    };

    /* ── Helper: pick random from array ──────────────────────────── */
    function _pick(arr) {
        if (!arr || !arr.length) return null;
        return arr[Math.floor(Math.random() * arr.length)];
    }

    /* ── Patch AudioManager after it initializes ─────────────────── */
    function _patchAudioManager() {
        if (!window.AudioManager) return;
        var AM = window.AudioManager;

        /* Store originals */
        var _origPlayCorrect = AM.playCorrect;
        var _origPlayIncorrect = AM.playIncorrect;
        var _origPlayEncounterStart = AM.playEncounterStart;
        var _origPlayPhaseTransition = AM.playPhaseTransition;
        var _origPlayDragPick = AM.playDragPick;
        var _origPlayDragDrop = AM.playDragDrop;
        var _origPlayVoiceStart = AM.playVoiceStart;
        var _origPlayVoiceEnd = AM.playVoiceEnd;
        var _origPlayPairMatch = AM.playPairMatch;
        var _origPlayLeitmotif = AM.playLeitmotif;
        var _origPlayNarrativeSting = AM.playNarrativeSting;
        var _origPlayArrivalSting = AM.playArrivalSting;
        var _origPlayPortalSting = AM.playPortalSting;
        var _origPlayDestinationComplete = AM.playDestinationComplete;
        var _origPlayRiddleAppears = AM.playRiddleAppears;
        var _origPlayBridgePlank = AM.playBridgePlank;
        var _origPlayRanaShimmer = AM.playRanaShimmer;
        var _origPlayCuadernoOpen = AM.playCuadernoOpen;
        var _origPlayEscapeSolved = AM.playEscapeSolved;
        var _origPlayEscapeComplete = AM.playEscapeComplete;
        var _origPlayFinaleChord = AM.playFinaleChord;
        var _origPlayRanaFirstSound = AM.playRanaFirstSound;
        var _origPlayNamingCeremony = AM.playNamingCeremony;

        /* We need a way to detect if the real audio played successfully.
           Since AudioManager silently skips missing files, we use a
           timer-based approach: try original, then TTS after a short delay
           only if no audio element is currently playing. */

        function _isAnyAudioPlaying() {
            var audios = document.querySelectorAll('audio');
            for (var i = 0; i < audios.length; i++) {
                if (!audios[i].paused && audios[i].currentTime > 0) return true;
            }
            return false;
        }

        /* Wrap a one-shot play method with TTS fallback */
        function _wrapOneShot(origFn, fallbackText, fallbackOpts) {
            return function () {
                var args = arguments;
                /* Call original first */
                origFn.apply(AM, args);

                /* After a brief delay, check if real audio started */
                if (fallbackText) {
                    setTimeout(function () {
                        if (!_isAnyAudioPlaying()) {
                            var text = typeof fallbackText === 'function' ? fallbackText() : fallbackText;
                            if (text) _speakFallback(text, fallbackOpts || {});
                        }
                    }, 150);
                }
            };
        }

        /* ── Gameplay SFX patches ─────────────────────────────────── */
        AM.playCorrect = _wrapOneShot(_origPlayCorrect, function () {
            return _pick(SFX_PHRASES['correct']);
        }, { rate: 1.1, volume: 0.3 });

        AM.playIncorrect = _wrapOneShot(_origPlayIncorrect, function () {
            return _pick(SFX_PHRASES['incorrect']);
        }, { rate: 0.9, volume: 0.3, pitch: 0.8 });

        AM.playEncounterStart = _wrapOneShot(_origPlayEncounterStart, function () {
            return _pick(SFX_PHRASES['encounter-start']);
        }, { rate: 0.8, volume: 0.3 });

        AM.playPhaseTransition = _wrapOneShot(_origPlayPhaseTransition, function () {
            return _pick(SFX_PHRASES['phase-transition']);
        }, { rate: 0.9, volume: 0.25 });

        AM.playPairMatch = _wrapOneShot(_origPlayPairMatch, function () {
            return _pick(SFX_PHRASES['pair-match']);
        }, { rate: 1.0, volume: 0.25 });

        /* Drag and voice — intentionally no TTS (too frequent / would conflict) */
        /* AM.playDragPick, AM.playDragDrop, AM.playVoiceStart, AM.playVoiceEnd left as-is */

        /* ── Character leitmotif patches ──────────────────────────── */
        AM.playLeitmotif = function (character, destNum) {
            _origPlayLeitmotif.call(AM, character, destNum);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    var phrases = CHARACTER_GREETINGS[character];
                    var text = _pick(phrases);
                    if (text) _speakFallback(text, { rate: 0.8, volume: 0.3 });
                }
            }, 150);
        };

        /* ── Narrative sting patches ──────────────────────────────── */
        AM.playNarrativeSting = function (stingName, onEnd) {
            _origPlayNarrativeSting.call(AM, stingName, onEnd);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    var text = STING_PHRASES[stingName];
                    if (text) _speakFallback(text, {
                        rate: 0.7,
                        volume: 0.35,
                        onEnd: onEnd || null
                    });
                }
            }, 150);
        };

        AM.playArrivalSting = function (destNum) {
            _origPlayArrivalSting.call(AM, destNum);
            var ARRIVAL_MAP = {
                1: 'awakening', 3: 'portal-pombo', 6: 'portal-rivera',
                9: 'portal-carrasquilla', 11: 'portal-macias',
                14: 'candelaria', 19: 'prospero', 33: 'grey-place', 39: 'music-returns'
            };
            var sting = ARRIVAL_MAP[destNum];
            if (sting) {
                setTimeout(function () {
                    if (!_isAnyAudioPlaying()) {
                        var text = STING_PHRASES[sting];
                        if (text) _speakFallback(text, { rate: 0.7, volume: 0.35 });
                    }
                }, 150);
            }
        };

        AM.playPortalSting = function (author) {
            _origPlayPortalSting.call(AM, author);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    var text = STING_PHRASES['portal-' + author];
                    if (text) _speakFallback(text, { rate: 0.7, volume: 0.35 });
                }
            }, 150);
        };

        AM.playDestinationComplete = function (destNum) {
            _origPlayDestinationComplete.call(AM, destNum);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    _speakFallback('Destino completado', { rate: 0.8, volume: 0.4 });
                }
            }, 150);
        };

        /* ── Escape room patches ──────────────────────────────────── */
        AM.playEscapeSolved = _wrapOneShot(_origPlayEscapeSolved, 'Resuelto', { rate: 0.9, volume: 0.35 });
        AM.playEscapeComplete = _wrapOneShot(_origPlayEscapeComplete, 'Escapaste', { rate: 0.8, volume: 0.4 });

        /* ── Riddle quest patches ─────────────────────────────────── */
        AM.playRiddleAppears = _wrapOneShot(_origPlayRiddleAppears, function () {
            return QUEST_PHRASES['riddle-appears'];
        }, { rate: 0.8, volume: 0.3 });

        AM.playBridgePlank = _wrapOneShot(_origPlayBridgePlank, function () {
            return QUEST_PHRASES['bridge-plank'];
        }, { rate: 0.9, volume: 0.25 });

        /* playRanaShimmer — intentionally silent */

        AM.playCuadernoOpen = _wrapOneShot(_origPlayCuadernoOpen, function () {
            return QUEST_PHRASES['cuaderno-open'];
        }, { rate: 0.8, volume: 0.3 });

        /* ── Finale patches ───────────────────────────────────────── */
        AM.playFinaleChord = function (onEnd) {
            _origPlayFinaleChord.call(AM, onEnd);
            /* Finale chord is special — give it more time before fallback */
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    _speakFallback('El viaje del jaguar se completa', {
                        rate: 0.6,
                        volume: 0.5,
                        onEnd: onEnd || null
                    });
                }
            }, 1200);
        };

        AM.playRanaFirstSound = function (onEnd) {
            _origPlayRanaFirstSound.call(AM, onEnd);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    _speakFallback('La rana canta por primera vez', {
                        rate: 0.7,
                        volume: 0.4,
                        onEnd: onEnd || null
                    });
                }
            }, 150);
        };

        AM.playNamingCeremony = function (onEnd) {
            _origPlayNamingCeremony.call(AM, onEnd);
            setTimeout(function () {
                if (!_isAnyAudioPlaying()) {
                    _speakFallback('La ceremonia del nombre', {
                        rate: 0.6,
                        volume: 0.5,
                        onEnd: onEnd || null
                    });
                }
            }, 150);
        };

        /* Mark as patched */
        AM._ttsFallbackActive = true;
    }

    /* ── Initialize ──────────────────────────────────────────────── */
    /* Patch after DOM is ready (AudioManager should exist by then) */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _patchAudioManager);
    } else {
        _patchAudioManager();
    }

    /* Also expose for manual patching if load order varies */
    window.TTSFallback = {
        patch: _patchAudioManager,
        speak: _speakFallback
    };

})();
