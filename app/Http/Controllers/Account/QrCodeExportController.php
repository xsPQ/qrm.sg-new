<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodeExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $qrCodes = QrCode::query()
            ->with('route')
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        $filename = 'qr-codes-export-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($qrCodes): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['title', 'type', 'code', 'alias', 'url', 'status', 'scan_count', 'created_at']);

            foreach ($qrCodes as $qrCode) {
                $route = $qrCode->route;
                $publicKey = $route?->alias ?: $route?->code;
                $url = $publicKey !== null
                    ? rtrim((string) config('app.url'), '/') . '/' . $publicKey
                    : '';

                fputcsv($handle, [
                    $qrCode->title,
                    $qrCode->type,
                    $route?->code ?? '',
                    $route?->alias ?? '',
                    $url,
                    $qrCode->status,
                    (string) $qrCode->scan_count,
                    $qrCode->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function template(Request $request): StreamedResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['title', 'type', 'content_url', 'content_text', 'alias']);
            fputcsv($handle, ['Summer campaign', 'url', 'https://example.com', '', 'summer-2026']);
            fclose($handle);
        }, 'bulk-qr-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
