<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QrCodeDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function host(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeQrCode(User $user, array $overrides = []): QrCode
    {
        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Untitled code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'scan_count' => 0,
            'burn' => false,
        ], $overrides));

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => $overrides['code'] ?? strtoupper(\Illuminate\Support\Str::random(8)),
            'alias' => $overrides['alias'] ?? null,
            'host' => $this->host(),
            'created_at' => now(),
        ]);

        return $qrCode->refresh();
    }

    public function test_guest_is_redirected_from_detail_page(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        $this->get(route('qr-codes.detail', $qrCode))->assertRedirect('/login');
    }

    public function test_owner_sees_the_detail_page_with_full_metadata(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Spring campaign',
            'type' => 'url',
            'content' => ['url' => 'https://example.com/spring'],
            'status' => 'active',
            'scan_count' => 42,
            'max_scans' => 100,
            'burn' => false,
            'alias' => 'spring-link',
        ]);

        $response = $this->actingAs($owner)->get(route('qr-codes.detail', $qrCode));

        $response->assertOk()
            ->assertSee('Spring campaign')
            ->assertSee('spring-link')
            ->assertSee('URL')
            ->assertSee('42')
            ->assertSee('100');
    }

    public function test_non_owner_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['alias' => 'secret-link']);

        $this->actingAs($intruder)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertForbidden();
    }

    public function test_admin_can_view_other_users_code(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['title' => 'Owner only']);

        $this->actingAs($this->adminUser())
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('Owner only');
    }

    public function test_detail_page_renders_qr_preview_and_download_buttons(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['code' => 'CODEXYZ']);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('data:image/png', false)
            ->assertSee(route('qr.download.svg', 'CODEXYZ'))
            ->assertSee(route('qr.download.png', 'CODEXYZ'));
    }

    public function test_type_specific_payload_is_rendered_per_qr_type(): void
    {
        $owner = User::factory()->create();

        $wifi = $this->makeQrCode($owner, [
            'title' => 'Office wifi',
            'type' => 'wifi',
            'content' => ['ssid' => 'AcmeNet', 'encryption' => 'WPA', 'password' => 'p4ss'],
            'alias' => 'wifi-alias',
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $wifi))
            ->assertOk()
            ->assertSee('AcmeNet')
            ->assertSee('WPA')
            ->assertSee('p4ss');
    }

    public function test_url_payload_rendered_with_open_link(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'content' => ['url' => 'https://example.com/promo'],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('https://example.com/promo');
    }

    public function test_redirect_payload_uses_target_url_partial(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Old redirect',
            'type' => 'redirect',
            'content' => ['target_url' => 'https://example.org/forward'],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('https://example.org/forward');
    }

    public function test_vcard_payload_renders_contact_fields(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Business card',
            'type' => 'vcard',
            'content' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('jane@example.com');
    }

    public function test_unknown_type_falls_back_to_generic_payload(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Custom',
            'type' => 'text',
            'content' => ['text' => 'Hello world'],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('Hello world');
    }

    public function test_payload_is_rendered_xss_safe(): void
    {
        $owner = User::factory()->create();
        $payload = '<script>alert(1)</script>';
        $qrCode = $this->makeQrCode($owner, [
            'type' => 'text',
            'content' => ['text' => $payload],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee($payload)            // escaped form (&lt;script&gt;…) still visible as text
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_url_payload_xss_safe(): void
    {
        $owner = User::factory()->create();
        $attack = '"><script>alert(1)</script>';
        $qrCode = $this->makeQrCode($owner, [
            'content' => ['url' => $attack],
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_last_scanned_at_is_taken_from_scans_table(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        Scan::create([
            'qr_code_id' => $qrCode->id,
            'response_type' => 'redirect',
            'http_status' => 302,
            'response_time_ms' => 12,
            'scanned_at' => '2026-06-01 12:30:00',
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $qrCode))
            ->assertOk()
            ->assertSee('2026-06-01 12:30');
    }

    public function test_effective_status_badges_surface_truthfully(): void
    {
        $owner = User::factory()->create();

        $burned = $this->makeQrCode($owner, [
            'title' => 'Burned one',
            'status' => 'active',
            'burn' => true,
            'scan_count' => 3,
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $burned))
            ->assertOk()
            ->assertSee('Burned');

        $expired = $this->makeQrCode($owner, [
            'title' => 'Date-expired one',
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('qr-codes.detail', $expired))
            ->assertOk()
            ->assertSee('Expired');
    }

    public function test_list_links_to_the_detail_page(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['title' => 'Linked code', 'alias' => 'linked']);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('qr-codes.detail', $qrCode));
    }
}
