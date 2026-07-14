@extends('qr-types.layout', ['title' => __('Password Required')])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #fef3c7;">
        <svg style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h1 class="qr-title">{{ __('Password Protected') }}</h1>
    <p class="qr-subtitle">{{ __('This QR code requires a password to view.') }}</p>
</div>
<div class="qr-card-body">
    <form method="POST" action="{{ route('qr.scan.password', $qrCode->route?->code ?? '') }}">
        @csrf
        <div style="margin-bottom: 1rem;">
            @php
                $hasError = $errors->has('password');
                $errorBorder = $hasError ? 'border-color: #ef4444;' : '';
            @endphp
            <input type="password"
                   name="password"
                   id="password"
                   required
                   autofocus
                   class="qr-input"
                   placeholder="{{ __('Enter password') }}"
                   style="{{ $errorBorder }}">
            @if($hasError)
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #ef4444;">{{ $errors->first('password') }}</p>
            @endif
        </div>
        <button type="submit" class="qr-btn" style="background: var(--qr-primary); color: white;">
            {{ __('Unlock') }}
        </button>
    </form>
</div>
@endsection
