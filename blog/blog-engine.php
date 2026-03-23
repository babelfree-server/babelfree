<?php
/**
 * Multilingual Blog Engine
 * 
 * Detects language from: ?lang= param → cookie → Accept-Language header → default (en)
 * Loads translations from blog/translations/{slug}-{lang}.json
 * Falls back to English if translation not available
 */

function getBlogLang() {
    // 1. Explicit param
    if (!empty($_GET['lang'])) {
        $lang = preg_replace('/[^a-z]/', '', strtolower($_GET['lang']));
        if (strlen($lang) === 2) {
            setcookie('blog_lang', $lang, time() + 86400 * 365, '/blog');
            return $lang;
        }
    }
    
    // 2. Cookie
    if (!empty($_COOKIE['blog_lang'])) {
        return preg_replace('/[^a-z]/', '', $_COOKIE['blog_lang']);
    }
    
    // 3. Accept-Language header
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (preg_match('/^([a-z]{2})/', $accept, $m)) {
        return $m[1];
    }
    
    return 'en';
}

function loadBlogTranslation($slug, $lang) {
    $supported = ['en','es','fr','de','pt','it','ru','zh','ja','ko','nl','ar'];
    if (!in_array($lang, $supported)) $lang = 'en';
    
    $file = __DIR__ . "/translations/{$slug}-{$lang}.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    
    // Fallback to English
    $enFile = __DIR__ . "/translations/{$slug}-en.json";
    if (file_exists($enFile)) {
        $data = json_decode(file_get_contents($enFile), true);
        $data['_fallback'] = true;
        $data['_requestedLang'] = $lang;
        return $data;
    }
    
    return null;
}

function getAvailableLanguages($slug) {
    $langs = [];
    $dir = __DIR__ . '/translations/';
    if (!is_dir($dir)) return ['en'];
    
    foreach (glob($dir . $slug . '-*.json') as $file) {
        if (preg_match('/-([a-z]{2})\.json$/', $file, $m)) {
            $langs[] = $m[1];
        }
    }
    return $langs ?: ['en'];
}

function renderLanguageSwitcher($slug, $currentLang) {
    $langs = getAvailableLanguages($slug);
    if (count($langs) <= 1) return '';
    
    $names = [
        'en' => 'English', 'es' => 'Español', 'fr' => 'Français',
        'de' => 'Deutsch', 'pt' => 'Português', 'it' => 'Italiano',
        'ru' => 'Русский', 'zh' => '中文', 'ja' => '日本語',
        'ko' => '한국어', 'nl' => 'Nederlands', 'ar' => 'العربية'
    ];
    
    $html = '<div class="blog-lang-switcher">';
    foreach ($langs as $l) {
        $active = ($l === $currentLang) ? ' class="active"' : '';
        $name = $names[$l] ?? strtoupper($l);
        $html .= "<a href=\"?lang={$l}\"{$active}>{$name}</a>";
    }
    $html .= '</div>';
    return $html;
}
