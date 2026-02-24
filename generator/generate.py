#!/usr/bin/env python3
"""
Page Generator for El Viaje del Jaguar
Generates 104 localized es-*.html pages from template + translations.

Usage:
    python3 generate.py                  # Generate all pages
    python3 generate.py en fr de         # Generate specific languages only
    python3 generate.py --list           # List all available language codes
"""

import json
import os
import sys
import re

# Paths
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(SCRIPT_DIR)
TEMPLATE_PATH = os.path.join(SCRIPT_DIR, 'template.html')
TRANSLATIONS_PATH = os.path.join(SCRIPT_DIR, 'translations.json')
OUTPUT_DIR = PROJECT_DIR  # es-*.html files go in public_html/

# All language codes and their hreflang mappings
LANG_CODES = [
    "en", "fr", "de", "it", "pt", "nl", "pl", "ro", "cs", "hu",
    "sv", "no", "da", "fi", "el", "bg", "hr", "sr", "sk", "sl",
    "lt", "lv", "et", "sq", "mk", "bs", "is", "mt", "cy", "ga",
    "gd", "eu", "ca", "gl", "lb", "uk", "be", "ru", "tr", "af",
    "zh", "zh-tw", "ja", "ko", "hi", "bn", "ur", "ta", "te", "mr",
    "gu", "kn", "ml", "pa", "or", "si", "ne", "th", "vi", "id",
    "ms", "tl", "my", "km", "lo", "mn", "uz", "kk", "ky", "tg",
    "tk", "az", "ka", "hy", "ar", "he", "fa", "ps", "ku", "sd",
    "ug", "sw", "am", "ha", "yo", "ig", "zu", "xh", "so", "rw",
    "sn", "st", "tn", "wo", "ln", "ti", "mg", "mi", "sm", "haw",
    "fj", "eo", "la", "bo"
]

# RTL languages
RTL_LANGS = {"ar", "he", "fa", "ps", "ur", "sd", "ug"}


def build_hreflang_tags(current_code):
    """Generate all hreflang link tags for a given page."""
    tags = []
    for code in LANG_CODES:
        tags.append(
            f'<link rel="alternate" hreflang="{code}" '
            f'href="https://babelfree.com/es-{code}.html">'
        )
    tags.append(
        '<link rel="alternate" hreflang="x-default" '
        'href="https://babelfree.com/">'
    )
    return '\n'.join(tags)


def generate_page(code, translations, template):
    """Generate a single es-{code}.html page."""
    if code not in translations:
        print(f"  SKIP: No translations for '{code}'")
        return False

    t = translations[code]
    direction = "rtl" if code in RTL_LANGS else "ltr"
    hreflang_tags = build_hreflang_tags(code)

    # Build replacement map
    replacements = {
        '{{lang_code}}': code,
        '{{lang_attr}}': t.get('lang_attr', code),
        '{{dir}}': direction,
        '{{hreflang_tags}}': hreflang_tags,
        '{{lang_name_english}}': t.get('lang_name_english', ''),
        '{{lang_name_native}}': t.get('lang_name_native', ''),
        '{{meta_title}}': t.get('meta_title', ''),
        '{{meta_description}}': t.get('meta_description', ''),
        '{{og_description}}': t.get('og_description', ''),
        '{{twitter_description}}': t.get('twitter_description', ''),
        '{{brand_subtitle}}': t.get('brand_subtitle', ''),
        '{{nav_languages}}': t.get('nav_languages', ''),
        '{{nav_journey}}': t.get('nav_journey', ''),
        '{{nav_contact}}': t.get('nav_contact', ''),
        '{{nav_login}}': t.get('nav_login', ''),
        '{{nav_start}}': t.get('nav_start', ''),
        '{{hero_subtitle}}': t.get('hero_subtitle', ''),
        '{{feature1_title}}': t.get('feature1_title', ''),
        '{{feature1_desc}}': t.get('feature1_desc', ''),
        '{{feature2_title}}': t.get('feature2_title', ''),
        '{{feature2_desc}}': t.get('feature2_desc', ''),
        '{{feature3_title}}': t.get('feature3_title', ''),
        '{{feature3_desc}}': t.get('feature3_desc', ''),
        '{{cta_begin}}': t.get('cta_begin', ''),
        '{{free_title}}': t.get('free_title', ''),
        '{{free_subtitle}}': t.get('free_subtitle', ''),
        '{{free_desc}}': t.get('free_desc', ''),
        '{{free_feature1}}': t.get('free_feature1', ''),
        '{{free_feature2}}': t.get('free_feature2', ''),
        '{{free_feature3}}': t.get('free_feature3', ''),
        '{{free_feature4}}': t.get('free_feature4', ''),
        '{{about_title}}': t.get('about_title', ''),
        '{{about_desc}}': t.get('about_desc', ''),
        '{{about_feature1_title}}': t.get('about_feature1_title', ''),
        '{{about_feature1_desc}}': t.get('about_feature1_desc', ''),
        '{{about_feature2_title}}': t.get('about_feature2_title', ''),
        '{{about_feature2_desc}}': t.get('about_feature2_desc', ''),
        '{{about_feature3_title}}': t.get('about_feature3_title', ''),
        '{{about_feature3_desc}}': t.get('about_feature3_desc', ''),
        '{{contact_title}}': t.get('contact_title', ''),
        '{{contact_subtitle}}': t.get('contact_subtitle', ''),
        '{{contact_cta}}': t.get('contact_cta', ''),
        '{{contact_write}}': t.get('contact_write', ''),
        '{{footer_desc}}': t.get('footer_desc', ''),
        '{{footer_available}}': t.get('footer_available', ''),
        '{{footer_worlds}}': t.get('footer_worlds', ''),
        '{{footer_contact}}': t.get('footer_contact', ''),
        '{{footer_languages}}': t.get('footer_languages', ''),
        '{{footer_login}}': t.get('footer_login', ''),
        '{{welcome_title}}': t.get('welcome_title', ''),
        '{{welcome_message}}': t.get('welcome_message', ''),
    }

    # Apply replacements
    output = template
    for placeholder, value in replacements.items():
        output = output.replace(placeholder, value)

    # Check for any remaining placeholders
    remaining = re.findall(r'\{\{[a-z_]+\}\}', output)
    if remaining:
        print(f"  WARNING [{code}]: Unfilled placeholders: {remaining}")

    # Write file
    filename = f"es-{code}.html"
    filepath = os.path.join(OUTPUT_DIR, filename)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(output)

    return True


def main():
    # Handle --list flag
    if '--list' in sys.argv:
        print(f"Available language codes ({len(LANG_CODES)}):")
        for code in LANG_CODES:
            print(f"  {code}")
        return

    # Load template
    if not os.path.exists(TEMPLATE_PATH):
        print(f"ERROR: Template not found at {TEMPLATE_PATH}")
        sys.exit(1)

    with open(TEMPLATE_PATH, 'r', encoding='utf-8') as f:
        template = f.read()

    # Load translations
    if not os.path.exists(TRANSLATIONS_PATH):
        print(f"ERROR: Translations not found at {TRANSLATIONS_PATH}")
        sys.exit(1)

    with open(TRANSLATIONS_PATH, 'r', encoding='utf-8') as f:
        translations = json.load(f)

    # Determine which languages to generate
    if len(sys.argv) > 1:
        codes_to_generate = [c for c in sys.argv[1:] if not c.startswith('-')]
    else:
        codes_to_generate = LANG_CODES

    # Generate pages
    success = 0
    skipped = 0
    for code in codes_to_generate:
        if code not in LANG_CODES:
            print(f"  UNKNOWN: '{code}' is not a recognized language code")
            skipped += 1
            continue
        if generate_page(code, translations, template):
            success += 1
        else:
            skipped += 1

    print(f"\nDone: {success} pages generated, {skipped} skipped.")
    print(f"Output directory: {OUTPUT_DIR}")


if __name__ == '__main__':
    main()
