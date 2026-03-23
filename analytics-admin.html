<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard — Babel Free Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/tower-logo.png">
    <link rel="stylesheet" href="/luxury.css">
    <style>
        /* Reset luxury.css body overlay for admin page */
        body::before { display: none !important; }
        body::after { display: none !important; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0a12;
            color: #e0e0e0;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Auth overlay */
        .auth-overlay {
            position: fixed;
            inset: 0;
            background: #0a0a12;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 3rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .auth-box h2 {
            font-family: var(--font-display, 'Playfair Display', serif);
            color: var(--lux-gold, #d4a843);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        .auth-box input {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 1rem;
        }
        .auth-box input:focus {
            outline: none;
            border-color: var(--lux-gold, #d4a843);
        }
        .auth-box button {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #bf953f, #b38728);
            color: #0a0a12;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .auth-box button:hover { opacity: 0.9; }
        .auth-error {
            color: #e74c3c;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: none;
        }

        /* Dashboard layout */
        .dash-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dash-header h1 {
            font-family: var(--font-display, 'Playfair Display', serif);
            font-size: 1.5rem;
            color: var(--lux-gold, #d4a843);
        }
        .dash-header .logout-btn {
            background: none;
            border: 1px solid rgba(255,255,255,0.2);
            color: #999;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .dash-header .logout-btn:hover { color: #e0e0e0; border-color: rgba(255,255,255,0.4); }
        .dash-header .refresh-btn {
            background: none;
            border: 1px solid rgba(212,168,67,0.3);
            color: var(--lux-gold, #d4a843);
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .dash-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .loading-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        .loading-state .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(212,168,67,0.2);
            border-top-color: var(--lux-gold, #d4a843);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Stat cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--lux-gold, #d4a843);
            line-height: 1.2;
        }
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.3rem;
        }

        /* Section panels */
        .panel {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .panel h2 {
            font-family: var(--font-display, 'Playfair Display', serif);
            font-size: 1.2rem;
            color: var(--lux-gold, #d4a843);
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        /* Bar chart */
        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            height: 180px;
            padding: 0 0.5rem;
        }
        .bar-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
        }
        .bar {
            width: 100%;
            max-width: 60px;
            border-radius: 6px 6px 0 0;
            background: linear-gradient(180deg, #d4a843, #8B6914);
            min-height: 4px;
            transition: height 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .bar-label {
            font-size: 0.75rem;
            color: #888;
            margin-top: 0.5rem;
            text-align: center;
        }
        .bar-value {
            font-size: 0.75rem;
            color: var(--lux-gold, #d4a843);
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            text-align: left;
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .data-table td {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        /* Two-col layout */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        @media (max-width: 768px) {
            .two-col { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* Trend mini-chart */
        .trend-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .trend-date {
            font-size: 0.75rem;
            color: #666;
            width: 70px;
            flex-shrink: 0;
        }
        .trend-bar-wrap {
            flex: 1;
            height: 18px;
            background: rgba(255,255,255,0.04);
            border-radius: 4px;
            overflow: hidden;
        }
        .trend-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B6914, #d4a843);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .trend-count {
            font-size: 0.75rem;
            color: #999;
            width: 30px;
            text-align: right;
            flex-shrink: 0;
        }

        .error-msg {
            background: rgba(231,76,60,0.1);
            border: 1px solid rgba(231,76,60,0.3);
            color: #e74c3c;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Auth overlay -->
    <div class="auth-overlay" id="authOverlay">
        <div class="auth-box">
            <h2>Admin analytics</h2>
            <p style="color:#888; margin-bottom:1.5rem; font-size:0.9rem;">Sign in with your admin account</p>
            <input type="email" id="authEmail" placeholder="Email" autocomplete="email">
            <input type="password" id="authPassword" placeholder="Password" autocomplete="current-password">
            <button onclick="doLogin()">Sign in</button>
            <p class="auth-error" id="authError"></p>
        </div>
    </div>

    <!-- Dashboard -->
    <div id="dashboard" style="display:none;">
        <div class="dash-header">
            <h1>Analytics dashboard</h1>
            <div>
                <button class="refresh-btn" onclick="loadAll()">Refresh</button>
                <button class="logout-btn" onclick="doLogout()">Sign out</button>
            </div>
        </div>

        <div class="dash-content">
            <div class="loading-state" id="loadingState">
                <div class="spinner"></div>
                <p>Loading analytics...</p>
            </div>

            <div id="dashData" style="display:none;">
                <!-- Top stats -->
                <div class="stat-grid" id="statGrid"></div>

                <!-- CEFR distribution + Activity trends -->
                <div class="two-col">
                    <div class="panel">
                        <h2>CEFR level distribution</h2>
                        <div class="bar-chart" id="cefrChart"></div>
                    </div>
                    <div class="panel">
                        <h2>Activity trend (30 days)</h2>
                        <div id="activityTrend"></div>
                    </div>
                </div>

                <!-- Destination struggles + Feedback -->
                <div class="two-col">
                    <div class="panel">
                        <h2>Hardest destinations</h2>
                        <div id="struggleTable"></div>
                    </div>
                    <div class="panel">
                        <h2>Registration trend (30 days)</h2>
                        <div id="regTrend"></div>
                    </div>
                </div>

                <!-- Funnel + Escape rooms -->
                <div class="two-col">
                    <div class="panel">
                        <h2>Destination funnel</h2>
                        <div id="funnelChart" style="max-height:250px; overflow-y:auto;"></div>
                    </div>
                    <div class="panel">
                        <h2>Busqueda (riddle quest)</h2>
                        <div id="busquedaStats"></div>
                    </div>
                </div>

                <!-- Languages + Feedback details -->
                <div class="two-col">
                    <div class="panel">
                        <h2>Native language distribution</h2>
                        <div id="langChart"></div>
                    </div>
                    <div class="panel">
                        <h2>Feedback overview</h2>
                        <div id="feedbackStats"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var API = '/api';
        var token = localStorage.getItem('jaguarToken') || '';

        // Check if already authenticated
        if (token) {
            showDashboard();
        }

        // Login
        window.doLogin = function() {
            var email = document.getElementById('authEmail').value.trim();
            var pw = document.getElementById('authPassword').value;
            var errEl = document.getElementById('authError');
            errEl.style.display = 'none';

            if (!email || !pw) {
                errEl.textContent = 'Please enter email and password.';
                errEl.style.display = 'block';
                return;
            }

            fetch(API + '/auth/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({email: email, password: pw})
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data && data.data.token) {
                    token = data.data.token;
                    localStorage.setItem('jaguarToken', token);
                    showDashboard();
                } else {
                    errEl.textContent = data.error || 'Login failed.';
                    errEl.style.display = 'block';
                }
            })
            .catch(function() {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            });
        };

        // Allow Enter key to submit
        document.getElementById('authPassword').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') doLogin();
        });

        window.doLogout = function() {
            localStorage.removeItem('jaguarToken');
            token = '';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('authOverlay').style.display = 'flex';
        };

        function showDashboard() {
            document.getElementById('authOverlay').style.display = 'none';
            document.getElementById('dashboard').style.display = 'block';
            loadAll();
        }

        window.loadAll = function() {
            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('dashData').style.display = 'none';

            fetch(API + '/analytics/all', {
                headers: {'Authorization': 'Bearer ' + token}
            })
            .then(function(r) {
                if (r.status === 403) {
                    doLogout();
                    var errEl = document.getElementById('authError');
                    errEl.textContent = 'Admin access required. Please sign in with an admin account.';
                    errEl.style.display = 'block';
                    throw new Error('Forbidden');
                }
                if (r.status === 401) {
                    doLogout();
                    throw new Error('Unauthorized');
                }
                return r.json();
            })
            .then(function(resp) {
                if (!resp.success) throw new Error(resp.error || 'Failed to load');
                var d = resp.data;
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('dashData').style.display = 'block';
                renderOverview(d.overview);
                renderCefr(d.cefr);
                renderActivity(d.activity);
                renderRegistrations(d.registrations);
                renderStruggles(d.struggles);
                renderFunnel(d.funnel);
                renderBusqueda(d.busqueda);
                renderLanguages(d.languages);
                renderFeedback(d.feedback);
            })
            .catch(function(err) {
                document.getElementById('loadingState').innerHTML = '<div class="error-msg">Failed to load analytics: ' + (err.message || 'Unknown error') + '</div>';
            });
        };

        function renderOverview(o) {
            if (!o) return;
            var cards = [
                {v: o.totalUsers, l: 'Total users'},
                {v: o.activeUsers7d, l: 'Active (7d)'},
                {v: o.activeUsers30d, l: 'Active (30d)'},
                {v: o.premiumUsers, l: 'Premium'},
                {v: o.usersCompletedAny, l: 'Completed 1+ dest'},
                {v: o.usersCompletedAll, l: 'Completed all 58'},
                {v: o.newToday, l: 'New today'},
                {v: o.newThisWeek, l: 'New this week'}
            ];
            var html = '';
            cards.forEach(function(c) {
                html += '<div class="stat-card"><div class="stat-value">' + fmt(c.v) + '</div><div class="stat-label">' + c.l + '</div></div>';
            });
            document.getElementById('statGrid').innerHTML = html;
        }

        function renderCefr(cefr) {
            var el = document.getElementById('cefrChart');
            if (!cefr || !cefr.length) { el.innerHTML = '<p style="color:#666;">No CEFR data yet.</p>'; return; }
            var max = Math.max.apply(null, cefr.map(function(c){ return parseInt(c.users) || 0; }));
            if (max === 0) max = 1;
            var colors = {A1:'#27ae60',A2:'#2ecc71',B1:'#3498db',B2:'#2980b9',C1:'#8e44ad',C2:'#6c3483'};
            var html = '';
            cefr.forEach(function(c) {
                var pct = Math.max((parseInt(c.users) / max) * 100, 3);
                var clr = colors[c.cefr_level] || '#d4a843';
                html += '<div class="bar-col">'
                    + '<div class="bar-value">' + fmt(c.users) + '</div>'
                    + '<div class="bar" style="height:' + pct + '%;background:' + clr + ';"></div>'
                    + '<div class="bar-label">' + c.cefr_level + '</div>'
                    + '</div>';
            });
            el.innerHTML = html;
        }

        function renderTrendRows(container, data, dateKey, valKey) {
            var el = document.getElementById(container);
            if (!data || !data.length) { el.innerHTML = '<p style="color:#666;">No data yet.</p>'; return; }
            // Show last 14 entries
            var items = data.slice(-14);
            var max = Math.max.apply(null, items.map(function(r){ return parseInt(r[valKey]) || 0; }));
            if (max === 0) max = 1;
            var html = '';
            items.forEach(function(r) {
                var val = parseInt(r[valKey]) || 0;
                var pct = (val / max) * 100;
                var d = r[dateKey] || '';
                var short = d.length >= 10 ? d.slice(5) : d;
                html += '<div class="trend-row">'
                    + '<span class="trend-date">' + short + '</span>'
                    + '<div class="trend-bar-wrap"><div class="trend-bar-fill" style="width:' + pct + '%;"></div></div>'
                    + '<span class="trend-count">' + val + '</span>'
                    + '</div>';
            });
            el.innerHTML = html;
        }

        function renderActivity(data) { renderTrendRows('activityTrend', data, 'date', 'active_users'); }
        function renderRegistrations(data) { renderTrendRows('regTrend', data, 'date', 'signups'); }

        function renderStruggles(struggles) {
            var el = document.getElementById('struggleTable');
            if (!struggles || !struggles.length) { el.innerHTML = '<p style="color:#666;">Not enough data yet.</p>'; return; }
            var html = '<table class="data-table"><thead><tr><th>Destination</th><th>Starts</th><th>Completions</th><th>Rate</th></tr></thead><tbody>';
            struggles.forEach(function(s) {
                html += '<tr><td>' + s.destination_id + '</td><td>' + s.starts + '</td><td>' + s.completions + '</td><td>' + s.completion_rate + '%</td></tr>';
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        }

        function renderFunnel(funnel) {
            var el = document.getElementById('funnelChart');
            if (!funnel || !funnel.length) { el.innerHTML = '<p style="color:#666;">No funnel data yet.</p>'; return; }
            var max = parseInt(funnel[0].users_reached) || 1;
            var html = '';
            funnel.forEach(function(f) {
                var val = parseInt(f.users_reached) || 0;
                var pct = (val / max) * 100;
                html += '<div class="trend-row">'
                    + '<span class="trend-date">Dest ' + f.dest_num + '</span>'
                    + '<div class="trend-bar-wrap"><div class="trend-bar-fill" style="width:' + pct + '%;"></div></div>'
                    + '<span class="trend-count">' + val + '</span>'
                    + '</div>';
            });
            el.innerHTML = html;
        }

        function renderBusqueda(b) {
            var el = document.getElementById('busquedaStats');
            if (!b) { el.innerHTML = '<p style="color:#666;">No data yet.</p>'; return; }
            var html = '<div class="stat-grid" style="margin-bottom:1rem;">'
                + '<div class="stat-card"><div class="stat-value">' + fmt(b.totalPlayers) + '</div><div class="stat-label">Players</div></div>'
                + '<div class="stat-card"><div class="stat-value">' + (b.avgBridgeSegments || 0) + '</div><div class="stat-label">Avg bridges</div></div>'
                + '<div class="stat-card"><div class="stat-value">' + fmt(b.ranasNamed) + '</div><div class="stat-label">Ranas named</div></div>'
                + '<div class="stat-card"><div class="stat-value">' + fmt(b.questCompleted) + '</div><div class="stat-label">Quest done</div></div>'
                + '</div>';
            if (b.distribution && b.distribution.length) {
                var max = Math.max.apply(null, b.distribution.map(function(d){ return parseInt(d.users) || 0; }));
                if (max === 0) max = 1;
                b.distribution.forEach(function(d) {
                    var val = parseInt(d.users) || 0;
                    var pct = (val / max) * 100;
                    html += '<div class="trend-row">'
                        + '<span class="trend-date" style="width:90px;">' + d.bracket + '</span>'
                        + '<div class="trend-bar-wrap"><div class="trend-bar-fill" style="width:' + pct + '%;"></div></div>'
                        + '<span class="trend-count">' + val + '</span>'
                        + '</div>';
                });
            }
            el.innerHTML = html;
        }

        function renderLanguages(langs) {
            var el = document.getElementById('langChart');
            if (!langs || !langs.length) { el.innerHTML = '<p style="color:#666;">No data yet.</p>'; return; }
            var max = parseInt(langs[0].users) || 1;
            var html = '';
            langs.slice(0, 12).forEach(function(l) {
                var val = parseInt(l.users) || 0;
                var pct = (val / max) * 100;
                html += '<div class="trend-row">'
                    + '<span class="trend-date">' + (l.native_lang || '??').toUpperCase() + '</span>'
                    + '<div class="trend-bar-wrap"><div class="trend-bar-fill" style="width:' + pct + '%;"></div></div>'
                    + '<span class="trend-count">' + val + '</span>'
                    + '</div>';
            });
            el.innerHTML = html;
        }

        function renderFeedback(fb) {
            var el = document.getElementById('feedbackStats');
            if (!fb) { el.innerHTML = '<p style="color:#666;">No data yet.</p>'; return; }
            var html = '<div class="stat-grid" style="margin-bottom:1rem;">'
                + '<div class="stat-card"><div class="stat-value">' + fmt(fb.total) + '</div><div class="stat-label">Total</div></div>'
                + '<div class="stat-card"><div class="stat-value">' + fmt(fb.unresolved) + '</div><div class="stat-label">Unresolved</div></div>'
                + '</div>';
            if (fb.byTypeAndStatus && fb.byTypeAndStatus.length) {
                html += '<table class="data-table"><thead><tr><th>Type</th><th>Status</th><th>Count</th></tr></thead><tbody>';
                fb.byTypeAndStatus.forEach(function(r) {
                    html += '<tr><td>' + r.feedback_type + '</td><td>' + r.status + '</td><td>' + r.count + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            el.innerHTML = html;
        }

        function fmt(n) {
            n = parseInt(n) || 0;
            if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
            if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
            return n.toString();
        }
    })();
    </script>
</body>
</html>