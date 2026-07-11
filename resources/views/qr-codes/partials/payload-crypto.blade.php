@php
    $fields = [
        __('Currency') => strtoupper((string) ($content['currency'] ?? '')),
        __('Address') => $content['address'] ?? '',
        __('Amount') => $content['amount'] ?? '',
        __('Label') => $content['label'] ?? '',
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])
