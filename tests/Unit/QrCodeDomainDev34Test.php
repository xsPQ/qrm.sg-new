<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\QrTypes\ContactQr;
use App\Domain\QrTypes\CryptoQr;
use App\Domain\QrTypes\EventQr;
use App\Domain\QrTypes\WifiQr;
use InvalidArgumentException;
use Tests\TestCase;

class QrCodeDomainDev34Test extends TestCase
{
    public function test_wifi_qr_from_array_valid(): void
    {
        $qr = WifiQr::fromArray([
            'ssid' => 'MyNetwork',
            'encryption' => 'WPA2',
            'password' => 'secret123',
            'hidden' => false,
        ]);

        $this->assertInstanceOf(WifiQr::class, $qr);
        $this->assertEquals('MyNetwork', $qr->ssid);
        $this->assertEquals('WPA2', $qr->encryption);
        $this->assertEquals('secret123', $qr->password);
        $this->assertFalse($qr->hidden);
    }

    public function test_wifi_qr_from_array_defaults(): void
    {
        $qr = WifiQr::fromArray([
            'ssid' => 'MyNetwork',
        ]);

        $this->assertEquals('WPA', $qr->encryption);
        $this->assertNull($qr->password);
        $this->assertFalse($qr->hidden);
    }

    public function test_wifi_qr_from_array_rejects_missing_ssid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQr::fromArray([]);
    }

    public function test_wifi_qr_from_array_rejects_empty_ssid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQr::fromArray(['ssid' => '']);
    }

    public function test_wifi_qr_from_array_rejects_long_ssid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQr::fromArray(['ssid' => str_repeat('a', 33)]);
    }

    public function test_wifi_qr_from_array_rejects_invalid_encryption(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQr::fromArray([
            'ssid' => 'MyNetwork',
            'encryption' => 'WPA3',
        ]);
    }

    public function test_wifi_qr_from_array_allows_none_encryption(): void
    {
        $qr = WifiQr::fromArray([
            'ssid' => 'OpenNetwork',
            'encryption' => 'none',
        ]);

        $this->assertEquals('none', $qr->encryption);
    }

    public function test_wifi_qr_from_array_allows_all_encryption_types(): void
    {
        foreach (['WPA', 'WPA2', 'WEP', 'none'] as $enc) {
            $qr = WifiQr::fromArray([
                'ssid' => 'Test',
                'encryption' => $enc,
            ]);
            $this->assertEquals($enc, $qr->encryption);
        }
    }

    public function test_wifi_qr_from_array_password_required_for_encrypted(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQr::fromArray([
            'ssid' => 'SecureNet',
            'encryption' => 'WPA2',
            'password' => '',
        ]);
    }

    public function test_wifi_qr_to_array(): void
    {
        $qr = WifiQr::fromArray([
            'ssid' => 'MyNetwork',
            'encryption' => 'WPA2',
            'password' => 'secret',
            'hidden' => true,
        ]);
        $data = $qr->toArray();

        $this->assertEquals('MyNetwork', $data['ssid']);
        $this->assertEquals('WPA2', $data['encryption']);
        $this->assertEquals('secret', $data['password']);
        $this->assertTrue($data['hidden']);
    }

    public function test_wifi_qr_rules(): void
    {
        $rules = WifiQr::rules();

        $this->assertArrayHasKey('content.ssid', $rules);
        $this->assertArrayHasKey('content.encryption', $rules);
        $this->assertArrayHasKey('content.password', $rules);
        $this->assertArrayHasKey('content.hidden', $rules);
    }

    public function test_crypto_qr_from_array_valid(): void
    {
        $qr = CryptoQr::fromArray([
            'currency' => 'BTC',
            'address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'amount' => 0.5,
            'label' => 'Donation',
        ]);

        $this->assertInstanceOf(CryptoQr::class, $qr);
        $this->assertEquals('BTC', $qr->currency);
        $this->assertEquals('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', $qr->address);
        $this->assertEquals(0.5, $qr->amount);
        $this->assertEquals('Donation', $qr->label);
    }

    public function test_crypto_qr_from_array_minimal(): void
    {
        $qr = CryptoQr::fromArray([
            'currency' => 'ETH',
            'address' => '0x1234567890abcdef',
        ]);

        $this->assertEquals('ETH', $qr->currency);
        $this->assertNull($qr->amount);
        $this->assertNull($qr->label);
    }

    public function test_crypto_qr_from_array_rejects_missing_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray(['address' => '0x123']);
    }

    public function test_crypto_qr_from_array_rejects_invalid_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray([
            'currency' => 'DOGE',
            'address' => '0x123',
        ]);
    }

    public function test_crypto_qr_from_array_allows_all_currencies(): void
    {
        foreach (['BTC', 'ETH', 'SOL', 'USDT', 'USDC'] as $currency) {
            $qr = CryptoQr::fromArray([
                'currency' => $currency,
                'address' => 'test-address',
            ]);
            $this->assertEquals($currency, $qr->currency);
        }
    }

    public function test_crypto_qr_from_array_rejects_missing_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray(['currency' => 'BTC']);
    }

    public function test_crypto_qr_from_array_rejects_empty_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray([
            'currency' => 'BTC',
            'address' => '',
        ]);
    }

    public function test_crypto_qr_from_array_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray([
            'currency' => 'BTC',
            'address' => 'test',
            'amount' => -1,
        ]);
    }

    public function test_crypto_qr_from_array_rejects_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CryptoQr::fromArray([
            'currency' => 'BTC',
            'address' => 'test',
            'amount' => 0,
        ]);
    }

    public function test_crypto_qr_to_array_filters_null_values(): void
    {
        $qr = CryptoQr::fromArray([
            'currency' => 'BTC',
            'address' => 'test',
        ]);
        $data = $qr->toArray();

        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('address', $data);
        $this->assertArrayNotHasKey('amount', $data);
        $this->assertArrayNotHasKey('label', $data);
    }

    public function test_crypto_qr_rules(): void
    {
        $rules = CryptoQr::rules();

        $this->assertArrayHasKey('content.currency', $rules);
        $this->assertArrayHasKey('content.address', $rules);
        $this->assertArrayHasKey('content.amount', $rules);
        $this->assertArrayHasKey('content.label', $rules);
    }

    public function test_event_qr_from_array_valid(): void
    {
        $qr = EventQr::fromArray([
            'title' => 'Conference 2026',
            'start' => '2026-12-01T09:00:00Z',
            'end' => '2026-12-01T17:00:00Z',
            'location' => 'Berlin',
            'description' => 'Annual conference',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->assertInstanceOf(EventQr::class, $qr);
        $this->assertEquals('Conference 2026', $qr->title);
        $this->assertEquals('2026-12-01T09:00:00Z', $qr->start);
        $this->assertEquals('2026-12-01T17:00:00Z', $qr->end);
    }

    public function test_event_qr_from_array_minimal(): void
    {
        $qr = EventQr::fromArray([
            'title' => 'Meeting',
            'start' => '2026-12-01T09:00:00Z',
        ]);

        $this->assertEquals('Meeting', $qr->title);
        $this->assertNull($qr->end);
        $this->assertNull($qr->location);
        $this->assertNull($qr->description);
        $this->assertEquals('UTC', $qr->timezone);
    }

    public function test_event_qr_from_array_rejects_missing_title(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventQr::fromArray(['start' => '2026-12-01T09:00:00Z']);
    }

    public function test_event_qr_from_array_rejects_missing_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventQr::fromArray(['title' => 'Meeting']);
    }

    public function test_event_qr_from_array_rejects_invalid_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventQr::fromArray([
            'title' => 'Meeting',
            'start' => 'not-a-date',
        ]);
    }

    public function test_event_qr_from_array_allows_null_end(): void
    {
        $qr = EventQr::fromArray([
            'title' => 'Meeting',
            'start' => '2026-12-01T09:00:00Z',
            'end' => null,
        ]);

        $this->assertNull($qr->end);
    }

    public function test_event_qr_from_array_rejects_invalid_end(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EventQr::fromArray([
            'title' => 'Meeting',
            'start' => '2026-12-01T09:00:00Z',
            'end' => 'not-a-date',
        ]);
    }

    public function test_event_qr_to_array_filters_nulls(): void
    {
        $qr = EventQr::fromArray([
            'title' => 'Meeting',
            'start' => '2026-12-01T09:00:00Z',
        ]);
        $data = $qr->toArray();

        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayNotHasKey('end', $data);
    }

    public function test_event_qr_rules(): void
    {
        $rules = EventQr::rules();

        $this->assertArrayHasKey('content.title', $rules);
        $this->assertArrayHasKey('content.start', $rules);
        $this->assertArrayHasKey('content.end', $rules);

        $endRule = implode('|', $rules['content.end']);
        $this->assertStringContainsString('after:content.start', $endRule);
    }

    public function test_contact_qr_from_array_valid(): void
    {
        $qr = ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'organization' => 'Acme Inc',
            'email' => 'john@example.com',
            'phone_mobile' => '+1234567890',
        ]);

        $this->assertInstanceOf(ContactQr::class, $qr);
        $this->assertEquals('John', $qr->firstName);
        $this->assertEquals('Doe', $qr->lastName);
        $this->assertEquals('Acme Inc', $qr->organization);
        $this->assertEquals('john@example.com', $qr->email);
        $this->assertEquals('+1234567890', $qr->phoneMobile);
    }

    public function test_contact_qr_from_array_minimal(): void
    {
        $qr = ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John', $qr->firstName);
        $this->assertEquals('Doe', $qr->lastName);
        $this->assertNull($qr->organization);
        $this->assertNull($qr->email);
    }

    public function test_contact_qr_from_array_rejects_missing_first_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactQr::fromArray(['last_name' => 'Doe']);
    }

    public function test_contact_qr_from_array_rejects_missing_last_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactQr::fromArray(['first_name' => 'John']);
    }

    public function test_contact_qr_from_array_rejects_long_first_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactQr::fromArray([
            'first_name' => str_repeat('a', 101),
            'last_name' => 'Doe',
        ]);
    }

    public function test_contact_qr_from_array_rejects_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'not-an-email',
        ]);
    }

    public function test_contact_qr_from_array_rejects_invalid_website(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'website' => 'not-a-url',
        ]);
    }

    public function test_contact_qr_from_array_accepts_valid_email(): void
    {
        $qr = ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'valid@example.com',
        ]);

        $this->assertEquals('valid@example.com', $qr->email);
    }

    public function test_contact_qr_from_array_accepts_valid_website(): void
    {
        $qr = ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'website' => 'https://example.com',
        ]);

        $this->assertEquals('https://example.com', $qr->website);
    }

    public function test_contact_qr_to_array_filters_nulls(): void
    {
        $qr = ContactQr::fromArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        $data = $qr->toArray();

        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayNotHasKey('organization', $data);
    }

    public function test_contact_qr_rules(): void
    {
        $rules = ContactQr::rules();

        $this->assertArrayHasKey('content.first_name', $rules);
        $this->assertArrayHasKey('content.last_name', $rules);
        $this->assertArrayHasKey('content.email', $rules);
        $this->assertArrayHasKey('content.website', $rules);
    }
}
