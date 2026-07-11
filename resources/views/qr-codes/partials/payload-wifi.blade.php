@php
    $encryption = $content['encryption'] ?? 'WPA';
    $fields = [
        __('Network (SSID)') => $content['ssid'] ?? '',
        __('Security') => $encryption,
        __('Password') => $content['password'] ?? '',
        __('Hidden network') => !empty($content['hidden']) ? __('Yes') : __('No'),
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])
