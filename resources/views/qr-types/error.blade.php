@extends('qr-types.layout', ['title' => $reason === 'not_found' ? __('Not Found') : __('QR Code Unavailable')])

@section('content')
<div class="qr-card-header">
    <div class="qr-icon-wrap" style="background: @if($reason === 'not_found') #f1f5f9 @else #fee2e2 @endif;">
        @if($reason === 'not_found')
            <svg style="color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @else
            <svg style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        @endif
    </div>
    <h1 class="qr-title" role="alert">
        @switch($reason)
            @case('not_found') {{ __('QR Code Not Found') }} @break
            @case('expired') {{ __('QR Code Expired') }} @break
            @case('burned') {{ __('QR Code Used') }} @break
            @case('max_scans') {{ __('Scan Limit Reached') }} @break
            @default {{ __('QR Code Unavailable') }}
        @endswitch
    </h1>
</div>
<div class="qr-card-body">
    <p style="text-align: center; color: var(--qr-muted); font-size: 0.95rem; margin: 0;">
        @switch($reason)
            @case('not_found') {{ __('This QR code does not exist or has been removed.') }} @break
            @case('expired') {{ __('This QR code has expired and is no longer active.') }} @break
            @case('burned') {{ __('This single-use QR code has already been scanned.') }} @break
            @case('max_scans') {{ __('This QR code has reached its maximum scan limit.') }} @break
            @default {{ __('This QR code is currently unavailable.') }}
        @endswitch
    </p>

    <div class="qr-error-actions" style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-top: 1.5rem;">
        <a href="{{ route('landing') }}" class="qr-btn qr-btn-primary">{{ __('Go to qrm.sg') }}</a>
        <a href="{{ route('landing') }}#help" class="qr-btn qr-btn-secondary">{{ __('Help &amp; Support') }}</a>
    </div>

    <p style="text-align: center; color: var(--qr-muted); font-size: 0.8rem; margin-top: 1.25rem;">
        {{ __('If you believe this is an error, please contact the person or business that gave you this QR code.') }}
    </p>
</div>
@endsection
