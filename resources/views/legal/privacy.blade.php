<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← Back to home</a>

        <h1 class="text-3xl font-bold mb-8">Privacy Policy</h1>
        <p class="text-sm text-gray-500 mb-8">Last updated: July 2026</p>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">1. Controller</h2>
                <p>The controller for data processing on this website within the meaning of the GDPR is:</p>
                <p class="mt-2">
                    <strong>wrkspc.xyz</strong> (Owner: Stephan)<br>
                    qrm.sg is a brand of wrkspc.xyz<br>
                    Email: <a href="mailto:info@qrm.sg" class="text-indigo-600 hover:underline">info@qrm.sg</a>
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">2. Personal Data Processed</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Account data:</strong> Name, email address (for registered users)</li>
                    <li><strong>Payment data:</strong> Processed exclusively via Stripe. We do not store credit card information.</li>
                    <li><strong>Scan data (pseudonymized):</strong> Each QR scan records: timestamp, device type, OS, browser, country/region/city (GeoIP), referer, language. IP addresses are <strong>never stored in plaintext</strong> — pseudonymized via HMAC-SHA-256 hash.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">3. Purposes</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Service provision:</strong> QR code creation and delivery (Art. 6(1)(b) GDPR)</li>
                    <li><strong>Analytics:</strong> Aggregated scan statistics (Art. 6(1)(f) GDPR)</li>
                    <li><strong>Abuse prevention:</strong> Rate-limiting and fair-use enforcement (Art. 6(1)(f) GDPR)</li>
                    <li><strong>Billing:</strong> Subscription management via Stripe (Art. 6(1)(b) GDPR)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">4. GeoIP Processing</h2>
                <p>Geolocation is determined via a locally operated MaxMind database. The scanner's IP is <strong>never transmitted to external services</strong>. Geo data is stored only in aggregated, anonymous form.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">5. Retention Periods</h2>
                <table class="w-full text-sm mt-2 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-b px-3 py-2 text-left">Data type</th>
                            <th class="border-b px-3 py-2 text-left">Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="px-3 py-2">Raw scans (Free)</td><td class="px-3 py-2">60 days after QR creation</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">Raw scans (Pro/Business)</td><td class="px-3 py-2">24 months after scan</td></tr>
                        <tr><td class="px-3 py-2">Aggregated statistics</td><td class="px-3 py-2">As long as QR code/account exists</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">User account</td><td class="px-3 py-2">Until deletion</td></tr>
                    </tbody>
                </table>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">6. Your Rights (GDPR)</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li>Access (Art. 15)</li>
                    <li>Rectification (Art. 16)</li>
                    <li>Erasure (Art. 17)</li>
                    <li>Restriction (Art. 18)</li>
                    <li>Data portability (Art. 20)</li>
                    <li>Objection (Art. 21)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">7. Cookies</h2>
                <p>qrm.sg uses only technically necessary cookies (session, CSRF token). No tracking or advertising cookies. The password cookie for protected QR codes is signed and expires after 60 minutes.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">8. Data Security</h2>
                <p>Passwords hashed with Argon2id. IPs pseudonymized with HMAC-SHA-256. Daily encrypted backups with 30-day retention and off-site copy.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">9. Right to Complain</h2>
                <p>You have the right to lodge a complaint with a data protection supervisory authority.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('datenschutz') }}" class="text-sm text-indigo-600 hover:underline">Deutsche Datenschutzerklärung →</a>
        </div>
    </main>
</body>
</html>
