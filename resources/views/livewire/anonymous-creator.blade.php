@php
    $typeOptions = $this->typeOptions();
    $fields = $this->fields();
    $dataUri = $this->previewDataUri();
    $previewPayload = $this->previewPayload();
@endphp

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if ($createdCode)
            <div class="mb-6 rounded-lg border border-green-300 bg-green-50 p-4 shadow-sm" role="status">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-sm text-green-800">
                        <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>
                            <span class="font-semibold">{{ __('QR code created.') }}</span>
                            @if (!empty($createdUrl))
                                <a href="{{ $createdUrl }}" target="_blank" rel="noopener" class="ml-1 underline">{{ $createdUrl }}</a>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Your QR Code is ready!') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Scan URL:') }}
                        <a href="{{ $createdUrl }}" target="_blank" class="text-indigo-600 hover:underline break-all">{{ $createdUrl }}</a>
                    </p>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 mb-6">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($createdUrl) }}"
                         alt="QR Code" class="mx-auto rounded-lg" width="240" height="240" />
                    <p class="mt-3 font-mono text-lg font-semibold text-gray-900">{{ $createdUrl }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('Valid for 15 minutes') }}</p>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 text-left">
                    <h4 class="font-semibold text-gray-900 mb-2">📋 {{ __('Save this code permanently') }}</h4>
                    <ul class="text-sm text-gray-600 space-y-1 mb-4">
                        <li>✅ {{ __('Change the target anytime without re-printing') }}</li>
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
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ __('Create a QR Code — Free') }}</h3>
                <p class="text-sm text-gray-500 mb-6">{{ __('No registration required. Try it now. Codes expire after 15 minutes.') }}</p>

                <form wire:submit="create" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-8">
                        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                1. {{ __('Choose a type') }}
                            </h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" role="radiogroup" aria-label="{{ __('QR type') }}">
                                @foreach ($typeOptions as $option)
                                    @php
                                        $selected = $type === $option['value'];
                                        $cardClass = $selected
                                            ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500'
                                            : 'border-gray-200 bg-white hover:border-gray-300';
                                        $iconClass = $selected ? 'text-indigo-600' : 'text-gray-600';
                                    @endphp
                                    <button type="button"
                                            wire:click="$set('type', '{{ $option['value'] }}')"
                                            role="radio"
                                            aria-checked="{{ $selected ? 'true' : 'false' }}"
                                            class="flex flex-col items-center gap-2 rounded-lg border p-4 text-center transition {{ $cardClass }}">
                                        <span class="flex h-8 w-8 items-center justify-center {{ $iconClass }}">
                                            <x-svg-icon :name="$option['icon']" class="h-6 w-6" />
                                        </span>
                                        <span class="text-sm font-medium text-gray-800">{{ $option['label'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $option['description'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                2. {{ __('Details') }}
                            </h3>

                            <div class="mb-4">
                                <x-input-label for="title" :value="__('Title')" />
                                <x-text-input id="title" wire:model="title" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Summer campaign') }}" autocomplete="off" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($fields as $field)
                                    @php
                                        $key = $field['key'];
                                        $inputType = $field['input'];
                                        $required = !empty($field['required']);
                                        $errorKey = 'content.' . $key;
                                        $label = $field['label'] . ($required ? ' *' : '');
                                        $placeholder = $field['placeholder'] ?? '';
                                        $maxlength = !empty($field['maxlength']) ? 'maxlength="' . e((string) $field['maxlength']) . '"' : '';
                                        $span = $inputType === 'checkbox' || $inputType === 'textarea' ? 'sm:col-span-2' : '';
                                    @endphp

                                    @if ($inputType === 'checkbox')
                                        <label class="flex items-center gap-3 {{ $span }}">
                                            <input type="checkbox" wire:model="content.{{ $key }}"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm text-gray-700">{{ $field['label'] }}</span>
                                        </label>
                                        <x-input-error :messages="$errors->get($errorKey)" class="{{ $span }}" />
                                    @elseif ($inputType === 'textarea')
                                        <div class="{{ $span }}">
                                            <x-input-label :value="$label" />
                                            <textarea wire:model="content.{{ $key }}"
                                                      rows="4"
                                                      {!! $maxlength !!}
                                                      placeholder="{{ $placeholder }}"
                                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                            <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
                                        </div>
                                    @else
                                        <div>
                                            <x-input-label :value="$label" />
                                            @if ($inputType === 'select')
                                                <select wire:model="content.{{ $key }}"
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    @foreach (($field['options'] ?? []) as $optValue => $optLabel)
                                                        <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ $inputType }}"
                                                       wire:model="content.{{ $key }}"
                                                       class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                       placeholder="{{ $placeholder }}"
                                                       {!! $maxlength !!}
                                                       autocomplete="off" />
                                            @endif
                                            <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    </div>

                    <aside class="lg:col-span-1">
                        <div class="lg:sticky lg:top-8 space-y-6">
                            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                    {{ __('Live preview') }}
                                </h3>

                                <div class="flex flex-col items-center gap-3">
                                    @if ($dataUri)
                                        <img src="{{ $dataUri }}" alt="{{ __('QR preview') }}" class="h-48 w-48 rounded-lg border border-gray-100 bg-white p-2" />
                                    @else
                                        <div class="flex h-48 w-48 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-center text-xs text-gray-400">
                                            {{ __('Fill in the details to see a preview') }}
                                        </div>
                                    @endif

                                    <p class="break-all text-center text-xs text-gray-500">{{ $previewPayload ?: __('Preview appears here') }}</p>
                                </div>
                            </section>

                            <x-primary-button class="w-full justify-center">
                                <span wire:loading.remove wire:target="create">{{ __('Create QR code') }}</span>
                                <span wire:loading wire:target="create">{{ __('Creating…') }}</span>
                            </x-primary-button>

                            <p class="text-center text-xs text-gray-400">
                                {{ __('Free QR codes expire after 15 minutes and include a qrm.sg watermark.') }}<br>
                                <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">{{ __('Register for permanent codes →') }}</a>
                            </p>
                        </div>
                    </aside>
                </form>
            </div>
        @endif
    </div>
</div>
