<?php

declare(strict_types=1);

namespace Tests\Support\Billing;

use Stripe\WebhookSignature;

/**
 * Builds Stripe webhook signature headers for tests (P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * Mirrors {@see WebhookSignature::verifyHeader}: the signature is
 * HMAC-SHA256(secret, "{timestamp}.{payload}"), and the header is
 * <code>t={timestamp},v1={signature}</code>.
 */
final class StripeSignature
{
    public static function header(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
