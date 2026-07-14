<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Scan;
use App\Models\User;
use App\Services\QrCodeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class QrCodeBurnMaxScansConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function createRouteWithQrCode(array $qrAttributes = [], array $routeAttributes = []): QrCodeRoute
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Concurrency QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ], $qrAttributes));

        return QrCodeRoute::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'code' => 'BURN1',
            'host' => 'localhost',
        ], $routeAttributes));
    }

    public function test_burn_code_first_request_delivers_second_returns_410(): void
    {
        $route = $this->createRouteWithQrCode(['burn' => true]);

        $first = $this->get('/BURN1');
        $second = $this->get('/BURN1');

        $first->assertRedirect('https://example.com');
        $second->assertStatus(410);

        $qrCode = $route->fresh()->qrCode;
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(1, $qrCode->scan_count);
    }

    public function test_burn_concurrency_ten_requests_deliver_exactly_once(): void
    {
        $route = $this->createRouteWithQrCode(['burn' => true]);

        $statuses = [];
        foreach (range(1, 10) as $i) {
            $statuses[] = $this->get('/BURN1')->status();
        }

        $delivered = count(array_filter($statuses, fn (int $s) => $s !== 410));
        $gone = count(array_filter($statuses, fn (int $s) => $s === 410));

        $this->assertSame(1, $delivered, 'Burn code must deliver exactly once across 10 requests.');
        $this->assertSame(9, $gone, 'The remaining 9 requests must receive 410 Gone.');

        $qrCode = $route->fresh()->qrCode;
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(1, $qrCode->scan_count);
        $this->assertSame(1, Scan::count(), 'Only the winning request logs a scan.');
    }

    public function test_max_scans_three_delivers_exactly_three_then_410(): void
    {
        $route = $this->createRouteWithQrCode(['max_scans' => 3]);

        $statuses = [];
        foreach (range(1, 10) as $i) {
            $statuses[] = $this->get('/BURN1')->status();
        }

        $delivered = count(array_filter($statuses, fn (int $s) => $s !== 410));
        $gone = count(array_filter($statuses, fn (int $s) => $s === 410));

        $this->assertSame(3, $delivered, 'max_scans=3 must deliver exactly 3 times across 10 requests.');
        $this->assertSame(7, $gone, 'The remaining 7 requests must receive 410 Gone.');

        $qrCode = $route->fresh()->qrCode;
        $this->assertSame('burned', $qrCode->status, 'Code burns once the scan limit is reached.');
        $this->assertSame(3, $qrCode->scan_count);
        $this->assertSame(3, Scan::count(), 'Each winning request logs a scan.');
    }

    public function test_max_scans_five_delivers_five_times_then_410(): void
    {
        $route = $this->createRouteWithQrCode(['max_scans' => 5]);

        // First four deliveries keep the code active (limit not yet reached).
        foreach (range(1, 4) as $i) {
            $this->get('/BURN1')->assertRedirect('https://example.com');
        }
        $this->assertSame('active', $route->fresh()->qrCode->status);
        $this->assertSame(4, $route->fresh()->qrCode->scan_count);

        // Fifth delivery reaches the limit and burns the code, but still serves.
        $this->get('/BURN1')->assertRedirect('https://example.com');

        $qrCode = $route->fresh()->qrCode;
        $this->assertSame('burned', $qrCode->status, 'Status burns once scan_count reaches max_scans.');
        $this->assertSame(5, $qrCode->scan_count);

        // Sixth request is gone.
        $this->get('/BURN1')->assertStatus(410);
    }

    /**
     * Direct exercise of the atomic conditional UPDATE: calling resolve() many
     * times against a single in-memory model still yields exactly one winner,
     * because the WHERE status = 'active' clause rejects every later attempt
     * even when the loaded model is stale. This is the concurrency invariant.
     */
    public function test_resolve_atomic_consume_yields_single_winner_for_burn(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Burn unit',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'burn' => true,
        ]);

        $resolver = new QrCodeResolver();
        $request = Request::create('/r/test');

        $winners = 0;
        foreach (range(1, 10) as $i) {
            $response = $resolver->resolve($qrCode, $request);
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                $winners++;
            }
        }

        $this->assertSame(1, $winners, 'Only one resolve() call may consume the burn code.');

        $qrCode->refresh();
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(1, $qrCode->scan_count);
        $this->assertSame(1, Scan::count());
    }

    public function test_resolve_atomic_consume_yields_exactly_n_winners_for_max_scans(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Max scans unit',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'max_scans' => 3,
        ]);

        $resolver = new QrCodeResolver();
        $request = Request::create('/r/test');

        $winners = 0;
        foreach (range(1, 10) as $i) {
            $response = $resolver->resolve($qrCode, $request);
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                $winners++;
            }
        }

        $this->assertSame(3, $winners, 'Exactly 3 resolve() calls may consume a max_scans=3 code.');

        $qrCode->refresh();
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(3, $qrCode->scan_count);
        $this->assertSame(3, Scan::count());
    }
}
