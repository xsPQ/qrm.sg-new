@extends('qr-types.layout', ['title' => __('Crypto Payment')])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #fed7aa;">
        <svg style="color: #ea580c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
    </div>
    <h1 class="qr-title">{{ __('Crypto Payment') }}</h1>
    <p class="qr-subtitle">{{ $currency }}</p>
</div>
<div class="qr-card-body">
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Wallet Address') }}</div>
        <div class="qr-field-value qr-mono" style="font-size: 0.875rem;">{{ $address }}</div>
    </div>
    @if($amount)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Amount') }}</div>
        <div class="qr-field-value">{{ $amount }} {{ $currency }}</div>
    </div>
    @endif

    <div class="qr-actions">
        @if($uri && ($currency === 'BTC' || $currency === 'ETH' || $currency === 'SOL'))
        <a href="{{ $uri }}" class="qr-btn" style="background: #ea580c; color: white;">
            {{ __('Open in Wallet') }}
        </a>
        @endif
        <button class="qr-btn qr-btn-copy" onclick="
            navigator.clipboard.writeText(@json($address));
            this.textContent = '{{ __('Copied!') }}';
            setTimeout(() => this.textContent = '{{ __('Copy address') }}', 2000);
        ">{{ __('Copy address') }}</button>
    </div>
</div>
@endsection
