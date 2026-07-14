<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use App\Services\QrCodeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Regression coverage for the DEV-42 race-loser HTTP status.
 *
 * The atomic consume UPDATE guarantees exactly-once delivery for burn codes
 * and at-most-N for max_scans codes. The losing request (its in-memory model
 * was still active when it lost the conditional UPDATE) must surface as HTTP
 * 410 Gone so the public contract ("die andere 410" / "8 bekommen 410") holds
 * even under true parallel concurrency — not as a bare View that the controller
 * would serve as HTTP 200.
 */
class Dev42RaceProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_burn_race_loser_is_http_410_not_bare_view(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id, 'title' => 'probe', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active', 'burn' => true,
        ]);

        $resolver = new QrCodeResolver();
        $request = Request::create('/r/probe');

        $first = $resolver->resolve($qrCode, $request);   // winner
        $second = $resolver->resolve($qrCode, $request);  // stale-model loser of the atomic race

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $first, 'Winner must deliver the redirect content.');
        $this->assertInstanceOf(Response::class, $second, 'Loser must be a Response, not a bare View.');
        $this->assertSame(410, $second->getStatusCode(), 'Race loser must be 410 Gone, not 200.');

        $qrCode->refresh();
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(1, $qrCode->scan_count);
    }

    public function test_max_scans_race_loser_after_limit_is_http_410(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id, 'title' => 'probe', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active', 'max_scans' => 1,
        ]);

        $resolver = new QrCodeResolver();
        $request = Request::create('/r/probe');

        $first = $resolver->resolve($qrCode, $request);   // winner (reaches limit, burns)
        $second = $resolver->resolve($qrCode, $request);  // stale-model loser

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $first);
        $this->assertInstanceOf(Response::class, $second);
        $this->assertSame(410, $second->getStatusCode(), 'max_scans race loser must be 410 Gone.');

        $qrCode->refresh();
        $this->assertSame('burned', $qrCode->status);
        $this->assertSame(1, $qrCode->scan_count);
    }
}
