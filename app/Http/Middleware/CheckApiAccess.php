<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserPlan;
use App\Models\Subscription;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAccess
{
    private const API_ADDON_IDENTIFIER = 'pro_api_addon';

    /**
     * Allow the request only when the authenticated user has API access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->userHasApiAccess($user)) {
            return $this->denialResponse($user);
        }

        return $next($request);
    }

    public function userHasApiAccess(User $user): bool
    {
        $plan = $this->currentPlan($user);

        if ($plan === UserPlan::Business->value) {
            return true;
        }

        if ($plan !== UserPlan::Pro->value) {
            return false;
        }

        return $this->hasApiAddon($user);
    }

    /**
     * @return array{message: string, code: string, upgrade_required: bool}
     */
    public function denialPayload(?User $user): array
    {
        $plan = $user instanceof User ? $this->currentPlan($user) : UserPlan::Free->value;

        return [
            'message' => $plan === UserPlan::Pro->value
                ? 'API access requires the Pro API add-on (+1€/month) or Business plan.'
                : 'API access requires Pro with API add-on or Business plan.',
            'code' => 'API_ACCESS_DENIED',
            'upgrade_required' => true,
        ];
    }

    public function denialResponse(?User $user): JsonResponse
    {
        return response()->json($this->denialPayload($user), 403);
    }

    private function currentPlan(User $user): string
    {
        $plan = strtolower((string) ($user->plan ?? ''));

        if (in_array($plan, UserPlan::values(), true)) {
            return $plan;
        }

        if ($user->subscribed('business')) {
            return UserPlan::Business->value;
        }

        if ($user->subscribed('pro')) {
            return UserPlan::Pro->value;
        }

        return UserPlan::Free->value;
    }

    private function hasApiAddon(User $user): bool
    {
        if ((bool) ($user->api_addon_enabled ?? false)) {
            return true;
        }

        return Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->with('items')
            ->get()
            ->contains(function (Subscription $subscription): bool {
                return $subscription->items->contains(function ($item): bool {
                    return in_array($item->stripe_product, [self::API_ADDON_IDENTIFIER], true)
                        || in_array($item->stripe_price, [self::API_ADDON_IDENTIFIER], true);
                });
            });
    }
}
