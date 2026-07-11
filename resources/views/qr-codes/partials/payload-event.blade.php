@php
    $fields = [
        __('Title') => $content['title'] ?? '',
        __('Start') => $content['start'] ?? '',
        __('End') => $content['end'] ?? '',
        __('Location') => $content['location'] ?? '',
        __('Description') => $content['description'] ?? '',
        __('Timezone') => $content['timezone'] ?? '',
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])
