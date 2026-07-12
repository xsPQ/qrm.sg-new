<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrTypeSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_8_types(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk()
            ->assertJsonCount(8, 'types');
    }

    public function test_each_type_has_fields_array(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk();

        foreach ($response->json('types') as $type) {
            $this->assertArrayHasKey('fields', $type);
            $this->assertIsArray($type['fields']);
        }
    }

    public function test_select_fields_have_options(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk();

        foreach ($response->json('types') as $type) {
            foreach ($type['fields'] as $field) {
                if (($field['type'] ?? null) !== 'select') {
                    continue;
                }

                $this->assertArrayHasKey('options', $field);
                $this->assertIsArray($field['options']);
                $this->assertNotEmpty($field['options']);
            }
        }
    }

    public function test_url_type_has_correct_fields(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk()
            ->assertJsonPath('types.0.id', 'url')
            ->assertJsonPath('types.0.label', 'URL')
            ->assertJsonPath('types.0.description', 'Open a link')
            ->assertJsonPath('types.0.icon', 'link')
            ->assertJsonPath('types.0.fields.0.key', 'url')
            ->assertJsonPath('types.0.fields.0.label', 'Destination URL')
            ->assertJsonPath('types.0.fields.0.type', 'url')
            ->assertJsonPath('types.0.fields.0.required', true)
            ->assertJsonPath('types.0.fields.0.maxlength', 2048)
            ->assertJsonPath('types.0.fields.0.placeholder', 'https://example.com');
    }

    public function test_wifi_type_has_correct_fields(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk();

        $wifi = collect($response->json('types'))->firstWhere('id', 'wifi');

        $this->assertNotNull($wifi);
        $this->assertSame('WiFi', $wifi['label']);
        $this->assertSame('Join network', $wifi['description']);
        $this->assertSame('wifi', $wifi['icon']);
        $this->assertSame('ssid', $wifi['fields'][0]['key']);
        $this->assertSame('text', $wifi['fields'][0]['type']);
        $this->assertSame('encryption', $wifi['fields'][1]['key']);
        $this->assertSame('select', $wifi['fields'][1]['type']);
        $this->assertSame('password', $wifi['fields'][2]['key']);
        $this->assertSame('checkbox', $wifi['fields'][3]['type']);
    }

    public function test_no_auth_required(): void
    {
        $response = $this->getJson('/api/qr-types');

        $response->assertOk();
    }
}
