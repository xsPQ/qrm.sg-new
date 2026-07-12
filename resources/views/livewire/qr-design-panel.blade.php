@php
    // Shared visual-design panel (M5-T04 / FEAT-04).
    // Included by both QrCreator and QrCodeEditor. Both components expose:
    //   $style, $showStylePanel, $logoUpload
    //   computed: canUseGradient, canUseLogo, canUsePremiumEc
    // and the saveLogo()/removeLogo() actions.

    $canUseGradient = $this->canUseGradient;
    $canUseLogo = $this->canUseLogo;
    $canUsePremiumEc = $this->canUsePremiumEc;

    $dotStyles = [
        'square' => __('Square'),
        'round' => __('Round'),
        'extra_round' => __('Extra-Round'),
    ];

    $ecLevels = [
        'L' => __('L — Low (7%)'),
        'M' => __('M — Medium (15%)'),
        'Q' => __('Q — Quartile (25%)'),
        'H' => __('H — High (30%)'),
    ];

    $premiumEcLevels = ['Q', 'H'];

    // Gradient state: the toggle is backed by the presence of $style['gradient'].
    $gradientEnabled = is_array($style['gradient'] ?? null) && !empty($style['gradient']);
    $gradientFrom = $style['gradient']['from'] ?? '#1a1a2e';
    $gradientTo = $style['gradient']['to'] ?? '#e94560';
    $gradientAngle = $style['gradient']['angle'] ?? 0;
@endphp

<div class="mt-6 border-t border-gray-100 pt-6">
    @php $styleToggleIconClass = $showStylePanel ? 'h-4 w-4 transition rotate-90' : 'h-4 w-4 transition'; @endphp
    <button type="button" wire:click="$toggle('showStylePanel')" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">
        <svg class="{{ $styleToggleIconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        {{ __('Design') }}
    </button>

    @if ($showStylePanel)
        <div class="mt-4 space-y-5">
            {{-- Colors (all plans) --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="style-fg-color" class="block text-sm font-medium text-gray-700">{{ __('Foreground color') }}</label>
                    <div class="mt-1 flex items-center gap-3">
                        <input id="style-fg-color"
                               type="color"
                               wire:model.live="style.fg_color"
                               class="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1"
                               aria-label="{{ __('Foreground color') }}">
                        <input type="text"
                               wire:model.live="style.fg_color"
                               class="block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               pattern="#[0-9a-fA-F]{6}"
                               maxlength="7">
                    </div>
                </div>

                <div>
                    <label for="style-bg-color" class="block text-sm font-medium text-gray-700">{{ __('Background color') }}</label>
                    <div class="mt-1 flex items-center gap-3">
                        <input id="style-bg-color"
                               type="color"
                               wire:model.live="style.bg_color"
                               class="h-10 w-16 cursor-pointer rounded border border-gray-300 p-1"
                               aria-label="{{ __('Background color') }}">
                        <input type="text"
                               wire:model.live="style.bg_color"
                               class="block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               pattern="#[0-9a-fA-F]{6}"
                               maxlength="7">
                    </div>
                </div>
            </div>

            {{-- Dot style (all plans) --}}
            <div>
                <label for="style-dot-style" class="block text-sm font-medium text-gray-700">{{ __('Dot style') }}</label>
                <select id="style-dot-style"
                        wire:model.live="style.dot_style"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($dotStyles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Error correction (L, M: all plans; Q, H: Pro+) --}}
            <div>
                <label for="style-ec" class="block text-sm font-medium text-gray-700">{{ __('Error correction') }}</label>
                <select id="style-ec"
                        wire:model.live="style.error_correction"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @if(! $canUsePremiumEc) onchange="if(['Q','H'].includes(this.value)){this.value='M';}" @endif>
                    @foreach ($ecLevels as $value => $label)
                        <option value="{{ $value }}"
                                @if(in_array($value, $premiumEcLevels) && ! $canUsePremiumEc) disabled @endif>
                            {{ $label }}
                            @if(in_array($value, $premiumEcLevels) && ! $canUsePremiumEc) 🔒 @endif
                        </option>
                    @endforeach
                </select>
                @if(! $canUsePremiumEc)
                    <p class="mt-1 text-xs text-gray-400">
                        🔒 <span class="font-semibold text-indigo-600">Pro</span>
                        — {{ __('Q and H levels are available on Pro and Business plans.') }}
                    </p>
                @endif
            </div>

            {{-- Margin (all plans) --}}
            <div>
                <label for="style-margin" class="flex items-center justify-between text-sm font-medium text-gray-700">
                    <span>{{ __('Margin (quiet zone)') }}</span>
                    <span class="text-gray-500">{{ $style['margin'] ?? 10 }}px</span>
                </label>
                <input id="style-margin"
                       type="range"
                       min="0" max="50"
                       wire:model.live="style.margin"
                       class="mt-2 w-full accent-indigo-600">
            </div>

            {{-- Gradient (Pro+ only) --}}
            <div class="rounded-lg border border-gray-200 p-4 @if(! $canUseGradient) bg-gray-50 opacity-75 @endif">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm font-medium @if(! $canUseGradient) text-gray-400 @else text-gray-700 @endif">
                        @if($canUseGradient)
                            <input type="checkbox"
                                   wire:click="$toggle('style.gradient')"
                                   @if($gradientEnabled) checked @endif
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        @else
                            <span class="text-base">🔒</span>
                        @endif
                        {{ __('Gradient') }}
                    </label>
                    @if(! $canUseGradient)
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">Pro</span>
                    @endif
                </div>

                @if($canUseGradient && $gradientEnabled)
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('From') }}</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color"
                                       wire:model.live="style.gradient.from"
                                       class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-1">
                                <span class="text-sm text-gray-500">{{ $style['gradient']['from'] ?? '' }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('To') }}</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color"
                                       wire:model.live="style.gradient.to"
                                       class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-1">
                                <span class="text-sm text-gray-500">{{ $style['gradient']['to'] ?? '' }}</span>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center justify-between text-xs font-medium text-gray-600">
                                <span>{{ __('Angle') }}</span>
                                <span class="text-gray-500">{{ $style['gradient']['angle'] ?? 0 }}°</span>
                            </label>
                            <input type="range"
                                   min="0" max="360"
                                   wire:model.live="style.gradient.angle"
                                   class="mt-2 w-full accent-indigo-600">
                        </div>
                    </div>
                @elseif(! $canUseGradient)
                    <p class="mt-2 text-xs text-gray-400">
                        {{ __('Add a color gradient to your QR code foreground.') }}
                        <a href="{{ route('account') }}" class="text-indigo-600 underline hover:text-indigo-500">{{ __('Upgrade to Pro') }}</a>
                    </p>
                @endif
            </div>

            {{-- Logo upload (Pro+ only) --}}
            <div class="rounded-lg border border-gray-200 p-4 @if(! $canUseLogo) bg-gray-50 opacity-75 @endif">
                <label class="flex items-center gap-2 text-sm font-medium @if(! $canUseLogo) text-gray-400 @else text-gray-700 @endif">
                    @if(! $canUseLogo)
                        <span class="text-base">🔒</span>
                    @endif
                    {{ __('Logo') }}
                    @if(! $canUseLogo)
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">Pro</span>
                    @endif
                </label>

                @if($canUseLogo)
                    @if(! empty($style['logo_path']))
                        <div class="mt-2 flex items-center gap-3">
                            <span class="text-sm text-gray-500">{{ __('Logo uploaded.') }}</span>
                            <button type="button"
                                    wire:click="removeLogo"
                                    class="text-xs text-red-600 hover:text-red-500 underline">
                                {{ __('Remove logo') }}
                            </button>
                        </div>
                    @else
                        <input type="file"
                               wire:model="logoUpload"
                               accept="image/png,image/jpeg,image/svg+xml"
                               class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-400">{{ __('PNG, JPEG or SVG. Max 1 MB.') }}</p>
                        <div wire:loading wire:target="logoUpload" class="mt-2 text-sm text-indigo-600">{{ __('Uploading…') }}</div>
                    @endif
                @else
                    <p class="mt-2 text-xs text-gray-400">
                        {{ __('Embed your logo in the center of the QR code.') }}
                        <a href="{{ route('account') }}" class="text-indigo-600 underline hover:text-indigo-500">{{ __('Upgrade to Pro') }}</a>
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>
