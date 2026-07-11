<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Analytics\AllowlistGuard;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAllowlistComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveQrCode(): QrCode
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Active QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'testcode123',
            'host' => 'localhost',
        ]);

        return $qrCode;
    }

    public function test_scan_captures_all_allowlist_fields(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
            'Referer' => 'https://twitter.com/post/123',
            'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertNotNull($scan->ip_hash);
        $this->assertNotEmpty($scan->user_agent_raw);
        $this->assertIsArray($scan->user_agent_parsed);
        $this->assertEquals('https://twitter.com/post/123', $scan->referer);
        $this->assertEquals('de-DE,de;q=0.9,en;q=0.8', $scan->accept_language);
        $this->assertEquals('mobile', $scan->device_type);
        $this->assertEquals('iOS', $scan->os_family);
        $this->assertEquals('Safari', $scan->browser_family);
        $this->assertFalse($scan->is_bot);
        $this->assertEquals('redirect', $scan->response_type);
        $this->assertGreaterThan(0, $scan->http_status);
        $this->assertGreaterThanOrEqual(0, $scan->response_time_ms);
    }

    public function test_scan_captures_all_five_utm_parameters(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123?' . http_build_query([
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'summer_sale',
            'utm_term' => 'qr+code',
            'utm_content' => 'header_banner',
            'utm_invalid' => 'should_be_ignored',
            'foo' => 'bar',
        ]), [
            'User-Agent' => 'Mozilla/5.0',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertEquals('newsletter', $scan->utm_source);
        $this->assertEquals('email', $scan->utm_medium);
        $this->assertEquals('summer_sale', $scan->utm_campaign);
        $this->assertEquals('qr+code', $scan->utm_term);
        $this->assertEquals('header_banner', $scan->utm_content);
    }

    public function test_scan_does_not_store_non_utm_query_params(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123?utm_source=google&foo=bar&password=secret123', [
            'User-Agent' => 'Mozilla/5.0',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertEquals('google', $scan->utm_source);
        // The non-UTM params must not appear in any column.
        $rowArray = $scan->toArray();
        $serialized = json_encode($rowArray);
        $this->assertStringNotContainsString('secret123', $serialized);
    }

    public function test_no_plaintext_ip_in_scan_row(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0',
            'REMOTE_ADDR' => '203.0.113.42',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $guard = new AllowlistGuard();
        $rowArray = $scan->toArray();
        $this->assertTrue($guard->isClean($rowArray), 'Scan row contains plaintext IP or sensitive data');

        // The ip_hash must not be the raw IP.
        $this->assertNotEquals('203.0.113.42', $scan->ip_hash);
    }

    public function test_no_cookies_or_authorization_persisted(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->call('GET', '/testcode123', [], [
            'session_id' => 'sensitive_session_value',
            'auth_token' => 'bearer_token_value',
        ], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_AUTHORIZATION' => 'Bearer secret_jwt_token',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);

        $serialized = json_encode($scan->toArray());
        $this->assertStringNotContainsString('sensitive_session_value', $serialized);
        $this->assertStringNotContainsString('bearer_token_value', $serialized);
        $this->assertStringNotContainsString('secret_jwt_token', $serialized);
    }

    public function test_bot_detection_flags_known_bots(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertTrue($scan->is_bot);
    }

    public function test_resolver_metadata_captured(): void
    {
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertContains($scan->response_type, ['view', 'redirect']);
        $this->assertGreaterThanOrEqual(200, $scan->http_status);
        $this->assertLessThan(600, $scan->http_status);
        $this->assertGreaterThanOrEqual(0, $scan->response_time_ms);
    }

    public function test_referer_truncated_when_too_long(): void
    {
        $qrCode = $this->createActiveQrCode();
        $longReferer = 'https://example.com/' . str_repeat('a', 600);

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0',
            'Referer' => $longReferer,
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertLessThanOrEqual(500, strlen($scan->referer));
    }

    public function test_geo_fields_are_nullable_without_database(): void
    {
        // Without a GeoIP database installed, geo fields should be null —
        // the scan must still succeed.
        $qrCode = $this->createActiveQrCode();

        $this->get('/testcode123', [
            'User-Agent' => 'Mozilla/5.0',
        ]);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();

        $this->assertNotNull($scan);
        $this->assertNull($scan->geo_country);
        $this->assertNull($scan->geo_region);
        $this->assertNull($scan->geo_city);
        $this->assertNull($scan->geo_asn);
    }

    public function test_allowlist_guard_sanitize_strips_non_allowlisted_keys(): void
    {
        $guard = new AllowlistGuard();

        $result = $guard->sanitize([
            'qr_code_id' => 1,
            'ip_hash' => 'abc123',
            'cookies' => 'session=xyz',
            'authorization' => 'Bearer token',
            'password' => 'secret',
            'extra_field' => 'not_allowed',
        ]);

        $this->assertArrayHasKey('qr_code_id', $result);
        $this->assertArrayHasKey('ip_hash', $result);
        $this->assertArrayNotHasKey('cookies', $result);
        $this->assertArrayNotHasKey('authorization', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('extra_field', $result);
    }
}
