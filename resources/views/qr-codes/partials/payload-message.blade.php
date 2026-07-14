@php
    $fields = [
        __('Title') => $content['title'] ?? '',
        __('Body') => $content['body'] ?? $content['message'] ?? '',
    ];
@endphp

@if (! empty($content['body']))
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 sm:col-span-2">
        <p class="whitespace-pre-wrap break-words text-sm text-gray-800">{{ $content['body'] }}</p>
    </div>
@endif

@include('qr-codes.partials._fields', ['fields' => $fields])
