<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impressum — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← {{ __('Zurück zur Startseite') }}</a>

        <h1 class="text-3xl font-bold mb-8">Impressum</h1>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">Angaben gemäß § 5 DDG</h2>
                <p>
                    <strong>wrkspc.xyz</strong><br>
                    Inhaber: Stephan<br>
                    qrm.sg ist eine Marke der wrkspc.xyz
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Kontakt</h2>
                <p>
                    E-Mail: <a href="mailto:info@qrm.sg" class="text-indigo-600 hover:underline">info@qrm.sg</a><br>
                    Benachrichtigungen: <a href="mailto:sg@wrkspc.xyz" class="text-indigo-600 hover:underline">sg@wrkspc.xyz</a>
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Verantwortlich für den Inhalt</h2>
                <p>Stephan, wrkspc.xyz</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Streitschlichtung</h2>
                <p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: <a href="https://ec.europa.eu/consumers/odr/" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr/</a>. Unsere E-Mail-Adresse finden Sie oben im Impressum.</p>
                <p class="mt-2">Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Haftung für Inhalte</h2>
                <p>Als Diensteanbieter sind wir gemäß § 7 Abs.1 DDG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 DDG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Haftung für Links</h2>
                <p>Unser Angebot enthält ggf. Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Urheberrecht</h2>
                <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('imprint') }}" class="text-sm text-indigo-600 hover:underline">English Imprint →</a>
        </div>
    </main>
</body>
</html>
