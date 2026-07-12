<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — qrm.sg</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-12">
        <a href="{{ route('landing') }}" class="text-sm text-indigo-600 hover:underline mb-8 inline-block">← Back to home</a>

        <h1 class="text-3xl font-bold mb-8">Terms of Service</h1>
        <p class="text-sm text-gray-500 mb-8">Last updated: July 2026</p>

        <div class="prose prose-sm max-w-none space-y-6">
            <section>
                <h2 class="text-xl font-semibold mb-2">§1 Service Description</h2>
                <p>qrm.sg is an online service for creating, managing, and analyzing dynamic QR codes. The content behind each QR code can be changed at any time without reprinting the QR code. The service includes analytics (scan statistics), various QR code types (URL, WiFi, vCard, Event, etc.), and A/B testing features.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§2 Plans and Pricing</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li><strong>Free:</strong> No charge. Max 10 active QR codes, 30-day validity, limited analytics.</li>
                    <li><strong>Pro:</strong> €5/month. Unlimited QR codes (fair-use cap: 500), full analytics, custom aliases from 4 characters, password protection.</li>
                    <li><strong>Business:</strong> €19/month. Higher limits (5,000 QR codes), API access, team management, white-label, premium aliases from 2 characters.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§3 Fair-Use Policy</h2>
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
                        <tr><td class="px-3 py-2">Active QR codes</td><td class="px-3 py-2 text-right">10</td><td class="px-3 py-2 text-right">500</td><td class="px-3 py-2 text-right">5,000</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">Scans/day (per code)</td><td class="px-3 py-2 text-right">—</td><td class="px-3 py-2 text-right">50,000</td><td class="px-3 py-2 text-right">500,000</td></tr>
                        <tr><td class="px-3 py-2">API requests/min</td><td class="px-3 py-2 text-right">—</td><td class="px-3 py-2 text-right">60</td><td class="px-3 py-2 text-right">300</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2">A/B variants per code</td><td class="px-3 py-2 text-right">0</td><td class="px-3 py-2 text-right">5</td><td class="px-3 py-2 text-right">20</td></tr>
                    </tbody>
                </table>
                <p class="mt-3">qrm.sg reserves the right to temporarily deactivate QR codes and notify the user when limits are exceeded. There is no entitlement to usage beyond the fair-use limits.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§4 User Obligations</h2>
                <ul class="list-disc pl-6 mt-2 space-y-1">
                    <li>No QR codes linking to phishing, malware, or illegal content</li>
                    <li>No spam, mass mailings, or unsolicited advertising</li>
                    <li>No content that violates applicable law, third-party rights, or public morals</li>
                    <li>No automated mass requests that overload the service</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§5 Payment Terms</h2>
                <p>Subscriptions are billed monthly via Stripe. Cancellation is possible at any time at the end of the current billing period.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§6 Cancellation and Grandfathering</h2>
                <p>QR codes created during a Pro/Business subscription retain their features after downgrade (validity, alias, password protection, branding). New QR codes follow the then-active plan.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§7 Limitation of Liability</h2>
                <p>qrm.sg is liable without limitation only for intent and gross negligence. For slight negligence, liability is limited to typical foreseeable damages.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§8 Data Protection</h2>
                <p>The provisions of the GDPR and applicable national data protection laws apply. Details are set out in the separate Privacy Policy.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">§9 Changes to These Terms</h2>
                <p>qrm.sg reserves the right to update these Terms at any time. Users will be notified of material changes via email. Continued use constitutes acceptance.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200">
            <a href="{{ route('agb') }}" class="text-sm text-indigo-600 hover:underline">Deutsche AGB →</a>
        </div>
    </main>
</body>
</html>
