<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprint — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← Back to home</a>

        <h1 class="text-3xl font-bold mb-8">Imprint</h1>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">Provider Information</h2>
                <p>
                    <strong>wrkspc.xyz</strong><br>
                    Owner: Stephan<br>
                    qrm.sg is a brand of wrkspc.xyz
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Contact</h2>
                <p>
                    Email: <a href="mailto:info@qrm.sg" class="text-indigo-600 hover:underline">info@qrm.sg</a><br>
                    Notifications: <a href="mailto:sg@wrkspc.xyz" class="text-indigo-600 hover:underline">sg@wrkspc.xyz</a>
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Responsible for Content</h2>
                <p>Stephan, wrkspc.xyz</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Dispute Resolution</h2>
                <p>The European Commission provides a platform for online dispute resolution (ODR): <a href="https://ec.europa.eu/consumers/odr/" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr/</a>. Our email address can be found above in this imprint.</p>
                <p class="mt-2">We are not willing or obliged to participate in dispute resolution proceedings before a consumer arbitration board.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Liability for Content</h2>
                <p>As a service provider, we are responsible for our own content on these pages in accordance with general legislation. However, we are not obliged to monitor transmitted or stored third-party information.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">Copyright</h2>
                <p>The content and works created by the site operators on these pages are subject to copyright. Reproduction, processing, distribution and any form of commercialization of such material beyond the scope of copyright law require the written consent of its respective author or creator.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('impressum') }}" class="text-sm text-indigo-600 hover:underline">Deutsches Impressum →</a>
        </div>
    </main>
</body>
</html>
