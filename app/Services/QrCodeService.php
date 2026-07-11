<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entitlement\EntitlementGate;
use App\Domain\Entitlement\EntitlementSnapshot;
use App\Enums\QrCodeType;
use App\Events\FreeTierLimitReached;
use App\Exceptions\FreeTierLimitExceededException;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QrCodeService
{
    public function __construct(
        private QrCodeRouteService $routeService,
        private EntitlementGate $gate,
    ) {}

    public function listForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = QrCode::query()->with('route');

        if (! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(min($perPage, 100));
    }

    /**
     * Number of QR codes that currently count against the Free active-code
     * limit for the given user.
     *
     * Counting rules (Pflichtenheft §2.4): only active, non-expired,
     * non-burned, non-maxed codes count. Grandfathered codes created under a
     * paid tier (entitlement snapshot tier != "free") are excluded so that a
     * downgrade does not retroactively breach the Free limit (Bestandsschutz,
     * §3.5.3; entitlement_snapshot model finalized in P2-T08).
     */
    public function countActiveForFreeTier(User $user): int
    {
        $snapshots = QrCode::query()
            ->where('user_id', $user->id)
            ->activeForLimit()
            ->pluck('entitlement_snapshot', 'id');

        return $snapshots
            ->filter(fn ($snapshot) => ($snapshot['tier'] ?? 'free') === 'free')
            ->count();
    }

    /**
     * Reject a new QR-code creation for Free users at/over the active limit.
     *
     * Throws {@see FreeTierLimitExceededException} when no further active Free
     * code may be created. Paid tiers pass through untouched. Call this before
     * any write in the creation path.
     */
    public function enforceFreeTierLimit(User $user): void
    {
        if (! $this->resolveEntitlement($user)->isFree()) {
            return;
        }

        $limit = (int) config('qr.free.max_active_qr_codes', 10);
        $active = $this->countActiveForFreeTier($user);

        if ($active >= $limit) {
            // Extensible hook (P2-T06): the upgrade-hint email (P2-T12) listens
            // to this event. Dispatched before the rejection so listeners can
            // run even though the create itself is rejected.
            event(new FreeTierLimitReached($user, $active, $limit));

            throw new FreeTierLimitExceededException($active, $limit);
        }
    }

    /**
     * Free-Tier status surfaced to the Creator/Dashboard for the Upgrade-Prompt
     * and the active-count display. Returns the user's resolved tier, the
     * number of codes counting against the Free limit, the limit itself, the
     * remaining headroom, and whether the limit has been reached.
     */
    public function freeTierStatus(User $user): array
    {
        $tier = $this->resolveEntitlement($user)->plan();
        $limit = (int) config('qr.free.max_active_qr_codes', 10);
        $active = $this->countActiveForFreeTier($user);
        $isFree = $tier === 'free';
        $limitReached = $isFree && $active >= $limit;

        return [
            'tier' => $tier,
            'active_count' => $active,
            'limit' => $isFree ? $limit : null,
            'remaining' => $isFree ? max(0, $limit - $active) : null,
            'limit_reached' => $limitReached,
            'upgrade_required' => $limitReached,
            'upgrade_hint' => $limitReached
                ? 'You have reached the Free plan limit of '.$limit.' active QR codes. Upgrade to Pro or Business to create more.'
                : null,
        ];
    }

    public function create(User $user, array $data): QrCode
    {
        // Enforce the Free-Tier active-QR limit before opening any transaction.
        // Paid tiers (pro/business) are never subject to this limit; this only
        // ever rejects Free users at/over the configured limit.
        // (Pflichtenheft §2.4: "Nach 10 QR-Codes → Upgrade-Prompt".)
        $this->enforceFreeTierLimit($user);

        // Feature-gate (P2-T07): custom alias and password protection are
        // Pro/Business-only (§2.1). Checked against the user's *current* plan
        // before any write so a 402-equivalent upgrade hint surfaces cleanly.
        $entitlement = $this->resolveEntitlement($user);

        if (! empty($data['alias'])) {
            $this->gate->assertCustomAlias($entitlement);
        }

        if (! empty($data['password'])) {
            $this->gate->assertPasswordProtection($entitlement);
        }

        return DB::transaction(function () use ($user, $data, $entitlement) {
            $qrCode = new QrCode();
            $qrCode->user_id = $user->id;
            $qrCode->title = $data['title'];
            $qrCode->type = $data['type'];
            $qrCode->content = $data['content'];
            $qrCode->settings = $data['settings'] ?? [];
            // Set exactly once at creation: the immutable per-code entitlement
            // snapshot (Pflichtenheft §6.2/§3.5.3). Stored as the canonical
            // versioned shape; never overwritten afterwards (model invariant).
            $qrCode->entitlement_snapshot = $entitlement->toArray();
            $qrCode->status = 'active';
            $qrCode->burn = $data['burn'] ?? false;
            $qrCode->max_scans = $data['max_scans'] ?? null;
            // expires_at is server-managed and immutable: free-tier codes auto
            // expire 30 days after creation; paid tiers have no expiry. Any
            // client-supplied expires_at is intentionally ignored.
            $qrCode->expires_at = $this->resolveExpiresAt($entitlement);
            $qrCode->password_hash = isset($data['password'])
                ? password_hash($data['password'], PASSWORD_BCRYPT)
                : null;
            $qrCode->save();

            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $this->routeService->generateRoute($qrCode->id, $host, $data['alias'] ?? null);

            $qrCode->load('route');

            return $qrCode;
        });
    }

    public function update(QrCode $qrCode, array $data): QrCode
    {
        // Feature-gate (P2-T07): custom alias and password protection are
        // Pro/Business-only (§2.1). On edit the gate checks the code's *own*
        // immutable entitlement snapshot — a grandfathered Pro code keeps its
        // rights after a downgrade (Bestandsschutz, §3.5.3), while a Free code
        // can never gain them retroactively.
        $snapshot = $qrCode->entitlementSnapshot();

        if (array_key_exists('alias', $data) && ! empty($data['alias'])) {
            $this->gate->assertCustomAlias($snapshot);
        }

        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $this->gate->assertPasswordProtection($snapshot);
        }

        return DB::transaction(function () use ($qrCode, $data) {
            if (isset($data['title'])) {
                $qrCode->title = $data['title'];
            }

            if (isset($data['content'])) {
                $qrCode->content = $data['content'];
            }

            if (isset($data['settings'])) {
                $qrCode->settings = $data['settings'];
            }

            if (isset($data['burn'])) {
                $qrCode->burn = $data['burn'];
            }

            if (isset($data['max_scans'])) {
                $qrCode->max_scans = $data['max_scans'];
            }

            // expires_at is server-managed and intentionally immutable via the
            // API; it is never changed here once set at creation time.

            if (array_key_exists('password', $data)) {
                $qrCode->password_hash = $data['password']
                    ? password_hash($data['password'], PASSWORD_BCRYPT)
                    : null;
            }

            if (isset($data['status'])) {
                $qrCode->status = $data['status'];
            }

            if (array_key_exists('alias', $data)) {
                $alias = $data['alias'];

                if ($qrCode->route && $alias) {
                    $this->routeService->assignAlias($qrCode->route, $alias);
                } elseif ($qrCode->route && ! $alias) {
                    $this->routeService->removeAlias($qrCode->route);
                } elseif (! $qrCode->route && $alias) {
                    $host = parse_url(config('app.url'), PHP_URL_HOST);
                    $this->routeService->generateRoute($qrCode->id, $host, $alias);
                }
            }

            $qrCode->save();
            $qrCode->load('route');

            return $qrCode;
        });
    }

    public function delete(QrCode $qrCode): void
    {
        $qrCode->delete();
    }

    public function findForUser(User $user, int $id): QrCode
    {
        return QrCode::where('user_id', $user->id)
            ->with('route')
            ->findOrFail($id);
    }

    public function find(int $id): QrCode
    {
        return QrCode::with('route')->findOrFail($id);
    }

    /**
     * Resolve the entitlement snapshot value object for a user's *current*
     * plan. This drives the snapshot captured for newly created codes. It does
     * NOT touch existing codes — their snapshot is immutable (§3.5.3).
     */
    private function resolveEntitlement(User $user): EntitlementSnapshot
    {
        $subscription = $user->subscriptions()
            ->where('stripe_status', 'active')
            ->first();

        if ($subscription) {
            $plan = $subscription->stripe_price === 'price_business'
                ? EntitlementSnapshot::PLAN_BUSINESS
                : EntitlementSnapshot::PLAN_PRO;

            return EntitlementSnapshot::forPlan($plan, audit: [
                'subscription_id' => $subscription->id,
                'source' => 'stripe',
            ]);
        }

        return EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE, audit: [
            'source' => 'default',
        ]);
    }

    /**
     * Feature flags for the Creator/Dashboard derived from the user's *current*
     * plan (Pflichtenheft §2.1–§2.3, P2-T07). Lets the UI render locked fields
     * and upgrade hints deterministically without re-implementing plan rules.
     *
     * @return array<string,mixed>
     */
    public function featureStatus(User $user): array
    {
        return $this->gate->featureFlags($this->resolveEntitlement($user));
    }

    /**
     * Compute the immutable server-side expiry for a new QR code.
     *
     * Free-tier codes expire 30 days after creation (Pflichtenheft §2.1).
     * Paid tiers (pro/business) do not auto-expire here.
     */
    private function resolveExpiresAt(EntitlementSnapshot $entitlement): ?Carbon
    {
        return $entitlement->isFree()
            ? now()->addDays((int) config('qr.free.expiry_days', 30))
            : null;
    }

    /**
     * FEAT-06: Sync A/B test variants for a QR code.
     *
     * Replaces all existing variants with the given set. Entitlement-gated:
     * only Pro/Business snapshots may have variants. Free codes are rejected
     * with FeatureNotEntitledException.
     *
     * @param  array<int, array{label:string,url:string,weight?:int,device_target?:?string}>  $variantsData
     */
    public function syncVariants(QrCode $qrCode, array $variantsData): void
    {
        $this->gate->assertAbTesting($qrCode->entitlementSnapshot());

        DB::transaction(function () use ($qrCode, $variantsData) {
            $qrCode->variants()->delete();

            $sortOrder = 0;
            foreach ($variantsData as $variantData) {
                $qrCode->variants()->create([
                    'label' => strtoupper(substr(trim($variantData['label']), 0, 10)),
                    'url' => trim($variantData['url']),
                    'weight' => max(1, (int) ($variantData['weight'] ?? 1)),
                    'device_target' => $variantData['device_target'] ?? null,
                    'sort_order' => $sortOrder++,
                    'scan_count' => 0,
                ]);
            }

            // Persist the strategy in settings.ab_testing.
            $settings = $qrCode->settings ?? [];
            $settings['ab_testing'] = [
                'strategy' => $variantsData[0]['device_target'] ?? null
                    ? 'device'
                    : 'random',
                'enabled' => count($variantsData) > 0,
            ];
            $qrCode->settings = $settings;
            $qrCode->save();
        });
    }

    /**
     * FEAT-06: Remove all variants from a QR code (disable A/B testing).
     */
    public function clearVariants(QrCode $qrCode): void
    {
        DB::transaction(function () use ($qrCode) {
            $qrCode->variants()->delete();

            $settings = $qrCode->settings ?? [];
            unset($settings['ab_testing']);
            $qrCode->settings = $settings ?: null;
            $qrCode->save();
        });
    }
}
