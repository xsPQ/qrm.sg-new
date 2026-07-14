@extends('qr-types.layout', ['title' => $title ?? __('Event')])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #ede9fe;">
        <svg style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </div>
    <h1 class="qr-title">{{ $title }}</h1>
</div>
<div class="qr-card-body">
    @if($start)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Starts') }}</div>
        <div class="qr-field-value">{{ $start }}</div>
    </div>
    @endif
    @if($end)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Ends') }}</div>
        <div class="qr-field-value">{{ $end }}</div>
    </div>
    @endif
    @if($location)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Location') }}</div>
        <div class="qr-field-value">{{ $location }}</div>
    </div>
    @endif
    @if($description)
    <div class="qr-field">
        <div class="qr-field-label">{{ __('Description') }}</div>
        <div class="qr-field-value" style="white-space: pre-wrap;">{{ $description }}</div>
    </div>
    @endif

    @if($icsUrl)
    <div style="margin-top: 1rem;">
        <a href="{{ $icsUrl }}" download="event.ics" class="qr-btn" style="background: #7c3aed; color: white;">
            {{ __('Add to Calendar') }}
        </a>
    </div>
    @endif
</div>
@endsection
