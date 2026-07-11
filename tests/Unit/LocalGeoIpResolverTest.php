<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Analytics\LocalGeoIpResolver;
use Tests\TestCase;

class LocalGeoIpResolverTest extends TestCase
{
    public function test_private_ip_returns_all_nulls(): void
    {
        $resolver = new LocalGeoIpResolver();

        $result = $resolver->resolve('192.168.1.1');

        $this->assertNull($result['country']);
        $this->assertNull($result['region']);
        $this->assertNull($result['city']);
        $this->assertNull($result['asn']);
    }

    public function test_loopback_ip_returns_all_nulls(): void
    {
        $resolver = new LocalGeoIpResolver();

        $result = $resolver->resolve('127.0.0.1');

        $this->assertNull($result['country']);
        $this->assertNull($result['asn']);
    }

    public function test_reserved_ip_returns_all_nulls(): void
    {
        $resolver = new LocalGeoIpResolver();

        $result = $resolver->resolve('0.0.0.0');

        $this->assertNull($result['country']);
        $this->assertNull($result['asn']);
    }

    public function test_missing_database_returns_all_nulls(): void
    {
        $resolver = new LocalGeoIpResolver(
            cityDbPath: '/nonexistent/GeoLite2-City.mmdb',
            asnDbPath: '/nonexistent/GeoLite2-ASN.mmdb',
        );

        $result = $resolver->resolve('8.8.8.8');

        $this->assertNull($result['country']);
        $this->assertNull($result['region']);
        $this->assertNull($result['city']);
        $this->assertNull($result['asn']);
    }

    public function test_is_available_returns_false_without_database(): void
    {
        $resolver = new LocalGeoIpResolver(
            cityDbPath: '/nonexistent/path.mmdb',
            asnDbPath: '/nonexistent/path.mmdb',
        );

        $this->assertFalse($resolver->isAvailable());
    }

    public function test_ipv6_private_returns_all_nulls(): void
    {
        $resolver = new LocalGeoIpResolver();

        $result = $resolver->resolve('::1');

        $this->assertNull($result['country']);
        $this->assertNull($result['asn']);
    }

    public function test_resolver_does_not_throw_on_invalid_ip(): void
    {
        $resolver = new LocalGeoIpResolver();

        // filter_var rejects this as not a valid IP, so isPublicIp returns false.
        $result = $resolver->resolve('not-an-ip');

        $this->assertNull($result['country']);
        $this->assertNull($result['asn']);
    }
}
