@component('mail::message')
# {{ __('Verify Email Address') }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('Please click the button below to verify your email address.') }}

@component('mail::button', ['url' => $verificationUrl])
{{ __('Verify Email Address') }}
@endcomponent

{{ __('If you did not create an account, no further action is required.') }}

@component('mail::panel')
{{ __('This link expires after :minutes minutes.', ['minutes' => config('auth.verification.expire', 60)]) }}
@endcomponent

{{ __('Regards,') }}
{{ config('app.name') }}
@endcomponent
