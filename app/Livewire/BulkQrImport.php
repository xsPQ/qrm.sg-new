<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Layout('layouts.app')]
class BulkQrImport extends Component
{
    use WithFileUploads;

    public $csvUpload = null;

    /** @var array<int,array<string,mixed>> */
    public array $previewRows = [];

    /** @var array<int,array<string,mixed>> */
    public array $resultRows = [];

    /** @var array<int,array<string,mixed>> */
    public array $rowErrors = [];

    public array $summary = [
        'imported' => 0,
        'failed' => 0,
        'message' => null,
    ];

    public ?string $previewError = null;

    public function mount(): void
    {
        abort_unless($this->isBusinessUser(), 403);
    }

    public function updatedCsvUpload(): void
    {
        $this->resetImportState();
        $this->previewError = null;

        if (! $this->csvUpload instanceof UploadedFile) {
            return;
        }

        try {
            $parsed = $this->parseCsvFile($this->csvUpload);
        } catch (\Throwable $exception) {
            $this->previewError = $exception->getMessage();

            return;
        }

        $this->previewRows = array_map(
            fn (array $row): array => Arr::only($row, ['row', 'title', 'type', 'content_url', 'content_text', 'alias']),
            array_slice($parsed['rows'], 0, 10),
        );
    }

    public function importCsv(QrCodeService $qrCodeService): void
    {
        $this->validate([
            'csvUpload' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $parsed = $this->parseCsvFile($this->csvUpload);
        $user = auth()->user();

        $normalizedRows = [];
        $errors = [];

        foreach ($parsed['rows'] as $row) {
            $rowErrors = $this->validateRow($row);

            if ($rowErrors !== []) {
                $errors[] = [
                    'row' => $row['row'],
                    'errors' => $rowErrors,
                ];

                continue;
            }

            $normalizedRows[] = $this->buildPayload($row);
        }

        if ($errors !== []) {
            $this->summary = [
                'imported' => 0,
                'failed' => count($errors),
                'message' => __('Import failed. No QR codes were created because one or more rows were invalid.'),
            ];
            $this->rowErrors = $errors;
            $this->resultRows = [];

            return;
        }

        $createdRows = [];

        DB::transaction(function () use ($normalizedRows, $qrCodeService, $user, &$createdRows): void {
            foreach ($normalizedRows as $payload) {
                $qrCode = $qrCodeService->create($user, $payload);
                $route = $qrCode->route;
                $publicKey = $route?->alias ?: $route?->code;
                $publicUrl = $publicKey
                    ? rtrim((string) config('app.url'), '/') . '/' . $publicKey
                    : null;

                $createdRows[] = [
                    'title' => $qrCode->title,
                    'type' => $qrCode->type,
                    'code' => $route?->code,
                    'alias' => $route?->alias,
                    'url' => $publicUrl,
                    'status' => $qrCode->status,
                    'scan_count' => $qrCode->scan_count,
                    'created_at' => $qrCode->created_at?->format('Y-m-d H:i:s'),
                ];
            }
        });

        $this->summary = [
            'imported' => count($createdRows),
            'failed' => 0,
            'message' => __('Import successful. :count QR codes were created.', ['count' => count($createdRows)]),
        ];
        $this->resultRows = $createdRows;
        $this->rowErrors = [];
        $this->previewRows = array_slice($parsed['rows'], 0, 10);
    }

    public function downloadTemplate()
    {
        $headers = ['title', 'type', 'content_url', 'content_text', 'alias'];
        $csv = implode(',', $headers) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bulk-qr-template.csv"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function render()
    {
        return view('livewire.bulk-qr-import');
    }

    private function isBusinessUser(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->plan === EntitlementSnapshot::PLAN_BUSINESS;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new \RuntimeException(__('Unable to read the uploaded CSV file.'));
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                throw new \RuntimeException(__('The uploaded CSV file is empty.'));
            }

            $headers = array_map([$this, 'normalizeHeader'], $headers);
            $expectedHeaders = ['title', 'type', 'content_url', 'content_text', 'alias'];

            if ($headers !== $expectedHeaders) {
                throw new \RuntimeException(__('The CSV headers must be exactly: title,type,content_url,content_text,alias'));
            }

            $rows = [];
            $rowNumber = 2;

            while (($row = fgetcsv($handle)) !== false) {
                $values = array_map(
                    fn ($value): string => trim((string) $value),
                    array_pad($row, 5, ''),
                );

                if ($values === ['', '', '', '', '']) {
                    $rowNumber++;
                    continue;
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'title' => $values[0] ?? '',
                    'type' => $values[1] ?? '',
                    'content_url' => $values[2] ?? '',
                    'content_text' => $values[3] ?? '',
                    'alias' => $values[4] ?? '',
                ];
                $rowNumber++;
            }

            return [
                'headers' => $headers,
                'rows' => $rows,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function validateRow(array $row): array
    {
        $errors = [];
        $title = (string) ($row['title'] ?? '');
        $type = (string) ($row['type'] ?? '');
        $contentUrl = (string) ($row['content_url'] ?? '');
        $contentText = (string) ($row['content_text'] ?? '');
        $alias = (string) ($row['alias'] ?? '');

        if ($title === '') {
            $errors[] = __('Row :row: title is required.', ['row' => $row['row']]);
        }

        if ($type === '') {
            $errors[] = __('Row :row: type is required.', ['row' => $row['row']]);
        } elseif (! in_array($type, ['url', 'message'], true)) {
            $errors[] = __('Row :row: type must be either url or message.', ['row' => $row['row']]);
        }

        if ($type === 'url' && $contentUrl === '') {
            $errors[] = __('Row :row: content_url is required for URL rows.', ['row' => $row['row']]);
        }

        if ($type === 'message' && $contentText === '') {
            $errors[] = __('Row :row: content_text is required for message rows.', ['row' => $row['row']]);
        }

        if ($alias !== '' && mb_strlen($alias) > 255) {
            $errors[] = __('Row :row: alias is too long.', ['row' => $row['row']]);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildPayload(array $row): array
    {
        $payload = [
            'title' => (string) $row['title'],
            'type' => (string) $row['type'],
            'alias' => $row['alias'] !== '' ? (string) $row['alias'] : null,
        ];

        if ($payload['type'] === 'url') {
            $payload['content'] = [
                'url' => (string) $row['content_url'],
            ];
        } else {
            $payload['content'] = [
                'message' => (string) $row['content_text'],
            ];
        }

        return $payload;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = ltrim($value, "\xEF\xBB\xBF");

        return strtolower($value);
    }

    private function resetImportState(): void
    {
        $this->previewRows = [];
        $this->resultRows = [];
        $this->rowErrors = [];
        $this->summary = [
            'imported' => 0,
            'failed' => 0,
            'message' => null,
        ];
    }
}
