<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QrCodeType;
use App\Livewire\QrCreator;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QrCreatorLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function host(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    private function grantPro(User $user): void
    {
        Subscription::create([
            'user_id' => $user->id,
            'stripe_id' => 'cus_pro_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ]);
    }

    public function test_guest_is_redirected_from_creator(): void
    {
        $this->get('/creator')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_creator_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/creator')
            ->assertOk()
            ->assertSee(__('Create QR Code'))
            ->assertSee(__('Choose a type'));
    }

    /**
     * @dataProvider typeFieldProvider
     */
    #[DataProvider('typeFieldProvider')]
    public function test_each_type_can_be_selected_and_renders_its_fields(string $type, string $expectedText): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', $type)
            ->assertSee($expectedText)
            ->assertHasNoErrors();
    }

    public static function typeFieldProvider(): array
    {
        return [
            'url' => ['url', 'https://example.com'],
            'message' => ['message', 'What should people see'],
            'redirect' => ['redirect', '301 — Permanent'],
            'social' => ['social', 'LinkedIn'],
            'wifi' => ['wifi', 'Network name (SSID)'],
            'crypto' => ['crypto', 'Wallet address'],
            'event' => ['event', 'Event title'],
            'vcard (contact)' => ['vcard', 'First name'],
        ];
    }

    public function test_all_eight_types_are_offered(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(QrCreator::class);

        foreach (['url', 'message', 'redirect', 'social', 'wifi', 'crypto', 'event', 'vcard'] as $type) {
            $component->assertSee(QrCodeType::from($type)->label());
        }
    }

    public function test_switching_type_resets_content(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('content.url', 'https://example.com')
            ->set('type', 'message')
            ->assertSet('content', ['message' => '']);
    }

    public function test_live_alias_check_reports_available(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('alias', 'free-alias-123')
            ->assertSet('aliasStatus', 'available');
    }

    public function test_live_alias_check_reports_taken_on_collision(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Existing',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'alias' => 'taken-name',
            'host' => $this->host(),
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('alias', 'taken-name')
            ->assertSet('aliasStatus', 'taken');
    }

    public function test_live_alias_check_reports_reserved_system_path(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('alias', 'login')
            ->assertSet('aliasStatus', 'reserved');
    }

    public function test_live_alias_check_reports_invalid_format(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('alias', 'ab')
            ->assertSet('aliasStatus', 'invalid');
    }

    public function test_submit_creates_url_qr_code_through_api_pipeline(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', 'Launch page')
            ->set('content.url', 'https://example.com/launch')
            ->call('submit')
            ->assertHasNoErrors();

        // No alias was supplied, so the resolve slug is the auto-generated code.
        $component->assertSet('created.title', 'Launch page');

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id,
            'title' => 'Launch page',
            'type' => 'url',
        ]);

        $this->assertNotNull($component->instance()->created['code']);
    }

    public function test_submit_creates_url_qr_with_deterministic_alias_url(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', 'Campaign')
            ->set('content.url', 'https://example.com')
            ->set('alias', 'campaign-2026')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('created.url', config('app.url') . '/campaign-2026')
            ->assertSet('created.alias', 'campaign-2026');
    }

    public function test_submit_creates_qr_code_with_custom_alias(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'message')
            ->set('title', 'Welcome')
            ->set('content.message', 'Hello there!')
            ->set('alias', 'welcome-2026')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('qr_codes', ['title' => 'Welcome']);
        $this->assertDatabaseHas('qr_code_routes', [
            'alias' => 'welcome-2026',
            'host' => $this->host(),
        ]);
    }

    public function test_submit_shows_validation_error_when_title_missing(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', '')
            ->set('content.url', 'https://example.com')
            ->call('submit')
            ->assertHasErrors(['title']);
    }

    public function test_submit_shows_validation_error_for_invalid_type_content(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', 'Bad url')
            ->set('content.url', 'not-a-url')
            ->call('submit')
            ->assertHasErrors(['content.url']);
    }

    public function test_submit_shows_upgrade_prompt_when_free_tier_limit_reached(): void
    {
        $user = User::factory()->create();
        $limit = (int) config('qr.free.max_active_qr_codes', 10);

        for ($i = 0; $i < $limit; $i++) {
            $qrCode = QrCode::create([
                'user_id' => $user->id,
                'title' => "Code #{$i}",
                'type' => 'url',
                'content' => ['url' => "https://example.com/{$i}"],
                'status' => 'active',
                'expires_at' => null,
                'burn' => false,
            ]);
            $qrCode->entitlement_snapshot = ['tier' => 'free'];
            $qrCode->save();
        }

        $component = Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', 'Over limit')
            ->set('content.url', 'https://example.com')
            ->call('submit');

        $component
            ->assertHasNoErrors(['title', 'content.url'])
            ->assertSet('freeTier.limit_reached', true)
            ->assertNotSet('upgradeMessage', null);

        $this->assertDatabaseMissing('qr_codes', ['title' => 'Over limit']);
    }

    public function test_preview_payload_reflects_alias_when_set(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('content.url', 'https://example.com');

        // Without alias → preview encodes the content URL.
        $this->assertSame('https://example.com', $component->instance()->previewPayload());

        // With alias → preview encodes the public short-link.
        $component->set('alias', 'my-brand');
        $this->assertSame(config('app.url') . '/my-brand', $component->instance()->previewPayload());
    }

    public function test_preview_data_uri_is_generated_for_url_type(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('content.url', 'https://example.com');

        $dataUri = $component->instance()->previewDataUri();

        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }

    // ---------------------------------------------------------------
    // BUG-FIX-01: Password field on create
    // ---------------------------------------------------------------

    public function test_pro_user_sees_password_field_on_create(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('showAdvanced', true)
            ->assertSee(__('Password protection (optional)'));
    }

    public function test_free_user_sees_password_upgrade_hint_on_create(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('showAdvanced', true)
            ->assertSee(__('Password protection is available on Pro and Business plans.'));
    }

    public function test_password_set_during_create_persists(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);

        Livewire::actingAs($user)
            ->test(QrCreator::class)
            ->set('type', 'url')
            ->set('title', 'Protected')
            ->set('content.url', 'https://example.com')
            ->set('password', 'secret123')
            ->call('submit')
            ->assertHasNoErrors();

        $qrCode = QrCode::where('title', 'Protected')->first();
        $this->assertNotNull($qrCode);
        $this->assertNotNull($qrCode->password_hash);
        $this->assertTrue(password_verify('secret123', $qrCode->password_hash));
    }
}
