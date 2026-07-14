<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_endpoints_array(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk()
            ->assertJsonStructure([
                'name',
                'version',
                'endpoints' => [
                    ['method', 'path', 'description', 'auth_required'],
                ],
            ]);
    }

    public function test_contains_qr_types_endpoint(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk();

        $this->assertContains('/api/qr-types', array_column($response->json('endpoints'), 'path'));
    }

    public function test_contains_qr_codes_crud(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk();

        $paths = array_column($response->json('endpoints'), 'path');
        $methods = array_column($response->json('endpoints'), 'method');

        $this->assertContains('/api/qr-codes', $paths);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('PATCH', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function test_no_auth_required(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk()
            ->assertJsonPath('endpoints.0.auth_required', false);
    }
}
