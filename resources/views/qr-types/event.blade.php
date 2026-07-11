@extends('qr-types.layout', ['title' => $title ?? __('Event')])

@section('content')
<div class="bg-white rounded-xl shadow-sm p-8">
    <div class="mb-6 text-center">
        <svg class="w-12 h-12 mx-auto text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold mb-6">{{ $title }}</h1>

    <div class="space-y-3 bg-gray-50 rounded-lg p-4 mb-6">
        @if($start)
        <div>
            <span class="text-sm text-gray-500">{{ __('Starts') }}</span>
            <p class="font-semibold">{{ $start }}</p>
        </div>
        @endif
        @if($end)
        <div>
            <span class="text-sm text-gray-500">{{ __('Ends') }}</span>
            <p class="font-semibold">{{ $end }}</p>
        </div>
        @endif
        @if($location)
        <div>
            <span class="text-sm text-gray-500">{{ __('Location') }}</span>
            <p class="font-semibold">{{ $location }}</p>
        </div>
        @endif
        @if($description)
        <div>
            <span class="text-sm text-gray-500">{{ __('Description') }}</span>
            <p>{{ $description }}</p>
        </div>
        @endif
    </div>

    @if($icsUrl)
    <a href="{{ $icsUrl }}" download="event.ics"
       class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition">
        {{ __('Add to Calendar') }}
    </a>
    @endif
</div>
@endsection
