<?php

use App\Enums\UserPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

/*
 * Account → tariff / plan card (Pflichtenheft §3.6.1, §2.1–§2.4; P2-T05).
 *
 * Renders the authenticated user's current subscription plan (Free / Pro /
 * Business) and the Billing/Upgrade entry points. The actual Stripe Checkout /
 * Customer Portal sessions are owned by P2-T09 ([DEV-161](/DEV/issues/DEV-161));
 * this card only *links* to them via the thin web billing routes
 * (`billing.web.checkout` / `billing.web.portal`). Free is never a checkout
 * target, so Free users see upgrade buttons for Pro/Business; paid users see
 * the "manage subscription" portal button.
 *
 * The account `plan` column is the plain string value written by the Stripe
 * webhook (P2-T10 / DEV-162) and resolved here through {@see UserPlan}. The
 * card is read-only with respect to the plan: only Stripe may change it.
 */

new class extends Component
{
    public ?string $plan = null;

    public function mount(): void
    {
        $this->plan = Auth::user()->plan;
    }

    /**
     * Resolved plan enum (falls back to Free on an unexpected value).
     */
    public function planEnum(): UserPlan
    {
        return UserPlan::tryFrom($this->plan ?? 'free') ?? UserPlan::Free;
    }

    /** Plans a Free user may upgrade to via Stripe Checkout. */
    public function checkoutTargets(): array
    {
        return array_filter(
            UserPlan::cases(),
            fn (UserPlan $plan) => $plan !== UserPlan::Free,
        );
    }

    public function isPaid(): bool
    {
        return $this->planEnum() !== UserPlan::Free;
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Plan & Billing') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Your current subscription plan and billing options.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <!-- Current plan -->
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                @if ($this->planEnum()->value === 'free') bg-gray-100 text-gray-800
                @elseif ($this->planEnum()->value === 'pro') bg-green-100 text-green-800
                @else bg-yellow-100 text-yellow-800 @endif">
                {{ $this->planEnum()->label() }}
            </span>
            <span class="text-sm text-gray-600">
                @if ($this->isPaid())
                    {{ __('Paid plan — manage your subscription via Stripe.') }}
                @else
                    {{ __('Free plan — upgrade to unlock more features.') }}
                @endif
            </span>
        </div>

        @if (session('billing_error'))
            <p class="text-sm font-medium text-red-600">
                {{ session('billing_error') }}
            </p>
        @endif

        @if (session('status') === 'billing-plan-changed')
            <p class="mt-2 font-medium text-sm text-green-600">
                {{ __('Your plan change is pending confirmation.') }}
            </p>
        @endif

        <!-- Billing / Upgrade entry points (P2-T09 / DEV-161). -->
        @if (! $this->isPaid())
            <div class="flex flex-wrap items-center gap-4">
                @foreach ($this->checkoutTargets() as $target)
                    <form method="POST" action="{{ route('billing.web.checkout', ['plan' => $target->value]) }}">
                        @csrf
                        <x-primary-button>
                            {{ __('Upgrade to :plan', ['plan' => $target->label()]) }}
                        </x-primary-button>
                    </form>
                @endforeach
            </div>
        @else
            <form method="POST" action="{{ route('billing.web.portal') }}">
                @csrf
                <x-primary-button>
                    {{ __('Manage Subscription') }}
                </x-primary-button>
            </form>
        @endif

        <p class="text-xs text-gray-500">
            {{ __('Plan changes are finalised by Stripe. Payment is handled securely by Stripe Checkout.') }}
        </p>
    </div>
</section>
