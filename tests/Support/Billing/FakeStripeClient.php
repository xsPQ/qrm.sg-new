<?php

declare(strict_types=1);

namespace Tests\Support\Billing;

use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\StripeObject;

/**
 * Minimal in-process stand-in for {@see StripeClient}.
 *
 * Cashier resolves the client through the container via
 * <code>app(StripeClient::class, ['config' => …])</code>; binding this class in
 * tests lets the real Cashier code paths run without touching the Stripe API.
 *
 * It implements only the service surface Cashier's Checkout/portal/customer
 * flows use: <code>customers</code>, <code>checkout->sessions</code> and
 * <code>billingPortal->sessions</code>. Every call is recorded on
 * {@see $calls} (keyed by service) so feature tests can assert the exact
 * payloads (success_url, cancel_url, return_url, line_items, …).
 */
final class FakeStripeClient
{
    public FakeCustomers $customers;

    public FakeCheckout $checkout;

    public FakeBillingPortal $billingPortal;

    /** @var array<string, list<array<string, mixed>>> */
    public array $calls = [];

    private int $customerSequence = 0;

    private int $sessionSequence = 0;

    private int $portalSequence = 0;

    public function __construct()
    {
        $this->customers = new FakeCustomers($this);
        $this->checkout = new FakeCheckout($this);
        $this->billingPortal = new FakeBillingPortal($this);
    }

    public function nextCustomerId(): string
    {
        return 'cus_test_'.++$this->customerSequence;
    }

    public function nextSessionId(): string
    {
        return 'cs_test_'.++$this->sessionSequence;
    }

    public function nextPortalId(): string
    {
        return 'bps_test_'.++$this->portalSequence;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function record(string $service, array $params): array
    {
        return $this->calls[$service][] = $params;
    }
}

final class FakeCustomers
{
    public function __construct(private readonly FakeStripeClient $client) {}

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $requestOptions
     */
    public function create(array $params = [], array $requestOptions = []): Customer
    {
        $this->client->record('customers.create', $params);

        return Customer::constructFrom([
            'id' => $this->client->nextCustomerId(),
            'object' => 'customer',
            'email' => $params['email'] ?? null,
        ]);
    }

    public function retrieve(string $id, array $params = []): Customer
    {
        $this->client->record('customers.retrieve', ['id' => $id] + $params);

        return Customer::constructFrom([
            'id' => $id,
            'object' => 'customer',
        ]);
    }
}

final class FakeCheckout
{
    public FakeCheckoutSessions $sessions;

    public function __construct(private readonly FakeStripeClient $client)
    {
        $this->sessions = new FakeCheckoutSessions($client);
    }
}

final class FakeCheckoutSessions
{
    public function __construct(private readonly FakeStripeClient $client) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Session
    {
        $this->client->record('checkout.sessions.create', $params);
        $id = $this->client->nextSessionId();

        return Session::constructFrom([
            'id' => $id,
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/'.$id,
            'mode' => $params['mode'] ?? 'subscription',
        ]);
    }
}

final class FakeBillingPortal
{
    public FakeBillingPortalSessions $sessions;

    public function __construct(private readonly FakeStripeClient $client)
    {
        $this->sessions = new FakeBillingPortalSessions($client);
    }
}

final class FakeBillingPortalSessions
{
    public function __construct(private readonly FakeStripeClient $client) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): StripeObject
    {
        $this->client->record('billingPortal.sessions.create', $params);
        $id = $this->client->nextPortalId();

        return StripeObject::constructFrom([
            'id' => $id,
            'object' => 'billing_portal.session',
            'url' => 'https://billing.stripe.com/p/session/'.$id,
        ]);
    }
}
