<?php

declare(strict_types=1);

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class SocialQr
{
    private const VALID_PLATFORMS = [
        'linkedin',
        'twitter',
        'x',
        'instagram',
        'facebook',
        'github',
        'youtube',
        'tiktok',
        'website',
    ];

    public function __construct(
        public readonly string $platform,
        public readonly string $username,
        public readonly ?string $profileUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $platform = $data['platform'] ?? null;
        if ($platform === null || $platform === '') {
            $errors[] = 'platform is required';
        } elseif (! in_array($platform, self::VALID_PLATFORMS, true)) {
            $errors[] = 'platform must be one of: ' . implode(', ', self::VALID_PLATFORMS);
        }

        $username = $data['username'] ?? null;
        if ($username === null || $username === '') {
            $errors[] = 'username is required';
        } elseif (! is_string($username) || mb_strlen($username) > 255) {
            $errors[] = 'username must be a string of max 255 characters';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid SocialQr content: ' . implode('; ', $errors));
        }

        $profileUrl = $data['profile_url'] ?? null;
        if ($profileUrl === null || $profileUrl === '') {
            $profileUrl = self::computeProfileUrl($platform, $username);
        }

        return new self(
            platform: $platform,
            username: $username,
            profileUrl: $profileUrl,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'platform' => $this->platform,
            'username' => $this->username,
            'profile_url' => $this->profileUrl,
        ], fn ($v) => $v !== null);
    }

    public static function rules(): array
    {
        return [
            'content.platform' => ['required', 'string', 'in:' . implode(',', self::VALID_PLATFORMS)],
            'content.username' => ['required', 'string', 'max:255'],
        ];
    }

    public static function computeProfileUrl(string $platform, string $username): string
    {
        return match ($platform) {
            'linkedin' => "https://linkedin.com/in/{$username}",
            'twitter', 'x' => "https://x.com/{$username}",
            'instagram' => "https://instagram.com/{$username}",
            'facebook' => "https://facebook.com/{$username}",
            'github' => "https://github.com/{$username}",
            'youtube' => "https://youtube.com/@{$username}",
            'tiktok' => "https://tiktok.com/@{$username}",
            'website' => $username,
            default => '',
        };
    }
}
