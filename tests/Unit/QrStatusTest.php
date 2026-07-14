<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\QrStatus;
use Tests\TestCase;

class QrStatusTest extends TestCase
{
    public function test_values_returns_all_cases(): void
    {
        $values = QrStatus::values();

        $this->assertCount(4, $values);
        $this->assertContains('active', $values);
        $this->assertContains('expired', $values);
        $this->assertContains('burned', $values);
        $this->assertContains('disabled', $values);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Active', QrStatus::Active->label());
        $this->assertEquals('Expired', QrStatus::Expired->label());
        $this->assertEquals('Burned', QrStatus::Burned->label());
        $this->assertEquals('Disabled', QrStatus::Disabled->label());
    }

    public function test_every_case_has_a_label(): void
    {
        foreach (QrStatus::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Case {$case->value} has empty label");
        }
    }
}
