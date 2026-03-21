<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Babel Free | El Viaje del Jaguar</title>
    <meta name="description" content="Privacy policy for Babel Free and El Viaje del Jaguar. Learn how we collect, use, and protect your personal data.">
    <link rel="canonical" href="https://babelfree.com/privacy">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://babelfree.com/privacy">
    <meta property="og:title" content="Privacy Policy — Babel Free">
    <meta property="og:description" content="Privacy policy for Babel Free. Learn how we collect, use, and protect your personal data.">
    <meta property="og:site_name" content="Babel Free">
    <meta property="og:image" content="https://babelfree.com/assets/og-babel.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Privacy Policy — Babel Free">
    <meta name="twitter:description" content="Privacy policy for Babel Free. Learn how we collect, use, and protect your personal data.">
    <meta name="twitter:image" content="https://babelfree.com/assets/og-babel.png">
    <meta name="theme-color" content="#F4A5A5">
    <link rel="icon" type="image/png" href="/assets/tower-logo.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebPage",
          "additionalType": "https://schema.org/WebPage",
          "@id": "https://babelfree.com/privacy",
          "url": "https://babelfree.com/privacy",
          "name": "Privacy Policy — Babel Free",
          "description": "Privacy policy for Babel Free and El Viaje del Jaguar. Learn how we collect, use, and protect your personal data.",
          "isPartOf": {"@id": "https://babelfree.com/#website"},
          "about": {"@id": "https://babelfree.com/#organization"},
          "inLanguage": "en",
          "datePublished": "2026-03-21",
          "dateModified": "2026-03-21",
          "publisher": {
            "@type": "Organization",
            "name": "Babel Free",
            "url": "https://babelfree.com",
            "logo": {
              "@type": "ImageObject",
              "url": "https://babelfree.com/assets/tower-logo.png"
            }
          }
        },
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Babel Free", "item": "https://babelfree.com/"},
            {"@type": "ListItem", "position": 2, "name": "Privacy Policy", "item": "https://babelfree.com/privacy"}
          ]
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lucida+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/footer.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Lucida Sans', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ===== Header ===== */
        .header {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed; top: 0; width: 100%; z-index: 1000;
            transition: all 0.3s ease;
        }
        .nav-container {
            max-width: 1200px; margin: 0 auto; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .logo-brand { display: flex; align-items: center; gap: 1rem; text-decoration: none; }
        .main-logo {
            height: 70px; border-radius: 8px; overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center;
        }
        .main-logo img { height: 100%; width: auto; object-fit: contain; max-width: none; }
        .brand-name { font-family: 'Bebas Neue', cursive; font-size: 2rem; color: #1a1a1a; letter-spacing: 1px; }
        .nav-menu { display: flex; gap: 2rem; list-style: none; align-items: center; }
        .nav-link {
            color: #1a1a1a; text-decoration: none; font-weight: 600;
            transition: color 0.3s ease; position: relative;
        }
        .nav-link:hover { color: #F4A5A5; }
        .nav-link::after {
            content: ''; position: absolute; bottom: -5px; left: 0;
            width: 0; height: 2px; background: #F4A5A5; transition: width 0.3s ease;
        }
        .nav-link:hover::after { width: 100%; }
        .nav-link.active { color: #F4A5A5; }
        .nav-link.active::after { width: 100%; }
        .cta-nav {
            background: #F4A5A5; color: white; padding: 0.75rem 1.5rem;
            border-radius: 25px; text-decoration: none; font-weight: 600;
            transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(244,165,165,0.3);
        }
        .cta-nav:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(244,165,165,0.4); }

        /* Mobile menu */
        .mobile-menu-toggle { display: none; flex-direction: column; cursor: pointer; padding: 0.5rem; }
        .menu-bar { width: 25px; height: 3px; background: #1a1a1a; margin: 3px 0; transition: 0.3s; border-radius: 2px; }
        .mobile-menu {
            display: none; position: fixed; top: 100px; left: 0; width: 100%;
            background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 2rem; z-index: 999;
        }
        .mobile-menu.active { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .mobile-menu a {
            display: block; color: #1a1a1a; text-decoration: none;
            padding: 1rem 0; border-bottom: 1px solid #ecf0f1; font-weight: 600;
        }
        .mobile-menu a:hover { color: #F4A5A5; }

        /* ===== Hero ===== */
        .privacy-hero {
            padding: 8rem 2rem 3rem;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .privacy-hero::after {
            content: ''; position: absolute; top: -50%; right: -20%;
            width: 600px; height: 600px; border-radius: 50%;
            background: radial-gradient(circle, rgba(244,165,165,0.15) 0%, transparent 70%);
            z-index: 1; pointer-events: none;
        }
        .privacy-hero-content { position: relative; z-index: 2; }
        .privacy-hero h1 {
            font-family: 'Bebas Neue', cursive; font-size: 3.5rem;
            color: #fff; letter-spacing: 3px; margin-bottom: 0.5rem;
        }
        .privacy-hero .subtitle {
            font-size: 1.05rem; color: rgba(255,255,255,0.7); line-height: 1.7;
            max-width: 550px; margin: 0 auto;
        }
        .effective-date {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.4rem 1.2rem;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            color: rgba(255,255,255,0.6);
            font-size: 0.88rem;
        }

        /* ===== Policy Content ===== */
        .privacy-content {
            flex: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .policy-section {
            margin-bottom: 2.5rem;
        }

        .policy-section h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.7rem;
            letter-spacing: 2px;
            color: #1a1a2e;
            margin-bottom: 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 2px solid #F4A5A5;
            display: inline-block;
        }

        .policy-section h3 {
            font-size: 1.05rem;
            color: #1a1a2e;
            margin: 1.25rem 0 0.4rem;
            font-weight: 700;
        }

        .policy-section p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 0.75rem;
            font-size: 0.97rem;
        }

        .policy-section ul {
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .policy-section li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 0.35rem;
            font-size: 0.97rem;
        }

        .policy-section li strong {
            color: #1a1a2e;
        }

        .policy-section a {
            color: #F4A5A5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .policy-section a:hover {
            color: #e08080;
        }

        /* Highlight callout */
        .policy-callout {
            background: linear-gradient(135deg, rgba(244,165,165,0.08), rgba(244,165,165,0.03));
            border-left: 4px solid #F4A5A5;
            padding: 1rem 1.3rem;
            border-radius: 0 8px 8px 0;
            margin: 1rem 0;
        }
        .policy-callout p {
            color: #444;
            margin: 0;
            font-weight: 500;
        }

        /* Table of contents */
        .policy-toc {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
        }
        .policy-toc h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.3rem;
            letter-spacing: 2px;
            color: #1a1a2e;
            margin-bottom: 0.75rem;
            border: none;
            padding: 0;
            display: block;
        }
        .policy-toc ol {
            padding-left: 1.5rem;
            columns: 2;
            column-gap: 2rem;
        }
        .policy-toc li {
            font-size: 0.92rem;
            line-height: 1.8;
            color: #555;
        }
        .policy-toc a {
            color: #555;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .policy-toc a:hover {
            color: #F4A5A5;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .mobile-menu-toggle { display: flex; }
            .privacy-hero h1 { font-size: 2.5rem; }
            .privacy-hero .subtitle { font-size: 0.95rem; }
            .privacy-content { padding: 2rem 1.25rem; }
            .policy-toc ol { columns: 1; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <a href="/" class="logo-brand">
                <div class="main-logo">
                    <img src="/assets/logo.png" alt="Babel Free Logo">
                </div>
            </a>

            <ul class="nav-menu">
                <li><a href="/" class="nav-link">Home</a></li>
                <li><a href="/services" class="nav-link">Services</a></li>
                <li><a href="/blog" class="nav-link">Blog</a></li>
                <li><a href="/dictionary" class="nav-link">Dictionaries</a></li>
                <li><a href="/contact" class="nav-link">Contact</a></li>
                <li><a href="/elviajedeljaguar" class="cta-nav">Spanish Course</a></li>
            </ul>

            <div class="mobile-menu-toggle" onclick="document.getElementById('mobileMenu').classList.toggle('active')">
                <div class="menu-bar"></div>
                <div class="menu-bar"></div>
                <div class="menu-bar"></div>
            </div>
        </nav>
    </header>
    <div class="mobile-menu" id="mobileMenu">
        <a href="/">Home</a>
        <a href="/services">Services</a>
        <a href="/blog">Blog</a>
        <a href="/dictionary">Dictionaries</a>
        <a href="/contact">Contact</a>
        <a href="/elviajedeljaguar">Spanish Course</a>
    </div>

    <!-- Hero -->
    <section class="privacy-hero">
        <div class="privacy-hero-content">
            <h1>Privacy Policy</h1>
            <p class="subtitle">How Babel Free collects, uses, and protects your personal data</p>
            <div class="effective-date">Effective date: March 21, 2026</div>
        </div>
    </section>

    <!-- Policy Content -->
    <main class="privacy-content">

        <nav class="policy-toc">
            <h2>Contents</h2>
            <ol>
                <li><a href="#data-collection">Data collection</a></li>
                <li><a href="#purpose">Purpose of data use</a></li>
                <li><a href="#storage-security">Storage and security</a></li>
                <li><a href="#cookies">Cookies</a></li>
                <li><a href="#third-parties">Third parties</a></li>
                <li><a href="#user-rights">Your rights</a></li>
                <li><a href="#marketing">Marketing communications</a></li>
                <li><a href="#data-retention">Data retention</a></li>
                <li><a href="#children">Children and minors</a></li>
                <li><a href="#contact">Contact us</a></li>
                <li><a href="#updates">Policy updates</a></li>
            </ol>
        </nav>

        <!-- 1. Data Collection -->
        <section class="policy-section" id="data-collection">
            <h2>1. Data Collection</h2>
            <p>When you create a Babel Free account or use our services, we may collect the following information:</p>
            <ul>
                <li><strong>Account information</strong> &mdash; Name, email address, and a password (which is stored only in hashed form, never as plain text)</li>
                <li><strong>Language preferences</strong> &mdash; Your native language and target language(s), used to personalize your learning experience</li>
                <li><strong>Date of birth</strong> &mdash; Used to verify age eligibility and to tailor content appropriateness</li>
                <li><strong>Country</strong> &mdash; Used for regional content adaptation and language variant selection (e.g., Latin American vs. Castilian Spanish)</li>
                <li><strong>Phone number</strong> (optional) &mdash; Provided only if you choose to add it for account recovery purposes</li>
                <li><strong>Learning progress</strong> &mdash; CEFR level, completed destinations, vocabulary mastery data, game scores, and personal lexicon entries</li>
                <li><strong>Feedback and forum contributions</strong> &mdash; Any content you submit through our feedback widget or grammar forum</li>
            </ul>
            <p>We do not collect data beyond what is necessary to provide and improve our language learning services.</p>
        </section>

        <!-- 2. Purpose -->
        <section class="policy-section" id="purpose">
            <h2>2. Purpose of Data Use</h2>
            <p>We use the data we collect for the following purposes:</p>
            <ul>
                <li><strong>Language learning personalization</strong> &mdash; Adapting content to your CEFR level, native language, and learning history. At A1&ndash;A2 Basic, instruction appears in your native language; from A2 Advanced onward, the course transitions to full Spanish immersion.</li>
                <li><strong>Progress tracking</strong> &mdash; Recording which destinations you have completed, which vocabulary you have mastered, and which areas need further practice.</li>
                <li><strong>CEFR level adaptation</strong> &mdash; Automatically adjusting the difficulty and scaffolding of activities based on your demonstrated proficiency.</li>
                <li><strong>Service improvement</strong> &mdash; Analyzing aggregated, anonymized usage patterns to improve our content, fix errors, and develop new features.</li>
                <li><strong>Communication</strong> &mdash; Responding to your feedback, support requests, and forum posts.</li>
                <li><strong>Account management</strong> &mdash; Authenticating your identity, enabling password recovery, and maintaining your account.</li>
            </ul>
        </section>

        <!-- 3. Storage & Security -->
        <section class="policy-section" id="storage-security">
            <h2>3. Storage and Security</h2>
            <p>We take the security of your data seriously and implement the following measures:</p>
            <ul>
                <li><strong>Encryption in transit</strong> &mdash; All data transmitted between your browser and our servers is encrypted using HTTPS (TLS/SSL).</li>
                <li><strong>Password hashing</strong> &mdash; Passwords are hashed using industry-standard algorithms before storage. We never store passwords in plain text, and our staff cannot view your password.</li>
                <li><strong>Access controls</strong> &mdash; Access to personal data is restricted to authorized personnel who need it to operate, develop, or improve our services.</li>
                <li><strong>Regular security audits</strong> &mdash; We conduct periodic security reviews of our systems and codebase.</li>
                <li><strong>Server security</strong> &mdash; Our servers are maintained with current security patches and monitored for unauthorized access.</li>
            </ul>
            <p>While no system can guarantee absolute security, we are committed to protecting your data using reasonable and appropriate technical and organizational measures.</p>
        </section>

        <!-- 4. Cookies -->
        <section class="policy-section" id="cookies">
            <h2>4. Cookies</h2>
            <p>Babel Free uses cookies in a limited and transparent manner:</p>
            <ul>
                <li><strong>Session cookies</strong> &mdash; Essential cookies used for authentication and maintaining your logged-in state. These expire when you close your browser or after a reasonable session timeout.</li>
                <li><strong>Preference cookies</strong> &mdash; Used to remember your language preference and display settings (e.g., English or Spanish interface). These are stored in your browser&rsquo;s localStorage.</li>
            </ul>
            <div class="policy-callout">
                <p>We do not use third-party tracking cookies. We do not use cookies for behavioral advertising, cross-site tracking, or profiling.</p>
            </div>
            <p>If our site displays advertisements, any ad-related cookies are managed by the ad network and are subject to their own privacy policies. We do not share your personal account data with advertisers.</p>
        </section>

        <!-- 5. Third Parties -->
        <section class="policy-section" id="third-parties">
            <h2>5. Third Parties</h2>
            <div class="policy-callout">
                <p>We do not sell, rent, or trade your personal data to any third party. Full stop.</p>
            </div>
            <p>We may share limited, anonymized data with the following categories of service providers, solely to operate our platform:</p>
            <ul>
                <li><strong>Hosting providers</strong> &mdash; For server infrastructure and data storage</li>
                <li><strong>Email services</strong> &mdash; For sending account-related communications (password resets, feedback responses)</li>
            </ul>
            <p>Any service providers we work with are contractually required to protect your data and use it only for the purposes we specify.</p>
        </section>

        <!-- 6. User Rights -->
        <section class="policy-section" id="user-rights">
            <h2>6. Your Rights</h2>
            <p>In compliance with the General Data Protection Regulation (GDPR) and similar data protection laws, you have the following rights regarding your personal data:</p>
            <ul>
                <li><strong>Right of access</strong> &mdash; You can request a copy of all personal data we hold about you.</li>
                <li><strong>Right to rectification</strong> &mdash; You can request correction of any inaccurate or incomplete data.</li>
                <li><strong>Right to erasure</strong> &mdash; You can request deletion of your personal data (&ldquo;right to be forgotten&rdquo;).</li>
                <li><strong>Right to restriction</strong> &mdash; You can request that we limit the processing of your data.</li>
                <li><strong>Right to data portability</strong> &mdash; You can request your data in a structured, commonly used, machine-readable format.</li>
                <li><strong>Right to object</strong> &mdash; You can object to the processing of your data for specific purposes.</li>
                <li><strong>Right to withdraw consent</strong> &mdash; Where processing is based on consent, you can withdraw that consent at any time.</li>
            </ul>
            <p>To exercise any of these rights, please contact us at <a href="mailto:privacy@babelfree.com">privacy@babelfree.com</a>. We will respond to your request within 30 days.</p>
        </section>

        <!-- 7. Marketing -->
        <section class="policy-section" id="marketing">
            <h2>7. Marketing Communications</h2>
            <p>We respect your inbox. Our approach to marketing communications is straightforward:</p>
            <ul>
                <li><strong>Opt-in only</strong> &mdash; We will only send you marketing emails if you have explicitly opted in to receive them.</li>
                <li><strong>Easy unsubscribe</strong> &mdash; Every marketing email includes a clear unsubscribe link. You can opt out at any time with a single click.</li>
                <li><strong>Transactional emails</strong> &mdash; Account-related emails (password resets, security alerts, direct replies to your feedback) are not marketing and will be sent as needed regardless of your marketing preferences.</li>
            </ul>
        </section>

        <!-- 8. Data Retention -->
        <section class="policy-section" id="data-retention">
            <h2>8. Data Retention</h2>
            <p>We retain your personal data as follows:</p>
            <ul>
                <li><strong>Active accounts</strong> &mdash; Your data is kept for as long as your account remains active. This includes your learning progress, personal lexicon, and all associated data.</li>
                <li><strong>Inactive accounts</strong> &mdash; If your account remains inactive for an extended period, we may contact you before taking any action on your data.</li>
                <li><strong>Deletion requests</strong> &mdash; When you request account deletion, we will remove your personal data within 30 days. Some anonymized, aggregated data may be retained for service improvement purposes.</li>
                <li><strong>Forum contributions</strong> &mdash; Public forum posts may be retained after account deletion in an anonymized form to preserve the integrity of community discussions.</li>
            </ul>
        </section>

        <!-- 9. Children -->
        <section class="policy-section" id="children">
            <h2>9. Children and Minors</h2>
            <p>Babel Free and El Viaje del Jaguar are intended for users aged <strong>13 and older</strong>.</p>
            <ul>
                <li>We do not knowingly collect personal data from children under the age of 13.</li>
                <li>Users between the ages of 13 and 16 (or the applicable age of consent in their jurisdiction) should have parental or guardian consent before creating an account.</li>
                <li>If we become aware that we have collected data from a child under 13 without parental consent, we will delete that data promptly.</li>
                <li>Parents or guardians who believe their child has provided personal data without consent should contact us at <a href="mailto:privacy@babelfree.com">privacy@babelfree.com</a>.</li>
            </ul>
            <p>Our course content is designed to be appropriate for all ages, but the account and data collection features are intended for users who meet the minimum age requirement.</p>
        </section>

        <!-- 10. Contact -->
        <section class="policy-section" id="contact">
            <h2>10. Contact Us</h2>
            <p>If you have questions about this privacy policy, want to exercise your data rights, or have concerns about how your data is handled, you can reach us through:</p>
            <ul>
                <li><strong>Privacy inquiries</strong>: <a href="mailto:privacy@babelfree.com">privacy@babelfree.com</a></li>
                <li><strong>General contact</strong>: <a href="mailto:info@babelfree.com">info@babelfree.com</a></li>
                <li><strong>Contact form</strong>: <a href="/contact">babelfree.com/contact</a></li>
            </ul>
            <p>We aim to respond to all privacy-related inquiries within 30 days.</p>
        </section>

        <!-- 11. Updates -->
        <section class="policy-section" id="updates">
            <h2>11. Policy Updates</h2>
            <p>We may update this privacy policy from time to time to reflect changes in our practices, technologies, legal requirements, or other factors.</p>
            <ul>
                <li>When we make material changes, we will notify registered users by email and/or by displaying a notice on our website.</li>
                <li>The &ldquo;Effective date&rdquo; at the top of this page indicates when the policy was last revised.</li>
                <li>We encourage you to review this policy periodically to stay informed about how we protect your data.</li>
                <li>Continued use of Babel Free after a policy update constitutes acceptance of the revised terms.</li>
            </ul>
            <p>If you disagree with any changes to this policy, you may close your account and request deletion of your data at any time.</p>
        </section>

    </main>

    <!-- Footer -->
    <footer class="site-footer light">
        <div class="footer-grid">
            <div class="footer-brand">
                <p class="footer-logo"><img src="/assets/tower-logo.png" alt="Babel Free" loading="lazy"></p>
                <p class="footer-desc">Language courses, professional translation, and immersive Spanish learning through El Viaje del Jaguar &mdash; a CEFR-aligned journey powered by Colombian culture and storytelling.</p>
            </div>
            <nav class="footer-links" aria-label="Site map">
                <p class="footer-heading">Explore</p>
                <a href="/services">Language courses</a>
                <a href="/elviajedeljaguar">El Viaje del Jaguar</a>
                <a href="/dictionary">Dictionaries</a>
                <a href="/languages">100+ languages</a>
                <a href="/blog">Blog</a>
            </nav>
            <div class="footer-contact">
                <p class="footer-heading">Contact us</p>
                <a href="/translation-services" class="footer-cta">Translation services</a>
                <a href="/contact" class="footer-cta">Message form</a>
                <a href="mailto:info@babelfree.com" class="footer-cta-secondary">Email</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Babel Free &middot; <a href="/privacy">Privacy</a> &middot; <a href="/about">About</a></p>
        </div>
    </footer>
</body>
</html>