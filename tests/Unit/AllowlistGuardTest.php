<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Analytics\AllowlistGuard;
use Tests\TestCase;

class AllowlistGuardTest extends TestCase
{
    private AllowlistGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new AllowlistGuard();
    }

    public function test_allowlist_contains_all_required_scan_columns(): void
    {
        $required = [
            'qr_code_id', 'ip_hash', 'user_agent_raw', 'user_agent_parsed',
            'referer', 'accept_language',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'geo_country', 'geo_region', 'geo_city', 'geo_asn',
            'device_type', 'os_family', 'browser_family', 'is_bot',
            'response_type', 'http_status', 'response_time_ms',
            'scanned_at',
        ];

        foreach ($required as $column) {
            $this->assertContains($column, $this->guard->allowedColumns(), "Missing allowlist column: {$column}");
        }
    }

    public function test_sanitize_strips_unauthorized_keys(): void
    {
        $input = [
            'qr_code_id' => 42,
            'ip_hash' => 'hash_value',
            'cookie' => 'session=secret',
            'authorization' => 'Bearer xyz',
            'password' => 'p@ssw0rd',
            'custom_field' => 'not_allowed',
        ];

        $result = $this->guard->sanitize($input);

        $this->assertArrayHasKey('qr_code_id', $result);
        $this->assertArrayHasKey('ip_hash', $result);
        $this->assertArrayNotHasKey('cookie', $result);
        $this->assertArrayNotHasKey('authorization', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('custom_field', $result);
    }

    public function test_sanitize_truncates_long_strings(): void
    {
        $longString = str_repeat('x', 600);

        $result = $this->guard->sanitize([
            'referer' => $longString,
            'accept_language' => $longString,
        ]);

        $this->assertLessThanOrEqual(500, strlen($result['referer']));
        $this->assertLessThanOrEqual(500, strlen($result['accept_language']));
    }

    public function test_is_clean_rejects_plaintext_ip(): void
    {
        $this->assertFalse($this->guard->isClean([
            'referer' => '203.0.113.42',
        ]));
    }

    public function test_is_clean_accepts_hashed_ip(): void
    {
        $this->assertTrue($this->guard->isClean([
            'ip_hash' => str_repeat('a', 64),
            'referer' => 'https://example.com',
        ]));
    }

    public function test_is_clean_rejects_sensitive_column_names(): void
    {
        $this->assertFalse($this->guard->isClean([
            'password' => 'secret_value',
        ]));
    }

    public function test_sanitize_preserves_null_values(): void
    {
        $result = $this->guard->sanitize([
            'referer' => null,
            'utm_source' => null,
        ]);

        $this->assertNull($result['referer']);
        $this->assertNull($result['utm_source']);
    }
}
