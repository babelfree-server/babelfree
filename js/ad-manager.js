/**
 * El Viaje del Jaguar — Ad Manager
 * IIFE exposing window.AdManager
 * Hides ads for premium users, shows for free/anonymous
 */
(function() {
    'use strict';

    function _isPremium() {
        return window.JaguarAPI && JaguarAPI.isPremium && JaguarAPI.isPremium();
    }

    function _applyState() {
        var premium = _isPremium();
        var slots = document.querySelectorAll('.ad-slot');

        if (premium) {
            document.body.classList.add('premium-no-ads');
            for (var i = 0; i < slots.length; i++) {
                slots[i].style.display = 'none';
            }
        } else {
            document.body.classList.remove('premium-no-ads');
            for (var i = 0; i < slots.length; i++) {
                slots[i].style.display = '';
            }
        }
    }

    function init() {
        _applyState();
    }

    function refresh() {
        _applyState();
    }

    window.AdManager = {
        init: init,
        refresh: refresh
    };
})();
