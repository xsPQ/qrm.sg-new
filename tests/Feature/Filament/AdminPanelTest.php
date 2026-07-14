<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\QrStatus;
use App\Enums\UserPlan;
use App\Enums\UserStatus;
use App\Filament\Resources\QrCodes\QrCodeResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\UserResource;
use App\Models\AdminActionLog;
use App\Models\QrCode;
use App\Models\Subscription;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // -----------------------------------------------------------------------
    // Acceptance: Admin-Panel ist unter /admin erreichbar
    // Acceptance: Nur Admin-Rolle hat Zugriff
    // -----------------------------------------------------------------------

    public function test_admin_panel_is_reachable_at_admin_path(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_can_access_panel_returns_true_only_for_admin_role(): void
    {
        $panel = app(Panel::class);

        $admin = $this->adminUser();
        $this->assertTrue($admin->canAccessPanel($panel));

        $user = User::factory()->create();
        $this->assertFalse($user->canAccessPanel($panel));
    }

    // -----------------------------------------------------------------------
    // Acceptance: Admin kann Nutzer sperren/entsperren
    // -----------------------------------------------------------------------

    public function test_admin_can_suspend_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $this->actingAs($admin);
        UsersTable::changeStatus($user, UserStatus::Blocked, 'user.suspend');

        $this->assertSame(UserStatus::Blocked->value, $user->fresh()->status);
    }

    public function test_admin_can_unsuspend_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => UserStatus::Blocked->value]);

        $this->actingAs($admin);
        UsersTable::changeStatus($user, UserStatus::Active, 'user.unsuspend');

        $this->assertSame(UserStatus::Active->value, $user->fresh()->status);
    }

    public function test_suspend_action_creates_audit_log(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $this->actingAs($admin);
        UsersTable::changeStatus($user, UserStatus::Blocked, 'user.suspend');

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'user.suspend',
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);

        $log = AdminActionLog::where('action', 'user.suspend')->first();
        $this->assertSame(['status' => 'active'], $log->before);
        $this->assertSame(['status' => 'blocked'], $log->after);
    }

    public function test_unsuspend_action_creates_audit_log(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['status' => UserStatus::Blocked->value]);

        $this->actingAs($admin);
        UsersTable::changeStatus($user, UserStatus::Active, 'user.unsuspend');

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'user.unsuspend',
        ]);

        $log = AdminActionLog::where('action', 'user.unsuspend')->first();
        $this->assertSame(['status' => 'blocked'], $log->before);
        $this->assertSame(['status' => 'active'], $log->after);
    }

    // -----------------------------------------------------------------------
    // Acceptance: Admin kann Plan eines Nutzers ändern
    // -----------------------------------------------------------------------

    public function test_admin_can_change_user_plan(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['plan' => UserPlan::Free->value]);

        $this->actingAs($admin);
        UsersTable::changePlan($user, UserPlan::Pro->value);

        $this->assertSame(UserPlan::Pro->value, $user->fresh()->plan);
    }

    public function test_change_plan_creates_audit_log(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['plan' => UserPlan::Free->value]);

        $this->actingAs($admin);
        UsersTable::changePlan($user, UserPlan::Business->value);

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'user.change_plan',
            'target_type' => User::class,
            'target_id' => $user->id,
        ]);

        $log = AdminActionLog::where('action', 'user.change_plan')->first();
        $this->assertSame(['plan' => 'free'], $log->before);
        $this->assertSame(['plan' => 'business'], $log->after);
    }

    // -----------------------------------------------------------------------
    // Acceptance: Admin kann QR-Codes einsehen/deaktivieren
    // -----------------------------------------------------------------------

    public function test_admin_can_deactivate_qr_code(): void
    {
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Test Code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $qr->setStatus(QrStatus::Disabled, 'qr.deactivate');

        $this->assertSame(QrStatus::Disabled->value, $qr->fresh()->status);
    }

    public function test_admin_can_reactivate_qr_code(): void
    {
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Test Code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => QrStatus::Disabled->value,
        ]);

        $this->actingAs($admin);
        $qr->setStatus(QrStatus::Active, 'qr.reactivate');

        $this->assertSame(QrStatus::Active->value, $qr->fresh()->status);
    }

    public function test_qr_deactivate_creates_audit_log(): void
    {
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Test Code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $qr->setStatus(QrStatus::Disabled, 'qr.deactivate');

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'qr.deactivate',
            'target_type' => QrCode::class,
            'target_id' => $qr->id,
        ]);

        $log = AdminActionLog::where('action', 'qr.deactivate')->first();
        $this->assertSame(['status' => 'active'], $log->before);
        $this->assertSame(['status' => 'disabled'], $log->after);
    }

    // -----------------------------------------------------------------------
    // Acceptance: Billing-Ansicht zeigt Stripe-ID/Status
    // -----------------------------------------------------------------------

    public function test_subscription_stores_stripe_id_and_status(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_TEST123']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'stripe_id' => 'sub_TEST456',
            'stripe_status' => 'active',
            'stripe_price' => 'price_TEST789',
            'quantity' => 1,
        ]);

        $this->assertSame('sub_TEST456', $subscription->stripe_id);
        $this->assertSame('active', $subscription->stripe_status);
        $this->assertSame('cus_TEST123', $subscription->user->stripe_id);
        $this->assertTrue($subscription->active());
    }

    public function test_billing_resource_disables_create_and_delete(): void
    {
        $this->assertFalse(SubscriptionResource::canCreate());
        $this->assertFalse(SubscriptionResource::canDeleteAny());
    }

    // -----------------------------------------------------------------------
    // Acceptance: no data deletion without audit (kein Löschen ohne Audit)
    // -----------------------------------------------------------------------

    public function test_user_resource_does_not_allow_deletion(): void
    {
        $this->assertFalse(UserResource::canDeleteAny());
    }

    public function test_qr_resource_does_not_allow_deletion(): void
    {
        $this->assertFalse(QrCodeResource::canDeleteAny());
    }

    public function test_qr_resource_does_not_allow_creation(): void
    {
        $this->assertFalse(QrCodeResource::canCreate());
    }
}
