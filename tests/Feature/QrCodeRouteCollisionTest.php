<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use App\Services\QrCodeRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeRouteCollisionTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeRouteService $service;
    /** @var int[] */
    private array $qrCodeIds;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrCodeRouteService();

        $user = User::factory()->create();

        $this->qrCodeIds = [];
        foreach (range(1, 10) as $i) {
            $qr = QrCode::create([
                'user_id' => $user->id,
                'title' => "QR {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
            $this->qrCodeIds[$i] = $qr->id;
        }
    }

    public function test_alias_collision_returns_409_http_status(): void
    {
        $this->service->generateRoute($this->qrCodeIds[1], 'example.com', 'cool-link');

        $this->expectException(\App\Exceptions\SlugCollisionException::class);
        $this->expectExceptionCode(409);

        $this->service->generateRoute($this->qrCodeIds[2], 'example.com', 'cool-link');
    }

    public function test_code_retry_exhaustion_throws(): void
    {
        $host = 'example.com';
        $qrCodeId = $this->qrCodeIds[1];

        $this->service->generateRoute($qrCodeId, $host);

        $usedCodes = QrCodeRoute::where('host', $host)->pluck('code')->toArray();
        $collisionCode = $usedCodes[0] ?? 'ABC123';

        $mock = $this->createPartialMock(QrCodeRouteService::class, ['generateCode']);
        $mock->method('generateCode')->willReturn($collisionCode);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exhausted retries');

        $mock->generateRoute($this->qrCodeIds[2], $host);
    }

    public function test_concurrent_alias_reservation_one_wins(): void
    {
        $host = 'example.com';

        $route = $this->service->generateRoute($this->qrCodeIds[1], $host, 'winning-alias');

        $this->assertNotNull($route);
        $this->assertEquals('winning-alias', $route->alias);

        $this->assertEquals(1, QrCodeRoute::where('host', $host)
            ->where('alias', 'winning-alias')
            ->count());

        try {
            $this->service->generateRoute($this->qrCodeIds[2], $host, 'winning-alias');
            $this->fail('Expected collision exception was not thrown.');
        } catch (\App\Exceptions\SlugCollisionException $e) {
            $this->assertEquals(409, $e->getCode());
        }
    }

    public function test_codes_are_unique_per_host(): void
    {
        $host = 'example.com';
        $codes = [];

        for ($i = 1; $i <= 10; $i++) {
            $route = $this->service->generateRoute($this->qrCodeIds[$i], $host);
            $codes[] = $route->code;
        }

        $this->assertCount(10, array_unique($codes));
    }

    public function test_same_code_different_hosts_allowed(): void
    {
        $route1 = $this->service->generateRoute($this->qrCodeIds[1], 'example.com');
        $code = $route1->code;

        QrCodeRoute::create([
            'qr_code_id' => $this->qrCodeIds[2],
            'code' => $code,
            'host' => 'other.com',
        ]);

        $this->assertDatabaseHas('qr_code_routes', [
            'code' => $code,
            'host' => 'example.com',
        ]);
        $this->assertDatabaseHas('qr_code_routes', [
            'code' => $code,
            'host' => 'other.com',
        ]);
    }
}
