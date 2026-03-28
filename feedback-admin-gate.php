<?php
$adminPassword = 'yaguara2026';
$input = $_POST['admin_pass'] ?? $_COOKIE['jaguar_admin_gate'] ?? null;
if ($input === $adminPassword) {
    setcookie('jaguar_admin_gate', $adminPassword, time() + 86400, '/', '', true, true);
    readfile(__DIR__ . '/feedback-admin.html');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') $error = 'Contraseña incorrecta';
?><!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso restringido</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:sans-serif;background:#0d0d0d;color:#e8d5b7;min-height:100vh;display:flex;align-items:center;justify-content:center}.g{background:rgba(26,18,8,.5);border:1px solid rgba(201,162,39,.15);border-radius:16px;padding:40px;max-width:360px;width:100%;text-align:center}h2{color:#c9a227;font-size:18px;margin-bottom:20px}input{width:100%;padding:12px;background:rgba(0,0,0,.3);border:1px solid rgba(201,162,39,.2);border-radius:8px;color:#e8d5b7;font-size:14px;margin-bottom:12px}button{width:100%;padding:12px;background:rgba(201,162,39,.15);border:1px solid rgba(201,162,39,.3);border-radius:8px;color:#c9a227;font-size:14px;font-weight:600;cursor:pointer}.e{color:#ef5350;font-size:13px;margin-bottom:12px}</style></head><body><div class="g"><h2>Acceso restringido</h2><?php if(!empty($error)):?><div class="e"><?=htmlspecialchars($error)?></div><?php endif;?><form method="POST"><input type="password" name="admin_pass" placeholder="Contraseña" autofocus><button>Entrar</button></form></div></body></html>
