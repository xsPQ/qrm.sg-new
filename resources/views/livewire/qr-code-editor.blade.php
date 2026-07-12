@php
    use App\Enums\QrCodeType;

    $fields = $this->fields();
    $typeLabel = QrCodeType::tryFrom($this->type)?->label() ?? ucfirst($this->type);

    $appBaseUrl = rtrim((string) config('app.url'), '/');
    $aliasHint = $this->aliasTierHint();
    $aliasPlan = $this->aliasPlan();
    $aliasIsBusiness = $aliasPlan === 'business';

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
                           minlength="{{ $this->aliasMinLength() }}" maxlength="{{ $this->aliasMaxLength() }}"
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
                @if($aliasIsBusiness)
                    <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                        <span>Premium Shortcode</span>
                        <span>≤4 chars</span>
                    </div>
                @endif
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

            {{-- Design panel (M5-T04) --}}
            @include('livewire.qr-design-panel')

            {{-- A/B Testing section (M5-T06) — only for url/redirect --}}
            @if (in_array($qrCode->type, ['url', 'redirect']))
                <div class="mt-6 border-t border-gray-100 pt-6">
                    @php $abToggleIcon = ($showAbPanel ?? false) ? 'rotate-90' : ''; @endphp
                    <button type="button" wire:click="toggleAbPanel" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        <svg class="h-4 w-4 transition {{ $abToggleIcon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        {{ __('A/B Testing') }}
                        @if(! $this->canUseAbTesting)
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">Pro</span>
                        @endif
                    </button>

                    @if($showAbPanel ?? false)
                        @if($this->canUseAbTesting)
                            <div class="mt-4 space-y-4">
                                @if(!empty($abVariants))
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Label') }}</th>
                                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('URL') }}</th>
                                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Weight') }}</th>
                                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Device') }}</th>
                                                <th class="text-left py-2 px-3 text-gray-500 font-medium">{{ __('Scans') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($abVariants as $i => $variant)
                                                <tr class="border-b border-gray-100">
                                                    <td class="py-2 px-3 font-medium">{{ $variant['label'] ?? chr(65 + $i) }}</td>
                                                    <td class="py-2 px-3 text-gray-600 truncate max-w-xs">{{ $variant['url'] ?? '' }}</td>
                                                    <td class="py-2 px-3 text-gray-600">{{ $variant['weight'] ?? 1 }}</td>
                                                    <td class="py-2 px-3 text-gray-600">{{ $variant['device_target'] ?? '—' }}</td>
                                                    <td class="py-2 px-3 text-gray-600">{{ $variant['scan_count'] ?? 0 }}</td>
                                                    <td class="py-2 px-3 text-right">
                                                        <button type="button" wire:click="removeVariant({{ $i }})" class="text-red-600 hover:text-red-500 text-xs">{{ __('Remove') }}</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">{{ __('Label') }}</label>
                                        <input type="text" wire:model="newVariantLabel" placeholder="A" maxlength="10" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600">{{ __('URL') }}</label>
                                        <input type="url" wire:model="newVariantUrl" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">{{ __('Device') }}</label>
                                        <select wire:model="newVariantDeviceTarget" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">{{ __('Any') }}</option>
                                            <option value="mobile">{{ __('Mobile') }}</option>
                                            <option value="desktop">{{ __('Desktop') }}</option>
                                            <option value="tablet">{{ __('Tablet') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" wire:click="addVariant" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">{{ __('+ Add variant') }}</button>

                                <div class="flex items-center gap-3 pt-2">
                                    <button type="button" wire:click="saveVariants" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                        <span wire:loading.remove wire:target="saveVariants">{{ __('Save variants') }}</span>
                                        <span wire:loading wire:target="saveVariants">{{ __('Saving…') }}</span>
                                    </button>
                                    @if(!empty($abVariants))
                                        <button type="button" wire:click="clearAllVariants" class="text-sm text-red-600 hover:text-red-500">{{ __('Clear all') }}</button>
                                    @endif
                                </div>
                                @if($abSuccessMessage)
                                    <p class="text-sm text-green-600">{{ $abSuccessMessage }}</p>
                                @endif
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-400">
                                {{ __('A/B testing lets you redirect to different URLs and track which performs better.') }}
                                <a href="{{ route('account') }}" class="text-indigo-600 underline hover:text-indigo-500">{{ __('Upgrade to Pro') }}</a>
                            </p>
                        @endif
                    @endif
                </div>
            @endif

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
