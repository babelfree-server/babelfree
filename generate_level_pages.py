#!/usr/bin/env python3
"""Generate CEFR level landing pages for El Viaje del Jaguar."""

import os

# Jaguar SVG used across all pages (inline, no external deps)
JAGUAR_SVG = '''<img src="img/jaguar-hero.jpg" alt="Yaguara the Jaguar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'''

JAGUAR_SVG_SMALL = '''<img src="img/jaguar-hero.jpg" alt="Yaguara" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'''

# Shared footer across all level pages
FOOTER_HTML = '''    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>El Viaje del Jaguar</h3>
                <p>Learn Spanish through the epic adventures of Yaguar&aacute; in Colombia. Total immersion with game-based learning.</p>
                <p>Available in 100+ languages at babelfree.com</p>
            </div>
            <div class="footer-section">
                <h3>Levels Available</h3>
                <p><a href="a1.html">A1 - Beginner</a></p>
                <p><a href="a2.html">A2 - Elementary</a></p>
                <p><a href="b1.html">B1 - Intermediate</a></p>
                <p><a href="b2.html">B2 - Upper Intermediate</a></p>
                <p><a href="c1.html">C1 - Advanced</a></p>
                <p><a href="c2.html">C2 - Mastery</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact &amp; Support</h3>
                <p><a href="mailto:info@babelfree.com">info@babelfree.com</a></p>
                <p><a href="languages.html">All Languages</a></p>
                <p><a href="/blog">Blog</a></p>
                <p><a href="login.html">Student Login</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 El Viaje del Jaguar &middot; <a href="/">Babel Free</a></p>
        </div>
    </footer>'''

# Region data from dashboard
LEVELS = [
    {
        "file": "a1.html",
        "level": "A1",
        "level_name": "Beginner",
        "level_label": "A1 B\u00c1SICO",
        "title": "El Despertar del Jaguar",
        "region": "Amazon &amp; Andes",
        "city": "Leticia &amp; Manizales",
        "ecosystem": "Bosque Tropical H\u00famedo &amp; Monta\u00f1a Cafetera",
        "color_dark": "#0a2e0a",
        "color_medium": "#1a4d1a",
        "color_light": "#2d6b2d",
        "accent": "#27ae60",
        "accent_light": "#2ecc71",
        "cultural": ["Pueblos ind\u00edgenas", "Medicina tradicional", "R\u00edo Amazonas", "Cultura Paisa", "Caf\u00e9 de especialidad"],
        "species": ["Jaguar", "Anaconda", "Guacamaya", "Oso de Anteojos", "Quetzal"],
        "floating_elements": ["\U0001F333", "\U0001F98B", "\U0001F33A", "\U0001F426", "\U0001F343", "\U0001F333", "\u2728", "\U0001F98E"],
        "is_active": True,
        "sub_pages": [
            {"url": "a1-amazon.html", "title": "Selva Amaz\u00f3nica", "subtitle": "Leticia, Colombia", "icon": "\U0001F333", "desc": "Journey through the Amazon rainforest. Learn basic survival Spanish with Yaguar\u00e1 in the world's largest tropical forest.", "lessons": 24, "badge_color": "#27ae60"},
            {"url": "a1-andes.html", "title": "Cordillera de los Andes", "subtitle": "Manizales, Colombia", "icon": "\u26f0\ufe0f", "desc": "Climb the Andes mountains. Explore coffee culture and mountain vocabulary in Colombia's coffee region.", "lessons": 32, "badge_color": "#8b4513"},
            {"url": "a1-exam.html", "title": "Examen Final A1", "subtitle": "Test your knowledge", "icon": "\U0001F3C6", "desc": "Complete the A1 final exam to unlock permanent access. Prove your beginner Spanish skills!", "lessons": 1, "badge_color": "#f9ca24"},
        ],
    },
    {
        "file": "a2.html",
        "level": "A2",
        "level_name": "Elementary",
        "level_label": "A2 ELEMENTAL",
        "title": "Los Desaf\u00edos del Jaguar",
        "region": "Costa Caribe\u00f1a",
        "city": "Cartagena, Colombia",
        "ecosystem": "Costa y Manglares",
        "color_dark": "#002244",
        "color_medium": "#003366",
        "color_light": "#004488",
        "accent": "#0077be",
        "accent_light": "#3498db",
        "cultural": ["Cultura Afrocaribe", "M\u00fasica vallenata", "Arquitectura colonial"],
        "species": ["Manat\u00ed", "Iguana", "Manglar", "Coral", "Pel\u00edcano"],
        "floating_elements": ["\U0001F3D6\ufe0f", "\U0001F30A", "\U0001F41A", "\U0001F420", "\U0001F334", "\u2600\ufe0f", "\U0001F9DC\u200d\u2640\ufe0f", "\U0001F40B"],
        "is_active": False,
        "what_youll_learn": [
            "Navigate Colombian Caribbean coastal cities",
            "Discuss food, markets, and daily life",
            "Understand vallenato music and Afro-Caribbean culture",
            "Describe mangrove ecosystems and marine life",
            "Use past tense and tell stories about experiences",
        ],
    },
    {
        "file": "b1.html",
        "level": "B1",
        "level_name": "Intermediate",
        "level_label": "B1 INTERMEDIO",
        "title": "La Madurez del Jaguar",
        "region": "Costa Pac\u00edfica - Choc\u00f3",
        "city": "Quibd\u00f3, Colombia",
        "ecosystem": "Bosque H\u00famedo Tropical",
        "color_dark": "#003333",
        "color_medium": "#005555",
        "color_light": "#007777",
        "accent": "#008080",
        "accent_light": "#1abc9c",
        "cultural": ["Comunidades afro", "M\u00fasica del Pac\u00edfico", "Artesan\u00edas"],
        "species": ["Ballena Jorobada", "Rana Dorada", "Palma", "Heliconias", "Tuc\u00e1n"],
        "floating_elements": ["\U0001F30A", "\U0001F40B", "\U0001F338", "\U0001F99C", "\U0001F33F", "\U0001F41A", "\u2728", "\U0001F308"],
        "is_active": False,
        "what_youll_learn": [
            "Discuss environmental conservation and biodiversity",
            "Express opinions about social and cultural topics",
            "Understand Pacific coast music traditions and rhythms",
            "Describe complex ecosystems and endemic species",
            "Narrate experiences using subjunctive and conditionals",
        ],
    },
    {
        "file": "b2.html",
        "level": "B2",
        "level_name": "Upper Intermediate",
        "level_label": "B2 INTERMEDIO ALTO",
        "title": "El Legado del Jaguar",
        "region": "Llanos Orientales",
        "city": "Villavicencio, Colombia",
        "ecosystem": "Sabana y Ganader\u00eda",
        "color_dark": "#3d2e00",
        "color_medium": "#5c4400",
        "color_light": "#7a5b00",
        "accent": "#ffd700",
        "accent_light": "#f39c12",
        "cultural": ["Cultura Llanera", "Joropo", "Ganader\u00eda sostenible"],
        "species": ["Chig\u00fciro", "Caim\u00e1n", "Palma Moriche", "Garceta", "Venado"],
        "floating_elements": ["\U0001F33E", "\U0001F434", "\U0001F305", "\U0001F985", "\U0001F33B", "\U0001F404", "\u2728", "\U0001F3B6"],
        "is_active": False,
        "what_youll_learn": [
            "Debate agricultural sustainability and land use",
            "Analyze Colombian literature and journalistic texts",
            "Understand Llanero cowboy culture and joropo music",
            "Discuss economics, trade, and rural development",
            "Use advanced grammar including reported speech and passive voice",
        ],
    },
    {
        "file": "c1.html",
        "level": "C1",
        "level_name": "Advanced",
        "level_label": "C1 AVANZADO",
        "title": "La Sabidur\u00eda del Jaguar",
        "region": "Sierra Nevada de Santa Marta",
        "city": "Santa Marta, Colombia",
        "ecosystem": "Monta\u00f1a Sagrada",
        "color_dark": "#1a0033",
        "color_medium": "#330066",
        "color_light": "#4d0099",
        "accent": "#663399",
        "accent_light": "#9b59b6",
        "cultural": ["Pueblos ancestrales", "Ciudad Perdida", "Sabidur\u00eda ind\u00edgena"],
        "species": ["C\u00f3ndor", "Oso Frontino", "Frailej\u00f3n", "Colibr\u00ed", "Orqu\u00eddea Nativa"],
        "floating_elements": ["\U0001F3D4\ufe0f", "\U0001F985", "\u2728", "\U0001F33F", "\U0001F30C", "\U0001F54A\ufe0f", "\U0001F3DB\ufe0f", "\U0001F4DC"],
        "is_active": False,
        "what_youll_learn": [
            "Interpret indigenous philosophy and cosmovision",
            "Analyze complex academic and literary texts",
            "Debate historical and archaeological topics fluently",
            "Understand dialectal variations across Colombian regions",
            "Produce nuanced arguments on social and environmental issues",
        ],
    },
    {
        "file": "c2.html",
        "level": "C2",
        "level_name": "Mastery",
        "level_label": "C2 MAESTR\u00cdA",
        "title": "El Esp\u00edritu del Jaguar",
        "region": "Toda Colombia",
        "city": "All Regions",
        "ecosystem": "Todos los Ecosistemas",
        "color_dark": "#2a1a00",
        "color_medium": "#4a3000",
        "color_light": "#6a4500",
        "accent": "#daa520",
        "accent_light": "#f9ca24",
        "cultural": ["Todas las culturas colombianas", "Literatura nacional", "Identidad y diversidad"],
        "species": ["Jaguar (s\u00edmbolo)", "C\u00f3ndor", "Guacamaya", "Ceiba", "Orqu\u00eddeas"],
        "floating_elements": ["\U0001F451", "\u2728", "\U0001F30E", "\U0001F3C6", "\U0001F33F", "\U0001F405", "\U0001F4DC", "\U0001F31F"],
        "is_active": False,
        "what_youll_learn": [
            "Full mastery of all Colombian Spanish registers and dialects",
            "Create and analyze literary, academic, and professional texts",
            "Navigate all cultural contexts with native-like fluency",
            "Synthesize knowledge from all regions into comprehensive understanding",
            "Achieve permanent certification as a Spanish master",
        ],
    },
]


def generate_footer_css():
    """CSS for the shared footer."""
    return '''
        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.9);
            padding: 3rem 2rem 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 10;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto 2rem;
            text-align: left;
        }

        .footer-section h3 {
            color: var(--jaguar-gold);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .footer-section p {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .footer-section a {
            color: #ccc;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--jaguar-gold);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #999;
        }

        .photo-credit {
            color: #666;
            font-size: 0.8rem;
            margin-top: 1rem;
            opacity: 0.7;
        }'''


def generate_page(level_data):
    """Generate a complete HTML page for a CEFR level."""
    d = level_data
    is_a1 = d["level"] == "A1"
    is_active = d.get("is_active", False)

    # Build species badges HTML
    species_html = ""
    for sp in d["species"]:
        species_html += f'                        <span class="species-tag">{sp}</span>\n'

    # Build cultural elements HTML
    cultural_html = ""
    for c in d["cultural"]:
        cultural_html += f'                        <span class="cultural-tag">{c}</span>\n'

    # Build the content section (different for A1 vs Coming Soon)
    if is_a1 and "sub_pages" in d:
        content_section = generate_a1_content(d)
    else:
        content_section = generate_coming_soon_content(d)

    # Build floating elements JS array
    floating_js = ", ".join([f"'{e}'" for e in d["floating_elements"]])

    html = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{d["level"]} {d["level_name"]} - El Viaje del Jaguar</title>
    <meta name="description" content="Level {d["level"]}: {d["title"]} - Learn Spanish through immersive adventures in Colombia's {d["region"]}. Free course with interactive games.">
    <link rel="canonical" href="https://babelfree.com/{d["level"].lower()}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://babelfree.com/{d["level"].lower()}">
    <meta property="og:title" content="{d["level"]} {d["level_name"]} - El Viaje del Jaguar">
    <meta property="og:description" content="Level {d["level"]}: {d["title"]} - Learn Spanish through immersive adventures in Colombia">
    <meta property="og:site_name" content="El Viaje del Jaguar">
    <meta property="og:image" content="https://babelfree.com/img/og-jaguar.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{d["level"]} {d["level_name"]} - El Viaje del Jaguar">
    <meta name="twitter:image" content="https://babelfree.com/img/og-jaguar.jpg">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="{d["color_dark"]}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#x1F406;</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {{
            --level-dark: {d["color_dark"]};
            --level-medium: {d["color_medium"]};
            --level-light: {d["color_light"]};
            --level-accent: {d["accent"]};
            --level-accent-light: {d["accent_light"]};
            --jaguar-gold: #f9ca24;
            --jaguar-orange: #ff6b35;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --text-light: #e8f5e8;
            --text-muted: #a5d6a7;
            --success-green: #4caf50;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.3);
            --border-radius: 20px;
        }}

        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        html {{ scroll-behavior: smooth; }}

        body {{
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--level-dark) 0%, var(--level-medium) 50%, var(--level-light) 100%);
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }}

        body::before {{
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background:
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.03) 0%, transparent 50%);
            z-index: 0;
            pointer-events: none;
        }}

        /* Floating Elements */
        .floating-elements {{
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            pointer-events: none; z-index: 1;
        }}

        .float-el {{
            position: absolute;
            animation: floatDown 20s infinite linear;
            opacity: 0.5;
            font-size: 1.5em;
        }}

        @keyframes floatDown {{
            0% {{ transform: translateY(-100px) translateX(0px) rotate(0deg); opacity: 0; }}
            10% {{ opacity: 0.5; }}
            90% {{ opacity: 0.5; }}
            100% {{ transform: translateY(100vh) translateX(30px) rotate(180deg); opacity: 0; }}
        }}

        /* Navigation */
        .nav-bar {{
            position: fixed; top: 0; width: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            z-index: 1000;
            border-bottom: 1px solid rgba(249, 202, 36, 0.2);
        }}

        .nav-brand {{
            display: flex; align-items: center; gap: 1rem;
            text-decoration: none;
        }}

        .logo {{
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid rgba(249, 202, 36, 0.5);
            transition: var(--transition);
            overflow: hidden;
        }}

        .logo:hover {{
            transform: scale(1.1);
            box-shadow: 0 8px 32px rgba(249, 202, 36, 0.3);
        }}

        .logo svg {{ width: 70%; height: 70%; }}

        .brand-text h1 {{
            color: var(--jaguar-gold); font-weight: 800; font-size: 1.3rem; margin: 0;
            background: linear-gradient(135deg, var(--jaguar-gold), #ffed4e);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }}

        .brand-text p {{ color: var(--text-muted); font-size: 0.8rem; margin: 0; }}

        .nav-actions {{ display: flex; gap: 1rem; align-items: center; }}

        .nav-link {{
            color: #fff; text-decoration: none; font-weight: 600;
            padding: 0.5rem 1rem; border-radius: 25px; transition: var(--transition);
        }}

        .nav-link:hover {{
            background: rgba(249, 202, 36, 0.2); color: var(--jaguar-gold);
        }}

        .nav-cta {{
            background: linear-gradient(135deg, var(--jaguar-orange), var(--jaguar-gold));
            color: white; padding: 0.7rem 1.5rem; border-radius: 25px;
            text-decoration: none; font-weight: 700; transition: var(--transition);
            box-shadow: 0 8px 32px rgba(249, 202, 36, 0.3);
        }}

        .nav-cta:hover {{
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(255, 107, 53, 0.4);
        }}

        /* Mobile Menu */
        .mobile-menu-toggle {{
            display: none; flex-direction: column; cursor: pointer; padding: 0.5rem;
        }}

        .menu-bar {{
            width: 25px; height: 3px; background: var(--jaguar-gold);
            margin: 3px 0; transition: var(--transition); border-radius: 2px;
        }}

        .mobile-menu {{
            display: none; position: fixed; top: 80px; left: 0; width: 100%;
            background: rgba(0, 0, 0, 0.98); backdrop-filter: blur(20px);
            padding: 2rem; border-bottom: 1px solid rgba(249, 202, 36, 0.2); z-index: 999;
        }}

        .mobile-menu.active {{ display: block; }}

        .mobile-menu-item {{
            display: block; color: #fff; text-decoration: none;
            padding: 1rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 600; transition: var(--transition);
        }}

        .mobile-menu-item:hover {{ color: var(--jaguar-gold); padding-left: 1rem; }}

        /* Main Content */
        .main-content {{
            max-width: 1100px; margin: 0 auto;
            padding: 110px 2rem 2rem;
            position: relative; z-index: 10;
        }}

        /* Level Badge */
        .level-badge {{
            display: inline-block;
            background: linear-gradient(135deg, var(--level-accent), var(--level-accent-light));
            color: white; padding: 0.5rem 1.5rem; border-radius: 25px;
            font-weight: 700; font-size: 0.9rem; margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }}

        /* Hero Section */
        .hero-section {{
            text-align: center;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 3rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(249, 202, 36, 0.3);
            box-shadow: var(--shadow-card);
            position: relative; overflow: hidden;
        }}

        .hero-section::before {{
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(249, 202, 36, 0.05), transparent);
            z-index: 0;
        }}

        .hero-inner {{ position: relative; z-index: 1; }}

        .jaguar-hero {{
            width: 200px; height: 200px; border-radius: 50%;
            margin: 0 auto 1.5rem; border: 5px solid var(--jaguar-gold);
            display: flex; align-items: center; justify-content: center;
            animation: breathe 6s ease-in-out infinite;
            box-shadow: 0 0 30px rgba(249, 202, 36, 0.6), 0 0 60px rgba(249, 202, 36, 0.3), 0 12px 40px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }}

        .jaguar-hero svg {{ width: 95%; height: 95%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }}

        @keyframes breathe {{
            0%, 100% {{ transform: scale(1); }}
            50% {{ transform: scale(1.05); }}
        }}

        .jaguar-hero:hover {{
            transform: scale(1.12);
            box-shadow: 0 0 40px rgba(249, 202, 36, 0.8), 0 0 80px rgba(249, 202, 36, 0.4), 0 16px 50px rgba(0, 0, 0, 0.6);
        }}

        .hero-title {{
            font-size: 3rem; font-weight: 900; margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff, var(--jaguar-gold), var(--level-accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            line-height: 1.2;
        }}

        .hero-subtitle {{
            font-size: 1.3rem; color: var(--text-light); margin-bottom: 0.5rem;
        }}

        .region-badge {{
            display: inline-block;
            background: linear-gradient(135deg, var(--level-accent), var(--level-accent-light));
            color: white; padding: 0.8rem 2rem; border-radius: 30px;
            font-weight: 700; font-size: 1.1rem; margin: 1rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }}

        /* Region Info Section */
        .region-info {{
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 2px solid var(--level-accent);
            box-shadow: var(--shadow-card);
        }}

        .region-info h2 {{
            color: var(--level-accent);
            font-size: 1.8rem; font-weight: 800; margin-bottom: 1.5rem;
            text-align: center;
        }}

        .region-details {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }}

        .detail-card {{
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px; padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }}

        .detail-card h3 {{
            color: var(--jaguar-gold); font-size: 1.1rem;
            font-weight: 700; margin-bottom: 1rem;
        }}

        .species-tag, .cultural-tag {{
            display: inline-block;
            padding: 0.4rem 0.8rem; border-radius: 15px;
            font-size: 0.85rem; font-weight: 600; margin: 0.25rem;
        }}

        .species-tag {{
            background: rgba(76, 175, 80, 0.2);
            color: var(--success-green);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }}

        .cultural-tag {{
            background: rgba(249, 202, 36, 0.15);
            color: var(--jaguar-gold);
            border: 1px solid rgba(249, 202, 36, 0.3);
        }}

        /* Sub-page Cards (A1 specific) */
        .subpage-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }}

        .subpage-card {{
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 2rem;
            border: 2px solid transparent;
            transition: var(--transition);
            text-decoration: none; color: white;
            display: block;
            position: relative; overflow: hidden;
        }}

        .subpage-card::before {{
            content: '';
            position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--level-accent), var(--jaguar-gold));
            opacity: 0; transition: opacity 0.3s ease;
        }}

        .subpage-card:hover {{
            transform: translateY(-8px);
            border-color: var(--jaguar-gold);
            box-shadow: 0 15px 40px rgba(249, 202, 36, 0.3);
        }}

        .subpage-card:hover::before {{ opacity: 1; }}

        .subpage-icon {{
            font-size: 3rem; margin-bottom: 1rem;
            display: block;
        }}

        .subpage-title {{
            font-size: 1.4rem; font-weight: 700;
            color: var(--jaguar-gold); margin-bottom: 0.3rem;
        }}

        .subpage-subtitle {{
            font-size: 0.95rem; color: var(--text-muted);
            margin-bottom: 1rem; font-style: italic;
        }}

        .subpage-desc {{
            color: var(--text-light); line-height: 1.7;
            margin-bottom: 1rem;
        }}

        .subpage-meta {{
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }}

        .lesson-count {{
            font-weight: 600; color: var(--text-muted); font-size: 0.9rem;
        }}

        .subpage-cta {{
            background: linear-gradient(135deg, var(--jaguar-gold), var(--jaguar-orange));
            color: white; padding: 0.5rem 1.2rem; border-radius: 20px;
            font-weight: 700; font-size: 0.85rem;
        }}

        /* Coming Soon Section */
        .coming-soon {{
            text-align: center;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 3rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(249, 202, 36, 0.2);
        }}

        .coming-soon-badge {{
            display: inline-block;
            background: linear-gradient(135deg, var(--jaguar-orange), #e74c3c);
            color: white; padding: 0.6rem 1.5rem; border-radius: 25px;
            font-weight: 700; font-size: 1rem; margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }}

        @keyframes pulse {{
            0%, 100% {{ box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.4); }}
            50% {{ box-shadow: 0 0 0 15px rgba(255, 107, 53, 0); }}
        }}

        .coming-soon h2 {{
            font-size: 2rem; color: var(--jaguar-gold);
            margin-bottom: 1rem; font-weight: 800;
        }}

        .coming-soon p {{
            color: var(--text-light); font-size: 1.1rem;
            max-width: 600px; margin: 0 auto 2rem; line-height: 1.7;
        }}

        /* What You'll Learn */
        .learn-list {{
            text-align: left; max-width: 600px; margin: 0 auto 2rem;
            list-style: none;
        }}

        .learn-list li {{
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; align-items: flex-start; gap: 0.8rem;
            color: var(--text-light); font-size: 1rem;
        }}

        .learn-list li::before {{
            content: '\u2713';
            color: var(--level-accent); font-weight: 700;
            font-size: 1.2rem; flex-shrink: 0;
        }}

        /* CTA Section */
        .cta-section {{
            text-align: center;
            background: linear-gradient(135deg, rgba(249, 202, 36, 0.1), rgba(255, 107, 53, 0.1));
            border: 2px solid var(--jaguar-gold);
            border-radius: var(--border-radius);
            padding: 3rem;
            margin-bottom: 2rem;
        }}

        .cta-section h2 {{
            font-size: 2rem; color: var(--jaguar-gold);
            margin-bottom: 1rem; font-weight: 800;
        }}

        .cta-section p {{
            color: var(--text-light); margin-bottom: 2rem;
            font-size: 1.1rem;
        }}

        .cta-buttons {{
            display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
        }}

        .btn-primary {{
            background: linear-gradient(135deg, var(--jaguar-orange), var(--jaguar-gold));
            color: white; border: none;
            padding: 1rem 2rem; border-radius: 50px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer;
            transition: var(--transition); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
            box-shadow: 0 8px 32px rgba(249, 202, 36, 0.3);
        }}

        .btn-primary:hover {{
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(255, 107, 53, 0.4);
        }}

        .btn-secondary {{
            background: transparent; color: var(--jaguar-gold);
            border: 2px solid var(--jaguar-gold);
            padding: 1rem 2rem; border-radius: 50px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer;
            transition: var(--transition); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }}

        .btn-secondary:hover {{
            background: var(--jaguar-gold); color: var(--level-dark);
            transform: translateY(-3px);
        }}
{generate_footer_css()}

        /* Responsive */
        @media (max-width: 768px) {{
            .nav-actions {{ display: none; }}
            .mobile-menu-toggle {{ display: flex; }}
            .main-content {{ padding: 100px 1rem 1rem; }}
            .hero-section {{ padding: 2rem; }}
            .hero-title {{ font-size: 2.2rem; }}
            .jaguar-hero {{ width: 160px; height: 160px; }}
            .subpage-grid {{ grid-template-columns: 1fr; }}
            .region-details {{ grid-template-columns: 1fr; }}
            .cta-buttons {{ flex-direction: column; align-items: center; }}
            .btn-primary, .btn-secondary {{ width: 100%; max-width: 280px; justify-content: center; }}
        }}

        @media (max-width: 480px) {{
            .hero-title {{ font-size: 1.8rem; }}
            .hero-section, .region-info, .coming-soon, .cta-section {{ padding: 1.5rem; }}
        }}

        @media (prefers-reduced-motion: reduce) {{
            *, *::before, *::after {{
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }}
        }}
    </style>
</head>
<body>
    <!-- Floating Elements -->
    <div class="floating-elements" id="floatingElements"></div>

    <!-- Navigation -->
    <nav class="nav-bar" role="navigation">
        <a href="index.html" class="nav-brand">
            <div class="logo">
                {JAGUAR_SVG_SMALL}
            </div>
            <div class="brand-text">
                <span>El viaje del jaguar</span>
                <p>Total Spanish immersion</p>
            </div>
        </a>

        <div class="nav-actions">
            <a href="index.html" class="nav-link">&larr; Home</a>
            <a href="index.html#precios" class="nav-link">Pricing</a>
            <a href="login.html" class="nav-link">Student Login</a>
            <a href="index.html#contacto" class="nav-cta">Start Learning</a>
        </div>

        <div class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle mobile menu">
            <div class="menu-bar"></div>
            <div class="menu-bar"></div>
            <div class="menu-bar"></div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.html" class="mobile-menu-item">&larr; Home</a>
        <a href="index.html#precios" class="mobile-menu-item">Pricing</a>
        <a href="index.html#contacto" class="mobile-menu-item">Contact</a>
        <a href="login.html" class="mobile-menu-item">Student Login</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-inner">
                <span class="level-badge">{d["level_label"]}</span>
                <div class="jaguar-hero">
                    {JAGUAR_SVG}
                </div>
                <h1 class="hero-title">{d["title"]}</h1>
                <p class="hero-subtitle">{d["level"]} {d["level_name"]} &mdash; Espa&ntilde;ol con Yaguar&aacute;</p>
                <div class="region-badge">{d["region"]} &bull; {d["city"]}</div>
            </div>
        </section>

        <!-- Region Info -->
        <section class="region-info">
            <h2>Explore: {d["region"]}</h2>
            <p style="text-align: center; color: var(--text-light); margin-bottom: 1.5rem; font-style: italic;">
                Ecosystem: {d["ecosystem"]}
            </p>
            <div class="region-details">
                <div class="detail-card">
                    <h3>Species You'll Discover</h3>
{species_html}
                </div>
                <div class="detail-card">
                    <h3>Cultural Elements</h3>
{cultural_html}
                </div>
            </div>
        </section>

{content_section}

        <!-- CTA Section -->
        <section class="cta-section">
            <h2>Ready to start your adventure?</h2>
            <p>Join thousands of students exploring Colombia while learning Spanish</p>
            <div class="cta-buttons">
                <a href="index.html#precios" class="btn-primary">View Pricing</a>
                <a href="mailto:info@babelfree.com" class="btn-secondary">Contact Us</a>
            </div>
        </section>
    </div>

{FOOTER_HTML}

    <script>
        // Floating elements
        (function() {{
            var elements = [{floating_js}];
            var container = document.getElementById('floatingElements');

            function createEl() {{
                var el = document.createElement('div');
                el.className = 'float-el';
                el.textContent = elements[Math.floor(Math.random() * elements.length)];
                el.style.left = Math.random() * 100 + 'vw';
                el.style.animationDuration = (15 + Math.random() * 10) + 's';
                el.style.fontSize = (1.2 + Math.random() * 0.8) + 'em';
                el.style.animationDelay = Math.random() * 2 + 's';
                container.appendChild(el);
                setTimeout(function() {{
                    if (container.contains(el)) container.removeChild(el);
                }}, 25000);
            }}

            for (var i = 0; i < 6; i++) {{
                setTimeout(createEl, i * 800);
            }}
            setInterval(function() {{
                if (Math.random() < 0.4) createEl();
            }}, 4000);
        }})();

        // Mobile menu
        function toggleMobileMenu() {{
            var menu = document.getElementById('mobileMenu');
            var toggle = document.querySelector('.mobile-menu-toggle');
            var bars = toggle.querySelectorAll('.menu-bar');
            menu.classList.toggle('active');
            if (menu.classList.contains('active')) {{
                bars[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                bars[1].style.opacity = '0';
                bars[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            }} else {{
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }}
        }}

        document.addEventListener('click', function(e) {{
            var menu = document.getElementById('mobileMenu');
            var toggle = document.querySelector('.mobile-menu-toggle');
            if (menu.classList.contains('active') && !menu.contains(e.target) && !toggle.contains(e.target)) {{
                menu.classList.remove('active');
                var bars = toggle.querySelectorAll('.menu-bar');
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }}
        }});
    </script>
    <script>if('serviceWorker' in navigator){{navigator.serviceWorker.register('/service-worker.js');}}</script>
</body>
</html>'''

    return html


def generate_a1_content(d):
    """Generate A1-specific content with sub-page cards."""
    cards = ""
    for sp in d["sub_pages"]:
        cards += f'''        <a href="{sp["url"]}" class="subpage-card">
            <span class="subpage-icon">{sp["icon"]}</span>
            <div class="subpage-title">{sp["title"]}</div>
            <div class="subpage-subtitle">{sp["subtitle"]}</div>
            <p class="subpage-desc">{sp["desc"]}</p>
            <div class="subpage-meta">
                <span class="lesson-count">{sp["lessons"]} lessons</span>
                <span class="subpage-cta">Enter &rarr;</span>
            </div>
        </a>
'''

    return f'''        <!-- A1 Sub-pages -->
        <section>
            <h2 style="text-align: center; color: var(--jaguar-gold); font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem;">
                Choose Your A1 Adventure
            </h2>
            <div class="subpage-grid">
{cards}            </div>
        </section>
'''


def generate_coming_soon_content(d):
    """Generate Coming Soon content for A2-C2 levels."""
    learn_items = ""
    if "what_youll_learn" in d:
        for item in d["what_youll_learn"]:
            learn_items += f"                <li>{item}</li>\n"

    return f'''        <!-- Coming Soon -->
        <section class="coming-soon">
            <div class="coming-soon-badge">Coming Soon</div>
            <h2>{d["title"]}</h2>
            <p>
                We're crafting an immersive {d["level"]} experience through Colombia's {d["region"]}.
                Yaguar&aacute;'s journey continues with new challenges, stories, and cultural discoveries.
            </p>
            <h3 style="color: var(--level-accent); margin-bottom: 1rem; font-size: 1.3rem;">What you'll learn:</h3>
            <ul class="learn-list">
{learn_items}            </ul>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Sign up now to be notified when this level launches!
            </p>
        </section>
'''


def main():
    output_dir = os.path.dirname(os.path.abspath(__file__))

    for level_data in LEVELS:
        filename = level_data["file"]
        filepath = os.path.join(output_dir, filename)
        html = generate_page(level_data)
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(html)
        print(f"Generated {filepath}")

    print(f"\nAll {len(LEVELS)} level pages generated successfully!")


if __name__ == "__main__":
    main()
