<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QrStatus;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Scan;
use App\Models\User;
use App\Services\ResolverCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * End-to-end resolver-cache behaviour (Pflichtenheft §3.2.3, P3-T05).
 *
 * The resolver cache is disabled by default in phpunit.xml so the rest of the
 * suite keeps its exact synchronous behaviour; these tests opt in per test and
 * isolate state by flushing the array store in setUp.
 */
class ResolverCacheTest extends TestCase
{
    use RefreshDatabase;

    private ResolverCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'http://localhost',
            'qr.resolver_cache.enabled' => true,
            'qr.resolver_cache.store' => 'array',
            'qr.resolver_cache.metrics' => true,
        ]);
        Cache::store('array')->flush();
        $this->cache = $this->app->make(ResolverCache::class);
    }

    public function test_second_resolve_is_a_cache_hit_with_security_headers_and_counting(): void
    {
        $route = $this->createRouteWithQrCode([
            'content' => ['url' => 'https://example.com/target'],
        ]);

        // First scan: cache miss -> live resolve, response stored.
        $first = $this->get('/ABC123');
        $first->assertRedirect('https://example.com/target');

        // Second scan: cache hit -> served from cache, scan counted async.
        $second = $this->get('/ABC123');
        $second->assertRedirect('https://example.com/target');
        $second->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        $second->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame(1, $this->cache->metrics()['hits']);
        $this->assertSame(2, $route->fresh()->qrCode->scan_count);
        $this->assertDatabaseCount('scans', 2);
    }

    public function test_edit_invalidates_cached_response_no_stale(): void
    {
        $route = $this->createRouteWithQrCode([
            'content' => ['url' => 'https://old.example.com'],
        ]);
        $qrCode = $route->qrCode;

        $this->get('/ABC123')->assertRedirect('https://old.example.com');
        $this->assertSame(0, $this->cache->metrics()['hits']);

        // Mutate the content -> QrCode saved hook invalidates the entry.
        $qrCode->content = ['url' => 'https://new.example.com'];
        $qrCode->save();

        $this->get('/ABC123')->assertRedirect('https://new.example.com');
        $this->assertSame(0, $this->cache->metrics()['hits'], 'edited code must not be served from stale cache');
    }

    public function test_delete_invalidates_cached_response(): void
    {
        $route = $this->createRouteWithQrCode();
        $qrCode = $route->qrCode;

        $this->get('/ABC123')->assertRedirect('https://example.com');

        $qrCode->delete();

        $this->get('/ABC123')->assertNotFound();
        $this->assertSame(0, $this->cache->metrics()['hits']);
    }

    public function test_deactivation_invalidates_cached_response(): void
    {
        $route = $this->createRouteWithQrCode();
        $qrCode = $route->qrCode;

        $this->get('/ABC123')->assertRedirect('https://example.com');

        $qrCode->setStatus(QrStatus::Disabled, 'test.deactivate');

        $this->get('/ABC123')->assertStatus(423);
        $this->assertSame(0, $this->cache->metrics()['hits']);
    }

    public function test_expired_code_is_never_served_from_cache(): void
    {
        $this->createRouteWithQrCode([
            'expires_at' => now()->subDay(),
        ]);

        $this->get('/ABC123')->assertStatus(410);
        $this->get('/ABC123')->assertStatus(410);

        $this->assertSame(0, $this->cache->metrics()['hits']);
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_burn_code_is_not_cached(): void
    {
        $this->createRouteWithQrCode(['burn' => true]);

        $this->get('/ABC123')->assertRedirect('https://example.com'); // served once, burned
        $this->get('/ABC123')->assertStatus(410); // gone

        $this->assertSame(0, $this->cache->metrics()['hits']);
    }

    public function test_max_scans_code_is_not_cached(): void
    {
        $this->createRouteWithQrCode(['max_scans' => 1]);

        $this->get('/ABC123')->assertRedirect('https://example.com');
        $this->get('/ABC123')->assertStatus(410);

        $this->assertSame(0, $this->cache->metrics()['hits']);
    }

    public function test_password_protected_code_is_not_cached(): void
    {
        $this->createRouteWithQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        // No grant cookie -> password prompt view (never cached).
        $this->get('/ABC123')->assertOk();
        $this->get('/ABC123')->assertOk();

        $this->assertSame(0, $this->cache->metrics()['hits']);
    }

    public function test_warmup_pre_populates_cache_without_consuming_scans(): void
    {
        $route = $this->createRouteWithQrCode([
            'content' => ['url' => 'https://example.com/warmed'],
        ]);
        $qrCode = $route->fresh()->qrCode;

        $this->artisan('qr:resolver-cache:warmup')->assertSuccessful();

        // Warmup must not inflate scan counts.
        $this->assertSame(0, (int) $qrCode->fresh()->scan_count);

        // First real request is already a cache hit.
        $this->get('/ABC123')->assertRedirect('https://example.com/warmed');
        $this->assertSame(1, $this->cache->metrics()['hits']);
    }

    public function test_warmup_command_warms_under_code_and_alias(): void
    {
        $this->createRouteWithQrCode([], [
            'code' => 'ABC123',
            'alias' => 'my-link',
        ]);

        $this->artisan('qr:resolver-cache:warmup')->assertSuccessful();

        $this->get('/ABC123')->assertRedirect('https://example.com');
        $this->get('/my-link')->assertRedirect('https://example.com');

        $this->assertSame(2, $this->cache->metrics()['hits']);
    }

    public function test_flush_command_clears_cache(): void
    {
        $this->createRouteWithQrCode();

        $this->get('/ABC123')->assertRedirect('https://example.com');
        $this->get('/ABC123')->assertRedirect('https://example.com');
        $this->assertSame(1, $this->cache->metrics()['hits']);

        $this->artisan('qr:resolver-cache:flush')->assertSuccessful();

        $this->get('/ABC123')->assertRedirect('https://example.com');
        $this->assertSame(1, $this->cache->metrics()['hits'], 'flush must clear all entries');
    }

    private function createRouteWithQrCode(array $qrAttributes = [], array $routeAttributes = []): QrCodeRoute
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ], $qrAttributes));

        return QrCodeRoute::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'host' => 'localhost',
        ], $routeAttributes));
    }
}
