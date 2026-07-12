<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WifiConnectControllerTest extends TestCase
{
    use RefreshDatabase;

    private QrCode $wifiQrCode;
    private string $routeCode;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->wifiQrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Hotel WiFi',
            'type' => 'wifi',
            'content' => [
                'ssid' => 'GrandHotel',
                'encryption' => 'WPA',
                'password' => 'supersecret123',
            ],
            'status' => 'active',
        ]);

        $this->routeCode = 'WIFITEST';
        QrCodeRoute::create([
            'qr_code_id' => $this->wifiQrCode->id,
            'host' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
            'code' => $this->routeCode,
        ]);
    }

    public function test_apple_profile_returns_mobileconfig(): void
    {
        $response = $this->get("/r/{$this->routeCode}/wifi.mobileconfig");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/x-apple-aspen-config');
        $response->assertHeader('Content-Disposition', 'attachment; filename="GrandHotel.mobileconfig"');

        $body = $response->getContent();
        $this->assertStringContainsString('com.apple.wifi.managed', $body);
        $this->assertStringContainsString('GrandHotel', $body);
        $this->assertStringContainsString('supersecret123', $body);
        $this->assertStringContainsString('<key>EncryptionType</key>', $body);
        $this->assertStringContainsString('WPA', $body);
    }

    public function test_apple_profile_for_open_network(): void
    {
        $this->wifiQrCode->update(['content' => [
            'ssid' => 'OpenCafe',
            'encryption' => 'none',
        ]]);

        $response = $this->get("/r/{$this->routeCode}/wifi.mobileconfig");

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('None', $body);
        $this->assertStringNotContainsString('<key>Password</key>', $body);
    }

    public function test_wifi_uri_returns_standard_format(): void
    {
        $response = $this->get("/r/{$this->routeCode}/wifi-uri");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $body = $response->getContent();
        $this->assertStringStartsWith('WIFI:S:GrandHotel;', $body);
        $this->assertStringContainsString('T:WPA;', $body);
        $this->assertStringContainsString('P:supersecret123;', $body);
    }

    public function test_wifi_uri_escapes_special_characters(): void
    {
        $this->wifiQrCode->update(['content' => [
            'ssid' => 'Cafe;Guest,Network',
            'encryption' => 'WPA',
            'password' => 'pass"word',
        ]]);

        $response = $this->get("/r/{$this->routeCode}/wifi-uri");

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('S:Cafe\;Guest\,Network;', $body);
        $this->assertStringContainsString('P:pass\"word;', $body);
    }

    public function test_apple_profile_returns_404_for_non_wifi_code(): void
    {
        $user = User::factory()->create();
        $urlQr = QrCode::create([
            'user_id' => $user->id,
            'title' => 'URL QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);
        QrCodeRoute::create([
            'qr_code_id' => $urlQr->id,
            'host' => 'localhost',
            'code' => 'URLTEST1',
        ]);

        $response = $this->get('/r/URLTEST1/wifi.mobileconfig');
        $response->assertNotFound();
    }

    public function test_apple_profile_returns_404_for_unknown_code(): void
    {
        $response = $this->get('/r/NOTEXIST/wifi.mobileconfig');
        $response->assertNotFound();
    }
}
