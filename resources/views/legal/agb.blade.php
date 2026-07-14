<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AGB — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← {{ __('Zurück zur Startseite') }}</a>

        <h1 class="text-3xl font-bold mb-8">Allgemeine Geschäftsbedingungen (AGB)</h1>
        <p class="text-sm text-gray-500 mb-8">Stand: Juli 2026</p>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">§1 Leistungsbeschreibung</h2>
                <p>qrm.sg ist ein Online-Dienst zur Erstellung, Verwaltung und Auswertung dynamischer QR-Codes. qrm.sg ist eine Marke der wrkspc.xyz (Inhaber: Stephan). Die Inhalte hinter den QR-Codes können jederzeit geändert werden, ohne dass der QR-Code neu gedruckt werden muss. Der Dienst umfasst Analytics-Funktionen (Scan-Statistiken), verschiedene QR-Code-Typen (URL, WiFi, vCard, Event, etc.) und A/B-Testing-Funktionen.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§2 Tarife und Preise</h2>
                <p>Es gelten die zum Zeitpunkt der Buchung auf der Website ausgewiesenen Preise. Alle Preise verstehen sich inklusive der gesetzlichen Mehrwertsteuer.</p>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Free:</strong> Kostenlos. Maximal 10 aktive QR-Codes, 30 Tage Gültigkeit, eingeschränkte Analytics.</li>
                    <li><strong>Pro:</strong> 5 €/Monat. Unbegrenzte QR-Codes (Fair-Use-Limit: 500), volle Analytics, Custom Alias ab 4 Zeichen, Passwortschutz.</li>
                    <li><strong>Business:</strong> 19 €/Monat. Höhere Limits (5.000 QR-Codes), API-Zugang, Team-Verwaltung, White-Label, Premium-Alias ab 2 Zeichen.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§3 Fair-Use-Policy</h2>
                <p>Um die Qualität des Dienstes für alle Nutzer zu gewährleisten, gelten folgende Fair-Use-Limits:</p>
                <table class="w-full text-sm mt-2 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-b border-gray-200 px-3 py-2 text-left">Limit</th>
                            <th class="border-b border-gray-200 px-3 py-2 text-right">Free</th>
                            <th class="border-b border-gray-200 px-3 py-2 text-right">Pro</th>
                            <th class="border-b border-gray-200 px-3 py-2 text-right">Business</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="px-3 py-2">Aktive QR-Codes</td><td class="px-3 py-2 text-right">10</td><td class="px-3 py-2 text-right">500</td><td class="px-3 py-2 text-right">5.000</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">Scans pro Tag (pro Code)</td><td class="px-3 py-2 text-right">—</td><td class="px-3 py-2 text-right">50.000</td><td class="px-3 py-2 text-right">500.000</td></tr>
                        <tr><td class="px-3 py-2">API-Requests/Minute</td><td class="px-3 py-2 text-right">—</td><td class="px-3 py-2 text-right">60</td><td class="px-3 py-2 text-right">300</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">A/B-Varianten pro Code</td><td class="px-3 py-2 text-right">0</td><td class="px-3 py-2 text-right">5</td><td class="px-3 py-2 text-right">20</td></tr>
                    </tbody>
                </table>
                <p class="mt-3">Bei Überschreitung der Limits behält sich qrm.sg vor, QR-Codes vorübergehend zu deaktivieren und den Nutzer zu informieren. Ein Anspruch auf Nutzung über die Fair-Use-Limits hinaus besteht nicht. Die Limitwerte können im konfigurierten Rahmen angepasst werden.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§4 Pflichten des Nutzers</h2>
                <p>Der Nutzer verpflichtet sich, qrm.sg nicht für illegale, irreführende, betrügerische oder schädliche Zwecke zu verwenden. Insbesondere ist es untersagt:</p>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li>QR-Codes zu erstellen, die auf Phishing-, Malware- oder illegale Inhalte weiterleiten</li>
                    <li>QR-Codes für Spam, Massen-Mailings oder unaufgeforderte Werbung zu verwenden</li>
                    <li>Inhalte zu speichern, die gegen geltendes Recht, Rechte Dritter oder gute Sitten verstoßen</li>
                    <li>Den Dienst durch automatisierte Massenabfragen zu überlasten</li>
                </ul>
                <p class="mt-3">Bei Verstößen ist qrm.sg berechtigt, betroffene QR-Codes sofort zu sperren und den Account vorübergehend oder dauerhaft zu deaktivieren.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§5 Zahlungsbedingungen</h2>
                <p>Abonnements (Pro, Business) werden monatlich über den Zahlungsdienstleister Stripe abgerechnet. Die Abbuchung erfolgt automatisch zum Beginn jedes Abrechnungszeitraums. Eine Kündigung ist jederzeit zum Ende der laufenden Periode möglich.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§6 Kündigung und Bestandsschutz</h2>
                <p>Eine Kündigung des Abonnements kann jederzeit über das Kundenportal erfolgen. Bei Downgrade oder Kündigung gelten die Bestandsschutz-Regeln: QR-Codes, die während eines Pro/Business-Abos erstellt wurden, behalten ihre Eigenschaften (Gültigkeit, Alias, Passwortschutz, Branding). Neue QR-Codes richten sich nach dem dann aktiven Tarif.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§7 Haftungsausschluss</h2>
                <p>qrm.sg haftet unbeschränkt nur bei Vorsatz und grober Fahrlässigkeit. Für leichte Fahrlässigkeit haftet qrm.sg nur bei Verletzung einer wesentlichen Vertragspflicht und nur in Höhe des typischerweise entstehenden Schadens. Die Verfügbarkeit des Dienstes wird im Rahmen des technisch Machbaren gewährleistet; ein Anspruch auf ununterbrochene Verfügbarkeit besteht nicht.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§8 Datenschutz</h2>
                <p>Es gelten die Bestimmungen der Datenschutz-Grundverordnung (DSGVO) und der geltenden nationalen Datenschutzgesetze. Details sind in der separaten Datenschutzerklärung geregelt.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§9 Gerichtsstand</h2>
                <p>Ausschließlicher Gerichtsstand für alle Streitigkeiten ist — soweit gesetzlich zulässig — der Sitz des Betreibers (wrkspc.xyz). Kontakt: <a href="mailto:info@qrm.sg" class="text-indigo-600 hover:underline">info@qrm.sg</a>.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§10 Änderungen dieser AGB</h2>
                <p>qrm.sg behält sich vor, diese AGB jederzeit zu ändern. Nutzer werden über wesentliche Änderungen per E-Mail informiert. Die Weiternutzung des Dienstes gilt als Zustimmung zu den geänderten Bedingungen.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('terms') }}" class="text-sm text-indigo-600 hover:underline">English Terms of Service →</a>
        </div>
    </main>
</body>
</html>
