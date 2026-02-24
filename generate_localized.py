#!/usr/bin/env python3
"""Generate localized landing pages and languages.html selector."""
import os, html as htmlmod

# Import language data
from lang_data_1 import LANGS_1
from lang_data_2 import LANGS_2
from lang_data_3 import LANGS_3
from lang_translations import TRANSLATIONS

ALL_LANGS = LANGS_1 + LANGS_2 + LANGS_3

# English fallback for any missing translation keys
EN = TRANSLATIONS["en"]

# Realistic front-facing jaguar SVGs
SVG_SMALL = '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="sf" cx="50%" cy="45%" r="50%"><stop offset="0%" stop-color="#f0b840"/><stop offset="70%" stop-color="#d4920a"/><stop offset="100%" stop-color="#a06800"/></radialGradient><radialGradient id="se" cx="40%" cy="35%"><stop offset="0%" stop-color="#7fff00"/><stop offset="60%" stop-color="#4db800"/><stop offset="100%" stop-color="#2d7a00"/></radialGradient><radialGradient id="sn" cx="50%" cy="30%"><stop offset="0%" stop-color="#4a3028"/><stop offset="100%" stop-color="#1a0a05"/></radialGradient></defs><ellipse cx="58" cy="38" rx="28" ry="32" fill="#b87a10"/><ellipse cx="142" cy="38" rx="28" ry="32" fill="#b87a10"/><ellipse cx="58" cy="42" rx="18" ry="20" fill="#d4920a"/><ellipse cx="142" cy="42" rx="18" ry="20" fill="#d4920a"/><ellipse cx="58" cy="44" rx="12" ry="14" fill="#e8b060"/><ellipse cx="142" cy="44" rx="12" ry="14" fill="#e8b060"/><ellipse cx="100" cy="105" rx="72" ry="78" fill="url(#sf)"/><ellipse cx="100" cy="75" rx="50" ry="30" fill="#c88a15" opacity="0.5"/><ellipse cx="100" cy="135" rx="42" ry="35" fill="#f5e6c8"/><ellipse cx="100" cy="128" rx="35" ry="25" fill="#fff5e0"/><ellipse cx="68" cy="90" rx="22" ry="18" fill="#a07010" opacity="0.4"/><ellipse cx="132" cy="90" rx="22" ry="18" fill="#a07010" opacity="0.4"/><ellipse cx="68" cy="90" rx="16" ry="12" fill="#111"/><ellipse cx="132" cy="90" rx="16" ry="12" fill="#111"/><circle cx="68" cy="90" r="10" fill="url(#se)"/><circle cx="132" cy="90" r="10" fill="url(#se)"/><ellipse cx="68" cy="90" rx="4.5" ry="8" fill="#000"/><ellipse cx="132" cy="90" rx="4.5" ry="8" fill="#000"/><circle cx="72" cy="86" r="3" fill="#fff" opacity="0.9"/><circle cx="136" cy="86" r="3" fill="#fff" opacity="0.9"/><circle cx="65" cy="93" r="1.5" fill="#fff" opacity="0.5"/><circle cx="129" cy="93" r="1.5" fill="#fff" opacity="0.5"/><ellipse cx="68" cy="90" rx="16" ry="12" fill="none" stroke="#1a0a00" stroke-width="2"/><ellipse cx="132" cy="90" rx="16" ry="12" fill="none" stroke="#1a0a00" stroke-width="2"/><path d="M56 100 Q52 110 50 120" stroke="#1a0a00" stroke-width="2" fill="none" opacity="0.5"/><path d="M144 100 Q148 110 150 120" stroke="#1a0a00" stroke-width="2" fill="none" opacity="0.5"/><path d="M88 118 Q92 110 100 108 Q108 110 112 118 Q108 124 100 126 Q92 124 88 118Z" fill="url(#sn)"/><ellipse cx="94" cy="117" rx="4" ry="3" fill="#2a1510" opacity="0.6"/><ellipse cx="106" cy="117" rx="4" ry="3" fill="#2a1510" opacity="0.6"/><ellipse cx="98" cy="114" rx="3" ry="2" fill="#5a4035" opacity="0.5"/><line x1="100" y1="126" x2="100" y2="138" stroke="#4a3020" stroke-width="2"/><path d="M85 142 Q100 152 115 142" stroke="#4a3020" stroke-width="2" fill="none"/><circle cx="82" cy="65" r="4" fill="#3a2000" opacity="0.6"/><circle cx="118" cy="65" r="4" fill="#3a2000" opacity="0.6"/><circle cx="100" cy="58" r="3.5" fill="#3a2000" opacity="0.5"/><circle cx="45" cy="95" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.5"/><circle cx="155" cy="95" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.5"/><circle cx="40" cy="110" r="4" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.5"/><circle cx="160" cy="110" r="4" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.5"/><circle cx="75" cy="130" r="2" fill="#4a3020" opacity="0.5"/><circle cx="80" cy="135" r="2" fill="#4a3020" opacity="0.5"/><circle cx="125" cy="130" r="2" fill="#4a3020" opacity="0.5"/><circle cx="120" cy="135" r="2" fill="#4a3020" opacity="0.5"/><line x1="72" y1="130" x2="25" y2="125" stroke="#e8d4b0" stroke-width="1" opacity="0.5"/><line x1="72" y1="135" x2="20" y2="138" stroke="#e8d4b0" stroke-width="1" opacity="0.5"/><line x1="128" y1="130" x2="175" y2="125" stroke="#e8d4b0" stroke-width="1" opacity="0.5"/><line x1="128" y1="135" x2="180" y2="138" stroke="#e8d4b0" stroke-width="1" opacity="0.5"/></svg>'

SVG_LARGE = '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="lf" cx="50%" cy="45%" r="50%"><stop offset="0%" stop-color="#f0b840"/><stop offset="70%" stop-color="#d4920a"/><stop offset="100%" stop-color="#a06800"/></radialGradient><radialGradient id="le" cx="40%" cy="35%"><stop offset="0%" stop-color="#7fff00"/><stop offset="60%" stop-color="#4db800"/><stop offset="100%" stop-color="#2d7a00"/></radialGradient><radialGradient id="ln" cx="50%" cy="30%"><stop offset="0%" stop-color="#4a3028"/><stop offset="100%" stop-color="#1a0a05"/></radialGradient></defs><ellipse cx="58" cy="38" rx="28" ry="32" fill="#b87a10"/><ellipse cx="142" cy="38" rx="28" ry="32" fill="#b87a10"/><ellipse cx="58" cy="42" rx="18" ry="20" fill="#d4920a"/><ellipse cx="142" cy="42" rx="18" ry="20" fill="#d4920a"/><ellipse cx="58" cy="44" rx="12" ry="14" fill="#e8b060"/><ellipse cx="142" cy="44" rx="12" ry="14" fill="#e8b060"/><circle cx="58" cy="35" r="5" fill="#1a0a00" opacity="0.4"/><circle cx="142" cy="35" r="5" fill="#1a0a00" opacity="0.4"/><ellipse cx="100" cy="105" rx="72" ry="78" fill="url(#lf)"/><ellipse cx="100" cy="75" rx="50" ry="30" fill="#c88a15" opacity="0.4"/><ellipse cx="100" cy="138" rx="42" ry="35" fill="#f5e6c8"/><ellipse cx="100" cy="130" rx="36" ry="26" fill="#fff5e0"/><ellipse cx="68" cy="92" rx="22" ry="17" fill="#a07010" opacity="0.35"/><ellipse cx="132" cy="92" rx="22" ry="17" fill="#a07010" opacity="0.35"/><ellipse cx="68" cy="92" rx="19" ry="14" fill="#e8d4a0" opacity="0.3"/><ellipse cx="132" cy="92" rx="19" ry="14" fill="#e8d4a0" opacity="0.3"/><ellipse cx="68" cy="92" rx="16" ry="12" fill="#111"/><ellipse cx="132" cy="92" rx="16" ry="12" fill="#111"/><circle cx="68" cy="92" r="10" fill="url(#le)"/><circle cx="132" cy="92" r="10" fill="url(#le)"/><ellipse cx="68" cy="92" rx="4.5" ry="8.5" fill="#000"/><ellipse cx="132" cy="92" rx="4.5" ry="8.5" fill="#000"/><circle cx="73" cy="87" r="3.5" fill="#fff" opacity="0.9"/><circle cx="137" cy="87" r="3.5" fill="#fff" opacity="0.9"/><circle cx="65" cy="95" r="1.8" fill="#fff" opacity="0.5"/><circle cx="129" cy="95" r="1.8" fill="#fff" opacity="0.5"/><ellipse cx="68" cy="92" rx="16" ry="12" fill="none" stroke="#1a0a00" stroke-width="2.5"/><ellipse cx="132" cy="92" rx="16" ry="12" fill="none" stroke="#1a0a00" stroke-width="2.5"/><path d="M56 100 Q52 110 50 120" stroke="#1a0a00" stroke-width="2" fill="none" opacity="0.5"/><path d="M144 100 Q148 110 150 120" stroke="#1a0a00" stroke-width="2" fill="none" opacity="0.5"/><path d="M86 120 Q90 111 100 109 Q110 111 114 120 Q110 127 100 129 Q90 127 86 120Z" fill="url(#ln)"/><ellipse cx="93" cy="119" rx="4.5" ry="3.5" fill="#2a1510" opacity="0.7"/><ellipse cx="107" cy="119" rx="4.5" ry="3.5" fill="#2a1510" opacity="0.7"/><ellipse cx="97" cy="115" rx="4" ry="2.5" fill="#6a5045" opacity="0.4"/><line x1="100" y1="129" x2="100" y2="142" stroke="#4a3020" stroke-width="2.5"/><path d="M82 146 Q92 154 100 150 Q108 154 118 146" stroke="#4a3020" stroke-width="2" fill="none"/><ellipse cx="100" cy="158" rx="20" ry="10" fill="#fff5e0" opacity="0.5"/><circle cx="82" cy="65" r="5" fill="none" stroke="#3a2000" stroke-width="2" opacity="0.5"/><circle cx="82" cy="65" r="2" fill="#3a2000" opacity="0.4"/><circle cx="118" cy="65" r="5" fill="none" stroke="#3a2000" stroke-width="2" opacity="0.5"/><circle cx="118" cy="65" r="2" fill="#3a2000" opacity="0.4"/><circle cx="100" cy="56" r="4" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.4"/><circle cx="100" cy="56" r="1.5" fill="#3a2000" opacity="0.3"/><circle cx="90" cy="76" r="3" fill="#3a2000" opacity="0.4"/><circle cx="110" cy="76" r="3" fill="#3a2000" opacity="0.4"/><circle cx="42" cy="95" r="6" fill="none" stroke="#3a2000" stroke-width="1.8" opacity="0.45"/><circle cx="42" cy="95" r="2.5" fill="#3a2000" opacity="0.35"/><circle cx="158" cy="95" r="6" fill="none" stroke="#3a2000" stroke-width="1.8" opacity="0.45"/><circle cx="158" cy="95" r="2.5" fill="#3a2000" opacity="0.35"/><circle cx="38" cy="112" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.4"/><circle cx="38" cy="112" r="2" fill="#3a2000" opacity="0.3"/><circle cx="162" cy="112" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.4"/><circle cx="162" cy="112" r="2" fill="#3a2000" opacity="0.3"/><circle cx="48" cy="128" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.35"/><circle cx="152" cy="128" r="5" fill="none" stroke="#3a2000" stroke-width="1.5" opacity="0.35"/><circle cx="55" cy="82" r="2.5" fill="#3a2000" opacity="0.4"/><circle cx="145" cy="82" r="2.5" fill="#3a2000" opacity="0.4"/><circle cx="65" cy="72" r="2" fill="#3a2000" opacity="0.35"/><circle cx="135" cy="72" r="2" fill="#3a2000" opacity="0.35"/><circle cx="73" cy="132" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="78" cy="137" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="71" cy="140" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="83" cy="142" r="2" fill="#5a4030" opacity="0.5"/><circle cx="127" cy="132" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="122" cy="137" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="129" cy="140" r="2.5" fill="#5a4030" opacity="0.6"/><circle cx="117" cy="142" r="2" fill="#5a4030" opacity="0.5"/><line x1="70" y1="132" x2="18" y2="126" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><line x1="70" y1="137" x2="14" y2="140" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><line x1="70" y1="142" x2="18" y2="155" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><line x1="130" y1="132" x2="182" y2="126" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><line x1="130" y1="137" x2="186" y2="140" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><line x1="130" y1="142" x2="182" y2="155" stroke="#f0dcc0" stroke-width="1.2" opacity="0.45"/><path d="M46 82 Q60 74 82 80" stroke="#8a6010" stroke-width="2" fill="none" opacity="0.35"/><path d="M154 82 Q140 74 118 80" stroke="#8a6010" stroke-width="2" fill="none" opacity="0.35"/></svg>'


def e(text):
    """HTML-escape text."""
    return htmlmod.escape(text, quote=True)


def t(code, key):
    """Get translated string with English fallback."""
    return TRANSLATIONS.get(code, EN).get(key, EN[key])


BASE_URL = "https://babelfree.com"


def gen_hreflang_tags(all_langs_list):
    """Generate hreflang link tags for all localized pages."""
    tags = []
    for lcode, *_ in all_langs_list:
        tags.append(f'<link rel="alternate" hreflang="{lcode}" href="{BASE_URL}/es-{lcode}">')
    tags.append(f'<link rel="alternate" hreflang="x-default" href="{BASE_URL}/">')
    return "\n".join(tags)


def gen_landing(code, name, native, flag, learn, free_tag, cta, direction):
    """Generate a localized landing page matching elviajedeljaguar.html design."""
    # RTL CSS additions
    rtl_css = ""
    if direction == "rtl":
        rtl_css = "body{direction:rtl;text-align:right}.nav-brand{flex-direction:row-reverse}.brand-text{text-align:right}.mobile-menu-item:hover{padding-left:0;padding-right:1rem}.free-feature{flex-direction:row-reverse}"

    # Gather all translated strings
    feat1_t = e(t(code, "feat1_t"))
    feat1_d = e(t(code, "feat1_d"))
    feat2_t = e(t(code, "feat2_t"))
    feat2_d = e(t(code, "feat2_d"))
    feat3_t = e(t(code, "feat3_t"))
    feat3_d = e(t(code, "feat3_d"))
    desc = e(t(code, "desc"))
    nav_lang = e(t(code, "nav_lang"))
    nav_login = e(t(code, "nav_login"))
    all_langs = e(t(code, "all_langs"))
    contact = e(t(code, "contact"))
    hero_subtitle = e(t(code, "hero_subtitle"))
    free_title = e(t(code, "free_title"))
    free_subtitle = e(t(code, "free_subtitle"))
    free_desc = e(t(code, "free_desc"))
    free_f1 = e(t(code, "free_f1"))
    free_f2 = e(t(code, "free_f2"))
    free_f3 = e(t(code, "free_f3"))
    free_f4 = e(t(code, "free_f4"))
    about_title = e(t(code, "about_title"))
    about_desc = e(t(code, "about_desc"))
    about_f1_t = e(t(code, "about_f1_t"))
    about_f1_d = e(t(code, "about_f1_d"))
    about_f2_t = e(t(code, "about_f2_t"))
    about_f2_d = e(t(code, "about_f2_d"))
    about_f3_t = e(t(code, "about_f3_t"))
    about_f3_d = e(t(code, "about_f3_d"))
    contact_title = e(t(code, "contact_title"))
    contact_subtitle = e(t(code, "contact_subtitle"))
    nav_dictionary = e(t(code, "nav_dictionary"))
    nav_journey = e(t(code, "nav_journey"))
    nav_start = e(t(code, "nav_start"))
    btn_begin = e(t(code, "btn_begin"))
    btn_first_step = e(t(code, "btn_first_step"))
    btn_write_us = e(t(code, "btn_write_us"))

    hreflang_tags = gen_hreflang_tags(ALL_LANGS)
    og_locale = code.replace("-", "_")

    return f'''<!DOCTYPE html>
<html lang="{code}" dir="{direction}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{e(learn)} - El Viaje del Jaguar</title>
<meta name="description" content="{e(learn)} - {desc}">
<link rel="canonical" href="{BASE_URL}/es-{code}">
<meta property="og:type" content="website">
<meta property="og:url" content="{BASE_URL}/es-{code}">
<meta property="og:title" content="{e(learn)} - El Viaje del Jaguar">
<meta property="og:description" content="{e(learn)} - {desc}">
<meta property="og:site_name" content="El Viaje del Jaguar">
<meta property="og:locale" content="{og_locale}">
<meta property="og:image" content="{BASE_URL}/img/og-jaguar.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{e(learn)} - El Viaje del Jaguar">
<meta name="twitter:description" content="{e(learn)} - {desc}">
<meta name="twitter:image" content="{BASE_URL}/img/og-jaguar.jpg">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#1a1208">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#x1F406;</text></svg>">
{hreflang_tags}
<script type="application/ld+json">
{{"@context":"https://schema.org","@graph":[{{"@type":"Course","name":"El Viaje del Jaguar — {e(learn)}","description":"{desc}","provider":{{"@type":"EducationalOrganization","name":"Babel Free","url":"{BASE_URL}/"}},"inLanguage":"es","availableLanguage":"{code}","isAccessibleForFree":true,"teaches":["Spanish grammar","Spanish vocabulary","Spanish conversation","Colombian culture"],"educationalLevel":["Beginner","Intermediate","Advanced"],"dateCreated":"2026-01-01","dateModified":"2026-02-24","educationalAlignment":[{{"@type":"AlignmentObject","alignmentType":"educationalLevel","educationalFramework":"CEFR","targetName":"A1 Breakthrough through C2 Mastery","targetUrl":"https://www.coe.int/en/web/common-european-framework-reference-languages/level-descriptions"}}]}},{{"@type":"BreadcrumbList","itemListElement":[{{"@type":"ListItem","position":1,"name":"Babel Free","item":"{BASE_URL}/"}},{{"@type":"ListItem","position":2,"name":"El Viaje del Jaguar","item":"{BASE_URL}/elviajedeljaguar"}},{{"@type":"ListItem","position":3,"name":"{e(name)}","item":"{BASE_URL}/es-{code}"}}]}}]}}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{{--earth-deep:#1a1208;--earth-warm:#2d1f0e;--earth-medium:#4a3520;--terracotta:#c67a4a;--ochre:#c9a227;--amber:#d4913a;--sand:#e8d5b7;--clay-light:#f0e6d3;--moss:#5a6e4a;--jaguar-gold:#c9a227;--jaguar-orange:#c67a4a;--glass-bg:rgba(26,18,8,0.7);--text-light:#f0e6d3;--text-muted:#b8a88a;--success-green:#5a6e4a;--transition:all .7s cubic-bezier(.23,1,.32,1);--shadow-card:0 8px 32px rgba(26,18,8,0.4);--shadow-warm:0 8px 24px rgba(198,122,74,0.15);--border-radius:24px;--font-display:'Cormorant Garamond',Georgia,serif}}
*{{margin:0;padding:0;box-sizing:border-box}}
html{{scroll-behavior:smooth}}
body{{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:linear-gradient(145deg,#0d0a06 0%,#1a1208 40%,#2d1f0e 100%);color:#fff;line-height:1.6;min-height:100vh}}
.bg-graphics{{position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;pointer-events:none;opacity:.1}}
.bg-circle{{position:absolute;border-radius:50%;background:radial-gradient(circle,var(--ochre),var(--terracotta));animation:bgfloat 20s ease-in-out infinite}}
.bg-circle:nth-child(1){{width:300px;height:300px;top:15%;left:15%;animation-delay:0s}}
.bg-circle:nth-child(2){{width:200px;height:200px;bottom:25%;right:20%;animation-delay:10s}}
@keyframes bgfloat{{0%,100%{{transform:translateY(0)}}50%{{transform:translateY(-30px)}}}}
.nav-bar{{position:fixed;top:0;width:100%;background:rgba(26,18,8,0.95);backdrop-filter:blur(20px);padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;z-index:1000;border-bottom:1px solid rgba(249,202,36,.2);transition:var(--transition)}}
.nav-brand{{display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit}}
.logo{{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(249,202,36,.5);transition:var(--transition);overflow:hidden}}
.logo:hover{{transform:scale(1.1);box-shadow:var(--shadow-warm)}}
.logo img{{width:100%;height:100%;object-fit:cover;border-radius:50%}}
.brand-text h1{{color:var(--jaguar-gold);font-weight:800;font-size:1.3rem;margin:0;background:linear-gradient(135deg,var(--jaguar-gold),#ffed4e);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}}
.brand-text p{{color:var(--text-muted);font-size:.8rem;margin:0}}
.nav-menu{{display:flex;gap:2rem;list-style:none;align-items:center}}
.nav-link{{color:#fff;text-decoration:none;font-weight:600;padding:.5rem 1rem;border-radius:25px;transition:var(--transition)}}
.nav-link:hover{{background:rgba(249,202,36,.2);color:var(--jaguar-gold);transform:translateY(-2px)}}
.nav-cta{{background:linear-gradient(135deg,var(--jaguar-orange),var(--jaguar-gold));color:#fff;padding:.7rem 1.5rem;border-radius:25px;text-decoration:none;font-weight:700;transition:var(--transition);box-shadow:var(--shadow-warm)}}
.nav-cta:hover{{transform:translateY(-3px);box-shadow:0 12px 32px rgba(198,122,74,.25)}}
.mobile-menu-toggle{{display:none;flex-direction:column;cursor:pointer;padding:.5rem}}
.menu-bar{{width:25px;height:3px;background:var(--jaguar-gold);margin:3px 0;transition:var(--transition);border-radius:2px}}
.mobile-menu{{display:none;position:fixed;top:80px;left:0;width:100%;background:rgba(26,18,8,0.98);backdrop-filter:blur(20px);padding:2rem;border-bottom:1px solid rgba(249,202,36,.2);z-index:999}}
.mobile-menu.active{{display:block;animation:slideDown .3s ease}}
@keyframes slideDown{{from{{opacity:0;transform:translateY(-20px)}}to{{opacity:1;transform:translateY(0)}}}}
.mobile-menu-item{{display:block;color:#fff;text-decoration:none;padding:1rem 0;border-bottom:1px solid rgba(255,255,255,.1);font-weight:600;transition:var(--transition)}}
.mobile-menu-item:hover{{color:var(--jaguar-gold);padding-left:1rem}}
.hero-section{{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:120px 2rem 40px}}
.hero-content{{max-width:900px;text-align:center;padding:3rem;background:rgba(26,18,8,0.85);backdrop-filter:blur(20px);border-radius:30px;border:2px solid rgba(249,202,36,.3);box-shadow:var(--shadow-card)}}
.hero-logo{{width:180px;height:180px;margin:0 auto 2rem;border-radius:50%;border:4px solid rgba(249,202,36,.7);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-warm);animation:breathe 6s ease-in-out infinite;overflow:hidden}}
@keyframes breathe{{0%,100%{{transform:scale(1)}}50%{{transform:scale(1.05)}}}}
.hero-flag{{font-size:4rem;display:block;margin-bottom:.5rem}}
.main-title{{font-size:3.5rem;font-weight:700;font-family:var(--font-display);margin-bottom:.5rem;background:linear-gradient(135deg,#fff,var(--jaguar-gold),var(--jaguar-orange));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2}}
.hero-native{{font-size:1.1rem;color:var(--text-muted);margin-bottom:1rem;font-style:italic}}
.subtitle{{font-size:1.3rem;margin-bottom:3rem;color:var(--text-light);line-height:1.7}}
.features{{display:grid;grid-template-columns:repeat(3,1fr);gap:2.5rem;margin:3rem auto;max-width:1000px;perspective:1000px}}
.feature-card{{background:linear-gradient(145deg,rgba(20,20,20,.85),rgba(10,10,10,.95));border-radius:20px;padding:2.5rem 2rem;text-align:center;border:1px solid rgba(212,168,67,.2);transition:all .5s cubic-bezier(.23,1,.32,1);backdrop-filter:blur(24px);position:relative;overflow:hidden}}
.feature-card::before{{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,var(--jaguar-gold),transparent);opacity:0;transition:opacity .5s ease}}
.feature-card:hover{{transform:translateY(-4px);border-color:rgba(212,168,67,.5);box-shadow:0 12px 32px rgba(26,18,8,.5)}}
.feature-card:hover::before{{opacity:1}}
.feature-icon{{font-size:3.2rem;margin-bottom:1.2rem;display:block;animation:bgfloat 4s ease-in-out infinite;filter:drop-shadow(0 4px 12px rgba(212,168,67,.3))}}
.feature-card:nth-child(2) .feature-icon{{animation-delay:1.3s}}
.feature-card:nth-child(3) .feature-icon{{animation-delay:2.6s}}
.feature-title{{font-size:1.3rem;font-weight:600;font-family:var(--font-display);margin-bottom:1rem;color:var(--jaguar-gold);letter-spacing:.03em}}
.feature-desc{{color:rgba(224,224,224,.85);line-height:1.7;font-size:.95rem}}
.cta-buttons{{display:flex;gap:1.5rem;justify-content:center;margin:3rem 0;flex-wrap:wrap}}
.btn-primary{{background:linear-gradient(135deg,var(--jaguar-orange),var(--jaguar-gold));color:#fff;border:none;padding:1.2rem 2.5rem;border-radius:50px;font-size:1.1rem;font-weight:700;cursor:pointer;transition:var(--transition);text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;box-shadow:var(--shadow-warm)}}
.btn-primary:hover{{transform:translateY(-3px);box-shadow:0 12px 32px rgba(198,122,74,.25)}}
.btn-secondary{{background:transparent;color:var(--jaguar-gold);border:2px solid var(--jaguar-gold);padding:1.2rem 2.5rem;border-radius:50px;font-size:1.1rem;font-weight:700;cursor:pointer;transition:var(--transition);text-decoration:none;display:inline-flex;align-items:center;gap:.5rem}}
.btn-secondary:hover{{background:var(--jaguar-gold);color:var(--earth-deep);transform:translateY(-3px)}}
.free-section{{background:rgba(0,0,0,.6);padding:4rem 2rem;backdrop-filter:blur(20px);text-align:center}}
.free-title{{font-size:2.5rem;font-family:var(--font-display);color:var(--jaguar-gold);margin-bottom:1rem;font-weight:800}}
.free-subtitle{{font-size:1.3rem;color:var(--success-green);margin-bottom:1rem;font-weight:600}}
.free-desc{{font-size:1.1rem;color:#e0e0e0;max-width:700px;margin:0 auto 2.5rem;line-height:1.8}}
.free-features{{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;max-width:900px;margin:0 auto 2.5rem;text-align:left}}
.free-feature{{background:var(--glass-bg);backdrop-filter:blur(20px);border-radius:var(--border-radius);padding:1.5rem;border:1px solid rgba(76,175,80,.3);display:flex;align-items:flex-start;gap:.8rem;font-size:1rem;color:var(--text-light)}}
.free-feature-icon{{font-size:1.3rem;flex-shrink:0}}
.about-section{{padding:4rem 2rem;text-align:center;background:rgba(255,255,255,.03)}}
.about-content{{max-width:800px;margin:0 auto}}
.about-content h2{{font-size:2.5rem;color:var(--jaguar-gold);margin-bottom:2rem;font-weight:800}}
.about-content>p{{font-size:1.1rem;color:#e0e0e0;margin-bottom:3rem;line-height:1.8}}
.contact-section{{background:rgba(26,18,8,.85);padding:4rem 2rem;text-align:center}}
.contact-container{{max-width:800px;margin:0 auto}}
.contact-title{{font-size:2.5rem;font-family:var(--font-display);color:var(--jaguar-gold);margin-bottom:1rem;font-weight:800}}
.contact-subtitle{{font-size:1.2rem;color:#e0e0e0;margin-bottom:2rem}}
.contact-buttons{{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:2rem}}
.notification{{position:fixed;top:100px;right:20px;background:rgba(76,175,80,.95);color:#fff;padding:1rem 1.5rem;border-radius:10px;font-weight:600;z-index:10000;transform:translateX(400px);transition:transform .4s ease;max-width:300px;box-shadow:var(--shadow-card);border:1px solid var(--success-green)}}
.notification.show{{transform:translateX(0)}}
.notification.info{{background:rgba(33,150,243,.95);border-color:#2196f3}}
.loading{{opacity:0;transform:translateY(20px);transition:all .6s ease}}
.loading.loaded{{opacity:1;transform:translateY(0)}}
@media(max-width:960px){{.features{{grid-template-columns:repeat(3,1fr);gap:1.5rem;max-width:100%}}}}
@media(max-width:768px){{.nav-menu{{display:none}}.mobile-menu-toggle{{display:flex}}.hero-section{{padding:100px 1rem 40px}}.hero-content{{padding:2rem}}.main-title{{font-size:2.5rem}}.subtitle{{font-size:1.1rem}}.hero-logo{{width:120px;height:120px}}.features{{grid-template-columns:1fr;gap:1.5rem}}.cta-buttons{{flex-direction:column;align-items:center}}.btn-primary,.btn-secondary{{width:100%;max-width:280px;justify-content:center}}.free-features{{grid-template-columns:1fr}}.contact-buttons{{flex-direction:column;align-items:center}}.notification{{right:10px;max-width:calc(100vw - 20px)}}}}
@media(max-width:480px){{.main-title{{font-size:2rem}}.nav-bar{{padding:1rem}}.hero-content{{padding:1.5rem}}.free-section,.about-section,.contact-section{{padding:3rem 1rem}}}}
@media(prefers-reduced-motion:reduce){{*,*::before,*::after{{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}}}
.nav-link:focus,.btn-primary:focus,.btn-secondary:focus{{outline:2px solid var(--jaguar-gold);outline-offset:2px}}
{rtl_css}
</style>
<link rel="stylesheet" href="css/footer.css">
</head>
<body>
<div class="bg-graphics"><div class="bg-circle"></div><div class="bg-circle"></div></div>
<nav class="nav-bar" role="navigation">
<a href="/" class="nav-brand">
<div class="logo"><img src="img/jaguar-hero-full.jpg" alt="Yaguará" loading="lazy"></div>
<div class="brand-text"><h1>El viaje del jaguar</h1><p>{e(native)}</p></div>
</a>
<ul class="nav-menu">
<li><a href="/dictionary" class="nav-link">{nav_dictionary}</a></li>
<li><a href="/languages" class="nav-link">{nav_lang}</a></li>
<li><a href="#curso" class="nav-link">{nav_journey}</a></li>
<li><a href="#contacto" class="nav-link">{contact}</a></li>
<li><a href="/login" class="nav-link">{nav_login}</a></li>
<li><a href="#" class="nav-cta" onclick="startFreeTrial()">{nav_start}</a></li>
</ul>
<div class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle mobile menu">
<div class="menu-bar"></div><div class="menu-bar"></div><div class="menu-bar"></div>
</div>
</nav>
<div class="mobile-menu" id="mobileMenu">
<a href="/dictionary" class="mobile-menu-item" onclick="closeMobileMenu()">{nav_dictionary}</a>
<a href="/languages" class="mobile-menu-item" onclick="closeMobileMenu()">{nav_lang}</a>
<a href="#curso" class="mobile-menu-item" onclick="closeMobileMenu()">{nav_journey}</a>
<a href="#contacto" class="mobile-menu-item" onclick="closeMobileMenu()">{contact}</a>
<a href="/login" class="mobile-menu-item" onclick="closeMobileMenu()">{nav_login}</a>
</div>
<section class="hero-section loading" id="inicio">
<div class="hero-content">
<div class="hero-logo"><img src="img/jaguar-hero-full.jpg" alt="Yaguará" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>
<span class="hero-flag">{flag}</span>
<h1 class="main-title">{e(learn)}</h1>
<p class="hero-native">{e(native)} &mdash; {e(name)}</p>
<p class="subtitle">{hero_subtitle}</p>
<div class="features">
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F331;</span><h3 class="feature-title">{feat1_t}</h3><p class="feature-desc">{feat1_d}</p></div>
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F30A;</span><h3 class="feature-title">{feat2_t}</h3><p class="feature-desc">{feat2_d}</p></div>
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F406;</span><h3 class="feature-title">{feat3_t}</h3><p class="feature-desc">{feat3_d}</p></div>
</div>
<div class="cta-buttons"><a href="#" class="btn-primary" onclick="startFreeTrial()">{btn_begin}</a></div>
</div>
</section>
<section class="free-section loading" id="precios">
<h2 class="free-title">{free_title}</h2>
<p class="free-subtitle">{free_subtitle}</p>
<p class="free-desc">{free_desc}</p>
<div class="free-features">
<div class="free-feature"><span class="free-feature-icon">&#x1F331;</span><span>{free_f1}</span></div>
<div class="free-feature"><span class="free-feature-icon">&#x1F30A;</span><span>{free_f2}</span></div>
<div class="free-feature"><span class="free-feature-icon">&#x1F406;</span><span>{free_f3}</span></div>
<div class="free-feature"><span class="free-feature-icon">&#x1F33F;</span><span>{free_f4}</span></div>
</div>
<div class="cta-buttons"><a href="#" class="btn-primary" onclick="startFreeTrial()">{btn_begin}</a></div>
</section>
<section class="about-section loading" id="curso">
<div class="about-content">
<h2>{about_title}</h2>
<p>{about_desc}</p>
<div class="features">
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F30E;</span><h3 class="feature-title">{about_f1_t}</h3><p class="feature-desc">{about_f1_d}</p></div>
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F331;</span><h3 class="feature-title">{about_f2_t}</h3><p class="feature-desc">{about_f2_d}</p></div>
<div class="feature-card"><span class="feature-icon" aria-hidden="true">&#x1F525;</span><h3 class="feature-title">{about_f3_t}</h3><p class="feature-desc">{about_f3_d}</p></div>
</div>
</div>
</section>
<section class="contact-section loading" id="contacto">
<div class="contact-container">
<h2 class="contact-title">{contact_title}</h2>
<p class="contact-subtitle">{contact_subtitle}</p>
<div class="contact-buttons">
<a href="#" class="btn-primary" onclick="startFreeTrial()">{btn_first_step}</a>
<a href="mailto:info@babelfree.com" class="btn-secondary">{btn_write_us}</a>
</div>
</div>
</section>
<footer class="site-footer">
<div class="footer-grid">
<div class="footer-brand">
<p class="footer-logo"><img src="assets/tower-logo.png" alt="El Viaje del Jaguar" loading="lazy"></p>
<p class="footer-desc">Curso de espa&ntilde;ol alineado al Marco Com&uacute;n Europeo de Referencia. Aprende espa&ntilde;ol a trav&eacute;s de la cultura colombiana, narrativa inmersiva y pedagog&iacute;a MCER &mdash; de A1 a C2.</p>
</div>
<nav class="footer-links" aria-label="Mapa del sitio">
<p class="footer-heading">Explorar</p>
<a href="/a1">A1 &mdash; Ra&iacute;ces</a>
<a href="/a2">A2 &mdash; Agua</a>
<a href="/b1">B1 &mdash; Comunidad</a>
<a href="/b2">B2 &mdash; Memoria</a>
<a href="/c1">C1 &mdash; Estrellas</a>
<a href="/c2">C2 &mdash; Destino</a>
<a href="/blog">Blog</a>
</nav>
<div class="footer-contact">
<p class="footer-heading">Escr&iacute;benos</p>
<a href="/contact" class="footer-cta">Buz&oacute;n de sugerencias</a>
<a href="mailto:info@babelfree.com" class="footer-cta-secondary">Correo electr&oacute;nico</a>
</div>
</div>
<div class="footer-bottom">
<p>&copy; 2026 El Viaje del Jaguar &middot; <a href="/">Babel Free</a> &middot; <a href="/privacy">Privacy</a></p>
</div>
</footer>
<script>
function toggleMobileMenu(){{var m=document.getElementById('mobileMenu'),t=document.querySelector('.mobile-menu-toggle');m.classList.toggle('active');t.classList.toggle('active');var b=t.querySelectorAll('.menu-bar');if(m.classList.contains('active')){{b[0].style.transform='rotate(45deg) translate(5px, 5px)';b[1].style.opacity='0';b[2].style.transform='rotate(-45deg) translate(7px, -6px)';document.body.style.overflow='hidden'}}else{{b[0].style.transform='none';b[1].style.opacity='1';b[2].style.transform='none';document.body.style.overflow='auto'}}}}
function closeMobileMenu(){{var m=document.getElementById('mobileMenu'),t=document.querySelector('.mobile-menu-toggle'),b=t.querySelectorAll('.menu-bar');m.classList.remove('active');t.classList.remove('active');b[0].style.transform='none';b[1].style.opacity='1';b[2].style.transform='none';document.body.style.overflow='auto'}}
function startFreeTrial(){{showNotification('\\uD83D\\uDC06 \\u00a1Bienvenido!','Redirecting to login...','info');localStorage.setItem('startingFreeTrial','true');localStorage.setItem('freeTrialTimestamp',new Date().toISOString());setTimeout(function(){{window.location.href='/login'}},1500)}}
function showNotification(title,message,type){{document.querySelectorAll('.notification').forEach(function(n){{n.remove()}});var d=document.createElement('div');d.className='notification '+(type||'info');d.setAttribute('role','alert');d.innerHTML='<div style="font-size:1.1em;margin-bottom:4px;font-weight:700">'+title+'</div><div style="font-size:.9em;opacity:.9">'+message+'</div>';document.body.appendChild(d);setTimeout(function(){{d.classList.add('show')}},100);setTimeout(function(){{d.classList.remove('show');setTimeout(function(){{if(document.body.contains(d))document.body.removeChild(d)}},400)}},4000)}}
document.querySelectorAll('a[href^="#"]').forEach(function(a){{a.addEventListener('click',function(ev){{ev.preventDefault();var tgt=document.querySelector(this.getAttribute('href'));if(tgt){{var nh=document.querySelector('.nav-bar').offsetHeight;window.scrollTo({{top:tgt.offsetTop-nh-20,behavior:'smooth'}})}};closeMobileMenu()}})}});
(function(){{var last=window.scrollY;window.addEventListener('scroll',function(){{var nav=document.querySelector('.nav-bar'),cur=window.scrollY;nav.style.background=cur>100?'rgba(26,18,8,0.98)':'rgba(26,18,8,0.95)';if(cur>last&&cur>200)nav.style.transform='translateY(-100%)';else nav.style.transform='translateY(0)';last=cur}})}})();
document.addEventListener('DOMContentLoaded',function(){{if('IntersectionObserver' in window){{var obs=new IntersectionObserver(function(entries){{entries.forEach(function(entry,i){{if(entry.isIntersecting){{setTimeout(function(){{entry.target.classList.add('loaded')}},i*100);obs.unobserve(entry.target)}}}})}},{{threshold:.1,rootMargin:'0px 0px -50px 0px'}});document.querySelectorAll('.loading').forEach(function(el){{obs.observe(el)}})}}else{{document.querySelectorAll('.loading').forEach(function(el){{el.classList.add('loaded')}})}}}});
document.addEventListener('click',function(ev){{var m=document.getElementById('mobileMenu'),t=document.querySelector('.mobile-menu-toggle');if(m.classList.contains('active')&&!m.contains(ev.target)&&!t.contains(ev.target))closeMobileMenu()}});
document.addEventListener('keydown',function(ev){{if(ev.key==='Escape'){{closeMobileMenu();document.querySelectorAll('.notification').forEach(function(n){{n.classList.remove('show');setTimeout(function(){{n.remove()}},400)}})}}}});
if('serviceWorker' in navigator){{navigator.serviceWorker.register('/service-worker.js')}}
</script>
</body>
</html>'''


def gen_languages_page(all_langs):
    """Generate the languages.html selector page."""
    cards = ""
    for code, name, native, flag, learn, free_tag, cta, d in all_langs:
        slug = f"/es-{code}"
        cards += f'''<a href="{slug}" class="lc"><span class="lf">{flag}</span><div class="ln">{e(name)}</div><div class="lnn">{e(native)}</div></a>\n'''

    return f'''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Languages - El Viaje del Jaguar | Learn Spanish in 100+ Languages</title>
<meta name="description" content="Learn Spanish in your native language. Choose from 100+ languages. Free A1-C2 course with 144+ interactive lessons. El Viaje del Jaguar.">
<link rel="canonical" href="{BASE_URL}/languages">
<meta property="og:type" content="website">
<meta property="og:url" content="{BASE_URL}/languages">
<meta property="og:title" content="All Languages - El Viaje del Jaguar">
<meta property="og:description" content="Learn Spanish in your native language. Choose from 100+ languages. Free A1-C2 course.">
<meta property="og:site_name" content="El Viaje del Jaguar">
<meta property="og:image" content="{BASE_URL}/img/og-jaguar.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="All Languages - El Viaje del Jaguar">
<meta name="twitter:description" content="Learn Spanish in 100+ languages. Free A1-C2 course.">
<meta name="twitter:image" content="{BASE_URL}/img/og-jaguar.jpg">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#0a3d0c">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#x1F406;</text></svg>">
<script type="application/ld+json">
{{"@context":"https://schema.org","@type":"ItemList","name":"Spanish Course Languages","description":"Learn Spanish in 100+ languages","numberOfItems":{len(all_langs)},"itemListElement":[{",".join(f'{{"@type":"ListItem","position":{i+1},"url":"{BASE_URL}/es-{code}","name":"{e(name)}"}}' for i,(code,name,*_) in enumerate(all_langs))}]}}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{{--forest-dark:#0a3d0c;--forest-medium:#1a5928;--forest-light:#2d6f3d;--jaguar-gold:#f9ca24;--jaguar-orange:#ff6b35;--glass-bg:rgba(255,255,255,.1);--text-light:#e8f5e8;--text-muted:#a5d6a7;--transition:all .3s cubic-bezier(.4,0,.2,1);--border-radius:20px}}
*{{margin:0;padding:0;box-sizing:border-box}}
body{{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0a1628,#1a2a4a,#2a3a5a);color:#fff;line-height:1.6;min-height:100vh}}
.nav{{position:fixed;top:0;width:100%;background:rgba(0,0,0,.85);backdrop-filter:blur(20px);padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;z-index:1000;border-bottom:1px solid rgba(249,202,36,.2)}}
.nav-brand{{display:flex;align-items:center;gap:1rem;text-decoration:none}}
.logo{{width:45px;height:45px;border-radius:50%;background:radial-gradient(circle at 30% 30%,var(--jaguar-orange),var(--jaguar-gold));display:flex;align-items:center;justify-content:center;border:2px solid rgba(249,202,36,.5)}}
.logo svg{{width:70%;height:70%}}
.brand h1{{color:var(--jaguar-gold);font-size:1.2rem;margin:0}}
.brand p{{color:var(--text-muted);font-size:.75rem;margin:0}}
.nav-links{{display:flex;gap:1rem;align-items:center}}
.nav-links a{{color:#fff;text-decoration:none;font-weight:600;padding:.5rem 1rem;border-radius:25px;transition:var(--transition);font-size:.9rem}}
.nav-links a:hover{{background:rgba(249,202,36,.2);color:var(--jaguar-gold)}}
.main{{max-width:1200px;margin:0 auto;padding:100px 2rem 2rem;position:relative;z-index:10}}
.hero{{text-align:center;margin-bottom:2rem}}
.hero h1{{font-size:2.8rem;font-weight:900;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--jaguar-gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}}
.hero p{{color:var(--text-light);font-size:1.1rem;max-width:600px;margin:0 auto}}
.search-box{{max-width:500px;margin:2rem auto;position:relative}}
.search-box input{{width:100%;padding:1rem 1.5rem 1rem 3rem;border-radius:50px;border:2px solid rgba(249,202,36,.3);background:rgba(0,0,0,.4);color:#fff;font-size:1rem;font-family:'Inter',sans-serif;outline:none;transition:var(--transition)}}
.search-box input:focus{{border-color:var(--jaguar-gold);box-shadow:0 0 20px rgba(249,202,36,.2)}}
.search-box input::placeholder{{color:rgba(255,255,255,.4)}}
.search-icon{{position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);font-size:1.2rem;opacity:.5}}
.count{{text-align:center;color:var(--text-muted);margin-bottom:1.5rem;font-size:.9rem}}
.grid{{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}}
.lc{{background:var(--glass-bg);backdrop-filter:blur(10px);border-radius:15px;padding:1.2rem;text-decoration:none;color:#fff;transition:var(--transition);border:1px solid transparent;text-align:center;display:block}}
.lc:hover{{transform:translateY(-5px);border-color:var(--jaguar-gold);box-shadow:0 10px 30px rgba(249,202,36,.2)}}
.lf{{font-size:2.5rem;display:block;margin-bottom:.5rem}}
.ln{{font-weight:700;font-size:.95rem;color:var(--jaguar-gold)}}
.lnn{{font-size:.8rem;color:var(--text-muted);font-style:italic}}
.footer{{background:rgba(0,0,0,.9);padding:2rem;text-align:center;border-top:1px solid rgba(255,255,255,.1);color:#999;font-size:.85rem;position:relative;z-index:10}}
.footer a{{color:#ccc;text-decoration:none}}
.footer a:hover{{color:var(--jaguar-gold)}}
@media(max-width:768px){{.nav-links{{display:none}}.grid{{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}}.hero h1{{font-size:2rem}}}}
</style>
</head>
<body>
<nav class="nav">
<a href="/" class="nav-brand"><div class="logo">{SVG_SMALL}</div><div class="brand"><h1>El viaje del jaguar</h1><p>Learn Spanish</p></div></a>
<div class="nav-links"><a href="/">Spanish Course</a><a href="/#contacto">Contact</a><a href="/login">Login</a></div>
</nav>
<div class="main">
<div class="hero">
<h1>&#x1F310; Choose Your Language</h1>
<p>Learn Spanish presented in your native language. Select below to see the course page in your language.</p>
</div>
<div class="search-box">
<span class="search-icon">&#x1F50D;</span>
<input type="text" id="search" placeholder="Search languages..." oninput="filterLangs()">
</div>
<div class="count" id="count">{len(all_langs)} languages available</div>
<div class="grid" id="grid">
{cards}</div>
</div>
<footer class="footer">
<p>&copy; 2026 El Viaje del Jaguar | <a href="/">Spanish Course</a> | <a href="/blog">Blog</a> | <a href="mailto:info@babelfree.com">Contact</a></p>
</footer>
<script>
function filterLangs(){{var q=document.getElementById('search').value.toLowerCase();var cards=document.querySelectorAll('.lc');var n=0;cards.forEach(function(c){{var t=c.textContent.toLowerCase();if(t.indexOf(q)>-1){{c.style.display='';n++}}else{{c.style.display='none'}}}});document.getElementById('count').textContent=n+' languages shown'}}
if('serviceWorker' in navigator){{navigator.serviceWorker.register('/service-worker.js');}}
</script>
</body>
</html>'''


def main():
    d = os.path.dirname(os.path.abspath(__file__))
    count = 0

    # Generate individual landing pages
    for code, name, native, flag, learn, free_tag, cta, direction in ALL_LANGS:
        fname = f"es-{code}.html"
        path = os.path.join(d, fname)
        with open(path, "w", encoding="utf-8") as f:
            f.write(gen_landing(code, name, native, flag, learn, free_tag, cta, direction))
        count += 1
        print(f"  {fname}")

    # Generate languages.html
    path = os.path.join(d, "languages.html")
    with open(path, "w", encoding="utf-8") as f:
        f.write(gen_languages_page(ALL_LANGS))
    print(f"  languages.html (selector)")

    print(f"\nGenerated {count} localized pages + languages.html")


if __name__ == "__main__":
    main()
