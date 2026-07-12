<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRateLimitHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_response_includes_rate_limit_headers(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    public function test_api_rate_limit_returns_429_with_retry_after(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        for ($i = 0; $i < 61; $i++) {
            $lastResponse = $this->getJson('/api/user');
        }

        $lastResponse->assertStatus(429);
        $lastResponse->assertHeader('Retry-After');
        $lastResponse->assertHeader('X-RateLimit-Limit');
        $lastResponse->assertHeader('X-RateLimit-Remaining');
        $lastResponse->assertJson([
            'code' => 'RATE_LIMIT_EXCEEDED',
        ]);
    }
}
