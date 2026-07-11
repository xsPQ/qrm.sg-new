<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\QrTypes\MessageQr;
use App\Domain\QrTypes\RedirectQr;
use App\Domain\QrTypes\SocialQr;
use App\Domain\QrTypes\UrlQr;
use InvalidArgumentException;
use Tests\TestCase;

class QrCodeDomainTest extends TestCase
{
    public function test_message_qr_from_array_valid(): void
    {
        $qr = MessageQr::fromArray(['message' => 'Hello World']);

        $this->assertInstanceOf(MessageQr::class, $qr);
        $this->assertEquals('Hello World', $qr->message);
    }

    public function test_message_qr_from_array_rejects_empty_message(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MessageQr::fromArray(['message' => '']);
    }

    public function test_message_qr_from_array_rejects_missing_message(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MessageQr::fromArray([]);
    }

    public function test_message_qr_from_array_rejects_too_long_message(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MessageQr::fromArray(['message' => str_repeat('a', 2001)]);
    }

    public function test_message_qr_from_array_accepts_max_length(): void
    {
        $qr = MessageQr::fromArray(['message' => str_repeat('a', 2000)]);

        $this->assertEquals(2000, mb_strlen($qr->message));
    }

    public function test_message_qr_to_array(): void
    {
        $qr = MessageQr::fromArray(['message' => 'Hello']);
        $data = $qr->toArray();

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Hello', $data['message']);
    }

    public function test_message_qr_rules(): void
    {
        $rules = MessageQr::rules();

        $this->assertArrayHasKey('content.message', $rules);
    }

    public function test_url_qr_from_array_valid(): void
    {
        $qr = UrlQr::fromArray(['url' => 'https://example.com']);

        $this->assertInstanceOf(UrlQr::class, $qr);
        $this->assertEquals('https://example.com', $qr->url);
    }

    public function test_url_qr_from_array_rejects_invalid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlQr::fromArray(['url' => 'not-a-valid-url']);
    }

    public function test_url_qr_from_array_rejects_missing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlQr::fromArray([]);
    }

    public function test_url_qr_from_array_rejects_too_long_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UrlQr::fromArray(['url' => 'https://example.com/' . str_repeat('a', 2048)]);
    }

    public function test_url_qr_to_array(): void
    {
        $qr = UrlQr::fromArray(['url' => 'https://example.com']);
        $data = $qr->toArray();

        $this->assertEquals('https://example.com', $data['url']);
    }

    public function test_url_qr_rules(): void
    {
        $rules = UrlQr::rules();

        $this->assertArrayHasKey('content.url', $rules);
    }

    public function test_redirect_qr_from_array_valid(): void
    {
        $qr = RedirectQr::fromArray([
            'target_url' => 'https://example.com',
            'redirect_code' => '301',
        ]);

        $this->assertInstanceOf(RedirectQr::class, $qr);
        $this->assertEquals('https://example.com', $qr->targetUrl);
        $this->assertEquals('301', $qr->redirectCode);
    }

    public function test_redirect_qr_from_array_default_redirect_code(): void
    {
        $qr = RedirectQr::fromArray([
            'target_url' => 'https://example.com',
        ]);

        $this->assertEquals('302', $qr->redirectCode);
    }

    public function test_redirect_qr_from_array_rejects_invalid_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RedirectQr::fromArray([
            'target_url' => 'https://example.com',
            'redirect_code' => '200',
        ]);
    }

    public function test_redirect_qr_from_array_rejects_missing_target_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RedirectQr::fromArray(['redirect_code' => '302']);
    }

    public function test_redirect_qr_from_array_rejects_invalid_target_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RedirectQr::fromArray([
            'target_url' => 'not-a-url',
            'redirect_code' => '302',
        ]);
    }

    public function test_redirect_qr_from_array_accepts_all_valid_codes(): void
    {
        foreach (['301', '302', '307', '308'] as $code) {
            $qr = RedirectQr::fromArray([
                'target_url' => 'https://example.com',
                'redirect_code' => $code,
            ]);
            $this->assertEquals($code, $qr->redirectCode);
        }
    }

    public function test_redirect_qr_to_array(): void
    {
        $qr = RedirectQr::fromArray([
            'target_url' => 'https://example.com',
            'redirect_code' => '301',
        ]);
        $data = $qr->toArray();

        $this->assertEquals('https://example.com', $data['target_url']);
        $this->assertEquals('301', $data['redirect_code']);
    }

    public function test_redirect_qr_rules(): void
    {
        $rules = RedirectQr::rules();

        $this->assertArrayHasKey('content.target_url', $rules);
        $this->assertArrayHasKey('content.redirect_code', $rules);
    }

    public function test_social_qr_from_array_valid(): void
    {
        $qr = SocialQr::fromArray([
            'platform' => 'github',
            'username' => 'octocat',
        ]);

        $this->assertInstanceOf(SocialQr::class, $qr);
        $this->assertEquals('github', $qr->platform);
        $this->assertEquals('octocat', $qr->username);
        $this->assertEquals('https://github.com/octocat', $qr->profileUrl);
    }

    public function test_social_qr_computes_profile_url_for_linkedin(): void
    {
        $qr = SocialQr::fromArray([
            'platform' => 'linkedin',
            'username' => 'johndoe',
        ]);

        $this->assertEquals('https://linkedin.com/in/johndoe', $qr->profileUrl);
    }

    public function test_social_qr_computes_profile_url_for_x(): void
    {
        $qr = SocialQr::fromArray([
            'platform' => 'x',
            'username' => 'johndoe',
        ]);

        $this->assertEquals('https://x.com/johndoe', $qr->profileUrl);
    }

    public function test_social_qr_from_array_rejects_invalid_platform(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SocialQr::fromArray([
            'platform' => 'myspace',
            'username' => 'johndoe',
        ]);
    }

    public function test_social_qr_from_array_rejects_missing_username(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SocialQr::fromArray(['platform' => 'github']);
    }

    public function test_social_qr_from_array_website_uses_username_as_url(): void
    {
        $qr = SocialQr::fromArray([
            'platform' => 'website',
            'username' => 'https://johndoe.com',
        ]);

        $this->assertEquals('https://johndoe.com', $qr->profileUrl);
    }

    public function test_social_qr_to_array(): void
    {
        $qr = SocialQr::fromArray([
            'platform' => 'github',
            'username' => 'octocat',
        ]);
        $data = $qr->toArray();

        $this->assertEquals('github', $data['platform']);
        $this->assertEquals('octocat', $data['username']);
        $this->assertEquals('https://github.com/octocat', $data['profile_url']);
    }

    public function test_social_qr_rules(): void
    {
        $rules = SocialQr::rules();

        $this->assertArrayHasKey('content.platform', $rules);
        $this->assertArrayHasKey('content.username', $rules);
    }

    public function test_social_qr_compute_profile_url_handles_unknown_platform(): void
    {
        $url = SocialQr::computeProfileUrl('unknown', 'test');

        $this->assertEquals('', $url);
    }
}
