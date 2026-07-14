<x-slot name="header">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('API Tokens') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Manage personal access tokens for API and integrations.') }}
            </p>
        </div>

        @if (! $this->canManageTokens())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
                {{ __('Creating API tokens is available on the Business plan only.') }}
            </div>
        @endif
    </div>
</x-slot>

<div class="py-12">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if ($plainTextToken)
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900">
                <p class="font-semibold">{{ __('Token created') }}</p>
                <p class="mt-1">{{ __('Copy this token now. You will not be able to see it again.') }}</p>
                <code class="mt-3 block break-all rounded-md bg-white px-3 py-2 text-sm text-gray-800 ring-1 ring-inset ring-green-200">{{ $plainTextToken }}</code>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('Existing Tokens') }}
                    </h3>
                </div>

                @if ($tokens->isEmpty())
                    <div class="px-6 py-10 text-sm text-gray-500">
                        {{ __('No API tokens have been created yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Scopes') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Created') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Last Used') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($tokens as $token)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $token->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            @if ($token->abilities === ['*'])
                                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
                                                    {{ __('All scopes') }}
                                                </span>
                                            @elseif (empty($token->abilities))
                                                <span class="text-gray-400">{{ __('No scopes') }}</span>
                                            @else
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($token->abilities as $ability)
                                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                                            {{ $ability }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $token->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <button
                                                type="button"
                                                wire:click="deleteToken({{ $token->id }})"
                                                wire:confirm="{{ __('Delete this token?') }}"
                                                class="font-medium text-red-600 hover:text-red-500"
                                            >
                                                {{ __('Delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('Create Token') }}
                    </h3>
                </div>

                <form wire:submit.prevent="createToken" class="space-y-4 px-6 py-6">
                    <div>
                        <label for="token-name" class="block text-sm font-medium text-gray-700">{{ __('Token Name') }}</label>
                        <input
                            id="token-name"
                            type="text"
                            wire:model.live="name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="{{ __('e.g. Zapier integration') }}"
                            @disabled(! $this->canManageTokens())
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="token-abilities" class="block text-sm font-medium text-gray-700">{{ __('Abilities / Scopes') }}</label>
                        <select
                            id="token-abilities"
                            wire:model.live="abilities"
                            multiple
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            size="5"
                            @disabled(! $this->canManageTokens())
                        >
                            @foreach ($availableAbilities as $ability => $label)
                                <option value="{{ $ability }}">{{ $ability }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Hold Ctrl / Cmd to select multiple scopes.') }}</p>
                        @error('abilities.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                        @disabled(! $this->canManageTokens())
                    >
                        {{ __('Create Token') }}
                    </button>
                </form>

                <div class="border-t border-gray-100 px-6 py-4 text-xs text-gray-500">
                    {{ __('Existing tokens remain active even if a user is not on the Business plan anymore.') }}
                </div>
            </div>
        </div>
    </div>
</div>
