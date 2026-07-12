<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Datenschutzerklärung — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← {{ __('Zurück zur Startseite') }}</a>

        <h1 class="text-3xl font-bold mb-8">Datenschutzerklärung</h1>
        <p class="text-sm text-gray-500 mb-8">Stand: Juli 2026</p>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">1. Verantwortlicher</h2>
                <p>Verantwortlich für die Datenverarbeitung auf dieser Website im Sinne der Datenschutz-Grundverordnung (DSGVO) ist der Betreiber von qrm.sg. Die Kontaktdaten werden vor Produktivstart im Impressum veröffentlicht.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">2. Verarbeitung personenbezogener Daten</h2>
                <p>Wir verarbeiten folgende personenbezogene Daten:</p>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Registrierungsdaten:</strong> Name, E-Mail-Adresse (bei registrierten Nutzern)</li>
                    <li><strong>Zahlungsdaten:</strong> Verarbeitung erfolgt ausschließlich über den Zahlungsdienstleister Stripe. Wir speichern keine Kreditkartendaten.</li>
                    <li><strong>Scan-Daten (pseudonymisiert):</strong> Bei jedem QR-Code-Scan erfassen wir: Zeitpunkt, Gerätetyp, Betriebssystem, Browser, Land/Region/Stadt (GeoIP), Referer und Sprache. IP-Adressen werden <strong>nicht im Klartext</strong> gespeichert, sondern als HMAC-SHA-256-Hash pseudonymisiert.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">3. Zwecke der Verarbeitung</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Service-Erbringung:</strong> Erstellung, Verwaltung und Auslieferung von QR-Codes (Art. 6 Abs. 1 lit. b DSGVO)</li>
                    <li><strong>Analytics:</strong> Aggregation von Scan-Statistiken für QR-Code-Eigentümer (Art. 6 Abs. 1 lit. f DSGVO)</li>
                    <li><strong>Missbrauchsschutz:</strong> Rate-Limiting und Fair-Use-Kontrolle (Art. 6 Abs. 1 lit. f DSGVO)</li>
                    <li><strong>Abrechnung:</strong> Abonnement-Verwaltung über Stripe (Art. 6 Abs. 1 lit. b DSGVO)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">4. GeoIP-Verarbeitung</h2>
                <p>Die Bestimmung des Landes, der Region und der Stadt erfolgt über eine lokal betriebene GeoIP-Datenbank (MaxMind). Die IP-Adresse des Scanners wird <strong>nicht an externe Dienste</strong> übermittelt. Die Geodaten werden ausschließlich in aggregierter, anonymer Form für Analytics gespeichert.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">5. Aufbewahrungsfristen</h2>
                <table class="w-full text-sm mt-2 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-b px-3 py-2 text-left">Datentyp</th>
                            <th class="border-b px-3 py-2 text-left">Aufbewahrung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="px-3 py-2">Rohscans (Free-Tier)</td><td class="px-3 py-2">60 Tage nach QR-Erstellung</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">Rohscans (Pro/Business)</td><td class="px-3 py-2">24 Monate nach Scan</td></tr>
                        <tr><td class="px-3 py-2">Aggregierte Statistiken</td><td class="px-3 py-2">Solange QR-Code/Account besteht</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">Nutzerkonto</td><td class="px-3 py-2">Bis zur Löschung</td></tr>
                    </tbody>
                </table>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">6. Ihre Rechte</h2>
                <p>Sie haben folgende Rechte nach DSGVO:</p>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li>Auskunft (Art. 15 DSGVO)</li>
                    <li>Berichtigung (Art. 16 DSGVO)</li>
                    <li>Löschung (Art. 17 DSGVO)</li>
                    <li>Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
                    <li>Datenübertragbarkeit (Art. 20 DSGVO)</li>
                    <li>Widerspruch (Art. 21 DSGVO)</li>
                </ul>
                <p class="mt-3">Zur Ausübung Ihrer Rechte kontaktieren Sie uns bitte über die im Impressum angegebene Adresse.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">7. Cookies</h2>
                <p>qrm.sg verwendet ausschließlich technisch notwendige Cookies (Session-Cookie für angemeldete Nutzer, CSRF-Token). Es werden keine Tracking- oder Werbe-Cookies gesetzt. Das Passwort-Cookie für geschützte QR-Codes ist signiert und läuft nach 60 Minuten ab.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">8. Auftragsverarbeiter</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Stripe:</strong> Zahlungsabwicklung (Art. 28 DSGVO)</li>
                    <li><strong>Hosting-Provider:</strong> Server-Infrastruktur (wird vor Launch eingetragen)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">9. Datensicherheit</h2>
                <p>Passwörter werden mit Argon2id gehasht. IP-Adressen werden mit HMAC-SHA-256 und einem serverseitigen Secret pseudonymisiert. Die Datenbank wird täglich verschlüsselt gesichert. Backups werden 30 Tage aufbewahrt mit mindestens einer externen Kopie.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">10. Beschwerderecht</h2>
                <p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde über die Verarbeitung Ihrer personenbezogenen Daten zu beschweren.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('privacy') }}" class="text-sm text-indigo-600 hover:underline">English Privacy Policy →</a>
        </div>
    </main>
</body>
</html>
