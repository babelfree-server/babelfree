<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $email, string $displayName, string $token): bool {
    $config = require __DIR__ . '/../config/app.php';
    $link = $config['base_url'] . '/login.html?verify=' . urlencode($token);

    $subject = 'Verifica tu cuenta — El Viaje del Jaguar';
    $html = getVerificationTemplate($displayName, $link);

    return sendMail($email, $subject, $html, $config['mail']);
}

function sendPasswordResetEmail(string $email, string $displayName, string $token): bool {
    $config = require __DIR__ . '/../config/app.php';
    $link = $config['base_url'] . '/login.html?reset=' . urlencode($token);

    $subject = 'Restablecer contraseña — El Viaje del Jaguar';
    $html = getResetTemplate($displayName, $link);

    return sendMail($email, $subject, $html, $config['mail']);
}

function sendMail(string $to, string $subject, string $html, array $mailConfig): bool {
    try {
        $mail = new PHPMailer(true);
        $mail->isSendmail();
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $html));
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer error: " . $e->getMessage());
        return false;
    }
}

function getVerificationTemplate(string $name, string $link): string {
    $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $escapedLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,'Times New Roman',serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #c9a84c33;border-radius:8px;">
    <tr><td style="padding:40px 40px 20px;text-align:center;">
        <h1 style="color:#c9a84c;font-size:24px;margin:0;">🐆 El Viaje del Jaguar</h1>
    </td></tr>
    <tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:16px;line-height:1.6;">
        <p>¡Hola {$escapedName}!</p>
        <p>Bienvenido/a a El Viaje del Jaguar. Para activar tu cuenta, haz clic en el siguiente enlace:</p>
    </td></tr>
    <tr><td style="padding:0 40px 30px;text-align:center;">
        <a href="{$escapedLink}" style="display:inline-block;background:linear-gradient(135deg,#c9a84c,#8B6914);color:#0a0a0a;text-decoration:none;padding:14px 32px;border-radius:6px;font-weight:bold;font-size:16px;">Verificar mi cuenta</a>
    </td></tr>
    <tr><td style="padding:0 40px 30px;color:#999;font-size:13px;line-height:1.5;">
        <p>Este enlace expira en 24 horas. Si no creaste esta cuenta, ignora este correo.</p>
        <p style="word-break:break-all;color:#666;font-size:11px;">{$escapedLink}</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
}

function getResetTemplate(string $name, string $link): string {
    $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $escapedLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,'Times New Roman',serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #c9a84c33;border-radius:8px;">
    <tr><td style="padding:40px 40px 20px;text-align:center;">
        <h1 style="color:#c9a84c;font-size:24px;margin:0;">🐆 El Viaje del Jaguar</h1>
    </td></tr>
    <tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:16px;line-height:1.6;">
        <p>Hola {$escapedName},</p>
        <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace:</p>
    </td></tr>
    <tr><td style="padding:0 40px 30px;text-align:center;">
        <a href="{$escapedLink}" style="display:inline-block;background:linear-gradient(135deg,#c9a84c,#8B6914);color:#0a0a0a;text-decoration:none;padding:14px 32px;border-radius:6px;font-weight:bold;font-size:16px;">Restablecer contraseña</a>
    </td></tr>
    <tr><td style="padding:0 40px 30px;color:#999;font-size:13px;line-height:1.5;">
        <p>Este enlace expira en 1 hora. Si no solicitaste este cambio, ignora este correo — tu contraseña no se modificará.</p>
        <p style="word-break:break-all;color:#666;font-size:11px;">{$escapedLink}</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
}
