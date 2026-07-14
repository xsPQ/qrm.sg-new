<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create QR Code') }}
        </h2>
    </x-slot>

    <livewire:qr-creator />
</x-app-layout>
