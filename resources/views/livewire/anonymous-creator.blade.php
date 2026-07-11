<div class="max-w-2xl mx-auto">
    @if ($createdCode)
        <!-- Success state -->
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Your QR Code is ready!') }}</h3>
                <p class="text-gray-500 text-sm">{{ __('Scan URL:') }} <a href="{{ $this->url }}" target="_blank" class="text-indigo-600 hover:underline break-all">{{ $this->url }}</a></p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($createdUrl) }}"
                     alt="QR Code" class="mx-auto rounded-lg" width="240" height="240" />
                <p class="mt-3 font-mono text-lg font-semibold text-gray-900">{{ $createdUrl }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('Valid for 24 hours') }}</p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 text-left">
                <h4 class="font-semibold text-gray-900 mb-2">📋 {{ __('Save this code permanently') }}</h4>
                <ul class="text-sm text-gray-600 space-y-1 mb-4">
                    <li>✅ {{ __('Change the target URL anytime without re-printing') }}</li>
                    <li>✅ {{ __('Track scans, devices and countries') }}</li>
                    <li>✅ {{ __('Set expiry dates and password protection') }}</li>
                    <li>✅ {{ __('No watermark on your QR codes') }}</li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition">
                    {{ __('Register to keep this code →') }}
                </a>
            </div>

            <button wire:click="$set('createdCode', null)" type="button"
                    class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Create another QR code') }}
            </button>
        </div>
    @else
        <!-- Form state -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ __('Create a QR Code — Free') }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ __('No registration required. Try it now.') }}</p>

            <form wire:submit="create">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Label (optional)') }}</label>
                    <input type="text" wire:model="title" placeholder="e.g. Birthday Party"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Destination URL') }} *</label>
                    <input type="url" wire:model="url" placeholder="https://example.com"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('url') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition disabled:opacity-50"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Create QR Code →') }}</span>
                    <span wire:loading>{{ __('Creating…') }}</span>
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-4">
                {{ __('Free QR codes expire after 24 hours and include a qrm.sg watermark.') }}<br>
                <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">{{ __('Register for permanent codes →') }}</a>
            </p>
        </div>
    @endif
</div>