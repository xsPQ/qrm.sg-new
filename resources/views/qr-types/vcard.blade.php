@extends('qr-types.layout', ['title' => $name])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #d1fae5;">
        <svg style="color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
    </div>
    <h1 class="qr-title">{{ $name }}</h1>
    @if($organization)
    <p class="qr-subtitle">{{ $organization }}</p>
    @endif
</div>
<div class="qr-card-body">
    @if($email)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Email') }}</div>
        <a href="mailto:{{ $email }}" class="qr-field-value" style="color: var(--qr-primary);">{{ $email }}</a>
    </div>
    @endif
    @if($phone)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Phone') }}</div>
        <a href="tel:{{ $phone }}" class="qr-field-value" style="color: var(--qr-primary);">{{ $phone }}</a>
    </div>
    @endif

    @if($vcardUrl)
    <div style="margin-top: 1rem;">
        <a href="{{ $vcardUrl }}" download="contact.vcf" class="qr-btn" style="background: #059669; color: white;">
            {{ __('Save to Contacts') }}
        </a>
    </div>
    @endif
</div>
@endsection
