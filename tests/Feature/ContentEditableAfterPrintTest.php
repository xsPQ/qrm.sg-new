<?php

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the central selling point of qrm.sg: a printed QR code
 * encodes only the resolver URL, so content can be changed at any time
 * without reprinting.
 *
 * Pflichtenheft §1.1 — "Editierbarkeit nach dem Druck".
 */
class ContentEditableAfterPrintTest extends TestCase
{
    use RefreshDatabase;

    private function createQrCodeWithRoute(string $type, array $content): array
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test ' . $type,
            'type' => $type,
            'content' => $content,
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
        ]);

        $route = QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'TEST01',
            'host' => 'localhost',
        ]);

        return [$user, $qrCode, $route];
    }

    public function test_event_time_can_be_changed_after_print_and_resolver_serves_new_content(): void
    {
        // Step 1: Create an event QR with the WRONG time (18:00)
        [$user, $qrCode, $route] = $this->createQrCodeWithRoute('event', [
            'title' => 'Sommerfest 2026',
            'start' => '2026-08-15T18:00:00',
            'end' => '2026-08-15T22:00:00',
            'location' => 'Rathausplatz',
        ]);

        // The QR code payload is the resolver URL — NOT the event data
        $this->assertStringNotContainsString('Sommerfest', $route->code);

        // Step 2: Simulate "printing" — the code is now fixed
        $printedCode = $route->code;
        $this->assertSame('TEST01', $printedCode);

        // Step 3: Resolve with original content — should show 18:00
        $response = $this->get('/' . $printedCode);
        $response->assertOk();
        $response->assertSee('18:00');

        // Step 4: Oops — wrong time! Fix it to 15:00
        $qrCode->update([
            'content' => [
                'title' => 'Sommerfest 2026',
                'start' => '2026-08-15T15:00:00',
                'end' => '2026-08-15T22:00:00',
                'location' => 'Rathausplatz',
            ],
        ]);

        // Step 5: Same QR code — now serves the corrected time
        $response2 = $this->get('/' . $printedCode);
        $response2->assertOk();
        $response2->assertSee('15:00');
        $response2->assertDontSee('18:00');
    }

    public function test_wifi_password_can_be_changed_after_print(): void
    {
        [$user, $qrCode, $route] = $this->createQrCodeWithRoute('wifi', [
            'ssid' => 'GuestNetwork',
            'encryption' => 'WPA',
            'password' => 'OldPassword123',
        ]);

        $printedCode = $route->code;

        // Original password visible
        $response = $this->get('/' . $printedCode);
        $response->assertOk();
        $response->assertSee('OldPassword123');

        // Change password after printing
        $qrCode->update([
            'content' => [
                'ssid' => 'GuestNetwork',
                'encryption' => 'WPA',
                'password' => 'NewSecurePassword456',
            ],
        ]);

        // Same QR — new password
        $response2 = $this->get('/' . $printedCode);
        $response2->assertOk();
        $response2->assertSee('NewSecurePassword456');
        $response2->assertDontSee('OldPassword123');
    }

    public function test_message_text_can_be_updated_without_changing_code(): void
    {
        [$user, $qrCode, $route] = $this->createQrCodeWithRoute('message', [
            'title' => 'Messe-Info',
            'body' => 'Willkommen am Stand 42!',
        ]);

        $printedCode = $route->code;

        // Original message
        $response = $this->get('/' . $printedCode);
        $response->assertOk();
        $response->assertSee('Stand 42');

        // Update message
        $qrCode->update([
            'content' => ['title' => 'Messe-Info', 'body' => 'Willkommen am Stand 99! Wir sind umgezogen.'],
        ]);

        // Same QR — new message
        $response2 = $this->get('/' . $printedCode);
        $response2->assertOk();
        $response2->assertSee('Stand 99');
        $response2->assertDontSee('Stand 42');
    }

    public function test_url_redirect_destination_can_be_swapped(): void
    {
        [$user, $qrCode, $route] = $this->createQrCodeWithRoute('url', [
            'url' => 'https://landing-page-a.example.com',
        ]);

        $printedCode = $route->code;

        // Original redirect
        $response = $this->get('/' . $printedCode);
        $response->assertRedirect('https://landing-page-a.example.com');

        // Swap to new campaign landing page
        $qrCode->update([
            'content' => ['url' => 'https://landing-page-b.example.com'],
        ]);

        // Same QR — new destination
        $response2 = $this->get('/' . $printedCode);
        $response2->assertRedirect('https://landing-page-b.example.com');
    }

    public function test_contact_details_can_be_updated_without_reprint(): void
    {
        [$user, $qrCode, $route] = $this->createQrCodeWithRoute('vcard', [
            'firstName' => 'Anna',
            'lastName' => 'Mueller',
            'phone' => '+49 170 1234567',
            'email' => 'anna@old-company.com',
        ]);

        $printedCode = $route->code;

        // Original contact
        $response = $this->get('/' . $printedCode);
        $response->assertOk();
        $response->assertSee('+49 170 1234567');

        // Anna changes jobs — new phone and email
        $qrCode->update([
            'content' => [
                'firstName' => 'Anna',
                'lastName' => 'Schmidt',
                'phone' => '+49 171 9876543',
                'email' => 'anna@new-company.com',
            ],
        ]);

        // Same QR — updated contact
        $response2 = $this->get('/' . $printedCode);
        $response2->assertOk();
        $response2->assertSee('+49 171 9876543');
        $response2->assertSee('anna@new-company.com');
        $response2->assertDontSee('+49 170 1234567');
    }
}
