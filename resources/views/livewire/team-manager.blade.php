<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Team & Roles') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Owner / Manager / Member permissions for Business accounts.') }}</p>
                </div>
                @if ($message)
                    <div class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">
                        {{ $message }}
                    </div>
                @endif
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if (! $this->isBusiness())
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    {{ __('Team management is available on the Business plan only.') }}
                </div>
            @endif

            @if (! $team)
                <div class="space-y-4">
                    <label class="block text-sm font-medium text-gray-700" for="team-name">{{ __('Team name') }}</label>
                    <input id="team-name" type="text" wire:model="teamName" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" @disabled(! $this->isBusiness())>
                    @error('teamName') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <button wire:click="createTeam" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:bg-gray-300" @disabled(! $this->isBusiness())>
                        {{ __('Create team') }}
                    </button>
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $team->name }}</h4>
                            <p class="text-sm text-gray-500">{{ __('Slug') }}: {{ $team->slug }}</p>
                            <p class="text-sm text-gray-500">{{ __('Owner') }}: {{ $team->owner?->name }}</p>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="branding-name">{{ __('Branding name') }}</label>
                                <input id="branding-name" type="text" wire:model="brandingName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="branding-logo">{{ __('Branding logo path or URL') }}</label>
                                <input id="branding-logo" type="text" wire:model="brandingLogoPath" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="whiteLabelEnabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                {{ __('Enable white-label mode') }}
                            </label>

                            <button wire:click="saveBranding" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                {{ __('Save branding settings') }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-lg border border-gray-200 p-4">
                        <h4 class="font-semibold text-gray-900">{{ __('Invite member') }}</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="member-email">{{ __('Member email') }}</label>
                            <input id="member-email" type="email" wire:model="memberEmail" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('memberEmail') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="member-role">{{ __('Role') }}</label>
                            <select id="member-role" wire:model="memberRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="member">{{ __('Member') }}</option>
                                <option value="manager">{{ __('Manager') }}</option>
                            </select>
                        </div>
                        <button wire:click="inviteMember" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            {{ __('Invite') }}
                        </button>

                        <div class="pt-4">
                            <h4 class="mb-3 font-semibold text-gray-900">{{ __('Members') }}</h4>
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2 text-left">{{ __('Name') }}</th>
                                            <th class="px-4 py-2 text-left">{{ __('Role') }}</th>
                                            <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white text-sm">
                                        @foreach ($members as $member)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900">{{ $member->user?->name }}</div>
                                                    <div class="text-gray-500">{{ $member->user?->email }}</div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select wire:change="updateMemberRole({{ $member->id }}, $event.target.value)" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="member" @selected($member->role === 'member')>{{ __('Member') }}</option>
                                                        <option value="manager" @selected($member->role === 'manager')>{{ __('Manager') }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <button wire:click="removeMember({{ $member->id }})" wire:confirm="{{ __('Remove this member?') }}" class="text-sm font-medium text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-6 text-sm text-gray-600">
        <p class="font-medium text-gray-900">{{ __('White-label preview') }}</p>
        <p class="mt-1">{{ $team?->white_label_enabled ? __('Enabled') : __('Disabled') }} — {{ $team?->branding_name ?? __('No branding configured yet') }}</p>
    </div>
</div>
