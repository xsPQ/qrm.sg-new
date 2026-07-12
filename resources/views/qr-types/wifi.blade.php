@extends('qr-types.layout', ['title' => __('WiFi Access')])

@section('content')
@php
    $routeCode = $qrCode->route?->code ?? '';
    $encryptionUpper = strtoupper($encryption);
    $isWpa = in_array($encryptionUpper, ['WPA', 'WPA2']);
    $isNoPass = $encryptionUpper === 'NONE' || $encryptionUpper === 'NOPASS';
    // Build WIFI: URI for JS navigator
    $wifiUri = 'WIFI:S:' . $ssid . ';T:' . ($isWpa ? 'WPA' : ($encryptionUpper === 'WEP' ? 'WEP' : 'nopass')) . ';';
    if ($password && !$isNoPass) { $wifiUri .= 'P:' . $password . ';'; }
    if ($hidden) { $wifiUri .= 'H:true;;'; }
@endphp

<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #dbeafe;">
        <svg style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/>
        </svg>
    </div>
    <h1 class="qr-title">{{ __('WiFi Network') }}</h1>
    <p class="qr-subtitle">{{ $ssid }}</p>
</div>
<div class="qr-card-body">

    {{-- Primary connect button: platform-aware --}}
    <div id="connect-section">
        <button id="connect-btn" class="qr-btn" style="background: #2563eb; color: white; font-size: 1.05rem; padding: 0.875rem 1rem;">
            <span id="connect-label">{{ __('Tap to connect') }}</span>
        </button>
        <p id="connect-hint" style="text-align: center; font-size: 0.8rem; color: var(--qr-muted); margin-top: 0.5rem;">
            {{ __('Opens WiFi settings automatically') }}
        </p>
    </div>

    {{-- Network details --}}
    <div style="margin-top: 1.5rem;">
        <div class="qr-field">
            <div class="qr-field-label">{{ __('Network Name (SSID)') }}</div>
            <div class="qr-field-value qr-mono">{{ $ssid }}</div>
        </div>
        <div class="qr-field">
            <div class="qr-field-label">{{ __('Security') }}</div>
            <div class="qr-field-value">{{ $encryption }}</div>
        </div>
        @if($password)
        <div class="qr-field">
            <div class="qr-field-label">{{ __('Password') }}</div>
            <div class="qr-field-value qr-mono" id="wifi-password">{{ $password }}</div>
        </div>
        @endif
        @if($hidden)
        <div class="qr-field">
            <div class="qr-field-value" style="color: #d97706; font-size: 0.85rem;">⚠ {{ __('This is a hidden network') }}</div>
        </div>
        @endif
    </div>

    {{-- Manual copy fallback --}}
    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
        @if($password)
        <button class="qr-btn qr-btn-copy" onclick="
            navigator.clipboard.writeText(@json($password));
            this.textContent = '{{ __('Password copied!') }}';
            setTimeout(() => this.textContent = '{{ __('Copy password') }}', 2000);
        " style="flex: 1;">{{ __('Copy password') }}</button>
        @endif
        <button class="qr-btn qr-btn-copy" onclick="
            navigator.clipboard.writeText(@json($ssid));
            this.textContent = '{{ __('SSID copied!') }}';
            setTimeout(() => this.textContent = '{{ __('Copy SSID') }}', 2000);
        " style="flex: 1;">{{ __('Copy SSID') }}</button>
    </div>

    @if($password)
    <p style="text-align: center; font-size: 0.78rem; color: var(--qr-muted); margin-top: 1rem; line-height: 1.4;">
        {{ __('Manually: Settings → WiFi → Select ":name" → Enter password', ['name' => $ssid]) }}
    </p>
    @endif
</div>

{{-- Platform detection and connect logic --}}
<script>
(function() {
    var btn = document.getElementById('connect-btn');
    var label = document.getElementById('connect-label');
    var hint = document.getElementById('connect-hint');
    var routeCode = @json($routeCode);
    var hasPassword = @json((bool) $password);
    var ssid = @json($ssid);

    if (!routeCode) return;

    var ua = navigator.userAgent;
    var isIOS = /iPad|iPhone|iPod/.test(ua);
    var isAndroid = /Android/.test(ua);
    var isMacSafari = /Macintosh/.test(ua) && /Safari/.test(ua) && !/Chrome/.test(ua);

    var appleUrl = '/r/' + routeCode + '/wifi.mobileconfig';

    if (isIOS || isMacSafari) {
        // iOS/macOS Safari → .mobileconfig profile
        label.textContent = @json(__('Install WiFi Profile'));
        hint.textContent = @json(__('Opens Settings → Profile Downloaded → Install'));
        btn.onclick = function() { window.location.href = appleUrl; };
    } else if (isAndroid) {
        // Android: try WIFI: intent via Android JoinWifi
        // Since browsers block WIFI: URIs, we offer a copy-and-open flow
        label.textContent = @json(__('Open WiFi Settings'));
        hint.textContent = @json(__('Copies password, then opens WiFi settings'));
        btn.onclick = function() {
            @if($password)
            navigator.clipboard.writeText(@json($password)).then(function() {
                hint.textContent = @json(__('Password copied! Opening settings…'));
                // Try Android intent URI
                setTimeout(function() {
                    window.location.href = 'intent://network?id=' + encodeURIComponent(ssid) + '#Intent;scheme=android.settings;package=com.android.settings;end;';
                }, 500);
            });
            @else
            window.location.href = 'intent://network?id=' + encodeURIComponent(ssid) + '#Intent;scheme=android.settings;package=com.android.settings;end;';
            @endif
        };
    } else {
        // Desktop / other: manual instructions
        label.textContent = @json(__('Show Connection Guide'));
        hint.textContent = @json(__('This device type does not support auto-connect'));
        btn.onclick = function() {
            alert(
                @json(__('Manual WiFi Setup')) + '\n\n' +
                '1. ' + @json(__('Open WiFi settings on your phone')) + '\n' +
                '2. ' + @json(__('Select network')) + ': "' + ssid + '"\n' +
                @if($password)
                '3. ' + @json(__('Enter password')) + ': ' + @json($password) + '\n' +
                @endif
                '4. ' + @json(__('Tap Connect'))
            );
        };
    }
})();
</script>
@endsection
