<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\CreateQrCodeRequest;
use App\Http\Requests\UpdateQrCodeRequest;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QrCodeFormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_request_requires_title(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_create_request_requires_valid_type(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'invalid_type',
            'content' => ['url' => 'https://example.com'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_create_request_requires_content(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'url',
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    public function test_create_request_validates_message_content(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'message',
            'content' => [],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content.message', $validator->errors()->toArray());
    }

    public function test_create_request_accepts_valid_url_qr(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_create_request_rejects_invalid_url_in_content(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'not-a-url'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content.url', $validator->errors()->toArray());
    }

    public function test_create_request_accepts_valid_message_qr(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'message',
            'content' => ['message' => 'Hello World'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_create_request_accepts_valid_redirect_qr(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'redirect',
            'content' => [
                'target_url' => 'https://example.com',
                'redirect_code' => '301',
            ],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_create_request_accepts_valid_social_qr(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'social',
            'content' => [
                'platform' => 'github',
                'username' => 'octocat',
            ],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_create_request_validates_alias_format(): void
    {
        $request = CreateQrCodeRequest::create('/api/qr-codes', 'POST', [
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'alias' => '-bad-alias',
        ]);
        $request->setContainer(app())->setUserResolver(fn () => User::factory()->create());

        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('alias', $validator->errors()->toArray());
    }

    public function test_update_request_allows_partial_updates(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Original',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Route::getRoutes()->add(
            Route::put('/api/qr-codes/{qr_code}', fn () => null)->name('qr-codes.update')
        );

        $request = UpdateQrCodeRequest::create("/api/qr-codes/{$qrCode->id}", 'PUT', [
            'title' => 'Updated',
        ]);
        $request->setContainer(app())->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

        $validator = validator($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_update_request_validates_content_against_existing_type(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Original',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Route::getRoutes()->add(
            Route::put('/api/qr-codes/{qr_code}', fn () => null)->name('qr-codes.update')
        );

        $request = UpdateQrCodeRequest::create("/api/qr-codes/{$qrCode->id}", 'PUT', [
            'content' => ['url' => 'not-a-valid-url'],
        ]);
        $request->setContainer(app())->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

        $validator = validator($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content.url', $validator->errors()->toArray());
    }
}
