@php
    $typeOptions = $this->typeOptions();
    $statusOptions = $this->statusOptions();
    $hasFilters = $search !== '' || $typeFilter !== '' || $statusFilter !== '';
@endphp

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">

            <div class="flex flex-col gap-4 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('My QR Codes') }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ __('Search by title, code or alias. Filter by type or status.') }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ __('Search code / alias / title…') }}"
                               autocomplete="off"
                               class="block w-full rounded-md border-gray-300 pl-9 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>

                    <select wire:model.live="typeFilter"
                            aria-label="{{ __('Filter by type') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="statusFilter"
                            aria-label="{{ __('Filter by status') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    @if ($hasFilters)
                        <button type="button" wire:click="resetFilters"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                            {{ __('Reset') }}
                        </button>
                    @endif
                </div>
            </div>

            <div wire:loading.delay wire:target="search,typeFilter,statusFilter" class="border-b border-gray-100 bg-indigo-50 px-6 py-2 text-sm text-indigo-700">
                {{ __('Updating…') }}
            </div>

            @if ($qrCodes->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9h2V7H3v2zm0 8h2v-6H3v6zm4-8h14V7H7v2zm0 8h14v-6H7v6z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">
                        @if ($hasFilters)
                            {{ __('No QR codes match your filters.') }}
                        @else
                            {{ __('You have no QR codes yet.') }}
                        @endif
                    </p>
                    <a href="{{ route('qr.creator') }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        {{ __('Create your first QR code') }} &rarr;
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Code / Alias') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Type') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Scans') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Created') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Expires') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($qrCodes as $qrCode)
                                @php
                                    $route = $qrCode->route;
                                    $codeAlias = $route?->alias ?? $route?->code ?? $qrCode->title;
                                    $status = $this->effectiveStatus($qrCode);
                                    $badge = $this->badgeClasses($status);
                                    $typeLabel = App\Enums\QrCodeType::tryFrom($qrCode->type)?->label() ?? ucfirst($qrCode->type);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('qr-codes.detail', $qrCode) }}"
                                           class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $codeAlias }}
                                        </a>
                                        @if ($route?->alias && $route?->code)
                                            <div class="text-xs text-gray-400">{{ $qrCode->title }} · {{ $route->code }}</div>
                                        @elseif ($qrCode->title && $qrCode->title !== $codeAlias)
                                            <div class="text-xs text-gray-400">{{ $qrCode->title }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $typeLabel }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $qrCode->scan_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $qrCode->created_at?->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if ($qrCode->expires_at)
                                            {{ $qrCode->expires_at->format('Y-m-d') }}
                                        @else
                                            <span class="text-gray-400">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('qr-codes.edit', $qrCode) }}" wire:navigate
                                               class="text-indigo-600 hover:text-indigo-500">
                                                {{ __('Edit') }}
                                            </a>
                                            <a href="{{ route('qr-codes.edit', $qrCode) }}#danger-zone"
                                               class="text-red-600 hover:text-red-500">
                                                {{ __('Delete') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $qrCodes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
