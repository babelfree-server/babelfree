/**
 * Feedback Widget — Standalone version for pages without yaguara-engine.js
 * Auto-initializes on DOMContentLoaded. Uses JaguarAPI for auth if available.
 */
(function() {
    'use strict';

    var SESSION_COUNT_KEY = 'ygFeedbackCount';
    var MAX_PER_SESSION = 5;
    var API_ENDPOINT = '/api/feedback/submit';

    var _panel = null;
    var _overlay = null;
    var _btn = null;
    var _isOpen = false;

    function _getSessionCount() {
        try { return parseInt(sessionStorage.getItem(SESSION_COUNT_KEY), 10) || 0; }
        catch(e) { return 0; }
    }
    function _incrementCount() {
        try { sessionStorage.setItem(SESSION_COUNT_KEY, _getSessionCount() + 1); }
        catch(e) {}
    }

    function _getSpeechBubbleSVG() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
    }

    function _getContext() {
        return {
            url: window.location.href,
            userAgent: navigator.userAgent,
            page: document.title,
            timestamp: new Date().toISOString()
        };
    }

    function _getAuthHeaders() {
        var headers = { 'Content-Type': 'application/json' };
        if (window.JaguarAPI && typeof JaguarAPI.isAuthenticated === 'function' && JaguarAPI.isAuthenticated()) {
            try {
                var raw = localStorage.getItem('jaguarUserSession');
                var session = raw ? JSON.parse(raw) : null;
                if (session && session.serverToken) {
                    headers['Authorization'] = 'Bearer ' + session.serverToken;
                }
            } catch(e) {}
        }
        var csrfMatch = document.cookie.match(/(?:^|;\s*)csrf_token=([^;]+)/);
        if (csrfMatch) headers['X-CSRF-Token'] = csrfMatch[1];
        return headers;
    }

    function _open() {
        if (_isOpen) return;
        _isOpen = true;
        _panel.classList.add('yg-feedback-open');
        _overlay.classList.add('yg-feedback-open');
        _btn.style.display = 'none';
        _panel.setAttribute('aria-hidden', 'false');
        var firstInput = _panel.querySelector('input[type="radio"]');
        if (firstInput) firstInput.focus();
    }

    function _close() {
        if (!_isOpen) return;
        _isOpen = false;
        _panel.classList.remove('yg-feedback-open');
        _overlay.classList.remove('yg-feedback-open');
        _btn.style.display = '';
        _panel.setAttribute('aria-hidden', 'true');
        _btn.focus();
    }

    function _buildUI() {
        // Skip if engine already created the widget
        if (document.getElementById('ygFeedbackBtn')) return;

        // Floating button
        _btn = document.createElement('button');
        _btn.className = 'yg-feedback-btn';
        _btn.id = 'ygFeedbackBtn';
        _btn.setAttribute('aria-label', 'Enviar comentarios');
        _btn.innerHTML = _getSpeechBubbleSVG();
        _btn.addEventListener('click', _open);

        // Overlay
        _overlay = document.createElement('div');
        _overlay.className = 'yg-feedback-overlay';
        _overlay.addEventListener('click', _close);

        // Panel
        _panel = document.createElement('div');
        _panel.className = 'yg-feedback-panel';
        _panel.setAttribute('role', 'dialog');
        _panel.setAttribute('aria-label', 'Formulario de comentarios');
        _panel.setAttribute('aria-hidden', 'true');

        _panel.innerHTML =
            '<div class="yg-feedback-header">' +
                '<h3 class="yg-feedback-title">\u00bfAlgo que decirnos?</h3>' +
                '<button class="yg-feedback-close" aria-label="Cerrar">\u00d7</button>' +
            '</div>' +
            '<div class="yg-feedback-type" role="radiogroup" aria-label="Tipo de comentario">' +
                '<label><input type="radio" name="ygFbType" value="error"><span class="yg-feedback-type-icon">\ud83d\udd34</span> Encontr\u00e9 un error</label>' +
                '<label><input type="radio" name="ygFbType" value="suggestion"><span class="yg-feedback-type-icon">\ud83d\udca1</span> Tengo una idea</label>' +
                '<label><input type="radio" name="ygFbType" value="content"><span class="yg-feedback-type-icon">\ud83d\udcdd</span> Sobre el contenido</label>' +
                '<label><input type="radio" name="ygFbType" value="technical"><span class="yg-feedback-type-icon">\u2699\ufe0f</span> Problema t\u00e9cnico</label>' +
                '<label><input type="radio" name="ygFbType" value="question"><span class="yg-feedback-type-icon">\u2753</span> Pregunta de gram\u00e1tica</label>' +
            '</div>' +
            '<div class="yg-feedback-question-note" style="display:none;padding:8px 12px;margin:0 0 8px;background:#c9a84c1a;border-radius:4px;color:#c9a84c;font-size:13px;line-height:1.4;"></div>' +
            '<textarea class="yg-feedback-textarea" placeholder="Escribe aqu\u00ed..." rows="4" aria-label="Tu mensaje"></textarea>' +
            '<div class="yg-feedback-error" style="display:none"></div>' +
            '<button class="yg-feedback-submit">Enviar</button>';

        // Wire close
        _panel.querySelector('.yg-feedback-close').addEventListener('click', _close);

        // Radio highlight + question note
        var labels = _panel.querySelectorAll('.yg-feedback-type label');
        var questionNote = _panel.querySelector('.yg-feedback-question-note');
        for (var i = 0; i < labels.length; i++) {
            labels[i].addEventListener('change', function() {
                for (var j = 0; j < labels.length; j++) labels[j].classList.remove('yg-fb-selected');
                this.classList.add('yg-fb-selected');
                var selectedVal = this.querySelector('input').value;
                if (selectedVal === 'question' && questionNote) {
                    var isLoggedIn = window.JaguarAPI && typeof JaguarAPI.isAuthenticated === 'function' && JaguarAPI.isAuthenticated();
                    questionNote.textContent = isLoggedIn
                        ? 'Te enviaremos la respuesta a tu correo electr\u00f3nico.'
                        : 'Inicia sesi\u00f3n para recibir respuestas por correo.';
                    questionNote.style.display = '';
                } else if (questionNote) {
                    questionNote.style.display = 'none';
                }
            });
        }

        // Submit
        _panel.querySelector('.yg-feedback-submit').addEventListener('click', _handleSubmit);

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && _isOpen) _close();
        });

        document.body.appendChild(_overlay);
        document.body.appendChild(_panel);
        document.body.appendChild(_btn);
    }

    function _handleSubmit() {
        if (_getSessionCount() >= MAX_PER_SESSION) {
            _showLimit();
            return;
        }

        var typeEl = _panel.querySelector('input[name="ygFbType"]:checked');
        var textarea = _panel.querySelector('.yg-feedback-textarea');
        var errDiv = _panel.querySelector('.yg-feedback-error');
        var submitBtn = _panel.querySelector('.yg-feedback-submit');

        errDiv.style.display = 'none';

        if (!typeEl) {
            errDiv.textContent = 'Selecciona un tipo de comentario.';
            errDiv.style.display = '';
            return;
        }
        var message = (textarea.value || '').trim();
        if (!message) {
            errDiv.textContent = 'Escribe un mensaje.';
            errDiv.style.display = '';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando\u2026';

        var payload = {
            feedback_type: typeEl.value,
            message: message,
            context: _getContext()
        };

        // Engine context if available
        if (window.YaguaraEngine) {
            try {
                var gd = YaguaraEngine.getGrowthData();
                payload.context.destination = gd.destinationId || null;
                payload.context.gameIndex = gd.completed ? Object.keys(gd.completed).length : null;
                payload.context.totalEncounters = gd.totalEncounters || null;
            } catch(e) {}
        }

        fetch(API_ENDPOINT, {
            method: 'POST',
            headers: _getAuthHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (data && data.success) {
                _incrementCount();
                _showSuccess();
            } else {
                _showError();
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar';
            }
        }).catch(function() {
            _showError();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar';
        });
    }

    function _showSuccess() {
        var body = _panel.querySelector('.yg-feedback-type');
        var ta = _panel.querySelector('.yg-feedback-textarea');
        var btn = _panel.querySelector('.yg-feedback-submit');
        var errDiv = _panel.querySelector('.yg-feedback-error');
        var qNote = _panel.querySelector('.yg-feedback-question-note');
        body.style.display = 'none';
        ta.style.display = 'none';
        btn.style.display = 'none';
        errDiv.style.display = 'none';
        if (qNote) qNote.style.display = 'none';

        var msg = document.createElement('div');
        msg.className = 'yg-feedback-success';
        msg.textContent = '\u00a1Gracias!';
        _panel.appendChild(msg);

        setTimeout(function() {
            _close();
            // Reset form after close animation
            setTimeout(function() {
                msg.remove();
                body.style.display = '';
                ta.style.display = '';
                btn.style.display = '';
                btn.disabled = false;
                btn.textContent = 'Enviar';
                ta.value = '';
                var labels = _panel.querySelectorAll('.yg-feedback-type label');
                for (var i = 0; i < labels.length; i++) labels[i].classList.remove('yg-fb-selected');
                var radios = _panel.querySelectorAll('input[name="ygFbType"]');
                for (var i = 0; i < radios.length; i++) radios[i].checked = false;
            }, 500);
        }, 2000);
    }

    function _showError() {
        var errDiv = _panel.querySelector('.yg-feedback-error');
        errDiv.textContent = 'Error al enviar. Int\u00e9ntalo m\u00e1s tarde.';
        errDiv.style.display = '';
    }

    function _showLimit() {
        var body = _panel.querySelector('.yg-feedback-type');
        var ta = _panel.querySelector('.yg-feedback-textarea');
        var btn = _panel.querySelector('.yg-feedback-submit');
        var errDiv = _panel.querySelector('.yg-feedback-error');
        body.style.display = 'none';
        ta.style.display = 'none';
        btn.style.display = 'none';
        errDiv.style.display = 'none';

        var msg = document.createElement('div');
        msg.className = 'yg-feedback-limit';
        msg.textContent = 'Has alcanzado el l\u00edmite de comentarios por sesi\u00f3n. \u00a1Gracias por tu inter\u00e9s!';
        _panel.appendChild(msg);
    }

    // Auto-init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _buildUI);
    } else {
        _buildUI();
    }

    // Expose for engine integration
    window.FeedbackWidget = {
        open: _open,
        close: _close,
        setTitle: function(text) {
            if (_panel) {
                var t = _panel.querySelector('.yg-feedback-title');
                if (t) t.textContent = text;
            }
        },
        setContext: function(ctx) {
            // Merge extra context into next submission
            window._ygFeedbackExtraCtx = ctx;
        }
    };
})();
