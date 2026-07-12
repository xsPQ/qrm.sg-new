{{--
    qrm.sg Brand Logo — Isometric Cube with QR Finder Patterns
    Keeps the familiar cube aesthetic but makes it qrm.sg-specific.
    Accepts Tailwind classes via $attributes.
--}}
<svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="qrmGradFace" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#4F46E5"/>
            <stop offset="100%" style="stop-color:#6366F1"/>
        </linearGradient>
        <linearGradient id="qrmGradRight" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#4338CA"/>
            <stop offset="100%" style="stop-color:#3730A3"/>
        </linearGradient>
        <linearGradient id="qrmGradLeft" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#7C3AED"/>
            <stop offset="100%" style="stop-color:#6D28D9"/>
        </linearGradient>
    </defs>

    {{-- Top face (rhombus) --}}
    <polygon points="158,30 278,95 158,160 38,95" fill="url(#qrmGradFace)"/>
    {{-- Right face --}}
    <polygon points="278,95 278,225 158,290 158,160" fill="url(#qrmGradRight)"/>
    {{-- Left face --}}
    <polygon points="38,95 158,160 158,290 38,225" fill="url(#qrmGradLeft)"/>

    {{-- QR finder pattern on top face (nested squares, projected onto rhombus) --}}
    {{-- Outer ring --}}
    <polygon points="158,55 233,95 158,135 83,95" fill="none" stroke="#fff" stroke-width="6"/>
    {{-- Inner square --}}
    <polygon points="158,72 203,95 158,118 113,95" fill="#fff"/>

    {{-- QR data dots on right face --}}
    <rect x="190" y="175" width="12" height="12" rx="2" fill="#fff" opacity="0.85"/>
    <rect x="210" y="175" width="12" height="12" rx="2" fill="#fff" opacity="0.55"/>
    <rect x="190" y="195" width="12" height="12" rx="2" fill="#fff" opacity="0.65"/>
    <rect x="230" y="195" width="12" height="12" rx="2" fill="#fff" opacity="0.9"/>
    <rect x="210" y="215" width="12" height="12" rx="2" fill="#fff" opacity="0.45"/>
    <rect x="190" y="235" width="12" height="12" rx="2" fill="#fff" opacity="0.75"/>
    <rect x="230" y="240" width="12" height="12" rx="2" fill="#fff" opacity="0.5"/>
    <rect x="210" y="255" width="12" height="12" rx="2" fill="#fff" opacity="0.85"/>

    {{-- QR finder pattern on left face (simplified, nested squares) --}}
    <polygon points="98,155 125,170 125,215 98,230 71,215 71,170" fill="none" stroke="#fff" stroke-width="5"/>
    <rect x="85" y="180" width="20" height="20" rx="3" fill="#fff"/>

    {{-- Subtle edge highlights --}}
    <line x1="38" y1="95" x2="158" y2="160" stroke="#fff" stroke-width="1" opacity="0.15"/>
    <line x1="278" y1="95" x2="158" y2="160" stroke="#fff" stroke-width="1" opacity="0.15"/>
    <line x1="158" y1="160" x2="158" y2="290" stroke="#fff" stroke-width="1" opacity="0.1"/>
</svg>
