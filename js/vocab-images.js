/**
 * Vocab Image Bank — La Aventura de Yaguará
 *
 * Maps vocabulary words to image paths under img/vocab/.
 * Falls back to emoji when an image hasn't been added yet.
 *
 * Usage in matching games:
 *   <script src="../../js/vocab-images.js"></script>
 *   var html = VocabImages.cardHTML('agua');
 *   // returns <img> if file exists, or a large emoji <span> as fallback
 *
 * Image naming convention:
 *   img/vocab/{category}/{word}.webp   (preferred — small, fast)
 *   img/vocab/{category}/{word}.png    (fallback)
 *   img/vocab/{category}/{word}.jpg    (fallback)
 *
 * To add a new image, just drop the file in the right folder.
 * The system auto-detects .webp → .png → .jpg.
 */
(function(global) {
    'use strict';

    var BASE = 'img/vocab/';

    // ─── Master vocabulary registry ────────────────────────────
    // key: word identifier used in game data-attributes
    // category: subfolder in img/vocab/
    // emoji: fallback when no image file exists
    // label: Spanish display label (optional, for accessibility)
    var registry = {

        // ── Module 1 — Family ──────────────────────────────────
        madre:    { category: 'family',  emoji: '👩', label: 'Madre' },
        padre:    { category: 'family',  emoji: '👨', label: 'Padre' },
        hermano:  { category: 'family',  emoji: '👦', label: 'Hermano' },
        hermana:  { category: 'family',  emoji: '👧', label: 'Hermana' },
        familia:  { category: 'family',  emoji: '👨‍👩‍👧‍👦', label: 'Familia' },

        // ── Module 2 — Animals ─────────────────────────────────
        mono:     { category: 'animals', emoji: '🐒', label: 'Mono' },
        tucan:    { category: 'animals', emoji: '🦜', label: 'Tucán' },
        rana:     { category: 'animals', emoji: '🐸', label: 'Rana' },
        grillo:   { category: 'animals', emoji: '🦗', label: 'Grillo' },
        jaguar:   { category: 'animals', emoji: '🐆', label: 'Jaguar' },
        anaconda: { category: 'animals', emoji: '🐍', label: 'Anaconda' },
        guacamaya:{ category: 'animals', emoji: '🦜', label: 'Guacamaya' },

        // ── Module 3 — Water ───────────────────────────────────
        agua:          { category: 'water', emoji: '💧', label: 'Agua' },
        rio:           { category: 'water', emoji: '🏞️', label: 'Río' },
        beber:         { category: 'water', emoji: '🥤', label: 'Beber' },
        sed:           { category: 'water', emoji: '😓', label: 'Sed' },
        nadar:         { category: 'water', emoji: '🏊', label: 'Nadar' },
        agua_limpia:   { category: 'water', emoji: '🫧', label: 'Agua limpia' },
        buscar_agua:   { category: 'water', emoji: '🔎', label: 'Buscar agua' },
        calor:         { category: 'water', emoji: '🌞', label: 'Hace calor' },
        refrescar:     { category: 'water', emoji: '💦', label: 'Refrescarse' },

        // ── Module 4 — Home ────────────────────────────────────
        casa:          { category: 'home', emoji: '🏡', label: 'Casa' },
        cueva:         { category: 'home', emoji: '🕳️', label: 'Cueva' },
        dormir:        { category: 'home', emoji: '😴', label: 'Dormir' },
        sueno:         { category: 'home', emoji: '💤', label: 'Sueño' },
        noche:         { category: 'home', emoji: '🌙', label: 'Noche' },
        peligro:       { category: 'home', emoji: '⚡', label: 'Peligro' },
        lluvia:        { category: 'home', emoji: '🌧️', label: 'Lluvia' },
        seguro:        { category: 'home', emoji: '🛖', label: 'Seguro' },
        ir_a_dormir:   { category: 'home', emoji: '🛏️', label: 'Ir a dormir' },
        buscar_casa:   { category: 'home', emoji: '🏠', label: 'Buscar casa' },
        ir_a_cueva:    { category: 'home', emoji: '🪨', label: 'Ir a la cueva' },
        ir_a_casa:     { category: 'home', emoji: '🏡', label: 'Ir a casa' },

        // ── Module 5 — Food ────────────────────────────────────
        pez:       { category: 'food', emoji: '🐟', label: 'Pez' },
        mango:     { category: 'food', emoji: '🥭', label: 'Mango' },
        platano:   { category: 'food', emoji: '🍌', label: 'Plátano' },
        comer:     { category: 'food', emoji: '🍽️', label: 'Comer' },
        me_gusta:  { category: 'food', emoji: '😋', label: 'Me gusta' },
        comer_rico:{ category: 'food', emoji: '🤤', label: 'Comer rico' },

        // ── Module 6 — Friends ─────────────────────────────────
        amigo_triste:   { category: 'friends', emoji: '😢', label: 'Amigo triste' },
        amigo_perdido:  { category: 'friends', emoji: '😟', label: 'Amigo perdido' },
        amigo_aburrido: { category: 'friends', emoji: '😑', label: 'Amigo aburrido' },
        ver_amigo:      { category: 'friends', emoji: '👋', label: 'Ver un amigo' },
        ayudar:    { category: 'friends', emoji: '🤝', label: 'Ayudar' },
        hablar:    { category: 'friends', emoji: '🗣️', label: 'Hablar' },
        decir_hola:{ category: 'friends', emoji: '👋', label: 'Decir hola' },
        jugar:     { category: 'friends', emoji: '⚽', label: 'Jugar' },

        // ── General actions/concepts ───────────────────────────
        escuchar:  { category: 'actions',  emoji: '👂', label: 'Escuchar' },
        caminar:   { category: 'actions',  emoji: '🚶', label: 'Caminar' },
        correr:    { category: 'actions',  emoji: '🏃', label: 'Correr' },
        grande:    { category: 'concepts', emoji: '⬆️', label: 'Grande' },
        pequeno:   { category: 'concepts', emoji: '⬇️', label: 'Pequeño' },
        bonito:    { category: 'concepts', emoji: '✨', label: 'Bonito' }
    };

    // ─── Image existence cache ─────────────────────────────────
    var imageCache = {};

    /**
     * Get the image path for a vocabulary word.
     * Checks .webp first, then .png, then .jpg.
     * Returns null if no entry exists in the registry.
     */
    function getImagePath(word) {
        var entry = registry[word];
        if (!entry) return null;
        // Return the base path — the cardHTML function handles format detection
        return BASE + entry.category + '/' + word;
    }

    /**
     * Generate an HTML string for a vocab card.
     * Shows the image if it loads, falls back to a large emoji.
     *
     * @param {string} word - The vocabulary key
     * @param {object} opts - Optional: { size: '80px', showLabel: true }
     * @returns {string} HTML string
     */
    function cardHTML(word, opts) {
        opts = opts || {};
        var size = opts.size || '80px';
        var showLabel = opts.showLabel !== false;
        var entry = registry[word];

        if (!entry) {
            return '<span style="font-size:' + size + ';display:block;text-align:center;">❓</span>';
        }

        var basePath = BASE + entry.category + '/' + word;
        var id = 'vocab-img-' + word + '-' + Math.random().toString(36).substr(2, 5);

        var html = '<div class="vocab-card" style="text-align:center;">';

        // Image with emoji fallback via onerror chain
        html += '<img id="' + id + '" '
            + 'src="' + basePath + '.webp" '
            + 'alt="' + (entry.label || word) + '" '
            + 'style="width:' + size + ';height:' + size + ';object-fit:contain;border-radius:12px;display:block;margin:0 auto;" '
            + 'onerror="this.onerror=function(){this.onerror=function(){this.style.display=\'none\';this.nextElementSibling.style.display=\'block\'};this.src=\'' + basePath + '.jpg\'};this.src=\'' + basePath + '.png\'" '
            + '>';

        // Emoji fallback (hidden by default, shown if all image formats fail)
        html += '<span style="font-size:calc(' + size + ' * 0.7);display:none;line-height:' + size + ';">'
            + entry.emoji + '</span>';

        if (showLabel) {
            html += '<div style="font-size:0.85rem;font-weight:600;margin-top:6px;color:inherit;">'
                + (entry.label || word) + '</div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Get just the emoji for a word (useful for inline text).
     */
    function emoji(word) {
        var entry = registry[word];
        return entry ? entry.emoji : '❓';
    }

    /**
     * Get the label for a word.
     */
    function label(word) {
        var entry = registry[word];
        return entry ? (entry.label || word) : word;
    }

    /**
     * Check if a word exists in the registry.
     */
    function has(word) {
        return word in registry;
    }

    /**
     * Get all words in a category.
     */
    function byCategory(cat) {
        var result = [];
        for (var key in registry) {
            if (registry[key].category === cat) {
                result.push(key);
            }
        }
        return result;
    }

    /**
     * Add a custom word to the registry at runtime.
     */
    function register(word, category, emojiChar, labelText) {
        registry[word] = {
            category: category,
            emoji: emojiChar,
            label: labelText || word
        };
    }

    // ─── Public API ────────────────────────────────────────────
    global.VocabImages = {
        cardHTML: cardHTML,
        emoji: emoji,
        label: label,
        has: has,
        byCategory: byCategory,
        register: register,
        getImagePath: getImagePath,
        registry: registry
    };

})(typeof window !== 'undefined' ? window : this);
