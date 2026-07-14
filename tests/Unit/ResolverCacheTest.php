<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Services\ResolverCache;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResolverCacheTest extends TestCase
{
    private ResolverCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'qr.resolver_cache.enabled' => true,
            'qr.resolver_cache.store' => 'array',
            'qr.resolver_cache.ttl' => 3600,
            'qr.resolver_cache.metrics' => true,
        ]);
        Cache::store('array')->flush();
        $this->cache = new ResolverCache;
    }

    public function test_key_is_case_insensitive_and_host_scoped(): void
    {
        $this->assertSame(
            $this->cache->key('QRM.SG', 'AbC-123'),
            $this->cache->key('qrm.sg', 'abc-123'),
        );
        $this->assertNotSame(
            $this->cache->key('qrm.sg', 'abc-123'),
            $this->cache->key('other.host', 'abc-123'),
        );
    }

    public function test_disabled_cache_returns_no_hits(): void
    {
        config(['qr.resolver_cache.enabled' => false]);

        $this->cache->put('qrm.sg', 'abc', 1, ['status' => 200, 'body' => 'x', 'content_type' => 'text/html', 'location' => null], null);

        $this->assertNull($this->cache->get('qrm.sg', 'abc'));
    }

    public function test_put_then_get_round_trip(): void
    {
        $this->cache->put('qrm.sg', 'abc', 7, [
            'status' => 200,
            'body' => 'hello',
            'content_type' => 'text/html',
            'location' => null,
        ], null);

        $payload = $this->cache->get('qrm.sg', 'abc');

        $this->assertNotNull($payload);
        $this->assertSame(7, $payload['qr_code_id']);
        $this->assertSame('hello', $payload['body']);
    }

    public function test_ttl_defaults_to_configured_value_without_expiry(): void
    {
        $this->assertSame(3600, $this->cache->ttlFor(null));
    }

    public function test_ttl_is_bounded_by_expires_at(): void
    {
        // Freeze the clock: ttlFor() uses now()->diffInSeconds(), so without a
        // frozen time ~1 s elapses between computing "in 10 s" and the diff,
        // making the assertion flap between 9 and 10.
        Carbon::setTestNow(Carbon::now());

        try {
            $inTenSeconds = Carbon::now()->addSeconds(10);

            $this->assertSame(10, $this->cache->ttlFor($inTenSeconds));
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_ttl_falls_back_to_one_second_for_expired_code(): void
    {
        $expired = Carbon::now()->subSecond();

        $this->assertSame(1, $this->cache->ttlFor($expired));
    }

    public function test_is_cacheable_only_for_active_unlimited_non_password_non_expired_codes(): void
    {
        $cacheable = $this->makeQrCode();
        $this->assertTrue($this->cache->isCacheable($cacheable));

        $this->assertFalse($this->cache->isCacheable($this->makeQrCode(['status' => 'disabled'])));
        $this->assertFalse($this->cache->isCacheable($this->makeQrCode(['burn' => true])));
        $this->assertFalse($this->cache->isCacheable($this->makeQrCode(['max_scans' => 5])));
        $this->assertFalse($this->cache->isCacheable($this->makeQrCode(['password_hash' => 'secret-hash'])));
        $this->assertFalse($this->cache->isCacheable($this->makeQrCode(['expires_at' => now()->subDay()])));
    }

    public function test_capture_and_rebuild_response_round_trip(): void
    {
        $original = new Response('<html>hi</html>', 302, ['Location' => 'https://example.com', 'Content-Type' => 'text/html']);

        $payload = $this->cache->captureResponse($original);
        $this->assertNotNull($payload);

        $rebuilt = $this->cache->toResponse($payload);

        $this->assertSame(302, $rebuilt->getStatusCode());
        $this->assertSame('https://example.com', $rebuilt->headers->get('Location'));
        $this->assertSame('<html>hi</html>', $rebuilt->getContent());
    }

    public function test_forget_for_clears_only_that_codes_entries(): void
    {
        $qrCodeA = $this->makeQrCode(); // id assigned below via forceFill
        $qrCodeA->id = 11;
        $qrCodeB = $this->makeQrCode();
        $qrCodeB->id = 22;

        $this->cache->put('qrm.sg', 'code-a', 11, $this->payload('A'), null);
        $this->cache->put('qrm.sg', 'code-b', 22, $this->payload('B'), null);

        $this->assertNotNull($this->cache->get('qrm.sg', 'code-a'));
        $this->assertNotNull($this->cache->get('qrm.sg', 'code-b'));

        $this->cache->forgetFor($qrCodeA);

        $this->assertNull($this->cache->get('qrm.sg', 'code-a'));
        $this->assertNotNull($this->cache->get('qrm.sg', 'code-b'));
    }

    public function test_flush_clears_everything(): void
    {
        $this->cache->put('qrm.sg', 'one', 1, $this->payload('1'), null);
        $this->cache->put('qrm.sg', 'two', 2, $this->payload('2'), null);

        $this->cache->flush();

        $this->assertNull($this->cache->get('qrm.sg', 'one'));
        $this->assertNull($this->cache->get('qrm.sg', 'two'));
    }

    public function test_metrics_track_hits_and_misses(): void
    {
        $this->cache->put('qrm.sg', 'abc', 1, $this->payload('x'), null);

        $this->cache->get('qrm.sg', 'abc'); // hit
        $this->cache->get('qrm.sg', 'abc'); // hit
        $this->cache->get('qrm.sg', 'missing'); // miss

        $metrics = $this->cache->metrics();

        $this->assertSame(2, $metrics['hits']);
        $this->assertSame(1, $metrics['misses']);
    }

    private function makeQrCode(array $attributes = []): QrCode
    {
        return new QrCode(array_merge([
            'status' => 'active',
            'burn' => false,
            'max_scans' => null,
            'password_hash' => null,
            'expires_at' => null,
            'scan_count' => 0,
        ], $attributes));
    }

    /** @return array<string,mixed> */
    private function payload(string $mark): array
    {
        return [
            'status' => 200,
            'body' => 'body-'.$mark,
            'content_type' => 'text/html',
            'location' => null,
        ];
    }
}
