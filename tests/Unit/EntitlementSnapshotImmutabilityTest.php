<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Exceptions\EntitlementSnapshotImmutableException;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * S-Tier invariant (Pflichtenheft §3.5.3, §6.2): once an entitlement snapshot
 * is attached to a QR code it must never change — not via edits and not via a
 * billing webhook that reacts to a plan downgrade. The QrCode model enforces
 * this at the persistence boundary, and a DB trigger enforces it for every
 * write path that bypasses the model.
 */
class EntitlementSnapshotImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a persisted QR code with its immutable snapshot set exactly once
     * at creation via direct attribute write (the snapshot is NOT mass-
     * assignable, so it cannot be supplied through QrCode::create()).
     */
    private function createWithSnapshot(User $user, string $title, array $snapshot): QrCode
    {
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $qrCode->entitlement_snapshot = $snapshot;
        $qrCode->save();

        return $qrCode;
    }

    public function test_snapshot_is_set_once_at_creation(): void
    {
        $user = User::factory()->create();
        $pro = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray();

        $qrCode = $this->createWithSnapshot($user, 'Pro code', $pro);

        $this->assertSame('pro', $qrCode->entitlement_snapshot['plan']);
        $this->assertSame('pro', $qrCode->entitlement_snapshot['tier']);
    }

    public function test_snapshot_cannot_be_changed_after_creation(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Pro code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray(),
        );

        // A direct overwrite attempt (e.g. a hypothetical webhook) is rejected.
        $qrCode->entitlement_snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE)->toArray();

        $this->expectException(EntitlementSnapshotImmutableException::class);
        $qrCode->save();
    }

    public function test_downgrade_does_not_overwrite_existing_snapshot(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Business code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_BUSINESS)->toArray(),
        );

        // Simulate a downgrade webhook storing the now-current Free plan onto
        // the existing grandfathered code — must be rejected.
        $qrCode->entitlement_snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE)->toArray();

        try {
            $qrCode->save();
            $this->fail('Expected EntitlementSnapshotImmutableException on downgrade overwrite.');
        } catch (EntitlementSnapshotImmutableException $e) {
            $this->assertSame($qrCode->id, $e->qrCodeId);
        }

        // The persisted snapshot is untouched.
        $this->assertSame(
            'business',
            QrCode::find($qrCode->id)->entitlement_snapshot['plan']
        );
    }

    public function test_re_saving_identical_snapshot_is_allowed(): void
    {
        $user = User::factory()->create();
        $pro = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray();
        $qrCode = $this->createWithSnapshot($user, 'Pro code', $pro);

        // Re-assigning the same value and saving must not throw.
        $qrCode->entitlement_snapshot = $pro;
        $qrCode->title = 'Renamed';
        $qrCode->save();

        $this->assertSame('Renamed', QrCode::find($qrCode->id)->title);
        $this->assertSame('pro', QrCode::find($qrCode->id)->entitlement_snapshot['plan']);
    }

    public function test_saving_other_attributes_does_not_touch_snapshot_guard(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Pro code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray(),
        );

        // A scan increments scan_count — unrelated to the snapshot — and must
        // save without tripping the immutability guard.
        $qrCode->scan_count++;
        $qrCode->save();

        $this->assertSame(1, QrCode::find($qrCode->id)->scan_count);
    }

    public function test_entitlement_snapshot_accessor_exposes_typed_value_object(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Business code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_BUSINESS)->toArray(),
        );

        $snapshot = $qrCode->entitlementSnapshot();

        $this->assertInstanceOf(EntitlementSnapshot::class, $snapshot);
        $this->assertSame('business', $snapshot->plan());
        $this->assertTrue($snapshot->allowsCustomDomain());
        $this->assertTrue($snapshot->allowsWhiteLabel());
    }

    public function test_accessor_handles_codes_without_snapshot(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'No snapshot',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        // Codes created without a snapshot default to Free rights when read.
        $snapshot = $qrCode->entitlementSnapshot();

        $this->assertTrue($snapshot->isFree());
        $this->assertSame('qrm.sg', $snapshot->branding());
    }

    /**
     * Defense-in-depth backstop (DEV-592): the DB trigger rejects a snapshot
     * change written through a raw query-builder UPDATE that never touches the
     * Eloquent model and therefore never fires the `saving` guard.
     */
    public function test_db_trigger_blocks_raw_update_that_bypasses_the_model(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Pro code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray(),
        );

        $downgrade = json_encode(
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE)->toArray(),
        );

        $thrown = null;
        // Wrap in a nested transaction (savepoint) so that PostgreSQL can
        // recover from the trigger exception without aborting the outer
        // RefreshDatabase transaction.
        DB::beginTransaction();
        try {
            // Bypasses Eloquent entirely: no `saving` event, no model guard.
            // Only the DB-level trigger can stop this.
            DB::table('qr_codes')
                ->where('id', $qrCode->id)
                ->update(['entitlement_snapshot' => $downgrade]);
        } catch (Throwable $e) {
            $thrown = $e;
            // PostgreSQL: abort the poisoned savepoint.
            DB::rollBack();
        }

        // If no exception was thrown we need to roll back the savepoint too.
        if ($thrown === null) {
            DB::rollBack();
        }

        $this->assertNotNull(
            $thrown,
            'Raw UPDATE that bypasses the model must be rejected by the DB trigger.',
        );
        $this->assertStringContainsString('immutable', strtolower($thrown->getMessage()));

        // The persisted snapshot is untouched (Bestandsschutz / grandfathering).
        $this->assertSame('pro', QrCode::find($qrCode->id)->entitlement_snapshot['plan']);
    }

    /**
     * Defense-in-depth backstop (DEV-592): `saveQuietly()` skips model events,
     * so the `saving` guard never runs. The DB trigger must still reject a
     * changed snapshot written through this silent path.
     */
    public function test_db_trigger_blocks_save_quietly_that_skips_model_events(): void
    {
        $user = User::factory()->create();
        $qrCode = $this->createWithSnapshot(
            $user,
            'Business code',
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_BUSINESS)->toArray(),
        );

        $qrCode->entitlement_snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE)->toArray();

        $thrown = null;
        DB::beginTransaction();
        try {
            // saveQuietly() bypasses the `saving` model event entirely.
            $qrCode->saveQuietly();
        } catch (Throwable $e) {
            $thrown = $e;
            DB::rollBack();
        }

        if ($thrown === null) {
            DB::rollBack();
        }

        $this->assertNotNull(
            $thrown,
            'saveQuietly() change must be rejected by the DB trigger.',
        );
        $this->assertStringContainsString('immutable', strtolower($thrown->getMessage()));

        $this->assertSame('business', QrCode::find($qrCode->id)->entitlement_snapshot['plan']);
    }

    /**
     * The trigger mirrors the model guard semantics: the invariant is
     * "immutable once set", not "never writable". A first-time NULL -> value
     * assignment via a raw UPDATE (bypass path) is therefore still allowed,
     * because the trigger's WHEN guard only fires when OLD IS NOT NULL.
     */
    public function test_db_trigger_allows_first_time_assignment_via_raw_update(): void
    {
        $user = User::factory()->create();
        // Code created WITHOUT a snapshot (column is NULL).
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'No snapshot yet',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $first = json_encode(
            EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO)->toArray(),
        );

        // Bypasses the model; the trigger's WHEN guard (OLD IS NOT NULL) is
        // false here, so this first-time assignment succeeds.
        DB::table('qr_codes')
            ->where('id', $qrCode->id)
            ->update(['entitlement_snapshot' => $first]);

        $this->assertSame('pro', QrCode::find($qrCode->id)->entitlement_snapshot['plan']);
    }
}
