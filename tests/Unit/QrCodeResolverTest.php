<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use App\Services\QrCodeResolver;
use App\Services\QrTypes\QrTypeHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class QrCodeResolverTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeResolver $resolver;

    private function createActiveQrCode(array $attributes = []): QrCode
    {
        $user = User::factory()->create();

        return QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Active QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ], $attributes));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new QrCodeResolver();
    }

    public function test_has_handler_returns_true_for_registered_type(): void
    {
        $this->assertTrue($this->resolver->hasHandler('url'));
        $this->assertTrue($this->resolver->hasHandler('message'));
        $this->assertTrue($this->resolver->hasHandler('redirect'));
        $this->assertTrue($this->resolver->hasHandler('social'));
        $this->assertTrue($this->resolver->hasHandler('wifi'));
        $this->assertTrue($this->resolver->hasHandler('event'));
        $this->assertTrue($this->resolver->hasHandler('vcard'));
    }

    public function test_has_handler_returns_false_for_unregistered_type(): void
    {
        $this->assertFalse($this->resolver->hasHandler('nonexistent'));
    }

    public function test_get_handler_returns_correct_handler(): void
    {
        $handler = $this->resolver->getHandler('url');

        $this->assertInstanceOf(QrTypeHandler::class, $handler);
    }

    public function test_get_handler_throws_for_unknown_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for QR code type: nonexistent');

        $this->resolver->getHandler('nonexistent');
    }

    public function test_extend_registers_custom_handler(): void
    {
        $customHandler = new class implements QrTypeHandler {
            public function handle(QrCode $qrCode, Request $request): mixed
            {
                return 'custom';
            }
        };

        $this->resolver->extend('text', $customHandler);

        $this->assertTrue($this->resolver->hasHandler('text'));
        $this->assertSame($customHandler, $this->resolver->getHandler('text'));
    }

    public function test_resolve_active_url_qr_creates_scan_and_increments_count(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test');

        $this->assertEquals(0, $qrCode->scan_count);

        $response = $this->resolver->resolve($qrCode, $request);

        $qrCode->refresh();
        $this->assertEquals(1, $qrCode->scan_count);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseCount('scans', 1);
    }

    public function test_resolve_inactive_qr_code_returns_error_view(): void
    {
        $qrCode = $this->createActiveQrCode(['status' => 'disabled']);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(423, $response->getStatusCode());
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_resolve_expired_qr_code_returns_error_view(): void
    {
        $qrCode = $this->createActiveQrCode([
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_resolve_burned_qr_code_returns_error_view(): void
    {
        $qrCode = $this->createActiveQrCode([
            'burn' => true,
            'scan_count' => 1,
        ]);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
    }

    public function test_resolve_max_scans_reached_returns_error_view(): void
    {
        $qrCode = $this->createActiveQrCode([
            'max_scans' => 3,
            'scan_count' => 3,
        ]);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
    }

    public function test_resolve_burn_code_sets_status_to_burned_after_scan(): void
    {
        $qrCode = $this->createActiveQrCode([
            'burn' => true,
            'scan_count' => 0,
        ]);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $qrCode->refresh();
        $this->assertEquals('burned', $qrCode->status);
        $this->assertEquals(1, $qrCode->scan_count);
    }

    public function test_resolve_password_protected_qr_without_password_returns_password_view(): void
    {
        $qrCode = $this->createActiveQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);
        $request = Request::create('/r/test');

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_resolve_password_protected_qr_with_correct_password_succeeds(): void
    {
        $qrCode = $this->createActiveQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        // DEV-613 (F1/F2): the GET resolver honours ONLY the signed grant cookie
        // issued by POST /r/{code}/password; a query-string password is ignored.
        $token = hash_hmac('sha256', $qrCode->id . $qrCode->password_hash, config('app.key'));
        $request = Request::create('/r/test', 'GET', [], ['qr_password_' . $qrCode->id => $token]);

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseCount('scans', 1);
    }

    public function test_resolve_password_protected_qr_with_wrong_password_fails(): void
    {
        $qrCode = $this->createActiveQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);
        $request = Request::create('/r/test', 'GET', ['password' => 'wrongpassword']);

        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_scan_created_with_correct_data(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
            'HTTP_REFERER' => 'https://referrer.example.com',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'user_agent_raw' => 'Mozilla/5.0 Test Browser',
            'referer' => 'https://referrer.example.com',
            'accept_language' => 'en-US,en;q=0.9',
        ]);
    }

    public function test_scan_detects_bots(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'is_bot' => true,
        ]);
    }

    public function test_scan_detects_device_type(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'device_type' => 'mobile',
        ]);
    }

    public function test_scan_detects_desktop_device(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'device_type' => 'desktop',
        ]);
    }

    public function test_scan_detects_os_family(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'os_family' => 'macOS',
        ]);
    }

    public function test_scan_detects_browser_family(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $this->assertDatabaseHas('scans', [
            'qr_code_id' => $qrCode->id,
            'browser_family' => 'Chrome',
        ]);
    }

    public function test_scan_ip_is_hashed(): void
    {
        $qrCode = $this->createActiveQrCode();
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $scan = \App\Models\Scan::where('qr_code_id', $qrCode->id)->first();
        $this->assertNotNull($scan);
        $this->assertNotEquals('192.168.1.1', $scan->ip_hash);
        $this->assertEquals(64, strlen($scan->ip_hash));
    }
}
