@php
    // Shared definition-list renderer for payload previews (P2-T02).
    // Every value is echoed through Blade's {{ }} (HTML-escaped) so payload
    // data is rendered XSS-safe regardless of QR type.
    $fields = $fields ?? [];
@endphp

<dl class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-100">
    @foreach ($fields as $label => $value)
        <div class="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
            <dd class="break-words text-sm font-medium text-gray-800">{{ $value === '' || $value === null ? '—' : $value }}</dd>
        </div>
    @endforeach
</dl>
