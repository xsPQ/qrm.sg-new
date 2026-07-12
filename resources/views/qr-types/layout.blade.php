<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'QR Code' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --qr-primary: #6366f1;
            --qr-primary-light: #eef2ff;
            --qr-text: #1e293b;
            --qr-muted: #64748b;
            --qr-border: #e2e8f0;
            --qr-bg: #f8fafc;
        }
        .qr-page { font-family: 'Figtree', system-ui, sans-serif; background: var(--qr-bg); min-height: 100vh; display: flex; flex-direction: column; }
        .qr-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .qr-card { background: white; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(0,0,0,0.06); max-width: 28rem; width: 100%; overflow: hidden; }
        .qr-card-header { padding: 2rem 2rem 0; text-align: center; }
        .qr-card-body { padding: 1.5rem 2rem 2rem; }
        .qr-icon-wrap { width: 3rem; height: 3rem; border-radius: 0.75rem; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
        .qr-icon-wrap svg { width: 1.5rem; height: 1.5rem; }
        .qr-title { font-size: 1.5rem; font-weight: 700; color: var(--qr-text); margin-bottom: 0.25rem; }
        .qr-subtitle { font-size: 0.875rem; color: var(--qr-muted); margin-bottom: 0; }
        .qr-field { padding: 0.75rem 0; border-bottom: 1px solid var(--qr-border); }
        .qr-field:last-child { border-bottom: none; }
        .qr-field-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--qr-muted); margin-bottom: 0.25rem; }
        .qr-field-value { font-size: 1rem; color: var(--qr-text); word-break: break-all; }
        .qr-mono { font-family: 'SF Mono', 'Fira Code', monospace; }
        .qr-btn { display: block; width: 100%; text-align: center; padding: 0.75rem 1rem; border-radius: 0.625rem; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: all 0.15s; cursor: pointer; border: none; }
        .qr-btn:hover { opacity: 0.9; }
        .qr-btn-copy { background: var(--qr-primary-light); color: var(--qr-primary); margin-top: 0.75rem; }
        .qr-btn-copy:hover { background: #e0e7ff; }
        .qr-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .qr-actions .qr-btn { flex: 1; }
        .qr-footer { border-top: 1px solid var(--qr-border); padding: 0.75rem; text-align: center; }
        .qr-footer a { font-size: 0.75rem; color: var(--qr-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 0.375rem; }
        .qr-footer a:hover { color: var(--qr-primary); }
        .qr-footer-hint { font-size: 0.6875rem; color: #94a3b8; margin-top: 0.25rem; }
        .qr-footer-hint a { color: var(--qr-primary); text-decoration: underline; }
        .qr-input { width: 100%; padding: 0.75rem 1rem; border-radius: 0.625rem; border: 1px solid var(--qr-border); font-size: 1rem; outline: none; transition: border-color 0.15s; }
        .qr-input:focus { border-color: var(--qr-primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .qr-copied { animation: qr-fade 1s ease; }
        @keyframes qr-fade { 0% { opacity: 1; } 100% { opacity: 1; } }
        @media (max-width: 640px) { .qr-card { border-radius: 1rem; } .qr-card-header { padding: 1.5rem 1.5rem 0; } .qr-card-body { padding: 1rem 1.5rem 1.5rem; } }
    </style>
</head>
<body class="qr-page">
    <main class="qr-main">
        <div class="qr-card">
            @yield('content')
        </div>
    </main>

    {{-- Branding footer (FEAT-09) --}}
    @php
        $showBranding = ! isset($qrCode) || ($qrCode->entitlementSnapshot()->plan() ?? 'free') === 'free';
        $showProHint = isset($qrCode) && ($qrCode->entitlementSnapshot()->plan() ?? 'free') === 'pro';
        $isWhitelabel = isset($qrCode) && ($qrCode->entitlementSnapshot()->plan() ?? 'free') === 'business';
    @endphp

    @if($showBranding && !$isWhitelabel)
        <footer class="qr-footer">
            <a href="{{ route('landing') }}">
                <svg width="14" height="14" viewBox="0 0 316 316" fill="currentColor"><path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125Z"/></svg>
                <span>{{ __('Powered by') }} <strong>qrm.sg</strong></span>
            </a>
            <p class="qr-footer-hint">{{ __('Create your own dynamic QR codes for free') }} → <a href="{{ route('landing') }}">{{ __('Get started') }}</a></p>
        </footer>
    @elseif($showProHint && !$isWhitelabel)
        <footer class="qr-footer" style="border-top: none;">
            <a href="{{ route('landing') }}">
                <svg width="12" height="12" viewBox="0 0 316 316" fill="currentColor"><path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125Z"/></svg>
                <strong>qrm.sg</strong>
            </a>
        </footer>
    @endif
</body>
</html>
