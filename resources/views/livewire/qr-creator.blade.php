@php
    $typeOptions = $this->typeOptions();
    $fields = $this->fields();

    $limitReached = $this->limitReached;
    $dataUri = $this->previewDataUri();
    $previewPayload = $this->previewPayload();

    $freeTierLimit = $freeTier['limit'] ?? null;
        $showFreeTierHint = $freeTierLimit !== null;
        $remaining = $freeTier['remaining'] ?? null;
        $showRemaining = $remaining !== null || array_key_exists('remaining', $freeTier);
        $remainingClass = ($remaining ?? 1) <= 0 ? 'text-red-600' : 'text-gray-500';

        $buttonClass = $limitReached
            ? 'w-full justify-center opacity-50 cursor-not-allowed'
            : 'w-full justify-center';

        $appBaseUrl = rtrim((string) config('app.url'), '/');
        $aliasHint = __('4–32 chars, letters, numbers and hyphens. Reserved system paths are blocked.');
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if ($showFreeTierHint)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-600">
                            <span class="font-semibold uppercase tracking-wide text-gray-800">{{ ucfirst((string) ($freeTier['tier'] ?? 'free')) }}</span>
                            {{ __('plan') }}
                            &middot;
                            <span class="font-medium">{{ $freeTier['active_count'] ?? 0 }}</span>
                            /
                            <span class="font-medium">{{ $freeTierLimit }}</span>
                            {{ __('active QR codes') }}
                        </div>
                        @if ($showRemaining)
                            <span class="text-xs font-medium {{ $remainingClass }}">
                                {{ __('Remaining') }}:&nbsp;{{ $remaining }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            @if ($upgradeMessage)
                <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 shadow-sm" role="alert">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="text-sm text-amber-800">
                            <p class="font-semibold">{{ __('Upgrade required') }}</p>
                            <p class="mt-1">{{ $upgradeMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($created)
                <div class="mb-6 rounded-lg border border-green-300 bg-green-50 p-4 shadow-sm" role="status">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3 text-sm text-green-800">
                            <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>
                                <span class="font-semibold">{{ __('QR code created.') }}</span>
                                @if (!empty($created['url']))
                                    <a href="{{ $created['url'] }}" target="_blank" rel="noopener" class="ml-1 underline">{{ $created['url'] }}</a>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="submit" class="grid grid-cols-1 gap-8 lg:grid-cols-3">

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

                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <x-input-label for="alias" :value="__('Custom alias (optional)')" />
                            <div class="mt-1 flex items-stretch">
                                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                    {{ $appBaseUrl }}/
                                </span>
                                <input id="alias" type="text"
                                       wire:model.live.debounce.500ms="alias"
                                       minlength="4" maxlength="32"
                                       pattern="[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?"
                                       placeholder="{{ __('my-link') }}"
                                       class="block w-full rounded-r-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       autocomplete="off">
                            </div>

                            @if ($aliasStatus)
                                @php
                                    $aliasOk = $aliasStatus === 'available';
                                    $aliasTextClass = $aliasOk ? 'text-green-600' : 'text-red-600';
                                    $aliasIconPath = $aliasOk
                                        ? 'M5 13l4 4L19 7'
                                        : 'M6 18L18 6M6 6l12 12';
                                @endphp
                                <p class="mt-2 text-sm {{ $aliasTextClass }}">
                                    <svg class="mr-1 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $aliasIconPath }}"/></svg>
                                    {{ $aliasMessage }}
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('alias')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-400">{{ $aliasHint }}</p>
                        </div>

                        <div class="mt-6">
                            @php $toggleIconClass = $showAdvanced ? 'h-4 w-4 transition rotate-90' : 'h-4 w-4 transition'; @endphp
                            <button type="button" wire:click="$toggle('showAdvanced')" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                <svg class="{{ $toggleIconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                {{ __('Advanced settings') }}
                            </button>

                            @if ($showAdvanced)
                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="maxScans" :value="__('Max scans (optional)')" />
                                        <x-text-input id="maxScans" type="number" wire:model="maxScans" min="1" class="mt-1 block w-full" placeholder="{{ __('unlimited') }}" />
                                        <x-input-error :messages="$errors->get('maxScans')" class="mt-2" />
                                    </div>
                                    <div class="flex items-center gap-3 self-end pb-2">
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" wire:model="burn" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            {{ __('Burn after first scan') }}
                                        </label>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="rounded-md bg-gray-50 p-3 text-xs text-gray-500">
                                            {{ __('Password protection is managed after creation (P2-T13). Expiry is set automatically by your plan.') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Visual Design panel (M5-T04) --}}
                        @include('livewire.qr-design-panel')
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

                        <x-primary-button class="{{ $buttonClass }}" :disabled="$limitReached">
                            <span wire:loading.remove wire:target="submit">{{ __('Create QR code') }}</span>
                            <span wire:loading wire:target="submit">{{ __('Creating…') }}</span>
                        </x-primary-button>

                        @if ($limitReached)
                            <p class="text-center text-xs text-red-600">
                                {{ __('You have reached the active QR-code limit for your plan. Upgrade to create more.') }}
                            </p>
                        @endif
                    </div>
                </aside>
            </form>
        </div>
    </div>

