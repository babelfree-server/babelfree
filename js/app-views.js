/**
 * App Views — JS-driven overlay panels for in-app views
 * Profile management (edit, password, logout, delete, export)
 */
(function(window) {
    'use strict';

    function createOverlay(id, html) {
        var existing = document.getElementById(id);
        if (existing) existing.remove();
        var overlay = document.createElement('div');
        overlay.id = id;
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(10,10,26,0.97);overflow-y:auto;animation:viewFadeIn 0.25s ease';
        overlay.innerHTML = html;
        document.body.appendChild(overlay);
        return overlay;
    }

    function closeOverlay(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    }

    // Inject animation keyframe once
    if (!document.getElementById('appViewStyles')) {
        var s = document.createElement('style');
        s.id = 'appViewStyles';
        s.textContent = '@keyframes viewFadeIn{from{opacity:0}to{opacity:1}}';
        document.head.appendChild(s);
    }

    function getSession() {
        try { return JSON.parse(localStorage.getItem('jaguarUserSession')); } catch(e) { return null; }
    }

    window.showProfileView = function() {
        var session = getSession();
        if (!session) { window.location.href = 'login.html'; return; }
        var u = session.user || session;
        var name = u.display_name || session.displayName || 'Usuario';
        var initial = name.charAt(0).toUpperCase();
        var cefr = u.cefr_level || session.cefrLevel || 'A1';
        var memberSince = u.created_at ? new Date(u.created_at).toLocaleDateString('es') : '';

        // Build native language options
        var langs = (window.LOGIN_LANGUAGES || []).filter(function(l) { return l.code !== 'auto'; });
        var natOpts = langs.map(function(l) {
            var sel = (l.code === (u.native_lang || session.nativeLanguage || 'en')) ? ' selected' : '';
            return '<option value="' + l.code + '"' + sel + '>' + l.flag + ' ' + l.native + '</option>';
        }).join('');

        // Country options
        var countries = [['US','United States'],['BR','Brasil'],['GB','United Kingdom'],['CA','Canada'],['FR','France'],['DE','Deutschland'],['CO','Colombia'],['MX','México'],['ES','España'],['AR','Argentina'],['IT','Italia'],['AU','Australia'],['JP','日本'],['KR','대한민국'],['CN','中国'],['IN','भारत'],['NL','Nederland'],['PL','Polska'],['SE','Sverige'],['PT','Portugal'],['CL','Chile'],['PE','Perú'],['EC','Ecuador'],['VE','Venezuela'],['TR','Türkiye'],['RU','Россия'],['IE','Ireland'],['BE','België'],['CH','Schweiz'],['AT','Österreich']];
        var countryOpts = '<option value="">—</option>' + countries.map(function(c) {
            var sel = (c[0] === (u.country || '')) ? ' selected' : '';
            return '<option value="' + c[0] + '"' + sel + '>' + c[1] + '</option>';
        }).join('');

        var genderChecked = function(v) { return (u.gender || session.gender || 'X') === v ? ' checked' : ''; };

        var css = '<style>' +
            '.pv-wrap{max-width:720px;margin:0 auto;padding:24px 16px 60px;color:#e8e0d0;font-family:Inter,Lucida Sans,sans-serif}' +
            '.pv-nav{background:rgba(15,15,30,0.95);padding:12px 24px;display:flex;justify-content:space-between;border-bottom:1px solid rgba(201,162,39,0.2);position:sticky;top:0;z-index:10}' +
            '.pv-nav a{color:#c9a227;text-decoration:none;font-weight:600;font-size:0.9rem;cursor:pointer}' +
            '.pv-nav a:hover{color:#e8d48b}' +
            '.pv-header{text-align:center;margin-bottom:32px}' +
            '.pv-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#c9a227,#8B6914);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#0a0a1a;margin:0 auto 12px}' +
            '.pv-header h1{font-size:1.6rem;color:#c9a227;margin:0 0 4px}' +
            '.pv-meta{font-size:0.85rem;color:rgba(232,224,208,0.5)}' +
            '.pv-badge{display:inline-block;background:#c9a227;color:#0a0a1a;padding:2px 10px;border-radius:12px;font-weight:700;font-size:0.8rem;margin-left:6px}' +
            '.pv-card{background:rgba(255,255,255,0.04);border:1px solid rgba(201,162,39,0.15);border-radius:16px;padding:24px;margin-bottom:20px}' +
            '.pv-card h2{color:#c9a227;font-size:1.2rem;margin:0 0 16px;border-bottom:1px solid rgba(201,162,39,0.15);padding-bottom:8px}' +
            '.pv-field{margin-bottom:14px}' +
            '.pv-field label{display:block;font-size:0.78rem;color:rgba(232,224,208,0.5);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px}' +
            '.pv-field input,.pv-field select{width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid rgba(201,162,39,0.2);border-radius:10px;color:#e8e0d0;font-size:0.95rem;box-sizing:border-box}' +
            '.pv-field input:focus,.pv-field select:focus{outline:none;border-color:#c9a227;box-shadow:0 0 0 3px rgba(201,162,39,0.15)}' +
            '.pv-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}' +
            '.pv-readonly{background:rgba(255,255,255,0.02)!important;color:rgba(232,224,208,0.4)!important}' +
            '.pv-gender{display:flex;gap:12px}.pv-gender label{display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.9rem;color:#e8e0d0;text-transform:none;letter-spacing:0}' +
            '.pv-gender input{accent-color:#c9a227;width:auto}' +
            '.pv-info{display:grid;grid-template-columns:1fr 1fr;gap:10px}' +
            '.pv-info-item{padding:10px;background:rgba(255,255,255,0.03);border-radius:8px}' +
            '.pv-info-item .il{font-size:0.72rem;color:rgba(232,224,208,0.4);text-transform:uppercase;letter-spacing:0.5px}' +
            '.pv-info-item .iv{font-size:0.95rem;margin-top:2px}' +
            '.pv-btn{padding:10px 24px;border:none;border-radius:10px;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.2s}' +
            '.pv-btn-gold{background:linear-gradient(135deg,#c9a227,#8B6914);color:#0a0a1a}' +
            '.pv-btn-gold:hover{background:linear-gradient(135deg,#e8d48b,#c9a227)}' +
            '.pv-btn-outline{background:transparent;border:1px solid rgba(201,162,39,0.3);color:#c9a227}' +
            '.pv-btn-danger{background:transparent;border:1px solid rgba(200,50,50,0.3);color:#c85050}' +
            '.pv-btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}' +
            '.pv-toggle{display:flex;justify-content:space-between;align-items:center;padding:8px 0}' +
            '.pv-msg{padding:10px 14px;border-radius:8px;margin-bottom:12px;display:none;font-size:0.9rem}' +
            '.pv-msg-ok{background:rgba(90,110,74,0.2);border:1px solid rgba(90,110,74,0.4);color:#a8c896}' +
            '.pv-msg-err{background:rgba(200,50,50,0.15);border:1px solid rgba(200,50,50,0.3);color:#e88}' +
            '.pv-hint{font-size:0.75rem;color:rgba(232,224,208,0.4);margin-top:4px}' +
            '.pv-modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:10001;align-items:center;justify-content:center}' +
            '.pv-modal.active{display:flex}' +
            '.pv-modal-box{background:#1a1a2e;border:1px solid rgba(201,162,39,0.2);border-radius:16px;padding:28px;max-width:400px;width:90%;text-align:center}' +
            '.pv-modal-box h3{color:#c85050;margin:0 0 12px}' +
            '.pv-modal-box p{font-size:0.9rem;color:rgba(232,224,208,0.7);margin-bottom:16px}' +
            '@media(max-width:600px){.pv-row,.pv-info{grid-template-columns:1fr}}' +
            '</style>';

        var html = css +
            '<div class="pv-nav"><a onclick="closeOverlay(\'profileOverlay\')">← Volver al mapa</a><a onclick="window._pvLogout()">Cerrar sesión</a></div>' +
            '<div class="pv-wrap">' +
            '<div class="pv-header"><div class="pv-avatar">' + initial + '</div><h1>' + name + '</h1>' +
            '<div class="pv-meta">' + (u.email || session.email || '') + ' <span class="pv-badge">' + cefr + '</span></div>' +
            (memberSince ? '<div class="pv-meta" style="margin-top:4px">Miembro desde ' + memberSince + '</div>' : '') + '</div>' +

            // Personal info
            '<div class="pv-card"><h2>Información personal</h2><div id="pvMsg" class="pv-msg"></div>' +
            '<div class="pv-field"><label>Nombre</label><input type="text" id="pvName" maxlength="100" value="' + name.replace(/"/g, '&quot;') + '"></div>' +
            '<div class="pv-field"><label>Email</label><input type="email" class="pv-readonly" readonly value="' + (u.email || session.email || '') + '"></div>' +
            '<div class="pv-row"><div class="pv-field"><label>Fecha de nacimiento</label><input type="date" id="pvDob" value="' + (u.dob || '') + '"></div>' +
            '<div class="pv-field"><label>País</label><select id="pvCountry">' + countryOpts + '</select></div></div>' +
            '<div class="pv-row"><div class="pv-field"><label>Teléfono</label><input type="tel" id="pvPhone" value="' + (u.phone || '') + '" placeholder="+1 555 123 4567"></div>' +
            '<div class="pv-field"><label>Idioma nativo</label><select id="pvNat">' + natOpts + '</select></div></div>' +
            '<div class="pv-field"><label>Género</label><div class="pv-gender">' +
            '<label><input type="radio" name="pvGender" value="M"' + genderChecked('M') + '> Masculino</label>' +
            '<label><input type="radio" name="pvGender" value="F"' + genderChecked('F') + '> Femenino</label>' +
            '<label><input type="radio" name="pvGender" value="X"' + genderChecked('X') + '> Prefiero no decir</label></div></div>' +
            '<div class="pv-btns"><button class="pv-btn pv-btn-gold" onclick="window._pvSave()">Guardar cambios</button></div></div>' +

            // Account info
            '<div class="pv-card"><h2>Mi cuenta</h2><div class="pv-info">' +
            '<div class="pv-info-item"><div class="il">Nivel CEFR</div><div class="iv"><span class="pv-badge">' + cefr + '</span></div></div>' +
            '<div class="pv-info-item"><div class="il">Tipo</div><div class="iv">' + (u.user_type === 'classroom' ? 'Aula' : 'Individual') + '</div></div>' +
            '<div class="pv-info-item"><div class="il">Rol</div><div class="iv">' + (u.role === 'teacher' ? 'Profesor' : 'Estudiante') + '</div></div>' +
            '<div class="pv-info-item"><div class="il">Plan</div><div class="iv">' + (u.tier === 'premium' ? 'Premium' : 'Gratuito') + '</div></div>' +
            '<div class="pv-info-item"><div class="il">Email verificado</div><div class="iv">' + (u.email_verified ? 'Sí' : 'No') + '</div></div>' +
            '<div class="pv-info-item"><div class="il">Miembro desde</div><div class="iv">' + memberSince + '</div></div>' +
            '</div></div>' +

            // Preferences
            '<div class="pv-card"><h2>Preferencias</h2><div class="pv-toggle"><span>Recibir novedades</span>' +
            '<input type="checkbox" id="pvMarketing"' + (u.marketing_consent ? ' checked' : '') + ' style="accent-color:#c9a227;width:20px;height:20px"></div></div>' +

            // Change password
            '<div class="pv-card"><h2>Cambiar contraseña</h2><div id="pvPwMsg" class="pv-msg"></div>' +
            '<div class="pv-field"><label>Contraseña actual</label><input type="password" id="pvPwCur"></div>' +
            '<div class="pv-field"><label>Nueva contraseña</label><input type="password" id="pvPwNew"><div class="pv-hint">Mínimo 10 caracteres. Debe incluir mayúscula, minúscula y número.</div></div>' +
            '<div class="pv-field"><label>Confirmar</label><input type="password" id="pvPwConf"></div>' +
            '<div class="pv-btns"><button class="pv-btn pv-btn-gold" onclick="window._pvChangePw()">Cambiar contraseña</button></div></div>' +

            // Account actions
            '<div class="pv-card"><h2>Acciones</h2><div class="pv-btns">' +
            '<button class="pv-btn pv-btn-outline" onclick="window._pvExport()">Exportar datos</button>' +
            '<button class="pv-btn pv-btn-outline" onclick="window._pvLogout()">Cerrar sesión</button>' +
            '<button class="pv-btn pv-btn-danger" onclick="document.getElementById(\'pvDelModal\').classList.add(\'active\')">Eliminar cuenta</button>' +
            '</div></div></div>' +

            // Delete modal
            '<div class="pv-modal" id="pvDelModal"><div class="pv-modal-box"><h3>Eliminar cuenta</h3>' +
            '<p>Esta acción es permanente. Escribe tu contraseña para confirmar.</p>' +
            '<div class="pv-field" style="text-align:left"><input type="password" id="pvDelPw" placeholder="Tu contraseña"></div>' +
            '<div class="pv-btns" style="justify-content:center">' +
            '<button class="pv-btn pv-btn-outline" onclick="document.getElementById(\'pvDelModal\').classList.remove(\'active\')">Cancelar</button>' +
            '<button class="pv-btn pv-btn-danger" onclick="window._pvDelete()">Eliminar</button>' +
            '</div></div></div>';

        createOverlay('profileOverlay', html);
    };

    function pvMsg(id, text, ok) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        el.className = 'pv-msg ' + (ok ? 'pv-msg-ok' : 'pv-msg-err');
        el.style.display = 'block';
        setTimeout(function() { el.style.display = 'none'; }, 5000);
    }

    window._pvSave = async function() {
        var gEl = document.querySelector('input[name="pvGender"]:checked');
        var data = {
            display_name: document.getElementById('pvName').value.trim(),
            gender: gEl ? gEl.value : 'X',
            native_lang: document.getElementById('pvNat').value,
            dob: document.getElementById('pvDob').value || null,
            country: document.getElementById('pvCountry').value || null,
            phone: document.getElementById('pvPhone').value || null,
            marketing_consent: document.getElementById('pvMarketing').checked
        };
        try {
            var res = await JaguarAPI.updateProfile(data);
            if (res.success) {
                var session = getSession();
                session.displayName = data.display_name;
                session.user = Object.assign(session.user || {}, res.data);
                localStorage.setItem('jaguarUserSession', JSON.stringify(session));
                pvMsg('pvMsg', 'Perfil actualizado', true);
            } else { pvMsg('pvMsg', res.error || 'Error', false); }
        } catch(e) { pvMsg('pvMsg', 'Error de conexión', false); }
    };

    window._pvChangePw = async function() {
        var cur = document.getElementById('pvPwCur').value;
        var nw = document.getElementById('pvPwNew').value;
        var conf = document.getElementById('pvPwConf').value;
        if (!cur || !nw || !conf) { pvMsg('pvPwMsg', 'Completa todos los campos', false); return; }
        if (nw.length < 10 || !/[a-z]/.test(nw) || !/[A-Z]/.test(nw) || !/[0-9]/.test(nw)) {
            pvMsg('pvPwMsg', 'Mínimo 10 caracteres, con mayúscula, minúscula y número', false); return;
        }
        if (nw !== conf) { pvMsg('pvPwMsg', 'Las contraseñas no coinciden', false); return; }
        try {
            var res = await JaguarAPI.changePassword(cur, nw);
            if (res.success) {
                pvMsg('pvPwMsg', 'Contraseña actualizada', true);
                document.getElementById('pvPwCur').value = '';
                document.getElementById('pvPwNew').value = '';
                document.getElementById('pvPwConf').value = '';
            } else { pvMsg('pvPwMsg', res.error || 'Error', false); }
        } catch(e) { pvMsg('pvPwMsg', 'Error de conexión', false); }
    };

    window._pvLogout = function() {
        JaguarAPI.logout().catch(function() {});
        localStorage.removeItem('jaguarUserSession');
        window.location.href = 'login.html';
    };

    window._pvExport = async function() {
        try {
            var res = await JaguarAPI.exportData();
            if (res.success) {
                var blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'babelfree-data-export.json';
                a.click();
            }
        } catch(e) { alert('Error al exportar'); }
    };

    window._pvDelete = async function() {
        var pw = document.getElementById('pvDelPw').value;
        if (!pw) return;
        try {
            var res = await JaguarAPI.deleteAccount(pw);
            if (res.success) {
                localStorage.removeItem('jaguarUserSession');
                alert('Cuenta eliminada.');
                window.location.href = 'login.html';
            } else { alert(res.error || 'Error'); }
        } catch(e) { alert('Error de conexión'); }
    };

})(window);
