<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use App\Services\QrCodeRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QrCodeRouteConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var int[] */
    private array $qrCodeIds;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->qrCodeIds = [];
        foreach (range(1, 4) as $i) {
            $qr = QrCode::create([
                'user_id' => $user->id,
                'title' => "QR {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
            $this->qrCodeIds[$i] = $qr->id;
        }
    }

    public function test_concurrent_code_generation_no_deadlock(): void
    {
        $host = 'example.com';
        $results = [];
        $errors = [];

        $generateTask = function (int $qrCodeId) use ($host, &$results, &$errors): void {
            try {
                $service = new QrCodeRouteService();
                $route = $service->generateRoute($qrCodeId, $host);
                $results[] = $route->code;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        };

        DB::transaction(function () use ($generateTask) {
            $generateTask($this->qrCodeIds[1]);
        });

        $generateTask($this->qrCodeIds[2]);
        $generateTask($this->qrCodeIds[3]);
        $generateTask($this->qrCodeIds[4]);

        $this->assertEmpty($errors, 'Concurrent generation produced errors: ' . implode('; ', $errors));
        $this->assertCount(4, $results);
        $this->assertCount(4, array_unique($results), 'Generated codes must be unique');
    }

    public function test_parallel_alias_creation_one_wins_one_gets_409(): void
    {
        $host = 'example.com';
        $winnerCount = 0;
        $conflictCount = 0;

        $createFn = function (int $qrCodeId) use ($host, &$winnerCount, &$conflictCount): void {
            try {
                $service = new QrCodeRouteService();
                $service->generateRoute($qrCodeId, $host, 'parallel-alias');
                $winnerCount++;
            } catch (\App\Exceptions\SlugCollisionException $e) {
                if ($e->getCode() === 409) {
                    $conflictCount++;
                }
            }
        };

        $createFn($this->qrCodeIds[1]);
        $createFn($this->qrCodeIds[2]);
        $createFn($this->qrCodeIds[3]);

        $this->assertEquals(1, $winnerCount, 'Exactly one request should win the alias.');
        $this->assertEquals(2, $conflictCount, 'The other two requests should get 409.');
    }
}
