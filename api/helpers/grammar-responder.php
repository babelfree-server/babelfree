<?php
/**
 * Grammar Question Auto-Responder
 *
 * Tries the local knowledge base first, falls back to LanguageTool (free, no key),
 * then formats and emails the response to the student.
 */

require_once __DIR__ . '/../data/grammar-kb.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mailer.php';

/**
 * Handle a grammar question: match or generate an answer, email it.
 *
 * @param string      $question    The student's question text
 * @param string      $userEmail   Student's email address
 * @param string      $userName    Student's display name
 * @param string      $userCefr    CEFR level (e.g. 'A1', 'B2')
 * @param string      $nativeLang  ISO code (e.g. 'en', 'fr')
 * @param string|null $destination Current destination (e.g. 'dest5')
 * @param string|null $gameType    Current game type if applicable
 * @return array      ['success' => bool, 'source' => 'kb'|'languagetool'|'fallback', 'error' => string|null]
 */
function handleGrammarQuestion(
    string $question,
    string $userEmail,
    string $userName,
    string $userCefr = 'A1',
    string $nativeLang = 'en',
    ?string $destination = null,
    ?string $gameType = null
): array {

    // Immersion rule: A1 through A2 Basic → native language. A2 Advanced+ → 100% Spanish.
    $immersionLevels = ['A2 Advanced', 'B1', 'B2', 'C1', 'C2'];
    $useSpanish = in_array($userCefr, $immersionLevels)
        || preg_match('/^(B[12]|C[12])\b/', $userCefr);  // catches "B1 básico", etc.
    $responseLang = $useSpanish ? 'es' : $nativeLang;

    // 1. Try local knowledge base
    $kbResult = findGrammarMatch($question, $userCefr);

    if ($kbResult && ($kbResult['match_score'] ?? 0) >= 3) {
        // Get the explanation text
        $explanation = $kbResult['matched_explanation']
            ?? (is_array($kbResult['explanation']) ? reset($kbResult['explanation']) : ($kbResult['explanation'] ?? ''));

        // Get mnemonic/tip
        $tip = '';
        if (!empty($kbResult['mnemonics'])) {
            $mn = $kbResult['mnemonics'];
            $tip = is_array($mn) ? ($mn[$responseLang] ?? $mn['en'] ?? reset($mn) ?: '') : $mn;
        }

        // If immersion mode (Spanish), try to use Spanish KB content
        // KB explanations are in English — for A2 Advanced+ students, prefer Spanish title
        if ($useSpanish) {
            $spanishExplanation = translateToSpanish($explanation, $tip, $kbResult['examples'] ?? [], $kbResult['common_errors'] ?? [], $userCefr);
            if ($spanishExplanation) {
                $kbResult['title'] = $kbResult['title_es'] ?? 'Gramática';
                $kbResult['explanation'] = $spanishExplanation['explanation'];
                $kbResult['tip'] = $spanishExplanation['tip'] ?? '';
                $kbResult['examples'] = $spanishExplanation['examples'] ?? $kbResult['examples'] ?? [];
                $kbResult['common_errors'] = $spanishExplanation['common_errors'] ?? $kbResult['common_errors'] ?? [];
                $html = buildGrammarEmail($userName, $question, $kbResult, 'kb', 'es');
                $sent = sendGrammarEmail($userEmail, $html);
                return [
                    'success' => $sent, 'source' => 'kb+translated', 'error' => $sent ? null : 'Email send failed',
                    'kb_topic' => $kbResult['topic'] ?? null, 'fundeu_url' => $kbResult['fundeu_url'] ?? null,
                    'dpd_url' => $kbResult['dpd_url'] ?? null, 'matched_explanation' => $explanation,
                ];
            }
            // If translation fails, fall through with English explanation
        }

        // For non-English native speakers at A1-A2 Basic, attempt native language translation
        if (!$useSpanish && $nativeLang !== 'en' && $nativeLang !== 'es') {
            $translatedExplanation = translateToNativeLang($explanation, $tip, $nativeLang, $userCefr);
            if ($translatedExplanation) {
                $explanation = $translatedExplanation;
            }
        }

        $kbResult['title'] = $useSpanish ? ($kbResult['title_es'] ?? 'Gramática') : ($kbResult['title_en'] ?? $kbResult['title_es'] ?? 'Grammar');
        $kbResult['explanation'] = $explanation;
        $kbResult['tip'] = $tip;
        $html = buildGrammarEmail($userName, $question, $kbResult, 'kb', $responseLang);
        $sent = sendGrammarEmail($userEmail, $html);
        return [
            'success' => $sent, 'source' => 'kb', 'error' => $sent ? null : 'Email send failed',
            'kb_topic' => $kbResult['topic'] ?? null, 'fundeu_url' => $kbResult['fundeu_url'] ?? null,
            'dpd_url' => $kbResult['dpd_url'] ?? null, 'matched_explanation' => $explanation,
        ];
    }

    // 2. Fall back to LanguageTool (free, no API key, no registration)
    $ltResult = callLanguageTool($question);

    if ($ltResult && !empty($ltResult['matches'])) {
        $ltExplanation = formatLanguageToolResponse($ltResult['matches'], $useSpanish);
        $responseData = [
            'title'         => $useSpanish ? 'Análisis de tu texto' : 'Analysis of your text',
            'explanation'   => $ltExplanation['explanation'],
            'examples'      => $ltExplanation['examples'],
            'common_errors' => $ltExplanation['errors'],
            'tip'           => $ltExplanation['tip'],
            'source'        => 'languagetool',
        ];
        $html = buildGrammarEmail($userName, $question, $responseData, 'languagetool', $responseLang);
        $sent = sendGrammarEmail($userEmail, $html);
        return ['success' => $sent, 'source' => 'languagetool', 'error' => $sent ? null : 'Email send failed'];
    }

    // 3. Generic fallback — notify student AND admin
    $fallbackData = [
        'title'       => $useSpanish ? 'Recibimos tu pregunta' : 'We received your question',
        'explanation' => $useSpanish
            ? 'Hemos recibido tu pregunta de gramática. Nuestro equipo la revisará y te responderá pronto. Mientras tanto, sigue practicando en el juego.'
            : 'We received your grammar question. Our team will review it and respond soon. In the meantime, keep practicing in the game!',
        'examples'    => [],
        'common_errors' => [],
        'tip'         => $useSpanish
            ? 'Puedes seguir enviando preguntas desde el widget de comentarios en cualquier momento.'
            : 'You can keep sending questions from the feedback widget at any time.',
        'source'      => 'fallback',
    ];
    $html = buildGrammarEmail($userName, $question, $fallbackData, 'fallback', $responseLang);
    $sent = sendGrammarEmail($userEmail, $html);

    // Notify admin about unanswered question
    notifyAdminUnansweredQuestion($question, $userEmail, $userName, $userCefr, $destination, $gameType);

    return [
        'success' => $sent,
        'source'  => 'fallback',
        'error'   => $sent ? null : 'Email send failed',
    ];
}

/**
 * Call LanguageTool API (free, no key, no registration).
 * Analyzes Spanish text for grammar/spelling errors and returns matches.
 *
 * @param string $text  The student's text to check
 * @return array|null   LanguageTool response with 'matches' array, or null on failure
 */
function callLanguageTool(string $text): ?array {
    $url = 'https://api.languagetool.org/v2/check';
    $postData = http_build_query([
        'text'     => $text,
        'language' => 'es',
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT      => 'ElViajeDelJaguar/1.0 (babelfree.com)',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("LanguageTool cURL error: {$curlError}");
        return null;
    }
    if ($httpCode !== 200) {
        error_log("LanguageTool HTTP {$httpCode}: " . mb_substr($response, 0, 300));
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        error_log('LanguageTool: invalid JSON response');
        return null;
    }

    return $data;
}

/**
 * Format LanguageTool matches into structured response data.
 *
 * @param array $matches   LanguageTool matches array
 * @param bool  $useSpanish  Whether to format labels in Spanish (immersion mode)
 * @return array  With keys: explanation, examples, errors, tip
 */
function formatLanguageToolResponse(array $matches, bool $useSpanish): array {
    $explanation = '';
    $errors = [];
    $examples = [];

    foreach (array_slice($matches, 0, 5) as $i => $match) {
        $message    = $match['message'] ?? '';
        $context    = $match['context']['text'] ?? '';
        $offset     = $match['context']['offset'] ?? 0;
        $length     = $match['context']['length'] ?? 0;
        $badText    = mb_substr($context, $offset, $length);
        $rule       = $match['rule']['description'] ?? '';
        $category   = $match['rule']['category']['name'] ?? '';
        $replacements = array_column(array_slice($match['replacements'] ?? [], 0, 3), 'value');

        // Build the error entry
        if ($badText && $replacements) {
            $errors[] = [
                'wrong' => $badText,
                'right' => implode(' / ', $replacements),
                'why'   => $message,
            ];
        }

        // Build explanation paragraph
        $num = $i + 1;
        $explanation .= "{$num}. {$message}";
        if ($rule) {
            $explanation .= " [{$category}]";
        }
        $explanation .= "\n";

        // Show context as example
        if ($context) {
            $examples[] = $context;
        }
    }

    $totalIssues = count($matches);
    $shown = min(5, $totalIssues);

    if ($useSpanish) {
        $intro = "Se encontraron {$totalIssues} observaciones en tu texto.";
        if ($totalIssues > 5) $intro .= " Se muestran las {$shown} más importantes.";
        $tip = 'LanguageTool revisa tu ortografía y gramática. Corrige los errores señalados y vuelve a enviar tu texto para verificar las mejoras.';
    } else {
        $intro = "{$totalIssues} issues found in your text.";
        if ($totalIssues > 5) $intro .= " Showing the top {$shown}.";
        $tip = 'LanguageTool checks your spelling and grammar. Fix the highlighted errors and re-submit your text to verify improvements.';
    }

    return [
        'explanation' => $intro . "\n\n" . $explanation,
        'examples'    => $examples,
        'errors'      => $errors,
        'tip'         => $tip,
    ];
}

/**
 * Translate KB explanation to Spanish for immersion-level students (A2 Advanced+).
 * Currently returns null — translations require an external API.
 * The KB explanation in English is used as-is when translation is unavailable.
 */
function translateToSpanish(string $explanation, string $tip, array $examples, array $errors, string $cefr): ?array {
    // Without an external translation API, we cannot auto-translate.
    // The KB already has Spanish titles (title_es) and Spanish examples.
    return null;
}

/**
 * Translate KB explanation to student's native language (for non-English A1-A2 Basic students).
 * Currently returns null — translations require an external API.
 */
function translateToNativeLang(string $explanation, string $tip, string $nativeLang, string $cefr): ?string {
    return null;
}

/**
 * Map language codes to human-readable names.
 */
function getNativeLangName(string $code): string {
    $map = [
        'es' => 'Spanish', 'en' => 'English', 'fr' => 'French', 'de' => 'German',
        'pt' => 'Portuguese', 'it' => 'Italian', 'zh' => 'Chinese', 'ja' => 'Japanese',
        'ko' => 'Korean', 'ru' => 'Russian', 'ar' => 'Arabic', 'nl' => 'Dutch',
    ];
    return $map[$code] ?? 'English';
}

/**
 * Send the grammar response email.
 */
function sendGrammarEmail(string $to, string $html): bool {
    $config = require __DIR__ . '/../config/app.php';
    $subject = 'Tu pregunta de gramática — El Viaje del Jaguar';
    return sendMail($to, $subject, $html, $config['mail']);
}

/**
 * Build a styled grammar response email matching the existing dark/gold theme.
 *
 * @param string $name         Student's display name
 * @param string $question     The original question
 * @param array  $data         Response data (title, explanation, examples, common_errors, tip, source)
 * @param string $source       'kb', 'languagetool', or 'fallback'
 * @return string              Full HTML email
 */
function buildGrammarEmail(string $name, string $question, array $data, string $source, string $lang = 'en'): string {
    $eName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $eQuestion = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
    $eTitle = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');

    // Localized UI labels based on response language
    $isSpanish = ($lang === 'es');
    $labels = [
        'greeting'     => $isSpanish ? "Hola {$eName}," : "Hi {$eName},",
        'your_question'=> $isSpanish ? 'Tu pregunta:' : 'Your question:',
        'examples'     => $isSpanish ? 'Ejemplos' : 'Examples',
        'errors'       => $isSpanish ? 'Errores comunes' : 'Common mistakes',
        'tip'          => $isSpanish ? 'Consejo' : 'Tip',
        'footer'       => $isSpanish
            ? '¿Necesitas más ayuda? Sigue preguntando desde el widget en el juego.'
            : 'Need more help? Keep asking from the feedback widget in the game.',
    ];

    // Explanation — some sources may contain markdown-style formatting, convert basic patterns
    $explanation = $data['explanation'] ?? '';
    if ($source === 'languagetool' || $source === 'kb+translated') {
        $explanation = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $explanation);
        $explanation = nl2br(htmlspecialchars($explanation, ENT_QUOTES, 'UTF-8'));
        $explanation = preg_replace('/&lt;strong&gt;(.+?)&lt;\/strong&gt;/', '<strong>$1</strong>', $explanation);
    } else {
        $explanation = nl2br(htmlspecialchars($explanation, ENT_QUOTES, 'UTF-8'));
    }

    // Build examples table
    $examplesHtml = '';
    if (!empty($data['examples'])) {
        $examplesHtml = '<tr><td style="padding:20px 40px 10px;color:#c9a84c;font-size:14px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;">' . $labels['examples'] . '</td></tr>' .
            '<tr><td style="padding:0 40px 20px;">' .
            '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
        foreach ($data['examples'] as $ex) {
            if (is_array($ex)) {
                // KB format: {es: "...", en: "..."}
                $eEs = htmlspecialchars($ex['es'] ?? '', ENT_QUOTES, 'UTF-8');
                $eEn = htmlspecialchars($ex['en'] ?? '', ENT_QUOTES, 'UTF-8');
                $examplesHtml .= '<tr>' .
                    '<td style="padding:8px 12px;color:#e8e0d0;font-size:15px;border-bottom:1px solid #333;font-style:italic;">' . $eEs . '</td>' .
                    '<td style="padding:8px 12px;color:#999;font-size:14px;border-bottom:1px solid #333;">' . $eEn . '</td>' .
                    '</tr>';
            } else {
                // LanguageTool format: plain context string
                $eCtx = htmlspecialchars((string)$ex, ENT_QUOTES, 'UTF-8');
                $examplesHtml .= '<tr>' .
                    '<td colspan="2" style="padding:8px 12px;color:#e8e0d0;font-size:14px;border-bottom:1px solid #333;font-family:monospace;">' . $eCtx . '</td>' .
                    '</tr>';
            }
        }
        $examplesHtml .= '</table></td></tr>';
    }

    // Build common errors
    $errorsHtml = '';
    if (!empty($data['common_errors'])) {
        $errorsHtml = '<tr><td style="padding:20px 40px 10px;color:#c9a84c;font-size:14px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;">' . $labels['errors'] . '</td></tr>' .
            '<tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:14px;line-height:1.6;">';
        foreach ($data['common_errors'] as $err) {
            if (is_array($err)) {
                $wrong = htmlspecialchars($err['wrong'] ?? '', ENT_QUOTES, 'UTF-8');
                $right = htmlspecialchars($err['right'] ?? '', ENT_QUOTES, 'UTF-8');
                $why = htmlspecialchars($err['why'] ?? '', ENT_QUOTES, 'UTF-8');
                $errorsHtml .= '<p style="margin:0 0 8px;padding-left:16px;border-left:3px solid #c9a84c33;">'
                    . '<span style="color:#e07070;text-decoration:line-through;">' . $wrong . '</span> → '
                    . '<span style="color:#70e070;">' . $right . '</span>'
                    . ($why ? '<br><span style="color:#999;font-size:12px;">' . $why . '</span>' : '')
                    . '</p>';
            } else {
                $eErr = htmlspecialchars((string)$err, ENT_QUOTES, 'UTF-8');
                $errorsHtml .= '<p style="margin:0 0 8px;padding-left:16px;border-left:3px solid #c9a84c33;">' . $eErr . '</p>';
            }
        }
        $errorsHtml .= '</td></tr>';
    }

    // Build tip
    $tipHtml = '';
    if (!empty($data['tip'])) {
        $eTip = htmlspecialchars($data['tip'], ENT_QUOTES, 'UTF-8');
        $tipHtml = '<tr><td style="padding:20px 40px;background:#1f1f1f;border-radius:0 0 8px 8px;">' .
            '<p style="margin:0;color:#c9a84c;font-size:13px;font-weight:bold;">' . $labels['tip'] . '</p>' .
            '<p style="margin:6px 0 0;color:#e8e0d0;font-size:14px;line-height:1.5;">' . $eTip . '</p>' .
            '</td></tr>';
    }

    // Source attribution
    $sourceHtml = '';
    if ($source === 'kb' || $source === 'kb+translated') {
        $sourceHtml = '<p style="margin:10px 0 0;color:#666;font-size:11px;font-style:italic;">'
            . ($isSpanish ? 'Fuente: Base de conocimiento de El Viaje del Jaguar' : 'Source: El Viaje del Jaguar knowledge base')
            . '</p>';
    } elseif ($source === 'languagetool') {
        $sourceHtml = '<p style="margin:10px 0 0;color:#666;font-size:11px;font-style:italic;">'
            . ($isSpanish ? 'Análisis de LanguageTool (código abierto)' : 'Analysis by LanguageTool (open source)')
            . '</p>';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,'Times New Roman',serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #c9a84c33;border-radius:8px;">
    <!-- Header -->
    <tr><td style="padding:40px 40px 20px;text-align:center;">
        <h1 style="color:#c9a84c;font-size:24px;margin:0;">&#x1F406; El Viaje del Jaguar</h1>
        <p style="color:#999;font-size:13px;margin:8px 0 0;">{$eTitle}</p>
    </td></tr>

    <!-- Greeting -->
    <tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:16px;line-height:1.6;">
        <p>{$labels['greeting']}</p>
    </td></tr>

    <!-- Original question -->
    <tr><td style="padding:0 40px 20px;">
        <div style="background:#0f0f0f;border-left:3px solid #c9a84c;padding:12px 16px;border-radius:0 4px 4px 0;">
            <p style="margin:0;color:#999;font-size:12px;text-transform:uppercase;letter-spacing:1px;">{$labels['your_question']}</p>
            <p style="margin:8px 0 0;color:#e8e0d0;font-size:15px;font-style:italic;line-height:1.5;">&ldquo;{$eQuestion}&rdquo;</p>
        </div>
    </td></tr>

    <!-- Title -->
    <tr><td style="padding:0 40px 10px;">
        <h2 style="margin:0;color:#c9a84c;font-size:20px;">{$eTitle}</h2>
    </td></tr>

    <!-- Explanation -->
    <tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:15px;line-height:1.7;">
        {$explanation}
    </td></tr>

    <!-- Examples -->
    {$examplesHtml}

    <!-- Common Errors -->
    {$errorsHtml}

    <!-- Tip -->
    {$tipHtml}

    <!-- Footer -->
    <tr><td style="padding:30px 40px;text-align:center;border-top:1px solid #333;">
        <p style="margin:0;color:#e8e0d0;font-size:14px;">{$labels['footer']}</p>
        {$sourceHtml}
        <p style="margin:16px 0 0;color:#666;font-size:11px;">El Viaje del Jaguar &mdash; babelfree.com</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
}

/**
 * Rate limit grammar questions: max 3 per hour per user.
 * Returns true if the request is allowed, false if rate-limited.
 */
function checkGrammarQuestionRateLimit(int $userId): bool {
    $key = "jaguar_rl:grammar_q:{$userId}";
    $max = 3;
    $window = 3600;

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);

        $current = (int)$redis->get($key);
        if ($current >= $max) {
            $redis->close();
            return false;
        }

        $redis->incr($key);
        if ($current === 0) {
            $redis->expire($key, $window);
        }
        $redis->close();
        return true;
    } catch (\Exception $e) {
        // If Redis is down, allow (fail open)
        error_log("Redis grammar rate limit error: " . $e->getMessage());
        return true;
    }
}

/**
 * Notify admin(s) when a grammar question couldn't be auto-answered.
 */
function notifyAdminUnansweredQuestion(
    string $question,
    string $studentEmail,
    string $studentName,
    string $cefr,
    ?string $destination,
    ?string $gameType
): void {
    $config = require __DIR__ . '/../config/app.php';
    $adminEmail = $config['admin_email'] ?? '';
    if (!$adminEmail) return;

    $eQuestion = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
    $eName     = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $eEmail    = htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8');
    $eDest     = htmlspecialchars($destination ?? '-', ENT_QUOTES, 'UTF-8');
    $eGame     = htmlspecialchars($gameType ?? '-', ENT_QUOTES, 'UTF-8');
    $adminUrl  = $config['base_url'] . '/feedback-admin.html';
    $time      = date('Y-m-d H:i:s');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,'Times New Roman',serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #c9a84c33;border-radius:8px;">
    <tr><td style="padding:30px 40px 15px;text-align:center;">
        <h1 style="color:#c9a84c;font-size:20px;margin:0;">&#x1F4E8; Pregunta sin respuesta automática</h1>
    </td></tr>
    <tr><td style="padding:10px 40px 20px;color:#e8e0d0;font-size:14px;line-height:1.6;">
        <p style="margin:0 0 12px;color:#999;font-size:13px;">{$time}</p>
        <table width="100%" style="border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;color:#c9a84c;width:100px;">Estudiante:</td><td style="padding:6px 0;color:#e8e0d0;">{$eName} ({$eEmail})</td></tr>
            <tr><td style="padding:6px 0;color:#c9a84c;">Nivel:</td><td style="padding:6px 0;color:#e8e0d0;">{$cefr}</td></tr>
            <tr><td style="padding:6px 0;color:#c9a84c;">Destino:</td><td style="padding:6px 0;color:#e8e0d0;">{$eDest}</td></tr>
            <tr><td style="padding:6px 0;color:#c9a84c;">Juego:</td><td style="padding:6px 0;color:#e8e0d0;">{$eGame}</td></tr>
        </table>
    </td></tr>
    <tr><td style="padding:0 40px 20px;">
        <div style="background:#0f0f0f;border-left:3px solid #c9a84c;padding:12px 16px;border-radius:0 4px 4px 0;">
            <p style="margin:0;color:#999;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Pregunta:</p>
            <p style="margin:8px 0 0;color:#e8e0d0;font-size:15px;font-style:italic;line-height:1.5;">&ldquo;{$eQuestion}&rdquo;</p>
        </div>
    </td></tr>
    <tr><td style="padding:0 40px 30px;text-align:center;">
        <a href="{$adminUrl}" style="display:inline-block;background:linear-gradient(135deg,#c9a84c,#8B6914);color:#0a0a0a;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:bold;font-size:14px;">Responder en el panel</a>
    </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;

    $subject = "Pregunta sin respuesta: {$studentName} ({$cefr})";
    sendMail($adminEmail, $subject, $html, $config['mail']);
}

/**
 * Send an admin reply email to a student regarding their feedback.
 */
function sendAdminReply(string $to, string $studentName, string $originalMessage, string $replyText): bool {
    $config = require __DIR__ . '/../config/app.php';

    $eName    = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $eOriginal = htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8');
    $eReply   = nl2br(htmlspecialchars($replyText, ENT_QUOTES, 'UTF-8'));

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,'Times New Roman',serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #c9a84c33;border-radius:8px;">
    <tr><td style="padding:40px 40px 20px;text-align:center;">
        <h1 style="color:#c9a84c;font-size:24px;margin:0;">&#x1F406; El Viaje del Jaguar</h1>
        <p style="color:#999;font-size:13px;margin:8px 0 0;">Respuesta del equipo</p>
    </td></tr>
    <tr><td style="padding:0 40px 20px;color:#e8e0d0;font-size:16px;line-height:1.6;">
        <p>Hola {$eName},</p>
    </td></tr>
    <tr><td style="padding:0 40px 15px;">
        <div style="background:#0f0f0f;border-left:3px solid #c9a84c;padding:12px 16px;border-radius:0 4px 4px 0;">
            <p style="margin:0;color:#999;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Tu mensaje original:</p>
            <p style="margin:8px 0 0;color:#aaa;font-size:14px;font-style:italic;line-height:1.4;">&ldquo;{$eOriginal}&rdquo;</p>
        </div>
    </td></tr>
    <tr><td style="padding:10px 40px 25px;color:#e8e0d0;font-size:15px;line-height:1.7;">
        {$eReply}
    </td></tr>
    <tr><td style="padding:20px 40px;text-align:center;border-top:1px solid #333;">
        <p style="margin:0;color:#e8e0d0;font-size:14px;">¿Necesitas más ayuda? Sigue preguntando desde el widget en el juego.</p>
        <p style="margin:16px 0 0;color:#666;font-size:11px;">El Viaje del Jaguar &mdash; babelfree.com</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;

    $subject = 'Respuesta a tu mensaje — El Viaje del Jaguar';
    return sendMail($to, $subject, $html, $config['mail']);
}
