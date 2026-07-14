<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_api_index_with_the_expected_json_structure(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk()
            ->assertJsonPath('name', 'qrm.sg API')
            ->assertJsonPath('version', '1.0')
            ->assertJsonStructure([
                'name',
                'version',
                'endpoints' => [
                    ['method', 'path', 'description', 'auth_required'],
                ],
            ]);
    }

    public function test_it_lists_the_available_endpoints_and_auth_flags(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk();

        $endpoints = collect($response->json('endpoints'));

        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'GET' && $endpoint['path'] === '/api'));
        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'GET' && $endpoint['path'] === '/api/qr-types'));
        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'POST' && $endpoint['path'] === '/api/billing/webhook'));
        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'GET' && $endpoint['path'] === '/api/qr-codes'));
        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'POST' && $endpoint['path'] === '/api/qr-codes'));
        $this->assertNotNull($endpoints->first(fn (array $endpoint) => $endpoint['method'] === 'POST' && $endpoint['path'] === '/api/billing/portal'));

        $this->assertFalse($this->endpoint($endpoints, 'GET', '/api')['auth_required']);
        $this->assertFalse($this->endpoint($endpoints, 'GET', '/api/qr-types')['auth_required']);
        $this->assertFalse($this->endpoint($endpoints, 'POST', '/api/billing/webhook')['auth_required']);
        $this->assertTrue($this->endpoint($endpoints, 'GET', '/api/qr-codes')['auth_required']);
        $this->assertSame(['qr:read'], $this->endpoint($endpoints, 'GET', '/api/qr-codes')['scopes']);
        $this->assertTrue($this->endpoint($endpoints, 'POST', '/api/qr-codes')['auth_required']);
        $this->assertSame(['qr:write'], $this->endpoint($endpoints, 'POST', '/api/qr-codes')['scopes']);
        $this->assertTrue($this->endpoint($endpoints, 'GET', '/api/user')['auth_required']);
    }

    private function endpoint(iterable $endpoints, string $method, string $path): array
    {
        $endpoint = $endpoints->first(fn (array $candidate) => $candidate['method'] === $method && $candidate['path'] === $path);

        $this->assertIsArray($endpoint, "Missing endpoint entry for {$method} {$path}");

        return $endpoint;
    }
}
