@php
    use App\Enums\QrCodeType;

    $fields = $this->fields();
    $typeLabel = QrCodeType::tryFrom($this->type)?->label() ?? ucfirst($this->type);

    $appBaseUrl = rtrim((string) config('app.url'), '/');
    $aliasHint = __('4–32 chars, letters, numbers and hyphens. Reserved system paths are blocked.');

    $aliasOk = $aliasStatus === 'available';
    $aliasTextClass = $aliasStatus === 'available' ? 'text-green-600' : 'text-red-600';
    $aliasIconPath = $aliasOk
        ? 'M5 13l4 4L19 7'
        : 'M6 18L18 6M6 6l12 12';
@endphp

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

        @if ($successMessage)
            <div class="rounded-lg border border-green-300 bg-green-50 p-4 shadow-sm" role="status">
                <div class="flex items-center gap-3 text-sm text-green-800">
                    <svg class="h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-semibold">{{ $successMessage }}</span>
                </div>
            </div>
        @endif

        <form wire:submit="save" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('Details') }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ __('Type cannot be changed after creation.') }}
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                    {{ $typeLabel }}
                </span>
            </div>

            <div>
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

            <div class="border-t border-gray-100 pt-6">
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
                    <p class="mt-2 text-sm {{ $aliasTextClass }}">
                        <svg class="mr-1 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $aliasIconPath }}"/></svg>
                        {{ $aliasMessage }}
                    </p>
                @endif
                <x-input-error :messages="$errors->get('alias')" class="mt-2" />
                <p class="mt-1 text-xs text-gray-400">{{ $aliasHint }}</p>
            </div>

            <div>
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

                        <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                            <x-input-label for="password" :value="__('Password protection')" />
                            <x-text-input id="password" type="password" wire:model="password" class="mt-1 block w-full" placeholder="{{ __('Leave blank to keep the current password') }}" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="removePassword" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('Remove password protection') }}
                            </label>
                            <p class="mt-2 rounded-md bg-gray-50 p-3 text-xs text-gray-500">
                                {{ __('Expiry is set automatically by your plan and cannot be changed here.') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>
                    <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </x-primary-button>
            </div>
        </form>

        <section class="rounded-lg border border-red-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-red-600">
                {{ __('Danger zone') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Deleting a QR code hides it from your dashboard and stops it from resolving. This cannot be undone.') }}
            </p>

            @if ($confirmingDelete)
                <div class="mt-4 rounded-md border border-red-300 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-800">
                        {{ __('Are you sure you want to delete this QR code?') }}
                    </p>
                    <div class="mt-4 flex items-center justify-end gap-3">
                        <x-secondary-button wire:click="cancelDelete" type="button">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button wire:click="delete" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="delete">{{ __('Delete QR code') }}</span>
                            <span wire:loading wire:target="delete">{{ __('Deleting…') }}</span>
                        </x-danger-button>
                    </div>
                </div>
            @else
                <div class="mt-4">
                    <x-danger-button wire:click="confirmDelete" type="button">
                        {{ __('Delete QR code') }}
                    </x-danger-button>
                </div>
            @endif
        </section>
    </div>
</div>
