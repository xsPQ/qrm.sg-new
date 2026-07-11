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
                    {{ __('Edit QR Code') }}
                </h2>
            </div>

            @if (Route::has('qr-codes.detail'))
                <a href="{{ route('qr-codes.detail', $qrCode) }}"
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                    {{ __('View details') }}
                </a>
            @endif
        </div>
    </x-slot>

    <livewire:qr-code-editor :qr-code="$qrCode" />
</x-app-layout>
