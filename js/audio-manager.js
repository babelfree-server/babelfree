/**
 * AudioManager — File-based audio system for El Viaje del Jaguar
 *
 * Plays background loops, ambient beds, character leitmotifs, stings,
 * and gameplay SFX from /audio/ directory. Falls back gracefully when
 * files are missing (game works without any audio files).
 *
 * Integrates with:
 *   - WorldReaction (harmony/desequilibrio SFX)
 *   - Escape rooms (ambience: heartbeat/river/wind)
 *   - Ecosystems (ambient loops per biome)
 *   - Characters (leitmotifs on appearance)
 *   - Riddle quest (Busqueda sounds)
 *   - Narrative stings (one-shot moments)
 *
 * All audio is optional. If a file 404s, the system silently skips it.
 */
(function () {
    'use strict';

    var BASE_PATH = 'audio/';

    /* ── Volume levels (0.0 – 1.0) ─────────────────────────────── */
    var VOL = {
        bgLoop:     0.12,   /* World theme loops — very quiet, under speech */
        ecosystem:  0.15,   /* Ecosystem ambient — slightly louder than world */
        escape:     0.18,   /* Escape room ambience — immersive but not loud */
        leitmotif:  0.35,   /* Character leitmotifs — brief, audible */
        sting:      0.40,   /* Narrative stings — momentary, prominent */
        sfx:        0.30,   /* Gameplay SFX — clear feedback */
        quest:      0.30,   /* Riddle quest sounds */
        finale:     0.50    /* Finale chord — the loudest thing in the game */
    };

    /* ── File manifest ──────────────────────────────────────────── */
    /* Maps logical names → filenames (relative to BASE_PATH).
       Composer delivers files; we update this manifest. Until then,
       all lookups return null and the game runs silently. */

    var FILES = {
        /* World theme loops (2 per world) */
        'world-abajo-a1':       'world-abajo-a1.mp3',
        'world-abajo-a2':       'world-abajo-a2.mp3',
        'world-medio-b1':       'world-medio-b1.mp3',
        'world-medio-b2':       'world-medio-b2.mp3',
        'world-arriba-c1':      'world-arriba-c1.mp3',
        'world-arriba-c2':      'world-arriba-c2.mp3',

        /* Ecosystem ambient loops */
        'eco-bosque':           'eco-bosque-ambient.mp3',
        'eco-costa':            'eco-costa-ambient.mp3',
        'eco-desierto':         'eco-desierto-ambient.mp3',
        'eco-islas':            'eco-islas-ambient.mp3',
        'eco-llanos':           'eco-llanos-ambient.mp3',
        'eco-nevada':           'eco-nevada-ambient.mp3',
        'eco-selva':            'eco-selva-ambient.mp3',
        'eco-sierra':           'eco-sierra-ambient.mp3',

        /* Escape room ambience */
        'escape-heartbeat':     'escape-heartbeat-loop.mp3',
        'escape-river':         'escape-river-loop.mp3',
        'escape-wind':          'escape-wind-loop.mp3',

        /* Escape room SFX */
        'escape-solved-abajo':  'escape-solved-abajo.mp3',
        'escape-solved-medio':  'escape-solved-medio.mp3',
        'escape-solved-arriba': 'escape-solved-arriba.mp3',
        'escape-complete-abajo':'escape-complete-abajo.mp3',
        'escape-complete-medio':'escape-complete-medio.mp3',
        'escape-complete-arriba':'escape-complete-arriba.mp3',

        /* Character leitmotifs */
        'char-yaguara-1':       'char-yaguara-sonadora.mp3',
        'char-yaguara-2':       'char-yaguara-caminante.mp3',
        'char-yaguara-3':       'char-yaguara-guardiana.mp3',
        'char-candelaria':      'char-candelaria.mp3',
        'char-prospero':        'char-prospero.mp3',
        'char-ceiba':           'char-ceiba.mp3',
        'char-asuncion':        'char-asuncion.mp3',
        'char-rana-silent':     null,  /* silence — no file needed */
        'char-rana-note':       'char-rana-note.mp3',
        'char-rana-song':       'char-rana-song.mp3',

        /* Gameplay SFX */
        'sfx-correct-01':       'sfx-correct-01.mp3',
        'sfx-correct-02':       'sfx-correct-02.mp3',
        'sfx-correct-03':       'sfx-correct-03.mp3',
        'sfx-incorrect-01':     'sfx-incorrect-01.mp3',
        'sfx-incorrect-02':     'sfx-incorrect-02.mp3',
        'sfx-incorrect-03':     'sfx-incorrect-03.mp3',
        'sfx-encounter-start':  'sfx-encounter-start.mp3',
        'sfx-phase-transition': 'sfx-phase-transition.mp3',
        'sfx-drag-pick':        'sfx-drag-pick.mp3',
        'sfx-drag-drop':        'sfx-drag-drop.mp3',
        'sfx-voice-start':      'sfx-voice-start.mp3',
        'sfx-voice-end':        'sfx-voice-end.mp3',
        'sfx-pair-match':       'sfx-pair-match.mp3',

        /* Destination complete stings */
        'sting-complete-abajo': 'sting-complete-abajo.mp3',
        'sting-complete-medio': 'sting-complete-medio.mp3',
        'sting-complete-arriba':'sting-complete-arriba.mp3',

        /* Narrative stings */
        'sting-awakening':      'sting-awakening.mp3',
        'sting-candelaria':     'sting-candelaria-appears.mp3',
        'sting-prospero':       'sting-prospero-arrives.mp3',
        'sting-grey-place':     'sting-grey-place.mp3',
        'sting-music-returns':  'sting-music-returns.mp3',
        'sting-portal-pombo':   'sting-portal-pombo.mp3',
        'sting-portal-rivera':  'sting-portal-rivera.mp3',
        'sting-portal-carrasquilla': 'sting-portal-carrasquilla.mp3',
        'sting-portal-macias':  'sting-portal-macias.mp3',

        /* Riddle quest */
        'quest-riddle-appears': 'quest-riddle-appears.mp3',
        'quest-bridge-plank':   'quest-bridge-plank.mp3',
        'quest-rana-shimmer':   'quest-rana-shimmer.mp3',
        'quest-cuaderno-open':  'quest-cuaderno-open.mp3',

        /* Finale */
        'finale-chord':         'finale-58-chord.mp3',
        'finale-rana-first':    'finale-rana-first-sound.mp3',
        'finale-naming':        'finale-naming-ceremony.mp3'
    };

    /* ── Audio element pool ─────────────────────────────────────── */
    var _pool = {};        /* { name: HTMLAudioElement } — cached after first load */
    var _loopEl = null;    /* Currently playing loop element */
    var _loopName = '';    /* Currently playing loop name */
    var _escapeEl = null;  /* Currently playing escape ambience */
    var _ecoEl = null;     /* Currently playing ecosystem ambient */
    var _muted = false;
    var _ready = false;
    var _userInteracted = false;

    /* Most browsers require user gesture before audio can play.
       We listen for first interaction and unlock audio context. */
    function _unlockAudio() {
        if (_userInteracted) return;
        _userInteracted = true;
        document.removeEventListener('click', _unlockAudio, true);
        document.removeEventListener('keydown', _unlockAudio, true);
        document.removeEventListener('touchstart', _unlockAudio, true);
    }
    document.addEventListener('click', _unlockAudio, true);
    document.addEventListener('keydown', _unlockAudio, true);
    document.addEventListener('touchstart', _unlockAudio, true);

    /* ── Core: load or retrieve an audio element ────────────────── */
    function _getAudio(name) {
        if (!name || !FILES[name]) return null;

        if (_pool[name]) return _pool[name];

        var el = new Audio();
        el.preload = 'auto';
        el.src = BASE_PATH + FILES[name];

        /* Silently handle missing files */
        el._failed = false;
        el.addEventListener('error', function () {
            el._failed = true;
        });

        _pool[name] = el;
        return el;
    }

    /* ── Play a one-shot sound (stings, SFX, leitmotifs) ────────── */
    function _playOneShot(name, volume, onEnd) {
        if (_muted || !_userInteracted) {
            if (onEnd) setTimeout(onEnd, 50);
            return;
        }

        var el = _getAudio(name);
        if (!el || el._failed) {
            if (onEnd) setTimeout(onEnd, 50);
            return;
        }

        el.volume = volume || VOL.sfx;
        el.loop = false;
        el.currentTime = 0;

        if (onEnd) {
            var handler = function () {
                el.removeEventListener('ended', handler);
                onEnd();
            };
            el.addEventListener('ended', handler);
        }

        el.play().catch(function () {
            /* Autoplay blocked or file missing — silent fallback */
            if (onEnd) onEnd();
        });
    }

    /* ── Start a loop (crossfade from current) ──────────────────── */
    function _startLoop(name, volume, targetEl) {
        if (_muted) return;
        var varName = targetEl === 'eco' ? '_ecoEl' : targetEl === 'escape' ? '_escapeEl' : '_loopEl';

        /* Already playing this loop */
        if (varName === '_loopEl' && _loopName === name) return;

        /* Fade out current loop on this channel */
        var current = varName === '_ecoEl' ? _ecoEl : varName === '_escapeEl' ? _escapeEl : _loopEl;
        if (current) {
            _fadeOut(current, 800);
        }

        if (!_userInteracted) return;

        var el = _getAudio(name);
        if (!el || el._failed) return;

        el.volume = 0;
        el.loop = true;
        el.currentTime = 0;

        el.play().catch(function () { /* silent */ });
        _fadeIn(el, volume || VOL.bgLoop, 1200);

        if (varName === '_ecoEl') { _ecoEl = el; }
        else if (varName === '_escapeEl') { _escapeEl = el; }
        else { _loopEl = el; _loopName = name; }
    }

    /* ── Stop a loop channel ────────────────────────────────────── */
    function _stopLoop(channel) {
        var el;
        if (channel === 'eco') { el = _ecoEl; _ecoEl = null; }
        else if (channel === 'escape') { el = _escapeEl; _escapeEl = null; }
        else { el = _loopEl; _loopEl = null; _loopName = ''; }

        if (el) _fadeOut(el, 800);
    }

    /* ── Fade helpers ───────────────────────────────────────────── */
    function _fadeIn(el, targetVol, durationMs) {
        var steps = 20;
        var stepMs = durationMs / steps;
        var increment = targetVol / steps;
        var current = 0;
        var timer = setInterval(function () {
            current += increment;
            if (current >= targetVol) {
                el.volume = targetVol;
                clearInterval(timer);
            } else {
                el.volume = current;
            }
        }, stepMs);
    }

    function _fadeOut(el, durationMs) {
        var startVol = el.volume;
        if (startVol <= 0) { el.pause(); return; }
        var steps = 20;
        var stepMs = durationMs / steps;
        var decrement = startVol / steps;
        var current = startVol;
        var timer = setInterval(function () {
            current -= decrement;
            if (current <= 0) {
                el.volume = 0;
                el.pause();
                clearInterval(timer);
            } else {
                el.volume = current;
            }
        }, stepMs);
    }

    /* ── World detection from destNum ───────────────────────────── */
    function _getWorld(destNum) {
        if (destNum <= 18) return 'abajo';
        if (destNum <= 38) return 'medio';
        return 'arriba';
    }

    function _getCefrBand(destNum) {
        if (destNum <= 12) return 'a1';
        if (destNum <= 18) return 'a2';
        if (destNum <= 28) return 'b1';
        if (destNum <= 38) return 'b2';
        if (destNum <= 48) return 'c1';
        return 'c2';
    }

    /* ── Yaguará stage from destNum ─────────────────────────────── */
    function _getYaguaraStage(destNum) {
        if (destNum <= 18) return 1;  /* Soñadora */
        if (destNum <= 38) return 2;  /* Caminante */
        return 3;                     /* Guardiana */
    }

    /* ═══════════════════════════════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════════════════════════════ */
    var AudioManager = {

        /* ── Initialize: call once on page load ─────────────────── */
        init: function () {
            _ready = true;
            /* Preload common SFX */
            _getAudio('sfx-correct-01');
            _getAudio('sfx-correct-02');
            _getAudio('sfx-correct-03');
            _getAudio('sfx-incorrect-01');
            _getAudio('sfx-encounter-start');
        },

        /* ── Mute / Unmute ──────────────────────────────────────── */
        mute: function () {
            _muted = true;
            if (_loopEl) _loopEl.volume = 0;
            if (_ecoEl) _ecoEl.volume = 0;
            if (_escapeEl) _escapeEl.volume = 0;
        },

        unmute: function () {
            _muted = false;
        },

        isMuted: function () { return _muted; },

        /* ── World theme loop ───────────────────────────────────── */
        /* Starts the appropriate world loop for a destination */
        playWorldLoop: function (destNum) {
            var band = _getCefrBand(destNum);
            var world = _getWorld(destNum);
            var name;
            if (world === 'abajo') {
                name = band === 'a1' ? 'world-abajo-a1' : 'world-abajo-a2';
            } else if (world === 'medio') {
                name = band === 'b1' ? 'world-medio-b1' : 'world-medio-b2';
            } else {
                name = band === 'c1' ? 'world-arriba-c1' : 'world-arriba-c2';
            }
            _startLoop(name, VOL.bgLoop, 'main');
        },

        stopWorldLoop: function () {
            _stopLoop('main');
        },

        /* ── Ecosystem ambient ──────────────────────────────────── */
        playEcosystem: function (ecoName) {
            if (!ecoName) return;
            var name = 'eco-' + ecoName.toLowerCase();
            _startLoop(name, VOL.ecosystem, 'eco');
        },

        stopEcosystem: function () {
            _stopLoop('eco');
        },

        /* ── Escape room ambience ───────────────────────────────── */
        playEscapeAmbience: function (ambienceType) {
            var name = 'escape-' + (ambienceType || 'heartbeat');
            _startLoop(name, VOL.escape, 'escape');
        },

        stopEscapeAmbience: function () {
            _stopLoop('escape');
        },

        playEscapeSolved: function (destNum) {
            var world = _getWorld(destNum);
            _playOneShot('escape-solved-' + world, VOL.sfx);
        },

        playEscapeComplete: function (destNum) {
            var world = _getWorld(destNum);
            _playOneShot('escape-complete-' + world, VOL.sting);
        },

        /* ── Character leitmotifs ───────────────────────────────── */
        playLeitmotif: function (character, destNum) {
            var name;
            switch (character) {
                case 'yaguara':
                    name = 'char-yaguara-' + _getYaguaraStage(destNum || 1);
                    break;
                case 'candelaria':
                    name = 'char-candelaria';
                    break;
                case 'prospero':
                    name = 'char-prospero';
                    break;
                case 'ceiba':
                    name = 'char-ceiba';
                    break;
                case 'asuncion':
                    name = 'char-asuncion';
                    break;
                case 'rana':
                    /* Progressive: silence → note → song */
                    if (window.Busqueda) {
                        var progress = Busqueda.getBridgeProgress();
                        if (progress >= 58) name = 'char-rana-song';
                        else if (progress >= 10) name = 'char-rana-note';
                        else name = 'char-rana-silent';
                    } else {
                        name = 'char-rana-silent';
                    }
                    break;
                default:
                    return;
            }
            _playOneShot(name, VOL.leitmotif);
        },

        /* ── Gameplay SFX ───────────────────────────────────────── */
        playCorrect: function () {
            var variants = ['sfx-correct-01', 'sfx-correct-02', 'sfx-correct-03'];
            var pick = variants[Math.floor(Math.random() * variants.length)];
            _playOneShot(pick, VOL.sfx);
        },

        playIncorrect: function () {
            var variants = ['sfx-incorrect-01', 'sfx-incorrect-02', 'sfx-incorrect-03'];
            var pick = variants[Math.floor(Math.random() * variants.length)];
            _playOneShot(pick, VOL.sfx);
        },

        playEncounterStart: function () {
            _playOneShot('sfx-encounter-start', VOL.sfx * 0.6);
        },

        playPhaseTransition: function () {
            _playOneShot('sfx-phase-transition', VOL.sfx);
        },

        playDragPick: function () {
            _playOneShot('sfx-drag-pick', VOL.sfx * 0.7);
        },

        playDragDrop: function () {
            _playOneShot('sfx-drag-drop', VOL.sfx * 0.7);
        },

        playVoiceStart: function () {
            _playOneShot('sfx-voice-start', VOL.sfx * 0.5);
        },

        playVoiceEnd: function () {
            _playOneShot('sfx-voice-end', VOL.sfx * 0.5);
        },

        playPairMatch: function () {
            _playOneShot('sfx-pair-match', VOL.sfx);
        },

        /* ── Destination complete sting ─────────────────────────── */
        playDestinationComplete: function (destNum) {
            var world = _getWorld(destNum);
            _playOneShot('sting-complete-' + world, VOL.sting);
        },

        /* ── Narrative stings ───────────────────────────────────── */
        playNarrativeSting: function (stingName, onEnd) {
            _playOneShot('sting-' + stingName, VOL.sting, onEnd);
        },

        /* Convenience for literary portals */
        playPortalSting: function (author) {
            _playOneShot('sting-portal-' + author, VOL.sting);
        },

        /* ── Riddle quest sounds ────────────────────────────────── */
        playRiddleAppears: function () {
            _playOneShot('quest-riddle-appears', VOL.quest);
        },

        playBridgePlank: function () {
            _playOneShot('quest-bridge-plank', VOL.quest);
        },

        playRanaShimmer: function () {
            _playOneShot('quest-rana-shimmer', VOL.quest * 0.7);
        },

        playCuadernoOpen: function () {
            _playOneShot('quest-cuaderno-open', VOL.quest);
        },

        /* ── Finale ─────────────────────────────────────────────── */
        playFinaleChord: function (onEnd) {
            /* Fade out all loops first */
            _stopLoop('main');
            _stopLoop('eco');
            _stopLoop('escape');
            setTimeout(function () {
                _playOneShot('finale-chord', VOL.finale, onEnd);
            }, 1000);
        },

        playRanaFirstSound: function (onEnd) {
            _playOneShot('finale-rana-first', VOL.finale * 0.8, onEnd);
        },

        playNamingCeremony: function (onEnd) {
            _playOneShot('finale-naming', VOL.finale, onEnd);
        },

        /* ── Duck volume during speech ──────────────────────────── */
        /* Call when TTS starts to lower background loops */
        duckForSpeech: function () {
            if (_loopEl && !_muted) _loopEl.volume = VOL.bgLoop * 0.3;
            if (_ecoEl && !_muted) _ecoEl.volume = VOL.ecosystem * 0.3;
        },

        /* Call when TTS ends to restore background loops */
        unduckForSpeech: function () {
            if (_loopEl && !_muted) _loopEl.volume = VOL.bgLoop;
            if (_ecoEl && !_muted) _ecoEl.volume = VOL.ecosystem;
        },

        /* ── Stop everything ────────────────────────────────────── */
        stopAll: function () {
            _stopLoop('main');
            _stopLoop('eco');
            _stopLoop('escape');
            for (var name in _pool) {
                if (_pool.hasOwnProperty(name) && _pool[name]) {
                    _pool[name].pause();
                    _pool[name].currentTime = 0;
                }
            }
        }
    };

    window.AudioManager = AudioManager;
})();
