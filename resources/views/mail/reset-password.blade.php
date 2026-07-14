@component('mail::message')
# {{ __('Reset Password Notification') }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('You are receiving this email because we received a password reset request for your account.') }}

@component('mail::button', ['url' => $resetUrl])
{{ __('Reset Password') }}
@endcomponent

{{ __('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire', 60)]) }}

{{ __('If you did not request a password reset, no further action is required.') }}

{{ __('Regards,') }}
{{ config('app.name') }}
@endcomponent
