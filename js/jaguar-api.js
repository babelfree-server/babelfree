/**
 * El Viaje del Jaguar — API Client & Sync Manager
 * IIFE exposing window.JaguarAPI
 * localStorage-first, server-second architecture
 */
(function() {
    'use strict';

    var API_BASE = '/api';
    var SESSION_KEY = 'jaguarUserSession';
    var SYNC_QUEUE_KEY = 'jaguarSyncQueue';

    // ── Internal Helpers ─────────────────────────────────────────────

    function _getSession() {
        try {
            var raw = localStorage.getItem(SESSION_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch(e) { return null; }
    }

    function _getToken() {
        var session = _getSession();
        return session ? session.serverToken : null;
    }

    function _setToken(token) {
        var session = _getSession() || {};
        session.serverToken = token;
        localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    }

    function _getCsrfToken() {
        var match = document.cookie.match(/(?:^|;\s*)csrf_token=([^;]+)/);
        return match ? match[1] : '';
    }

    function _handleSessionExpired() {
        localStorage.removeItem(SESSION_KEY);
        localStorage.removeItem('colombianStudentData');
        window.location.href = '/login';
    }

    function _request(method, path, body, opts401) {
        var headers = {
            'Content-Type': 'application/json'
        };

        var token = _getToken();
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        if (method === 'POST') {
            headers['X-CSRF-Token'] = _getCsrfToken();
        }

        var opts = {
            method: method,
            headers: headers,
            credentials: 'same-origin'
        };

        if (body && method === 'POST') {
            opts.body = JSON.stringify(body);
        }

        return fetch(API_BASE + path, opts).then(function(res) {
            if (res.status === 401 && !opts401) {
                _handleSessionExpired();
                return Promise.reject(new Error('Session expired'));
            }
            return res.json();
        });
    }

    // ── Sync Queue (offline resilience) ──────────────────────────────

    function _getQueue() {
        try {
            var raw = localStorage.getItem(SYNC_QUEUE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    }

    function _saveQueue(queue) {
        try { localStorage.setItem(SYNC_QUEUE_KEY, JSON.stringify(queue)); }
        catch (e) {
            while (queue.length > 10) queue.shift();
            try { localStorage.setItem(SYNC_QUEUE_KEY, JSON.stringify(queue)); } catch (e2) {}
        }
    }

    function _enqueue(method, path, body) {
        var queue = _getQueue();
        if (queue.length >= 100) queue.shift();
        queue.push({ method: method, path: path, body: body, ts: Date.now() });
        _saveQueue(queue);
    }

    function flushQueue() {
        var queue = _getQueue();
        if (!queue.length) return Promise.resolve();

        // Clear queue before flushing (prevents double-send)
        _saveQueue([]);

        var failed = [];
        var chain = Promise.resolve();

        queue.forEach(function(item) {
            chain = chain.then(function() {
                return _request(item.method, item.path, item.body).catch(function() {
                    failed.push(item);
                });
            });
        });

        return chain.then(function() {
            if (failed.length) {
                var existing = _getQueue();
                _saveQueue(existing.concat(failed));
            }
        });
    }

    // Auto-flush on reconnect
    window.addEventListener('online', function() {
        setTimeout(flushQueue, 1000);
    });

    // ── Public API ───────────────────────────────────────────────────

    var JaguarAPI = {

        /**
         * Register a new account
         */
        register: function(email, password, displayName, userType, interfaceLang, detectedLang, role, gender, nativeLang, marketingFields) {
            const payload = {
                email: email,
                password: password,
                display_name: displayName,
                user_type: userType || 'individual',
                role: role || (userType === 'classroom' ? 'teacher' : 'student'),
                interface_lang: interfaceLang || 'es',
                detected_lang: detectedLang || null,
                gender: gender || 'X',
                native_lang: nativeLang || null
            };
            if (marketingFields && typeof marketingFields === 'object') {
                if (marketingFields.dob) payload.dob = marketingFields.dob;
                if (marketingFields.country) payload.country = marketingFields.country;
                if (marketingFields.phone) payload.phone = marketingFields.phone;
                if (marketingFields.source) payload.source = marketingFields.source;
                if (marketingFields.goal) payload.goal = marketingFields.goal;
                payload.marketing_consent = !!marketingFields.consent;
                payload.data_consent = !!marketingFields.dataConsent;
            }
            return _request('POST', '/auth/register', payload, true);
        },

        updateProfile: function(data) {
            return _request('POST', '/auth/update-profile', data);
        },

        changePassword: function(currentPassword, newPassword) {
            return _request('POST', '/auth/change-password', {
                current_password: currentPassword,
                new_password: newPassword
            });
        },

        /**
         * Log in — returns { success, data: { token, user } }
         */
        login: function(email, password) {
            return _request('POST', '/auth/login', {
                email: email,
                password: password
            }, true);
        },

        /**
         * Log out — revokes server token, clears localStorage
         */
        logout: function() {
            var p = _request('POST', '/auth/logout', {}).catch(function() {});
            localStorage.removeItem(SESSION_KEY);
            localStorage.removeItem('colombianStudentData');
            localStorage.removeItem(SYNC_QUEUE_KEY);
            return p.then(function() {
                window.location.href = '/login';
            });
        },

        /**
         * Request password reset email
         */
        forgotPassword: function(email) {
            return _request('POST', '/auth/forgot', { email: email }, true);
        },

        /**
         * Reset password with token
         */
        resetPassword: function(token, newPassword) {
            return _request('POST', '/auth/reset', {
                token: token,
                password: newPassword
            }, true);
        },

        /**
         * Validate current session token
         */
        validateSession: function() {
            var token = _getToken();
            if (!token) return Promise.resolve(null);
            return _request('GET', '/session/validate').then(function(res) {
                return res.success ? res.data : null;
            }).catch(function() {
                return null;
            });
        },

        /**
         * Sync A1 lesson progress to server
         * Also writes to localStorage for instant local UX
         */
        syncA1Progress: function(moduleId, lessonId, data) {
            var payload = {
                module_id: moduleId,
                lesson_id: lessonId,
                current_step: data.currentStep || 0,
                total_steps: data.totalSteps || 0,
                is_complete: data.isComplete ? 1 : 0,
                matched_pairs: data.matchedPairs || 0
            };

            if (!_getToken()) return Promise.resolve();

            return _request('POST', '/progress/a1', payload).catch(function() {
                _enqueue('POST', '/progress/a1', payload);
            });
        },

        /**
         * Sync destination (A2+) progress to server
         * Reads current state from localStorage, sends to server
         */
        syncDestinationProgress: function(destinationId, storageKey) {
            storageKey = storageKey || 'yaguaraProgress';
            if (!_getToken()) return Promise.resolve();

            try {
                var raw = localStorage.getItem(storageKey);
                if (!raw) return Promise.resolve();
                var local = JSON.parse(raw);

                var completed = 0;
                var completedMap = local.completedMap || [];
                for (var i = 0; i < completedMap.length; i++) {
                    if (completedMap[i]) completed++;
                }

                var payload = {
                    destination_id: destinationId,
                    storage_key: storageKey,
                    current_encounter: local.currentEncounter || 0,
                    completed_count: completed,
                    completed_map: completedMap,
                    total_encounters: completedMap.length,
                    is_complete: completed === completedMap.length && completedMap.length > 0 ? 1 : 0
                };

                return _request('POST', '/progress/destinations', payload).catch(function() {
                    _enqueue('POST', '/progress/destinations', payload);
                });
            } catch(e) {
                return Promise.resolve();
            }
        },

        /**
         * Pull all progress from server, merge into localStorage (server wins if newer)
         */
        pullAllProgress: function() {
            if (!_getToken()) return Promise.resolve();

            return Promise.all([
                _request('GET', '/progress/a1').catch(function() { return null; }),
                _request('GET', '/progress/destinations').catch(function() { return null; }),
                _request('GET', '/stats').catch(function() { return null; }),
                _request('GET', '/settings').catch(function() { return null; }),
                _request('GET', '/progress/escaperooms').catch(function() { return null; })
            ]).then(function(results) {
                var a1Data = results[0];
                var destData = results[1];
                var statsData = results[2];
                var settingsData = results[3];
                var escapeData = results[4];

                // Merge A1 progress into localStorage
                if (a1Data && a1Data.success && a1Data.data && a1Data.data.progress) {
                    var a1Key = 'jaguarA1Progress';
                    var localA1Raw = localStorage.getItem(a1Key);
                    var localA1 = localA1Raw ? JSON.parse(localA1Raw) : {};

                    a1Data.data.progress.forEach(function(item) {
                        var id = item.module_id + '/' + item.lesson_id;
                        var serverTime = new Date(item.updated_at).getTime();
                        var localTime = localA1[id] && localA1[id].timestamp
                            ? new Date(localA1[id].timestamp).getTime() : 0;

                        if (serverTime > localTime) {
                            localA1[id] = {
                                moduleId: item.module_id,
                                lessonId: item.lesson_id,
                                currentStep: item.current_step,
                                totalSteps: item.total_steps,
                                isComplete: !!item.is_complete,
                                matchedPairs: item.matched_pairs,
                                timestamp: item.updated_at
                            };
                        }
                    });

                    localStorage.setItem(a1Key, JSON.stringify(localA1));
                }

                // Merge destination progress into localStorage
                if (destData && destData.success && destData.data && destData.data.progress) {
                    destData.data.progress.forEach(function(dest) {
                        var key = dest.storage_key;
                        if (!key) return;

                        var localRaw = localStorage.getItem(key);
                        var localData = localRaw ? JSON.parse(localRaw) : null;
                        var serverTime = new Date(dest.updated_at).getTime();
                        var localTime = localData && localData.timestamp
                            ? new Date(localData.timestamp).getTime() : 0;

                        // Server wins if newer
                        if (serverTime > localTime) {
                            localStorage.setItem(key, JSON.stringify({
                                version: 2,
                                currentEncounter: dest.current_encounter,
                                completedCount: dest.completed_count,
                                completedMap: dest.completed_map || [],
                                destinationId: dest.destination_id,
                                timestamp: dest.updated_at
                            }));
                        }
                    });
                }

                // Merge stats into colombianStudentData
                if (statsData && statsData.success && statsData.data && statsData.data.stats) {
                    var stats = statsData.data.stats;
                    var saved = localStorage.getItem('colombianStudentData');
                    var studentData = saved ? JSON.parse(saved) : {};

                    studentData.lessonsCompleted = parseInt(stats.lessons_completed) || studentData.lessonsCompleted || 0;
                    studentData.vocabularyMastered = parseInt(stats.vocabulary_mastered) || studentData.vocabularyMastered || 0;
                    studentData.dayStreak = parseInt(stats.day_streak) || studentData.dayStreak || 0;
                    studentData.perfectScores = parseInt(stats.perfect_scores) || studentData.perfectScores || 0;
                    studentData.culturalKnowledge = parseInt(stats.cultural_knowledge) || studentData.culturalKnowledge || 0;

                    localStorage.setItem('colombianStudentData', JSON.stringify(studentData));
                }

                // Merge escape room progress into localStorage
                if (escapeData && escapeData.success && escapeData.data && escapeData.data.progress) {
                    var erKey = 'jaguarEscapeRoomProgress';
                    var localERRaw = localStorage.getItem(erKey);
                    var localER = localERRaw ? JSON.parse(localERRaw) : {};

                    escapeData.data.progress.forEach(function(item) {
                        var id = item.destination_id;
                        var serverTime = new Date(item.updated_at).getTime();
                        var localTime = localER[id] && localER[id].timestamp
                            ? new Date(localER[id].timestamp).getTime() : 0;

                        if (serverTime > localTime) {
                            localER[id] = {
                                destinationId: item.destination_id,
                                puzzlesSolved: item.puzzles_solved || {},
                                isComplete: !!item.is_complete,
                                fragmentItem: item.fragment_item,
                                timestamp: item.updated_at
                            };
                        }
                    });

                    localStorage.setItem(erKey, JSON.stringify(localER));
                }

                return {
                    a1: a1Data,
                    destinations: destData,
                    stats: statsData,
                    settings: settingsData,
                    escapeRooms: escapeData
                };
            });
        },

        /**
         * Push dashboard stats to server
         */
        syncStats: function(statsObj) {
            if (!_getToken()) return Promise.resolve();
            return _request('POST', '/stats', statsObj).catch(function() {
                _enqueue('POST', '/stats', statsObj);
            });
        },

        /**
         * Flush offline sync queue
         */
        flushQueue: flushQueue,

        /**
         * Track game performance (silent DELE evaluation data)
         */
        trackGame: function(data) {
            if (!_getToken()) return Promise.resolve();
            return _request('POST', '/tracking/game', data).catch(function() { /* silent */ });
        },

        /**
         * Check if user is authenticated (has server token)
         */
        isAuthenticated: function() {
            return !!_getToken();
        },

        /**
         * Get current user data from localStorage session
         */
        getUser: function() {
            var session = _getSession();
            return session ? session.user || null : null;
        },

        /**
         * Get user role (student, teacher, admin)
         */
        getRole: function() {
            var session = _getSession();
            return (session && session.role) || (session && session.user && session.user.role) || 'student';
        },

        /**
         * Get user tier (free, premium)
         */
        getTier: function() {
            var session = _getSession();
            return (session && session.tier) || (session && session.user && session.user.tier) || 'free';
        },

        /**
         * Check if user has premium tier
         */
        isPremium: function() {
            return this.getTier() === 'premium';
        },

        /**
         * Check if user is a teacher
         */
        isTeacher: function() {
            return this.getRole() === 'teacher';
        },

        /**
         * Fetch free play catalog (public, no auth required)
         */
        getFreeplayCatalog: function(level, gameType) {
            var params = [];
            if (level) params.push('level=' + encodeURIComponent(level));
            if (gameType) params.push('type=' + encodeURIComponent(gameType));
            var qs = params.length ? '?' + params.join('&') : '';
            return fetch(API_BASE + '/freeplay/catalog' + qs).then(function(res) {
                return res.json();
            });
        },

        /**
         * Get aggregated progress for an ecosystem across all spirals.
         * Reads yaguara_{eco}_{spiral}_progress keys from localStorage.
         * Returns { spirals: { a1: {completed,total,percentage}, ... }, totalCompleted, totalEncounters, percentage }
         */
        getEcosystemProgress: function(ecoName) {
            var spirals = ['a1','a2','b1','b2','c1','c2'];
            var result = { spirals: {}, totalCompleted: 0, totalEncounters: 0, percentage: 0 };
            for (var i = 0; i < spirals.length; i++) {
                var key = 'yaguara_' + ecoName + '_' + spirals[i] + '_progress';
                var completed = 0, total = 0, pct = 0;
                try {
                    var raw = localStorage.getItem(key);
                    if (raw) {
                        var data = JSON.parse(raw);
                        if (data.completedMap) {
                            total = data.completedMap.length;
                            for (var j = 0; j < data.completedMap.length; j++) {
                                if (data.completedMap[j]) completed++;
                            }
                        } else {
                            completed = data.completedCount || 0;
                            total = completed;
                        }
                        pct = total > 0 ? Math.round((completed / total) * 100) : 0;
                    }
                } catch(e) {}
                result.spirals[spirals[i]] = { completed: completed, total: total, percentage: pct };
                result.totalCompleted += completed;
                result.totalEncounters += total;
            }
            result.percentage = result.totalEncounters > 0 ? Math.round((result.totalCompleted / result.totalEncounters) * 100) : 0;
            return result;
        },

        /**
         * Sync escape room progress to server
         */
        syncEscapeRoomProgress: function(destinationId, puzzlesSolved, isComplete, fragmentItem) {
            var payload = {
                destination_id: destinationId,
                puzzles_solved: puzzlesSolved,
                is_complete: isComplete ? 1 : 0,
                fragment_item: fragmentItem || null
            };

            if (!_getToken()) return Promise.resolve();

            return _request('POST', '/progress/escaperooms', payload).catch(function() {
                _enqueue('POST', '/progress/escaperooms', payload);
            });
        },

        /**
         * Sync busqueda (riddle quest) progress to server
         */
        syncBusquedaProgress: function(state) {
            if (!_getToken()) return Promise.resolve();
            var payload = {
                solved_riddles:  state.solvedRiddles || [],
                bridge_segments: state.bridgeSegments || 0,
                rana_opacity:    state.ranaOpacity || 0,
                rana_name:       state.ranaName || null,
                journal_entries: state.journalEntries || []
            };
            return _request('POST', '/busqueda/progress', payload).catch(function() {
                _enqueue('POST', '/busqueda/progress', payload);
            });
        },

        /**
         * Get busqueda progress from server (for cross-device sync)
         */
        getBusquedaProgress: function() {
            if (!_getToken()) return Promise.resolve(null);
            return _request('GET', '/busqueda/progress').then(function(res) {
                return res.success ? res.data.progress : null;
            }).catch(function() { return null; });
        },

        /**
         * Sync "Mi aventura por Colombia" composition progress to server
         */
        syncAdventureProgress: function(state) {
            if (!_getToken()) return Promise.resolve();
            var payload = {
                chapters:              state.chapters || {},
                earned_letters:        state.earnedLetters || [],
                earned_words:          state.earnedWords || [],
                earned_sentences:      state.earnedSentences || [],
                composition_revealed:  state.compositionRevealed || false,
                total_words_written:   state.totalWordsWritten || 0,
                started_at:            state.startedAt || null
            };
            return _request('POST', '/adventure/progress', payload).catch(function() {
                _enqueue('POST', '/adventure/progress', payload);
            });
        },

        /**
         * Get adventure progress from server (cross-device sync)
         */
        getAdventureProgress: function() {
            if (!_getToken()) return Promise.resolve(null);
            return _request('GET', '/adventure/progress').then(function(res) {
                return res.success ? res.data.progress : null;
            }).catch(function() { return null; });
        },

        /**
         * Sync personal lexicon to server
         */
        syncLexiconProgress: function(state) {
            if (!_getToken()) return Promise.resolve();
            var words = state.words || {};
            var payload = {
                lexicon_data: words,
                word_count: Object.keys(words).length
            };
            return _request('POST', '/lexicon/progress', payload).catch(function() {
                _enqueue('POST', '/lexicon/progress', payload);
            });
        },

        /**
         * Get personal lexicon from server (cross-device sync)
         */
        getLexiconProgress: function() {
            if (!_getToken()) return Promise.resolve(null);
            return _request('GET', '/lexicon/progress').then(function(res) {
                return res.success ? res.data.progress : null;
            }).catch(function() { return null; });
        },

        /**
         * Get collected escape room fragments (meta-quest)
         */
        getFragments: function() {
            if (!_getToken()) return Promise.resolve([]);
            return _request('GET', '/progress/fragments').then(function(res) {
                return res.success ? res.data.fragments : [];
            }).catch(function() { return []; });
        },

        /**
         * Record vocabulary encounter from game completion
         */
        recordVocabularyEncounter: function(words, correct, destinationId) {
            if (!_getToken()) return Promise.resolve();
            var payload = {
                words: words,
                correct: correct,
                destination_id: destinationId
            };
            return _request('POST', '/glossary/encounter', payload).catch(function() {
                _enqueue('POST', '/glossary/encounter', payload);
            });
        },

        /**
         * Get glossary vocabulary for an ecosystem
         */
        getGlossaryWords: function(ecosystem, level, dest) {
            var params = '?ecosystem=' + (ecosystem || '');
            if (level) params += '&level=' + level;
            if (dest) params += '&dest=' + dest;
            return _request('GET', '/glossary/words' + params);
        },

        /**
         * Get glossary word detail
         */
        getGlossaryWord: function(wordId) {
            return _request('GET', '/glossary/word/' + wordId);
        },

        /**
         * Get glossary mastery stats
         */
        getGlossaryStats: function() {
            return _request('GET', '/glossary/stats');
        },

        /**
         * Search glossary vocabulary
         */
        searchGlossary: function(query) {
            return _request('GET', '/glossary/search?q=' + encodeURIComponent(query));
        },

        /**
         * Delete account permanently (GDPR)
         */
        deleteAccount: function(password) {
            return _request('POST', '/auth/delete-account', { password: password }).then(function(res) {
                if (res.success) {
                    // Remove ALL student data from localStorage (GDPR compliance)
                    var keys = [
                        SESSION_KEY, 'colombianStudentData', SYNC_QUEUE_KEY,
                        'jaguarA1Progress', 'jaguarEscapeRoomProgress',
                        'yaguara_aventura', 'yaguara_cronica', 'yaguara_busqueda',
                        'yaguara_lexicon', 'yaguara_eco_affinity', 'yaguara_harvest',
                        'yaguara_evidence', 'yaguara_srs', 'yaguara_journey',
                        'yaguara_guardian_words', 'yaguara_latinkb_tip'
                    ];
                    keys.forEach(function(k) { localStorage.removeItem(k); });
                    // Clear any per-destination keys
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key && (key.indexOf('yaguara_') === 0 || key.indexOf('jaguar') === 0)) {
                            localStorage.removeItem(key);
                            i--; // adjust index after removal
                        }
                    }
                    window.location.href = '/';
                }
                return res;
            });
        },

        /**
         * Export all user data as JSON download (GDPR)
         */
        exportData: function() {
            return _request('GET', '/auth/export-data').then(function(res) {
                if (res.success) {
                    var blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'mis-datos-yaguara.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
                return res;
            });
        }
    };

    window.JaguarAPI = JaguarAPI;
})();
