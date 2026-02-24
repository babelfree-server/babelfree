/* ================================================================
   CONTENT LOADER — Bridges URL params → JSON fetch → YaguaraEngine
   Reads ?eco= and ?spiral= from URL, fetches content/{eco}_{spiral}.json,
   updates page header, and calls YaguaraEngine.init()
   ================================================================ */
(function() {
    'use strict';

    var ECOSYSTEM_META = {
        selva:    { name: 'Selva',    fullName: 'La Selva Amazónica',          geography: 'Leticia, Vaupés',           domain: 'Agua, orígenes, vida silvestre',            color: '#2d5a27' },
        sierra:   { name: 'Sierra',   fullName: 'La Sierra Andina',           geography: 'Bogotá, zona cafetera',     domain: 'Altitud, comunidad, agricultura',           color: '#5a4a2a' },
        costa:    { name: 'Costa',    fullName: 'La Costa Caribe',            geography: 'Cartagena, San Andrés',     domain: 'Mar, comercio, ritmo, historia',            color: '#2a4d5a' },
        bosque:   { name: 'Bosque',   fullName: 'El Bosque del Pacífico',     geography: 'Chocó, Tumaco',             domain: 'Lluvia, biodiversidad, cultura afrocolombiana', color: '#1a3a2a' },
        llanos:   { name: 'Llanos',   fullName: 'Los Llanos Orientales',      geography: 'Villavicencio, Orinoquía',  domain: 'Sabana, ganadería, cultura llanera',        color: '#8a7a2a' },
        nevada:   { name: 'Nevada',   fullName: 'La Sierra Nevada',           geography: 'Santa Marta, Ciudad Perdida', domain: 'Montaña sagrada, pueblos ancestrales',    color: '#4a3a5a' },
        islas:    { name: 'Islas',    fullName: 'Las Islas del Caribe',        geography: 'San Andrés, Providencia',   domain: 'Isla, cultura raizal, arrecifes',           color: '#1a5a7a' },
        desierto: { name: 'Desierto', fullName: 'El Desierto de La Guajira',  geography: 'Riohacha, Cabo de la Vela', domain: 'Arena, pueblo Wayúu, flamencos',            color: '#7a5a2a' }
    };

    var SPIRAL_META = {
        a1: { cefr: 'A1', mundo: 'mundoDeAbajo', mundoName: 'Mundo de Abajo', subtitle: 'Nombrar el mundo', speechRate: 0.5 },
        a2: { cefr: 'A2', mundo: 'mundoDeAbajo', mundoName: 'Mundo de Abajo', subtitle: 'Nombrar el mundo', speechRate: 0.6 },
        b1: { cefr: 'B1', mundo: 'mundoDelMedio', mundoName: 'Mundo del Medio', subtitle: 'Contar lo vivido', speechRate: 0.7 },
        b2: { cefr: 'B2', mundo: 'mundoDelMedio', mundoName: 'Mundo del Medio', subtitle: 'Contar lo vivido', speechRate: 0.8 },
        c1: { cefr: 'C1', mundo: 'mundoDeArriba', mundoName: 'Mundo de Arriba', subtitle: 'Cuidar la palabra', speechRate: 0.9 },
        c2: { cefr: 'C2', mundo: 'mundoDeArriba', mundoName: 'Mundo de Arriba', subtitle: 'Cuidar la palabra', speechRate: 1.0 }
    };

    function getParams() {
        var params = new URLSearchParams(window.location.search);
        return {
            eco: (params.get('eco') || '').toLowerCase(),
            spiral: (params.get('spiral') || '').toLowerCase()
        };
    }

    function loadContent() {
        var p = getParams();

        if (!p.eco || !ECOSYSTEM_META[p.eco]) {
            showError('Ecosistema no encontrado.');
            return;
        }
        if (!p.spiral || !SPIRAL_META[p.spiral]) {
            showError('Espiral no encontrado.');
            return;
        }

        var eco = ECOSYSTEM_META[p.eco];
        var spiral = SPIRAL_META[p.spiral];

        /* Update page header */
        var titleEl = document.querySelector('.yg-header-title');
        var subtitleEl = document.querySelector('.yg-header-subtitle');
        if (titleEl) titleEl.textContent = eco.fullName + ' — ' + spiral.cefr;
        if (subtitleEl) subtitleEl.textContent = spiral.subtitle;
        document.title = eco.name + ' ' + spiral.cefr + ' — El Viaje del Jaguar';

        /* Update back link */
        var backBtn = document.querySelector('.yg-back-btn');
        if (backBtn) backBtn.href = 'ecosystem.html?eco=' + p.eco;

        /* Fetch JSON content */
        var jsonUrl = 'content/' + p.eco + '_' + p.spiral + '.json';
        fetch(jsonUrl)
            .then(function(res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function(data) {
                var games = data.games || data;

                /* Update header with content-provided title if available */
                if (data.meta && data.meta.title) {
                    if (titleEl) titleEl.textContent = data.meta.title;
                    if (subtitleEl) subtitleEl.textContent = eco.fullName + ' — ' + spiral.cefr;
                }

                YaguaraEngine.init({
                    games: games,
                    container: document.getElementById('yaguaraCard'),
                    progressContainer: document.getElementById('yaguaraProgress'),
                    yaguaraPanel: document.getElementById('yaguaraPanel'),
                    destinationId: p.eco + '_' + p.spiral,
                    world: spiral.mundo,
                    spiral: p.spiral,
                    speechRate: spiral.speechRate,
                    storageKey: 'yaguara_' + p.eco + '_' + p.spiral + '_progress',
                    backUrl: 'ecosystem.html?eco=' + p.eco
                });
            })
            .catch(function(err) {
                showError('No se pudo cargar el contenido. Intenta de nuevo.');
            });
    }

    function showError(msg) {
        var card = document.getElementById('yaguaraCard');
        if (card) {
            card.innerHTML = '<div style="text-align:center;padding:3rem 1.5rem;">' +
                '<p style="font-size:1.2rem;color:var(--sand-muted);margin-bottom:1.5rem;">' + msg + '</p>' +
                '<a href="dashboard.html" style="color:var(--ochre);text-decoration:underline;">Volver al inicio</a>' +
                '</div>';
        }
    }

    /* Expose metadata for ecosystem.html and other pages */
    window.ContentLoader = {
        ECOSYSTEM_META: ECOSYSTEM_META,
        SPIRAL_META: SPIRAL_META,
        load: loadContent
    };

    /* Auto-load when DOM is ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadContent);
    } else {
        loadContent();
    }
})();
