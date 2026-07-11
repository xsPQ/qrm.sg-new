@extends('qr-types.layout', ['title' => __('WiFi Access')])

@section('content')
<div class="bg-white rounded-xl shadow-sm p-8 text-center">
    <div class="mb-6">
        <svg class="w-12 h-12 mx-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold mb-4">{{ __('WiFi Network') }}</h1>

    <div class="space-y-3 text-left bg-gray-50 rounded-lg p-4 mb-6">
        <div>
            <span class="text-sm text-gray-500">{{ __('Network Name (SSID)') }}</span>
            <p class="font-mono font-semibold">{{ $ssid }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">{{ __('Security') }}</span>
            <p class="font-semibold">{{ $encryption }}</p>
        </div>
        @if($password)
        <div>
            <span class="text-sm text-gray-500">{{ __('Password') }}</span>
            <p class="font-mono font-semibold">{{ $password }}</p>
        </div>
        @endif
        @if($hidden)
        <div>
            <span class="text-sm text-amber-600">{{ __('This is a hidden network') }}</span>
        </div>
        @endif
    </div>

    <p class="text-sm text-gray-500">
        {{ __('Scan this QR code with your device camera to connect automatically.') }}
    </p>
</div>
@endsection
