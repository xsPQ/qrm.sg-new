<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@if($reason === 'not_found') {{ __('Not Found') }} @else {{ __('QR Code Unavailable') }} @endif</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-md mx-auto px-4 py-16">
        <div class="text-center space-y-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full @if($reason === 'not_found') bg-gray-100 @else bg-red-100 @endif">
                @if($reason === 'not_found')
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                @endif
            </div>

            <h1 class="text-2xl font-bold">
                @switch($reason)
                    @case('not_found')
                        {{ __('QR Code Not Found') }}
                        @break
                    @case('expired')
                        {{ __('QR Code Expired') }}
                        @break
                    @case('burned')
                        {{ __('QR Code Used') }}
                        @break
                    @case('max_scans')
                        {{ __('Scan Limit Reached') }}
                        @break
                    @default
                        {{ __('QR Code Unavailable') }}
                @endswitch
            </h1>

            <p class="text-gray-500">
                @switch($reason)
                    @case('not_found')
                        {{ __('This QR code does not exist or has been removed.') }}
                        @break
                    @case('expired')
                        {{ __('This QR code has expired and is no longer active.') }}
                        @break
                    @case('burned')
                        {{ __('This single-use QR code has already been scanned.') }}
                        @break
                    @case('max_scans')
                        {{ __('This QR code has reached its maximum scan limit.') }}
                        @break
                    @default
                        {{ __('This QR code is currently unavailable.') }}
                @endswitch
            </p>
        </div>
    </main>
</body>
</html>
