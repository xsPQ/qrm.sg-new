@php
    $target = $content['target_url'] ?? '';
    $fields = [
        __('Target URL') => $target,
    ];
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])

@if ($target !== '')
    <p class="mt-2 text-right">
        <a href="{{ $target }}" target="_blank" rel="noopener noreferrer"
           class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            {{ __('Open target') }} &rarr;
        </a>
    </p>
@endif
