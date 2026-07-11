@php
    $links = is_array($content['links'] ?? null) ? $content['links'] : [];
    $hasSingleProfile = !empty($content['platform']) || !empty($content['username']);

    $fields = [];

    if (!empty($content['title'])) {
        $fields[__('Title')] = $content['title'];
    }
    if (!empty($content['description'])) {
        $fields[__('Description')] = $content['description'];
    }
    if ($hasSingleProfile) {
        $fields[__('Platform')] = ucfirst((string) ($content['platform'] ?? ''));
        $fields[__('Username')] = $content['username'] ?? '';
    }
@endphp

@if (!empty($fields))
    @include('qr-codes.partials._fields', ['fields' => $fields])
@endif

@if (!empty($links))
    <div class="rounded-lg border border-gray-100">
        @foreach ($links as $link)
            @php
                $platform = ucfirst((string) ($link['platform'] ?? ''));
                $url = (string) ($link['url'] ?? '#');
                $label = $link['label'] ?? $platform;
            @endphp
            <div class="flex items-center justify-between gap-4 px-4 py-2.5 border-b border-gray-100 last:border-0">
                <span class="text-sm font-medium text-gray-800">{{ $label !== '' ? $label : $platform }}</span>
                <span class="break-all text-sm text-gray-500">{{ $url }}</span>
            </div>
        @endforeach
    </div>
@endif
