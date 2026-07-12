<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Billing\Plan;
use App\Enums\UserPlan;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $limit = $this->limitForUser($user);
        $key = 'api:'.$user->id.':'.$request->path();

        RateLimiter::hit($key, 60);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return $this->rateLimitedResponse($key, $limit);
        }

        $response = $next($request);

        return $this->withHeaders($response, $key, $limit);
    }

    private function limitForUser(User $user): int
    {
        return $this->currentPlan($user) === UserPlan::Business->value ? 300 : 60;
    }

    private function currentPlan(User $user): string
    {
        $subscription = $user->subscriptions()
            ->where('stripe_status', 'active')
            ->first();

        if ($subscription?->stripe_price) {
            $mapped = Plan::planForPrice($subscription->stripe_price);

            if ($mapped instanceof UserPlan) {
                return $mapped->value;
            }
        }

        $plan = strtolower((string) ($user->plan ?? UserPlan::Free->value));

        return in_array($plan, UserPlan::values(), true)
            ? $plan
            : UserPlan::Free->value;
    }

    private function rateLimitedResponse(string $key, int $limit): JsonResponse
    {
        $retryAfter = max(1, RateLimiter::availableIn($key));

        return response()->json([
            'message' => __('Too many requests. Please try again later.'),
            'code' => 'RATE_LIMIT_EXCEEDED',
        ], 429)
            ->header('Retry-After', (string) $retryAfter)
            ->withHeaders($this->headers($key, $limit));
    }

    /**
     * @return array<string, int|string>
     */
    private function headers(string $key, int $limit): array
    {
        return [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - RateLimiter::attempts($key)),
            'X-RateLimit-Reset' => now()->addSeconds(max(0, RateLimiter::availableIn($key)))->timestamp,
        ];
    }

    private function withHeaders(Response $response, string $key, int $limit): Response
    {
        foreach ($this->headers($key, $limit) as $name => $value) {
            $response->headers->set($name, (string) $value);
        }

        return $response;
    }
}
