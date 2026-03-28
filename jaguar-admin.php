<?php
// Server-side admin gate — page never renders for non-admins
require_once __DIR__ . '/api/config/database.php';

$token = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
} elseif (isset($_COOKIE['jaguarToken'])) {
    $token = $_COOKIE['jaguarToken'];
}

// Also check the session from localStorage via a query param or cookie
// But since this is a page load (not API), we rely on JS to pass the token
// The real gate: if someone guesses the URL, they see a login prompt, not the data
// All DATA is protected by the API (admin role check on /api/admin/*)
// This PHP gate adds an extra password layer for the page itself

$adminPassword = 'yaguara2026';  // Simple page-access password
$inputPassword = $_POST['admin_pass'] ?? $_COOKIE['jaguar_admin_gate'] ?? null;

if ($inputPassword !== $adminPassword) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
        $error = 'Contraseña incorrecta';
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin — Acceso restringido</title>
        <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter',sans-serif; background:#0d0d0d; color:#e8d5b7; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .gate { background:rgba(26,18,8,0.5); border:1px solid rgba(201,162,39,0.15); border-radius:16px; padding:40px; max-width:360px; width:100%; text-align:center; }
        .gate h2 { color:#c9a227; font-size:18px; margin-bottom:8px; }
        .gate p { font-size:13px; color:#b8a48a; margin-bottom:24px; }
        .gate input { width:100%; padding:12px 16px; background:rgba(0,0,0,0.3); border:1px solid rgba(201,162,39,0.2); border-radius:8px; color:#e8d5b7; font-size:14px; margin-bottom:12px; }
        .gate input:focus { outline:none; border-color:#c9a227; }
        .gate button { width:100%; padding:12px; background:rgba(201,162,39,0.15); border:1px solid rgba(201,162,39,0.3); border-radius:8px; color:#c9a227; font-size:14px; font-weight:600; cursor:pointer; }
        .gate button:hover { background:rgba(201,162,39,0.25); }
        .error { color:#ef5350; font-size:13px; margin-bottom:12px; }
        </style>
    </head>
    <body>
        <div class="gate">
            <h2>Panel de administración</h2>
            <p>Ingresa la contraseña de administrador</p>
            <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="password" name="admin_pass" placeholder="Contraseña" autofocus>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set cookie so they don't need to re-enter on refresh (24h)
setcookie('jaguar_admin_gate', $adminPassword, time() + 86400, '/elviajedeljaguar/admin', '', true, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — El Viaje del Jaguar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #0d0d0d; color: #e8d5b7; min-height: 100vh; }
    .admin-header { background: rgba(20,14,6,0.95); padding: 16px 24px; display: flex; align-items: center; gap: 16px; border-bottom: 1px solid rgba(201,162,39,0.15); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(12px); }
    .admin-header h1 { font-size: 18px; color: #c9a227; font-weight: 600; }
    .admin-header .back { color: #c9a227; text-decoration: none; font-size: 14px; opacity: 0.7; }
    .admin-body { max-width: 1200px; margin: 0 auto; padding: 32px 24px; }

    /* Stats bar */
    .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .stat-card { background: rgba(26,18,8,0.5); border: 1px solid rgba(201,162,39,0.1); border-radius: 12px; padding: 20px; text-align: center; }
    .stat-num { font-size: 32px; font-weight: 700; color: #c9a227; }
    .stat-label { font-size: 12px; color: #b8a48a; margin-top: 4px; }

    /* Table */
    .table-wrap { background: rgba(26,18,8,0.3); border: 1px solid rgba(201,162,39,0.1); border-radius: 12px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { background: rgba(201,162,39,0.08); color: #c9a227; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; text-align: left; }
    td { padding: 14px 16px; border-top: 1px solid rgba(201,162,39,0.06); font-size: 14px; }
    tr:hover td { background: rgba(201,162,39,0.04); }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 100px; font-size: 11px; font-weight: 600; }
    .badge-admin { background: rgba(156,39,176,0.2); color: #CE93D8; }
    .badge-student { background: rgba(76,175,80,0.2); color: #81C784; }
    .progress-bar { width: 80px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; display: inline-block; vertical-align: middle; margin-right: 6px; }
    .progress-fill { height: 100%; background: #c9a227; border-radius: 3px; }
    .detail-link { color: #c9a227; text-decoration: none; font-size: 13px; }
    .detail-link:hover { text-decoration: underline; }

    /* Detail panel */
    .detail-panel { display: none; background: rgba(26,18,8,0.5); border: 1px solid rgba(201,162,39,0.15); border-radius: 12px; padding: 24px; margin-top: 24px; }
    .detail-panel.active { display: block; }
    .detail-panel h3 { color: #c9a227; font-size: 16px; margin-bottom: 16px; }
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .detail-item { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 14px; }
    .detail-item label { font-size: 11px; color: #b8a48a; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px; }
    .detail-item .value { font-size: 15px; color: #e8d5b7; }
    .cronica-text { background: rgba(76,175,80,0.06); border-left: 3px solid rgba(76,175,80,0.3); padding: 12px; border-radius: 0 8px 8px 0; margin: 8px 0; font-size: 13px; color: #c8d5b7; max-height: 200px; overflow-y: auto; }
    .dest-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .dest-chip { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; }
    .dest-chip.done { background: rgba(76,175,80,0.2); color: #81C784; }
    .dest-chip.pending { background: rgba(255,255,255,0.05); color: #666; }
    .loading { text-align: center; padding: 40px; color: #666; }
    @media (max-width: 640px) { .stats-bar { grid-template-columns: repeat(2, 1fr); } td, th { padding: 10px 12px; font-size: 13px; } }
    </style>
</head>
<body>
    <header class="admin-header">
        <a href="/elviajedeljaguar" class="back">&larr; Volver</a>
        <h1>Panel de administración</h1>
    </header>

    <div class="admin-body">
        <div class="stats-bar" id="statsBar">
            <div class="stat-card"><div class="stat-num" id="statTotal">-</div><div class="stat-label">Estudiantes</div></div>
            <div class="stat-card"><div class="stat-num" id="statActive">-</div><div class="stat-label">Activos (7d)</div></div>
            <div class="stat-card"><div class="stat-num" id="statDests">-</div><div class="stat-label">Destinos completados</div></div>
            <div class="stat-card"><div class="stat-num" id="statWords">-</div><div class="stat-label">Palabras escritas</div></div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Destinos</th>
                        <th>Adivinanzas</th>
                        <th>Palabras</th>
                        <th>Vocabulario</th>
                        <th>Registro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="studentTable">
                    <tr><td colspan="9" class="loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="detail-panel" id="detailPanel"></div>
    </div>

<script src="js/jaguar-api.js"></script>
<script>
(function() {
    if (!window.JaguarAPI || !JaguarAPI.isAuthenticated()) {
        window.location.href = '/elviajedeljaguar/login';
        return;
    }

    var API = '/api';
    function getToken() {
        try { var s = JSON.parse(localStorage.getItem('jaguarUserSession')); return s ? s.serverToken : null; } catch(e) { return null; }
    }

    function fetchAPI(path) {
        return fetch(API + path, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        }).then(function(r) { return r.json(); });
    }

    function formatDate(d) {
        if (!d) return '-';
        var dt = new Date(d);
        return dt.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function isActiveRecent(d) {
        if (!d) return false;
        var diff = Date.now() - new Date(d).getTime();
        return diff < 7 * 24 * 60 * 60 * 1000;
    }

    // Load students
    fetchAPI('/admin/students').then(function(res) {
        if (!res.success) { document.getElementById('studentTable').innerHTML = '<tr><td colspan="9">Error: ' + (res.error || 'Acceso denegado') + '</td></tr>'; return; }
        var students = res.data.students;

        // Stats
        document.getElementById('statTotal').textContent = students.length;
        var active = students.filter(function(s) { return isActiveRecent(s.last_active); }).length;
        document.getElementById('statActive').textContent = active;
        var totalDests = students.reduce(function(sum, s) { return sum + parseInt(s.dests_completed || 0); }, 0);
        document.getElementById('statDests').textContent = totalDests;
        var totalWords = students.reduce(function(sum, s) { return sum + parseInt(s.words_written || 0); }, 0);
        document.getElementById('statWords').textContent = totalWords;

        // Table
        var html = '';
        students.forEach(function(s) {
            var riddlePct = Math.round((parseFloat(s.riddle_progress || 0)) * 100);
            var destPct = Math.round((parseInt(s.dests_completed || 0) / 89) * 100);
            html += '<tr>';
            html += '<td><strong>' + (s.display_name || '-') + '</strong></td>';
            html += '<td>' + (s.email || '-') + '</td>';
            html += '<td><span class="badge badge-' + (s.role || 'student') + '">' + (s.role || 'student') + '</span></td>';
            html += '<td><div class="progress-bar"><div class="progress-fill" style="width:' + destPct + '%"></div></div>' + (s.dests_completed || 0) + '/89</td>';
            html += '<td><div class="progress-bar"><div class="progress-fill" style="width:' + riddlePct + '%"></div></div>' + riddlePct + '%</td>';
            html += '<td>' + (s.words_written || 0) + '</td>';
            html += '<td>' + (s.vocab_size || 0) + '</td>';
            html += '<td>' + formatDate(s.created_at) + '</td>';
            html += '<td><a href="#" class="detail-link" data-id="' + s.id + '">Ver</a></td>';
            html += '</tr>';
        });
        document.getElementById('studentTable').innerHTML = html;

        // Detail click handlers
        document.querySelectorAll('.detail-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadStudentDetail(this.getAttribute('data-id'));
            });
        });
    });

    function loadStudentDetail(id) {
        var panel = document.getElementById('detailPanel');
        panel.className = 'detail-panel active';
        panel.innerHTML = '<div class="loading">Cargando detalle...</div>';

        fetchAPI('/admin/student?id=' + id).then(function(res) {
            if (!res.success) { panel.innerHTML = '<p>Error</p>'; return; }
            var d = res.data;
            var s = d.student;
            var adv = d.adventure || {};
            var bus = d.busqueda || {};
            var lex = d.lexicon || {};

            var html = '<h3>' + s.display_name + ' &mdash; ' + s.email + '</h3>';

            // Summary grid
            html += '<div class="detail-grid">';
            html += '<div class="detail-item"><label>Destinos completados</label><div class="value">' + (d.destinations ? d.destinations.length : 0) + '/89</div></div>';
            html += '<div class="detail-item"><label>Adivinanzas resueltas</label><div class="value">' + ((bus.solved_riddles || []).length) + '/89</div></div>';
            html += '<div class="detail-item"><label>Palabras escritas</label><div class="value">' + (adv.total_words_written || 0) + '</div></div>';
            html += '<div class="detail-item"><label>Vocabulario</label><div class="value">' + (lex.word_count || 0) + ' palabras</div></div>';
            html += '<div class="detail-item"><label>Nombre de la rana</label><div class="value">' + (bus.rana_name || 'Sin nombrar') + '</div></div>';
            html += '<div class="detail-item"><label>Composición completa</label><div class="value">' + (adv.composition_revealed ? 'Sí' : 'No') + '</div></div>';
            html += '</div>';

            // Destination chips
            html += '<h3>Progreso por destino</h3>';
            html += '<div class="dest-chips">';
            var completedDests = {};
            (d.destinations || []).forEach(function(dp) { completedDests[dp.dest_num] = dp; });
            for (var i = 1; i <= 89; i++) {
                var done = completedDests[i];
                html += '<span class="dest-chip ' + (done ? 'done' : 'pending') + '">' + i + '</span>';
            }
            html += '</div>';

            // Crónicas (from adventure chapters)
            var chapters = adv.chapters || {};
            var chapterKeys = Object.keys(chapters).filter(function(k) { return chapters[k].cronica; });
            if (chapterKeys.length > 0) {
                html += '<h3 style="margin-top:20px">Crónicas escritas (' + chapterKeys.length + ')</h3>';
                chapterKeys.forEach(function(k) {
                    var ch = chapters[k];
                    html += '<div class="cronica-text"><strong>' + k + ':</strong> ' + ch.cronica + '</div>';
                });
            }

            panel.innerHTML = html;
            panel.scrollIntoView({ behavior: 'smooth' });
        });
    }
})();
</script>
</body>
</html>
