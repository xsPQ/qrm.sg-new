<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'profile' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Tab Navigation --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex flex-wrap gap-6" role="tablist">
                    <button @click="tab='profile'" :class="tab==='profile' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">
                        {{ __('Profile') }}
                    </button>
                    <button @click="tab='security'" :class="tab==='security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">
                        {{ __('Security') }}
                    </button>
                    <button @click="tab='billing'" :class="tab==='billing' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">
                        {{ __('Billing') }}
                    </button>
                    <button @click="tab='team'" :class="tab==='team' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">
                        {{ __('Team') }}
                    </button>
                    <button @click="tab='developer'" :class="tab==='developer' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">
                        {{ __('Developer') }}
                    </button>
                </nav>
            </div>

            {{-- Profile --}}
            <div x-show="tab==='profile'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.plan-card />
                    </div>
                </div>
            </div>

            {{-- Security --}}
            <div x-show="tab==='security'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>
            </div>

            {{-- Billing --}}
            <div x-show="tab==='billing'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.plan-card />
                    </div>
                </div>
            </div>

            {{-- Team --}}
            <div x-show="tab==='team'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-5xl">
                        <livewire:team-manager />
                    </div>
                </div>
            </div>

            {{-- Developer --}}
            <div x-show="tab==='developer'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-5xl space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('API Tokens') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('Business users can manage personal access tokens for integrations.') }}</p>
                            </div>
                            <a href="{{ route('account.api-tokens') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                {{ __('Open API Token Manager') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-5xl space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('Bulk Import / Export') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('Upload CSV files or export all QR codes as CSV.') }}</p>
                            </div>
                            <div class="flex gap-2">
                                @if (Route::has('bulk.import'))
                                    <a href="{{ route('bulk.import') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                        {{ __('Open Import Tool') }}
                                    </a>
                                @endif
                                @if (Route::has('bulk.export'))
                                    <a href="{{ route('bulk.export') }}" class="inline-flex items-center rounded-md border border-indigo-600 bg-white px-4 py-2 text-sm font-medium text-indigo-600 shadow-sm hover:bg-indigo-50">
                                        {{ __('Export CSV') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone (always visible) --}}
            <div class="mt-8 p-4 sm:p-8 bg-white shadow sm:rounded-lg border-t-2 border-red-200">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak]{display:none!important;}</style>
</x-app-layout>
