<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\AnonymousFairUse;
use App\Livewire\AnonymousCreator;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AnonymousCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_anonymous_creator_page(): void
    {
        $this->get('/create')
            ->assertOk()
            ->assertSee(__('Create a QR Code — Free'))
            ->assertSee(__('Choose a type'));
    }

    public function test_all_eight_qr_types_are_rendered(): void
    {
        Livewire::test(AnonymousCreator::class)
            ->assertSee('URL')
            ->assertSee('Message')
            ->assertSee('Redirect')
            ->assertSee('Social')
            ->assertSee('WiFi')
            ->assertSee('Crypto')
            ->assertSee('Event')
            ->assertSee('Contact');
    }

    public function test_each_type_renders_its_dynamic_fields(): void
    {
        $component = Livewire::test(AnonymousCreator::class);

        foreach ([
            'url' => 'Destination URL',
            'message' => 'What should people see when they scan?',
            'redirect' => 'Redirect code',
            'social' => 'Username / handle',
            'wifi' => 'Network name (SSID)',
            'crypto' => 'Wallet address',
            'event' => 'Event title',
            'vcard' => 'First name',
        ] as $type => $expectedText) {
            $component
                ->set('type', $type)
                ->assertSee($expectedText)
                ->assertHasNoErrors();
        }
    }

    public function test_creating_an_anonymous_qr_code_sets_a_fifteen_minute_expiry(): void
    {
        $now = Carbon::parse('2026-07-12 12:00:00');
        Carbon::setTestNow($now);

        try {
            $component = Livewire::test(AnonymousCreator::class)
                ->set('type', 'url')
                ->set('title', 'Launch page')
                ->set('content.url', 'https://example.com/launch')
                ->call('create')
                ->assertHasNoErrors();

            $qrCode = QrCode::query()->latest('id')->firstOrFail();
            $route = QrCodeRoute::query()->where('qr_code_id', $qrCode->id)->firstOrFail();

            $this->assertSame('Launch page', $qrCode->title);
            $this->assertSame('url', $qrCode->type);
            $this->assertSame($now->copy()->addMinutes(15)->toDateTimeString(), $qrCode->expires_at?->toDateTimeString());
            $this->assertNotNull($component->instance()->createdCode);
            $this->assertSame(rtrim((string) config('app.url'), '/') . '/' . $route->code, $component->instance()->createdUrl);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_anonymous_fair_use_blocks_after_ten_creations(): void
    {
        Cache::flush();

        $request = Request::create('/create', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.42');
        $request->headers->set('X-Browser-Fingerprint', 'browser-fingerprint-abc');

        $middleware = app(AnonymousFairUse::class);

        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->check($request);
            $this->assertNull($response);
        }

        $response = $middleware->check($request);

        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame(
            'Daily limit reached (10 QR codes per day). Sign up for unlimited codes.',
            json_decode($response->getContent(), true)['message'] ?? null,
        );
    }
}
