@extends('qr-types.layout', ['title' => $name])

@section('content')
<div class="bg-white rounded-xl shadow-sm p-8">
    <div class="mb-6 text-center">
        <svg class="w-12 h-12 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold mb-2 text-center">{{ $name }}</h1>

    <div class="space-y-3 bg-gray-50 rounded-lg p-4 mb-6">
        @if($email)
        <div>
            <span class="text-sm text-gray-500">{{ __('Email') }}</span>
            <p class="font-semibold">{{ $email }}</p>
        </div>
        @endif
        @if($phone)
        <div>
            <span class="text-sm text-gray-500">{{ __('Phone') }}</span>
            <p class="font-semibold">{{ $phone }}</p>
        </div>
        @endif
        @if($organization)
        <div>
            <span class="text-sm text-gray-500">{{ __('Organization') }}</span>
            <p class="font-semibold">{{ $organization }}</p>
        </div>
        @endif
    </div>

    @if($vcardUrl)
    <a href="{{ $vcardUrl }}" download="contact.vcf"
       class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition">
        {{ __('Save to Contacts') }}
    </a>
    @endif
</div>
@endsection
