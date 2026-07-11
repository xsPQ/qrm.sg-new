<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\QrCodeType;
use Tests\TestCase;

class QrCodeTypeTest extends TestCase
{
    public function test_values_returns_all_cases(): void
    {
        $values = QrCodeType::values();

        $this->assertCount(12, $values);
        $this->assertContains('url', $values);
        $this->assertContains('text', $values);
        $this->assertContains('email', $values);
        $this->assertContains('phone', $values);
        $this->assertContains('sms', $values);
        $this->assertContains('wifi', $values);
        $this->assertContains('crypto', $values);
        $this->assertContains('vcard', $values);
        $this->assertContains('event', $values);
        $this->assertContains('message', $values);
        $this->assertContains('redirect', $values);
        $this->assertContains('social', $values);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('URL', QrCodeType::Url->label());
        $this->assertEquals('Text', QrCodeType::Text->label());
        $this->assertEquals('Email', QrCodeType::Email->label());
        $this->assertEquals('Phone', QrCodeType::Phone->label());
        $this->assertEquals('SMS', QrCodeType::Sms->label());
        $this->assertEquals('WiFi', QrCodeType::Wifi->label());
        $this->assertEquals('vCard', QrCodeType::Vcard->label());
        $this->assertEquals('Event', QrCodeType::Event->label());
        $this->assertEquals('Message', QrCodeType::Message->label());
        $this->assertEquals('Redirect', QrCodeType::Redirect->label());
        $this->assertEquals('Social', QrCodeType::Social->label());
    }

    public function test_every_case_has_a_label(): void
    {
        foreach (QrCodeType::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Case {$case->value} has empty label");
        }
    }
}
