@extends('qr-types.layout', ['title' => $title])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: #dbeafe;">
        <svg style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
    </div>
    @if($title)
        <h1 class="qr-title">{{ $title }}</h1>
    @endif
</div>
<div class="qr-card-body">
    @if($body)
        <p class="qr-field-value" style="white-space: pre-wrap; line-height: 1.6;">{{ $body }}</p>
    @endif

    <button class="qr-btn qr-btn-copy" onclick="
        navigator.clipboard.writeText(@json($body ?? ''));
        this.textContent = '{{ __('Copied!') }}';
        setTimeout(() => this.textContent = '{{ __('Copy text') }}', 2000);
    ">{{ __('Copy text') }}</button>
</div>
@endsection
