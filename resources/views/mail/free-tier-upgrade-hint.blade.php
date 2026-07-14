@component('mail::message')
# {{ __('You have reached your Free plan limit') }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('You are using :active of :limit active QR codes on the Free plan. To create more QR codes and unlock permanent (non-expiring) codes, custom aliases and password protection, upgrade to Pro or Business.', [
    'active' => $activeCount,
    'limit' => $limit,
]) }}

@component('mail::button', ['url' => $upgradeUrl])
{{ __('Upgrade your plan') }}
@endcomponent

{{ __('If you do not want to upgrade right now, you can free up a slot by expiring or removing an existing QR code.') }}

{{ __('Regards,') }}
{{ config('app.name') }}
@endcomponent
