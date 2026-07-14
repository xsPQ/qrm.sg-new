<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SlugCollisionException;
use App\Models\QrCodeRoute;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QrCodeRouteService
{
    private const RESERVED_PATHS = [
        'login',
        'register',
        'api',
        'horizon',
        'health',
        'dashboard',
        'admin',
        'docs',
    ];

    private const CODE_LENGTH = 6;

    private const CODE_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const MAX_CODE_RETRIES = 5;

    private const ALIAS_MIN_LENGTH = 4;

    private const ALIAS_MAX_LENGTH = 32;

    private const ALIAS_PATTERN = '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

    public function generateRoute(int $qrCodeId, string $host, ?string $alias = null): QrCodeRoute
    {
        if ($alias !== null) {
            $this->validateAlias($alias, $host);

            if (! $this->isAliasAvailable($alias, $host)) {
                throw new SlugCollisionException("Alias '{$alias}' is already taken on host '{$host}'.");
            }
        }

        $attempts = 0;

        while ($attempts < self::MAX_CODE_RETRIES) {
            $code = $this->generateCode();

            try {
                return DB::transaction(function () use ($qrCodeId, $host, $alias, $code) {
                    if (! $this->isCodeAvailableInTx($code, $host)) {
                        throw new SlugCollisionException("Code '{$code}' is already taken on host '{$host}'.");
                    }

                    if ($alias !== null && ! $this->isAliasAvailableInTx($alias, $host)) {
                        throw new SlugCollisionException("Alias '{$alias}' is already taken on host '{$host}'.");
                    }

                    return QrCodeRoute::create([
                        'qr_code_id' => $qrCodeId,
                        'code' => $code,
                        'alias' => $alias,
                        'host' => $host,
                    ]);
                });
            } catch (UniqueConstraintViolationException $e) {
                $attempts++;
                if ($attempts >= self::MAX_CODE_RETRIES) {
                    throw new RuntimeException(
                        "Code generation exhausted retries after {$attempts} attempts.",
                        0,
                        $e,
                    );
                }
                continue;
            } catch (SlugCollisionException $e) {
                if (str_starts_with($e->getMessage(), 'Alias')) {
                    throw $e;
                }

                $attempts++;
                if ($attempts >= self::MAX_CODE_RETRIES) {
                    throw new RuntimeException(
                        "Code generation exhausted retries after {$attempts} attempts.",
                        0,
                        $e,
                    );
                }
                continue;
            }
        }

        throw new RuntimeException('Code generation exhausted retries.');
    }

    public function assignAlias(QrCodeRoute $route, string $alias): QrCodeRoute
    {
        $this->validateAlias($alias, $route->host);

        if ($route->alias !== null && strtolower($route->alias) === strtolower($alias)) {
            return $route;
        }

        if (! $this->isAliasAvailable($alias, $route->host, $route->id)) {
            throw new SlugCollisionException("Alias '{$alias}' is already taken on host '{$route->host}'.");
        }

        $route->update(['alias' => $alias]);

        return $route->fresh();
    }

    public function removeAlias(QrCodeRoute $route): QrCodeRoute
    {
        $route->update(['alias' => null]);

        return $route->fresh();
    }

    public function validateAlias(string $alias, string $host): void
    {
        $length = mb_strlen($alias);

        if ($length < self::ALIAS_MIN_LENGTH || $length > self::ALIAS_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                __('Alias must be between :min and :max characters.', ['min' => self::ALIAS_MIN_LENGTH, 'max' => self::ALIAS_MAX_LENGTH])
            );
        }

        if (! preg_match(self::ALIAS_PATTERN, $alias)) {
            throw new \InvalidArgumentException(
                __('Alias must start and end with an alphanumeric character and may only contain letters, numbers, and hyphens.')
            );
        }

        if ($this->isReservedPath($alias)) {
            throw new \InvalidArgumentException(__(':value is a reserved system path and cannot be used as an alias.', ['value' => $alias]));
        }
    }

    public function isReservedPath(string $slug): bool
    {
        $normalized = strtolower(trim($slug, '/'));

        foreach (self::RESERVED_PATHS as $reserved) {
            if ($normalized === $reserved) {
                return true;
            }
            if (str_starts_with($normalized, $reserved . '/')) {
                return true;
            }
        }

        return false;
    }

    public function isCodeAvailable(string $code, string $host, ?int $excludeId = null): bool
    {
        $query = QrCodeRoute::where('host', $host)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)]);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    public function isAliasAvailable(string $alias, string $host, ?int $excludeId = null): bool
    {
        $query = QrCodeRoute::where('host', $host)
            ->where('alias', '!=', null)
            ->whereRaw('LOWER(alias) = ?', [strtolower($alias)]);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    public function findByCode(string $code, string $host): ?QrCodeRoute
    {
        return QrCodeRoute::where('host', $host)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }

    public function findByAlias(string $alias, string $host): ?QrCodeRoute
    {
        return QrCodeRoute::where('host', $host)
            ->where('alias', '!=', null)
            ->whereRaw('LOWER(alias) = ?', [strtolower($alias)])
            ->first();
    }

    /**
     * Resolve a public slug (short code or custom alias) for a given host.
     * Case-insensitive, host-scoped. Aliases take precedence over codes.
     */
    public function findByCodeOrAlias(string $codeOrAlias, string $host): ?QrCodeRoute
    {
        $byAlias = $this->findByAlias($codeOrAlias, $host);

        if ($byAlias !== null) {
            return $byAlias;
        }

        return $this->findByCode($codeOrAlias, $host);
    }

    public static function getReservedPaths(): array
    {
        return self::RESERVED_PATHS;
    }

    protected function generateCode(): string
    {
        $charsetLength = strlen(self::CODE_CHARSET);
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $code;
    }

    private function isCodeAvailableInTx(string $code, string $host): bool
    {
        return QrCodeRoute::where('host', $host)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->lockForUpdate()
            ->doesntExist();
    }

    private function isAliasAvailableInTx(string $alias, string $host): bool
    {
        return QrCodeRoute::where('host', $host)
            ->where('alias', '!=', null)
            ->whereRaw('LOWER(alias) = ?', [strtolower($alias)])
            ->lockForUpdate()
            ->doesntExist();
    }
}
