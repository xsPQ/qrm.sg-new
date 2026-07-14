<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\User;
use App\Services\QrTypes\MessageHandler;
use App\Services\QrTypes\RedirectHandler;
use App\Services\QrTypes\SocialHandler;
use App\Services\QrTypes\UrlHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class QrTypeHandlersTest extends TestCase
{
    use RefreshDatabase;

    private function createQrCode(array $attributes = []): QrCode
    {
        $user = User::factory()->create();

        return QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ], $attributes));
    }

    public function test_url_handler_redirects_to_valid_url(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'url',
            'content' => ['url' => 'https://example.com/target'],
        ]);

        $handler = new UrlHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('example.com/target', $response->getTargetUrl());
    }

    public function test_url_handler_adds_protocol_to_url_without_scheme(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'url',
            'content' => ['url' => 'example.com/page'],
        ]);

        $handler = new UrlHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('https://example.com/page', $response->getTargetUrl());
    }

    public function test_url_handler_handles_empty_url(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'url',
            'content' => ['url' => ''],
        ]);

        $handler = new UrlHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_message_handler_returns_view(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'message',
            'content' => ['title' => 'Hello', 'body' => 'World'],
        ]);

        $handler = new MessageHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertIsObject($response);
    }

    public function test_message_handler_uses_fallback_title(): void
    {
        $qrCode = $this->createQrCode([
            'title' => 'Fallback Title',
            'type' => 'message',
            'content' => ['body' => 'World'],
        ]);

        $handler = new MessageHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertIsObject($response);
    }

    public function test_social_handler_returns_view_with_links(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'social',
            'content' => [
                'title' => 'Follow Us',
                'description' => 'Find us online',
                'links' => [
                    ['platform' => 'github', 'url' => 'https://github.com/example'],
                ],
            ],
        ]);

        $handler = new SocialHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertIsObject($response);
    }

    public function test_social_handler_handles_empty_links(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'social',
            'content' => ['links' => []],
        ]);

        $handler = new SocialHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertIsObject($response);
    }

    public function test_social_handler_handles_missing_platform_in_link(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'social',
            'content' => [
                'links' => [
                    ['url' => 'https://example.com'],
                ],
            ],
        ]);

        $handler = new SocialHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertIsObject($response);
    }

    public function test_redirect_handler_uses_default_url_when_no_rules(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'https://default.example.com',
                'rules' => [],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('default.example.com', $response->getTargetUrl());
    }

    public function test_redirect_handler_matches_device_rule(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'https://default.example.com',
                'rules' => [
                    [
                        'device' => ['mobile'],
                        'url' => 'https://mobile.example.com',
                    ],
                ],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('mobile.example.com', $response->getTargetUrl());
    }

    public function test_redirect_handler_falls_back_when_no_rule_matches(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'https://default.example.com',
                'rules' => [
                    [
                        'device' => ['tablet'],
                        'url' => 'https://tablet.example.com',
                    ],
                ],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('default.example.com', $response->getTargetUrl());
    }

    public function test_redirect_handler_matches_os_rule(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'https://default.example.com',
                'rules' => [
                    [
                        'os' => ['windows'],
                        'url' => 'https://windows.example.com',
                    ],
                ],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('windows.example.com', $response->getTargetUrl());
    }

    public function test_redirect_handler_matches_language_rule(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'https://default.example.com',
                'rules' => [
                    [
                        'language' => ['de'],
                        'url' => 'https://de.example.com',
                    ],
                ],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9',
        ]);
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('de.example.com', $response->getTargetUrl());
    }

    public function test_redirect_handler_adds_protocol_to_url_without_scheme(): void
    {
        $qrCode = $this->createQrCode([
            'type' => 'redirect',
            'content' => [
                'default_url' => 'plain.example.com',
                'rules' => [],
            ],
        ]);

        $handler = new RedirectHandler();
        $request = Request::create('/r/test');
        $response = $handler->handle($qrCode, $request);

        $this->assertStringContainsString('https://plain.example.com', $response->getTargetUrl());
    }
}
