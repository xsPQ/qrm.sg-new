<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\QrCodeList;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrCodeListLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function host(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
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

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_the_qr_code_list_on_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(__('My QR Codes'));
    }

    public function test_user_sees_only_their_own_qr_codes(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->makeQrCode($me, ['title' => 'My private code', 'alias' => 'mine-alias']);
        $theirs = $this->makeQrCode($other, ['title' => 'Someone else code', 'alias' => 'theirs-alias']);

        Livewire::actingAs($me)
            ->test(QrCodeList::class)
            ->assertSee('My private code')
            ->assertSee('mine-alias')
            ->assertDontSee('Someone else code')
            ->assertDontSee('theirs-alias');
    }

    public function test_search_filters_case_insensitively_by_alias(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'Newsletter signup', 'alias' => 'Newsletter-LINK']);
        $this->makeQrCode($user, ['title' => 'Black friday deal', 'alias' => 'bf-2026']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('search', 'newsletter')
            ->assertSee('Newsletter signup')
            ->assertDontSee('Black friday deal');
    }

    public function test_search_filters_by_code(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'First', 'code' => 'CODEAAA']);
        $this->makeQrCode($user, ['title' => 'Second', 'code' => 'CODEBBB']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('search', 'codebbb')
            ->assertSee('Second')
            ->assertDontSee('First');
    }

    public function test_search_filters_by_title(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'Summer campaign']);
        $this->makeQrCode($user, ['title' => 'Winter campaign']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('search', 'SUMMER')
            ->assertSee('Summer campaign')
            ->assertDontSee('Winter campaign');
    }

    public function test_type_filter_reduces_list_to_chosen_type(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'A url code', 'type' => 'url']);
        $this->makeQrCode($user, ['title' => 'A wifi code', 'type' => 'wifi']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('typeFilter', 'wifi')
            ->assertSee('A wifi code')
            ->assertDontSee('A url code');
    }

    public function test_status_filter_reduces_list_to_chosen_status(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'Active one', 'status' => 'active']);
        $this->makeQrCode($user, ['title' => 'Expired one', 'status' => 'expired']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('statusFilter', 'expired')
            ->assertSee('Expired one')
            ->assertDontSee('Active one');
    }

    public function test_status_badges_render_for_each_status(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'Active badge', 'status' => 'active']);
        $this->makeQrCode($user, ['title' => 'Expired badge', 'status' => 'expired']);
        $this->makeQrCode($user, [
            'title' => 'Burned badge',
            'status' => 'active',
            'burn' => true,
            'scan_count' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->assertSee('Active')
            ->assertSee('Expired')
            ->assertSee('Burned');
    }

    public function test_burned_code_shows_burned_badge_even_when_stored_status_active(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, [
            'title' => 'Consumed code',
            'status' => 'active',
            'burn' => true,
            'scan_count' => 5,
        ]);

        $component = Livewire::actingAs($user)->test(QrCodeList::class);

        $this->assertSame('burned', $component->instance()->effectiveStatus(QrCode::first()));
        $component->assertSee('Burned');
    }

    public function test_expired_by_date_shows_expired_badge_even_when_stored_status_active(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, [
            'title' => 'Date-expired code',
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($user)->test(QrCodeList::class);

        $this->assertSame('expired', $component->instance()->effectiveStatus(QrCode::first()));
        $component->assertSee('Expired');
    }

    public function test_pagination_limits_results_to_per_page(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 26; $i++) {
            $this->makeQrCode($user, ['title' => "Code #{$i}"]);
        }

        $component = Livewire::actingAs($user)->test(QrCodeList::class);

        $this->assertSame(25, $component->instance()->perPage);
        // First page only renders 25 of 26 codes.
        $component->assertSee('Code #0')
            ->assertDontSee('Code #25');

        // Navigating to page 2 surfaces the remaining code.
        $component->call('nextPage')->assertSee('Code #25');
    }

    public function test_updating_search_resets_to_first_page(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 26; $i++) {
            $this->makeQrCode($user, ['title' => "Code #{$i}", 'alias' => "alias-{$i}"]);
        }

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->call('nextPage')
            ->set('search', 'alias-1')
            ->assertSee('alias-1');
    }

    public function test_reset_filters_clears_all_filters(): void
    {
        $user = User::factory()->create();

        $this->makeQrCode($user, ['title' => 'Visible', 'type' => 'url', 'alias' => 'visible-alias']);
        $this->makeQrCode($user, ['title' => 'Hidden by filter', 'type' => 'wifi', 'alias' => 'hidden-alias']);

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->set('search', 'visible')
            ->set('typeFilter', 'wifi')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('typeFilter', '')
            ->assertSee('Visible')
            ->assertSee('Hidden by filter');
    }

    public function test_empty_state_shown_when_user_has_no_codes(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCodeList::class)
            ->assertSee(__('You have no QR codes yet.'));
    }

    public function test_type_options_cover_all_defined_types(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(QrCodeList::class);

        foreach (\App\Enums\QrCodeType::cases() as $type) {
            $component->assertSee($type->label(), false);
        }
    }
}
