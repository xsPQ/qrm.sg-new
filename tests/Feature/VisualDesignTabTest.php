<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrStyleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Visual customization tests (FEAT-04).
 * Tests the QrStyleService backend, not Blade UI (pending).
 */
class VisualDesignTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_service_strips_premium_ec_for_free_snapshot(): void
    {
        $service = app(QrStyleService::class);
        $snapshot = EntitlementSnapshot::forPlan('free');

        $resolved = $service->resolveStyle(
            ['error_correction' => 'H', 'gradient' => ['from' => '#fff', 'to' => '#000']],
            $snapshot,
        );

        $this->assertNotContains('H', [$resolved['error_correction']]);
        $this->assertArrayNotHasKey('gradient', $resolved);
    }

    public function test_style_service_allows_premium_ec_for_pro_snapshot(): void
    {
        $service = app(QrStyleService::class);
        $snapshot = EntitlementSnapshot::forPlan('pro');

        $resolved = $service->resolveStyle(
            ['error_correction' => 'H', 'gradient' => ['from' => '#fff', 'to' => '#000', 'angle' => 45]],
            $snapshot,
        );

        $this->assertSame('H', $resolved['error_correction']);
    }

    public function test_creator_page_loads_without_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('qr.creator'));

        $response->assertOk();
    }

    public function test_creator_can_save_basic_style(): void
    {
        $user = User::factory()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\QrCreator::class)
            ->set('title', 'Styled QR')
            ->set('type', 'url')
            ->set('content.url', 'https://example.com')
            ->set('style.fg_color', '#1a1a2e')
            ->set('style.bg_color', '#e94560')
            ->set('style.dot_style', 'round')
            ->call('submit')
            ->assertHasNoErrors();

        $qrCode = QrCode::where('title', 'Styled QR')->first();
        $this->assertNotNull($qrCode);
        $this->assertSame('#1a1a2e', $qrCode->settings['style']['fg_color'] ?? null);
        $this->assertSame('round', $qrCode->settings['style']['dot_style'] ?? null);
    }
}
