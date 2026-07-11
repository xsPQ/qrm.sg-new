@php
    $name = trim(($content['first_name'] ?? '') . ' ' . ($content['last_name'] ?? ''));
    $fields = [
        __('Name') => $name,
        __('Organization') => $content['organization'] ?? '',
        __('Title') => $content['title'] ?? '',
        __('Email') => $content['email'] ?? '',
        __('Phone (mobile)') => $content['phone_mobile'] ?? '',
        __('Phone (work)') => $content['phone_work'] ?? '',
        __('Website') => $content['website'] ?? '',
        __('Address') => trim(((string) ($content['address_street'] ?? '')) . ' ' . ((string) ($content['address_zip'] ?? '')) . ' ' . ((string) ($content['address_city'] ?? '')) . ' ' . ((string) ($content['address_country'] ?? ''))),
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])
