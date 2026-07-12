<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\BulkQrImport;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class BulkImportExportTest extends TestCase
{
    use RefreshDatabase;

    private function grantBusiness(User $user): void
    {
        $user->plan = 'business';
        $user->save();

        Subscription::create([
            'user_id' => $user->id,
            'stripe_id' => 'cus_business_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_business',
            'quantity' => 1,
        ]);
    }

    private function csvUpload(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('bulk-import.csv', $contents);
    }

    public function test_business_user_can_upload_csv_and_create_qr_codes(): void
    {
        $user = User::factory()->create();
        $this->grantBusiness($user);

        $csv = <<<CSV
        title,type,content_url,content_text,alias
        Summer URL,url,https://example.com/summer,,summer-2026
        Welcome Message,message,,Hello world,welcome-message
        CSV;

        $component = Livewire::actingAs($user)
            ->test(BulkQrImport::class)
            ->set('csvUpload', $this->csvUpload($csv))
            ->call('importCsv')
            ->assertHasNoErrors()
            ->assertSet('summary.imported', 2)
            ->assertSet('summary.failed', 0);

        $this->assertCount(2, $component->instance()->resultRows);

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id,
            'title' => 'Summer URL',
            'type' => 'url',
        ]);

        $this->assertDatabaseHas('qr_code_routes', [
            'alias' => 'summer-2026',
        ]);

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id,
            'title' => 'Welcome Message',
            'type' => 'message',
        ]);
    }

    public function test_free_user_is_rejected_from_bulk_import_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/account/bulk-import')
            ->assertForbidden();
    }

    public function test_export_returns_user_qr_codes_as_csv(): void
    {
        $user = User::factory()->create();
        $this->grantBusiness($user);

        $service = app(QrCodeService::class);
        $service->create($user, [
            'title' => 'Export URL',
            'type' => 'url',
            'content' => ['url' => 'https://example.com/export'],
            'alias' => 'export-url',
        ]);
        $service->create($user, [
            'title' => 'Export Message',
            'type' => 'message',
            'content' => ['message' => 'Hello export'],
        ]);

        $response = $this->actingAs($user)->get('/account/qr-codes/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($response->streamedContent()))));

        $this->assertSame(['title', 'type', 'code', 'alias', 'url', 'status', 'scan_count', 'created_at'], str_getcsv($lines[0]));

        $firstRow = str_getcsv($lines[1]);
        $secondRow = str_getcsv($lines[2]);

        $this->assertSame('Export URL', $firstRow[0]);
        $this->assertSame('url', $firstRow[1]);
        $this->assertSame('export-url', $firstRow[3]);
        $this->assertStringContainsString('/export-url', $firstRow[4]);
        $this->assertSame('active', $firstRow[5]);

        $this->assertSame('Export Message', $secondRow[0]);
        $this->assertSame('message', $secondRow[1]);
        $this->assertSame('', $secondRow[3]);
    }
}
