@component('mail::message')
# {{ __('Your QR code is expiring soon') }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('Your QR code ":title" will expire in :days day(s) — on :date.', [
    'title' => $qrTitle,
    'days' => max(0, $daysRemaining),
    'date' => $expiresAt?->format('Y-m-d'),
]) }}

{{ __('Free QR codes are active for 30 days. Upgrade to Pro or Business to keep your codes online permanently and create more.') }}

@component('mail::button', ['url' => $upgradeUrl])
{{ __('Upgrade your plan') }}
@endcomponent

@component('mail::panel')
{{ __('Tip: After expiry, your QR code stops resolving. Upgrade before :date to avoid downtime.', ['date' => $expiresAt?->format('Y-m-d')]) }}
@endcomponent

{{ __('Regards,') }}
{{ config('app.name') }}
@endcomponent
