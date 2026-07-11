@extends('qr-types.layout', ['title' => $title, 'theme' => $theme])

@section('content')
<div class="text-center space-y-6">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100">
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
    </div>

    @if($title)
        <h1 class="text-2xl font-bold">{{ $title }}</h1>
    @endif

    @if($body)
        <div class="prose max-w-none @if($theme === 'dark') prose-invert @endif">
            <p class="text-lg whitespace-pre-wrap">{{ $body }}</p>
        </div>
    @endif

    @if($qrCode->title)
        <p class="text-sm text-gray-500">-- {{ $qrCode->title }}</p>
    @endif
</div>
@endsection
