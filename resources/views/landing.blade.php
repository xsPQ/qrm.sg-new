<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>qrm.sg — QR-Codes dynamisch, messbar, kontrollierbar</title>
    <meta name="description" content="Erstelle dynamische QR-Codes mit Analytics, Ablaufdaten und Passwortschutz. Ändere Inhalte jederzeit — ohne neu drucken.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --qr-primary: #6366f1;
            --qr-primary-dark: #4f46e5;
            --qr-dark: #0f172a;
            --qr-gray: #64748b;
            --qr-light: #f8fafc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Figtree', system-ui, sans-serif;
            color: var(--qr-dark);
            background: var(--qr-light);
        }
        /* Nav */
        .nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem; background: white; border-bottom: 1px solid #e2e8f0;
            position: sticky; top: 0; z-index: 50;
        }
        .nav-logo {
            font-size: 1.5rem; font-weight: 700; color: var(--qr-primary);
            text-decoration: none; display: flex; align-items: center; gap: 0.5rem;
        }
        .nav-logo svg { width: 28px; height: 28px; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a {
            text-decoration: none; color: var(--qr-gray); font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--qr-primary); }
        .btn-primary {
            background: var(--qr-primary); color: white; padding: 0.6rem 1.5rem;
            border-radius: 0.5rem; font-weight: 600; transition: background 0.2s;
        }
        .btn-primary:hover { background: var(--qr-primary-dark); color: white; }
        .btn-outline {
            border: 2px solid var(--qr-primary); color: var(--qr-primary);
            padding: 0.5rem 1.5rem; border-radius: 0.5rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-outline:hover { background: var(--qr-primary); color: white; }
        /* Hero */
        .hero {
            text-align: center; padding: 5rem 2rem 4rem;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
        }
        .hero h1 {
            font-size: 3rem; font-weight: 700; line-height: 1.2;
            max-width: 700px; margin: 0 auto 1.5rem;
        }
        .hero h1 span { color: var(--qr-primary); }
        .hero p {
            font-size: 1.25rem; color: var(--qr-gray);
            max-width: 600px; margin: 0 auto 2.5rem;
        }
        .hero-cta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .hero-cta .btn-primary { font-size: 1.1rem; padding: 0.8rem 2rem; }
        .hero-cta .btn-outline { font-size: 1.1rem; padding: 0.7rem 2rem; }
        /* QR Types */
        .types {
            padding: 4rem 2rem; max-width: 1200px; margin: 0 auto;
        }
        .section-title {
            text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;
        }
        .section-sub {
            text-align: center; color: var(--qr-gray); font-size: 1.1rem; margin-bottom: 3rem;
        }
        .types-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .type-card {
            background: white; border-radius: 1rem; padding: 2rem;
            border: 1px solid #e2e8f0; transition: all 0.3s;
        }
        .type-card:hover {
            border-color: var(--qr-primary); box-shadow: 0 8px 30px rgba(99,102,241,0.1);
            transform: translateY(-4px);
        }
        .type-icon {
            width: 48px; height: 48px; border-radius: 0.75rem;
            background: #eef2ff; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1rem;
        }
        .type-card h3 { font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem; }
        .type-card p { color: var(--qr-gray); font-size: 0.95rem; }
        /* Features */
        .features {
            background: white; padding: 4rem 2rem;
        }
        .features-inner { max-width: 1200px; margin: 0 auto; }
        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .feature-item {
            display: flex; gap: 1rem; align-items: flex-start;
        }
        .feature-check {
            width: 24px; height: 24px; min-width: 24px;
            background: var(--qr-primary); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.8rem; margin-top: 2px;
        }
        .feature-item h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem; }
        .feature-item p { color: var(--qr-gray); font-size: 0.95rem; }
        /* Pricing */
        .pricing {
            padding: 4rem 2rem; max-width: 1200px; margin: 0 auto;
        }
        .pricing-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem; margin-top: 3rem;
        }
        .price-card {
            background: white; border-radius: 1rem; padding: 2.5rem;
            border: 2px solid #e2e8f0; text-align: center;
        }
        .price-card.featured { border-color: var(--qr-primary); position: relative; }
        .price-card.featured::after {
            content: 'Beliebt'; position: absolute; top: -12px; left: 50%;
            transform: translateX(-50%); background: var(--qr-primary); color: white;
            padding: 0.25rem 1rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;
        }
        .price-name { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; }
        .price-amount { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .price-period { color: var(--qr-gray); margin-bottom: 1.5rem; }
        .price-features { list-style: none; text-align: left; margin-bottom: 2rem; }
        .price-features li {
            padding: 0.5rem 0; color: var(--qr-gray); font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .price-features li::before { content: '✓ '; color: var(--qr-primary); font-weight: 700; }
        /* Footer */
        .footer {
            background: var(--qr-dark); color: #94a3b8; padding: 3rem 2rem;
        }
        .footer-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: 2rem;
        }
        .footer-brand {
            font-size: 1.3rem; font-weight: 700; color: white; margin-bottom: 0.5rem;
        }
        .footer-links { display: flex; gap: 2rem; flex-wrap: wrap; }
        .footer-links a { color: #94a3b8; text-decoration: none; transition: color 0.2s; }
        .footer-links a:hover { color: white; }
        @media (max-width: 640px) {
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1.1rem; }
            .nav { padding: 0.75rem 1rem; }
            .nav-links { gap: 1rem; }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <a href="/" class="nav-logo">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="5" y="5" width="90" height="90" rx="12" fill="#6366f1"/>
                <rect x="15" y="15" width="30" height="30" rx="4" fill="white"/>
                <rect x="55" y="15" width="30" height="30" rx="4" fill="white"/>
                <rect x="15" y="55" width="30" height="30" rx="4" fill="white"/>
                <rect x="60" y="60" width="20" height="20" rx="3" fill="white"/>
            </svg>
            qrm.sg
        </a>
        <div class="nav-links">
            <a href="#features">Funktionen</a>
            <a href="#types">QR-Typen</a>
            <a href="#pricing">Preise</a>
            <a href="{{ route('qr.create-anonymous') }}">Try Now</a>
            <a href="{{ route('register') }}" class="btn-primary">Sign Up Free</a>
        </div>
    </nav>

    <section class="hero">
        <h1>QR-Codes <span>dynamisch</span>, messbar und kontrollierbar</h1>
        <p>Erstelle QR-Codes in Sekunden, ändere Inhalte jederzeit ohne neu zu drucken, tracke Scans in Echtzeit und setze Ablaufdaten — alles in einem Dashboard.</p>
        <div class="hero-cta">
            <a href="{{ route('qr.create-anonymous') }}" class="btn-primary">{{ __('Try Now — No Sign-Up →') }}</a>
            <a href="{{ route('login') }}" class="btn-outline">{{ __('Login') }}</a>
        </div>
    </section>

    <section class="types" id="types">
        <h2 class="section-title">8 QR-Code-Typen</h2>
        <p class="section-sub">Für jeden Anwendungsfall der richtige QR-Code</p>
        <div class="types-grid">
            <div class="type-card"><div class="type-icon">💬</div><h3>Message</h3><p>Freitext-Nachrichten hinter dem QR-Code</p></div>
            <div class="type-card"><div class="type-icon">🔗</div><h3>URL</h3><p>Weiterleitung zu jeder Website</p></div>
            <div class="type-card"><div class="type-icon">📶</div><h3>WiFi</h3><p>WLAN-Zugangsdaten teilen</p></div>
            <div class="type-card"><div class="type-icon">₿</div><h3>Crypto</h3><p>Krypto-Zahlungen empfangen</p></div>
            <div class="type-card"><div class="type-icon">👥</div><h3>Social</h3><p>Social-Media-Profile verlinken</p></div>
            <div class="type-card"><div class="type-icon">↗️</div><h3>Redirect</h3><p>HTTP-Weiterleitung (301/302)</p></div>
            <div class="type-card"><div class="type-icon">📅</div><h3>Event</h3><p>Kalendereinträge mit ICS-Download</p></div>
            <div class="type-card"><div class="type-icon">👤</div><h3>Contact</h3><p>Kontaktdaten als vCard</p></div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="features-inner">
            <h2 class="section-title">Alles unter Kontrolle</h2>
            <p class="section-sub">Mehr als nur ein QR-Code-Generator</p>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>Inhalte ändern ohne neu drucken</h3><p>QR-Code bleibt gleich, Inhalt ändert sich im Dashboard</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>Echtzeit-Analytics</h3><p>Scans, Länder, Geräte, Zeitverläufe — live</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>Ablaufdaten & Limits</h3><p>Automatischer Ablauf oder maximale Scan-Anzahl festlegen</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>Passwortschutz</h3><p>Schütze sensible Inhalte mit einem Passwort (Pro/Business)</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>Custom Alias</h3><p>Merkbare Kurzlinks wie qrm.sg/mein-link</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-check">✓</div>
                    <div><h3>SVG & PNG Download</h3><p>Druckfertige QR-Codes in höchster Qualität</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing" id="pricing">
        <h2 class="section-title">Einfache Preise</h2>
        <p class="section-sub">Kostenlos starten, bei Bedarf upgraden</p>
        <div class="pricing-grid">
            <div class="price-card">
                <div class="price-name">Free</div>
                <div class="price-amount">0 €</div>
                <div class="price-period">für immer</div>
                <ul class="price-features">
                    <li>10 aktive QR-Codes</li>
                    <li>30 Tage Gültigkeit</li>
                    <li>Alle 8 QR-Typen</li>
                    <li>SVG + PNG Download</li>
                    <li>Grundlegende Analytics</li>
                </ul>
                <a href="{{ route('register') }}" class="btn-outline">Kostenlos starten</a>
            </div>
            <div class="price-card featured">
                <div class="price-name">Pro</div>
                <div class="price-amount">5 €</div>
                <div class="price-period">pro Monat</div>
                <ul class="price-features">
                    <li>Unbegrenzte QR-Codes</li>
                    <li>Kein Ablauf</li>
                    <li>Vollständige Analytics</li>
                    <li>Custom Alias</li>
                    <li>Passwortschutz</li>
                    <li>Kein Branding</li>
                </ul>
                <a href="{{ route('register') }}" class="btn-primary">Pro wählen →</a>
            </div>
            <div class="price-card">
                <div class="price-name">Business</div>
                <div class="price-amount">19 €</div>
                <div class="price-period">pro Monat</div>
                <ul class="price-features">
                    <li>Alles aus Pro</li>
                    <li>Eigene Domain</li>
                    <li>REST API</li>
                    <li>Team-Verwaltung</li>
                    <li>White-Label</li>
                    <li>Bulk-Import/Export</li>
                </ul>
                <a href="{{ route('register') }}" class="btn-outline">Business wählen</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-inner">
            <div>
                <div class="footer-brand">qrm.sg</div>
                <p>QR-Codes dynamisch, messbar, kontrollierbar.</p>
            </div>
            <div class="footer-links">
                <a href="{{ route('qr.create-anonymous') }}">Try Now</a>
                <a href="{{ route('register') }}">Sign Up</a>
                <a href="#features">Funktionen</a>
                <a href="#pricing">Preise</a>
            </div>
        </div>
        <div style="text-align:center; padding-top:2rem; margin-top:2rem; border-top: 1px solid #1e293b;">
            <p>&copy; {{ date('Y') }} qrm.sg — Ein Produkt von wrkspc.xyz</p>
        </div>
    </footer>
</body>
</html>