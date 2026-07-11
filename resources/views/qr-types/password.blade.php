<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Password Required') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-md mx-auto px-4 py-16">
        <div class="space-y-6">
            <div class="text-center space-y-3">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold">{{ __('Password Protected') }}</h1>
                <p class="text-gray-500">{{ __('This QR code requires a password to view.') }}</p>
            </div>

            <form method="POST" action="{{ route('qr.scan.password', $qrCode->route?->code ?? '') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Password') }}</label>
                    <input type="password"
                           name="password"
                           id="password"
                           required
                           autofocus
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-3 border @error('password') border-red-300 @enderror"
                           placeholder="{{ __('Enter password') }}">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full rounded-lg bg-blue-600 text-white px-4 py-3 font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    {{ __('Unlock') }}
                </button>
            </form>
        </div>
    </main>
</body>
</html>
