<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'qrm.sg') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-indigo-700 focus:shadow-lg">
            {{ __('Skip to main content') }}
        </a>

        <!-- Top nav -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                <a href="/" wire:navigate class="flex items-center gap-2 text-indigo-600 font-bold text-xl">
                    <x-application-logo class="w-8 h-8 fill-current text-indigo-600" />
                    qrm.sg
                </a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('login') }}" class="text-indigo-600 font-semibold text-sm hover:bg-indigo-50 px-3 py-2 rounded-lg transition">Login</a>
                    <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-4 py-2 rounded-lg transition">Sign Up Free</a>
                </div>
            </div>
        </nav>

        <main id="main-content" tabindex="-1" class="w-full">
            {{ $slot }}
        </main>
    </body>
</html>
