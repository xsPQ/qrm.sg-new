@php
    use App\Enums\QrCodeType;

    $route = $qrCode->route;
    $type = QrCodeType::tryFrom($qrCode->type);
    $typeLabel = $type?->label() ?? ucfirst($qrCode->type);
    $content = is_array($qrCode->content) ? $qrCode->content : [];
    $codeAlias = $route?->alias ?? $route?->code ?? $qrCode->title;

    // Effective status, mirroring the list (P2-T01): burned and date-expired
    // codes surface truthfully even before the cleanup job flips the column.
    $effectiveStatus = $qrCode->isBurned()
        ? 'burned'
        : ($qrCode->isExpired() ? 'expired' : $qrCode->status);

    $badgeClasses = match ($effectiveStatus) {
        'active' => 'bg-green-100 text-green-800 ring-green-600/20',
        'expired' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        'burned' => 'bg-red-100 text-red-800 ring-red-600/20',
        default => 'bg-gray-100 text-gray-600 ring-gray-500/20',
    };

    $tier = $qrCode->entitlement_snapshot['tier']
        ?? $qrCode->entitlement_snapshot['plan']
        ?? 'free';

    // Pre-computed for the "expires soon" banner below.
    $days = $qrCode->expiresInDays();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-indigo-600"
                   wire:navigate>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Dashboard') }}
                </a>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $codeAlias }}
                </h2>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">
                    {{ ucfirst($effectiveStatus) }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                @if (Route::has('qr-codes.edit'))
                    <a href="{{ route('qr-codes.edit', $qrCode) }}"
                       class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                        {{ __('Edit') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if ($qrCode->isExpiringSoon())
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ __('This code expires soon.') }}
                    @if ($days !== null && $days >= 0)
                        {{ trans_choice(':count day|:count days', $days) }}
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- QR preview + downloads --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('QR Preview') }}
                    </h3>

                    <div class="mt-4 flex flex-col items-center">
                        @if ($previewDataUri)
                            <img src="{{ $previewDataUri }}"
                                 alt="{{ __('QR code for :name', ['name' => $codeAlias]) }}"
                                 class="h-64 w-64 rounded-lg border border-gray-100 bg-white p-2" />
                        @else
                            <div class="flex h-64 w-64 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-sm text-gray-400">
                                {{ __('Preview unavailable') }}
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ $downloadSvgUrl }}"
                           class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ __('Download SVG') }}
                        </a>
                        <a href="{{ $downloadPngUrl }}"
                           class="inline-flex items-center gap-2 rounded-md border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ __('Download PNG') }}
                        </a>
                    </div>

                    <dl class="mt-5 space-y-1 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Scan link') }}</dt>
                            <dd class="truncate font-mono text-gray-700">{{ $shortLink }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Metadata --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('Details') }}
                    </h3>

                    @php
                        $rows = [
                            __('Title') => $qrCode->title ?: '—',
                            __('Type') => $typeLabel,
                            __('Code') => $route?->code ?? '—',
                            __('Alias') => $route?->alias ?: '—',
                            __('Status') => ucfirst($effectiveStatus),
                            __('Plan / Tier') => ucfirst((string) $tier),
                            __('Password protected') => ($qrCode->password_hash !== null) ? __('Yes') : __('No'),
                            __('Scans') => (string) (int) $qrCode->scan_count,
                            __('Max scans') => $qrCode->max_scans !== null ? (string) (int) $qrCode->max_scans : '∞',
                            __('Burn after scan') => $qrCode->burn ? __('Yes') : __('No'),
                            __('Created') => $qrCode->created_at?->format('Y-m-d H:i'),
                            __('Last scanned') => $lastScannedAt?->format('Y-m-d H:i') ?: '—',
                            __('Expires') => $qrCode->expires_at?->format('Y-m-d H:i') ?: '—',
                        ];
                    @endphp

                    <dl class="mt-4 divide-y divide-gray-100">
                        @foreach ($rows as $label => $value)
                            <div class="flex items-start justify-between gap-4 py-2">
                                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                                <dd class="text-right text-sm font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Type-specific payload (XSS-safe: every value is Blade-escaped) --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('Content') }} · {{ $typeLabel }}
                    </h3>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @switch($qrCode->type)
                            @case('url')
                                @include('qr-codes.partials.payload-url', ['content' => $content])
                                @break
                            @case('wifi')
                                @include('qr-codes.partials.payload-wifi', ['content' => $content])
                                @break
                            @case('vcard')
                                @include('qr-codes.partials.payload-vcard', ['content' => $content])
                                @break
                            @case('message')
                                @include('qr-codes.partials.payload-message', ['content' => $content])
                                @break
                            @case('crypto')
                                @include('qr-codes.partials.payload-crypto', ['content' => $content])
                                @break
                            @case('social')
                                @include('qr-codes.partials.payload-social', ['content' => $content])
                                @break
                            @case('event')
                                @include('qr-codes.partials.payload-event', ['content' => $content])
                                @break
                            @case('redirect')
                                @include('qr-codes.partials.payload-redirect', ['content' => $content])
                                @break
                            @default
                                @include('qr-codes.partials.payload-generic', ['content' => $content])
                        @endswitch
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
