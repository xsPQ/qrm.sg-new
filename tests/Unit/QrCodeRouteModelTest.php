<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeRouteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_belongs_to_qr_code(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $route = QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'host' => 'example.com',
        ]);

        $this->assertInstanceOf(QrCode::class, $route->qrCode);
        $this->assertEquals($qrCode->id, $route->qrCode->id);
    }

    public function test_route_has_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $route = QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'XYZ789',
            'alias' => 'my-alias',
            'host' => 'example.com',
        ]);

        $this->assertEquals($qrCode->id, $route->qr_code_id);
        $this->assertEquals('XYZ789', $route->code);
        $this->assertEquals('my-alias', $route->alias);
        $this->assertEquals('example.com', $route->host);
    }

    public function test_timestamps_are_disabled(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $route = QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'TIMETEST',
            'host' => 'example.com',
        ]);

        $this->assertFalse($route->timestamps);
    }

    public function test_qr_code_has_route_relationship(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'QWERTY1',
            'host' => 'example.com',
        ]);

        $qrCode->refresh();

        $this->assertInstanceOf(QrCodeRoute::class, $qrCode->route);
        $this->assertEquals('QWERTY1', $qrCode->route->code);
    }
}
