@extends('qr-types.layout', ['title' => $title])

@section('content')
<div class="qr-card-header">
    <h1 class="qr-title">{{ $title }}</h1>
    @if($description)
    <p class="qr-subtitle">{{ $description }}</p>
    @endif
</div>
<div class="qr-card-body">
    @if(!empty($links))
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        @foreach($links as $link)
        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
           style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.625rem; border: 1px solid var(--qr-border); text-decoration: none; transition: all 0.15s; color: var(--qr-text);"
           onmouseover="this.style.borderColor='var(--qr-primary)'; this.style.background='var(--qr-primary-light)'"
           onmouseout="this.style.borderColor='var(--qr-border)'; this.style.background='white'">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: var(--qr-primary-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="20" height="20" fill="var(--qr-primary)" viewBox="0 0 24 24">
                    <path d="{{ $link['icon'] }}"/>
                </svg>
            </div>
            <span style="font-weight: 500;">{{ $link['label'] ?? $link['platform'] }}</span>
            <svg style="margin-left: auto; color: var(--qr-muted);" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endforeach
    </div>
    @else
    <p style="text-align: center; color: var(--qr-muted);">{{ __('No social links configured.') }}</p>
    @endif
</div>
@endsection
