@extends('qr-types.layout', ['title' => __('Crypto Payment')])

@section('content')
<div class="bg-white rounded-xl shadow-sm p-8 text-center">
    <div class="mb-6">
        <svg class="w-12 h-12 mx-auto text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold mb-4">{{ __('Crypto Payment') }}</h1>

    <div class="space-y-3 text-left bg-gray-50 rounded-lg p-4 mb-6">
        <div>
            <span class="text-sm text-gray-500">{{ __('Currency') }}</span>
            <p class="font-bold text-lg">{{ $currency }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500">{{ __('Address') }}</span>
            <p class="font-mono text-sm break-all">{{ $address }}</p>
        </div>
        @if($amount)
        <div>
            <span class="text-sm text-gray-500">{{ __('Amount') }}</span>
            <p class="font-semibold">{{ $amount }}</p>
        </div>
        @endif
        @if($uri)
        <div>
            <span class="text-sm text-gray-500">{{ __('Wallet URI') }}</span>
            <p class="font-mono text-xs break-all">{{ $uri }}</p>
        </div>
        @endif
    </div>

    @if($uri && ($currency === 'BTC' || $currency === 'ETH' || $currency === 'SOL'))
    <a href="{{ $uri }}"
       class="block w-full text-center bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-4 rounded-lg transition">
        {{ __('Open in Wallet') }}
    </a>
    @endif

    <p class="text-sm text-gray-500 mt-4">
        {{ __('Scan this QR code to copy the wallet address.') }}
    </p>
</div>
@endsection
