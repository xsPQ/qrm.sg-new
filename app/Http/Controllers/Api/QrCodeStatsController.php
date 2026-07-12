<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ScanStatsDaily;
use App\Models\ScanStatsHourly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCodeStatsController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::findOrFail($id);
        $this->authorize('view', $qrCode);

        $recentScans = Scan::query()
            ->where('qr_code_id', $qrCode->id)
            ->orderByDesc('scanned_at')
            ->limit(20)
            ->get()
            ->map(fn (Scan $scan): array => [
                'device' => $scan->device_type,
                'os' => $scan->os_family,
                'browser' => $scan->browser_family,
                'country' => $scan->geo_country,
                'scanned_at' => $scan->scanned_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'total_scans' => Scan::query()->where('qr_code_id', $qrCode->id)->count(),
            'recent_scans' => $recentScans,
        ]);
    }

    public function daily(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::findOrFail($id);
        $this->authorize('view', $qrCode);

        $daily = ScanStatsDaily::query()
            ->where('qr_code_id', $qrCode->id)
            ->orderBy('day')
            ->get()
            ->map(fn (ScanStatsDaily $row): array => [
                'date' => $row->day->toDateString(),
                'count' => $row->scan_count,
                'unique_ips' => $row->unique_ips,
            ])
            ->values();

        return response()->json([
            'daily' => $daily,
        ]);
    }

    public function hourly(Request $request, int $id): JsonResponse
    {
        $qrCode = QrCode::findOrFail($id);
        $this->authorize('view', $qrCode);

        $hourly = ScanStatsHourly::query()
            ->where('qr_code_id', $qrCode->id)
            ->where('hour', '>=', now()->subHours(48))
            ->orderBy('hour')
            ->get()
            ->map(fn (ScanStatsHourly $row): array => [
                'hour' => $row->hour->toISOString(),
                'count' => $row->scan_count,
            ])
            ->values();

        return response()->json([
            'hourly' => $hourly,
        ]);
    }
}
