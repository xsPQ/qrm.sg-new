@php
    $url = $content['url'] ?? '';
    $fields = [
        __('URL') => $url,
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])

@if ($url !== '')
    <p class="mt-2 text-right">
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
           class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            {{ __('Open link') }} &rarr;
        </a>
    </p>
@endif
