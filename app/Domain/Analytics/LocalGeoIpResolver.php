<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * LocalGeoIpResolver (Pflichtenheft §3.3.1 Geodaten, §3.3.4 GeoIP lokal)
 *
 * Resolves country/region/city/ASN from a client IP using a LOCAL MaxMind
 * GeoLite2 database. No external HTTP call is ever made — the .mmdb file is
 * read entirely from disk.
 *
 * Privacy: the raw IP is never persisted. Only the resolved geo fields are
 * returned to the caller for storage. The IP is consumed transiently and
 * discarded.
 *
 * Graceful degradation: if the database file is missing, outdated, or the IP
 * is private/reserved, all fields resolve to null with a logged warning. The
 * scan pipeline is never disrupted by GeoIP failures.
 */
class LocalGeoIpResolver
{
    private ?Reader $cityReader = null;
    private ?Reader $asnReader = null;
    private bool $initialised = false;
    private bool $dbAvailable = false;

    public function __construct(
        private readonly ?string $cityDbPath = null,
        private readonly ?string $asnDbPath = null,
        private readonly array $locales = ['en', 'de'],
    ) {}

    /**
     * Resolve geo data for an IP address.
     *
     * @return array{country: ?string, region: ?string, city: ?string, asn: ?string}
     */
    public function resolve(string $ip): array
    {
        $empty = ['country' => null, 'region' => null, 'city' => null, 'asn' => null];

        // Private, reserved, or loopback IPs are never geo-resolved.
        if (!$this->isPublicIp($ip)) {
            return $empty;
        }

        $this->initReaders();

        if (!$this->dbAvailable) {
            return $empty;
        }

        $result = $empty;

        // Country/region/city from the City database.
        if ($this->cityReader !== null) {
            try {
                $record = $this->cityReader->city($ip);
                $result['country'] = $record->country->isoCode;
                $result['region'] = $record->mostSpecificSubdivision->name;
                $result['city'] = $record->city->name;
            } catch (AddressNotFoundException) {
                // IP not in database — leave nulls.
            } catch (\Exception $e) {
                Log::warning('GeoIP city lookup failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ASN from the ASN database (or Traits in Enterprise databases).
        if ($this->asnReader !== null) {
            try {
                $record = $this->asnReader->asn($ip);
                $asn = $record->autonomousSystemNumber;
                $result['asn'] = $asn !== null ? 'AS' . $asn : null;
            } catch (AddressNotFoundException) {
                // IP not in database — leave null.
            } catch (\Exception $e) {
                Log::warning('GeoIP ASN lookup failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Whether the GeoIP database is available for lookups.
     */
    public function isAvailable(): bool
    {
        $this->initReaders();

        return $this->dbAvailable;
    }

    /**
     * Initialise MaxMind readers lazily. If the DB files don't exist, the
     * resolver degrades to nulls with a single warning.
     */
    private function initReaders(): void
    {
        if ($this->initialised) {
            return;
        }

        $this->initialised = true;

        if ($this->cityDbPath && file_exists($this->cityDbPath)) {
            try {
                $this->cityReader = new Reader($this->cityDbPath, $this->locales);
                $this->dbAvailable = true;
            } catch (\Exception $e) {
                Log::warning('GeoIP city database could not be opened', [
                    'path' => $this->cityDbPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->asnDbPath && file_exists($this->asnDbPath)) {
            try {
                $this->asnReader = new Reader($this->asnDbPath);
                $this->dbAvailable = true;
            } catch (\Exception $e) {
                Log::warning('GeoIP ASN database could not be opened', [
                    'path' => $this->asnDbPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$this->dbAvailable) {
            Log::warning('GeoIP database not available — geo fields will be null', [
                'city_db' => $this->cityDbPath,
                'asn_db' => $this->asnDbPath,
            ]);
        }
    }

    /**
     * Check whether an IP is public (not private, reserved, or loopback).
     */
    private function isPublicIp(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }
}
