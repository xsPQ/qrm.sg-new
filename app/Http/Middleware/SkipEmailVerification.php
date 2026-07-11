<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Skip email verification requirement in non-production environments
 * or when APP_SKIP_EMAIL_VERIFICATION is set.
 *
 * Pflichtenheft §3.4.1 requires email verification before sensitive
 * functions. For dev/staging/testing we bypass this with an env flag
 * so registration → dashboard works without a mail server.
 *
 * In production (APP_ENV=production), this middleware is a no-op
 * unless APP_SKIP_EMAIL_VERIFICATION=true is explicitly set.
 */
class SkipEmailVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip()) {
            $user = $request->user();
            if ($user && is_null($user->email_verified_at)) {
                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }

    private function shouldSkip(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return (bool) config('app.skip_email_verification', false);
    }
}