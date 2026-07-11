@extends('qr-types.layout', ['title' => $title, 'theme' => $theme])

@section('content')
<div class="space-y-8">
    <div class="text-center space-y-3">
        <h1 class="text-2xl font-bold">{{ $title }}</h1>
        @if($description)
            <p class="text-gray-500 @if($theme === 'dark') text-gray-400 @endif">{{ $description }}</p>
        @endif
    </div>

    <div class="@if($layout === 'list') space-y-3 @else grid grid-cols-2 sm:grid-cols-3 gap-4 @endif">
        @foreach($links as $link)
            <a href="{{ $link['url'] }}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex flex-col items-center gap-3 p-4 rounded-xl border border-gray-200 @if($theme === 'dark') border-gray-700 hover:bg-gray-800 @else hover:bg-gray-100 @endif transition-colors group">
                <div class="w-12 h-12 rounded-full bg-gray-100 @if($theme === 'dark') bg-gray-700 @endif flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 @if($theme === 'dark') text-gray-300 @else text-gray-700 @endif" fill="currentColor" viewBox="0 0 24 24">
                        <path d="{{ $link['icon'] }}"/>
                    </svg>
                </div>
                <span class="text-sm font-medium capitalize">{{ $link['label'] ?? $link['platform'] }}</span>
            </a>
        @endforeach
    </div>

    @if(empty($links))
        <p class="text-center text-gray-400">{{ __('No social links configured.') }}</p>
    @endif
</div>
@endsection
