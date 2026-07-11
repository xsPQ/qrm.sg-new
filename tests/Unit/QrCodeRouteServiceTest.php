<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SlugCollisionException;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use App\Services\QrCodeRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeRouteServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeRouteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrCodeRouteService();

        $user = User::factory()->create();

        foreach (range(1, 2) as $i) {
            QrCode::create([
                'user_id' => $user->id,
                'title' => "QR {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }
    }

    public function test_reserved_paths_list_is_accessible(): void
    {
        $paths = QrCodeRouteService::getReservedPaths();

        $this->assertContains('login', $paths);
        $this->assertContains('api', $paths);
        $this->assertContains('admin', $paths);
        $this->assertCount(8, $paths);
    }

    public function test_is_reserved_path_exact_match(): void
    {
        $this->assertTrue($this->service->isReservedPath('login'));
        $this->assertTrue($this->service->isReservedPath('LOGIN'));
        $this->assertTrue($this->service->isReservedPath('/login/'));
    }

    public function test_is_reserved_path_prefix(): void
    {
        $this->assertTrue($this->service->isReservedPath('api/v1'));
        $this->assertTrue($this->service->isReservedPath('docs/getting-started'));
    }

    public function test_is_reserved_path_not_reserved(): void
    {
        $this->assertFalse($this->service->isReservedPath('my-link'));
        $this->assertFalse($this->service->isReservedPath('hello'));
        $this->assertFalse($this->service->isReservedPath('dashboard-pro'));
    }

    public function test_validate_alias_rejects_reserved_paths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved system path');

        $this->service->validateAlias('login', 'example.com');
    }

    public function test_validate_alias_rejects_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('between 4 and 32');

        $this->service->validateAlias('abc', 'example.com');
    }

    public function test_validate_alias_rejects_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('between 4 and 32');

        $this->service->validateAlias(str_repeat('a', 33), 'example.com');
    }

    public function test_validate_alias_rejects_leading_hyphen(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alphanumeric character');

        $this->service->validateAlias('-mylink', 'example.com');
    }

    public function test_validate_alias_rejects_trailing_hyphen(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alphanumeric character');

        $this->service->validateAlias('mylink-', 'example.com');
    }

    public function test_validate_alias_rejects_special_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alphanumeric character');

        $this->service->validateAlias('my_link', 'example.com');
    }

    public function test_validate_alias_rejects_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('alphanumeric character');

        $this->service->validateAlias('my link', 'example.com');
    }

    public function test_validate_alias_accepts_valid_aliases(): void
    {
        $this->service->validateAlias('my-link', 'example.com');
        $this->service->validateAlias('link123', 'example.com');
        $this->service->validateAlias('a-b-c-d', 'example.com');
        $this->service->validateAlias('ABCD', 'example.com');
        $this->service->validateAlias(str_repeat('a', 32), 'example.com');

        $this->assertTrue(true);
    }

    public function test_generate_route_creates_record_with_code(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');

        $this->assertInstanceOf(QrCodeRoute::class, $route);
        $this->assertEquals(1, $route->qr_code_id);
        $this->assertEquals('example.com', $route->host);
        $this->assertNotNull($route->code);
        $this->assertEquals(6, strlen($route->code));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{6}$/', $route->code);
        $this->assertNull($route->alias);
    }

    public function test_generate_route_with_valid_alias(): void
    {
        $route = $this->service->generateRoute(1, 'example.com', 'my-link');

        $this->assertEquals('my-link', $route->alias);
        $this->assertNotNull($route->code);
    }

    public function test_generate_route_with_reserved_alias_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved system path');

        $this->service->generateRoute(1, 'example.com', 'admin');
    }

    public function test_alias_collision_different_host_allowed(): void
    {
        $this->service->generateRoute(1, 'example.com', 'my-link');

        $this->service->generateRoute(2, 'other.com', 'my-link');

        $this->assertDatabaseCount('qr_code_routes', 2);
    }

    public function test_alias_collision_same_host_throws(): void
    {
        $this->service->generateRoute(1, 'example.com', 'my-link');

        $this->expectException(SlugCollisionException::class);
        $this->expectExceptionMessage('already taken');

        $this->service->generateRoute(2, 'example.com', 'my-link');
    }

    public function test_alias_collision_case_insensitive(): void
    {
        $this->service->generateRoute(1, 'example.com', 'my-link');

        $this->expectException(SlugCollisionException::class);
        $this->expectExceptionMessage('already taken');

        $this->service->generateRoute(2, 'example.com', 'MY-LINK');
    }

    public function test_code_generation_no_collision_across_hosts(): void
    {
        $route1 = $this->service->generateRoute(1, 'example.com');
        $route2 = $this->service->generateRoute(2, 'other.com');

        $this->assertNotEquals($route1->code, $route2->code);
    }

    public function test_assign_alias_updates_route(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $this->assertNull($route->alias);

        $updated = $this->service->assignAlias($route, 'cool-link');

        $this->assertEquals('cool-link', $updated->alias);
    }

    public function test_assign_alias_same_value_noop(): void
    {
        $route = $this->service->generateRoute(1, 'example.com', 'my-link');
        $updated = $this->service->assignAlias($route, 'MY-LINK');

        $this->assertEquals('my-link', $updated->alias);
    }

    public function test_assign_alias_collision_throws(): void
    {
        $this->service->generateRoute(1, 'example.com', 'taken-link');
        $route2 = $this->service->generateRoute(2, 'example.com');

        $this->expectException(SlugCollisionException::class);
        $this->service->assignAlias($route2, 'taken-link');
    }

    public function test_remove_alias_sets_null(): void
    {
        $route = $this->service->generateRoute(1, 'example.com', 'my-link');
        $this->assertEquals('my-link', $route->alias);

        $updated = $this->service->removeAlias($route);

        $this->assertNull($updated->alias);
    }

    public function test_find_by_code_returns_correct_route(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $found = $this->service->findByCode($route->code, 'example.com');

        $this->assertNotNull($found);
        $this->assertEquals($route->id, $found->id);
    }

    public function test_find_by_code_case_insensitive(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $found = $this->service->findByCode(strtolower($route->code), 'example.com');

        $this->assertNotNull($found);
        $this->assertEquals($route->id, $found->id);
    }

    public function test_find_by_code_wrong_host_returns_null(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $found = $this->service->findByCode($route->code, 'other.com');

        $this->assertNull($found);
    }

    public function test_find_by_alias_returns_correct_route(): void
    {
        $this->service->generateRoute(1, 'example.com', 'my-link');
        $found = $this->service->findByAlias('my-link', 'example.com');

        $this->assertNotNull($found);
        $this->assertEquals('my-link', $found->alias);
    }

    public function test_find_by_alias_case_insensitive(): void
    {
        $this->service->generateRoute(1, 'example.com', 'my-link');
        $found = $this->service->findByAlias('MY-LINK', 'example.com');

        $this->assertNotNull($found);
        $this->assertEquals('my-link', $found->alias);
    }

    public function test_is_code_available_returns_true_for_unused_code(): void
    {
        $this->assertTrue($this->service->isCodeAvailable('abc123', 'example.com'));
    }

    public function test_is_code_available_returns_false_for_used_code(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $this->assertFalse($this->service->isCodeAvailable($route->code, 'example.com'));
    }

    public function test_is_code_available_excludes_given_id(): void
    {
        $route = $this->service->generateRoute(1, 'example.com');
        $this->assertTrue($this->service->isCodeAvailable($route->code, 'example.com', $route->id));
    }

    public function test_is_alias_available_same_checks(): void
    {
        $this->assertTrue($this->service->isAliasAvailable('my-link', 'example.com'));

        $this->service->generateRoute(1, 'example.com', 'my-link');
        $this->assertFalse($this->service->isAliasAvailable('my-link', 'example.com'));
    }
}
