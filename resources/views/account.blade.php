<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Profile information (name, email) + email verification trigger. -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <!-- Password change (requires current password — re-auth guard). -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <!-- Tariff / plan display + Billing/Upgrade links (P2-T05). -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.plan-card />
                </div>
            </div>

            <!-- Team / Roles + White-label (M4). -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-5xl">
                    <livewire:team-manager />
                </div>
            </div>

            <!-- API tokens (Business only). -->
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

            <!-- Bulk import/export (Business only). -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-5xl space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Bulk Import / Export') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Upload CSV files or export all QR codes as CSV.') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('account.bulk-import') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                {{ __('Open Import Tool') }}
                            </a>
                            <a href="{{ route('account.qr-export') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                {{ __('Export CSV') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete account (requires password — re-auth guard). -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
