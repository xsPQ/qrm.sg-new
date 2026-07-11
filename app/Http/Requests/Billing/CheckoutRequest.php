<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Domain\Billing\Plan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a Checkout Session request (Pflichtenheft §3.5.2, P2-T09).
 *
 * The <code>plan</code> field must be a Checkout target (Pro or Business). Free
 * is rejected here because there is no payment for it — the user already has it.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'in:'.implode(',', Plan::CHECKOUT_TARGETS)],
        ];
    }
}
